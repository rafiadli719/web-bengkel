<?php
declare(strict_types=1);

class DocumentReader
{
    public function __construct(
        private ?ParserService $parserService = null,
        private ?OCRService $ocrService = null,
        private ?ExtractorService $extractorService = null
    ) {
        $this->parserService ??= new ParserService();
        $this->ocrService ??= new OCRService();
        $this->extractorService ??= new ExtractorService();
    }

    public function read(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new InvalidArgumentException('File dokumen tidak ditemukan.');
        }

        $fileType = $this->detectFileType($filePath);
        $rawText = '';
        $ocrUsed = false;
        $notes = [];

        switch ($fileType) {
            case 'pdf':
                $rawText = $this->parserService->extractFromPdf($filePath);
                if ($this->shouldFallbackToOcr($rawText)) {
                    $notes[] = 'Text PDF kosong atau kurang lengkap, dialihkan ke OCR.';
                    $ocrResult = $this->ocrService->extractFromPdf($filePath);
                    $rawText = $ocrResult['text'];
                    $ocrUsed = true;
                }
                break;

            case 'docx':
                $rawText = $this->parserService->extractFromDocx($filePath);
                break;

            case 'jpg':
            case 'jpeg':
            case 'png':
                $ocrResult = $this->ocrService->extractFromImage($filePath);
                $rawText = $ocrResult['text'];
                $ocrUsed = true;
                break;

            default:
                throw new RuntimeException('Tipe file tidak didukung: ' . $fileType);
        }

        $extracted = $this->extractorService->extract($rawText);

        return array_merge($extracted, [
            'file_type' => $fileType,
            'ocr_used' => $ocrUsed,
            'raw_text' => $rawText,
            'notes' => $notes,
            'ocr_status' => $this->ocrService->getRequirementStatus(),
        ]);
    }

    public function detectFileType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $supported = ['pdf', 'docx', 'jpg', 'jpeg', 'png'];

        if (!in_array($extension, $supported, true)) {
            throw new RuntimeException('Hanya file PDF, DOCX, JPG, JPEG, dan PNG yang didukung.');
        }

        return $extension;
    }

    private function shouldFallbackToOcr(string $text): bool
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($clean === '' || mb_strlen($clean) < 40) {
            return true;
        }

        return preg_match('/\d{10,16}/', $clean) !== 1 && preg_match('/Rp\s?[\d.,]+/i', $clean) !== 1;
    }
}
