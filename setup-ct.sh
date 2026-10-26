#!/bin/bash
set -e

echo "=========================================================="
echo "  MEMULAI INSTALASI E-RAPORT SDN 2 KARANGSARI PADA CT    "
echo "=========================================================="

# 1. Konfigurasi Database MariaDB
echo "[1/7] Menyiapkan database MariaDB 'db_eraport'..."
mariadb -e "CREATE DATABASE IF NOT EXISTS db_eraport CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mariadb -e "CREATE USER IF NOT EXISTS 'eraport'@'localhost' IDENTIFIED BY 'eraport123';"
mariadb -e "GRANT ALL PRIVILEGES ON db_eraport.* TO 'eraport'@'localhost';"
mariadb -e "FLUSH PRIVILEGES;"

# 2. Clone atau Pull Repository
echo "[2/7] Mengunduh project dari GitHub..."
if [ -d "/var/www/e_raport_sdn2karangsari" ]; then
    echo "Direktori sudah ada, melakukan update (git pull)..."
    cd /var/www/e_raport_sdn2karangsari
    git pull origin main
else
    cd /var/www
    git clone https://github.com/rizzqi/e_raport_sdn2karangsari.git
    cd /var/www/e_raport_sdn2karangsari
fi

# 3. Konfigurasi file .env
echo "[3/7] Mengatur file konfigurasi .env..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

sed -i 's/DB_DATABASE=.*/DB_DATABASE=db_eraport/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=eraport/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=eraport123/' .env
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# 4. Install Dependensi PHP Composer
echo "[4/7] Menginstal dependensi Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# Generate APP_KEY jika belum ada (setelah vendor terpasang)
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# 5. Build Frontend Assets
echo "[5/7] Mengompilasi asset frontend (Vite)..."
if command -v npm &> /dev/null; then
    npm install --no-audit --prefer-offline
    npm run build
fi

# 6. Database Migration, Seeder & Storage Link
echo "[6/7] Menjalankan migrasi database & seeder data awal..."
php artisan migrate --seed --force
php artisan storage:link --quiet || true
php artisan optimize:clear
php artisan optimize

# Hak akses folder
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/e_raport_sdn2karangsari

# 7. Konfigurasi Nginx
echo "[7/7] Mengonfigurasi web server Nginx..."
PHP_SOCKET="/var/run/php/php8.3-fpm.sock"
if [ ! -S "$PHP_SOCKET" ]; then
    # Cari socket PHP-FPM aktif lainnya jika bukan 8.3
    FOUND_SOCK=$(find /var/run/php/ -name "php*-fpm.sock" | head -n 1)
    if [ -n "$FOUND_SOCK" ]; then
        PHP_SOCKET="$FOUND_SOCK"
    fi
fi

cat <<EOF > /etc/nginx/sites-available/eraport
server {
    listen 80;
    server_name _;

    root /var/www/e_raport_sdn2karangsari/public;
    index index.php index.html;

    client_max_body_size 25M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_SOCKET;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/eraport /etc/nginx/sites-enabled/eraport
rm -f /etc/nginx/sites-enabled/default

# Test dan restart Nginx
nginx -t
systemctl restart nginx

echo "=========================================================="
echo "  SELESAI! E-Raport SDN 2 Karangsari Berhasil Dideploy!   "
echo "=========================================================="
