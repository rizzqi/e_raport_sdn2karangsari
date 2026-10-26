<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class KelasWaliEditTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_update_wali_nama_and_nip(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        $response = $this->actingAs($admin)->put(route('kelas.update.wali', $kelas->id), [
            'wali_nama' => 'Sri Wahyuni, S.Pd.SD.',
            'wali_nip' => '19850101 201001 2 015',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kelas->refresh();
        $this->assertEquals('Sri Wahyuni, S.Pd.SD.', $kelas->wali_nama);
        $this->assertEquals('19850101 201001 2 015', $kelas->wali_nip);
    }

    public function test_assigned_wali_can_update_own_class_wali_info(): void
    {
        $kelas = Kelas::first();
        $waliUser = User::where('role', 'wali')->first();
        $kelas->update(['wali_id' => $waliUser->id]);

        $response = $this->actingAs($waliUser)->put(route('kelas.update.wali', $kelas->id), [
            'wali_nama' => 'Nama Wali Baru, M.Pd.',
            'wali_nip' => '19790510 200501 1 003',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kelas->refresh();
        $this->assertEquals('Nama Wali Baru, M.Pd.', $kelas->wali_nama);
        $this->assertEquals('19790510 200501 1 003', $kelas->wali_nip);
    }

    public function test_wali_cannot_update_another_class_wali_info(): void
    {
        $kelas = Kelas::first();
        $waliUser1 = User::where('role', 'wali')->first();
        $waliUser2 = User::where('role', 'wali')->skip(1)->first();

        if (! $waliUser2) {
            $waliUser2 = User::create([
                'name' => 'Wali Lain',
                'email' => 'walilain@sdn2karangsari.sch.id',
                'password' => bcrypt('password'),
                'role' => 'wali',
            ]);
        }

        $kelas->update(['wali_id' => $waliUser1->id]);

        $response = $this->actingAs($waliUser2)->put(route('kelas.update.wali', $kelas->id), [
            'wali_nama' => 'Hacker Wali',
            'wali_nip' => '123',
        ]);

        $response->assertStatus(403);
    }

    public function test_input_kelas_page_displays_wali_edit_button(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('nilai.input.kelas', $kelas->id));
        $response->assertStatus(200);
        $response->assertSee('Edit Wali Kelas');
    }
}
