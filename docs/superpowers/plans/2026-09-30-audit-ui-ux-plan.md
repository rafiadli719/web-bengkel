# Audit UI/UX dan Rencana Perbaikan — 2026-09-30

Status: **RENCANA, belum ada kode yang diubah.** Menunggu keputusan Rafi (lihat bagian 6).
Terkait: fix UI kecil yang sudah masuk di commit `258b5b7` (aset kasir 404, avatar Komplain).

## 1. Cakupan audit (jujur soal batasnya)

| Lapisan | Cakupan | Metode |
|---|---|---|
| Struktur otomatis | **135 halaman** menu (semua modul) + 6 halaman Komplain | GET tiap halaman sebagai user ADM, parse HTML: template, viewport, judul, breadcrumb, tabel, alert, format angka/tanggal, aset, waktu respons |
| Visual desktop 1366px | **9 halaman** yang mewakili tiap keluarga tampilan | Screenshot di browser |
| Visual mobile ~495px | **3 halaman** (servis, POS, setoran kasir) | Screenshot + ukur overflow |
| Belum diperiksa | ±120 halaman lain secara visual, cetak/struk/PDF, role selain ADM, state error/loading | Lihat bagian 7 |

Angka "89 tabel tanpa pembungkus responsif" dari scan otomatis **tidak dipakai**: pengukuran browser di halaman servis menunjukkan tabelnya sebenarnya berada di dalam pembungkus scroll, jadi scan itu salah baca.

## 2. Gambaran umum

- 120 dari 135 halaman memakai template ACE (navbar biru + sidebar gelap). Ini basis yang konsisten.
- 10 halaman Keuangan Kasir memakai desain sendiri (sidebar gelap lain, font Inter, kartu). 2 halaman kasir lain memakai Bootstrap 5 tanpa sidebar. 2 halaman adalah unduhan/fragmen.
- Modul Komplain sudah dipindah ke ACE (commit `f3c158a`).
- Tidak ada gambar tanpa `alt`, tidak ada teks warning PHP di UI, tidak ada `NaN`/`[object Object]`, tidak ada mojibake.
- Performa tampil umumnya baik (rata-rata per modul 40–470 ms).

## 3. Temuan, dikelompokkan

Prioritas: **P0** fungsi/pemakaian terganggu, **P1** inkonsistensi yang terlihat jelas oleh staf, **P2** kerapian.

### P0 — mengganggu pemakaian

**T1. Master Pelanggan: halaman pertama berisi baris kosong.** (`pelanggan.php`, 37.674 baris)
Urut "Nama Pelanggan" menaruh ribuan pelanggan tanpa nama di atas. Kode berisi nopol (`G 5990 CAF`), nama/alamat/kota/telepon kosong. Staf yang membuka halaman ini melihat tabel yang tampak rusak.
Usulan: urut nama dengan nama kosong di akhir, dan/atau filter bawaan "hanya yang punya nama". Butuh keputusan (bagian 6, K1).

**T2. Kolom dan label kosong memenuhi tabel.**
- `barang.php`: kolom Stok Min, Stok Max, Stok Akhir, Rak, Barcode kosong hampir semua baris. "Applicable Motors: Tidak ada data" diulang di tiap baris.
- `pelanggan.php`: label "No GPS" di hampir semua baris (hanya 4 dari 37 ribu punya GPS). Badge tier (Bronze) tumpang tindih dengan teks kategori.
Usulan: sembunyikan kolom yang kosong, ganti label "No GPS" dengan penanda hanya pada yang punya GPS, perbaiki badge.

**T3. Data uji dan data kotor tampil di UI.**
`[TEST-E2E] Budi Uji` muncul di daftar servis dan laporan penjualan. Ditambah data lama yang sudah dicatat: merek dobel (HONDA, YAMAHA, SUZUKI, KAWASAKI), kategori motor dobel, dropdown mekanik berisi `################` dan `-`, Work Order sampah (WO0003/4/6/7, kode WO0001 dobel).
Usulan: bersihkan sebelum go-live dengan skrip yang aman dan bisa di-rollback (butuh keputusan K3).

### P1 — inkonsistensi yang terlihat

**T4. Dua "aplikasi" berbeda: ACE vs Keuangan Kasir.**
Kasir: tidak ada navbar atas (tidak ada jalan kembali ke menu utama selain tautan "Kembali ke Dashboard"), 12 halaman tanpa breadcrumb, font berbeda, gelembung notifikasi merah menutupi elemen (tab "Aksi" di desktop, kartu di mobile), deretan tab meluap horizontal.
Halaman `kas_awal_config_crud.php`, `index_kasir.php`, `view_transaksi.php` tanpa sidebar kasir sama sekali.
Usulan bertahap di bagian 5. Butuh keputusan K2.

