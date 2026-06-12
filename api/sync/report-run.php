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

$payload = accessSyncApiReadJsonPayload();
accessSyncEnsureRuntimeSchema($koneksi);

$sourceName = $payload['source_name'] ?? 'Machine Report';
$sourceFile = $payload['source_file'] ?? 'heartbeat.json';
$syncMode = $payload['sync_mode'] ?? 'incremental';
$status = $payload['status'] ?? 'success';
$runId = accessSyncCreateRun($koneksi, $sourceName, $sourceFile, $syncMode, [
    'dataset_key' => $payload['dataset_key'] ?? 'heartbeat',
    'trigger_source' => $payload['trigger_source'] ?? 'scheduler',
    'source_cabang' => $payload['source_cabang'] ?? '',
    'machine_name' => $payload['machine_name'] ?? ($_SERVER['HTTP_X_MACHINE_NAME'] ?? ''),
    'batch_key' => $payload['batch_key'] ?? '',
    'request_ip' => accessSyncApiRequestIp(),
]);
accessSyncFinishRun(
    $koneksi,
    $runId,
    $status,
    (int) ($payload['total_rows'] ?? 0),
    (int) ($payload['success_rows'] ?? 0),
    (int) ($payload['failed_rows'] ?? 0),
    (string) ($payload['notes'] ?? 'Heartbeat / scheduler report'),
    [
        'merge_status' => $payload['merge_status'] ?? 'skipped',
        'merge_processed' => (int) ($payload['merge_processed'] ?? 0),
        'merge_inserted' => (int) ($payload['merge_inserted'] ?? 0),
        'merge_updated' => (int) ($payload['merge_updated'] ?? 0),
        'merge_upserted' => (int) ($payload['merge_upserted'] ?? 0),
    ]
);

accessSyncApiRespond([
    'success' => true,
    'run_id' => $runId,
    'message' => 'Run report tersimpan'
]);
