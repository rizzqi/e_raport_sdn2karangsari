<?php

namespace Tests\Feature;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\User;
use App\Services\ExcelReader;
use App\Services\HitungNilaiService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NilaiImportDanKkmTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_mata_pelajaran_has_kkm_and_can_be_updated(): void
    {
        $admin = User::where('role', 'admin')->first();
        $mapel = MataPelajaran::first();

        $this->assertNotNull($mapel);
        $this->assertEquals(70, $mapel->kkm);

        $response = $this->actingAs($admin)->put(route('mapel.update', $mapel->id), [
            'nama' => 'Matematika Terpadu',
            'kkm' => 75,
            'urutan' => 1,
            'aktif' => true,
        ]);

        $response->assertRedirect(route('mapel.index'));
        $mapel->refresh();
        $this->assertEquals('Matematika Terpadu', $mapel->nama);
        $this->assertEquals(75, $mapel->kkm);
    }

    public function test_hitung_nilai_service_uses_kkm_and_calculates_ketuntasan(): void
    {
        $ks = KelasSiswa::first();
        $mapel = MataPelajaran::first();
        $mapel->update(['kkm' => 75]);

        // Simpan nilai di bawah KKM
        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ], [
            'formatif' => null,
            'sumatif' => 68,
        ]);

        $service = app(HitungNilaiService::class);
        $hasil = $service->hitungNilaiSiswa($ks, 'sts');

        $barisMapel = collect($hasil['baris'])->firstWhere('mapel_id', $mapel->id);
        $this->assertNotNull($barisMapel);
        $this->assertEquals(75, $barisMapel['kkm']);
        $this->assertEquals(68, $barisMapel['akhir']);
        $this->assertEquals('Belum Tuntas', $barisMapel['keterangan']);

        // Update nilai di atas KKM
        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ], [
            'formatif' => null,
            'sumatif' => 85,
        ]);

        $hasil2 = $service->hitungNilaiSiswa($ks, 'sts');
        $barisMapel2 = collect($hasil2['baris'])->firstWhere('mapel_id', $mapel->id);
        $this->assertEquals('Tuntas', $barisMapel2['keterangan']);
    }

    public function test_user_can_download_template_nilai_excel_per_kelas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        // Download template semua mapel (default format: XLSX)
        $responseSemua = $this->actingAs($admin)->get(route('nilai.template.excel', [
            'kelas' => $kelas->id,
            'mode' => 'semua',
            'periode' => 'sts',
        ]));

        $responseSemua->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $responseSemua->headers->get('content-type'));

        // Pastikan konten file .xlsx yang diunduh valid dan dapat dibaca oleh ExcelReader
        $tempXlsx = tempnam(sys_get_temp_dir(), 'test_dl_xlsx_');
        file_put_contents($tempXlsx, $responseSemua->getContent());
        $parsedRows = ExcelReader::parseFile($tempXlsx, 'xlsx');
        @unlink($tempXlsx);

        $this->assertNotEmpty($parsedRows);
        $this->assertEquals(['No', 'NIS', 'Nama Siswa'], array_slice($parsedRows[0], 0, 3));

        // Download template per mapel (format: XLSX)
        $mapel = MataPelajaran::first();
        $responseMapel = $this->actingAs($admin)->get(route('nilai.template.excel', [
            'kelas' => $kelas->id,
            'mode' => 'mapel',
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ]));

        $responseMapel->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $responseMapel->headers->get('content-type'));

        // Download template fallback format CSV
        $responseCsv = $this->actingAs($admin)->get(route('nilai.template.excel', [
            'kelas' => $kelas->id,
            'mode' => 'semua',
            'periode' => 'sts',
            'format' => 'csv',
        ]));

        $responseCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $responseCsv->headers->get('content-type'));
    }

    public function test_user_can_import_grades_from_clipboard_text(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $s1 = $kelasSiswaList[0]->siswa;
        $s2 = $kelasSiswaList[1]->siswa;

        $mapel1 = MataPelajaran::orderBy('urutan')->first();
        $mapel2 = MataPelajaran::orderBy('urutan')->skip(1)->first();

        $clipboardText = "No\tNIS\tNama Siswa\t{$mapel1->nama}\t{$mapel2->nama}\n"
            ."1\t{$s1->nis}\t{$s1->nama}\t88\t92\n"
            ."2\t{$s2->nis}\t{$s2->nama}\t76\t84\n";

        $response = $this->actingAs($admin)->post(route('nilai.import.excel', $kelas->id), [
            'periode' => 'sts',
            'data_teks' => $clipboardText,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi database terisi
        $n1 = Nilai::where('kelas_siswa_id', $kelasSiswaList[0]->id)
            ->where('mapel_id', $mapel1->id)
            ->where('periode', 'sts')
            ->first();

        $this->assertNotNull($n1);
        $this->assertEquals(88, $n1->sumatif);

        $n2 = Nilai::where('kelas_siswa_id', $kelasSiswaList[1]->id)
            ->where('mapel_id', $mapel2->id)
            ->where('periode', 'sts')
            ->first();

        $this->assertNotNull($n2);
        $this->assertEquals(84, $n2->sumatif);
    }

    public function test_user_can_import_grades_without_nis_or_nisn_column_matching_by_name(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $s1 = $kelasSiswaList[0]->siswa;
        $s2 = $kelasSiswaList[1]->siswa;

        $mapel = MataPelajaran::orderBy('urutan')->first();

        // Data teks tanpa NIS/NISN sama sekali, hanya kolom Nama Siswa dan Mapel
        $textTanpaNis = "No\tNama Siswa\t{$mapel->nama}\n"
            ."1\t{$s1->nama}\t95\n"
            ."2\t{$s2->nama}\t83\n";

        $response = $this->actingAs($admin)->post(route('nilai.import.excel', $kelas->id), [
            'periode' => 'sas',
            'data_teks' => $textTanpaNis,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $n1 = Nilai::where('kelas_siswa_id', $kelasSiswaList[0]->id)
            ->where('mapel_id', $mapel->id)
            ->where('periode', 'sas')
            ->first();

        $this->assertNotNull($n1);
        $this->assertEquals(95, $n1->sumatif);

        $n2 = Nilai::where('kelas_siswa_id', $kelasSiswaList[1]->id)
            ->where('mapel_id', $mapel->id)
            ->where('periode', 'sas')
            ->first();

        $this->assertNotNull($n2);
        $this->assertEquals(83, $n2->sumatif);
    }

    public function test_import_excel_with_abbreviations_kkm_row_and_fuzzy_student_names(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->with('siswa')->first();
        $student = $ks->siswa;

        // Beri sedikit typo pada nama siswa (misal tambahkan 1 huruf di akhir atau variasi y/i)
        $namaWithTypo = $student->nama.'h';

        $textWithAbbreviationsAndKkm = "NO\tNAMA\tPAI\tPP\tB. INDONESIA\tMATEMATIKA\tSBK\tB. JAWA\tB. INGGRIS\tB. USING\n"
            ."\tKKM\t75\t75\t75\t72\t70\t68\t70\t68\n"
            ."1\t{$namaWithTypo}\t85\t88\t90\t78\t80\t74\t82\t79\n";

        $response = $this->actingAs($admin)->post(route('nilai.import.excel', $kelas->id), [
            'periode' => 'sts',
            'data_teks' => $textWithAbbreviationsAndKkm,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // KKM harus tersinkron otomatis ke kelas_mapel_kkm
        $mapelMtk = MataPelajaran::where('nama', 'like', '%Matematika%')->first();
        $this->assertDatabaseHas('kelas_mapel_kkm', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelMtk->id,
            'kkm' => 72,
        ]);

        // Nilai harus tersimpan untuk siswa yang cocok fuzzy
        $nilaiMtk = Nilai::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapelMtk->id)
            ->where('periode', 'sts')
            ->first();

        $this->assertNotNull($nilaiMtk);
        $this->assertEquals(78, $nilaiMtk->sumatif);
    }

    public function test_user_can_empty_class_grades_for_specific_mapel(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel1 = MataPelajaran::first();
        $mapel2 = MataPelajaran::skip(1)->first();
        $ks = $kelas->kelasSiswa()->first();

        // Siapkan nilai untuk 2 mapel
        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel1->id,
            'periode' => 'sts',
        ], ['sumatif' => 88]);

        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel2->id,
            'periode' => 'sts',
        ], ['sumatif' => 90]);

        // Kosongkan HANYA mapel1
        $response = $this->actingAs($admin)->post(route('nilai.kosongkan.kelas', $kelas->id), [
            'periode' => 'sts',
            'mapel_id' => (string) $mapel1->id,
            'konfirmasi' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Nilai mapel1 harus terhapus
        $this->assertDatabaseMissing('nilai', [
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel1->id,
            'periode' => 'sts',
        ]);

        // Nilai mapel2 harus TETAP ADA
        $this->assertDatabaseHas('nilai', [
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel2->id,
            'periode' => 'sts',
        ]);
    }

    public function test_user_can_empty_all_class_grades_including_kehadiran_and_pembiasaan(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = MataPelajaran::first();
        $ks = $kelas->kelasSiswa()->first();
        $pem = Pembiasaan::first();

        Nilai::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapel->id,
            'periode' => 'sts',
        ], ['sumatif' => 85]);

        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sts',
        ], ['sakit' => 2, 'izin' => 1, 'alpa' => 0]);

        if ($pem) {
            NilaiPembiasaan::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'pembiasaan_id' => $pem->id,
                'periode' => 'sts',
            ], ['predikat' => 'Sangat Baik']);
        }

        // Kosongkan seluruh nilai sekelas beserta kehadiran & pembiasaan
        $response = $this->actingAs($admin)->post(route('nilai.kosongkan.kelas', $kelas->id), [
            'periode' => 'sts',
            'mapel_id' => 'semua',
            'kosongkan_kehadiran' => '1',
            'kosongkan_pembiasaan' => '1',
            'konfirmasi' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('nilai', [
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sts',
        ]);

        $this->assertDatabaseMissing('kehadiran', [
            'kelas_siswa_id' => $ks->id,
            'periode' => 'sts',
        ]);

        if ($pem) {
            $this->assertDatabaseMissing('nilai_pembiasaan', [
                'kelas_siswa_id' => $ks->id,
                'periode' => 'sts',
            ]);
        }
    }

    public function test_kosongkan_nilai_requires_confirmation_checkbox(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->post(route('nilai.kosongkan.kelas', $kelas->id), [
            'periode' => 'sts',
            'mapel_id' => 'semua',
            // Tanpa konfirmasi
        ]);

        $response->assertSessionHasErrors('konfirmasi');
    }
}
