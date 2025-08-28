<?php  
            $no_service= $_POST['txtnosrv'];			
            $km_skr=$_POST['txtkm_skr'];
            $km_berikut=$_POST['txtkm_next'];           
            
			$txtkdbarang= $_POST['txtcaribrg'];
			$txtqty= $_POST['txtqty'];
			$txtpot= $_POST['txtpot'];

			$txtcarisrv= $_POST['txtcarisrv'];
            
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
                    window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv');</script>";			                                                    
                } else {
                    if($txtqty>$stok_akhir) {
                        // Pesan Stok tidak cukup
                        echo"<script>window.alert('Stok Barang tidak mencukupi!');
                        window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv');</script>";			                                                    
                    } else {
                        $stok_proses=$stok_akhir-$txtqty;
                        if($stok_proses<0) {
                            // Pesan Stok tidak cukup                        
                            echo"<script>window.alert('Stok Barang tidak mencukupi!');
                            window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv');</script>";			                                                    
                        } else {
                            // Baru Proses Simpan Data
                            
                            // Cek dulu sudah pernah tersimpan belum
                            $data = mysqli_query($koneksi,"SELECT * FROM tblservis_barang 
                                                                WHERE 
                                                                no_service='$no_service' and 
                                                                no_item='$txtkdbarang'");
                            $cek = mysqli_num_rows($data);                            
                            if($cek > 0){
                                $txtkdbarang="";
                                echo"<script>window.alert('Item Barang sudah ada!');
                                window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv');</script>";			                                                    
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
                                mysqli_query($koneksi,"INSERT INTO tblservis_barang 
                                        (no_service, no_item, harga_jual, quantity, 
                                        potongan, total) 
                                        VALUES 
                                        ('$no_service', '$txtkdbarang','$txthargabarang','$txtqty',
                                        '$txtpot','$subtotal')");
                            }
                        }
                    }     
                }
            } else {
                echo"<script>window.alert('Belum ada item barang yang dipilih!');
                    window.location=('servis-input-reguler-rst.php?snoserv=$no_service&kd=$txtkdbarang&kdjasa=$txtcarisrv');</script>";			                                                    
            }

            // == Total dari Item Barang ==============
            $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot 
                                                FROM tblservis_barang 
                                                WHERE 
                                                no_service='$no_service'");			
            $tm_cari=mysqli_fetch_array($cari_kd);
            $total_barang=$tm_cari['tot']; 

            // == Total dari Item & Waktu Service ==============
                $cari_kd=mysqli_query($koneksi,"SELECT sum(total) as tot, 
                                                sum(waktu) as tot_waktu 
                                                FROM tblservis_jasa 
                                                WHERE 
                                                no_service='$no_service'");			
                $tm_cari=mysqli_fetch_array($cari_kd);
                $total_service=$tm_cari['tot']; 
                $total_waktu=$tm_cari['tot_waktu']; 

            $tot=$total_service+$total_barang;
            $net=$tot;
            $bayar=$tot;
            $kembalian=$bayar-$net;                    
                
            $txtcaribrg = "";
            $txtnamaitem= "";        
?>