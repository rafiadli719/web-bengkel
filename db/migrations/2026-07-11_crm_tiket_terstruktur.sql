-- ============================================================
-- MIGRATION: CRM Tiket Terstruktur
-- Tanggal: 2026-07-11
-- Deskripsi: 
--   1. master_jenis_masalah — katalog jenis masalah terstruktur
--   2. ALTER tbl_issue — tambah id_jenis_masalah FK + payload_json
--   3. servis_komisi — snapshot komisi permanen (dari FSD_SERVIS.md §5.2)
--      Dibutuhkan oleh pilot case "Revisi Komisi Servis Pasca-Bayar"
-- Referensi: FSD_CRM.md (restrukturisasi FR-02/FR-03), FSD_SERVIS.md §8
-- ============================================================

-- ------------------------------------------------------------
-- 1. master_jenis_masalah
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS master_jenis_masalah (
    id_jenis        INT AUTO_INCREMENT PRIMARY KEY,
    kategori        ENUM('data_pelanggan','data_kendaraan','komisi','stok','sistem','lainnya') NOT NULL,
    nama_masalah    VARCHAR(150) NOT NULL,
    skema_field     JSON NOT NULL COMMENT 'Daftar field dinamis: [{nama, tipe, wajib, sumber_lookup}]',
    role_approval   ENUM('self','supervisor','owner') NOT NULL DEFAULT 'supervisor'
                    COMMENT 'self=CS bisa self-approve, supervisor=user_akses=1, owner=user_akses=1',
    target_eksekusi VARCHAR(100) DEFAULT NULL
                    COMMENT 'Identifier fungsi PHP yang dijalankan saat approve (NULL=manual)',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data: jenis masalah awal
INSERT INTO master_jenis_masalah (kategori, nama_masalah, skema_field, role_approval, target_eksekusi) VALUES
-- Komisi
('komisi', 'Revisi Komisi Servis Pasca-Bayar', 
 '[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice_bayar"},{"nama":"peran","label":"Peran","tipe":"enum","wajib":true,"opsi":["mekanik1","mekanik2","mekanik3","mekanik4","kepala_mekanik1","kepala_mekanik2","admin1","admin2"]},{"nama":"persen_lama","label":"Persentase Lama (%)","tipe":"readonly","wajib":true,"sumber_lookup":"servis_komisi_persen"},{"nama":"persen_baru","label":"Persentase Baru (%)","tipe":"number","wajib":true},{"nama":"alasan","label":"Alasan Revisi","tipe":"textarea","wajib":true}]',
 'supervisor', 'revisi_komisi_pasca_bayar'),

('komisi', 'Koreksi Persentase Kerja Mekanik',
 '[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice_bayar"},{"nama":"mekanik_ke","label":"Mekanik Ke-","tipe":"enum","wajib":true,"opsi":["1","2","3","4"]},{"nama":"persen_lama","label":"Persen Kerja Lama (%)","tipe":"readonly","wajib":true,"sumber_lookup":"persen_mekanik"},{"nama":"persen_baru","label":"Persen Kerja Baru (%)","tipe":"number","wajib":true},{"nama":"alasan","label":"Alasan","tipe":"textarea","wajib":true}]',
 'supervisor', 'revisi_komisi_pasca_bayar'),

('komisi', 'Koreksi Kepala Mekanik Salah Assign',
 '[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice_bayar"},{"nama":"kepala_mekanik_ke","label":"Kepala Mekanik Ke-","tipe":"enum","wajib":true,"opsi":["1","2"]},{"nama":"nama_lama","label":"Kepala Mekanik Lama","tipe":"readonly","wajib":true,"sumber_lookup":"kepala_mekanik_nama"},{"nama":"id_kepala_baru","label":"Kepala Mekanik Baru","tipe":"autocomplete","wajib":true,"sumber_lookup":"daftar_mekanik"},{"nama":"alasan","label":"Alasan","tipe":"textarea","wajib":true}]',
 'supervisor', NULL),

-- Data pelanggan
('data_pelanggan', 'Salah Pilih Customer saat Transaksi',
 '[{"nama":"no_service","label":"No. Service/Transaksi","tipe":"text","wajib":true},{"nama":"customer_salah","label":"Customer yang Salah Dipilih","tipe":"autocomplete","wajib":true,"sumber_lookup":"pelanggan"},{"nama":"customer_benar","label":"Customer yang Benar","tipe":"autocomplete","wajib":true,"sumber_lookup":"pelanggan"},{"nama":"alasan","label":"Alasan/Bukti","tipe":"textarea","wajib":true}]',
 'supervisor', NULL),

-- Data kendaraan
('data_kendaraan', 'Salah Input Nopol saat Servis',
 '[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice"},{"nama":"nopol_salah","label":"Nopol yang Salah","tipe":"readonly","wajib":true,"sumber_lookup":"nopol_servis"},{"nama":"nopol_benar","label":"Nopol yang Benar","tipe":"text","wajib":true},{"nama":"alasan","label":"Alasan","tipe":"textarea","wajib":true}]',
 'supervisor', 'koreksi_nopol_servis'),

-- Lainnya (fallback free-text, tapi tetap harus pilih jenis ini dulu)
('lainnya', 'Masalah Lain (Tidak Terkategorikan)',
 '[{"nama":"judul","label":"Judul Masalah","tipe":"text","wajib":true},{"nama":"detail","label":"Detail Masalah","tipe":"textarea","wajib":true}]',
 'supervisor', NULL);

-- ------------------------------------------------------------
-- 2. ALTER tbl_issue — tambah kolom baru
-- ------------------------------------------------------------
-- Tambah id_jenis_masalah FK
ALTER TABLE tbl_issue
    ADD COLUMN id_jenis_masalah INT DEFAULT NULL AFTER kategori,
    ADD COLUMN payload_json JSON DEFAULT NULL AFTER deskripsi;

-- Ubah kategori enum supaya konsisten dengan master_jenis_masalah
-- (live punya 'komisi_mekanik','nominal_transaksi' — normalisasi ke enum FSD)
ALTER TABLE tbl_issue
    MODIFY COLUMN kategori VARCHAR(30) NOT NULL DEFAULT 'lainnya';

-- FK constraint
ALTER TABLE tbl_issue
    ADD CONSTRAINT fk_issue_jenis_masalah
    FOREIGN KEY (id_jenis_masalah) REFERENCES master_jenis_masalah(id_jenis)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Pastikan kolom-kolom FSD yang mungkin belum ada
ALTER TABLE tbl_issue
    ADD COLUMN IF NOT EXISTS pic INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS deadline DATE DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- deskripsi jadi opsional (sudah TEXT NULL by default di MySQL, tapi pastikan)
ALTER TABLE tbl_issue
    MODIFY COLUMN deskripsi TEXT DEFAULT NULL;

-- Index untuk pencarian cepat
ALTER TABLE tbl_issue
    ADD INDEX idx_issue_jenis (id_jenis_masalah),
    ADD INDEX idx_issue_status (status);

-- ------------------------------------------------------------
-- 3. servis_komisi — dari FSD_SERVIS.md §5.2 (schema persis)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servis_komisi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    no_service      VARCHAR(50) NOT NULL,
    peran           ENUM('mekanik1','mekanik2','mekanik3','mekanik4',
                         'kepala_mekanik1','kepala_mekanik2',
                         'admin1','admin2') NOT NULL,
    nominal_jasa    DOUBLE NOT NULL DEFAULT 0 COMMENT 'Komisi dari jasa',
    nominal_barang  DOUBLE NOT NULL DEFAULT 0 COMMENT 'Komisi dari laba barang',
    persen_terpakai INT NOT NULL DEFAULT 0 COMMENT 'Snapshot persentase saat dihitung',
    dihitung_saat   ENUM('selesai','bayar','revisi_tiket') NOT NULL DEFAULT 'bayar',
    id_issue_ref    VARCHAR(20) DEFAULT NULL COMMENT 'Referensi tiket asal untuk revisi pasca-bayar',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_komisi_service (no_service),
    INDEX idx_komisi_issue (id_issue_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. Penyempurnaan UX: form cari & pilih, bukan ketik bebas
-- ------------------------------------------------------------
UPDATE master_jenis_masalah SET skema_field='[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice_bayar"},{"nama":"peran","label":"Peran","tipe":"service_role_select","wajib":true,"depends_on":"no_service"},{"nama":"persen_lama","label":"Persentase Lama (%)","tipe":"readonly_auto","wajib":true,"depends_on":"peran"},{"nama":"persen_baru","label":"Persentase Baru (%)","tipe":"preset_percent","wajib":true,"depends_on":"no_service"},{"nama":"alasan","label":"Alasan Revisi","tipe":"preset_reason","wajib":true,"preset":"komisi"}]'
WHERE nama_masalah='Revisi Komisi Servis Pasca-Bayar';

UPDATE master_jenis_masalah SET skema_field='[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice_bayar"},{"nama":"mekanik_ke","label":"Mekanik","tipe":"service_mechanic_select","wajib":true,"depends_on":"no_service"},{"nama":"persen_lama","label":"Persen Kerja Lama (%)","tipe":"readonly_auto","wajib":true,"depends_on":"mekanik_ke"},{"nama":"persen_baru","label":"Persen Kerja Baru (%)","tipe":"preset_percent","wajib":true,"depends_on":"no_service"},{"nama":"alasan","label":"Alasan","tipe":"preset_reason","wajib":true,"preset":"komisi"}]', target_eksekusi='revisi_komisi_pasca_bayar'
WHERE nama_masalah='Koreksi Persentase Kerja Mekanik';

UPDATE master_jenis_masalah SET skema_field='[{"nama":"no_service","label":"No. Service","tipe":"autocomplete","wajib":true,"sumber_lookup":"tblservice"},{"nama":"nopol_salah","label":"Nopol yang Salah","tipe":"readonly_auto","wajib":true,"depends_on":"no_service"},{"nama":"nopol_benar","label":"Nopol yang Benar","tipe":"autocomplete","wajib":true,"sumber_lookup":"kendaraan"},{"nama":"alasan","label":"Alasan","tipe":"preset_reason","wajib":true,"preset":"nopol"}]', target_eksekusi='koreksi_nopol_servis'
WHERE nama_masalah='Salah Input Nopol saat Servis';
