<?php
    session_start();

    // Helper function to build URLs with tab preservation
    function buildUrlWithTab($baseUrl, $params = []) {
        $urlParams = [];
        parse_str($_SERVER['QUERY_STRING'], $urlParams);

        // Add tab parameter if it exists
        if (!isset($params['tab']) && isset($urlParams['tab'])) {
            $params['tab'] = $urlParams['tab'];
        }

        // Remove null/empty values
        $params = array_filter($params, function($value) {
            return $value !== null && $value !== '';
        });

        // Build query string
        $query = http_build_query($params);
        return $baseUrl . ($query ? '?' . $query : '');
    }

        if(empty($_SESSION['_iduser'])){
        header("location:../index.php");
        exit;
    } else {
		$id_user=$_SESSION['_iduser'];
        $kd_cabang=$_SESSION['_cabang'];
		include "../config/koneksi.php";
		include_once "../lib/rbac.php";
		rbac_require_any(array('input_servis_read','jadwal_jemput_read','servis_jemput_read','servis_menu_read','service_create','service_update'));
		include "_include_statistik_pelanggan.php";
		include "_include_kategori_member.php"; // Member kategori & discount helper
		include "_include_customer_vehicle_sync.php";
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";
		include_once "helper-functions.php";

        if (!function_exists('normalizePostedInt')) {
            function normalizePostedInt($value, $default = 0)
            {
                if ($value === null || $value === '') {
                    return (int) $default;
                }

                if (is_string($value)) {
                    $value = preg_replace('/[^0-9\-]/', '', $value);
                }

                return ($value === '' || $value === null) ? (int) $default : (int) $value;
            }
        }

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
                                        nama_cabang, tipe_cabang, lat_cabang, long_cabang
                                        FROM tbcabang
                                        WHERE kode_cabang='$kd_cabang'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';
        $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';
        $lat_cabang = $tm_cari ? $tm_cari['lat_cabang'] : '';
        $long_cabang = $tm_cari ? $tm_cari['long_cabang'] : '';
    // --------------------

		$no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : '';
    $txtcaribrg=mysqli_real_escape_string($koneksi, $_GET['kd'] ?? '');
    $txtcarisrv=mysqli_real_escape_string($koneksi, $_GET['kdjasa'] ?? '');
    $txtcariwo=mysqli_real_escape_string($koneksi, $_GET['kdwo'] ?? '');

    // Fallback if index.php passes no_service instead of snoserv
    if (empty($no_service) && isset($_GET['no_service'])) { $no_service = $_GET['no_service']; }
    // Guard: redirect to RST page if service already finished/paid
    if (!empty($no_service)) {
        $__ns = mysqli_real_escape_string($koneksi, $no_service);
        $__q = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='".$__ns."' LIMIT 1");
        if ($__q && ($__r = mysqli_fetch_assoc($__q))) {
            $__st = strtolower($__r['status_servis'] ?? '');
            if ($__st === 'selesai' || $__st === 'bayar') {
                $__redir = 'servis-input-reguler-jemput-rst.php?snoserv=' . urlencode($no_service);
                if (isset($_GET['tab']) && $_GET['tab'] !== '') { $__redir .= '&tab=' . urlencode($_GET['tab']); }
                header('Location: ' . $__redir);
                exit;
            }
        }
    }
    // Set active tab based on URL parameter or search parameters
    $active_tab = 'service-details'; // Default tab

    // Priority 0: Check POST parameter (HIGHEST PRIORITY - preserves state after form submission)
    // Fix for tab state loss after form submission
    if (isset($_POST['current_tab']) && !empty($_POST['current_tab'])) {
        $_GET['tab'] = $_POST['current_tab'];
    }

    // Priority 1: Check URL parameter (HIGHEST PRIORITY - preserves user's tab selection)
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
            case 'pickup':
                $active_tab = 'pickup-details';
                break;
            case 'temuan':
                $active_tab = 'temuan-penawaran';
                break;
            case 'actions':
                $active_tab = 'service-actions';
                break;
            default:
                $active_tab = 'service-details';
        }
    }
    // Priority 2: Check search parameters ONLY if no URL tab parameter AND it's a fresh search
    // This prevents automatic tab switching when returning from form submission
    elseif (!empty($txtcaribrg) && !isset($_POST['btnadd']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'service-items'; // Item Barang tab
    } elseif (!empty($txtcarisrv) && !isset($_POST['btnaddsrv']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'service-jasa'; // Item Jasa tab
    } elseif (!empty($txtcariwo) && !isset($_POST['btnaddworkorder']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'workorder-details'; // Work Order tab
    }

    // Initialize display variables
    $txtnamawo = '';

    // Get work order data if searching for work order
    if(!empty($txtcariwo)) {
        $cari_wo = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcariwo'");
        $tm_wo = mysqli_fetch_array($cari_wo);
        $txtnamawo = $tm_wo['nama_wo'] ?? '';
    }

    // === AUTO-ADD PROMO PACKET LOGIC ===
    if(!empty($no_service) && empty($_POST)) { // Check empty POST to run only on GET/Load
        // Use 'no_polisi_item' from query above, or fetch it here if not available yet
        $q_svc_temp = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service'");
        $svc_temp = mysqli_fetch_array($q_svc_temp);
        $nopol_ver = $svc_temp['no_polisi'] ?? '';

        if(isset($_SESSION['service_discount']) && 
           $_SESSION['service_discount']['apply_discount'] == 1 && 
           !isset($_SESSION['service_discount']['auto_add_done']) &&
           $_SESSION['service_discount']['nopol'] == $nopol_ver
        ) {
            $sess_disc = $_SESSION['service_discount'];
            
            // Only process if it's a periode promo (packets usually are)
            if($sess_disc['discount_type'] == 'periode' && !empty($sess_disc['discount_data']['promo_id'])) {
                 $promo_id = intval($sess_disc['discount_data']['promo_id']);
                 
                 // Get promo details
                 $q_promo = mysqli_query($koneksi, "SELECT * FROM master_diskon_periode WHERE id_promo='$promo_id'");
                 if($q_promo && mysqli_num_rows($q_promo) > 0) {
                     $promo = mysqli_fetch_assoc($q_promo);
                     
                     // Handle Work Order Packet
                     if($promo['target_type'] == 'workorder') {
                         $kode_wo_list = explode(',', $promo['target_id']);
                         $items_added = 0;
                         
                         foreach($kode_wo_list as $kode_wo) {
                             $kode_wo = trim($kode_wo);
                             if(empty($kode_wo)) continue;
                             
                             // Re-use logic to add Work Order
                             // 1. Check/Insert to SPK
                             $chk_spk = mysqli_query($koneksi, "SELECT id FROM tbservis_workorder WHERE no_service='$no_service' AND kode_wo='$kode_wo'");
                             if(mysqli_num_rows($chk_spk) == 0) {
                                 mysqli_query($koneksi, "INSERT INTO tbservis_workorder (no_service, kode_wo, status_pengerjaan) VALUES ('$no_service', '$kode_wo', 'diproses')");
                                 $wo_id = mysqli_insert_id($koneksi);
                                 
                                 // 2. Insert to Pending Items from Workorder Details
                                 $q_det = mysqli_query($koneksi, "SELECT * FROM tbworkorderdetail WHERE kode_wo='$kode_wo'");
                                 while($det = mysqli_fetch_assoc($q_det)) {
                                     $kode_item = $det['kode_barang'];
                                     $tipe = ($det['tipe'] == '1') ? 'jasa' : 'barang';
                                     
                                     // Get Item Name
                                     $nama_item = '';
                                     if($tipe == 'jasa') {
                                         $q_i = mysqli_query($koneksi, "SELECT namaitem FROM tblitem_jasa WHERE noitem='$kode_item'");
                                         if($r_i = mysqli_fetch_array($q_i)) { $nama_item = $r_i['namaitem']; }
                                         else { $nama_item = 'Jasa '.$kode_item; }
                                     } else {
                                         $q_i = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='$kode_item'");
                                         if($r_i = mysqli_fetch_array($q_i)) { $nama_item = $r_i['namaitem']; }
                                         else { $nama_item = 'Barang '.$kode_item; }
                                     }
                                     
                                     // Insert to Pending
                                     $sql_ins = "INSERT INTO tbservis_pending_items 
                                        (no_service, wo_id, kode_item, nama_item, tipe, quantity, harga_satuan, total, status_approval)
                                        VALUES
                                        ('$no_service', '$wo_id', '$kode_item', '".mysqli_real_escape_string($koneksi, $nama_item)."', '$tipe', 
                                         '{$det['jumlah']}', '{$det['harga']}', '{$det['total']}', 'disetujui')"; // Directly approved
                                     mysqli_query($koneksi, $sql_ins);
                                     
                                     // 3. Insert to Real Table (tblservis_barang / jasa)
                                     
                                     // CHECK EXCLUSION (Setting Diskon Member Item)
                                     // Even for Promo/Packet, user wants exclusion to apply if set.
                                     $is_excluded = false;
                                     // Check tblitem (source of truth for settings page)
                                     $q_ex = mysqli_query($koneksi, "SELECT i.exclude_diskon_member, h.exclude_diskon_member as kind_ex 
                                                                     FROM tblitem i 
                                                                     LEFT JOIN tbhargajual h ON i.jenis = h.jenis
                                                                     WHERE i.noitem = '$kode_item'");
                                     if($q_ex && mysqli_num_rows($q_ex) > 0) {
                                         $row_ex = mysqli_fetch_assoc($q_ex);
                                         if(!is_null($row_ex['exclude_diskon_member'])) {
                                             if($row_ex['exclude_diskon_member'] == 1) $is_excluded = true;
                                         } else {
                                             if(($row_ex['kind_ex'] ?? 0) == 1) $is_excluded = true;
                                         }
                                     }
                                     
                                     // Calculate specific discount for this item from the promo
                                     $diskon_persen = 0; $diskon_nominal = 0;
                                     
                                     if(!$is_excluded) {
                                         if($promo['tipe_promo'] == 'persen') {
                                             $diskon_persen = floatval($promo['nilai_promo']);
                                             $diskon_nominal = $det['harga'] * ($diskon_persen / 100);
                                         } else {
                                              // Nominal for packet logic
                                              $diskon_nominal = floatval($promo['nilai_promo']); 
                                              if($det['harga'] > 0) $diskon_persen = ($diskon_nominal / $det['harga']) * 100;
                                         }
                                     }
                                     
                                     $subtotal = ($det['harga'] * $det['jumlah']) - ($diskon_nominal * $det['jumlah']);
                                     
                                     // STORE PERCENTAGE IN 'potongan' COLUMN to match legacy/manual add behavior
                                     // 'diskon_nominal' stores the per-unit rupiah discount
                                     
                                     if($tipe == 'barang') {
                                         $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_barang WHERE no_service='$no_service'");
                                         $nb = mysqli_fetch_assoc($q_nb)['n'];
                                         
                                         mysqli_query($koneksi, "INSERT INTO tblservis_barang 
                                            (no_service, nobaris, no_item, quantity, harga_jual, potongan, total, 
                                             diskon_source, diskon_persen, diskon_nominal, id_promo)
                                            VALUES
                                            ('$no_service', '$nb', '$kode_item', '{$det['jumlah']}', '{$det['harga']}', 
                                             '$diskon_persen', '$subtotal', 'promo', '$diskon_persen', '$diskon_nominal', '$promo_id')");
                                     } else {
                                          $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_jasa WHERE no_service='$no_service'");
                                          $nb = mysqli_fetch_assoc($q_nb)['n'];
                                          
                                          mysqli_query($koneksi, "INSERT INTO tblservis_jasa 
                                            (no_service, nobaris, no_item, harga, potongan, total, 
                                             diskon_source, diskon_persen, diskon_nominal, id_promo)
                                            VALUES
                                            ('$no_service', '$nb', '$kode_item', '{$det['harga']}', 
                                             '$diskon_persen', '$subtotal', 'promo', '$diskon_persen', '$diskon_nominal', '$promo_id')");
                                     }
                                     $items_added++;
                                 }
                             }
                         }
                         
                         if($items_added > 0) {
                             echo "<script>alert('Paket Promo \"".addslashes($promo['nama_promo'])."\" berhasil ditambahkan otomatis!');</script>";
                         }
                     }
                 }
            }
            
            // Mark as done so it doesn't run again on refresh
            $_SESSION['service_discount']['auto_add_done'] = true;
        }
    }

    // Handler untuk update mechanic data
    if(isset($_POST['btnupdatemekanik'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
            $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
            $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

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

            // Validate percentage sum
            $total_persen = $persen_kepala1 + $persen_kepala2 + $persen_admin1 + $persen_admin2 +
                           $persen_mekanik1 + $persen_mekanik2 + $persen_mekanik3 + $persen_mekanik4;

            if($total_persen > 100) {
                echo "<script>alert('Error: Total persentase tidak boleh lebih dari 100%! Total saat ini: $total_persen%'); window.history.back();</script>";
                exit;
            }

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
                echo "<script>
                    alert('Data mekanik berhasil diupdate!');
                    var params = {
                        'snoserv': '$no_service',
                        'kd': '$txtcaribrg',
                        'kdjasa': '$txtcarisrv',
                        'kdwo': '$txtcariwo'
                    };
                    // Preserve current tab
                    var urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('tab')) {
                        params.tab = urlParams.get('tab');
                    }
                    window.location = 'servis-input-reguler-jemput.php?' + new URLSearchParams(params).toString();
                </script>";
            } else {
                echo "<script>alert('Error update data mekanik: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Handler untuk tambah keluhan
    if(isset($_POST['btnaddkeluhan'])) {
        $no_service = $_POST['txtnosrv'];
        $txtkeluhan = $_POST['txtkeluhan'];
        $kode_keluhan = $_POST['kode_keluhan'] ?? '';

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        // Validate required fields
        if(empty($no_service)) {
            echo"<script>alert('Error: No service tidak valid!'); window.history.back();</script>";
            exit;
        }

        if(empty($txtkeluhan) || trim($txtkeluhan) == '') {
            echo"<script>alert('Error: Keluhan tidak boleh kosong!'); window.history.back();</script>";
            exit;
        }

        // Escape input to prevent SQL injection
        $no_service = mysqli_real_escape_string($koneksi, $no_service);
        $txtkeluhan = mysqli_real_escape_string($koneksi, trim($txtkeluhan));
        $kode_keluhan_sql = !empty($kode_keluhan) ? "'" . mysqli_real_escape_string($koneksi, $kode_keluhan) . "'" : "NULL";

        $result = mysqli_query($koneksi,"INSERT INTO tbservis_keluhan_status
                        (no_service, keluhan, kode_keluhan, status_pengerjaan)
                        VALUES
                        ('$no_service','$txtkeluhan',$kode_keluhan_sql,'datang')");

        if($result) {
            echo "<script>
                alert('Keluhan berhasil ditambahkan ke SPK!');
                window.location='servis-input-reguler-jemput.php?snoserv=$no_service&tab=workorder';
            </script>";
        } else {
            echo"<script>alert('Error: Gagal menambahkan keluhan! " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
    }

    // Handler untuk update status keluhan
    if(isset($_POST['btnupdatestatuskeluhan'])) {
        $keluhan_id = intval($_POST['keluhan_id']);
        $status_keluhan = mysqli_real_escape_string($koneksi, $_POST['status_keluhan']);
        $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan_keluhan'] ?? '');

        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
        $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

        // Validate allowed status values
        $allowed_status = ['datang', 'diproses', 'selesai', 'tidak_selesai'];
        if(!in_array($status_keluhan, $allowed_status)) {
            echo "<script>alert('Error: Status tidak valid!'); window.history.back();</script>";
            exit;
        }

        mysqli_query($koneksi,"UPDATE tbservis_keluhan_status
                               SET status_pengerjaan='$status_keluhan',
                                   keterangan_tidak_selesai='$keterangan'
                               WHERE id='$keluhan_id'");

        echo "<script>
            alert('Status keluhan berhasil diupdate!');
            window.location='servis-input-reguler-jemput.php?snoserv=$no_service&tab=workorder';
        </script>";
    }

    // Handler untuk update keluhan dan catatan di tblservice
    if(isset($_POST['btnupdatekeluhan'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $no_service = mysqli_real_escape_string($koneksi, $no_service);
            $keluhan = mysqli_real_escape_string($koneksi, $_POST['keluhan'] ?? '');
            $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');

            $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
            $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
            $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

            // Update complaint and notes in tblservice
            $update_keluhan = "UPDATE tblservice SET
                keluhan='$keluhan',
                catatan='$catatan',
                updated_at=NOW()
                WHERE no_service='$no_service'";

            if(mysqli_query($koneksi, $update_keluhan)) {
                echo "<script>
                    alert('Keluhan dan catatan berhasil diupdate!');
                    window.location='servis-input-reguler-jemput.php?snoserv=$no_service&tab=service-details';
                </script>";
            } else {
                echo "<script>alert('Error update keluhan: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Handler untuk auto-add tarif jemput ke Item Jasa
    if(isset($_POST['btnaddtarifjemput'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            // Get tarif jemput from tblservice
            $query_get_tarif = mysqli_query($koneksi, "SELECT tarif_jemput, jarak_jemput, kondisi_motor FROM tblservice WHERE no_service='$no_service'");
            if($tarif_data = mysqli_fetch_array($query_get_tarif)) {
                $tarif_jemput = $tarif_data['tarif_jemput'] ?? 0;
                $jarak_jemput = $tarif_data['jarak_jemput'] ?? 0;
                $kondisi_motor = $tarif_data['kondisi_motor'] ?? 'jalan';

                if($tarif_jemput > 0) {
                    // Check if already exists
                    $check_existing = mysqli_query($koneksi, "SELECT id FROM tblservis_jasa WHERE no_service='$no_service' AND no_item='JEMPUT-ANTAR'");
                    if(mysqli_num_rows($check_existing) == 0) {
                        // Insert tarif jemput as jasa item
                        $kondisi_text = $kondisi_motor == 'mogok' ? 'Mogok' : 'Jalan';
                        $nama_jasa = "JEMPUT ANTAR MOTOR ($jarak_jemput KM - Motor $kondisi_text)";

                        // Get next nobaris
                        $query_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                        $nobaris_data = mysqli_fetch_array($query_nobaris);
                        $next_nobaris = $nobaris_data['next_nobaris'] ?? 1;

                        // Insert to tblservis_jasa
                        mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                                (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                                VALUES
                                                ('$no_service', '$next_nobaris', 'JEMPUT-ANTAR', '$tarif_jemput', '0', '0', '$tarif_jemput')");

                        echo "<script>
                            alert('Tarif jemput antar berhasil ditambahkan ke Item Jasa!');
                            window.location='servis-input-reguler-jemput.php?snoserv=$no_service';
                        </script>";
                    } else {
                        echo "<script>
                            alert('Tarif jemput antar sudah ada di Item Jasa!');
                            window.location='servis-input-reguler-jemput.php?snoserv=$no_service';
                        </script>";
                    }
                } else {
                    echo "<script>
                        alert('Tarif jemput belum dihitung atau jarak kurang dari 1 KM (gratis)!');
                        window.history.back();
                    </script>";
                }
            }
        }
    }

    // Handler untuk Cari Item Barang
    if(isset($_POST['btncari'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

        // Redirect to item search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jemput-item-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcaribrg) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=items&_from=jemput');</script>";
        exit;
    }

    // Handler untuk Cari Item Jasa
    if(isset($_POST['btncarisrv'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');

        // Redirect to jasa search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jemput-jasa-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcarisrv) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=jasa&_from=jemput');</script>";
        exit;
    }

    // ========== HANDLER: HAPUS ITEM BARANG/JASA (JEMPUT) ==========
    if (!empty($no_service) && (isset($_GET['hapus_brg']) || isset($_GET['hapus_srv']))) {
        $no_srv_esc  = mysqli_real_escape_string($koneksi, $no_service);
        $chk_st      = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_srv_esc' LIMIT 1");
        $row_st      = mysqli_fetch_assoc($chk_st);
        $sudah_tutup = $row_st && in_array($row_st['status_servis'], ['bayar', 'selesai']);

        if (isset($_GET['hapus_brg'])) {
            $id_hapus = (int)$_GET['hapus_brg'];
            if ($sudah_tutup) {
                echo "<script>alert('Item tidak bisa dihapus — servis sudah " . strtoupper($row_st['status_servis']) . ".');history.back();</script>"; exit;
            }
            mysqli_query($koneksi, "DELETE FROM tblservis_barang WHERE id=$id_hapus AND no_service='$no_srv_esc'");
            header('Location: servis-input-reguler-jemput.php?snoserv=' . urlencode($no_service) . '&tab=items');
            exit;
        }
        if (isset($_GET['hapus_srv'])) {
            $id_hapus = (int)$_GET['hapus_srv'];
            if ($sudah_tutup) {
                echo "<script>alert('Jasa tidak bisa dihapus — servis sudah " . strtoupper($row_st['status_servis']) . ".');history.back();</script>"; exit;
            }
            mysqli_query($koneksi, "DELETE FROM tblservis_jasa WHERE id=$id_hapus AND no_service='$no_srv_esc'");
            header('Location: servis-input-reguler-jemput.php?snoserv=' . urlencode($no_service) . '&tab=jasa');
            exit;
        }
    }

    // Handler untuk Tambah Item Barang
    if(isset($_POST['btnadd'])) {
        // Sanitize and validate input
        $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
        $txtkdbarang = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
        $txtqty = intval($_POST['txtqty'] ?? 0);
        $txtpot = floatval($_POST['txtpot'] ?? 0);
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
        $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

        if(!empty($txtkdbarang) && $txtqty > 0) {
            // ✅ CRITICAL FIX: Check stock availability
            $cari_stok = mysqli_query($koneksi, "SELECT saldo
                                                 FROM view_stok_master
                                                 WHERE kd_cabang='$kd_cabang' AND no_item='$txtkdbarang'");

            if(!$cari_stok || mysqli_num_rows($cari_stok) == 0) {
                echo "<script>alert('Item tidak ditemukan di stok cabang ini!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            $stok_data = mysqli_fetch_array($cari_stok);
            $stok_akhir = intval($stok_data['saldo']);

            // Validate stock availability
            if($stok_akhir <= 0) {
                echo "<script>alert('Stok barang kosong!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            if($txtqty > $stok_akhir) {
                echo "<script>alert('Stok barang tidak mencukupi! Stok tersedia: $stok_akhir'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            // Check if item already exists
            $check_existing = mysqli_query($koneksi, "SELECT id FROM tblservis_barang WHERE no_service='$no_service' AND no_item='$txtkdbarang'");

            if(mysqli_num_rows($check_existing) > 0) {
                echo "<script>alert('Item barang sudah ada!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            // Get item price with tiered pricing
            $cari_harga = mysqli_query($koneksi, "SELECT hargajual, hargajual2, hargajual3,
                                                   hjqtys1, hjqtys2
                                                   FROM tblitem WHERE noitem='$txtkdbarang'");
            if($harga_data = mysqli_fetch_array($cari_harga)) {
                $harga_ke1 = floatval($harga_data['hargajual']);
                $harga_ke2 = floatval($harga_data['hargajual2'] ?? $harga_ke1);
                $harga_ke3 = floatval($harga_data['hargajual3'] ?? $harga_ke1);
                $qty_ke1 = intval($harga_data['hjqtys1'] ?? 1);
                $qty_ke2 = intval($harga_data['hjqtys2'] ?? 999999);

                // Determine price based on quantity (tiered pricing)
                if($txtqty <= $qty_ke1) {
                    $harga_jual = $harga_ke1;
                } elseif($txtqty <= $qty_ke2) {
                    $harga_jual = $harga_ke2;
                } else {
                    $harga_jual = $harga_ke3;
                }

                // Calculate initial subtotal
                $harga_final = $harga_jual;
                $diskon_source = '';
                $diskon_persen = 0;
                $diskon_nominal = 0;
                $id_promo = 0;

                // Try session-based discount first (from Preview Diskon)
                $applied_session_discount = false;
                if(function_exists('calculateItemDiscount')) {
                    // Get plate for session matching
                    $q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='".$no_service."'");
                    $r_cust = $q_cust ? mysqli_fetch_assoc($q_cust) : null;
                    $no_polisi_svc = $r_cust['no_polisi'] ?? '';
                    if(!empty($no_polisi_svc)) {
                        $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $txtkdbarang, 'barang', $harga_jual);
                        if(($disc['diskon_nominal'] ?? 0) > 0) {
                            $diskon_persen = floatval($disc['diskon_persen']);
                            $diskon_nominal = floatval($disc['diskon_nominal']);
                            $harga_final = $harga_jual - $diskon_nominal;
                            $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                            // If promo, capture promo_id from active session discount
                            if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                if(!empty($ad['promo_id'])) { $id_promo = $ad['promo_id']; }
                            }
                            $applied_session_discount = true;
                        }
                    }
                }

                // 1. Check Promo Periode (Priority) if no session discount applied
                $tgl_cek = date('Y-m-d');
                $q_promo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                  WHERE target_type = 'barang' 
                                                  AND (target_id = '".$txtkdbarang."' OR FIND_IN_SET('".$txtkdbarang."', target_id))
                                                  AND status_aktif = 1 
                                                  AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai 
                                                  ORDER BY nilai_promo DESC LIMIT 1");
                if(!$applied_session_discount && $q_promo && mysqli_num_rows($q_promo) > 0) {
                    $prow = mysqli_fetch_assoc($q_promo);
                    $diskon_source = 'promo';
                    $id_promo = $prow['id_promo'];
                    if(($prow['tipe_promo'] ?? '') === 'nominal') {
                        $diskon_nominal = floatval($prow['nilai_promo']);
                        $diskon_persen = ($harga_jual > 0) ? ($diskon_nominal / $harga_jual * 100) : 0;
                    } else {
                        $diskon_persen = floatval($prow['nilai_promo']);
                        $diskon_nominal = $harga_jual * ($diskon_persen / 100);
                    }
                    $harga_final = max(0, $harga_jual - $diskon_nominal);
                }

                // 2. Check Member Discount (if no promo)
                if(!$applied_session_discount && $diskon_persen == 0) { // If no promo applied
                     // Get pel properti
                    if(!empty($kode_pelanggan)) {
                        // Check exclude using helper function
                        $is_excluded = false;
                        if(function_exists('isItemExcludedFromMemberDiscount')) {
                             $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $txtkdbarang);
                        }

                        if(!$is_excluded) {
                             $mem_disc = 0;
                             if(function_exists('getMemberDiscountForItem')) {
                                 $mem_disc = getMemberDiscountForItem($koneksi, $kode_pelanggan, $txtkdbarang, 'barang');
                             }

                             if($mem_disc > 0) {
                                 $diskon_source = 'member';
                                 $diskon_persen = $mem_disc;
                                 $diskon_nominal = $harga_jual * ($diskon_persen / 100);
                                 $harga_final = $harga_jual - $diskon_nominal;
                             }
                        }
                    }
                }

                // If manual potongan entered (overrides everything or additional? Usually input manual potongan replaces calculated)
                if($txtpot > 0) {
                    $diskon_source = 'manual';
                    $diskon_persen = $txtpot; // Assuming txtpot is percent
                    $diskon_nominal = $harga_jual * ($diskon_persen / 100);
                    $harga_final = $harga_jual - $diskon_nominal;
                }

                // Calculate total
                $subtotal = $harga_final * $txtqty;

                // Get next nobaris for barang
                $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
                $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
                $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

                // Insert to tblservis_barang
                mysqli_query($koneksi, "INSERT INTO tblservis_barang
                                        (no_service, nobaris, no_item, harga_jual, quantity, qty_retur, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                        VALUES
                                        ('$no_service', '$nobaris_brg', '$txtkdbarang', '$harga_jual', '$txtqty', '0', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', '$id_promo')");

                echo "<script>window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            } else {
                echo "<script>alert('Item tidak ditemukan di master!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Kode barang dan qty harus diisi!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
            exit;
        }
    }

    // Handler untuk Tambah Item Jasa
    if(isset($_POST['btnaddsrv'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtpotsrv = $_POST['txtpotsrv'] ?? 0;
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        if(!empty($txtcarisrv)) {
            // Check if jasa already exists
            $check_existing = mysqli_query($koneksi, "SELECT id FROM tblservis_jasa WHERE no_service='$no_service' AND no_item='$txtcarisrv'");

            if(mysqli_num_rows($check_existing) > 0) {
                echo "<script>alert('Item jasa sudah ada!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo';</script>";
                exit;
            }

            // Get work order details
            $cari_wo = mysqli_query($koneksi, "SELECT harga, waktu FROM tbworkorderheader WHERE kode_wo='$txtcarisrv'");
            if($wo_data = mysqli_fetch_array($cari_wo)) {
                $harga = $wo_data['harga'];
                $waktu = $wo_data['waktu'] ?? 0;

                // Calculate Discount Logic
                $harga_final = $harga;
                $diskon_source = '';
                $diskon_persen = 0;
                $diskon_nominal = 0;
                $id_promo = 0;

                // Try session-based discount first (from Preview Diskon)
                $applied_session_discount = false;
                if(function_exists('calculateItemDiscount')) {
                    $q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='".$no_service."'");
                    $r_cust = $q_cust ? mysqli_fetch_assoc($q_cust) : null;
                    $no_polisi_svc = $r_cust['no_polisi'] ?? '';
                    if(!empty($no_polisi_svc)) {
                        $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $txtcarisrv, 'jasa', $harga);
                        if(($disc['diskon_nominal'] ?? 0) > 0) {
                            $diskon_persen = floatval($disc['diskon_persen']);
                            $diskon_nominal = floatval($disc['diskon_nominal']);
                            $harga_final = $harga - $diskon_nominal;
                            $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                            if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                if(!empty($ad['promo_id'])) { $id_promo = $ad['promo_id']; }
                            }
                            $applied_session_discount = true;
                        }
                    }
                }

                // 1. Check Promo Periode (Priority) if no session discount applied
                $tgl_cek = date('Y-m-d');
                $q_promo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                  WHERE target_type = 'jasa' 
                                                  AND (target_id = '".$txtcarisrv."' OR FIND_IN_SET('".$txtcarisrv."', target_id))
                                                  AND status_aktif = 1 
                                                  AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai 
                                                  ORDER BY nilai_promo DESC LIMIT 1");
                if(!$applied_session_discount && $q_promo && mysqli_num_rows($q_promo) > 0) {
                    $prow = mysqli_fetch_assoc($q_promo);
                    $diskon_source = 'promo';
                    $id_promo = $prow['id_promo'];
                    if(($prow['tipe_promo'] ?? '') === 'nominal') {
                        $diskon_nominal = floatval($prow['nilai_promo']);
                        $diskon_persen = ($harga > 0) ? ($diskon_nominal / $harga * 100) : 0;
                    } else {
                        $diskon_persen = floatval($prow['nilai_promo']);
                        $diskon_nominal = $harga * ($diskon_persen / 100);
                    }
                    $harga_final = max(0, $harga - $diskon_nominal);
                }

                // 2. Check Member Discount for Service
                if(!$applied_session_discount && $diskon_persen == 0) {
                     if(!empty($kode_pelanggan)) {
                         // Check exclude (assuming services can be excluded too)
                         $is_excluded = false;
                         if(function_exists('isItemExcludedFromMemberDiscount')) {
                             $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $txtcarisrv);
                         }

                         if(!$is_excluded) {
                             $mem_disc = 0;
                             if(function_exists('getMemberDiscountForItem')) {
                                 // Assuming 'jasa' type for services
                                 $mem_disc = getMemberDiscountForItem($koneksi, $kode_pelanggan, $txtcarisrv, 'jasa');
                             }

                             if($mem_disc > 0) {
                                 $diskon_source = 'member';
                                 $diskon_persen = $mem_disc;
                                 $diskon_nominal = $harga * ($diskon_persen / 100);
                                 $harga_final = $harga - $diskon_nominal;
                             }
                         }
                     }
                }

                // Manual Potongan override
                if($txtpotsrv > 0) {
                    $diskon_source = 'manual';
                    $diskon_persen = $txtpotsrv;
                    $diskon_nominal = $harga * ($diskon_persen / 100);
                    $harga_final = $harga - $diskon_nominal;
                }

                // Calculate total
                $subtotal = $harga_final; // Qty always 1 for jasa usually, or if needed multiply by quantity if schema supports it (here schema seems implied 1 or from wo)

                // Get next nobaris
                $query_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                $nobaris_data = mysqli_fetch_array($query_nobaris);
                $next_nobaris = $nobaris_data['next_nobaris'] ?? 1;

                $keterangan_jasa = mysqli_real_escape_string($koneksi, $_POST['keterangan_jasa'] ?? '');

                // Insert to tblservis_jasa
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                        (no_service, nobaris, no_item, harga, waktu, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo, keterangan)
                                        VALUES
                                        ('$no_service', '$next_nobaris', '$txtcarisrv', '$harga', '$waktu', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', '$id_promo', '$keterangan_jasa')");

                echo "<script>window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo';</script>";
                exit;
            } else {
                echo "<script>alert('Jasa tidak ditemukan!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Kode jasa harus diisi!'); window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
            exit;
        }
    }

    // Handler untuk submit form
    if(isset($_POST['btnsimpan']) && empty($no_service)) {
        // Generate nomor service jika belum ada
        if(empty($no_service)) {
            $tanggal_service = date('Y-m-d');

            // Generate nomor service untuk jemput
            $prefix_service = 'JMP-' . date('Ymd') . '-';
            $query_last_service = "SELECT no_service FROM tblservice WHERE no_service LIKE '$prefix_service%' ORDER BY no_service DESC LIMIT 1";
            $result_last_service = mysqli_query($koneksi, $query_last_service);

            if(mysqli_num_rows($result_last_service) > 0) {
                $last_service = mysqli_fetch_array($result_last_service)['no_service'];
                $last_number = intval(substr($last_service, -4));
                $new_number = $last_number + 1;
            } else {
                $new_number = 1;
            }

            $no_service = $prefix_service . str_pad($new_number, 4, '0', STR_PAD_LEFT);

            // Generate nomor antrian
            $query_antrian_count = "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_service'";
            $result_antrian_count = mysqli_query($koneksi, $query_antrian_count);
            $antrian_count = mysqli_fetch_array($result_antrian_count)['total'];
            $no_antrian = $antrian_count + 1;

            // Insert data service jemput
            $tanggal_input = $_POST['id-date-picker-1'] ?? date('d/m/Y');
            $tanggal_service = date('Y-m-d', strtotime(str_replace('/', '-', $tanggal_input)));
            $jam_input = $_POST['jam_service'] ?? date('H:i');
            $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
            $no_polisi = $_POST['no_polisi'] ?? '';
            $keluhan = $_POST['keluhan'] ?? '';

            $keluhan_esc = mysqli_real_escape_string($koneksi, $keluhan);
            $query_insert_service = "INSERT INTO tblservice (
                no_service, tanggal, jam, no_pelanggan, no_polisi, kd_cabang, id_user,
                status, status_servis, status_jemput, keterangan, created_at
            ) VALUES (
                '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan',
                '$no_polisi', '$kd_cabang', '$id_user',
                '1', 'datang', '1', '$keluhan_esc', NOW()
            )";

            if(mysqli_query($koneksi, $query_insert_service)) {
                // Insert ke tabel antrian
                $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                    no_service, no_antrian, tanggal, jam_ambil,
                    status_antrian, prioritas, created_at
                ) VALUES (
                    '$no_service', '$no_antrian', '$tanggal_service', '$jam_input',
                    'menunggu', 'normal', NOW()
                )";

                if(mysqli_query($koneksi, $query_insert_antrian)) {
                    echo "<script>
                        alert('Service Jemput berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian');
                        window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service';
                    </script>";
                }
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Add Work Order
    if(isset($_POST['btnaddworkorder'])) {
        $no_service = $_POST['txtnosrv'];
        $kode_wo = $_POST['txtcariwo'];

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        // Validate required fields
        if(empty($no_service)) {
            echo"<script>alert('Error: No service tidak valid!'); window.history.back();</script>";
            exit;
        }

        if(empty($kode_wo) || trim($kode_wo) == '') {
            echo"<script>alert('Error: Kode Work Order tidak boleh kosong!'); window.history.back();</script>";
            exit;
        }

        // Escape input to prevent SQL injection
        $no_service = mysqli_real_escape_string($koneksi, $no_service);
        $kode_wo = mysqli_real_escape_string($koneksi, trim($kode_wo));

        // Verify work order exists in master
        $check_wo_master = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
        if(mysqli_num_rows($check_wo_master) == 0) {
            echo"<script>alert('Error: Work Order dengan kode \"$kode_wo\" tidak ditemukan di master!'); window.history.back();</script>";
            exit;
        }

        // Check if work order already exists in service
        $check_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbservis_workorder
                                           WHERE no_service='$no_service' AND kode_wo='$kode_wo'");
        $check_result = mysqli_fetch_array($check_wo);

        if($check_result['count'] > 0) {
            echo"<script>alert('Work Order \"$kode_wo\" sudah ditambahkan ke SPK ini!'); window.history.back();</script>";
            exit;
        }

        // Insert work order
        $result = mysqli_query($koneksi,"INSERT INTO tbservis_workorder
                                  (no_service, kode_wo, status_pengerjaan)
                                  VALUES
                                  ('$no_service','$kode_wo','diproses')");

        if(!$result) {
            echo"<script>alert('Error: Gagal menambahkan Work Order! " . mysqli_error($koneksi) . "'); window.history.back();</script>";
            exit;
        }

        // PERUBAHAN: Item workorder masuk ke PENDING untuk perlu approval (Temuan & Penawaran)
        $wo_id = mysqli_insert_id($koneksi);

        // Ambil detail WO
        $detail_wo = mysqli_query($koneksi, "SELECT kode_barang, tipe, harga, total, jumlah
                                             FROM tbworkorderdetail
                                             WHERE kode_wo='$kode_wo'");
        $detail_count = ($detail_wo ? mysqli_num_rows($detail_wo) : 0);

        // Fallback: variasi kode WO (WO1, WO01, WO001, dst) jika tidak ditemukan
        if ($detail_count === 0 && preg_match('/^WO0*(\\d+)$/', $kode_wo, $m)) {
            $wo_num = $m[1];
            $variants = array();
            $variants[] = 'WO' . $wo_num; // unpadded
            for ($pad = 2; $pad <= 5; $pad++) {
                $variants[] = 'WO' . str_pad($wo_num, $pad, '0', STR_PAD_LEFT);
            }
            $variants[] = $kode_wo; // original
            $variants = array_values(array_unique($variants));

            $in_list = array();
            foreach ($variants as $v) { $in_list[] = "'" . mysqli_real_escape_string($koneksi, $v) . "'"; }
            $in_sql = implode(',', $in_list);

            $detail_wo = mysqli_query($koneksi, "SELECT kode_barang, tipe, harga, total, jumlah
                                                 FROM tbworkorderdetail
                                                 WHERE kode_wo IN ($in_sql)");
            $detail_count = ($detail_wo ? mysqli_num_rows($detail_wo) : 0);
        }

        $total_items_added = 0;
        if ($detail_wo) {
            while ($detail = mysqli_fetch_array($detail_wo)) {
                $kode_item = $detail['kode_barang'];
                $quantity = $detail['jumlah'];
                $harga_satuan = $detail['harga'];
                $total = $detail['total'];
                $waktu = 0; // default, dapat diisi saat approve jika perlu

                // Nama item untuk display
                $nama_item = '';
                if ($detail['tipe'] == '1') {
                    // Jasa - coba di tblitem_jasa, fallback ke tblitem
                    $q_item = @mysqli_query($koneksi, "SELECT namaitem FROM tblitem_jasa WHERE noitem='" . mysqli_real_escape_string($koneksi, $kode_item) . "'");
                    if ($q_item && mysqli_num_rows($q_item) > 0) {
                        $item_data = mysqli_fetch_array($q_item);
                        $nama_item = $item_data['namaitem'];
                    } else {
                        $q_item2 = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='" . mysqli_real_escape_string($koneksi, $kode_item) . "'");
                        if ($q_item2 && mysqli_num_rows($q_item2) > 0) {
                            $item_data = mysqli_fetch_array($q_item2);
                            $nama_item = $item_data['namaitem'];
                        } else {
                            $nama_item = 'Jasa ' . $kode_item;
                        }
                    }
                } else {
                    // Barang
                    $q_item = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='" . mysqli_real_escape_string($koneksi, $kode_item) . "'");
                    if ($q_item && mysqli_num_rows($q_item) > 0) {
                        $item_data = mysqli_fetch_array($q_item);
                        $nama_item = $item_data['namaitem'];
                    } else {
                        $nama_item = 'Part ' . $kode_item;
                    }
                }

                $nama_item = mysqli_real_escape_string($koneksi, $nama_item);

                // Cek duplikat pending
                $check_pending = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbservis_pending_items
                                                         WHERE no_service='$no_service'
                                                           AND kode_item='" . mysqli_real_escape_string($koneksi, $kode_item) . "'
                                                           AND status_approval='pending'");
                $check_result = ($check_pending ? mysqli_fetch_array($check_pending) : ['count' => 0]);

                if (($check_result['count'] ?? 0) == 0) {
                    $tipe_item = ($detail['tipe'] == '1') ? 'jasa' : 'barang';
                    $wo_id_value = ($wo_id && $wo_id > 0) ? "'$wo_id'" : "NULL";

                    $sql_insert_pending = "INSERT INTO tbservis_pending_items
                                           (no_service, wo_id, kode_item, nama_item, tipe, quantity,
                                            harga_satuan, total, waktu, status_approval)
                                           VALUES
                                           ('$no_service', $wo_id_value, '" . mysqli_real_escape_string($koneksi, $kode_item) . "', '$nama_item',
                                            '$tipe_item', '" . mysqli_real_escape_string($koneksi, $quantity) . "', '" . mysqli_real_escape_string($koneksi, $harga_satuan) . "', '" . mysqli_real_escape_string($koneksi, $total) . "',
                                            '$waktu', 'pending')";

                    $insert_result = mysqli_query($koneksi, $sql_insert_pending);
                    if ($insert_result) {
                        $total_items_added++;
                    } else {
                        error_log("ERROR INSERT PENDING ITEM (jemput): " . mysqli_error($koneksi) . " | Query: " . $sql_insert_pending);
                    }
                }
            }
        }

        // Update KM data pada service
        mysqli_query($koneksi, "UPDATE tblservice SET km_skr='" . mysqli_real_escape_string($koneksi, $km_skr) . "', km_berikut='" . mysqli_real_escape_string($koneksi, $km_berikut) . "' WHERE no_service='$no_service'");

            // ====================================================================
            // CONDITIONAL AUTO-APPROVE:
            // DISABLED AS PER USER REQUEST for MANUAL INPUT
            // Manual Input di Tab Workorder -> SELALU PENDING
            // ====================================================================
            $should_auto_approve = false;
            
            // Cek session diskon - hanya auto-approve jika session valid DAN BUKAN manual input
            // Asumsi: btnaddworkorder adalah manual input dari tab Work Order
            // Jika logic auto-approve dibutuhkan untuk flow lain (misal dari booking), 
            // perlu flag tambahan. Saat ini untuk btnaddworkorder kita paksa FALSE.
            
            if($should_auto_approve) {
            // AUTO-APPROVE: langsung masukkan semua pending item WO ke tblservis_barang/jasa dengan diskon
            $auto_barang = 0; $auto_jasa = 0; $auto_approved = 0;
            $tgl_cek = date('Y-m-d');
            $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."' LIMIT 1");
            $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
            $no_polisi_svc = $svc['no_polisi'] ?? '';

            // ROBUST WO PROMO LOOKUP LOGIC


            // ROBUST WO PROMO LOOKUP LOGIC
            // Generate SQL condition to match user input against promo target_id (handling formats like WO 001, WO001, WO1)
            $wo_variants = [$kode_wo];
            $clean_wo = strtoupper(str_replace(' ', '', $kode_wo));
            $wo_variants[] = $clean_wo;
            if (preg_match('/^WO0*(\d+)$/i', $clean_wo, $m)) {
                $num = $m[1];
                for ($p=1; $p<=5; $p++) $wo_variants[] = 'WO' . str_pad($num, $p, '0', STR_PAD_LEFT);
            }
            $wo_variants = array_unique($wo_variants);
            
            $wo_sql_parts = [];
            foreach($wo_variants as $wv) {
                $esc = mysqli_real_escape_string($koneksi, $wv);
                $wo_sql_parts[] = "target_id = '$esc'";
                $wo_sql_parts[] = "FIND_IN_SET('$esc', target_id)";
            }
            $wo_condition_sql = implode(' OR ', $wo_sql_parts);


            $q_pending_all = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."' AND wo_id='".$wo_id."' AND status_approval='pending'");
        if($q_pending_all) {
            while($pending_data = mysqli_fetch_assoc($q_pending_all)) {
                if($pending_data['tipe'] == 'barang') {
                    // Cek duplikat
                    $cek = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."' AND no_item='".mysqli_real_escape_string($koneksi, $pending_data['kode_item'])."'");
                    $c = $cek ? (int)mysqli_fetch_assoc($cek)['c'] : 0;
                    if($c == 0) {
                        $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
                        $nb = $q_nb ? (int)mysqli_fetch_assoc($q_nb)['n'] : 1;

                        // Hitung diskon (session promo/member)
                        $diskon_source = '';
                        $diskon_persen = 0; $diskon_nominal = 0; $id_promo = 'NULL';
                        if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                            $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'barang', floatval($pending_data['harga_satuan']));
                            if(($disc['diskon_nominal'] ?? 0) > 0) {
                                $diskon_persen = floatval($disc['diskon_persen']);
                                $diskon_nominal = floatval($disc['diskon_nominal']);
                                $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                    $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                    if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                }
                            }
                        }

                        // Cek promo WO dan ambil yang lebih besar
                        // Cek promo WO dan ambil yang lebih besar (Use Robust Condition)
                        $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode WHERE target_type='workorder' AND ($wo_condition_sql) AND status_aktif=1 AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY nilai_promo DESC LIMIT 1");
                        if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                            $harga_tmp = floatval($pending_data['harga_satuan']);
                            $wo_persen = 0; $wo_nominal = 0;
                            if(($prow['tipe_promo'] ?? '') === 'nominal') { $wo_nominal = floatval($prow['nilai_promo']); $wo_persen = ($harga_tmp>0)?($wo_nominal/$harga_tmp*100):0; }
                            else { $wo_persen = floatval($prow['nilai_promo']); $wo_nominal = $harga_tmp * ($wo_persen/100); }
                            if($wo_nominal > $diskon_nominal) { $diskon_nominal = $wo_nominal; $diskon_persen = $wo_persen; $diskon_source = 'promo'; $id_promo = intval($prow['id_promo']); }
                        }

                        $qty = intval($pending_data['quantity']);
                        $harga = floatval($pending_data['harga_satuan']);
                        $subtotal = ($harga * $qty) - ($diskon_nominal * $qty);
                        if($subtotal < 0) { $subtotal = 0; }

                        mysqli_query($koneksi, "INSERT INTO tblservis_barang (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('".mysqli_real_escape_string($koneksi, $no_service)."', '".$nb."', '".mysqli_real_escape_string($koneksi, $pending_data['kode_item'])."', '".$qty."', '0', '".$harga."', '".$diskon_persen."', '".$subtotal."', '".$diskon_source."', '".$diskon_persen."', '".$diskon_nominal."', $id_promo)");
                        $auto_barang++;
                    }
                } else { // jasa
                    // Cek duplikat
                    $cek = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."' AND no_item='".mysqli_real_escape_string($koneksi, $pending_data['kode_item'])."'");
                    $c = $cek ? (int)mysqli_fetch_assoc($cek)['c'] : 0;
                    if($c == 0) {
                        $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
                        $nb = $q_nb ? (int)mysqli_fetch_assoc($q_nb)['n'] : 1;

                        // Hitung diskon (session promo/member)
                        $diskon_source = '';
                        $diskon_persen = 0; $diskon_nominal = 0; $id_promo = 'NULL';
                        if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                            $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'jasa', floatval($pending_data['harga_satuan']));
                            if(($disc['diskon_nominal'] ?? 0) > 0) {
                                $diskon_persen = floatval($disc['diskon_persen']);
                                $diskon_nominal = floatval($disc['diskon_nominal']);
                                $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                    $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                    if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                }
                            }
                        }

                        // Cek promo WO dan ambil yang lebih besar
                        // Cek promo WO dan ambil yang lebih besar (Use Robust Condition)
                        $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode WHERE target_type='workorder' AND ($wo_condition_sql) AND status_aktif=1 AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY nilai_promo DESC LIMIT 1");
                        if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                            $harga_tmp = floatval($pending_data['harga_satuan']);
                            $wo_persen = 0; $wo_nominal = 0;
                            if(($prow['tipe_promo'] ?? '') === 'nominal') { $wo_nominal = floatval($prow['nilai_promo']); $wo_persen = ($harga_tmp>0)?($wo_nominal/$harga_tmp*100):0; }
                            else { $wo_persen = floatval($prow['nilai_promo']); $wo_nominal = $harga_tmp * ($wo_persen/100); }
                            if($wo_nominal > $diskon_nominal) { $diskon_nominal = $wo_nominal; $diskon_persen = $wo_persen; $diskon_source = 'promo'; $id_promo = intval($prow['id_promo']); }
                        }

                        $harga = floatval($pending_data['harga_satuan']);
                        $waktu = intval($pending_data['waktu']);
                        $subtotal = $harga - $diskon_nominal; if($subtotal < 0) { $subtotal = 0; }

                        mysqli_query($koneksi, "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, waktu, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('".mysqli_real_escape_string($koneksi, $no_service)."', '".$nb."', '".mysqli_real_escape_string($koneksi, $pending_data['kode_item'])."', '".$waktu."', '".$harga."', '".$diskon_persen."', '".$subtotal."', '".$diskon_source."', '".$diskon_persen."', '".$diskon_nominal."', $id_promo)");
                        $auto_jasa++;
                    }
                }

                // Update pending status ke disetujui
                mysqli_query($koneksi, "UPDATE tbservis_pending_items SET status_approval='disetujui', approved_by='".($_SESSION['_iduser'] ?? 'system')."', approved_at=NOW(), updated_at=NOW() WHERE id='".$pending_data['id']."'");
            }
        }

            $msg = "Work Order berhasil ditambahkan dan item otomatis masuk ke servis!";
            if($auto_barang > 0 || $auto_jasa > 0) {
                $msg .= "\\n- Ditambahkan: ".$auto_barang." barang, ".$auto_jasa." jasa.";
                $msg .= "\\nSilakan cek di tab Item Barang/Jasa.";
            }
            echo "<script>
                alert('" . addslashes($msg) . "');
                window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service&tab=items#service-items';
            </script>";
            exit;
        } else {
            // TIDAK AUTO-APPROVE: Item tetap pending, perlu approval pelanggan
            // Redirect ke Tab Temuan & Penawaran
            $msg = "Work Order berhasil ditambahkan!\\n";
            $msg .= "Item dari Work Order memerlukan persetujuan pelanggan.\\n";
            $msg .= "Silakan cek di Tab Temuan & Penawaran untuk approve/reject item.";
            echo "<script>
                alert('" . addslashes($msg) . "');
                window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service&tab=temuan#temuan-penawaran';
            </script>";
            exit;
        }
    }

    // Update Status Work Order
    if(isset($_POST['btnupdatestatuswo'])) {
        $wo_id = $_POST['wo_id'];
        $status_wo = $_POST['status_wo'];
        $keterangan = $_POST['keterangan_wo'] ?? '';

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        $update_status = "UPDATE tbservis_workorder
                         SET status_pengerjaan='$status_wo',
                             keterangan_tidak_selesai='$keterangan'
                         WHERE id='$wo_id'";

        if(mysqli_query($koneksi, $update_status)) {
            echo "<script>
                alert('Status Work Order berhasil diupdate!');
                window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
            </script>";
        } else {
            echo "<script>alert('Error update status: " . mysqli_error($koneksi) . "');</script>";
        }
    }

    // Delete Work Order
    if(isset($_POST['btndeletewo'])) {
        $wo_id = $_POST['wo_id'];
        $no_service = $_POST['txtnosrv'];

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        $delete_wo = "DELETE FROM tbservis_workorder WHERE id='$wo_id'";

        if(mysqli_query($koneksi, $delete_wo)) {
            echo "<script>
                alert('Work Order berhasil dihapus!');
                window.location='servis-input-reguler-jemput.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
            </script>";
        } else {
            echo "<script>alert('Error hapus Work Order: " . mysqli_error($koneksi) . "');</script>";
        }
    }

    // Search Work Order - Always redirect to search page for better UX
    if(isset($_POST['btncariwo'])) {
        $no_service = $_POST['txtnosrv'];
        $txtcariwo = $_POST['txtcariwo'] ?? '';
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        if(!empty($no_service)) {
            // Always redirect to workorder search page for browsing and selection
            $cbocari = "";
            $cbourut = "52";
            echo "<script>window.location='servis-add-jemput-workorder-cari.php?snoserv=$no_service&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc';</script>";
        } else {
            echo "<script>alert('Nomor service harus diisi terlebih dahulu!');</script>";
        }
    }

    // HANDLER APPROVE WORKORDER ITEM - Pindah ke tblservis_barang/jasa
    if (isset($_POST['btnapprove_wo_item'])) {
        $pending_item_id = mysqli_real_escape_string($koneksi, $_POST['pending_item_id'] ?? '');
        $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');

        if (!empty($pending_item_id)) {
            // Get pending item data
            $q_pending = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items
                                                 WHERE id='$pending_item_id'
                                                   AND no_service='$no_service_post'
                                                   AND status_approval='pending'");

            if ($q_pending && mysqli_num_rows($q_pending) > 0) {
                $pending_data = mysqli_fetch_array($q_pending);

                // Check if item already exists in barang/jasa table
                if ($pending_data['tipe'] == 'barang') {
                    $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_barang
                                                             WHERE no_service='$no_service_post'
                                                               AND no_item='" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "'");
                    $check_result = mysqli_fetch_array($check_existing);

                    if ($check_result['count'] == 0) {
                        // Get next nobaris for barang
                        $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                             FROM tblservis_barang WHERE no_service='$no_service_post'");
                        $nobaris_data = mysqli_fetch_array($q_nobaris);
                        $nobaris_barang = $nobaris_data['next_nobaris'] ?? 1;

                        // Calculate discount based on session promo/member
                        $diskon_source = '';
                        $diskon_persen = 0;
                        $diskon_nominal = 0;
                        $id_promo = 'NULL';

                        $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service_post' LIMIT 1");
                        $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
                        $no_polisi_svc = $svc['no_polisi'] ?? '';
                        if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                            $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'barang', floatval($pending_data['harga_satuan']));
                            if(($disc['diskon_nominal'] ?? 0) > 0) {
                                $diskon_persen = floatval($disc['diskon_persen']);
                                $diskon_nominal = floatval($disc['diskon_nominal']);
                                $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                    $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                    if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                }
                            }
                        }

                        // Check Workorder-level promo (target_type='workorder') and prefer if larger
                        $kode_wo_appr = '';
                        if(!empty($pending_data['wo_id'])) {
                            $qwo = mysqli_query($koneksi, "SELECT kode_wo FROM tbservis_workorder WHERE id='".intval($pending_data['wo_id'])."' LIMIT 1");
                            if($qwo && ($rwo = mysqli_fetch_assoc($qwo))) { $kode_wo_appr = $rwo['kode_wo'] ?? ''; }
                        }
                        if(!empty($kode_wo_appr)) {
                            // ROBUST MATCHING for Approval
                            $wo_variants = [$kode_wo_appr];
                            $clean_wo = strtoupper(str_replace(' ', '', $kode_wo_appr));
                            $wo_variants[] = $clean_wo;
                            if (preg_match('/^WO0*(\d+)$/i', $clean_wo, $m)) {
                                $num = $m[1];
                                for ($p=1; $p<=5; $p++) $wo_variants[] = 'WO' . str_pad($num, $p, '0', STR_PAD_LEFT);
                            }
                            $wo_variants = array_unique($wo_variants);
                            $wo_sql_parts = [];
                            foreach($wo_variants as $wv) {
                                $esc = mysqli_real_escape_string($koneksi, $wv);
                                $wo_sql_parts[] = "target_id = '$esc'";
                                $wo_sql_parts[] = "FIND_IN_SET('$esc', target_id)";
                            }
                            $wo_condition_sql = implode(' OR ', $wo_sql_parts);

                            $tgl_cek = date('Y-m-d');
                            $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                                 WHERE target_type='workorder' 
                                                                   AND ($wo_condition_sql)
                                                                   AND status_aktif=1
                                                                   AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai
                                                                 ORDER BY nilai_promo DESC LIMIT 1");
                            if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                                $wo_persen = 0; $wo_nominal = 0; $harga = floatval($pending_data['harga_satuan']);
                                if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                    $wo_nominal = floatval($prow['nilai_promo']);
                                    $wo_persen = ($harga > 0) ? ($wo_nominal / $harga * 100) : 0;
                                } else {
                                    $wo_persen = floatval($prow['nilai_promo']);
                                    $wo_nominal = $harga * ($wo_persen / 100);
                                }
                                if($wo_nominal > $diskon_nominal) {
                                    $diskon_nominal = $wo_nominal;
                                    $diskon_persen = $wo_persen;
                                    $diskon_source = 'promo';
                                    $id_promo = intval($prow['id_promo']);
                                }
                            }
                        }

                        $qty_appr = intval($pending_data['quantity']);
                        $harga_jual = floatval($pending_data['harga_satuan']);
                        $subtotal = ($harga_jual * $qty_appr) - ($diskon_nominal * $qty_appr);
                        if($subtotal < 0) { $subtotal = 0; }

                        // Insert to tblservis_barang with discount fields
                        $sql_insert_barang = "INSERT INTO tblservis_barang
                                               (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                               VALUES
                                               ('$no_service_post', '$nobaris_barang', '" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "', '" . $qty_appr . "',
                                                '0', '" . $harga_jual . "', '" . $diskon_persen . "', '" . $subtotal . "', '" . $diskon_source . "', '" . $diskon_persen . "', '" . $diskon_nominal . "', " . $id_promo . ")";
                        mysqli_query($koneksi, $sql_insert_barang);
                    }
                } else {
                    // Jasa
                    $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_jasa
                                                             WHERE no_service='$no_service_post'
                                                               AND no_item='" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "'");
                    $check_result = mysqli_fetch_array($check_existing);

                    if ($check_result['count'] == 0) {
                        // Get next nobaris for jasa
                        $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                                  FROM tblservis_jasa WHERE no_service='$no_service_post'");
                        $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
                        $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

                        // Calculate discount for jasa
                        $diskon_source = '';
                        $diskon_persen = 0;
                        $diskon_nominal = 0;
                        $id_promo = 'NULL';
                        $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service_post' LIMIT 1");
                        $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
                        $no_polisi_svc = $svc['no_polisi'] ?? '';
                        if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                            $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'jasa', floatval($pending_data['harga_satuan']));
                            if(($disc['diskon_nominal'] ?? 0) > 0) {
                                $diskon_persen = floatval($disc['diskon_persen']);
                                $diskon_nominal = floatval($disc['diskon_nominal']);
                                $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                    $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                    if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                }
                            }
                        }

                        // Workorder-level promo for jasa
                        $kode_wo_appr = '';
                        if(!empty($pending_data['wo_id'])) {
                            $qwo = mysqli_query($koneksi, "SELECT kode_wo FROM tbservis_workorder WHERE id='".intval($pending_data['wo_id'])."' LIMIT 1");
                            if($qwo && ($rwo = mysqli_fetch_assoc($qwo))) { $kode_wo_appr = $rwo['kode_wo'] ?? ''; }
                        }
                        if(!empty($kode_wo_appr)) {
                            // ROBUST MATCHING for Approval
                            $wo_variants = [$kode_wo_appr];
                            $clean_wo = strtoupper(str_replace(' ', '', $kode_wo_appr));
                            $wo_variants[] = $clean_wo;
                            if (preg_match('/^WO0*(\d+)$/i', $clean_wo, $m)) {
                                $num = $m[1];
                                for ($p=1; $p<=5; $p++) $wo_variants[] = 'WO' . str_pad($num, $p, '0', STR_PAD_LEFT);
                            }
                            $wo_variants = array_unique($wo_variants);
                            $wo_sql_parts = [];
                            foreach($wo_variants as $wv) {
                                $esc = mysqli_real_escape_string($koneksi, $wv);
                                $wo_sql_parts[] = "target_id = '$esc'";
                                $wo_sql_parts[] = "FIND_IN_SET('$esc', target_id)";
                            }
                            $wo_condition_sql = implode(' OR ', $wo_sql_parts);

                            $tgl_cek = date('Y-m-d');
                            $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                                 WHERE target_type='workorder' 
                                                                   AND ($wo_condition_sql)
                                                                   AND status_aktif=1
                                                                   AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai
                                                                 ORDER BY nilai_promo DESC LIMIT 1");
                            if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                                $wo_persen = 0; $wo_nominal = 0; $harga = floatval($pending_data['harga_satuan']);
                                if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                    $wo_nominal = floatval($prow['nilai_promo']);
                                    $wo_persen = ($harga > 0) ? ($wo_nominal / $harga * 100) : 0;
                                } else {
                                    $wo_persen = floatval($prow['nilai_promo']);
                                    $wo_nominal = $harga * ($wo_persen / 100);
                                }
                                if($wo_nominal > $diskon_nominal) {
                                    $diskon_nominal = $wo_nominal;
                                    $diskon_persen = $wo_persen;
                                    $diskon_source = 'promo';
                                    $id_promo = intval($prow['id_promo']);
                                }
                            }
                        }

                        $harga_jasa = floatval($pending_data['harga_satuan']);
                        $waktu_jasa = intval($pending_data['waktu']);
                        $subtotal = $harga_jasa - $diskon_nominal;
                        if($subtotal < 0) { $subtotal = 0; }

                        // Insert to tblservis_jasa with discount fields
                        $sql_insert_jasa = "INSERT INTO tblservis_jasa
                                            (no_service, nobaris, no_item, waktu, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                            VALUES
                                            ('$no_service_post', '$nobaris_jasa', '" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "',
                                             '" . $waktu_jasa . "', '" . $harga_jasa . "', '" . $diskon_persen . "', '" . $subtotal . "', '" . $diskon_source . "', '" . $diskon_persen . "', '" . $diskon_nominal . "', " . $id_promo . ")";
                        mysqli_query($koneksi, $sql_insert_jasa);
                    }
                }

                // Update status pending item to approved
                mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                       SET status_approval='disetujui',
                                           updated_at=NOW()
                                       WHERE id='$pending_item_id'");

                echo "<script>
                    alert('Item berhasil disetujui dan ditambahkan ke " . ($pending_data['tipe'] == 'barang' ? "Item Barang" : "Item Jasa") . "!');
                    window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
                </script>";
                exit;
            } else {
                echo "<script>
                    alert('Item tidak ditemukan atau sudah diproses!');
                    window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
                </script>";
                exit;
            }
        } else {
            echo "<script>
                alert('ID item tidak valid!');
                window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
            </script>";
            exit;
        }
    }

    // HANDLER BULK APPROVE WORKORDER ITEMS
    if (isset($_POST['btnapprove_wo_bulk'])) {
        $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
        $selected_ids = $_POST['selected_item_ids'] ?? array();

        if (!empty($selected_ids) && is_array($selected_ids)) {
            $count_approved = 0;
            $count_barang = 0;
            $count_jasa = 0;

            foreach ($selected_ids as $item_id) {
                $item_id = mysqli_real_escape_string($koneksi, $item_id);

                // Get pending item data
                $q_pending = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items
                                                      WHERE id='$item_id'
                                                        AND no_service='$no_service_post'
                                                        AND status_approval='pending'");

                if ($q_pending && mysqli_num_rows($q_pending) > 0) {
                    $pending_data = mysqli_fetch_array($q_pending);

                    // Process based on type
                    if ($pending_data['tipe'] == 'barang') {
                        // Check if item already exists
                        $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_barang
                                                                 WHERE no_service='$no_service_post'
                                                                   AND no_item='" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "'");
                        $check_result = mysqli_fetch_array($check_existing);

                        if ($check_result['count'] == 0) {
                            // Get next nobaris
                            $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                                 FROM tblservis_barang WHERE no_service='$no_service_post'");
                            $nobaris_data = mysqli_fetch_array($q_nobaris);
                            $nobaris_barang = $nobaris_data['next_nobaris'] ?? 1;

                            // Calculate discount
                            $diskon_source = '';
                            $diskon_persen = 0;
                            $diskon_nominal = 0;
                            $id_promo = 'NULL';
                            $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service_post' LIMIT 1");
                            $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
                            $no_polisi_svc = $svc['no_polisi'] ?? '';
                            if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                                $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'barang', floatval($pending_data['harga_satuan']));
                                if(($disc['diskon_nominal'] ?? 0) > 0) {
                                    $diskon_persen = floatval($disc['diskon_persen']);
                                    $diskon_nominal = floatval($disc['diskon_nominal']);
                                    $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                    if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                        $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                        if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                    }
                                }
                            }

                            // Workorder-level promo for barang (bulk)
                            $kode_wo_appr = '';
                            if(!empty($pending_data['wo_id'])) {
                                $qwo = mysqli_query($koneksi, "SELECT kode_wo FROM tbservis_workorder WHERE id='".intval($pending_data['wo_id'])."' LIMIT 1");
                                if($qwo && ($rwo = mysqli_fetch_assoc($qwo))) { $kode_wo_appr = $rwo['kode_wo'] ?? ''; }
                            }
                            if(!empty($kode_wo_appr)) {
                                // ROBUST MATCHING for Bulk Approval
                                $wo_variants = [$kode_wo_appr];
                                $clean_wo = strtoupper(str_replace(' ', '', $kode_wo_appr));
                                $wo_variants[] = $clean_wo;
                                if (preg_match('/^WO0*(\d+)$/i', $clean_wo, $m)) {
                                    $num = $m[1];
                                    for ($p=1; $p<=5; $p++) $wo_variants[] = 'WO' . str_pad($num, $p, '0', STR_PAD_LEFT);
                                }
                                $wo_variants = array_unique($wo_variants);
                                $wo_sql_parts = [];
                                foreach($wo_variants as $wv) {
                                    $esc = mysqli_real_escape_string($koneksi, $wv);
                                    $wo_sql_parts[] = "target_id = '$esc'";
                                    $wo_sql_parts[] = "FIND_IN_SET('$esc', target_id)";
                                }
                                $wo_condition_sql = implode(' OR ', $wo_sql_parts);

                                $tgl_cek = date('Y-m-d');
                                $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                                     WHERE target_type='workorder' 
                                                                       AND ($wo_condition_sql)
                                                                       AND status_aktif=1
                                                                       AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai
                                                                     ORDER BY nilai_promo DESC LIMIT 1");
                                if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                                    $wo_persen = 0; $wo_nominal = 0; $harga = floatval($pending_data['harga_satuan']);
                                    if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                        $wo_nominal = floatval($prow['nilai_promo']);
                                        $wo_persen = ($harga > 0) ? ($wo_nominal / $harga * 100) : 0;
                                    } else {
                                        $wo_persen = floatval($prow['nilai_promo']);
                                        $wo_nominal = $harga * ($wo_persen / 100);
                                    }
                                    if($wo_nominal > $diskon_nominal) {
                                        $diskon_nominal = $wo_nominal;
                                        $diskon_persen = $wo_persen;
                                        $diskon_source = 'promo';
                                        $id_promo = intval($prow['id_promo']);
                                    }
                                }
                            }

                            $qty_appr = intval($pending_data['quantity']);
                            $harga_jual = floatval($pending_data['harga_satuan']);
                            $subtotal = ($harga_jual * $qty_appr) - ($diskon_nominal * $qty_appr);
                            if($subtotal < 0) { $subtotal = 0; }

                            // Insert to tblservis_barang with discount fields
                            mysqli_query($koneksi, "INSERT INTO tblservis_barang
                                                   (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                                   VALUES
                                                   ('$no_service_post', '$nobaris_barang', '" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "',
                                                    '" . $qty_appr . "', '0', '" . $harga_jual . "', '" . $diskon_persen . "', '" . $subtotal . "', '" . $diskon_source . "', '" . $diskon_persen . "', '" . $diskon_nominal . "', " . $id_promo . ")");
                            $count_barang++;
                        }
                    } else {
                        // Jasa
                        $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_jasa
                                                                 WHERE no_service='$no_service_post'
                                                                   AND no_item='" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "'");
                        $check_result = mysqli_fetch_array($check_existing);

                        if ($check_result['count'] == 0) {
                            // Get next nobaris
                            $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                                      FROM tblservis_jasa WHERE no_service='$no_service_post'");
                            $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
                            $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

                            // Calculate discount for jasa
                            $diskon_source = '';
                            $diskon_persen = 0;
                            $diskon_nominal = 0;
                            $id_promo = 'NULL';
                            $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service_post' LIMIT 1");
                            $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
                            $no_polisi_svc = $svc['no_polisi'] ?? '';
                            if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
                                $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $pending_data['kode_item'], 'jasa', floatval($pending_data['harga_satuan']));
                                if(($disc['diskon_nominal'] ?? 0) > 0) {
                                    $diskon_persen = floatval($disc['diskon_persen']);
                                    $diskon_nominal = floatval($disc['diskon_nominal']);
                                    $diskon_source = (stripos($disc['discount_source'], 'Promo') !== false || stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'Member') !== false || stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
                                    if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
                                        $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
                                        if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
                                    }
                                }
                            }

                            // Workorder-level promo for jasa (bulk)
                            $kode_wo_appr = '';
                            if(!empty($pending_data['wo_id'])) {
                                $qwo = mysqli_query($koneksi, "SELECT kode_wo FROM tbservis_workorder WHERE id='".intval($pending_data['wo_id'])."' LIMIT 1");
                                if($qwo && ($rwo = mysqli_fetch_assoc($qwo))) { $kode_wo_appr = $rwo['kode_wo'] ?? ''; }
                            }
                            if(!empty($kode_wo_appr)) {
                                // ROBUST MATCHING for Bulk Approval Jasa
                                $wo_variants = [$kode_wo_appr];
                                $clean_wo = strtoupper(str_replace(' ', '', $kode_wo_appr));
                                $wo_variants[] = $clean_wo;
                                if (preg_match('/^WO0*(\d+)$/i', $clean_wo, $m)) {
                                    $num = $m[1];
                                    for ($p=1; $p<=5; $p++) $wo_variants[] = 'WO' . str_pad($num, $p, '0', STR_PAD_LEFT);
                                }
                                $wo_variants = array_unique($wo_variants);
                                $wo_sql_parts = [];
                                foreach($wo_variants as $wv) {
                                    $esc = mysqli_real_escape_string($koneksi, $wv);
                                    $wo_sql_parts[] = "target_id = '$esc'";
                                    $wo_sql_parts[] = "FIND_IN_SET('$esc', target_id)";
                                }
                                $wo_condition_sql = implode(' OR ', $wo_sql_parts);

                                $tgl_cek = date('Y-m-d');
                                $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                                     WHERE target_type='workorder' 
                                                                       AND ($wo_condition_sql)
                                                                       AND status_aktif=1
                                                                       AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai
                                                                     ORDER BY nilai_promo DESC LIMIT 1");
                                if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                                    $wo_persen = 0; $wo_nominal = 0; $harga = floatval($pending_data['harga_satuan']);
                                    if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                        $wo_nominal = floatval($prow['nilai_promo']);
                                        $wo_persen = ($harga > 0) ? ($wo_nominal / $harga * 100) : 0;
                                    } else {
                                        $wo_persen = floatval($prow['nilai_promo']);
                                        $wo_nominal = $harga * ($wo_persen / 100);
                                    }
                                    if($wo_nominal > $diskon_nominal) {
                                        $diskon_nominal = $wo_nominal;
                                        $diskon_persen = $wo_persen;
                                        $diskon_source = 'promo';
                                        $id_promo = intval($prow['id_promo']);
                                    }
                                }
                            }

                            $harga_jasa = floatval($pending_data['harga_satuan']);
                            $waktu_jasa = intval($pending_data['waktu']);
                            $subtotal = $harga_jasa - $diskon_nominal;
                            if($subtotal < 0) { $subtotal = 0; }

                            // Insert to tblservis_jasa with discount fields
                            mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                                   (no_service, nobaris, no_item, waktu, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                                   VALUES
                                                   ('$no_service_post', '$nobaris_jasa', '" . mysqli_real_escape_string($koneksi, $pending_data['kode_item']) . "',
                                                    '" . $waktu_jasa . "', '" . $harga_jasa . "', '" . $diskon_persen . "', '" . $subtotal . "', '" . $diskon_source . "', '" . $diskon_persen . "', '" . $diskon_nominal . "', " . $id_promo . ")");
                            $count_jasa++;
                        }
                    }

                    // Update status to approved
                    mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                           SET status_approval='disetujui',
                                               approved_by='" . ($_SESSION['_iduser'] ?? 'system') . "',
                                               approved_at=NOW(),
                                               updated_at=NOW()
                                           WHERE id='$item_id'");
                    $count_approved++;
                }
            }

            $msg_parts = array();
            if ($count_barang > 0) $msg_parts[] = "$count_barang item barang";
            if ($count_jasa > 0) $msg_parts[] = "$count_jasa item jasa";

            echo "<script>
                alert('Berhasil menyetujui $count_approved item!\\n\\nDitambahkan ke: " . implode(', ', $msg_parts) . "');
                window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
            </script>";
            exit;
        } else {
            echo "<script>
                alert('Tidak ada item yang dipilih!');
                history.back();
            </script>";
            exit;
        }
    }

    // HANDLER REJECT WORKORDER ITEM - Masuk ke catatan service
    if (isset($_POST['btnreject_wo_item'])) {
        $pending_item_id = mysqli_real_escape_string($koneksi, $_POST['pending_item_id'] ?? '');
        $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
        $alasan_reject = mysqli_real_escape_string($koneksi, $_POST['alasan_reject'] ?? 'lainnya');
        $keterangan_reject = mysqli_real_escape_string($koneksi, $_POST['keterangan_reject'] ?? '');

        if (!empty($pending_item_id)) {
            // Get pending item data
            $q_pending = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items
                                                 WHERE id='$pending_item_id'
                                                   AND no_service='$no_service_post'
                                                   AND status_approval='pending'");

            if ($q_pending && mysqli_num_rows($q_pending) > 0) {
                $pending_data = mysqli_fetch_array($q_pending);

                // Update status to rejected
                mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                       SET status_approval='ditolak',
                                           alasan_tolak='$alasan_reject',
                                           keterangan_tolak='$keterangan_reject',
                                           updated_at=NOW()
                                       WHERE id='$pending_item_id'");

                // Tambahkan ke catatan service (opsional)
                $catatan_text = '[REJECT WO] ' . ($pending_data['nama_item'] ?? $pending_data['kode_item']);
                mysqli_query($koneksi, "UPDATE tblservice SET catatan = CONCAT(COALESCE(catatan,''), '\n', '" . mysqli_real_escape_string($koneksi, $catatan_text) . "') WHERE no_service='$no_service_post'");

                echo "<script>
                    alert('Item berhasil ditolak.');
                    window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
                </script>";
                exit;
            } else {
                echo "<script>
                    alert('Item tidak ditemukan atau sudah diproses!');
                    window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
                </script>";
                exit;
            }
        } else {
            echo "<script>
                alert('ID item tidak valid!');
                window.location.href = 'servis-input-reguler-jemput.php?snoserv=$no_service_post&tab=temuan-penawaran';
            </script>";
            exit;
        }
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

    // Get vehicle and customer data
    $klat = '';
    $klong = '';
    
    if(!empty($no_polisi)) {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $customerRow = $bundle['customer'] ?? [];
        $pemilik = $vehicleRow['pemilik'] ?? '';
        if(!empty($customerRow['namapelanggan'])) {
            $namapelanggan = $customerRow['namapelanggan'];
            $alamatpelanggan = $customerRow['alamat'] ?? '';
            $teleponpelanggan = $customerRow['telephone'] ?? '';
            $potongan_pelanggan = $customerRow['potongan'] ?? $potongan_pelanggan;
            $tipe_potongan = $customerRow['tipepot'] ?? $tipe_potongan;
            $kategori_pelanggan = $customerRow['kgrup'] ?? $kategori_pelanggan;
            if (empty($kode_pelanggan) && !empty($customerRow['nopelanggan'])) {
                $kode_pelanggan = $customerRow['nopelanggan'];
            }
        }
        $jenis=$vehicleRow['jenis'] ?? '';
        $merek=$vehicleRow['tipe'] ?? '';
        $warna=$vehicleRow['warna'] ?? '';
        $no_rangka=$vehicleRow['no_rangka'] ?? '';
        $no_mesin=$vehicleRow['no_mesin'] ?? '';
        
        $klat = $customerRow['klat'] ?? '';
        $klong = $customerRow['klong'] ?? '';
        
        $foto_tampak_rumah = $customerRow['foto_tampak_rumah'] ?? ($customerRow['patokan'] ?? '');
    } else {
        $pemilik = '';
        $jenis = '';
        $merek = '';
        $warna = '';
        $no_rangka = '';
        $no_mesin = '';
        $klat = '';
        $klong = '';
        $foto_tampak_rumah = '';
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
        $tipe_servis = 'JEMPUT';
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
    $persen_mekanik1 = 0;
        $mekanik2 = "";
    $persen_mekanik2 = 0;
        $mekanik3 = "";
    $persen_mekanik3 = 0;
        $mekanik4 = "";
    $persen_mekanik4 = 0;

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
            $persen_mekanik1 = $existing_data['persen_mekanik1'] ?? 0;
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_mekanik2 = $existing_data['persen_mekanik2'] ?? 0;
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_mekanik3 = $existing_data['persen_mekanik3'] ?? 0;
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_mekanik4 = $existing_data['persen_mekanik4'] ?? 0;
            $metode_pembayaran = $existing_data['metode_pembayaran'] ?? 'Tunai';
            $bukti_pembayaran = $existing_data['bukti_pembayaran'] ?? '';
            
            // Jemput specific variables
            $keterangan_jemput = $existing_data['keterangan_jemput'] ?? $existing_data['keterangan'] ?? '';
            $foto_service = $existing_data['foto_patokan'] ?? $existing_data['foto_motor'] ?? '';
            // Gunakan foto dari service jika ada, jika tidak gunakan dari data pelanggan (foto_tampak_rumah)
            $foto_patokan = !empty($foto_service) ? $foto_service : $foto_tampak_rumah;
            $status_jemput = $existing_data['status_jemput'] ?? '';
            $tanggal_jemput = $existing_data['tanggal'] ?? ''; 
            $jam_jemput = $existing_data['jam'] ?? '';
        }
    }

    // ========== AUTO-FILL KEPALA MEKANIK HARIAN ==========
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
                    if (document.querySelector('.alert-km-missing')) {
                        return;
                    }

                    var pageContent = document.querySelector('.ks-left') || document.querySelector('.page-content') || document.querySelector('.rd-page-wrapper') || document.body;
                    if (!pageContent) {
                        return;
                    }

                    var warningWrapper = document.createElement('div');
                    warningWrapper.className = 'alert alert-danger alert-dismissible alert-km-missing';
                    warningWrapper.style.cssText = 'margin:0 0 6px;font-size:11px;padding:6px 10px;border-radius:4px;';
                    warningWrapper.innerHTML =
                        '<button type=\"button\" class=\"close\" data-dismiss=\"alert\" style=\"font-size:14px;line-height:1;\">&times;</button>' +
                        '<i class=\"fa fa-warning\"></i> <strong>KM Harian belum diinput!</strong> ' +
                        '<a href=\"input_kepala_mekanik_harian.php\" style=\"font-weight:bold;\">Input sekarang</a>';

                    pageContent.insertBefore(warningWrapper, pageContent.firstChild);
                });
            </script>";
        }
    }
    // ========== END AUTO-FILL KEPALA MEKANIK HARIAN ==========

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
    $prioritas_wo = 'urgent'; // Jemput usually urgent
    $deskripsi_pekerjaan = '';
    $instruksi_khusus = '';
    $catatan_wo = '';

    // Initialize antrian variables
    $current_antrian = '';
    $suggested_antrian = '';

    // Get current antrian if service exists
    if(!empty($no_service)) {
        $query_current_antrian = "SELECT no_antrian FROM tb_antrian_servis WHERE no_service='$no_service'";
        $result_current_antrian = mysqli_query($koneksi, $query_current_antrian);
        if($result_current_antrian && mysqli_num_rows($result_current_antrian) > 0) {
            $antrian_data = mysqli_fetch_array($result_current_antrian);
            $current_antrian = $antrian_data['no_antrian'];
        }
    }

    // Generate suggested antrian number
    $tanggal_today = date('Y-m-d');
    $query_suggested_antrian = "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_today'";
    $result_suggested_antrian = mysqli_query($koneksi, $query_suggested_antrian);
    if($result_suggested_antrian) {
        $antrian_count = mysqli_fetch_array($result_suggested_antrian)['total'];
        $suggested_antrian = $antrian_count + 1;
    } else {
        $suggested_antrian = 1;
    }

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

    // Payment Processing for Jemput
    // ========== HANDLER: SAVE SERVICE (NO PAYMENT) ==========
    if(isset($_POST['btnsave']) || (isset($_POST['btnsimpan']) && !empty($_POST['txtnosrv'] ?? ''))) {
        $no_service = $_POST['txtnosrv'] ?? $no_service;
        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        // Get mechanic data (Manual extraction to ensure we capture all fields)
        $kepala_mekanik1 = $_POST['cbokepala_mekanik1'] ?? '';
        $persen_kepala1 = $_POST['txtpersen_kepala1'] ?? 0;
        $kepala_mekanik2 = $_POST['cbokepala_mekanik2'] ?? '';
        $persen_kepala2 = $_POST['txtpersen_kepala2'] ?? 0;
        $admin1 = $_POST['cboadmin1'] ?? '';
        $persen_admin1 = $_POST['txtpersen_admin1'] ?? 0;
        $admin2 = $_POST['cboadmin2'] ?? '';
        $persen_admin2 = $_POST['txtpersen_admin2'] ?? 0;
        $mekanik1 = $_POST['cbomekanik1'] ?? '';
        $persen_mekanik1 = $_POST['txtpersen_mekanik1'] ?? 0;
        $mekanik2 = $_POST['cbomekanik2'] ?? '';
        $persen_mekanik2 = $_POST['txtpersen_mekanik2'] ?? 0;
        $mekanik3 = $_POST['cbomekanik3'] ?? '';
        $persen_mekanik3 = $_POST['txtpersen_mekanik3'] ?? 0;
        $mekanik4 = $_POST['cbomekanik4'] ?? '';
        $persen_mekanik4 = $_POST['txtpersen_mekanik4'] ?? 0;
        
        // Get Jemput Specific Data
        // Note: Some might be in different tabs/forms, but usually main form submits all
        // If separate forms are used, we rely on what's posted.
        
        // Update Mechanic & Details
        // We do *not* set status='selesai' or 'bayar'. We keep current status or ensure it's valid.
        // Assuming 'datang' or 'diproses'.
        
        $update_query = "UPDATE tblservice SET 
            km_skr='$km_skr',
            km_berikut='$km_berikut',
            kepala_mekanik1='$kepala_mekanik1',
            kepala_mekanik2='$kepala_mekanik2',
            persen_kepala_mekanik1='$persen_kepala1',
            persen_kepala_mekanik2='$persen_kepala2',
            admin1='$admin1',
            admin2='$admin2',
            persen_admin1='$persen_admin1',
            persen_admin2='$persen_admin2',
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
            
        if(mysqli_query($koneksi, $update_query)) {
            echo "<script>
                alert('Data service berhasil disimpan!');
                window.location='servis-input-reguler-jemput.php?snoserv=$no_service&tab=actions';
            </script>";
        } else {
             echo "<script>alert('Error saving service: " . mysqli_error($koneksi) . "');</script>";
        }
    }

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
        $bayar_tunai    = (float)str_replace(['.', ','], '', $_POST['bayar_tunai']    ?? '0');
        $bayar_transfer = (float)str_replace(['.', ','], '', $_POST['bayar_transfer'] ?? '0');
        $bayar_qris     = (float)str_replace(['.', ','], '', $_POST['bayar_qris']     ?? '0');
        $ref_transfer   = mysqli_real_escape_string($koneksi, $_POST['ref_transfer']  ?? '');
        $txtbayar = $bayar_tunai + $bayar_transfer + $bayar_qris;
        if ($txtbayar == 0) {
            $txtbayar = (float)str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0');
        }

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
            echo "<script>window.alert('Tidak dapat memproses pembayaran karena:\\n- ".$msg."'); window.location='servis-input-reguler-jemput.php?snoserv=".addslashes($no_service)."&tab=actions#service-actions';</script>";
            exit;
        }

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

        // Exclusive discount: use member discount if available, else manual invoice discount
        $diskon_plus_nominal = $tot_pay * ($total_diskon_persen / 100);

        $ppn=($tot_pay - $diskon_plus_nominal)*($txtpajak_persen/100);
        $net_pay=$tot_pay-$diskon_plus_nominal+$ppn;
        $kembalian_pay=$txtbayar-$net_pay;

        // Validate payment amount
        if($txtbayar < $net_pay) {
            echo"<script>window.alert('Jumlah pembayaran tidak mencukupi! Total: Rp " . number_format($net_pay, 0, ',', '.') . ", Bayar: Rp " . number_format($txtbayar, 0, ',', '.') . "');
            window.history.back();</script>";
            exit;
        }

        if($net_pay <= 0 && $tot_pay > 0) { // Allow 0 if total is 0

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
                                diskon_persen='$total_diskon_persen', diskon_nom='$diskon_plus_nominal',
                                ppn_persen='$txtpajak_persen', ppn_nom='$ppn',
                                subtotal_item='$total_barang_pay',
                                subtotal_jasa='$total_service_pay',
                                subtotal='$tot_pay',
                                total_diskon='$diskon_plus_nominal',
                                total_pajak='$ppn',
                                total_grand='$net_pay',
                                total_akhir='$net_pay',
                                total_waktu='$total_waktu_pay',
                                km_skr='$km_skr',
                                km_berikut='$km_berikut',
                                status_servis='selesai',
                                {$tgl_bayar_set}
                                kepala_mekanik1='$kepala_mekanik1',
                                kepala_mekanik2='$kepala_mekanik2',
                                persen_kepala_mekanik1='$persen_kepala1',
                                persen_kepala_mekanik2='$persen_kepala2',
                                admin1='" . ($_POST['cboadmin1'] ?? '') . "',
                                admin2='" . ($_POST['cboadmin2'] ?? '') . "',
                                persen_admin1='" . ($_POST['txtpersen_admin1'] ?? 0) . "',
                                persen_admin2='" . ($_POST['txtpersen_admin2'] ?? 0) . "',
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
                                kembali='$kembalian_pay',
                                bayar_tunai='$bayar_tunai',
                                bayar_transfer='$bayar_transfer',
                                bayar_qris='$bayar_qris',
                                ref_transfer='$ref_transfer'" .
                                (!empty($bukti_pembayaran_path) ? ", bukti_pembayaran='$bukti_pembayaran_path'" : "") . "
                                WHERE
                                no_service='$no_service'";

        if(!mysqli_query($koneksi, $update_query)) {
            die("Error Update Service: " . mysqli_error($koneksi));
        }

        // Update antrian to selesai
        mysqli_query($koneksi, "UPDATE tb_antrian_servis 
                                SET status_antrian='selesai', 
                                    jam_selesai=NOW(), 
                                    updated_at=NOW() 
                                WHERE no_service='$no_service'");

        // 🆕 AUTO-UPDATE STATISTIK PELANGGAN, MEMBER TIER & HISTORY SERVICE
        $get_customer = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
        if ($get_customer && $customer_row = mysqli_fetch_assoc($get_customer)) {
            $no_pelanggan_bayar = $customer_row['no_pelanggan'];
            if (!empty($no_pelanggan_bayar)) {
                // Gunakan fungsi processAfterPayment untuk update semua data
                if (function_exists('processAfterPayment')) {
                    $payment_result = processAfterPayment($koneksi, $no_pelanggan_bayar, $no_service, 'jemput');
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
                            logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (jemput)');
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

    // Success message with redirect options
    if(function_exists('clearSessionDiscount')) { clearSessionDiscount(); }
    echo"<script>
        if(confirm('Pembayaran Service Jemput Berhasil!\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "\\n\\nService jemput telah selesai dan siap diantar kembali.\\n\\nKlik OK untuk print invoice\\nKlik Cancel untuk kembali ke daftar servis')) {
            window.location='servis-print.php?snoserv=$no_service';
        } else {
            window.location='servis-reguler.php';
        }
    </script>";
    }
}
?>
<?php
// Emergency Variable Recovery
if (!isset($koneksi) || !$koneksi) {
    if(file_exists("../config/koneksi.php")) include "../config/koneksi.php";
}
if (!isset($no_service)) {
    $no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : (isset($_GET['no_service']) ? $_GET['no_service'] : '');
}
if (!isset($active_tab)) {
    $active_tab = 'service-details';
    if(isset($_GET['tab'])) {
        switch($_GET['tab']) {
            case 'items': $active_tab = 'service-items'; break;
            case 'jasa': $active_tab = 'service-jasa'; break;
            case 'workorder': $active_tab = 'workorder-details'; break;
            case 'pickup': $active_tab = 'pickup-details'; break;
            case 'temuan': $active_tab = 'temuan-penawaran'; break;
            case 'actions': $active_tab = 'service-actions'; break;
        }
    }
}

// Ensure critical service data variables are defined to prevent template crashes
if ((!isset($no_polisi) || empty($no_polisi)) && !empty($no_service) && isset($koneksi)) {
    $rec_query = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$no_service'");
    if ($rec_query && $rec_data = mysqli_fetch_array($rec_query)) {
        $no_polisi = $rec_data['no_polisi'] ?? '-';
        $nama_pelanggan = $rec_data['nama_pelanggan'] ?? '-';
        $tipe = $rec_data['tipe_motor'] ?? '-';
        $tahun = $rec_data['tahun_motor'] ?? '-';
        $warna = $rec_data['warna_motor'] ?? '-';
        $keluhan = $rec_data['keluhan'] ?? '';
        $km_skr = $rec_data['km_skr'] ?? 0;
        $km_berikut = $rec_data['km_berikut'] ?? 0;
    } else {
        // Fallback if record not found
        $no_polisi = '-';
        $nama_pelanggan = 'Unknown';
    }
}
// =================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Input Service Jemput</title>

    <meta name="description" content="Input Service Jemput - Redesign v2.0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Bootstrap & FontAwesome (Local files to avoid CDN blocking) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- jQuery UI for datepicker -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- Fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- Redesign Styles -->
    <?php include "_template/_redesign_styles.php"; ?>
    <!-- Kasir 3-Column Layout -->
    <?php include "_template/_kasir_3col_layout.php"; ?>

    <style>
    body { font-family: 'Open Sans', 'Segoe UI', Tahoma, sans-serif; }
    .ks-tab-btn.active { border-bottom-color: #e67e22 !important; color: #e67e22 !important; }
    .ks-status-pill.jemput { background: linear-gradient(135deg, #e67e22, #f39c12); color: #fff; }
    .ks-btn-bayar { background: linear-gradient(135deg, #e67e22, #f39c12) !important; }
    </style>

    <!-- Scripts loaded early so legacy inline handlers can safely use jQuery/$ -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
</head>

<body>
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
            <span class="ks-status-pill jemput">
                <i class="fa fa-truck-pickup"></i> JEMPUT
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
            <?php if(!empty($no_service)): ?>
            <a href="servis-print.php?snoserv=<?= urlencode($no_service) ?>" class="ks-topbar-btn" target="_blank">
                <i class="fa fa-print"></i> Print
            </a>
            <?php endif; ?>
            <a href="servis-reguler-jemput.php" class="ks-topbar-btn">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <div class="ks-user-badge">
                <img src="../<?= $foto_user ?>" alt="User" class="ks-user-photo">
                <span class="ks-user-name"><?= htmlspecialchars($_nama) ?></span>
            </div>
        </div>
    </div>

    <!-- 3-Column Form -->
    <form method="POST" action="" id="formServiceJemput" enctype="multipart/form-data">
        <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
        <input type="hidden" name="current_tab" id="current_tab" value="<?= htmlspecialchars($active_tab) ?>">

        <?php
        if(function_exists('displayActiveDiscountBanner') && !empty($no_polisi)) {
            echo displayActiveDiscountBanner($koneksi, $no_polisi);
        }
        ?>

        <div class="ks-body">

            <!-- LEFT: Kendaraan + Keluhan + Mekanik -->
            <div class="ks-left">
                <?php include "_template/panel-kiri-kasir.php"; ?>
            </div>

            <!-- CENTER: 5 Tabs (termasuk Detail Jemput) -->
            <div class="ks-center">
                <?php $tab_base_url = 'servis-input-reguler-jemput.php?snoserv=' . urlencode($no_service); ?>
                <div class="ks-tabs-nav">
                    <a class="ks-tab-btn <?= $active_tab=='pickup-details'?'active':'' ?>"
                       data-target="pickup-details" href="<?= $tab_base_url ?>&tab=pickup">
                        <i class="fa fa-map-marked-alt"></i> Detail Jemput
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='workorder-details'?'active':'' ?>"
                       data-target="workorder-details" href="<?= $tab_base_url ?>&tab=workorder">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <?php $c_wo=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tbservis_workorder WHERE no_service='$no_service'"); if($r){$c_wo=mysqli_fetch_assoc($r)['c'];} if($c_wo>0): ?>
                        <span class="ks-badge"><?= $c_wo ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='temuan-penawaran'?'active':'' ?>"
                       data-target="temuan-penawaran" href="<?= $tab_base_url ?>&tab=temuan">
                        <i class="fa fa-search-plus"></i> Temuan & Penawaran
                        <?php $c_p=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tbservis_pending_items WHERE no_service='$no_service' AND status_approval='pending'"); if($r){$c_p=mysqli_fetch_assoc($r)['c'];} if($c_p>0): ?>
                        <span class="ks-badge warning"><?= $c_p ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='service-items'?'active':'' ?>"
                       data-target="service-items" href="<?= $tab_base_url ?>&tab=items">
                        <i class="fa fa-box"></i> Suku Cadang
                        <?php $c_b=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tblservis_barang WHERE no_service='$no_service'"); if($r){$c_b=mysqli_fetch_assoc($r)['c'];} if($c_b>0): ?>
                        <span class="ks-badge"><?= $c_b ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn <?= $active_tab=='service-jasa'?'active':'' ?>"
                       data-target="service-jasa" href="<?= $tab_base_url ?>&tab=jasa">
                        <i class="fa fa-tools"></i> Jasa Service
                        <?php $c_j=0; $r=mysqli_query($koneksi,"SELECT COUNT(*) c FROM tblservis_jasa WHERE no_service='$no_service'"); if($r){$c_j=mysqli_fetch_assoc($r)['c'];} if($c_j>0): ?>
                        <span class="ks-badge"><?= $c_j ?></span><?php endif; ?>
                    </a>
                </div>
                <div class="ks-tab-contents">
                    <?php
                    $center_active = in_array($active_tab, ['pickup-details','workorder-details','temuan-penawaran','service-items','service-jasa'])
                                     ? $active_tab : 'pickup-details';
                    ?>
                    <div id="pickup-details" class="ks-tab-pane <?= $center_active=='pickup-details'?'active':'' ?>">
                        <?php include "_template/tab-pickup-details-redesign.php"; ?>
                    </div>
                    <div id="workorder-details" class="ks-tab-pane <?= $center_active=='workorder-details'?'active':'' ?>">
                        <?php include "_template/tab-workorder-redesign.php"; ?>
                    </div>
                    <div id="temuan-penawaran" class="ks-tab-pane <?= $center_active=='temuan-penawaran'?'active':'' ?>">
                        <?php include "_template/tab-temuan-penawaran-redesign.php"; ?>
                    </div>
                    <div id="service-items" class="ks-tab-pane <?= $center_active=='service-items'?'active':'' ?>">
                        <?php include "_template/tab-item-barang-redesign.php"; ?>
                    </div>
                    <div id="service-jasa" class="ks-tab-pane <?= $center_active=='service-jasa'?'active':'' ?>">
                        <?php include "_template/tab-item-jasa-redesign.php"; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Ringkasan + Pembayaran -->
            <div class="ks-right">
                <?php include "_template/panel-kanan-kasir.php"; ?>
            </div>

        </div>
    </form>
</div>

    <!-- Include Modals -->
    <?php
    if(file_exists("_template/modal-callbacks.php")) include "_template/modal-callbacks.php";
    if(file_exists("_template/modal-search-temuan.php")) include "_template/modal-search-temuan.php";
    if(file_exists("_template/modal-search-keluhan.php")) include "_template/modal-search-keluhan.php";
    if(file_exists("_template/modal-fastmoves-v2.php")) include "_template/modal-fastmoves-v2.php";
    if(file_exists("_template/modal-fastmoves-part.php")) include "_template/modal-fastmoves-part.php";
    if(file_exists("_template/modal-tambah-keluhan-baru.php")) include "_template/modal-tambah-keluhan-baru.php";
    if(file_exists("_template/modal-input-barang-custom.php")) include "_template/modal-input-barang-custom.php";
    if(file_exists("_template/_modal_riwayat_kendaraan.php")) include "_template/_modal_riwayat_kendaraan.php";
    if(file_exists("_template/_modal_update_status_keluhan.php")) include "_template/_modal_update_status_keluhan.php";
    if(file_exists("_template/_modal_cancel_service.php")) include "_template/_modal_cancel_service.php";

    // Include Statistik Pelanggan Modal
    if(!empty($kode_pelanggan) && function_exists('renderModalStatistikPelanggan')) {
        echo renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
    }
    ?>

    <script>
    $(document).ready(function() {
        // Tab Navigation with state preservation
        $('.ks-tab-btn').on('click', function(e) {
            e.preventDefault();
            var target = $(this).data('target');

            $('.ks-tab-btn').removeClass('active');
            $(this).addClass('active');

            $('.ks-tab-pane').removeClass('active');
            $('#' + target).addClass('active');

            $('#current_tab').val(target);

            var url = new URL(window.location);
            url.searchParams.set('tab', target);
            window.history.pushState({}, '', url);
        });

        // Sync tab button highlight ke pane yang PHP-render sebagai active
        var $activePane = $('.ks-tab-pane.active');
        var defaultTarget = $activePane.length ? $activePane.attr('id') : 'detail-jemput';
        $('.ks-tab-btn[data-target="' + defaultTarget + '"]').addClass('active');

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

        // Preserve tab state on form submission
        $('form').on('submit', function() {
            var activeTab = $('.rd-tab-btn.active').data('target');
            $('#current_tab').val(activeTab);
        });
    });
    </script>

</body>
</html>
