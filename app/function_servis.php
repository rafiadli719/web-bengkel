<?php
/**
 * Generator no_service (nomor tiket servis).
 *
 * FIX 2026-08-23: sebelumnya OtomatisID() pakai SELECT COUNT(no_service),
 * lalu FormatNoTrans() nambah +1 — dua request bersamaan (cabang/staf beda)
 * bisa baca COUNT yang sama dan hasilkan no_service kembar (kolom
 * no_service TIDAK unique/primary key, jadi DB tidak menolak).
 *
 * Fix: pakai tabel counter kecil (tblservice_no_counter, 1 baris per
 * tahun) + pola atomic-increment MySQL "UPDATE ... SET seq =
 * LAST_INSERT_ID(seq + 1)". InnoDB row-lock otomatis nyerialize dua
 * request yang barengan, jadi gak mungkin dua-duanya dapat angka sama.
 * Signature kedua fungsi TETAP SAMA — semua caller lama (save-no-servis-*.php,
 * helper-functions.php) jalan tanpa perlu diubah.
 */
function OtomatisID()
{
    include "../config/koneksi.php";

    $tahun = (int) date('Y');

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS tblservice_no_counter (
        tahun INT NOT NULL PRIMARY KEY,
        seq INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");

    // Baris counter tahun ini belum ada? Seed dari angka tertinggi yang
    // SUDAH terpakai (bukan COUNT) biar gak nabrak no_service existing.
    $qCheck = mysqli_query($koneksi, "SELECT 1 FROM tblservice_no_counter WHERE tahun = $tahun");
    if (!$qCheck || mysqli_num_rows($qCheck) === 0) {
        $thn2 = substr((string) $tahun, 2, 2);
        $prefix = mysqli_real_escape_string($koneksi, "SV" . $thn2);
        $qSeed = mysqli_query($koneksi, "SELECT COALESCE(MAX(CAST(SUBSTRING(no_service, 5) AS UNSIGNED)), 0) AS mx
                                          FROM tblservice WHERE no_service LIKE '{$prefix}%'");
        $seed = 0;
        if ($qSeed && ($rowSeed = mysqli_fetch_assoc($qSeed))) {
            $seed = (int) $rowSeed['mx'];
        }
        mysqli_query($koneksi, "INSERT IGNORE INTO tblservice_no_counter (tahun, seq) VALUES ($tahun, $seed)");
    }

    // Atomic increment — aman dipanggil bersamaan dari cabang/staf berbeda.
    mysqli_query($koneksi, "UPDATE tblservice_no_counter SET seq = LAST_INSERT_ID(seq + 1) WHERE tahun = $tahun");
    $next = (int) mysqli_insert_id($koneksi);

    if ($next <= 0) {
        // Fallback (harusnya tidak pernah kejadian): baca langsung.
        $qFallback = mysqli_query($koneksi, "SELECT seq FROM tblservice_no_counter WHERE tahun = $tahun");
        $rowFallback = mysqli_fetch_assoc($qFallback);
        $next = (int) ($rowFallback['seq'] ?? 0);
    }

    // Dikembalikan sudah "num-1" biar kompatibel dengan FormatNoTrans() lama
    // yang selalu nambah +1 sebelum format — supaya signature/pemanggilan
    // di semua file lain tidak perlu berubah sama sekali.
    return $next - 1;
}

function FormatNoTrans($num) {
            $thn_skr=date('Y');
            $thn=substr($thn_skr,2,2);
        $num=$num+1; switch (strlen($num))
        {
        case 1 : $NoTrans = "SV".$thn."00000000".$num; break;
        case 2 : $NoTrans = "SV".$thn."0000000".$num; break;
        case 3 : $NoTrans = "SV".$thn."000000".$num; break;
        case 4 : $NoTrans = "SV".$thn."00000".$num; break;
        case 5 : $NoTrans = "SV".$thn."0000".$num; break;
        case 6 : $NoTrans = "SV".$thn."000".$num; break;
        case 7 : $NoTrans = "SV".$thn."00".$num; break;
        case 8 : $NoTrans = "SV".$thn."0".$num; break;
        case 9 : $NoTrans = "SV".$thn.$num; break;
        default: $NoTrans = $num;
        }
        return $NoTrans;
}

/**
 * FIX 2026-08-23 (lanjutan): generator no_service lain SELAIN OtomatisID()
 * (save_garapan.php, servis-garansi.php, servis-reguler-jemput.php,
 * ajax-save-service.php) masing-masing punya generator inline sendiri —
 * 3 pakai pola "SELECT MAX(...)+1" (race condition sama seperti
 * OtomatisID() lama) dan 1 pakai rand(1,999) (bisa nabrak, bukan cuma
 * race). Keempatnya punya prefix format beda (GAR-, SRV, SV, SERV) yang
 * dipakai/dicek di file lain, jadi format TIDAK diubah — cuma cara
 * generate nomor urutnya yang diganti ke atomic-increment per prefix,
 * pola sama seperti OtomatisID(): tabel counter kecil + row-lock MySQL
 * "UPDATE ... SET seq = LAST_INSERT_ID(seq + 1)".
 *
 * @param mysqli $koneksi
 * @param string $seqKey  kunci counter unik (biasanya = $prefix, atau
 *                         $prefix + periode kalau prefix-nya per-hari/bulan)
 * @param string $prefix  prefix no_service yang mau dicari MAX-nya buat seed
 * @return int    nomor urut berikutnya (belum di-pad, caller yang format)
 */
function NextServiceSeqByPrefix($koneksi, $seqKey, $prefix)
{
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS tbl_service_seq_counter (
        seq_key VARCHAR(64) NOT NULL PRIMARY KEY,
        seq INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB");

    $seqKeyEsc = mysqli_real_escape_string($koneksi, $seqKey);

    $qCheck = mysqli_query($koneksi, "SELECT 1 FROM tbl_service_seq_counter WHERE seq_key = '$seqKeyEsc'");
    if (!$qCheck || mysqli_num_rows($qCheck) === 0) {
        $prefixEsc = mysqli_real_escape_string($koneksi, $prefix);
        // Seed dari nomor tertinggi yang SUDAH terpakai untuk prefix ini,
        // bukan COUNT — biar gak nabrak no_service existing.
        $qSeed = mysqli_query($koneksi, "SELECT COALESCE(MAX(CAST(SUBSTRING(no_service, " . (strlen($prefix) + 1) . ") AS UNSIGNED)), 0) AS mx
                                          FROM tblservice WHERE no_service LIKE '{$prefixEsc}%'");
        $seed = 0;
        if ($qSeed && ($rowSeed = mysqli_fetch_assoc($qSeed))) {
            $seed = (int) $rowSeed['mx'];
        }
        mysqli_query($koneksi, "INSERT IGNORE INTO tbl_service_seq_counter (seq_key, seq) VALUES ('$seqKeyEsc', $seed)");
    }

    // Atomic increment — aman dipanggil bersamaan dari cabang/staf berbeda.
    mysqli_query($koneksi, "UPDATE tbl_service_seq_counter SET seq = LAST_INSERT_ID(seq + 1) WHERE seq_key = '$seqKeyEsc'");
    $next = (int) mysqli_insert_id($koneksi);

    if ($next <= 0) {
        // Fallback (harusnya tidak pernah kejadian): baca langsung.
        $qFallback = mysqli_query($koneksi, "SELECT seq FROM tbl_service_seq_counter WHERE seq_key = '$seqKeyEsc'");
        $rowFallback = mysqli_fetch_assoc($qFallback);
        $next = (int) ($rowFallback['seq'] ?? 0);
    }

    return $next;
}

/**
 * Bikin service garansi baru (ref ke service asli) + antrian prioritas
 * urgent. Extract dari servis-garansi.php (F1-A/F1-B) biar bisa dipanggil
 * ulang dari luar form garansi manual — dipakai juga oleh alur REWORK
 * modul Komplain (ajax_keputusan_rework.php), supaya rework selalu masuk
 * jalur garansi (is_garansi=1), bukan servis reguler/jemput.
 *
 * @param mysqli $koneksi
 * @param string $ref_service    no_service asli yang jadi dasar garansi (wajib, non-empty)
 * @param string $kode_pelanggan
 * @param string $no_polisi
 * @param string $keluhan
 * @param string $kd_cabang
 * @param int    $id_user
 * @return array{success:bool,no_service?:string,no_antrian?:int,message?:string}
 */
function createServisGaransi($koneksi, $ref_service, $kode_pelanggan, $no_polisi, $keluhan, $kd_cabang, $id_user)
{
    if (empty($ref_service)) {
        return ['success' => false, 'message' => 'ref_service wajib diisi untuk service garansi.'];
    }

    $ref_service_escaped = mysqli_real_escape_string($koneksi, $ref_service);
    $q_ref = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$ref_service_escaped'");
    $ref_service_data = ($q_ref && mysqli_num_rows($q_ref) > 0) ? mysqli_fetch_assoc($q_ref) : null;
    if (!$ref_service_data) {
        return ['success' => false, 'message' => "Service asli '$ref_service' tidak ditemukan."];
    }

    $tanggal_service = date('Y-m-d');
    $jam_input = date('H:i');

    $prefix_service = 'GAR-' . date('Ymd') . '-';
    $new_number = NextServiceSeqByPrefix($koneksi, $prefix_service, $prefix_service);
    $no_service = $prefix_service . str_pad($new_number, 4, '0', STR_PAD_LEFT);

    $query_antrian_count = "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_service'";
    $result_antrian_count = mysqli_query($koneksi, $query_antrian_count);
    $antrian_count = mysqli_fetch_array($result_antrian_count)['total'];
    $no_antrian = $antrian_count + 1;

    $keluhan_esc = mysqli_real_escape_string($koneksi, $keluhan);
    $kode_pelanggan_esc = mysqli_real_escape_string($koneksi, $kode_pelanggan);
    $no_polisi_esc = mysqli_real_escape_string($koneksi, $no_polisi);
    $kd_cabang_esc = mysqli_real_escape_string($koneksi, $kd_cabang);
    $id_user_esc = (int) $id_user;

    $tgl_expire = '';
    $mekanik_orig = $ref_service_data['mekanik1'] ?? '';
    $komisi_mode = 'unknown';
    $tgl_asal = $ref_service_data['tanggal'] ?? '';
    if ($tgl_asal) {
        $masa_garansi_standar = 7;
        if (function_exists('getMasaGaransiHari') && !empty($ref_service_data['no_pelanggan'])) {
            $mg = getMasaGaransiHari($koneksi, $ref_service_data['no_pelanggan']);
            $masa_garansi_standar = $mg['standar'];
        }
        $tgl_expire = date('Y-m-d', strtotime($tgl_asal . " +{$masa_garansi_standar} days"));
    }

    $query_insert_service = "INSERT INTO tblservice (
        no_service, tanggal, jam, no_pelanggan, no_polisi, kd_cabang, id_user,
        status, status_servis, status_jemput, keterangan,
        is_garansi, ref_no_service_original, tanggal_garansi_expire,
        mekanik_original, komisi_garansi_mode, created_at
    ) VALUES (
        '$no_service', '$tanggal_service', '$jam_input', '$kode_pelanggan_esc',
        '$no_polisi_esc', '$kd_cabang_esc', '$id_user_esc',
        '1', 'datang', '0', '$keluhan_esc',
        '1', '$ref_service_escaped', " . ($tgl_expire ? "'$tgl_expire'" : "NULL") . ",
        '$mekanik_orig', '$komisi_mode', NOW()
    )";

    if (!mysqli_query($koneksi, $query_insert_service)) {
        return ['success' => false, 'message' => 'Gagal insert tblservice: ' . mysqli_error($koneksi)];
    }

    $query_insert_antrian = "INSERT INTO tb_antrian_servis (
        no_service, no_antrian, tanggal, jam_ambil,
        status_antrian, prioritas, created_at
    ) VALUES (
        '$no_service', '$no_antrian', '$tanggal_service', '$jam_input',
        'menunggu', 'urgent', NOW()
    )";

    if (!mysqli_query($koneksi, $query_insert_antrian)) {
        return ['success' => false, 'message' => 'Gagal insert tb_antrian_servis: ' . mysqli_error($koneksi)];
    }

    return ['success' => true, 'no_service' => $no_service, 'no_antrian' => $no_antrian];
}
?>
