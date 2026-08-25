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

$page_title = "Input Kategori Item Baru";
$error_msg = '';
$success_msg = '';

// Process form submission
if ($_POST) {
    $kategori_item = strtoupper(trim($_POST['kategori_item']));
    $keterangan = trim($_POST['keterangan']);
    $margin_sesuai_jenis = mysqli_real_escape_string($koneksi, $_POST['margin_sesuai_jenis']);
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
        // Cek duplikat kategori item
        $check_query = "SELECT id FROM tbmaster_kategori_item WHERE kategori_item = '$kategori_item' AND status = '1'";
        $check_result = mysqli_query($koneksi, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error_msg = "Kategori item '$kategori_item' sudah ada! Silakan gunakan nama yang berbeda.";
        } else {
            // Insert data
            $margin_val = $margin_kategori ? "'$margin_kategori'" : 'NULL';
            $insert_query = "INSERT INTO tbmaster_kategori_item
                           (kategori_item, keterangan, margin_sesuai_jenis, margin_kategori, status, created_at)
                           VALUES ('$kategori_item', '$keterangan', '$margin_sesuai_jenis', $margin_val, '1', NOW())";

            if (mysqli_query($koneksi, $insert_query)) {
                $success_msg = "Data berhasil tersimpan!";
                // Clear form
                $kategori_item = '';
                $keterangan = '';
                $margin_sesuai_jenis = 'YA';
                $margin_kategori = '';
            } else {
                $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
            }
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
                                    Tambah Data Kategori Item
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
                                        KETERANGAN <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-6">
                                        <input type="text" name="keterangan" class="form-control"
                                               value="<?php echo isset($keterangan) ? strtoupper(htmlspecialchars($keterangan)) : ''; ?>"
                                               placeholder="Masukkan keterangan kategori item..."
                                               maxlength="255"
                                               style="text-transform: uppercase;"
                                               autocomplete="off" required>
                                        <div class="help-text">
                                            Masukkan keterangan terlebih dahulu, kategori item akan otomatis dibuat
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-sm-3 control-label">
                                        Kategori Item <span class="required">*</span>
                                    </label>
                                    <div class="col-sm-4">
                                        <input type="text" name="kategori_item" class="form-control"
                                               value="<?php echo isset($kategori_item) ? htmlspecialchars($kategori_item) : ''; ?>"
                                               placeholder="Otomatis dibuat dari keterangan"
                                               maxlength="50"
                                               style="text-transform: uppercase;"
                                               autocomplete="off" required>
                                        <div class="help-text">
                                            <strong>Otomatis dibuat dari keterangan</strong> - dapat diedit (1 kata, tanpa spasi)
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
                                            <i class="fa fa-save"></i> Simpan
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
                            <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #5bc0de;">
                                <h5><strong>Petunjuk Pengisian:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• <strong>KETERANGAN diisi terlebih dahulu</strong> - akan otomatis dalam huruf besar</li>
                                    <li>• <strong>Kategori Item otomatis dibuat</strong> dari kata pertama keterangan</li>
                                    <li>• Kategori Item dapat diedit (1 kata, tanpa spasi)</li>
                                    <li>• <strong>Margin Sesuai Jenis = YA:</strong> Margin mengikuti master jenis item</li>
                                    <li>• <strong>Margin Sesuai Jenis = TIDAK:</strong> Gunakan margin kategori yang diinput</li>
                                    <li>• Field margin kategori hanya aktif jika pilih "TIDAK"</li>
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

            // Auto focus pada keterangan (field pertama)
            $('input[name="keterangan"]').focus();

            // Auto-generate kategori item dari keterangan
            function generateKategoriItem(keterangan) {
                if (!keterangan) return '';
                
                // Ambil kata pertama dari keterangan
                var words = keterangan.trim().toUpperCase().split(/\s+/);
                var kategori = words[0] || '';
                
                // Hapus karakter non-alphanumeric dan batasi panjang
                kategori = kategori.replace(/[^A-Z0-9]/g, '').substring(0, 20);
                
                return kategori;
            }

            // Auto-generate kategori item saat keterangan diubah
            $('input[name="keterangan"]').on('input', function() {
                // Convert keterangan ke uppercase
                var keterangan = this.value.toUpperCase();
                this.value = keterangan;
                
                // Generate kategori item otomatis
                var generatedKategori = generateKategoriItem(keterangan);
                $('input[name="kategori_item"]').val(generatedKategori);
            });

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
            });
        });
    </script>
</body>
</html>