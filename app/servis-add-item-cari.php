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

		$no_service = $_GET['snoserv'];
		$txtkey= mysqli_real_escape_string($koneksi, $_GET['_key'] ?? '');
		$txtcari= $_GET['_cari'];
		$txturut= $_GET['_urut'];
		$txtflt= $_GET['_flt'];
		$only_applicable = intval($_GET['only_applicable'] ?? 0);
		$tab_param = $_GET['_tab'] ?? 'items'; // Get tab parameter, default to items
		$from_page = $_GET['_from'] ?? 'reguler'; // Track which page user came from: reguler, rst, jemput, jemput-rst
		$kdjasa="";

		$kd_kategori_motor = 0;
		$kd_jenis_motor = 0;
		$kode_tipe_motor = 0;
		$tipe_text = '';
		if (!empty($no_service)) {
			$no_service_safe = mysqli_real_escape_string($koneksi, $no_service);

			$has_view_kat = false;
			$chk_view_kat = mysqli_query($koneksi, "SHOW FULL TABLES LIKE 'view_service_kategori_motor'");
			if ($chk_view_kat && mysqli_num_rows($chk_view_kat) > 0) {
				$has_view_kat = true;
			}
			if ($has_view_kat) {
				$q_kd = mysqli_query($koneksi, "SELECT kd_kategori_motor FROM view_service_kategori_motor WHERE no_service='{$no_service_safe}'");
				if ($q_kd && mysqli_num_rows($q_kd) > 0) {
					$r_kd = mysqli_fetch_assoc($q_kd);
					$kd_kategori_motor = intval($r_kd['kd_kategori_motor'] ?? 0);
				}
			}

			$has_view_jenis = false;
			$chk_view_jenis = mysqli_query($koneksi, "SHOW FULL TABLES LIKE 'view_service_jenis_motor'");
			if ($chk_view_jenis && mysqli_num_rows($chk_view_jenis) > 0) {
				$has_view_jenis = true;
			}
			if ($has_view_jenis) {
				$q_kdj = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
				if ($q_kdj && mysqli_num_rows($q_kdj) > 0) {
					$r_kdj = mysqli_fetch_assoc($q_kdj);
					$kd_jenis_motor = intval($r_kdj['kd_jenis_motor'] ?? 0);
				}
			}

			$q_srv = mysqli_query($koneksi, "SELECT s.no_polisi, s.no_pelanggan,
										k.kode_tipe, k.tipe, k.kode_jenis,
										p.tipe_id as tipe_id_pelanggan, p.jenis_id as jenis_id_pelanggan
										FROM tblservice s
										LEFT JOIN tblkendaraan k ON k.nopolisi=s.no_polisi
										LEFT JOIN tblpelanggan p ON p.nopelanggan=s.no_pelanggan
										WHERE s.no_service='{$no_service_safe}'
										LIMIT 1");
			if ($q_srv && mysqli_num_rows($q_srv) > 0) {
				$r_srv = mysqli_fetch_assoc($q_srv);
				$kode_tipe_motor = intval($r_srv['tipe_id_pelanggan'] ?? 0);
				if ($kode_tipe_motor <= 0) {
					$kode_tipe_motor = intval($r_srv['kode_tipe'] ?? 0);
				}
				$tipe_text = trim((string)($r_srv['tipe'] ?? ''));
				$jenis_from_pelanggan = intval($r_srv['jenis_id_pelanggan'] ?? 0);
				if ($kd_jenis_motor <= 0 && $jenis_from_pelanggan > 0) {
					$kd_jenis_motor = $jenis_from_pelanggan;
				}
				$kd_jenis_motor = $kd_jenis_motor > 0 ? $kd_jenis_motor : intval($r_srv['kode_jenis'] ?? 0);
			}

			if ($kode_tipe_motor <= 0 && $tipe_text !== '') {
				$tipe_text_safe = mysqli_real_escape_string($koneksi, $tipe_text);
				$tipe_norm = strtoupper(str_replace([' ', '-'], '', $tipe_text));
				$tipe_norm_safe = mysqli_real_escape_string($koneksi, $tipe_norm);
				$q_tm = mysqli_query($koneksi, "SELECT kode_tipe, kode_kategori
										FROM tbtipe_motor
										WHERE UPPER(REPLACE(REPLACE(tipe,' ',''),'-',''))='{$tipe_norm_safe}'
										LIMIT 1");
				if ($q_tm && mysqli_num_rows($q_tm) > 0) {
					$r_tm = mysqli_fetch_assoc($q_tm);
					$kode_tipe_motor = intval($r_tm['kode_tipe'] ?? 0);
					if ($kd_kategori_motor <= 0) {
						$kd_kategori_motor = intval($r_tm['kode_kategori'] ?? 0);
					}
				}
			}
			if ($kode_tipe_motor > 0 && $kd_kategori_motor <= 0) {
                $q_kat_tm = mysqli_query($koneksi, "SELECT kode_kategori FROM tbtipe_motor WHERE kode_tipe={$kode_tipe_motor} LIMIT 1");
                if ($q_kat_tm && mysqli_num_rows($q_kat_tm) > 0) {
                    $r_kat_tm = mysqli_fetch_assoc($q_kat_tm);
                    $kd_kategori_motor = intval($r_kat_tm['kode_kategori'] ?? 0);
                }
            }
        }

        // Detect mapping column name for items mapping table
        $item_map_col = 'kd_kategori_motor';
        $chk_item_map = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_kategori_motor'");
        if (!$chk_item_map || mysqli_num_rows($chk_item_map) == 0) {
            $item_map_col = 'kd_jenis_motor';
        }

        $km_sql = intval($kd_kategori_motor);
        $kj_sql = intval($kd_jenis_motor);
        $kt_sql = intval($kode_tipe_motor);
        $map_val = ($item_map_col === 'kd_kategori_motor') ? $km_sql : $kj_sql;

        $applicable_condition = "(
            ({$kt_sql} <= 0 AND {$km_sql} <= 0 AND {$kj_sql} <= 0)
            OR (NOT EXISTS (SELECT 1 FROM tbitem_jenis_motor jm0 WHERE jm0.noitem=v.noitem)
                AND NOT EXISTS (SELECT 1 FROM tblitem_spart sp0 WHERE sp0.noitem=v.noitem))
            OR ({$kt_sql} > 0 AND EXISTS (SELECT 1 FROM tblitem_spart sp WHERE sp.noitem=v.noitem AND sp.kode_tipe={$kt_sql}))
            OR ({$map_val} > 0 AND EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=v.noitem AND jm.".$item_map_col."={$map_val}))
        )";

        $applicable_select = "CASE
                                WHEN {$kt_sql} <= 0 AND {$km_sql} <= 0 AND {$kj_sql} <= 0 THEN 1
                                WHEN NOT EXISTS (SELECT 1 FROM tbitem_jenis_motor jm0 WHERE jm0.noitem=v.noitem)
                                     AND NOT EXISTS (SELECT 1 FROM tblitem_spart sp0 WHERE sp0.noitem=v.noitem) THEN 1
                                WHEN {$kt_sql} > 0 AND EXISTS (SELECT 1 FROM tblitem_spart sp WHERE sp.noitem=v.noitem AND sp.kode_tipe={$kt_sql}) THEN 1
                                WHEN {$map_val} > 0 AND EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=v.noitem AND jm.".$item_map_col."={$map_val}) THEN 1
                                ELSE 0
                             END AS applicable,
                             CASE
                                WHEN {$kt_sql} > 0 AND EXISTS (SELECT 1 FROM tblitem_spart sp WHERE sp.noitem=v.noitem AND sp.kode_tipe={$kt_sql}) THEN 3
                                WHEN {$map_val} > 0 AND EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=v.noitem AND jm.".$item_map_col."={$map_val}) THEN 2
                                WHEN NOT EXISTS (SELECT 1 FROM tbitem_jenis_motor jm0 WHERE jm0.noitem=v.noitem)
                                     AND NOT EXISTS (SELECT 1 FROM tblitem_spart sp0 WHERE sp0.noitem=v.noitem) THEN 1
                                ELSE 0
                             END AS app_score";

        if($txtflt=='asc') {
            $tipebtn1="btn-danger";
            $tipebtn2="btn-info";
        } else {
            $tipebtn1="btn-info";                        
            $tipebtn2="btn-danger";
        }

                
           // Cari ================
        if($txtcari=='') {
            $sql_cari="";
        } else {
            if($txtcari=='30') {
                $sql_cari="noitem";
            }
            if($txtcari=='31') {
                $sql_cari="namaitem";
            }
            if($txtcari=='32') {
                $sql_cari="";
            }
            if($txtcari=='33') {
                $sql_cari="namajenis";
            }
        }
    // end ===========

    
    
    // urut ================
        // Default urut
        $sql_urut = "noitem"; // default sort by noitem

        if($txturut=='34') {
            $sql_urut="noitem";
        }
        if($txturut=='35') {
            $sql_urut="namaitem";
        }
        if($txturut=='36') {
            $sql_urut="noitem"; // fallback to noitem
        }
        if($txturut=='37') {
            $sql_urut="namajenis";
        }
        if($txturut=='52') {
            $sql_urut="noitem"; // default sort for service search
        }

    // end ===========

        if($txtflt=='asc') {
            IF($sql_cari=="") {
                $sql_query=" SELECT v.*, ".$applicable_select." FROM view_cari_item v 
                            WHERE 
                            ((noitem like '%".$txtkey."%') OR 
                            (namaitem like '%".$txtkey."%') OR 
                            (namajenis like '%".$txtkey."%'))";
                if ($only_applicable === 1) {
                    $sql_query .= " AND " . $applicable_condition;
                }
                $sql_query .= "
                            order by app_score DESC, ".$sql_urut." asc"; 

                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_item 
                                                WHERE 
                                                (noitem like '%".$txtkey."%') OR 
                                                (namaitem like '%".$txtkey."%') OR 
                                                (namajenis like '%".$txtkey."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];               
            } ELSE {
                $sql_query=" SELECT v.*, ".$applicable_select." FROM view_cari_item v 
                            WHERE (".$sql_cari." like '%".$txtkey."%')";
                if ($only_applicable === 1) {
                    $sql_query .= " AND " . $applicable_condition;
                }
                $sql_query .= " order by app_score DESC, ".$sql_urut." asc";
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_item 
                            WHERE ".$sql_cari." like '%".$txtkey."%'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];			                        
            }
        } else {
            IF($sql_cari=="") {
                $sql_query=" SELECT v.*, ".$applicable_select." FROM view_cari_item v 
                            WHERE 
                            ((noitem like '%".$txtkey."%') OR 
                            (namaitem like '%".$txtkey."%') OR 
                            (namajenis like '%".$txtkey."%'))";
                if ($only_applicable === 1) {
                    $sql_query .= " AND " . $applicable_condition;
                }
                $sql_query .= "
                            order by app_score DESC, ".$sql_urut." desc"; 

                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_item 
                                                WHERE 
                                                (noitem like '%".$txtkey."%') OR 
                                                (namaitem like '%".$txtkey."%') OR 
                                                (namajenis like '%".$txtkey."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];                               
            } else {
                $sql_query=" SELECT v.*, ".$applicable_select." FROM view_cari_item v 
                            WHERE (".$sql_cari." like '%".$txtkey."%')";
                if ($only_applicable === 1) {
                    $sql_query .= " AND " . $applicable_condition;
                }
                $sql_query .= " order by app_score DESC, ".$sql_urut." desc";
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot FROM view_cari_item 
                            WHERE ".$sql_cari." like '%".$txtkey."%'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];			                                    
            }
        }

        $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";
    
		if(isset($_POST['btnasc'])) {	
			$no_service= $_POST['txtnosrv'];
                        
			$txtkey= $_POST['txtkey'];
			$cbocari= $_POST['cbocari'];
			$cbourut= $_POST['cbourut'];
            $only_applicable_js = isset($_GET['only_applicable']) ? $_GET['only_applicable'] : '0';
            echo"<script>window.location=('servis-add-item-cari.php?snoserv=" . urlencode($no_service) . "&_key=" . urlencode($txtkey) . "&_cari=" . urlencode($cbocari) . "&_urut=" . urlencode($cbourut) . "&_flt=asc&only_applicable=" . urlencode($only_applicable_js) . "');</script>";
        }

		if(isset($_POST['btndesc'])) {
			$no_service= $_POST['txtnosrv'];

			$txtkey= $_POST['txtkey'];
			$cbocari= $_POST['cbocari'];
			$cbourut= $_POST['cbourut'];
            $only_applicable_js = isset($_GET['only_applicable']) ? $_GET['only_applicable'] : '0';
            echo"<script>window.location=('servis-add-item-cari.php?snoserv=" . urlencode($no_service) . "&_key=" . urlencode($txtkey) . "&_cari=" . urlencode($cbocari) . "&_urut=" . urlencode($cbourut) . "&_flt=desc&only_applicable=" . urlencode($only_applicable_js) . "');</script>";
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
								<a href="#">Input Service</a>
							</li>                            
							<li class="active">Cari Item Barang</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
						<?php
							$qs_common = 'snoserv=' . urlencode((string)$no_service)
								. '&_key=' . urlencode((string)$txtkey)
								. '&_cari=' . urlencode((string)$txtcari)
								. '&_urut=' . urlencode((string)$txturut)
								. '&_flt=' . urlencode((string)$txtflt)
								. '&_tab=' . urlencode((string)$tab_param)
								. '&_from=' . urlencode((string)$from_page);
							$url_all = 'servis-add-item-cari.php?' . $qs_common . '&only_applicable=0';
							$url_only = 'servis-add-item-cari.php?' . $qs_common . '&only_applicable=1';
						?>
						<div class="row">
							<div class="col-xs-12">
								<div class="alert alert-info" style="margin-bottom:10px;">
									<strong>Filter Motor:</strong>
									<?php
										$ctx = array();
										if (!empty($tipe_text)) { $ctx[] = 'Tipe: ' . htmlspecialchars($tipe_text); }
										if (!empty($kode_tipe_motor)) { $ctx[] = 'kode_tipe: ' . intval($kode_tipe_motor); }
										if (!empty($kd_kategori_motor)) { $ctx[] = 'kategori: ' . intval($kd_kategori_motor); }
										if (!empty($kd_jenis_motor)) { $ctx[] = 'jenis: ' . intval($kd_jenis_motor); }
										echo !empty($ctx) ? implode(' | ', $ctx) : '<span class="text-danger">Tidak terdeteksi (semua item dianggap applicable)</span>';
									?>
									<div class="pull-right">
										<?php if ($only_applicable === 1): ?>
											<a class="btn btn-xs btn-default" href="<?php echo htmlspecialchars($url_all); ?>">Tampilkan Semua</a>
										<?php else: ?>
											<a class="btn btn-xs btn-primary" href="<?php echo htmlspecialchars($url_only); ?>">Hanya Applicable</a>
										<?php endif; ?>
									</div>
									<div class="clearfix"></div>
								</div>
							</div>
						</div>

						<br>
						<div class="row">
							<div class="col-xs-12 col-sm-12">
								<div class="widget-box">
									<div class="widget-body">
										<div class="widget-main">	
                                            <form class="form-horizontal" action="" method="post" role="form">
                                                <input type="hidden" name="txtnosrv"  class="form-control" value="<?php echo $no_service; ?>"/>
                                                <?php include "_template/_cari-item-service.php"; ?>
                                            </form>
                                        </div>
									</div>
								</div>	
							</div>
						</div>
                        <div class="space space-8"></div> 
                        <div class="row">
							<div class="col-xs-12 col-sm-12">
                                    <div class="widget-header widget-header-green widget-header-flat">
                                        <h4 class="widget-title lighter">
                                            <i class="ace-icon fa fa-list"></i>
                                            <?php echo $hasil_cari; ?>
                                        </h4>
                                    </div>
                                    
                                    <div class="widget-body">
                                        <div class="widget-main no-padding">
                                            <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover" style="table-layout: fixed; width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th class="center" style="width: 40px;">Aksi</th>
                                                        <th style="width: 100px;">Kode Item</th>
                                                        <th style="width: 80px;">Barcode</th>
                                                        <th>Nama Item</th>
                                                        <th style="width: 130px;">Jenis</th>
                                                        <th class="center" style="width: 45px;">Stok</th>
                                                        <th class="center" style="width: 40px;">Min.</th>
                                                        <th style="width: 50px;">Satuan</th>
                                                        <th style="width: 45px;">Rak</th> 
                                                        <th class="center" style="width: 80px;">Harga Pokok</th>
                                                        <th class="center" style="width: 80px;">Harga Jual</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                    <?php
                                        // Debug: tampilkan query jika ada error
                                        $sql = mysqli_query($koneksi,$sql_query);

                                        if(!$sql) {
                                            echo "<tr><td colspan='11' class='center'>";
                                            echo "<div class='alert alert-danger'>";
                                            echo "<strong>Error Query:</strong><br>";
                                            echo mysqli_error($koneksi);
                                            echo "<br><br><strong>Query:</strong><br>";
                                            echo htmlspecialchars($sql_query);
                                            echo "</div>";
                                            echo "</td></tr>";
                                        } else if(mysqli_num_rows($sql) == 0) {
                                            echo "<tr><td colspan='11' class='center'>";
                                            echo "<div class='alert alert-warning'>";
                                            echo "<i class='fa fa-info-circle'></i> ";
                                            echo "Tidak ada data yang ditemukan. Silakan ubah kata kunci pencarian.";
                                            echo "</div>";
                                            echo "</td></tr>";
                                        }

                                        while ($tampil = mysqli_fetch_array($sql)) {
                                            $noitem=$tampil['noitem'];
                                            $stokmin=$tampil['stokmin'];

                                            // Query stok dengan fallback jika VIEW tidak ada
                                            $cari_kd=mysqli_query($koneksi,"SELECT saldo
                                                                            FROM view_stok_master
                                                                            WHERE
                                                                            kd_cabang='$kd_cabang' AND
                                                                            no_item='$noitem'");

                                            if($cari_kd && mysqli_num_rows($cari_kd) > 0) {
                                                $tm_cari=mysqli_fetch_array($cari_kd);
                                                $saldo_akhir = $tm_cari['saldo'] ?? 0;
                                            } else {
                                                // Fallback: hitung langsung dari tabel tbstok jika VIEW error
                                                $cari_fallback = mysqli_query($koneksi,"SELECT SUM(masuk - keluar) as saldo
                                                                                        FROM tbstok
                                                                                        WHERE kd_cabang='$kd_cabang'
                                                                                        AND no_item='$noitem'");
                                                if($cari_fallback && mysqli_num_rows($cari_fallback) > 0) {
                                                    $tm_fallback = mysqli_fetch_array($cari_fallback);
                                                    $saldo_akhir = $tm_fallback['saldo'] ?? 0;
                                                } else {
                                                    $saldo_akhir = 0; // Default jika semua query gagal
                                                }
                                            }	

                                            // Ensure saldo_akhir is numeric
                                            $saldo_akhir = $saldo_akhir ?? 0;
                                            $saldo_akhir = is_numeric($saldo_akhir) ? $saldo_akhir : 0;

                                            $blocked_reason = "";
                                            // Block by applicability (STRICT) if motor type context exists
                                            $is_applicable = isset($tampil['applicable']) ? intval($tampil['applicable']) : 1;
                                            if($saldo_akhir <= 0) {
                                                // Stok habis - BLOCK
                                                $row_class="danger";
                                                $stock_badge="danger";
                                                $disabled="disabled";
                                                $blocked_reason = "STOK KOSONG";
                                            } elseif($saldo_akhir <= $stokmin) {
                                                 // Stok menipis - WARNING
                                                $row_class="warning";
                                                $stock_badge="warning";
                                                $disabled="";
                                                $blocked_reason = "";
                                            } else {
                                                // Normal
                                                $row_class="";
                                                $stock_badge="success";
                                                $disabled="";
                                                $blocked_reason = "";
                                            }
                                            if ($kd_kategori_motor > 0 && $is_applicable !== 1) {
                                                $disabled = "disabled";
                                                $blocked_reason = "TIDAK APPLICABLE";
                                            }
                                    ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td class="center">
                                                <?php if($disabled == "disabled") { ?>
                                                    <button class="btn btn-minier btn-danger" disabled title="<?php echo $blocked_reason ?: 'Tidak dapat dipilih'; ?>">
                                                        <i class="ace-icon fa fa-ban"></i>
                                                    </button>
                                                <?php } else { ?>
                                                    <?php
                                                    // Determine target page based on _from parameter
                                                    $target_page = 'servis-input-reguler.php'; // default
                                                    switch($from_page) {
                                                        case 'rst':
                                                            $target_page = 'servis-input-reguler-rst.php';
                                                            break;
                                                        case 'jemput':
                                                            $target_page = 'servis-input-reguler-jemput.php';
                                                            break;
                                                        case 'jemput-rst':
                                                            $target_page = 'servis-input-reguler-jemput-rst.php';
                                                            break;
                                                        default:
                                                            $target_page = 'servis-input-reguler.php';
                                                    }
                                                    ?>
                                                    <a href="<?php echo $target_page; ?>?snoserv=<?php echo $no_service; ?>&kd=<?php echo $tampil['noitem']; ?>&tab=<?php echo $tab_param; ?>" 
                                                       class="btn btn-minier btn-success" title="Pilih Item">
                                                        <i class="ace-icon fa fa-check"></i>
                                                    </a>
                                                <?php } ?>                                                                                                   
                                            </td>														

                                            <td>
                                                <strong><?php echo $tampil['noitem']; ?></strong>
                                            </td>														

                                            <td>
                                                <span class="text-muted"><?php echo $tampil['kodebarcode']; ?></span>
                                            </td>	

                                            <td>
                                                <span class="text-primary"><?php echo $tampil['namaitem']; ?></span>
                                                <?php if ($kd_kategori_motor > 0 && $is_applicable !== 1) { ?>
                                                    <br><span class="label label-danger" style="font-size:10px;">Not applicable</span>
                                                <?php } ?>
                                            </td>                                            
                                            <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <span class="label label-sm label-info" title="<?php echo $tampil['namajenis']; ?>"><?php echo $tampil['namajenis']; ?></span>
                                            </td>																											                                            
                                            <td class="center">
                                                <span class="badge badge-<?php echo $stock_badge; ?>"><?php echo $saldo_akhir; ?></span>
                                            </td>                                            
                                            <td class="center">
                                                <span class="text-muted"><?php echo $tampil['stokmin']; ?></span>
                                            </td>
                                            <td><?php echo $tampil['satuan']; ?></td>
                                            <td>
                                                <span class="label label-sm label-grey"><?php echo $tampil['rakbarang']; ?></span>
                                            </td>
                                            <td class="center">
                                                <span class="text-danger">Rp <?php echo number_format($tampil['hargapokok'],0); ?></span>
                                            </td>
                                            <td class="center">
                                                <strong class="text-success">Rp <?php echo number_format($tampil['hargajual'],0); ?></strong>
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
			
				
				
				$.fn.dataTable.Buttons.defaults.dom.container.className = 'dt-buttons btn-overlap btn-group btn-overlap';
				
				new $.fn.dataTable.Buttons( myTable, {
					buttons: [
					  {
						"extend": "colvis",
						"text": "<i class='fa fa-search bigger-110 blue'></i> <span class='hidden'>Show/hide columns</span>",
						"className": "btn btn-white btn-primary btn-bold",
						columns: ':not(:first):not(:last)'
					  },
					  {
						"extend": "copy",
						"text": "<i class='fa fa-copy bigger-110 pink'></i> <span class='hidden'>Copy to clipboard</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "csv",
						"text": "<i class='fa fa-database bigger-110 orange'></i> <span class='hidden'>Export to CSV</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "excel",
						"text": "<i class='fa fa-file-excel-o bigger-110 green'></i> <span class='hidden'>Export to Excel</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "pdf",
						"text": "<i class='fa fa-file-pdf-o bigger-110 red'></i> <span class='hidden'>Export to PDF</span>",
						"className": "btn btn-white btn-primary btn-bold"
					  },
					  {
						"extend": "print",
						"text": "<i class='fa fa-print bigger-110 grey'></i> <span class='hidden'>Print</span>",
						"className": "btn btn-white btn-primary btn-bold",
						autoPrint: false,
						message: 'This print was produced using the Print button for DataTables'
					  }		  
					]
				} );
				myTable.buttons().container().appendTo( $('.tableTools-container') );
				
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
		</script>
	</body>
</html>

<?php 
?>