# Planning Tindak Lanjut — Daftar Keputusan yang Dibutuhkan

**Sumber:** `DAFTAR_KEPUTUSAN_YANG_DIBUTUHKAN (2).docx` (disusun 5 Juli 2026)
**Dibuat:** 2026-07-05
**Tujuan:** Memecah 25 poin keputusan jadi rencana kerja bertahap — mana yang blocking pembangunan modul, mana yang bisa IT kerjakan sekarang tanpa menunggu jawaban Owner, dan mana yang beririsan dengan fitur yang sudah dibangun (F1-A Garansi, F2-A DP, F2-B Retur Servis, F2-C Approval Diskon, B1 Formula Komisi).

---

## Ringkasan

25 keputusan bisnis tersebar di 6 modul: Membership, Data Pelanggan, Data Kendaraan, CRM/Layanan Pelanggan, Servis, Pengadaan & Stok.

- **13 poin** sudah ada Rekomendasi Teknis (✅) — Owner tinggal setuju/tolak, bukan mikir dari nol.
- **9 poin** arahnya sudah jelas tapi angka/kebijakan tetap perlu Owner (⚠️).
- **8 poin** murni keputusan internal, tidak ada rujukan industri yang relevan (❌).

**Catatan penting:** dokumen ini isinya keputusan bisnis untuk Owner & Kepala Divisi — bukan tugas teknis buat langsung dieksekusi. Peran IT di sini cuma: (1) siapkan bahan biar Owner gampang mutusin, (2) eksekusi begitu jawaban masuk, (3) kerjakan bagian yang murni teknis (tidak perlu nunggu Owner sama sekali).

---

## Fase 0 — Bisa Dikerjakan IT Sekarang, Tidak Perlu Nunggu Jawaban Owner

Ini kerjaan analisis/teknis murni yang jadi bahan diskusi atau memang sudah ditetapkan sebagai tugas IT di dokumen aslinya.

| # | Item | Kerjaan |
|---|------|---------|
| #3 | Threshold fuzzy-match nama pelanggan | Uji algoritma dedup pakai dataset duplikat riil ("SUGENG, BPK" 43 baris) di kisaran 80–90% similarity + exact/near-match no HP. Hasil uji jadi bahan sepakat bersama CS, bukan Owner yang nebak angka. |
| #10 (bahan) | Analisis distribusi belanja pelanggan | Siapkan analisis distribusi `statistik_pelanggan.total_nominal` (persentil 60/80/95) sebagai bahan diskusi angka batas naik level member — supaya Owner/Marketing mutusin berbasis data, bukan tebakan. |
| #21 | Gali 7 kategori peringatan harga beli lama | Murni penggalian data lama dari sistem lawas — baru 2/7 kategori ketemu detailnya. Tidak perlu keputusan bisnis, cuma butuh waktu gali. |
| #25 (audit) | Cek laporan rekap kunjungan per cabang | Audit dulu kode laporan yang bermasalah — cek query-nya beneran gabung semua cabang atau cuma tampilan judul yang salah. Baru setelah ketauan akar masalahnya, ajukan opsi A/B ke Owner. |
| #7 (persiapan) | Strategi data motor lama campur pemilik | Siapkan proposal "tanggal cutover" (pola migrasi standar ERP) sebagai draft — didiskusikan bareng Admin Data pas migrasi data lama beneran mulai, tidak menghalangi desain sekarang. |

---

## Fase 1 — Prioritas TINGGI, Blocking Pembangunan Modul (Kejar Jawaban Owner Duluan)

Ini yang harus dikejar duluan karena kalau telat jawab, modul terkait bisa terlanjur salah desain dan harus dirombak ulang.

