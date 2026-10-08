<?php
// Helper koneksi modul Persediaan (Moving Average). Pola sama dengan _komplain/koneksi_komplain.php:
// reuse $koneksi (mysqli) + RBAC fitmotor. Tanpa kredensial baru.
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../_include_menu_rbac.php';
require_once __DIR__ . '/../lib/InventoryService.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['_iduser'])) {
    header('Location: ../../index.php');
    exit;
}

$id_user_aktif = (int) $_SESSION['_iduser'];
$stmtUser = mysqli_prepare($koneksi, "SELECT kode_karyawan, kode_cabang, nama_user, foto_user FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, 'i', $id_user_aktif);
mysqli_stmt_execute($stmtUser);
$rowUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));
$kode_karyawan_aktif = $rowUser['kode_karyawan'] ?? null;
$_nama = $rowUser['nama_user'] ?? '';
$foto_user = ($rowUser['foto_user'] ?? '') ?: 'file_upload/avatar.png';
$kode_cabang_aktif = $rowUser['kode_cabang'] ?? ($_SESSION['_cabang'] ?? null);
if ($kode_karyawan_aktif === null) {
    die('Data karyawan tidak ditemukan — hubungi admin IT.');
}

$inv_user_permissions = getUserPermissions($koneksi, $id_user_aktif);

function cekPermissionInv(string $kode): bool {
    global $inv_user_permissions;
    return hasRbacPermission($inv_user_permissions, $kode);
}

function wajibPermissionInv(string $kode): void {
    if (!cekPermissionInv($kode)) {
        http_response_code(403);
        die('Tidak punya akses (' . htmlspecialchars($kode) . ').');
    }
}

/** Lokasi yang boleh dilihat user: semua bila punya inv_view_all, selain itu cabangnya sendiri. */
function lokasiBolehInv($koneksi, ?string $kodeCabangUser): array {
    $semua = [];
    $r = mysqli_query($koneksi, "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY kode_cabang");
    while ($x = mysqli_fetch_assoc($r)) {
        $semua[$x['kode_cabang']] = $x['nama_cabang'];
    }
    if (cekPermissionInv('inv_view_all')) {
        return $semua;
    }
    return isset($semua[$kodeCabangUser]) ? [$kodeCabangUser => $semua[$kodeCabangUser]] : [];
}

function csrfTokenInv(): string {
    if (empty($_SESSION['_inv_csrf'])) {
        $_SESSION['_inv_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_inv_csrf'];
}

function csrfCekInv(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['_inv_csrf']) || !hash_equals($_SESSION['_inv_csrf'], $t)) {
        http_response_code(400);
        die('Token tidak valid. Muat ulang halaman.');
    }
}

function einv($s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
