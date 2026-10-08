# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Implementasi Metode Pencatatan Persediaan Moving Average (Average Cost)
### Fit Motor Webbase

| Dokumen | Keterangan |
|---|---|
| Nama Dokumen | PRD - Moving Average Inventory Costing |
| Modul Terkait | Inventory, Purchasing, Sales, Service, Finance & Accounting |
| Disusun oleh | Business Analyst |
| Ditujukan kepada | Programmer, QA, Project Manager |
| Status | **v1.0 — Final, siap untuk development** (satu parameter konfigurasi masih menunggu nilai default dari manajemen — lihat Bagian 15) |
| Versi | 1.0 |
| Riwayat | v0.1 (draft awal) → v1.0 (revisi penuh pasca konfirmasi seluruh keputusan bisnis KRITIS) |

> Dokumen ini menggantikan draft v0.1. Seluruh Business Rules, Flow, dan Test Scenario pada dokumen ini sudah mencerminkan keputusan final yang dikonfirmasi pemilik bisnis (Bagian 3 dan 15). Poin yang masih ditandai **[PERLU NILAI DEFAULT]** bukan lagi ambiguitas kebijakan, melainkan hanya menunggu angka konfigurasi teknis yang tidak menghalangi development dimulai.

---

## 1. Executive Summary

**Tujuan Implementasi**
Mengimplementasikan metode penilaian persediaan **Moving Average (Average Cost)** pada Fit Motor Webbase sebagai metode standar untuk menghitung nilai persediaan dan Harga Pokok Penjualan (HPP), dihitung **secara independen per cabang** (termasuk Gudang Pusat sebagai satu lokasi), dengan prinsip **tidak ada perubahan retroaktif terhadap transaksi historis** — seluruh koreksi bersifat maju (forward-only).

**Ruang Lingkup**
- Seluruh transaksi yang menambah, mengurangi, atau memindahkan qty/nilai persediaan sparepart di seluruh lokasi Fit Motor (1 Gudang Pusat + cabang): Purchasing, Inventory, Sales, Service (WO), serta koreksi dari Finance/Accounting.
- Average dihitung per item **per lokasi** (Gudang Pusat dan tiap cabang masing-masing punya average sendiri).
- **Tidak termasuk**: modul Produksi/Assembly (dikonfirmasi tidak diperlukan), fitur backdate/recalculation berantai (dikonfirmasi diganti mekanisme lain), kebijakan penentuan harga jual/pricing engine (di luar cakupan, akan menjadi PRD terpisah).

**Tujuan Bisnis**
- HPP dan nilai persediaan mencerminkan biaya perolehan riil per lokasi secara berkelanjutan.
- Laporan periode yang sudah berjalan **tidak pernah berubah retroaktif** — koreksi apa pun tampil di periode koreksi, bukan menulis ulang sejarah.
- Menyediakan jejak audit penuh atas setiap perubahan average.

**Manfaat bagi Perusahaan**
- Keputusan harga jual dan margin didasarkan pada biaya riil rata-rata per lokasi.
- Arsitektur sederhana (tanpa mesin recalculation berantai) — sesuai skala operasional 1 Gudang Pusat + 4 cabang dengan estimasi ~200 transaksi/hari.
- Nilai persediaan di neraca dapat dipertanggungjawabkan saat audit/pajak, dengan jejak koreksi yang jelas dan tidak pernah menimpa data lama.

---

## 2. Business Background

### 2.1 Konteks Operasional (dikonfirmasi)
- Arsitektur database Fit Motor Webbase: **terpusat**, satu database untuk seluruh lokasi.
- Estimasi volume transaksi: **~200 transaksi/hari** gabungan untuk 1 Gudang Pusat + 4 cabang (≈40 transaksi/lokasi/hari) — volume rendah, mendukung desain pemrosesan langsung (synchronous) tanpa perlu infrastruktur antrian (queue).
- Alur distribusi utama: **Supplier → Gudang Pusat → Cabang**. Cabang diperbolehkan membeli langsung ke supplier/toko terdekat untuk kebutuhan mendesak.
- Harga jual dari Gudang Pusat ke Cabang = **HPP Gudang Pusat (at cost, tanpa markup)**.

### 2.2 Mengapa Menggunakan Moving Average
- Menghitung ulang rata-rata biaya setiap ada penambahan qty dengan harga berbeda — HPP mencerminkan kondisi biaya terkini secara bertahap (smoothing).
- Lebih sederhana dioperasikan dibanding FIFO berlapis, cocok untuk sparepart bervolume tinggi dengan variasi harga antar pembelian.
- Diterima secara akuntansi (PSAK 14 mengizinkan Average maupun FIFO) dan proporsional untuk skala UMKM.

### 2.3 Perbedaan dengan FIFO dan Standard Cost

| Aspek | Moving Average | FIFO | Standard Cost |
|---|---|---|---|
| Dasar HPP | Rata-rata tertimbang berjalan | Harga pembelian tertua yang belum terjual | Harga tetap, direvisi berkala |
| Kompleksitas | Sedang | Tinggi (tracking lapisan/batch) | Rendah, tapi perlu akun varians |
| Cocok untuk skala Fit Motor | **Ya — dipilih** | Terlalu rumit untuk volume & tim operasional saat ini | Kurang cocok, harga beli sering berubah antar pembelian |

### 2.4 Dampak terhadap Laporan Keuangan
- **Neraca**: nilai persediaan berubah tiap transaksi masuk yang mengubah average, per lokasi.
- **Laba Rugi**: HPP dihitung dari average saat transaksi terjadi. Selisih harga yang ditemukan setelah barang terjual dibukukan sebagai akun **Selisih Harga Pembelian**, bukan menulis ulang HPP historis (lihat Bagian 3.5).
- **Mutasi Persediaan**: setiap transaksi yang memengaruhi average tercermin di kartu stok per lokasi.

---

## 3. Business Rules

### 3.1 Aturan Dasar Average

