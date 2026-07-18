# PLANNING: Restrukturisasi Tabel Karyawan & User
## Bengkel 2.0 | Revisi: 2026-06-27

---

> **KEPUTUSAN PENTING (27 Jun 2026):**
> `tblmekanik` dan `tbuser_karyawan` **TIDAK DISENTUH**.
> - `tbuser_karyawan` = tabel target sync dari FITMOTOR GABUNG.MDB via pipeline Access sync
> - `tblmekanik` = master mekanik manual web, sudah berjalan dengan baik
> Mengubah kedua tabel ini berisiko merusak pipeline sync yang aktif.

---

## 1. KONDISI SAAT INI

### Tabel yang TETAP (tidak diubah)

| Tabel | Status | Alasan |
|---|---|---|
| `tblmekanik` | **TETAP** | Master mekanik web, dipakai dropdown servis |
| `tbuser_karyawan` | **TETAP** | Disync otomatis dari Access via `stg_access_gabung_mekanik` |
| `tblservice` | **TETAP** | Disync dari Access, menyimpan nama mekanik sebagai teks |
| `tb_master_posisi` | **TETAP** | Sudah bagus, berisi permissions JSON |

### Masalah yang Masih Perlu Diselesaikan

1. **Karyawan non-mekanik tidak punya tabel yang baik** — Admin, CS, Kasir, Pengadaan, CRM, dll masih pakai `tbuser` (102 baris, 98% dummy placeholder) dan tidak ada data HR yang nyata
2. **Login system memakai `tbuser` untuk semua orang** — padahal `tbuser` adalah campuran superadmin + dummy karyawan
3. **`tbl_master_kepala_mekanik`** — 6 baris, 4 real, 2 placeholder. Duplikat data dari tblmekanik
4. **`tb_user_mekanik_mapping`** — kosong total (0 baris), tidak pernah dipakai

---

## 2. VISI STRUKTUR BARU (SCOPE TERBATAS)

### Prinsip

```
tblmekanik       → tetap (dropdown mekanik servis)
tbuser_karyawan  → tetap (sync Access)
tbuser           → tetap HANYA untuk superadmin (1-2 akun owner)
karyawan BARU    → untuk staf non-mekanik: Admin, CS, Kasir, dll
karyawan_login   → login untuk staf non-mekanik
```

### Yang TIDAK Berubah

- Semua query `FROM tblmekanik` di form servis → tidak disentuh
- Semua query `FROM tbuser_karyawan` di sync & tab servis → tidak disentuh
- Pipeline sync Access → tidak disentuh

---

## 3. TABEL BARU

### 3.1 `master_departemen` (BARU)

```sql
CREATE TABLE master_departemen (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    kode      VARCHAR(20) NOT NULL UNIQUE,
    nama      VARCHAR(100) NOT NULL,
    is_active ENUM('active','inactive') DEFAULT 'active'
);
```

Data awal: WORKSHOP, FRONTOFFICE, PURCHASING, MANAGEMENT, FINANCE, MARKETING, HRD

---

### 3.2 `master_jabatan` (BARU)

```sql
CREATE TABLE master_jabatan (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    kode_jabatan VARCHAR(20) NOT NULL UNIQUE,
    nama_jabatan VARCHAR(100) NOT NULL,
    kode_role    VARCHAR(20) NOT NULL,
    urutan       INT DEFAULT 0,
    is_active    ENUM('active','inactive') DEFAULT 'active'
);
```

Data awal: ADM-1, CS-1, KSR-1, PGD-1, CRM-1, MNG-1, KEU-1, HRD-1

---

### 3.3 `karyawan` (BARU — hanya untuk non-mekanik)

```sql
CREATE TABLE karyawan (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    kode_karyawan  VARCHAR(20) NOT NULL UNIQUE,
    nik            VARCHAR(20),
    nama_lengkap   VARCHAR(100) NOT NULL,
    nama_panggilan VARCHAR(50),
    kode_posisi    VARCHAR(20) NOT NULL,
    kode_jabatan   VARCHAR(20),
    kode_cabang    VARCHAR(10),
    tanggal_masuk  DATE,
    tanggal_keluar DATE,
    is_active      ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_posisi) REFERENCES tb_master_posisi(kode_posisi)
);
```

**Yang masuk tabel ini:** Admin, CS, Kasir, Pengadaan, CRM, Manager, Keuangan, HRD

**Yang TIDAK masuk:** Mekanik & Kepala Mekanik → tetap di `tblmekanik`

---

### 3.4 `karyawan_login` (BARU — pengganti sebagian `tbuser`)

