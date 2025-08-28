<?php  
			$txtkdbarang= $_POST['txtcaribrg'];
			$txtqty= $_POST['txtqty'];
            $txtpot= $_POST['txtpot'];
            
            $tgl_pilih= $_POST['id-date-picker-1'];
            $nopelanggan=$_POST['txtkey'];
            $nmpelanggan=$_POST['txtnmpelanggan'];
            $cbosales=$_POST['cbosales'];
            
            if($txtkdbarang<>'') {
            // -- Cek Stok ------------
                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                saldo 
                                                FROM 
                                                view_stok_master 
                                                WHERE 
                                                kd_cabang='$kd_cabang' and  
                                                no_item='$txtkdbarang'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $stok_akhir=$tm_cari['saldo'];				                                                                                                                                         
            // ---------------------
            
                if($stok_akhir=='0') {
                    // Pesan Stok Kosong
                    echo"<script>window.alert('Stok Barang kosong!');
                    window.location=('pesanan_penjualan_add_rst.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&kd=$txtkdbarang');</script>";			                                                                        
                } else {
                    if($txtqty>$stok_akhir) {
                        // Pesan Stok tidak cukup
                        echo"<script>window.alert('Stok Barang tidak mencukupi!');
                        window.location=('pesanan_penjualan_add_rst.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&kd=$txtkdbarang');</script>";			                                                                        
                    } else {
                        $stok_proses=$stok_akhir-$txtqty;
                        if($stok_proses<0) {
                            // Pesan Stok tidak cukup                        
                            echo"<script>window.alert('Stok Barang tidak mencukupi!');
                            window.location=('pesanan_penjualan_add_rst.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&kd=$txtkdbarang');</script>";			                                                                        
                        } else {
                            // Baru Proses Simpan Data
                            
                            // Cek dulu sudah pernah tersimpan belum
                            $data = mysqli_query($koneksi,"SELECT * FROM tblorderjual_detail 
                                                                WHERE 
                                                                user='$_nama' and kd_cabang='$kd_cabang' 
                                                                and no_item='$txtkdbarang' and 
                                                                status_trx='0'");
                            $cek = mysqli_num_rows($data);                            
                            if($cek > 0){
                                $kdbrg="";
                                echo"<script>window.alert('Item Barang sudah ada!');
                                window.location=('pesanan_penjualan_add_rst.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&kd=$kdbrg');</script>";			                                                                        
                            } else {
                                // 1. Mencari Harga Jual ========
                                $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                hargajual, hargajual2, hargajual3, 
                                                                hjqtyd2, hjqtyd3, hjqtys1, hjqtys2 
                                                                FROM 
                                                                tblitem 
                                                                WHERE 
                                                                noitem='$txtkdbarang'");			
                                $tm_cari=mysqli_fetch_array($cari_kd);
                                $txtharga_ke1=$tm_cari['hargajual'];        
                                $txtharga_ke2=$tm_cari['hargajual2'];        
                                $txtharga_ke3=$tm_cari['hargajual3']; 

                                // 2. Mengambil Qty Barang yang diinput ========
                                $txtqty_ke1=$tm_cari['hjqtys1'];
                                $txtqty_ke2=$tm_cari['hjqtys2'];
                                $txtqty_ke3=$tm_cari['hjqtyd3'];                                   

                                // 3. Mencari Harga Jual berdasarkan jumlah item ========
                                if($txtqty<=$txtqty_ke1) {
                                    $txthargabarang=$txtharga_ke1;
                                } else {
                                    if($txtqty_ke1<=$txtqty_ke2) {
                                        $txthargabarang=$txtharga_ke2;                      
                                    } else {
                                        $txthargabarang=$txtharga_ke3;                    
                                    }
                                }                            

                                // 4. Menghitung Sub Total ========  
                                $subtotal=($txthargabarang*$txtqty)-(($txthargabarang*$txtqty)*($txtpot/100));                          

                                // 5. Simpan Ke Tabel Detail ========  
                                mysqli_query($koneksi,"INSERT INTO tblorderjual_detail 
                                                (no_order, no_item, harga_jual, quantity, 
                                                potongan, total, user, kd_cabang) 
                                                VALUES 
                                                ('', '$txtkdbarang','$txthargabarang',
                                                '$txtqty','$txtpot','$subtotal',
                                                '$_nama','$kd_cabang')");                                  
                            }
                        }
                    }     
                }
            } else {
                echo"<script>window.alert('Belum ada item barang yang dipilih!');
                window.location=('pesanan_penjualan_add_rst.php?stgl=$tgl_pilih&ssup=$nopelanggan&ssales=$cbosales&kd=$txtkdbarang');</script>";			                                                                                        
            }

            // == Total dari Item Barang ==============
            $cari_kd=mysqli_query($koneksi,"SELECT 
                                                sum(total) as tot, 
                                                sum(quantity) as tot_qty_jual 
                                                FROM tblorderjual_detail 
                                                WHERE 
                                                user='$_nama' and 
                                                kd_cabang='$kd_cabang' and 
                                                status_trx='0'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $tot=$tm_cari['tot'];                 
            $total_qty_order=$tm_cari['tot_qty_jual'];                                 
            $txtcaribrg = "";
            $txtnamaitem= "";        
?>