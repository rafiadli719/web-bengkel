<?php 
    $sql = mysqli_query($koneksi,"SELECT 
                                    no_urut, date(waktu) as tanggalan, time(waktu) as waktuan 
                                    FROM tbabsensi_temp where id<>0");
    while ($tampil = mysqli_fetch_array($sql)) {
        $no_urut=$tampil['no_urut'];
        $tanggalan=$tampil['tanggalan'];
        $waktuan=$tampil['waktuan'];
        
        $query = "UPDATE tbabsensi_temp 
                SET tgl='".$tanggalan."', jam='".$waktuan."' 
                WHERE no_urut='".$no_urut."'";

        mysqli_query($koneksi, $query); 
    }
?>