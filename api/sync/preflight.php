<?php
require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    accessSyncApiRespond(['success' => false, 'error' => 'Method harus POST'], 405);
}

accessSyncApiRequireAuth();

global $koneksi;
if (!$koneksi) {
    accessSyncApiRespond(['success' => false, 'error' => 'Koneksi database gagal'], 500);
}

accessSyncEnsureRuntimeSchema($koneksi);
accessSyncApiRespond([
    'success' => true,
    'message' => 'Preflight OK',
    'server_time' => date('Y-m-d H:i:s'),
    'db_name' => getenv('DB_NAME') ?: 'fitmotor_dbbengkel',
    'token_configured' => accessSyncGetApiToken() !== '',
    'supported_master' => ['cabang', 'customers', 'vehicles', 'items-master', 'suppliers', 'mechanics', 'customer-members'],
    'supported_transactions' => ['pembelian', 'penjualan', 'service', 'service_barang', 'service_jasa', 'service_advisor']
]);
