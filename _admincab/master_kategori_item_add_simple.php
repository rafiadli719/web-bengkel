<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    $error_msg = '';

    // Process form submission
    if ($_POST) {
        $kategori_item = strtoupper(trim($_POST['kategori_item']));
        $keterangan = trim($_POST['keterangan']);
        $margin_sesuai_jenis = $_POST['margin_sesuai_jenis'];
        $margin_kategori = ($margin_sesuai_jenis == 'TIDAK' && !empty($_POST['margin_kategori'])) ?
                           (float)$_POST['margin_kategori'] : null;

        if (empty($kategori_item)) {
            $error_msg = "Kategori Item harus diisi!";
        } elseif (strpos($kategori_item, ' ') !== false) {
            $error_msg = "Kategori Item tidak boleh mengandung spasi!";
        } elseif (empty($keterangan)) {
            $error_msg = "Keterangan harus diisi!";
        } elseif ($margin_sesuai_jenis == 'TIDAK' && (empty($margin_kategori) || $margin_kategori <= 0)) {
            $error_msg = "Margin Kategori harus diisi jika 'Margin Sesuai Jenis' = TIDAK!";
        } else {
            // Check duplicate
            $check_query = "SELECT id FROM tbmaster_kategori_item WHERE kategori_item = '$kategori_item' AND status = '1'";
            $check_result = mysqli_query($koneksi, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error_msg = "Kategori item '$kategori_item' sudah ada!";
            } else {
                // Insert data
                $margin_value = ($margin_kategori !== null) ? "'$margin_kategori'" : 'NULL';
                $insert_query = "INSERT INTO tbmaster_kategori_item (kategori_item, keterangan, margin_sesuai_jenis, margin_kategori, status, created_at)
                               VALUES ('$kategori_item', '$keterangan', '$margin_sesuai_jenis', $margin_value, '1', NOW())";

                if (mysqli_query($koneksi, $insert_query)) {
                    $_SESSION['success'] = "Data kategori item '$kategori_item' berhasil tersimpan!";
                    header('Location: master_kategori_item.php');
                    exit();
                } else {
                    $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Input Kategori Item Baru</title>
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
        <h2>Input Kategori Item Baru</h2>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-horizontal">
            <div class="form-group">
                <label class="col-sm-3 control-label">Kategori Item:</label>
                <div class="col-sm-4">
                    <input type="text" name="kategori_item" class="form-control"
                           value="<?php echo isset($kategori_item) ? htmlspecialchars($kategori_item) : ''; ?>"
                           placeholder="Contoh: BAUD, CAIRAN"
                           style="text-transform: uppercase;" required>
                    <small>Satu kata, tidak boleh spasi</small>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Keterangan:</label>
                <div class="col-sm-6">
                    <input type="text" name="keterangan" class="form-control"
                           value="<?php echo isset($keterangan) ? htmlspecialchars($keterangan) : ''; ?>"
                           placeholder="Contoh: BAUD 8, 10, 12, DLL" required>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-3 control-label">Margin Sesuai Jenis:</label>
                <div class="col-sm-4">
                    <select name="margin_sesuai_jenis" class="form-control" id="margin_sesuai_jenis" required>
                        <option value="YA" <?php echo (isset($margin_sesuai_jenis) && $margin_sesuai_jenis == 'YA') ? 'selected' : 'selected'; ?>>YA</option>
                        <option value="TIDAK" <?php echo (isset($margin_sesuai_jenis) && $margin_sesuai_jenis == 'TIDAK') ? 'selected' : ''; ?>>TIDAK</option>
                    </select>
                    <small>Pilih YA jika margin mengikuti jenis item</small>
                </div>
            </div>

            <div class="form-group margin-fields">
                <label class="col-sm-3 control-label">Margin Kategori (%):</label>
                <div class="col-sm-3">
                    <input type="number" name="margin_kategori" class="form-control"
                           value="<?php echo isset($margin_kategori) ? $margin_kategori : ''; ?>"
                           placeholder="Contoh: 40" min="1" max="100" step="0.01">
                    <small>Diisi jika margin sesuai jenis = TIDAK</small>
                </div>
            </div>

            <div class="form-group">
                <div class="col-sm-offset-3 col-sm-10">
                    <button type="submit" class="btn btn-primary">Simpan</button>
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

            $('input[name="kategori_item"]').focus();
        });
    </script>
</body>
</html>

<?php } ?>