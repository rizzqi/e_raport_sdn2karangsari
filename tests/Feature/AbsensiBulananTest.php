<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AbsensiBulananTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_access_absensi_bulanan_page(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.absensi.index', [
            'kelas' => $kelas->id,
            'bulan' => 8, // Agustus
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Presensi / Absensi Bulanan');
        $response->assertSee('Agustus');
        $response->assertSee($kelas->nama);
    }

    public function test_user_can_save_absensi_bulanan_and_auto_sync_to_raport(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa->first();

        $response = $this->actingAs($admin)->post(route('kelas.absensi.simpan', $kelas->id), [
            'tahun' => 2026,
            'bulan' => 8, // Agustus (Semester 1)
            'sakit' => [$ks->id => 2],
            'izin' => [$ks->id => 1],
            'alpa' => [$ks->id => 0],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi tabel kehadiran_bulanan
        $bulanan = KehadiranBulanan::where('kelas_siswa_id', $ks->id)
            ->where('tahun', 2026)
            ->where('bulan', 8)
            ->first();

        $this->assertNotNull($bulanan);
        $this->assertEquals(2, $bulanan->sakit);
        $this->assertEquals(1, $bulanan->izin);
        $this->assertEquals(0, $bulanan->alpa);

        // Verifikasi otomatis tersinkron ke kehadiran raport (STS & SAS)
        $kehadiranSts = Kehadiran::where('kelas_siswa_id', $ks->id)
            ->where('periode', 'sts')
            ->first();

        $this->assertNotNull($kehadiranSts);
        $this->assertEquals(2, $kehadiranSts->sakit);
        $this->assertEquals(1, $kehadiranSts->izin);
    }

    public function test_user_can_view_printable_absensi_sheet(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.absensi.cetak', [
            'kelas' => $kelas->id,
            'bulan' => 10,
            'tahun' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertSee('REKAPITULASI PRESENSI / ABSENSI PESERTA DIDIK');
        $response->assertSee('OKTOBER');
        $response->assertSee($kelas->nama);
    }
}
