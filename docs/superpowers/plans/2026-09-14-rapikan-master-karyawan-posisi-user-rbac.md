# Rapikan Master Karyawan, Jabatan, Posisi, User & RBAC — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Satukan 2 ruang identitas karyawan yang sekarang terpisah total (`tbuser_karyawan` data HR vs `tbuser` akun login), buang duplikat kode yang gak kepakai, dan bikin satu jalur RBAC yang konsisten (`tb_master_posisi`) tanpa mematikan kompatibilitas ke kode lama yang masih baca level numerik (`tblevel`/`user_akses`).

**Architecture:** Tambah FK nullable `tbuser.id_karyawan -> tbuser_karyawan.id` (additive, non-destruktif) sebagai jembatan 2 tabel yang sekarang lepas sama sekali. Satukan generator `kode_karyawan` (sekarang ada 2 salinan independen — di `master_karyawan_save.php` dan hasil patch sesi ini di `user_management.php`) jadi 1 fungsi shared di `config/`. Form "Tambah User" dikasih opsi pilih dari karyawan yang sudah ada (bukan ngetik ulang manual) buat cegah data nyimpang. Duplikat halaman posisi yang orphan diarsipkan. Migrasi data 102 user existing (linking + backfill kode_karyawan) dipisah jadi task tersendiri yang WAJIB konfirmasi eksplisit sebelum jalan (destruktif kalau salah mapping).

**Tech Stack:** PHP (mysqli, bukan PDO — ikut pola existing file-file ini, beda dari modul kasir/komplain yang PDO), MySQL, tanpa framework/ORM.

**Spec:** Tidak ada dokumen spec terpisah — plan ini disusun langsung dari investigasi codebase sesi 2026-09-14 (lihat "Ringkasan Temuan" di bawah, dan `CLAUDE.md` project section riwayat 2026-09-13/14 buat konteks Modul Komplain yang jadi pemicu investigasi ini).

## Ringkasan Temuan (pengganti spec)

Peta kondisi sekarang, dari investigasi langsung ke kode (bukan dugaan):

| Konsep | Tabel | Halaman CRUD | Status |
|---|---|---|---|
| Data HR karyawan | `tbuser_karyawan` | `master_karyawan.php`/`_add`/`_edit`/`_ajax`/`_save.php` | Live (linked di menu "Master Karyawan"), kode_karyawan auto-generate udah bener (`YYYYMM`+4 digit) |
| Akun login | `tbuser` | `user_management.php` + `user_management_ajax.php` | Live (dijangkau via redirect dari `user.php`, BUKAN link langsung di menu_config) |
| Posisi/role + permission | `tb_master_posisi` (kolom `permissions` JSON) | `master-posisi.php` (dash) + `master_posisi_ajax.php` | Live, linked langsung di menu "Master Posisi" |
| Posisi (duplikat orphan) | `tb_master_posisi` (baca doang) | `master_posisi.php` (underscore, TANPA dash) | Orphan, gak ke-link menu manapun, gak disentuh plan ini (append ke Task 4) |
| Jabatan (sub-level di bawah posisi) | `master_jabatan` (dirujuk di kode, TAPI gak ada di dump SQL statis — kemungkinan cuma ada di DB live, atau malah gak pernah dibikin) | **Gak ada** — cuma dropdown baca doang di `master_karyawan_add.php`, gak ada halaman buat isi/kelola isinya | Gap murni, ditandai [PERLU KEPUTUSAN RAFI] |
| Level akses (legacy) | `tblevel` (id, level_name — 8 baris statis, gak ada KACAB/KM) | Dipakai `user_add.php` (sudah diarsipkan sesi ini) | Legacy, dipertahankan buat kompatibilitas kolom numerik `tbuser.user_akses` |
| Akun login (percobaan lama, mati) | `tb_user_account` (kode_karyawan, username, password_hash, user_akses_level — ADA datanya, mirror `tbuser`) | **Gak ada** | Dead table dari rencana implementasi Nov 2025 (`docs/arsip/karyawan-login-rbac/`, status "Planning Phase") — `git grep` nol hasil di `app/`+`config/`, gak ada kode yang baca/tulis ke sini sama sekali |
| Data karyawan lama (di luar `fitmotor_dbbengkel`) | `fitmotor_maintance-beta.masterkeys` (kode_karyawan, nama_karyawan, entry_year, entry_month, kode_cabang, nama_cabang, status_aktif) | `masterkey.php`/`store_masterkey.php` (web_kasir lama, `web_kasir/website_kasir/`) | **MASIH ADA DATA KARYAWAN REAL YANG RELEVAN** (dikonfirmasi Rafi 2026-09-14) — BELUM PERNAH direkonsiliasi/diimpor ke `fitmotor_dbbengkel`. Database beda sama sekali dari `fitmotor_dbbengkel` |

