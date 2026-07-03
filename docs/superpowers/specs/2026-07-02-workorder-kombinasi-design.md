# Master Workorder: Kombinasi WO + Item Gratis Per-Baris

**Status:** Approved, implementing
**Date:** 2026-07-02

## Latar Belakang

WO (paket servis) saat ini bundel jasa + barang secara flat. Kebutuhan baru:
1. WO bisa mengombinasikan WO lain di dalamnya (mis. "Paket Lengkap" = "Paket Standar Matic" + "Servis CVT").
2. Item barang tertentu di dalam WO bisa dikunci harga jual 0 (gratis, karena sudah termasuk harga jasa paket) — ini per-item, bukan per-WO. Item lain di WO yang sama tetap harga normal.

## Data Model

`tbworkorderdetail`:
- Tipe existing: 1=jasa (atomic, snapshot harga — perilaku lama tidak berubah), 2=barang.
- Tipe baru **3 = Kombinasi WO**: `kode_barang` = kode_wo anak yang direferensikan. Row ini pointer saja, tanpa harga/total sendiri.
- Kolom baru `is_gratis` TINYINT(1) DEFAULT 0 — flag di baris tipe=2 (barang) yang harganya dikunci 0.

`tbservis_pending_items`:
- Kolom baru `is_gratis` TINYINT(1) DEFAULT 0 — dibawa dari tbworkorderdetail saat expand ke transaksi servis.

**Guard 1-level nesting:** WO anak yang direferensikan via tipe=3 tidak boleh punya row tipe=3 sendiri (validasi saat insert), dan tidak boleh reference dirinya sendiri.

## Alur Input (`workorder-input.php`)

- Section barang: tambah checkbox "Gratis (termasuk di harga jasa)" — dicentang → field harga dikunci 0, insert dengan `is_gratis=1`.
- Section baru "Kombinasi Work Order": cari & pilih WO lain (popup search pola sama `jasa-search-popup.php`), insert row tipe=3. Tolak jika target sudah punya tipe=3 sendiri atau target = WO ini sendiri.
- Total harga WO (`harga` di header) dihitung ulang saat simpan: jasa langsung + barang langsung (gratis dihitung 0) + total harga (cached) WO anak tipe=3.
- Tampilan detail WO: tabel baru "Kombinasi WO" di samping tabel Jasa/Barang existing, badge "GRATIS" pada baris barang `is_gratis=1`.

## Alur Expand ke Transaksi Servis

File terdampak: `servis-input-reguler.php`, `servis-input-reguler-jemput.php` (titik proses `kdwo` apply, sekitar baris ~1520 di servis-input-reguler.php dan bagian sepadan di jemput).

- Query ambil detail WO diubah: baris tipe=1/2 diproses seperti sekarang (bawa `is_gratis` kalau ada, default 0 untuk tipe=1). Baris tipe=3 di-expand: JOIN ke `tbworkorderdetail` milik WO anak, ambil baris tipe 1 & 2 miliknya, insert sebagai item terpisah ke `tbservis_pending_items` (bukan sebagai satu baris gabungan).
- Kalau `is_gratis=1`: paksa `harga_satuan=0`, `total=0` saat insert ke `tbservis_pending_items`, walau harga katalog barang saat itu bukan 0.
- Stok tetap dikurangi normal untuk barang gratis (item tetap terpakai/tercatat, cuma harga jual customer 0).

## Migration

File baru: `db/migrations/2026-07-02_workorder_kombinasi_gratis.sql`
- ALTER `tbworkorderdetail` ADD `is_gratis` TINYINT(1) NOT NULL DEFAULT 0
- ALTER `tbservis_pending_items` ADD `is_gratis` TINYINT(1) NOT NULL DEFAULT 0

## Scope eksplisit di luar spec ini

- Tampilan badge "GRATIS" di cetak invoice/struk servis — disesuaikan kalau template cetak butuh ubahan, tapi tidak dibuat halaman baru.
- Tidak ada UI approval tambahan (WO items sudah auto-approve per perubahan sebelumnya di servis-input-reguler.php).
