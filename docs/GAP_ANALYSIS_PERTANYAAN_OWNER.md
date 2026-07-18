# GAP ANALYSIS — PERTANYAAN BISNIS UNTUK MEETING OWNER
## Bengkel 2.0 → Web Migration | Prepared for: Pak Novian
### Tanggal Dibuat: 2026-06-26
### Berdasarkan: Audit Tahap 1 + Audit Tahap 2 + Source Access MDB (FITMOTOR APP.mdb + FITMOTOR GABUNG.mdb)

---

## EXECUTIVE SUMMARY

Dari hasil audit terhadap **FITMOTOR APP.mdb** (194 QueryDefs, 31 tabel), **FITMOTOR GABUNG.mdb** (253 QueryDefs, 33 tabel), dan seluruh codebase PHP web, ditemukan bahwa:

- Sistem Access menyimpan **banyak aturan bisnis implisit** yang tidak terdokumentasi — hanya terlihat dari pola query, nama field, dan logika VBA
- Web app sudah mengimplementasikan beberapa perbaikan di atas Access (status servis lebih detail, dll), namun beberapa perbedaan ini **belum tentu benar** karena mungkin tidak sesuai keinginan owner
- Ada **minimal 6 area kritis** yang jika salah implementasi akan mengakibatkan: stok minus, laporan omset salah, komisi mekanik tidak akurat, atau histori transaksi tidak bisa diaudit
- Terdapat **fitur di Access yang belum ada di web** dan **fitur di web yang tidak ada padanannya di Access** — keduanya perlu keputusan bisnis

Dokumen ini berisi **110 pertanyaan bisnis** yang harus dijawab owner sebelum developer coding lebih lanjut.

### Urutan Diskusi yang Disarankan

1. **Mulai dari C-01 dan C-02** — definisi selesai dan urutan status servis, karena ini fondasi dari semua aturan lain
2. **Lanjut ke C-07 dan C-32** — formula dan persentase komisi per cabang, karena paling sensitif bagi mekanik
3. **C-03, C-04, C-06** — stok dan cancel, karena langsung berdampak ke laporan gudang
4. **C-11, C-12, C-29, C-30** — garansi dan program promosi, keputusan bisnis yang tidak terlihat dari kode
5. **H-15** — hak akses per role, butuh sesi tersendiri dengan semua kepala divisi

---

# BAGIAN 1 — CRITICAL QUESTIONS

> Jika salah dijawab akan menyebabkan: stok salah, laporan salah, omset salah, komisi salah, histori salah, fraud, atau data tidak konsisten.

---

### C-01

| Field | Isi |
|---|---|
| **Kategori** | Servis — Definisi Selesai |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Kapan sebuah transaksi servis dianggap "SELESAI" secara bisnis? Apakah saat mekanik selesai mengerjakan, saat kasir menerima pembayaran, atau saat motor diambil customer?

**Mengapa penting:**
Di Access hanya ada satu status akhir (`Status='3'`). Web app menambahkan status lebih detail. Tapi urutan dan definisi exaknya belum dikonfirmasi owner.

**Risiko jika salah:**
Laporan omset harian bisa membaca data servis yang belum dibayar sebagai pendapatan.

**Keputusan yang dipengaruhi:**
Enum status servis, kapan omset dihitung, kapan mekanik dapat komisi.

**Tabel/File terkait:** `TBLService.Status`, `tbservis.status_servis`, `servis-input-reguler.php`

**Sumber:** Audit Tahap 2 — Temuan T-04

---

### C-02

| Field | Isi |
|---|---|
| **Kategori** | Servis — Urutan Status |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apa urutan status servis yang benar dari awal sampai akhir? Pilih yang sesuai praktik bengkel:

- **(A)** Datang → Antri → Dikerjakan → Selesai → Bayar → Motor Diambil
- **(B)** Datang → Dikerjakan → Bayar → Selesai → Motor Diambil
- **(C)** Datang → Antri → Approval → Dikerjakan → QC → Selesai → Bayar → Motor Diambil
- **(D)** Berbeda antara reguler dan jemput antar?

**Mengapa penting:**
Setiap transisi status berdampak pada: stok, komisi, laporan, dan notifikasi customer.

**Risiko jika salah:**
Workflow operasional tidak sesuai SOP bengkel. Mekanik bingung. Kasir tidak bisa bayar sebelum status benar.

**Keputusan yang dipengaruhi:**
Seluruh enum status, tombol aksi di setiap halaman, logika approval.

**Tabel/File terkait:** `tbservis.status_servis`, semua file `servis-input-*.php`

**Sumber:** Audit Tahap 2 — T-04, Analisa tambahan

---

### C-03

| Field | Isi |
|---|---|
| **Kategori** | Stok — Pemotongan Servis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Kapan stok sparepart dipotong saat servis? Pilih yang sesuai:

- **(A)** Saat sparepart ditambahkan ke work order (saat input)
- **(B)** Saat mekanik selesai mengerjakan
- **(C)** Saat kasir menerima pembayaran
- **(D)** Saat motor diambil customer

**Mengapa penting:**
Di Access, stok dipotong melalui VBA saat entry — bukan via query yang terlihat. Di web app, potongan stok terjadi saat penyimpanan. Tapi "saat simpan" bisa berbeda maknanya.

**Risiko jika salah:**
Stok minus jika dipotong terlalu awal dan servis dibatalkan. Stok tidak akurat jika dipotong terlalu lambat.

**Keputusan yang dipengaruhi:**
Logika insert ke `tbstok`, kapan restore jika batal.

**Tabel/File terkait:** `tbstok`, `TBLServiceItemDt`, `save-no-servis-reguler.php`

**Sumber:** Audit Tahap 2 — T-02, Temuan Indikatif I-01

---

### C-04

| Field | Isi |
|---|---|
| **Kategori** | Stok — Cancel Servis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika servis dibatalkan SETELAH sparepart sudah diinput (tapi belum bayar), apakah stok dikembalikan otomatis?

**Mengapa penting:**
Di Access, tidak ditemukan query restore stok untuk cancel servis. Artinya kemungkinan stok tidak kembali — atau ada mekanisme lain di luar query (manual opname).

**Risiko jika salah:**
Jika stok tidak dikembalikan → stok minus secara bertahap. Jika dikembalikan dobel → stok menggelembung.

**Keputusan yang dipengaruhi:**
Logic cancel di web, apakah perlu approval sebelum cancel.

**Tabel/File terkait:** `tbstok`, `TBLServiceItemDt`, `TBLService.Status`

**Sumber:** Audit Tahap 2 — Temuan Indikatif I-01

---

### C-05

| Field | Isi |
|---|---|
| **Kategori** | Stok — Cancel Setelah Bayar |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika servis sudah LUNAS kemudian dibatalkan (refund), apakah:

- **(A)** Stok dikembalikan + uang dikembalikan?
- **(B)** Stok dikembalikan + credit note ke piutang?
- **(C)** Tidak pernah terjadi di praktik bengkel?
- **(D)** Dibuatkan transaksi retur terpisah?

**Mengapa penting:**
Tidak ada mekanisme retur servis yang terlihat di Access maupun web app saat ini.

**Risiko jika salah:**
Uang kembali tapi stok tidak kembali → stok minus. Atau sebaliknya.

**Keputusan yang dipengaruhi:**
Perlu fitur retur servis atau tidak. Alur pengembalian uang.

**Tabel/File terkait:** `tbservis`, `tbstok`, modul kasir

**Sumber:** Analisa tambahan — edge case

---

### C-06

| Field | Isi |
|---|---|
| **Kategori** | Stok — Sparepart Sudah Pasang, Servis Batal |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika sparepart sudah dipasang di motor tapi customer menolak membayar (atau servis batal karena alasan lain), sparepart tersebut:

- **(A)** Dilepas dan stok dikembalikan
- **(B)** Tidak dilepas, dianggap biaya rugi
- **(C)** Ditagihkan tetap meskipun customer menolak
- **(D)** Tergantung kasusnya — ada mekanisme negosiasi?

**Mengapa penting:**
Ini edge case yang tidak ada di sistem manapun saat ini.

