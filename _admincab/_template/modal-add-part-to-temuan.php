<?php
/**
 * Modal: Tambah Part ke Temuan
 * File: modal-add-part-to-temuan.php
 * 
 * Modal untuk menambahkan part baru ke temuan yang sudah ada
 * Menggunakan style yang konsisten dengan redesign UI
 */
?>

<style>
/* Modal Add Part to Temuan - Redesign Style */
#modalAddPartToTemuan .modal-dialog {
    width: 90%;
    max-width: 900px;
}

#modalAddPartToTemuan .modal-header {
    background: linear-gradient(135deg, #4A90D9, #357ABD);
    color: white;
    border-radius: 6px 6px 0 0;
    padding: 15px 20px;
}

#modalAddPartToTemuan .modal-header .close {
    color: white;
    opacity: 0.9;
}

#modalAddPartToTemuan .modal-title {
    font-size: 16px;
    font-weight: 600;
}

#modalAddPartToTemuan .modal-body {
    max-height: 60vh;
    overflow-y: auto;
    padding: 20px;
}

#modalAddPartToTemuan .search-box {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

#modalAddPartToTemuan .search-box input {
    flex: 1;
    padding: 10px 14px;
    border: 1px solid #E9ECEF;
    border-radius: 6px;
    font-size: 14px;
}

#modalAddPartToTemuan .search-box button {
    padding: 10px 20px;
    background: #4A90D9;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

#modalAddPartToTemuan .search-box button:hover {
    background: #357ABD;
}

#modalAddPartToTemuan .part-list-container {
    border: 1px solid #E9ECEF;
    border-radius: 8px;
    overflow: hidden;
}

#modalAddPartToTemuan .part-list-container table {
    width: 100%;
    margin: 0;
    border-collapse: collapse;
    font-size: 13px;
}

#modalAddPartToTemuan .part-list-container thead {
    background: #F8F9FA;
}

#modalAddPartToTemuan .part-list-container th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #333;
    border-bottom: 1px solid #E9ECEF;
}

#modalAddPartToTemuan .part-list-container td {
    padding: 12px 16px;
    border-bottom: 1px solid #F1F3F4;
    vertical-align: middle;
}

#modalAddPartToTemuan .part-list-container tr:hover {
    background: #F8F9FA;
}

#modalAddPartToTemuan .btn-add-part {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: #5CB85C;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

#modalAddPartToTemuan .btn-add-part:hover {
    background: #4CAF50;
}

#modalAddPartToTemuan .btn-add-part:disabled {
    background: #E9ECEF;
    cursor: not-allowed;
}

#modalAddPartToTemuan .qty-input {
    width: 60px;
    text-align: center;
    padding: 6px 8px;
    border: 1px solid #E9ECEF;
    border-radius: 4px;
}

#modalAddPartToTemuan .stok-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

#modalAddPartToTemuan .stok-badge.success {
    background: rgba(92, 184, 92, 0.15);
    color: #5CB85C;
}

#modalAddPartToTemuan .stok-badge.danger {
    background: rgba(217, 83, 79, 0.15);
    color: #D9534F;
}

#modalAddPartToTemuan .empty-state {
    padding: 40px 20px;
    text-align: center;
    color: #6C757D;
}

#modalAddPartToTemuan .empty-state i {
    font-size: 36px;
    margin-bottom: 12px;
    opacity: 0.5;
}

#modalAddPartToTemuan .temuan-info {
    background: #E8F4FD;
    border: 1px solid #B8DAEC;
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

#modalAddPartToTemuan .temuan-info i {
    color: #4A90D9;
}

#modalAddPartToTemuan .temuan-info strong {
    color: #333;
}
</style>

