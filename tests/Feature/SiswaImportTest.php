<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_siswa_index_and_filter_by_kelas(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('siswa.index', ['kelas_id' => $kelas->id]));
        $response->assertStatus(200);
        $response->assertSee('Induk Data Peserta Didik');
        $response->assertSee('Impor Siswa Per Kelas');
    }

    public function test_admin_can_download_import_template(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $response = $this->actingAs($admin)->get(route('siswa.template.import', ['kelas_id' => $kelas->id]));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_import_students_per_kelas_via_clipboard(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $pasteData = "Muhammad Zidan\t8801\t0123456788\nNur Fadilah\t8802\t0123456789";

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => $kelas->id,
            'data_teks' => $pasteData,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', [
            'nama' => 'Muhammad Zidan',
            'nis' => '8801',
        ]);
        $this->assertDatabaseHas('siswa', [
            'nama' => 'Nur Fadilah',
            'nis' => '8802',
        ]);

        $zidan = Siswa::where('nis', '8801')->first();
        $this->assertNotNull($zidan);
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas->id,
            'siswa_id' => $zidan->id,
        ]);
    }

    public function test_admin_can_import_students_per_kelas_via_csv_file(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        $csvContent = "No,Nama Siswa,NIS,NISN\n1,Rizky Ananda,8803,0123456790\n2,Tiara Lestari,8804,0123456791";
        $file = UploadedFile::fake()->createWithContent('siswa_kelas.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => $kelas->id,
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswa', ['nis' => '8803', 'nama' => 'Rizky Ananda']);
        $this->assertDatabaseHas('siswa', ['nis' => '8804', 'nama' => 'Tiara Lestari']);

        $tiara = Siswa::where('nis', '8804')->first();
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas->id,
            'siswa_id' => $tiara->id,
        ]);
    }

    public function test_import_existing_student_assigns_to_new_class(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas1 = Kelas::first();
        $kelas2 = Kelas::skip(1)->first();
        $this->assertNotNull($kelas2);

        // Siswa sudah ada
        $existing = Siswa::create([
            'nama' => 'Siswa Lama',
            'nis' => '8899',
            'status' => 'aktif',
        ]);

        // Import dengan NIS yang sama ke kelas 2
        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => $kelas2->id,
            'data_teks' => "Siswa Lama\t8899\t0123456799",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas2->id,
            'siswa_id' => $existing->id,
        ]);
    }

    public function test_admin_can_import_students_auto_distributed_by_kelas_column_in_csv(): void
    {
        $admin = User::where('role', 'admin')->first();

        $csvContent = "No,Nama Siswa,NIS,NISN,Kelas\n1,Budi Kelas Satu,9101,0111111111,Kelas 1\n2,Ani Kelas Empat,9401,0222222222,Kelas 4\n3,Cici Kelas Dua,9201,0333333333,Kelas 2";
        $file = UploadedFile::fake()->createWithContent('siswa_multi_kelas.csv', $csvContent);

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => 'auto',
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $budi = Siswa::where('nis', '9101')->first();
        $ani = Siswa::where('nis', '9401')->first();
        $cici = Siswa::where('nis', '9201')->first();

        $this->assertNotNull($budi);
        $this->assertNotNull($ani);
        $this->assertNotNull($cici);

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $kelas4 = Kelas::where('nama', 'Kelas 4')->first();
        $kelas2 = Kelas::where('nama', 'Kelas 2')->first();

        $this->assertNotNull($kelas1);
        $this->assertNotNull($kelas4);
        $this->assertNotNull($kelas2);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $budi->id,
        ]);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas4->id,
            'siswa_id' => $ani->id,
        ]);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas2->id,
            'siswa_id' => $cici->id,
        ]);
    }

    public function test_admin_can_import_students_auto_distributed_via_clipboard(): void
    {
        $admin = User::where('role', 'admin')->first();

        $clipboard = "Doni Satu\t9102\t0111111112\tKelas 1\nCitra Empat\t9402\t0222222223\tKelas 4";

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => 'auto',
            'data_teks' => $clipboard,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $doni = Siswa::where('nis', '9102')->first();
        $citra = Siswa::where('nis', '9402')->first();

        $this->assertNotNull($doni);
        $this->assertNotNull($citra);

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $kelas4 = Kelas::where('nama', 'Kelas 4')->first();

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $doni->id,
        ]);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas4->id,
            'siswa_id' => $citra->id,
        ]);
    }

    public function test_admin_can_import_dapodik_export_file_and_distribute_per_kelas(): void
    {
        $admin = User::where('role', 'admin')->first();

        $kelas1 = Kelas::where('nama', 'Kelas 1')->first();
        $kelas4 = Kelas::where('nama', 'Kelas 4')->first();

        $dapodikCsv = implode("\n", [
            'Daftar Peserta Didik',
            'SD NEGERI 2 KARANGSARI',
            'Kecamatan Sempu',
            'Tanggal Unduh: 2026-08-25',
            'No,Nama,NIPD,JK,NISN,Tempat Lahir,Rombel Saat Ini',
            ',,,,,,',
            '1,Ahmad Dapodik,8101,L,0111118101,Banyuwangi,Kelas 1',
            '2,Budi Dapodik,8401,L,0111118401,Banyuwangi,Kelas 4',
            '3,Cinta Dapodik,8201,P,0111118201,Banyuwangi,Kelas 2',
        ]);

        $file = UploadedFile::fake()->createWithContent('daftar_pd.csv', $dapodikCsv);

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => 'auto',
            'file_excel' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $ahmad = Siswa::where('nis', '8101')->first();
        $budi = Siswa::where('nis', '8401')->first();
        $cinta = Siswa::where('nis', '8201')->first();

        $this->assertNotNull($ahmad);
        $this->assertNotNull($budi);
        $this->assertNotNull($cinta);

        $kelas2 = Kelas::where('nama', 'Kelas 2')->first();
        $this->assertNotNull($kelas2);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas1->id,
            'siswa_id' => $ahmad->id,
        ]);
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas4->id,
            'siswa_id' => $budi->id,
        ]);
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas2->id,
            'siswa_id' => $cinta->id,
        ]);
    }

    public function test_admin_can_import_students_without_nis_and_auto_generates_temporary_nis(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kelas = Kelas::first();

        // Clipboard data with Name only, no NIS
        $pasteData = "Murid Tanpa NIS Satu\t\t\nMurid Tanpa NIS Dua\t\t";

        $response = $this->actingAs($admin)->post(route('siswa.import'), [
            'kelas_id' => $kelas->id,
            'data_teks' => $pasteData,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $s1 = Siswa::where('nama', 'Murid Tanpa NIS Satu')->first();
        $s2 = Siswa::where('nama', 'Murid Tanpa NIS Dua')->first();

        $this->assertNotNull($s1);
        $this->assertNotNull($s2);
        $this->assertStringStartsWith('TMP-', $s1->nis);
        $this->assertStringStartsWith('TMP-', $s2->nis);
        $this->assertNotEquals($s1->nis, $s2->nis);

        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas->id,
            'siswa_id' => $s1->id,
        ]);
        $this->assertDatabaseHas('kelas_siswa', [
            'kelas_id' => $kelas->id,
            'siswa_id' => $s2->id,
        ]);
    }
}
