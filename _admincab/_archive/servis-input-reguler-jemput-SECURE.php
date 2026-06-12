<?php
/**
 * SECURE VERSION - Input Service Jemput dengan Prepared Statements
 *
 * PERUBAHAN KEAMANAN:
 * 1. Semua query menggunakan prepared statements
 * 2. Input validation untuk semua parameter
 * 3. CSRF token protection
 * 4. Session security
 * 5. Type checking untuk semua variable
 *
 * @version 2.0-SECURE
 * @date 2025-12-12
 */

session_start();

// ========== SECURITY INCLUDES ==========
require_once "lib_database_secure.php";

// ========== SESSION SECURITY CHECK ==========
SessionSecurity::requireLogin();

// Get sanitized session variables
$id_user = SessionSecurity::getUserId();
$kd_cabang = SessionSecurity::getCabang();

if (!$id_user || !$kd_cabang) {
    header("location:../index.php");
    exit;
}

// ========== DATABASE CONNECTION ==========
include "../config/koneksi.php";

// Initialize secure database helper
$db = new DatabaseSecure($koneksi);

// ========== RBAC CHECK ==========
include_once "../lib/rbac.php";
rbac_require_any(array('input_servis_read','jadwal_jemput_read','servis_jemput_read','servis_menu_read','service_create','service_update'));

// ========== INCLUDES ==========
include "_include_statistik_pelanggan.php";
include "_include_kategori_member.php";
include "_handler_temuan_penawaran.php";
include "_handler_barang_custom.php";
include "_handler_status_keluhan_wo.php";

// ========== CSRF PROTECTION ==========
$csrf_token = CSRFProtection::generateToken();

// ========== GET USER DATA (SECURE) ==========
$query = "SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id=?";
$user_data = $db->fetchRow($query, 'i', [$id_user]);

if (!$user_data) {
    die("Error: User tidak ditemukan");
}

$_nama = $user_data['nama_user'];
$pwd = $user_data['password'];
$lvl_akses = $user_data['user_akses'];
$foto_user = $user_data['foto_user'] ?: "file_upload/avatar.png";

// Set username session if not exists
if(!isset($_SESSION['username'])) {
    $_SESSION['username'] = $_nama;
}

// ========== GET CABANG DATA (SECURE) ==========
$query = "SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang=?";
$cabang_data = $db->fetchRow($query, 's', [$kd_cabang]);

$nama_cabang = $cabang_data['nama_cabang'] ?? '';
$tipe_cabang = $cabang_data['tipe_cabang'] ?? '';

// ========== SANITIZE GET PARAMETERS ==========
$no_service = InputValidator::sanitizeString($_GET['snoserv'] ?? '');
$txtcaribrg = InputValidator::sanitizeString($_GET['kd'] ?? '');
$txtcarisrv = InputValidator::sanitizeString($_GET['kdjasa'] ?? '');
$txtcariwo = InputValidator::sanitizeString($_GET['kdwo'] ?? '');

// Validate service number format if provided
if (!empty($no_service) && !InputValidator::isValidServiceNo($no_service)) {
    die("Error: Format nomor service tidak valid");
}

// Initialize display variables
$txtnamawo = '';

// Get work order data if searching (SECURE)
if(!empty($txtcariwo)) {
    $query = "SELECT nama_wo FROM tbworkorderheader WHERE kode_wo=?";
    $wo_data = $db->fetchRow($query, 's', [$txtcariwo]);
    $txtnamawo = $wo_data['nama_wo'] ?? '';
}

// ========== TAB HELPER FUNCTIONS (Same as before) ==========
function getCurrentTab() {
    global $txtcaribrg, $txtcarisrv, $txtcariwo;

    $posted_tab = $_POST['tab'] ?? null;
    if ($posted_tab) return $posted_tab;

    $url_tab = $_GET['tab'] ?? null;
    if ($url_tab) return $url_tab;

    if (!empty($txtcaribrg)) return 'items';
    if (!empty($txtcarisrv)) return 'jasa';
    if (!empty($txtcariwo)) return 'workorder';

    return 'details';
}

