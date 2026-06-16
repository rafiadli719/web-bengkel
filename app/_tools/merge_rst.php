<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$basePath = 'C:/xampp/htdocs/web-bengkel/aplikasi/aplikasi/_admincab/';

// Read original RST file
$originalFile = $basePath . 'servis-input-reguler-rst.php';
$htmlTemplate = $basePath . 'temp_html_rst.txt';
$outputFile = $basePath . 'servis-input-reguler-rst.php';

$originalContent = file_get_contents($originalFile);
$htmlContent = file_get_contents($htmlTemplate);

if ($originalContent === false) {
    die("Error: Cannot read original file\n");
}
if ($htmlContent === false) {
    die("Error: Cannot read HTML template\n");
}

$lines = explode("\n", $originalContent);

// Find PHP closing tag line (the first ?> that's on its own line before HTML)
$phpEndLine = -1;
for ($i = 0; $i < count($lines); $i++) {
    if (trim($lines[$i]) === '?>') {
        // Check if next non-empty line has <!DOCTYPE or is empty before HTML
        for ($j = $i + 1; $j < min($i + 3, count($lines)); $j++) {
            $nextLine = trim($lines[$j]);
            if ($nextLine === '' || strpos($nextLine, '<!DOCTYPE') === 0) {
                $phpEndLine = $i;
                break 2;
            }
        }
    }
}

if ($phpEndLine === -1) {
    die("Error: Could not find PHP end tag\n");
}

echo "PHP section ends at line: " . ($phpEndLine + 1) . "\n";

// Extract PHP section (lines 0 to phpEndLine inclusive with ?>)
$phpSection = array_slice($lines, 0, $phpEndLine + 1);
$phpContent = implode("\n", $phpSection);

// Combine PHP section + blank line + HTML template
$newContent = $phpContent . "\n\n" . $htmlContent;

// Write to output file
$bytesWritten = file_put_contents($outputFile, $newContent);

if ($bytesWritten === false) {
    die("Error: Could not write output file\n");
}

echo "Merge completed!\n";
echo "PHP lines: " . count($phpSection) . "\n";
echo "Bytes written: " . $bytesWritten . "\n";

// Check syntax
exec('php -l "' . $outputFile . '" 2>&1', $output, $returnCode);
echo "Syntax check (return code: $returnCode):\n";
echo implode("\n", $output) . "\n";
