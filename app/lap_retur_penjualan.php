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
        $lvl_akses= $tm_cari['user_akses'];
        $foto_user= $tm_cari['foto_user'];
        if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

        $cari_kd   = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
        $tm_cari   = mysqli_fetch_array($cari_kd);
        $nama_cabang = $tm_cari['nama_cabang'];

        date_default_timezone_set('Asia/Jakarta');
        function ubahformatTgl_RPJ($tanggal) {
            $pisah = explode('/',$tanggal);
            return $pisah[2].'-'.$pisah[1].'-'.$pisah[0];
        }

        include_once "includes/report-default-range.php";
        $_default_range = app_report_default_range($koneksi, 'tblretur_penjualan_header', 'tanggal', "kd_cabang='$kd_cabang'");
        $tgl_pilih_dari    = $_default_range['from_dmy'];
        $tgl_pilih_sampai  = $_default_range['to_dmy'];
        $tgl_dari_eng      = $_default_range['from_ymd'];
        $tgl_sampai_eng    = $_default_range['to_ymd'];
        $cbo_pelanggan     = '';
        $sum_total         = 0;
        $sum_qty           = 0;

        $where_base = "WHERE h.kd_cabang='$kd_cabang' AND (h.tanggal>='$tgl_dari_eng' AND h.tanggal<='$tgl_sampai_eng')";
        $order_base = "ORDER BY h.tanggal, h.noretur";

        $sql_query = "SELECT h.noretur, h.nopembelian AS nopenjualan, DATE_FORMAT(h.tanggal,'%d/%m/%Y') AS tanggal_trx,
                             h.total_qty_retur, h.total_akhir, h.status_retur, h.note,
                             COALESCE(p.namapelanggan,'') AS namapelanggan
                      FROM tblretur_penjualan_header h
                      LEFT JOIN tblpenjualan_header ph ON ph.notransaksi=h.nopembelian
                      LEFT JOIN tblpelanggan p ON p.nopelanggan=ph.no_pelanggan
                      $where_base $order_base";

        $cari_tot = mysqli_query($koneksi,"SELECT COUNT(*) AS tot FROM tblretur_penjualan_header h $where_base");
        $tm_tot   = mysqli_fetch_array($cari_tot);
        $tot = $tm_tot['tot'];
        $hasil_cari = "Ditemukan ".$tot." data";

        // Daftar pelanggan untuk filter
        $sql_pel = mysqli_query($koneksi,"SELECT nopelanggan, namapelanggan FROM tblpelanggan ORDER BY namapelanggan LIMIT 500");

        if(isset($_POST['btnrst'])) {
            $tgl_pilih_dari   = mysqli_real_escape_string($koneksi, $_POST['id-date-picker-1']);
            $tgl_pilih_sampai = mysqli_real_escape_string($koneksi, $_POST['id-date-picker-2']);
            $cbo_pelanggan    = mysqli_real_escape_string($koneksi, $_POST['cbopelanggan'] ?? '');
            $tgl_dari_eng     = ubahformatTgl_RPJ($tgl_pilih_dari);
            $tgl_sampai_eng   = ubahformatTgl_RPJ($tgl_pilih_sampai);

            $where = "WHERE h.kd_cabang='$kd_cabang' AND (h.tanggal>='$tgl_dari_eng' AND h.tanggal<='$tgl_sampai_eng')";
            if($cbo_pelanggan != '') {
                $where .= " AND ph.no_pelanggan='$cbo_pelanggan'";
            }
            $sql_query = "SELECT h.noretur, h.nopembelian AS nopenjualan, DATE_FORMAT(h.tanggal,'%d/%m/%Y') AS tanggal_trx,
                                 h.total_qty_retur, h.total_akhir, h.status_retur, h.note,
                                 COALESCE(p.namapelanggan,'') AS namapelanggan
                          FROM tblretur_penjualan_header h
                          LEFT JOIN tblpenjualan_header ph ON ph.notransaksi=h.nopembelian
                          LEFT JOIN tblpelanggan p ON p.nopelanggan=ph.no_pelanggan
                          $where $order_base";

            $cari_tot = mysqli_query($koneksi,"SELECT COUNT(*) AS tot, COALESCE(SUM(h.total_akhir),0) AS sum_total, COALESCE(SUM(h.total_qty_retur),0) AS sum_qty
                                               FROM tblretur_penjualan_header h
                                               LEFT JOIN tblpenjualan_header ph ON ph.notransaksi=h.nopembelian
                                               $where");
            $tm_tot   = mysqli_fetch_array($cari_tot);
            $tot       = $tm_tot['tot'];
            $sum_total = $tm_tot['sum_total'];
            $sum_qty   = $tm_tot['sum_qty'];
            $hasil_cari = "Ditemukan <strong>".$tot."</strong> data &nbsp;|&nbsp; Total Qty: <strong>".number_format($sum_qty,0,',','.')."</strong> &nbsp;|&nbsp; Total Nilai: <strong>Rp ".number_format($sum_total,0,',','.')."</strong>";
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
                            <img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
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
                        <li class="active">Laporan Retur Penjualan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title"><i class="fa fa-undo"></i> Laporan Retur Penjualan</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <form class="form-horizontal" action="" method="post">
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
                                                <select class="form-control chosen-select" name="cbopelanggan" id="cbopelanggan">
                                                    <option value="">-- Semua Pelanggan --</option>
                                                    <?php
                                                    mysqli_data_seek($sql_pel, 0);
                                                    while($rs = mysqli_fetch_assoc($sql_pel)): ?>
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
                                        <hr/>
                                        <div class="table-header"><?php echo $hasil_cari; ?></div>
                                        <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-condensed">
                                            <thead style="background:#5a7a35;color:#fff;">
                                                <tr>
                                                    <th class="center" width="4%">No</th>
                                                    <th width="13%">No. Retur</th>
                                                    <th width="13%">No. Penjualan</th>
                                                    <th class="center" width="9%">Tanggal</th>
                                                    <th width="23%">Pelanggan</th>
                                                    <th class="center" width="6%">Qty</th>
                                                    <th align="right" width="13%">Total Nilai</th>
                                                    <th class="center" width="7%">Status</th>
                                                    <th class="center" width="12%">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $no = 0;
                                            $sql = mysqli_query($koneksi, $sql_query);
                                            while($row = mysqli_fetch_assoc($sql)):
                                                $no++;
                                            ?>
                                            <tr>
                                                <td class="center"><?php echo $no; ?></td>
                                                <td><?php echo htmlspecialchars($row['noretur']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nopenjualan']); ?></td>
                                                <td class="center"><?php echo $row['tanggal_trx']; ?></td>
                                                <td><?php echo htmlspecialchars($row['namapelanggan']); ?></td>
                                                <td class="center"><?php echo number_format($row['total_qty_retur'],0,',','.'); ?></td>
                                                <td align="right"><?php echo number_format($row['total_akhir'],0,',','.'); ?></td>
                                                <td class="center">
                                                    <?php echo ($row['status_retur']=='1')
                                                        ? '<span class="label label-success">Selesai</span>'
                                                        : '<span class="label label-warning">Proses</span>'; ?>
                                                </td>
                                                <td class="center">
                                                    <a href="retur_penjualan_detail.php?noretur=<?php echo urlencode($row['noretur']); ?>" class="btn btn-xs btn-info">
                                                        <i class="fa fa-eye"></i> Detail
                                                    </a>
                                                    <a href="retur_penjualan_cetak.php?noretur=<?php echo urlencode($row['noretur']); ?>" class="btn btn-xs btn-default" target="_blank">
                                                        <i class="fa fa-print"></i>
                                                    </a>
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
        if(!ace.vars['touch']) {
            $('.chosen-select').chosen({allow_single_deselect:true});
        }
        $('.date-picker').datepicker({
            autoclose: true,
            format: 'dd/mm/yyyy'
        });
    });
    </script>
</body>
</html>
<?php } ?>
