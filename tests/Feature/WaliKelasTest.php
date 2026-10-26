<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\WaliKelas6Seeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WaliKelasTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_wali_kelas_6_seeder_creates_user_and_assigns_to_kelas_6(): void
    {
        $this->seed(WaliKelas6Seeder::class);

        $user = User::where('email', 'wali6@sdn2karangsari.sch.id')->first();
        $this->assertNotNull($user);
        $this->assertEquals('wali', $user->role);
        $this->assertEquals('Diah Puji Lestari, S.Pd', $user->name);

        $kelas6 = Kelas::where('nama', 'like', '%Kelas 6%')->first();
        if ($kelas6) {
            $this->assertEquals($user->id, $kelas6->wali_id);
            $this->assertEquals('Diah Puji Lestari, S.Pd', $kelas6->wali_nama);
        }
    }

    public function test_wali_kelas_6_can_login_and_access_dashboard(): void
    {
        $this->seed(WaliKelas6Seeder::class);

        $user = User::where('email', 'wali6@sdn2karangsari.sch.id')->first();

        $response = $this->post(route('login.post'), [
            'email' => 'wali6@sdn2karangsari.sch.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee($user->name);
    }

    public function test_wali_kelas_6_only_sees_own_students_in_siswa_index(): void
    {
        $this->seed(WaliKelas6Seeder::class);
        $wali = User::where('email', 'wali6@sdn2karangsari.sch.id')->first();

        $kelas6 = Kelas::firstOrCreate(
            ['nama' => 'Kelas 6'],
            [
                'fase' => 'Fase C',
                'sekolah_id' => 1,
                'tahun_ajaran_id' => 1,
                'wali_id' => $wali->id,
            ]
        );
        $kelas6->update(['wali_id' => $wali->id]);

        $siswa6 = Siswa::create([
            'nama' => 'Murid Khusus Kelas Enam',
            'nis' => '999901',
            'nisn' => '999901',
            'status' => 'aktif',
        ]);
        KelasSiswa::create([
            'kelas_id' => $kelas6->id,
            'siswa_id' => $siswa6->id,
        ]);

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $siswa1 = Siswa::create([
            'nama' => 'Murid Khusus Kelas Satu',
            'nis' => '999902',
            'nisn' => '999902',
            'status' => 'aktif',
        ]);
        KelasSiswa::create([
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa1->id,
        ]);

        $response = $this->actingAs($wali)->get(route('siswa.index'));
        $response->assertStatus(200);
        $response->assertSee('Murid Khusus Kelas Enam');
        $response->assertDontSee('Murid Khusus Kelas Satu');
    }

    public function test_admin_dashboard_renders_clean_modern_ui(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Status Pengisian Nilai');
        $response->assertSee('Total Siswa Aktif');
        $response->assertSee('Rombongan Belajar');
        $response->assertSee('Input Nilai');
    }

    public function test_wali_kelas_seeder_creates_all_accounts_1_sampai_6_and_assigns_to_classes(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $user = User::where('email', "wali{$i}@sdn2karangsari.sch.id")->first();
            $this->assertNotNull($user, "Akun wali{$i}@sdn2karangsari.sch.id harus ada.");
            $this->assertEquals('wali', $user->role);

            $kelas = Kelas::where('nama', "Kelas {$i}")->first();
            $this->assertNotNull($kelas, "Kelas {$i} harus ada.");
            $this->assertEquals($user->id, $kelas->wali_id);
            $this->assertEquals($user->name, $kelas->wali_nama);
        }
    }

    public function test_wali_kelas_1_sampai_5_can_login_and_access_dashboard(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $email = "wali{$i}@sdn2karangsari.sch.id";
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user);

            $response = $this->post(route('login.post'), [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            $dashboard = $this->actingAs($user)->get(route('dashboard'));
            $dashboard->assertStatus(200);
            $dashboard->assertSee($user->name);

            auth()->logout();
        }
    }

    public function test_login_page_renders_quick_access_for_all_wali_kelas(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        for ($i = 1; $i <= 6; $i++) {
            $response->assertSee("wali{$i}@sdn2karangsari.sch.id");
            $response->assertSee("Kelas {$i}");
        }
    }
}
