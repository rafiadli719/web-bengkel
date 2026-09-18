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
Write-Output "CABANG=$cab"
$body = @{ txtnama='admin'; txtpass='admin'; cbocabang=$cab }
$resp = Invoke-WebRequest -Uri ($base + 'cek_login.php') -Method Post -Body $body -WebSession $s -MaximumRedirection 10 -UseBasicParsing -ErrorAction Stop
Write-Output "LOGIN_STATUS=$($resp.StatusCode) FINAL=$($resp.BaseResponse.ResponseUri.AbsoluteUri)"
$urls = @('app/kas_masuk.php','app/kas_keluar.php','app/_keuangan/kasir/index_kasir.php','app/_keuangan/kasir/pemasukan.php','app/_keuangan/kasir/pengeluaran.php')
foreach ($path in $urls) {
    try {
        $r = Invoke-WebRequest -Uri ($base + $path) -WebSession $s -UseBasicParsing -TimeoutSec 30
        $plain = $r.Content -replace '<script[\s\S]*?</script>','' -replace '<style[\s\S]*?</style>','' -replace '<[^>]+>',' ' -replace '\s+',' '
        $plain = $plain.Trim()
        $relevant = $plain
        if ($path -eq 'app/kas_masuk.php') { $relevant = [regex]::Match($plain, 'Kas Masuk[\s\S]*').Value }
        if ($path -eq 'app/kas_keluar.php') { $relevant = [regex]::Match($plain, 'Pengeluaran Kas[\s\S]*').Value }
        if ($relevant.Length -gt 1800) { $relevant = $relevant.Substring(0,1800) }
        Write-Output "--- $path STATUS=$($r.StatusCode) FINAL=$($r.BaseResponse.ResponseUri.AbsoluteUri) LENGTH=$($r.Content.Length)"
        Write-Output $relevant
        if ($path -eq 'app/_keuangan/kasir/index_kasir.php') {
            Write-Output '--- LINKS KASIR ---'
            [regex]::Matches($r.Content, 'href=["'']([^"'']+)["'']') | ForEach-Object { $_.Groups[1].Value } | Where-Object { $_ -match 'pemasukan|pengeluaran|kode_transaksi' } | Select-Object -Unique | ForEach-Object { Write-Output $_ }
        }
    } catch { Write-Output "--- $path ERROR=$($_.Exception.Message)" }
}
