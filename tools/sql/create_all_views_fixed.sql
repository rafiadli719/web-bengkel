-- ============================================================
-- FILE: create_all_views_fixed.sql
-- DESKRIPSI: Script untuk membuat VIEW yang hilang/rusak (VERSI FIXED)
-- TANGGAL: 2025-11-28
-- CATATAN: master_tarif_jemput, tblmekanik, dan tbl_master_kepala_mekanik
--          adalah TABLE bukan VIEW, jadi di-skip
-- ============================================================

-- CATATAN PENTING:
-- Objek berikut adalah TABLE, bukan VIEW, jadi tidak perlu dibuat ulang:
-- - master_tarif_jemput (TABLE)
-- - tblmekanik (TABLE)
-- - tbl_master_kepala_mekanik (TABLE)

-- ============================================================
-- Hapus VIEW lama jika ada (hanya untuk objek yang memang VIEW)
-- ============================================================
DROP VIEW IF EXISTS `view_absensi`;
DROP VIEW IF EXISTS `view_cari_item`;
DROP VIEW IF EXISTS `view_cari_kendaraan`;
DROP VIEW IF EXISTS `view_cari_pelanggan`;
DROP VIEW IF EXISTS `view_do_tracking_latest`;
DROP VIEW IF EXISTS `view_item_classified`;
DROP VIEW IF EXISTS `view_keluhan_workorder`;
DROP VIEW IF EXISTS `view_laporan_cancel_servis`;
DROP VIEW IF EXISTS `view_master_kategori_member`;
DROP VIEW IF EXISTS `view_master_keluhan`;
DROP VIEW IF EXISTS `view_master_kepala_mekanik_aktif`;
DROP VIEW IF EXISTS `view_mekanik_users`;
DROP VIEW IF EXISTS `view_pelanggan_follow_up`;
DROP VIEW IF EXISTS `view_pelanggan_kendaraan`;
DROP VIEW IF EXISTS `view_pembayaran_hutang`;
DROP VIEW IF EXISTS `view_pembayaran_piutang`;
DROP VIEW IF EXISTS `view_pembelian_detail`;
DROP VIEW IF EXISTS `view_pembelian_header`;
DROP VIEW IF EXISTS `view_penawaran_part_lengkap`;
DROP VIEW IF EXISTS `view_penjualan_detail`;
DROP VIEW IF EXISTS `view_penjualan_header`;
DROP VIEW IF EXISTS `view_pesanan_pembelian_detail`;
DROP VIEW IF EXISTS `view_pesanan_pembelian_header`;
DROP VIEW IF EXISTS `view_pesanan_penjualan_h`;
DROP VIEW IF EXISTS `view_po_with_pr`;
DROP VIEW IF EXISTS `view_pr_complete`;
DROP VIEW IF EXISTS `view_retur_pembelian`;
DROP VIEW IF EXISTS `view_ringkasan_kedatangan_pelanggan`;
DROP VIEW IF EXISTS `view_riwayat_kedatangan_detail`;
DROP VIEW IF EXISTS `view_service`;
DROP VIEW IF EXISTS `view_servis_keluhan_lengkap`;
DROP VIEW IF EXISTS `view_servis_status_summary`;
DROP VIEW IF EXISTS `view_servis_temuan_lengkap`;
DROP VIEW IF EXISTS `view_servis_workorder_lengkap`;
DROP VIEW IF EXISTS `view_statistik_pelanggan`;
DROP VIEW IF EXISTS `view_stok`;
DROP VIEW IF EXISTS `view_stok_master`;
DROP VIEW IF EXISTS `view_suggested_parts`;
DROP VIEW IF EXISTS `view_tarif_jemput`;
DROP VIEW IF EXISTS `view_temuan_penawaran_summary`;
DROP VIEW IF EXISTS `view_top_pelanggan`;
DROP VIEW IF EXISTS `view_user_details`;
DROP VIEW IF EXISTS `view_workorder_complete`;
DROP VIEW IF EXISTS `view_wo_harga`;
DROP VIEW IF EXISTS `v_do_status`;
DROP VIEW IF EXISTS `v_po_status`;
DROP VIEW IF EXISTS `v_pr_status`;

