<?php
    session_start();
    if(empty($_SESSION['_iduser'])){
        header("location:../index.php");
    } else {
        $id_user   = $_SESSION['_iduser'];
        $kd_cabang = $_SESSION['_cabang'];
        include "../config/koneksi.php";

        $cari_kd = mysqli_query($koneksi,"SELECT nama_user, user_akses, foto_user FROM tbuser WHERE id='$id_user'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $_nama    = $tm_cari['nama_user'];
        $foto_user= $tm_cari['foto_user'];
        if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

        $cari_kd = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $nama_cabang = $tm_cari['nama_cabang'];

        date_default_timezone_set('Asia/Jakarta');
        function ubahTgl_KS($tgl) {
            $p = explode('/',$tgl);
            return $p[2].'-'.$p[1].'-'.$p[0];
        }

        $tipe_label = [
            '1' => 'Stok Awal',
            '2' => 'Pembelian / DO',
            '3' => 'Penjualan',
            '4' => 'Servis',
            '5' => 'Penyesuaian Masuk',
            '6' => 'Penyesuaian Keluar',
            '7' => 'Retur Pembelian',
            '8' => 'Retur Penjualan',
        ];

        $no_item_filter  = '';
        $namaitem_result = '';
        $tgl_dari        = date('d/m/Y', strtotime('-1 month'));
        $tgl_sampai      = date('d/m/Y');
        $tgl_dari_eng    = date('Y-m-d', strtotime('-1 month'));
        $tgl_sampai_eng  = date('Y-m-d');
        $rows            = [];
        $sudah_cari      = false;
        $saldo_awal      = 0;

        if(isset($_POST['btnrst'])) {
            $sudah_cari     = true;
            $no_item_filter = mysqli_real_escape_string($koneksi, trim($_POST['no_item'] ?? ''));
            $tgl_dari       = $_POST['id-date-picker-1'];
            $tgl_sampai     = $_POST['id-date-picker-2'];
            $tgl_dari_eng   = ubahTgl_KS($tgl_dari);
            $tgl_sampai_eng = ubahTgl_KS($tgl_sampai);

            if($no_item_filter != '') {
                $qitem = mysqli_query($koneksi,"SELECT namaitem FROM tblitem WHERE noitem='$no_item_filter'");
                if($ri = mysqli_fetch_assoc($qitem)) { $namaitem_result = $ri['namaitem']; }

                // Saldo sebelum periode
                $q_sal = mysqli_query($koneksi,"SELECT COALESCE(SUM(masuk),0) AS tot_masuk, COALESCE(SUM(keluar),0) AS tot_keluar
                                                FROM tbstok
                                                WHERE no_item='$no_item_filter' AND kd_cabang='$kd_cabang'
                                                AND tanggal < '$tgl_dari_eng'");
                $r_sal    = mysqli_fetch_assoc($q_sal);
                $saldo_awal = (int)$r_sal['tot_masuk'] - (int)$r_sal['tot_keluar'];

                // Mutasi dalam periode
                $sql = mysqli_query($koneksi,"SELECT tipe, no_transaksi, tanggal,
                                                     DATE_FORMAT(tanggal,'%d/%m/%Y') AS tgl_fmt,
                                                     masuk, keluar, keterangan
                                              FROM tbstok
                                              WHERE no_item='$no_item_filter' AND kd_cabang='$kd_cabang'
                                              AND tanggal>='$tgl_dari_eng' AND tanggal<='$tgl_sampai_eng'
                                              ORDER BY tanggal, id");
                while($r = mysqli_fetch_assoc($sql)) { $rows[] = $r; }
            }
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
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />
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
                <a href="index.php" class="navbar-brand">
                    <small><i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?></small>
                </a>
            </div>
            <div class="navbar-buttons navbar-header pull-right" role="navigation">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User" />
                            <span class="user-info"><small>Welcome,</small> <?php echo $_nama; ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container ace-save-state" id="main-container">
        <script>try{ace.settings.loadState('main-container')}catch(e){}</script>
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <script>try{ace.settings.loadState('sidebar')}catch(e){}</script>
            <?php include "menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"
                   data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i><a href="index.php">Home</a></li>
                        <li><a href="#">Laporan</a></li>
                        <li class="active">Kartu Stok</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-book"></i> Kartu Stok</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form action="" method="post">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-3">
                                                <label>Kode Barang</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" name="no_item" id="no_item"
                                                           value="<?php echo htmlspecialchars($no_item_filter); ?>"
                                                           placeholder="Ketik kode barang..." autocomplete="off" />
                                                    <span class="input-group-addon"><i class="fa fa-barcode"></i></span>
                                                </div>
                                                <?php if($namaitem_result): ?>
                                                <small class="text-info"><i class="fa fa-check"></i> <?php echo htmlspecialchars($namaitem_result); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Dari Tanggal</label>
                                                <div class="input-group">
                                                    <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off"
                                                        value="<?php echo $tgl_dari; ?>" data-date-format="dd/mm/yyyy" />
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Sampai Tanggal</label>
                                                <div class="input-group">
                                                    <input class="form-control date-picker" id="id-date-picker-2" name="id-date-picker-2" type="text" autocomplete="off"
                                                        value="<?php echo $tgl_sampai; ?>" data-date-format="dd/mm/yyyy" />
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>&nbsp;</label><br/>
                                                <button class="btn btn-primary" type="submit" name="btnrst">
                                                    <i class="fa fa-search"></i> Tampilkan
                                                </button>
                                            </div>
                                        </div>
                                        </form>

                                        <?php if($sudah_cari): ?>
                                        <hr/>
                                        <?php if($no_item_filter == ''): ?>
                                        <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Masukkan kode barang terlebih dahulu.</div>
                                        <?php elseif($namaitem_result == ''): ?>
                                        <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Kode barang <strong><?php echo htmlspecialchars($no_item_filter); ?></strong> tidak ditemukan.</div>
                                        <?php else: ?>
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-5">
                                                <table class="table table-condensed">
                                                    <tr><td width="35%"><strong>Kode Barang</strong></td><td><?php echo htmlspecialchars($no_item_filter); ?></td></tr>
                                                    <tr><td><strong>Nama Barang</strong></td><td><?php echo htmlspecialchars($namaitem_result); ?></td></tr>
                                                    <tr><td><strong>Periode</strong></td><td><?php echo $tgl_dari; ?> s/d <?php echo $tgl_sampai; ?></td></tr>
                                                    <tr><td><strong>Saldo Awal Periode</strong></td><td><strong class="text-primary"><?php echo number_format($saldo_awal,0,',','.'); ?></strong></td></tr>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-condensed">
                                            <thead style="background:#5a7a35;color:#fff;">
                                                <tr>
                                                    <th class="center" width="4%">No</th>
                                                    <th class="center" width="10%">Tanggal</th>
                                                    <th width="16%">No. Transaksi</th>
                                                    <th width="15%">Jenis</th>
                                                    <th width="25%">Keterangan</th>
                                                    <th class="center" width="8%">Masuk</th>
                                                    <th class="center" width="8%">Keluar</th>
                                                    <th class="center" width="9%">Saldo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $saldo = $saldo_awal;
                                            if(count($rows) == 0): ?>
                                            <tr><td colspan="8" class="center text-muted" style="padding:20px;">Tidak ada mutasi stok pada periode ini.</td></tr>
                                            <?php else:
                                                $no = 0;
                                                foreach($rows as $row):
                                                    $no++;
                                                    $saldo += (int)$row['masuk'] - (int)$row['keluar'];
                                                    $label  = $tipe_label[$row['tipe']] ?? 'Lainnya';
                                            ?>
                                            <tr>
                                                <td class="center"><?php echo $no; ?></td>
                                                <td class="center"><?php echo $row['tgl_fmt']; ?></td>
                                                <td><?php echo htmlspecialchars($row['no_transaksi']); ?></td>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                                <td class="center">
                                                    <?php echo ($row['masuk'] > 0)
                                                        ? '<strong class="text-success">'.number_format($row['masuk'],0,',','.').'</strong>'
                                                        : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td class="center">
                                                    <?php echo ($row['keluar'] > 0)
                                                        ? '<strong class="text-danger">'.number_format($row['keluar'],0,',','.').'</strong>'
                                                        : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td class="center"><strong><?php echo number_format($saldo,0,',','.'); ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr style="background:#e8f4e8;">
                                                <td colspan="5" align="right"><strong>Saldo Akhir Periode</strong></td>
                                                <td colspan="3" class="center"><strong class="text-primary" style="font-size:14px;"><?php echo number_format($saldo,0,',','.'); ?></strong></td>
                                            </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <div class="footer-content"><?php include "../lib/footer.php"; ?></div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    jQuery(function($) {
        $('.date-picker').datepicker({ autoclose: true, format: 'dd/mm/yyyy' });

        // Autocomplete kode barang via jQuery UI
        $('#no_item').autocomplete({
            source: function(req, resp) {
                $.getJSON('ajax_cari_item_stok.php', { q: req.term, cab: '<?php echo $kd_cabang; ?>' }, function(data) {
                    resp(data);
                });
            },
            minLength: 2,
            select: function(e, ui) {
                $('#no_item').val(ui.item.value);
                return false;
            }
        });
    });
    </script>
</body>
</html>
<?php } ?>
