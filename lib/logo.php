<?php
/**
 * Helper untuk menampilkan logo perusahaan secara dinamis
 * Akan menampilkan logo dari database master_perusahaan
 * Jika tidak ada logo, akan menampilkan icon default
 */

if(isset($koneksi)) {
    $query_perusahaan = mysqli_query($koneksi, "SELECT logo, nama_singkat FROM master_perusahaan WHERE is_aktif=1 ORDER BY id DESC LIMIT 1");
    
    if($query_perusahaan && mysqli_num_rows($query_perusahaan) > 0) {
        $data_perusahaan = mysqli_fetch_assoc($query_perusahaan);
        $logo = trim($data_perusahaan['logo']);
        $nama_singkat = trim($data_perusahaan['nama_singkat']);
        
        // Jika ada logo dan file exists
        if(!empty($logo) && file_exists("../" . $logo)) {
            echo '<img src="../' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($nama_singkat) . '" style="height: 24px; vertical-align: middle; margin-right: 5px;">';
        } else {
            // Fallback ke icon default
            echo '<i class="fa fa-leaf"></i>';
        }
    } else {
        // Fallback ke icon default
        echo '<i class="fa fa-leaf"></i>';
    }
} else {
    // Fallback ke icon default
    echo '<i class="fa fa-leaf"></i>';
}
?>
