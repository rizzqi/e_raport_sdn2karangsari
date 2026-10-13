<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\KelasSiswa;
use App\Services\HitungNilaiService;
use App\Services\RaportDocxService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RaportTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_and_dashboard_access(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        // Test login POST with email validation
        $loginPost = $this->post('/login', [
            'email' => 'admin@sdn2karangsari.sch.id',
            'password' => 'password',
        ]);
        $loginPost->assertRedirect('/dashboard');

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('SDN 2 KARANGSARI');
    }

    public function test_hitung_nilai_service(): void
    {
        $ks = KelasSiswa::first();
        $this->assertNotNull($ks);

        $service = app(HitungNilaiService::class);
        $hasil = $service->hitungNilaiSiswa($ks, 'sts');

        $this->assertArrayHasKey('baris', $hasil);
        $this->assertArrayHasKey('total_formatif', $hasil);
        $this->assertArrayHasKey('total_sumatif', $hasil);
        $this->assertArrayHasKey('rata_sumatif_format', $hasil);
        $this->assertGreaterThan(0, $hasil['total_sumatif']);
    }

    public function test_generate_docx_raport(): void
    {
        $ks = KelasSiswa::first();
        $this->assertNotNull($ks);

        $service = app(RaportDocxService::class);
        $filePath = $service->generateDocxSatuSiswa($ks, 'sts');

        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.docx', $filePath);
    }

    public function test_raport_preview_and_pdf_download(): void
    {
        $wali = User::where('role', 'wali')->first();
        $ks = KelasSiswa::first();

        // Test Preview
        $previewResponse = $this->actingAs($wali)->get("/raport/preview/{$ks->id}?periode=sts");
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee($ks->siswa->nama);
        $previewResponse->assertSee('F4');

        // Test PDF Download
        $pdfResponse = $this->actingAs($wali)->get("/raport/download-pdf/{$ks->id}?periode=sts");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_update_sekolah_kop_and_f4_raport(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->first();

        // Akses halaman identitas sekolah
        $editPage = $this->actingAs($admin)->get(route('sekolah.edit'));
        $editPage->assertStatus(200);
        $editPage->assertSeeText('Identitas & Kop Surat Raport');
        $editPage->assertSeeText('F4 / Folio (215 × 330 mm)');

        // Buat file fake untuk logo kiri dan logo kanan
        $logoKiri = UploadedFile::fake()->image('logo_pemda.png', 100, 100);
        $logoKanan = UploadedFile::fake()->image('logo_sekolah.png', 100, 100);

        // Submit pembaruan Kop
        $updatePost = $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => 'SD NEGERI 2 KARANGSARI UNGGULAN',
            'pemda' => 'PEMERINTAH KABUPATEN BANYUWANGI JATIM',
            'dinas' => 'DINAS PENDIDIKAN DAN KEBUDAYAAN',
            'kota' => 'Banyuwangi',
            'alamat' => 'Jl. Karangsari Indah No. 99, Sempu',
            'kontak' => 'Telp: 08123456789 | Email: sdn2karangsari@sekolah.id',
            'kepsek' => 'Bambang Irawan, M.Pd.',
            'nip' => '198001012005011005',
            'logo' => $logoKiri,
            'logo_kanan' => $logoKanan,
        ]);

        $updatePost->assertRedirect(route('sekolah.edit'));
        $updatePost->assertSessionHas('success');

        // Verifikasi database terupdate
        $sekolah = \App\Models\Sekolah::first();
        $this->assertEquals('SD NEGERI 2 KARANGSARI UNGGULAN', $sekolah->nama);
        $this->assertEquals('PEMERINTAH KABUPATEN BANYUWANGI JATIM', $sekolah->pemda);
        $this->assertEquals('DINAS PENDIDIKAN DAN KEBUDAYAAN', $sekolah->dinas);
        $this->assertEquals('Telp: 08123456789 | Email: sdn2karangsari@sekolah.id', $sekolah->kontak);
        $this->assertNotNull($sekolah->logo);
        $this->assertNotNull($sekolah->logo_kanan);
        Storage::disk('public')->assertExists($sekolah->logo);
        Storage::disk('public')->assertExists($sekolah->logo_kanan);

        // Verifikasi kop muncul pada pratinjau raport
        $ks = KelasSiswa::first();
        $preview = $this->actingAs($admin)->get("/raport/preview/{$ks->id}?periode=sts");
        $preview->assertStatus(200);
        $preview->assertSee('SD NEGERI 2 KARANGSARI UNGGULAN');
        $preview->assertSee('PEMERINTAH KABUPATEN BANYUWANGI JATIM');
        $preview->assertSee('Telp: 08123456789 | Email: sdn2karangsari@sekolah.id');

        // Verifikasi generate DOCX dengan kop dan logo
        $docxService = app(\App\Services\RaportDocxService::class);
        $filePath = $docxService->generateDocxSatuSiswa($ks, 'sts');
        $this->assertFileExists($filePath);

        // Verifikasi PDF download dengan kop baru
        $pdfResponse = $this->actingAs($admin)->get("/raport/download-pdf/{$ks->id}?periode=sts");
        $pdfResponse->assertStatus(200);
    }

    public function test_update_sekolah_kop_with_cropped_base64_logos(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->first();

        // 1x1 transparent PNG base64
        $base64Logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $updatePost = $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => 'SD NEGERI 2 KARANGSARI CROPPED',
            'pemda' => 'PEMERINTAH KABUPATEN BANYUWANGI',
            'dinas' => 'DINAS PENDIDIKAN',
            'kota' => 'Banyuwangi',
            'cropped_logo' => $base64Logo,
            'cropped_logo_kanan' => $base64Logo,
        ]);

        $updatePost->assertRedirect(route('sekolah.edit'));
        $updatePost->assertSessionHas('success');

        $sekolah = \App\Models\Sekolah::first();
        $this->assertEquals('SD NEGERI 2 KARANGSARI CROPPED', $sekolah->nama);
        $this->assertNotNull($sekolah->logo);
        $this->assertNotNull($sekolah->logo_kanan);
        $this->assertStringEndsWith('.png', $sekolah->logo);
        $this->assertStringEndsWith('.png', $sekolah->logo_kanan);
        Storage::disk('public')->assertExists($sekolah->logo);
        Storage::disk('public')->assertExists($sekolah->logo_kanan);
    }

    public function test_only_admin_can_input_grades_and_non_admin_forbidden(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kepsek = User::where('role', 'kepsek')->first();
        $ks = KelasSiswa::first();
        $kelas = $ks->kelas;

        // Admin dapat mengakses form input nilai
        $adminGet = $this->actingAs($admin)->get(route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]));
        $adminGet->assertStatus(200);

        // Admin dapat menyimpan nilai
        $mapel = \App\Models\MataPelajaran::first();
        $adminPost = $this->actingAs($admin)->post(route('nilai.simpan.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]), [
            'periode' => 'sts',
            'formatif' => [$mapel->id => 88],
            'sumatif' => [$mapel->id => 92],
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ]);
        $adminPost->assertRedirect();

        // Non-admin (kepsek/wali yang tidak diizinkan) dilarang mengakses form input nilai
        $kepsekGet = $this->actingAs($kepsek)->get(route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]));
        $kepsekGet->assertStatus(403);

        // Non-admin dilarang menyimpan nilai
        $kepsekPost = $this->actingAs($kepsek)->post(route('nilai.simpan.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]), [
            'periode' => 'sts',
            'formatif' => [$mapel->id => 50],
        ]);
        $kepsekPost->assertStatus(403);
    }

    public function test_kelas_direct_wali_nama_and_nip_without_user_account(): void
    {
        $admin = User::where('role', 'admin')->first();
        $ta = \App\Models\TahunAjaran::first();

        // Admin menambahkan kelas dengan mengetikkan nama wali kelas secara langsung
        $storePost = $this->actingAs($admin)->post(route('kelas.store'), [
            'nama' => 'Kelas 2 Unggulan',
            'fase' => 'Fase A',
            'tahun_ajaran_id' => $ta->id,
            'wali_nama' => 'Siti Nurhaliza, S.Pd.',
            'wali_nip' => '19900101 201502 2 001',
        ]);
        $storePost->assertRedirect(route('kelas.index'));

        $kelasBaru = \App\Models\Kelas::where('nama', 'Kelas 2 Unggulan')->first();
        $this->assertNotNull($kelasBaru);
        $this->assertEquals('Siti Nurhaliza, S.Pd.', $kelasBaru->nama_wali);
        $this->assertEquals('19900101 201502 2 001', $kelasBaru->nip_wali);
    }
}
