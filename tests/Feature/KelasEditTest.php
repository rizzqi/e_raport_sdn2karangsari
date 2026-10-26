<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class KelasEditTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_kelas_nama_and_fase(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        $response = $this->actingAs($admin)->put(route('kelas.update', $kelas->id), [
            'nama' => 'Kelas 3',
            'fase' => 'Fase B',
            'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
            'wali_nama' => 'Erna Sugiartiningsih, S.Pd',
            'wali_nip' => '19870101 201001 2 005',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kelas->refresh();
        $this->assertEquals('Kelas 3', $kelas->nama);
        $this->assertEquals('Fase B', $kelas->fase);
        $this->assertEquals('Erna Sugiartiningsih, S.Pd', $kelas->wali_nama);
    }

    public function test_admin_can_update_all_kelas_fields(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ta = TahunAjaran::first();

        $response = $this->actingAs($admin)->put(route('kelas.update', $kelas->id), [
            'nama' => 'Kelas 4A',
            'fase' => 'Fase B',
            'tahun_ajaran_id' => $ta->id,
            'wali_nama' => 'Guru Pengganti, S.Pd.',
            'wali_nip' => '19900202 201502 1 002',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kelas->refresh();
        $this->assertEquals('Kelas 4A', $kelas->nama);
        $this->assertEquals('Fase B', $kelas->fase);
        $this->assertEquals('Guru Pengganti, S.Pd.', $kelas->wali_nama);
        $this->assertEquals('19900202 201502 1 002', $kelas->wali_nip);
    }

    public function test_wali_cannot_update_kelas_structure(): void
    {
        $wali = User::where('role', 'wali')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($wali)->put(route('kelas.update', $kelas->id), [
            'nama' => 'Kelas Hacker',
            'fase' => 'Fase C',
            'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
            'wali_nama' => 'Wali',
        ]);

        $response->assertStatus(403);
    }

    public function test_validation_fails_if_nama_or_fase_empty(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->put(route('kelas.update', $kelas->id), [
            'nama' => '',
            'fase' => '',
            'tahun_ajaran_id' => $kelas->tahun_ajaran_id,
        ]);

        $response->assertSessionHasErrors(['nama', 'fase']);
    }

    public function test_kelas_index_displays_edit_kelas_modal_for_admin(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('kelas.index'));
        $response->assertStatus(200);
        $response->assertSee('Edit Kelas');
        $response->assertSee('modalEditKelas');
    }

    public function test_kelas_show_displays_edit_kelas_modal_for_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.show', $kelas->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Kelas & Fase', false);
        $response->assertSee('modalEditKelas');
    }
}
