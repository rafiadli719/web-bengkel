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
        include "_include_customer_vehicle_sync.php";
		include "_include_statistik_pelanggan.php";
		include "_include_kategori_member.php"; // Member kategori & discount helper
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";
		include_once "helper-functions.php";

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

    // Set active tab based on URL parameter or search parameters
    $active_tab = 'service-details'; // Default tab

    // Priority 1: Always check URL tab parameter first
    if (isset($_GET['tab'])) {
        switch($_GET['tab']) {
            case 'items':
                $active_tab = 'service-items';
                break;
            case 'jasa':
                $active_tab = 'service-jasa';
                break;
            case 'workorder':
                $active_tab = 'workorder-details';
                break;
            case 'actions':
                $active_tab = 'service-actions';
                break;
            default:
                $active_tab = 'service-details';
        }
    }
    // Priority 2: Check search parameters and form actions to maintain tab state
    elseif (!empty($txtcaribrg)) {
        $active_tab = 'service-items';
    } elseif (!empty($txtcarisrv)) {
        $active_tab = 'service-jasa';
    } elseif (!empty($txtcariwo)) {
        $active_tab = 'workorder-details';
    }

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

        // Calculate current totals from database
        $total_service = 0;
        $total_waktu = 0;
        $total_barang = 0;

        if(!empty($no_service)) {
            $cari_service = mysqli_query($koneksi,"SELECT COALESCE(sum(total), 0) as tot,
                                                  COALESCE(sum(waktu), 0) as tot_waktu
                                                  FROM tblservis_jasa
                                                  WHERE no_service='$no_service'");
            $tm_service = mysqli_fetch_array($cari_service);
            $total_service = $tm_service['tot'] ?? 0;
            $total_waktu = $tm_service['tot_waktu'] ?? 0;

            $cari_barang = mysqli_query($koneksi,"SELECT COALESCE(sum(total), 0) as tot
                                                 FROM tblservis_barang
                                                 WHERE no_service='$no_service'");
            $tm_barang = mysqli_fetch_array($cari_barang);
            $total_barang = $tm_barang['tot'] ?? 0;
        }

        // Calculate totals and apply automatic discount
        $tot = $total_service + $total_barang;

        // Get automatic discount based on customer category
        $auto_discount_percent = getDiscountByCategory($kategori_pelanggan, $potongan_pelanggan, $tipe_potongan);

        // Apply discount
        $discount_amount = $tot * ($auto_discount_percent / 100);
        $net = $tot - $discount_amount;
        $bayar = $net;
        $kembalian = $bayar - $net;

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
        $txtnamawo = ''; // Initialize for workorder name display

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

        // Get workorder data if searching for workorder
        if(!empty($txtcariwo)) {
            $cari_wo = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcariwo'");
            $tm_wo = mysqli_fetch_array($cari_wo);
            $txtnamawo = $tm_wo['nama_wo'] ?? '';
        }

    // Get existing mechanic data if service exists
        if(!empty($no_service)) {
            $query_existing = "SELECT * FROM tblservice WHERE no_service='$no_service'";
            $result_existing = mysqli_query($koneksi, $query_existing);
            if($result_existing && mysqli_num_rows($result_existing) > 0) {
            $existing_data = mysqli_fetch_array($result_existing);
            $kepala_mekanik1 = $existing_data['kepala_mekanik1'] ?? '';
            $persen_kepala1 = $existing_data['persen_kepala_mekanik1'] ?? 0;
            $kepala_mekanik2 = $existing_data['kepala_mekanik2'] ?? '';
            $persen_kepala2 = $existing_data['persen_kepala_mekanik2'] ?? 0;
            $admin1 = ''; // Admin columns don't exist in database yet
            $persen_admin1 = 0;
            $admin2 = '';
            $persen_admin2 = 0;
            $mekanik1 = $existing_data['mekanik1'] ?? '';
            $persen_kerja1 = $existing_data['persen_mekanik1'] ?? 0;
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_kerja2 = $existing_data['persen_mekanik2'] ?? 0;
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_kerja3 = $existing_data['persen_mekanik3'] ?? 0;
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_kerja4 = $existing_data['persen_mekanik4'] ?? 0;
            $metode_pembayaran = $existing_data['metode_pembayaran'] ?? 'Tunai';
            $bukti_pembayaran = $existing_data['bukti_pembayaran'] ?? '';
        }
    }

    // Initialize payment variables if not set
    if(!isset($metode_pembayaran)) {
        $metode_pembayaran = 'Tunai';
    }
    if(!isset($bukti_pembayaran)) {
        $bukti_pembayaran = '';
    }

    // Initialize other variables
    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';
    $prioritas_wo = 'normal'; // Regular service
    $deskripsi_pekerjaan = '';
    $instruksi_khusus = '';
    $catatan_wo = '';

    // Get additional service data if exists
    if(!empty($no_service)) {
        $query_service_detail = "SELECT * FROM tblservice WHERE no_service='$no_service'";
        $result_service_detail = mysqli_query($koneksi, $query_service_detail);
        if($result_service_detail && mysqli_num_rows($result_service_detail) > 0) {
            $service_detail = mysqli_fetch_array($result_service_detail);
            $keluhan = $service_detail['keluhan'] ?? '';
            $catatan = $service_detail['catatan'] ?? '';
            $no_workorder = $service_detail['no_workorder'] ?? '';
            $tanggal_wo = $service_detail['tanggal_wo'] ?? date('d/m/Y');
            $estimasi_selesai = $service_detail['estimasi_selesai'] ?? '';
            $prioritas_wo = $service_detail['prioritas_wo'] ?? 'normal';
            $deskripsi_pekerjaan = $service_detail['deskripsi_pekerjaan'] ?? '';
            $instruksi_khusus = $service_detail['instruksi_khusus'] ?? '';
            $catatan_wo = $service_detail['catatan_wo'] ?? '';
        }
    }

    // Helper function to get current tab
    function getCurrentTab() {
        // Get posted tab or URL parameter
        $tab = $_POST['tab'] ?? $_GET['tab'] ?? 'details';

        // Convert internal tab names to URL parameters
        switch($tab) {
            case 'service-items':
            case 'items':
                return 'items';
            case 'service-jasa':
            case 'jasa':
                return 'jasa';
            case 'workorder-details':
            case 'workorder':
                return 'workorder';
            case 'service-actions':
            case 'actions':
                return 'actions';
            case 'service-details':
            default:
                return 'details';
        }
    }

    // Helper function to build URL with parameters
    function buildUrl($base, $params = []) {
        // Add tab parameter if not present
        if (!isset($params['tab'])) {
            $params['tab'] = getCurrentTab();
        }

        // Build query string
        $query = http_build_query($params);
        return $base . ($query ? '?' . $query : '');
    }

    // ========== HANDLER: CARI ITEM BARANG ==========
    if (isset($_POST['btncari'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

        // Redirect to item search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-item-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcaribrg) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=items&_from=rst');</script>";
        exit;
    }

    // ========== HANDLER: CARI JASA ==========
    if (isset($_POST['btncarisrv'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');

        // Redirect to jasa search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jasa-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcarisrv) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=jasa&_from=rst');</script>";
        exit;
    }

    // ========== HANDLER: UPDATE MECHANIC DATA ==========
    if(isset($_POST['btnupdatemekanik'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';

            // Get mechanic data from form and escape
            $no_service = mysqli_real_escape_string($koneksi, $no_service);
            $kepala_mekanik1 = mysqli_real_escape_string($koneksi, $_POST['cbokepala_mekanik1'] ?? '');
            $persen_kepala1 = intval($_POST['txtpersen_kepala1'] ?? 0);
            $kepala_mekanik2 = mysqli_real_escape_string($koneksi, $_POST['cbokepala_mekanik2'] ?? '');
            $persen_kepala2 = intval($_POST['txtpersen_kepala2'] ?? 0);
            $admin1 = mysqli_real_escape_string($koneksi, $_POST['cboadmin1'] ?? '');
            $persen_admin1 = intval($_POST['txtpersen_admin1'] ?? 0);
            $admin2 = mysqli_real_escape_string($koneksi, $_POST['cboadmin2'] ?? '');
            $persen_admin2 = intval($_POST['txtpersen_admin2'] ?? 0);
            $mekanik1 = mysqli_real_escape_string($koneksi, $_POST['cbomekanik1'] ?? '');
            $persen_mekanik1 = intval($_POST['txtpersen_mekanik1'] ?? 0);
            $mekanik2 = mysqli_real_escape_string($koneksi, $_POST['cbomekanik2'] ?? '');
            $persen_mekanik2 = intval($_POST['txtpersen_mekanik2'] ?? 0);
            $mekanik3 = mysqli_real_escape_string($koneksi, $_POST['cbomekanik3'] ?? '');
            $persen_mekanik3 = intval($_POST['txtpersen_mekanik3'] ?? 0);
            $mekanik4 = mysqli_real_escape_string($koneksi, $_POST['cbomekanik4'] ?? '');
            $persen_mekanik4 = intval($_POST['txtpersen_mekanik4'] ?? 0);

            // Update mechanic data in tblservice - Fixed column names
            $update_mechanic = "UPDATE tblservice SET
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
                updated_at=NOW()
                WHERE no_service='$no_service'";

            if(mysqli_query($koneksi, $update_mechanic)) {
                $redirect_url = buildUrl('servis-input-reguler-rst.php', [
                    'snoserv' => $no_service,
                    'kd' => $txtcaribrg,
                    'kdjasa' => $txtcarisrv,
                    'kdwo' => $txtcariwo
                ]);

                echo "<script>
                    alert('Data mekanik berhasil diupdate!');
                    window.location='$redirect_url';
                </script>";
            } else {
                echo "<script>alert('Error update data mekanik: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // ========== HANDLER: ADD KELUHAN TO SPK ==========
    if(isset($_POST['btnaddkeluhan'])) {
        $no_service = $_POST['txtnosrv'];
        $txtkeluhan = $_POST['txtkeluhan'];

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        if(!empty($txtkeluhan)) {
            // Insert keluhan to SPK table
            mysqli_query($koneksi,"INSERT INTO tbservis_keluhan_status
                                   (no_service, keluhan, status_pengerjaan)
                                   VALUES
                                   ('$no_service','$txtkeluhan','datang')");

            // Update KM data
            mysqli_query($koneksi,"UPDATE tblservice
                                   SET km_skr='$km_skr', km_berikut='$km_berikut'
                                   WHERE no_service='$no_service'");

            $redirect_url = buildUrl('servis-input-reguler-rst.php', [
                'snoserv' => $no_service,
                'tab' => 'workorder'
            ]);

            echo "<script>
                alert('Keluhan berhasil ditambahkan ke SPK!');
                window.location='$redirect_url';
            </script>";
        } else {
            echo "<script>
                alert('Keluhan tidak boleh kosong!');
                window.history.back();
            </script>";
        }
    }

    // ========== HANDLER: SEARCH WORKORDER ==========
    if(isset($_POST['btncariwo'])) {
        $no_service = $_POST['txtnosrv'];
        $txtcariwo = $_POST['txtcariwo'];
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        // Update KM data before redirecting
        mysqli_query($koneksi,"UPDATE tblservice
                               SET km_skr='$km_skr', km_berikut='$km_berikut'
                               WHERE no_service='$no_service'");

        // Redirect to workorder search page
        $cbocari = "";
        $cbourut = "52";
        echo"<script>window.location=('servis-add-workorder-cari.php?snoserv=$no_service&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=workorder');</script>";
    }

    // ========== HANDLER: ADD WORKORDER TO SPK ==========
    if(isset($_POST['btnaddworkorder'])) {
        $no_service = $_POST['txtnosrv'];
        $kode_wo = $_POST['txtcariwo'];

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        if(!empty($kode_wo)) {
            // Ambil no_polisi untuk hitung diskon member/promo
            $no_polisi_svc = '';
            $q_cust_wo = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service'");
            if($q_cust_wo && ($cust_wo = mysqli_fetch_assoc($q_cust_wo))) {
                $no_polisi_svc = $cust_wo['no_polisi'] ?? '';
            }

            // Check if workorder already exists in SPK
            $check_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbservis_workorder
                                               WHERE no_service='$no_service' AND kode_wo='$kode_wo'");
            $check_result = mysqli_fetch_array($check_wo);

            if($check_result['count'] == 0) {
                // Verify workorder exists in master
                $verify_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
                $verify_result = mysqli_fetch_array($verify_wo);

                if($verify_result['count'] > 0) {
                    // Insert workorder to SPK
                    mysqli_query($koneksi,"INSERT INTO tbservis_workorder
                                          (no_service, kode_wo, status_pengerjaan)
                                          VALUES
                                          ('$no_service','$kode_wo','diproses')");

                    // Auto-add jasa dan barang dari workorder detail
                    $detail_wo = mysqli_query($koneksi,"SELECT kode_barang, tipe, harga, total, jumlah
                                                        FROM tbworkorderdetail
                                                        WHERE kode_wo='$kode_wo'");

                    while($detail = mysqli_fetch_array($detail_wo)) {
                        if($detail['tipe'] == '1') {
                            // Jasa - Insert to tblservis_jasa
                            $waktu = 0;
                            try {
                                // Get waktu from tbworkorderheader if column exists
                                $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorderheader LIKE 'waktu'");
                                if(mysqli_num_rows($check_waktu) > 0) {
                                    $waktu_query = mysqli_query($koneksi,"SELECT waktu FROM tbworkorderheader WHERE kode_wo='{$detail['kode_barang']}'");
                                    if($waktu_query && $waktu_data = mysqli_fetch_array($waktu_query)) {
                                        $waktu = $waktu_data['waktu'] ?? 0;
                                    }
                                }
                            } catch (Exception $e) {
                                $waktu = 0;
                            }

                            // Check if jasa already exists
                            $check_jasa = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tblservis_jasa
                                                                 WHERE no_service='$no_service' AND no_item='{$detail['kode_barang']}'");
                            $check_jasa_result = mysqli_fetch_array($check_jasa);

                            if($check_jasa_result['count'] == 0) {
                                // Get next nobaris for jasa
                                $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                                $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
                                $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

                                // Hitung diskon member/promo aktif untuk item ini
                                $diskon_source = ''; $diskon_persen = 0; $diskon_nominal = 0; $id_promo = 'NULL';
                                $harga_jasa_wo = floatval($detail['harga']);
                                if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                                    $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $detail['kode_barang'], 'jasa', $harga_jasa_wo);
                                    if(($disc['diskon_nominal'] ?? 0) > 0) {
                                        $diskon_persen = floatval($disc['diskon_persen']);
                                        $diskon_nominal = floatval($disc['diskon_nominal']);
                                        $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                        if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                            $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                            if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                        }
                                    }
                                }
                                $total_jasa_wo = $harga_jasa_wo - $diskon_nominal;
                                if($total_jasa_wo < 0) { $total_jasa_wo = 0; }

                                // Check if waktu column exists in tblservis_jasa
                                $check_jasa_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
                                if(mysqli_num_rows($check_jasa_waktu) > 0) {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, nobaris, no_item, harga, waktu, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                                          VALUES
                                                          ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '$harga_jasa_wo', '$waktu', '$diskon_persen', '$total_jasa_wo', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
                                } else {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, nobaris, no_item, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                                          VALUES
                                                          ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '$harga_jasa_wo', '$diskon_persen', '$total_jasa_wo', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
                                }
                                if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'jasa', $detail['kode_barang']); }
                            }
                        } else {
                            // Barang - Insert to tblservis_barang
                            // Check if barang already exists
                            $check_barang = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tblservis_barang
                                                                   WHERE no_service='$no_service' AND no_item='{$detail['kode_barang']}'");
                            $check_barang_result = mysqli_fetch_array($check_barang);

                            if($check_barang_result['count'] == 0) {
                                // Get next nobaris for barang
                                $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
                                $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
                                $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

                                // Hitung diskon member/promo aktif untuk item ini
                                $diskon_source = ''; $diskon_persen = 0; $diskon_nominal = 0; $id_promo = 'NULL';
                                $harga_brg_wo = floatval($detail['harga']);
                                $qty_brg_wo = intval($detail['jumlah']);
                                if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                                    $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $detail['kode_barang'], 'barang', $harga_brg_wo);
                                    if(($disc['diskon_nominal'] ?? 0) > 0) {
                                        $diskon_persen = floatval($disc['diskon_persen']);
                                        $diskon_nominal = floatval($disc['diskon_nominal']);
                                        $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                        if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                            $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                            if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                        }
                                    }
                                }
                                $total_brg_wo = ($harga_brg_wo * $qty_brg_wo) - ($diskon_nominal * $qty_brg_wo);
                                if($total_brg_wo < 0) { $total_brg_wo = 0; }

                                mysqli_query($koneksi,"INSERT INTO tblservis_barang
                                                      (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                                      VALUES
                                                      ('$no_service', '$nobaris_brg', '{$detail['kode_barang']}', '$qty_brg_wo', '0', '$harga_brg_wo', '$diskon_persen', '$total_brg_wo', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
                                if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'barang', $detail['kode_barang']); }
                            }
                        }
                    }

                    // Update KM data
                    mysqli_query($koneksi,"UPDATE tblservice
                                           SET km_skr='$km_skr', km_berikut='$km_berikut'
                                           WHERE no_service='$no_service'");

                    $redirect_url = buildUrl('servis-input-reguler-rst.php', [
                        'snoserv' => $no_service,
                        'tab' => 'workorder'
                    ]);

                    echo "<script>
                        alert('Work Order berhasil ditambahkan ke SPK!\\nJasa dan Barang dari WO telah otomatis ditambahkan.');
                        window.location='$redirect_url';
                    </script>";
                } else {
                    echo "<script>
                        alert('Kode Work Order tidak ditemukan di master!\\nSilakan periksa kembali kode WO.');
                        window.history.back();
                    </script>";
                }
            } else {
                $redirect_url = buildUrl('servis-input-reguler-rst.php', [
                    'snoserv' => $no_service,
                    'tab' => 'workorder'
                ]);

                echo "<script>
                    alert('Work Order ini sudah ada di SPK!');
                    window.location='$redirect_url';
                </script>";
            }
        } else {
            echo "<script>
                alert('Kode Work Order tidak boleh kosong!');
                window.history.back();
            </script>";
        }
    }

    // Payment Processing for RST
    if(isset($_POST['btnbayar'])) {
        $no_service= $_POST['txtnosrv'] ?? $no_service;
        $km_skr=$_POST['txtkm_skr'] ?? 0;
        $km_berikut=$_POST['txtkm_next'] ?? 0;

        $diskon_member = $_POST['txtdiskon_member'] ?? 0;
        $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'] ?? 0;
        $total_diskon_persen = ($diskon_member > 0 ? $diskon_member : $txtpotfaktur_persen);
        $txtpotfaktur_nom= str_replace(['.', ','], '', $_POST['txtpotfaktur_nom'] ?? '0'); // Remove formatting
        $txtpajak_persen= $_POST['txtpajak_persen'] ?? 0;
        $metode_pembayaran= $_POST['metode_pembayaran'] ?? 'Tunai';
        $txtbayar= str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0'); // Remove formatting

        // Handle bukti pembayaran upload
        $bukti_pembayaran_path = '';
        if($metode_pembayaran != 'Tunai' && isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
            $upload_dir = 'uploads/bukti_pembayaran/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

            if(in_array($file_ext, $allowed_ext) && $_FILES['bukti_pembayaran']['size'] <= 2097152) { // 2MB max
                $new_filename = 'bukti_' . $no_service . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if(move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $upload_path)) {
                    $bukti_pembayaran_path = $upload_path;
                }
            }
        }

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

        // Pre-payment validations (roles & keluhan status)
        $err_msgs = array();
        $has_kepala1 = !empty($kepala_mekanik1);
        $has_kepala2 = !empty($kepala_mekanik2);
        $kepala_count = ($has_kepala1?1:0) + ($has_kepala2?1:0);
        $pk1 = floatval($persen_kepala1);
        $pk2 = floatval($persen_kepala2);
        if($kepala_count == 0) { $err_msgs[] = 'Kepala Mekanik wajib diisi.'; }
        if($kepala_count == 1) { $p = $has_kepala1 ? $pk1 : $pk2; if(round($p,2) != 100) { $err_msgs[] = 'Persentase Kepala Mekanik harus 100% jika hanya satu.'; } }
        if($kepala_count == 2) { if(round($pk1 + $pk2,2) != 100) { $err_msgs[] = 'Total persentase Kepala Mekanik harus 100%.'; } }
        if(($pk1 < 0 || $pk1 > 100) || ($pk2 < 0 || $pk2 > 100)) { $err_msgs[] = 'Persentase Kepala Mekanik harus 0-100.'; }

        // Admin validations: require at least admin1
        $has_admin1 = isset($_POST['cboadmin1']) && $_POST['cboadmin1'] !== '';
        $has_admin2 = isset($_POST['cboadmin2']) && $_POST['cboadmin2'] !== '';
        $pa1 = floatval($_POST['txtpersen_admin1'] ?? 0);
        $pa2 = floatval($_POST['txtpersen_admin2'] ?? 0);
        $admin_count = ($has_admin1?1:0) + ($has_admin2?1:0);
        if(!$has_admin1) { $err_msgs[] = 'Admin wajib diisi (minimal Admin 1).'; }
        if($admin_count == 1) { $p = $has_admin1 ? $pa1 : $pa2; if(round($p,2) != 100) { $err_msgs[] = 'Persentase Admin harus 100% jika hanya satu.'; } }
        if($admin_count == 2) { if(round($pa1 + $pa2,2) != 100) { $err_msgs[] = 'Total persentase Admin harus 100%.'; } }
        if(($pa1 < 0 || $pa1 > 100) || ($pa2 < 0 || $pa2 > 100)) { $err_msgs[] = 'Persentase Admin harus 0-100.'; }

        // Mekanik validations
        $mekanik_vals = array(); $mekanik_pers = array();
        if(!empty($mekanik1)) { $mekanik_vals[] = $mekanik1; $mekanik_pers[] = floatval($persen_mekanik1); }
        if(!empty($mekanik2)) { $mekanik_vals[] = $mekanik2; $mekanik_pers[] = floatval($persen_mekanik2); }
        if(!empty($mekanik3)) { $mekanik_vals[] = $mekanik3; $mekanik_pers[] = floatval($persen_mekanik3); }
        if(!empty($mekanik4)) { $mekanik_vals[] = $mekanik4; $mekanik_pers[] = floatval($persen_mekanik4); }
        if(count($mekanik_vals) == 0) { $err_msgs[] = 'Mekanik wajib diisi (minimal 1).'; }
        $sum_mek = 0.0; foreach($mekanik_pers as $p) { if($p < 0 || $p > 100) { $err_msgs[] = 'Persentase Mekanik harus 0-100.'; } $sum_mek += $p; }
        if(count($mekanik_vals) == 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Persentase Mekanik harus 100% jika hanya satu.'; } }
        if(count($mekanik_vals) > 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Total persentase Mekanik harus 100%.'; } }

        // Keluhan still in progress?
        $___ns = mysqli_real_escape_string($koneksi, $no_service);
        $cek_keluhan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_keluhan_status WHERE no_service='".$___ns."' AND status_pengerjaan IN ('datang','diproses')");
        if($cek_keluhan && ($rk = mysqli_fetch_assoc($cek_keluhan))) { if(intval($rk['c']) > 0) { $err_msgs[] = 'Masih ada keluhan dengan status dalam proses/belum selesai.'; } }

        // 2. Validasi Temuan (New) - Harus Disetujui/Ditolak/Selesai, tidak boleh Ditemukan/Ditawarkan
        $cek_temuan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan WHERE no_service='".$___ns."' AND status_temuan IN ('ditemukan','ditawarkan')");
        if($cek_temuan && ($rt = mysqli_fetch_assoc($cek_temuan))) {
            if(intval($rt['c']) > 0) { $err_msgs[] = 'Masih ada TEMUAN yang belum diproses (status Ditemukan/Ditawarkan). Harap setujui atau tolak temuan tsb.'; }
        }

        // 3. Validasi Penawaran Part/Jasa (New) - Tidak boleh status Pending
        $cek_part_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_part WHERE no_service='".$___ns."' AND status='pending'");
        if($cek_part_pending && ($rp = mysqli_fetch_assoc($cek_part_pending))) {
            if(intval($rp['c']) > 0) { $err_msgs[] = 'Masih ada Penawaran PART yang statusnya Pending.'; }
        }

        $cek_jasa_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_jasa WHERE no_service='".$___ns."' AND status='pending'");
        if($cek_jasa_pending && ($rj = mysqli_fetch_assoc($cek_jasa_pending))) {
            if(intval($rj['c']) > 0) { $err_msgs[] = 'Masih ada Penawaran JASA yang statusnya Pending.'; }
        }

        if(!empty($err_msgs)) {
            $msg = implode("\\n- ", $err_msgs);
            echo "<script>window.alert('Tidak dapat memproses pembayaran karena:\\n- ".$msg."'); window.location='servis-input-reguler-rst.php?snoserv=".addslashes($no_service)."&tab=actions#service-actions';</script>";
            exit;
        }


    // ========== AUTO-FILL KEPALA MEKANIK HARIAN ==========
    // Jika data kepala mekanik masih kosong (insert baru atau belum ada di db), ambil dari jadwal harian
    if (empty($kepala_mekanik1)) {
        $tanggal_hari_ini = date('Y-m-d');
        // Gunakan $kd_cabang dari session yang sudah didefinisikan di awal file
        $query_km_harian = "SELECT kepala_mekanik_1, kepala_mekanik_2 FROM tbl_kepala_mekanik_harian 
                            WHERE kode_cabang='$kd_cabang' 
                            AND tanggal_kerja='$tanggal_hari_ini' LIMIT 1";
        $result_km_harian = mysqli_query($koneksi, $query_km_harian);
        
        if ($result_km_harian && mysqli_num_rows($result_km_harian) > 0) {
            $data_km_harian = mysqli_fetch_array($result_km_harian);
            $kepala_mekanik1 = $data_km_harian['kepala_mekanik_1'];
            
            // Logika Persentase Otomatis
            if (!empty($data_km_harian['kepala_mekanik_2'])) {
                // Jika ada 2 kepala mekanik, set default 50:50
                if (empty($kepala_mekanik2)) {
                   $kepala_mekanik2 = $data_km_harian['kepala_mekanik_2'];
                   $persen_kepala1 = 50;
                   $persen_kepala2 = 50;
                }
            } else {
                // Jika hanya 1 kepala mekanik, set 100%
                $persen_kepala1 = 100;
                $persen_kepala2 = 0;
            }

        } else {
             // Notification for missing daily schedule
             echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if(!$('.alert-km-missing').length) {
                         var warningMsg = '<div class=\"alert alert-danger alert-dismissible alert-km-missing\" style=\"margin: 10px 0;\">';
                         warningMsg += '<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>';
                         warningMsg += '<i class=\"icon fa fa-warning\"></i> <strong>PERHATIAN!</strong> ';
                         warningMsg += 'Kepala Mekanik Harian untuk tanggal hari ini belum diinput. ';
                         warningMsg += '<a href=\"input_kepala_mekanik_harian.php\" style=\"font-weight:bold; text-decoration:underline;\">Klik disini untuk input sekarang</a>';
                         warningMsg += '</div>';
                         $('.page-content').prepend(warningMsg);
                    }
                });
            </script>";
        }
    }
    // ========== END AUTO-FILL KEPALA MEKANIK HARIAN ==========

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
        $ppn=($tot_pay - $diskon_nominal)*($txtpajak_persen/100);
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

        $tgl_bayar_set = '';
        $chk_tgl_bayar = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'tgl_bayar'");
        if ($chk_tgl_bayar && mysqli_num_rows($chk_tgl_bayar) > 0) {
            $tgl_bayar_set = 'tgl_bayar=NOW(),';
        }

        $update_query = "UPDATE tblservice
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
                                {$tgl_bayar_set}
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
                                kembali='$kembalian_pay'";

        // Add bukti_pembayaran if uploaded
        if(!empty($bukti_pembayaran_path)) {
            $update_query .= ", bukti_pembayaran='$bukti_pembayaran_path'";
        }

        $update_query .= " WHERE no_service='$no_service'";

        if(!mysqli_query($koneksi, $update_query)) {
            die("Error Update Service: " . mysqli_error($koneksi));
        }

        // 🆕 AUTO-UPDATE STATISTIK PELANGGAN, MEMBER TIER & HISTORY SERVICE
        $get_customer = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
        if ($get_customer && $customer_row = mysqli_fetch_assoc($get_customer)) {
            $no_pelanggan_bayar = $customer_row['no_pelanggan'];
            if (!empty($no_pelanggan_bayar)) {
                // Gunakan fungsi processAfterPayment untuk update semua data
                if (function_exists('processAfterPayment')) {
                    $payment_result = processAfterPayment($koneksi, $no_pelanggan_bayar, $no_service, 'reguler');
                    // Log jika naik tier
                    if ($payment_result['naik_tier']) {
                        error_log("Customer $no_pelanggan_bayar naik tier: " . json_encode($payment_result['tier_info']));
                    }
                } else {
                    // Fallback ke fungsi lama jika fungsi baru belum ada
                    updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan_bayar, $no_service);
                }
            }
        }

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

        // ========== SISTEM ANTRIAN - RST SERVICE ==========
        // Cek apakah sudah ada nomor antrian untuk service RST ini
        $check_antrian_rst = mysqli_query($koneksi, "SELECT no_antrian FROM tb_antrian_servis WHERE no_service='$no_service'");

        if(mysqli_num_rows($check_antrian_rst) == 0) {
            // Belum ada antrian, generate nomor antrian baru
            $tanggal_antrian_rst = date('Y-m-d');
            $jam_antrian_rst = date('H:i:s');

            // Hitung total antrian hari ini
            $query_count_rst = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal='$tanggal_antrian_rst'");
            $count_rst = mysqli_fetch_array($query_count_rst);
            $nomor_urut_rst = $count_rst['total'] + 1;

            // Format nomor antrian: A001, A002, dst
            $no_antrian_rst = 'A' . str_pad($nomor_urut_rst, 3, '0', STR_PAD_LEFT);

            // Insert ke tabel antrian dengan prioritas URGENT untuk RST
            $insert_antrian_rst = mysqli_query($koneksi, "INSERT INTO tb_antrian_servis (
                no_service, no_antrian, tanggal, jam_ambil,
                status_antrian, prioritas, estimasi_waktu, created_at
            ) VALUES (
                '$no_service', '$no_antrian_rst', '$tanggal_antrian_rst', '$jam_antrian_rst',
                'selesai', 'urgent', '$total_waktu_pay', NOW()
            )");

            // Update jam_selesai karena langsung bayar
            mysqli_query($koneksi, "UPDATE tb_antrian_servis SET jam_selesai=NOW() WHERE no_service='$no_service'");

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
                                logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (RST)');
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

            if(function_exists('clearSessionDiscount')) { clearSessionDiscount(); }
            echo"<script>window.alert('Pembayaran Service RST Berhasil!\\n\\nNomor Antrian: $no_antrian_rst (Prioritas: URGENT)\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "');
            window.location=('servis-print.php?snoserv=$no_service');</script>";
        } else {
            // Sudah ada antrian, update status menjadi selesai
            mysqli_query($koneksi, "UPDATE tb_antrian_servis SET status_antrian='selesai', jam_selesai=NOW() WHERE no_service='$no_service'");

            $existing_rst = mysqli_fetch_array($check_antrian_rst);
            $no_antrian_rst_exist = $existing_rst['no_antrian'];

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
                                logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (RST existing)');
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

            if(function_exists('clearSessionDiscount')) { clearSessionDiscount(); }
            echo"<script>window.alert('Pembayaran Service RST Berhasil!\\n\\nNomor Antrian: $no_antrian_rst_exist\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "');
            window.location=('servis-print.php?snoserv=$no_service');</script>";
        }
        // ========== END SISTEM ANTRIAN RST ==========
    }
}

// Determine active tab based on URL parameters
$active_tab = 'service-details'; // default tab
if (!empty($txtcaribrg)) {
    $active_tab = 'service-items'; // Item Barang tab
} elseif (!empty($txtcarisrv)) {
    $active_tab = 'service-jasa'; // Item Jasa tab
} elseif (!empty($txtcariwo)) {
    $active_tab = 'workorder-details'; // Work Order tab
} elseif (isset($_GET['tab']) && $_GET['tab'] == 'actions') {
    $active_tab = 'service-actions'; // Actions tab
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Detail Service Reguler (Selesai)</title>

    <meta name="description" content="Detail Service Reguler - Redesign v2.0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Bootstrap & FontAwesome (CDN with local fallback) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" />

    <!-- jQuery UI for datepicker -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- Fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- Redesign Styles -->
    <?php include "_template/_redesign_styles.php"; ?>

    <style>
    /* Page-specific overrides - RST (Read-Only) Mode */
    body {
        background: #f4f6f9;
        font-family: 'Open Sans', 'Segoe UI', Tahoma, sans-serif;
    }

    .rd-page-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .rd-page-header {
        background: linear-gradient(135deg, #2C3E50 0%, #1a252f 100%);
        color: white;
        padding: 20px 24px;
        border-radius: var(--rd-radius-lg);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--rd-shadow-md);
    }

    .rd-page-header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rd-page-header .rd-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        opacity: 0.9;
    }

    .rd-page-header .rd-breadcrumb a {
        color: white;
        text-decoration: none;
    }

    .rd-page-header .rd-breadcrumb a:hover {
        text-decoration: underline;
    }

    /* Quick Summary Bar - RST Darker */
    .rd-quick-summary {
        background: white;
        border-radius: var(--rd-radius-md);
        padding: 16px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--rd-shadow-sm);
        border-left: 4px solid #27ae60;
    }

    .rd-quick-summary-left {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .rd-quick-summary-item {
        display: flex;
        flex-direction: column;
    }

    .rd-quick-summary-item .label {
        font-size: 11px;
        color: var(--rd-text-muted);
        text-transform: uppercase;
    }

    .rd-quick-summary-item .value {
        font-size: 16px;
        font-weight: 600;
        color: var(--rd-text-dark);
    }

    .rd-quick-summary-item .value.primary { color: #2C3E50; }
    .rd-quick-summary-item .value.success { color: var(--rd-success); }

    /* Tab Content Animation */
    .rd-tab-pane {
        display: none;
        animation: rdFadeIn 0.3s ease;
    }

    .rd-tab-pane.active {
        display: block;
    }

    @keyframes rdFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* RST Mode - Completed Badge */
    .rd-completed-badge {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* RST Tab Nav Color Override */
    .rd-tabs-nav .rd-tab-btn.active {
        color: #2C3E50;
        border-bottom-color: #2C3E50;
    }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #2C3E50;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-motorcycle"></i>
                <?php include "../lib/subtitel.php"; ?>
            </a>

            <div class="navbar-nav ml-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown">
                        <img src="../<?php echo $foto_user; ?>" alt="User"
                             style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; object-fit: cover;">
                        <span><?php echo $_nama; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="change_pwd.php">
                            <i class="fas fa-cog"></i> Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="rd-page-wrapper">
        <!-- Page Header -->
        <div class="rd-page-header">
            <div>
                <h1>
                    <i class="fas fa-check-circle"></i>
                    Detail Service Reguler
                    <span class="rd-completed-badge">
                        <i class="fas fa-check"></i> SELESAI
                    </span>
                </h1>
                <div class="rd-breadcrumb" style="margin-top: 8px;">
                    <a href="index.php"><i class="fas fa-home"></i></a>
                    <span>/</span>
                    <a href="servis-reguler.php">Daftar Service</a>
                    <span>/</span>
                    <span>Detail Service (Selesai)</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="servis-print.php?snoserv=<?= urlencode($no_service) ?>" class="rd-btn success" target="_blank">
                    <i class="fas fa-print"></i> Print Invoice
                </a>
                <a href="servis-reguler.php" class="rd-btn" style="background: rgba(255,255,255,0.2); color: white; margin-left: 8px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Quick Summary Bar -->
        <div class="rd-quick-summary">
            <div class="rd-quick-summary-left">
                <div class="rd-quick-summary-item">
                    <span class="label">No. Service</span>
                    <span class="value primary"><?= htmlspecialchars($no_service) ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">Pelanggan</span>
                    <span class="value"><?= htmlspecialchars($namapelanggan) ?: '-' ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">No. Polisi</span>
                    <span class="value"><?= htmlspecialchars($no_polisi) ?: '-' ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">Status</span>
                    <span class="rd-badge solid-success">
                        <i class="fas fa-check"></i> SELESAI
                    </span>
                </div>
            </div>
            <div class="rd-quick-summary-right">
                <div class="rd-quick-summary-item" style="text-align: right;">
                    <span class="label">Total Bayar</span>
                    <span class="value success" style="font-size: 20px;">
                        Rp <?= number_format($tot, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Form (Read-Only) -->
        <form method="POST" action="" id="formServiceRST">
            <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">

            <!-- Tab Navigation -->
            <div class="rd-tabs-nav">
                <button type="button" class="rd-tab-btn <?= $active_tab == 'service-details' ? 'active' : '' ?>" data-target="service-details">
                    <i class="fas fa-info-circle"></i>
                    Detail Service
                </button>
                <button type="button" class="rd-tab-btn <?= $active_tab == 'workorder-details' ? 'active' : '' ?>" data-target="workorder-details">
                    <i class="fas fa-clipboard-list"></i>
                    Work Order
                    <?php
                    $count_wo = 0;
                    $sql_wo_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_workorder WHERE no_service='$no_service'");
                    if($sql_wo_count) { $count_wo = mysqli_fetch_array($sql_wo_count)['total']; }
                    if($count_wo > 0): ?>
                    <span class="rd-badge"><?= $count_wo ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="rd-tab-btn <?= $active_tab == 'temuan-penawaran' ? 'active' : '' ?>" data-target="temuan-penawaran">
                    <i class="fas fa-search-plus"></i>
                    Temuan & Penawaran
                </button>
                <button type="button" class="rd-tab-btn <?= $active_tab == 'service-items' ? 'active' : '' ?>" data-target="service-items">
                    <i class="fas fa-box"></i>
                    Item Barang
                    <?php
                    $count_brg = 0;
                    $sql_brg_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tblservis_barang WHERE no_service='$no_service'");
                    if($sql_brg_count) { $count_brg = mysqli_fetch_array($sql_brg_count)['total']; }
                    if($count_brg > 0): ?>
                    <span class="rd-badge"><?= $count_brg ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="rd-tab-btn <?= $active_tab == 'service-jasa' ? 'active' : '' ?>" data-target="service-jasa">
                    <i class="fas fa-tools"></i>
                    Item Jasa
                    <?php
                    $count_jasa = 0;
                    $sql_jasa_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tblservis_jasa WHERE no_service='$no_service'");
                    if($sql_jasa_count) { $count_jasa = mysqli_fetch_array($sql_jasa_count)['total']; }
                    if($count_jasa > 0): ?>
                    <span class="rd-badge"><?= $count_jasa ?></span>
                    <?php endif; ?>
                </button>
                <button type="button" class="rd-tab-btn <?= $active_tab == 'service-actions' ? 'active' : '' ?>" data-target="service-actions">
                    <i class="fas fa-receipt"></i>
                    Ringkasan Pembayaran
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="rd-tab-contents">
                <!-- Tab 1: Detail Service -->
                <div id="service-details" class="rd-tab-pane <?= $active_tab == 'service-details' ? 'active' : '' ?>">
                    <?php include "_template/tab-detail-service-redesign.php"; ?>
                </div>

                <!-- Tab 2: Work Order -->
                <div id="workorder-details" class="rd-tab-pane <?= $active_tab == 'workorder-details' ? 'active' : '' ?>">
                    <?php include "_template/tab-workorder-redesign.php"; ?>
                </div>

                <!-- Tab 3: Temuan & Penawaran -->
                <div id="temuan-penawaran" class="rd-tab-pane <?= $active_tab == 'temuan-penawaran' ? 'active' : '' ?>">
                    <?php include "_template/tab-temuan-penawaran-redesign.php"; ?>
                </div>

                <!-- Tab 4: Item Barang -->
                <div id="service-items" class="rd-tab-pane <?= $active_tab == 'service-items' ? 'active' : '' ?>">
                    <?php include "_template/tab-item-barang-redesign.php"; ?>
                </div>

                <!-- Tab 5: Item Jasa -->
                <div id="service-jasa" class="rd-tab-pane <?= $active_tab == 'service-jasa' ? 'active' : '' ?>">
                    <?php include "_template/tab-item-jasa-redesign.php"; ?>
                </div>

                <!-- Tab 6: Payment Summary (RST - Read Only) -->
                <div id="service-actions" class="rd-tab-pane <?= $active_tab == 'service-actions' ? 'active' : '' ?>">
                    <div class="rd-card success">
                        <div class="rd-card-header">
                            <h5><i class="fas fa-receipt"></i> Ringkasan Pembayaran</h5>
                            <span class="rd-badge solid-success">LUNAS</span>
                        </div>
                        <div class="rd-card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                <div>
                                    <div class="rd-form-group">
                                        <label class="rd-label">Total Jasa</label>
                                        <div class="rd-input-group">
                                            <span class="rd-input-addon">Rp</span>
                                            <input type="text" class="rd-input text-right" value="<?= number_format($total_service, 0, ',', '.') ?>" readonly style="background: var(--rd-bg-light);">
                                        </div>
                                    </div>
                                    <div class="rd-form-group">
                                        <label class="rd-label">Total Barang</label>
                                        <div class="rd-input-group">
                                            <span class="rd-input-addon">Rp</span>
                                            <input type="text" class="rd-input text-right" value="<?= number_format($total_barang, 0, ',', '.') ?>" readonly style="background: var(--rd-bg-light);">
                                        </div>
                                    </div>
                                    <div class="rd-form-group">
                                        <label class="rd-label"><strong>Subtotal</strong></label>
                                        <div class="rd-input-group">
                                            <span class="rd-input-addon">Rp</span>
                                            <input type="text" class="rd-input text-right" value="<?= number_format($tot, 0, ',', '.') ?>" readonly style="background: var(--rd-bg-light); font-weight: 600;">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="rd-form-group">
                                        <label class="rd-label">Diskon</label>
                                        <div class="rd-input-group">
                                            <span class="rd-input-addon">Rp</span>
                                            <input type="text" class="rd-input text-right" value="<?= number_format($diskon_nom ?? 0, 0, ',', '.') ?>" readonly style="background: var(--rd-bg-light);">
                                        </div>
                                    </div>
                                    <div class="rd-form-group">
                                        <label class="rd-label" style="font-size: 15px;"><strong>Total Bayar</strong></label>
                                        <div class="rd-input-group">
                                            <span class="rd-input-addon" style="background: var(--rd-success); color: white;">Rp</span>
                                            <input type="text" class="rd-input text-right" value="<?= number_format($total_grand ?? $tot, 0, ',', '.') ?>" readonly style="font-size: 18px; font-weight: 700; background: rgba(92, 184, 92, 0.1); color: var(--rd-success);">
                                        </div>
                                    </div>
                                    <div class="rd-form-group">
                                        <label class="rd-label">Metode Pembayaran</label>
                                        <input type="text" class="rd-input" value="<?= htmlspecialchars($metode_pembayaran ?? 'Tunai') ?>" readonly style="background: var(--rd-bg-light);">
                                    </div>
                                </div>
                            </div>

                            <div class="rd-divider"></div>

                            <div class="rd-alert success">
                                <i class="fas fa-check-circle"></i>
                                <div>
                                    <strong>Pembayaran Selesai</strong>
                                    <br><small>Tanggal bayar: <?= date('d/m/Y H:i', strtotime($tgl_bayar ?? 'now')) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mechanic Summary -->
                    <div class="rd-card" style="margin-top: 20px;">
                        <div class="rd-card-header">
                            <h5><i class="fas fa-users-cog"></i> Mekanik yang Bekerja</h5>
                        </div>
                        <div class="rd-card-body">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
                                <?php if(!empty($kepala_mekanik1)): ?>
                                <div class="rd-stat-box">
                                    <div class="icon primary"><i class="fas fa-user-tie"></i></div>
                                    <div class="label">Kepala Mekanik 1</div>
                                    <div class="value"><?= htmlspecialchars($kepala_mekanik1) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($mekanik1)): ?>
                                <div class="rd-stat-box">
                                    <div class="icon purple"><i class="fas fa-wrench"></i></div>
                                    <div class="label">Mekanik 1</div>
                                    <div class="value"><?= htmlspecialchars($mekanik1) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($mekanik2)): ?>
                                <div class="rd-stat-box">
                                    <div class="icon purple"><i class="fas fa-wrench"></i></div>
                                    <div class="label">Mekanik 2</div>
                                    <div class="value"><?= htmlspecialchars($mekanik2) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($admin1)): ?>
                                <div class="rd-stat-box">
                                    <div class="icon info"><i class="fas fa-user-edit"></i></div>
                                    <div class="label">Admin</div>
                                    <div class="value"><?= htmlspecialchars($admin1) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Include Modals -->
    <?php
    if(file_exists("_template/modal-callbacks.php")) include "_template/modal-callbacks.php";
    if(file_exists("_template/_modal_riwayat_kendaraan.php")) include "_template/_modal_riwayat_kendaraan.php";

    // Include Statistik Pelanggan Modal
    if(!empty($kode_pelanggan) && function_exists('renderModalStatistikPelanggan')) {
        echo renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
    }
    ?>

    <!-- Scripts with local fallbacks -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      if (typeof window.jQuery === 'undefined') {
        document.write('<script src="assets/js/jquery-2.1.4.min.js"><\/script>');
      }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script>
      (function(){
        var ensureBS = function(){
          if (window.jQuery && (typeof jQuery.fn.modal === 'undefined')) {
            document.write('<script src="assets/js/bootstrap.min.js"><\/script>');
          }
        };
        try { ensureBS(); } catch(e) {}
      })();
    </script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>

    <script>
    $(document).ready(function() {
        // Tab Navigation
        $('.rd-tab-btn').on('click', function() {
            var target = $(this).data('target');

            // Update active tab button
            $('.rd-tab-btn').removeClass('active');
            $(this).addClass('active');

            // Update active tab pane
            $('.rd-tab-pane').removeClass('active');
            $('#' + target).addClass('active');
        });

        // Card body toggle
        window.toggleCardBody = function(header) {
            var $header = $(header);
            var $body = $header.next('.rd-card-body');
            var $icon = $header.find('.rd-collapse-icon');

            $header.toggleClass('collapsed');
            $body.slideToggle(200);

            if ($header.hasClass('collapsed')) {
                $icon.css('transform', 'rotate(-90deg)');
            } else {
                $icon.css('transform', 'rotate(0deg)');
            }
        };

        // Show statistik pelanggan
        window.showStatistikPelanggan = function() {
            if ($('#modalStatistikPelanggan').length) {
                $('#modalStatistikPelanggan').modal('show');
            }
        };

        // Show riwayat kendaraan
        window.showRiwayatKendaraan = function() {
            if ($('#modalRiwayatKendaraan').length) {
                $('#modalRiwayatKendaraan').modal('show');
            }
        };
    });
    </script>

</body>
</html>
