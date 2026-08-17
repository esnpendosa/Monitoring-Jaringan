@echo off
setlocal enabledelayedexpansion

title Rozitech FTTH Manager + GenieACS TR-069 Launcher

echo =========================================================================
echo   MEMULAI EKOSISTEM ROZITECH FTTH MANAGER + GENIEACS TR-069 (1-CLICK)
echo =========================================================================
echo.

REM 1. Set PATH untuk PHP & Node.js dari Laragon secara otomatis
set "PATH=%PATH%;C:\Program Files\nodejs;C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64;C:\laragon\bin\nodejs\node-v18"

REM 2. Start Portable Engine MongoDB (Port 27017)
set "GENIE_PATH=%~dp0genieacs"
if exist "%GENIE_PATH%\start_mongo.js" (
    echo [1/3] Memulai Engine MongoDB Portable Port 27017...
    start "MongoDB-Engine" /min node "%GENIE_PATH%\start_mongo.js"
    timeout /t 3 >nul
) else (
    echo [1/3] MongoDB Service: OK
)

REM 3. Start GenieACS TR-069 Services (Ports 7547, 7557, 7567, 3000)
if exist "%GENIE_PATH%\bin\genieacs-cwmp" (
    echo [2/3] Memulai Layanan GenieACS TR-069 Ports 7547, 7557, 7567, 3000...
    start "GenieACS-CWMP" /min node "%GENIE_PATH%\bin\genieacs-cwmp"
    start "GenieACS-NBI" /min node "%GENIE_PATH%\bin\genieacs-nbi"
    start "GenieACS-FS" /min node "%GENIE_PATH%\bin\genieacs-fs"
    start "GenieACS-UI" /min node "%GENIE_PATH%\bin\genieacs-ui"
) else (
    echo [2/3] WARNING: Engine GenieACS tidak ditemukan di %GENIE_PATH%
)

REM 4. Start Laravel Web Server & Open Browser
echo [3/3] Memulai Web Server Laravel Port 8000...
start http://127.0.0.1:8000/ftth

cd /d "%~dp0"
php artisan serve --host=127.0.0.1 --port=8000

pause
