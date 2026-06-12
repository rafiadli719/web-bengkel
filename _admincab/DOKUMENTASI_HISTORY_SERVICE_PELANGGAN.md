# DOKUMENTASI: History Service Pelanggan & Auto Diskon Member

## Tanggal Implementasi: 2025-12-26

---

## 1. FILE YANG DIMODIFIKASI

### A. File SQL (Jalankan di phpMyAdmin)
```
_admincab/sql/migration_history_service_pelanggan.sql
```

### B. File PHP yang diupdate
| File | Perubahan |
|------|-----------|
| `_include_statistik_pelanggan.php` | Ditambah fungsi: `saveHistoryServicePelanggan()`, `saveHistoryMekanikServis()`, `checkAndLogNaikTier()`, `processAfterPayment()`, `getRiwayatServicePelanggan()`, `getRiwayatServiceKendaraan()`, `getDiskonPersenByTier()` |
| `servis-input-reguler.php` | Handler bayar memanggil `processAfterPayment()` |
| `servis-input-reguler-rst.php` | Handler bayar memanggil `processAfterPayment()` |
| `servis-input-reguler-jemput.php` | Handler bayar memanggil `processAfterPayment()` |
| `servis-input-reguler-jemput-rst.php` | Handler bayar memanggil `processAfterPayment()` |
| `servis-garansi.php` | Handler bayar memanggil `processAfterPayment()` |
| `_template/_servis_actions_tab.php` | Ditambah JavaScript auto-calculate diskon member |

### C. File Baru
| File | Deskripsi |
|------|-----------|
| `sql/migration_history_service_pelanggan.sql` | Script SQL untuk membuat tabel baru |
| `_template/_servis_payment_calculator.js.php` | JavaScript include untuk kalkulasi pembayaran |

---

## 2. TABEL DATABASE BARU

### A. `tb_history_service_pelanggan`
Menyimpan history lengkap setiap service yang sudah dibayar:
- Data pelanggan & kendaraan
- Data pembayaran (subtotal, diskon, PPN, total)
- Keluhan, temuan, workorder (dalam format JSON)
- Barang & jasa yang digunakan (dalam format JSON)
- Data mekanik (kepala mekanik, admin, mekanik 1-4)
- Status member sebelum & sesudah transaksi
- Flag naik tier

### B. `tb_history_mekanik_servis`
Menyimpan history detail mekanik per service:
- Tipe role (kepala_mekanik/mekanik/admin)
- Nama karyawan
- Persentase kerja
- Pendapatan dari jasa

### C. `tb_log_naik_tier_member`
Menyimpan log ketika pelanggan naik tier:
- Tier lama & baru
- Total nominal saat naik
- Diskon lama & baru
- Status notifikasi WhatsApp

### D. Views
- `v_history_service_pelanggan_summary` - Summary history per pelanggan
- `v_history_mekanik_summary` - Summary performa mekanik
- `v_riwayat_kendaraan_lengkap` - Riwayat lengkap per kendaraan

---

## 3. CARA INSTALL

### Langkah 1: Jalankan SQL Migration
1. Buka phpMyAdmin
2. Pilih database `fitmotor_dbbengkel`
3. Import file: `_admincab/sql/migration_history_service_pelanggan.sql`

### Langkah 2: Verifikasi
Cek tabel berikut sudah ada:
- `tb_history_service_pelanggan`
- `tb_history_mekanik_servis`
- `tb_log_naik_tier_member`

### Langkah 3: Test
1. Buka halaman servis reguler/jemput/garansi
2. Lakukan pembayaran
3. Cek tabel `tb_history_service_pelanggan` apakah data tersimpan

---

## 4. ALUR PROSES SAAT BAYAR

