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
