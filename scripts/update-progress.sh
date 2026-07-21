#!/usr/bin/env bash
# ============================================================
# update-progress.sh — update status ke Google Sheet
# "Monitoring Web bengkel" (sheet "Progress") lewat Web App API.
#
# PAKAI:
#   ./update-progress.sh "Modul" "Sub-item" "Status" "Catatan"
#
# CONTOH:
#   ./update-progress.sh "Kendaraan" "Menu Pindah Kepemilikan Kendaraan" \
#     "✅ Selesai & Live" "Migrasi sudah dijalankan, tabel sudah ada di produksi"
#
# STATUS yang valid (harus PERSIS sama, termasuk emoji):
#   ✅ Selesai & Live
#   🔶 Sedang Dikerjakan
#   🔴 Blocked - Menunggu Jawaban Owner
#   ⚠️ Perlu Perhatian
#   ⬜ Belum Dikerjakan (Rencana)
#
# SETUP SEKALI:
#   export PROGRESS_API_TOKEN="isi_token_kamu"
#   (taruh baris ini di ~/.bashrc / ~/.zshrc, atau di file .env
#   lokal yang TIDAK dicommit ke git)
#
# Butuh: curl, jq (buat escape JSON dengan aman)
# ============================================================

set -euo pipefail

WEBAPP_URL="${PROGRESS_WEBAPP_URL:-https://script.google.com/macros/s/AKfycbyEuscIfLd61icFQCOWW3SBJCcAJGTNlAeFdIKjaYddR_tuKIt6zMJJWgZ7at_zDl0f0w/exec}"

# Token API diambil dari Script Property yang di-set di Apps Script
# (Project Settings > Script Properties, bagian API_TOKEN).
# WAJIB diset lewat env var PROGRESS_API_TOKEN — tidak ada default
# ditulis di sini supaya token tidak ikut kecommit ke repo yang juga
# diakses anak PKL.
API_TOKEN="${PROGRESS_API_TOKEN:-}"

if [ -z "$API_TOKEN" ]; then
  echo "Error: token API kosong. Set env var PROGRESS_API_TOKEN (mis. di ~/.bashrc atau .env lokal yang tidak dicommit)." >&2
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "Error: 'jq' belum terinstall. Install dulu (mis. apt install jq / brew install jq)." >&2
  exit 1
fi

if [ "$#" -lt 3 ]; then
  echo "Pakai: $0 \"Modul\" \"Sub-item\" \"Status\" [\"Catatan\"]" >&2
  exit 1
fi

MODUL="$1"
SUBITEM="$2"
STATUS="$3"
CATATAN="${4:-}"

PAYLOAD=$(jq -n \
  --arg token "$API_TOKEN" \
  --arg modul "$MODUL" \
  --arg subItem "$SUBITEM" \
  --arg status "$STATUS" \
  --arg catatan "$CATATAN" \
  --arg sumber "Claude Code" \
  '{token: $token, modul: $modul, subItem: $subItem, status: $status, catatan: $catatan, sumber: $sumber}')

RESPONSE=$(curl -s -L --post302 --post303 -X POST "$WEBAPP_URL" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD")

echo "$RESPONSE"

if echo "$RESPONSE" | jq -e '.ok == true' >/dev/null 2>&1; then
  exit 0
else
  echo "Update gagal, cek response di atas." >&2
  exit 1
fi
