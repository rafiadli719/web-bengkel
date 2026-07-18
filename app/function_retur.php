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
    $q = mysqli_query($koneksi, "SELECT count(noretur) as LastID FROM tblretur_servis_header");
    $row = mysqli_fetch_array($q);
    return isset($row['LastID']) ? (int)$row['LastID'] : 0;
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
