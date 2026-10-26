<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiBulananController extends Controller
{
    /**
     * Tampilkan halaman pengelolaan absensi bulanan per rombel kelas.
     */
    public function index(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);

        // Daftar siswa terurut abjad
        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa', 'kehadiranBulanan' => function ($q) use ($tahun) {
                $q->where('tahun', $tahun);
            }])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Data kehadiran bulan terpilih
        $kehadiranBulanIni = KehadiranBulanan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');

        // Rekap tahunan/semester per siswa (agregat semua bulan yang tercatat di tahun ini)
        $rekapPerSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $records = $ks->kehadiranBulanan;
            $rekapPerSiswa[$ks->id] = [
                'sakit' => $records->sum('sakit'),
                'izin' => $records->sum('izin'),
                'alpa' => $records->sum('alpa'),
                'total' => $records->sum('sakit') + $records->sum('izin') + $records->sum('alpa'),
            ];
        }

        $namaBulan = KehadiranBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";
        $daftarBulan = KehadiranBulanan::NAMA_BULAN;

        return view('kelas.absensi_bulanan', compact(
            'kelas',
            'kelasSiswaList',
            'kehadiranBulanIni',
            'rekapPerSiswa',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulan'
        ));
    }

    /**
     * Simpan input absensi bulanan untuk semua siswa dalam rombel.
     */
    public function simpan(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);

        $sakitData = $request->input('sakit', []);
        $izinData = $request->input('izin', []);
        $alpaData = $request->input('alpa', []);

        $kelasSiswaList = $kelas->kelasSiswa;

        foreach ($kelasSiswaList as $ks) {
            $sakit = max(0, (int) ($sakitData[$ks->id] ?? 0));
            $izin = max(0, (int) ($izinData[$ks->id] ?? 0));
            $alpa = max(0, (int) ($alpaData[$ks->id] ?? 0));

            KehadiranBulanan::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ], [
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
            ]);
        }

        // Sinkronisasi otomatis ke rekap kehadiran rapor (STS & SAS)
        $this->sinkronisasiKeRaport($kelas, $tahun);

        $namaBulan = KehadiranBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return redirect()->back()->with('success', "Absensi bulan {$namaBulan} {$tahun} untuk rombel {$kelas->nama} berhasil disimpan dan disinkronkan ke rekap rapor.");
    }

    /**
     * Sinkronkan akumulasi kehadiran bulanan ke tabel kehadiran rapor (STS dan SAS).
     */
    public function syncRaport(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAksesKelas($kelas);
        $tahun = (int) $request->input('tahun', now()->year);

        $this->sinkronisasiKeRaport($kelas, $tahun);

        return redirect()->back()->with('success', "Akumulasi absensi bulanan berhasil disinkronkan ke nilai kehadiran rapor STS dan SAS {$kelas->nama}.");
    }

    /**
     * Cetak atau unduh format lembar absensi bulanan siap pakai.
     */
    public function cetak(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        $rawTanggal = $request->input('tanggal');
        $tanggalObj = $rawTanggal ? Carbon::parse($rawTanggal)->locale('id') : Carbon::now()->locale('id');

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);
        $sekolah = Sekolah::first() ?? $kelas->sekolah;

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $kehadiranBulanIni = KehadiranBulanan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');

        $namaBulan = KehadiranBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return view('kelas.absensi_bulanan_cetak', compact(
            'kelas',
            'kelasSiswaList',
            'kehadiranBulanIni',
            'sekolah',
            'tahun',
            'bulan',
            'namaBulan',
            'tanggalObj'
        ));
    }

    /**
     * Hitung akumulasi bulanan dan perbarui tabel kehadiran semester (STS & SAS).
     */
    protected function sinkronisasiKeRaport(Kelas $kelas, int $tahun): void
    {
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        // Semester Ganjil: Juli(7) s/d Des(12). STS: 7-9, SAS: 7-12
        // Semester Genap: Jan(1) s/d Juni(6). STS: 1-3, SAS: 1-6
        if ($semester === 'genap') {
            $bulanSts = [1, 2, 3];
            $bulanSas = [1, 2, 3, 4, 5, 6];
        } else {
            $bulanSts = [7, 8, 9];
            $bulanSas = [7, 8, 9, 10, 11, 12];
        }

        foreach ($kelas->kelasSiswa as $ks) {
            // Hitung STS
            $stsRecords = KehadiranBulanan::where('kelas_siswa_id', $ks->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', $bulanSts)
                ->get();

            if ($stsRecords->isNotEmpty()) {
                Kehadiran::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'periode' => 'sts',
                ], [
                    'sakit' => $stsRecords->sum('sakit'),
                    'izin' => $stsRecords->sum('izin'),
                    'alpa' => $stsRecords->sum('alpa'),
                ]);
            }

            // Hitung SAS
            $sasRecords = KehadiranBulanan::where('kelas_siswa_id', $ks->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', $bulanSas)
                ->get();

            if ($sasRecords->isNotEmpty()) {
                Kehadiran::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'periode' => 'sas',
                ], [
                    'sakit' => $sasRecords->sum('sakit'),
                    'izin' => $sasRecords->sum('izin'),
                    'alpa' => $sasRecords->sum('alpa'),
                ]);
            }
        }
    }

    protected function authorizeAksesKelas(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Hanya Administrator atau Wali Kelas bersangkutan yang berhak mengelola absensi kelas ini.');
        }
    }
}
