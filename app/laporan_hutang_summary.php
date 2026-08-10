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

        // Date filter
        include_once "includes/report-default-range.php";
        $_default_range = app_report_default_range($koneksi, 'tblpembelian_header', 'tanggal', "carabayar='Kredit'");
        $tgl_dari = isset($_POST['tgl_dari']) ? $_POST['tgl_dari'] : $_default_range['from_dmy'];
        $tgl_sampai = isset($_POST['tgl_sampai']) ? $_POST['tgl_sampai'] : $_default_range['to_dmy'];
        
        function ubahformatTgl($tanggal) {
            $pisah = explode('/',$tanggal);
            $urutan = array($pisah[2],$pisah[1],$pisah[0]);
            $satukan = implode('-',$urutan);
            return $satukan;
        }
        
        if(isset($_POST['btngenerate'])) {
            $tgl_dari = $_POST['tgl_dari'];
            $tgl_sampai = $_POST['tgl_sampai'];
        }
        
        $tgl_dari_sql = ubahformatTgl($tgl_dari);
        $tgl_sampai_sql = ubahformatTgl($tgl_sampai);
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Laporan Hutang Summary | <?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Laporan Hutang Summary per Supplier" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<!-- page specific plugin styles -->
		<link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

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
	</head>

	<body class="no-skin">
		<div id="navbar" class="navbar navbar-default          ace-save-state">
			<div class="navbar-container ace-save-state" id="navbar-container">
				<?php include "_include_navbar.php"; ?>
			</div><!-- /.navbar-container -->
		</div>
		
		<div class="main-container ace-save-state" id="main-container">
			<script type="text/javascript">
				try{ace.settings.loadState('main-container')}catch(e){}
			</script>

			<div id="sidebar" class="sidebar responsive ace-save-state">
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
								<a href="#">Laporan</a>
							</li>                            
							<li class="active">Laporan Hutang Summary</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">
                        
                        <!-- Filter Section -->
                        <div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-blue widget-header-flat">
										<h4 class="widget-title lighter">
											<i class="ace-icon fa fa-filter"></i>
											Filter Periode
										</h4>
									</div>

									<div class="widget-body">
										<div class="widget-main">	
                                            <form class="form-horizontal" action="" method="post" role="form">
                                                <div class="row">
                                                    <div class="col-xs-12 col-sm-4">
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">Dari Tanggal:</label>
                                                            <div class="col-sm-8">
                                                                <div class="input-group">
                                                                    <input class="form-control date-picker" id="tgl-dari" name="tgl_dari" type="text" 
                                                                    value="<?php echo $tgl_dari; ?>" data-date-format="dd/mm/yyyy" />
                                                                    <span class="input-group-addon">
                                                                        <i class="fa fa-calendar bigger-110"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 col-sm-4">
                                                        <div class="form-group">
                                                            <label class="col-sm-4 control-label">Sampai Tanggal:</label>
                                                            <div class="col-sm-8">
                                                                <div class="input-group">
                                                                    <input class="form-control date-picker" id="tgl-sampai" name="tgl_sampai" type="text" 
                                                                    value="<?php echo $tgl_sampai; ?>" data-date-format="dd/mm/yyyy" />
                                                                    <span class="input-group-addon">
                                                                        <i class="fa fa-calendar bigger-110"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 col-sm-4">
                                                        <button class="btn btn-info btn-block" type="submit" name="btngenerate">
                                                            <i class="ace-icon fa fa-refresh"></i>
                                                            Generate Laporan
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
									</div>
								</div>	
							</div>
						</div>

                        <div class="space-12"></div>

                        <!-- Report Section -->
                        <div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-green widget-header-flat">
										<h4 class="widget-title lighter">
											<i class="ace-icon fa fa-bar-chart"></i>
											LAPORAN HUTANG SUMMARY PER SUPPLIER
										</h4>
                                        <div class="widget-toolbar">
                                            <a href="#" onclick="window.print();" class="btn btn-xs btn-warning">
                                                <i class="ace-icon fa fa-print"></i> Print
                                            </a>
                                            <a href="laporan_hutang_detail.php" class="btn btn-xs btn-primary">
                                                <i class="ace-icon fa fa-list"></i> View Detail
                                            </a>
                                        </div>
									</div>

									<div class="widget-body">
										<div class="widget-main no-padding">
                                            
                                            <div style="padding: 15px;">
                                                <h4 class="text-center">
                                                    <strong>*UPDATE HUTANG SUPLIER*</strong><br>
                                                    <small>JJH TEMPO s.d : <?php echo date('d-M-y', strtotime($tgl_sampai_sql)); ?></small><br>
                                                    <small><?php echo strtoupper($nama_cabang); ?></small>
                                                </h4>
                                            </div>

                                            <?php
                                            // Query untuk get summary hutang per supplier
                                            $sql_summary = "SELECT 
                                                            ph.no_supplier,
                                                            MAX(s.namasupplier) as namasupplier,
                                                            MAX(s.alamat) as alamatsupplier,
                                                            MAX(s.telephone) as tlpsupplier,
                                                            COUNT(ph.notransaksi) as jumlah_faktur,
                                                            SUM(ph.total_akhir - ph.jumlah_bayar) as total_hutang
                                                        FROM tblpembelian_header ph
                                                        JOIN tblsupplier s ON ph.no_supplier = s.nosupplier
                                                        WHERE ph.carabayar = 'Kredit'
                                                            AND ph.status_lunas = '0'
                                                            AND ph.kd_cabang = '$kd_cabang'
                                                            AND ph.tanggal BETWEEN '$tgl_dari_sql' AND '$tgl_sampai_sql'
                                                        GROUP BY ph.no_supplier
                                                        ORDER BY total_hutang DESC";
                                            
                                            $result_summary = mysqli_query($koneksi, $sql_summary);
                                            $grand_total = 0;
                                            $total_suppliers = 0;
                                            ?>
                                            
                                            <div style="padding: 20px;">
                                                <?php
                                                if(mysqli_num_rows($result_summary) > 0) {
                                                    while($summary = mysqli_fetch_array($result_summary)) {
                                                        $no_supplier = $summary['no_supplier'];
                                                        $nama_supplier = $summary['namasupplier'];
                                                        $total_hutang = $summary['total_hutang'];
                                                        $jumlah_faktur = $summary['jumlah_faktur'];
                                                        
                                                        $grand_total += $total_hutang;
                                                        $total_suppliers++;
                                                ?>
                                                
                                                <div style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #5bc0de;">
                                                    <h5 style="margin-top: 0;">
                                                        <strong>** <?php echo strtoupper($nama_supplier); ?>*</strong>
                                                    </h5>
                                                    <p style="margin: 5px 0; color: #666;">
                                                        <i class="fa fa-building-o"></i> <?php echo $summary['alamatsupplier']; ?><br>
                                                        <i class="fa fa-phone"></i> <?php echo $summary['tlpsupplier']; ?>
                                                    </p>
                                                    <div style="margin-top: 10px;">
                                                        <span class="label label-info" style="font-size: 12px; margin-right: 10px;">
                                                            <i class="fa fa-file-text"></i> <?php echo $jumlah_faktur; ?> Faktur
                                                        </span>
                                                        <span class="label label-danger" style="font-size: 13px;">
                                                            <i class="fa fa-money"></i> Total: Rp <?php echo number_format($total_hutang, 0); ?>
                                                        </span>
                                                    </div>
                                                    <div style="margin-top: 10px;">
                                                        <a href="laporan_hutang_detail.php" class="btn btn-xs btn-info">
                                                            <i class="fa fa-search"></i> Rincian
                                                        </a>
                                                    </div>
                                                </div>
                                                
                                                <?php
                                                    }
                                                ?>
                                                
                                                <!-- Grand Total Section -->
                                                <div style="margin-top: 30px; padding: 20px; background-color: #d4edda; border: 2px solid #28a745; border-radius: 5px;">
                                                    <div class="row">
                                                        <div class="col-sm-8">
                                                            <h4 style="margin: 0;">
                                                                <strong>*TAGIHAN:</strong>
                                                            </h4>
                                                            <p style="margin: 5px 0; color: #666;">
                                                                Total Supplier: <?php echo $total_suppliers; ?> supplier
                                                            </p>
                                                        </div>
                                                        <div class="col-sm-4 text-right">
                                                            <h3 style="margin: 0; color: #28a745;">
                                                                <strong>Rp <?php echo number_format($grand_total, 0); ?>*</strong>
                                                            </h3>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php
                                                } else {
                                                ?>
                                                
                                                <div class="alert alert-info text-center">
                                                    <i class="ace-icon fa fa-info-circle fa-2x"></i><br>
                                                    <strong>Tidak ada data hutang untuk periode yang dipilih.</strong>
                                                </div>
                                                
                                                <?php
                                                }
                                                ?>
                                            </div>

                                        </div>
									</div>
								</div>
							</div>
						</div>

                        <!-- Quick Stats -->
                        <?php if(mysqli_num_rows($result_summary) > 0) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-4">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-flat widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-users"></i>
                                            Total Supplier
                                        </h5>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <h2 class="text-center text-primary">
                                                <?php echo $total_suppliers; ?>
                                            </h2>
                                            <p class="text-center text-muted">supplier dengan hutang aktif</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-4">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-flat widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-money"></i>
                                            Total Hutang
                                        </h5>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <h2 class="text-center text-danger">
                                                Rp <?php echo number_format($grand_total, 0); ?>
                                            </h2>
                                            <p class="text-center text-muted">total hutang belum lunas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-4">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-flat widget-header-small">
                                        <h5 class="widget-title">
                                            <i class="ace-icon fa fa-calculator"></i>
                                            Rata-rata per Supplier
                                        </h5>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <h2 class="text-center text-warning">
                                                Rp <?php echo number_format($grand_total / $total_suppliers, 0); ?>
                                            </h2>
                                            <p class="text-center text-muted">rata-rata hutang per supplier</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>

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
		<script src="assets/js/bootstrap-datepicker.min.js"></script>

		<!-- ace scripts -->
		<script src="assets/js/ace-elements.min.js"></script>
		<script src="assets/js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
		<script type="text/javascript">
			jQuery(function($) {
				$('.date-picker').datepicker({
					autoclose: true,
					todayHighlight: true
				})
				.next().on(ace.click_event, function(){
					$(this).prev().focus();
				});
			});
		</script>

	</body>
</html>

<?php
	}
?>
