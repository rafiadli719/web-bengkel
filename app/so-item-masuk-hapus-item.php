<?php
	include "../config/koneksi.php";

	$sid = mysqli_real_escape_string($koneksi, $_GET['sid']);    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbitem_masuk_detail 
                                    WHERE 
                                    id='$sid'");
    echo"<script>window.location=('so-item-masuk-rst.php');</script>";			            
?>