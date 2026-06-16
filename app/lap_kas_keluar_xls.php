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
                FROM tblkas_keluar_masuk 
                                    WHERE 
                                    (tanggal>='$tglmulai' AND 
                                    tanggal<='$tglselesai') AND 
                            jenis='Keluar' 
                                    ORDER BY tanggal, kode_km";      

            // ---- SQL Total Data -----                            
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                count(*) as tot 
                                                FROM tblkas_keluar_masuk 
                                                WHERE 
                                                (tanggal>='$tglmulai' AND 
                                                tanggal<='$tglselesai') AND 
                            jenis='Keluar'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $tot=$tm_cari['tot'];  
                $hasil_cari="Hasil Pencarian ditemukan ".$tot." data";                         

    $nama_file="Laporan Kas Keluar ".$tgl_pilih_dari." s/d ".$tgl_pilih_sampai.".xls";
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
            Laporan Kas Keluar<br> Periode <?php echo $tgl_pilih_dari; ?>&nbsp;s/d&nbsp;<?php echo $tgl_pilih_sampai; ?>
        </h4>
    </b> 

	<table border="1" cellspacing="0" style="width: 100%">
        <tr>		
            <td bgcolor="gainsboro" align="center" width="5%"><b>No</b></td>
            <td bgcolor="gainsboro" width="10%"><b>No. Bukti</b></td>
            <td bgcolor="gainsboro" align="center" width="10%"><b>Tanggal</b></td>
            <td bgcolor="gainsboro" width="30%"><b>Keterangan</b></td>
            <td bgcolor="gainsboro" align="right" width="10%"><b>Jumlah</b></td>
            <td bgcolor="gainsboro" width="15%"><b>Akun Kas</b></td> 
            <td bgcolor="gainsboro" width="20%"><b>Akun Biaya</b></td> 
        </tr>
		<?php 
            $query = mysqli_query($koneksi,$sql_query);
            $no = 0;
            $tot_jml=0;
            while($row = mysqli_fetch_array($query))
            {
                $no++;
                $kode_akun=$row['kode_akun'];
                $kode_akun_biaya=$row['kode_akun_biaya'];

                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                namaakun 
                                                FROM tblakunkas 
                                                WHERE kodeakun='$kode_akun'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $namaakun=$tm_cari['namaakun'];				                                                                
                
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                nama_akun 
                                                FROM tbakun 
                                                WHERE no_akun='$kode_akun_biaya'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $namaakun_biaya=$tm_cari['nama_akun'];				                                                                
                
                $tot_jml=$tot_jml+$row['keluar'];
                        
        ?>
        <tr>
            <td align="center"><?php echo $no; ?></td>														
            <td><?php echo $row['kode_km']?></td>														
            <td align="center"><?php echo $row['tanggal_trx']?></td>				
            <td><?php echo $row['uraian']?></td>				   
            <td align="right"><?php echo $row['keluar']; ?></td>						
            <td><?php echo $namaakun; ?></td>
            <td>
                <?php echo $row['kode_akun_biaya']?>&nbsp;-&nbsp;
                <?php echo $namaakun_biaya; ?>
            </td>																																		
        </tr>

		<?php 
		}
		?>
        
        <tr>
            <td colspan="4" align="right" bgcolor="gainsboro"><b>Total : &nbsp;</b></td>														
            <td align="right" bgcolor="gainsboro"><b><?php echo $tot_jml; ?></b></td>
            <td bgcolor="gainsboro"></td>														                                                                                                                
            <td bgcolor="gainsboro"></td>
        </tr>        
	</table>
</body>
</html>
