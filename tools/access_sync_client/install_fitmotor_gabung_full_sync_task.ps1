$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$batchPath = Join-Path $scriptDir "run_fitmotor_gabung_full_sync.bat"
$taskName = "FitMotor Gabung Full Sync"

if (-not (Test-Path $batchPath)) {
    throw "Batch file tidak ditemukan: $batchPath"
}

$taskRun = 'cmd /c ""' + $batchPath + '""'
cmd /c "schtasks /Query /TN ""$taskName"" >nul 2>nul"
if ($LASTEXITCODE -eq 0) {
    schtasks /Delete /TN $taskName /F | Out-Null
}
schtasks /Create /TN $taskName /TR $taskRun /SC MINUTE /MO 10 /F | Out-Null

Write-Host "Task Scheduler berhasil dibuat: $taskName"
Write-Host "Batch file: $batchPath"
Write-Host "Interval: tiap 10 menit"
