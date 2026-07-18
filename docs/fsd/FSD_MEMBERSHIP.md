# Functional Specification Document — Modul Membership

**Versi:** 1.0 Draft
**Tanggal:** 2026-07-03
**Status:** Menunggu approval
**Referensi:** `docs/analysis/ANALISIS_ARSITEKTUR_PELANGGAN_KENDARAAN_ACCESS_TO_MYSQL.md`, `docs/audit/REVERSE_ENGINEERING_ACCESS_FITMOTOR_CUSTOMER_VEHICLE.md` (section 11.3, 11.4), `FSD_CUSTOMER.md`, `FSD_KENDARAAN.md`

**Decision yang mengikat dokumen ini** (final):
- Membership mengikuti Customer, bukan kendaraan. Kalau Customer Gold, semua kendaraannya otomatis dapat benefit Gold.

**Peringatan penting dari hasil reverse engineering:** dugaan awal bahwa `TIPE_MEMBER`/`UPDATE_TIPE_MEMBER` Access adalah kalkulator tier Bronze/Silver/Gold **terbukti salah** setelah SQL diekstrak literal — kedua query itu ternyata tidak menghitung tier sama sekali. `TBLPelangganGrup` (Bengkel/Gold/Silver) juga terbukti diskon Gold/Silver-nya tercatat 0% di Access. Ini berarti **FSD ini kemungkinan besar mendesain kapabilitas yang belum pernah benar-benar berfungsi solid di sistem lama** — bukan migrasi rule existing, melainkan formalisasi rule yang sebelumnya cuma niat/parsial.

---

## 1. Ringkasan & Tujuan

Modul ini mendefinisikan bagaimana tier membership Customer dihitung, benefit apa yang didapat, dan bagaimana perubahan data (WA, kendaraan, kepemilikan) mempengaruhi (atau tidak mempengaruhi) status tier.

## 2. Ruang Lingkup

**In scope:** definisi tier, threshold kenaikan/penurunan, benefit per tier, aturan status saat merge/jual-beli kendaraan.

**Out of scope:** identitas Customer/Kendaraan (FSD terpisah), notifikasi/broadcast promosi member (`FSD_CRM.md`).

## 3. Aktor & Role

| Aktor | Hak Akses |
|---|---|
| Sistem (background job) | Hitung ulang tier tiap Customer secara berkala/event-driven. |
| CS / Kasir | Lihat status member Customer, terapkan diskon sesuai tier saat transaksi. |
| Owner / Admin Pusat | Ubah definisi threshold tier & persentase benefit (`master_kategori_member`). |

## 4. Glosarium

| Istilah | Arti |
|---|---|
| Tier | Level membership (Bronze/Silver/Gold/Platinum). |
| Floor (Lantai Tier) | Aturan tier tidak pernah turun otomatis di bawah level yang sudah pernah dicapai lewat proses tertentu (mis. merge) — hanya naik atau tetap, kecuali re-evaluasi periodik eksplisit menyatakan turun. |
| Basis Perhitungan | Sumber data dipakai hitung tier — di FSD ini: `statistik_pelanggan` (nominal & kunjungan), BUKAN data kendaraan. |

## 5. Model Data

### 5.1 Tabel Existing (dipertahankan, jadi basis tunggal)

`master_kategori_member` — sudah ada di MySQL: `id_kategori`, `nama_kategori`, `tipe_kategori` (nominal/kunjungan), `min_value`, `max_value`, `diskon_persen`, `diskon_jasa`, `diskon_barang`, `benefit_text`, `is_active`.

`statistik_pelanggan` — sudah ada: `total_nominal`, `jumlah_kunjungan`, `status_member`, `diskon_persen`, `kategori_member_kunjungan`.

### 5.2 Perubahan yang Direkomendasikan

**Konsolidasi `tblpelanggangrup` -> deprecated untuk fungsi diskon.** Berdasar temuan 11.4 (diskon Gold/Silver Access = 0%, cuma "Bengkel" 5% yang aktif), `tblpelanggangrup`/`kgrup` **direkomendasikan dipertahankan HANYA untuk kategori non-loyalitas** (mis. "Bengkel" = rekanan/internal dengan diskon tetap 5%, bukan tier loyalitas pelanggan retail). Field `kgrup` di `tblpelanggan` tetap ada untuk kompatibilitas, tapi kalkulasi benefit member **sepenuhnya** dari `master_kategori_member` + `statistik_pelanggan`.

**Tabel baru: `member_tier_history`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `nopelanggan` | VARCHAR(20) FK | |
| `tier_lama` | VARCHAR(50) | |
| `tier_baru` | VARCHAR(50) | |
| `pemicu` | ENUM('transaksi','recalc_berkala','merge_customer','koreksi_manual') | |
| `tanggal` | TIMESTAMP | |

**Tabel baru: `siklus_komisi`** (dari temuan section 11.5 reverse engineering — jawaban B3)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `kd_cabang` | VARCHAR(10) FK -> tbcabang | |
| `nama_siklus` | VARCHAR(50) | input manual admin, mis. "Siklus Juli 2026 A" |
| `tanggal_awal` | DATE | |
| `tanggal_akhir` | DATE | |
| `dibuat_oleh` | INT FK -> tbuser_karyawan | |

