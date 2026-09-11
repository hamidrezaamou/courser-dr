<#
.SYNOPSIS
    Push clinic HIS data to the website, read-only against SQL Server.

.DESCRIPTION
    Reads batches from the Bina HIS database and POSTs them to
    /api/his/sync/{resource}. Never writes to HIS. The website owns the
    watermark, so a reinstalled agent resumes instead of replaying history.

.EXAMPLE
    .\sync.ps1
    .\sync.ps1 -DryRun
    .\sync.ps1 -Resource patients
#>

[CmdletBinding()]
param(
    [string] $ConfigPath = "$PSScriptRoot\config.json",
    [string] $Resource = '',
    [switch] $DryRun,
    [switch] $PingOnly
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------

$logDir = Join-Path $PSScriptRoot 'logs'
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
}
$logFile = Join-Path $logDir ("sync-{0:yyyyMMdd}.log" -f (Get-Date))

function Write-HisLog {
    param([string] $Message, [string] $Level = 'INFO')
    $line = '{0:yyyy-MM-dd HH:mm:ss} [{1}] {2}' -f (Get-Date), $Level, $Message
    Add-Content -Path $logFile -Value $line -Encoding UTF8
    if ($Level -eq 'ERROR') {
        Write-Host $line -ForegroundColor Red
    } elseif ($Level -eq 'WARN') {
        Write-Host $line -ForegroundColor Yellow
    } else {
        Write-Host $line
    }
}

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------

if (-not (Test-Path $ConfigPath)) {
    throw "Config not found: $ConfigPath  (copy config.example.json to config.json and fill it in)"
}

$config = Get-Content -Raw -Path $ConfigPath | ConvertFrom-Json
$baseUrl = ($config.website.baseUrl -replace '/$', '')
$agentKey = [string] $config.website.agentKey
$agentSecret = [string] $config.website.agentSecret
$timeout = if ($config.website.timeoutSeconds) { [int] $config.website.timeoutSeconds } else { 120 }
$batchSize = if ($config.batchSize) { [int] $config.batchSize } else { 200 }

if ([string]::IsNullOrWhiteSpace($baseUrl) -or $baseUrl -notmatch '^https://') {
    throw 'website.baseUrl must be an https:// URL. Patient identifiers must not travel over plain HTTP.'
}
if ([string]::IsNullOrWhiteSpace($agentKey) -or $agentKey -match 'PUT-THE-SAME') {
    throw 'website.agentKey is not configured.'
}

# ---------------------------------------------------------------------------
# SQL (SELECT only)
# ---------------------------------------------------------------------------

function New-HisConnectionString {
    $server = [string] $config.sql.server
    $database = [string] $config.sql.database
    $user = [string] $config.sql.user
    $password = [string] $config.sql.password

    # ApplicationIntent=ReadOnly is a second belt on top of a db_datareader login.
    $base = "Server=$server;Database=$database;ApplicationIntent=ReadOnly;Connect Timeout=30;"
    if ([string]::IsNullOrWhiteSpace($user)) {
        return $base + 'Integrated Security=SSPI;'
    }
    return $base + "User ID=$user;Password=$password;"
}

function Invoke-HisSelect {
    param(
        [string] $Query,
        [hashtable] $Parameters
    )

    # Hard ban: this agent must never mutate HIS.
    if ($Query -match '(?i)\b(INSERT|UPDATE|DELETE|MERGE|ALTER|DROP|CREATE|TRUNCATE|EXEC|EXECUTE|sp_)\b') {
        throw "Refusing to run a non-SELECT statement: $($Query.Substring(0, [Math]::Min(80, $Query.Length)))"
    }

    $connection = New-Object System.Data.SqlClient.SqlConnection (New-HisConnectionString)
    try {
        $connection.Open()
        $command = $connection.CreateCommand()
        $command.CommandText = $Query
        $command.CommandTimeout = 120

        foreach ($key in $Parameters.Keys) {
            $value = $Parameters[$key]
            if ($null -eq $value) {
                [void] $command.Parameters.AddWithValue($key, [DBNull]::Value)
            } else {
                [void] $command.Parameters.AddWithValue($key, $value)
            }
        }

        $adapter = New-Object System.Data.SqlClient.SqlDataAdapter $command
        $table = New-Object System.Data.DataTable
        [void] $adapter.Fill($table)
        return $table
    }
    finally {
        $connection.Close()
    }
}

