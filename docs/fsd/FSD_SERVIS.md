# Functional Specification Document — Modul Servis

**Versi:** 1.0 Draft
**Tanggal:** 2026-07-04
**Status:** Menunggu approval
**Referensi:** `docs/audit/ANALISIS_FITMOTOR_APP_PROSES_BISNIS.md`, memory `audit_tahap2_servis` (temuan T-01 s/d T-05, I-01, I-02), `docs/superpowers/specs/2026-07-02-workorder-kombinasi-design.md`

**Decision yang mengikat dokumen ini** (final, tidak dibuka ulang):
- Servis adalah transaksi terpisah dari Penjualan (`tblservice` vs `tblpenjualanheader`) — konsisten dengan Access lama (T-01). Tidak digabung, hanya disatukan di laporan.
- Komisi mekanik & admin dibahas di dokumen ini (bukan dipisah ke FSD Payroll), karena kalkulasinya melekat langsung ke lifecycle work order.
- Program Gratis Cuci Motor dan Reminder Ganti Oli by KM dibahas lengkap di sini (trigger dan output keduanya), bukan didelegasikan ke `FSD_CRM.md`.
- Alarm perubahan harga beli (7 status) **di luar scope** — masuk `FSD_PENGADAAN.md` / `FSD_INVENTORY.md` (belum ditulis).
- Skema tier membership (3-level kunjungan vs 4-level nominal) **di luar scope** — keputusan owner ada di `FSD_MEMBERSHIP.md`.

---

## 1. Ringkasan & Tujuan

Modul Servis mendefinisikan siklus hidup satu transaksi servis kendaraan — dari pelanggan datang sampai selesai dibayar — beserta tiga proses finansial yang melekat padanya: HPP sparepart yang dipakai, komisi mekanik/admin, dan reward pelanggan (voucher cuci gratis, reminder ganti oli).

**Masalah yang diselesaikan** (bukti dari audit, bukan hipotetis):
- Kartu stok tidak mencakup pemakaian barang di servis (T-02) — potensi selisih stok fisik vs sistem tidak terdeteksi.
- Status servis Access hanya 1 level final (`Status='3'`, 99.97% record), sementara web base sudah punya 5 state tapi sempat salah pasang `status_servis='selesai'` padahal seharusnya `'bayar'` setelah kasir lunas (T-04, sudah diperbaiki 2026-06-25 — didokumentasikan di sini sebagai baseline resmi, bukan dikerjakan ulang).
- Komisi mekanik/admin dihitung ulang tiap kali direvisi di Access, tidak pernah disimpan sebagai catatan permanen (T-05) — web base sudah punya kolom `persen_mekanik1-4`/`persen_admin1-2` di `tblservice`, tapi formula final (jasa 20%, barang 5%, admin jasa 5%, admin barang 5%) belum divalidasi lengkap terhadap struktur "Admin Penjualan" yang terpisah dari "Admin Servis".
- Dua fitur customer-facing yang pernah berjalan di Access dan sama sekali tidak ada padanannya di web base: voucher cuci gratis berbasis poin servis, dan reminder ganti oli berbasis interval KM (bukan tanggal).
- Cancel/hapus servis tidak mengembalikan stok (I-01), dan garansi cuma dideteksi dari pattern teks di kolom keterangan (I-02) — keduanya gap nyata, belum tentu jadi prioritas fix, tapi harus tercatat resmi.

## 2. Ruang Lingkup

**In scope:**
- State machine work order servis (datang → diproses → selesai → bayar, plus cancel) — didokumentasikan as-is dari kolom `status_servis` di `tblservice`.
- Work Order kombinasi (gabung beberapa WO jadi satu, item gratis per-baris) — fitur yang sudah diimplementasikan 2026-07-02, didokumentasikan sebagai bagian resmi lifecycle.
- HPP servis: formula FIFO + pengaman ACUAN_PKK (harga acuan minimum).
- Komisi mekanik (jasa & barang) dan komisi admin (admin servis vs admin penjualan).
- Program Gratis Cuci Motor (poin dari kombinasi jasa, voucher 14 hari).
- Reminder Ganti Oli berbasis interval KM.
- Integrasi kartu stok — servis harus muncul di laporan stok (gap T-02).
- Gap I-01 (stok tidak restore saat cancel) dan I-02 (garansi cuma text pattern) — dicatat sebagai temuan terbuka, bukan otomatis jadi item wajib fix.

