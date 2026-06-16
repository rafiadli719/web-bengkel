-- ============================================================
-- FILE: create_all_views.sql
-- DESKRIPSI: Script untuk membuat semua VIEW yang hilang/rusak
-- TANGGAL: 2025-11-28
-- ============================================================

-- Hapus VIEW lama jika ada
DROP VIEW IF EXISTS `master_tarif_jemput`;
DROP VIEW IF EXISTS `tblmekanik`;
DROP VIEW IF EXISTS `tbl_master_kepala_mekanik`;
DROP VIEW IF EXISTS `view_absensi`;
DROP VIEW IF EXISTS `view_cari_item`;
DROP VIEW IF EXISTS `view_cari_kendaraan`;
DROP VIEW IF EXISTS `view_cari_pelanggan`;
DROP VIEW IF EXISTS `view_keluhan_workorder`;
DROP VIEW IF EXISTS `view_master_keluhan`;
DROP VIEW IF EXISTS `view_pelanggan_kendaraan`;
DROP VIEW IF EXISTS `view_servis_keluhan_lengkap`;
DROP VIEW IF EXISTS `view_servis_workorder_lengkap`;
DROP VIEW IF EXISTS `view_stok`;
DROP VIEW IF EXISTS `view_stok_master`;
DROP VIEW IF EXISTS `v_po_status`;
DROP VIEW IF EXISTS `v_pr_status`;

-- ============================================================
-- VIEW: master_tarif_jemput
-- DESKRIPSI: View untuk tarif jemput motor dari master_tarif_jemput_range
-- ============================================================
CREATE VIEW `master_tarif_jemput` AS
SELECT
    id,
    jenis_motor,
    jarak_min as jarak_km,
    tarif_dasar as tarif,
    keterangan,
    created_at,
    updated_at
FROM master_tarif_jemput_range
WHERE aktif = 1
ORDER BY jenis_motor, jarak_min;

-- ============================================================
-- VIEW: tblmekanik
-- DESKRIPSI: View untuk data mekanik dari tbuser_karyawan dengan posisi mekanik
-- ============================================================
CREATE VIEW `tblmekanik` AS
SELECT
    k.kode_karyawan as nomekanik,
    k.nama_lengkap as nama,
    k.alamat,
    k.telp,
    CASE
        WHEN k.kode_level = 'KEPALA_MEKANIK' THEN '1'
        WHEN k.kode_level = 'MEKANIK_SENIOR' THEN '2'
        WHEN k.kode_level = 'MEKANIK_JUNIOR' THEN '3'
        ELSE '3'
    END as keahlian,
    CASE
        WHEN k.tanggal_keluar IS NULL THEN 'aktif'
        ELSE 'nonak'
    END as status,
    k.email,
    k.tanggal_masuk,
    NULL as gaji_pokok,
    k.spesialisasi,
    k.sertifikat,
    k.created_at,
    k.updated_at
FROM tbuser_karyawan k
WHERE k.kode_posisi IN ('MEKANIK', 'KEPALA_MEKANIK')
ORDER BY k.kode_karyawan;

-- ============================================================
-- VIEW: tbl_master_kepala_mekanik
-- DESKRIPSI: View untuk master kepala mekanik dari tbuser_karyawan
-- ============================================================
CREATE VIEW `tbl_master_kepala_mekanik` AS
SELECT
    k.kode_karyawan as id,
    k.kode_cabang,
    k.nama_lengkap as nama_kepala_mekanik,
    k.nik as nip_karyawan,
    k.telp as no_telepon,
    k.tanggal_masuk as tanggal_mulai,
    k.tanggal_keluar as tanggal_selesai,
    CASE
        WHEN k.tanggal_keluar IS NULL THEN 'aktif'
        ELSE 'nonak'
    END as status_aktif,
    NULL as created_by,
    k.created_at,
    k.updated_at
FROM tbuser_karyawan k
WHERE k.kode_posisi = 'KEPALA_MEKANIK' OR k.kode_level = 'KEPALA_MEKANIK'
ORDER BY k.kode_cabang, k.tanggal_masuk DESC;

