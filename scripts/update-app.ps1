# Update / deploy helpers for clinic installs
# Usage from project root:
#   powershell -ExecutionPolicy Bypass -File scripts\update-app.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$Php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
if (-not (Test-Path $Php)) {
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { $Php = $cmd.Source }
}
if (-not $Php -or -not (Test-Path $Php)) { throw "PHP not found" }

Write-Host "==> Maintenance on"
& $Php artisan down --retry=60 | Out-Null

Write-Host "==> Composer install"
if (Test-Path "composer.phar") {
    & $Php composer.phar install --no-dev --optimize-autoloader
} else {
    composer install --no-dev --optimize-autoloader
}

Write-Host "==> Migrate"
& $Php artisan migrate --force

Write-Host "==> Build assets"
if (Test-Path "package.json") {
    npm ci
    npm run build
}

Write-Host "==> Optimize"
& $Php artisan optimize:clear
& $Php artisan config:cache
& $Php artisan route:cache
& $Php artisan view:cache

Write-Host "==> Backup after update"
& $Php artisan db:backup --keep=14

Write-Host "==> Maintenance off"
& $Php artisan up

Write-Host "Done. Hard-refresh browsers (Ctrl+F5)."
