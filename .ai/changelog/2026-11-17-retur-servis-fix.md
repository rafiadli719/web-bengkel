# 2026-11-17 — Uji retur servis PACUL/CIKDITIRO/TRAYEMAN + 3 perbaikan + UNIQUE noretur (lokal)

## Added
- `db/migrations/2026-11-17_run_unique_retur_servis.php [dry|up|down]`: UNIQUE `uk_rs_h_noretur (noretur)` di `tblretur_servis_header`
  (tabel itu tanpa PK/indeks). Gagal aman bila ada nomor dobel; idempotent; reversible. Lokal: SUDAH up. fm1 dan produksi: BELUM.

## Fixed
- `app/function_retur.php` `OtomatisIDReturServis`: nomor retur dari `MAX` (dulu `COUNT(*)`). `retur_servis_batal.php` menghapus baris,
  jadi setelah membatalkan retur non-terakhir nomor baru bisa sama dengan nomor yang ada (terbukti: `RS26000000008` dipakai dua servis;
  batalnya merusak `qty_retur` servis lain). Hanya nomor berformat `RS` + 11 digit yang dihitung (data `RET-UATTEST*` diabaikan).
- `app/save_retur_servis.php`: harga refund dihitung server (`HargaNetItemReturServis`), bukan dari POST `item_harga[]`
  (terbukti harga 58.000 diterima untuk barang Rp22.000). Total header dihitung dari detail yang tersimpan (qty sudah dibatasi sisa);
  retur tanpa item ditolak.
- `app/ajax_get_items_retur_servis.php`: harga yang ditampilkan = harga bersih yang dibayar (jasa: `total`; barang: `total/quantity`),
  dulu harga katalog (jasa Rp40.000 padahal dibayar Rp36.000 setelah potongan). Query barang/jasa kini ber-`kd_cabang`
  (`no_service` tidak unik antar cabang).

## Hasil uji (DB lokal, akun `e2e_test_cikditiro`, curl + cek DB)
- Simpan + approve retur servis lulus di PACUL (`RS26000000005`), CIKDITIRO (`RS26000000004`), TRAYEMAN (`RS26000000006`):
  status 1/1, stok tipe 9 masuk, `qty_retur` dan `tblservice.total_retur` benar.
- Approve ulang tidak menggandakan stok. Sesi PACUL ditolak untuk approve/batal/detail retur milik CIKDITIRO.
- Batal retur mengembalikan `qty_retur`. Setelah perbaikan: nomor tidak dobel, harga manipulasi (99.999) tersimpan sebagai harga bersih.
- Migrasi UNIQUE: dry, up, up ulang, down, up semua sesuai; simpan dan batal retur tetap lulus dengan indeks aktif.
- Data uji tersisa: 3 retur approved (`RS…04/05/06`) + stok tipe 9. Draft uji lain dibatalkan; `SV26000103587` dikoreksi
  (qty_retur 1, total_retur 98.000) setelah dirusak bug nomor dobel.

## Known issues
- Kasus jasa berpotongan (rumus baru) belum dijalankan ulang end-to-end; servis ujinya sudah terlanjur diretur.
- Servis uji PACUL/TRAYEMAN (`SV26000103585/586`) berisi kode item rusak, harga 0 (sisa uji lama).
- Perbaikan dan migrasi ini belum ada di fm1 (deploy tertahan di no_service Fase 3, error SUPER MySQL) dan belum di produksi.
