#!/usr/bin/env bash
# ============================================================
# update-checklist.sh — update progress fitur ke dashboard
# checklist-projek (https://checklist.fitmotor.web.id).
#
# PAKAI:
#   ./update-checklist.sh "nama fitur" "status" "progress_percent" "keterangan"
#   ./update-checklist.sh --create "nama fitur" "nama projek" ["moscow"] ["status_kerja"] ["catatan"]
#
# CONTOH:
#   ./update-checklist.sh "Login SSO" "development" 60 "Middleware auth selesai, tinggal testing"
#   ./update-checklist.sh "Login SSO" "selesai" 100 "Sudah UAT & live"
#   ./update-checklist.sh --create "Modul Retur Servis" "FIT MOTOR WEB BASE" "should" "belum" "Belum dikerjakan"
#
# STATUS yang valid (status_pengembangan, dipakai mode update biasa):
#   belum_mulai | desain | development | uat | selesai
#
# MOSCOW yang valid (dipakai mode --create):
#   must | should | could | wont
# STATUS_KERJA yang valid (dipakai mode --create):
#   belum | proses | tuntas
#
# Mode --create manggil POST /api/features (bikin modul/fitur baru di
# projek yang SUDAH ADA). Kalau projeknya sendiri belum terdaftar,
# daftarkan manual dulu di dashboard — script ini tidak bikin projek.
#
# SETUP SEKALI:
#   export CHECKLIST_BASE_URL="http://localhost:8090"   # atau https://checklist.fitmotor.web.id
#   export API_KEY_WEBBENGKEL="isi_token_kamu"
#   (taruh baris ini di ~/.bashrc / ~/.zshrc, atau di file .env
#   lokal yang TIDAK dicommit ke git)
#
# Butuh: curl, jq
#
# Best-effort: kalau checklist-projek lagi down/unreachable, script
# exit 1 tapi TIDAK boleh dianggap blocking — lanjutkan kerjaan seperti
# biasa dan sebutkan ke Rafi kalau gagal.
# ============================================================

set -euo pipefail

BASE_URL="${CHECKLIST_BASE_URL:-http://localhost:8090}"
API_KEY="${API_KEY_WEBBENGKEL:-}"

if [ -z "$API_KEY" ]; then
  echo "Error: token kosong. Set env var API_KEY_WEBBENGKEL (mis. di ~/.bashrc atau .env lokal yang tidak dicommit)." >&2
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "Error: 'jq' belum terinstall. Install dulu (mis. apt install jq)." >&2
  exit 1
fi

if [ "$#" -lt 2 ]; then
  echo "Pakai: $0 \"nama fitur\" \"status\" [\"progress_percent\"] [\"keterangan\"]" >&2
  echo "   atau: $0 --create \"nama fitur\" \"nama projek\" [\"moscow\"] [\"status_kerja\"] [\"catatan\"]" >&2
  exit 1
fi

# ---- Mode --create: bikin modul/fitur baru di projek yang sudah ada ----
if [ "$1" = "--create" ]; then
  if [ "$#" -lt 3 ]; then
    echo "Pakai: $0 --create \"nama fitur\" \"nama projek\" [\"moscow\"] [\"status_kerja\"] [\"catatan\"]" >&2
    exit 1
  fi

  NAMA_FITUR="$2"
  PROJECT_NAME="$3"
  MOSCOW="${4:-should}"
  STATUS_KERJA="${5:-belum}"
  CATATAN="${6:-}"

  VALID_MOSCOW="must should could wont"
  if ! echo "$VALID_MOSCOW" | grep -qw "$MOSCOW"; then
    echo "Error: moscow '$MOSCOW' tidak valid. Pilih salah satu: $VALID_MOSCOW" >&2
    exit 1
  fi

  VALID_STATUS_KERJA="belum proses tuntas"
  if ! echo "$VALID_STATUS_KERJA" | grep -qw "$STATUS_KERJA"; then
    echo "Error: status_kerja '$STATUS_KERJA' tidak valid. Pilih salah satu: $VALID_STATUS_KERJA" >&2
    exit 1
  fi

  PAYLOAD=$(jq -n \
    --arg name "$NAMA_FITUR" \
    --arg project_name "$PROJECT_NAME" \
    --arg moscow "$MOSCOW" \
    --arg status_kerja "$STATUS_KERJA" \
    --arg catatan "$CATATAN" \
    '{name: $name, project_name: $project_name, moscow: $moscow, status_kerja: $status_kerja, catatan: $catatan}')

  RESPONSE=$(curl -s -X POST "$BASE_URL/api/features" \
    -H "Content-Type: application/json" \
    -H "X-API-Key: $API_KEY" \
    -d "$PAYLOAD")

  echo "$RESPONSE"

  if echo "$RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
    exit 0
  else
    echo "Bikin modul/fitur gagal, cek response di atas (404 = projek belum terdaftar, 409 = nama sudah ada)." >&2
    exit 1
  fi
fi

NAMA_FITUR="$1"
STATUS="$2"
PROGRESS="${3:-}"
KETERANGAN="${4:-}"

VALID_STATUS="belum_mulai desain development uat selesai"
if ! echo "$VALID_STATUS" | grep -qw "$STATUS"; then
  echo "Error: status '$STATUS' tidak valid. Pilih salah satu: $VALID_STATUS" >&2
  exit 1
fi

# 1. Cari ID fitur berdasarkan nama
SEARCH_RESPONSE=$(curl -s -G "$BASE_URL/api/features" \
  -H "X-API-Key: $API_KEY" \
  --data-urlencode "search=$NAMA_FITUR")

if ! echo "$SEARCH_RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
  echo "Gagal cari fitur (cek koneksi/token). Response: $SEARCH_RESPONSE" >&2
  exit 1
fi

FEATURE_COUNT=$(echo "$SEARCH_RESPONSE" | jq '.data.features | length')

if [ "$FEATURE_COUNT" -eq 0 ]; then
  echo "Fitur '$NAMA_FITUR' tidak ditemukan di checklist-projek. Daftarkan manual dulu di dashboard, jangan buat otomatis." >&2
  exit 1
fi

if [ "$FEATURE_COUNT" -gt 1 ]; then
  echo "Ada $FEATURE_COUNT fitur yang cocok dengan '$NAMA_FITUR', tidak unik:" >&2
  echo "$SEARCH_RESPONSE" | jq -r '.data.features[] | "  - \(.name) (id: \(.id), projek: \(.main_project_name) / \(.sub_project_name))"' >&2
  echo "Perjelas nama fitur." >&2
  exit 1
fi

FEATURE_ID=$(echo "$SEARCH_RESPONSE" | jq -r '.data.features[0].id')

# 2. Susun payload update (progress_percent opsional)
if [ -n "$PROGRESS" ]; then
  PAYLOAD=$(jq -n \
    --arg status "$STATUS" \
    --argjson progress "$PROGRESS" \
    --arg keterangan "$KETERANGAN" \
    '{status_pengembangan: $status, progress_percent: $progress, keterangan: $keterangan}')
else
  PAYLOAD=$(jq -n \
    --arg status "$STATUS" \
    --arg keterangan "$KETERANGAN" \
    '{status_pengembangan: $status, keterangan: $keterangan}')
fi

# 3. PATCH progress
RESPONSE=$(curl -s -X PATCH "$BASE_URL/api/features/$FEATURE_ID/progress" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -d "$PAYLOAD")

echo "$RESPONSE"

if echo "$RESPONSE" | jq -e '.success == true' >/dev/null 2>&1; then
  exit 0
else
  echo "Update gagal, cek response di atas." >&2
  exit 1
fi
