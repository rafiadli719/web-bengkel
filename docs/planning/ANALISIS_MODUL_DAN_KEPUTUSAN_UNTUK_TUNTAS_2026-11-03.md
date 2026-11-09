# Analisis Keseluruhan Modul FMWB dan Daftar Keputusan agar Tuntas

Tanggal: 2026-11-03
Cakupan: seluruh 162 tautan menu di `app/menu_config.php` (16 grup menu utama), ditambah keputusan yang tertahan, kebutuhan IT, dan kebutuhan management.
Status dokumen: analisa dan planning. Tidak ada kode yang diubah.

## 0. Cara membaca dan batas keyakinan

Sumber yang dipakai, dan seberapa kuat buktinya:

| Sumber | Dipakai untuk | Keyakinan |
|---|---|---|
| `app/menu_config.php` (dibaca penuh hari ini) | Daftar modul dan sub modul | Tinggi |
| Pemeriksaan keberadaan file dan `git log` hari ini | Ada atau tidaknya fitur sesudah analisa 13 Okt | Tinggi untuk "tidak ada file/commit", sedang untuk "belum ada logika" |
| `docs/planning/GAP_KEBUTUHAN_DOCX_VS_FMWB_2026-10-13.md`, `HASIL_E2E_MOVING_AVERAGE_2026-10-12.md`, `RUNBOOK_CUTOVER_MOVING_AVERAGE.md`, `docs/PERTANYAAN_OWNER_2026-08-30.md`, `.ai/changelog/*` | Status per modul, keputusan terbuka | Sedang: dokumen bertanggal, bisa basi |
| Catatan memori proyek (CLAUDE.md "Status Ringkas") | Status modul lama (Komplain, Kasir, Servis, Pembelian) | Sedang |

Yang TIDAK diverifikasi hari ini: isi database produksi atau fitmotor1, uji browser, dan jawaban lisan Rafi atau owner yang tidak tercatat di repo. Butir bertanda "(cek)" perlu dikonfirmasi sebelum dijadikan dasar keputusan.

Label status modul:
- **SIAP**: dipakai transaksi nyata atau lulus E2E lokal, tidak ada gap yang diketahui.
- **SIAP + GAP**: jalan, tetapi ada kebutuhan docx atau aturan bisnis yang belum ditegakkan.
- **DITAHAN**: kode ada, tetapi menunggu keputusan, data, atau deploy.
- **BELUM ADA**: tidak ditemukan file atau tabelnya.

## 1. Ringkasan eksekutif

1. **Fungsi inti sudah ada.** Servis, pembelian, penjualan, antar cabang, penyesuaian stok, laporan, keuangan kasir, komplain, dan persediaan Moving Average semuanya punya halaman yang berfungsi. E2E klik penuh di cabang pilot lokal (CIKDITIRO) lulus untuk servis bayar (3 varian), transfer, tiga jenis retur, Terima DO, dan seluruh halaman stok.
2. **Yang menahan "tuntas" bukan lagi fitur dasar, tetapi tiga hal:**
   - (a) **Go-live Moving Average** terhenti di dua keputusan teknis (transit antar cabang, proyeksi `tbstok`) dan di data/tindakan management (pilot, opname fisik, COA, penyetuju).
   - (b) **Aturan bisnis docx yang tertulis wajib tetapi belum ditegakkan** (blok harga naik, level MAHIR kepala mekanik, Sales di servis, KM berikut otomatis, status kondisi sparepart). Hampir semuanya menunggu keputusan atau data dari owner.
   - (c) **Deploy produksi tertinggal.** Sekitar 14 runner migrasi tanggal 30 Sep sampai 14 Okt belum dijalankan di shared hosting, dan satu commit lokal (`a6a2eb5`) belum di-push.
