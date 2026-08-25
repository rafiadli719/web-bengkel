<?php
    include "../config/koneksi.php";
    include "_template/_nota_pdf_parts.php";
	$nobl = mysqli_real_escape_string($koneksi, $_GET['snobl']);
    
// Data Perusahaan ===========
	$cari_kd=mysqli_query($koneksi,"SELECT * FROM tbsetting");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_perusahaan=$tm_cari['nama_perusahaan'];
    $alamat_perusahaan=$tm_cari['alamat'];	
    $notlp=$tm_cari['notlp'];	
    $fax=$tm_cari['fax'];	
    $file_logo=$tm_cari['file_logo'];	    
// ===================

// Data Transaksi Pembelian ==========       
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        tanggal, no_pelanggan, user, 
                                        total_qty, total_jual, 
                                        diskon, total_diskon, 
                                        pajak, total_pajak,
                                        total_akhir, pembayaran, no_sales, 
DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx, note 
                                        FROM 
                                        tblorderjual_header 
                                        WHERE 
                                        no_order='$nobl'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal_order=$tm_cari['tanggal_trx'];
		$no_pelanggan=$tm_cari['no_pelanggan'];
		$user_order=$tm_cari['user'];
        $total_qty=$tm_cari['total_qty']; 
        $total_beli=$tm_cari['total_jual'];
        $diskon=$tm_cari['diskon'];
        $total_diskon=$tm_cari['total_diskon'];
        $pajak=$tm_cari['pajak'];
        $total_pajak=$tm_cari['total_pajak'];
        $total_akhir=$tm_cari['total_akhir'];
        $pembayaran=$tm_cari['pembayaran'];
        $no_sales=$tm_cari['no_sales'];
        $ket=$tm_cari['note'];
        $jumlah_bayar=$total_akhir-$pembayaran;
        
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        namapelanggan, alamat  
                                        FROM tblpelanggan 
																	WHERE nopelanggan='$no_pelanggan'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$namapelanggan=$tm_cari['namapelanggan'];
        $alamat_pelanggan=$tm_cari['alamat'];

		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        namasales  
                                        FROM tblsales 
																	WHERE nosales='$no_sales'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$namasales=$tm_cari['namasales'];

		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        sum(quantity) as tot_beli 
                                        FROM 
                                        tblorderjual_detail 
                                        WHERE 
                                        no_order='$nobl'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
        $tot_beli=$tm_cari['tot_beli'];        
    
	$query = mysqli_query($koneksi,"SELECT id, no_item, quantity, 
                                                                        harga_jual, total, potongan 
                                                                        FROM tblorderjual_detail 
                                                                        WHERE no_order='$nobl'");
														
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();

	$html = '<head>
				<style>'.nota_pdf_style().'</style>
			</head>
			<body>
		<div style="margin-top: -20pt; padding: 10pt; overflow: none; text-align: justify;">
'.nota_pdf_header($file_logo, $nama_perusahaan, $alamat_perusahaan, $notlp, $fax, 'FAKTUR PESANAN PENJUALAN', array(
            array('No. Pesanan', $nobl),
            array('Tanggal', $tanggal_order),
            array('Pelanggan', $no_pelanggan.'&nbsp;'.$namapelanggan),
            array('Alamat', $alamat_pelanggan),
        )).'
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="7"><hr></td>
            </tr>
            <tr>
                <td class="center" width="5%"><font size="2"><b>No</b></font></td>
                <td width="15%"><font size="2"><b>Kode</b></font></td>
                <td width="34%"><font size="2"><b>Nama Item</b></font></td>
                <td align="right" width="10%"><font size="2"><b>Jumlah</b></font></td>
                <td align="right" width="10%"><font size="2"><b>Harga</b></font></td>
                <td align="right" width="10%"><font size="2"><b>Pot.</b></font></td>
                <td align="right" width="15%"><font size="2"><b>Total</b></font></td>																		
            </tr>
            <tr>
                <td colspan="7"><hr></td>
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
                <td align=right><font size=2>".number_format($row['harga_jual'],0)."</font></td>		
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
                <td align="right" width="54%"><font size="2"><b>Sub Total :</b></font></td>
                <td width="10%" align="right"><font size="2"><b>'.$tot_beli.'</b></font></td>
                <td colspan="2" align="right"><font size="2"><b>'.number_format($total_beli,0).'</b></font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right"><font size="2">Potongan Faktur :</font></td>
                <td width="15%" align="right"><font size="2">'.number_format($total_diskon,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right"><font size="2">Pajak :</font></td>
                <td width="15%" align="right"><font size="2">'.number_format($total_pajak,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right"><font size="2">Total Netto :</font></td>
                <td width="15%" align="right"><font size="2">'.number_format($total_akhir,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right"><font size="2">DP/Uang Muka :</font></td>
                <td width="15%" align="right"><font size="2">'.number_format($pembayaran,0).'</font></td>                
            </tr>
            <tr>																			
                <td colspan="3" align="right"><font size="2">Kekurangan :</font></td>
                <td width="15%" align="right"><font size="2">'.number_format($jumlah_bayar,0).'</font></td>                
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
$dompdf->stream('faktur-pesanan-penjualan.pdf',array("Attachment"=>0));
?>
