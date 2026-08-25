<?php
	include "../config/koneksi.php";
	    
	$txtkode= mysqli_real_escape_string($koneksi, $_POST['txtkode']);
	$txtnamawo= mysqli_real_escape_string($koneksi, $_POST['txtnamawo']);
    $txtwaktu= mysqli_real_escape_string($koneksi, $_POST['txtwaktu']);
    $txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);
    $txtharga= mysqli_real_escape_string($koneksi, $_POST['txtharga']);
    
	mysqli_query($koneksi,"UPDATE 
                            tbworkorderheader 
                            SET 
                            nama_wo='$txtnamawo', keterangan='$txtnote', 
                            waktu='$txtwaktu', harga='$txtharga' 
                            WHERE 
                            kode_wo='$txtkode'");
								
	echo"<script>window.alert('Data Paket Service/Work Order berhasil di update!');
    window.location=('paket.php');</script>";
?>