# 2026-11-14 - Identitas servis = (no_service, kd_cabang): Fase 0-6 (lokal)

Plan: `docs/planning/PLAN_NO_SERVICE_COMPOSITE_KEY_2026-11-09.md`. Dikerjakan dan divalidasi di DB lokal.
PRODUKSI BELUM disentuh, semua runner dijalankan Rafi.

## Added
- `scripts/audit_no_service.php` (`--kode [file...]`, `--monitor`, `--json`), baseline di `.ai/audit/`.
- `scripts/git-hooks/pre-commit` + `Makefile` (`make lint`, `make monitor-no-service`, `make install-hooks`).
- Runner reversible `db/migrations/2026-11-14_run_no_service_fase{1,2,3,4,6}_*.php` (`dry|up|down`).
- Tabel pendukung: `ns_karantina`, `ns_koreksi_log`, `ns_ddl_backup`, `ns_fk_log`.

## Changed
- Fase 1: 7 grup baris barang/jasa `kd_cabang` kosong diisi (induk tunggal); 13 grup ke karantina (tidak ditebak).
- Fase 2: kolom `kd_cabang` + indeks `(no_service, kd_cabang)` di 18 tabel anak. 22 grup diisi, 124 grup
  ambigu/yatim ke karantina (baris ambigu dibuat 2025-06..2026-10, induk kandidat semua Jan 2025: tanggal tak bisa membedakan).
- Fase 3: trigger BEFORE INSERT `trg_ns_kdcab_*` (induk tunggal, lalu `@kd_cabang_filter`); 18 join kode dan ~15 UPDATE/DELETE
  dibatasi `(no_service, kd_cabang)`. DP helper `getDpPendingTotal/offsetDpPending` menerima `$kd_cabang` (default sesi).
- Fase 4: 8 view memakai join gabungan + kolom `kd_cabang`; `trg_after_service_bayar` memakai `kd_cabang`.
- Fase 6: 13 FK gabungan (`fk_ns_*`, CASCADE) ke tabel operasional web-only. Log audit dan tabel yang ditulis sinkronisasi Access
  (`tblservis_barang/jasa/advisor`) sengaja tanpa FK. `no_service` 7 tabel dari utf8mb4 ke latin1 (syarat FK).

## Fixed (bug nyata yang terungkap)
- 8 view menggandakan baris karena join hanya `no_service` (workorder 165 baris untuk 57 WO, keluhan 146 untuk 62).
- `lap_servis` / `_pdf` / `_xls` menjumlah barang dan jasa lintas cabang bernomor sama (total membengkak).
- `servis-cancel-proses.php` meng-update `tblservice` hanya dengan `no_service` (bisa membatalkan servis cabang lain).
- `offsetDpPending` menandai DP pending lintas cabang sebagai terpakai.
- `servis-carinopol-kosongkan.php`: guard darurat "tolak bila no_service di >1 cabang" diganti scoping benar.

## Validasi
- Audit akhir: kode berisiko 0, monitor `STATUS: OK`, duplikat kunci induk 0, baris kosong tak terlacak 0.
- Tiap runner: dry, up, up ulang (idempotent), down, up. Trigger kd_cabang 4/4 skenario, FK 5/5 skenario (rollback),
  cascade work order terbukti, trigger bayar menulis kd_cabang, pre-commit tolak/lolos benar.
- 10 halaman terubah dimuat di browser (sesi admin): HTTP 200, tanpa tanda error, tanpa error baru di log PHP.
- BELUM: E2E klik penuh booking -> WO -> bayar -> batal -> garansi setelah FK aktif (lihat runbook).

## Runbook produksi (urutan, tiap langkah: backup DB dulu, `dry` dulu)
1. `php scripts/audit_no_service.php --json > baseline.json` (hitung ulang angka produksi).
2. Fase 1 -> 2 -> 3 (trigger) -> deploy kode -> 4 -> `--monitor` harus OK -> 6 setelah E2E di staging.
3. `tblservis_barang` 361 ribu baris: uji waktu Fase 2 di salinan, jalankan saat sepi.
4. Karantina (`ns_karantina` status open): Rafi putuskan cabang per baris, lalu NOT NULL.
5. Aktifkan hook: `make install-hooks`.
