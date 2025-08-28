<?php
    include "../config/koneksi.php";
    $txtnobyr= $_POST['txtnobyr']; 

    $jumlah=count($_POST["hapus"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus"][$i];
        mysqli_query($koneksi,"INSERT INTO tblpiutang_detail 
                                (no_transaksi, no_penjualan) 
                                VALUES 
                                ('$txtnobyr','$nip')");
    }
    echo"<script>window.location=('pmby_piutang_add_next1.php?nobyr=$txtnobyr');</script>";        
?>