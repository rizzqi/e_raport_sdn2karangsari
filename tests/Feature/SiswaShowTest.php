<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SiswaShowTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_siswa_show(): void
    {
        $siswa = Siswa::first();
        $this->assertNotNull($siswa);

        $response = $this->get(route('siswa.show', $siswa->id));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_siswa_detail(): void
    {
        $admin = User::where('role', 'admin')->first();
        $siswa = Siswa::first();
        $this->assertNotNull($admin);
        $this->assertNotNull($siswa);

        $response = $this->actingAs($admin)->get(route('siswa.show', $siswa->id));

        $response->assertOk();
        $response->assertViewIs('siswa.show');
        $response->assertViewHas('siswa');
        $response->assertSee($siswa->nama);
    }

    public function test_wali_can_view_student_in_their_class(): void
    {
        $wali = User::where('role', 'wali')->first();
        $kelas = Kelas::where('wali_id', $wali->id)->first();
        $this->assertNotNull($wali);
        $this->assertNotNull($kelas);

        $siswa = Siswa::create([
            'nama' => 'Budi Santoso Siswa Wali',
            'nis' => '998877',
            'status' => 'aktif',
        ]);
        KelasSiswa::create([
            'kelas_id' => $kelas->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($wali)->get(route('siswa.show', $siswa->id));
        $response->assertOk();
        $response->assertSee('Budi Santoso Siswa Wali');
    }

    public function test_wali_cannot_view_student_outside_their_class(): void
    {
        $wali1 = User::where('role', 'wali')->first();
        $waliLain = User::where('role', 'wali')->where('id', '!=', $wali1->id)->first();
        $kelasLain = Kelas::where('wali_id', $waliLain->id)->first();

        $siswaLain = Siswa::create([
            'nama' => 'Siswa Kelas Lain',
            'nis' => '112233',
            'status' => 'aktif',
        ]);
        KelasSiswa::create([
            'kelas_id' => $kelasLain->id,
            'siswa_id' => $siswaLain->id,
        ]);

        $response = $this->actingAs($wali1)->get(route('siswa.show', $siswaLain->id));
        $response->assertStatus(403);
    }

    public function test_ajax_or_json_request_returns_json_data(): void
    {
        $admin = User::where('role', 'admin')->first();
        $siswa = Siswa::first();

        $response = $this->actingAs($admin)->getJson(route('siswa.show', $siswa->id));
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'siswa' => [
                'id' => $siswa->id,
                'nama' => $siswa->nama,
            ],
        ]);
    }
}