**Risiko jika salah:**
Kerugian stok tidak tercatat, atau stok dikembalikan padahal sudah tidak ada fisiknya.

**Keputusan yang dipengaruhi:**
SOP cancel servis. Otorisasi yang diperlukan untuk cancel.

**Tabel/File terkait:** `tbstok`, `tbservis_item`

**Sumber:** Analisa tambahan — edge case

---

### C-07

| Field | Isi |
|---|---|
| **Kategori** | Komisi — Formula Mekanik |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Formula komisi mekanik saat ini di Access:
- Jasa: `(SubTotalJasa - Outsource) × 20% ÷ jumlah mekanik`
- Barang: `LabaItem × 5% ÷ jumlah mekanik`

Apakah formula ini MASIH BERLAKU untuk semua cabang? Atau ada cabang yang berbeda?

**Mengapa penting:**
Formula ini ditemukan dari query Access. Belum ada konfirmasi apakah ini masih current atau sudah berubah.

**Risiko jika salah:**
Seluruh laporan komisi mekanik salah → mekanik protes → fraud jika sistem dimanipulasi.

**Keputusan yang dipengaruhi:**
Implementasi `lap_komisi_mekanik.php`, tabel `BAGI_HASIL`.

**Tabel/File terkait:** `BAGI_HASIL`, `DATA_INSENTIF_SERVIS`, `lap_komisi_mekanik.php`

**Sumber:** Audit Tahap 2 — Temuan T-05

---

### C-08

| Field | Isi |
|---|---|
| **Kategori** | Komisi — Admin |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan:
- Admin jasa = `JASABERSIH × 5%`
- Admin barang = `LabaItem × 5%`

Siapa yang dimaksud "Admin" di sini? Apakah service advisor, kepala mekanik, atau admin kantor?

**Mengapa penting:**
Jika salah mapping, komisi dibayarkan ke orang yang salah.

**Risiko jika salah:**
Fraud. Pembayaran komisi ke pihak yang tidak berhak.

**Keputusan yang dipengaruhi:**
Field `no_advisor` di servis, tabel BAGI_HASIL kategori admin.

**Tabel/File terkait:** `DATA_INSENTIF_SERVIS`, `BAGI_HASIL`, `TBLService_Advisor`

**Sumber:** Audit Tahap 2 — T-05

---

### C-09

| Field | Isi |
|---|---|
| **Kategori** | Komisi — Revisi Invoice |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika invoice servis direvisi setelah selesai (misalnya harga jasa diubah atau item dihapus), apakah komisi mekanik ikut berubah otomatis atau tetap mengacu ke nilai awal?

**Mengapa penting:**
Di Access komisi dihitung real-time (SELECT, tidak di-commit). Di web app, perlu keputusan: real-time atau snapshot.

**Risiko jika salah:**
Komisi mekanik tidak konsisten dengan pembayaran aktual. Potensi selisih kas.

**Keputusan yang dipengaruhi:**
Apakah perlu tabel komisi_snapshot atau tetap real-time.

**Tabel/File terkait:** `DATA_INSENTIF_SERVIS`, `tbservis`, `tbservis_item`

**Sumber:** Audit Tahap 2 — T-05, Analisa tambahan

---

### C-10

| Field | Isi |
|---|---|
| **Kategori** | Komisi — Garansi & Comeback |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika customer kembali dengan keluhan (comeback/garansi) dan servis diulang tanpa biaya, apakah mekanik tetap dapat komisi untuk pekerjaan ulang tersebut?

**Mengapa penting:**
Tidak ada aturan ini di Access maupun web saat ini.

**Risiko jika salah:**
Mekanik dapat komisi ganda untuk pekerjaan yang seharusnya gratis. Atau mekanik tidak mau mengerjakan garansi karena tidak ada insentif.

**Keputusan yang dipengaruhi:**
Logic komisi untuk servis garansi, field `is_garansi`.

**Tabel/File terkait:** `CEK_INPUT_GARANSI_SERVIS`, `TBLService`, `BAGI_HASIL`

**Sumber:** Audit Tahap 2 — I-02, Analisa tambahan

---

### C-11

| Field | Isi |
|---|---|
| **Kategori** | Garansi — Definisi |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apa definisi resmi "garansi servis"? Berapa lama masa garansi? Apakah garansi berlaku untuk:

- **(A)** Jasa saja
- **(B)** Sparepart saja
- **(C)** Keduanya
- **(D)** Tergantung jenis servis

**Mengapa penting:**
Di Access, garansi hanya dideteksi dari teks bebas di field KETERANGAN (`LIKE '*GARAN*'`). Tidak ada tabel garansi terpisah. Ini sangat rapuh.

**Risiko jika salah:**
Garansi tidak tercatat → customer dirugikan → reputasi bengkel turun.

**Keputusan yang dipengaruhi:**
Perlu tabel garansi terpisah atau cukup field flag, masa berlaku garansi.

**Tabel/File terkait:** `CEK_INPUT_GARANSI_SERVIS`, `TBLService.Keterangan`, `servis-garansi-rst.php`

**Sumber:** Audit Tahap 2 — I-02

---

### C-12

| Field | Isi |
|---|---|
| **Kategori** | Garansi — Stok |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Saat servis garansi (pengerjaan ulang tanpa biaya), sparepart yang digunakan:

- **(A)** Dipotong stok normal (biaya ditanggung bengkel)
- **(B)** Dipotong stok tapi masuk ke akun "biaya garansi" terpisah
- **(C)** Tidak dipotong stok (sparepart lama dipakai ulang)
- **(D)** Tergantung keputusan mekanik/kepala bengkel?

**Mengapa penting:**
Tidak ada aturan ini di sistem manapun. Jika stok dipotong tanpa dicatat sebagai garansi, laporan HPP menjadi salah.

**Risiko jika salah:**
Laporan laba/rugi salah. Stok keluar tanpa jejak yang benar.

**Keputusan yang dipengaruhi:**
Logic `tbstok` saat servis garansi, laporan biaya garansi.

**Tabel/File terkait:** `tbstok`, `tbservis_item`, `servis-input-garansi.php`

**Sumber:** Analisa tambahan

---

### C-13

| Field | Isi |
|---|---|
| **Kategori** | Omset — Definisi Tanggal |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Laporan omset harian bengkel membaca dari mana? Apakah:

- **(A)** Semua servis dengan status = bayar/lunas di hari itu
- **(B)** Semua servis yang dibuka hari itu
- **(C)** Semua servis yang selesai dikerjakan hari itu
- **(D)** Berdasarkan tanggal pembayaran kasir

**Mengapa penting:**
Di Access, omset servis dibaca dari `TBLService.TotalAkhir` langsung. Tapi kapan tanggalnya — tanggal input atau tanggal bayar?

**Risiko jika salah:**
Omset bisa shift ke hari berbeda. Rekonsiliasi dengan kasir tidak cocok.

**Keputusan yang dipengaruhi:**
Filter tanggal di semua laporan servis, `lap_servis.php`.

**Tabel/File terkait:** `TBLService.Tanggal`, `tbservis.tgl_bayar`, `lap_servis.php`

**Sumber:** Audit Tahap 2 — T-01

---

### C-14

| Field | Isi |
|---|---|
| **Kategori** | Omset — Servis vs Penjualan |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Dalam laporan omset total bengkel (harian/bulanan), apakah servis dan penjualan counter digabung atau dipisah?

**Mengapa penting:**
Di Access ada query UNION `DATA_SERVIS_JUAL` yang menggabung keduanya untuk laporan tertentu. Tapi laporan spesifik mana yang gabung dan mana yang pisah belum dikonfirmasi.

**Risiko jika salah:**
Double-counting atau under-counting omset.

**Keputusan yang dipengaruhi:**
`lap_servis.php`, laporan rekap harian, laporan kasir.

**Tabel/File terkait:** `DATA_SERVIS_JUAL`, `REKAP_HARIAN`, `TRANSAKSI_HARIAN_KASIR`

**Sumber:** Audit Tahap 2 — T-01

---

### C-15

