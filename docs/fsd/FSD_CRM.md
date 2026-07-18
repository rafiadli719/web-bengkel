# Functional Specification Document — Modul CRM

**Versi:** 1.1 Draft Revisi
**Tanggal:** 2026-07-11
**Status:** Menunggu approval
**Referensi:** `docs/analysis/ANALISIS_ARSITEKTUR_PELANGGAN_KENDARAAN_ACCESS_TO_MYSQL.md`, `docs/audit/REVERSE_ENGINEERING_ACCESS_FITMOTOR_CUSTOMER_VEHICLE.md`, `FSD_CUSTOMER.md`, `FSD_KENDARAAN.md`, `FSD_MEMBERSHIP.md`, `FSD_SERVIS.md`

---

## 1. Ringkasan & Tujuan

Modul ini mendefinisikan dashboard Customer 360, mekanisme tiket internal lintas modul, dan fitur komunikasi pelanggan.

**Revisi penting 2026-07-11:** `tbl_issue` **tidak lagi** menjadi gerbang eksekusi utama untuk operasi yang **sudah punya alur approval sendiri** di modul lain. `tbl_issue` difokuskan ulang sebagai:
- rumah untuk laporan masalah ad hoc terstruktur dari user,
- sarana visibility/SLA lintas modul,
- titik approval untuk gap operasional yang belum punya tabel/alur khusus.

Contoh paling penting: **Revisi Komisi Servis Pasca-Bayar** untuk menutup gap resmi `FSD_SERVIS.md` section 8.

**Catatan penting:**
- Merge Customer tetap dieksekusi dari `customer_merge_log` (`FSD_CUSTOMER.md` FR-05).
- Pindah kepemilikan kendaraan tetap dieksekusi dari `kepemilikan_kendaraan` (`FSD_KENDARAAN.md` FR-05).
- `tbl_issue` boleh dibuat otomatis oleh sistem sebagai **referensi read-only** untuk pelacakan lintas modul, tetapi bukan sumber kebenaran approval utama untuk dua alur di atas.

## 2. Ruang Lingkup

**In scope:**
- dashboard Customer 360,
- tiket masalah terstruktur untuk kasus ad hoc yang belum punya alur khusus,
- approval tiket terstruktur,
- auto-eksekusi untuk jenis masalah tertentu,
- broadcast/reminder dasar.

**Out of scope:**
- logic merge Customer itu sendiri (`FSD_CUSTOMER.md`),
- logic pindah kepemilikan kendaraan itu sendiri (`FSD_KENDARAAN.md`),
- redesign formula komisi/HPP (`FSD_SERVIS.md`) — modul ini hanya memicu approval dan mencatat audit trail.

## 3. Aktor & Role

| Aktor | Hak Akses |
|---|---|
| CS / Kasir | Lihat dashboard Customer, buat tiket terstruktur baru. |
| Mekanik | Buat tiket terstruktur yang relevan dengan servis/komisi. |
| Supervisor / Kepala Cabang | Review, approve/reject tiket level cabang. |
| Owner / Admin Pusat | Review lintas cabang, approve tiket sensitif, kelola broadcast. |
| Sistem (background job) | Auto-generate tiket referensi/read-only untuk visibility lintas modul atau hasil deteksi anomali. |

## 4. Glosarium

| Istilah | Arti |
|---|---|
| Customer 360 | Dashboard 1 halaman menampilkan seluruh info relevan 1 Customer: identitas, kendaraan, membership, histori. |
| Tiket / Issue | Catatan permintaan tindakan atau masalah operasional yang butuh penanganan dan/atau approval. |
| Jenis Masalah | Katalog masalah terstruktur yang menentukan field input, role approval, dan target eksekusi. |
| `payload_json` | Data terstruktur hasil form dinamis berdasarkan `master_jenis_masalah.skema_field`. |

## 5. Model Data

### 5.1 Tabel Baru

