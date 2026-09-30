# Audit UI/UX dan Rencana Perbaikan — 2026-09-30

Status: **SEBAGIAN SUDAH DIKERJAKAN dan DIVALIDASI** (lihat bagian 13). Yang tersisa menunggu keputusan Rafi (bagian 6 dan 9).
Revisi 2 (audit lanjutan): cakupan diperluas ke 152 halaman menu, 24 halaman cetak/struk, dan hak akses per posisi. Temuan baru ada di bagian 8, keputusan tambahan di bagian 9.
Terkait: fix UI kecil yang sudah masuk di commit `258b5b7` (aset kasir 404, avatar Komplain).

## 1. Cakupan audit (jujur soal batasnya)

| Lapisan | Cakupan | Metode |
|---|---|---|
| Struktur otomatis | **135 halaman** menu (semua modul) + 6 halaman Komplain | GET tiap halaman sebagai user ADM, parse HTML: template, viewport, judul, breadcrumb, tabel, alert, format angka/tanggal, aset, waktu respons |
| Visual desktop 1366px | **±20 halaman** (mewakili tiap keluarga tampilan + semua yang terindikasi janggal) | Screenshot di browser |
| Metrik browser (revisi 2) | **±126 halaman menu** | Diukur di dalam browser: overflow horizontal, kolom kosong, teks terpotong, data uji, breadcrumb, font |
| Cetak/struk (revisi 2) | **24 halaman cetak**, 5 PDF dirender ke gambar dan dilihat | GET dengan ID nyata, render PDF |
| Hak akses (revisi 2) | 11 posisi x 136 menu | Baca `tb_master_posisi` vs `menu_config.php` |
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
110 dari 152 halaman berjudul "FIT MOTOR"; sisanya memakai 4 pola berbeda ("FIT MOTOR - X" 8, "X - FIT MOTOR" 5, "X - Web Bengkel/Bengkel System" 3, lain-lain 12). Staf yang membuka beberapa tab tidak bisa membedakannya, riwayat browser juga tidak informatif.
Usulan: satu titik perubahan di `lib/titel.php` yang menurunkan judul dari `menu_config.php` berdasarkan URL. Tidak perlu edit per halaman.

**T6. Campuran Inggris–Indonesia.**
- ~~Tombol "Cancel" muncul di 120 halaman~~ **DIKOREKSI: false alarm.** Kata itu hanya bagian dari label menu "Laporan Cancel Service" yang tampil di sidebar semua halaman, bukan tombol berbahasa Inggris.
- Contoh lain: "View Card Mode", "Applicable Motors", "Genuine Part / Aftermarket", tab POS "Sales Details / Payment Information", lencana "Sales Transaction", judul "Procurement Dashboard".
Usulan: buat glosarium istilah (K4), lalu ganti per komponen. Jangan terjemahkan istilah yang sudah dipakai staf sehari-hari (mis. "Work Order", "Sparepart") tanpa persetujuan.

**T7. Font serif di 20 halaman (akar masalah sudah ditemukan).**
`body{font-family:"Open Sans"}` tanpa cadangan `sans-serif`, dan 20 halaman tidak memuat `assets/css/fonts.googleapis.com.css`, sehingga jatuh ke font serif bawaan browser (sidebar ikut serif). Daftar: `issue_add`, `customer_merge_approve`, `admin_deteksi_pelanggan_dobel`, `kendaraan_pindah_tangan`, `kendaraan_pindah_tangan_approve`, `workorder-motor-mapping`, `jasa-motor-mapping`, `item-motor-mapping`, `master-fastmoves`, `master-posisi`, `procurement_dashboard`, `pr_add`, `pr_auto_draft`, `master-approval-pembelian`, `do_from_po`, `do_list`, `alarm-harga-beli`, `report-tracking-keluhan`, `lap_cancel_servis`, `lap_profit_insentif`.
Perbaikan: tambahkan link CSS font di 20 halaman itu **dan** fallback `sans-serif` di CSS global (menyelesaikan semua sekaligus, termasuk halaman baru).

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

## 8. Temuan tambahan (audit lanjutan, revisi 2)

**Koreksi:** format tanggal `09/30/2026` (bulan/hari) di kolom filter tanggal berasal dari `<input type="date">` bawaan browser yang mengikuti bahasa Chrome, **bukan** dari aplikasi. Itu bukan temuan dan tidak dimasukkan ke rencana.

### P0 — dokumen cetak (dilihat pelanggan)

