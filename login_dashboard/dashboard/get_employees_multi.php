<?php
// Multi-Database Employee Retrieval with Fallback
error_reporting(E_ALL);
ini_set('display_errors', 1);

// First try multi-database approach
try {
    include 'multi_database_connection.php';
    $multiDB = new MultiDatabaseConnection();
    $use_multi_db = true;
} catch (Exception $e) {
    error_log("Multi-database connection failed, falling back to single database: " . $e->getMessage());
    $use_multi_db = false;
    
    // Fallback to original single database connection
    $host = "localhost";
    $username = "fitmotor_LOGIN";
    $password = "Sayalupa12";
    $database = "fitmotor_maintance-beta";
    $con = new mysqli($host, $username, $password, $database);
    
    if ($con->connect_error) {
        echo '<option value="">Error: Connection failed - ' . $con->connect_error . '</option>';
        exit();
    }
}

// Check if 'cabang' is provided via POST to load employees by branch
if (isset($_POST['cabang'])) {
    $cabang = $_POST['cabang'];
    
    if ($use_multi_db) {
        // Use multi-database approach
        try {
            $employees = $multiDB->getCombinedEmployees($cabang);
            
            if (!empty($employees)) {
                foreach ($employees as $employee) {
                    $source_db = isset($employee['source']) ? ucfirst($employee['source']) . ' DB' : 'Unknown';
                    echo '<option value="' . htmlspecialchars($employee['kode_karyawan']) . '" data-source="' . $source_db . '">' 
                         . htmlspecialchars($employee['kode_karyawan'] . ' - ' . $employee['nama_karyawan'] . ' - ' . $employee['role']) . ' (' . $source_db . ')'
                         . '</option>';
                }
            } else {
                echo '<option value="">Karyawan tidak ditemukan di kedua database</option>';
            }
            $multiDB->closeConnections();
        } catch (Exception $e) {
            error_log("Error in multi-database query: " . $e->getMessage());
            echo '<option value="">Error: ' . $e->getMessage() . '</option>';
        }
    } else {
        // Fallback to single database
        $cabang_escaped = strtolower($con->real_escape_string($cabang));
        $sql = "SELECT kode_karyawan, nama_karyawan, role FROM users WHERE LOWER(nama_cabang) = ?";
        
        if ($stmt = $con->prepare($sql)) {
            $stmt->bind_param("s", $cabang_escaped);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($row['kode_karyawan']) . '">' 
                         . htmlspecialchars($row['kode_karyawan'] . ' - ' . $row['nama_karyawan'] . ' - ' . $row['role']) . ' (Single DB)'
                         . '</option>';
                }
            } else {
                echo '<option value="">Karyawan tidak ditemukan</option>';
            }
            $stmt->close();
        } else {
            echo '<option value="">Query gagal: ' . $con->error . '</option>';
        }
        $con->close();
    }
}

// Check if 'kode_karyawan' is provided to load the role for a specific employee
if (isset($_POST['kode_karyawan'])) {
    $kode_karyawan = $_POST['kode_karyawan'];
    
    // Search in Kasir database first
    try {
        $kasir_conn = $multiDB->getKasirConnection();
        $sql_kasir = "SELECT role FROM users WHERE kode_karyawan = ?";
        $stmt_kasir = $kasir_conn->prepare($sql_kasir);
        $stmt_kasir->execute([$kode_karyawan]);
        $kasir_result = $stmt_kasir->fetch(PDO::FETCH_ASSOC);
        
        if ($kasir_result) {
            echo '<option value="' . htmlspecialchars($kasir_result['role']) . '">' . htmlspecialchars($kasir_result['role']) . ' (Kasir DB)</option>';
        } else {
            // Search in Absensi database
            try {
                $absensi_conn = $multiDB->getAbsensiConnection();
                $sql_absensi = "SELECT position as role FROM employees WHERE employee_id = ?";
                $stmt_absensi = $absensi_conn->prepare($sql_absensi);
                $stmt_absensi->execute([$kode_karyawan]);
                $absensi_result = $stmt_absensi->fetch(PDO::FETCH_ASSOC);
                
                if ($absensi_result) {
                    echo '<option value="' . htmlspecialchars($absensi_result['role']) . '">' . htmlspecialchars($absensi_result['role']) . ' (Absensi DB)</option>';
                } else {
                    echo '<option value="">Role tidak ditemukan</option>';
                }
            } catch (PDOException $e) {
                error_log("Error querying absensi database: " . $e->getMessage());
                echo '<option value="">Error querying absensi database</option>';
            }
        }
    } catch (PDOException $e) {
        error_log("Error querying kasir database: " . $e->getMessage());
        echo '<option value="">Error querying database</option>';
    }
}

// Get all branches from both databases
if (isset($_POST['get_branches'])) {
    $branches = $multiDB->getAllBranches();
    
    if (!empty($branches)) {
        foreach ($branches as $branch) {
            echo '<option value="' . htmlspecialchars($branch) . '">' . htmlspecialchars($branch) . '</option>';
        }
    } else {
        echo '<option value="">Cabang tidak ditemukan</option>';
    }
}

// Close connections
$multiDB->closeConnections();
?>