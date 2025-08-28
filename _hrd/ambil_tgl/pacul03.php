<?php
    $sql = mysqli_query($koneksi,"SELECT distinct(id) as nip from tbabsensi_temp");
    while ($tampil = mysqli_fetch_array($sql)) {
        $nip=$tampil['nip'];

        $sql1 = mysqli_query($koneksi,"SELECT distinct(tgl) as tanggal 
                                        from tbabsensi_temp where id='$nip' 
                                        order by no_urut asc");
            while ($tampil1 = mysqli_fetch_array($sql1)) {
                $tanggal=$tampil1['tanggal'];

                $cari_kd=mysqli_query($koneksi,"SELECT no_urut, tgl, jam FROM tbabsensi_temp 
                                    WHERE id='$nip' and tgl='$tanggal' 
                                    order by no_urut asc limit 1");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $jam_masuk=$tm_cari['jam'];				                                                                

                $cari_kd=mysqli_query($koneksi,"SELECT no_urut, tgl, jam FROM tbabsensi_temp 
                                    WHERE id='$nip' and tgl='$tanggal' 
                                    order by no_urut desc limit 1");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $jam_pulang=$tm_cari['jam'];				                                                                
 
               $query = "INSERT INTO tbabsensi_temp_rst 
                        (id, tgl, jam_masuk, Jam_pulang) 
                        VALUES 
                        ('".$nip."','".$tanggal."','".$jam_masuk."','".$jam_pulang."')";

                mysqli_query($koneksi, $query);         
               
        }
    }
?>   