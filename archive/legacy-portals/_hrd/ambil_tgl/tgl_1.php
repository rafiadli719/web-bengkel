<?php  
        $tgl_absen=$tahun_absen."/".$bln_absen."/01";
        $tgl1 = $row['A'];
        if($tgl1<>'') {
            $panjang_data=strlen($tgl1);
            
            $tgl1_msk=substr($tgl1,0,5);
            if($panjang_data > "10") {
                $tgl1_plg=right($tgl1,5);
            } else {
                $tgl1_plg=substr($tgl1,5,10);                
            }
            
            $data = mysqli_query($koneksi,"SELECT * FROM tbabsensi 
                                            WHERE 
                                            tgl='$tgl_absen' and 
                                            nip='$id_absen'and 
                                            kode_lokasi='$cbocabang'");
            $cek = mysqli_num_rows($data);
            if($cek <= 0){		
                $query = "INSERT INTO tbabsensi 
                                (tgl, nip, nip_di_finger, jam_masuk, jam_keluar, kode_status_kehadiran, kode_lokasi) 
                                VALUES 
                                ('".$tgl_absen."','".$id_absen."','".$id_absen."','".$tgl1_msk."','".$tgl1_plg."','1','".$cbocabang."')";
                mysqli_query($koneksi, $query);   
                $jml_data++;
            }        
        }        
?>