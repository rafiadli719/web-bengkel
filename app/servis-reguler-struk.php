<?php
    session_start();
    if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
    include "../config/koneksi.php";
    include "_template/_nota_pdf_parts.php";
    include "_include_customer_vehicle_sync.php";
    include "helper-functions.php";
    $no_service = mysqli_real_escape_string($koneksi, $_GET['snoserv'] ?? '');
	$mode = isset($_GET['mode']) ? mysqli_real_escape_string($koneksi, $_GET['mode']) : '';
    
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
                                        total,
                                        diskon_persen, diskon_nom,
                                        ppn_persen, ppn_nom,
                                        total_grand, bayar, kembali,
                                        mekanik1, mekanik2, mekanik3, mekanik4
                                        FROM tblservice
                                        WHERE no_service='$no_service'");
		$tm_cari=mysqli_fetch_array($cari_kd);	
		$tanggal=$tm_cari['tanggal_serv'];                
		$jam=$tm_cari['jam'];        
		$kode_pelanggan=$tm_cari['no_pelanggan'];        
		$no_polisi=$tm_cari['no_polisi'];        
        
		$total=$tm_cari['total'];        
		$diskon_persen=$tm_cari['diskon_persen'];        
		$diskon_nom=$tm_cari['diskon_nom'];                
		$ppn_persen=$tm_cari['ppn_persen'];
        $ppn_nom=$tm_cari['ppn_nom'];
        $net=$tm_cari['total_grand'];
        $bayar=$tm_cari['bayar'];
        $kembali=$tm_cari['kembali'];
        $nama_mekanik = getMekanikNamaGabung($koneksi,
            $tm_cari['mekanik1'], $tm_cari['mekanik2'],
            $tm_cari['mekanik3'], $tm_cari['mekanik4']);

        $customerRow = fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $no_polisi);
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $bundleCustomer = $bundle['customer'] ?? [];
		$namapelanggan=$customerRow['namapelanggan'] ?? ($bundleCustomer['namapelanggan'] ?? '');
		$pemilik=$vehicleRow['pemilik'] ?? '';
		$jenis=$vehicleRow['jenis'] ?? '';
		$merek=$vehicleRow['merek'] ?? ($vehicleRow['tipe'] ?? '');
		$warna=$vehicleRow['warna'] ?? '';
		$no_rangka=$vehicleRow['no_rangka'] ?? '';
		$no_mesin=$vehicleRow['no_mesin'] ?? '';        
        $km_skr="";
        $km_berikut="";

        // == Total dari Item Srrvice ==============
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                        FROM tblservis_jasa 
                                        WHERE 
                                        no_service='$no_service'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $total_service=$tm_cari['tot']; 

        // == Total dari Item Barang ==============
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                        FROM tblservis_barang 
                                        WHERE 
                                        no_service='$no_service'");			
        $tm_cari=mysqli_fetch_array($cari_kd);
        $total_barang=$tm_cari['tot']; 
        
        $query_jasa = mysqli_query($koneksi, "SELECT
                        j.id, j.no_item, j.waktu, j.harga, j.total, j.potongan,
                        COALESCE(wh.nama_wo, j.no_item) AS namaitem
                    FROM tblservis_jasa j
                    LEFT JOIN tbworkorderheader wh ON wh.kode_wo = j.no_item
                    WHERE j.no_service='$no_service'");

        $query_barang = mysqli_query($koneksi, "SELECT
                        b.id, b.no_item, b.quantity, b.harga_jual, b.total, b.potongan,
                        COALESCE(i.namaitem, b.no_item) AS namaitem
                    FROM tblservis_barang b
                    LEFT JOIN tblitem i ON i.noitem = b.no_item
                    WHERE b.no_service='$no_service'");
                                                                        
    														
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();

	$html = '<head>
				<style>'.nota_pdf_style().'</style>
			</head>
			<body>
		<div style="margin-top: -20pt; padding: 10pt; overflow: none; text-align: justify;">
'.nota_pdf_header($file_logo, $nama_perusahaan, $alamat, $notlp, $fax, 'FAKTUR SERVICE', array(
            array('No. Service', $no_service),
            array('Tanggal', $tanggal),
            array('Pelanggan', $namapelanggan),
        )).'
        <hr>
        <table style="margin: 0 0pt; width: 100%;">
            <tr>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 30%;"><font size="2"><b>No. Polisi</b></font></td>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2"><b>:</b></font></td>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 65%;"><font size="2"><b>'.$no_polisi.'</b></font></td>
            </tr>
            '.(!empty($nama_mekanik) ? '
            <tr>
                <td style="padding: 1pt 2pt; vertical-align:top;"><font size="2">Mekanik</font></td>
                <td style="padding: 1pt 2pt; vertical-align:top;"><font size="2">:</font></td>
                <td style="padding: 1pt 2pt; vertical-align:top;"><font size="2">'.$nama_mekanik.'</font></td>
            </tr>' : '').'
        </table>
        <br>
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="7"><hr></td>
            </tr>
            <tr>
                <td colspan="7">Jasa Bengkel</td>
            </tr>
            <tr>	
                <td align="center" width="5%">No</td>
                <td width="15%">Kode Item</td>
                <td width="35%">Nama Item</td>
                <td width="10%">Waktu</td>
                <td align="right" width="10%">Biaya</td>
                <td align="right" width="10%">Pot %</td>
                <td align="right" width="15%">Total</td>
            </tr>
            <tr>
                <td colspan="7"><hr></td>
            </tr>';

            $no = 1;
            while($row = mysqli_fetch_array($query_jasa))
            {
                $namaitem_tbl = $row['namaitem'];

        $html .= "<tr>
                <td align=center><font size=2>".$no."</font></td>
                <td><font size=2>".$row['no_item']."</font></td>
                <td><font size=2>".$namaitem_tbl."</font></td>
                <td><font size=2>".$row['waktu']."</font></td>
                <td align=right><font size=2>".number_format($row['harga'],0)."</font></td>		
                <td align=right><font size=2>".$row['potongan']."%</font></td>		                
                <td align=right><font size=2>".number_format($row['total'],0)."</font></td>		                                
                </tr>";
            $no++;
            }
            
        $html .= '
            <tr>																			
                <td colspan="7" align="right"><font size="2"><b>'.number_format($total_service,0).'</b></font></td>                
            </tr>
            </table>