# ---------------------------------------------------------------------------
# Jalali → Gregorian (for nvarchar date columns in Bina)
# ---------------------------------------------------------------------------

function ConvertFrom-JalaliDate {
    param([string] $Text)

    $digits = $Text -replace '[^\d]', ''
    if ($digits.Length -lt 8) { return $null }

    $jy = [int] $digits.Substring(0, 4)
    $jm = [int] $digits.Substring(4, 2)
    $jd = [int] $digits.Substring(6, 2)

    if ($jy -lt 1200 -or $jy -gt 1500) { return $null }

    $gy = if ($jy -gt 979) { 1600 } else { 621 }
    $days = if ($jy -gt 979) {
        (($jy - 979) * 365) + ([Math]::Floor(($jy - 979) / 33) * 8) + [Math]::Floor(((($jy - 979) % 33) + 3) / 4)
    } else {
        (($jy - 1) * 365) + [Math]::Floor($jy / 33) * 8 + [Math]::Floor((($jy % 33) + 3) / 4)
    }

    $gDayNo = $days + (
        if ($jm -lt 7) { ($jm - 1) * 31 } else { (($jm - 7) * 30) + 186 }
    ) + $jd - 1

    $gy += 400 * [Math]::Floor($gDayNo / 146097)
    $gDayNo = $gDayNo % 146097

    $leap = $true
    if ($gDayNo -ge 36525) {
        $gDayNo--
        $gy += 100 * [Math]::Floor($gDayNo / 36524)
        $gDayNo = $gDayNo % 36524
        if ($gDayNo -ge 365) { $gDayNo++ } else { $leap = $false }
    }

    $gy += 4 * [Math]::Floor($gDayNo / 1461)
    $gDayNo = $gDayNo % 1461

    if ($gDayNo -ge 366) {
        $leap = $false
        $gDayNo--
        $gy += [Math]::Floor($gDayNo / 365)
        $gDayNo = $gDayNo % 365
    }

    $sal = @(0, 31, $(if ($leap) { 29 } else { 28 }), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31)
    $gm = 0
    for ($i = 1; $i -le 12; $i++) {
        if ($gDayNo -lt $sal[$i]) { $gm = $i; break }
        $gDayNo -= $sal[$i]
    }
    $gd = $gDayNo + 1

    return '{0:D4}-{1:D2}-{2:D2}' -f $gy, $gm, $gd
}

function Convert-RowDates {
    param($RowHashtable, [string[]] $JalaliColumns)

    foreach ($column in $JalaliColumns) {
        if (-not $RowHashtable.ContainsKey($column)) { continue }
        $raw = [string] $RowHashtable[$column]
        if ([string]::IsNullOrWhiteSpace($raw)) { continue }
        if ($raw -match '^\d{4}-\d{2}-\d{2}') { continue }

        $converted = ConvertFrom-JalaliDate $raw
        if ($converted) {
            $RowHashtable[$column] = $converted
        }
    }

    return $RowHashtable
}

# ---------------------------------------------------------------------------
# HTTP to the website
# ---------------------------------------------------------------------------

