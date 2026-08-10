<?php  
    $sql = mysqli_query($koneksi,"SELECT * from tbabsensi_temp_rst");
    while ($tampil = mysqli_fetch_array($sql)) {
        $nip=$tampil['id'];
        $tgl=$tampil['tgl'];
        $jam_masuk=$tampil['jam_masuk'];
        $Jam_pulang=$tampil['Jam_pulang'];
        
        $data = mysqli_query($koneksi,"SELECT * FROM tbabsensi 
                                            WHERE 
                                            tgl='$tgl' and 
                                            nip='$nip'and 
                                            kode_lokasi='$cbocabang'");
        $cek = mysqli_num_rows($data);
        if($cek <= 0){		
            $query = "INSERT INTO tbabsensi 
                        (nip, tgl, jam_masuk, jam_keluar, kode_status_kehadiran, kode_lokasi) 
                        VALUES 
                        ('".$nip."','".$tgl."','".$jam_masuk."','".$Jam_pulang."','1','".$cbocabang."')";

            mysqli_query($koneksi, $query);
            $jml_data++;
        }                
    }
?>