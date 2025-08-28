<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";
        
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$_nama=$tm_cari['nama_user'];				        
		$pwd=$tm_cari['password'];				        
		$lvl_akses=$tm_cari['user_akses'];				                
		$foto_user=$tm_cari['foto_user'];				
		if($foto_user=='') {
			$foto_user="file_upload/avatar.png";
		}

    // ------- Data Cabang ----------
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang=$tm_cari['nama_cabang'];				        
        $tipe_cabang=$tm_cari['tipe_cabang'];	
    // --------------------

        $kode_wo = $_GET['kode'] ?? '';
        
        // Get work order data
        $cari_kd = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
        if(mysqli_num_rows($cari_kd) == 0) {
            echo"<script>window.alert('Work order tidak ditemukan!');
            window.location=('workorder-list.php');</script>";
            exit;
        }
        
        $tm_cari = mysqli_fetch_array($cari_kd);
        $nama_wo = $tm_cari['nama_wo'];
        $keterangan = $tm_cari['keterangan'];
        $total_waktu = $tm_cari['waktu'];
        $total_harga = $tm_cari['harga'];
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="with draggable and editable events" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/fullcalendar.min.css" />

		<!-- text fonts -->
		<link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

		<!-- ace styles -->
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
	<script type="text/javascript" src="chartjs/Chart.js"></script>

    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>	

        <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.8.0/main.css' rel='stylesheet' />
		
	</head>

	<body class="no-skin">
		<div id="navbar" class="navbar navbar-default          ace-save-state">
			<div class="navbar-container ace-save-state" id="navbar-container">
				<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
					<span class="sr-only">Toggle sidebar</span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>

					<span class="icon-bar"></span>
				</button>

				<div class="navbar-header pull-left">
					
					<table>
						<tr>
							<td width="20%">
								<a href="index.php" class="navbar-brand">
									<small>
							<i class="fa fa-leaf"></i>
							<?php include "../lib/subtitel.php"; ?>
									</small>							
								</a>								
							</td>
							<td>

                            </td>							
						</tr>
					</table>
				
				</div>

				<div class="navbar-buttons navbar-header pull-right" role="navigation">
					
					<ul class="nav ace-nav">

						<li class="light-blue dropdown-modal">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
								<span class="user-info">
									<small>Welcome,</small>
									<?php echo $_nama; ?>
								</span>

								<i class="ace-icon fa fa-caret-down"></i>
							</a>

							<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="change_pwd.php">
										<i class="ace-icon fa fa-cog"></i>
										Change Password
									</a>
								</li>

								<li>
									<a href="profile.php">
										<i class="ace-icon fa fa-user"></i>
										Profile
									</a>
								</li>

								<li class="divider"></li>

								<li>
									<a href="logout.php">
										<i class="ace-icon fa fa-power-off"></i>
										Logout
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
				<div class="navbar-header pull-right">
					<a href="#" class="navbar-brand"><small></small></a>					
				</div>
			</div><!-- /.navbar-container -->
		</div>
		
		<div class="main-container ace-save-state" id="main-container">
			<script type="text/javascript">
				try{ace.settings.loadState('main-container')}catch(e){}
			</script>

			<div id="sidebar" class="sidebar                  responsive                    ace-save-state">
				<script type="text/javascript">
					try{ace.settings.loadState('sidebar')}catch(e){}
				</script>

