<?php
    session_start();
    if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
    include "../config/koneksi.php";

    date_default_timezone_set('Asia/Jakarta');
    $waktuaja_skr=date('h:i');
    function ubahformatTgl($tanggal) {
        $pisah = explode('/',$tanggal);
        $urutan = array($pisah[2],$pisah[1],$pisah[0]);
        $satukan = implode('-',$urutan);
        return $satukan;
    }

	$tgl_pilih_dari= mysqli_real_escape_string($koneksi, $_GET['stgl1']);
	$tgl_pilih_sampai= mysqli_real_escape_string($koneksi, $_GET['stgl2']);
	$nopelanggan= mysqli_real_escape_string($koneksi, $_GET['ssup']);

    $tglmulai = ubahformatTgl($_GET['stgl1']);
    $tglselesai = ubahformatTgl($_GET['stgl2']);

            if($nopelanggan=='') {
            // ---- SQL Hasil Data -----
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx
                FROM view_penjualan_header
                                    WHERE
                                    (tanggal>='$tglmulai' AND
                                    tanggal<='$tglselesai')
                                    ORDER BY tanggal, notransaksi";
            } else {
            // ---- SQL Hasil Data -----
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx
                FROM view_penjualan_header
                                    WHERE
                                    (tanggal>='$tglmulai' AND
                                    tanggal<='$tglselesai') AND
                                    ((no_pelanggan like '%".$nopelanggan."%') OR
                                    (namapelanggan like '%".$nopelanggan."%'))
                                    ORDER BY tanggal, notransaksi";
            }

    $nama_file="Laporan Penjualan ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".xls";
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
	</style>

	<?php
	header("Content-type: application/vnd-ms-excel");
	header("Content-Disposition: attachment; filename=$nama_file");
	?>

											<b>
											<h4>
											Laporan Penjualan<br> Periode <?php echo $tgl_pilih_dari; ?>&nbsp;s/d&nbsp;<?php echo $tgl_pilih_sampai; ?>
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
                                            <td bgcolor="gainsboro" width="10%"><b>Kode Pelanggan</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Nama Pelanggan</b></td>
                                            <td bgcolor="gainsboro" align="right" width="10%"><b>Total Akhir</b></td>
                                            <td bgcolor="gainsboro" width="15%"><b>Keterangan</b></td>
												</tr>
		<?php

$query = mysqli_query($koneksi,$sql_query);
		$no = 0;
                                    $tot_jual=0;
while($row = mysqli_fetch_array($query))
{
                                            $no++;
                    $tot_jual=$tot_jual+$row['total_akhir'];

																						?>
<tr>
                                            <td align="center"><?php echo $no; ?></td>
                                            <td align="center"><?php echo $row['notransaksi']?></td>
                                            <td align="center"><?php echo $row['tanggal_trx']?></td>
                                            <td align="center"><?php echo $row['carabayar']?></td>
                                            <td align="center"><?php echo $row['no_order']?></td>
                                            <td align="center"><?php echo $row['tanggal_order']?></td>
                                            <td><?php echo $row['no_pelanggan']?></td>
                                            <td><?php echo $row['namapelanggan']?></td>
                                            <td align="right"><?php echo $row['total_akhir']?></td>
                                           <td><?php echo $row['note']?></td>
        </tr>

		<?php
		}
		?>
                                    <tr>
                                        <td colspan="8" align="right" bgcolor="gainsboro"><b>Total : &nbsp;</b></td>
                                        <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jual; ?></b></td>
                                        <td bgcolor="gainsboro"></td>
                                    </tr>
	</table>
</body>
</html>
