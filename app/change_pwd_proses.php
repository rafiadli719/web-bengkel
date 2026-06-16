<?php
session_start();

if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: change_pwd.php");
    exit;
}

include "../config/koneksi.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$txtid = isset($_POST['txtid']) ? (int) $_POST['txtid'] : 0;
$sessionUserId = (int) $_SESSION['_iduser'];
$oldPassword = isset($_POST['txtpwd_lama']) ? trim($_POST['txtpwd_lama']) : '';
$newPassword = isset($_POST['txtpwd']) ? trim($_POST['txtpwd']) : '';
$confirmPassword = isset($_POST['txtpwd_confirm']) ? trim($_POST['txtpwd_confirm']) : '';

if ($txtid <= 0 || $txtid !== $sessionUserId) {
    echo "<script>window.alert('Sesi user tidak valid. Silakan login ulang.'); window.location=('../index.php');</script>";
    exit;
}

if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo "<script>window.alert('Semua field password wajib diisi.'); window.location=('change_pwd.php');</script>";
    exit;
}

if (strlen($newPassword) < 6) {
    echo "<script>window.alert('Password baru minimal 6 karakter.'); window.location=('change_pwd.php');</script>";
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo "<script>window.alert('Konfirmasi password baru tidak cocok.'); window.location=('change_pwd.php');</script>";
    exit;
}

try {
    $stmt = mysqli_prepare($koneksi, "SELECT password FROM tbuser WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $txtid);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        echo "<script>window.alert('User tidak ditemukan.'); window.location=('change_pwd.php');</script>";
        exit;
    }

    // Tetap kompatibel dengan sistem lama yang masih menyimpan password plaintext.
    if (!hash_equals((string) $user['password'], $oldPassword)) {
        echo "<script>window.alert('Password saat ini tidak sesuai.'); window.location=('change_pwd.php');</script>";
        exit;
    }

    if (hash_equals($oldPassword, $newPassword)) {
        echo "<script>window.alert('Password baru harus berbeda dari password lama.'); window.location=('change_pwd.php');</script>";
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE tbuser SET password = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $newPassword, $txtid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    session_unset();
    session_destroy();

    echo "<script>window.alert('Password berhasil diubah. Silakan login kembali.'); window.location=('../index.php');</script>";
} catch (Throwable $e) {
    error_log('change_pwd_proses failed: ' . $e->getMessage());
    echo "<script>window.alert('Terjadi kesalahan saat mengubah password.'); window.location=('change_pwd.php');</script>";
}
?>