3. **Modul pelengkap (dashboard manajemen, CRM, booking, progres untuk pelanggan, akuntansi) belum ada sama sekali.** Ini keputusan prioritas management, bukan utang teknis.
4. **Keputusan terbuka: 15 untuk tim IT (teknis), 15 untuk Rafi/owner (produk), 14 untuk management/operasional.** Daftar lengkap di bagian 3.
5. **Kemajuan sejak analisa 13 Okt:** A3 (warna stok + stok semua cabang), A6/G9 (izin tulis master barang/supplier), B4/G8 (nopol lama), C1 (filter pembelian/hutang), C2 dan C3 (opname otomatis + laporan selisih), keamanan 37 endpoint, bahasa Indonesia seluruh menu, dan perbaikan tombol Simpan yang hilang di Chrome (4 halaman). Fase A1/A2/A4/A5, B1 sampai B3, D, E, dan F belum disentuh (tidak ada commit sesudah 13 Okt yang menyentuhnya).

## 2. Inventaris modul dan sub modul

### 2.1 Dashboard dan CRM tiket

| Sub modul | Halaman | Status | Catatan |
|---|---|---|---|
| Dashboard | `index.php` | SIAP + GAP | Hanya antrian servis dan progres mekanik. Tidak ada omset, laba, hutang/piutang, perbandingan cabang (G20, G21). |
| Lapor Masalah | `issue_add.php` | SIAP | Tiket terstruktur, revisi komisi pasca bayar. |
| Gabung Pelanggan | `customer_merge_approve.php` | SIAP | Approver final masih memakai role yang ada (keputusan role final terbuka). |
| Deteksi Duplikat | `admin_deteksi_pelanggan_dobel.php` | SIAP | Threshold deteksi belum final. |
| Pindah Kepemilikan Kendaraan | `kendaraan_pindah_tangan*.php` | DITAHAN | Menu disembunyikan 2026-07-20 sampai migrasi data Access selesai. Kode dan tabel ada. |

### 2.2 Data Master (12 grup, 60+ halaman)

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Daftar Item (21 halaman: barang, kategori, satuan, pabrik, rak, margin, status harga, paket kerja, jasa, mapping per motor, keluhan, fast moves, temuan, barang custom, harga jual plus jasa) | SIAP + GAP | A3 selesai (warna merah/kuning, stok semua cabang). Belum: status harga naik/turun/tetap di list, harga jual vs acuan, update harga massal (G2), filter tipe motor (G12, cek). Izin tulis hanya ADM sudah ditegakkan. |
| Pelanggan (master, kategori, program member, statistik) | SIAP + GAP | Tier membership final belum diputuskan (cek: status jawaban owner). |
| Supplier | SIAP | Tipe supplier eksternal/antar cabang dibedakan lewat `no_supplier` berisi kode cabang. |
| Cabang (master, tipe, harga antar cabang) | SIAP + GAP | Alamat CIKDITIRO dan PACUL kosong, PESALAKAN dan PST tampak dummy, telepon belum diisi (dipakai kop struk). TRAYEMAN `tipe_cabang = 2` belum dijelaskan artinya. |
| Mekanik (master, level, kepala mekanik, tarif jemput) | SIAP + GAP | Validasi kepala mekanik minimal MAHIR tidak ada (G5). |
| Sales | SIAP + GAP | Master ada, tetapi tidak terhubung ke servis maupun insentif (G6). |
| Master Perusahaan | SIAP | |
| Kendaraan (tipe, pabrik, kategori, kendaraan, warna) | SIAP | Duplikat kategori dan merek sudah di-merge di lokal (belum di produksi). Merek legacy id 1-4 belum dipetakan. Nopol lama sudah dicatat dan diblokir (B4). |
| Wilayah (4 level) | SIAP | |
| Akun Sumber Kas, Akun Biaya | SIAP | |
| Data User, Master Posisi, Master Karyawan, Nominal Rupiah | SIAP | Akun TEST harus dinonaktifkan sebelum produksi. |
| Sinkronisasi Data Access (3 halaman) | SIAP + GAP | 7 dataset `gabung_*` disinkron tetapi tidak dipakai kode (keputusan: hentikan atau pakai). Sync tidak menimpa saldo cabang `live`. |

