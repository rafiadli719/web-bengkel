<?php
session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
include "../../config/koneksi.php";
include "../../config/permission_check.php";
if (!isAdmin()) { die("Hanya admin yang bisa menjalankan migration."); }

$results = [];

function run($koneksi, $label, $sql) {
    global $results;
    $ok = mysqli_query($koneksi, $sql);
    $results[] = [
        'label' => $label,
        'ok'    => (bool)$ok,
        'error' => $ok ? '' : mysqli_error($koneksi),
    ];
}

// ---- FASE 1 ----
run($koneksi, 'CREATE master_departemen',
    "CREATE TABLE IF NOT EXISTS master_departemen (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode VARCHAR(20) NOT NULL UNIQUE,
        nama VARCHAR(100) NOT NULL,
        is_active ENUM('active','inactive') DEFAULT 'active'
    )");

$depts = [
    ['WORKSHOP','Workshop / Bengkel'],['FRONTOFFICE','Front Office'],
    ['PURCHASING','Pembelian & Pengadaan'],['MANAGEMENT','Manajemen'],
    ['FINANCE','Keuangan'],['MARKETING','Marketing & CRM'],['HRD','Human Resource'],
];
foreach ($depts as $d) {
    run($koneksi, "INSERT master_departemen [{$d[0]}]",
        "INSERT IGNORE INTO master_departemen (kode,nama) VALUES ('{$d[0]}','{$d[1]}')");
}

run($koneksi, 'CREATE master_jabatan',
    "CREATE TABLE IF NOT EXISTS master_jabatan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kode_jabatan VARCHAR(20) NOT NULL UNIQUE,
        nama_jabatan VARCHAR(100) NOT NULL,
        kode_posisi  VARCHAR(20) NOT NULL,
        urutan INT DEFAULT 0,
        is_active ENUM('active','inactive') DEFAULT 'active'
    )");

