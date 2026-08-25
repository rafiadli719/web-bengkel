<?php
	include "../config/koneksi.php";

        $sid = mysqli_real_escape_string($koneksi, $_GET['sid']);
        $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv']);
    
        $modal=mysqli_query($koneksi,"Delete 
                                    FROM tblservis_jasa 
                                    WHERE 
                                    id='$sid'");

            echo"<script>window.history.back();</script>";        
?>