| # | Aturan | Status |
|---|---|---|
| BR-01 | Average berubah **hanya** saat transaksi masuk (pembelian, retur penjualan, adjustment(+), transfer masuk yang sudah diterima fisik) | **Final** |
| BR-02 | Average **tidak berubah** saat transaksi keluar (penjualan, retur pembelian, adjustment(-), transfer keluar) | **Final** |
| BR-03 | HPP transaksi keluar menggunakan average yang berlaku **pada saat transaksi diposting** | **Final** |
| BR-04 | Jika stok kosong (qty = 0) lalu ada pembelian masuk, average baru = harga beli masuk tersebut | **Final** |
| BR-05 | **Stok minus dilarang total, tanpa pengecualian.** Sistem menolak transaksi keluar apa pun yang menyebabkan qty menjadi negatif di lokasi manapun | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-06 | **Tidak ada fitur backdate.** Transaksi selalu berlaku efektif pada tanggal ia diinput ke sistem. Koreksi atas kesalahan masa lalu dilakukan melalui mekanisme Koreksi Maju (Bagian 3.5), bukan dengan menyisipkan/mengedit transaksi ke tanggal lampau | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-07 | **Retur penjualan menggunakan average berjalan (Skenario A)** — diperlakukan sebagai transaksi masuk biasa dengan harga masuk = HPP saat transaksi penjualan asli, dimasukkan ke rumus average normal. Retur **tidak** mengembalikan average ke nilai sebelum penjualan asli terjadi | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-08 | Periode yang sudah closing: transaksi pada periode tersebut **tidak boleh diedit/dihapus**. Sistem menggunakan tiga status periode: **Open** (dapat dikoreksi sesuai kewenangan), **Closed** (tidak boleh diedit langsung, koreksi lewat Adjustment di periode berjalan), **Locked** (koreksi membutuhkan otorisasi khusus di atas normal) | **Final** |
| BR-09 | Pembatalan transaksi: **Cancel** (sebelum barang keluar gudang secara fisik — qty & average kembali seperti semula tanpa jejak keluar-masuk) dibedakan tegas dari **Void** (setelah barang keluar — diperlakukan sebagai retur, mengikuti BR-07) | **Final** |
| BR-10 | **Average dihitung per lokasi (per item per cabang, dan per item di Gudang Pusat), independen** — tidak ada average konsolidasi gabungan seluruh lokasi | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-11 | Setiap perubahan average wajib tercatat di Average Cost History dengan referensi transaksi pemicu | **Final** |
| BR-12 | Sistem membedakan **Transaction Date** (kapan transaksi sebenarnya terjadi secara bisnis) dari **Correction Date** (kapan koreksi diinput) — keduanya disimpan, namun perhitungan average selalu mengikuti urutan **Correction Date/tanggal input**, bukan Transaction Date, sesuai BR-06 | **Final** |

### 3.2 Transfer Antar Lokasi & Barang dalam Transit

| # | Aturan | Status |
|---|---|---|
| BR-13 | Transfer antar lokasi (termasuk Gudang Pusat → Cabang) menggunakan status **"Barang dalam Perjalanan"** — qty keluar dari lokasi asal dan nilainya dipindahkan ke akun Barang dalam Perjalanan, **belum masuk ke average lokasi tujuan** | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-14 | Average lokasi tujuan baru dihitung ulang **saat barang diterima secara fisik** (konfirmasi penerimaan), bukan saat dikirim. Harga masuk yang dipakai = harga transfer (untuk Gudang Pusat → Cabang: sebesar HPP Gudang Pusat saat pengiriman, at cost tanpa markup) | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-15 | Jika pengiriman gagal/dibatalkan selama status transit (mis. cabang tujuan tutup), qty & nilai dikembalikan ke lokasi asal; average lokasi asal **tidak terpengaruh** karena statusnya hanya kembali dari "dalam perjalanan", bukan transaksi baru | **Final** |

### 3.3 Cabang Beli Langsung ke Supplier/Toko Lokal

| # | Aturan | Status |
|---|---|---|
| BR-16 | Pembelian darurat oleh cabang langsung ke supplier/toko lokal dicatat sebagai pembelian biasa di lokasi cabang tersebut (BR-01), masuk ke average cabang yang bersangkutan — **tidak mempengaruhi average Gudang Pusat atau cabang lain** | **Final** |

### 3.4 Bonus, Replacement, dan Klaim Supplier

| # | Aturan | Status |
|---|---|---|
| BR-17 | Bonus supplier (barang gratis) **dialokasikan secara proporsional** dari harga barang utama dalam faktur yang sama — tidak dimasukkan sebagai harga Rp0 mentah ke rumus average | **Final** |
| BR-18 | Replacement supplier (penggantian barang cacat) dicatat sebagai **dua transaksi terpisah**: Retur Pembelian (barang cacat keluar) + Pembelian baru (barang pengganti masuk) — bukan tukar langsung tanpa jejak | **Final** |
| BR-19 | Sparepart Claim (klaim ke supplier atas barang cacat) dan Penggantian Sparepart Warranty dibukukan melalui akun **"Piutang Klaim Supplier"** terpisah, bukan langsung sebagai beban | **Final** |
| BR-20 | Konsinyasi supplier: **tidak diimplementasikan pada rilis ini** (ditunda, tidak ada kebutuhan riil dikonfirmasi) | **Final** |

### 3.5 Mekanisme Koreksi (Pengganti Backdate)

**Prinsip utama: transaksi asli tidak pernah diubah atau dihapus (Immutable Transaction). Semua koreksi berupa transaksi baru bertanggal hari ini (Correction Date), dengan referensi eksplisit ke transaksi yang dikoreksi.**

