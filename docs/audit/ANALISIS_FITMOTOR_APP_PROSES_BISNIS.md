# Analisis FITMOTOR APP.MDB — Proses Bisnis yang Perlu Diimplementasikan ke Web Base

**Tanggal:** 2026-07-03
**Sumber:** `E:\BENGKEL 2.0\FITMOTOR APP.MDB` (285 query, dibaca literal via DAO) — database ini lebih kaya fitur dibanding `FITMOTOR GABUNG.MDB` yang dianalisis sebelumnya.

---

## 1. Formula HPP — Sekarang Terkonfirmasi Penuh (Koreksi Temuan Sebelumnya)

**Formula asli (literal dari `HRGPOKOK_PENJUALAN`/`HRGPOKOK_SERVICE`):**
```
HPP_final = MAX( MAX(HPP_FIFO, HPP_TBLITEM), ACUAN_PKK )
```

Jadi sistem **memang pakai FIFO** (dugaan awal benar) — tapi ditambah **2 lapis pengaman**: kalau HPP dari FIFO lebih rendah dari harga pokok master barang, atau lebih rendah dari "harga acuan" (`ACUAN_PKK`, kemungkinan harga pokok tertinggi historis buat jaga-jaga), sistem pakai yang **paling tinggi** di antara ketiganya. Temuan "acuan MAX" yang ditemukan sebelumnya di `GABUNG.mdb` adalah bagian dari pengaman ini, bukan pengganti FIFO.

**Kesimpulan:** desain HPP Web Base **harus** FIFO sebagai basis utama, ditambah mekanisme "harga acuan minimum" sebagai pengaman anti-anomali (misal barang yang FIFO cost-nya kebetulan 0 karena data lama rusak).

### Proses "Update HPP" — Workflow Nyata yang Ditemukan

Ada form `FR_HPPSTS_UPDATE` dan `FR_HPPSTS_UPDATE_PILIH` dengan status `BelumUpdateGarap` — staf bisa pilih tanggal/transaksi mana yang mau diproses, dan ada `BATAL_UPDATE_HPP` (bisa dibatalkan). Ini **konfirmasi ulang** temuan sebelumnya: HPP memang diproses **batch semi-manual**, bukan otomatis. Web Base sudah lebih baik (real-time), tapi kalau owner masih mau fitur "review sebelum commit HPP" atau "undo", itu fitur nyata yang pernah ada dan mungkin masih dibutuhkan staf.

### FITUR BARU (belum ada di Web Base): Alarm Perubahan Harga Beli

Query `CEK_PEMBELIAN_NAIK`/`CEK_PEMBELIAN_TURUN` mengklasifikasi barang otomatis ke 7 status seperti "Harga Beli Naik & Harga Jual perlu Naik", "Harga Pokok Turun", dll — sistem **otomatis kasih tahu staf barang mana yang harga jualnya perlu di-review** setiap kali ada pembelian baru dengan harga beda dari sebelumnya. **Ini fitur nyata dan berguna yang tidak ditemukan di Web Base manapun.**

---

## 2. Program "Gratis Cuci Motor" — Jawaban Pasti untuk D1

Ditemukan 2 varian rumus literal:

**Varian 1 (`GRATIS_CUCI_MOTOR`):** dalam 5 hari terakhir, servis dengan kombinasi jasa (Servis Standar, Gurah Mesin, Remap, Oli apapun) terkumpul **>=3 poin** (Remap/Turun Mesin = 3 poin, lainnya = 1 poin) -> dapat voucher cuci gratis berlaku 14 hari.

**Varian 2 (`GRATIS_CUCI_MOTOR_PERIODE`, versi lebih baru):** kombinasi servis standar/oli terkumpul >=2 poin **DAN** total transaksi >= Rp143.000 -> dapat voucher yang sama.

**Ini fitur nyata yang pernah/masih berjalan dan sama sekali belum ada di Web Base.** Perlu ditanyakan ke owner: program ini masih aktif atau sudah dihentikan? Kalau masih aktif, ini prioritas tinggi untuk diimplementasikan karena langsung berdampak ke customer experience harian.

---

## 3. Reminder & Loyalitas Berbasis Riwayat Servis

- **`INFO_GANTI_OLI`** — reminder ganti oli berbasis interval KM (kelipatan 30, offset 60).
- **`INFO_TUNEUP`** — deteksi pelanggan yang sudah 3x+ tune-up tapi belum pernah pakai voucher — kandidat dikasih promo khusus.
- **`SERVIS_STANDAR_VOUCHER`** — mencegah 1 plat nomor pakai diskon "servis standar" lebih dari sekali dalam periode tertentu (anti-abuse voucher).

