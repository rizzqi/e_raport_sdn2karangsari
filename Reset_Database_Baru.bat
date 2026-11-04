@echo off
title Reset dan Siapkan Database E-Raport
echo ========================================================
echo    RESET & SIAPKAN DATABASE E-RAPORT SDN 2 KARANGSARI
echo ========================================================
echo.
cd /d "%~dp0"

echo Pilih mode database yang ingin disiapkan:
echo [1] Impor data lengkap riil dari backup_database_e_raport_lengkap.sql (Disarankan)
echo [2] Migrasi bersih dengan data seeder demo bawaan
echo.
set /p pilihan="Masukkan pilihan (1/2): "

if "%pilihan%"=="2" (
    echo.
    echo Menjalankan migrasi dan seeder awal...
    php artisan migrate:fresh --seed
) else (
    echo.
    echo Mengimpor data lengkap ke SQLite...
    php artisan db:import-backup --fresh
)

echo.
echo ========================================================
echo [SELESAI] Database berhasil disiapkan!
echo Akun login demo (admin, wali kelas, kepsek) siap digunakan.
echo ========================================================
pause
