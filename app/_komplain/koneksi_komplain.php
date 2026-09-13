<?php
// Helper koneksi khusus modul komplain — reuse koneksi & RBAC fitmotor
// (pola app/_keuangan/kasir/koneksi_kasir.php), plus PDO terpisah buat
// tabel tblkomplain* (getenv() kredensial, no hardcode).
require_once __DIR__ . '/../koneksi.php'; // $koneksi (mysqli) sudah tersedia dari sini
require_once __DIR__ . '/../_include_menu_rbac.php'; // hasRbacPermission(), getUserPermissions()
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['_iduser'])) {
    header('Location: /index.php'); // root login fitmotor
    exit;
}

// kode_karyawan_aktif — session cuma simpan _iduser (tbuser.id), bukan
// kode_karyawan langsung. Lookup sekali per request (sama pola koneksi_kasir.php).
$id_user_aktif = (int) $_SESSION['_iduser'];
$stmtUser = mysqli_prepare($koneksi, "SELECT kode_karyawan, kode_cabang FROM tbuser WHERE id = ?");
mysqli_stmt_bind_param($stmtUser, 'i', $id_user_aktif);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$rowUser = mysqli_fetch_assoc($resUser);
$kode_karyawan_aktif = $rowUser['kode_karyawan'] ?? null;
// kode_cabang_aktif — modul komplain ikut pola tbuser.kode_cabang (teks
// pendek, mis. "PST"), BUKAN tbcabang.cabang_ref_kode (numerik) — sesuai
// Global Constraint plan modul komplain (beda dari modul kasir yang FK-nya
// butuh cabang_ref_kode numerik).
$kode_cabang_aktif = $rowUser['kode_cabang'] ?? ($_SESSION['_cabang'] ?? null);
if ($kode_karyawan_aktif === null) {
    die('Data karyawan tidak ditemukan — hubungi admin IT.');
}

// Permission user — dihitung live tiap request dari tb_master_posisi.permissions
// (getUserPermissions), BUKAN disimpan di session (session gak pernah set
// $_SESSION['permissions'] di sistem live fitmotor).
$komplain_user_permissions = getUserPermissions($koneksi, $id_user_aktif);

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

function cekPermissionKomplain(string $kode): bool {
    global $komplain_user_permissions;
    return hasRbacPermission($komplain_user_permissions, $kode);
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
    global $kode_karyawan_aktif;
    $stmt = $db->prepare(
        "INSERT INTO tblkomplain_log (komplain_id, aksi, status_sebelum, status_sesudah, kode_karyawan_pelaku, keterangan)
         VALUES (:id, :aksi, :sebelum, :sesudah, :pelaku, :ket)"
    );
    $stmt->execute([
        ':id' => $komplainId, ':aksi' => $aksi, ':sebelum' => $statusSebelum,
        ':sesudah' => $statusSesudah, ':pelaku' => $kode_karyawan_aktif, ':ket' => $keterangan,
    ]);
}
