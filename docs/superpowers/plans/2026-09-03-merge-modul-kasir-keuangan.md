# Merge Modul Kasir Closing (web_kasir → Keuangan) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gabungkan sistem closing kasir dari projek terpisah `web_kasir`
(`C:\laragon\www\web_kasir\website_kasir`, DB `fitmotor_maintance-beta`)
ke FIT MOTOR Web Base sebagai modul **Keuangan > Kasir**, satu DB
(`fitmotor_dbbengkel`) satu app, big-bang cutover.

**Architecture:** Rename+migrate 26 tabel transaksional/master web_kasir
ke prefix `*_closing_kasir` di `fitmotor_dbbengkel`, drop 4 tabel bridge HPP
+ 3 tabel app-lama yang tidak relevan, port ~140 file PHP ke
`app/_keuangan/kasir/` pakai koneksi & session fitmotor, mapping role
lama ke RBAC (`tb_user_roles`/`tb_permissions`) fitmotor, lalu drop 7
tabel kasir mati fitmotor (`tbkas_kasir*`, `tblakunkas`, `tbakun`,
`tbakun_pos`, `tblkas_keluar_masuk`) di langkah terpisah setelah cutover
sukses.

**Tech Stack:** PHP 8.3 (mysqli + PDO), MySQL/MariaDB, tidak ada
framework/test-runner formal di repo ini — verifikasi pakai disposable
CLI PHP script (pola project, lihat memory `web-bengkel-environment`)
buat query-level checks + Claude-in-Chrome buat smoke test UI, bukan
PHPUnit/Playwright config (belum ada di repo).

**Spec:** `docs/superpowers/specs/2026-09-03-merge-modul-kasir-keuangan-design.md`

## Global Constraints

- Semua tabel baru di `fitmotor_dbbengkel`, suffix `*_closing_kasir` (nama tabel asli dipertahankan).
- FK `kode_cabang` baru → HARUS pakai `tbcabang.cabang_ref_kode` (bukan
  `tbcabang.kode_cabang`) — dua kolom beda arti, jangan tertukar.
- FK `kode_karyawan` baru → `tbuser.kode_karyawan`.
- Setiap script migrasi CLI: jalankan lewat
  `/mnt/c/laragon/bin/php/php-8.3.16-nts-Win32-vs16-x64/php.exe -d extension_dir="C:\laragon\bin\php\php-8.3.16-nts-Win32-vs16-x64\ext" -d extension=php_mysqli.dll -d display_errors=1 -f "C:\laragon\www\web-bengkel\aplikasi\aplikasi\<file>.php"`,
  file ditulis di dalam project dir (bukan `/tmp`), dihapus (`rm -f`)
  setelah dipakai kalau memang disposable.
- DB kredensial: host `localhost`, user `fitmotor_LOGIN`, pass
  `Sayalupa12`, DB tujuan `fitmotor_dbbengkel`, DB sumber
  `fitmotor_maintance-beta` — SELALU baca dari `getenv()`/`config/koneksi.php`
  di kode final, kredensial literal cuma boleh di script migrasi
  sekali-pakai yang dihapus setelah dipakai (lihat memory
  `feedback_secrets_no_hardcoded_default` — no hardcoded secret default
  di kode yang MENETAP di repo).
- Setiap fase yang mengubah skema WAJIB didahului full dump
  `fitmotor_dbbengkel` ke `backups/pre-merge-kasir-YYYYMMDD_HHMMSS.sql`.
- Jangan drop `fitmotor_maintance-beta` (DB sumber web_kasir) sampai
  cutover dinyatakan sukses minimal beberapa hari jalan produksi — ini
  di luar scope task plan ini (manual decision Rafi).

---

## Task 1: Pre-migration backup & mapping validation

**Files:**
- Create: `backups/pre-merge-kasir-<timestamp>.sql` (output `mysqldump`, bukan ditulis manual)
- Create: `_tmp_validate_kasir_mapping.php` (disposable, root project — dihapus di Step akhir)

**Interfaces:**
- Produces: laporan tertulis (stdout, disalin ke commit message/PR notes) berisi jumlah baris tiap tabel sumber `fitmotor_maintance-beta`, dan daftar `kode_cabang`/`kode_karyawan` yatim (kalau ada). Task 2+ TIDAK boleh jalan kalau ada baris yatim.

- [ ] **Step 1: Dump `fitmotor_dbbengkel` sebagai rollback point**

Jalankan dari WSL (Laragon mysqldump ada di PATH Windows, panggil lewat cmd.exe kalau `mysqldump` gak ada di WSL — cek dulu):
```bash
mysqldump -h localhost -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel > backups/pre-merge-kasir-$(date +%Y%m%d_%H%M%S).sql
```
Kalau `mysqldump` WSL gak connect (kemungkinan besar, sama kayak `mysql` CLI), pakai `mysqldump.exe` bawaan Laragon:
```bash
"/mnt/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe" -h localhost -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel > backups/pre-merge-kasir-$(date +%Y%m%d_%H%M%S).sql
```
(path binary mysqldump.exe: cek dulu versi persis via `ls "/mnt/c/laragon/bin/mysql/"` kalau path di atas gak ada).

Expected: file `.sql` terbuat, size beberapa puluh-ratus MB (328 tabel), tidak kosong.

- [ ] **Step 2: Tulis script validasi mapping cabang & karyawan**

```php
<?php
// _tmp_validate_kasir_mapping.php — disposable, hapus setelah dipakai
$host='localhost'; $user='fitmotor_LOGIN'; $pass='Sayalupa12';
$dst = mysqli_connect($host,$user,$pass,'fitmotor_dbbengkel');
$src = mysqli_connect($host,$user,$pass,'fitmotor_maintance-beta');
if(!$dst || !$src){ die("connect fail\n"); }

// 1. Cabang: setiap cabang.kode_cabang di sumber harus match tbcabang.cabang_ref_kode
$validCabang = [];
$r = mysqli_query($dst, "SELECT cabang_ref_kode FROM tbcabang");
while($row = mysqli_fetch_row($r)) $validCabang[$row[0]] = true;

$orphanCabang = [];
$r = mysqli_query($src, "SELECT kode_cabang, nama_cabang FROM cabang");
while($row = mysqli_fetch_row($r)){
    if(!isset($validCabang[$row[0]])) $orphanCabang[] = $row[0].' ('.$row[1].')';
}
echo "=== Cabang orphan (tidak match tbcabang.cabang_ref_kode): ".count($orphanCabang)." ===\n";
foreach($orphanCabang as $o) echo "- $o\n";

// 2. Karyawan: setiap users.kode_karyawan (dan kode_karyawan di semua tabel transaksional) harus match tbuser.kode_karyawan
$validKaryawan = [];
$r = mysqli_query($dst, "SELECT kode_karyawan FROM tbuser WHERE kode_karyawan IS NOT NULL AND kode_karyawan != ''");
while($row = mysqli_fetch_row($r)) $validKaryawan[$row[0]] = true;

$orphanUser = [];
$r = mysqli_query($src, "SELECT kode_karyawan, nama_karyawan FROM users");
while($row = mysqli_fetch_row($r)){
    if(!isset($validKaryawan[$row[0]])) $orphanUser[] = $row[0].' ('.$row[1].')';
}
echo "\n=== users.kode_karyawan orphan (tidak match tbuser.kode_karyawan): ".count($orphanUser)." ===\n";
foreach($orphanUser as $o) echo "- $o\n";

// 3. Cek juga kode_karyawan yang dipakai langsung di tabel transaksional besar (kasir_transactions, pengeluaran_kasir)
foreach(['kasir_transactions' => 'kode_karyawan', 'pengeluaran_kasir' => 'kode_karyawan', 'pemasukan_kasir' => 'kode_karyawan'] as $tbl => $col){
    $distinct = [];
    $r = mysqli_query($src, "SELECT DISTINCT $col FROM $tbl WHERE $col IS NOT NULL AND $col != ''");
    while($row = mysqli_fetch_row($r)) $distinct[] = $row[0];
    $orphans = array_filter($distinct, fn($k) => !isset($validKaryawan[$k]));
    echo "\n=== $tbl.$col distinct orphan: ".count($orphans)." dari ".count($distinct)." ===\n";
    foreach($orphans as $o) echo "- $o\n";
}

echo "\n=== SUMMARY ===\n";
$totalOrphan = count($orphanCabang) + count($orphanUser);
echo $totalOrphan === 0 ? "OK — semua mapping cocok, aman lanjut Task 2.\n" : "STOP — ada $totalOrphan orphan, JANGAN lanjut migrasi sebelum diselesaikan.\n";
```

- [ ] **Step 3: Jalankan validasi**

Run: `/mnt/c/laragon/bin/php/php-8.3.16-nts-Win32-vs16-x64/php.exe -d extension_dir="C:\laragon\bin\php\php-8.3.16-nts-Win32-vs16-x64\ext" -d extension=php_mysqli.dll -d display_errors=1 -f "C:\laragon\www\web-bengkel\aplikasi\aplikasi\_tmp_validate_kasir_mapping.php"`

Expected: `OK — semua mapping cocok, aman lanjut Task 2.` di baris terakhir.
Kalau muncul `STOP`, catat semua orphan yang dilaporkan, selesaikan dulu
(perbaiki data sumber atau tambah mapping manual) sebelum lanjut ke
Task 2 — JANGAN lanjut migrasi dengan data yatim.

- [ ] **Step 4: Hapus script disposable & commit backup**

```bash
rm -f _tmp_validate_kasir_mapping.php
git add backups/pre-merge-kasir-*.sql
git commit -m "chore: backup fitmotor_dbbengkel sebelum merge modul kasir"
```

---

## Task 2: DDL — tabel master/independen `*_closing_kasir`

**Files:**
- Create: `docs/sql/tblkasir_schema_master.sql`

**Interfaces:**
- Produces: 4 tabel baru di `fitmotor_dbbengkel` — `master_akun_closing_kasir`,
  `master_nama_transaksi_closing_kasir`, `master_rekening_cabang_closing_kasir`,
  `kas_awal_config_closing_kasir` — dipakai Task 4 (migrasi data) dan semua
  task file-porting berikutnya.

- [ ] **Step 1: Ambil DDL asli dari sumber**

```php
<?php
// _tmp_show_create.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
foreach(['master_akun','master_nama_transaksi','master_rekening_cabang','kas_awal_config'] as $t){
    $r = mysqli_query($src, "SHOW CREATE TABLE `$t`");
    $row = mysqli_fetch_assoc($r);
    echo $row['Create Table'].";\n\n";
}
```
Run via php.exe (path sama seperti Task 1), simpan output ke
`docs/sql/tblkasir_schema_master.sql`, lalu `rm -f _tmp_show_create.php`.

- [ ] **Step 2: Edit DDL — rename tabel & tambah FK**

Buka `docs/sql/tblkasir_schema_master.sql`, untuk tiap `CREATE TABLE`:
1. Ganti nama tabel: `master_akun`→`master_akun_closing_kasir`,
   `master_nama_transaksi`→`master_nama_transaksi_closing_kasir`,
   `master_rekening_cabang`→`master_rekening_cabang_closing_kasir`,
   `kas_awal_config`→`kas_awal_config_closing_kasir`.
2. Kalau `master_rekening_cabang` punya kolom `kode_cabang`, tambahkan
   di akhir definisi tabel:
   ```sql
   , CONSTRAINT fk_master_rekening_cabang_closing_kasir FOREIGN KEY (kode_cabang) REFERENCES tbcabang(cabang_ref_kode)
   ```
