<?php
/**
 * Bagian HTML yang dipakai bareng oleh semua nota/struk yang di-render
 * lewat Dompdf (penjualan, pembelian, pembayaran hutang/piutang, pesanan,
 * penyesuaian stok, servis, dll). Tujuannya biar header logo+perusahaan,
 * style dasar, dan blok tanda tangan SERAGAM di semua nota, dan kalau ada
 * bug/perubahan cukup dibenerin di 1 tempat.
 *
 * Dipakai dengan cara: include file ini, lalu panggil fungsinya waktu
 * nyusun string $html buat di-load ke Dompdf.
 */

/** Style dasar yang sebelumnya di-copy-paste di tiap file nota Dompdf. */
function nota_pdf_style() {
    return '
        html, body {
            font-family: Arial, Helvetica, sans-serif;
        }
        table.table, table.table td, table.table th {
            border: 1px solid black;
        }
        table.table {
            width: 100%;
            border-collapse: collapse;
        }
        div.page_break + div.page_break{
            page-break-before: always;
        }
        sup {
            font-size: 8;
        }
    ';
}

/**
 * Header nota: logo + info perusahaan (kiri/tengah) + kotak judul & info
 * transaksi (kanan).
 *
 * @param string $file_logo       path logo dari tbsetting (relatif ke root app)
 * @param string $nama_perusahaan
 * @param string $alamat
 * @param string $notlp
 * @param string $fax
 * @param string $judul           judul dokumen, mis. "FAKTUR PENJUALAN"
 * @param array  $rows            list [label, value] buat kotak info kanan.
 *                                 Baris pertama otomatis di-bold (biasanya No. Transaksi).
 */
function nota_pdf_header($file_logo, $nama_perusahaan, $alamat, $notlp, $fax, $judul, $rows) {
    $rows_html = '';
    foreach ($rows as $i => $r) {
        $label = $r[0];
        $value = $r[1];
        $bold_open  = ($i === 0) ? '<b>' : '';
        $bold_close = ($i === 0) ? '</b>' : '';
        $rows_html .= '
            <tr>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;"><font size="2">'.$bold_open.$label.$bold_close.'</font></td>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 5%;"><font size="2">'.$bold_open.':'.$bold_close.'</font></td>
                <td style="padding: 1pt 2pt; vertical-align:top; width: 75%;"><font size="2">'.$bold_open.$value.$bold_close.'</font></td>
            </tr>';
    }

    return '
        <table style="margin: 0 0pt; width: 100%;">
            <tbody>
                <tr valign="top">
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 20%;">
                        <img src="../'.$file_logo.'" width="120pt">
                    </td>
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 40%;">
                        <b>'.$nama_perusahaan.'</b><br>
                        <font size="2">
                            '.$alamat.'<br>
                            Telp. '.$notlp.'<br>
                            Fax. '.$fax.'
                        </font>
                    </td>
                    <td style="padding: 1pt 2pt; vertical-align:top; width: 40%;">
                        <b>&nbsp;'.$judul.'</b><br>
                        <table style="margin: 0 0pt; width: 100%;">'.$rows_html.'
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>';
}

/**
 * Blok tanda tangan 2 kolom (mis. "Mengetahui" / "Penerima").
 * Sengaja TANPA nama hardcode apapun di baris tanda tangan.
 */
function nota_pdf_footer_ttd($label_kiri = 'Mengetahui', $label_kanan = 'Penerima') {
    return '
        <table style="margin: 0 0pt; width: 100%; border-collapse:collapse;" border="0">
            <tr>
                <td width="50%" align="center">
                <font size="2">'.$label_kiri.'</font>
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                </td>
                <td width="50%" align="center">
                <font size="2">'.$label_kanan.'</font>
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                <br>&nbsp;
                </td>
            </tr>
        </table>';
}
