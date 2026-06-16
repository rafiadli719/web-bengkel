<?php
// File: _template/_stok_keluar_detail.php
// Template yang disesuaikan dengan kode PHP yang sudah diperbarui
?>

<!-- Form untuk Pencarian dan Tambah Item -->
<form class="form-horizontal" action="" method="post" role="form" id="formAddItem">
    <input type="hidden" name="id-date-picker-1" value="<?php echo $tgl_pilih; ?>"/>
    
    <div class="widget-box">
        <div class="widget-header">
            <h4 class="widget-title">
                <i class="ace-icon fa fa-search"></i>
                Cari & Tambah Item
            </h4>
        </div>
        <div class="widget-body">
            <div class="widget-main">
                <div class="row">
                    <div class="col-xs-12 col-sm-3">
                        <div class="form-group">
                            <label class="control-label">Ketik Kode/Nama Item:</label>
                            <div class="input-group">
                                <input type="text" id="txtcaribrg" name="txtcaribrg" 
                                       class="form-control" 
                                       value="<?php echo $txtcaribrg; ?>" 
                                       placeholder="Masukkan kode item..." 
                                       autocomplete="off" />
                                <span class="input-group-btn">
                                    <button type="submit" class="btn btn-purple btn-sm" 
                                            id="btncari" name="btncari" 
                                            title="Cari Item (F2)">
                                        <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-sm-4">
                        <div class="form-group">
                            <label class="control-label">Nama Barang:</label>
                            <input type="text" id="txtnamabrg" name="txtnamabrg" 
                                   class="form-control" 
                                   value="<?php echo $txtnamaitem; ?>" 
                                   readonly 
                                   style="background-color: #f5f5f5;" />
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-sm-2">
                        <div class="form-group">
                            <label class="control-label">Quantity:</label>
                            <input type="number" id="txtqty" name="txtqty" 
                                   class="form-control" 
                                   value="" 
                                   min="1" 
                                   step="1" 
                                   placeholder="0" 
                                   autocomplete="off" 
                                   <?php echo empty($txtcaribrg) ? 'disabled' : ''; ?> />
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-sm-2">
                        <div class="form-group">
                            <label class="control-label">&nbsp;</label>
                            <button type="submit" 
                                    class="btn btn-primary btn-block" 
                                    id="btnadd" name="btnadd"
                                    title="Tambah Item (F3)"
                                    <?php echo (empty($txtcaribrg) || empty($txtnamaitem)) ? 'disabled' : ''; ?>>
                                <i class="ace-icon fa fa-plus"></i> ADD
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-xs-12 col-sm-1">
                        <div class="form-group">
                            <label class="control-label">&nbsp;</label>
                            <button type="button" 
                                    class="btn btn-warning btn-block" 
                                    id="btnclear" 
                                    title="Clear Form (ESC)">
                                <i class="ace-icon fa fa-eraser"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Info Status -->
                <div class="row">
                    <div class="col-xs-12">
                        <?php if(empty($txtcaribrg)): ?>
                        <div class="alert alert-info" id="itemStatus">
                            <i class="fa fa-info-circle"></i>
                            <strong>Cara Menambah Item:</strong>
                            <ol style="margin: 5px 0 0 20px;">
                                <li>Masukkan kode barang dan klik <strong>CARI</strong> atau tekan <strong>Enter</strong></li>
                                <li>Setelah item ditemukan, masukkan <strong>Quantity</strong></li>
                                <li>Klik tombol <strong>ADD</strong> untuk menambahkan ke detail</li>
                                <li>Ulangi untuk item lain, lalu klik <strong>SIMPAN</strong></li>
                            </ol>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($txtcaribrg) && !empty($txtnamaitem)): ?>
                        <div class="alert alert-success" id="itemFound">
                            <i class="fa fa-check-circle"></i>
                            <strong>Item Ditemukan:</strong> <?php echo $txtnamaitem; ?>
                            <br><small>Masukkan quantity dan klik ADD untuk menambahkan ke detail transaksi.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="space space-8"></div>

