<?php
    include "../config/koneksi.php";
    
	date_default_timezone_set('Asia/Jakarta');
	$waktuaja_skr=date('h:i');
	function ubahformatTgl($tanggal) {
		$pisah = explode('/',$tanggal);
		$urutan = array($pisah[2],$pisah[1],$pisah[0]);
		$satukan = implode('-',$urutan);
		return $satukan;
	}
    
	$txttgl = ubahformatTgl($_POST['id-date-picker-1']); 
	$txtcabang= $_POST['txtcabang'];
    $txtuser= $_POST['txtuser']; 
	$jam= $_POST['timepicker1']; 
    $total= $_POST['total'];

    include "function_kasir.php";
    $LastID=FormatNoTrans(OtomatisID());	
            
    $cari_kd=mysqli_query($koneksi,"SELECT count(keping) as tot FROM tbkeping");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $tot_keping=$tm_cari['tot'];				                            
  
    $no = 0 ;
    $sql = mysqli_query($koneksi,"SELECT * FROM tbkeping order by keping desc");
    while ($tampil = mysqli_fetch_array($sql)) {
        $no++;
        $nominal=$tampil['keping'];
        
        $keping="keping".$no;
        $jml_keping=$_POST[$keping];
        
        $nilai=$nominal*$jml_keping;
        
        mysqli_query($koneksi,"INSERT INTO tbkas_kasir_detail 
                        (no_bukti, nominal, jml_keping, nilai) 
                        VALUES 
                        ('$LastID','$nominal','$jml_keping','$nilai')");        
    }

    mysqli_query($koneksi,"INSERT INTO tbkas_kasir_header 
                        (no_bukti, tanggal, jam, kd_cabang, user, tipe, total) 
                        VALUES 
                        ('$LastID', '$txttgl','$jam','$txtcabang','$txtuser','1','$total')");
        
    echo"<script>window.alert('Pengisian Kas Awal Berhasil disimpan!');
    window.location=('index.php');</script>";                        
?>