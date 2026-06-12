<?php
// File: check_database_structure.php
// Diagnostic script untuk check tbuser and tb_master_posisi structure

session_start();
include "../config/koneksi.php";

echo "<h2>Database Structure Analysis</h2>";
echo "<hr>";

// Check tbuser structure
echo "<h3>1. tbuser Table Structure:</h3>";
$query = "DESCRIBE tbuser";
$result = mysqli_query($koneksi, $query);

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if kode_posisi exists
    $has_kode_posisi = false;
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['Field'] === 'kode_posisi') {
            $has_kode_posisi = true;
            break;
        }
    }
    
    echo "<p><strong>Has 'kode_posisi' column:</strong> " . ($has_kode_posisi ? "✅ YES" : "❌ NO") . "</p>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($koneksi) . "</p>";
}

echo "<hr>";

// Check tb_master_posisi structure
echo "<h3>2. tb_master_posisi Table Structure:</h3>";
$query2 = "DESCRIBE tb_master_posisi";
$result2 = mysqli_query($koneksi, $query2);

if ($result2) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result2)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($koneksi) . "</p>";
}

echo "<hr>";

// Check sample data from tb_master_posisi
echo "<h3>3. Sample Positions (tb_master_posisi):</h3>";
$query3 = "SELECT id, kode_posisi, nama_posisi, departemen, user_akses_level, is_active, 
           LEFT(permissions, 100) as permissions_preview 
           FROM tb_master_posisi 
           LIMIT 5";
$result3 = mysqli_query($koneksi, $query3);

if ($result3 && mysqli_num_rows($result3) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Kode</th><th>Nama</th><th>Departemen</th><th>Level</th><th>Status</th><th>Permissions (preview)</th></tr>";
    while ($row = mysqli_fetch_assoc($result3)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['kode_posisi']}</strong></td>";
        echo "<td>{$row['nama_posisi']}</td>";
        echo "<td>{$row['departemen']}</td>";
        echo "<td>{$row['user_akses_level']}</td>";
        echo "<td>{$row['is_active']}</td>";
        echo "<td><small>" . htmlspecialchars($row['permissions_preview']) . "...</small></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No position data found or error: " . mysqli_error($koneksi) . "</p>";
}

echo "<hr>";

// Check sample users
echo "<h3>4. Sample Users (tbuser):</h3>";
$user_fields_query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbuser'";
$fields_result = mysqli_query($koneksi, $user_fields_query);
$user_columns = [];
while ($field = mysqli_fetch_assoc($fields_result)) {
    $user_columns[] = $field['COLUMN_NAME'];
}

$has_kode_posisi_field = in_array('kode_posisi', $user_columns);

if ($has_kode_posisi_field) {
    $query4 = "SELECT id, nama_user, username_user, kode_posisi, user_ak FROM tbuser LIMIT 5";
} else {
    $query4 = "SELECT id, nama_user, username_user, user_ak FROM tbuser LIMIT 5";
}

$result4 = mysqli_query($koneksi, $query4);

if ($result4 && mysqli_num_rows($result4) > 0) {
    echo "<table border='1' cellpadding='5'>";
    $headers = "<tr><th>ID</th><th>Nama</th><th>Username</th>";
    if ($has_kode_posisi_field) {
        $headers .= "<th>Kode Posisi</th>";
    }
    $headers .= "<th>User AK</th></tr>";
    echo $headers;
    
    while ($row = mysqli_fetch_assoc($result4)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nama_user']}</td>";
        echo "<td>{$row['username_user']}</td>";
        if ($has_kode_posisi_field) {
            echo "<td><strong>" . ($row['kode_posisi'] ?? 'NULL') . "</strong></td>";
        }
        echo "<td>{$row['user_ak']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No user data found or error: " . mysqli_error($koneksi) . "</p>";
}

echo "<hr>";
echo "<h3>5. Recommendations:</h3>";
echo "<ul>";

if (!$has_kode_posisi_field) {
    echo "<li style='color:orange'><strong>⚠️ IMPORTANT:</strong> tbuser table does NOT have 'kode_posisi' column. 
          You need to add it with:<br>
          <code>ALTER TABLE tbuser ADD COLUMN kode_posisi VARCHAR(20) NULL AFTER user_ak;</code>
          </li>";
} else {
    echo "<li style='color:green'>✅ tbuser has 'kode_posisi' column - ready for RBAC!</li>";
}

echo "<li>Create RBAC menu renderer function</li>";
echo "<li>Update menu_dashboard.php to use RBAC filtering</li>";
echo "<li>Test with different positions</li>";
echo "</ul>";

echo "<hr>";
echo "<p><em>Analysis completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
