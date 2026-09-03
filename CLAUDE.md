## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Lapor Progress ke checklist-projek

Repo ini ("FIT MOTOR WEB BASE") di-track progressnya di dashboard terpusat
checklist-projek — produksi `https://checklist.fitmotor.web.id` (default,
lihat `CHECKLIST_BASE_URL` di `.env`), lokal `http://localhost:8090` kalau
mau tes tanpa nyentuh data produksi. **Jangan ubah kode/struktur repo ini
demi integrasi ini** — cukup panggil `scripts/update-checklist.sh` pas
Claude Code sesi kerja di sini menyelesaikan satu Fitur (bukan tiap commit
kecil).

Progress tracking sebelumnya lewat Google Sheet ("Monitoring Web bengkel")
sudah **dihentikan** — `scripts/update-progress.sh` dan `PROGRESS_API_TOKEN`
sudah dihapus. checklist-projek dashboard ini satu-satunya sumber progress
sekarang.

```bash
./scripts/update-checklist.sh "nama fitur" "status" "progress_percent" "keterangan"
# contoh:
./scripts/update-checklist.sh "Login SSO" "development" 60 "Middleware auth selesai, tinggal testing"
./scripts/update-checklist.sh "Login SSO" "selesai" 100 "Sudah UAT & live"
```

Status valid (`status_pengembangan`): `belum_mulai | desain | development | uat | selesai`.

Modul/fitur baru yang belum terdaftar sekarang bisa dibikin langsung lewat
mode `--create` (manggil `POST /api/features` di checklist-projek), tidak
perlu didaftarkan manual lagi:

```bash
./scripts/update-checklist.sh --create "nama fitur" "nama projek" ["moscow"] ["status_kerja"] ["catatan"]
# contoh:
./scripts/update-checklist.sh --create "Modul Retur Servis" "FIT MOTOR WEB BASE" "should" "belum" "Belum dikerjakan"
```

Moscow valid: `must | should | could | wont` (default `should`).
Status_kerja valid: `belum | proses | tuntas` (default `belum`).
Nama projek harus sudah terdaftar di checklist-projek (mis. "FIT MOTOR WEB
BASE") — mode ini cuma bikin modul di dalam projek yang ada, bukan bikin
projek baru.

Catatan:
1. Butuh env var `CHECKLIST_BASE_URL` dan `API_KEY_WEBBENGKEL` (sudah diset
   di `.env` lokal — JANGAN hardcode/commit value-nya).
2. Kalau script bilang fitur tidak ditemukan pada mode update biasa (belum
   terdaftar di checklist-projek), pakai mode `--create` di atas buat
   daftarin sekarang — gak perlu lapor manual ke Rafi lagi.
3. Kalau script bilang nama fitur ambigu (>1 match), perjelas nama yang
   dipanggil.
4. Panggilan ini best-effort — kalau checklist-projek lagi down/unreachable
   (script exit 1), lanjutkan kerjaan seperti biasa, jangan blocking, cukup
   sebutkan ke Rafi bahwa update progress gagal terkirim.

## Progress Merge Modul Kasir → Keuangan (2026-09-03)

Plan lengkap: `docs/superpowers/plans/2026-09-03-merge-modul-kasir-keuangan.md`
(34 task). Status per 2026-09-03 malam:

- **Task 1-10 SELESAI & commit**: backup DB (372MB), DDL 25 tabel
  `*_closing_kasir` + 10 VIEW, migrasi data ~50rb+ baris (row-count match
  semua), RBAC (`tb_master_posisi.permissions` +5 kode `kasir_*` di
  ADM/KEU/KSR).
- **Task 11-12 SELESAI & commit**: porting `app/_keuangan/kasir/` —
  `koneksi_kasir.php`, `kas_awal.php`, `kas_akhir.php`,
  `closing_revision_helpers.php`, `pemasukan.php`, `pengeluaran.php`,
  `process_closing_transaction.php`, `utils.php`. Sumber real beda dari
  draft plan awal (dashboard besar, bukan file kecil) — lihat commit
  message masing-masing buat detail keputusan porting.
- **Keputusan susulan (2026-09-03 malam)**: tabel `kas_awal_closing_kasir`
  → di-rename jadi **`kas_awal`** (dan `kas_akhir_closing_kasir` →
  `kas_akhir`, `detail_kas_awal_closing_kasir` → `detail_kas_awal`,
  `detail_kas_akhir_closing_kasir` → `detail_kas_akhir`) — TIDAK ada
  tabrakan nama sama tabel fitmotor lama (dicek: gak ada tabel `kas_awal`/
  `kas_akhir` asli di `fitmotor_dbbengkel`, fitur kasir lama fitmotor
  pakai `tbkas_kasir_header`/`tbkeping` bukan nama itu). File lama
  `app/kas_awal.php`/`app/kas_akhir.php` (pakai `tbkas_kasir_header`)
  MASIH LIVE, belum diganti — penggantian menu ke versi baru
  `app/_keuangan/kasir/kas_awal.php` ditahan sampai Task 15 (menu
  wiring), biar gak putus fitur user tanpa checkpoint.
- **Task 13-34 BELUM dikerjakan**: closing (`close_transaksi1.php`
  141KB), closing revisi, `setoran_keuangan.php` (424KB, terbesar
  seprojek), cutover (Task 17) & drop tabel mati (Task 18) — 2 task
  terakhir itu WAJIB tanya konfirmasi eksplisit dulu sebelum eksekusi
  (irreversible, sistem kasir/keuangan live).
