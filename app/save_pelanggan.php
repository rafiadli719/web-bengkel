<?php
	include "../config/koneksi.php";
	require_once __DIR__ . '/_customer_identity.php';

                date_default_timezone_set('Asia/Jakarta');
                $waktuaja_skr=date('h:i');
                function ubahformatTgl($tanggal) {
                    $pisah = explode('/',$tanggal);
                    $urutan = array($pisah[2],$pisah[1],$pisah[0]);
                    $satukan = implode('-',$urutan);
                    return $satukan;
                }
                
                $txttglpesan = ubahformatTgl($_POST['id-date-picker-1']);
	    
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
	$txtalamat= $_POST['txtalamat'];    
	$txtkota= $_POST['txtkota'];
	$txtprop= $_POST['txtprop'];
	$txtnegara= $_POST['txtnegara'];    
	$txtpos= $_POST['txtpos'];    
	$txttlp= $_POST['txttlp']; 
	$txtfax= $_POST['txtfax'];
	$txtkontak= $_POST['txtkontak'];
    $cbolevel= $_POST['cbolevel'];
	$txtnote= $_POST['txtnote'];    

	$txtpanggilan= $_POST['txtpanggilan'];    
	$txtlat= $_POST['txtlat'];    
	$txtlong= $_POST['txtlong'];    
	$txtpatokan= $_POST['txtpatokan'];    

	$cbopot= $_POST['cbopot'];
	$txttlp = trim($txttlp);

	if ($txttlp === '') {
		echo "<script>window.alert('No Telephone/Whatsapp wajib diisi supaya pelanggan tidak tercatat ganda!');
		window.history.back();</script>";
		exit;
	}

	$resolution = fitmotorResolveCustomerCodeByPhone($koneksi, $txttlp);
	$cek = count($resolution['matches']);

	if($cek > 0){
        echo"<script>window.alert('No Telephone/Whatsapp sudah terdaftar!');
        window.history.back();</script>";
    } else {
		if (trim($txtkd) === '') {
			$txtkd = fitmotorGenerateCustomerCode($koneksi);
		}
        $ins_stmt = mysqli_prepare($koneksi, "INSERT INTO tblpelanggan
                            (nopelanggan, namapelanggan,
                            alamat, kota, propinsi, kodepost, negara,
                            telephone, fax, kontakperson, note, kgrup,
                            patokan, klat, klong, panggilan, tgllahir,
                            tipepot)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        mysqli_stmt_bind_param($ins_stmt, str_repeat('s', 18),
            $txtkd, $txtnama, $txtalamat, $txtkota, $txtprop, $txtpos, $txtnegara,
            $txttlp, $txtfax, $txtkontak, $txtnote, $cbolevel,
            $txtpatokan, $txtlat, $txtlong, $txtpanggilan, $txttglpesan, $cbopot);
        mysqli_stmt_execute($ins_stmt);
        mysqli_stmt_close($ins_stmt);

        echo"<script>window.alert('Data Pelanggan Berhasil disimpan!');
        window.location=('pelanggan.php');</script>";
    }
?>
