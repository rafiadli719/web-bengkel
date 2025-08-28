<?php
    include "../config/koneksi.php";

	$nama_file_baru = $_POST['txtfile'];

	// Load librari PHPExcel nya
	require_once 'PHPExcel/PHPExcel.php';

	$excelreader = new PHPExcel_Reader_Excel2007();
	$loadexcel = $excelreader->load('tmp_excel/'.$nama_file_baru); // Load file excel yang tadi diupload ke folder tmp
	$sheet = $loadexcel->getActiveSheet()->toArray(null, true, true ,true);

	$numrow = 1;
	foreach($sheet as $row){
		// Ambil data pada excel sesuai Kolom
		$fld1 = $row['A']; // Ambil data Company ID
		$fld2 = $row['B']; // Ambil data Employee ID
		$fld3 = $row['C']; // Ambil data Nama
		$fld4 = $row['D']; // Ambil data Negara
		$fld5 = $row['E']; // Ambil data jenis kelamin
		$fld6 = $row['F']; // Ambil data status kawin
		$fld7 = $row['G']; // Ambil data tempat lahir
        $fld8 = $row['H']; // Ambil data tanggal lahir
        $fld9 = $row['I']; // Ambil data agama
        $fld10 = $row['J']; // Ambil data gol darah
        $fld11 = $row['K']; // Ambil data alamat
        $fld12 = $row['L']; // Ambil data kota                                                        
        $fld13 = $row['M']; // Ambil data kode pos
        $fld14 = $row['N']; // Ambil data propinsi
        $fld15 = $row['O']; // Ambil data telepon
        $fld16 = $row['P']; // Ambil data email
        $fld17 = $row['Q']; // Ambil data KTP
        $fld18 = $row['R']; // Ambil data pendidikan        
        $fld19 = $row['S']; // Ambil data aktif/tidak aktif
        $fld20 = $row['T']; // Ambil data status karyawan : tetap, dll		
        $fld21 = $row['U']; // Ambil data tgl masuk        
        $fld22 = $row['V']; // Ambil data kode jabatan
        $fld23 = $row['W']; // Ambil data lokasi kerja        
        $fld24 = $row['X']; // Ambil data jadwal kerja
        $fld25 = $row['Y']; // Ambil data gaji pokok        
        $fld26 = $row['Z']; // Ambil data npwp        
        $fld27 = $row['AA']; // Ambil data jumlah cuti
        $fld28 = $row['AB']; // Ambil data jumlah tanggungan
        $fld29 = $row['AC']; // Ambil data kode ptkp

        $fld30 = $row['AD']; // Ambil data kode ptkp
        $fld31 = $row['AE']; // Ambil data kode ptkp
        $fld32 = $row['AF']; // Ambil data kode ptkp
        $fld33 = $row['AG']; // Ambil data kode ptkp
        $fld34 = $row['AH']; // Ambil data kode ptkp
        $fld35 = $row['AI']; // Ambil data kode ptkp
        $fld36 = $row['AJ']; // Ambil data kode ptkp       
        $fld37 = $row['AK']; // Ambil data kode ptkp
        $fld38 = $row['AL']; // Ambil data kode ptkp
        $fld39 = $row['AM']; // Ambil data kode ptkp       
        $fld40 = $row['AN']; // Ambil data kode ptkp               
															
															// Cek jika semua data tidak diisi
															if($fld1 == "" && $fld2 == "" && $fld3 == "" && $fld4 == "" && $fld5 == "" && $fld6 == "" && $fld7 == "" 
																		&& $fld8 == "" && $fld9 == "" && $fld10 == "" && $fld11 == "" && $fld12 == "" && $fld13 == "" && $fld14 == "" 
																		&& $fld15 == "" && $fld16 == "" && $fld17 == "" && $fld18 == "" && $fld19 == "" && $fld20 == "" 																		
																		&& $fld21 == "" && $fld22 == "" && $fld23 == "" && $fld24 == "" && $fld25 == "" 																		
																		&& $fld26 == "" && $fld27 == "" && $fld28 == "" && $fld29 == "")
			continue; // Lewat data pada baris ini (masuk ke looping selanjutnya / baris selanjutnya)

		// Cek $numrow apakah lebih dari 1
		// Artinya karena baris pertama adalah nama-nama kolom
		// Jadi dilewat saja, tidak usah diimport
		if($numrow > 1){
			// Buat query Insert
 
            $data = mysqli_query($koneksi,"SELECT nip FROM tbpegawai 
                                            WHERE nip='$fld2'");
            $cek = mysqli_num_rows($data);
            if($cek <= 0){
                $query = "INSERT INTO tbpegawai 
							(kode_perusahaan, nip, nama, negara, kode_jk, kode_status_kawin, tempat_lahir, 
							tgl_lahir, kode_agama, kode_darah, alamat, kota, kodepos, prop, 
							notlp, email, ktp, kode_pendidikan, 
							status_aktif, kode_status_emp, tgl_masuk, kode_jabatan, lokasi, id_hari_kerja, gaji_pokok, 
							npwp, jumlah_cuti, jml_tanggungan, kode_ptkp, 
                            tlp_rumah, kode_divisi, districtsub, district, no_bpjs_tk, no_bpjs_kes, 
                            wage_template, id_tipepajak, bank, no_rek, nama_rek, 
                            user_name, password) 
							VALUES 
							('".$fld1."','".$fld2."','".$fld3."','".$fld4."','".$fld5."','".$fld6."','".$fld7."',
							'".$fld8."','".$fld9."','".$fld10."','".$fld11."','".$fld12."','".$fld13."','".$fld14."',
							'".$fld15."','".$fld16."','".$fld17."','".$fld18."', 
							'".$fld19."','".$fld20."','".$fld21."','".$fld22."','".$fld23."','".$fld24."','".$fld25."',
							'".$fld26."','".$fld27."','".$fld28."','".$fld29."',
                            '".$fld30."','".$fld31."','".$fld32."','".$fld33."',
                            '".$fld34."','".$fld35."','".$fld36."','".$fld37."',
                            '".$fld38."','".$fld39."','".$fld40."','".$fld2."','123')";

                // Eksekusi $query
                mysqli_query($koneksi, $query);
            }
		}

		$numrow++; // Tambah 1 setiap kali looping
	}    
	echo"<script>window.alert('Successfull!');window.location=('pegawai.php');</script>";
?>
