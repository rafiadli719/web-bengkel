# DOKUMENTASI AJAX ENDPOINTS - TEMUAN & MAPPING

**File**: `_handler_temuan_penawaran.php`
**Tanggal**: 2025-12-04
**Status**: ✅ **IMPLEMENTED**

---

## 📋 DAFTAR ENDPOINT BARU

| No | Endpoint | Method | Fungsi |
|----|----------|--------|---------|
| 1  | `get_parts_by_temuan_kode` | GET | Get parts dari mapping berdasarkan kode temuan |
| 2  | `check_temuan_duplicate` | GET | Check apakah temuan sudah ada di master (prevent duplicate) |
| 3  | `save_temuan_to_master` | POST | Save temuan custom ke master dengan auto-generate kode |

---

## 🎯 ENDPOINT 1: Get Parts by Temuan Kode

### Deskripsi
Mendapatkan list part yang sudah di-mapping ke temuan tertentu dari tabel `tbmaster_temuan_barang_mapping`.

### URL
```
GET _handler_temuan_penawaran.php?action=get_parts_by_temuan_kode&kode_temuan=TMN001
```

### Parameters
| Param | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| `action` | string | ✅ Yes | Harus: `get_parts_by_temuan_kode` |
| `kode_temuan` | string | ✅ Yes | Kode temuan (misal: TMN001) |

### Response Success
```json
{
  "success": true,
  "kode_temuan": "TMN001",
  "strategy": "mapping",
  "count": 2,
  "parts": [
    {
      "mapping_id": 1,
      "kode_barang": "FILTER-001",
      "nama_barang": "Filter Udara Original",
      "harga_jual": 150000,
      "satuan": "pcs",
      "is_primary": 1,
      "prioritas": 1,
      "qty_default": 1,
      "keterangan": "Filter Udara Original (Rekomendasi)",
      "stok_tersedia": 5
    },
    {
      "mapping_id": 2,
      "kode_barang": "FILTER-002",
      "nama_barang": "Filter Udara KW",
      "harga_jual": 75000,
      "satuan": "pcs",
      "is_primary": 0,
      "prioritas": 2,
      "qty_default": 1,
      "keterangan": "Filter Udara KW (Alternatif)",
      "stok_tersedia": 0
    }
  ]
}
```

### Response Error
```json
{
  "success": false,
  "error": "Kode temuan tidak boleh kosong"
}
```

### Field Explanation
- `mapping_id`: ID mapping di tabel tbmaster_temuan_barang_mapping
- `is_primary`: 1 = Primary/Rekomendasi, 0 = Alternatif
- `prioritas`: Urutan tampil (1 = tertinggi)
- `qty_default`: Qty yang disarankan
- `keterangan`: Info tambahan (Original, KW, garansi, dll)
- `stok_tersedia`: Stok di cabang (dari tblstok)

### Contoh Penggunaan JavaScript
```javascript
function getRecommendedParts(kodeTemuan) {
    $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: {
            action: 'get_parts_by_temuan_kode',
            kode_temuan: kodeTemuan
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.count > 0) {
                displayRecommendedParts(response.parts);
            } else {
                console.log('Tidak ada part yang di-mapping untuk temuan ini');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alert('Gagal mengambil data part: ' + error);
        }
    });
}

function displayRecommendedParts(parts) {
    var html = '<div class="alert alert-info">';
    html += '<h4><i class="fa fa-lightbulb-o"></i> Part Yang Direkomendasikan</h4>';
    html += '<div class="list-group">';

    parts.forEach(function(part) {
        var badge = part.is_primary == 1
            ? '<span class="label label-success">REKOMENDASI</span>'
            : '<span class="label label-info">Alternatif</span>';

        var stokBadge = part.stok_tersedia > 0
            ? '<span class="label label-success">Stok: ' + part.stok_tersedia + '</span>'
            : '<span class="label label-warning">Indent</span>';

        html += '<a href="#" class="list-group-item" onclick="selectPart(\'' + part.kode_barang + '\', \'' + part.nama_barang + '\', ' + part.harga_jual + ', ' + part.qty_default + '); return false;">';
        html += '  <div class="row">';
        html += '    <div class="col-xs-8">';
        html += '      <h4>' + badge + ' ' + part.nama_barang + '</h4>';
        html += '      <p>Kode: ' + part.kode_barang + ' | Qty: ' + part.qty_default + ' ' + part.satuan + '</p>';
        if (part.keterangan) {
            html += '      <p><em>' + part.keterangan + '</em></p>';
        }
        html += '    </div>';
        html += '    <div class="col-xs-4 text-right">';
        html += '      <h4>Rp ' + formatNumber(part.harga_jual) + '</h4>';
        html += '      ' + stokBadge;
        html += '    </div>';
        html += '  </div>';
        html += '</a>';
    });

    html += '</div>';
    html += '</div>';

    $('#recommended-parts-container').html(html);
}

function selectPart(kode, nama, harga, qty) {
    $('#kode_barang').val(kode);
    $('#nama_barang').val(nama);
    $('#harga_satuan').val(harga);
    $('#quantity').val(qty);
    $('#total_harga').val(harga * qty);

    // Focus ke button submit
    $('#btnaddpenawaran').focus();

    alert('Part berhasil dipilih! Silakan klik Tambah Penawaran.');
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
```

