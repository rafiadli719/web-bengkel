<?php  
    $query = "DELETE FROM tbabsensi_temp";
    mysqli_query($koneksi, $query); 

    $query = "DELETE FROM tbabsensi_temp_rst";
    mysqli_query($koneksi, $query); 
?>