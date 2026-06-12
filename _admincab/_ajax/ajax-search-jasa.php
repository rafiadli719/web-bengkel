<?php
/**
 * AJAX Search Jasa / Work Order - JSON Response
 * For redesigned tab-item-jasa
 */
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$keyword = trim($_GET['q'] ?? $_POST['q'] ?? '');

if(strlen($keyword) < 2) {
    echo json_encode([]);
    exit;
}

// Determine motor category context (prefer kd_kategori_motor) from request or service context. Fallback to kd_jenis_motor for legacy schema
$no_service = trim($_GET['no_service'] ?? $_POST['no_service'] ?? '');
$kd_kategori_motor = intval($_GET['kd_kategori_motor'] ?? $_POST['kd_kategori_motor'] ?? 0);
if ($kd_kategori_motor <= 0) {
    // Backward compatible param name
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

// Detect mapping column names
$item_map_col = 'kd_kategori_motor';
$chk_item_map = mysqli_query($koneksi, "SHOW COLUMNS FROM tbitem_jenis_motor LIKE 'kd_kategori_motor'");
if (!$chk_item_map || mysqli_num_rows($chk_item_map) == 0) {
    $item_map_col = 'kd_jenis_motor';
}
$wo_map_col = 'kd_kategori_motor';
$chk_wo_map = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorder_jenis_motor LIKE 'kd_kategori_motor'");
if (!$chk_wo_map || mysqli_num_rows($chk_wo_map) == 0) {
    $wo_map_col = 'kd_jenis_motor';
}

// Escape keyword for SQL
$keyword_safe = mysqli_real_escape_string($koneksi, $keyword);

$items = [];

// 1. Search in tblitem_jasa first (main source for jasa items)
$sql_item_jasa = "SELECT j.noitem, j.namaitem, j.harga, j.waktu,
                         CASE WHEN " . ($kd_kategori_motor > 0 ? "EXISTS (SELECT 1 FROM tbitem_jenis_motor m WHERE m.noitem=j.noitem AND m.".$item_map_col."={$kd_kategori_motor})" : "1=1") . "
                         THEN 1 ELSE 0 END AS applicable
                  FROM tblitem_jasa j
                  WHERE (j.noitem LIKE '%{$keyword_safe}%'
                         OR j.namaitem LIKE '%{$keyword_safe}%')
                  ORDER BY applicable DESC, j.namaitem ASC
                  LIMIT 20";

$result_item_jasa = mysqli_query($koneksi, $sql_item_jasa);

if($result_item_jasa && mysqli_num_rows($result_item_jasa) > 0) {
    while($row = mysqli_fetch_assoc($result_item_jasa)) {
        $items[] = [
            'kode' => $row['noitem'],
            'nama' => $row['namaitem'] ?? '',
            'harga' => floatval($row['harga'] ?? 0),
            'waktu' => intval($row['waktu'] ?? 0),
            'applicable' => intval($row['applicable'] ?? 1)
        ];
    }
}

// 2. Search in tbworkorderheader (Work Order packages)
$sql_wo = "SELECT kode_wo, nama_wo, waktu, harga
           FROM tbworkorderheader
           WHERE (kode_wo LIKE '%{$keyword_safe}%'
                  OR nama_wo LIKE '%{$keyword_safe}%')
           ORDER BY nama_wo ASC
           LIMIT 15";

$result_wo = mysqli_query($koneksi, $sql_wo);

if($result_wo && mysqli_num_rows($result_wo) > 0) {
    while($row = mysqli_fetch_assoc($result_wo)) {
        $kode_wo = $row['kode_wo'];

        // Use header price/time; fallback to sum detail.total for price if needed
        $harga = floatval($row['harga'] ?? 0);
        $waktu = intval($row['waktu'] ?? 0);
        if ($harga <= 0) {
            $detail_query = mysqli_query($koneksi, "SELECT SUM(total) as total_harga FROM tbworkorderdetail WHERE kode_wo='{$kode_wo}'");
            if($detail_query && $detail = mysqli_fetch_assoc($detail_query)) {
                $harga = floatval($detail['total_harga'] ?? 0);
            }
        }

        // Applicable check against mapping table
        $applicable_wo = 1;
        if ($kd_kategori_motor > 0) {
            $res_app = mysqli_query($koneksi, "SELECT 1 FROM tbworkorder_jenis_motor WHERE kode_wo='{$kode_wo}' AND ".$wo_map_col."={$kd_kategori_motor} LIMIT 1");
            $applicable_wo = ($res_app && mysqli_num_rows($res_app) > 0) ? 1 : 0;
        }

        $items[] = [
            'kode' => $kode_wo,
            'nama' => '[WO] ' . ($row['nama_wo'] ?? ''),
            'harga' => $harga,
            'waktu' => $waktu,
            'applicable' => $applicable_wo
        ];
    }
}

// 3. Search in tblitem where it's jasa type (fallback)
$sql_item = "SELECT i.noitem, i.namaitem, i.hargajual, i.jasawaktu,
                    CASE WHEN " . ($kd_kategori_motor > 0 ? "EXISTS (SELECT 1 FROM tbitem_jenis_motor m WHERE m.noitem=i.noitem AND m.".$item_map_col."={$kd_kategori_motor})" : "1=1") . "
                    THEN 1 ELSE 0 END AS applicable
             FROM tblitem i
             WHERE (i.noitem LIKE '%{$keyword_safe}%'
                    OR i.namaitem LIKE '%{$keyword_safe}%')
             ORDER BY applicable DESC, i.namaitem ASC
             LIMIT 10";

$result_item = mysqli_query($koneksi, $sql_item);

if($result_item && mysqli_num_rows($result_item) > 0) {
    while($row = mysqli_fetch_assoc($result_item)) {
        // Check if already in list
        $exists = false;
        foreach($items as $item) {
            if($item['kode'] == $row['noitem']) {
                $exists = true;
                break;
            }
        }
        if(!$exists) {
            $items[] = [
                'kode' => $row['noitem'],
                'nama' => $row['namaitem'] ?? '',
                'harga' => floatval($row['hargajual'] ?? 0),
                'waktu' => intval($row['jasawaktu'] ?? 0),
                'applicable' => intval($row['applicable'] ?? 1)
            ];
        }
    }
}

echo json_encode($items);
?>
