<?php

namespace Database\Seeders;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@sdn2karangsari.sch.id'],
            [
                'name' => 'Administrator Sekolah',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $kepsek = User::firstOrCreate(
            ['email' => 'kepsek@sdn2karangsari.sch.id'],
            [
                'name' => 'Hj. Siti Aminah, S.Pd., M.Pd.',
                'password' => Hash::make('password'),
                'role' => 'kepsek',
            ]
        );

        $wali1 = User::firstOrCreate(
            ['email' => 'wali1@sdn2karangsari.sch.id'],
            [
                'name' => 'Dewi Lestari, S.Pd.',
                'password' => Hash::make('password'),
                'role' => 'wali',
            ]
        );

        $wali4 = User::firstOrCreate(
            ['email' => 'wali4@sdn2karangsari.sch.id'],
            [
                'name' => 'Budi Santoso, S.Pd.',
                'password' => Hash::make('password'),
                'role' => 'wali',
            ]
        );

        // 2. Sekolah
        $sekolah = Sekolah::firstOrCreate(
            ['nama' => 'SD NEGERI 2 KARANGSARI'],
            [
                'alamat' => 'Jl. Karangsari No. 12, Kec. Sempu, Kab. Banyuwangi, Jawa Timur 68468',
                'pemda' => 'PEMERINTAH KABUPATEN BANYUWANGI',
                'dinas' => 'DINAS PENDIDIKAN',
                'kota' => 'Banyuwangi',
                'kepsek' => 'Hj. Siti Aminah, S.Pd., M.Pd.',
                'nip' => '19720512 199603 2 001',
            ]
        );

        // 3. Tahun Ajaran
        $tahunAjaran = TahunAjaran::firstOrCreate(
            ['tahun' => '2026/2027', 'semester' => 'ganjil'],
            ['aktif' => true]
        );

        // 4. Mata Pelajaran
        $mapels = [
            ['nama' => 'Pendidikan Agama dan Budi Pekerti', 'urutan' => 1],
            ['nama' => 'Pendidikan Pancasila', 'urutan' => 2],
            ['nama' => 'Bahasa Indonesia', 'urutan' => 3],
            ['nama' => 'Matematika', 'urutan' => 4],
            ['nama' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)', 'urutan' => 5],
            ['nama' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)', 'urutan' => 6],
            ['nama' => 'Seni dan Budaya', 'urutan' => 7],
            ['nama' => 'Bahasa Inggris', 'urutan' => 8],
            ['nama' => 'Bahasa Daerah / Bahasa Jawa', 'urutan' => 9],
        ];

        $mapelModels = [];
        foreach ($mapels as $m) {
            $mapelModels[] = MataPelajaran::firstOrCreate(
                ['nama' => $m['nama']],
                ['urutan' => $m['urutan'], 'aktif' => true]
            );
        }

        // 5. Pembiasaan
        $pembiasaans = [
            ['nama' => 'Berdoa sebelum dan sesudah beraktivitas', 'urutan' => 1],
            ['nama' => 'Menjaga kebersihan diri dan lingkungan madrasah/sekolah', 'urutan' => 2],
            ['nama' => 'Berperilaku santun dan hormat kepada guru dan sesama', 'urutan' => 3],
            ['nama' => 'Bersikap jujur dan bertanggung jawab atas tugas', 'urutan' => 4],
            ['nama' => 'Disiplin dan tepat waktu masuk sekolah', 'urutan' => 5],
        ];

        $pembiasaanModels = [];
        foreach ($pembiasaans as $p) {
            $pembiasaanModels[] = Pembiasaan::firstOrCreate(
                ['nama' => $p['nama']],
                ['urutan' => $p['urutan'], 'aktif' => true]
            );
        }

        // 6. Kelas
        $kelas1 = Kelas::firstOrCreate(
            ['nama' => 'Kelas 1', 'tahun_ajaran_id' => $tahunAjaran->id],
            [
                'fase' => 'Fase A',
                'sekolah_id' => $sekolah->id,
                'wali_id' => $wali1->id,
                'wali_nama' => 'Dewi Lestari, S.Pd.',
                'wali_nip' => '19880315 201101 2 008',
            ]
        );

        $kelas4 = Kelas::firstOrCreate(
            ['nama' => 'Kelas 4', 'tahun_ajaran_id' => $tahunAjaran->id],
            [
                'fase' => 'Fase B',
                'sekolah_id' => $sekolah->id,
                'wali_id' => $wali4->id,
                'wali_nama' => 'Budi Santoso, S.Pd.',
                'wali_nip' => '19820720 200902 1 003',
            ]
        );

        // 6b. Guru Pengampu Non-Wali (3 Guru Mapel per Kelas)
        $mapelPai = MataPelajaran::where('nama', 'like', '%Agama%')->first();
        $mapelPjok = MataPelajaran::where('nama', 'like', '%PJOK%')->orWhere('nama', 'like', '%Jasmani%')->first();
        $mapelInggris = MataPelajaran::where('nama', 'like', '%Inggris%')->orWhere('nama', 'like', '%Jawa%')->first();

        foreach ([$kelas1, $kelas4] as $k) {
            KelasGuru::firstOrCreate([
                'kelas_id' => $k->id,
                'urutan' => 1,
            ], [
                'mapel_id' => $mapelPai?->id,
                'nama' => 'Ust. Muhammad Sholeh, S.Pd.I',
                'nip' => '19840510 201001 1 015',
            ]);

            KelasGuru::firstOrCreate([
                'kelas_id' => $k->id,
                'urutan' => 2,
            ], [
                'mapel_id' => $mapelPjok?->id,
                'nama' => 'Bagus Wicaksono, S.Pd.',
                'nip' => '19910817 201902 1 007',
            ]);

            KelasGuru::firstOrCreate([
                'kelas_id' => $k->id,
                'urutan' => 3,
            ], [
                'mapel_id' => $mapelInggris?->id,
                'nama' => 'Nurul Hidayati, S.Pd.',
                'nip' => '19930412 202012 2 009',
            ]);
        }

        // 7. Siswa & Kelas Siswa
        $siswaData = [
            ['nama' => 'Ahmad Fauzi Pratama', 'nis' => '1001', 'nisn' => '0151234501'],
            ['nama' => 'Anisa Rahmawati', 'nis' => '1002', 'nisn' => '0151234502'],
            ['nama' => 'Bagas Arya Kusuma', 'nis' => '1003', 'nisn' => '0151234503'],
            ['nama' => 'Citra Dewi Lestari', 'nis' => '1004', 'nisn' => '0151234504'],
            ['nama' => 'Dimas Putra Setiawan', 'nis' => '1005', 'nisn' => '0151234505'],
        ];

        foreach ($siswaData as $index => $sData) {
            $siswa = Siswa::firstOrCreate(
                ['nis' => $sData['nis']],
                ['nama' => $sData['nama'], 'nisn' => $sData['nisn'], 'status' => 'aktif']
            );

            $ks = KelasSiswa::firstOrCreate([
                'kelas_id' => $kelas1->id,
                'siswa_id' => $siswa->id,
            ]);

            // Seed Nilai STS dan SAS
            foreach (['sts', 'sas'] as $periode) {
                // Kehadiran
                Kehadiran::firstOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'periode' => $periode,
                ], [
                    'sakit' => rand(0, 2),
                    'izin' => rand(0, 1),
                    'alpa' => 0,
                ]);

                // Nilai Mata Pelajaran
                foreach ($mapelModels as $mapel) {
                    $base = rand(78, 92);
                    Nilai::firstOrCreate([
                        'kelas_siswa_id' => $ks->id,
                        'mapel_id' => $mapel->id,
                        'periode' => $periode,
                    ], [
                        'formatif' => $base + rand(-2, 3),
                        'sumatif' => $base + rand(-3, 4),
                    ]);
                }

                // Pembiasaan
                $predikats = ['Sangat Baik', 'Baik', 'Baik', 'Sangat Baik', 'Baik'];
                foreach ($pembiasaanModels as $idx => $pem) {
                    NilaiPembiasaan::firstOrCreate([
                        'kelas_siswa_id' => $ks->id,
                        'pembiasaan_id' => $pem->id,
                        'periode' => $periode,
                    ], [
                        'predikat' => $predikats[$idx % count($predikats)],
                    ]);
                }
            }
        }

        // 8. Pastikan seluruh akun Wali Kelas (Kelas 1 - 6) terkonfigurasi dan terhubung ke rombel
        $this->call(WaliKelasSeeder::class);
    }
}
