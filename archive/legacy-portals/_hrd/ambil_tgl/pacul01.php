<?php  
    foreach($sheet as $row){
        $fld1 = $row['A'];
        $fld2 = $row['C'];
        $fld3 = $row['G'];
                                
        $query = "INSERT INTO tbabsensi_temp 
                (no_urut, id, waktu) 
                VALUES 
                ('".$fld1."','".$fld2."','".$fld3."')";

        mysqli_query($koneksi, $query); 
        $numrow++;
    }
    
?>