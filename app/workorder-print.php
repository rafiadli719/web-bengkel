<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";
        
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_user, password, user_akses, foto_user 
                                        FROM tbuser WHERE id='$id_user'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$_nama=$tm_cari['nama_user'];				        

    // ------- Data Cabang ----------
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                        nama_cabang, tipe_cabang 
                                        FROM tbcabang 
                                        WHERE kode_cabang='$kd_cabang'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang=$tm_cari['nama_cabang'];				        
    // --------------------

        $kode_wo = $_GET['kode'] ?? '';
        
        // Get work order data
        $cari_kd = mysqli_query($koneksi,"SELECT * FROM tbworkorderheader WHERE kode_wo='$kode_wo'");
        if(mysqli_num_rows($cari_kd) == 0) {
            echo"<script>window.alert('Work order tidak ditemukan!');
            window.close();</script>";
            exit;
        }
        
        $tm_cari = mysqli_fetch_array($cari_kd);
        $nama_wo = $tm_cari['nama_wo'];
        $keterangan = $tm_cari['keterangan'];
        $total_waktu = $tm_cari['waktu'];
        $total_harga = $tm_cari['harga'];
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Print Work Order - <?php echo $kode_wo; ?></title>

		<meta name="description" content="Print Work Order" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />

		<style>
			@media print {
				.no-print { display: none !important; }
				body { background: white; }
			}
			
			body { 
				background: white; 
				font-family: Arial, sans-serif;
				font-size: 12px;
			}
			
			.print-header {
				text-align: center;
				border-bottom: 2px solid #333;
				margin-bottom: 20px;
				padding-bottom: 10px;
			}
			
			.info-table {
				width: 100%;
				margin-bottom: 15px;
			}
			
			.info-table td {
				padding: 5px;
				border: 1px solid #ddd;
			}
			
			.detail-table {
				width: 100%;
				border-collapse: collapse;
				margin-bottom: 15px;
			}
			
			.detail-table th,
			.detail-table td {
				padding: 8px;
				border: 1px solid #333;
				text-align: left;
			}
			
			.detail-table th {
				background-color: #f5f5f5;
				font-weight: bold;
			}
			
			.text-center { text-align: center; }
			.text-right { text-align: right; }
			.text-bold { font-weight: bold; }
		</style>
	</head>

	<body>
		<div class="container-fluid">
			<!-- Print Controls -->
			<div class="no-print" style="margin-bottom: 20px; text-align: center;">
				<button onclick="window.print()" class="btn btn-primary">
					<i class="fa fa-print"></i> Print
				</button>
				<button onclick="window.close()" class="btn btn-default">
					<i class="fa fa-times"></i> Tutup
				</button>
			</div>

			<!-- Print Content -->
			<div class="print-header">
				<h2><?php echo $nama_cabang; ?></h2>
				<h3>WORK ORDER (PAKET SERVIS)</h3>
				<p style="margin: 5px 0;">Tanggal Print: <?php echo date('d/m/Y H:i:s'); ?></p>
			</div>

			<!-- Work Order Info -->
			<table class="info-table">
				<tr>
					<td width="20%" class="text-bold">Kode Work Order</td>
					<td width="30%"><?php echo $kode_wo; ?></td>
					<td width="20%" class="text-bold">Total Waktu</td>
					<td width="30%"><?php echo $total_waktu; ?> Menit</td>
				</tr>
				<tr>
					<td class="text-bold">Nama Work Order</td>
					<td><?php echo $nama_wo; ?></td>
					<td class="text-bold">Total Harga</td>
					<td>Rp <?php echo number_format($total_harga, 0, ',', '.'); ?></td>
				</tr>
				<tr>
					<td class="text-bold">Keterangan</td>
					<td colspan="3"><?php echo $keterangan; ?></td>
				</tr>
			</table>

			<!-- Jasa Service -->
			<h4>JASA SERVICE</h4>
			<table class="detail-table">
				<thead>
					<tr>
						<th width="5%">No</th>
						<th width="15%">Kode</th>
						<th width="50%">Nama Jasa</th>
						<th width="15%">Waktu</th>
						<th width="15%">Harga</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 0;
					$total_jasa = 0;
					$total_waktu_jasa = 0;
					$sql = mysqli_query($koneksi,"SELECT d.*, w.nama_wo, w.waktu 
												 FROM tbworkorderdetail d
												 LEFT JOIN tbworkorderheader w ON d.kode_barang = w.kode_wo
												 WHERE d.kode_wo='$kode_wo' AND d.tipe='1'
												 ORDER BY d.id ASC");
					while($tampil = mysqli_fetch_array($sql)) {
						$no++;
						$total_jasa += $tampil['total'];
						$total_waktu_jasa += $tampil['waktu'];
					?>
					<tr>
						<td class="text-center"><?php echo $no; ?></td>
						<td><?php echo $tampil['kode_barang']; ?></td>
						<td><?php echo $tampil['nama_wo']; ?></td>
						<td class="text-center"><?php echo $tampil['waktu']; ?> mnt</td>
						<td class="text-right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
					</tr>
					<?php } ?>
					<tr style="background-color: #f5f5f5;">
						<td colspan="3" class="text-center text-bold">TOTAL JASA</td>
						<td class="text-center text-bold"><?php echo $total_waktu_jasa; ?> mnt</td>
						<td class="text-right text-bold"><?php echo number_format($total_jasa, 0, ',', '.'); ?></td>
					</tr>
					<?php if($no == 0) { ?>
					<tr>
						<td colspan="5" class="text-center"><em>Tidak ada jasa service</em></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>

			<!-- Barang/Part -->
			<h4>BARANG/PART</h4>
			<table class="detail-table">
				<thead>
					<tr>
						<th width="5%">No</th>
						<th width="15%">Kode</th>
						<th width="45%">Nama Barang</th>
						<th width="10%">Qty</th>
						<th width="12%">Harga</th>
						<th width="13%">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$no = 0;
					$total_barang = 0;
					$sql = mysqli_query($koneksi,"SELECT d.*, v.namaitem 
												 FROM tbworkorderdetail d
												 LEFT JOIN view_cari_item v ON d.kode_barang = v.noitem
												 WHERE d.kode_wo='$kode_wo' AND d.tipe='2'
												 ORDER BY d.id ASC");
					while($tampil = mysqli_fetch_array($sql)) {
						$no++;
						$total_barang += $tampil['total'];
					?>
					<tr>
						<td class="text-center"><?php echo $no; ?></td>
						<td><?php echo $tampil['kode_barang']; ?></td>
						<td><?php echo $tampil['namaitem']; ?></td>
						<td class="text-center"><?php echo $tampil['jumlah']; ?></td>
						<td class="text-right"><?php echo number_format($tampil['harga'], 0, ',', '.'); ?></td>
						<td class="text-right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
					</tr>
					<?php } ?>
					<tr style="background-color: #f5f5f5;">
						<td colspan="5" class="text-center text-bold">TOTAL BARANG</td>
						<td class="text-right text-bold"><?php echo number_format($total_barang, 0, ',', '.'); ?></td>
					</tr>
					<?php if($no == 0) { ?>
					<tr>
						<td colspan="6" class="text-center"><em>Tidak ada barang/part</em></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>

			<!-- Grand Total -->
			<table class="detail-table" style="background-color: #f0f0f0;">
				<tr>
					<td width="70%" class="text-center text-bold" style="font-size: 14px;">
						GRAND TOTAL WORK ORDER
					</td>
					<td width="30%" class="text-right text-bold" style="font-size: 14px;">
						Rp <?php echo number_format($total_jasa + $total_barang, 0, ',', '.'); ?>
					</td>
				</tr>
			</table>

			<!-- Footer -->
			<div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
				<p>Work Order ini dicetak pada <?php echo date('d/m/Y H:i:s'); ?> oleh <?php echo $_nama; ?></p>
				<p><?php echo $nama_cabang; ?> - Sistem Manajemen Servis</p>
			</div>
		</div>

		<script>
			// Auto print when page loads (optional)
			// window.onload = function() { window.print(); };
		</script>
	</body>
</html>

<?php 
	}
?>