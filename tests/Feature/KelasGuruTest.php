<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class KelasGuruTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_three_non_wali_teachers_for_class(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $mapel1 = MataPelajaran::orderBy('urutan')->first();
        $mapel2 = MataPelajaran::orderBy('urutan')->skip(1)->first();
        $mapel3 = MataPelajaran::orderBy('urutan')->skip(2)->first();

        $response = $this->actingAs($admin)->put(route('kelas.guru-pengampu.update', $kelas->id), [
            'guru' => [
                [
                    'mapel_id' => $mapel1->id,
                    'nama' => 'Ust. Muhammad Sholeh, S.Pd.I',
                    'nip' => '19840510 201001 1 015',
                ],
                [
                    'mapel_id' => $mapel2->id,
                    'nama' => 'Bagus Wicaksono, S.Pd.',
                    'nip' => '19910817 201902 1 007',
                ],
                [
                    'mapel_id' => $mapel3->id,
                    'nama' => 'Nurul Hidayati, S.Pd.',
                    'nip' => '19930412 202012 2 009',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_guru', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel1->id,
            'nama' => 'Ust. Muhammad Sholeh, S.Pd.I',
            'nip' => '19840510 201001 1 015',
            'urutan' => 1,
        ]);

        $this->assertDatabaseHas('kelas_guru', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel2->id,
            'nama' => 'Bagus Wicaksono, S.Pd.',
            'nip' => '19910817 201902 1 007',
            'urutan' => 2,
        ]);

        $this->assertDatabaseHas('kelas_guru', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel3->id,
            'nama' => 'Nurul Hidayati, S.Pd.',
            'nip' => '19930412 202012 2 009',
            'urutan' => 3,
        ]);
    }

    public function test_daftar_hadir_automatically_uses_subject_teacher_as_pengawas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $mapelPai = MataPelajaran::where('nama', 'like', '%Agama%')->first();

        // Tetapkan guru PAI
        KelasGuru::updateOrCreate([
            'kelas_id' => $kelas->id,
            'urutan' => 1,
        ], [
            'mapel_id' => $mapelPai->id,
            'nama' => 'Ust. Muhammad Sholeh, S.Pd.I',
            'nip' => '19840510 201001 1 015',
        ]);

        // 1. Saat mapel Agama dipilih, pengawas otomatis Ust. Muhammad Sholeh
        $response = $this->actingAs($admin)->get(route('kelas.daftar-hadir', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
            'mapel' => $mapelPai->nama,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Ust. Muhammad Sholeh, S.Pd.I');
        $response->assertSee('19840510 201001 1 015');

        // 2. Saat mapel Matematika dipilih (bukan mapel guru pengampu khusus), pengawas otomatis Wali Kelas
        $mapelMat = MataPelajaran::where('nama', 'like', '%Matematika%')->first();
        $responseMat = $this->actingAs($admin)->get(route('kelas.daftar-hadir', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
            'mapel' => $mapelMat->nama,
        ]));

        $responseMat->assertStatus(200);
        $responseMat->assertSee($kelas->nama_wali);

        // 3. Pada unduhan Excel, pengawas juga terisi Ust. Muhammad Sholeh
        $responseExcel = $this->actingAs($admin)->get(route('kelas.daftar-hadir.excel', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
            'mapel' => $mapelPai->nama,
        ]));

        $responseExcel->assertStatus(200);
        $this->assertStringContainsString('Ust. Muhammad Sholeh, S.Pd.I', $responseExcel->getContent());
        $this->assertStringContainsString('19840510 201001 1 015', $responseExcel->getContent());
    }

    public function test_detail_kelas_page_displays_three_non_wali_teachers(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.show', $kelas->id));

        $response->assertStatus(200);
        $response->assertSee('3 Guru Mata Pelajaran (Bukan Wali Kelas)');
        $response->assertSee('Ust. Muhammad Sholeh, S.Pd.I');
        $response->assertSee('Bagus Wicaksono, S.Pd.');
    }
}
