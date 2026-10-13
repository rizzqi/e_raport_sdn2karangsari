<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Services\HitungNilaiService;

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
        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);

        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $rekapData = $this->hitungService->hitungRekapKelas($kelasSiswaList, $periode);

        return view('rekap.index', compact('kelas', 'periode', 'rekapData'));
    }
}
