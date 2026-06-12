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

$page_title = "Hapus Kategori Item";
$error_msg = '';
$success_msg = '';

// Get ID dari parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: master_kategori_item.php');
    exit();
}

// Get data yang akan dihapus
$query = "SELECT * FROM tbmaster_kategori_item WHERE id = '$id' AND status = '1'";
$result = mysqli_query($koneksi, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Data kategori item tidak ditemukan!";
    header('Location: master_kategori_item.php');
    exit();
}

$data = mysqli_fetch_assoc($result);

// Process delete confirmation
if (isset($_POST['confirm_delete'])) {
    // Cek apakah kategori item sudah digunakan di tabel lain
    $check_usage = false;
    $usage_info = [];

    // Cek di tabel barang (jika ada)
    $check_barang = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbbarang WHERE kategori_item = '{$data['kategori_item']}'");
    if ($check_barang) {
        $count_barang = mysqli_fetch_assoc($check_barang);
        if ($count_barang['count'] > 0) {
            $check_usage = true;
            $usage_info[] = "Digunakan di " . $count_barang['count'] . " item barang";
        }
    }

    // Cek di tabel penjualan detail (jika ada struktur yang menggunakan kategori)
    $check_penjualan = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbpenjualan_det p
                                              LEFT JOIN tbbarang b ON p.kd_barang = b.kd_barang
                                              WHERE b.kategori_item = '{$data['kategori_item']}'");
    if ($check_penjualan) {
        $count_penjualan = mysqli_fetch_assoc($check_penjualan);
        if ($count_penjualan['count'] > 0) {
            $check_usage = true;
            $usage_info[] = "Sudah ada " . $count_penjualan['count'] . " transaksi penjualan";
        }
    }

    // Cek di tabel pembelian detail (jika ada)
    $check_pembelian = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbpembelian_det p
                                               LEFT JOIN tbbarang b ON p.kd_barang = b.kd_barang
                                               WHERE b.kategori_item = '{$data['kategori_item']}'");
    if ($check_pembelian) {
        $count_pembelian = mysqli_fetch_assoc($check_pembelian);
        if ($count_pembelian['count'] > 0) {
            $check_usage = true;
            $usage_info[] = "Sudah ada " . $count_pembelian['count'] . " transaksi pembelian";
        }
    }

    if ($check_usage) {
        $error_msg = "Kategori item tidak bisa dihapus karena sudah digunakan:<br>" .
                    "• " . implode("<br>• ", $usage_info) . "<br><br>" .
                    "Silakan hapus data terkait terlebih dahulu atau nonaktifkan kategori item ini.";
    } else {
        // Safe to delete - soft delete
        $delete_query = "UPDATE tbmaster_kategori_item SET status = '0', updated_at = NOW() WHERE id = '$id'";

        if (mysqli_query($koneksi, $delete_query)) {
            $_SESSION['delete_success'] = "Kategori item '{$data['kategori_item']}' berhasil dihapus!";
            header('Location: master_kategori_item.php');
            exit();
        } else {
            $error_msg = "Gagal menghapus data: " . mysqli_error($koneksi);
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
        .delete-container {
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
        .data-to-delete {
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #d9534f;
        }
        .data-to-delete h5 {
            margin-top: 0;
            color: #a94442;
        }
        .warning-box {
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #f0ad4e;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        .info-value {
            width: 60%;
            text-align: right;
        }
        .margin-badge {
            font-size: 11px;
            padding: 2px 6px;
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
                                <i class="fa fa-trash-o text-danger"></i>
                                <?php echo $page_title; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Konfirmasi Penghapusan Data
                                </small>
                            </h1>
                        </div>

                        <div class="delete-container">
                            <!-- Alert Messages -->
                            <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert">
                                    <i class="ace-icon fa fa-times"></i>
                                </button>
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Tidak Bisa Dihapus!</strong><br>
                                <?php echo $error_msg; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Data yang akan dihapus -->
                            <div class="data-to-delete">
                                <h5><i class="fa fa-warning"></i> Data yang akan dihapus:</h5>

                                <div class="info-item">
                                    <span class="info-label">Kategori Item:</span>
                                    <span class="info-value">
                                        <strong><?php echo htmlspecialchars($data['kategori_item']); ?></strong>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Keterangan:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($data['keterangan']); ?></span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Margin Sesuai Jenis:</span>
                                    <span class="info-value">
                                        <span class="label <?php echo $data['margin_sesuai_jenis'] == 'YA' ? 'label-success' : 'label-warning'; ?> margin-badge">
                                            <?php echo $data['margin_sesuai_jenis']; ?>
                                        </span>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Margin Kategori:</span>
                                    <span class="info-value">
                                        <strong>
                                        <?php echo $data['margin_kategori'] ? number_format($data['margin_kategori'], 0) . '%' : '-'; ?>
                                        </strong>
                                    </span>
                                </div>

                                <div class="info-item">
                                    <span class="info-label">Dibuat:</span>
                                    <span class="info-value">
                                        <?php echo date('d-m-Y H:i', strtotime($data['created_at'])); ?>
                                    </span>
                                </div>

                                <?php if ($data['updated_at']): ?>
                                <div class="info-item">
                                    <span class="info-label">Terakhir Update:</span>
                                    <span class="info-value">
                                        <?php echo date('d-m-Y H:i', strtotime($data['updated_at'])); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Warning -->
                            <div class="warning-box">
                                <h5><i class="fa fa-exclamation-triangle text-warning"></i> Peringatan:</h5>
                                <ul class="list-unstyled" style="margin-left: 20px; margin-bottom: 0;">
                                    <li>• Kategori item akan dihapus secara permanen dari sistem</li>
                                    <li>• Kategori item yang sudah digunakan di transaksi tidak bisa dihapus</li>
                                    <li>• Pastikan tidak ada barang yang menggunakan kategori ini</li>
                                    <li>• Tindakan ini tidak dapat dibatalkan</li>
                                </ul>
                            </div>

                            <?php if (empty($error_msg)): ?>
                            <!-- Form Konfirmasi -->
                            <form method="POST" action="">
                                <div class="form-actions">
                                    <div class="text-center">
                                        <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg"
                                                onclick="return confirm('YAKIN ingin menghapus kategori item ini?\\n\\nTindakan ini tidak dapat dibatalkan!')">
                                            <i class="fa fa-trash-o"></i> Ya, Hapus Kategori Item
                                        </button>

                                        <a href="master_kategori_item.php" class="btn btn-default btn-lg">
                                            <i class="fa fa-arrow-left"></i> Batal
                                        </a>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                            <!-- Jika tidak bisa dihapus -->
                            <div class="form-actions">
                                <div class="text-center">
                                    <a href="master_kategori_item_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                                        <i class="fa fa-edit"></i> Edit Kategori Item
                                    </a>

                                    <a href="master_kategori_item.php" class="btn btn-primary">
                                        <i class="fa fa-list"></i> Kembali ke Daftar
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Info -->
                            <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 4px; border-left: 4px solid #5bc0de;">
                                <h5><strong>Alternatif Penghapusan:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Jika kategori item sudah digunakan, pertimbangkan untuk menonaktifkan saja</li>
                                    <li>• Pastikan semua barang dengan kategori ini sudah dipindah ke kategori lain</li>
                                    <li>• Hubungi administrator sistem jika ada masalah</li>
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
            // Auto focus pada tombol batal untuk safety
            $('.btn-default').focus();

            // Double confirmation untuk delete
            $('button[name="confirm_delete"]').on('click', function(e) {
                e.preventDefault();

                var kategoriItem = '<?php echo addslashes($data['kategori_item']); ?>';

                if (confirm('YAKIN ingin menghapus kategori item "' + kategoriItem + '"?\\n\\nTindakan ini tidak dapat dibatalkan!')) {
                    if (confirm('KONFIRMASI TERAKHIR:\\n\\nApakah Anda benar-benar yakin ingin menghapus kategori item ini?\\n\\nKlik OK untuk melanjutkan penghapusan.')) {
                        $(this).closest('form').submit();
                    }
                }

                return false;
            });
        });
    </script>
</body>
</html>