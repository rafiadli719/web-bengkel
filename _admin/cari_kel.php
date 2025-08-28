<?php
	include "../config/koneksi.php";
	error_reporting(E_ALL ^ E_DEPRECATED);

	$sql = mysqli_query($koneksi,"select kode, nama FROM tbl_adm where left(kode,8)='".$_POST["kec"]."' and length(kode)='13'");
	while($data_prov = mysqli_fetch_array($sql)){
?>
	<option value="<?php echo $data_prov["kode"] ?>"><?php echo $data_prov["nama"] ?></option><br>
<?php
	}
?>