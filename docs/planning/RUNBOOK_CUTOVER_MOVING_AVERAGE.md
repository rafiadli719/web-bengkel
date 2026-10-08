# Runbook Cutover Moving Average (F5-06, F5-07, F5-10)

Urutan wajib. Setiap tahap punya syarat lanjut dan cara mundur. Dikerjakan **satu cabang per giliran**; urutan cabang: PST, lalu cabang kecil, terakhir cabang besar (D-12).
Yang dikerjakan IT/Rafi ditandai (Rafi). Hal yang sudah dibangun di kode dan dites ada di `.ai/changelog/2026-10-08-moving-average.md`.

## Tahap A — Siapkan produksi (sekali)
1. (Rafi) Backup database produksi penuh.
2. (Rafi) Unggah kode terbaru, lalu jalankan runner berurutan:
   `db/migrations/2026-10-08_run_inv_core.php up`, `..._run_inv_koreksi.php up`, `..._run_inv_permissions.php up`, `..._run_inv_hpp_snapshot.php up`.
3. Syarat lanjut: halaman **Persediaan, Pengaturan & Rekonsiliasi** terbuka untuk ADM, semua cabang `off`, `php scripts/inv_harian.php` mengembalikan "sehat".
4. Mundur: `... up` bisa dibalik dengan `down` (permission) dan `down` untuk tabel (hanya bila kosong, atau argumen `force`). Backup `bak_20261008_tb_master_posisi` dibuat otomatis.

## Tahap B — Shadow (minimal 1-2 minggu per cabang pilot)
1. (Rafi) Aktifkan `shadow` untuk cabang pilot di halaman Pengaturan. Baseline tbstok tercatat otomatis.
2. Operasional berjalan seperti biasa. Alur lama tetap satu-satunya yang berlaku.
3. Harian: `php scripts/inv_harian.php`. Tinjau selisih shadow vs tbstok dan transaksi shadow ditolak.
   Selisih wajar sebelum opening stock: keluar ditolak karena ledger baru belum punya saldo.
4. Syarat lanjut: tidak ada selisih tak terjelaskan selama periode uji, tidak ada error shadow berulang.
5. Mundur: setel mode `off`. Jalur lama tidak pernah berubah.

## Tahap C — Opening stock (hari cutover)
1. (Rafi) Hentikan transaksi cabang sementara, selesaikan transaksi berjalan.
2. (Rafi) Opname fisik seluruh item cabang pada hari itu (D-03). Backup database.
3. Unduh template opening di **Opening Stock** (biaya awal sudah disarankan: pembelian terakhir, lalu acuan HPP Access, lalu harga pokok master). Isi `qty_fisik`, periksa dan lengkapi `unit_cost` yang masih kosong.
4. Unggah, perbaiki semua error validasi sampai bersih, periksa total nilai persediaan awal, lalu **Muat** (satu kali per lokasi).
5. Cek: **Valuasi Persediaan** sesuai total nilai yang dimuat; `php scripts/inv_harian.php` sehat.
6. Mundur sebelum Tahap D: hapus baris OPENING hanya lewat IT dengan backup (ledger append-only, jangan lewat UI).

## Tahap D — Live
**Sudah dibangun (hanya aktif bila cabang `live`; cabang `off/shadow` tidak berubah sama sekali):**
- Stok yang berlaku = saldo ledger baru (`inv_balance`), dicek saat tambah item dan/atau saat simpan: Penjualan POS (`penjualan_add_rst.php`, `penjualan_add.php`), Stok Keluar manual dan opname keluar (aslinya tidak ada cek stok sama sekali), Servis bayar di 6 titik (reguler, rst, jemput, jemput-rst, garansi, halaman bayar), Transfer kirim (`pengadaan_antarcab_proses/push`), Retur pembelian.
- Tanggal transaksi dipaksa hari ini (tanpa backdate, BR-06): penjualan, pembelian (4 varian), stok masuk/keluar, opname, penjualan/pembelian antar cabang.
- Snapshot HPP Moving Average ke `tblpenjualan_detail.harga_pokok` dan `tblservis_barang.harga_pokok` (kolom baru, migrasi `inv_hpp_snapshot`) hanya untuk cabang live.
- Guard sync Access: tidak menimpa saldo cabang live.
- Perbaikan bug produksi (berlaku semua cabang, aman): `servis-reguler-byr.php` kini punya guard potong ganda, dan guard lima jalur servis lain memperhitungkan cabang (sebelumnya servis cabang lain dengan nomor sama bisa melewati potong stok).
- E2E browser lulus (PST, 2026-10-08): Penjualan POS, Stok Keluar (6 > saldo 5 ditolak, 2 lolos), Pembelian (tanggal mundur dipaksa hari ini). Servis dan Transfer/Retur teruji di tingkat fungsi (CLI), belum di browser.

