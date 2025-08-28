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

        // Mode edit atau add new
        $mode = $_GET['mode'] ?? 'add';
        $kode_wo = $_GET['kode'] ?? '';
        
        // Inisialisasi variabel
        $nama_wo = '';
        $keterangan = '';
        $total_waktu = 0;
        $total_harga = 0;
        
        // Jika mode edit, ambil data work order
        if($mode == 'edit' && $kode_wo != '') {
            $cari_kd = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
            if(mysqli_num_rows($cari_kd) > 0) {
                $tm_cari = mysqli_fetch_array($cari_kd);
                $nama_wo = $tm_cari['nama_wo'];
                $keterangan = $tm_cari['keterangan'];
                $total_waktu = $tm_cari['waktu'];
                $total_harga = $tm_cari['harga'];
            }
        }

        // Generate kode work order otomatis untuk mode add
        if($mode == 'add') {
            $query_max = mysqli_query($koneksi,"SELECT MAX(CAST(SUBSTRING(kode_wo, 3) AS UNSIGNED)) as max_kode FROM tbworkorderheader WHERE kode_wo LIKE 'WO%'");
            $max_data = mysqli_fetch_array($query_max);
            $max_kode = $max_data['max_kode'] ?? 0;
            $kode_wo = 'WO' . str_pad($max_kode + 1, 4, '0', STR_PAD_LEFT);
        }

        // Proses simpan work order
        if(isset($_POST['btnsimpan'])) {
            $kode_wo = $_POST['kode_wo'];
            $nama_wo = $_POST['nama_wo'];
            $keterangan = $_POST['keterangan'];
            
            // Hitung total waktu dan harga dari detail
            $total_waktu_calc = 0;
            $total_harga_calc = 0;
            
            $detail_query = mysqli_query($koneksi,"SELECT * FROM tbworkorderdetail WHERE kode_wo='$kode_wo'");
            while($detail = mysqli_fetch_array($detail_query)) {
                if($detail['tipe'] == '1') { // Jasa
                    $jasa_query = mysqli_query($koneksi,"SELECT waktu FROM tbworkorderheader WHERE kode_wo='{$detail['kode_barang']}'");
                    $jasa_data = mysqli_fetch_array($jasa_query);
                    $total_waktu_calc += ($jasa_data['waktu'] ?? 0);
                }
                $total_harga_calc += $detail['total'];
            }
            
            if($mode == 'add') {
                // Insert work order header
                $insert = mysqli_query($koneksi,"INSERT INTO tbworkorderheader 
                                                (kode_wo, nama_wo, keterangan, status, waktu, harga) 
                                                VALUES 
                                                ('$kode_wo', '$nama_wo', '$keterangan', '0', '$total_waktu_calc', '$total_harga_calc')");
            } else {
                // Update work order header
                $update = mysqli_query($koneksi,"UPDATE tbworkorderheader 
                                                SET nama_wo='$nama_wo', keterangan='$keterangan', 
                                                    waktu='$total_waktu_calc', harga='$total_harga_calc'
                                                WHERE kode_wo='$kode_wo'");
            }
            
            echo"<script>window.alert('Work Order berhasil disimpan!');
            window.location=('workorder-input.php?mode=edit&kode=$kode_wo');</script>";
        }

        // Proses tambah jasa
        if(isset($_POST['btnaddjasa'])) {
            $kode_wo = $_POST['kode_wo'];
            $kode_jasa = $_POST['kode_jasa'];
            $harga_jasa = $_POST['harga_jasa'];
            
            // Get waktu jasa
            $jasa_query = mysqli_query($koneksi,"SELECT waktu FROM tbworkorderheader WHERE kode_wo='$kode_jasa'");
            $jasa_data = mysqli_fetch_array($jasa_query);
            $waktu = $jasa_data['waktu'] ?? 0;
            
            $insert_detail = mysqli_query($koneksi,"INSERT INTO tbworkorderdetail 
                                                    (kode_wo, kode_barang, jumlah, satuan, diskon, status_diskon, tipe, harga, total) 
                                                    VALUES 
                                                    ('$kode_wo', '$kode_jasa', '1', 'Unit', '0', '0', '1', '$harga_jasa', '$harga_jasa')");
            
            echo"<script>window.location=('workorder-input.php?mode=edit&kode=$kode_wo');</script>";
        }

        // Proses tambah barang
        if(isset($_POST['btnaddbarang'])) {
            $kode_wo = $_POST['kode_wo'];
            $kode_barang = $_POST['kode_barang'];
            $jumlah = $_POST['jumlah'];
            $harga_barang = $_POST['harga_barang'];
            $total = $jumlah * $harga_barang;
            
            $insert_detail = mysqli_query($koneksi,"INSERT INTO tbworkorderdetail 
                                                    (kode_wo, kode_barang, jumlah, satuan, diskon, status_diskon, tipe, harga, total) 
                                                    VALUES 
                                                    ('$kode_wo', '$kode_barang', '$jumlah', 'Pcs', '0', '0', '2', '$harga_barang', '$total')");
            
            echo"<script>window.location=('workorder-input.php?mode=edit&kode=$kode_wo');</script>";
        }
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
							<li class="active"><?php echo ($mode == 'edit') ? 'Edit' : 'Input'; ?> Work Order</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
                        <div class="page-header">
                            <h1>
                                <?php echo ($mode == 'edit') ? 'Edit' : 'Input'; ?> Work Order (Paket Servis)
                                <small>
                                    <i class="ace-icon fa fa-angle-double-right"></i>
                                    Kelola paket servis dengan kombinasi jasa dan barang
                                </small>
                            </h1>
                        </div>

                        <form class="form-horizontal" action="" method="post" role="form">                                            
                            <input type="hidden" name="kode_wo" value="<?php echo $kode_wo; ?>"/>
                        
                            <!-- Work Order Header -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Informasi Work Order</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Kode WO</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" value="<?php echo $kode_wo; ?>" readonly />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Nama WO</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" name="nama_wo" 
                                                               value="<?php echo $nama_wo; ?>" required 
                                                               placeholder="Contoh: PAKET SERVIS LENGKAP" />
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Total Waktu</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" 
                                                               value="<?php echo $total_waktu; ?> Menit" readonly />
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right">Total Harga</label>
                                                    <div class="col-sm-9">
                                                        <input type="text" class="form-control" 
                                                               value="Rp <?php echo number_format($total_harga, 0, ',', '.'); ?>" readonly />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="col-sm-2 control-label no-padding-right">Keterangan</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" name="keterangan" rows="3" 
                                                          placeholder="Deskripsi work order..."><?php echo $keterangan; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-2 col-sm-10">
                                                <button class="btn btn-success" type="submit" name="btnsimpan">
                                                    <i class="ace-icon fa fa-save"></i>
                                                    Simpan Work Order
                                                </button>
                                                <a href="workorder-list.php" class="btn btn-default">
                                                    <i class="ace-icon fa fa-list"></i>
                                                    Daftar Work Order
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if($mode == 'edit') { ?>
                            <!-- Detail Work Order -->
                            <div class="row">
                                <div class="col-xs-12 col-sm-6">
                                    <!-- Input Jasa -->
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Tambah Jasa Service</h4>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <div class="form-group">
                                                    <label>Kode Jasa</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="kode_jasa" name="kode_jasa" 
                                                               placeholder="Contoh: WO0001" />
                                                        <span class="input-group-btn">
                                                            <button type="button" class="btn btn-primary" onclick="cariJasa()">
                                                                <i class="ace-icon fa fa-search"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-info" onclick="openJasaPopup()">
                                                                <i class="ace-icon fa fa-list"></i>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nama Jasa</label>
                                                    <input type="text" class="form-control" id="nama_jasa" readonly />
                                                </div>
                                                <div class="form-group">
                                                    <label>Harga Jasa</label>
                                                    <input type="number" class="form-control" name="harga_jasa" id="harga_jasa" 
                                                           placeholder="0" />
                                                </div>
                                                <button type="submit" name="btnaddjasa" class="btn btn-success btn-block">
                                                    <i class="ace-icon fa fa-plus"></i>
                                                    Tambah Jasa
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xs-12 col-sm-6">
                                    <!-- Input Barang -->
                                    <div class="widget-box">
                                        <div class="widget-header">
                                            <h4 class="widget-title">Tambah Barang/Part</h4>
                                        </div>
                                        <div class="widget-body">
                                            <div class="widget-main">
                                                <div class="form-group">
                                                    <label>Kode Barang</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="kode_barang" name="kode_barang" 
                                                               placeholder="Contoh: GEN00009" />
                                                        <span class="input-group-btn">
                                                            <button type="button" class="btn btn-primary" onclick="cariBarang()">
                                                                <i class="ace-icon fa fa-search"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-info" onclick="openBarangPopup()">
                                                                <i class="ace-icon fa fa-list"></i>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nama Barang</label>
                                                    <input type="text" class="form-control" id="nama_barang" readonly />
                                                </div>
                                                <div class="row">
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label>Jumlah</label>
                                                            <input type="number" class="form-control" name="jumlah" 
                                                                   value="1" min="1" />
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-6">
                                                        <div class="form-group">
                                                            <label>Harga</label>
                                                            <input type="number" class="form-control" name="harga_barang" 
                                                                   id="harga_barang" placeholder="0" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <button type="submit" name="btnaddbarang" class="btn btn-success btn-block">
                                                    <i class="ace-icon fa fa-plus"></i>
                                                    Tambah Barang
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Daftar Detail Work Order -->
                            <div class="widget-box">
                                <div class="widget-header">
                                    <h4 class="widget-title">Detail Work Order</h4>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main">
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6">
                                                <h5 class="header blue">Jasa Service</h5>
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr class="info">
                                                            <th width="5%">No</th>
                                                            <th width="15%">Kode</th>
                                                            <th width="50%">Nama Jasa</th>
                                                            <th width="20%">Harga</th>
                                                            <th width="10%">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $no = 0;
                                                        $total_jasa = 0;
                                                        $sql = mysqli_query($koneksi,"SELECT d.*, w.nama_wo 
                                                                                     FROM tbworkorderdetail d
                                                                                     LEFT JOIN tbworkorderheader w ON d.kode_barang = w.kode_wo
                                                                                     WHERE d.kode_wo='$kode_wo' AND d.tipe='1'
                                                                                     ORDER BY d.id ASC");
                                                        while($tampil = mysqli_fetch_array($sql)) {
                                                            $no++;
                                                            $total_jasa += $tampil['total'];
                                                        ?>
                                                        <tr>
                                                            <td class="center"><?php echo $no; ?></td>
                                                            <td><?php echo $tampil['kode_barang']; ?></td>
                                                            <td><?php echo $tampil['nama_wo']; ?></td>
                                                            <td class="right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
                                                            <td class="center">
                                                                <a href="workorder-detail-hapus.php?id=<?php echo $tampil['id']; ?>&kode=<?php echo $kode_wo; ?>" 
                                                                   class="red" onclick="return confirm('Hapus item ini?')">
                                                                    <i class="ace-icon fa fa-trash-o"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                        <tr class="info">
                                                            <td colspan="3" class="center"><b>TOTAL JASA</b></td>
                                                            <td class="right"><b><?php echo number_format($total_jasa, 0, ',', '.'); ?></b></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="col-xs-12 col-sm-6">
                                                <h5 class="header green">Barang/Part</h5>
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr class="info">
                                                            <th width="5%">No</th>
                                                            <th width="15%">Kode</th>
                                                            <th width="40%">Nama Barang</th>
                                                            <th width="8%">Qty</th>
                                                            <th width="20%">Total</th>
                                                            <th width="12%">Aksi</th>
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
                                                            <td class="center">
                                                                <a href="workorder-detail-hapus.php?id=<?php echo $tampil['id']; ?>&kode=<?php echo $kode_wo; ?>" 
                                                                   class="red" onclick="return confirm('Hapus item ini?')">
                                                                    <i class="ace-icon fa fa-trash-o"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                        <tr class="info">
                                                            <td colspan="4" class="center"><b>TOTAL BARANG</b></td>
                                                            <td class="right"><b><?php echo number_format($total_barang, 0, ',', '.'); ?></b></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-xs-12">
                                                <table class="table table-bordered">
                                                    <tr class="warning">
                                                        <td width="70%" class="center"><h4><b>GRAND TOTAL WORK ORDER</b></h4></td>
                                                        <td width="30%" class="right">
                                                            <h4><b>Rp <?php echo number_format($total_jasa + $total_barang, 0, ',', '.'); ?></b></h4>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            
                        </form>

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

        <script type="text/javascript">
            function openJasaPopup() {
                var popup = window.open('jasa-search-popup.php', 'jasaPopup', 'width=800,height=600,scrollbars=yes,resizable=yes');
                popup.focus();
            }
            
            function openBarangPopup() {
                var popup = window.open('barang-search-popup.php', 'barangPopup', 'width=800,height=600,scrollbars=yes,resizable=yes');
                popup.focus();
            }
            
            function cariJasa() {
                var kode = document.getElementById('kode_jasa').value;
                if(kode == '') {
                    alert('Masukkan kode jasa terlebih dahulu');
                    return;
                }
                
                // AJAX untuk cari jasa
                $.ajax({
                    url: 'ajax-cari-jasa.php',
                    type: 'POST',
                    data: {kode: kode},
                    dataType: 'json',
                    success: function(response) {
                        if(response.found) {
                            document.getElementById('nama_jasa').value = response.nama;
                            document.getElementById('harga_jasa').value = response.harga;
                        } else {
                            alert('Jasa tidak ditemukan');
                            document.getElementById('nama_jasa').value = '';
                            document.getElementById('harga_jasa').value = '';
                        }
                    },
                    error: function() {
                        alert('Error saat mencari jasa');
                    }
                });
            }
            
            function cariBarang() {
                var kode = document.getElementById('kode_barang').value;
                if(kode == '') {
                    alert('Masukkan kode barang terlebih dahulu');
                    return;
                }
                
                // AJAX untuk cari barang
                $.ajax({
                    url: 'ajax-cari-barang.php',
                    type: 'POST',
                    data: {kode: kode},
                    dataType: 'json',
                    success: function(response) {
                        if(response.found) {
                            document.getElementById('nama_barang').value = response.nama;
                            document.getElementById('harga_barang').value = response.harga;
                        } else {
                            alert('Barang tidak ditemukan');
                            document.getElementById('nama_barang').value = '';
                            document.getElementById('harga_barang').value = '';
                        }
                    },
                    error: function() {
                        alert('Error saat mencari barang');
                    }
                });
            }
        </script>
        
	</body>
</html>

<?php 
	}
?>