3. Ganti `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` (atau apapun charset
   aslinya) jadi `ENGINE=InnoDB DEFAULT CHARSET=latin1` — samakan
   dengan charset koneksi fitmotor (`mysqli_set_charset($koneksi,
   'latin1')` di `app/koneksi.php`), supaya JOIN antar tabel lama-baru
   gak silent-fail collation mismatch (lihat memory
   `project_fase4_pengadaan_2026-07-18` — bug ini pernah kejadian).

- [ ] **Step 3: Eksekusi DDL ke `fitmotor_dbbengkel`**

```bash
"/mnt/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe" -h localhost -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel < docs/sql/tblkasir_schema_master.sql
```
(sesuaikan path `mysql.exe` kalau versi beda dari Task 1)

- [ ] **Step 4: Verifikasi tabel terbuat**

```php
<?php
// _tmp_verify_ddl.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
foreach(['master_akun_closing_kasir','master_nama_transaksi_closing_kasir','master_rekening_cabang_closing_kasir','kas_awal_config_closing_kasir'] as $t){
    $r = mysqli_query($c, "SELECT COUNT(*) n FROM `$t`");
    $row = mysqli_fetch_assoc($r);
    echo "$t: ".($row === null ? "TIDAK ADA/ERROR" : "OK, ".$row['n']." baris")."\n";
}
```
Expected: 4 baris "OK, 0 baris" (kosong, belum diisi data — itu benar,
data masuk di Task 4).

- [ ] **Step 5: Commit**

```bash
rm -f _tmp_show_create.php _tmp_verify_ddl.php
git add docs/sql/tblkasir_schema_master.sql
git commit -m "feat(keuangan-kasir): DDL tabel master *_closing_kasir"
```

---

## Task 3: DDL — tabel transaksional `*_closing_kasir`

**Files:**
- Create: `docs/sql/tblkasir_schema_transaksi.sql`

**Interfaces:**
- Consumes: `master_akun_closing_kasir`, `master_nama_transaksi_closing_kasir` (Task 2) sebagai target FK `kode_akun`.
- Produces: 18 tabel — `kasir_transactions_closing_kasir`, `closing_transaction_groups_closing_kasir`,
  `closing_transaction_details_closing_kasir`, `closing_revision_requests_closing_kasir`,
  `kas_awal_closing_kasir`, `kas_akhir_closing_kasir`,
  `detail_kas_awal_closing_kasir`, `detail_kas_akhir_closing_kasir`,
  `pemasukan_kasir_closing_kasir`, `pemasukan_pusat_closing_kasir`,
  `pengeluaran_kasir_closing_kasir`, `pengeluaran_pusat_closing_kasir`,
  `setoran_ke_bank_closing_kasir`, `setoran_ke_bank_detail_closing_kasir`,
  `setoran_keuangan_closing_kasir`, `pengambilan_setoran_closing_kasir`,
  `pengambilan_setoran_edit_log_closing_kasir`,
  `pengambilan_setoran_pembayaran_closing_kasir`,
  `serah_terima_kasir_closing_kasir`, `konfirmasi_buka_transaksi_closing_kasir`,
  `audit_log_closing_kasir` — dipakai Task 5-7 (migrasi data).

- [ ] **Step 1: Ambil DDL asli**

Sama pola Task 2 Step 1, ganti daftar tabel jadi 20 tabel di atas
(nama sumber, bukan nama target — lihat tabel mapping di spec §3.1).
Simpan ke `docs/sql/tblkasir_schema_transaksi.sql`.

- [ ] **Step 2: Edit DDL — rename + FK**

Untuk tiap `CREATE TABLE`, rename sesuai mapping spec §3.1. Tambahkan
FK di tabel yang punya kolom `kode_cabang`/`kode_karyawan`/`kode_akun`:

```sql
-- contoh untuk kasir_transactions_closing_kasir
ALTER TABLE kasir_transactions_closing_kasir
  ADD CONSTRAINT fk_kasir_transactions_closing_kasir_karyawan FOREIGN KEY (kode_karyawan) REFERENCES tbuser(kode_karyawan);
```
Terapkan pola sama untuk semua tabel yang punya kolom itu (referensi
kolom persis dari hasil dump Task 3 Step 1, JANGAN asumsi nama kolom —
cek `SHOW CREATE TABLE` hasil dump).

Samakan charset ke `latin1` (alasan sama Task 2 Step 2.3).

- [ ] **Step 3: Eksekusi & verifikasi**

Sama pola Task 2 Step 3-4, ganti daftar tabel jadi 20 tabel transaksional.
Expected: semua "OK, 0 baris".

- [ ] **Step 4: Commit**

```bash
git add docs/sql/tblkasir_schema_transaksi.sql
git commit -m "feat(keuangan-kasir): DDL tabel transaksional *_closing_kasir"
```

---

## Task 4: Migrasi data — tabel master/independen

**Files:**
- Create: `_tmp_migrate_kasir_master.php` (disposable)

**Interfaces:**
- Consumes: `master_akun_closing_kasir`, `master_nama_transaksi_closing_kasir`, `master_rekening_cabang_closing_kasir`, `kas_awal_config_closing_kasir` (Task 2).
- Produces: data ter-copy 1:1, dipakai FK oleh Task 5-7.

- [ ] **Step 1: Tulis script migrasi**

```php
<?php
// _tmp_migrate_kasir_master.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$dst = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
mysqli_set_charset($dst, 'latin1');

function copyTable($src, $dst, $srcTable, $dstTable){
    $r = mysqli_query($src, "SELECT * FROM `$srcTable`");
    $cols = null; $n = 0; $errors = 0;
    while($row = mysqli_fetch_assoc($r)){
        if($cols === null) $cols = array_keys($row);
        $colList = '`'.implode('`,`', $cols).'`';
        $vals = array_map(function($v) use ($dst){
            return $v === null ? 'NULL' : "'".mysqli_real_escape_string($dst, $v)."'";
        }, array_values($row));
        $sql = "INSERT INTO `$dstTable` ($colList) VALUES (".implode(',', $vals).")";
        if(mysqli_query($dst, $sql)) $n++;
        else { $errors++; echo "  ERROR row: ".mysqli_error($dst)."\n"; }
    }
    echo "$srcTable -> $dstTable: $n baris masuk, $errors error\n";
}

copyTable($src, $dst, 'master_akun', 'master_akun_closing_kasir');
copyTable($src, $dst, 'master_nama_transaksi', 'master_nama_transaksi_closing_kasir');
copyTable($src, $dst, 'master_rekening_cabang', 'master_rekening_cabang_closing_kasir');
copyTable($src, $dst, 'kas_awal_config', 'kas_awal_config_closing_kasir');
```

- [ ] **Step 2: Jalankan & verifikasi count**

Run via php.exe. Expected output 4 baris, error count = 0 di semuanya:
```
master_akun -> master_akun_closing_kasir: 34 baris masuk, 0 error
master_nama_transaksi -> master_nama_transaksi_closing_kasir: 159 baris masuk, 0 error
master_rekening_cabang -> master_rekening_cabang_closing_kasir: N baris masuk, 0 error
kas_awal_config -> kas_awal_config_closing_kasir: N baris masuk, 0 error
```
Kalau ada error > 0, baca pesan error (biasanya FK violation — cek
apakah semua `kode_cabang` di `master_rekening_cabang` valid, sama
pola validasi Task 1).

- [ ] **Step 3: Commit**

```bash
rm -f _tmp_migrate_kasir_master.php
git commit --allow-empty -m "chore(keuangan-kasir): migrasi data master *_closing_kasir selesai"
```
(commit `--allow-empty` karena tidak ada file kode yang berubah — data
DB, bukan tracked di git; commit ini jadi penanda checkpoint di history)

---

## Task 5: Migrasi data — kas awal/akhir & transaksi kasir

**Files:**
- Create: `_tmp_migrate_kasir_transaksi.php` (disposable)

**Interfaces:**
- Consumes: `copyTable()` helper pattern dari Task 4 (didefinisikan ulang di file ini — task terpisah dieksekusi worker berbeda, tidak bisa import file disposable task lain).
- Produces: `kas_awal_closing_kasir` (2224 baris), `kas_akhir_closing_kasir` (2213 baris), `detail_kas_awal_closing_kasir`, `detail_kas_akhir_closing_kasir`, `kasir_transactions_closing_kasir` (2222 baris) terisi — dipakai Task 6 (closing) via FK `kode_transaksi`.

- [ ] **Step 1: Tulis & jalankan script**

Sama struktur `copyTable()` seperti Task 4 Step 1 (copy-paste fungsinya
persis), lalu panggil urutan ini (urutan WAJIB — `kas_awal` sebelum
`kasir_transactions` karena `kasir_transactions.kode_transaksi`
mereferensi `kas_awal.kode_transaksi`):

```php
copyTable($src, $dst, 'kas_awal', 'kas_awal_closing_kasir');
copyTable($src, $dst, 'detail_kas_awal', 'detail_kas_awal_closing_kasir');
copyTable($src, $dst, 'kas_akhir', 'kas_akhir_closing_kasir');
copyTable($src, $dst, 'detail_kas_akhir', 'detail_kas_akhir_closing_kasir');
copyTable($src, $dst, 'kasir_transactions', 'kasir_transactions_closing_kasir');
```

- [ ] **Step 2: Verifikasi count cocok persis dengan sumber**

```php
<?php
// _tmp_verify_count_transaksi.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$dst = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$pairs = [
    ['kas_awal','kas_awal_closing_kasir'],
    ['kas_akhir','kas_akhir_closing_kasir'],
    ['kasir_transactions','kasir_transactions_closing_kasir'],
];
foreach($pairs as [$s,$d]){
    $rs = mysqli_fetch_assoc(mysqli_query($src, "SELECT COUNT(*) n FROM `$s`"));
    $rd = mysqli_fetch_assoc(mysqli_query($dst, "SELECT COUNT(*) n FROM `$d`"));
    $ok = $rs['n'] == $rd['n'] ? "MATCH" : "MISMATCH!";
    echo "$s({$rs['n']}) vs $d({$rd['n']}): $ok\n";
}
```
Expected: semua baris `MATCH`. Kalau `MISMATCH`, JANGAN lanjut Task 6 —
cek `_tmp_migrate_kasir_transaksi.php` output buat lihat baris mana
yang error.

- [ ] **Step 3: Sample nominal check**

```php
<?php
// _tmp_verify_nominal_sample.php — disposable, sample 5 kode_transaksi random
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$dst = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($src, "SELECT kode_transaksi, kas_awal, kas_akhir, omset FROM kasir_transactions ORDER BY RAND() LIMIT 5");
while($row = mysqli_fetch_assoc($r)){
    $kt = $row['kode_transaksi'];
    $r2 = mysqli_query($dst, "SELECT kas_awal, kas_akhir, omset FROM kasir_transactions_closing_kasir WHERE kode_transaksi = '".mysqli_real_escape_string($dst,$kt)."'");
    $row2 = mysqli_fetch_assoc($r2);
    $match = ($row2 && $row['kas_awal']==$row2['kas_awal'] && $row['kas_akhir']==$row2['kas_akhir'] && $row['omset']==$row2['omset']) ? "MATCH" : "MISMATCH!";
    echo "$kt: $match\n";
}
```
Expected: 5/5 `MATCH`.

- [ ] **Step 4: Commit checkpoint**

```bash
rm -f _tmp_migrate_kasir_transaksi.php _tmp_verify_count_transaksi.php _tmp_verify_nominal_sample.php
git commit --allow-empty -m "chore(keuangan-kasir): migrasi kas_awal/kas_akhir/kasir_transactions selesai & terverifikasi"
```

---

