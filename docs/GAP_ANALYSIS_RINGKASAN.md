# RINGKASAN PERTANYAAN BISNIS — MEETING PAK NOVIAN
### Bengkel 2.0 Web Migration | 2026-06-26
### 25 pertanyaan master, mewakili 110 pertanyaan teknis

---

> **Cara pakai:** Jawab satu per satu dari atas ke bawah. Setiap jawaban akan langsung membuka/menutup banyak keputusan teknis di belakang layar.

---

## BLOK A — ALUR SERVIS (Fondasi Sistem)

### A1. Urutan Status Servis
> *Mewakili: C-01, C-02, H-01, H-02*

Gambarkan alur servis dari awal sampai akhir. Contoh:
> "Motor datang → antri → dikerjakan → [QC?] → kasir bayar → motor diambil"

Apakah ada perbedaan alur antara servis reguler dan jemput antar?

---

### A2. Kapan Stok Dipotong & Bagaimana Jika Batal
> *Mewakili: C-03, C-04, C-05, C-06*

- **(a)** Stok sparepart dipotong **kapan** — saat input work order, saat selesai dikerjakan, atau saat bayar?
- **(b)** Jika servis dibatalkan setelah sparepart diinput, stok **dikembalikan otomatis** atau tidak?

---

### A3. Definisi & Durasi Garansi Servis
> *Mewakili: C-11, C-12, C-10, H-21*

- Berapa lama masa garansi servis (hari/minggu)?
- Garansi berlaku untuk jasa saja, sparepart saja, atau keduanya?
- Jika garansi diklaim, sparepart yang dipakai: dipotong stok normal atau ada akun biaya garansi terpisah?
- Mekanik dapat komisi untuk pengerjaan garansi?

---

### A4. Laporan Omset — Dibaca dari Mana
> *Mewakili: C-13, C-14*

- Laporan omset harian membaca tanggal **pembayaran** atau tanggal **motor masuk**?
- Di laporan total, servis dan penjualan counter **digabung** atau **dipisah**?

---

## BLOK B — KOMISI & INSENTIF (Paling Sensitif)

### B1. Formula Komisi Mekanik — Masih Berlaku?
> *Mewakili: C-07, C-09, C-15, C-16*

Di sistem lama ditemukan formula:
- **Jasa:** `(Total Jasa - Biaya Outsource) × 20% ÷ jumlah mekanik`
- **Barang:** `Laba Item × 5% ÷ jumlah mekanik`

Pertanyaan:
- **(a)** Formula ini **masih berlaku**?
- **(b)** Jika satu motor dikerjakan 2 mekanik — dibagi **rata** atau ada yang dapat lebih?
- **(c)** Jika invoice direvisi setelah selesai, komisi **ikut berubah** atau tetap dari nilai awal?

---

### B2. "Admin" di Formula Komisi = Siapa?
> *Mewakili: C-08, C-17*

Di sistem lama ada komponen "Admin Jasa = 5%" dan "Admin Barang = 5%". Siapa yang dimaksud "Admin" — **service advisor**, **kepala mekanik**, atau **admin kantor**?

---

### B3. Persentase Beda Per Cabang & Periode Bayar
> *Mewakili: C-32, C-28*

- Persentase komisi (20% jasa, 5% barang) **sama untuk semua cabang** atau tiap cabang bisa beda?
- Komisi dibayarkan **per bulan, per minggu, atau per siklus** (apa itu siklus)?

---

### B4. "Outsource" dalam Formula Komisi = Apa?
> *Mewakili: C-35*

Ada komponen "biaya outsource" yang dikurangi dari basis komisi jasa. Apa yang dimaksud outsource di sini?

---

## BLOK C — STOK & HPP

### C1. Metode Harga Pokok (HPP) yang Dipakai
> *Mewakili: C-18*

Saat menghitung laba penjualan sparepart, HPP yang digunakan:
- Harga beli **terakhir**, atau
- Harga beli **rata-rata** (average cost), atau
- **FIFO** (barang yang masuk pertama keluar pertama)?

---

### C2. Harga Jual Sparepart di Servis
> *Mewakili: C-26, C-27*

Sistem lama punya 3 tier harga (HargaJual1, 2, 3). Saat servis dipakai harga yang mana? Ada diskon khusus untuk paket servis standar?

---

