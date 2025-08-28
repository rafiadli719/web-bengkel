                                        <tr>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            29<br>
                                            <?php  
                                                $date=$thn_skr."/".$bulan_skr."/29"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            30<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/30"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            31<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/31"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/29";
                                                $data = mysqli_query($koneksi,"SELECT jam FROM tb_booking 
                                                                                WHERE 
                                                                                tanggal='$date'");
                                                $cek = mysqli_num_rows($data);
                                                if($cek > 0){
                                            ?>
                                                <table width="100%">
                                                    <?php 
                                                        $sql = mysqli_query($koneksi,"SELECT * FROM tb_booking 
                                                                                    WHERE 
                                                                                    tanggal='$date'");
                                                        while ($tampil = mysqli_fetch_array($sql)) {                                                                                                                            
                                                    ?>
                                                    <tr>
                                                        <td align="center">
                                                        <?php echo $tampil['jam']?>
                                                        <br>
                                                        <?php echo $tampil['nama']?>
                                                        <br>&nbsp;
                                                        </td>
                                                    </tr>                                                    
                                                    <?php  
                                                        }
                                                    ?>
                                                </table>
                                            <?php
                                                } else {
                                            ?>
                                                &nbsp;<br>&nbsp;<br>&nbsp;<br>
                                            <?php
                                                }		                                            
                                            ?>
                                            </td>
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/30";
                                                $data = mysqli_query($koneksi,"SELECT jam FROM tb_booking 
                                                                                WHERE 
                                                                                tanggal='$date'");
                                                $cek = mysqli_num_rows($data);
                                                if($cek > 0){
                                            ?>
                                                <table width="100%">
                                                    <?php 
													$sql = mysqli_query($koneksi,"SELECT * FROM tb_booking 
                                                                                WHERE 
                                                                                tanggal='$date'");
													while ($tampil = mysqli_fetch_array($sql)) {
												?>
                                                    <tr>
                                                        <td align="center">
                                                        <?php echo $tampil['jam']?>
                                                        <br>
                                                        <?php echo $tampil['nama']?>
                                                        <br>&nbsp;
                                                        </td>
                                                    </tr>
                                                    
                                                    <?php  
                                                    }
                                                    ?>
                                                </table>
                                            <?php
                                                } else {
                                            ?>
                                            &nbsp;<br>&nbsp;<br>&nbsp;<br>
                                            <?php
                                                }		                                            
                                            ?>
                                            </td>
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/31";
                                                $data = mysqli_query($koneksi,"SELECT jam FROM tb_booking 
                                                                                WHERE 
                                                                                tanggal='$date'");
                                                $cek = mysqli_num_rows($data);
                                                if($cek > 0){
                                            ?>
                                                <table width="100%">
                                                    <?php 
													$sql = mysqli_query($koneksi,"SELECT * FROM tb_booking 
                                                                                WHERE 
                                                                                tanggal='$date'");
													while ($tampil = mysqli_fetch_array($sql)) {
												?>
                                                    <tr>
                                                        <td align="center">
                                                        <?php echo $tampil['jam']?>
                                                        <br>
                                                        <?php echo $tampil['nama']?>
                                                        <br>&nbsp;
                                                        </td>
                                                    </tr>
                                                    
                                                    <?php  
                                                    }
                                                    ?>
                                                </table>
                                            <?php
                                                } else {
                                            ?>
                                            &nbsp;<br>&nbsp;<br>&nbsp;<br>
                                            <?php
                                                }		                                            
                                            ?>
                                            </td>
                                        </tr>