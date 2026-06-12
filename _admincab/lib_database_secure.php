<?php
/**
 * Secure Database Helper Functions
 * Menggunakan Prepared Statements untuk mencegah SQL Injection
 *
 * @author Claude Code Security Patch
 * @version 1.0
 * @date 2025-12-12
 */

class DatabaseSecure {
    private $koneksi;

    public function __construct($koneksi) {
        $this->koneksi = $koneksi;
    }

    /**
     * Execute prepared statement SELECT query
     * @param string $query Query dengan placeholder (?)
     * @param string $types Tipe data (i=integer, s=string, d=double)
     * @param array $params Array parameter
     * @return mysqli_result|false
     */
    public function select($query, $types = '', $params = []) {
        if (empty($params)) {
            // Query tanpa parameter (static query)
            return mysqli_query($this->koneksi, $query);
        }

        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->koneksi->error);
            return false;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }

        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }

    /**
     * Execute prepared statement INSERT/UPDATE/DELETE query
     * @param string $query Query dengan placeholder (?)
     * @param string $types Tipe data (i=integer, s=string, d=double)
     * @param array $params Array parameter
     * @return bool
     */
    public function execute($query, $types = '', $params = []) {
        if (empty($params)) {
            // Query tanpa parameter (static query)
            return mysqli_query($this->koneksi, $query);
        }

        $stmt = $this->koneksi->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->koneksi->error);
            return false;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $result = $stmt->execute();

        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return $result;
    }

    /**
     * Get single row from prepared statement
     * @param string $query Query dengan placeholder (?)
     * @param string $types Tipe data
     * @param array $params Array parameter
     * @return array|null
     */
    public function fetchRow($query, $types = '', $params = []) {
        $result = $this->select($query, $types, $params);
        if (!$result) return null;

        $row = $result->fetch_assoc();
        return $row ?: null;
    }

    /**
     * Get all rows from prepared statement
     * @param string $query Query dengan placeholder (?)
     * @param string $types Tipe data
     * @param array $params Array parameter
     * @return array
     */
    public function fetchAll($query, $types = '', $params = []) {
        $result = $this->select($query, $types, $params);
        if (!$result) return [];

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get last inserted ID
     * @return int
     */
    public function lastInsertId() {
        return $this->koneksi->insert_id;
    }

    /**
     * Get affected rows
     * @return int
     */
    public function affectedRows() {
        return $this->koneksi->affected_rows;
    }

    /**
     * Start transaction
     */
    public function beginTransaction() {
        $this->koneksi->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->koneksi->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->koneksi->rollback();
    }
}

/**
 * Input Validation Helper Functions
 */
class InputValidator {

    /**
     * Validate and sanitize integer
     * @param mixed $value
     * @param int $default
     * @return int
     */
    public static function sanitizeInt($value, $default = 0) {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
    }

    /**
     * Validate and sanitize float/double
     * @param mixed $value
     * @param float $default
     * @return float
     */
    public static function sanitizeFloat($value, $default = 0.0) {
        return filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['default' => $default]]);
    }

    /**
     * Validate and sanitize string
     * @param mixed $value
     * @param int $maxLength
     * @return string
     */
    public static function sanitizeString($value, $maxLength = 255) {
        $value = trim(strip_tags($value));
        return substr($value, 0, $maxLength);
    }

    /**
     * Validate service number format
     * @param string $no_service
     * @return bool
     */
    public static function isValidServiceNo($no_service) {
        // Format: JMP-YYYYMMDD-XXXX atau SV-XXXXXXX
        return preg_match('/^(JMP|SV|SRV)-[0-9]{8}-[0-9]{4}$|^SV[0-9]{11}$/', $no_service);
    }

    /**
     * Validate nopol format
     * @param string $nopol
     * @return bool
     */
    public static function isValidNopol($nopol) {
        // Format Indonesia: A 1234 BC atau AA 1234 BCD
        return preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,3}$/i', $nopol);
    }

    /**
     * Validate phone number
     * @param string $phone
     * @return bool
     */
    public static function isValidPhone($phone) {
        // Indonesian phone: 08xx or 628xx or +628xx
        return preg_match('/^(\+62|62|0)[0-9]{9,13}$/', $phone);
    }

    /**
     * Validate email
     * @param string $email
     * @return bool
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate date format (d/m/Y)
     * @param string $date
     * @return bool
     */
    public static function isValidDate($date) {
        $d = DateTime::createFromFormat('d/m/Y', $date);
        return $d && $d->format('d/m/Y') === $date;
    }

    /**
     * Convert d/m/Y to Y-m-d
     * @param string $date
     * @return string|false
     */
    public static function convertDateToMySQL($date) {
        $d = DateTime::createFromFormat('d/m/Y', $date);
        return $d ? $d->format('Y-m-d') : false;
    }

    /**
     * Sanitize enum value
     * @param string $value
     * @param array $allowedValues
     * @param string $default
     * @return string
     */
    public static function sanitizeEnum($value, $allowedValues, $default = '') {
        return in_array($value, $allowedValues, true) ? $value : $default;
    }
}

/**
 * CSRF Token Protection
 */
class CSRFProtection {

    /**
     * Generate CSRF token
     * @return string
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     * @param string $token
     * @return bool
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get HTML hidden input for CSRF token
     * @return string
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate POST request CSRF token
     * @return bool
     */
    public static function validatePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return true; // Not a POST request
        }

        $token = $_POST['csrf_token'] ?? '';
        return self::validateToken($token);
    }
}

/**
 * Session Security Helper
 */
class SessionSecurity {

    /**
     * Validate and sanitize session user ID
     * @return int|false
     */
    public static function getUserId() {
        if (!isset($_SESSION['_iduser'])) {
            return false;
        }

        return InputValidator::sanitizeInt($_SESSION['_iduser']);
    }

    /**
     * Validate and sanitize session cabang
     * @return string|false
     */
    public static function getCabang() {
        if (!isset($_SESSION['_cabang'])) {
            return false;
        }

        return InputValidator::sanitizeString($_SESSION['_cabang'], 10);
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn() {
        return self::getUserId() !== false;
    }

    /**
     * Redirect if not logged in
     * @param string $redirectUrl
     */
    public static function requireLogin($redirectUrl = '../index.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
}

?>
