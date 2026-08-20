# GAP ANALYSIS — Sistem Access Lama vs Dokumen Requirement vs Web App Sekarang

**Tanggal:** 9 Agustus 2026
**Penyusun:** Agent Arsitektur/Analis
**Sifat:** Read-only. Tidak ada kode yang diubah oleh analisa ini.

---

## 0. Sumber Data & Batasan Metodologi

**Sumber yang dibandingkan (3 sisi):**

| Sisi | Sumber | Cara baca |
|---|---|---|
| Access lama | `/mnt/d/BENGKEL 2.0/FITMOTOR.mdb` (58 tabel, DB operasional), `FITMOTOR APP.mdb` (33 tabel, DB aplikasi/master pendukung), `FITMOTOR GABUNG.mdb` (~125 tabel, konsolidasi lintas cabang) | `mdb-tables`, `mdb-schema` (read-only) |
| Requirement | `docs/summary/SISTEM INFORMASI BENGKEL FIT MOTOR.md` (692 baris, versi paralel .docx) | Read |
| Web app | `app/menu_config.php` (peta menu aktual), 1.003 file di `app/`, `db/migrations/*`, `docs/fsd/*.md`, `docs/CEKLIS_PROGRES_WEB_BENGKEL_2026-07-19.md` | Read + grep ke kode, bukan cuma FSD |

**Batasan yang WAJIB diketahui pembaca:**

1. **mdbtools hanya bisa baca TABEL, tidak bisa baca Form, Report, Query, dan VBA module Access.** Artinya: fitur Access yang implementasinya murni di Form/Report/Query — dan tidak menyisakan jejak berupa tabel — **tidak terdeteksi** oleh analisa ini. Banyak tabel bernama `*_TEMP` di Access sebenarnya adalah "sisa jejak" dari report; itu yang dipakai di sini sebagai petunjuk keberadaan laporan.
2. **Database MySQL produksi tidak bisa diakses dari WSL** (kredensial di `config/koneksi.php` ditolak dari sisi WSL — konsisten dengan catatan environment yang sudah ada). Jadi status "ada/belum" di sisi web disimpulkan dari **kode + menu + migration**, bukan dari `SHOW TABLES` langsung. Untuk item yang bertanda ⚠️ di kolom Catatan, verifikasi ke DB live masih perlu.
3. Analisa berbasis nama tabel **selalu** dikonfirmasi ke struktur kolom (`mdb-schema`) sebelum disimpulkan setara/tidak setara dengan fitur web.

---

## 1. Ringkasan Eksekutif

Web app **sudah jauh lebih lengkap dari yang tercatat di FSD** — modul Master, Servis, Pengadaan (PR→PO→DO→Invoice), Penjualan, Antar Cabang, Penyesuaian Stok, dan Laporan operasional sudah live. Beberapa area justru **melampaui** Access (mis. laporan penyesuaian stok, yang di dokumen requirement disebut "belum tersedia" di Access, tapi di web sudah ada).

Gap yang tersisa terkonsentrasi di **5 kantong besar**:

| # | Kantong Gap | Inti masalah |
|---|---|---|
| 1 | **HPP: FIFO & Acuan HPP 4-pembelian-termahal** | Access punya 5 tabel FIFO. Web: **nol** baris kode menyebut FIFO. Acuan HPP hanya di-*mirror* dari Access, tidak dihitung sendiri. |
| 2 | **Insentif / Bagi Hasil / Siklus** | Master-nya sudah di-sync ke MySQL (`tbpersen_insentif`, `tbbagi_hasil`, `tbsiklus`) tapi **0 file kode yang memakainya**. 2 laporan insentif yang sudah jadi pun tidak ada di menu. |
| 3 | **Akuntansi Keuangan / Laba Rugi** | Access punya kerangka rekap akun (`TBLRekap`, `TBLAkunKas`) + 4 tabel acuan laba rugi. Web belum punya laba rugi/neraca/jurnal sama sekali. |
| 4 | **Data analitik Access yang di-mirror tapi tidak dipakai** | 9 dataset `gabung_*` disinkron rutin, tapi hanya 2 yang punya konsumer di kode. Sisanya data masuk → mengendap. |
| 5 | **Reminder servis & jadwal oli** | Master jadwal penggantian oli tidak ada, `km_berikut` manual, dan data prediksi kedatangan yang sudah di-mirror belum punya UI/scheduler. |

> **Catatan koreksi (9 Agustus 2026):** Modul Kasir (Kas Awal → Kas Akhir/Closing) **sudah ada dan dipakai** — dikonfirmasi langsung oleh tim. Draft awal analisa ini sempat menempatkannya sebagai gap Prioritas 1 karena `kas_awal.php`/`kas_akhir.php` tidak muncul di `menu_config.php`; ternyata itu hanya soal jalur akses/sidebar, bukan soal modulnya belum ada. Sudah dikoreksi di §6.

---

