<?php
    session_start();
    if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
    include "../config/koneksi.php";
    include "helper-functions.php";

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
                
            $nopelanggan_esc = isset($nopelanggan) ? mysqli_real_escape_string($koneksi, $nopelanggan) : '';
            if($nopelanggan=='') {
            // ---- SQL Hasil Data -----
                $sql_query="SELECT vs.*, ts.mekanik1, ts.mekanik2, ts.mekanik3, ts.mekanik4
                            FROM view_service vs
                            LEFT JOIN tblservice ts ON vs.no_service = ts.no_service AND ts.kd_cabang = vs.kd_cabang
                            WHERE (vs.tanggal>='$tglmulai' AND vs.tanggal<='$tglselesai')
                            ORDER BY vs.tanggal, vs.no_service";

            // ---- SQL Total Data -----
                $cari_kd=mysqli_query($koneksi,"SELECT count(*) as tot FROM view_service
                                                WHERE (tanggal>='$tglmulai' AND tanggal<='$tglselesai')");
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";
            } else {
            // ---- SQL Hasil Data -----
                $sql_query="SELECT vs.*, ts.mekanik1, ts.mekanik2, ts.mekanik3, ts.mekanik4
                            FROM view_service vs
                            LEFT JOIN tblservice ts ON vs.no_service = ts.no_service AND ts.kd_cabang = vs.kd_cabang
                            WHERE (vs.tanggal>='$tglmulai' AND vs.tanggal<='$tglselesai') AND
                            (vs.no_pelanggan LIKE '%$nopelanggan_esc%' OR vs.namapelanggan LIKE '%$nopelanggan_esc%')
                            ORDER BY vs.tanggal, vs.no_service";

            // ---- SQL Total Data -----
                $cari_kd=mysqli_query($koneksi,"SELECT count(*) as tot FROM view_service
                                                WHERE (tanggal>='$tglmulai' AND tanggal<='$tglselesai') AND
                                                (no_pelanggan LIKE '%$nopelanggan_esc%' OR namapelanggan LIKE '%$nopelanggan_esc%')");
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
                                            <td bgcolor="gainsboro" align="center" width="4%"><b>No</b></td>
                                            <td bgcolor="gainsboro" align="center" width="9%"><b>No. Service</b></td>
                                            <td bgcolor="gainsboro" align="center" width="8%"><b>Tanggal</b></td>
                                            <td bgcolor="gainsboro" width="8%"><b>No. Polisi</b></td>
                                            <td bgcolor="gainsboro" width="17%"><b>Nama Pelanggan</b></td>
                                            <td bgcolor="gainsboro" width="16%"><b>Mekanik</b></td>
                                            <td bgcolor="gainsboro" width="12%" align="right"><b>Barang</b></td>
                                            <td bgcolor="gainsboro" width="12%" align="right"><b>Jasa Service</b></td>
                                            <td bgcolor="gainsboro" width="14%" align="right"><b>Total</b></td>
												</tr>
		<?php 

// Preload barang & jasa totals: 2 query, bukan N×2
$_preload_brg = [];
$q_pb = mysqli_query($koneksi, "SELECT sb.no_service, ts.kd_cabang, SUM(sb.total) as tot FROM tblservis_barang sb INNER JOIN tblservice ts ON sb.no_service=ts.no_service WHERE ts.tanggal BETWEEN '$tglmulai' AND '$tglselesai' GROUP BY sb.no_service, ts.kd_cabang");
while ($r = mysqli_fetch_assoc($q_pb)) $_preload_brg[$r['no_service'].'|'.$r['kd_cabang']] = (float)$r['tot'];

$_preload_jsa = [];
$q_pj = mysqli_query($koneksi, "SELECT sj.no_service, ts.kd_cabang, SUM(sj.total) as tot FROM tblservis_jasa sj INNER JOIN tblservice ts ON sj.no_service=ts.no_service WHERE ts.tanggal BETWEEN '$tglmulai' AND '$tglselesai' GROUP BY sj.no_service, ts.kd_cabang");
while ($r = mysqli_fetch_assoc($q_pj)) $_preload_jsa[$r['no_service'].'|'.$r['kd_cabang']] = (float)$r['tot'];

$query = mysqli_query($koneksi,$sql_query);
		$no = 0;
                                        $tot_brg=0;
                                        $tot_jasa=0;
                                        $tot_jual=0;
while($row = mysqli_fetch_array($query))
{
                                                $no++;
$no_service=$row['no_service'];
$_key_svc = $no_service.'|'.$row['kd_cabang'];

                                            $harga_brg = $_preload_brg[$_key_svc] ?? 0;
                                            $tot_brg=$tot_brg+$harga_brg;

                                            $harga_jasa = $_preload_jsa[$_key_svc] ?? 0;
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
                                            <td><?php echo htmlspecialchars(getMekanikNamaGabung($koneksi, $row['mekanik1'], $row['mekanik2'], $row['mekanik3'], $row['mekanik4'])); ?></td>
                                            <td align="right"><?php echo $harga_brg?></td>
                                            <td align="right"><?php echo $harga_jasa?></td>
                                            <td align="right"><?php echo $harga_servis?></td>
        </tr>



		<?php 
		}
		?>
                                        <tr>
                                            <td colspan="6" align="right" bgcolor="gainsboro"><b>Total : &nbsp;</b></td>														
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_brg; ?></b></td>
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jasa; ?></b></td>
                                            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jual; ?></b></td>                                            
                                        </tr>        
	</table>
</body>
</html>
