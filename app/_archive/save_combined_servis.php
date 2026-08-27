<?php
	session_start();
    $id_user=$_SESSION['_iduser'];		
    $kd_cabang=$_SESSION['_cabang'];        

    include "../config/koneksi.php";
    
    date_default_timezone_set('Asia/Jakarta');
    
    function ubahformatTgl($tanggal) {
        $pisah = explode('/',$tanggal);
        $urutan = array($pisah[2],$pisah[1],$pisah[0]);
        $satukan = implode('-',$urutan);
        return $satukan;
    }

    // Function to compress and resize image
    function compressImage($source, $destination, $quality = 70, $maxWidth = 800, $maxHeight = 600) {
        $info = getimagesize($source);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Calculate new dimensions
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
        } else {
            $newWidth = $width;
            $newHeight = $height;
        }

        // Create new image with new dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save compressed image
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($newImage, $destination, $quality);
                break;
            case 'image/png':
                $result = imagepng($newImage, $destination, 9);
                break;
            case 'image/gif':
                $result = imagegif($newImage, $destination);
                break;
        }

        imagedestroy($image);
        imagedestroy($newImage);

        return $result;
    }

    // Get form data
    $nopol = $_POST['txtnopol'];
    $noserv = $_POST['txtnosrv'];
    
    // Data Antar Jemput
    $tanggal_jemput = ubahformatTgl($_POST['tanggal_jemput']);
    $jam_jemput = $_POST['jam_jemput'];
    $keterangan_jemput = $_POST['keterangan_jemput'];
    
    // Data Servis
    $km_sekarang = $_POST['km_sekarang'] ? $_POST['km_sekarang'] : 0;
    $km_berikut = $_POST['km_berikut'] ? $_POST['km_berikut'] : 0;
    
    // Keluhan array
    $keluhan_array = array_filter($_POST['keluhan']); // Remove empty entries
    
    // Service arrays
    $service_codes = $_POST['service_code'];
    $service_names = $_POST['service_name'];
    $service_times = $_POST['service_time'];
    $service_prices = $_POST['service_price'];
    $service_discounts = $_POST['service_discount'];
    $service_totals = $_POST['service_total'];
    
    // Barang arrays
    $barang_codes = $_POST['barang_code'];
    $barang_names = $_POST['barang_name'];
    $barang_qtys = $_POST['barang_qty'];
    $barang_prices = $_POST['barang_price'];
    $barang_discounts = $_POST['barang_discount'];
    $barang_totals = $_POST['barang_total'];

    // Handle file upload
    $foto_motor = '';
    if (isset($_FILES['foto_motor']) && $_FILES['foto_motor']['error'] == 0) {
        $uploadDir = '../uploads/motor_photos/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = $noserv . '_' . time() . '_' . basename($_FILES['foto_motor']['name']);
        $targetFile = $uploadDir . $fileName;
        
        // Check file type
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($imageFileType, $allowedTypes)) {
            // Check file size (2MB max)
            if ($_FILES['foto_motor']['size'] <= 2097152) {
                // Compress and save image
                if (compressImage($_FILES['foto_motor']['tmp_name'], $targetFile, 70, 800, 600)) {
                    $foto_motor = 'uploads/motor_photos/' . $fileName;
                }
            }
        }
    }

    // Start transaction
    mysqli_autocommit($koneksi, FALSE);

    try {
        // 1. Insert into tblservice (main service record)
        $check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'foto_motor'");
        $column_exists = mysqli_num_rows($check_column) > 0;

        if ($column_exists && !empty($foto_motor)) {
            $query_service = "INSERT INTO tblservice 
                              (no_service, tanggal, jam, 
                              no_pelanggan, no_polisi, 
                              kd_cabang, id_user, status_jemput, keterangan, foto_motor, 
                              km_sekarang, km_berikut) 
                              VALUES 
                              ('$noserv','$tanggal_jemput','$jam_jemput',
                              '$nopol','$nopol',
                              '$kd_cabang','$id_user','1','$keterangan_jemput','$foto_motor',
                              '$km_sekarang','$km_berikut')";
        } else {
            $query_service = "INSERT INTO tblservice 
                              (no_service, tanggal, jam, 
                              no_pelanggan, no_polisi, 
                              kd_cabang, id_user, status_jemput, keterangan,
                              km_sekarang, km_berikut) 
                              VALUES 
                              ('$noserv','$tanggal_jemput','$jam_jemput',
                              '$nopol','$nopol',
                              '$kd_cabang','$id_user','1','$keterangan_jemput',
                              '$km_sekarang','$km_berikut')";
        }

        if (!mysqli_query($koneksi, $query_service)) {
            throw new Exception("Error inserting service: " . mysqli_error($koneksi));
        }

        // 2. Insert keluhan
        if (!empty($keluhan_array)) {
            foreach ($keluhan_array as $keluhan) {
                if (!empty(trim($keluhan))) {
                    $keluhan = mysqli_real_escape_string($koneksi, $keluhan);
                    $query_keluhan = "INSERT INTO tbservis_keluhan (no_service, keluhan) VALUES ('$noserv', '$keluhan')";
                    if (!mysqli_query($koneksi, $query_keluhan)) {
                        throw new Exception("Error inserting keluhan: " . mysqli_error($koneksi));
                    }
                }
            }
        }

        // 3. Insert service items
        for ($i = 0; $i < count($service_codes); $i++) {
            if (!empty($service_codes[$i]) || !empty($service_names[$i])) {
                $service_code = mysqli_real_escape_string($koneksi, $service_codes[$i]);
                $service_name = mysqli_real_escape_string($koneksi, $service_names[$i]);
                $service_time = (int)$service_times[$i];
                $service_price = (float)$service_prices[$i];
                $service_discount = mysqli_real_escape_string($koneksi, $service_discounts[$i]);
                $service_total = (float)$service_totals[$i];

                $query_service_item = "INSERT INTO tbservis_service 
                                       (no_service, kode_service, nama_service, waktu, harga, diskon, total) 
                                       VALUES 
                                       ('$noserv', '$service_code', '$service_name', '$service_time', '$service_price', '$service_discount', '$service_total')";
                
                if (!mysqli_query($koneksi, $query_service_item)) {
                    throw new Exception("Error inserting service item: " . mysqli_error($koneksi));
                }
            }
        }

        // 4. Insert barang items
        for ($i = 0; $i < count($barang_codes); $i++) {
            if (!empty($barang_codes[$i]) || !empty($barang_names[$i])) {
                $barang_code = mysqli_real_escape_string($koneksi, $barang_codes[$i]);
                $barang_name = mysqli_real_escape_string($koneksi, $barang_names[$i]);
                $barang_qty = (int)$barang_qtys[$i];
                $barang_price = (float)$barang_prices[$i];
                $barang_discount = mysqli_real_escape_string($koneksi, $barang_discounts[$i]);
                $barang_total = (float)$barang_totals[$i];

                $query_barang_item = "INSERT INTO tbservis_barang 
                                      (no_service, kode_barang, nama_barang, jumlah, harga, diskon, total) 
                                      VALUES 
                                      ('$noserv', '$barang_code', '$barang_name', '$barang_qty', '$barang_price', '$barang_discount', '$barang_total')";
                
                if (!mysqli_query($koneksi, $query_barang_item)) {
                    throw new Exception("Error inserting barang item: " . mysqli_error($koneksi));
                }
            }
        }

        // Commit transaction
        mysqli_commit($koneksi);
        
        echo "<script>
                alert('Data antar jemput dan servis berhasil disimpan!\\nNo. Servis: $noserv');
                window.location='servis-input-reguler-jemput.php?snopol=$nopol';
              </script>";

    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($koneksi);
        
        echo "<script>
                alert('Gagal menyimpan data: " . $e->getMessage() . "'); 
                history.back();
              </script>";
    }

    // Restore autocommit
    mysqli_autocommit($koneksi, TRUE);
?>