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
        // Tentukan tab aktif agar konsisten dengan versi RST
        $active_tab = 'service-details';
        $tab_req = isset($_GET['tab']) ? $_GET['tab'] : (isset($_POST['tab']) ? $_POST['tab'] : null);
        if ($tab_req !== null) {
            switch($tab_req) {
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
                case 'temuan':
                    $active_tab = 'temuan-penawaran';
                    break;
                default:
                    $active_tab = 'service-details';
            }
        } elseif (!empty($txtcaribrg)) {
            $active_tab = 'service-items';
        } elseif (!empty($txtcarisrv)) {
            $active_tab = 'service-jasa';
        } elseif (!empty($txtcariwo)) {
            $active_tab = 'workorder-details';
        }

        function _tbl_exists_local($koneksi, $name) {
            $name = mysqli_real_escape_string($koneksi, $name);
            $res = mysqli_query($koneksi, "SHOW TABLES LIKE '{$name}'");
            return ($res && mysqli_num_rows($res) > 0);
        }

        function _col_exists_local($koneksi, $table, $col) {
            $table = mysqli_real_escape_string($koneksi, $table);
            $col = mysqli_real_escape_string($koneksi, $col);
            $res = mysqli_query($koneksi, "SHOW COLUMNS FROM {$table} LIKE '{$col}'");
            return ($res && mysqli_num_rows($res) > 0);
        }

        function _get_item_map_col($koneksi) {
            $chk = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_kategori_motor'");
            if ($chk && mysqli_num_rows($chk) > 0) return 'kd_kategori_motor';
            return 'kd_jenis_motor';
        }

        function _get_wo_map_col($koneksi) {
            $chk = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorder_jenis_motor LIKE 'kd_kategori_motor'");
            if ($chk && mysqli_num_rows($chk) > 0) return 'kd_kategori_motor';
            return 'kd_jenis_motor';
        }

        function _get_kd_kategori_motor_by_service($koneksi, $no_service) {
            $no_service = trim((string)$no_service);
            if ($no_service === '') return 0;
            $no_service_safe = mysqli_real_escape_string($koneksi, $no_service);

            // 1) Try view alias first (fast path)
            if (_tbl_exists_local($koneksi, 'view_service_kategori_motor')) {
                $q = mysqli_query($koneksi, "SELECT kd_kategori_motor FROM view_service_kategori_motor WHERE no_service='{$no_service_safe}'");
                if ($q && ($r = mysqli_fetch_assoc($q)) && isset($r['kd_kategori_motor'])) {
                    $kd = intval($r['kd_kategori_motor'] ?? 0);
                    if ($kd > 0) return $kd;
                }
            }

            // 2) Derive from master service + vehicle + kategori
            // tblservice.no_polisi -> tblkendaraan.nopolisi -> tblkendaraan.jenis (string) -> tbkategori_motor.kategori
            if (_tbl_exists_local($koneksi, 'tblservice') && _tbl_exists_local($koneksi, 'tblkendaraan') && _tbl_exists_local($koneksi, 'tbkategori_motor')) {
                $q = mysqli_query($koneksi, "SELECT km.id AS kd_kategori_motor
                                            FROM tblservice s
                                            LEFT JOIN tblkendaraan v ON s.no_polisi = v.nopolisi
                                            LEFT JOIN tbtipe_motor tm
                                                ON (
                                                    (v.kode_tipe IS NOT NULL AND v.kode_tipe <> 0 AND tm.kode_tipe = v.kode_tipe)
                                                    OR (UPPER(TRIM(tm.tipe)) = UPPER(TRIM(v.tipe)))
                                                )
                                            LEFT JOIN tbkategori_motor km ON km.id = tm.kode_kategori
                                            WHERE s.no_service='{$no_service_safe}'
                                            LIMIT 1");
                if ($q && ($r = mysqli_fetch_assoc($q)) && isset($r['kd_kategori_motor'])) {
                    return intval($r['kd_kategori_motor'] ?? 0);
                }

                if (_tbl_exists_local($koneksi, 'tbtipe_motor')) {
                    $qv = mysqli_query($koneksi, "SELECT v.kode_tipe, v.tipe
                                                FROM tblservice s
                                                LEFT JOIN tblkendaraan v ON s.no_polisi = v.nopolisi
                                                WHERE s.no_service='{$no_service_safe}'
                                                LIMIT 1");
                    if ($qv && ($rv = mysqli_fetch_assoc($qv))) {
                        $kode_tipe = intval($rv['kode_tipe'] ?? 0);
                        $tipe_raw = trim((string)($rv['tipe'] ?? ''));

                        if ($kode_tipe > 0) {
                            $q2 = mysqli_query($koneksi, "SELECT km.id AS kd_kategori_motor
                                                        FROM tbtipe_motor tm
                                                        LEFT JOIN tbkategori_motor km ON km.id = tm.kode_kategori
                                                        WHERE tm.kode_tipe={$kode_tipe}
                                                        LIMIT 1");
                            if ($q2 && ($r2 = mysqli_fetch_assoc($q2)) && isset($r2['kd_kategori_motor'])) {
                                $kd2 = intval($r2['kd_kategori_motor'] ?? 0);
                                if ($kd2 > 0) return $kd2;
                            }
                        }

                        if ($tipe_raw !== '') {
                            $tipe_norm = strtoupper(preg_replace('/[^A-Z0-9]/', '', $tipe_raw));
                            $tipe_norm_safe = mysqli_real_escape_string($koneksi, $tipe_norm);
                            $q3 = mysqli_query($koneksi, "SELECT km.id AS kd_kategori_motor
                                                        FROM tbtipe_motor tm
                                                        LEFT JOIN tbkategori_motor km ON km.id = tm.kode_kategori
                                                        WHERE (
                                                            UPPER(REPLACE(REPLACE(REPLACE(TRIM(tm.tipe),' ',''),'-',''),'_','')) LIKE '{$tipe_norm_safe}%'
                                                            OR '{$tipe_norm_safe}' LIKE CONCAT(UPPER(REPLACE(REPLACE(REPLACE(TRIM(tm.tipe),' ',''),'-',''),'_','')), '%')
                                                        )
                                                        ORDER BY ABS(CHAR_LENGTH(UPPER(REPLACE(REPLACE(REPLACE(TRIM(tm.tipe),' ',''),'-',''),'_',''))) - CHAR_LENGTH('{$tipe_norm_safe}')) ASC,
                                                                 CHAR_LENGTH(UPPER(REPLACE(REPLACE(REPLACE(TRIM(tm.tipe),' ',''),'-',''),'_',''))) ASC
                                                        LIMIT 1");
                            if ($q3 && ($r3 = mysqli_fetch_assoc($q3)) && isset($r3['kd_kategori_motor'])) {
                                return intval($r3['kd_kategori_motor'] ?? 0);
                            }
                        }
                    }
                }
            }

            // 3) Legacy fallback
            if (_tbl_exists_local($koneksi, 'view_service_jenis_motor')) {
                $q = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
                if ($q && ($r = mysqli_fetch_assoc($q)) && isset($r['kd_jenis_motor'])) {
                    return intval($r['kd_jenis_motor'] ?? 0);
                }
            }

            return 0;
        }
        
        // Handler untuk submit form
        if(isset($_POST['btnsimpan'])) {
            // Generate nomor service jika belum ada
            if(empty($no_service)) {
                $tanggal_service = date('Y-m-d');
                $jam_service = date('H:i:s');
                
                // Generate nomor service
                $prefix_service = 'SRV-' . date('Ymd') . '-';
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
                
                // Insert data service
                $tanggal_input = $_POST['id-date-picker-1'] ?? date('d/m/Y');
                $tanggal_service = date('Y-m-d', strtotime(str_replace('/', '-', $tanggal_input)));
                $jam_input = $_POST['jam_service'] ?? date('H:i');
                $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
                $no_polisi = $_POST['no_polisi'] ?? '';
                $keluhan = $_POST['keluhan'] ?? '';
                
                $query_insert_service = "INSERT INTO tblservice (
                    no_service, tanggal, jam, no_pelanggan, no_polisi, keterangan, 
                    status_servis, id_user, kd_cabang, created_at
                ) VALUES (
                    '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan', 
                    '$no_polisi', '$keluhan', 'datang', '$id_user', '$kd_cabang', NOW()
                )";
                
                if(mysqli_query($koneksi, $query_insert_service)) {
                    // Insert ke tabel antrian
                    $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                        no_service, no_antrian, tanggal, jam_ambil, status_antrian, prioritas, created_at
                    ) VALUES (
                        '$no_service', '$no_antrian', '$tanggal_service', '$jam_input', 
                        'menunggu', 'normal', NOW()
                    )";
                    
                    if(mysqli_query($koneksi, $query_insert_antrian)) {
                        echo "<script>
                            alert('Service berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian');
                            window.location.href = 'servis-reguler.php';
                        </script>";
                    }
                } else {
                    echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                }
            } else {
                // Update existing service
                $tanggal_input = $_POST['id-date-picker-1'] ?? date('d/m/Y');
                $tanggal_service = date('Y-m-d', strtotime(str_replace('/', '-', $tanggal_input)));
                $jam_input = $_POST['jam_service'] ?? date('H:i');
                $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
                $no_polisi = $_POST['no_polisi'] ?? '';
                $keluhan = $_POST['keluhan'] ?? '';
                
                $query_update_service = "UPDATE tblservice SET 
                    tanggal = '$tanggal_service',
                    jam = '$jam_input',
                    no_pelanggan = '$kode_pelanggan',
                    no_polisi = '$no_polisi',
                    updated_at = NOW()
                    WHERE no_service = '$no_service'";
                
                if(mysqli_query($koneksi, $query_update_service)) {
                    // Update status antrian jika ada
                    $query_update_antrian = "UPDATE tb_antrian_servis SET 
                        tanggal = '$tanggal_service',
                        jam_ambil = '$jam_input',
                        updated_at = NOW()
                        WHERE no_service = '$no_service'";
                    mysqli_query($koneksi, $query_update_antrian);
                    
                    echo "<script>
                        alert('Service berhasil diupdate!\\nNomor Service: $no_service');
                        window.location.href = 'servis-reguler.php';
                    </script>";
                    exit;
                } else {
                    echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
                    exit;
                }
            }
        }
        
        // ========== HANDLER: PAYMENT PROCESSING FOR REGULER SERVICE ==========
        if(isset($_POST['btnbayar'])) {
            $no_service = $_POST['txtnosrv'] ?? $no_service;
            $km_skr = $_POST['txtkm_skr'] ?? 0;
            
            $diskon_member = $_POST['txtdiskon_member'] ?? 0;
            $txtpotfaktur_persen = $_POST['txtpotfaktur_persen'] ?? 0;
            $total_diskon_persen = $diskon_member + $txtpotfaktur_persen;
            $txtpotfaktur_nom = str_replace(['.', ','], '', $_POST['txtpotfaktur_nom'] ?? '0');
            $txtpajak_persen = $_POST['txtpajak_persen'] ?? 0;
            $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'Tunai';
            $txtbayar = str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0');
            
            // Handle bukti pembayaran upload
            $bukti_pembayaran_path = '';
            if($metode_pembayaran != 'Tunai' && isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
                $upload_dir = 'uploads/bukti_pembayaran/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if(in_array($file_ext, $allowed_ext) && $_FILES['bukti_pembayaran']['size'] <= 2097152) {
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
            
            // == Total dari Item Jasa ==============
            $cari_kd = mysqli_query($koneksi, "SELECT sum(total) as tot, sum(waktu) as tot_waktu 
                                               FROM tblservis_jasa 
                                               WHERE no_service='$no_service'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $total_service_pay = $tm_cari['tot'] ?? 0;
            $total_waktu_pay = $tm_cari['tot_waktu'] ?? 0;
            
            // == Total dari Item Barang ==============
            $cari_kd = mysqli_query($koneksi, "SELECT sum(total) as tot 
                                               FROM tblservis_barang 
                                               WHERE no_service='$no_service'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $total_barang_pay = $tm_cari['tot'] ?? 0;
            
            $tot_pay = $total_service_pay + $total_barang_pay;
            $diskon_nominal = $tot_pay * ($total_diskon_persen / 100);
            $ppn = $tot_pay * ($txtpajak_persen / 100);
            $net_pay = $tot_pay - $diskon_nominal + $ppn;
            $kembalian_pay = $txtbayar - $net_pay;
            
            // Validate payment amount
            if($txtbayar < $net_pay) {
                echo "<script>window.alert('Jumlah pembayaran tidak mencukupi! Total: Rp " . number_format($net_pay, 0, ',', '.') . ", Bayar: Rp " . number_format($txtbayar, 0, ',', '.') . "');
                window.history.back();</script>";
                exit;
            }
            
            if($net_pay <= 0) {
                echo "<script>window.alert('Total service harus lebih dari 0!');
                window.history.back();</script>";
                exit;
            }

            $tgl_bayar_set = '';
            $chk_tgl_bayar = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'tgl_bayar'");
            if ($chk_tgl_bayar && mysqli_num_rows($chk_tgl_bayar) > 0) {
                $tgl_bayar_set = 'tgl_bayar=NOW(),';
            }
            
            // Update tblservice with payment data
            mysqli_query($koneksi, "UPDATE tblservice SET 
                status='2', 
                total='$tot_pay', 
                diskon_persen='$total_diskon_persen', diskon_nom='$diskon_nominal', 
                ppn_persen='$txtpajak_persen', ppn_nom='$ppn', 
                total_grand='$net_pay',
                total_akhir='$net_pay',
                total_waktu='$total_waktu_pay',
                km_skr='$km_skr',
                status_servis='bayar',
                {$tgl_bayar_set}
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
                metode_pembayaran='$metode_pembayaran',
                bayar='$txtbayar',
                kembali='$kembalian_pay'" . 
                (!empty($bukti_pembayaran_path) ? ", bukti_pembayaran='$bukti_pembayaran_path'" : "") . "
                WHERE no_service='$no_service'");

            // Update status antrian menjadi selesai
            mysqli_query($koneksi, "UPDATE tb_antrian_servis 
                SET status_antrian='selesai', 
                    jam_selesai=NOW(),
                    prioritas='normal', -- Ensure priority is set
                    updated_at=NOW() 
                WHERE no_service='$no_service'");
            
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

            // Update stock for items used in service
            $sql = mysqli_query($koneksi, "SELECT * FROM tblservis_barang WHERE no_service='$no_service'");
            while ($tampil = mysqli_fetch_array($sql)) {
                $no_item = $tampil['no_item'];
                $qty = $tampil['quantity'];
                mysqli_query($koneksi, "INSERT INTO tbstok 
                    (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang) 
                    VALUES 
                    ('4','$no_service','$no_item', CURDATE(),'0','$qty','Penjualan Service Reguler','$kd_cabang')");
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
                                logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (reguler)');
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
            echo "<script>
                if(confirm('Pembayaran Service Berhasil!\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "\\n\\nKlik OK untuk print invoice\\nKlik Cancel untuk kembali ke daftar servis')) {
                    window.location='servis-print.php?snoserv=$no_service';
                } else {
                    window.location='servis-reguler.php';
                }
            </script>";
            exit;
        }
        // ========== END HANDLER: PAYMENT PROCESSING ==========
        
    // Guard: redirect to the correct page based on tipe_service and status (RST if finished)
    if(!empty($no_service)) {
        $___ns = mysqli_real_escape_string($koneksi, $no_service);
        $___q = mysqli_query($koneksi, "SELECT tipe_service, status_servis FROM tblservice WHERE no_service='".$___ns."' LIMIT 1");
        if($___q && ($___r = mysqli_fetch_assoc($___q))) {
            $___tipe = strtolower(trim($___r['tipe_service'] ?? 'reguler'));
            $___st = strtolower($___r['status_servis'] ?? '');
            $___finished = ($___st === 'selesai' || $___st === 'bayar');

            if ($___finished) {
                switch ($___tipe) {
                    case 'jemput': $___page = 'servis-input-reguler-jemput-rst.php'; break;
                    case 'garansi': $___page = 'servis-garansi-rst.php'; break;
                    default: $___page = 'servis-input-reguler-rst.php';
                }
            } else {
                switch ($___tipe) {
                    case 'jemput': $___page = 'servis-input-reguler-jemput.php'; break;
                    case 'garansi': $___page = 'servis-garansi.php'; break;
                    default: $___page = 'servis-input-reguler.php';
                }
            }

            $___current = basename($_SERVER['PHP_SELF']);
            if ($___page !== $___current) {
                $___redir = $___page . '?snoserv=' . urlencode($no_service);
                if(isset($_GET['tab']) && $_GET['tab'] !== '') { $___redir .= '&tab=' . urlencode($_GET['tab']); }
                header('Location: ' . $___redir);
                exit;
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
                case 'temuan-penawaran':
                case 'temuan':
                    return 'temuan';
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
        $txtcariwo = $_GET['kdwo'] ?? '';
        $txtnamaitem = '';
        $txtnamasrv = '';
        $txtnamawo = '';
        
        // Get item data if searching for item
        if(!empty($txtcaribrg)) {
            $txtnamaitem = '';
            $cari_item = mysqli_query($koneksi,"SELECT namaitem FROM view_cari_item WHERE noitem='$txtcaribrg'");
            if ($cari_item && mysqli_num_rows($cari_item) > 0) {
                $tm_item = mysqli_fetch_array($cari_item);
                $txtnamaitem = $tm_item['namaitem'] ?? '';
            } else {
                // Fallback jika VIEW belum ada
                $cari_item_tbl = mysqli_query($koneksi,"SELECT namaitem FROM tblitem WHERE noitem='$txtcaribrg'");
                if ($cari_item_tbl && mysqli_num_rows($cari_item_tbl) > 0) {
                    $tm_item = mysqli_fetch_array($cari_item_tbl);
                    $txtnamaitem = $tm_item['namaitem'] ?? '';
                }
            }
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

        // Handle POST actions (search/add items and services)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Cari item barang (redirect ke halaman pencarian)
            if (isset($_POST['btncari'])) {
                $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
                $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

                // Redirect to item search page
                $cbocari = "";
                $cbourut = "52";
                echo "<script>window.location=('servis-add-item-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcaribrg) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=items&_from=reguler');</script>";
                exit;
            }

            // Cari jasa (redirect ke halaman pencarian)
            if (isset($_POST['btncarisrv'])) {
                $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
                $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');

                // Redirect to jasa search page
                $cbocari = "";
                $cbourut = "52";
                echo "<script>window.location=('servis-add-jasa-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcarisrv) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=jasa&_from=reguler');</script>";
                exit;
            }

            // Tambah item barang ke SPK
            if (isset($_POST['btnadd'])) {
                $kd = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');
                $qty = (int)($_POST['txtqty'] ?? 1);
                $pot = 0; // potongan barang default 0 pada form ini
                if (empty($no_service)) {
                    echo "<script>alert('Harap simpan header service terlebih dahulu untuk mendapatkan No. Service.');</script>";
                } else if (!empty($kd) && $qty > 0) {
                    // Guard: enforce applicability based on service motor category
                    $kd_kategori_motor_guard = _get_kd_kategori_motor_by_service($koneksi, $no_service);
                    if ($kd_kategori_motor_guard > 0 && _tbl_exists_local($koneksi, 'tbitem_jenis_motor')) {
                        $map_col = _get_item_map_col($koneksi);
                        $exists_any = mysqli_query($koneksi, "SELECT 1 FROM tbitem_jenis_motor WHERE noitem='{$kd}' LIMIT 1");
                        $has_any_map = ($exists_any && mysqli_num_rows($exists_any) > 0);
                        if ($has_any_map) {
                            $exists_ok = mysqli_query($koneksi, "SELECT 1 FROM tbitem_jenis_motor WHERE noitem='{$kd}' AND {$map_col}={$kd_kategori_motor_guard} LIMIT 1");
                            $is_ok = ($exists_ok && mysqli_num_rows($exists_ok) > 0);
                            if (!$is_ok) {
                                echo "<script>alert('Item tidak sesuai kategori motor (applicable part).');window.location.href='servis-input-reguler-redesign.php?snoserv=" . urlencode($no_service) . "&tab=items#service-items';</script>";
                                exit;
                            }
                        }
                    }

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
                    $pot = 0; // Reset pot manual form
                    
                    // 1. Check Promo Periode (Priority)
                    $tgl_cek = date('Y-m-d'); // Default today
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
                            // Check exclude using helper function
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
                // Tetap di tab Item Barang setelah tambah
                $active_tab = 'service-items';
                // Redirect agar URL menyimpan tab=items sehingga JS tidak override ke default
                header('Location: servis-input-reguler-redesign.php?snoserv=' . urlencode($no_service) . '&tab=items#service-items');
                exit;
            }

            // Tambah item jasa ke SPK
            if (isset($_POST['btnaddsrv'])) {
                $kdj = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
                $potsrv = (float)($_POST['txtpotsrv'] ?? 0);
                if (empty($no_service)) {
                    echo "<script>alert('Harap simpan header service terlebih dahulu untuk mendapatkan No. Service.');</script>";
                } else if (!empty($kdj)) {
                    // Guard: enforce applicability based on service motor category
                    $kd_kategori_motor_guard = _get_kd_kategori_motor_by_service($koneksi, $no_service);
                    if ($kd_kategori_motor_guard > 0 && _tbl_exists_local($koneksi, 'tbitem_jenis_motor')) {
                        $map_col = _get_item_map_col($koneksi);
                        $exists_any = mysqli_query($koneksi, "SELECT 1 FROM tbitem_jenis_motor WHERE noitem='{$kdj}' LIMIT 1");
                        $has_any_map = ($exists_any && mysqli_num_rows($exists_any) > 0);
                        if ($has_any_map) {
                            $exists_ok = mysqli_query($koneksi, "SELECT 1 FROM tbitem_jenis_motor WHERE noitem='{$kdj}' AND {$map_col}={$kd_kategori_motor_guard} LIMIT 1");
                            $is_ok = ($exists_ok && mysqli_num_rows($exists_ok) > 0);
                            if (!$is_ok) {
                                echo "<script>alert('Jasa tidak sesuai kategori motor.');window.location.href='servis-input-reguler-redesign.php?snoserv=" . urlencode($no_service) . "&tab=jasa#service-jasa';</script>";
                                exit;
                            }
                        }
                    }

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
                    $potsrv = 0; // Reset pot manual form
                    
                    // 1. Check Promo Periode (Priority)
                    $tgl_cek = date('Y-m-d'); // Default today
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
                            // Check exclude using helper function
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
                // Tetap di tab Item Jasa setelah tambah
                $active_tab = 'service-jasa';
                // Redirect agar URL menyimpan tab=jasa sehingga JS tidak override ke default
                header('Location: servis-input-reguler-redesign.php?snoserv=' . urlencode($no_service) . '&tab=jasa#service-jasa');
                exit;
            }

            // Cari workorder (Handler untuk tombol Cari di Work Order tab)
            if (isset($_POST['btncariwo'])) {
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');
                $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
                $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

                $km_skr = $_POST['txtkm_skr'] ?? 0;
                $km_berikut = $_POST['txtkm_next'] ?? 0;

                // Update KM data before redirecting
                if(!empty($no_service_post)) {
                    mysqli_query($koneksi, "UPDATE tblservice SET km_skr='$km_skr', km_berikut='$km_berikut' WHERE no_service='$no_service_post'");
                }

                // Redirect to workorder search page
                $cbocari = "";
                $cbourut = "52";
                echo "<script>window.location=('servis-add-workorder-cari.php?snoserv=$no_service_post&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=workorder');</script>";
                exit;
            }

            // Tambah keluhan ke SPK (Handler untuk tombol Tambah Keluhan)
            if (isset($_POST['btnaddkeluhan'])) {
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $txtkeluhan = mysqli_real_escape_string($koneksi, $_POST['txtkeluhan'] ?? '');
                $kode_keluhan = mysqli_real_escape_string($koneksi, $_POST['kode_keluhan'] ?? '');

                $km_skr = $_POST['txtkm_skr'] ?? 0;
                $km_berikut = $_POST['txtkm_next'] ?? 0;

                $txtcarisrv = $_POST['txtcarisrv'] ?? '';
                $txtcaribrg = $_POST['txtcaribrg'] ?? '';
                $txtcariwo = $_POST['txtcariwo'] ?? '';

                if (!empty($txtkeluhan)) {
                    // Insert keluhan to SPK table
                    $kode_keluhan_value = !empty($kode_keluhan) ? "'$kode_keluhan'" : "NULL";
                    mysqli_query($koneksi, "INSERT INTO tbservis_keluhan_status
                                           (no_service, keluhan, kode_keluhan, status_pengerjaan)
                                           VALUES
                                           ('$no_service_post', '$txtkeluhan', $kode_keluhan_value, 'datang')");

                    // Update KM data
                    mysqli_query($koneksi, "UPDATE tblservice
                                           SET km_skr='$km_skr', km_berikut='$km_berikut'
                                           WHERE no_service='$no_service_post'");

                    echo "<script>
                        alert('Keluhan berhasil ditambahkan ke SPK!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=workorder';
                    </script>";
                    exit;
                } else {
                    echo "<script>
                        alert('Keluhan tidak boleh kosong!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=workorder';
                    </script>";
                    exit;
                }
            }

            // Tambah workorder ke SPK (Handler untuk tombol Tambah Work Order)
            if (isset($_POST['btnaddworkorder'])) {
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $kode_wo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

                $km_skr = $_POST['txtkm_skr'] ?? 0;
                $km_berikut = $_POST['txtkm_next'] ?? 0;

                $txtcarisrv = $_POST['txtcarisrv'] ?? '';
                $txtcaribrg = $_POST['txtcaribrg'] ?? '';

                if (!empty($kode_wo)) {
                    // Guard: enforce applicability based on service motor category
                    $kd_kategori_motor_guard = _get_kd_kategori_motor_by_service($koneksi, $no_service_post);
                    if ($kd_kategori_motor_guard > 0 && _tbl_exists_local($koneksi, 'tbworkorder_jenis_motor')) {
                        $map_col = _get_wo_map_col($koneksi);
                        $exists_any = mysqli_query($koneksi, "SELECT 1 FROM tbworkorder_jenis_motor WHERE kode_wo='{$kode_wo}' LIMIT 1");
                        $has_any_map = ($exists_any && mysqli_num_rows($exists_any) > 0);
                        if ($has_any_map) {
                            $exists_ok = mysqli_query($koneksi, "SELECT 1 FROM tbworkorder_jenis_motor WHERE kode_wo='{$kode_wo}' AND {$map_col}={$kd_kategori_motor_guard} LIMIT 1");
                            $is_ok = ($exists_ok && mysqli_num_rows($exists_ok) > 0);
                            if (!$is_ok) {
                                echo "<script>alert('Work Order tidak sesuai kategori motor.');window.location.href='servis-input-reguler-redesign.php?snoserv={$no_service_post}&tab=workorder#workorder-details';</script>";
                                exit;
                            }
                        }
                    }

                    // Check if workorder already exists in SPK
                    $check_wo = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbservis_workorder
                                                       WHERE no_service='$no_service_post' AND kode_wo='$kode_wo'");
                    $check_result = mysqli_fetch_array($check_wo);

                    if ($check_result['count'] == 0) {
                        // Verify workorder exists in master
                        $verify_wo = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
                        $verify_result = mysqli_fetch_array($verify_wo);

                        if ($verify_result['count'] > 0) {
                            // Insert workorder to SPK
                            mysqli_query($koneksi, "INSERT INTO tbservis_workorder
                                                   (no_service, kode_wo, status_pengerjaan)
                                                   VALUES
                                                   ('$no_service_post', '$kode_wo', 'diproses')");

                            // PERUBAHAN: Item workorder masuk ke PENDING untuk perlu approval
                            // Get workorder ID yang baru di-insert
                            $wo_id = mysqli_insert_id($koneksi);

                            // Get nama workorder untuk reference
                            $wo_info = mysqli_query($koneksi, "SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
                            $wo_data = mysqli_fetch_array($wo_info);
                            $nama_wo = $wo_data['nama_wo'] ?? 'Workorder ' . $kode_wo;

                            // Auto-add jasa dan barang dari workorder detail ke PENDING ITEMS
                            $detail_wo = mysqli_query($koneksi, "SELECT kode_barang, tipe, harga, total, jumlah
                                                                FROM tbworkorderdetail
                                                                WHERE kode_wo='$kode_wo'");
                            $detail_count = ($detail_wo ? mysqli_num_rows($detail_wo) : 0);

                            if ($detail_count === 0 && preg_match('/^WO0*(\\d+)$/', $kode_wo, $m)) {
                                $wo_num = $m[1];
                                // Build variants: unpadded and padded 2..5 digits
                                $variants = array();
                                $variants[] = 'WO' . $wo_num; // unpadded
                                for ($pad = 2; $pad <= 5; $pad++) {
                                    $variants[] = 'WO' . str_pad($wo_num, $pad, '0', STR_PAD_LEFT);
                                }
                                // Ensure uniqueness and include original code as well
                                $variants[] = $kode_wo;
                                $variants = array_values(array_unique($variants));

                                // Build IN list safely
                                $in_list = array();
                                foreach ($variants as $v) { $in_list[] = "'" . mysqli_real_escape_string($koneksi, $v) . "'"; }
                                $in_sql = implode(',', $in_list);

                                $detail_wo = mysqli_query($koneksi, "SELECT kode_barang, tipe, harga, total, jumlah FROM tbworkorderdetail WHERE kode_wo IN ($in_sql)");
                                $detail_count = ($detail_wo ? mysqli_num_rows($detail_wo) : 0);
                            }

                            $total_items_added = 0;
                            while ($detail = mysqli_fetch_array($detail_wo)) {
                                $kode_item = $detail['kode_barang'];
                                $quantity = $detail['jumlah'];
                                $harga_satuan = $detail['harga'];
                                $total = $detail['total'];
                                $waktu = 0;

                                // Get nama item
                                $nama_item = '';
                                if ($detail['tipe'] == '1') {
                                    // Jasa - cek dulu di tblitem_jasa, kalau tidak ada cek di tblitem
                                    $q_item = @mysqli_query($koneksi, "SELECT namaitem FROM tblitem_jasa WHERE noitem='$kode_item'");
                                    if ($q_item && mysqli_num_rows($q_item) > 0) {
                                        $item_data = mysqli_fetch_array($q_item);
                                        $nama_item = $item_data['namaitem'];
                                    } else {
                                        // Fallback ke tblitem
                                        $q_item2 = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='$kode_item'");
                                        if ($q_item2 && mysqli_num_rows($q_item2) > 0) {
                                            $item_data = mysqli_fetch_array($q_item2);
                                            $nama_item = $item_data['namaitem'];
                                        } else {
                                            $nama_item = 'Jasa ' . $kode_item;
                                        }
                                    }
                                } else {
                                    // Barang
                                    $q_item = mysqli_query($koneksi, "SELECT namaitem FROM tblitem WHERE noitem='$kode_item'");
                                    if ($q_item && mysqli_num_rows($q_item) > 0) {
                                        $item_data = mysqli_fetch_array($q_item);
                                        $nama_item = $item_data['namaitem'];
                                    } else {
                                        $nama_item = 'Part ' . $kode_item;
                                    }
                                }

                                // Escape nama item untuk insert
                                $nama_item = mysqli_real_escape_string($koneksi, $nama_item);

                                // Check if item already in pending
                                $check_pending = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbservis_pending_items
                                                                        WHERE no_service='$no_service_post'
                                                                        AND kode_item='$kode_item'
                                                                        AND status_approval='pending'");
                                $check_result = mysqli_fetch_array($check_pending);

                                if ($check_result['count'] == 0) {
                                    $tipe_item = ($detail['tipe'] == '1') ? 'jasa' : 'barang';

                                    // Insert to tbservis_pending_items
                                    $wo_id_value = ($wo_id && $wo_id > 0) ? "'$wo_id'" : "NULL";

                                    $sql_insert_pending = "INSERT INTO tbservis_pending_items
                                                           (no_service, wo_id, kode_item, nama_item, tipe, quantity,
                                                            harga_satuan, total, waktu, status_approval)
                                                           VALUES
                                                           ('$no_service_post', $wo_id_value, '$kode_item', '$nama_item',
                                                            '$tipe_item', '$quantity', '$harga_satuan', '$total',
                                                            '$waktu', 'pending')";

                                    $insert_result = mysqli_query($koneksi, $sql_insert_pending);

                                    if ($insert_result) {
                                        $total_items_added++;
                                    } else {
                                        // Log error ke file untuk debugging
                                        $error_msg = mysqli_error($koneksi);
                                        error_log("ERROR INSERT PENDING ITEM: " . $error_msg . " | Query: " . $sql_insert_pending);
                                    }
                                }
                            }

                            // Update KM data
                            mysqli_query($koneksi, "UPDATE tblservice
                                                   SET km_skr='$km_skr', km_berikut='$km_berikut'
                                                   WHERE no_service='$no_service_post'");

                            $msg = '';
                            if ($detail_count === 0) {
                                $msg = "Work Order berhasil ditambahkan! Work Order ini tidak memiliki item detail di master. Tidak ada item yang perlu di-approve.";
                            } else {
                                $msg = "Work Order berhasil ditambahkan! $total_items_added item masuk ke Tab Temuan & Penawaran sebagai penawaran pending. Silakan approve/reject di tab tersebut.";
                            }
                            echo "<script>
                                alert('" . addslashes($msg) . "');
                                window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                            </script>";
                            exit;
                        } else {
                            echo "<script>
                                alert('Kode Work Order tidak ditemukan di master!');
                                window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=workorder';
                            </script>";
                            exit;
                        }
                    } else {
                        echo "<script>
                            alert('Work Order ini sudah ditambahkan untuk service ini!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=workorder';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('Kode Work Order tidak boleh kosong!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=workorder';
                    </script>";
                    exit;
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
                                                                     AND no_item='{$pending_data['kode_item']}'");
                            $check_result = mysqli_fetch_array($check_existing);

                            if ($check_result['count'] == 0) {
                                // Insert to tblservis_barang
                                mysqli_query($koneksi, "INSERT INTO tblservis_barang
                                                       (no_service, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                                       VALUES
                                                       ('$no_service_post', '{$pending_data['kode_item']}', '{$pending_data['quantity']}',
                                                        '0', '{$pending_data['harga_satuan']}', '0', '{$pending_data['total']}')");
                            }
                        } else {
                            // Jasa
                            $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_jasa
                                                                     WHERE no_service='$no_service_post'
                                                                     AND no_item='{$pending_data['kode_item']}'");
                            $check_result = mysqli_fetch_array($check_existing);

                            if ($check_result['count'] == 0) {
                                // Check if waktu column exists
                                $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");

                                if (mysqli_num_rows($check_waktu) > 0) {
                                    mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                                           (no_service, no_item, harga, waktu, potongan, total)
                                                           VALUES
                                                           ('$no_service_post', '{$pending_data['kode_item']}', '{$pending_data['harga_satuan']}',
                                                            '{$pending_data['waktu']}', '0', '{$pending_data['total']}')");
                                } else {
                                    mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                                           (no_service, no_item, harga, potongan, total)
                                                           VALUES
                                                           ('$no_service_post', '{$pending_data['kode_item']}', '{$pending_data['harga_satuan']}',
                                                            '0', '{$pending_data['total']}')");
                                }
                            }
                        }

                        // Update status pending item to approved
                        mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                               SET status_approval='disetujui',
                                                   updated_at=NOW()
                                               WHERE id='$pending_item_id'");

                        echo "<script>
                            alert('Item berhasil disetujui dan ditambahkan ke " . ($pending_data['tipe'] == 'barang' ? 'Item Barang' : 'Item Jasa') . "!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    } else {
                        echo "<script>
                            alert('Item tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID item tidak valid!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
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

                        // Add to service notes/catatan
                        $alasan_text_map = array(
                            'customer_tidak_mau' => 'Pelanggan tidak setuju',
                            'stok_cabang_kosong' => 'Stok barang di bengkel tidak ada',
                            'stok_supplier_kosong' => 'Stok barang di supplier tidak ada',
                            'lainnya' => 'Lainnya'
                        );

                        $alasan_text = $alasan_text_map[$alasan_reject] ?? $alasan_reject;
                        $tipe_text = $pending_data['tipe'] == 'barang' ? 'Part' : 'Jasa';

                        // Get current keterangan/catatan
                        $q_service = mysqli_query($koneksi, "SELECT keterangan FROM tblservice WHERE no_service='$no_service_post'");
                        $service_data = mysqli_fetch_array($q_service);
                        $catatan_lama = $service_data['keterangan'] ?? '';

                        // Build new note
                        $catatan_baru = $catatan_lama;
                        if (!empty($catatan_lama)) {
                            $catatan_baru .= "\n\n";
                        }

                        $catatan_baru .= "[ITEM DITOLAK] $tipe_text: {$pending_data['nama_item']} ({$pending_data['kode_item']})";
                        $catatan_baru .= "\nAlasan: $alasan_text";
                        if (!empty($keterangan_reject)) {
                            $catatan_baru .= "\nKeterangan: $keterangan_reject";
                        }
                        $catatan_baru .= "\nTanggal: " . date('d/m/Y H:i');

                        // Update catatan
                        $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
                        mysqli_query($koneksi, "UPDATE tblservice
                                               SET keterangan='$catatan_escaped'
                                               WHERE no_service='$no_service_post'");

                        echo "<script>
                            alert('Item ditolak dan dicatat di catatan service! Alasan: $alasan_text');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    } else {
                        echo "<script>
                            alert('Item tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID item tidak valid!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                    </script>";
                    exit;
                }
            }

            // ========================================
            // HANDLER APPROVE PENAWARAN PART
            // ========================================
            if (isset($_POST['btnsetujuipenawaran'])) {
                $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_id'] ?? '');
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');

                if (!empty($penawaran_id)) {
                    // Get penawaran data
                    $q_penawaran = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_part
                                                           WHERE id='$penawaran_id'
                                                           AND no_service='$no_service_post'
                                                           AND status_penawaran='pending'");

                    if ($q_penawaran && mysqli_num_rows($q_penawaran) > 0) {
                        $penawaran_data = mysqli_fetch_array($q_penawaran);

                        // Check if item already exists in tblservis_barang
                        $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tblservis_barang
                                                                  WHERE no_service='$no_service_post'
                                                                  AND no_item='{$penawaran_data['kode_barang']}'");
                        $check_result = mysqli_fetch_array($check_existing);

                        if ($check_result['cnt'] == 0) {
                            // Get item details from tblitem
                            $q_item = mysqli_query($koneksi, "SELECT * FROM tblitem WHERE noitem='{$penawaran_data['kode_barang']}'");

                            if ($q_item && mysqli_num_rows($q_item) > 0) {
                                $item_data = mysqli_fetch_array($q_item);

                                // Get max nobaris for this service
                                $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) as max_nobaris
                                                                     FROM tblservis_barang
                                                                     WHERE no_service='$no_service_post'");
                                $nobaris_data = mysqli_fetch_array($q_nobaris);
                                $nobaris = $nobaris_data['max_nobaris'] + 1;

                                // Insert to tblservis_barang
                                $sql_insert = "INSERT INTO tblservis_barang
                                              (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                              VALUES
                                              ('$no_service_post',
                                               $nobaris,
                                               '{$penawaran_data['kode_barang']}',
                                               '{$penawaran_data['quantity']}',
                                               0,
                                               '{$penawaran_data['harga_satuan']}',
                                               0,
                                               '{$penawaran_data['total_harga']}')";

                                if (mysqli_query($koneksi, $sql_insert)) {
                                    // Update status penawaran
                                    $user_respon = $_SESSION['_nama'] ?? 'System';
                                    mysqli_query($koneksi, "UPDATE tbservis_penawaran_part
                                                           SET status_penawaran='disetujui',
                                                               tanggal_respon=NOW(),
                                                               user_respon='$user_respon',
                                                               updated_at=NOW()
                                                           WHERE id='$penawaran_id'");

                                    echo "<script>
                                        alert('Penawaran berhasil disetujui! Part telah ditambahkan ke Item Barang.');
                                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                                    </script>";
                                    exit;
                                } else {
                                    $error_msg = addslashes(mysqli_error($koneksi));
                                    echo "<script>
                                        alert('Gagal menambahkan ke item barang! Error: $error_msg');
                                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                                    </script>";
                                    exit;
                                }
                            } else {
                                // Item tidak ada di tblitem, kemungkinan custom item
                                // Get max nobaris for this service
                                $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) as max_nobaris
                                                                     FROM tblservis_barang
                                                                     WHERE no_service='$no_service_post'");
                                $nobaris_data = mysqli_fetch_array($q_nobaris);
                                $nobaris = $nobaris_data['max_nobaris'] + 1;

                                // Insert langsung ke tblservis_barang tanpa cek tblitem
                                $sql_insert = "INSERT INTO tblservis_barang
                                              (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                              VALUES
                                              ('$no_service_post',
                                               $nobaris,
                                               '{$penawaran_data['kode_barang']}',
                                               '{$penawaran_data['quantity']}',
                                               0,
                                               '{$penawaran_data['harga_satuan']}',
                                               0,
                                               '{$penawaran_data['total_harga']}')";

                                if (mysqli_query($koneksi, $sql_insert)) {
                                    // Update status penawaran
                                    $user_respon = $_SESSION['_nama'] ?? 'System';
                                    mysqli_query($koneksi, "UPDATE tbservis_penawaran_part
                                                           SET status_penawaran='disetujui',
                                                               tanggal_respon=NOW(),
                                                               user_respon='$user_respon',
                                                               updated_at=NOW()
                                                           WHERE id='$penawaran_id'");

                                    echo "<script>
                                        alert('Penawaran berhasil disetujui! Custom item telah ditambahkan ke Item Barang.');
                                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                                    </script>";
                                    exit;
                                } else {
                                    $error_msg = addslashes(mysqli_error($koneksi));
                                    echo "<script>
                                        alert('Gagal menambahkan custom item! Error: $error_msg');
                                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                                    </script>";
                                    exit;
                                }
                            }
                        } else {
                            echo "<script>
                                alert('Item sudah ada di daftar Item Barang!');
                                window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                            </script>";
                            exit;
                        }
                    } else {
                        echo "<script>
                            alert('Penawaran tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID penawaran tidak valid!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                    </script>";
                    exit;
                }
            }

            // ========================================
            // HANDLER REJECT PENAWARAN PART
            // ========================================
            if (isset($_POST['btnrejectpenawaran'])) {
                $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_id'] ?? '');
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $alasan_tolak = mysqli_real_escape_string($koneksi, $_POST['alasan_tolak'] ?? 'lainnya');
                $keterangan_tolak = mysqli_real_escape_string($koneksi, $_POST['keterangan_tolak'] ?? '');

                if (!empty($penawaran_id)) {
                    // Get penawaran data
                    $q_penawaran = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_part
                                                           WHERE id='$penawaran_id'
                                                           AND no_service='$no_service_post'
                                                           AND status_penawaran='pending'");

                    if ($q_penawaran && mysqli_num_rows($q_penawaran) > 0) {
                        // Update status to rejected
                        $user_respon = $_SESSION['_nama'] ?? 'System';
                        $sql_update = "UPDATE tbservis_penawaran_part
                                      SET status_penawaran='ditolak',
                                          alasan_tolak='$alasan_tolak',
                                          keterangan_tolak='$keterangan_tolak',
                                          tanggal_respon=NOW(),
                                          user_respon='$user_respon',
                                          updated_at=NOW()
                                      WHERE id='$penawaran_id'";

                        if (mysqli_query($koneksi, $sql_update)) {
                            $alasan_text_map = array(
                                'customer_tidak_mau' => 'Customer tidak mau',
                                'stok_bengkel_kosong' => 'Stok bengkel kosong',
                                'stok_supplier_kosong' => 'Stok supplier kosong',
                                'harga_tidak_cocok' => 'Harga tidak cocok',
                                'lainnya' => 'Lainnya'
                            );
                            $alasan_text = $alasan_text_map[$alasan_tolak] ?? $alasan_tolak;

                            echo "<script>
                                alert('Penawaran berhasil ditolak! Alasan: $alasan_text');
                                window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                            </script>";
                            exit;
                        } else {
                            $error_msg = addslashes(mysqli_error($koneksi));
                            echo "<script>
                                alert('Gagal menolak penawaran! Error: $error_msg');
                                window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                            </script>";
                            exit;
                        }
                    } else {
                        echo "<script>
                            alert('Penawaran tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID penawaran tidak valid!');
                        window.location.href = 'servis-input-reguler-redesign.php?snoserv=$no_service_post&tab=temuan';
                    </script>";
                    exit;
                }
            }
        }

    // ========================================
    // HANDLER: PAYMENT PROCESSING (btnbayar)
    // ========================================
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

        mysqli_query($koneksi, $update_query);

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
                                'Penjualan Service Reguler','$kd_cabang')");
        }

        // ========== SISTEM ANTRIAN - SERVICE REGULER ==========
        // Cek apakah sudah ada nomor antrian untuk service ini
        $check_antrian = mysqli_query($koneksi, "SELECT no_antrian FROM tb_antrian_servis WHERE no_service='$no_service'");

        if(mysqli_num_rows($check_antrian) == 0) {
            // Belum ada antrian, generate nomor antrian baru
            $tanggal_antrian = date('Y-m-d');
            $jam_antrian = date('H:i:s');

            // Hitung total antrian hari ini
            $query_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal='$tanggal_antrian'");
            $count = mysqli_fetch_array($query_count);
            $nomor_urut = $count['total'] + 1;

            // Format nomor antrian: A001, A002, dst
            $no_antrian = 'A' . str_pad($nomor_urut, 3, '0', STR_PAD_LEFT);

            // Insert ke tabel antrian dengan prioritas NORMAL untuk Reguler
            $insert_antrian = mysqli_query($koneksi, "INSERT INTO tb_antrian_servis (
                no_service, no_antrian, tanggal, jam_ambil,
                status_antrian, prioritas, estimasi_waktu, created_at
            ) VALUES (
                '$no_service', '$no_antrian', '$tanggal_antrian', '$jam_antrian',
                'selesai', 'normal', '$total_waktu_pay', NOW()
            )");

            // Update jam_selesai karena langsung bayar
            mysqli_query($koneksi, "UPDATE tb_antrian_servis SET jam_selesai=NOW() WHERE no_service='$no_service'");
        } else {
            // Sudah ada antrian, update status menjadi selesai
            mysqli_query($koneksi, "UPDATE tb_antrian_servis SET status_antrian='selesai', jam_selesai=NOW() WHERE no_service='$no_service'");

            $existing = mysqli_fetch_array($check_antrian);
            $no_antrian = $existing['no_antrian'];
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
                            logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (Reguler)');
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

        echo"<script>window.alert('Pembayaran Service Reguler Berhasil!\\n\\nNomor Antrian: $no_antrian\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "');
        window.location=('servis-print.php?snoserv=$no_service');</script>";
        exit;
    }
    // ========== END PAYMENT PROCESSING ==========

    // Get existing mechanic data if service exists
        if(!empty($no_service)) {
        // Try to get data from existing service table
        $query_existing = "SELECT * FROM tblservice WHERE no_service='$no_service'";
            $result_existing = mysqli_query($koneksi, $query_existing);
            if($result_existing && mysqli_num_rows($result_existing) > 0) {
            $existing_data = mysqli_fetch_array($result_existing);
            // Initialize with empty values if columns don't exist
            $kepala_mekanik1 = $existing_data['kepala_mekanik1'] ?? '';
            $persen_kepala1 = $existing_data['persen_kepala_mekanik1'] ?? ($existing_data['persen_kepala1'] ?? 0);
            $kepala_mekanik2 = $existing_data['kepala_mekanik2'] ?? '';
            $persen_kepala2 = $existing_data['persen_kepala_mekanik2'] ?? ($existing_data['persen_kepala2'] ?? 0);
            $admin1 = $existing_data['admin1'] ?? '';
            $persen_admin1 = $existing_data['persen_admin1'] ?? 0;
            $admin2 = $existing_data['admin2'] ?? '';
            $persen_admin2 = $existing_data['persen_admin2'] ?? 0;
            $mekanik1 = $existing_data['mekanik1'] ?? '';
            $persen_kerja1 = $existing_data['persen_mekanik1'] ?? ($existing_data['persen_kerja1'] ?? 0);
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_kerja2 = $existing_data['persen_mekanik2'] ?? ($existing_data['persen_kerja2'] ?? 0);
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_kerja3 = $existing_data['persen_mekanik3'] ?? ($existing_data['persen_kerja3'] ?? 0);
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_kerja4 = $existing_data['persen_mekanik4'] ?? ($existing_data['persen_kerja4'] ?? 0);
        }
    }
    
    // Initialize other variables
    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';

    // Totals and payment defaults for Actions tab
    $no_pelanggan = $kode_pelanggan ?? '';
    $total_barang = 0;
    $total_service = 0;
    $tot = 0;
    $discount_amount = 0;
    $auto_discount_percent = isset($auto_discount_percent) ? $auto_discount_percent : 0;
    $metode_pembayaran = isset($metode_pembayaran) ? $metode_pembayaran : 'Tunai';
    $potongan_pelanggan = isset($potongan_pelanggan) ? $potongan_pelanggan : 0;
    $net = 0;
    if (!empty($no_service)) {
        $rb = mysqli_query($koneksi, "SELECT COALESCE(SUM(total),0) AS tot FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if ($rb && ($rbrow = mysqli_fetch_assoc($rb))) { $total_barang = (float)$rbrow['tot']; }
        $rj = mysqli_query($koneksi, "SELECT COALESCE(SUM(total),0) AS tot FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if ($rj && ($rjrow = mysqli_fetch_assoc($rj))) { $total_service = (float)$rjrow['tot']; }
    }
    $tot = $total_barang + $total_service;
    $net = $tot;

    // Additional variables for redesign templates
    $catatan_service = '';
    if(!empty($no_service)) {
        $q_cat = mysqli_query($koneksi, "SELECT keterangan, km_skr, km_berikut FROM tblservice WHERE no_service='".mysqli_real_escape_string($koneksi, $no_service)."'");
        if($q_cat && $r_cat = mysqli_fetch_assoc($q_cat)) {
            $catatan_service = $r_cat['keterangan'] ?? '';
            $km_skr = $r_cat['km_skr'] ?? 0;
            $km_berikut = $r_cat['km_berikut'] ?? 0;
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Service Reguler - <?= htmlspecialchars($no_service ?: 'Baru') ?> | FIT MOTOR</title>

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
        padding: 16px 24px;
        color: var(--rd-text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
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
        background: linear-gradient(135deg, var(--rd-primary) 0%, #3A7BC8 100%);
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
                <a href="servis-reguler.php" class="rd-btn outline-primary">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div>
                    <h1><i class="fa fa-tools"></i> Input Service Reguler</h1>
                    <small class="rd-text-muted">Kelola detail service kendaraan</small>
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
                    <?= strtoupper($status_servis) ?>
                </span>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="" enctype="multipart/form-data" id="formServisReguler">
            <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($rd_active_tab) ?>">

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
                        <i class="fa fa-info-circle"></i> Detail Service
                    </a>
                    <a href="#tab-workorder" class="rd-main-tab-link <?= $rd_active_tab == 'workorder' ? 'active' : '' ?>" data-tab="workorder">
                        <i class="fa fa-clipboard-list"></i> Work Order
                        <span class="rd-badge info"><?= $count_keluhan + $count_wo ?></span>
                    </a>
                    <a href="#tab-temuan" class="rd-main-tab-link <?= $rd_active_tab == 'temuan' ? 'active' : '' ?>" data-tab="temuan">
                        <i class="fa fa-search-plus"></i> Temuan & Penawaran
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
                        <i class="fa fa-money-bill-wave"></i> Pembayaran
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
    if(file_exists('_template/_modal_cancel_service.php')) {
        include '_template/_modal_cancel_service.php';
    }
    if(file_exists('modal-tambah-keluhan-baru.php')) {
        include 'modal-tambah-keluhan-baru.php';
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
                        <button type="submit" name="btnupdatewo" class="btn btn-primary">Update</button>
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
                        <button type="submit" name="btnupdatekeluhan" class="btn btn-primary">Update</button>
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
<?php } ?>
</html>
