<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    // Basic user and access check
    $cari_kd = mysqli_query($koneksi,"SELECT user_akses FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $lvl_akses = $tm_cari['user_akses'];

    // Get ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: master_merk_motor.php');
        exit();
    }

    // Get current data
    $query = "SELECT * FROM tbpabrik_motor WHERE id = $id AND status = '1'";
    $result = mysqli_query($koneksi, $query);
    if (mysqli_num_rows($result) == 0) {
        header('Location: master_merk_motor.php');
        exit();
    }

    $data = mysqli_fetch_assoc($result);
    $error_msg = '';

    // Process deletion
    if ($_POST && isset($_POST['confirm_delete'])) {
        // Soft delete
        $delete_query = "UPDATE tbpabrik_motor SET status = '0' WHERE id = $id";
        if (mysqli_query($koneksi, $delete_query)) {
            $_SESSION['delete_success'] = "Data merk motor '{$data['merek']}' berhasil dihapus!";
            header('Location: master_merk_motor.php');
            exit();
        } else {
            $error_msg = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hapus Merk Motor</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        .container { margin-top: 30px; }
        .delete-box { background: #f8f8f8; padding: 20px; border-radius: 5px; border-left: 4px solid #d9534f; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hapus Merk Motor</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="delete-box">
            <h4><i class="glyphicon glyphicon-warning-sign"></i> Konfirmasi Penghapusan</h4>
            <p>Anda yakin ingin menghapus data merk motor berikut?</p>

            <table class="table table-bordered" style="margin-top: 15px;">
                <tr>
                    <th width="30%">Kode Merk</th>
                    <td><strong><?php echo htmlspecialchars($data['kode_brand']); ?></strong></td>
                </tr>
                <tr>
                    <th>Nama Merk</th>
                    <td><strong><?php echo htmlspecialchars($data['merek']); ?></strong></td>
                </tr>
            </table>

            <form method="POST">
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="glyphicon glyphicon-trash"></i> Ya, Hapus Data Ini
                </button>
                <a href="master_merk_motor.php" class="btn btn-default">
                    <i class="glyphicon glyphicon-remove"></i> Batal
                </a>
            </form>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>

<?php } ?>