<?php
// Database connection information
$host = "localhost";
$username = "fitmotor_LOGIN";
$password = "Sayalupa12";
$database = "fitmotor_maintance-beta";

// Connect to the database
$con = new mysqli($host, $username, $password, $database);

// Check the connection
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

// Check if 'cabang' is provided via POST to load employees by branch
if (isset($_POST['cabang'])) {
    $cabang = strtolower($con->real_escape_string($_POST['cabang']));

    // Query to get employees in the specified branch
    $sql = "SELECT kode_karyawan, nama_karyawan, role FROM users WHERE LOWER(nama_cabang) = ?";
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $cabang);
        $stmt->execute();
        $result = $stmt->get_result();

        // Return employee options
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($row['kode_karyawan']) . '">' 
                     . htmlspecialchars($row['kode_karyawan'] . ' - ' . $row['nama_karyawan'] . ' - ' . $row['role']) 
                     . '</option>';
            }
        } else {
            echo '<option value="">Karyawan tidak ditemukan</option>';
        }
        $stmt->close();
    } else {
        echo '<option value="">Query gagal: ' . $con->error . '</option>';
    }
}

// Check if 'kode_karyawan' is provided to load the role for a specific employee
if (isset($_POST['kode_karyawan'])) {
    $kode_karyawan = $con->real_escape_string($_POST['kode_karyawan']);

    // Query to get role by employee code
    $sql_role = "SELECT role FROM users WHERE kode_karyawan = ?";
    if ($stmt = $con->prepare($sql_role)) {
        $stmt->bind_param("s", $kode_karyawan);
        $stmt->execute();
        $result = $stmt->get_result();

        // Return employee role
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo '<option value="' . htmlspecialchars($row['role']) . '">' . htmlspecialchars($row['role']) . '</option>';
        } else {
            echo '<option value="">Role tidak ditemukan</option>';
        }
        $stmt->close();
    } else {
        echo '<option value="">Query gagal: ' . $con->error . '</option>';
    }
}

// Close the connection
$con->close();
?>
