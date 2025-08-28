                                        <tr>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            8<br>
                                            <?php  
                                                $date=$thn_skr."/".$bulan_skr."/08"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            9<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/09"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            10<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/10"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            11<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/11"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>                                        
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            12<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/12"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            13<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/13"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            14<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/14"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/08";
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
                                                $date=$thn_skr."/".$bulan_skr."/09";
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
                                                $date=$thn_skr."/".$bulan_skr."/10";
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
                                                $date=$thn_skr."/".$bulan_skr."/11";
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
                                                $date=$thn_skr."/".$bulan_skr."/12";
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
                                                $date=$thn_skr."/".$bulan_skr."/13";
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
                                                $date=$thn_skr."/".$bulan_skr."/14";
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