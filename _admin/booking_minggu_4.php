                                        <tr>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            22<br>
                                            <?php  
                                                $date=$thn_skr."/".$bulan_skr."/22"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            23<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/23"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            24<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/24"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            25<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/25"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>                                        
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            26<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/26"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            27<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/27"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            28<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/28"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/22";
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
                                                $date=$thn_skr."/".$bulan_skr."/23";
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
                                                $date=$thn_skr."/".$bulan_skr."/24";
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
                                                $date=$thn_skr."/".$bulan_skr."/25";
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
                                                $date=$thn_skr."/".$bulan_skr."/26";
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
                                                $date=$thn_skr."/".$bulan_skr."/27";
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
                                                $date=$thn_skr."/".$bulan_skr."/28";
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