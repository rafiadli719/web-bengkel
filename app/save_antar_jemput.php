<?php
ob_start();
date_default_timezone_set('Asia/Jakarta'); // Sinkronisasi waktu dengan WIB
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
require_once "../config/koneksi.php";

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$debug_messages = [];
function debug_log($message) {
    global $debug_messages;
    $debug_messages[] = date('[Y-m-d H:i:s] ') . $message;
    echo "<script>console.log('" . addslashes(date('[Y-m-d H:i:s] ') . $message) . "');</script>\n";
}

// Function to convert date format (dd/mm/yyyy to yyyy-mm-dd)
function ubahformatTgl($tanggal) {
    $pisah = explode('/', $tanggal);
    if (count($pisah) == 3) {
        $urutan = array($pisah[2], $pisah[1], $pisah[0]);
        $satukan = implode('-', $urutan);
        return $satukan;
    }
    // If already in yyyy-mm-dd format or invalid, return as is
    return $tanggal;
}

// Function to compress and resize image
function compressImage($source, $destination, $quality = 70, $maxWidth = 800, $maxHeight = 600) {
    $info = getimagesize($source);
    if (!$info) return false;

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

    if (!$image) return false;

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

// Check if this is called via POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debug_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo "<script>alert('Invalid request method!'); history.back();</script>";
    exit;
}

// Get form data with proper validation
$txttgljemput = isset($_POST['id-date-picker-1']) ? ubahformatTgl($_POST['id-date-picker-1']) : 
                (isset($_POST['txttanggal']) ? $_POST['txttanggal'] : date('Y-m-d'));
$nopol = mysqli_real_escape_string($koneksi, $_POST['txtnopol'] ?? '');
$noserv_antar = mysqli_real_escape_string($koneksi, $_POST['txtnosrv'] ?? '');
$txtjam = mysqli_real_escape_string($koneksi, $_POST['txtjam'] ?? date('H:i'));
$txtket = mysqli_real_escape_string($koneksi, $_POST['txtket'] ?? $_POST['txtketerangan'] ?? '');

// Validate required fields
if (empty($noserv_antar) || empty($nopol)) {
    debug_log("Missing required fields - noserv_antar: $noserv_antar, nopol: $nopol");
    echo "<script>alert('Data tidak lengkap! Pastikan nomor service dan nomor polisi terisi.'); history.back();</script>";
    exit;
}

debug_log("Processing form data - noserv_antar: $noserv_antar, nopol: $nopol, tanggal: $txttgljemput, jam: $txtjam");

// Handle file upload for foto_motor
$foto_motor = '';
if (isset($_FILES['foto_motor']) && $_FILES['foto_motor']['error'] == 0) {
    debug_log("File upload detected for foto_motor");
    $uploadDir = '../uploads/motor_photos/';

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        debug_log("Created upload directory: $uploadDir");
    }

    $fileName = $noserv_antar . '_motor_' . time() . '_' . basename($_FILES['foto_motor']['name']);
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
                debug_log("Image compressed and saved as: $foto_motor");
            } else {
                debug_log("Failed to compress image");
                echo "<script>alert('Gagal mengkompresi foto!'); history.back();</script>";
                exit;
            }
        } else {
            debug_log("File size exceeds 2MB: " . $_FILES['foto_motor']['size']);
            echo "<script>alert('Ukuran file terlalu besar! Maksimal 2MB.'); history.back();</script>";
            exit;
        }
    } else {
        debug_log("Unsupported file type: $imageFileType");
        echo "<script>alert('Format file tidak didukung! Gunakan JPG, JPEG, PNG, atau GIF.'); history.back();</script>";
        exit;
    }
}

// Handle file upload for foto_patokan
$foto_patokan = '';
if (isset($_FILES['foto_patokan']) && $_FILES['foto_patokan']['error'] == 0) {
    debug_log("File upload detected for foto_patokan");
    $uploadDir = '../uploads/foto_patokan/';

    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
        debug_log("Created upload directory: $uploadDir");
    }

    $fileName = $noserv_antar . '_patokan_' . time() . '_' . basename($_FILES['foto_patokan']['name']);
    $targetFile = $uploadDir . $fileName;

    // Check file type
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $allowedTypes = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($imageFileType, $allowedTypes)) {
        // Check file size (2MB max)
        if ($_FILES['foto_patokan']['size'] <= 2097152) {
            // Compress and save image
            if (compressImage($_FILES['foto_patokan']['tmp_name'], $targetFile, 70, 800, 600)) {
                $foto_patokan = 'uploads/foto_patokan/' . $fileName;
                debug_log("Patokan image compressed and saved as: $foto_patokan");
            } else {
                debug_log("Failed to compress patokan image");
                echo "<script>alert('Gagal mengkompresi foto patokan!'); history.back();</script>";
                exit;
            }
        } else {
            debug_log("Patokan file size exceeds 2MB: " . $_FILES['foto_patokan']['size']);
            echo "<script>alert('Ukuran file foto patokan terlalu besar! Maksimal 2MB.'); history.back();</script>";
            exit;
        }
    } else {
        debug_log("Unsupported patokan file type: $imageFileType");
        echo "<script>alert('Format file foto patokan tidak didukung! Gunakan JPG, JPEG, PNG, atau GIF.'); history.back();</script>";
        exit;
    }
}

