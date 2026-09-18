$ErrorActionPreference = 'Stop'
$base = 'http://localhost/web-bengkel/aplikasi/aplikasi/'
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$login = Invoke-WebRequest -Uri ($base + 'login.php') -WebSession $s -UseBasicParsing -TimeoutSec 20
$matches = [regex]::Matches($login.Content, '<option value="([^"]+)">([^<]+)</option>')
$cabang = ''
foreach ($m in $matches) { if ($m.Groups[2].Value -match 'Pusat|PST') { $cabang = $m.Groups[1].Value; break } }
if (-not $cabang -and $matches.Count -gt 1) { $cabang = $matches[1].Groups[1].Value }
Invoke-WebRequest -Uri ($base + 'cek_login.php') -Method Post -Body @{txtnama='admin';txtpass='admin';cbocabang=$cabang} -WebSession $s -MaximumRedirection 10 -UseBasicParsing | Out-Null

$r = Invoke-WebRequest -Uri ($base + 'app/workorder-list.php') -WebSession $s -UseBasicParsing -TimeoutSec 30
$activeDataMaster = $r.Content -match '(?s)<li class="[^"]*active[^"]*open[^"]*">\s*<a href="#" class="dropdown-toggle">.*?<span class="menu-text">\s*Data Master'
$activeDaftarItem = $r.Content -match '(?s)<li class="[^"]*active[^"]*open[^"]*">\s*<a href="#" class="dropdown-toggle">.*?<span class="menu-text">\s*Daftar Item'
$activeWorkOrder = $r.Content -match '(?s)<li class="[^"]*active[^"]*">\s*<a href="workorder-list\.php">.*?<span class="menu-text">Work Order/Paket'
Write-Output "ACTIVE STATUS=200 DATA_MASTER=$activeDataMaster DAFTAR_ITEM=$activeDaftarItem WORK_ORDER=$activeWorkOrder"

try {
    $missing = Invoke-WebRequest -Uri ($base + 'app/master_item.php') -WebSession $s -UseBasicParsing -TimeoutSec 30 -ErrorAction Stop
    $missingHtml = $missing.Content
    $status = $missing.StatusCode
} catch {
    $status = [int]$_.Exception.Response.StatusCode
    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
    $missingHtml = $reader.ReadToEnd()
}
$hasTitle = $missingHtml -match 'Halaman Master Item Tidak Digunakan'
$hasMasterBarang = $missingHtml -match 'Buka Master Barang'
$hasWorkOrder = $missingHtml -match 'Buka Work Order/Paket'
$rawError = $missingHtml -match 'No input file specified'
Write-Output "MASTER_ITEM STATUS=$status FRIENDLY_TITLE=$hasTitle MASTER_BARANG_CTA=$hasMasterBarang WORK_ORDER_CTA=$hasWorkOrder RAW_SERVER_ERROR=$rawError"
if (-not ($activeDataMaster -and $activeDaftarItem -and $activeWorkOrder -and $status -eq 404 -and $hasTitle -and $hasMasterBarang -and $hasWorkOrder -and -not $rawError)) { exit 1 }
