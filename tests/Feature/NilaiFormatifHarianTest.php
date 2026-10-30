<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiFormatifHarian;
use App\Models\NilaiRaporMapel;
use App\Models\PenilaianFormatifHarian;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class NilaiFormatifHarianTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_formatif_harian_dapat_diakses(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => 2026,
            'bulan' => 8,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rincian Penilaian Harian');
        $response->assertSee($mapel->nama);
        $response->assertSee('Agustus');
        $response->assertSee('Tambah Penilaian Harian');
    }

    public function test_tambah_update_dan_hapus_penilaian_formatif_harian(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();

        // 1. Tambah Penilaian Harian
        $response = $this->actingAs($admin)->post(route('kelas.mapel.formatif-harian.tambah-penilaian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'nama' => 'Harian 1 (TP 1)',
            'tanggal' => '2026-08-10',
            'lingkup_materi' => 'Teks Deskripsi',
            'jenis' => 'teori',
            'bobot' => 1.0,
            'tahun' => 2026,
            'bulan' => 8,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('penilaian_formatif_harian', [
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'nama' => 'Harian 1 (TP 1)',
            'lingkup_materi' => 'Teks Deskripsi',
            'tahun' => 2026,
            'bulan' => 8,
        ]);

        $penilaian = PenilaianFormatifHarian::where('nama', 'Harian 1 (TP 1)')->first();
        $this->assertNotNull($penilaian);

        // 2. Update Penilaian Harian
        $responseEdit = $this->actingAs($admin)->put(route('kelas.mapel.formatif-harian.update-penilaian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'penilaian' => $penilaian->id,
        ]), [
            'nama' => 'Harian 1 (Revisi TP 1)',
            'tanggal' => '2026-08-12',
            'lingkup_materi' => 'Teks Deskripsi Lanjutan',
            'jenis' => 'teori',
            'bobot' => 1.0,
        ]);

        $responseEdit->assertRedirect();
        $penilaian->refresh();
        $this->assertEquals('Harian 1 (Revisi TP 1)', $penilaian->nama);
        $this->assertEquals('Teks Deskripsi Lanjutan', $penilaian->lingkup_materi);

        // 3. Hapus Penilaian Harian
        $responseDelete = $this->actingAs($admin)->delete(route('kelas.mapel.formatif-harian.hapus-penilaian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'penilaian' => $penilaian->id,
        ]));

        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('penilaian_formatif_harian', [
            'id' => $penilaian->id,
        ]);
    }

    public function test_simpan_nilai_harian_otomatis_menghitung_rata_rata_bulanan_dan_masuk_ke_rapor(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();
        $ks = $kelas->kelasSiswa()->first();
        $this->assertNotNull($ks);

        // Buat 2 penilaian harian di bulan Agustus (Bulan 8)
        $p1 = PenilaianFormatifHarian::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tahun' => 2026,
            'bulan' => 8,
            'nama' => 'Harian 1',
            'jenis' => 'teori',
            'urutan' => 1,
        ]);

        $p2 = PenilaianFormatifHarian::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tahun' => 2026,
            'bulan' => 8,
            'nama' => 'Harian 2',
            'jenis' => 'teori',
            'urutan' => 2,
        ]);

        // Kirim nilai: Harian 1 = 80, Harian 2 = 90 (Rata-rata harus 85.0)
        $response = $this->actingAs($admin)->post(route('kelas.mapel.formatif-harian.simpan', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
        ]), [
            'tahun' => 2026,
            'bulan' => 8,
            'nilai' => [
                $p1->id => [$ks->id => 80],
                $p2->id => [$ks->id => 90],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 1. Verifikasi nilai harian asli tersimpan di database
        $this->assertDatabaseHas('nilai_formatif_harian', [
            'penilaian_formatif_harian_id' => $p1->id,
            'kelas_siswa_id' => $ks->id,
            'nilai' => 80,
        ]);
        $this->assertDatabaseHas('nilai_formatif_harian', [
            'penilaian_formatif_harian_id' => $p2->id,
            'kelas_siswa_id' => $ks->id,
            'nilai' => 90,
        ]);

        // 2. Verifikasi NilaiFormatifBulanan otomatis terisi rata-ratanya (85.0)
        $bulanan = NilaiFormatifBulanan::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', 2026)
            ->where('bulan', 8)
            ->first();

        $this->assertNotNull($bulanan);
        $this->assertEquals(85.0, (float) $bulanan->nilai);

        // 3. Verifikasi otomatis sinkron ke NilaiRaporMapel (semester)
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $ks->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', 2026)
            ->where('semester', 'ganjil')
            ->first();

        $this->assertNotNull($rapor);
        $this->assertEquals(85.0, (float) $rapor->rata_formatif);
        $this->assertNotNull($rapor->nilai_rapor);
    }

    public function test_cetak_lembar_penilaian_harian_berhasil(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.formatif-harian.cetak', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => 2026,
            'bulan' => 8,
        ]));

        $response->assertStatus(200);
        $response->assertSee('DAFTAR NILAI PENILAIAN HARIAN FORMATIF PESERTA DIDIK');
        $response->assertSee($mapel->nama);
        $response->assertSee('Agustus');
    }

    public function test_cetak_lembar_nilai_uts_pdf_berhasil(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-uts-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cetak_lembar_nilai_uas_pdf_berhasil(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();
        $mapel = $kelas->mataPelajaran()->first();

        $response = $this->actingAs($admin)->get(route('kelas.mapel.nilai.cetak-uas-pdf', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => 2026,
            'semester' => 'ganjil',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
