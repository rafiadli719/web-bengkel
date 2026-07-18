<?php
	session_start();
    $id_user=$_SESSION['_iduser'];		#
    $kd_cabang=$_SESSION['_cabang'];

	date_default_timezone_set('Asia/Jakarta');
	$tgl_skr=date('Y/m/d');
    $waktu_skr=date('h:i');
	$nopol= $_GET['snopol'];

    include "../config/koneksi.php";
    include "function_servis.php";
    require_once __DIR__ . '/_include_customer_vehicle_sync.php';
    $LastID=FormatNoTrans(OtomatisID());
    $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $nopol);
    $customerCode = trim((string)($bundle['mapped_customer_code'] ?? ''));

    if ($customerCode === '') {
        echo"<script>window.alert('Data pelanggan untuk nomor polisi ini belum valid. Rapikan relasi pelanggan-kendaraan dulu.');window.location=('servis-carinopol.php');</script>";
        exit;
    }

    mysqli_query($koneksi,"INSERT INTO tblservice
                            (no_service, tanggal, jam,
                            no_pelanggan, no_polisi,
                            kd_cabang, id_user)
                            VALUES
                            ('$LastID','$tgl_skr','$waktu_skr',
                            '$customerCode','$nopol',
                            '$kd_cabang','$id_user')");
    echo"<script>window.location=('servis-input-reguler-jemput.php?snoserv=$LastID');</script>";
?>