**Out of scope** (dibahas di FSD terpisah):
- Alarm perubahan harga beli / harga jual → `FSD_PENGADAAN.md` / `FSD_INVENTORY.md`.
- Skema tier membership final (3-level vs 4-level) → `FSD_MEMBERSHIP.md`.
- Dashboard 360 pelanggan, broadcast WA → `FSD_CRM.md`.
- Detail struktur kendaraan (plat, riwayat kepemilikan) → `FSD_KENDARAAN.md`.

## 3. Aktor & Role

| Aktor | Hak Akses di Modul Ini |
|---|---|
| CS / Admin Servis | Input work order, catat keluhan, assign mekanik, input jasa/barang, tandai selesai. |
| Mekanik / Kepala Mekanik | Dicatat sebagai pelaksana (mekanik1-4, kepala_mekanik1-2) untuk kalkulasi komisi. Tidak akses sistem langsung (input tetap lewat Admin Servis/CS). |
| Kasir | Proses pembayaran, memicu transisi status `selesai` → `bayar`, memicu insert kartu stok final & komisi. |
| Kepala Cabang / Supervisor | Approve cancel servis, lihat laporan komisi & HPP servis cabangnya. |
| Owner / Admin Pusat | Lihat semua laporan komisi lintas cabang, approve perubahan formula komisi/HPP. |
| Sistem (background job) | Hitung poin cuci gratis, generate reminder ganti oli, hitung HPP FIFO real-time. |

## 4. Glosarium

| Istilah | Arti |
|---|---|
| `no_service` | Primary key transaksi servis di `tblservice`. |
| Work Order (WO) | Satu paket jasa standar (`WO0001` dst) yang bisa diinput ke servis, termasuk WO kombinasi (gabungan beberapa WO). |
| `is_gratis` | Flag di `tbworkorderdetail`/`tbservis_pending_items` — item/baris dengan harga dikunci 0 (bagian promo/paket). |
| HPP FIFO | Harga Pokok Penjualan dihitung dari harga beli batch tertua yang belum habis. |
| ACUAN_PKK | Harga acuan minimum (pengaman) — HPP final tidak boleh lebih rendah dari nilai ini. |
| Komisi Jasa | 20% dari (SubTotal Jasa − Biaya Outsource), dibagi rata jumlah mekanik yang mengerjakan. |
| Komisi Barang | 5% dari Laba Item (harga jual − HPP), dibagi rata jumlah mekanik. |
| Admin Servis | Staf yang mengelola input work order servis — komisi 5% dari jasa bersih + 5% dari laba barang. |
| Admin Penjualan | Staf beda jalur dari Admin Servis, komisi dari transaksi penjualan sparepart di luar servis (bukan bagian modul ini, dicatat agar tidak tertukar). |
| Poin Cuci Gratis | Akumulasi poin dari kombinasi jasa dalam 5 hari terakhir; ≥3 poin → voucher cuci gratis 14 hari. |
| Status Servis | Enum `datang`, `diproses`, `selesai`, `bayar`, `cancel` di kolom `status_servis`. |

## 5. Model Data

### 5.1 Tabel Existing (tidak diubah strukturnya)

- **`tblservice`** — header servis. Kolom signifikan: `no_service`, `status_servis`, `km_skr`, `km_berikut`, `mekanik1-4` (varchar, isi kode mekanik mis. `MK001` — bukan FK constraint formal, tapi konvensi kode dipakai konsisten sejak fix T-03), `biayaM1-4`, `persen_mekanik1-4`, `admin1-2`, `persen_admin1-2`, `persen_kepala_mekanik1-2`, `subtotal_jasa`, `subtotal_item`, `total_akhir`, `tanggal_cancel`, `alasan_cancel`, `biaya_cancel`.
- **`tblservis_jasa`** — detail jasa per servis (`no_service`, `no_item` = kode WO, `harga`, `potongan`, `total`).
- **`tblservis_barang`** — detail sparepart per servis (`no_service`, `no_item`, `quantity`, `harga_jual`, `potongan`).
- **`tbservis_workorder`** — mapping WO ke servis (`no_service`, `kode_wo`, `status_pengerjaan` enum `diproses`/`selesai`/`tidak_selesai`).
- **`tbworkorderdetail`** — detail WO kombinasi, punya kolom `is_gratis` (migrasi 2026-07-02).
- **`tbservis_pending_items`** — item dibawa dari WO ke servis aktif, juga punya `is_gratis`.
- **`tblmekanik`** (view) — `nomekanik`, `nama`, `keahlian`, `status`.
- **`tbstok`** — kartu stok, `tipe='4'` dipakai untuk transaksi keluar servis (fix T-02 sudah jalan via guard double-insert; ada tool `admin-backfill-tbstok-servis.php` untuk data migrasi lama yang belum tercatat).

