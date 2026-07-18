# GAP ANALYSIS — JAWABAN DITEMUKAN DARI SISTEM
## Bengkel 2.0 Web Migration | Diekstrak dari: DB + Kodebase PHP
### Tanggal Dibuat: 2026-06-27
### Metode: Reverse engineering database `fitmotor_dbbengkel` + analisa kode PHP

---

> **Cara baca dokumen ini:**
> - ✅ = Jawaban PASTI ditemukan dari sistem
> - ⚠️ = Ditemukan tapi perlu konfirmasi karena ada kemungkinan berbeda dengan praktik
> - ❓ = Tidak ditemukan, harus ditanya ke owner

---

## BLOK A — ALUR SERVIS

### A1. Urutan Status Servis ✅

Ditemukan di `tblservice.status_servis` (enum):

```
datang → diproses → selesai → bayar
```

Field `cancel` ada di enum tapi **0 data aktual** yang menggunakan status ini (dari 103.540 transaksi servis).

Tidak ada status `diambil` — motor diambil customer tidak tercatat di sistem saat ini.

**Sumber:** `INFORMATION_SCHEMA.COLUMNS` + `SELECT DISTINCT status_servis FROM tblservice`

---

### A2. Cancel Servis — Mekanisme yang Sudah Disiapkan ✅

Sistem sudah menyiapkan 2 layer untuk cancel:

**Layer 1 — tblservice** (field langsung):
- `status_servis = 'cancel'`
- `tanggal_cancel` (datetime)
- `alasan_cancel` (text)
- `biaya_cancel` (double, default 0)
- `cancel_note` (text)
- `user_cancel` (varchar)

**Layer 2 — tb_log_cancel_servis** (log detail):

| Field | Tipe | Nilai |
|---|---|---|
| `kategori_alasan` | enum | customer_request, no_stock, no_mekanik, customer_no_show, lainnya |
| `status_barang` | enum | **belum_diambil, sudah_diambil, dikembalikan** |
| `status_pembayaran` | enum | **belum_bayar, dp_dibayar, lunas, refund** |
| `nominal_dp` | double | Nominal DP yang sudah dibayar |
| `nominal_refund` | double | Nominal yang dikembalikan ke customer |

**Kesimpulan:** Sistem sudah menyiapkan alur **refund + kembalikan barang** (opsi A dari pertanyaan C-05). Belum pernah dipakai — 0 record di tb_log_cancel_servis.

> ⚠️ **Yang perlu dikonfirmasi ke owner:** Siapa yang approve cancel? Apakah ada batasan tidak bisa cancel setelah motor dikerjakan?

---

### A3. Garansi Servis — Berdasarkan Level Member ✅

**Dari WA template** (`class_whatsapp_automation.php`):
> "Service Anda bergaransi **30 hari atau 1000 KM** (mana yang tercapai lebih dulu)"

**Dari master_kategori_member** — durasi bervariasi per level:

| Level Member | Masa Garansi |
|---|---|
| Bronze | 0 hari |
| Silver | 7 hari |
| Gold | 11 hari |
| Platinum | 14 hari |

> ⚠️ **Ada ketidaksesuaian:** WA template bilang 30 hari, tapi master member bilang 7–14 hari. Yang mana yang dipakai? Perlu konfirmasi.

**Sumber:** `class_whatsapp_automation.php`, `master_kategori_member`

---

### A4. Laporan Omset — Dibaca dari Tanggal Masuk ⚠️

Dari kode `lap_servis.php`:
```sql
WHERE vs.tanggal >= '$tglmulai' AND vs.tanggal <= '$tglselesai'
```

**Kesimpulan:** Laporan servis menggunakan **tanggal motor masuk** (`tblservice.tanggal`), **bukan tanggal bayar**. Tidak ada filter status — semua status (datang, diproses, selesai, bayar) masuk ke laporan.

> ⚠️ **Risiko:** Servis yang belum lunas ikut terhitung sebagai omset. Apakah ini yang dimaksud owner?

**Sumber:** `lap_servis.php` baris 56–58, 84–87, 106–109

---

## BLOK B — KOMISI & INSENTIF

### B1. Formula Komisi — Dua Versi Ditemukan ⚠️

**Versi 1 — tbbagi_hasil** (kemungkinan sync dari Access):

| Posisi | Kategori | Persen |
|---|---|---|
| ADMIN | BARANG | 10% |
| ADMIN | JASA | 6% |
| MEKANIK | BARANG | 15% |
| MEKANIK | JASA | 30% |

**Versi 2 — tbpersen_insentif** (setting web):

| Posisi | Persen Barang | Persen Jasa |
|---|---|---|
| ADMIN KASIR | 10% | 10% |
| MEKANIK | 15% | 35% |
| PENGADAAN | 10% | 10% |
| SERVIS ADVISOR | 15% | 35% |

