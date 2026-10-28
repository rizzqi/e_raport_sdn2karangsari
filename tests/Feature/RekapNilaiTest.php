<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Siswa;
use App\Models\User;
use App\Services\HitungNilaiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RekapNilaiTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_rekap_page_displays_mapel_names_and_peringkat_column(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        $response = $this->actingAs($admin)->get(route('rekap.kelas', $kelas->id));

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi Nilai');
        $response->assertSee('Ranking');
        $response->assertSee('Ranking Tertinggi');

        // Pastikan nama mapel muncul di tabel, bukan hanya kode M1 / M2
        $mapels = $kelas->mataPelajaran();
        foreach ($mapels as $m) {
            $response->assertSee($m->nama);
        }
    }

    public function test_hitung_rekap_kelas_calculates_ranking_from_highest_to_lowest(): void
    {
        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        // Bersihkan siswa kelas yang sudah ada untuk pengujian murni
        KelasSiswa::where('kelas_id', $kelas->id)->delete();

        $siswa1 = Siswa::create(['nama' => 'Budi Juara', 'nis' => '99001', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $siswa2 = Siswa::create(['nama' => 'Andi Sedang', 'nis' => '99002', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $siswa3 = Siswa::create(['nama' => 'Candra Terendah', 'nis' => '99003', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $siswa4 = Siswa::create(['nama' => 'Dedi Kosong', 'nis' => '99004', 'jenis_kelamin' => 'L', 'status' => 'aktif']);

        $ks1 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa1->id]);
        $ks2 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa2->id]);
        $ks3 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa3->id]);
        $ks4 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa4->id]);

        $mapel = MataPelajaran::first();
        $this->assertNotNull($mapel);

        // Siswa 1: Nilai 95
        Nilai::create([
            'kelas_siswa_id' => $ks1->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 95,
        ]);

        // Siswa 2: Nilai 85
        Nilai::create([
            'kelas_siswa_id' => $ks2->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 85,
        ]);

        // Siswa 3: Nilai 70
        Nilai::create([
            'kelas_siswa_id' => $ks3->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 70,
        ]);

        // Siswa 4 sengaja tidak diberi nilai (kosong)

        $service = app(HitungNilaiService::class);
        $ksList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekap = $service->hitungRekapKelas($ksList, 'sts', 'ranking');

        $this->assertCount(4, $rekap['siswa_list']);

        // Urutan pertama harus Budi Juara dengan peringkat 1
        $this->assertEquals('Budi Juara', $rekap['siswa_list'][0]['siswa']->nama);
        $this->assertEquals(1, $rekap['siswa_list'][0]['peringkat']);

        // Urutan kedua harus Andi Sedang dengan peringkat 2
        $this->assertEquals('Andi Sedang', $rekap['siswa_list'][1]['siswa']->nama);
        $this->assertEquals(2, $rekap['siswa_list'][1]['peringkat']);

        // Urutan ketiga harus Candra Terendah dengan peringkat 3
        $this->assertEquals('Candra Terendah', $rekap['siswa_list'][2]['siswa']->nama);
        $this->assertEquals(3, $rekap['siswa_list'][2]['peringkat']);

        // Urutan keempat harus Dedi Kosong dengan peringkat 4 (ranking 1 sampai terakhir)
        $this->assertEquals('Dedi Kosong', $rekap['siswa_list'][3]['siswa']->nama);
        $this->assertEquals(4, $rekap['siswa_list'][3]['peringkat']);
    }

    public function test_rekap_sort_by_nama_preserves_calculated_ranking(): void
    {
        $kelas = Kelas::first();
        KelasSiswa::where('kelas_id', $kelas->id)->delete();

        $siswaZ = Siswa::create(['nama' => 'Zainal Pintar', 'nis' => '99005', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $siswaA = Siswa::create(['nama' => 'Anton Biasa', 'nis' => '99006', 'jenis_kelamin' => 'L', 'status' => 'aktif']);

        $ksZ = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswaZ->id]);
        $ksA = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswaA->id]);

        $mapel = MataPelajaran::first();

        // Zainal mendapat 90 (juara 1)
        Nilai::create([
            'kelas_siswa_id' => $ksZ->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 90,
        ]);

        // Anton mendapat 75 (juara 2)
        Nilai::create([
            'kelas_siswa_id' => $ksA->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 75,
        ]);

        $service = app(HitungNilaiService::class);
        $ksList = $kelas->kelasSiswa()->with('siswa')->get();

        // Sort by nama
        $rekapNama = $service->hitungRekapKelas($ksList, 'sts', 'nama');

        // Anton Biasa (A) harus di posisi pertama karena sort nama
        $this->assertEquals('Anton Biasa', $rekapNama['siswa_list'][0]['siswa']->nama);
        // Namun peringkatnya tetap 2
        $this->assertEquals(2, $rekapNama['siswa_list'][0]['peringkat']);

        // Zainal Pintar (Z) di posisi kedua
        $this->assertEquals('Zainal Pintar', $rekapNama['siswa_list'][1]['siswa']->nama);
        // Namun peringkatnya tetap 1
        $this->assertEquals(1, $rekapNama['siswa_list'][1]['peringkat']);
    }

    public function test_rekap_page_uses_saved_and_custom_date(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        // 1. Saved date
        $kelas->update(['tanggal_raport_sts' => '2024-10-18']);
        $respSaved = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $respSaved->assertStatus(200);
        $respSaved->assertSee('18 Oktober 2024');

        // 2. Query param ?tanggal= overrides
        $respOverride = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts', 'tanggal' => '2024-10-25']));
        $respOverride->assertStatus(200);
        $respOverride->assertSee('25 Oktober 2024');
    }

    public function test_rekap_data_generates_accurate_chart_data(): void
    {
        $kelas = Kelas::first();
        KelasSiswa::where('kelas_id', $kelas->id)->delete();

        $siswa1 = Siswa::create(['nama' => 'Budi Hebat', 'nis' => '99101', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $siswa2 = Siswa::create(['nama' => 'Ani Rajin', 'nis' => '99102', 'jenis_kelamin' => 'P', 'status' => 'aktif']);

        $ks1 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa1->id]);
        $ks2 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $siswa2->id]);

        $mapel = MataPelajaran::first();

        // Budi: 90 (Kategori Sangat Baik)
        Nilai::create([
            'kelas_siswa_id' => $ks1->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 90,
        ]);

        // Ani: 76 (Kategori Baik)
        Nilai::create([
            'kelas_siswa_id' => $ks2->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
            'sumatif' => 76,
        ]);

        // Kehadiran: Budi (Sakit 2, Izin 1), Ani (Izin 3, Alpa 1)
        Kehadiran::create([
            'kelas_siswa_id' => $ks1->id,
            'periode' => 'sts',
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 0,
        ]);
        Kehadiran::create([
            'kelas_siswa_id' => $ks2->id,
            'periode' => 'sts',
            'sakit' => 0,
            'izin' => 3,
            'alpa' => 1,
        ]);

        // Pembiasaan: Berdoa Sebelum Belajar
        $pembiasaan = Pembiasaan::firstOrCreate(
            ['nama' => 'Berdoa Sebelum Belajar'],
            ['urutan' => 1, 'aktif' => true]
        );
        NilaiPembiasaan::create([
            'kelas_siswa_id' => $ks1->id,
            'pembiasaan_id' => $pembiasaan->id,
            'periode' => 'sts',
            'predikat' => 'Sangat Baik',
        ]);
        NilaiPembiasaan::create([
            'kelas_siswa_id' => $ks2->id,
            'pembiasaan_id' => $pembiasaan->id,
            'periode' => 'sts',
            'predikat' => 'Baik',
        ]);

        $service = app(HitungNilaiService::class);
        $ksList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekap = $service->hitungRekapKelas($ksList, 'sts', 'ranking');

        $this->assertArrayHasKey('chart_data', $rekap);
        $chartData = $rekap['chart_data'];

        $this->assertTrue($chartData['has_data']);
        $this->assertArrayHasKey('mapel', $chartData);
        $this->assertArrayHasKey('distribusi', $chartData);
        $this->assertArrayHasKey('ranking', $chartData);
        $this->assertArrayHasKey('rentang_nilai', $chartData);
        $this->assertArrayHasKey('kehadiran', $chartData);
        $this->assertArrayHasKey('pembiasaan', $chartData);
        $this->assertArrayHasKey('summary', $chartData);

        // Rentang Nilai: Budi (90), Ani (76) keduanya berada di rentang 76 - 100
        $this->assertEquals(['0 - 25', '26 - 50', '51 - 75', '76 - 100'], $chartData['rentang_nilai']['labels']);
        $this->assertEquals(0, $chartData['rentang_nilai']['counts'][0]); // 0 - 25
        $this->assertEquals(0, $chartData['rentang_nilai']['counts'][1]); // 26 - 50
        $this->assertEquals(0, $chartData['rentang_nilai']['counts'][2]); // 51 - 75
        $this->assertEquals(2, $chartData['rentang_nilai']['counts'][3]); // 76 - 100
        $this->assertEquals(100.0, $chartData['rentang_nilai']['percentages'][3]);

        // Mapel data
        $this->assertContains($mapel->nama_singkat, $chartData['mapel']['labels']);

        // Distribusi: 1 sangat baik (>=85) dan 1 baik (75-84)
        $this->assertEquals(1, $chartData['distribusi']['counts'][0]); // Sangat Baik
        $this->assertEquals(1, $chartData['distribusi']['counts'][1]); // Baik
        $this->assertEquals(2, $chartData['distribusi']['total_dinilai']);

        // Kehadiran: Sakit 2, Izin 4, Alpa 1, Total 7
        $this->assertEquals(2, $chartData['kehadiran']['total_sakit']);
        $this->assertEquals(4, $chartData['kehadiran']['total_izin']);
        $this->assertEquals(1, $chartData['kehadiran']['total_alpa']);
        $this->assertEquals(7, $chartData['kehadiran']['total_tidak_hadir']);

        // Pembiasaan
        $this->assertContains('Berdoa Sebelum Belajar', $chartData['pembiasaan']['labels']);
        $pIndex = array_search('Berdoa Sebelum Belajar', $chartData['pembiasaan']['labels']);
        $this->assertEquals(1, $chartData['pembiasaan']['datasets']['sangat_baik'][$pIndex]);
        $this->assertEquals(1, $chartData['pembiasaan']['datasets']['baik'][$pIndex]);

        // Ranking
        $this->assertCount(2, $chartData['ranking']);
        $this->assertEquals('Budi Hebat', $chartData['ranking'][0]['nama']);
        $this->assertEquals(1, $chartData['ranking'][0]['peringkat']);

        // Summary
        $this->assertEquals('Budi Hebat', $chartData['summary']['siswa_tertinggi']['nama']);
    }

    public function test_rekap_page_renders_chart_elements_and_canvas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));

        $response->assertStatus(200);
        $response->assertSee('Visualisasi & Analisis Grafik Nilai');
        $response->assertSee('section-grafik');
        $response->assertSee('chartMapel');
        $response->assertSee('chartDistribusi');
        $response->assertSee('chartRanking');
        $response->assertSee('chartKehadiran');
        $response->assertSee('chartPembiasaan');
        $response->assertSee('Performa Mapel vs KKM');
        $response->assertSee('Distribusi Capaian Siswa');
        $response->assertSee('Sebaran Capaian Siswa (Rentang Nilai)');
        $response->assertSee('Rentang Nilai');
        $response->assertSee('0 - 25');
        $response->assertSee('26 - 50');
        $response->assertSee('51 - 75');
        $response->assertSee('76 - 100');
        $response->assertSee('Kehadiran (SIA)');
        $response->assertSee('Pembiasaan');
    }

    public function test_rekap_chart_handles_empty_class_gracefully(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        KelasSiswa::where('kelas_id', $kelas->id)->delete();

        $service = app(HitungNilaiService::class);
        $rekap = $service->hitungRekapKelas(collect(), 'sts');

        $this->assertFalse($rekap['chart_data']['has_data']);

        $response = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $response->assertStatus(200);
        $response->assertSee('Grafik Belum Tersedia');
    }

    public function test_rekap_page_displays_class_switcher_for_quick_navigation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas1 = Kelas::first();

        $response = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas1->id, 'periode' => 'sts']));

        $response->assertStatus(200);
        $response->assertViewHas('kelasList');
        $response->assertSee('Pilih Kelas untuk Visualisasi Grafik:');
        $response->assertSee($kelas1->nama);
    }

    public function test_rekap_chart_calculates_score_ranges_distribution_per_class(): void
    {
        $kelas = Kelas::first();
        KelasSiswa::where('kelas_id', $kelas->id)->delete();

        $s1 = Siswa::create(['nama' => 'Siswa Rendah', 'nis' => '88001', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $s2 = Siswa::create(['nama' => 'Siswa Bimbingan', 'nis' => '88002', 'jenis_kelamin' => 'P', 'status' => 'aktif']);
        $s3 = Siswa::create(['nama' => 'Siswa Cukup', 'nis' => '88003', 'jenis_kelamin' => 'L', 'status' => 'aktif']);
        $s4 = Siswa::create(['nama' => 'Siswa Mahir', 'nis' => '88004', 'jenis_kelamin' => 'P', 'status' => 'aktif']);

        $ks1 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $s1->id]);
        $ks2 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $s2->id]);
        $ks3 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $s3->id]);
        $ks4 = KelasSiswa::create(['kelas_id' => $kelas->id, 'siswa_id' => $s4->id]);

        $mapel = MataPelajaran::first();

        Nilai::create(['kelas_siswa_id' => $ks1->id, 'mapel_id' => $mapel->id, 'periode' => 'sts', 'sumatif' => 20]);
        Nilai::create(['kelas_siswa_id' => $ks2->id, 'mapel_id' => $mapel->id, 'periode' => 'sts', 'sumatif' => 45]);
        Nilai::create(['kelas_siswa_id' => $ks3->id, 'mapel_id' => $mapel->id, 'periode' => 'sts', 'sumatif' => 70]);
        Nilai::create(['kelas_siswa_id' => $ks4->id, 'mapel_id' => $mapel->id, 'periode' => 'sts', 'sumatif' => 95]);

        $service = app(HitungNilaiService::class);
        $ksList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekap = $service->hitungRekapKelas($ksList, 'sts', 'ranking');

        $rentang = $rekap['chart_data']['rentang_nilai'];
        $this->assertEquals(['0 - 25', '26 - 50', '51 - 75', '76 - 100'], $rentang['labels']);
        $this->assertEquals([1, 1, 1, 1], $rentang['counts']);
        $this->assertEquals([25.0, 25.0, 25.0, 25.0], $rentang['percentages']);

        $this->assertEquals('Siswa Rendah', $rentang['siswa'][0][0]['nama']);
        $this->assertEquals('Siswa Bimbingan', $rentang['siswa'][1][0]['nama']);
        $this->assertEquals('Siswa Cukup', $rentang['siswa'][2][0]['nama']);
        $this->assertEquals('Siswa Mahir', $rentang['siswa'][3][0]['nama']);

        // Uji rincian sebaran per mata pelajaran
        $this->assertArrayHasKey('per_mapel', $rentang);
        $this->assertArrayHasKey($mapel->id, $rentang['per_mapel']);
        $mapelRentang = $rentang['per_mapel'][$mapel->id];
        $this->assertEquals([1, 1, 1, 1], $mapelRentang['counts']);
        $this->assertEquals([25.0, 25.0, 25.0, 25.0], $mapelRentang['percentages']);
        $this->assertEquals(4, $mapelRentang['total_dinilai']);
        $this->assertEquals('Siswa Rendah', $mapelRentang['siswa'][0][0]['nama']);
        $this->assertEquals('Siswa Mahir', $mapelRentang['siswa'][3][0]['nama']);

        $this->assertArrayHasKey('datasets_by_mapel', $rentang);
        $this->assertArrayHasKey('kumulatif_mapel', $rentang);
        $this->assertEquals([1, 1, 1, 1], $rentang['kumulatif_mapel']['counts']);
    }

    public function test_rekap_page_renders_mapel_filter_for_score_ranges(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));

        $response->assertStatus(200);
        $response->assertSee('selectMapelRentang');
        $response->assertSee('Semua Mapel (Grafik Bertumpuk)');
        $response->assertSee('Rata-rata Nilai Akhir Siswa');
    }

    public function test_cetak_rekap_semester_page_renders_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']));

        $response->assertStatus(200);
        $response->assertSee('REKAPITULASI NILAI SEMESTER');
        $response->assertSee('Rata-rata Otomatis');
        $response->assertSee($kelas->nama);
    }

    public function test_cetak_rekap_bulanan_page_renders_successfully(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.nilai-formatif.cetak-rekap', ['kelas' => $kelas->id, 'bulan' => 10]));

        $response->assertStatus(200);
        $response->assertSee('REKAPITULASI NILAI BULANAN (FORMATIF)');
        $response->assertSee('Rata-rata Otomatis');
        $response->assertSee($kelas->nama);
    }
}