<div class="modal fade" id="modalAddPartToTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fa fa-plus-circle"></i> Tambah Part ke Temuan
                </h4>
            </div>
            
            <div class="modal-body">
                <!-- Info Temuan -->
                <div class="temuan-info">
                    <i class="fa fa-info-circle"></i>
                    <span>Temuan: <strong id="add_part_temuan_nama">-</strong></span>
                </div>
                <input type="hidden" id="modal_add_part_temuan_id" value="">
                
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" id="search_part_input" placeholder="Cari part berdasarkan kode atau nama...">
                    <button type="button" id="btn_search_part">
                        <i class="fa fa-search"></i> Cari
                    </button>
                </div>
                
                <!-- Part List -->
                <div class="part-list-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Part</th>
                                <th>Satuan</th>
                                <th class="text-right">Harga</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Qty</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="add_part_list_body">
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fa fa-search"></i>
                                        <p style="margin: 0;">Ketik kata kunci dan klik Cari untuk mencari part</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
                    <i class="fa fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Wait for jQuery
    function initAddPartModal() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initAddPartModal, 100);
            return;
        }
        var $ = jQuery;
        
        // Search on enter key
        $('#search_part_input').on('keypress', function(e) {
            if (e.which === 13) {
                $('#btn_search_part').click();
            }
        });
        
        // Search button click
        $('#btn_search_part').on('click', function() {
            var query = $('#search_part_input').val().trim();
            if (query.length < 2) {
                alert('Minimal 2 karakter untuk pencarian');
                return;
            }
            searchPartsForTemuan(query);
        });
        
        // Search parts function
        function searchPartsForTemuan(query) {
            var tbody = $('#add_part_list_body');
            tbody.html('<tr><td colspan="7" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mencari...</td></tr>');
            
            $.ajax({
                url: '_handler_temuan_penawaran.php',
                type: 'GET',
                data: {
                    action: 'search_part',
                    q: query
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.length > 0) {
                        var html = '';
                        $.each(response, function(i, item) {
                            var stokClass = parseInt(item.stok_tersedia || 0) > 0 ? 'success' : 'danger';
                            var stokVal = item.stok_tersedia || 0;
                            
                            html += '<tr>';
                            html += '<td><code>' + item.kode_barang + '</code></td>';
                            html += '<td>' + item.nama_barang + '</td>';
                            html += '<td>' + (item.satuan || 'PCS') + '</td>';
                            html += '<td class="text-right"><strong>Rp ' + parseInt(item.harga_jual || 0).toLocaleString('id-ID') + '</strong></td>';
                            html += '<td class="text-center"><span class="stok-badge ' + stokClass + '">' + stokVal + '</span></td>';
                            html += '<td class="text-center"><input type="number" class="qty-input" value="1" min="1"></td>';
                            html += '<td>';
                            html += '<button type="button" class="btn-add-part" ';
                            html += 'data-kode="' + item.kode_barang + '" ';
                            html += 'data-nama="' + escapeHtml(item.nama_barang) + '" ';
                            html += 'data-harga="' + (item.harga_jual || 0) + '">';
                            html += '<i class="fa fa-plus"></i>';
                            html += '</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        tbody.html(html);
                    } else {
                        tbody.html('<tr><td colspan="7"><div class="empty-state"><i class="fa fa-inbox"></i><p style="margin:0;">Tidak ada hasil ditemukan</p></div></td></tr>');
                    }
                },
                error: function() {
                    tbody.html('<tr><td colspan="7" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Gagal memuat data</td></tr>');
                }
            });
        }
        
        // Add part button click
        $(document).on('click', '#modalAddPartToTemuan .btn-add-part', function() {
            var btn = $(this);
            var row = btn.closest('tr');
            var temuanId = $('#modal_add_part_temuan_id').val();
            var kode = btn.data('kode');
            var nama = btn.data('nama');
            var harga = btn.data('harga');
            var qty = row.find('.qty-input').val() || 1;
            
            if (!temuanId) {
                alert('Error: ID temuan tidak ditemukan');
                return;
            }
            
            // Disable button
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            
            // Submit via AJAX
            $.ajax({
                url: '_handler_temuan_penawaran.php',
                type: 'POST',
                data: {
                    action: 'add_part_to_temuan',
                    temuan_id: temuanId,
                    kode_barang: kode,
                    nama_barang: nama,
                    harga_satuan: harga,
                    quantity: qty
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        btn.html('<i class="fa fa-check"></i>').removeClass('btn-add-part').css('background', '#5CB85C');
                        
                        // Reload page after short delay
                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    } else {
                        alert('Error: ' + (response.message || 'Gagal menambahkan part'));
                        btn.prop('disabled', false).html('<i class="fa fa-plus"></i>');
                    }
                },
                error: function() {
                    alert('Error: Gagal menghubungi server');
                    btn.prop('disabled', false).html('<i class="fa fa-plus"></i>');
                }
            });
        });
        
        // Reset on modal close
        $('#modalAddPartToTemuan').on('hidden.bs.modal', function() {
            $('#search_part_input').val('');
            $('#add_part_list_body').html('<tr><td colspan="7"><div class="empty-state"><i class="fa fa-search"></i><p style="margin: 0;">Ketik kata kunci dan klik Cari untuk mencari part</p></div></td></tr>');
        });
        
        // Helper: Escape HTML
        function escapeHtml(text) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }
    }
    
    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAddPartModal);
    } else {
        initAddPartModal();
    }
})();
</script>
