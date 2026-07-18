<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];	
		$kd_cabang=$_SESSION['_cabang'];		                
		include "../config/koneksi.php";
        include_once "../lib/rbac.php";
        rbac_require_any(array('input_servis_read','servis_reguler_read','servis_menu_read','service_create','service_update'));
        
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
    
    // ------- Load Setting Highlight Member ----------
    $highlight_settings = array();
    $query_highlight = mysqli_query($koneksi, "SELECT kategori_member, background_color, text_color, border_color, border_width, is_bold, opacity, is_active FROM setting_highlight_member WHERE is_active = 1");
    while($row_highlight = mysqli_fetch_assoc($query_highlight)) {
        $highlight_settings[$row_highlight['kategori_member']] = $row_highlight;
    }
    // --------------------
    
		$tgl_skr=date('d');	
		$bulan_skr=date('m');
		$thn_skr=date('Y');

        // Query alternatif yang lebih fleksibel menggunakan LEFT JOIN
        $serviceCustomerMapSql = "(SELECT ts.no_polisi, SUBSTRING_INDEX(GROUP_CONCAT(ts.no_pelanggan ORDER BY ts.tanggal DESC, ts.jam DESC, ts.no_service DESC SEPARATOR '||'), '||', 1) AS no_pelanggan_map
                                   FROM tblservice ts
                                   WHERE ts.no_pelanggan IS NOT NULL AND ts.no_pelanggan <> ''
                                   GROUP BY ts.no_polisi)";

        $sql_query="SELECT
                    k.nopolisi as nopolisi,
                    COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) as pemilik,
                    k.tipe,
                    k.jenis,
                    k.warna,
                    COALESCE(pm.merek, 'Unknown') as merek,
                    COALESCE(p_map.telephone, p_plate.telephone, 'N/A') as telephone,
                    COALESCE(sp_map.status_member, sp_plate.status_member, 'Bronze') as kategori_member,
                    COALESCE(sp_map.lama_tidak_datang, sp_plate.lama_tidak_datang, 0) as lama_tidak_datang,
                    COALESCE(sp_map.estimasi_datang_berikutnya, sp_plate.estimasi_datang_berikutnya) as estimasi_datang_berikutnya,
                    COALESCE(sp_map.jumlah_kunjungan, sp_plate.jumlah_kunjungan, 0) as jumlah_kunjungan
                    FROM tblkendaraan k
                    LEFT JOIN {$serviceCustomerMapSql} map ON map.no_polisi = k.nopolisi
                    LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = map.no_pelanggan_map
                    LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
                    LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                    LEFT JOIN statistik_pelanggan sp_map ON p_map.nopelanggan = sp_map.no_pelanggan
                    LEFT JOIN statistik_pelanggan sp_plate ON p_plate.nopelanggan = sp_plate.no_pelanggan
                    ORDER BY COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) ASC
                    LIMIT 50";

        $hasil="Data Pelanggan & Kendaraan (50 data terbaru)";
        $txtsearch="";

        // Hitung total data dari tblkendaraan
        $cari_total=mysqli_query($koneksi,"SELECT count(*) as total FROM tblkendaraan");
        $tm_total=mysqli_fetch_array($cari_total);
        $total_data=$tm_total['total'];

		if(isset($_POST['btncari'])) {
			$txtsearch= mysqli_real_escape_string($koneksi, $_POST['txtsearch']);

            if(!empty(trim($txtsearch))) {
                $sql_query="SELECT
                            k.nopolisi as nopolisi,
                            COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) as pemilik,
                            k.tipe,
                            k.jenis,
                            k.warna,
                            COALESCE(pm.merek, 'Unknown') as merek,
                            COALESCE(p_map.telephone, p_plate.telephone, 'N/A') as telephone,
                            COALESCE(sp_map.status_member, sp_plate.status_member, 'Bronze') as kategori_member,
                    COALESCE(sp_map.lama_tidak_datang, sp_plate.lama_tidak_datang, 0) as lama_tidak_datang,
                    COALESCE(sp_map.estimasi_datang_berikutnya, sp_plate.estimasi_datang_berikutnya) as estimasi_datang_berikutnya,
                    COALESCE(sp_map.jumlah_kunjungan, sp_plate.jumlah_kunjungan, 0) as jumlah_kunjungan
                            FROM tblkendaraan k
                            LEFT JOIN {$serviceCustomerMapSql} map ON map.no_polisi = k.nopolisi
                            LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = map.no_pelanggan_map
                            LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
                            LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                            LEFT JOIN statistik_pelanggan sp_map ON p_map.nopelanggan = sp_map.no_pelanggan
                            LEFT JOIN statistik_pelanggan sp_plate ON p_plate.nopelanggan = sp_plate.no_pelanggan
                            WHERE
                            (k.nopolisi like '%".$txtsearch."%') OR
                            (COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) like '%".$txtsearch."%') OR
                            (COALESCE(p_map.telephone, p_plate.telephone, '') like '%".$txtsearch."%')
                            ORDER BY COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) ASC";

                $cari_kd=mysqli_query($koneksi,"SELECT
                                                count(*) as tot
                                                FROM tblkendaraan k
                                                LEFT JOIN {$serviceCustomerMapSql} map ON map.no_polisi = k.nopolisi
                                                LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = map.no_pelanggan_map
                                                LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
                                                WHERE
                                                (k.nopolisi like '%".$txtsearch."%') OR
                                                (COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) like '%".$txtsearch."%') OR
                                                (COALESCE(p_map.telephone, p_plate.telephone, '') like '%".$txtsearch."%')");
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];

                if($tot > 0) {
                    $hasil="Hasil Pencarian untuk '<strong>".$txtsearch."</strong>' ditemukan ".$tot." data.";
                } else {
                    $hasil="Pencarian untuk '<strong>".$txtsearch."</strong>' tidak ditemukan. Total data keseluruhan: ".$total_data;
                }
            } else {
                // Jika search kosong, kembali ke default
                $sql_query="SELECT
                            k.nopolisi as nopolisi,
                            COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) as pemilik,
                            k.tipe,
                            k.jenis,
                            k.warna,
                            COALESCE(pm.merek, 'Unknown') as merek,
                            COALESCE(p_map.telephone, p_plate.telephone, 'N/A') as telephone,
                            COALESCE(sp_map.status_member, sp_plate.status_member, 'Bronze') as kategori_member,
                    COALESCE(sp_map.lama_tidak_datang, sp_plate.lama_tidak_datang, 0) as lama_tidak_datang,
                    COALESCE(sp_map.estimasi_datang_berikutnya, sp_plate.estimasi_datang_berikutnya) as estimasi_datang_berikutnya,
                    COALESCE(sp_map.jumlah_kunjungan, sp_plate.jumlah_kunjungan, 0) as jumlah_kunjungan
                            FROM tblkendaraan k
                            LEFT JOIN {$serviceCustomerMapSql} map ON map.no_polisi = k.nopolisi
                            LEFT JOIN tblpelanggan p_map ON p_map.nopelanggan = map.no_pelanggan_map
                            LEFT JOIN tblpelanggan p_plate ON p_plate.nopelanggan = k.nopolisi
                            LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                            LEFT JOIN statistik_pelanggan sp_map ON p_map.nopelanggan = sp_map.no_pelanggan
                            LEFT JOIN statistik_pelanggan sp_plate ON p_plate.nopelanggan = sp_plate.no_pelanggan
                            ORDER BY COALESCE(p_map.namapelanggan, p_plate.namapelanggan, k.pemilik) ASC
                            LIMIT 50";
                $hasil="Mohon masukkan kata kunci pencarian. Total data keseluruhan: ".$total_data;
            }
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

    <!-- Custom CSS for Member Highlight -->
    <style>
        /* Highlight Styles untuk Kategori Member */
        /* Highlight Styles untuk Kategori Member */
        <?php foreach($highlight_settings as $kategori => $setting): ?>
        .member-highlight-<?php echo strtolower(str_replace(' ', '-', $kategori)); ?> {
            background-color: <?php echo $setting['background_color']; ?> !important;
            color: <?php echo $setting['text_color']; ?> !important;
            opacity: <?php echo $setting['opacity']; ?>;
            <?php if($setting['border_width'] > 0 && $setting['border_color']): ?>
            border-left: <?php echo $setting['border_width']; ?>px solid <?php echo $setting['border_color']; ?> !important;
            <?php endif; ?>
            <?php if($setting['is_bold']): ?>
            font-weight: bold !important;
            <?php endif; ?>
            transition: all 0.3s ease;
        }
        .member-highlight-<?php echo strtolower(str_replace(' ', '-', $kategori)); ?>:hover {
            opacity: 1 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        <?php endforeach; ?>
        
        /* Legend untuk Kategori Member */
        .member-legend {
            display: inline-block;
            padding: 5px 10px;
            margin: 5px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
    </style>

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
							<?php include "../lib/logo.php"; ?>
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
								<a href="#">Servis Reguler</a>
							</li>                            
							<li class="active">Input Servis</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

						<div class="page-header">
							<h1>
								Input Servis
								<small>
									<i class="ace-icon fa fa-angle-double-right"></i>
									Cari Nopol Konsumen Servis
								</small>
							</h1>
						</div><!-- /.page-header -->

						<div class="row">
							<div class="col-xs-10">
                                <form class="form-horizontal" role="form" action="" method="post">
									<div class="form-group">
										<label class="col-sm-4 control-label no-padding-right" for="txtsearch">
                                        No. Polisi / Nama Pemilik / No. Telepon : </label>
										<div class="col-sm-8">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                name="txtsearch" id="txtsearch"
                                                value="<?php echo htmlspecialchars($txtsearch); ?>"
                                                placeholder="Masukkan kata kunci pencarian..." autocomplete="off" />
                                                <div class="input-group-btn">
                                                    <button type="submit" class="btn btn-primary btn-sm"
                                                    id="btncari" name="btncari" title="Cari Data">
                                                        <i class="ace-icon fa fa-search"></i>
                                                    </button>
                                                    <?php if(!empty($txtsearch)): ?>
                                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-warning btn-sm" title="Clear Search">
                                                        <i class="ace-icon fa fa-times"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-info btn-sm" title="Refresh Data">
                                                        <i class="ace-icon fa fa-refresh"></i>
                                                    </a>
                                                </div>
                                            </div>
                                            <span class="help-block">
                                                <small>Cari berdasarkan nomor polisi, nama pemilik, atau nomor telepon</small>
                                            </span>
										</div>
									</div>
                                </form>
                            </div>
                            <div class="col-xs-2">
                                <div class="text-right">
                                    <button class="btn btn-success btn-block" onclick="window.location.href='input_pelanggan_awal.php'">
                                        <i class="ace-icon fa fa-plus"></i>
                                        Pelanggan Baru
                                    </button>
                                </div>
                            </div>                            

                        <div class="row">
							<div class="col-xs-12 col-sm-12">
                                <!-- Legend Kategori Member -->
                                <div class="alert alert-info" style="margin-bottom: 10px; padding: 10px;">
                                    <strong><i class="ace-icon fa fa-info-circle"></i> Legend Kategori Member:</strong>
                                    <div style="margin-top: 8px;">
                                        <?php foreach($highlight_settings as $kategori => $setting): ?>
                                        <span class="member-legend member-highlight-<?php echo strtolower(str_replace(' ', '-', $kategori)); ?>" 
                                              style="background-color: <?php echo $setting['background_color']; ?>; 
                                                     color: <?php echo $setting['text_color']; ?>;
                                                     border-left: <?php echo $setting['border_width']; ?>px solid <?php echo $setting['border_color']; ?>;">
                                            <?php echo strtoupper($kategori); ?>
                                        </span>
                                        <?php endforeach; ?>
                                        <a href="setting-highlight-member.php" class="btn btn-xs btn-warning pull-right" title="Atur Warna Highlight">
                                            <i class="ace-icon fa fa-cog"></i> Atur Highlight
                                        </a>
                                    </div>
                                </div>

								<div class="table-header">
									<?php echo $hasil; ?>
								</div>                            
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td width="8%"></td>
                                            <td width="9%">No. Polisi</td>
                                            <td width="17%">Nama Pelanggan</td>
                                            <td width="9%" align="center">Tipe</td>
                                            <td width="8%" align="center">Jenis</td>
                                            <td width="9%">Telepon</td>
                                            <td width="8%" align="center">Warna</td>
                                            <td width="9%" align="center">Kategori Member</td>
                                            <td width="10%" align="center">Terakhir Datang</td>
                                            <td width="13%" align="center">Estimasi Berikutnya</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $sql = mysqli_query($koneksi,$sql_query);
                                            $jumlah_data = mysqli_num_rows($sql);

                                            if($jumlah_data > 0) {
                                                while ($tampil = mysqli_fetch_array($sql)) {
                                                    $kategori_member = $tampil['kategori_member'];
                                                    $highlight_class = 'member-highlight-' . strtolower(str_replace(' ', '-', $kategori_member));
                                        ?>
                                        <tr class="<?php echo $highlight_class; ?>">
                                            <td class="center">
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow">
                                                        Aksi
                                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-default">
                                                        <li>
                                                            <a href="#" onclick="validatePhoneNumber('<?php echo htmlspecialchars($tampil['nopolisi']); ?>', '<?php echo htmlspecialchars($tampil['telephone']); ?>', '<?php echo htmlspecialchars($tampil['pemilik']); ?>', 'reguler')">
                                                                <i class="fa fa-wrench blue"></i> Input Servis Reguler
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#" onclick="validatePhoneNumber('<?php echo htmlspecialchars($tampil['nopolisi']); ?>', '<?php echo htmlspecialchars($tampil['telephone']); ?>', '<?php echo htmlspecialchars($tampil['pemilik']); ?>', 'jemput')">
                                                                <i class="fa fa-truck orange"></i> Input Servis Jemput Antar
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->
                                            </td>
                                            <td><strong><?php echo htmlspecialchars($tampil['nopolisi']); ?></strong></td>
                                            <td><?php echo $tampil['pemilik'] !== '' && $tampil['pemilik'] !== null ? htmlspecialchars($tampil['pemilik']) : '<span class="text-muted"><em>Data belum lengkap</em></span>'; ?></td>
                                            <td class="center"><?php echo htmlspecialchars($tampil['tipe']); ?></td>
                                            <td class="center"><?php echo htmlspecialchars($tampil['jenis']); ?></td>
                                            <td>
                                                <?php
                                                    $phone = $tampil['telephone'];
                                                    if($phone && $phone != 'N/A') {
                                                        echo '<i class="ace-icon fa fa-phone green"></i> ' . htmlspecialchars($phone);
                                                    } else {
                                                        echo '<span class="text-muted">Tidak ada</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="center"><?php echo htmlspecialchars($tampil['warna']); ?></td>
                                            <td class="center">
                                                <?php
                                                    $kategori = $tampil['kategori_member'];
                                                    
                                                    // Use settings from database if available, otherwise fallback
                                                    $setting = $highlight_settings[$kategori] ?? null;
                                                    
                                                    // Get icon from master_kategori_member if possible, or use default
                                                    // Ideally we should join with master_kategori_member in the main query, 
                                                    // but for now let's rely on the highlight settings or a simple lookup if needed.
                                                    // Since we don't have the icon in $tampil, we can try to match with common ones or leave it.
                                                    // However, the user wants it dynamic. 
                                                    // Let's assume the icon is part of the category name or we just display the name with the style.
                                                    
                                                    // Better approach: The highlight settings are already loaded in $highlight_settings
                                                    // We can use the style from there.
                                                    
                                                    if($setting) {
                                                        $style = "background-color: {$setting['background_color']}; color: {$setting['text_color']}; border: 1px solid {$setting['border_color']};";
                                                        echo '<span class="label" style="' . $style . '">' . strtoupper($kategori) . '</span>';
                                                    } else {
                                                        echo '<span class="label label-default">' . strtoupper($kategori) . '</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="center">
                                                <?php
                                                    $lama = (int)($tampil['lama_tidak_datang'] ?? 0);
                                                    $kunjungan = (int)($tampil['jumlah_kunjungan'] ?? 0);
                                                    if($kunjungan === 0) {
                                                        echo '<span class="text-muted"><small>Belum pernah</small></span>';
                                                    } elseif($lama === 0) {
                                                        echo '<span class="label label-success">Hari ini</span>';
                                                    } elseif($lama > 90) {
                                                        echo '<span class="label label-danger">' . $lama . ' hari</span>';
                                                    } elseif($lama > 45) {
                                                        echo '<span class="label label-warning">' . $lama . ' hari</span>';
                                                    } else {
                                                        echo '<span class="label label-success">' . $lama . ' hari</span>';
                                                    }
                                                ?>
                                            </td>
                                            <td class="center">
                                                <?php
                                                    $estimasi = $tampil['estimasi_datang_berikutnya'] ?? '';
                                                    if($estimasi && $estimasi !== '0000-00-00') {
                                                        $est_fmt = date('d/m/Y', strtotime($estimasi));
                                                        $est_ts  = strtotime($estimasi);
                                                        $today_ts = strtotime(date('Y-m-d'));
                                                        $selisih = (int)(($est_ts - $today_ts) / 86400);
                                                        if($selisih < 0) {
                                                            echo '<span class="label label-danger"><i class="fa fa-clock-o"></i> ' . $est_fmt . '</span>';
                                                        } elseif($selisih <= 7) {
                                                            echo '<span class="label label-warning"><i class="fa fa-calendar"></i> ' . $est_fmt . '</span>';
                                                        } else {
                                                            echo '<span class="label label-info"><i class="fa fa-calendar"></i> ' . $est_fmt . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted"><small>-</small></span>';
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                                }
                                            } else {
                                        ?>
                                        <tr>
                                            <td colspan="10" class="center">
                                                <div class="alert alert-warning" style="margin: 20px;">
                                                    <i class="ace-icon fa fa-exclamation-triangle bigger-120"></i>
                                                    <strong>Tidak ada data!</strong><br>
                                                    <?php if(isset($_POST['btncari']) && !empty($txtsearch)): ?>
                                                        Tidak ditemukan data untuk pencarian "<strong><?php echo htmlspecialchars($txtsearch); ?></strong>".
                                                    <?php else: ?>
                                                        Belum ada data pelanggan dan kendaraan.
                                                    <?php endif; ?>
                                                    <br><br>
                                                    <a href="input_pelanggan_awal.php" class="btn btn-primary btn-sm">
                                                        <i class="ace-icon fa fa-plus"></i>
                                                        Tambah Data Baru
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
		<script src="assets/js/dataTables.buttons.min.js"></script>
		<script src="assets/js/buttons.flash.min.js"></script>
		<script src="assets/js/buttons.html5.min.js"></script>
		<script src="assets/js/buttons.print.min.js"></script>
		<script src="assets/js/buttons.colVis.min.js"></script>
		<script src="assets/js/dataTables.select.min.js"></script>
		<?php include "includes/datatables-defaults.php"; ?>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->

		<!-- Modal Preview Diskon -->
		<style>
			#discountPreviewModal .modal-body {
				max-height: 60vh;
				overflow-y: auto;
			}
			#discountPreviewModal .modal-footer {
				background: #f5f5f5;
				border-top: 2px solid #ddd;
				padding: 15px 20px;
			}
			#discountPreviewModal .modal-footer .btn {
				min-width: 150px;
				padding: 10px 20px;
				font-size: 14px;
			}
			#discountPreviewModal .modal-footer .btn-success {
				background: linear-gradient(135deg, #27ae60, #2ecc71);
				border: none;
			}
			#discountPreviewModal .modal-footer .btn-warning {
				background: linear-gradient(135deg, #e67e22, #f39c12);
				border: none;
				color: white;
			}
			.service-type-badge {
				display: inline-block;
				padding: 5px 15px;
				border-radius: 20px;
				font-weight: bold;
				margin-left: 10px;
			}
			.service-type-reguler {
				background: #3498db;
				color: white;
			}
			.service-type-jemput {
				background: #e67e22;
				color: white;
			}
		</style>
		<div class="modal fade" id="discountPreviewModal" tabindex="-1" role="dialog" data-backdrop="static">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
						<button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">&times;</button>
						<h4 class="modal-title">
							<i class="ace-icon fa fa-gift"></i>
							Preview Diskon & Riwayat Servis
							<span id="serviceTypeBadge" class="service-type-badge"></span>
						</h4>
					</div>
					<div class="modal-body" id="discountPreviewContent">
						<div class="text-center">
							<i class="ace-icon fa fa-spinner fa-spin fa-3x blue"></i>
							<p class="bigger-110">Memuat data...</p>
						</div>
					</div>
					<div class="modal-footer">
						<input type="hidden" id="previewNopol" value="">
						<input type="hidden" id="previewServiceType" value="">
						<input type="hidden" id="previewDiscountType" value="">
						<input type="hidden" id="previewDiscountValue" value="">

						<div class="row" style="width: 100%;">
							<div class="col-xs-4 text-left">
								<button type="button" class="btn btn-default btn-lg" data-dismiss="modal">
									<i class="ace-icon fa fa-times"></i> Batal
								</button>
							</div>
							<div class="col-xs-8 text-right">
								<button type="button" class="btn btn-warning btn-lg" id="btnSkipDiscount" onclick="proceedWithoutDiscount()">
									<i class="ace-icon fa fa-forward"></i> Tanpa Diskon
								</button>
								<button type="button" class="btn btn-success btn-lg" id="btnApplyDiscount" onclick="proceedWithDiscount()">
									<i class="ace-icon fa fa-check"></i> Terapkan Diskon & Lanjut
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

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
					  null, null,null, null, null,
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
			
			
					select: {
						style: 'multi'
					}
			    } );
			
				
				
				// Export buttons are disabled on this page because the legacy
				// DataTables Buttons bundle is incompatible with the local setup
				// and breaks the primary service entry flow.
				
				//style the message box
				var defaultCopyAction = myTable.button(1).action();
				myTable.button(1).action(function (e, dt, button, config) {
					defaultCopyAction(e, dt, button, config);
					$('.dt-button-info').addClass('gritter-item-wrapper gritter-info gritter-center white');
				});
				
				
				var defaultColvisAction = myTable.button(0).action();
				myTable.button(0).action(function (e, dt, button, config) {
					
					defaultColvisAction(e, dt, button, config);
					
					
					if($('.dt-button-collection > .dropdown-menu').length == 0) {
						$('.dt-button-collection')
						.wrapInner('<ul class="dropdown-menu dropdown-light dropdown-caret dropdown-caret" />')
						.find('a').attr('href', '#').wrap("<li />")
					}
					$('.dt-button-collection').appendTo('.tableTools-container .dt-buttons')
				});
			
				////
			
				setTimeout(function() {
					$($('.tableTools-container')).find('a.dt-button').each(function() {
						var div = $(this).find(' > div').first();
						if(div.length == 1) div.tooltip({container: 'body', title: div.parent().text()});
						else $(this).tooltip({container: 'body', title: $(this).text()});
					});
				}, 500);
				
				
				
				
				
				myTable.on( 'select', function ( e, dt, type, index ) {
					if ( type === 'row' ) {
						$( myTable.row( index ).node() ).find('input:checkbox').prop('checked', true);
					}
				} );
				myTable.on( 'deselect', function ( e, dt, type, index ) {
					if ( type === 'row' ) {
						$( myTable.row( index ).node() ).find('input:checkbox').prop('checked', false);
					}
				} );
			
			
			
			
				/////////////////////////////////
				//table checkboxes
				$('th input[type=checkbox], td input[type=checkbox]').prop('checked', false);
				
				//select/deselect all rows according to table header checkbox
				$('#dynamic-table > thead > tr > th input[type=checkbox], #dynamic-table_wrapper input[type=checkbox]').eq(0).on('click', function(){
					var th_checked = this.checked;//checkbox inside "TH" table header
					
					$('#dynamic-table').find('tbody > tr').each(function(){
						var row = this;
						if(th_checked) myTable.row(row).select();
						else  myTable.row(row).deselect();
					});
				});
				
				//select/deselect a row when the checkbox is checked/unchecked
				$('#dynamic-table').on('click', 'td input[type=checkbox]' , function(){
					var row = $(this).closest('tr').get(0);
					if(this.checked) myTable.row(row).deselect();
					else myTable.row(row).select();
				});
			
			
			
				$(document).on('click', '#dynamic-table .dropdown-toggle', function(e) {
					e.stopImmediatePropagation();
					e.stopPropagation();
					e.preventDefault();
				});
				
				
				
				//And for the first simple table, which doesn't have TableTools or dataTables
				//select/deselect all rows according to table header checkbox
				var active_class = 'active';
				$('#simple-table > thead > tr > th input[type=checkbox]').eq(0).on('click', function(){
					var th_checked = this.checked;//checkbox inside "TH" table header
					
					$(this).closest('table').find('tbody > tr').each(function(){
						var row = this;
						if(th_checked) $(row).addClass(active_class).find('input[type=checkbox]').eq(0).prop('checked', true);
						else $(row).removeClass(active_class).find('input[type=checkbox]').eq(0).prop('checked', false);
					});
				});
				
				//select/deselect a row when the checkbox is checked/unchecked
				$('#simple-table').on('click', 'td input[type=checkbox]' , function(){
					var $row = $(this).closest('tr');
					if($row.is('.detail-row ')) return;
					if(this.checked) $row.addClass(active_class);
					else $row.removeClass(active_class);
				});
			
				
			
				/********************************/
				//add tooltip for small view action buttons in dropdown menu
				$('[data-rel="tooltip"]').tooltip({placement: tooltip_placement});
				
				//tooltip placement on right or left
				function tooltip_placement(context, source) {
					var $source = $(source);
					var $parent = $source.closest('table')
					var off1 = $parent.offset();
					var w1 = $parent.width();
			
					var off2 = $source.offset();
					//var w2 = $source.width();
			
					if( parseInt(off2.left) < parseInt(off1.left) + parseInt(w1 / 2) ) return 'right';
					return 'left';
				}
				
				
				
				
				/***************/
				$('.show-details-btn').on('click', function(e) {
					e.preventDefault();
					$(this).closest('tr').next().toggleClass('open');
					$(this).find(ace.vars['.icon']).toggleClass('fa-angle-double-down').toggleClass('fa-angle-double-up');
				});
				/***************/
				
				
				
				
				
				/**
				//add horizontal scrollbars to a simple table
				$('#simple-table').css({'width':'2000px', 'max-width': 'none'}).wrap('<div style="width: 1000px;" />').parent().ace_scroll(
				  {
					horizontal: true,
					styleClass: 'scroll-top scroll-dark scroll-visible',//show the scrollbars on top(default is bottom)
					size: 2000,
					mouseWheelLock: true
				  }
				).css('padding-top', '12px');
				*/
			
			})

			// Phone Number Validation Function
			function validatePhoneNumber(nopol, currentPhone, customerName, serviceType) {
				// Clean phone number for display
				var displayPhone = currentPhone && currentPhone !== 'N/A' ? currentPhone : '';
				
				// Create modal content
				var modalContent = `
					<div class="modal fade" id="phoneValidationModal" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">
										<i class="ace-icon fa fa-phone blue"></i> 
										Validasi Nomor WhatsApp
									</h4>
								</div>
								<div class="modal-body">
									<div class="alert alert-info">
										<i class="ace-icon fa fa-info-circle"></i>
										<strong>Verifikasi data pelanggan sebelum input servis</strong>
									</div>
									
									<div class="form-horizontal">
										<div class="form-group">
											<label class="col-sm-4 control-label">Nomor Polisi:</label>
											<div class="col-sm-8">
												<p class="form-control-static"><strong>${nopol}</strong></p>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label">Nama Pelanggan:</label>
											<div class="col-sm-8">
												<p class="form-control-static">${customerName}</p>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label">Jenis Servis:</label>
											<div class="col-sm-8">
												<p class="form-control-static">
													<span class="label ${serviceType === 'reguler' ? 'label-primary' : 'label-warning'}">
														<i class="ace-icon fa ${serviceType === 'reguler' ? 'fa-wrench' : 'fa-truck'}"></i>
														${serviceType === 'reguler' ? 'Servis Reguler' : 'Servis Jemput Antar'}
													</span>
												</p>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label">Nomor WA Tersimpan:</label>
											<div class="col-sm-8">
												<p class="form-control-static">
													${displayPhone ? '<i class="ace-icon fa fa-phone green"></i> ' + displayPhone : '<span class="text-muted">Tidak ada nomor tersimpan</span>'}
												</p>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-4 control-label"><span class="text-danger">*</span> Nomor WA Aktif:</label>
											<div class="col-sm-8">
												<input type="text" id="newPhoneNumber" class="form-control" 
													   placeholder="Masukkan nomor WA aktif (08xxx atau +628xxx)" 
													   value="${displayPhone}" autocomplete="off" 
													   data-original="${displayPhone}" />
												<span class="help-block">
													<small>Pastikan nomor WA ini aktif untuk komunikasi servis</small>
												</span>
												<div id="phoneValidationError" class="alert alert-danger" style="display:none;margin-top:10px;">
													<i class="ace-icon fa fa-exclamation-triangle"></i>
													<span id="phoneValidationErrorText"></span>
												</div>
											</div>
										</div>
									</div>

									<div class="alert alert-warning">
										<i class="ace-icon fa fa-exclamation-triangle"></i>
										<strong>Penting:</strong> Nomor WA akan digunakan untuk komunikasi progress servis. 
										Pastikan nomor yang dimasukkan benar dan aktif.
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal">
										<i class="ace-icon fa fa-times"></i> Batal
									</button>
									<button type="button" class="btn btn-primary" onclick="checkPhoneChanges('${nopol}', '${serviceType}')">
										<i class="ace-icon fa fa-arrow-right"></i> Lanjut ke Input Servis
									</button>
								</div>
							</div>
						</div>
					</div>
				`;
				
				// Remove existing modal if any
				$('#phoneValidationModal').remove();
				
				// Add modal to body
				$('body').append(modalContent);
				
				// Show modal
				$('#phoneValidationModal').modal('show');
				
				// Focus on phone input
				$('#phoneValidationModal').on('shown.bs.modal', function() {
					$('#newPhoneNumber').focus().select();
				});
				
				// Handle Enter key
				$('#newPhoneNumber').on('keypress', function(e) {
					if (e.which === 13) {
						checkPhoneChanges(nopol, serviceType);
					}
				});
			}

			// Show inline validation error inside the phone modal (replaces blocking alert()).
			function showPhoneValidationError(msg) {
				$('#phoneValidationErrorText').text(msg);
				$('#phoneValidationError').show();
			}

			// Check Phone Changes Function
			function checkPhoneChanges(nopol, serviceType) {
				var newPhone = $('#newPhoneNumber').val().trim();
				var originalPhone = $('#newPhoneNumber').data('original') || '';

				$('#phoneValidationError').hide();

				// Basic validation first
				if (!newPhone) {
					showPhoneValidationError('Nomor WA harus diisi!');
					$('#newPhoneNumber').focus();
					return;
				}

				// Basic phone validation
				var phonePattern = /^(\+62|62|0)[0-9]{9,13}$/;
				if (!phonePattern.test(newPhone)) {
					showPhoneValidationError('Format nomor WA tidak valid! Gunakan format: 08xxx atau +628xxx');
					$('#newPhoneNumber').focus().select();
					return;
				}
				
				// Normalize phone numbers for comparison (remove spaces, dashes, etc.)
				var normalizedNew = newPhone.replace(/[\s\-\(\)]/g, '');
				var normalizedOriginal = originalPhone.replace(/[\s\-\(\)]/g, '');
				
				// Check if phone number has changed
				if (normalizedNew !== normalizedOriginal && originalPhone !== '') {
					showPhoneChangeOptions(nopol, serviceType, newPhone, originalPhone);
				} else {
					// No change or no original phone, proceed directly
					proceedToService(nopol, serviceType, false);
				}
			}

			// Show Phone Change Options Function
			function showPhoneChangeOptions(nopol, serviceType, newPhone, originalPhone) {
				// Close the validation modal first
				$('#phoneValidationModal').modal('hide');
				
				var optionsContent = `
					<div class="modal fade" id="phoneChangeModal" tabindex="-1" role="dialog">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">
										<i class="ace-icon fa fa-question-circle orange"></i> 
										Nomor WhatsApp Berbeda
									</h4>
								</div>
								<div class="modal-body">
									<div class="alert alert-warning">
										<i class="ace-icon fa fa-exclamation-triangle"></i>
										<strong>Perhatian!</strong> Nomor WA yang dimasukkan berbeda dengan yang tersimpan.
									</div>
									
									<div class="row">
										<div class="col-sm-6">
											<div class="widget-box widget-color-blue2">
												<div class="widget-header">
													<h5 class="widget-title smaller">
														<i class="ace-icon fa fa-database"></i>
														Nomor Tersimpan
													</h5>
												</div>
												<div class="widget-body">
													<div class="widget-main">
														<p class="text-center">
															<i class="ace-icon fa fa-phone green bigger-150"></i><br>
															<strong>${originalPhone}</strong>
														</p>
													</div>
												</div>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="widget-box widget-color-orange2">
												<div class="widget-header">
													<h5 class="widget-title smaller">
														<i class="ace-icon fa fa-edit"></i>
														Nomor Baru
													</h5>
												</div>
												<div class="widget-body">
													<div class="widget-main">
														<p class="text-center">
															<i class="ace-icon fa fa-phone orange bigger-150"></i><br>
															<strong>${newPhone}</strong>
														</p>
													</div>
												</div>
											</div>
										</div>
									</div>
									
									<div class="alert alert-info">
										<i class="ace-icon fa fa-info-circle"></i>
										<strong>Pilih salah satu opsi:</strong>
										<ul style="margin-top: 10px; margin-bottom: 0;">
											<li><strong>Update Nomor:</strong> Simpan nomor baru ke database dan lanjut servis</li>
											<li><strong>Gunakan Nomor Lama:</strong> Abaikan perubahan dan gunakan nomor tersimpan</li>
										</ul>
									</div>
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default" data-dismiss="modal">
										<i class="ace-icon fa fa-times"></i> Batal
									</button>
									<button type="button" class="btn btn-warning" onclick="useOriginalPhone('${nopol}', '${serviceType}', '${originalPhone}')">
										<i class="ace-icon fa fa-undo"></i> Gunakan Nomor Lama
									</button>
									<button type="button" class="btn btn-success" onclick="updatePhoneAndProceed('${nopol}', '${serviceType}', '${newPhone}')">
										<i class="ace-icon fa fa-save"></i> Update Nomor & Lanjut
									</button>
								</div>
							</div>
						</div>
					</div>
				`;
				
				// Remove existing modal if any
				$('#phoneChangeModal').remove();
				
				// Add modal to body
				$('body').append(optionsContent);
				
				// Show modal
				$('#phoneChangeModal').modal('show');
			}

			// Use Original Phone Function
			function useOriginalPhone(nopol, serviceType, originalPhone) {
				$('#phoneChangeModal').modal('hide');
				
				// Set the phone back to original and proceed without updating
				$('#newPhoneNumber').val(originalPhone);
				proceedToService(nopol, serviceType, false);
			}

			// Update Phone and Proceed Function
			function updatePhoneAndProceed(nopol, serviceType, newPhone) {
				$('#phoneChangeModal').modal('hide');
				
				// Set the new phone and proceed with update
				$('#newPhoneNumber').val(newPhone);
				proceedToService(nopol, serviceType, true);
			}

			// Proceed to Service Function
			function proceedToService(nopol, serviceType, forceUpdate) {
				var newPhone = $('#newPhoneNumber').val().trim();
				forceUpdate = forceUpdate || false;
				
				// Validate phone number
				if (!newPhone) {
					showPhoneValidationError('Nomor WA harus diisi!');
					$('#newPhoneNumber').focus();
					return;
				}

				// Basic phone validation
				var phonePattern = /^(\+62|62|0)[0-9]{9,13}$/;
				if (!phonePattern.test(newPhone)) {
					showPhoneValidationError('Format nomor WA tidak valid! Gunakan format: 08xxx atau +628xxx');
					$('#newPhoneNumber').focus().select();
					return;
				}
				
				// Show loading on active modal
				var activeModal = $('#phoneValidationModal:visible, #phoneChangeModal:visible');
				if (activeModal.length > 0) {
					activeModal.find('.modal-footer .btn-primary, .modal-footer .btn-success').prop('disabled', true).html('<i class="ace-icon fa fa-spinner fa-spin"></i> Memproses...');
				}
				
				// Update phone number via AJAX if forced or changed
				var originalPhone = $('#newPhoneNumber').data('original') || '';
				if (forceUpdate || (newPhone !== originalPhone && originalPhone !== '')) {
					$.ajax({
						url: 'update_customer_phone.php',
						type: 'POST',
						data: {
							nopol: nopol,
							phone: newPhone
						},
						dataType: 'json',
						success: function(response) {
							if (response.success) {
								redirectToService(nopol, serviceType);
							} else {
								alert('Gagal update nomor WA: ' + response.message);
								if (activeModal.length > 0) {
									activeModal.find('.modal-footer .btn-primary, .modal-footer .btn-success').prop('disabled', false).html('<i class="ace-icon fa fa-arrow-right"></i> Lanjut ke Input Servis');
								}
							}
						},
						error: function() {
							// Continue even if update fails
							console.log('Warning: Failed to update phone number');
							redirectToService(nopol, serviceType);
						}
					});
				} else {
					// No change, proceed directly
					redirectToService(nopol, serviceType);
				}
			}

			// Redirect to Service Function - Modified to show discount preview first
			function redirectToService(nopol, serviceType) {
				$('#phoneValidationModal').modal('hide');
				$('#phoneChangeModal').modal('hide');

				// Show discount preview modal
				showDiscountPreview(nopol, serviceType);
			}

			// Show Discount Preview Modal
			function showDiscountPreview(nopol, serviceType) {
				$('#previewNopol').val(nopol);
				$('#previewServiceType').val(serviceType);

				// Set service type badge
				var badgeHtml = '';
				if(serviceType === 'reguler') {
					badgeHtml = '<span class="service-type-badge service-type-reguler"><i class="fa fa-wrench"></i> REGULER</span>';
				} else if(serviceType === 'jemput') {
					badgeHtml = '<span class="service-type-badge service-type-jemput"><i class="fa fa-motorcycle"></i> JEMPUT</span>';
				}
				$('#serviceTypeBadge').html(badgeHtml);

				// Reset content
				$('#discountPreviewContent').html(`
					<div class="text-center">
						<i class="ace-icon fa fa-spinner fa-spin fa-3x blue"></i>
						<p class="bigger-110">Memuat data diskon dan riwayat servis...</p>
					</div>
				`);

				// Show modal
				$('#discountPreviewModal').modal('show');

				// Load data via AJAX
				$.ajax({
					url: '_ajax/ajax-get-discount-preview.php',
					type: 'POST',
					data: {
						nopol: nopol,
						service_type: serviceType
					},
					dataType: 'json',
					success: function(response) {
						if(response.success) {
							renderDiscountPreview(response);
						} else {
							$('#discountPreviewContent').html(`
								<div class="alert alert-danger">
									<i class="ace-icon fa fa-exclamation-triangle"></i>
									${response.message || 'Gagal memuat data'}
								</div>
							`);
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error:', error);
						$('#discountPreviewContent').html(`
							<div class="alert alert-warning">
								<i class="ace-icon fa fa-exclamation-triangle"></i>
								Tidak dapat memuat data diskon. Anda tetap dapat melanjutkan input servis.
							</div>
						`);
					}
				});
			}

			// Render Discount Preview Content
			function renderDiscountPreview(data) {
				var html = '';
				var customer = data.customer;
				var hasDiscount = false;

				// Customer Info Section
				var lamaHari = customer.lama_tidak_datang || 0;
				var lamaLabel = lamaHari === 0 ? 'Hari ini' : lamaHari + ' hari lalu';
				var lamaClass = lamaHari > 90 ? 'text-danger' : (lamaHari > 45 ? 'text-warning' : 'text-success');
				var estimasiTgl = customer.estimasi_datang_berikutnya || '';
				var estimasiHtml = estimasiTgl
					? `<span class="label label-info"><i class="ace-icon fa fa-calendar"></i> ${estimasiTgl}</span>`
					: '<span class="text-muted">-</span>';
				var intervalHari = customer.rata_jarak_kunjungan > 0
					? 'Setiap ~' + Math.round(customer.rata_jarak_kunjungan) + ' hari'
					: (customer.jumlah_kunjungan <= 1 ? 'Kunjungan pertama' : '-');

				html += `
				<div class="row">
					<div class="col-md-12">
						<div class="widget-box widget-color-blue2">
							<div class="widget-header">
								<h5 class="widget-title"><i class="ace-icon fa fa-user"></i> Data Pelanggan</h5>
							</div>
							<div class="widget-body">
								<div class="widget-main">
									<div class="row">
										<div class="col-sm-6">
											<p><strong>No. Polisi:</strong> ${customer.nopolisi}</p>
											<p><strong>Nama:</strong> ${customer.nama_pelanggan || '-'}</p>
											<p><strong>Telepon:</strong> ${customer.telephone || '-'}</p>
											<p><strong>Motor:</strong> ${customer.merek || '-'} ${customer.tipe || ''}</p>
										</div>
										<div class="col-sm-6">
											<p><strong>Total Kunjungan:</strong> ${customer.jumlah_kunjungan || 0}x</p>
											<p><strong>Total Transaksi:</strong> Rp ${formatNumber(customer.total_nominal || 0)}</p>
											<p><strong>Terakhir Datang:</strong> <span class="${lamaClass}">${lamaLabel}</span></p>
											<p><strong>Interval Servis:</strong> ${intervalHari}</p>
										</div>
									</div>
									${estimasiTgl ? `
									<div class="row" style="margin-top:5px;">
										<div class="col-sm-12">
											<div style="background:#f0f7ff;border-left:3px solid #3498db;padding:6px 10px;border-radius:3px;">
												<i class="ace-icon fa fa-calendar-check-o blue"></i>
												<strong>Estimasi Kunjungan Berikutnya:</strong> ${estimasiHtml}
											</div>
										</div>
									</div>` : ''}
								</div>
							</div>
						</div>
					</div>
				</div>
				<br>`;

				// Discount Section
				html += '<div class="row">';

				// Priority: Diskon Periode > Diskon Member
				if(data.discount_type === 'periode' && data.discount_periode && data.discount_periode.length > 0) {
					hasDiscount = true;
					$('#previewDiscountType').val('periode');

					html += `
					<div class="col-md-12">
						<div class="alert alert-success" style="border-left: 5px solid #2ecc71;">
							<h4><i class="ace-icon fa fa-star"></i> PROMO AKTIF!</h4>
							<p>Ada promo spesial yang berlaku untuk servis ini:</p>
						</div>
					</div>`;

					data.discount_periode.forEach(function(promo, index) {
						var diskonText = promo.tipe_promo === 'persen'
							? promo.nilai_promo + '%'
							: 'Rp ' + formatNumber(promo.nilai_promo);

						html += `
						<div class="col-md-6">
							<div class="widget-box widget-color-green2" style="margin-bottom: 15px;">
								<div class="widget-header">
									<h5 class="widget-title">
										<i class="ace-icon fa fa-tag"></i> ${promo.nama_promo}
									</h5>
									<span class="widget-toolbar">
										<span class="badge badge-warning">${diskonText}</span>
									</span>
								</div>
								<div class="widget-body">
									<div class="widget-main">
										<p>${promo.deskripsi || 'Promo spesial untuk pelanggan setia'}</p>
										<p><small><i class="ace-icon fa fa-calendar"></i> Berlaku: ${promo.tanggal_mulai} s/d ${promo.tanggal_selesai}</small></p>
										<p><small><i class="ace-icon fa fa-cube"></i> Untuk: ${promo.target_type} - ${promo.target_nama || promo.target_id}</small></p>
									</div>
								</div>
							</div>
						</div>`;
					});

					// Store first promo value
					var firstPromo = data.discount_periode[0];
					$('#previewDiscountValue').val(JSON.stringify({
						type: 'periode',
						promo_id: firstPromo.id_promo,
						tipe_promo: firstPromo.tipe_promo,
						nilai: firstPromo.nilai_promo
					}));

				} else if(data.discount_type === 'member' && data.discount_member) {
					hasDiscount = true;
					$('#previewDiscountType').val('member');

					var member = data.discount_member;
					var badgeStyle = `background-color: ${member.warna || '#cd7f32'}; color: white;`;

					html += `
					<div class="col-md-12">
						<div class="alert alert-info" style="border-left: 5px solid ${member.warna || '#3498db'};">
							<h4>
								<span class="label" style="${badgeStyle}">
									${member.icon || '🏅'} ${member.nama_kategori}
								</span>
								DISKON MEMBER
							</h4>
							<p>Anda berhak mendapat diskon sebagai member ${member.nama_kategori}:</p>
						</div>
					</div>

					<div class="col-md-6">
						<div class="widget-box" style="border: 2px solid ${member.warna || '#3498db'};">
							<div class="widget-header" style="background: ${member.warna || '#3498db'}; color: white;">
								<h5 class="widget-title"><i class="ace-icon fa fa-wrench"></i> Diskon Jasa</h5>
							</div>
							<div class="widget-body">
								<div class="widget-main text-center">
									<h1 class="text-success">${member.diskon_jasa || 0}%</h1>
									<p>untuk semua jasa servis</p>
								</div>
							</div>
						</div>
					</div>

					<div class="col-md-6">
						<div class="widget-box" style="border: 2px solid ${member.warna || '#3498db'};">
							<div class="widget-header" style="background: ${member.warna || '#3498db'}; color: white;">
								<h5 class="widget-title"><i class="ace-icon fa fa-cube"></i> Diskon Barang</h5>
							</div>
							<div class="widget-body">
								<div class="widget-main text-center">
									<h1 class="text-primary">${member.diskon_barang || 0}%</h1>
									<p>untuk sparepart</p>
								</div>
							</div>
						</div>
					</div>`;

					// Benefits
					if(member.benefit_text) {
						html += `
						<div class="col-md-12" style="margin-top: 15px;">
							<div class="alert alert-warning">
								<strong><i class="ace-icon fa fa-gift"></i> Benefit Member ${member.nama_kategori}:</strong>
								<ul style="margin-top: 10px;">
									${member.benefit_text.split('\n').map(b => '<li>' + b + '</li>').join('')}
								</ul>
							</div>
						</div>`;
					}

					// Store member discount value
					$('#previewDiscountValue').val(JSON.stringify({
						type: 'member',
						kategori: member.nama_kategori,
						diskon_jasa: member.diskon_jasa,
						diskon_barang: member.diskon_barang
					}));

				} else {
					// No discount available
					$('#previewDiscountType').val('none');

					html += `
					<div class="col-md-12">
						<div class="alert alert-default" style="background: #f9f9f9; border: 1px solid #ddd;">
							<h4><i class="ace-icon fa fa-info-circle"></i> Tidak Ada Diskon Aktif</h4>
							<p>Saat ini tidak ada promo periode atau diskon member yang berlaku.</p>
						</div>
					</div>`;
				}

				// Next Tier Info
				if(data.next_tier && data.to_next_tier > 0) {
					html += `
					<div class="col-md-12" style="margin-top: 15px;">
						<div class="alert alert-info">
							<i class="ace-icon fa fa-arrow-up"></i>
							<strong>Naik ke ${data.next_tier.nama_kategori}!</strong>
							Butuh ${data.exclude_mode === 'kunjungan'
								? Math.ceil(data.to_next_tier) + ' kunjungan lagi'
								: 'Rp ' + formatNumber(data.to_next_tier) + ' transaksi lagi'}
							untuk mendapat diskon ${data.next_tier.diskon_jasa}% jasa & ${data.next_tier.diskon_barang}% barang!
						</div>
					</div>`;
				}

				// Excluded Categories Info
				if(data.excluded_categories && data.excluded_categories.length > 0 && hasDiscount) {
					html += `
					<div class="col-md-12" style="margin-top: 10px;">
						<div class="alert alert-warning">
							<small>
								<i class="ace-icon fa fa-exclamation-circle"></i>
								<strong>Catatan:</strong> Diskon member tidak berlaku untuk kategori:
								${data.excluded_categories.map(c => c.jenis).join(', ')}
							</small>
						</div>
					</div>`;
				}

				html += '</div>';

				// Service History Section
				html += `<hr style="margin: 20px 0;">
				<div class="row">
					<div class="col-md-12">
						<h4><i class="ace-icon fa fa-history blue"></i> Riwayat Servis Sebelumnya</h4>`;

				if(data.service_history && data.service_history.length > 0) {
					html += `
					<div class="table-responsive">
						<table class="table table-bordered table-striped table-condensed">
							<thead>
								<tr class="info">
									<th>No. Service</th>
									<th>Tanggal</th>
									<th>Keluhan</th>
									<th>Mekanik</th>
									<th>Status</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>`;

					data.service_history.forEach(function(hist) {
						var statusClass = hist.status_servis === 'bayar' ? 'success' :
							(hist.status_servis === 'proses' ? 'warning' : 'default');
						var statusText = hist.status_servis === 'bayar' ? 'Selesai' :
							(hist.status_servis === 'proses' ? 'Proses' : hist.status_servis);

						html += `
						<tr>
							<td><small>${hist.no_service}</small></td>
							<td>${hist.tanggal}</td>
							<td>${hist.keluhan || '-'}</td>
							<td>${hist.mekanik_nama || '-'}</td>
							<td><span class="label label-${statusClass}">${statusText}</span></td>
							<td class="text-right">Rp ${formatNumber(hist.total_akhir || hist.subtotal || 0)}</td>
						</tr>`;
					});

					html += `</tbody></table></div>`;
				} else {
					html += `
					<div class="alert alert-info">
						<i class="ace-icon fa fa-info-circle"></i>
						Belum ada riwayat servis untuk kendaraan ini. Ini adalah kunjungan pertama!
					</div>`;
				}

				html += '</div></div>';

				// Keluhan History Section
				if(data.keluhan_history && data.keluhan_history.length > 0) {
					html += `
					<hr style="margin: 20px 0;">
					<div class="row">
						<div class="col-md-12">
							<h4><i class="ace-icon fa fa-comments orange"></i> Riwayat Keluhan</h4>
							<div class="table-responsive">
								<table class="table table-bordered table-condensed">
									<thead>
										<tr class="warning">
											<th width="15%">Tanggal</th>
											<th width="50%">Keluhan</th>
											<th width="15%">Status</th>
											<th width="20%">Catatan</th>
										</tr>
									</thead>
									<tbody>`;

					data.keluhan_history.forEach(function(kel) {
						var statusClass = kel.status_keluhan === 'selesai' ? 'success' :
							(kel.status_keluhan === 'pending' ? 'danger' : 'warning');
						var statusIcon = kel.status_keluhan === 'selesai' ? 'check-circle' :
							(kel.status_keluhan === 'pending' ? 'exclamation-circle' : 'clock-o');

						html += `
						<tr>
							<td><small>${kel.tanggal_service || '-'}</small></td>
							<td>${kel.keluhan || '-'}</td>
							<td>
								<span class="label label-${statusClass}">
									<i class="ace-icon fa fa-${statusIcon}"></i>
									${kel.status_keluhan || 'Tidak diketahui'}
								</span>
							</td>
							<td><small>${kel.catatan_penyelesaian || '-'}</small></td>
						</tr>`;
					});

					html += `</tbody></table></div></div></div>`;
				}

				// Get service type info for buttons and destination
				var serviceType = $('#previewServiceType').val();
				var serviceLabel = (serviceType === 'jemput') ? 'Input Servis Jemput' : 'Input Servis Reguler';
				var serviceIcon = (serviceType === 'jemput') ? 'fa-motorcycle' : 'fa-wrench';

				// Add destination info at bottom of content
				html += `
				<div class="alert alert-default" style="background: #eef7ff; border: 1px solid #3498db; margin-top: 15px;">
					<i class="ace-icon fa ${serviceIcon} blue bigger-120"></i>
					<strong>Tujuan:</strong> Anda akan diarahkan ke halaman <strong>${serviceLabel}</strong>
				</div>`;

				// Update modal content
				$('#discountPreviewContent').html(html);

				// Update buttons based on discount availability and service type
				if(!hasDiscount) {
					$('#btnApplyDiscount').hide();
					$('#btnSkipDiscount')
						.html('<i class="ace-icon fa ' + serviceIcon + '"></i> Lanjut ke ' + serviceLabel)
						.removeClass('btn-warning').addClass('btn-primary');
				} else {
					$('#btnApplyDiscount')
						.html('<i class="ace-icon fa fa-check"></i> Terapkan Diskon & Lanjut ke ' + serviceLabel)
						.show();
					$('#btnSkipDiscount')
						.html('<i class="ace-icon fa fa-forward"></i> Tanpa Diskon')
						.removeClass('btn-primary').addClass('btn-warning');
				}
			}

			// Format number helper
			function formatNumber(num) {
				return parseFloat(num || 0).toLocaleString('id-ID');
			}

			// Proceed with discount
			function proceedWithDiscount() {
				var nopol = $('#previewNopol').val();
				var serviceType = $('#previewServiceType').val();
				var discountType = $('#previewDiscountType').val();
				var discountValue = $('#previewDiscountValue').val();

				// Save discount to session via AJAX
				$.ajax({
					url: '_ajax/ajax-save-discount-session.php',
					type: 'POST',
					data: {
						nopol: nopol,
						service_type: serviceType,
						discount_type: discountType,
						discount_value: discountValue,
						apply_discount: 1
					},
					dataType: 'json',
					success: function(response) {
						finalRedirectToService(nopol, serviceType);
					},
					error: function() {
						// Continue anyway
						finalRedirectToService(nopol, serviceType);
					}
				});
			}

			// Proceed without discount
			function proceedWithoutDiscount() {
				var nopol = $('#previewNopol').val();
				var serviceType = $('#previewServiceType').val();

				// Save to session that user skipped discount
				$.ajax({
					url: '_ajax/ajax-save-discount-session.php',
					type: 'POST',
					data: {
						nopol: nopol,
						service_type: serviceType,
						discount_type: 'none',
						discount_value: '',
						apply_discount: 0
					},
					dataType: 'json',
					complete: function() {
						finalRedirectToService(nopol, serviceType);
					}
				});
			}

			// Final redirect to service page
			function finalRedirectToService(nopol, serviceType) {
				$('#discountPreviewModal').modal('hide');

				// Redirect based on service type
				if (serviceType === 'reguler') {
					window.location.href = 'save-no-servis-reguler.php?snopol=' + encodeURIComponent(nopol);
				} else if (serviceType === 'jemput') {
					window.location.href = 'servis-reguler-jemput.php?snopol=' + encodeURIComponent(nopol);
				}
			}
		</script>
		
	</body>
</html>

<?php 
}
?>
