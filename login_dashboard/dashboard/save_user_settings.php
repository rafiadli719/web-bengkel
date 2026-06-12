<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_STRING);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);
    $permissions = isset($_POST['permissions']) ? json_decode($_POST['permissions'], true) : [];

    // Koneksi database
    $pdo = new PDO('mysql:host=localhost;dbname=fitmotor_maintance-beta', 'fitmotor_LOGIN', 'Sayalupa12');

    // Jika role ada, simpan role
    if ($role) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE kode_karyawan = ?");
        $stmt->execute([$role, $employee_id]);
    }

    // Jika permissions ada, simpan izin
    if (!empty($permissions)) {
        foreach ($permissions as $permission) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_sidebar_settings WHERE kode_karyawan = ? AND sidebar_id = ?");
            $stmt->execute([$employee_id, $permission['id']]);
            $exists = $stmt->fetchColumn();

            if ($exists) {
                $stmt = $pdo->prepare("UPDATE user_sidebar_settings SET is_visible = ? WHERE kode_karyawan = ? AND sidebar_id = ?");
                $stmt->execute([$permission['is_visible'], $employee_id, $permission['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO user_sidebar_settings (kode_karyawan, sidebar_id, is_visible) VALUES (?, ?, ?)");
                $stmt->execute([$employee_id, $permission['id'], $permission['is_visible']]);
            }
        }
    }

    echo 'Pengaturan berhasil disimpan.';
}
?>