Rantai RBAC yang beneran jalan sekarang: `config/auth_session.php::auth_get_user_context()` JOIN `tbuser.kode_posisi` ke `tb_master_posisi.permissions`, taruh ke `$_SESSION['_permissions']`, dibaca `config/permission_check.php::hasPermission()`. Satu pengecualian hardcode: `user_akses == 1` selalu lolos semua permission (bypass admin lama, independen dari kode_posisi).

**5 gap konkret yang jadi alasan plan ini:**
1. `tbuser` dan `tbuser_karyawan` gak ada relasi sama sekali (no FK) — orang yang sama bisa punya 2 `kode_karyawan` beda di 2 tempat, atau punya salah satu doang.
2. Generator `kode_karyawan` ada 2 salinan kode independen (`master_karyawan_save.php` dan `user_management.php` hasil patch sesi ini) — kalau salah satu diubah, yang lain gak ikut, gampang divergen lagi.
3. `master_jabatan` gak punya halaman kelola — kolom `kode_jabatan` di `tbuser_karyawan` praktis gak pernah kepake bener (dropdown-nya kemungkinan kosong terus).
4. **`fitmotor_maintance-beta.masterkeys` punya data karyawan real yang belum diimpor** (dikonfirmasi Rafi) — generator baru manapun yang cuma discope ke `fitmotor_dbbengkel` beresiko bikin `kode_karyawan` baru buat orang yang SEBENARNYA udah punya kode di sana, atau (lebih parah) kebentur nomor yang sama persis kalau suatu saat data itu diimpor belakangan.
5. `tb_user_account` — tabel + data hidup di skema tapi 100% gak kepake kode, nyampah & bikin orang salah kira ini yang aktif.

## Global Constraints

- Semua query di file-file ini pakai mysqli (bukan PDO) — ikut pola existing, JANGAN campur PDO di tengah file mysqli.
- Setiap `ALTER TABLE` WAJIB nullable/additive dulu (kolom baru boleh NULL, JANGAN NOT NULL langsung) — data 102 user existing belum punya nilainya.
- Kredensial DB tetap lewat `../config/koneksi.php` existing punya, JANGAN hardcode baru.
- Tidak ada file yang dihapus permanen — orphan dipindah ke `app/_archive/` (pola yang sudah dipakai sesi ini & sesi-sesi sebelumnya), git mv biar histori kedeteksi rename.
- Task migrasi data (Task 7) WAJIB konfirmasi eksplisit Rafi sebelum eksekusi — ini satu-satunya task yang nulis ke data user/karyawan yang sudah ada (bukan cuma skema/kode baru).
- Setiap task diverifikasi lewat `php -l` (lint) + query manual ke DB (bukan asumsi) sebelum ditandai selesai — gak ada environment tes otomatis di project ini.

---

### Task 0: [WAJIB KONFIRMASI EKSPLISIT RAFI, KERJAKAN PALING AWAL] Rekonsiliasi `kode_karyawan` dari `fitmotor_maintance-beta`

**Kenapa ini paling awal:** Task 2 (generator shared) baru aman dibangun SETELAH tau persis kode apa aja yang udah kepake di `fitmotor_maintance-beta.masterkeys` — kalau dibalik urutannya, generator baru bisa keburu numbrok kode yang nanti diimpor dari sana.

**Files:**
- Buat: `tools/sql/2026-09-14-cek-koneksi-maintance-beta.php` (skrip cek, BUKAN migrasi)

**Interfaces:** Tidak ada — task ini investigasi & keputusan, bukan kode produksi.

**Step 1-2 SUDAH DIKERJAKAN 2026-09-14 (bukan lagi rencana, ini hasil beneran):** Cross-database query BERHASIL — bukan dari `localhost` (WSL gak bisa), tapi dari gateway Windows-nya (`ip route show default`, IP `172.22.0.1` di sesi ini, bisa beda di mesin lain) pakai kredensial `fitmotor_LOGIN`/`Sayalupa12` yang sama. `fitmotor_maintance-beta` dan `fitmotor_dbbengkel` ada di **server MySQL yang sama** (Windows/Laragon), jadi cross-database JOIN langsung bisa, gak perlu export/import manual.

**Hasil nyata:**

| Tabel | Isi | Temuan |
|---|---|---|
| `fitmotor_maintance-beta.masterkeys` | 30 karyawan real (nama, cabang, tanggal masuk asli) | Sumber kebenaran historis |
| `fitmotor_maintance-beta.users` | 19 akun login, semua FK `kode_karyawan` ke `masterkeys` | — |
| `fitmotor_dbbengkel.tbuser` | 47 baris | **19 di antaranya SUDAH persis migrasi dari `users` lama** — `kode_karyawan` sama persis, `nama_lengkap` sama, `nama_user` = huruf kecil `kode_user` lama (mis. id 110: `RAFI ADLI PRADIANSYAH`, kode `2024090025`, username `buh` = lowercase `kode_user` `BUH`). Migrasi `tbuser` SUDAH BENER, gak perlu diutak-atik. |
| `fitmotor_dbbengkel.tbuser_karyawan` | 99 baris | Cuma **7 nama yang match** ke 30 karyawan `masterkeys` (dicocokkan by nama), dan **SEMUA 7 pakai kode 3-digit BEDA** dari kode asli `masterkeys` (mis. `MAMUN MUAMIL` = `2015010001` di `tbuser`/BENAR vs `002` di `tbuser_karyawan`/BEDA). **Bukti nyata gap #1 di Ringkasan Temuan, bukan dugaan lagi.** |

