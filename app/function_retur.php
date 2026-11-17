<?php
function OtomatisIDReturPembelian($koneksi) {
    $q = mysqli_query($koneksi, "SELECT count(noretur) as LastID FROM tblretur_pembelian_header");
    $row = mysqli_fetch_array($q);
    return isset($row['LastID']) ? (int)$row['LastID'] : 0;
}

function FormatNoReturPembelian($num) {
    $thn = substr(date('Y'), 2, 2);
    $num = $num + 1;
    switch (strlen((string)$num)) {
        case 1: return "RP".$thn."00000000".$num;
        case 2: return "RP".$thn."0000000".$num;
        case 3: return "RP".$thn."000000".$num;
        case 4: return "RP".$thn."00000".$num;
        case 5: return "RP".$thn."0000".$num;
        case 6: return "RP".$thn."000".$num;
        case 7: return "RP".$thn."00".$num;
        case 8: return "RP".$thn."0".$num;
        case 9: return "RP".$thn.$num;
        default: return $num;
    }
}

function OtomatisIDReturPenjualan($koneksi) {
    $q = mysqli_query($koneksi, "SELECT count(noretur) as LastID FROM tblretur_penjualan_header");
    $row = mysqli_fetch_array($q);
    return isset($row['LastID']) ? (int)$row['LastID'] : 0;
}

function FormatNoReturPenjualan($num) {
    $thn = substr(date('Y'), 2, 2);
    $num = $num + 1;
    switch (strlen((string)$num)) {
        case 1: return "RJ".$thn."00000000".$num;
        case 2: return "RJ".$thn."0000000".$num;
        case 3: return "RJ".$thn."000000".$num;
        case 4: return "RJ".$thn."00000".$num;
        case 5: return "RJ".$thn."0000".$num;
        case 6: return "RJ".$thn."000".$num;
        case 7: return "RJ".$thn."00".$num;
        case 8: return "RJ".$thn."0".$num;
        case 9: return "RJ".$thn.$num;
        default: return $num;
    }
}

function OtomatisIDReturServis($koneksi) {
    // MAX (bukan COUNT): retur_servis_batal.php menghapus baris, COUNT membuat nomor dobel setelah pembatalan.
    // Format nomor: RS + 2 digit tahun + 9 digit urut, jadi urut = SUBSTRING dari karakter ke-5.
    $q = mysqli_query($koneksi, "SELECT COALESCE(MAX(CAST(SUBSTRING(noretur, 5) AS UNSIGNED)), 0) AS LastID FROM tblretur_servis_header WHERE noretur REGEXP '^RS[0-9]{11}$'");
    $row = mysqli_fetch_array($q);
    return isset($row['LastID']) ? (int)$row['LastID'] : 0;
}

// Harga per unit yang benar-benar dibayar pelanggan (sesudah potongan), dihitung dari DB, bukan dari input form.
// barang: total/quantity; jasa: total (jasa selalu qty 1). Tidak boleh lebih dari yang dibayar.
function HargaNetItemReturServis($koneksi, $no_service, $kd_cabang, $tipe_item, $no_item) {
    $s = mysqli_real_escape_string($koneksi, $no_service);
    $c = mysqli_real_escape_string($koneksi, $kd_cabang);
    $i = mysqli_real_escape_string($koneksi, $no_item);
    if ($tipe_item === 'jasa') {
        $q = mysqli_query($koneksi, "SELECT total FROM tblservis_jasa WHERE no_service='$s' AND kd_cabang='$c' AND no_item='$i' LIMIT 1");
        $r = $q ? mysqli_fetch_assoc($q) : null;
        return $r ? max(0.0, (float)$r['total']) : 0.0;
    }
    $q = mysqli_query($koneksi, "SELECT quantity, total FROM tblservis_barang WHERE no_service='$s' AND kd_cabang='$c' AND no_item='$i' LIMIT 1");
    $r = $q ? mysqli_fetch_assoc($q) : null;
    if (!$r || (int)$r['quantity'] <= 0) { return 0.0; }
    return max(0.0, round((float)$r['total'] / (int)$r['quantity'], 2));
}

function FormatNoReturServis($num) {
    $thn = substr(date('Y'), 2, 2);
    $num = $num + 1;
    switch (strlen((string)$num)) {
        case 1: return "RS".$thn."00000000".$num;
        case 2: return "RS".$thn."0000000".$num;
        case 3: return "RS".$thn."000000".$num;
        case 4: return "RS".$thn."00000".$num;
        case 5: return "RS".$thn."0000".$num;
        case 6: return "RS".$thn."000".$num;
        case 7: return "RS".$thn."00".$num;
        case 8: return "RS".$thn."0".$num;
        case 9: return "RS".$thn.$num;
        default: return $num;
    }
}
?>
