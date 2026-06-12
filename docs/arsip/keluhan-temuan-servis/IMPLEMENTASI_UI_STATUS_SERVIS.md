# Implementasi UI Status Pengerjaan - Input Servis

## 📋 Overview

Panduan implementasi tampilan status pengerjaan untuk keluhan, temuan, penawaran, dan work order di halaman input servis.

---

## 🎯 File yang Perlu Diupdate

1. `servis-input-reguler.php`
2. `servis-input-reguler-rst.php`
3. `servis-input-reguler-jemput.php`
4. `servis-input-reguler-jemput-rst.php`
5. `servis-garansi.php`

---

## 📊 Komponen yang Ditambahkan

### **1. Tab Keluhan - Tambah Kolom Status**
### **2. Tab Work Order - Tambah Kolom Status & Progress**
### **3. Tab Temuan & Penawaran - Sudah ada di `_handler_temuan_penawaran.php`**
### **4. Dashboard Summary - Widget Progress**

---

## 🔧 Step-by-Step Implementation

### **STEP 1: Update Query Keluhan (Gunakan View)**

**Lokasi:** Bagian tampilan tabel keluhan di masing-masing file

**SEBELUM:**
```php
$query_keluhan = "SELECT * FROM tbservis_keluhan_status 
                  WHERE no_service='$no_service' 
                  ORDER BY created_at DESC";
```

**SESUDAH:**
```php
$query_keluhan = "SELECT * FROM view_servis_keluhan_lengkap 
                  WHERE no_service='$no_service' 
                  ORDER BY created_at DESC";
```

---

### **STEP 2: Update Tabel Keluhan - Tambah Kolom Status**

**Cari bagian tabel keluhan (biasanya di tab Keluhan), lalu update:**

**SEBELUM:**
```html
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No</th>
            <th>Keluhan</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($row = mysqli_fetch_array($result_keluhan)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['keluhan']; ?></td>
            <td>
                <button class="btn btn-xs btn-danger">Delete</button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
```

**SESUDAH:**
```html
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="35%">Keluhan</th>
            <th width="15%">Status</th>
            <th width="30%">Keterangan</th>
            <th width="15%">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($row = mysqli_fetch_array($result_keluhan)) {
            // Highlight row jika tidak selesai
            $row_class = ($row['status_pengerjaan'] == 'tidak_selesai') ? 'class="danger"' : '';
        ?>
        <tr <?php echo $row_class; ?>>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['keluhan']; ?></td>
            <td>
                <span class="label label-<?php echo $row['status_badge_color']; ?>">
                    <?php 
                    $status_icon = '';
                    switch($row['status_pengerjaan']) {
                        case 'selesai': $status_icon = '<i class="ace-icon fa fa-check"></i> '; break;
                        case 'diproses': $status_icon = '<i class="ace-icon fa fa-cog fa-spin"></i> '; break;
                        case 'tidak_selesai': $status_icon = '<i class="ace-icon fa fa-times"></i> '; break;
                        case 'datang': $status_icon = '<i class="ace-icon fa fa-clock-o"></i> '; break;
                    }
                    echo $status_icon . strtoupper($row['status_pengerjaan']); 
                    ?>
                </span>
            </td>
            <td>
                <?php 
                if($row['status_pengerjaan'] == 'tidak_selesai' && !empty($row['keterangan_tidak_selesai'])) {
                    echo '<strong class="text-danger">' . $row['keterangan_tidak_selesai'] . '</strong>';
                } else {
                    echo '<em class="text-muted">-</em>';
                }
                ?>
            </td>
            <td>
                <button class="btn btn-xs btn-info btn-update-status-keluhan" 
                        data-id="<?php echo $row['id']; ?>"
                        data-keluhan="<?php echo htmlspecialchars($row['keluhan']); ?>"
                        data-status="<?php echo $row['status_pengerjaan']; ?>"
                        data-keterangan="<?php echo htmlspecialchars($row['keterangan_tidak_selesai']); ?>">
                    <i class="ace-icon fa fa-edit"></i> Update Status
                </button>
                <button class="btn btn-xs btn-danger">
                    <i class="ace-icon fa fa-trash"></i> Delete
                </button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
```

