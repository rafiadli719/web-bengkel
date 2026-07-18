# PROMPT — Planning Modul Promo Engine (Pengganti Program Cuci Motor Gratis)

**Dibuat:** 2026-07-17
**Untuk:** Claude Code — sesi **planning**, bukan implementasi
**Konteks:** Tindak lanjut jawaban Owner (Pak Novian, 16 Juli 2026) atas 5 poin keputusan bisnis yang blocking pembangunan modul FIT MOTOR

---

## 0. Status 5 Keputusan (baca dulu, biar tidak kerja ulang yang sudah selesai)

| # | Item | Status | Catatan |
|---|---|---|---|
| 1 | Skema Tier Membership | ❌ Belum dijawab Owner | Masih blocking `FSD_MEMBERSHIP.md`. **Bukan bagian task ini** — jangan ikut dikerjakan, tunggu jawaban terpisah. |
| 2 | Angka Batas Naik Level Member | ❌ Belum dijawab Owner | Bergantung #1. |
| 3 | Batas Rupiah Approval Pembelian | ✅ Final & sudah terdokumentasi | Sudah masuk `FSD_PENGADAAN_INVENTORY.md` §7.1 (`tb_master_approval_pembelian`), 2026-07-16. Tidak perlu planning ulang. |
| 4 | Persen Perubahan Harga Beli "Waspada" | ✅ Final & sudah terdokumentasi | Sudah masuk `FSD_PENGADAAN_INVENTORY.md` §11.1 (`tb_master_threshold_harga`, naik/turun dibedakan, satu halaman setting). Tidak perlu planning ulang. |
| 5 | Program Cuci Motor Gratis | 🔴 **Pivot arsitektur — target sesi ini** | Owner menolak desain hardcode di `FSD_SERVIS.md` §9 (Varian 1/2 poin). Mau promo engine generik berbasis master data. |

Fokus prompt ini murni **#5**. #1/#2 sengaja dicantumkan supaya tidak salah asumsi ikut dikerjakan — statusnya masih blocked di luar scope sesi ini.

---

## 1. Konteks Wajib Dibaca Sebelum Mulai

Investigate-first — laporkan temuan dulu sebelum mengusulkan skema apa pun. Urutan baca:

1. **`FSD_SERVIS.md` §9 (Program Gratis Cuci Motor) dan §12 poin #1** — ini desain LAMA yang harus dianggap **superseded** oleh keputusan Owner 16 Juli. Tapi tabel yang disebut di sana (`servis_poin_cuci`, `servis_voucher_cuci`) perlu dicek: sudah benar-benar dibuat & dipakai di DB live, atau baru proposal FSD yang belum pernah dieksekusi? Project ini punya riwayat FSD-proposal vs implementasi-live yang beda (lihat `IMPLEMENTATION_PLAN_WEB_BENGKEL.md` bagian "Verifikasi Status 2026-07-04" sebagai contoh kenapa ini harus dicek ke DB, bukan cuma dipercaya dari teks FSD).
2. **`FSD_PENGADAAN_INVENTORY.md` §5.2, §7.1, §11.1** — dipakai sebagai **pola desain rujukan**. `tb_master_approval_pembelian` dan `tb_master_threshold_harga` lahir dari instruksi Owner yang sama semangatnya dengan promo ("jangan hardcode, taruh di master, editable lewat UI"). Promo engine harus mengikuti pola/gaya penamaan yang sama, bukan pola baru yang beda konvensi.
3. **`FSD_MEMBERSHIP.md` §5, FR-03** — pastikan promo engine jalurnya **beda** dari diskon tier member (`master_kategori_member` / `tbmaster_kategori_member`). `IMPLEMENTATION_PLAN_WEB_BENGKEL.md` (sekitar catatan F2-C) menyebut ada field `diskon_source` yang sudah membedakan diskon member vs promo di level item — cek field ini di skema live, ini kemungkinan titik integrasi utama promo engine ke alur transaksi.
4. **Skema database aktual** (`tools/sql/fitmotor_dbbengkel_FIXED_V7.sql` atau versi lebih baru kalau ada) — cari semua tabel yang mengandung kata `promo`, `voucher`, `diskon`, `poin`, `gratis`. Jangan asumsikan keberadaan tabel dari nama di FSD saja.
5. **`tbworkorderdetail.is_gratis` / `tbservis_pending_items.is_gratis`** (fitur WO kombinasi, live sejak 2026-07-02) — ini mekanisme "item harga dikunci 0" yang **sudah ada**. Promo engine baru kemungkinan besar perlu nyambung ke mekanisme ini untuk eksekusi gratis-item, bukan bikin mekanisme kedua yang tumpang tindih.
6. **`helper-functions.php`** — cari fungsi `checkDiskonApproval()` dan pemakaian `diskon_source` di tiga handler bayar servis (`servis-input-reguler.php`, `servis-input-reguler-jemput.php`, `servis-garansi.php`). Promo engine kemungkinan perlu masuk di titik yang sama.
7. **`FSD_CRM.md` §5.1** (pola `master_jenis_masalah` + `payload_json`) — bukan untuk dipakai langsung, tapi contoh pola "skema field dinamis via JSON + master katalog" yang sudah diterima di project ini. Relevan kalau promo engine butuh field yang beda-beda tergantung jenis promo.

---

## 2. Keputusan Owner yang Mengikat (final, jangan dibuka ulang)

Arahan Pak Novian, 16 Juli 2026 — diterjemahkan jadi constraint desain:

