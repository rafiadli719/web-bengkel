-- lap_servis.php butuh filter status_servis (bayar/selesai = lunas, exclude
-- datang/diproses/cancel dari laporan pendapatan) — view_service belum expose
-- kolom ini sama sekali.
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
  s.status_servis,
  s.kd_cabang,
  s.status,
  COALESCE(p.namapelanggan, s.no_pelanggan) AS namapelanggan
FROM tblservice s
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan;