**`master_jenis_masalah`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id_jenis` | INT PK AUTO_INCREMENT | |
| `kategori` | ENUM('data_pelanggan','data_kendaraan','komisi','stok','sistem','lainnya') | kategori induk |
| `nama_masalah` | VARCHAR(150) | label yang muncul ke user |
| `skema_field` | JSON | definisi field dinamis: nama, label, tipe, wajib/opsional, lookup |
| `role_approval` | ENUM('self','supervisor','owner') | siapa yang boleh approve |
| `target_eksekusi` | VARCHAR(100) NULL | identifier fungsi/modul auto-eksekusi |
| `is_active` | TINYINT(1) | 1 = aktif |
| `created_at`,`updated_at` | TIMESTAMP | |

**Seed awal katalog:**
- `Revisi Komisi Servis Pasca-Bayar`
- `Koreksi Persentase Kerja Mekanik`
- `Koreksi Kepala Mekanik Salah Assign`
- `Salah Pilih Customer saat Transaksi`
- `Salah Input Nopol saat Servis`
- `Masalah Lain (Tidak Terkategorikan)`

### 5.2 Tabel Existing yang Direstrukturisasi

**`tbl_issue`**

`tbl_issue` **tetap dipakai**, bukan diganti tabel baru, tetapi direstrukturisasi sebagai berikut:

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id_issue` | VARCHAR(20) PK | format `ISS-YYYYMMDD-####` |
| `tanggal_lapor` | TIMESTAMP | |
| `pelapor` | INT FK -> `tbuser` / `tbuser_karyawan` | |
| `kd_cabang` | VARCHAR(10) | cabang pelapor |
| `divisi_terkait` | ENUM('CS','Kasir','Mekanik','Gudang','Owner','IT') | |
| `kategori` | VARCHAR(30) | nilai normalisasi dari `master_jenis_masalah.kategori` |
| `id_jenis_masalah` | INT FK -> `master_jenis_masalah` | jenis masalah terstruktur |
| `prioritas` | ENUM('low','medium','high','critical') | |
| `status` | ENUM('open','in_progress','waiting_approval','resolved','closed','rejected') | |
| `pic` | INT NULL | penanggung jawab |
| `deadline` | DATE NULL | |
| `deskripsi` | TEXT NULL | catatan tambahan, **bukan sumber utama data** |
| `payload_json` | JSON NULL | data utama hasil form dinamis |
| `solusi` | TEXT NULL | hasil penanganan / catatan approver |
| `ref_nopelanggan` | VARCHAR(20) NULL | opsional |
| `ref_no_polisi` | VARCHAR(20) NULL | opsional |
| `created_at`,`updated_at` | TIMESTAMP | |

**Prinsip baru:**
- user **tidak mengandalkan textarea bebas** sebagai input utama,
- informasi utama harus berasal dari `payload_json`,
- `deskripsi` hanya untuk konteks tambahan.

### 5.3 Tabel Existing Tetap

**`tbl_issue_progress_log`**
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `id_issue` | VARCHAR(20) FK | |
| `tanggal` | TIMESTAMP | |
| `oleh` | INT FK -> user | |
| `catatan` | TEXT | |
| `status_before`,`status_after` | VARCHAR(20) | |

### 5.4 Tabel Dependensi Pilot

**`servis_komisi`** — mengikuti `FSD_SERVIS.md` section 5.2, dipakai sebagai snapshot permanen komisi per servis. Untuk kasus revisi pasca-bayar, sistem **INSERT baris baru**, bukan update baris lama.

## 6. Functional Requirements

### FR-01 — Dashboard Customer 360
**Deskripsi:** Halaman tunggal per Customer, layout dua tingkat:
```
+------------------------------------------------------------+
| CUSTOMER: [Nama]                    Status Member: GOLD     |
| Total Kendaraan: 3 | Total Kunjungan: 47 | LTV: Rp 18.4jt   |
| Total Omzet: Rp 18.4jt | Terakhir Service: 12 hari lalu     |
+------------------------------------------------------------+
+--- Motor A --- Motor B --- Motor C (expand/collapse) -------+
| Kunjungan | Transaksi | Jasa | Sparepart | Terakhir | KM     |
+---------------------------------------------------------------+
```
**Sumber data:**
- Bagian atas: `statistik_pelanggan` + `master_kategori_member`.
- Bagian bawah: `statistik_kendaraan` untuk kendaraan aktif milik Customer.

### FR-02 — Ajukan Tiket Terstruktur
**Deskripsi:**
User klik **Lapor Masalah** → pilih **kategori** → pilih **jenis masalah** → sistem render **form dinamis** sesuai `master_jenis_masalah.skema_field` → submit tiket.

**Business rule:**
- user **tidak** mengetik deskripsi bebas sebagai input utama,
- field utama diambil dari `payload_json`,
- status awal:
  - `waiting_approval` untuk jenis yang butuh approval,
  - `open` untuk jenis `role_approval='self'`.

