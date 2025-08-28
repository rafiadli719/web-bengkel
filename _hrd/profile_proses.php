<?php
	include "../config/koneksi.php";

	$folder="../file_upload/";
	$folder_save="file_upload/";
	
	$txtid= $_POST['txtid'];
	$txtuser= $_POST['txtuser'];

		$foto_save="";
		if(!empty($_FILES["id-input-file-3"]["tmp_name"])){
			$temp = $_FILES['id-input-file-3']['tmp_name'];
			$name = basename( $_FILES['id-input-file-3']['name']) ;
			$size = $_FILES['id-input-file-3']['size'];
			$type = $_FILES['id-input-file-3']['type'];
			$foto = $folder.$name;	
			
			move_uploaded_file($temp, $folder . $name);
			$foto_save=$folder_save.$name;
		}
		
		if($foto_save=='') {
			mysqli_query($koneksi,"UPDATE tbuser SET nama_user='$txtuser' WHERE id='$txtid'");
		} else {
			mysqli_query($koneksi,"UPDATE tbuser SET nama_user='$txtuser', foto_user='$foto_save' WHERE id='$txtid'");
		}
		echo"<script>window.alert('Profil User berhasil diubah! Silahkan login kembali');
        window.location=('../index.php');</script>";
?>