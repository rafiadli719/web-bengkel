# Audit Formal — Modul Laporan Masalah

**Tanggal:** 2026-07-11  
**Scope:** `app/issue_add.php`, `app/ajax-get-jenis-masalah.php`, `app/ajax-issue-service-detail.php`, `app/ajax-autocomplete.php`, `app/customer_merge_approve.php`, `app/menu_config.php`, `lib/rbac.php`  
**Sudut pandang utama:** user operasional gaptek (CS, kasir, supervisor)

---

## Executive Summary

Modul sudah punya fondasi teknis cukup kuat: tiket terstruktur, auto-lookup, approval, log progress, dan hook eksekusi otomatis. Masalah utama ada di **UX, konsistensi permission, dan scope produk**. Sistem masih terasa dibuat untuk orang yang paham struktur internal aplikasi, bukan untuk user operasional yang hanya tahu “saya salah input apa”.

---

## Severity Matrix

### Critical

1. **Akses halaman belum diproteksi konsisten**  
   - `app/issue_add.php` hanya cek login, belum enforce permission fitur.  
   - Dampak: user yang tidak semestinya masih bisa buka URL langsung.

2. **Aksi merge pelanggan berisiko tinggi dan masih minim guardrail**  
   - `app/customer_merge_approve.php` langsung re-point transaksi ke pelanggan target.  
   - Dampak: salah klik / salah approve bisa mengubah histori pelanggan tanpa rollback otomatis yang aman.

### High

3. **Flow submit tiket terlalu abstrak untuk user gaptek**  
   - User dipaksa paham kategori dulu, baru jenis masalah.  
   - Dampak: bingung, salah pilih jalur, submit lambat.

4. **Lookup hasil cari kurang kaya konteks**  
   - Hasil servis hanya tampil `no_service` + `nopol`.  
   - Dampak: user mudah salah pilih transaksi yang mirip.

5. **Scope tiket dan merge pelanggan masih campur**  
   - Planning sudah arahkan merge ke tool admin terpisah, tapi mental model UI masih bercampur.  
   - Dampak: produk terasa tidak rapi dan membingungkan.

6. **Permission menu merge tidak sinkron dengan akses halaman**  
   - Menu pakai `issue_read`, halaman pakai admin-only.  
   - Dampak: user lihat menu, lalu mentok akses ditolak.

### Medium

7. **Label status terlalu sistem-oriented**  
   - `waiting_approval`, `resolved`, `closed` belum cukup ramah operasional.  
   - Dampak: user kurang paham kondisi tiket.

8. **Feedback submit belum cukup menenangkan user**  
   - Setelah submit, user belum dapat banner konfirmasi yang jelas.  
   - Dampak: user ragu laporan berhasil atau tidak.

9. **Error display aktif di halaman operasional**  
   - `display_errors=1` aktif di halaman utama dan approval merge.  
   - Dampak: warning teknis bisa muncul ke user.

10. **Update status tiket umum masih terlalu bebas**  
    - Alur non-auto masih seperti alat admin, belum dibimbing.  
    - Dampak: user bingung pilih status berikutnya.

### Low

11. **List tiket kurang informatif untuk monitoring cepat**  
    - Belum ada kolom “butuh aksi siapa” / “pelapor”.

12. **Deskripsi jenis masalah di picker masih minim**  
    - Hanya jumlah field, belum menjelaskan kapan opsi itu dipakai.

---

## Root Cause

1. Desain awal terlalu generic dan schema-driven.  
2. Belum dipisah tegas antara **tiket koreksi transaksi** vs **tool data cleanup admin**.  
3. Permission dan UX berkembang terpisah, belum dirapikan sebagai satu produk utuh.

---

## Rekomendasi Implementasi

### Gelombang 1 — Wajib
- Enforce permission di halaman tiket.
- Sinkronkan permission menu merge dan halaman merge.
- Sederhanakan flow submit: pilih masalah langsung, bukan kategori dulu.
- Perkaya hasil lookup servis/kendaraan.
- Tampilkan flash success yang jelas setelah tiket dibuat.
- Ubah copy status agar lebih mudah dipahami.

### Gelombang 2 — Sangat Disarankan
- Tambah guardrail merge: retype kode target / preview dampak / warning permanen.
- Pisahkan total merge pelanggan dari mental model “lapor masalah”.
- Tambah deskripsi operasional di setiap jenis masalah.

### Gelombang 3 — Nice to Have
- Tambah dashboard approval terpisah.
- Tambah filter “menunggu saya”.
- Tambah kolom pelapor dan update terakhir.

---

## Status Audit

Audit ini langsung ditindaklanjuti dengan patch tahap 1 pada sesi yang sama.
