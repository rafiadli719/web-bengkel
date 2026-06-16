<?php
include "../config/koneksi.php";

// Get form data
$username = mysqli_real_escape_string($koneksi, $_POST['username']);
$password = mysqli_real_escape_string($koneksi, $_POST['password']);
$nama_staff = mysqli_real_escape_string($koneksi, $_POST['nama_staff']);
$email = mysqli_real_escape_string($koneksi, $_POST['email']);
$telepon = mysqli_real_escape_string($koneksi, $_POST['telepon']);
$alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
$shift_kerja = mysqli_real_escape_string($koneksi, $_POST['shift_kerja']);
$spesialisasi = mysqli_real_escape_string($koneksi, $_POST['spesialisasi']);
$status_staff = mysqli_real_escape_string($koneksi, $_POST['status_staff']);

// Check if username already exists
$check_user = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE nama_user = '$username'");
if(mysqli_num_rows($check_user) > 0) {
    echo "<script>
        alert('Username sudah digunakan! Gunakan username yang berbeda.');
        window.history.back();
    </script>";
    exit;
}

// Handle photo upload
$foto_path = 'file_upload/avatar.png'; // default
if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $upload_dir = '../file_upload/staff/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

    if(in_array($file_ext, $allowed_ext) && $_FILES['foto']['size'] <= 2097152) { // 2MB max
        $new_filename = 'cs_' . time() . '.' . $file_ext;
        $target_path = $upload_dir . $new_filename;

        if(move_uploaded_file($_FILES['foto']['tmp_name'], $target_path)) {
            $foto_path = 'file_upload/staff/' . $new_filename;
        }
    }
}

// Start transaction
mysqli_begin_transaction($koneksi);

try {
    // 1. Insert into tbuser (level CS = 2)
    $user_query = "INSERT INTO tbuser (nama_user, password, foto_user, user_akses, status_row)
                   VALUES ('$username', '$password', '$foto_path', 2, '0')";

    if(!mysqli_query($koneksi, $user_query)) {
        throw new Exception("Error creating user: " . mysqli_error($koneksi));
    }

    // Get the inserted user ID
    $user_id = mysqli_insert_id($koneksi);

    // 2. Insert into tb_staff_cs
    $staff_query = "INSERT INTO tb_staff_cs (user_id, nama_staff, email, telepon, alamat, foto, shift_kerja, spesialisasi, status_staff)
                    VALUES ('$user_id', '$nama_staff', '$email', '$telepon', '$alamat', '$foto_path', '$shift_kerja', '$spesialisasi', '$status_staff')";

    if(!mysqli_query($koneksi, $staff_query)) {
        throw new Exception("Error creating staff data: " . mysqli_error($koneksi));
    }

    // Commit transaction
    mysqli_commit($koneksi);

    echo "<script>
        alert('Data Staff Customer Service berhasil disimpan!');
        window.location='staff_cs.php';
    </script>";

} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($koneksi);

    echo "<script>
        alert('Error: " . $e->getMessage() . "');
        window.history.back();
    </script>";
}
?>