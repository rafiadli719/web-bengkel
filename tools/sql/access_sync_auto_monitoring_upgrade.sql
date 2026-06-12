ALTER TABLE sync_access_runs ADD COLUMN dataset_key VARCHAR(50) NULL AFTER source_name;
ALTER TABLE sync_access_runs ADD COLUMN trigger_source VARCHAR(50) NULL AFTER dataset_key;
ALTER TABLE sync_access_runs ADD COLUMN source_cabang VARCHAR(30) NULL AFTER trigger_source;
ALTER TABLE sync_access_runs ADD COLUMN machine_name VARCHAR(120) NULL AFTER source_cabang;
ALTER TABLE sync_access_runs ADD COLUMN batch_key VARCHAR(100) NULL AFTER machine_name;
ALTER TABLE sync_access_runs ADD COLUMN request_ip VARCHAR(45) NULL AFTER batch_key;
ALTER TABLE sync_access_runs ADD COLUMN merge_status VARCHAR(20) NULL AFTER failed_rows;
ALTER TABLE sync_access_runs ADD COLUMN merge_processed INT NOT NULL DEFAULT 0 AFTER merge_status;
ALTER TABLE sync_access_runs ADD COLUMN merge_inserted INT NOT NULL DEFAULT 0 AFTER merge_processed;
ALTER TABLE sync_access_runs ADD COLUMN merge_updated INT NOT NULL DEFAULT 0 AFTER merge_inserted;
ALTER TABLE sync_access_runs ADD COLUMN merge_upserted INT NOT NULL DEFAULT 0 AFTER merge_updated;
ALTER TABLE sync_access_runs ADD COLUMN last_heartbeat_at DATETIME NULL AFTER finished_at;

ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_dataset (dataset_key);
ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_source_cabang (source_cabang);
ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_started_at (started_at);
ALTER TABLE sync_access_runs ADD KEY idx_sync_access_runs_sync_mode (sync_mode);