**Kesimpulan konkret:** `tbuser` (akun login) itu udah SEJALAN sama `fitmotor_maintance-beta` — anggap ini "yang benar". Yang perlu dibenerin cuma `tbuser_karyawan` (HR), yang isinya kebanyakan gak nyambung ke data karyawan real (dugaan: 99 baris itu campuran dummy/seed lama + 7 real dengan kode salah + sisanya entah). Task 2 (generator shared) AMAN dibangun sekarang — 19 kode dari `masterkeys` yang sudah dipakai SUDAH ada juga di `tbuser`, jadi generator yang discope ke `tbuser`+`tbuser_karyawan` otomatis udah nyakup kode-kode itu, gak perlu scope check tambahan ke `fitmotor_maintance-beta` lagi.

- [x] **Step 3-4 SELESAI DIEKSEKUSI 2026-09-14** — Rafi konfirmasi eksplisit: "sesuaikan pakai kode karyawan di masterkeys" (masterkeys = sumber kebenaran). 8 baris `tbuser_karyawan` yang kodenya beda dari `masterkeys` (dicocokkan by nama, exact match trimmed+uppercase) di-UPDATE satu-satu (bukan bulk), diverifikasi hasilnya:

| id | nama | kode lama | kode baru (dari masterkeys) |
|---|---|---|---|
| 41 | A. ABDUL ROZAK | `022` | `2019120001` |
| 51 | ATTORIQ SHULHAN | `032` | `2022060001` |
| 35 | ERMA SETIAWAN | `015` | `2018100001` |
| 55 | FAIZ MUBAROK | `036` | `2022020002` |
| 42 | FAJAR ROYYANI | `023` | `2020020001` |
| 80 | INDRA WIGUNA | `061` | `2024050003` |
| 27 | MAMUN MUAMIL | `002` | `2015010001` |
| 49 | SAHRUL SOBIRIN | `030` | `2021100001` |

Dicek dulu gak ada bentrok (`SELECT kode_karyawan FROM tbuser_karyawan WHERE kode_karyawan IN (...)` kosong sebelum UPDATE) — aman, unique constraint `uk_kode_karyawan` gak kesenggol.

**Belum ditindak, sengaja gak diauto-fix (butuh keputusan eksplisit terpisah):**
- **NOVIAN ARDIANSYAH** — di `tbuser` (login, `fitmotor_dbbengkel`) namanya persis sama masterkeys, tapi di `tbuser_karyawan` id 64 namanya **"NOVIAN ARDIANSYAH YUSUF"** (ada tambahan nama belakang) dengan kode `045`. Nama gak exact-match, jadi gak ikut ke-update otomatis — bisa orang yang sama (nama lengkap vs nama panggilan) atau orang beda. **Perlu Rafi konfirmasi manual** sebelum disamakan ke kode masterkeys `2010010001`.
- **11 orang di `masterkeys` yang belum punya baris `tbuser_karyawan` sama sekali** (4 di antaranya: ADIT PRASETYO, DIFFIYANI AULIA NAJWA, MUAFIFI ALAM, RAHMAT ALFIAN A. — belum dijawab, beda dari 7 yang barusan di-UPDATE) — belum diimpor. Masih nunggu keputusan per-orang (impor pakai kode asli / skip karena resign).

- [x] **Step 5 — Task 2 (generator shared) AMAN dibangun sekarang.** 19 kode dari `masterkeys` yang sudah aktif dipakai (login) SUDAH ada di `tbuser`; 8 yang tadinya cuma ada di `tbuser_karyawan` sekarang juga udah sinkron. Generator yang discope ke `tbuser`+`tbuser_karyawan` (rencana awal Task 2) otomatis udah nyakup semua kode `masterkeys` yang relevan — TIDAK perlu scope check tambahan ke `fitmotor_maintance-beta` lagi.

---

### Task 1: Tambah kolom penghubung `tbuser.id_karyawan`

**Files:**
- Buat: `tools/sql/2026-09-14-add-id-karyawan-tbuser.sql`
- Modify: tidak ada (DDL murni, dijalankan manual oleh Rafi — sesi ini gak punya akses tulis DB)

**Interfaces:**
- Produces: kolom `tbuser.id_karyawan` (`INT NULL`, FK opsional ke `tbuser_karyawan.id`) — dipakai Task 3 & Task 7.

- [ ] **Step 1: Tulis DDL additive**

