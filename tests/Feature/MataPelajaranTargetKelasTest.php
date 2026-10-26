<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\HitungNilaiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class MataPelajaranTargetKelasTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_target_kelas_can_be_stored_and_retrieved(): void
    {
        $admin = User::where('role', 'admin')->first();

        $mapel = MataPelajaran::create([
            'nama' => 'Robotika Khusus',
            'kkm' => 75,
            'urutan' => 15,
            'target_kelas' => [5, 6],
            'aktif' => true,
        ]);

        $this->assertEquals([5, 6], $mapel->fresh()->target_kelas);
        $this->assertEquals('Fase C (Kelas 5 - 6)', $mapel->fresh()->target_kelas_label);

        // Update target kelas via controller route
        $response = $this->actingAs($admin)->put(route('mapel.update', $mapel->id), [
            'nama' => 'Robotika Khusus',
            'kkm' => 75,
            'urutan' => 15,
            'target_kelas' => [4, 5, 6],
            'aktif' => 1,
        ]);

        $response->assertRedirect(route('mapel.index'));
        $this->assertEquals([4, 5, 6], $mapel->fresh()->target_kelas);
        $this->assertEquals('Kelas 4, 5, 6', $mapel->fresh()->target_kelas_label);
    }

    public function test_classes_filter_subjects_according_to_grade_level(): void
    {
        $ipas = MataPelajaran::where('nama', 'like', '%IPAS%')->first();
        if ($ipas) {
            $ipas->update(['target_kelas' => [3, 4, 5, 6]]);
        }

        $koding = MataPelajaran::create([
            'nama' => 'Koding & AI',
            'kkm' => 75,
            'urutan' => 20,
            'target_kelas' => [5, 6],
            'aktif' => true,
        ]);

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $kelas3 = Kelas::firstOrCreate(
            ['nama' => 'Kelas 3'],
            [
                'fase' => 'Fase B',
                'sekolah_id' => 1,
                'tahun_ajaran_id' => 1,
            ]
        );
        $kelas6 = Kelas::firstOrCreate(
            ['nama' => 'Kelas 6'],
            [
                'fase' => 'Fase C',
                'sekolah_id' => 1,
                'tahun_ajaran_id' => 1,
            ]
        );

        $mapelKelas1 = $kelas1->mataPelajaran()->pluck('nama')->toArray();
        $mapelKelas3 = $kelas3->mataPelajaran()->pluck('nama')->toArray();
        $mapelKelas6 = $kelas6->mataPelajaran()->pluck('nama')->toArray();

        // Kelas 1 tidak boleh ada IPAS maupun Koding
        $this->assertFalse(in_array('Ilmu Pengetahuan Alam dan Sosial (IPAS)', $mapelKelas1));
        $this->assertFalse(in_array('Koding & AI', $mapelKelas1));

        // Kelas 3 ada IPAS tapi tidak ada Koding
        if ($ipas) {
            $this->assertTrue(in_array('Ilmu Pengetahuan Alam dan Sosial (IPAS)', $mapelKelas3));
        }
        $this->assertFalse(in_array('Koding & AI', $mapelKelas3));

        // Kelas 6 ada IPAS dan Koding
        if ($ipas) {
            $this->assertTrue(in_array('Ilmu Pengetahuan Alam dan Sosial (IPAS)', $mapelKelas6));
        }
        $this->assertTrue(in_array('Koding & AI', $mapelKelas6));
    }

    public function test_hitung_nilai_service_uses_class_specific_subjects(): void
    {
        $ipas = MataPelajaran::where('nama', 'like', '%IPAS%')->first();
        if ($ipas) {
            $ipas->update(['target_kelas' => [3, 4, 5, 6]]);
        }

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $siswa = Siswa::first();
        $kelasSiswa = KelasSiswa::where('kelas_id', $kelas1->id)->where('siswa_id', $siswa->id)->first();

        $hitungService = app(HitungNilaiService::class);
        $hasil = $hitungService->hitungNilaiSiswa($kelasSiswa, 'sts');

        $daftarMapelHasil = array_column($hasil['baris'], 'mapel_nama');

        // IPAS tidak boleh masuk ke baris nilai siswa Kelas 1
        $this->assertNotContains('Ilmu Pengetahuan Alam dan Sosial (IPAS)', $daftarMapelHasil);
    }

    public function test_toggle_status_mapel_and_inactive_mapel_hidden_from_reports(): void
    {
        $admin = User::where('role', 'admin')->first();
        $mapel = MataPelajaran::where('aktif', true)->first();
        $this->assertNotNull($mapel);

        // 1. Nonaktifkan mapel via toggle route
        $toggleResponse = $this->actingAs($admin)->post(route('mapel.toggle-status', $mapel->id));
        $toggleResponse->assertRedirect(route('mapel.index'));
        $this->assertFalse($mapel->fresh()->aktif);

        // 2. Pastikan mapel nonaktif tidak muncul di daftar mata pelajaran kelas manapun
        $kelas = Kelas::first();
        $mapelAktifKelas = $kelas->mataPelajaran()->pluck('id')->toArray();
        $this->assertNotContains($mapel->id, $mapelAktifKelas);

        // 3. Pastikan tidak masuk ke baris perhitungan nilai / raport
        $ks = $kelas->kelasSiswa()->first();
        if ($ks) {
            $hasil = app(HitungNilaiService::class)->hitungNilaiSiswa($ks, 'sts');
            $mapelDiRaport = array_column($hasil['baris'], 'mapel_nama');
            $this->assertNotContains($mapel->nama, $mapelDiRaport);
        }

        // 4. Aktifkan kembali via toggle route
        $toggleBackResponse = $this->actingAs($admin)->post(route('mapel.toggle-status', $mapel->id));
        $toggleBackResponse->assertRedirect(route('mapel.index'));
        $this->assertTrue($mapel->fresh()->aktif);

        // 5. Test update mapel dengan checkbox aktif tidak dicentang (aktif = 0)
        $updateResponse = $this->actingAs($admin)->put(route('mapel.update', $mapel->id), [
            'nama' => $mapel->nama,
            'kkm' => $mapel->kkm,
            'urutan' => $mapel->urutan,
            // 'aktif' tidak disertakan (checkbox tidak dicentang)
        ]);
        $updateResponse->assertRedirect(route('mapel.index'));
        $this->assertFalse($mapel->fresh()->aktif);
    }
}
