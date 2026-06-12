<?php
/**
 * Check Database untuk Sistem Temuan & Penawaran
 * Comprehensive check untuk semua tabel dan struktur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../config/koneksi.php";

echo "<html><head>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #4CAF50; color: white; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
</style>";
echo "</head><body>";

echo "<h1>🔍 Database Check - Sistem Temuan & Penawaran</h1>";
echo "<p><strong>Tanggal:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// ============================================
// 1. CHECK TABLES
// ============================================
echo "<div class='section'>";
echo "<h2>1. Check Tables</h2>";

$required_tables = [
    'tbmaster_temuan' => 'Master data temuan',
    'tbservis_temuan' => 'Temuan per servis',
    'tbservis_penawaran_part' => 'Penawaran part',
    'tbservis_penawaran_log' => 'Log penawaran',
    'tbmaster_kategori_fastmoves' => 'Kategori fast moves',
    'tbmaster_barang_fastmoves' => 'Mapping barang fast moves',
    'tbmaster_barang_nonitem' => 'Barang non-item',
    'tbmaster_keluhan_temuan' => 'Mapping keluhan-temuan'
];

echo "<table>";
echo "<tr><th>No</th><th>Table Name</th><th>Description</th><th>Status</th><th>Row Count</th></tr>";

$no = 1;
$missing_tables = [];

foreach($required_tables as $table => $desc) {
    $check = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    
    if(mysqli_num_rows($check) > 0) {
        $count_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM $table");
        $count_row = mysqli_fetch_array($count_query);
        $count = $count_row['total'];
        
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$desc</td>";
        echo "<td class='success'>✅ EXISTS</td>";
        echo "<td>$count rows</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$desc</td>";
        echo "<td class='error'>❌ NOT FOUND</td>";
        echo "<td>-</td>";
        echo "</tr>";
        
        $missing_tables[] = $table;
    }
    $no++;
}

echo "</table>";

if(count($missing_tables) > 0) {
    echo "<div class='error'>";
    echo "<h3>⚠️ MISSING TABLES:</h3>";
    echo "<p>Tabel berikut tidak ditemukan:</p>";
    echo "<ul>";
    foreach($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    echo "<p><strong>ACTION:</strong> Jalankan file <code>database_master_temuan_penawaran.sql</code></p>";
    echo "</div>";
}

echo "</div>";

// ============================================
// 2. CHECK COLUMNS IN TBLSERVICE
// ============================================
echo "<div class='section'>";
echo "<h2>2. Check Columns in tblservice</h2>";

$required_columns = [
    'jumlah_temuan' => 'INT(11)',
    'jumlah_penawaran' => 'INT(11)',
    'total_penawaran' => 'DECIMAL(15,2)',
    'total_penawaran_disetujui' => 'DECIMAL(15,2)'
];

echo "<table>";
echo "<tr><th>No</th><th>Column Name</th><th>Expected Type</th><th>Status</th><th>Actual Type</th></tr>";

$no = 1;
$missing_columns = [];

foreach($required_columns as $col => $type) {
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE '$col'");
    
    if(mysqli_num_rows($check) > 0) {
        $col_info = mysqli_fetch_array($check);
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$col</strong></td>";
        echo "<td>$type</td>";
        echo "<td class='success'>✅ EXISTS</td>";
        echo "<td>{$col_info['Type']}</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$col</strong></td>";
        echo "<td>$type</td>";
        echo "<td class='error'>❌ NOT FOUND</td>";
        echo "<td>-</td>";
        echo "</tr>";
        
        $missing_columns[] = $col;
    }
    $no++;
}

echo "</table>";

if(count($missing_columns) > 0) {
    echo "<div class='error'>";
    echo "<h3>⚠️ MISSING COLUMNS:</h3>";
    echo "<p>Kolom berikut tidak ditemukan di tblservice:</p>";
    echo "<ul>";
    foreach($missing_columns as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";
    echo "<p><strong>ACTION:</strong> Jalankan ALTER TABLE dari file SQL</p>";
    echo "</div>";
}

echo "</div>";

// ============================================
// 3. CHECK SAMPLE DATA
// ============================================
echo "<div class='section'>";
echo "<h2>3. Check Sample Data</h2>";

// Check tbmaster_temuan
$count_temuan = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbmaster_temuan");
$row_temuan = mysqli_fetch_array($count_temuan);

echo "<p><strong>tbmaster_temuan:</strong> {$row_temuan['total']} records</p>";

if($row_temuan['total'] == 0) {
    echo "<div class='warning'>";
    echo "<p>⚠️ Tidak ada data master temuan. Sistem bisa berjalan tapi modal search temuan akan kosong.</p>";
    echo "<p><strong>REKOMENDASI:</strong> Insert sample data dari file SQL</p>";
    echo "</div>";
} else {
    echo "<p class='success'>✅ Ada data master temuan</p>";
    
    // Show sample
    $sample = mysqli_query($koneksi, "SELECT * FROM tbmaster_temuan LIMIT 5");
    echo "<table>";
    echo "<tr><th>Kode</th><th>Nama Temuan</th><th>Kategori</th><th>Urgensi</th><th>Active</th></tr>";
    while($row = mysqli_fetch_array($sample)) {
        echo "<tr>";
        echo "<td>{$row['kode_temuan']}</td>";
        echo "<td>{$row['nama_temuan']}</td>";
        echo "<td>{$row['kategori']}</td>";
        echo "<td>{$row['tingkat_urgensi']}</td>";
        echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check tbmaster_kategori_fastmoves
$count_kategori = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbmaster_kategori_fastmoves");
$row_kategori = mysqli_fetch_array($count_kategori);

echo "<p><strong>tbmaster_kategori_fastmoves:</strong> {$row_kategori['total']} records</p>";

if($row_kategori['total'] == 0) {
    echo "<div class='warning'>";
    echo "<p>⚠️ Tidak ada kategori fast moves. Modal fast moves akan kosong.</p>";
    echo "<p><strong>REKOMENDASI:</strong> Insert kategori dari file SQL atau via halaman master-fastmoves.php</p>";
    echo "</div>";
} else {
    echo "<p class='success'>✅ Ada kategori fast moves</p>";
    
    // Show sample
    $sample = mysqli_query($koneksi, "SELECT * FROM tbmaster_kategori_fastmoves LIMIT 10");
    echo "<table>";
    echo "<tr><th>Kode</th><th>Nama Kategori</th><th>Icon</th><th>Urutan</th><th>Active</th></tr>";
    while($row = mysqli_fetch_array($sample)) {
        echo "<tr>";
        echo "<td>{$row['kode_kategori']}</td>";
        echo "<td>{$row['nama_kategori']}</td>";
        echo "<td>{$row['icon']}</td>";
        echo "<td>{$row['urutan']}</td>";
        echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check tbmaster_barang_fastmoves
$count_mapping = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbmaster_barang_fastmoves");
$row_mapping = mysqli_fetch_array($count_mapping);

echo "<p><strong>tbmaster_barang_fastmoves:</strong> {$row_mapping['total']} records</p>";

if($row_mapping['total'] == 0) {
    echo "<div class='warning'>";
    echo "<p>⚠️ Tidak ada mapping barang ke kategori. Fast moves tidak akan menampilkan part.</p>";
    echo "<p><strong>REKOMENDASI:</strong> Mapping barang via halaman master-fastmoves.php</p>";
    echo "</div>";
} else {
    echo "<p class='success'>✅ Ada mapping barang</p>";
}

echo "</div>";

// ============================================
// 4. CHECK VIEWS
// ============================================
echo "<div class='section'>";
echo "<h2>4. Check Views</h2>";

$required_views = [
    'view_servis_temuan_lengkap',
    'view_penawaran_part_lengkap',
    'view_fastmoves_barang'
];

echo "<table>";
echo "<tr><th>No</th><th>View Name</th><th>Status</th></tr>";

$no = 1;
foreach($required_views as $view) {
    $check = mysqli_query($koneksi, "SHOW TABLES LIKE '$view'");
    
    if(mysqli_num_rows($check) > 0) {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$view</strong></td>";
        echo "<td class='success'>✅ EXISTS</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td><strong>$view</strong></td>";
        echo "<td class='error'>❌ NOT FOUND</td>";
        echo "</tr>";
    }
    $no++;
}

echo "</table>";
echo "</div>";

// ============================================
// 5. CHECK TRIGGERS
// ============================================
echo "<div class='section'>";
echo "<h2>5. Check Triggers</h2>";

$triggers = mysqli_query($koneksi, "SHOW TRIGGERS WHERE `Trigger` LIKE '%temuan%' OR `Trigger` LIKE '%penawaran%'");

if(mysqli_num_rows($triggers) > 0) {
    echo "<table>";
    echo "<tr><th>Trigger Name</th><th>Event</th><th>Table</th><th>Timing</th></tr>";
    
    while($trigger = mysqli_fetch_array($triggers)) {
        echo "<tr>";
        echo "<td>{$trigger['Trigger']}</td>";
        echo "<td>{$trigger['Event']}</td>";
        echo "<td>{$trigger['Table']}</td>";
        echo "<td>{$trigger['Timing']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p class='success'>✅ Triggers ditemukan</p>";
} else {
    echo "<p class='warning'>⚠️ Tidak ada triggers untuk temuan/penawaran</p>";
}

echo "</div>";

// ============================================
// 6. TEST QUERY
// ============================================
echo "<div class='section'>";
echo "<h2>6. Test Query (Sample Service)</h2>";

// Get sample service
$sample_service = mysqli_query($koneksi, "SELECT no_service FROM tblservice ORDER BY tanggal DESC LIMIT 1");

if(mysqli_num_rows($sample_service) > 0) {
    $service = mysqli_fetch_array($sample_service);
    $no_service = $service['no_service'];
    
    echo "<p><strong>Testing with service:</strong> $no_service</p>";
    
    // Test query temuan
    $test_temuan = mysqli_query($koneksi, "
        SELECT st.*, 
               COALESCE(mt.nama_temuan, st.temuan_custom) as nama_temuan
        FROM tbservis_temuan st
        LEFT JOIN tbmaster_temuan mt ON st.kode_temuan = mt.kode_temuan
        WHERE st.no_service = '$no_service'
    ");
    
    if($test_temuan) {
        echo "<p class='success'>✅ Query temuan berhasil (" . mysqli_num_rows($test_temuan) . " rows)</p>";
    } else {
        echo "<p class='error'>❌ Query temuan error: " . mysqli_error($koneksi) . "</p>";
    }
    
    // Test query penawaran
    $test_penawaran = mysqli_query($koneksi, "
        SELECT spp.*
        FROM tbservis_penawaran_part spp
        WHERE spp.no_service = '$no_service'
    ");
    
    if($test_penawaran) {
        echo "<p class='success'>✅ Query penawaran berhasil (" . mysqli_num_rows($test_penawaran) . " rows)</p>";
    } else {
        echo "<p class='error'>❌ Query penawaran error: " . mysqli_error($koneksi) . "</p>";
    }
    
} else {
    echo "<p class='warning'>⚠️ Tidak ada data service untuk testing</p>";
}

echo "</div>";

// ============================================
// 7. SUMMARY & RECOMMENDATIONS
// ============================================
echo "<div class='section'>";
echo "<h2>7. Summary & Recommendations</h2>";

$total_issues = count($missing_tables) + count($missing_columns);

if($total_issues == 0) {
    echo "<div class='success'>";
    echo "<h3>✅ DATABASE READY!</h3>";
    echo "<p>Semua tabel dan kolom sudah ada. Sistem siap digunakan.</p>";
    echo "</div>";
    
    if($row_temuan['total'] == 0 || $row_kategori['total'] == 0 || $row_mapping['total'] == 0) {
        echo "<div class='warning'>";
        echo "<h4>⚠️ Data Masih Kosong</h4>";
        echo "<p>Tabel sudah ada tapi data masih kosong. Rekomendasi:</p>";
        echo "<ol>";
        if($row_temuan['total'] == 0) {
            echo "<li>Insert sample data master temuan</li>";
        }
        if($row_kategori['total'] == 0) {
            echo "<li>Insert kategori fast moves (41 kategori)</li>";
        }
        if($row_mapping['total'] == 0) {
            echo "<li>Mapping barang ke kategori via halaman master-fastmoves.php</li>";
        }
        echo "</ol>";
        echo "</div>";
    }
} else {
    echo "<div class='error'>";
    echo "<h3>❌ DATABASE INCOMPLETE!</h3>";
    echo "<p>Ditemukan $total_issues masalah:</p>";
    echo "<ul>";
    if(count($missing_tables) > 0) {
        echo "<li>" . count($missing_tables) . " tabel tidak ditemukan</li>";
    }
    if(count($missing_columns) > 0) {
        echo "<li>" . count($missing_columns) . " kolom tidak ditemukan</li>";
    }
    echo "</ul>";
    
    echo "<h4>🔧 ACTION REQUIRED:</h4>";
    echo "<ol>";
    echo "<li>Buka MySQL/phpMyAdmin</li>";
    echo "<li>Pilih database: <strong>fitmotor_dbbengkel</strong></li>";
    echo "<li>Import file: <strong>database_master_temuan_penawaran.sql</strong></li>";
    echo "<li>Atau jalankan via command line:</li>";
    echo "</ol>";
    echo "<pre>mysql -u root -p fitmotor_dbbengkel < database_master_temuan_penawaran.sql</pre>";
    echo "</div>";
}

echo "</div>";

echo "<hr>";
echo "<p><em>Check completed at " . date('Y-m-d H:i:s') . "</em></p>";

echo "</body></html>";
?>
