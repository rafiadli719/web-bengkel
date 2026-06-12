<div class="col-xs-12 col-sm-12">
    <div class="table-header">
        List Faktur Pembelian - <span id="selected-count" style="color: #87B87F;">0 faktur dipilih</span>
    </div>
    <table class="table table-bordered" id="tbl-hutang">
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
                $pembayaran = (float)$tampil['pembayaran'];
                $total_beli = (float)$tampil['total_beli'];
                $total_diskon = (float)$tampil['total_diskon'];
                $total_pajak = (float)$tampil['total_pajak'];
                $total_akhir = $total_beli - $total_diskon + $total_pajak;
                $kekurangan = $total_akhir - $pembayaran;
                if ($kekurangan < 0) {
                    $kekurangan = 0;
                }
        ?>
            <tr class="invoice-row">
                <td class="center">
                    <input type="checkbox" class="invoice-checkbox" name="hapus[]" 
                    value="<?php echo $tampil['notransaksi']; ?>" 
                    data-kekurangan="<?php echo $kekurangan; ?>">
                </td>
                <td><?php echo $tampil['notransaksi']?></td>														
                <td class="center"><?php echo $tampil['tanggal_trx']?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_beli'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_diskon'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($tampil['total_pajak'],0)?></td>														                                                        
                <td align="right"><?php echo number_format($total_akhir,0)?></td>														                                                        
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
document.addEventListener('DOMContentLoaded', function() {
    function getCheckedInvoices() {
        return Array.prototype.slice.call(document.querySelectorAll('.invoice-checkbox:checked'));
    }

    function updateTotal() {
        var total = 0;
        var checked = getCheckedInvoices();

        checked.forEach(function(el) {
            var kekurangan = parseFloat(el.getAttribute('data-kekurangan')) || 0;
            total += kekurangan;
            var tr = el.closest('tr');
            if (tr) tr.style.backgroundColor = '#DFF0D8';
        });

        Array.prototype.slice.call(document.querySelectorAll('.invoice-checkbox:not(:checked)')).forEach(function(el) {
            var tr = el.closest('tr');
            if (tr) tr.style.backgroundColor = '';
        });

        var totalEl = document.getElementById('total-bayar');
        if (totalEl) totalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');

        var countEl = document.getElementById('selected-count');
        if (countEl) countEl.textContent = checked.length + ' faktur dipilih';

        var btn = document.getElementById('btnsimpan');
        if (btn) {
            btn.disabled = (checked.length === 0);
            if (btn.disabled) btn.classList.add('disabled');
            else btn.classList.remove('disabled');
        }
    }

    var checkAll = document.getElementById('check-all');
    if (checkAll) {
        checkAll.addEventListener('click', function() {
            Array.prototype.slice.call(document.querySelectorAll('.invoice-checkbox')).forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
            updateTotal();
        });
    }

    Array.prototype.slice.call(document.querySelectorAll('.invoice-checkbox')).forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateTotal();

            var all = document.querySelectorAll('.invoice-checkbox').length;
            var checked = document.querySelectorAll('.invoice-checkbox:checked').length;
            if (checkAll) checkAll.checked = (all > 0 && all === checked);
        });
    });

    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var submitter = e.submitter || document.activeElement;
            if (submitter && submitter.id && submitter.id !== 'btnsimpan') {
                return true;
            }
            var checked = document.querySelectorAll('.invoice-checkbox:checked').length;
            if (checked === 0) {
                e.preventDefault();
                alert('Silahkan pilih minimal 1 faktur untuk dibayar!');
                return false;
            }
            return true;
        });
    }

    updateTotal();
});
</script>