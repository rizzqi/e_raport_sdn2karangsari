<?php

namespace Database\Seeders;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds for demo / sample data (siswa dummy & nilai contoh).
     */
    public function run(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%Kelas 1%')->first();
        if (! $kelas1) {
            return;
        }

        $mapelModels = MataPelajaran::all();
        $pembiasaanModels = Pembiasaan::all();

        $siswaData = [
            ['nama' => 'Ahmad Fauzi Pratama', 'nis' => '1001', 'nisn' => '0151234501'],
            ['nama' => 'Anisa Rahmawati', 'nis' => '1002', 'nisn' => '0151234502'],
            ['nama' => 'Bagas Arya Kusuma', 'nis' => '1003', 'nisn' => '0151234503'],
            ['nama' => 'Citra Dewi Lestari', 'nis' => '1004', 'nisn' => '0151234504'],
            ['nama' => 'Dimas Putra Setiawan', 'nis' => '1005', 'nisn' => '0151234505'],
        ];

        foreach ($siswaData as $sData) {
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
    }
}
