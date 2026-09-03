<?php

function getUserKodeCabang(PDO $pdo, string $kodeKaryawan): ?string
{
    // Return cabang_ref_kode (bukan tbuser.kode_cabang teks) — itu yang dipakai
    // kolom kode_cabang di kasir_transactions_closing_kasir (FK ke tbcabang.cabang_ref_kode).
    $stmt = $pdo->prepare(
        "SELECT c.cabang_ref_kode FROM tbuser u
         JOIN tbcabang c ON c.kode_cabang = u.kode_cabang
         WHERE u.kode_karyawan = ? LIMIT 1"
    );
    $stmt->execute([$kodeKaryawan]);
    $kodeCabang = $stmt->fetchColumn();

    return $kodeCabang !== false ? (string) $kodeCabang : null;
}

function getTransactionByCode(PDO $pdo, string $kodeTransaksi): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM kasir_transactions_closing_kasir WHERE kode_transaksi = ? LIMIT 1");
    $stmt->execute([$kodeTransaksi]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    return $transaction ?: null;
}

function userCanAccessTransaction(PDO $pdo, array $session, array $transaction): bool
{
    if (!isset($session['kode_karyawan'], $session['role'])) {
        return false;
    }

    if (!in_array($session['role'], ['kasir', 'admin', 'super_admin'], true)) {
        return false;
    }

    if ($session['role'] === 'super_admin') {
        return true;
    }

    $sessionKode = (string) $session['kode_karyawan'];
    $transactionOwner = (string) ($transaction['kode_karyawan'] ?? '');
    $transactionKasirAsal = (string) ($transaction['kasir_asal'] ?? '');

    if ($sessionKode !== '' && ($sessionKode === $transactionOwner || $sessionKode === $transactionKasirAsal)) {
        return true;
    }

    if ($session['role'] === 'admin') {
        $userCabang = getUserKodeCabang($pdo, $sessionKode);
        return $userCabang !== null && $userCabang === ($transaction['kode_cabang'] ?? null);
    }

    return false;
}

function canTransactionBeRevised(array $transaction, ?string &$reason = null): bool
{
    $status = (string) ($transaction['status'] ?? '');
    if ($status !== 'end proses') {
        $reason = 'Hanya transaksi dengan status end proses yang bisa diajukan revisi.';
        return false;
    }

    if (!empty($transaction['revision_child_kode'])) {
        $reason = 'Transaksi ini sudah memiliki transaksi revisi.';
        return false;
    }

    $depositStatus = trim((string) ($transaction['deposit_status'] ?? ''));
    $allowedDepositStatuses = ['', 'Belum Disetor', 'Dikembalikan ke CS'];
    if (!in_array($depositStatus, $allowedDepositStatuses, true)) {
        $reason = 'Transaksi ini sudah masuk proses lanjutan (' . $depositStatus . ') sehingga revisi harus dihentikan.';
        return false;
    }

    if (!empty($transaction['kode_setoran'])) {
        $reason = 'Transaksi ini sudah terkait ke setoran sehingga tidak bisa direvisi langsung.';
        return false;
    }

    return true;
}

function canApproveClosingRevision(PDO $pdo, array $session): bool
{
    if (!isset($session['kode_karyawan'], $session['role'])) {
        return false;
    }

    // RBAC Task 10: kasir_approve dipegang ADM (super_admin) + KEU (admin).
    return in_array($session['role'], ['super_admin', 'admin'], true);
}

function userCanRequestRevisionForTransaction(PDO $pdo, array $session, array $transaction): bool
{
    return userCanAccessTransaction($pdo, $session, $transaction);
}

