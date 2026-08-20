# Ceklis Progres Web Bengkel FIT MOTOR — untuk Pak Novian (Owner)

**Tanggal:** 19 Juli 2026
**Metodologi:** Setiap modul diverifikasi ke kode & schema database langsung (bukan cuma baca status di dokumen FSD). Ini penting karena beberapa modul ternyata SUDAH LIVE dan dipakai transaksi nyata, padahal dokumen FSD-nya masih tertulis "Menunggu approval" — dokumennya yang ketinggalan, bukan kodenya.

**Status yang dipakai:**
- ✅ Selesai & Live
- 🔶 Sedang Dikerjakan
- 🔴 Blocked — Menunggu Jawaban Owner
- ⚠️ Perlu Perhatian (bug/isu belum diperbaiki)

---

## 1. Membership

**Status: ✅ Selesai & Live** (kalkulasi tier + floor rule + garansi dinamis) — **🔴 Blocked** (2 hal skema lanjutan)

- Dokumen FSD masih tertulis "Menunggu approval", tapi kode **sudah live dan dipakai 37.110 baris data pelanggan** (`statistik_pelanggan`).
- `master_kategori_member` (8 baris tier) dipakai `app/_include_kategori_member.php`.
- Aturan "tier tidak pernah turun otomatis" **sudah dikode** (`fitmotorApplyMemberTierFloor()`, `app/_include_kategori_member.php:158`).
- Garansi dinamis per tier (durasi garansi beda per level member) **sudah live** lewat tabel `tbmaster_kategori_member` (kolom `masa_garansi_hari`/`masa_garansi_maks_hari`).
- Tabel `member_tier_history` & `siklus_komisi` — **belum dibangun**, masih rencana.

🔴 **Masih perlu jawaban Owner (2 pertanyaan terpisah, belum dijawab per 19 Juli):**
1. Skema tier final: pakai gabungan (kombinasi kriteria) atau RFM (recency/frequency/monetary)?
2. Konfirmasi ulang: status member boleh turun tier, atau sekali naik permanen selamanya? (Catatan: kode saat ini SUDAH menerapkan "sekali naik, tidak pernah turun" — tapi ini perlu dikonfirmasi resmi ke Owner, bukan asumsi tim dev.)

---

## 2. Customer

**Status: ✅ Selesai & Live** (deteksi & merge duplikat pelanggan)

- Dokumen FSD masih "Menunggu approval", tapi:
- `app/admin_deteksi_pelanggan_dobel.php` (deteksi duplikat) + `app/customer_merge_approve.php` (approval & eksekusi merge) — sudah ada di menu aplikasi.
- Tabel `customer_merge_log` sudah ada **2 baris**, `customer_alias` sudah ada **1 baris** — artinya **minimal 1 merge pelanggan sudah benar-benar dieksekusi di data produksi**.
- Bagian riwayat kontak/profil pelanggan (`pelanggan_kontak_history`, `pelanggan_profile_history`) — **belum dibangun**, masih rencana FSD.

---

## 3. Kendaraan

**Status: ⚠️ Perlu Perhatian — URGENT**

Ini temuan paling penting di ceklis ini:

- Menu **"Pindah Kepemilikan Kendaraan" sudah tampil dan bisa diklik user** (`app/kendaraan_pindah_tangan.php`, `app/kendaraan_pindah_tangan_approve.php`, sudah masuk `menu_config.php`).
- Tapi tabel database pendukungnya (`kepemilikan_kendaraan`, `permintaan_pindah_kepemilikan_kendaraan`, `kendaraan_plat_history`, `statistik_kendaraan`) **TIDAK ADA di database produksi** — migrasinya belum pernah dijalankan.
- Kedua file itu query langsung ke tabel yang belum ada, **tanpa guard** — kemungkinan besar **akan error kalau user mengklik menu ini sekarang**.
- Tim dev sebenarnya sudah sadar risiko ini (ada guard defensif di `app/detail_pelanggan.php`), tapi guard itu tidak dipasang di 2 file menu utama.

**Rekomendasi segera:** jalankan migrasi `db/migrations/2026-07-13_kendaraan_pindah_kepemilikan.sql` ke DB live, ATAU sembunyikan menu ini dulu sampai diverifikasi — sebelum ada user yang tidak sengaja mengklik dan menemukan error di produksi.