function buildUrl($base, $params = []) {
    if (!isset($params['tab'])) {
        $params['tab'] = getCurrentTab();
    }
    $query = http_build_query($params);
    return $base . ($query ? '?' . $query : '');
}

$current_tab = getCurrentTab();
$active_tab = match($current_tab) {
    'items' => 'service-items',
    'jasa' => 'service-jasa',
    'workorder' => 'workorder-details',
    'pickup' => 'pickup-details',
    'actions' => 'service-actions',
    default => 'service-details'
};

// ========== POST HANDLERS (SECURE VERSION) ==========

// CSRF VALIDATION for all POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validatePost()) {
        die("Error: CSRF token validation failed. Silakan refresh halaman dan coba lagi.");
    }
}

// ========== HANDLER: UPDATE MEKANIK (SECURE) ==========
if(isset($_POST['btnupdatemekanik'])) {
    $no_service = InputValidator::sanitizeString($_POST['txtnosrv'] ?? '');

    if(empty($no_service) || !InputValidator::isValidServiceNo($no_service)) {
        echo"<script>alert('Error: No service tidak valid!'); window.history.back();</script>";
        exit;
    }

    // Sanitize all inputs
    $txtcaribrg = InputValidator::sanitizeString($_POST['txtcaribrg'] ?? '');
    $txtcarisrv = InputValidator::sanitizeString($_POST['txtcarisrv'] ?? '');
    $txtcariwo = InputValidator::sanitizeString($_POST['txtcariwo'] ?? '');

    $kepala_mekanik1 = InputValidator::sanitizeString($_POST['cbokepala_mekanik1'] ?? '');
    $persen_kepala1 = InputValidator::sanitizeInt($_POST['txtpersen_kepala1'] ?? 0);
    $kepala_mekanik2 = InputValidator::sanitizeString($_POST['cbokepala_mekanik2'] ?? '');
    $persen_kepala2 = InputValidator::sanitizeInt($_POST['txtpersen_kepala2'] ?? 0);
    $admin1 = InputValidator::sanitizeString($_POST['cboadmin1'] ?? '');
    $persen_admin1 = InputValidator::sanitizeInt($_POST['txtpersen_admin1'] ?? 0);
    $admin2 = InputValidator::sanitizeString($_POST['cboadmin2'] ?? '');
    $persen_admin2 = InputValidator::sanitizeInt($_POST['txtpersen_admin2'] ?? 0);
    $mekanik1 = InputValidator::sanitizeString($_POST['cbomekanik1'] ?? '');
    $persen_mekanik1 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik1'] ?? 0);
    $mekanik2 = InputValidator::sanitizeString($_POST['cbomekanik2'] ?? '');
    $persen_mekanik2 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik2'] ?? 0);
    $mekanik3 = InputValidator::sanitizeString($_POST['cbomekanik3'] ?? '');
    $persen_mekanik3 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik3'] ?? 0);
    $mekanik4 = InputValidator::sanitizeString($_POST['cbomekanik4'] ?? '');
    $persen_mekanik4 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik4'] ?? 0);

    // Validate percentage sum
    $total_persen = $persen_kepala1 + $persen_kepala2 + $persen_admin1 + $persen_admin2 +
                   $persen_mekanik1 + $persen_mekanik2 + $persen_mekanik3 + $persen_mekanik4;

    if($total_persen > 100) {
        echo "<script>alert('Error: Total persentase tidak boleh lebih dari 100%! Total saat ini: $total_persen%'); window.history.back();</script>";
        exit;
    }

    // UPDATE MECHANIC DATA (SECURE - PREPARED STATEMENT)
    $query = "UPDATE tblservice SET
        kepala_mekanik1=?,
        kepala_mekanik2=?,
        persen_kepala_mekanik1=?,
        persen_kepala_mekanik2=?,
        mekanik1=?,
        mekanik2=?,
        mekanik3=?,
        mekanik4=?,
        persen_mekanik1=?,
        persen_mekanik2=?,
        persen_mekanik3=?,
        persen_mekanik4=?,
        updated_at=NOW()
        WHERE no_service=?";

    $result = $db->execute($query, 'ssiissssiiiiis', [
        $kepala_mekanik1, $kepala_mekanik2,
        $persen_kepala1, $persen_kepala2,
        $mekanik1, $mekanik2, $mekanik3, $mekanik4,
        $persen_mekanik1, $persen_mekanik2, $persen_mekanik3, $persen_mekanik4,
        $no_service
    ]);

    if($result) {
        $redirect_url = buildUrl('servis-input-reguler-jemput.php', [
            'snoserv' => $no_service,
            'kd' => $txtcaribrg,
            'kdjasa' => $txtcarisrv,
            'kdwo' => $txtcariwo
        ]);
        echo "<script>
            alert('Data mekanik berhasil diupdate!');
            window.location = '" . htmlspecialchars($redirect_url, ENT_QUOTES) . "';
        </script>";
        exit;
    } else {
        echo "<script>alert('Error update data mekanik!');</script>";
    }
}

