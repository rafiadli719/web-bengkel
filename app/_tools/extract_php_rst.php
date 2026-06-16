<?php
$content = file_get_contents('C:/xampp/htdocs/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler-rst.php');
$lines = explode("\n", $content);

// Find line with standalone ?> that ends PHP section
$phpEndLine = 0;
for ($i = 0; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    if ($line === '?>') {
        // Check if next line starts HTML
        if (isset($lines[$i+1])) {
            $nextLine = trim($lines[$i+1]);
            if ($nextLine === '' || strpos($nextLine, '<!DOCTYPE') === 0 || strpos($nextLine, '<html') === 0) {
                $phpEndLine = $i;
                break;
            }
        }
    }
}

echo 'PHP section ends at line: ' . ($phpEndLine + 1) . PHP_EOL;

// Extract PHP section (lines 0 to phpEndLine inclusive)
$phpSection = array_slice($lines, 0, $phpEndLine + 1);
file_put_contents('C:/xampp/htdocs/web-bengkel/aplikasi/aplikasi/_admincab/temp_php_section_rst.txt', implode("\n", $phpSection));

echo 'PHP section saved to temp_php_section_rst.txt' . PHP_EOL;
echo 'Lines: ' . count($phpSection) . PHP_EOL;