## 2. MODUL: DATA MASTER

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Master Barang, Kategori, Satuan, Pabrik, Rak | Ada (`TBLItem`, `RAKBARANG`, `PABRIK`, `TBLItemSatuan`, `TBLItemJenis`) | Ada, wired | — | — | `menu_config.php:58-62` |
| Master Margin Harga Jual + Harga Jual Plus Jasa | Ada (`HARGA_JUAL` kolom JENIS/MARGIN/MARGINPLUS/BULAT, `HARGA_JUAL_PLUSJASA` JASA/NILAI) | Ada (`margin_jual.php`, `hjual_jasa.php`) | — | — | Struktur kolom Access cocok dengan konsep web |
| Master Status Harga | Ada (`STATUS_HARGA`) | Ada (`status_harga.php`) | — | — | |
| Master WO/Paket (Jasa + Barang) | Ada (docx: PAKET SERVIS LENGKAP) | Ada (`paket.php` + 3 mapping motor) | — | — | Web lebih kaya (mapping WO/Jasa/Item ke jenis motor) |
| Master Pelanggan + Kategori (Silver/Gold/Diamond) | Ada (`TBLPelanggan`, `TBLPelangganGrup`, `DATA_MEMBER`) | Ada + Loyalty Program + Statistik Pelanggan | — | — | Web melampaui Access |
| Master Supplier, Cabang, Tipe Cabang | Ada | Ada + `setting_antarcabang.php` | — | — | `JasaOrderCabangMitra.PersenJasa` (Access) ≈ setting margin antar cabang di web |
| Master Mekanik + Level + Kepala Mekanik | Ada (`TBLMekanik`) | Ada (`mekanik.php`, `mekanik_level.php`, `master_kepala_mekanik.php`) | — | — | ⚠️ Aturan docx "Kepala Mekanik minimal level MAHIR" belum diverifikasi ada enforcement-nya di kode |
| Master Sales | Ada (`TBLSales` — punya `KomisiJual`, `KomisiNominal`, `OpPilSistemKomisi`) | Master ada (`sales.php`); **perhitungan komisi sales tidak ada** | **Sebagian** | Sedang | docx: "SALES akan mendapat insentif/hadiah" (customer-get-customer). Kolom komisi di Access menandakan perhitungannya memang ada di Access |
| Master Tipe/Pabrik/Kategori Motor, Kendaraan, Warna | Ada (`TIPEMOTOR`, `KATEGORIMOTOR`) | Ada semua | — | — | |
| Master Wilayah (Desa/Kec/Kota/Prop) | Diminta docx | Ada 4 level | — | — | |
| Master Akun Kas & Akun Biaya | Ada (`TBLAkunKas`) | Ada | — | — | |
| Master User + Hak Akses | Ada (`TBLUser`, `TBLUserAkses` — granular mOpen/mNew/mEdit/mDel) | Ada, RBAC permission-based | — | — | Model web berbeda (permission string) tapi setara/lebih baik |
| Master Perusahaan (kop/logo nota) | Ada (`TBLPerusahaan`) | Ada (`app/master_perusahaan.php`) | — | — | ⚠️ Tidak ada di `menu_config.php` — perlu cek apakah sengaja |
| **Master Jadwal Penggantian Oli / Periode Item Jasa** | **Ada** (`PERIODE_ITEM_JASA`: NOITEM, NAMAITEM, HARI) + docx eksplisit: "KM berikut bertambah otomatis saat diinput KM Sekarang, mengacu MASTER JADWAL PENGGANTIAN OLI" | **Tidak ada.** `km_berikut` murni input manual (`save_combined_servis.php:92`, `servis-input-reguler-jemput-rst.php:137`), hanya divalidasi > km sekarang (`helper-functions.php:231`) | **Belum Ada** | **Kecil** | Tabel master 3 kolom + auto-fill di form servis. Dampak besar ke akurasi reminder servis |
| **Master Status Kondisi Sparepart** | docx Form Servis poin 5: "nantinya perlu data master status kondisi per jenis sparepart" | Ada `master-temuan.php` + mapping temuan→part/jasa, tapi bukan "status kondisi per jenis sparepart" | **Sebagian** | Kecil | Perlu konfirmasi Owner apakah `master-temuan` sudah dianggap menggantikan konsep ini |
| Master Bagi Hasil (cabang mitra) | Ada (`BAGI_HASIL`: STS, KATEGORI, PERSEN_BAGIHASIL) | Data di-sync ke `tbbagi_hasil`, **0 file kode yang membacanya** | **Sebagian** | Sedang | Lihat §7 Insentif |
| Master Persen Insentif per Posisi | Ada (`PERSEN_INSENTIF`: POSISI, PERSEN_BARANG, PERSEN_JASA) | Data di-sync ke `tbpersen_insentif`, **0 konsumer** | **Sebagian** | Sedang | Lihat §7 |
| Master Siklus (periode komisi) | Ada (`SIKLUS`: SIKLUS, TGLAWAL, TGLAKHIR — per cabang di GABUNG) | Data di-sync ke `tbsiklus`, **0 konsumer**. `siklus_komisi` disebut "belum dibangun" di CEKLIS 19 Juli | **Sebagian** | Sedang | Blocker untuk pembayaran komisi per siklus (pertanyaan Owner B3) |
| Master Vendor Cuci Motor | Ada (`CUCI_MOTOR`: NAMA, ALAMAT) | Tidak ada | **Sengaja Skip (tunda)** | Kecil | CEKLIS 19 Juli: program cuci gratis 🔴 Blocked menunggu keputusan Owner. Konsisten, bukan kelalaian |
| Master Garapan / Daftar Pengerjaan Servis | Ada (`GARAPAN`, `DAFTAR_PENGERJAAN_SERVIS`: PENGERJAAN, KATEGORI) | Ada padanan fungsional: `master-keluhan-crud.php` + `paket.php` (WO) | **Perlu Konfirmasi** | Kecil | Perlu konfirmasi Owner: apakah taksonomi "kategori pengerjaan" Access masih dipakai untuk pengelompokan laporan |
| Master Cabang↔Admin | Ada (`TBLCABANG_ADMIN`) | Tidak ada tabel setara; relasi user–cabang lewat session/RBAC | **Perlu Konfirmasi** | Kecil | Kemungkinan besar sudah tidak relevan di model web |

---