function generateRevisionTransactionCode(PDO $pdo, string $tanggalTransaksi, string $kodeCabang, string $kodeKaryawan): string
{
    $stmtUser = $pdo->prepare("SELECT nama_user FROM tbuser WHERE kode_karyawan = ? LIMIT 1");
    $stmtUser->execute([$kodeKaryawan]);
    $kodeUser = $stmtUser->fetchColumn();

    if (!$kodeUser) {
        throw new RuntimeException('Kode user untuk transaksi revisi tidak ditemukan.');
    }

    $stmtCount = $pdo->prepare(
        "SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE tanggal_transaksi = :tanggal_transaksi AND kode_cabang = :kode_cabang"
    );
    $stmtCount->execute([
        ':tanggal_transaksi' => $tanggalTransaksi,
        ':kode_cabang' => $kodeCabang,
    ]);

    $counter = (int) $stmtCount->fetchColumn();
    $datePart = date('Ymd', strtotime($tanggalTransaksi));
    $runningNumber = str_pad((string) ($counter + 1), 4, '0', STR_PAD_LEFT);
    $kodeTransaksiBaru = "TRX-{$datePart}-{$kodeUser}{$runningNumber}";

    $attempt = 0;
    while ($attempt < 100) {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM kasir_transactions_closing_kasir WHERE kode_transaksi = ?");
        $stmtCheck->execute([$kodeTransaksiBaru]);
        if ((int) $stmtCheck->fetchColumn() === 0) {
            return $kodeTransaksiBaru;
        }

        $attempt++;
        $runningNumber = str_pad((string) ((int) $runningNumber + 1), 4, '0', STR_PAD_LEFT);
        $kodeTransaksiBaru = "TRX-{$datePart}-{$kodeUser}{$runningNumber}";
    }

    throw new RuntimeException('Gagal membuat nomor transaksi revisi yang unik.');
}

function duplicateTransactionRows(PDO $pdo, string $tableName, array $columns, string $oldKodeTransaksi, string $newKodeTransaksi): void
{
    $targetColumns = implode(', ', $columns);
    $selectColumns = [];

    foreach ($columns as $column) {
        if ($column === 'kode_transaksi') {
            $selectColumns[] = ':kode_transaksi_baru';
            continue;
        }

        $selectColumns[] = $column;
    }

    $sql = sprintf(
        "INSERT INTO %s (%s) SELECT %s FROM %s WHERE kode_transaksi = :kode_transaksi_lama",
        $tableName,
        $targetColumns,
        implode(', ', $selectColumns),
        $tableName
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':kode_transaksi_baru' => $newKodeTransaksi,
        ':kode_transaksi_lama' => $oldKodeTransaksi,
    ]);
}

function buildClosingRevisionTransactionSnapshot(array $transaction): array
{
    $kasAwal = (float) ($transaction['kas_awal'] ?? 0); // kolom asli kasir_transactions_closing_kasir tetap 'kas_awal'
    $kasAkhir = isset($transaction['kas_akhir']) ? (float) $transaction['kas_akhir'] : null;
    $totalPenjualan = (float) ($transaction['total_penjualan'] ?? 0);
    $totalServis = (float) ($transaction['total_servis'] ?? 0);
    $totalPemasukan = (float) ($transaction['total_pemasukan'] ?? 0);
    $totalPengeluaran = (float) ($transaction['total_pengeluaran'] ?? 0);
    $omset = $totalPenjualan + $totalServis;
    $setoranReal = $kasAkhir !== null ? ($kasAkhir - $kasAwal) : null;
    $dataSetoran = $omset + $totalPemasukan - $totalPengeluaran;
    $selisihSetoran = $setoranReal !== null ? ($setoranReal - $dataSetoran) : null;

    return [
        'status' => $transaction['status'] ?? null,
        'kas_awal' => $kasAwal,
        'kas_akhir' => $kasAkhir,
        'total_penjualan' => $totalPenjualan,
        'total_servis' => $totalServis,
        'omset' => $omset,
        'total_pemasukan' => $totalPemasukan,
        'total_pengeluaran' => $totalPengeluaran,
        'setoran_real' => $setoranReal,
        'data_setoran' => $dataSetoran,
        'selisih_setoran' => $selisihSetoran,
    ];
}

