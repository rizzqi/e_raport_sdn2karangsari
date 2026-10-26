<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class SiswaTambahManualTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_add_student_without_nis_and_nisn_and_auto_generates_temporary_nis(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('siswa.store'), [
            'nama' => 'Ahmad Murid Baru Kelas Satu',
            'nis' => '',
            'nisn' => '',
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswa = Siswa::where('nama', 'Ahmad Murid Baru Kelas Satu')->first();
        $this->assertNotNull($siswa);
        $this->assertNull($siswa->nisn);
        $this->assertStringStartsWith('TMP-', $siswa->nis);
        $this->assertEquals('aktif', $siswa->status);
    }

    public function test_admin_can_add_student_and_assign_directly_to_kelas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas1 = Kelas::first();
        $this->assertNotNull($kelas1);

        $response = $this->actingAs($admin)->post(route('siswa.store'), [
            'nama' => 'Siti Murid Baru',
            'nis' => '',
            'nisn' => '',
            'kelas_id' => $kelas1->id,
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('siswa.index', ['kelas_id' => $kelas1->id]));
        $response->assertSessionHas('success');

        $siswa = Siswa::where('nama', 'Siti Murid Baru')->first();
        $this->assertNotNull($siswa);
        $this->assertStringStartsWith('TMP-', $siswa->nis);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    public function test_admin_can_add_student_with_custom_nis(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->post(route('siswa.store'), [
            'nama' => 'Budi Ber-NIS Resmi',
            'nis' => '9988',
            'nisn' => '0123998811',
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('siswa', [
            'nama' => 'Budi Ber-NIS Resmi',
            'nis' => '9988',
            'nisn' => '0123998811',
        ]);
    }

    public function test_admin_can_update_temporary_nis_to_real_nis_later(): void
    {
        $admin = User::where('role', 'admin')->first();

        $siswa = Siswa::create([
            'nama' => 'Doni Sementara',
            'nis' => 'TMP-2026-9999',
            'nisn' => null,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->put(route('siswa.update', $siswa->id), [
            'nama' => 'Doni Sementara',
            'nis' => '2401',
            'nisn' => '0159998877',
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $siswa->refresh();
        $this->assertEquals('2401', $siswa->nis);
        $this->assertEquals('0159998877', $siswa->nisn);
    }

    public function test_admin_can_assign_unplaced_student_to_kelas_via_update(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        // Siswa tanpa kelas
        $siswa = Siswa::create([
            'nama' => 'Rina Belum Ditempatkan',
            'nis' => 'TMP-2026-1122',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($admin)->put(route('siswa.update', $siswa->id), [
            'nama' => 'Rina Belum Ditempatkan',
            'nis' => 'TMP-2026-1122',
            'kelas_id' => $kelas->id,
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_siswa', [
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
        ]);
    }
}
