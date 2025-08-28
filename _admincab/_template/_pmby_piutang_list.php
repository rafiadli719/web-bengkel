<div class="col-xs-12 col-sm-12">
    <div class="table-header">
        List Faktur Penjualan
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <td align="center" bgcolor="gainsboro" width="5%"></td>
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
            <tr>
                <td class="center"><input type="checkbox" name="hapus[]" value="<?php echo $tampil['notransaksi']; ?>"></td>
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
    </table>
</div>