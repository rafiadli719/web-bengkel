            // == Total dari Item Barang ==============
            $cari_kd=mysqli_query($koneksi,"SELECT 
                                                sum(total) as tot, 
                                                sum(qty_order) as tot_qty_order, 
                                                sum(quantity) as tot_qty_jual, 
                                                sum(qty_retur) as tot_qty_retur, 
                                                FROM tblpenjualan_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $tot=$tm_cari['tot'];                 
            $total_qty_order=$tm_cari['tot_qty_order'];                 
            $total_qty_beli=$tm_cari['tot_qty_jual'];                                 
            $tot_qty_retur=$tm_cari['tot_qty_retur'];                                             