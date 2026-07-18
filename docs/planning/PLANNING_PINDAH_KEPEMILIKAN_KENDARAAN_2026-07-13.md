# Planning Teknis — Pindah Kepemilikan Kendaraan Saat Motor Dijual

**Tanggal:** 2026-07-13  
**Sumber utama:** `docs/fsd/FSD_KENDARAAN.md`, dependency `docs/fsd/FSD_CUSTOMER.md`  
**Status:** Draft implementasi teknis + migrasi SQL siap review

---

## 1. Tujuan

Menyediakan alur resmi saat motor dijual ke pemilik baru tanpa merusak histori servis kendaraan dan tanpa membocorkan histori customer lama ke customer baru.

**Target hasil akhir:**
- Kendaraan punya identitas tetap (`id_kendaraan`) walau pemilik berganti.
- Riwayat servis teknis tetap nyambung lifetime per kendaraan.
- Riwayat customer-facing difilter sesuai periode kepemilikan.
- Proses pindah kepemilikan diblokir bila masih ada servis menggantung/belum lunas.
- Ada jejak audit siapa yang mengajukan, menyetujui, dan mengeksekusi.

---

## 2. Masalah Existing

Struktur existing saat ini:
- `tblkendaraan` pakai `nopolisi` sebagai PK.
- `tblkendaraan.pemilik` hanya teks bebas, bukan FK.
- `tblservice.no_polisi` dan `tblservice.no_pelanggan` hanya snapshot transaksi saat itu.
- Tidak ada tabel kepemilikan bertanggal.
- Tidak ada cara resmi membedakan:
  - typo nopol,
  - ganti plat asli,
  - motor dijual ke pemilik baru.

Dampak:
- Histori motor dan histori customer bercampur.
- Duplikat pelanggan/kendaraan sulit dibedakan dengan kasus jual beli motor yang valid.
- Customer baru bisa salah terlihat “mewarisi” histori customer lama bila tidak dipisah.

---

## 3. Desain Solusi

### 3.1 Prinsip inti

- `id_kendaraan` = identitas fisik motor, immutable.
- `nopolisi` = atribut yang bisa berubah, bukan identity utama.
- `nopelanggan` = identity customer, bisa berganti sebagai pemilik kendaraan.
- Histori teknis mengikuti `id_kendaraan`.
- Histori customer-facing mengikuti `kepemilikan_kendaraan`.

### 3.2 Tabel baru / perubahan

#### A. Update `tblkendaraan`
Tambah kolom:
- `id_kendaraan BIGINT UNSIGNED NULL UNIQUE`

Fungsi:
- menjadi surrogate key tetap untuk motor fisik.

#### B. `kendaraan_plat_history`
Mencatat riwayat plat aktif/non-aktif.

Dipakai untuk:
- ganti plat asli,
- koreksi typo,
- melacak plat lama saat pencarian histori.

#### C. `kepemilikan_kendaraan`
Mencatat pemilik kendaraan per periode tanggal.

Dipakai untuk:
- menentukan siapa owner aktif,
- menentukan histori customer-facing yang boleh terlihat,
- mendukung motor dijual berkali-kali.

#### D. `permintaan_pindah_kepemilikan_kendaraan`
Tabel request + approval.

Dipakai untuk:
- pengajuan CS/Admin,
- approval Supervisor/Owner,
- audit blocker,
- catatan alasan jual beli / koreksi.

#### E. `statistik_kendaraan`
Denormalisasi statistik lifetime per kendaraan.

Dipakai untuk:
- dashboard teknis,
- histori servis kendaraan,
- kartu kendaraan.

---

## 4. Flow Operasional

### 4.1 Flow user

1. CS/Admin buka halaman **Pindah Kepemilikan Kendaraan**.
2. User cari motor berdasarkan:
   - nopol aktif,
   - nopol lama,
   - `id_kendaraan`,
   - no rangka / no mesin bila ada.
3. Sistem tampilkan:
   - data kendaraan,
   - pemilik aktif sekarang,
   - jumlah histori servis,
   - status blocker transaksi.
4. User pilih customer baru.
5. User isi alasan pindah kepemilikan.
6. Sistem buat request status `draft` / `diajukan`.
7. Supervisor review.
8. Sistem cek ulang blocker:
   - ada `tblservice.status_servis IN ('datang','diproses','selesai')` untuk kendaraan itu?
   - jika ya: reject/hold.
