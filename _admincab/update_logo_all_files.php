<?php
/**
 * Script untuk mengganti semua logo hardcoded dengan logo dinamis
 * JALANKAN SEKALI SAJA!
 */

// Pattern yang akan diganti
$patterns = [
    // Pattern 1: Icon dengan subtitel di bawahnya
    [
        'old' => '<i class="fa fa-leaf"></i>
							<?php include "../lib/subtitel.php"; ?>',
        'new' => '<?php include "../lib/logo.php"; ?>
							<?php include "../lib/subtitel.php"; ?>'
    ],
    // Pattern 2: Icon dengan subtitel (spasi berbeda)
    [
        'old' => '<i class="fa fa-leaf"></i>
                                    <?php include "../lib/subtitel.php"; ?>',
        'new' => '<?php include "../lib/logo.php"; ?>
                                    <?php include "../lib/subtitel.php"; ?>'
    ],
    // Pattern 3: Icon dengan subtitel (inline)
    [
        'old' => '<i class="fa fa-leaf"></i> <?php include "../lib/subtitel.php"; ?>',
        'new' => '<?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?>'
    ],
    // Pattern 4: Hardcoded text
    [
        'old' => '<small><i class="fa fa-leaf"></i> Bengkel System</small>',
        'new' => '<small><?php include "../lib/logo.php"; ?> <?php include "../lib/subtitel.php"; ?></small>'
    ],
    // Pattern 5: Icon dengan subtitel (tab berbeda)
    [
        'old' => '<i class="fa fa-leaf"></i>
						<?php include "../lib/subtitel.php"; ?>',
        'new' => '<?php include "../lib/logo.php"; ?>
						<?php include "../lib/subtitel.php"; ?>'
    ],
    // Pattern 6: Icon dengan subtitel (spasi 4)
    [
        'old' => '<i class="fa fa-leaf"></i>
                        <?php include "../lib/subtitel.php"; ?>',
        'new' => '<?php include "../lib/logo.php"; ?>
                        <?php include "../lib/subtitel.php"; ?>'
    ]
];

// Direktori yang akan diproses
$directory = __DIR__;

// Ambil semua file PHP
$files = glob($directory . '/*.php');

$updated_files = [];
$skipped_files = ['update_logo_all_files.php']; // Skip file ini sendiri

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip file ini sendiri
    if (in_array($filename, $skipped_files)) {
        continue;
    }
    
    // Baca isi file
    $content = file_get_contents($file);
    $original_content = $content;
    $changed = false;
    
    // Coba semua pattern
    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern['old']) !== false) {
            $content = str_replace($pattern['old'], $pattern['new'], $content);
            $changed = true;
        }
    }
    
    // Jika ada perubahan, simpan file
    if ($changed && $content !== $original_content) {
        file_put_contents($file, $content);
        $updated_files[] = $filename;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Logo - Result</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .file-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .file-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .file-list li {
            padding: 5px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Update Logo - Hasil Eksekusi</h1>
        
        <?php if (count($updated_files) > 0): ?>
            <div class="success">
                <strong>✅ Berhasil!</strong><br>
                Total <strong><?php echo count($updated_files); ?> file</strong> telah diupdate dengan logo dinamis.
            </div>
            
            <div class="file-list">
                <h3>📄 Daftar File yang Diupdate:</h3>
                <ul>
                    <?php foreach ($updated_files as $file): ?>
                        <li><?php echo htmlspecialchars($file); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>ℹ️ Info</strong><br>
                Tidak ada file yang perlu diupdate. Semua file sudah menggunakan logo dinamis.
            </div>
        <?php endif; ?>
        
        <div class="warning">
            <strong>⚠️ Penting:</strong><br>
            Script ini hanya boleh dijalankan SEKALI. Setelah selesai, sebaiknya hapus atau rename file ini untuk keamanan.
        </div>
        
        <a href="index.php" class="btn">← Kembali ke Dashboard</a>
    </div>
</body>
</html>
