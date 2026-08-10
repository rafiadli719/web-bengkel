<?php
	session_start();
	include "../config/koneksi.php";

	$stahun= $_GET['sthn'];
	$sbulan= $_GET['sbln'];	

	$cari_kd=mysqli_query($koneksi,"SELECT nama FROM bulan_transaksi WHERE id='$sbulan'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_bulan=$tm_cari['nama'];

    $nama_file="Laporan Absensi ".$nama_bulan." ".$stahun.".pdf";
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,"SELECT distinct(nip) as snip FROM view_absensi 
                                    where bulan='$sbulan' and tahun='$stahun'");

	
	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
					<tr>
						<td align="center"><b>LAPORAN ABSENSI<br>Periode '.$nama_bulan.' '.$stahun.'</b></td>
					</tr>
				</table>
				<br>
				<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">
												<tr>																			
														<td class="center" width="5%"><b>No</b></td>
														<td class="center" width="10%"><b>NIK</b></td>
														<td width="20%"><b>Nama Karyawan</b></td>
														<td class="center" width="10%"><b>Jabatan</b></td>
														<td class="center" width="10%"><b>Divisi</b></td>
                                                        <td align="center" width="10%"><b>Total Hari Kerja</b></td>	
                                                        <td align="center" width="5%"><b>Izin</b></td>
                                                        <td align="center" width="5%"><b>Sakit</b></td>
                                                        <td align="center" width="5%"><b>Cuti</b></td>
                                                        <td align="center" width="10%"><b>Tanpa Keterangan</b></td>
                                                        <td align="center" width="10%"><b>Hadir (Hari Kerja)</b></td>                                                        
												</tr>';
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
    $html .= "<tr>
														<td align=center>".$no."</td>
														<td align=center>".$row['snip']."</td>
														<td>".$nama."</td>
														<td align=center>".$nama_jabatan."</td>
														<td align=center>".$nama_divisi."</td>                                                        
														<td align=center>".$jml_kerja."</td>
														<td align=center>".$jml_izin."</td>
														<td align=center>".$jml_sakit."</td>
														<td align=center>".$jml_cuti."</td>
														<td align=center>".$jml_absen."</td>
														<td align=center>".$jml_hadir."</td>
        </tr>";
    $no++;
}
$html .= "</html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream($nama_file);
?>
