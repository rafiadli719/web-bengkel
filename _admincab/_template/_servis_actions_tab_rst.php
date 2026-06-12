<!-- Tab Actions RST - Enhanced with Payment -->
<div class="actions-content">
    <div class="row">
        <div class="col-xs-12">
            <!-- Include Kepala Mekanik Harian Helper -->
            <?php
            include "get_kepala_mekanik_harian.php";
            $kepala_mekanik_harian = getKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
            $has_kepala_mekanik_harian = hasKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
            ?>

            <!-- Kepala Mekanik Harian Status -->
            <?php if (!$has_kepala_mekanik_harian): ?>
            <div class="alert alert-warning alert-dismissible" id="alertKepalaMetanikHarian">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="ace-icon fa fa-exclamation-triangle"></i>
                <strong>Perhatian!</strong> Belum ada input kepala mekanik untuk hari ini.
                <a href="input_kepala_mekanik_harian.php" class="btn btn-warning btn-xs" style="margin-left: 10px;">
                    <i class="ace-icon fa fa-plus"></i> Input Kepala Mekanik Harian
                </a>
                <button type="button" class="btn btn-info btn-xs" onclick="refreshKepalaMetanikHarian()" style="margin-left: 5px;">
                    <i class="ace-icon fa fa-refresh"></i> Refresh
                </button>
            </div>
            <?php else: ?>
            <div class="alert alert-success" id="alertKepalaMetanikHarian">
                <i class="ace-icon fa fa-check-circle"></i>
                <strong>Kepala Mekanik Hari Ini:</strong>
                <?php echo htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_1']); ?>
                <?php if($kepala_mekanik_harian['kepala_mekanik_2']): ?>
                & <?php echo htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_2']); ?>
                <?php endif; ?>
                <?php if (isset($kepala_mekanik_harian['shift_kerja']) && !empty($kepala_mekanik_harian['shift_kerja'])): ?>
                <span class="label label-info"><?php echo strtoupper($kepala_mekanik_harian['shift_kerja']); ?></span>
                <?php endif; ?>
                <button type="button" class="btn btn-success btn-xs pull-right" onclick="autoFillKepalaMetanik(true)" title="Auto Fill Kepala Mekanik">
                    <i class="ace-icon fa fa-magic"></i> Auto Fill
                </button>
            </div>
            <?php endif; ?>

            <div class="padding-18">

                <!-- Payment Section -->
                <div class="row">
                    <div class="col-sm-12">
                        <h5 class="green">
                            <i class="ace-icon fa fa-money"></i>
                            Pembayaran Service RST
                        </h5>
                        <div class="space-6"></div>
                    </div>
                </div>

                <!-- Customer Info Section -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-info">
                            <i class="ace-icon fa fa-user"></i>
                            <strong>Pelanggan:</strong> <?php echo $namapelanggan; ?> 
                            <?php if($kategori_pelanggan && $kategori_pelanggan != '001'): ?>
                                | <strong>Kategori:</strong> 
                                <?php 
                                switch(strtoupper($kategori_pelanggan)) {
                                    case 'G': case 'GOLD': echo '<span class="label label-warning">GOLD MEMBER (15% OFF)</span>'; break;
                                    case 'S': case 'SILVER': echo '<span class="label label-info">SILVER MEMBER (10% OFF)</span>'; break;
                                    case 'B': case 'BRONZE': echo '<span class="label label-success">BRONZE MEMBER (5% OFF)</span>'; break;
                                    default: echo '<span class="label label-default">REGULAR</span>';
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cancel History Display (Informational Only) -->
                <?php if (!empty($no_pelanggan)): ?>
                <?php
                $query_cancel_stats = "SELECT jumlah_cancel, cancel_rate, tanggal_cancel_terakhir,
                    cancel_customer_request, cancel_no_stock, cancel_no_mekanik, cancel_no_show, cancel_lainnya
                    FROM statistik_pelanggan WHERE no_pelanggan = '$no_pelanggan'";
                $result_cancel = mysqli_query($koneksi, $query_cancel_stats);
                if ($result_cancel && mysqli_num_rows($result_cancel) > 0) {
                    $cancel_stats = mysqli_fetch_assoc($result_cancel);
                    $jumlah_cancel = $cancel_stats['jumlah_cancel'] ?? 0;
                    if ($jumlah_cancel > 0):
                ?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert" style="background-color: #f9f9f9; border: 1px solid #ddd; margin-bottom: 15px;">
                            <i class="ace-icon fa fa-history"></i>
                            <strong>Riwayat Cancel:</strong> <span class="label label-default"><?php echo $jumlah_cancel; ?> kali</span>
                            <?php if ($cancel_stats['cancel_rate'] > 0): ?>
                                (<?php echo number_format($cancel_stats['cancel_rate'], 1); ?>% dari total booking)
                            <?php endif; ?>
                            <?php if ($cancel_stats['tanggal_cancel_terakhir']): ?>
                                | <small class="text-muted">Terakhir: <?php echo date('d M Y', strtotime($cancel_stats['tanggal_cancel_terakhir'])); ?></small>
                            <?php endif; ?>
                            <br/><small><strong>Alasan:</strong> <?php 
                            $alasan_list = [];
                            if ($cancel_stats['cancel_customer_request'] > 0) $alasan_list[] = "Customer request ({$cancel_stats['cancel_customer_request']}x)";
                            if ($cancel_stats['cancel_no_stock'] > 0) $alasan_list[] = "Stok habis ({$cancel_stats['cancel_no_stock']}x)";
                            if ($cancel_stats['cancel_no_mekanik'] > 0) $alasan_list[] = "Mekanik tidak ada ({$cancel_stats['cancel_no_mekanik']}x)";
                            if ($cancel_stats['cancel_no_show'] > 0) $alasan_list[] = "Customer no-show ({$cancel_stats['cancel_no_show']}x)";
                            if ($cancel_stats['cancel_lainnya'] > 0) $alasan_list[] = "Lainnya ({$cancel_stats['cancel_lainnya']}x)";
                            echo !empty($alasan_list) ? implode(', ', $alasan_list) : 'Tidak ada detail';
                            ?></small>
                        </div>
                    </div>
                </div>
                <?php endif; } ?>
                <?php endif; ?>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Total Jasa:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txttotal_jasa" name="txttotal_jasa" 
                                           value="<?php echo number_format($total_service, 0, ',', '.'); ?>" readonly />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Total Barang:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txttotal_barang" name="txttotal_barang" 
                                           value="<?php echo number_format($total_barang, 0, ',', '.'); ?>" readonly />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right"><strong>Subtotal:</strong></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txttotal" name="txttotal" 
                                           value="<?php echo number_format($tot, 0, ',', '.'); ?>" readonly 
                                           style="font-weight: bold;" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Diskon Member:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="txtdiskon_member" name="txtdiskon_member" 
                                           value="<?php echo $auto_discount_percent; ?>" readonly 
                                           style="background-color: #f5f5f5;" />
                                    <span class="input-group-addon">%</span>
                                </div>
                                <small class="help-block">Diskon otomatis berdasarkan kategori member</small>
                                <!-- Hidden fields for category info -->
                                <input type="hidden" name="kategori_pelanggan" value="<?php echo $kategori_pelanggan; ?>" />
                                <input type="hidden" name="potongan_pelanggan" value="<?php echo $potongan_pelanggan; ?>" />
                                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Diskon Tambahan (%):</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="txtpotfaktur_persen" name="txtpotfaktur_persen" 
                                       value="0" min="0" max="100" step="0.01" 
                                       placeholder="Diskon tambahan jika ada" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Total Diskon (Rp):</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txtpotfaktur_nom" name="txtpotfaktur_nom" 
                                           value="<?php echo number_format($discount_amount, 0, ',', '.'); ?>" readonly 
                                           style="background-color: #f5f5f5;" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">PPN (%):</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="txtpajak_persen" name="txtpajak_persen" 
                                       value="0" min="0" max="100" step="0.01" />
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">PPN (Rp):</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txtpajak_nom" name="txtpajak_nom" 
                                           value="0" readonly />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right"><strong>Total Bayar:</strong></label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txtnet" name="txtnet" 
                                           value="<?php echo number_format($net, 0, ',', '.'); ?>" readonly 
                                           style="font-weight: bold; font-size: 16px;" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Metode Bayar:</label>
                            <div class="col-sm-8">
                                <select class="form-control" id="metode_pembayaran" name="metode_pembayaran">
                                    <option value="Tunai" <?php echo ($metode_pembayaran == 'Tunai') ? 'selected' : ''; ?>>Tunai</option>
                                    <option value="Transfer Bank" <?php echo ($metode_pembayaran == 'Transfer Bank') ? 'selected' : ''; ?>>Transfer Bank</option>
                                    <option value="Kartu Kredit" <?php echo ($metode_pembayaran == 'Kartu Kredit') ? 'selected' : ''; ?>>Kartu Kredit</option>
                                    <option value="Kartu Debit" <?php echo ($metode_pembayaran == 'Kartu Debit') ? 'selected' : ''; ?>>Kartu Debit</option>
                                    <option value="E-Wallet" <?php echo ($metode_pembayaran == 'E-Wallet') ? 'selected' : ''; ?>>E-Wallet</option>
                                    <option value="QRIS" <?php echo ($metode_pembayaran == 'QRIS') ? 'selected' : ''; ?>>QRIS</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="bukti_pembayaran_group" style="display: none;">
                            <label class="col-sm-4 control-label no-padding-right">Bukti Pembayaran:</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" 
                                       accept="image/*,.pdf" />
                                <small class="help-block">Upload bukti transfer/pembayaran (JPG, PNG, PDF - Max 2MB)</small>
                                <?php if(!empty($bukti_pembayaran) && file_exists("../".$bukti_pembayaran)): ?>
                                <div class="alert alert-info" style="margin-top: 10px;">
                                    <i class="fa fa-file"></i> 
                                    <a href="../<?php echo $bukti_pembayaran; ?>" target="_blank">Lihat Bukti Pembayaran</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <script type="text/javascript">
                        // Inline handler for immediate execution
                        (function() {
                            function toggleBuktiPembayaran() {
                                var metodeBayar = document.getElementById('metode_pembayaran');
                                var buktiGroup = document.getElementById('bukti_pembayaran_group');
                                
                                if (metodeBayar && buktiGroup) {
                                    if (metodeBayar.value === 'Tunai') {
                                        buktiGroup.style.display = 'none';
                                    } else {
                                        buktiGroup.style.display = 'block';
                                    }
                                }
                            }
                            
                            // Run on load
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', toggleBuktiPembayaran);
                            } else {
                                toggleBuktiPembayaran();
                            }
                            
                            // Add event listener
                            var metodeBayar = document.getElementById('metode_pembayaran');
                            if (metodeBayar) {
                                metodeBayar.addEventListener('change', toggleBuktiPembayaran);
                            }
                        })();
                        </script>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Jumlah Bayar:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txtbayar" name="txtbayar" 
                                           value="<?php echo number_format($bayar, 0, ',', '.'); ?>" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Kembalian:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="text" class="form-control text-right" id="txtkembalian" name="txtkembalian" 
                                           value="<?php echo number_format($kembalian, 0, ',', '.'); ?>" readonly 
                                           style="background-color: #f0f8ff;" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hr hr-24"></div>

                <!-- Mechanic Assignment Section -->
                <div class="row">
                    <div class="col-sm-12">
                        <h5 class="purple">
                            <i class="ace-icon fa fa-users"></i>
                            Penugasan Mekanik RST
                        </h5>
                        <div class="space-6"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <!-- Kepala Mekanik 1 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Kepala Mekanik 1:</label>
                            <div class="col-sm-5">
                                <select name="cbokepala_mekanik1" id="cbokepala_mekanik1" class="form-control">
                                    <option value="">- Pilih Kepala Mekanik -</option>
                                    <?php
                                    $q_km1 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND ((UPPER(kode_posisi)='KM' OR UPPER(kode_posisi) LIKE '%KEPALA%MEKANIK%') OR user_akses=10)
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_km1 = mysqli_query($koneksi, $q_km1);
                                    if ($r_km1) {
                                        while($row_kepala = mysqli_fetch_array($r_km1)) {
                                            $sel = '';
                                            if (isset($kepala_mekanik1)) {
                                                if ($kepala_mekanik1 == $row_kepala['nama'] || $kepala_mekanik1 == ($row_kepala['kode_karyawan'] ?? '') || $kepala_mekanik1 == ($row_kepala['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_kepala['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_kepala['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_kepala1" id="txtpersen_kepala1" 
                                           class="form-control" value="<?php echo isset($persen_kepala1) ? $persen_kepala1 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mekanik 1 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 1 *:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik1" id="cbomekanik1" class="form-control" required>
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
                                    $q_mk1 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND (UPPER(kode_posisi)='MK' OR UPPER(kode_posisi) LIKE '%MEKANIK%')
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_mk1 = mysqli_query($koneksi, $q_mk1);
                                    if ($r_mk1) {
                                        while($row_mekanik = mysqli_fetch_array($r_mk1)) {
                                            $sel = '';
                                            if (isset($mekanik1)) {
                                                if ($mekanik1 == $row_mekanik['nama'] || $mekanik1 == ($row_mekanik['kode_karyawan'] ?? '') || $mekanik1 == ($row_mekanik['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_mekanik['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_mekanik['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_mekanik1" id="txtpersen_mekanik1" 
                                           class="form-control" value="<?php echo isset($persen_kerja1) ? $persen_kerja1 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mekanik 2 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 2:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik2" id="cbomekanik2" class="form-control">
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
                                    $q_mk2 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND (UPPER(kode_posisi)='MK' OR UPPER(kode_posisi) LIKE '%MEKANIK%')
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_mk2 = mysqli_query($koneksi, $q_mk2);
                                    if ($r_mk2) {
                                        while($row_mekanik = mysqli_fetch_array($r_mk2)) {
                                            $sel = '';
                                            if (isset($mekanik2)) {
                                                if ($mekanik2 == $row_mekanik['nama'] || $mekanik2 == ($row_mekanik['kode_karyawan'] ?? '') || $mekanik2 == ($row_mekanik['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_mekanik['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_mekanik['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_mekanik2" id="txtpersen_mekanik2" 
                                           class="form-control" value="<?php echo isset($persen_kerja2) ? $persen_kerja2 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <!-- Kepala Mekanik 2 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Kepala Mekanik 2:</label>
                            <div class="col-sm-5">
                                <select name="cbokepala_mekanik2" id="cbokepala_mekanik2" class="form-control">
                                    <option value="">- Pilih Kepala Mekanik -</option>
                                    <?php
                                    $q_km2 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND ((UPPER(kode_posisi)='KM' OR UPPER(kode_posisi) LIKE '%KEPALA%MEKANIK%') OR user_akses=10)
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_km2 = mysqli_query($koneksi, $q_km2);
                                    if ($r_km2) {
                                        while($row_kepala = mysqli_fetch_array($r_km2)) {
                                            $sel = '';
                                            if (isset($kepala_mekanik2)) {
                                                if ($kepala_mekanik2 == $row_kepala['nama'] || $kepala_mekanik2 == ($row_kepala['kode_karyawan'] ?? '') || $kepala_mekanik2 == ($row_kepala['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_kepala['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_kepala['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_kepala2" id="txtpersen_kepala2" 
                                           class="form-control" value="<?php echo isset($persen_kepala2) ? $persen_kepala2 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mekanik 3 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 3:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik3" id="cbomekanik3" class="form-control">
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
                                    $q_mk3 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND (UPPER(kode_posisi)='MK' OR UPPER(kode_posisi) LIKE '%MEKANIK%')
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_mk3 = mysqli_query($koneksi, $q_mk3);
                                    if ($r_mk3) {
                                        while($row_mekanik = mysqli_fetch_array($r_mk3)) {
                                            $sel = '';
                                            if (isset($mekanik3)) {
                                                if ($mekanik3 == $row_mekanik['nama'] || $mekanik3 == ($row_mekanik['kode_karyawan'] ?? '') || $mekanik3 == ($row_mekanik['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_mekanik['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_mekanik['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_mekanik3" id="txtpersen_mekanik3" 
                                           class="form-control" value="<?php echo isset($persen_kerja3) ? $persen_kerja3 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mekanik 4 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 4:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik4" id="cbomekanik4" class="form-control">
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
                                    $q_mk4 = "SELECT id, kode_karyawan, COALESCE(nama_lengkap, nama_user) AS nama
                                              FROM tbuser
                                              WHERE status_row='0' AND (is_active='active' OR is_active IS NULL)
                                                AND (UPPER(kode_posisi)='MK' OR UPPER(kode_posisi) LIKE '%MEKANIK%')
                                                AND (kode_cabang='".mysqli_real_escape_string($koneksi, $kd_cabang)."' OR IFNULL(kode_cabang,'')='' OR UPPER(kode_cabang)='ALL')
                                              ORDER BY nama";
                                    $r_mk4 = mysqli_query($koneksi, $q_mk4);
                                    if ($r_mk4) {
                                        while($row_mekanik = mysqli_fetch_array($r_mk4)) {
                                            $sel = '';
                                            if (isset($mekanik4)) {
                                                if ($mekanik4 == $row_mekanik['nama'] || $mekanik4 == ($row_mekanik['kode_karyawan'] ?? '') || $mekanik4 == ($row_mekanik['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_mekanik['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_mekanik['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_mekanik4" id="txtpersen_mekanik4" 
                                           class="form-control" value="<?php echo isset($persen_kerja4) ? $persen_kerja4 : '0'; ?>" 
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KM Service Fields -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">KM Sekarang:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="txtkm_skr" name="txtkm_skr"
                                           value="<?php echo $km_skr; ?>" min="0" />
                                    <span class="input-group-addon">KM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hr hr-24"></div>

                <!-- Total Percentage Display -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                            <div class="row">
                                <div class="col-sm-8">
                                    <i class="ace-icon fa fa-calculator"></i>
                                    <span id="totalPercentageDisplay"><strong>Total: 0%</strong></span>
                                    <small class="help-block" style="margin-top: 5px; margin-bottom: 0;">
                                        <i class="fa fa-info-circle"></i> Persentase akan otomatis dibagi rata ketika mekanik dipilih. Total harus 100%.
                                    </small>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <button type="button" class="btn btn-sm btn-info" onclick="autoCalculatePercentage()" title="Hitung Ulang Persentase">
                                        <i class="ace-icon fa fa-refresh"></i> Auto Hitung
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Mechanic Data Button -->
                <div class="row">
                    <div class="col-sm-12">
                        <button type="button" class="btn btn-warning btn-block btn-lg" id="btnSaveMechanicData" onclick="saveMechanicData()">
                            <i class="ace-icon fa fa-save"></i>
                            Simpan Data Mekanik & Persentase
                        </button>
                        <div class="space-12"></div>
                    </div>
                </div>

                <div class="hr hr-24"></div>

                <!-- Action Buttons Section -->
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="blue">
                            <i class="ace-icon fa fa-cogs"></i>
                            Aksi Service RST
                        </h5>
                        <div class="space-6"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xs-3">
                        <button class="btn btn-success btn-block" type="submit"
                        id="btnsimpan" name="btnsimpan">
                            <i class="ace-icon fa fa-save"></i>
                            Simpan
                        </button>
                    </div>
                    <div class="col-xs-3">
                        <button class="btn btn-primary btn-block" type="submit"
                        id="btnbayar" name="btnbayar">
                            <i class="ace-icon fa fa-money"></i>
                            Bayar
                        </button>
                    </div>
                    <div class="col-xs-3">
                        <button class="btn btn-info btn-block" type="button"
                        id="btncetak" name="btncetak" onclick="window.open('servis-print.php?snoserv=<?php echo $no_service; ?>', '_blank')">
                            <i class="ace-icon fa fa-print"></i>
                            Cetak
                        </button>
                    </div>
                    <div class="col-xs-3">
                        <a href="servis.php">
                            <button class="btn btn-default btn-block" type="button">
                                <i class="ace-icon fa fa-arrow-left"></i>
                                Tutup
                            </button>
                        </a>
                    </div>
                </div>

                <div class="space-12"></div>

                <div class="row">
                    <div class="col-xs-6">
                        <button class="btn btn-warning btn-block" type="button" onclick="resetService()">
                            <i class="ace-icon fa fa-refresh"></i>
                            Reset Service
                        </button>
                    </div>
                    <div class="col-xs-6">
                        <?php if($status_servis != 'bayar' && $status_servis != 'cancel' && !empty($no_service)): ?>
                        <button class="btn btn-danger btn-block" type="button" 
                                data-toggle="modal" data-target="#modalCancelService">
                            <i class="ace-icon fa fa-ban"></i>
                            Cancel Service
                        </button>
                        <?php else: ?>
                        <button class="btn btn-default btn-block" type="button" disabled>
                            <i class="ace-icon fa fa-ban"></i>
                            <?php echo ($status_servis == 'cancel') ? 'Service Dibatalkan' : 'Tidak Dapat Cancel'; ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Include Modal Cancel Service -->
<?php if($status_servis != 'bayar' && $status_servis != 'cancel' && !empty($no_service)): ?>
    <?php include "_template/_modal_cancel_service.php"; ?>
<?php endif; ?>

<script type="text/javascript">
// DEFER EXECUTION until jQuery is loaded
(function() {
    function initServiceActionsRst() {
        // Double-check jQuery is available
        if (typeof jQuery === 'undefined') {
            console.error('jQuery is not loaded yet, retrying...');
            setTimeout(initServiceActionsRst, 100);
            return;
        }
        
        var $ = jQuery;

        // ============ AUTO FILL KEPALA MEKANIK HARIAN ============
        window.autoFillKepalaMetanik = function(autoSave) {
            $.ajax({
                url: 'get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik&tanggal=<?php echo isset($tanggal_srv) ? $tanggal_srv : ''; ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.has_data) {
                        var data = response.data;
                        var filled = false;

                        // Kepala Mekanik 1: match by option text or value (case-insensitive). If not found, append.
                        if (data.kepala_mekanik_1) {
                            var name1Raw = String(data.kepala_mekanik_1).trim();
                            var name1 = name1Raw.toLowerCase();
                            var found1 = false;
                            $('#cbokepala_mekanik1 option').each(function() {
                                var t = $(this).text().trim().toLowerCase();
                                var v = String($(this).val() || '').trim().toLowerCase();
                                if (t === name1 || v === name1) {
                                    $(this).prop('selected', true).trigger('change');
                                    filled = true;
                                    found1 = true;
                                    return false;
                                }
                            });
                            if (!found1) {
                                var $sel1 = $('#cbokepala_mekanik1');
                                $sel1.append('<option value="'+name1Raw+'">'+name1Raw+'</option>');
                                $sel1.val(name1Raw).trigger('change');
                                filled = true;
                            }
                        }

                        // Kepala Mekanik 2: match by option text or value (case-insensitive). If not found, append.
                        if (data.kepala_mekanik_2) {
                            var name2Raw = String(data.kepala_mekanik_2).trim();
                            var name2 = name2Raw.toLowerCase();
                            var found2 = false;
                            $('#cbokepala_mekanik2 option').each(function() {
                                var t = $(this).text().trim().toLowerCase();
                                var v = String($(this).val() || '').trim().toLowerCase();
                                if (t === name2 || v === name2) {
                                    $(this).prop('selected', true).trigger('change');
                                    filled = true;
                                    found2 = true;
                                    return false;
                                }
                            });
                            if (!found2) {
                                var $sel2 = $('#cbokepala_mekanik2');
                                $sel2.append('<option value="'+name2Raw+'">'+name2Raw+'</option>');
                                $sel2.val(name2Raw).trigger('change');
                                filled = true;
                            }
                        }

                        // Auto-calculate percentage for ALL selected mechanics
                        if (filled && typeof window.autoCalculatePercentage === 'function') {
                            window.autoCalculatePercentage();
                        }

                        // Save to database if autoSave is true
                        if (autoSave === true) {
                            saveKepalaMetanikToService();
                        }

                        // Show success message
                        showNotification('success', 'Kepala mekanik berhasil di-auto fill!');

                    } else {
                        showNotification('warning', response.message || 'Belum ada data kepala mekanik untuk hari ini');
                    }
                },
                error: function() {
                    showNotification('error', 'Gagal mengambil data kepala mekanik harian');
                }
            });
        };
        
        // Save kepala mekanik to service
        window.saveKepalaMetanikToService = function() {
            var noService = '<?php echo $no_service ?? ""; ?>';
            var kepala1 = $('#cbokepala_mekanik1').val();
            var kepala2 = $('#cbokepala_mekanik2').val();
            var persen1 = $('#txtpersen_kepala1').val() || 0;
            var persen2 = $('#txtpersen_kepala2').val() || 0;
            
            if (!noService) {
                return;
            }
            
            $.ajax({
                url: 'get_kepala_mekanik_harian.php',
                type: 'POST',
                data: {
                    action: 'save_kepala_mekanik',
                    no_service: noService,
                    kepala_mekanik1: kepala1,
                    kepala_mekanik2: kepala2,
                    persen_kepala1: persen1,
                    persen_kepala2: persen2
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        console.log('Kepala mekanik berhasil disimpan ke service');
                    }
                },
                error: function() {
                    console.log('Gagal menyimpan kepala mekanik ke service');
                }
            });
        };

        // ============ REFRESH KEPALA MEKANIK HARIAN STATUS ============
        window.refreshKepalaMetanikHarian = function() {
            $.ajax({
                url: 'get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik&tanggal=<?php echo isset($tanggal_srv) ? $tanggal_srv : ''; ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        var labelStr = '';
                        if (response.data && response.data.shift_kerja) {
                            labelStr = ' <span class="label label-info">' + String(response.data.shift_kerja).toUpperCase() + '</span>';
                        }
                        var alertHtml = '<div class="alert alert-success" id="alertKepalaMetanikHarian">' +
                            '<i class="ace-icon fa fa-check-circle"></i>' +
                            '<strong>Kepala Mekanik Hari Ini:</strong> ' +
                            (response.data && response.data.kepala_mekanik_1 ? response.data.kepala_mekanik_1 : '-') +
                            (response.data && response.data.kepala_mekanik_2 ? ' & ' + response.data.kepala_mekanik_2 : '') +
                            labelStr +
                            '<button type="button" class="btn btn-success btn-xs pull-right" onclick="autoFillKepalaMetanik(true)" title="Auto Fill Kepala Mekanik">' +
                            '<i class="ace-icon fa fa-magic"></i> Auto Fill' +
                            '</button>' +
                            '</div>';
                        $('#alertKepalaMetanikHarian').replaceWith(alertHtml);
                        showNotification('success', 'Data kepala mekanik harian berhasil direfresh!');
                    } else {
                        showNotification('info', response.message || 'Belum ada data kepala mekanik untuk hari ini');
                    }
                },
                error: function() {
                    showNotification('error', 'Gagal refresh data kepala mekanik harian');
                }
            });
        };
        
        // ============ NOTIFICATION HELPER FUNCTION ============
        function showNotification(type, message) {
            var alertClass = 'alert-info';
            var icon = 'fa-info-circle';

            switch(type) {
                case 'success':
                    alertClass = 'alert-success';
                    icon = 'fa-check-circle';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    icon = 'fa-exclamation-triangle';
                    break;
                case 'error':
                    alertClass = 'alert-danger';
                    icon = 'fa-times-circle';
                    break;
            }

            var notification = '<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 70px; right: 20px; z-index: 9999; min-width: 300px;">' +
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                '<i class="ace-icon fa ' + icon + '"></i> ' +
                message +
                '</div>';

            $('body').append(notification);

            // Auto remove after 5 seconds
            setTimeout(function() {
                $('.alert').last().fadeOut();
            }, 5000);
        }

        // ============ PAYMENT METHOD HANDLER ============
        // Show/hide bukti pembayaran field based on payment method
        $('#metode_pembayaran').on('change', function() {
            var method = $(this).val();
            if (method === 'Tunai') {
                $('#bukti_pembayaran_group').hide();
                $('#bukti_pembayaran').prop('required', false);
            } else {
                $('#bukti_pembayaran_group').show();
                $('#bukti_pembayaran').prop('required', false); // Optional for non-cash
            }
        });

        // Trigger on page load to set initial state
        $('#metode_pembayaran').trigger('change');

        // ============ AUTO CALCULATE PERCENTAGE ============
        window.autoCalculatePercentage = function() {
            // Get all selected mechanics
            var selectedMechanics = [];
            
            // Check kepala mekanik
            if ($('#cbokepala_mekanik1').val()) selectedMechanics.push({id: 'txtpersen_kepala1', elem: $('#txtpersen_kepala1')});
            if ($('#cbokepala_mekanik2').val()) selectedMechanics.push({id: 'txtpersen_kepala2', elem: $('#txtpersen_kepala2')});
            
            // Check regular mechanics
            if ($('#cbomekanik1').val()) selectedMechanics.push({id: 'txtpersen_mekanik1', elem: $('#txtpersen_mekanik1')});
            if ($('#cbomekanik2').val()) selectedMechanics.push({id: 'txtpersen_mekanik2', elem: $('#txtpersen_mekanik2')});
            if ($('#cbomekanik3').val()) selectedMechanics.push({id: 'txtpersen_mekanik3', elem: $('#txtpersen_mekanik3')});
            if ($('#cbomekanik4').val()) selectedMechanics.push({id: 'txtpersen_mekanik4', elem: $('#txtpersen_mekanik4')});
            
            if (selectedMechanics.length > 0) {
                var percentPerMechanic = Math.floor(100 / selectedMechanics.length);
                var remainder = 100 - (percentPerMechanic * selectedMechanics.length);
                
                selectedMechanics.forEach(function(mech, index) {
                    var percent = percentPerMechanic;
                    if (index === 0) percent += remainder; // Add remainder to first mechanic
                    mech.elem.val(percent);
                });
            }
            
            updateTotalPercentage();
        };
        
        // ============ UPDATE TOTAL PERCENTAGE DISPLAY ============
        window.updateTotalPercentage = function() {
            var total = 0;
            total += parseInt($('#txtpersen_kepala1').val() || 0);
            total += parseInt($('#txtpersen_kepala2').val() || 0);
            total += parseInt($('#txtpersen_mekanik1').val() || 0);
            total += parseInt($('#txtpersen_mekanik2').val() || 0);
            total += parseInt($('#txtpersen_mekanik3').val() || 0);
            total += parseInt($('#txtpersen_mekanik4').val() || 0);
            
            var displayText = '<strong>Total: ' + total + '%</strong>';
            if (total !== 100) {
                displayText += ' <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> Harus 100%</span>';
            } else {
                displayText += ' <span class="label label-success"><i class="fa fa-check"></i> OK</span>';
            }
            
            $('#totalPercentageDisplay').html(displayText);
        };
        
        // ============ SAVE MECHANIC DATA ============
        window.saveMechanicData = function() {
            var noService = '<?php echo $no_service ?? ""; ?>';
            
            if (!noService) {
                alert('Nomor service tidak ditemukan!');
                return;
            }
            
            // Validate total percentage
            var total = 0;
            total += parseInt($('#txtpersen_kepala1').val() || 0);
            total += parseInt($('#txtpersen_kepala2').val() || 0);
            total += parseInt($('#txtpersen_mekanik1').val() || 0);
            total += parseInt($('#txtpersen_mekanik2').val() || 0);
            total += parseInt($('#txtpersen_mekanik3').val() || 0);
            total += parseInt($('#txtpersen_mekanik4').val() || 0);
            
            if (total !== 100 && total !== 0) {
                if (!confirm('Total persentase adalah ' + total + '%, bukan 100%.\nLanjutkan menyimpan?')) {
                    return;
                }
            }
            
            // Prepare data
            var formData = {
                txtnosrv: noService,
                cbokepala_mekanik1: $('#cbokepala_mekanik1').val(),
                txtpersen_kepala1: $('#txtpersen_kepala1').val(),
                cbokepala_mekanik2: $('#cbokepala_mekanik2').val(),
                txtpersen_kepala2: $('#txtpersen_kepala2').val(),
                cbomekanik1: $('#cbomekanik1').val(),
                txtpersen_mekanik1: $('#txtpersen_mekanik1').val(),
                cbomekanik2: $('#cbomekanik2').val(),
                txtpersen_mekanik2: $('#txtpersen_mekanik2').val(),
                cbomekanik3: $('#cbomekanik3').val(),
                txtpersen_mekanik3: $('#txtpersen_mekanik3').val(),
                cbomekanik4: $('#cbomekanik4').val(),
                txtpersen_mekanik4: $('#txtpersen_mekanik4').val(),
                btnupdatemekanik: '1'
            };
            
            // Show loading
            $('#btnSaveMechanicData').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            // Submit via AJAX
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: formData,
                success: function(response) {
                    showNotification('success', 'Data mekanik berhasil disimpan!');
                    $('#btnSaveMechanicData').prop('disabled', false).html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
                },
                error: function() {
                    showNotification('error', 'Gagal menyimpan data mekanik!');
                    $('#btnSaveMechanicData').prop('disabled', false).html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
                }
            });
        };
        
        // Add change listeners to update total percentage
        $('#txtpersen_kepala1, #txtpersen_kepala2, #txtpersen_mekanik1, #txtpersen_mekanik2, #txtpersen_mekanik3, #txtpersen_mekanik4').on('keyup change', function() {
            updateTotalPercentage();
        });
        
        // Add change listeners to mechanic dropdowns to auto-calculate
        $('#cbokepala_mekanik1, #cbokepala_mekanik2, #cbomekanik1, #cbomekanik2, #cbomekanik3, #cbomekanik4').on('change', function() {
            updateTotalPercentage();
        });
        
        // Initial percentage display update
        updateTotalPercentage();

        // ============ INITIALIZATION ============
        // Auto fill on page load if data exists
        <?php if ($has_kepala_mekanik_harian): ?>
        // Check if kepala mekanik already filled for this service
        var kepala1Value = $('#cbokepala_mekanik1').val();
        var kepala2Value = $('#cbokepala_mekanik2').val();
        
        // Only show confirmation if kepala mekanik not yet filled
        if (!kepala1Value && !kepala2Value) {
            setTimeout(function() {
                if (confirm('Auto fill kepala mekanik dari data harian hari ini?')) {
                    autoFillKepalaMetanik(true); // Pass true to auto-save
                }
            }, 1000);
        }
        <?php else: ?>
        setTimeout(function(){
            if (confirm('Belum ada input Kepala Mekanik untuk tanggal ini. Buka halaman input sekarang?')) {
                window.location.href = 'input_kepala_mekanik_harian.php';
            }
        }, 800);
        <?php endif; ?>
    }
    
    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServiceActionsRst);
    } else {
        // DOM already loaded, start init immediately
        initServiceActionsRst();
    }
})();
</script>
