<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WaliKelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultWali = [
            1 => [
                'nama' => 'Dinda Asni F, S.Pd.',
                'nip' => '-',
                'fase' => 'Fase A',
            ],
            2 => [
                'nama' => 'Umi Faiqotur Rizkiyani, S.Pd',
                'nip' => '-',
                'fase' => 'Fase A',
            ],
            3 => [
                'nama' => 'Erna Sugiartiningsih, S.Pd',
                'nip' => '196712132008012013',
                'fase' => 'Fase B',
            ],
            4 => [
                'nama' => 'Wahyu SIska Novita, S.Pd.',
                'nip' => '-',
                'fase' => 'Fase B',
            ],
            5 => [
                'nama' => 'Kiki Susanto, S.Pd',
                'nip' => '-',
                'fase' => 'Fase C',
            ],
            6 => [
                'nama' => 'Diah Puji Lestari, S.Pd',
                'nip' => '199207232022212004',
                'fase' => 'Fase C',
            ],
        ];

        $tahunAktif = TahunAjaran::aktif()->first() ?? TahunAjaran::first();
        $sekolah = Sekolah::first();

        for ($i = 1; $i <= 6; $i++) {
            $kelas = Kelas::where('nama', 'like', "%Kelas {$i}%")->first();

            $namaWali = $kelas?->wali_nama ?: $defaultWali[$i]['nama'];
            $nipWali = ($kelas?->wali_nip !== null && $kelas?->wali_nip !== '') ? $kelas->wali_nip : $defaultWali[$i]['nip'];

            $user = User::updateOrCreate(
                ['email' => "wali{$i}@sdn2karangsari.sch.id"],
                [
                    'name' => $namaWali,
                    'password' => Hash::make('password'),
                    'role' => 'wali',
                ]
            );

            if ($kelas) {
                $kelas->update([
                    'wali_id' => $user->id,
                    'wali_nama' => $namaWali,
                    'wali_nip' => $nipWali,
                ]);
            } elseif ($tahunAktif && $sekolah) {
                Kelas::create([
                    'nama' => "Kelas {$i}",
                    'fase' => $defaultWali[$i]['fase'],
                    'sekolah_id' => $sekolah->id,
                    'tahun_ajaran_id' => $tahunAktif->id,
                    'wali_id' => $user->id,
                    'wali_nama' => $namaWali,
                    'wali_nip' => $nipWali,
                ]);
            }
        }
    }
}
