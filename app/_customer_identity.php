<?php

function fitmotorNormalizePhoneDigits($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        return '';
    }

    if (strpos($digits, '62') === 0) {
        return $digits;
    }

    if (strpos($digits, '0') === 0) {
        return '62' . substr($digits, 1);
    }

    return $digits;
}

function fitmotorPhoneVariants($phone)
{
    $raw = trim((string) $phone);
    $normalized = fitmotorNormalizePhoneDigits($raw);
    $variants = [];

    if ($raw !== '') {
        $variants[] = $raw;
    }

    if ($normalized !== '') {
        $variants[] = $normalized;
        if (strpos($normalized, '62') === 0) {
            $variants[] = '0' . substr($normalized, 2);
            $variants[] = '+' . $normalized;
        }
    }

    return array_values(array_unique(array_filter($variants)));
}

function fitmotorFindCustomerCodesByPhone($koneksi, $phone)
{
    $variants = fitmotorPhoneVariants($phone);
    if (count($variants) === 0) {
        return [];
    }

    $parts = [];
    $types = '';
    $params = [];
    foreach ($variants as $variant) {
        $parts[] = '(telephone = ? OR no_wa = ? OR notlp = ?)';
        $types .= 'sss';
        $params[] = $variant;
        $params[] = $variant;
        $params[] = $variant;
    }

    $sql = 'SELECT DISTINCT nopelanggan FROM tblpelanggan WHERE ' . implode(' OR ', $parts) . ' ORDER BY nopelanggan ASC';
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $codes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        if (!empty($row['nopelanggan'])) {
            $codes[] = $row['nopelanggan'];
        }
    }

    mysqli_stmt_close($stmt);
    return array_values(array_unique($codes));
}

function fitmotorResolveCustomerCodeByPhone($koneksi, $phone)
{
    $codes = fitmotorFindCustomerCodesByPhone($koneksi, $phone);
    if (count($codes) === 1) {
        return ['status' => 'existing', 'code' => $codes[0], 'matches' => $codes];
    }

    if (count($codes) > 1) {
        return ['status' => 'ambiguous', 'code' => '', 'matches' => $codes];
    }

    return ['status' => 'new', 'code' => '', 'matches' => []];
}

function fitmotorGenerateCustomerCode($koneksi)
{
    $prefix = 'CST' . date('ymd');
    $like = $prefix . '%';
    $sql = "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan LIKE ? ORDER BY nopelanggan DESC LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row && !empty($row['nopelanggan'])) {
            $lastNumber = (int) substr($row['nopelanggan'], -4);
            return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        }
    }

    return $prefix . '0001';
}

