<?php
/**
 * AJAX ENDPOINT - HITUNG TARIF JEMPUT ANTAR
 * ==========================================
 * Endpoint untuk menghitung tarif jemput antar secara real-time
 * Dibuat: 13 Oktober 2025
 */

header('Content-Type: application/json');
session_start();

// Check authentication
if (empty($_SESSION['_iduser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Database connection
include "../config/koneksi.php";
include "functions/tarif_jemput_helper.php";

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    // Get and validate input
    $jenis_motor = $_POST['jenis_motor'] ?? '';
    $jarak = floatval($_POST['jarak'] ?? 0);
    
    if (empty($jenis_motor)) {
        throw new Exception('Jenis motor harus dipilih');
    }
    
    if ($jarak <= 0) {
        throw new Exception('Jarak harus lebih dari 0');
    }
    
    // Hitung tarif menggunakan helper function
    $tarif_info = hitungTarifJemput($koneksi, $jenis_motor, $jarak);
    
    if (!$tarif_info['success']) {
        throw new Exception($tarif_info['error']);
    }
    
    // Hitung juga untuk jenis motor lainnya (untuk perbandingan)
    $jenis_lain = ($jenis_motor === 'Motor Jalan') ? 'Motor Mogok' : 'Motor Jalan';
    $tarif_lain = hitungTarifJemput($koneksi, $jenis_lain, $jarak);
    
    // Format response
    $response = [
        'success' => true,
        'data' => [
            'jarak' => $jarak,
            'jenis_motor' => $jenis_motor,
            'tarif' => $tarif_info['tarif'],
            'tarif_formatted' => formatTarif($tarif_info['tarif']),
            'breakdown' => $tarif_info['breakdown'],
            'range_info' => $tarif_info['range_info'],
            'perbandingan' => [
                'jenis' => $jenis_lain,
                'tarif' => $tarif_lain['success'] ? $tarif_lain['tarif'] : 0,
                'tarif_formatted' => $tarif_lain['success'] ? formatTarif($tarif_lain['tarif']) : 'N/A'
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