| Field | Isi |
|---|---|
| **Kategori** | Multi-Mekanik — Pembagian Komisi |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika satu motor dikerjakan oleh 2 atau lebih mekanik, apakah komisi dibagi rata atau ada mekanisme pembagian tidak rata?

**Mengapa penting:**
Di Access ada field `Mekanik1`–`Mekanik4` dan `BiayaM1`–`BiayaM4`. Formula `÷ jumlah_mekanik` mengindikasikan bagi rata, tapi nilai `BiayaM1`–`BiayaM4` yang bisa berbeda mengindikasikan mungkin tidak selalu rata.

**Risiko jika salah:**
Mekanik tertentu dibayar lebih atau kurang dari haknya.

**Keputusan yang dipengaruhi:**
Logic pembagian komisi, field `BiayaM1`–`BiayaM4` vs formula rata.

**Tabel/File terkait:** `TBLService.BiayaM1`–`BiayaM4`, `MEKANIK_PERSERVIS`, `DATA_INSENTIF_SERVIS`

**Sumber:** Audit Tahap 2 — T-05

---

### C-16

| Field | Isi |
|---|---|
| **Kategori** | Multi-Mekanik — Kepala Mekanik |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah ada konsep "Kepala Mekanik" atau "Mekanik Utama" yang mendapat porsi komisi berbeda dari mekanik pembantu?

**Mengapa penting:**
Di Access ditemukan `TBLService_Advisor` — apakah ini service advisor atau kepala mekanik? Tidak jelas dari struktur saja.

**Risiko jika salah:**
Hierarchy komisi tidak benar. Kepala mekanik tidak puas.

**Keputusan yang dipengaruhi:**
Field Mekanik1 = utama atau tidak ada perbedaan, formula komisi.

**Tabel/File terkait:** `TBLService_Advisor`, `TBLService.Mekanik1`

**Sumber:** Audit Tahap 2, ACCESS_FEATURE_ANALYSIS

---

### C-17

| Field | Isi |
|---|---|
| **Kategori** | Insentif — Service Advisor |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan query `INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS` dan `INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM`. Apakah Service Advisor mendapat insentif terpisah dari mekanik? Berapa persennya?

**Mengapa penting:**
Tidak ada field advisor di web app saat ini.

**Risiko jika salah:**
Advisor tidak mendapat insentif → demotivasi. Atau dibayar double dengan mekanik.

**Keputusan yang dipengaruhi:**
Perlu field `no_advisor` di servis, tabel insentif advisor.

**Tabel/File terkait:** `INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS`, `TBLService_Advisor`

**Sumber:** ACCESS_FEATURE_ANALYSIS — cluster insentif

---

### C-18

| Field | Isi |
|---|---|
| **Kategori** | Laba — Metode HPP |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Laba item servis dihitung dari: Harga Jual Item - HPP Item. HPP yang dipakai adalah:

- **(A)** HPP terakhir beli (last purchase price)
- **(B)** HPP rata-rata (average cost)
- **(C)** FIFO (first in, first out)
- **(D)** HPP yang diinput manual saat beli

**Mengapa penting:**
Di Access ada `TBLServiceItemDtFifo` — menunjukkan FIFO. Tapi di web app, metode HPP belum tentu sama.

**Risiko jika salah:**
Laporan laba/rugi servis tidak akurat. Komisi mekanik (berbasis laba) ikut salah.

**Keputusan yang dipengaruhi:**
Metode costing di seluruh sistem, `tbstok_fifo`, komisi mekanik.

**Tabel/File terkait:** `TBLServiceItemDtFifo`, `LABA_ITEM_SERVICE`, `tbstok`

**Sumber:** Audit Tahap 2 — T-02

---

### C-19

| Field | Isi |
|---|---|
| **Kategori** | Histori Servis — Visibility |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Kapan sebuah servis boleh tampil di histori kendaraan customer? Apakah:

- **(A)** Sejak dibuka (status datang)
- **(B)** Hanya yang sudah selesai/bayar
- **(C)** Hanya yang sudah diambil motornya
- **(D)** Semua kecuali yang dibatalkan

**Mengapa penting:**
Customer bisa melihat histori servis. Jika servis yang belum bayar tampil, bisa membingungkan. Jika yang batal tampil, merusak histori.

**Risiko jika salah:**
Customer salah membaca histori. Staff salah referensi garansi.

**Keputusan yang dipengaruhi:**
Filter query histori kendaraan, `_modal_riwayat_kendaraan.php`.

**Tabel/File terkait:** `HISTORY_SERVIS_HEADER`, `HISTORY_SERVIS_DETAIL`, `_template/_modal_riwayat_kendaraan.php`

**Sumber:** Analisa tambahan

---

### C-20

| Field | Isi |
|---|---|
| **Kategori** | Stok Opnam |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Saat stok opnam dilakukan dan ada selisih, bagaimana prosesnya:

- **(A)** Selisih langsung di-adjust di sistem
- **(B)** Perlu approval atasan sebelum adjust
- **(C)** Selisih masuk ke akun rugi/laba penyesuaian
- **(D)** Ada SOP tertulis untuk ini?

**Mengapa penting:**
Di Access ditemukan `STOK_OPNAM_TEMP`, `STOK_OPNAM_TIDAKLAKU`, `HASIL_STOK_OPNAM_TEMP`. Fitur stok opnam belum ada di web.

**Risiko jika salah:**
Stok opnam tanpa prosedur benar → stok bisa dimanipulasi → fraud.

**Keputusan yang dipengaruhi:**
Fitur stok opnam di web, apakah perlu approval workflow.

**Tabel/File terkait:** `STOK_OPNAM_TEMP`, `STOK_OPNAM_TIDAKLAKU`

**Sumber:** ACCESS_FEATURE_ANALYSIS — cluster stok

---

### C-21

| Field | Isi |
|---|---|
| **Kategori** | Pembayaran — Split Payment |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah customer bisa membayar servis dengan lebih dari satu metode pembayaran dalam satu transaksi? Contoh: sebagian tunai, sebagian transfer?

**Mengapa penting:**
Tidak ada mekanisme split payment yang terlihat di web app maupun Access saat ini.

**Risiko jika salah:**
Kasir tidak bisa menutup transaksi dengan benar. Selisih kas.

**Keputusan yang dipengaruhi:**
Tabel pembayaran, laporan kas per metode.

**Tabel/File terkait:** `tbservis.carabayar`, modul kasir

**Sumber:** Analisa tambahan — edge case

---

### C-22

| Field | Isi |
|---|---|
| **Kategori** | DP / Uang Muka Servis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah ada mekanisme uang muka (DP) untuk servis? Jika ada:

- **(A)** Berapa persen minimal DP?
- **(B)** Apakah DP masuk kasir langsung?
- **(C)** Jika servis batal setelah DP, apakah dikembalikan?

**Mengapa penting:**
Tidak ditemukan field DP di `TBLService`. Tapi ini edge case yang sangat mungkin terjadi di bengkel.

**Risiko jika salah:**
DP tercatat di kasir tapi servis tidak jadi → selisih kas.

**Keputusan yang dipengaruhi:**
Field `dp_amount` di servis, alur kasir DP.

**Tabel/File terkait:** `tbservis`, `tbkas`

**Sumber:** Analisa tambahan — edge case

---

### C-23

| Field | Isi |
|---|---|
| **Kategori** | Rekonsiliasi Antar Cabang |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan query `REKONSIL_JUAL_BELI_ANTARCABANG_DATA`, `REKAP_BELI_CABANG_SALAH`, `REKAP_JUAL_CABANG_SALAH`. Bagaimana prosedur rekonsiliasi penjualan-pembelian antar cabang? Siapa yang bertanggung jawab jika ada selisih?

**Mengapa penting:**
Rekonsiliasi ini tidak ada di web app saat ini.

**Risiko jika salah:**
Selisih antar cabang tidak terdeteksi → kerugian tidak diketahui.

**Keputusan yang dipengaruhi:**
Fitur rekonsiliasi antar cabang, approval workflow.

**Tabel/File terkait:** `REKONSIL_JUAL_BELI_ANTARCABANG_DATA`, `antarcab_list.php`