> ❓ **Perlu dikonfirmasi owner:** Mana yang berlaku? tbbagi_hasil (30% jasa) atau tbpersen_insentif (35% jasa)?

---

### B2. "Admin" dalam Komisi = Beberapa Pihak ✅ (parsial)

Dari `tblservice`, ditemukan kolom yang memisahkan peran secara jelas:

- `kepala_mekanik1`, `kepala_mekanik2` → `persen_kepala_mekanik1`, `persen_kepala_mekanik2`
- `admin1`, `admin2` → `persen_admin1`, `persen_admin2`
- `mekanik1`–`mekanik4` → `persen_mekanik1`–`persen_mekanik4`

**Kesimpulan:** Sistem memisahkan 3 peran: kepala mekanik, admin, dan mekanik reguler. Masing-masing punya persentase sendiri per transaksi.

> ❓ **Yang belum jelas:** "Admin" di tbpersen_insentif = service advisor atau admin kantor?

---

### B3. Persentase Per Cabang ⚠️

`tbbagi_hasil` hanya punya 4 baris tanpa kolom cabang. Artinya saat ini **satu persentase berlaku untuk semua cabang**.

> ❓ **Perlu dikonfirmasi:** Apakah memang sama semua cabang, atau di Access dulu berbeda?

---

### B4. "Siklus" Komisi ⚠️

Tabel `tbsiklus` EXISTS tapi **kosong** (0 data). Struktur ada, belum diisi.

> ❓ **Perlu dikonfirmasi:** Periode pembayaran komisi — mingguan, bulanan, atau per siklus?

---

### B5. Service Advisor Insentif ✅

`tbpersen_insentif` memiliki entry: **SERVIS ADVISOR → 15% barang, 35% jasa**.

Advisor sudah diakui sebagai penerima insentif terpisah di konfigurasi web.

---

## BLOK C — STOK & HPP

### C1. Metode HPP ⚠️

`tblservis_barang` **tidak menyimpan kolom hpp** per item servis. Laba item tidak dihitung di level transaksi servis.

Dari kode `cari_item_pembelian_rst.php`, HPP yang ditampilkan adalah **harga beli terakhir** (last purchase price).

> ⚠️ **Perlu konfirmasi:** Metode HPP resmi yang ingin dipakai (last price / average / FIFO)?

---

### C2. Harga Jual Sparepart di Servis ✅

`tblservis_barang` memiliki kolom:
- `harga_jual` — harga yang dipakai saat transaksi
- `diskon_source` — sumber diskon (member, promo, manual, dll)
- `diskon_persen` — persentase diskon yang diterapkan
- `id_promo` — link ke master_diskon_periode

Sistem mendukung **diskon per item** berbasis promo periode atau level member.

> ⚠️ **Perlu konfirmasi:** Default harga yang diambil dari tblitem (HargaJual1, 2, atau 3)?

---

### C3. Stok Minimum Alert ✅

`tblitem_stok` memiliki `stokmin` dan `stok_maks` per item per cabang. Konfigurasi stok minimum sudah ada di database.

> ❓ **Perlu konfirmasi:** Apakah sudah ada notifikasi otomatis saat stok di bawah minimum?

---

## BLOK D — PROGRAM BISNIS

### D1. Program Member ✅

**ADA.** Empat level dengan dua tipe penentuan (total nominal transaksi ATAU jumlah kunjungan):

| Level | Nominal Total | Jumlah Kunjungan | Diskon Jasa | Benefit Tambahan |
|---|---|---|---|---|
| Bronze | Rp0 – 1.999.999 | 0 – 4x | 0% | Akses semua layanan |
| Silver | Rp2.000.000 – 4.999.999 | 5 – 9x | 10% | + Prioritas antrian |
| Gold | Rp5.000.000 – 9.999.999 | 10 – 19x | 15% | + **Gratis cuci motor** |
| Platinum | Rp10.000.000+ | 20x+ | 20% | + **Gratis cuci motor & oli** + **Jemput antar gratis** |

**Sumber:** `master_kategori_member`

---

### D2. Gratis Cuci Motor ✅

**ADA.** Otomatis dari benefit level member:
- **Gold** ke atas → gratis cuci motor
- **Platinum** → gratis cuci motor & oli + jemput antar gratis

> ⚠️ **Perlu konfirmasi:** Berapa kali per bulan/per kunjungan?

---

### D3. Reminder Servis via WA ✅ (parsial)

Sistem punya `class_whatsapp_automation.php` yang mengirim notifikasi WA setelah servis selesai. Tabel `gabung_rekap_konsumen_wa` menyimpan rekap untuk follow-up.

