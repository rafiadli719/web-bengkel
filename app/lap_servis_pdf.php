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
            

    $nama_file="Laporan Service ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".pdf";
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,$sql_query);
	
	// Preload barang & jasa totals: 2 query, bukan N×2
    $_preload_brg = [];
    $q_pb = mysqli_query($koneksi, "SELECT sb.no_service, ts.kd_cabang, SUM(sb.total) as tot FROM tblservis_barang sb INNER JOIN tblservice ts ON sb.no_service=ts.no_service WHERE ts.tanggal BETWEEN '$tglmulai' AND '$tglselesai' GROUP BY sb.no_service, ts.kd_cabang");
    while ($r = mysqli_fetch_assoc($q_pb)) $_preload_brg[$r['no_service'].'|'.$r['kd_cabang']] = (float)$r['tot'];

    $_preload_jsa = [];
    $q_pj = mysqli_query($koneksi, "SELECT sj.no_service, ts.kd_cabang, SUM(sj.total) as tot FROM tblservis_jasa sj INNER JOIN tblservice ts ON sj.no_service=ts.no_service WHERE ts.tanggal BETWEEN '$tglmulai' AND '$tglselesai' GROUP BY sj.no_service, ts.kd_cabang");
    while ($r = mysqli_fetch_assoc($q_pj)) $_preload_jsa[$r['no_service'].'|'.$r['kd_cabang']] = (float)$r['tot'];

	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
                <tr>
                    <td align="center"><b>Laporan Service <br>Periode '.$tgl_pilih_dari.' s/d '.$tgl_pilih_sampai.'</b></td>
                </tr>
            </table>
            <br>
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">											
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
                </tr>';
            
                $no = 1;
                                        $tot_brg=0;
                                        $tot_jasa=0;
                                        $tot_jual=0;                                        
                while($row = mysqli_fetch_array($query))
                    {
                                            $no_service=$row['no_service'];
                                            $_key_svc = $no_service.'|'.$row['kd_cabang'];

                                            $harga_brg = $_preload_brg[$_key_svc] ?? 0;
                                            $tot_brg=$tot_brg+$harga_brg;

                                            $harga_jasa = $_preload_jsa[$_key_svc] ?? 0;
                                            $tot_jasa=$tot_jasa+$harga_jasa;

                                            $harga_servis=$harga_brg+$harga_jasa;
                                            $tot_jual=$tot_jual+$harga_servis;

														
            $nama_mekanik_pdf = htmlspecialchars(getMekanikNamaGabung($koneksi, $row['mekanik1'], $row['mekanik2'], $row['mekanik3'], $row['mekanik4']));
            $html .= "<tr>
                <td align=center>".$no."</td>
                <td align=center>".$row['no_service']."</td>
                <td align=center>".$row['tanggal_trx']."</td>
                <td>".$row['no_polisi']."</td>
                <td>".$row['namapelanggan']."</td>
                <td>".$nama_mekanik_pdf."</td>
                <td align=right>".number_format($harga_brg)."</td>
                <td align=right>".number_format($harga_jasa)."</td>
                <td align=right>".number_format($harga_servis)."</td>
            </tr>";
            $no++;
            }

            $html .= "<tr>
                <td colspan=6 align=right>Total : &nbsp;</td>
                <td align=right>".number_format($tot_brg)."</td>
                <td align=right>".number_format($tot_jasa)."</td>
                <td align=right>".number_format($tot_jual)."</td>
            </tr>";
            
$html .= "</table></html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream($nama_file);
?>
