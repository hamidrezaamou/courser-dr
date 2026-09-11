<#
.SYNOPSIS
    Read-only survey of the Bina HIS database.

.DESCRIPTION
    Run this once, before any sync is configured. It only issues SELECT and
    catalog queries; it never writes, and it never prints patient data.

    Bina has ~965 tables, so the column names for patients, appointments,
    admissions and money cannot be guessed from the outside. This script finds
    the likely candidates and prints their columns so config.json can be filled
    in with real names instead of assumptions.

.EXAMPLE
    .\discover.ps1 -SqlServer localhost -Database HIS -OutFile .\discovery.txt
#>

[CmdletBinding()]
param(
    [string] $SqlServer = 'localhost',
    [string] $Database = 'HIS',

    # Leave empty to use the Windows account this script runs as.
    [string] $SqlUser = '',
    [string] $SqlPassword = '',

    [string] $OutFile = "$PSScriptRoot\discovery.txt"
)

$ErrorActionPreference = 'Stop'

function New-HisConnectionString {
    param([string] $Server, [string] $Db, [string] $User, [string] $Password)

    # ApplicationIntent=ReadOnly is a second belt: even a mistyped query cannot
    # write if the server routes us to a read-only replica.
    $base = "Server=$Server;Database=$Db;ApplicationIntent=ReadOnly;Connect Timeout=30;"

    if ([string]::IsNullOrWhiteSpace($User)) {
        return $base + 'Integrated Security=SSPI;'
    }

    return $base + "User ID=$User;Password=$Password;"
}

function Invoke-HisQuery {
    param([string] $ConnectionString, [string] $Query)

    $connection = New-Object System.Data.SqlClient.SqlConnection $ConnectionString
    try {
        $connection.Open()
        $command = $connection.CreateCommand()
        $command.CommandText = $Query
        $command.CommandTimeout = 120

        $adapter = New-Object System.Data.SqlClient.SqlDataAdapter $command
        $table = New-Object System.Data.DataTable
        [void] $adapter.Fill($table)
        return $table
    }
    finally {
        $connection.Close()
    }
}

$connectionString = New-HisConnectionString -Server $SqlServer -Db $Database -User $SqlUser -Password $SqlPassword
$report = New-Object System.Text.StringBuilder

function Add-Section {
    param([string] $Title)
    [void] $report.AppendLine('')
    [void] $report.AppendLine('=' * 70)
    [void] $report.AppendLine($Title)
    [void] $report.AppendLine('=' * 70)
}

function Add-Table {
    param($Rows, [string[]] $Columns)
    foreach ($row in $Rows) {
        $parts = foreach ($column in $Columns) { "$column=$($row[$column])" }
        [void] $report.AppendLine(($parts -join '  |  '))
    }
}

Write-Host "Connecting to $SqlServer/$Database (read only)..." -ForegroundColor Cyan

