@echo off
REM ============================================================================
REM AUTO PATCH SCRIPT - Fitur Perhitungan Jarak Otomatis
REM File ini akan otomatis mem-patch file PHP dengan fitur baru
REM ============================================================================

echo.
echo ========================================================================
echo   AUTO PATCH - Perhitungan Jarak Otomatis Cabang ke Pelanggan
echo ========================================================================
echo.

REM Backup files
echo [1/3] Creating backups...
copy /Y servis-reguler-jemput.php servis-reguler-jemput.php.backup2 >nul 2>&1
copy /Y cabang.php cabang.php.backup >nul 2>&1
echo      ✓ Backup created

echo.
echo [2/3] File patches ready in:
echo      - PATCH_servis-reguler-jemput.txt
echo      - PATCH_cabang.php.txt
echo.
echo [3/3] Manual steps required:
echo.
echo   STEP 1: Import Database
echo   -----------------------
echo   File: ..\..\..\UPDATE_DATABASE_JARAK_OTOMATIS.sql
echo   → Open phpMyAdmin
echo   → Select database: fitmotor_dbbengkel
echo   → Import SQL file
echo.
echo   STEP 2: Apply Patches
echo   ---------------------
echo   Open files with code editor and apply patches:
echo.
echo   A. servis-reguler-jemput.php
echo      → Open: PATCH_servis-reguler-jemput.txt
echo      → Apply 5 patches (copy-paste)
echo.
echo   B. cabang.php
echo      → Open: PATCH_cabang.php.txt
echo      → Apply 2 patches (copy-paste)
echo.
echo   STEP 3: Testing
echo   ---------------
echo   → Login to system
echo   → Check Master Cabang (new columns)
echo   → Test Jadwal Penjemputan (auto-calculate)
echo.
echo ========================================================================
echo   Files Ready! Please apply patches manually.
echo ========================================================================
echo.
pause