## 3. MODUL: SERVIS

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Servis Reguler (input, cari nopol, antrian, kepala mekanik harian) | Ada | Ada, live | — | — | `menu_config.php:317-327` |
| Servis Garansi (Komplain/Rework, tarik nomor servis sebelumnya) | Ada (docx bagian 2) | Ada (`servis-carinopol-garansi.php` + turunan), garansi dinamis per tier live | — | — | Web melampaui: durasi garansi dinamis per tier member |
| Servis Jemput Antar | Ada (`TBLService_JEMPUTANTAR_{CABANG}`) | Ada + master tarif jemput + tracking keluhan | — | — | |
| Pisah Perintah Kerja / Keluhan / Catatan + status progress | docx: "saat ini masih dalam satu kolom" (= keinginan, belum ada di Access) | **Sudah ada** (`_handler_status_keluhan_wo.php`, master keluhan, mapping keluhan→WO) | — | — | Web menyelesaikan gap yang di docx masih berstatus keinginan |
| Form Servis (temuan, usulan ganti part, estimasi, approval pelanggan) | docx: lembar kertas manual | **Sudah didigitalkan** (`_handler_temuan_penawaran.php` 62KB, master temuan + mapping part & jasa, approve/reject penawaran) | — | — | Salah satu pencapaian terbesar web vs Access |
| Cetak "Form Servis Kosong" (lembar checklist untuk Kepala Mekanik) | docx menampilkan format cetaknya | Tidak ada cetakan form kosong | **Perlu Konfirmasi** | Kecil | Kalau alur sudah full-digital, cetakan ini mungkin memang tidak perlu lagi. Perlu konfirmasi Owner |
| Barang custom (part yang belum ada di master) | docx: "Admin bisa input penawaran sparepart yang belum ada di master" | Ada (`master-barang-custom.php`, `_handler_barang_custom.php`) | — | — | |
| Kontribusi mekanik total 100%, kepala mekanik wajib, min 1 mekanik | docx PENTING | Ada (validasi di alur servis) | — | — | ⚠️ Enforcement "total harus 100%" belum diverifikasi baris-per-baris di sesi ini |
| Diskon jasa/barang otomatis per tipe pelanggan, fleksibel %/Rp | docx PENTING | Ada (promo engine + diskon member + approval diskon) | — | — | Web melampaui (promo engine multi-target/multi-cabang) |
| DP Servis, Retur Servis, Approval Diskon | Tidak eksplisit di Access | Ada, live | — | — | Fitur baru web |
| Komisi mekanik snapshot permanen | Access hitung real-time (temuan audit T-05) | **Sudah live** (commit `b7a242a`, 3 titik bayar → `servis_komisi`) | — | — | Sudah menutup temuan CEKLIS 19 Juli |
| **KM Berikut otomatis dari master jadwal oli** | Ada (`PERIODE_ITEM_JASA`) + docx eksplisit | Manual | **Belum Ada** | Kecil | Duplikat dari §2, ditulis ulang karena dampaknya di form servis |
| **Booking Servis** | docx PELENGKAP, ada mockup | **Nol file.** Grep "booking" hanya nyangkut di `webhook_whatsapp.php` & `_include_statistik_pelanggan.php` (bukan modul booking) | **Belum Ada** | Sedang | Bagian dari daftar PELENGKAP, bukan MANDATORY |
| Status waktu tiap proses (DATANG/DIPROSES/SELESAI/BAYAR) | docx: "STATUS WAKTU TIAP PROSES bisa tercatat" | Ada `kelola-antrian.php` + status servis; **timestamp per tahap belum diverifikasi lengkap** | **Sebagian** ⚠️ | Kecil–Sedang | Perlu cek kolom timestamp di `tblservice` ke DB live |
| Audit servis nomor kosong per admin | Ada (`ADMIN_SERVIS_KOSONG`: Tanggal, NoService, User) | Ada `lap_cancel_servis.php` (mirip tapi belum tentu setara) | **Perlu Konfirmasi** | Kecil | Perlu konfirmasi apakah "servis kosong" = "servis cancel" atau konsep berbeda |
| Reminder jadwal servis ke customer | Ada (`REMINDER_JADWAL_SERVIS`; GABUNG `REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA` punya kolom `DATANG_BERIKUTNYA` & `REMIND_SEBELUMNYA`) | Data sudah di-mirror ke `gabung_rekap_kedatangan`; ada `class_whatsapp_automation.php` + `statistik_pelanggan_send_wa.php`, **tapi tidak ada halaman/penjadwal reminder servis** | **Sebagian** | Sedang | Data & channel WA sudah siap; yang kurang UI kampanye + scheduler. Lihat juga §8 CRM |
| Cuci gratis / poin cuci | Ada (`CUCI_MOTOR`) | Tabel `servis_poin_cuci`, `servis_voucher_cuci` belum dibuat | **Sengaja Skip (tunda)** | Sedang | 🔴 Blocked Owner, tercatat di CEKLIS 19 Juli |

---

