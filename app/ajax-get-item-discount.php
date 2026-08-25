<?php
/**
 * AJAX: GET ITEM DISCOUNT (COMBINED)
 * ============================================================================
 * Mengecek semua jenis diskon untuk item tertentu:
 * 1. Promo Periode (Prioritas Tertinggi)
 * 2. Diskon Member (Jika tidak ada promo dan tidak excluded)
 * 
 * Parameter:
 * - noitem: Kode item
 * - no_pelanggan: Kode pelanggan (untuk member discount)
 * - item_type: 'barang' | 'jasa'
 * - tanggal: (optional) tanggal servis
 * 
 * Return: JSON detail diskon
 * ============================================================================
 */

session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include "../config/koneksi.php";
include "_include_statistik_pelanggan.php"; // Helper functions for member discount

// Get parameters
$noitem = isset($_GET['noitem']) ? mysqli_real_escape_string($koneksi, $_GET['noitem']) : '';
$no_pelanggan = isset($_GET['no_pelanggan']) ? mysqli_real_escape_string($koneksi, $_GET['no_pelanggan']) : '';
$item_type = isset($_GET['item_type']) ? strtolower(trim($_GET['item_type'])) : 'barang';
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : date('Y-m-d');

if(empty($noitem)) {
    echo json_encode([
        'success' => false, 
        'message' => 'noitem required',
        'has_discount' => false
    ]);
    exit;
}

// Result structure default
$result = [
    'success' => true,
    'has_discount' => false,
    'discount_source' => 'none', // promo, member, none
    'discount_type' => 'persen',
    'discount_value' => 0,
    'promo_id' => null,
    'promo_name' => '',
    'is_excluded_member' => false,
    'debug_info' => []
];

// 1. CHECK PROMO PERIODE (High Priority)
// Use the logic from existing ajax-get-promo-detail.php but integrated here
$target_type = ($item_type == 'jasa') ? 'jasa' : 'barang';

// Check date format
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}

$query_promo = "SELECT
            id_promo, nama_promo, tipe_promo, nilai_promo
          FROM v_promo_target_legacy
          WHERE target_type = '$target_type'
            AND (target_id = '$noitem' OR FIND_IN_SET('$noitem', target_id))
            AND status_aktif = 1
            AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai
          ORDER BY nilai_promo DESC
          LIMIT 1";

$q_promo = mysqli_query($koneksi, $query_promo);

if($q_promo && mysqli_num_rows($q_promo) > 0) {
    // Found Active Promo!
    $row_promo = mysqli_fetch_assoc($q_promo);
    
    $result['has_discount'] = true;
    $result['discount_source'] = 'promo';
    $result['discount_type'] = $row_promo['tipe_promo'] == 'nominal' ? 'nominal' : 'persen';
    $result['discount_value'] = floatval($row_promo['nilai_promo']);
    $result['promo_id'] = $row_promo['id_promo'];
    $result['promo_name'] = $row_promo['nama_promo'];
    
    echo json_encode($result);
    // Exit here because promo overrides member discount
    exit;
}

// 2. CHECK MEMBER DISCOUNT (If customer provided)
if(!empty($no_pelanggan)) {
    // Check if excluded
    $is_excluded = isItemExcludedFromMemberDiscount($koneksi, $noitem);
    $result['is_excluded_member'] = $is_excluded;
    
    if(!$is_excluded) {
        // Get member discount %
        $member_disc_percent = getMemberDiscountForItem($koneksi, $no_pelanggan, $noitem, $item_type);
        
        if($member_disc_percent > 0) {
            $result['has_discount'] = true;
            $result['discount_source'] = 'member';
            $result['discount_type'] = 'persen';
            $result['discount_value'] = floatval($member_disc_percent);
            $result['promo_name'] = 'Member Discount'; // Generic label
        }
    }
}

echo json_encode($result);
?>
