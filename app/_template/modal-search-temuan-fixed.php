<!-- Modal Search Temuan - Fixed for Ace Admin -->
<div id="modalSearchTemuan" class="modal fade" tabindex="-1">
    <div class="modal-dialog" style="width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">
                    <i class="ace-icon fa fa-search blue"></i>
                    Cari Temuan
                </h4>
            </div>

            <div class="modal-body">
                <!-- Search Box -->
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchTemuan" placeholder="Cari temuan...">
                        <span class="input-group-btn">
                            <button class="btn btn-primary btn-sm" type="button">
                                <i class="ace-icon fa fa-search"></i>
                            </button>
                        </span>
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
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
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
                                    case 'rendah': $badge_class = 'label-success'; break;
                                    case 'sedang': $badge_class = 'label-warning'; break;
                                    case 'tinggi': $badge_class = 'label-danger'; break;
                                    case 'kritis': $badge_class = 'label-inverse'; break;
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
                                <td><span class="label label-info"><?php echo $temuan['kategori']; ?></span></td>
                                <td><span class="label <?php echo $badge_class; ?>"><?php echo strtoupper($temuan['tingkat_urgensi']); ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-xs btn-pilih-temuan" 
                                            data-kode="<?php echo $temuan['kode_temuan']; ?>"
                                            data-nama="<?php echo $temuan['nama_temuan']; ?>"
                                            data-kategori="<?php echo $temuan['kategori']; ?>"
                                            data-urgensi="<?php echo $temuan['tingkat_urgensi']; ?>">
                                        <i class="ace-icon fa fa-check"></i> Pilih
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Input Manual -->
                <div class="space-8"></div>
                <div class="alert alert-info">
                    <i class="ace-icon fa fa-info-circle"></i> Tidak menemukan temuan yang sesuai? 
                    <button type="button" class="btn btn-info btn-xs" id="btnInputManualTemuan">
                        Input Manual
                    </button>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-sm" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Manual Temuan -->
<div id="modalInputManualTemuan" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="ace-icon fa fa-edit blue"></i>
                    Input Temuan Manual
                </h4>
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
                            <option value="kritis">Kritis</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control" id="deskripsiTemuanManual" rows="3"></textarea>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-sm" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    Batal
                </button>
                <button class="btn btn-sm btn-primary" id="btnSimpanTemuanManual">
                    <i class="ace-icon fa fa-check"></i>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(function($) {
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

    // Pilih temuan
    $(document).on('click', '.btn-pilih-temuan', function() {
        var kode = $(this).data('kode');
        var nama = $(this).data('nama');
        var kategori = $(this).data('kategori');
        var urgensi = $(this).data('urgensi');
        
        // Set values
        $('#kode_temuan').val(kode);
        $('#nama_temuan').val(nama);
        
        // Close modal
        $('#modalSearchTemuan').modal('hide');
    });

    // Input manual temuan
    $('#btnInputManualTemuan').on('click', function() {
        $('#modalSearchTemuan').modal('hide');
        setTimeout(function() {
            $('#modalInputManualTemuan').modal('show');
        }, 300);
    });

    // Simpan temuan manual
    $('#btnSimpanTemuanManual').on('click', function() {
        var nama = $('#namaTemuanManual').val();
        var kategori = $('#kategoriTemuanManual').val();
        var urgensi = $('#urgensiTemuanManual').val();
        var deskripsi = $('#deskripsiTemuanManual').val();
        
        if(!nama) {
            alert('Nama temuan harus diisi!');
            return;
        }
        
        // Set values
        $('#kode_temuan').val('');
        $('#nama_temuan').val(nama);
        
        // Close modal
        $('#modalInputManualTemuan').modal('hide');
        
        // Reset form
        $('#formInputManualTemuan')[0].reset();
    });
});
</script>