- **Nama promo**: fleksibel, bukan nama dedicated hardcode ("bukan dedicated nama tertentu").
- **Periode berlaku**: fleksibel, ditentukan user per promo — bukan hardcode "5 hari"/"14 hari" seperti desain lama.
- **Apa yang dipromokan**: fleksibel, tapi scope-nya cuma dua kemungkinan — **item** dan/atau **jasa** (bukan customer/tier, itu tetap ranah Membership, modul beda).
- **Stackable atau tidak**: fleksibel per promo — user yang menentukan apakah 1 promo boleh digabung dengan promo lain (atau dengan diskon member) atau tidak.
- Semua poin di atas **wajib berbasis data master**, bukan logic hardcode di kode aplikasi.

---

## 3. Tugas Claude Code di Sesi Ini

**Sesi ini planning saja.** Tidak menulis migrasi SQL, tidak menulis kode PHP, tidak membuat/mengubah file aplikasi apa pun. Output yang diharapkan: draft `FSD_PROMO.md` mengikuti format yang sama persis dengan FSD lain di project ini (rujuk struktur `FSD_MEMBERSHIP.md` / `FSD_PENGADAAN_INVENTORY.md`: Ringkasan & Tujuan, Ruang Lingkup, Aktor & Role, Glosarium, Model Data, Functional Requirements, Business Rules Konsolidasi, Alur Utama, Edge Case Handling, Non-Functional Requirements, Dependency Antar Modul, Kriteria Penerimaan, Open Items).

Langkah kerja:

1. **Investigasi dulu** (section 1 di atas). Laporkan temuan sebagai ringkasan sebelum lanjut ke desain — tegaskan mana yang sudah live di DB vs mana yang cuma proposal FSD yang belum pernah dieksekusi.
2. **Tentukan scope resmi dokumen**: apakah `FSD_PROMO.md` jadi dokumen berdiri sendiri, atau tetap sub-bagian `FSD_SERVIS.md`. Rekomendasi awal saya: berdiri sendiri, karena promo sekarang eksplisit mencakup item DAN jasa — tidak eksklusif milik alur Servis seperti asumsi lama. Tapi ini keputusan yang harus ditulis besertaalasannya, jangan diputuskan diam-diam tanpa penjelasan.
3. **Rancang model data master promo generik**, minimal mencakup:
   - Header promo: nama, tanggal mulai/akhir, status aktif, jenis benefit (diskon persen / diskon nominal / gratis-item), aturan stackable.
   - Target promo: item dan/atau jasa mana yang kena — harus bisa banyak, dan campur item+jasa dalam 1 promo.
   - Histori pemakaian: siapa pakai promo apa, kapan, di transaksi mana — supaya auditable, konsisten dengan pola audit trail yang sudah dipakai project ini (`member_tier_history`, `alarm_harga_beli`).
4. **Petakan migrasi cuci motor gratis versi lama** (poin dari kombinasi jasa dalam 5 hari) jadi 1 instance di engine baru — bukan dihapus konsepnya, cuma pindah dari hardcode ke data. Kalau logic "akumulasi poin rolling" ternyata tidak muat ke model generik sederhana (diskon langsung per transaksi), catat sebagai kasus khusus yang mungkin butuh extension terpisah — jangan dipaksakan muat kalau memang tidak pas, tulis apa adanya sebagai gap.
5. **Definisikan aturan stacking secara eksplisit**: kalau 2 promo stackable aktif bersamaan di 1 transaksi, urutan hitungnya bagaimana (harga dasar → promo A → promo B berurutan, atau sistem pilih kombinasi paling menguntungkan customer)? Ini wajib didesain eksplisit — kalau butuh keputusan Owner tambahan, taruh di Open Items, jangan ditebak.
6. **Definisikan interaksi dengan diskon member** (`FSD_MEMBERSHIP.md`): apakah promo dan diskon tier member default-nya bisa digabung, atau butuh flag per promo (mis. `boleh_gabung_diskon_member`)? Ini beririsan langsung dengan field `diskon_source` yang sudah ada di kode.
7. **List Open Items** yang kemungkinan besar masih butuh keputusan Owner tambahan (contoh: siapa yang boleh bikin promo baru — Owner only atau Kepala Cabang juga; promo bisa diaktifkan per cabang atau selalu pusat; dst). Jangan diputuskan sendiri, catat sebagai pertanyaan.

---

## 4. Batasan Tegas

- **Jangan** mengubah atau menghapus tabel yang sudah live tanpa konfirmasi eksplisit.
- **Jangan** mulai coding di sesi ini — kalau nemu bug/gap yang menggoda buat langsung diperbaiki, catat di dokumen, jangan dieksekusi.
- **Jangan** asumsikan sesuatu "sudah pasti begitu" dari nama tabel/kolom — verifikasi ke schema live sebelum menuliskannya di FSD sebagai fakta.
- Kalau ternyata `servis_poin_cuci`/`servis_voucher_cuci` sudah live **dan sudah punya data**, itu jadi constraint migrasi yang harus ditangani eksplisit di desain — bukan didesain ulang dari nol seolah belum ada apa-apa.

---

## 5. Output yang Diharapkan

1. File baru: `docs/fsd/FSD_PROMO.md` (draft, status "Menunggu approval" mengikuti konvensi dokumen lain di project ini).
2. Ringkasan singkat di akhir sesi: apa yang ditemukan saat investigasi, keputusan desain yang diambil beserta alasannya, dan daftar Open Items untuk Owner.
