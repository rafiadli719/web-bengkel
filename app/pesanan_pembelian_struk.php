<?php
    include "../config/koneksi.php";
    include "_template/_nota_pdf_parts.php";
	$nopesanan = mysqli_real_escape_string($koneksi, $_GET['snopesanan']);

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
                                        tanggal, no_supplier, user 
                                        FROM tblorder_header 
                                        WHERE no_order='$nopesanan'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal_order=$tm_cari['tanggal'];
		$no_supplier=$tm_cari['no_supplier'];
		$user_order=$tm_cari['user'];

		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        namasupplier, alamat 
                                        FROM tblsupplier 
                                        WHERE nosupplier='$no_supplier'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$namasupplier=$tm_cari['namasupplier'];				        
        $alamat=$tm_cari['alamat'];
        
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot, 
                                        sum(quantity) as tot_order
                                        FROM tblorder_detail 
                                        WHERE 
                                        no_order='$nopesanan'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $tot=$tm_cari['tot'];
        $tot_order=$tm_cari['tot_order'];

	$query = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, quantity, harga_pokok, total 
                                                                        FROM tblorder_detail 
                                                                        WHERE no_order='$nopesanan'");
														
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();

	$html = '<head>
				<style>'.nota_pdf_style().'</style>
			</head>
			<body>
		<div style="margin-top: -20pt; padding: 10pt; overflow: none; text-align: justify;">
'.nota_pdf_header($file_logo, $nama_perusahaan, $alamat, $notlp, $fax, 'FAKTUR PESANAN PEMBELIAN', array(
            array('No. Pesanan', $nopesanan),
            array('Tanggal', $tanggal_order),
            array('Supplier', $no_supplier.'&nbsp;'.$namasupplier),
            array('Alamat', $alamat),
        )).'
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="6"><hr></td>
            </tr>
            <tr>
                <td class="center" width="5%"><font size="2"><b>No</b></font></td>
                <td width="15%"><font size="2"><b>Kode</b></font></td>
                <td width="40%"><font size="2"><b>Nama Item</b></font></td>
                <td align="right" width="10%"><font size="2"><b>Jumlah</b></font></td>
                <td align="right" width="15%"><font size="2"><b>Harga Pokok</b></font></td>
                <td align="right" width="15%"><font size="2"><b>Total</b></font></td>
            </tr>
            <tr>
                <td colspan="6"><hr></td>
            </tr>';

            $no = 1;
			$tot_calc = 0;
			$tot_qty_calc = 0;
			while($row = mysqli_fetch_array($query))
			{
				$no_item=$row['no_item'];
				$cari_kd=mysqli_query($koneksi,"SELECT namaitem, hargapokok 
																		FROM tblitem 
																		WHERE (noitem='$no_item' OR kodebarcode='$no_item')");			
				$tm_cari=mysqli_fetch_array($cari_kd);
				$namaitem_tbl=$tm_cari['namaitem'];

				$qty = (int)$row['quantity'];
				$harga_pokok = (float)$row['harga_pokok'];
				if ($harga_pokok <= 0) {
					$harga_pokok = (float)$tm_cari['hargapokok'];
				}
				$total_row = $harga_pokok * $qty;
				$tot_calc += $total_row;
				$tot_qty_calc += $qty;
																
		$html .= "<tr>
				<td align=center><font size=2>".$no."</font></td>
				<td><font size=2>".$row['no_item']."</font></td>
				<td><font size=2>".$namaitem_tbl."</font></td>
				<td align=right><font size=2>".$qty."</font></td>
				<td align=right><font size=2>".number_format($harga_pokok,0)."</font></td>		
				<td align=right><font size=2>".number_format($total_row,0)."</font></td>										
		
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
                <td width="10%" align="right"><font size="2"><b>'.$tot_qty_calc.'</b></font></td>
                <td width="30%" align="right"><font size="2"><b>'.number_format($tot_calc,0).'</b></font></td>                
            </tr>
            </table>
            <br>&nbsp;
            '.nota_pdf_footer_ttd();
							
$html .= "</div></body></html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream('faktur-pesanan-pembelian.pdf',array("Attachment"=>0));
?>
