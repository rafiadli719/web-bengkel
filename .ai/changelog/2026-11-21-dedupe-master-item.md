# 2026-11-21 — Dedupe master item + UNIQUE `tblitem.noitem` + uji halaman (lokal)

## Added
- `db/migrations/2026-11-21_run_master_item_dedupe.php [dry|up|down]`: reversible, backup `bak_20261121_<tabel>` per tabel terdampak.
  - Gabung varian huruf besar/kecil ke versi PST: `23233-k44-V00` (PER CVT H.BEAT ORISIN, HPP 33.000; PST = kode center `202601001`),
    `33651-K25-901` (nama/harga identik). Kode di semua tabel transaksi/stok ikut diubah.
  - Hapus item uji `aa` (x12), `10009`, `DMY01` beserta transaksi/stoknya (keputusan Rafi 2026-11-21): tbstok 14, tbitem_masuk_detail 10, tblitem_spart 5, tblitem 16.
  - `UNIQUE KEY uk_item_noitem (noitem)`. Catatan: `tblitem` tidak punya PK/`id`, jadi rencana lama "PK id + UNIQUE" tidak berlaku.
- Hasil lokal: `tblitem` 6.120 -> 6.102 baris, semua unik. Siklus `up -> down -> up` lulus.

## Fixed (selama pengujian migrasi)
- `down` awal gagal meng-INSERT balik karena UNIQUE masih aktif (baris kanonik sempat hilang). Sekarang `down` melepas UNIQUE dulu,
  dan `up` memulihkan baris kanonik dari backup bila hilang.

## Hasil uji halaman (login admin, cabang PST)
- `pesanan_pembelian.php` = redirect normal ke `pembelian_dari_po.php` (bukan error). `hutang.php` memang tidak ada di repo.
- 11 halaman hutang/piutang/retur terbuka tanpa error PHP.

## Deploy fm1 (2026-11-21, konfirmasi Rafi)
- fm1 (`/var/www/fitmotor`, commit `fa23106`) sudah punya semua migrasi November; hanya dedupe ini yang belum.
- Backup `~/backup_item_dedupe_20261121.sql` (77 MB, mysqldump 10 tabel) di home user fm1, plus tabel `bak_20261121_*` di DB.
- `up` dijalankan via stdin (file migrasi belum di-push/pull ke fm1): hapus item uji (masuk 10, spart 5, stok 14, tblitem 16),
  gabung varian, UNIQUE aktif. Verifikasi: `tblitem` 5.901 baris unik, sisa kode uji 0, kanonik utuh.
- Produksi shared hosting: BELUM (Rafi jalankan runner sendiri).

## Uji tulis Pusat (PST)
- Kas masuk via POST: 1 baris `tblkas_keluar_masuk`, `kd_cabang=PST`, nominal benar, tidak dobel. Data uji `KM26000000112` ("UJI TULIS PST ... (hapus)") masih di DB lokal.
- Pembelian, penjualan, servis di PST belum diuji.

## Known issues / keputusan menunggu
- Plan 2026-11-15 menyebut kode dobel "data uji": salah. 4 dari 5 dipakai transaksi; hanya `aa` yang bersih.
- UPDATE `tblpembelian_detail` memicu trigger alarm: `alarm_harga_beli` dapat baris tambahan (2 per kode). Tidak berbahaya, belum dibersihkan.
- Satuan beda 20 (bukan 42): 18 kode jasa (tidak berstok) + 2 sparepart (`5011987101074` PCS vs BTL, `BKHBFI` SET vs PCS) butuh review Rafi. Belum diubah.
- Belum dikerjakan: `pelanggan_alias`, `supplier_alias` (7 kode bentrok + 15 nama dobel perlu review manual), orphan service, karantina 137 grup.
- Belum diuji tulis: Pusat (PST), antar-cabang non-PST, jasa berpotongan retur servis.
- C (produksi): migrasi ini belum di fm1/produksi. Deploy fm1 tertahan di no_service Fase 3 (error SUPER MySQL).
