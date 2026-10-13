<?php
	include "../config/koneksi.php";
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	    
	$txtnopol= mysqli_real_escape_string($koneksi, $_POST['txtnopol']);    
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat']);
    $cbomerek= mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
    
    $cbotipe= mysqli_real_escape_string($koneksi, $_POST['cbotipe']);
    $cbojenis= mysqli_real_escape_string($koneksi, $_POST['cbojenis']);
    $cbowarna= mysqli_real_escape_string($koneksi, $_POST['cbowarna']);
    
    $txtthn_buat= mysqli_real_escape_string($koneksi, $_POST['txtthn_buat']);
    $txtthn_rakit= mysqli_real_escape_string($koneksi, $_POST['txtthn_rakit']);
    $txtsilinder= mysqli_real_escape_string($koneksi, $_POST['txtsilinder']);
    $txtnorangka= mysqli_real_escape_string($koneksi, $_POST['txtnorangka']);
    $txtnomesin= mysqli_real_escape_string($koneksi, $_POST['txtnomesin']);
    $txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);

	$data = mysqli_query($koneksi,"SELECT nopelanggan FROM tblpelanggan 
                                    WHERE nopelanggan='$txtnopol'");
	$cek = mysqli_num_rows($data);

	// Cek nopol terhadap kendaraan aktif dan riwayat nopol lama (kendaraan_plat_history).
	$cek_kendaraan = 0;
	$cek_riwayat = 0;
	$q_kend = mysqli_query($koneksi,"SELECT nopolisi FROM tblkendaraan WHERE nopolisi='$txtnopol' LIMIT 1");
	if($q_kend) { $cek_kendaraan = mysqli_num_rows($q_kend); }
	$q_riw = mysqli_query($koneksi,"SELECT nopolisi FROM kendaraan_plat_history WHERE nopolisi='$txtnopol' LIMIT 1");
	if($q_riw) { $cek_riwayat = mysqli_num_rows($q_riw); }

	if($cek > 0){
        echo"<script>window.alert('No Polisi sudah terdaftar di Data Pelanggan!');
        window.history.back();</script>";
    } elseif($cek_kendaraan > 0){
        echo"<script>window.alert('No Polisi sudah terdaftar sebagai kendaraan!');
        window.history.back();</script>";
    } elseif($cek_riwayat > 0){
        echo"<script>window.alert('No Polisi ini pernah tercatat sebagai nopol lama kendaraan lain. Gunakan menu pindah kepemilikan/ganti plat.');
        window.history.back();</script>";
    } else {
        
        // Tipe Motor : spt B eat-110, dll
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            tipe 
                                            FROM tbtipe_motor 
                                            WHERE 
                                            kode_tipe='$cbotipe'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$tipe_motor=$tm_cari['tipe'];		
        // End Tipe Motor

        // Jenis Motor : spt FL, Carbu, dll
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            jenis 
                                            FROM tbjenis_motor 
                                            WHERE 
                                            kd='$cbojenis'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$jenis_motor=$tm_cari['jenis'];		
        // End Jenis Motor

        // Warna Motor
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            warna 
                                            FROM tbwarna 
                                            WHERE 
                                            id='$cbowarna'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$warna_motor=$tm_cari['warna'];		
        // End Jenis Motor
        
        mysqli_query($koneksi,"INSERT INTO tblkendaraan 
                            (nopolisi, pemilik, alamat, 
                            kode_merek, kode_tipe, kode_jenis, 
                            tahun_buat, tahun_rakit, silinder, 
                            kode_warna, no_rangka, no_mesin, 
                            note,
                            tipe, jenis, warna) 
                            VALUES 
                            ('$txtnopol','$txtnama','$txtalamat',
                            '$cbomerek','$cbotipe','$cbojenis',
                            '$txtthn_buat','$txtthn_rakit','$txtsilinder',
                            '$cbowarna','$txtnorangka','$txtnomesin',
                            '$txtnote',
                            '$tipe_motor','$jenis_motor','$warna_motor')");

        // Catat plat aktif pertama ke riwayat nopol (dipakai cek duplikasi nopol lama).
        $q_idk = mysqli_query($koneksi,"SELECT id_kendaraan FROM tblkendaraan WHERE nopolisi='$txtnopol' LIMIT 1");
        $r_idk = $q_idk ? mysqli_fetch_assoc($q_idk) : null;
        if($r_idk && !empty($r_idk['id_kendaraan'])) {
            $id_k = (int)$r_idk['id_kendaraan'];
            $uid_k = isset($_SESSION['_iduser']) ? (int)$_SESSION['_iduser'] : 0;
            mysqli_query($koneksi,"INSERT INTO kendaraan_plat_history 
                                    (id_kendaraan, nopolisi, tanggal_mulai, is_current, alasan, diinput_oleh) 
                                    VALUES ($id_k, '$txtnopol', CURDATE(), 1, 'kendaraan_baru', $uid_k)");
        }
                                    
        echo"<script>window.alert('Data Kendaraan Berhasil disimpan!');
        window.location=('kendaraan.php');</script>";        
    }
?>