**Contoh jenis masalah:**
- Revisi Komisi Servis Pasca-Bayar
- Koreksi Persentase Kerja Mekanik
- Salah Pilih Customer saat Transaksi
- Salah Input Nopol saat Servis

### FR-03 — Approval Tiket Terstruktur
**Deskripsi:**
Approver membuka tiket `waiting_approval`, melihat detail terstruktur dari `payload_json`, melihat diff nilai lama vs nilai baru, lalu approve/reject.

**Business rule:**
- setiap perubahan status **wajib** insert `tbl_issue_progress_log`,
- approval/reject **tidak boleh** hanya mengandalkan baca paragraf deskripsi,
- layar approval harus menampilkan data terstruktur dan diff sejauh bisa dihitung sistem.

### FR-04 — Auto-Eksekusi Saat Approve
**Deskripsi:**
Jika `master_jenis_masalah.target_eksekusi` terisi, maka saat tiket di-approve sistem menjalankan aksi otomatis terkait.

**Business rule:**
- kalau `target_eksekusi` kosong, tiket tetap valid sebagai tiket terstruktur manual,
- hasil auto-eksekusi harus tercatat di `solusi`/audit trail tiket.

### FR-05 — Tiket Referensi Otomatis dari Modul Lain
**Deskripsi:**
Modul lain boleh membuat `tbl_issue` otomatis sebagai **referensi read-only** untuk visibility/SLA lintas modul.

**Business rule:**
- merge Customer dan pindah kepemilikan kendaraan **tetap** dieksekusi di tabel sumber masing-masing,
- `tbl_issue` pada dua kasus itu bukan gerbang approval utama.

### FR-06 — Broadcast/Reminder Dasar
**Deskripsi:**
Fitur ekspor daftar kontak Customer untuk broadcast WA manual.

**Business rule:**
- ekspor hanya pakai kontak aktif (`is_current=1` di `pelanggan_kontak_history`).

### FR-07 — Pilot Wajib: Revisi Komisi Servis Pasca-Bayar
**Deskripsi:**
Jenis masalah terstruktur untuk merevisi komisi servis setelah status bayar, dengan approval Supervisor+.

**Skema field wajib:**
- `no_service` — autocomplete dari `tblservice WHERE status_servis='bayar'`
- `peran` — enum sama seperti `servis_komisi.peran`
- `persen_lama` — auto-fill read-only
- `persen_baru` — input user
- `alasan` — wajib

**Approval & Eksekusi:**
- approver melihat diff nilai lama vs nilai baru,
- saat approve, sistem **INSERT** baris baru ke `servis_komisi`,
- baris lama **tidak diupdate/dihapus**,
- `id_issue_ref` disimpan untuk audit trail.

**Formula yang dikonsumsi dari `FSD_SERVIS.md` section 8:**
- komisi jasa mekanik: 20% dari jasa bersih, dibagi rata jumlah mekanik kerja,
- komisi barang mekanik: 5% dari laba item, dibagi rata jumlah mekanik kerja,
- komisi admin jasa: 5% dari jasa bersih,
- komisi admin barang: 5% dari laba item.

**Catatan implementasi live schema 2026-07-11:**
schema produksi belum punya snapshot `laba_barang` di `tblservice`, sehingga laba barang dihitung dari `tblservis_barang.total - (quantity * tblitem.hargapokok)` sampai modul servis punya snapshot HPP/laba resmi per transaksi.

## 7. Business Rules Konsolidasi

| Kode | Aturan |
|---|---|
| BR-CRM-01 | Dashboard 360 hanya menampilkan kendaraan dengan kepemilikan aktif Customer yang sedang dilihat. |
| BR-CRM-02 | `tbl_issue` dipakai untuk masalah ad hoc terstruktur dan visibility lintas modul; bukan gerbang utama untuk alur yang sudah punya tabel approval sendiri. |
| BR-CRM-03 | Setiap perubahan status tiket wajib tercatat di `tbl_issue_progress_log`. |
| BR-CRM-04 | Input utama tiket harus berasal dari `payload_json`, bukan `deskripsi` bebas. |
| BR-CRM-05 | Approve revisi komisi pasca-bayar harus menambah snapshot baru di `servis_komisi`, tidak overwrite snapshot lama. |
| BR-CRM-06 | Export kontak WA selalu pakai data kontak `is_current=1`. |

