<?php

namespace Tests\Feature;

use App\Models\AbsensiHarian;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AbsensiHarianTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_akses_hub_absensi_harian_menampilkan_statistik_dan_kelas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $response = $this->actingAs($admin)->get(route('absensi-harian.index'));

        $response->assertStatus(200);
        $response->assertSee('Pusat Absensi Harian');
        $response->assertSee('Wali Kelas');
        $response->assertSee('Pendidikan Agama');
        $response->assertSee('PJOK');
    }

    public function test_wali_kelas_dapat_menginput_absensi_harian_umum_dan_tersimpan(): void
    {
        $kelas = Kelas::first();
        $wali = $kelas->wali ?? User::where('role', 'admin')->first();
        $ksList = $kelas->kelasSiswa()->take(3)->get();
        $this->assertNotEmpty($ksList);

        $tanggal = now()->format('Y-m-d');

        // Status untuk 3 siswa: Siswa 0 = H, Siswa 1 = S, Siswa 2 = I
        $statusInput = [
            $ksList[0]->id => 'H',
            $ksList[1]->id => 'S',
            $ksList[2]->id => 'I',
        ];
        $ketInput = [
            $ksList[1]->id => 'Demam flu',
            $ksList[2]->id => 'Acara keluarga',
        ];

        $response = $this->actingAs($wali)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
            'status' => $statusInput,
            'keterangan' => $ketInput,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan tersimpan dengan mapel_id NULL (absen umum Wali Kelas)
        $absen0 = AbsensiHarian::where('kelas_siswa_id', $ksList[0]->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
        $this->assertNotNull($absen0);
        $this->assertNull($absen0->mapel_id);
        $this->assertEquals('H', $absen0->status);

        $absen1 = AbsensiHarian::where('kelas_siswa_id', $ksList[1]->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
        $this->assertNotNull($absen1);
        $this->assertNull($absen1->mapel_id);
        $this->assertEquals('S', $absen1->status);
        $this->assertEquals('Demam flu', $absen1->keterangan);

        $absen2 = AbsensiHarian::where('kelas_siswa_id', $ksList[2]->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
        $this->assertNotNull($absen2);
        $this->assertNull($absen2->mapel_id);
        $this->assertEquals('I', $absen2->status);
        $this->assertEquals('Acara keluarga', $absen2->keterangan);
    }

    public function test_guru_agama_dapat_menginput_absensi_harian_mapel_agama(): void
    {
        $kelas = Kelas::first();
        $admin = User::where('role', 'admin')->first();
        $mapelAgama = $kelas->getMapelAgama();
        $this->assertNotNull($mapelAgama);

        $ks = $kelas->kelasSiswa()->first();
        $tanggal = now()->format('Y-m-d');

        $response = $this->actingAs($admin)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'agama',
            'status' => [$ks->id => 'H'],
            'keterangan' => [$ks->id => 'Aktif membaca iqro'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absenAgama = AbsensiHarian::where('kelas_siswa_id', $ks->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
        $this->assertNotNull($absenAgama);
        $this->assertEquals($mapelAgama->id, $absenAgama->mapel_id);
        $this->assertEquals('H', $absenAgama->status);
        $this->assertEquals('Aktif membaca iqro', $absenAgama->keterangan);
    }

    public function test_guru_pjok_dapat_menginput_absensi_harian_mapel_pjok_dengan_catatan_fisik(): void
    {
        $kelas = Kelas::first();
        $admin = User::where('role', 'admin')->first();
        $mapelPjok = $kelas->getMapelPjok();
        $this->assertNotNull($mapelPjok);

        $ks = $kelas->kelasSiswa()->first();
        $tanggal = now()->format('Y-m-d');

        $response = $this->actingAs($admin)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'pjok',
            'status' => [$ks->id => 'S'],
            'keterangan' => [$ks->id => 'Cedera kaki terkilir saat pemanasan'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $absenPjok = AbsensiHarian::where('kelas_siswa_id', $ks->id)
            ->whereDate('tanggal', $tanggal)
            ->first();
        $this->assertNotNull($absenPjok);
        $this->assertEquals($mapelPjok->id, $absenPjok->mapel_id);
        $this->assertEquals('S', $absenPjok->status);
        $this->assertEquals('Cedera kaki terkilir saat pemanasan', $absenPjok->keterangan);
    }

    public function test_sinkronisasi_absensi_harian_ke_kehadiran_bulanan_berhasil(): void
    {
        $kelas = Kelas::first();
        $admin = User::where('role', 'admin')->first();
        $ks = $kelas->kelasSiswa()->first();

        $tahun = 2026;
        $bulan = 10;

        // Buat absensi harian umum untuk bulan Oktober 2026: 2 Sakit, 1 Izin, 1 Alpa
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => '2026-10-05',
            'status' => 'S',
            'created_by' => $admin->id,
        ]);
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => '2026-10-06',
            'status' => 'S',
            'created_by' => $admin->id,
        ]);
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => '2026-10-12',
            'status' => 'I',
            'created_by' => $admin->id,
        ]);
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => '2026-10-19',
            'status' => 'A',
            'created_by' => $admin->id,
        ]);

        // Trigger sinkronisasi
        $response = $this->actingAs($admin)->post(route('kelas.absensi-harian.sync-bulanan', $kelas->id), [
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Pastikan KehadiranBulanan terisi sesuai akumulasi (S=2, I=1, A=1)
        $this->assertDatabaseHas('kehadiran_bulanan', [
            'kelas_siswa_id' => $ks->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 1,
        ]);
    }

    public function test_halaman_cetak_harian_dan_bulanan_dapat_diakses(): void
    {
        $kelas = Kelas::first();
        $admin = User::where('role', 'admin')->first();
        $tanggal = now()->format('Y-m-d');

        // Cetak harian
        $respHarian = $this->actingAs($admin)->get(route('kelas.absensi-harian.cetak', [
            'kelas' => $kelas->id,
            'tanggal' => $tanggal,
            'mode' => 'harian',
            'tipe' => 'wali',
        ]));
        $respHarian->assertStatus(200);
        $respHarian->assertSee('DAFTAR PRESENSI HARIAN PESERTA DIDIK');

        // Cetak bulanan
        $respBulanan = $this->actingAs($admin)->get(route('kelas.absensi-harian.cetak', [
            'kelas' => $kelas->id,
            'tanggal' => $tanggal,
            'mode' => 'bulanan',
            'tipe' => 'wali',
        ]));
        $respBulanan->assertStatus(200);
        $respBulanan->assertSee('LEGER REKAPITULASI PRESENSI HARIAN PESERTA DIDIK');
    }
}
