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
	$tgl_input=date('Y/m/d');
	$folder="../file_upload/";
	$folder_save="file_upload/";

	$txttgllhr = ubahformatTgl($_POST['id-date-picker-1']); 
	$txttglmasuk = ubahformatTgl($_POST['id-date-picker-2']); 

	$txtnip= $_POST['txtnip'];
	$txtnama= $_POST['txtnama'];
	$cbojk= $_POST['cbojk'];
	$cbostatus= $_POST['cbostatus'];
	$txttempatlhr= $_POST['txttempatlhr'];
	$cboagama= $_POST['cboagama'];
	$cbodarah= $_POST['cbodarah'];
	$cbopendidikan= $_POST['cbopendidikan'];
	$txtemail= $_POST['txtemail'];
	$txtktp= $_POST['txtktp'];
	$txtnpwp= $_POST['txtnpwp'];	
	$txttlprumah= $_POST['txttlprumah'];	
	$txttlp= $_POST['txttlp'];
	
	$txtalamat= $_POST['txtalamat'];
	$txtdistrict= $_POST['txtdistrict'];
	$txtdistrictsub= $_POST['txtdistrictsub'];
	$txtkota= $_POST['txtkota'];
	$txtprop= $_POST['txtprop'];
	$txtkodepos= $_POST['txtkodepos'];

	$cbodivisi= $_POST['cbodivisi'];	
	$cbojabatan= $_POST['cbojabatan'];
	$cboempstatus= $_POST['cboempstatus'];
	$cboharikerja= $_POST['cboharikerja'];
	$txtcuti= $_POST['txtcuti'];
	
	$txttanggungan= $_POST['txttanggungan'];
	$cboptkp= $_POST['cboptkp'];
	$txtbpjs_tk= $_POST['txtbpjs_tk'];
	$txtbpjs_kes= $_POST['txtbpjs_kes'];
	
	$txtgapok= $_POST['txtgapok'];
	$txtnorek= $_POST['txtnorek'];
	$txtnmrek= $_POST['txtnmrek'];
	$txtbank= $_POST['txtbank'];

	$txtwage_template= $_POST['cbowage_template'];
	$cbotipe_pajak= $_POST['cbotipe_pajak'];
	$lokasi= $_POST['txtwlok']; 
	
	$data = mysqli_query($koneksi,"SELECT nip FROM tbpegawai WHERE nip='$txtnip'"); 
	$cek = mysqli_num_rows($data);
	if($cek > 0){		
		echo"<script>window.alert('Data Pegawai sudah ada!');window.location=('pegawai_add.php');</script>";
	} else {
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
	
		mysqli_query($koneksi,"INSERT INTO tbpegawai 
								(nip, nama, alamat, kode_jabatan, notlp, kode_jk, 
								tempat_lahir, tgl_lahir, email, kode_pendidikan, npwp, kode_status_kawin, 
								jml_tanggungan, kode_ptkp, gaji_pokok, no_rek, nama_rek, bank, 
								ktp, kode_agama, kode_darah, no_bpjs_tk, no_bpjs_kes, 
								kode_divisi, 
								kode_status_emp, tgl_masuk, foto_pegawai, 
								kota, prop, district, districtsub, kodepos, 
								id_hari_kerja,jumlah_cuti,tgl_input,  
								status_aktif,tlp_rumah,wage_template,id_tipepajak, lokasi, 
                                user_name, password) 
								VALUES 
								('$txtnip','$txtnama','$txtalamat','$cbojabatan','$txttlp','$cbojk',
								'$txttempatlhr','$txttgllhr','$txtemail','$cbopendidikan','$txtnpwp','$cbostatus',
								'$txttanggungan','$cboptkp','$txtgapok','$txtnorek','$txtnmrek','$txtbank',
								'$txtktp','$cboagama','$cbodarah','$txtbpjs_tk','$txtbpjs_kes', 
								'$cbodivisi',
								'$cboempstatus','$txttglmasuk','$foto_save',
								'$txtkota','$txtprop','$txtdistrict','$txtdistrictsub','$txtkodepos',
								'$cboharikerja','$txtcuti','$tgl_input',
								'Aktif','$txttlprumah','$txtwage_template','$cbotipe_pajak',
                                '$lokasi','$txtnip','123')");
		echo"<script>window.alert('Data Pegawai berhasil disimpan!');window.location=('pegawai.php');</script>";
	}
?>