| # | Jenis Kesalahan | Mekanisme Koreksi | Status |
|---|---|---|---|
| BR-21 | Salah qty pada transaksi lama | **Stock Adjustment (+/-)** bertanggal hari ini, wajib reason code + referensi transaksi asal | Final |
| BR-22 | Salah harga beli, barang **masih ada** di gudang (qty on hand ≥ qty yang perlu dikoreksi) | **Koreksi Harga Pembelian**: selisih harga × qty yang masih ada dimasukkan ke rumus average seperti pembelian baru dengan harga = selisih | Final |
| BR-23 | Salah harga beli, sebagian/seluruh barang **sudah terjual** | Untuk qty yang masih ada: seperti BR-22. Untuk qty yang sudah terjual: selisih dibukukan **langsung ke akun "Selisih Harga Pembelian"** di Laba Rugi periode berjalan. HPP transaksi penjualan yang sudah terjadi **tidak diubah** | **Final — Keputusan Bisnis Terkonfirmasi** |
| BR-24 | Salah tanggal transaksi | Tidak dikoreksi ke tanggal yang benar. Dicatat di keterangan/audit log; transaksi tetap berlaku pada Correction Date | Final |
| BR-25 | Salah lokasi/cabang | Dikoreksi melalui **Transfer Antar Lokasi** dari lokasi salah ke lokasi benar, bertanggal hari ini | Final |
| BR-26 | Salah pembayaran (Finance) | **Jurnal Koreksi** terpisah, tidak menyentuh data persediaan | Final |
| BR-27 | Seluruh mekanisme koreksi di atas wajib melalui **maker-checker**: petugas mengajukan → atasan (Kepala Cabang/Kepala Mekanik) memeriksa → Finance/HQ menyetujui koreksi yang berdampak keuangan, dengan audit log **Who-What-When-Why-Before-After-Approval** | Final |

---

## 4. Identifikasi Seluruh Transaksi yang Berdampak terhadap Persediaan

Legenda: **Qty** = Mengubah Qty, **Avg** = Mengubah Average, **Nilai** = Mengubah Nilai Persediaan, **HPP** = Menghasilkan HPP.

### 4.1 Purchasing

| Transaksi | Qty | Avg | Nilai | HPP | Keterangan |
|---|---|---|---|---|---|
| Pembelian (dari supplier ke Gudang Pusat, atau cabang beli darurat lokal - BR-16) | Naik | Ya | Naik | Tidak | Pemicu average baru sesuai rumus |
| Retur Pembelian | Turun | Tidak berubah (BR-02) | Turun sebesar qty × average berjalan | Tidak | |
| Purchase Adjustment | Bisa naik/turun | Ya jika mengubah harga/qty | Ya | Tidak | |
| Bonus Supplier | Naik | Ya, harga dialokasikan proporsional (BR-17) | Naik | Tidak | |
| Replacement Supplier | Retur + Pembelian baru (BR-18) | Sesuai masing-masing transaksi | Sesuai masing-masing | Tidak | |
| Sparepart Claim ke Supplier | Turun (barang keluar untuk diklaim) | Tidak berubah | Turun, dipindah ke Piutang Klaim Supplier (BR-19) | Tidak | |
| Konsinyasi Masuk | — | — | — | — | Tidak diimplementasikan (BR-20) |

### 4.2 Inventory

| Transaksi | Qty | Avg | Nilai | HPP | Keterangan |
|---|---|---|---|---|---|
| Stock Adjustment (+) | Naik | Ya, seperti pembelian | Naik | Tidak | Wajib reason code |
| Stock Adjustment (-) | Turun | Tidak berubah | Turun sebesar average berjalan | Kadang dibebankan sebagai kerugian | Wajib reason code + approval |
| Stock Opname | Naik/turun sesuai selisih | Sesuai arah (naik: seperti +; turun: tidak berubah) | Naik/turun | Selisih minus = kerugian | Approval berjenjang; ambang batas nominal = parameter konfigurasi (Bagian 15) |
| Opening Stock (go-live) | Naik | Ya, menjadi average awal | Naik | Tidak | Sumber: data pembelian riil terakhir per item per lokasi |
| Transfer Antar Lokasi (termasuk Gudang Pusat → Cabang) | Turun di asal (ke status transit), Naik di tujuan saat diterima | Asal: tidak berubah. Tujuan: dihitung ulang saat diterima (BR-13, BR-14) | Berpindah via akun Barang dalam Perjalanan | Tidak | |
| Barang Hilang / Rusak / Scrap | Turun | Tidak berubah | Turun, dibebankan sebagai kerugian | Tidak (beban, bukan HPP) | Wajib berita acara/approval |
| Konversi Satuan | Qty berubah sesuai rasio | Average per satuan baru = average lama ÷ rasio | Nilai total tetap | Tidak | |
| Pemakaian Sparepart Internal | Turun | Tidak berubah | Turun, dibebankan sebagai beban operasional | Tidak (beban, bukan HPP penjualan) | Jenis transaksi tersendiri, terpisah dari Penjualan/Adjustment |

> Bundling/Unbundling dan modul Produksi/Assembly: **tidak diimplementasikan** — dikonfirmasi tidak sesuai arah bisnis Fit Motor.

### 4.3 Sales

| Transaksi | Qty | Avg | Nilai | HPP | Keterangan |
|---|---|---|---|---|---|
| Penjualan Customer | Turun **saat item diinput ke invoice** | Tidak berubah | Turun | Ya, HPP = qty × average berjalan | Tidak ada Delivery Order terpisah dari invoice |
| Retur Penjualan Customer | Naik | Ya, sesuai BR-07 (Skenario A) | Naik | Membalik HPP | |
| Penjualan Antar Cabang/Gudang Pusat | Turun di lokasi penjual **saat diinput ke invoice** | Tidak berubah di lokasi penjual | Turun | Ya, sisi penjual | Sisi penerima mengikuti aturan Transfer Antar Lokasi (BR-13, BR-14) jika berupa pengiriman stok, bukan penjualan tunai antar lokasi |
| Cancel Invoice (sebelum barang keluar) | Kembali ke semula | Tidak berubah | Kembali ke semula | Dibatalkan | |
| Void Invoice (setelah barang keluar) | Naik (diperlakukan sebagai retur) | Sesuai BR-07 | Naik | Membalik HPP | Dibedakan tegas dari Cancel (BR-09) |

### 4.4 Service

| Transaksi | Qty | Avg | Nilai | HPP | Keterangan |
|---|---|---|---|---|---|
| Penjualan Sparepart via WO | Turun | Tidak berubah | Turun | Ya | Sama prinsip dengan Penjualan Customer |
| Retur Sparepart Service | Naik | Sesuai BR-07 | Naik | Membalik HPP | |
| Pembatalan WO | Naik (jika sparepart sudah keluar) | Sesuai BR-07 | Naik | Membalik HPP | |
| Penggantian Sparepart Warranty | Turun lalu naik (saat pengganti dari klaim diterima) | Sesuai BR-19 | Via Piutang Klaim Supplier | Tidak (bukan HPP normal) | |

