<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit();
}

include "../config/koneksi.php";

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

$cari_kd = mysqli_query($koneksi,"SELECT user_akses FROM tbuser WHERE id='$id_user'");
$tm_cari = mysqli_fetch_array($cari_kd);
$lvl_akses = $tm_cari['user_akses'];

$is_admin_pengadaan = ($lvl_akses == 'admin' || $lvl_akses == 'pengadaan');
if (!$is_admin_pengadaan) {
    header('Location: master_tipe_header.php');
    exit();
}

$page_title = "Input Tipe Header Motor Baru";
$error_msg = '';
$success_msg = '';

// Get daftar merk untuk dropdown
$merk_query = "SELECT id, merek, kode_brand FROM tbpabrik_motor WHERE status = '1' ORDER BY merek ASC";
$merk_result = mysqli_query($koneksi, $merk_query);

// Process form submission
if ($_POST) {
    $tipe_header = mysqli_real_escape_string($koneksi, strtoupper(trim($_POST['tipe_header'])));
    $id_brand = (int)$_POST['id_brand'];

    // Validasi input
    if (empty($tipe_header)) {
        $error_msg = "Tipe Header harus diisi!";
    } elseif (strpos($tipe_header, ' ') !== false) {
        $error_msg = "Tipe Header hanya boleh satu kata, tidak boleh ada spasi!";
    } elseif ($id_brand <= 0) {
        $error_msg = "Merk harus dipilih!";
    } else {
        // Get kode_brand from selected brand
        $brand_query = "SELECT kode_brand FROM tbpabrik_motor WHERE id = $id_brand AND status = '1'";
        $brand_result = mysqli_query($koneksi, $brand_query);

        if (mysqli_num_rows($brand_result) > 0) {
            $brand_data = mysqli_fetch_array($brand_result);
            $kode_brand = $brand_data['kode_brand'];

            // Cek duplikat tipe header
            $check_query = "SELECT id FROM tbmaster_tipe_header WHERE nama_model = '$tipe_header' AND id_brand = $id_brand AND status = '1'";
            $check_result = mysqli_query($koneksi, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error_msg = "Tipe header '$tipe_header' untuk brand '$kode_brand' sudah ada! Silakan gunakan nama yang berbeda.";
            } else {
                // Insert data
                $insert_query = "INSERT INTO tbmaster_tipe_header (nama_model, id_brand, status, created_at)
                               VALUES ('$tipe_header', $id_brand, '1', NOW())";

                if (mysqli_query($koneksi, $insert_query)) {
                    $_SESSION['success'] = "Data tipe header '$tipe_header' berhasil tersimpan!";
                    header('Location: master_tipe_header.php');
                    exit();
                } else {
                    $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
                }
            }
        } else {
            $error_msg = "Brand tidak valid!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title><?php echo $page_title; ?> - Web Bengkel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/ace.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/font-awesome/4.5.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/chosen.min.css" rel="stylesheet" type="text/css" />

    <style>
        .page-header {
            border-bottom: 2px solid #e5e5e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-actions {
            border-top: 1px solid #e5e5e5;
            padding-top: 20px;
            margin-top: 30px;
        }
        .required {
            color: #d15b47;
        }
        .help-text {
            font-size: 11px;
            color: #999;
        }
        .kode-display {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 8px 12px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
        }
    </style>
</head>

<body class="no-skin">
    <div class="main-content">
        <div class="main-content-inner">
            <div class="page-content">
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h1>
                                <?php echo $page_title; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Tambah Data Tipe Header Motor
                                </small>
                            </h1>
                        </div>

                        <div class="form-container">
                            <!-- Alert Messages -->
                            <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Error!</strong> <?php echo $error_msg; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                                <i class="fa fa-check"></i>
                                <strong>Berhasil!</strong> <?php echo $success_msg; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Form -->
                            <form method="POST" action="" class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Tipe Header <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-4">
                                        <input type="text" name="tipe_header" class="form-control"
                                               value="<?php echo isset($tipe_header) ? htmlspecialchars($tipe_header) : ''; ?>"
                                               placeholder="Contoh: PCX"
                                               maxlength="50"
                                               style="text-transform: uppercase;"
                                               autocomplete="off" required>
                                        <div class="help-text">
                                            Diisi dengan satu kata, tidak boleh ada spasi.
                                            Tidak boleh ada tipe header yang sama persis.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Merk <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-4">
                                        <select name="id_brand" class="form-control chosen-select" required>
                                            <option value="">Pilih Merk Motor</option>
                                            <?php
                                            mysqli_data_seek($merk_result, 0); // Reset pointer
                                            while ($merk = mysqli_fetch_assoc($merk_result)) {
                                                $selected = (isset($id_brand) && $id_brand == $merk['id']) ? 'selected' : '';
                                                echo "<option value='{$merk['id']}' $selected>{$merk['merek']}</option>";
                                            }
                                            ?>
                                        </select>
                                        <div class="help-text">
                                            Merk bisa diisi dengan mengetik (type to find) atau dengan memilih dari daftar merk yang sudah diinput sebelumnya.
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="control-label" style="margin-top: 7px;">
                                            Kode Merk:
                                        </label>
                                        <div class="kode-display" id="kode_display">-</div>
                                        <div class="help-text">
                                            Kode Merk akan terisi otomatis setelah memilih merk.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Simpan
                                        </button>

                                        <a href="master_tipe_header.php" class="btn btn-success">
                                            <i class="fa fa-list"></i> Lihat Daftar Tipe Header
                                        </a>

                                        <a href="menu_master01h.php" class="btn btn-default">
                                            <i class="fa fa-home"></i> Ke Menu Awal
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <!-- Info -->
                            <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #5bc0de;">
                                <h5><strong>Petunjuk Pengisian:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Tipe Header harus satu kata tanpa spasi (contoh: PCX, BEAT, MIO, SUPRA)</li>
                                    <li>• Pilih merk motor dari dropdown yang tersedia</li>
                                    <li>• Kode merk akan muncul otomatis setelah memilih merk</li>
                                    <li>• Jika setelah klik simpan tapi tipe header sudah ada, maka muncul pesan info & harus diganti</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Chosen
            $('.chosen-select').chosen({
                allow_single_deselect: true,
                search_contains: true,
                width: '100%'
            });

            // Auto focus pada tipe header
            $('input[name="tipe_header"]').focus();

            // Data merk untuk mapping kode
            var merkData = {
                <?php
                mysqli_data_seek($merk_result, 0);
                $items = [];
                while ($merk = mysqli_fetch_assoc($merk_result)) {
                    $items[] = "'{$merk['id']}': '{$merk['kode_brand']}'";
                }
                echo implode(', ', $items);
                ?>
            };

            // Update kode merk saat pilih merk
            $('select[name="id_brand"]').on('change', function() {
                var selectedId = $(this).val();
                var kodeDisplay = $('#kode_display');

                if (selectedId && merkData[selectedId]) {
                    kodeDisplay.text(merkData[selectedId]);
                } else {
                    kodeDisplay.text('-');
                }
            });

            // Trigger change event untuk set initial value
            $('select[name="id_brand"]').trigger('change');

            // Remove spaces from tipe header input
            $('input[name="tipe_header"]').on('input', function() {
                var val = this.value.toUpperCase().replace(/\s+/g, '');
                this.value = val;
            });

            // Validasi form sebelum submit
            $('form').on('submit', function(e) {
                var tipeHeader = $('input[name="tipe_header"]').val().trim();
                var idBrand = $('select[name="id_brand"]').val();

                if (!tipeHeader) {
                    alert('Tipe Header harus diisi!');
                    e.preventDefault();
                    return false;
                }

                if (tipeHeader.indexOf(' ') !== -1) {
                    alert('Tipe Header tidak boleh mengandung spasi!');
                    e.preventDefault();
                    return false;
                }

                if (!idBrand) {
                    alert('Merk harus dipilih!');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>