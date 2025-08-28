<?php
	session_start();
	include "../config/koneksi.php";

	$nip= $_GET['snip'];
	$sbulan= $_GET['sbln'];	
	$stahun= $_GET['sthn'];

	$cari_kd=mysqli_query($koneksi,"SELECT nama FROM bulan_transaksi WHERE id='$sbulan'");
	$tm_cari=mysqli_fetch_array($cari_kd);
	$nama_bulan=$tm_cari['nama'];

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        nama, kode_jabatan, kode_divisi 
                                                                                        FROM tbpegawai 
                                                                                        where nip='$nip'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama=$tm_cari['nama'];
                                                        $kode_jabatan=$tm_cari['kode_jabatan'];
                                                        $kode_departemen=$tm_cari['kode_divisi'];
                                                                                            
                                                        $cari_kd=mysqli_query($koneksi,"SELECT nama_divisi FROM tbdivisi WHERE kode_divisi='$kode_departemen'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_divisi=$tm_cari['nama_divisi'];														
                                                                                            
                                                        $cari_kd=mysqli_query($koneksi,"SELECT nama_jabatan FROM tbjabatan WHERE kode_jabatan='$kode_jabatan'");
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $nama_jabatan=$tm_cari['nama_jabatan'];
    
    $nama_file="Laporan Absensi ".$nama_bulan." ".$stahun."-".$nip.".pdf";
    $periode=$nama_bulan." ".$stahun;

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and bulan='$sbulan' and tahun='$stahun'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_kerja=$tm_cari['jml'];	

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='2'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_absen=$tm_cari['jml'];	
													
													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='3'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_sakit=$tm_cari['jml'];					

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='4'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_izin=$tm_cari['jml'];

													$cari_kd=mysqli_query($koneksi,"SELECT count(id) as jml FROM view_absensi 
																					WHERE nip='$nip' and bulan='$sbulan' and tahun='$stahun' and kode_status_kehadiran='5'");
													$tm_cari=mysqli_fetch_array($cari_kd);
													$jml_cuti=$tm_cari['jml'];
													
													$jml_hadir=$jml_kerja-($jml_absen+$jml_sakit+$jml_izin+$jml_cuti);	

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='1' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='1' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen1=$jam_absensi;
																		} else { 
																				$ket_absen1=$status_kehadiran;
																		}
																	} else {
																		$ket_absen1="";
																	}																		

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='2' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='2' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen2=$jam_absensi;
																		} else { 
																				$ket_absen2=$status_kehadiran;
																		}
																	} else {
																		$ket_absen2="             ";
																	}							

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='3' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='3' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen3=$jam_absensi;
																		} else { 
																				$ket_absen3=$status_kehadiran;
																		}
																	} else {
																		$ket_absen3="";
																	}	

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='4' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='4' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen4=$jam_absensi;
																		} else { 
																				$ket_absen4=$status_kehadiran;
																		}
																	} else {
																		$ket_absen4="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='5' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='5' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen5=$jam_absensi;
																		} else { 
																				$ket_absen5=$status_kehadiran;
																		}
																	} else {
																		$ket_absen5="";
																	}			

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='6' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='6' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen6=$jam_absensi;
																		} else { 
																				$ket_absen6=$status_kehadiran;
																		}
																	} else {
																		$ket_absen6="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='7' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='7' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen7=$jam_absensi;
																		} else { 
																				$ket_absen7=$status_kehadiran;
																		}
																	} else {
																		$ket_absen7="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='8' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='8' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen8=$jam_absensi;
																		} else { 
																				$ket_absen8=$status_kehadiran;
																		}
																	} else {
																		$ket_absen8="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='9' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='9' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen9=$jam_absensi;
																		} else { 
																				$ket_absen9=$status_kehadiran;
																		}
																	} else {
																		$ket_absen9="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='10' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='10' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen10=$jam_absensi;
																		} else { 
																				$ket_absen10=$status_kehadiran;
																		}
																	} else {
																		$ket_absen10="";
																	}																	

$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='11' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='11' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen11=$jam_absensi;
																		} else { 
																				$ket_absen11=$status_kehadiran;
																		}
																	} else {
																		$ket_absen11="";
																	}

