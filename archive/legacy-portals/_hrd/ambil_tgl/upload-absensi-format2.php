<?php  
	date_default_timezone_set('Asia/Jakarta');
	$waktuaja_skr=date('h:i');
    $tanggal_skr=date('Y/m/d');
    
    // Tarik data dan update tgl & waktu
    include "ambil_tgl/clear_data.php";
    include "ambil_tgl/pacul01.php";
    include "ambil_tgl/pacul02.php";

    // Proses Berikutnya    
    include "ambil_tgl/pacul03.php";
    
    $jml_data = 0;
    include "ambil_tgl/pacul04.php";

    if($jml_data=='0') {
        echo"<script>window.alert('Data Absensi sudah ada. Tidak Ada Data Baru yang ditambahkan');
        window.location=('absensi_upload.php');</script>";                           
    } else {
        include "function_absensi_upload.php";
        $LastID=FormatNoTrans(OtomatisID());		

        $query = "UPDATE tbabsensi 
                SET id_upload='".$LastID."' 
                WHERE id_upload=''";
        mysqli_query($koneksi, $query);
                
        $cari_kd=mysqli_query($koneksi,"SELECT tgl FROM tbabsensi 
                                            WHERE id_upload='".$LastID."' 
                                            AND nip<>'0' 
                                            ORDER BY tgl asc limit 1");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $tgl_awal=$tm_cari['tgl'];
            
        $cari_kd=mysqli_query($koneksi,"SELECT tgl FROM tbabsensi 
                                            WHERE id_upload='".$LastID."' 
                                            ORDER BY tgl desc limit 1");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $tgl_akhir=$tm_cari['tgl'];        
            
        $query = "INSERT INTO tbabsensi_upload 
                        (id_upload, tgl_upload, waktu_upload, 
                        tgl_absensi_awal, tgl_absensi_akhir, kode_cabang) 
                        VALUES 
                        ('".$LastID."','".$tanggal_skr."','".$waktuaja_skr."',
                        '".$tgl_awal."','".$tgl_akhir."','".$cbocabang."')";

        mysqli_query($koneksi, $query);
                
        echo"<script>window.alert('Proses Upload Absensi telah berhasil!');
        window.location=('absensi_upload.php');</script>";                
    }
?>