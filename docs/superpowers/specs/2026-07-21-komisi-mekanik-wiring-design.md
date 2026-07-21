# Wiring Komisi Mekanik — Snapshot ke Tabel (REVISI)

**Tanggal:** 2026-07-21
**Status:** Revisi — spec versi awal (siang) salah premis, dibuang.

## Kenapa ditulis ulang

Draft pertama mengasumsikan tabel `servis_komisi` dan `bagi_hasil_komisi`
belum ada, dan mendesain skema baru dengan pool persen yang
bisa dikonfigurasi per cabang (`kategori`, `persen_pool`, `persen_individu`).

Investigasi ulang (`app/issue_add.php` fungsi `eksekusi_revisi_komisi`,
`app/lap_komisi_mekanik.php`, migrasi
`db/migrations/2026-07-11_crm_tiket_terstruktur.sql`) menemukan:

- Tabel `servis_komisi` **sudah ada di produksi** dan sudah dipakai
  (jalur revisi tiket lewat `issue_add.php`). Skemanya beda total dari
  draft: `peran ENUM('mekanik1'..'admin2')`, `nominal_jasa`,
  `nominal_barang`, `persen_terpakai`, `dihitung_saat
  ENUM('selesai','bayar','revisi_tiket')`, `id_issue_ref`. Tidak ada
  kolom `kategori`/`persen_pool` sama sekali.
- Persentase pool **tidak configurable per cabang** — hardcoded di dua
  tempat independen dengan formula identik:
  - Mekanik/kepala_mekanik: jasa 20%, barang 5%, **dibagi jml_mekanik
    aktif** (slot mekanik1-4 terisi).
  - Admin: jasa 5%, barang 5%, **flat, tidak dibagi**.
  - `laba_barang = SUM(GREATEST(total_baris - qty x hargapokok, 0))`
    per `no_service`, dari `tblservis_barang` JOIN `tblitem.hargapokok`.
- `app/lap_komisi_mekanik.php` adalah laporan **live-recompute** —
  hitung ulang dari `tblservice`+`tblservis_barang` tiap load, sama
  sekali tidak baca `servis_komisi`. Ini sumber angka komisi paralel
  yang sudah berjalan dan dipakai sekarang.
- `servis_komisi` **tidak ada kolom `kd_cabang`**, kunci cuma
  `no_service` — padahal `no_service` terbukti tidak unik lintas cabang
  (temuan kritis 2026-07-19, `project_critical_no_service_not_unique`
  memory, 30rb+ baris dobel).

Tidak ada tabel `bagi_hasil_komisi` — konsep pool configurable per
cabang **tidak dipakai**, dibuang dari scope.

## Keputusan (dikonfirmasi user 2026-07-21)

1. **Tambah kolom `kd_cabang`** ke `servis_komisi` lewat migration baru
   — insert di 3 titik bayar wajib isi `kd_cabang` dari session, supaya
   tidak tabrakan data lintas cabang (mengingat `no_service` tidak
   unik).
2. **`lap_komisi_mekanik.php` TIDAK disentuh** — tetap live-recompute.
   Wiring `servis_komisi` jalan paralel sebagai snapshot/audit-trail,
   bukan pengganti sumber laporan. Migrasi laporan ke baca snapshot
   ditunda sampai data snapshot terbukti konsisten (item terpisah,
   bukan scope sesi ini).

## Tujuan

Insert baris `servis_komisi` (pakai skema **yang sudah ada**, ditambah
`kd_cabang`) di 3 titik pembayaran servis, dengan formula yang sudah
terbukti benar di `lap_komisi_mekanik.php` dan `issue_add.php` — bukan
formula baru.

## Scope

**Termasuk:**
- Migration: `ALTER TABLE servis_komisi ADD COLUMN kd_cabang VARCHAR(10)
  NOT NULL DEFAULT '' AFTER no_service`, plus index
  `idx_komisi_cabang_service (kd_cabang, no_service)`.
- Fungsi shared hitung+insert, dipakai 3 titik bayar:
  - `app/servis-reguler-byr.php` (blok `btnsimpan`)
  - `app/servis-input-reguler-jemput.php` (blok `btnbayar`)
  - `app/servis-garansi.php` (blok pembayaran)
- Insert 1 baris per slot terisi (mekanik1-4, kepala_mekanik1-2,
  admin1-2) dengan `dihitung_saat='bayar'`, `id_issue_ref=NULL`.

**Tidak termasuk:**
- Perubahan `lap_komisi_mekanik.php` (tetap live-recompute).
- Pool persen configurable per cabang (tidak ada kebutuhan terbukti —
  formula 20/5/5/5 hardcoded dipakai konsisten di 2 tempat produksi).
- Migrasi data historis (servis yang sudah dibayar sebelum fitur aktif
  tidak dapat baris `servis_komisi`).
- Halaman master baru — tidak ada config untuk di-CRUD (persen
  hardcoded, bukan per-cabang).

