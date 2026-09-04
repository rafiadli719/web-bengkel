<?php
declare(strict_types=1);

class OCRService
{
    private ?string $tesseractBinary;
    private ?string $magickBinary;
    private string $language;
    private string $tempDirectory;

    public function __construct(array $config = [])
    {
        $this->tesseractBinary = $config['tesseract_binary'] ?? $this->resolveBinary('tesseract.exe', [
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
            'C:\\Users\\ACER\\AppData\\Local\\Programs\\Tesseract-OCR\\tesseract.exe',
        ]);
        $this->magickBinary = $config['magick_binary'] ?? $this->resolveBinary('magick.exe', [
            'C:\\Program Files\\ImageMagick-7.1.1-Q16-HDRI\\magick.exe',
            'C:\\Program Files\\ImageMagick-7.1.1-Q16\\magick.exe',
            'C:\\Program Files\\ImageMagick-7.1.0-Q16-HDRI\\magick.exe',
            'C:\\Program Files\\ImageMagick-7.1.0-Q16\\magick.exe',
        ]);
        $this->language = $config['language'] ?? 'ind';
        $this->tempDirectory = $config['temp_directory'] ?? sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'web_kasir_ocr';
    }

    public function isReady(): bool
    {
        return $this->tesseractBinary !== null && $this->magickBinary !== null;
    }

    public function extractFromImage(string $filePath): array
    {
        $this->assertOcrRequirements();

        $preprocessedPath = $this->preprocessImage($filePath);
        try {
            $ocrText = $this->runCommand([
                $this->tesseractBinary,
                $preprocessedPath,
                'stdout',
                '-l',
                $this->language,
                '--psm',
                '6',
            ]);
        } finally {
            if ($preprocessedPath !== $filePath && is_file($preprocessedPath)) {
                @unlink($preprocessedPath);
            }
        }

        return [
            'text' => trim($ocrText),
            'engine' => 'tesseract',
            'language' => $this->language,
        ];
    }

    public function extractFromPdf(string $filePath): array
    {
        $this->assertOcrRequirements();
        $this->ensureDirectory($this->tempDirectory);

        $prefix = $this->tempDirectory . DIRECTORY_SEPARATOR . uniqid('pdf_ocr_', true);
        $outputPattern = $prefix . '-page-%03d.png';

        $this->runCommand([
            $this->magickBinary,
            '-density',
            '220',
            $filePath,
            '-background',
            'white',
            '-alpha',
            'remove',
            '-alpha',
            'off',
            $outputPattern,
        ]);

        $pages = glob($prefix . '-page-*.png') ?: [];
        if (empty($pages)) {
            throw new RuntimeException('OCR gagal: halaman PDF tidak berhasil dikonversi ke gambar.');
        }

        $textParts = [];
        try {
            foreach ($pages as $pagePath) {
                $ocr = $this->extractFromImage($pagePath);
                if ($ocr['text'] !== '') {
                    $textParts[] = $ocr['text'];
                }
            }
        } finally {
            foreach ($pages as $pagePath) {
                if (is_file($pagePath)) {
                    @unlink($pagePath);
                }
            }
        }

        return [
            'text' => trim(implode("\n", $textParts)),
            'engine' => 'tesseract',
            'language' => $this->language,
        ];
    }

    public function getRequirementStatus(): array
    {
        return [
            'tesseract_binary' => $this->tesseractBinary,
            'magick_binary' => $this->magickBinary,
            'ready' => $this->isReady(),
        ];
    }

    private function preprocessImage(string $filePath): string
    {
        $this->ensureDirectory($this->tempDirectory);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'png';
        $outputPath = $this->tempDirectory . DIRECTORY_SEPARATOR . uniqid('ocr_img_', true) . '.' . $extension;

        $this->runCommand([
            $this->magickBinary,
            $filePath,
            '-auto-orient',
            '-colorspace',
            'Gray',
            '-deskew',
            '40%',
            '-normalize',
            '-threshold',
            '60%',
            $outputPath,
        ]);

        return $outputPath;
    }

    private function assertOcrRequirements(): void
    {
        if ($this->tesseractBinary === null) {
            throw new RuntimeException('Tesseract OCR belum terinstall atau tidak ditemukan.');
        }

        if ($this->magickBinary === null) {
            throw new RuntimeException('ImageMagick belum terinstall atau tidak ditemukan.');
        }
    }

    private function runCommand(array $parts): string
    {
        $escaped = array_map(static fn(string $part): string => escapeshellarg($part), $parts);
        $command = implode(' ', $escaped) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(implode("\n", $output)) ?: 'Proses OCR gagal dijalankan.');
        }

        return trim(implode("\n", $output));
    }

    private function resolveBinary(string $binaryName, array $preferredPaths): ?string
    {
        foreach ($preferredPaths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        $globCandidates = glob('C:\\Program Files\\*\\' . $binaryName) ?: [];
        foreach ($globCandidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $whereOutput = [];
        $exitCode = 0;
        exec('where ' . escapeshellarg($binaryName) . ' 2>NUL', $whereOutput, $exitCode);
        if ($exitCode === 0 && !empty($whereOutput[0]) && is_file($whereOutput[0])) {
            return trim($whereOutput[0]);
        }

        return null;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Gagal membuat direktori OCR: ' . $directory);
        }
    }
}
