<?php
function OtomatisID()
{
    include "../config/koneksi.php";
	$querycount="SELECT count(id_upload) as LastID FROM tbabsensi_upload";
	$result=mysqli_query($koneksi,$querycount) or die(mysql_error());
	$row=mysqli_fetch_array($result);
	return $row['LastID'];
}

function FormatNoTrans($num) {
        $num=$num+1; switch (strlen($num))
        {                
        case 1 : $NoTrans = "UPLO000".$num; break;    
        case 2 : $NoTrans = "UPL000".$num; break;    
        case 3 : $NoTrans = "UPL00".$num; break;            
        case 4 : $NoTrans = "UPL0".$num; break;
        case 5 : $NoTrans = "UPL".$num; break;        
        default: $NoTrans = $num;          
        }          
        return $NoTrans;
}
?>