**Sumber:** FITMOTOR GABUNG audit

---

### C-24

| Field | Isi |
|---|---|
| **Kategori** | Mekanik — Lintas Cabang |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah satu mekanik bisa bekerja di lebih dari satu cabang? Atau mekanik terikat ke satu cabang saja?

**Mengapa penting:**
Jika mekanik bisa lintas cabang, laporan komisi harus difilter per cabang per mekanik. Jika terikat ke satu cabang, cukup filter per cabang.

**Risiko jika salah:**
Laporan komisi mekanik di GABUNG tidak akurat.

**Keputusan yang dipengaruhi:**
Field `kd_cabang` di `tblmekanik`, laporan komisi lintas cabang.

**Tabel/File terkait:** `TBLMekanik`, `tblmekanik`, `TBLMekanik_CIKDITIRO`

**Sumber:** Audit Tahap 2 — T-03

---

### C-25

| Field | Isi |
|---|---|
| **Kategori** | Piutang Customer |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah ada customer yang boleh hutang (bayar nanti) untuk servis? Jika iya:

- **(A)** Siapa yang berwenang approve kredit servis?
- **(B)** Berapa maksimal hutang yang dibolehkan?
- **(C)** Bagaimana cara pelunasannya di sistem?

**Mengapa penting:**
Di Access ada `TBLPiutangHeader` dan `TBLPiutangDetail`. Web app punya modul piutang. Tapi aturan kreditnya belum jelas.

**Risiko jika salah:**
Customer hutang tidak terkontrol → kerugian bengkel.

**Keputusan yang dipengaruhi:**
Modul piutang, limit kredit per customer.

**Tabel/File terkait:** `TBLPiutangHeader`, `tblpiutang_header`

**Sumber:** ACCESS_FEATURE_ANALYSIS — cluster piutang

---

### C-26

| Field | Isi |
|---|---|
| **Kategori** | Harga Jual Sparepart di Servis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Harga sparepart yang ditagihkan ke customer saat servis menggunakan harga mana:

- **(A)** HargaJual1 (harga normal)
- **(B)** HargaJual2 atau HargaJual3 (harga khusus)
- **(C)** HPP + margin yang ditetapkan manajemen
- **(D)** Bisa berbeda tergantung jenis pelanggan/member

**Mengapa penting:**
Di Access ada `HargaJual`, `HargaJual2`, `HargaJual3`, `HJQtyD2`, `HJQtyD3`, `HJQtyS1` — multiple price tiers. Web app belum tentu mengimplementasikan semua ini.

**Risiko jika salah:**
Customer dikenai harga yang salah. Margin tidak sesuai target.

**Keputusan yang dipengaruhi:**
Logic pemilihan harga di form servis.

**Tabel/File terkait:** `TBLItem.HargaJual`, `TBLItem.HargaJual2`, `TBLItem.HargaJual3`

**Sumber:** ACCESS_FEATURE_ANALYSIS — cluster item

---

### C-27

| Field | Isi |
|---|---|
| **Kategori** | Diskon Servis Standar |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan query `SERVIS_STANDAR_TERAKHIR_DISKON`. Apakah ada diskon khusus untuk paket servis standar? Siapa yang menetapkan diskon ini dan kapan berlaku?

**Mengapa penting:**
Fitur ini tidak ada di web app saat ini.

**Risiko jika salah:**
Diskon tidak diberikan → customer tidak puas. Atau diskon salah diterapkan → margin turun.

**Keputusan yang dipengaruhi:**
Field diskon di servis, tabel paket servis standar.

**Tabel/File terkait:** `SERVIS_STANDAR_TERAKHIR_DISKON`

**Sumber:** ACCESS_FEATURE_ANALYSIS

---

### C-28

| Field | Isi |
|---|---|
| **Kategori** | Insentif — Periode Siklus |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan `INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS` dan tabel `SIKLUS_*` per cabang. Apa yang dimaksud "siklus" dalam konteks komisi? Apakah ini periode mingguan, bulanan, atau per proyek?

**Mengapa penting:**
"Siklus" mungkin adalah periode pembayaran komisi yang berbeda antar cabang.

**Risiko jika salah:**
Komisi dibayar di waktu yang salah. Mekanik protes.

**Keputusan yang dipengaruhi:**
Kapan komisi dihitung dan dibayarkan, periode laporan komisi.

**Tabel/File terkait:** `SIKLUS_CIKDITIRO`, `INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS`

**Sumber:** FITMOTOR GABUNG audit

---

### C-29

| Field | Isi |
|---|---|
| **Kategori** | Member & Loyalty |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan `DATA_MEMBER` dan `TIPE_MEMBER_PELANGGAN`. Apakah ada program member untuk customer? Jika iya:

- **(A)** Apa benefit member (diskon, prioritas, poin)?
- **(B)** Bagaimana cara jadi member?
- **(C)** Apakah member berpengaruh ke harga servis/sparepart?

**Mengapa penting:**
Fitur member tidak terlihat di web app saat ini.

**Risiko jika salah:**
Benefit member tidak diberikan → customer tidak puas. Atau double benefit.

**Keputusan yang dipengaruhi:**
Field `tipe_member` di pelanggan, logic diskon member.

**Tabel/File terkait:** `DATA_MEMBER`, `TIPE_MEMBER_PELANGGAN`, `TBLPelanggan`

**Sumber:** ACCESS_FEATURE_ANALYSIS

---

### C-30

| Field | Isi |
|---|---|
| **Kategori** | Promosi — Cuci Motor Gratis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Di Access ditemukan `GRATIS_CUCI_MOTOR`, `GRATIS_CUCI_MOTOR_89RIBU`, dan `GRATIS_CUCI_MOTOR_PERIODE`. Ada program gratis cuci motor dengan kondisi tertentu. Apa kondisinya? Apakah masih berlaku?

**Mengapa penting:**
Program ini tidak ada di web app. Jika masih aktif, perlu diimplementasikan.

**Risiko jika salah:**
Benefit tidak diberikan ke customer yang berhak → reputasi turun.

**Keputusan yang dipengaruhi:**
Logika promosi di servis, integrasi dengan laporan.

**Tabel/File terkait:** `GRATIS_CUCI_MOTOR`, `CUCI_MOTOR`, `GRATIS_CUCI_MOTOR_89RIBU`

**Sumber:** ACCESS_FEATURE_ANALYSIS

---

### C-31

| Field | Isi |
|---|---|
| **Kategori** | Mekanik Diganti di Tengah Servis |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika mekanik yang mengerjakan motor harus diganti (sakit, pulang, atau customer minta ganti), bagaimana sistem mencatat ini?

- **(A)** Mekanik lama dihapus, diganti mekanik baru
- **(B)** Kedua mekanik dicatat (multi-mekanik)
- **(C)** Tidak pernah terjadi

**Mengapa penting:**
Histori pengerjaan tidak akurat jika mekanik diganti tanpa prosedur.

**Risiko jika salah:**
Komisi diberikan ke mekanik yang tidak mengerjakan. Histori tidak akurat untuk evaluasi kualitas.

**Keputusan yang dipengaruhi:**
Apakah perlu log perubahan mekanik.

**Tabel/File terkait:** `TBLService.Mekanik1`–`Mekanik4`, `tbservis.mekanik`

**Sumber:** Analisa tambahan — edge case

---

### C-32

| Field | Isi |
|---|---|
| **Kategori** | Bagi Hasil Per Cabang |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Tabel `BAGI_HASIL` di Access memiliki kolom `STS` (kode cabang) dan `KATEGORI` (BARANG/JASA). Artinya setiap cabang bisa punya persentase bagi hasil yang BERBEDA. Apakah ini benar? Berapa persen masing-masing cabang untuk barang dan jasa?

**Mengapa penting:**
Jika persentase berbeda per cabang tapi web app menggunakan satu angka global, semua laporan komisi cabang salah.

**Risiko jika salah:**
Komisi mekanik di setiap cabang tidak akurat.

**Keputusan yang dipengaruhi:**
Tabel bagi_hasil di web, konfigurasi per cabang.

