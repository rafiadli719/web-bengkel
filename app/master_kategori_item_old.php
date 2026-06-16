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
$is_read_only = !$is_admin_pengadaan;

$page_title = "Master Kategori Item";

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = "WHERE (kategori_item LIKE '%$search%' OR keterangan LIKE '%$search%') AND status = '1'";
} else {
    $search_condition = "WHERE status = '1'";
}

// Get data kategori item
$query = "SELECT * FROM tbmaster_kategori_item $search_condition ORDER BY kategori_item ASC";
$result = mysqli_query($koneksi, $query);

// Handle success message from other pages
$success_msg = '';
if (isset($_SESSION['delete_success'])) {
    $success_msg = $_SESSION['delete_success'];
    unset($_SESSION['delete_success']);
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
        .search-box {
            margin-bottom: 20px;
        }
        .action-buttons {
            text-align: right;
            margin-bottom: 15px;
        }
        .table-responsive {
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .alert-info-custom {
            background-color: #d9edf7;
            border-color: #bce8f1;
            color: #31708f;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
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
                    <div class="col-xs-12">
                        <!-- Page Header -->
                        <div class="page-header">
                            <h1>
                                <?php echo $page_title; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Kelola Data Kategori Item
                                </small>
                            </h1>
                        </div>

                        <?php if ($success_msg): ?>
                        <div class="alert alert-success">
                            <button type="button" class="close" data-dismiss="alert">
                                <i class="ace-icon fa fa-times"></i>
                            </button>
                            <i class="fa fa-check"></i> <?php echo $success_msg; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_read_only): ?>
                        <div class="alert-info-custom">
                            <i class="fa fa-info-circle"></i>
                            <strong>Info:</strong> Anda hanya memiliki akses read-only untuk halaman ini.
                            Master Kategori Item hanya bisa diakses penuh oleh Admin Pengadaan.
                        </div>
                        <?php endif; ?>

                        <!-- Search Box -->
                        <div class="row search-box">
                            <div class="col-md-6">
                                <form method="GET" action="">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control"
                                               placeholder="Cari kategori item atau keterangan..."
                                               value="<?php echo htmlspecialchars($search); ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fa fa-search"></i> Cari
                                            </button>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        Sistem akan mencari yang memuat kata tersebut
                                    </small>
                                </form>
                            </div>
                            <div class="col-md-6 action-buttons">
                                <?php if (!$is_read_only): ?>
                                <a href="master_kategori_item_add.php" class="btn btn-success">
                                    <i class="fa fa-plus"></i> Input Baru
                                </a>
                                <?php endif; ?>
                                <a href="menu_master01h.php" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> Ke Menu Awal
                                </a>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr class="info">
                                        <th width="8%" class="text-center">No</th>
                                        <th width="20%">Kategori Item</th>
                                        <th width="30%">Keterangan</th>
                                        <th width="15%" class="text-center">Margin Sesuai Jenis?</th>
                                        <th width="12%" class="text-center">Margin Kategori</th>
                                        <?php if (!$is_read_only): ?>
                                        <th width="15%" class="text-center">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['kategori_item']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                        <td class="text-center">
                                            <?php if ($row['margin_sesuai_jenis'] == 'YA'): ?>
                                                <span class="label label-success margin-badge">YA</span>
                                            <?php else: ?>
                                                <span class="label label-warning margin-badge">TIDAK</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['margin_sesuai_jenis'] == 'TIDAK' && $row['margin_kategori']): ?>
                                                <strong><?php echo number_format($row['margin_kategori'], 0); ?>%</strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if (!$is_read_only): ?>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a href="master_kategori_item_edit.php?id=<?php echo $row['id']; ?>"
                                                   class="btn btn-xs btn-info" title="Edit">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <a href="master_kategori_item_del.php?id=<?php echo $row['id']; ?>"
                                                   class="btn btn-xs btn-danger" title="Hapus"
                                                   onclick="return confirm('Yakin ingin menghapus kategori item ini?\\n\\nKategori item bisa dihapus jika belum ada transaksi.')">
                                                    <i class="fa fa-trash-o"></i> Hapus
                                                </a>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php
                                        }
                                    } else {
                                    ?>
                                    <tr>
                                        <td colspan="<?php echo $is_read_only ? '5' : '6'; ?>" class="text-center">
                                            <em>
                                                <?php if (!empty($search)): ?>
                                                    Tidak ada data kategori item yang sesuai dengan pencarian "<?php echo htmlspecialchars($search); ?>"
                                                <?php else: ?>
                                                    Belum ada data kategori item
                                                <?php endif; ?>
                                            </em>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($search)): ?>
                        <div class="text-center" style="margin-top: 15px;">
                            <a href="master_kategori_item.php" class="btn btn-default">
                                <i class="fa fa-list"></i> Lihat Semua Data
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Info Footer -->
                        <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 4px;">
                            <h5><strong>Ketentuan Master Kategori Item:</strong></h5>
                            <ul class="list-unstyled" style="margin-left: 20px;">
                                <li>• Kategori Item hanya boleh 1 kata, tidak boleh ada spasi</li>
                                <li>• Keterangan diisi bebas sesuai arti kategori</li>
                                <li>• <strong>Margin Sesuai Jenis:</strong> Hanya bisa diisi YA/TIDAK</li>
                                <li>&nbsp;&nbsp;- Jika <strong>YA</strong>: Margin mengikuti master jenis item</li>
                                <li>&nbsp;&nbsp;- Jika <strong>TIDAK</strong>: Gunakan margin kategori sendiri</li>
                                <li>• Kolom margin kategori aktif jika "Margin Sesuai Jenis" = TIDAK</li>
                                <li>• Master ini hanya bisa diakses penuh oleh Admin Pengadaan</li>
                            </ul>
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
            // Auto focus pada search box jika kosong
            <?php if (empty($search)): ?>
            $('input[name="search"]').focus();
            <?php endif; ?>

            // Highlight search text
            <?php if (!empty($search)): ?>
            var searchText = '<?php echo addslashes($search); ?>';
            $('tbody td').each(function() {
                var text = $(this).html();
                var regex = new RegExp('(' + searchText + ')', 'gi');
                var newText = text.replace(regex, '<mark>$1</mark>');
                $(this).html(newText);
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>