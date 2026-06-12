@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "PYTHON_EXE=py -3.11"
set "ENV_FILE=%SCRIPT_DIR%sync_settings.env"

echo [%date% %time%] Menjalankan TEST sync FITMOTOR GABUNG sample 5 row per dataset...
%PYTHON_EXE% "%SCRIPT_DIR%sync_fitmotor_gabung_full.py" --env "%ENV_FILE%" --sample-limit 5
set "EXIT_CODE=%ERRORLEVEL%"

echo [%date% %time%] Test sync selesai dengan exit code %EXIT_CODE%.
exit /b %EXIT_CODE%
