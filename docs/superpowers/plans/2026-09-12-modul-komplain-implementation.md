# Modul Penanganan Komplain — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bangun modul web standalone pencatatan & penanganan komplain pelanggan lintas cabang (Fase 1), terpisah total dari fitur "Komplain Garansi" yang sudah ada di modul servis.

**Architecture:** Folder baru `app/_komplain/` mengikuti pola modul kasir (`app/_keuangan/kasir/`) — PDO + `getenv()` untuk kredensial DB, RBAC via `tb_master_posisi.permissions` (JSON) + `tbuser.kode_cabang` untuk scoping cabang, tabel MySQL baru (bukan reuse tabel servis). Satu tabel inti `tblkomplain` dengan kolom kondisional nullable (bukan tabel terpisah per kategori) — skala kecil, gak perlu JOIN tambahan buat status transition. Audit trail append-only di `tblkomplain_log`.

**Tech Stack:** PHP 8.4 (PDO, konsisten dgn modul kasir), MySQL, Bootstrap/jQuery existing UI stack, phpspreadsheet (sudah ada dari Task 25 kasir) untuk export Excel.

**Spec:** `docs/Modul_Penanganan_Komplain_Fit_Motor.md` (18 bagian, status Final)

## Global Constraints

- Kredensial DB WAJIB via `getenv('DB_HOST'/'DB_USER'/'DB_PASS'/'DB_NAME')`, no hardcode (pola `koneksi_kasir.php`).
- Semua query pakai prepared statement (PDO `:param`) — no string concat SQL.
- Tidak ada aksi hapus (`DELETE`) untuk record komplain — hanya UPDATE status + insert log (spec bagian 8 rule 9).
- Kategori komplain wajib dropdown dari master data, no teks bebas (spec bagian 8 rule 2).
- Batas revisi REWORK maksimal 2 kali, revisi ke-3 auto-eskalasi (spec bagian 8 rule 8).
- Role selain Kepala Cabang tidak bisa set status "Selesai"/"Ditutup - Ditolak" — validasi di backend, bukan cuma UI (spec bagian 8 rule 5, acceptance criteria terakhir bagian 10).
- Export Excel hanya role Manajemen, setiap export dicatat log (spec bagian 14).
- Prefix nomor komplain **`KPL-`** (bukan `KOMP-`) — `KOMP` sudah dipakai fitur Komplain Garansi existing di servis (`app/_ajax/ajax-generate-antrian.php:19`). Dikonfirmasi Rafi: fitur Komplain Garansi itu bagian modul servis, tetap terpisah, gak disentuh plan ini.

## Temuan Prasyarat (investigasi live DB sebelum plan ini ditulis, 2026-09-12)

Query `tb_master_posisi`:

```
kode_posisi  nama_posisi       departemen    user_akses_level
KEU  Keuangan          Finance       8
CS   Customer Service  Front Office  2
KSR  Kasir             Front Office  2
HRD  HRD Staff         Human Resource 9
ADM  Administrator     Management    1
MNG  Manager           Management    7
CRM  CRM Staff         Marketing     6
PGD  Pengadaan         Purchasing    5
KM   Kepala Mekanik    Workshop      10
MK   Mekanik           Workshop      4
```

Distribusi user per cabang untuk posisi relevan:

```
kode_posisi  kode_cabang  jumlah
ADM  PACUL       2
ADM  PESALAKAN   2
ADM  PST         2
ADM  TRAYEMAN    1
KM   PST         2
MNG  PST         3
```

**Gap kritis:**
1. Posisi **"Kepala Cabang" TIDAK ADA SAMA SEKALI** di `tb_master_posisi` maupun `tbuser`. Harus dibuat baru (Task 1).
2. Posisi **Kepala Mekanik (KM) cuma ada di cabang PST**. Cabang PACUL, PESALAKAN, TRAYEMAN, CIKDITIRO nol Kepala Mekanik terdaftar — konsisten dengan backlog lama "staff CS/KSR/ADM cuma ada di cabang PST" (session S2041). Tanpa akun ini, alur REWORK di 4 cabang lain gak bisa jalan (gak ada PIC buat di-assign).
3. `tbuser.kode_cabang` pakai kode singkat teks (`PST`/`PACUL`/`PESALAKAN`/`TRAYEMAN`/`CIKDITIRO`), beda dari `tbcabang.cabang_ref_kode` (kode numerik) — modul ini ikut pola `tbuser.kode_cabang` (sudah dipakai modul kasir), bukan `cabang_ref_kode`.

Task 1 menangani gap ini sebagai prasyarat blocking sebelum modul REWORK bisa di-UAT di 5 cabang.

## File Structure

**Buat baru:**
- `db/migrations/2026-09-12_komplain_schema.sql` — DDL 4 tabel baru + seed master kategori + insert posisi KACAB + update permissions JSON posisi existing
- `app/_komplain/koneksi_komplain.php` — koneksi PDO + guard RBAC (pola `koneksi_kasir.php`)
- `app/_komplain/input.php` — form input CS/Admin (semua kategori)
- `app/_komplain/ajax_cek_riwayat_nopol.php` — endpoint AJAX cek riwayat komplain per NOPOL
- `app/_komplain/ajax_submit_komplain.php` — endpoint AJAX submit form input (validasi backend)
- `app/_komplain/antrian_rework.php` — antrian & form usulan Kepala Mekanik
- `app/_komplain/ajax_submit_usulan.php` — endpoint AJAX submit usulan Terima/Tolak
- `app/_komplain/antrian_approval.php` — antrian Kepala Cabang (approval REWORK + closing non-REWORK)
- `app/_komplain/ajax_keputusan_rework.php` — endpoint AJAX Setuju/Minta Revisi/No-show
- `app/_komplain/ajax_close_nonrework.php` — endpoint AJAX closing non-REWORK dengan tindak lanjut
- `app/_komplain/detail.php` — halaman detail 1 komplain + timeline log
- `app/_komplain/eskalasi_manajemen.php` — halaman keputusan final Manajemen untuk status Eskalasi
- `app/_komplain/ajax_keputusan_eskalasi.php` — endpoint AJAX keputusan Manajemen
- `app/_komplain/dashboard_manajemen.php` — dashboard agregat lintas cabang
- `app/_komplain/master_kategori.php` — CRUD master kategori (Super Admin)
- `app/_komplain/export.php` — export Excel (Manajemen only, log tiap export)