### 4.5 Finance & Accounting

| Transaksi | Qty | Avg | Nilai | HPP | Keterangan |
|---|---|---|---|---|---|
| Koreksi Harga Pembelian | Tidak berubah | Ya, sesuai BR-22/BR-23 | Ya | Berpotensi ke akun Selisih Harga Pembelian, bukan HPP historis | Forward-only, lihat Bagian 3.5 |
| Debit Note / Credit Note | Tidak berubah | Bisa menurunkan/menaikkan average jika barang masih ada | Ya | Tidak langsung | |
| Revaluasi Persediaan | Tidak berubah | Ya, langsung ke nilai baru | Ya | Tidak | **Dibatasi hanya Finance HQ**, wajib approval + alasan tertulis |

---

## 5. Flow Average Cost

### 5.1 Flow Transaksi Masuk

```
[TRANSAKSI MASUK: Pembelian / Retur Penjualan / Adjustment(+) / Penerimaan Transfer]
        |
        v
[Validasi: Item valid? Harga >= 0? Qty > 0? Lokasi valid?]
        |
        v
[Ambil Qty Lama & Average Lama dari Inventory Balance LOKASI INI (row-level lock diaktifkan)]
        |
        v
[Hitung Average Baru: Rumus Moving Average - Bagian 6]
        |
        v
[Update Qty on Hand & Average Cost di Inventory Balance lokasi ini]
        |
        v
[Catat ke Inventory Ledger + Average Cost History]
        |
        v
[Posting Jurnal Akuntansi (real-time)]
        |
        v
[Lepas lock]
```

### 5.2 Flow Transaksi Keluar

```
[TRANSAKSI KELUAR: Penjualan / WO Service / Retur Pembelian / Adjustment(-) / Pengiriman Transfer]
        |
        v
[Validasi: Qty tersedia cukup? (WAJIB - stok minus dilarang total, BR-05)]
        |
        v
[Jika tidak cukup -> TOLAK transaksi, tampilkan qty tersedia saat ini]
        |
        v
[Ambil Average Cost berjalan LOKASI INI]
        |
        v
[Hitung HPP = Qty Keluar x Average Cost Berjalan]
        |
        v
[Update Qty on Hand (turun). Average TIDAK berubah]
        |
        v
[Jika transfer: pindahkan nilai ke akun "Barang dalam Perjalanan", status = In Transit]
        |
        v
[Catat ke Inventory Ledger]
        |
        v
[Posting Jurnal Akuntansi (real-time)]
```

### 5.3 Flow Penerimaan Transfer (Barang dalam Transit)

```
[Barang berstatus "Dalam Perjalanan" dari lokasi asal]
        |
        v
[Lokasi tujuan konfirmasi penerimaan fisik]
        |
        v
[Sistem memperlakukan penerimaan ini sebagai TRANSAKSI MASUK biasa di lokasi tujuan,
 dengan harga masuk = nilai barang saat dikirim (harga transfer / HPP pengirim)]
        |
        v
[Jalankan Flow 5.1 - hitung average baru lokasi tujuan]
        |
        v
[Akun "Barang dalam Perjalanan" berkurang sebesar nilai yang diterima]

[JIKA GAGAL/DIBATALKAN saat transit:]
        |
        v
[Qty & nilai dikembalikan ke lokasi asal - average lokasi asal TIDAK berubah,
 karena bukan transaksi baru, hanya pembalikan status transit]
```

### 5.4 Flow Koreksi (Pengganti Backdate)

```
[Kesalahan pada transaksi lama ditemukan]
        |
        v
[Petugas mengajukan Transaksi Koreksi bertanggal HARI INI, dengan referensi ke transaksi asal
 dan alasan wajib diisi]
        |
        v
[Approval berjenjang - maker-checker (BR-27)]
        |
        v
[JIKA transaksi asal berada di periode CLOSED/LOCKED -> koreksi tetap diizinkan
 (karena koreksi bertanggal hari ini, BUKAN mengedit periode closed - BR-06/BR-08)]
        |
        v
[Sistem memproses sebagai transaksi baru mengikuti Flow 5.1/5.2 sesuai jenisnya]
        |
        v
[JIKA koreksi harga & sebagian barang sudah terjual: pisahkan porsi qty tersisa
 (masuk ke average) dari porsi qty terjual (masuk ke akun Selisih Harga Pembelian) - BR-23]
        |
        v
[Catat ke Average Cost History dengan flag "correction", referensi transaksi asal]
        |
        v
[Notifikasi ke Finance/Accounting]
```

> **Catatan penting:** karena tidak ada backdate/recalculation berantai, flow ini jauh lebih sederhana dari draft v0.1 — tidak ada proses lock lintas banyak transaksi, tidak ada resiko cascading, tidak ada kebutuhan job queue.

---

## 6. Rumus Moving Average

### 6.1 Rumus Dasar

```
Average Baru = ((Qty Lama x Average Lama) + (Qty Masuk x Harga Baru)) / (Qty Lama + Qty Masuk)
```

Berlaku **per lokasi** (BR-10) — Gudang Pusat dan tiap cabang punya perhitungan sendiri-sendiri, tidak saling mempengaruhi kecuali melalui transaksi transfer eksplisit (BR-13/BR-14).

### 6.2 Penerapan pada Berbagai Kondisi

| Kondisi | Perlakuan |
|---|---|
| Pembelian pertama / stok nol lalu ada transaksi masuk | Average Baru = Harga Masuk (Qty Lama = 0) |
| Stok minus | **Tidak berlaku — dilarang total (BR-05).** Sistem menolak transaksi sebelum qty bisa menjadi negatif |
| Retur Pembelian | Average tidak berubah (BR-02) |
| Retur Penjualan | Masuk ke rumus average biasa dengan harga masuk = HPP saat penjualan asli (BR-07, Skenario A) |
| Adjustment (+) | Masuk ke rumus average dengan harga = harga input adjustment |
| Adjustment (-) | Average tidak berubah |
| Koreksi Harga (BR-22/23) | Untuk qty yang masih ada: masuk ke rumus average sebagai "pembelian" dengan harga = selisih. Untuk qty yang sudah terjual: **tidak masuk rumus average**, langsung ke akun Selisih Harga Pembelian |
| Penerimaan Transfer (BR-14) | Masuk ke rumus average lokasi tujuan dengan harga masuk = harga transfer (HPP pengirim saat dikirim) |
| Bonus Supplier (BR-17) | Harga masuk = alokasi proporsional dari harga barang utama dalam faktur yang sama (bukan Rp0) |