// ========== HANDLER: TAMBAH KELUHAN (SECURE) ==========
if(isset($_POST['btnaddkeluhan'])) {
    $no_service = InputValidator::sanitizeString($_POST['txtnosrv'] ?? '');
    $txtkeluhan = InputValidator::sanitizeString($_POST['txtkeluhan'] ?? '', 255);

    // Validate inputs
    if(empty($no_service) || !InputValidator::isValidServiceNo($no_service)) {
        echo"<script>alert('Error: No service tidak valid!'); window.history.back();</script>";
        exit;
    }

    if(empty($txtkeluhan)) {
        echo"<script>alert('Error: Keluhan tidak boleh kosong!'); window.history.back();</script>";
        exit;
    }

    // INSERT KELUHAN (SECURE - PREPARED STATEMENT)
    $query = "INSERT INTO tbservis_keluhan_status (no_service, keluhan, status_pengerjaan) VALUES (?, ?, 'datang')";
    $result = $db->execute($query, 'ss', [$no_service, $txtkeluhan]);

    if($result) {
        echo "<script>
            alert('Keluhan berhasil ditambahkan ke SPK!');
            window.location='servis-input-reguler-jemput.php?snoserv=" . urlencode($no_service) . "&tab=workorder';
        </script>";
        exit;
    } else {
        echo"<script>alert('Error: Gagal menambahkan keluhan!'); window.history.back();</script>";
        exit;
    }
}

// ========== HANDLER: UPDATE STATUS KELUHAN (SECURE) ==========
if(isset($_POST['btnupdatestatuskeluhan'])) {
    $keluhan_id = InputValidator::sanitizeInt($_POST['keluhan_id'] ?? 0);
    $status_keluhan = InputValidator::sanitizeString($_POST['status_keluhan'] ?? '');
    $keterangan = InputValidator::sanitizeString($_POST['keterangan_keluhan'] ?? '', 255);

    // Validate allowed status values
    $allowed_status = ['datang', 'diproses', 'selesai', 'tidak_selesai'];
    $status_keluhan = InputValidator::sanitizeEnum($status_keluhan, $allowed_status, 'datang');

    if($keluhan_id <= 0) {
        echo "<script>alert('Error: ID keluhan tidak valid!'); window.history.back();</script>";
        exit;
    }

    // UPDATE STATUS (SECURE - PREPARED STATEMENT)
    $query = "UPDATE tbservis_keluhan_status SET status_pengerjaan=?, keterangan_tidak_selesai=? WHERE id=?";
    $result = $db->execute($query, 'ssi', [$status_keluhan, $keterangan, $keluhan_id]);

    if($result) {
        echo "<script>
            alert('Status keluhan berhasil diupdate!');
            window.location='servis-input-reguler-jemput.php?snoserv=" . urlencode($no_service) . "&tab=workorder';
        </script>";
        exit;
    } else {
        echo "<script>alert('Error update status keluhan!');</script>";
    }
}

