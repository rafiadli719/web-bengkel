# Modul Penanganan Komplain Fit Motor
**Dokumen Requirement — Fase 1**
Status: Final, siap untuk estimasi & pengembangan
Disusun oleh: Business Analyst | Untuk: Tim IT/Developer

---

## 1. Latar Belakang

Saat ini pencatatan komplain pelanggan dilakukan secara manual di Excel, terpisah dari aplikasi bengkel (desktop, lokal per cabang). Kondisi ini menyebabkan:

- **Dual entry** — jika pelanggan benar-benar datang untuk perbaikan ulang, data dicatat dua kali (Excel + aplikasi desktop).
- **Blind spot** — komplain yang tidak berujung kedatangan pelanggan (harga, sikap staf, waktu tunggu, fasilitas, dll) tidak tercatat di sistem manapun secara terstruktur.
- **Tidak ada kontrol keputusan** — tidak ada mekanisme persetujuan berjenjang untuk memvalidasi apakah komplain rework benar-benar valid, siapa yang bertanggung jawab, dan siapa yang menindaklanjuti.
- **Tidak ada visibilitas lintas cabang** — manajemen tidak punya gambaran tren komplain di seluruh cabang.

## 2. Tujuan Bisnis

Membangun satu sistem pencatatan komplain berbasis web, terpusat lintas cabang, yang:

1. Mencatat **semua jenis komplain** (bukan hanya yang berujung rework), baik pelanggan jadi datang kembali maupun tidak.
2. Merutekan setiap komplain ke penanggung jawab (PIC) yang tepat sesuai kategorinya.
3. Menerapkan kontrol persetujuan berjenjang khusus untuk komplain kategori teknis (REWORK) agar tidak ada penutupan sepihak oleh pihak yang bertanggung jawab atas kesalahan tersebut.
4. Menjadi fondasi platform web yang akan disambung dengan modul transaksi/nota di fase berikutnya (fase 2 — di luar cakupan dokumen ini, ditunda sampai modul ini stabil di lapangan).

## 3. Ruang Lingkup

### Termasuk dalam Fase 1
- Input komplain semua kategori, oleh CS/admin cabang.
- Master data kategori komplain (dikelola oleh Super Admin).
- Alur persetujuan berjenjang untuk kategori REWORK (Kepala Mekanik → Kepala Cabang).
- Alur penanganan langsung untuk kategori non-REWORK (ditangani Kepala Cabang tanpa approval berjenjang).
- Dashboard ringkas untuk Manajemen (lintas cabang).
- Hak akses berbasis role dan cabang.
- Uploader data massal (untuk kebutuhan migrasi/impor data ke depan — bukan untuk data komplain historis Excel, yang disepakati tidak dimigrasikan).

### Tidak termasuk (ditunda / fase berikutnya)
- Migrasi modul transaksi/nota dari aplikasi desktop ke web.
- Reminder otomatis (WA/email) untuk komplain yang menggantung.
- Integrasi otomatis/API dengan sistem lain.
- Migrasi data komplain historis (Agustus–September 2026) — sistem dimulai dari kosong.

## 4. Role & Hak Akses

| Role | Cakupan Akses | Wewenang |
|---|---|---|
| **CS / Admin Cabang** | Data cabang sendiri | Input komplain baru, edit sebelum status final |
| **Kepala Mekanik** | Data cabang sendiri, komplain kategori REWORK | Meninjau komplain, assign mekanik pelaksana, menyusun usulan (terima+jadwalkan / tolak) |
| **Mekanik** | Komplain yang ditugaskan padanya | Update status pekerjaan rework (dikerjakan → selesai teknis); tidak punya wewenang keputusan |
| **Kepala Cabang** | Data cabang sendiri (semua kategori) | - Approve/minta revisi usulan Kepala Mekanik (kategori REWORK)<br>- Tangani & tutup langsung komplain non-REWORK (tanpa approval berjenjang) |
| **Manajemen (Owner/BOD)** | Semua cabang | View seluruh data, dashboard agregat, terima eskalasi revisi ke-3 |
| **Super Admin** | Konfigurasi sistem | Kelola master kategori komplain & pemetaan PIC default |

