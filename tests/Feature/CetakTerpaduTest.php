<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CetakTerpaduTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pengaturan_sekolah_dapat_mengubah_default_kertas_a4_f4_orientasi_dan_margin(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $sekolah = Sekolah::firstOrFail();

        // Default awal sesuai konfigurasi baru adalah A4 Portrait Normal
        $defaultConfig = $sekolah->resolvePrintConfig();
        $this->assertSame('a4', $defaultConfig['paper']);
        $this->assertSame('portrait', $defaultConfig['orientation']);
        $this->assertSame('normal', $defaultConfig['margin']);
        $this->assertSame([0.0, 0.0, 595.28, 841.89], $defaultConfig['dompdf_paper']);

        // Validasi menolak ukuran kertas di luar A4/F4
        $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda,
            'dinas' => $sekolah->dinas,
            'kota' => $sekolah->kota,
            'bobot_formatif' => 40,
            'bobot_uts' => 30,
            'bobot_uas' => 30,
            'default_paper_size' => 'legal',
            'default_paper_orientation' => 'portrait',
            'default_margin_preset' => 'normal',
        ])->assertSessionHasErrors('default_paper_size');

        // Simpan default baru: F4 Landscape Sempit
        $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda,
            'dinas' => $sekolah->dinas,
            'kota' => $sekolah->kota,
            'bobot_formatif' => 40,
            'bobot_uts' => 30,
            'bobot_uas' => 30,
            'default_paper_size' => 'f4',
            'default_paper_orientation' => 'landscape',
            'default_margin_preset' => 'sempit',
        ])->assertSessionHas('success');

        $sekolah->refresh();
        $this->assertSame('f4', $sekolah->default_paper_size);
        $this->assertSame('landscape', $sekolah->default_paper_orientation);
        $this->assertSame('sempit', $sekolah->default_margin_preset);

        $f4Config = $sekolah->resolvePrintConfig();
        $this->assertSame([0.0, 0.0, 609.45, 935.43], $f4Config['dompdf_paper']);
        $this->assertSame('330mm 215mm', $f4Config['css_page_size']);
        $this->assertSame('6mm 8mm 6mm 8mm', $f4Config['css_margin']);
    }

    public function test_menu_cetak_memiliki_empat_grup_bersih_tanpa_nama_mapel_dan_dialog_cakupan_terpadu(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $mapel = MataPelajaran::firstOrFail();

        $responseMapel = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]));

        $responseMapel->assertOk();
        $responseMapel->assertSee('1. Cetak Formatif');
        $responseMapel->assertSee('2. Cetak STS');
        $responseMapel->assertSee('3. Cetak SAS');
        $responseMapel->assertSee('4. Cetak Rapor');
        $responseMapel->assertSee('Dialog Cetak Dokumen');
        $responseMapel->assertSee('Cakupan Cetak (Siswa / Kelas / Sekolah)');
        $responseMapel->assertSee('Filter Mata Pelajaran (Opsional)');
        $responseMapel->assertSee('A4 (210 × 297 mm)', false);
        $responseMapel->assertSee('F4 / Folio (215 × 330 mm)', false);
        $responseMapel->assertSee('Lengkapi dulu');
        $responseMapel->assertSee('Tetap cetak');

        // Nama mapel tidak boleh muncul lagi di item menu dropdown cetak
        $responseMapel->assertDontSee('Cetak Formatif Per Bulan ('.$mapel->nama.')');
        $responseMapel->assertDontSee('Daftar Nilai STS ('.$mapel->nama.')');
        $responseMapel->assertDontSee('Daftar Nilai SAS ('.$mapel->nama.')');
        $responseMapel->assertDontSee('Lembar Nilai Rapor ('.$mapel->nama.' PDF)');

        $responseKelas = $this->actingAs($admin)->get(route('kelas.show', $kelas->id));
        $responseKelas->assertOk();
        $responseKelas->assertSee('1. Cetak Formatif');
        $responseKelas->assertSee('4. Cetak Rapor');
        $responseKelas->assertSee('Dialog Cetak Dokumen');
    }

    public function test_rapor_lengkap_siswa_menampilkan_nilai_predikat_catatan_guru_serta_a4_dan_f4(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::with('tahunAjaran')->firstOrFail();
        $mapel = MataPelajaran::firstOrFail();
        $ks = $kelas->kelasSiswa()->firstOrFail();

        $tahun = now()->year;
        $semester = strtolower((string) ($kelas->tahunAjaran->semester ?? 'ganjil'));
        $bulan = $semester === 'genap' ? 2 : 8;

        NilaiFormatifBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], ['nilai' => 86]);

        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ], ['sumatif' => 88]);

        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sas',
        ], ['sumatif' => 90]);

        $catatanKhusus = 'Ananda aktif berdiskusi dan sangat teliti menyelesaikan tugas proyek.';
        NilaiRaporMapel::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ], [
            'nilai_uts' => 88,
            'nilai_uas' => 90,
            'rata_formatif' => 86,
            'nilai_rapor' => 87.8,
            'predikat' => 'B',
            'catatan' => $catatanKhusus,
        ]);

        // Default preview menggunakan A4
        $previewA4 = $this->actingAs($admin)->get(route('raport.preview', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sas',
        ]));
        $previewA4->assertOk();
        $previewA4->assertSee('Predikat');
        $previewA4->assertSee('Catatan Guru &amp; Capaian Kompetensi', false);
        $previewA4->assertSee($catatanKhusus);
        $previewA4->assertSee('210mm 297mm');

        // Beralih ke F4 Landscape
        $previewF4 = $this->actingAs($admin)->get(route('raport.preview', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sas',
            'paper' => 'f4',
            'orientation' => 'landscape',
            'margin' => 'sempit',
        ]));
        $previewF4->assertOk();
        $previewF4->assertSee('330mm 215mm');
        $previewF4->assertSee($catatanKhusus);

        // Unduh PDF A4 & F4 menghasilkan stream PDF valid
        $pdfResp = $this->actingAs($admin)->get(route('raport.download.pdf', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sas',
            'paper' => 'f4',
        ]));
        $pdfResp->assertOk();
        $pdfResp->assertHeader('content-type', 'application/pdf');
        $this->assertTrue(str_starts_with($pdfResp->getContent(), '%PDF'));
    }

    public function test_cek_kelengkapan_sebelum_cetak_mendeteksi_kosong_dan_berubah_lengkap_saat_terisi(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::with('tahunAjaran')->firstOrFail();
        $mapel = MataPelajaran::firstOrFail();
        $ks = $kelas->kelasSiswa()->firstOrFail();

        $tahun = now()->year;
        $semester = strtolower((string) ($kelas->tahunAjaran->semester ?? 'ganjil'));
        $bulan = $semester === 'genap' ? 2 : 8;

        // Kosongkan data nilai siswa & mapel target
        NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        Nilai::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();
        NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapel->id)->delete();

        $incomplete = $this->actingAs($admin)->getJson(route('kelas.cetak.cek-kelengkapan', [
            'kelas' => $kelas->id,
            'jenis' => 'rapor_kelas',
            'cakupan' => 'siswa',
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ]));

        $incomplete->assertOk();
        $incomplete->assertJsonPath('is_complete', false);
        $this->assertGreaterThanOrEqual(1, (int) $incomplete->json('summary.nilai_kosong'));
        $this->assertGreaterThanOrEqual(1, (int) $incomplete->json('summary.predikat_kosong'));
        $this->assertGreaterThanOrEqual(1, (int) $incomplete->json('summary.catatan_kosong'));

        $firstItem = $incomplete->json('items.0');
        $this->assertNotNull($firstItem);
        $this->assertSame($ks->siswa->nama, $firstItem['siswa_nama']);
        $this->assertStringContainsString('/nilai', $firstItem['url']);

        // Lengkapi semua komponen + catatan guru
        NilaiFormatifBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], ['nilai' => 85]);

        $this->actingAs($admin)->post(route('kelas.mapel.nilai.simpan-terpadu', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => $tahun,
            'semester' => $semester,
            'sts' => [$ks->id => 85],
            'sas' => [$ks->id => 88],
            'catatan' => [$ks->id => 'Sangat baik dalam memahami konsep inti.'],
        ])->assertSessionHas('success');

        $complete = $this->actingAs($admin)->getJson(route('kelas.cetak.cek-kelengkapan', [
            'kelas' => $kelas->id,
            'jenis' => 'rapor_kelas',
            'cakupan' => 'siswa',
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'semester' => $semester,
        ]));

        $complete->assertOk();
        $complete->assertJsonPath('is_complete', true);
        $complete->assertJsonPath('total_missing_items', 0);
    }

    public function test_pengguna_tanpa_hak_akses_ditolak_saat_cek_kelengkapan_atau_cetak_dokumen(): void
    {
        $kelas = Kelas::firstOrFail();

        $guruLuar = User::create([
            'name' => 'Guru Tanpa Kelas',
            'email' => 'guru.luar@sdn2karangsari.sch.id',
            'password' => bcrypt('password'),
            'role' => 'guru',
        ]);

        $this->actingAs($guruLuar)->getJson(route('kelas.cetak.cek-kelengkapan', [
            'kelas' => $kelas->id,
            'jenis' => 'rapor_kelas',
        ]))->assertForbidden();

        $this->actingAs($guruLuar)->get(route('kelas.cetak.dokumen', [
            'kelas' => $kelas->id,
            'jenis' => 'rapor_kelas',
        ]))->assertForbidden();
    }

    #[TestWith(['formatif_bulanan', 'kelas', 'a4', 'portrait'])]
    #[TestWith(['formatif_semester', 'kelas', 'f4', 'landscape'])]
    #[TestWith(['sts_daftar', 'kelas', 'a4', 'landscape'])]
    #[TestWith(['sts_lembar', 'kelas', 'f4', 'portrait'])]
    #[TestWith(['sas_daftar', 'kelas', 'a4', 'landscape'])]
    #[TestWith(['sas_lembar', 'kelas', 'a4', 'portrait'])]
    #[TestWith(['rapor_siswa', 'siswa', 'a4', 'portrait'])]
    #[TestWith(['rapor_kelas', 'kelas', 'f4', 'portrait'])]
    #[TestWith(['rapor_leger', 'kelas', 'a4', 'landscape'])]
    #[TestWith(['rapor_predikat_catatan', 'kelas', 'a4', 'portrait'])]
    public function test_endpoint_cetak_dokumen_semua_jenis_format_menghasilkan_pdf_valid(
        string $jenis,
        string $cakupan,
        string $paper,
        string $orientation
    ): void {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $ks = $kelas->kelasSiswa()->firstOrFail();

        $response = $this->actingAs($admin)->get(route('kelas.cetak.dokumen', [
            'kelas' => $kelas->id,
            'jenis' => $jenis,
            'cakupan' => $cakupan,
            'kelas_siswa_id' => $ks->id,
            'paper' => $paper,
            'orientation' => $orientation,
            'margin' => 'normal',
            'tahun' => now()->year,
            'bulan' => now()->month,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertTrue(str_starts_with($response->getContent(), '%PDF'));
    }

    public function test_cetak_cakupan_sekolah_dan_filter_mapel_opsional_menghasilkan_pdf_multikelas(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas1 = Kelas::with('tahunAjaran')->firstOrFail();
        $mapel = MataPelajaran::firstOrFail();

        // Tambahkan kelas kedua pada tahun ajaran yang sama agar cakupan sekolah benar-benar multi-kelas
        $kelas2 = Kelas::create([
            'nama' => 'Kelas II-B',
            'tingkat' => 2,
            'fase' => 'A',
            'tahun_ajaran_id' => $kelas1->tahun_ajaran_id,
            'sekolah_id' => $kelas1->sekolah_id,
            'wali_id' => $admin->id,
        ]);

        $siswa2 = Siswa::create([
            'nis' => '998877',
            'nisn' => '0099887766',
            'nama' => 'Siswa Uji Sekolah',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $ks2 = KelasSiswa::create([
            'kelas_id' => $kelas2->id,
            'siswa_id' => $siswa2->id,
        ]);

        NilaiRaporMapel::create([
            'kelas_siswa_id' => $ks2->id,
            'mapel_id' => $mapel->id,
            'tahun' => now()->year,
            'semester' => strtolower((string) ($kelas1->tahunAjaran->semester ?? 'ganjil')),
            'nilai_uts' => 82,
            'nilai_uas' => 84,
            'rata_formatif' => 80,
            'nilai_rapor' => 81.8,
            'predikat' => 'B',
            'catatan' => 'Terus pertahankan semangat belajar di kelas.',
        ]);

        // Cetak Rekap Predikat & Catatan untuk Cakupan Sekolah + Filter 1 Mapel Opsional
        $respSekolah = $this->actingAs($admin)->get(route('kelas.cetak.dokumen', [
            'kelas' => $kelas1->id,
            'jenis' => 'rapor_predikat_catatan',
            'cakupan' => 'sekolah',
            'mapel_id' => $mapel->id,
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margin' => 'normal',
        ]));

        $respSekolah->assertOk();
        $respSekolah->assertHeader('content-type', 'application/pdf');
        $this->assertTrue(str_starts_with($respSekolah->getContent(), '%PDF'));

        // Cetak Rapor Lengkap Sekelas untuk Cakupan Sekolah
        $respRaporSekolah = $this->actingAs($admin)->get(route('kelas.cetak.dokumen', [
            'kelas' => $kelas1->id,
            'jenis' => 'rapor_kelas',
            'cakupan' => 'sekolah',
            'paper' => 'f4',
            'orientation' => 'portrait',
            'margin' => 'sempit',
        ]));

        $respRaporSekolah->assertOk();
        $respRaporSekolah->assertHeader('content-type', 'application/pdf');
        $this->assertTrue(str_starts_with($respRaporSekolah->getContent(), '%PDF'));
    }
}
