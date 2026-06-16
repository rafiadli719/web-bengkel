<?php
declare(strict_types=1);

    $baseUrl = $argv[1] ?? 'http://localhost/web-bengkel/aplikasi/aplikasi/api/sync';

require_once __DIR__ . '/../app/_include_access_sync.php';

$token = accessSyncGetApiToken();
if ($token === '') {
    fwrite(STDERR, "ACCESS_SYNC_TOKEN belum dikonfigurasi.\n");
    exit(1);
}

function readCsvRows(string $path): array {
    $rows = [];
    $handle = fopen($path, 'rb');
    if (!$handle) {
        throw new RuntimeException('Gagal membuka file: ' . $path);
    }

    $headers = fgetcsv($handle);
    if (!is_array($headers)) {
        fclose($handle);
        return [];
    }

    while (($values = fgetcsv($handle)) !== false) {
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $values[$index] ?? '';
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function postJson(string $url, string $token, array $payload): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", [
                'Content-Type: application/json',
                'X-Sync-Token: ' . $token,
                'X-Machine-Name: LOCAL-CLI-TEST',
                'Content-Length: ' . strlen((string) $body),
            ]),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 60,
        ]
    ];

    $response = file_get_contents($url, false, stream_context_create($opts));
    return is_string($response) ? (json_decode($response, true) ?: ['raw' => $response]) : ['raw' => ''];
}

$tests = [
    [
        'label' => 'Preflight',
        'url' => $baseUrl . '/preflight.php',
        'payload' => ['source_name' => 'Local CLI Test']
    ],
    [
        'label' => 'Master Cabang',
        'url' => $baseUrl . '/master-data/cabang.php',
        'payload' => [
            'sync_mode' => 'full',
            'trigger_source' => 'cli_test',
            'source_name' => 'CLI Test Cabang',
            'rows' => [
                ['kode' => '201601001', 'cabang' => 'FIT MOTOR ADIWERNA'],
                ['kode' => '201809001', 'cabang' => 'FIT MOTOR PACUL'],
                ['kode' => '202201001', 'cabang' => 'FIT MOTOR CIKDITIRO'],
                ['kode' => '202505001', 'cabang' => 'FIT MOTOR TRAYEMAN'],
                ['kode' => '202601001', 'cabang' => 'FIT MOTOR PUSAT'],
            ]
        ]
    ],
    [
        'label' => 'Master Pelanggan',
        'url' => $baseUrl . '/master-data/customers.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Pelanggan',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_pelanggan_sample.csv'),
        ]
    ],
    [
        'label' => 'Master Kendaraan',
        'url' => $baseUrl . '/master-data/vehicles.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Kendaraan',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_kendaraan_pelanggan_sample.csv'),
        ]
    ],
    [
        'label' => 'Master Item',
        'url' => $baseUrl . '/master-data/items-master.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Item',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_item_sample.csv'),
        ]
    ],
    [
        'label' => 'Master Supplier',
        'url' => $baseUrl . '/master-data/suppliers.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Supplier',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_supplier_sample.csv'),
        ]
    ],
    [
        'label' => 'Master Mekanik',
        'url' => $baseUrl . '/master-data/mechanics.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Mekanik',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_mekanik_sample.csv'),
        ]
    ],
    [
        'label' => 'Member Pelanggan',
        'url' => $baseUrl . '/master-data/customer-members.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Member',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_member_pelanggan_sample.csv'),
        ]
    ],
    [
        'label' => 'Transaksi Pembelian',
        'url' => $baseUrl . '/transactions/pembelian.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Pembelian',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_pembelian_sample.csv'),
        ]
    ],
    [
        'label' => 'Transaksi Penjualan',
        'url' => $baseUrl . '/transactions/penjualan.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Penjualan',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_penjualan_sample.csv'),
        ]
    ],
    [
        'label' => 'Transaksi Service',
        'url' => $baseUrl . '/transactions/service.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PST',
            'source_name' => 'CLI Test Service',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_service_sample.csv'),
        ]
    ],
    [
        'label' => 'Histori Service Barang',
        'url' => $baseUrl . '/transactions/service-barang.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PESALAKAN',
            'source_name' => 'CLI Test Service Barang',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_service_barang_sample.csv'),
        ]
    ],
    [
        'label' => 'Histori Service Jasa',
        'url' => $baseUrl . '/transactions/service-jasa.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PESALAKAN',
            'source_name' => 'CLI Test Service Jasa',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_service_jasa_sample.csv'),
        ]
    ],
    [
        'label' => 'Service Advisor',
        'url' => $baseUrl . '/transactions/service-advisor.php',
        'payload' => [
            'sync_mode' => 'incremental',
            'trigger_source' => 'cli_test',
            'source_cabang' => 'PESALAKAN',
            'source_name' => 'CLI Test Service Advisor',
            'rows' => readCsvRows(__DIR__ . '/testdata/access_sync_service_advisor_sample.csv'),
        ]
    ],
];

foreach ($tests as $test) {
    echo "== {$test['label']} ==\n";
    $response = postJson($test['url'], $token, $test['payload']);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
}
