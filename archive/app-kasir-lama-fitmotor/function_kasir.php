<?php
function OtomatisID()
{
    include "../config/koneksi.php";
	$querycount="SELECT count(no_bukti) as LastID FROM tbkas_kasir_header";
	$result=mysqli_query($koneksi,$querycount) or die(mysql_error());
	$row=mysqli_fetch_array($result);
	return $row['LastID'];
}

function FormatNoTrans($num) {
            $thn_skr=date('Y');
            $thn=substr($thn_skr,2,2);
        $num=$num+1; switch (strlen($num))
        {                
        case 1 : $NoTrans = "KSR".$thn."0000000".$num; break;    
        case 2 : $NoTrans = "KSR".$thn."000000".$num; break;    
        case 3 : $NoTrans = "KSR".$thn."00000".$num; break;            
        case 4 : $NoTrans = "KSR".$thn."0000".$num; break;                    
        case 5 : $NoTrans = "KSR".$thn."000".$num; break;
        case 6 : $NoTrans = "KSR".$thn."00".$num; break;
        case 7 : $NoTrans = "KSR".$thn."0".$num; break;
        case 8 : $NoTrans = "KSR".$thn.$num; break;
        default: $NoTrans = $num;          
        }          
        return $NoTrans;
}
?>