**Tabel/File terkait:** `BAGI_HASIL.STS`, `BAGI_HASIL.PERSEN_BAGIHASIL`

**Sumber:** Audit Tahap 2 — T-05

---

### C-33

| Field | Isi |
|---|---|
| **Kategori** | Pajak |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Apakah ada PPN atau pajak lain yang dikenakan pada servis atau penjualan? Jika iya:

- **(A)** Berapa persennya?
- **(B)** Apakah semua cabang sama?
- **(C)** Apakah ada customer tertentu yang dikenai pajak berbeda (corporate vs retail)?

**Mengapa penting:**
Di tabel pembelian dan penjualan ada field `pajak` dan `total_pajak`. Tapi tidak jelas kapan dan berapa pajaknya.

**Risiko jika salah:**
Laporan pajak salah → kewajiban perpajakan tidak terpenuhi.

**Keputusan yang dipengaruhi:**
Setting pajak di master, laporan pajak.

**Tabel/File terkait:** `tblpembelian_header.pajak`, `tblpenjualan_header.pajak`

**Sumber:** ACCESS_TO_MYSQL_TABLE_MAPPING

---

### C-34

| Field | Isi |
|---|---|
| **Kategori** | Motor Tidak Diambil |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Jika motor sudah selesai servis dan sudah dibayar tetapi tidak diambil dalam waktu lama, apakah ada:

- **(A)** Biaya parkir/titip?
- **(B)** Notifikasi ke customer setelah berapa hari?
- **(C)** Prosedur untuk motor yang tidak diambil sangat lama?

**Mengapa penting:**
Di Access ditemukan `Q_SURAT_AMBILMOTOR` — ada surat resmi untuk kondisi ini.

**Risiko jika salah:**
Motor tidak diambil tidak terlacak. Tidak ada eskalasi.

**Keputusan yang dipengaruhi:**
Status "motor menunggu diambil", laporan motor pending.

**Tabel/File terkait:** `Q_SURAT_AMBILMOTOR`, `tbservis.status_servis`

**Sumber:** ACCESS_FEATURE_ANALYSIS

---

### C-35

| Field | Isi |
|---|---|
| **Kategori** | Outsource / Jasa Pihak Ketiga |
| **Prioritas** | CRITICAL |

**Pertanyaan:**
Dalam formula komisi mekanik ditemukan komponen `OUTSRC` (outsource). Apa yang dimaksud outsource di sini? Apakah ada pekerjaan yang disubkontrakan ke pihak luar? Bagaimana biayanya dicatat?

**Mengapa penting:**
Jika ada biaya outsource yang dikurangkan dari basis komisi, nilainya harus dicatat di sistem. Saat ini tidak ada field untuk ini.

**Risiko jika salah:**
Komisi mekanik dihitung dari nilai yang terlalu besar (tanpa dikurangi biaya outsource).

**Keputusan yang dipengaruhi:**
Field outsource di detail jasa servis.

**Tabel/File terkait:** `DATA_INSENTIF_SERVIS.OUTSRC`, `TBLServiceJasaDt`

**Sumber:** Audit Tahap 2 — T-05

---

# BAGIAN 2 — HIGH PRIORITY QUESTIONS

> Mempengaruhi workflow operasional, pelayanan customer, UI, dan approval.

---

### H-01 — Approval Customer Sebelum Dikerjakan

**Pertanyaan:** Apakah ada proses approval sebelum mekanik mulai mengerjakan motor? Misalnya: customer harus setuju dengan estimasi biaya dulu (SPK/Work Order disetujui)?

**Mengapa penting:** Tidak ada status "menunggu persetujuan customer" di sistem saat ini.

**Risiko:** Mekanik mulai kerja sebelum customer setuju harga → dispute.

**Keputusan:** Status "approval customer" di workflow servis.

**Siapa menjawab:** Owner + Kepala Bengkel

---

### H-02 — Proses QC Setelah Mekanik Selesai

**Pertanyaan:** Apakah ada proses Quality Control (QC) setelah mekanik selesai mengerjakan, sebelum motor diserahkan ke customer?

**Mengapa penting:** Field `status_qc` ada di tabel pembelian tapi tidak ada di tabel servis.

**Risiko:** Motor dikembalikan ke customer sebelum dicek → comeback tinggi.

**Keputusan:** Perlu status QC di enum servis. Siapa yang melakukan QC.

**Siapa menjawab:** Kepala Bengkel

---

### H-03 — Alur Servis Jemput Antar

**Pertanyaan:** Untuk servis jemput antar: siapa yang menginput order jemput? Siapa yang menjemput — mekanik yang sama atau kurir terpisah? Setelah selesai, siapa yang mengantar kembali? Biaya jemput masuk ke mana — ke servis atau transaksi terpisah?

**Mengapa penting:** Di Access ada tabel `TBLService_JEMPUTANTAR` terpisah dari `TBLService`.

**Risiko:** Biaya jemput tidak tercatat. Mekanik/kurir tidak jelas tugasnya.

**Keputusan:** Apakah jemput antar adalah sub-modul servis atau modul terpisah.

**Tabel/File:** `TBLService_JEMPUTANTAR`, `tbservis_jemputantar`, `save_antar_jemput.php`

**Siapa menjawab:** Kepala Bengkel + Owner

---

### H-04 — Tarif Jemput Antar

**Pertanyaan:** Tarif jemput antar dihitung berdasarkan apa: jarak, zona, flat fee, atau free untuk transaksi di atas nominal tertentu?

**Risiko:** Tarif salah → pendapatan jemput tidak akurat.

**Tabel/File:** `master-tarif-jemput.php`, `tbservis`

**Siapa menjawab:** Owner

---

### H-05 — Sistem Reminder Servis

**Pertanyaan:** Di Access ditemukan `REMINDER_JADWAL_SERVIS`. Bagaimana reminder ini bekerja? Via WA atau SMS? Kapan dikirim — berapa hari/bulan sebelum perkiraan servis berikutnya? Otomatis oleh sistem atau manual oleh staff?

**Risiko:** Reminder tidak terkirim → customer lupa servis → pendapatan turun.

**Tabel/File:** `REMINDER_JADWAL_SERVIS`, `REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA`

**Siapa menjawab:** Owner

---

### H-06 — Estimasi Servis Berikutnya

**Pertanyaan:** Bagaimana sistem menghitung estimasi kapan customer harus servis berikutnya? Berdasarkan km, tanggal, atau keduanya (mana lebih dulu)?

**Risiko:** Reminder dikirim terlalu awal atau terlalu lambat.

**Tabel/File:** `REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA`, `tbkendaraan`

**Siapa menjawab:** Kepala Bengkel

---

### H-07 — Satu Customer, Banyak Motor

**Pertanyaan:** Satu customer bisa punya lebih dari satu kendaraan? Apakah satu nomor polisi bisa berpindah owner (motor dijual)? Histori servis terikat ke nomor polisi atau ke customer?

**Risiko:** Histori tercampur. Reminder dikirim ke pemilik lama.

**Tabel/File:** `TBLKendaraan`, `tbkendaraan`, `tbpelanggan`

**Siapa menjawab:** Owner + Kepala Bengkel

---

### H-08 — Layanan Pajak Kendaraan

**Pertanyaan:** Di Access ditemukan `INFO_PAJAK_MOTOR`. Apakah bengkel melayani jasa pengurusan pajak kendaraan? Jika iya, bagaimana alurnya dan bagaimana dicatat di sistem?

**Risiko:** Pendapatan dari jasa pajak tidak tercatat.

**Siapa menjawab:** Owner

---

### H-09 — Beli Sparepart Sendiri, Minta Pasang di Bengkel

**Pertanyaan:** Apakah customer bisa beli sparepart di counter lalu minta dipasang di servis dalam kunjungan yang sama? Jika iya, bagaimana cara mencegah double potong stok?

**Risiko:** Stok minus ganda.

**Siapa menjawab:** Kasir + Kepala Bengkel

---

### H-10 — Aturan Antrian

**Pertanyaan:** Apakah ada aturan antrian berdasarkan waktu datang, jenis servis, atau status member? Berapa kapasitas maksimal antrian per hari per mekanik?

