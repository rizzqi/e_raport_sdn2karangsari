<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\PenempatanSiswaController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\RaportController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UserController;

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

    // Modul Rekap & Unduh Raport Kelas (Akses Lihat Semua Peran)
    Route::prefix('kelas/{kelas}')->group(function () {
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.kelas');
        Route::get('/raport/download-kelas-docx', [RaportController::class, 'downloadKelasDocx'])->name('raport.download.kelas.docx');
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
    Route::get('/kelas/{kelas}/penempatan', [PenempatanSiswaController::class, 'index'])->name('penempatan.index');
    Route::resource('siswa', SiswaController::class)->only(['index', 'show']);

    // HANYA ADMIN YANG DAPAT MENGINPUT & MENGUBAH DATA
    Route::middleware('role:admin')->group(function () {
        // Modul Input Nilai (Per Siswa & Matriks Kelas)
        Route::prefix('kelas/{kelas}')->group(function () {
            Route::get('/nilai/siswa/{kelasSiswa}', [NilaiController::class, 'inputSiswa'])->name('nilai.input.siswa');
            Route::post('/nilai/siswa/{kelasSiswa}', [NilaiController::class, 'simpanSiswa'])->name('nilai.simpan.siswa');

            Route::get('/nilai/matriks', [NilaiController::class, 'inputKelas'])->name('nilai.input.kelas');
            Route::post('/nilai/matriks', [NilaiController::class, 'simpanKelas'])->name('nilai.simpan.kelas');
        });

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
        Route::delete('/mapel/{mapel}', [MataPelajaranController::class, 'destroyMapel'])->name('mapel.destroy');

        Route::post('/pembiasaan', [MataPelajaranController::class, 'storePembiasaan'])->name('pembiasaan.store');
        Route::put('/pembiasaan/{pembiasaan}', [MataPelajaranController::class, 'updatePembiasaan'])->name('pembiasaan.update');
        Route::delete('/pembiasaan/{pembiasaan}', [MataPelajaranController::class, 'destroyPembiasaan'])->name('pembiasaan.destroy');

        // Manajemen Pengguna
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });
});