---

### **STEP 3: Update Query Work Order (Gunakan View)**

**SEBELUM:**
```php
$query_wo = "SELECT wo.*, woh.nama_wo 
             FROM tbservis_workorder wo
             LEFT JOIN tbworkorderheader woh ON wo.kode_wo = woh.kode_wo
             WHERE wo.no_service='$no_service'";
```

**SESUDAH:**
```php
$query_wo = "SELECT * FROM view_servis_workorder_lengkap 
             WHERE no_service='$no_service' 
             ORDER BY created_at DESC";
```

---

### **STEP 4: Update Tabel Work Order - Tambah Kolom Status**

**SEBELUM:**
```html
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th>No</th>
            <th>Kode WO</th>
            <th>Nama Work Order</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($row = mysqli_fetch_array($result_wo)) {
        ?>
        <tr>
            <td><?php echo $no++; ?></td>
            <td><?php echo $row['kode_wo']; ?></td>
            <td><?php echo $row['nama_wo']; ?></td>
            <td>
                <button class="btn btn-xs btn-danger">Delete</button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
```

**SESUDAH:**
```html
<table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="10%">Kode WO</th>
            <th width="25%">Nama Work Order</th>
            <th width="15%">Status</th>
            <th width="15%">Progress</th>
            <th width="20%">Keterangan</th>
            <th width="10%">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        while($row = mysqli_fetch_array($result_wo)) {
            $row_class = ($row['status_pengerjaan'] == 'tidak_selesai') ? 'class="danger"' : '';
            $progress = $row['progress_percentage'];
            $progress_color = $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
        ?>
        <tr <?php echo $row_class; ?>>
            <td><?php echo $no++; ?></td>
            <td><strong><?php echo $row['kode_wo']; ?></strong></td>
            <td><?php echo $row['nama_wo']; ?></td>
            <td>
                <span class="label label-<?php echo $row['status_badge_color']; ?>">
                    <?php 
                    $status_icon = '';
                    switch($row['status_pengerjaan']) {
                        case 'selesai': $status_icon = '<i class="ace-icon fa fa-check"></i> '; break;
                        case 'diproses': $status_icon = '<i class="ace-icon fa fa-cog fa-spin"></i> '; break;
                        case 'tidak_selesai': $status_icon = '<i class="ace-icon fa fa-times"></i> '; break;
                    }
                    echo $status_icon . strtoupper($row['status_pengerjaan']); 
                    ?>
                </span>
            </td>
            <td>
                <div class="progress" style="margin-bottom: 0; height: 20px;">
                    <div class="progress-bar progress-bar-<?php echo $progress_color; ?>" 
                         style="width: <?php echo $progress; ?>%">
                        <?php echo $progress; ?>%
                    </div>
                </div>
            </td>
            <td>
                <?php 
                if($row['status_pengerjaan'] == 'tidak_selesai' && !empty($row['keterangan_tidak_selesai'])) {
                    echo '<strong class="text-danger">' . $row['keterangan_tidak_selesai'] . '</strong>';
                } else {
                    echo '<em class="text-muted">-</em>';
                }
                ?>
            </td>
            <td>
                <button class="btn btn-xs btn-info btn-update-status-wo" 
                        data-id="<?php echo $row['id']; ?>"
                        data-kode="<?php echo $row['kode_wo']; ?>"
                        data-nama="<?php echo htmlspecialchars($row['nama_wo']); ?>"
                        data-status="<?php echo $row['status_pengerjaan']; ?>"
                        data-keterangan="<?php echo htmlspecialchars($row['keterangan_tidak_selesai']); ?>">
                    <i class="ace-icon fa fa-edit"></i> Update
                </button>
                <button class="btn btn-xs btn-danger">
                    <i class="ace-icon fa fa-trash"></i> Delete
                </button>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
```

---

### **STEP 5: Tambah Modal Update Status Keluhan**

