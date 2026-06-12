<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    // Get ID and data
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

    // Process form submission
    if ($_POST) {
        $keterangan = trim($_POST['keterangan']);
        $margin_sesuai_jenis = $_POST['margin_sesuai_jenis'];
        $margin_kategori = ($margin_sesuai_jenis == 'TIDAK' && !empty($_POST['margin_kategori'])) ?
                           (float)$_POST['margin_kategori'] : null;

        if (empty($keterangan)) {
            $error_msg = "Keterangan harus diisi!";
        } elseif ($margin_sesuai_jenis == 'TIDAK' && (empty($margin_kategori) || $margin_kategori <= 0)) {
            $error_msg = "Margin Kategori harus diisi jika 'Margin Sesuai Jenis' = TIDAK!";
        } else {
            // Update data
            $margin_value = ($margin_kategori !== null) ? "'$margin_kategori'" : 'NULL';
            $update_query = "UPDATE tbmaster_kategori_item SET
                           keterangan = '$keterangan',
                           margin_sesuai_jenis = '$margin_sesuai_jenis',
                           margin_kategori = $margin_value,
                           updated_at = NOW()
                           WHERE id = '$id'";

            if (mysqli_query($koneksi, $update_query)) {
                $_SESSION['success'] = "Data kategori item berhasil diupdate!";
                header('Location: master_kategori_item.php');
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
    <title>Edit Kategori Item</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        .container { margin-top: 30px; }
        .form-group { margin-bottom: 15px; }
        .alert { margin-bottom: 20px; }
        .margin-fields { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Kategori Item</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-horizontal">
            <div class="form-group">
                <label class="col-sm-3 control-label">Kategori Item:</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control"
                           value="<?php echo htmlspecialchars($data['kategori_item']); ?>" disabled>
                    <small>Kategori item tidak dapat diubah</small>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Keterangan:</label>
                <div class="col-sm-6">
                    <input type="text" name="keterangan" class="form-control"
                           value="<?php echo htmlspecialchars($data['keterangan']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Margin Sesuai Jenis:</label>
                <div class="col-sm-4">
                    <select name="margin_sesuai_jenis" class="form-control" id="margin_sesuai_jenis" required>
                        <option value="YA" <?php echo ($data['margin_sesuai_jenis'] == 'YA') ? 'selected' : ''; ?>>YA</option>
                        <option value="TIDAK" <?php echo ($data['margin_sesuai_jenis'] == 'TIDAK') ? 'selected' : ''; ?>>TIDAK</option>
                    </select>
                </div>
            </div>

            <div class="form-group margin-fields">
                <label class="col-sm-3 control-label">Margin Kategori (%):</label>
                <div class="col-sm-3">
                    <input type="number" name="margin_kategori" class="form-control"
                           value="<?php echo $data['margin_kategori'] ? $data['margin_kategori'] : ''; ?>"
                           min="1" max="100" step="0.01">
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-10">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="master_kategori_item.php" class="btn btn-default">Kembali</a>
                </div>
            </div>
        </form>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Show/hide margin fields based on selection
            $('#margin_sesuai_jenis').on('change', function() {
                if ($(this).val() == 'TIDAK') {
                    $('.margin-fields').show();
                    $('input[name="margin_kategori"]').attr('required', true);
                } else {
                    $('.margin-fields').hide();
                    $('input[name="margin_kategori"]').removeAttr('required').val('');
                }
            });

            // Initial check
            if ($('#margin_sesuai_jenis').val() == 'TIDAK') {
                $('.margin-fields').show();
                $('input[name="margin_kategori"]').attr('required', true);
            }

            $('input[name="keterangan"]').focus();
        });
    </script>
</body>
</html>

<?php } ?>