# ============================================================
# update-checklist.ps1 - PowerShell version of update-checklist.sh
# ============================================================
param (
    [Parameter(Position = 0, Mandatory = $true)]
    [string]$Feature,

    [Parameter(Position = 1, Mandatory = $true)]
    [string]$Status,

    [Parameter(Position = 2, Mandatory = $false)]
    [int]$Progress = 100,

    [Parameter(Position = 3, Mandatory = $false)]
    [string]$Keterangan = ""
)

$baseUrl = if ($env:CHECKLIST_BASE_URL) { $env:CHECKLIST_BASE_URL } else { "http://localhost:8090" }
$apiKey  = $env:API_KEY_WEBBENGKEL

if ([string]::IsNullOrEmpty($apiKey)) {
    Write-Host "[ERROR] Token kosong. Set env var API_KEY_WEBBENGKEL (mis. di profile PowerShell atau .env lokal yang tidak dicommit)." -ForegroundColor Red
    exit 1
}

$payload = @{
    feature          = $Feature
    status           = $Status
    progress_percent = $Progress
    keterangan       = $Keterangan
} | ConvertTo-Json

$headers = @{
    "Content-Type" = "application/json"
    "X-API-Key"    = $apiKey
}

try {
    $res = Invoke-RestMethod -Uri "$baseUrl/api/webhook/progress" -Method POST -Headers $headers -Body $payload
    Write-Host "[SUCCESS] $($res.message)" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Gagal update progress: $_" -ForegroundColor Red
    exit 1
}