### 2.3 Pembelian

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Dashboard Stok Min/Maks | SIAP | |
| Rencana Order | DITAHAN | Butuh tabel `tblsupplier_tempo` yang tidak ada; sekarang menampilkan penjelasan, bukan error. Bangun atau arsipkan. |
| Permintaan Pembelian, Draf PR otomatis | SIAP | |
| Pesanan Pembelian (daftar, manual, unggah Excel, approval bertingkat) | SIAP | Nominal approval owner dan persen peringatan harga masih perlu angka final (cek). |
| Penerimaan Barang (DO), Daftar DO | SIAP | Tombol Simpan/Terima sempat hilang di Chrome, sudah diperbaiki lokal. |
| Pembelian Faktur (daftar, dari PO, manual) | SIAP | Filter status bayar dan tipe supplier sudah ada (C1). |
| Retur Pembelian | SIAP | |
| Pembayaran Hutang | SIAP | Filter tipe supplier dan rentang jatuh tempo sudah ada (C1). |
| Alarm Harga Beli | SIAP + GAP | Per Juli tabel `alarm_harga_beli` berisi 0 baris; trigger perlu dipastikan terpasang di produksi (cek `SHOW TRIGGERS`). Alarm belum menjadi blok di penjualan (G1). |

### 2.4 Penjualan

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Pesanan Penjualan (daftar, manual) | SIAP | |
| Penjualan (daftar, dari pesanan, manual) | SIAP + GAP | Hanya `STOK KOSONG` yang diblokir; blok "stok kosong dan harga naik" belum (G1). |
| Retur Penjualan | SIAP | |
| Piutang (pembayaran, input) | SIAP | |

### 2.5 Antar Cabang

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Cabang Sendiri (pesan, tarik/kirim, penerimaan) | SIAP + GAP | Barang dalam perjalanan belum tercatat di ledger (F-7). Halaman kirim/terima belum memakai `shipTransfer`/`receiveTransfer`. |
| Cabang Mitra (unggah Excel, manual, penerimaan) | SIAP | |
| Daftar Transaksi | SIAP | |
| Pengadaan Barang (permintaan, buat, kirim tanpa permintaan) | SIAP + GAP | Sama seperti di atas. Tombol Simpan kirim pusat sudah diperbaiki lokal. |

### 2.6 Servis

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Servis Reguler (KM harian, antrian, input, lihat data) | SIAP + GAP | Tidak ada kolom Sales (G6), KM berikut manual (G7), Form Servis terpadu dan status kondisi belum (G15, G16). Bayar lulus E2E lokal. Dropdown Admin form bayar kosong di CIKDITIRO. |
| Servis Garansi | SIAP | Dua versi masa garansi pernah ditemukan (30 hari/1000 KM vs 7-14 hari per tier); keputusan resmi perlu dikonfirmasi (C3, cek). |
| Servis Jemput Antar (jadwal, pelacakan keluhan) | SIAP | Bug bayar tanpa jasa sudah diperbaiki lokal. |
| Master Promo/Diskon, Persetujuan Diskon | SIAP | Threshold nominal approval opsional. |
| Laporan DP Servis | SIAP | |
| Retur Servis | SIAP | Kebijakan stok barang terpasang perlu keputusan (cek). |
| Komisi mekanik | SIAP | Terpasang di 3 titik bayar (Juli). Aturan Admin 5%, siklus bayar, snapshot revisi: status jawaban tidak tercatat (cek). |

### 2.7 Penyesuaian Stok

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Manual masuk/keluar | SIAP | |
| Otomatis (opname) | SIAP | Acuan "semua item stok > 0", urut Rak lalu Nama, N+1 diperbaiki (C2). |
| Stok Akhir, Kartu Stok | SIAP + GAP | Untuk cabang `live`, pembaca lama tidak melihat OPENING/ADJ/SCRAP (F-8). |

### 2.8 Laporan (±30 halaman)

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Pembelian, retur, hutang (3), pesanan | SIAP | |
| Penjualan, laba, retur, piutang (3), antar cabang (3), servis, kunjungan, batal servis, kas masuk/keluar, stok masuk/keluar | SIAP | Dua laporan sempat lambat dan sudah dioptimalkan. Tanggal omset servis memakai tanggal masuk, bukan tanggal bayar (C4, keputusan terbuka). |
| Selisih Stok Opname | SIAP | Baru, selesai 14 Okt (C3). |
| Rekap periodik gabungan semua cabang | BELUM ADA | Harian, bulanan, tahunan, per cabang dan gabungan (G17). |
| Laba & Insentif | SIAP + GAP | Tidak ada komponen Sales (G6). |

