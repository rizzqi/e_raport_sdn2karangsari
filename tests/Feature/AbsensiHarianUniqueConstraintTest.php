<?php

namespace Tests\Feature;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Pengujian Proteksi Duplikasi Presensi (Fase 2 – Unique Constraint Absensi Harian).
 *
 * Memverifikasi bahwa:
 * 1. Tidak dapat menyimpan dua record absensi umum (wali kelas / mapel_id NULL)
 *    untuk siswa dan tanggal yang sama → database-level guard.
 * 2. Tidak dapat menyimpan dua record absensi mapel yang sama (kelas_siswa_id,
 *    mapel_id, tanggal) → database-level guard.
 * 3. Siswa yang sama boleh memiliki absensi umum DAN absensi mapel pada hari yang
 *    sama tanpa konflik.
 * 4. Controller `simpan()` menggunakan updateOrCreate sehingga double-submit hanya
 *    memperbarui record yang ada, bukan menciptakan duplikat.
 * 5. Controller `simpan()` menolak penyimpanan untuk siswa yang bukan anggota kelas
 *    (parameter tampering / IDOR protection).
 */
class AbsensiHarianUniqueConstraintTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 1. Database-level: unique constraint absensi umum (mapel_id IS NULL)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_duplikasi_absensi_umum_ditolak_oleh_database(): void
    {
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $tanggal = '2026-11-03';

        // Simpan pertama — harus berhasil
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        // Simpan kedua dengan data identik — harus ditolak database
        $this->expectException(UniqueConstraintViolationException::class);

        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => $tanggal,
            'status' => 'S', // status berbeda, tapi kunci sama
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 2. Database-level: unique constraint absensi mapel (mapel_id IS NOT NULL)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_duplikasi_absensi_mapel_ditolak_oleh_database(): void
    {
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $mapelAgama = $kelas->getMapelAgama();
        $this->assertNotNull($mapelAgama, 'Mata pelajaran Agama harus tersedia di fixture.');

        $tanggal = '2026-11-03';

        // Simpan pertama — harus berhasil
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapelAgama->id,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        // Simpan kedua dengan kunci identik — harus ditolak database
        $this->expectException(UniqueConstraintViolationException::class);

        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapelAgama->id,
            'tanggal' => $tanggal,
            'status' => 'A',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 3. Absensi umum dan absensi mapel pada hari yang sama TIDAK konflik
    // ──────────────────────────────────────────────────────────────────────────

    public function test_absensi_umum_dan_mapel_pada_hari_sama_tidak_konflik(): void
    {
        $kelas = Kelas::first();
        $ks = $kelas->kelasSiswa()->first();
        $mapelAgama = $kelas->getMapelAgama();
        $this->assertNotNull($mapelAgama);

        $tanggal = '2026-11-03';

        // Absensi umum (wali kelas)
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => null,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        // Absensi mapel Agama pada hari yang sama — tidak boleh konflik
        AbsensiHarian::create([
            'kelas_siswa_id' => $ks->id,
            'mapel_id' => $mapelAgama->id,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        $this->assertDatabaseCount('absensi_harian', 2);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 4. Controller: double-submit hanya memperbarui, tidak menciptakan duplikat
    // ──────────────────────────────────────────────────────────────────────────

    public function test_double_submit_absensi_hanya_memperbarui_bukan_duplikat(): void
    {
        $kelas = Kelas::first();
        $admin = User::where('role', 'admin')->first();
        $ks = $kelas->kelasSiswa()->first();
        $tanggal = now()->format('Y-m-d');

        $payload = [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
            'status' => [$ks->id => 'H'],
            'keterangan' => [],
        ];

        // Submit pertama
        $this->actingAs($admin)
            ->post(route('kelas.absensi-harian.simpan', $kelas->id), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        // Submit kedua dengan status berbeda (simulasi double-click / update)
        $payload['status'][$ks->id] = 'S';
        $this->actingAs($admin)
            ->post(route('kelas.absensi-harian.simpan', $kelas->id), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        // Harus tetap hanya 1 record, dengan status terbaru
        $records = AbsensiHarian::where('kelas_siswa_id', $ks->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('mapel_id')
            ->get();

        $this->assertCount(1, $records, 'Harus ada tepat 1 record, bukan duplikat.');
        $this->assertEquals('S', $records->first()->status, 'Status harus diperbarui ke nilai terbaru.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 5. Controller: parameter tampering / IDOR — siswa kelas lain diabaikan
    // ──────────────────────────────────────────────────────────────────────────

    public function test_parameter_tampering_siswa_kelas_lain_diabaikan(): void
    {
        $kelasList = Kelas::withCount('kelasSiswa')->get();

        // Butuh minimal 2 kelas dengan masing-masing minimal 1 siswa
        if ($kelasList->count() < 2) {
            $this->markTestSkipped('Dibutuhkan minimal 2 kelas untuk pengujian ini.');
        }

        $kelasPenyerang = $kelasList->get(0);
        $kelasKorban = $kelasList->get(1);

        $siswaKorban = $kelasKorban->kelasSiswa()->first();
        if (! $siswaKorban) {
            $this->markTestSkipped('Kelas korban tidak memiliki siswa.');
        }

        $admin = User::where('role', 'admin')->first();
        $tanggal = now()->format('Y-m-d');

        // Menyuntikkan ID siswa kelas lain ke payload kelas yang dibuka
        $response = $this->actingAs($admin)
            ->post(route('kelas.absensi-harian.simpan', $kelasPenyerang->id), [
                'tanggal' => $tanggal,
                'tipe' => 'wali',
                'status' => [$siswaKorban->id => 'A'], // ID siswa dari kelas lain
                'keterangan' => [],
            ]);

        $response->assertRedirect();

        // Pastikan absensi siswa kelas korban TIDAK tersimpan
        $this->assertDatabaseMissing('absensi_harian', [
            'kelas_siswa_id' => $siswaKorban->id,
            'tanggal' => $tanggal,
            'status' => 'A',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 6. Absensi siswa berbeda pada hari dan mapel yang sama tidak konflik
    // ──────────────────────────────────────────────────────────────────────────

    public function test_absensi_siswa_berbeda_pada_hari_sama_tidak_konflik(): void
    {
        $kelas = Kelas::first();
        $ksList = $kelas->kelasSiswa()->take(2)->get();

        if ($ksList->count() < 2) {
            $this->markTestSkipped('Dibutuhkan minimal 2 siswa dalam kelas untuk pengujian ini.');
        }

        $tanggal = '2026-11-03';

        AbsensiHarian::create([
            'kelas_siswa_id' => $ksList[0]->id,
            'mapel_id' => null,
            'tanggal' => $tanggal,
            'status' => 'H',
        ]);

        AbsensiHarian::create([
            'kelas_siswa_id' => $ksList[1]->id,
            'mapel_id' => null,
            'tanggal' => $tanggal,
            'status' => 'S',
        ]);

        $this->assertDatabaseCount('absensi_harian', 2);
    }
}
