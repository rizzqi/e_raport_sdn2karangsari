<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DaftarHadirTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_view_daftar_hadir_sheet_for_class(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.daftar-hadir', ['kelas' => $kelas->id, 'periode' => 'sts']));

        $response->assertStatus(200);
        $response->assertSee('DAFTAR HADIR ASESMEN / SUMATIF TENGAH SEMESTER (STS)');
        $response->assertSee($kelas->nama);
        $response->assertSee('REKAPITULASI KEHADIRAN');
    }

    public function test_user_can_download_daftar_hadir_excel_for_class(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.daftar-hadir.excel', ['kelas' => $kelas->id, 'periode' => 'sts']));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');
        $this->assertStringContainsString('DAFTAR HADIR ASESMEN / SUMATIF TENGAH SEMESTER (STS)', $response->getContent());
        $this->assertStringContainsString($kelas->nama, $response->getContent());
    }

    public function test_user_can_view_daftar_hadir_sheet_with_custom_exam_identity(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.daftar-hadir', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
            'mapel' => 'Matematika',
            'ruang' => 'Ruang 02',
            'tanggal' => 'Senin, 20 Oktober 2026',
            'waktu' => '07.30 - 09.00 WIB',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Matematika');
        $response->assertSee('Ruang 02');
        $response->assertSee('Senin, 20 Oktober 2026');
        $response->assertSee('07.30 - 09.00 WIB');
    }

    public function test_user_can_download_daftar_hadir_excel_with_custom_exam_identity(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.daftar-hadir.excel', [
            'kelas' => $kelas->id,
            'periode' => 'sas',
            'mapel' => 'Pendidikan Pancasila',
            'ruang' => 'Ruang 01',
            'tanggal' => 'Selasa, 21 Oktober 2026',
            'waktu' => '09.30 - 11.00 WIB',
        ]));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringContainsString('Pendidikan Pancasila', $content);
        $this->assertStringContainsString('Ruang 01', $content);
        $this->assertStringContainsString('Selasa, 21 Oktober 2026', $content);
        $this->assertStringContainsString('09.30 - 11.00 WIB', $content);
        $this->assertStringContainsString('Daftar_Hadir_SAS_', $response->headers->get('content-disposition'));
    }
}