### 2.9 Keuangan Kasir (14 halaman)

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Dashboard Kasir, closing, revisi, serah terima, setoran pusat/bank, master (akun, nama transaksi, rekening, nominal, kas awal) | SIAP | Semua halaman dan sidebar tervalidasi. Task 16-18 (cutover dari aplikasi kasir lama) tuntas. |
| Tampilan Keuangan Kasir vs tema ACE | DITAHAN | Keputusan K2 belum ada. |

### 2.10 Penanganan Komplain (7 halaman)

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Input, antrian rework/approval, eskalasi, dashboard, ekspor, master kategori, impor massal | SIAP | REWORK otomatis membuat servis garansi. Tiga gap desain sudah ditutup 4 Okt. |
| Akun Kepala Cabang dan Kepala Mekanik asli | DITAHAN | Menghalangi approval di PACUL, PESALAKAN, TRAYEMAN, CIKDITIRO. Akun TEST sudah ada untuk uji. |

### 2.11 Persediaan (Rata-rata Bergerak / Moving Average)

| Sub modul | Status | Gap atau catatan |
|---|---|---|
| Valuasi, kartu stok bernilai, transit/PPV/klaim/HPP, koreksi dengan persetujuan, stok awal, pengaturan dan rekonsiliasi | DITAHAN | Seluruhnya mode `off`/`shadow`. 164 tes CLI lulus, E2E browser lulus. Dari 12 temuan E2E 12 Okt: 9 sudah diperbaiki (F-9 dan F-11 pada 24 Okt), F-7 dan F-8 menunggu keputusan, F-12 backlog. Opsi mode `live` di halaman Pengaturan sengaja belum ditawarkan. |

### 2.12 Modul pelengkap yang BELUM ADA

| Modul | Rujukan | Catatan |
|---|---|---|
| Dashboard Manajemen dan Dashboard Cabang | G20, G21 | |
| CRM (pengingat servis, follow-up, posisi Staf CRM) | G22 | Hanya statistik, loyalty, rekap kunjungan. |
| Booking Servis | G23 | |
| Status progres servis untuk pelanggan (tautan bertoken) | G24 | |
| Akuntansi keuangan, jurnal otomatis | G25 | Kode jurnal sudah ada, nonaktif sampai kode akun COA diisi. |
| Alur buka cabang baru | G26 | |
| Master Jadwal Penggantian Oli, KM berikut otomatis | G7 | |
| Master Status Kondisi Sparepart, Form Servis terpadu | G15, G16 | |
| Program cuci gratis dan voucher | F1 (30 Agt) | Tabel belum dibuat, menunggu skema owner. |
| Login predictive user | G19 | |

## 3. Keputusan dan kebutuhan per pihak

Format: ID, butir, usulan (bila ada), apa yang tertahan.

### 3.1 Tim IT (dikerjakan tanpa menunggu bisnis)

