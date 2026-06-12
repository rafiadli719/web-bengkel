<?php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

if (!isset($_POST['kode_wo']) || empty($_POST['kode_wo'])) {
    echo json_encode(['success' => false, 'message' => 'Kode work order tidak valid']);
    exit;
}

$kode_wo = $_POST['kode_wo'];
$no_service = $_POST['no_service'] ?? '';

try {
    // Check if waktu column exists in tbworkorderheader
    $waktu_column_exists = false;
    $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorderheader LIKE 'waktu'");
    if(mysqli_num_rows($check_waktu) > 0) {
        $waktu_column_exists = true;
    }

    // Get work order header info - include waktu if column exists
    if($waktu_column_exists) {
        $query_header = "SELECT kode_wo, nama_wo, keterangan, waktu, harga
                         FROM tbworkorderheader
                         WHERE kode_wo = ? AND status = '0'";
    } else {
        $query_header = "SELECT kode_wo, nama_wo, keterangan, harga
                         FROM tbworkorderheader
                         WHERE kode_wo = ? AND status = '0'";
    }
    $stmt_header = mysqli_prepare($koneksi, $query_header);
    mysqli_stmt_bind_param($stmt_header, "s", $kode_wo);
    mysqli_stmt_execute($stmt_header);
    $result_header = mysqli_stmt_get_result($stmt_header);
    
    if (mysqli_num_rows($result_header) == 0) {
        echo json_encode(['success' => false, 'message' => 'Work order tidak ditemukan atau tidak aktif']);
        exit;
    }
    
    $work_order = mysqli_fetch_assoc($result_header);
    
    // Get work order details (items)
    $query_details = "SELECT wd.kode_barang, wd.jumlah, wd.satuan, wd.harga, wd.total, wd.tipe,
                             CASE 
                                 WHEN wd.tipe = '1' THEN wh.nama_wo
                                 WHEN wd.tipe = '2' THEN i.namaitem
                                 ELSE wd.kode_barang
                             END as nama_item,
                             CASE
                                 WHEN wd.tipe = '1' AND ? = 1 THEN IFNULL(wh.waktu, 0)
                                 ELSE 0
                             END as waktu
                      FROM tbworkorderdetail wd
                      LEFT JOIN tbworkorderheader wh ON (wd.kode_barang = wh.kode_wo AND wd.tipe = '1')
                      LEFT JOIN tblitem i ON (wd.kode_barang = i.noitem AND wd.tipe = '2')
                      WHERE wd.kode_wo = ?
                      ORDER BY wd.tipe ASC, wd.kode_barang ASC";
    
    $stmt_details = mysqli_prepare($koneksi, $query_details);
    mysqli_stmt_bind_param($stmt_details, "is", $waktu_column_exists ? 1 : 0, $kode_wo);
    mysqli_stmt_execute($stmt_details);
    $result_details = mysqli_stmt_get_result($stmt_details);
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result_details)) {
        // For type '1' (jasa), make sure we get the correct service name and price
        if ($row['tipe'] == '1') {
            // Get service/jasa details
            if($waktu_column_exists) {
                $query_jasa = "SELECT nama_wo, harga, waktu FROM tbworkorderheader WHERE kode_wo = ?";
            } else {
                $query_jasa = "SELECT nama_wo, harga FROM tbworkorderheader WHERE kode_wo = ?";
            }
            $stmt_jasa = mysqli_prepare($koneksi, $query_jasa);
            mysqli_stmt_bind_param($stmt_jasa, "s", $row['kode_barang']);
            mysqli_stmt_execute($stmt_jasa);
            $result_jasa = mysqli_stmt_get_result($stmt_jasa);

            if ($jasa_data = mysqli_fetch_assoc($result_jasa)) {
                $row['nama_item'] = $jasa_data['nama_wo'];
                $row['harga'] = $jasa_data['harga'];
                $row['waktu'] = $waktu_column_exists ? ($jasa_data['waktu'] ?? 0) : 0;
                $row['total'] = $row['jumlah'] * $jasa_data['harga'];
            }
        }
        // For type '2' (barang), get the current item price
        elseif ($row['tipe'] == '2') {
            $query_barang = "SELECT namaitem, hargajual, satuan FROM tblitem WHERE noitem = ?";
            $stmt_barang = mysqli_prepare($koneksi, $query_barang);
            mysqli_stmt_bind_param($stmt_barang, "s", $row['kode_barang']);
            mysqli_stmt_execute($stmt_barang);
            $result_barang = mysqli_stmt_get_result($stmt_barang);
            
            if ($barang_data = mysqli_fetch_assoc($result_barang)) {
                $row['nama_item'] = $barang_data['namaitem'];
                $row['harga'] = $barang_data['hargajual'];
                $row['satuan'] = $barang_data['satuan'];
                $row['total'] = $row['jumlah'] * $barang_data['hargajual'];
            }
        }
        
        $items[] = $row;
    }
    
    // Check if this work order is already added to the service
    $already_added = false;
    if (!empty($no_service)) {
        $query_check = "SELECT COUNT(*) as count FROM tbservis_workorder WHERE no_service = ? AND kode_wo = ?";
        $stmt_check = mysqli_prepare($koneksi, $query_check);
        mysqli_stmt_bind_param($stmt_check, "ss", $no_service, $kode_wo);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $check_data = mysqli_fetch_assoc($result_check);
        $already_added = ($check_data['count'] > 0);
    }
    
    echo json_encode([
        'success' => true,
        'work_order_name' => $work_order['nama_wo'],
        'work_order_code' => $work_order['kode_wo'],
        'work_order_description' => $work_order['keterangan'],
        'work_order_time' => $waktu_column_exists ? ($work_order['waktu'] ?? 0) : 0,
        'work_order_price' => $work_order['harga'],
        'items' => $items,
        'already_added' => $already_added,
        'item_count' => count($items)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt_header)) mysqli_stmt_close($stmt_header);
    if (isset($stmt_details)) mysqli_stmt_close($stmt_details);
    if (isset($stmt_jasa)) mysqli_stmt_close($stmt_jasa);
    if (isset($stmt_barang)) mysqli_stmt_close($stmt_barang);
    if (isset($stmt_check)) mysqli_stmt_close($stmt_check);
}
?>