---

## 7. Contoh Perhitungan

### Contoh 1 - Pembelian Awal (Gudang Pusat)
| Kondisi | Qty | Average | Nilai |
|---|---|---|---|
| Opening Stock | 100 pcs | Rp10.000 | Rp1.000.000 |
| Pembelian 50 pcs @Rp12.000 | +50 pcs | - | +Rp600.000 |

```
Average Baru = ((100 x 10.000) + (50 x 12.000)) / 150 = Rp10.666,67
```
**Hasil: Qty = 150 pcs, Average = Rp10.666,67**

### Contoh 2 - Penjualan (saat invoice diinput)
Penjualan 30 pcs dari kondisi Contoh 1.
```
HPP = 30 x 10.666,67 = Rp320.000
```
**Hasil: Qty = 120 pcs, Average TETAP Rp10.666,67**

### Contoh 3 - Validasi Stok Minus Ditolak
Dari kondisi Contoh 2 (Qty 120), ada input penjualan 150 pcs.
```
Sistem menolak transaksi: "Qty tidak mencukupi. Tersedia: 120 pcs, diminta: 150 pcs"
```
**Tidak ada transaksi yang diproses. Ini adalah perilaku wajib sesuai BR-05, bukan bug.**

### Contoh 4 - Retur Penjualan (Skenario A)
Dari kondisi Contoh 2 (Qty 120, Average Rp10.666,67), customer meretur 10 pcs dari penjualan tersebut (HPP asal Rp10.666,67/pcs).
```
Average Baru = ((120 x 10.666,67) + (10 x 10.666,67)) / 130 = Rp10.666,67
```
**Hasil: Qty = 130 pcs, Average tetap Rp10.666,67** (karena harga masuk retur = average saat itu juga, hasilnya tidak berubah — akan berbeda jika average sudah berubah sejak penjualan asli terjadi)

### Contoh 5 - Transfer Gudang Pusat ke Cabang (dengan Transit)
Gudang Pusat (Average Rp10.666,67) mengirim 50 pcs ke Cabang A.

**Saat dikirim:**
Gudang Pusat: Qty turun 50 pcs, average tetap Rp10.666,67. Nilai Rp533.333 dipindah ke akun "Barang dalam Perjalanan". Cabang A: **belum ada perubahan** (barang masih berstatus transit).

**Saat diterima Cabang A** (asumsi Cabang A sudah punya stok 40 pcs @Rp9.800):
```
Average Baru Cabang A = ((40 x 9.800) + (50 x 10.666,67)) / 90 = Rp10.148,15
```
**Hasil Cabang A: Qty = 90 pcs, Average = Rp10.148,15**

**Skenario gagal kirim** (Cabang A tutup): qty 50 pcs dan nilai Rp533.333 kembali ke Gudang Pusat. Average Gudang Pusat tidak berubah (BR-15) — cukup qty kembali normal, seolah transfer tidak pernah dikirim.

### Contoh 6 - Koreksi Harga, Barang Sebagian Sudah Terjual (BR-23)
Cabang A membeli 100 pcs @Rp10.000 pada 1 Maret (Average jadi Rp10.000, asumsi stok awal 0). Sepanjang Maret terjual 60 pcs (HPP tercatat Rp10.000/pcs). Sisa qty on hand = 40 pcs.

Pada 15 April, ditemukan bahwa faktur 1 Maret ternyata harusnya Rp10.500/pcs (selisih Rp500/pcs, total selisih Rp50.000 untuk 100 pcs).

**Pembagian koreksi (BR-23):**
- Untuk 40 pcs yang **masih ada**: selisih Rp500 x 40 = Rp20.000 masuk ke rumus average sebagai "pembelian koreksi":
  ```
  Average Baru = ((40 x 10.000) + (40 x 500)) / 40 = Rp10.500
  ```
  (Qty tidak bertambah karena ini murni koreksi nilai, bukan qty baru — average langsung disesuaikan menjadi Rp10.500 untuk 40 pcs yang ada)
- Untuk 60 pcs yang **sudah terjual**: selisih Rp500 x 60 = Rp30.000 dibukukan **langsung ke akun Selisih Harga Pembelian** di Laba Rugi periode April (periode ditemukannya kesalahan) — **HPP penjualan Maret yang sudah tercatat Rp10.000/pcs TIDAK diubah**.

**Ini adalah contoh inti dari prinsip forward-only correction yang menggantikan backdate.**

### Contoh 7 - Bonus Supplier
Faktur: 100 pcs barang utama @Rp10.000 (total Rp1.000.000) + bonus 5 pcs barang sama.
```
Harga efektif per pcs (termasuk bonus) = Rp1.000.000 / 105 = Rp9.523,81
```
**Seluruh 105 pcs masuk dengan harga Rp9.523,81/pcs** (bukan 100 pcs @Rp10.000 + 5 pcs @Rp0), sesuai BR-17.

---

## 8. Dampak ke Database

| Tabel | Field Kunci | Keterangan |
|---|---|---|
| Inventory Balance (per item per lokasi) | `location_id`, `qty_on_hand`, `average_cost`, `total_value` | Satu baris per item per lokasi (Gudang Pusat dihitung sebagai satu lokasi) |
| Inventory Ledger / Stock Card | `transaction_type`, `transaction_ref`, `qty_in/out`, `unit_cost`, `average_before/after`, `transaction_date`, `correction_date`, `is_correction` (flag, BR-12) | `transaction_date` dan `correction_date` dua field terpisah wajib ada |
| Average Cost History | `item_id`, `location_id`, `average_before`, `average_after`, `trigger_type`, `trigger_ref`, `is_correction`, `changed_by`, `changed_at` | |
| Transit Balance (tabel baru) | `item_id`, `from_location_id`, `to_location_id`, `qty`, `value`, `status` (in_transit/received/returned), `shipped_at`, `received_at` | Mendukung BR-13/14/15 |
| Purchase Price Variance Log (tabel baru) | `item_id`, `location_id`, `qty_affected`, `price_difference`, `total_variance`, `correction_ref`, `posted_to_account` | Mendukung BR-23 |
| Claim Receivable (tabel baru) | `item_id`, `qty`, `value`, `supplier_id`, `status` (pending/settled/rejected) | Mendukung BR-19 |
| Approval Threshold Config (tabel baru) | `config_key` (mis. "stock_opname_threshold"), `value`, `updated_by`, `updated_at` | Parameter fleksibel, bisa diubah manajemen kapan saja (Bagian 15) |
| Period Status | `period_id`, `location_id`, `status` (open/closed/locked) | Mendukung BR-08 |
| Journal Header/Detail | Baris jurnal untuk setiap transaksi persediaan, termasuk akun Barang dalam Perjalanan, Selisih Harga Pembelian, Piutang Klaim Supplier | |

