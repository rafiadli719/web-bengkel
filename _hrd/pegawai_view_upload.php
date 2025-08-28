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

		$nip=$_GET['kd'];
		$cari_kd=mysqli_query($koneksi,"SELECT 
										nama, alamat, notlp, kode_jk, 
										tempat_lahir, DATE_FORMAT(tgl_lahir,'%d/%m/%Y') AS tanggal_lahir, 
										email, kode_pendidikan, npwp, kode_status_kawin, 
										jml_tanggungan, kode_ptkp, no_rek, nama_rek, bank, 
										ktp, kode_agama, kode_darah, no_bpjs_tk, no_bpjs_kes, 
										kode_jabatan, kode_divisi, 
										kode_status_emp, DATE_FORMAT(tgl_masuk,'%d/%m/%Y') AS tanggal_masuk, foto_pegawai, 
										kota, prop, 
										district, districtsub, kodepos, 
										gaji_pokok, id_hari_kerja, jumlah_cuti, tlp_rumah, 
                                        wage_template, id_tipepajak, lokasi 
										FROM tbpegawai 
										WHERE nip='$nip'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$jumlah_cuti=$tm_cari['jumlah_cuti'];
		$notelp=$tm_cari['tlp_rumah'];
		$nama=$tm_cari['nama'];
		$alamat=$tm_cari['alamat'];
		$nohp=$tm_cari['notlp'];		
		$kode_jk=$tm_cari['kode_jk'];		
		$tempat_lahir=$tm_cari['tempat_lahir'];
		$tanggal_lahir=$tm_cari['tanggal_lahir'];
		$email=$tm_cari['email'];
		$kode_pendidikan=$tm_cari['kode_pendidikan'];
		$npwp=$tm_cari['npwp'];
		$kode_status_kawin=$tm_cari['kode_status_kawin'];		
		$jml_tanggungan=$tm_cari['jml_tanggungan'];
		$kode_ptkp=$tm_cari['kode_ptkp'];
		$no_rek=$tm_cari['no_rek'];
		$nama_rek=$tm_cari['nama_rek'];
		$bank=$tm_cari['bank'];
		$ktp=$tm_cari['ktp'];	
		$kode_agama=$tm_cari['kode_agama'];	
		$kode_darah=$tm_cari['kode_darah'];	
		$no_bpjs_tk=$tm_cari['no_bpjs_tk'];
		$no_bpjs_kes=$tm_cari['no_bpjs_kes'];					
		$foto_pegawai=$tm_cari['foto_pegawai'];
														
		$kota=$tm_cari['kota'];
		$prop=$tm_cari['prop'];		
		$district=$tm_cari['district'];
		$districtsub=$tm_cari['districtsub'];
		$kodepos=$tm_cari['kodepos'];		

		$kode_jabatan=$tm_cari['kode_jabatan'];	
		$kode_divisi=$tm_cari['kode_divisi'];
		$kode_status_emp=$tm_cari['kode_status_emp'];	
		$tanggal_masuk=$tm_cari['tanggal_masuk'];
		
		$gaji_pokok=$tm_cari['gaji_pokok'];
		$id_hari_kerja=$tm_cari['id_hari_kerja'];		

		$wage_template=$tm_cari['wage_template'];
        $tipe_pajak=$tm_cari['id_tipepajak'];        
        $lokasi=$tm_cari['lokasi'];        
		
	if($foto_pegawai=='') {
		$foto_pegawai="file_upload/pic1.png";
	}

$cari_kd=mysqli_query($koneksi,"SELECT nama_lokasi FROM tbwork_loc 
                                WHERE kode_lokasi='$lokasi'");
$tm_cari=mysqli_fetch_array($cari_kd);
$nama_lokasi=$tm_cari['nama_lokasi'];

$cari_kd=mysqli_query($koneksi,"SELECT tipe_pajak FROM tbtipe_pajak WHERE id='$tipe_pajak'");
$tm_cari=mysqli_fetch_array($cari_kd);
$tipe_pajak=$tm_cari['tipe_pajak'];

$cari_kd=mysqli_query($koneksi,"SELECT list_bpjs FROM tblist_bpjs WHERE id='$wage_template'");
$tm_cari=mysqli_fetch_array($cari_kd);
$list_bpjs=$tm_cari['list_bpjs'];

$cari_kd=mysqli_query($koneksi,"SELECT schedule_type, jml_day  FROM tbwork_schedule WHERE id_work='$id_hari_kerja'");
$tm_cari=mysqli_fetch_array($cari_kd);
$schedule_type=$tm_cari['schedule_type'];
$jml_day=$tm_cari['jml_day'];

$cari_kd=mysqli_query($koneksi,"SELECT kode FROM tbtarif_ptkp WHERE id='$kode_ptkp'");
$tm_cari=mysqli_fetch_array($cari_kd);
$ptkp=$tm_cari['kode'];

$cari_kd=mysqli_query($koneksi,"SELECT jk FROM tbjk WHERE kode_jk='$kode_jk'");
$tm_cari=mysqli_fetch_array($cari_kd);
$jk=$tm_cari['jk'];

$cari_kd=mysqli_query($koneksi,"SELECT status_nikah FROM tbstatus_nikah WHERE kode='$kode_status_kawin'");
$tm_cari=mysqli_fetch_array($cari_kd);
$status_nikah=$tm_cari['status_nikah'];

$cari_kd=mysqli_query($koneksi,"SELECT agama FROM tbagama WHERE kode='$kode_agama'");
$tm_cari=mysqli_fetch_array($cari_kd);
$agama=$tm_cari['agama'];

$cari_kd=mysqli_query($koneksi,"SELECT darah FROM tbdarah WHERE kode='$kode_darah'");
$tm_cari=mysqli_fetch_array($cari_kd);
$darah=$tm_cari['darah'];

		$cari_kd=mysqli_query($koneksi,"SELECT status FROM tbstatus_emp WHERE kode='$kode_status_emp'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$status_empl=$tm_cari['status'];

$cari_kd=mysqli_query($koneksi,"SELECT pendidikan FROM tbpendidikan WHERE kode='$kode_pendidikan'");
$tm_cari=mysqli_fetch_array($cari_kd);
$pendidikan=$tm_cari['pendidikan'];
														$cari_kd=mysqli_query($koneksi,"SELECT nama_divisi FROM tbdivisi WHERE kode_divisi='$kode_divisi'");
														$tm_cari=mysqli_fetch_array($cari_kd);
														$nama_divisi=$tm_cari['nama_divisi'];														
														
														$cari_kd=mysqli_query($koneksi,"SELECT nama_jabatan FROM tbjabatan WHERE kode_jabatan='$kode_jabatan'");
														$tm_cari=mysqli_fetch_array($cari_kd);
														$nama_jabatan=$tm_cari['nama_jabatan'];														
														
		$warna="#F0F8FF";		

		if(isset($_POST['btntutup'])) {		
			header('location:pegawai.php');
		}				
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title><?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Common form elements and layouts" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
		<link rel="stylesheet" href="assets/css/chosen.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-datepicker3.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-timepicker.min.css" />
		<link rel="stylesheet" href="assets/css/daterangepicker.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap-colorpicker.min.css" />

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

				<?php include "menu_pegawai.php"; ?>

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
								<a href="pegawai.php">Pegawai</a>
							</li>
							<li class="active">Ubah Foto</li>
						</ul><!-- /.breadcrumb -->

						
					</div>

					<div class="page-content">
						<br>
						<div class="row">
							<div class="col-xs-12 col-sm-3">
								<center>
									<img id="avatar" src="../<?php echo $foto_pegawai; ?>" width="200" height="200" />
								    <br>
								    <br>
                                    <form class="form-horizontal" enctype="multipart/form-data" action="pegawai_view_upload_proses.php" method="post">
        								<input type="hidden" name="kd"  class="form-control" value="<?php echo $nip; ?>"/>
                                        <div class="form-group">
											<div class="col-xs-12">
												<input multiple="" type="file" id="id-input-file-3" name="id-input-file-3" />
											</div>
										</div>
                                        <div class="form-group">
    										<div class="col-xs-12">
                                                <button class="btn btn-success btn-next" data-last="Finish">
    											<i class="ace-icon fa fa-check icon-on-right"></i>
    											Save Foto 
    										    </button>
    									    </div>
    									</div>															
    					            </form>								    
								</center>
							</div>
							<div class="col-xs-12 col-sm-9">
								<table id="dynamic-table" class="table table-bordered">
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> ID Pegawai </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $nip; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Nama Pegawai </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $nama; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Jenis Kelamin </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $jk; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Status Perkawinan </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $status_nikah; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Tempat Lahir </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $tempat_lahir; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Tanggal Lahir </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $tanggal_lahir; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Agama </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $agama; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Gol. Darah </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $darah; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Pendidikan Terakhir </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $pendidikan; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Email </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $email; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> KTP </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $ktp; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> NPWP </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $npwp; ?> </font></td> 										
									</tr>
									<tr>
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> Telp, Rumah </b></font></td> 
										<td width="30%"><font size="2"> <?php echo $notelp; ?> </font></td> 	
										<td width="20%" bgcolor="<?php echo $warna; ?>"><font size="2" color="maroon"><b> No HP </b></font></td>  
										<td width="30%"><font size="2"> <?php echo $nohp; ?> </font></td> 										
									</tr>
								</table>
							</div>
						</div>
						
						<div class="row">
							<div class="tabbable">
								<ul class="nav nav-tabs padding-18 tab-size-bigger" id="myTab">
									<li class="active">
										<a data-toggle="tab" href="#faq-tab-1">Working</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-2">Alamat</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-3">BPJS</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-4">Salary &amp; Bank Info</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-5a">Tunjangan Tetap</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-5">Pendidikan</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-6">Training</a>
									</li>
									<li>
										<a data-toggle="tab" href="#faq-tab-7">Informasi Keluarga</a>
									</li>
								</ul>
								<div class="tab-content no-border padding-24">
									<div id="faq-tab-1" class="tab-pane fade in active">
																<div class="profile-user-info profile-user-info-striped">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Divisi </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $nama_divisi; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Jabatan </div>
																		<div class="profile-info-value">
																			<span class="editable" id="age"><?php echo $nama_jabatan; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Status </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $status_empl; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Mulai Kerja </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $tanggal_masuk; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Hari Kerja </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username">
																				<?php echo $schedule_type; ?> (<?php echo $jml_day; ?> hari)
																			</span>
																		</div>
																	</div>		
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Jumlah Cuti </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username">
																				<?php echo $jumlah_cuti; ?> hari
																			</span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Lokasi Kerja </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username">
																				<?php echo $nama_lokasi; ?>
																			</span>
																		</div>
																	</div>																	
																</div>
										</div>
										
										<div id="faq-tab-2" class="tab-pane fade">
											<div class="profile-user-info profile-user-info-striped">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Alamat </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $alamat; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Desa/Kel </div>
																		<div class="profile-info-value">
																			<span class="editable" id="age"><?php echo $district; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Kecamatan </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $districtsub; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Kab/Kota </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $kota; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Propinsi </div>
																		<div class="profile-info-value">
																			<span class="editable" id="country"><?php echo $prop; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Kode Pos </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $kodepos; ?></span>
																		</div>
																	</div>
																</div>

										</div>
										<div id="faq-tab-3" class="tab-pane fade">
																<div class="profile-user-info profile-user-info-striped">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Tanggungan </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $jml_tanggungan; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> PTKP </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $ptkp; ?></span>
																		</div>
																	</div>																	
																	<div class="profile-info-row">
																		<div class="profile-info-name"> BPJS TK No </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $no_bpjs_tk; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> BPJS KES No </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $no_bpjs_kes; ?></span>
																		</div>
																	</div>
																</div>
										</div>
										<div id="faq-tab-4" class="tab-pane fade">
																<div class="profile-user-info profile-user-info-striped">
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Gaji Pokok </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username">Rp. <?php echo number_format($gaji_pokok,0) ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Tipe BPJS </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $list_bpjs; ?></span>
																		</div>
																	</div>												
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Tipe Pajak </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $tipe_pajak; ?></span>
																		</div>
																	</div>												                                                                    
                                                                    
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Bank Name </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $bank; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Account </div>
																		<div class="profile-info-value">
																			<span class="editable" id="username"><?php echo $no_rek; ?></span>
																		</div>
																	</div>
																	<div class="profile-info-row">
																		<div class="profile-info-name"> Acc Name </div>
																		<div class="profile-info-value">
																			<span class="editable" id="country"><?php echo $nama_rek; ?></span>
																		</div>
																	</div>
																</div>
										</div>
										<div id="faq-tab-5a" class="tab-pane fade">
																<table id="dynamic-table" class="table table-striped table-bordered table-hover">
																	<thead>
																		<tr>
                                                                            <td align="center" width="5%">No</td>
                                                                            <td width="75%">Nama Tunjangan</td>
                                                                            <td align="right" width="20%">Besaran Tunjangan</td>
																		</tr>
																	</thead>
																	<tbody>
																	<?php 
																		$no = 0 ;
																		$sql = mysqli_query($koneksi,"SELECT id, nama_tunjangan, nilai_tunjangan FROM tbemp_tunjangan WHERE nip='$nip'");
																		while ($tampil = mysqli_fetch_array($sql)) {
																			$no++;
																	?>
																		<tr>
																			<td class="center"><?php echo $no ?></td>
                                                                            <td><?php echo $tampil['nama_tunjangan']?></td>
                                                                            <td align="right"><?php echo number_format($tampil['nilai_tunjangan'],0) ?></td>
																		</tr>

																	<?php
																		}
																	?>
																	</tbody>
																</table>
										</div>									
										<div id="faq-tab-5" class="tab-pane fade">
																<table id="dynamic-table" class="table table-striped table-bordered table-hover">
																	<thead>
																		<tr>
																			<th class="center" width="5%">No</th>
																			<th width="20%">Education Level Name</th>
																			<th width="25%">Education Field Name</th>
																			<th width="30%">Institution Name</th>
																			<th class="center" width="10%">Start Year</th>
																			<th class="center" width="10%">End Year</th>														
																		</tr>
																	</thead>
																	<tbody>
																	<?php 
																		$no = 0 ;
																		$sql = mysqli_query($koneksi,"SELECT id, fld1, fld2, fld3, fld4, fld5 FROM tbemp_education WHERE nip='$nip'");
																		while ($tampil = mysqli_fetch_array($sql)) {
																			$no++;
																	?>
																		<tr>
																			<td class="center"><?php echo $no ?></td>
																			<td><?php echo $tampil['fld1']?></td>
																			<td><?php echo $tampil['fld3']?></td>
																			<td><?php echo $tampil['fld2']?></td>														
																			<td class="center"><?php echo $tampil['fld4']?></td>
																			<td class="center"><?php echo $tampil['fld5']?></td>																												
																		</tr>

																	<?php
																		}
																	?>
																	</tbody>
																</table>
										</div>										
										<div id="faq-tab-6" class="tab-pane fade">
																<table id="dynamic-table" class="table table-striped table-bordered table-hover">
																	<thead>
																		<tr>
																			<th class="center" width="5%">No</th>
																			<th width="45%">Event Name</th>
																			<th width="40%">Training Institution</th>
																			<th class="center" width="10%">Year</th>
																		</tr>
																	</thead>
																	<tbody>
																	<?php 
																		$no = 0 ;
																		$sql = mysqli_query($koneksi,"SELECT id, fld1, fld2, fld3 FROM tbemp_training WHERE nip='$nip'");
																		while ($tampil = mysqli_fetch_array($sql)) {
																			$no++;
																	?>
																		<tr>
																			<td class="center"><?php echo $no ?></td>
																			<td><?php echo $tampil['fld1']?></td>
																			<td><?php echo $tampil['fld2']?></td>
																			<td class="center"><?php echo $tampil['fld3']?></td>																																						
																		</tr>
																	<?php
																		}
																	?>
																	</tbody>
																</table>
										</div>																				
										<div id="faq-tab-7" class="tab-pane fade">
<table id="dynamic-table" class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
														<th class="center" width="5%">No</th>
														<th width="20%">Nama</th>
														<th class="center" width="15%">Jenis Kelamin</th>
														<th width="30%">Alamat</th>
														<th width="10%">No. Telepon</th>
														<th class="center" width="10%">Hubungan</th>														

													</tr>
												</thead>
												<tbody>
												<?php 
													$no = 0 ;
													$sql = mysqli_query($koneksi,"SELECT id, nama, id_jk, alamat, notlp, hubungan FROM tbemp_family WHERE nip='$nip'");
													while ($tampil = mysqli_fetch_array($sql)) {
														$no++;
														$kode_jk=$tampil['id_jk'];
														$cari_kd=mysqli_query($koneksi,"SELECT jk FROM tbjk WHERE kode_jk='$kode_jk'");
														$tm_cari=mysqli_fetch_array($cari_kd);
														$jk=$tm_cari['jk'];
												?>
													<tr>
														<td class="center"><?php echo $no ?></td>
														<td><?php echo $tampil['nama']?></td>
														<td class="center"><?php echo $jk; ?></td>
														<td><?php echo $tampil['alamat']?></td>														
														<td><?php echo $tampil['notlp']?></td>																												
														<td class="center"><?php echo $tampil['hubungan']?></td>																												

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

<br>
						<div class="row">
							<div class="col-xs-12 col-sm-3">
							<form class="form-horizontal" action="" method="post">
												<button class="btn btn-info" type="submit" id="btntutup" name="btntutup">
													<i class="ace-icon fa fa-close bigger-110"></i>
													Close
												</button>						
							</form>
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
			
			
					$('#chosen-multiple-style .btn').on('click', function(e){
						var target = $(this).find('input[type=radio]');
						var which = parseInt(target.val());
						if(which == 2) $('#form-field-select-4').addClass('tag-input-style');
						 else $('#form-field-select-4').removeClass('tag-input-style');
					});
				}
			
			
				$('[data-rel=tooltip]').tooltip({container:'body'});
				$('[data-rel=popover]').popover({container:'body'});
			
				autosize($('textarea[class*=autosize]'));
				
				$('textarea.limited').inputlimiter({
					remText: '%n character%s remaining...',
					limitText: 'max allowed : %n.'
				});
			
				$.mask.definitions['~']='[+-]';
				$('.input-mask-date').mask('99/99/9999');
				$('.input-mask-phone').mask('(999) 999-9999');
				$('.input-mask-eyescript').mask('~9.99 ~9.99 999');
				$(".input-mask-product").mask("a*-999-a999",{placeholder:" ",completed:function(){alert("You typed the following: "+this.val());}});
			
			
			
				$( "#input-size-slider" ).css('width','200px').slider({
					value:1,
					range: "min",
					min: 1,
					max: 8,
					step: 1,
					slide: function( event, ui ) {
						var sizing = ['', 'input-sm', 'input-lg', 'input-mini', 'input-small', 'input-medium', 'input-large', 'input-xlarge', 'input-xxlarge'];
						var val = parseInt(ui.value);
						$('#form-field-4').attr('class', sizing[val]).attr('placeholder', '.'+sizing[val]);
					}
				});
			
				$( "#input-span-slider" ).slider({
					value:1,
					range: "min",
					min: 1,
					max: 12,
					step: 1,
					slide: function( event, ui ) {
						var val = parseInt(ui.value);
						$('#form-field-5').attr('class', 'col-xs-'+val).val('.col-xs-'+val);
					}
				});
			
			
				
				//"jQuery UI Slider"
				//range slider tooltip example
				$( "#slider-range" ).css('height','200px').slider({
					orientation: "vertical",
					range: true,
					min: 0,
					max: 100,
					values: [ 17, 67 ],
					slide: function( event, ui ) {
						var val = ui.values[$(ui.handle).index()-1] + "";
			
						if( !ui.handle.firstChild ) {
							$("<div class='tooltip right in' style='display:none;left:16px;top:-6px;'><div class='tooltip-arrow'></div><div class='tooltip-inner'></div></div>")
							.prependTo(ui.handle);
						}
						$(ui.handle.firstChild).show().children().eq(1).text(val);
					}
				}).find('span.ui-slider-handle').on('blur', function(){
					$(this.firstChild).hide();
				});
				
				
				$( "#slider-range-max" ).slider({
					range: "max",
					min: 1,
					max: 10,
					value: 2
				});
				
				$( "#slider-eq > span" ).css({width:'90%', 'float':'left', margin:'15px'}).each(function() {
					// read initial values from markup and remove that
					var value = parseInt( $( this ).text(), 10 );
					$( this ).empty().slider({
						value: value,
						range: "min",
						animate: true
						
					});
				});
				
				$("#slider-eq > span.ui-slider-purple").slider('disable');//disable third item
			
				
				$('#id-input-file-1 , #id-input-file-2').ace_file_input({
					no_file:'No File ...',
					btn_choose:'Choose',
					btn_change:'Change',
					droppable:false,
					onchange:null,
					thumbnail:false //| true | large
					//whitelist:'gif|png|jpg|jpeg'
					//blacklist:'exe|php'
					//onchange:''
					//
				});
				//pre-show a file name, for example a previously selected file
				//$('#id-input-file-1').ace_file_input('show_file_list', ['myfile.txt'])
			
			
				$('#id-input-file-3').ace_file_input({
					style: 'well',
					btn_choose: 'Drop files here or click to choose',
					btn_change: null,
					no_icon: 'ace-icon fa fa-cloud-upload',
					droppable: true,
					thumbnail: 'small'//large | fit
					//,icon_remove:null//set null, to hide remove/reset button
					/**,before_change:function(files, dropped) {
						//Check an example below
						//or examples/file-upload.html
						return true;
					}*/
					/**,before_remove : function() {
						return true;
					}*/
					,
					preview_error : function(filename, error_code) {
						//name of the file that failed
						//error_code values
						//1 = 'FILE_LOAD_FAILED',
						//2 = 'IMAGE_LOAD_FAILED',
						//3 = 'THUMBNAIL_FAILED'
						//alert(error_code);
					}
			
				}).on('change', function(){
					//console.log($(this).data('ace_input_files'));
					//console.log($(this).data('ace_input_method'));
				});
				
				
				//$('#id-input-file-3')
				//.ace_file_input('show_file_list', [
					//{type: 'image', name: 'name of image', path: 'http://path/to/image/for/preview'},
					//{type: 'file', name: 'hello.txt'}
				//]);
			
				
				
			
				//dynamically change allowed formats by changing allowExt && allowMime function
				$('#id-file-format').removeAttr('checked').on('change', function() {
					var whitelist_ext, whitelist_mime;
					var btn_choose
					var no_icon
					if(this.checked) {
						btn_choose = "Drop images here or click to choose";
						no_icon = "ace-icon fa fa-picture-o";
			
						whitelist_ext = ["jpeg", "jpg", "png", "gif" , "bmp"];
						whitelist_mime = ["image/jpg", "image/jpeg", "image/png", "image/gif", "image/bmp"];
					}
					else {
						btn_choose = "Drop files here or click to choose";
						no_icon = "ace-icon fa fa-cloud-upload";
						
						whitelist_ext = null;//all extensions are acceptable
						whitelist_mime = null;//all mimes are acceptable
					}
					var file_input = $('#id-input-file-3');
					file_input
					.ace_file_input('update_settings',
					{
						'btn_choose': btn_choose,
						'no_icon': no_icon,
						'allowExt': whitelist_ext,
						'allowMime': whitelist_mime
					})
					file_input.ace_file_input('reset_input');
					
					file_input
					.off('file.error.ace')
					.on('file.error.ace', function(e, info) {
						//console.log(info.file_count);//number of selected files
						//console.log(info.invalid_count);//number of invalid files
						//console.log(info.error_list);//a list of errors in the following format
						
						//info.error_count['ext']
						//info.error_count['mime']
						//info.error_count['size']
						
						//info.error_list['ext']  = [list of file names with invalid extension]
						//info.error_list['mime'] = [list of file names with invalid mimetype]
						//info.error_list['size'] = [list of file names with invalid size]
						
						
						/**
						if( !info.dropped ) {
							//perhapse reset file field if files have been selected, and there are invalid files among them
							//when files are dropped, only valid files will be added to our file array
							e.preventDefault();//it will rest input
						}
						*/
						
						
						//if files have been selected (not dropped), you can choose to reset input
						//because browser keeps all selected files anyway and this cannot be changed
						//we can only reset file field to become empty again
						//on any case you still should check files with your server side script
						//because any arbitrary file can be uploaded by user and it's not safe to rely on browser-side measures
					});
					
					
					/**
					file_input
					.off('file.preview.ace')
					.on('file.preview.ace', function(e, info) {
						console.log(info.file.width);
						console.log(info.file.height);
						e.preventDefault();//to prevent preview
					});
					*/
				
				});
			
				$('#spinner1').ace_spinner({value:0,min:0,max:200,step:10, btn_up_class:'btn-info' , btn_down_class:'btn-info'})
				.closest('.ace-spinner')
				.on('changed.fu.spinbox', function(){
					//console.log($('#spinner1').val())
				}); 
				$('#spinner2').ace_spinner({value:0,min:0,max:10000,step:100, touch_spinner: true, icon_up:'ace-icon fa fa-caret-up bigger-110', icon_down:'ace-icon fa fa-caret-down bigger-110'});
				$('#spinner3').ace_spinner({value:0,min:-100,max:100,step:10, on_sides: true, icon_up:'ace-icon fa fa-plus bigger-110', icon_down:'ace-icon fa fa-minus bigger-110', btn_up_class:'btn-success' , btn_down_class:'btn-danger'});
				$('#spinner4').ace_spinner({value:0,min:-100,max:100,step:10, on_sides: true, icon_up:'ace-icon fa fa-plus', icon_down:'ace-icon fa fa-minus', btn_up_class:'btn-purple' , btn_down_class:'btn-purple'});
			
				//$('#spinner1').ace_spinner('disable').ace_spinner('value', 11);
				//or
				//$('#spinner1').closest('.ace-spinner').spinner('disable').spinner('enable').spinner('value', 11);//disable, enable or change value
				//$('#spinner1').closest('.ace-spinner').spinner('value', 0);//reset to 0
			
			
				//datepicker plugin
				//link
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true
				})
				//show datepicker when clicking on the icon
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
			
				//or change it into a date range picker
				$('.input-daterange').datepicker({autoclose:true});
			
			
				//to translate the daterange picker, please copy the "examples/daterange-fr.js" contents here before initialization
				$('input[name=date-range-picker]').daterangepicker({
					'applyClass' : 'btn-sm btn-success',
					'cancelClass' : 'btn-sm btn-default',
					locale: {
						applyLabel: 'Apply',
						cancelLabel: 'Cancel',
					}
				})
				.prev().on(ace.click_event, function(){
					$(this).next().focus();
				});
			
			
				$('#timepicker1').timepicker({
					minuteStep: 1,
					showSeconds: true,
					showMeridian: false,
					disableFocus: true,
					icons: {
						up: 'fa fa-chevron-up',
						down: 'fa fa-chevron-down'
					}
				}).on('focus', function() {
					$('#timepicker1').timepicker('showWidget');
				}).next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
				
				
			
				
				if(!ace.vars['old_ie']) $('#date-timepicker1').datetimepicker({
				 //format: 'MM/DD/YYYY h:mm:ss A',//use this option to display seconds
				 icons: {
					time: 'fa fa-clock-o',
					date: 'fa fa-calendar',
					up: 'fa fa-chevron-up',
					down: 'fa fa-chevron-down',
					previous: 'fa fa-chevron-left',
					next: 'fa fa-chevron-right',
					today: 'fa fa-arrows ',
					clear: 'fa fa-trash',
					close: 'fa fa-times'
				 }
				}).next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
				
			
				$('#colorpicker1').colorpicker();
				//$('.colorpicker').last().css('z-index', 2000);//if colorpicker is inside a modal, its z-index should be higher than modal'safe
			
				$('#simple-colorpicker-1').ace_colorpicker();
				//$('#simple-colorpicker-1').ace_colorpicker('pick', 2);//select 2nd color
				//$('#simple-colorpicker-1').ace_colorpicker('pick', '#fbe983');//select #fbe983 color
				//var picker = $('#simple-colorpicker-1').data('ace_colorpicker')
				//picker.pick('red', true);//insert the color if it doesn't exist
			
			
				$(".knob").knob();
				
				
				var tag_input = $('#form-field-tags');
				try{
					tag_input.tag(
					  {
						placeholder:tag_input.attr('placeholder'),
						//enable typeahead by specifying the source array
						source: ace.vars['US_STATES'],//defined in ace.js >> ace.enable_search_ahead
						/**
						//or fetch data from database, fetch those that match "query"
						source: function(query, process) {
						  $.ajax({url: 'remote_source.php?q='+encodeURIComponent(query)})
						  .done(function(result_items){
							process(result_items);
						  });
						}
						*/
					  }
					)
			
					//programmatically add/remove a tag
					var $tag_obj = $('#form-field-tags').data('tag');
					$tag_obj.add('Programmatically Added');
					
					var index = $tag_obj.inValues('some tag');
					$tag_obj.remove(index);
				}
				catch(e) {
					//display a textarea for old IE, because it doesn't support this plugin or another one I tried!
					tag_input.after('<textarea id="'+tag_input.attr('id')+'" name="'+tag_input.attr('name')+'" rows="3">'+tag_input.val()+'</textarea>').remove();
					//autosize($('#form-field-tags'));
				}
				
				
				/////////
				$('#modal-form input[type=file]').ace_file_input({
					style:'well',
					btn_choose:'Drop files here or click to choose',
					btn_change:null,
					no_icon:'ace-icon fa fa-cloud-upload',
					droppable:true,
					thumbnail:'large'
				})
				
				//chosen plugin inside a modal will have a zero width because the select element is originally hidden
				//and its width cannot be determined.
				//so we set the width after modal is show
				$('#modal-form').on('shown.bs.modal', function () {
					if(!ace.vars['touch']) {
						$(this).find('.chosen-container').each(function(){
							$(this).find('a:first-child').css('width' , '210px');
							$(this).find('.chosen-drop').css('width' , '210px');
							$(this).find('.chosen-search input').css('width' , '200px');
						});
					}
				})
				/**
				//or you can activate the chosen plugin after modal is shown
				//this way select element becomes visible with dimensions and chosen works as expected
				$('#modal-form').on('shown', function () {
					$(this).find('.modal-chosen').chosen();
				})
				*/
			
				
				
				$(document).one('ajaxloadstart.page', function(e) {
					autosize.destroy('textarea[class*=autosize]')
					
					$('.limiterBox,.autosizejs').remove();
					$('.daterangepicker.dropdown-menu,.colorpicker.dropdown-menu,.bootstrap-datetimepicker-widget.dropdown-menu').remove();
				});
			
			});
		</script>
	</body>
</html>

<?php 
	}
?>
