<!-- Modal Fast Moves Part -->
<div class="modal fade" id="modalFastMovesPart" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="width: 90%; max-width: 1200px;" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fa fa-bolt"></i> Fast Moves - Pilih Part Cepat
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php
                // Check if $koneksi is available
                if (!isset($koneksi)) {
                    echo '<div class="alert alert-danger">';
                    echo '<i class="fa fa-exclamation-triangle"></i> ';
                    echo '<strong>Error:</strong> Koneksi database tidak tersedia. ';
                    echo 'Pastikan file ini di-include setelah koneksi database dibuat.';
                    echo '</div>';
                    echo '</div></div></div></div>'; // Close modal
                    return;
                }
                ?>

                <!-- Kategori Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group-toggle" data-toggle="buttons">
                            <button class="btn btn-outline-primary btn-kategori-fm active" data-kategori="">
                                <i class="fa fa-th"></i> Semua
                            </button>
                            <?php
                            $query_kategori = mysqli_query($koneksi, "SELECT * FROM tbmaster_kategori_fastmoves WHERE is_active = 1 ORDER BY urutan");
                            if ($query_kategori) {
                                while($kat = mysqli_fetch_array($query_kategori)) {
                            ?>
                            <button class="btn btn-outline-primary btn-kategori-fm" data-kategori="<?php echo $kat['kode_kategori']; ?>">
                                <i class="fa <?php echo $kat['icon']; ?>"></i> <?php echo $kat['nama_kategori']; ?>
                            </button>
                            <?php
                                } // end while kategori
                            } else {
                                echo '<button class="btn btn-outline-secondary disabled">Kategori tidak tersedia</button>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Search Box -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchFastMoves" placeholder="Cari kode atau nama part...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="showOnlyStock">
                            <label class="custom-control-label" for="showOnlyStock">
                                Tampilkan yang ada stok saja
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tabel Part -->
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th width="120">Kode Part</th>
                                <th>Nama Part</th>
                                <th width="100">Kategori</th>
                                <th width="80">Satuan</th>
                                <th width="120" class="text-right">Harga</th>
                                <th width="80" class="text-center">Stok</th>
                                <th width="100" class="text-center">Qty</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="fastMovesTableBody">
                            <?php
                            // Query dengan fallback jika tabel tidak ada
                            $query_fm = @mysqli_query($koneksi, "
                                SELECT
                                    kfm.kode_kategori,
                                    kfm.nama_kategori,
                                    kfm.icon,
                                    mbf.kode_barang,
                                    item.namaitem as nama_barang,
                                    item.hargajual as harga_jual,
                                    item.satuan,
                                    0 as stok_tersedia,
                                    mbf.is_featured,
                                    mbf.urutan
                                FROM tbmaster_kategori_fastmoves kfm
                                INNER JOIN tbmaster_barang_fastmoves mbf ON kfm.kode_kategori = mbf.kode_kategori
                                INNER JOIN tblitem item ON mbf.kode_barang = item.noitem
                                WHERE kfm.is_active = 1
                                ORDER BY kfm.urutan, mbf.urutan, item.namaitem
                            ");

                            if ($query_fm && mysqli_num_rows($query_fm) > 0) {
                                while($item = mysqli_fetch_array($query_fm)) {
                                $stok_class = $item['stok_tersedia'] > 0 ? 'text-success' : 'text-danger';
                                $featured_badge = $item['is_featured'] ? '<span class="badge badge-warning badge-sm">Featured</span> ' : '';
                            ?>
                            <tr class="fm-row" 
                                data-kategori="<?php echo $item['kode_kategori']; ?>"
                                data-stok="<?php echo $item['stok_tersedia']; ?>"
                                data-kode="<?php echo $item['kode_barang']; ?>"
                                data-nama="<?php echo $item['nama_barang']; ?>"
                                data-harga="<?php echo $item['harga_jual']; ?>"
                                data-satuan="<?php echo $item['satuan']; ?>">
                                <td>
                                    <strong><?php echo $item['kode_barang']; ?></strong>
                                    <?php if($item['is_featured']) { ?>
                                    <br><span class="badge badge-warning badge-sm">★ Featured</span>
                                    <?php } ?>
                                </td>
                                <td><?php echo $item['nama_barang']; ?></td>
                                <td>
                                    <span class="badge badge-info badge-sm">
                                        <i class="fa <?php echo $item['icon']; ?>"></i> <?php echo $item['nama_kategori']; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['satuan']; ?></td>
                                <td class="text-right">
                                    <strong>Rp <?php echo number_format($item['harga_jual'], 0, ',', '.'); ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $item['stok_tersedia'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $item['stok_tersedia']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <input type="number" class="form-control form-control-sm qty-input" 
                                           value="1" min="1" max="<?php echo ($item['stok_tersedia']>0 ? $item['stok_tersedia'] : 999); ?>"
                                           style="width: 70px;">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success btn-tambah-fm">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php
                                } // end while items
                            } else {
                                // Jika tidak ada data atau query gagal
                                ?>
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px;">
                                        <i class="fa fa-exclamation-triangle fa-3x text-warning"></i>
                                        <h5 style="margin-top: 15px;">Tidak Ada Data Fast Moves</h5>
                                        <p class="text-muted">
                                            <?php
                                            if (!$query_fm) {
                                                echo 'Error query: ' . mysqli_error($koneksi);
                                                echo '<br><small>Pastikan tabel tbmaster_kategori_fastmoves, tbmaster_barang_fastmoves, dan tblitem sudah ada.</small>';
                                            } else {
                                                echo 'Belum ada data part di Fast Moves.<br>';
                                                echo 'Silakan tambahkan data melalui menu Master Data.';
                                            }
                                            ?>
                                        </p>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Info -->
                <div class="mt-3">
                    <div class="alert alert-info mb-0">
                        <i class="fa fa-info-circle"></i>
                        <strong>Tips:</strong> Klik kategori untuk filter, atau gunakan search untuk mencari part spesifik.
                        Part yang ditambahkan akan masuk ke form Penawaran Part.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Wait until jQuery is available, then initialize
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initFastMoves);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initFastMoves($) {
    if (!$ || typeof $.fn === 'undefined') { $ = window.jQuery; }
    // Filter kategori
    $('.btn-kategori-fm').on('click', function() {
        $('.btn-kategori-fm').removeClass('active');
        $(this).addClass('active');
        
        var kategori = $(this).data('kategori');
        if(kategori === '') {
            $('.fm-row').show();
        } else {
            $('.fm-row').hide();
            $('.fm-row[data-kategori="' + kategori + '"]').show();
        }
    });

    // Search
    $('#searchFastMoves').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.fm-row').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Show only stock
    $('#showOnlyStock').on('change', function() {
        if($(this).is(':checked')) {
            $('.fm-row').each(function() {
                var stok = parseInt($(this).data('stok'));
                if(stok <= 0) {
                    $(this).hide();
                }
            });
        } else {
            var kategori = $('.btn-kategori-fm.active').data('kategori');
            if(kategori === '') {
                $('.fm-row').show();
            } else {
                $('.fm-row').hide();
                $('.fm-row[data-kategori="' + kategori + '"]').show();
            }
        }
    });

    // Tambah part ke penawaran
    $(document).on('click', '.btn-tambah-fm', function() {
        var row = $(this).closest('tr');
        var kode = row.data('kode');
        var nama = row.data('nama');
        var harga = row.data('harga');
        var satuan = row.data('satuan');
        var qty = row.find('.qty-input').val();
        var stok = row.data('stok');
        
        // Diizinkan menambahkan meskipun stok 0 (akan menjadi indent/pending)
        
        // Callback ke form parent
        if(typeof window.onFastMovesPartSelected === 'function') {
            window.onFastMovesPartSelected(kode, nama, harga, satuan, qty);
        }
        
        // Visual feedback
        $(this).html('<i class="fa fa-check"></i>').prop('disabled', true);
        setTimeout(function() {
            row.find('.btn-tambah-fm').html('<i class="fa fa-plus"></i>').prop('disabled', false);
        }, 1000);
    });
}
</script>

<style>
.btn-kategori-fm {
    margin: 2px;
    border-radius: 20px;
}
.btn-kategori-fm.active {
    background-color: #007bff;
    color: white;
}
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
