<?php
	session_start();
	
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";
		include_once "../lib/rbac.php";
		rbac_require_any(array('lihat_servis_garansi_read','servis_garansi_read','servis_menu_read','service_read'));
		include "_include_customer_vehicle_sync.php";
		include "_include_statistik_pelanggan.php";
		include "_include_kategori_member.php"; // Member kategori & discount helper
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";
        
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
		
		// Set username session if not exists to prevent login redirect
		if(!isset($_SESSION['username'])) {
			$_SESSION['username'] = $_nama;
		}

    // ------- Data Cabang ----------
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';				        
        $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';	
    // --------------------				        
    
        $no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : '';
    $txtcaribrg=mysqli_real_escape_string($koneksi, $_GET['kd'] ?? '');
    $txtcarisrv=mysqli_real_escape_string($koneksi, $_GET['kdjasa'] ?? '');
    $txtcariwo=mysqli_real_escape_string($koneksi, $_GET['kdwo'] ?? '');
    
// [READ-ONLY] Save handler removed
    $is_readonly_wo = true; // halaman garansi selesai: tombol cari/tambah Work Order dinonaktifkan (tidak ada handler POST)
    
    // Get service data if exists
    if(!empty($no_service)) {
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        tanggal,
                                        DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_serv, 
                                        jam, no_pelanggan, no_polisi, status_servis 
                                        FROM tblservice 
                                        WHERE no_service='$no_service'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal=$tm_cari['tanggal_serv'] ?? '';
        $tanggal_srv=$tm_cari['tanggal'] ?? '';                
		$jam=$tm_cari['jam'] ?? '';        
		$kode_pelanggan=$tm_cari['no_pelanggan'] ?? '';        
		$no_polisi=$tm_cari['no_polisi'] ?? '';        
        $status_servis=$tm_cari['status_servis'] ?? 'datang';        
    } else {
        $tanggal = date('d/m/Y');
        $jam = date('H:i');
        $kode_pelanggan = '';
        $no_polisi = '';
        $status_servis = 'datang';
    }
                
    // Get customer data
    if(!empty($kode_pelanggan)) {
        $customerBundle = fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $no_polisi);
        $namapelanggan=$customerBundle['namapelanggan'] ?? '';
    } else {
        $namapelanggan = '';
    }

    // Get vehicle data
    if(!empty($no_polisi)) {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $customerRow = $bundle['customer'] ?? [];
        $pemilik=$vehicleRow['pemilik'] ?? '';
        $jenis=$vehicleRow['jenis'] ?? '';
        $merek=$vehicleRow['merek'] ?? ($vehicleRow['tipe'] ?? '');
        $warna=$vehicleRow['warna'] ?? '';
        $no_rangka=$vehicleRow['no_rangka'] ?? '';
        $no_mesin=$vehicleRow['no_mesin'] ?? '';
        if (empty($namapelanggan) && !empty($customerRow['namapelanggan'])) {
            $namapelanggan = $customerRow['namapelanggan'];
            if (empty($kode_pelanggan) && !empty($customerRow['nopelanggan'])) {
                $kode_pelanggan = $customerRow['nopelanggan'];
            }
        }
    } else {
        $pemilik = '';
        $jenis = '';
        $merek = '';
        $warna = '';
        $no_rangka = '';
        $no_mesin = '';
    }
        
        $km_skr="";
        $km_berikut="";
        
        // Initialize mechanic variables
        $kepala_mekanik1 = "";
        $persen_kepala1 = 0;
        $kepala_mekanik2 = "";
        $persen_kepala2 = 0;
    $admin1 = "";
    $persen_admin1 = 0;
    $admin2 = "";
    $persen_admin2 = 0;
        $mekanik1 = "";
    $persen_kerja1 = 0;
        $mekanik2 = "";
    $persen_kerja2 = 0;
        $mekanik3 = "";
    $persen_kerja3 = 0;
        $mekanik4 = "";
    $persen_kerja4 = 0;
    
    // Initialize additional variables for templates
    $txtcaribrg = mysqli_real_escape_string($koneksi, $_GET['kd'] ?? '');
    $txtcarisrv = mysqli_real_escape_string($koneksi, $_GET['kdjasa'] ?? '');
    $txtnamaitem = '';
    $txtnamasrv = '';
    
    // Get item data if searching for item
    if(!empty($txtcaribrg)) {
        $cari_item = mysqli_query($koneksi,"SELECT namaitem FROM view_cari_item WHERE noitem='$txtcaribrg'");
        $tm_item = mysqli_fetch_array($cari_item);
        $txtnamaitem = $tm_item['namaitem'] ?? '';
    }
    
    // Get service data if searching for service
    if(!empty($txtcarisrv)) {
        $cari_serv = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcarisrv'");
        $tm_serv = mysqli_fetch_array($cari_serv);
        $txtnamasrv = $tm_serv['nama_wo'] ?? '';
    }

    // Initialize workorder name variable
    $txtnamawo = '';
    if(!empty($txtcariwo)) {
        $cari_wo = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcariwo'");
        $tm_wo = mysqli_fetch_array($cari_wo);
        $txtnamawo = $tm_wo['nama_wo'] ?? '';
    }