**Catatan penting:** Kepala Cabang **hanya** melihat data cabangnya sendiri. Akses lintas cabang hanya untuk role Manajemen.

## 5. Master Data Kategori Komplain

Dikelola oleh Super Admin (CRUD), bukan hardcode di aplikasi.

| Kategori | PIC | Jenis Penyelesaian |
|---|---|---|
| **REWORK** | Kepala Mekanik → approval Kepala Cabang | Pelanggan diarahkan kembali untuk perbaikan ulang (ada jadwal kedatangan) |
| **HARGA** | Kepala Cabang langsung | Tindak lanjut penanganan (tanpa kedatangan wajib) |
| **SIKAP** | Kepala Cabang langsung | Tindak lanjut penanganan |
| **ANTRIAN** | Kepala Cabang langsung | Tindak lanjut penanganan |
| **FASILITAS** | Kepala Cabang langsung | Tindak lanjut penanganan |
| **LAINNYA** | Kepala Cabang langsung | Tindak lanjut penanganan |

Field kategori bersifat dropdown, sumbernya dari master data ini — bukan teks bebas.

## 6. Alur Proses (Business Flow)

### 6.1 Alur Umum (Semua Kategori)
1. CS/Admin menerima komplain (telepon, WA, atau pelanggan datang langsung) dan menginput ke sistem.
2. Sistem menyimpan komplain dengan status **Open**, dan otomatis merutekan ke PIC sesuai kategori (lihat tabel master kategori).

### 6.2 Alur Kategori REWORK (Maker–Checker)
1. **Kepala Mekanik meninjau** komplain — cek unit, riwayat servis, riwayat mekanik terkait.
2. **Kepala Mekanik menyusun usulan**: pilih salah satu —
   - *Usulan Terima* → assign mekanik pelaksana rework + rencana tanggal kedatangan, atau
   - *Usulan Tolak* → isi alasan penolakan (mis. kondisi wajar pemakaian, bukan cacat servis).
3. Status berubah menjadi **Diajukan**.
4. **Kepala Cabang memutuskan**:
   - **Setuju** → sistem lanjut otomatis sesuai jenis usulan:
     - Usulan Terima → status **Dijadwalkan** → **Dikerjakan** → **Selesai**.
     - Usulan Tolak → status **Ditutup - Ditolak** (final, alasan tersimpan).
   - **Minta Revisi** → kembali ke Kepala Mekanik untuk menyusun ulang usulan (status kembali ke "Diajukan" setelah direvisi).
5. **Batas revisi: maksimal 2 kali.** Jika revisi ke-3 diperlukan, sistem otomatis eskalasi ke role Manajemen untuk keputusan final.
6. Jika pelanggan tidak datang sesuai jadwal lebih dari batas waktu yang ditentukan (default: 7 hari, dapat dikonfigurasi), Kepala Cabang dapat mengubah status menjadi **No-show** (bukan dihapus).

### 6.3 Alur Kategori Non-REWORK (HARGA, SIKAP, ANTRIAN, FASILITAS, LAINNYA)
1. Komplain otomatis masuk ke Kepala Cabang (tanpa melalui Kepala Mekanik).
2. Kepala Cabang mengisi field **Tindak Lanjut Penanganan** (wajib diisi, berupa keterangan tindakan yang dilakukan).
3. Kepala Cabang menutup status menjadi **Selesai** — **tanpa approval berjenjang** dari pihak manapun.

### 6.4 Status yang Berlaku
`Open` → `Diajukan` (khusus REWORK) → `Dijadwalkan` → `Dikerjakan` → `Selesai`
Status alternatif: `Ditutup - Ditolak`, `No-show` (khusus REWORK), `Eskalasi Manajemen` (setelah revisi ke-3)

## 7. Data Dictionary

