-- Wiring komisi mekanik: servis_komisi butuh kd_cabang karena no_service
-- tidak unik lintas cabang (temuan kritis 2026-07-19).
ALTER TABLE servis_komisi
  ADD COLUMN kd_cabang VARCHAR(10) NOT NULL DEFAULT '' AFTER no_service,
  ADD INDEX idx_komisi_cabang_service (kd_cabang, no_service);