```sql
CREATE TABLE karyawan_login (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    kode_karyawan VARCHAR(20) NOT NULL UNIQUE,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    is_active     ENUM('active','inactive') DEFAULT 'active',
    last_login    TIMESTAMP NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_karyawan) REFERENCES karyawan(kode_karyawan)
);
```

---

## 4. TABEL YANG DEPRECATED

| Tabel | Nasib | Kapan |
|---|---|---|
| `tb_user_mekanik_mapping` | **Langsung hapus** (0 baris, aman) | Fase 1 |
| `tbl_master_kepala_mekanik` | Rename → `_bak_tbl_master_kepala_mekanik` | Setelah Fase 3 |
| `tbuser` | Rename → `_bak_tbuser`, sisakan baris superadmin | Fase 3 |

**TIDAK deprecated:**
- `tblmekanik` → tetap
- `tbuser_karyawan` → tetap
- `tbmekanik_level` → tetap

---

## 5. MIGRASI DATA

### Dari `tbuser` ke `karyawan_login`

Hanya akun real yang dipindah:
- Akun superadmin/owner → tetap di `tbuser`
- Akun Indri dan staf real lainnya → pindah ke `karyawan` + `karyawan_login`
- 100 akun dummy → **tidak dimigrasi, dihapus**

### Data Karyawan Non-Mekanik

Belum ada data real. Perlu input manual oleh HRD/owner per cabang setelah tabel `karyawan` dibuat.

---

## 6. DAMPAK KE KODE APLIKASI

### File yang DIUBAH

| File | Yang Berubah | Prioritas |
|---|---|---|
| `login.php` | Tambah jalur cek `karyawan_login` dulu, fallback `tbuser` untuk superadmin | P1 |
| `config/session_check.php` | Handle session dari `karyawan_login` | P1 |
| `config/auth_session.php` | Auth context untuk karyawan non-mekanik | P1 |
| `lib/rbac.php` | RBAC support untuk karyawan | P1 |
| `app/user.php`, `user_add.php`, `user_edit.php` | CRUD user → arahkan ke `karyawan` | P2 |
| `app/master_karyawan.php` | CRUD karyawan non-mekanik (buat halaman baru) | P2 |
| `app/input_kepala_mekanik_harian.php` | Query KM sudah difix sementara dari tblmekanik | P2 |
| `app/get_kepala_mekanik_harian.php` | Query KM dari tblmekanik | P2 |

### File yang TIDAK DIUBAH

- Semua `app/_template/_servis_*.php` → tetap query `tblmekanik`
- Semua `app/servis-*.php` → tetap
- `app/_include_access_sync.php` → tetap
- `app/lap_komisi_mekanik.php` → tetap query `tbuser_karyawan`
- `app/mekanik_*.php` → tetap
- `app/mekanik_management*.php` → tetap

---

## 7. TAHAPAN PENGERJAAN

### Fase 1 — Master Tables Baru (tanpa breaking change, ~1 hari)
- [ ] Buat `master_departemen` + isi data awal
- [ ] Buat `master_jabatan` + isi data awal
- [ ] ALTER `tb_master_posisi` tambah kolom `kode_departemen`, `bisa_login`, `dapat_insentif`
- [ ] Hapus `tb_user_mekanik_mapping` (0 baris, aman)

### Fase 2 — Tabel Karyawan Non-Mekanik (~2-3 hari)
- [ ] Buat tabel `karyawan` + `karyawan_login`
- [ ] Input data karyawan non-mekanik (HRD/owner per cabang)
- [ ] Buat UI `master_karyawan.php` untuk CRUD

### Fase 3 — Update Login System (~2-3 hari)
- [ ] Update `login.php` — cek `karyawan_login` dulu, fallback `tbuser` untuk superadmin
- [ ] Update `session_check.php`, `auth_session.php`, `rbac.php`
- [ ] Test semua role login
- [ ] Rename `tbuser` dummy ke `_bak_*`, sisakan superadmin

### Fase 4 — Cleanup (setelah 1 bulan berjalan)
- [ ] Rename `tbl_master_kepala_mekanik` ke `_bak_*`
- [ ] Monitor, lalu drop backup jika aman

---

## 8. PERTANYAAN KE OWNER SEBELUM MULAI

1. **Format `kode_karyawan` yang diinginkan?** (contoh: KRY-00001, ADM001, dll)
2. **Siapa yang input data karyawan non-mekanik?** (HRD pusat atau masing-masing cabang?)
3. **Apakah CS, Kasir, Pengadaan perlu login ke sistem?**

---

*Revisi v2.0 (2026-06-27): tblmekanik dan tbuser_karyawan tidak disentuh — terikat pipeline sync Access.*
