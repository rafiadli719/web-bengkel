-- 2026-09-05: widen kode_transaksi varchar(20)->varchar(50) di 9 tabel
-- kasir - ketahuan lewat UAT E2E, akun fitmotor-native (nama_user > 3
-- huruf, misal 'admin'/'adm01'/'keu01') gagal total bikin transaksi kas
-- awal ("Data too long for column kode_transaksi"). Staff migrasi asli
-- aman (kode_user 3 huruf dari source). Operasi aditif murni, 0 data
-- berubah, dikonfirmasi eksplisit Rafi sebelum eksekusi.
ALTER TABLE data_penjualan_closing_kasir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE data_servis_closing_kasir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE detail_kas_akhir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE detail_kas_awal MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE kas_akhir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE kas_awal MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE pemasukan_kasir_closing_kasir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
ALTER TABLE pemasukan_pusat_closing_kasir MODIFY COLUMN kode_transaksi VARCHAR(50) DEFAULT NULL;
ALTER TABLE pengeluaran_kasir_closing_kasir MODIFY COLUMN kode_transaksi VARCHAR(50) NOT NULL;
