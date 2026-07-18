<?php
if (!function_exists('app_report_default_range')) {
    function app_report_default_range($koneksi, $source, $column = 'tanggal', $where = '1=1') {
        $fallback_to = date('Y-m-d');
        $fallback_from = date('Y-m-01', strtotime($fallback_to));
        $max_tanggal = null;

        $sql = "SELECT MAX($column) AS max_tanggal FROM $source WHERE $where";
        $q = mysqli_query($koneksi, $sql);
        if ($q) {
            $r = mysqli_fetch_assoc($q);
            if (!empty($r['max_tanggal'])) {
                $max_tanggal = substr($r['max_tanggal'], 0, 10);
            }
        }

        if (!$max_tanggal) {
            $to_ymd = $fallback_to;
            $from_ymd = $fallback_from;
        } else {
            $to_ymd = date('Y-m-d', strtotime($max_tanggal));
            $from_ymd = date('Y-m-01', strtotime($max_tanggal));
        }

        return [
            'from_ymd' => $from_ymd,
            'to_ymd' => $to_ymd,
            'from_ymd_slash' => str_replace('-', '/', $from_ymd),
            'to_ymd_slash' => str_replace('-', '/', $to_ymd),
            'from_dmy' => date('d/m/Y', strtotime($from_ymd)),
            'to_dmy' => date('d/m/Y', strtotime($to_ymd)),
        ];
    }
}
