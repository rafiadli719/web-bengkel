<?php
declare(strict_types=1);

class ExtractorService
{
    /**
     * @var string[]
     */
    private array $bankKeywords = ['BCA', 'BRI', 'MANDIRI', 'BNI'];

    /**
     * @var string[]
     */
    private array $senderKeywords = [
        'DARI',
        'PENGIRIM',
        'REKENING ASAL',
        'REK ASAL',
        'REKENING PENGIRIM',
        'REK PENGIRIM',
        'NO REK PENGIRIM',
        'NO REKENING PENGIRIM',
        'ACCOUNT NUMBER SENDER',
        'FROM ACCOUNT',
        'DEBIT ACCOUNT',
    ];

    /**
     * @var string[]
     */
    private array $receiverKeywords = [
        'PENERIMA',
        'TRANSFER KE',
        'KE REKENING',
        'REKENING TUJUAN',
        'REK TUJUAN',
        'REKENING PENERIMA',
        'REK PENERIMA',
        'NO REK PENERIMA',
        'NO REKENING PENERIMA',
        'ACCOUNT NUMBER',
        'ACCOUNT NUMBER BENEFICIARY',
        'TO ACCOUNT',
        'CREDIT ACCOUNT',
    ];

    public function extract(string $rawText): array
    {
        $normalizedText = $this->normalizeText($rawText);
        $lines = $this->prepareLines($normalizedText);

        $sender = $this->extractAccountByPatterns(
            $normalizedText,
            $lines,
            [
                '/\b(dari|pengirim)\b.*?(\d{10,16})/i',
                '/\b(rekening asal|rek asal|rekening pengirim|rek pengirim|no\.?\s*rek(?:ening)? pengirim|from account|debit account)\b.*?(\d{10,16})/i',
            ],
            $this->senderKeywords
        );

        $receiver = $this->extractAccountByPatterns(
            $normalizedText,
            $lines,
            [
                '/\b(ke|penerima)\b.*?(\d{10,16})/i',
                '/\b(transfer ke|ke rekening|rekening tujuan|rek tujuan|rekening penerima|rek penerima|no\.?\s*rek(?:ening)? penerima|account number|to account|credit account)\b.*?(\d{10,16})/i',
            ],
            $this->receiverKeywords
        );

        $nominal = $this->extractNominal($normalizedText, $lines);
        $detectedBank = $this->detectBank($normalizedText);

        $validationErrors = [];
        if (!$this->isValidAccount($sender['value'])) {
            $validationErrors[] = 'Nomor rekening pengirim tidak valid.';
            $sender['value'] = null;
        }

        if (!$this->isValidAccount($receiver['value'])) {
            $validationErrors[] = 'Nomor rekening penerima tidak valid.';
            $receiver['value'] = null;
        }

        if ($sender['value'] !== null && $receiver['value'] !== null && $sender['value'] === $receiver['value']) {
            $validationErrors[] = 'Nomor rekening pengirim dan penerima tidak boleh sama.';
        }

        if ($nominal['value'] === null || $nominal['value'] <= 1000) {
            $validationErrors[] = 'Nominal transfer tidak valid atau terlalu kecil.';
            $nominal['value'] = null;
        }

        $confidence = $this->resolveConfidence($sender['source'], $receiver['source'], $nominal['source'], $validationErrors);

        return [
            'pengirim' => $sender['value'],
            'penerima' => $receiver['value'],
            'nominal' => $nominal['value'] !== null ? (string)$nominal['value'] : null,
            'confidence' => $confidence,
            'bank' => $detectedBank,
            'normalized_text' => $normalizedText,
            'match_meta' => [
                'pengirim_source' => $sender['source'],
                'penerima_source' => $receiver['source'],
                'nominal_source' => $nominal['source'],
                'bank' => $detectedBank,
            ],
            'validation_errors' => $validationErrors,
        ];
    }

