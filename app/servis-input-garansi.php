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
                                
        // Update stock for items used in service — guard agar tidak double-insert
        $chk_stok = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tbstok WHERE no_transaksi='$no_service' AND tipe='4'");
        $r_chk = mysqli_fetch_assoc($chk_stok);
        if ((int)$r_chk['cnt'] === 0) {
            $sql = mysqli_query($koneksi,"SELECT * FROM tblservis_barang WHERE no_service='$no_service'");
            while ($tampil = mysqli_fetch_array($sql)) {
                $no_item = $tampil['no_item'];
                $qty     = (int)$tampil['quantity'];
                mysqli_query($koneksi,"INSERT INTO tbstok
                    (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                    VALUES
                    ('4','$no_service','$no_item','$tanggal_srv','0','$qty','Servis','$kd_cabang')");
            }
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <meta charset="utf-8"/>
    <title><?php include "../lib/titel.php"; ?> - Input Service Garansi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css"/>
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css"/>
    <!-- Redesign Styles -->
    <?php include "_template/_redesign_styles.php"; ?>
    <!-- Kasir 3-Column Layout -->
    <?php include "_template/_kasir_3col_layout.php"; ?>
    <style>
    body { font-family: 'Open Sans', 'Segoe UI', Tahoma, sans-serif; }
    .ks-status-pill.garansi { background: linear-gradient(135deg,#27ae60,#2ecc71); color:#fff; }
    .ks-btn-bayar { background: linear-gradient(135deg,#27ae60,#2ecc71) !important; }
    .ks-tab-btn.active { border-bottom-color: #27ae60 !important; color: #27ae60 !important; }
    .garansi-meta { padding:8px 12px; background:#f8fffe; border-bottom:1px solid #d1e8dc; }
    .garansi-meta label { font-size:10px;color:#6b7280;text-transform:uppercase;margin:0 0 2px;display:block; }
    .garansi-meta .rd-input, .garansi-meta textarea {
        width:100%;font-size:12px;padding:4px 8px;
        border:1px solid #d1d5db;border-radius:4px;background:#fff;
    }
    .garansi-meta textarea { resize:vertical; min-height:52px; }
    </style>
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
    <script src="assets/js/bootstrap-datepicker.min.js"></script>
</head>

<body>
<?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'workorder-details'; ?>
<div class="ks-shell">

    <!-- Topbar -->
    <div class="ks-topbar">
        <a class="ks-topbar-brand" href="index.php">
            <i class="fa fa-leaf"></i>
            <?php include "../lib/subtitel.php"; ?>
        </a>
        <div class="ks-topbar-info">
            <div class="ks-topbar-divider"></div>
            <div class="ks-topbar-item">
                <span class="lbl">No. Service</span>
                <span class="val"><?= htmlspecialchars($no_service ?: 'BARU') ?></span>
            </div>
            <span class="ks-status-pill garansi">
                <i class="fa fa-shield"></i> GARANSI
            </span>
            <div class="ks-topbar-divider"></div>
            <div class="ks-topbar-item">
                <span class="lbl">Pelanggan</span>
                <span class="val"><?= htmlspecialchars($namapelanggan ?: '-') ?></span>
            </div>
            <div class="ks-topbar-item">
                <span class="lbl">No. Polisi</span>
                <span class="val"><?= htmlspecialchars($no_polisi ?: '-') ?></span>
            </div>
        </div>
        <div class="ks-topbar-right">
            <div class="ks-total-live-wrap">
                <span class="lbl">Total</span>
                <span class="ks-total-live" id="ks-live-total">
                    Rp <?= number_format($net ?? $tot ?? 0, 0, ',', '.') ?>
                </span>
            </div>
            <a href="servis-garansi-rst.php" class="ks-topbar-btn">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <div class="ks-user-badge">
                <img src="../<?= $foto_user ?>" alt="User" class="ks-user-photo">
                <span class="ks-user-name"><?= htmlspecialchars($_nama) ?></span>
            </div>
        </div>
    </div>

    <!-- 3-Column Form -->
    <form class="form-horizontal" action="" method="post" id="formServiceGaransi" role="form">
        <div class="ks-body">

            <!-- LEFT: Info Garansi + Kendaraan + Mekanik -->
            <div class="ks-left">
                <!-- Garansi-specific: tanggal, jam, keluhan -->
                <div class="garansi-meta">
                    <div style="display:flex;gap:8px;margin-bottom:8px;">
                        <div style="flex:1;">
                            <label>Tanggal</label>
                            <input type="text" name="id-date-picker-1" class="rd-input date-picker"
                                   value="<?= htmlspecialchars($tanggal) ?>" data-date-format="dd/mm/yyyy">
                        </div>
                        <div style="flex:0 0 76px;">
                            <label>Jam</label>
                            <input type="time" name="jam_service" class="rd-input"
                                   value="<?= htmlspecialchars($jam) ?>">
                        </div>
                    </div>
                    <label>Keluhan Garansi</label>
                    <textarea name="keluhan" id="keluhan" rows="3"
                              placeholder="Keluhan garansi..."><?= htmlspecialchars($keluhan ?? '') ?></textarea>
                </div>
                <?php include "_template/panel-kiri-kasir.php"; ?>
            </div>

            <!-- CENTER: Work Order + Barang + Jasa -->
            <div class="ks-center">
                <div class="ks-tabs-nav">
                    <a class="ks-tab-btn <?= $active_tab=='workorder-details'?'active':'' ?>"
                       data-target="workorder-details">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <?php $c_wo=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tbservis_workorder WHERE no_service='$no_service'"); if($r){$c_wo=mysqli_fetch_assoc($r)['c'];} if($c_wo>0): ?>
                        <span class="ks-badge"><?= $c_wo ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='service-items'?'active':'' ?>"
                       data-target="service-items">
                        <i class="fa fa-box"></i> Item Barang
                        <?php $c_b=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tblservis_barang WHERE no_service='$no_service'"); if($r){$c_b=mysqli_fetch_assoc($r)['c'];} if($c_b>0): ?>
                        <span class="ks-badge"><?= $c_b ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='service-jasa'?'active':'' ?>"
                       data-target="service-jasa">
                        <i class="fa fa-tools"></i> Item Jasa
                        <?php $c_j=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tblservis_jasa WHERE no_service='$no_service'"); if($r){$c_j=mysqli_fetch_assoc($r)['c'];} if($c_j>0): ?>
                        <span class="ks-badge"><?= $c_j ?></span><?php endif; ?>
                    </a>
                </div>
                <div class="ks-tab-contents">
                    <?php $g_act = in_array($active_tab,['workorder-details','service-items','service-jasa']) ? $active_tab : 'workorder-details'; ?>
                    <div id="workorder-details" class="ks-tab-pane <?= $g_act=='workorder-details'?'active':'' ?>">
                        <?php include "_template/tab-workorder-redesign.php"; ?>
                    </div>
                    <div id="service-items" class="ks-tab-pane <?= $g_act=='service-items'?'active':'' ?>"
                         style="padding:16px;">
                        <?php include "_template/_servis_garansi_detail_barang.php"; ?>
                    </div>
                    <div id="service-jasa" class="ks-tab-pane <?= $g_act=='service-jasa'?'active':'' ?>">
                        <?php include "_template/_servis_garansi_detail_servis.php"; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Payment -->
            <div class="ks-right">
                <?php include "_template/panel-kanan-kasir.php"; ?>
            </div>

        </div>
    </form>
</div>

<!-- Modals -->
<?php
if(!empty($kode_pelanggan) && function_exists('renderModalStatistikPelanggan')) {
    echo renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
}
?>

<script>
$(document).ready(function() {
    $('.ks-tab-btn').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        $('.ks-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.ks-tab-pane').removeClass('active');
        $('#' + target).addClass('active');
    });
    if($.fn.datepicker) {
        $('.date-picker').datepicker({ autoclose: true, todayHighlight: true });
    }
    if(typeof hitungTotalV2 === 'function') hitungTotalV2();
});
</script>

</body>
</html>