### 5.2 Tabel Baru (diusulkan)

**`servis_komisi`** — catatan permanen komisi per servis (Access tidak commit, hanya hitung real-time — FSD ini mengusulkan disimpan agar auditable dan tidak berubah kalau servis direvisi setelah dibayar).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_service` | VARCHAR(50) FK → tblservice | |
| `peran` | ENUM('mekanik1','mekanik2','mekanik3','mekanik4','kepala_mekanik1','kepala_mekanik2','admin1','admin2') | |
| `nominal_jasa` | DOUBLE | Komisi dari jasa |
| `nominal_barang` | DOUBLE | Komisi dari laba barang |
| `persen_terpakai` | INT | Snapshot persentase saat dihitung (jaga histori kalau formula berubah) |
| `dihitung_saat` | ENUM('selesai','bayar') | Kapan snapshot diambil |
| `created_at` | TIMESTAMP | |

**`servis_poin_cuci`** — akumulasi poin program cuci gratis.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_pelanggan` | VARCHAR(20) FK | |
| `no_service` | VARCHAR(50) FK | Servis yang menyumbang poin ini |
| `poin` | INT | 1 untuk jasa standar/oli, 3 untuk remap/turun mesin |
| `tanggal` | DATE | |
| `created_at` | TIMESTAMP | |

**`servis_voucher_cuci`** — voucher yang diterbitkan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_pelanggan` | VARCHAR(20) FK | |
| `kode_voucher` | VARCHAR(30) UNIQUE | |
| `tanggal_terbit` | DATE | |
| `tanggal_expired` | DATE | terbit + 14 hari |
| `status` | ENUM('aktif','terpakai','expired') | |
| `no_service_pakai` | VARCHAR(50) NULL | Servis cuci yang memakai voucher ini |

**`servis_reminder_oli`** — jadwal reminder ganti oli per kendaraan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `no_polisi` | VARCHAR(20) FK | |
| `km_terakhir_ganti` | INT | Dari `tblservice.km_skr` saat servis ganti oli |
| `km_target_berikut` | INT | `km_terakhir_ganti` + interval (kelipatan 30, offset 60) |
| `status` | ENUM('menunggu','due','terkirim','selesai') | |
| `updated_at` | TIMESTAMP | |

## 6. State Machine Work Order (as-is + gap)

```
datang → diproses → selesai → bayar
   \          \          \
    ────────── cancel ────