-- ============================================================
-- VIEW: view_absensi
-- DESKRIPSI: View untuk data absensi dengan format extended
-- ============================================================
CREATE VIEW `view_absensi` AS
SELECT
    a.nip,
    a.tgl,
    DAY(a.tgl) as tanggal,
    MONTH(a.tgl) as bulan,
    YEAR(a.tgl) as tahun,
    a.jam_masuk,
    a.jam_keluar,
    CONCAT(a.jam_masuk, '-', a.jam_keluar) as jam_absensi,
    a.kode_status_kehadiran,
    a.keterangan,
    a.id,
    a.kode_perusahaan,
    a.kode_lokasi
FROM tbabsensi a
ORDER BY a.tgl DESC, a.nip;

-- ============================================================
-- VIEW: view_cari_item
-- DESKRIPSI: View untuk pencarian item/barang dengan detail lengkap
-- ============================================================
CREATE VIEW `view_cari_item` AS
SELECT
    i.noitem,
    i.namaitem,
    i.nama_part_resmi,
    i.kodebarcode,
    i.merek,
    i.tipe_item,
    i.status_validasi,
    i.kategori_rak,
    i.hargapokok,
    i.hargajual,
    i.statusitem,
    i.satuan,
    i.stokmin,
    i.rakbarang,
    j.namajenis,
    i.note as keterangan,
    i.created_at,
    i.updated_at
FROM tblitem i
LEFT JOIN tblitemjenis j ON i.jenis = j.jenis
WHERE i.statusitem = '1'
ORDER BY i.namaitem;

-- ============================================================
-- VIEW: view_cari_kendaraan
-- DESKRIPSI: View untuk pencarian kendaraan dengan detail lengkap
-- ============================================================
CREATE VIEW `view_cari_kendaraan` AS
SELECT
    k.nopolisi,
    k.pemilik,
    k.alamat,
    k.kode_merek,
    k.tipe,
    k.kode_tipe,
    k.jenis,
    k.kode_jenis,
    k.tahun_buat,
    k.tahun_rakit,
    k.silinder,
    k.warna,
    k.kode_warna,
    k.no_rangka,
    k.no_mesin,
    k.note,
    COALESCE(
        NULLIF(pm.merek, ''),
        CASE
            WHEN (k.kode_merek = '0' OR k.kode_merek IS NULL OR k.kode_merek = '')
            THEN TRIM(SUBSTRING_INDEX(k.tipe, ' ', 1))
            ELSE ''
        END
    ) as merek
FROM tblkendaraan k
LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
ORDER BY k.nopolisi;

-- ============================================================
-- VIEW: view_cari_pelanggan
-- DESKRIPSI: View untuk pencarian pelanggan dengan grup
-- ============================================================
CREATE VIEW `view_cari_pelanggan` AS
SELECT
    p.nopelanggan,
    p.namapelanggan,
    p.alamat,
    p.kota,
    p.propinsi,
    p.kodepost,
    p.negara,
    p.telephone,
    p.fax,
    p.kontakperson,
    p.note,
    p.potongan,
    p.tipepot,
    p.lavelharga,
    p.kgrup,
    p.patokan,
    p.klat,
    p.klong,
    p.panggilan,
    g.grup
FROM tblpelanggan p
LEFT JOIN tblpelanggangrup g ON p.kgrup = g.kgrup
ORDER BY p.namapelanggan;

-- ============================================================
-- VIEW: view_keluhan_workorder
-- DESKRIPSI: View untuk mapping keluhan dengan workorder
-- ============================================================
CREATE VIEW `view_keluhan_workorder` AS
SELECT
    kw.id,
    kw.kode_keluhan,
    k.nama_keluhan,
    kw.kode_workorder,
    w.nama_wo,
    w.keterangan as deskripsi_wo,
    w.harga as harga_wo,
    w.waktu as estimasi_waktu,
    kw.prioritas,
    kw.status_aktif,
    kw.created_at,
    kw.updated_at
FROM tbmaster_keluhan_workorder kw
LEFT JOIN tbmaster_keluhan k ON kw.kode_keluhan = k.kode_keluhan
LEFT JOIN tbworkorderheader w ON kw.kode_workorder = w.kode_wo
WHERE kw.status_aktif = '1'
ORDER BY kw.kode_keluhan, kw.prioritas;

