<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\HitungNilaiService;
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

    public function test_sekolah_settings_kehadiran_can_be_configured_by_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $sekolah = Sekolah::first();
        $this->assertNotNull($sekolah);

        $response = $this->actingAs($admin)->post(route('sekolah.update'), [
            'nama' => $sekolah->nama,
            'pemda' => $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI',
            'dinas' => $sekolah->dinas ?? 'DINAS PENDIDIKAN',
            'kota' => $sekolah->kota ?? 'Banyuwangi',
            'alamat' => $sekolah->alamat,
            'kepsek' => $sekolah->kepsek,
            'nip' => $sekolah->nip,
            'hari_efektif_semester' => 110,
            'persen_kehadiran_minimal' => 80,
            'batas_alpa_maksimal' => 2,
            'batas_izin_maksimal' => 5,
        ]);

        $response->assertSessionHas('success');

        $sekolah->refresh();
        $this->assertEquals(110, $sekolah->getHariEfektif());
        $this->assertEquals(80, $sekolah->getPersenKehadiranMinimal());
        $this->assertEquals(2, $sekolah->getBatasAlpaMaksimal());
        $this->assertEquals(5, $sekolah->getBatasIzinMaksimal());
    }

    public function test_sia_integration_calculates_kehadiran_info_and_threshold_warnings(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();

        // Buat data kehadiran yang melampaui batas alpa (misal 5 hari alpa)
        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sas',
        ], [
            'sakit' => 2,
            'izin' => 1,
            'alpa' => 5,
        ]);

        $service = app(HitungNilaiService::class);
        $hasilNilai = $service->hitungNilaiSiswa($ks, 'sas');

        $this->assertArrayHasKey('kehadiran_info', $hasilNilai);
        $this->assertTrue($hasilNilai['is_peringatan_kehadiran']);
        $this->assertEquals(5, $hasilNilai['kehadiran_info']['alpa']);
        $this->assertStringContainsString('Alpa', implode(' ', $hasilNilai['pesan_kehadiran']));
        $this->assertStringContainsString('alpa', strtolower($hasilNilai['catatan_wali_otomatis']));

        // Pratinjau rapor harus menampilkan peringatan kehadiran & kotak informasi kehadiran
        $preview = $this->actingAs($admin)->get(route('raport.preview', [
            'kelasSiswa' => $ks->id,
            'periode' => 'sas',
        ]));
        $preview->assertStatus(200);
        $preview->assertSee('Peringatan: Ambang Batas Kehadiran Terlampaui!');
        $preview->assertSee('Hari Efektif');
        $preview->assertSee('Alpa (Tanpa Ket.)');
        $preview->assertSee('Catatan Wali Kelas');
    }

    public function test_academic_grades_are_not_deducted_by_attendance(): void
    {
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $service = app(HitungNilaiService::class);

        // Hitung nilai saat kehadiran 100% (0 alpa)
        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sas',
        ], ['sakit' => 0, 'izin' => 0, 'alpa' => 0]);
        $hasilNolAlpa = $service->hitungNilaiSiswa($ks, 'sas');

        // Hitung nilai saat alpa tinggi (misal 10 hari alpa)
        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sas',
        ], ['sakit' => 0, 'izin' => 0, 'alpa' => 10]);
        $hasilBanyakAlpa = $service->hitungNilaiSiswa($ks, 'sas');

        // Nilai akademik tidak boleh terpotong atau berbeda karena absensi
        $this->assertEquals($hasilNolAlpa['total_akhir'], $hasilBanyakAlpa['total_akhir']);
        $this->assertEquals($hasilNolAlpa['rata_akhir'], $hasilBanyakAlpa['rata_akhir']);

        // Tetapi kehadiran_info dan catatan otomatis harus berubah sesuai fakta absensi
        $this->assertFalse($hasilNolAlpa['is_peringatan_kehadiran']);
        $this->assertTrue($hasilBanyakAlpa['is_peringatan_kehadiran']);
        $this->assertNotEquals($hasilNolAlpa['catatan_wali_otomatis'], $hasilBanyakAlpa['catatan_wali_otomatis']);
    }
}