9. Jika disetujui:
   - tutup row kepemilikan lama (`tanggal_akhir`, `is_current=0`),
   - buat row kepemilikan baru (`tanggal_mulai`, `is_current=1`, `sumber='jual_beli'`),
   - update denormalisasi owner aktif di `statistik_kendaraan`.
10. Servis berikutnya memakai owner baru.

### 4.2 Flow data

**Sebelum dijual**
- `kepemilikan_kendaraan`
  - row A: `id_kendaraan=123`, `nopelanggan=CUST001`, `tanggal_akhir=NULL`, `is_current=1`

**Sesudah dijual**
- row A ditutup:
  - `tanggal_akhir=2026-07-13`, `is_current=0`
- row B dibuat:
  - `id_kendaraan=123`, `nopelanggan=CUST777`, `tanggal_mulai=2026-07-13`, `tanggal_akhir=NULL`, `is_current=1`

---

## 5. Rule Implementasi Wajib

### 5.1 Blocker bisnis

Pindah kepemilikan **harus gagal** jika kendaraan punya transaksi servis belum selesai/lunas:
- `tblservice.status_servis IN ('datang','diproses','selesai')`

**Tidak blocker** bila:
- semua transaksi `bayar` atau `cancel`

### 5.2 Visibility histori

#### Internal teknis
Boleh lihat semua histori kendaraan berdasarkan `id_kendaraan`.

#### Customer lama
Hanya boleh lihat histori transaksi pada rentang:
- `tanggal >= tanggal_mulai_kepemilikan`
- `tanggal <= tanggal_akhir_kepemilikan` (atau tak terbatas bila current waktu itu)

#### Customer baru
Hanya boleh lihat histori sejak `tanggal_mulai` kepemilikannya.

### 5.3 Merge customer interaction

Jika nanti ada merge pelanggan:
- `kepemilikan_kendaraan.nopelanggan` harus ikut direpoint ke customer master.
- `permintaan_pindah_kepemilikan_kendaraan.nopelanggan_lama/baru` boleh tetap historis atau ikut direpoint sesuai kebutuhan audit.

---

## 6. Strategi Backfill Data Lama

### 6.1 Isi `id_kendaraan`

Semua row existing `tblkendaraan` diberi `id_kendaraan` bertahap.

Strategi awal aman:
- satu row `tblkendaraan` lama = satu `id_kendaraan`
- belum mencoba dedup motor fisik lintas nopol otomatis

### 6.2 Isi `kendaraan_plat_history`

Buat 1 row awal untuk setiap kendaraan existing:
- `nopolisi = tblkendaraan.nopolisi`
- `tanggal_mulai = MIN(tblservice.tanggal)` bila ada
- fallback `CURDATE()` bila tidak ada histori servis
- `is_current=1`
- `alasan='kendaraan_baru'`

### 6.3 Isi `kepemilikan_kendaraan`

Buat 1 row awal untuk setiap kendaraan existing:
- cari `nopelanggan` paling akhir dari `tblservice` berdasarkan `no_polisi`
- bila tidak ada, fallback ke `tblkendaraan.pemilik` jika cocok ke `tblpelanggan.nopelanggan`
- `tanggal_mulai = MIN(tblservice.tanggal)` untuk kendaraan tsb bila ada
- `is_current=1`
- `sumber='migrasi_backfill'`

### 6.4 Batasan backfill

Backfill ini **tidak bisa menyimpulkan histori owner lama yang benar** untuk data legacy yang sudah bercampur. Karena itu:
- hasil migrasi cukup akurat untuk owner aktif saat ini,
- histori kepemilikan lama sebelum cutover perlu perbaikan manual per kasus penting.

---

## 7. Tahap Implementasi Disarankan

### Tahap 1 — Schema & backfill
- Tambah `tblkendaraan.id_kendaraan`
- Buat 4 tabel baru
- Backfill `id_kendaraan`
- Backfill `kendaraan_plat_history`
- Backfill `kepemilikan_kendaraan`
- Backfill `statistik_kendaraan`

### Tahap 2 — Read path
- Buat view owner aktif kendaraan
- Ubah query pencarian kendaraan agar bisa baca `id_kendaraan`
- Ubah kartu histori kendaraan agar lifetime by `id_kendaraan`
- Ubah tampilan customer-facing agar filter by periode kepemilikan