## 8. Alur Utama

### 8.1 Tiket Masalah Terstruktur

```
User buka Lapor Masalah
    -> pilih kategori
    -> pilih jenis masalah
    -> isi form dinamis
    -> submit tiket
    -> status waiting_approval
    -> approver review diff
    -> approve/reject
    -> jika ada target_eksekusi: jalankan otomatis
```

### 8.2 Merge Customer / Pindah Kepemilikan

```
Modul sumber jalankan approval di tabel sumber masing-masing
    -> opsional create tbl_issue referensi read-only
    -> tbl_issue hanya untuk visibility / SLA / audit lintas modul
```

## 9. Edge Case Handling

| Edge Case | Penanganan |
|---|---|
| User butuh lapor masalah yang belum ada di katalog | sementara pilih `Masalah Lain (Tidak Terkategorikan)`; usulan jenis baru dibahas di Open Item |
| Tiket terstruktur dibuat, tapi lookup nilai lama tidak tersedia | tiket tetap bisa diproses; approver melihat payload baru + catatan bahwa nilai lama tidak terdeteksi otomatis |
| Revisi komisi pasca-bayar dilakukan lebih dari sekali | setiap revisi = 1 baris baru `servis_komisi`; histori tetap utuh |
| Modul lain sudah punya approval sendiri | `tbl_issue` hanya referensi, bukan sumber kebenaran approval |
| Tiket diajukan tapi tidak pernah direspon | perlu SLA/reminder eskalasi — tetap Open Item |

## 10. Non-Functional Requirements

- Form tiket harus support render field dinamis dari JSON schema tanpa hardcode semua jenis masalah.
- `tbl_issue` harus searchable by `id_issue`, `kategori`, `ref_nopelanggan`, `ref_no_polisi`.
- Approval screen harus tetap terbaca meski `payload_json` punya banyak field.
- Auto-eksekusi harus menulis hasil ke audit trail tiket.

## 11. Dependency Antar Modul

- `FSD_CUSTOMER.md` — data Customer, merge Customer.
- `FSD_KENDARAAN.md` — data kendaraan, pindah kepemilikan.
- `FSD_MEMBERSHIP.md` — badge tier di dashboard.
- `FSD_SERVIS.md` — formula komisi, `servis_komisi`, referensi servis bayar.

## 12. Kriteria Penerimaan

1. Tidak ada lagi textarea bebas sebagai satu-satunya input utama di form lapor masalah.
2. User membuat tiket dengan memilih jenis masalah dari katalog `master_jenis_masalah`.
3. Approval screen menampilkan data terstruktur dari `payload_json`, bukan hanya deskripsi paragraf.
4. Revisi Komisi Servis Pasca-Bayar saat approve menghasilkan **baris baru** di `servis_komisi`, tanpa menghapus/menimpa baris lama.
5. Merge Customer dan pindah kepemilikan kendaraan tetap berjalan lewat `customer_merge_log` / `kepemilikan_kendaraan` seperti sebelumnya.
6. Semua perubahan status tiket tercatat di `tbl_issue_progress_log`.

## 13. Open Items — Butuh Keputusan Sebelum Implementasi Lanjutan

| # | Pertanyaan | Kenapa Penting |
|---|---|---|
| O1 | Siapa yang berhak maintain katalog `master_jenis_masalah`? Owner only, atau Supervisor boleh tambah/edit? | Menentukan UI master katalog dan governance jenis masalah baru |
| O2 | Jenis masalah `role_approval='self'` apa saja yang diizinkan? | Menentukan tiket low-risk yang tidak perlu approval Supervisor |
| O3 | Perlu jalur deteksi anomali otomatis penuh (bukan hanya laporan user) di fase ini atau fase berikutnya? | Menentukan prioritas FR auto-generated proactive tickets |
| O4 | Saat live schema belum punya snapshot HPP/laba barang per servis, apakah pendekatan hitung dari `tblservis_barang` + `tblitem.hargapokok` diterima sementara? | Menentukan akurasi komisi barang sampai modul servis punya snapshot resmi |
| O5 | Apakah `Masalah Lain (Tidak Terkategorikan)` tetap diizinkan permanen, atau hanya transisi sementara sampai katalog matang? | Menentukan seberapa ketat sistem memaksa struktur penuh |
