<?php
// tblitem warisan skema Access: 29 kolom NOT NULL TANPA default. MySQL strict
// (STRICT_TRANS_TABLES) menolak INSERT yang tidak mengisi kolom-kolom itu, jadi
// form tambah barang/jasa yang cuma kirim kolom inti selalu gagal (ditemukan
// E2E Data Master 2026-09-30). Helper ini menyisipkan kolom yang belum ada di
// INSERT dengan nilai konvensi data lama (mayoritas 5.7rb dari 5.9rb baris):
// harga bertingkat = hargajual, tier qty 1/1000/2/1001, jeniskomisi '3',
// inv_tglawal '1900-01-01', sisanya 0 / ''.
//
// Pakai: $sql = tblitemLengkapiInsert($sql, $hargajual);
// Kolom yang SUDAH ada di INSERT tidak disentuh.

function tblitemLengkapiInsert(string $sql, $hargajual): string
{
    if (!preg_match('/^(\s*INSERT\s+INTO\s+`?tblitem`?\s*\()(.*?)(\)\s*VALUES\s*\()(.*)(\)\s*;?\s*)$/is', $sql, $m)) {
        return $sql; // bukan pola INSERT tblitem (...) VALUES (...) tunggal — biarkan apa adanya
    }
    $hj = (float) $hargajual;
    $default = [
        'kodebarcode' => "''", 'gambar' => "''", 'note' => "''", 'kd_etalase' => "''",
        'supplier' => "''", 'supplier2' => "''", 'supplier3' => "''",
        'hargajual2' => $hj, 'hargajual3' => $hj,
        'hjqtys1' => 1, 'hjqtys2' => 1000, 'hjqtyd2' => 2, 'hjqtyd3' => 1001,
        'inv_hrgawal' => 0, 'inv_idawal' => 0, 'inv_jmlawal' => 0, 'inv_tglawal' => "'1900-01-01'",
        'jasasatuanwaktu' => "'1'", 'jasawaktu' => 0, 'jenis_jasa' => 0, 'jeniskomisi' => "'3'",
        'kd_pabrik' => 0, 'komisinominal' => 0, 'komisiprosen' => 0, 'quantity' => 0,
        'statusproduk' => "'1'", 'stok_maks' => 0, 'stokmin' => 0, 'totalpokok' => 0,
    ];
    $sudah = array_map(function ($c) { return strtolower(trim($c, " `\t\r\n")); }, explode(',', $m[2]));
    $cols = '';
    $vals = '';
    foreach ($default as $kolom => $nilai) {
        if (in_array($kolom, $sudah, true)) continue;
        $cols .= ", $kolom";
        $vals .= ", $nilai";
    }
    return $m[1] . $m[2] . $cols . $m[3] . $m[4] . $vals . $m[5];
}
