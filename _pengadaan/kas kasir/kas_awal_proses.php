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
            
    $keping1= $_POST['keping1'];
    $keping2= $_POST['keping2'];
    $keping3= $_POST['keping3'];
    $keping4= $_POST['keping4'];
    $keping5= $_POST['keping5'];
    $keping6= $_POST['keping6'];
    $keping7= $_POST['keping7'];
    $keping8= $_POST['keping8'];
    $keping9= $_POST['keping9'];
    $keping10= $_POST['keping10'];

    $total= $_POST['total'];
    

	$data = mysqli_query($koneksi,"SELECT * FROM tbkas_kasir 
                                    WHERE 
                                    tanggal='$txttgl' and user='$txtuser' AND 
                                    tipe='1'");
	$cek = mysqli_num_rows($data);
	if($cek > 0){		
        echo"<script>window.alert('Pengisian Kas Awal sudah pernah dibuat sebelumnya!');
        window.location=('index.php');</script>";                        
    } else {
        mysqli_query($koneksi,"INSERT INTO tbkas_kasir 
                        (tanggal, jam, kd_cabang, user, tipe, 
                        keping_1, keping_2, keping_3, 
                        keping_4, keping_5, keping_6, 
                        keping_7, keping_8, keping_9, 
                        keping_10, total) 
                        VALUES 
                        ('$txttgl','$jam','$txtcabang','$txtuser','1',
                        '$keping1','$keping2','$keping3',
                        '$keping4','$keping5','$keping6',
                        '$keping7','$keping8','$keping9',
                        '$keping10','$total')");
                          
        echo"<script>window.alert('Pengisian Kas Awal Berhasil disimpan!');
        window.location=('index.php');</script>";                
    }
        
        

?>