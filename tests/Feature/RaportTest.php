<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Raport;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\HitungNilaiService;
use App\Services\RaportDocxService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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
        $sekolah = Sekolah::first();
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
        $docxService = app(RaportDocxService::class);
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

        $sekolah = Sekolah::first();
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
        $mapel = MataPelajaran::first();
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
        $ta = TahunAjaran::first();

        // Admin menambahkan kelas dengan mengetikkan nama wali kelas secara langsung
        $storePost = $this->actingAs($admin)->post(route('kelas.store'), [
            'nama' => 'Kelas 2 Unggulan',
            'fase' => 'Fase A',
            'tahun_ajaran_id' => $ta->id,
            'wali_nama' => 'Siti Nurhaliza, S.Pd.',
            'wali_nip' => '19900101 201502 2 001',
        ]);
        $storePost->assertRedirect(route('kelas.index'));

        $kelasBaru = Kelas::where('nama', 'Kelas 2 Unggulan')->first();
        $this->assertNotNull($kelasBaru);
        $this->assertEquals('Siti Nurhaliza, S.Pd.', $kelasBaru->nama_wali);
        $this->assertEquals('19900101 201502 2 001', $kelasBaru->nip_wali);
    }

    public function test_upload_ttd_kepsek_and_preview(): void
    {
        Storage::fake('public');
        $admin = User::where('role', 'admin')->first();
        $fakeTtd = UploadedFile::fake()->image('ttd_kepsek.png', 300, 150);

        $updatePost = $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => 'SD Negeri 2 Karangsari',
            'pemda' => 'Pemerintah Kabupaten Banyuwangi',
            'dinas' => 'Dinas Pendidikan',
            'kota' => 'Banyuwangi',
            'alamat' => 'Jl. Karangsari No. 12',
            'kontak' => 'Telp: 08123456789',
            'kepsek' => 'Budi Santoso, M.Pd.',
            'nip' => '19750101 200003 1 002',
            'ttd' => $fakeTtd,
        ]);
        $updatePost->assertRedirect(route('sekolah.edit'));

        $sekolah = Sekolah::first();
        $this->assertNotNull($sekolah->ttd);
        Storage::disk('public')->assertExists($sekolah->ttd);

        // Uji hapus TTD
        $hapusPost = $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda,
            'dinas' => $sekolah->dinas,
            'kota' => $sekolah->kota,
            'hapus_ttd' => '1',
        ]);
        $hapusPost->assertRedirect(route('sekolah.edit'));
        $this->assertNull($sekolah->fresh()->ttd);
    }

    public function test_cetak_sekelas_and_download_pdf_sekelas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $this->assertNotNull($kelas);

        // Akses halaman cetak sekelas di browser
        $cetakResponse = $this->actingAs($admin)->get(route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $cetakResponse->assertStatus(200);
        $cetakResponse->assertSeeText('Cetak Semua (Ctrl+P)');
        $cetakResponse->assertSeeText('Format F4 (215 × 330 mm)');
        $cetakResponse->assertSeeText('Unduh PDF Sekelas');

        // Pastikan nama-nama siswa di kelas tampil di lembar cetak
        foreach ($kelas->kelasSiswa as $ks) {
            $cetakResponse->assertSeeText($ks->siswa->nama);
        }

        // Akses download PDF gabungan sekelas
        $pdfResponse = $this->actingAs($admin)->get(route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }

    public function test_update_tanggal_raport_authorization_and_persistence(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $otherUser = User::factory()->create(['role' => 'kepsek']);

        // Unauthorized user cannot update tanggal raport
        $forbidden = $this->actingAs($otherUser)->put(route('kelas.tanggal-raport.update', $kelas->id), [
            'periode' => 'sts',
            'tanggal' => '2024-12-20',
        ]);
        $forbidden->assertStatus(403);

        // Admin can update via AJAX (returns JSON)
        $ajaxResponse = $this->actingAs($admin)->putJson(route('kelas.tanggal-raport.update', $kelas->id), [
            'periode' => 'sts',
            'tanggal' => '2024-10-15',
        ]);
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson([
            'success' => true,
            'tanggal' => '2024-10-15',
            'periode' => 'sts',
        ]);

        $this->assertEquals('2024-10-15', $kelas->fresh()->tanggal_raport_sts->format('Y-m-d'));

        // Admin can also update both via full form
        $formResponse = $this->actingAs($admin)->put(route('kelas.tanggal-raport.update', $kelas->id), [
            'tanggal_raport_sts' => '2024-10-20',
            'tanggal_raport_sas' => '2024-12-21',
        ]);
        $formResponse->assertRedirect();
        $fresh = $kelas->fresh();
        $this->assertEquals('2024-10-20', $fresh->tanggal_raport_sts->format('Y-m-d'));
        $this->assertEquals('2024-12-21', $fresh->tanggal_raport_sas->format('Y-m-d'));
    }

    public function test_raport_preview_and_cetak_sekelas_uses_custom_and_saved_date(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa->first();

        // 1. Set saved date
        $kelas->update([
            'tanggal_raport_sts' => '2024-10-18',
        ]);

        // Preview should use saved date: 18 Oktober 2024
        $previewSaved = $this->actingAs($admin)->get(route('raport.preview', ['kelasSiswa' => $ks->id, 'periode' => 'sts']));
        $previewSaved->assertStatus(200);
        $previewSaved->assertSee('18 Oktober 2024');

        // 2. Query param ?tanggal= overrides saved date
        $previewOverride = $this->actingAs($admin)->get(route('raport.preview', ['kelasSiswa' => $ks->id, 'periode' => 'sts', 'tanggal' => '2024-10-25']));
        $previewOverride->assertStatus(200);
        $previewOverride->assertSee('25 Oktober 2024');

        // 3. Cetak sekelas also uses the custom date
        $cetakSekelas = $this->actingAs($admin)->get(route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts', 'tanggal' => '2024-10-25']));
        $cetakSekelas->assertStatus(200);
        $cetakSekelas->assertSee('25 Oktober 2024');
    }

    public function test_download_pdf_and_docx_uses_custom_date_and_records_history(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa->first();

        // PDF single student with custom date
        $pdfResponse = $this->actingAs($admin)->get(route('raport.download.pdf', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sts',
            'tanggal' => '2024-10-15',
        ]));
        $pdfResponse->assertStatus(200);

        $riwayatPdf = Raport::where('kelas_siswa_id', $ks->id)
            ->where('file_path', 'PDF_STREAM')
            ->latest('id')
            ->first();
        $this->assertNotNull($riwayatPdf);
        $this->assertEquals('2024-10-15', $riwayatPdf->tanggal_cetak->format('Y-m-d'));

        // DOCX single student with custom date
        $docxResponse = $this->actingAs($admin)->get(route('raport.download.docx', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sts',
            'tanggal' => '2024-10-15',
        ]));
        $docxResponse->assertStatus(200);

        $riwayatDocx = Raport::where('kelas_siswa_id', $ks->id)
            ->where('file_path', '!=', 'PDF_STREAM')
            ->latest('id')
            ->first();
        $this->assertNotNull($riwayatDocx);
        $this->assertEquals('2024-10-15', $riwayatDocx->tanggal_cetak->format('Y-m-d'));
    }

    public function test_raport_view_columns_are_clean_for_parents_and_pdf_kelas_works(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa->first();

        // 1. Preview Tunggal Rapor
        $preview = $this->actingAs($admin)->get(route('raport.preview', ['kelasSiswa' => $ks->id, 'periode' => 'sts']));
        $preview->assertStatus(200);
        $preview->assertSee('Nilai Rapor');
        $preview->assertSee('Predikat');
        $preview->assertSee('Capaian Kompetensi');
        // Tidak menampilkan kolom formatif/uts/uas di tabel nilai
        $preview->assertDontSee('<th class="border border-slate-900 py-2 px-2 w-16">UTS</th>', false);
        $preview->assertDontSee('<th class="border border-slate-900 py-2 px-2 w-16">UAS</th>', false);

        // 2. Cetak Sekelas
        $cetakKelas = $this->actingAs($admin)->get(route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $cetakKelas->assertStatus(200);
        $cetakKelas->assertSee('Nilai Rapor');
        $cetakKelas->assertSee('Predikat');
        $cetakKelas->assertSee('Capaian Kompetensi');
        $cetakKelas->assertSee('sheet-raport');

        // 3. Download PDF Sekelas
        $pdfKelas = $this->actingAs($admin)->get(route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $pdfKelas->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfKelas->headers->get('content-type'));
    }

    public function test_lembar_sts_and_sas_dedicated_format(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa->first();

        // 1. Lembar STS Cetak Sekelas di Browser
        $stsResponse = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.cetak', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
        ]));
        $stsResponse->assertStatus(200);
        $stsResponse->assertSee('LAPORAN HASIL BELAJAR PESERTA DIDIK');
        $stsResponse->assertSee('SUMATIF TENGAH SEMESTER (STS)');
        $stsResponse->assertSee('PERKEMBANGAN KARAKTER &amp; KETIDAKHADIRAN', false);
        $stsResponse->assertSee('Tanpa Ket.');
        $stsResponse->assertSee($ks->siswa->nama);

        // 2. Lembar SAS Cetak Sekelas di Browser
        $sasResponse = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.cetak', [
            'kelas' => $kelas->id,
            'periode' => 'sas',
        ]));
        $sasResponse->assertStatus(200);
        $sasResponse->assertSee('LAPORAN HASIL BELAJAR PESERTA DIDIK');
        $sasResponse->assertSee('SUMATIF AKHIR SEMESTER (SAS)');

        // 3. Unduh PDF Sekelas STS
        $pdfSekelas = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.pdf-sekelas', [
            'kelas' => $kelas->id,
            'periode' => 'sts',
        ]));
        $pdfSekelas->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfSekelas->headers->get('content-type'));

        // 4. Unduh PDF Siswa Tunggal STS
        $pdfSiswa = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.pdf-siswa', [
            'kelas' => $kelas->id,
            'kelasSiswa' => $ks->id,
            'periode' => 'sts',
        ]));
        $pdfSiswa->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfSiswa->headers->get('content-type'));
    }
}