> **Tidak ada** tabel "Recalculation Log" atau infrastruktur job queue — tidak diperlukan karena tidak ada backdate.

---

## 9. Dampak ke Laporan

| Laporan | Dampak |
|---|---|
| Stock Card / Kartu Persediaan | Per lokasi, menampilkan `transaction_date` dan `correction_date` terpisah |
| Inventory Valuation | Per lokasi: qty_on_hand x average_cost |
| Laporan Barang dalam Perjalanan (baru) | Rekap barang yang sedang transit antar lokasi, termasuk yang gagal/dibatalkan |
| Laporan Selisih Harga Pembelian (baru) | Rekap seluruh koreksi harga yang mempengaruhi periode berjalan (BR-23) — penting untuk Finance memantau frekuensi & nilai kesalahan input |
| HPP / Laba Rugi | HPP historis tidak pernah berubah; selisih koreksi tampil terpisah di periode berjalan |
| Neraca | Total nilai persediaan seluruh lokasi = akumulasi Inventory Balance per lokasi + saldo Barang dalam Perjalanan |
| Average Cost History Report | Termasuk flag koreksi vs transaksi normal |
| Laporan Piutang Klaim Supplier (baru) | Status klaim per supplier |

---

## 10. Edge Cases

| # | Edge Case | Perlakuan |
|---|---|---|
| EC-01 | Qty menjadi negatif | **Tidak mungkin terjadi** — ditolak di validasi (BR-05) |
| EC-02 | Qty menjadi nol | Average tetap tersimpan sebagai referensi transaksi masuk berikutnya |
| EC-03 | Harga nol tanpa konteks bonus | Ditolak, wajib konfirmasi eksplisit jika memang bonus (BR-17 menangani lewat alokasi, bukan harga Rp0 mentah) |
| EC-04 | Harga negatif | Ditolak |
| EC-05 | Double posting | Idempotency check berbasis nomor referensi transaksi unik |
| EC-06 | Edit transaksi lama | **Tidak diizinkan sama sekali** (BR-06) — selalu lewat mekanisme koreksi Bagian 3.5 |
| EC-07 | Delete transaksi | Tidak diizinkan (hard delete) — gunakan Cancel/Void (BR-09) |
| EC-08 | Barang transit gagal diterima | Kembali ke lokasi asal, average asal tidak berubah (BR-15) |
| EC-09 | Multi user pada item & lokasi sama | Row-level locking wajib (V-09) |
| EC-10 | Koreksi harga menyentuh periode closed/locked | **Tetap diizinkan** karena koreksi bertanggal hari ini, bukan mengedit data di periode closed (bedakan dari edit langsung yang dilarang EC-06) |
| EC-11 | Race condition pengiriman & penerimaan transfer terjadi bersamaan dengan transaksi lain di lokasi yang sama | Row-level locking per lokasi per item |
| EC-12 | Retry posting setelah kegagalan jaringan | Idempotency (EC-05) + transaksi database atomic |

---

## 11. Risiko Bisnis

| Risiko | Penyebab | Dampak | Mitigasi |
|---|---|---|---|
| HPP historis tidak 100% akurat setelah ditemukan kesalahan harga | Prinsip forward-only correction (BR-23) — pilihan sadar demi kesederhanaan | Laporan margin masa lalu tidak direvisi meski ada kesalahan input yang baru ketahuan | Diterima sebagai trade-off yang disengaja; dimitigasi dengan Laporan Selisih Harga Pembelian yang transparan menunjukkan besaran & frekuensi koreksi ke manajemen |
| Bonus supplier tidak dialokasikan dengan benar | Kesalahan input alokasi proporsional | Average understated/overstated | Validasi otomatis: total nilai alokasi harus sama dengan total nilai faktur |
| Barang transit "hilang" antara status kirim dan terima | Tidak ada tindak lanjut konfirmasi penerimaan dalam waktu wajar | Nilai persediaan tersangkut di akun Barang dalam Perjalanan tanpa batas waktu | Laporan Barang dalam Perjalanan menampilkan aging (berapa lama status transit berjalan), dengan alert untuk transit >X hari |
| Approval koreksi disalahgunakan untuk menutupi kehilangan barang | Kontrol approval lemah | Kerugian tersembunyi | Maker-checker wajib (BR-27) + audit log lengkap, approval berjenjang berdasarkan nilai dampak |
| Ambang batas stock opname belum ada nilai default saat go-live | Keputusan nilai ditunda manajemen | Selisih opname kecil-kecil bisa lolos tanpa approval jika default terlalu tinggi, atau membebani operasional jika default terlalu rendah | Rekomendasi: set default konservatif (mis. wajib approval untuk semua selisih di atas Rp0) sampai manajemen menetapkan angka final (Bagian 15) |

---

## 12. Validasi Sistem