**T17. Kop struk selalu "FIT MOTOR ADIWERNA" untuk semua cabang.**
Kop (nama, alamat, telepon) semua struk/faktur diambil dari satu baris `tbsetting`. Terbukti: login sebagai CIKDITIRO lalu cetak faktur servis tetap berkop Adiwerna. Untuk 5 cabang berarti 4 cabang mencetak kop yang salah. Butuh keputusan K6.

**T18. `servis-print-pdf.php` (Invoice Servis A4): teks saling menimpa.**
Nama perusahaan bertumpuk dengan alamat dan kolom No. Service/Status di bagian atas; label "Data Pelanggan" menimpa "Nama" dan "Data Kendaraan" menimpa "No. Polisi". Dokumen ini tidak bisa dibaca. Penyebab ada di CSS blok `.header`/`h4` (dompdf).

**T19. Alamat perusahaan tertimpa alamat pelanggan di kop `penjualan_struk.php`.**
Variabel `$alamat` diisi dua kali (baris 12 alamat perusahaan, baris 61 alamat pelanggan), lalu dikirim ke kop, jadi kop mencetak alamat pelanggan. `pesanan_penjualan_struk.php` sudah benar (`$alamat_perusahaan` vs `$alamat_pelanggan`). `pesanan_pembelian_struk.php` memiliki pola serupa, perlu diverifikasi.

### P1

**T20. Halaman berlabel "Cetak" ternyata form transaksi penuh.**
`penjualan_cetak.php` (breadcrumb "Cetak Struk") dan `servis-reguler-cetak.php` ("Cetak Nota") menampilkan form yang masih bisa diedit (tambah item, tambah keluhan, pilih mekanik), bukan dokumen cetak. Tidak ada `@page`/`@media print` di 9 halaman `*_cetak`. Dokumen cetak sebenarnya adalah `*_struk` (PDF). Usulan: ganti label jadi "Detail Transaksi" dan taruh tombol "Cetak Struk" yang jelas.

**T21. Teks placeholder tercetak di dokumen.**
Kolom fax berisi `SIMPAN NOMOR WA DI ATAS` (tercetak di semua struk), dan data uji seperti `Jl. Dummy No. 1`, `[TEST-E2E] Budi Uji`, `INV-TEST-001`. Perlu dibersihkan bersama T3.

**T22. Format tanggal berbeda antar dokumen.**
Faktur servis dan bukti pembelian memakai `05/07/2026`, faktur penjualan memakai `2026-09-29`. Seragamkan ke `dd/mm/yyyy`.

**T23. Detail kecil faktur servis dan penjualan.**
Label "No. Transaksi" terpecah dua baris; tabel "Jasa Bengkel" kosong hanya menampilkan angka `0`; dua garis tanda tangan tanpa label; kolom "Jumlah"/"Satuan" berdempetan; nama item `-` untuk item tanpa nama.

**T24. DataTables berbahasa Inggris.**
"Showing … entries", "Previous/Next", "Search:", "Actions", dan tautan mentah "Copy CSV Print" tanpa gaya. Terukur di 14 halaman dari ±60 yang diperiksa (`barang`, `pelanggan`, `kendaraan`, `workorder-list`, `jasa-list`, `master-keluhan-crud`, `cabang`, `motor_tipe`, `mekanik_management`, `pmby_hutang`, dll). Perbaikan satu titik: file bahasa DataTables (`language: {url/..}`) di init global.

**T25. Halaman Data Master dengan isi janggal.**
- `item-motor-mapping.php`: 302 baris, banyak item tanpa kode atau nama (`-`, `--`), kolom "Mapping Kategori" kosong semua. Tombol admin "Cleanup Duplikasi Kategori" dan "Rapikan Kategori Tipe Motor" terbuka untuk semua pemilik izin.
- `mekanik_management.php`: judul Inggris "Mechanic Management System", format kode campur (`MK001`, `KRY-00010`, `2019120001`, `037`), nomor telepon palsu `081234567890`, dua entri bernama "Kepala Mekanik", kolom Gaji Pokok kosong.
- `statistik_pelanggan_dashboard.php`: kartu "Perlu Follow Up" = 37.110 = Total Pelanggan (semua pelanggan ditandai follow up, kartunya jadi tidak bermakna); 96% pelanggan berstatus Bronze; kartu "Total Pendapatan" terpecah dua baris sehingga tinggi kartu tidak sama.
- `master-barang-custom.php`: item dummy `CUSTOM-00006 DMY`, judul tab "- Web Bengkel", nomor urut menurun.
- `master-temuan-mapping.php`: kolom "Nama Part" kosong. `motor_tipe.php`: kolom "Tahun" kosong.
- `pelanggan_kategori.php` dan `member-loyalty-program.php`: dua entri menu menuju halaman yang sama ("Master Kategori Member").

