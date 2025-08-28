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
        $jenis_filter = $_GET['jenis'] ?? '';
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
							<li class="active">Master Jasa Service</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<div class="page-header">
							<h1>
								Master Jasa Service
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Kelola data jasa service dan perawatan
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-12">
								<!-- PAGE CONTENT BEGINS -->
                                <div class="row">
									<div class="col-xs-4">
										<form class="form-search" action="" method="get">
											<span class="input-icon">
												<input type="text" placeholder="Cari jasa service ..." class="nav-search-input" 
                                                       name="search" value="<?php echo $search; ?>" autocomplete="off" />
												<i class="ace-icon fa fa-search nav-search-icon"></i>
											</span>
											<input type="hidden" name="jenis" value="<?php echo $jenis_filter; ?>" />
											<input class="btn btn-purple btn-sm" type="submit" value="Cari" />
                                            <a href="jasa-list.php" class="btn btn-default btn-sm">Reset</a>
										</form>
									</div>
                                    <div class="col-xs-4">
                                        <form method="get" class="form-inline">
                                            <select name="jenis" class="form-control input-sm" onchange="this.form.submit()">
                                                <option value="">Semua Jenis</option>
                                                <option value="1" <?php echo ($jenis_filter=='1')?'selected':''; ?>>Jasa Servis</option>
                                                <option value="2" <?php echo ($jenis_filter=='2')?'selected':''; ?>>Jasa Perawatan</option>
                                                <option value="3" <?php echo ($jenis_filter=='3')?'selected':''; ?>>Jasa Perbaikan</option>
                                            </select>
                                            <input type="hidden" name="search" value="<?php echo $search; ?>" />
                                        </form>
                                    </div>
                                    <div class="col-xs-4 text-right">
                                        <a href="jasa-input.php?mode=add" class="btn btn-success">
                                            <i class="ace-icon fa fa-plus"></i>
                                            Tambah Jasa Baru
                                        </a>
                                    </div>
								</div>

								<div class="row">
									<div class="col-xs-12">
										<div class="table-header">
											Master Jasa Service
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
														<th width="12%">Kode Jasa</th>
														<th width="35%">Nama Jasa</th>
														<th width="15%">Jenis</th>
														<th width="10%" class="center">Waktu</th>
														<th width="10%" class="center">Harga</th>
														<th width="10%" class="center">Status</th>
														<th width="5%" class="center">Aksi</th>
													</tr>
												</thead>

												<tbody>
													<?php 
                                                        $no = 0;
                                                        
                                                        $where_clause = "WHERE jasawaktu > 0"; // Filter hanya yang punya waktu jasa
                                                        
                                                        if($search != '') {
                                                            $where_clause .= " AND (noitem LIKE '%$search%' OR namaitem LIKE '%$search%')";
                                                        }
                                                        
                                                        if($jenis_filter != '') {
                                                            $where_clause .= " AND jenis_jasa = '$jenis_filter'";
                                                        }
                                                        
                                                        $sql = mysqli_query($koneksi,"SELECT * FROM tblitem 
                                                                                      $where_clause
                                                                                      ORDER BY noitem ASC");
                                                        
                                                        while ($tampil = mysqli_fetch_array($sql)) {
                                                            $no++;
                                                            
                                                            // Determine jenis jasa
                                                            $jenis_jasa = '';
                                                            switch($tampil['jenis_jasa']) {
                                                                case '1': $jenis_jasa = 'Jasa Servis'; break;
                                                                case '2': $jenis_jasa = 'Jasa Perawatan'; break;
                                                                case '3': $jenis_jasa = 'Jasa Perbaikan'; break;
                                                                default: $jenis_jasa = 'Jasa Umum'; break;
                                                            }
                                                            
                                                            // Status
                                                            $status_label = ($tampil['statusitem'] == '1') ? 'Aktif' : 'Non-Aktif';
                                                            $status_class = ($tampil['statusitem'] == '1') ? 'label-success' : 'label-danger';
                                                    ?>
                                                    <tr>
                                                        <td class="center"><?php echo $no ?></td>
                                                        <td>
                                                            <span class="label label-info"><?php echo $tampil['noitem']?></span>
                                                        </td>
                                                        <td>
                                                            <b><?php echo $tampil['namaitem']?></b>
                                                            <?php if($tampil['note']) { ?>
                                                            <br><small class="text-muted"><?php echo $tampil['note']; ?></small>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo $jenis_jasa; ?></td>
                                                        <td class="center">
                                                            <span class="badge badge-info">
                                                                <?php echo $tampil['jasawaktu']?> 
                                                                <?php echo ($tampil['jasasatuanwaktu']=='1')?'menit':'jam'; ?>
                                                            </span>
                                                        </td>
                                                        <td class="right">
                                                            <b>Rp <?php echo number_format($tampil['hargajual'], 0, ',', '.')?></b>
                                                        </td>
                                                        <td class="center">
                                                            <span class="label <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                                        </td>
                                                        <td class="center">
                                                            <div class="btn-group">
                                                                <button data-toggle="dropdown" class="btn btn-xs btn-primary dropdown-toggle">
                                                                    <i class="ace-icon fa fa-cog"></i>
                                                                    <i class="ace-icon fa fa-caret-down icon-only"></i>
                                                                </button>

                                                                <ul class="dropdown-menu dropdown-menu-right">
                                                                    <li>
                                                                        <a href="jasa-input.php?mode=edit&kode=<?php echo $tampil['noitem']; ?>">
                                                                            <i class="ace-icon fa fa-pencil"></i>
                                                                            Edit
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="jasa-detail.php?kode=<?php echo $tampil['noitem']; ?>">
                                                                            <i class="ace-icon fa fa-eye"></i>
                                                                            Lihat Detail
                                                                        </a>
                                                                    </li>
                                                                    <li class="divider"></li>
                                                                    <li>
                                                                        <a href="jasa-hapus.php?kode=<?php echo $tampil['noitem']; ?>" 
                                                                           onclick="return confirm('Hapus jasa service ini?')"
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
                                                        <td colspan="8" class="center">
                                                            <em>Tidak ada data jasa service</em>
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
					  null, null, null, null, null, null,
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