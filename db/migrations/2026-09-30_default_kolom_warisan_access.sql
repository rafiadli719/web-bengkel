-- Kolom NOT NULL warisan skema Access tanpa DEFAULT membuat INSERT yang tidak
-- menyebut kolom itu gagal di MySQL strict (STRICT_TRANS_TABLES). Ditemukan
-- E2E 2026-09-30: scan seluruh INSERT di app/ lalu dibuktikan dengan INSERT
-- nyata (rollback). Kolom di bawah selalu bernilai 0/'' di data lama, jadi
-- default = konvensi data lama. ALTER COLUMN SET DEFAULT = perubahan metadata
-- instan (tanpa rebuild tabel, aman untuk tblservis_barang 360rb baris).
--
-- SENGAJA TIDAK termasuk: nobaris tabel servis (berurutan 1,2,3 di data lama,
-- diisi di kode), kolom kunci (no_service, no_transaksi, kd_cabang, user).

ALTER TABLE tblservis_barang ALTER COLUMN qty_retur SET DEFAULT 0;
ALTER TABLE tblservis_jasa ALTER COLUMN waktu SET DEFAULT 0;
ALTER TABLE tblorder_detail ALTER COLUMN nobaris SET DEFAULT 0, ALTER COLUMN qty_terima SET DEFAULT 0;
ALTER TABLE tblorderjual_detail ALTER COLUMN nobaris SET DEFAULT 0, ALTER COLUMN qty_terima SET DEFAULT 0,
  ALTER COLUMN harga_sp SET DEFAULT 0, ALTER COLUMN harga_pokok SET DEFAULT 0, ALTER COLUMN margin_jual SET DEFAULT 0;
ALTER TABLE tblpiutang_detail ALTER COLUMN nobaris SET DEFAULT 0, ALTER COLUMN jumlah_piutang SET DEFAULT 0,
  ALTER COLUMN jumlah_bayar SET DEFAULT 0, ALTER COLUMN keterangan SET DEFAULT '', ALTER COLUMN status SET DEFAULT '0';
ALTER TABLE tbitem_masuk_detail ALTER COLUMN keterangan SET DEFAULT '', ALTER COLUMN penyesuaian SET DEFAULT 0, ALTER COLUMN stok_sistem SET DEFAULT 0;
ALTER TABLE tbitem_keluar_detail ALTER COLUMN keterangan SET DEFAULT '', ALTER COLUMN penyesuaian SET DEFAULT 0, ALTER COLUMN stok_sistem SET DEFAULT 0;
ALTER TABLE tblitem_stok ALTER COLUMN rakbarang SET DEFAULT 0, ALTER COLUMN stok_awal SET DEFAULT 0;
ALTER TABLE tbworkorderdetail ALTER COLUMN satuan SET DEFAULT '', ALTER COLUMN status_diskon SET DEFAULT '0', ALTER COLUMN diskon SET DEFAULT 0;

-- Rollback (satu ALTER per tabel, DROP DEFAULT untuk tiap kolom di atas), contoh:
-- ALTER TABLE tblservis_barang ALTER COLUMN qty_retur DROP DEFAULT;
-- ALTER TABLE tblservis_jasa ALTER COLUMN waktu DROP DEFAULT;
-- ALTER TABLE tblorder_detail ALTER COLUMN nobaris DROP DEFAULT, ALTER COLUMN qty_terima DROP DEFAULT;
-- ALTER TABLE tblorderjual_detail ALTER COLUMN nobaris DROP DEFAULT, ALTER COLUMN qty_terima DROP DEFAULT, ALTER COLUMN harga_sp DROP DEFAULT, ALTER COLUMN harga_pokok DROP DEFAULT, ALTER COLUMN margin_jual DROP DEFAULT;
-- ALTER TABLE tblpiutang_detail ALTER COLUMN nobaris DROP DEFAULT, ALTER COLUMN jumlah_piutang DROP DEFAULT, ALTER COLUMN jumlah_bayar DROP DEFAULT, ALTER COLUMN keterangan DROP DEFAULT, ALTER COLUMN status DROP DEFAULT;
-- ALTER TABLE tbitem_masuk_detail ALTER COLUMN keterangan DROP DEFAULT, ALTER COLUMN penyesuaian DROP DEFAULT, ALTER COLUMN stok_sistem DROP DEFAULT;
-- ALTER TABLE tbitem_keluar_detail ALTER COLUMN keterangan DROP DEFAULT, ALTER COLUMN penyesuaian DROP DEFAULT, ALTER COLUMN stok_sistem DROP DEFAULT;
-- ALTER TABLE tblitem_stok ALTER COLUMN rakbarang DROP DEFAULT, ALTER COLUMN stok_awal DROP DEFAULT;
-- ALTER TABLE tbworkorderdetail ALTER COLUMN satuan DROP DEFAULT, ALTER COLUMN status_diskon DROP DEFAULT, ALTER COLUMN diskon DROP DEFAULT;
