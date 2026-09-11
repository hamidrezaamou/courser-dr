# ساخت بسته آماده آپلود به cPanel در پوشه هم‌تراز پروژه
# اجرا: powershell -ExecutionPolicy Bypass -File scripts\build-cpanel.ps1

$ErrorActionPreference = 'Stop'
$ProjectRoot = Split-Path -Parent $PSScriptRoot
$OutRoot = Join-Path (Split-Path -Parent $ProjectRoot) 'patient-archive-cpanel'
$Stamp = Get-Date -Format 'yyyyMMdd-HHmm'

Write-Host "==> Project: $ProjectRoot"
Write-Host "==> Output:  $OutRoot"

# اگر خود پروژه همان پوشه خروجی باشد، پاک‌سازی زیر باعث حذف کل پروژه می‌شود
if ([IO.Path]::GetFullPath($OutRoot) -eq [IO.Path]::GetFullPath($ProjectRoot)) {
    throw "Output folder is the project folder ($ProjectRoot). Run this script from the source project, not from the package folder."
}

if (Test-Path $OutRoot) {
    Write-Host 'Removing previous package folder...'
    Remove-Item -LiteralPath $OutRoot -Recurse -Force
}
New-Item -ItemType Directory -Path $OutRoot | Out-Null

# Assets باید از قبل build شده باشند
$buildManifest = Join-Path $ProjectRoot 'public\build\manifest.json'
if (-not (Test-Path $buildManifest)) {
    Write-Host 'Building frontend assets (npm run build)...'
    Push-Location $ProjectRoot
    npm run build
    Pop-Location
}

$excludeFiles = @(
    '.env',
    '.phpunit.result.cache',
    'Homestead.json',
    'Homestead.yaml',
    'auth.json'
)

Write-Host 'Copying files (excluding .git, node_modules, tests, vendor)...'
# vendor را جدا با composer --no-dev می‌سازیم تا پروژه لوکال دست نخورد
Get-ChildItem -LiteralPath $ProjectRoot -Force | ForEach-Object {
    $name = $_.Name
    if ($name -in @('.git', '.github', 'node_modules', 'tests', 'jadid', '.idea', '.vscode', 'vendor')) {
        return
    }
    $dest = Join-Path $OutRoot $name
    if ($_.PSIsContainer) {
        robocopy $_.FullName $dest /E /NFL /NDL /NJH /NJS /nc /ns /np `
            /XD node_modules .git tests `
            /XF .env *.log | Out-Null
        if ($LASTEXITCODE -ge 8) {
            throw "robocopy failed for $($_.FullName) code=$LASTEXITCODE"
        }
    } else {
        if ($name -notin $excludeFiles) {
            Copy-Item -LiteralPath $_.FullName -Destination $dest -Force
        }
    }
}

Write-Host 'Installing production Composer dependencies into package...'
Push-Location $OutRoot
composer install --no-dev --optimize-autoloader --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'composer install --no-dev failed' }
Pop-Location

# پاکسازی لاگ‌ها و کش‌های کپی‌شده
$cleanPaths = @(
    'storage\logs\*',
    'storage\framework\cache\data\*',
    'storage\framework\sessions\*',
    'storage\framework\views\*',
    'bootstrap\cache\*.php'
)
foreach ($p in $cleanPaths) {
    $full = Join-Path $OutRoot $p
    Get-ChildItem -Path $full -Force -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force -ErrorAction SilentlyContinue
}

# پوشه‌های خالی ضروری
$keepDirs = @(
    'storage\app\public',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs',
    'bootstrap\cache'
)
foreach ($d in $keepDirs) {
    $full = Join-Path $OutRoot $d
    New-Item -ItemType Directory -Force -Path $full | Out-Null
    Set-Content -Path (Join-Path $full '.gitignore') -Value "*`n!.gitignore`n" -Encoding utf8
}

# قفل نصب قبلی نباشد
$lock = Join-Path $OutRoot 'storage\app\.cpanel-installed'
if (Test-Path $lock) { Remove-Item $lock -Force }

# نمونه env برای مرجع (نصب واقعی با وب‌اینستالر)
Copy-Item (Join-Path $ProjectRoot '.env.example') (Join-Path $OutRoot '.env.example') -Force

# راهنما
$readme = @"
# بسته cPanel — بایگانی بیماران

## آپلود
1. محتویات این پوشه را (یا ZIP آن را) در `public_html` یا ساب‌دامین آپلود کنید.
2. ترجیحاً Document Root را روی پوشه ``public`` تنظیم کنید.
3. اگر نمی‌توانید Document Root را عوض کنید، فایل ``.htaccess`` ریشه پروژه را نگه دارید تا به ``public`` هدایت کند.

## دیتابیس در cPanel
1. MySQL Databases → دیتابیس بسازید
2. کاربر بسازید و به دیتابیس وصل کنید (All Privileges)
3. مشخصات را یادداشت کنید

## نصب خودکار (بدون SSH)
1. مرورگر: ``https://دامنه-شما/cpanel-install.php``
2. فرم را پر کنید (دیتابیس، آدرس سایت، رمز ادمین، اختیاری SMS.ir)
3. دکمه نصب — خودش ``.env`` می‌سازد، migrate می‌زند، کاربر دکتر می‌سازد
4. **فایل ``public/cpanel-install.php`` را حذف کنید**

## ورود
- با **کد ملی** و رمزی که در نصب‌کننده وارد کردید

## مجوزها (اگر خطا داد)
روی پوشه‌های زیر مجوز نوشتن (755 یا 775):
- ``storage``
- ``bootstrap/cache``

## PHP
حداقل PHP **8.2** با افزونه‌های: ``pdo_mysql``, ``mbstring``, ``openssl``, ``tokenizer``, ``xml``, ``ctype``, ``json``, ``fileinfo``, ``bcmath``

ساخته‌شده: $Stamp
"@
Set-Content -Path (Join-Path $OutRoot 'CPANEL-README.txt') -Value $readme -Encoding UTF8

Write-Host ''
Write-Host "Done. Package ready at:"
Write-Host "  $OutRoot"
Write-Host 'Open public/cpanel-install.php after upload.'
Write-Host ''

# تلاش برای ZIP (اختیاری)
$zipPath = Join-Path (Split-Path -Parent $OutRoot) "patient-archive-cpanel-$Stamp.zip"
try {
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
    Write-Host "Creating ZIP: $zipPath"
    Compress-Archive -Path (Join-Path $OutRoot '*') -DestinationPath $zipPath -CompressionLevel Optimal
    Write-Host "ZIP ready: $zipPath"
} catch {
    Write-Host "ZIP skipped: $($_.Exception.Message)"
}
