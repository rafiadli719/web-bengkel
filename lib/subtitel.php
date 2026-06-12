<?php
// Ambil nama perusahaan dari database
if(isset($koneksi)) {
    $query_perusahaan = mysqli_query($koneksi, "SELECT nama_singkat FROM master_perusahaan WHERE is_aktif=1 ORDER BY id DESC LIMIT 1");
    if($query_perusahaan && mysqli_num_rows($query_perusahaan) > 0) {
        $data_perusahaan = mysqli_fetch_assoc($query_perusahaan);
        $nama_singkat = trim($data_perusahaan['nama_singkat']);
        if(!empty($nama_singkat)) {
            echo strtoupper($nama_singkat);
        } else {
            echo 'FIT MOTOR';
        }
    } else {
        echo 'FIT MOTOR';
    }
} else {
    echo 'FIT MOTOR';
}
?>