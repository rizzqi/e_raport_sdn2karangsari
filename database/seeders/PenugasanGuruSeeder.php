<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenugasanGuruSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun Guru Mata Pelajaran Khusus (Non-Wali)
        $guruAgama = User::firstOrCreate(
            ['email' => 'agama@sdn2karangsari.sch.id'],
            [
                'name' => 'Asrofi, S.Pd.I',
                'password' => Hash::make('password'),
                'role' => 'guru',
            ]
        );

        $guruPjok = User::firstOrCreate(
            ['email' => 'pjok@sdn2karangsari.sch.id'],
            [
                'name' => 'Wanto, S.Pd',
                'password' => Hash::make('password'),
                'role' => 'guru',
            ]
        );

        // Cari mata pelajaran Agama & PJOK
        $mapelAgama = MataPelajaran::where('nama', 'like', '%Agama%')->first();
        $mapelPjok = MataPelajaran::where('nama', 'like', '%PJOK%')->orWhere('nama', 'like', '%Jasmani%')->first();

        $kelasList = Kelas::all();

        foreach ($kelasList as $kelas) {
            // A. Penugasan Guru Agama
            if ($mapelAgama) {
                $kgAgama = KelasGuru::where('kelas_id', $kelas->id)
                    ->where(function ($q) use ($mapelAgama) {
                        $q->where('mapel_id', $mapelAgama->id)
                          ->orWhere('nama', 'like', '%Asrofi%');
                    })->first();

                if ($kgAgama) {
                    $kgAgama->update([
                        'user_id' => $guruAgama->id,
                        'mapel_id' => $mapelAgama->id,
                        'nama' => $kgAgama->nama ?: $guruAgama->name,
                    ]);
                } else {
                    KelasGuru::create([
                        'kelas_id' => $kelas->id,
                        'mapel_id' => $mapelAgama->id,
                        'user_id' => $guruAgama->id,
                        'nama' => $guruAgama->name,
                        'nip' => '-',
                        'urutan' => 1,
                    ]);
                }
            }

            // B. Penugasan Guru PJOK
            if ($mapelPjok) {
                $kgPjok = KelasGuru::where('kelas_id', $kelas->id)
                    ->where(function ($q) use ($mapelPjok) {
                        $q->where('mapel_id', $mapelPjok->id)
                          ->orWhere('nama', 'like', '%Wanto%');
                    })->first();

                if ($kgPjok) {
                    $kgPjok->update([
                        'user_id' => $guruPjok->id,
                        'mapel_id' => $mapelPjok->id,
                        'nama' => $kgPjok->nama ?: $guruPjok->name,
                    ]);
                } else {
                    KelasGuru::create([
                        'kelas_id' => $kelas->id,
                        'mapel_id' => $mapelPjok->id,
                        'user_id' => $guruPjok->id,
                        'nama' => $guruPjok->name,
                        'nip' => '197905102021211007',
                        'urutan' => 2,
                    ]);
                }
            }

            // C. Hubungkan record kelas_guru yang namanya cocok dengan pengguna user yang ada
            $existingGurus = KelasGuru::where('kelas_id', $kelas->id)->whereNull('user_id')->get();
            foreach ($existingGurus as $eg) {
                $matchedUser = User::where('name', $eg->nama)->first();
                if ($matchedUser) {
                    $eg->update(['user_id' => $matchedUser->id]);
                }
            }
        }
    }
}
