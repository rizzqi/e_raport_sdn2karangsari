@echo off
title Perbaiki MySQL XAMPP
echo ========================================================
echo    PERBAIKAN MYSQL XAMPP SHUTDOWN UNEXPECTEDLY
echo ========================================================
echo.
echo Pastikan MySQL di XAMPP Control Panel dalam keadaan STOP!
echo.
pause

echo 1. Mengamankan ib_logfile lama dan menghapus temporary file...
if exist "C:\xampp\mysql\data\ibtmp1" del /f /q "C:\xampp\mysql\data\ibtmp1"
if exist "C:\xampp\mysql\data\ib_logfile0" del /f /q "C:\xampp\mysql\data\ib_logfile0"

echo 2. Menyalin ibdata1 dan ib_logfile0 yang bersih dari backup XAMPP...
copy /y "C:\xampp\mysql\backup\ibdata1" "C:\xampp\mysql\data\ibdata1"
copy /y "C:\xampp\mysql\backup\ib_logfile0" "C:\xampp\mysql\data\ib_logfile0"

echo.
echo ========================================================
echo [SELESAI] File sistem InnoDB MySQL telah di-reset bersih!
echo.
echo Langkah selanjutnya:
echo 1. Buka XAMPP Control Panel, klik START pada MySQL (akan langsung hijau).
echo 2. Jalankan file Jalankan_eRaport.bat atau php artisan migrate:fresh --seed.
echo ========================================================
pause
