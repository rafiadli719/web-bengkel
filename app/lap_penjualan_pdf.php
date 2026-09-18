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
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx FROM view_penjualan_header
                                    WHERE
                                    (tanggal>='$tglmulai' AND
                                    tanggal<='$tglselesai')
                                    ORDER BY tanggal, notransaksi";
            } else {
            // ---- SQL Hasil Data -----
                $sql_query="SELECT *, DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx FROM view_penjualan_header
                                    WHERE
                                    (tanggal>='$tglmulai' AND
                                    tanggal<='$tglselesai') AND
                                    ((no_pelanggan like '%".$nopelanggan."%') OR
                                    (namapelanggan like '%".$nopelanggan."%'))
                                    ORDER BY tanggal, notransaksi";
            }


    $nama_file="Laporan Penjualan ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".pdf";

	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();
	$query = mysqli_query($koneksi,$sql_query);

	$html = '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
                <tr>
                    <td align="center"><b>Laporan Penjualan <br>Periode '.$tgl_pilih_dari.' s/d '.$tgl_pilih_sampai.'</b></td>
                </tr>
            </table>
            <br>
            <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">
                <tr>
                    <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
                    <td bgcolor="gainsboro" width="9%"><b>No. Transaksi</b></td>
                    <td bgcolor="gainsboro" align="center" width="9%"><b>Tanggal</b></td>
                    <td bgcolor="gainsboro" align="center" width="9%"><b>Cara Bayar</b></td>
                    <td bgcolor="gainsboro" width="10%"><b>Kode Pelanggan</b></td>
                    <td bgcolor="gainsboro" width="20%"><b>Nama Pelanggan</b></td>
                    <td bgcolor="gainsboro" align="right" width="10%"><b>Total Akhir</b></td>
                    <td bgcolor="gainsboro" width="15%"><b>Keterangan</b></td>
                </tr>';

                $no = 1;
                $tot_jual=0;
                while($row = mysqli_fetch_array($query))
                    {
                        $tot_jual=$tot_jual+$row['total_akhir'];

            $html .= "<tr>
                <td align=center>".$no."</td>
                <td align=center>".$row['notransaksi']."</td>
                <td align=center>".$row['tanggal_trx']."</td>
                <td align=center>".$row['carabayar']."</td>
                <td>".$row['no_pelanggan']."</td>
                <td>".$row['namapelanggan']."</td>
                <td align=right>".number_format($row['total_akhir'])."</td>
                <td>".$row['note']."</td>
            </tr>";
            $no++;
            }

            $html .= "<tr>
                <td colspan=6 align=right>Total : &nbsp;</td>
                <td align=right>".number_format($tot_jual)."</td>
                <td></td>
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
