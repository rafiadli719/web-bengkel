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
	$nopelanggan= $_GET['ssup'];	
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            if($nopelanggan=='') {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pesanan_penjualan_h 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') 
                                    ORDER BY tanggal, no_order";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pesanan_penjualan_h 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             
            } else {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pesanan_penjualan_h 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                                    (no_pelanggan like '%".$nopelanggan."%') OR 
                                    (namapelanggan like '%".$nopelanggan."%') 
                                    ORDER BY tanggal, no_order";                   

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pesanan_penjualan_h 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                                                (no_pelanggan like '%".$nopelanggan."%') OR 
                                    (namapelanggan like '%".$nopelanggan."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";                
            }
            

    $nama_file="Laporan Pesanan Penjualan ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".pdf";
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,$sql_query);
	
	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
                <tr>
                    <td align="center"><b>Laporan Pesanan Penjualan <br>Periode '.$tgl_pilih_dari.' s/d '.$tgl_pilih_sampai.'</b></td>
                </tr>
            </table>
            <br>
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">											
                <tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                                            <td bgcolor="gainsboro" align="center" width="10%"><b>No. Pesanan</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" width="9%"><b>Kode Pelanggan</b></td>
                                            <td bgcolor="gainsboro" width="10%"><b>Nama Pelanggan</b></td>
                                            <td bgcolor="gainsboro" width="10%"><b>Sales</b></td>
                                            <td bgcolor="gainsboro" width="9%" align="right"><b>Total Qty</b></td>
                                            <td bgcolor="gainsboro" width="10%" align="right"><b>Total Order</b></td>
                                            <td bgcolor="gainsboro" width="9%" align="right"><b>Diskon</b></td>
                                            <td bgcolor="gainsboro" width="9%" align="right"><b>Pajak</b></td>
                                            <td bgcolor="gainsboro" width="10%" align="right"><b>Total Akhir</b></td>
                </tr>';
            
                $no = 1;
                $tot_qty=0;
                $tot_jual=0;
                
                $tot_diskon=0;
                $tot_pajak=0;
                $tot_akhir=0;                
                while($row = mysqli_fetch_array($query))
                    {
                                            $status_order=$row['status'];
                                            if($status_order=='0') {
                                                $ket_status="Open";
                                            } else {
                                                $ket_status="Closed";
                                            }
                                            $tot_qty=$tot_qty+$row['total_qty'];
                                            $tot_jual=$tot_jual+$row['total_akhir'];
                                            $tot_diskon=$tot_diskon+$row['diskon'];
                                            $tot_pajak=$tot_pajak+$row['pajak'];  
                                            $tot_akhir=$tot_akhir+$row['total_akhir'];

														
            $html .= "<tr>
                <td align=center>".$no."</td>
                <td align=center>".$row['no_order']."</td>
                <td align=center>".$row['tanggal_trx']."</td>
                <td>".$row['no_pelanggan']."</td>		
                <td>".$row['namapelanggan']."</td>		
                <td>".$row['namasales']."</td>		                
                <td align=center>".$row['total_qty']."</td>
                <td align=right>".number_format($row['total_jual'])."</td>
                <td align=right>".number_format($row['diskon'])."</td>
                                <td align=right>".number_format($row['pajak'])."</td>
                <td align=right>".number_format($row['total_akhir'])."</td>
            </tr>";
            $no++;
            }

            $html .= "<tr>
                <td colspan=6 align=right>Total : &nbsp;</td>
                <td align=center>".$tot_qty."</td>
                <td align=right>".number_format($tot_jual)."</td>
                <td align=right>".number_format($tot_diskon)."</td>
                <td align=right>".number_format($tot_pajak)."</td>
                <td align=right>".number_format($tot_akhir)."</td>                
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
