# ANALISA LENGKAP SISTEM STATUS PENGERJAAN SERVIS - WEB BENGKEL FIT MOTOR

**Tanggal:** 27 Desember 2025
**Version:** 1.0
**Status:** Analisa Komprehensif

---

## DAFTAR ISI

1. [Executive Summary](#executive-summary)
2. [Struktur Database Status Servis](#struktur-database-status-servis)
3. [Flow Status Pengerjaan](#flow-status-pengerjaan)
4. [Mekanisme Edit vs Read-Only](#mekanisme-edit-vs-read-only)
5. [Guard & Validasi](#guard-validasi)
6. [Handler Pembayaran](#handler-pembayaran)
7. [Implementasi di Semua Halaman Input](#implementasi-di-semua-halaman-input)
8. [Rekomendasi Perbaikan](#rekomendasi-perbaikan)
9. [Checklist Implementasi](#checklist-implementasi)

---

## EXECUTIVE SUMMARY

Sistem status pengerjaan servis di Web Bengkel menggunakan **4 level status** untuk tracking progress servis:

| Status | Fase | Editable | Keterangan |
|--------|------|----------|------------|
| **datang** | Initial | ✅ Yes | Customer baru datang / motor diterima |
| **diproses** | In Progress | ✅ Yes | Sedang dikerjakan mekanik |
| **selesai** | Completed | ❌ No (Read-Only) | Pekerjaan selesai, belum bayar |
| **bayar** | Paid | ❌ No (Read-Only) | Invoice sudah lunas |
| **cancel** | Cancelled | ❌ No (Read-Only) | Servis dibatalkan |

**Enforcement Mechanism:**
- **UI Level:** Button "Edit" berubah jadi "Lihat" di `servis-reguler.php`
- **Server Level:** Auto-redirect ke file `*-rst.php` (read-only) jika status = selesai/bayar
- **Database Level:** Status ENUM di tblservice memastikan hanya nilai valid

---

## STRUKTUR DATABASE STATUS SERVIS

### Tabel Utama: `tblservice`

**File SQL:** `fitmotor_dbbengkel.sql`, `fitmotor_dbbengkel_FIXED_V7.sql`

#### Kolom Status & Flag

```sql
CREATE TABLE `tblservice` (
  `no_service` VARCHAR(50) PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `jam` TIME NOT NULL,
  `no_pelanggan` VARCHAR(20),
  `no_polisi` VARCHAR(20),

  -- STATUS COLUMNS
  `status_servis` ENUM('datang','diproses','selesai','bayar','cancel')
                  DEFAULT 'datang' COMMENT 'Status utama pengerjaan',
  `status` VARCHAR(1) DEFAULT '1'
           COMMENT 'Legacy status: 0=draft, 1=aktif, 2=selesai, 3=batal',
  `status_jemput` VARCHAR(1) DEFAULT '0'
                  COMMENT 'Flag jemput antar: 0=tidak, 1=ya',

  -- PAYMENT COLUMNS
  `total_grand` DOUBLE DEFAULT 0 COMMENT 'Total invoice keseluruhan',
  `bayar` DOUBLE DEFAULT 0 COMMENT 'Jumlah yang dibayar customer',
  `kembali` DOUBLE DEFAULT 0 COMMENT 'Kembalian',
  `jenis_bayar` INT(11) COMMENT 'FK ke tbjenis_bayar (Cash/Transfer/EDC)',

  -- JEMPUT ANTAR COLUMNS (jika status_jemput = '1')
  `kondisi_motor` ENUM('jalan','mogok') COMMENT 'Kondisi saat dijemput',
  `jarak_jemput` DECIMAL(5,1) COMMENT 'Jarak tempuh dalam KM',
  `tarif_jemput` INT(11) COMMENT 'Biaya jemput dalam Rupiah',

  -- TIMESTAMPS
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,

  KEY `idx_status_servis` (`status_servis`),
  KEY `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Detail Items Servis

**Tabel: `tblservis_barang`** (Spare parts yang dipakai)
```sql
CREATE TABLE `tblservis_barang` (
  `no_service` VARCHAR(50),
  `nobaris` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_item` VARCHAR(20) COMMENT 'Kode barang',
  `quantity` INT(11) DEFAULT 1,
  `qty_retur` INT(11) DEFAULT 0 COMMENT 'Jumlah barang return/ganti',
  `harga_jual` DOUBLE DEFAULT 0,
  `potongan` DOUBLE DEFAULT 0 COMMENT 'Diskon dalam %',
  `total` DOUBLE DEFAULT 0 COMMENT 'quantity * harga_jual * (1 - potongan/100)',

  KEY `idx_no_service` (`no_service`),
  FOREIGN KEY (`no_service`) REFERENCES `tblservice`(`no_service`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Tabel: `tblservis_jasa`** (Service/jasa yang dikerjakan)
```sql
CREATE TABLE `tblservis_jasa` (
  `no_service` VARCHAR(50),
  `nobaris` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_item` VARCHAR(20) COMMENT 'Kode jasa (e.g., JEMPUT-ANTAR)',
  `harga` DOUBLE DEFAULT 0,
  `waktu` INT(11) DEFAULT 0 COMMENT 'Estimasi waktu dalam menit',
  `potongan` DOUBLE DEFAULT 0 COMMENT 'Diskon dalam %',
  `total` DOUBLE DEFAULT 0,

  KEY `idx_no_service` (`no_service`),
  FOREIGN KEY (`no_service`) REFERENCES `tblservice`(`no_service`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabel Antrian: `tb_antrian_servis`

```sql
CREATE TABLE `tb_antrian_servis` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_service` VARCHAR(50) UNIQUE,
  `no_antrian` INT(11) NOT NULL COMMENT 'Nomor antrian harian',
  `tanggal` DATE NOT NULL,
  `jam` TIME NOT NULL COMMENT 'Jam daftar antrian',
  `jam_mulai` TIME NULL COMMENT 'Jam mulai dikerjakan',
  `jam_selesai` TIME NULL COMMENT 'Jam selesai dikerjakan',

  -- STATUS ANTRIAN
  `status_antrian` ENUM('menunggu','diproses','selesai','batal')
                   DEFAULT 'menunggu',

  `prioritas` ENUM('normal','urgent','vip') DEFAULT 'normal',
  `tipe_service` VARCHAR(50) COMMENT 'reguler/jemput/garansi',

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP,

  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status_antrian` (`status_antrian`),
  FOREIGN KEY (`no_service`) REFERENCES `tblservice`(`no_service`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Relasi Status:**

| Status Antrian | Status Servis | Keterangan |
|----------------|---------------|------------|
| menunggu | datang | Customer sudah daftar, menunggu giliran |
| diproses | diproses | Mekanik sedang mengerjakan |
| selesai | selesai/bayar | Pekerjaan rampung |
| batal | cancel | Dibatalkan (customer tidak jadi / part tidak tersedia) |

---

## FLOW STATUS PENGERJAAN

### Flow Normal (Happy Path)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                        ALUR STATUS SERVIS NORMAL                         │
└──────────────────────────────────────────────────────────────────────────┘

TAHAP 1: PENERIMAAN
┌──────────────┐
│   datang     │  ← Customer datang / Motor dijemput
│              │    - Buat no_service baru
│  EDITABLE ✅ │    - Input data kendaraan
└──────┬───────┘    - Input keluhan
       │
       │ Mekanik mulai diagnosa & kerjakan
       v

TAHAP 2: PENGERJAAN
┌──────────────┐
│  diproses    │  ← Mekanik sedang mengerjakan
│              │    - Tambah spare parts
│  EDITABLE ✅ │    - Tambah jasa service
└──────┬───────┘    - Update progress
       │
       │ Pekerjaan selesai, cek kualitas
       v

TAHAP 3: SELESAI (Menunggu Pembayaran)
┌──────────────┐
│   selesai    │  ← Pekerjaan selesai, invoice dibuat
│              │    - Customer review invoice
│ READ-ONLY ❌ │    - Tidak bisa edit item lagi
└──────┬───────┘    - Redirect ke *-rst.php otomatis
       │
       │ Customer bayar
       v

TAHAP 4: LUNAS
┌──────────────┐
│    bayar     │  ← Invoice sudah dibayar lunas
│              │    - Data service FINAL
│ READ-ONLY ❌ │    - Statistik customer di-update
└──────────────┘    - Motor bisa diambil

FLOW CANCEL:
┌──────────────┐
│    cancel    │  ← Bisa dari tahap manapun
│              │    - Customer tidak jadi
│ READ-ONLY ❌ │    - Part tidak tersedia
└──────────────┘    - Alasan lain
```

### State Transition Rules

```javascript
// ALLOWED TRANSITIONS
datang    → diproses  ✅ (mekanik mulai kerja)
datang    → cancel    ✅ (customer tidak jadi)
diproses  → selesai   ✅ (pekerjaan rampung)
diproses  → cancel    ✅ (ada masalah)
selesai   → bayar     ✅ (pembayaran lunas)
selesai   → diproses  ⚠️ (ada revisi - JARANG)

// FORBIDDEN TRANSITIONS
bayar     → *         ❌ (FINAL STATE - tidak bisa diubah)
cancel    → *         ❌ (FINAL STATE - tidak bisa diubah)
```

---

## MEKANISME EDIT VS READ-ONLY

### File Mapping

| Tipe Servis | Edit Mode (Editable) | Read-Only Mode |
|-------------|----------------------|----------------|
| **Reguler** | `servis-input-reguler.php` | `servis-input-reguler-rst.php` |
| **Jemput Antar** | `servis-input-reguler-jemput.php` | `servis-input-reguler-jemput-rst.php` |
| **Garansi** | `servis-garansi.php` | `servis-garansi-rst.php` |

### Routing Logic di `servis-reguler.php`

**Lokasi:** `_admincab/servis-reguler.php`, baris 368-395

```php
// Determine correct edit URL based on service type and status
// If finished/bayar, force RST (Read Only)
$is_finished = ($status_servis == 'selesai' || $status_servis == 'bayar' || $status == '2');

if ($status_jemput == '1') {
    // SERVIS JEMPUT ANTAR
    $edit_url = $is_finished ? 'servis-input-reguler-jemput-rst.php' :
                              'servis-input-reguler-jemput.php';
    $edit_label = $is_finished ? 'Lihat Servis Jemput' : 'Edit Servis Jemput';

} elseif (strpos(strtolower($tampil['tipe_service'] ?? ''), 'garansi') !== false) {
    // SERVIS GARANSI
    $edit_url = $is_finished ? 'servis-garansi-rst.php' : 'servis-garansi.php';
    $edit_label = $is_finished ? 'Lihat Servis Garansi' : 'Edit Servis Garansi';

} else {
    // SERVIS REGULER
    $edit_url = $is_finished ? 'servis-input-reguler-rst.php' :
                              'servis-input-reguler.php';
    $edit_label = $is_finished ? 'Lihat Servis Reguler' : 'Edit Servis Reguler';
}
```

**Render di UI:**
```php
<a href="<?php echo $edit_url; ?>?snoserv=<?php echo urlencode($tampil['no_service']); ?>">
    <i class="ace-icon fa <?php echo $is_finished ? 'fa-eye' : 'fa-edit'; ?>"></i>
    <?php echo $edit_label; ?>
</a>
```

**Visual Indicator:**
- Icon: `fa-edit` (pensil) untuk editable, `fa-eye` (mata) untuk readonly
- Label: "Edit" vs "Lihat"

---

## GUARD & VALIDASI

### Double-Guard Protection System

#### Guard Level 1: UI Protection (List Page)

**File:** `servis-reguler.php`

**Purpose:** Menampilkan link yang benar berdasarkan status

**Weakness:** User masih bisa manually ketik URL edit page

```php
// Hanya mencegah di UI, tidak mencegah direct access
// User bisa bypass dengan cara:
// http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php?snoserv=SRV-20251227-0001
```

#### Guard Level 2: Server-Side Protection (Edit Page Header)

**File:** `servis-input-reguler-jemput.php`, baris 73-86

**Purpose:** Force redirect ke readonly page jika status sudah final

**Implementation:**
```php
<?php
// GUARD: Redirect to RST page if service already finished/paid
if (!empty($no_service)) {
    // Sanitize input
    $__ns = mysqli_real_escape_string($koneksi, $no_service);

    // Query database untuk check status
    $__q = mysqli_query($koneksi, "SELECT status_servis FROM tblservice
                                    WHERE no_service='".$__ns."' LIMIT 1");

    if ($__q && ($__r = mysqli_fetch_assoc($__q))) {
        $__st = strtolower($__r['status_servis'] ?? '');

        // Check if status is FINAL (selesai atau bayar)
        if ($__st === 'selesai' || $__st === 'bayar') {
            // Build redirect URL ke RST page
            $__redir = 'servis-input-reguler-jemput-rst.php?snoserv=' .
                       urlencode($no_service);

            // Preserve tab parameter
            if (isset($_GET['tab']) && $_GET['tab'] !== '') {
                $__redir .= '&tab=' . urlencode($_GET['tab']);
            }

            // Force redirect
            header('Location: ' . $__redir);
            exit;
        }
    }
}
?>
```

**Protection Coverage:**
- ✅ Mencegah direct URL access ke edit page
- ✅ Preserve tab state saat redirect
- ✅ Check database real-time (bukan session cache)
- ⚠️ Tidak check user permission (hanya check status)

### Guard Implementation di Semua File

**Lokasi Guard Yang Sudah Ada:**

| File | Baris | Status |
|------|-------|--------|
| `servis-input-reguler-jemput.php` | 73-86 | ✅ Implemented |
| `servis-garansi.php` | 48-61 | ✅ Implemented |
| `servis-input-reguler.php` | TBD | ❌ **BELUM ADA** |
| `servis-input-reguler-rst.php` | N/A | ✅ Already readonly |

**ACTION REQUIRED:**
Tambahkan guard di `servis-input-reguler.php` (file reguler non-jemput)

---

## HANDLER PEMBAYARAN

### File: `servis-reguler-byr.php`

#### Update Status Handler

**Lokasi:** Baris 141-150

```php
// ========== HANDLER: UPDATE STATUS SERVIS ==========
if(isset($_POST['btnupdatestatus'])) {
    $no_service = $_POST['txtnosrv'];
    $status_servis_baru = $_POST['cbostatus'];  // ⚠️ TIDAK ADA SANITASI!

    // Update status di database
    mysqli_query($koneksi,"UPDATE tblservice
                           SET status_servis='$status_servis_baru'
                           WHERE no_service='$no_service'");

    // Redirect back to payment page
    $txtcaribrg = $_POST['txtcaribrg'] ?? '';
    $txtcarisrv = $_POST['txtcarisrv'] ?? '';
    $txtcariwo = $_POST['txtcariwo'] ?? '';

    echo"<script>
        window.location=('servis-reguler-byr.php?snoserv=$no_service&kd=$txtcaribrg&kdjasa=$txtcarisrv&kdwo=$txtcariwo');
    </script>";
}
```

#### Dropdown Status Options

```html
<div class="form-group">
    <label class="col-sm-3 control-label no-padding-right">Status Servis:</label>
    <div class="col-sm-9">
        <select name="cbostatus" class="form-control" required>
            <option value="datang" <?php if($status_servis=='datang') echo 'selected'; ?>>
                Datang
            </option>
            <option value="diproses" <?php if($status_servis=='diproses') echo 'selected'; ?>>
                Diproses
            </option>
            <option value="selesai" <?php if($status_servis=='selesai') echo 'selected'; ?>>
                Selesai (Belum Bayar)
            </option>
            <option value="bayar" <?php if($status_servis=='bayar') echo 'selected'; ?>>
                Bayar (Lunas)
            </option>
            <option value="cancel" <?php if($status_servis=='cancel') echo 'selected'; ?>>
                Cancel (Batal)
            </option>
        </select>
    </div>
</div>

<button type="submit" name="btnupdatestatus" class="btn btn-primary">
    <i class="ace-icon fa fa-save"></i> Update Status
</button>
```

#### Vulnerability: SQL Injection Risk

```php
// CURRENT CODE (VULNERABLE)
$status_servis_baru = $_POST['cbostatus'];  // No escaping!
mysqli_query($koneksi,"UPDATE tblservice SET status_servis='$status_servis_baru'...");

// SECURE VERSION (RECOMMENDED)
$status_servis_baru = mysqli_real_escape_string($koneksi, $_POST['cbostatus']);

// OR BETTER: Use prepared statements
$stmt = mysqli_prepare($koneksi, "UPDATE tblservice SET status_servis=? WHERE no_service=?");
mysqli_stmt_bind_param($stmt, "ss", $status_servis_baru, $no_service);
mysqli_stmt_execute($stmt);
```

### Payment Flow dengan Status Update

```
USER ACTION                    DATABASE UPDATE                  UI STATE
────────────────────────────────────────────────────────────────────────────

1. Buka Payment Page
   servis-reguler-byr.php      SELECT * FROM tblservice         Show invoice
                               WHERE no_service=...              + Status dropdown

2. Review Invoice
   - Check items                                                 Current status:
   - Check total                                                 "diproses"

3. Klik "Update Status"
   btnupdatestatus clicked     UPDATE tblservice                Reload page
                               SET status_servis='selesai'

4. Konfirmasi & Input Bayar
   btnbayar clicked            UPDATE tblservice                Show success
                               SET status_servis='bayar',
                                   bayar=..., kembali=...

5. Coba Edit Lagi
   Click "Edit" button         Guard check:                     Auto redirect
                               status_servis = 'bayar'          to *-rst.php
                               → READONLY MODE
```

---

## IMPLEMENTASI DI SEMUA HALAMAN INPUT

### Checklist Guard Implementation

| No | File | Guard Status | Action Required |
|----|------|--------------|-----------------|
| 1 | `servis-input-reguler.php` | ❌ **MISSING** | **ADD GUARD** |
| 2 | `servis-input-reguler-rst.php` | ✅ N/A (sudah readonly) | None |
| 3 | `servis-input-reguler-jemput.php` | ✅ Implemented (baris 73-86) | None |
| 4 | `servis-input-reguler-jemput-rst.php` | ✅ N/A (sudah readonly) | None |
| 5 | `servis-garansi.php` | ✅ Implemented (baris 48-61) | None |
| 6 | `servis-garansi-rst.php` | ✅ N/A (sudah readonly) | None |

### Template Guard Code (Copy-Paste Ready)

```php
<?php
session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

// Get no_service from URL
$no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : '';

// ============================================================
// GUARD: Redirect to RST page if service already finished/paid
// ============================================================
if (!empty($no_service)) {
    // Sanitize input untuk prevent SQL injection
    $__ns = mysqli_real_escape_string($koneksi, $no_service);

    // Query database untuk check status REAL-TIME
    $__q = mysqli_query($koneksi, "SELECT status_servis FROM tblservice
                                    WHERE no_service='".$__ns."' LIMIT 1");

    if ($__q && ($__r = mysqli_fetch_assoc($__q))) {
        $__st = strtolower($__r['status_servis'] ?? '');

        // Check if status is FINAL (selesai atau bayar)
        if ($__st === 'selesai' || $__st === 'bayar') {
            // Determine RST page berdasarkan tipe servis
            $__redir = 'servis-input-reguler-rst.php?snoserv=' . urlencode($no_service);

            // Preserve tab parameter untuk UX continuity
            if (isset($_GET['tab']) && $_GET['tab'] !== '') {
                $__redir .= '&tab=' . urlencode($_GET['tab']);
            }

            // Force redirect dengan HTTP 302
            header('Location: ' . $__redir);
            exit;
        }
    }
}
// ============================================================
// END GUARD
// ============================================================

// Rest of your code continues here...
?>
```

### Modifikasi untuk Tipe Servis Berbeda

**Untuk Servis Reguler:**
```php
$__redir = 'servis-input-reguler-rst.php?snoserv=' . urlencode($no_service);
```

**Untuk Servis Jemput:**
```php
$__redir = 'servis-input-reguler-jemput-rst.php?snoserv=' . urlencode($no_service);
```

**Untuk Servis Garansi:**
```php
$__redir = 'servis-garansi-rst.php?snoserv=' . urlencode($no_service);
```

---

## REKOMENDASI PERBAIKAN

### 1. Security Hardening

#### A. SQL Injection Prevention

**Current Vulnerability:**
```php
// File: servis-reguler-byr.php, baris 143-148
$status_servis_baru = $_POST['cbostatus'];  // ❌ NO SANITIZATION
mysqli_query($koneksi,"UPDATE tblservice SET status_servis='$status_servis_baru'...");
```

**Recommended Fix:**
```php
// Option 1: Escape dengan mysqli_real_escape_string
$status_servis_baru = mysqli_real_escape_string($koneksi, $_POST['cbostatus']);

// Option 2: BETTER - Use prepared statements
$stmt = mysqli_prepare($koneksi, "UPDATE tblservice SET status_servis=? WHERE no_service=?");
mysqli_stmt_bind_param($stmt, "ss", $_POST['cbostatus'], $_POST['txtnosrv']);
mysqli_stmt_execute($stmt);

// Option 3: BEST - Validate against enum values
$allowed_status = ['datang', 'diproses', 'selesai', 'bayar', 'cancel'];
$status_input = $_POST['cbostatus'];

if (!in_array($status_input, $allowed_status)) {
    die("Invalid status value");
}

$stmt = mysqli_prepare($koneksi, "UPDATE tblservice SET status_servis=? WHERE no_service=?");
mysqli_stmt_bind_param($stmt, "ss", $status_input, $_POST['txtnosrv']);
mysqli_stmt_execute($stmt);
```

#### B. Permission-Based Access Control

**Current Issue:**
- Guard hanya check status, tidak check user permission
- User dengan permission `service_update` bisa bypass readonly check

**Recommended Implementation:**
```php
// Di awal guard check
include_once "../lib/rbac.php";

// Check if user has permission to edit finished service
if ($__st === 'selesai' || $__st === 'bayar') {
    // Check if user has override permission (e.g., admin/manager)
    if (!rbac_check('service_edit_finished')) {
        // No permission → force redirect to readonly
        header('Location: ' . $__redir);
        exit;
    } else {
        // Has permission → show warning message
        echo "<script>
            alert('PERINGATAN: Anda sedang mengedit service yang sudah SELESAI/BAYAR!\\n" .
                  "Pastikan ada alasan valid untuk perubahan ini.');
        </script>";
    }
}
```

**Permission Matrix:**

| User Role | Permission | Edit Finished Service | Edit Paid Service |
|-----------|------------|----------------------|-------------------|
| Admin | `service_edit_finished` | ✅ Yes (with warning) | ✅ Yes (with warning) |
| Manager | `service_edit_finished` | ✅ Yes (with warning) | ❌ No |
| CS | `service_create` | ❌ No | ❌ No |
| Mekanik | `service_update` | ❌ No | ❌ No |

### 2. Audit Trail / Change Log

**Purpose:** Track siapa yang mengubah status servis dan kapan

**Schema:**
```sql
CREATE TABLE `status_change_log` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_service` VARCHAR(50) NOT NULL,
  `status_lama` ENUM('datang','diproses','selesai','bayar','cancel'),
  `status_baru` ENUM('datang','diproses','selesai','bayar','cancel'),
  `changed_by` VARCHAR(50) NOT NULL COMMENT 'User ID atau nama user',
  `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `reason` TEXT COMMENT 'Alasan perubahan (opsional)',
  `ip_address` VARCHAR(50) COMMENT 'IP address user',

  KEY `idx_no_service` (`no_service`),
  KEY `idx_changed_at` (`changed_at`),
  FOREIGN KEY (`no_service`) REFERENCES `tblservice`(`no_service`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Implementation:**
```php
// Insert log setelah update status
function logStatusChange($koneksi, $no_service, $status_lama, $status_baru, $changed_by, $reason = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO status_change_log (no_service, status_lama, status_baru, changed_by, reason, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)");

    mysqli_stmt_bind_param($stmt, "ssssss",
        $no_service, $status_lama, $status_baru, $changed_by, $reason, $ip_address);

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Usage di handler update status
if(isset($_POST['btnupdatestatus'])) {
    // Get old status
    $result = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_service'");
    $old_data = mysqli_fetch_assoc($result);
    $status_lama = $old_data['status_servis'];

    // Update status
    $status_baru = $_POST['cbostatus'];
    mysqli_query($koneksi, "UPDATE tblservice SET status_servis='$status_baru' WHERE no_service='$no_service'");

    // Log change
    logStatusChange($koneksi, $no_service, $status_lama, $status_baru, $_nama, $_POST['reason'] ?? '');
}
```

### 3. Validation Workflow

**Purpose:** Prevent illogical status transitions

**Implementation:**
```php
// Define allowed transitions
$allowed_transitions = [
    'datang' => ['diproses', 'cancel'],
    'diproses' => ['selesai', 'cancel'],
    'selesai' => ['bayar', 'diproses'],  // diproses = revisi
    'bayar' => [],  // FINAL - no transitions allowed
    'cancel' => []  // FINAL - no transitions allowed
];

// Validation function
function validateStatusTransition($status_lama, $status_baru) {
    global $allowed_transitions;

    if ($status_lama === $status_baru) {
        return ['valid' => false, 'message' => 'Status tidak berubah'];
    }

    if (!isset($allowed_transitions[$status_lama])) {
        return ['valid' => false, 'message' => 'Status lama tidak valid'];
    }

    if (!in_array($status_baru, $allowed_transitions[$status_lama])) {
        return ['valid' => false, 'message' => "Tidak bisa mengubah status dari '$status_lama' ke '$status_baru'"];
    }

    return ['valid' => true];
}

// Usage
if(isset($_POST['btnupdatestatus'])) {
    $result = mysqli_query($koneksi, "SELECT status_servis FROM tblservice WHERE no_service='$no_service'");
    $old_data = mysqli_fetch_assoc($result);
    $status_lama = $old_data['status_servis'];
    $status_baru = $_POST['cbostatus'];

    // Validate transition
    $validation = validateStatusTransition($status_lama, $status_baru);

    if (!$validation['valid']) {
        echo "<script>alert('ERROR: " . $validation['message'] . "'); window.history.back();</script>";
        exit;
    }

    // Proceed with update...
}
```

### 4. UI Enhancement - Status Badge

**Purpose:** Visual indicator yang lebih jelas untuk setiap status

**Implementation di `servis-reguler.php`:**
```php
<?php
// Define status badge config
$status_config = [
    'datang' => [
        'label' => 'Datang',
        'class' => 'label-warning',
        'icon' => 'fa-clock-o'
    ],
    'diproses' => [
        'label' => 'Diproses',
        'class' => 'label-info',
        'icon' => 'fa-cogs'
    ],
    'selesai' => [
        'label' => 'Selesai',
        'class' => 'label-success',
        'icon' => 'fa-check'
    ],
    'bayar' => [
        'label' => 'Lunas',
        'class' => 'label-primary',
        'icon' => 'fa-money'
    ],
    'cancel' => [
        'label' => 'Batal',
        'class' => 'label-danger',
        'icon' => 'fa-times'
    ]
];

$status_servis = $tampil['status_servis'];
$config = $status_config[$status_servis] ?? $status_config['datang'];
?>

<span class="label <?php echo $config['class']; ?> arrowed-in arrowed-in-right">
    <i class="ace-icon fa <?php echo $config['icon']; ?>"></i>
    <?php echo $config['label']; ?>
</span>
```

---

## CHECKLIST IMPLEMENTASI

### Phase 1: Security Hardening (HIGH PRIORITY)

- [ ] **1.1 Add Guard di `servis-input-reguler.php`**
  - File: `_admincab/servis-input-reguler.php`
  - Tambah guard code setelah baris 50
  - Test: Coba akses direct URL dengan status = 'bayar'
  - Expected: Auto redirect ke `servis-input-reguler-rst.php`

- [ ] **1.2 Fix SQL Injection di `servis-reguler-byr.php`**
  - File: `_admincab/servis-reguler-byr.php`, baris 143-148
  - Ganti dengan prepared statement atau escape input
  - Test: Submit dengan payload `'; DROP TABLE tblservice; --`
  - Expected: Query di-escape, tidak eksekusi DROP

- [ ] **1.3 Validate Status Enum Values**
  - File: `servis-reguler-byr.php`
  - Tambah validation sebelum update
  - Test: Submit dengan status value = 'hacked'
  - Expected: Rejected dengan error message

### Phase 2: Audit Trail (MEDIUM PRIORITY)

- [ ] **2.1 Create `status_change_log` Table**
  - File: Buat migration SQL baru
  - Run migration di database
  - Test: `SHOW TABLES LIKE 'status_change_log'`

- [ ] **2.2 Implement Logging Function**
  - File: `_admincab/lib_status_logging.php` (new file)
  - Create function `logStatusChange()`
  - Test: Update status, check log table

- [ ] **2.3 Integrate Logging ke Handlers**
  - File: `servis-reguler-byr.php`
  - Call `logStatusChange()` setelah update
  - Test: Update status multiple kali, verify log entries

### Phase 3: Workflow Validation (MEDIUM PRIORITY)

- [ ] **3.1 Define Transition Rules**
  - File: `_admincab/config_status_workflow.php` (new file)
  - Define `$allowed_transitions` array
  - Test: Unit test untuk semua kombinasi

- [ ] **3.2 Implement Validation Function**
  - File: `_admincab/lib_status_workflow.php` (new file)
  - Create function `validateStatusTransition()`
  - Test: Try invalid transition (bayar → datang)

- [ ] **3.3 Integrate Validation ke Handler**
  - File: `servis-reguler-byr.php`
  - Call validation sebelum update
  - Test: Try all invalid transitions

### Phase 4: Permission-Based Access (LOW PRIORITY)

- [ ] **4.1 Define Permission Matrix**
  - File: `config/rbac.php`
  - Add permission `service_edit_finished`
  - Assign ke role Admin/Manager only

- [ ] **4.2 Update Guard dengan Permission Check**
  - File: `servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`
  - Add `rbac_check('service_edit_finished')` di guard
  - Test: Login sebagai CS, coba edit finished service

- [ ] **4.3 Add Warning Message untuk Admin**
  - File: Same files as 4.2
  - Show alert jika edit finished service dengan permission
  - Test: Login sebagai Admin, edit finished service

### Phase 5: UI Enhancement (LOW PRIORITY)

- [ ] **5.1 Update Status Badge**
  - File: `servis-reguler.php`
  - Replace text status dengan colored badge
  - Test: Visual check di browser

- [ ] **5.2 Add Confirmation Dialog**
  - File: `servis-reguler-byr.php`
  - Add JS confirm sebelum update status ke 'bayar' atau 'cancel'
  - Test: Click update status, dialog muncul

- [ ] **5.3 Add Status History Widget**
  - File: `servis-input-reguler-rst.php`, dsb
  - Display status change log di readonly page
  - Test: Check status history display

---

## TESTING CHECKLIST

### Test Case 1: Guard Enforcement

```
PREREQUISITES:
- Buat 1 service baru dengan status = 'datang'
- Kode service: SRV-20251227-TEST

TEST STEPS:
1. Update status ke 'bayar' via payment page
2. Klik "Edit" di list page
3. Coba akses direct URL:
   http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php?snoserv=SRV-20251227-TEST

EXPECTED RESULT:
- Step 2: Link mengarah ke *-rst.php
- Step 3: Auto redirect ke *-rst.php
- Tidak bisa edit form
```

### Test Case 2: Status Transition Validation

```
TEST STEPS:
1. Buat service baru (status = 'datang')
2. Update status ke 'bayar' (skip diproses & selesai)

EXPECTED RESULT:
- Rejected dengan error: "Tidak bisa mengubah status dari 'datang' ke 'bayar'"
```

### Test Case 3: SQL Injection Prevention

```
TEST STEPS:
1. Buka payment page
2. Inspect element, ubah dropdown value menjadi: '; DROP TABLE tblservice; --
3. Submit form

EXPECTED RESULT:
- Query di-escape
- Table tblservice masih ada
- Status tidak berubah
```

### Test Case 4: Permission-Based Access

```
TEST STEPS:
1. Login sebagai user dengan role 'CS'
2. Buka service dengan status = 'bayar'
3. Coba akses edit page

EXPECTED RESULT:
- Auto redirect ke readonly page
- Tidak bisa edit
```

---

## DATABASE MIGRATION SCRIPTS

### Migration 1: Add Guard to Reguler Page

**File:** `_admincab/migrations/add_guard_servis_reguler.sql`

```sql
-- Migration not needed (PHP code change only)
-- See checklist Phase 1.1
```

### Migration 2: Create Status Change Log Table

**File:** `_admincab/migrations/create_status_change_log_table.sql`

```sql
-- ============================================================
-- Migration: Create status_change_log table
-- Date: 2025-12-27
-- Purpose: Audit trail untuk track status changes
-- ============================================================

CREATE TABLE IF NOT EXISTS `status_change_log` (
  `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
  `no_service` VARCHAR(50) NOT NULL,
  `status_lama` ENUM('datang','diproses','selesai','bayar','cancel') NOT NULL,
  `status_baru` ENUM('datang','diproses','selesai','bayar','cancel') NOT NULL,
  `changed_by` VARCHAR(50) NOT NULL COMMENT 'User ID atau nama user',
  `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `reason` TEXT COMMENT 'Alasan perubahan (opsional)',
  `ip_address` VARCHAR(50) COMMENT 'IP address user',

  KEY `idx_no_service` (`no_service`),
  KEY `idx_changed_at` (`changed_at`),
  KEY `idx_changed_by` (`changed_by`),
  CONSTRAINT `fk_status_log_service`
    FOREIGN KEY (`no_service`)
    REFERENCES `tblservice`(`no_service`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Audit trail untuk track perubahan status servis';

-- Index untuk query performance
CREATE INDEX `idx_status_transition` ON `status_change_log` (`status_lama`, `status_baru`);

-- Verify table creation
SELECT
    TABLE_NAME,
    TABLE_ROWS,
    CREATE_TIME,
    UPDATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'status_change_log';

-- Test insert
-- INSERT INTO status_change_log (no_service, status_lama, status_baru, changed_by)
-- VALUES ('SRV-TEST', 'datang', 'diproses', 'TEST_USER');
```

**Run Migration:**
```bash
# Via CLI
mysql -u root -p fitmotor_dbbengkel < _admincab/migrations/create_status_change_log_table.sql

# Via phpMyAdmin
# Copy-paste SQL dan Execute
```

### Migration 3: Add Permission for Edit Finished Service

**File:** `_admincab/migrations/add_permission_edit_finished.sql`

```sql
-- ============================================================
-- Migration: Add permission for editing finished service
-- Date: 2025-12-27
-- Purpose: Allow admin/manager to edit finished service with warning
-- ============================================================

-- Check if permission table exists
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tb_permissions';

-- Insert new permission (adjust table name if different)
INSERT INTO tb_permissions (permission_code, permission_name, description, created_at)
VALUES (
    'service_edit_finished',
    'Edit Finished Service',
    'Allow user to edit service yang sudah selesai/bayar (with warning)',
    NOW()
) ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    description = VALUES(description);

-- Assign permission to Admin role
INSERT INTO tb_role_permissions (role_id, permission_code, created_at)
SELECT
    r.id AS role_id,
    'service_edit_finished' AS permission_code,
    NOW() AS created_at
FROM tb_roles r
WHERE r.role_name = 'Admin'
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Assign permission to Manager role
INSERT INTO tb_role_permissions (role_id, permission_code, created_at)
SELECT
    r.id AS role_id,
    'service_edit_finished' AS permission_code,
    NOW() AS created_at
FROM tb_roles r
WHERE r.role_name = 'Manager'
ON DUPLICATE KEY UPDATE
    updated_at = NOW();

-- Verify permissions
SELECT
    r.role_name,
    rp.permission_code,
    p.permission_name
FROM tb_roles r
JOIN tb_role_permissions rp ON r.id = rp.role_id
JOIN tb_permissions p ON rp.permission_code = p.permission_code
WHERE p.permission_code = 'service_edit_finished';
```

---

## CONTACT & SUPPORT

**Developer:** Web Bengkel Team
**Documentation:** ANALISA_SISTEM_STATUS_PENGERJAAN_SERVIS.md
**Last Updated:** 27 Desember 2025

**Quick Reference Files:**
- Database Schema: `fitmotor_dbbengkel.sql`
- Guard Template: See section "Template Guard Code"
- Status Config: See section "Flow Status Pengerjaan"

**Related Documents:**
- `ANALISA_INPUT_SERVIS_REGULER.md` - Detail input servis
- `DOKUMENTASI_ALUR_PROCUREMENT.md` - Alur procurement
- `DATABASE_STRUCTURE_ANALYSIS.md` - Struktur database lengkap

---

**END OF DOCUMENT**
