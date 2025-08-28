<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];		
        $kd_cabang=$_SESSION['_cabang'];        
		include "../config/koneksi.php";

        $search = $_GET['search'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Cari Barang/Part</title>

		<meta name="description" content="Search Barang/Part" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<!-- bootstrap & fontawesome -->
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<style>
			body { padding: 10px; }
			.search-result { cursor: pointer; }
			.search-result:hover { background-color: #f5f5f5; }
		</style>
	</head>

	<body class="no-skin">
		<div class="main-content">
			<div class="main-content-inner">
				<div class="page-content">
					<div class="row">
						<div class="col-xs-12">
							<h4>Cari Barang/Part</h4>
							
							<form method="get" class="form-inline" style="margin-bottom: 15px;">
								<div class="input-group">
									<input type="text" name="search" class="form-control" 
										   placeholder="Masukkan kode atau nama barang..." 
										   value="<?php echo $search; ?>" />
									<span class="input-group-btn">
										<button type="submit" class="btn btn-primary">
											<i class="fa fa-search"></i> Cari
										</button>
									</span>
								</div>
							</form>

							<div style="max-height: 400px; overflow-y: auto;">
								<table class="table table-striped table-bordered table-hover">
									<thead>
										<tr>
											<th width="20%">Kode Barang</th>
											<th width="50%">Nama Barang</th>
											<th width="15%">Satuan</th>
											<th width="15%">Harga</th>
										</tr>
									</thead>
									<tbody>
										<?php 
											if($search != '') {
												$sql = mysqli_query($koneksi,"SELECT noitem, namaitem, satuan, hargajual 
																			  FROM view_cari_item 
																			  WHERE (noitem LIKE '%$search%' OR namaitem LIKE '%$search%')
																			  ORDER BY noitem ASC 
																			  LIMIT 30");
											} else {
												$sql = mysqli_query($koneksi,"SELECT noitem, namaitem, satuan, hargajual 
																			  FROM view_cari_item 
																			  ORDER BY noitem ASC 
																			  LIMIT 30");
											}
											
											while ($tampil = mysqli_fetch_array($sql)) {
										?>
										<tr class="search-result" 
											onclick="selectBarang('<?php echo $tampil['noitem']; ?>', 
																 '<?php echo addslashes($tampil['namaitem']); ?>', 
																 '<?php echo $tampil['hargajual']; ?>')">
											<td><?php echo $tampil['noitem']; ?></td>
											<td><?php echo $tampil['namaitem']; ?></td>
											<td><?php echo $tampil['satuan']; ?></td>
											<td class="text-right"><?php echo number_format($tampil['hargajual'], 0, ',', '.'); ?></td>
										</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>

							<div class="text-center" style="margin-top: 10px;">
								<button type="button" class="btn btn-default" onclick="window.close();">
									<i class="fa fa-times"></i> Tutup
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script src="assets/js/jquery-2.1.4.min.js"></script>
		<script src="assets/js/bootstrap.min.js"></script>

		<script>
			function selectBarang(kode, nama, harga) {
				if(window.opener) {
					window.opener.document.getElementById('kode_barang').value = kode;
					window.opener.document.getElementById('nama_barang').value = nama;
					window.opener.document.getElementById('harga_barang').value = harga;
					window.close();
				}
			}
		</script>
	</body>
</html>

<?php 
	}
?>