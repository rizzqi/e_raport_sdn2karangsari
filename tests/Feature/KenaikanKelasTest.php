<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class KenaikanKelasTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_access_mutasi_page(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('kelas.mutasi.index'));
        $response->assertStatus(200);
        $response->assertSee('Kenaikan Kelas');
        $response->assertSee('Pindah Kelas');
        $response->assertSee('Proses Kelulusan Siswa');
    }

    public function test_non_admin_cannot_access_mutasi_page(): void
    {
        $wali = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($wali)->get(route('kelas.mutasi.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_promote_students_with_mode_naik_preserving_history(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas1 = Kelas::where('nama', 'like', '%1%')->firstOrFail();
        $kelas2 = Kelas::where('nama', 'like', '%2%')->firstOrFail();

        $siswa = Siswa::create([
            'nama' => 'Siswa Naik Kelas',
            'nis' => '9901',
            'nisn' => '009901',
            'status' => 'aktif',
        ]);

        KelasSiswa::create([
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($admin)->post(route('kelas.mutasi.kenaikan'), [
            'kelas_asal_id' => $kelas1->id,
            'kelas_tujuan_id' => $kelas2->id,
            'siswa_ids' => [$siswa->id],
            'mode' => 'naik',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // History in kelas 1 is preserved
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa->id,
        ]);

        // Student is now enrolled in kelas 2
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas2->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    public function test_admin_can_transfer_students_with_mode_pindah_removing_from_old_class(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas1 = Kelas::where('nama', 'like', '%1%')->firstOrFail();
        $kelas2 = Kelas::where('nama', 'like', '%2%')->firstOrFail();

        $siswa = Siswa::create([
            'nama' => 'Siswa Pindah Rombel',
            'nis' => '9902',
            'nisn' => '009902',
            'status' => 'aktif',
        ]);

        KelasSiswa::create([
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($admin)->post(route('kelas.mutasi.kenaikan'), [
            'kelas_asal_id' => $kelas1->id,
            'kelas_tujuan_id' => $kelas2->id,
            'siswa_ids' => [$siswa->id],
            'mode' => 'pindah',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Removed from kelas 1
        $this->assertDatabaseMissing('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $siswa->id,
        ]);

        // Present in kelas 2
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas2->id,
            'siswa_id' => $siswa->id,
        ]);
    }

    public function test_admin_can_graduate_students(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();

        $siswa = Siswa::create([
            'nama' => 'Siswa Tamat Belajar',
            'nis' => '9903',
            'nisn' => '009903',
            'status' => 'aktif',
        ]);

        KelasSiswa::create([
            'kelas_id' => $kelas->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($admin)->post(route('kelas.mutasi.kelulusan'), [
            'kelas_id' => $kelas->id,
            'siswa_ids' => [$siswa->id],
            'status' => 'lulus',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->id,
            'status' => 'lulus',
        ]);
    }

    public function test_admin_can_revert_graduated_student_to_active(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();

        $siswa = Siswa::create([
            'nama' => 'Siswa Batal Lulus',
            'nis' => '9904',
            'nisn' => '009904',
            'status' => 'lulus',
        ]);

        KelasSiswa::create([
            'kelas_id' => $kelas->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($admin)->post(route('kelas.mutasi.kelulusan'), [
            'kelas_id' => $kelas->id,
            'siswa_ids' => [$siswa->id],
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', [
            'id' => $siswa->id,
            'status' => 'aktif',
        ]);
    }

    public function test_kenaikan_rejects_same_target_class(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas1 = Kelas::firstOrFail();
        $siswa = Siswa::firstOrFail();

        $response = $this->actingAs($admin)->post(route('kelas.mutasi.kenaikan'), [
            'kelas_asal_id' => $kelas1->id,
            'kelas_tujuan_id' => $kelas1->id,
            'siswa_ids' => [$siswa->id],
            'mode' => 'naik',
        ]);

        $response->assertSessionHasErrors('kelas_tujuan_id');
    }
}
