<div class="col-xs-12 col-sm-12">
    <div class="table-header">
        List Faktur Penjualan - <span id="selected-count" style="color: #87B87F;">0 faktur dipilih</span>
    </div>
    <table class="table table-bordered" id="tbl-piutang">
        <thead>
            <tr>
                <td align="center" bgcolor="gainsboro" width="5%">
                    <input type="checkbox" id="check-all" title="Centang Semua">
                </td>
                <td bgcolor="gainsboro" width="15%"><b>No. Transaksi</b></td>
                <td bgcolor="gainsboro" align="center" width="10%"><b>Tanggal</b></td>
                <td bgcolor="gainsboro" align="right" width="10%"><b>Sub Total</b></td> 
                <td bgcolor="gainsboro" align="right" width="10%"><b>Pot. Faktur</b></td>                                                             
                <td bgcolor="gainsboro" align="right" width="10%"><b>Pajak</b></td>
                <td bgcolor="gainsboro" align="right" width="15%"><b>Total Netto</b></td>  
                <td bgcolor="gainsboro" align="right" width="15%"><b>Pembayaran</b></td>
                <td bgcolor="gainsboro" align="right" width="10%"><b>Kekurangan</b></td>                                                                
            </tr>
        </thead>
        <tbody>
        <?php 
            $sql = mysqli_query($koneksi,$sql_cari);
            while ($tampil = mysqli_fetch_array($sql)) {
                $pembayaran=$tampil['pembayaran'];
                $total_akhir=$tampil['total_akhir'];
                $kekurangan=$total_akhir-$pembayaran;
        ?>
            <tr class="invoice-row">
                <td class="center">
                    <input type="checkbox" class="invoice-checkbox" name="hapus[]" 
                    value="<?php echo $tampil['notransaksi']; ?>" 
                    data-kekurangan="<?php echo $kekurangan; ?>">
                </td>
                <td><?php echo $tampil['notransaksi']?></td>														
                <td class="center"><?php echo $tampil['tanggal_trx']?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_jual'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_diskon'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_pajak'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_akhir'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['pembayaran'],0)?></td>
                <td bgcolor="red" align="right"><font color="white"><?php echo number_format($kekurangan,0)?></font></td>														                                                                                                                
            </tr>
        <?php
            }
        ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #D9EDF7; font-weight: bold;">
                <td colspan="8" align="right">TOTAL YANG AKAN DIBAYAR:</td>
                <td align="right" id="total-bayar" style="font-size: 16px; color: #3A87AD;">
                    Rp 0
                </td>
            </tr>
        </tfoot>
    </table>
</div>

<script>
jQuery(function($) {
    // Auto-calculate total pembayaran
    function updateTotal() {
        let total = 0;
        let count = 0;
        
        $('.invoice-checkbox:checked').each(function() {
            const kekurangan = parseFloat($(this).data('kekurangan')) || 0;
            total += kekurangan;
            count++;
            
            // Highlight selected row
            $(this).closest('tr').css('background-color', '#DFF0D8');
        });
        
        // Remove highlight from unchecked rows
        $('.invoice-checkbox:not(:checked)').each(function() {
            $(this).closest('tr').css('background-color', '');
        });
        
        // Update total display
        $('#total-bayar').html('Rp ' + total.toLocaleString('id-ID'));
        
        // Update selected count
        $('#selected-count').html(count + ' faktur dipilih');
        
        // Enable/disable submit button
        if(count > 0) {
            $('#btnsimpan').removeClass('disabled').prop('disabled', false);
        } else {
            $('#btnsimpan').addClass('disabled').prop('disabled', true);
        }
    }
    
    // Check all functionality
    $('#check-all').on('click', function() {
        $('.invoice-checkbox').prop('checked', this.checked);
        updateTotal();
    });
    
    // Individual checkbox change
    $('.invoice-checkbox').on('change', function() {
        updateTotal();
        
        // Update "check all" state
        const total = $('.invoice-checkbox').length;
        const checked = $('.invoice-checkbox:checked').length;
        $('#check-all').prop('checked', total === checked);
    });
    
    // Initial state
    updateTotal();
    
    // Validate before submit
    $('form').on('submit', function(e) {
        const checked = $('.invoice-checkbox:checked').length;
        if(checked === 0) {
            e.preventDefault();
            alert('Silahkan pilih minimal 1 faktur untuk dibayar!');
            return false;
        }
        return true;
    });
});
</script>