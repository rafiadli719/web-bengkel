<table class="table table-bordered">
            <tr>
                <td width="25%">
                    <label>Kode Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-8">
                            <input type="text" class="form-control input-sm"
                            id="txtcarisrv" name="txtcarisrv"
                            value="<?php echo $txtcarisrv; ?>" autocomplete="off" />
                        </div>
                        <div class="col-xs-4 col-sm-4">
                            <button class="btn btn-primary btn-sm" type="submit"
                            id="btncarisrv" name="btncarisrv">
                                Cari
                            </button>
                        </div>
                    </div>
                </td>
                <td width="40%">
                    <label>Nama Service :</label>
                    <input type="text" class="form-control input-sm"
                    value="<?php echo $txtnamasrv; ?>" readonly="true" />
                </td>
                <td width="15%">
                    <label>Pot. % :</label>
                    <input type="text" class="form-control input-sm"
                    id="txtpotsrv" name="txtpotsrv" value="0" autocomplete="off" />
                </td>
                <td width="20%">
                    <label>&nbsp;</label><br>
                    <button class="btn btn-success btn-sm btn-block" type="submit"
                    id="btnaddsrv" name="btnaddsrv">
                        + Service
                    </button>
                </td>
            </tr>
        </table>

        <table class="table table-bordered table-striped">
            <thead>
                <tr class="info">
                    <th width="5%" class="center">No</th>
                    <th width="15%">Kode</th>
                    <th width="35%">Nama Service</th>
                    <th width="10%" class="center">Waktu</th>
                    <th width="12%" class="center">Harga</th>
                    <th width="10%" class="center">Diskon</th>
                    <th width="10%" class="center">Total</th>
                    <th width="5%" class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $no = 0;
                    $total_service_detail = 0;
                    $total_waktu_detail = 0;
                    $sql = mysqli_query($koneksi,"SELECT 
                                                    id, no_item, waktu, harga, potongan, total,
                                                    diskon_source, diskon_persen, diskon_nominal, id_promo
                                                    FROM tblservis_jasa 
                                                    WHERE no_service='$no_service'
                                                    ORDER BY id ASC");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no++;
                        $total_service_detail += $tampil['total'];
                        $total_waktu_detail += $tampil['waktu'];
                        
                        // Get service name
                        if($tampil['no_item'] == 'JEMPUT-ANTAR') {
                            // Special handling for pickup fee
                            $query_pickup_info = mysqli_query($koneksi,"SELECT jarak_jemput, kondisi_motor FROM tblservice WHERE no_service='$no_service'");
                            if($pickup_info = mysqli_fetch_array($query_pickup_info)) {
                                $jarak = $pickup_info['jarak_jemput'] ?? 0;
                                $kondisi = $pickup_info['kondisi_motor'] == 'mogok' ? 'Mogok' : 'Jalan';
                                $nama_service = "JEMPUT ANTAR MOTOR ($jarak KM - Motor $kondisi)";
                            } else {
                                $nama_service = "JEMPUT ANTAR MOTOR";
                            }
                        } else {
                            $get_service = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='{$tampil['no_item']}'");
                            $service_data = mysqli_fetch_array($get_service);
                            $nama_service = $service_data['nama_wo'] ?? $tampil['no_item'];
                        }
                ?>
                <tr id="row-jasa-<?php echo $tampil['id']; ?>">
                    <td class="center"><?php echo $no; ?></td>
                    <td><?php echo $tampil['no_item']; ?></td>
                    <td><?php echo $nama_service; ?></td>
                    <td class="center"><?php echo $tampil['waktu']; ?> menit</td>
                    <td class="right">
                        <!-- Harga bisa diedit dengan klik -->
                        <span class="harga-display" id="harga-display-<?php echo $tampil['id']; ?>"
                              onclick="editHargaJasa(<?php echo $tampil['id']; ?>, <?php echo $tampil['harga']; ?>, <?php echo $tampil['potongan']; ?>)"
                              style="cursor: pointer; border-bottom: 1px dashed #999;"
                              title="Klik untuk edit harga">
                            <?php echo number_format($tampil['harga'], 0, ',', '.'); ?>
                            <i class="fa fa-pencil" style="font-size: 10px; color: #999;"></i>
                        </span>
                        <div class="harga-edit" id="harga-edit-<?php echo $tampil['id']; ?>" style="display: none;">
                            <input type="number" class="form-control input-sm"
                                   id="harga-input-<?php echo $tampil['id']; ?>"
                                   value="<?php echo $tampil['harga']; ?>"
                                   style="width: 120px; display: inline-block;">
                            <button class="btn btn-success btn-xs" onclick="saveHargaJasa(<?php echo $tampil['id']; ?>, '<?php echo $no_service; ?>')">
                                <i class="fa fa-check"></i>
                            </button>
                            <button class="btn btn-default btn-xs" onclick="cancelEditHargaJasa(<?php echo $tampil['id']; ?>)">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </td>
                    <td class="center">
                        <?php 
                        // Determine display based on source
                        $d_source = $tampil['diskon_source'] ?? 'none';
                        $d_persen = $tampil['diskon_persen'] ?? 0;
                        $d_nominal = $tampil['diskon_nominal'] ?? 0;
                        
                        // Fallback for legacy data (potongan column)
                        if ($d_source == 'none' && $tampil['potongan'] > 0) {
                            $d_source = 'manual';
                            $d_persen = $tampil['potongan'];
                        }
                        
                        if ($d_source == 'promo') {
                            $tipe_disc = ($d_persen > 0) ? 'persen' : 'nominal';
                            $val_disc = ($d_persen > 0) ? $d_persen : $d_nominal;
                            echo '<span class="label label-success label-white middle" style="font-size: 10px;">PROMO</span><br>';
                            echo ($tipe_disc == 'persen') ? $val_disc.'%' : 'Rp'.number_format($val_disc,0,',','.');
                        } elseif ($d_source == 'member') {
                            echo '<span class="label label-info label-white middle" style="font-size: 10px;">MEMBER</span><br>';
                            echo $d_persen.'%';
                        } elseif ($d_source == 'manual') {
                            echo $d_persen.'%';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td class="right">
                        <span id="total-display-<?php echo $tampil['id']; ?>">
                            <?php echo number_format($tampil['total'], 0, ',', '.'); ?>
                        </span>
                    </td>
                    <td class="center">
                        <a class="red" data-rel="tooltip" title="Delete"
                        href="servis-jasa-hapus.php?jid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>"
                        onclick="return confirm('Item service akan dihapus. Lanjutkan?')">
                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                    if($no == 0) {
                ?>
                <tr>
                    <td colspan="8" class="center"><em>Belum ada service yang diinput</em></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="info">
                    <th colspan="3" class="center">TOTAL SERVICE</th>
                    <th class="center"><?php echo $total_waktu_detail; ?> menit</th>
                    <th colspan="2"></th>
                    <th class="right"><?php echo number_format($total_service_detail, 0, ',', '.'); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <script>
        // ====================================================
        // EDIT HARGA JASA INLINE
        // ====================================================
        function editHargaJasa(jasaId, currentHarga, currentPotongan) {
            console.log('Edit harga jasa:', jasaId, currentHarga, currentPotongan);

            // Hide display, show edit form
            document.getElementById('harga-display-' + jasaId).style.display = 'none';
            document.getElementById('harga-edit-' + jasaId).style.display = 'block';

            // Focus on input
            document.getElementById('harga-input-' + jasaId).focus();
            document.getElementById('harga-input-' + jasaId).select();
        }

        function cancelEditHargaJasa(jasaId) {
            // Hide edit form, show display
            document.getElementById('harga-edit-' + jasaId).style.display = 'none';
            document.getElementById('harga-display-' + jasaId).style.display = 'inline';
        }

        function saveHargaJasa(jasaId, noService) {
            const newHarga = document.getElementById('harga-input-' + jasaId).value;

            if(!newHarga || newHarga < 0) {
                alert('Harga harus diisi dengan nilai yang valid!');
                return;
            }

            // Show loading
            const btnSave = event.target.closest('button');
            const originalHtml = btnSave.innerHTML;
            btnSave.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            btnSave.disabled = true;

            // Send AJAX request
            fetch('ajax-update-harga-jasa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'jasa_id=' + jasaId + '&harga=' + newHarga + '&no_service=' + noService
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Update display
                    const formatter = new Intl.NumberFormat('id-ID');
                    document.getElementById('harga-display-' + jasaId).innerHTML =
                        formatter.format(newHarga) + ' <i class="fa fa-pencil" style="font-size: 10px; color: #999;"></i>';
                    document.getElementById('total-display-' + jasaId).textContent =
                        formatter.format(data.new_total);

                    // Hide edit form, show display
                    cancelEditHargaJasa(jasaId);

                    // Show success message
                    alert('✅ Harga berhasil diupdate!\n\nHarga Baru: Rp ' + formatter.format(newHarga) + '\nTotal Baru: Rp ' + formatter.format(data.new_total));

                    // Reload page to update grand total
                    location.reload();
                } else {
                    alert('❌ Gagal update harga: ' + (data.message || 'Unknown error'));
                    btnSave.innerHTML = originalHtml;
                    btnSave.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Terjadi error saat update harga: ' + error);
                btnSave.innerHTML = originalHtml;
                btnSave.disabled = false;
            });
        }

        // Allow Enter key to save
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[id^="harga-input-"]').forEach(function(input) {
                input.addEventListener('keypress', function(e) {
                    if(e.key === 'Enter') {
                        e.preventDefault();
                        const jasaId = this.id.replace('harga-input-', '');
                        const noService = '<?php echo $no_service; ?>';
                        saveHargaJasa(jasaId, noService);
                    }
                });
            });
        });
        </script>