<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";
$tipe_id = isset($_POST['tipe_id']) ? intval($_POST['tipe_id']) : 0;
$kategori = '';

if ($tipe_id) {
    // Menggunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($koneksi, "SELECT tm.kode_kategori, tkm.kategori
                                      FROM tbtipe_motor tm
                                      LEFT JOIN tbkategori_motor tkm ON tm.kode_kategori = tkm.id
                                      WHERE tm.kode_tipe = ?");
    mysqli_stmt_bind_param($stmt, "i", $tipe_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_array($result)) {
        $kategori = $row['kategori'] ?? '';
    }
    mysqli_stmt_close($stmt);
}

echo $kategori;
mysqli_close($koneksi);
?>