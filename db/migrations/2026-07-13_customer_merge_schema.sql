-- ============================================================
-- MIGRATION: Customer Merge & Alias Schema
-- Tanggal: 2026-07-13
-- Deskripsi:
--   1. customer_merge_log — log histori pengajuan & eksekusi merge
--   2. customer_alias — tabel mapping kode lama ke kode baru untuk redirect/alias
-- ============================================================

CREATE TABLE IF NOT EXISTS customer_merge_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nopelanggan_source VARCHAR(50) NOT NULL COMMENT 'Pelanggan lama yang akan dilebur/dihapus',
    nopelanggan_target VARCHAR(50) NOT NULL COMMENT 'Pelanggan baru yang dipertahankan',
    alasan TEXT NOT NULL,
    dibuat_oleh INT NOT NULL COMMENT 'FK tbuser pembuat pengajuan',
    status ENUM('diajukan', 'disetujui', 'dieksekusi', 'ditolak') NOT NULL DEFAULT 'diajukan',
    disetujui_oleh INT DEFAULT NULL COMMENT 'FK tbuser supervisor/owner penyetuju',
    snapshot_before_json JSON DEFAULT NULL COMMENT 'Snapshot backup data pelanggan sebelum lebur',
    id_issue VARCHAR(20) DEFAULT NULL COMMENT 'Ref tiket komplain jika diajukan via CS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    executed_at DATETIME DEFAULT NULL,
    INDEX idx_merge_source (nopelanggan_source),
    INDEX idx_merge_target (nopelanggan_target),
    INDEX idx_merge_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS customer_alias (
    nopelanggan_lama VARCHAR(50) NOT NULL PRIMARY KEY COMMENT 'Kode pelanggan lama yang didelete/alias',
    nopelanggan_baru VARCHAR(50) NOT NULL COMMENT 'Kode pelanggan target aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_alias_baru (nopelanggan_baru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