```sql
-- tools/sql/2026-09-14-add-id-karyawan-tbuser.sql
-- Nullable dari awal — 102 user existing belum punya nilai ini.
ALTER TABLE tbuser
  ADD COLUMN id_karyawan INT NULL DEFAULT NULL COMMENT 'FK opsional ke tbuser_karyawan.id — akun login yang gak punya record HR (mis. akun sistem lama) boleh NULL',
  ADD KEY idx_tbuser_id_karyawan (id_karyawan);

ALTER TABLE tbuser
  ADD CONSTRAINT fk_tbuser_id_karyawan
  FOREIGN KEY (id_karyawan) REFERENCES tbuser_karyawan(id)
  ON DELETE SET NULL ON UPDATE CASCADE;
```

- [ ] **Step 2: Serahkan ke Rafi buat dijalankan**

Sesi ini gak punya akses tulis MySQL live (WSL gak bisa konek ke instance Windows/Laragon). Kirim file ini ke Rafi, minta dijalankan via phpMyAdmin/MySQL client yang beliau pegang. **Jangan lanjut Task 3 sebelum kolom ini kekonfirmasi ada.**

- [ ] **Step 3: Verifikasi kolom ada**

Minta Rafi jalanin dan tempel hasilnya:
```sql
DESCRIBE tbuser;
```
Harus muncul baris `id_karyawan | int(11) | YES | MUL | NULL`.

- [ ] **Step 4: Commit**

```bash
git add tools/sql/2026-09-14-add-id-karyawan-tbuser.sql
git commit -m "chore(db): tambah kolom penghubung tbuser.id_karyawan -> tbuser_karyawan.id"
```

---

### Task 2: Satukan generator `kode_karyawan` jadi 1 fungsi shared

**Files:**
- Buat: `config/karyawan_helper.php`
- Modify: `app/master_karyawan_save.php:74-91`
- Modify: `app/user_management.php` (closure `$generateKodeKaryawan` hasil patch sesi 2026-09-14 — cari `// Generate kode_karyawan (prefix YYYYMM`)

**Interfaces:**
- Produces: `generateKodeKaryawan(mysqli $koneksi): ?string` — scan kedua tabel (`tbuser_karyawan` DAN `tbuser`) sekaligus biar 1 ruang nomor beneran (sebelumnya 2 fungsi masing-masing cuma scan tabelnya sendiri, jadi 2 orang bisa kebagian kode sama persis kalau dibuat di hari & bulan yang sama).
- Consumes (Task 3 nanti): dipanggil dari `master_karyawan_save.php` dan `user_management.php`.

- [ ] **Step 1: Tulis fungsi shared**

```php
<?php
// config/karyawan_helper.php
// Satu ruang nomor kode_karyawan buat 2 tabel (tbuser_karyawan + tbuser)
// - sebelum ini masing-masing generate sendiri-sendiri, discope cuma ke
// tabelnya sendiri, jadi 2 orang beda tabel bisa kebagian kode identik.

if (!function_exists('generateKodeKaryawan')) {
    function generateKodeKaryawan(mysqli $koneksi): ?string {
        $prefix = date('Ym');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $seqKaryawan = 0;
            $r1 = mysqli_query($koneksi,
                "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
                 FROM tbuser_karyawan WHERE kode_karyawan LIKE '{$prefix}%'");
            if ($r1 && ($row = mysqli_fetch_assoc($r1)) && !empty($row['seq'])) {
                $seqKaryawan = (int) $row['seq'];
            }

            $seqUser = 0;
            $r2 = mysqli_query($koneksi,
                "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
                 FROM tbuser WHERE kode_karyawan LIKE '{$prefix}%'");
            if ($r2 && ($row = mysqli_fetch_assoc($r2)) && !empty($row['seq'])) {
                $seqUser = (int) $row['seq'];
            }

            $seq = max($seqKaryawan, $seqUser) + 1;
            $candidate = sprintf('%s%04d', $prefix, $seq);

            $c1 = mysqli_query($koneksi, "SELECT id FROM tbuser_karyawan WHERE kode_karyawan = '$candidate'");
            $c2 = mysqli_query($koneksi, "SELECT id FROM tbuser WHERE kode_karyawan = '$candidate'");
            $taken = ($c1 && mysqli_num_rows($c1) > 0) || ($c2 && mysqli_num_rows($c2) > 0);
            if (!$taken) {
                return $candidate;
            }
        }
        return null;
    }
}
```

- [ ] **Step 2: Lint**

Run: `php -l config/karyawan_helper.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Ganti pemanggilan di `master_karyawan_save.php`**

Di `app/master_karyawan_save.php`, tambah `include "../config/karyawan_helper.php";` setelah baris `include "../config/permission_check.php";`, lalu ganti blok generate manual (baris 74-91, dari komentar `// Generate kode_karyawan` sampai `$kode_karyawan = sprintf(...)`) jadi:

```php
        // Generate kode_karyawan lewat fungsi shared (satu ruang nomor
        // dengan tbuser) — lihat config/karyawan_helper.php.
        $kode_karyawan = generateKodeKaryawan($koneksi);
        if ($kode_karyawan === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal generate kode karyawan, coba lagi']);
            return;
        }
```

