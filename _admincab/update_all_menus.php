<?php
/**
 * SCRIPT UPDATE SEMUA MENU AGAR KONSISTEN
 * ========================================
 * Script untuk mengupdate semua file menu agar menggunakan template standar yang konsisten
 * Dibuat: 13 Oktober 2025
 */

// Daftar semua file menu yang perlu diupdate
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

// Baca template standar
$template_path = '_template/menu_template_standard.php';
if (!file_exists($template_path)) {
    die("❌ Template file tidak ditemukan: $template_path\n");
}

$template_content = file_get_contents($template_path);
if ($template_content === false) {
    die("❌ Gagal membaca template file\n");
}

echo "🚀 MEMULAI UPDATE SEMUA FILE MENU\n";
echo "================================\n\n";

$success_count = 0;
$error_count = 0;
$backup_dir = 'menu_backups_' . date('Y-m-d_H-i-s');

// Buat direktori backup
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    echo "📁 Direktori backup dibuat: $backup_dir\n\n";
}

foreach ($menu_files as $menu_file) {
    echo "🔄 Processing: $menu_file\n";
    
    if (!file_exists($menu_file)) {
        echo "   ⚠️  File tidak ditemukan, skip...\n\n";
        continue;
    }
    
    try {
        // Backup file asli
        $backup_path = "$backup_dir/$menu_file";
        if (copy($menu_file, $backup_path)) {
            echo "   💾 Backup dibuat: $backup_path\n";
        } else {
            echo "   ⚠️  Gagal membuat backup\n";
        }
        
        // Baca file asli untuk mendapatkan class active
        $original_content = file_get_contents($menu_file);
        
        // Tentukan menu mana yang harus active berdasarkan nama file
        $active_menu = determineActiveMenu($menu_file);
        
        // Update template dengan class active yang sesuai
        $updated_content = updateActiveClasses($template_content, $active_menu);
        
        // Tulis file yang sudah diupdate
        if (file_put_contents($menu_file, $updated_content) !== false) {
            echo "   ✅ Berhasil diupdate\n";
            $success_count++;
        } else {
            echo "   ❌ Gagal menulis file\n";
            $error_count++;
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        $error_count++;
    }
    
    echo "\n";
}

echo "📊 HASIL UPDATE:\n";
echo "================\n";
echo "✅ Berhasil: $success_count file\n";
echo "❌ Gagal: $error_count file\n";
echo "📁 Backup disimpan di: $backup_dir\n\n";

if ($success_count > 0) {
    echo "🎉 UPDATE SELESAI! Semua menu sekarang konsisten.\n";
    echo "\n📋 FITUR BARU YANG DITAMBAHKAN:\n";
    echo "- ✅ Master Tarif Jemput Antar (di submenu Mekanik)\n";
    echo "- ✅ Master Kepala Mekanik (di submenu Mekanik)\n";
    echo "- ✅ Input Kepala Mekanik Harian (di submenu Servis Reguler)\n";
    echo "- ✅ Servis Jemput Antar (submenu baru)\n";
    echo "- ✅ Jadwal Penjemputan\n";
    echo "- ✅ Tracking Keluhan\n";
} else {
    echo "⚠️  Tidak ada file yang berhasil diupdate.\n";
}

/**
 * Menentukan menu mana yang harus active berdasarkan nama file
 */
function determineActiveMenu($filename) {
    if (strpos($filename, 'master') !== false) {
        return 'master';
    } elseif (strpos($filename, 'servis') !== false) {
        return 'servis';
    } elseif (strpos($filename, 'pembelian') !== false) {
        return 'pembelian';
    } elseif (strpos($filename, 'penjualan') !== false) {
        return 'penjualan';
    } elseif (strpos($filename, 'laporan') !== false) {
        return 'laporan';
    } elseif (strpos($filename, 'stok') !== false) {
        return 'stok';
    } elseif (strpos($filename, 'kasir') !== false) {
        return 'kasir';
    } elseif (strpos($filename, 'antarcab') !== false) {
        return 'antarcab';
    } elseif (strpos($filename, 'dashboard') !== false) {
        return 'dashboard';
    }
    
    return 'master'; // default
}

/**
 * Update class active pada template sesuai dengan menu yang dipilih
 */
function updateActiveClasses($content, $active_menu) {
    // Reset semua class active
    $content = str_replace('class="active open"', 'class=""', $content);
    $content = str_replace('class="active"', 'class=""', $content);
    
    // Set active sesuai menu
    switch ($active_menu) {
        case 'dashboard':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="index.php">',
                '<li class="active">\n\t\t\t\t\t\t<a href="index.php">',
                $content
            );
            break;
            
        case 'master':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-cog"></i>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-cog"></i>',
                $content
            );
            break;
            
        case 'servis':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text">\n\t\t\t\t\t\t\t\tServis\n\t\t\t\t\t\t\t</span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text">\n\t\t\t\t\t\t\t\tServis\n\t\t\t\t\t\t\t</span>',
                $content
            );
            break;
            
        case 'pembelian':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Pembelian </span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Pembelian </span>',
                $content
            );
            break;
            
        case 'penjualan':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Penjualan </span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Penjualan </span>',
                $content
            );
            break;
            
        case 'laporan':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-print"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Laporan </span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-print"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Laporan </span>',
                $content
            );
            break;
            
        case 'stok':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text">\n\t\t\t\t\t\t\t\tPenyesuaian Stok\n\t\t\t\t\t\t\t</span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text">\n\t\t\t\t\t\t\t\tPenyesuaian Stok\n\t\t\t\t\t\t\t</span>',
                $content
            );
            break;
            
        case 'antarcab':
            $content = str_replace(
                '<li class="">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Antar Cabang </span>',
                '<li class="active open">\n\t\t\t\t\t\t<a href="#" class="dropdown-toggle">\n\t\t\t\t\t\t\t<i class="menu-icon fa fa-list"></i>\n\t\t\t\t\t\t\t<span class="menu-text"> Antar Cabang </span>',
                $content
            );
            break;
    }
    
    return $content;
}

?>