**Tambahkan sebelum tag `</body>` atau di bagian modals:**

```html
<!-- Modal Update Status Keluhan -->
<div class="modal fade" id="modalUpdateStatusKeluhan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="" id="formUpdateStatusKeluhan">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-edit"></i> Update Status Keluhan
                    </h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="keluhan_id" id="keluhan_id">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                    
                    <div class="form-group">
                        <label>Keluhan:</label>
                        <input type="text" class="form-control" id="keluhan_text" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Pengerjaan: <span class="text-danger">*</span></label>
                        <select name="status_keluhan" id="status_keluhan" class="form-control" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="datang">Datang (Baru)</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="keterangan_keluhan_group" style="display:none;">
                        <label>Keterangan Tidak Selesai: <span class="text-danger">*</span></label>
                        <textarea name="keterangan_keluhan" id="keterangan_keluhan" 
                                  class="form-control" rows="3" 
                                  placeholder="Jelaskan alasan kenapa tidak selesai..."></textarea>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            Keterangan ini akan masuk ke catatan servis
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-primary">
                        <i class="ace-icon fa fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

### **STEP 6: Tambah Modal Update Status Work Order**

```html
<!-- Modal Update Status Work Order -->
<div class="modal fade" id="modalUpdateStatusWO" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="" id="formUpdateStatusWO">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="ace-icon fa fa-edit"></i> Update Status Work Order
                    </h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="wo_id" id="wo_id">
                    <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                    
                    <div class="form-group">
                        <label>Work Order:</label>
                        <input type="text" class="form-control" id="wo_text" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Status Pengerjaan: <span class="text-danger">*</span></label>
                        <select name="status_wo" id="status_wo" class="form-control" required>
                            <option value="">-- Pilih Status --</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="keterangan_wo_group" style="display:none;">
                        <label>Keterangan Tidak Selesai: <span class="text-danger">*</span></label>
                        <textarea name="keterangan_wo" id="keterangan_wo" 
                                  class="form-control" rows="3" 
                                  placeholder="Jelaskan alasan kenapa tidak selesai..."></textarea>
                        <span class="help-block">
                            <i class="ace-icon fa fa-info-circle"></i> 
                            Keterangan ini akan masuk ke catatan servis
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" name="btnupdatestatuswo" class="btn btn-primary">
                        <i class="ace-icon fa fa-save"></i> Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

### **STEP 7: Tambah JavaScript untuk Modal**

**Tambahkan di bagian `<script>` sebelum `</body>`:**