## 4. MODUL: PENGADAAN / PEMBELIAN / INVENTORY

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Pesanan Pembelian (PO) | Ada (`TBLOrderHeader`/`TBLOrderDetail`) | Ada + input manual + upload Excel + **approval bertingkat by nilai** | — | — | Web melampaui Access |
| Purchase Request (PR) + auto-draft dari stok minimal | Tidak ada di Access | Ada (`pr_add.php`, `pr_auto_draft.php`) | — | — | Fitur baru web |
| Delivery Order (DO) | Tidak ada di Access | Ada | — | — | Fitur baru web |
| Pembelian / Invoice + Nomor & Tanggal Faktur | Ada (`TBLPembelianHeader`) + docx minta kolom faktur | Ada 3 jalur (dari PO / manual / daftar) | — | — | ⚠️ Kehadiran kolom `no_faktur`/`tgl_faktur` belum diverifikasi ke DB live |
| Retur Pembelian | Ada (`TBLReturBeliHeader`/`Detail`) | Ada + laporan | — | — | |
| Pembayaran Hutang + pelunasan multi-faktur (centang) | Ada (`TBLHutangHeader`/`Detail`) + docx | Ada (`pmby_hutang.php`) | — | — | ⚠️ Fitur "pelunasan beberapa faktur sekaligus via centang" belum diverifikasi |
| Laporan Hutang detail per faktur & total per supplier | docx eksplisit (untuk dikirim ke WA manajemen) | **Ada keduanya** (`laporan_hutang_detail.php`, `laporan_hutang_summary.php`) | — | — | Menutup keinginan docx "nantinya manajemen bisa akses sendiri" |
| Min/Max stok per cabang + penanda warna | docx eksplisit (kuning ≤ min, merah = 0) | Ada (`procurement_dashboard.php`, `rencana_order.php`) | — | — | ⚠️ Penanda warna di master barang belum diverifikasi |
| Alarm Harga Beli (threshold naik/turun) | Ada padanan (`TMP_CEK_PEMBELIAN_NAIK`, `CEK_PEMBELIAN`) | Ada, arsitektur trigger DB | **Sebagian** ⚠️ | Kecil | CEKLIS 19 Juli: tabel `alarm_harga_beli` 0 baris — perlu `SHOW TRIGGERS` untuk pastikan trigger terpasang |
| Kartu Stok | docx ada mockup; Access `TBLItemRekap` + tmp | Ada (`kartu_stok.php` di menu; `barang_kartu_stok.php` di modul barang) | — | — | |
| History Harga Pokok / Pembelian | docx ada mockup; Access `CEK_PEMBELIAN`, `BELI_PERITEM_ALL` | Ada file `barang_history_hp.php`, **tidak ada di `menu_config.php`** | **Sebagian** | Kecil | Kemungkinan dijangkau dari halaman detail barang; perlu verifikasi entry point |
| Penyesuaian Stok Manual (Item Masuk/Keluar) + Otomatis (opnam) | Ada (`TBLItemMHeader/MDetail`, `TBLItemKHeader/KDetail`, `HASIL_STOK_OPNAM_TEMP`, `STOK_OPNAM_TEMP`) | Ada ketiganya + Lihat Stok Akhir | — | — | |
| **Laporan Penyesuaian Stok Masuk/Keluar** | docx menyebut **"Report belum tersedia"** di Access | **Ada** (`lap_stok_masuk.php`, `lap_stok_keluar.php`) | — | — | **Web menutup gap yang di Access memang belum ada** |
| **HPP metode FIFO** | **Ada 5 tabel FIFO** di Access: `TBLItemFifo` (IDInv/RefIDInv/TipeTrs/HargaPokok/Sisa), `TBLPenjualanDtFifo`, `TBLServiceItemDtFifo`, `TBLReturBeliDtFifo`, `TBLItemKDtFifo` + `TBLSetting.SistemHargaPokok` | **Nol.** `grep -i fifo app/*.php` = 0 hasil | **Belum Ada** | **Besar** | `CLAUDE.md` project menuliskan "HPP FIFO" sebagai Rule, tapi belum ada implementasinya. Ini gap paling dalam secara arsitektur (menyentuh pembelian, penjualan, servis, retur, koreksi stok) |
| **Acuan HPP = harga beli termahal dari 4 pembelian terakhir** | docx eksplisit **dua kali** (Master Item & Transaksi Servis); Access punya `LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP` (kolom `ACUAN_HPP`, `FREK`) | Hanya **di-mirror** dari Access ke `gabung_hpp_acuan` (`access-sync.php:59`). **Tidak dihitung sendiri, tidak ada konsumer di kode** | **Belum Ada (perhitungan)** | Sedang | Selama ini web masih bergantung pada Access untuk angka acuan HPP. Ini menghalangi pelepasan Access |
| Update harga jual massal (centang beberapa item) berdasar status harga | docx eksplisit poin 3 | `margin_jual.php` + `status_harga.php` ada; **bulk update belum diverifikasi** | **Sebagian** ⚠️ | Sedang | Perlu cek apakah ada aksi massal di `barang.php` |
| Blokir input barang "stok kosong & harga naik" ke transaksi | docx eksplisit (di Penjualan **dan** Servis) | ⚠️ Belum diverifikasi di sesi ini | **Perlu Konfirmasi** ⚠️ | Kecil | Aturan bisnis penting; layak jadi item verifikasi terpisah |
| Laporan stok tidak laku / slow moving | Ada (`STOK_OPNAM_TIDAKLAKU`: qty per cabang, rak, status) | Tidak ada | **Belum Ada** | Kecil–Sedang | Data pendukung sudah ada di web (stok + transaksi); tinggal query |
| Statistik item per minggu (pola permintaan) | Ada (`GABUNG_STATISTIK_ITEM_MINGGU_PIVOT`, M1–M12) | Di-mirror ke `gabung_statistik_item_minggu`, **0 konsumer** | **Sebagian** | Kecil | Bahan mentah untuk demand planning sudah masuk, tinggal dibikin laporannya |
| RFQ Supplier Response | Tidak ada di Access | Belum dibangun (diakui di `FSD_PENGADAAN_INVENTORY.md` §12) | **Belum Ada** | Sedang | Fitur baru, bukan gap migrasi |

---