| # | Item | Kenapa blocking | Ke siapa |
|---|------|------------------|----------|
| **#1** | Skema Tier Membership (3 level lama vs 4 level baru RFM) | **Paling mendesak, lintas modul.** Seluruh perhitungan diskon member bergantung ini. Rekomendasi teknis: Opsi B/C (gabungan nominal+kunjungan, pola RFM) — nama tier tetap keputusan Owner. | Owner/Manajemen, Marketing |
| #4 | Siapa approve merge data pelanggan duplikat | Blocking fitur dedup/merge yang jadi bagian modul CRM data-quality. | Owner, struktur wewenang staf |
| #10 | Angka batas naik level member | Bergantung #1 duluan — begitu skema tier disepakati, angka ambang batas harus final sebelum modul Membership jalan. | Owner, Marketing/Sales |
| #14 | Program "Cuci Motor Gratis" masih jalan? | Kalau masih jalan tapi tidak dibangun di sistem baru, langsung kerasa ke pengalaman pelanggan harian. | Owner, Kepala Cabang, CS |
| #19 | Batas rupiah approval pembelian ke Owner | Blocking workflow approval Pengadaan. | Owner, Keuangan |
| #20 | Persen perubahan harga beli "perlu diwaspadai" | Blocking fitur alert harga Pengadaan. Rekomendasi teknis: pilot 3–10%. | Owner, Bagian Pengadaan/Gudang Pusat |

---

## Fase 2 — Prioritas Sedang, Beririsan dengan Fitur yang Sudah Dibangun

Poin-poin ini perlu diverifikasi dulu karena nyerempet kerjaan yang sudah live — jangan sampai jawaban Owner nanti kontradiksi sama yang sudah jalan.

| # | Item | Overlap dengan | Yang perlu dicek |
|---|------|-----------------|-------------------|
| #18 | Kolom resmi khusus garansi servis | F1-A (garansi dinamis, `is_garansi`/`ref_no_service_original` sudah ada di `tblservice`) | F1-A sudah punya kolom terstruktur untuk *auto-check masa garansi* — tapi Keputusan #18 bicara soal *klaim garansi* (siapa nanggung biaya). Cek: apakah kolom existing cukup, atau perlu kolom tambahan khusus pencatatan klaim (beda dari sekadar flag `is_garansi`). |
| #17 | Retur stok servis dibatalkan: otomatis atau manual | F2-B (Retur Servis Lunas, baru dibangun 2026-07-05) | Rekomendasi teknis: hybrid (otomatis reversal KECUALI barang sudah fisik terpasang → approval terpisah). Cek ulang flow `retur_servis_approve.php` — apakah sudah bedakan 2 jalur ini atau treat semua sama. |
| #9 | Kategori diskon "Bengkel" (rekanan 5%) masih dipakai | B1 (formula komisi, baru terjawab 2026-07-05) & F2-C (approval diskon) | Cek `tbmaster_kategori_member`/master diskon — apakah kategori rekanan 5% ini konflik atau tumpang tindih sama skema tier baru (#1) dan formula komisi 20/5/5/5 yang baru disepakati. |
| #16 | Fitur tinjau ulang sebelum HPP dikunci | Cara kerja HPP real-time yang sudah berjalan sekarang | Rekomendasi teknis: tidak perlu (Opsi B) — cukup audit trail + transaksi koreksi. Kemungkinan besar sudah sesuai arah sistem baru, tinggal konfirmasi Kasir/Kepala Cabang. |

Poin sedang lainnya yang independen (tidak overlap kerjaan existing, tunggu jawaban normal): #2, #5, #6, #8, #11, #12, #15, #22, #23, #25 (setelah audit Fase 0).

---

## Fase 3 — Rendah / Nice-to-Have, Bisa Ditunda ke Roadmap Berikutnya

| # | Item |
|---|------|
| #7 | Data motor lama campur pemilik (baru relevan saat migrasi data lama) |
| #13 | Sistem "Lapor Masalah" diperluas ke Gudang/Komisi (rekomendasi: rancang generik dari awal, tapi tidak mengunci desain sekarang) |
| #24 | Auto-draft permintaan barang saat stok minim (rekomendasi: effort rendah, manfaat tinggi — layak dinaikkan prioritas kalau ada slot dev) |

---

## Urutan Kerja yang Disarankan

1. **Sekarang:** kerjakan seluruh Fase 0 (analisis, audit, penggalian data) — tidak perlu nunggu siapa pun.
2. **Minggu ini:** kirim Fase 1 ke Owner/divisi terkait secara terpisah per topik (bukan satu email 25 poin sekaligus) — supaya Owner tidak overwhelmed dan modul Membership + Pengadaan bisa segera lanjut jalan.
3. **Paralel:** audit 4 poin Fase 2 yang overlap kerjaan existing (F1-A, F2-B, F2-C, B1) — supaya kalau jawaban Owner keluar, tidak perlu bongkar ulang yang sudah live.
4. **Belakangan:** Fase 3 masuk backlog roadmap, revisit setelah Fase 1–2 kelar.

