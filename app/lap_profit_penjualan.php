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
        function ubahTgl_PP($tgl) {
            $p = explode('/',$tgl);
            return $p[2].'-'.$p[1].'-'.$p[0];
        }

        $tgl_pilih_dari   = date('d/m/Y');
        $tgl_pilih_sampai = date('d/m/Y');
        $tgl_dari_eng     = date('Y-m-d');
        $tgl_sampai_eng   = date('Y-m-d');
        $cbo_pelanggan    = '';
        $hasil_cari       = '';
        $rows             = [];
        $grand_jual       = 0;
        $grand_hpp        = 0;

        $sql_pel = mysqli_query($koneksi,"SELECT nopelanggan, namapelanggan FROM tblpelanggan ORDER BY namapelanggan LIMIT 500");

        if(isset($_POST['btnrst'])) {
            $tgl_pilih_dari   = $_POST['id-date-picker-1'];
            $tgl_pilih_sampai = $_POST['id-date-picker-2'];
            $cbo_pelanggan    = mysqli_real_escape_string($koneksi, $_POST['cbopelanggan'] ?? '');
            $tgl_dari_eng     = ubahTgl_PP($tgl_pilih_dari);
            $tgl_sampai_eng   = ubahTgl_PP($tgl_pilih_sampai);

            $where_pel = ($cbo_pelanggan != '') ? " AND ph.no_pelanggan='$cbo_pelanggan'" : '';

            $sql = mysqli_query($koneksi,
                "SELECT ph.notransaksi,
                        DATE_FORMAT(ph.tanggal,'%d/%m/%Y') AS tgl_fmt,
                        COALESCE(p.namapelanggan,'Umum') AS namapelanggan,
                        SUM(d.quantity * d.harga_jual)  AS total_jual,
                        SUM(d.quantity * d.harga_pokok) AS total_hpp,
                        SUM(d.quantity) AS total_qty
                 FROM tblpenjualan_detail d
                 JOIN tblpenjualan_header ph ON ph.notransaksi = d.no_transaksi
                 LEFT JOIN tblpelanggan p ON p.nopelanggan = ph.no_pelanggan
                 WHERE d.kd_cabang='$kd_cabang'
                   AND ph.tanggal>='$tgl_dari_eng' AND ph.tanggal<='$tgl_sampai_eng'
                   AND d.status_trx != '0'
                   $where_pel
                 GROUP BY ph.notransaksi, ph.tanggal, p.namapelanggan
                 ORDER BY ph.tanggal, ph.notransaksi");

            while($r = mysqli_fetch_assoc($sql)) {
                $rows[]      = $r;
                $grand_jual += (float)$r['total_jual'];
                $grand_hpp  += (float)$r['total_hpp'];
            }

            $tot      = count($rows);
            $gprofit  = $grand_jual - $grand_hpp;
            $gmargin  = ($grand_jual > 0) ? round($gprofit / $grand_jual * 100, 1) : 0;
            $hasil_cari = "Ditemukan <strong>$tot</strong> transaksi &nbsp;|&nbsp; "
                        . "Total Jual: <strong>Rp ".number_format($grand_jual,0,',','.')."</strong> &nbsp;|&nbsp; "
                        . "Total HPP: <strong>Rp ".number_format($grand_hpp,0,',','.')."</strong> &nbsp;|&nbsp; "
                        . "Profit: <strong class='text-success'>Rp ".number_format($gprofit,0,',','.')."</strong> &nbsp;|&nbsp; "
                        . "Margin: <strong>$gmargin%</strong>";
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
                        <li class="active">Laporan Profit Penjualan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-line-chart"></i> Laporan Profit Penjualan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form action="" method="post">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Dari Tanggal</label>
                                                <div class="input-group">
                                                    <input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off"
                                                        value="<?php echo $tgl_pilih_dari; ?>" data-date-format="dd/mm/yyyy" />
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Sampai Tanggal</label>
                                                <div class="input-group">
                                                    <input class="form-control date-picker" id="id-date-picker-2" name="id-date-picker-2" type="text" autocomplete="off"
                                                        value="<?php echo $tgl_pilih_sampai; ?>" data-date-format="dd/mm/yyyy" />
                                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-3">
                                                <label>Pelanggan</label>
                                                <select class="form-control chosen-select" name="cbopelanggan">
                                                    <option value="">-- Semua Pelanggan --</option>
                                                    <?php while($rs = mysqli_fetch_assoc($sql_pel)): ?>
                                                    <option value="<?php echo htmlspecialchars($rs['nopelanggan']); ?>"
                                                        <?php echo ($cbo_pelanggan==$rs['nopelanggan'])?'selected':''; ?>>
                                                        <?php echo htmlspecialchars($rs['namapelanggan']); ?>
                                                    </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>&nbsp;</label><br/>
                                                <button class="btn btn-primary" type="submit" name="btnrst">
                                                    <i class="fa fa-search"></i> Tampilkan
                                                </button>
                                            </div>
                                        </div>
                                        </form>

                                        <?php if($hasil_cari): ?>
                                        <hr/>
                                        <div class="table-header" style="margin-bottom:10px;"><?php echo $hasil_cari; ?></div>
                                        <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-condensed">
                                            <thead style="background:#5a7a35;color:#fff;">
                                                <tr>
                                                    <th class="center" width="4%">No</th>
                                                    <th width="13%">No. Penjualan</th>
                                                    <th class="center" width="9%">Tanggal</th>
                                                    <th width="19%">Pelanggan</th>
                                                    <th class="center" width="5%">Qty</th>
                                                    <th align="right" width="12%">Total Jual</th>
                                                    <th align="right" width="12%">HPP</th>
                                                    <th align="right" width="12%">Profit</th>
                                                    <th class="center" width="7%">Margin</th>
                                                    <th class="center" width="7%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $no = 0;
                                            foreach($rows as $row):
                                                $no++;
                                                $profit     = (float)$row['total_jual'] - (float)$row['total_hpp'];
                                                $margin     = ((float)$row['total_jual'] > 0) ? round($profit / (float)$row['total_jual'] * 100, 1) : 0;
                                                $cls_profit = $profit >= 0 ? 'text-success' : 'text-danger';
                                            ?>
                                            <tr>
                                                <td class="center"><?php echo $no; ?></td>
                                                <td><?php echo htmlspecialchars($row['notransaksi']); ?></td>
                                                <td class="center"><?php echo $row['tgl_fmt']; ?></td>
                                                <td><?php echo htmlspecialchars($row['namapelanggan']); ?></td>
                                                <td class="center"><?php echo number_format($row['total_qty'],0,',','.'); ?></td>
                                                <td align="right"><?php echo number_format($row['total_jual'],0,',','.'); ?></td>
                                                <td align="right"><?php echo number_format($row['total_hpp'],0,',','.'); ?></td>
                                                <td align="right"><strong class="<?php echo $cls_profit; ?>"><?php echo number_format($profit,0,',','.'); ?></strong></td>
                                                <td class="center"><span class="<?php echo $cls_profit; ?>"><?php echo $margin; ?>%</span></td>
                                                <td class="center">
                                                    <a href="penjualan_detail.php?notransaksi=<?php echo urlencode($row['notransaksi']); ?>" class="btn btn-xs btn-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if(count($rows) > 0):
                                                $gprofit = $grand_jual - $grand_hpp;
                                                $gmargin = ($grand_jual > 0) ? round($gprofit / $grand_jual * 100, 1) : 0;
                                            ?>
                                            <tr style="background:#e8f4e8;font-weight:bold;">
                                                <td colspan="5" align="right">TOTAL</td>
                                                <td align="right"><?php echo number_format($grand_jual,0,',','.'); ?></td>
                                                <td align="right"><?php echo number_format($grand_hpp,0,',','.'); ?></td>
                                                <td align="right" class="text-success"><?php echo number_format($gprofit,0,',','.'); ?></td>
                                                <td class="center"><?php echo $gmargin; ?>%</td>
                                                <td></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if(count($rows) == 0): ?>
                                            <tr><td colspan="10" class="center text-muted" style="padding:20px;">Tidak ada data pada periode ini.</td></tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                        </div>
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
    <script src="assets/js/chosen.jquery.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    jQuery(function($) {
        if(!ace.vars['touch']) { $('.chosen-select').chosen({allow_single_deselect:true}); }
        $('.date-picker').datepicker({ autoclose: true, format: 'dd/mm/yyyy' });
    });
    </script>
</body>
</html>
<?php } ?>
