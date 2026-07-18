<?php
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

$search_query = trim((string) ($_POST['txtsearch'] ?? ''));

if (empty($search_query)) {
    echo json_encode(array('success' => true, 'data' => array()));
    exit;
}

$like = '%' . mysqli_real_escape_string($koneksi, $search_query) . '%';
$sql = "SELECT nopolisi, pemilik, tipe, jenis, warna, merek, telephone, alamat
        FROM view_pelanggan_kendaraan
        WHERE (nopolisi LIKE '$like') OR (pemilik LIKE '$like') OR (telephone LIKE '$like')
        ORDER BY pemilik ASC
        LIMIT 20";
$result = mysqli_query($koneksi, $sql);

$data = array();
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
}

echo json_encode(array('success' => true, 'data' => $data));