$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='12' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='12' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen12=$jam_absensi;
																		} else { 
																				$ket_absen12=$status_kehadiran;
																		}
																	} else {
																		$ket_absen12="";
																	}






																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='13' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='13' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen13=$jam_absensi;
																		} else { 
																				$ket_absen13=$status_kehadiran;
																		}
																	} else {
																		$ket_absen13="";
																	}	

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='14' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='14' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen14=$jam_absensi;
																		} else { 
																				$ket_absen14=$status_kehadiran;
																		}
																	} else {
																		$ket_absen14="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='15' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='15' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen15=$jam_absensi;
																		} else { 
																				$ket_absen15=$status_kehadiran;
																		}
																	} else {
																		$ket_absen15="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='16' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='16' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen16=$jam_absensi;
																		} else { 
																				$ket_absen16=$status_kehadiran;
																		}
																	} else {
																		$ket_absen16="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='17' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='17' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen17=$jam_absensi;
																		} else { 
																				$ket_absen17=$status_kehadiran;
																		}
																	} else {
																		$ket_absen17="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='18' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='18' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen18=$jam_absensi;
																		} else { 
																				$ket_absen18=$status_kehadiran;
																		}
																	} else {
																		$ket_absen18="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='19' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='19' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen19=$jam_absensi;
																		} else { 
																				$ket_absen19=$status_kehadiran;
																		}
																	} else {
																		$ket_absen19="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='20' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='20' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen20=$jam_absensi;
																		} else { 
																				$ket_absen20=$status_kehadiran;
																		}
																	} else {
																		$ket_absen20="";
																	}
																															
$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='21' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='21' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen21=$jam_absensi;
																		} else { 
																				$ket_absen21=$status_kehadiran;
																		}
																	} else {
																		$ket_absen21="";
																	}

