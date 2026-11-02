<?php

namespace Tests\Feature;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Raport;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RaportAuthorizationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_and_kepsek_can_access_any_student_raport(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kepsek = User::where('role', 'kepsek')->firstOrFail();
        $ks = KelasSiswa::firstOrFail();

        // Admin preview
        $this->actingAs($admin)
            ->get(route('raport.preview', $ks->id))
            ->assertStatus(200);

        // Kepsek preview
        $this->actingAs($kepsek)
            ->get(route('raport.preview', $ks->id))
            ->assertStatus(200);
    }

    public function test_wali_kelas_can_access_own_class_student_raport(): void
    {
        $ks = KelasSiswa::firstOrFail();
        $wali = $ks->kelas->wali;
        $this->assertNotNull($wali);

        $this->actingAs($wali)
            ->get(route('raport.preview', $ks->id))
            ->assertStatus(200);

        $this->actingAs($wali)
            ->get(route('raport.download.docx', $ks->id))
            ->assertStatus(200);
    }

    public function test_wali_kelas_is_forbidden_from_accessing_other_class_student_raport(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%1%')->firstOrFail();
        $kelas4 = Kelas::where('nama', 'like', '%4%')->firstOrFail();

        $wali1 = $kelas1->wali;

        // Create student in Kelas 4
        $siswa4 = Siswa::create(['nama' => 'Budi Siswa 4', 'nis' => '4001', 'nisn' => '04001', 'status' => 'aktif']);
        $ks4 = KelasSiswa::create(['kelas_id' => $kelas4->id, 'siswa_id' => $siswa4->id]);

        $this->actingAs($wali1)
            ->get(route('raport.preview', $ks4->id))
            ->assertStatus(403);

        $this->actingAs($wali1)
            ->get(route('raport.download.docx', $ks4->id))
            ->assertStatus(403);

        $this->actingAs($wali1)
            ->get(route('raport.download.pdf', $ks4->id))
            ->assertStatus(403);

        $this->actingAs($wali1)
            ->get(route('raport.cetak.kelas', $kelas4->id))
            ->assertStatus(403);
    }

    public function test_wali_kelas_only_sees_own_class_in_riwayat_cetak(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%1%')->firstOrFail();
        $kelas4 = Kelas::where('nama', 'like', '%4%')->firstOrFail();

        $wali1 = $kelas1->wali;
        $ks1 = $kelas1->kelasSiswa()->firstOrFail();

        $siswa4 = Siswa::create(['nama' => 'Budi Siswa 4', 'nis' => '4001', 'nisn' => '04001', 'status' => 'aktif']);
        $ks4 = KelasSiswa::create(['kelas_id' => $kelas4->id, 'siswa_id' => $siswa4->id]);

        $admin = User::where('role', 'admin')->firstOrFail();

        Raport::create([
            'kelas_siswa_id' => $ks1->id,
            'periode' => 'sts',
            'tanggal_cetak' => now()->toDateString(),
            'file_path' => 'dummy1.docx',
            'dibuat_oleh' => $wali1->id,
        ]);

        Raport::create([
            'kelas_siswa_id' => $ks4->id,
            'periode' => 'sts',
            'tanggal_cetak' => now()->toDateString(),
            'file_path' => 'dummy2.docx',
            'dibuat_oleh' => $admin->id,
        ]);

        $response = $this->actingAs($wali1)->get(route('raport.riwayat'));
        $response->assertStatus(200);
        $response->assertSee($ks1->siswa->nama);
        $response->assertDontSee($ks4->siswa->nama);
    }

    public function test_absensi_harian_simpan_ignores_tampered_student_ids(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%1%')->firstOrFail();
        $kelas4 = Kelas::where('nama', 'like', '%4%')->firstOrFail();

        $wali1 = $kelas1->wali;
        $ks1 = $kelas1->kelasSiswa()->firstOrFail();

        $siswa4 = Siswa::create(['nama' => 'Budi Siswa 4', 'nis' => '4001', 'nisn' => '04001', 'status' => 'aktif']);
        $ks4 = KelasSiswa::create(['kelas_id' => $kelas4->id, 'siswa_id' => $siswa4->id]);

        $tanggal = now()->format('Y-m-d');

        // Submit form for kelas1, but maliciously include ks4 from kelas4
        $response = $this->actingAs($wali1)->post(route('kelas.absensi-harian.simpan', $kelas1->id), [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
            'status' => [
                $ks1->id => 'H',
                $ks4->id => 'A', // Should be ignored!
            ],
        ]);

        $response->assertRedirect();

        $this->assertNotNull(
            AbsensiHarian::where('kelas_siswa_id', $ks1->id)
                ->where('status', 'H')
                ->whereDate('tanggal', $tanggal)
                ->first()
        );

        // ks4 should not have an attendance record created
        $this->assertNull(
            AbsensiHarian::where('kelas_siswa_id', $ks4->id)
                ->whereDate('tanggal', $tanggal)
                ->first()
        );
    }
}
