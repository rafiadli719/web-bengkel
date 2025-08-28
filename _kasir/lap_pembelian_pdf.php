<?php
    include "../config/koneksi.php";
                
    date_default_timezone_set('Asia/Jakarta');
    $waktuaja_skr=date('h:i');
    function ubahformatTgl($tanggal) {
        $pisah = explode('/',$tanggal);
        $urutan = array($pisah[2],$pisah[1],$pisah[0]);
        $satukan = implode('-',$urutan);
        return $satukan;
    }
                
	$tgl_pilih_dari= $_GET['stgl1'];
	$tgl_pilih_sampai= $_GET['stgl2'];	
	$no_supplier= $_GET['ssup'];	
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            if($no_supplier=='') {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pembelian_header 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') 
                                    ORDER BY tanggal, notransaksi";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pembelian_header 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             
            } else {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pembelian_header 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                                    no_supplier='$no_supplier' 
                                    ORDER BY tanggal, notransaksi";                   

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pembelian_header 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                                                no_supplier='$no_supplier'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";                
            }
            

    $nama_file="Laporan Pembelian ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".pdf";
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,$sql_query);
	
	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
                <tr>
                    <td align="center"><b>Laporan Pembelian <br>Periode '.$tgl_pilih_dari.' s/d '.$tgl_pilih_sampai.'</b></td>
                </tr>
            </table>
            <br>
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">											
                <tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>                                                                        
                                            <td bgcolor="gainsboro" width="9%"><b>No. Transaksi</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Cara Bayar</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>No. Pesanan</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Tgl Pesanan</b></td>                                            
                                            <td bgcolor="gainsboro" width="10%"><b>Kode Supplier</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Nama Supplier</b></td>
                                            <td bgcolor="gainsboro" align="right" width="10%"><b>Total Akhir</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Keterangan</b></td>
                </tr>';
            
                $no = 1;
                $tot_beli=0;
                while($row = mysqli_fetch_array($query))
                    {
                        $tot_beli=$tot_beli+$row['total_akhir'];
														
            $html .= "<tr>
                <td align=center>".$no."</td>
                <td align=center>".$row['notransaksi']."</td>
                <td align=center>".$row['tanggal_trx']."</td>
                <td align=center>".$row['carabayar']."</td>
                <td align=center>".$row['no_order']."</td>
                <td align=center>".$row['tanggal_order']."</td>                
                <td>".$row['no_supplier']."</td>		
                <td>".$row['namasupplier']."</td>		
                <td align=right>".number_format($row['total_akhir'])."</td>
                <td>".$row['note']."</td>		
            </tr>";
            $no++;
            }

            $html .= "<tr>
                <td colspan=8 align=right>Total : &nbsp;</td>
                <td align=right>".number_format($tot_beli)."</td>
                <td></td>		
            </tr>";
            
$html .= "</table></html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream($nama_file);
?>
