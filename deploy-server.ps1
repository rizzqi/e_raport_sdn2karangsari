# Deploy Script via Tailscale for Windows PowerShell
$ErrorActionPreference = "Stop"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  DEPLOY E-RAPORT KE SERVER (VIA TAILSCALE)" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$configFile = Join-Path $PSScriptRoot ".server_ip"
$serverIp = ""

if (Test-Path $configFile) {
    $serverIp = (Get-Content $configFile -Raw).Trim()
}

if ([string]::IsNullOrWhiteSpace($serverIp)) {
    Write-Host ""
    $serverIp = Read-Host "Masukkan IP Tailscale Server Anda (contoh: 100.80.25.10)"
    if ([string]::IsNullOrWhiteSpace($serverIp)) {
        Write-Error "IP Server tidak boleh kosong."
        exit 1
    }
    Set-Content -Path $configFile -Value $serverIp.Trim()
    Write-Host "[INFO] IP Server berhasil disimpan ke .server_ip" -ForegroundColor Green
}

Write-Host ""
Write-Host "[1/3] Memastikan commit lokal sudah ter-push ke GitHub..." -ForegroundColor Yellow
try {
    git push origin main
} catch {
    Write-Warning "Peringatan saat git push. Melanjutkan remote deployment..."
}

Write-Host ""
Write-Host "[2/3] Menghubungi server via SSH ($serverIp)..." -ForegroundColor Yellow
ssh -o StrictHostKeyChecking=accept-new "root@$serverIp" "cd /var/www/e_raport_sdn2karangsari && bash deploy.sh"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "==========================================================" -ForegroundColor Green
    Write-Host "  [SUKSES] Aplikasi di server telah berhasil di-update!" -ForegroundColor Green
    Write-Host "==========================================================" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "==========================================================" -ForegroundColor Red
    Write-Host "  [GAGAL] Deployment ke server gagal. Pastikan Tailscale aktif." -ForegroundColor Red
    Write-Host "==========================================================" -ForegroundColor Red
    exit 1
}
