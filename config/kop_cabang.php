<?php
/**
 * Kop dokumen (struk/faktur/cetak) per cabang.
 *
 * Drop-in pengganti mysqli_query($koneksi, "SELECT * FROM tbsetting"): mengembalikan
 * mysqli_result satu baris dengan kolom tbsetting yang sama, tapi nama/alamat/telepon
 * diambil dari cabang yang sedang login ($_SESSION['_cabang']) bila terisi di tbcabang,
 * selain itu fallback ke tbsetting. Fax placeholder ("SIMPAN NOMOR WA ...") dikosongkan.
 */
if (!function_exists('fm_kop_query')) {
    function fm_kop_query($koneksi) {
        $kd = isset($_SESSION['_cabang']) ? mysqli_real_escape_string($koneksi, $_SESSION['_cabang']) : '';
        $has_tel = false;
        $c = @mysqli_query($koneksi, "SHOW COLUMNS FROM tbcabang LIKE 'telepon_cabang'");
        if ($c && mysqli_num_rows($c) > 0) { $has_tel = true; }
        $tel = $has_tel ? "COALESCE(NULLIF(TRIM(c.telepon_cabang),''), s.notlp)" : "s.notlp";
        $sql = "SELECT s.id, s.file_logo,
                       COALESCE(NULLIF(TRIM(c.nama_cabang),''), s.nama_perusahaan) AS nama_perusahaan,
                       COALESCE(NULLIF(TRIM(c.alamat_cabang),''), s.alamat) AS alamat,
                       $tel AS notlp,
                       IF(s.fax LIKE 'SIMPAN NOMOR%', '', s.fax) AS fax
                FROM tbsetting s
                LEFT JOIN tbcabang c ON c.kode_cabang = '$kd'
                LIMIT 1";
        $r = mysqli_query($koneksi, $sql);
        // Jaga-jaga: kalau join gagal, kembali ke perilaku lama.
        return $r ? $r : mysqli_query($koneksi, "SELECT * FROM tbsetting");
    }
}