---

## 4. CRM

**Status: ✅ Selesai & Live**

- Dokumen FSD masih "Menunggu approval", tapi:
- `master_jenis_masalah` (katalog jenis tiket) — sudah ada **6 baris**, sesuai desain FSD.
- `tbl_issue` — sudah ada **4 tiket nyata**; `tbl_issue_progress_log` — sudah ada **6 baris log audit**.
- Fitur "Revisi Komisi Servis Pasca-Bayar" (pilot) — sudah terverifikasi jalan lewat `app/issue_add.php` (insert baris baru ke `servis_komisi`, sesuai desain, bukan menimpa data lama).

---

## 5. Servis

| Sub-fitur | Status | Catatan |
|---|---|---|
| Garansi Dinamis (F1-A) | ✅ Selesai & Live | Kolom garansi di `tblservice` (dari migrasi 2026-07-04) dipakai `servis-garansi.php` & turunannya. Sudah melampaui apa yang disebut FSD sebagai gap terbuka. |
| DP Servis (F2-A) | ✅ Selesai & Live | Tabel `tb_dp_servis` sudah ada isi (1 transaksi), diimplementasi `ajax-catat-dp.php`. |
| Retur Servis (F2-B) | ✅ Selesai & Live | Kode lengkap (`retur_servis*.php`) + tabel `tblretur_servis_header/detail` ada, wired ke menu. *(Jumlah transaksi live belum sempat direcek ulang di sesi ini — struktur & kode terkonfirmasi ada.)* |
| Approval Diskon (F2-C) | ✅ Selesai & Live | Tabel `tb_approval_diskon` sudah ada isi (1 baris), `approval-diskon.php` wired ke menu dengan permission. |
| Formula Komisi Mekanik (B1) | ⚠️ Perlu Perhatian | Tabel `servis_komisi` ada tapi **0 baris dari alur bayar normal** — satu-satunya yang mengisi tabel ini adalah jalur revisi tiket CRM (`issue_add.php`), bukan alur pembayaran servis harian. `servis-reguler-byr.php` (proses bayar normal) **tidak** menyimpan snapshot komisi permanen. Artinya rencana inti FSD ini (komisi permanen tersimpan tiap servis dibayar) **belum berjalan** — komisi masih dihitung real-time dari kolom persentase mekanik, masalah lama yang justru ingin diselesaikan fitur ini. |
| Cuci Gratis & Reminder Oli | 🔴 Blocked — Menunggu Jawaban Owner | Tabel pendukung (`servis_poin_cuci`, `servis_voucher_cuci`, `servis_reminder_oli`) belum dibuat sama sekali — sesuai FSD, memang menunggu keputusan Owner soal skema program ini. |

---

## 6. Pengadaan & Inventory

**Status: ✅ Selesai & Live** (approval bertingkat PO + threshold harga) — **⚠️ Perlu Perhatian** (alarm harga beli)

- `tb_master_approval_pembelian` (approval bertingkat PO) — sudah ada **2 baris konfigurasi nyata** (Supervisor 300rb–1jt, Manager >1jt, sesuai keputusan Owner), dipakai `master-approval-pembelian.php`, `po_approval_action.php`, `pesanan_pembelian_add.php`, `do_from_po.php`. **Live & aktif.**
- `tb_master_threshold_harga` (alert harga naik/turun) — sudah ada **2 baris** (naik 5%, turun 10%), dipakai `setting-threshold-harga.php`. **Live.**
- Alarm Harga Beli — arsitekturnya pakai **trigger database** (`trg_alarm_harga_beli`), bukan kode PHP biasa. Tabel `alarm_harga_beli` ada tapi **0 baris tercatat** — kemungkinan trigger belum pernah kena kondisi pemicu, ATAU trigger-nya belum benar-benar terpasang di database produksi. ⚠️ **Perlu dicek manual** (`SHOW TRIGGERS`) oleh tim dev untuk memastikan alarm ini benar-benar aktif.
- RFQ Supplier Response — belum dibangun sama sekali; ini satu-satunya bagian yang statusnya sudah jujur ditulis "belum dibangun" di FSD-nya sendiri.

---

## 7. Promo Engine

