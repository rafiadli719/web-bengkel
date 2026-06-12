@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "PYTHON_EXE=py -3.11"
set "ENV_FILE=%SCRIPT_DIR%sync_settings.env"
set "LOG_DIR=%SCRIPT_DIR%logs"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

echo [%date% %time%] Menjalankan full sync FITMOTOR GABUNG...
%PYTHON_EXE% "%SCRIPT_DIR%sync_fitmotor_gabung_full.py" --env "%ENV_FILE%"
set "EXIT_CODE=%ERRORLEVEL%"

echo [%date% %time%] Sync selesai dengan exit code %EXIT_CODE%.
exit /b %EXIT_CODE%