- [ ] **Step 4: Ganti pemanggilan di `user_management.php`**

Di `app/user_management.php`, tambah `include "../config/karyawan_helper.php";` di dekat include `koneksi.php`/`permission_check.php` yang sudah ada, lalu hapus definisi closure `$generateKodeKaryawan = function () use ($koneksi) { ... };` (hasil patch sebelumnya) dan ganti setiap pemanggilan `$generateKodeKaryawan()` jadi `generateKodeKaryawan($koneksi)`.

- [ ] **Step 5: Lint kedua file**

Run: `php -l app/master_karyawan_save.php && php -l app/user_management.php`
Expected: `No syntax errors detected` buat keduanya.

- [ ] **Step 6: Commit**

```bash
git add config/karyawan_helper.php app/master_karyawan_save.php app/user_management.php
git commit -m "refactor(karyawan): satukan generator kode_karyawan jadi 1 fungsi shared"
```

---

### Task 3: Form "Tambah User" bisa pilih dari karyawan existing

**Files:**
- Modify: `app/user_management.php` (form Add User, sekitar modal `addUserModal`)
- Modify: `app/user_management_ajax.php` (tambah 1 action baru: `search_karyawan`)

**Interfaces:**
- Consumes: `generateKodeKaryawan()` dari Task 2, kolom `tbuser.id_karyawan` dari Task 1.
- Produces: field tersembunyi `id_karyawan` di form Add User, terisi otomatis kalau admin milih dari hasil pencarian.

- [ ] **Step 1: Tambah endpoint pencarian karyawan**

Di `app/user_management_ajax.php`, tambah blok baru (sebelum `?>` penutup):

```php
if (isset($_POST['action']) && $_POST['action'] == 'search_karyawan') {
    $keyword = mysqli_real_escape_string($koneksi, $_POST['keyword'] ?? '');
    $result = mysqli_query($koneksi,
        "SELECT id, kode_karyawan, nama_lengkap, kode_posisi, kode_cabang
         FROM tbuser_karyawan
         WHERE (nama_lengkap LIKE '%$keyword%' OR kode_karyawan LIKE '%$keyword%')
           AND tanggal_keluar IS NULL
           AND id NOT IN (SELECT id_karyawan FROM tbuser WHERE id_karyawan IS NOT NULL)
         ORDER BY nama_lengkap ASC LIMIT 20");
    $rows = [];
    while ($r = mysqli_fetch_assoc($result)) { $rows[] = $r; }
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}
```

Catatan: `id NOT IN (SELECT id_karyawan FROM tbuser WHERE id_karyawan IS NOT NULL)` sengaja nyaring karyawan yang UDAH punya akun login — biar gak ada yang bikin akun dobel buat orang yang sama.

- [ ] **Step 2: Lint**

Run: `php -l app/user_management_ajax.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Tambah UI pencarian di modal Add User**

Di `app/user_management.php`, tepat sebelum field `<label>Username ...` di dalam `addUserModal`, sisipkan:

```html
<div class="form-group">
    <label>Cari dari Master Karyawan <small class="text-muted">(opsional — kosongkan buat isi manual)</small></label>
    <input type="text" class="form-control" id="karyawan_search" placeholder="Ketik nama atau kode karyawan...">
    <div id="karyawan_search_results" style="max-height:150px; overflow-y:auto; border:1px solid #ddd; display:none;"></div>
    <input type="hidden" name="id_karyawan" id="id_karyawan_selected">
</div>
```

Dan tambah script di bagian `<script>` yang sudah ada di file ini (dekat `function updateRoleInfo`):

```javascript
$('#karyawan_search').on('keyup', function() {
    var keyword = $(this).val();
    if (keyword.length < 2) { $('#karyawan_search_results').hide(); return; }
    $.ajax({
        url: 'user_management_ajax.php',
        type: 'POST',
        data: {action: 'search_karyawan', keyword: keyword},
        dataType: 'json',
        success: function(rows) {
            var html = '';
            rows.forEach(function(r) {
                html += '<div class="karyawan-result-item" style="padding:5px; cursor:pointer;" ' +
                        'data-id="' + r.id + '" data-nama="' + r.nama_lengkap + '" ' +
                        'data-posisi="' + r.kode_posisi + '" data-cabang="' + r.kode_cabang + '">' +
                        r.nama_lengkap + ' [' + r.kode_karyawan + ']</div>';
            });
            $('#karyawan_search_results').html(html).show();
        }
    });
});

