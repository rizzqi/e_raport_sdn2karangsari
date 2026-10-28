@echo off
title Perbaikan Database XAMPP - E-Raport
echo ========================================================
echo    PERBAIKAN IBDATA1 XAMPP MYSQL (E-RAPORT)
echo ========================================================
echo.
echo PENTING:
echo 1. Buka XAMPP Control Panel dan klik STOP pada MySQL!
echo 2. Jika sudah di-STOP, tekan sembarang tombol di sini...
echo ========================================================
pause

copy /y "C:\xampp\mysql\ibdata1" "C:\xampp\mysql\data\ibdata1"
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================================
    echo [BERHASIL] File ibdata1 asli (76 MB) berhasil disalin ke folder data!
    echo Silakan buka XAMPP Control Panel dan klik START pada MySQL.
    echo Kemudian refresh browser http://localhost:8000
    echo ========================================================
) else (
    echo.
    echo [GAGAL] Gagal menyalin file. Pastikan MySQL di XAMPP benar-benar sudah di-STOP.
)

pause
