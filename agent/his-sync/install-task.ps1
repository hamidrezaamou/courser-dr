<#
.SYNOPSIS
    Installs a Windows Scheduled Task that runs sync.ps1 every 5 minutes.

.DESCRIPTION
    Creates (or replaces) the task "ClinicHisSync". The task runs as the
    current user by default; pass -RunAs SYSTEM only if that account can
    reach SQL Server with Integrated Security and the website over HTTPS.

.EXAMPLE
    .\install-task.ps1
    .\install-task.ps1 -EveryMinutes 10
#>

[CmdletBinding()]
param(
    [int] $EveryMinutes = 5,
    [string] $TaskName = 'ClinicHisSync',
    [switch] $RunAsSystem,
    [switch] $Uninstall
)

$ErrorActionPreference = 'Stop'
$script = Join-Path $PSScriptRoot 'sync.ps1'
$config = Join-Path $PSScriptRoot 'config.json'

if ($Uninstall) {
    Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false -ErrorAction SilentlyContinue
    Write-Host "Removed task $TaskName"
    exit 0
}

if (-not (Test-Path $script)) { throw "sync.ps1 not found at $script" }
if (-not (Test-Path $config)) {
    throw "config.json not found. Copy config.example.json to config.json and fill it in first."
}

$action = New-ScheduledTaskAction `
    -Execute 'powershell.exe' `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$script`"" `
    -WorkingDirectory $PSScriptRoot

$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date `
    -RepetitionInterval (New-TimeSpan -Minutes $EveryMinutes) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 25)

if ($RunAsSystem) {
    $principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
} else {
    $principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited
}

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Force | Out-Null

Write-Host "Installed task '$TaskName' every $EveryMinutes minutes." -ForegroundColor Green
Write-Host "Test once with:  powershell -File `"$script`" -PingOnly"
Write-Host "Then a dry run:  powershell -File `"$script`" -DryRun"
Write-Host "Logs land in:    $(Join-Path $PSScriptRoot 'logs')"
