<?php
	// Local XAMPP Database Configuration for Testing
	// Suppress connection warnings
	error_reporting(E_ERROR | E_PARSE);
	ini_set('display_errors', 0);

	$envBootstrap = __DIR__ . '/db_env.php';
	if (is_file($envBootstrap)) { include $envBootstrap; }
	mysqli_report(MYSQLI_REPORT_OFF);
	$DB_HOST = getenv('DB_HOST') ?: 'localhost';
	$DB_USER = getenv('DB_USER') ?: 'fitmotor_LOGIN';
	$DB_PASS = getenv('DB_PASS') ?: 'Sayalupa12';
	$DB_NAME = getenv('DB_NAME') ?: 'fitmotor_dbbengkel';
	$koneksi = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
	if ($koneksi) {
		@mysqli_set_charset($koneksi, 'latin1');
	}
	if (mysqli_connect_errno()){
		// Don't output HTML errors - let calling script handle it
		// echo "Koneksi database gagal : " . mysqli_connect_error();
		// exit;
	}
?>
