# 2026-10-12 — E2E browser Moving Average vs PRD

Detail: `docs/planning/HASIL_E2E_MOVING_AVERAGE_2026-10-12.md`.

## Added
- Kontrol Periode persediaan (open/closed) di `app/_inventory/admin.php` (BR-08); locked hanya IT.

## Fixed
- 11 halaman `*_edit_item.php` (pembelian, penjualan, pesanan x3, stok masuk/keluar, tarik pesanan, servis x3): `UPDATE` kini ikut guard status (`status_trx='0'` / servis belum bayar/selesai). Sebelumnya baris transaksi posted bisa ditulis ulang lewat URL (AC-04).
- `InventoryService::postSekali`: biaya Rp0 ditolak untuk `PEMBELIAN`, `DO_TERIMA`, `STOK_MASUK` (EC-03, V-05); sebelumnya menurunkan average.
- `pengadaan_antarcab_terima.php`: qty terima tidak boleh melebihi qty kirim; item dari detail kiriman, bukan POST.
- `servis-reguler-byr.php`: total jasa/waktu/barang NULL di-cast 0; servis tanpa jasa gagal update status di MySQL strict padahal stok terpotong.
- 13 endpoint `*_hapus_item.php`: wajib login.
- `scripts/inv_precutover.php`: periode yang belum diset tidak lagi dianggap GAGAL.

## Belum (planning)
- Barang dalam perjalanan belum tercatat di `inv_transit` (halaman transfer belum memanggil ship/receive/return).
- Proyeksi `OPENING`/`ADJ`/`SCRAP` ke `tbstok` (pembaca lama tidak melihat opening dan adjustment).
- Backlog kecil: keranjang menerima qty 0/negatif, ajuan koreksi qty 0, notifikasi Finance (V-13).

## Catatan
- Klaim runbook "F3-07 edit item wajib status_trx='0'" sebelumnya hanya benar untuk SELECT tampilan; kini diterapkan pada UPDATE.
- Contoh 5 PRD (average tujuan 10.148,15) salah hitung; yang benar 10.281,48.
