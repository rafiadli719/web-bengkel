# Wiring Komisi Mekanik — Snapshot ke Tabel

**Tanggal:** 2026-07-21
**Status:** Disetujui

## Latar Belakang

Temuan audit Access lama (T-05, `audit_tahap2_servis` memory): komisi
mekanik/admin dihitung real-time saat laporan dibuka, tidak pernah
di-*commit* ke tabel permanen. Kalau data servis direvisi belakangan,
angka komisi ikut berubah — tidak ada snapshot historis.

Investigasi ulang di web app (2026-07-21) menemukan:

- Tiga jalur pembayaran servis (**reguler** `servis-reguler-byr.php`,
  **jemput** `servis-input-reguler-jemput.php` btnbayar, **garansi**
  `servis-garansi.php` btnsimpan) semuanya sudah menangkap *siapa*
  mengerjakan servis dan *pembagian per-orang* lewat template bersama
  `app/_template/panel-kiri-kasir.php`: kolom `kepala_mekanik1/2`,
  `admin1/2`, `mekanik1-4` di `tblservice`, masing-masing dengan
  `persen_kepala_mekanik1/2`, `persen_admin1/2`, `persen_mekanik1-4`.
- Tiga grup persen (`km`, `admin`, `mekanik`) masing-masing **independen
  menjumlah 100%** — ini hanya pembagian *di dalam* peran (contoh:
  mekanik1 60%, mekanik2 40% dari "jatah mekanik"), BUKAN besaran pool
  Rupiah per peran terhadap omset.
- Besaran pool per peran (dari Access: jasa->mekanik 20%, jasa->admin 5%,
  barang->mekanik 5%, barang->admin 5%) **tidak ada di kode manapun** saat
  ini — gap ini yang membuat konversi ke Rupiah belum bisa dilakukan.
- Tidak ada tabel snapshot komisi sama sekali di skema
  (`tools/sql/fitmotor_dbbengkel_FIXED_V7.sql` dicek, tidak ketemu).

## Tujuan

1. Simpan snapshot komisi (Rupiah final, per orang, per servis) saat
   pembayaran servis selesai — supaya revisi data servis di kemudian
   hari tidak mengubah komisi yang sudah tercatat.
2. Sediakan cara admin mengatur besaran pool per peran (mekanik/admin)
   per kategori (jasa/barang), per cabang — karena kebutuhan boleh beda
   antar cabang.
3. Tidak mengubah UI input mekanik/persen yang sudah ada — murni
   menambah langkah snapshot di titik pembayaran.

## Scope

**Termasuk:**
- Tabel baru `bagi_hasil_komisi` (config pool per cabang).
- Tabel baru `servis_komisi` (snapshot final).
- Halaman master CRUD `app/master-bagi-hasil-komisi.php` (pola sama
  seperti `app/master-tarif-jemput.php`) + permission RBAC
  `master_komisi_manage` + entry menu.
- Fungsi/logic hitung & insert `servis_komisi`, dipanggil dari 3 titik
  pembayaran: `servis-reguler-byr.php` (btnsimpan),
  `servis-input-reguler-jemput.php` (btnbayar), `servis-garansi.php`
  (btnsimpan/btnsave-bayar).

**Tidak termasuk (di luar scope sesi ini):**
- Halaman laporan/rekap komisi mekanik (menyusul terpisah).
- Perubahan UI slider persen yang sudah ada di
  `panel-kiri-kasir.php`.
- Migrasi data historis (servis yang sudah dibayar sebelum fitur ini
  aktif tidak akan punya baris `servis_komisi`).

## Desain

### 1. Skema tabel

```sql
CREATE TABLE bagi_hasil_komisi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kd_cabang VARCHAR(10) NOT NULL,
  kategori ENUM('jasa','barang') NOT NULL,
  persen_mekanik DECIMAL(5,2) NOT NULL DEFAULT 0,
  persen_admin DECIMAL(5,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cabang_kategori (kd_cabang, kategori)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

CREATE TABLE servis_komisi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_service VARCHAR(50) NOT NULL,
  kd_cabang VARCHAR(10) NOT NULL,
  kategori ENUM('jasa','barang') NOT NULL,
  peran ENUM('kepala_mekanik','mekanik','admin') NOT NULL,
  kd_penerima VARCHAR(50) NOT NULL,
  nama_penerima VARCHAR(100) DEFAULT NULL,
  persen_pool DECIMAL(5,2) NOT NULL DEFAULT 0,
  persen_individu DECIMAL(5,2) NOT NULL DEFAULT 0,
  nominal DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_no_service (no_service),
  KEY idx_cabang_penerima (kd_cabang, kd_penerima)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
```