### 7.1 Tabel Inti (berlaku untuk semua kategori)

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| Nama Pelanggan | Teks | Ya | |
| No HP | Teks | Ya | |
| Nopol | Teks | Ya | Digunakan untuk deteksi riwayat komplain berulang |
| Cabang | Dropdown | Ya | Cabang asal servis |
| Kategori Komplain | Dropdown (master data) | Ya | Menentukan PIC & jenis field lanjutan |
| Channel Lapor | Dropdown (Telepon/WA/Datang langsung/Lainnya) | Ya | |
| Tanggal Lapor | Tanggal (auto) | Ya | |
| Detail Keluhan | Teks panjang | Ya | |
| Nomor Nota Rujukan | Teks bebas | Tidak wajib | Mengacu ke nomor nota servis terakhir kendaraan terkait; input manual (belum tervalidasi otomatis, karena data nota masih di aplikasi desktop) |
| PIC | Auto-assign | — | Berdasarkan kategori |
| Status | Enum (lihat 6.4) | — | |
| Log Perubahan Status | Auto (siapa, kapan, dari-ke status apa) | — | Audit trail, tidak bisa dihapus |

### 7.2 Field Kondisional — Kategori REWORK

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| Mekanik Servis Awal | Referensi/Teks | Tidak wajib | Jika diketahui |
| Mekanik Pelaksana Rework | Referensi | Ya (saat usulan disusun) | Diisi oleh Kepala Mekanik |
| Jenis Usulan | Enum (Terima/Tolak) | Ya | |
| Alasan Usulan | Teks | Ya jika Usulan Tolak | |
| Rencana Tanggal Kedatangan | Tanggal | Ya jika Usulan Terima & disetujui | |
| Tanggal Rework Aktual | Tanggal | Ya saat status "Selesai" | |
| Biaya Rework | Angka (boleh 0) | Ya saat status "Selesai" | 0 = gratis garansi, tetap tercatat sebagai biaya internal |
| Jumlah Revisi | Angka (auto counter) | — | Trigger eskalasi otomatis di angka 3 |
| Keputusan Kepala Cabang | Enum (Setuju/Minta Revisi) | Ya | |

### 7.3 Field Kondisional — Kategori Non-REWORK

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| Tindak Lanjut Penanganan | Teks panjang | **Ya, wajib sebelum status "Selesai"** | |
| Tanggal Ditindaklanjuti | Tanggal | Ya saat status "Selesai" | |

## 8. Business Rules & Validasi

1. Komplain dapat dibuat tanpa transaksi servis apa pun ada terlebih dahulu di aplikasi desktop.
2. Kategori komplain wajib dipilih dari master data (dropdown), tidak boleh teks bebas.
3. Sistem otomatis menampilkan riwayat komplain sebelumnya untuk NOPOL yang sama saat CS mulai input (deteksi komplain berulang).
4. Role selain Kepala Mekanik **tidak dapat** membuat/mengubah usulan pada komplain kategori REWORK.
5. Role selain Kepala Cabang **tidak dapat** menyetujui/meminta revisi usulan, dan **tidak dapat** menutup status "Selesai" pada kategori apapun.
6. Status "Ditutup - Ditolak" wajib memiliki alasan terisi sebelum dapat disimpan.
7. Status "Selesai" pada kategori non-REWORK wajib memiliki field "Tindak Lanjut Penanganan" terisi.
8. Jumlah revisi usulan REWORK dibatasi maksimal 2 kali; revisi ke-3 otomatis mengubah status menjadi "Eskalasi Manajemen" dan mengunci aksi Kepala Mekanik/Kepala Cabang sampai Manajemen memutuskan.
9. Aksi "Delete" tidak tersedia untuk record komplain — hanya perubahan status, dengan jejak audit (siapa, kapan, status sebelum/sesudah).
10. Kepala Cabang hanya dapat melihat dan menangani data cabangnya sendiri. Role Manajemen dapat melihat semua cabang.
11. Master kategori komplain (nama kategori, PIC default) hanya dapat diubah oleh Super Admin.
12. Indikator umur komplain (aging) dihitung sejak **tanggal lapor pertama**, tidak reset saat terjadi revisi usulan.

