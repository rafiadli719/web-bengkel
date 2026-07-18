-- F2-B: tblservis_barang sudah punya qty_retur (dipakai fitur lain sebelumnya),
-- tblservis_jasa belum. Jasa tidak punya kolom quantity (selalu 1 baris = 1x
-- jasa dikerjakan), jadi qty_retur di sini cuma bernilai 0/1 (belum/sudah diretur).
ALTER TABLE tblservis_jasa
  ADD COLUMN qty_retur INT NOT NULL DEFAULT 0;
