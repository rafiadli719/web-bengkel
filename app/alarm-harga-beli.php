<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];
    include "../config/koneksi.php";

    $stmt=mysqli_prepare($koneksi,"SELECT nama_user FROM tbuser WHERE id=?");
    mysqli_stmt_bind_param($stmt,"s",$id_user);
    mysqli_stmt_execute($stmt);
    $tm_cari=mysqli_fetch_array(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $_nama=$tm_cari['nama_user'];

    // --- Handler aksi review ---
    if(isset($_POST['btnaksi'])){
        $id = (int)$_POST['id'];
        $aksi = mysqli_real_escape_string($koneksi, $_POST['aksi']); // 'harga_disesuaikan' atau 'diabaikan'
        if(in_array($aksi, ['harga_disesuaikan','diabaikan'], true)){
            $stmt = mysqli_prepare($koneksi, "UPDATE alarm_harga_beli SET status_review=?, direview_oleh=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "ssi", $aksi, $_nama, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("location:alarm-harga-beli.php?updated=1");
        exit;
    }

    $filter = isset($_GET['filter']) && $_GET['filter']==='semua' ? 'semua' : 'pending';

    if($filter === 'pending'){
        $list = mysqli_query($koneksi, "SELECT a.*, i.namaitem
                                        FROM alarm_harga_beli a
                                        LEFT JOIN tblitem i ON i.noitem = a.no_item
                                        WHERE a.status_review='belum_direview'
                                        ORDER BY a.created_at DESC");
    } else {
        $list = mysqli_query($koneksi, "SELECT a.*, i.namaitem
                                        FROM alarm_harga_beli a
                                        LEFT JOIN tblitem i ON i.noitem = a.no_item
                                        ORDER BY a.created_at DESC LIMIT 200");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />
    <link rel="stylesheet" href="assets/css/ace-skins.min.css" />
    <link rel="stylesheet" href="assets/css/ace-rtl.min.css" />
    <script src="assets/js/ace-extra.min.js"></script>
</head>
<body class="no-skin">
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
            </button>
            <div class="navbar-header pull-left">
                <a href="index.php" class="navbar-brand"><small><?php include "../lib/subtitel.php"; ?></small></a>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script type="text/javascript">try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script type="text/javascript">try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Pembelian</a></li>
                        <li class="active">Alarm Harga Beli</li>
                    </ul>
                </div>

                <div class="page-content">
                    <h4 class="header blue"><i class="fa fa-bell"></i> Alarm Harga Beli</h4>
                    <small><i class="ace-icon fa fa-angle-double-right"></i> Perubahan harga beli signifikan (naik/turun) yang perlu direview</small>

                    <?php if(isset($_GET['updated'])): ?>
                        <div class="alert alert-success">Status alarm berhasil diupdate.</div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-xs-12">
                            <a href="alarm-harga-beli.php?filter=pending" class="btn btn-sm <?php echo $filter==='pending'?'btn-primary':'btn-default'; ?>">Belum Direview</a>
                            <a href="alarm-harga-beli.php?filter=semua" class="btn btn-sm <?php echo $filter==='semua'?'btn-primary':'btn-default'; ?>">Semua (200 terakhir)</a>
                            <a href="setting-threshold-harga.php" class="btn btn-sm btn-default pull-right"><i class="fa fa-cog"></i> Setting Threshold</a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Item</th>
                                                    <th>No. Transaksi</th>
                                                    <th>Harga Lama</th>
                                                    <th>Harga Baru</th>
                                                    <th>Selisih %</th>
                                                    <th>Arah</th>
                                                    <th>Threshold Saat Itu</th>
                                                    <th>Harga Jual Saat Ini</th>
                                                    <th>Klasifikasi</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(mysqli_num_rows($list)===0): ?>
                                                <tr><td colspan="12" class="text-center">Tidak ada alarm.</td></tr>
                                                <?php endif; ?>
                                                <?php while($row = mysqli_fetch_assoc($list)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                    <td><?php echo htmlspecialchars(($row['namaitem'] ?? $row['no_item'])); ?></td>
                                                    <td><?php echo htmlspecialchars($row['no_transaksi_pembelian']); ?></td>
                                                    <td align="right"><?php echo number_format($row['harga_beli_lama'],0); ?></td>
                                                    <td align="right"><?php echo number_format($row['harga_beli_baru'],0); ?></td>
                                                    <td align="right"><?php echo number_format($row['persen_selisih'],1); ?>%</td>
                                                    <td><?php echo $row['arah']==='naik' ? '<span class="label label-danger">Naik</span>' : '<span class="label label-warning">Turun</span>'; ?></td>
                                                    <td align="right"><?php echo number_format($row['threshold_saat_itu'],1); ?>%</td>
                                                    <td align="right"><?php echo $row['harga_jual_saat_ini']!==null ? number_format($row['harga_jual_saat_ini'],0) : '-'; ?></td>
                                                    <td><?php echo htmlspecialchars($row['status_klasifikasi'] ?? '-'); ?></td>
                                                    <td>
                                                        <?php
                                                        $badge = ['belum_direview'=>'default','direview'=>'info','harga_disesuaikan'=>'success','diabaikan'=>'default'];
                                                        $lbl = $badge[$row['status_review']] ?? 'default';
                                                        ?>
                                                        <span class="label label-<?php echo $lbl; ?>"><?php echo htmlspecialchars($row['status_review']); ?></span>
                                                        <?php if($row['direview_oleh']): ?><br><small>oleh <?php echo htmlspecialchars($row['direview_oleh']); ?></small><?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($row['status_review']==='belum_direview'): ?>
                                                        <form method="POST" action="alarm-harga-beli.php?filter=<?php echo $filter; ?>" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                            <input type="hidden" name="aksi" value="harga_disesuaikan">
                                                            <button type="submit" name="btnaksi" class="btn btn-xs btn-success" onclick="return confirm('Tandai harga jual sudah disesuaikan?');">Sudah Disesuaikan</button>
                                                        </form>
                                                        <form method="POST" action="alarm-harga-beli.php?filter=<?php echo $filter; ?>" style="display:inline;">
                                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                            <input type="hidden" name="aksi" value="diabaikan">
                                                            <button type="submit" name="btnaksi" class="btn btn-xs btn-default" onclick="return confirm('Abaikan alarm ini?');">Abaikan</button>
                                                        </form>
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>
<?php
}
?>
