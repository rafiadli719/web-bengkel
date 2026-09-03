<?php
// Helper koneksi khusus modul kasir — reuse koneksi & RBAC fitmotor, bukan bikin baru
require_once __DIR__ . '/../../koneksi.php'; // $koneksi (mysqli) sudah tersedia dari sini
require_once __DIR__ . '/../../_include_menu_rbac.php'; // hasRbacPermission(), canAccessPage(), requirePermission()
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['_iduser'])) {
    header('Location: /index.php'); // root login fitmotor — sesuaikan base path deploy kalau beda
    exit;
}

// Guard permission dasar modul kasir — tiap file spesifik (kas_awal.php dst)
// WAJIB panggil requirePermission() lagi di atasnya sendiri dengan kode yang
// lebih spesifik (kasir_operate/kasir_close/kasir_approve/kasir_admin, Task 10)
// — ini cuma baseline "boleh lihat menu kasir sama sekali".
requirePermission($koneksi, (int) $_SESSION['_iduser'], 'kasir_menu_read');

// kode_cabang_aktif — WAJIB translate, $_SESSION['_cabang'] isinya
// tbuser.kode_cabang (teks, mis. "PST"), BUKAN tbcabang.cabang_ref_kode
// (numerik) yang dipakai FK semua tabel *_closing_kasir.
$kode_cabang_text = $_SESSION['_cabang'] ?? '';
$kode_cabang_aktif = null;
$nama_cabang_aktif = null;
if ($kode_cabang_text !== '') {
    $stmt = mysqli_prepare($koneksi, "SELECT cabang_ref_kode, nama_cabang FROM tbcabang WHERE kode_cabang = ?");
    mysqli_stmt_bind_param($stmt, 's', $kode_cabang_text);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rowCabang = mysqli_fetch_assoc($res);
    $kode_cabang_aktif = $rowCabang['cabang_ref_kode'] ?? null;
    $nama_cabang_aktif = $rowCabang['nama_cabang'] ?? null;
}
if ($kode_cabang_aktif === null) {
    die('Cabang aktif tidak valid atau belum dipilih — hubungi admin IT.');
}

// kode_karyawan_aktif — session TIDAK simpan kode_karyawan langsung, cuma
// _iduser (tbuser.id). Lookup sekali per request.
$id_user_aktif = (int) $_SESSION['_iduser'];
$stmt2 = mysqli_prepare($koneksi, "SELECT kode_karyawan, nama_lengkap FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmt2, 'i', $id_user_aktif);
mysqli_stmt_execute($stmt2);
$res2 = mysqli_stmt_get_result($stmt2);
$rowUser = mysqli_fetch_assoc($res2);
$kode_karyawan_aktif = $rowUser['kode_karyawan'] ?? null;
$nama_karyawan_aktif = $rowUser['nama_lengkap'] ?? null;
if ($kode_karyawan_aktif === null) {
    die('Data karyawan tidak ditemukan — hubungi admin IT.');
}

// Kompatibilitas fungsi hasil porting web_kasir (closing_revision_helpers.php dkk)
// yang masih terima array $session gaya lama (['kode_karyawan'=>..,'role'=>..])
// — bukan session asli fitmotor, cuma array kompatibilitas dibentuk dari posisi
// fitmotor. Mapping kode_posisi -> role sesuai Task 10 (kebalikan mapping migrasi).
$kode_posisi_aktif = $_SESSION['_kode_posisi'] ?? '';
$roleMap = ['ADM' => 'super_admin', 'KEU' => 'admin', 'KSR' => 'kasir'];
$legacy_session_kasir = [
    'kode_karyawan' => $kode_karyawan_aktif,
    'role' => $roleMap[$kode_posisi_aktif] ?? 'kasir',
];
