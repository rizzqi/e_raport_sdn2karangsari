<?php

namespace Tests\Feature;

use App\Models\AbsensiHarian;
use App\Models\Kelas;
use App\Models\KelasGuru;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PembatasanAksesAbsensiPenugasanTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_memiliki_akses_penuh_semua_kelas_dan_tipe(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        $kelasList = Kelas::all();
        foreach ($kelasList as $kelas) {
            $this->assertTrue($admin->canInputAbsensi($kelas, 'wali'));
            $this->assertTrue($admin->canInputAbsensi($kelas, 'agama'));
            $this->assertTrue($admin->canInputAbsensi($kelas, 'pjok'));
        }
    }

    public function test_hub_absensi_hanya_menampilkan_kelas_yang_diampu_oleh_guru_login(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%1%')->first();
        $kelas2 = Kelas::where('nama', 'like', '%2%')->first() ?? Kelas::where('id', '!=', $kelas1->id)->first();
        $wali1 = $kelas1->wali;

        // Wali Kelas 1 membuka Hub Absensi Harian
        $response = $this->actingAs($wali1)->get(route('absensi-harian.index'));

        $response->assertStatus(200);
        // Harus melihat kelasnya sendiri
        $response->assertSee('Kelas '.$kelas1->nama);
        // TIDAK BOLEH menampilkan kelas lain yang tidak diampu
        $response->assertDontSee('Kelas '.$kelas2->nama);

        // Sedangkan Admin melihat semua kelas
        $admin = User::where('role', 'admin')->first();
        $responseAdmin = $this->actingAs($admin)->get(route('absensi-harian.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Kelas '.$kelas1->nama);
        $responseAdmin->assertSee('Kelas '.$kelas2->nama);
    }

    public function test_wali_kelas_dapat_mengisi_wali_agama_dan_pjok_di_kelas_sendiri(): void
    {
        $kelas = Kelas::where('nama', 'like', '%1%')->first() ?? Kelas::first();
        $wali = $kelas->wali;
        $this->assertNotNull($wali);

        // Di kelas sendiri: boleh isi Wali, Agama, dan PJOK
        $this->assertTrue($wali->canInputAbsensi($kelas, 'wali'));
        $this->assertTrue($wali->canInputAbsensi($kelas, 'agama'));
        $this->assertTrue($wali->canInputAbsensi($kelas, 'pjok'));

        // Tes buka halaman GET: status 200 OK dan view dimuat sempurna
        $resGet = $this->actingAs($wali)->get(route('kelas.absensi-harian.index', [
            'kelas' => $kelas->id,
            'tipe' => 'wali',
        ]));
        $resGet->assertStatus(200);
        $resGet->assertSee($kelas->nama);

        // Tes simpan backend di kelas sendiri
        $ks = $kelas->kelasSiswa()->first();
        $responseWali = $this->actingAs($wali)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'wali',
            'status' => [$ks->id => 'H'],
        ]);
        $responseWali->assertRedirect();

        $responseAgama = $this->actingAs($wali)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'agama',
            'status' => [$ks->id => 'H'],
        ]);
        $responseAgama->assertRedirect();
    }

    public function test_wali_kelas_ditolak_mengisi_di_kelas_lain_jika_tidak_mengampu(): void
    {
        $kelas1 = Kelas::where('nama', 'like', '%1%')->first();
        $kelas2 = Kelas::where('nama', 'like', '%2%')->first() ?? Kelas::where('id', '!=', $kelas1->id)->first();
        $wali1 = $kelas1->wali;

        // Pastikan wali1 bukan pengampu di kelas2
        $this->assertNotEquals($kelas2->wali_id, $wali1->id);

        // Wali 1 tidak boleh mengisi 'wali' di Kelas 2
        $this->assertFalse($wali1->canInputAbsensi($kelas2, 'wali'));

        // Coba simpan backend: harus menghasilkan HTTP 403 Forbidden
        $response = $this->actingAs($wali1)->post(route('kelas.absensi-harian.simpan', $kelas2->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'wali',
            'status' => [999 => 'H'],
        ]);

        $response->assertStatus(403);
    }

    public function test_guru_pengampu_hanya_dapat_mengisi_mapel_yang_diampu_di_kelas_lain(): void
    {
        $kelas = Kelas::first();
        $mapelAgama = $kelas->getMapelAgama();
        $this->assertNotNull($mapelAgama);

        // Buat user guru agama khusus (bukan wali kelas ini)
        $guruAgama = User::firstOrCreate(
            ['email' => 'guru.agama.test@sdn2karangsari.sch.id'],
            [
                'name' => 'Bapak Ustadz, S.Pd.I',
                'password' => Hash::make('password'),
                'role' => 'guru',
            ]
        );

        // Berikan penugasan hanya untuk mapel Agama di kelas ini
        KelasGuru::updateOrCreate([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapelAgama->id,
        ], [
            'user_id' => $guruAgama->id,
            'nama' => $guruAgama->name,
            'nip' => '-',
            'urutan' => 1,
        ]);

        $kelas->load('guruPengampu');

        // Boleh isi Agama
        $this->assertTrue($guruAgama->canInputAbsensi($kelas, 'agama'));

        // Ditolak isi Wali dan PJOK
        $this->assertFalse($guruAgama->canInputAbsensi($kelas, 'wali'));
        $this->assertFalse($guruAgama->canInputAbsensi($kelas, 'pjok'));

        $ks = $kelas->kelasSiswa()->first();

        // Simpan Agama berhasil
        $resAgama = $this->actingAs($guruAgama)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'agama',
            'status' => [$ks->id => 'H'],
        ]);
        $resAgama->assertRedirect();

        // Simpan PJOK ditolak (HTTP 403)
        $resPjok = $this->actingAs($guruAgama)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'pjok',
            'status' => [$ks->id => 'H'],
        ]);
        $resPjok->assertStatus(403);

        // Simpan Wali ditolak (HTTP 403)
        $resWali = $this->actingAs($guruAgama)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => now()->format('Y-m-d'),
            'tipe' => 'wali',
            'status' => [$ks->id => 'H'],
        ]);
        $resWali->assertStatus(403);
    }

    public function test_hapus_absensi_tanggal_mematuhi_otorisasi_dan_mengosongkan_data(): void
    {
        $kelas = Kelas::where('nama', 'like', '%1%')->first() ?? Kelas::first();
        $wali = $kelas->wali;
        $ks = $kelas->kelasSiswa()->first();
        $tanggal = now()->format('Y-m-d');

        // 1. Wali simpan absensi
        $this->actingAs($wali)->post(route('kelas.absensi-harian.simpan', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
            'status' => [$ks->id => 'S'],
        ])->assertRedirect();

        $this->assertEquals(1, AbsensiHarian::where('kelas_siswa_id', $ks->id)
            ->whereDate('tanggal', $tanggal)
            ->where('status', 'S')
            ->whereNull('mapel_id')
            ->count());

        // 2. User lain tanpa penugasan mencoba menghapus -> 403
        $randomUser = User::factory()->create(['role' => 'wali']);
        $this->actingAs($randomUser)->delete(route('kelas.absensi-harian.hapus-tanggal', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
        ])->assertStatus(403);

        // 3. Wali yang sah menghapus absensi tanggal ini -> Sukses
        $resDelete = $this->actingAs($wali)->delete(route('kelas.absensi-harian.hapus-tanggal', $kelas->id), [
            'tanggal' => $tanggal,
            'tipe' => 'wali',
        ]);
        $resDelete->assertRedirect();

        // 4. Pastikan data di database sudah terhapus bersih
        $this->assertEquals(0, AbsensiHarian::where('kelas_siswa_id', $ks->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('mapel_id')
            ->count());
    }
}
