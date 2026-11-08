# Aturan & Standar Deployment (Server Deploy Toolkit)

## Gambaran Umum
Proyek **E-Raport SDN 2 Karangsari** menggunakan alur kerja deployment server terpadu yang berfokus pada **Server Auto-Pull dari GitHub** melalui terminal dengan perintah:
```bash
npm run deploy
# atau
.\deploy-server.ps1
```

Setiap agen pengembang (AI) dan pengembang tim harus mematuhi alur kerja yang didefinisikan dalam aturan ini.

---

## Struktur Menu Deploy Toolkit
Antarmuka terminal menyajikan pilihan menu ringkas:

1. **[1] Server Pull dari GitHub (Otomatis Tarik Update & Deploy ke Server)**
   * Menghubungi server via SSH Tailscale (`root@<IP_SERVER>`).
   * Menjalankan skrip `deploy.sh <branch>` langsung di server (`git fetch`, `git reset --hard origin/<branch>`, `composer install --no-dev`, `php artisan migrate --force`, `npm run build`, `php artisan storage:link`, `php artisan optimize`, dan perbaikan hak akses `chmod`/`chown`).
   * Tidak melakukan `git add` atau `git push` dari komputer lokal, sehingga proses deploy dijamin cepat, bersih, dan bebas konflik branch lokal.

2. **[2] Cek Status Server & Health Check**
   * Memeriksa keterhubungan jaringan ke IP Tailscale server.
   * Melakukan inspeksi status layanan sistem:
     * Web Server (Nginx)
     * PHP-FPM Service (`php8.4-fpm` / `php8.3-fpm`)
     * Database Service (`mariadb` / `mysql`)
     * Tailscale VPN Daemon
   * Memeriksa sisa kapasitas Disk (`df -h /`) dan penggunaan Memori RAM (`free -m`).
   * Menguji kesiapan HTTP response kode lokal server (`/login`).

3. **[0] Keluar**
   * Mengakhiri sesi deployment toolkit.

---

## Konfigurasi Target Server
* Target IP server disimpan secara otomatis di berkas lokal `.server_ip` (nilai default: `100.99.65.48`).
* Berkas ini dibaca setiap kali skrip dijalankan dan diabaikan oleh Git agar tidak terekspos.
* Jika IP server Tailscale berubah di kemudian hari, cukup ubah nilai di dalam berkas `.server_ip`.

---

## Prinsip & Batasan Keamanan
1. **Pemisahan Lingkungan**:
   * Lingkungan lokal pengembang dapat menggunakan SQLite atau MySQL XAMPP.
   * Lingkungan server produksi menggunakan **MariaDB / MySQL**.
   * Berkas konfigurasi `.env` dan `.server_ip` dilarang keras di-commit ke repositori GitHub.
2. **Kompabilitas Branch**:
   * Skrip `deploy.sh` di server menerima argumen nama branch (`$1`, default `main`) agar peralihan cabang di server berjalan otomatis dan aman.
3. **Penyimpanan Kredensial**:
   * Seluruh akses remote server diarahkan melalui jaringan privat **Tailscale SSH**.
