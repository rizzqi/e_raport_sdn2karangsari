@echo off
title Server E-Raport SDN 2 Karangsari
echo ========================================================
echo    MENJALANKAN E-RAPOR SDN 2 KARANGSARI (LOCAL)
echo ========================================================
echo.

:: 1. Cek apakah MySQL XAMPP sudah berjalan
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL Service terdeteksi sudah aktif.
) else (
    echo [INFO] Menyalakan MySQL XAMPP...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 2 >nul
)

echo.
echo [INFO] Membuka web browser ke http://127.0.0.1:8000 ...
echo [INFO] Jendela ini jangan ditutup selama menggunakan aplikasi.
echo ========================================================
echo.

cd /d C:\xampp\htdocs\e_raport_sdn2karangsari
start http://127.0.0.1:8000

:: Jalankan server lokal
if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
) else (
    php artisan serve --host=127.0.0.1 --port=8000
)

pause
