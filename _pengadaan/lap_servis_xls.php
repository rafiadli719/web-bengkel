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
	$nopelanggan= $_GET['ssup'];	
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            if($nopelanggan=='') {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT * FROM view_service 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') 
                                    ORDER BY tanggal, no_service";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_service 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             
            } else {
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT * FROM view_service 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                                    (no_pelanggan like '%".$nopelanggan."%') OR 
                                    (namapelanggan like '%".$nopelanggan."%') 
                                    ORDER BY tanggal, no_service";                   

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM view_service 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                                                (no_pelanggan like '%".$nopelanggan."%') OR 
                                    (namapelanggan like '%".$nopelanggan."%')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";                
            }
            

    $nama_file="Laporan Service ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".xls";
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
										Laporan Service<br> Periode <?php echo $tgl_pilih_dari; ?>&nbsp;s/d&nbsp;<?php echo $tgl_pilih_sampai; ?>
										</h4>
										</b> 

	<table border="1" cellspacing="0" style="width: 100%">
												<tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                                            <td bgcolor="gainsboro" align="center" width="10%"><b>No. Service</b></td>
                                            <td bgcolor="gainsboro" align="center" width="10%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" width="10%"><b>No. Polisi</b></td>
                                            <td bgcolor="gainsboro" width="20%"><b>Nama Pelanggan</b></td>
                                            <td bgcolor="gainsboro" width="15%" align="right"><b>Barang</b></td>
                                            <td bgcolor="gainsboro" width="15%" align="right"><b>Jasa Service</b></td>
                                            <td bgcolor="gainsboro" width="15%" align="right"><b>Total</b></td>
												</tr>
		<?php 

$query = mysqli_query($koneksi,$sql_query);
		$no = 0;
                                        $tot_brg=0;
                                        $tot_jasa=0;
                                        $tot_jual=0;                                        
while($row = mysqli_fetch_array($query))
{
                                                $no++;
$no_service=$row['no_service'];
                                            
                                            $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                            sum(total) as tot 
                                                                            FROM 
                                                                            tblservis_barang 
                                                                            WHERE no_service='$no_service'");			
                                            $tm_cari=mysqli_fetch_array($cari_kd);
                                            $harga_brg=$tm_cari['tot'];				        
                                            $tot_brg=$tot_brg+$harga_brg;

                                            $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                            sum(total) as tot 
                                                                            FROM 
                                                                            tblservis_jasa 
                                                                            WHERE no_service='$no_service'");			
                                            $tm_cari=mysqli_fetch_array($cari_kd);
                                            $harga_jasa=$tm_cari['tot'];				        
                                            $tot_jasa=$tot_jasa+$harga_jasa;
                                            
                                            $harga_servis=$harga_brg+$harga_jasa;
                                            $tot_jual=$tot_jual+$harga_servis;
                        
																						?>
<tr>
                                            <td align="center"><?php echo $no; ?></td>														
                                            <td align="center"><?php echo $row['no_service']?></td>														
                                            <td align="center"><?php echo $row['tanggal_trx']?></td>	
                                            <td><?php echo $row['no_polisi']?></td>									
                                            <td><?php echo $row['namapelanggan']?></td>
                                            <td align="right"><?php echo $harga_brg?></td>
                                            <td align="right"><?php echo $harga_jasa?></td>
                                            <td align="right"><?php echo $harga_servis?></td>                                            
        </tr>



		<?php 
		}
		?>
                                        <tr>
                                            <td colspan="5" align="right" bgcolor="gainsboro"><b>Total : &nbsp;</b></td>														
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_brg; ?></b></td>
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jasa; ?></b></td>
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jual; ?></b></td>                                            
                                        </tr>        
	</table>
</body>
</html>