| # | Validasi | Wajib/Opsional |
|---|---|---|
| V-01 | Tidak boleh posting transaksi keluar jika qty tidak cukup — **tanpa pengecualian** (BR-05) | MUST HAVE |
| V-02 | Tidak boleh edit/hapus transaksi historis dalam bentuk apa pun — koreksi selalu transaksi baru (BR-06) | MUST HAVE |
| V-03 | Semua perubahan average tercatat ke Average Cost History, termasuk yang berasal dari koreksi | MUST HAVE |
| V-04 | Semua posting bersifat atomic (database transaction) | MUST HAVE |
| V-05 | Harga tidak boleh negatif; harga nol wajib melalui konfirmasi eksplisit bonus | MUST HAVE |
| V-06 | Qty transaksi tidak boleh nol/negatif pada input | MUST HAVE |
| V-07 | Idempotency check berbasis nomor referensi unik | MUST HAVE |
| V-08 | Row-level locking pada Inventory Balance per lokasi per item | MUST HAVE — dikonfirmasi sebagai prasyarat teknis |
| V-09 | Pembulatan average cost: 2 desimal Rupiah, round half up | MUST HAVE |
| V-10 | Maker-checker wajib untuk seluruh transaksi koreksi (BR-27), Stock Adjustment(-), Stock Opname minus, Revaluasi Persediaan | MUST HAVE |
| V-11 | Revaluasi Persediaan hanya bisa dilakukan role Finance HQ | MUST HAVE |
| V-12 | Ambang batas approval Stock Opname harus berupa parameter yang dapat diubah manajemen tanpa perlu deployment ulang (bukan hardcode) | MUST HAVE |
| V-13 | Notifikasi otomatis ke Finance/Accounting setiap ada transaksi Koreksi Harga Pembelian yang menghasilkan entri Selisih Harga Pembelian | SHOULD HAVE |
| V-14 | Alert otomatis untuk barang berstatus "Dalam Perjalanan" lebih dari X hari (nilai X dikonfirmasi kemudian) | SHOULD HAVE |

---

## 13. Acceptance Criteria

**AC-01 — Pembelian Pertama**: Given item belum memiliki stok, When diinput pembelian, Then average = harga pembelian tersebut.

**AC-02 — Penjualan Tidak Mengubah Average**: Given item punya qty & average tertentu, When dilakukan penjualan sebagian, Then average tidak berubah dan HPP = qty terjual × average saat itu.

**AC-03 — Penolakan Stok Minus**: Given qty tersedia kurang dari qty yang diminta, When transaksi keluar diajukan, Then sistem menolak dan menampilkan qty tersedia — **tanpa pengecualian apapun**.

**AC-04 — Tidak Ada Edit Transaksi Lama**: Given ada transaksi yang sudah diposting, When user mencoba mengedit tanggal/qty/harga transaksi tersebut secara langsung, Then sistem menolak dan mengarahkan ke mekanisme Transaksi Koreksi.

**AC-05 — Koreksi Harga dengan Barang Sebagian Terjual**: Given suatu pembelian sebagian sudah terjual, When diajukan Koreksi Harga Pembelian, Then sistem memisahkan otomatis porsi qty yang masih ada (masuk ke average) dari porsi qty yang sudah terjual (masuk ke akun Selisih Harga Pembelian), dan HPP transaksi penjualan yang sudah terjadi tidak berubah.

**AC-06 — Transfer via Status Transit**: Given transfer antar lokasi diajukan, When barang dikirim, Then qty & nilai lokasi asal berkurang dan masuk ke akun Barang dalam Perjalanan, dan average lokasi tujuan **belum berubah** sampai penerimaan dikonfirmasi.

**AC-07 — Average Lokasi Tujuan Saat Penerimaan**: Given barang berstatus Dalam Perjalanan diterima, When konfirmasi penerimaan dilakukan, Then average lokasi tujuan dihitung ulang menggunakan harga transfer sebagai harga masuk.

**AC-08 — Retur Penjualan (Skenario A)**: Given ada penjualan dengan HPP tertentu, When dilakukan retur, Then qty & nilai bertambah dengan harga masuk = HPP transaksi asal, dan average dihitung ulang dengan rumus normal.

**AC-09 — Bonus Supplier Dialokasikan Proporsional**: Given faktur berisi barang utama dan bonus, When diposting, Then harga per unit yang masuk ke average = total nilai faktur ÷ total qty (utama + bonus), bukan Rp0 untuk bonus.

**AC-10 — Maker-Checker pada Koreksi**: Given transaksi koreksi apapun diajukan, When diproses, Then sistem mewajibkan approval sesuai alur berjenjang sebelum average/nilai persediaan berubah.

**AC-11 — Idempotency**: Given transaksi dengan nomor referensi tertentu sudah diposting, When permintaan posting kedua dengan referensi sama diterima, Then sistem menolak tanpa mengubah data.

**AC-12 — Ambang Batas Stock Opname Konfigurabel**: Given manajemen mengubah nilai ambang batas approval stock opname melalui halaman konfigurasi, When perubahan disimpan, Then seluruh transaksi stock opname berikutnya mengikuti ambang batas baru tanpa perlu deployment ulang.

---

## 14. Test Scenario

### A. Normal
1. Pembelian pertama - average = harga beli.
2. Pembelian kedua harga beda - average sesuai rumus.
3. Penjualan sebagian stok - average tidak berubah.
4. Retur pembelian - average tidak berubah, nilai berkurang.
5. Retur penjualan - average dihitung ulang dengan harga masuk = HPP asal (AC-08).
6. Transfer Gudang Pusat ke Cabang - status transit berjalan benar (AC-06, AC-07).
7. Stock Adjustment (+/-) - average sesuai aturan masing-masing arah.
8. Penjualan via WO Service - HPP konsisten dengan Sales biasa.
9. Konversi satuan - average per satuan baru sesuai rasio.
10. Pembelian darurat cabang ke supplier lokal - hanya mempengaruhi average cabang tersebut (BR-16).
11. Bonus supplier - alokasi proporsional benar (AC-09).
12. Replacement supplier - tercatat sebagai retur + pembelian baru terpisah (BR-18).

### B. Boundary
13. Pembelian qty = 1.
14. Penjualan tepat menghabiskan seluruh stok (qty sisa = 0).
15. Average dengan hasil desimal panjang - pembulatan sesuai V-09 konsisten di seluruh modul.
16. Retur qty = seluruh qty yang pernah dibeli.

### C. Error / Negatif
17. Input harga negatif - ditolak.
18. Input qty negatif/nol - ditolak.
19. **Penjualan melebihi stok tersedia - ditolak tanpa pengecualian (AC-03), termasuk untuk role Superadmin sekalipun.**
20. Double posting transaksi sama - hanya terekam sekali (AC-11).
21. Adjustment tanpa reason code - ditolak.
22. **Percobaan edit langsung terhadap transaksi lama (qty/harga/tanggal) - ditolak, diarahkan ke mekanisme koreksi (AC-04).**

