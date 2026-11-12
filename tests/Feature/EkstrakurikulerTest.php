<?php

namespace Tests\Feature;

use App\Models\Ekstrakurikuler;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\NilaiEkstrakurikuler;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class EkstrakurikulerTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_dapat_mengelola_master_ekstrakurikuler_dan_non_admin_ditolak(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $wali = User::where('role', 'wali')->firstOrFail();

        $indexResponse = $this->actingAs($admin)->get(route('mapel.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Kegiatan Ekstrakurikuler');
        $indexResponse->assertSee('Pramuka');

        // Non-admin ditolak (403)
        $this->actingAs($wali)->post(route('ekstrakurikuler.store'), [
            'nama' => 'Drumband',
            'urutan' => 5,
        ])->assertStatus(403);

        // Admin menambah ekstrakurikuler baru
        $this->actingAs($admin)->post(route('ekstrakurikuler.store'), [
            'nama' => 'Drumband',
            'urutan' => 5,
        ])->assertRedirect(route('mapel.index'));

        $drumband = Ekstrakurikuler::where('nama', 'Drumband')->firstOrFail();
        $this->assertTrue($drumband->aktif);

        // Update nama & urutan
        $this->actingAs($admin)->put(route('ekstrakurikuler.update', $drumband->id), [
            'nama' => 'Drumband Sekolah',
            'urutan' => 6,
        ])->assertRedirect(route('mapel.index'));

        $this->assertDatabaseHas('ekstrakurikuler', [
            'id' => $drumband->id,
            'nama' => 'Drumband Sekolah',
            'urutan' => 6,
        ]);

        // Toggle status nonaktif
        $this->actingAs($admin)->post(route('ekstrakurikuler.toggle-status', $drumband->id))
            ->assertRedirect(route('mapel.index'));
        $this->assertFalse($drumband->fresh()->aktif);

        // Delete
        $this->actingAs($admin)->delete(route('ekstrakurikuler.destroy', $drumband->id))
            ->assertRedirect(route('mapel.index'));
        $this->assertDatabaseMissing('ekstrakurikuler', ['id' => $drumband->id]);
    }

    public function test_wali_dan_admin_dapat_simpan_ekstrakurikuler_terpadu_dan_lintas_wali_ditolak(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $kelasLain = Kelas::where('id', '!=', $kelas->id)->firstOrFail();
        $waliLain = User::findOrFail($kelasLain->wali_id);
        $ks = KelasSiswa::where('kelas_id', $kelas->id)->firstOrFail();
        $mapel = $kelas->mataPelajaran()->first();
        $pramuka = Ekstrakurikuler::where('nama', 'Pramuka')->firstOrFail();
        $futsal = Ekstrakurikuler::where('nama', 'Olahraga / Futsal')->firstOrFail();

        // Buka via shortcut route kelas.ekstrakurikuler.index
        $pageResponse = $this->actingAs($admin)->get(route('kelas.ekstrakurikuler.index', $kelas->id));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Nilai Ekstrakurikuler Siswa');
        $pageResponse->assertSee('Pramuka');

        // Buka via tab di kelas.mapel.nilai
        $tabResponse = $this->actingAs($admin)->get(route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'ekstrakurikuler',
            'periode' => 'sts',
        ]));
        $tabResponse->assertStatus(200);
        $tabResponse->assertSee('Simpan Semua Ekstrakurikuler (STS)');

        // Isi data awal Futsal agar teruji penghapusan saat dikosongkan
        NilaiEkstrakurikuler::create([
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $futsal->id,
            'periode' => 'sts',
            'predikat' => 'Baik',
            'keterangan' => 'Pernah ikut',
        ]);

        // Simpan terpadu: Pramuka diisi, Futsal dikosongkan (- Tidak Ikut -)
        $this->actingAs($admin)->post(route('kelas.ekstrakurikuler.simpan', $kelas->id), [
            'periode' => 'sts',
            'tahun' => 2026,
            'ekskul' => [
                $ks->id => [
                    $pramuka->id => [
                        'predikat' => 'Sangat Baik',
                        'keterangan' => 'Aktif mengikuti perkemahan dan latihan rutin.',
                    ],
                    $futsal->id => [
                        'predikat' => '',
                        'keterangan' => '',
                    ],
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sts',
            'predikat' => 'Sangat Baik',
            'keterangan' => 'Aktif mengikuti perkemahan dan latihan rutin.',
        ]);

        $this->assertDatabaseMissing('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $futsal->id,
            'periode' => 'sts',
        ]);

        // Wali kelas lain dilarang simpan
        $this->actingAs($waliLain)->post(route('kelas.ekstrakurikuler.simpan', $kelas->id), [
            'periode' => 'sts',
            'ekskul' => [],
        ])->assertStatus(403);
    }

    public function test_autosave_ekstrakurikuler_simpan_dan_hapus_saat_kosong(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $kelasLain = Kelas::where('id', '!=', $kelas->id)->firstOrFail();
        $ks = KelasSiswa::where('kelas_id', $kelas->id)->firstOrFail();
        $ksLain = KelasSiswa::where('kelas_id', $kelasLain->id)->first();
        if (! $ksLain) {
            $siswaLain = Siswa::create([
                'nama' => 'Siswa Kelas Lain',
                'nis' => '99887766',
                'nisn' => '9999888877',
                'jenis_kelamin' => 'L',
            ]);
            $ksLain = KelasSiswa::create([
                'kelas_id' => $kelasLain->id,
                'siswa_id' => $siswaLain->id,
            ]);
        }
        $pramuka = Ekstrakurikuler::where('nama', 'Pramuka')->firstOrFail();

        // Autosave simpan baru
        $saveRes = $this->actingAs($admin)->postJson(route('kelas.mapel.nilai.autosave-ekstrakurikuler', $kelas->id), [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sas',
            'predikat' => 'Baik',
            'keterangan' => '',
        ]);

        $saveRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'saved',
            ]);

        $this->assertStringContainsString('Pramuka', (string) $saveRes->json('generated_keterangan'));

        $this->assertDatabaseHas('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sas',
            'predikat' => 'Baik',
            'keterangan' => null,
        ]);

        // Cross-class isolation (403)
        $this->actingAs($admin)->postJson(route('kelas.mapel.nilai.autosave-ekstrakurikuler', $kelas->id), [
            'kelas_siswa_id' => $ksLain->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sas',
            'predikat' => 'Baik',
        ])->assertStatus(403);

        // Autosave hapus saat dipilih "- Tidak Ikut -"
        $delRes = $this->actingAs($admin)->postJson(route('kelas.mapel.nilai.autosave-ekstrakurikuler', $kelas->id), [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sas',
            'predikat' => null,
            'keterangan' => null,
        ]);

        $delRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'deleted',
            ]);

        $this->assertDatabaseMissing('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sas',
        ]);
    }

    public function test_input_siswa_menyimpan_ekstrakurikuler_per_siswa(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $ks = KelasSiswa::where('kelas_id', $kelas->id)->firstOrFail();
        $pramuka = Ekstrakurikuler::where('nama', 'Pramuka')->firstOrFail();
        $btq = Ekstrakurikuler::where('nama', "Baca Tulis Al-Qur'an (BTQ)")->firstOrFail();

        $this->actingAs($admin)->get(route('nilai.input.siswa', [
            'kelas' => $kelas->id,
            'kelasSiswa' => $ks->id,
            'periode' => 'sts',
        ]))->assertStatus(200)->assertSee('Ekstrakurikuler');

        $this->actingAs($admin)->post(route('nilai.simpan.siswa', [
            'kelas' => $kelas->id,
            'kelasSiswa' => $ks->id,
        ]), [
            'periode' => 'sts',
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
            'ekskul' => [
                $pramuka->id => [
                    'predikat' => 'Cukup',
                    'keterangan' => 'Perlu lebih disiplin saat latihan baris.',
                ],
                $btq->id => [
                    'predikat' => '',
                    'keterangan' => '',
                ],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sts',
            'predikat' => 'Cukup',
            'keterangan' => 'Perlu lebih disiplin saat latihan baris.',
        ]);

        $this->assertDatabaseMissing('nilai_ekstrakurikuler', [
            'kelas_siswa_id' => $ks->id,
            'ekstrakurikuler_id' => $btq->id,
            'periode' => 'sts',
        ]);
    }

    public function test_ekstrakurikuler_tampil_di_raport_preview_cetak_kelas_lembar_sts_pdf_dan_docx(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kelas = Kelas::firstOrFail();
        $siswaKelas = KelasSiswa::where('kelas_id', $kelas->id)->orderBy('id')->take(2)->get();
        $this->assertCount(2, $siswaKelas);

        [$ks1, $ks2] = [$siswaKelas[0], $siswaKelas[1]];
        $pramuka = Ekstrakurikuler::where('nama', 'Pramuka')->firstOrFail();
        $futsal = Ekstrakurikuler::where('nama', 'Olahraga / Futsal')->firstOrFail();

        // Siswa 1 ikut 2 ekskul; Siswa 2 tidak ikut ekskul sama sekali
        NilaiEkstrakurikuler::create([
            'kelas_siswa_id' => $ks1->id,
            'ekstrakurikuler_id' => $pramuka->id,
            'periode' => 'sts',
            'predikat' => 'Sangat Baik',
            'keterangan' => null,
        ]);

        NilaiEkstrakurikuler::create([
            'kelas_siswa_id' => $ks1->id,
            'ekstrakurikuler_id' => $futsal->id,
            'periode' => 'sts',
            'predikat' => 'Baik',
            'keterangan' => 'Aktif latihan futsal setiap pekan.',
        ]);

        // 1. Preview Raport Siswa 1 (menampilkan Pramuka & Futsal saja, bukan BTQ/Seni Tari)
        $preview1 = $this->actingAs($admin)->get(route('raport.preview', ['kelasSiswa' => $ks1->id, 'periode' => 'sts']));
        $preview1->assertStatus(200);
        $preview1->assertSee('Kegiatan Ekstrakurikuler');
        $preview1->assertSee('Pramuka');
        $preview1->assertSee('Sangat aktif, disiplin, dan berprestasi dalam mengikuti kegiatan ekstrakurikuler Pramuka.');
        $preview1->assertSee('Olahraga / Futsal');
        $preview1->assertSee('Aktif latihan futsal setiap pekan.');
        $preview1->assertDontSee('Seni Tari &amp; Musik', false);

        // 2. Preview Raport Siswa 2 (fallback bersih)
        $preview2 = $this->actingAs($admin)->get(route('raport.preview', ['kelasSiswa' => $ks2->id, 'periode' => 'sts']));
        $preview2->assertStatus(200);
        $preview2->assertSee('Belum ada kegiatan ekstrakurikuler yang diikuti');

        // 3. Cetak Kelas HTML
        $cetakKelas = $this->actingAs($admin)->get(route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $cetakKelas->assertStatus(200);
        $cetakKelas->assertSee('Kegiatan Ekstrakurikuler');
        $cetakKelas->assertSee('Aktif latihan futsal setiap pekan.');
        $cetakKelas->assertSee('Belum ada kegiatan ekstrakurikuler yang diikuti');

        // 4. Lembar STS/SAS Cetak Kelas & Single
        $lembarKelas = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $lembarKelas->assertStatus(200);
        $lembarKelas->assertSee('Kegiatan Ekstrakurikuler');
        $lembarKelas->assertSee('Aktif latihan futsal setiap pekan.');

        $lembarSingle = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.cetak-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks1->id, 'periode' => 'sts']));
        $lembarSingle->assertStatus(200);
        $lembarSingle->assertSee('Aktif latihan futsal setiap pekan.');

        // 5. PDF Exports (Single, Kelas, Lembar STS Single)
        $pdfRes = $this->actingAs($admin)->get(route('raport.download.pdf', ['kelasSiswa' => $ks1->id, 'periode' => 'sts']));
        $pdfRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfRes->getContent());

        $pdfKelasRes = $this->actingAs($admin)->get(route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $pdfKelasRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfKelasRes->getContent());

        $lembarPdfRes = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.pdf-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks1->id, 'periode' => 'sts']));
        $lembarPdfRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $lembarPdfRes->getContent());

        $lembarKelasPdfRes = $this->actingAs($admin)->get(route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => 'sts']));
        $lembarKelasPdfRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $lembarKelasPdfRes->getContent());

        // 6. DOCX Export & verifikasi isi XML
        $docxRes = $this->actingAs($admin)->get(route('raport.download.docx', ['kelasSiswa' => $ks1->id, 'periode' => 'sts']));
        $docxRes->assertStatus(200);
        $docxFile = $docxRes->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($docxFile) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($docxFile);

        $this->assertStringContainsString('EKSTRAKURIKULER', $xml);
        $this->assertStringContainsString('Pramuka', $xml);
        $this->assertStringContainsString('Aktif latihan futsal setiap pekan.', $xml);
    }
}
