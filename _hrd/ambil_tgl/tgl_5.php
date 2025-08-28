<?php  
        $tgl_absen=$tahun_absen."/".$bln_absen."/05";
        $tgl5 = $row['E']; 
        if($tgl5<>'') {
            $tgl5_msk=substr($tgl5,0,5);
            $tgl5_plg=right($tgl5,5);
            
            $data = mysqli_query($koneksi,"SELECT * FROM tbabsensi 
                                            WHERE 
                                            tgl='$tgl_absen' and 
                                            nip='$id_absen' and 
                                            kode_lokasi='$cbocabang'");
            $cek = mysqli_num_rows($data);
            if($cek <= 0){		
                $query = "INSERT INTO tbabsensi 
                                (tgl, nip, nip_di_finger, jam_masuk, jam_keluar, kode_status_kehadiran, kode_lokasi) 
                                VALUES 
                                ('".$tgl_absen."','".$id_absen."','".$id_absen."','".$tgl5_msk."','".$tgl5_plg."','1','".$cbocabang."')";
                mysqli_query($koneksi, $query);
                                $jml_data++;
            }
        }
?>