<?php include "menu_servis01.php"; ?>

				<div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
					<i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state" data-icon1="ace-icon fa fa-angle-double-left" data-icon2="ace-icon fa fa-angle-double-right"></i>
				</div>
			</div>

			<div class="main-content">
				<div class="main-content-inner">
					<div class="breadcrumbs ace-save-state" id="breadcrumbs">
						<ul class="breadcrumb">
							<li>
								<i class="ace-icon fa fa-home home-icon"></i>
								<a href="index.php">Home</a>
							</li>
                            <li>
								<a href="#">Master Data</a>
							</li>
                            <li>
								<a href="workorder-list.php">Work Order</a>
							</li>                            
							<li class="active">Detail Work Order</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
                        <div class="page-header">
                            <h1>
                                Detail Work Order: <?php echo $kode_wo; ?>
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    <?php echo $nama_wo; ?>
                                </small>
                            </h1>
                        </div>

                        <!-- Work Order Header -->
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title">
                                    <i class="ace-icon fa fa-info-circle"></i>
                                    Informasi Work Order
                                </h4>
                                <div class="widget-toolbar">
                                    <a href="workorder-input.php?mode=edit&kode=<?php echo $kode_wo; ?>" class="btn btn-primary btn-xs">
                                        <i class="ace-icon fa fa-pencil"></i>
                                        Edit
                                    </a>
                                    <a href="workorder-print.php?kode=<?php echo $kode_wo; ?>" target="_blank" class="btn btn-success btn-xs">
                                        <i class="ace-icon fa fa-print"></i>
                                        Print
                                    </a>
                                    <a href="workorder-list.php" class="btn btn-default btn-xs">
                                        <i class="ace-icon fa fa-arrow-left"></i>
                                        Kembali
                                    </a>
                                </div>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <td width="30%"><b>Kode Work Order</b></td>
                                                    <td>
                                                        <span class="label label-info label-lg"><?php echo $kode_wo; ?></span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><b>Nama Work Order</b></td>
                                                    <td><?php echo $nama_wo; ?></td>
                                                </tr>
                                                <tr>
                                                    <td><b>Keterangan</b></td>
                                                    <td><?php echo $keterangan; ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-xs-12 col-sm-6">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <td width="30%"><b>Total Waktu</b></td>
                                                    <td>
                                                        <span class="badge badge-info"><?php echo $total_waktu; ?> Menit</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><b>Total Harga</b></td>
                                                    <td>
                                                        <b class="text-primary">Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></b>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><b>Status</b></td>
                                                    <td>
                                                        <span class="label label-success">Aktif</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detail Work Order -->
                        <div class="widget-box">
                            <div class="widget-header">
                                <h4 class="widget-title">
                                    <i class="ace-icon fa fa-list"></i>
                                    Detail Paket Servis
                                </h4>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6">
                                            <h5 class="header blue">
                                                <i class="ace-icon fa fa-wrench"></i>
                                                Jasa Service
                                            </h5>
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr class="info">
                                                        <th width="5%">No</th>
                                                        <th width="20%">Kode</th>
                                                        <th width="50%">Nama Jasa</th>
                                                        <th width="15%">Waktu</th>
                                                        <th width="15%">Harga</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 0;
                                                    $total_jasa = 0;
                                                    $total_waktu_jasa = 0;
                                                    $sql = mysqli_query($koneksi,"SELECT d.*, w.nama_wo, w.waktu 
                                                                                 FROM tbworkorderdetail d
                                                                                 LEFT JOIN tbworkorderheader w ON d.kode_barang = w.kode_wo
                                                                                 WHERE d.kode_wo='$kode_wo' AND d.tipe='1'
                                                                                 ORDER BY d.id ASC");
                                                    while($tampil = mysqli_fetch_array($sql)) {
                                                        $no++;
                                                        $total_jasa += $tampil['total'];
                                                        $total_waktu_jasa += $tampil['waktu'];
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no; ?></td>
                                                        <td><?php echo $tampil['kode_barang']; ?></td>
                                                        <td><?php echo $tampil['nama_wo']; ?></td>
                                                        <td class="center"><?php echo $tampil['waktu']; ?> mnt</td>
                                                        <td class="right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                    <tr class="info">
                                                        <td colspan="3" class="center"><b>TOTAL JASA</b></td>
                                                        <td class="center"><b><?php echo $total_waktu_jasa; ?> mnt</b></td>
                                                        <td class="right"><b><?php echo number_format($total_jasa, 0, ',', '.'); ?></b></td>
                                                    </tr>
                                                    <?php if($no == 0) { ?>
                                                    <tr>
                                                        <td colspan="5" class="center"><em>Tidak ada jasa service</em></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="col-xs-12 col-sm-6">
                                            <h5 class="header green">
                                                <i class="ace-icon fa fa-cogs"></i>
                                                Barang/Part
                                            </h5>
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr class="info">
                                                        <th width="5%">No</th>
                                                        <th width="20%">Kode</th>
                                                        <th width="45%">Nama Barang</th>
                                                        <th width="10%">Qty</th>
                                                        <th width="20%">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $no = 0;
                                                    $total_barang = 0;
                                                    $sql = mysqli_query($koneksi,"SELECT d.*, v.namaitem 
                                                                                 FROM tbworkorderdetail d
                                                                                 LEFT JOIN view_cari_item v ON d.kode_barang = v.noitem
                                                                                 WHERE d.kode_wo='$kode_wo' AND d.tipe='2'
                                                                                 ORDER BY d.id ASC");
                                                    while($tampil = mysqli_fetch_array($sql)) {
                                                        $no++;
                                                        $total_barang += $tampil['total'];
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no; ?></td>
                                                        <td><?php echo $tampil['kode_barang']; ?></td>
                                                        <td><?php echo $tampil['namaitem']; ?></td>
                                                        <td class="center"><?php echo $tampil['jumlah']; ?></td>
                                                        <td class="right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                    <tr class="info">
                                                        <td colspan="4" class="center"><b>TOTAL BARANG</b></td>
                                                        <td class="right"><b><?php echo number_format($total_barang, 0, ',', '.'); ?></b></td>
                                                    </tr>
                                                    <?php if($no == 0) { ?>
                                                    <tr>
                                                        <td colspan="5" class="center"><em>Tidak ada barang/part</em></td>
                                                    </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-xs-12">
                                            <table class="table table-bordered">
                                                <tr class="warning">
                                                    <td width="15%" class="center"><h4><b><i class="ace-icon fa fa-clock-o"></i> Total Waktu</b></h4></td>
                                                    <td width="20%" class="center">
                                                        <h4><b><?php echo $total_waktu; ?> Menit</b></h4>
                                                    </td>
                                                    <td width="15%" class="center"><h4><b><i class="ace-icon fa fa-money"></i> Grand Total</b></h4></td>
                                                    <td width="50%" class="right">
                                                        <h4><b class="text-primary">Rp <?php echo number_format($total_jasa + $total_barang, 0, ',', '.'); ?></b></h4>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<div class="footer">
				<div class="footer-inner">
					<div class="footer-content">
                        <?php include "../lib/footer.php"; ?>
					</div>
				</div>
			</div>
            
			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<!--[if !IE]> -->
		<script src="assets/js/jquery-2.1.4.min.js"></script>

		<!-- <![endif]-->

		<!--[if IE]>
<script src="assets/js/jquery-1.11.3.min.js"></script>
<![endif]-->
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="assets/js/bootstrap.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>
        
	</body>
</html>

<?php 
	}
?>