<?php
/**
 * Script untuk update semua file menu
 * - Tambah menu Fast Moves Mapping dan Master Temuan
 * - Hapus menu Kas Akhir
 * - Hapus menu Jadwal Penjemputan
 */

$menu_files = [
    'menu_adm01.php',
    'menu_adm02.php',
    'menu_adm03.php',
    'menu_adm04.php',
    'menu_akun.php',
    'menu_akun_biaya.php',
    'menu_antarcab01.php',
    'menu_antarcab02.php',
    'menu_antarcab03.php',
    'menu_cabang01.php',
    'menu_cabang02.php',
    'menu_dashboard.php',
    'menu_kasir01.php',
    'menu_kasir02a.php',
    'menu_kasir02b.php',
    'menu_kasir03.php',
    'menu_kendaraan01.php',
    'menu_kendaraan02.php',
    'menu_kendaraan03.php',
    'menu_kendaraan04.php',
    'menu_kendaraan05.php',
    'menu_laporan01.php',
    'menu_laporan02.php',
    'menu_laporan03.php',
    'menu_laporan04.php',
    'menu_laporan05.php',
    'menu_laporan06.php',
    'menu_laporan07.php',
    'menu_laporan08.php',
    'menu_laporan09.php',
    'menu_laporan10.php',
    'menu_laporan11.php',
    'menu_master01a.php',
    'menu_master01b.php',
    'menu_master01c.php',
    'menu_master01d.php',
    'menu_master01e.php',
    'menu_master01f.php',
    'menu_master01g.php',
    'menu_master01h.php',
    'menu_master01i.php',
    'menu_mekanik01.php',
    'menu_mekanik02.php',
    'menu_nominal.php',
    'menu_pelanggan01.php',
    'menu_pelanggan02.php',
    'menu_pembelian01.php',
    'menu_pembelian02.php',
    'menu_pembelian03.php',
    'menu_penjualan01.php',
    'menu_penjualan02.php',
    'menu_penjualan03.php',
    'menu_penjualan04.php',
    'menu_sales.php',
    'menu_servis01.php',
    'menu_servis02.php',
    'menu_servis03.php',
    'menu_stok01.php',
    'menu_stok02.php',
    'menu_stok03.php',
    'menu_stok04.php',
    'menu_supplier.php',
    'menu_user.php'
];

$updated_count = 0;
$error_count = 0;
$log = [];