**T5. Judul tab browser sama semua.**
97 dari 135 halaman berjudul "FIT MOTOR". Staf yang membuka beberapa tab tidak bisa membedakannya, riwayat browser juga tidak informatif.
Usulan: satu titik perubahan di `lib/titel.php` yang menurunkan judul dari `menu_config.php` berdasarkan URL. Tidak perlu edit per halaman.

**T6. Campuran Inggris–Indonesia.**
- Tombol "Cancel" muncul di **120 halaman** (kemungkinan besar satu komponen bersama, jadi satu perbaikan).
- Contoh lain: "View Card Mode", "Applicable Motors", "Genuine Part / Aftermarket", tab POS "Sales Details / Payment Information", lencana "Sales Transaction", judul "Procurement Dashboard".
Usulan: buat glosarium istilah (K4), lalu ganti per komponen. Jangan terjemahkan istilah yang sudah dipakai staf sehari-hari (mis. "Work Order", "Sparepart") tanpa persetujuan.

**T7. Font serif di Procurement Dashboard.**
Seluruh halaman, termasuk sidebar, jatuh ke font serif bawaan browser (font yang dipanggil tidak termuat). Halaman lain memakai Open Sans. Perlu dicek apakah pola yang sama ada di halaman lain (belum diukur).

**T8. Nama cabang tidak seragam dalam satu tabel.**
`pengadaan_antarcab.php`: kolom "Dari" berisi `ADIWERNA` sedangkan "Ke" berisi `FIT MOTOR PACUL` / `FIT MOTOR ADIWERNA`. Sumbernya beda kolom nama; perlu satu fungsi format nama cabang.

### P2 — kerapian dan kualitas

**T9. Halaman berat.**
`desa.php` 1 MB HTML, `jasa-list.php` 590 KB, `monitoring_setoran.php` 576 KB. `item-motor-mapping.php` 303 baris tanpa paging. Waktu respons: `servis-carinopol.php` 2,3 s, `statistik-pelanggan.php` 1,4 s, `servis-carinopol-garansi.php` 1,3 s.

**T10. Kasir: gaya inline dan lebar tetap.**
`monitoring_setoran.php` punya 2.858 atribut `style=` dan 104 lebar tetap ≥700px; `setoran_keuangan.php` membawa blok `<style>` 40 KB. Sulit dirawat, dan penyebab tab meluap di layar kecil.

**T11. Formulir tanpa label yang memadai.**
`penjualan_mitra_add.php` (11 input, 5 label), `pengadaan_antarcab_push.php` (7 input, 3 label), `setoran_bank_rekap.php` (12 input, 3 label), `_komplain/master_kategori.php` (0 label).

**T12. Dialog bawaan browser.**
94 pemanggilan `alert()` di halaman. Selain terasa kasar, dialog ini membekukan pengujian otomatis dan kurang nyaman di HP. Halaman terbanyak: `pesanan_pembelian_upload.php` (13), `procurement_dashboard.php` (10), `penjualan_antarcab_upload.php` (8), `keuangan_pusat.php` (7).

**T13. Mobile.**
Di lebar <500px logo "FIT MOTOR" terpecah jadi dua baris di navbar; tab POS sempit. Tabel servis dan kasir sudah bisa di-scroll. Setoran kasir responsif dengan baik.

**T14. Menu yang mengarah ke halaman pengalihan (6).**
`member-loyalty-program.php`, `statistik-pelanggan.php`, `mekanik.php`, `master_kepala_mekanik.php`, `pengadaan_antarcab_add.php`, `lap_cancel_servis.php`. Menu aktif/sorotan sidebar bisa tidak cocok dengan halaman tujuan. Usulan: arahkan menu langsung ke tujuan akhir.

### Bukan murni UI, tapi terlihat di UI (perlu verifikasi data)

**T15.** Procurement Dashboard menampilkan `559` untuk "Item Urgent", "Perlu Order", dan "Total Item" sekaligus. Kemungkinan seluruh item terhitung stok nol/minus atau logika kartu sama. Perlu dicek ke query sebelum dianggap benar.

**T16.** Angka ≥6 digit tanpa pemisah ribuan di beberapa halaman (`pelanggan.php`, `servis-carinopol.php`, `cabang.php`, `mekanik_management.php`). Kemungkinan besar kode atau nomor telepon (wajar), belum dipastikan bukan nominal uang.

## 4. Yang sudah beres (tidak perlu dikerjakan lagi)

- Sidebar dan navbar Komplain, sidebar Keuangan Kasir, sidebar `closing_revisi_admin.php`.
- Aset kasir 404 (Bootstrap), avatar Komplain, aset 404 di Work Order/Jasa.
- Label menu 2 baris terpotong, validasi nopol, grid form Komplain.

