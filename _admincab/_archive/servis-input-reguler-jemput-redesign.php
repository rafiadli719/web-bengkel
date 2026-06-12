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
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";
        
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
                    window.location = 'servis-input-reguler-jemput-redesign.php?' + new URLSearchParams(params).toString();
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

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

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

        $result = mysqli_query($koneksi,"INSERT INTO tbservis_keluhan_status
                        (no_service, keluhan, status_pengerjaan)
                        VALUES
                        ('$no_service','$txtkeluhan','datang')");

        if($result) {
            echo "<script>
                alert('Keluhan berhasil ditambahkan ke SPK!');
                window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&tab=workorder';
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
            window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&tab=workorder';
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
                    window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&tab=service-details';
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
                            window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service';
                        </script>";
                    } else {
                        echo "<script>
                            alert('Tarif jemput antar sudah ada di Item Jasa!');
                            window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service';
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
            // âœ… CRITICAL FIX: Check stock availability
            $cari_stok = mysqli_query($koneksi, "SELECT saldo
                                                 FROM view_stok_master
                                                 WHERE kd_cabang='$kd_cabang' AND no_item='$txtkdbarang'");

            if(!$cari_stok || mysqli_num_rows($cari_stok) == 0) {
                echo "<script>alert('Item tidak ditemukan di stok cabang ini!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            $stok_data = mysqli_fetch_array($cari_stok);
            $stok_akhir = intval($stok_data['saldo']);

            // Validate stock availability
            if($stok_akhir <= 0) {
                echo "<script>alert('Stok barang kosong!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            if($txtqty > $stok_akhir) {
                echo "<script>alert('Stok barang tidak mencukupi! Stok tersedia: $stok_akhir'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }

            // Check if item already exists
            $check_existing = mysqli_query($koneksi, "SELECT id FROM tblservis_barang WHERE no_service='$no_service' AND no_item='$txtkdbarang'");

            if(mysqli_num_rows($check_existing) > 0) {
                echo "<script>alert('Item barang sudah ada!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
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

                // Calculate total
                $subtotal = ($harga_jual * $txtqty) - (($harga_jual * $txtqty) * ($txtpot / 100));

                // Insert to tblservis_barang
                mysqli_query($koneksi, "INSERT INTO tblservis_barang
                                        (no_service, no_item, harga_jual, quantity, potongan, total)
                                        VALUES
                                        ('$no_service', '$txtkdbarang', '$harga_jual', '$txtqty', '$txtpot', '$subtotal')");

                echo "<script>window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            } else {
                echo "<script>alert('Item tidak ditemukan di master!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Kode barang dan qty harus diisi!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
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
                echo "<script>alert('Item jasa sudah ada!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo';</script>";
                exit;
            }
            
            // Get work order details
            $cari_wo = mysqli_query($koneksi, "SELECT harga, waktu FROM tbworkorderheader WHERE kode_wo='$txtcarisrv'");
            if($wo_data = mysqli_fetch_array($cari_wo)) {
                $harga = $wo_data['harga'];
                $waktu = $wo_data['waktu'] ?? 0;
                
                // Calculate total
                $subtotal = $harga - ($harga * ($txtpotsrv / 100));
                
                // Get next nobaris
                $query_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                $nobaris_data = mysqli_fetch_array($query_nobaris);
                $next_nobaris = $nobaris_data['next_nobaris'] ?? 1;
                
                // Insert to tblservis_jasa
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa 
                                        (no_service, nobaris, no_item, harga, waktu, potongan, total) 
                                        VALUES 
                                        ('$no_service', '$next_nobaris', '$txtcarisrv', '$harga', '$waktu', '$txtpotsrv', '$subtotal')");
                
                echo "<script>window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo';</script>";
                exit;
            } else {
                echo "<script>alert('Jasa tidak ditemukan!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Kode jasa harus diisi!'); window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';</script>";
            exit;
        }
    }

    // Handler untuk submit form
    if(isset($_POST['btnsimpan'])) {
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
            
            $query_insert_service = "INSERT INTO tblservice (
                no_service, tanggal, jam, no_pelanggan, no_polisi, keluhan, 
                status_servis, tipe_service, user_input, kd_cabang, created_at
            ) VALUES (
                '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan', 
                '$no_polisi', '$keluhan', 'dijemput', 'jemput', '$_nama', '$kd_cabang', NOW()
            )";
            
            if(mysqli_query($koneksi, $query_insert_service)) {
                // Insert ke tabel antrian
                $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                    no_service, no_antrian, tanggal, jam, no_pelanggan, no_polisi, 
                    status_antrian, tipe_service, created_at
                ) VALUES (
                    '$no_service', '$no_antrian', '$tanggal_service', '$jam_input', 
                    '$kode_pelanggan', '$no_polisi', 'dijemput', 'jemput', NOW()
                )";
                
                if(mysqli_query($koneksi, $query_insert_antrian)) {
                    echo "<script>
                        alert('Service Jemput berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian');
                        window.location.href = 'servis-input-reguler-jemput-redesign.php?snoserv=$no_service';
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
        
        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;
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

                // Auto add jasa dan barang dari work order
                $detail_wo = mysqli_query($koneksi,"SELECT kode_barang, tipe, harga, total, jumlah 
                                                    FROM tbworkorderdetail 
                                                    WHERE kode_wo='$kode_wo'");
                
                while($detail = mysqli_fetch_array($detail_wo)) {
                    if($detail['tipe'] == '1') { // Jasa
                        // Try to get waktu from tbworkorderheader
                        $waktu = 0; // Default waktu value
                        try {
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

                        // Check if waktu column exists in tblservis_jasa before inserting
                        $check_jasa_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
                        if(mysqli_num_rows($check_jasa_waktu) > 0) {
                            // Insert with waktu column
                            mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                  (no_service, no_item, harga, waktu, potongan, total)
                                                  VALUES
                                                  ('$no_service', '{$detail['kode_barang']}', '{$detail['harga']}', '$waktu', '0', '{$detail['total']}')");
                        } else {
                            // Insert without waktu column
                            mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                  (no_service, no_item, harga, potongan, total)
                                                  VALUES
                                                  ('$no_service', '{$detail['kode_barang']}', '{$detail['harga']}', '0', '{$detail['total']}')");
                        }
                    } else { // Barang
                        mysqli_query($koneksi,"INSERT INTO tblservis_barang 
                                              (no_service, no_item, quantity, qty_retur, harga_jual, potongan, total) 
                                              VALUES 
                                              ('$no_service', '{$detail['kode_barang']}', '{$detail['jumlah']}', '0', '{$detail['harga']}', '0', '{$detail['total']}')");
                    }
                }
                
        echo "<script>
            alert('Work Order berhasil ditambahkan ke SPK!');
            window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=';
        </script>";
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
                window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
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
                window.location='servis-input-reguler-jemput-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
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

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        if(!empty($no_service)) {
            // Always redirect to workorder search page for browsing and selection
            $cbocari = "";
            $cbourut = "52";
            echo "<script>window.location='servis-add-jemput-workorder-cari.php?snoserv=$no_service&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc';</script>";
        } else {
            echo "<script>alert('Nomor service harus diisi terlebih dahulu!');</script>";
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
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        namapelanggan, potongan, tipepot, kgrup 
                                        FROM tblpelanggan 
                                        WHERE nopelanggan='$kode_pelanggan'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
        $namapelanggan=$tm_cari['namapelanggan'] ?? '';
        $potongan_pelanggan = $tm_cari['potongan'] ?? 0;
        $tipe_potongan = $tm_cari['tipepot'] ?? '';
        $kategori_pelanggan = $tm_cari['kgrup'] ?? '001';
    } else {
        $namapelanggan = '';
        $potongan_pelanggan = 0;
        $tipe_potongan = '';
        $kategori_pelanggan = '001';
    }

    // Get vehicle data
    if(!empty($no_polisi)) {
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        pemilik, jenis, tipe as merek, warna, 
                                        no_rangka, no_mesin 
                                        FROM tblkendaraan 
                                        WHERE nopolisi='$no_polisi'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
        $pemilik=$tm_cari['pemilik'] ?? '';
        $jenis=$tm_cari['jenis'] ?? '';
        $merek=$tm_cari['merek'] ?? '';
        $warna=$tm_cari['warna'] ?? '';
        $no_rangka=$tm_cari['no_rangka'] ?? '';
        $no_mesin=$tm_cari['no_mesin'] ?? '';
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
    $persen_mekanik1 = 0;
        $mekanik2 = "";
    $persen_mekanik2 = 0;
        $mekanik3 = "";
    $persen_mekanik3 = 0;
        $mekanik4 = "";
    $persen_mekanik4 = 0;
    
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
    if(isset($_POST['btnbayar'])) {	
        $no_service= $_POST['txtnosrv'] ?? $no_service;			
        $km_skr=$_POST['txtkm_skr'] ?? 0;
        $km_berikut=$_POST['txtkm_next'] ?? 0;    

        $diskon_member = $_POST['txtdiskon_member'] ?? 0;
        $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'] ?? 0;  
        $total_diskon_persen = $diskon_member + $txtpotfaktur_persen;
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
                                kembali='$kembalian_pay'" . 
                                (!empty($bukti_pembayaran_path) ? ", bukti_pembayaran='$bukti_pembayaran_path'" : "") . "
                                WHERE 
                                no_service='$no_service'");
                        
        // mysqli_query($koneksi, $update_query); // This line was removed as the query is now executed directly

        // ðŸ†• AUTO-UPDATE STATISTIK PELANGGAN, MEMBER TIER & HISTORY SERVICE
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
                                'Penjualan Service Jemput','$kd_cabang')"); 
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

    // Additional calculations for totals
    $total_barang = 0;
    $total_service = 0;
    if (!empty($no_service) && isset($koneksi)) {
        $rb = mysqli_query($koneksi, "SELECT COALESCE(SUM(total),0) AS tot FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if ($rb && ($rbrow = mysqli_fetch_assoc($rb))) { $total_barang = (float)$rbrow['tot']; }
        $rj = mysqli_query($koneksi, "SELECT COALESCE(SUM(total),0) AS tot FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if ($rj && ($rjrow = mysqli_fetch_assoc($rj))) { $total_service = (float)$rjrow['tot']; }
    }
    $tot = $total_barang + $total_service;
    $net = $tot;

    // Additional variables for redesign templates
    $catatan_service = '';
    $tahun_kendaraan = '';
    if(!empty($no_service)) {
        $q_cat = mysqli_query($koneksi, "SELECT keterangan, km_skr, km_berikut FROM tblservice WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if($q_cat && $r_cat = mysqli_fetch_assoc($q_cat)) {
            $catatan_service = $r_cat['keterangan'] ?? '';
            $km_skr = $r_cat['km_skr'] ?? 0;
            $km_berikut = $r_cat['km_berikut'] ?? 0;
        }
        // Get tahun kendaraan
        if(!empty($no_polisi)) {
            $q_kend = mysqli_query($koneksi, "SELECT tahun FROM tblkendaraan WHERE nopolisi='".mysqli_real_escape_string($koneksi, $no_polisi)."'");
            if($q_kend && $r_kend = mysqli_fetch_assoc($q_kend)) {
                $tahun_kendaraan = $r_kend['tahun'] ?? '';
            }
        }
    }

    // Map active tab for redesign
    $rd_active_tab = 'detail';
    switch($active_tab) {
        case 'service-items': $rd_active_tab = 'barang'; break;
        case 'service-jasa': $rd_active_tab = 'jasa'; break;
        case 'workorder-details': $rd_active_tab = 'workorder'; break;
        case 'pickup-details': $rd_active_tab = 'pickup'; break;
        case 'service-actions': $rd_active_tab = 'actions'; break;
        case 'temuan-penawaran': $rd_active_tab = 'temuan'; break;
        default: $rd_active_tab = 'detail';
    }

    // Count items for badges
    $count_keluhan = 0;
    $sql_kel = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tbservis_keluhan_status WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
    if($sql_kel) $count_keluhan = mysqli_fetch_array($sql_kel)['c'] ?? 0;

    $count_wo = 0;
    $sql_wo = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tbservis_workorder WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
    if($sql_wo) $count_wo = mysqli_fetch_array($sql_wo)['c'] ?? 0;

    $count_barang = 0;
    $sql_brg = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
    if($sql_brg) $count_barang = mysqli_fetch_array($sql_brg)['c'] ?? 0;

    $count_jasa = 0;
    $sql_jasa_c = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
    if($sql_jasa_c) $count_jasa = mysqli_fetch_array($sql_jasa_c)['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Service Jemput - <?= htmlspecialchars($no_service ?: 'Baru') ?> | FIT MOTOR</title>

    <!-- Bootstrap & jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Leaflet for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <!-- Custom Redesign Styles -->
    <?php include '_template/_redesign_styles.php'; ?>

    <style>
    body {
        background: #f0f2f5;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .rd-page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    .rd-page-header {
        background: white;
        border-radius: var(--rd-radius);
        padding: 20px 24px;
        margin-bottom: 20px;
        box-shadow: var(--rd-shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .rd-page-title {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .rd-page-title h1 {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: var(--rd-text);
    }
    .rd-service-info {
        display: flex;
        align-items: center;
        gap: 24px;
        font-size: 14px;
        flex-wrap: wrap;
    }
    .rd-service-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .rd-service-info-item i {
        color: var(--rd-primary);
    }
    .rd-main-tabs {
        background: white;
        border-radius: var(--rd-radius);
        box-shadow: var(--rd-shadow);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .rd-main-tabs-nav {
        display: flex;
        border-bottom: 1px solid var(--rd-border);
        overflow-x: auto;
    }
    .rd-main-tab-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 16px 20px;
        color: var(--rd-text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 13px;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .rd-main-tab-link:hover {
        color: var(--rd-primary);
        background: var(--rd-bg-light);
        text-decoration: none;
    }
    .rd-main-tab-link.active {
        color: var(--rd-primary);
        border-bottom-color: var(--rd-primary);
        background: rgba(74, 144, 217, 0.05);
    }
    .rd-main-tab-link .rd-badge {
        font-size: 10px;
        padding: 2px 6px;
    }
    .rd-tab-content {
        display: none;
        padding: 24px;
    }
    .rd-tab-content.active {
        display: block;
    }
    .rd-quick-summary {
        background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
        color: white;
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }
    .rd-quick-summary-item {
        text-align: center;
    }
    .rd-quick-summary-item .value {
        font-size: 20px;
        font-weight: 700;
    }
    .rd-quick-summary-item .label {
        font-size: 11px;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    </style>
</head>
<body>
    <div class="rd-page-container">
        <!-- Page Header -->
        <div class="rd-page-header">
            <div class="rd-page-title">
                <a href="servis-reguler-jemput.php" class="rd-btn outline-warning">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="fa fa-truck"></i> Input Service Jemput</h1>
                    <small class="rd-text-muted">Kelola detail service jemput antar</small>
                </div>
            </div>
            <div class="rd-service-info">
                <div class="rd-service-info-item">
                    <i class="fa fa-hashtag"></i>
                    <strong><?= htmlspecialchars($no_service ?: 'BARU') ?></strong>
                </div>
                <div class="rd-service-info-item">
                    <i class="fa fa-motorcycle"></i>
                    <strong><?= htmlspecialchars($no_polisi ?: '-') ?></strong>
                </div>
                <div class="rd-service-info-item">
                    <i class="fa fa-calendar"></i>
                    <?= !empty($tanggal) ? $tanggal : date('d/m/Y') ?>
                </div>
                <span class="rd-badge solid-<?= $status_servis == 'bayar' ? 'success' : ($status_servis == 'proses' ? 'warning' : 'info') ?>">
                    <i class="fa fa-truck"></i> <?= strtoupper($status_servis) ?>
                </span>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="" enctype="multipart/form-data" id="formServisJemput">
            <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($rd_active_tab) ?>">
            <input type="hidden" name="current_tab" value="<?= htmlspecialchars($active_tab) ?>">
            <input type="hidden" name="txtcaribrg" value="<?= htmlspecialchars($txtcaribrg ?? '') ?>">
            <input type="hidden" name="txtcarisrv" value="<?= htmlspecialchars($txtcarisrv ?? '') ?>">
            <input type="hidden" name="txtcariwo" value="<?= htmlspecialchars($txtcariwo ?? '') ?>">

            <!-- Main Tabs Container -->
            <div class="rd-main-tabs">
                <!-- Quick Summary -->
                <div class="rd-quick-summary">
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_keluhan + $count_wo ?></div>
                        <div class="label">SPK</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_barang ?></div>
                        <div class="label">Barang</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value"><?= $count_jasa ?></div>
                        <div class="label">Jasa</div>
                    </div>
                    <div class="rd-quick-summary-item">
                        <div class="value">Rp <?= number_format($tot, 0, ',', '.') ?></div>
                        <div class="label">Total</div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="rd-main-tabs-nav">
                    <a href="#tab-detail" class="rd-main-tab-link <?= $rd_active_tab == 'detail' ? 'active' : '' ?>" data-tab="detail">
                        <i class="fa fa-info-circle"></i> Detail
                    </a>
                    <a href="#tab-pickup" class="rd-main-tab-link <?= $rd_active_tab == 'pickup' ? 'active' : '' ?>" data-tab="pickup">
                        <i class="fa fa-truck"></i> Jemput
                    </a>
                    <a href="#tab-workorder" class="rd-main-tab-link <?= $rd_active_tab == 'workorder' ? 'active' : '' ?>" data-tab="workorder">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <span class="rd-badge info"><?= $count_keluhan + $count_wo ?></span>
                    </a>
                    <a href="#tab-temuan" class="rd-main-tab-link <?= $rd_active_tab == 'temuan' ? 'active' : '' ?>" data-tab="temuan">
                        <i class="fa fa-search-plus"></i> Temuan
                    </a>
                    <a href="#tab-barang" class="rd-main-tab-link <?= $rd_active_tab == 'barang' ? 'active' : '' ?>" data-tab="barang">
                        <i class="fa fa-box"></i> Barang
                        <span class="rd-badge purple"><?= $count_barang ?></span>
                    </a>
                    <a href="#tab-jasa" class="rd-main-tab-link <?= $rd_active_tab == 'jasa' ? 'active' : '' ?>" data-tab="jasa">
                        <i class="fa fa-cogs"></i> Jasa
                        <span class="rd-badge success"><?= $count_jasa ?></span>
                    </a>
                    <a href="#tab-actions" class="rd-main-tab-link <?= $rd_active_tab == 'actions' ? 'active' : '' ?>" data-tab="actions">
                        <i class="fa fa-money-bill-wave"></i> Bayar
                    </a>
                </div>

                <!-- Tab Contents -->
                <div id="tab-detail" class="rd-tab-content <?= $rd_active_tab == 'detail' ? 'active' : '' ?>">
                    <?php include '_template/tab-detail-service-redesign.php'; ?>
                </div>

                <div id="tab-pickup" class="rd-tab-content <?= $rd_active_tab == 'pickup' ? 'active' : '' ?>">
                    <?php include '_template/tab-pickup-details-redesign.php'; ?>
                </div>

                <div id="tab-workorder" class="rd-tab-content <?= $rd_active_tab == 'workorder' ? 'active' : '' ?>">
                    <?php include '_template/tab-workorder-redesign.php'; ?>
                </div>

                <div id="tab-temuan" class="rd-tab-content <?= $rd_active_tab == 'temuan' ? 'active' : '' ?>">
                    <?php include '_template/tab-temuan-penawaran-redesign.php'; ?>
                </div>

                <div id="tab-barang" class="rd-tab-content <?= $rd_active_tab == 'barang' ? 'active' : '' ?>">
                    <?php include '_template/tab-item-barang-redesign.php'; ?>
                </div>

                <div id="tab-jasa" class="rd-tab-content <?= $rd_active_tab == 'jasa' ? 'active' : '' ?>">
                    <?php include '_template/tab-item-jasa-redesign.php'; ?>
                </div>

                <div id="tab-actions" class="rd-tab-content <?= $rd_active_tab == 'actions' ? 'active' : '' ?>">
                    <?php include '_template/tab-actions-redesign.php'; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Modals -->
    <?php
    // Include modals
    if(file_exists('_template/modal-search-keluhan.php')) {
        include '_template/modal-search-keluhan.php';
    }
    if(file_exists('_template/modal-input-barang-custom.php')) {
        include '_template/modal-input-barang-custom.php';
    }
    if(file_exists('_template/_modal_riwayat_kendaraan.php')) {
        include '_template/_modal_riwayat_kendaraan.php';
    }
    if(file_exists('_template/_modal_update_status_keluhan.php')) {
        include '_template/_modal_update_status_keluhan.php';
    }
    if(file_exists('_template/_modal_cancel_service.php')) {
        include '_template/_modal_cancel_service.php';
    }
    if(file_exists('modal-tambah-keluhan-baru.php')) {
        include 'modal-tambah-keluhan-baru.php';
    }
    if(file_exists('_template/_modal_tambah_keluhan_inline.php')) {
        include '_template/_modal_tambah_keluhan_inline.php';
    }
    if(file_exists('_template/modal-search-temuan.php')) {
        include '_template/modal-search-temuan.php';
    }
    if(file_exists('_template/modal-fastmoves-v2.php')) {
        include '_template/modal-fastmoves-v2.php';
    }
    if(file_exists('_template/modal-fastmoves-part.php')) {
        include '_template/modal-fastmoves-part.php';
    }
    if(file_exists('_template/modal-callbacks.php')) {
        include '_template/modal-callbacks.php';
    }

    // Render modal statistik pelanggan if function exists
    if(function_exists('renderModalStatistikPelanggan') && !empty($kode_pelanggan)) {
        renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
    }
    ?>

    <!-- Modal Update Status Work Order -->
    <div class="modal fade" id="modalUpdateStatusWO" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit"></i> Update Status Work Order</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
                    <div class="modal-body">
                        <input type="hidden" name="wo_id" id="wo_id">
                        <div class="form-group">
                            <label>Work Order:</label>
                            <input type="text" class="form-control" id="wo_text" readonly>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status_wo" id="status_wo" class="form-control">
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="tidak_selesai">Tidak Selesai</option>
                            </select>
                        </div>
                        <div class="form-group" id="keterangan_wo_group" style="display:none;">
                            <label>Keterangan:</label>
                            <textarea name="keterangan_wo" id="keterangan_wo" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btnupdatestatuswo" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Update Status Keluhan -->
    <div class="modal fade" id="modalUpdateStatusKeluhan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-edit"></i> Update Status Keluhan</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
                    <div class="modal-body">
                        <input type="hidden" name="keluhan_id" id="keluhan_id">
                        <div class="form-group">
                            <label>Keluhan:</label>
                            <input type="text" class="form-control" id="keluhan_text" readonly>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status_keluhan" id="status_keluhan" class="form-control">
                                <option value="datang">Datang</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="tidak_selesai">Tidak Selesai</option>
                            </select>
                        </div>
                        <div class="form-group" id="keterangan_keluhan_group" style="display:none;">
                            <label>Keterangan:</label>
                            <textarea name="keterangan_keluhan" id="keterangan_keluhan" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Tab Navigation
    $(document).ready(function() {
        // Tab click handler
        $('.rd-main-tab-link').on('click', function(e) {
            e.preventDefault();
            var tabId = $(this).attr('href');
            var tabName = $(this).data('tab');

            // Update active states
            $('.rd-main-tab-link').removeClass('active');
            $(this).addClass('active');
            $('.rd-tab-content').removeClass('active');
            $(tabId).addClass('active');

            // Update hidden tab input
            $('input[name="tab"]').val(tabName);

            // Update URL without reload
            var newUrl = window.location.pathname + '?snoserv=<?= urlencode($no_service) ?>&tab=' + tabName;
            history.pushState(null, '', newUrl);
        });

        // Status handlers
        $('#status_keluhan').on('change', function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_keluhan_group').slideDown();
            } else {
                $('#keterangan_keluhan_group').slideUp();
            }
        });

        $('#status_wo').on('change', function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_wo_group').slideDown();
            } else {
                $('#keterangan_wo_group').slideUp();
            }
        });
    });

    // Global helper functions
    function toggleCardBody(header) {
        var body = header.nextElementSibling;
        header.classList.toggle('collapsed');
        body.style.display = body.style.display === 'none' ? 'block' : 'none';
    }

    function showStatistikPelanggan() {
        if(jQuery('#modalStatistikPelanggan').length) {
            jQuery('#modalStatistikPelanggan').modal('show');
        } else {
            alert('Modal statistik pelanggan belum tersedia.');
        }
    }

    function showRiwayatKendaraan() {
        if(jQuery('#modalRiwayatKendaraan').length) {
            jQuery('#modalRiwayatKendaraan').modal('show');
        } else {
            alert('Modal riwayat kendaraan tidak tersedia');
        }
    }

    function updateStatusKeluhan(id, text) {
        jQuery('#keluhan_id').val(id);
        jQuery('#keluhan_text').val(text || '');
        jQuery('#modalUpdateStatusKeluhan').modal('show');
    }

    function updateStatusWO(id, nama) {
        jQuery('#wo_id').val(id);
        jQuery('#wo_text').val(nama || '');
        jQuery('#modalUpdateStatusWO').modal('show');
    }

    function printPickupSchedule() {
        window.print();
    }

    function showNotifV2(message, type) {
        type = type || 'info';
        var bgColor = type === 'success' ? 'var(--rd-success)' :
                      type === 'warning' ? 'var(--rd-warning)' :
                      type === 'danger' ? 'var(--rd-danger)' : 'var(--rd-info)';

        var notif = $('<div class="fm-notif">' + message + '</div>');
        notif.css({
            position: 'fixed', top: '20px', right: '20px', padding: '12px 20px',
            background: bgColor, color: 'white', borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)', zIndex: 9999
        });
        $('body').append(notif);
        setTimeout(function() { notif.fadeOut(300, function() { notif.remove(); }); }, 2000);
    }
    </script>

    <!-- OSRM Route Calculator Script -->
    <script src="assets/js/osrm-route-calculator.js"></script>
</body>
</html>
