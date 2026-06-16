<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    // Get ID
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('Location: master_kategori_item.php');
        exit();
    }

    // Get current data
    $query = "SELECT * FROM tbmaster_kategori_item WHERE id = '$id' AND status = '1'";
    $result = mysqli_query($koneksi, $query);
    if (mysqli_num_rows($result) == 0) {
        header('Location: master_kategori_item.php');
        exit();
    }

    $data = mysqli_fetch_assoc($result);
    $error_msg = '';

    // Process deletion
    if ($_POST && isset($_POST['confirm_delete'])) {
        // Soft delete
        $delete_query = "UPDATE tbmaster_kategori_item SET status = '0' WHERE id = '$id'";
        if (mysqli_query($koneksi, $delete_query)) {
            $_SESSION['delete_success'] = "Data kategori item '{$data['kategori_item']}' berhasil dihapus!";
            header('Location: master_kategori_item.php');
            exit();
        } else {
            $error_msg = "Gagal menghapus data: " . mysqli_error($koneksi);
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hapus Kategori Item</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        .container { margin-top: 30px; }
        .delete-box { background: #f8f8f8; padding: 20px; border-radius: 5px; border-left: 4px solid #d9534f; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Hapus Kategori Item</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="delete-box">
            <h4><i class="glyphicon glyphicon-warning-sign"></i> Konfirmasi Penghapusan</h4>
            <p>Anda yakin ingin menghapus data kategori item berikut?</p>

            <table class="table table-bordered" style="margin-top: 15px;">
                <tr>
                    <th width="30%">Kategori Item</th>
                    <td><strong><?php echo htmlspecialchars($data['kategori_item']); ?></strong></td>
                </tr>
                <tr>
                    <th>Keterangan</th>
                    <td><?php echo htmlspecialchars($data['keterangan']); ?></td>
                </tr>
                <tr>
                    <th>Margin Sesuai Jenis</th>
                    <td><?php echo $data['margin_sesuai_jenis']; ?></td>
                </tr>
                <?php if ($data['margin_sesuai_jenis'] == 'TIDAK' && $data['margin_kategori']): ?>
                <tr>
                    <th>Margin Kategori</th>
                    <td><?php echo number_format($data['margin_kategori'], 2); ?>%</td>
                </tr>
                <?php endif; ?>
            </table>

            <form method="POST">
                <button type="submit" name="confirm_delete" class="btn btn-danger">
                    <i class="glyphicon glyphicon-trash"></i> Ya, Hapus Data Ini
                </button>
                <a href="master_kategori_item.php" class="btn btn-default">
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