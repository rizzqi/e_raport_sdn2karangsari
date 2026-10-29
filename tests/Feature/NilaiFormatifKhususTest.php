<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NilaiFormatifKhususTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_mapel_biasa_menampilkan_tabel_formatif_reguler(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapelMatematika = MataPelajaran::where('nama', 'like', '%Matematika%')->first();
        $this->assertNotNull($mapelMatematika);

        $this->assertEquals('reguler', $mapelMatematika->getJenisFormatif());
        $this->assertFalse($mapelMatematika->isAgama());
        $this->assertFalse($mapelMatematika->isPjok());

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapelMatematika->id,
            'tab' => 'formatif',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Mapel Reguler (1 Nilai/Bulan)');
        $response->assertDontSee('Prak 1');
        $response->assertDontSee('Praktik Jasmani');

        // Simpan nilai formatif reguler
        $ks = $kelas->kelasSiswa()->first();
        $saveResp = $this->actingAs($admin)->post(route('kelas.mapel.nilai.formatif', [
            'kelas' => $kelas->id,
            'mapel' => $mapelMatematika->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'formatif' => [
                $ks->id => [
                    7 => 80,
                    8 => 85,
                    9 => 90,
                ],
            ],
        ]);

        $saveResp->assertSessionHas('success');

        // Pastikan tersimpan dan rata-rata benar
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapelMatematika->id)->first();
        $this->assertNotNull($rapor);
        $this->assertEquals(85.0, (float) $rapor->rata_formatif);
    }

    public function test_pendidikan_agama_menampilkan_dua_kolom_praktikum_dan_enam_bulan_formatif(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapelAgama = MataPelajaran::where('nama', 'like', '%Agama%')->first();
        $this->assertNotNull($mapelAgama);

        $this->assertEquals('agama', $mapelAgama->getJenisFormatif());
        $this->assertTrue($mapelAgama->isAgama());
        $this->assertFalse($mapelAgama->isPjok());

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapelAgama->id,
            'tab' => 'formatif',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Format Khusus Pendidikan Agama');
        $response->assertSee('Prak 1');
        $response->assertSee('(Pra-UTS)');
        $response->assertSee('Prak 2');
        $response->assertSee('(Pra-UAS)');

        // Simpan nilai bulanan + 2 praktikum
        $ks = $kelas->kelasSiswa()->first();
        $saveResp = $this->actingAs($admin)->post(route('kelas.mapel.nilai.formatif', [
            'kelas' => $kelas->id,
            'mapel' => $mapelAgama->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'formatif' => [
                $ks->id => [
                    7 => 80,
                    8 => 80,
                ],
            ],
            'praktik_1' => [
                $ks->id => 90,
            ],
            'praktik_2' => [
                $ks->id => 90,
            ],
        ]);

        $saveResp->assertSessionHas('success');

        // Nilai praktikum 1 & 2 tersimpan di nilai_rapor_mapel
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapelAgama->id)->first();
        $this->assertNotNull($rapor);
        $this->assertEquals(90.0, (float) $rapor->praktik_1);
        $this->assertEquals(90.0, (float) $rapor->praktik_2);
        // Rata-rata formatif mencakup (80 + 80 + 90 + 90) / 4 = 85.0
        $this->assertEquals(85.0, (float) $rapor->rata_formatif);

        // Cetak lembar formatif siswa PDF
        $pdfResp = $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-formatif-siswa-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapelAgama->id,
            'kelasSiswa' => $ks->id,
        ]));
        $pdfResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp->headers->get('Content-Type'));
    }

    public function test_pjok_menampilkan_dua_nilai_formatif_dan_praktik_setiap_bulan(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapelPjok = MataPelajaran::where('nama', 'like', '%PJOK%')->orWhere('nama', 'like', '%Jasmani%')->first();
        $this->assertNotNull($mapelPjok);

        $this->assertEquals('pjok', $mapelPjok->getJenisFormatif());
        $this->assertFalse($mapelPjok->isAgama());
        $this->assertTrue($mapelPjok->isPjok());

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapelPjok->id,
            'tab' => 'formatif',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Format Khusus PJOK');
        $response->assertSee('Fmt');
        $response->assertSee('Prak');

        // Simpan nilai PJOK: formatif teori & praktikum jasmani per bulan
        $ks = $kelas->kelasSiswa()->first();
        $saveResp = $this->actingAs($admin)->post(route('kelas.mapel.nilai.formatif', [
            'kelas' => $kelas->id,
            'mapel' => $mapelPjok->id,
        ]), [
            'tahun' => now()->year,
            'semester' => 'ganjil',
            'formatif' => [
                $ks->id => [
                    7 => 75,
                    8 => 85,
                ],
            ],
            'praktik' => [
                $ks->id => [
                    7 => 85,
                    8 => 95,
                ],
            ],
        ]);

        $saveResp->assertSessionHas('success');

        // Nilai praktik tersimpan di nilai_formatif_bulanan
        $fb7 = NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapelPjok->id)->where('bulan', 7)->first();
        $this->assertNotNull($fb7);
        $this->assertEquals(75.0, (float) $fb7->nilai);
        $this->assertEquals(85.0, (float) $fb7->nilai_praktik);

        $fb8 = NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapelPjok->id)->where('bulan', 8)->first();
        $this->assertNotNull($fb8);
        $this->assertEquals(85.0, (float) $fb8->nilai);
        $this->assertEquals(95.0, (float) $fb8->nilai_praktik);

        // Rata-rata formatif = (75 + 85 + 85 + 95) / 4 = 85.0
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)->where('mapel_id', $mapelPjok->id)->first();
        $this->assertNotNull($rapor);
        $this->assertEquals(85.0, (float) $rapor->rata_formatif);

        // Cetak lembar formatif siswa PDF
        $pdfResp = $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-formatif-siswa-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapelPjok->id,
            'kelasSiswa' => $ks->id,
        ]));
        $pdfResp->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResp->headers->get('Content-Type'));
    }
}
