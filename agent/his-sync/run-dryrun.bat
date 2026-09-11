@echo off
chcp 65001 >nul
cd /d "%~dp0"

if not exist "config.json" (
  echo [ERROR] config.json پیدا نشد.
  echo   copy config.example.json config.json
  pause
  exit /b 1
)

echo === DryRun (بدون ارسال واقعی به سایت) ===
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0sync.ps1" -DryRun
echo.
pause
