<?php  
    $baris_bgcolor="beige";
    $baris_bgcolor1="yellow";
?>

        <table id="dynamic-table" class="table table-bordered">
            <tr>
                <td width="18%"><font size="2"><b>Pelanggan</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $kode_pelanggan; ?></font></td>

                <td width="18%"><font size="2"><b>Warna</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $warna; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>Nama</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $namapelanggan; ?></font></td>

                <td width="18%"><font size="2"><b>No. Rangka</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $no_rangka; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>No. Polisi</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $no_polisi; ?></font></td>

                <td width="18%"><font size="2"><b>No. Mesin</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $no_mesin; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>Pemilik</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $pemilik; ?></font></td>

                <td width="18%" align="right" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>Tgl / Jam&nbsp;</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><?php echo $tanggal; ?> / <?php echo $jam; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>Jenis</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $jenis; ?></font></td>

                <td width="18%" align="right" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>User&nbsp;</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><?php echo $_nama; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>Merk/Tipe</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor; ?>"><font size="2"><?php echo $merek; ?></font></td>

                <td width="18%" align="right" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>Cabang&nbsp;</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor1; ?>"><font size="2"><?php echo $nama_cabang; ?></font></td>
            </tr>
            <tr>
                <td width="18%"><font size="2"><b>Status Servis</b></font></td>
                <td width="2%" align="center"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="lightblue">
                    <font size="2">
                        <select name="cbostatus" id="cbostatus" class="form-control input-sm">
                            <option value="datang" <?php echo ($status_servis=='datang')?'selected':''; ?>>Datang</option>
                            <option value="diproses" <?php echo ($status_servis=='diproses')?'selected':''; ?>>Di Proses</option>
                            <option value="selesai" <?php echo ($status_servis=='selesai')?'selected':''; ?>>Selesai</option>
                            <option value="bayar" <?php echo ($status_servis=='bayar')?'selected':''; ?>>Bayar/Serah Terima</option>
                        </select>
                    </font>
                </td>
                <td colspan="3" align="center">
                    <button class="btn btn-success btn-xs" type="submit" name="btnupdatestatus">
                        Update Status
                    </button>
                </td>
            </tr>
        </table>

        <!-- SPK Section -->
        <?php include "_template/_servis_spk_unified.php"; ?>

        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Service</h4>
                    <?php include "_template/_servis_add_jemput_detail_servis.php"; ?> 
                </div>
            </div>
        </div>
        
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Barang</h4>
                    <?php include "_template/_servis_add_jemput_detail_barang.php"; ?> 
                </div>
            </div>
        </div>
        
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <?php include "_template/_servis_add_jemput_total.php"; ?> 
                </div>
            </div>
        </div>