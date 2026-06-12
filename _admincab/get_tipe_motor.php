<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";
$merek_id = isset($_POST['merek_id']) ? intval($_POST['merek_id']) : 0;
$response = '<option value="">- Pilih Tipe -</option>';

if ($merek_id) {
    // Menggunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($koneksi, "SELECT kode_tipe, tipe FROM tbtipe_motor WHERE kode_pabrik = ? ORDER BY tipe");
    mysqli_stmt_bind_param($stmt, "i", $merek_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_array($result)) {
        $response .= "<option value='{$row['kode_tipe']}'>{$row['tipe']}</option>";
    }
    mysqli_stmt_close($stmt);
}

echo $response;
mysqli_close($koneksi);
?>