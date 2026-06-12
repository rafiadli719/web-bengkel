<?php
	include "../config/koneksi.php";

        $sid = $_GET['sid'];
        $no_service = $_GET['snoserv'];
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_barang 
                                    WHERE 
                                    id='$sid'");

            echo"<script>window.history.back();</script>";        
?>