```javascript
<script>
$(document).ready(function() {
    // ========== KELUHAN STATUS ==========
    // Open modal update status keluhan
    $('.btn-update-status-keluhan').click(function() {
        var id = $(this).data('id');
        var keluhan = $(this).data('keluhan');
        var status = $(this).data('status');
        var keterangan = $(this).data('keterangan');
        
        $('#keluhan_id').val(id);
        $('#keluhan_text').val(keluhan);
        $('#status_keluhan').val(status);
        $('#keterangan_keluhan').val(keterangan);
        
        // Show/hide keterangan field
        if(status == 'tidak_selesai') {
            $('#keterangan_keluhan_group').show();
        } else {
            $('#keterangan_keluhan_group').hide();
        }
        
        $('#modalUpdateStatusKeluhan').modal('show');
    });
    
    // Show/hide keterangan field for Keluhan
    $('#status_keluhan').change(function() {
        if($(this).val() == 'tidak_selesai') {
            $('#keterangan_keluhan_group').slideDown();
            $('#keterangan_keluhan').attr('required', true);
        } else {
            $('#keterangan_keluhan_group').slideUp();
            $('#keterangan_keluhan').attr('required', false);
            $('#keterangan_keluhan').val('');
        }
    });
    
    // Validation before submit keluhan
    $('#formUpdateStatusKeluhan').submit(function(e) {
        var status = $('#status_keluhan').val();
        var keterangan = $('#keterangan_keluhan').val();
        
        if(status == 'tidak_selesai' && keterangan.trim() == '') {
            alert('Keterangan tidak selesai harus diisi!');
            e.preventDefault();
            return false;
        }
    });
    
    // ========== WORK ORDER STATUS ==========
    // Open modal update status work order
    $('.btn-update-status-wo').click(function() {
        var id = $(this).data('id');
        var kode = $(this).data('kode');
        var nama = $(this).data('nama');
        var status = $(this).data('status');
        var keterangan = $(this).data('keterangan');
        
        $('#wo_id').val(id);
        $('#wo_text').val(kode + ' - ' + nama);
        $('#status_wo').val(status);
        $('#keterangan_wo').val(keterangan);
        
        // Show/hide keterangan field
        if(status == 'tidak_selesai') {
            $('#keterangan_wo_group').show();
        } else {
            $('#keterangan_wo_group').hide();
        }
        
        $('#modalUpdateStatusWO').modal('show');
    });
    
    // Show/hide keterangan field for Work Order
    $('#status_wo').change(function() {
        if($(this).val() == 'tidak_selesai') {
            $('#keterangan_wo_group').slideDown();
            $('#keterangan_wo').attr('required', true);
        } else {
            $('#keterangan_wo_group').slideUp();
            $('#keterangan_wo').attr('required', false);
            $('#keterangan_wo').val('');
        }
    });
    
    // Validation before submit work order
    $('#formUpdateStatusWO').submit(function(e) {
        var status = $('#status_wo').val();
        var keterangan = $('#keterangan_wo').val();
        
        if(status == 'tidak_selesai' && keterangan.trim() == '') {
            alert('Keterangan tidak selesai harus diisi!');
            e.preventDefault();
            return false;
        }
    });
});
</script>
```

---

### **STEP 8: Update Handler PHP untuk Update Status Work Order**

**Tambahkan di bagian handler (setelah handler keluhan):**

```php
// Handler Update Status Work Order
if(isset($_POST['btnupdatestatuswo'])) {
    $wo_id = $_POST['wo_id'];
    $status_wo = $_POST['status_wo'];
    $keterangan = $_POST['keterangan_wo'] ?? '';
    
    $wo_id = mysqli_real_escape_string($koneksi, $wo_id);
    $status_wo = mysqli_real_escape_string($koneksi, $status_wo);
    $keterangan = mysqli_real_escape_string($koneksi, $keterangan);
    
    $query = "UPDATE tbservis_workorder 
              SET status_pengerjaan='$status_wo', 
                  keterangan_tidak_selesai='$keterangan' 
              WHERE id='$wo_id'";
    
    mysqli_query($koneksi, $query);
    
    echo "<script>
        alert('Status work order berhasil diupdate!');
        window.location='servis-input-reguler.php?snoserv=$no_service';
    </script>";
    exit();
}
```

---

### **STEP 9: Tambah Dashboard Summary Widget (OPTIONAL)**

**Tambahkan di bagian atas halaman (setelah info service):**