// ========== HANDLER: TAMBAH ITEM BARANG (SECURE) ==========
if(isset($_POST['btnadd'])) {
    // Sanitize inputs
    $no_service = InputValidator::sanitizeString($_POST['txtnosrv'] ?? '');
    $txtkdbarang = InputValidator::sanitizeString($_POST['txtcaribrg'] ?? '');
    $txtqty = InputValidator::sanitizeInt($_POST['txtqty'] ?? 0);
    $txtpot = InputValidator::sanitizeFloat($_POST['txtpot'] ?? 0);

    // Validate inputs
    if(empty($no_service) || !InputValidator::isValidServiceNo($no_service)) {
        echo "<script>alert('Error: No service tidak valid!'); window.history.back();</script>";
        exit;
    }

    if(empty($txtkdbarang) || $txtqty <= 0) {
        echo "<script>alert('Kode barang dan qty harus diisi!'); window.history.back();</script>";
        exit;
    }

    // CHECK STOCK (SECURE - PREPARED STATEMENT)
    $query = "SELECT saldo FROM view_stok_master WHERE kd_cabang=? AND no_item=?";
    $stok_data = $db->fetchRow($query, 'ss', [$kd_cabang, $txtkdbarang]);

    if(!$stok_data) {
        echo "<script>alert('Item tidak ditemukan di stok cabang ini!'); window.history.back();</script>";
        exit;
    }

    $stok_akhir = InputValidator::sanitizeInt($stok_data['saldo']);

    // Validate stock availability
    if($stok_akhir <= 0) {
        echo "<script>alert('Stok barang kosong!'); window.history.back();</script>";
        exit;
    }

    if($txtqty > $stok_akhir) {
        echo "<script>alert('Stok barang tidak mencukupi! Stok tersedia: $stok_akhir'); window.history.back();</script>";
        exit;
    }

    // Check if item already exists (SECURE)
    $query = "SELECT COUNT(*) as count FROM tblservis_barang WHERE no_service=? AND no_item=?";
    $check_data = $db->fetchRow($query, 'ss', [$no_service, $txtkdbarang]);

    if($check_data && $check_data['count'] > 0) {
        echo "<script>alert('Item barang sudah ada!'); window.history.back();</script>";
        exit;
    }

    // Get item price with tiered pricing (SECURE)
    $query = "SELECT hargajual, hargajual2, hargajual3, hjqtys1, hjqtys2 FROM tblitem WHERE noitem=?";
    $harga_data = $db->fetchRow($query, 's', [$txtkdbarang]);

    if(!$harga_data) {
        echo "<script>alert('Item tidak ditemukan di master!'); window.history.back();</script>";
        exit;
    }

    $harga_ke1 = InputValidator::sanitizeFloat($harga_data['hargajual']);
    $harga_ke2 = InputValidator::sanitizeFloat($harga_data['hargajual2'] ?? $harga_ke1);
    $harga_ke3 = InputValidator::sanitizeFloat($harga_data['hargajual3'] ?? $harga_ke1);
    $qty_ke1 = InputValidator::sanitizeInt($harga_data['hjqtys1'] ?? 1);
    $qty_ke2 = InputValidator::sanitizeInt($harga_data['hjqtys2'] ?? 999999);

    // Determine price based on quantity
    if($txtqty <= $qty_ke1) {
        $harga_jual = $harga_ke1;
    } elseif($txtqty <= $qty_ke2) {
        $harga_jual = $harga_ke2;
    } else {
        $harga_jual = $harga_ke3;
    }

    // Calculate total
    $subtotal = ($harga_jual * $txtqty) - (($harga_jual * $txtqty) * ($txtpot / 100));

    // INSERT ITEM BARANG (SECURE - PREPARED STATEMENT)
    $query = "INSERT INTO tblservis_barang (no_service, no_item, harga_jual, quantity, potongan, total) VALUES (?, ?, ?, ?, ?, ?)";
    $result = $db->execute($query, 'ssdidi', [$no_service, $txtkdbarang, $harga_jual, $txtqty, $txtpot, $subtotal]);

    if($result) {
        echo "<script>window.location='servis-input-reguler-jemput.php?snoserv=" . urlencode($no_service) . "&tab=items';</script>";
        exit;
    } else {
        echo "<script>alert('Error: Gagal menambahkan item!'); window.history.back();</script>";
        exit;
    }
}