---

## 🔍 ENDPOINT 2: Check Temuan Duplicate

### Deskripsi
Mengecek apakah nama temuan yang akan di-input sudah ada di master (untuk prevent duplikasi).
Endpoint ini melakukan 2 level checking:
1. **Exact match** - Case insensitive exact match
2. **Similar match** - Tokenization untuk catch typo/variasi nama

### URL
```
GET _handler_temuan_penawaran.php?action=check_temuan_duplicate&nama_temuan=Filter%20Udara%20Kotor
```

### Parameters
| Param | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| `action` | string | ✅ Yes | Harus: `check_temuan_duplicate` |
| `nama_temuan` | string | ✅ Yes | Nama temuan yang mau dicek |

### Response - Exact Match Found
```json
{
  "success": true,
  "duplicate_found": true,
  "match_type": "exact",
  "data": {
    "kode_temuan": "TMN001",
    "nama_temuan": "Filter Udara Kotor",
    "deskripsi": "Filter udara perlu dibersihkan atau diganti",
    "kategori": "Mesin",
    "tingkat_urgensi": "sedang"
  }
}
```

### Response - Similar Match Found
```json
{
  "success": true,
  "duplicate_found": true,
  "match_type": "similar",
  "data": [
    {
      "kode_temuan": "TMN001",
      "nama_temuan": "Filter Udara Kotor",
      "deskripsi": "Filter udara perlu dibersihkan atau diganti",
      "kategori": "Mesin",
      "tingkat_urgensi": "sedang"
    },
    {
      "kode_temuan": "TMN008",
      "nama_temuan": "Filter Oli Kotor",
      "deskripsi": "Filter oli perlu diganti",
      "kategori": "Mesin",
      "tingkat_urgensi": "tinggi"
    }
  ]
}
```

### Response - No Duplicate
```json
{
  "success": true,
  "duplicate_found": false,
  "data": null
}
```

### Response Error
```json
{
  "success": false,
  "error": "Nama temuan tidak boleh kosong"
}
```

### Match Type Explanation
- `exact`: Nama persis sama (case insensitive)
- `similar`: Nama mirip berdasarkan tokenization

