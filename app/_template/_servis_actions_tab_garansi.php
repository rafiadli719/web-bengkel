<!-- Tab Actions Garansi - Enhanced with Payment -->
<div class="actions-content">
    <div class="row">
        <div class="col-xs-12">
            <?php 
            include "get_kepala_mekanik_harian.php";
            $kepala_mekanik_harian = getKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
            $has_kepala_mekanik_harian = hasKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
            ?>

            <?php if (!$has_kepala_mekanik_harian): ?>
            <div class="alert alert-warning alert-dismissible" id="alertKepalaMetanikHarian">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="ace-icon fa fa-exclamation-triangle"></i>
                <strong>Perhatian!</strong> Belum ada input kepala mekanik untuk tanggal ini.
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
                <strong>Kepala Mekanik Tanggal Ini:</strong>
                <?php echo htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_1']); ?>
                <?php if($kepala_mekanik_harian['kepala_mekanik_2']): ?>
                & <?php echo htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_2']); ?>
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
                            Pembayaran Service Garansi
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
                            | <i class="ace-icon fa fa-shield"></i> <strong>Service Garansi</strong>
                        </div>
                    </div>
                </div>

                <!-- Cancel History Display (Informational Only) -->
                <?php if (!empty($no_pelanggan)): $query_cancel_stats = "SELECT jumlah_cancel, cancel_rate, tanggal_cancel_terakhir, cancel_customer_request, cancel_no_stock, cancel_no_mekanik, cancel_no_show, cancel_lainnya FROM statistik_pelanggan WHERE no_pelanggan = '$no_pelanggan'"; $result_cancel = mysqli_query($koneksi, $query_cancel_stats); if ($result_cancel && mysqli_num_rows($result_cancel) > 0) { $cancel_stats = mysqli_fetch_assoc($result_cancel); $jumlah_cancel = $cancel_stats['jumlah_cancel'] ?? 0; if ($jumlah_cancel > 0): ?>
                <div class="row"><div class="col-sm-12"><div class="alert" style="background-color: #f9f9f9; border: 1px solid #ddd; margin-bottom: 15px;"><i class="ace-icon fa fa-history"></i> <strong>Riwayat Cancel:</strong> <span class="label label-default"><?php echo $jumlah_cancel; ?> kali</span> <?php if ($cancel_stats['cancel_rate'] > 0): ?>(<?php echo number_format($cancel_stats['cancel_rate'], 1); ?>% dari total booking)<?php endif; ?> <?php if ($cancel_stats['tanggal_cancel_terakhir']): ?>| <small class="text-muted">Terakhir: <?php echo date('d M Y', strtotime($cancel_stats['tanggal_cancel_terakhir'])); ?></small><?php endif; ?><br/><small><strong>Alasan:</strong> <?php $alasan_list = []; if ($cancel_stats['cancel_customer_request'] > 0) $alasan_list[] = "Customer request ({$cancel_stats['cancel_customer_request']}x)"; if ($cancel_stats['cancel_no_stock'] > 0) $alasan_list[] = "Stok habis ({$cancel_stats['cancel_no_stock']}x)"; if ($cancel_stats['cancel_no_mekanik'] > 0) $alasan_list[] = "Mekanik tidak ada ({$cancel_stats['cancel_no_mekanik']}x)"; if ($cancel_stats['cancel_no_show'] > 0) $alasan_list[] = "Customer no-show ({$cancel_stats['cancel_no_show']}x)"; if ($cancel_stats['cancel_lainnya'] > 0) $alasan_list[] = "Lainnya ({$cancel_stats['cancel_lainnya']}x)"; echo !empty($alasan_list) ? implode(', ', $alasan_list) : 'Tidak ada detail'; ?></small></div></div></div>
                <?php endif; } endif; ?>

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
                                    <option value="Tunai">Tunai</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Kartu Kredit">Kartu Kredit</option>
                                    <option value="Kartu Debit">Kartu Debit</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                    <option value="QRIS">QRIS</option>
                                </select>
                            </div>
                        </div>

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

                        <!-- Garansi Specific Info -->
                        <div class="alert alert-success">
                            <i class="ace-icon fa fa-shield"></i>
                            <strong>Info Garansi:</strong> Service ini dilakukan dalam masa garansi dan telah selesai.
                        </div>
                    </div>
                </div>

                <div class="hr hr-24"></div>

                <!-- Mechanic Assignment Section -->
                <div class="row">
                    <div class="col-sm-12">
                        <h5 class="purple">
                            <i class="ace-icon fa fa-users"></i>
                            Penugasan Mekanik Garansi
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

                        <!-- Mekanik 3 -->
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">Mekanik 3:</label>
                            <div class="col-sm-5">
                                <select name="cbomekanik3" id="cbomekanik3" class="form-control">
                                    <option value="">- Pilih Mekanik -</option>
                                    <?php
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
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label class="col-sm-4 control-label no-padding-right">KM Berikut:</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="txtkm_next" name="txtkm_next"
                                           value="<?php echo $km_berikut ?? 0; ?>" min="0" />
                                    <span class="input-group-addon">KM</span>
                                </div>
                                <small class="help-block">KM servis berikutnya</small>
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

                <!-- Action Buttons Section -->
                <div class="row">
                    <div class="col-sm-6">
                        <h5 class="blue">
                            <i class="ace-icon fa fa-shield"></i>
                            Aksi Service Garansi
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
                    <div class="col-xs-4">
                        <button class="btn btn-warning btn-block" type="button" onclick="resetService()">
                            <i class="ace-icon fa fa-refresh"></i>
                            Reset Service
                        </button>
                    </div>
                    <div class="col-xs-4">
                        <button class="btn btn-purple btn-block" type="button" onclick="checkWarranty()">
                            <i class="ace-icon fa fa-shield"></i>
                            Cek Garansi
                        </button>
                    </div>
                    <div class="col-xs-4">
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
(function(){
    function showNotification(type, message){
        var cls='alert-info', icon='fa-info-circle';
        if(type==='success'){cls='alert-success';icon='fa-check-circle';}
        else if(type==='warning'){cls='alert-warning';icon='fa-exclamation-triangle';}
        else if(type==='error'){cls='alert-danger';icon='fa-times-circle';}
        var el=document.createElement('div');
        el.className='alert '+cls+' alert-dismissible';
        el.style.cssText='position:fixed;top:70px;right:20px;z-index:9999;min-width:300px;';
        el.innerHTML='<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                     '<i class="ace-icon fa '+icon+'"></i> '+(message||'');
        document.body.appendChild(el);
        setTimeout(function(){ if(el && el.parentNode){ el.parentNode.removeChild(el);} }, 5000);
    }

    window.updateTotalPercentage = function(){
        var t=0; var $=window.jQuery||function(s){return document.querySelector(s)};
        function gv(id){var v=(window.jQuery?jQuery('#'+id).val():document.getElementById(id)?.value)||0;return parseFloat(v)||0;}
        t+=gv('txtpersen_kepala1'); t+=gv('txtpersen_kepala2'); t+=gv('txtpersen_mekanik1'); t+=gv('txtpersen_mekanik2'); t+=gv('txtpersen_mekanik3'); t+=gv('txtpersen_mekanik4');
        var el = document.getElementById('totalPercentageDisplay');
        if(el){ var color = (t===100)?'green':(t>100?'red':'orange'); el.innerHTML='<strong style="color:'+color+';">Total: '+t+'%<\/strong>'; }
        return t;
    };

    window.autoCalculatePercentage = function(){
        var ids=[];
        if((jQuery('#cbokepala_mekanik1').val()||'')!==''){ids.push('txtpersen_kepala1');}
        if((jQuery('#cbokepala_mekanik2').val()||'')!==''){ids.push('txtpersen_kepala2');}
        if((jQuery('#cbomekanik1').val()||'')!==''){ids.push('txtpersen_mekanik1');}
        if((jQuery('#cbomekanik2').val()||'')!==''){ids.push('txtpersen_mekanik2');}
        if((jQuery('#cbomekanik3').val()||'')!==''){ids.push('txtpersen_mekanik3');}
        if((jQuery('#cbomekanik4').val()||'')!==''){ids.push('txtpersen_mekanik4');}
        if(ids.length>0){
            var per = Math.floor(100/ids.length); var rem = 100 - (per*ids.length);
            ids.forEach(function(id,idx){ jQuery('#'+id).val(per + (idx===0?rem:0)); });
        } else {
            ['txtpersen_kepala1','txtpersen_kepala2','txtpersen_mekanik1','txtpersen_mekanik2','txtpersen_mekanik3','txtpersen_mekanik4'].forEach(function(id){ jQuery('#'+id).val(0); });
        }
        window.updateTotalPercentage();
    };

    window.saveKepalaMetanikToService = function(){
        var noService = '<?php echo $no_service ?? ""; ?>';
        if(!noService) return;
        jQuery.ajax({
            url:'get_kepala_mekanik_harian.php', type:'POST', dataType:'json',
            data:{
                action:'save_kepala_mekanik',
                no_service:noService,
                kepala_mekanik1: jQuery('#cbokepala_mekanik1').val(),
                kepala_mekanik2: jQuery('#cbokepala_mekanik2').val(),
                persen_kepala1: jQuery('#txtpersen_kepala1').val()||0,
                persen_kepala2: jQuery('#txtpersen_kepala2').val()||0
            },
            success:function(r){ if(r && r.success){ showNotification('success','Kepala mekanik disimpan ke service'); } },
            error:function(){ showNotification('error','Gagal menyimpan kepala mekanik'); }
        });
    };

    window.autoFillKepalaMetanik = function(autoSave){
        function applyFill(d){
            var filled=false;
            if(d && d.kepala_mekanik_1){
                var name1Raw = String(d.kepala_mekanik_1).trim();
                var n1 = name1Raw.toLowerCase();
                var found1 = false;
                jQuery('#cbokepala_mekanik1 option').each(function(){
                    var t=jQuery(this).text().trim().toLowerCase();
                    var v=String(jQuery(this).val()||'').trim().toLowerCase();
                    if(t===n1 || v===n1){ jQuery(this).prop('selected',true).trigger('change'); filled=true; found1=true; return false; }
                });
                if(!found1){ var $s1=jQuery('#cbokepala_mekanik1'); $s1.append('<option value="'+name1Raw+'">'+name1Raw+'</option>'); $s1.val(name1Raw).trigger('change'); filled=true; }
            }
            if(d && d.kepala_mekanik_2){
                var name2Raw = String(d.kepala_mekanik_2).trim();
                var n2 = name2Raw.toLowerCase();
                var found2 = false;
                jQuery('#cbokepala_mekanik2 option').each(function(){
                    var t=jQuery(this).text().trim().toLowerCase();
                    var v=String(jQuery(this).val()||'').trim().toLowerCase();
                    if(t===n2 || v===n2){ jQuery(this).prop('selected',true).trigger('change'); filled=true; found2=true; return false; }
                });
                if(!found2){ var $s2=jQuery('#cbokepala_mekanik2'); $s2.append('<option value="'+name2Raw+'">'+name2Raw+'</option>'); $s2.val(name2Raw).trigger('change'); filled=true; }
            }
            if(filled){
                if(typeof window.autoCalculatePercentage==='function'){ window.autoCalculatePercentage(); }
                if(autoSave===true && typeof window.saveKepalaMetanikToService==='function'){ window.saveKepalaMetanikToService(); }
                showNotification('success','Auto fill kepala mekanik berhasil');
                return true;
            }
            return false;
        }

        jQuery.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik&tanggal=<?php echo isset($tanggal_srv) ? $tanggal_srv : ''; ?>', function(res){
            if(res && res.success && res.has_data){
                applyFill(res.data);
            } else {
                // Fallback: coba ambil data kepala mekanik untuk HARI INI (tanpa parameter tanggal)
                jQuery.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik', function(res2){
                    if(res2 && res2.success && res2.has_data){
                        applyFill(res2.data);
                        showNotification('info','Menggunakan data kepala mekanik hari ini');
                    } else {
                        showNotification('warning', (res2&&res2.message)||'Belum ada input kepala mekanik harian');
                        // Jangan redirect otomatis; biarkan user tetap di halaman ini
                    }
                }, 'json');
            }
        }, 'json').fail(function(){ showNotification('error','Gagal mengambil data kepala mekanik harian'); });
    };

    window.saveMechanicData = function() {
        var noService = '<?php echo $no_service ?? ""; ?>';

        if (!noService) {
            alert('Nomor service tidak ditemukan!');
            return;
        }

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

        var button = $('#btnSaveMechanicData');
        button.prop('disabled', true);
        button.html('<i class="ace-icon fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: '_ajax/save_mechanic_data.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                button.prop('disabled', false);
                button.html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');

                if (response.status === 'success') {
                    showNotification('success', 'Data mekanik dan persentase berhasil disimpan!');
                } else {
                    showNotification('error', 'Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                button.prop('disabled', false);
                button.html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
                showNotification('error', 'Terjadi kesalahan saat menyimpan data: ' + error);
            }
        });
    };

    if(typeof jQuery!=='undefined'){
        jQuery(function($){
            $('#cbokepala_mekanik1,#cbokepala_mekanik2,#cbomekanik1,#cbomekanik2,#cbomekanik3,#cbomekanik4').on('change', function(){ window.autoCalculatePercentage(); });
            $('#txtpersen_kepala1,#txtpersen_kepala2,#txtpersen_mekanik1,#txtpersen_mekanik2,#txtpersen_mekanik3,#txtpersen_mekanik4').on('input change', function(){ window.updateTotalPercentage(); });
            window.updateTotalPercentage();
            var k1=$('#cbokepala_mekanik1').val()||'', k2=$('#cbokepala_mekanik2').val()||'';
            $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik&tanggal=<?php echo isset($tanggal_srv) ? $tanggal_srv : ''; ?>', function(res){
                if(res && res.success && res.has_data && !k1 && !k2){
                    setTimeout(function(){ if(confirm('Auto fill kepala mekanik dari data harian tanggal ini?')){ window.autoFillKepalaMetanik(true); } }, 800);
                } else if(res && res.success === true && !res.has_data) {
                    // Fallback: coba data HARI INI terlebih dahulu sebelum menawarkan redirect
                    $.get('get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik', function(res2){
                        if(res2 && res2.success && res2.has_data && !k1 && !k2){
                            setTimeout(function(){ if(confirm('Auto fill kepala mekanik dari data harian HARI INI?')){ window.autoFillKepalaMetanik(true); } }, 800);
                        } else {
                            setTimeout(function(){ if(confirm('Belum ada input Kepala Mekanik. Buka halaman input sekarang?')){ window.open('input_kepala_mekanik_harian.php','_blank'); } }, 800);
                        }
                    }, 'json');
                }
            }, 'json');
        });
    }
})();
</script>