**Semua ini menjawab pertanyaan terbuka D2** ("reminder servis dihitung dari KM atau tanggal?") — jawabannya: **dari KM**, bukan tanggal, setidaknya untuk ganti oli. Ini juga belum ada padanan aktifnya di Web Base.

---

## 4. Rumus Member Tier ASLI Ditemukan (Beda dari Dugaan Sebelumnya)

**Ini BUKAN query `TIPE_MEMBER` yang dicek sebelumnya di `GABUNG.mdb`** (yang ternyata bukan soal tier) — ini query berbeda, `TIPE_MEMBER_PELANGGAN`, di database yang berbeda:

```
Dikelompokkan per NOMOR TELEPON (bukan per NoPelanggan!)
Total kunjungan >= 6  -> GOLD
Total kunjungan 3-5   -> SILVER
Total kunjungan < 3   -> REGULER
```

**Dua temuan penting:**
1. Tier di sistem asli **cuma 3 level** (Gold/Silver/Reguler), murni dari **jumlah kunjungan**, tidak ada unsur nominal — beda dari rencana `master_kategori_member` Web Base yang 4 level (Bronze/Silver/Gold/Platinum) dengan basis nominal+kunjungan.
2. Sistem asli **mengelompokkan per nomor telepon**, bukan per kode pelanggan — cara ini justru mengakali masalah "1 orang, banyak kode pelanggan" (yang jadi akar duplikasi) dengan menyatukan berdasarkan nomor HP. Ini **konsisten** dengan alasan kenapa nomor WA harus wajib diisi (perbaikan yang baru saja dikerjakan di Web Base).

**Rekomendasi:** perlu keputusan owner — lanjutkan skema 4-tier nominal+kunjungan yang sudah direncanakan, atau kembali ke skema 3-tier kunjungan-murni yang terbukti pernah dipakai? Ini murni keputusan bisnis.

---

## 5. Formula Komisi — Detail Baru Ditemukan

**Pembagian mekanik saat 1 servis dikerjakan beberapa orang** (`MEKANIK_PERSERVIS_PERSEN`):
```
Persen per mekanik = 100 / jumlah_mekanik_yang_kerja
```
**Jawaban pasti untuk pertanyaan B1(b)** ("kalau 2 mekanik kerja 1 motor, dibagi rata atau ada yang lebih?") — **dibagi RATA**, tidak ada logic "yang kerja lebih banyak dapat lebih".

**Struktur insentif lengkap** (`INSENTIF_KARYAWAN`) — komisi dipecah 2 kelompok terpisah: **Jasa** dan **Barang**, masing-masing dihitung per siklus, per mekanik (sampai 4 orang), plus jalur terpisah untuk **Admin** (servis) dan **Admin Penjualan** (beda dari admin servis). Ini strukturnya lebih detail dari yang diasumsikan di gap analysis awal — perlu dicek apakah `tblservice` MySQL (yang sudah punya kolom `persen_admin1/2`, `persen_mekanik1-4`) sudah cukup menampung struktur ini, atau butuh kolom tambahan buat "Admin Penjualan" yang terpisah.

---

## Ringkasan Prioritas Implementasi

| # | Fitur | Status di Web Base | Prioritas |
|---|---|---|---|
| 1 | Alarm perubahan harga beli (naik/turun) | Tidak ada | **Tinggi** — langsung bantu operasional harian gudang/pembelian |
| 2 | Program Gratis Cuci Motor | Tidak ada | **Tinggi, tapi perlu konfirmasi owner dulu** — masih aktif atau tidak |
| 3 | Reminder ganti oli berbasis KM | Tidak ada | Sedang — bagus buat CRM, tapi bukan blocker operasional |
| 4 | Formula HPP dengan pengaman "harga acuan" | Sudah FIFO real-time, tapi pengaman ACUAN_PKK belum ada | Sedang — nice-to-have anti-anomali |
| 5 | Keputusan skema tier (3-level kunjungan vs 4-level nominal+kunjungan) | Sudah direncanakan versi baru | **Perlu keputusan owner sebelum lanjut FSD Membership** |
| 6 | Deteksi abuse voucher servis standar | Tidak ada | Rendah — cuma relevan kalau program voucher itu masih jalan |

**Rekomendasi langkah:** item #1 dan #5 paling berdampak — #1 karena langsung membantu operasional sehari-hari tanpa perlu keputusan bisnis rumit, #5 karena FSD Membership yang sudah ditulis butuh divalidasi ulang terhadap temuan baru ini sebelum owner benar-benar approve.
