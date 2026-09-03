-- Task 3: DDL tabel transaksional *_closing_kasir (21 tabel)
-- Sumber: fitmotor_maintance-beta. Rename + FK + charset latin1 default
-- (exception kode_cabang & kode_karyawan -> utf8mb4/utf8mb4_general_ci, match
-- kolom target FK tbcabang.cabang_ref_kode & tbuser.kode_karyawan — sama alasan Task 2).
--
-- PREREQUISITE (dijalankan 2026-09-03, sebelum file ini):
-- tbuser.kode_karyawan gak punya index -> FK gak bisa dibikin. Verified 0 duplikat
-- non-kosong dulu:
--   ALTER TABLE tbuser ADD UNIQUE KEY unique_kode_karyawan (kode_karyawan);
--
-- Orphan data (kode_karyawan/kode_cabang/kode_akun yang gak match) SENGAJA gak
-- divalidasi di sini — semua tabel di bawah dibuat KOSONG, FK cuma dicek pas
-- INSERT di Task 4-7. Kalau ada orphan, migrasi data bakal gagal di situ (fail-fast
-- by design, bukan bug DDL).

CREATE TABLE `kasir_transactions_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(255) DEFAULT NULL,
  `kas_awal` decimal(15,2) DEFAULT NULL,
  `kas_akhir` decimal(15,2) DEFAULT NULL,
  `total_pemasukan` decimal(15,2) DEFAULT '0.00',
  `total_pengeluaran` decimal(15,2) DEFAULT '0.00',
  `total_penjualan` decimal(15,2) DEFAULT '0.00',
  `total_servis` decimal(15,2) DEFAULT '0.00',
  `status` enum('on proses','end proses','dibatalkan') DEFAULT 'on proses',
  `tanggal_transaksi` date DEFAULT NULL,
  `tanggal_closing` date DEFAULT NULL,
  `jam_closing` time DEFAULT NULL,
  `setoran_real` decimal(15,2) DEFAULT '0.00',
  `omset` decimal(15,2) DEFAULT '0.00',
  `data_setoran` decimal(15,2) DEFAULT '0.00',
  `selisih_setoran` decimal(15,2) DEFAULT '0.00',
  `kode_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kasir_asal` varchar(12) DEFAULT NULL COMMENT 'Kode karyawan kasir pembuat transaksi asli',
  `kode_cabang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_cabang` varchar(100) DEFAULT NULL,
  `deposit_status` enum('Belum Disetor','Sudah Disetor ke Keuangan','Sedang Dibawa Kurir','Diterima Staff Keuangan','Validasi Keuangan OK','Validasi Keuangan SELISIH','Dikembalikan ke CS','Sudah Disetor ke Bank','Diserahterimakan','Pending Serah Terima') DEFAULT 'Belum Disetor' COMMENT 'Status deposit termasuk Dikembalikan ke CS',
  `deposit_difference_status` enum('Sesuai','Selisih','Belum Diverifikasi','Sudah Diverifikasi') DEFAULT 'Belum Diverifikasi',
  `jenis_closing` enum('closing','dipinjam','meminjam') DEFAULT NULL COMMENT 'Jenis transaksi dalam closing',
  `closing_group_id` int DEFAULT NULL COMMENT 'ID grup closing transaction',
  `is_part_of_closing` tinyint(1) DEFAULT '0' COMMENT 'Flag apakah transaksi ini bagian dari closing',
  `jenis_setoran_id` int DEFAULT NULL COMMENT 'ID jenis setoran',
  `kode_setoran` varchar(50) DEFAULT NULL,
  `jumlah_diterima_fisik` decimal(15,2) DEFAULT NULL,
  `selisih_fisik` decimal(15,2) GENERATED ALWAYS AS ((`data_setoran` - ifnull(`jumlah_diterima_fisik`,0))) STORED,
  `catatan_validasi` text,
  `rekening_tujuan_id` int DEFAULT NULL,
  `validasi_at` timestamp NULL DEFAULT NULL,
  `validasi_by` varchar(50) DEFAULT NULL,
  `bukti_transaksi` varchar(50) DEFAULT NULL COMMENT 'Nomor transaksi pemasukan dari closing yang terkait',
  `revision_parent_kode` varchar(255) DEFAULT NULL,
  `revision_child_kode` varchar(255) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` varchar(255) DEFAULT NULL,
  `cancel_reason` text,
  PRIMARY KEY (`id`),
  KEY `kode_transaksi` (`kode_transaksi`),
  KEY `fk_kode_karyawan` (`kode_karyawan`),
  KEY `fk_nama_cabang` (`nama_cabang`),
  KEY `idx_rekening_tujuan` (`rekening_tujuan_id`),
  KEY `idx_deposit_status` (`deposit_status`),
  KEY `idx_kode_setoran` (`kode_setoran`),
  KEY `idx_closing_group_id` (`closing_group_id`),
  KEY `idx_kasir_asal` (`kasir_asal`),
  CONSTRAINT `fk_kt_ck_rekening_tujuan` FOREIGN KEY (`rekening_tujuan_id`) REFERENCES `master_rekening_cabang_closing_kasir` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_kt_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`),
  CONSTRAINT `fk_kt_ck_cabang` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `closing_transaction_groups_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_code` varchar(50) NOT NULL COMMENT 'Kode unik grup closing',
  `nama_cabang` varchar(100) NOT NULL COMMENT 'Nama cabang',
  `kode_setoran` varchar(50) DEFAULT NULL COMMENT 'Kode setoran terkait',
  `tanggal_closing` date NOT NULL COMMENT 'Tanggal closing',
  `total_closing` decimal(15,2) DEFAULT '0.00' COMMENT 'Total transaksi closing',
  `total_dipinjam` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang dipinjam',
  `total_meminjam` decimal(15,2) DEFAULT '0.00' COMMENT 'Total uang meminjam',
  `total_gabungan` decimal(15,2) DEFAULT '0.00' COMMENT 'Total gabungan semua transaksi',
  `jumlah_transaksi` int DEFAULT '0' COMMENT 'Jumlah transaksi dalam grup',
  `status_validasi` enum('pending','validated','rejected') DEFAULT 'pending' COMMENT 'Status validasi grup',
  `validated_by` varchar(50) DEFAULT NULL COMMENT 'Kode karyawan yang memvalidasi',
  `validated_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu validasi',
  `catatan_validasi` text COMMENT 'Catatan validasi',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_group_code` (`group_code`),
  KEY `idx_nama_cabang` (`nama_cabang`),
  KEY `idx_tanggal_closing` (`tanggal_closing`),
  KEY `idx_kode_setoran` (`kode_setoran`),
  KEY `idx_closing_group_status` (`status_validasi`,`tanggal_closing`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tabel untuk mengelompokkan transaksi closing';

CREATE TABLE `closing_transaction_details_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL COMMENT 'ID grup closing',
  `transaction_id` int NOT NULL COMMENT 'ID transaksi kasir',
  `jenis_dalam_closing` enum('closing','dipinjam','meminjam') NOT NULL COMMENT 'Jenis transaksi dalam closing',
  `nominal` decimal(15,2) NOT NULL COMMENT 'Nominal transaksi',
  `keterangan` text COMMENT 'Keterangan transaksi',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_jenis_dalam_closing` (`jenis_dalam_closing`),
  CONSTRAINT `fk_ctd_ck_group` FOREIGN KEY (`group_id`) REFERENCES `closing_transaction_groups_closing_kasir` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Detail transaksi dalam grup closing';

CREATE TABLE `closing_revision_requests_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi_lama` varchar(255) NOT NULL,
  `kode_transaksi_baru` varchar(255) DEFAULT NULL,
  `kode_cabang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode_pemohon` varchar(255) NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approver_kode` varchar(255) DEFAULT NULL,
  `approval_note` text,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_transaksi_lama` (`kode_transaksi_lama`),
  KEY `idx_status_created_at` (`status`,`created_at`),
  KEY `idx_kode_pemohon` (`kode_pemohon`),
  CONSTRAINT `fk_crr_ck_cabang` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `kas_awal_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `total_nilai` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time DEFAULT NULL,
  `status` enum('on proses','end proses') DEFAULT 'on proses',
  `kode_karyawan` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `idx_kode_karyawan` (`kode_karyawan`),
  CONSTRAINT `fk_ka_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `kas_akhir_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `total_nilai` decimal(15,2) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `kode_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kode_karyawan` (`kode_karyawan`),
  KEY `kode_transaksi` (`kode_transaksi`),
  CONSTRAINT `fk_kak_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `detail_kas_awal_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `nominal` int NOT NULL,
  `jumlah_keping` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kode_transaksi` (`kode_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `detail_kas_akhir_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `nominal` int NOT NULL,
  `jumlah_keping` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kode_transaksi` (`kode_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pemasukan_kasir_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `kode_akun` varchar(10) NOT NULL,
  `kode_pengambilan_ref` varchar(50) DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan_transaksi` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `kode_karyawan` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nomor_transaksi_closing` varchar(20) DEFAULT NULL COMMENT 'Referensi ke kode transaksi closing untuk transaksi DARI CLOSING',
  PRIMARY KEY (`id`),
  KEY `kode_karyawan` (`kode_karyawan`),
  KEY `kode_transaksi` (`kode_transaksi`),
  KEY `idx_tanggal_waktu` (`tanggal`,`waktu`),
  KEY `idx_kode_akun` (`kode_akun`),
  KEY `idx_pemasukan_kasir_nomor_closing` (`nomor_transaksi_closing`),
  KEY `idx_pemasukan_kasir_kode_pengambilan_ref` (`kode_pengambilan_ref`),
  CONSTRAINT `fk_pk_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`),
  CONSTRAINT `fk_pk_ck_akun` FOREIGN KEY (`kode_akun`) REFERENCES `master_akun_closing_kasir` (`kode_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pemasukan_pusat_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) DEFAULT NULL,
  `kode_karyawan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cabang` varchar(100) NOT NULL,
  `kode_akun` varchar(10) DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal input ke sistem',
  `tanggal_transaksi` date NOT NULL COMMENT 'Tanggal aktual terjadinya transaksi',
  `waktu` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_karyawan` (`kode_karyawan`),
  KEY `idx_cabang` (`cabang`),
  KEY `idx_kode_akun` (`kode_akun`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_tanggal_transaksi` (`tanggal_transaksi`),
  CONSTRAINT `fk_pp_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`),
  CONSTRAINT `fk_pp_ck_akun` FOREIGN KEY (`kode_akun`) REFERENCES `master_akun_closing_kasir` (`kode_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pengeluaran_kasir_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(20) NOT NULL,
  `kode_akun` varchar(10) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan_transaksi` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu` time NOT NULL,
  `kode_karyawan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `umur_pakai` int DEFAULT NULL,
  `kategori` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `kode_karyawan` (`kode_karyawan`),
  KEY `kode_transaksi` (`kode_transaksi`),
  KEY `idx_tanggal_waktu` (`tanggal`,`waktu`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_kode_akun` (`kode_akun`),
  CONSTRAINT `fk_pgk_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`),
  CONSTRAINT `fk_pgk_ck_akun` FOREIGN KEY (`kode_akun`) REFERENCES `master_akun_closing_kasir` (`kode_akun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pengeluaran_pusat_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) DEFAULT NULL,
  `kode_karyawan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `cabang` varchar(100) NOT NULL,
  `kode_akun` varchar(20) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text NOT NULL,
  `umur_pakai` int NOT NULL DEFAULT '0',
  `kategori` varchar(100) DEFAULT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal input ke sistem',
  `tanggal_transaksi` date NOT NULL COMMENT 'Tanggal aktual terjadinya transaksi',
  `waktu` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_karyawan` (`kode_karyawan`),
  KEY `idx_cabang` (`cabang`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_tanggal_transaksi_pengeluaran` (`tanggal_transaksi`),
  CONSTRAINT `fk_ppg_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `setoran_ke_bank_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_setoran` varchar(50) NOT NULL,
  `tanggal_setoran` date NOT NULL,
  `metode_setoran` enum('Tunai','Transfer') NOT NULL,
  `rekening_tujuan` varchar(100) NOT NULL,
  `total_setoran` decimal(15,2) NOT NULL,
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(50) NOT NULL,
  `tanggal_setor` date DEFAULT NULL COMMENT 'Tanggal setor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kode_setoran` (`kode_setoran`),
  KEY `idx_tanggal_setoran` (`tanggal_setoran`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_tanggal_setor` (`tanggal_setor`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `setoran_keuangan_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_setoran` varchar(50) NOT NULL,
  `kode_karyawan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kode_cabang` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `tanggal_setoran` date NOT NULL,
  `tanggal_closing` date DEFAULT NULL,
  `jumlah_setoran` decimal(15,2) NOT NULL,
  `nama_pengantar` varchar(100) NOT NULL,
  `status` enum('Sedang Dibawa Kurir','Diterima Staff Keuangan','Validasi Keuangan OK','Validasi Keuangan SELISIH','Ada yang Dikembalikan ke CS','Sudah Disetor ke Bank') DEFAULT 'Sedang Dibawa Kurir' COMMENT 'Status setoran termasuk Ada yang Dikembalikan ke CS',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(50) DEFAULT NULL,
  `kasir_asal_kode` varchar(12) DEFAULT NULL COMMENT 'Kode karyawan kasir asal (sebelum serah terima)',
  `kasir_asal_nama` varchar(100) DEFAULT NULL COMMENT 'Nama karyawan kasir asal (sebelum serah terima)',
  `jumlah_diterima` decimal(15,2) DEFAULT NULL,
  `selisih_setoran` decimal(15,2) DEFAULT NULL,
  `catatan_validasi` text,
  `total_selisih` decimal(15,2) DEFAULT NULL COMMENT 'Total selisih semua transaksi',
  `created_by` varchar(50) DEFAULT NULL COMMENT 'Dibuat oleh',
  `has_closing_transactions` tinyint(1) DEFAULT '0' COMMENT 'Setoran mengandung transaksi closing',
  `total_closing_groups` int DEFAULT '0' COMMENT 'Jumlah grup closing',
  `closing_summary` longtext COMMENT 'Ringkasan closing JSON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_kode_setoran` (`kode_setoran`),
  KEY `idx_kode_karyawan` (`kode_karyawan`),
  KEY `idx_tanggal_setoran` (`tanggal_setoran`),
  KEY `idx_setoran_keuangan_status` (`status`),
  KEY `idx_setoran_closing_flag` (`has_closing_transactions`),
  KEY `idx_kasir_asal_setoran` (`kasir_asal_kode`),
  CONSTRAINT `fk_sk_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`),
  CONSTRAINT `fk_sk_ck_cabang` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `setoran_ke_bank_detail_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setoran_ke_bank_id` int NOT NULL,
  `setoran_keuangan_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setoran_detail` (`setoran_ke_bank_id`,`setoran_keuangan_id`),
  KEY `idx_setoran_ke_bank_id` (`setoran_ke_bank_id`),
  KEY `idx_setoran_keuangan_id` (`setoran_keuangan_id`),
  CONSTRAINT `fk_skd_ck_bank` FOREIGN KEY (`setoran_ke_bank_id`) REFERENCES `setoran_ke_bank_closing_kasir` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_skd_ck_keuangan` FOREIGN KEY (`setoran_keuangan_id`) REFERENCES `setoran_keuangan_closing_kasir` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pengambilan_setoran_closing_kasir` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_pengambilan` varchar(50) NOT NULL,
  `parent_kode_pengambilan` varchar(50) DEFAULT NULL,
  `kode_karyawan_input` varchar(50) DEFAULT NULL,
  `kode_cabang_keuangan` varchar(20) DEFAULT NULL,
  `nama_cabang_keuangan` varchar(100) DEFAULT NULL,
  `nominal_diambil` decimal(15,2) NOT NULL DEFAULT '0.00',
  `nominal_sisa` decimal(15,2) NOT NULL DEFAULT '0.00',
  `id_setoran_bank` int unsigned DEFAULT NULL,
  `kode_setoran_bank` varchar(100) DEFAULT NULL,
  `jenis_rekening` enum('internal','eksternal') NOT NULL DEFAULT 'internal',
  `klasifikasi` enum('internal','hutang') NOT NULL DEFAULT 'internal',
  `no_rekening_peminjam` varchar(50) DEFAULT NULL,
  `no_rekening_penerima` varchar(50) DEFAULT NULL,
  `kode_cabang_penerima` varchar(20) DEFAULT NULL,
  `tanggal_perencanaan_setor` date DEFAULT NULL,
  `status` enum('proses','hutang','selesai') NOT NULL DEFAULT 'proses',
  `status_setor_bank` enum('belum','siap','disetor') NOT NULL DEFAULT 'belum',
  `tanggal_penyerahan_setor` date DEFAULT NULL,
  `tanggal_riil_setor_bank` date DEFAULT NULL,
  `setoran_ke_bank_id` int DEFAULT NULL,
  `verified_nominal_kas_masuk` tinyint(1) NOT NULL DEFAULT '0',
  `verified_notrx_kas_masuk` tinyint(1) NOT NULL DEFAULT '0',
  `verified_cabang_penerima_sudah_closing` tinyint(1) NOT NULL DEFAULT '0',
  `verified_cabang_closing_at` datetime DEFAULT NULL,
  `verified_mutasi_bank` tinyint(1) NOT NULL DEFAULT '0',
  `mutasi_dokumen_path` varchar(255) DEFAULT NULL,
  `mutasi_dokumen_nama_asli` varchar(255) DEFAULT NULL,
  `mutasi_dokumen_mime` varchar(120) DEFAULT NULL,
  `mutasi_dokumen_tipe` varchar(20) DEFAULT NULL,
  `mutasi_rekening_pengirim` varchar(50) DEFAULT NULL,
  `mutasi_rekening_penerima` varchar(50) DEFAULT NULL,
  `mutasi_nominal_terdeteksi` decimal(15,2) DEFAULT NULL,
  `mutasi_confidence` enum('high','medium','low') DEFAULT NULL,
  `mutasi_hasil_json` longtext,
  `mutasi_verified_by` varchar(50) DEFAULT NULL,
  `mutasi_verified_at` datetime DEFAULT NULL,
  `id_pemasukan_gudang` int unsigned DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_pengambilan` (`kode_pengambilan`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_parent` (`parent_kode_pengambilan`),
  KEY `idx_klasifikasi` (`klasifikasi`),
  KEY `idx_kode_cabang_penerima` (`kode_cabang_penerima`),
  KEY `idx_no_rekening_peminjam` (`no_rekening_peminjam`),
  KEY `idx_id_setoran_bank` (`id_setoran_bank`),
  KEY `idx_status_setor_bank` (`status_setor_bank`),
  CONSTRAINT `fk_ps_ck_setoran_bank` FOREIGN KEY (`setoran_ke_bank_id`) REFERENCES `setoran_ke_bank_closing_kasir` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pengambilan_setoran_edit_log_closing_kasir` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_pengambilan` varchar(50) NOT NULL,
  `edited_by_role` enum('keuangan','penerima') NOT NULL,
  `edited_by_kode_karyawan` varchar(50) NOT NULL,
  `field_changed` varchar(50) NOT NULL DEFAULT 'nominal_diambil',
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `pemasukan_kasir_id_invalidated` int unsigned DEFAULT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pengambilan_setoran_edit_log_kode` (`kode_pengambilan`),
  KEY `idx_pengambilan_setoran_edit_log_dibaca` (`dibaca`),
  CONSTRAINT `fk_psel_ck_pengambilan` FOREIGN KEY (`kode_pengambilan`) REFERENCES `pengambilan_setoran_closing_kasir` (`kode_pengambilan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `pengambilan_setoran_pembayaran_closing_kasir` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_pengambilan` varchar(50) NOT NULL,
  `pemasukan_kasir_id` int DEFAULT NULL,
  `nominal_dibayar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sumber` enum('ocr','manual') NOT NULL DEFAULT 'ocr',
  `no_transaksi` varchar(100) DEFAULT NULL,
  `tanggal_transfer` date DEFAULT NULL,
  `kode_validasi_input` varchar(50) DEFAULT NULL,
  `kode_karyawan_input` varchar(50) NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `dokumen_path` varchar(255) DEFAULT NULL,
  `dokumen_nama_asli` varchar(255) DEFAULT NULL,
  `dokumen_mime` varchar(120) DEFAULT NULL,
  `dokumen_tipe` varchar(20) DEFAULT NULL,
  `rekening_pengirim_terdeteksi` varchar(50) DEFAULT NULL,
  `rekening_penerima_terdeteksi` varchar(50) DEFAULT NULL,
  `nominal_terdeteksi` decimal(15,2) DEFAULT NULL,
  `confidence` enum('high','medium','low') DEFAULT NULL,
  `hasil_ekstraksi_json` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_pengambilan` (`kode_pengambilan`),
  KEY `idx_pemasukan_kasir_id` (`pemasukan_kasir_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_psp_ck_pengambilan` FOREIGN KEY (`kode_pengambilan`) REFERENCES `pengambilan_setoran_closing_kasir` (`kode_pengambilan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_psp_ck_pemasukan` FOREIGN KEY (`pemasukan_kasir_id`) REFERENCES `pemasukan_kasir_closing_kasir` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `serah_terima_kasir_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_serah_terima` varchar(50) NOT NULL,
  `kode_karyawan_pemberi` varchar(12) DEFAULT NULL,
  `kode_karyawan_penerima` varchar(12) DEFAULT NULL,
  `kode_cabang` varchar(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `kode_transaksi_asal` varchar(20) NOT NULL,
  `tanggal_serah_terima` datetime NOT NULL,
  `total_setoran` decimal(15,2) NOT NULL DEFAULT '0.00',
  `catatan` text,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_serah_terima` (`kode_serah_terima`),
  KEY `idx_kode_karyawan_pemberi` (`kode_karyawan_pemberi`),
  KEY `idx_kode_karyawan_penerima` (`kode_karyawan_penerima`),
  KEY `idx_kode_cabang` (`kode_cabang`),
  KEY `idx_kode_transaksi_asal` (`kode_transaksi_asal`),
  CONSTRAINT `fk_stk_ck_cabang` FOREIGN KEY (`kode_cabang`) REFERENCES `tbcabang` (`cabang_ref_kode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `konfirmasi_buka_transaksi_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) NOT NULL,
  `kode_karyawan_peminta` varchar(12) DEFAULT NULL,
  `nama_cabang` varchar(100) NOT NULL,
  `tanggal_permintaan` datetime NOT NULL,
  `tanggal_diproses` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `alasan_permintaan` text NOT NULL,
  `catatan_admin` text,
  `kode_karyawan_admin` varchar(12) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_transaksi` (`kode_transaksi`),
  KEY `idx_status` (`status`),
  KEY `idx_kode_karyawan_peminta` (`kode_karyawan_peminta`),
  KEY `idx_nama_cabang` (`nama_cabang`),
  KEY `idx_tanggal_permintaan` (`tanggal_permintaan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tabel untuk konfirmasi pembukaan transaksi';

CREATE TABLE `audit_log_closing_kasir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kode_karyawan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `action` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `old_data` text,
  `new_data` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_kode_karyawan` (`kode_karyawan`),
  KEY `idx_action` (`action`),
  KEY `idx_table_name` (`table_name`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_al_ck_karyawan` FOREIGN KEY (`kode_karyawan`) REFERENCES `tbuser` (`kode_karyawan`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
