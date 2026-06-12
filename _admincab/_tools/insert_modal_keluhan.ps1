$ErrorActionPreference = 'Stop'

$File = 'c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\servis-input-reguler.php'
if (-not (Test-Path -LiteralPath $File)) {
    Write-Error "File not found: $File"
    exit 1
}

# Read file
$text = [System.IO.File]::ReadAllText($File)

# If already included, exit quietly
if ($text -match 'modal-tambah-keluhan-baru\.php') {
    Write-Output 'Already included'
    exit 0
}

# Anchor include to insert after
$anchor = "<?php include '_template/modal-fastmoves-part.php'; ?>"
$insert = [System.Environment]::NewLine + "<?php include '_template/modal-tambah-keluhan-baru.php'; ?>"

if ($text.Contains($anchor)) {
    $new = $text.Replace($anchor, $anchor + $insert)
    [System.IO.File]::WriteAllText($File, $new, [System.Text.Encoding]::UTF8)
    Write-Output 'Inserted include OK'
} else {
    Write-Error 'Anchor include not found'
    exit 1
}
