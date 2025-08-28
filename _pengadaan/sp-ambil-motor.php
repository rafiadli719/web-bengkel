<?php
    include "../config/koneksi.php";
	$no_service = $_GET['snosrv'];
    
// Data Perusahaan ===========
	$cari_kd=mysqli_query($koneksi,"SELECT * FROM tbsetting");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_perusahaan=$tm_cari['nama_perusahaan'];
    $alamat=$tm_cari['alamat'];	
    $notlp=$tm_cari['notlp'];	
    $fax=$tm_cari['fax'];	
    $file_logo=$tm_cari['file_logo'];	    
// ===================

// Data Transaksi Servis ==========       
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_serv, 
                                        jam, no_pelanggan, no_polisi, 
                                        keterangan 
                                        FROM tblservice 
                                        WHERE no_service='$no_service'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal=$tm_cari['tanggal_serv'];                
		$jam=$tm_cari['jam'];        
		$kode_pelanggan=$tm_cari['no_pelanggan'];        
		$no_polisi=$tm_cari['no_polisi'];        
		$ket=$tm_cari['keterangan'];        
                
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        namapelanggan, alamat, patokan, kgrup 
                                        FROM tblpelanggan 
                                        WHERE nopelanggan='$kode_pelanggan'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$namapelanggan=$tm_cari['namapelanggan'];
		$alamat_pelanggan=$tm_cari['alamat'];
		$patokan=$tm_cari['patokan'];
		$kgrup=$tm_cari['kgrup'];

		$cari_kd=mysqli_query($koneksi,"SELECT grup, diskon FROM tblpelanggangrup 
                                        WHERE kgrup='$kgrup'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tipepelanggan=$tm_cari['grup'];
        



        
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        pemilik, jenis, merek, warna, 
                                        no_rangka, no_mesin, tipe 
                                        FROM view_cari_kendaraan 
                                        WHERE nopolisi='$no_polisi'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$pemilik=$tm_cari['pemilik'];
		$jenis=$tm_cari['jenis'];
		$merek=$tm_cari['merek'];
		$warna=$tm_cari['warna'];
		$tipe=$tm_cari['tipe'];
		$no_rangka=$tm_cari['no_rangka'];
		$no_mesin=$tm_cari['no_mesin'];        
        $km_skr="";
        $km_berikut="";

        $query_keluhan = mysqli_query($koneksi,"SELECT keluhan FROM tbservis_keluhan 
                                            WHERE no_service='$no_service' 
                                            order by id asc");

    														
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();

	$html = '<head>
				<style>
					html, body {
						font-family: Arial, Helvetica, sans-serif;
					}
					table.table, table.table td, table.table th {
						border: 1px solid black;
					}

					table.table {
						width: 100%;
						border-collapse: collapse;
					}

div.page_break + div.page_break{
    page-break-before: always;
}

					sup {
						font-size: 8;
					}
				</style>	
			</head>
			<body>
		<div style="margin-top: -20pt; padding: 10pt; overflow: none; text-align: justify;">
        
        <table style="margin: 0 0pt; width: 100%;">
            <tbody>
                <tr valign="top">
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 40%;">
                        <b>&nbsp;SURAT PERINTAH PENGAMBILAN MOTOR</b><br>
                        <b>&nbsp;BENGKEL FIT MOTOR</b>
                        <br>&nbsp;<br>&nbsp;

                        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">
                            <tr>
                                <td colspan="2" style="padding: 1pt 2pt; vertical-align:top;">
                                    <table style="margin: 0 0pt; width: 100%;">
                                        <tr>
                                            <td width="10%"><font size="2">Tanggal :</font></td>
                                            <td width="10%"><font size="2">'.$tanggal.'</font></td>                                            
                                            <td width="15%" align="right"><font size="2">Jam Ambil :</font></td>
                                            <td width="10%"><font size="2">'.$jam.'</font></td>                                            
                                            <td width="15%" align="right"><font size="2">No. Servis :&nbsp;</font></td>
                                            <td width="40%"><font size="2">'.$no_service.'</font></td>                                                                                        
                                        </tr>
                                    </table>
                                </td> 			
                            </tr>                        
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Nama :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;"><font size="2"><b>&nbsp;'.$namapelanggan.'</b></font></td>                    
                            </tr>                        
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">No. Polisi :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;">
                                    <table style="margin: 0 0pt; width: 100%;">
                                        <tr>
                                            <td width="25%">
                                                <font size="2">
                                                    <b>&nbsp;'.$no_polisi.'</b>
                                                </font>                                            
                                            </td>
                                            <td width="25%">
                                                <font size="2">
                                                    &nbsp;Merek : '.$merek.'
                                                </font>                                                                                        
                                            </td>
                                            <td width="25%">
                                                <font size="2">
                                                    &nbsp;Tipe : '.$tipe.'
                                                </font>                                                                                        
                                            </td>
                                            <td width="25%">
                                                <font size="2">
                                                    &nbsp;Jenis : '.$jenis.'
                                                </font>                                                                                        
                                            </td>
                                        </tr>
                                    </table>
                                </td>                    
                            </tr>                        
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Pengerjaan :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;">
                                <font size="2">'.$tipepelanggan.'</font>
<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">                                
                                ';

            $no = 1;
            while($row = mysqli_fetch_array($query_keluhan))
            {                                                
            
            $html .= "<tr>
                <td align=center><font size=2>".$no."</font></td>
                <td><font size=2>".$row['keluhan']."</font></td>
                </tr>";
            $no++;
            }                                    
                            
                        $html .= '
                                    </table>
                                </td>                    
                            </tr>                                                    
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Alamat :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;"><font size="2">&nbsp;'.$alamat_pelanggan.'</font></td>                    
                            </tr>                                                                                
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Patokan :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;"><font size="2"><b>&nbsp;'.$patokan.'</b></font></td>                    
                            </tr>                                                                                                            
                            <tr>
                                <td align="right" style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Keterangan :&nbsp;</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 80%;"><font size="2"><b>&nbsp;'.$ket.'</b></font></td>                    
                            </tr>                                                                                                            
                        </table>
                    </td> 			
                </tr>
			</tbody>
		</table>';
							
$html .= "</div></body></html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'portrait');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream('surat-penawaran.pdf',array("Attachment"=>0));
?>
