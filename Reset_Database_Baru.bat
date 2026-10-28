@echo off
title Reset dan Siapkan Database E-Raport
echo ========================================================
echo    RESET & SEED DATABASE E-RAPORT SDN 2 KARANGSARI
echo ========================================================
echo.
echo Pastikan MySQL di XAMPP sudah HIJAU (START).
echo.
pause
cd /d C:\xampp\htdocs\e_raport_sdn2karangsari
php artisan migrate:fresh --seed
echo.
echo ========================================================
echo [SELESAI] Database berhasil dibuat ulang dan diisi data awal!
echo Akun login demo (admin, wali kelas, kepsek) siap digunakan.
echo ========================================================
pause
