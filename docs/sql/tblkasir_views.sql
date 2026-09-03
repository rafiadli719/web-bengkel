-- Task 9: Rebuild 10 VIEW modul kasir, rename tabel sumber ke *_closing_kasir
-- + rename VIEW pakai konvensi fitmotor (prefix view_, bukan v_)
-- + `users` (tabel web_kasir sendiri, TIDAK dimigrasi) diganti `tbuser`:
--   u.nama_karyawan -> u.nama_lengkap, u.nama_cabang -> JOIN tbcabang via u.kode_cabang

CREATE VIEW `view_transaksi_ada_selisih_closing_kasir` AS
SELECT kt.id, kt.kode_transaksi, kt.kas_awal, kt.kas_akhir, kt.total_pemasukan, kt.total_pengeluaran,
  kt.total_penjualan, kt.total_servis, kt.status, kt.tanggal_transaksi, kt.tanggal_closing, kt.jam_closing,
  kt.setoran_real, kt.omset, kt.data_setoran, kt.selisih_setoran, kt.kode_karyawan, kt.kode_cabang,
  kt.nama_cabang, kt.deposit_status, kt.deposit_difference_status, kt.jenis_closing, kt.closing_group_id,
  kt.is_part_of_closing, kt.jenis_setoran_id, kt.kode_setoran, kt.jumlah_diterima_fisik, kt.selisih_fisik,
  kt.catatan_validasi, kt.rekening_tujuan_id, kt.validasi_at, kt.validasi_by, kt.bukti_transaksi,
  sk.nama_cabang AS sk_nama_cabang, sk.nama_pengantar AS sk_nama_pengantar, sk.tanggal_setoran AS sk_tanggal_setoran,
  u.nama_lengkap AS user_nama_karyawan,
  (CASE WHEN (kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%'
    OR EXISTS(SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kt.kode_transaksi))
    THEN 'DARI CLOSING' ELSE 'TRANSAKSI BIASA' END) AS jenis_transaksi
FROM ((kasir_transactions_closing_kasir kt
  LEFT JOIN setoran_keuangan_closing_kasir sk ON (kt.kode_setoran = sk.kode_setoran))
  LEFT JOIN tbuser u ON (sk.kode_karyawan = u.kode_karyawan))
WHERE (kt.deposit_status = 'Validasi Keuangan SELISIH');

CREATE VIEW `view_transaksi_dikembalikan_cs_closing_kasir` AS
SELECT kt.id, kt.kode_transaksi, kt.kas_awal, kt.kas_akhir, kt.total_pemasukan, kt.total_pengeluaran,
  kt.total_penjualan, kt.total_servis, kt.status, kt.tanggal_transaksi, kt.tanggal_closing, kt.jam_closing,
  kt.setoran_real, kt.omset, kt.data_setoran, kt.selisih_setoran, kt.kode_karyawan, kt.kode_cabang,
  kt.nama_cabang, kt.deposit_status, kt.deposit_difference_status, kt.jenis_closing, kt.closing_group_id,
  kt.is_part_of_closing, kt.jenis_setoran_id, kt.kode_setoran, kt.jumlah_diterima_fisik, kt.selisih_fisik,
  kt.catatan_validasi, kt.rekening_tujuan_id, kt.validasi_at, kt.validasi_by, kt.bukti_transaksi,
  sk.nama_cabang AS sk_nama_cabang, sk.nama_pengantar AS sk_nama_pengantar, sk.tanggal_setoran AS sk_tanggal_setoran,
  u.nama_lengkap AS user_nama_karyawan,
  (CASE WHEN (kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%'
    OR EXISTS(SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kt.kode_transaksi))
    THEN 'DARI CLOSING' ELSE 'TRANSAKSI BIASA' END) AS jenis_transaksi
FROM ((kasir_transactions_closing_kasir kt
  LEFT JOIN setoran_keuangan_closing_kasir sk ON (kt.kode_setoran = sk.kode_setoran))
  LEFT JOIN tbuser u ON (sk.kode_karyawan = u.kode_karyawan))
WHERE (kt.deposit_status = 'Dikembalikan ke CS');

