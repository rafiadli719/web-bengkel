<?php
// Multi-Database Connection Configuration
class MultiDatabaseConnection {
    private $kasir_connection;
    private $absensi_connection;
    
    // Database configuration
    private $host = 'localhost';
    private $username = 'fitmotor_LOGIN';
    private $password = 'Sayalupa12';
    private $kasir_db = 'fitmotor_maintance-beta';
    private $absensi_db = 'fitmotor_prototype';
    
    public function __construct() {
        $this->connectKasir();
        $this->connectAbsensi();
    }
    
    // Connection to Kasir Database (maintace-beta)
    private function connectKasir() {
        try {
            $this->kasir_connection = new PDO(
                "mysql:host={$this->host};dbname={$this->kasir_db}", 
                $this->username, 
                $this->password
            );
            $this->kasir_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Koneksi ke database kasir gagal: " . $e->getMessage());
        }
    }
    
    // Connection to Absensi Database (prototype)
    private function connectAbsensi() {
        try {
            $this->absensi_connection = new PDO(
                "mysql:host={$this->host};dbname={$this->absensi_db}", 
                $this->username, 
                $this->password
            );
            $this->absensi_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("Koneksi ke database absensi gagal: " . $e->getMessage());
        }
    }
    
    // Get Kasir Database Connection
    public function getKasirConnection() {
        return $this->kasir_connection;
    }
    
    // Get Absensi Database Connection
    public function getAbsensiConnection() {
        return $this->absensi_connection;
    }
    
    // Get employees from Kasir database
    public function getKasirEmployees($cabang = null) {
        try {
            if ($cabang) {
                $sql = "SELECT kode_karyawan, nama_karyawan, role, nama_cabang FROM users WHERE LOWER(nama_cabang) = ? ORDER BY nama_karyawan";
                $stmt = $this->kasir_connection->prepare($sql);
                $stmt->execute([strtolower($cabang)]);
            } else {
                $sql = "SELECT kode_karyawan, nama_karyawan, role, nama_cabang FROM users ORDER BY nama_karyawan";
                $stmt = $this->kasir_connection->prepare($sql);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add source indicator for debugging
            foreach ($results as &$result) {
                $result['source'] = 'kasir';
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Error getting kasir employees: " . $e->getMessage());
            return [];
        }
    }
    
    // Get employees from Absensi database
    public function getAbsensiEmployees($cabang = null) {
        try {
            // Based on debug info, absensi database uses 'users' table
            $table_name = 'users';
            
            // Column mapping for absensi database
            $id_col = 'kode_karyawan';
            $name_col = 'nama_karyawan';
            $role_col = 'role';
            $branch_col = 'nama_cabang';
            
            // Build query based on cabang filter
            if ($cabang) {
                $sql = "SELECT $id_col as kode_karyawan, $name_col as nama_karyawan, $role_col as role, $branch_col as nama_cabang 
                        FROM $table_name 
                        WHERE LOWER($branch_col) = ? AND status = 1
                        ORDER BY $name_col";
                $stmt = $this->absensi_connection->prepare($sql);
                $stmt->execute([strtolower($cabang)]);
            } else {
                $sql = "SELECT $id_col as kode_karyawan, $name_col as nama_karyawan, $role_col as role, $branch_col as nama_cabang 
                        FROM $table_name 
                        WHERE status = 1
                        ORDER BY $name_col";
                $stmt = $this->absensi_connection->prepare($sql);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add source indicator for debugging
            foreach ($results as &$result) {
                $result['source'] = 'absensi';
            }
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Error getting absensi employees: " . $e->getMessage());
            return [];
        }
    }
    
    // Get combined employees from both databases
    public function getCombinedEmployees($cabang = null) {
        $kasir_employees = $this->getKasirEmployees($cabang);
        $absensi_employees = $this->getAbsensiEmployees($cabang);
        
        // Combine arrays and remove duplicates based on kode_karyawan
        $combined = array_merge($kasir_employees, $absensi_employees);
        
        // Remove duplicates based on kode_karyawan
        $unique_employees = [];
        $seen_codes = [];
        
        foreach ($combined as $employee) {
            if (!in_array($employee['kode_karyawan'], $seen_codes)) {
                $unique_employees[] = $employee;
                $seen_codes[] = $employee['kode_karyawan'];
            }
        }
        
        // Sort by nama_karyawan
        usort($unique_employees, function($a, $b) {
            return strcmp($a['nama_karyawan'], $b['nama_karyawan']);
        });
        
        return $unique_employees;
    }
    
    // Get all branches from both databases
    public function getAllBranches() {
        $kasir_branches = $this->getKasirBranches();
        $absensi_branches = $this->getAbsensiBranches();
        
        // Combine and remove duplicates
        $combined_branches = array_merge($kasir_branches, $absensi_branches);
        $unique_branches = array_unique($combined_branches);
        sort($unique_branches);
        
        return $unique_branches;
    }
    
    // Get branches from Kasir database
    private function getKasirBranches() {
        try {
            $sql = "SELECT DISTINCT nama_cabang FROM users WHERE nama_cabang IS NOT NULL AND nama_cabang != ''";
            $stmt = $this->kasir_connection->prepare($sql);
            $stmt->execute();
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama_cabang');
        } catch (PDOException $e) {
            error_log("Error getting kasir branches: " . $e->getMessage());
            return [];
        }
    }
    
    // Get branches from Absensi database
    private function getAbsensiBranches() {
        try {
            // Based on debug info, absensi database uses 'users' table with 'nama_cabang' column
            $sql = "SELECT DISTINCT nama_cabang FROM users WHERE nama_cabang IS NOT NULL AND nama_cabang != '' AND status = 1";
            $stmt = $this->absensi_connection->prepare($sql);
            $stmt->execute();
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama_cabang');
        } catch (PDOException $e) {
            error_log("Error getting absensi branches: " . $e->getMessage());
            return [];
        }
    }
    
    // Close connections
    public function closeConnections() {
        $this->kasir_connection = null;
        $this->absensi_connection = null;
    }
}
?>