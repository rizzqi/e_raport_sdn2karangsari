<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Raport;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\HitungNilaiService;

class DashboardController extends Controller
{
    public function index(HitungNilaiService $hitungService)
    {
        $user = auth()->user();
        $tahunAktif = TahunAjaran::aktif()->first();
        $sekolah = Sekolah::first();

        $queryKelas = Kelas::with(['wali', 'tahunAjaran', 'kelasSiswa.siswa']);
        if ($tahunAktif) {
            $queryKelas->where('tahun_ajaran_id', $tahunAktif->id);
        }

        if ($user->isWali()) {
            $queryKelas->where('wali_id', $user->id);
        }

        $kelasList = $queryKelas->get();

        if ($user->isWali()) {
            $waliKelasIds = $kelasList->pluck('id');
            $totalSiswa = Siswa::whereHas('kelasSiswa', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', $waliKelasIds);
            })->where('status', 'aktif')->count();
            $totalKelas = $kelasList->count();
        } else {
            $totalSiswa = Siswa::where('status', 'aktif')->count();
            $totalKelas = Kelas::count();
        }

        $totalRaportDicetak = Raport::count();

        // Hitung kelengkapan tiap kelas
        $rekapKelasOverview = [];
        foreach ($kelasList as $k) {
            $ksList = $k->kelasSiswa;
            $rekap = $hitungService->hitungRekapKelas($ksList, 'sts');
            $rekapKelasOverview[] = [
                'kelas' => $k,
                'total_siswa' => $ksList->count(),
                'siswa_selesai' => $rekap['siswa_selesai'],
                'persen_selesai' => $rekap['persen_selesai'],
                'rata_rata' => $rekap['rata_rata_kelas_format'],
            ];
        }

        return view('dashboard.index', compact(
            'user',
            'tahunAktif',
            'sekolah',
            'kelasList',
            'totalSiswa',
            'totalKelas',
            'totalRaportDicetak',
            'rekapKelasOverview'
        ));
    }
}