CREATE VIEW `view_transaksi_perlu_validasi_closing_kasir` AS
SELECT kt.id, kt.kode_transaksi, kt.kas_awal, kt.kas_akhir, kt.total_pemasukan, kt.total_pengeluaran,
  kt.total_penjualan, kt.total_servis, kt.status, kt.tanggal_transaksi, kt.tanggal_closing, kt.jam_closing,
  kt.setoran_real, kt.omset, kt.data_setoran, kt.selisih_setoran, kt.kode_karyawan, kt.kode_cabang,
  kt.nama_cabang, kt.deposit_status, kt.deposit_difference_status, kt.jenis_closing, kt.closing_group_id,
  kt.is_part_of_closing, kt.jenis_setoran_id, kt.kode_setoran, kt.jumlah_diterima_fisik, kt.selisih_fisik,
  kt.catatan_validasi, kt.rekening_tujuan_id, kt.validasi_at, kt.validasi_by, kt.bukti_transaksi,
  sk.nama_cabang AS sk_nama_cabang, sk.nama_pengantar AS sk_nama_pengantar, sk.tanggal_setoran AS sk_tanggal_setoran,
  u.nama_lengkap AS user_nama_karyawan,
  (CASE WHEN (kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%'
    OR EXISTS(SELECT 1 FROM pemasukan_kasir_closing_kasir pk WHERE pk.nomor_transaksi_closing = kt.kode_transaksi))
    THEN 'DARI CLOSING' ELSE 'TRANSAKSI BIASA' END) AS jenis_transaksi
FROM ((kasir_transactions_closing_kasir kt
  LEFT JOIN setoran_keuangan_closing_kasir sk ON (kt.kode_setoran = sk.kode_setoran))
  LEFT JOIN tbuser u ON (sk.kode_karyawan = u.kode_karyawan))
WHERE (kt.deposit_status = 'Diterima Staff Keuangan');

CREATE VIEW `view_pemasukan_kasir_closing_kasir` AS
SELECT p.id, p.kode_transaksi, k.nama_cabang, 'tidak_ada' AS kategori_akun, p.tanggal, p.waktu, p.kode_akun,
  m.arti AS nama_akun, m.jenis_akun, p.jumlah, p.keterangan_transaksi AS keterangan_akun, p.kode_karyawan,
  'kasir' AS jenis_sumber, p.tanggal AS tanggal_transaksi, CONCAT(p.tanggal, ' ', p.waktu) AS datetime_input
FROM ((pemasukan_kasir_closing_kasir p
  JOIN kasir_transactions_closing_kasir k ON (p.kode_transaksi = k.kode_transaksi))
  LEFT JOIN master_akun_closing_kasir m ON (p.kode_akun = m.kode_akun));

CREATE VIEW `view_pemasukan_pusat_closing_kasir` AS
SELECT pp.id, pp.kode_transaksi, pp.cabang AS nama_cabang, 'tidak_ada' AS kategori_akun, pp.tanggal, pp.waktu,
  pp.kode_akun, ma.arti AS nama_akun, ma.jenis_akun, pp.jumlah, pp.keterangan AS keterangan_akun, pp.kode_karyawan,
  'pusat' AS jenis_sumber, pp.tanggal AS tanggal_transaksi, CONCAT(pp.tanggal, ' ', pp.waktu) AS datetime_input
FROM (pemasukan_pusat_closing_kasir pp LEFT JOIN master_akun_closing_kasir ma ON (pp.kode_akun = ma.kode_akun));

CREATE VIEW `view_pemasukan_combined_closing_kasir` AS
SELECT id, kode_transaksi, nama_cabang, kategori_akun, tanggal, waktu, kode_akun, nama_akun, jenis_akun,
  jumlah, keterangan_akun, kode_karyawan, jenis_sumber, tanggal_transaksi, datetime_input
FROM view_pemasukan_kasir_closing_kasir
UNION ALL
SELECT id, kode_transaksi, nama_cabang, kategori_akun, tanggal, waktu, kode_akun, nama_akun, jenis_akun,
  jumlah, keterangan_akun, kode_karyawan, jenis_sumber, tanggal_transaksi, datetime_input
FROM view_pemasukan_pusat_closing_kasir;

CREATE VIEW `view_pemasukan_with_closing_closing_kasir` AS
SELECT pk.id, pk.kode_transaksi, pk.kode_akun, pk.jumlah, pk.keterangan_transaksi, pk.tanggal, pk.waktu,
  pk.kode_karyawan, pk.nomor_transaksi_closing, u.nama_lengkap AS nama_karyawan, tc.nama_cabang,
  ma.arti AS nama_akun, kt.setoran_real AS nilai_closing, kt.tanggal_transaksi AS tanggal_closing,
  (CASE WHEN (pk.nomor_transaksi_closing IS NOT NULL) THEN 'DARI CLOSING' ELSE 'REGULER' END) AS jenis_pemasukan
