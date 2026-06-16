<?php
    include "../config/koneksi.php";
    
	date_default_timezone_set('Asia/Jakarta');
    $thn_skr=date('Y');
	$waktuaja_skr=date('h:i');
    $thn=substr($thn_skr,2,2);

	function ubahformatTgl($tanggal) {
		$pisah = explode('/',$tanggal);
		$urutan = array($pisah[2],$pisah[1],$pisah[0]);
		$satukan = implode('-',$urutan);
		return $satukan;
	}
    
	$txttgl = ubahformatTgl($_POST['id-date-picker-1']); 
    $txttgl_indo = $_POST['id-date-picker-1'];
	$txtcabang= $_POST['txtcabang'];
    $txtuser= $_POST['txtuser']; 
	$jam= $_POST['timepicker1'];             
    $total= $_POST['total'];
    $total_awal= $_POST['total_awal'];
    $total_real= $_POST['total_real'];    
    
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
                        (no_bukti, tanggal, jam, kd_cabang, user, tipe, 
                        total, total_awal, total_real) 
                        VALUES 
                        ('$LastID', '$txttgl','$jam','$txtcabang','$txtuser','2',
                        '$total','$total_awal','$total_real')");
        
    // Buat ke tabel penerimaan kas --------
                $querycount="SELECT 
                            count(kode_km) as LastID FROM tblkas_keluar_masuk 
                            WHERE 
                            year(tanggal)='$thn_skr'";	
                $result=mysqli_query($koneksi,$querycount) or die(mysql_error());
                $row=mysqli_fetch_array($result);
                $jmlrow=$row['LastID']+1;

                if($jmlrow<10) {
                    $no_km="KM".$thn."00000000".$jmlrow;    
                } else {
                    if($jmlrow<100) {
                        $no_km="KM".$thn."0000000".$jmlrow;    
                    } else {
                        if($jmlrow<1000) {
                            $no_km="KM".$thn."0000000".$jmlrow;    
                        } else {
                            $no_km="KM".$thn."000000".$jmlrow;                    
                        }            
                    }        
                }

    $ket="Closing Tgl. ".$txttgl_indo;
    $jml_setor=$total+$total_awal;
    
    mysqli_query($koneksi,"INSERT INTO tblkas_keluar_masuk 
                        (tanggal, kode_km, kd_cabang, user, jenis, 
                        uraian, masuk, kode_akun) 
                        VALUES 
                        ('$txttgl','$LastID','$txtcabang','$txtuser','Masuk',
                        '$ket','$jml_setor','KAS2')");
    
    echo"<script>window.alert('Pengisian Kas Akhir Berhasil disimpan!');
    window.location=('index.php');</script>";                            
?>