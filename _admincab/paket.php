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

        // Filter pencarian
        $search = $_GET['search'] ?? '';
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

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!-- HTML5shiv and Respond.js for IE8 to support HTML5 elements and media queries -->

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
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

<?php include "menu_master01h.php"; ?>

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
							<li class="active">Daftar Work Order</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Daftar Work Order (Paket Servis)
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Kelola paket servis dengan kombinasi jasa dan barang
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- Notifications -->
								<?php if(isset($_GET['success'])): ?>
									<div class="alert alert-success alert-dismissible">
										<button type="button" class="close" data-dismiss="alert">&times;</button>
										<i class="ace-icon fa fa-check"></i> <?php echo htmlspecialchars($_GET['success']); ?>
									</div>
								<?php endif; ?>
								<?php if(isset($_GET['error'])): ?>
									<div class="alert alert-danger alert-dismissible">
										<button type="button" class="close" data-dismiss="alert">&times;</button>
										<i class="ace-icon fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
									</div>
								<?php endif; ?>
								<!-- PAGE CONTENT BEGINS -->
                                <div class="row">
									<div class="col-xs-6">
										<form class="form-search" action="" method="get">
											<span class="input-icon">
												<input type="text" placeholder="Cari work order ..." class="nav-search-input" 
                                                       name="search" value="<?php echo $search; ?>" autocomplete="off" />
												<i class="ace-icon fa fa-search nav-search-icon"></i>
											</span>
											<input class="btn btn-purple btn-sm" type="submit" value="Cari" />
                                            <a href="workorder-list.php" class="btn btn-default btn-sm">Reset</a>
										</form>
									</div>
                                    <div class="col-xs-6 text-right">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                                <i class="ace-icon fa fa-download"></i>
                                                Export
                                                <i class="ace-icon fa fa-caret-down"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a href="workorder-export.php?format=excel&search=<?php echo urlencode($search); ?>">
                                                        <i class="ace-icon fa fa-file-excel-o"></i>
                                                        Export Excel
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="workorder-export.php?format=csv&search=<?php echo urlencode($search); ?>">
                                                        <i class="ace-icon fa fa-file-text-o"></i>
                                                        Export CSV
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <a href="workorder-input.php?mode=add" class="btn btn-success">
                                            <i class="ace-icon fa fa-plus"></i>
                                            Tambah Work Order Baru
                                        </a>
                                    </div>
								</div>

								<div class="row">
									<div class="col-xs-12">
										<div class="table-header">
											Daftar Work Order
										</div>

										<!-- div.table-responsive -->

										<!-- div.dataTables_borderWrap -->
										<div>
											<table id="dynamic-table" class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th class="center" width="3%">
															No
														</th>
														<th width="12%">Kode Work Order</th>
														<th width="35%">Nama Work Order</th>
														<th width="25%">Keterangan</th>
														<th width="8%" class="center">Waktu</th>
														<th width="12%" class="center">Harga</th>
														<th width="5%" class="center">Aksi</th>
													</tr>
												</thead>

												<tbody>
													<?php 
                                                        $no = 0;
                                                        
                                                        if($search != '') {
                                                            $sql = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader 
                                                                                            WHERE (kode_wo LIKE '%$search%' OR nama_wo LIKE '%$search%') 
                                                                                            AND status='0'
                                                                                            ORDER BY kode_wo ASC");
                                                        } else {
                                                            $sql = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader 
                                                                                            WHERE status='0'
                                                                                            ORDER BY kode_wo ASC");
                                                        }
                                                        
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                            $no++;
                                                            
                                                            // Hitung jumlah detail
                                                            $detail_query = mysqli_query($koneksi,"SELECT COUNT(*) as total_detail FROM tbworkorderdetail WHERE kode_wo='{$tampil['kode_wo']}'");
                                                            $detail_data = mysqli_fetch_array($detail_query);
                                                            $total_detail = $detail_data['total_detail'];
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no ?></td>
                                                        <td>
                                                            <span class="label label-info"><?php echo $tampil['kode_wo']?></span>
                                                        </td>
                                                        <td>
                                                            <b><?php echo $tampil['nama_wo']?></b>
                                                            <br>
                                                            <small class="text-muted">
                                                                <i class="ace-icon fa fa-list"></i>
                                                                <?php echo $total_detail; ?> item detail
                                                            </small>
                                                        </td>
                                                        <td><?php echo $tampil['keterangan']?></td>
                                                        <td class="center">
                                                            <span class="badge badge-info"><?php echo $tampil['waktu']?> menit</span>
                                                        </td>
                                                        <td class="right">
                                                            <b>Rp <?php echo number_format($tampil['harga'], 0, ',', '.')?></b>
                                                        </td>
                                                        <td class="center">
                                                            <div class="btn-group">
                                                                <button data-toggle="dropdown" class="btn btn-xs btn-primary dropdown-toggle">
                                                                    <i class="ace-icon fa fa-cog"></i>
                                                                    <i class="ace-icon fa fa-caret-down icon-only"></i>
                                                                </button>

                                                                <ul class="dropdown-menu dropdown-menu-right">
                                                                    <li>
                                                                        <a href="workorder-input.php?mode=edit&kode=<?php echo $tampil['kode_wo']; ?>">
                                                                            <i class="ace-icon fa fa-pencil"></i>
                                                                            Edit
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="workorder-detail-view.php?kode=<?php echo $tampil['kode_wo']; ?>">
                                                                            <i class="ace-icon fa fa-eye"></i>
                                                                            Lihat Detail
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="workorder-print.php?kode=<?php echo $tampil['kode_wo']; ?>" target="_blank">
                                                                            <i class="ace-icon fa fa-print"></i>
                                                                            Print Work Order
                                                                        </a>
                                                                    </li>
                                                                    <li class="divider"></li>
                                                                    <li>
                                                                        <a href="workorder-hapus-master.php?kode=<?php echo $tampil['kode_wo']; ?>" 
                                                                           onclick="return confirm('Hapus work order ini beserta semua detailnya?')"
                                                                           class="text-danger">
                                                                            <i class="ace-icon fa fa-trash-o"></i>
                                                                            Hapus
                                                                        </a>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </td>
                                                    </tr>
													<?php
                                                        }
                                                        if($no == 0) {
                                                    ?>
                                                    <tr>
                                                        <td colspan="7" class="center">
                                                            <em>Tidak ada data work order</em>
                                                        </td>
                                                    </tr>
                                                    <?php } ?>
												</tbody>
											</table>
										</div>
									</div>
								</div>

								<!-- PAGE CONTENT ENDS -->
							</div><!-- /.col -->
						</div><!-- /.row -->
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
				//.wrap("<div class='dataTables_borderWrap' />")   //if you are applying horizontal scrolling (sScrollX)
				.DataTable( {
					bAutoWidth: false,
					"aoColumns": [
					  { "bSortable": false },
					  null, null, null, null, null,
					  { "bSortable": false }
					],
					"aaSorting": [],
					
					//"bProcessing": true,
			        //"bServerSide": true,
			        //"sAjaxSource": "http://127.0.0.1/table.php"	,

					//,
					//"sScrollY": "200px",
					//"bPaginate": false,

					//"sScrollX": "100%",
					//"sScrollXInner": "120%",
					//"bScrollCollapse": true,
					//Note: if you are applying horizontal scrolling (sScrollX) on a ".table-bordered"
					//you may want to wrap the table inside a "div.dataTables_borderWrap" element

					//"iDisplayLength": 50
			    } );
			
			})
		</script>
	</body>
</html>

<?php 
	}
?>