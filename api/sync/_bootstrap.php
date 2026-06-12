<?php
header('Content-Type: application/json; charset=utf-8');

include dirname(__DIR__, 2) . '/config/koneksi.php';
require_once dirname(__DIR__, 2) . '/_admincab/_include_access_sync.php';

function accessSyncApiRespond($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function accessSyncApiReadJsonPayload() {
    $rawBody = file_get_contents('php://input');
    if (!is_string($rawBody) || trim($rawBody) === '') {
        return [];
    }

    $decoded = json_decode($rawBody, true);
    return is_array($decoded) ? $decoded : [];
}

function accessSyncApiRequireAuth() {
    $token = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';
    if (!accessSyncValidateApiToken($token)) {
        accessSyncApiRespond([
            'success' => false,
            'error' => 'Token sync tidak valid'
        ], 401);
    }
}

function accessSyncApiRequestIp() {
    foreach (['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return trim((string) explode(',', (string) $_SERVER[$key])[0]);
        }
    }
    return '';
}

function accessSyncApiBaseOptions($payload, $datasetKey) {
    return [
        'source_cabang' => $payload['source_cabang'] ?? '',
        'mode' => $payload['mode'] ?? 'append',
        'source_name' => $payload['source_name'] ?? accessSyncDatasetLabel($datasetKey),
        'source_file' => $payload['source_file'] ?? ('api-' . $datasetKey . '.json'),
        'sync_mode' => $payload['sync_mode'] ?? 'incremental',
        'trigger_source' => $payload['trigger_source'] ?? 'api_auto',
        'machine_name' => $payload['machine_name'] ?? ($_SERVER['HTTP_X_MACHINE_NAME'] ?? ''),
        'batch_key' => $payload['batch_key'] ?? '',
        'request_ip' => accessSyncApiRequestIp(),
        'auto_merge' => array_key_exists('auto_merge', $payload) ? (bool) $payload['auto_merge'] : true,
    ];
}

function accessSyncApiHandleDataset($datasetKey) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        accessSyncApiRespond(['success' => false, 'error' => 'Method harus POST'], 405);
    }
    accessSyncApiRequireAuth();

    global $koneksi;
    if (!$koneksi) {
        accessSyncApiRespond(['success' => false, 'error' => 'Koneksi database gagal'], 500);
    }

    $payload = accessSyncApiReadJsonPayload();
    $rows = $payload['rows'] ?? [];
    $result = accessSyncRunDatasetSync($koneksi, $datasetKey, $rows, accessSyncApiBaseOptions($payload, $datasetKey));
    if (!$result['success']) {
        accessSyncApiRespond(['success' => false, 'error' => $result['message']], 422);
    }

    accessSyncApiRespond($result);
}

function accessSyncApiHandleCabang() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        accessSyncApiRespond(['success' => false, 'error' => 'Method harus POST'], 405);
    }
    accessSyncApiRequireAuth();

    global $koneksi;
    if (!$koneksi) {
        accessSyncApiRespond(['success' => false, 'error' => 'Koneksi database gagal'], 500);
    }

    $payload = accessSyncApiReadJsonPayload();
    $rows = $payload['rows'] ?? [];
    $result = accessSyncRunCabangSync($koneksi, $rows, accessSyncApiBaseOptions($payload, 'cabang'));
    if (!$result['success']) {
        accessSyncApiRespond(['success' => false, 'error' => $result['message']], 422);
    }

    accessSyncApiRespond($result);
}