> ❓ **Perlu konfirmasi:** Berapa hari/km sebelum jadwal servis berikutnya untuk dikirim reminder?

---

## BLOK E — KONTROL & KEUANGAN

### E1. Diskon Approval ⚠️

`tblservis_barang` punya `diskon_source` (member/promo/manual) dan `master_diskon_periode` untuk promo terjadwal. Tidak ditemukan tabel `approval_diskon` atau batas nominal approval.

> ❓ **Perlu dikonfirmasi:** Siapa yang boleh beri diskon manual? Perlu approval di atas nominal tertentu?

---

### E2. PPN / Pajak Servis ✅

Dari 103.540 data servis aktual: **0 transaksi yang punya ppn_persen > 0**.

**Kesimpulan: Saat ini TIDAK ada PPN yang dikenakan pada servis.**

`tbtipe_pajak` hanya berisi 2 baris terkait PPh karyawan (bukan PPN transaksi pelanggan).

---

### E3. Piutang Customer ✅ (infrastruktur ada)

Tabel `tblpiutang_header` dan `tblpiutang_detail` sudah ada. Modul piutang sudah disiapkan secara database.

> ❓ **Perlu dikonfirmasi:** Customer mana yang boleh hutang servis? Siapa yang approve?

---

### E4. Rekonsiliasi Antar Cabang ✅ (parsial)

Tabel `gabung_rekonsil_antarcabang` dan view `view_transaksi_gabungan` sudah ada. Infrastruktur rekonsiliasi sudah disiapkan.

> ❓ **Perlu dikonfirmasi:** Siapa yang bertanggung jawab jika ada selisih antar cabang?

---

## BLOK F — OPERASIONAL

### F1. Tarif Jemput Antar ✅ LENGKAP

Sistem menggunakan **tarif range berbasis jarak** dengan dua kondisi motor:

**Motor JALAN (bisa dikendarai):**

| Range Jarak | Tarif Dasar | Tarif Tambahan |
|---|---|---|
| 0 – 1.0 km | **GRATIS** | Rp0 |
| 1.1 – 5.0 km | Rp 8.000 | + Rp 2.000 / km |
| 5.1 – 10.0 km | Rp 16.000 | + Rp 3.000 / km |
| > 10.1 km | Rp 31.000 | + Rp 4.000 / km |

**Motor MOGOK (tidak bisa jalan):**

| Range Jarak | Tarif Dasar | Tarif Tambahan |
|---|---|---|
| 0 – 1.0 km | **GRATIS** | Rp0 |
| 1.1 – 5.0 km | Rp 12.000 | + Rp 3.000 / km |
| 5.1 – 10.0 km | Rp 24.000 | + Rp 4.000 / km |
| > 10.1 km | Rp 44.000 | + Rp 5.000 / km |

Sumber: `master_tarif_jemput_range`, logika di `functions/tarif_jemput_helper.php`

---

### F2. Cabang yang Terdaftar ✅

| Kode | Nama Cabang | Tipe |
|---|---|---|
| CIKDITIRO | FIT MOTOR CIKDITIRO | 1 |
| PACUL | FIT MOTOR PACUL | 1 |
| PESALAKAN | FIT MOTOR ADIWERNA | 1 |
| PST | FIT MOTOR PUSAT | 1 |
| TRAYEMAN | FIT MOTOR TRAYEMAN | **2** |

> ⚠️ **Catatan:** TRAYEMAN memiliki `tipe_cabang = 2` — berbeda dari 4 cabang lainnya. Artinya apa secara bisnis?

---

### F3. Mekanik Lintas Cabang ✅

`tblmekanik` **tidak memiliki kolom `kd_cabang`**. Secara database, mekanik tidak terikat ke satu cabang.

---

### F4. Split Payment ⚠️

`tbjenis_bayar` mendukung: TUNAI, DEBIT CARD, CREDIT CARD, TRANSFER.

Tapi `tblservice.metode_pembayaran` adalah **varchar(50) satu field** — hanya satu metode per transaksi. Dari 103.540 data aktual, **100% menggunakan "Tunai"**.

> ❓ **Perlu konfirmasi:** Apakah ada rencana split payment? Saat ini belum tersedia secara teknis.

---

### F5. DP / Uang Muka Servis ⚠️

`tblservice` **tidak memiliki kolom DP**. Tapi `tb_log_cancel_servis` memiliki field `nominal_dp` (artinya konsep DP sudah dipikirkan dalam skenario cancel).

> ❓ **Perlu dikonfirmasi:** Apakah ada mekanisme DP? Jika iya, perlu ditambahkan kolom di tblservice.

---

### F6. Motor Tidak Diambil ❓

Tidak ditemukan kolom `tanggal_diambil` atau status `diambil` di tblservice. Alur motor diambil tidak tercatat di sistem.

