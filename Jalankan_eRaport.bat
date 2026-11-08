@echo off
title Server E-Raport SDN 2 Karangsari
echo ========================================================
echo    MENJALANKAN E-RAPOR SDN 2 KARANGSARI (LOCAL)
echo ========================================================
echo.
cd /d "%~dp0"

:: Pastikan file database SQLite ada jika menggunakan driver SQLite
if not exist "database\database.sqlite" (
    echo [INFO] Menyiapkan file database SQLite baru...
    type nul > "database\database.sqlite"
    php artisan migrate --force
)

echo [INFO] Membuka web browser ke http://127.0.0.1:8000 ...
echo [INFO] Jendela ini jangan ditutup selama menggunakan aplikasi.
echo ========================================================
echo.

start http://127.0.0.1:8000

:: Jalankan server lokal Laravel
php artisan serve --host=127.0.0.1 --port=8000

pause
