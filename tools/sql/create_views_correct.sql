-- ============================================================
-- FILE: create_views_correct.sql
-- DESKRIPSI: Script FINAL dengan nama tabel dan kolom yang BENAR
-- TANGGAL: 2025-11-28
-- STATUS: SUDAH DISESUAIKAN DENGAN STRUKTUR DATABASE AKTUAL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- STEP 1: Hapus TABLE placeholder VIEW
-- ============================================================
DROP TABLE IF EXISTS `view_cari_item`;
DROP TABLE IF EXISTS `view_cari_kendaraan`;
DROP TABLE IF EXISTS `view_cari_pelanggan`;
DROP TABLE IF EXISTS `view_pelanggan_kendaraan`;
DROP TABLE IF EXISTS `view_stok`;
DROP TABLE IF EXISTS `view_stok_master`;
DROP TABLE IF EXISTS `view_master_keluhan`;
DROP TABLE IF EXISTS `view_keluhan_workorder`;
DROP TABLE IF EXISTS `view_tarif_jemput`;
DROP TABLE IF EXISTS `view_pembelian_header`;
DROP TABLE IF EXISTS `view_pembelian_detail`;
DROP TABLE IF EXISTS `view_penjualan_header`;
DROP TABLE IF EXISTS `view_penjualan_detail`;
DROP TABLE IF EXISTS `view_user_details`;
DROP TABLE IF EXISTS `v_po_status`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- STEP 2: Buat VIEW yang sebenarnya
-- ============================================================

-- VIEW 1: view_cari_item (SUDAH DIPERBAIKI)
CREATE VIEW `view_cari_item` AS
SELECT
    i.noitem as kode_barang,
    i.namaitem as nama_barang,
    i.jenis as kode_jenis,
    j.namajenis as nama_jenis,
    i.satuan as kode_satuan,
    i.hargajual as harga_jual,
    i.hargapokok as harga_beli,
    COALESCE(i.quantity, 0) as stok,
    i.statusitem as status_aktif
FROM tblitem i
LEFT JOIN tblitemjenis j ON i.jenis = j.jenis
WHERE i.statusitem = '1';

-- VIEW 2: view_cari_kendaraan (SUDAH DIPERBAIKI)
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

-- VIEW 3: view_cari_pelanggan (SUDAH DIPERBAIKI)
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

-- VIEW 4: view_pelanggan_kendaraan (SUDAH DIPERBAIKI)
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

-- VIEW 5: view_stok (SUDAH DIPERBAIKI - menggunakan tblitem_stok)
CREATE VIEW `view_stok` AS
SELECT
    s.id,
    s.kode_barang,
    i.namaitem as nama_barang,
    i.jenis as kode_jenis,
    j.namajenis as nama_jenis,
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
FROM tblitem_stok s
LEFT JOIN tblitem i ON s.kode_barang = i.noitem
LEFT JOIN tblitemjenis j ON i.jenis = j.jenis
LEFT JOIN tbcabang c ON s.kode_cabang = c.kode_cabang
ORDER BY s.tanggal DESC, s.id DESC;

-- VIEW 6: view_stok_master (SUDAH DIPERBAIKI)
CREATE VIEW `view_stok_master` AS
SELECT
    s.kode_barang,
    i.namaitem as nama_barang,
    i.jenis as kode_jenis,
    j.namajenis as nama_jenis,
    s.kode_cabang,
    c.nama_cabang,
    SUM(COALESCE(s.qty_masuk, 0)) as total_masuk,
    SUM(COALESCE(s.qty_keluar, 0)) as total_keluar,
    MAX(s.saldo) as saldo_akhir,
    i.hargajual as harga_jual,
    i.hargapokok as harga_beli,
    (MAX(s.saldo) * i.hargapokok) as nilai_stok
FROM tblitem_stok s
LEFT JOIN tblitem i ON s.kode_barang = i.noitem
LEFT JOIN tblitemjenis j ON i.jenis = j.jenis
LEFT JOIN tbcabang c ON s.kode_cabang = c.kode_cabang
GROUP BY s.kode_barang, s.kode_cabang, i.namaitem, i.jenis,
         j.namajenis, c.nama_cabang, i.hargajual, i.hargapokok;

-- VIEW 7: view_master_keluhan (SUDAH DIPERBAIKI)
CREATE VIEW `view_master_keluhan` AS
SELECT
    kode_keluhan,
    nama_keluhan,
    deskripsi,
    kategori,
    created_at,
    updated_at
FROM tbmaster_keluhan
WHERE status_aktif = '1' AND status_approval = 'approved'
ORDER BY kategori, nama_keluhan;

-- VIEW 8: view_keluhan_workorder (SUDAH DIPERBAIKI)
CREATE VIEW `view_keluhan_workorder` AS
SELECT
    mk.kode_keluhan,
    mk.nama_keluhan,
    mk.kategori as kategori_keluhan,
    w.kode_wo,
    w.nama_wo,
    w.harga_jasa
FROM tbmaster_keluhan mk
LEFT JOIN tbmaster_keluhan_workorder mw ON mk.kode_keluhan = mw.kode_keluhan
LEFT JOIN tbworkorderheader w ON mw.kode_wo = w.kode_wo
WHERE mk.status_aktif = '1';

-- VIEW 9: view_tarif_jemput
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

-- VIEW 10: v_po_status (SUDAH DIPERBAIKI)
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

-- VIEW 11: view_user_details
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
-- SELESAI!
-- Total VIEW berhasil dibuat: 11 VIEW
-- ============================================================

SELECT 'BERHASIL! 11 VIEW telah dibuat.' as STATUS,
       'Silakan jalankan: SHOW FULL TABLES WHERE table_type = \'VIEW\';' as VERIFIKASI;
