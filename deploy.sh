#!/bin/bash
set -e

echo "=================================================="
echo "  Deploy E-Raport SDN 2 Karangsari"
echo "=================================================="

# Tarik commit terbaru dari GitHub
echo "[1/6] Menarik commit terbaru dari GitHub..."
git pull origin main

# Update dependensi Composer
echo "[2/6] Menginstal dependensi Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# Jalankan migrasi database
echo "[3/6] Menjalankan migrasi database..."
php artisan migrate --force

# Kompilasi frontend asset jika npm tersedia
if command -v npm &> /dev/null; then
    echo "[4/6] Mengompilasi asset frontend (Vite)..."
    npm install --no-audit --prefer-offline
    npm run build
else
    echo "[4/6] NPM tidak ditemukan, melewati build asset..."
fi

# Symbolic link storage
echo "[5/6] Memeriksa storage link..."
php artisan storage:link --quiet || true

# Optimasi cache Laravel
echo "[6/6] Mengoptimasi cache Laravel..."
php artisan optimize:clear
php artisan optimize

# Penyesuaian hak akses folder
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "=================================================="
echo "  Deployment Berhasil!"
echo "=================================================="