## 5. MODUL: PENJUALAN & ANTAR CABANG

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Pesanan Penjualan + Penjualan (pelanggan) | Ada | Ada 3 jalur | — | — | |
| Retur Penjualan | Ada (`TBLReturJualHeader`/`Detail`) | Ada + refund (migration 2026-07-05) | — | — | |
| Piutang + pembayaran | Ada (`TBLPiutangHeader`/`Detail`, `TBLPelangganSA`) | Ada + laporan summary/detail | — | — | |
| Penjualan Antar Cabang — Cabang Sendiri (harga=pokok, tunai, diskon 100%) | docx eksplisit | Ada (`pesanan_penjualan_cab_add.php`, `penjualan_cab_add.php`) | — | — | |
| Penjualan Antar Cabang — Cabang Mitra (pokok + margin 5%, tempo 10 hari, bisa diubah) | docx eksplisit | Ada + `setting_antarcabang.php` (margin & tempo configurable) | — | — | Sesuai docx: nilai bisa diubah |
| Upload Excel pesanan antar cabang | docx eksplisit; Access `TBLPenjualanHeader_UPLOAD` | Ada (`penjualan_antarcab_upload.php`, `pesanan_pembelian_upload.php`) | — | — | |
| Penerimaan Antar Cabang (hanya nota yang belum diterima, ketentuan ikut nota pengirim) | docx eksplisit | Ada (`penerimaan_antarcab.php`, `penerimaan_mitra.php`) | — | — | ⚠️ Aturan "hanya nota yang belum pernah diterima" belum diverifikasi |
| Laporan Pengiriman/Penerimaan antar cabang | — | Ada | — | — | |
| **Rekonsiliasi Jual–Beli Antar Cabang** | Ada (`REKONSIL_JUAL_BELI_ANTARCABANG_DATA` — 23 kolom, pasangan sisi jual vs sisi beli per item) | Di-mirror ke `gabung_rekonsil_antarcabang`, **tidak ada halaman laporan** | **Belum Ada (laporan)** | Sedang | Juga jadi pertanyaan Owner E5 di `GAP_ANALYSIS_RINGKASAN.md`. Data sudah masuk, tinggal UI + aturan siapa yang menyelesaikan selisih |

---

## 6. MODUL: KASIR & KEUANGAN

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| **Kas Awal → Transaksi → Kas Akhir (Closing)** | **docx MANDATORY #2**, lengkap dengan formula `SETORAN RIIL = Kas Awal + Penjualan Tunai + Servis + Uang Masuk − Uang Keluar − Pembelian Tunai`; Access `TBLKasKeluarMasuk`, `TBLTmpKasRekap` | **Sudah ada & dipakai** — `app/kas_awal.php` (44KB), `app/kas_akhir.php` (48KB) + `_proses.php`. Ini closing kasir web yang berjalan (dikonfirmasi tim, 9 Agt 2026) | **—** | — | Catatan minor: halamannya tidak muncul di `menu_config.php` (jalur aksesnya di luar sidebar utama). Bukan gap fitur; kalau mau dirapikan, tinggal ditambah entri menu. Grep kata "closing" di seluruh repo = 0 hasil — penamaannya memang `kas_awal`/`kas_akhir` |
| Multi-shift kasir dalam 1 hari | docx eksplisit ("admin 1 pagi–siang, admin 2 siang–sore") | Ikut modul kasir di atas | **—** | — | Tidak diverifikasi baris-per-baris di sesi ini, tapi bukan gap yang perlu diangkat |
| Kas Masuk / Kas Keluar (input) | Ada (`TBLKasKeluarMasuk`) | Kode ada (`kas_masuk_add.php`, `kas_keluar_add.php`); di menu utama baru LAPORAN-nya (`lap_kas_masuk`, `lap_kas_keluar`) | **—** | — | Pola akses sama dengan kasir — kemungkinan besar juga sudah dipakai lewat jalur non-sidebar |
| Akun Sumber Kas & Akun Biaya | Ada (`TBLAkunKas`) | Ada, wired | — | — | |
| Setting akun default (kas beli/jual/hutang/piutang/servis/biaya op) | Ada (`TBLSetting`: `AkunKasBeli`, `AkunKasJual`, `AkunKasService`, `AkunBiayaOp`, `JTPembayaran`, `SistemHargaPokok`) | ⚠️ Belum ditemukan halaman setting setara | **Perlu Konfirmasi** | Kecil | `TBLSetting.JTPembayaran` (default jatuh tempo) & `SistemHargaPokok` relevan ke gap FIFO |
| **Akuntansi Keuangan / Laba Rugi** | **docx PELENGKAP #6**; Access punya `TBLRekap` (PeriodeRekap/KodeAkun/SaldoAwal/Debet/Kredit/SaldoAkhir) + 4 tabel `LABARUGI_ACUAN_*` (HPP pembelian, qty item masuk/keluar, qty penjualan, qty servis) | **Tidak ada** — grep `labarugi|neraca|jurnal` = 0 file | **Belum Ada** | **Besar** | Struktur `TBLRekap` menunjukkan Access sudah punya kerangka buku besar per periode. Ini modul PELENGKAP, jadi wajar belum, tapi perlu masuk roadmap |
| Saldo Awal (stok, piutang pelanggan, hutang supplier) | Ada (`TBLItemSA`, `TBLPelangganSA`, `TBLSupplierSA`) | Ada sebagian (`supplier_awal_hutang_save.php`, `saldo_awal` di `kartu_stok.php`, `pelanggan.php`) | **Sebagian** ⚠️ | Kecil | Perlu verifikasi apakah ketiga jenis saldo awal punya jalur input yang lengkap |

---

## 7. MODUL: INSENTIF & BAGI HASIL