// ========== HANDLER: TAMBAH ITEM JASA (SECURE) ==========
if(isset($_POST['btnaddsrv'])) {
    $no_service = InputValidator::sanitizeString($_POST['txtnosrv'] ?? '');
    $txtcarisrv = InputValidator::sanitizeString($_POST['txtcarisrv'] ?? '');
    $txtpotsrv = InputValidator::sanitizeFloat($_POST['txtpotsrv'] ?? 0);

    if(empty($txtcarisrv)) {
        echo "<script>alert('Kode jasa harus diisi!'); window.history.back();</script>";
        exit;
    }

    // Check if jasa already exists (SECURE)
    $query = "SELECT COUNT(*) as count FROM tblservis_jasa WHERE no_service=? AND no_item=?";
    $check_data = $db->fetchRow($query, 'ss', [$no_service, $txtcarisrv]);

    if($check_data && $check_data['count'] > 0) {
        echo "<script>alert('Item jasa sudah ada!'); window.history.back();</script>";
        exit;
    }

    // Get work order details (SECURE)
    $query = "SELECT harga, waktu FROM tbworkorderheader WHERE kode_wo=?";
    $wo_data = $db->fetchRow($query, 's', [$txtcarisrv]);

    if(!$wo_data) {
        echo "<script>alert('Jasa tidak ditemukan!'); window.history.back();</script>";
        exit;
    }

    $harga = InputValidator::sanitizeFloat($wo_data['harga']);
    $waktu = InputValidator::sanitizeInt($wo_data['waktu'] ?? 0);

    // Calculate total
    $subtotal = $harga - ($harga * ($txtpotsrv / 100));

    // Get next nobaris (SECURE)
    $query = "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service=?";
    $nobaris_data = $db->fetchRow($query, 's', [$no_service]);
    $next_nobaris = InputValidator::sanitizeInt($nobaris_data['next_nobaris'] ?? 1);

    // INSERT JASA (SECURE - PREPARED STATEMENT)
    $query = "INSERT INTO tblservis_jasa (no_service, nobaris, no_item, harga, waktu, potongan, total) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $result = $db->execute($query, 'sisdidd', [$no_service, $next_nobaris, $txtcarisrv, $harga, $waktu, $txtpotsrv, $subtotal]);

    if($result) {
        echo "<script>window.location='servis-input-reguler-jemput.php?snoserv=" . urlencode($no_service) . "&tab=jasa';</script>";
        exit;
    } else {
        echo "<script>alert('Error: Gagal menambahkan jasa!'); window.history.back();</script>";
        exit;
    }
}