## Task 6: Migrasi data — closing, revisi, konfirmasi, audit log

**Files:**
- Create: `_tmp_migrate_kasir_closing.php` (disposable)

**Interfaces:**
- Consumes: `kasir_transactions_closing_kasir` (Task 5) untuk FK `transaction_id`/`kode_transaksi`.
- Produces: `closing_transaction_groups_closing_kasir`, `closing_transaction_details_closing_kasir`, `closing_revision_requests_closing_kasir`, `konfirmasi_buka_transaksi_closing_kasir`, `audit_log_closing_kasir`.

- [ ] **Step 1: Tulis & jalankan script**

Sama `copyTable()` pattern, urutan (group sebelum detail — FK):
```php
copyTable($src, $dst, 'closing_transaction_groups', 'closing_transaction_groups_closing_kasir');
copyTable($src, $dst, 'closing_transaction_details', 'closing_transaction_details_closing_kasir');
copyTable($src, $dst, 'closing_revision_requests', 'closing_revision_requests_closing_kasir');
copyTable($src, $dst, 'konfirmasi_buka_transaksi', 'konfirmasi_buka_transaksi_closing_kasir');
copyTable($src, $dst, 'audit_log', 'audit_log_closing_kasir');
```

- [ ] **Step 2: Verifikasi count** (pola sama Task 5 Step 2, 5 pasang tabel)

- [ ] **Step 3: Commit checkpoint**

```bash
rm -f _tmp_migrate_kasir_closing.php
git commit --allow-empty -m "chore(keuangan-kasir): migrasi closing/revisi/audit_log selesai & terverifikasi"
```

---

## Task 7: Migrasi data — pemasukan, pengeluaran, setoran, serah terima

**Files:**
- Create: `_tmp_migrate_kasir_keuangan.php` (disposable)

**Interfaces:**
- Consumes: `master_akun_closing_kasir` (Task 4) untuk FK `kode_akun`.
- Produces: `pemasukan_kasir_closing_kasir` (1723 baris), `pemasukan_pusat_closing_kasir`, `pengeluaran_kasir_closing_kasir` (**22156 baris — tabel terbesar, migrasi ini paling lama**), `pengeluaran_pusat_closing_kasir`, `setoran_ke_bank_closing_kasir` (337 baris), `setoran_ke_bank_detail_closing_kasir`, `setoran_keuangan_closing_kasir`, `pengambilan_setoran_closing_kasir`, `pengambilan_setoran_edit_log_closing_kasir`, `pengambilan_setoran_pembayaran_closing_kasir`, `serah_terima_kasir_closing_kasir`.

- [ ] **Step 1: Tulis & jalankan script**

```php
copyTable($src, $dst, 'pemasukan_kasir', 'pemasukan_kasir_closing_kasir');
copyTable($src, $dst, 'pemasukan_pusat', 'pemasukan_pusat_closing_kasir');
copyTable($src, $dst, 'pengeluaran_kasir', 'pengeluaran_kasir_closing_kasir');
copyTable($src, $dst, 'pengeluaran_pusat', 'pengeluaran_pusat_closing_kasir');
copyTable($src, $dst, 'setoran_ke_bank', 'setoran_ke_bank_closing_kasir');
copyTable($src, $dst, 'setoran_ke_bank_detail', 'setoran_ke_bank_detail_closing_kasir');
copyTable($src, $dst, 'setoran_keuangan', 'setoran_keuangan_closing_kasir');
copyTable($src, $dst, 'pengambilan_setoran', 'pengambilan_setoran_closing_kasir');
copyTable($src, $dst, 'pengambilan_setoran_edit_log', 'pengambilan_setoran_edit_log_closing_kasir');
copyTable($src, $dst, 'pengambilan_setoran_pembayaran', 'pengambilan_setoran_pembayaran_closing_kasir');
copyTable($src, $dst, 'serah_terima_kasir', 'serah_terima_kasir_closing_kasir');
```

Catatan performa: `pengeluaran_kasir` 22156 baris row-by-row INSERT bisa
lambat (beberapa menit). Kalau timeout di CLI run tunggal, jalankan
tabel ini sendirian dulu di script terpisah (`_tmp_migrate_pengeluaran_only.php`)
sebelum yang lain.

- [ ] **Step 2: Verifikasi count** (pola sama Task 5 Step 2, 11 pasang tabel — `pengeluaran_kasir` WAJIB persis 22156 di kedua sisi)

- [ ] **Step 3: Commit checkpoint**

```bash
rm -f _tmp_migrate_kasir_keuangan.php
git commit --allow-empty -m "chore(keuangan-kasir): migrasi pemasukan/pengeluaran/setoran/serah-terima selesai & terverifikasi"
```

---

## Task 8: Tabel `keping` — investigasi & keputusan

**Files:**
- Modify: `docs/superpowers/specs/2026-09-03-merge-modul-kasir-keuangan-design.md` (isi jawaban ambiguitas §4)

**Interfaces:**
- Produces: keputusan tertulis (drop, atau migrasi ke `keping_closing_kasir` / gabung `tbkas_kasir_detail`).

- [ ] **Step 1: Bandingkan struktur & isi**

```php
<?php
// _tmp_check_keping.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$dst = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
echo "--- web_kasir.keping ---\n";
$r = mysqli_query($src, "SHOW COLUMNS FROM keping"); while($row=mysqli_fetch_assoc($r)) echo $row['Field']." | ".$row['Type']."\n";
$r = mysqli_query($src, "SELECT COUNT(*) n FROM keping"); echo "rows: ".mysqli_fetch_assoc($r)['n']."\n";
$r = mysqli_query($src, "SELECT * FROM keping LIMIT 5"); while($row=mysqli_fetch_assoc($r)) echo json_encode($row)."\n";
echo "\n--- fitmotor.tbkas_kasir_detail ---\n";
$r = mysqli_query($dst, "SHOW COLUMNS FROM tbkas_kasir_detail"); while($row=mysqli_fetch_assoc($r)) echo $row['Field']." | ".$row['Type']."\n";
```

- [ ] **Step 2: Putuskan berdasar hasil**

Kalau `keping` adalah master pecahan uang (Rp 1000/2000/5000/dst, statis,
tidak berubah) → migrasi jadi `keping_closing_kasir` (tabel master kecil,
pola sama Task 4). Kalau ternyata kosong/tidak dipakai fitur aktif →
drop, catat alasan di spec §4.

- [ ] **Step 3: Update spec & commit**

Edit bagian §4 spec, isi baris "Tabel `keping`" dengan keputusan final
dan alasannya (bukan lagi "cek dulu").
```bash
rm -f _tmp_check_keping.php
git add docs/superpowers/specs/2026-09-03-merge-modul-kasir-keuangan-design.md
git commit -m "docs(keuangan-kasir): keputusan final tabel keping"
```

---

## Task 9: Rebuild VIEW

**Files:**
- Create: `docs/sql/tblkasir_views.sql`

**Interfaces:**
- Consumes: semua tabel `*_closing_kasir` dari Task 2-7 (VIEW ini query dari situ).
- Produces: 10 VIEW baru di `fitmotor_dbbengkel`, dipakai laporan/UI modul keuangan-kasir.

- [ ] **Step 1: Ambil definisi VIEW asli**

```php
<?php
// _tmp_show_view.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$views = ['v_transaksi_ada_selisih','v_transaksi_dikembalikan_cs','v_transaksi_perlu_validasi','view_pemasukan_combined','view_pemasukan_kasir','view_pemasukan_pusat','view_pemasukan_with_closing','view_pengeluaran_kasir','view_pengeluaran_pusat','view_setoran_with_closing'];
foreach($views as $v){
    $r = mysqli_query($src, "SHOW CREATE VIEW `$v`");
    $row = mysqli_fetch_assoc($r);
    echo "-- $v\n".$row['Create View'].";\n\n";
}
```
Simpan ke `docs/sql/tblkasir_views.sql`, `rm -f _tmp_show_view.php`.

- [ ] **Step 2: Edit — ganti semua nama tabel & VIEW sesuai mapping**

Di tiap `CREATE VIEW`, ganti:
1. Nama VIEW itu sendiri: suffix jadi `*_closing_kasir` (nama VIEW asli dipertahankan, contoh
   `view_pemasukan_kasir` → `view_pemasukan_kasir_closing_kasir`), konsisten sama
   pola VIEW fitmotor yang sudah ada (`view_penjualan_header`, dst —
   prefix `view_`, bukan `v_`).
2. Semua nama tabel di `FROM`/`JOIN` sesuai mapping tabel spec §3.1
   (`kasir_transactions`→`kasir_transactions_closing_kasir`, dst).

- [ ] **Step 3: Eksekusi & verifikasi**

