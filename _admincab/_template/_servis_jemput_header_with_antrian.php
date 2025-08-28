<?php  
    $baris_bgcolor="beige";
    $baris_bgcolor1="yellow";
    $baris_bgcolor2="lightblue";
    
    // Ambil data antrian jika ada
    $no_antrian = "";
    $status_antrian = "";
    $prioritas = "normal";
    $estimasi_waktu = "";
    $catatan_antrian = "";
    
    if(!empty($no_service)) {
        $query_antrian = "SELECT * FROM tb_antrian_servis WHERE no_service = '$no_service'";
        $result_antrian = mysqli_query($koneksi, $query_antrian);
        if($result_antrian && mysqli_num_rows($result_antrian) > 0) {
            $antrian_data = mysqli_fetch_array($result_antrian);
            $no_antrian = $antrian_data['no_antrian'];
            $status_antrian = $antrian_data['status_antrian'];
            $prioritas = $antrian_data['prioritas'];
            $estimasi_waktu = $antrian_data['estimasi_waktu'];
            $catatan_antrian = $antrian_data['catatan'];
        }
    }
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
            
            <!-- Baris Nomor Antrian -->
            <tr>
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>No. Antrian</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor2; ?>">
                    <div class="input-group">
                        <input type="text" name="txtno_antrian" id="txtno_antrian" 
                               class="form-control input-sm" value="<?php echo $no_antrian; ?>" 
                               placeholder="Nomor Antrian" readonly>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-primary btn-sm" id="btnGenerateAntrian">
                                <i class="fa fa-refresh"></i> Generate
                            </button>
                        </span>
                    </div>
                </td>
                
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>Prioritas</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor2; ?>">
                    <select name="cboprioritas" id="cboprioritas" class="form-control input-sm">
                        <option value="normal" <?php echo ($prioritas=='normal')?'selected':''; ?>>Normal</option>
                        <option value="urgent" <?php echo ($prioritas=='urgent')?'selected':''; ?>>Urgent</option>
                        <option value="vip" <?php echo ($prioritas=='vip')?'selected':''; ?>>VIP</option>
                    </select>
                </td>
            </tr>
            
            <!-- Baris Estimasi Waktu dan Status -->
            <tr>
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>Estimasi Waktu</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor2; ?>">
                    <div class="input-group">
                        <input type="number" name="txtestimasi_waktu" id="txtestimasi_waktu" 
                               class="form-control input-sm" value="<?php echo $estimasi_waktu; ?>" 
                               placeholder="0" min="0">
                        <span class="input-group-addon">menit</span>
                    </div>
                </td>
                
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>Status Antrian</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor2; ?>">
                    <select name="cbostatus_antrian" id="cbostatus_antrian" class="form-control input-sm">
                        <option value="menunggu" <?php echo ($status_antrian=='menunggu')?'selected':''; ?>>Menunggu</option>
                        <option value="diproses" <?php echo ($status_antrian=='diproses')?'selected':''; ?>>Di Proses</option>
                        <option value="selesai" <?php echo ($status_antrian=='selesai')?'selected':''; ?>>Selesai</option>
                        <option value="batal" <?php echo ($status_antrian=='batal')?'selected':''; ?>>Batal</option>
                    </select>
                </td>
            </tr>
            
            <!-- Baris Catatan Antrian -->
            <tr>
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>Catatan Antrian</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
                <td width="30%" bgcolor="<?php echo $baris_bgcolor2; ?>">
                    <input type="text" name="txtcatatan_antrian" id="txtcatatan_antrian" 
                           class="form-control input-sm" value="<?php echo $catatan_antrian; ?>" 
                           placeholder="Catatan khusus untuk antrian ini">
                </td>
                
                <td width="18%" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>Status Servis</b></font></td>
                <td width="2%" align="center" bgcolor="<?php echo $baris_bgcolor2; ?>"><font size="2"><b>:</b></font></td>
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
            </tr>
            
            <!-- Baris Tombol Update -->
            <tr>
                <td colspan="6" align="center" bgcolor="lightblue">
                    <button class="btn btn-success btn-xs" type="submit" name="btnupdatestatus">
                        Update Status
                    </button>
                    <button class="btn btn-primary btn-xs" type="button" id="btnSaveAntrian">
                        <i class="fa fa-save"></i> Simpan Antrian
                    </button>
                </td>
            </tr>
        </table>

        <!-- Hanya Service dan Barang, WorkOrder dihilangkan -->
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Service</h4>
                    <?php include "_template/_servis_add_detail_servis.php"; ?> 
                </div>
            </div>
        </div>
        
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <h4 class="header green">Barang</h4>
                    <?php include "_template/_servis_add_detail_barang.php"; ?> 
                </div>
            </div>
        </div>
        
        <div class="widget-box">
            <div class="widget-body">
                <div class="widget-main">
                    <?php include "_template/_servis_add_total.php"; ?> 
                </div>
            </div>
        </div>

<script>
$(document).ready(function() {
    // Generate nomor antrian
    $('#btnGenerateAntrian').click(function() {
        $.ajax({
            url: '_ajax/ajax-generate-antrian.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#txtno_antrian').val(response.no_antrian);
                    alert('Nomor antrian berhasil di-generate: ' + response.no_antrian);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat generate nomor antrian');
            }
        });
    });
    
    // Simpan antrian
    $('#btnSaveAntrian').click(function() {
        var no_antrian = $('#txtno_antrian').val();
        var no_service = '<?php echo $no_service; ?>';
        var prioritas = $('#cboprioritas').val();
        var estimasi_waktu = $('#txtestimasi_waktu').val();
        var catatan = $('#txtcatatan_antrian').val();
        
        if(!no_antrian) {
            alert('Silakan generate nomor antrian terlebih dahulu');
            return;
        }
        
        $.ajax({
            url: '_ajax/ajax-save-antrian.php',
            type: 'POST',
            data: {
                no_antrian: no_antrian,
                no_service: no_service,
                prioritas: prioritas,
                estimasi_waktu: estimasi_waktu,
                catatan: catatan
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Antrian berhasil disimpan');
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menyimpan antrian');
            }
        });
    });
    
    // Update status antrian saat status servis berubah
    $('#cbostatus_antrian').change(function() {
        var status_baru = $(this).val();
        var no_service = '<?php echo $no_service; ?>';
        
        if(no_service && status_baru) {
            $.ajax({
                url: '_ajax/ajax-update-status-antrian.php',
                type: 'POST',
                data: {
                    no_service: no_service,
                    status_baru: status_baru
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Update status servis juga
                        $('#cbostatus').val(status_baru);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan saat update status');
                }
            });
        }
    });
});
</script>