## 9. Kebutuhan Tampilan (UI) per Role

| Layar | Untuk Role | Elemen Utama |
|---|---|---|
| Form Input Komplain | CS/Admin Cabang | Field wajib minimal (nama, HP, nopol, cabang, kategori, channel, detail keluhan); field kondisional REWORK/non-REWORK baru muncul di tahap berikutnya, bukan di form awal |
| Antrian & Usulan REWORK | Kepala Mekanik | List komplain REWORK yang jadi tanggung jawabnya, form assign mekanik + pilih jenis usulan |
| Antrian Approval | Kepala Cabang | List usulan REWORK menunggu keputusan (Setuju/Minta Revisi), list komplain non-REWORK menunggu tindak lanjut, indikator umur (aging) per komplain |
| Dashboard Manajemen | Manajemen (Owner/BOD) | Agregat lintas cabang: jumlah komplain per kategori, resolution rate, rata-rata hari selesai, jumlah eskalasi, approval rate per kepala mekanik/cabang (untuk deteksi pola persetujuan yang terlalu longgar) |
| Konfigurasi Master Kategori | Super Admin | CRUD kategori komplain + pemetaan PIC default |

## 10. Acceptance Criteria

- Given CS menerima komplain dan pelanggan belum tentu datang, When CS mengisi form komplain minimal, Then sistem menyimpan record berstatus "Open" tanpa memerlukan data transaksi servis apa pun.
- Given NOPOL yang sama pernah komplain sebelumnya, When CS mengetik NOPOL di form baru, Then sistem menampilkan riwayat komplain terkait sebagai peringatan.
- Given komplain berkategori REWORK, When CS submit, Then sistem otomatis merutekan ke Kepala Mekanik cabang terkait sebagai PIC.
- Given komplain berkategori non-REWORK, When CS submit, Then sistem otomatis merutekan ke Kepala Cabang sebagai PIC, tanpa melalui Kepala Mekanik.
- Given Kepala Mekanik menyusun usulan tanpa mengisi mekanik pelaksana, When ia submit usulan "Terima", Then sistem menolak dan meminta field mekanik pelaksana diisi.
- Given Kepala Cabang memilih "Minta Revisi", When jumlah revisi sudah mencapai 2 kali, Then permintaan revisi berikutnya otomatis mengubah status menjadi "Eskalasi Manajemen", bukan kembali ke Kepala Mekanik.
- Given Kepala Cabang menutup komplain non-REWORK sebagai "Selesai", When field "Tindak Lanjut Penanganan" kosong, Then sistem menolak penyimpanan.
- Given akun dengan role Kepala Cabang Cabang A, When mencoba mengakses data komplain Cabang B, Then sistem menolak akses.
- Given akun dengan role CS atau Mekanik, When mencoba mengubah status menjadi "Selesai" atau "Ditutup - Ditolak" secara langsung, Then sistem menolak aksi tersebut di level backend (bukan hanya disembunyikan di UI).

## 11. Skenario Pengujian (Test Scenarios)

| Jenis | Skenario |
|---|---|
| Positif | Input komplain REWORK lengkap → status Open → PIC Kepala Mekanik → usulan Terima → approve Kepala Cabang → Dijadwalkan → Selesai |
| Positif | Input komplain HARGA → PIC Kepala Cabang → isi tindak lanjut → Selesai (tanpa approval berjenjang) |
| Negatif | Submit form tanpa nama/no HP → sistem block, pesan field wajib |
| Negatif | Kepala Cabang tutup status non-REWORK tanpa isi tindak lanjut → sistem block |
| Batas (Boundary) | Revisi usulan REWORK ke-2 masih kembali ke Kepala Mekanik; revisi ke-3 otomatis eskalasi ke Manajemen |
| Eksepsi | Dua CS input komplain untuk NOPOL sama di waktu berdekatan → sistem tampilkan peringatan riwayat, tidak otomatis membuat duplikat tanpa notifikasi |
| Otorisasi | Role CS mencoba mengakses endpoint ubah status "Selesai" langsung (bypass UI) → ditolak di backend |
| Otorisasi | Kepala Cabang Cabang A mencoba melihat data Cabang B → ditolak |
| Recovery | Koneksi terputus saat submit form → tidak menghasilkan komplain hilang maupun submit ganda (idempotent) |
| Aging | Komplain dengan 1x revisi tetap menghitung umur dari tanggal lapor pertama, bukan reset dari tanggal revisi |

