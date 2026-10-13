# E-Raport SD Negeri 2 Karangsari (Laravel)

Sistem Informasi Pengolahan Nilai dan Pencetakan Raport Digital Kurikulum Merdeka untuk **SD Negeri 2 Karangsari**, Kabupaten Banyuwangi. Dibangun berbasis PRD (Product Requirements Document) dan ERD (Entity Relationship Diagram) resmi.

---

## 🌟 Fitur Utama (Berdasarkan PRD & ERD)

1. **Autentikasi & Hak Akses Berbasis Peran (F1)**
   - **Admin Sekolah**: Mengelola seluruh identitas sekolah, tahun ajaran, rombel/kelas, master siswa, mata pelajaran, aspek pembiasaan, dan akun pengguna.
   - **Wali Kelas**: Menginput nilai formatif & sumatif, aspek pembiasaan, kehadiran siswa di kelas binaannya; pratinjau dan unduh raport.
   - **Kepala Sekolah**: Memantau rekap nilai seluruh rombel dan status kelengkapan nilai tanpa mengubah nilai.

2. **Identitas & Kop Surat Resmi (F2)**
   - Terintegrasi format resmi Pemerintah Kabupaten Banyuwangi & Dinas Pendidikan.
   - Dilengkapi nama kepala sekolah, NIP, serta pengaturan logo & tanda tangan.

3. **Tahun Ajaran & Semester (F3)**
   - Pengaturan tahun ajaran (contoh: `2026/2027`) dan semester (Ganjil / Genap).
   - Aturan bisnis: Tepat satu tahun ajaran aktif dalam satu waktu.

4. **Rombongan Belajar / Kelas & Fase (F4 & F6)**
   - Pembagian kelas sesuai Fase Kurikulum Merdeka (Fase A, B, C).
   - Poros relasi `kelas_siswa` menjaga riwayat nilai siswa tetap utuh saat naik kelas.

5. **Manajemen Siswa & Impor Cepat (F5)**
   - CRUD data siswa (NIS unik, NISN, status aktif/lulus).
   - Fitur tempel langsung dari clipboard Excel / spreadsheet (Format: Nama, NIS, NISN).

6. **Mata Pelajaran & Pembiasaan Karakter (F7)**
   - Pengaturan urutan baris di lembar raport.
   - 5 Predikat Aspek Pembiasaan: *Sangat Baik*, *Baik*, *Cukup*, *Perlu Bimbingan*.

7. **Input Nilai Interaktif & Hitung Otomatis (F8 & F9)**
   - Nilai formatif dan sumatif (0 - 100).
   - Otomatis menghitung **Jumlah Nilai** dan **Rata-rata Nilai** secara realtime di layar dan database.
   - Mendukung format desimal koma standar Indonesia (contoh: `86,5`).
   - Pencatatan ketidakhadiran (Sakit, Izin, Tanpa Keterangan).
   - Tombol alur cepat *"Simpan & Lanjut ke Siswa Berikutnya"*.

8. **Output Raport Lengkap (F10, F11, F12)**
   - **Pratinjau Web (F10)**: Tampilan lembar raport di layar dengan tata letak A4 resmi.
   - **Unduh Dokumen Word .docx (F11)**: Menghasilkan file Word satu siswa atau seluruh siswa satu kelas dalam satu file menggunakan `phpoffice/phpword` (dapat diedit kembali di MS Word).
   - **Unduh PDF & Cetak A4 (F12)**: Menghasilkan PDF siap cetak menggunakan `barryvdh/laravel-dompdf`.

9. **Rekapitulasi Kelas & Status Kelengkapan (F13)**
   - Buku besar nilai sumatif per siswa dan rata-rata per mata pelajaran di kelas.
   - Indikator persentase kelengkapan pengisian raport.

10. **Riwayat Cetak Raport (F14)**
    - Pencatatan log siswa yang dicetak, periode (STS/SAS), waktu cetak, dan pembuat.

---

## 🔑 Akun Demo Siap Pakai

| Peran | Email | Kata Sandi | Keterangan |
|---|---|---|---|
| **Admin** | `admin@sdn2karangsari.sch.id` | `password` | Akses penuh seluruh modul |
| **Wali Kelas 1** | `wali1@sdn2karangsari.sch.id` | `password` | Wali Kelas 1 (Fase A) |
| **Wali Kelas 4** | `wali4@sdn2karangsari.sch.id` | `password` | Wali Kelas 4 (Fase B) |
| **Kepala Sekolah** | `kepsek@sdn2karangsari.sch.id` | `password` | Monitoring & Cetak Rekap |

> 💡 *Pada halaman login tersedia tombol klik satu kali untuk langsung mengisi akun demo di atas.*

---

## 🛠️ Instalasi & Menjalankan Aplikasi

1. **Jalankan Apache & MySQL di XAMPP Control Panel**.
2. **Konfigurasi Database** di `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=e_raport_sdn2karangsari
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. **Migrasi & Seeding Data Awal**:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Buka peramban di: `http://127.0.0.1:8000` atau melalui Apache XAMPP: `http://localhost/e_raport_sdn2karangsari/public/`.

---

## 🧪 Pengujian Otomatis

Seluruh pengujian unit dan fitur telah lulus:
```bash
php artisan test
```
```text
Pass: 6 tests, 21 assertions (Tests\Feature\ExampleTest, Tests\Feature\RaportTest)
```