### Tahap 3 — Write path
- Form request pindah kepemilikan
- Validasi blocker transaksi menggantung
- Approval supervisor
- Eksekusi tutup owner lama + buka owner baru

### Tahap 4 — Hardening
- Tambah unique/index sekunder untuk `no_rangka` / `no_mesin` bila diputuskan
- Tambah audit UI
- Tambah laporan kendaraan pindah tangan

---

## 8. Query Kunci yang Akan Dipakai App

### Owner aktif kendaraan
```sql
SELECT kk.*
FROM kepemilikan_kendaraan kk
WHERE kk.id_kendaraan = ? AND kk.is_current = 1
LIMIT 1;
```

### Cek blocker servis menggantung
```sql
SELECT COUNT(*) AS total_blocker
FROM tblservice s
JOIN tblkendaraan k ON k.nopolisi = s.no_polisi
WHERE k.id_kendaraan = ?
  AND s.status_servis IN ('datang','diproses','selesai');
```

### Histori customer-facing
```sql
SELECT s.*
FROM tblservice s
JOIN tblkendaraan k ON k.nopolisi = s.no_polisi
JOIN kepemilikan_kendaraan kk ON kk.id_kendaraan = k.id_kendaraan
WHERE kk.nopelanggan = ?
  AND kk.id_kendaraan = ?
  AND s.tanggal >= kk.tanggal_mulai
  AND (kk.tanggal_akhir IS NULL OR s.tanggal <= kk.tanggal_akhir);
```

### Histori teknis lifetime kendaraan
```sql
SELECT s.*
FROM tblservice s
JOIN tblkendaraan k ON k.nopolisi = s.no_polisi
WHERE k.id_kendaraan = ?
ORDER BY s.tanggal DESC, s.jam DESC, s.no_service DESC;
```

---

## 9. Risiko & Mitigasi

### Risiko 1 — Data lama `pemilik` bukan kode pelanggan
Mitigasi:
- fallback pakai servis terakhir sebagai owner aktif
- simpan backlog manual review

### Risiko 2 — Satu motor fisik punya 2 row `tblkendaraan`
Mitigasi:
- tahap awal jangan auto-merge
- flag manual review via no rangka/no mesin sama

### Risiko 3 — Customer baru bisa lihat histori lama
Mitigasi:
- seluruh query customer-facing wajib pakai `kepemilikan_kendaraan`
- jangan query langsung `tblservice WHERE no_pelanggan=?`

### Risiko 4 — Pindah tangan saat servis masih berjalan
Mitigasi:
- blocker keras di approval dan eksekusi
- re-check blocker saat approve, bukan hanya saat request dibuat

---

## 10. Acceptance Test Teknis

### Skenario A — Motor dijual normal
- owner lama aktif
- tidak ada servis menggantung
- supervisor approve
- owner lama tertutup
- owner baru aktif
- histori teknis tetap utuh
- histori customer baru hanya sejak tanggal beli

### Skenario B — Motor dijual tapi servis belum lunas
- ada `status_servis='diproses'`
- request boleh dibuat atau langsung ditolak (pilihan UX)
- approve harus gagal
- tidak ada perubahan owner aktif

### Skenario C — Motor dijual lalu dibeli lagi oleh owner lama
- sistem membuat row kepemilikan baru lagi
- histori per periode tetap benar

### Skenario D — Ganti plat tanpa ganti owner
- `kepemilikan_kendaraan` tidak berubah
- `kendaraan_plat_history` bertambah row baru

---

## 11. Output File Implementasi Tahap Ini

1. `db/migrations/2026-07-13_kendaraan_pindah_kepemilikan.sql`
   - schema + backfill dasar
2. Dokumen ini
   - panduan implementasi tahap lanjut (UI, approval, query app)

---

## 12. Keputusan yang Masih Perlu Dikonfirmasi

1. Approval FR-05 cukup Supervisor atau perlu Owner juga?
2. Request pindah kepemilikan dibuat langsung `diajukan` atau mulai dari `draft`?
3. `no_rangka` / `no_mesin` mau dijadikan unique helper index atau tetap review manual?
4. Untuk data lama yang owner historisnya tidak akurat, apakah perlu menu “koreksi histori kepemilikan” setelah migrasi?

