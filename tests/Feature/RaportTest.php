<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Models\User;
use App\Models\KelasSiswa;
use App\Services\HitungNilaiService;
use App\Services\RaportDocxService;

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

        // Test PDF Download
        $pdfResponse = $this->actingAs($wali)->get("/raport/download-pdf/{$ks->id}?periode=sts");
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertHeader('content-type', 'application/pdf');
    }
}
