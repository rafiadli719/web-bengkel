# Panduan Alur: Kirim Barang Pusat ke Cabang & Cabang Pesan Part ke Gudang

Dokumen ini menjelaskan dengan bahasa sederhana bagaimana proses barang berpindah antara **Pusat/Gudang Pusat** dan **Cabang**, mulai dari input sampai barang diterima. Ditulis untuk yang belum paham istilah IT — cukup ikuti urutan langkah dan nama menunya.

Ada **2 jalur** di sistem untuk kebutuhan ini. Keduanya beda tujuan, jangan tertukar:

| | Jalur 1: Pusat Kirim Barang ke Cabang | Jalur 2: Cabang Minta Part ke Gudang |
|---|---|---|
| Siapa yang mulai | Cabang buat pesanan dulu | Cabang buat permintaan dulu |
| Menu utama | **Antar Cabang > Cabang Sendiri (Internal)** | **Antar Cabang > Pengadaan Barang** |
| Cocok untuk | Transfer barang rutin antar cabang, tercatat sebagai transaksi jual-beli internal | Permintaan part/barang yang butuh proses "Purchase Order" resmi antar cabang |
| Efek ke stok | Otomatis bikin PO + harus lanjut input Pembelian (nota) baru stok nambah | Konfirmasi terima langsung, tanpa perlu input nota Pembelian |

Kalau bingung pakai yang mana, pakai **Jalur 1** untuk transfer barang harian antar cabang/gudang. Pakai **Jalur 2** kalau memang mau proses permintaan resmi bergaya "pesan lalu di-ACC lalu dikirim".

---

## Jalur 1: Pusat Kirim Barang ke Cabang (Transfer Antar Cabang)

Menu: **Antar Cabang > Cabang Sendiri (Internal)**

### Langkah 1 — Cabang bikin pesanan
- Yang mengerjakan: **staf cabang** yang butuh barang.
- Menu: **Antar Cabang > Cabang Sendiri (Internal) > Buat Pesanan**
- Nama halaman di layar: *"Buat Pesanan Antar Cabang"*
- Cara pakai: klik **Buat Pesanan Baru**, isi cabang tujuan pengiriman (mis. Pusat/Gudang), pilih barang dan jumlahnya, simpan.
- Hasil: pesanan masuk ke daftar "pesanan masuk" milik cabang/gudang yang dituju.

### Langkah 2 — Pusat proses & kirim barangnya
- Yang mengerjakan: **staf Pusat/Gudang** (pihak yang memegang stok barangnya).
- Menu: **Antar Cabang > Cabang Sendiri (Internal) > Tarik Data (Kirim)**
- Nama halaman di layar: *"Tarik Data & Kirim Barang"*, ada daftar **"Pesanan Masuk"** ke cabang/gudang yang login.
- Cara pakai: buka daftar pesanan yang statusnya **"Belum Diproses"**, cek barang & jumlah yang diminta, proses supaya barang dianggap terkirim.
- Hasil: transaksi jadi "penjualan antar cabang" — status pesanan berubah, barang siap diambil/di-drop ke cabang tujuan.

### Langkah 3 — Cabang terima barang
- Yang mengerjakan: **staf cabang** yang tadi memesan (Langkah 1).
- Menu: **Antar Cabang > Cabang Sendiri (Internal) > Penerimaan**
- Nama halaman di layar: *"Penerimaan Antar Cabang"*
- Cara pakai: cari pesanan dengan status **"Belum Diterima"**, cek fisik barang yang datang cocok dengan yang tercatat, klik **"Proses Terima"**.
- Efek otomatis: sistem langsung membuatkan **PO (Purchase Order) otomatis** untuk transaksi ini — tidak perlu bikin manual.

### Langkah 4 — Cabang input Pembelian (nota) supaya stok resmi bertambah
- Yang mengerjakan: **staf gudang cabang**.
- Menu: **Pembelian > Pembelian (Invoice) > Dari PO**
- Cara pakai: cari PO otomatis dari Langkah 3, cocokkan jumlah barang fisik yang diterima, simpan sebagai transaksi Pembelian.
- Hasil akhir: **stok barang di cabang baru resmi bertambah** setelah langkah ini — bukan otomatis dari langkah "Proses Terima" saja.

**Ringkasan urutan:**
```
Cabang: Buat Pesanan
      -> Pusat: Tarik Data (Kirim)
            -> Cabang: Penerimaan -> Proses Terima (PO otomatis dibuat)
                  -> Cabang: Pembelian > Dari PO -> input nota (stok bertambah)
```

Kalau semua pesanan mau dilihat lintas cabang (siapa pesan apa, statusnya apa), bisa cek di menu **Antar Cabang > Daftar Transaksi**.

---

## Jalur 2: Cabang Pesan Part ke Gudang Pusat (Pengadaan Barang Antar Cabang)

Menu: **Antar Cabang > Pengadaan Barang**

Ada 2 cara mulai: **cabang minta duluan**, atau **pusat kirim duluan tanpa diminta** (misal pusat lihat stok cabang menipis).