## Desain

### 1. Migration

```sql
ALTER TABLE servis_komisi
  ADD COLUMN kd_cabang VARCHAR(10) NOT NULL DEFAULT '' AFTER no_service,
  ADD INDEX idx_komisi_cabang_service (kd_cabang, no_service);
```

### 2. Formula (copy exact dari lap_komisi_mekanik.php / issue_add.php)

```
jml_mekanik_aktif = COUNT slot mekanik1..4 yang terisi (non-null, non-empty)

laba_barang = SUM(GREATEST(sb.total - sb.quantity * ti.hargapokok, 0))
              FROM tblservis_barang sb LEFT JOIN tblitem ti
              WHERE sb.no_service = ? AND sb.kd_cabang = ?

per slot mekanik{n} / kepala_mekanik{n} terisi:
  nominal_jasa   = subtotal_jasa * 0.20 / jml_mekanik_aktif
  nominal_barang = laba_barang   * 0.05 / jml_mekanik_aktif

per slot admin{n} terisi:
  nominal_jasa   = subtotal_jasa * 0.05
  nominal_barang = laba_barang   * 0.05
```

`persen_terpakai` diisi dari `persen_mekanik{n}`/`persen_admin{n}` yang
ada di `tblservice` untuk baris itu (bagian-dalam-peran, bukan pool) —
disimpan sebagai snapshot, tidak dipakai untuk hitung nominal Rupiah.

### 3. Titik wiring (3 file) — KOREKSI 2026-07-21 siang

Investigasi ulang menemukan nama file di draft awal salah.
`servis-reguler-byr.php` (blok `btnsimpan` baris 537) **BUKAN** titik
yang benar — blok itu cuma set `status_servis='bayar'`, tidak pernah
menangkap `mekanik1-4`/`admin1-2`/`kepala_mekanik1-2`. Titik yang benar,
dikonfirmasi baca kode:

- **`app/servis-input-reguler.php`** baris ~693-743 (UPDATE
  `tblservice` status='2', capture kepala_mekanik/admin/mekanik) —
  **guard `kd_cabang` TIDAK ADA** di WHERE (baris 743).
- **`app/servis-input-reguler-jemput.php`** baris ~2531-2573 (sama
  polanya, `status_servis='selesai'`) — **guard `kd_cabang` TIDAK ADA**
  (baris 2573).
- **`app/servis-garansi.php`** baris ~1066-1109 — guard `kd_cabang`
  **SUDAH ADA** (`AND kd_cabang = '$kd_cabang'`, baris 1109).

Keputusan user: sekalian fix guard `kd_cabang` yang hilang di 2 file
pertama, di commit yang sama dengan wiring komisi (satu blok kode yang
sama disentuh untuk dua alasan).

Fungsi shared di `app/_include_komisi_snapshot.php`
(`snapshot_komisi_servis($koneksi, $no_service, $kd_cabang)`),
dipanggil **setelah** UPDATE `tblservice` sukses (setelah guard
`kd_cabang` ditambah/dipastikan ada). Fungsi:
1. SELECT ulang `tblservice` (subtotal_jasa, mekanik1-4,
   kepala_mekanik1-2, admin1-2, persen_*) by `no_service AND kd_cabang`
   — data terbaru pasca-UPDATE, bukan nilai awal request.
2. Hitung `laba_barang` dari `tblservis_barang` JOIN `tblitem`.
3. Loop tiap slot terisi, INSERT baris `servis_komisi` dengan
   `kd_cabang` diisi.
4. Return jumlah baris ter-insert (buat logging), tidak throw — insert
   gagal dicatat `error_log()`, tidak menggagalkan alur bayar (konsisten
   pola existing, tidak ada transaction wrapper di 3 file ini).

### 4. Error handling

- Insert gagal -> `error_log()`, alur bayar tetap lanjut.
- `jml_mekanik_aktif == 0` (servis tanpa mekanik ditugaskan, kasus
  garansi tertentu) -> skip insert baris mekanik, admin tetap insert
  kalau slot admin terisi (admin tidak dibagi jml_mekanik).

### 5. Testing

Manual E2E via browser (tidak ada automated test suite di project):
1. Bayar 1 servis reguler dengan mekanik+admin terisi -> cek baris
   `servis_komisi` (dengan `kd_cabang` benar) muncul, nominal cocok
   manual hitung.
2. Bayar 1 servis jemput, 1 servis garansi -> sama.
3. Bandingkan total nominal snapshot vs angka yang tampil di
   `lap_komisi_mekanik.php` untuk servis yang sama -> harus sama persis
   (bukti formula konsisten).
4. `php -l` semua file yang diubah.
5. Cek 2 servis beda cabang dengan `no_service` sama (kalau ada di data
   test) -> pastikan insert servis_komisi tidak tercampur, masing-masing
   `kd_cabang` benar.
