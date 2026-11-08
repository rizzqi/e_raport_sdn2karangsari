@echo off
title Server E-Raport SDN 2 Karangsari
echo ========================================================
echo    MENJALANKAN E-RAPOR SDN 2 KARANGSARI (LOCAL)
echo ========================================================
echo.
cd /d "%~dp0"

:: Cek driver database dari .env
findstr /i "^DB_CONNECTION=sqlite" .env >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    if not exist "database\database.sqlite" (
        echo [INFO] Menyiapkan file database SQLite baru...
        type nul > "database\database.sqlite"
        php artisan migrate --force
    )
) else (
    tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
    if "%ERRORLEVEL%"=="0" (
        echo [OK] MySQL Service terdeteksi sudah aktif.
    ) else (
        if exist "C:\xampp\mysql\bin\mysqld.exe" (
            echo [INFO] Menyalakan MySQL XAMPP...
            start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
            timeout /t 2 >nul
        ) else (
            echo [WARNING] MySQL belum aktif. Pastikan service MySQL di XAMPP sudah di-START.
        )
    )
)

echo [INFO] Membuka web browser ke http://127.0.0.1:8000 ...
echo [INFO] Jendela ini jangan ditutup selama menggunakan aplikasi.
echo ========================================================
echo.

start http://127.0.0.1:8000

:: Jalankan server lokal Laravel
php artisan serve --host=127.0.0.1 --port=8000

pause
