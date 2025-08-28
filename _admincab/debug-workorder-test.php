<?php
// File: debug-workorder-test.php
// Debug script untuk test workorder auto-add functionality

session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Ambil parameter test
$test_workorder = isset($_GET['test_wo']) ? $_GET['test_wo'] : 'WO0001';
$test_service = isset($_GET['test_service']) ? $_GET['test_service'] : '';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug WorkOrder Test</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>

<h1>Debug WorkOrder Auto-Add Functionality</h1>

<div class="test-section">
    <h2>1. Test Master Keluhan Data</h2>
    <table>
        <tr>
            <th>Kode Keluhan</th>
            <th>Nama Keluhan</th>
            <th>Kategori</th>
            <th>Prioritas</th>
            <th>WorkOrder Default</th>
            <th>Status</th>
        </tr>
        <?php
        $keluhan_query = "SELECT * FROM tbmaster_keluhan WHERE status_aktif = '1' ORDER BY kategori, nama_keluhan";
        $keluhan_result = mysqli_query($koneksi, $keluhan_query);
        
        if($keluhan_result) {
            while($row = mysqli_fetch_assoc($keluhan_result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['kode_keluhan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_keluhan']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tingkat_prioritas']) . "</td>";
                echo "<td>" . htmlspecialchars($row['workorder_default'] ?? '-') . "</td>";
                echo "<td class='success'>Aktif</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6' class='error'>Error: " . mysqli_error($koneksi) . "</td></tr>";
        }
        ?>
    </table>
</div>

<div class="test-section">
    <h2>2. Test WorkOrder Detail</h2>
    <form method="GET">
        <label>Test WorkOrder: 
            <select name="test_wo">
                <option value="WO0001" <?php echo ($test_workorder == 'WO0001') ? 'selected' : ''; ?>>WO0001</option>
                <option value="WO0002" <?php echo ($test_workorder == 'WO0002') ? 'selected' : ''; ?>>WO0002</option>
                <option value="WO0003" <?php echo ($test_workorder == 'WO0003') ? 'selected' : ''; ?>>WO0003</option>
                <option value="WO0005" <?php echo ($test_workorder == 'WO0005') ? 'selected' : ''; ?>>WO0005</option>
            </select>
        </label>
        <button type="submit">Test</button>
    </form>
    
    <h3>WorkOrder Header: <?php echo $test_workorder; ?></h3>
    <table>
        <tr>
            <th>Kode WO</th>
            <th>Nama WO</th>
            <th>Harga</th>
            <th>Waktu</th>
            <th>Status</th>
        </tr>
        <?php
        $wo_header_query = "SELECT * FROM tbworkorderheader WHERE kode_wo = '$test_workorder'";
        $wo_header_result = mysqli_query($koneksi, $wo_header_query);
        
        if($wo_header_result && mysqli_num_rows($wo_header_result) > 0) {
            $wo_header = mysqli_fetch_assoc($wo_header_result);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($wo_header['kode_wo']) . "</td>";
            echo "<td>" . htmlspecialchars($wo_header['nama_wo']) . "</td>";
            echo "<td>Rp " . number_format($wo_header['harga'], 0, ',', '.') . "</td>";
            echo "<td>" . $wo_header['waktu'] . " menit</td>";
            echo "<td>" . ($wo_header['status'] == '0' ? 'Aktif' : 'Nonaktif') . "</td>";
            echo "</tr>";
        } else {
            echo "<tr><td colspan='5' class='error'>WorkOrder tidak ditemukan</td></tr>";
        }
        ?>
    </table>
    
    <h3>WorkOrder Detail Items: <?php echo $test_workorder; ?></h3>
    <table>
        <tr>
            <th>Kode Barang</th>
            <th>Tipe</th>
            <th>Nama Item</th>
            <th>Harga</th>
            <th>Jumlah</th>
            <th>Total</th>
        </tr>
        <?php
        $wo_detail_query = "SELECT wd.*, 
                                  CASE 
                                      WHEN wd.tipe = '1' THEN 'Jasa'
                                      WHEN wd.tipe = '2' THEN 'Barang'
                                      ELSE 'Unknown'
                                  END as tipe_name,
                                  CASE 
                                      WHEN wd.tipe = '1' THEN j.namajasa
                                      WHEN wd.tipe = '2' THEN b.namaitem
                                      ELSE 'Unknown'
                                  END as nama_item
                           FROM tbworkorderdetail wd
                           LEFT JOIN tbjasa j ON wd.kode_barang = j.kodejasa AND wd.tipe = '1'
                           LEFT JOIN tblbarang b ON wd.kode_barang = b.noitem AND wd.tipe = '2'
                           WHERE wd.kode_wo = '$test_workorder'
                           ORDER BY wd.tipe, wd.kode_barang";
        
        $wo_detail_result = mysqli_query($koneksi, $wo_detail_query);
        $detail_count = 0;
        
        if($wo_detail_result) {
            while($detail = mysqli_fetch_assoc($wo_detail_result)) {
                $detail_count++;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($detail['kode_barang']) . "</td>";
                echo "<td>" . htmlspecialchars($detail['tipe_name']) . "</td>";
                echo "<td>" . htmlspecialchars($detail['nama_item'] ?? 'Item tidak ditemukan') . "</td>";
                echo "<td>Rp " . number_format($detail['harga'], 0, ',', '.') . "</td>";
                echo "<td>" . $detail['jumlah'] . "</td>";
                echo "<td>Rp " . number_format($detail['total'], 0, ',', '.') . "</td>";
                echo "</tr>";
            }
        }
        
        if($detail_count == 0) {
            echo "<tr><td colspan='6' class='error'>Tidak ada detail items untuk WorkOrder ini</td></tr>";
        } else {
            echo "<tr><td colspan='6' class='info'><strong>Total Items: $detail_count</strong></td></tr>";
        }
        ?>
    </table>
</div>

<div class="test-section">
    <h2>3. Test Keluhan Mapping</h2>
    <table>
        <tr>
            <th>Kode Keluhan</th>
            <th>Kode WorkOrder</th>
            <th>Prioritas</th>
            <th>Status</th>
        </tr>
        <?php
        $mapping_query = "SELECT * FROM tbmaster_keluhan_workorder WHERE status_aktif = '1' ORDER BY prioritas DESC";
        $mapping_result = mysqli_query($koneksi, $mapping_query);
        
        if($mapping_result) {
            while($mapping = mysqli_fetch_assoc($mapping_result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($mapping['kode_keluhan']) . "</td>";
                echo "<td>" . htmlspecialchars($mapping['kode_workorder']) . "</td>";
                echo "<td>" . htmlspecialchars($mapping['prioritas']) . "</td>";
                echo "<td class='success'>Aktif</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='error'>Error: " . mysqli_error($koneksi) . "</td></tr>";
        }
        ?>
    </table>
</div>

<div class="test-section">
    <h2>4. Test Service Data</h2>
    <?php if($test_service): ?>
    <h3>Service: <?php echo htmlspecialchars($test_service); ?></h3>
    
    <h4>Service Keluhan:</h4>
    <table>
        <tr>
            <th>ID</th>
            <th>Keluhan</th>
            <th>Auto WorkOrder</th>
            <th>WorkOrder Applied</th>
            <th>Status</th>
        </tr>
        <?php
        $service_keluhan_query = "SELECT * FROM tbservis_keluhan_status WHERE no_service = '$test_service'";
        $service_keluhan_result = mysqli_query($koneksi, $service_keluhan_query);
        
        if($service_keluhan_result) {
            while($sk = mysqli_fetch_assoc($service_keluhan_result)) {
                echo "<tr>";
                echo "<td>" . $sk['id'] . "</td>";
                echo "<td>" . htmlspecialchars($sk['keluhan']) . "</td>";
                echo "<td>" . htmlspecialchars($sk['auto_workorder'] ?? '-') . "</td>";
                echo "<td>" . ($sk['workorder_applied'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . htmlspecialchars($sk['status_pengerjaan']) . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
    
    <h4>Service WorkOrders:</h4>
    <table>
        <tr>
            <th>ID</th>
            <th>Kode WO</th>
            <th>Status</th>
        </tr>
        <?php
        $service_wo_query = "SELECT * FROM tbservis_workorder WHERE no_service = '$test_service'";
        $service_wo_result = mysqli_query($koneksi, $service_wo_query);
        
        if($service_wo_result) {
            while($swo = mysqli_fetch_assoc($service_wo_result)) {
                echo "<tr>";
                echo "<td>" . $swo['id'] . "</td>";
                echo "<td>" . htmlspecialchars($swo['kode_wo']) . "</td>";
                echo "<td>" . htmlspecialchars($swo['status_pengerjaan']) . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
    <?php else: ?>
    <form method="GET">
        <input type="hidden" name="test_wo" value="<?php echo $test_workorder; ?>">
        <label>Test Service No: 
            <input type="text" name="test_service" placeholder="Masukkan no service untuk test" />
        </label>
        <button type="submit">Test Service</button>
    </form>
    <?php endif; ?>
</div>

<div class="test-section">
    <h2>5. Database Tables Status</h2>
    <table>
        <tr>
            <th>Table</th>
            <th>Count</th>
            <th>Status</th>
        </tr>
        <?php
        $tables = [
            'tbmaster_keluhan' => 'Master Keluhan',
            'tbmaster_keluhan_workorder' => 'Keluhan-WorkOrder Mapping',
            'tbworkorderheader' => 'WorkOrder Header',
            'tbworkorderdetail' => 'WorkOrder Detail',
            'tbservis_keluhan_status' => 'Service Keluhan',
            'tbservis_workorder' => 'Service WorkOrder'
        ];
        
        foreach($tables as $table => $description) {
            $count_query = "SELECT COUNT(*) as count FROM $table";
            $count_result = mysqli_query($koneksi, $count_query);
            
            if($count_result) {
                $count_data = mysqli_fetch_assoc($count_result);
                echo "<tr>";
                echo "<td>$description ($table)</td>";
                echo "<td>" . $count_data['count'] . "</td>";
                echo "<td class='success'>OK</td>";
                echo "</tr>";
            } else {
                echo "<tr>";
                echo "<td>$description ($table)</td>";
                echo "<td>-</td>";
                echo "<td class='error'>Error: " . mysqli_error($koneksi) . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
</div>

<div class="test-section">
    <h2>6. Quick Test Actions</h2>
    <p><strong>Test Steps:</strong></p>
    <ol>
        <li>Pastikan ada data di Master Keluhan dengan workorder_default</li>
        <li>Pastikan WorkOrder yang di-reference memiliki detail items</li>
        <li>Test input keluhan di halaman servis reguler</li>
        <li>Check apakah workorder dan items otomatis ditambahkan</li>
    </ol>
    
    <p><strong>Common Issues:</strong></p>
    <ul>
        <li>WorkOrder detail kosong → Check tbworkorderdetail</li>
        <li>Master keluhan tidak ada workorder_default → Update master data</li>
        <li>Tipe item salah (harus '1' untuk jasa, '2' untuk barang)</li>
        <li>Referensi item tidak valid (jasa/barang tidak ada)</li>
    </ul>
</div>

</body>
</html>