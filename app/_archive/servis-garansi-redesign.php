<?php
	session_start();
	
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";
		include_once "../lib/rbac.php";
		rbac_require_any(array('lihat_servis_garansi_read','servis_garansi_read','servis_menu_read','service_read'));
		include "_include_customer_vehicle_sync.php";
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
    
    // Fallback if index.php passes no_service instead of snoserv
    if (empty($no_service) && isset($_GET['no_service'])) { $no_service = $_GET['no_service']; }
    
    // Guard: redirect to RST page if service already finished/paid
    if (!empty($no_service)) {
        $__ns = mysqli_real_escape_string($koneksi, $no_service);
        $__q = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='".$__ns."' LIMIT 1");
        if ($__q && ($__r = mysqli_fetch_assoc($__q))) {
            $__st = strtolower($__r['status_servis'] ?? '');
            if ($__st === 'selesai' || $__st === 'bayar') {
                $__redir = 'servis-garansi-rst.php?snoserv=' . urlencode($no_service);
                if (isset($_GET['tab']) && $_GET['tab'] !== '') { $__redir .= '&tab=' . urlencode($_GET['tab']); }
                header('Location: ' . $__redir);
                exit;
            }
        }
    }
    $txtcaribrg=$_GET['kd'] ?? '';
    $txtcarisrv=$_GET['kdjasa'] ?? '';
    $txtcariwo=$_GET['kdwo'] ?? '';
    
    // Handler untuk submit form
    if(isset($_POST['btnsimpan'])) {
        // Generate nomor service jika belum ada
        if(empty($no_service)) {
            $tanggal_service = date('Y-m-d');
            
            // Generate nomor service untuk garansi
            $prefix_service = 'GAR-' . date('Ymd') . '-';
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
            
            // Insert data service garansi
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
                '$no_polisi', '$keluhan', 'datang', 'garansi', '$_nama', '$kd_cabang', NOW()
            )";
            
            if(mysqli_query($koneksi, $query_insert_service)) {
                // Insert ke tabel antrian dengan prioritas tinggi untuk garansi
                $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                    no_service, no_antrian, tanggal, jam, no_pelanggan, no_polisi, 
                    status_antrian, tipe_service, prioritas, created_at
                ) VALUES (
                    '$no_service', '$no_antrian', '$tanggal_service', '$jam_input', 
                    '$kode_pelanggan', '$no_polisi', 'menunggu', 'garansi', 'tinggi', NOW()
                )";
                
                if(mysqli_query($koneksi, $query_insert_antrian)) {
                    echo "<script>
                        alert('Service Garansi berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian\\n(Prioritas Tinggi)');
                        window.location.href = 'servis-garansi-redesign.php?snoserv=$no_service';
                    </script>";
                }
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
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
                
    // Get customer data
    if(!empty($kode_pelanggan)) {
        $customerBundle = fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $no_polisi);
        $namapelanggan=$customerBundle['namapelanggan'] ?? '';
    } else {
        $namapelanggan = '';
    }

    // Get vehicle data
    if(!empty($no_polisi)) {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $customerRow = $bundle['customer'] ?? [];
        $pemilik=$vehicleRow['pemilik'] ?? '';
        $jenis=$vehicleRow['jenis'] ?? '';
        $merek=$vehicleRow['merek'] ?? ($vehicleRow['tipe'] ?? '');
        $warna=$vehicleRow['warna'] ?? '';
        $no_rangka=$vehicleRow['no_rangka'] ?? '';
        $no_mesin=$vehicleRow['no_mesin'] ?? '';
        if (empty($namapelanggan) && !empty($customerRow['namapelanggan'])) {
            $namapelanggan = $customerRow['namapelanggan'];
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

    // Initialize workorder name variable
    $txtnamawo = '';
    if(!empty($txtcariwo)) {
        $cari_wo = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcariwo'");
        $tm_wo = mysqli_fetch_array($cari_wo);
        $txtnamawo = $tm_wo['nama_wo'] ?? '';
    }

    // ========== HANDLER: UPDATE MECHANIC DATA (GARANSI) ==========
    if(isset($_POST['btnupdatemekanik'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';

            // Get mechanic data from form
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
                    alert('Data mekanik garansi berhasil diupdate!');
                    window.location='servis-garansi-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions';
                </script>";
            } else {
                echo "<script>alert('Error update data mekanik: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // ========== HANDLER: ADD ITEM BARANG (GARANSI) ==========
    if (isset($_POST['btnadd'])) {
        $no_service = $_POST['txtnosrv'] ?? $no_service;
        $kd = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
        $qty = (int)($_POST['txtqty'] ?? 1);
        $pot = 0; 
        
        if (empty($no_service)) {
            echo "<script>alert('Harap simpan header service terlebih dahulu untuk mendapatkan No. Service.');</script>";
        } else if (!empty($kd) && $qty > 0) {
            $harga = 0;
            $rh = mysqli_query($koneksi, "SELECT hargajual FROM tblitem WHERE noitem='$kd'");
            if ($rh && ($rhrow = mysqli_fetch_array($rh))) {
                $harga = (float)($rhrow['hargajual'] ?? 0);
            }
            
            // === DISCOUNT LOGIC IMPLEMENTATION ===
            $diskon_source = 'none';
            $diskon_persen = 0;
            $diskon_nominal = 0;
            $id_promo = 'NULL';
            $pot = 0; 
            
            // 1. Check Promo Periode (Priority)
            $tgl_cek = date('Y-m-d'); 
            if(!empty($tanggal_service)) $tgl_cek = $tanggal_service;
            
            $q_promo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                              WHERE target_type = 'barang' 
                                              AND (target_id = '$kd' OR FIND_IN_SET('$kd', target_id))
                                              AND status_aktif = 1 
                                              AND '$tgl_cek' BETWEEN tanggal_mulai AND tanggal_selesai 
                                              ORDER BY nilai_promo DESC LIMIT 1");
                                              
            if($q_promo && mysqli_num_rows($q_promo) > 0) {
                $prow = mysqli_fetch_assoc($q_promo);
                $diskon_source = 'promo';
                $id_promo = $prow['id_promo'];
                if($prow['tipe_promo'] == 'nominal') {
                    $diskon_nominal = $prow['nilai_promo'];
                    $diskon_persen = ($harga > 0) ? ($diskon_nominal / $harga * 100) : 0;
                } else {
                    $diskon_persen = $prow['nilai_promo'];
                    $diskon_nominal = $harga * ($diskon_persen / 100);
                }
            } 
            // 2. Check Member Discount (If no promo)
            else {
                // Get pel properti
                $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
                $cust_row = mysqli_fetch_assoc($cust_query);
                $no_pel = $cust_row['no_pelanggan'] ?? '';
                
                if(!empty($no_pel)) {
                    // Check exclude 
                    $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $kd);
                    if(!$is_excluded) {
                        $mem_disc = getMemberDiscountForItem($koneksi, $no_pel, $kd, 'barang');
                        if($mem_disc > 0) {
                            $diskon_source = 'member';
                            $diskon_persen = $mem_disc;
                            $diskon_nominal = $harga * ($diskon_persen / 100);
                        }
                    }
                }
            }
            
            // Calculate Total
            $total_diskon_amt = $diskon_nominal * $qty;
            $subtotal = ($harga * $qty) - $total_diskon_amt;
            
            // Insert with discount columns
            mysqli_query($koneksi, "INSERT INTO tblservis_barang 
                (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, 
                 diskon_source, diskon_persen, diskon_nominal, id_promo) 
                VALUES 
                ('$no_service', 0, '$kd', '$qty', 0, '$harga', '$diskon_persen', '$subtotal',
                 '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
        }
        // Redirect back
        header('Location: servis-garansi-redesign.php?snoserv=' . urlencode($no_service) . '&tab=service-items#service-items');
        exit;
    }

    // ========== HANDLER: ADD ITEM JASA (GARANSI) ==========
    if (isset($_POST['btnaddsrv'])) {
        $no_service = $_POST['txtnosrv'] ?? $no_service;
        $kdj = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
        $potsrv = (float)($_POST['txtpotsrv'] ?? 0);
        
        if (empty($no_service)) {
            echo "<script>alert('Harap simpan header service terlebih dahulu untuk mendapatkan No. Service.');</script>";
        } else if (!empty($kdj)) {
            $harga = 0; $waktu = 0;
            $rj = mysqli_query($koneksi, "SELECT harga, waktu FROM tblitem_jasa WHERE noitem='$kdj'");
            if ($rj && ($rjrow = mysqli_fetch_array($rj))) {
                $harga = (float)($rjrow['harga'] ?? 0);
                $waktu = (int)($rjrow['waktu'] ?? 0);
            } else {
                // Fallback ke tblitem
                $rj2 = mysqli_query($koneksi, "SELECT hargajual, jasawaktu FROM tblitem WHERE noitem='$kdj'");
                if ($rj2 && ($rjrow2 = mysqli_fetch_array($rj2))) {
                    $harga = (float)($rjrow2['hargajual'] ?? 0);
                    $waktu = (int)($rjrow2['jasawaktu'] ?? 0);
                }
            }
            
            // === DISCOUNT LOGIC IMPLEMENTATION ===
            $diskon_source = 'none';
            $diskon_persen = 0;
            $diskon_nominal = 0;
            $id_promo = 'NULL';
            $potsrv = 0; 
            
            // 1. Check Promo Periode (Priority)
            $tgl_cek = date('Y-m-d'); 
            if(!empty($tanggal_service)) $tgl_cek = $tanggal_service;
            
            $q_promo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                              WHERE target_type = 'jasa' 
                                              AND (target_id = '$kdj' OR FIND_IN_SET('$kdj', target_id))
                                              AND status_aktif = 1 
                                              AND '$tgl_cek' BETWEEN tanggal_mulai AND tanggal_selesai 
                                              ORDER BY nilai_promo DESC LIMIT 1");
                                              
            if($q_promo && mysqli_num_rows($q_promo) > 0) {
                $prow = mysqli_fetch_assoc($q_promo);
                $diskon_source = 'promo';
                $id_promo = $prow['id_promo'];
                if($prow['tipe_promo'] == 'nominal') {
                    $diskon_nominal = $prow['nilai_promo'];
                    $diskon_persen = ($harga > 0) ? ($diskon_nominal / $harga * 100) : 0;
                } else {
                    $diskon_persen = $prow['nilai_promo'];
                    $diskon_nominal = $harga * ($diskon_persen / 100);
                }
            } 
            // 2. Check Member Discount (If no promo)
            else {
                // Get pel properti
                $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
                $cust_row = mysqli_fetch_assoc($cust_query);
                $no_pel = $cust_row['no_pelanggan'] ?? '';
                
                if(!empty($no_pel)) {
                    // Check exclude 
                    $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $kdj);
                    if(!$is_excluded) {
                        $mem_disc = getMemberDiscountForItem($koneksi, $no_pel, $kdj, 'jasa');
                        if($mem_disc > 0) {
                            $diskon_source = 'member';
                            $diskon_persen = $mem_disc;
                            $diskon_nominal = $harga * ($diskon_persen / 100);
                        }
                    }
                }
            }
            
            $total = $harga - $diskon_nominal;
            
            // Cek apakah kolom waktu tersedia
            $has_waktu = false;
            $chk = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
            if ($chk && mysqli_num_rows($chk) > 0) { $has_waktu = true; }
            
            if ($has_waktu) {
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa 
                    (no_service, nobaris, no_item, harga, waktu, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo) 
                    VALUES 
                    ('$no_service', 0, '$kdj', '$harga', '$waktu', '$diskon_persen', '$total',
                     '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
            } else {
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa 
                    (no_service, nobaris, no_item, harga, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo)
                    VALUES 
                    ('$no_service', 0, '$kdj', '$harga', '$diskon_persen', '$total',
                     '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
            }
        }
        // Redirect back
        header('Location: servis-garansi-redesign.php?snoserv=' . urlencode($no_service) . '&tab=service-jasa#service-jasa');
        exit;
    }

    // ========== HANDLER: ADD KELUHAN TO SPK (GARANSI) ==========
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

            echo"<script>
                alert('Keluhan berhasil ditambahkan ke SPK Garansi!');
                window.location=('servis-garansi-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo');
            </script>";
        } else {
            echo"<script>
                alert('Keluhan tidak boleh kosong!');
                window.history.back();
            </script>";
        }
    }

    // ========== HANDLER: CARI ITEM BARANG ==========
    if (isset($_POST['btncari'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

        // Redirect to item search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-item-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcaribrg) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=items&_from=garansi');</script>";
        exit;
    }

    // ========== HANDLER: CARI JASA ==========
    if (isset($_POST['btncarisrv'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');

        // Redirect to jasa search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jasa-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcarisrv) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=jasa&_from=garansi');</script>";
        exit;
    }

    // ========== HANDLER: SEARCH WORKORDER (GARANSI) ==========
    if(isset($_POST['btncariwo'])) {
        $no_service = $_POST['txtnosrv'];
        $txtcariwo = $_POST['txtcariwo'];
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        // Update KM data before redirecting
        if(!empty($no_service)) {
            mysqli_query($koneksi,"UPDATE tblservice
                                   SET km_skr='$km_skr', km_berikut='$km_berikut'
                                   WHERE no_service='$no_service'");
        }

        // Redirect to workorder search page
        $cbocari = "";
        $cbourut = "52";
        echo"<script>window.location=('servis-add-workorder-cari.php?snoserv=$no_service&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";
    }

    // ========== HANDLER: ADD WORKORDER TO SPK (GARANSI) ==========
    if(isset($_POST['btnaddworkorder'])) {
        $no_service = $_POST['txtnosrv'];
        $kode_wo = $_POST['txtcariwo'];

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        if(!empty($kode_wo)) {
            // Check if workorder already exists in SPK
            $check_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbservis_workorder
                                               WHERE no_service='$no_service' AND kode_wo='$kode_wo'");
            $check_result = mysqli_fetch_array($check_wo);

            if($check_result['count'] == 0) {
                // Verify workorder exists in master
                $verify_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
                $verify_result = mysqli_fetch_array($verify_wo);

                if($verify_result['count'] > 0) {
                    // Insert workorder to SPK with status garansi
                    mysqli_query($koneksi,"INSERT INTO tbservis_workorder
                                          (no_service, kode_wo, status_pengerjaan)
                                          VALUES
                                          ('$no_service','$kode_wo','garansi')");

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
                                // Garansi - gratis (harga 0, potongan 100%)
                                $check_jasa_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
                                if(mysqli_num_rows($check_jasa_waktu) > 0) {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, no_item, harga, waktu, potongan, total)
                                                          VALUES
                                                          ('$no_service', '{$detail['kode_barang']}', '{$detail['harga']}', '$waktu', '100', '0')");
                                } else {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, no_item, harga, potongan, total)
                                                          VALUES
                                                          ('$no_service', '{$detail['kode_barang']}', '{$detail['harga']}', '100', '0')");
                                }
                            }
                        } else {
                            // Barang - Insert to tblservis_barang
                            $check_barang = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tblservis_barang
                                                                   WHERE no_service='$no_service' AND no_item='{$detail['kode_barang']}'");
                            $check_barang_result = mysqli_fetch_array($check_barang);

                            if($check_barang_result['count'] == 0) {
                                // Garansi - gratis (potongan 100%)
                                mysqli_query($koneksi,"INSERT INTO tblservis_barang
                                                      (no_service, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                                      VALUES
                                                      ('$no_service', '{$detail['kode_barang']}', '{$detail['jumlah']}', '0', '{$detail['harga']}', '100', '0')");
                            }
                        }
                    }

                    // Update KM data
                    mysqli_query($koneksi,"UPDATE tblservice
                                           SET km_skr='$km_skr', km_berikut='$km_berikut'
                                           WHERE no_service='$no_service'");

                    echo"<script>
                        alert('Work Order GARANSI berhasil ditambahkan ke SPK!\\nSemua item di-set GRATIS (potongan 100%).');
                        window.location=('servis-garansi-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=');
                    </script>";
                } else {
                    echo"<script>
                        alert('Kode Work Order tidak ditemukan di master!\\nSilakan periksa kembali kode WO.');
                        window.history.back();
                    </script>";
                }
            } else {
                echo"<script>
                    alert('Work Order ini sudah ada di SPK Garansi!');
                    window.location=('servis-garansi-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=');
                </script>";
            }
        } else {
            echo"<script>
                alert('Kode Work Order tidak boleh kosong!');
                window.history.back();
            </script>";
        }
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
            $persen_kerja1 = $existing_data['persen_kerja1'] ?? 0;
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_kerja2 = $existing_data['persen_kerja2'] ?? 0;
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_kerja3 = $existing_data['persen_kerja3'] ?? 0;
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_kerja4 = $existing_data['persen_kerja4'] ?? 0;
        }
    }
    
    // ========== HANDLER: PAYMENT/BAYAR (GARANSI) ==========
    if(isset($_POST['btnbayar'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        
        if(!empty($no_service)) {
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';
            
            // Get payment data
            $tipe_pembayaran = $_POST['metode_pembayaran'] ?? 'Tunai';
            $txttotal_jasa = str_replace(['.', ','], '', $_POST['txttotal_jasa'] ?? '0');
            $txttotal_barang = str_replace(['.', ','], '', $_POST['txttotal_barang'] ?? '0');
            
            // Discount Inputs
            $diskon_member = floatval($_POST['txtdiskon_member'] ?? 0);
            $pot_tambahan_persen = floatval($_POST['txtpotfaktur_persen'] ?? 0);
            $total_diskon_persen = $diskon_member + $pot_tambahan_persen;
            
            // Calculate Totals
            $subtotal = $txttotal_jasa + $txttotal_barang;
            $diskon_nominal = $subtotal * ($total_diskon_persen / 100);
            
            // PPN
            $pajak_persen = floatval($_POST['txtpajak_persen'] ?? 0);
            $ppn_nominal = ($subtotal - $diskon_nominal) * ($pajak_persen / 100);
            
            $total_akhir = ($subtotal - $diskon_nominal) + $ppn_nominal;
            
            $jumlah_bayar = str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0');
            $kembalian = $jumlah_bayar - $total_akhir;
            
             // Validate payment amount (allow 0 for full warranty)
            if($jumlah_bayar < $total_akhir && $total_akhir > 0) {
                 echo "<script>alert('Jumlah pembayaran kurang!'); window.history.back();</script>";
                 exit;
            }

            // Update service status to bayar
            $update_payment = "UPDATE tblservice SET
                status_servis = 'bayar',
                tipe_pembayaran = '$tipe_pembayaran',
                total_jasa = '$txttotal_jasa',
                total_barang = '$txttotal_barang',
                subtotal = '$subtotal',
                
                diskon = '$diskon_nominal',
                diskon_persen = '$total_diskon_persen',
                diskon_nom = '$diskon_nominal',
                ppn_persen = '$pajak_persen',
                ppn_nom = '$ppn_nominal',
                
                total_akhir = '$total_akhir',
                dibayar = '$jumlah_bayar',
                kembali = '$kembalian',
                tanggal_bayar = NOW(),
                updated_at = NOW()
                WHERE no_service = '$no_service'";
            
            if(mysqli_query($koneksi, $update_payment)) {
                // 🆕 AUTO-UPDATE STATISTIK PELANGGAN, MEMBER TIER & HISTORY SERVICE
                $get_customer = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
                if ($get_customer && $customer_row = mysqli_fetch_assoc($get_customer)) {
                    $no_pelanggan_bayar = $customer_row['no_pelanggan'];
                    if (!empty($no_pelanggan_bayar)) {
                        // Gunakan fungsi processAfterPayment untuk update semua data
                        if (function_exists('processAfterPayment')) {
                            $payment_result = processAfterPayment($koneksi, $no_pelanggan_bayar, $no_service, 'garansi');
                            // Log jika naik tier
                            if ($payment_result['naik_tier']) {
                                error_log("✅ [GARANSI] Customer $no_pelanggan_bayar naik tier: " . json_encode($payment_result['tier_info']));
                            }
                            error_log("✅ [GARANSI] Statistik & history pelanggan updated for: $no_pelanggan_bayar (Service: $no_service)");
                        } elseif (function_exists('updateStatistikPelangganAfterPayment')) {
                            // Fallback ke fungsi lama jika fungsi baru belum ada
                            updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan_bayar, $no_service);
                            error_log("✅ [GARANSI] Statistik pelanggan updated for: $no_pelanggan_bayar (Service: $no_service)");
                        } else {
                            error_log("⚠️ [GARANSI] Function updateStatistikPelangganAfterPayment not found for service: $no_service");
                        }
                    }
                }
                
                // Update antrian status
                mysqli_query($koneksi, "UPDATE tb_antrian_servis SET 
                    status_antrian = 'selesai',
                    jam_selesai = NOW(),
                    updated_at = NOW()
                    WHERE no_service = '$no_service'");
                
                echo "<script>
                    alert('Pembayaran service garansi berhasil!\\nNo. Service: $no_service\\nTotal: Rp " . number_format($total_akhir, 0, ',', '.') . "');
                    window.location='servis-garansi-redesign.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions';
                </script>";
                exit;
            } else {
                echo "<script>alert('Error pembayaran: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
                exit;
            }
        }
    }
    
    // Initialize other variables

    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';
    $prioritas_wo = 'urgent'; // Garansi usually urgent
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
            if(!isset($km_skr)) $km_skr = $r_cat['km_skr'] ?? 0;
            if(!isset($km_berikut)) $km_berikut = $r_cat['km_berikut'] ?? 0;
        }
        // Get tahun kendaraan
        if(!empty($no_polisi)) {
            $q_kend = mysqli_query($koneksi, "SELECT tahun FROM tblkendaraan WHERE nopolisi='".mysqli_real_escape_string($koneksi, $no_polisi)."'");
            if($q_kend && $r_kend = mysqli_fetch_assoc($q_kend)) {
                $tahun_kendaraan = $r_kend['tahun'] ?? '';
            }
        }
    }

    // Set active tab based on URL parameter
    $active_tab = 'service-details';
    if (isset($_GET['tab'])) {
        switch($_GET['tab']) {
            case 'items': $active_tab = 'service-items'; break;
            case 'jasa': $active_tab = 'service-jasa'; break;
            case 'workorder': $active_tab = 'workorder-details'; break;
            case 'temuan': $active_tab = 'temuan-penawaran'; break;
            case 'actions': $active_tab = 'service-actions'; break;
        }
    }

    // Map active tab for redesign
    $rd_active_tab = 'detail';
    switch($active_tab) {
        case 'service-items': $rd_active_tab = 'barang'; break;
        case 'service-jasa': $rd_active_tab = 'jasa'; break;
        case 'workorder-details': $rd_active_tab = 'workorder'; break;
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
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Garansi - <?= htmlspecialchars($no_service ?: 'Baru') ?> | FIT MOTOR</title>

    <!-- Bootstrap & jQuery -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

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
        color: var(--rd-success);
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
        padding: 16px 24px;
        color: var(--rd-text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 13px;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .rd-main-tab-link:hover {
        color: var(--rd-success);
        background: var(--rd-bg-light);
        text-decoration: none;
    }
    .rd-main-tab-link.active {
        color: var(--rd-success);
        border-bottom-color: var(--rd-success);
        background: rgba(39, 174, 96, 0.05);
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
        background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);
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
    .rd-garansi-badge {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    </style>
</head>
<body>
    <div class="rd-page-container">
        <!-- Page Header -->
        <div class="rd-page-header">
            <div class="rd-page-title">
                <a href="servis-reguler.php" class="rd-btn outline-success">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="fa fa-shield-alt"></i> Service Garansi</h1>
                    <small class="rd-text-muted">Service dalam masa garansi - Otomatis 100% Ditanggung</small>
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
                <span class="rd-garansi-badge">
                    <i class="fa fa-shield-alt"></i> GARANSI
                </span>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="" enctype="multipart/form-data" id="formServisGaransi">
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
                        <div class="value">Rp 0</div>
                        <div class="label">Total (Garansi)</div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="rd-main-tabs-nav">
                    <a href="#tab-detail" class="rd-main-tab-link <?= $rd_active_tab == 'detail' ? 'active' : '' ?>" data-tab="detail">
                        <i class="fa fa-info-circle"></i> Detail Service
                    </a>
                    <a href="#tab-workorder" class="rd-main-tab-link <?= $rd_active_tab == 'workorder' ? 'active' : '' ?>" data-tab="workorder">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <span class="rd-badge info"><?= $count_keluhan + $count_wo ?></span>
                    </a>
                    <a href="#tab-temuan" class="rd-main-tab-link <?= $rd_active_tab == 'temuan' ? 'active' : '' ?>" data-tab="temuan">
                        <i class="fa fa-search-plus"></i> Temuan
                    </a>
                    <a href="#tab-barang" class="rd-main-tab-link <?= $rd_active_tab == 'barang' ? 'active' : '' ?>" data-tab="barang">
                        <i class="fa fa-box"></i> Item Barang
                        <span class="rd-badge purple"><?= $count_barang ?></span>
                    </a>
                    <a href="#tab-jasa" class="rd-main-tab-link <?= $rd_active_tab == 'jasa' ? 'active' : '' ?>" data-tab="jasa">
                        <i class="fa fa-cogs"></i> Item Jasa
                        <span class="rd-badge success"><?= $count_jasa ?></span>
                    </a>
                    <a href="#tab-actions" class="rd-main-tab-link <?= $rd_active_tab == 'actions' ? 'active' : '' ?>" data-tab="actions">
                        <i class="fa fa-check-circle"></i> Selesai
                    </a>
                </div>

                <!-- Tab Contents -->
                <div id="tab-detail" class="rd-tab-content <?= $rd_active_tab == 'detail' ? 'active' : '' ?>">
                    <?php include '_template/tab-detail-service-redesign.php'; ?>
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
</body>
</html>

