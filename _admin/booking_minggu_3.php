                                        <tr>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            15<br>
                                            <?php  
                                                $date=$thn_skr."/".$bulan_skr."/15"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            16<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/16"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            17<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/17"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            18<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/18"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>                                        
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            19<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/19"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            20<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/20"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            21<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/21"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/15";
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
                                                $date=$thn_skr."/".$bulan_skr."/16";
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
                                                $date=$thn_skr."/".$bulan_skr."/17";
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
                                                $date=$thn_skr."/".$bulan_skr."/18";
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
                                                $date=$thn_skr."/".$bulan_skr."/19";
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
                                                $date=$thn_skr."/".$bulan_skr."/20";
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
                                                $date=$thn_skr."/".$bulan_skr."/21";
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