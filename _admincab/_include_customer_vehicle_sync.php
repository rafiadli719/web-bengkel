<?php

function fitmotorEscapeLike($koneksi, $value) {
    return mysqli_real_escape_string($koneksi, str_replace(['%', '_'], ['\\%', '\\_'], (string) $value));
}

function fitmotorFindLatestCustomerCodeByPolice($koneksi, $noPolisi) {
    $noPolisi = trim((string) $noPolisi);
    if ($noPolisi === '') {
        return '';
    }

    $sql = "SELECT no_pelanggan
            FROM tblservice
            WHERE no_polisi = ?
              AND no_pelanggan IS NOT NULL
              AND no_pelanggan <> ''
            ORDER BY tanggal DESC, jam DESC, no_service DESC
            LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 's', $noPolisi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row['no_pelanggan'] ?? '';
}

function fitmotorGetCustomerById($koneksi, $noPelanggan) {
    $noPelanggan = trim((string) $noPelanggan);
    if ($noPelanggan === '') {
        return null;
    }

    $sql = "SELECT p.*, pg.grup, sp.status_member, sp.diskon_persen
            FROM tblpelanggan p
            LEFT JOIN tblpelanggangrup pg ON pg.kgrup = p.kgrup
            LEFT JOIN statistik_pelanggan sp ON sp.no_pelanggan = p.nopelanggan
            WHERE p.nopelanggan = ?
            LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 's', $noPelanggan);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function fitmotorGetVehicleByPolice($koneksi, $noPolisi) {
    $noPolisi = trim((string) $noPolisi);
    if ($noPolisi === '') {
        return null;
    }

    $sql = "SELECT k.*, pm.merek
            FROM tblkendaraan k
            LEFT JOIN tbpabrik_motor pm ON pm.id = k.kode_merek
            WHERE k.nopolisi = ?
            LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 's', $noPolisi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function fitmotorFindCustomerByVehicleOwner($koneksi, $ownerName) {
    $ownerName = trim((string) $ownerName);
    if ($ownerName === '') {
        return null;
    }

    $sql = "SELECT p.*, pg.grup, sp.status_member, sp.diskon_persen
            FROM tblpelanggan p
            LEFT JOIN tblpelanggangrup pg ON pg.kgrup = p.kgrup
            LEFT JOIN statistik_pelanggan sp ON sp.no_pelanggan = p.nopelanggan
            WHERE UPPER(TRIM(p.namapelanggan)) = UPPER(TRIM(?))
            ORDER BY p.nopelanggan ASC
            LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 's', $ownerName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($row) {
        return $row;
    }

    $like = '%' . fitmotorEscapeLike($koneksi, $ownerName) . '%';
    $sql = "SELECT p.*, pg.grup, sp.status_member, sp.diskon_persen
            FROM tblpelanggan p
            LEFT JOIN tblpelanggangrup pg ON pg.kgrup = p.kgrup
            LEFT JOIN statistik_pelanggan sp ON sp.no_pelanggan = p.nopelanggan
            WHERE p.namapelanggan LIKE ?
            ORDER BY p.nopelanggan ASC
            LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, 's', $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $row ?: null;
}

function fitmotorGetCustomerVehicleBundle($koneksi, $noPolisi, $explicitCustomerCode = '') {
    $vehicle = fitmotorGetVehicleByPolice($koneksi, $noPolisi);
    $customer = null;

    $candidateCodes = [];
    $explicitCustomerCode = trim((string) $explicitCustomerCode);
    if ($explicitCustomerCode !== '') {
        $candidateCodes[] = $explicitCustomerCode;
    }
    $candidateCodes[] = fitmotorFindLatestCustomerCodeByPolice($koneksi, $noPolisi);
    $candidateCodes[] = trim((string) $noPolisi);
    $candidateCodes = array_values(array_unique(array_filter($candidateCodes)));

    foreach ($candidateCodes as $customerCode) {
        $customer = fitmotorGetCustomerById($koneksi, $customerCode);
        if ($customer) {
            break;
        }
    }

    if (!$customer && $vehicle && !empty($vehicle['pemilik'])) {
        $customer = fitmotorFindCustomerByVehicleOwner($koneksi, $vehicle['pemilik']);
    }

    return [
        'vehicle' => $vehicle,
        'customer' => $customer,
        'mapped_customer_code' => $customer['nopelanggan'] ?? ($explicitCustomerCode !== '' ? $explicitCustomerCode : ''),
    ];
}

function fitmotorFindCustomerForService($koneksi, $noPelanggan, $noPolisi = '') {
    $customer = fitmotorGetCustomerById($koneksi, $noPelanggan);
    if ($customer) {
        return $customer;
    }

    if (trim((string) $noPolisi) !== '') {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $noPolisi, $noPelanggan);
        if (!empty($bundle['customer'])) {
            return $bundle['customer'];
        }
    }

    return null;
}
