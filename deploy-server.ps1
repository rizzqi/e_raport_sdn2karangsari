# ==============================================================================
# E-RAPORT SDN 2 KARANGSARI - SERVER DEPLOY TOOLKIT
# Server Auto-Pull dari GitHub via Tailscale SSH
# ==============================================================================
$ErrorActionPreference = "Continue"

$configFile = Join-Path $PSScriptRoot ".server_ip"
$serverIp = "100.99.65.48"

if (Test-Path $configFile) {
    $savedIp = (Get-Content $configFile -Raw).Trim()
    if (-not [string]::IsNullOrWhiteSpace($savedIp)) {
        $serverIp = $savedIp
    }
} else {
    Set-Content -Path $configFile -Value $serverIp
}

# Deteksi branch git aktif
function Get-ActiveBranch {
    try {
        $detected = (git branch --show-current 2>$null)
        if (-not [string]::IsNullOrWhiteSpace($detected)) {
            return $detected.Trim()
        }
    } catch {}
    return "main"
}

function Show-Header {
    param([string]$targetIp, [string]$branch)
    Clear-Host
    $tl = [char]0x2554
    $tr = [char]0x2557
    $bl = [char]0x255A
    $br = [char]0x255D
    $v  = [char]0x2551
    $h  = [string]([char]0x2550) * 68

    Write-Host ""
    Write-Host "  $tl$h$tr" -ForegroundColor Cyan
    Write-Host "  $v       E-RAPORT SDN 2 KARANGSARI - SERVER DEPLOY TOOLKIT            $v" -ForegroundColor Cyan
    Write-Host "  $v                 Server Auto-Pull dari GitHub                       $v" -ForegroundColor Cyan
    Write-Host "  $bl$h$br" -ForegroundColor Cyan
    Write-Host "   Target Server: root@$targetIp  |  Branch: $branch" -ForegroundColor DarkGray
    Write-Host ""
}