**Modifikasi:**
- `app/menu_config.php` — tambah grup menu "Penanganan Komplain" (link ke file di atas, per role); dan `tb_master_posisi.permissions` (data, lewat migration SQL) untuk posisi `KM`, `CS`/`ADM`, `MNG` — tambah kode `komplain_*`.

**Tidak disentuh (dikonfirmasi Rafi, di luar scope):**
- `app/servis-garansi.php`, `app/_ajax/ajax-generate-antrian.php`, `app/_template/_servis_*_with_tabs.php` — fitur Komplain Garansi existing tetap bagian modul servis, gak dilink/diganti di Fase 1.

## Tugas / Task Planning

### Fase Prasyarat (blocking, sebelum UAT bisa jalan di 5 cabang)

### Task 1: Setup Posisi & Akun Kepala Cabang + Lengkapi Kepala Mekanik

**Files:**
- Modify (data live DB, via migration SQL): `tb_master_posisi`, `tbuser`

**Interfaces:**
- Produces: posisi `KACAB` dipakai semua task RBAC berikutnya; `permissions` JSON key baru `komplain_*` dipakai guard di `koneksi_komplain.php`.

- [ ] **Step 1: Insert posisi Kepala Cabang**

```sql
INSERT INTO tb_master_posisi (kode_posisi, nama_posisi, departemen, deskripsi, user_akses_level, permissions, is_active)
VALUES ('KACAB', 'Kepala Cabang', 'Management',
  'Penanggung jawab operasional 1 cabang, approver komplain REWORK & non-REWORK',
  7,
  JSON_ARRAY('komplain_approve_rework', 'komplain_close_nonrework', 'komplain_view_cabang'),
  'active');
```

- [ ] **Step 2: Verifikasi insert**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "SELECT * FROM tb_master_posisi WHERE kode_posisi='KACAB';"
```
Expected: 1 baris, `permissions` berisi 3 kode di atas.

- [ ] **Step 3: Laporkan ke Rafi daftar cabang yang belum punya Kepala Mekanik**

```sql
SELECT c.cabang_ref_kode, c.nama_cabang
FROM tbcabang c
WHERE c.nama_cabang NOT IN (
  SELECT DISTINCT kode_cabang FROM tbuser WHERE kode_posisi = 'KM'
);
```
Jangan auto-create akun user — butuh nama staf real & password dari Rafi.

- [ ] **Step 4: Setelah Rafi konfirmasi nama staf, insert akun per cabang yang kosong**

Template per cabang (ulangi, ganti `<NAMA>`, `<KODE_CABANG>`, `<NIP_BARU>`):

```sql
INSERT INTO tbuser (kode_karyawan, nama_user, kode_posisi, kode_cabang, status_row, is_active, password)
VALUES ('<NIP_BARU>', '<NAMA>', 'KACAB', '<KODE_CABANG>', '0', 'active', PASSWORD_HASH_DIISI_TERPISAH);
```
Password ikut pola generation existing di `app/master_karyawan.php` — jangan hardcode plaintext ([[feedback_secrets_no_hardcoded_default]]).

- [ ] **Step 5: Commit migration schema-only (bukan data staf real) ke git**

```bash
git add db/migrations/2026-09-12_komplain_schema.sql
git commit -m "feat(komplain): tambah posisi KACAB + permissions RBAC dasar"
```

---

### Task 2: DDL Skema Database Modul Komplain

**Files:**
- Create: `db/migrations/2026-09-12_komplain_schema.sql`

**Interfaces:**
- Produces: tabel `tblkomplain`, `tblkomplain_kategori`, `tblkomplain_log`, `tblkomplain_export_log` — nama & kolom persis dipakai semua task berikutnya.

- [ ] **Step 1: Tulis DDL lengkap**

```sql
CREATE TABLE tblkomplain_kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_kategori VARCHAR(20) NOT NULL UNIQUE,
  nama_kategori VARCHAR(50) NOT NULL,
  pic_role ENUM('KEPALA_MEKANIK','KEPALA_CABANG') NOT NULL,
  jenis_penyelesaian TEXT NOT NULL,
  is_active ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO tblkomplain_kategori (kode_kategori, nama_kategori, pic_role, jenis_penyelesaian) VALUES
