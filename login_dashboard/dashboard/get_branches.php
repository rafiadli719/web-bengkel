<?php
// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$host = 'localhost';
$username = 'fitmotor_LOGIN';
$password = 'Sayalupa12';
$database = 'fitmotor_maintance-beta';

// Establish connection to the database
$mysqli = new mysqli($host, $username, $password, $database);

// Check for connection errors
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the 'karyawan' (employee code) from the POST request
$kode_karyawan = $_POST['karyawan'] ?? '';

// Check if 'kode_karyawan' is provided
if (!empty($kode_karyawan)) {
    // Prepare the SQL query to fetch unique branch names for the selected employee
    $sql = "SELECT DISTINCT nama_cabang FROM masterkeys WHERE kode_karyawan = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $kode_karyawan);
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize the dropdown options
        $options = '<option value="">Pilih Cabang</option>';
        
        // Fetch and append each branch name as an option
        while ($row = $result->fetch_assoc()) {
            $options .= '<option value="' . htmlspecialchars($row['nama_cabang']) . '">' 
                        . htmlspecialchars($row['nama_cabang']) . '</option>';
        }
        
        // Output the options
        echo $options;

        // Close the statement
        $stmt->close();
    } else {
        // Handle query preparation error
        echo '<option value="">Query preparation failed: ' . htmlspecialchars($mysqli->error) . '</option>';
    }
} else {
    // Handle case where no 'kode_karyawan' is provided
    echo '<option value="">Karyawan tidak dipilih</option>';
}

// Close the database connection
$mysqli->close();
?>
