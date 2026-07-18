-- FASE 4: Approval Bertingkat PO (jawaban Owner 16 Jul 2026, FSD_PENGADAAN_INVENTORY.md §7.1)
-- Bracket rupiah -> posisi minimal yang boleh approve. Fully editable via master page.

CREATE TABLE IF NOT EXISTS tb_master_approval_pembelian (
  id INT PRIMARY KEY AUTO_INCREMENT,
  level_approval INT NOT NULL,
  nama_level VARCHAR(50) NOT NULL,
  batas_bawah DECIMAL(15,2) NOT NULL DEFAULT 0,
  batas_atas DECIMAL(15,2) NULL COMMENT 'NULL = tidak terbatas (unlimited)',
  kode_posisi VARCHAR(20) NOT NULL COMMENT 'FK longgar ke tb_master_posisi.kode_posisi - posisi minimal yang boleh approve',
  urutan INT NOT NULL DEFAULT 1,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed sesuai contoh Owner 16 Jul 2026: Rp300rb-1jt (posisi setara Manager), >1jt (posisi setara Administrator).
-- Tidak ada posisi "Supervisor" di tb_master_posisi saat ini - dipetakan ke Manager (MNG) sbg pendekatan
-- terdekat, dan Administrator (ADM) untuk tier tak terbatas. Editable kapan saja lewat master page.
INSERT INTO tb_master_approval_pembelian (level_approval, nama_level, batas_bawah, batas_atas, kode_posisi, urutan, aktif)
VALUES
  (1, 'Manager (300rb - 1jt)', 300000, 1000000, 'MNG', 1, 1),
  (2, 'Administrator (> 1jt)', 1000000.01, NULL, 'ADM', 2, 1);