```

- **datang**: servis baru dibuat, belum ada mekanik/jasa diinput.
- **diproses**: mekanik sudah assigned, jasa/barang mulai diinput.
- **selesai**: pengerjaan fisik selesai, belum dibayar. Trigger: staf servis klik "Selesai".
- **bayar**: kasir sudah terima pembayaran. Trigger: kasir klik "Bayar" (fix T-04 — tidak boleh lagi langsung set `selesai` di titik ini).
- **cancel**: bisa terjadi dari state manapun sebelum `bayar`. Butuh `alasan_cancel`, opsional `biaya_cancel` (barang/jasa yang sudah terpakai tapi servis dibatalkan).

**Gap terbuka (dicatat, bukan otomatis wajib fix):**
- **I-01**: transisi ke `cancel` tidak mengembalikan stok barang yang sudah dipotong (`tblservis_barang`). Butuh keputusan: restore otomatis atau tetap manual dengan alasan (kadang barang sudah terpasang di motor, tidak bisa dikembalikan fisik).
- **I-02**: garansi servis tidak punya field formal, hanya terdeteksi dari pola teks (`*GARAN*`, `*KOMPLA*`) di kolom keterangan. Rekomendasi: tambah `is_garansi` + `no_service_asal` (referensi servis awal yang digaransikan) di iterasi berikutnya — belum dirancang detail di FSD ini.

Work Order Kombinasi (2026-07-02) tetap mengikuti state machine yang sama di level header `tblservice` — kombinasi hanya memengaruhi struktur baris `tbworkorderdetail`/`tbservis_pending_items` (multiple WO + `is_gratis` per baris), bukan menambah state baru.

## 7. HPP Servis

**Formula (dikonfirmasi dari `HRGPOKOK_SERVICE` di FITMOTOR APP.mdb):**
```
HPP_final = MAX( MAX(HPP_FIFO, HPP_TBLITEM), ACUAN_PKK )
```

- **HPP_FIFO**: harga beli batch tertua yang belum habis untuk item tersebut — basis utama, dihitung real-time (web base sudah lebih baik dari Access yang batch semi-manual).
- **HPP_TBLITEM**: harga pokok di master barang — pengaman kalau FIFO tidak tersedia/rusak.
- **ACUAN_PKK**: harga acuan tertinggi historis — pengaman terakhir kalau kedua nilai di atas anomali (misal FIFO kebetulan 0 karena data lama corrupt).

**Keputusan implementasi:** web base sudah pakai FIFO real-time (lebih baik dari Access), tapi **belum punya** lapisan pengaman ACUAN_PKK. Ini nice-to-have anti-anomali, bukan blocker — masuk prioritas Sedang.

**Tidak diadopsi dari Access:** fitur "review sebelum commit HPP" / undo (`FR_HPPSTS_UPDATE`, `BATAL_UPDATE_HPP`) — relevan hanya kalau HPP tetap batch. Karena web base sudah real-time, fitur ini kemungkinan tidak diperlukan. **Open question ke owner** di section 12.

## 8. Komisi Mekanik & Admin

**Formula (dari `DATA_INSENTIF_SERVIS` + `MEKANIK_PERSERVIS_PERSEN`):**

| Peran | Basis | Formula |
|---|---|---|
| Mekanik (jasa) | (SubTotal Jasa − Biaya Outsource) | × 20%, dibagi rata: `100 / jumlah_mekanik_kerja` per orang |
| Mekanik (barang) | Laba Item (harga jual − HPP) | × 5%, dibagi rata sama seperti jasa |
| Admin Servis (jasa) | Jasa Bersih | × 5% |
| Admin Servis (barang) | Laba Item | × 5% |
| Admin Penjualan | — | **Di luar servis** — komisi dari transaksi penjualan sparepart biasa, jalur terpisah, tidak dihitung di modul ini |

- Pembagian antar mekanik **selalu rata** (`100% / jumlah_mekanik_kerja`), tidak ada bobot berdasarkan kontribusi kerja — dikonfirmasi dari Access, tidak diusulkan berubah.
- Kolom existing `persen_mekanik1-4`/`persen_admin1-2` di `tblservice` menyimpan **persentase kerja** (dari fitur slider yang sudah dibuat 2026-07-03), bukan persentase komisi finansial — dua hal berbeda yang perlu tetap dipisah: persentase kerja menentukan siapa dapat berapa bagian dari 100%/jumlah_mekanik, sedangkan formula 20%/5% di atas menentukan total pot komisi yang dibagi.
- Snapshot ke `servis_komisi` diambil saat status masuk `bayar` (final), bukan `selesai` — supaya komisi tidak berubah lagi kalau ada revisi setelah kasir lunas. Kalau ada revisi setelah `bayar`, itu di luar alur normal dan butuh approval terpisah (tidak dirancang di FSD ini).

## 9. Program Gratis Cuci Motor

**Trigger (varian yang dipakai — perlu konfirmasi owner mana yang aktif, lihat section 12):**
- Varian 1: dalam 5 hari terakhir, kombinasi jasa (Servis Standar, Gurah Mesin, Remap, Oli apapun) terkumpul ≥3 poin (Remap/Turun Mesin = 3 poin, lainnya = 1 poin).
- Varian 2 (lebih baru): kombinasi servis standar/oli ≥2 poin **dan** total transaksi ≥ Rp143.000.

**Output:** voucher cuci gratis, berlaku 14 hari sejak terbit.

**Alur:**
1. Setiap servis `bayar` (final), sistem hitung poin dari jasa yang diinput → insert ke `servis_poin_cuci`.
2. Background job cek akumulasi poin per pelanggan (5 hari rolling) → kalau memenuhi threshold, generate `servis_voucher_cuci` (status `aktif`).
3. Voucher dipakai saat servis cuci baru — CS input kode voucher, sistem validasi `status='aktif'` dan belum expired, set `harga=0` untuk jasa cuci, update voucher jadi `terpakai` + catat `no_service_pakai`.
4. Voucher lewat 14 hari tanpa dipakai → job harian set status `expired`.

## 10. Reminder Ganti Oli by KM

**Sumber (`INFO_GANTI_OLI`):** dihitung dari interval KM, bukan tanggal kalender — kelipatan 30 (ribu KM, perlu konfirmasi satuan ke owner), offset 60.

**Alur:**
1. Setiap servis yang mencatat jasa "Ganti Oli", sistem ambil `km_skr` → hitung `km_target_berikut` (kelipatan 30 dari titik itu, offset 60) → upsert ke `servis_reminder_oli` per `no_polisi`.
2. Background job harian bandingkan `km_target_berikut` dengan `km_skr` servis terbaru kendaraan tersebut (kalau ada input KM baru dari servis lain) → kalau sudah dekat/lewat, set status `due`.
3. Status `due` memicu notifikasi (kanal notifikasi — WA/lainnya — didefinisikan di `FSD_CRM.md`, di sini hanya definisikan kapan trigger-nya menyala).
4. Setelah pelanggan ganti oli lagi, servis baru reset siklus (`status='selesai'`, `km_terakhir_ganti` diperbarui).

**Terkait tapi tidak diadopsi tanpa keputusan owner:**
- `INFO_TUNEUP` (deteksi pelanggan 3x+ tune-up belum pernah pakai voucher) — kandidat promo, prioritas Rendah.
- `SERVIS_STANDAR_VOUCHER` (anti-abuse 1 plat nomor pakai diskon servis standar lebih dari sekali per periode) — relevan hanya kalau program voucher servis standar itu sendiri masih berjalan.

## 11. Integrasi Kartu Stok

- Servis yang memakai sparepart (`tblservis_barang`) **harus** tercatat di `tbstok` dengan `tipe='4'` — ini sudah jalan (guard double-insert dipasang 2026-06-25, tool `admin-backfill-tbstok-servis.php` tersedia untuk data lama yang belum tercatat).
- **Kewajiban ke depan:** setiap perubahan pada alur input barang servis (termasuk WO kombinasi dengan `is_gratis=1`) harus tetap insert ke `tbstok` — barang gratis tetap **mengurangi stok fisik** meski `harga_jual=0`. Ini poin kritis yang gampang terlewat kalau developer fokus ke harga tanpa cek quantity.
- Retur barang servis (kalau ada, misal typo qty) harus punya jalur restore stok simetris dengan retur penjualan biasa — belum ada bukti alur ini di Access, dianggap belum tentu ada, perlu dicek terpisah di kode existing.

## 12. Open Questions / Decision untuk Owner

| # | Pertanyaan | Kenapa Penting |
|---|---|---|
| 1 | Program Gratis Cuci Motor masih aktif atau sudah dihentikan? Kalau aktif, varian 1 atau varian 2 (atau dua-duanya)? | Menentukan apakah section 9 diimplementasikan sama sekali, dan formula mana yang dipakai. |
| 2 | Reminder ganti oli: satuan "kelipatan 30" itu KM (30.000) atau ribuan KM lain? Perlu konfirmasi ke data asli Access. | Salah satuan bikin reminder muncul di waktu yang salah total. |
| 3 | Perlu fitur "review sebelum commit HPP" / undo seperti `FR_HPPSTS_UPDATE` di Access, meski web base sudah real-time? | Kalau staf masih butuh kontrol manual sebelum HPP final, ini fitur tambahan; kalau tidak, skip. |
| 4 | I-01: cancel servis — stok barang yang sudah dipotong direstore otomatis, atau tetap manual dengan approval? | Menentukan desain detail alur cancel, belum dirancang di FSD ini. |
| 5 | I-02: garansi servis — perlu field formal (`is_garansi`, referensi servis asal) di iterasi berikutnya? | Kalau ya, jadi FSD/perubahan skema terpisah, bukan bagian dokumen ini. |

---

## Urutan Pengerjaan Selanjutnya (usulan)

1. Owner jawab section 12 (5 pertanyaan) — terutama #1 dan #2 karena langsung menentukan scope implementasi.
2. Kalau disetujui: implementasi `servis_komisi` (snapshot komisi permanen) — dampak langsung ke transparansi payroll, risiko teknis rendah.
3. Implementasi program Cuci Gratis (tergantung jawaban #1) atau Reminder Oli (tergantung jawaban #2) — pilih salah satu duluan berdasar mana yang owner anggap lebih mendesak.
4. Lanjut FSD modul berikutnya: Pengadaan (termasuk alarm harga beli yang di-exclude dari sini).
