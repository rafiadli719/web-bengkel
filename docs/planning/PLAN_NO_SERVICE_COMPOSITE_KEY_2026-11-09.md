# Rencana perbaikan akar masalah `no_service` tidak unik lintas cabang

Tanggal: 2026-11-09. Status: hasil investigasi (read-only) dan rencana. Belum ada perubahan skema atau kode.

## 1. Ringkasan

`no_service` memang tidak unik secara global (30.889 nilai muncul di lebih dari satu cabang, 78.106 dari 103.551 baris `tblservice`). Itu bukan akar masalah yang bisa "dihapus": nomor yang sudah tercetak di nota, WhatsApp, dan laporan tidak boleh diganti. Identitas servis yang benar adalah **kunci gabungan `(no_service, kd_cabang)`**, dan kunci itu sudah ada di `tblservice` (`uq_service_cabang`, PRIMARY KEY).

Kebakaran yang tersisa ada di lapisan di bawahnya:

1. 18 tabel anak memakai `no_service` saja tanpa `kd_cabang`, sehingga join dan lookup bisa salah cabang.
2. 8 dari 11 view yang memakai `no_service` tidak menyertakan `kd_cabang`.
3. Tidak ada foreign key nyata ke `tblservice` (0 FK), jadi data yatim bisa muncul tanpa ada yang menolak.
4. Tabel yang sudah punya `kd_cabang` pun tidak bersih: ada baris kosong dan yatim.
5. Tidak ada penjaga yang mencegah kode baru mengulang pola join `no_service` saja.

## 2. Temuan (DB lokal, salinan produksi)

Generator nomor baru sudah atomik (`tblservice_no_counter`, `NextServiceSeqByPrefix`). Pastikan juga aktif di produksi.

Tabel anak TANPA `kd_cabang` (baris ambigu / total):

| Tabel | Ambigu / Total |
|---|---|
| tbservis_workorder | 36 / 57 |
| tbservis_pending_items | 33 / 68 |
| tbservis_keluhan_status | 28 / 62 |
| tb_antrian_servis | 11 / 18 |
| tbservis_temuan | 10 / 15 |
| tbservis_penawaran_part | 4 / 8 |
| master_kedatangan_pelanggan | 2 / 4 |
| tbservis_pengerjaan | 1 / 4 |
| tb_log_cancel_servis, tbservis_keluhan, promo_usage_log, tb_log_antrian, tb_progress_mekanik, tb_mekanik_progress, tbservis_keluhan_tracking, tbservis_penawaran_jasa, tbservis_penawaran_log, tbservis_temuan_activity_log | 0 ambigu (kecil/kosong), tetap perlu kolom |

Tabel yang sudah punya `kd_cabang` tapi kotor (kosong / yatim): `tblservis_barang` 7 / 12, `tblservis_jasa` 4 / 11, `tblservice_advisor` 1 / 1, `servis_komisi` 0 / 6, `tb_dp_servis` 0 / 1, `tblretur_servis_header` dan `_detail` 0 / 2.

Lain-lain: 1 trigger (`trg_after_service_bayar`) menyentuh `no_service`. View tanpa `kd_cabang`: `view_penawaran_part_lengkap`, `view_ringkasan_kedatangan_pelanggan`, `view_riwayat_kedatangan_detail`, `view_servis_keluhan_lengkap`, `view_servis_status_summary`, `view_servis_temuan_lengkap`, `view_servis_workorder_lengkap`, `view_temuan_penawaran_summary`.

Catatan: angka di atas dari DB lokal. Hitung ulang di produksi sebelum migrasi (skrip audit di Fase 0).

## 3. Keputusan desain

1. Pertahankan nomor lama. Jangan renumber `no_service` (merusak nota, WA, arsip).
2. Identitas servis = `(no_service, kd_cabang)`. Semua tabel anak membawa `kd_cabang`, semua join memakai keduanya.
3. Baris lama yang tidak bisa dipastikan cabangnya TIDAK ditebak. Dikarantina (`kd_cabang` NULL + tercatat di tabel karantina), tidak ikut join sampai ditetapkan manual.
4. Foreign key gabungan ditambahkan setelah data bersih. Fase terakhir dan butuh persetujuan Rafi.
5. Pencegahan: skrip audit yang gagal bila ada query join `tblservice` hanya dengan `no_service`.

## 4. Fase kerja

**Fase 0 - Baseline (read-only, aman).** Skrip audit baku `scripts/audit_no_service.php`: hitung ambigu per tabel, kosong, yatim, view tanpa `kd_cabang`. Jalankan di lokal dan produksi, simpan hasilnya.

**Fase 1 - Bersihkan tabel yang sudah punya `kd_cabang`.** Isi baris kosong dan analisa yatim di `tblservis_barang/jasa/advisor`, `servis_komisi`, `tb_dp_servis`, `tblretur_*`. Isi hanya bila induk bisa dipastikan (satu kandidat); sisanya ke karantina. Migrasi reversible dengan backup `bak_*`.

**Fase 2 - Tambah `kd_cabang` ke 18 tabel anak.** Migrasi: tambah kolom NULL + indeks `(no_service, kd_cabang)`. Backfill: baris dengan `no_service` tunggal diisi otomatis; baris ambigu dicoba lewat sinyal lain (pembuat/user cabang, tanggal terdekat dengan `tblservice.tanggal`); yang tetap ambigu ke karantina. Setelah tuntas dan kode baru live, ubah ke NOT NULL.

**Fase 3 - Ubah kode.** Semua INSERT ke tabel Fase 2 menulis `kd_cabang` dari sesi. Semua SELECT/JOIN/UPDATE/DELETE memakai `(no_service, kd_cabang)`. Termasuk 3 file A3: `_include_kategori_member.php`, `_ajax/ajax-get-discount-preview.php`, `_include_statistik_pelanggan.php`. Urutan deploy: migrasi kolom (NULL) dulu, lalu kode, lalu NOT NULL.

**Fase 4 - View dan trigger.** Tulis ulang 8 view dengan join gabungan. Tinjau `trg_after_service_bayar`.

**Fase 5 - Penjaga.** `scripts/audit_no_service.php` ditambah pemeriksaan kode (grep pola join `no_service` tanpa `kd_cabang`), dijalankan di `make lint`/pre-commit. Monitor harian: jumlah duplikat `(no_service, kd_cabang)` harus 0, dan hitung baris tanpa induk.

**Fase 6 - Foreign key (opsional, persetujuan Rafi).** FK gabungan ke `tblservice` setelah yatim = 0 dan karantina selesai. Uji pada salinan dulu karena sinkronisasi Access menulis anak dan induk tidak selalu berurutan.

## 5. Risiko dan batas

- Semua migrasi produksi dijalankan Rafi lewat runner (reversible, backup dulu). Saya tidak menyentuh produksi.
- Atribusi cabang untuk baris lama adalah penilaian, bukan kepastian; karena itu karantina, bukan tebakan.
- Migrasi besar (`tblservis_barang` 361 ribu baris) perlu jendela tenang; uji waktu di salinan.
- Tiap fase punya tes sebelum lanjut: audit sebelum/sesudah, uji handler lewat harness CLI, E2E browser lokal untuk alur utama (booking, WO, bayar, batal, garansi).

## 6. Urutan eksekusi yang diusulkan

Fase 0 -> 1 -> 2 (lokal) -> 3 -> 4 -> 5 -> serahkan paket migrasi + runbook ke Rafi -> Fase 6 setelah produksi stabil.
