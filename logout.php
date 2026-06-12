<?php
/**
 * Logout Handler
 * Menghapus semua session dan redirect ke login page
 */

session_start();

// Log logout activity
if (isset($_SESSION['_iduser'])) {
    $username = $_SESSION['_iduser'] ?? 'Unknown';
    error_log("User logout: $username from IP: {$_SERVER['REMOTE_ADDR']}");
}

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: index.php?logout=1");
exit;
?>