function getClosingRevisionSummary(PDO $pdo, string $kodeTransaksi): ?array
{
    $stmt = $pdo->prepare(
        "SELECT r.*, u.nama_lengkap AS nama_pemohon, a.nama_lengkap AS nama_approver
         FROM closing_revision_requests_closing_kasir r
         LEFT JOIN tbuser u ON u.kode_karyawan = r.kode_pemohon
         LEFT JOIN tbuser a ON a.kode_karyawan = r.approver_kode
         WHERE r.kode_transaksi_lama = :kode_transaksi
            OR r.kode_transaksi_baru = :kode_transaksi
         ORDER BY COALESCE(r.approved_at, r.rejected_at, r.created_at) DESC
         LIMIT 1"
    );
    $stmt->execute([':kode_transaksi' => $kodeTransaksi]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        return null;
    }

    $codes = array_values(array_filter([
        $request['kode_transaksi_lama'] ?? null,
        $request['kode_transaksi_baru'] ?? null,
    ]));

    $transactions = [];
    if (!empty($codes)) {
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $stmtTrx = $pdo->prepare("SELECT * FROM kasir_transactions_closing_kasir WHERE kode_transaksi IN ($placeholders)");
        $stmtTrx->execute($codes);
        foreach ($stmtTrx->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $transactions[$row['kode_transaksi']] = $row;
        }
    }

    $old = $transactions[$request['kode_transaksi_lama']] ?? null;
    $new = !empty($request['kode_transaksi_baru']) ? ($transactions[$request['kode_transaksi_baru']] ?? null) : null;

    $oldSnapshot = $old ? buildClosingRevisionTransactionSnapshot($old) : null;
    $newSnapshot = $new ? buildClosingRevisionTransactionSnapshot($new) : null;

    $comparisonFields = [
        'status' => 'Status',
        'kas_awal' => 'Kas Awal',
        'kas_akhir' => 'Kas Akhir',
        'total_penjualan' => 'Penjualan',
        'total_servis' => 'Servis',
        'omset' => 'Omset',
        'total_pemasukan' => 'Pemasukan',
        'total_pengeluaran' => 'Pengeluaran',
        'setoran_real' => 'Setoran Real',
        'data_setoran' => 'Data Setoran',
        'selisih_setoran' => 'Selisih Setoran',
    ];

    $differences = [];
    foreach ($comparisonFields as $field => $label) {
        $oldValue = $oldSnapshot[$field] ?? null;
        $newValue = $newSnapshot[$field] ?? null;

        $isDifferent = false;
        if (is_numeric($oldValue) || is_numeric($newValue)) {
            $isDifferent = round((float) $oldValue, 2) !== round((float) $newValue, 2);
        } else {
            $isDifferent = (string) $oldValue !== (string) $newValue;
        }

        if ($isDifferent) {
            $differences[] = [
                'field' => $field,
                'label' => $label,
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }
    }

    return [
        'request' => $request,
        'old_transaction' => $old,
        'new_transaction' => $new,
        'old_snapshot' => $oldSnapshot,
        'new_snapshot' => $newSnapshot,
        'differences' => $differences,
        'line_item_changes' => (!empty($request['kode_transaksi_lama']) && !empty($request['kode_transaksi_baru']))
            ? getClosingRevisionLineItemChanges($pdo, (string) $request['kode_transaksi_lama'], (string) $request['kode_transaksi_baru'])
            : [],
    ];
}

function getClosingRevisionLineItemChanges(PDO $pdo, string $oldKodeTransaksi, string $newKodeTransaksi): array
{
    $sections = [
        [
            'key' => 'pemasukan',
            'label' => 'Pemasukan Kasir',
            'table' => 'pemasukan_kasir_closing_kasir',
            'fields' => ['kode_akun', 'keterangan_transaksi', 'tanggal', 'waktu'],
            'value_field' => 'jumlah',
            'value_label' => 'Nominal',
            'display' => static function (array $row): string {
                return trim(sprintf(
                    '%s | %s | %s %s',
                    $row['kode_akun'] ?? '-',
                    $row['keterangan_transaksi'] ?? '-',
                    $row['tanggal'] ?? '-',
                    $row['waktu'] ?? '-'
                ));
            },
        ],
        [
            'key' => 'pengeluaran',
            'label' => 'Pengeluaran Kasir',
            'table' => 'pengeluaran_kasir_closing_kasir',
            'fields' => ['kode_akun', 'kategori', 'keterangan_transaksi', 'tanggal', 'waktu', 'umur_pakai'],
            'value_field' => 'jumlah',
            'value_label' => 'Nominal',
            'display' => static function (array $row): string {
                return trim(sprintf(
                    '%s | %s | %s | %s %s',
                    $row['kode_akun'] ?? '-',
                    $row['kategori'] ?? '-',
                    $row['keterangan_transaksi'] ?? '-',
                    $row['tanggal'] ?? '-',
                    $row['waktu'] ?? '-'
                ));
            },
        ],
        [
            'key' => 'penjualan',
            'label' => 'Data Penjualan',
            'table' => 'data_penjualan_closing_kasir',
            'fields' => ['tanggal', 'waktu'],
            'value_field' => 'jumlah_penjualan',
            'value_label' => 'Nominal',
            'display' => static function (array $row): string {
                return trim(sprintf(
                    'Penjualan | %s %s',
                    $row['tanggal'] ?? '-',
                    $row['waktu'] ?? '-'
                ));
            },
        ],
        [
            'key' => 'servis',
            'label' => 'Data Servis',
            'table' => 'data_servis_closing_kasir',
            'fields' => ['tanggal', 'waktu'],
            'value_field' => 'jumlah_servis',
            'value_label' => 'Nominal',
            'display' => static function (array $row): string {
                return trim(sprintf(
                    'Servis | %s %s',
                    $row['tanggal'] ?? '-',
                    $row['waktu'] ?? '-'
                ));
            },
        ],
        [
            'key' => 'kas_akhir_detail',
            'label' => 'Detail Kas Akhir',
            'table' => 'detail_kas_akhir',
            'fields' => ['nominal'],
            'value_field' => 'jumlah_keping',
            'value_label' => 'Jumlah Keping',
            'display' => static function (array $row): string {
                return 'Nominal Rp' . number_format((float) ($row['nominal'] ?? 0), 0, ',', '.');
            },
        ],
    ];

    $results = [];
    foreach ($sections as $section) {
        $rowsOld = fetchClosingRevisionRows($pdo, $section['table'], $oldKodeTransaksi);
        $rowsNew = fetchClosingRevisionRows($pdo, $section['table'], $newKodeTransaksi);
        $results[] = [
            'key' => $section['key'],
            'label' => $section['label'],
            'added' => [],
            'removed' => [],
            'changed' => [],
        ];
        $index = count($results) - 1;

        $oldMap = [];
        foreach ($rowsOld as $row) {
            $identity = buildClosingRevisionIdentity($row, $section['fields']);
            $oldMap[$identity] = $row;
        }

        $newMap = [];
        foreach ($rowsNew as $row) {
            $identity = buildClosingRevisionIdentity($row, $section['fields']);
            $newMap[$identity] = $row;
        }

        $allKeys = array_values(array_unique(array_merge(array_keys($oldMap), array_keys($newMap))));
        foreach ($allKeys as $identity) {
            $oldRow = $oldMap[$identity] ?? null;
            $newRow = $newMap[$identity] ?? null;

            if ($oldRow && !$newRow) {
                $results[$index]['removed'][] = [
                    'label' => $section['display']($oldRow),
                    'old_value' => $oldRow[$section['value_field']] ?? null,
                    'value_label' => $section['value_label'],
                ];
                continue;
            }

            if (!$oldRow && $newRow) {
                $results[$index]['added'][] = [
                    'label' => $section['display']($newRow),
                    'new_value' => $newRow[$section['value_field']] ?? null,
                    'value_label' => $section['value_label'],
                ];
                continue;
            }

            $oldValue = $oldRow[$section['value_field']] ?? null;
            $newValue = $newRow[$section['value_field']] ?? null;
            if (round((float) $oldValue, 2) !== round((float) $newValue, 2)) {
                $results[$index]['changed'][] = [
                    'label' => $section['display']($newRow),
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'value_label' => $section['value_label'],
                ];
            }
        }
    }

    return $results;
}

function fetchClosingRevisionRows(PDO $pdo, string $tableName, string $kodeTransaksi): array
{
    $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE kode_transaksi = ?");
    $stmt->execute([$kodeTransaksi]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildClosingRevisionIdentity(array $row, array $fields): string
{
    $parts = [];
    foreach ($fields as $field) {
        $parts[] = trim((string) ($row[$field] ?? ''));
    }
    return implode('||', $parts);
}

function approveClosingRevisionRequest(PDO $pdo, int $requestId, string $approverKode, ?string $approvalNote = null): array
{
    $stmtRequest = $pdo->prepare(
        "SELECT * FROM closing_revision_requests_closing_kasir WHERE id = ? AND status = 'pending' LIMIT 1"
    );
    $stmtRequest->execute([$requestId]);
    $request = $stmtRequest->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new RuntimeException('Permintaan revisi tidak ditemukan atau sudah diproses.');
    }

    $stmtTransaction = $pdo->prepare(
        "SELECT * FROM kasir_transactions_closing_kasir WHERE kode_transaksi = ? LIMIT 1"
    );
    $stmtTransaction->execute([$request['kode_transaksi_lama']]);
    $transaction = $stmtTransaction->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new RuntimeException('Transaksi lama tidak ditemukan.');
    }

    $revisionBlockReason = '';
    if (!canTransactionBeRevised($transaction, $revisionBlockReason)) {
        throw new RuntimeException($revisionBlockReason);
    }

    $kodeTransaksiBaru = generateRevisionTransactionCode(
        $pdo,
        (string) $transaction['tanggal_transaksi'],
        (string) $transaction['kode_cabang'],
        (string) $transaction['kode_karyawan']
    );

    $pdo->beginTransaction();

    try {
        $stmtInsertTransaction = $pdo->prepare(
            "INSERT INTO kasir_transactions_closing_kasir (
                kode_transaksi, kas_awal, kas_akhir, total_pemasukan, total_pengeluaran,
                total_penjualan, total_servis, status, tanggal_transaksi, tanggal_closing,
                jam_closing, setoran_real, omset, data_setoran, selisih_setoran,
                kode_karyawan, kasir_asal, kode_cabang, nama_cabang, deposit_status,
                deposit_difference_status, jenis_closing, closing_group_id, is_part_of_closing,
                jenis_setoran_id, kode_setoran, jumlah_diterima_fisik, catatan_validasi,
                rekening_tujuan_id, validasi_at, validasi_by, bukti_transaksi, revision_parent_kode
            ) VALUES (
                :kode_transaksi, :kas_awal, :kas_akhir, :total_pemasukan, :total_pengeluaran,
                :total_penjualan, :total_servis, 'on proses', :tanggal_transaksi, NULL,
                NULL, :setoran_real, :omset, :data_setoran, :selisih_setoran,
                :kode_karyawan, :kasir_asal, :kode_cabang, :nama_cabang, 'Belum Disetor',
                'Belum Diverifikasi', :jenis_closing, :closing_group_id, :is_part_of_closing,
                :jenis_setoran_id, NULL, NULL, NULL,
                NULL, NULL, NULL, NULL, :revision_parent_kode
            )"
        );

        $stmtInsertTransaction->execute([
            ':kode_transaksi' => $kodeTransaksiBaru,
            ':kas_awal' => $transaction['kas_awal'],
            ':kas_akhir' => $transaction['kas_akhir'],
            ':total_pemasukan' => $transaction['total_pemasukan'],
            ':total_pengeluaran' => $transaction['total_pengeluaran'],
            ':total_penjualan' => $transaction['total_penjualan'],
            ':total_servis' => $transaction['total_servis'],
            ':tanggal_transaksi' => $transaction['tanggal_transaksi'],
            ':setoran_real' => $transaction['setoran_real'],
            ':omset' => $transaction['omset'],
            ':data_setoran' => $transaction['data_setoran'],
            ':selisih_setoran' => $transaction['selisih_setoran'],
            ':kode_karyawan' => $transaction['kode_karyawan'],
            ':kasir_asal' => $transaction['kasir_asal'],
            ':kode_cabang' => $transaction['kode_cabang'],
            ':nama_cabang' => $transaction['nama_cabang'],
            ':jenis_closing' => $transaction['jenis_closing'],
            ':closing_group_id' => $transaction['closing_group_id'],
            ':is_part_of_closing' => $transaction['is_part_of_closing'],
            ':jenis_setoran_id' => $transaction['jenis_setoran_id'],
            ':revision_parent_kode' => $transaction['kode_transaksi'],
        ]);

        duplicateTransactionRows(
            $pdo,
            'kas_awal',
            ['kode_transaksi', 'total_nilai', 'tanggal', 'waktu', 'status', 'kode_karyawan'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'detail_kas_awal',
            ['kode_transaksi', 'nominal', 'jumlah_keping'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'kas_akhir',
            ['kode_transaksi', 'total_nilai', 'tanggal', 'waktu', 'kode_karyawan'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'detail_kas_akhir',
            ['kode_transaksi', 'nominal', 'jumlah_keping'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'pemasukan_kasir_closing_kasir',
            ['kode_transaksi', 'kode_akun', 'jumlah', 'keterangan_transaksi', 'tanggal', 'waktu', 'kode_karyawan', 'nomor_transaksi_closing'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'pengeluaran_kasir_closing_kasir',
            ['kode_transaksi', 'kode_akun', 'jumlah', 'keterangan_transaksi', 'tanggal', 'waktu', 'kode_karyawan', 'umur_pakai', 'kategori'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'data_penjualan_closing_kasir',
            ['kode_transaksi', 'jumlah_penjualan', 'tanggal', 'waktu', 'kode_karyawan'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        duplicateTransactionRows(
            $pdo,
            'data_servis_closing_kasir',
            ['kode_transaksi', 'jumlah_servis', 'tanggal', 'waktu', 'kode_karyawan'],
            $transaction['kode_transaksi'],
            $kodeTransaksiBaru
        );

        $stmtCancelOld = $pdo->prepare(
            "UPDATE kasir_transactions_closing_kasir
             SET status = 'dibatalkan',
                 revision_child_kode = :revision_child_kode,
                 cancelled_at = NOW(),
                 cancelled_by = :cancelled_by,
                 cancel_reason = :cancel_reason
             WHERE kode_transaksi = :kode_transaksi"
        );
        $stmtCancelOld->execute([
            ':revision_child_kode' => $kodeTransaksiBaru,
            ':cancelled_by' => $approverKode,
            ':cancel_reason' => $request['alasan'],
            ':kode_transaksi' => $transaction['kode_transaksi'],
        ]);

        $stmtUpdateRequest = $pdo->prepare(
            "UPDATE closing_revision_requests_closing_kasir
             SET status = 'approved',
                 kode_transaksi_baru = :kode_transaksi_baru,
                 approver_kode = :approver_kode,
                 approval_note = :approval_note,
                 approved_at = NOW()
             WHERE id = :id"
        );
        $stmtUpdateRequest->execute([
            ':kode_transaksi_baru' => $kodeTransaksiBaru,
            ':approver_kode' => $approverKode,
            ':approval_note' => $approvalNote,
            ':id' => $requestId,
        ]);

        $pdo->commit();

        return [
            'kode_transaksi_lama' => $transaction['kode_transaksi'],
            'kode_transaksi_baru' => $kodeTransaksiBaru,
        ];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}