## 12. Non-Functional Requirements

| Aspek | Ketentuan |
|---|---|
| Perangkat | Web app, prioritas akses via PC/laptop di cabang (sesuai kondisi kerja CS saat ini). Tampilan mobile-friendly untuk Kepala Cabang/Kepala Mekanik/Manajemen adalah nilai tambah, bukan syarat wajib Fase 1. |
| Browser | Browser modern (Chrome direkomendasikan). Tidak perlu dukungan browser lama. |
| Jumlah pengguna bersamaan | Estimasi rendah (skala bisnis multi-cabang kecil-menengah, bukan ratusan user bersamaan) — tidak perlu arsitektur high-concurrency yang mahal. |
| Waktu respon | Form input dan list dasar harus tampil dalam hitungan detik pada koneksi internet normal cabang. Tidak perlu SLA performa enterprise. |
| Ketersediaan | Jam operasional bengkel (bukan syarat 24/7 uptime enterprise-grade); downtime singkat di luar jam kerja dapat diterima. |

## 13. Asumsi & Dependency

1. **Koneksi internet stabil di setiap cabang menjadi syarat operasional** — sistem berbasis web, tidak ada mode offline di Fase 1. Ini konsekuensi langsung dari keputusan meninggalkan aplikasi desktop lokal; cabang perlu memastikan koneksi memadai sebelum go-live.
2. **Setiap pengguna wajib punya akun login individual**, bukan akun bersama per cabang — ini syarat mutlak agar audit trail (siapa input, siapa approve, siapa revisi) valid dan bisa dipertanggungjawabkan.
3. Data referensi nomor nota masih manual (belum tervalidasi otomatis ke sistem lain) sampai Fase 2 berjalan.
4. Tidak ada kebutuhan integrasi pihak ketiga (WA Business API, email gateway, dll) di Fase 1.

## 14. Data Privasi & Keamanan

1. Data pelanggan yang disimpan dibatasi pada yang benar-benar diperlukan: nama, no HP, nopol. Tidak perlu data pribadi lain (KTP, alamat lengkap, dll) kecuali ada kebutuhan bisnis spesifik yang belum disebutkan.
2. Fitur **export data (Excel, data only — tanpa formatting laporan)** hanya tersedia untuk role **Manajemen**. Kepala Cabang/Kepala Mekanik/CS tidak memiliki akses export, untuk membatasi penyebaran data pelanggan keluar sistem.
3. Setiap aksi export dicatat di log (siapa, kapan, cakupan data apa) — kontrol minimal murah untuk jaga-jaga kalau ada kebocoran data ke depan, tanpa membebani operasional harian.
4. Akses dibatasi sesuai role & cabang seperti diatur di Bagian 4 — tidak ada perubahan tambahan di luar itu.

## 15. Success Metrics (Definisi Keberhasilan)

Dipakai untuk evaluasi setelah go-live, bukan bagian dari kontrak fungsional ke IT:

| Metrik | Target awal (usulan) |
|---|---|
| Cakupan pencatatan komplain | 100% komplain (semua kategori) tercatat di sistem dalam 2 minggu pertama operasional, termasuk yang tidak berujung kedatangan pelanggan |
| Penghapusan dual entry | Tidak ada lagi pencatatan paralel di Excel setelah 1 bulan go-live |
| Komplain menggantung | Tidak ada komplain berstatus "Open"/"Diajukan" lebih dari 7 hari tanpa aksi PIC, terpantau lewat dashboard |
| Kepatuhan field wajib | 0% status "Selesai" tersimpan tanpa field wajib (tindak lanjut/biaya rework) terisi — divalidasi sistem, bukan manual |

