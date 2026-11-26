# Runbook deploy produksi (disusun 2026-11-26)

Dijalankan Rafi. Belum pernah diuji di produksi.

Keadaan fm1 (di-update 2026-11-26): kode di `752acc0`; trigger `trg_ns_kdcab_*` ada (18), UNIQUE retur servis,
indeks 11-16, UNIQUE `tblitem.noitem` (11-21, 6 baris terkait), `pelanggan_alias` 9.224 baris,
`audit_no_service --monitor` OK. Belum: Fase 6 (FK). Backup pra-deploy:
`~/backups/pre-deploy-20261126-fitmotor_dbbengkel.sql.gz`. Kode dikirim lewat `git bundle` (tanpa token).
`log_bin=1` dan `log_bin_trust_function_creators=0`: migrasi trigger gagal dengan user aplikasi; di fm1 dipakai
`sudo mysql -e "SET GLOBAL log_bin_trust_function_creators=1"` hanya selama migrasi, lalu dikembalikan ke 0.

Daftar di bawah berasal dari header tiap runner di `db/migrations/` dan dari
`.ai/changelog/2026-11-14-no-service-composite-key.md`. Daftar ini mulai dari 2026-09-30;
migrasi sebelumnya dianggap sudah ada di produksi dan perlu dipastikan.

## 0. Prasyarat (sebelum menyentuh apa pun)

1. Backup penuh database dan file aplikasi. Simpan di luar server.
2. Pastikan runner bisa dijalankan lewat CLI (`php <file> <arg>`).
3. Cek hak membuat trigger. Fase 3 dan Fase 4 membuat trigger; deploy fm1 pernah tertahan di sini (error SUPER).
   Jalankan di database tujuan:
   ```sql
   CREATE TABLE zz_cek_trigger (a INT);
   CREATE TRIGGER zz_cek_trg BEFORE INSERT ON zz_cek_trigger FOR EACH ROW SET NEW.a = NEW.a;
   DROP TRIGGER zz_cek_trg;
   DROP TABLE zz_cek_trigger;
   ```
   Kalau `CREATE TRIGGER` gagal dengan error 1419, minta hosting mengaktifkan `log_bin_trust_function_creators=1`
   atau memberi hak yang diperlukan. **Jangan lanjut** sebelum lolos: runner Fase 3 melakukan `DROP TRIGGER IF EXISTS`
   lalu `CREATE TRIGGER`, dan Fase 4 mengganti `trg_after_service_bayar`.
4. `php scripts/audit_no_service.php --json > baseline.json`.
5. Jadwalkan saat bengkel tutup. Uji waktu Fase 2 di salinan database dulu (`tblservis_barang` ratusan ribu baris).

## 1. Migrasi sebelum deploy kode

Jalankan berurutan. Berhenti di error pertama.

| No | Runner (`db/migrations/`) | Argumen |
|----|---------------------------|---------|
| 1 | `2026-09-30_run_cancel_servis_charset.php` | tanpa argumen |
| 2 | `2026-09-30_run_default_kolom_warisan_access.php` | tanpa argumen |
| 3 | `2026-09-30_run_tblservis_jasa_keterangan.php` | tanpa argumen |
| 4 | `2026-10-04_run_komplain_setting.php` | tanpa argumen |
| 5 | `2026-10-04_run_tbcabang_telepon.php` | tanpa argumen |
| 6 | `2026-10-04_run_merge_kategori_motor_cleanup_wo.php` | `up` |
| 7 | `2026-10-04_run_merge_merek_motor_duplikat.php` | `up` |
| 8 | `2026-10-08_run_inv_core.php` | `up` |
| 9 | `2026-10-08_run_inv_hpp_snapshot.php` | `up` |
| 10 | `2026-10-08_run_inv_koreksi.php` | `up` |
| 11 | `2026-10-08_run_inv_permissions.php` | `up` |
| 12 | `2026-10-13_run_komplain_perm_adm.php` | `up` |
| 13 | `2026-10-13_run_plat_history_seed.php` | `up` |
| 14 | `2026-10-14_run_master_write_perm.php` | `up` |
| 15 | `2026-11-07_run_tblservice_catatan.php` | `up` |
| 16 | `2026-11-14_run_no_service_fase1_bersih.php` | `dry`, lalu `up` |
| 17 | `2026-11-14_run_no_service_fase2_kd_cabang.php` | `dry`, lalu `up` |
| 18 | `2026-11-14_run_no_service_fase3_trigger_kd_cabang.php` | `up` (tidak ada `dry`) |
| 19 | `2026-11-15_run_center_reload_schema.php` | `up` |
| 20 | `2026-11-16_run_fase_k_po_composite.php` | `up` |
| 21 | `2026-11-16_run_fase_k_unique_header.php` | `up` (gagal aman bila ada nomor dobel) |
| 22 | `2026-11-16_run_index_performa_servis_stok.php` | `up` |
| 23 | `2026-11-17_run_unique_retur_servis.php` | `dry`, lalu `up` |
| 24 | `2026-11-21_run_master_item_dedupe.php` | `dry`, lalu `up` |

Catatan:
- Nomor 24 menghapus item uji beserta baris transaksinya. Baca hasil `dry` dengan data produksi sebelum `up`.
- Runner tanpa `dry` langsung mengubah data. Backup adalah jaring pengaman untuk runner tersebut.

## 2. Deploy kode

1. Merge `feat/center-reload-adiwerna` ke `main` (setelah disetujui).
2. Deploy. Pastikan `app/db_env.php` dan `config/db_env.php` ada di server (tidak ikut git).

## 3. Sesudah deploy

1. `2026-11-14_run_no_service_fase4_view_trigger.php`: `dry`, lalu `up`.
2. `php scripts/audit_no_service.php --monitor` harus `STATUS: OK`.
3. `2026-11-14_run_no_service_fase6_foreign_key.php`: `dry`, lalu `up`, hanya setelah E2E di staging lulus.
4. `make install-hooks` (di mesin pengembang).
5. Nonaktifkan akun `TEST-*` dan `e2e_test_*`.
6. Uji singkat per cabang: servis sampai bayar, penjualan tunai, pembelian, retur servis.

## 4. Di luar runbook ini (perlu keputusan dulu)

- Muat ulang data per cabang dari `fitmotor_center` ke produksi (`scripts/center_reload/reload_cabang.php`).
- `2026-11-26_run_pelanggan_alias.php`: butuh database `fitmotor_center` di server MySQL yang sama.
- Moving Average mode `live`: ikuti `docs/planning/RUNBOOK_CUTOVER_MOVING_AVERAGE.md`. `inv_mode` tetap `off`.
- Karantina `ns_karantina`: tentukan cabang per baris, baru pasang NOT NULL.

## Rollback

Kembalikan kode ke commit sebelumnya dan pulihkan backup. Runner ber-argumen `down` bisa dipakai mundur satu
per satu dalam urutan terbalik, dengan batasan yang tertulis di header masing-masing.
