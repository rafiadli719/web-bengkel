<?php
    include "../config/koneksi.php";
    include "_template/_nota_pdf_parts.php";
	$notrx = mysqli_real_escape_string($koneksi, $_GET['snopesanan']);
    
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
				<style>'.nota_pdf_style().'</style>
			</head>
			<body>
		<div style="margin-top: -20pt; padding: 10pt; overflow: none; text-align: justify;">
'.nota_pdf_header($file_logo, $nama_perusahaan, $alamat, $notlp, $fax, 'PENYESUAIAN STOK ITEM MASUK', array(
            array('No', $notrx),
            array('Tanggal', $tgl_pilih),
            array('Keterangan', $ket),
            array('User', $user_order),
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
            '.nota_pdf_footer_ttd();
							
$html .= "</div></body></html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream('penyesuaian-stok-masuk.pdf',array("Attachment"=>0));
?>
