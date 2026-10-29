<?php

namespace App\Http\Controllers;

use App\Models\AbsensiHarian;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Sekolah;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiHarianController extends Controller
{
    /**
     * Dashboard / Hub Absensi Harian:
     * Menyajikan akses cepat untuk Wali Kelas, Guru Pendidikan Agama, dan Guru PJOK.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $tanggalObj = Carbon::parse($tanggal)->locale('id');

        $kelasList = Kelas::with(['wali', 'tahunAjaran', 'kelasSiswa'])
            ->withCount('kelasSiswa')
            ->orderBy('nama')
            ->get();

        // Cari mata pelajaran Agama & PJOK
        $mapelAgama = MataPelajaran::where('nama', 'like', '%Agama%')->first();
        $mapelPjok = MataPelajaran::where('nama', 'like', '%PJOK%')->orWhere('nama', 'like', '%Jasmani%')->first();

        // Ringkasan Kehadiran Hari Ini (Semua Kelas)
        $absenHariIni = AbsensiHarian::whereDate('tanggal', $tanggal)->get();

        $stats = [
            'total_absen' => $absenHariIni->count(),
            'hadir' => $absenHariIni->where('status', 'H')->count(),
            'sakit' => $absenHariIni->where('status', 'S')->count(),
            'izin' => $absenHariIni->where('status', 'I')->count(),
            'alpa' => $absenHariIni->where('status', 'A')->count(),
        ];

        // Status per kelas untuk hari ini
        $statusKelasHariIni = [];
        foreach ($kelasList as $k) {
            $ksIds = $k->kelasSiswa->pluck('id');
            $absenK = $absenHariIni->whereIn('kelas_siswa_id', $ksIds);

            // Cek status masing-masing tipe
            $umum = $absenK->whereNull('mapel_id');
            $agama = $mapelAgama ? $absenK->where('mapel_id', $mapelAgama->id) : collect();
            $pjok = $mapelPjok ? $absenK->where('mapel_id', $mapelPjok->id) : collect();

            $statusKelasHariIni[$k->id] = [
                'wali_terisi' => $umum->count() > 0,
                'wali_hadir' => $umum->where('status', 'H')->count(),
                'wali_absen' => $umum->whereIn('status', ['S', 'I', 'A'])->count(),
                'agama_terisi' => $agama->count() > 0,
                'pjok_terisi' => $pjok->count() > 0,
            ];
        }

        return view('absensi_harian.hub', compact(
            'kelasList',
            'tanggal',
            'tanggalObj',
            'mapelAgama',
            'mapelPjok',
            'stats',
            'statusKelasHariIni',
            'user'
        ));
    }

    /**
     * Halaman Input & Rekap Absensi Harian per Kelas.
     * Mendukung 3 Tipe:
     * - 'wali' : Absensi Harian Rombel oleh Wali Kelas
     * - 'agama': Absensi Sesi Pembelajaran Guru Pendidikan Agama
     * - 'pjok' : Absensi Sesi Pembelajaran Guru PJOK
     */
    public function kelas(Kelas $kelas, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'guruPengampu.mapel']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();

        // Parameter Tanggal & Tipe
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $tanggalObj = Carbon::parse($tanggal)->locale('id');

        $tipe = strtolower($request->input('tipe', 'wali'));
        if (! in_array($tipe, ['wali', 'agama', 'pjok'])) {
            $tipe = 'wali';
        }

        // Tentukan Mapel berdasarkan tipe
        $mapel = null;
        if ($tipe === 'agama') {
            $mapel = $kelas->getMapelAgama();
        } elseif ($tipe === 'pjok') {
            $mapel = $kelas->getMapelPjok();
        }

        $mapelId = $mapel?->id;

        // Daftar Siswa di Kelas
        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        // Ambil Data Absensi untuk Tanggal & Tipe ini
        $absenQuery = AbsensiHarian::whereIn('kelas_siswa_id', $ksIds)
            ->whereDate('tanggal', $tanggal);

        if ($mapelId === null) {
            $absenQuery->whereNull('mapel_id');
        } else {
            $absenQuery->where('mapel_id', $mapelId);
        }

        $absenHariIni = $absenQuery->get()->keyBy('kelas_siswa_id');

        // Ringkasan Kehadiran Hari Ini
        $summary = [
            'total' => $kelasSiswaList->count(),
            'hadir' => $absenHariIni->where('status', 'H')->count(),
            'sakit' => $absenHariIni->where('status', 'S')->count(),
            'izin' => $absenHariIni->where('status', 'I')->count(),
            'alpa' => $absenHariIni->where('status', 'A')->count(),
            'belum_diisi' => max(0, $kelasSiswaList->count() - $absenHariIni->count()),
        ];

        // Rekap Bulanan (Grid Kalender Tanggal 1 s.d. Akhir Bulan)
        $tahun = (int) $tanggalObj->year;
        $bulan = (int) $tanggalObj->month;
        $jumlahHariBulan = $tanggalObj->daysInMonth;

        $rekapBulanQuery = AbsensiHarian::whereIn('kelas_siswa_id', $ksIds)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan);

        if ($mapelId === null) {
            $rekapBulanQuery->whereNull('mapel_id');
        } else {
            $rekapBulanQuery->where('mapel_id', $mapelId);
        }

        $semuaAbsenBulan = $rekapBulanQuery->get();

        // Bentuk matriks kalender per siswa: [ksId => [tgl => status]]
        $matriksBulan = [];
        $rekapTotalPerSiswa = [];

        foreach ($kelasSiswaList as $ks) {
            $siswaAbsen = $semuaAbsenBulan->where('kelas_siswa_id', $ks->id);
            $hCount = $siswaAbsen->where('status', 'H')->count();
            $sCount = $siswaAbsen->where('status', 'S')->count();
            $iCount = $siswaAbsen->where('status', 'I')->count();
            $aCount = $siswaAbsen->where('status', 'A')->count();

            $byDay = [];
            foreach ($siswaAbsen as $rec) {
                $dayNum = (int) $rec->tanggal->day;
                $byDay[$dayNum] = [
                    'status' => $rec->status,
                    'keterangan' => $rec->keterangan,
                ];
            }

            $matriksBulan[$ks->id] = $byDay;
            $rekapTotalPerSiswa[$ks->id] = [
                'H' => $hCount,
                'S' => $sCount,
                'I' => $iCount,
                'A' => $aCount,
                'total_tidak_hadir' => $sCount + $iCount + $aCount,
            ];
        }

        // Tentukan Guru Pengampu terkait
        $guruPengampu = null;
        if ($mapel) {
            $guruPengampu = $kelas->guruPengampu->firstWhere('mapel_id', $mapel->id);
        }

        return view('absensi_harian.index', compact(
            'kelas',
            'sekolah',
            'kelasSiswaList',
            'tanggal',
            'tanggalObj',
            'tipe',
            'mapel',
            'absenHariIni',
            'summary',
            'tahun',
            'bulan',
            'jumlahHariBulan',
            'matriksBulan',
            'rekapTotalPerSiswa',
            'guruPengampu'
        ));
    }

    /**
     * Simpan input absensi harian kelas.
     */
    public function simpan(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $request->validate([
            'tanggal' => 'required|date',
            'tipe' => 'required|in:wali,agama,pjok',
            'status' => 'required|array',
            'keterangan' => 'nullable|array',
        ]);

        $tanggal = $request->input('tanggal');
        $tipe = $request->input('tipe');
        $statusData = $request->input('status', []);
        $keteranganData = $request->input('keterangan', []);

        $mapel = null;
        if ($tipe === 'agama') {
            $mapel = $kelas->getMapelAgama();
        } elseif ($tipe === 'pjok') {
            $mapel = $kelas->getMapelPjok();
        }
        $mapelId = $mapel?->id;

        $userId = auth()->id();
        $updatedCount = 0;

        foreach ($statusData as $ksId => $st) {
            $cleanStatus = strtoupper(trim((string) $st));
            if (! in_array($cleanStatus, ['H', 'S', 'I', 'A'])) {
                $cleanStatus = 'H';
            }

            $ket = isset($keteranganData[$ksId]) ? trim((string) $keteranganData[$ksId]) : null;

            AbsensiHarian::updateOrCreate([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapelId,
                'tanggal' => $tanggal,
            ], [
                'status' => $cleanStatus,
                'keterangan' => $ket,
                'created_by' => $userId,
            ]);

            $updatedCount++;
        }

        $tanggalFormat = Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y');
        $namaTipe = match ($tipe) {
            'agama' => 'Pendidikan Agama & Budi Pekerti',
            'pjok' => 'PJOK',
            default => 'Wali Kelas (Umum)',
        };

        return redirect()->route('kelas.absensi-harian.index', [
            'kelas' => $kelas->id,
            'tanggal' => $tanggal,
            'tipe' => $tipe,
        ])->with('success', "Absensi harian {$namaTipe} untuk {$updatedCount} siswa tanggal {$tanggalFormat} berhasil disimpan.");
    }

    /**
     * Sinkronkan akumulasi absensi harian umum (Wali Kelas) ke Rekap Bulanan (KehadiranBulanan) & Rapor.
     */
    public function syncKehadiranBulanan(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);

        $kelasSiswaList = $kelas->kelasSiswa;

        // Ambil seluruh absensi harian umum (mapel_id IS NULL) pada bulan & tahun tersebut
        $allAbsen = AbsensiHarian::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->whereNull('mapel_id')
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->get();

        foreach ($kelasSiswaList as $ks) {
            $siswaAbsen = $allAbsen->where('kelas_siswa_id', $ks->id);
            $s = $siswaAbsen->where('status', 'S')->count();
            $i = $siswaAbsen->where('status', 'I')->count();
            $a = $siswaAbsen->where('status', 'A')->count();

            KehadiranBulanan::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ], [
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
            ]);
        }

        $namaBulan = KehadiranBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return redirect()->back()->with('success', "Akumulasi absensi harian umum bulan {$namaBulan} {$tahun} berhasil disinkronkan ke Modul Absensi Bulanan & SIA Rapor.");
    }

    /**
     * Cetak Lembar Dokumen Absensi Harian / Rekap Kalender Bulanan (F4).
     */
    public function cetak(Kelas $kelas, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'guruPengampu.mapel']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();

        $mode = $request->input('mode', 'harian'); // 'harian' atau 'bulanan'
        $tanggal = $request->input('tanggal', now()->format('Y-m-d'));
        $tanggalObj = Carbon::parse($tanggal)->locale('id');

        $tipe = strtolower($request->input('tipe', 'wali'));
        $mapel = null;
        if ($tipe === 'agama') {
            $mapel = $kelas->getMapelAgama();
        } elseif ($tipe === 'pjok') {
            $mapel = $kelas->getMapelPjok();
        }
        $mapelId = $mapel?->id;

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        if ($mode === 'bulanan') {
            $tahun = (int) $tanggalObj->year;
            $bulan = (int) $tanggalObj->month;
            $jumlahHariBulan = $tanggalObj->daysInMonth;

            $rekapQuery = AbsensiHarian::whereIn('kelas_siswa_id', $ksIds)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan);

            if ($mapelId === null) {
                $rekapQuery->whereNull('mapel_id');
            } else {
                $rekapQuery->where('mapel_id', $mapelId);
            }

            $semuaAbsenBulan = $rekapQuery->get();

            $matriksBulan = [];
            $rekapTotalPerSiswa = [];

            foreach ($kelasSiswaList as $ks) {
                $siswaAbsen = $semuaAbsenBulan->where('kelas_siswa_id', $ks->id);
                $byDay = [];
                foreach ($siswaAbsen as $rec) {
                    $byDay[(int) $rec->tanggal->day] = $rec->status;
                }
                $matriksBulan[$ks->id] = $byDay;
                $rekapTotalPerSiswa[$ks->id] = [
                    'H' => $siswaAbsen->where('status', 'H')->count(),
                    'S' => $siswaAbsen->where('status', 'S')->count(),
                    'I' => $siswaAbsen->where('status', 'I')->count(),
                    'A' => $siswaAbsen->where('status', 'A')->count(),
                ];
            }

            return view('absensi_harian.cetak_bulanan', compact(
                'kelas',
                'sekolah',
                'kelasSiswaList',
                'tanggalObj',
                'tipe',
                'mapel',
                'tahun',
                'bulan',
                'jumlahHariBulan',
                'matriksBulan',
                'rekapTotalPerSiswa'
            ));
        }

        // Mode Cetak Harian
        $absenQuery = AbsensiHarian::whereIn('kelas_siswa_id', $ksIds)
            ->whereDate('tanggal', $tanggal);

        if ($mapelId === null) {
            $absenQuery->whereNull('mapel_id');
        } else {
            $absenQuery->where('mapel_id', $mapelId);
        }

        $absenHariIni = $absenQuery->get()->keyBy('kelas_siswa_id');

        return view('absensi_harian.cetak_harian', compact(
            'kelas',
            'sekolah',
            'kelasSiswaList',
            'tanggal',
            'tanggalObj',
            'tipe',
            'mapel',
            'absenHariIni'
        ));
    }

    /**
     * Otorisasi akses: Admin, Wali Kelas bersangkutan, atau Guru Pengampu.
     */
    protected function authorizeAkses(Kelas $kelas): void
    {
        $user = auth()->user();
        if ($user->isAdmin() || $user->isKepsek()) {
            return;
        }

        if ($user->isWali() && $user->id === $kelas->wali_id) {
            return;
        }

        // Guru mata pelajaran diizinkan mengisi absensi kelas
        if ($user->role === 'guru' || $user->isWali()) {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses untuk absensi rombel kelas ini.');
    }
}
