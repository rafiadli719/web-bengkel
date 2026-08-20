<?php
    session_start();
    if(empty($_SESSION['_iduser'])){
        header("location:../index.php");
    } else {
        $id_user   = $_SESSION['_iduser'];
        $kd_cabang = $_SESSION['_cabang'];
        include "../config/koneksi.php";

        $cari_kd = mysqli_query($koneksi,"SELECT nama_user, foto_user FROM tbuser WHERE id='$id_user'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $_nama    = $tm_cari['nama_user'];
        $foto_user= $tm_cari['foto_user'];
        if($foto_user=='') { $foto_user="file_upload/avatar.png"; }

        $cari_kd = mysqli_query($koneksi,"SELECT nama_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $nama_cabang = $tm_cari['nama_cabang'];

        date_default_timezone_set('Asia/Jakarta');

        $filter_cari        = '';
        $filter_member      = '';
        $filter_lama_min    = '';
        $filter_sort        = 'lama_tidak_datang';
        $rows               = [];
        $grand_kunjungan    = 0;
        $grand_nominal      = 0;
        $hasil_cari         = '';
        $sudah_cari         = false;

        if(isset($_POST['btnrst'])) {
            $sudah_cari       = true;
            $filter_cari      = mysqli_real_escape_string($koneksi, trim($_POST['filter_cari'] ?? ''));
            $filter_member    = mysqli_real_escape_string($koneksi, $_POST['filter_member'] ?? '');
            $filter_lama_min  = (int)($_POST['filter_lama_min'] ?? 0);
            $filter_sort      = in_array($_POST['filter_sort'] ?? '', ['lama_tidak_datang','estimasi_datang_berikutnya','jumlah_kunjungan','total_nominal'])
                                ? $_POST['filter_sort'] : 'lama_tidak_datang';

            $where = ['1=1'];
            if($filter_cari !== '') {
                $where[] = "(nama_pelanggan LIKE '%$filter_cari%' OR no_pelanggan LIKE '%$filter_cari%' OR telephone LIKE '%$filter_cari%')";
            }
            if($filter_member !== '') {
                $where[] = "status_member = '$filter_member'";
            }
            if($filter_lama_min > 0) {
                $where[] = "lama_tidak_datang >= $filter_lama_min";
            }

            $order_dir = ($filter_sort === 'estimasi_datang_berikutnya') ? 'ASC' : 'DESC';
            $where_sql = implode(' AND ', $where);

            $sql = mysqli_query($koneksi,
                "SELECT no_pelanggan, nama_pelanggan, telephone,
                        jumlah_kunjungan, total_nominal, status_member,
                        tanggal_pertama, tanggal_terakhir,
                        lama_tidak_datang, lama_menjadi_pelanggan,
                        rata_jarak_kunjungan, estimasi_datang_berikutnya,
                        total_motor,
                        DATE_FORMAT(tanggal_terakhir, '%d/%m/%Y') AS tgl_terakhir_fmt,
                        DATE_FORMAT(estimasi_datang_berikutnya, '%d/%m/%Y') AS estimasi_fmt
                 FROM view_statistik_pelanggan
                 WHERE $where_sql
                 ORDER BY
                     CASE WHEN $filter_sort IS NULL THEN 1 ELSE 0 END,
                     $filter_sort $order_dir
                 LIMIT 500");

            while($r = mysqli_fetch_assoc($sql)) {
                $rows[]          = $r;
                $grand_kunjungan += (int)$r['jumlah_kunjungan'];
                $grand_nominal   += (float)$r['total_nominal'];
            }

            $tot = count($rows);
            $hasil_cari = "Ditemukan <strong>$tot</strong> pelanggan &nbsp;|&nbsp; "
                        . "Total Kunjungan: <strong>" . number_format($grand_kunjungan,0,',','.') . "</strong> &nbsp;|&nbsp; "
                        . "Total Transaksi: <strong class='text-success'>Rp " . number_format($grand_nominal,0,',','.') . "</strong>";
            if($tot >= 500) $hasil_cari .= ' &nbsp;<span class="label label-warning">Maks 500 ditampilkan</span>';
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
                        <li class="active">Rekap Kunjungan Pelanggan</li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">
                                        <i class="fa fa-users"></i> Rekap Kunjungan Pelanggan
                                        <small class="text-muted"> &mdash; statistik kunjungan &amp; estimasi kedatangan berikutnya</small>
                                    </h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">

                                        <form action="" method="post">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-3">
                                                <label>Cari Nama / No. Pelanggan / Telepon</label>
                                                <input type="text" name="filter_cari" class="form-control"
                                                    value="<?php echo htmlspecialchars($filter_cari); ?>"
                                                    placeholder="Ketik nama, no. pelanggan, atau no. telp..." />
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Status Member</label>
                                                <select name="filter_member" class="form-control">
                                                    <option value="">-- Semua --</option>
                                                    <?php
                                                    $member_options = ['Bronze','Silver','Gold','Platinum'];
                                                    foreach($member_options as $mo):
                                                    ?>
                                                    <option value="<?php echo $mo; ?>" <?php echo ($filter_member==$mo)?'selected':''; ?>><?php echo $mo; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Lama Tidak Datang (min. hari)</label>
                                                <input type="number" name="filter_lama_min" class="form-control" min="0"
                                                    value="<?php echo htmlspecialchars($filter_lama_min); ?>"
                                                    placeholder="mis. 30" />
                                            </div>
                                            <div class="col-xs-12 col-sm-2">
                                                <label>Urutkan Berdasar</label>
                                                <select name="filter_sort" class="form-control">
                                                    <option value="lama_tidak_datang" <?php echo ($filter_sort=='lama_tidak_datang')?'selected':''; ?>>Lama Tidak Datang</option>
                                                    <option value="estimasi_datang_berikutnya" <?php echo ($filter_sort=='estimasi_datang_berikutnya')?'selected':''; ?>>Estimasi Berikutnya</option>
                                                    <option value="jumlah_kunjungan" <?php echo ($filter_sort=='jumlah_kunjungan')?'selected':''; ?>>Jumlah Kunjungan</option>
                                                    <option value="total_nominal" <?php echo ($filter_sort=='total_nominal')?'selected':''; ?>>Total Transaksi</option>
                                                </select>
                                            </div>
                                            <div class="col-xs-12 col-sm-3">
                                                <label>&nbsp;</label><br/>
                                                <button class="btn btn-primary" type="submit" name="btnrst">
                                                    <i class="fa fa-search"></i> Tampilkan
                                                </button>
                                                <button class="btn btn-default" type="button" onclick="window.location='lap_rekap_kunjungan.php'">
                                                    <i class="fa fa-refresh"></i> Reset
                                                </button>
                                            </div>
                                        </div>
                                        </form>

                                        <div class="row" style="margin-top:10px;">
                                            <div class="col-xs-12">
                                                <div class="alert alert-info alert-sm" style="margin-bottom:0;padding:6px 12px;">
                                                    <i class="fa fa-info-circle"></i>
                                                    <strong>Kode warna Lama Tidak Datang:</strong>
                                                    <span class="label label-success">&le; 45 hari</span>
                                                    <span class="label label-warning">46&ndash;90 hari</span>
                                                    <span class="label label-danger">&gt; 90 hari</span>
                                                    &nbsp;&mdash; maksimal 500 data ditampilkan per pencarian.
                                                </div>
                                            </div>
                                        </div>

                                        <?php if($sudah_cari): ?>
                                        <hr/>
                                        <div class="table-header" style="margin-bottom:10px;"><?php echo $hasil_cari; ?></div>
                                        <div class="table-responsive">
                                        <table id="tbl-rekap" class="table table-bordered table-striped table-condensed">
                                            <thead style="background:#3a6ea5;color:#fff;">
                                                <tr>
                                                    <th class="center" width="4%">No</th>
                                                    <th width="18%">Nama Pelanggan</th>
                                                    <th width="10%">No. Pelanggan</th>
                                                    <th width="9%">Telepon</th>
                                                    <th class="center" width="5%">Kunjungan</th>
                                                    <th class="center" width="9%">Terakhir Datang</th>
                                                    <th class="center" width="9%">Lama Tidak Datang</th>
                                                    <th class="center" width="8%">Interval Rata-rata</th>
                                                    <th class="center" width="9%">Estimasi Berikutnya</th>
                                                    <th align="right" width="10%">Total Transaksi</th>
                                                    <th class="center" width="6%">Member</th>
                                                    <th class="center" width="4%">WA</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $no = 0;
                                            foreach($rows as $row):
                                                $no++;
                                                $lama = (int)$row['lama_tidak_datang'];
                                                $lama_cls = $lama > 90 ? 'danger' : ($lama > 45 ? 'warning' : 'success');
                                                $lama_text = $lama === 0 ? 'Hari ini' : $lama . ' hari';
                                                $interval = (float)$row['rata_jarak_kunjungan'];
                                                $interval_text = ($interval > 0)
                                                    ? '~' . round($interval) . ' hari'
                                                    : (((int)$row['jumlah_kunjungan'] <= 1) ? 'Baru 1x' : '-');
                                                $member = $row['status_member'] ?? 'Bronze';
                                                $member_cls = $member === 'Platinum' ? 'primary'
                                                    : ($member === 'Gold' ? 'warning'
                                                    : ($member === 'Silver' ? 'info' : 'default'));
                                                $telp = preg_replace('/[^0-9]/', '', $row['telephone'] ?? '');
                                                $wa_number = $telp ? (substr($telp,0,1)==='0' ? '62'.substr($telp,1) : $telp) : '';
                                                $wa_url = $wa_number ? 'https://wa.me/' . $wa_number : '#';
                                            ?>
                                            <tr>
                                                <td class="center"><?php echo $no; ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></td>
                                                <td><small><?php echo htmlspecialchars($row['no_pelanggan']); ?></small></td>
                                                <td><?php echo htmlspecialchars($row['telephone'] ?? '-'); ?></td>
                                                <td class="center"><strong><?php echo number_format((int)$row['jumlah_kunjungan'],0,',','.'); ?>x</strong></td>
                                                <td class="center"><small><?php echo $row['tgl_terakhir_fmt'] ?? '-'; ?></small></td>
                                                <td class="center">
                                                    <span class="label label-<?php echo $lama_cls; ?>"><?php echo $lama_text; ?></span>
                                                </td>
                                                <td class="center"><small><?php echo $interval_text; ?></small></td>
                                                <td class="center">
                                                    <?php if($row['estimasi_fmt']): ?>
                                                    <span class="label label-info"><?php echo $row['estimasi_fmt']; ?></span>
                                                    <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td align="right"><?php echo 'Rp '.number_format((float)$row['total_nominal'],0,',','.'); ?></td>
                                                <td class="center">
                                                    <span class="label label-<?php echo $member_cls; ?>"><?php echo htmlspecialchars($member); ?></span>
                                                </td>
                                                <td class="center">
                                                    <?php if($wa_number): ?>
                                                    <a href="<?php echo $wa_url; ?>" target="_blank" class="btn btn-xs btn-success" title="Hubungi via WhatsApp">
                                                        <i class="fa fa-whatsapp"></i>
                                                    </a>
                                                    <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if(count($rows) === 0): ?>
                                            <tr><td colspan="12" class="center text-muted" style="padding:20px;">Tidak ada data yang sesuai filter.</td></tr>
                                            <?php endif; ?>
                                            </tbody>
                                            <?php if(count($rows) > 0): ?>
                                            <tfoot style="background:#e8eef5;font-weight:bold;">
                                                <tr>
                                                    <td colspan="4" align="right">TOTAL</td>
                                                    <td class="center"><?php echo number_format($grand_kunjungan,0,',','.'); ?>x</td>
                                                    <td colspan="4"></td>
                                                    <td align="right">Rp <?php echo number_format($grand_nominal,0,',','.'); ?></td>
                                                    <td colspan="2"></td>
                                                </tr>
                                            </tfoot>
                                            <?php endif; ?>
                                        </table>
                                        </div>
                                        <?php endif; ?>

                                        <?php if(!$sudah_cari): ?>
                                        <div class="alert alert-default" style="background:#f5f5f5;margin-top:15px;text-align:center;padding:30px;">
                                            <i class="fa fa-users fa-3x text-muted" style="display:block;margin-bottom:10px;"></i>
                                            <p class="text-muted">Gunakan filter di atas dan klik <strong>Tampilkan</strong> untuk melihat rekap kunjungan pelanggan.</p>
                                            <p class="text-muted"><small>Tip: Kosongkan semua filter untuk melihat semua pelanggan (maks. 500).</small></p>
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
    <script src="assets/js/jquery.dataTables.min.js"></script>
    <script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
    <script src="assets/js/ace-elements.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
    <script>
    jQuery(function($) {
        <?php if($sudah_cari && count($rows) > 0): ?>
        $('#tbl-rekap').DataTable({
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ pelanggan",
                "infoEmpty": "0 data",
                "zeroRecords": "Tidak ada data",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            },
            "pageLength": 25,
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": [0, 11] }
            ]
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>
<?php } ?>
