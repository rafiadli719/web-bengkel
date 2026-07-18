-- F2-B: Retur Servis Lunas — tabel baru (bukan extend tb_log_cancel_servis,
-- karena tabel itu sudah dipakai flow CANCEL PRA-BAYAR dan statistik pelanggan
-- COUNT(*) semua baris tanpa pembeda jenis — nyampur retur pasca-bayar ke situ
-- akan merusak statistik "customer sering cancel"). Niru pola
-- tblretur_penjualan_header/tblretur_penjualan_detail yang sudah jalan.

CREATE TABLE tblretur_servis_header (
  noretur VARCHAR(50) NOT NULL,
  no_service VARCHAR(50) NOT NULL,
  tanggal VARCHAR(50) NOT NULL,
  note VARCHAR(100) NOT NULL DEFAULT '',
  cara_bayar_refund VARCHAR(20) DEFAULT NULL,
  total_qty_retur INT NOT NULL DEFAULT 0,
  total_retur DOUBLE NOT NULL DEFAULT 0,
  user VARCHAR(50) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  status_retur VARCHAR(1) NOT NULL DEFAULT '0',
  status_refund VARCHAR(1) NOT NULL DEFAULT '0',
  tanggal_refund DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE tblretur_servis_detail (
  no_retur VARCHAR(50) NOT NULL,
  no_service VARCHAR(50) NOT NULL,
  tipe_item ENUM('barang','jasa') NOT NULL,
  no_item VARCHAR(50) NOT NULL,
  quantity INT NOT NULL,
  harga DOUBLE NOT NULL,
  total DOUBLE NOT NULL,
  alasan_retur VARCHAR(100) NOT NULL,
  user VARCHAR(50) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  id INT NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- tblservice.total_retur — analog tblpenjualan_header.total_retur, dipakai
-- lap_servis.php buat netting pendapatan (total_akhir - total_retur).
ALTER TABLE tblservice
  ADD COLUMN total_retur DECIMAL(15,2) NOT NULL DEFAULT 0;

-- view_service belum expose total_akhir/total_retur sama sekali — lap_servis.php
-- butuh keduanya buat kolom Retur + Total Akhir (Net).
DROP VIEW IF EXISTS view_service;
CREATE VIEW view_service AS
SELECT
  s.no_service,
  s.tanggal,
  DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_trx,
  s.jam,
  s.no_pelanggan,
  s.no_polisi,
  s.total_grand,
  s.total_akhir,
  s.total_retur,
  s.kd_cabang,
  s.status,
  COALESCE(p.namapelanggan, s.no_pelanggan) AS namapelanggan
FROM tblservice s
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan;