// ========== HANDLER: PROSES PEMBAYARAN (SECURE) ==========
if(isset($_POST['btnbayar'])) {
    // START TRANSACTION for payment processing
    $db->beginTransaction();

    try {
        $no_service = InputValidator::sanitizeString($_POST['txtnosrv'] ?? '');

        if(empty($no_service) || !InputValidator::isValidServiceNo($no_service)) {
            throw new Exception("No service tidak valid");
        }

        // Sanitize payment inputs
        $km_skr = InputValidator::sanitizeInt($_POST['txtkm_skr'] ?? 0);
        $km_berikut = InputValidator::sanitizeInt($_POST['txtkm_next'] ?? 0);
        $diskon_member = InputValidator::sanitizeFloat($_POST['txtdiskon_member'] ?? 0);
        $txtpotfaktur_persen = InputValidator::sanitizeFloat($_POST['txtpotfaktur_persen'] ?? 0);
        $total_diskon_persen = $diskon_member + $txtpotfaktur_persen;
        $txtpajak_persen = InputValidator::sanitizeFloat($_POST['txtpajak_persen'] ?? 0);
        $metode_pembayaran = InputValidator::sanitizeString($_POST['metode_pembayaran'] ?? 'Tunai');

        // Remove formatting from currency inputs
        $txtbayar = InputValidator::sanitizeFloat(str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0'));

        // Handle file upload for bukti pembayaran
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
        $kepala_mekanik1 = InputValidator::sanitizeString($_POST['cbokepala_mekanik1'] ?? '');
        $persen_kepala1 = InputValidator::sanitizeInt($_POST['txtpersen_kepala1'] ?? 0);
        $kepala_mekanik2 = InputValidator::sanitizeString($_POST['cbokepala_mekanik2'] ?? '');
        $persen_kepala2 = InputValidator::sanitizeInt($_POST['txtpersen_kepala2'] ?? 0);
        $mekanik1 = InputValidator::sanitizeString($_POST['cbomekanik1'] ?? '');
        $persen_mekanik1 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik1'] ?? 0);
        $mekanik2 = InputValidator::sanitizeString($_POST['cbomekanik2'] ?? '');
        $persen_mekanik2 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik2'] ?? 0);
        $mekanik3 = InputValidator::sanitizeString($_POST['cbomekanik3'] ?? '');
        $persen_mekanik3 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik3'] ?? 0);
        $mekanik4 = InputValidator::sanitizeString($_POST['cbomekanik4'] ?? '');
        $persen_mekanik4 = InputValidator::sanitizeInt($_POST['txtpersen_mekanik4'] ?? 0);

        // Calculate totals from database (SECURE)
        $query = "SELECT COALESCE(SUM(total), 0) as tot, COALESCE(SUM(waktu), 0) as tot_waktu FROM tblservis_jasa WHERE no_service=?";
        $jasa_data = $db->fetchRow($query, 's', [$no_service]);
        $total_service_pay = InputValidator::sanitizeFloat($jasa_data['tot']);
        $total_waktu_pay = InputValidator::sanitizeInt($jasa_data['tot_waktu']);

        $query = "SELECT COALESCE(SUM(total), 0) as tot FROM tblservis_barang WHERE no_service=?";
        $barang_data = $db->fetchRow($query, 's', [$no_service]);
        $total_barang_pay = InputValidator::sanitizeFloat($barang_data['tot']);

        $tot_pay = $total_service_pay + $total_barang_pay;
        $diskon_nominal = $tot_pay * ($total_diskon_persen / 100);
        $ppn = $tot_pay * ($txtpajak_persen / 100);
        $net_pay = $tot_pay - $diskon_nominal + $ppn;
        $kembalian_pay = $txtbayar - $net_pay;

        // Validate payment amount
        if($txtbayar < $net_pay) {
            throw new Exception('Jumlah pembayaran tidak mencukupi! Total: Rp ' . number_format($net_pay, 0, ',', '.') . ', Bayar: Rp ' . number_format($txtbayar, 0, ',', '.'));
        }

        if($net_pay <= 0) {
            throw new Exception('Total service harus lebih dari 0!');
        }

        // UPDATE SERVICE (SECURE - PREPARED STATEMENT)
        $query = "UPDATE tblservice SET
            status='2',
            total=?,
            diskon_persen=?,
            diskon_nom=?,
            ppn_persen=?,
            ppn_nom=?,
            total_grand=?,
            total_waktu=?,
            km_skr=?,
            km_berikut=?,
            status_servis='bayar',
            kepala_mekanik1=?,
            kepala_mekanik2=?,
            persen_kepala_mekanik1=?,
            persen_kepala_mekanik2=?,
            mekanik1=?,
            mekanik2=?,
            mekanik3=?,
            mekanik4=?,
            persen_mekanik1=?,
            persen_mekanik2=?,
            persen_mekanik3=?,
            persen_mekanik4=?,
            metode_pembayaran=?,
            bayar=?,
            kembali=?" .
            (!empty($bukti_pembayaran_path) ? ", bukti_pembayaran=?" : "") . "
            WHERE no_service=?";

        $params = [
            $tot_pay, $total_diskon_persen, $diskon_nominal,
            $txtpajak_persen, $ppn, $net_pay, $total_waktu_pay,
            $km_skr, $km_berikut,
            $kepala_mekanik1, $kepala_mekanik2,
            $persen_kepala1, $persen_kepala2,
            $mekanik1, $mekanik2, $mekanik3, $mekanik4,
            $persen_mekanik1, $persen_mekanik2, $persen_mekanik3, $persen_mekanik4,
            $metode_pembayaran, $txtbayar, $kembalian_pay
        ];

        $types = 'ddidddiissiissssiiiis ddd';

        if(!empty($bukti_pembayaran_path)) {
            $params[] = $bukti_pembayaran_path;
            $types .= 's';
        }

        $params[] = $no_service;
        $types .= 's';

        $result = $db->execute($query, $types, $params);

        if(!$result) {
            throw new Exception('Gagal update service');
        }

        // Update stock for items (SECURE)
        $query = "SELECT no_item, quantity FROM tblservis_barang WHERE no_service=?";
        $items = $db->fetchAll($query, 's', [$no_service]);

        // Get tanggal from service
        $query = "SELECT tanggal FROM tblservice WHERE no_service=?";
        $service_data = $db->fetchRow($query, 's', [$no_service]);
        $tanggal_srv = $service_data['tanggal'];

        foreach($items as $item) {
            $no_item = $item['no_item'];
            $qty = InputValidator::sanitizeInt($item['quantity']);

            $query = "INSERT INTO tbstok (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                      VALUES ('4', ?, ?, ?, 0, ?, 'Penjualan Service Jemput', ?)";
            $db->execute($query, 'sssiss', [$no_service, $no_item, $tanggal_srv, $qty, $kd_cabang]);
        }

        // Update statistik pelanggan
        $query = "SELECT no_pelanggan FROM tblservice WHERE no_service=?";
        $customer_data = $db->fetchRow($query, 's', [$no_service]);

        if($customer_data && !empty($customer_data['no_pelanggan'])) {
            $no_pelanggan = $customer_data['no_pelanggan'];
            if(function_exists('updateStatistikPelangganAfterPayment')) {
                updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan, $no_service);
            }
        }

        // COMMIT TRANSACTION
        $db->commit();

        // WhatsApp automation (optional - outside transaction)
        try {
            if(file_exists('config_whatsapp.php')) require_once 'config_whatsapp.php';
            if(file_exists('class_whatsapp_automation.php')) require_once 'class_whatsapp_automation.php';

            if(defined('WA_API_ENABLED') && WA_API_ENABLED &&
               defined('WA_AUTO_SEND_AFTER_PAYMENT') && WA_AUTO_SEND_AFTER_PAYMENT) {
                if(class_exists('WhatsAppAutomation')) {
                    if(defined('WA_SEND_DELAY')) sleep(WA_SEND_DELAY);
                    $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
                    $wa->sendTerimaKasih($no_service);
                }
            }
        } catch(Exception $e) {
            error_log("WhatsApp error: " . $e->getMessage());
        }

        // Success redirect
        echo "<script>
            if(confirm('Pembayaran Service Jemput Berhasil!\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "\\n\\nService jemput telah selesai dan siap diantar kembali.\\n\\nKlik OK untuk print invoice\\nKlik Cancel untuk kembali ke daftar servis')) {
                window.location='servis-print.php?snoserv=" . urlencode($no_service) . "';
            } else {
                window.location='servis-reguler.php';
            }
        </script>";
        exit;

    } catch(Exception $e) {
        // ROLLBACK on error
        $db->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit;
    }
}

// ========== CONTINUE WITH REST OF THE CODE... ==========
// (The HTML/UI part would be similar, just add CSRF token field)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Same head section as original -->
</head>
<body>
    <!-- Same body structure as original -->

    <!-- IMPORTANT: Add CSRF token to all forms -->
    <form method="post" action="">
        <?php echo CSRFProtection::getTokenField(); ?>
        <!-- Rest of form fields -->
    </form>

    <!-- Continue with rest of HTML... -->
</body>
</html>