## 5. Rencana kerja (bertahap, tiap fase bisa dihentikan)

Aturan tiap fase: screenshot sebelum/sesudah, jalankan skrip crawl regresi (hasil = tidak ada aset 404, tidak ada warning), commit kecil per temuan, tidak menyentuh logika bisnis.

**Fase 0 — Persiapan (setengah hari)**
1. Simpan skrip crawl struktur (`_aud.php`) ke `scripts/qa/ui-crawl.php` supaya bisa diulang sebagai uji regresi (bukan berkas sekali pakai).
2. Ambil screenshot dasar 25 halaman kunci (desktop + mobile) ke `.ai/testing/ui-baseline/`.
3. Lengkapi audit visual sisa halaman (bagian 7).

**Fase 1 — Perubahan satu titik, dampak luas (1 hari)**
- T5 judul tab dari `menu_config` lewat `lib/titel.php` (97 halaman sekaligus).
- T6 "Cancel" di komponen bersama (120 halaman sekaligus).
- T14 menu langsung ke tujuan akhir.
- T13 logo navbar tidak terpecah di layar sempit.
Risiko rendah, verifikasi lewat crawl.

**Fase 2 — Kerapian daftar (1–2 hari)**
- T1 urutan/filter Master Pelanggan (setelah K1).
- T2 kolom kosong dan label "No GPS", badge tier.
- T7 font Procurement Dashboard, lalu ukur halaman lain dengan pola sama.
- T8 fungsi format nama cabang.

**Fase 3 — Keuangan Kasir (2–4 hari, tergantung K2)**
- Minimum: navbar/tautan kembali seragam, breadcrumb, gelembung notifikasi dipindah supaya tidak menutupi, tab tidak meluap.
- Opsional besar: port ke template ACE (13 halaman, `setoran_keuangan.php` >3.000 baris). Tidak disarankan sekarang.
- T10 rapikan gaya inline `monitoring_setoran.php` bersamaan dengan paging (T9).

**Fase 4 — Performa dan aksesibilitas (2 hari)**
- T9 paging `desa.php`, `jasa-list.php`, `item-motor-mapping.php`, dan tuning `servis-carinopol*.php`, `statistik-pelanggan.php`.
- T11 label formulir.
- T12 ganti `alert()` dengan modal untuk 4 halaman terbanyak dulu.

**Fase 5 — Kebersihan data (setelah K3)**
- T3 bersihkan data uji dan data kotor lewat skrip yang bisa di-rollback, catat di `.ai/decisions/`.

Estimasi total ±8–12 hari kerja bila semua dikerjakan; Fase 0–2 sudah memberi perbaikan paling terasa (±3–4 hari).

## 6. Keputusan yang dibutuhkan dari Rafi

- **K1.** Master Pelanggan: urut nama dengan nama kosong di akhir saja, atau filter bawaan "hanya yang punya nama"? Pelanggan tanpa nama itu data hasil migrasi atau memang belum dilengkapi?
- **K2.** Keuangan Kasir: dibiarkan sebagai portal tersendiri dengan tampilan seragam minimum (disarankan), atau dipindah penuh ke template ACE?
- **K3.** Boleh membersihkan data uji/duplikat (merek, kategori motor, WO sampah, dropdown mekanik)? Semua perlu cek FK dulu.
- **K4.** Istilah mana yang harus tetap bahasa Inggris (mis. "Work Order"), dan mana yang diganti ke Indonesia? Perlu daftar singkat dari Rafi atau staf.
- **K5.** Apakah staf memakai HP/tablet untuk halaman selain kasir? Kalau ya, audit mobile dinaikkan prioritasnya.

## 7. Yang belum diperiksa (usulan audit lanjutan)

1. Tinjauan visual ±120 halaman sisanya, terutama Data Master (40 halaman) dan Laporan (25 halaman, 48 memakai Highcharts dari CDN).
2. Cetak: struk, nota, PDF, cetak estimasi servis — hal yang dilihat pelanggan langsung.
3. Role selain ADM (Kasir, Kepala Cabang, Kepala Mekanik, Mekanik): menu dan halaman bisa tampil berbeda.
4. State khusus: pesan error validasi, loading, data kosong, dan hasil pencarian nol.
5. Ketergantungan CDN (Highcharts, jsdelivr, cdnjs, unpkg, Google): kalau internet cabang putus, grafik dan beberapa halaman kasir tidak tampil. Pertimbangkan menyalin aset ke lokal.
6. Aksesibilitas dasar: kontras warna, navigasi keyboard, ukuran target sentuh di HP.