```
[User klik Bayar]
        ↓
[Update tblservice dengan data pembayaran]
        ↓
[Panggil processAfterPayment()]
        ↓
    ┌───────────────────────────────────────┐
    │ 1. updateStatistikPelangganAfterPayment()    │
    │    - Update total_transaksi                  │
    │    - Update total_nominal                    │
    │    - Update jumlah_kunjungan                 │
    │    - Update status_member (Bronze/Silver/Gold/Platinum) │
    ├───────────────────────────────────────┤
    │ 2. saveHistoryServicePelanggan()             │
    │    - Simpan ke tb_history_service_pelanggan  │
    │    - Include: keluhan, temuan, workorder,    │
    │      barang, jasa, mekanik (dalam JSON)      │
    ├───────────────────────────────────────┤
    │ 3. saveHistoryMekanikServis()                │
    │    - Simpan ke tb_history_mekanik_servis     │
    │    - Detail per mekanik dengan pendapatan    │
    ├───────────────────────────────────────┤
    │ 4. checkAndLogNaikTier()                     │
    │    - Cek apakah pelanggan naik tier          │
    │    - Jika naik, insert ke tb_log_naik_tier_member │
    │    - Update flag naik_tier di history        │
    └───────────────────────────────────────┘
        ↓
[Proses lanjutan: Update stok, antrian, WhatsApp]
```

---

## 5. AUTO DISKON MEMBER

### Cara Kerja
1. Saat halaman load, JavaScript membaca nilai `txtdiskon_member`
2. Diskon dihitung otomatis: `Subtotal × (diskon_member_persen + diskon_tambahan_persen) / 100`
3. Total bayar = Subtotal - Total Diskon + PPN
4. Kembalian = Jumlah Bayar - Total Bayar

### Field yang terpengaruh
- `txtdiskon_member` - Persentase diskon member (readonly)
- `txtpotfaktur_persen` - Persentase diskon tambahan (editable)
- `txtpotfaktur_nom` - Total diskon dalam Rupiah (auto-calculated)
- `txtpajak_persen` - Persentase PPN (editable)
- `txtpajak_nom` - Nominal PPN (auto-calculated)
- `txtnet` - Total Bayar (auto-calculated)
- `txtkembalian` - Kembalian (auto-calculated)

---

## 6. FUNGSI PHP BARU

### `processAfterPayment($koneksi, $no_pelanggan, $no_service, $tipe_service)`
Fungsi utama yang memanggil semua update setelah pembayaran.

**Return:**
```php
[
    'statistik' => true/false,
    'history' => true/false,
    'naik_tier' => true/false,
    'tier_info' => [
        'tier_lama' => 'Silver',
        'tier_baru' => 'Gold',
        'diskon_lama' => 10,
        'diskon_baru' => 15
    ]
]
```

### `saveHistoryServicePelanggan($koneksi, $no_service, $tipe_service)`
Menyimpan history lengkap service ke tabel.

### `getRiwayatServicePelanggan($koneksi, $no_pelanggan, $limit)`
Mengambil riwayat service pelanggan.

### `getRiwayatServiceKendaraan($koneksi, $no_polisi, $limit)`
Mengambil riwayat service per kendaraan.

### `getDiskonPersenByTier($koneksi, $status_member)`
Mengambil persentase diskon berdasarkan tier member.

---

## 7. TROUBLESHOOTING

### History tidak tersimpan
1. Cek tabel `tb_history_service_pelanggan` sudah ada
2. Cek error log di `error_log()`
3. Pastikan fungsi `processAfterPayment()` terpanggil

### Diskon tidak otomatis terapply
1. Pastikan nilai `txtdiskon_member` terisi
2. Cek console browser untuk JavaScript error
3. Pastikan jQuery sudah loaded

### Status member tidak update
1. Cek tabel `statistik_pelanggan` untuk pelanggan tersebut
2. Cek tabel `master_kategori_member` untuk konfigurasi tier

---

## 8. CATATAN PENTING

- Fungsi baru backward-compatible dengan fallback ke fungsi lama
- Data JSON di history bisa di-decode untuk reporting
- Index sudah dibuat untuk performa query
- Unique constraint pada `no_service` mencegah duplikat