> ❓ **Perlu dikonfirmasi:** Apakah ada biaya parkir/titip? Siapa yang kirim notifikasi dan berapa hari?

---

## RINGKASAN STATUS SETIAP PERTANYAAN

| No | Pertanyaan (ringkas) | Status |
|---|---|---|
| 1 | Stok kembali jika batal sebelum bayar? | ⚠️ Sistem siapkan, aturan belum jelas |
| 2 | Cancel setelah LUNAS → prosedur? | ✅ Sistem siapkan refund + kembalikan barang |
| 3 | Sparepart sudah pasang, batal → stok? | ❓ Tidak ada di sistem |
| 4 | Formula komisi masih berlaku? | ⚠️ Ada 2 tabel berbeda (30% vs 35% jasa) |
| 5 | Komisi berubah jika invoice direvisi? | ❓ Tidak ada mekanisme snapshot |
| 6 | 2 mekanik → komisi dibagi rata? | ⚠️ Ada kolom persen per mekanik, bisa beda |
| 7 | "Admin" dalam formula = siapa? | ⚠️ Ada kolom admin1/admin2 terpisah |
| 8 | Outsource dalam formula = apa? | ❓ Tidak ada kolom outsource di servis |
| 9 | Persentase beda per cabang? | ⚠️ Saat ini 1 global di tbbagi_hasil |
| 10 | Siklus komisi = berapa lama? | ❓ tbsiklus kosong |
| 11 | Service advisor dapat insentif? | ✅ ADA (15% barang, 35% jasa) |
| 12 | Mekanik dapat komisi servis garansi? | ❓ Tidak ada flag garansi di tblservice |
| 13 | Omset dari tanggal masuk atau bayar? | ⚠️ Kode pakai tanggal masuk, semua status masuk |
| 14 | Servis & penjualan digabung di laporan? | ❓ Belum dicek lap_harian |
| 15 | HPP: FIFO / avg / last? | ⚠️ Kode menunjukkan last price |
| 16 | Harga tier berapa untuk servis? | ⚠️ Ada diskon_source per item, tier belum clear |
| 17 | Program member ada? | ✅ ADA — 4 level Bronze/Silver/Gold/Platinum |
| 18 | Gratis cuci motor masih aktif? | ✅ ADA — Gold ke atas, Platinum + gratis oli |
| 19 | Customer boleh hutang servis? | ⚠️ Infrastruktur piutang ada, aturan belum |
| 20 | Ada mekanisme DP? | ⚠️ Tidak ada kolom DP di tblservice |
| 21 | Siapa yang boleh beri diskon? | ❓ Tidak ada approval system |
| 22 | PPN pada servis? | ✅ TIDAK ADA — 0 dari 103.540 servis punya PPN |
| 23 | Rekonsiliasi antar cabang siapa? | ⚠️ Infrastruktur ada, SOP belum |
| 24 | Tarif jemput antar dihitung apa? | ✅ Per km berbasis range, 1 km pertama gratis |
| 25 | Motor tidak diambil → SOP? | ❓ Tidak ada di sistem |
| 26 | Split payment tersedia? | ⚠️ Jenis bayar ada, tapi satu field per transaksi |

---

## PERTANYAAN YANG MASIH HARUS DITANYA KE OWNER

Setelah eliminasi dari analisa sistem, ini yang tersisa murni kebijakan bisnis:

1. Siapa yang **approve cancel servis**? (terutama setelah mekanik mulai kerja)
2. Apakah **komisi berubah** jika invoice direvisi setelah selesai? (snapshot atau real-time)
3. Mana yang berlaku: **tbbagi_hasil (30% jasa) atau tbpersen_insentif (35% jasa)?**
4. **Siklus komisi** = berapa lama? (mingguan/bulanan/per siklus)
5. Motor tidak diambil → ada **biaya parkir**? Notifikasi setelah berapa hari?
6. **Garansi**: WA template 30 hari vs master_member 7–14 hari — yang berlaku?
7. **Omset laporan**: tanggal masuk (sekarang) atau ganti ke tanggal bayar?
8. **Customer hutang servis**: siapa yang approve dan batas maksimal?
9. **Siapa yang boleh beri diskon manual?** Perlu approval di atas nominal berapa?
10. **TRAYEMAN tipe_cabang=2** — artinya berbeda perlakuan bisnis apa?
11. Mekanik servis garansi → **tetap dapat komisi atau tidak?**
12. Jika 2 mekanik, **dibagi rata atau ada yang dapat lebih**?

---

*Dokumen ini dibuat oleh: System Analyst — Reverse Engineering Database*
*Database: fitmotor_dbbengkel (103.540 transaksi servis)*
*Kodebase: /app/ + functions/*
*Versi: 1.0 | 2026-06-27*