**Risiko:** Antrian tidak fair → customer tidak puas → mekanik kelebihan beban.

**Tabel/File:** `dashboard-antrian-servis.php`

**Siapa menjawab:** Owner + Kepala Bengkel

---

### H-11 — Scope Laporan Tutup Kasir Harian

**Pertanyaan:** Laporan tutup kasir harian harus mencakup: hanya servis? servis + penjualan? servis + penjualan + penerimaan piutang? Atau semua kas masuk dan keluar?

**Risiko:** Kasir tidak bisa rekonsiliasi. Uang kas tidak cocok dengan laporan.

**Tabel/File:** `TRANSAKSI_HARIAN_KASIR`, `REKAP_HARIAN`

**Siapa menjawab:** Kasir + Owner

---

### H-12 — Approval Diskon

**Pertanyaan:** Siapa yang berhak memberikan diskon pada servis atau penjualan? Apakah mekanik bisa langsung, harus kasir, atau harus approval kepala bengkel di atas nominal tertentu?

**Risiko:** Fraud diskon. Margin turun tidak terkontrol.

**Siapa menjawab:** Owner

---

### H-13 — Pelunasan Hutang Supplier

**Pertanyaan:** Untuk pembelian kredit ke supplier, pelunasan dibayar per invoice atau rekap per supplier per periode? Ada jadwal jatuh tempo otomatis?

**Risiko:** Hutang supplier tidak terlacak → hubungan supplier rusak.

**Siapa menjawab:** Owner + Akuntan

---

### H-14 — Approval Pembelian / Purchase Requisition

**Pertanyaan:** Untuk pembelian sparepart, apakah ada prosedur PR (Purchase Requisition) sebelum beli? Approval hanya di atas nilai tertentu?

**Mengapa penting:** Ada field `no_pr` di tabel pembelian MySQL — menunjukkan ada konsep PR.

**Risiko:** Pembelian tanpa kontrol → pengeluaran tidak terkendali.

**Siapa menjawab:** Owner + Gudang

---

### H-15 — Mapping Hak Akses Per Role

**Pertanyaan:** Daftarkan level hak akses yang ada di bengkel dan apa yang masing-masing boleh lakukan:

| Role | Yang Boleh Dilakukan | Yang Tidak Boleh |
|---|---|---|
| Owner / Pemilik | ? | ? |
| Kepala Bengkel | ? | ? |
| Service Advisor | ? | ? |
| Mekanik | ? | ? |
| Kasir | ? | ? |
| Gudang / Stok | ? | ? |
| Admin Cabang | ? | ? |
| Admin Pusat | ? | ? |

**Risiko:** Staff bisa mengakses data yang tidak seharusnya → fraud. Atau tidak bisa akses yang dibutuhkan → operasional terhambat.

**Siapa menjawab:** Owner (semua divisi)

---

### H-16 — Format Laporan Laba Rugi

**Pertanyaan:** Di Access ditemukan banyak query laba rugi (`LABARUGI_ACUAN_*`). Laporan L/R yang ingin dilihat owner mencakup apa saja? Per cabang atau gabungan? Breakdown servis vs penjualan vs biaya operasional?

**Risiko:** Owner membaca angka yang tidak sesuai ekspektasi → keputusan bisnis salah.

**Tabel/File:** `LABARUGI_ACUAN_*`, `GABUNG_SERVISJUAL_*`

**Siapa menjawab:** Owner + Akuntan

---

### H-17 — Bahan Habis Pakai

**Pertanyaan:** Di Access ditemukan `BAHAN_HABIS_PAKAI_SERVIS`. Apakah ada bahan habis pakai (majun, amplas, dll) yang perlu dilacak penggunaannya per servis?

**Risiko:** Stok bahan habis pakai tidak akurat. Biaya tidak masuk HPP servis.

**Tabel/File:** `BAHAN_HABIS_PAKAI_SERVIS`

**Siapa menjawab:** Gudang + Kepala Bengkel

---

### H-18 — Piutang Antar Cabang

**Pertanyaan:** Saat pusat menjual ke cabang mitra dengan tempo, piutang tersebut dicatat di mana dan siapa yang bertanggung jawab menagih?

**Risiko:** Piutang antar cabang tidak terlacak → kerugian pusat.

**Tabel/File:** `PENJUALAN_ANTAR_CABANG_PIUTANG`, `tblorderjual_header`

**Siapa menjawab:** Owner

---

### H-19 — Notifikasi Internal

**Pertanyaan:** Apakah perlu notifikasi internal saat: servis baru masuk, servis selesai dikerjakan, motor siap diambil, stok barang habis, pembayaran diterima?

**Risiko:** Komunikasi antar staff manual → delay → customer tidak puas.

**Siapa menjawab:** Owner + Kepala Bengkel

---

### H-20 — Input Km Motor

**Pertanyaan:** Apakah km odometer motor dicatat saat servis? Di mana disimpan — di header servis atau di data kendaraan?

**Risiko:** Reminder servis berikutnya tidak akurat jika basis km tidak tercatat.

**Siapa menjawab:** Kepala Bengkel

---

### H-21 — Garansi Kedua Kali

**Pertanyaan:** Jika customer kembali dengan masalah yang sama untuk kedua kalinya setelah garansi pertama, apakah masih dilayani gratis atau sudah bayar?

**Risiko:** Inkonsistensi pelayanan antar cabang.

**Siapa menjawab:** Owner

---

### H-22 — Onboarding Customer Baru

**Pertanyaan:** Jika customer datang dengan motor yang nomor polisinya belum terdaftar, apakah didaftarkan dulu ke master kendaraan, atau bisa langsung input servis dengan nomor baru?

**Risiko:** Duplikasi data pelanggan.

**Siapa menjawab:** Kepala Bengkel

---

### H-23 — Mekanik Lihat Komisi Sendiri

**Pertanyaan:** Apakah mekanik bisa melihat rekap komisinya sendiri di sistem, atau hanya manajemen yang bisa lihat?

**Risiko:** Mekanik tidak bisa verifikasi komisinya → potensi dispute.

**Siapa menjawab:** Owner

---

### H-24 — Stok Minimum dan Alert

**Pertanyaan:** Apakah ada pengaturan stok minimum per item yang jika terlampaui harus ada alert atau otomatis buat PO?

**Risiko:** Stok habis saat dibutuhkan → customer kecewa.

**Siapa menjawab:** Owner + Gudang

---

### H-25 — Supplier Default Per Item

**Pertanyaan:** Apakah setiap sparepart punya supplier default (utama)? Atau bisa beli dari supplier manapun sesuai ketersediaan?

**Risiko:** Pembelian dari supplier yang salah (harga lebih mahal atau kualitas berbeda).

**Siapa menjawab:** Gudang

---

### H-26 — Prosedur Kas Awal / Akhir

**Pertanyaan:** Apakah ada prosedur buka tutup kasir setiap hari? Siapa yang input kas awal? Siapa yang verifikasi kas akhir? Jika ada selisih, siapa yang bertanggung jawab?

**Risiko:** Selisih kas tidak diketahui siapa yang bertanggung jawab.

**Tabel/File:** `kas_awal.php`, `kas_akhir.php`

**Siapa menjawab:** Owner + Kasir

---

### H-27 — Visibilitas Data Lintas Cabang

**Pertanyaan:** Siapa yang bisa melihat data lebih dari satu cabang? Owner bisa lihat semua? Admin cabang hanya cabang sendiri? Ada role "Manager Area"?

**Risiko:** Cabang A bisa lihat data cabang B → privasi data internal bocor.

**Siapa menjawab:** Owner

---

### H-28 — Booking vs Walk-in

**Pertanyaan:** Apakah ada perbedaan alur antara customer yang booking sebelumnya dan customer yang walk-in langsung? Apakah customer booking diprioritaskan dalam antrian?

**Risiko:** Sistem booking tidak berguna jika tidak ada perbedaan alur.

**Tabel/File:** `_booking`, `dashboard-antrian-servis.php`

**Siapa menjawab:** Kepala Bengkel

---

