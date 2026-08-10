                    $sql = mysqli_query($koneksi,"SELECT * FROM tblorderjual_detail 
                                                WHERE no_order='$nopesanan'");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no_item=$tampil['no_item'];
                        $txthargabarang=$tampil['harga_jual'];
                        $txtqty=$tampil['quantity']; 
                        $txtpot=$tampil['potongan'];
                        $subtotal=$tampil['total'];                       

                        mysqli_query($koneksi,"INSERT INTO tblpenjualan_detail 
                                                (no_transaksi, no_item, harga_jual, 
                                                quantity, qty_order, potongan, total, 
                                                user, kd_cabang) 
                                                VALUES 
                                                ('$LastID', '$no_item','$txthargabarang',
                                                '$txtqty','$txtqty','$txtpot','$subtotal',
                                                '$_nama','$kd_cabang')");                          
                    }