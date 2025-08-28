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
    
    $tglmulai = ubahformatTgl($_GET['stgl1']); 
    $tglselesai = ubahformatTgl($_GET['stgl2']); 
                
            // ---- SQL Hasil Data ----- 
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx 
                FROM tbitem_masuk_header 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') 
                                    ORDER BY tanggal, no_transaksi";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM tbitem_masuk_header 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai')");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";             
            

    $nama_file="Laporan Penyesuaian Stok (Item Masuk) ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".xls";
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
										Laporan Penyesuaian Stok (Item Masuk)<br> Periode <?php echo $tgl_pilih_dari; ?>&nbsp;s/d&nbsp;<?php echo $tgl_pilih_sampai; ?>
										</h4>
										</b> 

	<table border="1" cellspacing="0" style="width: 100%">
												<tr>																			
                                            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                                            <td bgcolor="gainsboro" width="10%"><b>No. Transaksi</b></td>
                                            <td bgcolor="gainsboro" align="center" width="10%"><b>Tanggal</b></td>                                            
                                            <td bgcolor="gainsboro" width="75%"><b>Keterangan</b></td>
												</tr>
		<?php 

$query = mysqli_query($koneksi,$sql_query);
		$no = 0;
while($row = mysqli_fetch_array($query))
{
                                                $no++;
                        
																						?>
<tr>
                                            <td align="center"><?php echo $no; ?></td>														
                                            <td><?php echo $row['no_transaksi']?></td>														
                                            <td align="center"><?php echo $row['tanggal_trx']?></td>	
                                            <td><?php echo $row['note']?></td>									
        </tr>



		<?php 
		}
		?>

	</table>
</body>
</html>