### P2 — hak akses (butuh keputusan, bukan bug UI)

**T26. Menu yang terlihat per posisi.**
| Posisi | User aktif | Menu terlihat |
|---|---|---|
| ADM | 9 | 136 dari 136 |
| KEU | 3 | 29 |
| MNG | 3 | 26 |
| PGD | 4 | 22 |
| CRM | 3 | 17 |
| KSR | 10 | 16 |
| KM | 6 | 8 |
| CS | 4 | 7 |
| MK | 3 | 4 |
| HRD | 3 | 4 |
| **KACAB** | **4** | **1** (hanya approval Komplain) |
Kepala Cabang hanya melihat satu menu (tanpa Servis, Laporan, Penjualan). Perlu dipastikan itu memang disengaja (K7).

### Yang sudah aman
Tidak ada teks warning PHP, tidak ada `NaN`, tidak ada scroll horizontal di desktop pada 126 halaman yang diukur, tidak ada teks terpotong berarti, seluruh halaman ACE punya breadcrumb kecuali `master-barang-custom.php`.

## 9. Keputusan tambahan untuk Rafi

- **K6.** Kop struk per cabang: tambah kolom alamat/telepon/WA di `tbcabang` (disarankan) atau tabel pengaturan per cabang? Perlu data resmi tiap cabang (alamat, telepon, WA). Isi "Fax" saat ini hanya placeholder.
- **K7.** Kepala Cabang (KACAB) memang hanya boleh melihat Komplain? Kalau tidak, izin apa yang perlu ditambah (mis. dashboard, laporan cabang sendiri, servis)?
- **K8.** Halaman berlabel "Cetak" (`*_cetak.php`): dipertahankan sebagai halaman detail dengan label baru, atau dibuat menjadi dokumen cetak sungguhan (HTML A4 + `@media print`)?
- **K9.** Format tanggal standar untuk semua dokumen dan tabel: `dd/mm/yyyy`?

## 10. Urutan kerja yang diperbarui

Sisipkan **Fase 1B — Dokumen cetak (prioritas tertinggi setelah Fase 0, 1–2 hari):**
1. T18 perbaiki tata letak `servis-print-pdf.php`.
2. T19 perbaiki tabrakan `$alamat` di `penjualan_struk.php` (+ verifikasi `pesanan_pembelian_struk.php`).
3. T17 kop per cabang (setelah K6), sekaligus mengganti placeholder fax (T21).
4. T22/T23 seragamkan tanggal dan rapikan detail faktur.
Alasan diprioritaskan: dokumen ini diterima pelanggan langsung dan T17/T18 membuat dokumen salah atau tidak terbaca.

Fase lain menyesuaikan: T7 (font) dan T24 (DataTables) masuk **Fase 1** karena bisa diselesaikan di satu titik global; T25 dan T26 masuk **Fase 2** setelah keputusan K1, K7.

## 11. Cakupan akhir audit

Sudah: struktur 152 halaman menu, metrik browser ±126 halaman, ±20 screenshot desktop, 3 screenshot mobile, 24 halaman cetak (5 PDF dilihat), hak akses 11 posisi.
Belum: tampilan mobile untuk sebagian besar halaman, halaman Laporan dan Master lain secara visual satu per satu (hanya lewat metrik), state error/loading, kontras warna dan navigasi keyboard, tampilan cetak dari kertas termal 58/80mm (struk saat ini A4 landscape).

## 12. Audit visual Laporan dan Master (revisi 3)

Dilihat satu per satu di browser 1366px: **Laporan** 10 halaman (`lap_pembelian`, `lap_profit_penjualan`, `lap_servis`, `lap_rekap_kunjungan`, `lap_antarcab`, `lap_kas_keluar`, `lap_profit_insentif`, `laporan-cancel-servis`, plus `lap_penjualan` dan `lap_pesanan_pembelian` sebelumnya) dan **Master** 12 halaman (`barang`, `master-temuan`, `pelanggan`, `item-motor-mapping`, `mekanik_management`, `master-barang-custom`, `master-posisi`, `user_management`, `workorder-list`, `jasa-list`, `barang_kategori`, `statistik_pelanggan_dashboard`). Sisanya (±15 Laporan dan ±28 Master) hanya lewat metrik karena satu templatenya sama dengan yang sudah dilihat. Kendala teknis: tab browser beberapa kali membeku dan harus dibuat ulang, jadi tidak semua halaman sempat di-screenshot.

### P0