| ID | Butir | Tertahan / alasan |
|---|---|---|
| IT-1 | Commit changelog 2 Nov dan `CLAUDE.md`; push `a6a2eb5` (satu-satunya commit yang belum di remote). | Definition of Done. |
| IT-2 | Review Sonnet lalu Opus (merge gate) untuk commit 1-2 Nov. Tidak terlihat catatan reviewnya. | Aturan merge. |
| IT-3 | Deploy ke fitmotor1 dan jalankan migrasi `2026-10-14_run_master_write_perm.php` di sana. Tanpa itu ADM non-`*` kena 403 di master barang dan supplier. | Server uji. |
| IT-4 | Siapkan paket migrasi produksi shared hosting (Rafi yang menjalankan runner): `2026-09-30_*` (3), `2026-10-04_*` (4), `2026-10-08_*` (4), `2026-10-13_*` (2), `2026-10-14_*` (1). Sertakan urutan, backup, dan `down`. | Semua belum di produksi. |
| IT-5 | Wiring transit antar cabang T1-T6 (setelah R-2). Perkiraan 1-2 hari. | R-2. |
| IT-6 | Proyeksi OPENING/ADJ/SCRAP ke `tbstok` (setelah R-1). Perkiraan ukuran sedang. | R-1. |
| IT-7 | E2E halaman yang memanggil WhatsApp/Accurate (input servis x5, stok masuk/keluar rst) di cabang pilot dengan kredensial asli. Tidak boleh diuji di lokal. | Akses pilot. |
| IT-8 | Notifikasi Finance untuk entri PPV (V-13, F-12). | Backlog. |
| IT-9 | Hapus kredensial DB hardcode fallback di `app/koneksi.php` dan file sejenis. Utang lintas banyak file. | Belum dijadwalkan. |
| IT-10 | Suite Playwright. DoD mensyaratkannya, sedangkan `tests/` hanya berisi tes CLI inventory. | Belum ada. |
| IT-11 | Pastikan trigger `trg_alarm_harga_beli` terpasang di produksi (`SHOW TRIGGERS`). | Prasyarat G1. |
| IT-12 | Arsipkan kode mati (9 INSERT tak terjangkau, `keluhan-proses.php`, `save_*_h.php`, dll.) dan hentikan atau pakai dataset `gabung_*`. | Kebersihan. |
| IT-13 | Kebersihan data: tabel master temuan-jasa mapping hilang di lokal, nama "-" di mapping, stok negatif historis. | Data. |
| IT-14 | Setelah semua cabang live stabil 30 hari: tutup shadow, arsipkan jalur tulis lama (F5-10). | Akhir proyek MA. |
| IT-15 | Sinkronkan CLAUDE.md "Status Ringkas", memori, dan checklist-projek dengan keadaan hari ini. Aturan lama "HPP FIFO" sudah diganti Moving Average. | Dokumentasi. |

### 3.2 Rafi / owner (keputusan produk dan teknis-bisnis)

| ID | Keputusan | Usulan | Membuka |
|---|---|---|---|
| R-1 | F-8: proyeksi `tbstok` opsi A, B, atau C; nasib saldo negatif historis (D-10). | A (tulis baris `tbstok` di transaksi yang sama). | IT-6, pilot `live` |
| R-2 | F-7 transit: terima sebagian boleh atau tidak; siapa boleh membatalkan transit. | Tetap in-transit + alert aging, selesai lewat tombol "kembalikan ke asal". | IT-5, pilot `live` |
| R-3 | K-1 / G18: acuan HPP. | MA tetap HPP akuntansi; "termahal dari 4 pembelian terakhir" hanya acuan harga jual dan status harga. Revisi docx. | Fase A |
| R-4 | K-2 / G1: definisi "harga naik" dan keras atau lunak. | (a) baris alarm belum direview, atau (b) harga beli terakhir di atas harga pokok master melewati threshold; blok dengan override approval. | A1, A5 |
| R-5 | K-3 / G6, B7: skema insentif Sales (nominal, persen, hadiah) dan dasar hitung. | Tentukan satu. | B1 |
| R-6 | K-4 / G7, F4: interval KM jadwal oli per jenis oli dan kategori motor, dan siapa mengisi data pertama. | Data dari management (M-10). | B3 |
| R-7 | K-5 / G15, C7: daftar status kondisi per jenis sparepart; apakah `master-temuan.php` sudah cukup. | Seed dari file xlsx. | D1-D2 |
| R-8 | K-6: urutan modul pelengkap (Dashboard, CRM, Booking, Akuntansi) dan pemakainya. | Dashboard Manajemen lebih dulu. | Fase E-F |
| R-9 | Rencana Order: bangun atau arsipkan. | Putuskan setelah M-9. | Satu halaman menu |
| R-10 | History service pelanggan (tabel `tb_history_*` hilang, kode tidak cocok skema): bangun atau arsipkan. | Perbaiki hati-hati, bukan sekadar CREATE TABLE. | Alur bayar |
| R-11 | Pemetaan merek legacy id 1-4 ke merek kanonik. | | Kebersihan master |
| R-12 | UI: K2 (Keuangan Kasir vs ACE), K4 (glosarium), K5 (mobile), K7 (menu KACAB hanya 1 item). | | Polesan akhir |
| R-13 | Task 21 / Task 34: migrasi SSO bridge `priori-tech` ke `tbuser`, lalu retire `masterkey.php`. | Butuh koordinasi eksternal. | Penutupan keamanan |
| R-14 | Tier membership final (gabungan vs RFM) dan konfirmasi tier tidak pernah turun. Per 19 Juli belum dijawab (cek). | | Maintenance membership |
| R-15 | Rekonsiliasi `docs/PERTANYAAN_OWNER_2026-08-30.md`: A1 terjawab lewat keputusan MA 5 Okt; B1-B7, C1-C10, D1-D6, E1-E3, F1-F4, G1-G2 kolom jawabannya masih kosong di file. Sebagian mungkin sudah diputuskan lisan. Prioritas menurut file: B1, B3, C3, C4. | | Komisi, garansi, laporan omset |

