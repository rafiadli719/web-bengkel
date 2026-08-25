<?php
	include "../config/koneksi.php";

        $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
        $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv']);
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_barang 
                                    WHERE 
                                    id='$sid'");

            $kdbrg="";
            $kdjasa="";
            echo"<script>window.location=('servis-reguler-byr.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>