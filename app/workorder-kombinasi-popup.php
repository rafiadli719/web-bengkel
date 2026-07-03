<?php
	session_start();
	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		include "../config/koneksi.php";

        $search = $_GET['search'] ?? '';
        $exclude = $_GET['exclude'] ?? '';
        $exclude_safe = mysqli_real_escape_string($koneksi, $exclude);
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<meta charset="utf-8" />
		<title>Cari Work Order (Kombinasi)</title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
		<link rel="stylesheet" href="assets/css/ace.min.css" class="ace-main-stylesheet" id="main-ace-style" />

		<style>
			body { padding: 10px; }
			.search-result { cursor: pointer; }
			.search-result:hover { background-color: #f5f5f5; }
			.disabled-row { color: #aaa; cursor: not-allowed; }
		</style>
	</head>

	<body class="no-skin">
		<div class="main-content">
			<div class="main-content-inner">
				<div class="page-content">
					<div class="row">
						<div class="col-xs-12">
							<h4>Cari Work Order untuk Dikombinasikan</h4>
							<p class="text-muted">Work Order yang sudah punya kombinasi WO lain di dalamnya tidak bisa dipilih (maksimal 1 level nested).</p>

							<form method="get" class="form-inline" style="margin-bottom: 15px;">
								<input type="hidden" name="exclude" value="<?php echo htmlspecialchars($exclude); ?>" />
								<div class="input-group">
									<input type="text" name="search" class="form-control"
										   placeholder="Masukkan kode atau nama work order..."
										   value="<?php echo htmlspecialchars($search); ?>" />
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
											<th width="15%">Kode WO</th>
											<th width="45%">Nama WO</th>
											<th width="20%">Harga</th>
											<th width="20%">Ket.</th>
										</tr>
									</thead>
									<tbody>
										<?php
											$where = "status='0' AND kode_wo != '$exclude_safe'";
											if($search != '') {
												$search_safe = mysqli_real_escape_string($koneksi, $search);
												$where .= " AND (kode_wo LIKE '%$search_safe%' OR nama_wo LIKE '%$search_safe%')";
											}
											$sql = mysqli_query($koneksi,"SELECT kode_wo, nama_wo, harga
																		  FROM tbworkorderheader
																		  WHERE $where
																		  ORDER BY kode_wo ASC
																		  LIMIT 30");

											while ($tampil = mysqli_fetch_array($sql)) {
												// Guard 1-level: WO ini gak boleh dipilih kalau dia sendiri sudah
												// punya baris kombinasi (tipe=3) di dalamnya.
												$chk = mysqli_query($koneksi,"SELECT COUNT(*) as c FROM tbworkorderdetail
																			   WHERE kode_wo='{$tampil['kode_wo']}' AND tipe='3'");
												$chk_data = mysqli_fetch_array($chk);
												$has_nested = ($chk_data['c'] > 0);
										?>
										<?php if($has_nested) { ?>
										<tr class="disabled-row">
											<td><?php echo $tampil['kode_wo']; ?></td>
											<td><?php echo $tampil['nama_wo']; ?> <span class="label label-default">Sudah berisi kombinasi WO lain</span></td>
											<td class="text-right"><?php echo number_format($tampil['harga'], 0, ',', '.'); ?></td>
											<td>Tidak bisa dipilih</td>
										</tr>
										<?php } else { ?>
										<tr class="search-result"
											onclick="selectWO('<?php echo $tampil['kode_wo']; ?>',
															   '<?php echo addslashes($tampil['nama_wo']); ?>',
															   '<?php echo $tampil['harga']; ?>')">
											<td><?php echo $tampil['kode_wo']; ?></td>
											<td><?php echo $tampil['nama_wo']; ?></td>
											<td class="text-right"><?php echo number_format($tampil['harga'], 0, ',', '.'); ?></td>
											<td></td>
										</tr>
										<?php } ?>
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
			function selectWO(kode, nama, harga) {
				if(window.opener) {
					window.opener.document.getElementById('kode_wo_kombinasi').value = kode;
					window.opener.document.getElementById('nama_wo_kombinasi').value = nama;
					window.opener.document.getElementById('harga_wo_kombinasi').value = harga;
					window.close();
				}
			}
		</script>
	</body>
</html>

<?php
	}
?>
