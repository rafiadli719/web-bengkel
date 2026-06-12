    <?php
    // Konfigurasi koneksi ke database
    $host = 'localhost';  // Ganti dengan host database Anda
    $dbname = 'fitmotor_maintance-beta';  // Ganti dengan nama database Anda
    $username = 'fitmotor_LOGIN';  // Ganti dengan username database Anda
    $password = 'Sayalupa12';  // Ganti dengan password database Anda

    // Membuat koneksi
    $conn = new mysqli($host, $username, $password, $dbname);

    // Cek koneksi
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    ?>