    public function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', ' ', $text) ?? $text;
        $text = str_replace(['|', '\\', '/', ':', ';'], ' ', $text);
        $text = preg_replace('/(?<=\d)[\s\-_.]+(?=\d)/', '', $text) ?? $text;
        $text = $this->fixCommonOcrErrors($text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{2,}/", "\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @return array{value:?string,source:string}
     */
    private function extractAccountByPatterns(string $normalizedText, array $lines, array $patterns, array $keywords): array
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalizedText, $matches)) {
                $candidate = $this->sanitizeAccountNumber($matches[2] ?? '');
                if ($this->isValidAccount($candidate)) {
                    return ['value' => $candidate, 'source' => 'regex'];
                }
            }
        }

        $scoredCandidate = $this->extractAccountFromKeywordWindows($lines, $keywords);
        if ($this->isValidAccount($scoredCandidate)) {
            return ['value' => $scoredCandidate, 'source' => 'keyword-window'];
        }

        $fallback = $this->extractFallbackAccounts($normalizedText);
        if (!empty($fallback)) {
            return ['value' => $fallback[0], 'source' => 'fallback'];
        }

        return ['value' => null, 'source' => 'missing'];
    }

    /**
     * @return array{value:?int,source:string}
     */
    private function extractNominal(string $normalizedText, array $lines): array
    {
        if (preg_match('/Rp\s?([\d.,]+)/i', $normalizedText, $matches)) {
            return [
                'value' => $this->sanitizeNominal($matches[1] ?? ''),
                'source' => 'regex',
            ];
        }

        foreach ($lines as $line) {
            if (preg_match('/(nominal|jumlah|total|transfer).*?Rp?\s*([\d.,]+)/i', $line, $matches)) {
                return [
                    'value' => $this->sanitizeNominal($matches[2] ?? ''),
                    'source' => 'keyword-window',
                ];
            }
        }

        return ['value' => null, 'source' => 'missing'];
    }

    private function detectBank(string $normalizedText): ?string
    {
        $upper = strtoupper($normalizedText);
        foreach ($this->bankKeywords as $bank) {
            if (strpos($upper, $bank) !== false) {
                return $bank;
            }
        }

        return null;
    }

    /**
     * @param string[] $lines
     */
    private function extractAccountFromKeywordWindows(array $lines, array $keywords): ?string
    {
        $bestValue = null;
        $bestScore = -1;

        foreach ($lines as $index => $line) {
            $window = trim(
                ($lines[$index - 1] ?? '') . ' ' .
                $line . ' ' .
                ($lines[$index + 1] ?? '')
            );

            $score = $this->scoreKeywordWindow($window, $keywords);
            if ($score <= 0) {
                continue;
            }

            foreach ($this->extractFallbackAccounts($window) as $candidate) {
                $candidateScore = $score;
                if (preg_match('/' . preg_quote($candidate, '/') . '/', $line)) {
                    $candidateScore += 4;
                }

                if ($candidateScore > $bestScore) {
                    $bestScore = $candidateScore;
                    $bestValue = $candidate;
                }
            }
        }

        return $bestValue;
    }

    /**
     * @return string[]
     */
    private function extractFallbackAccounts(string $text): array
    {
        preg_match_all('/(?<!\d)(?:\d[\d\s.\-]{8,20}\d)(?!\d)/', $text, $matches);
        $accounts = [];
        foreach ($matches[0] ?? [] as $match) {
            $candidate = $this->sanitizeAccountNumber($match);
            if ($this->isValidAccount($candidate)) {
                $accounts[$candidate] = $candidate;
            }
        }

        return array_values($accounts);
    }

    private function sanitizeAccountNumber(string $value): ?string
    {
        $value = preg_replace('/\D+/', '', $value) ?? '';
        if (!$this->isValidAccount($value)) {
            return null;
        }

        return $value;
    }

    private function sanitizeNominal(string $value): ?int
    {
        $value = preg_replace('/[^\d]/', '', $value) ?? '';
        if ($value === '') {
            return null;
        }

        return (int)$value;
    }

    private function isValidAccount(?string $value): bool
    {
        return $value !== null && preg_match('/^\d{10,16}$/', $value) === 1;
    }

    private function resolveConfidence(string $senderSource, string $receiverSource, string $nominalSource, array $validationErrors): string
    {
        if (!empty($validationErrors)) {
            return 'low';
        }

        if ($senderSource === 'regex' && $receiverSource === 'regex' && $nominalSource === 'regex') {
            return 'high';
        }

        if ($senderSource !== 'missing' && $receiverSource !== 'missing' && $nominalSource !== 'missing') {
            return 'medium';
        }

        return 'low';
    }

    private function fixCommonOcrErrors(string $text): string
    {
        return preg_replace_callback('/[A-Z0-9]{6,}/i', static function (array $matches): string {
            $token = $matches[0];

            if (preg_match('/\d/', $token) !== 1) {
                return $token;
            }

            return strtr($token, [
                'O' => '0',
                'o' => '0',
                'I' => '1',
                'l' => '1',
                'S' => '5',
            ]);
        }, $text) ?? $text;
    }

    /**
     * @return string[]
     */
    private function prepareLines(string $text): array
    {
        $lines = array_filter(array_map(static fn(string $line): string => trim($line), explode("\n", $text)));
        return array_values($lines);
    }

    /**
     * @param string[] $keywords
     */
    private function scoreKeywordWindow(string $window, array $keywords): int
    {
        $upperWindow = strtoupper($window);
        $score = 0;

        foreach ($keywords as $keyword) {
            if (strpos($upperWindow, strtoupper($keyword)) !== false) {
                $score += 8;
            }
        }

        foreach ($this->bankKeywords as $bank) {
            if (strpos($upperWindow, $bank) !== false) {
                $score += 2;
            }
        }

        if (preg_match('/NO\.?\s*REK|REKENING|ACCOUNT NUMBER/i', $window)) {
            $score += 3;
        }

        return $score;
    }
}
