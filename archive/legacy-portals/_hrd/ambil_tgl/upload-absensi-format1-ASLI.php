<?php  
foreach($sheet as $row){
    if($numrow=='3') {
        
        // ini buat ngambil tanggal
        $fld1 = $row['C'];
        
        // ambil tahun
        $tahun_absen=substr($fld1,0,4);
        // ambil bulan
        $bln_absen=substr($fld1,5,2);

        // ambil tgl pertama di tanggal apakah 0 atau 1,2,3
        $bln_absen_cek=substr($fld1,5,1);
        if($bln_absen_cek=='0') {
            $bln_absen_cek=substr($fld1,6,1);            
            $data = mysqli_query($koneksi,"SELECT tgl FROM tbabsensi 
                                WHERE 
                                month(tgl)='$bln_absen_cek' AND 
                                year(tgl)='$tahun_absen' AND 
                                kode_lokasi='$cbocabang'");
            $cek = mysqli_num_rows($data);
            if($cek > 0){		
                $isi="1";
                //echo"<script>window.alert('Data Absensi sudah Ada atau Pernah di Upload!');
                //window.location=('absensi_upload.php');</script>";
            } else {
                $isi="0";
            }
        } else {
            $bln_absen_cek=substr($fld1,5,2);
            $data = mysqli_query($koneksi,"SELECT tgl FROM tbabsensi 
                                WHERE 
                                month(tgl)='$bln_absen_cek' and 
                                year(tgl)='$tahun_absen' AND 
                                kode_lokasi='$cbocabang'");
            $cek = mysqli_num_rows($data);
            if($cek > 0){		
                $isi="1";
                //echo"<script>window.alert('Data Absensi sudah Ada atau Pernah di Upload!');
                //window.location=('absensi_upload.php');</script>";
            } else {
                $isi="0";
            }
        }
    }
    
    //// ini buat ngambil Data Pegawai (26 Pegawai)
    //include "ambil_tgl/data_pegawai_10.php";
   
    $numrow++;
}


    if($isi=='1') {
        echo"<script>window.alert('Data Absensi sudah Ada atau Pernah di Upload!');
        window.location=('absensi_upload.php');</script>";        
    } else {
        $numrow = 1;
        foreach($sheet as $row){
            if($numrow=='3') {        
                // ini buat ngambil tanggal
                $fld1 = $row['C'];        
                // ambil tahun
                $tahun_absen=substr($fld1,0,4);
                // ambil bulan
                $bln_absen=substr($fld1,5,2);
            }
            
            // ini buat ngambil Data Pegawai (26 Pegawai)
            include "ambil_tgl/data_pegawai_10.php";
           
            $numrow++;
        }        
        echo"<script>window.alert('Proses Upload Absensi telah berhasil!');
        window.location=('absensi_upload.php');</script>";        
    }
    ?>