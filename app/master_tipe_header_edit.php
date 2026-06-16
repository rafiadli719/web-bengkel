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

$page_title = "Edit Tipe Header Motor";
$error_msg = '';
$success_msg = '';

// Get ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: master_tipe_header.php');
    exit();
}

// Get current data
$query = "SELECT th.*, pm.merek, pm.kode_brand
          FROM tbmaster_tipe_header th
          LEFT JOIN tbpabrik_motor pm ON th.id_brand = pm.id
          WHERE th.id = $id AND th.status = '1'";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    header('Location: master_tipe_header.php');
    exit();
}

$data = mysqli_fetch_assoc($result);

// Get daftar merk untuk dropdown
$merk_query = "SELECT id, merek, kode_brand FROM tbpabrik_motor WHERE status = '1' ORDER BY merek ASC";
$merk_result = mysqli_query($koneksi, $merk_query);

// Process form submission
if ($_POST) {
    $id_brand = (int)$_POST['id_brand'];

    // Validasi input - tipe header tidak bisa diubah untuk menjaga konsistensi
    if ($id_brand <= 0) {
        $error_msg = "Merk harus dipilih!";
    } else {
        // Update data
        $update_query = "UPDATE tbmaster_tipe_header SET id_brand = $id_brand, updated_at = NOW() WHERE id = $id";

        if (mysqli_query($koneksi, $update_query)) {
            $success_msg = "Data berhasil diupdate!";
            // Refresh data
            $data['id_brand'] = $id_brand;
            // Get updated merk info
            $merk_info = mysqli_query($koneksi, "SELECT merek, kode_brand FROM tbpabrik_motor WHERE id = $id_brand");
            if ($merk_data = mysqli_fetch_assoc($merk_info)) {
                $data['merek'] = $merk_data['merek'];
                $data['kode_brand'] = $merk_data['kode_brand'];
            }
        } else {
            $error_msg = "Gagal mengupdate data: " . mysqli_error($koneksi);
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
        .data-awal {
            background-color: #f8f8f8;
            border-left: 4px solid #1f8dd6;
            padding: 15px;
            margin-bottom: 20px;
        }
        .edit-section {
            background-color: #f0f7ff;
            border-left: 4px solid #5bc0de;
            padding: 15px;
            margin-bottom: 20px;
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
                                    Ubah Data Tipe Header Motor
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

                            <!-- Data Awal -->
                            <div class="data-awal">
                                <h5><strong>Data Awal</strong></h5>
                                <p class="help-text">Data awal yang muncul hanya tampilan, bisa mengubahnya di kolom "edit menjadi".</p>
                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-sm-3"><strong>Tipe Header :</strong></div>
                                    <div class="col-sm-9"><?php echo htmlspecialchars($data['nama_model']); ?></div>
                                </div>
                                <div class="row" style="margin-top: 8px;">
                                    <div class="col-sm-3"><strong>Merk :</strong></div>
                                    <div class="col-sm-9">
                                        <?php echo htmlspecialchars($data['merek']); ?>
                                        <?php if ($data['kode_brand']): ?>
                                            <span class="text-muted">(<?php echo htmlspecialchars($data['kode_brand']); ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Section -->
                            <div class="edit-section">
                                <h5><strong>Edit Menjadi</strong></h5>
                                <form method="POST" action="" class="form-horizontal" style="margin-top: 20px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">
                                            Merk <span class="required">*</span>
                                        </label>
                                        <div class="col-sm-4">
                                            <select name="id_brand" class="form-control chosen-select" required>
                                                <option value="">Pilih Merk Motor</option>
                                                <?php
                                                while ($merk = mysqli_fetch_assoc($merk_result)) {
                                                    $selected = ($data['id_brand'] == $merk['id']) ? 'selected' : '';
                                                    echo "<option value='{$merk['id']}' $selected>{$merk['merek']}</option>";
                                                }
                                                ?>
                                            </select>
                                            <div class="help-text">
                                                Muncul merk dari data awal, namun bisa diubah.
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="control-label" style="margin-top: 7px;">
                                                Kode Merk:
                                            </label>
                                            <div class="kode-display" id="kode_display"><?php echo htmlspecialchars($data['kode_brand'] ?? '-'); ?></div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="form-actions">
                                <div class="text-center">
                                    <button type="submit" form="editForm" class="btn btn-primary">
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

                            <!-- Info -->
                            <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f39c12;">
                                <h5><strong>Catatan:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Tipe Header tidak dapat diubah untuk menjaga konsistensi data tipe detail</li>
                                    <li>• Hanya merk yang dapat diubah</li>
                                    <li>• Kode merk akan berubah otomatis sesuai merk yang dipilih</li>
                                    <li>• Perubahan akan mempengaruhi semua tipe detail yang menggunakan tipe header ini</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form tersembunyi untuk submit -->
    <form id="editForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="id_brand" id="hidden_id_brand">
    </form>

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

            // Handle submit
            $('button[type="submit"]').on('click', function(e) {
                e.preventDefault();

                var idBrand = $('select[name="id_brand"]').val();

                if (!idBrand) {
                    alert('Merk harus dipilih!');
                    return false;
                }

                // Set nilai ke form tersembunyi dan submit
                $('#hidden_id_brand').val(idBrand);
                $('#editForm').submit();
            });
        });
    </script>
</body>
</html>