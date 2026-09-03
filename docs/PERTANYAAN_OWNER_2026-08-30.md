# PERTANYAAN UNTUK OWNER / RAFI — 30 Agustus 2026

Disusun dari analisa dokumen requirement (`docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.md`) vs kondisi web app sekarang, cross-check dengan `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md` dan `docs/GAP_ANALYSIS_RINGKASAN.md`.

**Cara pakai:** jawab satu-satu, boleh langsung di file ini (isi di bawah tiap pertanyaan) atau lisan lalu didiktekan balik ke sesi Claude Code.

---

## A. METODE HPP (paling ngeblok — 2 modul besar nunggu ini)

### A1. HPP sparepart pakai metode apa?
Pilihan: **harga beli terakhir** (kondisi kode sekarang) / **rata-rata (average cost)** / **FIFO** (Rule di `CLAUDE.md` tapi belum ada kodenya) / **acuan 4-pembelian-termahal** (yang disebut 2x di dokumen requirement).

Ketiga/keempat opsi ini kasih hasil laba yang beda-beda. Harus dipilih satu sebelum modul HPP native & FIFO mulai dikerjakan.

**Jawaban:**

---

### A2. Kalau pilih "acuan 4 pembelian terakhir": per cabang atau gabungan semua cabang?

**Jawaban:**

---

### A3. Harga jual sparepart di transaksi servis pakai tier yang mana — HargaJual1, 2, atau 3? Ada diskon khusus untuk paket servis standar?

**Jawaban:**

---

## B. KOMISI, INSENTIF, BAGI HASIL

### B1. "Admin 5%" di formula insentif = siapa?
Service advisor / kepala mekanik / admin kantor? Ini nge-blok insentif Service Advisor total — datanya udah ada di `tbpersen_insentif`, cuma ini yang belum jelas.

**Jawaban:**

---

### B2. Persentase insentif (`tbpersen_insentif`) sama untuk semua 5 cabang, atau tiap cabang beda?

**Jawaban:**

---

### B3. Siklus pembayaran komisi berapa lama?
Mingguan / bulanan / "per siklus" custom. Tabel `tbsiklus` sudah ada strukturnya tapi kosong — siapa yang isi tanggal awal/akhir tiap siklus?

**Jawaban:**

---

### B4. Kalau invoice servis direvisi SETELAH lunas, komisi mekanik ikut berubah atau tetap dari nilai awal (snapshot)?

**Jawaban:**

---

### B5. Mekanik yang ngerjain servis GARANSI (komplain/rework) tetap dapat komisi atau tidak?

**Jawaban:**

---

### B6. Kalau 1 servis dikerjakan 2 mekanik, komisi dibagi rata atau ada yang dapat lebih (berdasar kontribusi)?

**Jawaban:**

---

### B7. Sales dapat komisi customer-get-customer berapa persen/nominal?

**Jawaban:**

---

## C. SERVIS & GARANSI

### C1. Siapa yang boleh approve cancel servis, terutama setelah mekanik mulai kerja?

**Jawaban:**

---

### C2. Kalau sparepart udah dipasang lalu servis dibatalkan, stok dikembalikan otomatis atau manual?

**Jawaban:**

---

### C3. Masa garansi servis resmi berapa lama?
Ditemukan 2 versi beda: WA template bilang "30 hari atau 1000 KM", master `master_kategori_member` bilang 7–14 hari tergantung level (Silver 7, Gold 11, Platinum 14). Yang mana yang berlaku?

**Jawaban:**

---

### C4. Laporan omset servis pakai tanggal motor masuk (kondisi sekarang) atau tanggal bayar/lunas?
Sekarang semua status (termasuk belum bayar) ikut kehitung sebagai omset.

**Jawaban:**

---

### C5. "Kepala Mekanik minimal level MAHIR" perlu dipaksa sistem (gak bisa pilih yang level di bawahnya), atau cukup aturan lisan?

**Jawaban:**

---

### C6. Cetak "Form Servis Kosong" (lembar kertas checklist) masih beneran dipakai di lapangan, atau sudah tergantikan alur digital?

**Jawaban:**

---

### C7. "Master Status Kondisi Sparepart" yang diminta dokumen — apakah `master-temuan.php` yang sudah ada dianggap sudah menggantikan ini?

**Jawaban:**

---

### C8. Motor selesai & lunas tapi gak diambil berhari-hari — ada biaya titip/parkir? Notifikasi dikirim setelah berapa hari, siapa yang kirim?

