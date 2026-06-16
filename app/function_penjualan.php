<?php
function OtomatisID()
{
    include "../config/koneksi.php";
	$querycount="SELECT count(notransaksi) as LastID FROM tblpenjualan_header";
	$result=mysqli_query($koneksi,$querycount) or die(mysql_error());
	$row=mysqli_fetch_array($result);
	return $row['LastID'];
}

function FormatNoTrans($num) {
            $thn_skr=date('Y');
            $thn=substr($thn_skr,2,2);
        $num=$num+1; switch (strlen($num))
        {                
        case 1 : $NoTrans = "JL".$thn."00000000".$num; break;    
        case 2 : $NoTrans = "JL".$thn."0000000".$num; break;    
        case 3 : $NoTrans = "JL".$thn."000000".$num; break;            
        case 4 : $NoTrans = "JL".$thn."00000".$num; break;                    
        case 5 : $NoTrans = "JL".$thn."0000".$num; break;
        case 6 : $NoTrans = "JL".$thn."000".$num; break;
        case 7 : $NoTrans = "JL".$thn."00".$num; break;
        case 8 : $NoTrans = "JL".$thn."0".$num; break;
        case 9 : $NoTrans = "JL".$thn.$num; break;
        default: $NoTrans = $num;          
        }          
        return $NoTrans;
}
?>