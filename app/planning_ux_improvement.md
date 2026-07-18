# Planning Perbaikan UX & Bug — Web Bengkel FitMotor
**Tanggal**: 30 Juni 2026  
**Berdasarkan**: E2E Testing PR → PO → DO → Pembelian → Antar Cabang PUSH/PULL

---

## RINGKASAN EKSEKUTIF

Dari perspektif user awam (non-IT), alur pengadaan saat ini **sulit diikuti** karena:
1. Banyak form yang tidak saling mengisi data otomatis (user harus copy-paste nomor)
2. Terminologi teknis (PO, DO, PULL, PUSH) tanpa penjelasan
3. **Bug kritis**: form submit hang/freeze di beberapa modul

---

## BUGS KRITIS (Harus Diperbaiki Segera)

### BUG-01: Form Submit Hang/Freeze Browser
**Modul**: do_from_po.php, pembelian_add_rst.php, pengadaan_antarcab_push.php  
**Gejala**: Setelah klik tombol submit, browser freeze 30-45 detik  
**Root cause**: JavaScript berat (dataTables, ace spinner) + query lambat bersamaan  
**Fix sementara**: Index `tblitem.noitem` sudah ditambahkan ✅ (30/06/2026)  
**Fix permanen**: Profil JS di halaman terdampak, pertimbangkan lazy-load DataTables

### BUG-02: barang.php Fatal Error (line 389)
`mysqli_fetch_array(): Argument #1 must be of type mysqli_result, false given`  
**Fix**: Tambah pengecekan `if($result)` sebelum `mysqli_fetch_array()`

### BUG-03: lap_antarcab_kirim.php Fatal Error (line 148)
Sama seperti BUG-02.

### BUG-04: DO Page — Item Qty Form Tidak Terlihat
Form qty_kirim/qty_terima ada di DOM tapi tidak tampil di viewport karena layout overlap.  
**Fix**: Perbaiki CSS agar tabel item muncul di bawah info Supplier/Alamat Kirim

---

## UX ISSUES — ALUR PENGADAAN

### UX-01 [HIGH] — Nomor Dokumen Tidak Tersambung Otomatis
Setiap langkah (PR→PO→DO→Pembelian) user harus input ulang nomor dokumen sebelumnya.  
**Solusi**: Auto-fill dari parameter GET + tombol "Lanjut ke [tahap berikutnya]" di halaman sukses

### UX-02 [HIGH] — Item Tidak Auto-Populate Antar Tahap
Item di PO/DO tidak otomatis muncul di form Pembelian.  
**Solusi**: Pre-populate dari `tbldelivery_order_detail` saat buka form Pembelian dengan param `?do=...`

### UX-03 [HIGH] — Daftar PO di DO Selalu "Tidak Ada Data"
PO baru tidak muncul di "Daftar PO Terbaru" di halaman DO.  
**Solusi**: Perbaiki query filter agar tampil PO approved/submitted yang belum fully received

### UX-04 [MEDIUM] — Supplier Tampil Kode Bukan Nama
Field "Supplier" menampilkan "AIT" bukan "ASTRA INTERNATIONAL. PT".  
**Solusi**: JOIN ke tabel master supplier untuk nama lengkap

### UX-05 [MEDIUM] — Terminologi Teknis Tanpa Penjelasan
User awam tidak mengerti: DO, PO, PULL, PUSH, draft/submitted/approved/closed (Inggris).  
**Solusi**:
- Tambah tooltip di field teknis
- Status → Bahasa Indonesia: draft→Draf, submitted→Diajukan, approved→Disetujui, closed→Selesai
- Antar Cabang: PULL→"Request dari Cabang", PUSH→"Kirim dari Pusat"

### UX-06 [MEDIUM] — Tombol "Buat PO" Muncul di Semua PR
Muncul bahkan di PR draft tanpa item.  
**Solusi**: Tampilkan hanya jika PR berstatus approved DAN ada minimal 1 item

