<?php
include 'database_connection.php';

if (isset($_POST['kode_karyawan'])) {
    $kode_karyawan = $con_fitmotor_pak_novi->real_escape_string($_POST['kode_karyawan']);

    $sql_role = "SELECT role FROM users WHERE kode_karyawan = ?";
    if ($stmt = $con_fitmotor_pak_novi->prepare($sql_role)) {
        $stmt->bind_param("s", $kode_karyawan);
        $stmt->execute();
        $result = $stmt->get_result();

        // Mengembalikan role karyawan
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo '<option value="' . htmlspecialchars($row['role']) . '">' . htmlspecialchars($row['role']) . '</option>';
        } else {
            echo '<option value="">Role tidak ditemukan</option>';
        }
        $stmt->close();
    } else {
        echo '<option value="">Query gagal: ' . $con_fitmotor_pak_novi->error . '</option>';
    }
}
?>