### 3.3 Management / operasional

| ID | Butuh | Tertahan |
|---|---|---|
| M-1 | Cabang pilot dan jadwal shadow 1-2 minggu; urutan PST, cabang kecil, cabang besar. | Seluruh go-live MA |
| M-2 | Penyetuju (checker KEU atau MNG) koreksi persediaan per cabang. | Koreksi nilai |
| M-3 | Opname fisik hari cutover per cabang dengan transaksi dihentikan sementara (D-03). | Stok awal ledger |
| M-4 | Kode akun COA dari akuntan (D-07). | Jurnal otomatis, akuntansi |
| M-5 | Arti kode lokasi `001` di `tbstok` lama. | Pembersihan data stok |
| M-6 | Nama staf asli dan kode karyawan Kepala Cabang dan Kepala Mekanik di 4 cabang; admin untuk dropdown form bayar CIKDITIRO. | Approval komplain, form bayar |
| M-7 | Alamat dan telepon cabang (Master Cabang). | Kop struk per cabang |
| M-8 | Persetujuan bisnis larangan backdate dan stok minus ditolak (BR-05/06) saat `live`, plus jadwal pelatihan staf sesuai `docs/SOP_PERSEDIAAN_MOVING_AVERAGE.md`. | Mode `live` |
| M-9 | Data tempo supplier. | Rencana Order |
| M-10 | Data interval jadwal oli dan daftar status kondisi sparepart. | B3, D1 |
| M-11 | Kebijakan servis: masa garansi resmi (C3), omset servis pakai tanggal masuk atau tanggal bayar (C4), siapa approve cancel (C1), hutang servis (C9), batas diskon manual (C10), motor tidak diambil (C8). | Laporan omset, garansi |
| M-12 | Komisi: arti "Admin 5%" (B1), siklus bayar (B3), perlakuan garansi dan revisi pasca lunas (B4, B5), pembagian 2 mekanik (B6). Pastikan mana yang sudah diputuskan. | Insentif penuh |
| M-13 | Program bisnis: PPN (E1), prioritas modul Akuntansi (E2), cuci gratis (F1), Booking (F2), reminder servis (F3). | Fase F |
| M-14 | Hak akses role Pengadaan dan Staf CRM (G1), arti `tipe_cabang = 2` TRAYEMAN (D1), tanggung jawab rekonsiliasi antar cabang (D2). | RBAC lengkap |

## 4. Yang diperlukan agar FMWB tuntas

Definisi tuntas yang diusulkan: (1) seluruh cabang berjalan di Moving Average mode `live` dan stabil 30 hari, (2) semua aturan bisnis wajib dari docx (kelompok P1 dan P2) ditegakkan, (3) migrasi dan kode di produksi sama dengan repo, (4) semua kriteria Definition of Done global terpenuhi, termasuk tes otomatis.

### Gelombang 0 — Bersih-bersih dan deploy (IT, tidak menunggu keputusan)

Isi: IT-1, IT-2, IT-3, IT-4, IT-11, IT-15. Selesai bila repo = server uji = produksi untuk kode dan migrasi.
Syarat keluar: `git status` bersih, migrasi tercatat di produksi, trigger alarm terverifikasi.

