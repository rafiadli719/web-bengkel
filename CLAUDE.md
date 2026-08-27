## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Lapor Progress ke checklist-projek

Repo ini ("FIT MOTOR WEB BASE") di-track progressnya di dashboard terpusat
checklist-projek (`https://checklist.fitmotor.web.id`, lokal:
`http://localhost:8090`). **Jangan ubah kode/struktur repo ini demi
integrasi ini** — cukup panggil API checklist-projek pas Claude Code
sesi kerja di sini menyelesaikan satu Fitur (bukan tiap commit kecil).

Alur:
1. `GET {CHECKLIST_BASE_URL}/api/features?search=<nama fitur>` dengan header
   `X-API-Key: {CHECKLIST_API_KEY}` — cari `id` fitur yang namanya paling
   cocok dengan kerjaan yang baru selesai.
2. `PATCH {CHECKLIST_BASE_URL}/api/features/{id}/progress` header sama,
   body JSON:
   ```json
   {
     "status_pengembangan": "development | uat | selesai",
     "progress_percent": 0,
     "keterangan": "catatan singkat, misal blocker atau apa yang baru selesai"
   }
   ```
   (`progress_percent` 0-100.)
3. `CHECKLIST_BASE_URL` dan `CHECKLIST_API_KEY` diambil dari environment
   lokal developer (tanya Rafi kalau belum ada) — JANGAN hardcode key di
   commit manapun.
4. Kalau `GET /api/features` tidak nemu fitur yang cocok (belum terdaftar
   di checklist-projek), JANGAN membuat fitur baru sendiri — laporkan ke
   Rafi supaya didaftarkan manual dulu di dashboard.
5. Panggilan ini best-effort — kalau checklist-projek lagi down/unreachable,
   lanjutkan kerjaan seperti biasa, jangan blocking, cukup sebutkan ke Rafi
   bahwa update progress gagal terkirim.
