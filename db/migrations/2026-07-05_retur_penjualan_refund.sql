-- F: Retur Penjualan — pencatatan refund tunai per retur
-- Extend tblretur_penjualan_header (bukan bikin tabel baru — sudah ada mekanisme
-- retur+approval di tabel ini, tinggal tambah kolom refund).
-- cara_bayar_refund: metode pengembalian uang ke customer (Tunai/Transfer/dst)
-- status_refund: '0' = belum dikembalikan (default, saat retur baru dibuat/pending)
--                '1' = sudah dikembalikan (di-set otomatis saat retur di-approve)
-- tanggal_refund: kapan refund tercatat selesai (diisi bareng approve)

ALTER TABLE tblretur_penjualan_header
  ADD COLUMN cara_bayar_refund VARCHAR(20) NULL AFTER note,
  ADD COLUMN status_refund VARCHAR(1) NOT NULL DEFAULT '0' AFTER status_retur,
  ADD COLUMN tanggal_refund DATETIME NULL AFTER status_refund;
