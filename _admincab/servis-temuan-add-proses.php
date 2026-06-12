<?php
/**
 * Backend Process: Input Temuan & Create Penawaran Part
 * Dipanggil dari form di _servis_input_temuan.php
 */

session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";

// Get user info
$cari_user = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($cari_user);
$nama_user = $user_data['nama_user'];

// =========================================
// COLLECT FORM DATA
// =========================================
$no_service = isset($_POST['txtnosrv']) ? mysqli_real_escape_string($koneksi, $_POST['txtnosrv']) : '';
$keluhan_id = isset($_POST['keluhan_id']) ? intval($_POST['keluhan_id']) : 0;
$kode_temuan = isset($_POST['kode_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['kode_temuan']) : '';
$temuan_custom = isset($_POST['temuan_custom']) ? mysqli_real_escape_string($koneksi, $_POST['temuan_custom']) : '';
$deskripsi_temuan = isset($_POST['deskripsi_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['deskripsi_temuan']) : '';
$tingkat_urgensi = isset($_POST['tingkat_urgensi']) ? mysqli_real_escape_string($koneksi, $_POST['tingkat_urgensi']) : 'sedang';
$jenis_perbaikan = isset($_POST['jenis_perbaikan']) ? mysqli_real_escape_string($koneksi, $_POST['jenis_perbaikan']) : 'setting';
$estimasi_biaya = isset($_POST['estimasi_biaya']) ? floatval($_POST['estimasi_biaya']) : 0;
$mekanik_id = isset($_POST['mekanik_id']) ? mysqli_real_escape_string($koneksi, $_POST['mekanik_id']) : '';

// Get mekanik name
$mekanik_name = '';
if($mekanik_id != '') {
    $query_mekanik = mysqli_query($koneksi, "SELECT nama FROM tblmekanik WHERE nomekanik='$mekanik_id'");
    if($query_mekanik && mysqli_num_rows($query_mekanik) > 0) {
        $data_mekanik = mysqli_fetch_array($query_mekanik);
        $mekanik_name = $data_mekanik['nama'];
    }
}

// Validate required fields
if(empty($no_service) || $keluhan_id == 0) {
    echo "<script>
            alert('Data tidak lengkap! No service dan keluhan harus diisi.');
            history.back();
          </script>";
    exit;
}

if(empty($kode_temuan) && empty($temuan_custom)) {
    echo "<script>
            alert('Temuan harus diisi! Pilih dari master atau ketik manual.');
            history.back();
          </script>";
    exit;
}

// =========================================
// HANDLE FILE UPLOAD (Foto Temuan)
// =========================================
$foto_temuan = '';
$upload_dir = '../file_upload/temuan/';

// Create directory if not exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if(isset($_FILES['foto_temuan']) && $_FILES['foto_temuan']['error'] == 0) {
    $file_name = $_FILES['foto_temuan']['name'];
    $file_tmp = $_FILES['foto_temuan']['tmp_name'];
    $file_size = $_FILES['foto_temuan']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Allowed extensions
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

    if(in_array($file_ext, $allowed_ext)) {
        if($file_size <= 2097152) { // Max 2MB
            // Generate unique filename
            $new_file_name = 'temuan_' . $no_service . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if(move_uploaded_file($file_tmp, $upload_path)) {
                $foto_temuan = 'file_upload/temuan/' . $new_file_name;
            }
        } else {
            echo "<script>
                    alert('Ukuran file foto terlalu besar! Maksimal 2MB.');
                    history.back();
                  </script>";
            exit;
        }
    } else {
        echo "<script>
                alert('Format file tidak didukung! Gunakan JPG, PNG, atau GIF.');
                history.back();
              </script>";
        exit;
    }
}

// =========================================
// INSERT TEMUAN
// =========================================
mysqli_begin_transaction($koneksi);

try {
    // Insert temuan
    $status_temuan = 'ditemukan'; // Status awal

    $sql_insert_temuan = "INSERT INTO tbservis_temuan (
                            no_service,
                            keluhan_id,
                            kode_temuan,
                            temuan_custom,
                            deskripsi_temuan,
                            jenis_perbaikan,
                            status_temuan,
                            tingkat_urgensi,
                            estimasi_biaya,
                            foto_temuan,
                            mekanik_id,
                            mekanik_name,
                            created_by,
                            created_at
                          ) VALUES (
                            '$no_service',
                            '$keluhan_id',
                            " . ($kode_temuan ? "'$kode_temuan'" : "NULL") . ",
                            " . ($temuan_custom ? "'$temuan_custom'" : "NULL") . ",
                            '$deskripsi_temuan',
                            '$jenis_perbaikan',
                            '$status_temuan',
                            '$tingkat_urgensi',
                            '$estimasi_biaya',
                            " . ($foto_temuan ? "'$foto_temuan'" : "NULL") . ",
                            " . ($mekanik_id ? "'$mekanik_id'" : "NULL") . ",
                            " . ($mekanik_name ? "'$mekanik_name'" : "NULL") . ",
                            '$id_user',
                            NOW()
                          )";

    if(!mysqli_query($koneksi, $sql_insert_temuan)) {
        throw new Exception('Error insert temuan: ' . mysqli_error($koneksi));
    }

    $temuan_id = mysqli_insert_id($koneksi);

    // Log activity
    $activity_desc = "Temuan baru: " . ($kode_temuan ? $kode_temuan : $temuan_custom);
    mysqli_query($koneksi, "INSERT INTO tbservis_temuan_activity_log
                            (no_service, temuan_id, activity_type, description, user_id, user_name, created_at)
                            VALUES
                            ('$no_service', '$temuan_id', 'temuan_created', '$activity_desc', '$id_user', '$nama_user', NOW())");

    // =========================================
    // CREATE PENAWARAN PART (jika jenis_perbaikan = penggantian_part)
    // =========================================
    if($jenis_perbaikan == 'penggantian_part') {
        // Check if ada part yang dipilih
        $selected_parts = isset($_POST['selected_parts']) ? $_POST['selected_parts'] : array();

        if(count($selected_parts) > 0) {
            // Loop insert penawaran part
            foreach($selected_parts as $kode_barang) {
                $kode_barang = mysqli_real_escape_string($koneksi, $kode_barang);
                $qty = isset($_POST['qty_' . $kode_barang]) ? intval($_POST['qty_' . $kode_barang]) : 1;

                // Get data barang (FIXED: gunakan tblitem)
                $query_barang = "SELECT namaitem, hargajual, quantity FROM tblitem WHERE noitem='$kode_barang'";
                $result_barang = mysqli_query($koneksi, $query_barang);

                if($result_barang && mysqli_num_rows($result_barang) > 0) {
                    $barang = mysqli_fetch_array($result_barang);
                    $nama_barang = mysqli_real_escape_string($koneksi, $barang['namaitem']);
                    $harga_satuan = $barang['hargajual'];
                    $stok_tersedia = $barang['quantity']; // quantity = stok di tblitem
                    $total_harga = $harga_satuan * $qty;

                    // Estimasi ketersediaan
                    $estimasi_ketersediaan = 'ready_stock';
                    if($stok_tersedia <= 0) {
                        $estimasi_ketersediaan = 'indent_3_hari';
                    } elseif($stok_tersedia < $qty) {
                        $estimasi_ketersediaan = 'indent_1_hari';
                    }

                    // Check prioritas dari mapping (untuk tracking)
                    $suggestion_priority = 0;
                    $is_from_suggestion = 1;
                    if($kode_temuan) {
                        $query_mapping = "SELECT prioritas FROM tbmaster_temuan_barang_mapping
                                         WHERE kode_temuan='$kode_temuan' AND noitem='$kode_barang' AND status_aktif=1";
                        $result_mapping = mysqli_query($koneksi, $query_mapping);
                        if($result_mapping && mysqli_num_rows($result_mapping) > 0) {
                            $mapping = mysqli_fetch_array($result_mapping);
                            $suggestion_priority = $mapping['prioritas'];
                        }
                    }

                    // Insert penawaran part
                    $sql_insert_penawaran = "INSERT INTO tbservis_penawaran_part (
                                                no_service,
                                                temuan_id,
                                                kode_barang,
                                                nama_barang,
                                                quantity,
                                                harga_satuan,
                                                total_harga,
                                                status_penawaran,
                                                is_from_suggestion,
                                                suggestion_priority,
                                                stok_tersedia,
                                                estimasi_ketersediaan,
                                                tanggal_penawaran,
                                                user_penawaran
                                             ) VALUES (
                                                '$no_service',
                                                '$temuan_id',
                                                '$kode_barang',
                                                '$nama_barang',
                                                '$qty',
                                                '$harga_satuan',
                                                '$total_harga',
                                                'pending',
                                                '$is_from_suggestion',
                                                '$suggestion_priority',
                                                '$stok_tersedia',
                                                '$estimasi_ketersediaan',
                                                NOW(),
                                                '$id_user'
                                             )";

                    if(!mysqli_query($koneksi, $sql_insert_penawaran)) {
                        throw new Exception('Error insert penawaran part: ' . mysqli_error($koneksi));
                    }

                    $penawaran_id = mysqli_insert_id($koneksi);

                    // Log penawaran
                    $log_desc = "Part ditawarkan: $nama_barang (Qty: $qty)";
                    mysqli_query($koneksi, "INSERT INTO tbservis_penawaran_log
                                            (penawaran_id, no_service, action, alasan, user_action, created_at)
                                            VALUES
                                            ('$penawaran_id', '$no_service', 'ditawarkan', '$log_desc', '$id_user', NOW())");
                }
            }

            // Update status temuan menjadi 'ditawarkan'
            mysqli_query($koneksi, "UPDATE tbservis_temuan
                                   SET status_temuan = 'ditawarkan'
                                   WHERE id = '$temuan_id'");

            // Log activity
            mysqli_query($koneksi, "INSERT INTO tbservis_temuan_activity_log
                                    (no_service, temuan_id, activity_type, description, user_id, user_name, created_at)
                                    VALUES
                                    ('$no_service', '$temuan_id', 'penawaran_created',
                                     'Dibuat " . count($selected_parts) . " penawaran part', '$id_user', '$nama_user', NOW())");
        }
    }

    // Commit transaction
    mysqli_commit($koneksi);

    // Success redirect
    $redirect_url = $_SERVER['HTTP_REFERER'] ?? 'servis-carinopol.php';
    $penawaran_msg = '';
    if ($jenis_perbaikan == 'penggantian_part' && isset($selected_parts) && is_array($selected_parts) && count($selected_parts) > 0) {
        $penawaran_msg = "\\n\\nPenawaran part berhasil dibuat dan menunggu approval customer.";
    }
    echo "<script>\n            alert('Temuan berhasil disimpan!$penawaran_msg');\n            window.location='$redirect_url';\n          </script>";

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($koneksi);

    echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            history.back();
          </script>";
}
?>
