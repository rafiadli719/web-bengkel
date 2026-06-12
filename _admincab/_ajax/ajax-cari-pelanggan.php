<?php
session_start();
include "../../config/koneksi.php";
require_once "../_include_customer_vehicle_sync.php";
require_once "../_include_kategori_member.php";

header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

try {
    $kode_pelanggan = trim((string) ($_POST['kode_pelanggan'] ?? ''));
    
    if(empty($kode_pelanggan)) {
        echo json_encode(array('success' => false, 'message' => 'Kode pelanggan tidak boleh kosong'));
        exit;
    }
    
    $like = '%' . fitmotorEscapeLike($koneksi, $kode_pelanggan) . '%';
    $query = "SELECT p.nopelanggan, p.namapelanggan, p.alamat, p.telephone, p.kgrup,
                     pg.grup, sp.status_member, sp.diskon_persen
              FROM tblpelanggan p
              LEFT JOIN tblpelanggangrup pg ON pg.kgrup = p.kgrup
              LEFT JOIN statistik_pelanggan sp ON sp.no_pelanggan = p.nopelanggan
              WHERE p.nopelanggan LIKE ? OR p.namapelanggan LIKE ? OR p.telephone LIKE ?
              ORDER BY
                CASE WHEN p.nopelanggan = ? THEN 0 ELSE 1 END,
                CASE WHEN p.namapelanggan = ? THEN 0 ELSE 1 END,
                p.namapelanggan ASC
              LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'sssss', $like, $like, $like, $kode_pelanggan, $kode_pelanggan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($result && mysqli_num_rows($result) > 0) {
        $pelanggan = mysqli_fetch_array($result);
        echo json_encode(array(
            'success' => true,
            'kode_pelanggan' => $pelanggan['nopelanggan'],
            'nama_pelanggan' => $pelanggan['namapelanggan'],
            'alamat' => $pelanggan['alamat'],
            'telepon' => $pelanggan['telephone'],
            'kgrup' => $pelanggan['kgrup'],
            'grup' => $pelanggan['grup'],
            'status_member' => $pelanggan['status_member'] ?: fitmotorMapGrupNameToTier($pelanggan['grup']),
            'diskon_persen' => $pelanggan['diskon_persen'] ?? 0
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Pelanggan tidak ditemukan'));
    }
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}
?>
