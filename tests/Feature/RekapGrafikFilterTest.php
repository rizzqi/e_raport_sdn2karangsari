<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\NilaiSumatif;
use App\Models\Pembiasaan;
use App\Models\NilaiPembiasaan;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\HitungNilaiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RekapGrafikFilterTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_generate_chart_data_contains_all_akademik_sub_datasets(): void
    {
        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        $rekap = app(HitungNilaiService::class)->hitungRekapKelas($kelas->kelasSiswa, 'sas');
        $chartData = $rekap['chart_data'];

        // Pastikan root key ada
        $this->assertArrayHasKey('has_data', $chartData);
        $this->assertArrayHasKey('akademik', $chartData);
        $this->assertArrayHasKey('perbandingan', $chartData);
        $this->assertArrayHasKey('kehadiran', $chartData);
        $this->assertArrayHasKey('pembiasaan', $chartData);

        // Pastikan struktur akademik memuat seluruh jenis nilai
        $akademik = $chartData['akademik'];
        $this->assertArrayHasKey('rapor', $akademik);
        $this->assertArrayHasKey('uts', $akademik);
        $this->assertArrayHasKey('uas', $akademik);
        $this->assertArrayHasKey('formatif', $akademik);
        for ($b = 7; $b <= 12; $b++) {
            $this->assertArrayHasKey('formatif_' . $b, $akademik);
            $this->assertArrayHasKey('mapel', $akademik['formatif_' . $b]);
            $this->assertArrayHasKey('distribusi', $akademik['formatif_' . $b]);
            $this->assertArrayHasKey('rentang_nilai', $akademik['formatif_' . $b]);
            $this->assertArrayHasKey('summary', $akademik['formatif_' . $b]);
        }
    }

    public function test_perbandingan_dataset_structure_and_overall_metrics(): void
    {
        $kelas = Kelas::first();
        $rekap = app(HitungNilaiService::class)->hitungRekapKelas($kelas->kelasSiswa, 'sas');
        $chartData = $rekap['chart_data'];

        $pb = $chartData['perbandingan'];
        $this->assertArrayHasKey('labels', $pb);
        $this->assertArrayHasKey('full_names', $pb);
        $this->assertArrayHasKey('datasets', $pb);
        $this->assertArrayHasKey('formatif', $pb['datasets']);
        $this->assertArrayHasKey('uts', $pb['datasets']);
        $this->assertArrayHasKey('uas', $pb['datasets']);
        $this->assertArrayHasKey('rapor', $pb['datasets']);
        $this->assertArrayHasKey('overall', $pb);
        $this->assertArrayHasKey('formatif', $pb['overall']);
        $this->assertArrayHasKey('uts', $pb['overall']);
        $this->assertArrayHasKey('uas', $pb['overall']);
        $this->assertArrayHasKey('rapor', $pb['overall']);
    }

    public function test_kehadiran_sorted_by_alpa_desc_and_tren_bulanan(): void
    {
        $kelas = Kelas::first();
        $siswaList = $kelas->kelasSiswa()->take(3)->get();
        if ($siswaList->count() < 3) {
            $this->markTestSkipped('Butuh minimal 3 siswa');
        }

        // Setup kehadiran dengan variasi alpa
        Kehadiran::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[0]->id, 'periode' => 'sas'],
            ['sakit' => 1, 'izin' => 0, 'alpa' => 1]
        );
        Kehadiran::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[1]->id, 'periode' => 'sas'],
            ['sakit' => 0, 'izin' => 1, 'alpa' => 15] // Alpa tertinggi di kelas (> batas sekolah default 3)
        );
        Kehadiran::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[2]->id, 'periode' => 'sas'],
            ['sakit' => 2, 'izin' => 2, 'alpa' => 0]
        );

        // Setup kehadiran bulanan untuk tren
        KehadiranBulanan::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[0]->id, 'tahun' => 2026, 'bulan' => 7],
            ['sakit' => 1, 'izin' => 0, 'alpa' => 1]
        );
        KehadiranBulanan::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[1]->id, 'tahun' => 2026, 'bulan' => 7],
            ['sakit' => 0, 'izin' => 1, 'alpa' => 5]
        );
        KehadiranBulanan::updateOrCreate(
            ['kelas_siswa_id' => $siswaList[2]->id, 'tahun' => 2026, 'bulan' => 8],
            ['sakit' => 2, 'izin' => 2, 'alpa' => 0]
        );

        $rekap = app(HitungNilaiService::class)->hitungRekapKelas($kelas->kelasSiswa, 'sas');
        $chartData = $rekap['chart_data'];
        $kh = $chartData['kehadiran'];

        // Cek Tren Bulanan
        $this->assertArrayHasKey('tren_bulanan', $kh);
        $this->assertCount(6, $kh['tren_bulanan']['labels']); // Juli s.d. Desember
        $this->assertEquals(['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], $kh['tren_bulanan']['labels']);

        // Cek Bar Siswa diurutkan dari Alpa DESC
        $this->assertArrayHasKey('siswa_bar', $kh);
        $bars = $kh['siswa_bar'];
        $this->assertNotEmpty($bars);

        // Siswa pertama di urutan bar harus yang memiliki Alpa terbanyak (siswaList[1] dengan 15 alpa)
        $this->assertEquals($siswaList[1]->id, $bars[0]['id']);
        $this->assertEquals(15, $bars[0]['alpa']);
        $this->assertTrue($bars[0]['is_peringatan_alpa']);

        // Pastikan urutan alpa monoton turun (DESC)
        for ($i = 0; $i < count($bars) - 1; $i++) {
            $this->assertGreaterThanOrEqual($bars[$i + 1]['alpa'], $bars[$i]['alpa']);
        }
    }

    public function test_pembiasaan_donut_totals_and_siswa_perlu_bimbingan(): void
    {
        $kelas = Kelas::first();
        $pembiasaan = Pembiasaan::aktif()->first();
        $this->assertNotNull($pembiasaan);
        $ks = $kelas->kelasSiswa()->first();
        $this->assertNotNull($ks);

        // Beri nilai PB (Perlu Bimbingan) pada siswa pertama
        NilaiPembiasaan::updateOrCreate(
            ['kelas_siswa_id' => $ks->id, 'pembiasaan_id' => $pembiasaan->id, 'periode' => 'sas'],
            ['predikat' => 'Perlu Bimbingan']
        );

        $rekap = app(HitungNilaiService::class)->hitungRekapKelas($kelas->kelasSiswa, 'sas');
        $chartData = $rekap['chart_data'];
        $pem = $chartData['pembiasaan'];

        $this->assertArrayHasKey('totals', $pem);
        $this->assertGreaterThanOrEqual(1, $pem['totals']['perlu_bimbingan']);
        $this->assertArrayHasKey('siswa_perlu_bimbingan', $pem);
        $this->assertNotEmpty($pem['siswa_perlu_bimbingan']);

        $pbSiswa = collect($pem['siswa_perlu_bimbingan'])->firstWhere('id', $ks->siswa_id);
        $this->assertNotNull($pbSiswa);
        $this->assertContains($pembiasaan->nama, $pbSiswa['aspek']);
        $this->assertNotEmpty($pbSiswa['saran']);
    }

    public function test_rekap_page_renders_new_filters_tabs_and_chart_elements(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sas']));
        $response->assertStatus(200);

        // Tab baru Perbandingan
        $response->assertSee('Perbandingan Nilai');

        // Filter Bar Jenis Nilai
        $response->assertSee('Jenis Nilai:');
        $response->assertSee('Nilai Rapor (Akhir)');
        $response->assertSee('STS / UTS');
        $response->assertSee('Semesteran / UAS');
        $response->assertSee('Formatif Bulanan');
        $response->assertSee('selectBulanFormatif');

        // Canvas element ID
        $response->assertSee('id="chartPerbandingan"', false);
        $response->assertSee('id="chartKehadiranTren"', false);
        $response->assertSee('id="chartKehadiranSiswa"', false);
        $response->assertSee('id="chartPembiasaanDonut"', false);

        // KPI card element IDs
        $response->assertSee('id="kpi-mapel-tertinggi-nama"', false);
        $response->assertSee('id="kpi-mapel-terendah-nama"', false);
        $response->assertSee('id="kpi-siswa-tertinggi-nama"', false);
        $response->assertSee('id="kpi-tuntas-kkm-pct"', false);
        $response->assertSee('id="active-jenis-label"', false);

        // Box Monitoring Siswa Perlu Bimbingan
        $response->assertSee('Monitoring Intervensi Karakter');
    }
}