### H-29 — Barcode dan Kecepatan Input Item

**Pertanyaan:** Apakah semua sparepart punya barcode? Bagaimana cara pencarian item saat input servis — kode item, nama, atau scan barcode?

**Risiko:** Input sparepart lambat → antrian menumpuk.

**Tabel/File:** `TBLItem.KodeBarCode`, `tblitem.kodebarcode`

**Siapa menjawab:** Gudang

---

### H-30 — Mekanik Tetap vs Freelance

**Pertanyaan:** Apakah semua mekanik adalah karyawan tetap, atau ada mekanik freelance/harian? Bagaimana sistem membedakan keduanya untuk perhitungan komisi?

**Risiko:** Komisi dibayarkan ke ID yang tidak aktif atau tidak terdaftar.

**Tabel/File:** `tblmekanik`, `tbuser_karyawan`

**Siapa menjawab:** Owner + HRD

---

# BAGIAN 3 — MEDIUM PRIORITY QUESTIONS

> Mengenai tampilan, laporan, filter, dan pencarian.

| ID | Pertanyaan | Siapa Menjawab |
|---|---|---|
| M-01 | Laporan servis — filter apa saja yang dibutuhkan (tanggal, mekanik, jenis servis, cabang, status)? | Owner + Kepala Bengkel |
| M-02 | Laporan penjualan — perlu laporan per sales/counter staff atau hanya per cabang? | Owner |
| M-03 | Laporan stok — kapan stok opnam rutin dilakukan? Format laporan yang diinginkan? | Owner + Gudang |
| M-04 | Di nota/struk servis, apakah nama mekanik harus tampil? | Owner |
| M-05 | Apakah customer bisa minta cetak ulang nota servis yang sudah lama? Berapa lama histori harus tersedia? | Owner |
| M-06 | Format nomor servis saat ini (tahun+cabang+sequence) — sudah sesuai keinginan? | Owner |
| M-07 | Apakah mekanik bisa melihat laporan komisinya sendiri di sistem? | Owner |
| M-08 | Apakah ada laporan "servis per merek/tipe motor" untuk analisis pasar? | Owner |
| M-09 | Dashboard owner — KPI apa yang paling penting di halaman utama? | Owner |
| M-10 | Apakah ada laporan "barang tidak laku" atau "barang paling banyak keluar" yang rutin dilihat? | Owner + Gudang |
| M-11 | Format tanggal di laporan — harus dd/mm/yyyy atau bisa fleksibel? | Owner |
| M-12 | Apakah laporan laba rugi perlu breakdown per jenis (laba servis, laba penjualan, biaya operasional)? | Owner + Akuntan |
| M-13 | Apakah perlu laporan khusus untuk servis jemput antar terpisah dari servis reguler? | Owner |
| M-14 | Apakah perlu aging report piutang (0–30 hari, 31–60 hari, 60+ hari)? | Owner + Akuntan |
| M-15 | Apakah perlu laporan "comeback rate" atau "garansi rate" per mekanik untuk evaluasi kualitas? | Owner + Kepala Bengkel |
| M-16 | Apakah perlu laporan perbandingan antar cabang (benchmark performa)? | Owner |
| M-17 | Bagaimana format ideal laporan untuk diserahkan ke Pak Novian setiap hari/minggu/bulan? | Owner |
| M-18 | Apakah perlu export laporan ke Excel? Format apa yang paling sering dipakai? | Owner |
| M-19 | Histori kendaraan — apakah perlu tampil tanggal, km, jenis servis, total biaya, mekanik sekaligus? | Owner |
| M-20 | Apakah ada pelanggan korporat (perusahaan) yang motornya banyak dan perlu laporan per perusahaan? | Owner |
| M-21 | Apakah nota servis perlu tanda tangan digital atau cukup nomor transaksi sebagai bukti? | Owner |
| M-22 | Apakah ada format invoice berbeda untuk customer retail vs korporat? | Owner |
| M-23 | Apakah perlu laporan "stok bergerak vs tidak bergerak" per periode? | Owner + Gudang |
| M-24 | Apakah tampilan antrian di monitor bengkel (TV/display) dibutuhkan? | Owner + Kepala Bengkel |
| M-25 | Apakah perlu laporan rekonsiliasi antara kas fisik dengan sistem setiap hari? | Owner + Kasir |

---

# BAGIAN 4 — LOW PRIORITY QUESTIONS

> Pertanyaan kosmetik, preferensi tampilan, non-operasional.

| ID | Pertanyaan | Siapa Menjawab |
|---|---|---|
| L-01 | Warna/tema aplikasi — ada preferensi warna khusus untuk brand bengkel? | Owner |
| L-02 | Apakah logo bengkel perlu muncul di nota servis yang dicetak? | Owner |
| L-03 | Nama bengkel di struk — perlu alamat lengkap dan nomor telepon? | Owner |
| L-04 | Bahasa antarmuka — seluruh UI harus Bahasa Indonesia atau bisa campur Inggris untuk istilah teknis? | Owner |
| L-05 | Apakah karyawan yang tidak melek teknologi perlu mode "simplified UI"? | Owner |
| L-06 | Apakah nota servis perlu QR code untuk validasi keaslian? | Owner |
| L-07 | Apakah footer nota perlu kalimat "Terima kasih atas kepercayaan Anda"? | Owner |
| L-08 | Apakah ada preferensi font atau ukuran tulisan untuk struk cetak di printer thermal? | Owner |
| L-09 | Notifikasi WA ke customer setelah servis selesai — perlu pesan template atau cukup nomor servis? | Owner |
| L-10 | Apakah foto motor perlu diupload saat check-in servis (dokumentasi kondisi sebelum)? | Owner |
| L-11 | Apakah ada kebutuhan mode gelap (dark mode) untuk aplikasi? | Owner |
| L-12 | Apakah perlu fitur pencarian global (cari apa saja di satu tempat)? | Owner |
| L-13 | Apakah ada standar pengisian nama pelanggan (kapital semua atau title case)? | Owner |
| L-14 | Apakah perlu fitur "favorit" untuk mekanik atau item yang sering digunakan? | Owner |
| L-15 | Apakah alamat pelanggan perlu divalidasi terhadap database wilayah (kecamatan/kelurahan)? | Owner |
| L-16 | Apakah perlu fitur multi-bahasa? | Owner |
| L-17 | Apakah tampilan laporan perlu grafik/chart atau cukup tabel angka? | Owner |
| L-18 | Apakah perlu fitur backup data otomatis yang bisa diatur jadwalnya oleh owner? | Owner |
| L-19 | Apakah ada kebutuhan notifikasi ulang tahun pelanggan untuk program loyalitas? | Owner |
| L-20 | Apakah ada kebutuhan akses mobile/responsive untuk mekanik di lapangan? | Owner |

---

# BAGIAN 5 — DECISION MATRIX

