<?php
// Sumber: web_kasir/hapus_pengeluaran1.php — hapus 1 baris pengeluaran
// milik kasir sendiri (guard kode_karyawan match). Gerbang asli cuma cek
// session login (semua role) -> kasir_operate (Task 10, sama kayak
// pengeluaran.php).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$kode_karyawan = $kode_karyawan_aktif;

// Get ID from URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    die("ID pengeluaran tidak ditemukan.");
}

try {
    // Fetch the pengeluaran data first to get the kode_transaksi
    $sql = "SELECT kode_transaksi FROM pengeluaran_kasir_closing_kasir WHERE id = :id AND kode_karyawan = :kode_karyawan";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
    $stmt->execute();
    $pengeluaran = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pengeluaran) {
        die("Data pengeluaran tidak ditemukan atau Anda tidak memiliki akses untuk menghapus data ini.");
    }

    $kode_transaksi = $pengeluaran['kode_transaksi'];

    // Check if Kas Akhir has been input (additional security check)
    $sql_check_kas_akhir = "SELECT total_nilai FROM kas_akhir WHERE kode_transaksi = :kode_transaksi AND kode_karyawan = :kode_karyawan";
    $stmt_check_kas_akhir = $pdo->prepare($sql_check_kas_akhir);
    $stmt_check_kas_akhir->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
    $stmt_check_kas_akhir->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);
    $stmt_check_kas_akhir->execute();
    $kas_akhir_exists = $stmt_check_kas_akhir->fetchColumn();

    if (!$kas_akhir_exists) {
        die("Kas Akhir belum diinput. Anda tidak bisa menghapus pengeluaran sebelum Kas Akhir diinput.");
    }

    // Delete the pengeluaran data based on ID and kode_karyawan
    $sql = "DELETE FROM pengeluaran_kasir_closing_kasir WHERE id = :id AND kode_karyawan = :kode_karyawan";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':kode_karyawan', $kode_karyawan, PDO::PARAM_STR);

    if ($stmt->execute()) {
        error_log("Data pengeluaran ID: $id berhasil dihapus oleh karyawan: $kode_karyawan dari edit_pengeluaran1");
        header("Location: edit_pengeluaran1.php?kode_transaksi=$kode_transaksi&deleted=1");
        exit;
    } else {
        error_log("Gagal menghapus data pengeluaran ID: $id dari edit_pengeluaran1");
        header("Location: edit_pengeluaran1.php?kode_transaksi=$kode_transaksi&error=delete_failed");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error saat menghapus pengeluaran dari edit_pengeluaran1: " . $e->getMessage());
    header("Location: edit_pengeluaran1.php?error=database_error");
    exit;
} catch (Exception $e) {
    error_log("Error saat menghapus pengeluaran dari edit_pengeluaran1: " . $e->getMessage());
    header("Location: edit_pengeluaran1.php?error=general_error");
    exit;
}
