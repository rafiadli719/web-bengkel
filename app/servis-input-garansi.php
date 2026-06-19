<?php
	session_start();
	
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";
        include_once "../lib/rbac.php";
        rbac_require_any(array('input_servis_garansi_read','servis_garansi_read','servis_menu_read','service_create','service_update'));
        include "_include_customer_vehicle_sync.php";
        include "_include_statistik_pelanggan.php";
        
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
        $txtcaribrg=$_GET['kd'] ?? '';
        $txtcarisrv=$_GET['kdjasa'] ?? '';
        $txtcariwo=$_GET['kdwo'] ?? '';
        
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
                
    // Get customer data with category info
    if(!empty($kode_pelanggan)) {
        $customerBundle = fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $no_polisi);
        $namapelanggan=$customerBundle['namapelanggan'] ?? '';
        $potongan_pelanggan = $customerBundle['potongan'] ?? 0;
        $tipe_potongan = $customerBundle['tipepot'] ?? '';
        $kategori_pelanggan = $customerBundle['kgrup'] ?? '001';
    } else {
        $namapelanggan = '';
        $potongan_pelanggan = 0;
        $tipe_potongan = '';
        $kategori_pelanggan = '001';
    }

    // Get vehicle data
    if(!empty($no_polisi)) {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $customerRow = $bundle['customer'] ?? [];
        $pemilik=$vehicleRow['pemilik'] ?? '';
        $jenis=$vehicleRow['jenis'] ?? '';
        $merek=$vehicleRow['tipe'] ?? '';
        $warna=$vehicleRow['warna'] ?? '';
        $no_rangka=$vehicleRow['no_rangka'] ?? '';
        $no_mesin=$vehicleRow['no_mesin'] ?? '';
        if (empty($namapelanggan) && !empty($customerRow['namapelanggan'])) {
            $namapelanggan = $customerRow['namapelanggan'];
            $potongan_pelanggan = $customerRow['potongan'] ?? $potongan_pelanggan;
            $tipe_potongan = $customerRow['tipepot'] ?? $tipe_potongan;
            $kategori_pelanggan = $customerRow['kgrup'] ?? $kategori_pelanggan;
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

        // Function to determine discount based on customer category
        function getDiscountByCategory($kategori, $potongan_existing = 0, $tipe_pot = '') {
            $discount_percent = 0;
            
            // Kategori member dengan diskon otomatis
            switch($kategori) {
                case 'GOLD':
                case 'gold':
                case 'G':
                    $discount_percent = 15; // Member Gold 15%
                    break;
                case 'SILVER':
                case 'silver':
                case 'S':
                    $discount_percent = 10; // Member Silver 10%
                    break;
                case 'BRONZE':
                case 'bronze':
                case 'B':
                    $discount_percent = 5; // Member Bronze 5%
                    break;
                default:
                    // Gunakan potongan yang sudah ada di database pelanggan
                    if($potongan_existing > 0) {
                        if($tipe_pot == '%' || $tipe_pot == 'P') {
                            $discount_percent = $potongan_existing;
                        }
                    }
                    break;
            }
            
            return $discount_percent;
        }
        
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
        $txtcaribrg = $_GET['kd'] ?? '';
        $txtcarisrv = $_GET['kdjasa'] ?? '';
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
        
        // Calculate total values for garansi
        $total_barang = 0;
        $total_service = 0;
        $total_waktu = 0;
        
        if(!empty($no_service)) {
            // Total barang
            $sql_barang = mysqli_query($koneksi,"SELECT COALESCE(SUM(total), 0) as total_barang FROM tblservis_barang WHERE no_service='$no_service'");
            $data_barang = mysqli_fetch_array($sql_barang);
            $total_barang = $data_barang['total_barang'] ?? 0;
            
            // Total service with waktu
            $sql_service = mysqli_query($koneksi,"SELECT COALESCE(SUM(total), 0) as total_service, COALESCE(SUM(waktu), 0) as total_waktu FROM tblservis_jasa WHERE no_service='$no_service'");
            $data_service = mysqli_fetch_array($sql_service);
            $total_service = $data_service['total_service'] ?? 0;
            $total_waktu = $data_service['total_waktu'] ?? 0;
        }
        
        // Calculate totals and apply automatic discount
        $tot = $total_service + $total_barang;
        
        // Get automatic discount based on customer category
        $auto_discount_percent = getDiscountByCategory($kategori_pelanggan, $potongan_pelanggan, $tipe_potongan);
        
        // Apply discount (for garansi, usually no discount but we keep the system consistent)
        $discount_amount = $tot * ($auto_discount_percent / 100);
        $net = $tot - $discount_amount;
        $bayar = $net;
        $kembalian = $bayar - $net;
        
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
    
    // Initialize other variables
    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';
    $prioritas_wo = 'urgent'; // Garansi biasanya urgent
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

    // Payment Processing for Garansi
    if(isset($_POST['btnbayar'])) {	
        $no_service= $_POST['txtnosrv'] ?? $no_service;			
        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        $diskon_member = $_POST['txtdiskon_member'] ?? 0;
        $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'] ?? 0;  
        $total_diskon_persen = $diskon_member + $txtpotfaktur_persen;
        $txtpotfaktur_nom= str_replace(['.', ','], '', $_POST['txtpotfaktur_nom'] ?? '0'); // Remove formatting   
        $txtpajak_persen= $_POST['txtpajak_persen'] ?? 0;   
        $metode_pembayaran= $_POST['metode_pembayaran'] ?? 'Tunai';   
        $txtbayar= str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0'); // Remove formatting
        
        // Get mechanic data
        $kepala_mekanik1 = $_POST['cbokepala_mekanik1'] ?? '';
        $persen_kepala1 = $_POST['txtpersen_kepala1'] ?? 0;
        $kepala_mekanik2 = $_POST['cbokepala_mekanik2'] ?? '';
        $persen_kepala2 = $_POST['txtpersen_kepala2'] ?? 0;
        $mekanik1 = $_POST['cbomekanik1'] ?? '';
        $persen_mekanik1 = $_POST['txtpersen_mekanik1'] ?? 0;
        $mekanik2 = $_POST['cbomekanik2'] ?? '';
        $persen_mekanik2 = $_POST['txtpersen_mekanik2'] ?? 0;
        $mekanik3 = $_POST['cbomekanik3'] ?? '';
        $persen_mekanik3 = $_POST['txtpersen_mekanik3'] ?? 0;
        $mekanik4 = $_POST['cbomekanik4'] ?? '';
        $persen_mekanik4 = $_POST['txtpersen_mekanik4'] ?? 0;   
        
    // == Total dari Item & Waktu Service ==============
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot, 
                                        sum(waktu) as tot_waktu 
                                        FROM tblservis_jasa 
                                        WHERE 
                                        no_service='$no_service'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $total_service_pay=$tm_cari['tot']; 
        $total_waktu_pay=$tm_cari['tot_waktu']; 

    // == Total dari Item Barang ==============
    $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                        FROM tblservis_barang 
                                        WHERE 
                                        no_service='$no_service'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $total_barang_pay=$tm_cari['tot']; 

        $tot_pay=$total_service_pay+$total_barang_pay;
        $diskon_nominal = $tot_pay * ($total_diskon_persen / 100);
        $ppn=$tot_pay*($txtpajak_persen/100);
        $net_pay=$tot_pay-$diskon_nominal+$ppn;
        $kembalian_pay=$txtbayar-$net_pay;
        
        // Validate payment amount
        if($txtbayar < $net_pay) {
            echo"<script>window.alert('Jumlah pembayaran tidak mencukupi! Total: Rp " . number_format($net_pay, 0, ',', '.') . ", Bayar: Rp " . number_format($txtbayar, 0, ',', '.') . "');
            window.history.back();</script>";
            exit;
        }
        
        if($net_pay <= 0) {
            echo"<script>window.alert('Total service harus lebih dari 0!');
            window.history.back();</script>";
            exit;
        }           

        mysqli_query($koneksi,"UPDATE 
                                tblservice 
                                SET status='2', 
                                total='$tot_pay', 
                                diskon_persen='$total_diskon_persen', diskon_nom='$diskon_nominal', 
                                ppn_persen='$txtpajak_persen', ppn_nom='$ppn', 
                                total_grand='$net_pay',
                                total_akhir='$net_pay',
                                total_waktu='$total_waktu_pay',
                                km_skr='$km_skr',
                                km_berikut='$km_berikut',
                                status_servis='bayar',
                                kepala_mekanik1='$kepala_mekanik1',
                                kepala_mekanik2='$kepala_mekanik2',
                                persen_kepala_mekanik1='$persen_kepala1',
                                persen_kepala_mekanik2='$persen_kepala2',
                                mekanik1='$mekanik1',
                                mekanik2='$mekanik2', 
                                mekanik3='$mekanik3',
                                mekanik4='$mekanik4',
                                persen_mekanik1='$persen_mekanik1',
                                persen_mekanik2='$persen_mekanik2',
                                persen_mekanik3='$persen_mekanik3',
                                persen_mekanik4='$persen_mekanik4',
                                metode_pembayaran='$metode_pembayaran',
                                bayar='$txtbayar',
                                kembali='$kembalian_pay'
                                WHERE 
                                no_service='$no_service'");
                                
        // Update stock for items used in service
        $sql = mysqli_query($koneksi,"SELECT * FROM tblservis_barang 
                                        WHERE 
                                        no_service='$no_service'");
        while ($tampil = mysqli_fetch_array($sql)) {
            $no_item=$tampil['no_item'];
            $qty=$tampil['quantity'];
            mysqli_query($koneksi,"INSERT INTO tbstok 
                                (tipe, no_transaksi, no_item, 
                                tanggal, masuk, keluar, keterangan, 
                                kd_cabang) 
                                VALUES 
                                ('4','$no_service','$no_item',
                                '$tanggal_srv','0','$qty',
                                'Penjualan Service Garansi','$kd_cabang')"); 
        }
        
        // ========== KIRIM WHATSAPP OTOMATIS ==========
        try {
            if(file_exists('config_whatsapp.php')) {
                require_once 'config_whatsapp.php';
            }
            
            if(file_exists('class_whatsapp_automation.php')) {
                require_once 'class_whatsapp_automation.php';
            }
            
            if(defined('WA_API_ENABLED') && WA_API_ENABLED && 
               defined('WA_AUTO_SEND_AFTER_PAYMENT') && WA_AUTO_SEND_AFTER_PAYMENT) {
                
                if(class_exists('WhatsAppAutomation')) {
                    if(defined('WA_SEND_DELAY')) sleep(WA_SEND_DELAY);
                    
                    $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
                    $wa_result = $wa->sendTerimaKasih($no_service);
                    
                    if(function_exists('logWhatsAppActivity')) {
                        if(isset($wa_result['success']) && $wa_result['success']) {
                            $phone = isset($wa_result['phone']) ? $wa_result['phone'] : '';
                            logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (garansi)');
                        } else {
                            $msg = isset($wa_result['message']) ? $wa_result['message'] : 'Unknown error';
                            logWhatsAppActivity($no_service, '', 'failed', $msg);
                        }
                    }
                }
            }
        } catch(Exception $e) {
            if(function_exists('logWhatsAppActivity')) {
                logWhatsAppActivity($no_service, '', 'error', 'Exception: ' . $e->getMessage());
            }
        }
        // ========== END KIRIM WHATSAPP ==========
        
    echo"<script>
        if(confirm('Pembayaran Service Garansi Berhasil!\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "\\n\\nService garansi telah selesai.\\n\\nKlik OK untuk print invoice\\nKlik Cancel untuk kembali ke daftar servis')) {
            window.location='servis-print.php?snoserv=$no_service';
        } else {
            window.location='servis-reguler.php';
        }
    </script>";
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
							<li class="active">Input Service Garansi</li>
						</ul><!-- /.breadcrumb -->
					</div>

					<div class="page-content">

                        <form class="form-horizontal" action="" method="post" role="form">
						<div class="row">
							<div class="col-xs-12">
								<div class="widget-box">
									<div class="widget-header widget-header-blue widget-header-flat">
										<h4 class="widget-title lighter">
											<i class="ace-icon fa fa-shield orange"></i>
											Input Service Garansi <?php echo $no_service ? '#'.$no_service : ''; ?>
										</h4>
                                        <div class="widget-toolbar">
                                            <span class="label label-warning arrowed-in arrowed-in-right">Warranty Service</span>
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
																				<label class="col-sm-3 control-label no-padding-right"> Kode Pelanggan :</label>													
																				<div class="col-sm-9">
																					<input type="text" class="form-control" name="kode_pelanggan" id="kode_pelanggan" 
																					value="<?php echo $kode_pelanggan; ?>" placeholder="Kode pelanggan..." />
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
															<button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#modalStatistikPelanggan" style="text-align: left; position: relative; padding: 12px 15px;">
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
																				<label class="col-sm-4 control-label no-padding-right"> Pemilik :</label>													
																				<div class="col-sm-8">
																					<input type="text" class="form-control" name="pemilik" id="pemilik" 
																					value="<?php echo $pemilik; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Jenis :</label>													
																				<div class="col-sm-8">
																					<input type="text" class="form-control" name="jenis" id="jenis" 
																					value="<?php echo $jenis; ?>" readonly />
																				</div>
																			</div>
																			<div class="form-group">
																				<label class="col-sm-4 control-label no-padding-right"> Keluhan Garansi :</label>
																				<div class="col-sm-8">
																					<textarea class="form-control" id="keluhan" name="keluhan" rows="3" 
																					placeholder="Keluhan garansi pelanggan..."><?php echo $keluhan; ?></textarea>
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
														<?php include "_template/_servis_add_header_kanan_combined.php"; ?>
													</div>

													<div id="service-items" class="tab-pane fade">
														<div class="row">
															<div class="col-xs-12">
																<div class="padding-18">
																	<h4 class="orange">
																		<i class="ace-icon fa fa-cubes"></i>
																		Item Barang - Service Garansi
																	</h4>
																	<?php include "_template/_servis_garansi_detail_barang.php"; ?>
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
																	<?php include "_template/_servis_garansi_detail_servis.php"; ?>
																</div>
															</div>
														</div>
													</div>

													<div id="service-actions" class="tab-pane fade">
														<?php include "_template/_servis_actions_tab_garansi.php"; ?>
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


					</div><!-- /.page-content -->
				</div>
			</div><!-- /.main-content -->

			<div class="footer">
				<div class="footer-inner">
					<div class="footer-content">
                        <?php include "_template/_servis_footer_with_tabs_garansi.php"; ?>
					</div>
				</div>
			</div>

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->

		<!-- basic scripts -->

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

		<!-- Payment Calculation JavaScript for Garansi -->
		<script type="text/javascript">
		    $(document).ready(function() {
		        // Update totals when page loads
		        updatePaymentTotals();
		        
		        // Format currency inputs
		        function formatNumber(num) {
		            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
		        }
		        
		        function parseNumber(str) {
		            return parseInt(str.replace(/\./g, '')) || 0;
		        }
		        
		        // Calculate payment totals
		        function updatePaymentTotals() {
		            // Get current totals from service and items
		            updateServiceTotals();
		            
		            var subtotal = parseNumber($("#txttotal").val()) || 0;
		            var diskonMember = parseFloat($("#txtdiskon_member").val()) || 0;
		            var diskonTambahan = parseFloat($("#txtpotfaktur_persen").val()) || 0;
		            var pajakPersen = parseFloat($("#txtpajak_persen").val()) || 0;
		            
		            // Calculate total discount percentage (member + additional)
		            var totalDiskonPersen = diskonMember + diskonTambahan;
		            
		            // Calculate total discount nominal
		            var diskonNom = (totalDiskonPersen / 100) * subtotal;
		            $("#txtpotfaktur_nom").val(formatNumber(diskonNom));
		            
		            // Calculate tax nominal
		            var pajakNom = (pajakPersen / 100) * subtotal;
		            $("#txtpajak_nom").val(formatNumber(pajakNom));
		            
		            // Calculate net total
		            var net = subtotal - diskonNom + pajakNom;
		            $("#txtnet").val(formatNumber(net));
		            
		            // Update payment amount to match net total if not manually changed
		            if (!$("#txtbayar").data('manually-changed')) {
		                $("#txtbayar").val(formatNumber(net));
		            }
		            
		            // Calculate change
		            calculateChange();
		        }
		        
		        // Update service totals from database/current items
		        function updateServiceTotals() {
		            // Get current totals from PHP values
		            var totalJasa = <?php echo $total_service; ?>;
		            var totalBarang = <?php echo $total_barang; ?>;
		            var currentTotal = totalJasa + totalBarang;
		            
		            $("#txttotal_jasa").val(formatNumber(totalJasa));
		            $("#txttotal_barang").val(formatNumber(totalBarang));
		            $("#txttotal").val(formatNumber(currentTotal));
		        }
		        
		        // Calculate change
		        function calculateChange() {
		            var net = parseNumber($("#txtnet").val());
		            var bayar = parseNumber($("#txtbayar").val());
		            var kembalian = bayar - net;
		            
		            $("#txtkembalian").val(formatNumber(kembalian));
		            
		            // Change color based on amount
		            if (kembalian < 0) {
		                $("#txtkembalian").css("background-color", "#ffebee");
		                $("#txtkembalian").css("color", "#d32f2f");
		            } else {
		                $("#txtkembalian").css("background-color", "#f0f8ff");
		                $("#txtkembalian").css("color", "#333");
		            }
		        }
		        
		        // Event handlers for additional discount percentage
		        $("#txtpotfaktur_persen").on('keyup change', function() {
		            updatePaymentTotals();
		        });
		        
		        // Event handlers for tax percentage
		        $("#txtpajak_persen").on('keyup change', function() {
		            updatePaymentTotals();
		        });
		        
		        // Event handlers for payment amount
		        $("#txtbayar").on('keyup change', function() {
		            $(this).data('manually-changed', true);
		            calculateChange();
		        }).on('focus', function() {
		            $(this).data('manually-changed', true);
		        });
		        
		        // Format currency on blur
		        $("#txtpotfaktur_nom, #txtbayar").on('blur', function() {
		            var value = parseNumber($(this).val());
		            $(this).val(formatNumber(value));
		        });
		        
		        // Payment method change handler
		        $("#metode_pembayaran").on('change', function() {
		            var method = $(this).val();
		            if (method === 'Tunai') {
		                $("#txtbayar").prop('readonly', false);
		            } else {
		                // For non-cash payments, set exact amount
		                var net = parseNumber($("#txtnet").val());
		                $("#txtbayar").val(formatNumber(net));
		                $("#txtbayar").prop('readonly', true);
		                calculateChange();
		            }
		        });
		        
		        // Validate payment before submit
		        $("#btnbayar").on('click', function(e) {
		            var net = parseNumber($("#txtnet").val());
		            var bayar = parseNumber($("#txtbayar").val());
		            
		            if (bayar < net) {
		                e.preventDefault();
		                alert('Jumlah pembayaran tidak mencukupi!');
		                return false;
		            }
		            
		            if (net <= 0) {
		                e.preventDefault();
		                alert('Total pembayaran harus lebih dari 0!');
		                return false;
		            }
		            
		            var kembalian = bayar - net;
		            var confirmMsg = 'Konfirmasi Pembayaran Service Garansi:\n';
		            confirmMsg += 'Total: Rp ' + formatNumber(net) + '\n';
		            confirmMsg += 'Bayar: Rp ' + formatNumber(bayar) + '\n';
		            confirmMsg += 'Kembalian: Rp ' + formatNumber(kembalian) + '\n';
		            confirmMsg += 'Metode: ' + $("#metode_pembayaran").val() + '\n\n';
		            confirmMsg += 'Service garansi telah selesai.\n\n';
		            confirmMsg += 'Lanjutkan pembayaran?';
		            
		            if (!confirm(confirmMsg)) {
		                e.preventDefault();
		                return false;
		            }
		        });
		    });
		    
		    // Print service function
		    function printService() {
		        var noService = '<?php echo $no_service; ?>';
		        if (noService) {
		            window.open('servis-print.php?snoserv=' + noService, '_blank');
		        } else {
		            alert('Simpan service terlebih dahulu sebelum mencetak!');
		        }
		    }
		    
		    // Reset service function
		    function resetService() {
		        if (confirm('Yakin ingin mereset service garansi ini?\nData yang sudah diinput akan dikosongkan!')) {
		            var noService = '<?php echo $no_service; ?>';
		            if (noService) {
		                window.location.href = 'servis-carinopol-kosongkan.php?snoserv=' + noService;
		            } else {
		                window.location.reload();
		            }
		        }
		    }
		    
		    // Check warranty function
		    function checkWarranty() {
		        var noService = '<?php echo $no_service; ?>';
		        if (noService) {
		            if (confirm('Buka halaman cek garansi untuk service: ' + noService + '?\nAnda akan diarahkan ke halaman informasi garansi.')) {
		                // Redirect to warranty check page
		                window.location.href = 'cek-garansi.php?snoserv=' + noService;
		            }
		        } else {
		            alert('Simpan service terlebih dahulu sebelum mengecek garansi!');
		        }
		    }
		    
		    // Cancel service function
		    function cancelService() {
		        if (confirm('Yakin ingin membatalkan service garansi ini?\nData yang sudah diinput akan hilang!')) {
		            window.location.href = 'servis.php';
		        }
		    }
		</script>

        <script type="text/javascript">
        setTimeout(function(){
            if (typeof jQuery === 'undefined') return;
            jQuery(function($){
                var $modal = $('#modalStatistikPelanggan');
                if ($modal.length && typeof $.fn.modal !== 'undefined'){
                    $modal.modal({backdrop:'static', keyboard:true, show:false});
                }
                $(document).on('click','button[data-target="#modalStatistikPelanggan"]', function(e){
                    e.preventDefault();
                    var noPelanggan = '<?php echo $kode_pelanggan; ?>';
                    if (!noPelanggan) {
                        alert('Pilih Kode/Nama Pelanggan terlebih dahulu.');
                        return false;
                    }
                    if ($('#modalStatistikPelanggan').length && typeof $.fn.modal !== 'undefined'){
                        $('#modalStatistikPelanggan').modal('show');
                    }
                });
            });
        },300);
        </script>

        <script type="text/javascript">
        (function(){
            if (typeof jQuery === 'undefined') return;
            jQuery(function($){
                var $tabs = $('#myTab a[data-toggle="tab"]');
                if ($tabs.length) {
                    if (typeof $.fn.tab === 'undefined') {
                        $tabs.on('click', function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            var target = $(this).attr('href');
                            $('#myTab li').removeClass('active');
                            $(this).parent().addClass('active');
                            $('.tab-content .tab-pane').removeClass('active in');
                            $(target).addClass('active in');
                        });
                    } else {
                        $tabs.on('click', function(e){ e.preventDefault(); e.stopPropagation(); $(this).tab('show'); });
                    }
                }

                $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik', function(res){
                    try{
                        if(res && res.success && res.has_data === false){
                            var go = confirm('Belum ada input Kepala Mekanik Harian untuk hari ini. Buka halaman input sekarang?');
                            if(go && res.redirect_url) window.location.href = res.redirect_url;
                        }
                    }catch(err){}
                }, 'json');
            });
        })();

        function refreshKepalaMetanikHarian(){ window.location.reload(); }
        </script>

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
            // Global tabs fallback with debug logs
            var DEBUG = true;
            function log(){ if (DEBUG && window.console) try{ console.log.apply(console, arguments); }catch(e){} }
            function bindTabsWithjQuery($){
                var $links = $('a[data-toggle="tab"]');
                log('[TabsInit][Garansi] Found tab links:', $links.length, 'Bootstrap tab:', typeof $.fn.tab);
                if ($links.length===0) return;
                if (typeof $.fn.tab === 'undefined'){
                    $links.off('click.__fallback').on('click.__fallback', function(ev){
                        ev.preventDefault(); ev.stopPropagation();
                        var target = $(this).attr('href');
                        log('[TabsInit][Garansi] Fallback activating tab:', target);
                        $('#myTab li').removeClass('active');
                        $(this).parent().addClass('active');
                        $('.tab-content .tab-pane').removeClass('active in');
                        $(target).addClass('active in');
                    });
                } else {
                    $links.off('click.__force').on('click.__force', function(ev){
                        ev.preventDefault(); ev.stopPropagation();
                        var target = $(this).attr('href');
                        log('[TabsInit][Garansi] Bootstrap show tab:', target);
                        $(this).tab('show');
                    });
                }
            }
            function bindTabsVanilla(){
                if (window.jQuery) return; // prefer jQuery binding
                var listener = function(ev){
                    var a = ev.target && ev.target.closest ? ev.target.closest('a[data-toggle="tab"]') : null;
                    if (!a) return;
                    ev.preventDefault(); ev.stopPropagation();
                    var target = a.getAttribute('href');
                    log('[TabsInit][Garansi][Vanilla] Fallback activating tab:', target);
                    try{
                        document.querySelectorAll('#myTab li').forEach(function(li){ li.classList.remove('active'); });
                        if (a.parentNode) a.parentNode.classList.add('active');
                        document.querySelectorAll('.tab-content .tab-pane').forEach(function(p){ p.classList.remove('active'); p.classList.remove('in'); });
                        var el = document.querySelector(target);
                        if (el){ el.classList.add('active'); el.classList.add('in'); }
                    }catch(e){ log('[TabsInit][Garansi][Vanilla] Error:', e); }
                };
                document.addEventListener('click', listener, true);
                log('[TabsInit][Garansi] Vanilla fallback bound');
            }
            function init(){
                log('[TabsInit][Garansi] Initializing...');
                if (window.jQuery) { jQuery(function($){ bindTabsWithjQuery($); }); }
                else { bindTabsVanilla(); }
            }
            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
        })();
        </script>
	</body>
</html>

