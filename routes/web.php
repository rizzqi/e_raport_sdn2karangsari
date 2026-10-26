<?php

use App\Http\Controllers\AbsensiBulananController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\PenempatanSiswaController;
use App\Http\Controllers\RaportController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Rekap, Unduh Raport Kelas, Daftar Hadir Ujian, & Absensi Bulanan
    Route::prefix('kelas/{kelas}')->group(function () {
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.kelas');
        Route::get('/raport/cetak-sekelas', [RaportController::class, 'cetakKelas'])->name('raport.cetak.kelas');
        Route::get('/raport/download-kelas-pdf', [RaportController::class, 'downloadKelasPdf'])->name('raport.download.kelas.pdf');
        Route::get('/raport/download-kelas-docx', [RaportController::class, 'downloadKelasDocx'])->name('raport.download.kelas.docx');
        Route::get('/daftar-hadir', [KelasController::class, 'daftarHadir'])->name('kelas.daftar-hadir');
        Route::get('/daftar-hadir/excel', [KelasController::class, 'downloadDaftarHadirExcel'])->name('kelas.daftar-hadir.excel');

        // Absensi Bulanan (Admin & Wali Kelas)
        Route::get('/absensi-bulanan', [AbsensiBulananController::class, 'index'])->name('kelas.absensi.index');
        Route::post('/absensi-bulanan', [AbsensiBulananController::class, 'simpan'])->name('kelas.absensi.simpan');
        Route::post('/absensi-bulanan/sync', [AbsensiBulananController::class, 'syncRaport'])->name('kelas.absensi.sync');
        Route::get('/absensi-bulanan/cetak', [AbsensiBulananController::class, 'cetak'])->name('kelas.absensi.cetak');
    });

    // Modul Raport Preview & Download (Akses Semua Peran)
    Route::prefix('raport')->group(function () {
        Route::get('/preview/{kelasSiswa}', [RaportController::class, 'preview'])->name('raport.preview');
        Route::get('/download-docx/{kelasSiswa}', [RaportController::class, 'downloadDocx'])->name('raport.download.docx');
        Route::get('/download-pdf/{kelasSiswa}', [RaportController::class, 'downloadPdf'])->name('raport.download.pdf');
        Route::get('/riwayat', [RaportController::class, 'riwayatCetak'])->name('raport.riwayat');
    });

    // Modul Kelas & Siswa (Lihat Data)
    Route::resource('kelas', KelasController::class)->parameters(['kelas' => 'kelas'])->only(['index', 'show']);
    Route::put('/kelas/{kelas}/wali', [KelasController::class, 'updateWali'])->name('kelas.update.wali');
    Route::put('/kelas/{kelas}/tanggal-raport', [KelasController::class, 'updateTanggalRaport'])->name('kelas.tanggal-raport.update');
    Route::put('/kelas/{kelas}/guru-pengampu', [KelasController::class, 'updateGuruPengampu'])->name('kelas.guru-pengampu.update');
    Route::put('/kelas/{kelas}/kkm', [KelasController::class, 'updateKkmMapel'])->name('kelas.kkm.update');
    Route::get('/kelas/{kelas}/penempatan', [PenempatanSiswaController::class, 'index'])->name('penempatan.index');
    Route::get('/siswa/template-import', [SiswaController::class, 'downloadTemplate'])->name('siswa.template.import');
    Route::resource('siswa', SiswaController::class)->only(['index', 'show']);

    // Modul Input Nilai (Per Siswa, Matriks Kelas, & Import Excel) - Dapat diakses Admin & Wali Kelas bersangkutan
    Route::middleware('role:admin,wali')->prefix('kelas/{kelas}')->group(function () {
        Route::get('/nilai/siswa/{kelasSiswa}', [NilaiController::class, 'inputSiswa'])->name('nilai.input.siswa');
        Route::post('/nilai/siswa/{kelasSiswa}', [NilaiController::class, 'simpanSiswa'])->name('nilai.simpan.siswa');

        Route::get('/nilai/matriks', [NilaiController::class, 'inputKelas'])->name('nilai.input.kelas');
        Route::post('/nilai/matriks', [NilaiController::class, 'simpanKelas'])->name('nilai.simpan.kelas');

        Route::get('/nilai/template-excel', [NilaiController::class, 'downloadTemplateExcel'])->name('nilai.template.excel');
        Route::post('/nilai/import-excel', [NilaiController::class, 'importExcel'])->name('nilai.import.excel');
        Route::post('/nilai/kosongkan', [NilaiController::class, 'kosongkanKelas'])->name('nilai.kosongkan.kelas');

        // Modul Kehadiran (SIA) & Karakter Pembiasaan
        Route::get('/sia-pembiasaan', [NilaiController::class, 'inputSiaPembiasaan'])->name('kelas.sia-pembiasaan.index');
        Route::post('/sia-pembiasaan', [NilaiController::class, 'simpanSiaPembiasaan'])->name('kelas.sia-pembiasaan.simpan');
        Route::get('/sia-pembiasaan/template', [NilaiController::class, 'downloadTemplateSiaPembiasaan'])->name('kelas.sia-pembiasaan.template');
        Route::post('/sia-pembiasaan/import', [NilaiController::class, 'importSiaPembiasaan'])->name('kelas.sia-pembiasaan.import');
        Route::post('/sia-pembiasaan/tarik-absensi', [NilaiController::class, 'tarikDariAbsensiBulanan'])->name('kelas.sia-pembiasaan.tarik-absensi');
    });

    // HANYA ADMIN YANG DAPAT MENGINPUT & MENGUBAH DATA MASTER / PENGGUNA
    Route::middleware('role:admin')->group(function () {

        // Penempatan Siswa ke Kelas
        Route::post('/kelas/{kelas}/penempatan', [PenempatanSiswaController::class, 'assign'])->name('penempatan.assign');
        Route::delete('/kelas/{kelas}/penempatan/{kelasSiswa}', [PenempatanSiswaController::class, 'remove'])->name('penempatan.remove');

        // Input & Edit Kelas
        Route::post('/kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
        Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

        // Input & Edit Siswa
        Route::post('/siswa', [SiswaController::class, 'store'])->name('siswa.store');
        Route::put('/siswa/{siswa}', [SiswaController::class, 'update'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');
        Route::post('/siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
        Route::post('/siswa/import-clipboard', [SiswaController::class, 'importClipboard'])->name('siswa.import.clipboard');

        // Identitas Sekolah
        Route::get('/sekolah/profil', [SekolahController::class, 'edit'])->name('sekolah.edit');
        Route::post('/sekolah/profil', [SekolahController::class, 'update'])->name('sekolah.update');

        // Tahun Ajaran
        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->parameters(['tahun-ajaran' => 'tahunAjaran'])
            ->only(['index', 'store', 'destroy']);
        Route::post('/tahun-ajaran/{tahunAjaran}/set-aktif', [TahunAjaranController::class, 'setAktif'])->name('tahun-ajaran.set-aktif');

        // Mata Pelajaran & Pembiasaan
        Route::get('/mapel-pembiasaan', [MataPelajaranController::class, 'index'])->name('mapel.index');
        Route::post('/mapel', [MataPelajaranController::class, 'storeMapel'])->name('mapel.store');
        Route::put('/mapel/{mapel}', [MataPelajaranController::class, 'updateMapel'])->name('mapel.update');
        Route::post('/mapel/{mapel}/toggle-status', [MataPelajaranController::class, 'toggleStatusMapel'])->name('mapel.toggle-status');
        Route::delete('/mapel/{mapel}', [MataPelajaranController::class, 'destroyMapel'])->name('mapel.destroy');

        Route::post('/pembiasaan', [MataPelajaranController::class, 'storePembiasaan'])->name('pembiasaan.store');
        Route::put('/pembiasaan/{pembiasaan}', [MataPelajaranController::class, 'updatePembiasaan'])->name('pembiasaan.update');
        Route::post('/pembiasaan/{pembiasaan}/toggle-status', [MataPelajaranController::class, 'toggleStatusPembiasaan'])->name('pembiasaan.toggle-status');
        Route::delete('/pembiasaan/{pembiasaan}', [MataPelajaranController::class, 'destroyPembiasaan'])->name('pembiasaan.destroy');

        // Manajemen Pengguna
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });
});
