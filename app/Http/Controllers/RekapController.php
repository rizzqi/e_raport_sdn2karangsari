<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Services\HitungNilaiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    protected HitungNilaiService $hitungService;

    public function __construct(HitungNilaiService $hitungService)
    {
        $this->hitungService = $hitungService;
    }

    public function index(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $sortBy = $request->input('sort', 'ranking');
        $jenisNilai = $request->input('jenis_nilai', 'rapor');
        $bulan = $request->input('bulan');
        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);

        $rawTanggal = $request->input('tanggal') ?? $kelas->getTanggalRaport($periode);
        try {
            $tanggalObj = $rawTanggal ? Carbon::parse($rawTanggal)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            $tanggalObj = Carbon::now()->locale('id');
        }
        $tanggal = $tanggalObj->format('Y-m-d');

        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekapData = $this->hitungService->hitungRekapKelas($kelasSiswaList, $periode, $sortBy);

        // Ambil daftar kelas untuk pemilih/tab ganti kelas cepat
        $user = auth()->user();
        $queryKelas = Kelas::where('tahun_ajaran_id', $kelas->tahun_ajaran_id);
        if ($user && $user->isWali()) {
            $queryKelas->where('wali_id', $user->id);
        }
        $kelasList = $queryKelas->get()->sortBy(function (Kelas $k) {
            return $k->tingkat ?? 999;
        })->values();

        return view('rekap.index', compact('kelas', 'periode', 'sortBy', 'rekapData', 'tanggal', 'tanggalObj', 'kelasList', 'jenisNilai', 'bulan'));
    }

    /**
     * Cetak Rekapitulasi Nilai Semester Otomatis (Leger Kelas Siap Cetak F4/A4)
     */
    public function cetak(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sas'); // Default ke 'sas' untuk rekap semester penuh
        $sortBy = $request->input('sort', 'ranking');
        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);

        $rawTanggal = $request->input('tanggal') ?? $kelas->getTanggalRaport($periode);
        try {
            $tanggalObj = $rawTanggal ? Carbon::parse($rawTanggal)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            $tanggalObj = Carbon::now()->locale('id');
        }
        $tanggal = $tanggalObj->format('Y-m-d');

        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekapData = $this->hitungService->hitungRekapKelas($kelasSiswaList, $periode, $sortBy);

        // Ambil kehadiran untuk seluruh siswa di kelas
        $ksIds = $kelasSiswaList->pluck('id');
        $kehadiranList = \App\Models\Kehadiran::whereIn('kelas_siswa_id', $ksIds)
            ->where('periode', in_array($periode, ['sas', 'semester']) ? 'sas' : 'sts')
            ->get()
            ->keyBy('kelas_siswa_id');

        // Jika periode sas belum diisi absensinya, otomatis ambil dari sts
        if ($kehadiranList->isEmpty() && in_array($periode, ['sas', 'semester'])) {
            $kehadiranList = \App\Models\Kehadiran::whereIn('kelas_siswa_id', $ksIds)
                ->where('periode', 'sts')
                ->get()
                ->keyBy('kelas_siswa_id');
        }

        return view('rekap.cetak', compact('kelas', 'periode', 'sortBy', 'rekapData', 'tanggal', 'tanggalObj', 'kehadiranList'));
    }
}
