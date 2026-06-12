<!-- Tab Actions - Enhanced with Payment -->
<div class="actions-content">
    <div class="row">
        <div class="col-xs-12">
            <!-- Include notification for cancel results -->
            <?php include "_template/_notification_cancel.php"; ?>
            
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
                <span class="label label-success label-sm">Backup</span>
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
                            Pembayaran Service
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
                    <?php 
                    // Display member badge using new system
                    if (!empty($no_pelanggan) && function_exists('displayBadgeStatusMember')) {
                        $member_status = getStatusMember($koneksi, $no_pelanggan);
                        $discount_pct = getDiskonPelanggan($koneksi, $no_pelanggan);
                        echo ' | ' . displayBadgeStatusMember($member_status);
                        if ($discount_pct > 0) {
                            echo ' <small class="text-muted">(' . number_format($discount_pct, 0) . '% Auto Discount)</small>';
                        }
                    } elseif (!empty($kategori_pelanggan) && $kategori_pelanggan != '001') {
                        // Fallback to old system if new system not available
                        echo ' | <strong>Kategori:</strong> ';
                        switch(strtoupper($kategori_pelanggan)) {
                            case 'G': case 'GOLD': echo '<span class="label label-warning">GOLD MEMBER (15% OFF)</span>'; break;
                            case 'S': case 'SILVER': echo '<span class="label label-info">SILVER MEMBER (10% OFF)</span>'; break;
                            case 'B': case 'BRONZE': echo '<span class="label label-success">BRONZE MEMBER (5% OFF)</span>'; break;
                            default: echo '<span class="label label-default">REGULAR</span>';
                        }
                    }
                    ?>
                </div>
                    </div>
                </div>

                <!-- Cancel History Display (Informational Only) -->
                <?php if (!empty($no_pelanggan)): ?>
                <?php
                // Get cancel statistics for customer
                $query_cancel_stats = "SELECT 
                    jumlah_cancel,
                    cancel_rate,
                    tanggal_cancel_terakhir,
                    cancel_customer_request,
                    cancel_no_stock,
                    cancel_no_mekanik,
                    cancel_no_show,
                    cancel_lainnya
                    FROM statistik_pelanggan 
                    WHERE no_pelanggan = '$no_pelanggan'";
                
                $result_cancel = mysqli_query($koneksi, $query_cancel_stats);
                
                if ($result_cancel && mysqli_num_rows($result_cancel) > 0) {
                    $cancel_stats = mysqli_fetch_assoc($result_cancel);
                    $jumlah_cancel = $cancel_stats['jumlah_cancel'] ?? 0;
                    
                    // Only show if customer has cancel history
                    if ($jumlah_cancel > 0):
                ?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert" style="background-color: #f9f9f9; border: 1px solid #ddd; margin-bottom: 15px;">
                            <i class="ace-icon fa fa-history"></i>
                            <strong>Riwayat Cancel:</strong>
                            <span class="label label-default"><?php echo $jumlah_cancel; ?> kali</span>
                            <?php if ($cancel_stats['cancel_rate'] > 0): ?>
                                (<?php echo number_format($cancel_stats['cancel_rate'], 1); ?>% dari total booking)
                            <?php endif; ?>
                            
                            <?php if ($cancel_stats['tanggal_cancel_terakhir']): ?>
                                | <small class="text-muted">Terakhir: <?php echo date('d M Y', strtotime($cancel_stats['tanggal_cancel_terakhir'])); ?></small>
                            <?php endif; ?>
                            
                            <!-- Breakdown alasan cancel -->
                            <br/><small>
                            <strong>Alasan:</strong>
                            <?php 
                            $alasan_list = [];
                            if ($cancel_stats['cancel_customer_request'] > 0) {
                                $alasan_list[] = "Customer request ({$cancel_stats['cancel_customer_request']}x)";
                            }
                            if ($cancel_stats['cancel_no_stock'] > 0) {
                                $alasan_list[] = "Stok habis ({$cancel_stats['cancel_no_stock']}x)";
                            }
                            if ($cancel_stats['cancel_no_mekanik'] > 0) {
                                $alasan_list[] = "Mekanik tidak ada ({$cancel_stats['cancel_no_mekanik']}x)";
                            }
                            if ($cancel_stats['cancel_no_show'] > 0) {
                                $alasan_list[] = "Customer no-show ({$cancel_stats['cancel_no_show']}x)";
                            }
                            if ($cancel_stats['cancel_lainnya'] > 0) {
                                $alasan_list[] = "Lainnya ({$cancel_stats['cancel_lainnya']}x)";
                            }
                            
                            echo !empty($alasan_list) ? implode(', ', $alasan_list) : 'Tidak ada detail';
                            ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php 
                    endif; // End if jumlah_cancel > 0
                } // End if result exists
                ?>
                <?php endif; // End if no_pelanggan exists ?>

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
                        
                        <?php
                        // Populate auto discount from member kategori if not already set
                        if (!isset($auto_discount_percent) || $auto_discount_percent == 0) {
                            if (!empty($no_pelanggan) && function_exists('getDiskonPelanggan')) {
                                $auto_discount_percent = getDiskonPelanggan($koneksi, $no_pelanggan);
                            } else {
                                $auto_discount_percent = 0;
                            }
                        }
                        ?>

                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Diskon Member:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="txtdiskon_member" name="txtdiskon_member" 
                                           value="<?php echo $auto_discount_percent; ?>" readonly 
                                           style="background-color: #f5f5f5; cursor: not-allowed;" />
                                    <span class="input-group-addon">%</span>
                                </div>
                                <small class="help-block"><i class="fa fa-info-circle"></i> Diskon otomatis berdasarkan kategori member</small>
                                <!-- Hidden fields for category info -->
                                <input type="hidden" name="kategori_pelanggan" value="<?php echo $kategori_pelanggan; ?>" />
                                <input type="hidden" name="potongan_pelanggan" value="<?php echo $potongan_pelanggan; ?>" />
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
                                    <option value="Tunai" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'Tunai') ? 'selected' : ''; ?>>Tunai</option>
                                    <option value="Transfer Bank" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'Transfer Bank') ? 'selected' : ''; ?>>Transfer Bank</option>
                                    <option value="Kartu Kredit" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'Kartu Kredit') ? 'selected' : ''; ?>>Kartu Kredit</option>
                                    <option value="Kartu Debit" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'Kartu Debit') ? 'selected' : ''; ?>>Kartu Debit</option>
                                    <option value="E-Wallet" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'E-Wallet') ? 'selected' : ''; ?>>E-Wallet</option>
                                    <option value="QRIS" <?php echo (isset($metode_pembayaran) && $metode_pembayaran == 'QRIS') ? 'selected' : ''; ?>>QRIS</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" id="bukti_pembayaran_group" style="display: none;">
                            <label class="col-sm-4 control-label no-padding-right">Bukti Pembayaran:</label>
                            <div class="col-sm-8">
                                <input type="file" class="form-control" id="bukti_pembayaran" name="bukti_pembayaran" 
                                       accept="image/*,.pdf" />
                                <small class="help-block">Upload bukti transfer/pembayaran (JPG, PNG, PDF - Max 2MB)</small>
                                <?php if(isset($bukti_pembayaran) && !empty($bukti_pembayaran) && file_exists("../".$bukti_pembayaran)): ?>
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
                            function toggleBuktiPembayaranReguler() {
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
                                document.addEventListener('DOMContentLoaded', toggleBuktiPembayaranReguler);
                            } else {
                                toggleBuktiPembayaranReguler();
                            }
                            
                            // Add event listener
                            var metodeBayar = document.getElementById('metode_pembayaran');
                            if (metodeBayar) {
                                metodeBayar.addEventListener('change', toggleBuktiPembayaranReguler);
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
                            Penugasan Mekanik
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
                                    // Query Kepala Mekanik from tbuser_karyawan
                                    $q_km1 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'KM'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
                                    $r_km1 = mysqli_query($koneksi, $q_km1);
                                    if ($r_km1) {
                                        while($row_kepala = mysqli_fetch_array($r_km1)) {
                                            $sel = '';
                                            if (isset($kepala_mekanik1)) {
                                                if ($kepala_mekanik1 == $row_kepala['nama'] || $kepala_mekanik1 == $row_kepala['kode_karyawan']) {
                                                    $sel = 'selected';
                                                }
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

                        <!-- Admin/Kasir 1 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Admin/Kasir 1:</label>
                            <div class="col-sm-5">
                                <select name="cboadmin1" id="cboadmin1" class="form-control">
                                    <option value="">- Pilih Admin/Kasir -</option>
                                    <?php
                                    // Query Admin/Kasir from tbuser_karyawan
                                    $q_admin1 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                                FROM tbuser_karyawan
                                                WHERE kode_posisi IN ('CS', 'KSR', 'ADM')
                                                  AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                  AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                       OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                                ORDER BY nama_lengkap";
                                    $r_admin1 = mysqli_query($koneksi, $q_admin1);
                                    if ($r_admin1) {
                                        while($row_admin = mysqli_fetch_array($r_admin1)) {
                                            $sel = '';
                                            if (isset($admin1)) {
                                                if ($admin1 == $row_admin['nama'] || $admin1 == ($row_admin['kode_karyawan'] ?? '') || $admin1 == ($row_admin['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_admin['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_admin['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_admin1" id="txtpersen_admin1"
                                           class="form-control" value="<?php echo isset($persen_admin1) ? $persen_admin1 : '0'; ?>"
                                           min="0" max="100" />
                                    <span class="input-group-addon">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Mekanik 1 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 1:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik1" id="cbomekanik1" class="form-control">
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
                                    // Query Mekanik from tbuser_karyawan
                                    $q_mk1 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'MK'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
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
                                    // Query Mekanik 2
                                    $q_mk2 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'MK'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
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
                                    // Query Kepala Mekanik 2
                                    $q_km2 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'KM'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
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

                        <!-- Admin/Kasir 2 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Admin/Kasir 2:</label>
                            <div class="col-sm-5">
                                <select name="cboadmin2" id="cboadmin2" class="form-control">
                                    <option value="">- Pilih Admin/Kasir -</option>
                                    <?php
                                    // Query Admin/Kasir 2
                                    $q_admin2 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                                FROM tbuser_karyawan
                                                WHERE kode_posisi IN ('CS', 'KSR', 'ADM')
                                                  AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                  AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                       OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                                ORDER BY nama_lengkap";
                                    $r_admin2 = mysqli_query($koneksi, $q_admin2);
                                    if ($r_admin2) {
                                        while($row_admin = mysqli_fetch_array($r_admin2)) {
                                            $sel = '';
                                            if (isset($admin2)) {
                                                if ($admin2 == $row_admin['nama'] || $admin2 == ($row_admin['kode_karyawan'] ?? '') || $admin2 == ($row_admin['id'] ?? '')) { $sel = 'selected'; }
                                            }
                                            echo "<option value='".htmlspecialchars($row_admin['nama'], ENT_QUOTES)."' $sel>".htmlspecialchars($row_admin['nama'])."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <div class="input-group">
                                    <input type="number" name="txtpersen_admin2" id="txtpersen_admin2"
                                           class="form-control" value="<?php echo isset($persen_admin2) ? $persen_admin2 : '0'; ?>"
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
                                    // Query Mekanik 3
                                    $q_mk3 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'MK'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
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
                                    // Query Mekanik 4
                                    $q_mk4 = "SELECT id, kode_karyawan, nama_lengkap AS nama
                                              FROM tbuser_karyawan
                                              WHERE kode_posisi = 'MK'
                                                AND (tanggal_keluar IS NULL OR tanggal_keluar = '0000-00-00')
                                                AND (kode_cabang = '".mysqli_real_escape_string($koneksi, $kd_cabang)."' 
                                                     OR kode_cabang = 'CAB001' OR kode_cabang = 'ALL' OR kode_cabang IS NULL OR kode_cabang = '')
                                              ORDER BY nama_lengkap";
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
                            Aksi Service
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
                        <a href="servis-reguler.php">
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
                        <a href="servis-carinopol-kosongkan.php?snoserv=<?php echo $no_service; ?>"
                        onclick="return confirm('Inputan Service akan dikosongkan. Lanjutkan?')">
                            <button class="btn btn-warning btn-block" type="button">
                                <i class="ace-icon fa fa-refresh"></i>
                                Reset Service
                            </button>
                        </a>
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
    if (typeof jQuery === 'undefined') {
        // jQuery not loaded yet, defer execution
        document.addEventListener('DOMContentLoaded', initServiceActions);
    } else {
        // jQuery already loaded
        jQuery(document).ready(initServiceActions);
    }

    function initServiceActions() {
        var $ = jQuery; // Alias for safety

        function syncKepalaMekanikValidationField() {
            if (!$('#cbokepala1').length) {
                var $hidden = $('<input/>', { type: 'hidden', id: 'cbokepala1' });
                var $form = $('form').first();
                if ($form.length) { $form.append($hidden); } else { $('body').append($hidden); }
            }
            $('#cbokepala1').val($('#cbokepala_mekanik1').val() || '');
        }

        syncKepalaMekanikValidationField();

        if (!$('#totalPersenReguler').length) {
            $('<span id="totalPersenReguler" style="display:none;">0</span>').appendTo('body');
        }

        // ============ SAVE MECHANIC DATA FUNCTION ============
        window.saveMechanicData = function() {
            var noService = '<?php echo $no_service; ?>';

            if (!noService) {
                alert('Nomor service tidak ditemukan!');
                return;
            }

            // Collect all mechanic data
            var data = {
                no_service: noService,
                kepala_mekanik1: $('#cbokepala_mekanik1').val(),
                persen_kepala1: $('#txtpersen_kepala1').val() || 0,
                kepala_mekanik2: $('#cbokepala_mekanik2').val(),
                persen_kepala2: $('#txtpersen_kepala2').val() || 0,
                admin1: $('#cboadmin1').val(),
                persen_admin1: $('#txtpersen_admin1').val() || 0,
                admin2: $('#cboadmin2').val(),
                persen_admin2: $('#txtpersen_admin2').val() || 0,
                mekanik1: $('#cbomekanik1').val(),
                persen_mekanik1: $('#txtpersen_mekanik1').val() || 0,
                mekanik2: $('#cbomekanik2').val(),
                persen_mekanik2: $('#txtpersen_mekanik2').val() || 0,
                mekanik3: $('#cbomekanik3').val(),
                persen_mekanik3: $('#txtpersen_mekanik3').val() || 0,
                mekanik4: $('#cbomekanik4').val(),
                persen_mekanik4: $('#txtpersen_mekanik4').val() || 0,
                km_skr: $('#txtkm_skr').val() || 0,
                km_berikut: $('#txtkm_next').val() || 0
            };

            // Disable button and show loading
            var button = $('#btnSaveMechanicData');
            button.prop('disabled', true);
            button.html('<i class="ace-icon fa fa-spinner fa-spin"></i> Menyimpan...');

            // Send AJAX request
            $.ajax({
                url: '_ajax/save_mechanic_data.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    button.prop('disabled', false);
                    button.html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');

                    if (response.status === 'success') {
                        alert('Data mekanik dan persentase berhasil disimpan!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    button.prop('disabled', false);
                    button.html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
                    alert('Terjadi kesalahan saat menyimpan data: ' + error);
                }
            });
        };

        // ============ PRINT SERVICE FUNCTION ============
        window.printService = function() {
            var noService = '<?php echo $no_service; ?>';
            if (noService) {
                window.open('servis-print.php?snoserv=' + noService, '_blank');
            } else {
                alert('Simpan service terlebih dahulu sebelum mencetak!');
            }
        };

        // ============ AUTO CALCULATE PERCENTAGE (Per Divisi 100%) ============
        window.autoCalculatePercentage = function() {
            function distributeEven($elems) {
                if ($elems.length > 0) {
                    var base = Math.floor(100 / $elems.length);
                    var rem = 100 - (base * $elems.length);
                    $elems.each(function(i){
                        var val = base + (i === 0 ? rem : 0);
                        $(this).val(val);
                    });
                }
            }

            // Kepala Mekanik group
            var kmElems = $();
            if ($('#cbokepala_mekanik1').val()) kmElems = kmElems.add($('#txtpersen_kepala1'));
            if ($('#cbokepala_mekanik2').val()) kmElems = kmElems.add($('#txtpersen_kepala2'));
            if (kmElems.length > 0) { distributeEven(kmElems); } else { $('#txtpersen_kepala1, #txtpersen_kepala2').val(0); }

            // Admin/Kasir group
            var admElems = $();
            if ($('#cboadmin1').val()) admElems = admElems.add($('#txtpersen_admin1'));
            if ($('#cboadmin2').val()) admElems = admElems.add($('#txtpersen_admin2'));
            if (admElems.length > 0) { distributeEven(admElems); } else { $('#txtpersen_admin1, #txtpersen_admin2').val(0); }

            // Mekanik group
            var mkElems = $();
            if ($('#cbomekanik1').val()) mkElems = mkElems.add($('#txtpersen_mekanik1'));
            if ($('#cbomekanik2').val()) mkElems = mkElems.add($('#txtpersen_mekanik2'));
            if ($('#cbomekanik3').val()) mkElems = mkElems.add($('#txtpersen_mekanik3'));
            if ($('#cbomekanik4').val()) mkElems = mkElems.add($('#txtpersen_mekanik4'));
            if (mkElems.length > 0) { distributeEven(mkElems); } else { $('#txtpersen_mekanik1, #txtpersen_mekanik2, #txtpersen_mekanik3, #txtpersen_mekanik4').val(0); }

            // Update totals per group
            window.updateTotalPercentage();
        };

        // ============ UPDATE TOTAL PERCENTAGE (Per Divisi) ============
        window.updateTotalPercentage = function() {
            var kmTotal = (parseFloat($('#txtpersen_kepala1').val() || 0) + parseFloat($('#txtpersen_kepala2').val() || 0));
            var admTotal = (parseFloat($('#txtpersen_admin1').val() || 0) + parseFloat($('#txtpersen_admin2').val() || 0));
            var mkTotal = (parseFloat($('#txtpersen_mekanik1').val() || 0) + parseFloat($('#txtpersen_mekanik2').val() || 0) + parseFloat($('#txtpersen_mekanik3').val() || 0) + parseFloat($('#txtpersen_mekanik4').val() || 0));

            function label(total){
                return total === 100
                    ? ' <span class="label label-success"><i class="fa fa-check"></i> OK</span>'
                    : ' <span class="label label-warning"><i class="fa fa-exclamation-triangle"></i> Harus 100%</span>';
            }

            if ($('#totalPercentageDisplay').length) {
                var html = ''+
                    '<strong>KM:</strong> ' + kmTotal + '%' + label(kmTotal) +
                    ' &nbsp; | &nbsp; <strong>Mekanik:</strong> ' + mkTotal + '%' + label(mkTotal) +
                    ' &nbsp; | &nbsp; <strong>Admin:</strong> ' + admTotal + '%' + label(admTotal);
                $('#totalPercentageDisplay').html(html);
            }

            $('#totalPersenReguler').text(parseInt(mkTotal, 10) || 0);

            return { km: kmTotal, adm: admTotal, mk: mkTotal };
        };

        // ============ AUTO FILL KEPALA MEKANIK HARIAN ============
        window.autoFillKepalaMetanik = function(autoSave) {
            $.ajax({
                url: 'get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik<?php echo !empty($tanggal_srv) ? ('&tanggal=' . $tanggal_srv) : ''; ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.has_data) {
                        var data = response.data;
                        var filled = false;

                        // Find kepala mekanik 1 in dropdown and select it (match by text or value, case-insensitive)
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

                        // Find kepala mekanik 2 in dropdown and select it (match by text or value, case-insensitive)
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
                        // This will distribute 100% evenly among all selected mechanics
                        if (filled && typeof window.autoCalculatePercentage === 'function') {
                            window.autoCalculatePercentage();
                        }

                        // Save to database if autoSave is true
                        if (autoSave === true) {
                            saveKepalaMetanikToService();
                        }

                        // Show success message
                        showNotification('success', 'Kepala mekanik berhasil di-auto fill! Persentase dibagi otomatis ke semua mekanik.');

                    } else {
                        // Fallback: coba ambil data HARI INI (tanpa parameter tanggal)
                        $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik', function(res2){
                            if (res2 && res2.success && res2.has_data) {
                                var filled2 = false;
                                var data2 = res2.data;
                                // KM1
                                if (data2.kepala_mekanik_1) {
                                    var name1Raw = String(data2.kepala_mekanik_1).trim();
                                    var name1 = name1Raw.toLowerCase();
                                    var found1 = false;
                                    $('#cbokepala_mekanik1 option').each(function(){
                                        var t=$(this).text().trim().toLowerCase();
                                        var v=String($(this).val()||'').trim().toLowerCase();
                                        if (t===name1 || v===name1) { $(this).prop('selected', true).trigger('change'); filled2 = true; found1 = true; return false; }
                                    });
                                    if (!found1) { var $s1=$('#cbokepala_mekanik1'); $s1.append('<option value="'+name1Raw+'">'+name1Raw+'</option>'); $s1.val(name1Raw).trigger('change'); filled2 = true; }
                                }
                                // KM2
                                if (data2.kepala_mekanik_2) {
                                    var name2Raw = String(data2.kepala_mekanik_2).trim();
                                    var name2 = name2Raw.toLowerCase();
                                    var found2 = false;
                                    $('#cbokepala_mekanik2 option').each(function(){
                                        var t=$(this).text().trim().toLowerCase();
                                        var v=String($(this).val()||'').trim().toLowerCase();
                                        if (t===name2 || v===name2) { $(this).prop('selected', true).trigger('change'); filled2 = true; found2 = true; return false; }
                                    });
                                    if (!found2) { var $s2=$('#cbokepala_mekanik2'); $s2.append('<option value="'+name2Raw+'">'+name2Raw+'</option>'); $s2.val(name2Raw).trigger('change'); filled2 = true; }
                                }
                                if (filled2 && typeof window.autoCalculatePercentage==='function') { window.autoCalculatePercentage(); }
                                if (autoSave === true) { saveKepalaMetanikToService(); }
                                showNotification('info', 'Menggunakan data kepala mekanik HARI INI');
                            } else {
                                showNotification('warning', (res2 && res2.message) || 'Belum ada data kepala mekanik harian');
                                // Jangan paksa redirect; tawarkan buka input di tab baru
                                if (response.redirect_url) {
                                    setTimeout(function(){ if (confirm('Buka halaman input Kepala Mekanik Harian?')) { window.open(response.redirect_url, '_blank'); } }, 1000);
                                }
                            }
                        }, 'json');
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
                url: 'get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik<?php echo !empty($tanggal_srv) ? ('&tanggal=' . $tanggal_srv) : ''; ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update alert to success
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
                        showNotification('info', response.message);
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

        // ============ EVENT LISTENERS ============
        // Trigger auto calculation when mechanic is selected or changed
        $('#cbokepala_mekanik1, #cbokepala_mekanik2, #cboadmin1, #cboadmin2, #cbomekanik1, #cbomekanik2, #cbomekanik3, #cbomekanik4').on('change', function() {
            if (this.id === 'cbokepala_mekanik1') { syncKepalaMekanikValidationField(); }
            window.autoCalculatePercentage();
        });

        // Update total when percentage is manually changed
        $('#txtpersen_kepala1, #txtpersen_kepala2, #txtpersen_admin1, #txtpersen_admin2, #txtpersen_mekanik1, #txtpersen_mekanik2, #txtpersen_mekanik3, #txtpersen_mekanik4').on('input change', function() {
            window.updateTotalPercentage();
        });

        // ============ AUTO APPLY MEMBER DISCOUNT & CALCULATE TOTAL ============
        window.calculatePaymentTotal = function() {
            // Parse subtotal (remove formatting)
            var subtotalStr = $('#txttotal').val() || '0';
            var subtotal = parseFloat(subtotalStr.replace(/\./g, '').replace(',', '.')) || 0;

            // Get member discount percentage
            var diskonMemberPersen = parseFloat($('#txtdiskon_member').val()) || 0;

            // Get additional discount percentage
            var diskonTambahanPersen = parseFloat($('#txtpotfaktur_persen').val()) || 0;

            // Calculate discounts
            var diskonMemberNom = subtotal * (diskonMemberPersen / 100);
            var diskonTambahanNom = subtotal * (diskonTambahanPersen / 100);
            var totalDiskon = diskonMemberNom + diskonTambahanNom;

            // Calculate after discount
            var setelahDiskon = subtotal - totalDiskon;

            // Get PPN percentage
            var ppnPersen = parseFloat($('#txtpajak_persen').val()) || 0;
            var ppnNom = setelahDiskon * (ppnPersen / 100);

            // Calculate total
            var totalBayar = setelahDiskon + ppnNom;

            // Format and update fields
            $('#txtpotfaktur_nom').val(formatRupiah(totalDiskon));
            $('#txtpajak_nom').val(formatRupiah(ppnNom));
            $('#txtnet').val(formatRupiah(totalBayar));

            // Store raw values in hidden fields if needed
            if (!$('#txtpotfaktur_nom_raw').length) {
                $('<input type="hidden" id="txtpotfaktur_nom_raw" name="txtpotfaktur_nom_raw">').insertAfter('#txtpotfaktur_nom');
            }
            $('#txtpotfaktur_nom_raw').val(totalDiskon);

            if (!$('#txtpajak_nom_raw').length) {
                $('<input type="hidden" id="txtpajak_nom_raw" name="txtpajak_nom_raw">').insertAfter('#txtpajak_nom');
            }
            $('#txtpajak_nom_raw').val(ppnNom);

            if (!$('#txtnet_raw').length) {
                $('<input type="hidden" id="txtnet_raw" name="txtnet_raw">').insertAfter('#txtnet');
            }
            $('#txtnet_raw').val(totalBayar);

            // Calculate change (kembalian)
            calculateKembalian();

            return {
                subtotal: subtotal,
                diskonMemberPersen: diskonMemberPersen,
                diskonMemberNom: diskonMemberNom,
                diskonTambahanPersen: diskonTambahanPersen,
                diskonTambahanNom: diskonTambahanNom,
                totalDiskon: totalDiskon,
                ppnPersen: ppnPersen,
                ppnNom: ppnNom,
                totalBayar: totalBayar
            };
        };

        // Format number to Rupiah format
        function formatRupiah(number) {
            return Math.round(number).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // Parse Rupiah format to number
        function parseRupiah(str) {
            if (!str) return 0;
            return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // Calculate kembalian
        function calculateKembalian() {
            var totalBayar = parseRupiah($('#txtnet').val());
            var jumlahBayar = parseRupiah($('#txtbayar').val());
            var kembalian = jumlahBayar - totalBayar;

            if (kembalian < 0) kembalian = 0;

            $('#txtkembalian').val(formatRupiah(kembalian));
        }

        // Auto apply member discount on page load
        window.autoApplyMemberDiscount = function() {
            var diskonMember = parseFloat($('#txtdiskon_member').val()) || 0;

            if (diskonMember > 0) {
                // Show notification that discount is applied
                console.log('Auto-applying member discount: ' + diskonMember + '%');
            }

            // Calculate total
            calculatePaymentTotal();
        };

        // ============ EVENT LISTENERS FOR PAYMENT ============
        // When additional discount changes
        $('#txtpotfaktur_persen').on('input change', function() {
            calculatePaymentTotal();
        });

        // When PPN changes
        $('#txtpajak_persen').on('input change', function() {
            calculatePaymentTotal();
        });

        // When payment amount changes
        $('#txtbayar').on('input change blur', function() {
            // Remove formatting for calculation
            var val = $(this).val().replace(/\./g, '');
            $(this).val(formatRupiah(parseFloat(val) || 0));
            calculateKembalian();
        });

        // ============ INITIALIZATION ============
        // Initialize total percentage on page load
        window.updateTotalPercentage();

        // Auto apply member discount
        autoApplyMemberDiscount();

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

        // Jika sudah terisi tetapi berbeda dengan data harian, tawarkan untuk menyamakan
        if (kepala1Value || kepala2Value) {
            $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik<?php echo !empty($tanggal_srv) ? ('&tanggal=' . $tanggal_srv) : ''; ?>', function(res){
                if(res && res.success && res.has_data){
                    var d=res.data;
                    var cur1Val = ( $('#cbokepala_mekanik1').val() || '' ).toLowerCase().trim();
                    var cur1Txt = ( $('#cbokepala_mekanik1 option:selected').text() || '' ).toLowerCase().trim();
                    var cur1 = cur1Val || cur1Txt;
                    var cur2Val = ( $('#cbokepala_mekanik2').val() || '' ).toLowerCase().trim();
                    var cur2Txt = ( $('#cbokepala_mekanik2 option:selected').text() || '' ).toLowerCase().trim();
                    var cur2 = cur2Val || cur2Txt;
                    var daily1 = String(d.kepala_mekanik_1||'').toLowerCase().trim();
                    var daily2 = String(d.kepala_mekanik_2||'').toLowerCase().trim();
                    var mismatch = (daily1 && daily1!==cur1) || (daily2 && daily2!==cur2);
                    if(mismatch){
                        setTimeout(function(){ if(confirm('Data Kepala Mekanik harian berbeda dari servis ini. Terapkan data harian?')){ autoFillKepalaMetanik(true); } }, 600);
                    }
                }
            }, 'json');
        }
        <?php else: ?>
        // Jika untuk tanggal service tidak ada, coba fallback hari ini dulu
        setTimeout(function(){
            $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik', function(res2){
                if(res2 && res2.success && res2.has_data){
                    if (confirm('Auto fill kepala mekanik dari data harian HARI INI?')) { autoFillKepalaMetanik(true); }
                } else {
                    if (confirm('Belum ada input Kepala Mekanik. Buka halaman input sekarang?')) { window.open('input_kepala_mekanik_harian.php','_blank'); }
                }
            }, 'json');
        }, 800);
        <?php endif; ?>
    }
})();
</script>
