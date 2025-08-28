<?php
include "../config/koneksi.php";
$tipe_id = isset($_POST['tipe_id']) ? intval($_POST['tipe_id']) : 0;
$kategori = '';
if ($tipe_id) {
    $kategori_query = mysqli_query($koneksi, "SELECT tm.kode_kategori, tkm.kategori 
                                             FROM tbtipe_motor tm 
                                             LEFT JOIN tbkategori_motor tkm ON tm.kode_kategori = tkm.id 
                                             WHERE tm.kode_tipe = '$tipe_id'");
    if ($row = mysqli_fetch_array($kategori_query)) {
        $kategori = $row['kategori'];
    }
}
echo $kategori;
mysqli_close($koneksi);
?>