<?php
	include "../config/koneksi.php";

	$sid = $_GET['sid'];    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_keluar_detail 
                                    WHERE 
                                    id='$sid'");
    echo"<script>window.location=('so-item-keluar-rst.php');</script>";			            
?>