| No | Pertanyaan Ringkas | Siapa Menjawab | Dampak | Prioritas |
|---|---|---|---|---|
| C-01 | Kapan servis dianggap "selesai" bisnis? | Owner + Kepala Bengkel | Omset, Komisi, Status | CRITICAL |
| C-02 | Urutan status servis yang benar? | Owner + Kepala Bengkel | Workflow seluruh sistem | CRITICAL |
| C-03 | Kapan stok dipotong saat servis? | Owner + Gudang | Akurasi stok | CRITICAL |
| C-04 | Stok kembali jika servis batal? | Owner | Akurasi stok | CRITICAL |
| C-05 | Prosedur cancel servis sudah bayar? | Owner + Kasir | Kas, Stok, Histori | CRITICAL |
| C-06 | Sparepart sudah pasang, servis batal? | Owner + Kepala Bengkel | Stok, Kerugian | CRITICAL |
| C-07 | Formula komisi mekanik masih berlaku? | Owner | Seluruh laporan komisi | CRITICAL |
| C-08 | "Admin" di formula komisi = siapa? | Owner | Pembayaran komisi | CRITICAL |
| C-09 | Komisi ikut berubah jika invoice direvisi? | Owner | Akurasi komisi | CRITICAL |
| C-10 | Mekanik dapat komisi untuk servis garansi? | Owner | Komisi, Motivasi mekanik | CRITICAL |
| C-11 | Definisi dan durasi garansi servis? | Owner | Pelayanan, Histori | CRITICAL |
| C-12 | Stok sparepart garansi dipotong normal? | Owner + Gudang | Stok, HPP | CRITICAL |
| C-13 | Omset harian dibaca dari tanggal apa? | Owner + Kasir | Laporan omset | CRITICAL |
| C-14 | Servis dan penjualan counter digabung di laporan mana? | Owner | Format laporan | CRITICAL |
| C-15 | Komisi multi-mekanik rata atau tidak? | Owner + Kepala Bengkel | Keadilan komisi | CRITICAL |
| C-16 | Ada konsep "kepala mekanik" dengan porsi beda? | Owner | Formula komisi | CRITICAL |
| C-17 | Advisor dapat insentif terpisah? | Owner | Insentif advisor | CRITICAL |
| C-18 | Metode HPP yang dipakai (FIFO/avg/last)? | Owner + Akuntan | Laporan L/R | CRITICAL |
| C-19 | Servis kapan tampil di histori kendaraan? | Owner | Histori customer | CRITICAL |
| C-20 | Prosedur stok opnam dan selisih? | Owner + Gudang | Kontrol stok | CRITICAL |
| C-21 | Boleh split payment (tunai + transfer)? | Owner + Kasir | Rekonsiliasi kas | CRITICAL |
| C-22 | Ada mekanisme DP untuk servis? | Owner + Kasir | Kas, Piutang | CRITICAL |
| C-23 | Prosedur rekonsiliasi antar cabang? | Owner | Laporan gabungan | CRITICAL |
| C-24 | Mekanik bisa lintas cabang atau terikat? | Owner + Kepala Bengkel | Laporan komisi cabang | CRITICAL |
| C-25 | Customer boleh hutang untuk servis? | Owner | Piutang, Risiko | CRITICAL |
| C-26 | Harga sparepart di servis pakai tier mana? | Owner | Margin, Revenue | CRITICAL |
| C-27 | Ada diskon servis standar? Kondisinya? | Owner | Margin | CRITICAL |
| C-28 | "Siklus" dalam konteks insentif = berapa lama? | Owner | Periode komisi | CRITICAL |
| C-29 | Ada program member? Benefitnya apa? | Owner | Harga, Loyalitas | CRITICAL |
| C-30 | Program gratis cuci motor — masih aktif? | Owner | Promosi | CRITICAL |
| C-31 | Mekanik diganti di tengah servis, dicatat bagaimana? | Kepala Bengkel | Histori, Komisi | CRITICAL |
| C-32 | Persentase bagi hasil berbeda per cabang? | Owner | Komisi per cabang | CRITICAL |
| C-33 | Ada PPN atau pajak yang dikenakan? | Owner + Akuntan | Laporan pajak | CRITICAL |
| C-34 | Motor tidak diambil — SOP-nya? | Owner + Kepala Bengkel | Operasional | CRITICAL |
| C-35 | Outsource = apa dalam formula komisi? | Owner + Kepala Bengkel | Akurasi komisi | CRITICAL |
| H-01 | Ada approval customer sebelum dikerjakan? | Owner + Kepala Bengkel | Workflow servis | HIGH |
| H-02 | Ada proses QC setelah mekanik selesai? | Kepala Bengkel | Kualitas, Comeback | HIGH |
| H-03 | Alur operasional servis jemput antar? | Kepala Bengkel + Owner | UI jemput antar | HIGH |
| H-04 | Tarif jemput antar dihitung bagaimana? | Owner | Revenue jemput | HIGH |
| H-05 | Sistem reminder — via WA, kapan, berapa hari? | Owner | Retensi customer | HIGH |
| H-06 | Estimasi servis berikutnya dari km atau tanggal? | Kepala Bengkel | Akurasi reminder | HIGH |
| H-07 | Satu customer bisa banyak motor? | Owner | Data pelanggan | HIGH |
| H-08 | Bengkel layani jasa pajak kendaraan? | Owner | Modul baru | HIGH |
| H-09 | Customer beli sparepart lalu pasang di servis? | Kasir | Stok, Invoice | HIGH |
| H-10 | Aturan antrian — prioritas member? Kapasitas mekanik? | Owner + Kepala Bengkel | Operasional | HIGH |
| H-11 | Scope laporan tutup kasir harian? | Kasir + Owner | Rekonsiliasi | HIGH |
| H-12 | Siapa yang berhak beri diskon? Perlu approval? | Owner | Fraud prevention | HIGH |
| H-13 | Pelunasan hutang supplier — per faktur atau rekap? | Owner + Akuntan | Akurasi hutang | HIGH |
| H-14 | Ada prosedur PR sebelum beli? | Owner + Gudang | Kontrol pembelian | HIGH |
| H-15 | Mapping lengkap hak akses per role? | Owner (semua divisi) | RBAC | HIGH |
| H-16 | Format laporan L/R yang biasa dibaca owner? | Owner + Akuntan | Laporan keuangan | HIGH |
| H-17 | Bahan habis pakai perlu dilacak per servis? | Owner + Gudang | HPP, Stok | HIGH |
| H-18 | Piutang antar cabang dikelola siapa? | Owner | Keuangan cabang | HIGH |
| H-19 | Notifikasi internal untuk event apa saja? | Owner + Kepala Bengkel | Operasional | HIGH |
| H-20 | Km motor dicatat saat servis? | Kepala Bengkel | Reminder, Histori | HIGH |
| H-21 | Garansi kedua kali — masih gratis? | Owner | Biaya garansi | HIGH |
| H-22 | Motor baru — input pelanggan dulu atau langsung servis? | Kepala Bengkel | UI, Alur | HIGH |
| H-23 | Mekanik bisa lihat rekap komisinya sendiri? | Owner | Transparansi | HIGH |
| H-24 | Ada stok minimum per item? Alert jika habis? | Owner + Gudang | Kelancaran servis | HIGH |
| H-25 | Setiap item punya supplier default? | Gudang | Pengadaan | HIGH |
| H-26 | Prosedur kas awal/akhir — siapa bertanggung jawab? | Owner + Kasir | Kontrol kas | HIGH |
| H-27 | Siapa yang bisa lihat data lintas cabang? | Owner | RBAC, Privasi | HIGH |
| H-28 | Alur booking vs walk-in berbeda? | Kepala Bengkel | Antrian, UI | HIGH |
| H-29 | Semua item punya barcode? Cara input saat servis? | Gudang | Kecepatan input | HIGH |
| H-30 | Mekanik tetap vs freelance — cara bedanya? | Owner + HRD | Komisi | HIGH |
| M-01..M-25 | Filter laporan, format, export | Owner + semua departemen | Laporan | MEDIUM |
| L-01..L-20 | Kosmetik, preferensi tampilan | Owner | UI/UX | LOW |

---

## RINGKASAN STATISTIK

| Prioritas | Jumlah |
|---|---|
| CRITICAL | 35 |
| HIGH | 30 |
| MEDIUM | 25 |
| LOW | 20 |
| **Total** | **110** |

---

## CATATAN UNTUK FASILITATOR MEETING

- **Rekam setiap jawaban** — bahkan jawaban "tidak tahu" atau "belum pernah dipikirkan" harus dicatat
- **Jangan asumsikan** — jika owner ragu, tandai sebagai "perlu konfirmasi lanjutan"
- **Bawa contoh visual** — untuk pertanyaan tentang laporan dan status, lebih baik tunjukkan mockup/screenshot daripada hanya bertanya verbal
- **Prioritaskan CRITICAL dulu** — selesaikan semua C-01 sampai C-35 sebelum pindah ke HIGH
- **Estimasi waktu meeting:** CRITICAL saja butuh minimal 3–4 jam. Rekomendasikan 2 sesi terpisah

---

*Dokumen ini dibuat oleh: System Analyst — Bengkel 2.0 Web Migration*
*Berdasarkan reverse engineering FITMOTOR APP.mdb dan FITMOTOR GABUNG.mdb*
*Versi: 1.0 | 2026-06-26*