```bash
"/mnt/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe" -h localhost -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel < docs/sql/tblkasir_views.sql
```
```php
<?php
// _tmp_verify_views.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
foreach(['v_transaksi_ada_selisih_closing_kasir','v_transaksi_dikembalikan_cs_closing_kasir','v_transaksi_perlu_validasi_closing_kasir','view_pemasukan_combined_closing_kasir','view_pemasukan_kasir_closing_kasir','view_pemasukan_pusat_closing_kasir','view_pemasukan_with_closing_closing_kasir','view_pengeluaran_kasir_closing_kasir','view_pengeluaran_pusat_closing_kasir','view_setoran_with_closing_closing_kasir'] as $v){
    $r = mysqli_query($c, "SELECT COUNT(*) n FROM `$v`");
    echo "$v: ".($r ? "OK, ".mysqli_fetch_assoc($r)['n']." baris" : "ERROR: ".mysqli_error($c))."\n";
}
```
Expected: 10 baris "OK" (nama VIEW final sesuaikan dengan yang benar-benar
dipakai — sesuaikan daftar di Step 3 dengan hasil rename Step 2, jangan
asumsi nama di atas kalau beda saat Step 2).

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_verify_views.php
git add docs/sql/tblkasir_views.sql
git commit -m "feat(keuangan-kasir): rebuild VIEW laporan kasir"
```

---

## Task 10: RBAC — permission & role mapping

**Files:**
- Modify: struktur data `tb_permissions`, `tb_user_roles` (via INSERT, bukan ubah skema — cek dulu struktur exact tabel ini dengan `SHOW COLUMNS` sebelum nulis INSERT, JANGAN asumsi nama kolom)
- Create: `_tmp_seed_kasir_permissions.php` (disposable)

**Interfaces:**
- Produces: 4 permission baru (`kasir_super_admin`, `kasir_admin_keuangan`, `kasir_staff_cabang`, `kasir_kasir_cabang` — atau nama sesuai konvensi kolom asli setelah Step 1) terdaftar dan bisa dipakai guard akses menu Task 12.

- [ ] **Step 1: Cek struktur asli**

```php
<?php
// _tmp_check_rbac_schema.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
foreach(['tb_permissions','tb_user_roles'] as $t){
    echo "--- $t ---\n";
    $r = mysqli_query($c, "SHOW COLUMNS FROM `$t`");
    while($row=mysqli_fetch_assoc($r)) echo $row['Field']." | ".$row['Type']."\n";
    $r = mysqli_query($c, "SELECT * FROM `$t` LIMIT 3");
    while($row=mysqli_fetch_assoc($r)) echo json_encode($row)."\n";
}
```
Run, catat kolom exact (nama field permission code, nama field role,
apakah ada tabel pivot terpisah).

- [ ] **Step 2: Tulis INSERT sesuai struktur yang ditemukan**

Sesuaikan nama kolom dengan hasil Step 1 (tidak bisa ditulis di sini
karena struktur exact belum diketahui sebelum Step 1 dijalankan —
worker task ini WAJIB jalankan Step 1 dulu, baca hasilnya, baru tulis
INSERT yang cocok kolomnya). Prinsip mapping (dari spec §3.3):
- `super_admin` (web_kasir) → permission akses penuh semua cabang + approval
- `admin` → permission "keuangan pusat" (approval setoran, tanpa akses input transaksi cabang)
- `user` → permission "staff cabang" (lihat + input non-approval)
- `kasir` → permission "kasir cabang" (buka/tutup kasir, input pemasukan/pengeluaran shift sendiri)

- [ ] **Step 3: Verifikasi & commit**

Query balik permission yang baru diinsert, pastikan muncul. Hapus
`_tmp_check_rbac_schema.php` dan `_tmp_seed_kasir_permissions.php`.
```bash
git commit --allow-empty -m "feat(keuangan-kasir): seed permission RBAC modul kasir"
```

---

## Task 11: Port file PHP — kas awal/akhir (buka/tutup shift)

**Files:**
- Create: `app/_keuangan/kasir/kas_awal.php` (source: `C:\laragon\www\web_kasir\website_kasir\kas_awal*.php` — cek dulu nama file exact di source, spec belum verifikasi nama file 1:1)
- Create: `app/_keuangan/kasir/kas_akhir.php` (source setara)
- Create: `app/_keuangan/kasir/koneksi_kasir.php` (helper include, replace `config.php` web_kasir)

**Interfaces:**
- Consumes: `kas_awal_closing_kasir`, `kas_akhir_closing_kasir`, `detail_kas_awal_closing_kasir`, `detail_kas_akhir_closing_kasir` (Task 5); `$koneksi` dari `app/koneksi.php` fitmotor; `$_SESSION['_cabang']`, session `kode_karyawan` (nama variabel exact — verifikasi dulu di Step 1, JANGAN asumsi).

- [ ] **Step 1: Cari nama variabel session login fitmotor yang eksis**

```bash
grep -rn "SESSION\['kode_karyawan'\]\|SESSION\['user'\]\|SESSION\['nama_user'\]" app/_admincab/login*.php app/_admincab/cek_login*.php 2>&1 | head -20
```
Kalau file itu tidak ada di path itu, cari dulu file login yang benar:
```bash
grep -rln "session_start\|SESSION\['_cabang'\]" app/_admincab/*.php 2>&1 | head -10
```
Catat nama variabel session persis yang menyimpan kode_karyawan user
login — dipakai di semua step berikutnya, ganti setiap referensi
`$_SESSION['kode_karyawan']` di contoh kode di bawah dengan nama
variabel session yang benar hasil temuan ini.

- [ ] **Step 2: Baca file sumber, catat query & struktur form**

```bash
cat "/mnt/c/laragon/www/web_kasir/website_kasir/kas_awal.php"
```
(Kalau nama file beda, sesuaikan — cek `ls "/mnt/c/laragon/www/web_kasir/website_kasir" | grep -i kas_awal`)
Catat: query INSERT ke `kas_awal`/`detail_kas_awal`, kolom form input
(nominal per pecahan uang), validasi yang ada.

- [ ] **Step 3: Tulis `app/_keuangan/kasir/koneksi_kasir.php`**

```php
<?php
// Helper koneksi khusus modul kasir — reuse koneksi fitmotor, bukan bikin baru
require_once __DIR__ . '/../../koneksi.php'; // $koneksi (mysqli) sudah tersedia dari sini
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['_cabang'])) {
    header('Location: /aplikasi/app/login.php'); // sesuaikan path login fitmotor yang benar
    exit;
}
$kode_cabang_aktif = $_SESSION['_cabang']; // ini cabang_ref_kode, dipakai semua query *_closing_kasir
$kode_karyawan_aktif = $_SESSION['kode_karyawan'] ?? null; // ganti sesuai temuan Step 1 kalau nama beda
?>
```

- [ ] **Step 4: Tulis `app/_keuangan/kasir/kas_awal.php`**

Port logic dari file sumber (Step 2), ganti:
- `require 'config.php'` → `require_once 'koneksi_kasir.php'`
- Semua `INSERT INTO kas_awal` → `INSERT INTO kas_awal_closing_kasir`
- Semua `INSERT INTO detail_kas_awal` → `INSERT INTO detail_kas_awal_closing_kasir`
- Kolom `kode_cabang` di query pakai `$kode_cabang_aktif` (bukan dari
  session/table `cabang`/`users` lama)
- Kolom `kode_karyawan` di query pakai `$kode_karyawan_aktif`

Struktur query INSERT (contoh, sesuaikan kolom exact dengan hasil
`SHOW COLUMNS` Task 3):
```php
$stmt = mysqli_prepare($koneksi, "INSERT INTO kas_awal_closing_kasir (kode_transaksi, total_nilai, tanggal, waktu, status, kode_karyawan) VALUES (?, ?, CURDATE(), CURTIME(), 'on proses', ?)");
mysqli_stmt_bind_param($stmt, 'sds', $kode_transaksi, $total_nilai, $kode_karyawan_aktif);
mysqli_stmt_execute($stmt);
```

- [ ] **Step 5: Tulis `app/_keuangan/kasir/kas_akhir.php`** (pola sama Step 4, target `kas_akhir_closing_kasir`/`detail_kas_akhir_closing_kasir`)

- [ ] **Step 6: Smoke test manual via Claude-in-Chrome**

Login sebagai user role kasir, buka `app/_keuangan/kasir/kas_awal.php`,
isi nominal pecahan, submit. Verifikasi lewat query DB langsung (bukan
cuma tampilan UI — lihat memory `feedback_wajib_alur_kerja_5_langkah`):
```php
<?php
// _tmp_verify_kas_awal_test.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($c, "SELECT * FROM kas_awal_closing_kasir ORDER BY id DESC LIMIT 1");
echo json_encode(mysqli_fetch_assoc($r), JSON_PRETTY_PRINT)."\n";
```
Expected: baris baru muncul dengan `kode_karyawan` & nominal sesuai
input, bukan NULL/kosong.

- [ ] **Step 7: Commit**

```bash
rm -f _tmp_verify_kas_awal_test.php
git add app/_keuangan/kasir/kas_awal.php app/_keuangan/kasir/kas_akhir.php app/_keuangan/kasir/koneksi_kasir.php
git commit -m "feat(keuangan-kasir): port kas awal/akhir (buka-tutup shift)"
```

---

## Task 12: Port file PHP — pemasukan & pengeluaran kasir

**Files:**
- Create: `app/_keuangan/kasir/pemasukan.php`
- Create: `app/_keuangan/kasir/pengeluaran.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php` (Task 11), `pemasukan_kasir_closing_kasir`, `pengeluaran_kasir_closing_kasir`, `master_akun_closing_kasir`, `master_nama_transaksi_closing_kasir` (Task 4/7).

- [ ] **Step 1: Baca file sumber** (`pemasukan_kasir1.php`/`edit_pemasukan1.php`/`kas_masuk_add.php`-setara — cek nama exact via `ls` sumber dulu, JANGAN asumsi nama file, banyak varian `_asli`/`1`/`_fixed` di source seperti terlihat waktu investigasi)

```bash
ls "/mnt/c/laragon/www/web_kasir/website_kasir" | grep -i "pemasukan\|pengeluaran"
```
Pilih file aktif (bukan `_asli`/`1`/`_fixed`/`.bak` — cek yang direferensikan `sidebar.php`, itu yang aktif dipakai; file bernomor/varian biasanya legacy/orphan sama polanya kayak fitmotor).

- [ ] **Step 2: Tulis `pemasukan.php`** — pola sama Task 11 Step 4, target
`pemasukan_kasir_closing_kasir`, kolom `kode_akun` FK ke `master_akun_closing_kasir`.

- [ ] **Step 3: Tulis `pengeluaran.php`** — target `pengeluaran_kasir_closing_kasir`.

- [ ] **Step 4: Smoke test + verifikasi DB** (pola sama Task 11 Step 6)

- [ ] **Step 5: Commit**

```bash
git add app/_keuangan/kasir/pemasukan.php app/_keuangan/kasir/pengeluaran.php
git commit -m "feat(keuangan-kasir): port pemasukan/pengeluaran kasir"
```

---

## Task 13: Port file PHP — closing & validasi setoran keuangan pusat

**Files:**
- Create: `app/_keuangan/kasir/closing.php`
- Create: `app/_keuangan/kasir/closing_revisi.php`
- Create: `app/_keuangan/kasir/validasi_keuangan_pusat.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php`, `kasir_transactions_closing_kasir`, `closing_transaction_groups_closing_kasir`, `closing_transaction_details_closing_kasir`, `closing_revision_requests_closing_kasir` (Task 5/6).

- [ ] **Step 1: Baca file sumber** (`close_transaksi.php`, `close_transaksi1.php`, `admin_closing_revision.php`, `closing_revision_request.php`, `closing_revision_helpers.php` — file terbesar di source, 141KB/38KB/15KB/12.5KB/22.8KB, port bertahap per fungsi, JANGAN port sekaligus 1 file raksasa — pecah sesuai tanggung jawab: closing biasa vs revisi vs helper)

- [ ] **Step 2: Tulis `closing.php`** — logic grouping "dipinjam/meminjam"
antar kasir dalam 1 hari, insert `closing_transaction_groups_closing_kasir` +
`closing_transaction_details_closing_kasir`, update `deposit_status` di
`kasir_transactions_closing_kasir`.

- [ ] **Step 3: Tulis `closing_revisi.php`** — port dari
`closing_revision_request.php` + `closing_revision_helpers.php`, target
`closing_revision_requests_closing_kasir`.

- [ ] **Step 4: Tulis `validasi_keuangan_pusat.php`** — update
`deposit_status`/`deposit_difference_status` di `kasir_transactions_closing_kasir`
sesuai enum yang sudah ada (`Sudah Disetor ke Keuangan`, `Validasi
Keuangan OK`, dst — pakai value enum PERSIS dari `SHOW COLUMNS` Task 3,
jangan bikin value baru).

- [ ] **Step 5: Smoke test + verifikasi DB** (pola sama Task 11 Step 6,
cek `closing_transaction_groups_closing_kasir.total_closing` matching sum
`closing_transaction_details_closing_kasir.nominal`)

- [ ] **Step 6: Commit**

```bash
git add app/_keuangan/kasir/closing.php app/_keuangan/kasir/closing_revisi.php app/_keuangan/kasir/validasi_keuangan_pusat.php
git commit -m "feat(keuangan-kasir): port closing, revisi closing, validasi keuangan pusat"
```

---

## Task 14: Port file PHP — setoran bank, pengambilan setoran, serah terima

**Files:**
- Create: `app/_keuangan/kasir/setoran_bank.php`
- Create: `app/_keuangan/kasir/pengambilan_setoran.php`
- Create: `app/_keuangan/kasir/serah_terima.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php`, `setoran_ke_bank_closing_kasir`(+detail), `pengambilan_setoran_closing_kasir`(+log/pembayaran), `serah_terima_kasir_closing_kasir`, `master_rekening_cabang_closing_kasir` (Task 2/4/7).

- [ ] **Step 1: Baca file sumber** (`api_setor_bank_pengambilan.php`,
`api_edit_nominal_pengambilan.php`, `api_pelunasan_manual.php`,
`api_validate_pelunasan_document.php`, `services/pelunasan_hutang/`)

- [ ] **Step 2-4: Tulis ketiga file** — pola sama task sebelumnya,
target tabel `setoran_ke_bank_closing_kasir`/`pengambilan_setoran_closing_kasir`/`serah_terima_kasir_closing_kasir`.

- [ ] **Step 5: Smoke test + verifikasi DB**

- [ ] **Step 6: Commit**

```bash
git add app/_keuangan/kasir/setoran_bank.php app/_keuangan/kasir/pengambilan_setoran.php app/_keuangan/kasir/serah_terima.php
git commit -m "feat(keuangan-kasir): port setoran bank, pengambilan setoran, serah terima"
```

---

## Task 15: Wire menu & sidebar

**Files:**
- Modify: `app/menu_config.php`

**Interfaces:**
- Consumes: permission dari Task 10, file-file dari Task 11-14.
- Produces: menu "Keuangan > Kasir" muncul di sidebar untuk role yang punya permission terkait.

- [ ] **Step 1: Cari struktur menu existing sebagai referensi**

```bash
grep -n "'title' => 'Kas Masuk'" -A5 -B15 app/menu_config.php
```
Baca struktur array sekitar baris itu (grup menu, format `title`/`url`/`permission`).

- [ ] **Step 2: Tambah grup menu baru**

Ikuti format exact yang ditemukan Step 1, tambahkan grup baru (contoh
struktur, sesuaikan array nesting persis dengan pola asli):
```php
[
    'title' => 'Keuangan Kasir',
    'icon' => 'icon-wallet',
    'submenu' => [
        ['title' => 'Buka Kasir', 'url' => '_keuangan/kasir/kas_awal.php', 'permission' => 'kasir_kasir_cabang'],
        ['title' => 'Tutup Kasir', 'url' => '_keuangan/kasir/kas_akhir.php', 'permission' => 'kasir_kasir_cabang'],
        ['title' => 'Pemasukan', 'url' => '_keuangan/kasir/pemasukan.php', 'permission' => 'kasir_kasir_cabang'],
        ['title' => 'Pengeluaran', 'url' => '_keuangan/kasir/pengeluaran.php', 'permission' => 'kasir_kasir_cabang'],
        ['title' => 'Closing', 'url' => '_keuangan/kasir/closing.php', 'permission' => 'kasir_staff_cabang'],
        ['title' => 'Revisi Closing', 'url' => '_keuangan/kasir/closing_revisi.php', 'permission' => 'kasir_admin_keuangan'],
        ['title' => 'Validasi Keuangan Pusat', 'url' => '_keuangan/kasir/validasi_keuangan_pusat.php', 'permission' => 'kasir_admin_keuangan'],
        ['title' => 'Setoran ke Bank', 'url' => '_keuangan/kasir/setoran_bank.php', 'permission' => 'kasir_admin_keuangan'],
        ['title' => 'Pengambilan Setoran', 'url' => '_keuangan/kasir/pengambilan_setoran.php', 'permission' => 'kasir_admin_keuangan'],
        ['title' => 'Serah Terima Kasir', 'url' => '_keuangan/kasir/serah_terima.php', 'permission' => 'kasir_kasir_cabang'],
    ],
],
```
(nama permission string harus PERSIS sama dengan yang di-seed Task 10 Step 2)

- [ ] **Step 3: Smoke test tampilan menu**

Via Claude-in-Chrome, login sebagai tiap role (super_admin/admin/user/kasir
mapping Task 10), verifikasi menu "Keuangan Kasir" muncul dengan submenu
sesuai permission masing-masing role, dan setiap link bisa diklik tanpa
403/500.

- [ ] **Step 4: Commit**

```bash
git add app/menu_config.php
git commit -m "feat(keuangan-kasir): wire menu Keuangan > Kasir ke sidebar"
```

---

## Task 16: Smoke test E2E per cabang

**Files:**
- Tidak ada file baru — task verifikasi murni.

**Interfaces:**
- Consumes: seluruh Task 11-15 (app harus lengkap).
- Produces: laporan hasil test tertulis (ditempel ke PR description / commit message Task 17).

- [ ] **Step 1: Test alur penuh 1 cabang via Claude-in-Chrome**

Login role kasir di cabang PST → buka kasir → input 2 pemasukan, 1
pengeluaran → tutup kasir → closing → login role admin keuangan →
validasi setoran → setor ke bank. Screenshot tiap langkah.

- [ ] **Step 2: Ulangi utk sisa 4 cabang** (CIKDITIRO, PACUL, PESALAKAN, TRAYEMAN) — minimal alur buka-tutup kasir + closing (skip validasi penuh kalau sudah pernah di Step 1).

- [ ] **Step 3: Verifikasi lewat query DB langsung, bukan cuma UI**

```php
<?php
// _tmp_verify_e2e.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($c, "SELECT kode_transaksi, kode_cabang, deposit_status, omset FROM kasir_transactions_closing_kasir WHERE tanggal_transaksi = CURDATE() ORDER BY id DESC");
while($row = mysqli_fetch_assoc($r)) echo json_encode($row)."\n";
```
Expected: transaksi hari ini muncul dengan `kode_cabang` benar, `deposit_status` berubah sesuai alur yang dijalankan.

- [ ] **Step 4: Commit laporan**

```bash
rm -f _tmp_verify_e2e.php
git commit --allow-empty -m "test(keuangan-kasir): smoke test E2E 5 cabang selesai, semua alur PASS"
```
(kalau ada yang FAIL, JANGAN commit sebagai PASS — perbaiki dulu task
terkait, ulangi Task 16 sampai semua alur benar-benar lolos)

---

## Task 17: Cutover — matikan web_kasir lama, hapus bridge HPP

**Files:**
- Modify: `C:\laragon\www\web_kasir\website_kasir\config.php` (di luar repo git ini — edit langsung via filesystem)
- Modify: `docs/superpowers/specs/2026-09-03-merge-modul-kasir-keuangan-design.md` (update status jadi "Cutover selesai")

**Interfaces:**
- Consumes: Task 16 harus PASS semua sebelum task ini boleh jalan.

- [ ] **Step 1: Matikan akses web_kasir lama**

Edit `C:\laragon\www\web_kasir\website_kasir\config.php`, tambahkan di
paling atas setelah `<?php`:
```php
die('Modul ini sudah digabung ke FIT MOTOR Web Base > Keuangan > Kasir. Hubungi admin IT kalau ada kebutuhan akses data lama.');
```
(bukan hapus filenya — biar data & kode masih ada buat rollback kalau
perlu, cuma diblokir aksesnya)

- [ ] **Step 2: Drop 4 tabel bridge HPP + 3 tabel app-lama di DB sumber**

```php
<?php
// _tmp_cleanup_bridge.php — disposable, jalankan HANYA setelah Task 16 PASS total
// PENTING: `users` dan `masterkeys` SENGAJA TIDAK didrop di sini —
// masih dipakai live oleh login_dashboard/sync_users_api.php buat sync
// ke priori-tech (projek eksternal). Baru boleh didrop setelah Task 21
// (alihkan bridge) selesai & terverifikasi. Lihat spec §3.8.
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
foreach(['hpp_api_keys','hpp_bypass','hpp_bypass_request','hpp_sync_log','hpp_sync_status','dynamic_sidebars','user_sidebar_settings'] as $t){
    $ok = mysqli_query($c, "DROP TABLE IF EXISTS `$t`");
    echo "$t: ".($ok ? "dropped" : mysqli_error($c))."\n";
}
```
(DB sumber `fitmotor_maintance-beta` sendiri TIDAK didrop total di task
ini — cuma tabel bridge yang sudah tidak relevan; keputusan drop DB
utuh menyusul beberapa hari setelah cutover stabil, di luar scope plan
ini. `users`+`masterkeys` ditangani terpisah di Task 21.)

- [ ] **Step 3: Update status spec**

Edit baris `**Status:**` di spec jadi `Cutover selesai — <tanggal>`.

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_cleanup_bridge.php
git add docs/superpowers/specs/2026-09-03-merge-modul-kasir-keuangan-design.md
git commit -m "chore(keuangan-kasir): cutover — matikan web_kasir lama, hapus bridge HPP"
```

---

## Task 18: Arsip tabel & file kasir mati fitmotor (langkah terpisah, spec §3.6)

**Files:**
- Create: `backups/tbkas_kasir_legacy_dump_<timestamp>.sql`
- Modify: `archive/` (pindahkan file PHP mati)

**Interfaces:**
- Consumes: Task 17 harus sudah cutover sukses (jeda minimal beberapa hari operasional, bukan langsung sesudah Task 17 — keputusan waktu ada di Rafi, task ini dieksekusi terpisah saat diminta).

**Verifikasi 2026-09-03**: selain 7 tabel "kas kasir" awal, `tb_bank`
(0 pemakaian via `rg -l "tb_bank\b" app --glob "*.php"`) dan
`master_bank` (1 file `app/master_bank.php`, TAPI gak ada di
`menu_config.php` → halaman yatim) juga overlap konsepnya sama
`master_rekening_cabang_closing_kasir` — ditambahkan ke daftar drop,
total 9 tabel + 1 file tambahan.

- [ ] **Step 1: Dump 9 tabel kasir mati fitmotor**

```bash
"/mnt/c/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe" -h localhost -u fitmotor_LOGIN -pSayalupa12 fitmotor_dbbengkel tbkas_kasir_header tbkas_kasir_detail tbkas_kasir tblakunkas tbakun tbakun_pos tblkas_keluar_masuk tb_bank master_bank > backups/tbkas_kasir_legacy_dump_$(date +%Y%m%d_%H%M%S).sql
```

- [ ] **Step 2: Drop 9 tabel**

```php
<?php
// _tmp_drop_legacy_kasir.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
foreach(['tbkas_kasir_header','tbkas_kasir_detail','tbkas_kasir','tblakunkas','tbakun','tbakun_pos','tblkas_keluar_masuk','tb_bank','master_bank'] as $t){
    $ok = mysqli_query($c, "DROP TABLE IF EXISTS `$t`");
    echo "$t: ".($ok ? "dropped" : mysqli_error($c))."\n";
}
```

- [ ] **Step 3: Arsip file PHP mati**

```bash
mkdir -p archive/legacy-kasir-lama
git mv "app/kas kasir" archive/legacy-kasir-lama/
git mv app/kas_awal.php app/kas_akhir.php app/kas_awal_proses.php app/kas_akhir_proses.php app/master_bank.php archive/legacy-kasir-lama/ 2>&1 || true
```
(pakai `git mv` biar history rename kedeteksi git, bukan delete+create
baru — pola yang sudah dipakai project ini, lihat memory
`project_arsip_file_tidak_terpakai_2026-08-09`)

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_drop_legacy_kasir.php
git add backups/tbkas_kasir_legacy_dump_*.sql archive/legacy-kasir-lama
git commit -m "chore(keuangan-kasir): arsip tabel & file kasir mati fitmotor lama"
```

---

## Task 19: Update checklist-projek & memory

**Files:**
- Tidak ada file kode — administrasi progress.

- [ ] **Step 1: Update checklist-projek**

```bash
./scripts/update-checklist.sh "Modul Keuangan Kasir (merge web_kasir)" "selesai" 100 "Merge modul closing kasir dari web_kasir ke FIT MOTOR Web Base selesai, cutover sukses, smoke test 5 cabang PASS"
```
Kalau fitur belum terdaftar (kemungkinan besar, ini fitur baru), pakai
mode `--create` dulu sebelum sesi berikutnya mulai kerja Task 1:
```bash
./scripts/update-checklist.sh --create "Modul Keuangan Kasir (merge web_kasir)" "FIT MOTOR WEB BASE" "must" "belum" "Planning selesai, siap eksekusi merge"
```

- [ ] **Step 2: Tulis memory project**

Simpan file memory baru
`~/.claude/projects/.../memory/project_merge_kasir_keuangan_2026-09-03.md`
berisi ringkasan: keputusan desain final, mapping tabel, status tiap
task, temuan penting selama eksekusi (terutama hasil Task 8 tabel
`keping`, Task 10 struktur RBAC exact, Task 11 nama variabel session).
Update `MEMORY.md` index dengan satu baris pointer.

---

## Task 20: Port dashboard kasir/admin

**Files:**
- Create: `app/_keuangan/kasir/dashboard.php` (source: `admin_dashboard.php` di web_kasir, satu-satunya dashboard aktif — dilink `includes/sidebar.php`)

**Interfaces:**
- Consumes: `koneksi_kasir.php` (Task 11), `kasir_transactions_closing_kasir` (Task 5), `tbuser`/RBAC (Task 10) buat cek role.

- [ ] **Step 1: Baca file sumber**

```bash
grep -n "SELECT\|FROM \|function " "/mnt/c/laragon/www/web_kasir/website_kasir/admin_dashboard.php"
```
Catat: query cek role (`SELECT role FROM users WHERE kode_karyawan = ?`
di source — ganti ke cek permission RBAC Task 10, bukan tabel `users`
lama), query rekap `kasir_transactions` per cabang/tanggal (ganti ke
`kasir_transactions_closing_kasir`), dan query distinct cabang (ganti ke join
`tbcabang` via `cabang_ref_kode`, bukan `DISTINCT nama_cabang` manual
dari data transaksi).

- [ ] **Step 2: Tulis `dashboard.php`**

Port logic, ganti semua referensi seperti pola task sebelumnya
(`kasir_transactions`→`kasir_transactions_closing_kasir`, cek role via RBAC fitmotor,
`kode_cabang` pakai `$kode_cabang_aktif` dari `koneksi_kasir.php`).

- [ ] **Step 3: Smoke test + verifikasi DB**

Login role admin keuangan & role kasir cabang via Claude-in-Chrome,
buka dashboard, pastikan rekap omset/closing per cabang tampil dan
angkanya cocok dengan query manual ke `kasir_transactions_closing_kasir` (bandingkan
`SUM(omset)` hari berjalan).

- [ ] **Step 4: Jadikan `dashboard.php` landing page modul**

Update menu Task 15 — link "Keuangan Kasir" grup di sidebar mengarah
duluan ke `dashboard.php` sebagai entry point, bukan langsung ke
`kas_awal.php`.

- [ ] **Step 5: Commit**

```bash
git add app/_keuangan/kasir/dashboard.php app/menu_config.php
git commit -m "feat(keuangan-kasir): port dashboard kasir/admin"
```

---

## Task 21: Alihkan bridge SSO priori-tech ke `tbuser` fitmotor

**Files:**
- Modify: `C:\laragon\www\login_dashboard\sync_users_api.php` (di luar repo git ini — edit langsung via filesystem, projek terpisah)

**Interfaces:**
- Consumes: `tbuser`, RBAC permission Task 10 (`fitmotor_dbbengkel`).
- Produces: endpoint tetap mengembalikan JSON bentuk sama
  (`kode_karyawan, nama_karyawan, role, nama_cabang, kode_cabang`) tapi
  sumber data dari `fitmotor_dbbengkel.tbuser` (+ RBAC), bukan lagi
  `fitmotor_maintance-beta.users`/`masterkeys`.

**PRASYARAT WAJIB**: task ini baru boleh dikerjakan SETELAH dikoordinasikan
dengan pihak yang pegang priori-tech (bukan cuma commit sepihak — endpoint
ini dipakai sistem eksternal aktif, salah ubah bentuk response bisa
mematikan priori-tech). Konfirmasi dulu ke Rafi sebelum eksekusi Step 2.

- [ ] **Step 1: Baca query asli & pahami kontrak response**

```bash
cat "/mnt/c/laragon/www/login_dashboard/sync_users_api.php"
```
Catat persis: nama field JSON yang dikembalikan, filter
`masterkeys.status_aktif = 1` (artinya cuma karyawan aktif yang
disertakan — cari padanan kolom `is_active`/`status_row` yang sudah ada
di `tbuser` fitmotor).

- [ ] **Step 2: Tulis query pengganti**

Ganti koneksi PDO di `config.php` (di direktori sama) supaya nunjuk ke
`fitmotor_dbbengkel` (bukan `fitmotor_maintance-beta`), lalu ganti query:
```php
$stmt = $pdo->query(
    "SELECT u.kode_karyawan, u.nama_user AS nama_karyawan, u.role_name AS role,
            c.nama_cabang, c.cabang_ref_kode AS kode_cabang
     FROM tbuser u
     LEFT JOIN tbcabang c ON c.kode_cabang = u.kode_cabang
     WHERE u.is_active = 'active'"
);
```
(sesuaikan nama kolom persis kalau ternyata beda dari yang ditemukan
Task 1/investigasi awal — `tbuser.role_name` mungkin perlu di-map ke
role string yang sama formatnya dengan `users.role` lama kalau
priori-tech expect value tertentu, bukan asumsi cocok otomatis; cek
dengan pihak priori-tech dulu sesuai prasyarat di atas)

- [ ] **Step 3: Test endpoint manual**

```bash
curl -s -H "X-Sync-Secret: <ambil dari sync_users_api.php>" "http://localhost/login_dashboard/sync_users_api.php" | head -c 2000
```
Expected: JSON array dengan shape sama seperti sebelum perubahan,
bandingkan manual beberapa baris dengan hasil query lama.

- [ ] **Step 4: Setelah dikonfirmasi priori-tech tetap jalan normal — baru drop `users`+`masterkeys`**

```php
<?php
// _tmp_drop_users_masterkeys.php — disposable, jalankan HANYA setelah Step 3 dikonfirmasi OK oleh priori-tech
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
foreach(['users','masterkeys'] as $t){
    $ok = mysqli_query($c, "DROP TABLE IF EXISTS `$t`");
    echo "$t: ".($ok ? "dropped" : mysqli_error($c))."\n";
}
```

- [ ] **Step 5: Commit**

```bash
rm -f _tmp_drop_users_masterkeys.php
git commit --allow-empty -m "chore(keuangan-kasir): alihkan bridge priori-tech ke tbuser fitmotor, drop users/masterkeys lama"
```
(commit di repo `web-bengkel` sebagai penanda, walau file yang diubah
ada di repo `login_dashboard` terpisah — kalau `login_dashboard` juga
git repo, commit perubahan asalnya di situ juga)

---

## Task 22: Tambah `tbuser.kode_kasir` & migrasi field karyawan yang belum ada

**Files:**
- Create: `_tmp_add_kode_kasir.php` (disposable)

**Interfaces:**
- Consumes: `tbuser` (fitmotor), `web_kasir.users.kode_user`.
- Produces: kolom baru `tbuser.kode_kasir`, dipakai quick-login kasir di Task 11/24.

- [ ] **Step 1: Tambah kolom**

```php
<?php
// _tmp_add_kode_kasir.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$ok = mysqli_query($c, "ALTER TABLE tbuser ADD COLUMN kode_kasir VARCHAR(3) NULL UNIQUE AFTER kode_karyawan");
echo $ok ? "kolom ditambahkan\n" : mysqli_error($c)."\n";
```

- [ ] **Step 2: Isi data dari sumber**

```php
<?php
// _tmp_migrate_kode_kasir.php — disposable
$src = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_maintance-beta');
$dst = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($src, "SELECT kode_user, kode_karyawan FROM users WHERE kode_user IS NOT NULL AND kode_user != ''");
$n = 0; $skip = 0;
while($row = mysqli_fetch_assoc($r)){
    $ku = mysqli_real_escape_string($dst, $row['kode_user']);
    $kk = mysqli_real_escape_string($dst, $row['kode_karyawan']);
    $ok = mysqli_query($dst, "UPDATE tbuser SET kode_kasir = '$ku' WHERE kode_karyawan = '$kk'");
    if($ok && mysqli_affected_rows($dst) > 0) $n++; else $skip++;
}
echo "$n terupdate, $skip skip (kode_karyawan gak ketemu di tbuser)\n";
```
Expected: `$skip` = 0 (semua 18 user web_kasir harus match ke `tbuser`
via validasi Task 1). Kalau ada skip, cek lagi hasil Task 1.

- [ ] **Step 3: Commit**

```bash
rm -f _tmp_add_kode_kasir.php _tmp_migrate_kode_kasir.php
git commit --allow-empty -m "feat(keuangan-kasir): tambah tbuser.kode_kasir buat quick-login shift"
```

---

## Task 23: DDL + migrasi + port `data_penjualan`/`data_servis` (input manual harian)

**Files:**
- Modify: `docs/sql/tblkasir_schema_transaksi.sql` (tambah 2 tabel)
- Create: `app/_keuangan/kasir/input_penjualan_servis.php`

**Interfaces:**
- Consumes: `master_akun_closing_kasir` pola sama tabel lain; `koneksi_kasir.php` (Task 11).
- Produces: `data_penjualan_closing_kasir`, `data_servis_closing_kasir` — dipakai cross-check manual kasir vs sistem.

- [ ] **Step 1: DDL** — pola sama Task 3, ambil `SHOW CREATE TABLE` dari
`data_penjualan`/`data_servis` sumber, rename ke `data_penjualan_closing_kasir`/
`data_servis_closing_kasir`, tambah FK `kode_cabang`→`tbcabang.cabang_ref_kode`,
`kode_karyawan`→`tbuser.kode_karyawan`.

- [ ] **Step 2: Migrasi data** — pola `copyTable()` sama Task 4-7.

- [ ] **Step 3: Baca file sumber & port**

```bash
grep -n "SELECT \|FROM \|INSERT INTO" "/mnt/c/laragon/www/web_kasir/website_kasir/input_penjualan_servis.php"
```
Port ke `app/_keuangan/kasir/input_penjualan_servis.php`, ganti target
tabel `data_penjualan`/`data_servis` → `data_penjualan_closing_kasir`/
`data_servis_closing_kasir`, koneksi pakai `koneksi_kasir.php`.

- [ ] **Step 4: Smoke test + verifikasi DB, commit**

```bash
git add docs/sql/tblkasir_schema_transaksi.sql app/_keuangan/kasir/input_penjualan_servis.php
git commit -m "feat(keuangan-kasir): port input manual penjualan/servis harian"
```

---

## Task 24: Port `index_kasir.php` — landing dashboard kasir harian

**Files:**
- Create: `app/_keuangan/kasir/index_kasir.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php` (Task 11), `kasir_transactions_closing_kasir`, `kas_awal_closing_kasir/akhir` (Task 5), `tbuser.kode_kasir` (Task 22).

- [ ] **Step 1: Baca file sumber**

```bash
grep -n "SELECT \|FROM \|function " "/mnt/c/laragon/www/web_kasir/website_kasir/index_kasir.php" | head -40
```

- [ ] **Step 2: Port** — ini landing page role `kasir`/`user` (beda dari
`dashboard.php` Task 20 yang buat admin). Ganti semua referensi tabel
sesuai mapping §3.1, cek status shift aktif (`kas_awal_closing_kasir.status
= 'on proses'`) buat kasir yang login, tampilkan tombol buka/tutup
kasir sesuai status.

- [ ] **Step 3: Update menu (Task 15) & smoke test**

Login role kasir, landing page harus `index_kasir.php` bukan
`dashboard.php` — atur logic redirect di file login/router fitmotor
(bukan `menu_config.php`, itu buat sidebar; cek file yang handle
redirect-setelah-login fitmotor, pola sama Task 11 Step 1 pencarian
session).

- [ ] **Step 4: Commit**

```bash
git add app/_keuangan/kasir/index_kasir.php
git commit -m "feat(keuangan-kasir): port index_kasir landing dashboard kasir harian"
```

---

## Task 25: Setup lib laporan (phpspreadsheet + tcpdf) scoped modul kasir

**Files:**
- Modify: `composer.json` (root fitmotor)

**Interfaces:**
- Produces: `phpoffice/phpspreadsheet`, `tecnickcom/tcpdf`, `phpoffice/phpword` ter-install, dipakai Task 26/30.

- [ ] **Step 1: Tambah dependency**

```bash
cat composer.json
```
(baca dulu isi sekarang — cuma ada `dompdf/dompdf`, JANGAN dihapus)
Edit `composer.json`, tambahkan ke `require` (jangan ganti yang sudah ada):
```json
{
    "require": {
        "dompdf/dompdf": "^2.0",
        "phpoffice/phpspreadsheet": "^3.3",
        "tecnickcom/tcpdf": "^6.10",
        "smalot/pdfparser": "^2.12",
        "phpoffice/phpword": "^1.3"
    }
}
```

- [ ] **Step 2: Install**

```bash
/mnt/c/laragon/bin/php/php-8.3.16-nts-Win32-vs16-x64/php.exe /mnt/c/laragon/www/web-bengkel/aplikasi/aplikasi/composer.phar install
```
(pakai `composer.phar`/binary composer yang tersedia di environment —
cek dulu `which composer` atau `ls *.phar` kalau command di atas gak
ada; fallback: download composer.phar resmi kalau belum ada sama sekali
di environment)

- [ ] **Step 3: Verifikasi autoload gak bentrok**

```php
<?php
// _tmp_verify_composer.php — disposable
require_once __DIR__ . '/vendor/autoload.php';
echo class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet') ? "phpspreadsheet OK\n" : "phpspreadsheet GAGAL\n";
echo class_exists('TCPDF') ? "tcpdf OK\n" : "tcpdf GAGAL\n";
echo class_exists('Dompdf\Dompdf') ? "dompdf masih OK (gak kesenggol)\n" : "dompdf RUSAK — STOP, investigasi\n";
```

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_verify_composer.php
git add composer.json composer.lock
git commit -m "chore(keuangan-kasir): tambah lib phpspreadsheet/tcpdf/pdfparser/phpword scoped modul kasir"
```

---

## Task 26: Port OCR pipeline pelunasan hutang

**Files:**
- Create: `app/_keuangan/kasir/services/pelunasan_hutang/DocumentReader.php`
- Create: `app/_keuangan/kasir/services/pelunasan_hutang/ExtractorService.php`
- Create: `app/_keuangan/kasir/services/pelunasan_hutang/OCRService.php`
- Create: `app/_keuangan/kasir/services/pelunasan_hutang/ParserService.php`

**Interfaces:**
- Consumes: `vendor/autoload.php` (Task 25, `smalot/pdfparser`).
- Produces: fungsi baca/ekstrak dokumen bukti transfer, dipakai Task 27/28 (form upload pelunasan hutang & setoran).

- [ ] **Step 1: Copy 4 file service apa adanya dulu**

```bash
mkdir -p app/_keuangan/kasir/services/pelunasan_hutang
cp "/mnt/c/laragon/www/web_kasir/website_kasir/services/pelunasan_hutang/DocumentReader.php" app/_keuangan/kasir/services/pelunasan_hutang/
cp "/mnt/c/laragon/www/web_kasir/website_kasir/services/pelunasan_hutang/ExtractorService.php" app/_keuangan/kasir/services/pelunasan_hutang/
cp "/mnt/c/laragon/www/web_kasir/website_kasir/services/pelunasan_hutang/OCRService.php" app/_keuangan/kasir/services/pelunasan_hutang/
cp "/mnt/c/laragon/www/web_kasir/website_kasir/services/pelunasan_hutang/ParserService.php" app/_keuangan/kasir/services/pelunasan_hutang/
```

- [ ] **Step 2: Baca isi tiap file, cari hardcoded path/DB call**

```bash
grep -n "require\|include\|new PDO\|mysqli_connect\|__DIR__" app/_keuangan/kasir/services/pelunasan_hutang/*.php
```
Kalau ada hardcoded path relatif ke struktur lama (`../config.php`, dst)
atau koneksi DB langsung dibuat sendiri (bukan di-inject), sesuaikan ke
struktur baru (`require_once __DIR__ . '/../../koneksi_kasir.php'`,
path upload disesuaikan Task 32).

- [ ] **Step 3: Test unit manual — proses 1 file sample**

```php
<?php
// _tmp_test_ocr.php — disposable, taruh 1 file sample bukti transfer dulu
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/_keuangan/kasir/services/pelunasan_hutang/DocumentReader.php';
require_once __DIR__ . '/app/_keuangan/kasir/services/pelunasan_hutang/OCRService.php';
require_once __DIR__ . '/app/_keuangan/kasir/services/pelunasan_hutang/ParserService.php';
// panggil sesuai signature asli fungsi (baca dulu class-nya di Step 2 buat tau nama method persis)
```
Expected: gak fatal error, hasil ekstraksi (nominal/rekening tujuan)
sesuai isi dokumen sample.

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_test_ocr.php
git add app/_keuangan/kasir/services
git commit -m "feat(keuangan-kasir): port OCR pipeline pelunasan hutang"
```

---

## Task 27: Port modul Keuangan Pusat

**Files:**
- Create: `app/_keuangan/kasir/keuangan_pusat.php`
- Create: `app/_keuangan/kasir/laporan_keuangan_pusat.php`
- Create: `app/_keuangan/kasir/edit_keuangan_pusat.php`
- Create: `app/_keuangan/kasir/hapus_keuangan_pusat.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php`, `pemasukan_pusat_closing_kasir`, `pengeluaran_pusat_closing_kasir`, `master_akun_closing_kasir` (Task 4/7).

- [ ] **Step 1: Baca file sumber (100KB, port bertahap per fungsi — JANGAN sekaligus)**

```bash
grep -n "function \|SELECT \|INSERT INTO\|UPDATE " "/mnt/c/laragon/www/web_kasir/website_kasir/keuangan_pusat.php" | head -60
```
Pecah jadi fungsi-fungsi logis, port satu-satu, ganti tabel sesuai
mapping §3.1.

- [ ] **Step 2-4: Port `laporan_keuangan_pusat.php`, `edit_keuangan_pusat.php`, `hapus_keuangan_pusat.php`** (pola sama Step 1)

- [ ] **Step 5: Smoke test + verifikasi DB, commit**

```bash
git add app/_keuangan/kasir/keuangan_pusat.php app/_keuangan/kasir/laporan_keuangan_pusat.php app/_keuangan/kasir/edit_keuangan_pusat.php app/_keuangan/kasir/hapus_keuangan_pusat.php
git commit -m "feat(keuangan-kasir): port modul keuangan pusat"
```

---

## Task 28: Port `setoran_keuangan` (family terbesar, 434KB+268KB+2 varian)

**Files:**
- Create: `app/_keuangan/kasir/setoran_keuangan.php`
- Create: `app/_keuangan/kasir/setoran_keuangan_cs.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php`, `setoran_ke_bank_closing_kasir`(+detail), `pengambilan_setoran_closing_kasir`(+log/pembayaran), OCR service (Task 26).

**PENTING**: `setoran_keuangan.php` (434KB) itu file TERBESAR di seluruh
projek — JANGAN port sekaligus 1 file. Pecah per fungsi/tab UI, commit
bertahap per fungsi selesai, bukan 1 commit raksasa di akhir.
`setoran_keuangan1.php` (268KB) & `setoran_keuangan_cs_enhanced.php`
adalah varian — cek dulu mana yang aktif dilink dari `includes/sidebar.php`
sebelum port (jangan asumsi `setoran_keuangan.php` polos yang aktif).

- [ ] **Step 1: Cek varian mana yang aktif**

```bash
grep -n "setoran_keuangan" "/mnt/c/laragon/www/web_kasir/website_kasir/includes/sidebar.php"
```

- [ ] **Step 2: Baca struktur fungsi file aktif**

```bash
grep -n "function \|<!-- TAB\|case '" "/mnt/c/laragon/www/web_kasir/website_kasir/setoran_keuangan.php" | head -80
```
(sesuaikan nama file kalau hasil Step 1 nunjuk varian lain)

- [ ] **Step 3: Port bertahap per fungsi/tab**, commit tiap fungsi kelar
(bukan detail di sini — worker breakdown sendiri berdasar hasil Step 2,
prinsip: tiap sub-fungsi = 1 commit, target tabel sesuai mapping §3.1,
form upload OCR pakai service Task 26)

- [ ] **Step 4: Port `setoran_keuangan_cs.php`** (varian CS, cek juga apa
`_cs_enhanced.php` yang aktif via sidebar, pola sama Step 1)

- [ ] **Step 5: Smoke test penuh alur setoran + OCR upload, verifikasi DB**

- [ ] **Step 6: Commit final penanda selesai**

```bash
git commit --allow-empty -m "feat(keuangan-kasir): port setoran_keuangan selesai (semua sub-fungsi)"
```

---

## Task 29: Port CRUD master data admin

**Files:**
- Create: `app/_keuangan/kasir/master_akun.php`
- Create: `app/_keuangan/kasir/master_nama_transaksi.php`
- Create: `app/_keuangan/kasir/master_rekening_cabang.php`
- Create: `app/_keuangan/kasir/keping.php` (tergantung keputusan Task 8)

**Interfaces:**
- Consumes: `koneksi_kasir.php`, `master_akun_closing_kasir`, `master_nama_transaksi_closing_kasir`, `master_rekening_cabang_closing_kasir` (Task 2/4).

- [ ] **Step 1-4: Port tiap file** — pola sama task porting lain, CRUD
sederhana (list/tambah/edit/hapus), target tabel sesuai mapping §3.1.
`setup_master_cabang.php` **TIDAK diport** — cabang dikelola `tbcabang`
fitmotor, bukan tabel `cabang` web_kasir yang di-drop.

- [ ] **Step 5: Smoke test + commit**

```bash
git add app/_keuangan/kasir/master_akun.php app/_keuangan/kasir/master_nama_transaksi.php app/_keuangan/kasir/master_rekening_cabang.php app/_keuangan/kasir/keping.php
git commit -m "feat(keuangan-kasir): port CRUD master data admin"
```

---

## Task 30: Port laporan/export (17 file → 3 batch)

**Files:**
- Create: `app/_keuangan/kasir/export/excel.php` (konsolidasi `export_excel*.php`, `generate_excel.php`, `ganerate_excel_admin.php`, `export_csv.php`, `export_setoran_excel.php`, `export_keuangan_excel.php` — parametrized by report type, bukan 1 file per jenis laporan kalau strukturnya emang mirip; kalau ternyata beda logic signifikan, pecah jadi beberapa file, jangan paksa 1 file kalau bikin kode jelek)
- Create: `app/_keuangan/kasir/export/pdf.php` (konsolidasi `export_pdf_closing_kasir.php`, `export_pdf_setoran.php`, `generate_pdf.php`, `ganerate_pdf_admin.php`, `print_closing_kasir.php`, `print_serah_terima_pengambilan.php`)

**Interfaces:**
- Consumes: `phpoffice/phpspreadsheet`/`tecnickcom/tcpdf` (Task 25), semua tabel `*_closing_kasir` sesuai jenis laporan.

- [ ] **Step 1: Baca tiap 17 file sumber, kelompokkan by jenis laporan & format**

```bash
ls "/mnt/c/laragon/www/web_kasir/website_kasir" | grep -iE "export|generate|ganerate|print_"
```
Buat tabel mapping: nama laporan → tabel sumber → format (Excel/PDF/CSV)
sebelum mulai nulis kode konsolidasi.

- [ ] **Step 2: Tulis `export/excel.php`** — pakai `PhpOffice\PhpSpreadsheet`,
port logic tiap jenis laporan Excel jadi 1 fungsi per jenis dalam file
ini (atau file terpisah kalau lebih jelas), ganti semua tabel sesuai
mapping §3.1.

- [ ] **Step 3: Tulis `export/pdf.php`** — pakai `TCPDF`, pola sama Step 2.

- [ ] **Step 4: Smoke test tiap jenis laporan** — generate & buka hasil
file Excel/PDF, cek data cocok dengan query manual ke `*_closing_kasir`.

- [ ] **Step 5: Commit**

```bash
git add app/_keuangan/kasir/export
git commit -m "feat(keuangan-kasir): port laporan/export excel & pdf (konsolidasi 17 file)"
```

---

## Task 31: Port monitoring & riwayat transaksi

**Files:**
- Create: `app/_keuangan/kasir/monitoring_setoran.php`
- Create: `app/_keuangan/kasir/monitor_branch_data.php`
- Create: `app/_keuangan/kasir/view_transaksi.php`
- Create: `app/_keuangan/kasir/view_transaksi_admin.php`

**Interfaces:**
- Consumes: `koneksi_kasir.php`, semua tabel `*_closing_kasir` transaksional (Task 5-7), view Task 9.

- [ ] **Step 1-4: Port tiap file** — pola sama task porting lain, query
laporan/monitoring read-only, ganti tabel sesuai mapping §3.1, prefer
pakai VIEW hasil Task 9 kalau ada yang cocok (jangan duplikat query
kompleks yang udah ada di VIEW).

- [ ] **Step 5: Smoke test + commit**

```bash
git add app/_keuangan/kasir/monitoring_setoran.php app/_keuangan/kasir/monitor_branch_data.php app/_keuangan/kasir/view_transaksi.php app/_keuangan/kasir/view_transaksi_admin.php
git commit -m "feat(keuangan-kasir): port monitoring & riwayat transaksi"
```

---

## Task 32: Migrasi file fisik uploads

**Files:**
- Create: `app/uploads/kasir/pelunasan_hutang/` (folder target)
- Create: `_tmp_migrate_uploads.php` (disposable)

**Interfaces:**
- Consumes: path lama di kolom `pengambilan_setoran.mutasi_dokumen_path` (Task 7 hasil migrasi).
- Produces: file fisik pindah, kolom path di `pengambilan_setoran_closing_kasir` diupdate ke path baru.

- [ ] **Step 1: Copy fisik file**

```bash
mkdir -p app/uploads/kasir/pelunasan_hutang
cp -r "/mnt/c/laragon/www/web_kasir/website_kasir/uploads/pelunasan_hutang/"* app/uploads/kasir/pelunasan_hutang/
```

- [ ] **Step 2: Update path di DB**

```php
<?php
// _tmp_migrate_uploads.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($c, "SELECT id, mutasi_dokumen_path FROM pengambilan_setoran_closing_kasir WHERE mutasi_dokumen_path IS NOT NULL AND mutasi_dokumen_path != ''");
$n = 0;
while($row = mysqli_fetch_assoc($r)){
    $old = $row['mutasi_dokumen_path'];
    $newPath = str_replace('uploads/pelunasan_hutang', 'uploads/kasir/pelunasan_hutang', $old);
    $id = (int)$row['id'];
    $esc = mysqli_real_escape_string($c, $newPath);
    mysqli_query($c, "UPDATE pengambilan_setoran_closing_kasir SET mutasi_dokumen_path = '$esc' WHERE id = $id");
    $n++;
}
echo "$n path terupdate\n";
```

- [ ] **Step 3: Verifikasi file benar-benar ada di path baru**

```php
<?php
// _tmp_verify_uploads.php — disposable
$c = mysqli_connect('localhost','fitmotor_LOGIN','Sayalupa12','fitmotor_dbbengkel');
$r = mysqli_query($c, "SELECT id, mutasi_dokumen_path FROM pengambilan_setoran_closing_kasir WHERE mutasi_dokumen_path IS NOT NULL AND mutasi_dokumen_path != ''");
$missing = 0;
while($row = mysqli_fetch_assoc($r)){
    $full = __DIR__ . '/' . $row['mutasi_dokumen_path'];
    if(!file_exists($full)){ echo "MISSING: {$row['mutasi_dokumen_path']}\n"; $missing++; }
}
echo $missing === 0 ? "OK — semua file ketemu\n" : "$missing file HILANG, cek Step 1\n";
```

- [ ] **Step 4: Commit**

```bash
rm -f _tmp_migrate_uploads.php _tmp_verify_uploads.php
git add app/uploads/kasir
git commit -m "feat(keuangan-kasir): migrasi file fisik uploads pelunasan hutang"
```

---

## Task 33: Bersihkan file berbahaya di webroot web_kasir (URGENT, independen dari cutover)

**Files:**
- Modify: `C:\laragon\www\web_kasir\website_kasir` (di luar repo git ini)

**Interfaces:**
- Tidak ada — task keamanan murni, TIDAK perlu menunggu task lain, bisa dieksekusi kapan saja mulai sekarang.

- [ ] **Step 1: Hapus dump SQL mentah & error log dari webroot**

```bash
rm -f "/mnt/c/laragon/www/web_kasir/website_kasir/fitmotor_maintance-beta (1).sql"
rm -f "/mnt/c/laragon/www/web_kasir/website_kasir/error_log"
```
(pastikan sudah ada backup lain kalau perlu — tapi Task 1 sudah bikin
dump resmi ke `backups/`, dump liar di webroot ini murni resiko, bukan
satu-satunya backup)

- [ ] **Step 2: Hapus file dev/debug/test mati**

```bash
cd "/mnt/c/laragon/www/web_kasir/website_kasir"
rm -f debug_closing_dropdown.php debug_rekening.php cleanup_debug_files.php create_test_closing.php temp_js.txt temp_php.txt temp_php2.txt restore_pga.php repair_missing_data.php fix_orphaned_payments.php fix_sisa.php patch_ocr_upload.php run_sync.php
```

- [ ] **Step 3: Verifikasi web_kasir masih jalan normal setelah cleanup**

Buka via Claude-in-Chrome, login, pastikan halaman utama masih normal
(file yang dihapus semuanya bukan dependency runtime, cuma script
debug/dev berdiri sendiri — tapi verifikasi tetap wajib sebelum
lanjut).

- [ ] **Step 4: Commit** (di repo `web_kasir` kalau itu git repo terpisah — bukan repo `web-bengkel` ini)

```bash
git -C "/mnt/c/laragon/www/web_kasir" add -A
git -C "/mnt/c/laragon/www/web_kasir" commit -m "security: hapus dump SQL mentah, error_log, dan file dev/debug dari webroot"
```

---

## Task 34: Retire `masterkey.php` setelah Task 21

**Files:**
- Tidak ada file baru — task administratif/verifikasi.

**Interfaces:**
- Consumes: Task 21 harus selesai (bridge sudah pakai `tbuser.is_active`).

- [ ] **Step 1: Verifikasi tidak ada lagi yang butuh `masterkey.php`/`store_masterkey.php`**

Cek `login_dashboard/sync_users_api.php` versi baru (Task 21) sudah
pakai `tbuser.is_active`, bukan `masterkeys.status_aktif`. Kalau sudah,
`masterkey.php`/`store_masterkey.php` di web_kasir aman gak diport —
catat keputusan ini di spec §3.9 (sudah ditulis, task ini cuma
verifikasi eksekusi sesuai rencana).

- [ ] **Step 2: Commit penanda**

```bash
git commit --allow-empty -m "docs(keuangan-kasir): konfirmasi masterkey.php retired, tergantikan tbuser.is_active"
```

---

## Self-Review Notes (writing-plans skill)

- **Spec coverage:** §3.1 skema → Task 2-9. §3.2 struktur file → Task
  11-14, 20 (dashboard). §3.3 RBAC → Task 10. §3.4 migrasi data → Task
  4-7. §3.5 cutover → Task 1 (backup), 16 (smoke test), 17 (cutover).
  §3.6 tabel lama → Task 18. §3.7 testing → Task 16. §3.8 dependensi
  priori-tech → Task 21. §4 ambiguitas → Task 8 (keping), Task 11 Step 1
  (session var), Task 17 (dynamic_sidebars didrop setelah dicek gak
  dipakai UI aktif web_kasir — kalau ternyata dipakai, worker WAJIB stop
  & lapor sebelum Task 17 Step 2 jalan). Semua section spec punya task
  yang menjalankannya.
- **Gap ditemukan setelah plan awal (2026-09-03, pertanyaan Rafi):**
  dashboard (`admin_dashboard.php`) awalnya kelewat dari daftar file
  porting — ditambah sebagai Task 20. Dependensi eksternal
  `login_dashboard`→priori-tech (baca `users`/`masterkeys`) awalnya gak
  diketahui pas brainstorming — Task 17 direvisi (gak jadi drop
  `users`/`masterkeys` langsung), ditambah Task 21 buat alihkan bridge
  ke `tbuser` fitmotor dulu sebelum drop.
- **Gap audit lanjutan (2026-09-03, "cek jangan ada yang kelewat")**:
  sample awal cuma ~40 dari 155 file root web_kasir. Audit penuh nemu:
  landing kasir terpisah `index_kasir.php` (Task 24), modul Keuangan
  Pusat 100KB+ (Task 27), `setoran_keuangan.php` 434KB — file terbesar
  seluruh projek (Task 28), OCR pipeline nyata buat verifikasi bukti
  transfer (Task 26, butuh lib baru — Task 25), 17 file laporan/export
  (Task 30), CRUD master data admin (Task 29), monitoring/riwayat
  terpisah dari dashboard (Task 31), 2 tabel `data_penjualan`/
  `data_servis` yang kelewat total dari skema awal (Task 23), file fisik
  `uploads/` belum ada rencana migrasi (Task 32), dump SQL mentah 4.3MB
  + error_log 798KB nangkring di webroot — resiko keamanan aktif (Task
  33, urgent independen dari cutover), dan `tbuser` perlu kolom baru
  `kode_kasir` buat fitur quick-login yang gak ada padanannya (Task 22).
  Semua 13 task baru (22-34) ini hasil audit ulang, bukan asumsi ulang —
  tiap satu ditelusuri ke file/kolom sumber yang konkret sebelum
  ditambahkan ke plan.
- **Placeholder scan:** setiap step migrasi/DDL pakai kode PHP/SQL
  konkret; step yang isinya "cek dulu path/nama file exact" (Task 1
  mysqldump path, Task 11 nama file sumber & session var, Task 13 nama
  file sumber) BUKAN placeholder kosong — itu langkah investigasi wajib
  karena nilainya memang belum diketahui sebelum dijalankan di
  lingkungan nyata (path binary, nama variabel session existing), sudah
  dikasih command persis buat nemuinnya, bukan "tambahkan validasi yang
  sesuai" generik.
- **Type/nama consistency:** nama tabel target (`*_closing_kasir`) dipakai
  konsisten Task 2 → Task 18; nama fungsi helper `copyTable()`
  didefinisikan ulang tiap task migrasi data (Task 4-7) karena tiap
  task dieksekusi subagent terpisah yang gak bisa import file
  disposable task lain — ini SENGAJA, bukan duplikasi bug.
