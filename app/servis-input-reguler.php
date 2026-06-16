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
        include "_include_customer_vehicle_sync.php";
        include "_handler_temuan_penawaran.php";
        include "_handler_barang_custom.php";
        include "_handler_status_keluhan_wo.php";

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

        // Handler hapus item barang/jasa (tombol "Hapus" di tab Item Barang/Item Jasa)
        if (!empty($no_service)) {
            $no_service_esc = mysqli_real_escape_string($koneksi, $no_service);
            if (isset($_GET['hapus_brg'])) {
                $id_hapus = (int) $_GET['hapus_brg'];
                mysqli_query($koneksi, "DELETE FROM tblservis_barang WHERE id={$id_hapus} AND no_service='{$no_service_esc}'");
                header('Location: servis-input-reguler.php?snoserv=' . urlencode($no_service) . '&tab=items');
                exit;
            }
            if (isset($_GET['hapus_srv'])) {
                $id_hapus = (int) $_GET['hapus_srv'];
                mysqli_query($koneksi, "DELETE FROM tblservis_jasa WHERE id={$id_hapus} AND no_service='{$no_service_esc}'");
                header('Location: servis-input-reguler.php?snoserv=' . urlencode($no_service) . '&tab=jasa');
                exit;
            }
        }

        function _tbl_exists_local($koneksi, $name) {
            $name = mysqli_real_escape_string($koneksi, $name);
            $res = mysqli_query($koneksi, "SHOW TABLES LIKE '{$name}'");
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
                
                // Capture Mechanic Data from POST for btnsimpan update
                // Note: The form layout might have these fields outside the main form or in a different tab. 
                // But if they are POSTed, we should update them.
                // We use coalescing operator to avoid overwriting with empty if not POSTed (though form submission usually sends all inputs)
                // However, usually mechanic assignment is done via separate handlers or btnbayar.
                // But user requested "btnsimpan juga diperbaiki".
                
                $extra_update = "";
                if(isset($_POST['cbokepala_mekanik1'])) {
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

                    $extra_update = ", 
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
                    persen_mekanik4='$persen_mekanik4'";
                }

                $query_update_service = "UPDATE tblservice SET 
                    tanggal = '$tanggal_service',
                    jam = '$jam_input',
                    no_pelanggan = '$kode_pelanggan',
                    no_polisi = '$no_polisi',
                    keterangan = '$keluhan'
                    $extra_update,
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
            $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
            
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

            $has_admin1 = !empty($admin1);
            $has_admin2 = !empty($admin2);
            $admin_count = ($has_admin1?1:0) + ($has_admin2?1:0);
            $pa1 = floatval($persen_admin1);
            $pa2 = floatval($persen_admin2);
            if(!$has_admin1) { $err_msgs[] = 'Admin wajib diisi (minimal Admin 1).'; }
            if($admin_count == 1) { $p = $has_admin1 ? $pa1 : $pa2; if(round($p,2) != 100) { $err_msgs[] = 'Persentase Admin harus 100% jika hanya satu.'; } }
            if($admin_count == 2) { if(round($pa1 + $pa2,2) != 100) { $err_msgs[] = 'Total persentase Admin harus 100%.'; } }
            if(($pa1 < 0 || $pa1 > 100) || ($pa2 < 0 || $pa2 > 100)) { $err_msgs[] = 'Persentase Admin harus 0-100.'; }

            $mekanik_vals = array();
            $mekanik_pers = array();
            if(!empty($mekanik1)) { $mekanik_vals[] = $mekanik1; $mekanik_pers[] = floatval($persen_mekanik1); }
            if(!empty($mekanik2)) { $mekanik_vals[] = $mekanik2; $mekanik_pers[] = floatval($persen_mekanik2); }
            if(!empty($mekanik3)) { $mekanik_vals[] = $mekanik3; $mekanik_pers[] = floatval($persen_mekanik3); }
            if(!empty($mekanik4)) { $mekanik_vals[] = $mekanik4; $mekanik_pers[] = floatval($persen_mekanik4); }
            if(count($mekanik_vals) == 0) { $err_msgs[] = 'Mekanik wajib diisi (minimal 1).'; }
            $sum_mek = 0.0; foreach($mekanik_pers as $p) { if($p < 0 || $p > 100) { $err_msgs[] = 'Persentase Mekanik harus 0-100.'; } $sum_mek += $p; }
            if(count($mekanik_vals) == 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Persentase Mekanik harus 100% jika hanya satu.'; } }
            if(count($mekanik_vals) > 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Total persentase Mekanik harus 100%.'; } }

            $___ns = mysqli_real_escape_string($koneksi, $no_service);
            $cek_keluhan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_keluhan_status WHERE no_service='".$___ns."' AND status_pengerjaan IN ('datang','diproses')");
            if($cek_keluhan && ($rk = mysqli_fetch_assoc($cek_keluhan))) {
                if(intval($rk['c']) > 0) { $err_msgs[] = 'Masih ada keluhan dengan status dalam proses/belum selesai.'; }
            }

            if(!empty($err_msgs)) {
                $msg = implode("\\n- ", $err_msgs);
                echo "<script>window.alert('Tidak dapat memproses pembayaran karena:\\n- ".$msg."'); window.location='servis-input-reguler.php?snoserv=".addslashes($no_service)."&tab=actions#service-actions';</script>";
                exit;
            }

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
            
            // Discount Logic for Manual Invoice Discount (Additional)
            // $tot_pay (sum of tblservis_jasa/tblservis_barang.total) is already net of member discount,
            // so only the additional invoice-level discount (txtpotfaktur_persen) is applied here
            // to avoid double-discounting. $total_diskon_persen is kept for the diskon_persen record/report column.
            $diskon_plus_nominal = $tot_pay * ($txtpotfaktur_persen / 100);
            
            $ppn = ($tot_pay - $diskon_plus_nominal) * ($txtpajak_persen / 100);
            $net_pay = $tot_pay - $diskon_plus_nominal + $ppn;
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
            
            $has_tgl_bayar_col = false;
            $chk_tgl_bayar = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'tgl_bayar'");
            if ($chk_tgl_bayar && mysqli_num_rows($chk_tgl_bayar) > 0) {
                $has_tgl_bayar_col = true;
            }

            $update_query = "UPDATE tblservice SET 
                status='2', 
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
                status_servis='selesai'";

            if ($has_tgl_bayar_col) {
                $update_query .= ", tgl_bayar=NOW()";
            }

            $update_query .= ",
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
                kembali='$kembalian_pay'";

            if (!empty($bukti_pembayaran_path)) {
                $update_query .= ", bukti_pembayaran='$bukti_pembayaran_path'";
            }

            $update_query .= " WHERE no_service='$no_service'";

            if(!mysqli_query($koneksi, $update_query)) {
                die("Error Update Service: " . mysqli_error($koneksi));
            }

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
            if(function_exists('clearSessionDiscount')) { clearSessionDiscount(); }
            
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

    // FALLBACK: If customer name is empty (no linked customer record), use vehicle owner name
    if (empty($namapelanggan) && !empty($pemilik)) {
        $namapelanggan = $pemilik;
        // Optionally set a flag or default category if needed
        if ($kategori_pelanggan == '001' || empty($kategori_pelanggan)) {
            $kategori_pelanggan = 'REGULAR'; // Or keep '001' depending on logic
        }
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
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_GET['kd'] ?? '');
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_GET['kdjasa'] ?? '');
        $txtcariwo = mysqli_real_escape_string($koneksi, $_GET['kdwo'] ?? '');
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
                                echo "<script>alert('Item tidak sesuai kategori motor (applicable part).');window.location.href='servis-input-reguler.php?snoserv=" . urlencode($no_service) . "&tab=items#service-items';</script>";
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
                    // Now uses session discount from preview modal (servis-carinopol.php)
                    $diskon_source = 'none';
                    $diskon_persen = 0;
                    $diskon_nominal = 0;
                    $id_promo = 'NULL';
                    $pot = 0; // Reset pot manual form

                    // Get customer plate (no_polisi) for discount session matching
                    $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan, no_polisi FROM tblservice WHERE no_service='$no_service'");
                    $cust_row = mysqli_fetch_assoc($cust_query);
                    $no_pel = $cust_row['no_pelanggan'] ?? '';
                    $no_polisi_item = $cust_row['no_polisi'] ?? '';

                    // Use helper function to get active discount (from session or fallback)
                    if(function_exists('getActiveDiscountForService') && !empty($no_polisi_item)) {
                        $active_discount = getActiveDiscountForService($koneksi, $no_polisi_item);

                        if($active_discount['apply_discount']) {
                            // Check if item is excluded from member discount
                            $is_excluded = false;
                            if($active_discount['discount_type'] == 'member') {
                                if(function_exists('isItemExcludedFromDiscount')) {
                                    $is_excluded = isItemExcludedFromDiscount($koneksi, $kd);
                                } elseif(function_exists('isItemExcludedFromMemberDiscount')) {
                                    $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $kd);
                                }
                            }

                            if(!$is_excluded) {
                                if($active_discount['discount_type'] == 'periode') {
                                    $diskon_source = 'promo';
                                    $id_promo = $active_discount['promo_id'] ? $active_discount['promo_id'] : 'NULL';
                                    $diskon_persen = floatval($active_discount['diskon_barang']);
                                    $diskon_nominal = $harga * ($diskon_persen / 100);
                                } else if($active_discount['discount_type'] == 'member') {
                                    $diskon_source = 'member';
                                    $diskon_persen = floatval($active_discount['diskon_barang']);
                                    $diskon_nominal = $harga * ($diskon_persen / 100);
                                }
                            }
                        }
                    }
                    // Fallback to old logic if helper function not available
                    else {
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
                        else if(!empty($no_pel)) {
                            $is_excluded = function_exists('isItemExcludedFromMemberDiscount')
                                ? isItemExcludedFromMemberDiscount($koneksi, $kd) : false;
                            if(!$is_excluded && function_exists('getMemberDiscountForItem')) {
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
                header('Location: servis-input-reguler.php?snoserv=' . urlencode($no_service) . '&tab=items#service-items');
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
                                echo "<script>alert('Jasa tidak sesuai kategori motor.');window.location.href='servis-input-reguler.php?snoserv=" . urlencode($no_service) . "&tab=jasa#service-jasa';</script>";
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
                    
                    // === DISCOUNT LOGIC IMPLEMENTATION (Session-based from Preview, fallback Promo/Member) ===
                    $diskon_source = 'none';
                    $diskon_persen = 0;
                    $diskon_nominal = 0;
                    $id_promo = 'NULL';

                    // Get plate for session discount matching
                    $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan, no_polisi FROM tblservice WHERE no_service='$no_service'");
                    $cust_row = mysqli_fetch_assoc($cust_query);
                    $no_pel = $cust_row['no_pelanggan'] ?? '';
                    $no_polisi_item = $cust_row['no_polisi'] ?? '';

                    // Try session-based active discount first
                    if(function_exists('getActiveDiscountForService') && !empty($no_polisi_item)) {
                        $active_discount = getActiveDiscountForService($koneksi, $no_polisi_item);

                        if($active_discount['apply_discount']) {
                            // For member discount, check exclusion
                            $is_excluded = false;
                            if($active_discount['discount_type'] == 'member') {
                                if(function_exists('isItemExcludedFromDiscount')) {
                                    $is_excluded = isItemExcludedFromDiscount($koneksi, $kdj);
                                } elseif(function_exists('isItemExcludedFromMemberDiscount')) {
                                    $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $kdj);
                                }
                            }

                            if(!$is_excluded) {
                                if($active_discount['discount_type'] == 'periode') {
                                    $diskon_source = 'promo';
                                    $id_promo = $active_discount['promo_id'] ? $active_discount['promo_id'] : 'NULL';
                                    $diskon_persen = floatval($active_discount['diskon_jasa']);
                                    $diskon_nominal = $harga * ($diskon_persen / 100);
                                } else if($active_discount['discount_type'] == 'member') {
                                    $diskon_source = 'member';
                                    $diskon_persen = floatval($active_discount['diskon_jasa']);
                                    $diskon_nominal = $harga * ($diskon_persen / 100);
                                }
                            }
                        }
                    } else {
                        // Fallback legacy logic below
                    }
                    $potsrv = 0; // Reset pot manual form
                    
                    // 1. Check Promo Periode (Priority) - Fallback if no session discount
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
                    // 2. Check Member Discount (If no promo & no session)
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
                header('Location: servis-input-reguler.php?snoserv=' . urlencode($no_service) . '&tab=jasa#service-jasa');
                exit;
            }

            // Cari workorder (Handler untuk tombol Cari di Work Order tab)
            if (isset($_POST['btncariwo'])) {
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $txtcariwo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');
                $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');
                $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

                $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
                $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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

                $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
                $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=workorder';
                    </script>";
                    exit;
                } else {
                    echo "<script>
                        alert('Keluhan tidak boleh kosong!');
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=workorder';
                    </script>";
                    exit;
                }
            }

            // Tambah workorder ke SPK (Handler untuk tombol Tambah Work Order)
            if (isset($_POST['btnaddworkorder'])) {
                $no_service_post = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
                $kode_wo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

                $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
                $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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
                                echo "<script>alert('Work Order tidak sesuai kategori motor.');window.location.href='servis-input-reguler.php?snoserv={$no_service_post}&tab=workorder#workorder-details';</script>";
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

                            // ====================================================================
                            // CONDITIONAL AUTO-APPROVE:
                            // - Jika ada Diskon Periode AKTIF dan user klik "Terapkan Diskon" → AUTO-APPROVE
                            // - Jika "Tanpa Diskon" atau tidak ada promo periode → Item tetap PENDING
                            //   dan perlu approval di Tab Temuan & Penawaran
                            // ====================================================================
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
                                $qsvc = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service_post' LIMIT 1");
                                $svc = $qsvc ? mysqli_fetch_assoc($qsvc) : null;
                                $no_polisi_svc = $svc['no_polisi'] ?? '';
                                $tgl_cek = date('Y-m-d');

                                $q_pending_all = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE no_service='$no_service_post' AND wo_id='".$wo_id."' AND status_approval='pending'");
                                if($q_pending_all) {
                                while($pending_data = mysqli_fetch_assoc($q_pending_all)) {
                                    if($pending_data['tipe'] == 'barang') {
                                        // Cek duplikat
                                        $cek = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_barang WHERE no_service='$no_service_post' AND no_item='".$pending_data['kode_item']."'");
                                        $c = $cek ? mysqli_fetch_assoc($cek)['c'] : 0;
                                        if($c == 0) {
                                            $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_barang WHERE no_service='$no_service_post'");
                                            $nb = $q_nb ? mysqli_fetch_assoc($q_nb)['n'] : 1;

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
                                            $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode WHERE target_type='workorder' AND (target_id='".mysqli_real_escape_string($koneksi, $kode_wo)."' OR FIND_IN_SET('".mysqli_real_escape_string($koneksi, $kode_wo)."', target_id)) AND status_aktif=1 AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY nilai_promo DESC LIMIT 1");
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

                                            mysqli_query($koneksi, "INSERT INTO tblservis_barang (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('$no_service_post', '".$nb."', '".$pending_data['kode_item']."', '".$qty."', '0', '".$harga."', '".$diskon_persen."', '".$subtotal."', '".$diskon_source."', '".$diskon_persen."', '".$diskon_nominal."', $id_promo)");
                                            $auto_barang++;
                                        }
                                    } else { // jasa
                                        // Cek duplikat
                                        $cek = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM tblservis_jasa WHERE no_service='$no_service_post' AND no_item='".$pending_data['kode_item']."'");
                                        $c = $cek ? mysqli_fetch_assoc($cek)['c'] : 0;
                                        if($c == 0) {
                                            $q_nb = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris),0)+1 AS n FROM tblservis_jasa WHERE no_service='$no_service_post'");
                                            $nb = $q_nb ? mysqli_fetch_assoc($q_nb)['n'] : 1;

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
                                            $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode WHERE target_type='workorder' AND (target_id='".mysqli_real_escape_string($koneksi, $kode_wo)."' OR FIND_IN_SET('".mysqli_real_escape_string($koneksi, $kode_wo)."', target_id)) AND status_aktif=1 AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai ORDER BY nilai_promo DESC LIMIT 1");
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

                                            mysqli_query($koneksi, "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, waktu, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo) VALUES ('$no_service_post', '".$nb."', '".$pending_data['kode_item']."', '".$waktu."', '".$harga."', '".$diskon_persen."', '".$subtotal."', '".$diskon_source."', '".$diskon_persen."', '".$diskon_nominal."', $id_promo)");
                                            $auto_jasa++;
                                        }
                                    }

                                    // Update pending status ke disetujui
                                    mysqli_query($koneksi, "UPDATE tbservis_pending_items SET status_approval='disetujui', approved_by='".($_SESSION['_iduser'] ?? 'system')."', approved_at=NOW(), updated_at=NOW() WHERE id='".$pending_data['id']."'");
                                    $auto_approved++;
                                }
                            }

                                $msg = "Work Order berhasil ditambahkan dan item otomatis masuk ke servis!";
                                if($auto_barang > 0 || $auto_jasa > 0) {
                                    $msg .= "\\n- Ditambahkan: ".$auto_barang." barang, ".$auto_jasa." jasa.";
                                    $msg .= "\\nSilakan cek di tab Item Barang/Jasa.";
                                }
                                echo "<script>
                                    alert('" . addslashes($msg) . "');
                                    window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=items#service-items';
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
                                    window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan#temuan-penawaran';
                                </script>";
                                exit;
                            }
                        } else {
                            echo "<script>
                                alert('Kode Work Order tidak ditemukan di master!');
                                window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=workorder';
                            </script>";
                            exit;
                        }
                    } else {
                        echo "<script>
                            alert('Work Order ini sudah ditambahkan untuk service ini!');
                            window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=workorder';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('Kode Work Order tidak boleh kosong!');
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=workorder';
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
                                // Get next nobaris for barang
                                $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                                     FROM tblservis_barang WHERE no_service='$no_service_post'");
                                $nobaris_data = mysqli_fetch_array($q_nobaris);
                                $nobaris_barang = $nobaris_data['next_nobaris'] ?? 1;

                                // Calculate discount for barang using session promo/member
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
                                    file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG BARANG: Item={$pending_data['kode_item']}, SQL=$wo_condition_sql" . PHP_EOL, FILE_APPEND);

                                    $tgl_cek = date('Y-m-d');
                                    $q_promo_wo = mysqli_query($koneksi, "SELECT id_promo, tipe_promo, nilai_promo FROM master_diskon_periode 
                                                                         WHERE target_type='workorder' 
                                                                           AND ($wo_condition_sql)
                                                                           AND status_aktif=1
                                                                           AND '".$tgl_cek."' BETWEEN tanggal_mulai AND tanggal_selesai
                                                                         ORDER BY nilai_promo DESC LIMIT 1");
                                    if($q_promo_wo && ($prow = mysqli_fetch_assoc($q_promo_wo))) {
                                        $wo_persen = 0; $wo_nominal = 0; $harga_tmp = floatval($pending_data['harga_satuan']);
                                        if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                            $wo_nominal = floatval($prow['nilai_promo']);
                                            $wo_persen = ($harga_tmp > 0) ? ($wo_nominal / $harga_tmp * 100) : 0;
                                        } else {
                                            $wo_persen = floatval($prow['nilai_promo']);
                                            $wo_nominal = $harga_tmp * ($wo_persen / 100);
                                        }

                                        file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG PROMO FOUND: ID={$prow['id_promo']}, Val={$prow['nilai_promo']}, Tipe={$prow['tipe_promo']}" . PHP_EOL, FILE_APPEND);
                                        file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG CALC: WONom=$wo_nominal, CurrDisk=$diskon_nominal, Price=$harga_tmp" . PHP_EOL, FILE_APPEND);

                                        if($wo_nominal > $diskon_nominal) {
                                            $diskon_nominal = $wo_nominal;
                                            $diskon_persen = $wo_persen;
                                            $diskon_source = 'promo';
                                            $id_promo = intval($prow['id_promo']);
                                            file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG APPLY: Applied WO Discount (New=$diskon_nominal)" . PHP_EOL, FILE_APPEND);
                                        } else {
                                            file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG SKIP: WONom <= CurrDisk" . PHP_EOL, FILE_APPEND);
                                        }
                                    } else {
                                        file_put_contents(__DIR__.'/_debug_log.txt', "DEBUG PROMO: No promo found or query failed" . PHP_EOL, FILE_APPEND);
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
                                                       ('$no_service_post', '$nobaris_barang', '{$pending_data['kode_item']}', '$qty_appr',
                                                        '0', '$harga_jual', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)";
                                mysqli_query($koneksi, $sql_insert_barang);
                            }
                        } else {
                            // Jasa
                            $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_jasa
                                                                     WHERE no_service='$no_service_post'
                                                                     AND no_item='{$pending_data['kode_item']}'");
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

                                // Workorder-level promo for jasa (prefer larger)
                                $kode_wo_appr = '';
                                if(!empty($pending_data['wo_id'])) {
                                    $qwo = mysqli_query($koneksi, "SELECT kode_wo FROM tbservis_workorder WHERE id='".intval($pending_data['wo_id'])."' LIMIT 1");
                                    if($qwo && ($rwo = mysqli_fetch_assoc($qwo))) { $kode_wo_appr = $rwo['kode_wo'] ?? ''; }
                                }
                                if(!empty($kode_wo_appr)) {
                                    // ROBUST MATCHING for Approval Jasa
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
                                        $wo_persen = 0; $wo_nominal = 0; $harga_tmp = floatval($pending_data['harga_satuan']);
                                        if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                            $wo_nominal = floatval($prow['nilai_promo']);
                                            $wo_persen = ($harga_tmp > 0) ? ($wo_nominal / $harga_tmp * 100) : 0;
                                        } else {
                                            $wo_persen = floatval($prow['nilai_promo']);
                                            $wo_nominal = $harga_tmp * ($wo_persen / 100);
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
                                                    ('$no_service_post', '$nobaris_jasa', '{$pending_data['kode_item']}',
                                                     '$waktu_jasa', '$harga_jasa', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)";
                                mysqli_query($koneksi, $sql_insert_jasa);
                            }
                        }

                        // Update status pending item to approved
                        mysqli_query($koneksi, "UPDATE tbservis_pending_items
                                               SET status_approval='disetujui',
                                                   updated_at=NOW()
                                               WHERE id='$pending_item_id'");

                        echo "<script>
                            alert('Item berhasil disetujui dan ditambahkan ke " . ($pending_data['tipe'] == 'barang' ? 'Item Barang' : 'Item Jasa') . "!');
                            window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    } else {
                        echo "<script>
                            alert('Item tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID item tidak valid!');
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
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
                                                                         AND no_item='{$pending_data['kode_item']}'");
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

                                    // Workorder-level promo (prefer larger) - bulk barang
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
                                            $wo_persen = 0; $wo_nominal = 0; $harga_tmp = floatval($pending_data['harga_satuan']);
                                            if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                                $wo_nominal = floatval($prow['nilai_promo']);
                                                $wo_persen = ($harga_tmp > 0) ? ($wo_nominal / $harga_tmp * 100) : 0;
                                            } else {
                                                $wo_persen = floatval($prow['nilai_promo']);
                                                $wo_nominal = $harga_tmp * ($wo_persen / 100);
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
                                                           ('$no_service_post', '$nobaris_barang', '{$pending_data['kode_item']}',
                                                            '$qty_appr', '0', '$harga_jual', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
                                    $count_barang++;
                                }
                            } else {
                                // Jasa
                                $check_existing = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblservis_jasa
                                                                         WHERE no_service='$no_service_post'
                                                                         AND no_item='{$pending_data['kode_item']}'");
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

                                    // Workorder-level promo (prefer larger) - bulk jasa
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
                                            $wo_persen = 0; $wo_nominal = 0; $harga_tmp = floatval($pending_data['harga_satuan']);
                                            if(($prow['tipe_promo'] ?? '') === 'nominal') {
                                                $wo_nominal = floatval($prow['nilai_promo']);
                                                $wo_persen = ($harga_tmp > 0) ? ($wo_nominal / $harga_tmp * 100) : 0;
                                            } else {
                                                $wo_persen = floatval($prow['nilai_promo']);
                                                $wo_nominal = $harga_tmp * ($wo_persen / 100);
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
                                                           ('$no_service_post', '$nobaris_jasa', '{$pending_data['kode_item']}',
                                                            '$waktu_jasa', '$harga_jasa', '$diskon_persen', '$subtotal', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
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
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan-penawaran';
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
                            window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    } else {
                        echo "<script>
                            alert('Item tidak ditemukan atau sudah diproses!');
                            window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
                        </script>";
                        exit;
                    }
                } else {
                    echo "<script>
                        alert('ID item tidak valid!');
                        window.location.href = 'servis-input-reguler.php?snoserv=$no_service_post&tab=temuan';
                    </script>";
                    exit;
                }
            }

        }

    // ========================================
    // HANDLER UPDATE BARANG
    // ========================================
    if(isset($_POST['btnupdatebrg'])){
        $id_detail = (int) ($_POST['id_detail'] ?? 0);
        $qty_baru = max(1, (int) ($_POST['qty_brg'] ?? 1));
        $harga_baru = (float) str_replace(array('.',','),'',''.($_POST['hrg_brg'] ?? '0').''); // Clean currency format
        $diskon_persen_baru = (float) ($_POST['disc_brg'] ?? 0);

        $no_service_post = $_POST['txtnosrv'] ?? $no_service; // Ensure no_service is set from post if available

        // Get current data to preserve unedited info if needed
        $q_curr = mysqli_query($koneksi, "SELECT * FROM tblservis_barang WHERE id={$id_detail}");
        if($q_curr && mysqli_num_rows($q_curr) > 0) {
            $curr = mysqli_fetch_array($q_curr);
            
            $subtotal_gross = $harga_baru * $qty_baru;
            $diskon_nominal_baru = ($harga_baru * $qty_baru) * ($diskon_persen_baru / 100); // Diskon is typically per line total in this logic or per item? 
            // Previous logic: $diskon_nominal = $harga_jual * ($diskon_persen / 100); -> Per Unit
            // Let's stick to Per Unit calculation for consistency with btnadd
            
            // Re-calculate based on per-unit discount
            $diskon_nominal_per_unit = $harga_baru * ($diskon_persen_baru / 100);
            $harga_net_satuan = $harga_baru - $diskon_nominal_per_unit;
            $total_baru = $harga_net_satuan * $qty_baru;
            
            // Total Diskon Nominal for the line (stored in diskon_nominal usually refers to per unit or total? In btnadd it seemed per unit in one place and total in another? 
            // In btnadd: $diskon_nominal = $harga_jual * ($diskon_persen / 100); -> Per Unit
            // So we store Per Unit nominal discount.
            
            $diskon_nominal_store = $diskon_nominal_per_unit;

            // Update source if generic 'none'
            $source = $curr['diskon_source'];
            if(empty($source) || $source == 'none') {
                $source = ($diskon_persen_baru > 0) ? 'manual' : 'none';
            }
            
            $sql_update = "UPDATE tblservis_barang SET
                            quantity={$qty_baru},
                            harga_jual={$harga_baru},
                            diskon_persen={$diskon_persen_baru},
                            diskon_nominal={$diskon_nominal_store},
                            potongan={$diskon_persen_baru},
                            total={$total_baru},
                            diskon_source='$source'
                           WHERE id={$id_detail}";
                           
            if(mysqli_query($koneksi, $sql_update)){
                 echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service_post&tab=items';</script>";
                 exit;
            } else {
                 $error_msg = addslashes(mysqli_error($koneksi));
                 echo "<script>alert('Gagal update item: $error_msg'); window.location='servis-input-reguler.php?snoserv=$no_service_post&tab=items';</script>";
                 exit;
            }
        }
        exit;
    }

    // ========================================
    // HANDLER UPDATE JASA
    // ========================================
    if(isset($_POST['btnupdatesrv'])){
        $id_detail = (int) ($_POST['id_detail'] ?? 0);
        $harga_baru = (float) str_replace(array('.',','),'',''.($_POST['hrg_srv'] ?? '0').'');
        $waktu_baru = (int) ($_POST['waktu_srv'] ?? 0);
        $diskon_persen_baru = (float) ($_POST['disc_srv'] ?? 0);

        $no_service_post = $_POST['txtnosrv'] ?? $no_service;

        // Get current data to preserve diskon_source if unedited
        $q_curr = mysqli_query($koneksi, "SELECT diskon_source FROM tblservis_jasa WHERE id={$id_detail}");
        $curr = $q_curr ? mysqli_fetch_array($q_curr) : null;
        $source = $curr['diskon_source'] ?? '';
        if(empty($source) || $source == 'none') {
            $source = ($diskon_persen_baru > 0) ? 'manual' : 'none';
        }

        // Calculate (jasa = 1 unit per baris, tidak ada kolom quantity)
        $diskon_nominal_per_unit = $harga_baru * ($diskon_persen_baru / 100);
        $harga_net_satuan = $harga_baru - $diskon_nominal_per_unit;
        $total_baru = $harga_net_satuan;

        $sql_update = "UPDATE tblservis_jasa SET
                        harga={$harga_baru},
                        waktu={$waktu_baru},
                        diskon_persen={$diskon_persen_baru},
                        diskon_nominal={$diskon_nominal_per_unit},
                        total={$total_baru},
                        diskon_source='$source'
                       WHERE id={$id_detail}";

        if(mysqli_query($koneksi, $sql_update)){
             echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service_post&tab=jasa';</script>";
             exit;
        } else {
             $error_msg = addslashes(mysqli_error($koneksi));
             echo "<script>alert('Gagal update jasa: $error_msg'); window.location='servis-input-reguler.php?snoserv=$no_service_post&tab=jasa';</script>";
             exit;
        }
        exit;
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
                // Jika ada 2 kepala mekanik, set default 50:50 (atau sesuaikan kebutuhan user)
                // Tapi user minta "otomatis 100%", mungkin maksudnya total? 
                // Kita asumsi KM 1 tetap prioritas atau bagi rata.
                // Untuk keamanan, kita bagi 50:50 jika ada 2.
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
            // Jika belum input Kepala Mekanik Harian
            // Tampilkan notifikasi peringatan
             echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (document.querySelector('.alert-km-missing')) {
                        return;
                    }

                    var container = document.querySelector('.page-content') || document.body;
                    if (!container) {
                        return;
                    }

                    var warningWrapper = document.createElement('div');
                    warningWrapper.className = 'alert alert-danger alert-dismissible alert-km-missing';
                    warningWrapper.style.margin = '10px 0';
                    warningWrapper.innerHTML =
                        '<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>' +
                        '<i class=\"icon fa fa-warning\"></i> <strong>PERHATIAN!</strong> ' +
                        'Kepala Mekanik Harian untuk tanggal hari ini belum diinput. ' +
                        '<a href=\"input_kepala_mekanik_harian.php\" style=\"font-weight:bold; text-decoration:underline;\">Klik disini untuk input sekarang</a>';

                    container.insertBefore(warningWrapper, container.firstChild);
                });
            </script>";
        }
    }
    // ========== END AUTO-FILL KEPALA MEKANIK HARIAN ==========
    
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Input Service Reguler</title>

    <meta name="description" content="Input Service Reguler - Redesign v2.0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Bootstrap 4 & FontAwesome (Local files to avoid CDN blocking) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

    <!-- jQuery UI for datepicker -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- Fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- Redesign Styles -->
    <?php include "_template/_redesign_styles.php"; ?>

    <style>
    /* Page-specific overrides */
    body {
        background: #f4f6f9;
        font-family: 'Open Sans', 'Segoe UI', Tahoma, sans-serif;
    }

    .navbar {
        min-height: 56px;
        margin-bottom: 0;
        border: 0;
        border-radius: 0;
        box-shadow: 0 2px 8px rgba(31, 45, 61, 0.12);
    }

    .navbar .container-fluid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 56px;
        padding: 0 16px;
    }

    .navbar .container-fluid::before,
    .navbar .container-fluid::after {
        content: none;
        display: none;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        height: auto;
        padding: 0;
        color: #d8e6f3 !important;
        font-size: 28px;
        line-height: 1;
    }

    .navbar-brand i {
        color: #2d7fd3;
        font-size: 22px;
    }

    .navbar-nav {
        margin: 0;
        margin-left: auto;
    }

    .navbar .nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        color: #f3f7fb !important;
    }

    .navbar .nav-link:hover,
    .navbar .nav-link:focus {
        color: #ffffff !important;
        text-decoration: none;
    }

    .navbar-user-photo {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 2px solid rgba(255,255,255,0.55);
        object-fit: cover;
        flex-shrink: 0;
    }

    .navbar-user-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        line-height: 1.15;
        min-width: 0;
    }

    .navbar-user-info small {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .04em;
        opacity: .75;
        margin-bottom: 2px;
    }

    .navbar-user-name {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }

    .rd-page-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .rd-page-header {
        background: linear-gradient(135deg, var(--rd-primary) 0%, #3A7BC8 100%);
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

    /* Quick Summary Bar */
    .rd-quick-summary {
        background: white;
        border-radius: var(--rd-radius-md);
        padding: 16px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--rd-shadow-sm);
        border-left: 4px solid var(--rd-primary);
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

    .rd-quick-summary-item .value.primary { color: var(--rd-primary); }
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
    </style>
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #2C3E50;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fa fa-leaf"></i>
                <?php include "../lib/subtitel.php"; ?>
            </a>

            <div class="navbar-nav ml-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown">
                        <img src="../<?php echo $foto_user; ?>" alt="User" class="navbar-user-photo">
                        <span class="navbar-user-info">
                            <small>Welcome,</small>
                            <span class="navbar-user-name"><?php echo $_nama; ?></span>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="change_pwd.php">
                            <i class="fa fa-cog"></i> Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../logout.php">
                            <i class="fa fa-sign-out-alt"></i> Logout
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
                    <i class="fa fa-wrench"></i>
                    Input Service Reguler
                </h1>
                <div class="rd-breadcrumb" style="margin-top: 8px;">
                    <a href="index.php"><i class="fa fa-home"></i></a>
                    <span>/</span>
                    <a href="servis-reguler.php">Daftar Service</a>
                    <span>/</span>
                    <span>Input Service</span>
                </div>
            </div>
            <div>
                <a href="servis-reguler.php" class="rd-btn" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fa fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Quick Summary Bar -->
        <div class="rd-quick-summary">
            <div class="rd-quick-summary-left">
                <div class="rd-quick-summary-item">
                    <span class="label">No. Service</span>
                    <span class="value primary"><?= htmlspecialchars($no_service) ?: 'BARU' ?></span>
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
                    <span class="rd-badge solid-<?= $status_servis == 'bayar' ? 'success' : ($status_servis == 'proses' ? 'warning' : 'info') ?>">
                        <?= strtoupper($status_servis) ?>
                    </span>
                </div>
            </div>
            <div class="rd-quick-summary-right">
                <div class="rd-quick-summary-item" style="text-align: right;">
                    <span class="label">Total</span>
                    <span class="value success" style="font-size: 20px;">
                        Rp <?= number_format($tot, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Active Discount Banner -->
        <?php
        // Display active discount banner if applicable
        if(function_exists('displayActiveDiscountBanner') && !empty($no_polisi)) {
            echo displayActiveDiscountBanner($koneksi, $no_polisi);
        }
        ?>

        <!-- Main Form -->
        <form method="POST" action="" enctype="multipart/form-data" id="formService">
            <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">

            <!-- Tab Navigation -->
            <div class="rd-tabs-nav">
                <?php $tab_base_url = 'servis-input-reguler.php?snoserv=' . urlencode($no_service); ?>
                <a class="rd-tab-btn <?= $active_tab == 'service-details' ? 'active' : '' ?>" data-target="service-details" href="<?= $tab_base_url ?>&tab=details">
                    <i class="fa fa-info-circle"></i>
                    Detail Service
                </a>
                <a class="rd-tab-btn <?= $active_tab == 'workorder-details' ? 'active' : '' ?>" data-target="workorder-details" href="<?= $tab_base_url ?>&tab=workorder">
                    <i class="fa fa-clipboard-list"></i>
                    Work Order
                    <?php
                    $count_wo = 0;
                    $sql_wo_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_workorder WHERE no_service='$no_service'");
                    if($sql_wo_count) { $count_wo = mysqli_fetch_array($sql_wo_count)['total']; }
                    if($count_wo > 0): ?>
                    <span class="rd-badge"><?= $count_wo ?></span>
                    <?php endif; ?>
                </a>
                <a class="rd-tab-btn <?= $active_tab == 'temuan-penawaran' ? 'active' : '' ?>" data-target="temuan-penawaran" href="<?= $tab_base_url ?>&tab=temuan">
                    <i class="fa fa-search-plus"></i>
                    Temuan & Penawaran
                    <?php
                    $count_pending = 0;
                    $sql_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_pending_items WHERE no_service='$no_service' AND status_approval='pending'");
                    if($sql_pending) { $count_pending = mysqli_fetch_array($sql_pending)['total']; }
                    if($count_pending > 0): ?>
                    <span class="rd-badge warning"><?= $count_pending ?></span>
                    <?php endif; ?>
                </a>
                <a class="rd-tab-btn <?= $active_tab == 'service-items' ? 'active' : '' ?>" data-target="service-items" href="<?= $tab_base_url ?>&tab=items">
                    <i class="fa fa-box"></i>
                    Item Barang
                    <?php
                    $count_brg = 0;
                    $sql_brg_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tblservis_barang WHERE no_service='$no_service'");
                    if($sql_brg_count) { $count_brg = mysqli_fetch_array($sql_brg_count)['total']; }
                    if($count_brg > 0): ?>
                    <span class="rd-badge"><?= $count_brg ?></span>
                    <?php endif; ?>
                </a>
                <a class="rd-tab-btn <?= $active_tab == 'service-jasa' ? 'active' : '' ?>" data-target="service-jasa" href="<?= $tab_base_url ?>&tab=jasa">
                    <i class="fa fa-tools"></i>
                    Item Jasa
                    <?php
                    $count_jasa = 0;
                    $sql_jasa_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tblservis_jasa WHERE no_service='$no_service'");
                    if($sql_jasa_count) { $count_jasa = mysqli_fetch_array($sql_jasa_count)['total']; }
                    if($count_jasa > 0): ?>
                    <span class="rd-badge"><?= $count_jasa ?></span>
                    <?php endif; ?>
                </a>
                <a class="rd-tab-btn <?= $active_tab == 'service-actions' ? 'active' : '' ?>" data-target="service-actions" href="<?= $tab_base_url ?>&tab=actions">
                    <i class="fa fa-cash-register"></i>
                    Pembayaran
                </a>
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

                <!-- Tab 6: Actions/Payment -->
                <div id="service-actions" class="rd-tab-pane <?= $active_tab == 'service-actions' ? 'active' : '' ?>">
                    <?php include "_template/tab-actions-redesign.php"; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Include Modals -->
    <?php
    // Include existing modals
    $template_dir = __DIR__ . DIRECTORY_SEPARATOR . '_template' . DIRECTORY_SEPARATOR;
    if(file_exists($template_dir . "modal-callbacks.php")) include $template_dir . "modal-callbacks.php";
    if(file_exists($template_dir . "modal-search-temuan.php")) include $template_dir . "modal-search-temuan.php";
    if(file_exists($template_dir . "modal-search-keluhan.php")) include $template_dir . "modal-search-keluhan.php";
    if(file_exists($template_dir . "modal-fastmoves-v2.php")) include $template_dir . "modal-fastmoves-v2.php";
    if(file_exists($template_dir . "modal-fastmoves-part.php")) include $template_dir . "modal-fastmoves-part.php";
    if(file_exists($template_dir . "modal-tambah-keluhan-baru.php")) include $template_dir . "modal-tambah-keluhan-baru.php";
    if(file_exists($template_dir . "modal-input-barang-custom.php")) include $template_dir . "modal-input-barang-custom.php";
    if(file_exists($template_dir . "_modal_riwayat_kendaraan.php")) include $template_dir . "_modal_riwayat_kendaraan.php";
    if(file_exists($template_dir . "_modal_update_status_keluhan.php")) include $template_dir . "_modal_update_status_keluhan.php";
    if(file_exists($template_dir . "_modal_cancel_service.php")) include $template_dir . "_modal_cancel_service.php";

    // Include Statistik Pelanggan Modal
    if(!empty($kode_pelanggan) && function_exists('renderModalStatistikPelanggan')) {
        echo renderModalStatistikPelanggan($koneksi, $kode_pelanggan);
    }
    ?>

    <script>
    (function() {
        function getTabParam(target) {
            switch (target) {
                case 'service-items': return 'items';
                case 'service-jasa': return 'jasa';
                case 'workorder-details': return 'workorder';
                case 'service-actions': return 'actions';
                case 'temuan-penawaran': return 'temuan';
                default: return 'details';
            }
        }

        function initServiceTabs() {
            document.querySelectorAll('.rd-tab-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    var target = button.getAttribute('data-target');
                    window.switchServiceTab(target);
                });
            });

            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    var activeButton = document.querySelector('.rd-tab-btn.active');
                    var activeTarget = activeButton ? activeButton.getAttribute('data-target') : 'service-details';
                    ensureTabField(form, getTabParam(activeTarget));
                });
            });
        }

        function ensureTabField(form, tabParam) {
            if (!form) {
                return;
            }

            var field = form.querySelector('input[name="tab"]');
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = 'tab';
                form.appendChild(field);
            }
            field.value = tabParam;
        }

        window.switchServiceTab = function(target) {
            document.querySelectorAll('.rd-tab-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.getAttribute('data-target') === target);
            });

            document.querySelectorAll('.rd-tab-pane').forEach(function(pane) {
                pane.classList.toggle('active', pane.id === target);
            });

            ensureTabField(document.getElementById('formService'), getTabParam(target));
        };

        function showModal(modalId) {
            var modal = document.getElementById(modalId);
            if (!modal) {
                return false;
            }

            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
                window.jQuery(modal).modal('show');
            } else {
                modal.style.display = 'block';
                modal.classList.add('in');
            }
            return true;
        }

        window.toggleCardBody = function(header) {
            var body = header ? header.nextElementSibling : null;
            var icon = header ? header.querySelector('.rd-collapse-icon') : null;
            if (!header || !body) {
                return;
            }

            header.classList.toggle('collapsed');
            var isCollapsed = header.classList.contains('collapsed');
            body.style.display = isCollapsed ? 'none' : 'block';

            if (icon) {
                icon.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
            }
        };

        window.showStatistikPelanggan = function() {
            if (!showModal('modalStatistikPelanggan')) {
                alert('Modal statistik pelanggan belum tersedia');
            }
        };

        window.showRiwayatKendaraan = function() {
            if (!showModal('modalRiwayatKendaraan')) {
                alert('Modal riwayat kendaraan belum tersedia');
            }
        };

        window.updateStatusKeluhan = function(id) {
            var input = document.getElementById('keluhan_id');
            if (input) {
                input.value = id;
            }
            showModal('modalUpdateStatusKeluhan');
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initServiceTabs);
        } else {
            initServiceTabs();
        }
    })();
    </script>

</body>
<?php } ?>
</html>
