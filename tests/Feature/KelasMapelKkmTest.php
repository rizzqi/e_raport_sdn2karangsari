<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasMapelKkm;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\HitungNilaiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class KelasMapelKkmTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_kkm_per_rombel_and_per_mapel(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapels = MataPelajaran::aktif()->take(2)->get();

        $response = $this->actingAs($admin)->put(route('kelas.kkm.update', $kelas->id), [
            'kkm' => [
                $mapels[0]->id => 75,
                $mapels[1]->id => 80,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_mapel_kkm', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapels[0]->id,
            'kkm' => 75,
        ]);

        $this->assertDatabaseHas('kelas_mapel_kkm', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapels[1]->id,
            'kkm' => 80,
        ]);

        $this->assertEquals(75, $kelas->fresh()->getKkmForMapel($mapels[0]));
        $this->assertEquals(80, $kelas->fresh()->getKkmForMapel($mapels[1]));
    }

    public function test_wali_of_class_can_update_kkm_for_their_own_class(): void
    {
        $wali = User::where('role', 'wali')->first();
        $kelas = Kelas::first();
        $kelas->update(['wali_id' => $wali->id]);

        $mapel = MataPelajaran::aktif()->first();

        $response = $this->actingAs($wali)->put(route('kelas.kkm.update', $kelas->id), [
            'kkm' => [
                $mapel->id => 78,
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_mapel_kkm', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'kkm' => 78,
        ]);
    }

    public function test_other_wali_cannot_update_kkm_of_different_class(): void
    {
        $wali1 = User::where('role', 'wali')->first();
        $otherWali = User::factory()->create(['role' => 'wali']);

        $kelas = Kelas::first();
        $kelas->update(['wali_id' => $wali1->id]);

        $mapel = MataPelajaran::aktif()->first();

        $response = $this->actingAs($otherWali)->put(route('kelas.kkm.update', $kelas->id), [
            'kkm' => [
                $mapel->id => 85,
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_kkm_falls_back_to_master_if_not_configured_for_class(): void
    {
        $kelas = Kelas::first();
        $mapel = MataPelajaran::aktif()->first();
        $mapel->update(['kkm' => 72]);

        // Belum ada konfigurasi khusus untuk kelas ini
        KelasMapelKkm::where('kelas_id', $kelas->id)->where('mapel_id', $mapel->id)->delete();

        $this->assertEquals(72, $kelas->getKkmForMapel($mapel));
    }

    public function test_nullable_kkm_input_deletes_custom_record_and_reverts_to_master(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::aktif()->first();
        $mapel->update(['kkm' => 70]);

        // Buat KKM khusus 85
        KelasMapelKkm::updateOrCreate(
            ['kelas_id' => $kelas->id, 'mapel_id' => $mapel->id],
            ['kkm' => 85]
        );
        $this->assertEquals(85, $kelas->fresh()->getKkmForMapel($mapel));

        // Submit form dengan string kosong untuk mereset
        $this->actingAs($admin)->put(route('kelas.kkm.update', $kelas->id), [
            'kkm' => [
                $mapel->id => '',
            ],
        ]);

        $this->assertDatabaseMissing('kelas_mapel_kkm', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
        ]);

        $this->assertEquals(70, $kelas->fresh()->getKkmForMapel($mapel));
    }

    public function test_hitung_nilai_service_uses_class_kkm_for_tuntas_evaluation(): void
    {
        $kelas = Kelas::first();
        $kelasSiswa = KelasSiswa::where('kelas_id', $kelas->id)->first();
        $mapel = MataPelajaran::aktif()->first();

        // Beri siswa nilai 72
        Nilai::updateOrCreate([
            'kelas_siswa_id' => $kelasSiswa->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ], [
            'sumatif' => 72,
        ]);

        $service = app(HitungNilaiService::class);

        // Kasus 1: KKM kelas di-set 70 -> Nilai 72 harus Tuntas
        KelasMapelKkm::updateOrCreate(
            ['kelas_id' => $kelas->id, 'mapel_id' => $mapel->id],
            ['kkm' => 70]
        );

        $hasil70 = $service->hitungNilaiSiswa($kelasSiswa->fresh(), 'sts');
        $barisMapel70 = collect($hasil70['baris'])->firstWhere('mapel_id', $mapel->id);
        $this->assertEquals(70, $barisMapel70['kkm']);
        $this->assertEquals('Tuntas', $barisMapel70['keterangan']);

        // Kasus 2: KKM kelas dinaikkan ke 75 -> Nilai 72 harus Belum Tuntas
        KelasMapelKkm::updateOrCreate(
            ['kelas_id' => $kelas->id, 'mapel_id' => $mapel->id],
            ['kkm' => 75]
        );

        $hasil75 = $service->hitungNilaiSiswa($kelasSiswa->fresh(), 'sts');
        $barisMapel75 = collect($hasil75['baris'])->firstWhere('mapel_id', $mapel->id);
        $this->assertEquals(75, $barisMapel75['kkm']);
        $this->assertEquals('Belum Tuntas', $barisMapel75['keterangan']);
    }

    public function test_kelas_show_page_renders_kkm_rombel_and_modal(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.show', $kelas->id));

        $response->assertStatus(200);
        $response->assertSee('modalEditKkmKelas');
        $response->assertSee(route('kelas.kkm.update', $kelas->id));
    }

    public function test_kkm_updated_in_one_class_does_not_affect_other_classes(): void
    {
        $kelas1 = Kelas::orderBy('id', 'asc')->first();
        $kelas2 = Kelas::orderBy('id', 'desc')->first();
        $this->assertNotEquals($kelas1->id, $kelas2->id);

        $wali2 = User::factory()->create(['role' => 'wali']);
        $kelas2->update(['wali_id' => $wali2->id]);

        $mapel = MataPelajaran::aktif()->first();
        $mapel->update(['kkm' => 70]);

        // Pastikan awal keduanya fallback ke 70
        $this->assertEquals(70, $kelas1->fresh()->getKkmForMapel($mapel));
        $this->assertEquals(70, $kelas2->fresh()->getKkmForMapel($mapel));

        // Wali kelas 2 mengubah KKM kelas 2 menjadi 82
        $response = $this->actingAs($wali2)->put(route('kelas.kkm.update', $kelas2->id), [
            'kkm' => [
                $mapel->id => 82,
            ],
        ]);

        $response->assertRedirect();

        // KKM Kelas 2 sekarang 82
        $this->assertEquals(82, $kelas2->fresh()->getKkmForMapel($mapel));

        // KKM Kelas 1 TETAP 70 (TIDAK terpengaruh)
        $this->assertEquals(70, $kelas1->fresh()->getKkmForMapel($mapel));
    }

    public function test_input_kelas_page_renders_kkm_modal_for_assigned_wali(): void
    {
        TahunAjaran::query()->update(['aktif' => true]);
        $wali = User::where('email', 'wali1@sdn2karangsari.sch.id')->first();
        $kelas = Kelas::where('wali_id', $wali->id)->first();

        $response = $this->actingAs($wali)->get(route('nilai.input.kelas', $kelas->id));

        $response->assertStatus(200);
        $response->assertSee('modalEditKkmKelas');
        $response->assertSee('Ubah KKM');
        $response->assertSee(route('kelas.kkm.update', $kelas->id));
    }

    public function test_other_wali_cannot_access_input_kelas_of_different_class(): void
    {
        TahunAjaran::query()->update(['aktif' => true]);
        $wali1 = User::where('email', 'wali1@sdn2karangsari.sch.id')->first();
        $otherWali = User::factory()->create(['role' => 'wali']);
        $kelas = Kelas::where('wali_id', $wali1->id)->first();

        $response = $this->actingAs($otherWali)->get(route('nilai.input.kelas', $kelas->id));

        $response->assertStatus(403);
    }
}