**Masih harus dikerjakan sebelum cabang pilot boleh `live` (mode `live` di halaman Pengaturan sengaja belum ditawarkan; setel lewat `InventoryService::setMode` hanya untuk uji):**
- E2E browser di cabang pilot untuk Servis bayar (semua varian), Transfer, Retur, dan Terima DO.
- Atomisitas (cabang live): SUDAH untuk Penjualan POS (`penjualan_add_rst.php`, transaksi eksplisit) dan, lewat `inv_live_tx_begin` + `inv_atomic_*` (komit otomatis di akhir request, rollback bila ledger menolak atau fatal error), untuk penjualan (`penjualan_add.php`, antar cabang + mitra), pembelian (3 file), stok keluar, opname masuk/keluar, servis halaman bayar dan garansi. Alur yang sudah punya transaksi sendiri (retur pembelian/penjualan/servis, transfer kirim/terima, terima DO) memposting ledger di transaksi yang sama (`inv_live_post_*`). Teruji dengan kode halaman asli lewat harness CLI untuk POS dan Pembelian (komit konsisten dan rollback total saat periode closed). Servis reguler/rst/jemput/jemput-rst juga atomik sampai potong stok selesai (komit eksplisit `inv_live_tx_commit()` sebelum blok WhatsApp/HTTP); ini BELUM teruji di halaman/harness, wajib E2E di cabang pilot sebelum live. Stok masuk (`stok_masuk_add`, `_rst`) atomik sampai stok lokal selesai lalu komit sebelum sinkronisasi Accurate (HTTP); `stok_keluar_add_rst` dan `do_from_po` memposting ledger di transaksi yang sudah ada (error ledger memicu rollback). Semua alur penulis stok kini memakai ledger di transaksi yang sama saat live. Teruji dengan kode halaman asli lewat harness CLI: POS, Pembelian, dan Retur Pembelian (komit konsisten dan rollback total termasuk `qty_retur` asal). Yang BELUM teruji di halaman/harness: servis x4, stok masuk/keluar rst, `do_from_po`, transfer, retur penjualan/servis, terima DO (hanya lint + tes helper); wajib E2E per alur di cabang pilot sebelum live.
- Laporan laba/komisi sudah membaca snapshot dengan fallback ke HPP master (`inv_servis_hpp_sql`): `lap_komisi_mekanik.php`, `_include_komisi_snapshot.php`, `issue_add.php`; `lap_profit_penjualan.php` sudah memakai `tblpenjualan_detail.harga_pokok`. Data sebelum cutover tidak berubah.
- `tbstok` lama: opening stock hanya masuk ledger baru. Pembaca lama (`view_stok_master`, kartu stok lama, MinMax) masih membaca `tbstok` yang saldonya historis negatif; normalisasi/proyeksi `tbstok` perlu diputuskan saat cutover.
- Larang edit/hapus transaksi posted (F3-07): endpoint hapus/edit item keranjang kini wajib `status_trx='0'` (pembelian, penjualan, stok masuk/keluar) dan hapus barang servis diblokir bila servis sudah bayar/selesai (berlaku semua cabang). Edit item servis (`servis_edit_item_byr/garansi/jemput`) juga ditolak bila servis sudah bayar/selesai. Hapus/edit item pesanan juga wajib `status_trx='0'`, dan hapus pembayaran hutang/piutang ditolak bila pembayaran sudah punya header (posted).

## Tahap E — Pengamatan dan cabang berikutnya
1. 1-2 minggu pengamatan di pilot: rekonsiliasi harian, antrian koreksi, keluhan stok ditolak.
2. Lanjut ke cabang berikutnya hanya bila pilot bersih. PST (Gudang Pusat) dengan perhatian khusus transfer.
3. Setelah semua cabang live dan stabil 30 hari: tutup shadow, arsipkan jalur tulis lama (F5-10).

## Keputusan yang masih terbuka
- D-07: kode akun COA. Isi lewat `InventoryJournal::setAkun` (atau langsung di `inv_config`, kunci `acct_*`) lalu jalankan `scripts/inv_harian.php --jurnal`; jurnal tertunda terbentuk otomatis.
- Konfirmasi (OWNER): arti kode lokasi `001` di tbstok lama, jadwal pilot, penyetuju per cabang.