<!-- Tabel Detail Items -->
<div class="widget-box">
    <div class="widget-header">
        <h4 class="widget-title">
            <i class="ace-icon fa fa-list"></i>
            Detail Items (Total: Rp <?php echo number_format($tot, 0, ',', '.'); ?>)
        </h4>
        <div class="widget-toolbar">
            <?php
            // Hitung jumlah item
            $sql_count = mysqli_query($koneksi,"SELECT COUNT(*) as jml FROM tbitem_keluar_detail 
                                                WHERE user='$_nama' and kd_cabang='$kd_cabang' and status_trx='0'");
            $count_data = mysqli_fetch_array($sql_count);
            $total_items = $count_data['jml'];
            ?>
            <span class="badge badge-info"><?php echo $total_items; ?> Items</span>
        </div>
    </div>
    <div class="widget-body">
        <div class="widget-main no-padding">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th class="center" width="5%">Aksi</th>
                            <th class="center" width="5%">No</th>
                            <th width="15%">Kode Item</th>
                            <th width="40%">Nama Item</th>
                            <th class="center" width="10%">Qty</th>
                            <th class="right" width="12%">Harga</th>
                            <th class="right" width="13%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $no = 0;
                            $subtotal_all = 0;
                            $sql = mysqli_query($koneksi,"SELECT 
                                                        d.id, d.no_item, d.quantity, d.harga, d.total, i.namaitem
                                                        FROM tbitem_keluar_detail d
                                                        LEFT JOIN tblitem i ON d.no_item = i.noitem
                                                        WHERE 
                                                        d.user='$_nama' and 
                                                        d.kd_cabang='$kd_cabang' and 
                                                        d.status_trx='0'
                                                        ORDER BY d.id DESC");
            
                            if(mysqli_num_rows($sql) > 0) {
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    $no++;
                                    $subtotal_all += $tampil['total'];
                        ?>
                        <tr>
                            <td class="center">
                                <div class="btn-group">
                                    <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow" title="Pilih Aksi">
                                        <span class="ace-icon fa fa-cog"></span>
                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-default">
                                        <li>
                                            <a href="stok_keluar_edit_item.php?sid=<?php echo $tampil['id']; ?>&stgl=<?php echo $tgl_pilih; ?>">
                                                <i class="ace-icon fa fa-edit"></i> Edit Item
                                            </a>
                                        </li>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="stok_keluar_hapus_item.php?sid=<?php echo $tampil['id']; ?>&stgl=<?php echo $tgl_pilih; ?>" 
                                               onclick="return confirm('Item \"<?php echo addslashes($tampil['namaitem']); ?>\" akan dihapus dari detail transaksi.\n\nLanjutkan?')"
                                               class="red">
                                                <i class="ace-icon fa fa-trash"></i> Hapus Item
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                            <td class="center"><?php echo $no; ?></td>
                            <td>
                                <strong><?php echo $tampil['no_item']; ?></strong>
                            </td>
                            <td><?php echo $tampil['namaitem']; ?></td>
                            <td class="center">
                                <span class="badge badge-info"><?php echo number_format($tampil['quantity'], 0); ?></span>
                            </td>
                            <td class="right"><?php echo number_format($tampil['harga'], 0, ',', '.'); ?></td>
                            <td class="right">
                                <strong><?php echo number_format($tampil['total'], 0, ',', '.'); ?></strong>
                            </td>
                        </tr>
                        <?php
                                }
                            } else {
                        ?>
                        <tr>
                            <td colspan="7" class="center text-muted" style="padding: 30px;">
                                <i class="ace-icon fa fa-inbox fa-2x"></i>
                                <br><br>
                                <em>Belum ada item yang ditambahkan ke detail transaksi.</em>
                                <br><small>Gunakan form di atas untuk mencari dan menambah item.</small>
                            </td>
                        </tr>
                        <?php
                            }
                        ?>
                    </tbody>
                    
                    <?php if($total_items > 0): ?>
                    <tfoot>
                        <tr class="info">
                            <th colspan="6" class="right">
                                <strong>GRAND TOTAL:</strong>
                            </th>
                            <th class="right">
                                <strong style="font-size: 14px;">
                                    Rp <?php echo number_format($subtotal_all, 0, ',', '.'); ?>
                                </strong>
                            </th>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript untuk Form Handling -->
<script type="text/javascript">
$(document).ready(function() {
    // Auto-focus management yang disesuaikan dengan kode PHP
    var txtcaribrg = $('#txtcaribrg').val();
    var txtnamabrg = $('#txtnamabrg').val();
    
    <?php if (empty($txtcaribrg)): ?>
        $('#txtcaribrg').focus();
    <?php elseif (!empty($txtcaribrg) && !empty($txtnamaitem)): ?>
        $('#txtqty').focus();
    <?php endif; ?>
    
    // Form validation untuk ADD - disesuaikan dengan validasi PHP
    $('#formAddItem').on('submit', function(e) {
        var submitBtn = $(document.activeElement).attr('name');
        
        if(submitBtn === 'btnadd') {
            var kode = $('#txtcaribrg').val().trim();
            var qty = parseFloat($('#txtqty').val()) || 0;
            
            if(!kode) {
                e.preventDefault();
                alert('❌ Silakan cari item terlebih dahulu!');
                $('#txtcaribrg').focus();
                return false;
            }
            
            if(qty <= 0) {
                e.preventDefault();
                alert('❌ Quantity harus lebih dari 0!');
                $('#txtqty').focus().select();
                return false;
            }
            
            // Konfirmasi sebelum ADD
            if(!confirm('➕ Tambah item ke detail transaksi?\n\nKode: ' + kode + '\nQuantity: ' + qty)) {
                e.preventDefault();
                return false;
            }
        }
        
        if(submitBtn === 'btncari') {
            var kode = $('#txtcaribrg').val().trim();
            if(!kode) {
                e.preventDefault();
                alert('❌ Masukkan kode barang terlebih dahulu!');
                $('#txtcaribrg').focus();
                return false;
            }
        }
    });
    
    // Clear button functionality
    $('#btnclear').on('click', function() {
        if(confirm('🗑️ Clear form input item?')) {
            window.location.href = 'stok_keluar_add.php?stgl=<?php echo $tgl_pilih; ?>';
        }
    });
    
    // Keyboard shortcuts yang disesuaikan
    $(document).on('keydown', function(e) {
        // Enter pada txtcaribrg = submit pencarian
        if(e.target.id === 'txtcaribrg' && e.keyCode === 13) {
            e.preventDefault();
            $('#btncari').click();
        }
        
        // Enter pada txtqty = submit add
        if(e.target.id === 'txtqty' && e.keyCode === 13) {
            e.preventDefault();
            $('#btnadd').click();
        }
        
        // F2: Focus ke pencarian item
        if (e.keyCode == 113) { // F2
            e.preventDefault();
            $('#txtcaribrg').focus().select();
            return false;
        }
        
        // F3: Focus ke quantity
        if (e.keyCode == 114) { // F3
            e.preventDefault();
            $('#txtqty').focus().select();
            return false;
        }
        
        // Esc: Clear form
        if (e.keyCode == 27) { // Esc
            e.preventDefault();
            $('#btnclear').click();
            return false;
        }
    });
    
    // Validasi quantity untuk mencegah input negatif
    $('#txtqty').on('input', function() {
        var value = parseFloat(this.value);
        if (value < 0) {
            this.value = '';
            alert('❌ Quantity tidak boleh negatif!');
        }
    });
    
    // Enable/disable controls based on search result
    $('#txtcaribrg').on('input', function() {
        var value = $(this).val().trim();
        if(!value) {
            $('#txtnamabrg').val('');
            $('#txtqty').val('').prop('disabled', true);
            $('#btnadd').prop('disabled', true);
        }
    });
    
    // Update button states berdasarkan kondisi
    function updateButtonStates() {
        var hasCode = $('#txtcaribrg').val().trim() !== '';
        var hasName = $('#txtnamabrg').val().trim() !== '';
        
        $('#txtqty').prop('disabled', !hasCode || !hasName);
        $('#btnadd').prop('disabled', !hasCode || !hasName);
    }
    
    // Initial state update
    updateButtonStates();
    
    // Monitor changes
    $('#txtcaribrg, #txtnamabrg, #txtqty').on('input change', function() {
        updateButtonStates();
    });
    
    // Auto-update total jika diperlukan
    setInterval(function() {
        // Update hidden input total untuk form simpan
        var currentTotal = <?php echo $tot; ?>;
        $('input[name="txttotal_harga"]').val(currentTotal);
    }, 1000);

    // Highlight row yang baru ditambahkan
    $('table tbody tr:first-child').addClass('success').delay(3000).queue(function(){
        $(this).removeClass('success').dequeue();
    });
    
    console.log('🔧 Template Detail loaded successfully');
    console.log('Current item search:', '<?php echo $txtcaribrg; ?>');
    console.log('Current item name:', '<?php echo $txtnamaitem; ?>');
    console.log('Current total:', <?php echo $tot; ?>);
});
</script>

<!-- CSS untuk styling tambahan -->
<style>
.table-responsive {
    border: none;
}

.btn-minier {
    padding: 1px 6px;
    font-size: 10px;
    line-height: 1.1;
}

.alert {
    margin-bottom: 10px;
}

.badge {
    font-size: 11px;
}

.form-group {
    margin-bottom: 15px;
}

/* Highlight untuk row yang baru ditambahkan */
.table tbody tr.success {
    background-color: #dff0d8 !important;
    animation: highlightFade 3s ease-in-out;
}

@keyframes highlightFade {
    0% { background-color: #5cb85c; }
    100% { background-color: #dff0d8; }
}

/* Styling untuk form input */
#txtcaribrg:focus {
    border-color: #66afe9;
    box-shadow: 0 0 8px rgba(102, 175, 233, 0.6);
}

#txtqty:focus {
    border-color: #66afe9;
    box-shadow: 0 0 8px rgba(102, 175, 233, 0.6);
}

/* Disabled state styling */
input:disabled {
    background-color: #f5f5f5 !important;
    opacity: 0.6;
}

button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .col-xs-12 {
        margin-bottom: 10px;
    }
    
    .table-responsive {
        font-size: 12px;
    }
    
    .btn-block {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>