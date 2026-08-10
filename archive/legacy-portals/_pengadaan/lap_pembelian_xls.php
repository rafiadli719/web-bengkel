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
	$no_supplier= $_GET['ssup'];	
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            if($no_supplier=='') {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pembelian_header 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') 
                                    ORDER BY tanggal, notransaksi";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pembelian_header 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             
            } else {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM view_pembelian_header 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                                    no_supplier='$no_supplier' 
                                    ORDER BY tanggal, notransaksi";                   

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_pembelian_header 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                                                no_supplier='$no_supplier'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";                
            }
            

    $nama_file="Laporan Pembelian ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".xls";
?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<style type="text/css">
	body{
		font-family: sans-serif;
	}
	table{
		margin: 20px auto;
		border-collapse: collapse;
	}
	table th,
	table td{
		border: 1px solid #3c3c3c;
		padding: 3px 8px;

	}
	a{
		background: blue;
		color: #fff;
		padding: 8px 10px;
		text-decoration: none;
		border-radius: 2px;
	}
	</style>

	<?php
	header("Content-type: application/vnd-ms-excel");
	header("Content-Disposition: attachment; filename=$nama_file");
	?>


										<b>
										<h4>
										Laporan Pembelian<br> Periode <?php echo $tgl_pilih_dari; ?>&nbsp;s/d&nbsp;<?php echo $tgl_pilih_sampai; ?>
										</h4>
										</b> 

	<table border="1" cellspacing="0" style="width: 100%">
												<tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                                            <td bgcolor="gainsboro" width="9%"><b>No. Transaksi</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Cara Bayar</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>No. Pesanan</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>Tgl Pesanan</b></td>                                            
                                            <td bgcolor="gainsboro" width="10%"><b>Kode Supplier</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Nama Supplier</b></td>
                                            <td bgcolor="gainsboro" align="right" width="10%"><b>Total Akhir</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Keterangan</b></td>
												</tr>
		<?php 

$query = mysqli_query($koneksi,$sql_query);
		$no = 0;
                                        $tot_beli=0;
while($row = mysqli_fetch_array($query))
{
                                                $no++;
                        $tot_beli=$tot_beli+$row['total_akhir'];
                        
																						?>
<tr>
                                            <td align="center"><?php echo $no; ?></td>														
                                            <td align="center"><?php echo $row['notransaksi']?></td>														
                                            <td align="center"><?php echo $row['tanggal_trx']?></td>	
                                            <td align="center"><?php echo $row['carabayar']?></td>	    
                                            <td align="center"><?php echo $row['no_order']?></td>														
                                            <td align="center"><?php echo $row['tanggal_order']?></td>	                                        
                                            <td><?php echo $row['no_supplier']?></td>									
                                            <td><?php echo $row['namasupplier']?></td>
                                            <td align="right"><?php echo $row['total_akhir']?></td>
                                           <td><?php echo $row['note']?></td>														                                                                                                                
        </tr>



		<?php 
		}
		?>
                                        <tr>
                                            <td colspan="8" align="right" bgcolor="gainsboro"><b>Total : &nbsp;</b></td>														
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_beli; ?></b></td>
                                            <td bgcolor="gainsboro"></td>														                                                                                                                
                                        </tr>        
	</table>
</body>
</html>