FROM (((pemasukan_kasir_closing_kasir pk
  LEFT JOIN tbuser u ON (pk.kode_karyawan = u.kode_karyawan))
  LEFT JOIN tbcabang tc ON (u.kode_cabang = tc.cabang_ref_kode))
  LEFT JOIN master_akun_closing_kasir ma ON (pk.kode_akun = ma.kode_akun))
  LEFT JOIN kasir_transactions_closing_kasir kt ON (pk.nomor_transaksi_closing = kt.kode_transaksi)
ORDER BY pk.tanggal DESC, pk.waktu DESC;

CREATE VIEW `view_pengeluaran_kasir_closing_kasir` AS
SELECT p.id, p.kode_transaksi, k.nama_cabang, p.kategori AS kategori_akun, p.tanggal, p.waktu, p.kode_akun,
  m.arti AS nama_akun, m.jenis_akun, p.jumlah, p.umur_pakai, p.keterangan_transaksi AS keterangan_akun,
  p.kode_karyawan, 'kasir' AS jenis_sumber, p.tanggal AS tanggal_transaksi, CONCAT(p.tanggal, ' ', p.waktu) AS datetime_input
FROM ((pengeluaran_kasir_closing_kasir p
  JOIN kasir_transactions_closing_kasir k ON (p.kode_transaksi = k.kode_transaksi))
  LEFT JOIN master_akun_closing_kasir m ON (p.kode_akun = m.kode_akun));

CREATE VIEW `view_pengeluaran_pusat_closing_kasir` AS
SELECT pp.id, pp.kode_transaksi, pp.cabang AS nama_cabang, pp.kategori AS kategori_akun, pp.tanggal, pp.waktu,
  pp.kode_akun, ma.arti AS nama_akun, ma.jenis_akun, pp.jumlah, pp.umur_pakai, pp.keterangan AS keterangan_akun,
  pp.kode_karyawan, 'pusat' AS jenis_sumber, pp.tanggal AS tanggal_transaksi, CONCAT(pp.tanggal, ' ', pp.waktu) AS datetime_input
FROM (pengeluaran_pusat_closing_kasir pp LEFT JOIN master_akun_closing_kasir ma ON (pp.kode_akun = ma.kode_akun));

CREATE VIEW `view_setoran_with_closing_closing_kasir` AS
SELECT kasir_transactions_closing_kasir.kode_transaksi, kasir_transactions_closing_kasir.tanggal_transaksi,
  kasir_transactions_closing_kasir.setoran_real, kasir_transactions_closing_kasir.kode_karyawan,
  kasir_transactions_closing_kasir.nama_cabang,
  (CASE WHEN (kasir_transactions_closing_kasir.deposit_status IS NULL OR kasir_transactions_closing_kasir.deposit_status = '')
    THEN 'Belum Disetor' ELSE kasir_transactions_closing_kasir.deposit_status END) AS deposit_status,
  kasir_transactions_closing_kasir.bukti_transaksi, 'NORMAL' AS jenis_setoran,
  kasir_transactions_closing_kasir.setoran_real AS jumlah_setoran_final
FROM kasir_transactions_closing_kasir
WHERE (kasir_transactions_closing_kasir.status = 'end proses')
UNION ALL
SELECT pk.nomor_transaksi_closing AS kode_transaksi, kt_closing.tanggal_transaksi, kt_closing.setoran_real,
  kt_pembuat.kode_karyawan, kt_closing.nama_cabang,
  (CASE WHEN (kt_closing.deposit_status IS NULL OR kt_closing.deposit_status = '')
    THEN 'Belum Disetor' ELSE kt_closing.deposit_status END) AS deposit_status,
  pk.id AS bukti_transaksi, 'DARI CLOSING' AS jenis_setoran, (pk.jumlah * -1) AS jumlah_setoran_final
FROM ((pemasukan_kasir_closing_kasir pk
  JOIN kasir_transactions_closing_kasir kt_pembuat ON (pk.kode_transaksi = kt_pembuat.kode_transaksi))
  JOIN kasir_transactions_closing_kasir kt_closing ON (pk.nomor_transaksi_closing = kt_closing.kode_transaksi))
WHERE (kt_pembuat.status = 'end proses' AND kt_closing.status = 'end proses' AND pk.nomor_transaksi_closing IS NOT NULL);