---

## Referensi Terkait

- [[project_implementation_plan_2026-07-04]] — status F1-A/F1-B/B1/Q1 yang sudah berjalan, beririsan Fase 2 di atas.
- [[project_task3_toggle_buat_servis]] — Task 3 sudah live & ditest, tidak disinggung dokumen keputusan ini.

---

## Detail Teknis Per Item — Perubahan Kode/Skema yang Dibutuhkan

### Fase 0 (bisa mulai sekarang)

**#25 — BUG DIKONFIRMASI (bukan dugaan lagi).** Audit `app/lap_rekap_kunjungan.php:7-64`: `$kd_cabang` diambil dari session, tapi cuma dipakai buat tampilkan `$nama_cabang` di judul (baris 16-18) — **tidak pernah masuk ke `WHERE` clause** query `view_statistik_pelanggan` (baris 40-64). Hasil: laporan berjudul "Rekap Kunjungan — Cabang X" tapi datanya gabungan SEMUA cabang. Kalau Owner jawab Opsi B (seharusnya per cabang): tambah `$where[] = "kd_cabang = '$kd_cabang'"` di baris ~40, cek dulu kolom `kd_cabang` ada di view `view_statistik_pelanggan` atau perlu di-JOIN dari tabel dasarnya (`statistik_pelanggan`/`tblpelanggan`). Kalau Opsi A (memang disengaja pusat): cukup ubah judul jadi "Rekap Kunjungan — Semua Cabang", jangan pura-pura per cabang.

**#3 — Uji fuzzy-match dedup.** Belum ada kode dedup sekarang. Perlu: script PHP/SQL pakai `SOUNDEX()`/Levenshtein (`levenshtein()` builtin PHP cukup untuk generate skor similarity) dijalankan terhadap `tblpelanggan.namapelanggan` + `telephone`, cari semua pasangan >80% mirip, verifikasi manual terhadap kasus "SUGENG, BPK" yang diketahui 43 baris. Output: laporan pasangan kandidat duplikat buat disepakati ambang batasnya bareng CS — bukan fitur produksi dulu, ini uji coba/kalibrasi.

**#10-bahan — Analisis distribusi nominal.** Query `SELECT total_nominal FROM statistik_pelanggan` (atau view turunannya), hitung persentil 60/80/95 pakai PHP/Excel. Tidak perlu kode baru di aplikasi, cukup query ad-hoc + laporan/spreadsheet buat bahan diskusi Owner.

**#21 — Gali kategori peringatan harga.** Bukan kerjaan kode — audit data lama (kemungkinan di `FITMOTOR APP.mdb` atau dokumen `FSD_*`) buat lengkapi 5 kategori yang belum ketemu detailnya.

**#7-persiapan — Draft strategi cutover.** Tidak ada kode sekarang — cukup dokumen proposal (mirip pola `tanggal_garansi_expire` yang sudah dipakai F1-A sebagai contoh "tanggal acuan" di kode existing).

### Fase 1 (blocking, tunggu jawaban Owner — siapkan skema begitu jawaban masuk)

**#1 — Skema tier membership.** Tabel `tbmaster_kategori_member` sudah ada dan sudah dipola jadi master editable per tier (dipakai F1-A untuk `masa_garansi_hari`). Begitu Owner pilih Opsi B/C (RFM gabungan), perlu: (a) tambah kolom ambang batas `min_kunjungan` + `min_nominal` per tier di tabel ini (kalau belum ada — cek dulu skema aktual sebelum migrasi), (b) ubah logic penentuan tier pelanggan (kemungkinan ada di `_include_kategori_member.php`, tempat `getMasaGaransiHari()` sekarang tinggal) dari "hitung kunjungan doang" jadi "kunjungan DAN nominal", (c) ubah pengelompokan dari nomor HP ke `nopelanggan` (kode pelanggan) kalau itu juga bagian dari keputusan — cek dulu apakah "dikelompokkan berdasarkan kode pelanggan" ini bagian keputusan #1 atau asumsi desain terpisah.

