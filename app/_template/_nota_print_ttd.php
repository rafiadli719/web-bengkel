<?php
/**
 * Blok tanda tangan 3 kolom buat nota browser-print.
 * Include ini SETELAH set $ttd_cols di scope pemanggil, mis.:
 *   $ttd_cols = [
 *       ['label' => 'Dibuat Oleh,', 'nama' => $h['user']],
 *       ['label' => 'Diketahui,',   'nama' => '______________'],
 *       ['label' => 'Pelanggan,',   'nama' => '______________'],
 *   ];
 */
?>
<div class="ttd-row">
    <?php foreach ($ttd_cols as $col): ?>
    <div class="ttd-col">
        <div><?php echo htmlspecialchars($col['label']); ?></div>
        <div class="ttd-box"></div>
        <p>(<?php echo htmlspecialchars($col['nama']); ?>)</p>
    </div>
    <?php endforeach; ?>
</div>