('REWORK', 'Rework', 'KEPALA_MEKANIK', 'Pelanggan diarahkan kembali untuk perbaikan ulang (ada jadwal kedatangan)'),
('HARGA', 'Harga', 'KEPALA_CABANG', 'Tindak lanjut penanganan (tanpa kedatangan wajib)'),
('SIKAP', 'Sikap', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('ANTRIAN', 'Antrian', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('FASILITAS', 'Fasilitas', 'KEPALA_CABANG', 'Tindak lanjut penanganan'),
('LAINNYA', 'Lainnya', 'KEPALA_CABANG', 'Tindak lanjut penanganan');

CREATE TABLE tblkomplain (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_komplain VARCHAR(30) NOT NULL UNIQUE,
  nama_pelanggan VARCHAR(100) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  nopol VARCHAR(20) NOT NULL,
  kode_cabang VARCHAR(20) NOT NULL,
  kode_kategori VARCHAR(20) NOT NULL,
  channel_lapor ENUM('Telepon','WA','Datang langsung','Lainnya') NOT NULL,
  tanggal_lapor DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  detail_keluhan TEXT NOT NULL,
  no_nota_rujukan VARCHAR(50) DEFAULT NULL,
  pic_kode_karyawan VARCHAR(20) DEFAULT NULL,
  status ENUM('Open','Diajukan','Dijadwalkan','Dikerjakan','Selesai','Ditutup - Ditolak','No-show','Eskalasi Manajemen') NOT NULL DEFAULT 'Open',
  mekanik_servis_awal VARCHAR(100) DEFAULT NULL,
  mekanik_pelaksana_kode VARCHAR(20) DEFAULT NULL,
  jenis_usulan ENUM('Terima','Tolak') DEFAULT NULL,
  alasan_usulan TEXT DEFAULT NULL,
  rencana_tanggal_kedatangan DATE DEFAULT NULL,
  tanggal_rework_aktual DATE DEFAULT NULL,
  biaya_rework DECIMAL(12,2) DEFAULT NULL,
  jumlah_revisi INT NOT NULL DEFAULT 0,
  keputusan_kepala_cabang ENUM('Setuju','Minta Revisi') DEFAULT NULL,
  tindak_lanjut_penanganan TEXT DEFAULT NULL,
  tanggal_ditindaklanjuti DATE DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_nopol (nopol),
  KEY idx_cabang_status (kode_cabang, status),
  KEY idx_kategori (kode_kategori),
  CONSTRAINT fk_komplain_kategori FOREIGN KEY (kode_kategori) REFERENCES tblkomplain_kategori(kode_kategori)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE tblkomplain_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  komplain_id INT NOT NULL,
  aksi VARCHAR(50) NOT NULL,
  status_sebelum VARCHAR(30) DEFAULT NULL,
  status_sesudah VARCHAR(30) NOT NULL,
  kode_karyawan_pelaku VARCHAR(20) NOT NULL,
  keterangan TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_komplain (komplain_id),
  CONSTRAINT fk_log_komplain FOREIGN KEY (komplain_id) REFERENCES tblkomplain(id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE tblkomplain_export_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_karyawan VARCHAR(20) NOT NULL,
  cakupan_filter TEXT DEFAULT NULL,
  jumlah_baris INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

- [ ] **Step 2: Jalankan migration di DB live**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel < db/migrations/2026-09-12_komplain_schema.sql
```

- [ ] **Step 3: Verifikasi 4 tabel + seed 6 kategori**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "SHOW TABLES LIKE 'tblkomplain%'; SELECT COUNT(*) FROM tblkomplain_kategori;"
```
Expected: 4 tabel, count = 6.

- [ ] **Step 4: Commit**

```bash
git add db/migrations/2026-09-12_komplain_schema.sql
git commit -m "feat(komplain): DDL tabel inti + master kategori + audit log + export log"
```

---

### Task 3: koneksi_komplain.php — RBAC Guard & Helper Routing PIC

**Files:**
- Create: `app/_komplain/koneksi_komplain.php`

**Interfaces:**
- Produces: `$koneksi_komplain` (PDO), `cekPermissionKomplain(string $kode): bool`, `generateNoKomplain(PDO $db): string` (format `KPL-YYYYMMDD-XXXX`), `getPicRework(PDO $db, string $kode_cabang): ?string`, `getPicNonRework(PDO $db, string $kode_cabang): ?string`, `catatLogKomplain(PDO $db, int $komplainId, string $aksi, ?string $statusSebelum, string $statusSesudah, string $keterangan = ''): void`.

- [ ] **Step 1: Tulis koneksi_komplain.php**

```php
<?php
session_start();
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'fitmotor_LOGIN';
$DB_PASS = getenv('DB_PASS') ?: 'Sayalupa12';
$DB_NAME = getenv('DB_NAME') ?: 'fitmotor_dbbengkel';

try {
    $koneksi_komplain = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=latin1",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('[komplain] DB connect failed: ' . $e->getMessage());
    die('Koneksi database gagal.');
}

if (!isset($_SESSION['kode_karyawan'])) {
    header('Location: /login.php');
    exit;
}

function cekPermissionKomplain(string $kode): bool {
    $perms = $_SESSION['permissions'] ?? [];
    if (is_string($perms)) {
        $perms = json_decode($perms, true) ?: [];
    }
    return in_array($kode, $perms, true);
}

function generateNoKomplain(PDO $db): string {
    $tanggal = date('Ymd');
    $stmt = $db->prepare("SELECT COUNT(*) FROM tblkomplain WHERE no_komplain LIKE :prefix");
    $prefix = "KPL-$tanggal-%";
    $stmt->execute([':prefix' => $prefix]);
    $urutan = (int)$stmt->fetchColumn() + 1;
    return sprintf('KPL-%s-%04d', $tanggal, $urutan);
}

function getPicRework(PDO $db, string $kode_cabang): ?string {
    $stmt = $db->prepare(
        "SELECT kode_karyawan FROM tbuser
         WHERE kode_posisi = 'KM' AND kode_cabang = :cabang AND is_active = 'active'
         ORDER BY kode_karyawan ASC LIMIT 1"
    );
    $stmt->execute([':cabang' => $kode_cabang]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : null;
}

function getPicNonRework(PDO $db, string $kode_cabang): ?string {
    $stmt = $db->prepare(
        "SELECT kode_karyawan FROM tbuser
         WHERE kode_posisi = 'KACAB' AND kode_cabang = :cabang AND is_active = 'active'
         ORDER BY kode_karyawan ASC LIMIT 1"
    );
    $stmt->execute([':cabang' => $kode_cabang]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : null;
}

function catatLogKomplain(PDO $db, int $komplainId, string $aksi, ?string $statusSebelum, string $statusSesudah, string $keterangan = ''): void {
    $stmt = $db->prepare(
        "INSERT INTO tblkomplain_log (komplain_id, aksi, status_sebelum, status_sesudah, kode_karyawan_pelaku, keterangan)
         VALUES (:id, :aksi, :sebelum, :sesudah, :pelaku, :ket)"
    );
    $stmt->execute([
        ':id' => $komplainId, ':aksi' => $aksi, ':sebelum' => $statusSebelum,
        ':sesudah' => $statusSesudah, ':pelaku' => $_SESSION['kode_karyawan'], ':ket' => $keterangan,
    ]);
}
```
Catatan: "PIC primary per cabang" pakai `ORDER BY kode_karyawan ASC LIMIT 1` — sesuai keputusan Rafi "1 Kepala Mekanik primary per cabang". Kalau nanti ada kolom eksplisit `is_primary`, cukup ganti query ini (satu titik perubahan).

- [ ] **Step 2: Lint**

```bash
php -l app/_komplain/koneksi_komplain.php
```
Expected: `No syntax errors detected`

- [ ] **Step 3: Smoke test routing function**

```bash
php -r '
putenv("DB_HOST=172.22.0.1"); putenv("DB_USER=fitmotor_LOGIN"); putenv("DB_PASS=Sayalupa12"); putenv("DB_NAME=fitmotor_dbbengkel");
session_start(); $_SESSION["kode_karyawan"] = "TEST";
require "app/_komplain/koneksi_komplain.php";
var_dump(getPicRework($koneksi_komplain, "PST"));
var_dump(getPicRework($koneksi_komplain, "PACUL"));
var_dump(generateNoKomplain($koneksi_komplain));
'
```
Expected: `getPicRework("PST")` return string kode_karyawan; `getPicRework("PACUL")` return `NULL` (sampai Task 1 selesai — expected); `generateNoKomplain` return string format `KPL-20260912-0001`.

- [ ] **Step 4: Commit**

```bash
git add app/_komplain/koneksi_komplain.php
git commit -m "feat(komplain): koneksi PDO + RBAC guard + helper routing PIC"
```

---

### Task 4: Form Input Komplain (CS/Admin) + Deteksi Riwayat NOPOL

**Files:**
- Create: `app/_komplain/input.php`, `app/_komplain/ajax_cek_riwayat_nopol.php`, `app/_komplain/ajax_submit_komplain.php`

**Interfaces:**
- Consumes: `generateNoKomplain()`, `getPicRework()`, `getPicNonRework()`, `catatLogKomplain()`, `cekPermissionKomplain()` dari Task 3.
- Produces: endpoint POST `ajax_submit_komplain.php` → JSON `{success, no_komplain, message}`.

- [ ] **Step 1: Tulis ajax_cek_riwayat_nopol.php**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

$nopol = trim($_GET['nopol'] ?? '');
if ($nopol === '') {
    echo json_encode(['riwayat' => []]);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "SELECT no_komplain, kode_kategori, status, tanggal_lapor
     FROM tblkomplain WHERE nopol = :nopol ORDER BY tanggal_lapor DESC LIMIT 10"
);
$stmt->execute([':nopol' => $nopol]);
echo json_encode(['riwayat' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
```

- [ ] **Step 2: Tulis ajax_submit_komplain.php (validasi backend rule 2, 8.1)**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_input')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses input komplain.']);
    exit;
}

$nama = trim($_POST['nama_pelanggan'] ?? '');
$hp = trim($_POST['no_hp'] ?? '');
$nopol = trim($_POST['nopol'] ?? '');
$kategori = trim($_POST['kode_kategori'] ?? '');
$channel = trim($_POST['channel_lapor'] ?? '');
$detail = trim($_POST['detail_keluhan'] ?? '');
$noNota = trim($_POST['no_nota_rujukan'] ?? '') ?: null;
$kodeCabang = $_SESSION['kode_cabang'];

if ($nama === '' || $hp === '' || $nopol === '' || $kategori === '' || $channel === '' || $detail === '') {
    echo json_encode(['success' => false, 'message' => 'Field wajib belum lengkap.']);
    exit;
}

$stmtKategori = $koneksi_komplain->prepare(
    "SELECT pic_role FROM tblkomplain_kategori WHERE kode_kategori = :k AND is_active = 'active'"
);
$stmtKategori->execute([':k' => $kategori]);
$picRole = $stmtKategori->fetchColumn();
if ($picRole === false) {
    echo json_encode(['success' => false, 'message' => 'Kategori tidak valid.']);
    exit;
}

$pic = $picRole === 'KEPALA_MEKANIK'
    ? getPicRework($koneksi_komplain, $kodeCabang)
    : getPicNonRework($koneksi_komplain, $kodeCabang);

$noKomplain = generateNoKomplain($koneksi_komplain);

$stmt = $koneksi_komplain->prepare(
    "INSERT INTO tblkomplain
     (no_komplain, nama_pelanggan, no_hp, nopol, kode_cabang, kode_kategori, channel_lapor, detail_keluhan, no_nota_rujukan, pic_kode_karyawan, status)
     VALUES (:no, :nama, :hp, :nopol, :cabang, :kategori, :channel, :detail, :nota, :pic, 'Open')"
);
$stmt->execute([
    ':no' => $noKomplain, ':nama' => $nama, ':hp' => $hp, ':nopol' => $nopol,
    ':cabang' => $kodeCabang, ':kategori' => $kategori, ':channel' => $channel,
    ':detail' => $detail, ':nota' => $noNota, ':pic' => $pic,
]);
$komplainId = (int)$koneksi_komplain->lastInsertId();
catatLogKomplain($koneksi_komplain, $komplainId, 'input_baru', null, 'Open', "Diinput via channel $channel");

echo json_encode(['success' => true, 'no_komplain' => $noKomplain, 'message' => 'Komplain tersimpan.']);
```

- [ ] **Step 3: Tulis input.php (form HTML + AJAX call ke 2 endpoint di atas)**

Form Bootstrap pola `app/servis-input-reguler.php` — field: Nama, No HP, Nopol (`onblur` trigger `ajax_cek_riwayat_nopol.php`, tampilkan riwayat di panel bawah), Kategori (dropdown dari `tblkomplain_kategori` where `is_active='active'`), Channel Lapor, Detail Keluhan, No Nota Rujukan (opsional). Submit via `fetch()` POST ke `ajax_submit_komplain.php`, tampilkan `no_komplain` di alert sukses.

- [ ] **Step 4: Lint semua file**

```bash
php -l app/_komplain/input.php
php -l app/_komplain/ajax_cek_riwayat_nopol.php
php -l app/_komplain/ajax_submit_komplain.php
```
Expected: `No syntax errors detected` untuk ketiganya.

- [ ] **Step 5: Smoke test insert non-REWORK**

```bash
php -r '
putenv("DB_HOST=172.22.0.1"); putenv("DB_USER=fitmotor_LOGIN"); putenv("DB_PASS=Sayalupa12"); putenv("DB_NAME=fitmotor_dbbengkel");
session_start(); $_SESSION["kode_karyawan"]="TEST"; $_SESSION["kode_cabang"]="PST"; $_SESSION["permissions"]=["komplain_input"];
$_POST = ["nama_pelanggan"=>"Budi","no_hp"=>"08123","nopol"=>"G1234XX","kode_kategori"=>"HARGA","channel_lapor"=>"WA","detail_keluhan"=>"Harga kemahalan"];
require "app/_komplain/ajax_submit_komplain.php";
'
```
Expected: JSON `{"success":true,"no_komplain":"KPL-...","message":"Komplain tersimpan."}`, `pic_kode_karyawan` di DB terisi kode_karyawan Kepala Cabang PST (setelah Task 1 selesai).

- [ ] **Step 6: Commit**

```bash
git add app/_komplain/input.php app/_komplain/ajax_cek_riwayat_nopol.php app/_komplain/ajax_submit_komplain.php
git commit -m "feat(komplain): form input CS + deteksi riwayat NOPOL + auto-routing PIC"
```

---

### Task 5: Antrian & Usulan Kepala Mekanik (REWORK)

**Files:**
- Create: `app/_komplain/antrian_rework.php`, `app/_komplain/ajax_submit_usulan.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain()`, `catatLogKomplain()` dari Task 3.
- Produces: endpoint POST `ajax_submit_usulan.php` (`komplain_id`, `jenis_usulan`, `mekanik_pelaksana_kode`, `rencana_tanggal_kedatangan`/`alasan_usulan`) → JSON, status `Open`/`Eskalasi Manajemen` → `Diajukan`.

- [ ] **Step 1: Tulis ajax_submit_usulan.php (validasi acceptance criteria bagian 10 poin 5)**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_review')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses menyusun usulan.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$jenis = $_POST['jenis_usulan'] ?? '';
$mekanik = trim($_POST['mekanik_pelaksana_kode'] ?? '');
$tanggal = trim($_POST['rencana_tanggal_kedatangan'] ?? '');
$alasan = trim($_POST['alasan_usulan'] ?? '');

if (!in_array($jenis, ['Terima', 'Tolak'], true)) {
    echo json_encode(['success' => false, 'message' => 'Jenis usulan tidak valid.']);
    exit;
}
if ($jenis === 'Terima' && $mekanik === '') {
    echo json_encode(['success' => false, 'message' => 'Mekanik pelaksana wajib diisi untuk usulan Terima.']);
    exit;
}
if ($jenis === 'Tolak' && $alasan === '') {
    echo json_encode(['success' => false, 'message' => 'Alasan wajib diisi untuk usulan Tolak.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET jenis_usulan = :jenis, mekanik_pelaksana_kode = :mekanik,
     rencana_tanggal_kedatangan = :tanggal, alasan_usulan = :alasan, status = 'Diajukan'
     WHERE id = :id AND status IN ('Open', 'Eskalasi Manajemen')"
);
$stmt->execute([
    ':jenis' => $jenis, ':mekanik' => $mekanik ?: null,
    ':tanggal' => $tanggal ?: null, ':alasan' => $alasan ?: null, ':id' => $id,
]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau status tidak valid untuk usulan.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'submit_usulan', 'Open', 'Diajukan', "Usulan: $jenis");
echo json_encode(['success' => true, 'message' => 'Usulan tersimpan.']);
```

- [ ] **Step 2: Tulis antrian_rework.php**

List komplain REWORK dengan `pic_kode_karyawan = $_SESSION['kode_karyawan']` dan `status IN ('Open','Eskalasi Manajemen')`, tiap baris ada tombol buka modal usulan (Terima/Tolak) submit ke `ajax_submit_usulan.php`.

- [ ] **Step 3: Lint**

```bash
php -l app/_komplain/antrian_rework.php
php -l app/_komplain/ajax_submit_usulan.php
```

- [ ] **Step 4: Smoke test usulan Terima tanpa mekanik (harus ditolak)**

```bash
php -r '
putenv("DB_HOST=172.22.0.1"); putenv("DB_USER=fitmotor_LOGIN"); putenv("DB_PASS=Sayalupa12"); putenv("DB_NAME=fitmotor_dbbengkel");
session_start(); $_SESSION["kode_karyawan"]="TEST"; $_SESSION["permissions"]=["komplain_review"];
$_POST = ["komplain_id"=>1,"jenis_usulan"=>"Terima"];
require "app/_komplain/ajax_submit_usulan.php";
'
```
Expected: `{"success":false,"message":"Mekanik pelaksana wajib diisi untuk usulan Terima."}`.

- [ ] **Step 5: Commit**

```bash
git add app/_komplain/antrian_rework.php app/_komplain/ajax_submit_usulan.php
git commit -m "feat(komplain): antrian & usulan Kepala Mekanik untuk REWORK"
```

---

### Task 6: Approval Kepala Cabang — REWORK (Setuju/Minta Revisi/No-show) + Non-REWORK (Closing)

**Files:**
- Create: `app/_komplain/antrian_approval.php`, `app/_komplain/ajax_keputusan_rework.php`, `app/_komplain/ajax_close_nonrework.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain()`, `catatLogKomplain()` dari Task 3.
- Produces: `ajax_keputusan_rework.php` (`komplain_id`, `keputusan` in `Setuju`/`Minta Revisi`/`No-show`) menerapkan batas revisi + eskalasi; `ajax_close_nonrework.php` (`komplain_id`, `tindak_lanjut_penanganan`) menutup status `Selesai`.

- [ ] **Step 1: Tulis ajax_keputusan_rework.php (rule 8.2 poin 4-6, rule 8 poin 8)**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_approve_rework')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses approval.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$keputusan = $_POST['keputusan'] ?? '';

$stmtCek = $koneksi_komplain->prepare("SELECT status, jenis_usulan, jumlah_revisi FROM tblkomplain WHERE id = :id");
$stmtCek->execute([':id' => $id]);
$row = $stmtCek->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['status'] !== 'Diajukan') {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak dalam status Diajukan.']);
    exit;
}

if ($keputusan === 'No-show') {
    $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain SET status = 'No-show' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'no_show', $row['status'], 'No-show');
    echo json_encode(['success' => true, 'message' => 'Status diubah jadi No-show.']);
    exit;
}

if ($keputusan === 'Setuju') {
    $statusBaru = $row['jenis_usulan'] === 'Terima' ? 'Dijadwalkan' : 'Ditutup - Ditolak';
    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = :status, keputusan_kepala_cabang = 'Setuju' WHERE id = :id"
    );
    $stmt->execute([':status' => $statusBaru, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'approve', 'Diajukan', $statusBaru);
    echo json_encode(['success' => true, 'message' => "Status diubah jadi $statusBaru."]);
    exit;
}

if ($keputusan === 'Minta Revisi') {
    $revisiBaru = $row['jumlah_revisi'] + 1;
    if ($revisiBaru >= 3) {
        $stmt = $koneksi_komplain->prepare(
            "UPDATE tblkomplain SET status = 'Eskalasi Manajemen', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id"
        );
        $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
        catatLogKomplain($koneksi_komplain, $id, 'eskalasi_otomatis', 'Diajukan', 'Eskalasi Manajemen', "Revisi ke-$revisiBaru");
        echo json_encode(['success' => true, 'message' => 'Revisi ke-3, otomatis eskalasi ke Manajemen.']);
        exit;
    }
    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = 'Open', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id"
    );
    $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'minta_revisi', 'Diajukan', 'Open', "Revisi ke-$revisiBaru");
    echo json_encode(['success' => true, 'message' => "Dikembalikan ke Kepala Mekanik (revisi ke-$revisiBaru)."]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
```

- [ ] **Step 2: Tulis ajax_close_nonrework.php (rule 8 poin 7)**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_close_nonrework')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses closing.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$tindakLanjut = trim($_POST['tindak_lanjut_penanganan'] ?? '');

if ($tindakLanjut === '') {
    echo json_encode(['success' => false, 'message' => 'Tindak Lanjut Penanganan wajib diisi.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET status = 'Selesai', tindak_lanjut_penanganan = :tl, tanggal_ditindaklanjuti = CURDATE()
     WHERE id = :id AND status = 'Open'"
);
$stmt->execute([':tl' => $tindakLanjut, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau bukan status Open.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'close_nonrework', 'Open', 'Selesai', $tindakLanjut);
echo json_encode(['success' => true, 'message' => 'Komplain ditutup Selesai.']);
```

- [ ] **Step 3: Tulis antrian_approval.php**

Dua panel: (a) list REWORK status `Diajukan` cabang sendiri (`kode_cabang = $_SESSION['kode_cabang']`) dgn tombol Setuju/Minta Revisi/No-show; (b) list non-REWORK status `Open` cabang sendiri dgn form tindak lanjut + tombol Selesai. Tampilkan aging (`DATEDIFF(NOW(), tanggal_lapor)` hari) di kedua panel.

- [ ] **Step 4: Lint semua**

```bash
php -l app/_komplain/antrian_approval.php
php -l app/_komplain/ajax_keputusan_rework.php
php -l app/_komplain/ajax_close_nonrework.php
```

- [ ] **Step 5: Smoke test batas revisi (boundary test spec bagian 11)**

```bash
php -r '
putenv("DB_HOST=172.22.0.1"); putenv("DB_USER=fitmotor_LOGIN"); putenv("DB_PASS=Sayalupa12"); putenv("DB_NAME=fitmotor_dbbengkel");
session_start(); $_SESSION["kode_karyawan"]="TEST"; $_SESSION["permissions"]=["komplain_approve_rework"];
require "app/_komplain/koneksi_komplain.php";
$koneksi_komplain->exec("UPDATE tblkomplain SET jumlah_revisi = 2, status = \"Diajukan\", jenis_usulan = \"Terima\" WHERE id = 1");
$_POST = ["komplain_id"=>1,"keputusan"=>"Minta Revisi"];
require "app/_komplain/ajax_keputusan_rework.php";
'
```
Expected: `{"success":true,"message":"Revisi ke-3, otomatis eskalasi ke Manajemen."}`, `status` di DB jadi `Eskalasi Manajemen`.

- [ ] **Step 6: Commit**

```bash
git add app/_komplain/antrian_approval.php app/_komplain/ajax_keputusan_rework.php app/_komplain/ajax_close_nonrework.php
git commit -m "feat(komplain): approval Kepala Cabang REWORK + closing non-REWORK + batas revisi"
```

---

### Task 7: Halaman Detail Komplain + Timeline Log

**Files:**
- Create: `app/_komplain/detail.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain()` dari Task 3, tabel `tblkomplain` + `tblkomplain_log`.

- [ ] **Step 1: Tulis detail.php**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $koneksi_komplain->prepare("SELECT * FROM tblkomplain WHERE id = :id");
$stmt->execute([':id' => $id]);
$komplain = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$komplain) {
    die('Komplain tidak ditemukan.');
}

$isManajemen = cekPermissionKomplain('komplain_view_all');
if (!$isManajemen && $komplain['kode_cabang'] !== $_SESSION['kode_cabang']) {
    http_response_code(403);
    die('Tidak punya akses ke data cabang lain.');
}

$stmtLog = $koneksi_komplain->prepare("SELECT * FROM tblkomplain_log WHERE komplain_id = :id ORDER BY created_at ASC");
$stmtLog->execute([':id' => $id]);
$logs = $stmtLog->fetchAll(PDO::FETCH_ASSOC);
// render HTML detail (field core + kondisional sesuai kategori) + timeline $logs
```

- [ ] **Step 2: Lint**

```bash
php -l app/_komplain/detail.php
```

- [ ] **Step 3: Smoke test guard lintas cabang (acceptance criteria bagian 10)**

```bash
php -r '
putenv("DB_HOST=172.22.0.1"); putenv("DB_USER=fitmotor_LOGIN"); putenv("DB_PASS=Sayalupa12"); putenv("DB_NAME=fitmotor_dbbengkel");
session_start(); $_SESSION["kode_karyawan"]="TEST"; $_SESSION["kode_cabang"]="PACUL"; $_SESSION["permissions"]=[];
$_GET["id"]=1;
require "app/_komplain/detail.php";
' 2>&1 | tail -3
```
Expected: exit dengan "Tidak punya akses ke data cabang lain." (asumsi komplain id=1 kode_cabang PST).

- [ ] **Step 4: Commit**

```bash
git add app/_komplain/detail.php
git commit -m "feat(komplain): halaman detail + timeline audit log + guard cabang"
```

---

### Task 8: Wiring Menu (UAT Tahap 1)

**Files:**
- Modify: `app/menu_config.php`

**Interfaces:**
- Consumes: file Task 4-7.

- [ ] **Step 1: Tambah grup menu "Penanganan Komplain"**

Pola sama grup "Keuangan Kasir" existing — item link ke `app/_komplain/*.php`, dibatasi permission yang dicek `cekPermissionKomplain()`:
- "Input Komplain" → `input.php` (permission `komplain_input`)
- "Antrian Rework" → `antrian_rework.php` (permission `komplain_review`)
- "Antrian Approval" → `antrian_approval.php` (permission `komplain_approve_rework` ATAU `komplain_close_nonrework`)

- [ ] **Step 2: Lint**

```bash
php -l app/menu_config.php
```

- [ ] **Step 3: Smoke test render menu login sebagai CS**

Login browser akun CS existing, pastikan 2 item pertama muncul, item approval TIDAK muncul.

- [ ] **Step 4: Commit**

```bash
git add app/menu_config.php
git commit -m "chore(komplain): wire menu Penanganan Komplain UAT tahap 1"
```

---

## UAT Tahap 1 selesai di sini (sesuai spec bagian 16)

Task 1-8 = alur inti: input semua kategori → routing otomatis → REWORK maker-checker dasar → closing non-REWORK. Cukup untuk target UAT 20 September.

---

### Fase Tahap 2 (boleh menyusul, sesuai spec bagian 16)

### Task 9: Eskalasi Manajemen — Keputusan Final

**Files:**
- Create: `app/_komplain/eskalasi_manajemen.php`, `app/_komplain/ajax_keputusan_eskalasi.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain('komplain_eskalasi_keputusan')`, `catatLogKomplain()`.
- Produces: `ajax_keputusan_eskalasi.php` (`komplain_id`, `keputusan_final` in `Dijadwalkan`/`Ditutup - Ditolak`).

- [ ] **Step 1: Tulis ajax_keputusan_eskalasi.php**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_eskalasi_keputusan')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses keputusan eskalasi.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$keputusan = $_POST['keputusan_final'] ?? '';

if (!in_array($keputusan, ['Dijadwalkan', 'Ditutup - Ditolak'], true)) {
    echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET status = :status WHERE id = :id AND status = 'Eskalasi Manajemen'"
);
$stmt->execute([':status' => $keputusan, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain bukan status Eskalasi Manajemen.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'keputusan_eskalasi', 'Eskalasi Manajemen', $keputusan, 'Keputusan final Manajemen');
echo json_encode(['success' => true, 'message' => "Keputusan final: $keputusan."]);
```

- [ ] **Step 2: Tulis eskalasi_manajemen.php** — list status `Eskalasi Manajemen` semua cabang, tombol 2 opsi keputusan.

- [ ] **Step 3: Lint**

```bash
php -l app/_komplain/eskalasi_manajemen.php
php -l app/_komplain/ajax_keputusan_eskalasi.php
```

- [ ] **Step 4: Smoke test** — pakai record hasil Task 6 Step 5 (sudah `Eskalasi Manajemen`), submit `Ditutup - Ditolak`, verify status berubah + log tercatat.

- [ ] **Step 5: Commit**

```bash
git add app/_komplain/eskalasi_manajemen.php app/_komplain/ajax_keputusan_eskalasi.php
git commit -m "feat(komplain): keputusan final Manajemen untuk kasus eskalasi revisi ke-3"
```

---

### Task 10: Dashboard Manajemen (Agregat Lintas Cabang)

**Files:**
- Create: `app/_komplain/dashboard_manajemen.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain('komplain_dashboard')`.

- [ ] **Step 1: Query agregat**

```sql
SELECT kode_cabang, kode_kategori, COUNT(*) AS jumlah
FROM tblkomplain GROUP BY kode_cabang, kode_kategori;

SELECT kode_cabang,
  SUM(status IN ('Selesai','Ditutup - Ditolak')) / COUNT(*) AS resolution_rate,
  AVG(DATEDIFF(updated_at, tanggal_lapor)) AS rata_rata_hari
FROM tblkomplain GROUP BY kode_cabang;

SELECT pic_kode_karyawan,
  SUM(keputusan_kepala_cabang = 'Setuju') / NULLIF(COUNT(keputusan_kepala_cabang), 0) AS approval_rate
FROM tblkomplain WHERE keputusan_kepala_cabang IS NOT NULL GROUP BY pic_kode_karyawan;
```

- [ ] **Step 2: Tulis dashboard_manajemen.php** — render 3 query di atas jadi tabel/chart (cek pola chart existing di `app/lap_servis.php`).

- [ ] **Step 3: Lint**

```bash
php -l app/_komplain/dashboard_manajemen.php
```

- [ ] **Step 4: Smoke test query langsung, pastikan gak error dengan 0 baris**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "SELECT kode_cabang, kode_kategori, COUNT(*) FROM tblkomplain GROUP BY kode_cabang, kode_kategori;"
```

- [ ] **Step 5: Commit**

```bash
git add app/_komplain/dashboard_manajemen.php
git commit -m "feat(komplain): dashboard agregat Manajemen lintas cabang"
```

---

### Task 11: Export Excel (Manajemen only) + Log Export

**Files:**
- Create: `app/_komplain/export.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain('komplain_export')`, phpspreadsheet (sudah tersedia dari Task 25 kasir).

- [ ] **Step 1: Tulis export.php**

```php
<?php
require __DIR__ . '/koneksi_komplain.php';
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!cekPermissionKomplain('komplain_export')) {
    http_response_code(403);
    die('Tidak punya akses export.');
}

$filterCabang = $_GET['cabang'] ?? null;
$sql = "SELECT no_komplain, nama_pelanggan, nopol, kode_cabang, kode_kategori, status, tanggal_lapor FROM tblkomplain";
$params = [];
if ($filterCabang) {
    $sql .= " WHERE kode_cabang = :cabang";
    $params[':cabang'] = $filterCabang;
}
$stmt = $koneksi_komplain->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$header = ['No Komplain', 'Nama Pelanggan', 'Nopol', 'Cabang', 'Kategori', 'Status', 'Tanggal Lapor'];
$sheet->fromArray($header, null, 'A1');
$sheet->fromArray(array_map('array_values', $rows), null, 'A2');

$stmtLog = $koneksi_komplain->prepare(
    "INSERT INTO tblkomplain_export_log (kode_karyawan, cakupan_filter, jumlah_baris) VALUES (:pelaku, :filter, :jumlah)"
);
$stmtLog->execute([
    ':pelaku' => $_SESSION['kode_karyawan'],
    ':filter' => $filterCabang ?: 'semua_cabang',
    ':jumlah' => count($rows),
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="komplain_export_' . date('Ymd_His') . '.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
```

- [ ] **Step 2: Lint**

```bash
php -l app/_komplain/export.php
```

- [ ] **Step 3: Smoke test tanpa permission (harus ditolak)**

```bash
php -r '
session_start(); $_SESSION["kode_karyawan"]="TEST"; $_SESSION["permissions"]=[];
require "app/_komplain/export.php";
' 2>&1
```
Expected: "Tidak punya akses export."

- [ ] **Step 4: Verifikasi log export tercatat**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "SELECT * FROM tblkomplain_export_log ORDER BY id DESC LIMIT 1;"
```

- [ ] **Step 5: Commit**

```bash
git add app/_komplain/export.php
git commit -m "feat(komplain): export Excel Manajemen-only + audit log export"
```

---

### Task 12: CRUD Master Kategori (Super Admin)

**Files:**
- Create: `app/_komplain/master_kategori.php`

**Interfaces:**
- Consumes: `cekPermissionKomplain('komplain_master_kategori')`.

- [ ] **Step 1: Tulis master_kategori.php**

CRUD standar (list, tambah, edit, nonaktifkan — bukan delete fisik, biar histori komplain lama gak kehilangan referensi FK) ke `tblkomplain_kategori`: `kode_kategori`, `nama_kategori`, `pic_role` (dropdown `KEPALA_MEKANIK`/`KEPALA_CABANG`), `jenis_penyelesaian`.

- [ ] **Step 2: Lint**

```bash
php -l app/_komplain/master_kategori.php
```

- [ ] **Step 3: Smoke test tambah kategori baru**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "
INSERT INTO tblkomplain_kategori (kode_kategori, nama_kategori, pic_role, jenis_penyelesaian) VALUES ('TESTKAT','Test Kategori','KEPALA_CABANG','Test');
SELECT * FROM tblkomplain_kategori WHERE kode_kategori='TESTKAT';
DELETE FROM tblkomplain_kategori WHERE kode_kategori='TESTKAT';
"
```

- [ ] **Step 4: Commit**

```bash
git add app/_komplain/master_kategori.php
git commit -m "feat(komplain): CRUD master kategori komplain oleh Super Admin"
```

---

### Task 13: Update Menu Tahap 2 + Permission Data Tambahan

**Files:**
- Modify: `app/menu_config.php`
- Modify (data): `tb_master_posisi.permissions` untuk posisi `MNG` dan `ADM`

- [ ] **Step 1: Update permissions JSON posisi MNG (Manajemen)**

```sql
UPDATE tb_master_posisi
SET permissions = JSON_ARRAY_APPEND(
  JSON_ARRAY_APPEND(
    JSON_ARRAY_APPEND(permissions, '$', 'komplain_view_all'),
    '$', 'komplain_eskalasi_keputusan'),
  '$', 'komplain_dashboard')
WHERE kode_posisi = 'MNG';

UPDATE tb_master_posisi
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'komplain_export')
WHERE kode_posisi = 'MNG';

UPDATE tb_master_posisi
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'komplain_master_kategori')
WHERE kode_posisi = 'ADM';
```

- [ ] **Step 2: Verifikasi**

```bash
mysql -h 172.22.0.1 -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel -e "SELECT kode_posisi, permissions FROM tb_master_posisi WHERE kode_posisi IN ('MNG','ADM');"
```

- [ ] **Step 3: Tambah 4 item menu tahap 2**

"Eskalasi Manajemen" → `eskalasi_manajemen.php`, "Dashboard Komplain" → `dashboard_manajemen.php`, "Export Komplain" → `export.php`, "Master Kategori Komplain" → `master_kategori.php`.

- [ ] **Step 4: Lint**

```bash
php -l app/menu_config.php
```

- [ ] **Step 5: Commit**

```bash
git add app/menu_config.php
git commit -m "chore(komplain): wire menu tahap 2 + permission Manajemen/Super Admin"
```

---

## Ringkasan Tambah / Edit / Hapus

**Tambah (semua baru, nol resiko regresi ke sistem existing):**
- 4 tabel DB (`tblkomplain`, `tblkomplain_kategori`, `tblkomplain_log`, `tblkomplain_export_log`)
- 1 posisi baru (`KACAB` di `tb_master_posisi`) + akun user per cabang yang kosong (Task 1)
- 14 file PHP baru di `app/_komplain/`

**Edit (file existing, perubahan kecil/additive):**
- `app/menu_config.php` — nambah 2 grup menu, gak ubah menu existing
- `tb_master_posisi.permissions` posisi `KM`, `CS`/`ADM`, `MNG`, `ADM` — nambah kode `komplain_*` ke array JSON existing (additive, `JSON_ARRAY_APPEND`, gak overwrite permission lama)

**Hapus:**
- **Tidak ada.** Modul ini standalone baru, gak ada file/tabel/kode existing yang dihapus atau digantikan. Fitur Komplain Garansi existing di modul servis tetap utuh, gak disentuh.

## Self-Review

**Spec coverage:**
- Bagian 3 (scope) — Task 4-13 cover; uploader data massal SENGAJA di-drop (lihat gap #1 di bawah).
- Bagian 4 (role) — Task 1, 3.
- Bagian 5 (master kategori) — Task 2 (seed), Task 12 (CRUD).
- Bagian 6 (alur proses) — Task 4 (input+routing), 5 (usulan), 6 (approval+closing), 9 (eskalasi).
- Bagian 7 (data dictionary) — skema Task 2.
- Bagian 8 (business rules 1-12) — semua 12 rule dipetakan ke validasi backend Task 3-9.
- Bagian 9 (UI per role) — Task 4, 5, 6, 10, 12.
- Bagian 10 (acceptance criteria, 9 kriteria) — semua ada smoke test terkait.
- Bagian 11 (10 skenario test) — di-cover smoke test Task 4-9 kecuali skenario idempotent (gap #2).
- Bagian 12-13 (NFR, asumsi) — constraint desain, gak butuh task kode.
- Bagian 14 (privasi/keamanan) — Task 11.
- Bagian 15 (success metrics) — non-functional, gak butuh task kode.

**Gap yang sengaja tidak dikerjakan (perlu keputusan Rafi lanjutan):**
1. **Uploader data massal** (spec bagian 3) — spec sendiri ambigu: gak jelas format file/kolom mapping, dan gak disebut termasuk UAT tahap 1 atau boleh menyusul. Spec bagian 17 juga bilang data historis Excel TIDAK dimigrasikan — jadi kebutuhan riil fitur ini belum jelas dipakai buat apa sekarang. Tanya Rafi dulu sebelum jadi Task 14.
2. **Idempotent submit saat koneksi terputus** (skenario recovery bagian 11) — Task 4 belum implement dedup. Kalau perlu untuk UAT tahap 1: tambah cek duplikat (`nama_pelanggan+nopol+detail_keluhan` dalam 60 detik terakhir) sebelum insert.
3. **Threshold No-show "7 hari, dapat dikonfigurasi"** (spec bagian 6.2 poin 6) — plan ini treat sebagai keputusan manual Kepala Cabang (klik kapan saja), gak ada validasi angka hari otomatis atau tabel config, karena spec gak jelas siapa yang boleh ubah angka itu.

**Placeholder scan:** semua step berisi kode nyata, tidak ada "TODO"/"implementasi serupa".

**Type consistency:** `getPicRework`/`getPicNonRework`/`generateNoKomplain`/`catatLogKomplain`/`cekPermissionKomplain` dipakai konsisten nama & parameter sama di semua task 3-13.
