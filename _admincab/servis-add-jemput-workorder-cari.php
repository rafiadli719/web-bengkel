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
        
		$no_service=$_GET['snoserv'];
        $_key=$_GET['_key'];
        $_cari=$_GET['_cari'];
        $_urut=$_GET['_urut'];
        $_flt=$_GET['_flt'];
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Dynamic tables and grids using jqGrid plugin" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/chosen.min.css" />
		<link rel="stylesheet" href="assets/css/ace-fonts.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<!--[if lte IE 9]>
			<link rel="stylesheet" href="assets/css/ace-part2.min.css" class="ace-main-stylesheet" />
		<![endif]-->
		<link rel="stylesheet" href="assets/css/ace-skins.min.css" />
		<link rel="stylesheet" href="assets/css/ace-rtl.min.css" />

		<!--[if lte IE 9]>
		  <link rel="stylesheet" href="assets/css/ace-ie.min.css" />
		<![endif]-->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
	</head>

	<body class="no-skin">
		<div id="navbar" class="navbar navbar-default ace-save-state">
			<div class="navbar-container ace-save-state" id="navbar-container">
				<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
					<span class="sr-only">Toggle sidebar</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>

				<div class="navbar-header pull-left">
					<a href="index.php" class="navbar-brand">
						<small>
							<i class="fa fa-leaf"></i>
							<?php include "../lib/subtitel.php"; ?>
						</small>
					</a>
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
			</div>
		</div>

		<div class="main-container ace-save-state" id="main-container">
			<script type="text/javascript">
				try{ace.settings.loadState('main-container')}catch(e){}
			</script>

			<div id="sidebar" class="sidebar responsive ace-save-state">
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
								<a href="#">Servis Jemput</a>
							</li>                            
							<li class="active">Cari Work Order</li>
						</ul>
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Cari Work Order
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Pilih work order untuk service jemput <?php echo $no_service; ?>
								</small>
							</h1>
						</div>

						<div class="row">
							<div class="col-xs-12">
                                <div class="row">
									<div class="col-xs-6">
										<form class="form-search" action="" method="get">
                                            <input type="hidden" name="snoserv" value="<?php echo $no_service; ?>" />
                                            <input type="hidden" name="_urut" value="<?php echo $_urut; ?>" />
                                            <input type="hidden" name="_flt" value="<?php echo $_flt; ?>" />
											<span class="input-icon">
												<input type="text" placeholder="Cari work order ..." class="nav-search-input" id="_cari" name="_cari" value="<?php echo $_cari; ?>" autocomplete="off" />
												<i class="ace-icon fa fa-search nav-search-icon"></i>
											</span>
                                            <input type="text" class="hide" id="_key" name="_key" value="<?php echo $_key; ?>" />
											<input class="btn btn-purple btn-sm" type="submit" value="Cari" />
                                            <a href="servis-input-reguler-jemput-rst.php?snoserv=<?php echo $no_service; ?>" class="btn btn-warning btn-sm">Kembali</a>
										</form>
									</div>
								</div>

								<div class="row">
									<div class="col-xs-12">
										<div class="table-header">
											Results for "<?php echo $_key; ?>"
										</div>

										<div>
											<table id="dynamic-table" class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th class="center" width="3%">No</th>
														<th width="15%">Kode Work Order</th>
														<th width="45%">Nama Work Order</th>
														<th width="20%">Keterangan</th>
														<th width="10%" class="center">Waktu (Menit)</th>
														<th width="7%" class="center">Aksi</th>
													</tr>
												</thead>

												<tbody>
													<?php 
                                                        $no = 0;
                                                        
                                                        if($_cari<>"") {
                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                            kode_wo, nama_wo, keterangan, waktu, harga
                                                                                            FROM tbworkorderheader 
                                                                                            WHERE (kode_wo LIKE '%$_cari%' OR nama_wo LIKE '%$_cari%') 
                                                                                            AND status='0'
                                                                                            ORDER BY kode_wo ASC");
                                                        } else {
                                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                                            kode_wo, nama_wo, keterangan, waktu, harga
                                                                                            FROM tbworkorderheader 
                                                                                            WHERE kode_wo LIKE '%$_key%' OR nama_wo LIKE '%$_key%'
                                                                                            AND status='0'
                                                                                            ORDER BY kode_wo ASC");
                                                        }
                                                        
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                            $no++;
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no ?></td>
                                                        <td><?php echo $tampil['kode_wo']?></td>
                                                        <td><?php echo $tampil['nama_wo']?></td>
                                                        <td><?php echo $tampil['keterangan']?></td>
                                                        <td class="center"><?php echo $tampil['waktu']?></td>
                                                        <td class="center">
                                                            <div class="btn-group">
                                                                <a class="btn btn-success btn-xs" 
                                                                   href="servis-input-reguler-jemput-rst.php?snoserv=<?php echo $no_service; ?>&kdwo=<?php echo $tampil['kode_wo']; ?>">
                                                                    <i class="ace-icon fa fa-check bigger-120"></i>
                                                                    Pilih
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
													<?php
                                                        }
                                                    ?>
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
		</div>

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

		<!-- page specific plugin scripts -->
		<script src="assets/js/jquery.dataTables.min.js"></script>
		<script src="assets/js/jquery.dataTables.bootstrap.min.js"></script>
		<script src="assets/js/dataTables.tableTools.min.js"></script>
		<script src="assets/js/dataTables.colVis.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				//initiate dataTables plugin
				var myTable = 
				$('#dynamic-table')
				.DataTable( {
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null,null, null,
					  { "bSortable": false }
					],
					"aaSorting": [],
					//"iDisplayLength": 50
			    } );
			})
		</script>
	</body>
</html>

<?php 
	}
?>