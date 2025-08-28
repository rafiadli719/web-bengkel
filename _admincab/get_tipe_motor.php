<?php
include "../config/koneksi.php";
$merek_id = isset($_POST['merek_id']) ? intval($_POST['merek_id']) : 0;
$response = '<option value="">- Pilih Tipe -</option>';
if ($merek_id) {
    $tipe_query = mysqli_query($koneksi, "SELECT kode_tipe, tipe FROM tbtipe_motor WHERE kode_pabrik = '$merek_id' ORDER BY tipe");
    while ($row = mysqli_fetch_array($tipe_query)) {
        $response .= "<option value='{$row['kode_tipe']}'>{$row['tipe']}</option>";
    }
}
echo $response;
mysqli_close($koneksi);
?>