### Contoh Penggunaan JavaScript
```javascript
function checkTemuanDuplicate(namaTemuan) {
    return $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: {
            action: 'check_temuan_duplicate',
            nama_temuan: namaTemuan
        },
        dataType: 'json'
    });
}

// Usage dalam form input temuan
$('#nama_temuan').on('blur', function() {
    var namaTemuan = $(this).val().trim();

    if (namaTemuan.length > 3) { // Minimal 3 karakter
        checkTemuanDuplicate(namaTemuan).done(function(response) {
            if (response.success && response.duplicate_found) {
                if (response.match_type === 'exact') {
                    // Exact match - auto-select dari master
                    var data = response.data;

                    var confirmMsg = 'Temuan "' + data.nama_temuan + '" sudah ada di master.\n\n';
                    confirmMsg += 'Kategori: ' + data.kategori + '\n';
                    confirmMsg += 'Urgensi: ' + data.tingkat_urgensi + '\n\n';
                    confirmMsg += 'Gunakan temuan ini dari master?';

                    if (confirm(confirmMsg)) {
                        // Auto-fill dari master
                        $('#kode_temuan').val(data.kode_temuan);
                        $('#nama_temuan').val(data.nama_temuan);
                        $('#deskripsi_temuan').val(data.deskripsi);
                        $('#kategori').val(data.kategori);
                        $('#tingkat_urgensi').val(data.tingkat_urgensi);

                        // Trigger event untuk get recommended parts
                        $('#kode_temuan').trigger('change');

                        alert('Data temuan berhasil diambil dari master!');
                    }

                } else if (response.match_type === 'similar') {
                    // Similar match - tampilkan pilihan
                    var items = response.data;

                    var msg = 'Ditemukan temuan yang mirip:\n\n';
                    items.forEach(function(item, index) {
                        msg += (index + 1) + '. ' + item.nama_temuan + ' (' + item.kategori + ')\n';
                    });
                    msg += '\nApakah Anda maksud salah satu dari temuan di atas?';

                    if (confirm(msg)) {
                        // Tampilkan modal untuk pilih
                        showSimilarTemuanModal(items);
                    }
                }
            } else {
                // No duplicate - bisa lanjut save as new
                console.log('Temuan baru, bisa disimpan');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error checking duplicate:', error);
        });
    }
});

function showSimilarTemuanModal(items) {
    var html = '<div class="modal fade" id="modalSimilarTemuan" tabindex="-1">';
    html += '<div class="modal-dialog">';
    html += '<div class="modal-content">';
    html += '<div class="modal-header">';
    html += '<button type="button" class="close" data-dismiss="modal">&times;</button>';
    html += '<h4 class="modal-title">Pilih Temuan dari Master</h4>';
    html += '</div>';
    html += '<div class="modal-body">';
    html += '<div class="list-group">';

    items.forEach(function(item) {
        html += '<a href="#" class="list-group-item" onclick="useExistingTemuan(\'' + item.kode_temuan + '\'); return false;">';
        html += '  <h4 class="list-group-item-heading">' + item.nama_temuan + '</h4>';
        html += '  <p class="list-group-item-text">';
        html += '    <strong>Kategori:</strong> ' + item.kategori + ' | ';
        html += '    <strong>Urgensi:</strong> ' + item.tingkat_urgensi;
        html += '  </p>';
        if (item.deskripsi) {
            html += '  <p><em>' + item.deskripsi + '</em></p>';
        }
        html += '</a>';
    });

    html += '</div>';
    html += '</div>';
    html += '<div class="modal-footer">';
    html += '<button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>';
    html += '<button type="button" class="btn btn-primary" onclick="saveAsNewTemuan()">Simpan Sebagai Temuan Baru</button>';
    html += '</div>';
    html += '</div>';
    html += '</div>';
    html += '</div>';

    $('body').append(html);
    $('#modalSimilarTemuan').modal('show');

    $('#modalSimilarTemuan').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

function useExistingTemuan(kodeTemuan) {
    // Get full data dan auto-fill
    $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: {
            action: 'get_temuan_by_kode', // Endpoint tambahan jika perlu
            kode_temuan: kodeTemuan
        },
        success: function(response) {
            if (response.success) {
                var data = response.data;
                $('#kode_temuan').val(data.kode_temuan);
                $('#nama_temuan').val(data.nama_temuan);
                $('#deskripsi_temuan').val(data.deskripsi);
                $('#kategori').val(data.kategori);
                $('#tingkat_urgensi').val(data.tingkat_urgensi);

                $('#modalSimilarTemuan').modal('hide');
                alert('Temuan berhasil dipilih dari master!');
            }
        }
    });
}
```

---

## 💾 ENDPOINT 3: Save Temuan to Master

### Deskripsi
Menyimpan temuan custom yang di-input user ke master temuan dengan auto-generate kode temuan.
**Catatan**: Langsung masuk ke master tanpa approval dari pusat.

### URL
```
POST _handler_temuan_penawaran.php
```

