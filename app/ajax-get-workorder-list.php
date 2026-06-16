<?php
/**
 * AJAX: GET WORKORDER LIST
 * ============================================================================
 * Mengambil daftar Work Order untuk dropdown picker
 * Return: JSON array of {kode_wo, nama_wo, harga}
 * ============================================================================
 */

session_start();
if(empty($_SESSION['_iduser'])) {
    echo json_encode([]);
    exit;
}

include "../config/koneksi.php";

header('Content-Type: application/json');

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';

$where = "";
if($search) {
    $where = "WHERE kode_wo LIKE '%$search%' OR nama_wo LIKE '%$search%'";
}

$query = mysqli_query($koneksi, "SELECT kode_wo, nama_wo, harga, waktu 
                                 FROM tbworkorderheader 
                                 $where
                                 ORDER BY nama_wo ASC
                                 LIMIT 200");

$result = [];
if($query) {
    while($row = mysqli_fetch_assoc($query)) {
        $result[] = [
            'kode_wo' => $row['kode_wo'],
            'nama_wo' => $row['nama_wo'],
            'harga' => floatval($row['harga']),
            'waktu' => intval($row['waktu'])
        ];
    }
}

echo json_encode($result);
?>