*(catatan: `siklus_komisi` relevan untuk modul Komisi, dicantumkan di sini karena ditemukan bersamaan saat reverse engineering membership — akan direferensikan silang dari FSD Komisi/Servis Advisor yang belum dibuat.)*

## 6. Functional Requirements

### FR-01 — Kalkulasi Tier Otomatis
**Deskripsi:** Tier Customer dihitung dari `statistik_pelanggan.total_nominal` DAN/ATAU `jumlah_kunjungan` (sesuai `tipe_kategori` tiap baris `master_kategori_member` — nominal atau kunjungan, ambil yang lebih menguntungkan Customer).
**Trigger:** setiap kali `statistik_pelanggan` di-refresh (servis lunas) — real-time, BUKAN batch manual seperti Access (`UPDATE_TIPE_MEMBER` yang ternyata bukan kalkulator tier sama sekali — lihat catatan di kepala dokumen).
**Business rule:** basis kalkulasi **murni dari Customer** (`statistik_pelanggan`), tidak pernah membaca tabel kendaraan.
**Output:** `statistik_pelanggan.status_member` terupdate, insert 1 baris `member_tier_history` kalau tier berubah.

### FR-02 — Floor Rule (Tidak Pernah Turun Otomatis)
**Deskripsi:** Tier yang sudah dicapai Customer tidak turun otomatis hanya karena omzet periode berjalan menurun (mis. kendaraan lama dijual mengurangi transaksi masa depan, TAPI histori transaksi lama tetap dihitung — lihat FR-05 di `FSD_KENDARAAN.md`, statistik pelanggan lama tidak berkurang saat kendaraan lepas).
**Business rule:** FR-01 hanya boleh **menaikkan** tier otomatis. Penurunan tier (kalau kebijakan bisnis memang menghendaki reset periodik, mis. tahunan) harus lewat proses `recalc_berkala` eksplisit yang terpisah dan wajib disetujui Owner — bukan efek samping harian dari FR-01.

### FR-03 — Benefit Otomatis ke Semua Kendaraan Customer (Decision #3)
**Deskripsi:** Saat transaksi servis untuk kendaraan manapun milik Customer Gold, diskon/benefit tier Gold otomatis berlaku — TIDAK perlu kendaraan spesifik "didaftarkan" sebagai Gold.
**Implementasi:** lookup benefit dilakukan dari `nopelanggan` (via `kepemilikan_kendaraan.is_current` kendaraan yang sedang diservis -> dapat `nopelanggan` -> lookup `statistik_pelanggan.status_member` -> lookup `master_kategori_member` untuk persentase diskon), bukan dari field manapun di `tblkendaraan`.

### FR-04 — Efek Ganti WA/Alamat terhadap Tier (Kasus 4, Membership)
**Deskripsi:** Tier **tidak terpengaruh** oleh perubahan kontak/atribut Customer selama identity (`nopelanggan`) tidak berubah.
**Business rule:** karena FR-03 di `FSD_CUSTOMER.md` menjamin ganti WA adalah UPDATE pada `nopelanggan` yang sama (bukan record baru), FR-01 di modul ini otomatis tetap konsisten — **tidak butuh logic tambahan**, ini adalah manfaat langsung dari desain modul Customer yang benar.
**Regresi yang harus dicegah:** temuan 11.2 menunjukkan Access sendiri (`UPDATE_TIPE_MEMBER`) match wajib Nama+Telepon exact — desain Web Base **wajib berbeda** dari pola itu; jangan pernah pakai kombinasi nama+telepon sebagai kunci re-lookup status member.

### FR-05 — Efek Tambah Kendaraan terhadap Tier
**Deskripsi:** Tambah kendaraan baru ke Customer existing tidak langsung mengubah tier (tier baru naik lewat transaksi/omzet, bukan lewat jumlah kendaraan), tapi transaksi dari kendaraan baru tsb ikut terakumulasi ke `statistik_pelanggan` yang sama -> bisa mempercepat kenaikan tier lewat FR-01 secara alami.

### FR-06 — Efek Jual Kendaraan terhadap Tier
**Deskripsi:** Menjual salah satu kendaraan **tidak menurunkan** tier Customer (lama). Merujuk BR-KEND (FSD Kendaraan): statistik transaksi historis tetap milik pemilik lama.
**Business rule:** FR-01 hanya re-evaluasi dari `statistik_pelanggan` yang tidak berkurang oleh event jual-beli kendaraan (event itu hanya mengubah `kepemilikan_kendaraan`, tidak menyentuh `statistik_pelanggan`).

### FR-07 — Efek Merge Customer terhadap Tier
**Deskripsi:** Saat 2 Customer di-merge (`FSD_CUSTOMER.md` FR-05), tier hasil merge = tier **tertinggi** di antara keduanya (floor rule diperluas ke skenario merge, bukan cuma penurunan alami).
**Business rule:** setelah `statistik_pelanggan` target direbuild (gabungan agregat kedua Customer), jalankan FR-01 ulang — tapi tambahkan pengecekan: kalau hasil FR-01 dari agregat gabungan < tier tertinggi sebelumnya (kasus jarang, misal aturan tier berubah), pakai yang lebih tinggi (`GREATEST`), insert `member_tier_history` (`pemicu='merge_customer'`).

