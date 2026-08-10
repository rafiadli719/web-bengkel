<?php
    include "../config/koneksi.php";


	//$no_bukti = "HT22000000001";
	$no_bukti = $_GET['snotrx'];
    
// Data Perusahaan ===========
	$cari_kd=mysqli_query($koneksi,"SELECT * FROM tbsetting");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_perusahaan=$tm_cari['nama_perusahaan'];
    $alamat=$tm_cari['alamat'];	
    $notlp=$tm_cari['notlp'];	
    $fax=$tm_cari['fax'];	
    $file_logo=$tm_cari['file_logo'];	    
// ===================

	$cari_kd=mysqli_query($koneksi,"SELECT 
									DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx, no_supplier, 
                                    note, total_bayar, user, id_tabel 
                                    FROM tblhutang_header 
									WHERE no_transaksi='$no_bukti'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$tgltrx_eng=$tm_cari['tanggal_trx'];
    $no_supplier=$tm_cari['no_supplier'];	
    $note=$tm_cari['note'];	
    $total_bayar=$tm_cari['total_bayar'];	
    $user=$tm_cari['user'];
    $id_tabel=$tm_cari['id_tabel'];

	$cari_kd=mysqli_query($koneksi,"SELECT 
									namasupplier 
                                    FROM tblsupplier 
                                    WHERE nosupplier='$no_supplier'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$namasupplier=$tm_cari['namasupplier'];

	$cari_kd=mysqli_query($koneksi,"SELECT 
									sum(jumlah_bayar) as tot 
                                    FROM tblhutang_detail 
                                    WHERE no_transaksi='$no_bukti'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$tot=$tm_cari['tot'];
    
	$query = mysqli_query($koneksi,"SELECT 
                                    no_pembelian, keterangan, jumlah_hutang, jumlah_bayar, status 
                                    FROM 
                                    tblhutang_detail 
                                    WHERE no_transaksi='$no_bukti'");
														
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
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;">
                    <img src="../'.$file_logo.'" width="120pt">
                    </td> 			
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 40%;">
                        <b>'.$nama_perusahaan.'</b><br>
                        <font size="2">
                            '.$alamat.'<br>
                            Telp. '.$notlp.'<br>
                            Fax. '.$fax.'
                        </font>
                    </td> 			                    
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 40%;">
                        <b>&nbsp;BUKTI PEMBAYARAN HUTANG</b><br>
                        <table style="margin: 0 0pt; width: 100%;">
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">No. Trs</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$no_bukti.'</font></td>                    
                            </tr>                        
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Tanggal</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$tgltrx_eng.'</font></td>                    
                            </tr>                
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Supplier</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$no_supplier.'&nbsp;'.$namasupplier.'</font></td>                    
                            </tr>                           
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">User</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$user.'</font></td>                    
                            </tr>                                                 
                        </table>
                    </td> 			
                </tr>
			</tbody>
		</table>
        <br>
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="4"><hr></td>
            </tr>
            <tr>																			
                <td width="5%"><font size="2"><b>No</b></font></td>
                <td width="20%"><font size="2"><b>No. Transaksi</b></font></td>
                <td width="55%"><font size="2"><b>Keterangan</b></font></td>													
                <td width="20%" align="right"><font size="2"><b>Total</b></font></td>                
            </tr>
            <tr>
                <td colspan="4"><hr></td>
            </tr>';

            $no = 1;
            while($row = mysqli_fetch_array($query))
            {

        $html .= "<tr>
                <td align=center><font size=2>".$no."</font></td>
                <td><font size=2>".$row['no_pembelian']."</font></td>
                <td><font size=2>".$row['keterangan']."</font></td>
                <td align=right><font size=2>".number_format($row['jumlah_bayar'],0)."</font></td>		
                </tr>";
            $no++;
            }

        $html .= '</table>        
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">											
                        <tr>
                <td colspan="3"><hr></td>
            </tr>
            <tr>																			
                <td width="70%"><font size="2">Keterangan : </font></td>
                <td width="10%" align="right"><font size="2"><b>Total</b></font></td>
                <td width="20%" align="right"><font size="2"><b>'.number_format($tot,0).'</b></font></td>                
            </tr>
            </table>
            <br>&nbsp;
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">											
            <tr>																			
                <td width="50%" align="center">
                <font size="2">Mengetahui</font>
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <u><font color="white">andri mulia alius amir hamzah</font></u>
                </td>
                <td width="50%" align="center">
                <font size="2">Penerima</font>
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <u><font color="white">andri mulia alius amir hamzah</font></u>
                </td>
            </tr>
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
