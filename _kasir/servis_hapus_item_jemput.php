<?php
	include "../config/koneksi.php";

        $sid = $_GET['sid'];
        $no_service = $_GET['snoserv'];
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_barang 
                                    WHERE 
                                    id='$sid'");

            $kdbrg="";
            $kdjasa="";
            echo"<script>window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$kdbrg&kdjasa=$kdjasa');</script>";        
?>