$(document).on('click', '.karyawan-result-item', function() {
    $('#id_karyawan_selected').val($(this).data('id'));
    $('#karyawan_search').val($(this).data('nama'));
    $('#karyawan_search_results').hide();
    $('#user_akses_add').val($(this).data('posisi')).trigger('change');
    $('select[name="kode_cabang"]').val($(this).data('cabang'));
});
```

- [ ] **Step 4: Wire `id_karyawan` ke INSERT**

Di `app/user_management.php`, di blok `btn_add_user` (Task 2 sudah nambah `generateKodeKaryawan()` di situ), tambah baris ambil POST sejajar deklarasi `$kode_cabang` yang sudah ada:

```php
$id_karyawan = !empty($_POST['id_karyawan']) ? (int) $_POST['id_karyawan'] : null;
```

lalu ubah query INSERT (hasil patch sebelumnya) jadi ikutan nyimpen kolom ini:

```php
$id_karyawan_val = $id_karyawan !== null ? $id_karyawan : 'NULL';
$query = "INSERT INTO tbuser (kode_karyawan, nama_user, password, user_akses, kode_posisi, kode_cabang, role_name, department, foto_user, status_row, is_active, id_karyawan, created_at)
         VALUES ('$kode_karyawan', '$nama_user', '$password', '$user_akses', '$kode_posisi', '$kode_cabang', '$role_name', '$department', 'file_upload/avatar.png', '0', '$is_active', $id_karyawan_val, NOW())";
```

- [ ] **Step 5: Lint**

Run: `php -l app/user_management.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add app/user_management.php app/user_management_ajax.php
git commit -m "feat(user): pilih dari master karyawan existing saat bikin akun login"
```

---

### Task 4: Arsipkan duplikat halaman Posisi yang orphan

**Files:**
- Move: `app/master_posisi.php` -> `app/_archive/master_posisi.php`
- Move: `app/master_posisi_ajax.php` -> `app/_archive/master_posisi_ajax.php`

**Interfaces:** Tidak ada — file ini gak dipanggil dari mana pun (sudah diverifikasi `git grep` sesi 2026-09-14, cuma `master-posisi.php` dengan dash yang ke-link di `menu_config.php`).

- [ ] **Step 1: Verifikasi ulang tidak ada pemanggil (jaga-jaga kalau ada perubahan sejak investigasi)**

Run: `git grep -rn "master_posisi\.php\|master_posisi_ajax\.php" -- app | grep -v "^app/master_posisi"`
Expected: kosong (no match) — kalau ada match baru, STOP, investigasi dulu sebelum lanjut Step 2.

- [ ] **Step 2: Arsipkan**

```bash
git mv app/master_posisi.php app/_archive/master_posisi.php
git mv app/master_posisi_ajax.php app/_archive/master_posisi_ajax.php
```

- [ ] **Step 3: Commit**

```bash
git commit -m "chore: arsipkan duplikat orphan master_posisi.php (pola sama kayak user_management.php vs user.php lama)"
```

---

### Task 5: [PERLU KEPUTUSAN RAFI] Nasib `master_jabatan`

**Files:** Tidak ada perubahan kode sampai keputusan diambil.

Dropdown "Jabatan" di `app/master_karyawan_add.php:53-58` baca dari tabel `master_jabatan`, tapi:
- Tabel ini gak ada di dump SQL statis (`tools/sql/fitmotor_dbbengkel.sql`) — kemungkinan cuma dibuat manual di DB live, atau malah beneran gak pernah dibikin (dropdown selalu kosong).
- Gak ada satu pun halaman admin buat CRUD isinya (beda dari Posisi yang punya `master-posisi.php`).

- [x] **Step 1 SELESAI 2026-09-14** — dicek live: `SELECT COUNT(*) FROM master_jabatan;` = **0 baris**. Tabelnya ADA (query gak error), tapi kosong total.

- [x] **Step 2 — Rafi pilih Opsi B: tabel kosong, jabatan gak pernah beneran dipakai.**

- [ ] **Step 3: Buang dropdown "Jabatan" dari `app/master_karyawan_add.php:53-58` (baca `master_jabatan` yang selalu kosong — dead UI). Kolom `kode_jabatan` di `tbuser_karyawan` dibiarkan nullable apa adanya, gak diutak-atik. Belum dieksekusi — nunggu giliran, gak destruktif jadi bisa nyusul kapan aja.**

---

### Task 6: Migrasi ~19 file yang beneran gating akses via `user_akses` numerik (hasil audit lengkap 2026-09-14)

**Audit sudah dijalankan** (bukan lagi "audit dulu, task nyusul" seperti draft awal) — `git grep` nemu **382 file** yang nyebut `lvl_akses`/`user_akses`, tapi mayoritas cuma nge-assign ke variabel buat ditampilin di header (`$lvl_akses = $tm_cari['user_akses'];` doang, gak pernah dipakai buat keputusan) — dead-read, BUKAN celah akses, prioritas rendah/gak usah disentuh. Yang beneran GATING sesuatu cuma **19 file**, dibagi 3 kelompok:

**Kelompok A — admin-only check `== '1'`, konsisten & aman (12 file), migrasi opsional/rendah prioritas:**
`app/_ajax/ajax-refresh-antrian-dashboard.php`, `app/admin_deteksi_pelanggan_dobel.php`, `app/check_antrian.php`, `app/customer_merge_approve.php`, `app/dashboard-antrian-servis.php`, `app/index.php:333`, `app/issue_add.php`, `app/kendaraan_pindah_tangan.php`, `app/kendaraan_pindah_tangan_approve.php` — semua pola sama: `$is_admin = ($lvl_akses == '1');`.

**Kelompok B — BUG NYATA, perbandingan string vs kolom integer, kemungkinan gak pernah `true` (6 file), prioritas TINGGI:**
`app/master_kategori_item.php`, `app/master_kategori_item_add.php`, `app/master_kategori_item_del.php`, `app/master_kategori_item_edit.php`, `app/master_tipe_detail_edit.php`, `app/master_tipe_header_add.php`, `app/master_tipe_header_del.php`, `app/master_tipe_header_edit.php` — semua pola `$is_admin_pengadaan = ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');`. Kolom `tbuser.user_akses` itu **INT** (lihat DDL `user_akses int(11)`), gak pernah bernilai string `'admin'`/`'pengadaan'` — perbandingan ini kemungkinan besar **SELALU FALSE**. Bukti tambahan: `master_kategori_item.php:37` sudah di-workaround orang lain jadi `$is_admin_pengadaan = true; // ($lvl_akses == 'admin' ...)` — dikomentarin dan di-hardcode `true`, artinya proteksinya udah DIMATIKAN TOTAL di file itu, bukan cuma broken.

