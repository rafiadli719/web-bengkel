<?php
/**
 * CSS bareng buat nota yang di-render browser (window.print()), bukan
 * lewat Dompdf - dipakai retur_penjualan_cetak.php, retur_pembelian_cetak.php,
 * pengadaan_antarcab_print.php, dan nota browser-print lain ke depannya.
 * Include di dalam tag <style> di <head>.
 */
?>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:Arial, sans-serif; font-size:12px; color:#333; background:#fff; }
.container { max-width:800px; margin:10px auto; padding:20px; }
.header-nota { text-align:center; border-bottom:2px solid #333; padding-bottom:10px; margin-bottom:15px; }
.header-nota h2 { font-size:18px; font-weight:bold; text-transform:uppercase; }
.header-nota p  { font-size:11px; color:#555; margin-top:2px; }
.header-nota h3 { font-size:14px; margin-top:8px; letter-spacing:1px; text-transform:uppercase; }
.info-grid { display:table; width:100%; margin-bottom:15px; }
.info-col  { display:table-cell; width:50%; vertical-align:top; }
.info-col table { width:100%; border-collapse:collapse; font-size:11px; }
.info-col td { padding:3px 5px; border:1px solid #ccc; }
.info-col td:first-child { font-weight:bold; width:110px; background:#f5f5f5; }
table.detail { width:100%; border-collapse:collapse; margin-bottom:15px; font-size:11px; }
table.detail th { background:#333; color:#fff; padding:5px 6px; text-align:center; border:1px solid #555; }
table.detail td { padding:4px 6px; border:1px solid #ccc; }
table.detail tfoot td { font-weight:bold; background:#f0f0f0; }
.ttd-row { display:table; width:100%; margin-top:30px; }
.ttd-col { display:table-cell; width:33%; text-align:center; padding:0 8px; }
.ttd-box { border:1px solid #ccc; height:75px; margin-bottom:5px; }
.ttd-col p { font-size:11px; }
.badge-type { display:inline-block; padding:2px 8px; border-radius:3px; font-size:10px; color:#fff; }
@media print {
    .no-print { display:none !important; }
    body { margin:0; }
    .container { margin:0; padding:10px; max-width:100%; }
}
