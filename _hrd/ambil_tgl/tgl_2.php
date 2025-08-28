<?php  
        $tgl_absen=$tahun_absen."/".$bln_absen."/02";
        $tgl2 = $row['B']; 
        if($tgl2<>'') {
            //$panjang_data=strlen($tgl2);
            
            $tgl2_msk=substr($tgl2,0,5);
            //if($panjang_data > "10") {
                $tgl2_plg=right($tgl2,5);
            //} else {
            //    $tgl2_plg=substr($tgl2,5,10);                
            //}
                        
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
                                ('".$tgl_absen."','".$id_absen."','".$id_absen."','".$tgl2_msk."','".$tgl2_plg."','1','".$cbocabang."')";
                mysqli_query($koneksi, $query);
                                $jml_data++;
            }
        }
?>