Add-Section 'SERVER'
$version = Invoke-HisQuery $connectionString "SELECT @@VERSION AS v, DB_NAME() AS db, SUSER_SNAME() AS login_name"
[void] $report.AppendLine("database   : $($version.Rows[0]['db'])")
[void] $report.AppendLine("login      : $($version.Rows[0]['login_name'])")
[void] $report.AppendLine("version    : $(($version.Rows[0]['v'] -split "`n")[0].Trim())")

# The login should be db_datareader and nothing else. If this prints a writer
# role, fix it on the SQL side before going any further.
Add-Section 'PERMISSIONS OF THE CURRENT LOGIN'
$roles = Invoke-HisQuery $connectionString @"
SELECT r.name AS role_name
FROM sys.database_role_members m
JOIN sys.database_principals r ON r.principal_id = m.role_principal_id
JOIN sys.database_principals u ON u.principal_id = m.member_principal_id
WHERE u.name = USER_NAME()
"@
Add-Table $roles.Rows @('role_name')

Add-Section 'CANDIDATE TABLES (row counts)'
$candidates = Invoke-HisQuery $connectionString @"
SELECT TOP 60
       s.name AS [schema],
       t.name AS [table],
       SUM(p.rows) AS [rows]
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id = t.schema_id
JOIN sys.partitions p ON p.object_id = t.object_id AND p.index_id IN (0, 1)
WHERE s.name IN ('PID','Plan','Admission','Accounting','Health','Base')
GROUP BY s.name, t.name
HAVING SUM(p.rows) > 0
ORDER BY SUM(p.rows) DESC
"@
Add-Table $candidates.Rows @('schema', 'table', 'rows')

# These four are the ones the website actually imports. Their exact column
# names decide everything in config.json.
$targets = @(
    @{ Schema = 'PID';        Table = 'Patient';           Purpose = 'patients' },
    @{ Schema = 'Plan';       Table = 'Appointment';       Purpose = 'appointments' },
    @{ Schema = 'Admission';  Table = 'GeneralAdmission';  Purpose = 'visits' },
    @{ Schema = 'Accounting'; Table = 'Payment';            Purpose = 'finance (PaymentType 1=POS 2=Cash 3=Wallet)' },
    @{ Schema = 'Accounting'; Table = 'CashPayment';        Purpose = 'finance (links Payment to Admission)' },
    @{ Schema = 'Accounting'; Table = 'AdmissionDeposit';   Purpose = 'finance optional deposits' },
    @{ Schema = 'Admission';  Table = 'RequestedService';  Purpose = 'services (mali1-style)' },
    @{ Schema = 'Base';       Table = 'Service';           Purpose = 'service titles' }
)

foreach ($target in $targets) {
    Add-Section "COLUMNS: $($target.Schema).$($target.Table)  ->  $($target.Purpose)"

    $columns = Invoke-HisQuery $connectionString @"
SELECT c.name AS column_name,
       ty.name AS data_type,
       c.max_length,
       c.is_nullable
FROM sys.columns c
JOIN sys.types ty ON ty.user_type_id = c.user_type_id
WHERE c.object_id = OBJECT_ID('$($target.Schema).$($target.Table)')
ORDER BY c.column_id
"@

    if ($columns.Rows.Count -eq 0) {
        [void] $report.AppendLine('(table not found - search the candidate list above for the real name)')
        continue
    }

    Add-Table $columns.Rows @('column_name', 'data_type', 'max_length', 'is_nullable')

    # A change column is what makes incremental sync possible. Without one the
    # agent has to fall back to "everything newer than id N", which misses
    # edits to old rows.
    $changeColumns = $columns.Rows |
        Where-Object { $_['column_name'] -match 'Modif|Updat|Edit|Change|LastWrite|RowVersion|Timestamp' }

    [void] $report.AppendLine('')
    if ($changeColumns) {
        [void] $report.AppendLine('possible change-tracking columns: ' + (($changeColumns | ForEach-Object { $_['column_name'] }) -join ', '))
    } else {
        [void] $report.AppendLine('WARNING: no change-tracking column found. Incremental sync will only see new rows, not edits.')
    }

    # Jalali dates are often stored as nvarchar in Bina. The agent has to know
    # which, because it converts before sending.
    $dateLike = $columns.Rows |
        Where-Object { $_['column_name'] -match 'Date|Time' } |
        ForEach-Object { "$($_['column_name']) ($($_['data_type']))" }

    if ($dateLike) {
        [void] $report.AppendLine('date-ish columns: ' + ($dateLike -join ', '))
    }
}

Add-Section 'STATUS VALUES IN Plan.Appointment'
# These become the keys of config/his.php status_map on the website.
try {
    $statusColumn = Invoke-HisQuery $connectionString @"
SELECT TOP 1 c.name AS column_name
FROM sys.columns c
WHERE c.object_id = OBJECT_ID('Plan.Appointment')
  AND c.name LIKE '%Status%'
ORDER BY c.column_id
"@

    if ($statusColumn.Rows.Count -gt 0) {
        $name = $statusColumn.Rows[0]['column_name']
        $values = Invoke-HisQuery $connectionString @"
SELECT TOP 40 [$name] AS status_value, COUNT(*) AS [count]
FROM Plan.Appointment
GROUP BY [$name]
ORDER BY COUNT(*) DESC
"@
        [void] $report.AppendLine("column: $name")
        Add-Table $values.Rows @('status_value', 'count')
    } else {
        [void] $report.AppendLine('(no status column found)')
    }
}
catch {
    [void] $report.AppendLine("(could not read status values: $($_.Exception.Message))")
}

Add-Section 'NATIONAL CODE COVERAGE'
# Patients without a national code cannot be matched to website records, so
# the website gives them a synthetic key instead. This shows how many.
try {
    $coverage = Invoke-HisQuery $connectionString @"
SELECT TOP 1 c.name AS column_name
FROM sys.columns c
WHERE c.object_id = OBJECT_ID('PID.Patient')
  AND (c.name LIKE '%National%' OR c.name LIKE '%Melli%' OR c.name LIKE '%NID%')
ORDER BY c.column_id
"@

    if ($coverage.Rows.Count -gt 0) {
        $name = $coverage.Rows[0]['column_name']
        $stats = Invoke-HisQuery $connectionString @"
SELECT COUNT(*) AS total,
       SUM(CASE WHEN [$name] IS NULL OR LTRIM(RTRIM([$name])) = '' THEN 1 ELSE 0 END) AS missing
FROM PID.Patient
"@
        [void] $report.AppendLine("column : $name")
        [void] $report.AppendLine("total  : $($stats.Rows[0]['total'])")
        [void] $report.AppendLine("missing: $($stats.Rows[0]['missing'])")
    } else {
        [void] $report.AppendLine('(no national code column found)')
    }
}
catch {
    [void] $report.AppendLine("(could not read coverage: $($_.Exception.Message))")
}

$report.ToString() | Set-Content -Path $OutFile -Encoding UTF8

Write-Host ''
Write-Host "Done. Report written to $OutFile" -ForegroundColor Green
Write-Host 'Nothing was modified. Use the column names in the report to fill in config.json.' -ForegroundColor Green
