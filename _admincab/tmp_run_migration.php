<?php
include "../config/koneksi.php";

$sql_content = file_get_contents('sql/migration_diskon_per_item.sql');
$queries = explode(';', $sql_content);

foreach($queries as $query) {
    $query = trim($query);
    if(empty($query)) continue;
    
    // Remove comments
    $query = preg_replace('/^--.*$/m', '', $query);
    if(empty(trim($query))) continue;
    
    if(mysqli_query($koneksi, $query)) {
        echo "Success: " . substr($query, 0, 50) . "...\n";
    } else {
        // Ignore "Duplicate column name" error (1060)
        if(mysqli_errno($koneksi) == 1060) {
            echo "Skipped (Column exists): " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error: " . mysqli_error($koneksi) . " in query: " . substr($query, 0, 50) . "...\n";
        }
    }
}
echo "Migration completed.\n";
?>
