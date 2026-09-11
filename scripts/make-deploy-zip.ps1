# Build a clean deploy zip that avoids common ClamAV false positives
# (node_modules / vendor / preview HTML / .git / storage junk).

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$outDir = Join-Path $root 'storage\app'
$stamp = Get-Date -Format 'yyyyMMdd-HHmm'
$zipPath = Join-Path $outDir "patient-archive-deploy-$stamp.zip"

$excludeDirs = @(
    '.git',
    'node_modules',
    'vendor',
    '.idea',
    '.vscode',
    '.fleet',
    '.zed',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'storage\app\ui-preview',
    'storage\app\backups',
    'storage\pail',
    'public\hot'
)

$excludeFiles = @(
    '.env',
    '.env.backup',
    '.env.production',
    'Thumbs.db',
    '*.log'
)

Push-Location $root
try {
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

    $items = Get-ChildItem -Force | Where-Object {
        $name = $_.Name
        if ($name -in @('.git', 'node_modules', 'vendor')) { return $false }
        if ($name -like 'patient-archive-deploy-*.zip') { return $false }
        return $true
    }

    $temp = Join-Path $env:TEMP ("pac-deploy-" + [guid]::NewGuid().ToString('n'))
    New-Item -ItemType Directory -Path $temp | Out-Null

    foreach ($item in $items) {
        $dest = Join-Path $temp $item.Name
        Copy-Item -LiteralPath $item.FullName -Destination $dest -Recurse -Force
    }

    foreach ($rel in $excludeDirs) {
        $p = Join-Path $temp $rel
        if (Test-Path $p) { Remove-Item $p -Recurse -Force -ErrorAction SilentlyContinue }
    }

    Get-ChildItem -Path $temp -Recurse -File -Force -ErrorAction SilentlyContinue |
        Where-Object {
            $_.Name -eq '.env' -or
            $_.Name -like '*.log' -or
            $_.Extension -eq '.html' -and $_.FullName -match 'ui-preview|preview'
        } |
        Remove-Item -Force -ErrorAction SilentlyContinue

    # Keep empty storage placeholders
    @(
        'storage\app\public',
        'storage\framework\cache',
        'storage\framework\sessions',
        'storage\framework\views',
        'storage\logs'
    ) | ForEach-Object {
        $p = Join-Path $temp $_
        New-Item -ItemType Directory -Force -Path $p | Out-Null
        Set-Content -Path (Join-Path $p '.gitignore') -Value "*`n!.gitignore`n"
    }

    # Never ship a previous install lock — it makes cpanel-install.php return 403
    # on a fresh host before the form is even shown.
    $installLock = Join-Path $temp 'storage\app\.cpanel-installed'
    if (Test-Path $installLock) { Remove-Item $installLock -Force }

    Compress-Archive -Path (Join-Path $temp '*') -DestinationPath $zipPath -CompressionLevel Optimal
    Write-Host "Created: $zipPath"
    Write-Host "Size MB: $([math]::Round((Get-Item $zipPath).Length / 1MB, 2))"
    Write-Host "On server run: composer install --no-dev && npm ci && npm run build && php artisan migrate --force"
}
finally {
    Pop-Location
    if ($temp -and (Test-Path $temp)) { Remove-Item $temp -Recurse -Force -ErrorAction SilentlyContinue }
}
