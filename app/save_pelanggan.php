<?php
	include "../config/koneksi.php";
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['_iduser'])) { header("location:../index.php"); exit; }
	require_once __DIR__ . '/_customer_identity.php';

                date_default_timezone_set('Asia/Jakarta');
                $waktuaja_skr=date('h:i');
                function ubahformatTgl($tanggal) {
                    $pisah = explode('/',$tanggal);
                    $urutan = array($pisah[2],$pisah[1],$pisah[0]);
                    $satukan = implode('-',$urutan);
                    return $satukan;
                }
                
                $txttglpesan = ubahformatTgl($_POST['id-date-picker-1'] ?? '');
                // tgllahir NOT NULL (date): tanggal kosong/format salah -> hari ini,
                // sama konvensi save_pelanggan_only.php. Tanpa ini strict mode nolak '--'.
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $txttglpesan)) $txttglpesan = date('Y-m-d');
	    
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
                            tipepot, id_panggilan, lavelharga, pertanggal, potongan, saldoawal)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 0, '3', CURDATE(), 0, 0)");
        mysqli_stmt_bind_param($ins_stmt, str_repeat('s', 18),
            $txtkd, $txtnama, $txtalamat, $txtkota, $txtprop, $txtpos, $txtnegara,
            $txttlp, $txtfax, $txtkontak, $txtnote, $cbolevel,
            $txtpatokan, $txtlat, $txtlong, $txtpanggilan, $txttglpesan, $cbopot);
        $ins_ok = mysqli_stmt_execute($ins_stmt);
        $ins_err = mysqli_stmt_error($ins_stmt);
        mysqli_stmt_close($ins_stmt);
        if (!$ins_ok) {
            error_log('[save_pelanggan] insert gagal: ' . $ins_err);
            echo "<script>window.alert(" . json_encode('Gagal menyimpan pelanggan: ' . $ins_err) . "); window.history.back();</script>";
            exit;
        }

        echo"<script>window.alert('Data Pelanggan Berhasil disimpan!');
        window.location=('pelanggan.php');</script>";
    }
?>
