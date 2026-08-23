<?php
	session_start();

	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];
        $kd_cabang=$_SESSION['_cabang'];
		include "../config/koneksi.php";
		include_once "../lib/rbac.php";
		include_once "helper-functions.php";
		include_once "function_servis.php";
		rbac_require_any(array('lihat_servis_garansi_read','servis_garansi_read','servis_menu_read','service_read'));
		include "_include_customer_vehicle_sync.php";
		include "_include_statistik_pelanggan.php";
		include "_include_kategori_member.php"; // Member kategori & discount helper
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";
		include "_include_komisi_snapshot.php";

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
			$foto_user="assets/images/avatars/avatar.png";
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
    $txtcaribrg=mysqli_real_escape_string($koneksi, $_GET['kd'] ?? '');
    $txtcarisrv=mysqli_real_escape_string($koneksi, $_GET['kdjasa'] ?? '');
    $txtcariwo=mysqli_real_escape_string($koneksi, $_GET['kdwo'] ?? '');

    // ========== HANDLE REFERENCE SERVICE (FOR WARRANTY) ==========
    // ref_service contains the original service number that this warranty is based on
    $ref_service = $_GET['ref_service'] ?? '';
    $ref_service_data = null;
    
    if(!empty($ref_service)) {
        $ref_service_escaped = mysqli_real_escape_string($koneksi, $ref_service);
        $q_ref = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$ref_service_escaped'");
        if($q_ref && mysqli_num_rows($q_ref) > 0) {
            $ref_service_data = mysqli_fetch_assoc($q_ref);

            // If this is a new warranty (no existing no_service), pre-fill customer & vehicle data
            if(empty($no_service)) {
                $kode_pelanggan = $ref_service_data['no_pelanggan'] ?? '';
                $no_polisi = $ref_service_data['no_polisi'] ?? '';
            }
        }
    } elseif (!empty($no_service)) {
        // Buka ulang garansi yang sudah dibuat: ref_service tidak ada di URL,
        // ambil dari kolom ref_no_service_original yang tersimpan di tblservice.
        $ns_esc = mysqli_real_escape_string($koneksi, $no_service);
        $q_self = mysqli_query($koneksi, "SELECT ref_no_service_original FROM tblservice WHERE no_service='$ns_esc'");
        if ($q_self && ($row_self = mysqli_fetch_assoc($q_self)) && !empty($row_self['ref_no_service_original'])) {
            $ref_service = $row_self['ref_no_service_original'];
            $ref_service_escaped = mysqli_real_escape_string($koneksi, $ref_service);
            $q_ref = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$ref_service_escaped'");
            if ($q_ref && mysqli_num_rows($q_ref) > 0) {
                $ref_service_data = mysqli_fetch_assoc($q_ref);
            }
        }
    }

    // Handler untuk submit form
    if(isset($_POST['btnsimpan'])) {
        // Generate nomor service jika belum ada
        if(empty($no_service)) {
            $tanggal_service = date('Y-m-d');

            // Generate nomor service untuk garansi
            // FIX 2026-08-23: SELECT ... ORDER BY DESC LIMIT 1 lalu +1 rawan
            // race condition. Ganti ke atomic counter per prefix
            // (function_servis.php::NextServiceSeqByPrefix). Format
            // no_service TIDAK berubah (masih dicek servis-estimasi-pdf.php).
            $prefix_service = 'GAR-' . date('Ymd') . '-';
            $new_number = NextServiceSeqByPrefix($koneksi, $prefix_service, $prefix_service);

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

            $keluhan_esc = mysqli_real_escape_string($koneksi, $keluhan);
            // F1-A: set garansi fields
            $ref_original    = !empty($ref_service) ? mysqli_real_escape_string($koneksi, $ref_service) : '';
            $tgl_expire      = '';
            $mekanik_orig    = '';
            $komisi_mode     = 'unknown';
            if (!empty($ref_service_data)) {
                $tgl_asal    = $ref_service_data['tanggal'] ?? '';
                if ($tgl_asal) {
                    // F1-A: masa garansi dinamis per tier member (jawaban A3, 2026-07-04),
                    // bukan flat 7 hari. Lihat tbmaster_kategori_member.masa_garansi_hari.
                    $masa_garansi_standar = 7;
                    if (function_exists('getMasaGaransiHari') && !empty($ref_service_data['no_pelanggan'])) {
                        $mg = getMasaGaransiHari($koneksi, $ref_service_data['no_pelanggan']);
                        $masa_garansi_standar = $mg['standar'];
                    }
                    $tgl_expire = date('Y-m-d', strtotime($tgl_asal . " +{$masa_garansi_standar} days"));
                }
                // F1-B: derive komisi mode
                $mekanik_orig = $ref_service_data['mekanik1'] ?? '';
            }
            $is_garansi_flag = !empty($ref_original) ? 1 : 0;

            $query_insert_service = "INSERT INTO tblservice (
                no_service, tanggal, jam, no_pelanggan, no_polisi, kd_cabang, id_user,
                status, status_servis, status_jemput, keterangan,
                is_garansi, ref_no_service_original, tanggal_garansi_expire,
                mekanik_original, komisi_garansi_mode, created_at
            ) VALUES (
                '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan',
                '$no_polisi', '$kd_cabang', '$id_user',
                '1', 'datang', '0', '$keluhan_esc',
                '$is_garansi_flag', '$ref_original', " . ($tgl_expire ? "'$tgl_expire'" : "NULL") . ",
                '$mekanik_orig', '$komisi_mode', NOW()
            )";

            if(mysqli_query($koneksi, $query_insert_service)) {
                // Insert ke tabel antrian dengan prioritas tinggi untuk garansi
                $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                    no_service, no_antrian, tanggal, jam_ambil,
                    status_antrian, prioritas, created_at
                ) VALUES (
                    '$no_service', '$no_antrian', '$tanggal_service', '$jam_input',
                    'menunggu', 'urgent', NOW()
                )";

                if(mysqli_query($koneksi, $query_insert_antrian)) {
                    echo "<script>
                        alert('Service Garansi berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian\\n(Prioritas Tinggi)');
                        window.location.href = 'servis-garansi.php?snoserv=$no_service';
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
        
        // If ref_service exists, pre-fill customer & vehicle data from reference service
        if(!empty($ref_service) && !empty($ref_service_data)) {
            $kode_pelanggan = $ref_service_data['no_pelanggan'] ?? '';
            $no_polisi = $ref_service_data['no_polisi'] ?? '';
        }
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

            // F1-B: Komisi garansi mode — bandingkan mekanik1 baru vs mekanik_original
            $no_service_esc_mk = mysqli_real_escape_string($koneksi, $no_service);
            $q_orig = mysqli_query($koneksi, "SELECT mekanik_original FROM tblservice WHERE no_service='$no_service_esc_mk' LIMIT 1");
            $row_orig = mysqli_fetch_assoc($q_orig);
            $mekanik_original_saved = $row_orig['mekanik_original'] ?? '';
            $komisi_mode_baru = 'unknown';
            if (!empty($mekanik_original_saved) && !empty($mekanik1)) {
                $komisi_mode_baru = ($mekanik1 === $mekanik_original_saved) ? 'skip' : 'transfer';
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
                komisi_garansi_mode='$komisi_mode_baru',
                updated_at=NOW()
                WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'";

            if(mysqli_query($koneksi, $update_mechanic)) {
                echo "<script>
                    alert('Data mekanik garansi berhasil diupdate!');
                    window.location='servis-garansi.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions';
                </script>";
            } else {
                echo "<script>alert('Error update data mekanik: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // ========== HANDLER: HAPUS ITEM BARANG/JASA (GARANSI) ==========
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
            header('Location: servis-garansi.php?snoserv=' . urlencode($no_service) . '&tab=service-items');
            exit;
        }
        if (isset($_GET['hapus_srv'])) {
            $id_hapus = (int)$_GET['hapus_srv'];
            if ($sudah_tutup) {
                echo "<script>alert('Jasa tidak bisa dihapus — servis sudah " . strtoupper($row_st['status_servis']) . ".');history.back();</script>"; exit;
            }
            mysqli_query($koneksi, "DELETE FROM tblservis_jasa WHERE id=$id_hapus AND no_service='$no_srv_esc'");
            header('Location: servis-garansi.php?snoserv=' . urlencode($no_service) . '&tab=service-jasa');
            exit;
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
            // NB: query lama di sini pakai kolom target_type/target_id yang sudah
            // di-drop migrasi 2026-07-18 Promo Engine — diganti calculateItemDiscount()
            // (skema baru: master_diskon_periode_target + syarat kelayakan + multi-cabang).
            $diskon_source = 'none';
            $diskon_persen = 0;
            $diskon_nominal = 0;
            $id_promo = 'NULL';
            $pot = 0;

            $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
            $cust_row = mysqli_fetch_assoc($cust_query);
            $no_pel = $cust_row['no_pelanggan'] ?? '';

            if(!empty($no_pel) && function_exists('calculateItemDiscount')) {
                $disc = calculateItemDiscount($koneksi, $no_pel, $kd, 'barang', $harga);
                if(($disc['diskon_nominal'] ?? 0) > 0) {
                    $diskon_persen = floatval($disc['diskon_persen']);
                    $diskon_nominal = floatval($disc['diskon_nominal']);
                    $src = $disc['discount_source'] ?? '';
                    $diskon_source = (stripos($src, 'promo') !== false) ? 'promo' : ((stripos($src, 'member') !== false) ? 'member' : '');
                    if(!empty($disc['promo_breakdown'][0]['id_promo'])) {
                        $id_promo = intval($disc['promo_breakdown'][0]['id_promo']);
                    }
                }
            }

            // Calculate Total
            $total_diskon_amt = $diskon_nominal * $qty;
            $subtotal = ($harga * $qty) - $total_diskon_amt;

            // Get next nobaris for barang
            $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
            $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
            $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

            // Insert with discount columns
            mysqli_query($koneksi, "INSERT INTO tblservis_barang
                (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total,
                 diskon_source, diskon_persen, diskon_nominal, id_promo)
                VALUES
                ('$no_service', '$nobaris_brg', '$kd', '$qty', 0, '$harga', '$diskon_persen', '$subtotal',
                 '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
            if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'barang', $kd); }
        }
        // Redirect back
        header('Location: servis-garansi.php?snoserv=' . urlencode($no_service) . '&tab=service-items#service-items');
        exit;
    }

    // Tambah part milik customer (F1-E) — tidak potong stok
    if (isset($_POST['btnadd_partcust'])) {
        $no_service = $_POST['txtnosrv'] ?? $no_service;
        if (!empty($no_service)) {
            $no_service_esc_pc = mysqli_real_escape_string($koneksi, $no_service);
            $pc_nama    = mysqli_real_escape_string($koneksi, trim($_POST['pc_nama']    ?? ''));
            $pc_merek   = mysqli_real_escape_string($koneksi, trim($_POST['pc_merek']   ?? ''));
            $pc_kondisi = mysqli_real_escape_string($koneksi, $_POST['pc_kondisi'] ?? 'ORI');
            $pc_harga   = (float)($_POST['pc_harga'] ?? 0);
            $pc_qty     = max(1, (int)($_POST['pc_qty'] ?? 1));
            $pc_total   = $pc_harga * $pc_qty;
            $pc_ket     = '[PART-CUST: ' . $pc_nama . ' | ' . ($pc_merek ?: '-') . ' | ' . $pc_kondisi . ']';
            if (!empty($pc_nama)) {
                mysqli_query($koneksi, "INSERT INTO tblservis_barang
                    (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo, keterangan, asal_barang)
                    VALUES
                    ('$no_service_esc_pc', 0, 'PART-CUST', $pc_qty, 0, $pc_harga, 0, $pc_total,
                     'none', 0, 0, 0, '$pc_ket', 'PART-CUST')");
            }
        }
        header('Location: servis-garansi.php?snoserv=' . urlencode($no_service) . '&tab=items#service-items');
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
            // NB: query lama di sini pakai kolom target_type/target_id yang sudah
            // di-drop migrasi 2026-07-18 Promo Engine — diganti calculateItemDiscount().
            $diskon_source = 'none';
            $diskon_persen = 0;
            $diskon_nominal = 0;
            $id_promo = 'NULL';
            $potsrv = 0;

            $cust_query = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
            $cust_row = mysqli_fetch_assoc($cust_query);
            $no_pel = $cust_row['no_pelanggan'] ?? '';

            if(!empty($no_pel) && function_exists('calculateItemDiscount')) {
                $disc = calculateItemDiscount($koneksi, $no_pel, $kdj, 'jasa', $harga);
                if(($disc['diskon_nominal'] ?? 0) > 0) {
                    $diskon_persen = floatval($disc['diskon_persen']);
                    $diskon_nominal = floatval($disc['diskon_nominal']);
                    $src = $disc['discount_source'] ?? '';
                    $diskon_source = (stripos($src, 'promo') !== false) ? 'promo' : ((stripos($src, 'member') !== false) ? 'member' : '');
                    if(!empty($disc['promo_breakdown'][0]['id_promo'])) {
                        $id_promo = intval($disc['promo_breakdown'][0]['id_promo']);
                    }
                }
            }

            $total = $harga - $diskon_nominal;

            // Get next nobaris for jasa
            $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
            $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
            $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

            $keterangan_jasa = mysqli_real_escape_string($koneksi, $_POST['keterangan_jasa'] ?? '');

            // Cek apakah kolom waktu tersedia
            $has_waktu = false;
            $chk = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
            if ($chk && mysqli_num_rows($chk) > 0) { $has_waktu = true; }

            if ($has_waktu) {
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                    (no_service, nobaris, no_item, harga, waktu, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo, keterangan)
                    VALUES
                    ('$no_service', '$nobaris_jasa', '$kdj', '$harga', '$waktu', '$diskon_persen', '$total',
                     '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo, '$keterangan_jasa')");
            } else {
                mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                    (no_service, nobaris, no_item, harga, potongan, total,
                     diskon_source, diskon_persen, diskon_nominal, id_promo, keterangan)
                    VALUES
                    ('$no_service', '$nobaris_jasa', '$kdj', '$harga', '$diskon_persen', '$total',
                     '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo, '$keterangan_jasa')");
            }
            if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'jasa', $kdj); }
        }
        // Redirect back
        header('Location: servis-garansi.php?snoserv=' . urlencode($no_service) . '&tab=service-jasa#service-jasa');
        exit;
    }

    // ========== HANDLER: ADD KELUHAN TO SPK (GARANSI) ==========
    if(isset($_POST['btnaddkeluhan'])) {
        $no_service = $_POST['txtnosrv'];
        $txtkeluhan = $_POST['txtkeluhan'];

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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
                                   WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'");

            echo"<script>
                alert('Keluhan berhasil ditambahkan ke SPK Garansi!');
                window.location=('servis-garansi.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo');
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
        $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
        $txtcariwo = $_POST['txtcariwo'] ?? '';
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        // Update KM data before redirecting
        if(!empty($no_service)) {
            mysqli_query($koneksi,"UPDATE tblservice
                                   SET km_skr='$km_skr', km_berikut='$km_berikut'
                                   WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'");
        }

        // Redirect to workorder search page
        $cbocari = "";
        $cbourut = "52";
        echo"<script>window.location=('servis-add-workorder-cari.php?snoserv=" . urlencode($no_service) . "&_key=" . urlencode($txtcariwo) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc');</script>";
    }

    // ========== HANDLER: ADD WORKORDER TO SPK (GARANSI) ==========
    if(isset($_POST['btnaddworkorder'])) {
        $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
        $kode_wo = mysqli_real_escape_string($koneksi, $_POST['txtcariwo'] ?? '');

        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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

                    // Expand baris tipe=3 (kombinasi WO) jadi baris jasa/barang milik WO anak (1 level).
                    $detail_rows = array();
                    while($detail_wo && ($detail = mysqli_fetch_array($detail_wo))) {
                        if($detail['tipe'] == '3') {
                            $kode_wo_anak = mysqli_real_escape_string($koneksi, $detail['kode_barang']);
                            $q_anak = mysqli_query($koneksi,"SELECT kode_barang, tipe, harga, total, jumlah
                                                             FROM tbworkorderdetail
                                                             WHERE kode_wo='$kode_wo_anak' AND tipe IN ('1','2')");
                            if($q_anak) {
                                while($anak = mysqli_fetch_array($q_anak)) {
                                    $detail_rows[] = $anak;
                                }
                            }
                        } else {
                            $detail_rows[] = $detail;
                        }
                    }

                    foreach($detail_rows as $detail) {
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

                                // Garansi - gratis (harga 0, potongan 100%)
                                $check_jasa_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
                                if(mysqli_num_rows($check_jasa_waktu) > 0) {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                                          VALUES
                                                          ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '{$detail['harga']}', '$waktu', '100', '0')");
                                } else {
                                    mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                          (no_service, nobaris, no_item, harga, potongan, total)
                                                          VALUES
                                                          ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '{$detail['harga']}', '100', '0')");
                                }
                            }
                        } else {
                            // Barang - Insert to tblservis_barang
                            $check_barang = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tblservis_barang
                                                                   WHERE no_service='$no_service' AND no_item='{$detail['kode_barang']}'");
                            $check_barang_result = mysqli_fetch_array($check_barang);

                            if($check_barang_result['count'] == 0) {
                                // Get next nobaris for barang
                                $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
                                $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
                                $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

                                // Garansi - gratis (potongan 100%)
                                mysqli_query($koneksi,"INSERT INTO tblservis_barang
                                                      (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                                      VALUES
                                                      ('$no_service', '$nobaris_brg', '{$detail['kode_barang']}', '{$detail['jumlah']}', '0', '{$detail['harga']}', '100', '0')");
                            }
                        }
                    }

                    // Update KM data
                    mysqli_query($koneksi,"UPDATE tblservice
                                           SET km_skr='$km_skr', km_berikut='$km_berikut'
                                           WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'");

                    echo"<script>
                        alert('Work Order GARANSI berhasil ditambahkan ke SPK!\\nSemua item di-set GRATIS (potongan 100%).');
                        window.location=('servis-garansi.php?snoserv=" . urlencode($no_service) . "&kd=" . urlencode($txtcaribrg) . "&kdjasa=" . urlencode($txtcarisrv) . "&kdwo=');
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
                    window.location=('servis-garansi.php?snoserv=" . urlencode($no_service) . "&kd=" . urlencode($txtcaribrg) . "&kdjasa=" . urlencode($txtcarisrv) . "&kdwo=');
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
                    if(!$('.alert-km-missing').length) {
                         var warningMsg = '<div class=\"alert alert-danger alert-dismissible alert-km-missing\" style=\"margin:0 0 6px;font-size:11px;padding:6px 10px;border-radius:4px;\">';
                         warningMsg += '<button type=\"button\" class=\"close\" data-dismiss=\"alert\" style=\"font-size:14px;line-height:1;\">&times;</button>';
                         warningMsg += '<i class=\"fa fa-warning\"></i> <strong>KM Harian belum diinput!</strong> ';
                         warningMsg += '<a href=\"input_kepala_mekanik_harian.php\" style=\"font-weight:bold;\">Input sekarang</a>';
                         warningMsg += '</div>';
                         var _cont = $('.ks-left')[0] || $('.page-content')[0] || document.body;
                         $(_cont).prepend(warningMsg);
                    }
                });
            </script>";
        }
    }
    // ========== END AUTO-FILL KEPALA MEKANIK HARIAN ==========

    // ========== HANDLER: SAVE SERVICE (NO PAYMENT - GARANSI) ==========
    if(isset($_POST['btnsave']) || (isset($_POST['btnsimpan']) && !empty($_POST['txtnosrv'] ?? ''))) {
        $no_service = $_POST['txtnosrv'] ?? $no_service;
        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

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
        
        // Helper redirect params
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

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
            WHERE no_service='$no_service' AND kd_cabang='$kd_cabang'";
            
        if(mysqli_query($koneksi, $update_query)) {
            echo "<script>
                alert('Data service garansi berhasil disimpan!');
                window.location='servis-garansi.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions';
            </script>";
        } else {
             echo "<script>alert('Error saving service: " . mysqli_error($koneksi) . "');</script>";
        }
    }

    // ========== HANDLER: PAYMENT/BAYAR (GARANSI) ==========
    if(isset($_POST['btnbayar'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        $km_skr = normalizePostedInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = normalizePostedInt($_POST['txtkm_next'] ?? 0);

        if(!empty($no_service)) {
            // Validasi Pre-Payment (Complaints, Findings, Offers)
            $err_msgs = array();
            $___ns = mysqli_real_escape_string($koneksi, $no_service);

            // 1. Validasi Keluhan - Status harus selesai (tidak boleh datang/diproses)
            $cek_keluhan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_keluhan_status WHERE no_service='".$___ns."' AND status_pengerjaan IN ('datang','diproses')");
            if($cek_keluhan && ($rk = mysqli_fetch_assoc($cek_keluhan))) { 
                if(intval($rk['c']) > 0) { 
                    $err_msgs[] = 'Masih ada keluhan dengan status dalam proses/belum selesai.'; 
                } 
            }

            // 2. Validasi Temuan - Status tidak boleh Ditemukan/Ditawarkan
            $cek_temuan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan WHERE no_service='".$___ns."' AND status_temuan IN ('ditemukan','ditawarkan')");
            if($cek_temuan && ($rt = mysqli_fetch_assoc($cek_temuan))) {
                if(intval($rt['c']) > 0) { 
                    $err_msgs[] = 'Masih ada TEMUAN yang belum diproses (status Ditemukan/Ditawarkan). Harap setujui atau tolak temuan tsb.'; 
                }
            }

            // 3. Validasi Penawaran Part/Jasa - Status tidak boleh Pending
            $cek_part_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_part WHERE no_service='".$___ns."' AND status='pending'");
            if($cek_part_pending && ($rp = mysqli_fetch_assoc($cek_part_pending))) {
                if(intval($rp['c']) > 0) { 
                    $err_msgs[] = 'Masih ada Penawaran PART yang statusnya Pending.'; 
                }
            }

            $cek_jasa_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_jasa WHERE no_service='".$___ns."' AND status='pending'");
            if($cek_jasa_pending && ($rj = mysqli_fetch_assoc($cek_jasa_pending))) {
                if(intval($rj['c']) > 0) { 
                    $err_msgs[] = 'Masih ada Penawaran JASA yang statusnya Pending.'; 
                }
            }

            if(!empty($err_msgs)) {
                $msg = implode("\\n- ", $err_msgs);
                $txtcaribrg = $_POST['txtcaribrg'] ?? '';
                $txtcarisrv = $_POST['txtcarisrv'] ?? '';
                $txtcariwo = $_POST['txtcariwo'] ?? '';
                echo "<script>window.alert('Tidak dapat memproses pembayaran karena:\\n- ".$msg."'); window.location='servis-garansi.php?snoserv=".addslashes($no_service)."&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions#actions';</script>";
                exit;
            }
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';

            // Get payment data
            $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'Tunai';
            $bayar_tunai    = (float)str_replace(['.', ','], '', $_POST['bayar_tunai']    ?? '0');
            $bayar_transfer = (float)str_replace(['.', ','], '', $_POST['bayar_transfer'] ?? '0');
            $bayar_qris     = (float)str_replace(['.', ','], '', $_POST['bayar_qris']     ?? '0');
            $ref_transfer   = mysqli_real_escape_string($koneksi, $_POST['ref_transfer']  ?? '');
            $txttotal_jasa = str_replace(['.', ','], '', $_POST['txttotal_jasa'] ?? '0');
            $txttotal_barang = str_replace(['.', ','], '', $_POST['txttotal_barang'] ?? '0');

            // Discount Inputs
            $diskon_member = floatval($_POST['txtdiskon_member'] ?? 0);
            $pot_tambahan_persen = floatval($_POST['txtpotfaktur_persen'] ?? 0);
            $total_diskon_persen = $diskon_member + $pot_tambahan_persen;
            $txtpotfaktur_nom_garansi = str_replace(['.', ','], '', $_POST['txtpotfaktur_nom'] ?? '0');

            // F2-C: diskon manual level-invoice butuh approval Supervisor/Manager
            if (function_exists('checkDiskonApproval') && !checkDiskonApproval($koneksi, $no_service, $pot_tambahan_persen, $txtpotfaktur_nom_garansi, $id_user, $kd_cabang)) {
                echo "<script>window.alert('Diskon manual di luar SOP butuh approval Supervisor/Manager sebelum pembayaran bisa diproses. Request approval sudah dikirim.'); window.location='servis-garansi.php?snoserv=" . urlencode($no_service) . "&tab=actions';</script>";
                exit;
            }

            // Calculate Totals
            $subtotal = $txttotal_jasa + $txttotal_barang;
            $diskon_nominal = $subtotal * ($total_diskon_persen / 100);

            // PPN
            $pajak_persen = floatval($_POST['txtpajak_persen'] ?? 0);
            $ppn_nominal = ($subtotal - $diskon_nominal) * ($pajak_persen / 100);

            $total_akhir = ($subtotal - $diskon_nominal) + $ppn_nominal;

            $jumlah_bayar = str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0');

            // F2-A: DP pending mengurangi sisa yang wajib dibayar sekarang (Q9)
            $dp_pending_total_pay = function_exists('getDpPendingTotal') ? getDpPendingTotal($koneksi, $no_service) : 0;
            $total_akhir_required = max(0, $total_akhir - $dp_pending_total_pay);
            $kembalian = $jumlah_bayar - $total_akhir_required;

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

             // Validate payment amount (allow 0 for full warranty)
            if($jumlah_bayar < $total_akhir_required && $total_akhir_required > 0) {
                 echo "<script>alert('Jumlah pembayaran kurang!'); window.history.back();</script>";
                 exit;
            }

            // Update service status to selesai
            $update_payment = "UPDATE tblservice SET
                status = '2',
                status_servis = 'selesai',
                total = '$subtotal',
                subtotal_jasa = '$txttotal_jasa',
                subtotal_item = '$txttotal_barang',
                subtotal = '$subtotal',
                diskon_persen = '$total_diskon_persen',
                diskon_nom = '$diskon_nominal',
                total_diskon = '$diskon_nominal',
                ppn_persen = '$pajak_persen',
                ppn_nom = '$ppn_nominal',
                total_pajak = '$ppn_nominal',
                total_grand = '$total_akhir',
                total_akhir = '$total_akhir',
                total_waktu = COALESCE((SELECT SUM(waktu) FROM tblservis_jasa WHERE no_service = '$no_service'), 0),
                km_skr = '$km_skr',
                km_berikut = '$km_berikut',
                metode_pembayaran = '$metode_pembayaran',
                bayar = '$jumlah_bayar',
                kembali = '$kembalian',
                updated_at = NOW(),
                kepala_mekanik1 = '$kepala_mekanik1',
                kepala_mekanik2 = '$kepala_mekanik2',
                persen_kepala_mekanik1 = '$persen_kepala1',
                persen_kepala_mekanik2 = '$persen_kepala2',
                admin1 = '$admin1',
                admin2 = '$admin2',
                persen_admin1 = '$persen_admin1',
                persen_admin2 = '$persen_admin2',
                mekanik1 = '$mekanik1',
                mekanik2 = '$mekanik2',
                mekanik3 = '$mekanik3',
                mekanik4 = '$mekanik4',
                persen_mekanik1 = '$persen_mekanik1',
                persen_mekanik2 = '$persen_mekanik2',
                persen_mekanik3 = '$persen_mekanik3',
                persen_mekanik4 = '$persen_mekanik4',
                bayar_tunai = '$bayar_tunai',
                bayar_transfer = '$bayar_transfer',
                bayar_qris = '$bayar_qris',
                ref_transfer = '$ref_transfer'"
                . (!empty($bukti_pembayaran_path) ? ", bukti_pembayaran = '$bukti_pembayaran_path'" : "") . "
                WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang'";

            if(mysqli_query($koneksi, $update_payment)) {
                snapshot_komisi_servis($koneksi, $no_service, $kd_cabang);
                // F2-A: tandai DP pending sebagai offset setelah pelunasan (Q9)
                if (function_exists('offsetDpPending')) {
                    offsetDpPending($koneksi, $no_service);
                }
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

                // Update stock for items used in warranty service — guard agar tidak double-insert
                // Barang bukan asal stok bengkel (PART-CUST milik customer, atau dari nota Penjualan yg sudah kepotong stok) — exclude dari kartu stok
                $chk_stok_garansi = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tbstok WHERE no_transaksi='$no_service' AND tipe='4'");
                $r_chk_garansi = mysqli_fetch_assoc($chk_stok_garansi);
                if ((int)$r_chk_garansi['cnt'] === 0) {
                    $sql_garansi = mysqli_query($koneksi, "SELECT * FROM tblservis_barang WHERE no_service='$no_service' AND asal_barang='SERVIS'");
                    while ($tampil_garansi = mysqli_fetch_array($sql_garansi)) {
                        $no_item_garansi = $tampil_garansi['no_item'];
                        $qty_garansi = (int)$tampil_garansi['quantity'];
                        mysqli_query($koneksi, "INSERT INTO tbstok
                            (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                            VALUES
                            ('4','$no_service','$no_item_garansi', CURDATE(),'0','$qty_garansi','Servis Garansi','$kd_cabang')");
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
                    window.location='servis-garansi.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo&tab=actions';
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
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>FIT MOTOR - Input Service Garansi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />
    <?php include "_template/_redesign_styles.php"; ?>
    <?php include "_template/_kasir_3col_layout.php"; ?>

    <style>
        .ks-tab-btn.active { border-bottom-color: #27ae60 !important; color: #27ae60 !important; }
        .ks-status-pill.garansi { background: rgba(39,174,96,.15); color: #27ae60; border-color: rgba(39,174,96,.3); }
        .ks-btn-bayar { background: linear-gradient(135deg,#27ae60,#2ecc71) !important; }
        .ks-garansi-input { background:#f0fff4; border:1px solid #c3e6cb; border-radius:8px; padding:10px; margin-bottom:6px; }
        .ks-garansi-input label { font-size:9px; text-transform:uppercase; letter-spacing:.05em; color:#27ae60; font-weight:700; display:block; margin-bottom:3px; }
        .ks-garansi-input input, .ks-garansi-input textarea { width:100%; border:1px solid #c3e6cb; border-radius:4px; padding:5px 8px; font-size:12px; box-sizing:border-box; }
        .ks-garansi-input textarea { resize:vertical; min-height:55px; }
    </style>
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>
</head>
<?php
// Variable aliases & defaults for ks-shell templates
$tipe_servis           = 'GARANSI';
$tahun_buat            = isset($vehicleRow) ? ($vehicleRow['tahun_buat'] ?? '') : '';
$keluhan               = $keluhan ?? '';
$no_pelanggan          = $kode_pelanggan ?? '';
$metode_pembayaran     = $metode_pembayaran ?? 'Tunai';
$auto_discount_percent = 0;
$discount_amount       = 0;
$total_barang = 0; $total_service = 0;
if (!empty($no_service)) {
    $__rb = mysqli_query($koneksi, "SELECT COALESCE(SUM(total),0) AS t FROM tblservis_barang WHERE no_service='".mysqli_real_escape_string($koneksi,$no_service)."'");
    if ($__rb && ($__r = mysqli_fetch_assoc($__rb))) $total_barang = (float)$__r['t'];
    $__rs = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) AS t FROM tblservis_jasa WHERE no_service='".mysqli_real_escape_string($koneksi,$no_service)."'");
    if ($__rs && ($__r = mysqli_fetch_assoc($__rs))) $total_service = (float)$__r['t'];
}
$tot = $total_barang + $total_service;
$net = $tot;
$bayar = 0; $kembalian = 0;
$center_active = 'workorder-details';

// F2-A: Penanda servis mesin besar / part inden + DP pending (Q9)
$boleh_dp = 0;
$dp_pending_list = [];
$dp_pending_total = 0;
if (!empty($no_service)) {
    $ns_dp = mysqli_real_escape_string($koneksi, $no_service);
    $r_bdp = mysqli_query($koneksi, "SELECT boleh_dp FROM tblservice WHERE no_service='$ns_dp'");
    if ($r_bdp && ($row_bdp = mysqli_fetch_assoc($r_bdp))) { $boleh_dp = (int)$row_bdp['boleh_dp']; }
    $r_dp = mysqli_query($koneksi, "SELECT no_dp, jumlah_dp FROM tb_dp_servis WHERE no_service='$ns_dp' AND status='pending' ORDER BY id DESC");
    if ($r_dp) {
        while ($row_dp = mysqli_fetch_assoc($r_dp)) {
            $dp_pending_list[] = $row_dp;
            $dp_pending_total += (float)$row_dp['jumlah_dp'];
        }
    }
}
?>
<body>
<div class="ks-shell">

    <!-- TOP BAR -->
    <div class="ks-topbar">
        <a href="index.php?pg=menu_servis01" class="ks-topbar-brand">
            <i class="fa fa-motorcycle"></i> FIT MOTOR
        </a>
        <div class="ks-topbar-info">
            <div class="ks-topbar-item">
                <span class="lbl">No. Service</span>
                <span class="val"><?= htmlspecialchars($no_service ?: 'BARU') ?></span>
            </div>
            <div class="ks-topbar-divider"></div>
            <span class="ks-status-pill garansi">GARANSI</span>
            <?php if(!empty($namapelanggan)): ?>
            <div class="ks-topbar-divider"></div>
            <div class="ks-topbar-item">
                <span class="lbl">Pelanggan</span>
                <span class="val"><?= htmlspecialchars($namapelanggan) ?></span>
            </div>
            <?php endif; ?>
            <?php if(!empty($no_polisi)): ?>
            <div class="ks-topbar-divider"></div>
            <div class="ks-topbar-item">
                <span class="lbl">No. Polisi</span>
                <span class="val"><?= htmlspecialchars($no_polisi) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <div class="ks-topbar-right">
            <div class="ks-total-live-wrap">
                <span class="lbl">Total</span>
                <span class="ks-total-live" id="ks-live-total">Rp <?= number_format($net,0,',','.') ?></span>
            </div>
            <a href="index.php?pg=menu_servis01" class="ks-topbar-btn">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
            <div class="ks-user-badge">
                <img src="<?= htmlspecialchars($foto_user) ?>" class="ks-user-photo" onerror="this.onerror=null;this.src='assets/images/avatars/avatar.png'">
                <span class="ks-user-name"><?= htmlspecialchars($_nama) ?></span>
            </div>
        </div>
    </div>

    <form method="POST" action="" enctype="multipart/form-data" id="formService">
        <input type="hidden" name="txtnosrv" value="<?= htmlspecialchars($no_service) ?>">
        <input type="hidden" name="kode_pelanggan" value="<?= htmlspecialchars($kode_pelanggan) ?>">
        <input type="hidden" name="no_polisi" value="<?= htmlspecialchars($no_polisi) ?>">
        <div class="ks-body">

            <!-- LEFT PANEL -->
            <div class="ks-left">
                <!-- Garansi-specific: Tanggal, Jam, Keluhan -->
                <div class="ks-garansi-input">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:6px;">
                        <div>
                            <label><i class="fa fa-calendar"></i> Tanggal</label>
                            <input type="text" class="date-picker" id="id-date-picker-1" name="id-date-picker-1"
                                   value="<?= htmlspecialchars($tanggal ?: date('d/m/Y')) ?>" placeholder="dd/mm/yyyy">
                        </div>
                        <div>
                            <label><i class="fa fa-clock-o"></i> Jam</label>
                            <input type="time" id="jam_service" name="jam_service"
                                   value="<?= htmlspecialchars($jam ?: date('H:i')) ?>">
                        </div>
                    </div>
                    <label><i class="fa fa-comment"></i> Keluhan Garansi</label>
                    <textarea name="keluhan" placeholder="Deskripsi keluhan untuk service garansi..."><?= htmlspecialchars($keluhan) ?></textarea>
                </div>
                <?php if(!empty($ref_service) && !empty($ref_service_data)): ?>
                <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:6px;padding:8px;font-size:11px;margin-bottom:4px;">
                    <div style="font-weight:700;color:#f57c00;font-size:9px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">
                        <i class="fa fa-link"></i> Ref. Service Asli
                    </div>
                    <div style="font-weight:700;color:#333;"><?= htmlspecialchars($ref_service) ?></div>
                    <?php if(!empty($ref_service_data['tanggal'])): ?>
                    <div style="color:#777;">Tgl: <?= date('d/m/Y', strtotime($ref_service_data['tanggal'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php include "_template/panel-kiri-kasir.php"; ?>
            </div>

            <!-- CENTER: TABS -->
            <div class="ks-center">
                <div class="ks-tab-bar">
                    <?php
                    $__wc = 0;
                    if(!empty($no_service)) {
                        $__wq = mysqli_query($koneksi,"SELECT COUNT(*) AS c FROM tblservis_wo WHERE no_service='".mysqli_real_escape_string($koneksi,$no_service)."'");
                        if($__wq) $__wc = (int)mysqli_fetch_assoc($__wq)['c'];
                    }
                    ?>
                    <a class="ks-tab-btn active" data-target="workorder-details" href="#">
                        <i class="fa fa-tasks"></i> Work Order
                        <?php if($__wc > 0): ?><span class="ks-badge"><?= $__wc ?></span><?php endif; ?>
                    </a>
                    <a class="ks-tab-btn" data-target="temuan-penawaran" href="#">
                        <i class="fa fa-search"></i> Temuan & Penawaran
                    </a>
                    <a class="ks-tab-btn" data-target="service-items" href="#">
                        <i class="fa fa-boxes"></i> Suku Cadang
                    </a>
                    <a class="ks-tab-btn" data-target="service-jasa" href="#">
                        <i class="fa fa-tools"></i> Jasa Service
                    </a>
                    <?php if (!empty($ref_service)): ?>
                    <a class="ks-tab-btn" data-target="riwayat-servis-asal" href="#">
                        <i class="fa fa-history"></i> Riwayat Servis Asal
                    </a>
                    <?php endif; ?>
                </div>
                <div class="ks-tab-contents">
                    <div id="workorder-details" class="ks-tab-pane active">
                        <?php include "_template/tab-workorder-redesign.php"; ?>
                    </div>
                    <div id="temuan-penawaran" class="ks-tab-pane">
                        <?php include "_template/tab-temuan-penawaran-redesign.php"; ?>
                    </div>
                    <div id="service-items" class="ks-tab-pane">
                        <?php include "_template/tab-item-barang-redesign.php"; ?>
                    </div>
                    <div id="service-jasa" class="ks-tab-pane">
                        <?php include "_template/tab-item-jasa-redesign.php"; ?>
                    </div>
                    <?php if (!empty($ref_service)): ?>
                    <div id="riwayat-servis-asal" class="ks-tab-pane">
                        <?php include "_template/tab-riwayat-servis-asal-redesign.php"; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT PANEL -->
            <div class="ks-right">
                <?php include "_template/panel-kanan-kasir.php"; ?>
            </div>

        </div>
    </form>
</div>

<!-- Modals -->
<?php
$template_dir = __DIR__ . DIRECTORY_SEPARATOR . '_template' . DIRECTORY_SEPARATOR;
if(file_exists($template_dir . "modal-search-temuan.php")) include $template_dir . "modal-search-temuan.php";
if(file_exists($template_dir . "modal-search-keluhan.php")) include $template_dir . "modal-search-keluhan.php";
if(file_exists($template_dir . "modal-fastmoves-v2.php")) include $template_dir . "modal-fastmoves-v2.php";
if(file_exists($template_dir . "modal-fastmoves-part.php")) include $template_dir . "modal-fastmoves-part.php";
if(file_exists($template_dir . "modal-tambah-keluhan-baru.php")) include $template_dir . "modal-tambah-keluhan-baru.php";
if(file_exists($template_dir . "modal-input-barang-custom.php")) include $template_dir . "modal-input-barang-custom.php";
if(file_exists($template_dir . "_modal_riwayat_kendaraan.php")) include $template_dir . "_modal_riwayat_kendaraan.php";
if(file_exists($template_dir . "_modal_update_status_keluhan.php")) include $template_dir . "_modal_update_status_keluhan.php";
if(file_exists($template_dir . "_modal_cancel_service.php")) include $template_dir . "_modal_cancel_service.php";

// Modal Statistik Pelanggan
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
        var url = new URL(window.location);
        url.searchParams.set('tab', target);
        window.history.pushState({}, '', url);
    });

    // Handle URL tab param on load
    var validTabs = ['workorder-details','temuan-penawaran','service-items','service-jasa','riwayat-servis-asal'];
    var activeTab = new URLSearchParams(window.location.search).get('tab');
    if (activeTab && validTabs.indexOf(activeTab) !== -1) {
        $('.ks-tab-btn[data-target="' + activeTab + '"]').trigger('click');
    }

    if ($.fn.datepicker) {
        $('.date-picker').datepicker({ dateFormat: 'dd/mm/yy', changeMonth: true, changeYear: true });
    }
});
</script>
</body>
</html>
