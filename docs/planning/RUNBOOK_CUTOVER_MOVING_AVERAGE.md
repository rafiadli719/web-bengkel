# Runbook Cutover Moving Average (F5-06, F5-07, F5-10)

Urutan wajib. Setiap tahap punya syarat lanjut dan cara mundur. Dikerjakan **satu cabang per giliran**; urutan cabang: PST, lalu cabang kecil, terakhir cabang besar (D-12).
Yang dikerjakan IT/Rafi ditandai (Rafi). Hal yang sudah dibangun di kode dan dites ada di `.ai/changelog/2026-10-08-moving-average.md`.

## Tahap A — Siapkan produksi (sekali)
1. (Rafi) Backup database produksi penuh.
2. (Rafi) Unggah kode terbaru, lalu jalankan runner berurutan:
   `db/migrations/2026-10-08_run_inv_core.php up`, `..._run_inv_koreksi.php up`, `..._run_inv_permissions.php up`.
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
**Sudah ada dan lulus E2E browser (PST, 2026-10-08), hanya alur Penjualan POS (`penjualan_add_rst.php`, `proses-add-detail/penjualan.php`):**
stok yang berlaku saat tambah item dan saat simpan = saldo ledger baru; tanggal dipaksa hari ini; transaksi dicatat ke ledger dengan HPP. Guard sync Access: tidak menimpa saldo cabang live.

**Masih harus dibangun dan dites E2E per alur sebelum cabang pilot boleh `live`** (mode `live` di halaman Pengaturan sengaja belum ditawarkan; setel lewat `InventoryService::setMode` hanya untuk uji):
- Penegakan BR-05 + larang backdate di alur lain: servis bayar (6 titik), pembelian, retur, transfer antar cabang, stok manual, opname.
- Atomisitas: posting ledger kini berjalan setelah penulisan lama di koneksi terpisah. Race dua kasir pada stok terakhir dicegah ledger (tidak pernah minus) tetapi `tbstok` lama bisa berbeda; rekonsiliasi harian menandainya. Untuk atomik penuh, alur harus dipindah ke satu transaksi.
- Isi `harga_pokok` snapshot di `tblpenjualan_detail` dan `tblservis_barang` (kolom baru, migrasi terpisah) agar laporan laba memakai HPP Moving Average.
- `tbstok` lama: opening stock hanya masuk ledger baru. Pembaca lama (`view_stok_master`, kartu stok lama, MinMax) masih membaca `tbstok` yang saldonya historis negatif; normalisasi/proyeksi `tbstok` perlu diputuskan saat cutover.
- Larang edit/hapus transaksi posted di semua endpoint (F3-07) dan arahkan ke Koreksi.
- Perbaiki `servis-reguler-byr.php` (guard potong ganda) dan guard servis per cabang (temuan F0-08).

## Tahap E — Pengamatan dan cabang berikutnya
1. 1-2 minggu pengamatan di pilot: rekonsiliasi harian, antrian koreksi, keluhan stok ditolak.
2. Lanjut ke cabang berikutnya hanya bila pilot bersih. PST (Gudang Pusat) dengan perhatian khusus transfer.
3. Setelah semua cabang live dan stabil 30 hari: tutup shadow, arsipkan jalur tulis lama (F5-10).

## Keputusan yang masih terbuka
- D-07: kode akun COA. Isi lewat `InventoryJournal::setAkun` (atau langsung di `inv_config`, kunci `acct_*`) lalu jalankan `scripts/inv_harian.php --jurnal`; jurnal tertunda terbentuk otomatis.
- Konfirmasi (OWNER): arti kode lokasi `001` di tbstok lama, jadwal pilot, penyetuju per cabang.