function Invoke-HisApi {
    param(
        [string] $Method,
        [string] $Path,
        [object] $Body = $null
    )

    $uri = "$baseUrl$Path"
    $headers = @{
        'Authorization' = "Bearer $agentKey"
        'Accept'        = 'application/json'
        'X-His-Key'     = $agentKey
    }

    $json = $null
    if ($null -ne $Body) {
        $json = $Body | ConvertTo-Json -Depth 8 -Compress
    } else {
        $json = ''
    }

    if (-not [string]::IsNullOrWhiteSpace($agentSecret) -and $agentSecret -notmatch 'PUT-THE-SAME') {
        $timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds().ToString()
        $payload = if ($Method -eq 'GET') { '' } else { $json }
        $signature = [System.BitConverter]::ToString(
            (New-Object System.Security.Cryptography.HMACSHA256 (
                [System.Text.Encoding]::UTF8.GetBytes($agentSecret)
            )).ComputeHash([System.Text.Encoding]::UTF8.GetBytes("$timestamp.$payload"))
        ).Replace('-', '').ToLowerInvariant()

        $headers['X-His-Timestamp'] = $timestamp
        $headers['X-His-Signature'] = $signature
    }

    $params = @{
        Method      = $Method
        Uri         = $uri
        Headers     = $headers
        TimeoutSec  = $timeout
        ContentType = 'application/json; charset=utf-8'
    }
    if ($Method -ne 'GET') {
        $params['Body'] = [System.Text.Encoding]::UTF8.GetBytes($json)
    }

    $response = Invoke-WebRequest @params
    return ($response.Content | ConvertFrom-Json)
}

function Get-HisCursor {
    param([string] $Name)
    return Invoke-HisApi -Method GET -Path "/api/his/cursor/$Name"
}

function Send-HisBatch {
    param(
        [string] $Name,
        [object[]] $Rows
    )

    $batchId = [guid]::NewGuid().ToString()
    $body = @{
        batch_id = $batchId
        rows     = @($Rows)
    }

    if ($DryRun) {
        Write-HisLog "DRY-RUN $Name batch=$batchId rows=$($Rows.Count) first_his_id=$($Rows[0].his_id)"
        return @{
            received = $Rows.Count
            created  = 0
            updated  = 0
            failed   = 0
            cursor   = @{ last_id = $Rows[-1].his_id; last_changed_at = $Rows[-1].changed_at }
        }
    }

    return Invoke-HisApi -Method POST -Path "/api/his/sync/$Name" -Body $body
}

# ---------------------------------------------------------------------------
# Row shaping
# ---------------------------------------------------------------------------

function Convert-DataRowToHashtable {
    param([System.Data.DataRow] $DataRow)

    $hash = [ordered]@{}
    foreach ($column in $DataRow.Table.Columns) {
        $value = $DataRow[$column.ColumnName]
        if ($value -is [DBNull]) {
            $hash[$column.ColumnName] = $null
        } elseif ($value -is [datetime]) {
            $hash[$column.ColumnName] = $value.ToString('yyyy-MM-dd HH:mm:ss')
        } else {
            $hash[$column.ColumnName] = $value
        }
    }
    return $hash
}

# ---------------------------------------------------------------------------
# One resource
# ---------------------------------------------------------------------------

