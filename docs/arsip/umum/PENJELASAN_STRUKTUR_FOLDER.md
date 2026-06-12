# PENJELASAN STRUKTUR FOLDER - SISTEM STATISTIK PELANGGAN

## 📁 Struktur Folder yang Benar

### Root Folder Aplikasi:
```
c:\xampp\htdocs\web-bengkel\
```

### Folder Aplikasi Admin Cabang:
```
c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\
```

---

## 🗂️ Lokasi File-File Sistem Statistik

### 1. File PHP (di folder _admincab):

```
c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\
├── statistik_pelanggan_dashboard.php      ← Dashboard utama
├── statistik_pelanggan_send_wa.php        ← Handler kirim WhatsApp
├── class_whatsapp_automation.php          ← Class WhatsApp
├── config_whatsapp.php                    ← Konfigurasi WhatsApp
├── webhook_whatsapp.php                   ← Webhook handler
├── _include_statistik_pelanggan.php       ← Helper functions
└── _template/
    ├── _statistik_semua_pelanggan.php     ← Template semua pelanggan
    ├── _statistik_followup_pelanggan.php  ← Template follow-up
    └── _statistik_top_pelanggan.php       ← Template top pelanggan
```

### 2. File SQL & Dokumentasi (di root):

```
c:\xampp\htdocs\web-bengkel\
├── database_statistik_pelanggan_otomatis.sql
├── fix_foreign_key_statistik_pelanggan.sql
├── update_sp_refresh_statistik_pelanggan_safe.sql
├── PENJELASAN_SEDERHANA_STATISTIK_PELANGGAN.md
├── IMPLEMENTASI_STATISTIK_DI_SERVIS_INPUT.md
├── PROSES_INPUT_SERVIS_KE_STATISTIK.md
├── DOKUMENTASI_WEBHOOK_WHATSAPP.md
├── DOKUMENTASI_SISTEM_STATISTIK_PELANGGAN.md
└── QUICK_START_STATISTIK_PELANGGAN.txt
```

---

## 🌐 URL Akses dari Browser

### ❌ SALAH:
```
http://localhost/web-bengkel/_admincab/statistik_pelanggan_dashboard.php
```
**Kenapa salah?** Karena folder `_admincab` ada di dalam `aplikasi/aplikasi/`, bukan langsung di root.

### ✅ BENAR:
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php
```

---

## 🔗 Path dalam Kode PHP

### Relative Path (dari dalam folder _admincab):

Jika Anda ada di file yang berada di folder `_admincab`, gunakan relative path:

```php
// File: statistik_pelanggan_dashboard.php
// Lokasi: aplikasi/aplikasi/_admincab/

// Include file di folder yang sama
include "config_whatsapp.php";                    // ✅ BENAR
include "class_whatsapp_automation.php";          // ✅ BENAR
include "_include_statistik_pelanggan.php";       // ✅ BENAR

// Include template
include "_template/_statistik_semua_pelanggan.php";  // ✅ BENAR

// Include koneksi (naik 2 level)
include "../../config/koneksi.php";               // ✅ BENAR

// Link ke halaman lain di folder yang sama
<a href="statistik_pelanggan_send_wa.php">        // ✅ BENAR
<a href="statistik_pelanggan_dashboard.php">      // ✅ BENAR
```

### Absolute Path (dari browser):

Jika Anda membuat link yang akan diklik di browser:

```php
// ❌ SALAH
<a href="http://localhost/web-bengkel/_admincab/statistik_pelanggan_dashboard.php">

// ✅ BENAR
<a href="http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php">
```

### Relative Path dari Halaman Servis Input:

Jika Anda ada di halaman servis input (juga di folder `_admincab`):

```php
// File: servis-input-reguler.php
// Lokasi: aplikasi/aplikasi/_admincab/

// Link ke dashboard (sama-sama di folder _admincab)
<a href="statistik_pelanggan_dashboard.php">      // ✅ BENAR
<a href="statistik_pelanggan_send_wa.php">        // ✅ BENAR