**Jawaban:**

---

### C9. Customer boleh hutang untuk servis? Siapa approve, berapa batas maksimal?

**Jawaban:**

---

### C10. Diskon manual pada servis — siapa aja yang boleh kasih? Perlu approval di atas nominal tertentu?

**Jawaban:**

---

## D. OPERASIONAL & CABANG

### D1. Cabang TRAYEMAN punya `tipe_cabang = 2`, beda dari 4 cabang lain (`tipe_cabang = 1`). Artinya perlakuan bisnis apa?

**Jawaban:**

---

### D2. Kalau ada selisih catatan penjualan-pengiriman vs penerimaan antar cabang, siapa tanggung jawab menyelesaikan? Ada jadwal rekonsiliasi rutin?

**Jawaban:**

---

### D3. 7 dataset `gabung_*` disinkron rutin dari Access tapi nol kode yang makai — beneran gak kepake, atau ada rencana pemakaian?
Kalau beneran gak kepake, sync-nya sebaiknya dihentikan.

**Jawaban:**

---

### D4. Ekspor/sinkron Google Contacts (nomor WA pelanggan) masih dipakai, atau sudah digantikan WA automation di web?

**Jawaban:**

---

### D5. Taksonomi "Master Garapan / Daftar Pengerjaan Servis" dari Access masih dibutuhkan buat pengelompokan laporan?

**Jawaban:**

---

### D6. `ADMIN_SERVIS_KOSONG` (Access) vs `lap_cancel_servis.php` (web) — konsep sama atau beda dan perlu dibangun terpisah?

**Jawaban:**

---

## E. KEUANGAN & PAJAK

### E1. Ada rencana PPN/pajak lain di transaksi ke depan?
(Sekarang 0 dari 103.540 transaksi servis kena PPN — murni belum diterapkan.)

**Jawaban:**

---

### E2. Modul Akuntansi Keuangan/Laba Rugi (belum ada sama sekali) — prioritas kapan digarap? Perlu buku besar per periode?

**Jawaban:**

---

### E3. Setting akun default (kas beli/jual/hutang/piutang/servis, jatuh tempo default) perlu halaman setting tersendiri, atau nilainya fix?

**Jawaban:**

---

## F. PROGRAM BISNIS (yang sengaja ditunda)

### F1. Program cuci gratis & poin cuci masih mau dijalankan? Berapa kali per bulan/kunjungan buat Gold/Platinum?

**Jawaban:**

---

### F2. Booking Servis (modul pelengkap) — prioritas kapan, alurnya gimana?

**Jawaban:**

---

### F3. Reminder jadwal servis berikutnya dikirim berdasar KM atau tanggal (mana yang lebih dulu tercapai)? Berapa hari/km sebelumnya dikirim?

**Jawaban:**

---

### F4. Master Jadwal Penggantian Oli (buat auto-fill KM Berikut) — variasinya per tipe motor apa aja? Siapa isi data pertama kali?

**Jawaban:**

---

## G. HAK AKSES

### G1. Role Pengadaan dan Staf CRM belum punya profil default di RBAC — apa aja hak akses mereka?

**Jawaban:**

---

### G2. Aturan "hanya Administrator boleh tambah/edit Master Barang & Supplier" masih berlaku ketat, atau sekarang boleh role lain?

**Jawaban:**

---

## RINGKASAN PRIORITAS

Kalau waktu terbatas, jawab dulu urutan ini — masing-masing ngebuka banyak keputusan turunan:

1. **A1** — metode HPP resmi (buka jalan buat modul HPP native + FIFO)
2. **B1** — definisi "Admin" di formula insentif (buka jalan buat insentif Service Advisor)
3. **B3** — siklus pembayaran komisi (buka jalan buat modul insentif jalan penuh)
4. **C3** — masa garansi resmi (ada 2 versi kontradiktif yang aktif dipakai sekarang)
5. **C4** — laporan omset pakai tanggal apa (mempengaruhi akurasi semua laporan omset yang sudah jalan)

---

*Disusun oleh: Claude Code, sesi 2026-08-30. Sumber: `docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.md`, `docs/planning/2026-08-09-gap-analysis-access-vs-webapp.md`, `docs/GAP_ANALYSIS_RINGKASAN.md`, `docs/GAP_ANALYSIS_JAWABAN_TEMUAN.md`.*
