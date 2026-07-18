# UAT Checklist — Pindah Kepemilikan Kendaraan (Motor Dijual)

**Tanggal:** 2026-07-13  
**Modul:** `kendaraan_pindah_tangan.php`, `kendaraan_pindah_tangan_approve.php`, `detail_pelanggan.php`  
**Tujuan:** memastikan alur jual beli motor sesuai FSD dan mudah dipahami user operasional.

---

## 1. Persiapan Data Uji

Siapkan minimal 2 pelanggan dan 1 kendaraan:
- **Pelanggan lama**: `CUST_LAMA`
- **Pelanggan baru**: `CUST_BARU`
- **Motor**: nopol `TEST 1234 XX`

Pastikan:
- kendaraan sudah punya `id_kendaraan`
- ada row owner aktif di `kepemilikan_kendaraan` untuk `CUST_LAMA`
- ada histori servis minimal 2 transaksi untuk motor tersebut

Untuk skenario blocker, siapkan 1 transaksi `tblservice.status_servis='diproses'` untuk nopol yang sama.

---

## 2. Skenario UAT — Form Pengajuan CS/Admin

### UAT-01 — Cari kendaraan valid
**Langkah:**
1. Buka `kendaraan_pindah_tangan.php`
2. Input nopol yang ada
3. Klik `Periksa`

**Expected:**
- detail nopol tampil
- tipe/warna tampil
- owner aktif tampil
- status jelas: `SIAP` atau `DIBLOKIR`
- riwayat pemilik tampil jika ada

### UAT-02 — Nopol tidak ditemukan
**Langkah:**
1. Cari nopol palsu

**Expected:**
- muncul alert merah “nomor polisi tidak ditemukan”
- form pengajuan tidak aktif

### UAT-03 — Kendaraan diblokir karena servis aktif
**Langkah:**
1. Pastikan ada servis `datang/diproses/selesai`
2. Cari nopol tersebut

**Expected:**
- muncul badge/alert `DIBLOKIR`
- form pengajuan tidak muncul
- user paham kenapa tidak bisa lanjut

### UAT-04 — Pilih pelanggan baru via autocomplete
**Langkah:**
1. Cari motor status `SIAP`
2. Di field pemilik baru, ketik nama customer baru
3. Pilih dari list autocomplete

**Expected:**
- nama/label customer tampil di input
- ID customer baru tampil di box readonly
- user tidak perlu menebak kode pelanggan

### UAT-05 — Konfirmasi kode target salah
**Langkah:**
1. Isi form lengkap
2. Ketik ulang kode target dengan salah
3. Submit

**Expected:**
- request gagal
- pesan error jelas
- tidak ada row baru di `permintaan_pindah_kepemilikan_kendaraan`

### UAT-06 — Submit pengajuan sukses
**Langkah:**
1. Isi semua field benar
2. Submit

**Expected:**
- muncul pesan sukses
- row baru masuk ke `permintaan_pindah_kepemilikan_kendaraan`
- status = `diajukan`
- log tampil di tabel riwayat bawah

---

## 3. Skenario UAT — Approval Supervisor

### UAT-07 — Daftar pengajuan tampil
**Langkah:**
1. Buka `kendaraan_pindah_tangan_approve.php`

**Expected:**
- pengajuan status `diajukan` tampil
- supervisor bisa lihat:
  - nopol
  - unit
  - pengaju
  - pemilik lama
  - pemilik baru
  - WA/alamat
  - alasan jual beli

### UAT-08 — Konfirmasi target salah saat approve
**Langkah:**
1. Input kode target salah
2. Klik setujui

**Expected:**
- approve gagal
- status request tetap `diajukan`
- tidak ada perubahan owner aktif

### UAT-09 — Reject request
**Langkah:**
1. Isi catatan internal
2. Klik `Tolak`

**Expected:**
- status request jadi `ditolak`
- request hilang dari list pending
- catatan tersimpan

### UAT-10 — Approve & eksekusi sukses
**Langkah:**
1. Ketik kode target benar
2. Klik `Setujui & Eksekusi`

**Expected:**
- row owner lama di `kepemilikan_kendaraan` ditutup (`is_current=0`, `tanggal_akhir` terisi)
- row owner baru dibuat (`is_current=1`, `tanggal_mulai=hari ini`)
- `tblkendaraan.pemilik` berubah ke owner baru
- `statistik_kendaraan.nopelanggan_current` berubah ke owner baru
- request status jadi `dieksekusi`

---

## 4. Skenario UAT — Histori Customer-Facing

### UAT-11 — Customer lama tetap lihat histori masa lalu
**Langkah:**
1. Buka `detail_pelanggan.php?nopelanggan=CUST_LAMA`

**Expected:**
- histori servis sebelum tanggal jual tetap terlihat
- kendaraan yang pernah dimiliki tetap tampil di daftar kendaraan

### UAT-12 — Customer baru hanya lihat histori sejak tanggal beli
**Langkah:**
1. Buka `detail_pelanggan.php?nopelanggan=CUST_BARU`

**Expected:**
- histori servis lama sebelum tanggal beli **tidak tampil**
- hanya servis setelah `tanggal_mulai` kepemilikan baru yang tampil

### UAT-13 — Histori teknis internal tetap utuh
**Langkah:**
1. Buka modul teknis/riwayat kendaraan internal
2. Cari nopol motor yang sudah pindah tangan

**Expected:**
- semua histori servis kendaraan tetap utuh lintas owner

---

## 5. Temuan UX yang Harus Dicatat Saat Test

Tester diminta catat:
- apakah istilah “pemilik lama” dan “pemilik baru” cukup jelas?
- apakah user paham kenapa ada field ketik ulang kode target?
- apakah status `SIAP/DIBLOKIR` cukup mencolok?
- apakah autocomplete pelanggan baru cukup membantu?
- apakah supervisor merasa informasi owner lama vs baru sudah cukup untuk approve?

---

## 6. Exit Criteria

UAT dianggap lulus jika:
- semua skenario UAT-01 s/d UAT-13 sesuai expected
- tidak ada data bocor ke customer baru
- tidak ada pindah tangan yang lolos saat servis aktif
- user operasional dapat memahami form tanpa perlu penjelasan teknis tambahan

