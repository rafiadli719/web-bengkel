<?php
    include "../config/koneksi.php";
    $txtnobyr= mysqli_real_escape_string($koneksi, $_POST['txtnobyr']); 

    $jumlah=count($_POST["hapus"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=mysqli_real_escape_string($koneksi, $_POST["hapus"][$i]);
        mysqli_query($koneksi,"INSERT INTO tblhutang_detail 
                                (no_transaksi, no_pembelian) 
                                VALUES 
                                ('$txtnobyr','$nip')");
    }
    echo"<script>window.location=('pmby_hutang_add_next1.php?nobyr=$txtnobyr');</script>";        
?>