-- ============================================================
-- VIEW 1: view_cari_item
-- DESKRIPSI: View untuk pencarian item/barang dengan informasi lengkap
-- ============================================================
CREATE VIEW `view_cari_item` AS
SELECT
    i.kode_barang,
    i.nama_barang,
    i.kode_kategori,
    k.nama_kategori,
    i.kode_satuan,
    i.harga_jual,
    i.harga_beli,
    COALESCE(s.saldo, 0) as stok,
    i.status_aktif
FROM tblitem i
LEFT JOIN tblitemkategori k ON i.kode_kategori = k.kode_kategori
LEFT JOIN (
    SELECT kode_barang, SUM(saldo) as saldo
    FROM tbstok
    GROUP BY kode_barang
) s ON i.kode_barang = s.kode_barang
WHERE i.status_aktif = 'aktif';

-- ============================================================
-- VIEW 2: view_cari_kendaraan
-- DESKRIPSI: View untuk pencarian kendaraan dengan informasi lengkap
-- ============================================================
CREATE VIEW `view_cari_kendaraan` AS
SELECT
    k.nopol,
    k.no_rangka,
    k.no_mesin,
    k.nopelanggan,
    p.nama as nama_pelanggan,
    p.telp as telp_pelanggan,
    k.merek_id,
    m.nama_brand as nama_merek,
    k.tipe_id,
    t.nama_tipe,
    k.tahun,
    k.warna_id,
    w.warna as nama_warna
FROM tblkendaraan k
LEFT JOIN tblpelanggan p ON k.nopelanggan = p.nopelanggan
LEFT JOIN tbpabrik_motor m ON k.merek_id = m.id
LEFT JOIN tbtipe_motor t ON k.tipe_id = t.kode_tipe
LEFT JOIN tbwarna w ON k.warna_id = w.id;

-- ============================================================
-- VIEW 3: view_cari_pelanggan
-- DESKRIPSI: View untuk pencarian pelanggan dengan informasi kategori
-- ============================================================
CREATE VIEW `view_cari_pelanggan` AS
SELECT
    p.nopelanggan,
    p.nama,
    p.alamat,
    p.telp,
    p.email,
    p.kategori_id,
    k.nama_kategori,
    k.diskon_persen,
    p.tanggal_daftar,
    p.status
FROM tblpelanggan p
LEFT JOIN master_kategori_member k ON p.kategori_id = k.id
WHERE p.status = 'aktif';

-- ============================================================
-- VIEW 4: view_pelanggan_kendaraan
-- DESKRIPSI: View gabungan pelanggan dan kendaraan
-- ============================================================
CREATE VIEW `view_pelanggan_kendaraan` AS
SELECT
    p.nopelanggan,
    p.nama as nama_pelanggan,
    p.alamat,
    p.telp,
    p.email,
    k.nopol,
    k.no_rangka,
    k.no_mesin,
    m.nama_brand as merek_motor,
    t.nama_tipe as tipe_motor,
    k.tahun,
    w.warna as warna_motor,
    p.kategori_id,
    km.nama_kategori as kategori_member,
    km.diskon_persen
FROM tblpelanggan p
LEFT JOIN tblkendaraan k ON p.nopelanggan = k.nopelanggan
LEFT JOIN tbpabrik_motor m ON k.merek_id = m.id
LEFT JOIN tbtipe_motor t ON k.tipe_id = t.kode_tipe
LEFT JOIN tbwarna w ON k.warna_id = w.id
LEFT JOIN master_kategori_member km ON p.kategori_id = km.id
WHERE p.status = 'aktif';

-- ============================================================
-- VIEW 5: view_stok
-- DESKRIPSI: View untuk transaksi stok dengan informasi item
-- ============================================================
CREATE VIEW `view_stok` AS
SELECT
    s.id,
    s.kode_barang,
    i.nama_barang,
    i.kode_kategori,
    k.nama_kategori,
    s.kode_cabang,
    c.nama_cabang,
    s.tanggal,
    s.jenis_transaksi,
    s.qty_masuk,
    s.qty_keluar,
    s.saldo,
    s.harga_satuan,
    s.keterangan,
    s.no_referensi
FROM tbstok s
LEFT JOIN tblitem i ON s.kode_barang = i.kode_barang
LEFT JOIN tblitemkategori k ON i.kode_kategori = k.kode_kategori
LEFT JOIN tbcabang c ON s.kode_cabang = c.kode_cabang
ORDER BY s.tanggal DESC, s.id DESC;

