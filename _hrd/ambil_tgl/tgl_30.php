<?php  
        $tgl_absen=$tahun_absen."/".$bln_absen."/30";
        $tgl30 = $row['AD']; 
        if($tgl30<>'') {
            $tgl30_msk=substr($tgl30,0,5);
            $tgl30_plg=right($tgl30,5);
            
            $data = mysqli_query($koneksi,"SELECT * FROM tbabsensi 
                                            WHERE 
                                            tgl='$tgl_absen' and 
                                            nip='$id_absen' and 
                                            kode_lokasi='$cbocabang'");
            $cek = mysqli_num_rows($data);
            if($cek <= 0){		            
                // Buat query Insert
                $query = "INSERT INTO tbabsensi 
                                (tgl, nip, nip_di_finger, jam_masuk, jam_keluar, kode_status_kehadiran, kode_lokasi) 
                                VALUES 
                                ('".$tgl_absen."','".$id_absen."','".$id_absen."','".$tgl30_msk."','".$tgl30_plg."','1','".$cbocabang."')";

                // Eksekusi $query
                mysqli_query($koneksi, $query);
                                $jml_data++;
            }
        }
?>