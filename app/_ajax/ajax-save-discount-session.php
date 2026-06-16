<?php
/**
 * AJAX Handler - Save Discount to Session
 *
 * Menyimpan data diskon yang dipilih ke session untuk digunakan
 * di halaman input servis
 *
 * @author Claude AI
 * @date 2026-01-05
 */

session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

// Get POST data
$nopol = isset($_POST['nopol']) ? trim($_POST['nopol']) : '';
$service_type = isset($_POST['service_type']) ? trim($_POST['service_type']) : 'reguler';
$discount_type = isset($_POST['discount_type']) ? trim($_POST['discount_type']) : 'none';
$discount_value = isset($_POST['discount_value']) ? $_POST['discount_value'] : '';
$apply_discount = isset($_POST['apply_discount']) ? intval($_POST['apply_discount']) : 0;

if(empty($nopol)) {
    echo json_encode(['success' => false, 'message' => 'Nomor polisi tidak valid']);
    exit;
}

// Parse discount value if it's JSON string
$discount_data = [];
if(!empty($discount_value) && is_string($discount_value)) {
    $decoded = json_decode($discount_value, true);
    if($decoded) {
        $discount_data = $decoded;
    }
}

// Store in session
$_SESSION['service_discount'] = [
    'nopol' => $nopol,
    'service_type' => $service_type,
    'discount_type' => $discount_type,
    'discount_data' => $discount_data,
    'apply_discount' => $apply_discount,
    'timestamp' => time()
];

// Return success response
echo json_encode([
    'success' => true,
    'message' => $apply_discount ? 'Diskon akan diterapkan' : 'Melanjutkan tanpa diskon',
    'data' => [
        'nopol' => $nopol,
        'discount_type' => $discount_type,
        'apply_discount' => $apply_discount
    ]
]);
?>