### Cara A — Cabang minta duluan

**Langkah 1 — Cabang bikin permintaan**
- Yang mengerjakan: staf cabang.
- Menu: **Antar Cabang > Pengadaan Barang > Buat Permintaan**
- Nama halaman: *"Buat Permintaan Barang"*
- Cara pakai: pilih barang & jumlah yang dibutuhkan, simpan.

**Langkah 2 — Lihat status permintaan (opsional, buat cek)**
- Menu: **Antar Cabang > Pengadaan Barang > Daftar Permintaan**
- Nama halaman: *"Permintaan Antar Cabang"* — daftar semua permintaan dan statusnya.

**Langkah 3 — Pusat proses & konfirmasi kirim**
- Yang mengerjakan: staf Pusat/Gudang.
- Halaman: *"Konfirmasi Pengiriman Barang"* (dibuka dari daftar permintaan, tombol "Proses Kirim").
- Cara pakai: cek barang yang diminta, konfirmasi jumlah yang benar-benar dikirim, simpan.

**Langkah 4 — Cabang konfirmasi terima**
- Yang mengerjakan: staf cabang yang tadi minta.
- Halaman: *"Konfirmasi Penerimaan Barang"*
- Cara pakai: cek fisik barang datang, klik konfirmasi terima.
- Hasil: status permintaan selesai, tidak perlu input nota Pembelian terpisah seperti Jalur 1.

### Cara B — Pusat kirim duluan (tanpa cabang minta)
- Yang mengerjakan: staf Pusat/Gudang.
- Menu/halaman: **"Kirim Barang ke Cabang (Inisiasi Pusat)"**
- Cara pakai: pusat pilih cabang tujuan, pilih barang & jumlah, kirim langsung.
- Lanjutannya sama seperti Cara A Langkah 4 — cabang tujuan tetap harus buka *"Konfirmasi Penerimaan Barang"* untuk menyatakan barang sudah sampai.

**Ringkasan urutan (Cara A, cabang minta duluan):**
```
Cabang: Buat Permintaan
      -> Pusat: Proses Kirim (Konfirmasi Pengiriman Barang)
            -> Cabang: Konfirmasi Terima (Konfirmasi Penerimaan Barang) -> selesai
```

**Ringkasan urutan (Cara B, pusat inisiatif kirim duluan):**
```
Pusat: Kirim Barang ke Cabang (Inisiasi Pusat)
      -> Cabang: Konfirmasi Terima (Konfirmasi Penerimaan Barang) -> selesai
```

---

## Tabel Menu Lengkap (buat cepat cari)

| Langkah | Siapa | Menu | Nama Halaman |
|---|---|---|---|
| Cabang buat pesanan ke pusat | Cabang | Antar Cabang > Cabang Sendiri (Internal) > Buat Pesanan | Buat Pesanan Antar Cabang |
| Pusat proses & kirim | Pusat | Antar Cabang > Cabang Sendiri (Internal) > Tarik Data (Kirim) | Tarik Data & Kirim Barang |
| Cabang terima barang | Cabang | Antar Cabang > Cabang Sendiri (Internal) > Penerimaan | Penerimaan Antar Cabang |
| Cabang input nota biar stok nambah | Cabang | Pembelian > Pembelian (Invoice) > Dari PO | — |
| Lihat semua transaksi antar cabang | Siapa saja (sesuai akses) | Antar Cabang > Daftar Transaksi | — |
| Cabang bikin permintaan barang | Cabang | Antar Cabang > Pengadaan Barang > Buat Permintaan | Buat Permintaan Barang |
| Lihat daftar permintaan | Siapa saja (sesuai akses) | Antar Cabang > Pengadaan Barang > Daftar Permintaan | Permintaan Antar Cabang |
| Pusat konfirmasi kirim | Pusat | (dibuka dari Daftar Permintaan) | Konfirmasi Pengiriman Barang |
| Pusat kirim duluan tanpa diminta | Pusat | (dibuka dari menu Pengadaan Barang) | Kirim Barang ke Cabang (Inisiasi Pusat) |
| Cabang konfirmasi terima | Cabang | (dibuka dari Daftar Permintaan) | Konfirmasi Penerimaan Barang |

---

## Catatan penting

- **Beda paling penting antara Jalur 1 dan Jalur 2:** Jalur 1 butuh langkah tambahan input Pembelian (nota) supaya stok cabang benar-benar bertambah di sistem. Jalur 2 langsung selesai begitu cabang klik konfirmasi terima, tanpa nota terpisah.
- Kalau lupa langkah input nota di Jalur 1, barang secara fisik sudah ada di cabang tapi **stok di sistem belum nambah** — bisa bikin laporan stok salah. Pastikan langkah 4 Jalur 1 selalu dikerjakan.
- Status pesanan/permintaan yang belum diproses biasanya ditandai **"Belum Diproses"** atau **"Belum Diterima"** — cek filter status di tiap halaman kalau daftar kelihatan kosong (defaultnya cuma nampilin yang pending).
