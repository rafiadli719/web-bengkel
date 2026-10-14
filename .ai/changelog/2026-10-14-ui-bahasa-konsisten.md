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

## Lanjutan (sore)
- 539 string dinamis (alert/pesan error/option) diindonesiakan di 310 file: "Error:"→"Kesalahan:", "AJAX Error"→"Kesalahan AJAX",
  "Error loading data"→"Gagal memuat data", "DB connection failed"→"Koneksi database gagal", dst. Library (chartjs/dompdf) dan
  skrip CLI tidak disentuh. Tidak ada kode yang membandingkan string lama (di-grep).
- Sidebar admin/keuangan Keuangan Kasir (`includes/sidebar.css`) diseragamkan dengan tema ACE: latar terang #f2f2f2, brand
  #438eb9, item aktif biru. Tautan CSS diberi `?v=20261014` karena browser menyimpan salinan lama.
- Tidak disentuh: halaman kasir harian (closing, kas_awal, pemasukan, index_kasir, dst) punya desain inline sendiri.