-- ============================================================
-- VIEW: view_master_keluhan
-- DESKRIPSI: View untuk master keluhan
-- ============================================================
CREATE VIEW `view_master_keluhan` AS
SELECT
    id,
    kode_keluhan,
    nama_keluhan,
    deskripsi,
    kategori,
    status_aktif,
    created_at,
    updated_at
FROM tbmaster_keluhan
WHERE status_aktif = '1'
ORDER BY kode_keluhan;

-- ============================================================
-- VIEW: view_pelanggan_kendaraan
-- DESKRIPSI: View untuk pelanggan dengan kendaraan yang dimiliki
-- ============================================================
CREATE VIEW `view_pelanggan_kendaraan` AS
SELECT
    k.nopolisi,
    k.pemilik,
    k.alamat,
    k.kode_merek,
    k.tipe,
    k.kode_tipe,
    k.jenis,
    k.kode_jenis,
    k.tahun_buat,
    k.tahun_rakit,
    k.silinder,
    k.warna,
    k.kode_warna,
    k.no_rangka,
    k.no_mesin,
    k.note,
    COALESCE(pm.merek, '') as merek,
    p.telephone
FROM tblkendaraan k
LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
LEFT JOIN tblpelanggan p ON k.pemilik = p.namapelanggan
ORDER BY k.pemilik, k.nopolisi;

-- ============================================================
-- VIEW: view_servis_keluhan_lengkap
-- DESKRIPSI: View untuk servis dengan detail keluhan lengkap
-- ============================================================
CREATE VIEW `view_servis_keluhan_lengkap` AS
SELECT
    sk.id,
    sk.no_service,
    sk.keluhan,
    sk.status_pengerjaan,
    sk.keterangan_tidak_selesai,
    sk.auto_workorder,
    sk.workorder_applied,
    sk.created_at,
    sk.updated_at,
    s.tanggal as tanggal_service,
    s.jam as jam_service,
    s.no_pelanggan,
    s.no_polisi,
    s.status_servis,
    p.namapelanggan,
    p.telephone as hp_pelanggan,
    p.notlp as hp_pelanggan2,
    p.alamat as alamat_pelanggan,
    k.kode_merek,
    k.tipe as tipe_kendaraan,
    k.jenis as jenis_kendaraan,
    k.tahun_buat,
    w.nama_wo as nama_workorder,
    COALESCE(s.jumlah_temuan, 0) as jumlah_temuan,
    CASE sk.status_pengerjaan
        WHEN 'datang' THEN '#6c757d'
        WHEN 'diproses' THEN '#ffc107'
        WHEN 'selesai' THEN '#28a745'
        WHEN 'tidak_selesai' THEN '#dc3545'
        ELSE '#6c757d'
    END as status_badge_color
FROM tbservis_keluhan_status sk
LEFT JOIN tblservice s ON sk.no_service = s.no_service
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
LEFT JOIN tbworkorderheader w ON sk.auto_workorder = w.kode_wo
ORDER BY sk.created_at DESC;

-- ============================================================
-- VIEW: view_servis_workorder_lengkap
-- DESKRIPSI: View untuk servis dengan detail workorder lengkap
-- ============================================================
CREATE VIEW `view_servis_workorder_lengkap` AS
SELECT
    sw.id,
    sw.no_service,
    sw.kode_wo,
    sw.status_pengerjaan,
    sw.keterangan_tidak_selesai,
    sw.created_at,
    sw.updated_at,
    w.nama_wo,
    w.keterangan as deskripsi_wo,
    w.harga as harga_wo,
    w.waktu as estimasi_waktu,
    w.status as status_wo,
    s.tanggal as tanggal_service,
    s.jam as jam_service,
    s.no_pelanggan,
    s.no_polisi,
    s.status_servis,
    p.namapelanggan,
    p.telephone as hp_pelanggan,
    p.notlp as hp_pelanggan2,
    p.alamat as alamat_pelanggan,
    k.kode_merek,
    k.tipe as tipe_kendaraan,
    k.jenis as jenis_kendaraan,
    k.tahun_buat,
    0 as jumlah_item,
    0 as jumlah_jasa,
    0 as jumlah_barang,
    CASE sw.status_pengerjaan
        WHEN 'diproses' THEN '#ffc107'
        WHEN 'selesai' THEN '#28a745'
        WHEN 'tidak_selesai' THEN '#dc3545'
        ELSE '#6c757d'
    END as status_badge_color,
    CASE sw.status_pengerjaan
        WHEN 'selesai' THEN 100
        WHEN 'diproses' THEN 50
        WHEN 'tidak_selesai' THEN 0
        ELSE 0
    END as progress_percentage