**Kelompok C — numerik custom, 2 file:**
`app/mekanik_management.php:24` & `app/mekanik_management_ajax.php:11`: `if($lvl_akses != 1 && $lvl_akses != 7 && $lvl_akses != 10)` (Admin/Manager/Kepala Mekanik). `app/input_kepala_mekanik_harian.php:26` udah CAMPURAN: `if (!isAdmin() && (int)$lvl_akses !== 10)` — sudah setengah migrasi ke `isAdmin()` modern tapi separonya masih numerik lama.

**Files:**
- Modify: 8 file Kelompok B (prioritas tinggi — bug akses nyata)
- Modify: 2 file Kelompok C + `app/input_kepala_mekanik_harian.php`
- Modify (opsional): 9 file Kelompok A

**Interfaces:** Consumes `hasPermission($module, $action)` dari `config/permission_check.php` (sudah ada, gak perlu dibuat baru).

- [ ] **Step 1: Fix Kelompok B dulu (paling urgent) — cek permission code yang sesuai di `tb_master_posisi.permissions` buat modul pengadaan/kategori-item (kemungkinan `barang_kategori_read`/`pembelian_menu_read`, cek persis nama kodenya di DB live), lalu ganti tiap file:**

```php
// SEBELUM (broken, gak pernah true / sudah di-hardcode true)
$is_admin_pengadaan = ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');

// SESUDAH
$is_admin_pengadaan = hasPermission('barang_kategori', 'edit') || isAdmin();
```

Satu file satu commit. Lint tiap file abis diubah.

- [ ] **Step 2: Verifikasi manual browser — login akun non-admin yang SEHARUSNYA bisa akses (posisi pengadaan/PGD), pastikan gak keblokir; login akun yang SEHARUSNYA gak boleh, pastikan keblokir.** Ini nyentuh proteksi akses beneran, WAJIB dites hidup, bukan cuma lint.

- [ ] **Step 3: Kelompok C — selaraskan `mekanik_management.php`/`_ajax.php`/`input_kepala_mekanik_harian.php` semua ke `hasPermission('mekanik', 'manage') || isAdmin()`, konsisten satu pola (sekarang beda-beda: 2 numerik murni, 1 campuran).**

- [ ] **Step 4: Kelompok A — opsional, boleh nyusul kapan aja, resiko rendah. Ganti `== '1'` jadi `isAdmin()` (fungsi ini sudah otomatis nyakup `user_akses==1` per definisi di `permission_check.php`) satu-satu, bukan wajib buat rilis awal.**

---

### Task 7: [WAJIB KONFIRMASI EKSPLISIT RAFI] Migrasi data 102 user existing

**Files:**
- Buat: `tools/sql/2026-09-14-backfill-tbuser-karyawan-link.sql` (draft query, BUKAN buat dieksekusi otomatis)

**Interfaces:** Konsumsi kolom `tbuser.id_karyawan` dari Task 1.

Ini task PALING beresiko — nyentuh data user yang udah live dipakai. **Jangan dieksekusi sebelum Rafi baca & setuju approach-nya.**

- [x] **Step 1 SELESAI 2026-09-14** — laporan matching dijalankan (READ-ONLY, kolom `tbuser.id_karyawan` dari Task 1 BELUM ada, jadi laporan pakai LEFT JOIN by nama langsung ke semua 47 baris `tbuser`, bukan filter `WHERE id_karyawan IS NULL`):

