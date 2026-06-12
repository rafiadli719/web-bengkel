<?php
/**
 * AJAX Search Barang - JSON Response
 * For redesigned tab-item-barang
 */
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Allow for testing - don't require session for now, but still check
$kd_cabang = $_SESSION['_cabang'] ?? '';

$keyword = trim($_GET['q'] ?? $_POST['q'] ?? '');

if(strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

// Determine motor category context (prefer kd_kategori_motor). Fallback to kd_jenis_motor for legacy schema.
$no_service = trim($_GET['no_service'] ?? $_POST['no_service'] ?? '');
$kd_kategori_motor = intval($_GET['kd_kategori_motor'] ?? $_POST['kd_kategori_motor'] ?? 0);
if ($kd_kategori_motor <= 0) {
    // Backward compatibility: accept kd_jenis_motor param name
    $kd_kategori_motor = intval($_GET['kd_jenis_motor'] ?? $_POST['kd_jenis_motor'] ?? 0);
}
if ($kd_kategori_motor <= 0 && $no_service !== '') {
    $no_service_safe = mysqli_real_escape_string($koneksi, $no_service);
    // Try new view first
    $res_kd = mysqli_query($koneksi, "SELECT kd_kategori_motor FROM view_service_kategori_motor WHERE no_service='{$no_service_safe}'");
    if ($res_kd && ($row_kd = mysqli_fetch_assoc($res_kd)) && isset($row_kd['kd_kategori_motor'])) {
        $kd_kategori_motor = intval($row_kd['kd_kategori_motor'] ?? 0);
    }
    if ($kd_kategori_motor <= 0) {
        // Fallback to legacy view
        $res_kd2 = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
        if ($res_kd2 && ($row_kd2 = mysqli_fetch_assoc($res_kd2)) && isset($row_kd2['kd_jenis_motor'])) {
            $kd_kategori_motor = intval($row_kd2['kd_jenis_motor'] ?? 0);
        }
    }
}

// Detect mapping column name for items mapping table
$map_col = 'kd_kategori_motor';
$col_check = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_kategori_motor'");
if (!$col_check || mysqli_num_rows($col_check) == 0) {
    $map_col = 'kd_jenis_motor';
}

// Escape keyword for SQL
$keyword_safe = mysqli_real_escape_string($koneksi, $keyword);

$items = [];

// Search items and compute applicability based on motor type mapping (STRICT marking)
$sql = "SELECT i.noitem, i.namaitem, i.hargajual, i.satuan, i.kodebarcode,
               v.namajenis,
               CASE
                   WHEN {$kd_kategori_motor} <= 0 THEN 1
                   WHEN NOT EXISTS (SELECT 1 FROM tbitem_jenis_motor jm0 WHERE jm0.noitem=i.noitem) THEN 1
                   WHEN EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=i.noitem AND jm.".$map_col."={$kd_kategori_motor}) THEN 1
                   ELSE 0
               END AS applicable
        FROM tblitem i
        LEFT JOIN view_cari_item v ON v.noitem = i.noitem
        WHERE (i.noitem LIKE '%{$keyword_safe}%'
               OR i.namaitem LIKE '%{$keyword_safe}%'
               OR i.kodebarcode LIKE '%{$keyword_safe}%')
        ORDER BY applicable DESC, i.namaitem ASC
        LIMIT 20";

$result = mysqli_query($koneksi, $sql);

if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $noitem = $row['noitem'];

        // Get stock for this branch
        $stok = 0;
        if(!empty($kd_cabang)) {
            // Try view_stok_master first
            $stok_query = mysqli_query($koneksi, "SELECT saldo
                                                  FROM view_stok_master
                                                  WHERE kd_cabang='{$kd_cabang}'
                                                  AND no_item='{$noitem}'");
            if($stok_query && mysqli_num_rows($stok_query) > 0) {
                $stok_row = mysqli_fetch_assoc($stok_query);
                $stok = intval($stok_row['saldo'] ?? 0);
            } else {
                // Fallback to tbstok
                $stok_query = mysqli_query($koneksi, "SELECT SUM(masuk - keluar) as saldo
                                                      FROM tbstok
                                                      WHERE kd_cabang='{$kd_cabang}'
                                                      AND no_item='{$noitem}'");
                if($stok_query && $stok_row = mysqli_fetch_assoc($stok_query)) {
                    $stok = intval($stok_row['saldo'] ?? 0);
                }
            }
        }

        $items[] = [
            'kode' => $noitem,
            'nama' => $row['namaitem'] ?? '',
            'harga' => floatval($row['hargajual'] ?? 0),
            'stok' => $stok,
            'satuan' => $row['satuan'] ?? 'PCS',
            'barcode' => $row['kodebarcode'] ?? '',
            'jenis' => $row['namajenis'] ?? '',
            'applicable' => intval($row['applicable'] ?? 0)
        ];
    }
}

echo json_encode($items);
?>