### Gelombang 1 — Go-live Moving Average di cabang pilot

Isi: R-1, R-2 (keputusan) lalu IT-5, IT-6, IT-7, IT-8; M-1, M-2, M-3, M-4, M-5, M-8.
Syarat keluar: pilot berjalan `live` 1-2 minggu, rekonsiliasi harian bersih, tidak ada selisih tak terjelaskan. Lalu cabang kecil, terakhir cabang besar; stabil 30 hari, baru IT-14.
Risiko: tanpa opname fisik per cabang dan keputusan F-7/F-8, mode `live` tidak boleh dinyalakan.

### Gelombang 2 — Aturan bisnis docx (P1)

Isi: R-3, R-4 lebih dulu, lalu A1, A2, A4, A5 (status harga, update massal, blok harga naik); R-5 lalu B1 (Sales di servis); B2 (validasi MAHIR, tidak menunggu keputusan, tetapi konfirmasi C5 bila ingin dipaksa sistem); R-6 dan M-10 lalu B3 (jadwal oli, KM berikut).
Syarat keluar: setiap aturan punya tes E2E data dummy dan migrasi reversible.
Risiko: performa list master barang 5.000+ item; ukur sebelum dan sesudah.

### Gelombang 3 — Form Servis, laporan, dashboard

Isi: R-7 dan M-10 lalu D1-D3 (status kondisi, lembar Form Servis terpadu, panduan upselling); E1 (rekap periodik) dengan tabel ringkasan harian; E2 (Dashboard Manajemen dan Cabang); E3 (login predictive).
Risiko: query agregat lintas cabang berat; pakai tabel ringkasan dan job di `scripts/`.

### Gelombang 4 — Pelengkap dan penutupan

Isi: R-8, R-9, R-10, R-13, M-13 lalu Booking, progres pelanggan, CRM, akuntansi (setelah COA), alur buka cabang baru; R-12 polesan UI; IT-9, IT-10, IT-12, IT-13.
Syarat keluar: Playwright berjalan, `masterkey.php` dipensiunkan, tidak ada kredensial hardcode.

### Kebutuhan lintas gelombang

- **Tim:** IT tunggal (Rafi). Gelombang 1 dan 2 tidak bisa dipararelkan tanpa menambah tenaga, karena keduanya menyentuh alur penjualan dan servis.
- **Disiplin:** investigasi dulu, E2E data dummy lalu hapus, migrasi reversible, commit, `.ai/changelog`, checklist-projek.
- **Akses:** staging atau cabang pilot dengan kredensial WhatsApp/Accurate asli (IT-7); akses produksi untuk menjalankan runner (Rafi).

## 5. Risiko utama

1. **Produksi tertinggal dari kode.** Kode yang bergantung pada migrasi (mis. izin `barang_write`) akan 403 bila dideploy tanpa migrasi. Deploy kode dan migrasi harus satu paket.
2. **Dua angka stok.** Selama F-8 belum diputuskan, cabang `live` akan menampilkan stok berbeda antara ledger baru dan laporan lama.
3. **Pola bug yang berulang.** Di sesi E2E sebelumnya ditemukan pola serupa di banyak halaman (INSERT kolom NOT NULL tanpa default, tombol submit yang di-disable sinkron). Halaman yang belum diuji klik penuh tetap berisiko.
4. **Satu orang IT.** Semua keputusan teknis bertumpu pada satu orang; tidak ada pemeriksa independen selain review model.
5. **Data kotor.** Stok negatif historis, master duplikat, dan akun TEST mengganggu cutover bila tidak dibersihkan lebih dulu.

## 6. Langkah berikutnya yang disarankan

1. Rafi menjawab R-1 dan R-2 (dua pertanyaan, keduanya sudah punya usulan). Itu membuka Gelombang 1.
2. Management menunjuk cabang pilot, penyetuju koreksi, dan tanggal opname (M-1 sampai M-3).
3. IT menjalankan Gelombang 0 sambil menunggu jawaban.
4. Rafi menjawab R-3 dan R-4 agar Gelombang 2 bisa mulai paralel dengan pilot.