// [READ-ONLY] Update handlers removed
    
    // Get existing mechanic data if service exists
    if(!empty($no_service)) {
        // Try to get data from existing service table
        $query_existing = "SELECT * FROM tblservice WHERE no_service='$no_service'";
        $result_existing = mysqli_query($koneksi, $query_existing);
        if($result_existing && mysqli_num_rows($result_existing) > 0) {
            $existing_data = mysqli_fetch_array($result_existing);
            // Initialize with empty values if columns don't exist
            $kepala_mekanik1 = $existing_data['kepala_mekanik1'] ?? '';
            $persen_kepala1 = $existing_data['persen_kepala1'] ?? 0;
            $kepala_mekanik2 = $existing_data['kepala_mekanik2'] ?? '';
            $persen_kepala2 = $existing_data['persen_kepala2'] ?? 0;
            $admin1 = $existing_data['admin1'] ?? '';
            $persen_admin1 = $existing_data['persen_admin1'] ?? 0;
            $admin2 = $existing_data['admin2'] ?? '';
            $persen_admin2 = $existing_data['persen_admin2'] ?? 0;
            $mekanik1 = $existing_data['mekanik1'] ?? '';
            $persen_kerja1 = $existing_data['persen_kerja1'] ?? 0;
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_kerja2 = $existing_data['persen_kerja2'] ?? 0;
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_kerja3 = $existing_data['persen_kerja3'] ?? 0;
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_kerja4 = $existing_data['persen_kerja4'] ?? 0;
        }
    }
    
