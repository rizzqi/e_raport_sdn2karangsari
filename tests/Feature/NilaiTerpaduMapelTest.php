<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\Sekolah;
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

    public function test_halaman_lembar_nilai_rapor_bersifat_read_only_dan_memiliki_menu_input_nilai(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Lembar Nilai Rapor (Read-Only)');
        $response->assertSee('Input Nilai');
        $response->assertSee('Formatif Harian');
        $response->assertSee('Formatif Bulanan');
        $response->assertSee('STS (Sumatif Tengah Semester)');
        $response->assertSee('SAS (Sumatif Akhir Semester)');
        $response->assertSee('Input Catatan Guru');
        $response->assertDontSee('name="sts[', false);
        $response->assertDontSee('name="sas[', false);
        $response->assertDontSee('btnFokusSemua', false);
    }

    public function test_nilai_rapor_dan_predikat_menampilkan_tanda_strip_dan_badge_clickable_sebelum_lengkap(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();

        // Bersihkan nilai awal siswa agar hanya Formatif & STS yang terisi (SAS kosong)
        NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.formatif', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'formatif' => [$ks->id => [7 => 80]],
        ])->assertSessionHas('success');

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.uts', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'sts' => [$ks->id => 90],
        ])->assertSessionHas('success');

        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', now()->year)
            ->where('semester', 'ganjil')
            ->first();

        $this->assertNotNull($rapor);
        $this->assertEquals(80.0, (float) $rapor->rata_formatif);
        $this->assertEquals(90.0, (float) $rapor->nilai_uts);
        $this->assertNull($rapor->nilai_rapor);
        $this->assertNull($rapor->predikat);

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Perlu SAS');
        $response->assertSee('tab=sas', false);
        $response->assertSee('focus_ks='.$ks->id, false);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/id="rapor_val_'.$ks->id.'"[^>]*>–</u', $html);
        $this->assertMatchesRegularExpression('/id="predikat_badge_'.$ks->id.'"[^>]*>–</u', $html);
    }

    public function test_form_input_sts_dan_sas_terpisah_bekerja_dan_menghitung_nilai_rapor_otomatis(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();

        NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();

        $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'sts',
            'focus_ks' => $ks->id,
        ]))
            ->assertStatus(200)
            ->assertSee('Form Input Nilai STS')
            ->assertSee('name="sts['.$ks->id.']"', false);

        $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'sas',
            'focus_ks' => $ks->id,
        ]))
            ->assertStatus(200)
            ->assertSee('Form Input Nilai SAS')
            ->assertSee('name="sas['.$ks->id.']"', false);

        NilaiFormatifBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => now()->year,
            'bulan' => 7,
        ], ['nilai' => 80]);

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.uts', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'submit_mode' => 'stay',
            'sts' => [$ks->id => 90],
        ])->assertRedirect(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'sts',
            'tahun' => now()->year,
        ]));

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.uas', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'submit_mode' => 'kembali',
            'sas' => [$ks->id => 85],
        ])->assertRedirect(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'mapel',
            'tahun' => now()->year,
        ]));

        // (80 * 0.4) + (90 * 0.3) + (85 * 0.3) = 84.5 (Predikat B)
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', now()->year)
            ->where('semester', 'ganjil')
            ->first();

        $this->assertNotNull($rapor);
        $this->assertEquals(84.5, (float) $rapor->nilai_rapor);
        $this->assertEquals('B', $rapor->predikat);
    }

    public function test_pengaturan_bobot_dinamis_memvalidasi_total_100_dan_menghitung_ulang_nilai_rapor(): void
    {
        $admin = User::where('role', 'admin')->first();
        $sekolah = Sekolah::first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();

        // 1. Validasi gagal jika total bobot != 100
        $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda,
            'dinas' => $sekolah->dinas,
            'kota' => $sekolah->kota,
            'bobot_formatif' => 50,
            'bobot_uts' => 30,
            'bobot_uas' => 30,
        ])->assertSessionHasErrors('bobot_total');

        // 2. Buat data lengkap terlebih dahulu (80 / 90 / 60 -> 40/30/30 = 77.0)
        NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.simpan-terpadu', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'sts' => [$ks->id => 90],
            'sas' => [$ks->id => 60],
        ])->assertSessionHas('success');

        NilaiFormatifBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => now()->year,
            'bulan' => 7,
        ], ['nilai' => 80]);

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.uts', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'sts' => [$ks->id => 90],
        ])->assertSessionHas('success');

        // 3. Ubah bobot menjadi 50 / 25 / 25
        $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda,
            'dinas' => $sekolah->dinas,
            'kota' => $sekolah->kota,
            'bobot_formatif' => 50,
            'bobot_uts' => 25,
            'bobot_uas' => 25,
        ])->assertSessionHas('success');

        // Rumus baru: (80 * 0.5) + (90 * 0.25) + (60 * 0.25) = 40 + 22.5 + 15 = 77.5
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', now()->year)
            ->where('semester', 'ganjil')
            ->first();

        $this->assertNotNull($rapor);
        $this->assertEquals(77.5, (float) $rapor->nilai_rapor);

        $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]))
            ->assertStatus(200)
            ->assertSee('Formatif (50%)')
            ->assertSee('STS (25%)')
            ->assertSee('SAS (25%)');
    }

    public function test_semua_empat_format_cetak_pdf_berhasil_diakses(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();

        // 1a. Cetak Formatif Per Bulan
        $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-formatif-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'bulan' => 8,
        ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 1b. Cetak Formatif 1 Semester
        $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-formatif-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'mode' => 'semester',
        ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 2. Cetak Daftar Nilai STS
        $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-uts-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => now()->year,
            'semester' => 'ganjil',
        ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 3. Cetak Daftar Nilai SAS
        $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-uas-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => now()->year,
            'semester' => 'ganjil',
        ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');

        // 4. Cetak Lembar Nilai Rapor Mata Pelajaran
        $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-rapor-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => now()->year,
            'semester' => 'ganjil',
        ]))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }
}
