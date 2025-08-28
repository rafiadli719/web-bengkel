                                        <tr>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            1<br>
                                            <?php  
                                                $date=$thn_skr."/".$bulan_skr."/01"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            2<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/02"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            3<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/03"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            4<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/04"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>                                        
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            5<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/05"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            6<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/06"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                            <td bgcolor=" gainsboro" width="10%" align="center">
                                            7<br>
                                                                                        <?php  
                                                $date=$thn_skr."/".$bulan_skr."/07"; 
                                                $namahari = date('l', strtotime($date)); 
                                                echo $daftar_hari[$namahari];                                                 
                                            ?>
                                            </td>
                                        </tr>
                                        <tr valign="top">
                                            <td width="10%" align="center">
                                            <br>
                                            <?php 
                                                $date=$thn_skr."/".$bulan_skr."/01";
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
                                                $date=$thn_skr."/".$bulan_skr."/02";
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
                                                $date=$thn_skr."/".$bulan_skr."/03";
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
                                                $date=$thn_skr."/".$bulan_skr."/04";
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
                                                $date=$thn_skr."/".$bulan_skr."/05";
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
                                                $date=$thn_skr."/".$bulan_skr."/06";
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
                                                $date=$thn_skr."/".$bulan_skr."/07";
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