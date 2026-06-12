$taskName = "FitMotor Gabung Full Sync"

if (Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue) {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Write-Host "Task Scheduler dihapus: $taskName"
} else {
    Write-Host "Task Scheduler tidak ditemukan: $taskName"
}