-- ============================================================
-- VIEW 6: view_stok_master
-- DESKRIPSI: View untuk saldo stok per cabang
-- ============================================================
CREATE VIEW `view_stok_master` AS
SELECT
    s.kode_barang,
    i.nama_barang,
    i.kode_kategori,
    k.nama_kategori,
    s.kode_cabang,
    c.nama_cabang,
    SUM(COALESCE(s.qty_masuk, 0)) as total_masuk,
    SUM(COALESCE(s.qty_keluar, 0)) as total_keluar,
    MAX(s.saldo) as saldo_akhir,
    i.harga_jual,
    i.harga_beli,
    (MAX(s.saldo) * i.harga_beli) as nilai_stok
FROM tbstok s
LEFT JOIN tblitem i ON s.kode_barang = i.kode_barang
LEFT JOIN tblitemkategori k ON i.kode_kategori = k.kode_kategori
LEFT JOIN tbcabang c ON s.kode_cabang = c.kode_cabang
GROUP BY s.kode_barang, s.kode_cabang, i.nama_barang, i.kode_kategori,
         k.nama_kategori, c.nama_cabang, i.harga_jual, i.harga_beli;

-- ============================================================
-- VIEW 7: view_master_keluhan
-- DESKRIPSI: View untuk master keluhan yang aktif
-- ============================================================
CREATE VIEW `view_master_keluhan` AS
SELECT
    kode_keluhan,
    nama_keluhan,
    deskripsi,
    kategori,
    estimasi_waktu,
    created_at,
    updated_at
FROM tbmaster_keluhan
WHERE status = 'aktif'
ORDER BY kategori, nama_keluhan;

-- ============================================================
-- VIEW 8: view_keluhan_workorder
-- DESKRIPSI: View mapping keluhan ke workorder
-- ============================================================
CREATE VIEW `view_keluhan_workorder` AS
SELECT
    mk.kode_keluhan,
    mk.nama_keluhan,
    mk.kategori as kategori_keluhan,
    w.kode_wo,
    w.nama_wo,
    w.harga_jasa,
    w.estimasi_waktu
FROM tbmaster_keluhan mk
LEFT JOIN tbmaster_keluhan_workorder mw ON mk.kode_keluhan = mw.kode_keluhan
LEFT JOIN tbworkorderheader w ON mw.kode_wo = w.kode_wo
WHERE mk.status = 'aktif';

-- ============================================================
-- VIEW 9: view_tarif_jemput
-- DESKRIPSI: View tarif jemput dengan range jarak
-- ============================================================
CREATE VIEW `view_tarif_jemput` AS
SELECT
    id,
    jenis_motor,
    jarak_km,
    tarif,
    keterangan,
    created_at,
    updated_at
FROM master_tarif_jemput
ORDER BY jenis_motor, jarak_km;

-- ============================================================
-- VIEW 10: v_po_status
-- DESKRIPSI: View status Purchase Order dengan informasi lengkap
-- ============================================================
CREATE VIEW `v_po_status` AS
SELECT
    h.no_order,
    h.no_pr,
    h.no_supplier,
    s.nama as nama_supplier,
    h.tanggal,
    h.kd_cabang,
    c.nama_cabang,
    h.status,
    h.status_approval,
    h.approved_by,
    h.approved_date,
    COUNT(d.kode_barang) as total_items,
    SUM(d.qty) as total_qty_po,
    SUM(COALESCE(do_d.qty_terima, 0)) as total_qty_terima,
    CASE
        WHEN SUM(d.qty) > 0 THEN ROUND((SUM(COALESCE(do_d.qty_terima, 0)) / SUM(d.qty)) * 100, 2)
        ELSE 0
    END as persen_terima
FROM tblorder_header h
LEFT JOIN tblorder_detail d ON h.no_order = d.no_order
LEFT JOIN tblsupplier s ON h.no_supplier = s.no_supplier
LEFT JOIN tbcabang c ON h.kd_cabang = c.kode_cabang
LEFT JOIN tbldelivery_order_header do_h ON h.no_order = do_h.no_po
LEFT JOIN tbldelivery_order_detail do_d ON do_h.no_do = do_d.no_do AND d.kode_barang = do_d.kode_barang
GROUP BY h.no_order, h.no_pr, h.no_supplier, s.nama, h.tanggal, h.kd_cabang,
         c.nama_cabang, h.status, h.status_approval, h.approved_by, h.approved_date;

