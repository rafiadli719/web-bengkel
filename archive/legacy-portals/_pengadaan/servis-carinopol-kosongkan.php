<?php
	include "../config/koneksi.php";

    $no_service = $_GET['snoserv'];
    
    // Hapus Tabel Keluhan
    $modal=mysqli_query($koneksi,"Delete 
                                    FROM tbservis_keluhan 
                                    WHERE 
                                    no_service='$no_service'");

    // Hapus Tabel Item Pengerjaan
    $modal=mysqli_query($koneksi,"Delete 
                                    FROM tbservis_pengerjaan 
                                    WHERE 
                                    no_service='$no_service'");

    // Hapus Tabel Item Barang
    $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_barang 
                                    WHERE 
                                    no_service='$no_service'");

    // Hapus Tabel Item Paket
    $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_jasa 
                                    WHERE 
                                    no_service='$no_service'");
                                    
            $kdbrg="";
            $kdjasa="";
            echo"<script>window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>