**Ini kantong gap yang paling "hampir jadi tapi berhenti di tengah".**

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Perhitungan insentif jual & servis per siklus | **docx PELENGKAP #1**; Access `INSENTIF_JUAL_SERVIS_DATA` (16 kolom: SIKLUS, TRX, GARAP, MEKANIK, JUALNET, TOTHPP, LABA, KATEGORI) + `INSENTIF_JUAL_SERVIS_GABUNG_DATA` | `lap_profit_insentif.php` ada — tapi **membaca hasil jadi dari Access** (`tbinsentif_jual_servis`), bukan menghitung sendiri. **Tidak ada di `menu_config.php`** | **Sebagian** | Sedang | Web masih bergantung Access untuk angka insentif. Sejalan dengan gap Acuan HPP (§4) |
| Komisi mekanik (20% jasa / 5% barang ÷ jumlah mekanik) | Access hitung real-time | `lap_komisi_mekanik.php` **menghitung native** (formula sesuai audit T-05) + snapshot `servis_komisi` sudah live | **Sebagian** | Kecil | ⚠️ **`lap_komisi_mekanik.php` juga tidak ada di `menu_config.php`** — laporan sudah jadi tapi tidak bisa dibuka user |
| Persen insentif per posisi | `PERSEN_INSENTIF` (POSISI/PERSEN_BARANG/PERSEN_JASA) | Tabel `tbpersen_insentif` terisi via sync, **0 konsumer**, tidak ada CRUD | **Sebagian** | Sedang | Menjawab pertanyaan Owner B3 ("persentase sama semua cabang atau beda?") butuh master ini hidup |
| Siklus pembayaran komisi | `SIKLUS` per cabang (5 tabel di GABUNG) | `tbsiklus` terisi via sync, **0 konsumer** | **Sebagian** | Sedang | Blocker untuk "komisi dibayar per siklus" |
| Bagi hasil (cabang mitra) | `BAGI_HASIL` (STS/KATEGORI/PERSEN_BAGIHASIL) | `tbbagi_hasil` terisi via sync, **0 konsumer** | **Sebagian** | Sedang | |
| Komisi Sales (customer-get-customer) | `TBLSales.KomisiJual`/`KomisiNominal`/`OpPilSistemKomisi` + docx | Master sales ada, perhitungan komisi tidak ada | **Belum Ada** | Sedang | |
| Insentif Service Advisor / "Admin" 5% | Access `TBLService_Advisor_*`, `ADVISOR_CEK_TMP` | Belum ada perhitungan | **Belum Ada** | Sedang | Pertanyaan Owner B2 ("Admin = siapa?") masih menggantung → gap ini memang belum bisa dikerjakan |

**Ringkas kantong ini:** pipa datanya sudah dibangun (sync Access → MySQL jalan), tapi **tidak ada satu pun logika bisnis di sisi web yang memakainya**. Verifikasi: `grep -rln "tbbagi_hasil\|tbpersen_insentif\|tbsiklus" app/*.php` → hanya `access-sync.php` (halaman sync itu sendiri).

---

## 8. MODUL: CRM & MEMBERSHIP

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Customer 360 / statistik pelanggan | Ada (`REKAP_KONSUMEN_DATA`: jumlah kunjungan, pelanggan sejak, lama tidak datang, rata-rata) | Ada, **37.110 baris live** (`statistik_pelanggan`) | — | — | Web melampaui Access |
| Tiket masalah terstruktur + approval | Tidak ada di Access | Ada, live (`tbl_issue`, `tbl_issue_progress_log`) | — | — | Fitur baru web |
| Merge pelanggan + deteksi duplikat | Tidak ada di Access | Ada, live | — | — | Fitur baru web |
| Membership tier + garansi dinamis per tier | `DATA_MEMBER` (NOPELANGGAN, TIPEMEMBER) — sangat sederhana | Ada, jauh lebih kaya (8 tier, floor rule, garansi dinamis) | — | — | Web melampaui Access |
| Pindah Kepemilikan Kendaraan | Access `NOPOLISI_VS_NOPELANGGAN` (mapping nopol↔pelanggan) | Kode ada, **menu sengaja dinonaktifkan** (`menu_config.php:32-47`) menunggu migrasi data Access | **Sengaja Skip (sementara)** | — | Terdokumentasi dengan alasan jelas di kode. Bukan kelalaian |
| Prediksi kedatangan berikutnya + reminder | `REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA` (`DATANG_BERIKUTNYA`, `REMIND_SEBELUMNYA`, `GANTI_OLI_AKHIR`) | Di-mirror ke `gabung_rekap_kedatangan`, **tidak ada UI/scheduler reminder** | **Sebagian** | Sedang | Kombinasikan dengan gap Master Jadwal Oli (§2) untuk reminder yang akurat |
| Nomor WA pelanggan (konsolidasi + Google Contacts) | `NOMOR_WA_GCONTACT_SEMUA_DATA`, `NOMOR_WA__DOMISILI_POLISI_DATA`, `REKAP_KONSUMEN_NOMOR_WA_DATA` | Di-mirror (`gabung_rekap_konsumen_wa`); ada `statistik_pelanggan_send_wa.php` | **Sebagian** | Kecil | Kirim WA ada; ekspor/sinkron Google Contacts tidak ada — **perlu konfirmasi apakah masih perlu** |
| Domisili pelanggan per cabang | `NOPOLISI_DOMISILI_DATA` (STS per cabang → DOMISILI) | Di-mirror ke `gabung_nopolisi_domisili`, **0 konsumer** | **Sebagian** | Kecil | Berguna untuk kanibalisasi antar cabang & targeting promo |
| Riwayat pernah jemput-antar | `PERNAH_JEMPUTANTAR_DATA` | Di-mirror, 0 konsumer | **Sebagian** | Kecil | |
| Broadcast / kampanye reminder terjadwal | — | `class_whatsapp_automation.php` + `webhook_whatsapp.php` ada; **tidak ada UI kampanye / penjadwal** | **Sebagian** | Sedang | FSD_CRM FR-06 |

