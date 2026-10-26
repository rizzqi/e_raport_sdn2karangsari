#!/bin/bash
set -e

export COMPOSER_ALLOW_SUPERUSER=1

echo "=========================================================="
echo "  MEMULAI INSTALASI E-RAPORT SDN 2 KARANGSARI PADA CT    "
echo "=========================================================="

# Pastikan PHP 8.4 dan Composer resmi terpasang
echo "[0/7] Memastikan PHP 8.4 dan Composer resmi terpasang..."
apt-get update -y
apt-get install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-gd php8.4-zip php8.4-bcmath php8.4-intl
update-alternatives --set php /usr/bin/php8.4 2>/dev/null || true
systemctl enable --now php8.4-fpm

# Hapus composer bawaan apt (yang bentrok) dan pasang Composer standalone resmi
apt-get remove -y composer 2>/dev/null || true
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
chmod +x /usr/local/bin/composer

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
/usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction

# Generate APP_KEY jika belum ada (setelah vendor terpasang)
if ! grep -q "^APP_KEY=base64:" .env; then
    php artisan key:generate --force
fi

# 5. Build Frontend Assets
echo "[5/7] Mengompilasi asset frontend (Vite)..."
NODE_MAJOR=0
if command -v node &> /dev/null; then
    NODE_MAJOR=$(node -v | cut -d'.' -f1 | tr -d 'v')
fi

if [ "$NODE_MAJOR" -lt 20 ]; then
    echo "Node.js saat ini versi $(node -v 2>/dev/null || echo 'none'), memperbarui ke Node.js 20 LTS..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs
fi

npm install --no-audit --prefer-offline
npm run build

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
if [ -S "/var/run/php/php8.4-fpm.sock" ]; then
    PHP_SOCKET="/var/run/php/php8.4-fpm.sock"
elif [ -S "/var/run/php/php8.3-fpm.sock" ]; then
    PHP_SOCKET="/var/run/php/php8.3-fpm.sock"
else
    PHP_SOCKET=$(find /var/run/php/ -name "php*-fpm.sock" | head -n 1)
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
