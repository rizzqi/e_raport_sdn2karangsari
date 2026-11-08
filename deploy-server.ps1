# ==============================================================================
# E-RAPORT SDN 2 KARANGSARI - INTERACTIVE DEPLOY TOOLKIT
# Multi-Client & Production Deployment Workflow
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
    Write-Host "  $v       E-RAPORT SDN 2 KARANGSARI - INTERACTIVE DEPLOY TOOLKIT       $v" -ForegroundColor Cyan
    Write-Host "  $v              Sistem Pengolahan Nilai & Raport Digital              $v" -ForegroundColor Cyan
    Write-Host "  $bl$h$br" -ForegroundColor Cyan
    Write-Host "   Server: root@$targetIp  |  Branch: $branch" -ForegroundColor DarkGray
    Write-Host ""
}

while ($true) {
    $currentBranch = Get-ActiveBranch
    Show-Header -targetIp $serverIp -branch $currentBranch

    Write-Host "PILIHAN DEPLOY & DEVELOPMENT:" -ForegroundColor White
    Write-Host "  [1]  Deploy ke GitHub (git add, commit & push)" -ForegroundColor White
    Write-Host "  [2]  Deploy Cepat ke Server (Fast Sync file / Git Pull langsung via SSH)" -ForegroundColor White
    Write-Host "  [3]  Full Deploy: GitHub + Server Auto-Pull & Update (Rilis Resmi)" -ForegroundColor White
    Write-Host "  [4]  Cek Status Server & Health Check" -ForegroundColor White
    Write-Host "  [5]  Jalankan Local Dev Server (php artisan serve)" -ForegroundColor White
    Write-Host "  [0]  Keluar" -ForegroundColor White
    Write-Host ""

    $pilihan = Read-Host "Pilih menu [0-5]"
    if ([string]::IsNullOrWhiteSpace($pilihan)) {
        continue
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
            # [1] Deploy ke GitHub (git add, commit & push)
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [1] DEPLOY KE GITHUB ===" -ForegroundColor Cyan
            Write-Host "Branch aktif : $currentBranch"
            $targetBranch = Read-Host "Ketik branch target [Enter untuk '$currentBranch']"
            if ([string]::IsNullOrWhiteSpace($targetBranch)) {
                $targetBranch = $currentBranch
            }

            git status --short
            Write-Host ""
            $nowStr = Get-Date -Format 'yyyy-MM-dd HH:mm'
            $commitMsg = Read-Host "Masukkan pesan commit [Enter untuk 'update: pembaruan aplikasi']"
            if ([string]::IsNullOrWhiteSpace($commitMsg)) {
                $commitMsg = "update: pembaruan aplikasi ($nowStr)"
            }

            Write-Host ""
            Write-Host "[1/2] Menyiapkan perubahan (git add & commit)..." -ForegroundColor Yellow
            git add .
            git commit -m "$commitMsg"

            Write-Host "[2/2] Mendorong ke origin/$targetBranch..." -ForegroundColor Yellow
            git push origin $targetBranch

            if ($LASTEXITCODE -eq 0) {
                Write-Host ""
                Write-Host "  [SUKSES] Perubahan berhasil di-push ke GitHub (Branch: $targetBranch)!" -ForegroundColor Green
            } else {
                Write-Host ""
                Write-Host "  [GAGAL] Gagal mendorong ke GitHub." -ForegroundColor Red
            }
            Write-Host ""
            Read-Host "Tekan Enter untuk kembali ke menu..."
        }

        "2" {
            # -------------------------------------------------------------
            # [2] Deploy Cepat ke Server (Fast Sync / Quick Pull via SSH)
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [2] DEPLOY CEPAT KE SERVER (FAST SYNC VIA SSH) ===" -ForegroundColor Cyan
            Write-Host "Target Server : root@$serverIp"
            Write-Host "Branch default: $currentBranch"
            $targetBranch = Read-Host "Ketik branch target [Enter untuk '$currentBranch']"
            if ([string]::IsNullOrWhiteSpace($targetBranch)) {
                $targetBranch = $currentBranch
            }

            Write-Host ""
            Write-Host "Menghubungi server via SSH ($serverIp)..." -ForegroundColor Yellow
            $quickCmd = "cd /var/www/e_raport_sdn2karangsari && git fetch origin $targetBranch && (git checkout $targetBranch 2>/dev/null || git checkout -b $targetBranch origin/$targetBranch) && git pull origin $targetBranch && php artisan optimize:clear && php artisan optimize"

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8 "root@$serverIp" $quickCmd

            if ($LASTEXITCODE -eq 0) {
                Write-Host ""
                Write-Host "  [SUKSES] Fast Sync ke server berhasil (Branch: $targetBranch)!" -ForegroundColor Green
            } else {
                Write-Host ""
                Write-Host "  [GAGAL] Fast Sync gagal. Pastikan Tailscale aktif dan SSH terhubung." -ForegroundColor Red
            }
            Write-Host ""
            Read-Host "Tekan Enter untuk kembali ke menu..."
        }

        "3" {
            # -------------------------------------------------------------
            # [3] Full Deploy: GitHub + Server Auto-Pull & Update (Rilis Resmi)
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [3] FULL DEPLOY (GITHUB + SERVER AUTO-PULL & UPDATE) ===" -ForegroundColor Cyan
            Write-Host "Branch default: $currentBranch"
            $targetBranch = Read-Host "Ketik branch target [Enter untuk '$currentBranch']"
            if ([string]::IsNullOrWhiteSpace($targetBranch)) {
                $targetBranch = $currentBranch
            }

            # Periksa apakah ada uncommitted changes
            $statusCheck = (git status --porcelain 2>$null)
            if (-not [string]::IsNullOrWhiteSpace($statusCheck)) {
                Write-Host ""
                git status --short
                Write-Host ""
                $nowStr = Get-Date -Format 'yyyy-MM-dd HH:mm'
                $commitMsg = Read-Host "Ada perubahan lokal. Masukkan pesan commit [Enter untuk 'release: rilis e-raport']"
                if ([string]::IsNullOrWhiteSpace($commitMsg)) {
                    $commitMsg = "release: rilis pembaruan e-raport ($nowStr)"
                }
                git add .
                git commit -m "$commitMsg"
            }

            Write-Host ""
            Write-Host "[1/2] Mendorong branch '$targetBranch' ke GitHub..." -ForegroundColor Yellow
            git push origin $targetBranch
            if ($LASTEXITCODE -ne 0) {
                Write-Host ""
                Write-Host "  [PANDUAN GIT] Push ditolak (non-fast-forward / branch tertinggal):" -ForegroundColor Yellow
                Write-Host "  1. Ambil update dari GitHub: git pull origin $targetBranch" -ForegroundColor DarkGray
                Write-Host "  2. Jika fitur baru ada di branch 'yayaya', gabungkan terlebih dahulu:" -ForegroundColor DarkGray
                Write-Host "     git checkout $targetBranch && git merge yayaya" -ForegroundColor DarkGray
                Write-Host ""
                Write-Warning "Peringatan saat push ke GitHub. Melanjutkan deploy ke server..."
            }

            Write-Host ""
            Write-Host "[2/2] Menghubungi Server via SSH ($serverIp)..." -ForegroundColor Yellow
            if ($serverIp.StartsWith("100.")) {
                $tsProcess = Get-Process -Name "*tailscale*" -ErrorAction SilentlyContinue
                if (-not $tsProcess) {
                    Write-Host "  [PERINGATAN] Aplikasi Tailscale di Windows tampaknya belum berjalan." -ForegroundColor Yellow
                    Write-Host "  Pastikan Anda telah membuka dan menghubungkan (Connect) aplikasi Tailscale di laptop." -ForegroundColor Yellow
                }
            }
            Write-Host "Menjalankan deploy.sh lengkap di /var/www/e_raport_sdn2karangsari..." -ForegroundColor DarkGray
            Write-Host ""

            ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=8 "root@$serverIp" "cd /var/www/e_raport_sdn2karangsari && bash deploy.sh $targetBranch"

            if ($LASTEXITCODE -eq 0) {
                Write-Host ""
                Write-Host "  [SUKSES] Full Deploy berhasil! Aplikasi di server telah diperbarui sepenuhnya." -ForegroundColor Green
            } else {
                Write-Host ""
                Write-Host "  [GAGAL] Full Deploy ke server mengalami kendala." -ForegroundColor Red
                if ($serverIp.StartsWith("100.")) {
                    Write-Host "  Kemungkinan kendala jaringan Tailscale ($serverIp):" -ForegroundColor Yellow
                    Write-Host "  1. Buka aplikasi Tailscale di Windows dan pastikan status 'Connected'." -ForegroundColor DarkGray
                    Write-Host "  2. Pastikan server target sedang menyala dan tailscaled aktif." -ForegroundColor DarkGray
                    Write-Host "  3. Coba tes koneksi dengan: ping $serverIp" -ForegroundColor DarkGray
                }
            }
            Write-Host ""
            Read-Host "Tekan Enter untuk kembali ke menu..."
        }

        "4" {
            # -------------------------------------------------------------
            # [4] Cek Status Server & Health Check
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [4] CEK STATUS SERVER & HEALTH CHECK ===" -ForegroundColor Cyan
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

        "5" {
            # -------------------------------------------------------------
            # [5] Jalankan Local Dev Server (php artisan serve)
            # -------------------------------------------------------------
            Write-Host ""
            Write-Host "=== [5] MENJALANKAN LOCAL DEV SERVER ===" -ForegroundColor Cyan
            
            # Cek driver database dari .env
            $dbDriver = "mysql"
            $envPath = Join-Path $PSScriptRoot ".env"
            if (Test-Path $envPath) {
                $envMatch = Get-Content $envPath | Select-String -Pattern "^DB_CONNECTION=(.*)$"
                if ($envMatch) {
                    $dbDriver = $envMatch.Matches.Groups[1].Value.Trim()
                }
            }

            if ($dbDriver -eq "sqlite") {
                $sqlitePath = Join-Path $PSScriptRoot "database\database.sqlite"
                if (-not (Test-Path $sqlitePath)) {
                    Write-Host "[INFO] Menyiapkan database SQLite baru..." -ForegroundColor Yellow
                    New-Item -ItemType File -Path $sqlitePath -Force | Out-Null
                    php artisan migrate --force
                }
            } else {
                # Driver MySQL: Periksa apakah service MySQL XAMPP sudah aktif
                $mysqlProcess = Get-Process -Name mysqld -ErrorAction SilentlyContinue
                if (-not $mysqlProcess) {
                    if (Test-Path "C:\xampp\mysql\bin\mysqld.exe") {
                        Write-Host "[INFO] Menyalakan MySQL XAMPP..." -ForegroundColor Yellow
                        Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone" -WindowStyle Hidden
                        Start-Sleep -Seconds 2
                    } else {
                        Write-Host "[WARNING] MySQL belum aktif. Pastikan service MySQL di XAMPP Control Panel sudah di-START." -ForegroundColor Yellow
                    }
                } else {
                    Write-Host "[OK] MySQL Service terdeteksi aktif." -ForegroundColor Green
                }
            }

            Write-Host "[INFO] Membuka web browser ke http://127.0.0.1:8000 ..." -ForegroundColor Green
            Write-Host "[INFO] Tekan Ctrl+C di terminal ini untuk menghentikan server." -ForegroundColor Yellow
            Write-Host ""
            Start-Process "http://127.0.0.1:8000"
            php artisan serve --host=127.0.0.1 --port=8000
            exit 0
        }

        default {
            Write-Host ""
            Write-Host "[WARNING] Pilihan '$pilihan' tidak valid, silakan masukkan angka 0 s/d 5." -ForegroundColor Yellow
            Start-Sleep -Seconds 1
        }
    }
}
