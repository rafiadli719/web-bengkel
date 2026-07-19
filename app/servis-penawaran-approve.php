<?php
/**
 * Backend: Approve Penawaran Part
 * Ketika customer setuju dengan penawaran part, part akan ditambahkan ke tblservis_barang
 */

session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";
include "_include_kategori_member.php"; // Member kategori & discount helper

// Get user info
$cari_user = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($cari_user);
$nama_user = $user_data['nama_user'];

// Get parameters
$penawaran_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$no_service = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';

if($penawaran_id == 0 || empty($no_service)) {
    echo "<script>
            alert('Parameter tidak valid!');
            history.back();
          </script>";
    exit;
}

// Get penawaran data
$query_penawaran = "SELECT * FROM tbservis_penawaran_part WHERE id = '$penawaran_id'";
$result_penawaran = mysqli_query($koneksi, $query_penawaran);

if(!$result_penawaran || mysqli_num_rows($result_penawaran) == 0) {
    echo "<script>
            alert('Penawaran tidak ditemukan!');
            history.back();
          </script>";
    exit;
}

$penawaran = mysqli_fetch_array($result_penawaran);

// Validasi status penawaran harus pending
if($penawaran['status_penawaran'] != 'pending') {
    echo "<script>
            alert('Penawaran ini sudah diproses sebelumnya! Status: " . $penawaran['status_penawaran'] . "');
            history.back();
          </script>";
    exit;
}

// Hitung diskon promo/member aktif untuk item ini
$no_polisi_svc = '';
$q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='{$penawaran['no_service']}'");
if($q_cust && ($cust = mysqli_fetch_assoc($q_cust))) {
    $no_polisi_svc = $cust['no_polisi'] ?? '';
}

$quantity = (int)$penawaran['quantity'];
$harga_satuan = floatval($penawaran['harga_satuan']);
$diskon_source = '';
$diskon_persen = 0;
$diskon_nominal = 0;
$id_promo = 'NULL';

if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
    $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $penawaran['kode_barang'], 'barang', $harga_satuan);
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

$total_after_discount = floatval($penawaran['total_harga']) - ($diskon_nominal * $quantity);
if($total_after_discount < 0) { $total_after_discount = 0; }

mysqli_begin_transaction($koneksi);

try {
    // 1. Insert part ke tblservis_barang
    $sql_insert_barang = "INSERT INTO tblservis_barang (
                            no_service,
                            no_item,
                            quantity,
                            qty_retur,
                            harga_jual,
                            potongan,
                            total,
                            diskon_source,
                            diskon_persen,
                            diskon_nominal,
                            id_promo
                          ) VALUES (
                            '{$penawaran['no_service']}',
                            '{$penawaran['kode_barang']}',
                            '$quantity',
                            0,
                            '$harga_satuan',
                            '$diskon_persen',
                            '$total_after_discount',
                            '$diskon_source',
                            '$diskon_persen',
                            '$diskon_nominal',
                            $id_promo
                          )";

    if(!mysqli_query($koneksi, $sql_insert_barang)) {
        throw new Exception('Error insert barang: ' . mysqli_error($koneksi));
    }
    if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $penawaran['no_service'], 'barang', $penawaran['kode_barang']); }

    // 2. Update status penawaran menjadi 'disetujui'
    $sql_update_penawaran = "UPDATE tbservis_penawaran_part
                            SET status_penawaran = 'disetujui',
                                tanggal_respon = NOW(),
                                user_respon = '$id_user'
                            WHERE id = '$penawaran_id'";

    if(!mysqli_query($koneksi, $sql_update_penawaran)) {
        throw new Exception('Error update penawaran: ' . mysqli_error($koneksi));
    }

    // 3. Update status temuan jika semua penawaran sudah approved
    $temuan_id = $penawaran['temuan_id'];

    $check_pending = mysqli_query($koneksi, "SELECT COUNT(*) as total
                                             FROM tbservis_penawaran_part
                                             WHERE temuan_id = '$temuan_id'
                                             AND status_penawaran = 'pending'");
    $pending_count = mysqli_fetch_array($check_pending)['total'];

    if($pending_count == 0) {
        // Semua penawaran sudah diproses, update status temuan
        mysqli_query($koneksi, "UPDATE tbservis_temuan
                               SET status_temuan = 'disetujui'
                               WHERE id = '$temuan_id'");
    }

    // 4. Log activity
    $log_desc = "Part disetujui: {$penawaran['nama_barang']} (Qty: {$penawaran['quantity']})";

    mysqli_query($koneksi, "INSERT INTO tbservis_penawaran_log
                            (penawaran_id, no_service, action, alasan, user_action, created_at)
                            VALUES
                            ('$penawaran_id', '$no_service', 'disetujui', '$log_desc', '$id_user', NOW())");

    mysqli_query($koneksi, "INSERT INTO tbservis_temuan_activity_log
                            (no_service, temuan_id, penawaran_id, activity_type, description, user_id, user_name, created_at)
                            VALUES
                            ('$no_service', '$temuan_id', '$penawaran_id', 'penawaran_approved', '$log_desc', '$id_user', '$nama_user', NOW())");

    // 5. Update rejection summary stats
    mysqli_query($koneksi, "INSERT INTO tbservis_penawaran_rejection_summary
                            (kode_barang, nama_barang, total_penawaran, total_diterima, total_ditolak, last_offered_date)
                            VALUES
                            ('{$penawaran['kode_barang']}', '{$penawaran['nama_barang']}', 1, 1, 0, CURDATE())
                            ON DUPLICATE KEY UPDATE
                            total_penawaran = total_penawaran + 1,
                            total_diterima = total_diterima + 1,
                            acceptance_rate = (total_diterima / total_penawaran) * 100,
                            last_offered_date = CURDATE(),
                            updated_at = NOW()");

    // Commit
    mysqli_commit($koneksi);

    // Redirect back with success message
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? "servis-carinopol.php";

    echo "<script>
            alert('Penawaran part DISETUJUI!\\n\\nPart berhasil ditambahkan ke daftar barang servis.');
            window.location='$redirect_url';
          </script>";

} catch (Exception $e) {
    mysqli_rollback($koneksi);

    echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            history.back();
          </script>";
}
?>
