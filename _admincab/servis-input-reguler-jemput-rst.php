<?php
	session_start();

	if(empty($_SESSION['_iduser'])){
		header("location:../index.php");
	} else {
		$id_user=$_SESSION['_iduser'];
        $kd_cabang=$_SESSION['_cabang'];
		include "../config/koneksi.php";
		include_once "../lib/rbac.php";
		rbac_require_any(array('input_servis_read','jadwal_jemput_read','servis_jemput_read','servis_menu_read','service_create','service_update'));
		include "_include_customer_vehicle_sync.php";
		include "_include_statistik_pelanggan.php";
		include "_include_kategori_member.php"; // Member kategori & discount helper
		include "_handler_temuan_penawaran.php";
		include "_handler_barang_custom.php";
		include "_handler_status_keluhan_wo.php";

		$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_user, password, user_akses, foto_user
                                        FROM tbuser WHERE id='$id_user'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$_nama=$tm_cari['nama_user'];
		$pwd=$tm_cari['password'];
		$lvl_akses=$tm_cari['user_akses'];
		$foto_user=$tm_cari['foto_user'];
		if($foto_user=='') {
			$foto_user="file_upload/avatar.png";
		}

		// Set username session if not exists to prevent login redirect
		if(!isset($_SESSION['username'])) {
			$_SESSION['username'] = $_nama;
		}

    // ------- Data Cabang ----------
		$cari_kd=mysqli_query($koneksi,"SELECT
                                        nama_cabang, tipe_cabang
                                        FROM tbcabang
                                        WHERE kode_cabang='$kd_cabang'");
		$tm_cari=mysqli_fetch_array($cari_kd);
		$nama_cabang = $tm_cari ? $tm_cari['nama_cabang'] : '';
        $tipe_cabang = $tm_cari ? $tm_cari['tipe_cabang'] : '';
    // --------------------

		$no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : '';
    $txtcaribrg=$_GET['kd'] ?? '';
    $txtcarisrv=$_GET['kdjasa'] ?? '';
    $txtcariwo=$_GET['kdwo'] ?? '';

    // Set active tab based on URL parameter or search parameters
    $active_tab = 'service-details'; // Default tab

    // Priority 1: Check URL parameter (HIGHEST PRIORITY - preserves user's tab selection)
    if (isset($_GET['tab'])) {
        switch($_GET['tab']) {
            case 'items':
                $active_tab = 'service-items';
                break;
            case 'jasa':
                $active_tab = 'service-jasa';
                break;
            case 'workorder':
                $active_tab = 'workorder-details';
                break;
            case 'actions':
                $active_tab = 'service-actions';
                break;
            default:
                $active_tab = 'service-details';
        }
    }
    // Priority 2: Check search parameters ONLY if no URL tab parameter AND it's a fresh search
    // This prevents automatic tab switching when returning from form submission
    elseif (!empty($txtcaribrg) && !isset($_POST['btnadditem']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'service-items'; // Item Barang tab
    } elseif (!empty($txtcarisrv) && !isset($_POST['btnaddservice']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'service-jasa'; // Item Jasa tab
    } elseif (!empty($txtcariwo) && !isset($_POST['btnaddwo']) && !isset($_POST['btnupdatemekanik'])) {
        $active_tab = 'workorder-details'; // Work Order tab
    }

    // Handler untuk auto-add tarif jemput ke Item Jasa
    if(isset($_POST['btnaddtarifjemput'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            // Get tarif jemput from tblservice
            $query_get_tarif = mysqli_query($koneksi, "SELECT tarif_jemput, jarak_jemput, kondisi_motor FROM tblservice WHERE no_service='$no_service'");
            if($tarif_data = mysqli_fetch_array($query_get_tarif)) {
                $tarif_jemput = $tarif_data['tarif_jemput'] ?? 0;
                $jarak_jemput = $tarif_data['jarak_jemput'] ?? 0;
                $kondisi_motor = $tarif_data['kondisi_motor'] ?? 'jalan';

                if($tarif_jemput > 0) {
                    // Check if already exists
                    $check_existing = mysqli_query($koneksi, "SELECT id FROM tblservis_jasa WHERE no_service='$no_service' AND no_item='JEMPUT-ANTAR'");
                    if(mysqli_num_rows($check_existing) == 0) {
                        // Insert tarif jemput as jasa item
                        $kondisi_text = $kondisi_motor == 'mogok' ? 'Mogok' : 'Jalan';
                        $nama_jasa = "JEMPUT ANTAR MOTOR ($jarak_jemput KM - Motor $kondisi_text)";

                        // Get next nobaris
                        $query_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                        $nobaris_data = mysqli_fetch_array($query_nobaris);
                        $next_nobaris = $nobaris_data['next_nobaris'] ?? 1;

                        // Insert to tblservis_jasa
                        mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                                (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                                VALUES
                                                ('$no_service', '$next_nobaris', 'JEMPUT-ANTAR', '$tarif_jemput', '0', '0', '$tarif_jemput')");

                        echo "<script>
                            alert('Tarif jemput antar berhasil ditambahkan ke Item Jasa!');
                            window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service';
                        </script>";
                    } else {
                        echo "<script>
                            alert('Tarif jemput antar sudah ada di Item Jasa!');
                            window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service';
                        </script>";
                    }
                } else {
                    echo "<script>
                        alert('Tarif jemput belum dihitung atau jarak kurang dari 1 KM (gratis)!');
                        window.history.back();
                    </script>";
                }
            }
        }
    }

    // Handler untuk Tambah Item Barang (sama seperti servis reguler)
    if(isset($_POST['btnadd'])) {
        $no_service = $_POST['txtnosrv'];
        $km_skr = $_POST['txtkm_skr'];
        $km_berikut = $_POST['txtkm_next'];

        $txtkdbarang = $_POST['txtcaribrg'];
        $txtqty = $_POST['txtqty'];
        $txtpot = $_POST['txtpot'];
        $txtcarisrv = $_POST['txtcarisrv'];
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        if($txtkdbarang <> '') {
            // -- Cek Stok ------------
            $cari_kd = mysqli_query($koneksi, "SELECT saldo
                                                FROM view_stok_master
                                                WHERE kd_cabang='$kd_cabang' AND no_item='$txtkdbarang'");
            $tm_cari = mysqli_fetch_array($cari_kd);
            $stok_akhir = $tm_cari['saldo'];
            // ---------------------

            if($stok_akhir == '0') {
                echo "<script>window.alert('Stok Barang kosong!');
                window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
            } else {
                if($txtqty > $stok_akhir) {
                    echo "<script>window.alert('Stok Barang tidak mencukupi!');
                    window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
                } else {
                    $stok_proses = $stok_akhir - $txtqty;
                    if($stok_proses < 0) {
                        echo "<script>window.alert('Stok Barang tidak mencukupi!');
                        window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
                    } else {
                        // Cek dulu sudah pernah tersimpan belum
                        $data = mysqli_query($koneksi, "SELECT * FROM tblservis_barang
                                                        WHERE no_service='$no_service' AND no_item='$txtkdbarang'");
                        $cek = mysqli_num_rows($data);

                        if($cek > 0) {
                            $txtkdbarang = "";
                            echo "<script>window.alert('Item Barang sudah ada!');
                            window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
                        } else {
                            // 1. Mencari Harga Jual dengan harga bertingkat
                            $cari_kd = mysqli_query($koneksi, "SELECT hargajual, hargajual2, hargajual3,
                                                                hjqtyd2, hjqtyd3, hjqtys1, hjqtys2
                                                                FROM tblitem
                                                                WHERE noitem='$txtkdbarang'");
                            $tm_cari = mysqli_fetch_array($cari_kd);
                            $txtharga_ke1 = $tm_cari['hargajual'];
                            $txtharga_ke2 = $tm_cari['hargajual2'];
                            $txtharga_ke3 = $tm_cari['hargajual3'];
                            $txtqty_ke1 = $tm_cari['hjqtys1'];
                            $txtqty_ke2 = $tm_cari['hjqtys2'];
                            $txtqty_ke3 = $tm_cari['hjqtyd3'];

                            // 2. Tentukan harga berdasarkan qty
                            if($txtqty <= $txtqty_ke1) {
                                $txthargabarang = $txtharga_ke1;
                            } else {
                                if($txtqty_ke1 <= $txtqty_ke2) {
                                    $txthargabarang = $txtharga_ke2;
                                } else {
                                    $txthargabarang = $txtharga_ke3;
                                }
                            }

                            // 3. Hitung subtotal
                            $subtotal = ($txthargabarang * $txtqty) - (($txthargabarang * $txtqty) * ($txtpot / 100));

                            // 4. Get next nobaris for barang
                            $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
                            $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
                            $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

                            // 5. Simpan ke database
                            mysqli_query($koneksi, "INSERT INTO tblservis_barang
                                                    (no_service, nobaris, no_item, harga_jual, quantity, qty_retur, potongan, total)
                                                    VALUES
                                                    ('$no_service', '$nobaris_brg', '$txtkdbarang', '$txthargabarang', '$txtqty', '0', '$txtpot', '$subtotal')");

                            echo "<script>window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
                        }
                    }
                }
            }
        } else {
            echo "<script>window.alert('Belum ada item barang yang dipilih!');
            window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv&kdwo=$txtcariwo');</script>";
        }
    }

    // Handler untuk Tambah Item Jasa (sama seperti servis reguler)
    if(isset($_POST['btnaddsrv'])) {
        $no_service = $_POST['txtnosrv'];
        $km_skr = $_POST['txtkm_skr'];
        $km_berikut = $_POST['txtkm_next'];

        $txtkdsrv = $_POST['txtcarisrv'];
        $txtpotsrv = $_POST['txtpotsrv'];
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        $cari_kd = mysqli_query($koneksi, "SELECT nama_wo, waktu, harga
                                            FROM tbworkorderheader
                                            WHERE kode_wo='$txtkdsrv'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $txthargasrv = $tm_cari['harga'];
        $waktu = $tm_cari['waktu'];

        $subtotal = $txthargasrv - ($txthargasrv * ($txtpotsrv / 100));

        if($txtkdsrv <> '') {
            // Get next nobaris for jasa
            $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
            $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
            $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

            mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                    (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                    VALUES
                                    ('$no_service', '$nobaris_jasa', '$txtkdsrv', '$txthargasrv', '$waktu', '$txtpotsrv', '$subtotal')");

            echo "<script>window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo');</script>";
        } else {
            echo "<script>window.location=('servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=&kdwo=$txtcariwo');</script>";
        }
    }

    // Handler untuk update mechanic data
    if(isset($_POST['btnupdatemekanik'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';

            // Get mechanic data from form
            $kepala_mekanik1 = $_POST['cbokepala_mekanik1'] ?? '';
            $persen_kepala1 = $_POST['txtpersen_kepala1'] ?? 0;
            $kepala_mekanik2 = $_POST['cbokepala_mekanik2'] ?? '';
            $persen_kepala2 = $_POST['txtpersen_kepala2'] ?? 0;
            $admin1 = $_POST['cboadmin1'] ?? '';
            $persen_admin1 = $_POST['txtpersen_admin1'] ?? 0;
            $admin2 = $_POST['cboadmin2'] ?? '';
            $persen_admin2 = $_POST['txtpersen_admin2'] ?? 0;
            $mekanik1 = $_POST['cbomekanik1'] ?? '';
            $persen_mekanik1 = $_POST['txtpersen_mekanik1'] ?? 0;
            $mekanik2 = $_POST['cbomekanik2'] ?? '';
            $persen_mekanik2 = $_POST['txtpersen_mekanik2'] ?? 0;
            $mekanik3 = $_POST['cbomekanik3'] ?? '';
            $persen_mekanik3 = $_POST['txtpersen_mekanik3'] ?? 0;
            $mekanik4 = $_POST['cbomekanik4'] ?? '';
            $persen_mekanik4 = $_POST['txtpersen_mekanik4'] ?? 0;

            // Update mechanic data in tblservice - Fixed column names
            $update_mechanic = "UPDATE tblservice SET
                kepala_mekanik1='$kepala_mekanik1',
                kepala_mekanik2='$kepala_mekanik2',
                persen_kepala_mekanik1='$persen_kepala1',
                persen_kepala_mekanik2='$persen_kepala2',
                mekanik1='$mekanik1',
                mekanik2='$mekanik2',
                mekanik3='$mekanik3',
                mekanik4='$mekanik4',
                persen_mekanik1='$persen_mekanik1',
                persen_mekanik2='$persen_mekanik2',
                persen_mekanik3='$persen_mekanik3',
                persen_mekanik4='$persen_mekanik4',
                updated_at=NOW()
                WHERE no_service='$no_service'";

            if(mysqli_query($koneksi, $update_mechanic)) {
                echo "<script>
                    alert('Data mekanik berhasil diupdate!');
                    window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
                </script>";
            } else {
                echo "<script>alert('Error update data mekanik: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Handler untuk tambah keluhan
    if(isset($_POST['btnaddkeluhan'])) {
        $no_service = $_POST['txtnosrv'];
        $txtkeluhan = $_POST['txtkeluhan'];

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        if(!empty($txtkeluhan)) {
            mysqli_query($koneksi,"INSERT INTO tbservis_keluhan_status
                            (no_service, keluhan, status_pengerjaan)
                            VALUES
                            ('$no_service','$txtkeluhan','datang')");

            echo "<script>
                alert('Keluhan berhasil ditambahkan!');
                window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
            </script>";
        } else {
            echo "<script>alert('Keluhan tidak boleh kosong!');</script>";
        }
    }

    // Handler untuk update status keluhan
    if(isset($_POST['btnupdatestatuskeluhan'])) {
        $keluhan_id = $_POST['keluhan_id'];
        $status_keluhan = $_POST['status_keluhan'];
        $keterangan = $_POST['keterangan_keluhan'] ?? '';

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        mysqli_query($koneksi,"UPDATE tbservis_keluhan_status
                               SET status_pengerjaan='$status_keluhan',
                                   keterangan_tidak_selesai='$keterangan'
                               WHERE id='$keluhan_id'");

        echo "<script>
            alert('Status keluhan berhasil diupdate!');
            window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
        </script>";
    }

    // Handler untuk update keluhan dan catatan di tblservice
    if(isset($_POST['btnupdatekeluhan'])) {
        $no_service = $_POST['txtnosrv'] ?? '';
        if(!empty($no_service)) {
            $keluhan = $_POST['keluhan'] ?? '';
            $catatan = $_POST['catatan'] ?? '';

            $txtcarisrv = $_POST['txtcarisrv'] ?? '';
            $txtcaribrg = $_POST['txtcaribrg'] ?? '';
            $txtcariwo = $_POST['txtcariwo'] ?? '';

            // Update complaint and notes in tblservice
            $update_keluhan = "UPDATE tblservice SET
                keluhan='$keluhan',
                catatan='$catatan',
                updated_at=NOW()
                WHERE no_service='$no_service'";

            if(mysqli_query($koneksi, $update_keluhan)) {
                echo "<script>
                    alert('Keluhan dan catatan berhasil diupdate!');
                    window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
                </script>";
            } else {
                echo "<script>alert('Error update keluhan: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Handler untuk submit form
    if(isset($_POST['btnsimpan'])) {
        // Generate nomor service jika belum ada
        if(empty($no_service)) {
            $tanggal_service = date('Y-m-d');

            // Generate nomor service untuk jemput
            $prefix_service = 'JMP-' . date('Ymd') . '-';
            $query_last_service = "SELECT no_service FROM tblservice WHERE no_service LIKE '$prefix_service%' ORDER BY no_service DESC LIMIT 1";
            $result_last_service = mysqli_query($koneksi, $query_last_service);

            if(mysqli_num_rows($result_last_service) > 0) {
                $last_service = mysqli_fetch_array($result_last_service)['no_service'];
                $last_number = intval(substr($last_service, -4));
                $new_number = $last_number + 1;
            } else {
                $new_number = 1;
            }

            $no_service = $prefix_service . str_pad($new_number, 4, '0', STR_PAD_LEFT);

            // Generate nomor antrian
            $query_antrian_count = "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_service'";
            $result_antrian_count = mysqli_query($koneksi, $query_antrian_count);
            $antrian_count = mysqli_fetch_array($result_antrian_count)['total'];
            $no_antrian = $antrian_count + 1;

            // Insert data service jemput
            $tanggal_input = $_POST['id-date-picker-1'] ?? date('d/m/Y');
            $tanggal_service = date('Y-m-d', strtotime(str_replace('/', '-', $tanggal_input)));
            $jam_input = $_POST['jam_service'] ?? date('H:i');
            $kode_pelanggan = $_POST['kode_pelanggan'] ?? '';
            $no_polisi = $_POST['no_polisi'] ?? '';
            $keluhan = $_POST['keluhan'] ?? '';

            $query_insert_service = "INSERT INTO tblservice (
                no_service, tanggal, jam, no_pelanggan, no_polisi, keluhan,
                status_servis, tipe_service, user_input, kd_cabang, created_at
            ) VALUES (
                '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan',
                '$no_polisi', '$keluhan', 'dijemput', 'jemput', '$_nama', '$kd_cabang', NOW()
            )";

            if(mysqli_query($koneksi, $query_insert_service)) {
                // Insert ke tabel antrian
                $query_insert_antrian = "INSERT INTO tb_antrian_servis (
                    no_service, no_antrian, tanggal, jam, no_pelanggan, no_polisi,
                    status_antrian, tipe_service, created_at
                ) VALUES (
                    '$no_service', '$no_antrian', '$tanggal_service', '$jam_input',
                    '$kode_pelanggan', '$no_polisi', 'dijemput', 'jemput', NOW()
                )";

                if(mysqli_query($koneksi, $query_insert_antrian)) {
                    echo "<script>
                        alert('Service Jemput berhasil disimpan!\\nNomor Service: $no_service\\nNomor Antrian: $no_antrian');
                        window.location.href = 'servis-input-reguler-jemput-rst.php?snoserv=$no_service';
                    </script>";
                }
            } else {
                echo "<script>alert('Error: " . mysqli_error($koneksi) . "');</script>";
            }
        }
    }

    // Add Work Order
    if(isset($_POST['btnaddworkorder'])) {
        $no_service = $_POST['txtnosrv'];
        $kode_wo = $_POST['txtcariwo'];

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        if($kode_wo != '') {
            // Check if work order exists
            $check_wo = mysqli_query($koneksi,"SELECT COUNT(*) as count FROM tbservis_workorder
                                               WHERE no_service='$no_service' AND kode_wo='$kode_wo'");
            $check_result = mysqli_fetch_array($check_wo);

            if($check_result['count'] == 0) {
                // Insert work order
                mysqli_query($koneksi,"INSERT INTO tbservis_workorder
                                      (no_service, kode_wo, status_pengerjaan)
                                      VALUES
                                      ('$no_service','$kode_wo','diproses')");

                // Auto add jasa dan barang dari work order
                $detail_wo = mysqli_query($koneksi,"SELECT kode_barang, tipe, harga, total, jumlah
                                                    FROM tbworkorderdetail
                                                    WHERE kode_wo='$kode_wo'");

                while($detail = mysqli_fetch_array($detail_wo)) {
                    if($detail['tipe'] == '1') { // Jasa
                        // Try to get waktu from tbworkorderheader
                        $waktu = 0; // Default waktu value
                        try {
                            $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tbworkorderheader LIKE 'waktu'");
                            if(mysqli_num_rows($check_waktu) > 0) {
                                $waktu_query = mysqli_query($koneksi,"SELECT waktu FROM tbworkorderheader WHERE kode_wo='{$detail['kode_barang']}'");
                                if($waktu_query && $waktu_data = mysqli_fetch_array($waktu_query)) {
                                    $waktu = $waktu_data['waktu'] ?? 0;
                                }
                            }
                        } catch (Exception $e) {
                            $waktu = 0;
                        }

                        // Get next nobaris for jasa
                        $q_nobaris_jasa = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
                        $nobaris_jasa_data = mysqli_fetch_array($q_nobaris_jasa);
                        $nobaris_jasa = $nobaris_jasa_data['next_nobaris'] ?? 1;

                        // Check if waktu column exists in tblservis_jasa before inserting
                        $check_jasa_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
                        if(mysqli_num_rows($check_jasa_waktu) > 0) {
                            // Insert with waktu column
                            mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                  (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                                  VALUES
                                                  ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '{$detail['harga']}', '$waktu', '0', '{$detail['total']}')");
                        } else {
                            // Insert without waktu column
                            mysqli_query($koneksi,"INSERT INTO tblservis_jasa
                                                  (no_service, nobaris, no_item, harga, potongan, total)
                                                  VALUES
                                                  ('$no_service', '$nobaris_jasa', '{$detail['kode_barang']}', '{$detail['harga']}', '0', '{$detail['total']}')");
                        }
                    } else { // Barang
                        // Get next nobaris for barang
                        $q_nobaris_brg = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_barang WHERE no_service='$no_service'");
                        $nobaris_brg_data = mysqli_fetch_array($q_nobaris_brg);
                        $nobaris_brg = $nobaris_brg_data['next_nobaris'] ?? 1;

                        mysqli_query($koneksi,"INSERT INTO tblservis_barang
                                              (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                              VALUES
                                              ('$no_service', '$nobaris_brg', '{$detail['kode_barang']}', '{$detail['jumlah']}', '0', '{$detail['harga']}', '0', '{$detail['total']}')");
                    }
                }

                echo "<script>
                    alert('Work Order berhasil ditambahkan dan item otomatis dimasukkan!');
                    window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=&kdjasa=&kdwo=';
                </script>";
            } else {
                echo "<script>alert('Work Order sudah ada untuk service ini!');</script>";
            }
        }
    }

    // Update Status Work Order - Fixed table name
    if(isset($_POST['btnupdatestatuswo'])) {
        $wo_id = $_POST['wo_id'];
        $status_wo = $_POST['status_wo'];
        $keterangan = $_POST['keterangan_wo'] ?? '';

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        $update_status = "UPDATE tbservis_workorder
                         SET status_pengerjaan='$status_wo',
                             keterangan_tidak_selesai='$keterangan'
                         WHERE id='$wo_id'";

        if(mysqli_query($koneksi, $update_status)) {
            echo "<script>
                alert('Status Work Order berhasil diupdate!');
                window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
            </script>";
        } else {
            echo "<script>alert('Error update status: " . mysqli_error($koneksi) . "');</script>";
        }
    }

    // Delete Work Order - Fixed table name
    if(isset($_POST['btndeletewo'])) {
        $wo_id = $_POST['wo_id'];
        $no_service = $_POST['txtnosrv'];

        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';
        $txtcariwo = $_POST['txtcariwo'] ?? '';

        $delete_wo = "DELETE FROM tbservis_workorder WHERE id='$wo_id'";

        if(mysqli_query($koneksi, $delete_wo)) {
            echo "<script>
                alert('Work Order berhasil dihapus!');
                window.location='servis-input-reguler-jemput-rst.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo';
            </script>";
        } else {
            echo "<script>alert('Error hapus Work Order: " . mysqli_error($koneksi) . "');</script>";
        }
    }

    // Handler untuk Cari Item Barang
    if(isset($_POST['btncari'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcaribrg = mysqli_real_escape_string($koneksi, $_POST['txtcaribrg'] ?? '');

        // Redirect to item search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jemput-item-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcaribrg) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=items&_from=jemput-rst');</script>";
        exit;
    }

    // Handler untuk Cari Item Jasa
    if(isset($_POST['btncarisrv'])) {
        $no_service_post = !empty($no_service) ? $no_service : ($_POST['txtnosrv'] ?? '');
        $txtcarisrv = mysqli_real_escape_string($koneksi, $_POST['txtcarisrv'] ?? '');

        // Redirect to jasa search page
        $cbocari = "";
        $cbourut = "52";
        echo "<script>window.location=('servis-add-jemput-jasa-cari.php?snoserv=" . urlencode($no_service_post) . "&_key=" . urlencode($txtcarisrv) . "&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=jasa&_from=jemput-rst');</script>";
        exit;
    }

    // Search Work Order - Always redirect to search page for better UX
    if(isset($_POST['btncariwo'])) {
        $no_service = $_POST['txtnosrv'];
        $txtcariwo = $_POST['txtcariwo'] ?? '';
        $txtcarisrv = $_POST['txtcarisrv'] ?? '';
        $txtcaribrg = $_POST['txtcaribrg'] ?? '';

        $km_skr = $_POST['txtkm_skr'] ?? 0;
        $km_berikut = $_POST['txtkm_next'] ?? 0;

        if(!empty($no_service)) {
            // Always redirect to workorder search page for browsing and selection with tab parameter
            $cbocari = "";
            $cbourut = "52";
            echo "<script>window.location='servis-add-jemput-workorder-cari.php?snoserv=$no_service&_key=$txtcariwo&_cari=$cbocari&_urut=$cbourut&_flt=asc&_tab=workorder';</script>";
        } else {
            echo "<script>alert('Nomor service harus diisi terlebih dahulu!');</script>";
        }
    }

    // Get service data if exists
    if(!empty($no_service)) {
		$cari_kd=mysqli_query($koneksi,"SELECT
                                        tanggal,
                                        DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_serv,
                                        jam, no_pelanggan, no_polisi, status_servis
                                        FROM tblservice
                                        WHERE no_service='$no_service'");
		$tm_cari=mysqli_fetch_array($cari_kd);
        $tanggal=$tm_cari['tanggal_serv'] ?? '';
        $tanggal_srv=$tm_cari['tanggal'] ?? '';
        $jam=$tm_cari['jam'] ?? '';
        $kode_pelanggan=$tm_cari['no_pelanggan'] ?? '';
        $no_polisi=$tm_cari['no_polisi'] ?? '';
        $status_servis=$tm_cari['status_servis'] ?? 'datang';
    } else {
        $tanggal = date('d/m/Y');
        $jam = date('H:i');
        $kode_pelanggan = '';
        $no_polisi = '';
        $status_servis = 'datang';
    }

    // Get customer data with category info
    if(!empty($kode_pelanggan)) {
        $customerBundle = fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $no_polisi);
        $namapelanggan=$customerBundle['namapelanggan'] ?? '';
        $potongan_pelanggan = $customerBundle['potongan'] ?? 0;
        $tipe_potongan = $customerBundle['tipepot'] ?? '';
        $kategori_pelanggan = $customerBundle['kgrup'] ?? '001';
    } else {
        $namapelanggan = '';
        $potongan_pelanggan = 0;
        $tipe_potongan = '';
        $kategori_pelanggan = '001';
    }

    // Get vehicle data
    if(!empty($no_polisi)) {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $no_polisi, $kode_pelanggan);
        $vehicleRow = $bundle['vehicle'] ?? [];
        $customerRow = $bundle['customer'] ?? [];
        $pemilik=$vehicleRow['pemilik'] ?? '';
        $jenis=$vehicleRow['jenis'] ?? '';
        $merek=$vehicleRow['tipe'] ?? '';
        $warna=$vehicleRow['warna'] ?? '';
        $no_rangka=$vehicleRow['no_rangka'] ?? '';
        $no_mesin=$vehicleRow['no_mesin'] ?? '';
        if (empty($namapelanggan) && !empty($customerRow['namapelanggan'])) {
            $namapelanggan = $customerRow['namapelanggan'];
            $potongan_pelanggan = $customerRow['potongan'] ?? $potongan_pelanggan;
            $tipe_potongan = $customerRow['tipepot'] ?? $tipe_potongan;
            $kategori_pelanggan = $customerRow['kgrup'] ?? $kategori_pelanggan;
            if (empty($kode_pelanggan) && !empty($customerRow['nopelanggan'])) {
                $kode_pelanggan = $customerRow['nopelanggan'];
            }
        }
    } else {
        $pemilik = '';
        $jenis = '';
        $merek = '';
        $warna = '';
        $no_rangka = '';
        $no_mesin = '';
    }

        $km_skr="";
        $km_berikut="";

        // Function to determine discount based on customer category
        function getDiscountByCategory($kategori, $potongan_existing = 0, $tipe_pot = '') {
            $discount_percent = 0;

            // Kategori member dengan diskon otomatis
            switch($kategori) {
                case 'GOLD':
                case 'gold':
                case 'G':
                    $discount_percent = 15; // Member Gold 15%
                    break;
                case 'SILVER':
                case 'silver':
                case 'S':
                    $discount_percent = 10; // Member Silver 10%
                    break;
                case 'BRONZE':
                case 'bronze':
                case 'B':
                    $discount_percent = 5; // Member Bronze 5%
                    break;
                default:
                    // Gunakan potongan yang sudah ada di database pelanggan
                    if($potongan_existing > 0) {
                        if($tipe_pot == '%' || $tipe_pot == 'P') {
                            $discount_percent = $potongan_existing;
                        }
                    }
                    break;
            }

            return $discount_percent;
        }

        // Calculate current totals from database
        $total_service = 0;
        $total_waktu = 0;
        $total_barang = 0;

        if(!empty($no_service)) {
            $cari_service = mysqli_query($koneksi,"SELECT COALESCE(sum(total), 0) as tot,
                                                  COALESCE(sum(waktu), 0) as tot_waktu
                                                  FROM tblservis_jasa
                                                  WHERE no_service='$no_service'");
            $tm_service = mysqli_fetch_array($cari_service);
            $total_service = $tm_service['tot'] ?? 0;
            $total_waktu = $tm_service['tot_waktu'] ?? 0;

            $cari_barang = mysqli_query($koneksi,"SELECT COALESCE(sum(total), 0) as tot
                                                 FROM tblservis_barang
                                                 WHERE no_service='$no_service'");
            $tm_barang = mysqli_fetch_array($cari_barang);
            $total_barang = $tm_barang['tot'] ?? 0;
        }

        // Calculate totals and apply automatic discount
        $tot = $total_service + $total_barang;

        // Get automatic discount based on customer category
        $auto_discount_percent = getDiscountByCategory($kategori_pelanggan, $potongan_pelanggan, $tipe_potongan);

        // Apply discount
        $discount_amount = $tot * ($auto_discount_percent / 100);
        $net = $tot - $discount_amount;
        $bayar = $net;
        $kembalian = $bayar - $net;

        // Initialize mechanic variables
        $kepala_mekanik1 = "";
        $persen_kepala1 = 0;
        $kepala_mekanik2 = "";
        $persen_kepala2 = 0;
    $admin1 = "";
    $persen_admin1 = 0;
    $admin2 = "";
    $persen_admin2 = 0;
        $mekanik1 = "";
    $persen_kerja1 = 0;
        $mekanik2 = "";
    $persen_kerja2 = 0;
        $mekanik3 = "";
    $persen_kerja3 = 0;
        $mekanik4 = "";
    $persen_kerja4 = 0;

    // Initialize additional variables for templates
    $txtcaribrg = $_GET['kd'] ?? '';
    $txtcarisrv = $_GET['kdjasa'] ?? '';
    $txtnamaitem = '';
    $txtnamasrv = '';

    // Get item data if searching for item
    if(!empty($txtcaribrg)) {
        $cari_item = mysqli_query($koneksi,"SELECT namaitem FROM view_cari_item WHERE noitem='$txtcaribrg'");
        $tm_item = mysqli_fetch_array($cari_item);
        $txtnamaitem = $tm_item['namaitem'] ?? '';
    }

    // Get service data if searching for service
    if(!empty($txtcarisrv)) {
        $cari_serv = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='$txtcarisrv'");
        $tm_serv = mysqli_fetch_array($cari_serv);
        $txtnamasrv = $tm_serv['nama_wo'] ?? '';
    }

    // Get existing mechanic data if service exists
    if(!empty($no_service)) {
        // Try to get data from existing service table
        $query_existing = "SELECT * FROM tblservice WHERE no_service='$no_service'";
        $result_existing = mysqli_query($koneksi, $query_existing);
        if($result_existing && mysqli_num_rows($result_existing) > 0) {
            $existing_data = mysqli_fetch_array($result_existing);
            // Initialize with correct column names from database
            $kepala_mekanik1 = $existing_data['kepala_mekanik1'] ?? '';
            $persen_kepala1 = $existing_data['persen_kepala_mekanik1'] ?? 0;
            $kepala_mekanik2 = $existing_data['kepala_mekanik2'] ?? '';
            $persen_kepala2 = $existing_data['persen_kepala_mekanik2'] ?? 0;
            $admin1 = $existing_data['admin1'] ?? '';
            $persen_admin1 = $existing_data['persen_admin1'] ?? 0;
            $admin2 = $existing_data['admin2'] ?? '';
            $persen_admin2 = $existing_data['persen_admin2'] ?? 0;
            $mekanik1 = $existing_data['mekanik1'] ?? '';
            $persen_kerja1 = $existing_data['persen_mekanik1'] ?? 0;
            $mekanik2 = $existing_data['mekanik2'] ?? '';
            $persen_kerja2 = $existing_data['persen_mekanik2'] ?? 0;
            $mekanik3 = $existing_data['mekanik3'] ?? '';
            $persen_kerja3 = $existing_data['persen_mekanik3'] ?? 0;
            $mekanik4 = $existing_data['mekanik4'] ?? '';
            $persen_kerja4 = $existing_data['persen_mekanik4'] ?? 0;
            $metode_pembayaran = $existing_data['metode_pembayaran'] ?? 'Tunai';
            $bukti_pembayaran = $existing_data['bukti_pembayaran'] ?? '';
        }
    }

    // ========== AUTO-FILL KEPALA MEKANIK HARIAN ==========
    if (empty($kepala_mekanik1)) {
        $tanggal_hari_ini = date('Y-m-d');
        // Gunakan $kd_cabang dari session yang sudah didefinisikan di awal file
        $query_km_harian = "SELECT kepala_mekanik_1, kepala_mekanik_2 FROM tbl_kepala_mekanik_harian 
                            WHERE kode_cabang='$kd_cabang' 
                            AND tanggal_kerja='$tanggal_hari_ini' LIMIT 1";
        $result_km_harian = mysqli_query($koneksi, $query_km_harian);
        
        if ($result_km_harian && mysqli_num_rows($result_km_harian) > 0) {
            $data_km_harian = mysqli_fetch_array($result_km_harian);
            $kepala_mekanik1 = $data_km_harian['kepala_mekanik_1'];
            
            // Logika Persentase Otomatis
            if (!empty($data_km_harian['kepala_mekanik_2'])) {
                // Jika ada 2 kepala mekanik, set default 50:50
                if (empty($kepala_mekanik2)) {
                   $kepala_mekanik2 = $data_km_harian['kepala_mekanik_2'];
                   $persen_kepala1 = 50;
                   $persen_kepala2 = 50;
                }
            } else {
                // Jika hanya 1 kepala mekanik, set 100%
                $persen_kepala1 = 100;
                $persen_kepala2 = 0;
            }

        } else {
             // Notification for missing daily schedule
             echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if(!$('.alert-km-missing').length) {
                         var warningMsg = '<div class=\"alert alert-danger alert-dismissible alert-km-missing\" style=\"margin: 10px 0;\">';
                         warningMsg += '<button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>';
                         warningMsg += '<i class=\"icon fa fa-warning\"></i> <strong>PERHATIAN!</strong> ';
                         warningMsg += 'Kepala Mekanik Harian untuk tanggal hari ini belum diinput. ';
                         warningMsg += '<a href=\"input_kepala_mekanik_harian.php\" style=\"font-weight:bold; text-decoration:underline;\">Klik disini untuk input sekarang</a>';
                         warningMsg += '</div>';
                         $('.page-content').prepend(warningMsg);
                    }
                });
            </script>";
        }
    }
    // ========== END AUTO-FILL KEPALA MEKANIK HARIAN ==========

    // Initialize payment variables if not set
    if(!isset($metode_pembayaran)) {
        $metode_pembayaran = 'Tunai';
    }
    if(!isset($bukti_pembayaran)) {
        $bukti_pembayaran = '';
    }

    // Initialize other variables
    $keluhan = '';
    $catatan = '';
    $no_workorder = '';
    $tanggal_wo = date('d/m/Y');
    $estimasi_selesai = '';
    $prioritas_wo = 'urgent'; // Jemput usually urgent
    $deskripsi_pekerjaan = '';
    $instruksi_khusus = '';
    $catatan_wo = '';

    // Initialize antrian variables
    $current_antrian = '';
    $suggested_antrian = '';

    // Get current antrian if service exists
    if(!empty($no_service)) {
        $query_current_antrian = "SELECT no_antrian FROM tb_antrian_servis WHERE no_service='$no_service'";
        $result_current_antrian = mysqli_query($koneksi, $query_current_antrian);
        if($result_current_antrian && mysqli_num_rows($result_current_antrian) > 0) {
            $antrian_data = mysqli_fetch_array($result_current_antrian);
            $current_antrian = $antrian_data['no_antrian'];
        }
    }

    // Generate suggested antrian number
    $tanggal_today = date('Y-m-d');
    $query_suggested_antrian = "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_today'";
    $result_suggested_antrian = mysqli_query($koneksi, $query_suggested_antrian);
    if($result_suggested_antrian) {
        $antrian_count = mysqli_fetch_array($result_suggested_antrian)['total'];
        $suggested_antrian = $antrian_count + 1;
    } else {
        $suggested_antrian = 1;
    }

    // Get additional service data if exists
    if(!empty($no_service)) {
        // Get data from existing service table if available
        $query_service_detail = "SELECT * FROM tblservice WHERE no_service='$no_service'";
        $result_service_detail = mysqli_query($koneksi, $query_service_detail);
        if($result_service_detail && mysqli_num_rows($result_service_detail) > 0) {
            $service_detail = mysqli_fetch_array($result_service_detail);
            // Use existing data from tblservice if available
            $keluhan = $service_detail['keluhan'] ?? '';
            $catatan = $service_detail['catatan'] ?? '';
            $no_workorder = $service_detail['no_workorder'] ?? '';
            $tanggal_wo = $service_detail['tanggal_wo'] ?? date('d/m/Y');
            $estimasi_selesai = $service_detail['estimasi_selesai'] ?? '';
            $prioritas_wo = $service_detail['prioritas_wo'] ?? 'urgent';
            $deskripsi_pekerjaan = $service_detail['deskripsi_pekerjaan'] ?? '';
            $instruksi_khusus = $service_detail['instruksi_khusus'] ?? '';
            $catatan_wo = $service_detail['catatan_wo'] ?? '';
        }
    }

    // Payment Processing for Jemput RST
    if(isset($_POST['btnbayar'])) {
        $no_service= $_POST['txtnosrv'] ?? $no_service;
        $km_skr=$_POST['txtkm_skr'] ?? 0;
        $km_berikut=$_POST['txtkm_next'] ?? 0;

        $diskon_member = $_POST['txtdiskon_member'] ?? 0;
        $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'] ?? 0;
        $total_diskon_persen = ($diskon_member > 0 ? $diskon_member : $txtpotfaktur_persen);
        $txtpotfaktur_nom= str_replace(['.', ','], '', $_POST['txtpotfaktur_nom'] ?? '0'); // Remove formatting
        $txtpajak_persen= $_POST['txtpajak_persen'] ?? 0;
        $metode_pembayaran= $_POST['metode_pembayaran'] ?? 'Tunai';
        $txtbayar= str_replace(['.', ','], '', $_POST['txtbayar'] ?? '0'); // Remove formatting

        // Handle bukti pembayaran upload
        $bukti_pembayaran_path = '';
        if($metode_pembayaran != 'Tunai' && isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] == 0) {
            $upload_dir = 'uploads/bukti_pembayaran/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = strtolower(pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

            if(in_array($file_ext, $allowed_ext) && $_FILES['bukti_pembayaran']['size'] <= 2097152) { // 2MB max
                $new_filename = 'bukti_' . $no_service . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;

                if(move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $upload_path)) {
                    $bukti_pembayaran_path = $upload_path;
                }
            }
        }

        // Get mechanic data
        $kepala_mekanik1 = $_POST['cbokepala_mekanik1'] ?? '';
        $persen_kepala1 = $_POST['txtpersen_kepala1'] ?? 0;
        $kepala_mekanik2 = $_POST['cbokepala_mekanik2'] ?? '';
        $persen_kepala2 = $_POST['txtpersen_kepala2'] ?? 0;
        $mekanik1 = $_POST['cbomekanik1'] ?? '';
        $persen_mekanik1 = $_POST['txtpersen_mekanik1'] ?? 0;
        $mekanik2 = $_POST['cbomekanik2'] ?? '';
        $persen_mekanik2 = $_POST['txtpersen_mekanik2'] ?? 0;
        $mekanik3 = $_POST['cbomekanik3'] ?? '';
        $persen_mekanik3 = $_POST['txtpersen_mekanik3'] ?? 0;
        $mekanik4 = $_POST['cbomekanik4'] ?? '';
        $persen_mekanik4 = $_POST['txtpersen_mekanik4'] ?? 0;

        // Pre-payment validations (roles & keluhan status)
        $err_msgs = array();
        $has_kepala1 = !empty($kepala_mekanik1);
        $has_kepala2 = !empty($kepala_mekanik2);
        $kepala_count = ($has_kepala1?1:0) + ($has_kepala2?1:0);
        $pk1 = floatval($persen_kepala1);
        $pk2 = floatval($persen_kepala2);
        if($kepala_count == 0) { $err_msgs[] = 'Kepala Mekanik wajib diisi.'; }
        if($kepala_count == 1) { $p = $has_kepala1 ? $pk1 : $pk2; if(round($p,2) != 100) { $err_msgs[] = 'Persentase Kepala Mekanik harus 100% jika hanya satu.'; } }
        if($kepala_count == 2) { if(round($pk1 + $pk2,2) != 100) { $err_msgs[] = 'Total persentase Kepala Mekanik harus 100%.'; } }
        if(($pk1 < 0 || $pk1 > 100) || ($pk2 < 0 || $pk2 > 100)) { $err_msgs[] = 'Persentase Kepala Mekanik harus 0-100.'; }

        // Admin validations: require at least admin1
        $has_admin1 = isset($_POST['cboadmin1']) && $_POST['cboadmin1'] !== '';
        $has_admin2 = isset($_POST['cboadmin2']) && $_POST['cboadmin2'] !== '';
        $pa1 = floatval($_POST['txtpersen_admin1'] ?? 0);
        $pa2 = floatval($_POST['txtpersen_admin2'] ?? 0);
        $admin_count = ($has_admin1?1:0) + ($has_admin2?1:0);
        if(!$has_admin1) { $err_msgs[] = 'Admin wajib diisi (minimal Admin 1).'; }
        if($admin_count == 1) { $p = $has_admin1 ? $pa1 : $pa2; if(round($p,2) != 100) { $err_msgs[] = 'Persentase Admin harus 100% jika hanya satu.'; } }
        if($admin_count == 2) { if(round($pa1 + $pa2,2) != 100) { $err_msgs[] = 'Total persentase Admin harus 100%.'; } }
        if(($pa1 < 0 || $pa1 > 100) || ($pa2 < 0 || $pa2 > 100)) { $err_msgs[] = 'Persentase Admin harus 0-100.'; }

        // Mekanik validations
        $mekanik_vals = array(); $mekanik_pers = array();
        if(!empty($mekanik1)) { $mekanik_vals[] = $mekanik1; $mekanik_pers[] = floatval($persen_mekanik1); }
        if(!empty($mekanik2)) { $mekanik_vals[] = $mekanik2; $mekanik_pers[] = floatval($persen_mekanik2); }
        if(!empty($mekanik3)) { $mekanik_vals[] = $mekanik3; $mekanik_pers[] = floatval($persen_mekanik3); }
        if(!empty($mekanik4)) { $mekanik_vals[] = $mekanik4; $mekanik_pers[] = floatval($persen_mekanik4); }
        if(count($mekanik_vals) == 0) { $err_msgs[] = 'Mekanik wajib diisi (minimal 1).'; }
        $sum_mek = 0.0; foreach($mekanik_pers as $p) { if($p < 0 || $p > 100) { $err_msgs[] = 'Persentase Mekanik harus 0-100.'; } $sum_mek += $p; }
        if(count($mekanik_vals) == 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Persentase Mekanik harus 100% jika hanya satu.'; } }
        if(count($mekanik_vals) > 1) { if(round($sum_mek,2) != 100) { $err_msgs[] = 'Total persentase Mekanik harus 100%.'; } }

        // Keluhan still in progress?
        $___ns = mysqli_real_escape_string($koneksi, $no_service);
        $cek_keluhan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_keluhan_status WHERE no_service='".$___ns."' AND status_pengerjaan IN ('datang','diproses')");
        if($cek_keluhan && ($rk = mysqli_fetch_assoc($cek_keluhan))) { if(intval($rk['c']) > 0) { $err_msgs[] = 'Masih ada keluhan dengan status dalam proses/belum selesai.'; } }

        // 2. Validasi Temuan (New) - Harus Disetujui/Ditolak/Selesai, tidak boleh Ditemukan/Ditawarkan
        $cek_temuan = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan WHERE no_service='".$___ns."' AND status_temuan IN ('ditemukan','ditawarkan')");
        if($cek_temuan && ($rt = mysqli_fetch_assoc($cek_temuan))) {
            if(intval($rt['c']) > 0) { $err_msgs[] = 'Masih ada TEMUAN yang belum diproses (status Ditemukan/Ditawarkan). Harap setujui atau tolak temuan tsb.'; }
        }

        // 3. Validasi Penawaran Part/Jasa (New) - Tidak boleh status Pending
        $cek_part_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_part WHERE no_service='".$___ns."' AND status='pending'");
        if($cek_part_pending && ($rp = mysqli_fetch_assoc($cek_part_pending))) {
            if(intval($rp['c']) > 0) { $err_msgs[] = 'Masih ada Penawaran PART yang statusnya Pending.'; }
        }

        $cek_jasa_pending = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_temuan_jasa WHERE no_service='".$___ns."' AND status='pending'");
        if($cek_jasa_pending && ($rj = mysqli_fetch_assoc($cek_jasa_pending))) {
            if(intval($rj['c']) > 0) { $err_msgs[] = 'Masih ada Penawaran JASA yang statusnya Pending.'; }
        }

        if(!empty($err_msgs)) {
            $msg = implode("\\n- ", $err_msgs);
            echo "<script>window.alert('Tidak dapat memproses pembayaran karena:\\n- ".$msg."'); window.location='servis-input-reguler-jemput-rst.php?snoserv=".addslashes($no_service)."&tab=actions#service-actions';</script>";
            exit;
        }


    // == Total dari Item & Waktu Service ==============
        $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot,
                                        sum(waktu) as tot_waktu
                                        FROM tblservis_jasa
                                        WHERE
                                        no_service='$no_service'");
        $tm_cari=mysqli_fetch_array($cari_kd);
        $total_service_pay=$tm_cari['tot'];
        $total_waktu_pay=$tm_cari['tot_waktu'];

    // == Total dari Item Barang ==============
    $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot
                                        FROM tblservis_barang
                                        WHERE
                                        no_service='$no_service'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $total_barang_pay=$tm_cari['tot'];

        $tot_pay=$total_service_pay+$total_barang_pay;
        $diskon_nominal = $tot_pay * ($total_diskon_persen / 100);
        $ppn=($tot_pay - $diskon_nominal)*($txtpajak_persen/100);
        $net_pay=$tot_pay-$diskon_nominal+$ppn;
        $kembalian_pay=$txtbayar-$net_pay;

        // Validate payment amount
        if($txtbayar < $net_pay) {
            echo"<script>window.alert('Jumlah pembayaran tidak mencukupi! Total: Rp " . number_format($net_pay, 0, ',', '.') . ", Bayar: Rp " . number_format($txtbayar, 0, ',', '.') . "');
            window.history.back();</script>";
            exit;
        }

        if($net_pay <= 0) {
            echo"<script>window.alert('Total service harus lebih dari 0!');
            window.history.back();</script>";
            exit;
        }

        $tgl_bayar_set = '';
        $chk_tgl_bayar = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'tgl_bayar'");
        if ($chk_tgl_bayar && mysqli_num_rows($chk_tgl_bayar) > 0) {
            $tgl_bayar_set = 'tgl_bayar=NOW(),';
        }

        $update_query = "UPDATE tblservice
                                SET status='2',
                                total='$tot_pay',
                                diskon_persen='$total_diskon_persen', diskon_nom='$diskon_nominal',
                                ppn_persen='$txtpajak_persen', ppn_nom='$ppn',
                                total_grand='$net_pay',
                                total_akhir='$net_pay',
                                total_waktu='$total_waktu_pay',
                                km_skr='$km_skr',
                                km_berikut='$km_berikut',
                                status_servis='bayar',
                                {$tgl_bayar_set}
                                kepala_mekanik1='$kepala_mekanik1',
                                kepala_mekanik2='$kepala_mekanik2',
                                persen_kepala_mekanik1='$persen_kepala1',
                                persen_kepala_mekanik2='$persen_kepala2',
                                mekanik1='$mekanik1',
                                mekanik2='$mekanik2',
                                mekanik3='$mekanik3',
                                mekanik4='$mekanik4',
                                persen_mekanik1='$persen_mekanik1',
                                persen_mekanik2='$persen_mekanik2',
                                persen_mekanik3='$persen_mekanik3',
                                persen_mekanik4='$persen_mekanik4',
                                metode_pembayaran='$metode_pembayaran',
                                bayar='$txtbayar',
                                kembali='$kembalian_pay'";

        // Add bukti_pembayaran if uploaded
        if(!empty($bukti_pembayaran_path)) {
            $update_query .= ", bukti_pembayaran='$bukti_pembayaran_path'";
        }

        $update_query .= " WHERE no_service='$no_service'";

        if(!mysqli_query($koneksi, $update_query)) {
            die("Error Update Service: " . mysqli_error($koneksi));
        }

        // 🆕 AUTO-UPDATE STATISTIK PELANGGAN, MEMBER TIER & HISTORY SERVICE
        $get_customer = mysqli_query($koneksi, "SELECT no_pelanggan FROM tblservice WHERE no_service='$no_service'");
        if ($get_customer && $customer_row = mysqli_fetch_assoc($get_customer)) {
            $no_pelanggan_bayar = $customer_row['no_pelanggan'];
            if (!empty($no_pelanggan_bayar)) {
                // Gunakan fungsi processAfterPayment untuk update semua data
                if (function_exists('processAfterPayment')) {
                    $payment_result = processAfterPayment($koneksi, $no_pelanggan_bayar, $no_service, 'jemput');
                    // Log jika naik tier
                    if ($payment_result['naik_tier']) {
                        error_log("Customer $no_pelanggan_bayar naik tier: " . json_encode($payment_result['tier_info']));
                    }
                } else {
                    // Fallback ke fungsi lama jika fungsi baru belum ada
                    updateStatistikPelangganAfterPayment($koneksi, $no_pelanggan_bayar, $no_service);
                }
            }
        }

        // Update stock for items used in service
        $sql = mysqli_query($koneksi,"SELECT * FROM tblservis_barang
                                        WHERE
                                        no_service='$no_service'");
        while ($tampil = mysqli_fetch_array($sql)) {
            $no_item=$tampil['no_item'];
            $qty=$tampil['quantity'];
            mysqli_query($koneksi,"INSERT INTO tbstok
                                (tipe, no_transaksi, no_item,
                                tanggal, masuk, keluar, keterangan,
                                kd_cabang)
                                VALUES
                                ('4','$no_service','$no_item',
                                '$tanggal_srv','0','$qty',
                                'Penjualan Service Jemput RST','$kd_cabang')");
        }

        // ========== KIRIM WHATSAPP OTOMATIS ==========
        try {
            if(file_exists('config_whatsapp.php')) {
                require_once 'config_whatsapp.php';
            }

            if(file_exists('class_whatsapp_automation.php')) {
                require_once 'class_whatsapp_automation.php';
            }

            if(defined('WA_API_ENABLED') && WA_API_ENABLED &&
               defined('WA_AUTO_SEND_AFTER_PAYMENT') && WA_AUTO_SEND_AFTER_PAYMENT) {

                if(class_exists('WhatsAppAutomation')) {
                    if(defined('WA_SEND_DELAY')) sleep(WA_SEND_DELAY);

                    $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
                    $wa_result = $wa->sendTerimaKasih($no_service);

                    if(function_exists('logWhatsAppActivity')) {
                        if(isset($wa_result['success']) && $wa_result['success']) {
                            $phone = isset($wa_result['phone']) ? $wa_result['phone'] : '';
                            logWhatsAppActivity($no_service, $phone, 'sent', 'Auto-sent after payment (jemput-rst)');
                        } else {
                            $msg = isset($wa_result['message']) ? $wa_result['message'] : 'Unknown error';
                            logWhatsAppActivity($no_service, '', 'failed', $msg);
                        }
                    }
                }
            }
        } catch(Exception $e) {
            if(function_exists('logWhatsAppActivity')) {
                logWhatsAppActivity($no_service, '', 'error', 'Exception: ' . $e->getMessage());
            }
        }
        // ========== END KIRIM WHATSAPP ==========

    // Success message with redirect options
    if(function_exists('clearSessionDiscount')) { clearSessionDiscount(); }
    echo"<script>
        if(confirm('Pembayaran Service Jemput RST Berhasil!\\nKembalian: Rp " . number_format($kembalian_pay, 0, ',', '.') . "\\n\\nCatatan: Service RST mungkin memerlukan penyesuaian biaya setelah analisis.\\nKendaraan akan diantar kembali setelah selesai.\\n\\nKlik OK untuk print invoice\\nKlik Cancel untuk kembali ke daftar servis')) {
            window.location='servis-print.php?snoserv=$no_service';
        } else {
            window.location='servis-reguler.php';
        }
    </script>";
    }
}

// Determine active tab based on URL parameters
$active_tab = 'service-details'; // default tab

// Priority order: explicit tab parameter > search parameters > default
if (isset($_GET['tab'])) {
    switch($_GET['tab']) {
        case 'workorder':
            $active_tab = 'workorder-details';
            break;
        case 'pickup':
            $active_tab = 'pickup-details';
            break;
        case 'items':
            $active_tab = 'service-items';
            break;
        case 'jasa':
            $active_tab = 'service-jasa';
            break;
        case 'actions':
            $active_tab = 'service-actions';
            break;
        default:
            $active_tab = 'service-details';
            break;
    }
} elseif (!empty($txtcaribrg)) {
    $active_tab = 'service-items'; // Item Barang tab
} elseif (!empty($txtcarisrv)) {
    $active_tab = 'service-jasa'; // Item Jasa tab
} elseif (!empty($txtcariwo)) {
    $active_tab = 'workorder-details'; // Work Order tab
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title><?php include "../lib/titel.php"; ?> - Detail Service Jemput (Selesai)</title>

    <meta name="description" content="Detail Service Jemput - Redesign v2.0" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    <!-- Bootstrap 4 & FontAwesome 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

    <!-- jQuery UI for datepicker -->
    <link rel="stylesheet" href="assets/css/jquery-ui.custom.min.css" />

    <!-- Fonts -->
    <link rel="stylesheet" href="assets/css/fonts.googleapis.com.css" />

    <!-- Redesign Styles -->
    <?php include "_template/_redesign_styles.php"; ?>

    <style>
    /* Page-specific overrides - Jemput RST (Read-Only) Mode */
    body {
        background: #f4f6f9;
        font-family: 'Open Sans', 'Segoe UI', Tahoma, sans-serif;
    }

    .rd-page-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .rd-page-header {
        background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
        color: white;
        padding: 20px 24px;
        border-radius: var(--rd-radius-lg);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--rd-shadow-md);
    }

    .rd-page-header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rd-page-header .rd-breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        opacity: 0.9;
    }

    .rd-page-header .rd-breadcrumb a {
        color: white;
        text-decoration: none;
    }

    .rd-page-header .rd-breadcrumb a:hover {
        text-decoration: underline;
    }

    /* Quick Summary Bar - Jemput Teal */
    .rd-quick-summary {
        background: white;
        border-radius: var(--rd-radius-md);
        padding: 16px 24px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--rd-shadow-sm);
        border-left: 4px solid #1abc9c;
    }

    .rd-quick-summary-left {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .rd-quick-summary-item {
        display: flex;
        flex-direction: column;
    }

    .rd-quick-summary-item .label {
        font-size: 11px;
        color: var(--rd-text-muted);
        text-transform: uppercase;
    }

    .rd-quick-summary-item .value {
        font-size: 16px;
        font-weight: 600;
        color: var(--rd-text-dark);
    }

    .rd-quick-summary-item .value.primary { color: #1abc9c; }
    .rd-quick-summary-item .value.success { color: var(--rd-success); }

    /* Tab Content Animation */
    .rd-tab-pane {
        display: none;
        animation: rdFadeIn 0.3s ease;
    }

    .rd-tab-pane.active {
        display: block;
    }

    @keyframes rdFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Jemput RST Mode - Completed Badge */
    .rd-completed-badge {
        background: linear-gradient(135deg, #16a085, #1abc9c);
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Jemput Badge */
    .rd-jemput-badge {
        background: linear-gradient(135deg, #e67e22, #d35400);
        color: white;
        padding: 6px 14px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 8px;
    }

    /* RST Tab Nav Color Override */
    .rd-tabs-nav .rd-tab-btn.active {
        color: #1abc9c;
        border-bottom-color: #1abc9c;
    }
    </style>
</head>

<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #16a085;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-motorcycle"></i>
                <?php include "../lib/subtitel.php"; ?>
            </a>

            <div class="navbar-nav ml-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown">
                        <img src="../<?php echo $foto_user; ?>" alt="User"
                             style="width: 32px; height: 32px; border-radius: 50%; margin-right: 8px; object-fit: cover;">
                        <span><?php echo $_nama; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="change_pwd.php">
                            <i class="fas fa-cog"></i> Change Password
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="rd-page-wrapper">
        <!-- Page Header -->
        <div class="rd-page-header">
            <div>
                <h1>
                    <i class="fas fa-shipping-fast"></i>
                    Detail Service Jemput
                    <span class="rd-jemput-badge">
                        <i class="fas fa-car-side"></i> JEMPUT
                    </span>
                    <span class="rd-completed-badge">
                        <i class="fas fa-check"></i> SELESAI
                    </span>
                </h1>
                <div class="rd-breadcrumb" style="margin-top: 8px;">
                    <a href="index.php"><i class="fas fa-home"></i></a>
                    <span>/</span>
                    <a href="servis-reguler-jemput.php">Daftar Service Jemput</a>
                    <span>/</span>
                    <span>Detail Service (Selesai)</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="servis-print.php?snoserv=<?= urlencode($no_service) ?>" class="rd-btn success" target="_blank">
                    <i class="fas fa-print"></i> Print Invoice
                </a>
                <a href="servis-reguler-jemput.php" class="rd-btn" style="background: rgba(255,255,255,0.2); color: white; margin-left: 8px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- Quick Summary Bar -->
        <div class="rd-quick-summary">
            <div class="rd-quick-summary-left">
                <div class="rd-quick-summary-item">
                    <span class="label">No. Service</span>
                    <span class="value primary"><?= htmlspecialchars($no_service) ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">Pelanggan</span>
                    <span class="value"><?= htmlspecialchars($namapelanggan) ?: '-' ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">No. Polisi</span>
                    <span class="value"><?= htmlspecialchars($no_polisi) ?: '-' ?></span>
                </div>
                <div class="rd-quick-summary-item">
                    <span class="label">Status</span>
                    <span class="value success">
                        <i class="fas fa-check-circle"></i> SELESAI & DIBAYAR
                    </span>
                </div>
            </div>
            <div>
                <div class="rd-quick-summary-item" style="align-items: flex-end;">
                    <span class="label">Total Bayar</span>
                    <span class="value primary" style="font-size: 20px;">
                        Rp <?= number_format($net, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="rd-tabs-nav">
            <button class="rd-tab-btn <?= $active_tab == 'service-details' ? 'active' : '' ?>"
                    onclick="switchTab('service-details')">
                <i class="fas fa-info-circle"></i>
                <span>Detail Service</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'pickup-details' ? 'active' : '' ?>"
                    onclick="switchTab('pickup-details')">
                <i class="fas fa-shipping-fast"></i>
                <span>Detail Jemput</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'workorder-details' ? 'active' : '' ?>"
                    onclick="switchTab('workorder-details')">
                <i class="fas fa-clipboard-list"></i>
                <span>Work Order</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'temuan-penawaran' ? 'active' : '' ?>"
                    onclick="switchTab('temuan-penawaran')">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Temuan & Penawaran</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'service-items' ? 'active' : '' ?>"
                    onclick="switchTab('service-items')">
                <i class="fas fa-box"></i>
                <span>Item Barang</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'service-jasa' ? 'active' : '' ?>"
                    onclick="switchTab('service-jasa')">
                <i class="fas fa-tools"></i>
                <span>Item Jasa</span>
            </button>
            <button class="rd-tab-btn <?= $active_tab == 'payment-summary' ? 'active' : '' ?>"
                    onclick="switchTab('payment-summary')">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Ringkasan Pembayaran</span>
            </button>
        </div>

        <!-- Tab Contents -->
        <div id="service-details" class="rd-tab-pane <?= $active_tab == 'service-details' ? 'active' : '' ?>">
            <?php include "_template/_servis_detail_tab.php"; ?>
        </div>

        <div id="pickup-details" class="rd-tab-pane <?= $active_tab == 'pickup-details' ? 'active' : '' ?>">
            <?php include "_template/tab-pickup-details-redesign.php"; ?>
        </div>

        <div id="workorder-details" class="rd-tab-pane <?= $active_tab == 'workorder-details' ? 'active' : '' ?>">
            <?php include "_template/_servis_workorder_tab.php"; ?>
        </div>

        <div id="temuan-penawaran" class="rd-tab-pane <?= $active_tab == 'temuan-penawaran' ? 'active' : '' ?>">
            <?php include "_template/tab-temuan-penawaran-redesign.php"; ?>
        </div>

        <div id="service-items" class="rd-tab-pane <?= $active_tab == 'service-items' ? 'active' : '' ?>">
            <?php include "_template/tab-item-barang-redesign.php"; ?>
        </div>

        <div id="service-jasa" class="rd-tab-pane <?= $active_tab == 'service-jasa' ? 'active' : '' ?>">
            <?php include "_template/tab-item-jasa-redesign.php"; ?>
        </div>

        <div id="payment-summary" class="rd-tab-pane <?= $active_tab == 'payment-summary' ? 'active' : '' ?>">
            <?php
            // For RST mode, payment summary should be read-only
            $readonly_mode = true;
            include "_template/_servis_footer_tabs_rst.php";
            ?>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery-ui.custom.min.js"></script>

    <script>
    // Tab Switching Logic
    function switchTab(tabId) {
        // Hide all tabs
        document.querySelectorAll('.rd-tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });

        // Remove active from all buttons
        document.querySelectorAll('.rd-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        document.getElementById(tabId).classList.add('active');

        // Activate corresponding button
        event.currentTarget.classList.add('active');

        // Update URL without reload
        const url = new URL(window.location);
        const tabParam = tabId.replace('service-', '').replace('details', 'details').replace('pickup-details', 'pickup').replace('workorder-details', 'workorder').replace('temuan-penawaran', 'temuan').replace('payment-summary', 'payment');
        url.searchParams.set('tab', tabParam);
        window.history.pushState({}, '', url);
    }

    // Initialize on page load
    $(document).ready(function() {
        console.log('Jemput RST Page - Read-only mode loaded');
    });
    </script>
</body>
</html>