Catatan: `kepala_mekanik` dan `mekanik` dianggap berbagi **satu pool**
yang sama (`persen_mekanik` di config) — Access formula hanya
membedakan mekanik vs admin, bukan kepala vs mekanik biasa. Kolom
`peran` di `servis_komisi` tetap membedakan `kepala_mekanik` vs
`mekanik` untuk keperluan pelaporan nanti, tapi keduanya memakai
`persen_mekanik` yang sama sebagai basis pool.

`nama_penerima` didenormalisasi supaya laporan komisi tidak putus kalau
mekanik/admin dihapus dari master di kemudian hari.

### 2. Formula hitung nominal

Basis per kategori:
- `jasa` -> total nilai jasa servis (`SUM(tblservis_jasa.total)` untuk
  `no_service` tsb — variabel `$total_service` sudah dihitung di semua
  3 file).
- `barang` -> total nilai barang servis (`$total_barang`, sudah
  dihitung di semua 3 file).

Per slot terisi (kepala_mekanik1/2, mekanik1-4, admin1/2):

```
nominal = basis_kategori x (persen_pool / 100) x (persen_individu / 100)
```

`persen_pool` diambil dari `bagi_hasil_komisi` sesuai `kd_cabang` +
`kategori` + peran (mekanik pool dipakai untuk kepala_mekanik & mekanik,
admin pool untuk admin). Kalau baris config belum ada untuk cabang
tsb, `persen_pool` dianggap 0 — nominal jadi 0, pembayaran servis TETAP
lanjut (tidak boleh block transaksi).

Satu slot yang terisi bisa menghasilkan sampai 2 baris `servis_komisi`
(satu kategori jasa, satu kategori barang) kalau kedua basis > 0.

### 3. Titik wiring (3 file)

Logic hitung+insert dibungkus jadi 1 fungsi shared, taruh di file baru
`app/_include_komisi_snapshot.php` (di-include oleh ketiga halaman
pembayaran), supaya tidak duplikasi 3x. Fungsi menerima:
`$koneksi, $no_service, $kd_cabang, $total_service, $total_barang`,
dan membaca kolom `kepala_mekanik1/2`, `admin1/2`, `mekanik1-4`,
`persen_*` langsung dari `tblservice` (SELECT ulang sebelum insert,
supaya data yang dipakai adalah yang baru saja di-UPDATE, bukan nilai
lama dari awal request).

Dipanggil **setelah** UPDATE `tblservice SET status.../status_servis=...`
sukses, di titik yang sama tempat sudah ada guard `kd_cabang` (baru
ditambahkan sesi ini):
- `app/servis-reguler-byr.php` — dalam blok `btnsimpan`, setelah baris
  UPDATE `tblservice` (skrg ~line 570-581).
- `app/servis-input-reguler-jemput.php` — dalam blok `btnbayar`,
  setelah UPDATE status jadi bayar.
- `app/servis-garansi.php` — dalam blok `btnsimpan` pembayaran (line
  ~116 area), setelah UPDATE status.

### 4. Halaman master `bagi_hasil_komisi`

Pola sama seperti `master-tarif-jemput.php`: list per cabang, form
tambah/edit `kategori` (dropdown jasa/barang), `persen_mekanik`,
`persen_admin` (validasi masing-masing 0-100, tidak wajib total 100
karena mekanik dan admin adalah pool terpisah, bukan saling melengkapi).
RBAC: permission baru `master_komisi_manage`, guard pakai
`rbac_require_any(['master_komisi_manage'])`, entry menu ditaruh di
grup Master bareng tarif jemput.

### 5. Error handling

- Insert `servis_komisi` gagal (`mysqli_query` return false) -> catat ke
  `error_log()`, **tidak** menggagalkan alur pembayaran utama (konsisten
  dengan pola existing di 3 file ini — tidak ada transaction wrapper).
- Config `bagi_hasil_komisi` kosong untuk suatu cabang -> nominal 0.
  **Keputusan:** skip insert kalau nominal Rupiah = 0 (baik karena
  persen_pool 0 maupun basis 0), supaya tabel tidak penuh baris kosong.

### 6. Testing

Manual E2E via browser (tidak ada automated test suite di project ini):
1. Set config `bagi_hasil_komisi` untuk 1 cabang lewat halaman master.
2. Bayar 1 servis reguler dengan mekanik+persen terisi -> cek baris
   `servis_komisi` muncul dengan nominal benar.
3. Bayar 1 servis jemput, 1 servis garansi -> sama.
4. Bayar servis di cabang yang belum punya config -> pastikan
   pembayaran tetap sukses, tidak ada baris `servis_komisi` (nominal 0
   di-skip).
5. `php -l` semua file yang diubah.