```php
<?php
// Get summary status
$query_summary = "SELECT * FROM view_servis_status_summary WHERE no_service='$no_service'";
$result_summary = mysqli_query($koneksi, $query_summary);
$summary = mysqli_fetch_array($result_summary);

if($summary) {
    $progress = $summary['progress_percentage'];
    $progress_color = $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
?>
<div class="row">
    <div class="col-xs-12">
        <div class="widget-box">
            <div class="widget-header widget-header-flat">
                <h4 class="widget-title">
                    <i class="ace-icon fa fa-dashboard"></i> Progress Pengerjaan
                </h4>
            </div>
            <div class="widget-body">
                <div class="widget-main">
                    <div class="row">
                        <div class="col-xs-12">
                            <h5>Overall Progress</h5>
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar progress-bar-<?php echo $progress_color; ?>" 
                                     style="width: <?php echo $progress; ?>%">
                                    <strong><?php echo $progress; ?>% Complete</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Keluhan Stats -->
                        <div class="col-sm-6 col-md-3">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5><i class="ace-icon fa fa-list"></i> Keluhan</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main padding-6">
                                        <p>Total: <strong><?php echo $summary['total_keluhan']; ?></strong></p>
                                        <p class="text-success">Selesai: <?php echo $summary['keluhan_selesai']; ?></p>
                                        <p class="text-warning">Diproses: <?php echo $summary['keluhan_diproses']; ?></p>
                                        <p class="text-danger">Tidak Selesai: <?php echo $summary['keluhan_tidak_selesai']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Work Order Stats -->
                        <div class="col-sm-6 col-md-3">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5><i class="ace-icon fa fa-clipboard"></i> Work Order</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main padding-6">
                                        <p>Total: <strong><?php echo $summary['total_workorder']; ?></strong></p>
                                        <p class="text-success">Selesai: <?php echo $summary['workorder_selesai']; ?></p>
                                        <p class="text-warning">Diproses: <?php echo $summary['workorder_diproses']; ?></p>
                                        <p class="text-danger">Tidak Selesai: <?php echo $summary['workorder_tidak_selesai']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Temuan Stats -->
                        <div class="col-sm-6 col-md-3">
                            <div class="widget-box">
                                <div class="widget-header widget-header-flat widget-header-small">
                                    <h5><i class="ace-icon fa fa-search"></i> Temuan</h5>
                                </div>
                                <div class="widget-body">
                                    <div class="widget-main padding-6">
                                        <p>Total: <strong><?php echo $summary['total_temuan']; ?></strong></p>
                                        <p class="text-success">Selesai: <?php echo $summary['temuan_selesai']; ?></p>
                                        <p class="text-primary">Disetujui: <?php echo $summary['temuan_disetujui']; ?></p>
                                        <p class="text-danger">Ditolak: <?php echo $summary['temuan_ditolak']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>
```

---

## ✅ Checklist Implementasi

### **Untuk setiap file (5 files):**

- [ ] Update query keluhan menggunakan `view_servis_keluhan_lengkap`
- [ ] Update tabel keluhan dengan kolom status & keterangan
- [ ] Update query work order menggunakan `view_servis_workorder_lengkap`
- [ ] Update tabel work order dengan kolom status, progress & keterangan
- [ ] Tambah modal update status keluhan
- [ ] Tambah modal update status work order
- [ ] Tambah JavaScript untuk modal handling
- [ ] Tambah handler PHP untuk update status work order
- [ ] (Optional) Tambah dashboard summary widget
- [ ] Test semua fungsi update status

---

## 📝 Catatan Penting

### **1. Handler Update Status Keluhan**
Handler sudah ada di semua file (`btnupdatestatuskeluhan`), tidak perlu ditambahkan lagi.

### **2. Handler Update Status Work Order**
Perlu ditambahkan di file yang belum punya:
- ✅ `servis-input-reguler-rst.php` (sudah ada)
- ✅ `servis-input-reguler-jemput.php` (sudah ada)
- ✅ `servis-input-reguler-jemput-rst.php` (sudah ada)
- ❌ `servis-input-reguler.php` (perlu ditambah)
- ❌ `servis-garansi.php` (perlu ditambah)

### **3. Tab Temuan & Penawaran**
Sudah dihandle oleh `_handler_temuan_penawaran.php`, tidak perlu modifikasi.

### **4. Auto-collect Catatan**
Implementasi di handler pembayaran (`btnbayar`) untuk mengumpulkan keterangan tidak selesai ke catatan servis.

---

## 🚀 Urutan Implementasi yang Disarankan

1. **Run SQL Fix** terlebih dahulu (buat views)
2. **Update file `servis-input-reguler.php`** sebagai template
3. **Test** semua fungsi di file pertama
4. **Copy implementasi** ke 4 file lainnya
5. **Adjust** sesuai kebutuhan masing-masing file
6. **Test** semua file

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check console browser untuk JavaScript error
2. Check PHP error log
3. Verify view sudah dibuat dengan benar
4. Test query manual di phpMyAdmin

---

**Last Updated:** 2025-01-09  
**Version:** 1.0  
**Status:** Ready to Implement
