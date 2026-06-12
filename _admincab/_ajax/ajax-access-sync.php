<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

include "../../config/koneksi.php";
require_once "../_include_access_sync.php";

if (!$koneksi) {
    echo json_encode(['success' => false, 'error' => 'Koneksi database gagal']);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$dataset = isset($_POST['dataset']) ? trim($_POST['dataset']) : '';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : 'append';
$sourceCabang = isset($_POST['source_cabang']) ? strtoupper(trim($_POST['source_cabang'])) : '';
$rows = isset($_POST['rows']) ? json_decode($_POST['rows'], true) : [];

$configs = accessSyncDatasetConfigs();

if ($action === 'errors') {
    $runId = isset($_POST['run_id']) ? (int) $_POST['run_id'] : 0;
    $sql = "SELECT dataset_name, row_key, error_message, created_at
            FROM sync_access_row_errors
            WHERE sync_run_id = ?
            ORDER BY id DESC
            LIMIT 100";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $runId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rowsOut = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rowsOut[] = $row;
    }
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'errors' => $rowsOut]);
    exit;
}

if ($action === 'merge_master') {
    $runId = isset($_POST['run_id']) ? (int) $_POST['run_id'] : 0;
    if ($runId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Run ID tidak valid']);
        exit;
    }
    if (!accessSyncIsMasterDataset($dataset)) {
        echo json_encode(['success' => false, 'error' => 'Dataset ini bukan master operasional']);
        exit;
    }
    $merge = accessSyncMergeMasterRun($koneksi, $dataset, $runId);
    if (!$merge['success']) {
        echo json_encode(['success' => false, 'error' => $merge['message']]);
        exit;
    }
    echo json_encode(['success' => true, 'summary' => $merge]);
    exit;
}

if ($action === 'merge_transaction') {
    $runId = isset($_POST['run_id']) ? (int) $_POST['run_id'] : 0;
    if ($runId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Run ID tidak valid']);
        exit;
    }
    if (!accessSyncIsTransactionDataset($dataset)) {
        echo json_encode(['success' => false, 'error' => 'Dataset ini bukan transaksi konsolidasi']);
        exit;
    }
    $merge = accessSyncMergeTransactionRun($koneksi, $dataset, $runId);
    if (!$merge['success']) {
        echo json_encode(['success' => false, 'error' => $merge['message']]);
        exit;
    }
    echo json_encode(['success' => true, 'summary' => $merge]);
    exit;
}

if (!isset($configs[$dataset])) {
    echo json_encode(['success' => false, 'error' => 'Dataset tidak valid']);
    exit;
}

if (!is_array($rows) || empty($rows)) {
    echo json_encode(['success' => false, 'error' => 'Tidak ada data baris yang diproses']);
    exit;
}

if ($action === 'preview') {
    $previewRows = [];
    $summary = ['total' => 0, 'valid' => 0, 'invalid' => 0];

    foreach ($rows as $index => $row) {
        list($mapped, $errors) = accessSyncMapRow($dataset, $row, $sourceCabang);
        $summary['total']++;
        if (empty($errors)) {
            $summary['valid']++;
        } else {
            $summary['invalid']++;
        }

        if ($index < 20) {
            $previewRows[] = [
                'row_number' => $index + 1,
                'mapped' => $mapped,
                'errors' => $errors
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'preview' => $previewRows,
        'dataset_label' => $configs[$dataset]['label']
    ]);
    exit;
}

if ($action === 'save') {
    $fileName = isset($_FILES['source_file']['name']) ? $_FILES['source_file']['name'] : ('manual-' . $dataset . '.json');
    if (!accessSyncAllowedExtension($fileName)) {
        echo json_encode(['success' => false, 'error' => 'Extension file tidak didukung']);
        exit;
    }

    $storageDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'access_sync';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0775, true);
    }

    $storedFilename = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($fileName));
    $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedFilename;
    if (isset($_FILES['source_file']['tmp_name']) && is_uploaded_file($_FILES['source_file']['tmp_name'])) {
        move_uploaded_file($_FILES['source_file']['tmp_name'], $storedPath);
    }

    mysqli_begin_transaction($koneksi);
    try {
        $runId = accessSyncCreateRun($koneksi, $dataset, $storedFilename, $mode === 'replace' ? 'manual_upload' : 'manual_upload');

        if ($mode === 'replace') {
            accessSyncDeletePriorStaging($koneksi, $dataset, $sourceCabang);
        }

        $totalRows = 0;
        $successRows = 0;
        $failedRows = 0;

        foreach ($rows as $row) {
            $totalRows++;
            list($mapped, $errors) = accessSyncMapRow($dataset, $row, $sourceCabang);
            $rowKey = '';
            if (isset($mapped['no_transaksi'])) {
                $rowKey = (string) $mapped['no_transaksi'];
            } elseif (isset($mapped['no_service'])) {
                $rowKey = (string) $mapped['no_service'];
            } elseif (isset($mapped['no_supplier'])) {
                $rowKey = (string) $mapped['no_supplier'];
            } elseif (isset($mapped['no_mekanik'])) {
                $rowKey = (string) $mapped['no_mekanik'];
            } elseif (isset($mapped['no_pelanggan'])) {
                $rowKey = (string) $mapped['no_pelanggan'];
            } elseif (isset($mapped['no_item'])) {
                $rowKey = (string) $mapped['no_item'];
            } elseif (isset($mapped['no_polisi'])) {
                $rowKey = (string) $mapped['no_polisi'];
            }

            if (!empty($errors)) {
                $failedRows++;
                accessSyncLogError($koneksi, $runId, $dataset, $rowKey, implode('; ', $errors), $row);
                continue;
            }

            list($saved, $saveError) = accessSyncInsertRow($koneksi, $dataset, $runId, $mapped, $row);
            if ($saved) {
                $successRows++;
            } else {
                $failedRows++;
                accessSyncLogError($koneksi, $runId, $dataset, $rowKey, $saveError, $row);
            }
        }

        $status = $failedRows > 0 ? ($successRows > 0 ? 'partial' : 'failed') : 'success';
        $notes = 'Mode: ' . $mode . '. Dataset: ' . $dataset;
        accessSyncFinishRun($koneksi, $runId, $status, $totalRows, $successRows, $failedRows, $notes);
        mysqli_commit($koneksi);

        echo json_encode([
            'success' => true,
            'run_id' => $runId,
            'status' => $status,
            'summary' => [
                'total' => $totalRows,
                'success' => $successRows,
                'failed' => $failedRows
            ]
        ]);
        exit;
    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Action tidak dikenali']);
