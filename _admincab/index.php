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
        
		$tgl=date('Y/m/d');
		$tgl_skr=date('d');	
		$bulan_skr=date('m');
		$thn_skr=date('Y');
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
		
		<!-- Custom styles for Antrian Servis Dashboard -->
		<style>
			.huge {
				font-size: 2.5em;
				font-weight: bold;
				margin-bottom: 5px;
			}
			.text-warning { color: #f39c12 !important; }
			.text-info { color: #3498db !important; }
			.text-success { color: #27ae60 !important; }
			.text-muted { color: #95a5a6 !important; }
			.label-purple { background-color: #9b59b6 !important; }
			.progress { height: 20px; margin-bottom: 0; }
			.progress-bar { line-height: 20px; font-size: 11px; font-weight: bold; }
			.widget-box { margin-bottom: 20px; }
			.widget-header { padding: 10px 15px; border-bottom: 1px solid #ddd; }
			.widget-toolbar { float: right; }
			.table-responsive { margin-top: 15px; }
			.table th { background-color: #f8f9fa; font-weight: 600; }
			.btn-xs { padding: 2px 6px; font-size: 11px; }
		</style>
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

				<?php include "menu_dashboard.php"; ?>

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
								<a href="#">Home</a>
							</li>
							<li class="active">Dashboard</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

						<!-- Dashboard Antrian Servis -->
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header">
										<h4 class="header green"><i class="fa fa-list-ol"></i> Dashboard Antrian Servis Hari Ini</h4>
										<div class="widget-toolbar">
											<a href="dashboard-antrian-servis.php" class="btn btn-primary btn-sm">
												<i class="fa fa-external-link"></i> Lihat Detail
											</a>
										</div>
									</div>
									<div class="widget-body">
										<div class="widget-main">
											<?php
											// Ambil statistik antrian servis hari ini
											$tgl_hari_ini = date('Y-m-d');
											
											$query_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini'");
											$total_antrian = mysqli_fetch_array($query_total)['total'];
											
											$query_menunggu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'menunggu'");
											$antrian_menunggu = mysqli_fetch_array($query_menunggu)['total'];
											
											$query_diproses = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'diproses'");
											$antrian_diproses = mysqli_fetch_array($query_diproses)['total'];
											
											$query_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'selesai'");
											$antrian_selesai = mysqli_fetch_array($query_selesai)['total'];
											?>
											
											<!-- Statistik Antrian -->
											<div class="row">
												<div class="col-xs-12 col-sm-3">
													<div class="widget-box">
														<div class="widget-body">
															<div class="widget-main">
																<div class="text-center">
																	<div class="huge"><?php echo $total_antrian; ?></div>
																	<div class="text-muted">Total Antrian</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="col-xs-12 col-sm-3">
													<div class="widget-box">
														<div class="widget-body">
															<div class="widget-main">
																<div class="text-center">
																	<div class="huge text-warning"><?php echo $antrian_menunggu; ?></div>
																	<div class="text-muted">Menunggu</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="col-xs-12 col-sm-3">
													<div class="widget-box">
														<div class="widget-body">
															<div class="widget-main">
																<div class="text-center">
																	<div class="huge text-info"><?php echo $antrian_diproses; ?></div>
																	<div class="text-muted">Diproses</div>
																</div>
															</div>
														</div>
													</div>
												</div>
												<div class="col-xs-12 col-sm-3">
													<div class="widget-box">
														<div class="widget-body">
															<div class="widget-main">
																<div class="text-center">
																	<div class="huge text-success"><?php echo $antrian_selesai; ?></div>
																	<div class="text-muted">Selesai</div>
																</div>
															</div>
														</div>
													</div>
												</div>
											</div>
											
											<!-- Daftar Antrian Terbaru -->
											<div class="row">
												<div class="col-xs-12">
													<h5><i class="fa fa-clock-o"></i> Antrian Terbaru Hari Ini</h5>
													<div class="table-responsive">
														<table class="table table-bordered table-striped">
															<thead>
																<tr>
																	<th>No. Antrian</th>
																	<th>No. Service</th>
																	<th>Jam Ambil</th>
																	<th>Prioritas</th>
																	<th>Status</th>
																	<th>Estimasi</th>
																	<th>Aksi</th>
																</tr>
															</thead>
															<tbody>
																<?php
																					$query_antrian_terbaru = mysqli_query($koneksi, "
						SELECT a.*, p.namapelanggan, s.no_polisi 
						FROM tb_antrian_servis a 
						LEFT JOIN tblservice s ON a.no_service = s.no_service 
						LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
						WHERE a.tanggal = '$tgl_hari_ini' 
						ORDER BY a.created_at DESC 
						LIMIT 10
					");
																
																if(mysqli_num_rows($query_antrian_terbaru) > 0) {
																	while($row = mysqli_fetch_array($query_antrian_terbaru)) {
																		$status_class = '';
																		switch($row['status_antrian']) {
																			case 'menunggu': $status_class = 'label-warning'; break;
																			case 'diproses': $status_class = 'label-info'; break;
																			case 'selesai': $status_class = 'label-success'; break;
																			case 'batal': $status_class = 'label-danger'; break;
																		}
																		
																		$prioritas_class = '';
																		switch($row['prioritas']) {
																			case 'urgent': $prioritas_class = 'label-danger'; break;
																			case 'vip': $prioritas_class = 'label-purple'; break;
																			default: $prioritas_class = 'label-default'; break;
																		}
																		
																		$estimasi = $row['estimasi_waktu'] ? $row['estimasi_waktu'] . ' menit' : '-';
																		?>
																		<tr>
																			<td>
																				<strong><?php echo $row['no_antrian']; ?></strong>
																			</td>
																			<td>
																				<?php echo $row['no_service']; ?><br>
																				<small class="text-muted">
																					<?php echo $row['namapelanggan'] ? $row['namapelanggan'] : 'N/A'; ?> - 
																					<?php echo $row['no_polisi'] ? $row['no_polisi'] : 'N/A'; ?>
																				</small>
																			</td>
																			<td><?php echo date('H:i', strtotime($row['jam_ambil'])); ?></td>
																			<td>
																				<span class="label <?php echo $prioritas_class; ?>">
																					<?php echo ucfirst($row['prioritas']); ?>
																				</span>
																			</td>
																			<td>
																				<span class="label <?php echo $status_class; ?>">
																					<?php echo ucfirst($row['status_antrian']); ?>
																				</span>
																			</td>
																			<td><?php echo $estimasi; ?></td>
																			<td>
																				<a href="servis-input-reguler.php?no_service=<?php echo $row['no_service']; ?>" 
																				   class="btn btn-xs btn-info" title="Lihat Detail">
																					<i class="fa fa-eye"></i>
																				</a>
																			</td>
																		</tr>
																		<?php
																	}
																} else {
																	?>
																	<tr>
																		<td colspan="7" class="text-center text-muted">
																			<i class="fa fa-info-circle"></i> Belum ada antrian servis hari ini
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
											
											<!-- Mekanik yang Sedang Bekerja -->
											<div class="row">
												<div class="col-xs-12">
													<h5><i class="fa fa-users"></i> Mekanik yang Sedang Bekerja</h5>
													<div class="table-responsive">
														<table class="table table-bordered table-striped">
															<thead>
																<tr>
																	<th>Mekanik</th>
																	<th>No. Antrian</th>
																	<th>Progress</th>
																	<th>Status</th>
																	<th>Jam Mulai</th>
																</tr>
															</thead>
															<tbody>
																<?php
																$query_mekanik_bekerja = mysqli_query($koneksi, "
																	SELECT p.*, a.no_antrian, a.no_service, p.nama_mekanik
																	FROM tb_progress_mekanik p
																	JOIN tb_antrian_servis a ON p.no_service = a.no_service
																	WHERE a.tanggal = '$tgl_hari_ini' 
																	AND p.status_kerja = 'bekerja'
																	ORDER BY p.jam_mulai DESC
																	LIMIT 5
																");
																
																if(mysqli_num_rows($query_mekanik_bekerja) > 0) {
																	while($row = mysqli_fetch_array($query_mekanik_bekerja)) {
																		$nama_mekanik = $row['nama_mekanik'] ? $row['nama_mekanik'] : 'Mekanik #' . $row['id_mekanik'];
																		?>
																		<tr>
																			<td>
																				<strong><?php echo $nama_mekanik; ?></strong><br>
																				<small class="text-muted"><?php echo ucfirst($row['jenis_mekanik']); ?></small>
																			</td>
																			<td>
																				<?php echo $row['no_antrian']; ?><br>
																				<small class="text-muted"><?php echo $row['no_service']; ?></small>
																			</td>
																			<td>
																				<div class="progress progress-striped active" style="margin-bottom: 0;">
																					<div class="progress-bar progress-bar-success" style="width: <?php echo $row['persen_kerja']; ?>%">
																						<?php echo $row['persen_kerja']; ?>%
																					</div>
																				</div>
																			</td>
																			<td>
																				<span class="label label-info">Bekerja</span>
																			</td>
																			<td><?php echo date('H:i', strtotime($row['jam_mulai'])); ?></td>
																		</tr>
																		<?php
																	}
																} else {
																	?>
																	<tr>
																		<td colspan="5" class="text-center text-muted">
																			<i class="fa fa-info-circle"></i> Tidak ada mekanik yang sedang bekerja
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

						<!-- Tombol Refresh dan Auto-refresh -->
						<div class="row">
							<div class="col-xs-12 text-center">
								<button class="btn btn-primary btn-lg" onclick="refreshAntrianData()" style="margin: 20px 0;">
									<i class="fa fa-refresh"></i> Refresh Data Antrian
								</button>
								<div class="text-muted">
									<i class="fa fa-info-circle"></i> Data akan di-refresh otomatis setiap 30 detik
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-xs-12 col-sm-6">

							</div><!-- /.col -->
							<div class="col-xs-12 col-sm-6">

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
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/moment.min.js"></script>
		<script src="assets/js/fullcalendar.min.js"></script>
		<script src="assets/js/bootbox.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>
		
		<!-- Custom JavaScript for Antrian Servis Dashboard -->
		<script>
			// Fungsi untuk refresh data antrian menggunakan AJAX
			function refreshAntrianData() {
				var $btn = $('button[onclick="refreshAntrianData()"]');
				var originalText = $btn.html();
				
				$btn.html('<i class="fa fa-spinner fa-spin"></i> Refreshing...');
				$btn.prop('disabled', true);
				
				$.ajax({
					url: '_ajax/ajax-refresh-antrian-dashboard.php',
					type: 'GET',
					dataType: 'json',
					success: function(response) {
						if(response.success) {
							updateDashboardData(response.data);
							showNotification('Data berhasil di-refresh', 'success');
						} else {
							showNotification('Error: ' + response.message, 'error');
						}
					},
					error: function() {
						showNotification('Error: Gagal refresh data', 'error');
					},
					complete: function() {
						setTimeout(function() {
							$btn.html(originalText);
							$btn.prop('disabled', false);
						}, 1000);
					}
				});
			}
			
			// Fungsi untuk update data dashboard
			function updateDashboardData(data) {
				// Update statistik
				$('.huge:contains("Total Antrian")').parent().find('.huge').text(data.statistik.total);
				$('.huge:contains("Menunggu")').parent().find('.huge').text(data.statistik.menunggu);
				$('.huge:contains("Diproses")').parent().find('.huge').text(data.statistik.diproses);
				$('.huge:contains("Selesai")').parent().find('.huge').text(data.statistik.selesai);
				
				// Update timestamp
				$('.text-muted:contains("Data akan di-refresh otomatis")').html(
					'<i class="fa fa-info-circle"></i> Data akan di-refresh otomatis setiap 30 detik<br>' +
					'<small>Last update: ' + data.last_update + '</small>'
				);
			}
			
			// Fungsi untuk menampilkan notifikasi
			function showNotification(message, type) {
				var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
				var $notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade in" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">' +
					'<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
					'<span aria-hidden="true">&times;</span></button>' +
					'<i class="fa fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message +
					'</div>');
				
				$('body').append($notification);
				
				setTimeout(function() {
					$notification.fadeOut(function() {
						$(this).remove();
					});
				}, 3000);
			}
			
			// Auto-refresh setiap 30 detik
			setInterval(function() {
				refreshAntrianData();
			}, 30000);
			
			// Inisialisasi saat halaman load
			$(document).ready(function() {
				// Tambahkan timestamp awal
				$('.text-muted:contains("Data akan di-refresh otomatis")').html(
					'<i class="fa fa-info-circle"></i> Data akan di-refresh otomatis setiap 30 detik<br>' +
					'<small>Last update: ' + new Date().toLocaleTimeString() + '</small>'
				);
			});
		</script>
		
	</body>
</html>

<?php 
	}
?>
