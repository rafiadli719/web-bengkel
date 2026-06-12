<?php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../../config/koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

if (!isset($_POST['no_service']) || empty($_POST['no_service'])) {
    echo json_encode(['success' => false, 'message' => 'No service tidak valid']);
    exit;
}

$no_service = trim($_POST['no_service']);

$type = $_POST['type'] ?? 'barang';

try {
    $items = [];

    if ($type == 'barang') {
        // Load barang items from tblservis_barang
        $query = "SELECT sb.no_item as kode, i.namaitem as nama, sb.quantity as qty,
                         sb.harga_jual as harga, sb.total as subtotal, i.satuan
                  FROM tblservis_barang sb
                  LEFT JOIN tblitem i ON sb.no_item = i.noitem
                  WHERE sb.no_service = ?
                  ORDER BY sb.no_item";

        $stmt = mysqli_prepare($koneksi, $query);
        if(!$stmt){ throw new Exception('Prepare failed: '.mysqli_error($koneksi)); }
        mysqli_stmt_bind_param($stmt, "s", $no_service);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $counter = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'id' => $counter++,
                'kode' => $row['kode'],
                'nama' => $row['nama'] ?? $row['kode'],
                'qty' => (int)$row['qty'],
                'satuan' => $row['satuan'] ?? 'Pcs',
                'harga' => (float)$row['harga'],
                'subtotal' => (float)$row['subtotal']
            ];
        }

    } elseif ($type == 'jasa') {
        // Load jasa items from tblservis_jasa
        $query = "SELECT sj.no_item as kode, wh.nama_wo as nama, 1 as qty,
                         sj.harga, sj.total as subtotal, sj.waktu
                  FROM tblservis_jasa sj
                  LEFT JOIN tbworkorderheader wh ON sj.no_item = wh.kode_wo
                  WHERE sj.no_service = ?
                  ORDER BY sj.no_item";

        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "s", $no_service);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $counter = 1;
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'id' => $counter++,
                'kode' => $row['kode'],
                'nama' => $row['nama'] ?? $row['kode'],
                'qty' => (int)$row['qty'],
                'harga' => (float)$row['harga'],
                'subtotal' => (float)$row['subtotal'],
                'waktu' => (int)($row['waktu'] ?? 0)
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items),
        'type' => $type
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
}
?>