**T27. `lap_profit_insentif.php`: layout halaman pecah.**
Sidebar melayang di tengah layar, navbar tidak melebar, isi halaman terdorong ke bawah, dan seluruh teks berfont serif. Terbukti di dua sesi tab. Penyebab: `<head>` ditulis manual dan menyimpang dari template standar (tidak memuat `fonts.googleapis.com.css` dan `ace-rtl.min.css`, urutan CSS berbeda dari `lap_profit_penjualan.php`). Laporan ini praktis tidak bisa dipakai. 19 halaman lain dari daftar T7 hanya kena font serif (layout utuh) kecuali dibuktikan lain.
Usulan: satu partial `<head>` bersama (`_include_head.php`) yang dipakai semua halaman ACE, menggantikan head manual. Ini juga menutup T5, T7, T24 sekaligus.

### P1

**T28. Filter "Pelanggan" di `lap_profit_penjualan.php` tampil rusak.**
Kotak pencarian select2 terbuka permanen dan tidak sejajar dengan kolom lain; tombol Tampilkan turun posisi.

**T29. Dua gaya laporan yang berbeda.**
Mayoritas Laporan memakai gaya lama (filter satu baris, header biru "Hasil Pencarian ditemukan N data", tombol Tampilkan/Cetak/Export lebar). `lap_antarcab.php`, `laporan-cancel-servis.php`, dan `lap_rekap_kunjungan.php` memakai gaya baru (judul besar, kartu KPI, filter kompak). `lap_rekap_kunjungan.php` punya *empty state* dan legenda warna terbaik; usulan menjadikannya acuan.

**T30. Rentang tanggal bawaan tidak seragam.**
`lap_pembelian` 01/06–30/06, `lap_servis` 01/07–06/07, `lap_profit_penjualan` 01/09–29/09, `lap_kas_keluar` 01/09–30/09. Usulan bawaan seragam: awal bulan berjalan sampai hari ini.

**T31. Data Master berisi sampah yang terlihat jelas.**
- `workorder-list.php`: WO tanpa nama (WO0003), WO bernama `2444` dengan badge waktu `2147483647 menit` (melebar keluar kolom) dan keterangan `zzzzzz…` yang meluber, WO `coba`.
- `jasa-list.php`: banyak jasa bernama `-` (mis. GBDBSG, GBLBTS); sebagian besar berstatus Non-Aktif.
- `user_management.php`: seluruh user berstatus "Last Login: Never" (pelacakan login sepertinya tidak terisi), kolom Department `-` semua, dan akun uji (`e2e_test_*`, `kacab_*`, `km_*`) tampil bersama akun asli.
Termasuk ke K3 (pembersihan data) dan satu pengecekan: apakah `last_login` memang tidak pernah diisi.

**T32. Bahasa dan gaya DataTables tidak konsisten (lanjutan T24).**
`user_management` dan `mekanik_management` berjudul "User Management System"/"Mechanic Management System", memakai "Username/Role/Status/Actions", dan tautan `Copy CSV Print` tampil sebagai teks biasa tanpa tombol. `workorder-list`/`jasa-list` memakai "Display 10 records", "Search:", "Export".

### Yang sudah baik (acuan pola)
- `master-temuan.php`, `master-posisi.php` (form akses sidebar), `jasa-list.php`: tata letak rapi, badge status jelas.
- `lap_rekap_kunjungan.php`: empty state dan legenda.
- `barang.php` dan `servis-reguler.php`: bersih, hanya masalah kolom kosong (T2).

### Dampak ke urutan kerja
T27 dipindah ke **Fase 1** dengan prioritas tertinggi (halaman tidak bisa dipakai, perbaikannya sederhana). T28 masuk Fase 2. T29/T30 dikerjakan bersama saat membuat komponen laporan bersama. T31 masuk Fase 5 (data).

## 13. Status pelaksanaan (revisi 4) - sudah dikerjakan dan divalidasi

Semua yang **tidak butuh keputusan Rafi** sudah dikerjakan, diuji, dan di-commit. Validasi memakai: crawl 152 halaman menu (0 error PHP, 0 halaman tanpa CSS font, judul generik 110 menjadi 1), render PDF ke gambar, pengukuran DOM di browser, dan screenshot.

