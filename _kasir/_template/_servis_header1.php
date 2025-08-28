<?php  
    $baris_bgcolor="beige";
    $baris_bgcolor1="yellow";
?>

        <table id="dynamic-table" class="table table-bordered">
            <tr>
                <td width="20%"><font size="2"><b>Pelanggan</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $kode_pelanggan; ?></font></td>

                <td width="20%"><font size="2"><b>Warna</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $warna; ?></font></td>
            </tr>
            <tr>
                <td width="20%"><font size="2"><b>Nama</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $namapelanggan; ?></font></td>

                <td width="20%"><font size="2"><b>No. Rangka</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $no_rangka; ?></font></td>
            </tr>
            <tr>
                <td width="20%"><font size="2"><b>No. Polisi</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $kode_pelanggan; ?></font></td>

                <td width="20%"><font size="2"><b>No. Mesin</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $no_mesin; ?></font></td>
            </tr>
            <tr>
                <td width="20%"><font size="2"><b>Pemilik</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $pemilik; ?></font></td>

                <td colspan="5"></td>
            </tr>
            <tr>
                <td width="20%"><font size="2"><b>Jenis</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $jenis; ?></font></td>

                <td width="20%" align="right" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>User&nbsp;</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><?php echo $_nama; ?></font></td>
            </tr>
            <tr>
                <td width="20%"><font size="2"><b>Merk/Tipe</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $merek; ?></font></td>

                <td width="20%" align="right" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>Tgl / Jam&nbsp;</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>:</b></font></td>
                <td width="28%" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><?php echo $tanggal_skr; ?></font></td>
            </tr>
        </table>

        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Service</h4>
                    <?php include "_template/_servis_header2_servis.php"; ?> 
                </div>
            </div>
        </div>
        
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Barang</h4>
                    <?php include "_template/_servis_header2_barang.php"; ?> 
                </div>
            </div>
        </div>