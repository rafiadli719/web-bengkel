<!-- Modal Search Temuan -->
<div class="modal fade" id="modalSearchTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;">
                <h5 class="modal-title" style="color:#1f2937;font-size:17px;font-weight:600;margin:0;">
                    <i class="fa fa-search" style="color:#4f46e5;margin-right:6px;"></i> Cari Temuan
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#9ca3af;opacity:1;text-shadow:none;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Box -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchTemuan" placeholder="Cari temuan...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button">
                                <i class="fa fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Kategori -->
                <div class="form-group">
                    <label>Filter Kategori:</label>
                    <select class="form-control" id="filterKategoriTemuan">
                        <option value="">-- Semua Kategori --</option>
                        <option value="Mesin">Mesin</option>
                        <option value="Kelistrikan">Kelistrikan</option>
                        <option value="Rem">Rem</option>
                        <option value="Transmisi">Transmisi</option>
                        <option value="Ban">Ban</option>
                        <option value="Suspensi">Suspensi</option>
                        <option value="Body">Body</option>
                    </select>
                </div>

                <!-- Tabel Temuan -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="100">Kode</th>
                                <th>Nama Temuan</th>
                                <th width="120">Kategori</th>
                                <th width="100">Urgensi</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="temuanTableBody">
                            <?php
                            $query_temuan = mysqli_query($koneksi, "SELECT * FROM tbmaster_temuan WHERE is_active = 1 ORDER BY kategori, nama_temuan");
                            while($temuan = mysqli_fetch_array($query_temuan)) {
                                $badge_class = '';
                                switch($temuan['tingkat_urgensi']) {
                                    case 'rendah': $badge_class = 'badge-success'; break;
                                    case 'sedang': $badge_class = 'badge-warning'; break;
                                    case 'tinggi': $badge_class = 'badge-danger'; break;
                                    case 'kritis': $badge_class = 'badge-dark'; break;
                                }
                            ?>
                            <tr class="temuan-row" data-kategori="<?php echo $temuan['kategori']; ?>">
                                <td><?php echo $temuan['kode_temuan']; ?></td>
                                <td>
                                    <strong><?php echo $temuan['nama_temuan']; ?></strong>
                                    <?php if($temuan['deskripsi']) { ?>
                                    <br><small class="text-muted"><?php echo $temuan['deskripsi']; ?></small>
                                    <?php } ?>
                                </td>
                                <td><span class="badge badge-info"><?php echo $temuan['kategori']; ?></span></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo strtoupper($temuan['tingkat_urgensi']); ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary btn-pilih-temuan" 
                                            data-kode="<?php echo $temuan['kode_temuan']; ?>"
                                            data-nama="<?php echo $temuan['nama_temuan']; ?>"
                                            data-kategori="<?php echo $temuan['kategori']; ?>"
                                            data-urgensi="<?php echo $temuan['tingkat_urgensi']; ?>">
                                        <i class="fa fa-check"></i> Pilih
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Input Manual -->
                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Tidak menemukan temuan yang sesuai? 
                        <button type="button" class="btn btn-sm btn-info ml-2" id="btnInputManualTemuan">
                            Input Manual
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Manual Temuan -->
<div class="modal fade" id="modalInputManualTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;">
                <h5 class="modal-title" style="color:#1f2937;font-size:17px;font-weight:600;margin:0;">
                    <i class="fa fa-edit" style="color:#4f46e5;margin-right:6px;"></i> Input Temuan Manual
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#9ca3af;opacity:1;text-shadow:none;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formInputManualTemuan">
                    <div class="form-group">
                        <label>Nama Temuan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="namaTemuanManual" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select class="form-control" id="kategoriTemuanManual">
                            <option value="Mesin">Mesin</option>
                            <option value="Kelistrikan">Kelistrikan</option>
                            <option value="Rem">Rem</option>
                            <option value="Transmisi">Transmisi</option>
                            <option value="Ban">Ban</option>
                            <option value="Suspensi">Suspensi</option>
                            <option value="Body">Body</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tingkat Urgensi</label>
                        <select class="form-control" id="urgensiTemuanManual">
                            <option value="rendah">Rendah</option>
                            <option value="sedang" selected>Sedang</option>
                            <option value="tinggi">Tinggi</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control" id="deskripsiTemuanManual" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanTemuanManual">
                    <i class="fa fa-save"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initModalSearchTemuan);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initModalSearchTemuan() {
    // Search temuan
    $('#searchTemuan').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#temuanTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Filter kategori
    $('#filterKategoriTemuan').on('change', function() {
        var kategori = $(this).val();
        if(kategori === '') {
            $('.temuan-row').show();
        } else {
            $('.temuan-row').hide();
            $('.temuan-row[data-kategori="' + kategori + '"]').show();
        }
    });

    // Pilih temuan - menggunakan event delegation
    $(document).on('click', '.btn-pilih-temuan', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var kode = $(this).attr('data-kode');
        var nama = $(this).attr('data-nama');
        var kategori = $(this).attr('data-kategori');
        var urgensi = $(this).attr('data-urgensi');
        
        console.log('Temuan dipilih:', {kode: kode, nama: nama, kategori: kategori, urgensi: urgensi});
        
        // Callback ke form parent
        if(typeof window.onTemuanSelected === 'function') {
            window.onTemuanSelected(kode, nama, kategori, urgensi);
            console.log('Callback onTemuanSelected dipanggil');
        } else {
            console.error('window.onTemuanSelected tidak ditemukan!');
            alert('Error: Callback function tidak ditemukan. Silakan reload halaman.');
        }
        
        $('#modalSearchTemuan').modal('hide');
    });

    // Input manual temuan
    $('#btnInputManualTemuan').on('click', function() {
        $('#modalSearchTemuan').modal('hide');
        $('#modalInputManualTemuan').modal('show');
    });

    // Simpan temuan manual (dengan validasi anti-duplikasi master)
    $('#btnSimpanTemuanManual').on('click', function(e) {
        e.preventDefault();
        
        var nama = $('#namaTemuanManual').val().trim();
        var kategori = $('#kategoriTemuanManual').val();
        var urgensi = $('#urgensiTemuanManual').val();
        var deskripsi = $('#deskripsiTemuanManual').val();
        
        if(!nama) {
            alert('Nama temuan harus diisi!');
            return;
        }
        
        // Cek duplikasi pada master temuan (exact/similar)
        $.getJSON('_handler_temuan_penawaran.php', { action: 'check_temuan_duplicate', nama_temuan: nama })
            .done(function(res){
                try { console.log('Dup-check response:', res); } catch(e) {}
                if(res && res.success && res.duplicate_found){
                    // Jika ada duplikasi di master, arahkan user memakai data master
                    if(res.match_type === 'exact' && res.data){
                        var d = res.data;
                        alert('"' + (d.nama_temuan||nama) + '" sudah ada di master temuan. Data master akan digunakan.');
                        if(typeof window.onTemuanSelected === 'function'){
                            window.onTemuanSelected(d.kode_temuan||'', d.nama_temuan||nama, kategori, urgensi);
                        } else if(typeof window.onTemuanManualCreated === 'function') {
                            // Fallback (harusnya tidak terjadi)
                            window.onTemuanManualCreated('', d.nama_temuan||nama, kategori, urgensi, deskripsi);
                        }
                        $('#modalInputManualTemuan').modal('hide');
                        $('#formInputManualTemuan')[0].reset();
                        return;
                    }
                    // Jika similar, tawarkan untuk memakai salah satu yang mirip (ambil pertama)
                    if(res.match_type === 'similar' && $.isArray(res.data) && res.data.length > 0){
                        var d0 = res.data[0];
                        var useSimilar = confirm('Nama temuan mirip sudah ada di master: ' + (d0.kode_temuan||'') + ' - ' + (d0.nama_temuan||'') + '.\nGunakan data master ini?');
                        if(useSimilar){
                            if(typeof window.onTemuanSelected === 'function'){
                                window.onTemuanSelected(d0.kode_temuan||'', d0.nama_temuan||nama, kategori, urgensi);
                            }
                            $('#modalInputManualTemuan').modal('hide');
                            $('#formInputManualTemuan')[0].reset();
                            return;
                        }
                    }
                }
                // Tidak ada duplikasi atau user memilih lanjut dengan manual
                if(typeof window.onTemuanManualCreated === 'function') {
                    window.onTemuanManualCreated('', nama, kategori, urgensi, deskripsi);
                }
                $('#modalInputManualTemuan').modal('hide');
                $('#formInputManualTemuan')[0].reset();
            })
            .fail(function(){
                // Jika cek duplikasi gagal, tetap lanjut sebagai manual (server-side handler masih dedup saat submit)
                if(typeof window.onTemuanManualCreated === 'function') {
                    window.onTemuanManualCreated('', nama, kategori, urgensi, deskripsi);
                }
                $('#modalInputManualTemuan').modal('hide');
                $('#formInputManualTemuan')[0].reset();
            });
    });
}
</script>