### Parameters (POST Body)
| Param | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| `action` | string | ✅ Yes | Harus: `save_temuan_to_master` |
| `nama_temuan` | string | ✅ Yes | Nama temuan (min 3 char) |
| `deskripsi` | string | ❌ No | Deskripsi detail temuan |
| `kategori` | string | ❌ No | Kategori (default: "Lainnya") |
| `tingkat_urgensi` | enum | ❌ No | rendah/sedang/tinggi/kritis (default: "sedang") |

### Response Success
```json
{
  "success": true,
  "message": "Temuan berhasil disimpan ke master",
  "data": {
    "id": 11,
    "kode_temuan": "TMN011",
    "nama_temuan": "Rantai Motor Bunyi",
    "deskripsi": "Rantai motor berbunyi kasar saat jalan",
    "kategori": "Transmisi",
    "tingkat_urgensi": "sedang"
  }
}
```

### Response Error
```json
{
  "success": false,
  "error": "Nama temuan tidak boleh kosong"
}
```

### Auto-Generate Kode Logic
```
Last kode di database: TMN010
→ Extract number: 010 → 10
→ Increment: 10 + 1 = 11
→ New kode: TMN011

Jika belum ada data sama sekali:
→ Start dari: TMN001
```

### Contoh Penggunaan JavaScript
```javascript
function saveTemuanToMaster(data) {
    return $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'POST',
        data: {
            action: 'save_temuan_to_master',
            nama_temuan: data.nama_temuan,
            deskripsi: data.deskripsi,
            kategori: data.kategori,
            tingkat_urgensi: data.tingkat_urgensi
        },
        dataType: 'json'
    });
}

function saveAsNewTemuan() {
    var data = {
        nama_temuan: $('#nama_temuan').val(),
        deskripsi: $('#deskripsi_temuan').val(),
        kategori: $('#kategori').val(),
        tingkat_urgensi: $('#tingkat_urgensi').val()
    };

    if (!data.nama_temuan) {
        alert('Nama temuan harus diisi!');
        return;
    }

    if (confirm('Simpan "' + data.nama_temuan + '" sebagai temuan baru di master?')) {
        saveTemuanToMaster(data).done(function(response) {
            if (response.success) {
                alert(response.message + '\n\nKode Temuan: ' + response.data.kode_temuan);

                // Auto-fill form dengan data yang baru disimpan
                $('#kode_temuan').val(response.data.kode_temuan);
                $('#nama_temuan').val(response.data.nama_temuan);

                // Close modal jika ada
                $('#modalSimilarTemuan').modal('hide');

                // Note: Temuan baru belum ada mapping, jadi tidak ada recommended parts
                alert('Catatan: Temuan baru belum memiliki mapping part.\nSilakan hubungi admin untuk menambahkan mapping.');

            } else {
                alert('Error: ' + response.error);
            }
        }).fail(function(xhr, status, error) {
            alert('Gagal menyimpan temuan: ' + error);
        });
    }
}
```

---

## 🔄 WORKFLOW LENGKAP

### Scenario 1: User Pilih Temuan dari Master

```
1. User pilih temuan dari dropdown/modal
   ↓
2. Trigger event change → AJAX get_parts_by_temuan_kode
   ↓
3. Tampilkan recommended parts (Primary + Alternative)
   ↓
4. User pilih part → Auto-fill form penawaran
   ↓
5. User klik "Tambah Penawaran" → Part masuk ke tbservis_penawaran_part
```

### Scenario 2: User Input Temuan Custom (Tidak Ada di Master)

```
1. User ketik nama temuan di textbox
   ↓
2. Blur event → AJAX check_temuan_duplicate
   ↓
3a. EXACT MATCH FOUND
    → Confirm: "Gunakan dari master?"
    → Yes: Auto-fill dari master + get recommended parts
    → No: Lanjut input custom

3b. SIMILAR MATCH FOUND
    → Modal: "Pilih temuan yang mirip atau simpan sebagai baru?"
    → Pilih existing: Auto-fill + get recommended parts
    → Simpan baru: Lanjut ke step 4

3c. NO DUPLICATE
    → Lanjut ke step 4
   ↓
4. User klik "Tambah Temuan" dengan checkbox "Simpan ke Master"
   ↓
5. AJAX save_temuan_to_master
   ↓
6. Temuan tersimpan dengan kode auto-generate (misal: TMN011)
   ↓
7. Note: Belum ada mapping, manual input part
```

