<?php
/**
 * Header nota browser-print (nama perusahaan + alamat/telp + judul dokumen).
 * Include ini SETELAH set variabel berikut di scope pemanggil:
 *   $nama_perusahaan, $alamat_perusahaan, $telp_perusahaan, $judul_doc
 * $telp_perusahaan boleh kosong (baris telp gak akan dicetak).
 */
?>
<div class="header-nota">
    <h2><?php echo htmlspecialchars($nama_perusahaan); ?></h2>
    <p>
        <?php echo htmlspecialchars($alamat_perusahaan); ?><?php if (!empty($telp_perusahaan)): ?>
            &nbsp;&mdash;&nbsp;Telp. <?php echo htmlspecialchars($telp_perusahaan); ?>
        <?php endif; ?>
    </p>
    <h3><?php echo htmlspecialchars($judul_doc); ?></h3>
</div>
