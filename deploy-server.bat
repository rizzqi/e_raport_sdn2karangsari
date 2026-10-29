@echo off
setlocal enabledelayedexpansion

echo ==========================================================
echo   DEPLOY E-RAPORT SDN 2 KARANGSARI KE SERVER (VIA TAILSCALE)
echo ==========================================================

set CONFIG_FILE=%~dp0.server_ip
set SERVER_USER=root

if exist "%CONFIG_FILE%" (
    set /p SERVER_IP=<"%CONFIG_FILE%"
)

if "%SERVER_IP%"=="" (
    echo.
    echo Masukkan IP Tailscale Server Anda (contoh: 100.80.25.10):
    set /p SERVER_IP="IP Server: "
    if not "!SERVER_IP!"=="" (
        echo !SERVER_IP!>"%CONFIG_FILE%"
        echo [INFO] IP Server berhasil disimpan ke .server_ip
    ) else (
        echo [ERROR] IP Server tidak boleh kosong.
        exit /b 1
    )
)

echo.
echo [1/3] Memastikan commit lokal sudah ter-push ke GitHub...
git push origin main
if errorlevel 1 (
    echo [WARNING] Git push mengalami kendala atau tidak ada commit baru. Melanjutkan deploy...
)

echo.
echo [2/3] Menghubungi Server via Tailscale SSH (IP: %SERVER_IP%)...
echo Menjalankan deploy.sh di /var/www/e_raport_sdn2karangsari...
echo.

ssh -o StrictHostKeyChecking=accept-new %SERVER_USER%@%SERVER_IP% "cd /var/www/e_raport_sdn2karangsari && bash deploy.sh"

if errorlevel 1 (
    echo.
    echo ==========================================================
    echo   [GAGAL] Tidak dapat terhubung ke server.
    echo   Pastikan Tailscale di laptop dan server sudah aktif!
    echo ==========================================================
    exit /b 1
)

echo.
echo ==========================================================
echo   [SUKSES] Aplikasi di server telah berhasil di-update!
echo ==========================================================
