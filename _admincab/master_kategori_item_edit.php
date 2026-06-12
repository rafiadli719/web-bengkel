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
    header('Location: master_kategori_item.php');
    exit();
}

$page_title = "Edit Kategori Item";
$error_msg = '';
$success_msg = '';

// Get ID dari parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: master_kategori_item.php');
    exit();
}

// Get data yang akan diedit
$query = "SELECT * FROM tbmaster_kategori_item WHERE id = '$id' AND status = '1'";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Data kategori item tidak ditemukan!";
    header('Location: master_kategori_item.php');
    exit();
}

$data = mysqli_fetch_assoc($result);

// Process form submission
if ($_POST) {
    $kategori_item = strtoupper(trim($_POST['kategori_item']));
    $keterangan = trim($_POST['keterangan']);
    $margin_sesuai_jenis = $_POST['margin_sesuai_jenis'];
    $margin_kategori = ($margin_sesuai_jenis == 'TIDAK' && !empty($_POST['margin_kategori'])) ?
                       (float)$_POST['margin_kategori'] : null;

    // Validasi input
    if (empty($kategori_item)) {
        $error_msg = "Kategori Item harus diisi!";
    } elseif (strpos($kategori_item, ' ') !== false) {
        $error_msg = "Kategori Item hanya boleh satu kata, tidak boleh ada spasi!";
    } elseif (empty($keterangan)) {
        $error_msg = "Keterangan harus diisi!";
    } elseif (!in_array($margin_sesuai_jenis, ['YA', 'TIDAK'])) {
        $error_msg = "Margin Sesuai Jenis harus dipilih (YA/TIDAK)!";
    } elseif ($margin_sesuai_jenis == 'TIDAK' && (empty($margin_kategori) || $margin_kategori <= 0)) {
        $error_msg = "Margin Kategori harus diisi dengan angka positif jika 'Margin Sesuai Jenis' = TIDAK!";
    } else {
        // Cek duplikat kategori item (kecuali data yang sedang diedit)
        $check_query = "SELECT id FROM tbmaster_kategori_item
                       WHERE kategori_item = '$kategori_item' AND status = '1' AND id != '$id'";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Kategori item '$kategori_item' sudah ada! Silakan gunakan nama yang berbeda.";
        } else {
            // Update data
            $margin_val = $margin_kategori ? "'$margin_kategori'" : 'NULL';
            $update_query = "UPDATE tbmaster_kategori_item SET
                           kategori_item = '$kategori_item',
                           keterangan = '$keterangan',
                           margin_sesuai_jenis = '$margin_sesuai_jenis',
                           margin_kategori = $margin_val,
                           updated_at = NOW()
                           WHERE id = '$id'";

            if (mysqli_query($koneksi, $update_query)) {
                $_SESSION['success'] = "Data berhasil diupdate!";
                header('Location: master_kategori_item.php');
                exit();
            } else {
                $error_msg = "Gagal mengupdate data: " . mysqli_error($koneksi);
            }
        }
    }
} else {
    // Set initial values dari database
    $kategori_item = $data['kategori_item'];
    $keterangan = $data['keterangan'];
    $margin_sesuai_jenis = $data['margin_sesuai_jenis'];
    $margin_kategori = $data['margin_kategori'];
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
        .margin-section {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        .margin-inactive {
            opacity: 0.5;
            pointer-events: none;
        }
        .original-data {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            border-left: 4px solid #5bc0de;
        }
        .original-data h5 {
            margin-top: 0;
            color: #31708f;
        }
        .data-compare {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        .data-compare .col {
            padding: 10px;
            border-radius: 3px;
        }
        .original-col {
            background-color: #d9edf7;
            border: 1px solid #bce8f1;
        }
        .edit-col {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
        }
    </style>
</head>

<body class="no-skin">
    <div class="main-content">
        <div class="main-content-inner">
            <div class="page-content">
                <div class="row">
                    <div class="col-md-10 col-md-offset-1">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h1>
                                <?php echo $page_title; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Update Data Kategori Item
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

                            <!-- Data Asli -->
                            <div class="original-data">
                                <h5><i class="fa fa-info-circle"></i> Data Asli</h5>
                                <div class="data-compare">
                                    <div class="col original-col">
                                        <strong>Kategori Item:</strong> <?php echo htmlspecialchars($data['kategori_item']); ?><br>
                                        <strong>Keterangan:</strong> <?php echo htmlspecialchars($data['keterangan']); ?><br>
                                        <strong>Margin Sesuai Jenis:</strong>
                                        <span class="label <?php echo $data['margin_sesuai_jenis'] == 'YA' ? 'label-success' : 'label-warning'; ?>">
                                            <?php echo $data['margin_sesuai_jenis']; ?>
                                        </span><br>
                                        <strong>Margin Kategori:</strong>
                                        <?php echo $data['margin_kategori'] ? $data['margin_kategori'] . '%' : '-'; ?>
                                    </div>
                                    <div class="col edit-col">
                                        <strong>Status:</strong> <span class="text-info">Sedang diedit...</span><br>
                                        <small class="text-muted">Data akan berubah setelah tombol "Update" diklik</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Form -->
                            <form method="POST" action="" class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Kategori Item <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-4">
                                        <input type="text" name="kategori_item" class="form-control"
                                               value="<?php echo isset($kategori_item) ? htmlspecialchars($kategori_item) : ''; ?>"
                                               placeholder="Contoh: BAUD"
                                               maxlength="50"
                                               style="text-transform: uppercase;"
                                               autocomplete="off" required>
                                        <div class="help-text">
                                            Hanya boleh 1 kata, tidak boleh ada spasi
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Keterangan <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-6">
                                        <input type="text" name="keterangan" class="form-control"
                                               value="<?php echo isset($keterangan) ? htmlspecialchars($keterangan) : ''; ?>"
                                               placeholder="Contoh: BAUD, MUR, RING"
                                               maxlength="255"
                                               style="text-transform: uppercase;"
                                               autocomplete="off" required>
                                        <div class="help-text">
                                            Keterangan diisi bebas sesuai arti
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Margin Sesuai Jenis? <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-3">
                                        <select name="margin_sesuai_jenis" class="form-control" required>
                                            <option value="YA" <?php echo (isset($margin_sesuai_jenis) && $margin_sesuai_jenis == 'YA') ? 'selected' : ''; ?>>YA</option>
                                            <option value="TIDAK" <?php echo (isset($margin_sesuai_jenis) && $margin_sesuai_jenis == 'TIDAK') ? 'selected' : ''; ?>>TIDAK</option>
                                        </select>
                                        <div class="help-text">
                                            Hanya bisa diisi YA/TIDAK
                                        </div>
                                    </div>
                                    <div class="col-sm-1 text-center" style="margin-top: 7px;">
                                        <button type="button" class="btn btn-xs btn-info" data-toggle="tooltip"
                                                title="YA = Margin mengikuti jenis item&#10;TIDAK = Gunakan margin kategori sendiri">
                                            <i class="fa fa-question"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Margin Kategori
                                    </label>
                                    <div class="col-sm-3">
                                        <div class="input-group">
                                            <input type="number" name="margin_kategori" class="form-control"
                                                   value="<?php echo isset($margin_kategori) ? $margin_kategori : ''; ?>"
                                                   placeholder="40"
                                                   min="0" max="999" step="0.01"
                                                   id="margin_kategori_input">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                        <div class="help-text">
                                            Kolom margin kategori aktif jika jawaban sebelumnya "TIDAK", hanya bisa diisi angka.
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update
                                        </button>

                                        <a href="master_kategori_item.php" class="btn btn-success">
                                            <i class="fa fa-list"></i> Lihat Daftar Kategori Item
                                        </a>

                                        <a href="menu_master01h.php" class="btn btn-default">
                                            <i class="fa fa-home"></i> Ke Menu Awal
                                        </a>
                                    </div>
                                </div>
                            </form>

                            <!-- Info -->
                            <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f0ad4e;">
                                <h5><strong>Perhatian saat Edit:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Pastikan tidak ada duplikat kategori item</li>
                                    <li>• Kategori Item tetap harus satu kata tanpa spasi</li>
                                    <li>• Jika mengubah "Margin Sesuai Jenis" dari YA ke TIDAK, wajib isi margin kategori</li>
                                    <li>• Jika mengubah dari TIDAK ke YA, margin kategori akan dihapus otomatis</li>
                                    <li>• Pastikan data sudah benar sebelum klik "Update"</li>
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
    <script src="assets/js/ace.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize tooltip
            $('[data-toggle="tooltip"]').tooltip({
                html: true,
                placement: 'top'
            });

            // Auto focus pada kategori item
            $('input[name="kategori_item"]').focus();

            // Handle margin sesuai jenis change
            function toggleMarginKategori() {
                var marginSesuaiJenis = $('select[name="margin_sesuai_jenis"]').val();
                var marginInput = $('#margin_kategori_input');

                if (marginSesuaiJenis === 'TIDAK') {
                    marginInput.prop('disabled', false).prop('required', true);
                    marginInput.closest('.form-group').removeClass('margin-inactive');
                } else {
                    marginInput.prop('disabled', true).prop('required', false).val('');
                    marginInput.closest('.form-group').addClass('margin-inactive');
                }
            }

            // Initial toggle
            toggleMarginKategori();

            // On change event
            $('select[name="margin_sesuai_jenis"]').on('change', toggleMarginKategori);

            // Remove spaces from kategori item input
            $('input[name="kategori_item"]').on('input', function() {
                var val = this.value.toUpperCase().replace(/\s+/g, '');
                this.value = val;
            });

            // Validasi form sebelum submit
            $('form').on('submit', function(e) {
                var kategoriItem = $('input[name="kategori_item"]').val().trim();
                var keterangan = $('input[name="keterangan"]').val().trim();
                var marginSesuaiJenis = $('select[name="margin_sesuai_jenis"]').val();
                var marginKategori = $('#margin_kategori_input').val();

                if (!kategoriItem) {
                    alert('Kategori Item harus diisi!');
                    e.preventDefault();
                    return false;
                }

                if (kategoriItem.indexOf(' ') !== -1) {
                    alert('Kategori Item tidak boleh mengandung spasi!');
                    e.preventDefault();
                    return false;
                }

                if (!keterangan) {
                    alert('Keterangan harus diisi!');
                    e.preventDefault();
                    return false;
                }

                if (marginSesuaiJenis === 'TIDAK' && (!marginKategori || parseFloat(marginKategori) <= 0)) {
                    alert('Margin Kategori harus diisi dengan angka positif jika pilih "TIDAK"!');
                    e.preventDefault();
                    return false;
                }

                // Konfirmasi sebelum update
                return confirm('Yakin ingin mengupdate data kategori item ini?');
            });
        });
    </script>
</body>
</html>