function Sync-HisResource {
    param(
        [string] $Name,
        $Definition
    )

    if (-not $Definition.enabled) {
        Write-HisLog "skip $Name (disabled in config)"
        return
    }

    Write-HisLog "=== $Name ==="

    $cursor = Get-HisCursor -Name $Name
    $lastId = $cursor.last_id
    $lastChangedAt = $null
    if ($cursor.last_changed_at) {
        try { $lastChangedAt = [datetime]::Parse($cursor.last_changed_at).ToString('yyyy-MM-dd HH:mm:ss') } catch { $lastChangedAt = $null }
    }

    Write-HisLog "cursor last_id=$lastId last_changed_at=$lastChangedAt"

    $totalReceived = 0
    $totalCreated = 0
    $totalUpdated = 0
    $totalFailed = 0
    $rounds = 0

    while ($true) {
        $rounds++
        if ($rounds -gt 500) {
            Write-HisLog "safety stop after 500 rounds for $Name" 'WARN'
            break
        }

        $parameters = @{
            '@Top'           = $batchSize
            '@LastId'        = $(if ($lastId) { $lastId } else { [DBNull]::Value })
            '@LastChangedAt' = $(if ($lastChangedAt) { $lastChangedAt } else { [DBNull]::Value })
        }

        $table = Invoke-HisSelect -Query ([string] $Definition.query) -Parameters $parameters
        if ($table.Rows.Count -eq 0) {
            Write-HisLog "$Name: no more rows"
            break
        }

        $rows = @()
        foreach ($dataRow in $table.Rows) {
            $hash = Convert-DataRowToHashtable $dataRow
            $jalali = @()
            if ($Definition.jalaliColumns) { $jalali = @($Definition.jalaliColumns) }
            $hash = Convert-RowDates -RowHashtable $hash -JalaliColumns $jalali

            if (-not $hash.Contains('his_id') -or [string]::IsNullOrWhiteSpace([string] $hash['his_id'])) {
                Write-HisLog "$Name: row missing his_id, skipped" 'WARN'
                continue
            }

            $rows += [pscustomobject]$hash
        }

        if ($rows.Count -eq 0) { break }

        $result = Send-HisBatch -Name $Name -Rows $rows
        $totalReceived += [int] $result.received
        $totalCreated += [int] $result.created
        $totalUpdated += [int] $result.updated
        $totalFailed += [int] $result.failed

        Write-HisLog ("{0}: batch ok received={1} created={2} updated={3} failed={4}" -f `
            $Name, $result.received, $result.created, $result.updated, $result.failed)

        if ($result.errors) {
            foreach ($err in $result.errors) {
                Write-HisLog ("{0} row error his_id={1}: {2}" -f $Name, $err.his_id, $err.error) 'WARN'
            }
        }

        # Advance from the website's cursor (authoritative), falling back to the
        # last row we just sent so DryRun still progresses.
        if ($result.cursor -and $result.cursor.last_id) {
            $lastId = [string] $result.cursor.last_id
        } else {
            $lastId = [string] $rows[-1].his_id
        }

        if ($result.cursor -and $result.cursor.last_changed_at) {
            try {
                $lastChangedAt = [datetime]::Parse([string] $result.cursor.last_changed_at).ToString('yyyy-MM-dd HH:mm:ss')
            } catch {
                $lastChangedAt = [string] $result.cursor.last_changed_at
            }
        } elseif ($rows[-1].changed_at) {
            $lastChangedAt = [string] $rows[-1].changed_at
        }

        # Partial page means we are done for this run.
        if ($table.Rows.Count -lt $batchSize) { break }

        Start-Sleep -Milliseconds 400
    }

    Write-HisLog ("{0} done: received={1} created={2} updated={3} failed={4}" -f `
        $Name, $totalReceived, $totalCreated, $totalUpdated, $totalFailed)
}

# ---------------------------------------------------------------------------
# Log retention
# ---------------------------------------------------------------------------

function Clear-OldLogs {
    $days = if ($config.logRetentionDays) { [int] $config.logRetentionDays } else { 14 }
    $cutoff = (Get-Date).AddDays(-$days)
    Get-ChildItem -Path $logDir -Filter 'sync-*.log' -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -lt $cutoff } |
        Remove-Item -Force -ErrorAction SilentlyContinue
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

Write-HisLog "start DryRun=$DryRun"
Clear-OldLogs

try {
    $ping = Invoke-HisApi -Method GET -Path '/api/his/ping'
    Write-HisLog ("ping ok server_time={0} resources={1}" -f $ping.server_time, ($ping.resources -join ','))
}
catch {
    Write-HisLog ("ping failed: {0}" -f $_.Exception.Message) 'ERROR'
    throw
}

if ($PingOnly) {
    Write-HisLog 'PingOnly — exiting'
    exit 0
}

$resourceNames = @('patients', 'appointments', 'visits', 'finance')
if (-not [string]::IsNullOrWhiteSpace($Resource)) {
    $resourceNames = @($Resource)
}

foreach ($name in $resourceNames) {
    $definition = $config.resources.$name
    if (-not $definition) {
        Write-HisLog "no config for resource $name" 'WARN'
        continue
    }

    try {
        Sync-HisResource -Name $name -Definition $definition
    }
    catch {
        Write-HisLog ("$name failed: {0}" -f $_.Exception.Message) 'ERROR'
    }
}

Write-HisLog 'finished'