**Status: ✅ Selesai & Live** (mesin diskon inti) — **🔴 Blocked** (2 hal belum dikonfirmasi ulang ke Owner) — **⚠️ Perlu Perhatian** (sisa kerjaan teknis)

- Multi-target (barang/jasa), multi-cabang, syarat kelayakan, stacking berurutan, audit log (`promo_usage_log`) — semua **sudah live**, sudah diverifikasi lewat PHP CLI (kalkulasi & stacking benar).

🔴 **Belum dikonfirmasi ulang ke Owner:**
1. **Scope per-cabang** — implementasi saat ini membolehkan promo di-scope ke cabang tertentu (editable), padahal ini **kebalikan** dari keputusan sebelumnya (16 Juli) yang bilang "global saja". Perlu konfirmasi ulang apakah reverse keputusan ini memang disetujui Owner.
2. **Definisi "minimum total servis"** — syarat kelayakan ini dihitung dari **akumulasi lifetime** pelanggan (`statistik_pelanggan.total_nominal`), bukan dari transaksi yang sedang berjalan. Belum ada konfirmasi eksplisit ke Owner apakah definisi ini yang dimaksud.

⚠️ **Sisa kerjaan teknis (belum urgent, tapi belum tuntas):**
- Mode OR syarat kelayakan belum ditest (baru mode AND yang tervalidasi).
- Jenis syarat `jumlah_kunjungan` & `paket_workorder` belum ditest sama sekali.
- Browser E2E end-to-end lewat form kasir sungguhan belum dilakukan (baru diverifikasi lewat PHP CLI + endpoint AJAX langsung).
- Permission `promo_diskon_read` belum didaftarkan resmi di sistem RBAC berbasis DB — saat ini hanya jalan lewat akses admin/owner.
- Tabel `promo_usage_log` **tidak punya kolom `kd_cabang`** — belum jadi masalah aktif (belum ada laporan yang JOIN balik ke servis), tapi berpotensi salah atribusi cabang kalau ada laporan promo per-cabang dibuat nanti. Perlu ditambah sebelum laporan semacam itu dibangun.

---

## 8. Known Issue — `no_service` Tidak Unik Lintas Cabang

**Status: ⚠️ Perlu Perhatian** (baru audit, belum ada perbaikan kode)

- Terkonfirmasi: kolom `no_service` di `tblservice` **tidak unik secara global**, hanya unik per cabang — ada **30.889 grup nomor tiket kembar** antar cabang berbeda.
- Sudah diaudit menyeluruh (180+ file dicek) 19 Juli 2026:
  - **Grup risiko tinggi** (bisa mengubah/menghapus data servis cabang lain secara salah sasaran): 22 file, termasuk 3 file KRITIS tanpa pengecekan session sama sekali (`servis-carinopol-batal.php`, `servis-carinopol-kosongkan.php` + duplikatnya).
  - **Grup risiko rendah** (cuma data salah tampil di laporan/layar, tidak mengubah database): ~58 file + puluhan file tampilan tab.
- **Belum ada satu pun perbaikan kode dari hasil audit ini** — audit murni investigasi, perbaikan menunggu keputusan urutan prioritas dari Owner/tim dev di sesi terpisah.

---

## Ringkasan Prioritas untuk Owner

1. **URGENT:** Menu "Pindah Kepemilikan Kendaraan" sudah bisa diklik user tapi tabel database-nya belum ada — berisiko error di depan pelanggan. Perlu tindakan sebelum ada laporan masuk dari lapangan.
2. **Perlu keputusan Owner:** 2 pertanyaan Membership (skema tier, aturan turun/naik tier), 2 pertanyaan Promo Engine (scope cabang, definisi minimum total servis), keputusan Cuci Gratis & Reminder Oli.
3. **Perlu perhatian teknis:** Formula Komisi Mekanik permanen belum berjalan di alur bayar normal (masih pakai cara lama). Alarm Harga Beli perlu dicek manual apakah trigger-nya benar-benar aktif. Bug `no_service` tidak unik — 22 file berisiko tinggi menunggu perbaikan, urutan prioritas menunggu keputusan.
4. **Dokumentasi FSD ketinggalan realita** untuk Membership, Customer, CRM — statusnya masih tertulis "Menunggu approval" padahal kodenya sudah live dan dipakai transaksi nyata. Perlu diupdate supaya dokumen mencerminkan kenyataan.