### D. Mekanisme Koreksi (Pengganti Backdate)
23. Koreksi qty via Stock Adjustment bertanggal hari ini dengan referensi transaksi asal - berhasil, transaksi asal tidak berubah.
24. Koreksi Harga Pembelian, seluruh qty masih ada di gudang - seluruh selisih masuk ke average (BR-22).
25. Koreksi Harga Pembelian, sebagian qty sudah terjual - pemisahan otomatis ke average (qty sisa) dan Selisih Harga Pembelian (qty terjual) sesuai AC-05.
26. Koreksi Harga Pembelian, seluruh qty sudah terjual (qty sisa = 0) - seluruh selisih masuk ke akun Selisih Harga Pembelian, average tidak berubah sama sekali.
27. Koreksi terhadap transaksi pada periode closed - tetap berhasil karena koreksi bertanggal hari ini (EC-10), bukan mengedit periode closed.
28. Koreksi tanpa approval maker-checker - ditolak/tertahan sampai disetujui (AC-10).
29. Cancel invoice sebelum barang keluar - qty & average kembali seperti semula tanpa jejak.
30. Void invoice setelah barang keluar - diproses sebagai retur (BR-07), bukan sebagai cancel.

### E. Transit & Multi Lokasi
31. Transfer dengan penerimaan berhasil - average tujuan dihitung ulang benar (AC-07).
32. Transfer dengan penerimaan gagal (cabang tutup) - qty & nilai kembali ke asal, average asal tidak berubah (EC-08).
33. Laporan Barang dalam Perjalanan menampilkan status & aging transit dengan benar.
34. Dua transfer bersamaan ke lokasi tujuan yang sama untuk item yang sama - tidak terjadi race condition pada average tujuan (EC-11).
35. Rekonsiliasi: total nilai persediaan seluruh lokasi + saldo Barang dalam Perjalanan = total sesuai jurnal.

### F. Klaim & Piutang Supplier
36. Sparepart Claim - qty keluar, nilai masuk ke Piutang Klaim Supplier, bukan langsung beban.
37. Klaim disetujui supplier - piutang klaim lunas, tidak ada dampak tambahan ke average.
38. Klaim ditolak supplier - piutang klaim dipindah menjadi beban.

### G. Approval & Konfigurasi
39. Perubahan ambang batas approval stock opname oleh manajemen - berlaku untuk transaksi berikutnya tanpa deployment ulang (AC-12).
40. Selisih stock opname di bawah ambang batas - diproses tanpa approval HQ (hanya approval Kepala Cabang).
41. Selisih stock opname di atas ambang batas - wajib approval Finance HQ, transaksi tertahan sampai disetujui.
42. Revaluasi Persediaan diajukan oleh role selain Finance HQ - ditolak (V-11).

### H. Concurrency/Reliability
43. Dua transaksi penjualan pada item & lokasi sama diproses bersamaan - tidak ada qty/average salah akibat race condition.
44. Simulasi kegagalan sistem di tengah posting - tidak ada data tersimpan sebagian (rollback penuh).
45. Retry setelah timeout jaringan - idempotency mencegah duplikasi.

### I. Integrasi Akuntansi
46. Setiap transaksi persediaan menghasilkan jurnal seimbang (debit = kredit).
47. Total nilai persediaan seluruh lokasi sama dengan saldo akun Inventory di jurnal pada tanggal yang sama.
48. Total entri akun Selisih Harga Pembelian pada suatu periode dapat direkonsiliasi dengan daftar Koreksi Harga Pembelian periode tersebut.

---

## 15. Open Items (Bukan Lagi Ambiguitas Kebijakan — Hanya Menunggu Nilai Konfigurasi)

Seluruh keputusan kebijakan bisnis yang sebelumnya berstatus "Perlu Konfirmasi" pada draft v0.1 **sudah final** (lihat Bagian 3). Yang tersisa hanya satu item teknis-operasional yang secara sengaja ditunda oleh manajemen dan **tidak menghalangi development dimulai**:

1. **Nilai default ambang batas approval Stock Opname** (nominal/persentase yang memicu approval Finance HQ, bukan cukup Kepala Cabang). Keputusan desain: dibangun sebagai parameter konfigurasi yang dapat diubah manajemen kapan saja (Approval Threshold Config, Bagian 8), bukan nilai hardcode. **Rekomendasi BA: set default awal ke Rp0 (semua selisih wajib approval HQ) sampai manajemen menetapkan angka final**, agar tidak ada celah kontrol selama masa transisi.

2. Item pelengkap non-blocking yang bisa ditentukan bersamaan development berjalan: nilai ambang hari (X) untuk alert aging Barang dalam Perjalanan (V-14) — bisa mulai dengan default wajar (mis. 3 hari) dan disesuaikan setelah observasi operasional riil.

**Di luar cakupan PRD ini, dicatat sebagai proyek terpisah:** kebijakan penentuan harga jual (pricing engine berbasis tren harga beli per cabang) — akan disusun sebagai PRD tersendiri setelah modul Moving Average ini berjalan.

---

## Catatan Penutup

PRD ini menggantikan seluruh isi draft v0.1. Perubahan paling signifikan dibanding draft awal:
- **Backdate dihilangkan sepenuhnya**, digantikan mekanisme koreksi maju (Bagian 3.5) — menghapus kebutuhan recalculation engine, job queue, dan seluruh edge case terkait cascading recalculation yang ada di draft v0.1.
- **Stok minus dilarang total** tanpa pengecualian — menyederhanakan validasi dan menghilangkan seluruh logika reset average untuk kondisi qty negatif.
- **Barang dalam Perjalanan** ditambahkan sebagai konsep baru untuk transfer antar lokasi.
- Volume transaksi rendah (~200/hari) mengonfirmasi bahwa arsitektur synchronous sederhana sudah memadai — tidak diperlukan infrastruktur asynchronous/queue untuk rilis ini.

Dokumen ini siap diteruskan ke tim Programmer, QA, dan Project Manager sebagai acuan development, code review, testing, dan UAT.