<br>&nbsp;
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td colspan="8"><hr></td>
            </tr>
            <tr>
                <td colspan="8">Barang/Part Bengkel</td>
            </tr>
            <tr>	
                <td align="center" width="5%">No</td>
                <td width="10%">Kode Item</td>
                <td width="30%">Nama Item</td>
                <td width="10%"align="right">Jumlah</td>
                <td width="10%">Satuan</td>
                <td align="right" width="10%">Biaya</td>
                <td align="right" width="10%">Pot %</td>
                <td align="right" width="15%">Total</td>
            </tr>
            <tr>
                <td colspan="8"><hr></td>
            </tr>';

            $no = 1;
            while($row = mysqli_fetch_array($query_barang))
            {
                $namaitem_tbl = $row['namaitem'];

        $html .= "<tr>
                <td align=center><font size=2>".$no."</font></td>
                <td><font size=2>".$row['no_item']."</font></td>
                <td><font size=2>".$namaitem_tbl."</font></td>
                <td align=right><font size=2>".$row['quantity']."</font></td>
                <td><font size=2>PCS</font></td>                
                <td align=right><font size=2>".number_format($row['harga_jual'],0)."</font></td>		
                <td align=right><font size=2>".$row['potongan']."%</font></td>		                
                <td align=right><font size=2>".number_format($row['total'],0)."</font></td>		                                
                </tr>";
            $no++;
            }
            
        $html .= '
            <tr>																			
                <td colspan="8" align="right"><font size="2"><b>'.number_format($total_barang,0).'</b></font></td>                
            </tr>
            </table>
            <br>&nbsp;
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">											
            <tr valign="top">																			
                <td width="18%" align="center">
                <font size="2">Pemilik/Pembawa</font>
                </td>
                <td width="4%"></td>
                <td width="18%" align="center">
                <font size="2">Administrasi</font>
                </td>                
                <td width="15%" align="right">
                    <font size="2">Pot. Faktur : </font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">'.$diskon_persen.' %</font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">'.number_format($diskon_nom,0).'</font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">Total Akhir : </font>     
                </td>   
                <td width="15%" align="right">
                    <font size="2">'.number_format($net,0).'</font>     
                </td>             
            </tr>
            <tr valign="top">																			
                <td width="18%" align="center">
                <font size="2">Kendaraan</font>
                </td>
                <td width="4%"></td>                
                <td width="18%" align="center">
                <font size="2"></font>
                </td>                
                <td width="15%" align="right">
                    <font size="2">Pajak : </font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">'.$ppn_persen.' %</font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">'.number_format($ppn_nom,0).'</font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">Bayar : </font>     
                </td>   
                <td width="15%" align="right">
                    <font size="2">'.number_format($bayar,0).'</font>     
                </td>             
            </tr>            
            <tr valign="top">																			
                <td width="18%" align="center">
                <font size="2"></font>
                </td>
                <td width="4%"></td>                
                <td width="18%" align="center">
                <font size="2"></font>
                </td>                
                <td width="15%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2"></font>     
                </td>   
                <td width="15%" align="right">
                <hr>
                </td>             
            </tr>
            <tr valign="top">																			
                <td width="18%" align="center">
                <font size="2"></font>
                </td>
                <td width="4%"></td>                
                <td width="18%" align="center">
                <font size="2"></font>
                </td>                
                <td width="15%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2"></font>     
                </td>
                <td width="10%" align="right">
                    <font size="2">Kembali</font>     
                </td>   
                <td width="15%" align="right">
                <font size="2">'.number_format($kembali,0).'</font> 
                </td>             
            </tr>
            <tr valign="top">																			
                <td width="18%" align="center">
                    <br>&nbsp;
                    <hr>
                </td>
                <td width="4%"></td>                
                <td width="18%" align="center">
                    <br>&nbsp;
                    <hr>
                </td>                
                <td colspan="5"></td>             
            </tr>
            </table>';
							
$html .= "</div></body></html>";

	if($mode === 'print') {
		header('Content-Type: text/html; charset=UTF-8');
		echo $html;
		echo '<script>window.onload=function(){window.print();};</script>';
		exit;
	}

	if($mode === 'view') {
		header('Content-Type: text/html; charset=UTF-8');
		echo $html;
		exit;
	}

	$dompdf->loadHtml($html);
	// Setting ukuran dan orientasi kertas
	$dompdf->setPaper('A4', 'landscape');
	// Rendering dari HTML Ke PDF
	$dompdf->render();
	// Melakukan output file Pdf - mode download = attachment 1, default = inline 0
	$attachment = ($mode === 'download') ? 1 : 0;
	$dompdf->stream('Nota-Servis-'.$no_service.'.pdf',array("Attachment"=>$attachment));
?>