-- ============================================================
-- VIEW 11: view_pembelian_header
-- DESKRIPSI: View header pembelian dengan informasi supplier
-- ============================================================
CREATE VIEW `view_pembelian_header` AS
SELECT
    h.no_pembelian,
    h.tanggal,
    h.no_supplier,
    s.nama as nama_supplier,
    s.telp as telp_supplier,
    h.kd_cabang,
    c.nama_cabang,
    h.total_qty,
    h.total_nilai,
    h.ppn,
    h.grand_total,
    h.status,
    h.created_by,
    h.created_at
FROM tblpembelian_header h
LEFT JOIN tblsupplier s ON h.no_supplier = s.no_supplier
LEFT JOIN tbcabang c ON h.kd_cabang = c.kode_cabang;

-- ============================================================
-- VIEW 12: view_pembelian_detail
-- DESKRIPSI: View detail pembelian dengan informasi item
-- ============================================================
CREATE VIEW `view_pembelian_detail` AS
SELECT
    d.no_pembelian,
    h.tanggal,
    d.kode_barang,
    i.nama_barang,
    i.kode_kategori,
    k.nama_kategori,
    d.qty,
    d.harga_beli,
    d.diskon_persen,
    d.diskon_nominal,
    d.subtotal,
    h.no_supplier,
    s.nama as nama_supplier
FROM tblpembelian_detail d
LEFT JOIN tblpembelian_header h ON d.no_pembelian = h.no_pembelian
LEFT JOIN tblitem i ON d.kode_barang = i.kode_barang
LEFT JOIN tblitemkategori k ON i.kode_kategori = k.kode_kategori
LEFT JOIN tblsupplier s ON h.no_supplier = s.no_supplier;

-- ============================================================
-- VIEW 13: view_penjualan_header
-- DESKRIPSI: View header penjualan dengan informasi pelanggan
-- ============================================================
CREATE VIEW `view_penjualan_header` AS
SELECT
    h.no_penjualan,
    h.tanggal,
    h.nopelanggan,
    p.nama as nama_pelanggan,
    p.telp as telp_pelanggan,
    h.kd_cabang,
    c.nama_cabang,
    h.total_qty,
    h.total_nilai,
    h.diskon_member,
    h.ppn,
    h.grand_total,
    h.status,
    h.created_by,
    h.created_at
FROM tblpenjualan_header h
LEFT JOIN tblpelanggan p ON h.nopelanggan = p.nopelanggan
LEFT JOIN tbcabang c ON h.kd_cabang = c.kode_cabang;

-- ============================================================
-- VIEW 14: view_penjualan_detail
-- DESKRIPSI: View detail penjualan dengan informasi item
-- ============================================================
CREATE VIEW `view_penjualan_detail` AS
SELECT
    d.no_penjualan,
    h.tanggal,
    d.kode_barang,
    i.nama_barang,
    i.kode_kategori,
    k.nama_kategori,
    d.qty,
    d.harga_jual,
    d.diskon_persen,
    d.diskon_nominal,
    d.subtotal,
    h.nopelanggan,
    p.nama as nama_pelanggan
FROM tblpenjualan_detail d
LEFT JOIN tblpenjualan_header h ON d.no_penjualan = h.no_penjualan
LEFT JOIN tblitem i ON d.kode_barang = i.kode_barang
LEFT JOIN tblitemkategori k ON i.kode_kategori = k.kode_kategori
LEFT JOIN tblpelanggan p ON h.nopelanggan = p.nopelanggan;

-- ============================================================
-- VIEW 15: view_user_details
-- DESKRIPSI: View detail user dengan informasi cabang dan role
-- ============================================================
CREATE VIEW `view_user_details` AS
SELECT
    u.id,
    u.nama_user,
    u.username,
    u.email,
    u.user_akses,
    u.kode_cabang,
    c.nama_cabang,
    u.status,
    u.last_login,
    u.created_at,
    u.updated_at
FROM tbuser u
LEFT JOIN tbcabang c ON u.kode_cabang = c.kode_cabang;

-- ============================================================
-- COMPLETED!
-- Total VIEW berhasil dibuat: 15 VIEW
-- ============================================================
-- CARA VERIFIKASI:
-- 1. SHOW FULL TABLES WHERE table_type = 'VIEW';
-- 2. SELECT * FROM view_cari_item LIMIT 5;
-- 3. SELECT * FROM view_stok_master LIMIT 5;
-- ============================================================
