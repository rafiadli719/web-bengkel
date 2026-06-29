-- ============================================================
-- Migrasi: Modul Pengadaan Antar Cabang (Phase 1)
-- Tanggal: 29 Juni 2026
-- Jalankan sekali di database fitmotor_dbbengkel
-- ============================================================

-- Header permintaan barang antar cabang
CREATE TABLE IF NOT EXISTS tblorder_antarcab_header (
    no_order          VARCHAR(20)   NOT NULL PRIMARY KEY,
    kd_cabang_asal    VARCHAR(50)   NOT NULL COMMENT 'Cabang yang request barang',
    kd_cabang_tujuan  VARCHAR(50)   NOT NULL COMMENT 'Cabang tujuan (pusat)',
    tanggal_request   DATE          NOT NULL,
    tanggal_proses    DATE          NULL     COMMENT 'Diisi saat pusat proses',
    tanggal_kirim     DATE          NULL     COMMENT 'Diisi saat pusat kirim',
    tanggal_terima    DATE          NULL     COMMENT 'Diisi saat cabang konfirmasi terima',
    status            ENUM('draft','terkirim','diproses','dikirim','selesai','batal')
                                    NOT NULL DEFAULT 'draft',
    no_penjualan      VARCHAR(20)   NULL     COMMENT 'Ref transaksi penjualan pusat',
    no_pembelian      VARCHAR(20)   NULL     COMMENT 'Ref transaksi pembelian cabang',
    total_item        INT           NOT NULL DEFAULT 0,
    total_qty         INT           NOT NULL DEFAULT 0,
    total_nilai       DECIMAL(15,2) NOT NULL DEFAULT 0,
    catatan           TEXT          NULL,
    user_request      VARCHAR(100)  NOT NULL,
    user_proses       VARCHAR(100)  NULL,
    created_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_kd_asal   (kd_cabang_asal),
    KEY idx_status    (status),
    KEY idx_tgl       (tanggal_request)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Detail item per permintaan
CREATE TABLE IF NOT EXISTS tblorder_antarcab_detail (
    id           INT           NOT NULL AUTO_INCREMENT PRIMARY KEY,
    no_order     VARCHAR(20)   NOT NULL,
    no_baris     INT           NOT NULL DEFAULT 1,
    no_item      VARCHAR(50)   NOT NULL COMMENT 'Kode barang (FK tblitem.noitem)',
    qty_request  INT           NOT NULL DEFAULT 0 COMMENT 'Qty diminta cabang',
    qty_kirim    INT           NOT NULL DEFAULT 0 COMMENT 'Qty dikirim pusat',
    qty_terima   INT           NOT NULL DEFAULT 0 COMMENT 'Qty diterima cabang',
    harga_pokok  DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'HPP dari tblitem.hargapokok',
    subtotal     DECIMAL(15,2) NOT NULL DEFAULT 0,
    KEY idx_no_order (no_order),
    KEY idx_no_item  (no_item)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
