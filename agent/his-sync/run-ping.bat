@echo off
chcp 65001 >nul
cd /d "%~dp0"

if not exist "config.json" (
  echo [ERROR] config.json پیدا نشد.
  echo اول این دستور را بزنید:
  echo   copy config.example.json config.json
  echo بعد config.json را با Notepad پر کنید.
  pause
  exit /b 1
)

echo === PingOnly ===
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0sync.ps1" -PingOnly
echo.
pause