```sql
SELECT u.id AS user_id, u.nama_user, u.nama_lengkap AS nama_di_tbuser, u.kode_karyawan AS kode_di_tbuser,
       k.id AS karyawan_id, k.kode_karyawan AS kode_di_karyawan, k.nama_lengkap AS nama_di_karyawan
FROM tbuser u
LEFT JOIN tbuser_karyawan k ON UPPER(TRIM(k.nama_lengkap)) = UPPER(TRIM(u.nama_lengkap))
ORDER BY (k.id IS NOT NULL) DESC, u.nama_lengkap;
```

**Hasil (47 baris `tbuser`, dikelompokkan):**
1. **1 exact match beres duluan**: MAMUN MUAMIL (id 105, `tbuser.kode_karyawan=2015010001`) — sama persis `tbuser_karyawan` id 27 (baru dibenerin di Task 0). Aman langsung di-link begitu kolom `id_karyawan` (Task 1) ada.
2. **16 orang nyata dari 19 login yang berasal dari `masterkeys`** (lihat Task 0) — **NOL match** di `tbuser_karyawan`, belum ada record HR-nya sama sekali (mis. RAFI ADLI PRADIANSYAH id 110, DIAN RAMADHANI id 114, dst).
3. **1 ambigu**: NOVIAN ARDIANSYAH (tbuser id 103) vs "NOVIAN ARDIANSYAH YUSUF" (tbuser_karyawan id 64) — nama gak exact-match, sama seperti temuan Task 0, belum diputuskan.
4. **~29 akun `KRY-0000X`/`cs01`/`crm01`/`pgd01`/`dev_test_kasir`/dll** — jelas akun demo/seed test (nama role generik "CS & Kasir", "Kepala Mekanik", bukan nama orang), saling cross-match ke sesama dummy data doang. **Di luar cakupan rekonsiliasi karyawan real** — kalau mau dibersihin itu task terpisah (bukan bagian dari plan ini).

- [x] **Step 2 SELESAI — laporan dikirim ke Rafi.**

- [ ] **Step 3: Baru setelah Rafi approve daftar final, tulis `UPDATE tbuser SET id_karyawan = ? WHERE id = ?` satu-satu (bukan bulk UPDATE by JOIN) — biar ada jejak persis siapa yang di-link ke siapa, gampang di-audit/revert kalau ada yang salah.**

- [ ] **Step 4: User yang gak ke-match sama sekali (kemungkinan besar mayoritas dari 102) — TIDAK dipaksa dibikinin record `tbuser_karyawan` baru secara otomatis. Biarkan `id_karyawan` tetap NULL sampai ada proses onboarding data karyawan manual dari Rafi/HR. Auto-generate record HR palsu demi ngisi FK adalah pelanggaran aturan "jangan auto-generate identitas" yang sudah disepakati.**

---

### Task 8: [PERLU KEPUTUSAN RAFI] Nasib `tb_user_account` (dead table)

**Files:** Tidak ada perubahan sampai keputusan diambil.

- [x] **Step 1 SELESAI 2026-09-14** — re-cek `git grep -rln "tb_user_account" -- app config` = kosong, konfirmasi ulang gak ada pemanggil.

- [x] **Step 2 — Rafi pilih Opsi A: drop tabelnya.**

- [x] **Step 3 SELESAI DIEKSEKUSI 2026-09-14:**
  1. Backup: `mysqldump fitmotor_dbbengkel tb_user_account > backups/backup_tb_user_account_2026-09-14.sql` (65 baris, tersimpan di `backups/`, gitignored — kalau perlu restore ada di situ).
  2. `DROP TABLE tb_user_account;` — sukses, `SHOW TABLES LIKE 'tb_user_account'` sekarang kosong.

---

## Self-Review

**Cakupan temuan:** 5 gap di Ringkasan Temuan masing-masing punya task (gap 1 -> Task 1+7, gap 2 -> Task 2, gap 3 -> Task 5, gap 4 -> Task 0, gap 5 -> Task 8). Duplikat orphan (`master_posisi.php`) -> Task 4. Legacy numeric RBAC -> Task 6.

**Placeholder check:** Task 0, 5, 7, 8 sengaja gak punya kode konkret di Step akhir karena nunggu keputusan/approval Rafi — itu bukan placeholder "TODO males isi", tapi memang gerbang keputusan yang gak boleh dilewati tanpa manusia (nyentuh data karyawan real/history, atau DROP TABLE ireversibel). Semua task lain punya kode lengkap, bukan deskripsi doang.

**Urutan eksekusi:** Task 0 WAJIB duluan (sebelum Task 2), karena Task 2 (generator shared) butuh tau kode yang udah kepake di `fitmotor_maintance-beta` biar gak numbrok. Task 1, 4, 6, 8 independen, bisa jalan kapan aja. Task 3 butuh Task 1+2 kelar dulu. Task 7 butuh Task 1 kelar dulu.

**Konsistensi nama:** `generateKodeKaryawan($koneksi)` dipakai konsisten Task 2 & 3 (bukan `generate_kode_karyawan` atau nama lain). `id_karyawan` dipakai konsisten Task 1/3/7 (bukan `karyawan_id` atau `ref_karyawan`).