### Scenario 3: Temuan Baru + Langsung Input Part Manual

```
1. User input temuan custom → TIDAK centang "Simpan ke Master"
   ↓
2. Temuan masuk ke tbservis_temuan dengan kode_temuan = NULL
   ↓
3. User manual input part (tidak ada recommended)
   ↓
4. Part masuk ke tbservis_penawaran_part
```

---

## ✅ TESTING CHECKLIST

### Endpoint 1: get_parts_by_temuan_kode
- [ ] Test dengan kode temuan yang valid (TMN001)
- [ ] Test dengan kode temuan yang tidak ada mapping
- [ ] Test dengan kode temuan yang invalid
- [ ] Test tanpa parameter kode_temuan
- [ ] Verify stok tersedia muncul sesuai cabang
- [ ] Verify sorting: primary dulu, lalu alternative
- [ ] Verify qty_default dan keterangan muncul

### Endpoint 2: check_temuan_duplicate
- [ ] Test exact match (case insensitive)
- [ ] Test similar match (dengan token yang sama)
- [ ] Test no match
- [ ] Test dengan nama temuan yang sangat pendek (< 3 char)
- [ ] Test dengan special characters
- [ ] Verify similar match max 3 hasil

### Endpoint 3: save_temuan_to_master
- [ ] Test save temuan baru
- [ ] Test auto-generate kode (TMN011, TMN012, dst)
- [ ] Test dengan nama temuan kosong (error)
- [ ] Test dengan kategori custom
- [ ] Test dengan tingkat_urgensi invalid (default ke 'sedang')
- [ ] Verify data tersimpan di database
- [ ] Verify auto-increment kode benar

### Integration Test
- [ ] Flow: Check duplicate → No duplicate → Save to master → Success
- [ ] Flow: Check duplicate → Exact match → Use existing → Get parts
- [ ] Flow: Check duplicate → Similar match → Choose one → Get parts
- [ ] Flow: Save to master → Get parts (harus kosong karena belum ada mapping)

---

## 📁 FILE TERKAIT

1. **Handler**: `_admincab/_handler_temuan_penawaran.php`
2. **Database Tables**:
   - `tbmaster_temuan` - Master temuan
   - `tbmaster_temuan_barang_mapping` - Mapping temuan ↔ part
   - `tbservis_temuan` - Temuan per service
   - `tblitem` - Master barang/part
   - `tblstok` - Stok per cabang

---

## 🐛 ERROR HANDLING

### Common Errors

#### Error 1: Database connection not available
**Cause**: Session belum di-start atau koneksi DB belum di-include
**Solution**: Pastikan session_start() dan include koneksi.php sudah ada

#### Error 2: Kode temuan tidak boleh kosong
**Cause**: Parameter kode_temuan tidak dikirim
**Solution**: Pastikan URL parameter benar

#### Error 3: Query error
**Cause**: Tabel tidak ada atau struktur berubah
**Solution**: Cek database schema, pastikan semua tabel ada

#### Error 4: Duplicate entry
**Cause**: Save temuan dengan nama yang sudah exact match
**Solution**: Gunakan check_temuan_duplicate dulu sebelum save

---

## 🚀 NEXT STEPS

Setelah endpoint ini berfungsi, langkah selanjutnya:

1. **Update UI Form Input Temuan**
   - Tambah event handler untuk check duplicate
   - Tambah checkbox "Simpan ke Master"
   - Tambah modal untuk show recommended parts

2. **Update UI Form Penawaran**
   - Tambah container untuk tampil recommended parts
   - Auto-fill saat user klik recommended part

3. **Create Management Page**
   - CRUD untuk master temuan
   - CRUD untuk mapping temuan ↔ part

4. **Analytics**
   - Track conversion rate: recommended vs manual
   - Track most used temuan

---

**END OF DOCUMENTATION**

Last Updated: 2025-12-04
Version: 1.0
Status: ✅ Ready for Testing
