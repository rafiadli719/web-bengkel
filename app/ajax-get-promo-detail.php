<?php
/**
 * AJAX: GET PROMO DETAIL
 * ============================================================================
 * Mengecek apakah item/jasa/workorder tertentu sedang memiliki promo aktif
 * 
 * Parameter:
 * - target_type: 'workorder' | 'jasa' | 'barang'
 * - target_id: kode_wo atau noitem
 * - tanggal: (optional) tanggal untuk cek, default hari ini
 * 
 * Return:
 * - has_promo: boolean
 * - promo: { id, nama, tipe, nilai }
 * ============================================================================
 */

session_start();
if(empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include "../config/koneksi.php";

header('Content-Type: application/json');

// Get parameters
$target_type = isset($_GET['target_type']) ? mysqli_real_escape_string($koneksi, $_GET['target_type']) : '';
$target_id = isset($_GET['target_id']) ? mysqli_real_escape_string($koneksi, $_GET['target_id']) : '';
$tanggal = isset($_GET['tanggal']) ? mysqli_real_escape_string($koneksi, $_GET['tanggal']) : date('Y-m-d');

// Validate
if(empty($target_type) || empty($target_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter target_type dan target_id wajib diisi',
        'has_promo' => false
    ]);
    exit;
}

// Validate target_type
if(!in_array($target_type, ['workorder', 'jasa', 'barang'])) {
    echo json_encode([
        'success' => false,
        'message' => 'target_type tidak valid',
        'has_promo' => false
    ]);
    exit;
}

// Validate date format
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    $tanggal = date('Y-m-d');
}

// Query for active promo
$query = "SELECT 
            id_promo,
            nama_promo,
            keterangan,
            tipe_promo,
            nilai_promo,
            tanggal_mulai,
            tanggal_selesai,
            target_nama
          FROM v_promo_target_legacy
          WHERE target_type = '$target_type'
            AND target_id = '$target_id'
            AND status_aktif = 1
            AND '$tanggal' BETWEEN tanggal_mulai AND tanggal_selesai
          ORDER BY nilai_promo DESC
          LIMIT 1";

$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    
    echo json_encode([
        'success' => true,
        'has_promo' => true,
        'promo' => [
            'id' => intval($row['id_promo']),
            'nama' => $row['nama_promo'],
            'deskripsi' => $row['keterangan'],
            'tipe' => $row['tipe_promo'],
            'nilai' => floatval($row['nilai_promo']),
            'tanggal_mulai' => $row['tanggal_mulai'],
            'tanggal_selesai' => $row['tanggal_selesai'],
            'target_nama' => $row['target_nama']
        ]
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_promo' => false,
        'promo' => null
    ]);
}
?>
