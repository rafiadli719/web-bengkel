<?php
    include "../config/koneksi.php";
	$notrx = $_GET['snopesanan'];
    
// Data Perusahaan ===========
	$cari_kd=mysqli_query($koneksi,"SELECT * FROM tbsetting");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_perusahaan=$tm_cari['nama_perusahaan'];
    $alamat=$tm_cari['alamat'];	
    $notlp=$tm_cari['notlp'];	
    $fax=$tm_cari['fax'];	
    $file_logo=$tm_cari['file_logo'];	    
// ===================

		$cari_kd=mysqli_query($koneksi,"SELECT *, 
                                        DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                                        FROM tbitem_masuk_header 
                                        WHERE 
                                        no_transaksi='$notrx'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
        $tgl_pilih=$tm_cari['tanggal_trx'];                 
        $ket=$tm_cari['note'];     
		$user_order=$tm_cari['user'];
        
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot, 
                                        sum(quantity) as tot_order
                                        FROM tbitem_masuk_detail 
                                        WHERE 
                                        no_transaksi='$notrx'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $tot=$tm_cari['tot'];
        $tot_order=$tm_cari['tot_order'];

	$query = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, quantity, harga, total 
                                                                        FROM tbitem_masuk_detail 
                                                                        WHERE no_transaksi='$notrx'");
														
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
                        <b>&nbsp;PENYESUAIAN STOK ITEM MASUK</b><br>
                        <table style="margin: 0 0pt; width: 100%;">
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2"><b>No</b></font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2"><b>:</b></font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2"><b>'.$notrx.'</b></font></td>                    
                            </tr>                        
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Tanggal</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$tgl_pilih.'</font></td>                    
                            </tr>                
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Keterangan</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$ket.'</font></td>                    
                            </tr>                           
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">User</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$user_order.'</font></td>                    
                            </tr>                                                 
                        </table>
                    </td> 			
                </tr>
			</tbody>
		</table>
        <br>
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="6"><hr></td>
            </tr>
            <tr>	
                <td class="center" width="5%"><font size="2"><b>No</b></font></td>
                <td width="15%"><font size="2"><b>Kode</b></font></td>
                <td width="40%"><font size="2"><b>Nama Item</b></font></td>
                <td align="right" width="10%"><font size="2"><b>Jumlah</b></font></td>
                <td align="right" width="15%"><font size="2"><b>Harga</b></font></td>
                <td align="right" width="15%"><font size="2"><b>Total</b></font></td>
            </tr>
            <tr>
                <td colspan="6"><hr></td>
            </tr>';

            $no = 1;
            while($row = mysqli_fetch_array($query))
            {
                $no_item=$row['no_item'];
                $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                                FROM tblitem 
                                                WHERE noitem='$no_item'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $namaitem_tbl=$tm_cari['namaitem'];
                                                
        $html .= "<tr>
                <td align=center><font size=2>".$no."</font></td>
                <td><font size=2>".$row['no_item']."</font></td>
                <td><font size=2>".$namaitem_tbl."</font></td>
                <td align=right><font size=2>".$row['quantity']."</font></td>
                <td align=right><font size=2>".number_format($row['harga'],0)."</font></td>		
                <td align=right><font size=2>".number_format($row['total'],0)."</font></td>		                                
                </tr>";
            $no++;
            }

        $html .= '</table>        
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">											
                        <tr>
                <td colspan="4"><hr></td>
            </tr>
            <tr>																			
                <td align="right" width="60%"><font size="2"><b>Sub Total :</b></font></td>
                <td width="10%" align="right"><font size="2"><b>'.$tot_order.'</b></font></td>
                <td width="30%" align="right"><font size="2"><b>'.number_format($tot,0).'</b></font></td>                
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
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream('surat-penawaran.pdf',array("Attachment"=>0));
?>