FROM tbservis_workorder sw
LEFT JOIN tbworkorderheader w ON sw.kode_wo = w.kode_wo
LEFT JOIN tblservice s ON sw.no_service = s.no_service
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
ORDER BY sw.created_at DESC;

-- ============================================================
-- VIEW: view_stok
-- DESKRIPSI: View untuk transaksi stok (masuk/keluar)
-- ============================================================
CREATE VIEW `view_stok` AS
SELECT
    s.tipe,
    s.no_transaksi,
    s.no_item,
    s.tanggal,
    s.masuk,
    s.keluar,
    s.keterangan,
    s.id,
    DATE_FORMAT(s.tanggal, '%d-%m-%Y') as tgl_trx,
    i.namaitem,
    s.kd_cabang
FROM tbstok s
LEFT JOIN tblitem i ON s.no_item = i.noitem
ORDER BY s.tanggal DESC, s.id DESC;

-- ============================================================
-- VIEW: view_stok_master
-- DESKRIPSI: View untuk saldo stok per cabang per item
-- ============================================================
CREATE VIEW `view_stok_master` AS
SELECT
    s.kd_cabang,
    s.no_item,
    SUM(s.masuk - s.keluar) as saldo
FROM tbstok s
GROUP BY s.kd_cabang, s.no_item
ORDER BY s.kd_cabang, s.no_item;

-- ============================================================
-- VIEW: v_po_status
-- DESKRIPSI: View untuk status Purchase Order dengan approval
-- ============================================================
CREATE VIEW `v_po_status` AS
SELECT
    po.no_order,
    po.no_pr,
    po.no_supplier,
    po.tanggal,
    po.kd_cabang,
    po.status,
    po.status_approval,
    po.approved_by,
    po.approved_date,
    COUNT(DISTINCT pod.no_item) as total_items,
    SUM(pod.quantity) as total_qty_po,
    COALESCE(SUM(dod.qty_terima), 0) as total_qty_terima,
    CASE
        WHEN SUM(pod.quantity) > 0 THEN
            ROUND((COALESCE(SUM(dod.qty_terima), 0) / SUM(pod.quantity) * 100), 2)
        ELSE 0
    END as persen_terima
FROM tblorder_header po
LEFT JOIN tblorder_detail pod ON po.no_order = pod.no_order
LEFT JOIN tbldelivery_order_header doh ON po.no_order = doh.no_po
LEFT JOIN tbldelivery_order_detail dod ON doh.no_do = dod.no_do AND pod.no_item = dod.no_item
GROUP BY po.no_order, po.no_pr, po.no_supplier, po.tanggal, po.kd_cabang,
         po.status, po.status_approval, po.approved_by, po.approved_date
ORDER BY po.tanggal DESC;

-- ============================================================
-- VIEW: v_pr_status
-- DESKRIPSI: View untuk status Purchase Request
-- Note: Tabel tblpurchase_request belum ada, jadi view ini kosong
-- Akan diisi ketika tabel PR sudah dibuat
-- ============================================================
CREATE VIEW `v_pr_status` AS
SELECT
    NULL as no_pr,
    NULL as tanggal,
    NULL as kd_cabang,
    NULL as status,
    NULL as status_approval,
    NULL as approved_by,
    NULL as approved_date,
    NULL as total_items,
    NULL as total_qty
WHERE 1=0;
-- Komentar: View ini sementara kosong karena tabel purchase_request belum ada
-- Ketika tabel sudah dibuat, silakan update query di atas

-- ============================================================
-- SELESAI - Semua VIEW telah dibuat
-- ============================================================

-- Verifikasi VIEW yang telah dibuat
SELECT 'VIEW telah dibuat:' as status;
SHOW FULL TABLES WHERE table_type = 'VIEW';
