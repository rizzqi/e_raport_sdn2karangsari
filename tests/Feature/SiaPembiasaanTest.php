<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\User;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiaPembiasaanTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_input_sia_pembiasaan_dapat_diakses_oleh_admin_dan_wali(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('kelas.sia-pembiasaan.index', $kelas->id));
        $response->assertStatus(200);
        $response->assertSee('Input SIA');
        $response->assertSee($kelas->nama);
        $response->assertSee('Ketidakhadiran (Hari)');

        // Akses wali kelas yang bersangkutan
        if ($kelas->wali_id) {
            $wali = User::find($kelas->wali_id);
            if ($wali) {
                $responseWali = $this->actingAs($wali)->get(route('kelas.sia-pembiasaan.index', $kelas->id));
                $responseWali->assertStatus(200);
            }
        }
    }

    public function test_simpan_sia_pembiasaan_secara_manual(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $this->assertNotNull($ks);

        $pembiasaan = Pembiasaan::aktif()->first();
        $this->assertNotNull($pembiasaan);

        $response = $this->actingAs($admin)->post(route('kelas.sia-pembiasaan.simpan', $kelas->id), [
            'periode' => 'sts',
            'sakit' => [$ks->id => 2],
            'izin' => [$ks->id => 3],
            'alpa' => [$ks->id => 1],
            'predikat' => [
                $ks->id => [
                    $pembiasaan->id => 'Sangat Baik',
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        // Pastikan Kehadiran tersimpan
        $kh = Kehadiran::where('kelas_siswa_id', $ks->id)->where('periode', 'sts')->first();
        $this->assertNotNull($kh);
        $this->assertEquals(2, $kh->sakit);
        $this->assertEquals(3, $kh->izin);
        $this->assertEquals(1, $kh->alpa);

        // Pastikan Nilai Pembiasaan tersimpan
        $np = NilaiPembiasaan::where('kelas_siswa_id', $ks->id)->where('pembiasaan_id', $pembiasaan->id)->where('periode', 'sts')->first();
        $this->assertNotNull($np);
        $this->assertEquals('Sangat Baik', $np->predikat);
    }

    public function test_download_template_sia_pembiasaan_excel_dan_csv(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        // 1. Template .xlsx
        $respXlsx = $this->actingAs($admin)->get(route('kelas.sia-pembiasaan.template', [
            'kelas' => $kelas->id,
            'format' => 'xlsx',
            'periode' => 'sts',
        ]));
        $respXlsx->assertStatus(200);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $respXlsx->headers->get('Content-Type'));

        // 2. Template .csv
        $respCsv = $this->actingAs($admin)->get(route('kelas.sia-pembiasaan.template', [
            'kelas' => $kelas->id,
            'format' => 'csv',
            'periode' => 'sts',
        ]));
        $respCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $respCsv->headers->get('Content-Type'));
    }

    public function test_import_sia_pembiasaan_dari_clipboard(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $this->assertNotNull($ks);
        $siswa = $ks->siswa;

        $pembiasaan = Pembiasaan::aktif()->first();

        // Data teks clipboard TSV
        $clipboardText = "No\tNIS\tNama Siswa\tSakit\tIzin\tAlpa\t{$pembiasaan->nama}\n"
            ."1\t{$siswa->nis}\t{$siswa->nama}\t4\t2\t1\tSangat Baik";

        $response = $this->actingAs($admin)->post(route('kelas.sia-pembiasaan.import', $kelas->id), [
            'periode' => 'sts',
            'data_teks' => $clipboardText,
        ]);

        $response->assertSessionHas('success');

        $kh = Kehadiran::where('kelas_siswa_id', $ks->id)->where('periode', 'sts')->first();
        $this->assertNotNull($kh);
        $this->assertEquals(4, $kh->sakit);
        $this->assertEquals(2, $kh->izin);
        $this->assertEquals(1, $kh->alpa);

        $np = NilaiPembiasaan::where('kelas_siswa_id', $ks->id)->where('pembiasaan_id', $pembiasaan->id)->where('periode', 'sts')->first();
        $this->assertNotNull($np);
        $this->assertEquals('Sangat Baik', $np->predikat);
    }

    public function test_import_sia_pembiasaan_dari_file_pdf(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $this->assertNotNull($ks);
        $siswa = $ks->siswa;

        $pembiasaan = Pembiasaan::aktif()->first();

        // Buat file PDF sintetis dengan Dompdf
        $dompdf = new Dompdf;
        $html = "
        <html><body>
        <table border='1'>
            <tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>{$pembiasaan->nama}</th></tr>
            <tr><td>1</td><td>{$siswa->nis}</td><td>{$siswa->nama}</td><td>3</td><td>1</td><td>0</td><td>Sangat Baik</td></tr>
        </table>
        </body></html>
        ";
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $tmpPdf = tempnam(sys_get_temp_dir(), 'test_sia_').'.pdf';
        file_put_contents($tmpPdf, $dompdf->output());

        $uploadedPdf = new UploadedFile($tmpPdf, 'rekap_sia.pdf', 'application/pdf', null, true);

        $response = $this->actingAs($admin)->post(route('kelas.sia-pembiasaan.import', $kelas->id), [
            'periode' => 'sas',
            'file_excel' => $uploadedPdf,
        ]);

        unlink($tmpPdf);

        $response->assertSessionHas('success');

        $kh = Kehadiran::where('kelas_siswa_id', $ks->id)->where('periode', 'sas')->first();
        $this->assertNotNull($kh);
        $this->assertEquals(3, $kh->sakit);
        $this->assertEquals(1, $kh->izin);
        $this->assertEquals(0, $kh->alpa);

        $np = NilaiPembiasaan::where('kelas_siswa_id', $ks->id)->where('pembiasaan_id', $pembiasaan->id)->where('periode', 'sas')->first();
        $this->assertNotNull($np);
        $this->assertEquals('Sangat Baik', $np->predikat);
    }

    public function test_tarik_dari_absensi_bulanan(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $tahun = now()->year;

        // Buat absensi bulan Juli (7) dan Agustus (8) untuk STS semester ganjil
        KehadiranBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'tahun' => $tahun,
            'bulan' => 7,
        ], [
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 0,
        ]);

        KehadiranBulanan::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'tahun' => $tahun,
            'bulan' => 8,
        ], [
            'sakit' => 1,
            'izin' => 2,
            'alpa' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('kelas.sia-pembiasaan.tarik-absensi', $kelas->id), [
            'periode' => 'sts',
            'tahun' => $tahun,
        ]);

        $response->assertSessionHas('success');

        $kh = Kehadiran::where('kelas_siswa_id', $ks->id)->where('periode', 'sts')->first();
        $this->assertNotNull($kh);
        // Total akumulasi bulan 7 + 8: Sakit 3, Izin 3, Alpa 1
        $this->assertEquals(3, $kh->sakit);
        $this->assertEquals(3, $kh->izin);
        $this->assertEquals(1, $kh->alpa);
    }
}