// Include helper
include "_include_statistik_pelanggan.php";       // ✅ BENAR
```

---

## 📝 Contoh Lengkap

### Contoh 1: Link di Halaman Servis Input

```php
// File: servis-input-reguler.php
// Lokasi: aplikasi/aplikasi/_admincab/servis-input-reguler.php

<?php
// Include helper (relative path)
include "_include_statistik_pelanggan.php";

// Tampilkan status member
if(!empty($kode_pelanggan)) {
    echo displayStatistikPelangganInfo($koneksi, $kode_pelanggan);
}
?>

<!-- Link ke dashboard (relative path) -->
<a href="statistik_pelanggan_dashboard.php" target="_blank" class="btn btn-info">
    <i class="fa fa-bar-chart"></i> Lihat Statistik
</a>

<!-- Link kirim WhatsApp (relative path) -->
<a href="statistik_pelanggan_send_wa.php?no_service=<?php echo $no_service; ?>" 
   target="_blank" class="btn btn-success">
    <i class="fa fa-whatsapp"></i> Kirim WhatsApp
</a>
```

### Contoh 2: Akses dari Browser

```
Buka browser → ketik URL:

http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php
```

### Contoh 3: Include Koneksi Database

```php
// File: statistik_pelanggan_dashboard.php
// Lokasi: aplikasi/aplikasi/_admincab/

// Struktur folder:
// web-bengkel/
// ├── config/
// │   └── koneksi.php
// └── aplikasi/
//     └── aplikasi/
//         └── _admincab/
//             └── statistik_pelanggan_dashboard.php (kita di sini)

// Naik 2 level (../../) untuk ke folder config
include "../../config/koneksi.php";  // ✅ BENAR
```

---

## 🎯 Kesimpulan

### Path untuk URL Browser (Absolute):
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/[nama_file].php
```

### Path dalam Kode PHP (Relative):
```php
// Jika file di folder yang sama (_admincab)
include "nama_file.php";
<a href="nama_file.php">

// Jika file di folder template
include "_template/nama_file.php";

// Jika file di folder config (naik 2 level)
include "../../config/koneksi.php";
```

---

## 🔧 Troubleshooting

### Problem: File not found / 404 Error

**Gejala:**
```
Not Found
The requested URL /web-bengkel/_admincab/statistik_pelanggan_dashboard.php was not found
```

**Penyebab:**
Path URL salah, kurang `aplikasi/aplikasi/`

**Solusi:**
```
❌ http://localhost/web-bengkel/_admincab/statistik_pelanggan_dashboard.php
✅ http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php
```

---

### Problem: Include file not found

**Gejala:**
```
Warning: include(config_whatsapp.php): Failed to open stream: No such file or directory
```

**Penyebab:**
File tidak ada di folder yang sama atau path salah

**Solusi:**
```php
// Cek lokasi file saat ini
echo __DIR__;  // Tampilkan folder saat ini

// Cek file exist
if(file_exists('config_whatsapp.php')) {
    echo "File ditemukan";
} else {
    echo "File TIDAK ditemukan";
}

// Gunakan path yang benar
include "config_whatsapp.php";  // Jika di folder yang sama
include "../config_whatsapp.php";  // Jika di folder parent
```

---

## 📚 Referensi Cepat

| File | Lokasi Fisik | URL Browser |
|------|--------------|-------------|
| Dashboard | `aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php` | `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_dashboard.php` |
| WhatsApp Send | `aplikasi/aplikasi/_admincab/statistik_pelanggan_send_wa.php` | `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/statistik_pelanggan_send_wa.php` |
| Webhook | `aplikasi/aplikasi/_admincab/webhook_whatsapp.php` | `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/webhook_whatsapp.php` |
| Servis Input | `aplikasi/aplikasi/_admincab/servis-input-reguler.php` | `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php` |

---

**Ingat:** 
- **URL Browser** = Path lengkap dari root web server
- **Include PHP** = Path relative dari file saat ini

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