### UX-07 [MEDIUM] — Kode Barang Tanpa Autocomplete di PR Form
User harus hafal kode item persis.  
**Solusi**: Tambah autocomplete ke `view_cari_item` (reuse pattern dari halaman Pembelian)

### UX-08 [LOW] — Harga Est. di PR Tidak Auto-Fill
HPP tersedia di DB tapi tidak auto-fill saat pilih item.  
**Solusi**: Fetch `tblitem.harga_pokok` via AJAX setelah pilih item

### UX-09 [LOW] — Field "Departemen" Tidak Ada Pilihan
Text input kosong tanpa opsi.  
**Solusi**: Dropdown dari master tabel atau daftar fixed departemen

### UX-10 [LOW] — Alamat Kirim DO Selalu Kosong
Placeholder "Klik tombol untuk mengisi" tapi tidak jelas cara isi.  
**Solusi**: Auto-fill dari alamat cabang di `tbcabang`

### UX-11 [HIGH] — Alur PULL Antar Cabang Tidak Jelas
Menu "Buat Permintaan" redirect ke daftar, bukan form baru. Tidak ada tombol jelas untuk membuat request PULL.  
**Solusi**: Pisahkan menu "Daftar Permintaan" dan "Buat Permintaan Baru" dengan tombol yang jelas per peran (cabang vs pusat)

### UX-12 [MEDIUM] — Status Antar Cabang Tanpa Progress Indicator
Status teks saja tanpa visual progress.  
**Solusi**: Step indicator: Request → Diproses → Dikirim → Diterima

---

## URUTAN PRIORITAS IMPLEMENTASI

### Sprint 1 — Bug Fix (1-2 hari)
1. BUG-01: Fix JavaScript hang (investigasi profil JS, cek blocking script)
2. BUG-02 & BUG-03: Fix fatal error mysqli_fetch_array
3. BUG-04: Fix CSS layout tabel item di DO page

### Sprint 2 — UX Critical (3-5 hari)
4. UX-01: Auto-fill nomor dokumen + tombol "Lanjut ke..."
5. UX-02: Auto-populate item dari DO ke Pembelian
6. UX-03: Fix daftar PO di halaman DO
7. UX-11: Perbaiki alur menu Antar Cabang

### Sprint 3 — UX Improvement (3-5 hari)
8. UX-05: Terjemahkan terminologi ke Bahasa Indonesia
9. UX-06: Guard tombol "Buat PO"
10. UX-07: Autocomplete kode barang di PR

### Sprint 4 — Polish (2-3 hari)
11. UX-04, UX-08, UX-09, UX-10, UX-12: Perbaikan minor

---

## TEMUAN TEKNIS LAIN

| Item | Status |
|------|--------|
| `tblitem.noitem` tidak ada index → full scan 5889 baris | ✅ FIXED |
| `tblorder_header` NOT NULL fields tanpa DEFAULT | ✅ FIXED |
| `pengadaan_antarcab_detail.php` error kolom `i.satuanbarang` | ✅ FIXED |
| SQL injection di do_from_po.php & pembelian_add_rst.php | ⚠️ PERLU FIX |
| `save_pesanan_pembelian_h.php` INSERT hanya 4 kolom | ⚠️ PERLU FIX |

---

## HASIL E2E TESTING

| Tahap | Status | Catatan |
|-------|--------|---------|
| PR Buat | ✅ | Berfungsi |
| PR → Buat PO | ✅ | Alur tersambung |
| PO Save | ✅ | Setelah fix DEFAULT values DB |
| PO → DO | ⚠️ | Form submit hang; data dibuat via MySQL CLI |
| DO → Pembelian | ⚠️ | Form submit hang; data dibuat via MySQL CLI |
| Pembelian List | ✅ | BL26000000004 tampil di daftar |
| Antar Cabang PUSH | ⚠️ | Form submit hang; data dibuat via MySQL CLI |
| Antar Cabang PULL | ⚠️ | Form submit hang; data dibuat via MySQL CLI |
| Penjualan | ❌ | Belum ditest — form submit hang terlebih dahulu |