// [READ-ONLY] Payment handler removed
    
    // Initialize other variables

    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';
    $prioritas_wo = 'urgent'; // Garansi usually urgent
    $deskripsi_pekerjaan = '';
    $instruksi_khusus = '';
    $catatan_wo = '';
    
    // Get additional service data if exists
    if(!empty($no_service)) {
        // Get data from existing service table if available
        $query_service_detail = "SELECT * FROM tblservice WHERE no_service='$no_service'";
        $result_service_detail = mysqli_query($koneksi, $query_service_detail);
        if($result_service_detail && mysqli_num_rows($result_service_detail) > 0) {
            $service_detail = mysqli_fetch_array($result_service_detail);
            // Use existing data from tblservice if available
            $keluhan = $service_detail['keluhan'] ?? '';
            $catatan = $service_detail['catatan'] ?? '';
            $no_workorder = $service_detail['no_workorder'] ?? '';
            $tanggal_wo = $service_detail['tanggal_wo'] ?? date('d/m/Y');
            $estimasi_selesai = $service_detail['estimasi_selesai'] ?? '';
            $prioritas_wo = $service_detail['prioritas_wo'] ?? 'urgent';
            $deskripsi_pekerjaan = $service_detail['deskripsi_pekerjaan'] ?? '';
            $instruksi_khusus = $service_detail['instruksi_khusus'] ?? '';
            $catatan_wo = $service_detail['catatan_wo'] ?? '';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Input Service Garansi" />
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

		<!-- ace settings handler -->
		<script src="assets/js/ace-extra.min.js"></script>

		<!--[if lte IE 8]>
		<script src="assets/js/html5shiv.min.js"></script>
		<script src="assets/js/respond.min.js"></script>
		<![endif]-->
		<script src="assets/js/jquery-2.1.4.min.js"></script>
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
								<a href="index.php">Home</a>
							</li>
                            <li>
								<a href="#">Service</a>
							</li>
							<li class="active">Detail Service Garansi (Selesai)</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

                        <div class="alert alert-success">
                            <i class="ace-icon fa fa-check-circle green bigger-130"></i>
                            <strong>Servis garansi ini sudah SELESAI.</strong>
                            Halaman ini hanya untuk melihat &amp; mencetak ulang data (mode lihat saja, tidak bisa diedit lagi).
                        </div>

                        <form class="form-horizontal" action="" method="post" role="form" enctype="multipart/form-data">
						<!-- Hidden fields for form submission -->
						<input type="hidden" name="txtnosrv" id="txtnosrv" value="<?php echo $no_service; ?>" />
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-blue widget-header-flat">
										<h4 class="widget-title lighter">
											<i class="ace-icon fa fa-shield orange"></i>
											Detail Service Garansi <?php echo $no_service ? '#'.$no_service : ''; ?>
										</h4>
                                        <div class="widget-toolbar">
                                            <span class="label label-success arrowed-in arrowed-in-right">Selesai</span>
                                        </div>
									</div>

									<div class="widget-body">
										<div class="widget-main padding-12 no-padding-left no-padding-right">
											<div class="tabbable">
												<ul class="nav nav-tabs" id="myTab">
													<li class="active">
														<a data-toggle="tab" href="#service-details" aria-expanded="true">
															<i class="green ace-icon fa fa-info-circle bigger-120"></i>
															Detail Service
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#workorder-details" aria-expanded="false">
															<i class="blue ace-icon fa fa-clipboard bigger-120"></i>
															Work Order
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#temuan-penawaran" aria-expanded="false">
															<i class="red ace-icon fa fa-clipboard-check bigger-120"></i>
															Temuan & Penawaran
															<?php
															$count_temuan = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM tbservis_temuan WHERE no_service='$no_service'"));
															$count_penawaran_pending = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM tbservis_penawaran_part WHERE no_service='$no_service' AND status_penawaran='pending'"));
															if($count_temuan > 0 || $count_penawaran_pending > 0) {
															?>
															<span class="badge badge-warning"><?php echo $count_temuan + $count_penawaran_pending; ?></span>
															<?php } ?>
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#service-items" aria-expanded="false">
															<i class="orange ace-icon fa fa-truck bigger-120"></i>
															Item Barang
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#service-jasa" aria-expanded="false">
															<i class="purple ace-icon fa fa-cogs bigger-120"></i>
															Item Jasa
														</a>
													</li>

													<li class="">
														<a data-toggle="tab" href="#service-actions" aria-expanded="false">
															<i class="grey ace-icon fa fa-cogs bigger-120"></i>
															Actions
														</a>
													</li>
												</ul>

												<div class="tab-content">
													<div id="service-details" class="tab-pane fade active in">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<div class="row">
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> No. Service :</label>													
																				<div class="col-sm-8">
																					<input type="text" id="txtnoservice" name="txtnoservice" class="form-control" 
																					value="<?php echo $no_service; ?>" readonly="true" />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Tanggal :</label>													
																				<div class="col-sm-8">
																					<div class="input-group">
																						<input class="form-control date-picker" id="id-date-picker-1" name="id-date-picker-1" type="text" autocomplete="off" 
																						value="<?php echo $tanggal; ?>" data-date-format="dd/mm/yyyy" />
																						<span class="input-group-addon">
																							<i class="fa fa-calendar bigger-110"></i>
																						</span>
																					</div>
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Jam :</label>													
																				<div class="col-sm-8">
																					<input type="time" class="form-control" name="jam_service" id="jam_service" 
																					value="<?php echo $jam; ?>" />
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> User :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" 
																					value="<?php echo $_nama; ?>" disabled />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> Nama Pelanggan :</label>
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="namapelanggan" id="namapelanggan"
																					value="<?php echo $namapelanggan; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
										<label class="col-sm-3 control-label no-padding-right"> Statistik :</label>
										<div class="col-sm-9">
											<button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalStatistikPelanggan" style="text-align: left; position: relative; padding: 12px 15px;" <?php echo empty($kode_pelanggan) ? 'disabled' : ''; ?>>
												<i class="fa fa-bar-chart"></i> <strong>Lihat Statistik Pelanggan Lengkap</strong>
												<span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%);">
													<i class="fa fa-arrow-circle-right"></i>
												</span>
												<br>
												<small style="color: #e3f2fd;">
													Kategori Member, Kendaraan, Riwayat Transaksi, dll
												</small>
											</button>
										</div>
									</div>
																		</div>
																	</div>
																	
																	<div class="hr hr-24"></div>
																	
																	<div class="row">
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> No. Polisi :</label>													
																				<div class="col-sm-8">
																					<input type="text" class="form-control" name="no_polisi" id="no_polisi" 
																					value="<?php echo $no_polisi; ?>" placeholder="Nomor polisi..." />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Jenis :</label>													
																				<div class="col-sm-8">
																					<input type="text" class="form-control" name="jenis" id="jenis" 
																					value="<?php echo $jenis; ?>" readonly />
																				</div>
																			</div>
																		</div>
																		<div class="col-xs-12 col-sm-6">
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> Merek :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="merek" id="merek" 
																					value="<?php echo $merek; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> Warna :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="warna" id="warna" 
																					value="<?php echo $warna; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> No. Rangka :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="no_rangka" id="no_rangka" 
																					value="<?php echo $no_rangka; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-3 control-label no-padding-right"> No. Mesin :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="no_mesin" id="no_mesin" 
																					value="<?php echo $no_mesin; ?>" readonly />
																				</div>
																			</div>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													</div>

													<div id="workorder-details" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<?php include "_template/_servis_add_header_kanan_workorder_only.php"; ?>
																	
																	<!-- Modal Riwayat Kendaraan -->
																	<?php include "_template/_modal_riwayat_kendaraan.php"; ?>
																</div>
															</div>
														</div>
													</div>

													<div id="temuan-penawaran" class="tab-pane fade">
								<div class="row">
									<div class="col-xs-12">
										<div class="padding-18">
											<?php include "_template/tab-temuan-penawaran-content.php"; ?>
										</div>
									</div>
								</div>
							</div>

							<div id="service-items" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<h4 class="orange">
																		<i class="ace-icon fa fa-cubes"></i>
																		Item Barang - Service Garansi
																	</h4>
																	<?php 
																	// Calculate total barang for garansi
																	$total_barang = 0;
																	if(!empty($no_service)) {
																		$sql_barang = mysqli_query($koneksi,"SELECT SUM(total) as total_barang FROM tblservis_barang WHERE no_service='$no_service'");
																		$data_barang = mysqli_fetch_array($sql_barang);
																		$total_barang = $data_barang['total_barang'] ?? 0;
																	}
																	include "_template/_servis_garansi_detail_barang.php"; 
																	?>
																</div>
															</div>
														</div>
													</div>

													<div id="service-jasa" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<h4 class="purple">
																		<i class="ace-icon fa fa-cogs"></i>
																		Item Jasa - Service Garansi
																	</h4>
																	<?php 
																	// Calculate total service for garansi
																	$total_service = 0;
																	if(!empty($no_service)) {
																		$sql_service = mysqli_query($koneksi,"SELECT SUM(total) as total_service FROM tblservis_jasa WHERE no_service='$no_service'");
																		$data_service = mysqli_fetch_array($sql_service);
																		$total_service = $data_service['total_service'] ?? 0;
																	}
																	include "_template/_servis_garansi_detail_servis.php"; 
																	?>
																</div>
															</div>
														</div>
													</div>

													<!-- TAB: Temuan & Penawaran -->
													<div id="temuan-penawaran" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<?php include "_template/tab-temuan-penawaran-content.php"; ?>
																</div>
															</div>
														</div>
													</div>

													<div id="service-actions" class="tab-pane fade">
										<?php include "_template/_servis_actions_tab_garansi_rst.php"; ?>
									</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
                        </div>
                        </form>                            
                        </div>

                        </form>

					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<div class="footer">
				<div class="footer-inner">
					<div class="footer-content">
                        <?php include "_template/_servis_footer_tabs_rst.php"; ?>
					</div>
				</div>
			</div>

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->

		<!-- basic scripts -->

		<script type="text/javascript">
			if(!window.jQuery){
				document.write("<script src='assets/js/jquery-2.1.4.min.js'>"+"<"+"/script>");
			}
		</script>
		<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="assets/js/bootstrap.min.js"></script>

		<!-- page specific plugin scripts -->

		<!--[if lte IE 8]>
		  <script src="assets/js/excanvas.min.js"></script>
		<![endif]-->
		<script src="assets/js/jquery-ui.custom.min.js"></script>
		<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
		<script src="assets/js/chosen.jquery.min.js"></script>
		<script src="assets/js/spinbox.min.js"></script>
		<script src="assets/js/bootstrap-datepicker.min.js"></script>
		<script src="assets/js/bootstrap-timepicker.min.js"></script>
		<script src="assets/js/moment.min.js"></script>
		<script src="assets/js/daterangepicker.min.js"></script>
		<script src="assets/js/bootstrap-datetimepicker.min.js"></script>
		<script src="assets/js/bootstrap-colorpicker.min.js"></script>
		<script src="assets/js/jquery.knob.min.js"></script>
		<script src="assets/js/autosize.min.js"></script>
		<script src="assets/js/jquery.inputlimiter.min.js"></script>
		<script src="assets/js/jquery.maskedinput.min.js"></script>
		<script src="assets/js/bootstrap-tag.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- Callback Functions - MUST be loaded BEFORE modals -->
		<?php include '_template/modal-callbacks.php'; ?>

		<!-- Modals untuk Temuan & Penawaran -->
		<?php include '_template/modal-search-temuan.php'; ?>
		<?php include '_template/modal-fastmoves-v2.php'; ?>
		<?php include '_template/modal-tambah-keluhan-baru.php'; ?>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				
				$('#id-disable-check').on('click', function() {
					var inp = $('#form-input-readonly').get(0);
					if(inp.hasAttribute('disabled')) {
						inp.setAttribute('readonly' , 'true');
						inp.removeAttribute('disabled');
						inp.value="This text field is readonly!";
					}
					else {
						inp.setAttribute('disabled' , 'disabled');
						inp.removeAttribute('readonly');
						inp.value="This text field is disabled!";
					}
				});
			
			
				if(!ace.vars['touch']) {
					$('.chosen-select').chosen({allow_single_deselect:true}); 
					//resize the chosen on window resize
			
					$(window)
					.off('resize.chosen')
					.on('resize.chosen', function() {
						$('.chosen-select').each(function() {
							 var $this = $(this);
							 $this.next().css({'width': $this.parent().width()});
						})
					}).trigger('resize.chosen');
					//resize chosen on sidebar collapse/expand
					$(document).on('settings.ace.chosen', function(e, event_name, event_val) {
						if(event_name != 'sidebar_collapsed') return;
						$('.chosen-select').each(function() {
							 var $this = $(this);
							 $this.next().css({'width': $this.parent().width()});
						})
					});
				}

				$('[data-rel=tooltip]').tooltip({container:'body'});
				$('[data-rel=popover]').popover({container:'body'});
			
				autosize($('textarea[class*=autosize]'));
			
				//datepicker plugin
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true
				})
				//show datepicker when clicking on the icon
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
			});
		</script>