### C3. Prosedur Stok Opnam
> *Mewakili: C-20*

Stok opnam rutin dilakukan seberapa sering? Jika ada selisih, siapa yang berwenang melakukan penyesuaian?

---

## BLOK D — PROGRAM BISNIS

### D1. Program Member & Gratis Cuci Motor
> *Mewakili: C-29, C-30*

- Apakah ada program **member**? Apa benefitnya (diskon, prioritas, poin)?
- Apakah program **gratis cuci motor** masih aktif? Apa syaratnya?

---

### D2. Reminder Servis ke Customer
> *Mewakili: H-05, H-06, H-07*

- Reminder dikirim via **WA atau SMS**? Berapa hari/minggu sebelum jadwal?
- Jadwal perkiraan servis dihitung dari **km** atau **tanggal** (mana yang lebih dulu)?

---

## BLOK E — KONTROL & KEUANGAN

### E1. Hak Akses Per Role
> *Mewakili: H-15, H-27*

Isi tabel sesuai yang diinginkan:

| Role | Bisa Lihat | Bisa Input/Edit | Tidak Boleh |
|---|---|---|---|
| Owner | semua | semua | — |
| Kepala Bengkel | ? | ? | ? |
| Service Advisor | ? | ? | ? |
| Mekanik | ? | ? | ? |
| Kasir | ? | ? | ? |
| Gudang | ? | ? | ? |
| Admin Cabang | ? | ? | ? |

---

### E2. Siapa yang Boleh Beri Diskon?
> *Mewakili: H-12*

Diskon pada servis — siapa saja yang boleh memberikan? Apakah di atas nominal tertentu perlu approval kepala bengkel atau owner?

---

### E3. Customer Boleh Hutang untuk Servis?
> *Mewakili: C-25*

Apakah ada customer yang diperbolehkan bayar nanti untuk servis? Siapa yang approve dan berapa batas maksimalnya?

---

### E4. Pajak
> *Mewakili: C-33*

Apakah ada PPN atau pajak lain yang dikenakan pada transaksi? Berapa persennya? Semua cabang sama?

---

### E5. Rekonsiliasi Antar Cabang
> *Mewakili: C-23*

Jika ada selisih antara catatan penjualan pusat dan penerimaan cabang, siapa yang bertanggung jawab menyelesaikan? Ada jadwal rekonsiliasi rutin?

---

## BLOK F — OPERASIONAL

### F1. Servis Jemput Antar
> *Mewakili: H-03, H-04*

- Siapa yang menjemput — **mekanik yang sama** atau **kurir terpisah**?
- Tarif jemput dihitung dari **jarak, zona, atau flat fee**?
- Biaya jemput masuk ke invoice servis atau **transaksi terpisah**?

---

### F2. Antrian & Kapasitas
> *Mewakili: H-10, H-28*

- Berapa kapasitas maksimal motor per hari per mekanik?
- Customer yang booking online diprioritaskan dibanding walk-in?

---

### F3. Mekanik Tetap vs Freelance & Lintas Cabang
> *Mewakili: C-24, H-30*

- Apakah ada mekanik freelance/harian selain karyawan tetap?
- Apakah satu mekanik bisa bekerja di lebih dari satu cabang?

---

### F4. Motor Tidak Diambil
> *Mewakili: C-34*

Jika motor sudah selesai dan sudah dibayar tapi tidak diambil beberapa hari:
- Ada biaya titip/parkir?
- Siapa yang kirim notifikasi dan dalam berapa hari?

---

### F5. 3 Laporan Terpenting untuk Owner
> *Mewakili: H-16, M-09, M-17*

Dari semua laporan yang ada, **3 laporan paling penting** yang Pak Novian ingin lihat setiap hari/minggu adalah apa?

---

## RINGKASAN

| Blok | Topik | Pertanyaan |
|---|---|---|
| A | Alur Servis | 4 |
| B | Komisi & Insentif | 4 |
| C | Stok & HPP | 3 |
| D | Program Bisnis | 2 |
| E | Kontrol & Keuangan | 5 |
| F | Operasional | 5 |
| **Total** | | **25** |

---

*Detail teknis lengkap → [GAP_ANALYSIS_PERTANYAAN_OWNER.md](GAP_ANALYSIS_PERTANYAAN_OWNER.md)*
*Versi: 1.0 | 2026-06-26*
