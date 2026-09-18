$ErrorActionPreference = 'Stop'
$base = 'http://localhost/web-bengkel/aplikasi/aplikasi/'
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$login = Invoke-WebRequest -Uri ($base + 'login.php') -WebSession $s -UseBasicParsing -TimeoutSec 20
$branchMatches = [regex]::Matches($login.Content, '<option value="([^"]+)">([^<]+)</option>')
$cab = ''
foreach ($m in $branchMatches) {
    if ($m.Groups[2].Value -match 'Pusat|PST') { $cab = $m.Groups[1].Value; break }
}
if (-not $cab -and $branchMatches.Count -gt 1) { $cab = $branchMatches[1].Groups[1].Value }
$body = @{ txtnama='admin'; txtpass='admin'; cbocabang=$cab }
$resp = Invoke-WebRequest -Uri ($base + 'cek_login.php') -Method Post -Body $body -WebSession $s -MaximumRedirection 10 -UseBasicParsing -ErrorAction Stop
Write-Output "LOGIN FINAL=$($resp.BaseResponse.ResponseUri.AbsoluteUri) CABANG=$cab"
$checks = @(
    @{Label='Work Order/Paket'; Path='app/workorder-list.php'; Must='Daftar Work Order'},
    @{Label='Master Jasa Service'; Path='app/jasa-list.php'; Must='Master Jasa Service'},
    @{Label='Master Perusahaan'; Path='app/master_perusahaan.php'; Must='Master Perusahaan'},
    @{Label='Kirim Barang Tanpa Request'; Path='app/pengadaan_antarcab_push.php'; Must='Kirim Barang'},
    @{Label='Ringkasan Antar Cabang'; Path='app/lap_antarcab.php'; Must='Laporan Transaksi Antar Cabang'},
    @{Label='Profit & Insentif'; Path='app/lap_profit_insentif.php'; Must='Laporan Profit'}
)
foreach ($c in $checks) {
    try {
        $r = Invoke-WebRequest -Uri ($base + $c.Path) -WebSession $s -UseBasicParsing -TimeoutSec 30
        $plain = $r.Content -replace '<script[\s\S]*?</script>','' -replace '<style[\s\S]*?</style>','' -replace '<[^>]+>',' ' -replace '\s+',' '
        $plain = $plain.Trim()
        $hasMenuLabel = $plain -like ('*' + $c.Label + '*')
        $hasMust = $plain -like ('*' + $c.Must + '*')
        $snippet = $plain
        if ($snippet.Length -gt 450) { $snippet = $snippet.Substring(0,450) }
        Write-Output "--- $($c.Label) PATH=$($c.Path) STATUS=$($r.StatusCode) FINAL=$($r.BaseResponse.ResponseUri.AbsoluteUri) HAS_LABEL=$hasMenuLabel HAS_CONTENT=$hasMust LEN=$($r.Content.Length)"
        Write-Output $snippet
    } catch {
        Write-Output "--- $($c.Label) PATH=$($c.Path) ERROR=$($_.Exception.Message)"
    }
}
