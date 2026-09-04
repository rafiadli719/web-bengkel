<?php
declare(strict_types=1);

use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class ParserService
{
    public function extractFromPdf(string $filePath): string
    {
        $parser = new Parser();
        $document = $parser->parseFile($filePath);
        $text = $document->getText();

        return $this->normalizeWhitespace($text);
    }

    public function extractFromDocx(string $filePath): string
    {
        $textParts = [];

        try {
            $phpWord = IOFactory::load($filePath);
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = $this->extractElementText($element);
                    if ($text !== '') {
                        $textParts[] = $text;
                    }
                }
            }
        } catch (Throwable $exception) {
            $textParts = [];
        }

        $text = $this->normalizeWhitespace(implode("\n", $textParts));
        if ($text !== '') {
            return $text;
        }

        return $this->extractDocxXmlFallback($filePath);
    }

    private function extractElementText(mixed $element): string
    {
        if ($element instanceof Text) {
            return trim((string)$element->getText());
        }

        if ($element instanceof TextBreak) {
            return "\n";
        }

        if ($element instanceof TextRun || $element instanceof ListItemRun) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $childText = $this->extractElementText($child);
                if ($childText !== '') {
                    $parts[] = $childText;
                }
            }

            return trim(implode(' ', $parts));
        }

        if ($element instanceof Table) {
            $rows = [];
            foreach ($element->getRows() as $row) {
                $cells = [];
                foreach ($row->getCells() as $cell) {
                    $cellText = $this->extractCellText($cell);
                    if ($cellText !== '') {
                        $cells[] = $cellText;
                    }
                }
                if (!empty($cells)) {
                    $rows[] = implode(' | ', $cells);
                }
            }

            return implode("\n", $rows);
        }

        if (method_exists($element, 'getText')) {
            return trim((string)$element->getText());
        }

        if (method_exists($element, 'getElements')) {
            $parts = [];
            foreach ($element->getElements() as $child) {
                $childText = $this->extractElementText($child);
                if ($childText !== '') {
                    $parts[] = $childText;
                }
            }

            return trim(implode(' ', $parts));
        }

        return '';
    }

    private function extractCellText(Cell $cell): string
    {
        $parts = [];
        foreach ($cell->getElements() as $element) {
            $text = $this->extractElementText($element);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return trim(implode(' ', $parts));
    }

    private function extractDocxXmlFallback(string $filePath): string
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($xml === '') {
            return '';
        }

        $text = strip_tags(str_replace(['</w:p>', '</w:tr>', '</w:tc>'], ["\n", "\n", ' '], $xml));
        return $this->normalizeWhitespace(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{2,}/", "\n", $text) ?? $text;

        return trim($text);
    }
}
