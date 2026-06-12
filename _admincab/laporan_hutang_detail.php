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
        $tgl_dari = isset($_POST['tgl_dari']) ? $_POST['tgl_dari'] : date('01/m/Y');
        $tgl_sampai = isset($_POST['tgl_sampai']) ? $_POST['tgl_sampai'] : date('d/m/Y');
        
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
		<title>Laporan Hutang Detail | <?php include "../lib/titel.php"; ?></title>

		<meta name="description" content="Laporan Hutang Detail per Faktur per Supplier" />
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

		<!-- inline styles related to this page -->

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
				<?php include "menu_nav.php"; ?>
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

                <?php include "menu_pembelian03.php"; ?>

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
							<li class="active">Laporan Hutang Detail</li>
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
											<i class="ace-icon fa fa-list-alt"></i>
											LAPORAN HUTANG DETAIL PER SUPPLIER
										</h4>
                                        <div class="widget-toolbar">
                                            <a href="#" onclick="window.print();" class="btn btn-xs btn-warning">
                                                <i class="ace-icon fa fa-print"></i> Print
                                            </a>
                                            <a href="laporan_hutang_summary.php" class="btn btn-xs btn-info">
                                                <i class="ace-icon fa fa-bar-chart"></i> View Summary
                                            </a>
                                        </div>
									</div>

									<div class="widget-body">
										<div class="widget-main no-padding">
                                            
                                            <div style="padding: 15px;">
                                                <h4 class="text-center">
                                                    <strong>LAPORAN HUTANG DETAIL PER SUPPLIER</strong><br>
                                                    <small>Periode: <?php echo $tgl_dari; ?> s/d <?php echo $tgl_sampai; ?></small><br>
                                                    <small><?php echo strtoupper($nama_cabang); ?></small>
                                                </h4>
                                            </div>

                                            <?php
                                            // Query untuk get list supplier yang punya hutang
                                            $sql_supplier = "SELECT DISTINCT 
                                                            ph.no_supplier,
                                                            s.namasupplier,
                                                            s.alamatsupplier,
                                                            s.tlpsupplier
                                                        FROM tblpembelian_header ph
                                                        JOIN tblsupplier s ON ph.no_supplier = s.nosupplier
                                                        WHERE ph.carabayar = 'Kredit'
                                                            AND ph.status_lunas = '0'
                                                            AND ph.kd_cabang = '$kd_cabang'
                                                            AND ph.tanggal BETWEEN '$tgl_dari_sql' AND '$tgl_sampai_sql'
                                                        ORDER BY s.namasupplier ASC";
                                            
                                            $result_supplier = mysqli_query($koneksi, $sql_supplier);
                                            $grand_total = 0;
                                            
                                            while($supplier = mysqli_fetch_array($result_supplier)) {
                                                $no_supplier = $supplier['no_supplier'];
                                                $nama_supplier = $supplier['namasupplier'];
                                                
                                                // Query detail hutang per supplier
                                                $sql_detail = "SELECT 
                                                                ph.notransaksi,
                                                                DATE_FORMAT(ph.tanggal,'%d/%m/%Y') as tgl_jt,
                                                                ph.no_faktur,
                                                                DATE_FORMAT(ph.tanggal_faktur,'%d/%m/%Y') as tgl_faktur,
                                                                ph.total_akhir,
                                                                ph.pembayaran,
                                                                ph.jumlah_bayar,
                                                                DATE_FORMAT(ph.tanggal_lunas,'%d/%m/%Y') as tgl_terima,
                                                                (ph.total_akhir - ph.jumlah_bayar) as sisa_hutang
                                                            FROM tblpembelian_header ph
                                                            WHERE ph.no_supplier = '$no_supplier'
                                                                AND ph.carabayar = 'Kredit'
                                                                AND ph.status_lunas = '0'
                                                                AND ph.kd_cabang = '$kd_cabang'
                                                                AND ph.tanggal BETWEEN '$tgl_dari_sql' AND '$tgl_sampai_sql'
                                                            ORDER BY ph.tanggal_faktur, ph.tanggal ASC";
                                                
                                                $result_detail = mysqli_query($koneksi, $sql_detail);
                                                $total_supplier = 0;
                                            ?>
                                            
                                            <div style="padding: 15px; page-break-inside: avoid;">
                                                <h5 style="background-color: #f0f0f0; padding: 10px; margin-bottom: 10px;">
                                                    <strong>** <?php echo strtoupper($nama_supplier); ?> **</strong><br>
                                                    <small><?php echo $supplier['alamatsupplier']; ?> | Telp: <?php echo $supplier['tlpsupplier']; ?></small>
                                                </h5>
                                                
                                                <table class="table table-bordered table-condensed">
                                                    <thead>
                                                        <tr style="background-color: #e0e0e0;">
                                                            <th width="12%">No. Transaksi</th>
                                                            <th width="10%">Tgl JT</th>
                                                            <th width="12%">No. Faktur</th>
                                                            <th width="10%">Tgl Faktur</th>
                                                            <th width="12%" class="text-right">Total</th>
                                                            <th width="12%" class="text-right">Sudah Bayar</th>
                                                            <th width="12%" class="text-right">Sisa Hutang</th>
                                                            <th width="10%">Tgl Terima</th>
                                                            <th width="10%">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    if(mysqli_num_rows($result_detail) > 0) {
                                                        while($detail = mysqli_fetch_array($result_detail)) {
                                                            $sisa = $detail['sisa_hutang'];
                                                            $total_supplier += $sisa;
                                                            
                                                            // Status badge
                                                            if($sisa > 0) {
                                                                $status_badge = '<span class="label label-danger">BELUM LUNAS</span>';
                                                            } else {
                                                                $status_badge = '<span class="label label-success">LUNAS</span>';
                                                            }
                                                    ?>
                                                        <tr>
                                                            <td><?php echo $detail['notransaksi']; ?></td>
                                                            <td><?php echo $detail['tgl_jt']; ?></td>
                                                            <td><strong><?php echo $detail['no_faktur'] ? $detail['no_faktur'] : '-'; ?></strong></td>
                                                            <td><?php echo $detail['tgl_faktur'] ? $detail['tgl_faktur'] : '-'; ?></td>
                                                            <td class="text-right"><?php echo number_format($detail['total_akhir'],0); ?></td>
                                                            <td class="text-right"><?php echo number_format($detail['jumlah_bayar'],0); ?></td>
                                                            <td class="text-right"><strong><?php echo number_format($sisa,0); ?></strong></td>
                                                            <td><?php echo $detail['tgl_terima'] ? $detail['tgl_terima'] : '-'; ?></td>
                                                            <td><?php echo $status_badge; ?></td>
                                                        </tr>
                                                    <?php
                                                        }
                                                    } else {
                                                    ?>
                                                        <tr>
                                                            <td colspan="9" class="text-center text-muted">
                                                                <em>Tidak ada hutang untuk supplier ini</em>
                                                            </td>
                                                        </tr>
                                                    <?php
                                                    }
                                                    ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr style="background-color: #fff3cd;">
                                                            <td colspan="6" class="text-right"><strong>TOTAL <?php echo strtoupper($nama_supplier); ?>:</strong></td>
                                                            <td class="text-right"><strong style="font-size: 14px;">Rp <?php echo number_format($total_supplier,0); ?></strong></td>
                                                            <td colspan="2"></td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                            
                                            <?php
                                                $grand_total += $total_supplier;
                                            }
                                            ?>
                                            
                                            <!-- Grand Total -->
                                            <div style="padding: 15px; background-color: #d4edda; margin: 15px;">
                                                <h4 class="text-right">
                                                    <strong>GRAND TOTAL HUTANG: Rp <?php echo number_format($grand_total,0); ?></strong>
                                                </h4>
                                            </div>

                                            <?php if(mysqli_num_rows($result_supplier) == 0) { ?>
                                            <div class="alert alert-info text-center" style="margin: 20px;">
                                                <i class="ace-icon fa fa-info-circle"></i>
                                                Tidak ada data hutang untuk periode yang dipilih.
                                            </div>
                                            <?php } ?>

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
