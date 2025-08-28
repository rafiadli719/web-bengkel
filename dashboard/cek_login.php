<?php 
	session_start();
	include '../config/koneksi.php';
 
	$txtnama = $_POST['txtnama'];
	$txtpass = $_POST['txtpass'];
	$cbocabang = $_POST['cbocabang'];

	$data = mysqli_query($koneksi,"SELECT * FROM tbuser 
                                    WHERE 
                                    nama_user='$txtnama' and password='$txtpass' AND 
                                    status_row='0'");
	$cek = mysqli_num_rows($data);
	if($cek > 0){		
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        id, user_akses 
                                        FROM tbuser 
                                        WHERE nama_user='$txtnama'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$id_user=$tm_cari['id'];
		$lvl_akses=$tm_cari['user_akses'];
        $_SESSION['_iduser']=$id_user;
        $_SESSION['_cabang']=$cbocabang;
        
        if($lvl_akses=='1') {
            header("location:_penjualan/index.php");			
        }

	} else {		
        $data = mysqli_query($koneksi,"SELECT * FROM tbdokter 
                                        WHERE 
                                        user_name='$txtnama' and user_password='$txtpass'");
        $cek = mysqli_num_rows($data);
        if($cek > 0){		
            $cari_kd=mysqli_query($koneksi,"SELECT 
                                            kode_dokter, nama_dokter 
                                            FROM tbdokter 
                                            WHERE user_name='$txtnama' and user_password='$txtpass'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $id_user=$tm_cari['kode_dokter'];
            $nm_user=$tm_cari['nama_dokter'];
            $_SESSION['_iduser']=$id_user;
            $_SESSION['_nmuser']=$nm_user;            
            header("location:_dokter/index.php");			
        } else {
            echo"<script>window.alert('Anda Belum Terdaftar!');window.location=('index.php');</script>";			            
        }    
	}
?>