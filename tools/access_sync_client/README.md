# FITMOTOR GABUNG Auto Sync Client

File utama:

- `sync_fitmotor_gabung_full.py`
- `sync_settings.env`
- `run_fitmotor_gabung_full_sync.bat`
- `run_fitmotor_gabung_full_sync_test.bat`
- `install_fitmotor_gabung_full_sync_task.ps1`

Cara pakai manual:

```bat
tools\access_sync_client\run_fitmotor_gabung_full_sync_test.bat
tools\access_sync_client\run_fitmotor_gabung_full_sync.bat
```

Install task scheduler:

```powershell
powershell -ExecutionPolicy Bypass -File tools\access_sync_client\install_fitmotor_gabung_full_sync_task.ps1
```

Uninstall task scheduler:

```powershell
powershell -ExecutionPolicy Bypass -File tools\access_sync_client\uninstall_fitmotor_gabung_full_sync_task.ps1
```

Log output:

- `tools/access_sync_client/logs/`
- `tools/access_sync_client/runtime/sync_state.sqlite3`
