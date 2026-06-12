<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    $kd_cabang=$_SESSION['_cabang'];
    include "../config/koneksi.php";

    // Basic user and access check
    $cari_kd = mysqli_query($koneksi,"SELECT user_akses FROM tbuser WHERE id='$id_user'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $lvl_akses = $tm_cari['user_akses'];

    // Get ID and data
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

    // Process form submission
    if ($_POST) {
        $keterangan = trim($_POST['keterangan']);

        if (empty($keterangan)) {
            $error_msg = "Nama merk harus diisi!";
        } else {
            // Update data
            $update_query = "UPDATE tbpabrik_motor SET merek = '$keterangan' WHERE id = $id";
            if (mysqli_query($koneksi, $update_query)) {
                $_SESSION['success'] = "Data merk motor berhasil diupdate!";
                header('Location: master_merk_motor.php');
                exit();
            } else {
                $error_msg = "Gagal mengupdate data: " . mysqli_error($koneksi);
            }
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Merk Motor</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        .container { margin-top: 30px; }
        .form-group { margin-bottom: 15px; }
        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Merk Motor</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-horizontal">
            <div class="form-group">
                <label class="col-sm-2 control-label">Kode Merk:</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['kode_brand']); ?>" disabled>
                    <small>Kode merk tidak dapat diubah</small>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">Nama Merk:</label>
                <div class="col-sm-6">
                    <input type="text" name="keterangan" class="form-control"
                           value="<?php echo htmlspecialchars($data['merek']); ?>"
                           style="text-transform: uppercase;" required>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="master_merk_motor.php" class="btn btn-default">Kembali</a>
                </div>
            </div>
        </form>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        $('input[name="keterangan"]').focus();
    </script>
</body>
</html>

<?php } ?>