while ($true) {
    $currentBranch = Get-ActiveBranch
    Show-Header -targetIp $serverIp -branch $currentBranch

    Write-Host "PILIHAN DEPLOY:" -ForegroundColor White
    Write-Host "  [1]  Server Pull dari GitHub (Otomatis Tarik Update & Deploy ke Server)" -ForegroundColor White
    Write-Host "  [2]  Cek Status Server & Health Check" -ForegroundColor White
    Write-Host "  [0]  Keluar" -ForegroundColor White
    Write-Host ""

    $pilihan = Read-Host "Pilih menu [1/2/0, Enter untuk '1']"
    if ([string]::IsNullOrWhiteSpace($pilihan)) {
        $pilihan = "1"
    }
    $pilihan = $pilihan.Trim()

    switch ($pilihan) {
        "0" {
            Write-Host ""
            Write-Host "[INFO] Deployment Toolkit ditutup. Sampai jumpa!" -ForegroundColor Yellow
            exit 0
        }

        "1" {
            # -------------------------------------------------------------
            # [1] Server Pull dari GitHub
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [1] SERVER PULL DARI GITHUB ===" -ForegroundColor Cyan
            Write-Host "Target Server : root@$serverIp"
            Write-Host "Branch default: $currentBranch"
            $targetBranch = Read-Host "Ketik branch target [Enter untuk '$currentBranch']"
            if ([string]::IsNullOrWhiteSpace($targetBranch)) {
                $targetBranch = $currentBranch
            }

            Write-Host ""
            Write-Host "Menghubungi Server via SSH ($serverIp)..." -ForegroundColor Yellow
            if ($serverIp.StartsWith("100.")) {
                $tsProcess = Get-Process -Name "*tailscale*" -ErrorAction SilentlyContinue
                if (-not $tsProcess) {
                    Write-Host "  [CATATAN] Pastikan aplikasi Tailscale di Windows sudah aktif (Connected)." -ForegroundColor DarkGray
                }
            }
            Write-Host "Memerintahkan server untuk menarik commit terbaru dari GitHub (branch: $targetBranch)..." -ForegroundColor DarkGray
            Write-Host ""

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8 "root@$serverIp" "cd /var/www/e_raport_sdn2karangsari && bash deploy.sh $targetBranch"

            if ($LASTEXITCODE -eq 0) {
                Write-Host ""
                Write-Host "  [SUKSES] Server berhasil menarik update dari GitHub dan aplikasi telah diperbarui!" -ForegroundColor Green
            } else {
                Write-Host ""
                Write-Host "  [GAGAL] Server mengalami kendala saat menarik update dari GitHub." -ForegroundColor Red
                if ($serverIp.StartsWith("100.")) {
                    Write-Host "  Pastikan koneksi Tailscale di perangkat Anda dan server aktif." -ForegroundColor Yellow
                }
            }
            Write-Host ""
            Read-Host "Tekan Enter untuk kembali ke menu..."
        }

        "2" {
            # -------------------------------------------------------------
            # [2] Cek Status Server & Health Check
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [2] CEK STATUS SERVER & HEALTH CHECK ===" -ForegroundColor Cyan
            Write-Host "Menghubungi target root@$serverIp..." -ForegroundColor Yellow
            Write-Host ""

            $healthCheckScript = @'
echo "--------------------------------------------------------"
echo "  INFORMASI STATUS SERVER E-RAPORT SDN 2 KARANGSARI"
echo "--------------------------------------------------------"
printf "%-20s : %s\n" "Host Name" "$(hostname)"
printf "%-20s : %s\n" "OS System" "$(uname -srm)"
printf "%-20s : %s\n" "Server Uptime" "$(uptime -p 2>/dev/null || uptime)"
echo "--------------------------------------------------------"
echo "STATUS LAYANAN UTAMA:"

check_service() {
    local name=$1
    local svc=$2
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
        printf "  [OK] %-15s : AKTIF (Running)\n" "$name"
    else
        printf "  [--] %-15s : MATI / TIDAK AKTIF\n" "$name"
    fi
}

check_service "Web Server Nginx" "nginx"
(systemctl is-active --quiet "php8.4-fpm" 2>/dev/null && check_service "PHP 8.4-FPM" "php8.4-fpm") || check_service "PHP 8.3-FPM" "php8.3-fpm"
(systemctl is-active --quiet "mariadb" 2>/dev/null && check_service "Database MariaDB" "mariadb") || check_service "Database MySQL" "mysql"
check_service "Tailscale VPN" "tailscaled"

echo "--------------------------------------------------------"
echo "PENGGUNAAN SUMBER DAYA:"
printf "  Disk (/)   : %s\n" "$(df -h / | awk 'NR==2 {print $3 " / " $2 " (Terpakai " $5 ")"}')"
printf "  RAM/Memori : %s\n" "$(free -m | awk 'NR==2 {printf "%s MB / %s MB (%.1f%%)", $3, $2, $3*100/$2}')"

echo "--------------------------------------------------------"
echo "STATUS APLIKASI WEB:"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/login 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ]; then
    printf "  Aplikasi Web (Local Port) : [OK] Respon HTTP 200 (Login Page Ready)\n"
elif [ "$HTTP_CODE" = "302" ]; then
    printf "  Aplikasi Web (Local Port) : [OK] Respon HTTP 302 (Redirect)\n"
else
    printf "  Aplikasi Web (Local Port) : [PERINGATAN] Respon HTTP %s\n" "$HTTP_CODE"
fi
echo "--------------------------------------------------------"
'@

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=5 "root@$serverIp" $healthCheckScript

            if ($LASTEXITCODE -ne 0) {
                Write-Host ""
                Write-Host "  [GAGAL] Tidak dapat terhubung ke server $serverIp." -ForegroundColor Red
                Write-Host "  Pastikan koneksi Tailscale di perangkat Anda dan server aktif." -ForegroundColor Yellow
            }
            Write-Host ""
            Read-Host "Tekan Enter untuk kembali ke menu..."
        }

        default {
            Write-Host ""
            Write-Host "[PERINGATAN] Pilihan '$pilihan' tidak valid. Silakan pilih 1, 2, atau 0." -ForegroundColor Yellow
            Start-Sleep -Seconds 1
        }
    }
}