// Check if service already exists
$check_service = "SELECT COUNT(*) as count FROM tblservice WHERE no_service = ?";
$stmt_check = mysqli_prepare($koneksi, $check_service);
mysqli_stmt_bind_param($stmt_check, "s", $noserv_antar);
mysqli_stmt_execute($stmt_check);
$result_check = mysqli_stmt_get_result($stmt_check);
$row_check = mysqli_fetch_assoc($result_check);
mysqli_stmt_close($stmt_check);

debug_log("Service exists check: " . $row_check['count']);

if ($row_check['count'] > 0) {
    // Update existing service
    $update_fields = [];
    $update_values = [];
    $update_types = "";
    
    $update_fields[] = "tanggal = ?";
    $update_values[] = $txttgljemput;
    $update_types .= "s";
    
    $update_fields[] = "jam = ?";
    $update_values[] = $txtjam;
    $update_types .= "s";
    
    $update_fields[] = "no_pelanggan = ?";
    $update_values[] = $nopol;
    $update_types .= "s";
    
    $update_fields[] = "no_polisi = ?";
    $update_values[] = $nopol;
    $update_types .= "s";
    
    $update_fields[] = "keterangan = ?";
    $update_values[] = $txtket;
    $update_types .= "s";
    
    $update_fields[] = "keterangan_jemput = ?";
    $update_values[] = $txtket;
    $update_types .= "s";
    
    if (!empty($foto_motor)) {
        $update_fields[] = "foto_motor = ?";
        $update_values[] = $foto_motor;
        $update_types .= "s";
    }
    
    if (!empty($foto_patokan)) {
        $update_fields[] = "foto_patokan = ?";
        $update_values[] = $foto_patokan;
        $update_types .= "s";
    }
    
    $update_values[] = $noserv_antar;
    $update_types .= "s";
    
    $query = "UPDATE tblservice SET " . implode(", ", $update_fields) . " WHERE no_service = ?";
    
    debug_log("Executing UPDATE SQL: $query");
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, $update_types, ...$update_values);
    
} else {
    // Insert new service
    $insert_fields = [
        'no_service', 'tanggal', 'jam', 'no_pelanggan', 'no_polisi', 
        'kd_cabang', 'id_user', 'status_jemput', 'keterangan', 'keterangan_jemput',
        'status', 'status_servis'
    ];
    $insert_values = [
        $noserv_antar, $txttgljemput, $txtjam, $nopol, $nopol,
        $kd_cabang, $id_user, '1', $txtket, $txtket,
        '1', 'datang'
    ];
    $insert_types = "ssssssssssss";
    
    if (!empty($foto_motor)) {
        $insert_fields[] = 'foto_motor';
        $insert_values[] = $foto_motor;
        $insert_types .= "s";
    }
    
    if (!empty($foto_patokan)) {
        $insert_fields[] = 'foto_patokan';
        $insert_values[] = $foto_patokan;
        $insert_types .= "s";
    }
    
    $placeholders = str_repeat('?,', count($insert_fields) - 1) . '?';
    $query = "INSERT INTO tblservice (" . implode(", ", $insert_fields) . ") VALUES ($placeholders)";
    
    debug_log("Executing INSERT SQL: $query");
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, $insert_types, ...$insert_values);
}

if (mysqli_stmt_execute($stmt)) {
    debug_log("Data successfully saved to tblservice");
    mysqli_stmt_close($stmt);
    
    // Success - redirect to servis-input-reguler-jemput-rst.php
    $redirect_url = "servis-input-reguler-jemput-rst.php?snoserv=" . urlencode($noserv_antar);
    
    if (headers_sent($file, $line)) {
        debug_log("Headers already sent in $file on line $line, using JavaScript redirect");
        echo "<script>
                alert('Data antar jemput berhasil disimpan!');
                window.location='$redirect_url';
              </script>";
    } else {
        debug_log("Redirecting to: $redirect_url");
        header("Location: $redirect_url");
        exit;
    }
} else {
    $error_message = mysqli_error($koneksi);
    debug_log("Failed to save data: $error_message");
    mysqli_stmt_close($stmt);
    echo "<script>
            alert('Gagal menyimpan data: $error_message');
            history.back();
          </script>";
}

ob_end_flush();
?>