$jabatans = [
    ['ADM-1','Administrator','ADM',1],['CS-1','Customer Service','CS',1],
    ['KSR-1','Kasir','KSR',1],['PGD-1','Staff Pengadaan','PGD',1],
    ['CRM-1','CRM Staff','CRM',1],['MNG-1','Manager','MNG',1],
    ['KEU-1','Staff Keuangan','KEU',1],['HRD-1','HRD Staff','HRD',1],
];
foreach ($jabatans as $j) {
    run($koneksi, "INSERT master_jabatan [{$j[0]}]",
        "INSERT IGNORE INTO master_jabatan (kode_jabatan,nama_jabatan,kode_posisi,urutan)
         VALUES ('{$j[0]}','{$j[1]}','{$j[2]}',{$j[3]})");
}

// ALTER tb_master_posisi — cek kolom dulu karena ADD COLUMN IF NOT EXISTS butuh MySQL 8+
$existing = [];
$r = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_master_posisi");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $existing[] = $row['Field']; } }

if (!in_array('kode_departemen', $existing)) {
    run($koneksi, 'ALTER tb_master_posisi ADD kode_departemen',
        "ALTER TABLE tb_master_posisi ADD COLUMN kode_departemen VARCHAR(20) DEFAULT NULL AFTER departemen");
}
if (!in_array('bisa_login', $existing)) {
    run($koneksi, 'ALTER tb_master_posisi ADD bisa_login',
        "ALTER TABLE tb_master_posisi ADD COLUMN bisa_login ENUM('ya','tidak') DEFAULT 'ya' AFTER kode_departemen");
}
if (!in_array('dapat_insentif', $existing)) {
    run($koneksi, 'ALTER tb_master_posisi ADD dapat_insentif',
        "ALTER TABLE tb_master_posisi ADD COLUMN dapat_insentif ENUM('ya','tidak') DEFAULT 'tidak' AFTER bisa_login");
}

$posisiMap = [
    'ADM' => ['FRONTOFFICE','ya',  'tidak'],
    'CS'  => ['FRONTOFFICE','ya',  'tidak'],
    'KSR' => ['FRONTOFFICE','ya',  'tidak'],
    'KM'  => ['WORKSHOP',   'ya',  'ya'   ],
    'MK'  => ['WORKSHOP',   'tidak','ya'  ],
    'PGD' => ['PURCHASING', 'ya',  'tidak'],
    'CRM' => ['MARKETING',  'ya',  'tidak'],
    'MNG' => ['MANAGEMENT', 'ya',  'tidak'],
    'KEU' => ['FINANCE',    'ya',  'tidak'],
    'HRD' => ['HRD',        'ya',  'tidak'],
];
foreach ($posisiMap as $kp => $v) {
    run($koneksi, "UPDATE tb_master_posisi [{$kp}]",
        "UPDATE tb_master_posisi SET kode_departemen='{$v[0]}',bisa_login='{$v[1]}',dapat_insentif='{$v[2]}'
         WHERE kode_posisi='$kp'");
}

run($koneksi, 'DROP tb_user_mekanik_mapping',
    "DROP TABLE IF EXISTS tb_user_mekanik_mapping");

// ---- FASE 2 ----
run($koneksi, 'CREATE karyawan',
    "CREATE TABLE IF NOT EXISTS karyawan (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        kode_karyawan  VARCHAR(20) NOT NULL UNIQUE,
        nik            VARCHAR(20),
        nama_lengkap   VARCHAR(100) NOT NULL,
        nama_panggilan VARCHAR(50),
        kode_posisi    VARCHAR(20) NOT NULL,
        kode_jabatan   VARCHAR(20),
        kode_cabang    VARCHAR(10),
        email          VARCHAR(100),
        telp           VARCHAR(20),
        alamat         TEXT,
        spesialisasi   TEXT,
        sertifikat     TEXT,
        tanggal_masuk  DATE,
        tanggal_keluar DATE,
        is_active      ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

run($koneksi, 'CREATE karyawan_login',
    "CREATE TABLE IF NOT EXISTS karyawan_login (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        kode_karyawan VARCHAR(20) NOT NULL UNIQUE,
        username      VARCHAR(100) NOT NULL UNIQUE,
        password      VARCHAR(255) NOT NULL,
        is_active     ENUM('active','inactive') DEFAULT 'active',
        last_login    TIMESTAMP NULL,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (kode_karyawan) REFERENCES karyawan(kode_karyawan) ON DELETE CASCADE
    )");

$ok_count  = count(array_filter($results, fn($r) => $r['ok']));
$err_count = count($results) - $ok_count;
?>
<!DOCTYPE html><html><head><title>Migration Fase 1-2</title>
<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head><body class="container" style="margin-top:30px">
<h3>Migration Fase 1 &amp; 2 — Restrukturisasi Karyawan</h3>
<p>
  <span class="label label-success"><?= $ok_count ?> OK</span>&nbsp;
  <span class="label label-<?= $err_count ? 'danger' : 'default' ?>"><?= $err_count ?> Error</span>
</p>
<table class="table table-bordered" style="font-size:13px">
<thead><tr><th>#</th><th>Step</th><th>Status</th><th>Pesan Error</th></tr></thead>
<tbody>
<?php foreach ($results as $i => $r): ?>
<tr class="<?= $r['ok'] ? '' : 'danger' ?>">
  <td><?= $i+1 ?></td>
  <td><?= htmlspecialchars($r['label']) ?></td>
  <td><?= $r['ok'] ? '<span class="text-success">✓ OK</span>' : '<span class="text-danger">✗ Error</span>' ?></td>
  <td><?= htmlspecialchars($r['error']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
<?php if ($err_count === 0): ?>
<div class="alert alert-success"><strong>Semua step berhasil.</strong> Fase 1 &amp; 2 selesai.</div>
<?php else: ?>
<div class="alert alert-danger">Ada <?= $err_count ?> error. Periksa detail di atas.</div>
<?php endif; ?>
<a href="../master_karyawan.php" class="btn btn-primary">Ke Master Karyawan</a>
</body></html>