---

## 9. MODUL: LAPORAN & DASHBOARD

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Laporan operasional (pembelian, penjualan, servis, hutang, piutang, kas, stok, antar cabang, retur) | Ada | Ada, 24 entri laporan di menu + varian PDF/XLS | — | — | Cakupan web sudah luas |
| Profit Penjualan | Access `CEK_POKOK_JUAL`, `HRGPOKOK_PENJUALAN_TEMP` | Ada (`lap_profit_penjualan.php`) | — | — | ⚠️ Akurasi bergantung metode HPP (lihat gap FIFO §4) |
| Rekap Kunjungan Pelanggan | Ada | Ada | — | — | |
| **Dashboard Manajemen (gabungan semua cabang)** | **docx PELENGKAP #3** + level user MANAJEMEN | Ada portal legacy `_managemen/` (9 laporan, gaya Access-era, login terpisah dari menu utama); **tidak terintegrasi ke `menu_config.php`** | **Sebagian** | Sedang | Perlu keputusan: modernisasi portal `_managemen` atau bangun dashboard baru di menu utama |
| **Dashboard Cabang** | docx PELENGKAP #4 | Ada `dashboard/index.php` (terpisah), `procurement_dashboard.php`, `dashboard-antrian-servis.php`, `statistik_pelanggan_dashboard.php` — tersebar, bukan satu dashboard cabang | **Sebagian** | Sedang | |
| Laporan harian/bulanan/tahunan untuk manajemen | docx eksplisit | Laporan per rentang tanggal ada; agregasi periodik siap-saji belum | **Sebagian** | Sedang | Terkait pertanyaan Owner F5 ("3 laporan terpenting") yang belum dijawab |
| Laporan hutang untuk dikirim ke WA manajemen | docx: "saat ini untuk kirim ke WA manajemen, nantinya manajemen akses sendiri" | Laporan sudah ada di web (manajemen bisa akses sendiri) | — | — | Keinginan docx sudah terpenuhi |

---

## 10. LEVEL USER & AKSES

| Fitur | Status Access/docx | Status Web App Sekarang | Kategori Gap | Effort | Catatan |
|---|---|---|---|---|---|
| Login pilih cabang (user bisa pindah cabang) | docx eksplisit | Ada (`$_SESSION['_cabang']`) | — | — | |
| Role: Administrator, Staf CS/Kasir, Kepala Mekanik, Pengadaan, Staf CRM, Manajemen | docx eksplisit 6 role | RBAC permission-based ada. Tapi fallback berbasis level (`_include_menu_rbac.php:60-110`) hanya mengenal: super admin, admin, manager, **kasir**, **mekanik**, default | **Sebagian** | Kecil | Role **Pengadaan** dan **Staf CRM** tidak punya profil default di fallback level |
| Hak akses granular per menu (open/new/edit/delete) | Ada (`TBLUserAkses`) | Permission string per menu (read/create/approve) | — | — | Model berbeda, setara secara fungsi |
| Administrator saja yang boleh tambah/edit master barang & supplier | docx eksplisit 2 kali | ⚠️ Belum diverifikasi apakah permission `barang_create`/`supplier_create` benar-benar dibatasi admin | **Perlu Konfirmasi** ⚠️ | Kecil | Aturan bisnis eksplisit di docx, layak diverifikasi |

---

## 11. Isu Silang yang Bukan Gap Fitur (tapi memengaruhi semua modul)

| Isu | Status | Catatan |
|---|---|---|
| `no_service` tidak unik lintas cabang (30.889 grup kembar) | ⚠️ Audit tuntas, sebagian besar file High-Risk sudah diberi guard `kd_cabang` (commit `a52ca6a`, `8cf1389`); sisa 3 file risiko rendah | Memengaruhi akurasi **semua** laporan servis, insentif, dan komisi |
| Dokumen FSD tertinggal dari realita kode | Tercatat di CEKLIS 19 Juli | Analisa ini memakai kode sebagai sumber kebenaran, bukan FSD |
| 9 dataset `gabung_*` disinkron rutin, hanya 2 yang punya konsumer | Terverifikasi lewat grep | Biaya sync berjalan tanpa nilai balik. Entah dipakai, atau dihentikan |
| Ketergantungan ke Access belum putus | Acuan HPP & Insentif masih dihitung di Access lalu di-mirror | Selama 2 hal ini belum dihitung native di web, Access **tidak bisa dimatikan** |

---

## 12. Ringkasan Prioritas — Dampak Bisnis Tertinggi Duluan

Sudut pandang: bengkel motor, banyak cabang, tulang punggung servis + penjualan + pengadaan.

### Prioritas 1 — Kerjakan Duluan (dampak besar, effort kecil)

1. **Wiring 2 laporan insentif/komisi yang sudah jadi ke menu** (`lap_komisi_mekanik.php`, `lap_profit_insentif.php`).
   *Kenapa duluan:* Sudah jadi, tinggal 2 baris di `menu_config.php` + permission. Komisi adalah topik paling sensitif bagi mekanik.
2. **Master Jadwal Penggantian Oli + auto-fill KM Berikut.**
   *Kenapa:* Tabel 3 kolom + satu auto-fill di form servis. Ini fondasi reminder servis yang akurat — dan reminder servis adalah mesin kunjungan ulang, sumber omset berulang.
3. **Verifikasi trigger `alarm_harga_beli` benar-benar terpasang** (`SHOW TRIGGERS`).
   *Kenapa:* Fitur diklaim live tapi 0 baris data. Kalau mati, kenaikan harga beli lolos tanpa alarm.