$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='22' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='22' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen22=$jam_absensi;
																		} else { 
																				$ket_absen22=$status_kehadiran;
																		}
																	} else {
																		$ket_absen22="";
																	}






																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='23' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='23' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen23=$jam_absensi;
																		} else { 
																				$ket_absen23=$status_kehadiran;
																		}
																	} else {
																		$ket_absen23="";
																	}	

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='24' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='24' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen24=$jam_absensi;
																		} else { 
																				$ket_absen24=$status_kehadiran;
																		}
																	} else {
																		$ket_absen24="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='25' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='25' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen25=$jam_absensi;
																		} else { 
																				$ket_absen25=$status_kehadiran;
																		}
																	} else {
																		$ket_absen25="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='26' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='26' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen26=$jam_absensi;
																		} else { 
																				$ket_absen26=$status_kehadiran;
																		}
																	} else {
																		$ket_absen26="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='27' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='27' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen27=$jam_absensi;
																		} else { 
																				$ket_absen27=$status_kehadiran;
																		}
																	} else {
																		$ket_absen27="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='28' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='28' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen28=$jam_absensi;
																		} else { 
																				$ket_absen28=$status_kehadiran;
																		}
																	} else {
																		$ket_absen28="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='29' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='29' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen29=$jam_absensi;
																		} else { 
																				$ket_absen29=$status_kehadiran;
																		}
																	} else {
																		$ket_absen29="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='30' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='30' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen30=$jam_absensi;
																		} else { 
																				$ket_absen30=$status_kehadiran;
																		}
																	} else {
																		$ket_absen30="";
																	}

																	$data = mysqli_query($koneksi,"SELECT id from view_absensi 
																									where nip='$nip' and 
																									tanggal='31' and bulan='$sbulan' and tahun='$stahun'");
																	$cek = mysqli_num_rows($data);
																	if($cek > 0){		
																		$cari_kd=mysqli_query($koneksi,"SELECT kode_status_kehadiran, jam_absensi from view_absensi 
																									where nip='$nip' and 
																									tanggal='31' and bulan='$sbulan' and tahun='$stahun'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$kode_status_kehadiran=$tm_cari['kode_status_kehadiran'];
																		$jam_absensi=$tm_cari['jam_absensi'];

																		$cari_kd=mysqli_query($koneksi,"SELECT status_kehadiran FROM tbstatus_kehadiran WHERE id='$kode_status_kehadiran'");
																		$tm_cari=mysqli_fetch_array($cari_kd);
																		$status_kehadiran=$tm_cari['status_kehadiran'];
																		
																		if($kode_status_kehadiran=='1') {
																				$ket_absen31=$jam_absensi;
																		} else { 
																				$ket_absen31=$status_kehadiran;
																		}
																	} else {
																		$ket_absen31="";
																	}	
		
	require_once("dompdf/autoload.inc.php");
	use Dompdf\Dompdf;
	$dompdf = new Dompdf();

	$html = '<table style="margin: 10 10pt; width: 100%; border-collapse:collapse;" border="0">';
	$html .= "<tr>														
                <td colspan=3 align=center><b>LAPORAN ABSENSI</b></td>
                </tr>
                
                <tr>														
                        <td width=10%><b>NIP</b></td>
                        <td width=5% align=center><b>:</b></td>
                        <td width=85%>".$nip."</td>
                    </tr>
                    <tr>														
                        <td width=10%><b>Nama</b></td>
                        <td width=5% align=center><b>:</b></td>
                        <td width=85%>".$nama."</td>
                    </tr>
                    <tr>														
                        <td width=10%><b>Divisi</b></td>
                        <td width=5% align=center><b>:</b></td>
                        <td width=85%>".$nama_divisi."</td>
                    </tr>
                    <tr>														
                        <td width=10%><b>Jabatan</b></td>
                        <td width=5% align=center><b>:</b></td>
                        <td width=85%>".$nama_jabatan."</td>
                    </tr>
            </table>
            <br>";

	$html .= '<table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="1">
												<tr>
                                                    <td align="center" colspan="10"><b>'.$periode.'</b></td>
                                                </tr>
                                                <tr>														
													<td align="center" bgcolor="gainsboro" width="10%"><b>1</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>2</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>3</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>4</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>5</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>6</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>7</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>8</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>9</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>10</b></td>
												</tr>';

													$html .= "<tr>
														<td align=center>".$ket_absen1."</td>														
														<td align=center>".$ket_absen2."</td>																												
														<td align=center>".$ket_absen3."</td>														
														<td align=center>".$ket_absen4."</td>																																										
														<td align=center>".$ket_absen5."</td>
														<td align=center>".$ket_absen6."</td>														
														<td align=center>".$ket_absen7."</td>																												
														<td align=center>".$ket_absen8."</td>														
														<td align=center>".$ket_absen9."</td>																																										
														<td align=center>".$ket_absen10."</td>                                                                                                
													</tr>";

	$html .= '
                                                <tr>														
													<td align="center" bgcolor="gainsboro" width="10%"><b>11</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>12</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>13</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>14</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>15</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>16</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>17</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>18</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>19</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>20</b></td>
												</tr>';    

													$html .= "<tr>
														<td align=center>".$ket_absen11."</td>														
														<td align=center>".$ket_absen12."</td>																												
														<td align=center>".$ket_absen13."</td>														
														<td align=center>".$ket_absen14."</td>																																										
														<td align=center>".$ket_absen15."</td>
														<td align=center>".$ket_absen16."</td>														
														<td align=center>".$ket_absen17."</td>																												
														<td align=center>".$ket_absen18."</td>														
														<td align=center>".$ket_absen19."</td>																																										
														<td align=center>".$ket_absen20."</td>                                                                                                
													</tr>";

	$html .= '
                                                <tr>														
													<td align="center" bgcolor="gainsboro" width="10%"><b>21</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>22</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>23</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>24</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>25</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>26</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>27</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>28</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>29</b></td>
													<td align="center" bgcolor="gainsboro" width="10%"><b>30</b></td>
												</tr>';    
                                                
													$html .= "<tr>
														<td align=center>".$ket_absen21."</td>														
														<td align=center>".$ket_absen22."</td>																												
														<td align=center>".$ket_absen23."</td>														
														<td align=center>".$ket_absen24."</td>																																										
														<td align=center>".$ket_absen25."</td>
														<td align=center>".$ket_absen26."</td>														
														<td align=center>".$ket_absen27."</td>																												
														<td align=center>".$ket_absen28."</td>														
														<td align=center>".$ket_absen29."</td>																																										
														<td align=center>".$ket_absen30."</td>                                                                                                
													</tr>";

	$html .= '
                                                <tr>														
													<td align="center" bgcolor="gainsboro" width="10%"><b>31</b></td>
													<td align="center" colspan="2" bgcolor="gainsboro"><b>Total Hari Kerja</b></td>	
													<td align="center" width="10%" bgcolor="gainsboro"><b>Izin</b></td>
													<td align="center" width="10%" bgcolor="gainsboro"><b>Sakit</b></td>
													<td align="center" width="10%" bgcolor="gainsboro"><b>Cuti</b></td>
													<td align="center" colspan="2" bgcolor="gainsboro"><b>Tanpa Keterangan</b></td>
													<td align="center" colspan="2" bgcolor="gainsboro"><b>Hadir (Hari Kerja)</b></td>                                                    
												</tr>';    

													$html .= "<tr>
														<td align=center>".$ket_absen31."</td>														
														<td align=center colspan=2>".$jml_kerja."</td>														
														<td align=center>".$jml_izin."</td>																												
														<td align=center>".$jml_sakit."</td>														
														<td align=center>".$jml_cuti."</td>																																										
														<td align=center colspan=2>".$jml_absen."</td>
														<td align=center colspan=2>".$jml_hadir."</td>
													</tr>
                                                    </table>
                                                    <br>";
                                                    
	
            
$html .= "</html>";
$dompdf->loadHtml($html);
// Setting ukuran dan orientasi kertas
$dompdf->setPaper('A4', 'landscape');
// Rendering dari HTML Ke PDF
$dompdf->render();
// Melakukan output file Pdf
$dompdf->stream($nama_file);
?>
