# 2026-10-14 — Sapuan bahasa & konsistensi UI

## Changed
- `app/menu_config.php`: 41 judul menu berbahasa Inggris diindonesiakan (Approve→Persetujuan, Upload→Unggah, Export→Ekspor,
  Setting→Pengaturan, Monitoring→Pemantauan, Service→Servis, Opening Stock→Stok Awal, Moving Average→Rata-rata Bergerak, dst).
  Istilah lapangan dipertahankan: Item, Work Order (dijelaskan), Fast Moves (dijelaskan), Sales, Dashboard, DO/PR.
- Modul `_inventory`: Qty→Jumlah, Average→Rata-rata, ledger→buku stok, Opening Stock→Stok Awal, shadow dijelaskan "bayangan".
- 224 file: teks antar-tag Print→Cetak, Qty→Jumlah, Reset→Atur Ulang, Export→Ekspor, Import→Impor, User→Pengguna,
  Input Data→Tambah Data, Pending→Menunggu, Approved→Disetujui, Error→Kesalahan, dll (299 penggantian, `<option>`/`<textarea>`
  dikecualikan agar nilai form tidak berubah).
- Keuangan Kasir: brand sidebar "Dashboard Admin"→"Keuangan Kasir", judul tab, "Role: Super_admin"→"Peran: Administrator"
  (helper `labelPeranKasir()`), "User:"→"Pengguna:".

## Belum (butuh keputusan)
- Layout Keuangan Kasir masih beda dari tema ACE (sidebar gelap sendiri, Bootstrap 5). Penyeragaman penuh = ±48 halaman.
- Teks Inggris di dalam string PHP/JS dinamis (alert, pesan error) belum disapu.
