<?php
	include "../config/koneksi.php";

	$stahun= $_GET['sthn'];
	$sbulan= $_GET['sbln'];	

	$cari_kd=mysqli_query($koneksi,"SELECT nama FROM bulan_transaksi WHERE id='$sbulan'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_bulan=$tm_cari['nama'];

    $nama_file="Laporan Absensi ".$nama_bulan." ".$stahun.".xls";    
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
										LAPORAN ABSENSI<br>
										<?php echo $nama_bulan; ?>&nbsp;<?php echo $stahun; ?>
										</h4>
										</b> 

	<table border="1" cellspacing="0" style="width: 100%">
												<tr>	
														<td align="center" width="5%"><b>No</b></td>
														<td align="center" width="10%"><b>NIK</b></td>
														<td width="20%"><b>Nama Karyawan</b></td>
														<td align="center" width="10%"><b>Jabatan</b></td>
														<td align="center" width="10%"><b>Divisi</b></td>
                                                        <td align="center" width="10%"><b>Total Hari Kerja</b></td>	
                                                        <td align="center" width="5%"><b>Izin</b></td>
                                                        <td align="center" width="5%"><b>Sakit</b></td>
                                                        <td align="center" width="5%"><b>Cuti</b></td>
                                                        <td align="center" width="10%"><b>Tanpa Keterangan</b></td>
                                                        <td align="center" width="10%"><b>Hadir (Hari Kerja)</b></td>                                                        
												</tr>
		<?php 

$query = mysqli_query($koneksi,"SELECT distinct(nip) as snip FROM view_absensi 
                                where bulan='$sbulan' and tahun='$stahun'");
		$no = 1;
while($row = mysqli_fetch_array($query))
{
                                                        $nip=$row['snip'];

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        nama, kode_jabatan, kode_divisi 
                                                                                        FROM tbpegawai 
                                                                                        where nip='$nip'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama=$tm_cari['nama'];
                                                        $kode_jabatan=$tm_cari['kode_jabatan'];
                                                        $kode_departemen=$tm_cari['kode_divisi'];

                                                                                            
                                                        $cari_kd=mysqli_query($koneksi,"SELECT nama_divisi FROM tbdivisi WHERE kode_divisi='$kode_departemen'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_divisi=$tm_cari['nama_divisi'];														
                                                                                            
                                                        $cari_kd=mysqli_query($koneksi,"SELECT nama_jabatan FROM tbjabatan WHERE kode_jabatan='$kode_jabatan'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_jabatan=$tm_cari['nama_jabatan'];

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and 
                                                                                    bulan='$sbulan' and tahun='$stahun'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_kerja=$tm_cari['jml'];	

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and 
                                                                                    bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='2'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_absen=$tm_cari['jml'];	
													
													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and 
                                                                                    bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='3'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_sakit=$tm_cari['jml'];					

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and 
                                                                                    bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='4'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_izin=$tm_cari['jml'];

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and 
                                                                                    bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='5'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_cuti=$tm_cari['jml'];
													
													$jml_hadir=$jml_kerja-($jml_absen+$jml_sakit+$jml_izin+$jml_cuti);														
                                                    
																						?>
<tr>
														<td align="center"><?php echo $no; ?></td>
														<td align="center"><?php echo $row['snip']?></td>
														<td><?php echo $nama; ?></td>
														<td align="center"><?php echo $nama_jabatan; ?></td>
														<td align="center"><?php echo $nama_divisi; ?></td>
                                                        
														<td align="center"><?php echo $jml_kerja; ?></td>
														<td align="center"><?php echo $jml_izin; ?></td>
														<td align="center"><?php echo $jml_sakit; ?></td>
														<td align="center"><?php echo $jml_cuti; ?></td>
														<td align="center"><?php echo $jml_absen; ?></td>
														<td align="center"><?php echo $jml_hadir; ?></td>
        </tr>



		<?php 
		$no++;
		}
		?>
	</table>
</body>
</html>
