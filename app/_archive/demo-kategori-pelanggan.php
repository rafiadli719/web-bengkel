<?php
/**
 * Demo Kategori Pelanggan dan Sistem Diskon
 * Menampilkan contoh kategori pelanggan dan diskon yang diberikan
 */

// Test data kategori pelanggan
$test_customers = [
    [
        'nama' => 'BUDI SANTOSO',
        'kategori' => 'GOLD',
        'diskon' => 15,
        'total_service' => 500000,
        'description' => 'Member Gold mendapat diskon 15%'
    ],
    [
        'nama' => 'SITI AMINAH',
        'kategori' => 'SILVER',
        'diskon' => 10,
        'total_service' => 300000,
        'description' => 'Member Silver mendapat diskon 10%'
    ],
    [
        'nama' => 'AHMAD WIJAYA',
        'kategori' => 'BRONZE',
        'diskon' => 5,
        'total_service' => 200000,
        'description' => 'Member Bronze mendapat diskon 5%'
    ],
    [
        'nama' => 'REGULAR CUSTOMER',
        'kategori' => 'REGULAR',
        'diskon' => 0,
        'total_service' => 150000,
        'description' => 'Pelanggan regular tanpa diskon khusus'
    ]
];

echo "<h2>Demo Sistem Diskon Berdasarkan Kategori Pelanggan</h2>";
echo "<p>Sistem ini secara otomatis memberikan diskon berdasarkan kategori member pelanggan.</p>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background-color: #f5f5f5;'>";
echo "<th style='padding: 10px;'>Nama Pelanggan</th>";
echo "<th style='padding: 10px;'>Kategori</th>";
echo "<th style='padding: 10px;'>Diskon</th>";
echo "<th style='padding: 10px;'>Total Service</th>";
echo "<th style='padding: 10px;'>Diskon Amount</th>";
echo "<th style='padding: 10px;'>Total Bayar</th>";
echo "<th style='padding: 10px;'>Keterangan</th>";
echo "</tr>";

foreach ($test_customers as $customer) {
    $diskon_amount = $customer['total_service'] * ($customer['diskon'] / 100);
    $total_bayar = $customer['total_service'] - $diskon_amount;
    
    $badge_color = '';
    switch($customer['kategori']) {
        case 'GOLD': $badge_color = 'background-color: #ffc107; color: #000;'; break;
        case 'SILVER': $badge_color = 'background-color: #6c757d; color: #fff;'; break;
        case 'BRONZE': $badge_color = 'background-color: #cd7f32; color: #fff;'; break;
        default: $badge_color = 'background-color: #e9ecef; color: #000;';
    }
    
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . $customer['nama'] . "</td>";
    echo "<td style='padding: 8px;'><span style='padding: 4px 8px; border-radius: 4px; $badge_color'>" . $customer['kategori'] . "</span></td>";
    echo "<td style='padding: 8px; text-align: center;'>" . $customer['diskon'] . "%</td>";
    echo "<td style='padding: 8px; text-align: right;'>Rp " . number_format($customer['total_service'], 0, ',', '.') . "</td>";
    echo "<td style='padding: 8px; text-align: right; color: #dc3545;'>Rp " . number_format($diskon_amount, 0, ',', '.') . "</td>";
    echo "<td style='padding: 8px; text-align: right; font-weight: bold;'>Rp " . number_format($total_bayar, 0, ',', '.') . "</td>";
    echo "<td style='padding: 8px;'>" . $customer['description'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Cara Kerja Sistem:</h3>";
echo "<ol>";
echo "<li><strong>Deteksi Otomatis:</strong> Sistem membaca kategori pelanggan dari database (field 'kgrup' di tabel tblpelanggan)</li>";
echo "<li><strong>Diskon Otomatis:</strong> Berdasarkan kategori, sistem otomatis menerapkan diskon:";
echo "<ul>";
echo "<li>GOLD Member: 15% diskon</li>";
echo "<li>SILVER Member: 10% diskon</li>";
echo "<li>BRONZE Member: 5% diskon</li>";
echo "<li>Regular: Menggunakan diskon dari database pelanggan (jika ada)</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>Diskon Tambahan:</strong> Admin masih bisa memberikan diskon tambahan di atas diskon member</li>";
echo "<li><strong>Perhitungan Real-time:</strong> Total pembayaran dihitung ulang secara otomatis saat ada perubahan</li>";
echo "</ol>";

echo "<h3>Integrasi dengan Tab Lain:</h3>";
echo "<ul>";
echo "<li><strong>Tab Item Barang:</strong> Total barang otomatis terintegrasi ke perhitungan pembayaran</li>";
echo "<li><strong>Tab Item Jasa:</strong> Total jasa otomatis terintegrasi ke perhitungan pembayaran</li>";
echo "<li><strong>Tab Actions:</strong> Menampilkan breakdown lengkap: Total Jasa + Total Barang = Subtotal - Diskon + PPN = Total Bayar</li>";
echo "</ul>";

echo "<h3>Contoh Konfigurasi Database:</h3>";
echo "<pre style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "UPDATE tblpelanggan SET kgrup = 'GOLD' WHERE nopelanggan = 'B1234ABC';\n";
echo "UPDATE tblpelanggan SET kgrup = 'SILVER' WHERE nopelanggan = 'B5678DEF';\n";
echo "UPDATE tblpelanggan SET kgrup = 'BRONZE' WHERE nopelanggan = 'B9012GHI';";
echo "</pre>";

echo "<p><a href='servis-input-reguler.php' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>← Kembali ke Input Service</a></p>";
?>