**#4 — Approval merge pelanggan.** Belum ada kode merge pelanggan sama sekali. Perlu dibangun baru: tabel log merge (audit siapa-approve-kapan, mirip pola `tb_approval_diskon`/`tb_log_cancel_servis`), UI pilih 2 data duplikat + pilih mana yang jadi master, lalu re-point semua FK (`tblkendaraan.nopolisi`? — cek dulu field mana yang jadi FK ke pelanggan) ke `nopelanggan` master. Desain RBAC: kalau Opsi A/C (Kepala Cabang bisa approve), perlu cek middleware RBAC existing (`tb_master_posisi`) apakah levelnya cukup granular per-cabang.

**#10 (angka) — Begitu #1 & threshold final:** update nilai kolom ambang batas per tier di `tbmaster_kategori_member` — ini cuma data entry, bukan perubahan kode, asalkan struktur kolom di atas sudah ada dari #1.

**#14 — Program cuci motor gratis.** Kalau Opsi A (lanjut): perlu tabel baru `tb_voucher_cuci_motor` atau kolom counter di `tblservice`/`statistik_pelanggan` buat lacak "servis ke-berapa" per pelanggan, plus logic auto-generate voucher pas hitungan tercapai. Belum ada kode existing untuk ini — perlu cek dulu draft "2 versi aturan poin" yang disebut dokumen sebelum desain tabel final.

**#19 — Batas rupiah approval pembelian.** Pola desain sudah ada contohnya di `tb_approval_diskon` (F2-C, servis) — replikasi pola sejenis untuk modul Pengadaan: kolom `batas_approval_owner` di tabel setting/`tbcabang`, cek nilai total PO terhadap batas ini sebelum status bisa lanjut ke approve level berikutnya. Cek dulu struktur tabel Pengadaan (`PR`/`PO`/`DO` yang disebut commit terakhir "perbaikan alur pengadaan") buat tau titik insert approval-nya.

**#20 — Persen waspada harga beli.** Perlu kolom `harga_beli_sebelumnya` (kemungkinan sudah ada di riwayat pembelian) dibandingkan ke harga baru saat input PO — kalau selisih > threshold (setting, bukan hardcode), tampilkan warning di UI input. Cek dulu tabel riwayat harga beli existing sebelum nambah kolom baru.

### Fase 2 (audit dulu sebelum eksekusi — berpotensi bentrok kerjaan live)

**#18 vs F1-A:** `tblservice.is_garansi`/`ref_no_service_original` yang sudah ada itu untuk *auto-check masa garansi kadaluarsa* (F1-A), bukan pencatatan *klaim* garansi (siapa nanggung biaya, approval klaim). Kalau Owner jawab Opsi A (perlu kolom resmi), kemungkinan perlu tabel baru `tb_klaim_garansi` (nomor klaim, status approve, biaya ditanggung siapa) — bukan sekadar nambah kolom di `tblservice` yang sudah padat.

**#17 vs F2-B:** cek `app/retur_servis_approve.php` — apakah kode retur stok (`tbstok` tipe='9', disebut di memory F2-B) sekarang treat SEMUA barang retur sama (auto reversal), atau sudah ada percabangan untuk barang yang sudah terpasang fisik. Kalau belum ada percabangan dan Owner konfirmasi rekomendasi hybrid, perlu tambah kolom `status_fisik` (`di_gudang`/`terpasang`) di form retur + validasi: `terpasang` wajib approval manual terpisah, tidak auto-reversal stok.

**#9 vs B1:** cek tabel master diskon (kemungkinan `tbmaster_kategori_member` atau tabel diskon terpisah) — apakah kategori "Bengkel/Rekanan 5%" masih ada barisnya dan aktif dipakai di kode approval diskon (F2-C). Kalau Opsi B (sudah tidak relevan), tinggal nonaktifkan/hapus baris master-nya — bukan perubahan kode besar. Kalau Opsi A (masih dipakai), perlu re-data siapa saja rekanannya.

**#16:** tidak ada perubahan kode diperlukan kalau Owner setuju rekomendasi (Opsi B, tidak perlu tinjau ulang) — cukup pastikan audit trail transaksi koreksi HPP (kalau belum ada, ini jadi item baru: tabel log perubahan HPP).
