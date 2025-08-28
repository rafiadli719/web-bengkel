<?php
function OtomatisID()
{
    include "../config/koneksi.php";
	$querycount="SELECT count(kode_wo) as LastID FROM tbworkorderheader";
	$result=mysqli_query($koneksi,$querycount) or die(mysql_error());
	$row=mysqli_fetch_array($result);
	return $row['LastID'];
}

function FormatNoTrans($num) {
        $num=$num+1; switch (strlen($num))
        {                
        case 1 : $NoTrans = "WO000".$num; break;    
        case 2 : $NoTrans = "WOS00".$num; break;    
        case 3 : $NoTrans = "WO0".$num; break;            
        case 4 : $NoTrans = "WO".$num; break;
        default: $NoTrans = $num;          
        }          
        return $NoTrans;
}
?>