| Kode | Perbaikan | Commit | Bukti validasi |
|---|---|---|---|
| T5 | Judul tab dari `menu_config.php` lewat `lib/titel.php` | `a9a4b34` | 152 halaman: judul generik 110 menjadi 1, judul unik 148 |
| T7 | CSS font di 20 halaman + fallback `sans-serif` di `ace.min.css` | `a9a4b34` | crawl: 0 halaman ACE tanpa CSS font; DOM: `"Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif` |
| T27 | `lap_profit_insentif.php`: pembungkus `#navbar` yang hilang | `a9a4b34` | screenshot: layout normal; crawl: 0 halaman tanpa pembungkus navbar |
| T18 | `servis-print-pdf.php`: header/judul seksi jadi tabel (Dompdf), kolom `quantity`/`harga_jual`/`potongan` yang benar | `7136d52` | render PDF: tidak ada teks menimpa; Qty 5, Harga Rp 4.000 (sebelumnya kosong dan Rp 0) |
| T19 | Alamat kop tidak tertimpa alamat pelanggan/supplier (`penjualan_struk`, `pesanan_pembelian_struk`) | `7136d52` | ekstraksi teks PDF: kop "Ds. Pesalakan, Kec. Adiwerna" |
| T22 | Tanggal `dd/mm/yyyy` di semua struk (helper `_nota_pdf_parts.php`) | `7136d52` | PDF: 29/09/2026 (sebelumnya 2026-09-29) |
| T23 (sebagian) | Label "No. Transaksi" tidak terpecah dua baris | `7136d52` | PDF dirender |
| T24 | DataTables berbahasa Indonesia (112 halaman) + pagination manual 6 halaman | `c463642` | DOM `workorder-list`: "Tampilkan 10 data", "Cari:", "Menampilkan 1 sampai 10 dari 12 data", "Sebelumnya/Berikutnya" |
| T14 (sebagian) | 4 entri menu langsung ke tujuan akhir | `c463642` | crawl: hanya 3 pengalihan tersisa (entri ganda) |
| T13 | `.navbar-brand{white-space:nowrap}` di `ace.min.css` | `c463642` | DOM: `nowrap` aktif |
| T28 | Filter Pelanggan `lap_profit_penjualan`: `<select>` biasa | `c463642` | screenshot: satu baris rapi |
| T1 | Master Pelanggan: nama kosong diurutkan paling akhir | `188eb3c` | halaman 1 dan 3 berisi nama terisi (0 baris kosong per 100) |
| T2 | Label "No GPS" jadi strip abu-abu; badge tier `inline-block nowrap` | `188eb3c` | DOM: 0 label oranye, 99 strip; badge `inline-block/nowrap` |
| T25 (sebagian) | Judul dan kolom Inggris di `mekanik_management`, `user_management` | `188eb3c` | crawl: "Manajemen Mekanik/Pengguna", kolom Aksi |

### Temuan selama pelaksanaan
- **Invoice servis juga salah data, bukan hanya tampilan:** kolom `qty/harga/diskon` tidak ada di `tblservis_barang`, sehingga Qty kosong dan Harga Rp 0. Sudah diperbaiki bersama T18.
- **`pesanan_pembelian_struk.php` memang punya bug alamat yang sama** dengan `penjualan_struk.php` (kop mencetak alamat supplier); sudah diperbaiki.
- **`chosen` di `lap_profit_penjualan`:** memuat `chosen.min.css` membuat halaman membeku, jadi widget dilepas. Penyebab pembekuan belum ditelusuri (halaman lain aman).
- **Cache browser:** `ace.min.css` dan `jquery.dataTables.bootstrap.min.js` disimpan browser. Staf perlu hard-reload (Ctrl+F5) sekali setelah deploy, atau tambahkan versi (`?v=`) pada link CSS/JS jika ingin otomatis.
- **T12 (alert), T9 (halaman berat), T10/T11 (kasir inline style, label form)** tidak dikerjakan: perubahannya luas dan lebih baik dikerjakan per modul setelah keputusan K2.

### Yang belum dikerjakan (dan alasannya)
| Kode | Alasan |
|---|---|
| T3, T31 (data uji/sampah) | butuh K3 |
| T4, T10 (kasir seragam) | butuh K2 |
| T17, T21 (kop per cabang, placeholder fax) | butuh K6 (data resmi tiap cabang) |
| T20 (label "Cetak") | butuh K8 |
| T26 (menu KACAB) | butuh K7 |
| T29, T30 (gaya dan tanggal bawaan laporan) | butuh K9 dan keputusan komponen laporan bersama |
| T14 (3 entri menu ganda) | keputusan produk: hapus atau biarkan |
| T23 sisa (tabel Jasa kosong menampilkan `0`, garis tanda tangan tanpa label) | perlu desain baru dokumen |
| T15 (KPI 559 di Procurement), T16 (angka tanpa pemisah) | perlu verifikasi query/data, bukan UI |

Ukuran tampilan mobile <500px tidak bisa diuji di lingkungan ini (jendela browser tidak bisa lebih sempit dari ±495px), jadi T13 hanya divalidasi lewat gaya terhitung.

