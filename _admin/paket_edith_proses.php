<?php
	include "../config/koneksi.php";
	    
	$txtkode= $_POST['txtkode'];
	$txtnamawo= $_POST['txtnamawo'];
    $txtwaktu= $_POST['txtwaktu'];
    $txtnote= $_POST['txtnote'];
    $txtharga= $_POST['txtharga'];
    
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