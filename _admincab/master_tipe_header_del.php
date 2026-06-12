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

$page_title = "Hapus Tipe Header Motor";
$error_msg = '';

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

// Process deletion
if ($_POST && isset($_POST['confirm_delete'])) {
    // Cek apakah tipe header sudah digunakan dalam transaksi
    $check_usage = false;

    // Cek di master tipe detail
    $check1 = mysqli_query($koneksi, "SELECT id FROM tbmaster_tipe_detail WHERE id_tipe_header = $id AND status = '1' LIMIT 1");
    if ($check1 && mysqli_num_rows($check1) > 0) $check_usage = true;

    // Cek di tipe motor (jika menggunakan tipe header)
    $check2 = mysqli_query($koneksi, "SELECT kode_tipe FROM tbtipe_motor WHERE kode_tipe LIKE '{$data['nama_model']}%' LIMIT 1");
    if ($check2 && mysqli_num_rows($check2) > 0) $check_usage = true;

    // Cek di kendaraan yang mungkin menggunakan tipe header ini
    $check3 = mysqli_query($koneksi, "SELECT id FROM tbkendaraan WHERE tipe LIKE '%{$data['nama_model']}%' LIMIT 1");
    if ($check3 && mysqli_num_rows($check3) > 0) $check_usage = true;

    if ($check_usage) {
        $error_msg = "Data tidak dapat dihapus karena masih digunakan dalam transaksi atau master data lainnya!";
    } else {
        // Soft delete - update status menjadi '0'
        $delete_query = "UPDATE tbmaster_tipe_header SET status = '0', updated_at = NOW() WHERE id = $id";

        if (mysqli_query($koneksi, $delete_query)) {
            $_SESSION['delete_success'] = "Data tipe header motor berhasil dihapus!";
            header('Location: master_tipe_header.php');
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
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .data-hapus {
            background-color: #f2dede;
            border-left: 4px solid #d43f3a;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .warning-box {
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
            color: #8a6d3b;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
                                    Konfirmasi Penghapusan Data
                                </small>
                            </h1>
                        </div>

                        <div class="form-container">
                            <!-- Alert Messages -->
                            <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Error!</strong> <?php echo $error_msg; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Warning -->
                            <div class="warning-box">
                                <i class="fa fa-warning"></i>
                                <strong>Perhatian!</strong>
                                Anda akan menghapus data tipe header motor berikut.
                                Pastikan data ini tidak sedang digunakan dalam transaksi atau master data lainnya.
                            </div>

                            <!-- Data yang akan dihapus -->
                            <div class="data-hapus">
                                <h5><strong>Data Yang Akan Dihapus</strong></h5>
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

                            <!-- Konfirmasi -->
                            <div class="text-center">
                                <h4 style="color: #d43f3a; margin-bottom: 30px;">
                                    <i class="fa fa-question-circle"></i>
                                    Yakin ingin menghapus data ini?
                                </h4>

                                <form method="POST" action="" style="display: inline;">
                                    <button type="submit" name="confirm_delete" class="btn btn-danger btn-lg">
                                        <i class="fa fa-trash-o"></i> Ya, Hapus Data
                                    </button>
                                </form>

                                <a href="master_tipe_header.php" class="btn btn-success btn-lg" style="margin-left: 15px;">
                                    <i class="fa fa-list"></i> Lihat Daftar Tipe Header
                                </a>

                                <a href="menu_master01h.php" class="btn btn-default btn-lg" style="margin-left: 15px;">
                                    <i class="fa fa-home"></i> Ke Menu Awal
                                </a>
                            </div>

                            <!-- Info -->
                            <div style="margin-top: 40px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #f39c12;">
                                <h5><strong>Catatan Penghapusan:</strong></h5>
                                <ul class="list-unstyled" style="margin-left: 15px; margin-bottom: 0;">
                                    <li>• Tipe header bisa dihapus jika belum ada transaksi</li>
                                    <li>• Sistem akan mengecek penggunaan di master tipe detail, tipe motor, dan kendaraan</li>
                                    <li>• Data yang dihapus tidak benar-benar hilang, hanya dinonaktifkan</li>
                                    <li>• Setelah hapus berhasil otomatis masuk ke halaman daftar tipe header motor</li>
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
            // Konfirmasi sebelum hapus
            $('button[name="confirm_delete"]').on('click', function(e) {
                if (!confirm('PERHATIAN!\n\nApakah Anda benar-benar yakin ingin menghapus tipe header motor ini?\n\nData yang sudah dihapus tidak dapat dikembalikan dan akan mempengaruhi semua tipe detail yang menggunakannya!')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>