## 16. Catatan Timeline & Risiko Jadwal

Target UAT: **20 September 2026 — ini target awal, bukan deadline mati.** Kurang dari 10 hari sejak dokumen ini diserahkan — realistis untuk memulai development, tapi ketat untuk seluruh scope Fase 1 sudah siap UAT penuh. Usulan mitigasi risiko jadwal:

1. **Minta estimasi IT dulu terhadap tanggal ini**, jangan asumsikan tanggal ini otomatis bisa dicapai — ini bagian dari langkah "IT balas dengan estimasi + pertanyaan" yang sudah disarankan sebelumnya.
2. **Jika waktu tidak cukup untuk seluruh scope**, prioritaskan UAT bertahap:
   - **UAT tahap 1 (prioritas, harus siap 20 September):** alur inti — input komplain semua kategori, routing PIC otomatis, alur REWORK dengan maker-checker dasar, closing non-REWORK dengan tindak lanjut wajib.
   - **UAT tahap 2 (boleh menyusul):** batas revisi otomatis + eskalasi Manajemen, dashboard agregat, export Excel, konfigurasi master kategori oleh Super Admin (bisa disiapkan manual oleh developer di awal tanpa UI admin dulu).
3. Jangan korbankan kontrol maker-checker atau field wajib demi kejar tanggal — itu bagian yang paling berisiko secara operasional kalau disederhanakan diam-diam oleh developer karena tekanan waktu.

## 17. Catatan untuk Fase 2 (Tidak Dikerjakan Sekarang)

Fase 1 dirancang sebagai fondasi platform web, dengan prinsip: **data setiap komplain masuk ke database sesuai kategori** (tabel inti + field kondisional). Ini memudahkan penyambungan dengan modul transaksi/nota di fase 2, ketika aplikasi desktop di tiap cabang akan digantikan sepenuhnya oleh sistem berbasis web.

Fase 2 **sengaja ditunda** sampai modul komplain ini terbukti stabil di lapangan (operasional minimal 1–2 bulan tanpa masalah signifikan). Breakdown timeline fase 2 akan disusun terpisah, dan memerlukan informasi tambahan: jumlah cabang, kesamaan/perbedaan software desktop antar cabang, dan ketersediaan akses data (API/database) dari aplikasi desktop yang ada.

## 18. Riwayat Keputusan (Decision Log)

| Keputusan | Final |
|---|---|
| Cakupan komplain | Semua jenis, bukan hanya rework |
| Kategori komplain | REWORK, HARGA, SIKAP, ANTRIAN, FASILITAS, LAINNYA |
| PIC per kategori | REWORK → Kepala Mekanik + approval Kepala Cabang; lainnya → Kepala Cabang langsung |
| Wewenang closing/reject | Kepala Cabang, berdasarkan usulan Kepala Mekanik (untuk REWORK) |
| Jalur revisi usulan | Ada, maksimal 2 kali, revisi ke-3 eskalasi ke Manajemen |
| Batas akses cabang | Kepala Cabang hanya cabang sendiri; Manajemen lintas cabang |
| Pengelola master kategori | Super Admin |
| Field wajib "Tindak Lanjut" (non-REWORK) | Wajib sebelum status "Selesai" |
| Closing non-REWORK | Kepala Cabang langsung, tanpa approval berjenjang |
| Referensi nomor nota | Input manual teks bebas (mengacu ke nota servis terakhir kendaraan), belum tervalidasi otomatis |
| Migrasi data lama | Tidak dilakukan — sistem dimulai dari kosong |
| Integrasi aplikasi desktop | Ditunda ke Fase 2, prinsip dasar arsitektur data sudah disiapkan di Fase 1 |
| Reminder otomatis | Ditunda ke fase berikutnya |
| Perangkat input CS | PC/laptop |
| Target UAT | 20 September 2026 — target awal, bukan deadline mati (lihat Bagian 16 untuk catatan risiko jadwal) |
| Export data | Excel, data only, akses terbatas role Manajemen |