*(Modul Kasir sempat ada di posisi ini pada draft awal — sudah dicoret karena closing kasir web ternyata sudah jalan. Lihat catatan koreksi di §1.)*

### Prioritas 2 — Melepas Ketergantungan Access (dampak besar, effort sedang)

5. **Hitung Acuan HPP native di web** (harga beli termahal dari 4 pembelian terakhir).
   *Kenapa:* Disebut dua kali di dokumen requirement dan dipakai untuk HPP servis. Selama masih di-mirror dari Access, **Access tidak bisa dimatikan** dan semua laporan profit menggantung pada proses manual di luar web.
6. **Perhitungan Insentif native + hidupkan master `tbpersen_insentif`/`tbsiklus`/`tbbagi_hasil`.**
   *Kenapa:* Sama seperti di atas — datanya sudah di MySQL, tinggal logikanya. Ini juga yang membuka jawaban atas pertanyaan Owner B3 (persentase per cabang, periode bayar).
7. **Laporan Rekonsiliasi Jual–Beli Antar Cabang.**
   *Kenapa:* Data 23 kolom sudah masuk MySQL. Dengan 5 cabang saling kirim barang, selisih catatan pengiriman vs penerimaan adalah kebocoran nilai yang tidak terlihat. Sekaligus menjawab pertanyaan Owner E5.

### Prioritas 3 — Fondasi Jangka Menengah (effort besar, tapi menentukan akurasi angka)

8. **Metode HPP FIFO.**
   *Kenapa:* Access punya 5 tabel FIFO; web nol. `CLAUDE.md` project sudah menuliskan "HPP FIFO" sebagai Rule tapi belum ada implementasinya. **Semua** angka laba (penjualan, servis, insentif) berdiri di atas metode HPP. Menyentuh pembelian, penjualan, servis, retur, dan koreksi stok sekaligus — perlu perencanaan dan migrasi data tersendiri, jangan dikerjakan sambil lalu.
   *Catatan penting:* Pertanyaan Owner **C1** ("HPP pakai terakhir / rata-rata / FIFO?") **belum dijawab**. Jangan bangun FIFO sebelum jawaban ini keluar — dokumen requirement menyebut "harga beli termahal dari 4 pembelian terakhir" yang **bukan FIFO**. Kedua acuan ini bertabrakan dan harus diputuskan Owner dulu.
9. **Dashboard Manajemen multi-cabang yang terintegrasi** (menggantikan/menyerap portal legacy `_managemen/`).
10. **Modul Akuntansi Keuangan / Laba Rugi.** Status PELENGKAP di requirement — wajar terakhir, tapi kerangkanya (`TBLRekap` di Access) menunjukkan Owner memang sudah memikirkan buku besar per periode.

### Prioritas 4 — Menunggu Keputusan Owner (jangan dikerjakan dulu)

- Cuci gratis & poin cuci (🔴 blocked)
- Reminder oli — skema program (🔴 blocked)
- Booking Servis (PELENGKAP, belum ada keputusan)
- Definisi "Admin 5%" di formula insentif (pertanyaan B2 belum dijawab) → menghalangi insentif Service Advisor
- Metode HPP final (pertanyaan C1) → menghalangi item #8

### Yang Perlu Dikonfirmasi ke Owner (apakah memang tidak perlu dibawa ke web)

| Fitur Access/docx | Pertanyaan |
|---|---|
| Cetak "Form Servis Kosong" (lembar checklist kertas) | Alur temuan/penawaran sudah full digital — apakah lembar kertas ini masih dipakai di lapangan? |
| Ekspor/sinkron Google Contacts (`NOMOR_WA_GCONTACT_SEMUA_DATA`) | Masih dipakai untuk broadcast WA, atau sudah digantikan WA automation di web? |
| Master Garapan / Daftar Pengerjaan Servis (`GARAPAN`, `DAFTAR_PENGERJAAN_SERVIS`) | Apakah taksonomi kategori pengerjaan Access masih dibutuhkan untuk pengelompokan laporan? |
| `ADMIN_SERVIS_KOSONG` (audit servis kosong per admin) | Apakah `lap_cancel_servis.php` sudah menggantikan ini, atau "servis kosong" konsep berbeda dari "servis cancel"? |
| `TBLCABANG_ADMIN` | Masih relevan, atau sudah tergantikan relasi user–cabang di RBAC web? |
| 7 dataset `gabung_*` tanpa konsumer | Dipakai untuk apa sebenarnya? Kalau tidak dipakai, sync-nya dihentikan saja untuk mengurangi beban. |

---

## 13. Daftar Verifikasi Lanjutan (butuh akses DB live — tidak bisa dari WSL)

Item bertanda ⚠️ di atas, dikumpulkan:

1. `SHOW TRIGGERS` — pastikan `trg_alarm_harga_beli` terpasang.
2. Kolom `no_faktur` / `tgl_faktur` di tabel pembelian.
3. Kolom timestamp per tahap servis (datang/diproses/selesai/bayar) di `tblservice`.
4. Enforcement "total kontribusi mekanik = 100%".
5. Enforcement "barang stok kosong & harga naik tidak bisa diinput ke transaksi".
6. Pembatasan tambah/edit master barang & supplier hanya untuk Administrator.
7. Fitur pelunasan multi-faktur (centang) di `pmby_hutang.php`.
8. Bulk update harga jual (centang beberapa item) di modul barang.
9. Kelengkapan jalur input Saldo Awal untuk stok / piutang pelanggan / hutang supplier.
10. Entry point `barang_history_hp.php` dan `master_perusahaan.php` (keduanya tidak ada di `menu_config.php`).
11. Aturan "hanya nota yang belum pernah diterima" di penerimaan antar cabang.
