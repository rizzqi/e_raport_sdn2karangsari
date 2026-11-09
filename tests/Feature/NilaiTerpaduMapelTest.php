<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NilaiTerpaduMapelTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_input_nilai_terpadu_dapat_diakses_dan_menampilkan_kolom_yang_benar(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Formatif (40%)');
        $response->assertSee('STS (30%)');
        $response->assertSee('SAS (30%)');
        $response->assertSee('Nilai Rapor');
        $response->assertSee('Predikat');
        $response->assertSee('Catatan Guru');
        $response->assertSee('Pembiasaan');
        $response->assertSee('Kehadiran');
    }

    public function test_simpan_nilai_terpadu_menghitung_rapor_dan_predikat_secara_otomatis(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();

        // 1. Simpan formatif bulanan rata-rata 80 (40%)
        NilaiFormatifBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => now()->year,
            'bulan' => 7,
        ], [
            'nilai' => 80,
        ]);

        // 2. Guru simpan STS = 90 (30%) dan SAS = 85 (30%) serta catatan
        $response = $this->actingAs($admin)->post(route('kelas.mapel.nilai.simpan-terpadu', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'sts' => [$ks->id => 90],
            'sas' => [$ks->id => 85],
            'catatan' => [$ks->id => 'Menunjukkan pemahaman yang sangat baik dalam materi pembelajaran.'],
        ]);

        $response->assertSessionHas('success');

        // Pastikan tabel Nilai terisi
        $nilaiSts = Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->where('periode', 'sts')->first();
        $this->assertNotNull($nilaiSts);
        $this->assertEquals(90, (float) $nilaiSts->sumatif);

        $nilaiSas = Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->where('periode', 'sas')->first();
        $this->assertNotNull($nilaiSas);
        $this->assertEquals(85, (float) $nilaiSas->sumatif);

        // Rumus: (80 * 0.4) + (90 * 0.3) + (85 * 0.3) = 32 + 27 + 25.5 = 84.5
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->first();
        $this->assertNotNull($rapor);
        $this->assertEquals(80.0, (float) $rapor->rata_formatif);
        $this->assertEquals(90.0, (float) $rapor->nilai_uts);
        $this->assertEquals(85.0, (float) $rapor->nilai_uas);
        $this->assertEquals(84.5, (float) $rapor->nilai_rapor);
        $this->assertEquals('B', $rapor->predikat);
        $this->assertEquals('Menunjukkan pemahaman yang sangat baik dalam materi pembelajaran.', $rapor->catatan);
    }

    public function test_simpan_catatan_guru_via_ajax(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();

        $response = $this->actingAs($admin)->postJson(route('kelas.mapel.nilai.simpan-catatan-ajax', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'kelas_siswa_id' => $ks->id,
            'catatan' => 'Catatan langsung via modal ajax.',
            'tahun' => now()->year,
            'semester' => 'ganjil',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'catatan' => 'Catatan langsung via modal ajax.',
        ]);

        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->first();
        $this->assertNotNull($rapor);
        $this->assertEquals('Catatan langsung via modal ajax.', $rapor->catatan);
    }

    public function test_tab_pembiasaan_dan_kehadiran_terintegrasi_di_halaman_yang_sama(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'pembiasaan',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Penilaian Pembiasaan Karakter');
        $response->assertSee('SIA');
        $response->assertSee('Sakit (S)');
        $response->assertSee('Izin (I)');
        $response->assertSee('Alpa (A)');
        $response->assertSee('Isi semua Baik');
    }

    public function test_tombol_input_nilai_mengarahkan_wali_kelas_langsung_ke_kelas_binaannya(): void
    {
        $wali = User::where('role', 'wali')->first();
        $kelas = Kelas::where('wali_id', $wali->id)->first();
        if (! $kelas) {
            $kelas = Kelas::first();
            $kelas->update(['wali_id' => $wali->id]);
        }

        $response = $this->actingAs($wali)->get(route('input-nilai'));

        $response->assertRedirect(route('kelas.input-nilai', $kelas->id));
    }

    public function test_tombol_input_nilai_mengarahkan_admin_ke_kelas_pertama(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::orderBy('nama')->first();

        $response = $this->actingAs($admin)->get(route('input-nilai'));

        $response->assertRedirect(route('kelas.input-nilai', $kelas->id));
    }

    public function test_halaman_input_nilai_menampilkan_status_kelengkapan_dan_filter_belum_lengkap(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Kelengkapan Nilai Siswa');
        $response->assertSee('Belum Lengkap');
        $response->assertSee('Lengkap');
    }
}
