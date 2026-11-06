# Aturan & Standar Deployment (Interactive Deploy Toolkit)

## Gambaran Umum
Proyek **E-Raport SDN 2 Karangsari** menggunakan alur kerja deployment interaktif terpadu yang dapat dipanggil melalui terminal dengan perintah:
```bash
npm run deploy
# atau
.\deploy-server.ps1
```

Setiap agen pengembang (AI) dan pengembang tim harus mematuhi struktur dan alur kerja yang didefinisikan dalam aturan ini.

---

## Struktur Menu Deploy Toolkit
Antarmuka terminal menyajikan pilihan menu berurutan:

1. **[1] Deploy ke GitHub (git add, commit & push)**
   * Menampilkan ringkasan status berkas (`git status --short`).
   * Meminta pesan commit yang deskriptif dari pengguna.
   * Melakukan `git add .`, `git commit`, dan `git push origin <branch>`.
   * Digunakan untuk menyimpan pekerjaan ke repositori remote tanpa memperbarui server produksi.

2. **[2] Deploy Cepat ke Server (Fast Sync via SSH)**
   * Menghubungi server via SSH Tailscale (`root@<IP_SERVER>`).
   * Menjalankan pembaruan cepat: `git pull`, `php artisan optimize:clear`, `php artisan optimize`.
   * Digunakan saat pengujian langsung atau perubahan kecil (styling/template) tanpa memerlukan migrasi atau kompilasi penuh.

3. **[3] Full Deploy: GitHub + Server Auto-Pull & Update (Rilis Resmi)**
   * Workflow rilis produksi lengkap:
     1. Menyimpan dan melakukan push seluruh commit ke GitHub.
     2. Menghubungi server produksi via SSH.
     3. Menjalankan skrip `deploy.sh <branch>` di server (`git pull`, `composer install --no-dev`, `php artisan migrate --force`, `npm run build`, `php artisan storage:link`, `php artisan optimize`, dan perbaikan hak akses `chmod`/`chown`).

4. **[4] Cek Status Server & Health Check**
   * Memeriksa keterhubungan jaringan ke IP Tailscale server.
   * Melakukan inspeksi status layanan sistem:
     * Web Server (Nginx)
     * PHP-FPM Service (`php8.4-fpm` / `php8.3-fpm`)
     * Database Service (`mariadb` / `mysql`)
     * Tailscale VPN Daemon
   * Memeriksa sisa kapasitas Disk (`df -h /`) dan penggunaan Memori RAM (`free -m`).
   * Menguji kesiapan HTTP response kode lokal server (`/login`).

5. **[5] Jalankan Local Dev Server**
   * Memastikan berkas basis data lokal `database/database.sqlite` tersedia.
   * Membuka peramban ke `http://127.0.0.1:8000`.
   * Menjalankan `php artisan serve --host=127.0.0.1 --port=8000`.

6. **[0] Keluar**
   * Mengakhiri sesi interaktif toolkit.

---

## Konfigurasi Target Server
* Target IP server disimpan secara otomatis di berkas lokal `.server_ip` (nilai default: `100.99.65.48`).
* Berkas ini dibaca setiap kali skrip dijalankan dan diabaikan oleh Git agar tidak terekspos.
* Jika IP server Tailscale berubah di kemudian hari, cukup ubah nilai di dalam berkas `.server_ip`.

---

## Prinsip & Batasan Keamanan
1. **Pemisahan Lingkungan**:
   * Lingkungan lokal menggunakan **SQLite** (`database/database.sqlite`).
   * Lingkungan server produksi menggunakan **MariaDB / MySQL** (`db_eraport`).
   * Berkas konfigurasi `.env` dan `.server_ip` dilarang keras di-commit ke repositori GitHub.
2. **Kompabilitas Branch**:
   * Skrip `deploy.sh` di server harus selalu menerima argumen nama branch (`$1`, default `main`) agar peralihan cabang di server berjalan otomatis dan aman.
3. **Penyimpanan Kredensial**:
   * Seluruh akses remote server diarahkan melalui jaringan privat **Tailscale SSH**. Jangan membuka port SSH langsung ke IP publik tanpa proteksi VPN atau firewall.

