<?php
    include "../config/koneksi.php";
	$nobl = $_GET['snobl'];
    
// Data Perusahaan ===========
	$cari_kd=mysqli_query($koneksi,"SELECT * FROM tbsetting");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_perusahaan=$tm_cari['nama_perusahaan'];
    $alamat=$tm_cari['alamat'];	
    $notlp=$tm_cari['notlp'];	
    $fax=$tm_cari['fax'];	
    $file_logo=$tm_cari['file_logo'];	    
// ===================

// Data Transaksi Pembelian ==========       
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        tanggal, no_supplier, user, 
                                        total_qty, total_beli, 
                                        diskon, total_diskon, 
                                        pajak, total_pajak,
                                        total_akhir, pembayaran, jumlah_bayar 
                                        FROM 
                                        tblpembelian_header 
                                        WHERE 
                                        notransaksi='$nobl'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal_order=$tm_cari['tanggal'];
		$no_supplier=$tm_cari['no_supplier'];
		$user_order=$tm_cari['user'];
        $total_qty=$tm_cari['total_qty']; 
        $total_beli=$tm_cari['total_beli'];
        $diskon=$tm_cari['diskon'];
        $total_diskon=$tm_cari['total_diskon'];
        $pajak=$tm_cari['pajak'];
        $total_pajak=$tm_cari['total_pajak'];
        $total_akhir=$tm_cari['total_akhir'];
        $pembayaran=$tm_cari['pembayaran'];
        $jumlah_bayar=$tm_cari['jumlah_bayar'];
// =====================

	$cari_kd=mysqli_query($koneksi,"SELECT 
									namasupplier, alamat 
                                    FROM tblsupplier 
                                    WHERE nosupplier='$no_supplier'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$namasupplier=$tm_cari['namasupplier'];
	$alamat=$tm_cari['alamat'];

		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        sum(qty_order) as tot_order, 
                                        sum(quantity) as tot_beli 
                                        FROM 
                                        tblpembelian_detail 
                                        WHERE 
                                        no_transaksi='$nobl'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$tot_qty_order=$tm_cari['tot_order'];				        
        $tot_qty_beli=$tm_cari['tot_beli'];        
    
	$query = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, qty_order, quantity, 
                                                                        harga_pokok, total, potongan 
                                                                        FROM tblpembelian_detail 
                                                                        WHERE no_transaksi='$nobl'");
														
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
                        <b>&nbsp;FAKTUR PEMBELIAN</b><br>
                        <table style="margin: 0 0pt; width: 100%;">
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2"><b>No. Transaksi</b></font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2"><b>:</b></font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2"><b>'.$nobl.'</b></font></td>                    
                            </tr>                        
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Tanggal</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$tanggal_order.'</font></td>                    
                            </tr>                
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Supplier</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$no_supplier.'&nbsp;'.$namasupplier.'</font></td>                    
                            </tr>                           
                            <tr>
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">Alamat</font></td> 			
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">:</font></td>                    
                                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$alamat.'</font></td>                    
                            </tr>                                                 
                        </table>
                    </td> 			
                </tr>
			</tbody>
		</table>
        <br>
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="8"><hr></td>
            </tr>
            <tr>	
                <td class="center" width="5%"><font size="2"><b>No</b></font></td>
                <td width="15%"><font size="2"><b>Kode</b></font></td>
                <td width="30%"><font size="2"><b>Nama Item</b></font></td>
                <td align="right" width="9%"><font size="2"><b>Pesan</b></font></td>
                <td align="right" width="9%"><font size="2"><b>Jumlah</b></font></td>
                <td align="right" width="9%"><font size="2"><b>Harga</b></font></td>
                <td align="right" width="9%"><font size="2"><b>Pot.</b></font></td>
                <td align="right" width="14%"><font size="2"><b>Total</b></font></td>																		
            </tr>
            <tr>
                <td colspan="8"><hr></td>
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
                <td align=right><font size=2>".$row['qty_order']."</font></td>
                <td align=right><font size=2>".$row['quantity']."</font></td>
                <td align=right><font size=2>".number_format($row['harga_pokok'],0)."</font></td>		
                <td align=right><font size=2>".number_format($row['potongan'],0)."%</font></td>		                
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
                <td align="right" width="59%"><font size="2"><b>Sub Total :</b></font></td>
                <td width="9%" align="right"><font size="2"><b>'.$tot_qty_beli.'</b></font></td>
                <td colspan="2" align="right"><font size="2"><b>'.number_format($total_beli,0).'</b></font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right" width="59%"><font size="2">Potongan Faktur :</font></td>
                <td width="9%" align="right"><font size="2">'.number_format($total_diskon,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right" width="59%"><font size="2">Pajak :</font></td>
                <td width="9%" align="right"><font size="2">'.number_format($total_pajak,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right" width="59%"><font size="2">Total Netto :</font></td>
                <td width="9%" align="right"><font size="2">'.number_format($total_akhir,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right" width="59%"><font size="2">DP/Uang Muka :</font></td>
                <td width="9%" align="right"><font size="2">'.number_format($pembayaran,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right" width="59%"><font size="2"><b>Kekurangan :</b></font></td>
                <td width="9%" align="right"><font size="2"><b>'.number_format($jumlah_bayar,0).'</b></font></td>                
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