foreach ($menu_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $log[] = "❌ File tidak ditemukan: $file";
        $error_count++;
        continue;
    }
    
    // Backup file
    $backup_file = $filepath . '.backup_' . date('Y-m-d_H-i-s');
    copy($filepath, $backup_file);
    
    // Baca isi file
    $content = file_get_contents($filepath);
    $original_content = $content;
    $changes_made = false;
    
    // 1. HAPUS menu "Kas Akhir"
    $kas_akhir_pattern = '/\s*<li class="">\s*<a href="kas_akhir\.php">\s*<i class="menu-icon fa fa-caret-right"><\/i>\s*Kas Akhir\s*<\/a>\s*<b class="arrow"><\/b>\s*<\/li>\s*/s';
    if (preg_match($kas_akhir_pattern, $content)) {
        $content = preg_replace($kas_akhir_pattern, '', $content);
        $changes_made = true;
        $log[] = "  ✓ Hapus menu 'Kas Akhir' dari $file";
    }
    
    // 2. HAPUS menu "Jadwal Penjemputan"
    $jadwal_pattern = '/\s*<li class="">\s*<a href="[^"]*jadwal[^"]*penjemputan[^"]*\.php">\s*<i class="menu-icon fa fa-caret-right"><\/i>\s*Jadwal Penjemputan\s*<\/a>\s*<b class="arrow"><\/b>\s*<\/li>\s*/is';
    if (preg_match($jadwal_pattern, $content)) {
        $content = preg_replace($jadwal_pattern, '', $content);
        $changes_made = true;
        $log[] = "  ✓ Hapus menu 'Jadwal Penjemputan' dari $file";
    }
    
    // 3. TAMBAH menu Fast Moves Mapping dan Master Temuan (setelah Keluhan - WO Mapping)
    $wo_mapping_pattern = '/(<li class="">\s*<a href="master-workorder-mapping\.php">\s*<i class="menu-icon fa fa-caret-right"><\/i>\s*Keluhan - WO Mapping\s*<\/a>\s*<b class="arrow"><\/b>\s*<\/li>)/s';
    
    if (preg_match($wo_mapping_pattern, $content)) {
        // Cek apakah menu Fast Moves sudah ada
        if (strpos($content, 'master-fastmoves.php') === false) {
            $new_menus = '$1
									<li class="">
										<a href="master-fastmoves.php">
											<i class="menu-icon fa fa-caret-right"></i>
											Fast Moves Mapping
										</a>
										<b class="arrow"></b>
									</li>
									<li class="">
										<a href="master-keluhan.php">
											<i class="menu-icon fa fa-caret-right"></i>
											Master Temuan
										</a>
										<b class="arrow"></b>
									</li>';
            
            $content = preg_replace($wo_mapping_pattern, $new_menus, $content);
            $changes_made = true;
            $log[] = "  ✓ Tambah menu 'Fast Moves Mapping' dan 'Master Temuan' ke $file";
        } else {
            $log[] = "  ℹ Menu Fast Moves sudah ada di $file";
        }
    }
    
    // Simpan jika ada perubahan
    if ($changes_made && $content !== $original_content) {
        file_put_contents($filepath, $content);
        $updated_count++;
        $log[] = "✅ File berhasil diupdate: $file";
    } else {
        $log[] = "⚪ Tidak ada perubahan di: $file";
        // Hapus backup jika tidak ada perubahan
        unlink($backup_file);
    }
    
    $log[] = ""; // Empty line
}

// Output hasil
echo "<!DOCTYPE html>
<html>
<head>
    <title>Update Menu - Fast Moves & Temuan</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1200px; margin: 0 auto; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .summary { background: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .summary h2 { margin-top: 0; color: #2c3e50; }
        .log { background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 5px; font-size: 13px; line-height: 1.6; max-height: 600px; overflow-y: auto; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { color: #3498db; }
        pre { margin: 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Update Menu - Fast Moves & Temuan</h1>
        
        <div class='summary'>
            <h2>📊 Summary</h2>
            <p><strong>Total File Diproses:</strong> " . count($menu_files) . " files</p>
            <p class='success'><strong>✅ Berhasil Diupdate:</strong> $updated_count files</p>
            <p class='error'><strong>❌ Error:</strong> $error_count files</p>
        </div>
        
        <h2>📝 Detail Log</h2>
        <div class='log'>
            <pre>" . implode("\n", $log) . "</pre>
        </div>
        
        <div style='margin-top: 20px; padding: 15px; background: #d4edda; border-left: 4px solid #28a745; border-radius: 5px;'>
            <h3 style='margin-top: 0; color: #155724;'>✅ Perubahan yang Dilakukan:</h3>
            <ul style='color: #155724;'>
                <li>✅ Hapus menu <strong>\"Kas Akhir\"</strong> dari semua file menu</li>
                <li>✅ Hapus menu <strong>\"Jadwal Penjemputan\"</strong> dari semua file menu</li>
                <li>✅ Tambah menu <strong>\"Fast Moves Mapping\"</strong> ke Data Master → Daftar Item</li>
                <li>✅ Tambah menu <strong>\"Master Temuan\"</strong> ke Data Master → Daftar Item</li>
                <li>✅ Backup file dibuat untuk setiap file yang diupdate</li>
            </ul>
        </div>
        
        <div style='margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;'>
            <h3 style='margin-top: 0; color: #856404;'>⚠️ Catatan:</h3>
            <ul style='color: #856404;'>
                <li>File backup tersimpan dengan format: <code>nama_file.php.backup_YYYY-MM-DD_HH-MM-SS</code></li>
                <li>Jika ada error, restore dari file backup</li>
                <li>Test menu di browser setelah update</li>
                <li>Clear browser cache jika menu tidak berubah</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
