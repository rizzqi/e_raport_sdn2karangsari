<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WaliKelas6Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelas6 = Kelas::where('nama', 'like', '%Kelas 6%')->first();

        $namaWali = $kelas6?->wali_nama ?: 'Diah Puji Lestari, S.Pd';
        $nipWali = $kelas6?->wali_nip ?: '199207232022212004';

        $user = User::updateOrCreate(
            ['email' => 'wali6@sdn2karangsari.sch.id'],
            [
                'name' => $namaWali,
                'password' => Hash::make('password'),
                'role' => 'wali',
            ]
        );

        if ($kelas6) {
            $kelas6->update([
                'wali_id' => $user->id,
                'wali_nama' => $namaWali,
                'wali_nip' => $nipWali,
            ]);
        }
    }
}
