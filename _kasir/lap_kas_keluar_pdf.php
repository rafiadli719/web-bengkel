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
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM tblkas_keluar_masuk 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                            jenis='Keluar' 
                                    ORDER BY tanggal, kode_km";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM tblkas_keluar_masuk 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                            jenis='Keluar'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             

            

    $nama_file="Laporan Kas Keluar ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".pdf";
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,$sql_query);
	
	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
                <tr>
                    <td align="center"><b>Laporan Kas Keluar <br>Periode '.$tgl_pilih_dari.' s/d '.$tgl_pilih_sampai.'</b></td>
                </tr>
            </table>
            <br>
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">											
                <tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                                            <td bgcolor="gainsboro" width="10%"><b>No. Bukti</b></td>
                                            <td bgcolor="gainsboro" align="center" width="10%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" width="30%"><b>Keterangan</b></td>
                                            <td bgcolor="gainsboro" align="right" width="10%"><b>Jumlah</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Akun Kas</b></td> 
                                            <td bgcolor="gainsboro" width="20%"><b>Akun Biaya</b></td>                                                                                                          
                </tr>';
            
                $no = 1;
                $tot_jml=0;
                while($row = mysqli_fetch_array($query))
                    {

                                                        $kode_akun=$row['kode_akun'];
                                                        $kode_akun_biaya=$row['kode_akun_biaya'];                                                        
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT namaakun FROM tblakunkas 
                                                                                        WHERE kodeakun='$kode_akun'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $namaakun=$tm_cari['namaakun'];				                                                                

                                                        $cari_kd=mysqli_query($koneksi,"SELECT nama_akun FROM tbakun 
                                                                                        WHERE no_akun='$kode_akun_biaya'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $namaakun_biaya=$tm_cari['nama_akun'];				                                                                
                                                        
                                            $tot_jml=$tot_jml+$row['keluar'];
														
            $html .= "<tr valign=top>
                <td align=center>".$no."</td>
                <td>".$row['kode_km']."</td>
                <td align=center>".$row['tanggal_trx']."</td>
                <td>".$row['uraian']."</td>		
                <td align=right>".number_format($row['keluar'])."</td>
                <td>".$namaakun."</td>
                <td>".$row['kode_akun_biaya']."-".$namaakun_biaya."</td>                               
            </tr>";
            $no++;
            }

            $html .= "<tr>
                <td colspan=4 align=right>Total : &nbsp;</td>
                <td align=right>".number_format($tot_jml)."</td>
                <td align=center></td>
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