## 7. Business Rules Konsolidasi

| Kode | Aturan |
|---|---|
| BR-MBR-01 | Tier dihitung murni dari `nopelanggan` (`statistik_pelanggan`) — tidak pernah dari `tblkendaraan`/`id_kendaraan` manapun. |
| BR-MBR-02 | Tier tidak pernah turun otomatis harian — hanya naik (FR-01), penurunan cuma lewat `recalc_berkala` eksplisit yang disetujui Owner. |
| BR-MBR-03 | Merge Customer mengambil tier tertinggi (FR-07), tidak pernah menurunkan benefit gabungan. |
| BR-MBR-04 | Identity lookup untuk membership TIDAK BOLEH pakai kombinasi nama+telepon (pola Access yang terbukti rapuh) — selalu pakai `nopelanggan`. |
| BR-MBR-05 | `tblpelanggangrup`/`kgrup` tidak dipakai untuk kalkulasi diskon member — hanya untuk kategori non-loyalitas (rekanan/internal). |

## 8. Alur Utama

```
Servis lunas --> statistik_pelanggan refresh (nominal/kunjungan naik)
       |
       v
FR-01 recalc tier --> naik? --> insert member_tier_history, update status_member
       |
       v
Transaksi berikutnya (kendaraan manapun milik Customer ini)
       --> FR-03 lookup nopelanggan --> terapkan diskon tier otomatis
```

## 9. Edge Case Handling

| Edge Case | Penanganan |
|---|---|
| Customer Gold ganti WA -> tetap Gold? | FR-04 — ya, karena identity tidak berubah |
| Customer Gold tambah kendaraan baru -> ikut Gold? | FR-03 — ya, otomatis, lookup by `nopelanggan` |
| Customer Gold jual 1 kendaraan -> status tetap? | FR-06 — ya, statistik historis tidak berkurang |
| Merge 2 Customer beda tier | FR-07 — ambil tertinggi |
| Threshold tier berubah kebijakan (mis. naik dari Rp X ke Rp Y) | Di luar FR-01 harian — perubahan `master_kategori_member.min_value` oleh Owner, efeknya baru kelihatan di recalc berikutnya, bukan retroaktif otomatis kecuali dijalankan `recalc_berkala` manual |

## 10. Non-Functional Requirements

- FR-01 dipicu oleh event (servis lunas) — tidak boleh full table scan 37rb+ Customer tiap kali; hanya re-evaluasi 1 `nopelanggan` yang terlibat transaksi.
- `recalc_berkala` (kalau diaktifkan kebijakan Owner) berjalan sebagai batch job terjadwal, di luar jam sibuk.

## 11. Dependency Antar Modul

- `FSD_CUSTOMER.md` — basis identity (`nopelanggan`) dan efek merge (FR-07).
- `FSD_KENDARAAN.md` — sumber transaksi yang mengalir ke `statistik_pelanggan`, dan aturan "statistik tidak berkurang saat kendaraan dijual".
- `FSD_CRM.md` — badge/status member ditampilkan di dashboard Customer 360.
- Modul Komisi/Servis Advisor (FSD terpisah, belum dibuat) — `siklus_komisi` yang didefinisikan di sini dipakai basis periode pembayaran komisi.

## 12. Kriteria Penerimaan

1. Ganti WA Customer Gold tidak mengubah `status_member` sama sekali.
2. Tambah kendaraan baru untuk Customer Gold, transaksi kendaraan baru langsung dapat diskon Gold tanpa setup tambahan apapun.
3. Jual 1 dari 3 kendaraan Customer Gold tidak menurunkan `status_member`.
4. Merge Customer Bronze ke Customer Gold menghasilkan status akhir Gold, bukan rata-rata atau Bronze.
5. Tidak ada satupun query kalkulasi tier yang JOIN berdasarkan nama+telepon sebagai kunci pencarian identity.

## 13. Open Items — Butuh Keputusan Sebelum Implementasi

| # | Pertanyaan | Kenapa Penting |
|---|---|---|
| O1 | Apakah tier memang TIDAK PERNAH turun (lifetime achievement) atau ada kebijakan reset tahunan? | Menentukan apakah `recalc_berkala` (FR-02) perlu dibangun sekarang atau nanti |
| O2 | Apakah "Bengkel" (kgrup 001, diskon 5%) di data Access masih relevan sebagai kategori di Web Base, dan siapa yang termasuk kategori ini? | Menentukan scope migrasi `tblpelanggangrup` |
| O3 | Threshold nominal/kunjungan `master_kategori_member` saat ini — apakah sudah final/disetujui owner, atau masih draft yang perlu di-review ulang mengingat temuan bahwa versi Access-nya (kalau ada) tidak pernah ditemukan formulanya? | Risiko: threshold yang dipakai sekarang mungkin cuma tebakan tim dev, bukan kebijakan bisnis yang divalidasi |