<!-- Initialize JavaScript Arrays for Existing Items -->
<script type="text/javascript">
    // Initialize item arrays
    var itemBarangList = [];
    var itemJasaList = [];
    var itemBarangCounter = 0;
    var itemJasaCounter = 0;

    // Load existing items from database
    $(document).ready(function() {
        loadExistingItems();
    });

    // Function to load existing items from database
    function loadExistingItems() {
        var noService = '<?php echo $no_service; ?>';
        console.log('Loading existing items for service:', noService);
        if (!noService) {
            console.log('No service number found');
            return;
        }

        // Load existing barang items
        $.ajax({
            url: '_ajax/ajax-load-existing-items.php',
            type: 'POST',
            data: {
                no_service: noService,
                type: 'barang'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Barang AJAX response:', response);
                if (response.success && response.items) {
                    itemBarangList = response.items;
                    // Update counter to be the highest ID + 1
                    itemBarangCounter = itemBarangList.length > 0 ? Math.max(...itemBarangList.map(item => item.id)) : 0;
                    console.log('Loaded', itemBarangList.length, 'barang items');
                    updateItemBarangTable();
                } else {
                    console.log('No barang items found or error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading existing barang items:', error);
                console.log('Status:', status);
                console.log('Response:', xhr.responseText);
            }
        });

        // Load existing jasa items
        $.ajax({
            url: '_ajax/ajax-load-existing-items.php',
            type: 'POST',
            data: {
                no_service: noService,
                type: 'jasa'
            },
            dataType: 'json',
            success: function(response) {
                console.log('Jasa AJAX response:', response);
                if (response.success && response.items) {
                    itemJasaList = response.items;
                    // Update counter to be the highest ID + 1
                    itemJasaCounter = itemJasaList.length > 0 ? Math.max(...itemJasaList.map(item => item.id)) : 0;
                    console.log('Loaded', itemJasaList.length, 'jasa items');
                    updateItemJasaTable();
                } else {
                    console.log('No jasa items found or error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error loading existing jasa items:', error);
                console.log('Status:', status);
                console.log('Response:', xhr.responseText);
            }
        });
    }

    // Add update functions that match the template style
    function updateItemBarangTable() {
        var tbody = document.getElementById('itemBarangList');
        if (!tbody) return;

        var total = 0;
        tbody.innerHTML = '';

        itemBarangList.forEach(function(item, index) {
            var row = tbody.insertRow();
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.kode}</td>
                <td>${item.nama}</td>
                <td>${item.qty}</td>
                <td>${formatCurrency(item.harga)}</td>
                <td>${formatCurrency(item.subtotal)}</td>
                <td>
                    <button type="button" class="btn btn-xs btn-danger" onclick="removeItemBarang(${item.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            `;
            total += item.subtotal;
        });

        if (document.getElementById('totalItemBarang')) {
            document.getElementById('totalItemBarang').textContent = formatCurrency(total);
        }
    }

    function updateItemJasaTable() {
        var tbody = document.getElementById('itemJasaList');
        if (!tbody) return;

        var total = 0;
        tbody.innerHTML = '';

        itemJasaList.forEach(function(item, index) {
            var row = tbody.insertRow();
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>${item.kode}</td>
                <td>${item.nama}</td>
                <td>${item.qty}</td>
                <td>${formatCurrency(item.harga)}</td>
                <td>${formatCurrency(item.subtotal)}</td>
                <td>
                    <button type="button" class="btn btn-xs btn-danger" onclick="removeItemJasa(${item.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            `;
            total += item.subtotal;
        });

        if (document.getElementById('totalItemJasa')) {
            document.getElementById('totalItemJasa').textContent = formatCurrency(total);
        }
    }

    function removeItemBarang(id) {
        itemBarangList = itemBarangList.filter(item => item.id !== id);
        updateItemBarangTable();
    }

    function removeItemJasa(id) {
        itemJasaList = itemJasaList.filter(item => item.id !== id);
        updateItemJasaTable();
    }

    function formatCurrency(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
</script>

<!-- ========== MODAL STATISTIK PELANGGAN ========== -->
<?php 
if(!empty($kode_pelanggan)) {
    echo renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
}
?>
<!-- ========== END MODAL STATISTIK PELANGGAN ========== -->

<!-- ========== SCRIPT MODAL STATISTIK PELANGGAN ========== -->
<script type="text/javascript">
setTimeout(function() {
    (function() {
        'use strict';
        
        console.log('ðŸš€ Starting Modal Statistik Pelanggan (Garansi) initialization...');
        
        function initModalStatistikPelanggan() {
            if (typeof jQuery === 'undefined') {
                console.error('jQuery belum load, retry dalam 100ms...');
                setTimeout(initModalStatistikPelanggan, 100);
                return;
            }
        
        jQuery(document).ready(function($) {
            console.log('=== INIT MODAL STATISTIK PELANGGAN (GARANSI) ===');
            
            if (typeof $.fn.modal === 'undefined') {
                console.error('âŒ Bootstrap Modal plugin TIDAK ditemukan!');
                return;
            } else {
                console.log('âœ… Bootstrap Modal plugin ditemukan');
            }
            
            var btnStatistik = $('button[data-target="#modalStatistikPelanggan"]');
            console.log('ðŸ” Jumlah tombol ditemukan: ' + btnStatistik.length);
            
            if(btnStatistik.length > 0) {
                console.log('âœ… Tombol Statistik Pelanggan ditemukan');
            }
            
            var modal = $('#modalStatistikPelanggan');
            console.log('ðŸ” Jumlah modal ditemukan: ' + modal.length);
            
            if(modal.length > 0) {
                console.log('âœ… Modal Statistik Pelanggan ditemukan');
                
                modal.modal({
                    backdrop: 'static',
                    keyboard: true,
                    show: false
                });
                
                modal.on('show.bs.modal', function (e) {
                    console.log('ðŸ“¢ EVENT: Modal akan dibuka');
                });
                
                modal.on('shown.bs.modal', function (e) {
                    console.log('ðŸ“¢ EVENT: Modal sudah tampil');
                });
                
                modal.on('hide.bs.modal', function (e) {
                    console.log('ðŸ“¢ EVENT: Modal akan ditutup');
                });
            }
            
            console.log('=== END INIT MODAL STATISTIK PELANGGAN (GARANSI) ===');
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModalStatistikPelanggan);
    } else {
        initModalStatistikPelanggan();
    }
    })();
}, 500);
</script>
<!-- ========== END SCRIPT MODAL STATISTIK PELANGGAN ========== -->

<!-- ========== MODAL UPDATE STATUS KELUHAN ========== -->
<div class="modal fade" id="modalUpdateStatusKeluhan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="formUpdateStatusKeluhan">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-edit"></i> Update Status Keluhan
                    </h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                    <input type="hidden" name="keluhan_id_status" id="keluhan_id_status">
                    
                    <div class="form-group">
                        <label>Keluhan:</label>
                        <textarea class="form-control" id="keluhan_text" rows="2" readonly></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Pengerjaan: <span class="text-danger">*</span></label>
                        <select name="status_keluhan_update" id="status_keluhan_update" class="form-control" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            <strong>Diproses:</strong> Sedang dikerjakan<br>
                            <strong>Selesai:</strong> Sudah selesai dikerjakan<br>
                            <strong>Tidak Selesai:</strong> Tidak dapat diselesaikan
                        </span>
                    </div>
                    
                    <div class="form-group" id="keterangan_keluhan_group" style="display:none;">
                        <label>Keterangan Tidak Selesai: <span class="text-danger">*</span></label>
                        <textarea name="keterangan_keluhan" id="keterangan_keluhan" 
                                  class="form-control" rows="3" 
                                  placeholder="Jelaskan alasan kenapa tidak selesai..."></textarea>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            Keterangan ini akan masuk ke catatan servis
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-primary">
                        <i class="ace-icon fa fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== MODAL UPDATE STATUS WORK ORDER ========== -->
<div class="modal fade" id="modalUpdateStatusWO" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" id="formUpdateStatusWO">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-edit"></i> Update Status Work Order
                    </h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                    <input type="hidden" name="wo_id_status" id="wo_id_status">
                    
                    <div class="form-group">
                        <label>Work Order:</label>
                        <input type="text" class="form-control" id="wo_text" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Pengerjaan: <span class="text-danger">*</span></label>
                        <select name="status_wo_update" id="status_wo_update" class="form-control" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="datang">Datang</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            <strong>Datang:</strong> Menunggu part datang<br>
                            <strong>Diproses:</strong> Sedang dikerjakan<br>
                            <strong>Selesai:</strong> Sudah selesai dikerjakan<br>
                            <strong>Tidak Selesai:</strong> Tidak dapat diselesaikan
                        </span>
                    </div>
                    
                    <div class="form-group">
                        <label>Progress Pengerjaan: <span class="text-danger">*</span></label>
                        <input type="number" name="progress_wo" id="progress_wo" 
                               class="form-control" min="0" max="100" value="0" required>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            Masukkan persentase progress (0-100%)
                        </span>
                    </div>
                    
                    <div class="form-group" id="keterangan_wo_group" style="display:none;">
                        <label>Keterangan Tidak Selesai: <span class="text-danger">*</span></label>
                        <textarea name="keterangan_wo" id="keterangan_wo" 
                                  class="form-control" rows="3" 
                                  placeholder="Jelaskan alasan kenapa tidak selesai..."></textarea>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            Keterangan ini akan masuk ke catatan servis
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" name="btnupdatestatuswo" class="btn btn-primary">
                        <i class="ace-icon fa fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========== MODAL TAMBAH KELUHAN BARU (INLINE) ========== -->
<?php include "_template/_modal_tambah_keluhan_inline.php"; ?>
<!-- ========== END MODAL TAMBAH KELUHAN BARU ========== -->

<script type="text/javascript">
  (function(){
      function unlockUI(){
          try{
              ['.modal-backdrop','.blockUI','.block-ui','.ui-widget-overlay'].forEach(function(sel){
                  document.querySelectorAll(sel).forEach(function(n){ if(n && n.parentNode){ n.parentNode.removeChild(n); } });
              });
              document.body.classList.remove('modal-open');
              document.querySelectorAll('.nav-tabs a,.btn,button,[data-toggle="tab"]').forEach(function(el){ el.style.pointerEvents='auto'; });
          }catch(e){}
      }
      if(document.readyState==='loading'){
          document.addEventListener('DOMContentLoaded', function(){ setTimeout(unlockUI, 200); });
      } else {
          setTimeout(unlockUI, 200);
      }
      document.addEventListener('keydown', function(ev){ if(ev.key==='Escape'){ unlockUI(); } });
  })();
</script>

<script type="text/javascript">
  (function(){
      var DEBUG = false; function log(){ if(DEBUG && window.console) try{ console.log.apply(console, arguments); }catch(e){} }
      function bindTabsVanilla(){
          if (window.jQuery) return;
          document.addEventListener('click', function(ev){
              var a = ev.target && ev.target.closest ? ev.target.closest('a[data-toggle="tab"]') : null;
              if(!a) return;
              ev.preventDefault(); ev.stopPropagation();
              var target = a.getAttribute('href');
              log('[TabsInit][Garansi] Vanilla activating', target);
              try{
                  document.querySelectorAll('#myTab li').forEach(function(li){ li.classList.remove('active'); });
                  if (a.parentNode) a.parentNode.classList.add('active');
                  document.querySelectorAll('.tab-content .tab-pane').forEach(function(p){ p.classList.remove('active'); p.classList.remove('in'); });
                  var el = document.querySelector(target);
                  if (el){ el.classList.add('active'); el.classList.add('in'); }
              }catch(e){ log('[TabsInit][Garansi] Vanilla error', e); }
          }, true);
      }
      if (typeof jQuery !== 'undefined'){
          jQuery(function($){
              var $tabs = $('#myTab a[data-toggle="tab"]');
              if ($tabs.length){
                  if (typeof $.fn.tab === 'undefined'){
                      $tabs.off('click.__fallback').on('click.__fallback', function(e){
                          e.preventDefault(); e.stopPropagation();
                          var t = $(this).attr('href');
                          log('[TabsInit][Garansi] Fallback activating', t);
                          $('#myTab li').removeClass('active');
                          $(this).parent().addClass('active');
                          $('.tab-content .tab-pane').removeClass('active in');
                          $(t).addClass('active in');
                      });
                  } else {
                      $tabs.off('click.__force').on('click.__force', function(e){ e.preventDefault(); e.stopPropagation(); $(this).tab('show'); });
                  }
              }
          });
      } else {
          if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindTabsVanilla); else bindTabsVanilla();
      }
  })();
</script>

<?php include "_template/modal-input-barang-custom.php"; ?>

  	</body>
</html>
