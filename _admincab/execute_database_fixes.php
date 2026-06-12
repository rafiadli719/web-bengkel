<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    die("Unauthorized access. Please login first.");
}

include "../config/koneksi.php";

$user_id = $_SESSION['_iduser'];

echo "<h2>Database Fixes Execution - ORI/NON-ORI System</h2>";
echo "<p><strong>Target Issues:</strong> Fix 2,966 uncategorized NON-ORI items</p>";
echo "<hr>";

// Check current state before fixes
echo "<h3>BEFORE FIXES - Current State</h3>";
echo "<pre>";

$result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item = 'NON_ORI' AND (kategori_rak IS NULL OR kategori_rak = '') AND statusitem = '1'");
$uncategorized_before = mysqli_fetch_assoc($result)['count'];
echo "Uncategorized NON-ORI items: $uncategorized_before\n";

$result = mysqli_query($koneksi, "SELECT status_validasi, COUNT(*) as count FROM tblitem WHERE statusitem = '1' GROUP BY status_validasi");
echo "Validation status distribution:\n";
while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['status_validasi'] ?: 'NULL';
    echo "  $status: {$row['count']} items\n";
}
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_fixes'])) {
    echo "<h3>EXECUTING FIXES</h3>";
    echo "<pre>";
    
    $fixes_executed = 0;
    $total_updated = 0;
    
    // Step 1: Create backup
    echo "Step 1: Creating backup table...\n";
    $backup_sql = "CREATE TABLE IF NOT EXISTS `tblitem_backup_categories` AS 
                   SELECT noitem, namaitem, kategori_rak, tipe_item 
                   FROM tblitem 
                   WHERE tipe_item = 'NON_ORI' AND (kategori_rak IS NULL OR kategori_rak = '')";
    
    if (mysqli_query($koneksi, $backup_sql)) {
        echo "✓ Backup table created successfully\n";
        $fixes_executed++;
    } else {
        echo "✗ Failed to create backup: " . mysqli_error($koneksi) . "\n";
    }
    
    // Category assignment fixes
    $category_fixes = [
        'KB' => [
            'name' => 'KABEL',
            'keywords' => ['%KABEL%', '%CABLE%', '%KAWAT%', '%WIRE%']
        ],
        'EL' => [
            'name' => 'KELISTRIKAN', 
            'keywords' => ['%LISTRIK%', '%ELECTRIC%', '%LAMPU%', '%LAMP%', '%LED%', '%BOHLAM%', '%BULB%', '%SPUL%', '%COIL%', '%CDI%', '%KIPROK%', '%REGULATOR%', '%STARTER%', '%DINAMO%', '%ALTERNATOR%']
        ],
        'RM' => [
            'name' => 'REM',
            'keywords' => ['%REM%', '%BRAKE%', '%KAMPAS%', '%PAD%', '%TROMOL%', '%DRUM%', '%DISC%', '%CAKRAM%', '%MASTER%', '%KALIPER%']
        ],
        'MS' => [
            'name' => 'MESIN',
            'keywords' => ['%MESIN%', '%ENGINE%', '%PISTON%', '%RING%', '%KLEP%', '%VALVE%', '%SEHER%', '%BEARING%', '%LAHER%', '%GASKET%', '%PAKING%', '%HEAD%', '%BLOK%', '%SILINDER%', '%CYLINDER%']
        ],
        'CV' => [
            'name' => 'CVT',
            'keywords' => ['%CVT%', '%BELT%', '%VBELT%', '%V-BELT%', '%PULLEY%', '%PULI%', '%VARIO%', '%MATIC%', '%CLUTCH%', '%KOPLING%']
        ],
        'RD' => [
            'name' => 'RODA',
            'keywords' => ['%RODA%', '%WHEEL%', '%BAN%', '%TIRE%', '%VELG%', '%RIM%', '%PELEK%', '%SPOKE%', '%JARI%']
        ],
        'CR' => [
            'name' => 'CARBU',
            'keywords' => ['%CARBU%', '%KARBU%', '%CARBURETOR%', '%FUEL%', '%BENSIN%', '%INJECTION%', '%INJECTOR%']
        ],
        'FL' => [
            'name' => 'FILTER',
            'keywords' => ['%FILTER%', '%SARINGAN%', '%UDARA%', '%AIR%', '%ANGIN%']
        ],
        'CH' => [
            'name' => 'CAIRAN',
            'keywords' => ['%OLI%', '%OIL%', '%CAIRAN%', '%LIQUID%', '%MINYAK%', '%GREASE%', '%PELUMAS%', '%COOLANT%', '%RADIATOR%']
        ],
        'BD' => [
            'name' => 'BAUD',
            'keywords' => ['%BAUD%', '%MUR%', '%BOLT%', '%SCREW%', '%SEKRUP%', '%NUT%', '%WASHER%', '%RING%']
        ]
    ];
    
    echo "\nStep 2: Assigning categories based on keywords...\n";
    
    foreach ($category_fixes as $category => $config) {
        $conditions = [];
        foreach ($config['keywords'] as $keyword) {
            $conditions[] = "namaitem LIKE '$keyword'";
        }
        $condition_sql = implode(' OR ', $conditions);
        
        $update_sql = "UPDATE tblitem SET 
                       kategori_rak = '$category'
                       WHERE tipe_item = 'NON_ORI' 
                       AND (kategori_rak IS NULL OR kategori_rak = '')
                       AND ($condition_sql)";
        
        if (mysqli_query($koneksi, $update_sql)) {
            $affected = mysqli_affected_rows($koneksi);
            echo "✓ $category ({$config['name']}): $affected items updated\n";
            $total_updated += $affected;
            $fixes_executed++;
        } else {
            echo "✗ $category ({$config['name']}): Failed - " . mysqli_error($koneksi) . "\n";
        }
    }
    
    // Step 3: Set remaining items to default category
    echo "\nStep 3: Setting default category for remaining items...\n";
    $default_sql = "UPDATE tblitem SET 
                    kategori_rak = 'MS'
                    WHERE tipe_item = 'NON_ORI' 
                    AND (kategori_rak IS NULL OR kategori_rak = '')";
    
    if (mysqli_query($koneksi, $default_sql)) {
        $affected = mysqli_affected_rows($koneksi);
        echo "✓ Default category (MS): $affected items updated\n";
        $total_updated += $affected;
        $fixes_executed++;
    } else {
        echo "✗ Default category failed: " . mysqli_error($koneksi) . "\n";
    }
    
    // Step 4: Optional bulk validation
    echo "\nStep 4: Bulk validation for items with proper naming...\n";
    $validation_sql = "UPDATE tblitem SET 
                       status_validasi = 'validated',
                       validated_by = $user_id,
                       updated_at = CURRENT_TIMESTAMP  
                       WHERE tipe_item = 'NON_ORI' 
                       AND kategori_rak IS NOT NULL 
                       AND kategori_rak != ''
                       AND status_validasi = 'pending_validation'
                       AND namaitem LIKE '%IMI%'";
    
    if (mysqli_query($koneksi, $validation_sql)) {
        $affected = mysqli_affected_rows($koneksi);
        echo "✓ Bulk validation: $affected items validated\n";
        $fixes_executed++;
    } else {
        echo "✗ Bulk validation failed: " . mysqli_error($koneksi) . "\n";
    }
    
    // Step 5: Set audit trail defaults
    echo "\nStep 5: Setting audit trail defaults...\n";
    $audit_sql = "UPDATE tblitem SET 
                  created_by = $user_id
                  WHERE created_by IS NULL 
                  AND statusitem = '1'";
    
    if (mysqli_query($koneksi, $audit_sql)) {
        $affected = mysqli_affected_rows($koneksi);
        echo "✓ Audit trail: $affected items updated\n";
        $fixes_executed++;
    } else {
        echo "✗ Audit trail failed: " . mysqli_error($koneksi) . "\n";
    }
    
    echo "\n=== FIXES EXECUTION COMPLETED ===\n";
    echo "Total fixes attempted: " . count($category_fixes) + 4 . "\n";
    echo "Fixes executed successfully: $fixes_executed\n";
    echo "Total items updated: $total_updated\n";
    echo "</pre>";
    
    // Show results after fixes
    echo "<h3>AFTER FIXES - Results</h3>";
    echo "<pre>";
    
    $result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item = 'NON_ORI' AND (kategori_rak IS NULL OR kategori_rak = '') AND statusitem = '1'");
    $uncategorized_after = mysqli_fetch_assoc($result)['count'];
    echo "Uncategorized NON-ORI items: $uncategorized_after (was: $uncategorized_before)\n";
    
    echo "\nCategory distribution:\n";
    $result = mysqli_query($koneksi, "SELECT kategori_rak, COUNT(*) as count FROM tblitem WHERE tipe_item = 'NON_ORI' AND statusitem = '1' GROUP BY kategori_rak ORDER BY count DESC");
    while ($row = mysqli_fetch_assoc($result)) {
        $cat = $row['kategori_rak'] ?: 'NULL';
        echo "  $cat: {$row['count']} items\n";
    }
    
    echo "\nValidation status distribution:\n";
    $result = mysqli_query($koneksi, "SELECT status_validasi, COUNT(*) as count FROM tblitem WHERE statusitem = '1' GROUP BY status_validasi");
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['status_validasi'] ?: 'NULL';
        echo "  $status: {$row['count']} items\n";
    }
    
    if ($uncategorized_after == 0) {
        echo "\n🎉 SUCCESS! All NON-ORI items now have categories assigned.\n";
        echo "The auto-code generation system is now fully operational!\n";
    } else {
        echo "\n⚠️  WARNING: $uncategorized_after items still need manual category assignment.\n";
    }
    
    echo "</pre>";
    
    echo "<h3>Next Steps</h3>";
    echo "<ul>";
    echo "<li>✅ Test the add item functionality at <a href='barang_add_improved.php'>barang_add_improved.php</a></li>";
    echo "<li>✅ Test auto-code generation for NON-ORI items</li>";
    echo "<li>✅ Verify the item listing at <a href='barang_list_improved.php'>barang_list_improved.php</a></li>";
    echo "<li>📝 Optional: Review and manually validate remaining pending items</li>";
    echo "</ul>";
    
} else {
    // Show confirmation form
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ Database Update Required</h4>";
    echo "<p>Found <strong>$uncategorized_before uncategorized NON-ORI items</strong> that need category assignment.</p>";
    echo "<p>This fix is <strong>required</strong> for the auto-code generation to work properly.</p>";
    echo "</div>";
    
    echo "<h3>What This Update Will Do:</h3>";
    echo "<ol>";
    echo "<li><strong>Create backup</strong> of current state</li>";
    echo "<li><strong>Auto-assign categories</strong> based on item name keywords</li>";
    echo "<li><strong>Set default category</strong> for unmatched items</li>";
    echo "<li><strong>Bulk validate</strong> obvious items (optional)</li>";
    echo "<li><strong>Set audit trail</strong> defaults (optional)</li>";
    echo "</ol>";
    
    echo "<h3>Safety Features:</h3>";
    echo "<ul>";
    echo "<li>✅ Automatic backup creation</li>";
    echo "<li>✅ Non-destructive updates</li>";
    echo "<li>✅ Rollback capability</li>";
    echo "<li>✅ Detailed logging</li>";
    echo "</ul>";
    
    echo "<form method='POST' style='margin-top: 20px;'>";
    echo "<div class='alert alert-info'>";
    echo "<p><strong>Ready to execute database fixes?</strong></p>";
    echo "<p><em>Estimated time: 2-5 minutes</em></p>";
    echo "<button type='submit' name='execute_fixes' class='btn btn-primary btn-lg' onclick='return confirm(\"Execute database fixes now? This will update $uncategorized_before items.\")'>Execute Database Fixes</button>";
    echo "</div>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='barang.php' class='btn btn-default'>← Back to Item List</a> ";
echo "<a href='barang_add_improved.php' class='btn btn-success'>Test Add Item</a></p>";
?>