<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Pembiasaan;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Kehadiran;
use App\Services\HitungNilaiService;

class NilaiController extends Controller
{
    protected HitungNilaiService $hitungService;

    public function __construct(HitungNilaiService $hitungService)
    {
        $this->hitungService = $hitungService;
    }

    /**
     * Tampilan input nilai per siswa dalam kelas
     */
    public function inputSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapels = MataPelajaran::aktif()->get();
        $pembiasaans = Pembiasaan::aktif()->get();

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);
        $kelasSiswa->load('siswa');

        $nilaiList = Nilai::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('mapel_id');

        $pembiasaanList = NilaiPembiasaan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('pembiasaan_id');

        $kehadiran = Kehadiran::firstOrCreate([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
        ], [
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ]);

        $hasilPerhitungan = $this->hitungService->hitungNilaiSiswa($kelasSiswa, $periode);

        // Siswa berikutnya dan sebelumnya untuk navigasi cepat
        $allSiswaIds = $kelas->kelasSiswa()->orderBy('id')->pluck('id')->toArray();
        $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
        $prevId = $currentIndex > 0 ? $allSiswaIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allSiswaIds) - 1 ? $allSiswaIds[$currentIndex + 1] : null;

        return view('nilai.input_siswa', compact(
            'kelas',
            'kelasSiswa',
            'periode',
            'mapels',
            'pembiasaans',
            'nilaiList',
            'pembiasaanList',
            'kehadiran',
            'hasilPerhitungan',
            'prevId',
            'nextId'
        ));
    }

    /**
     * Simpan input nilai per siswa
     */
    public function simpanSiswa(Request $request, Kelas $kelas, KelasSiswa $kelasSiswa)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');

        // 1. Simpan Nilai Mapel
        $formatifData = $request->input('formatif', []);
        $sumatifData = $request->input('sumatif', []);

        $mapels = MataPelajaran::aktif()->get();

        foreach ($mapels as $mapel) {
            $formatif = isset($formatifData[$mapel->id]) && $formatifData[$mapel->id] !== '' ? (float) $formatifData[$mapel->id] : null;
            $sumatif = isset($sumatifData[$mapel->id]) && $sumatifData[$mapel->id] !== '' ? (float) $sumatifData[$mapel->id] : null;

            // Validasi 0 - 100
            if ($formatif !== null) $formatif = max(0, min(100, $formatif));
            if ($sumatif !== null) $sumatif = max(0, min(100, $sumatif));

            Nilai::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'mapel_id' => $mapel->id,
                'periode' => $periode,
            ], [
                'formatif' => $formatif,
                'sumatif' => $sumatif,
            ]);
        }

        // 2. Simpan Pembiasaan
        $predikatData = $request->input('predikat', []);
        $pembiasaans = Pembiasaan::aktif()->get();

        foreach ($pembiasaans as $pem) {
            $predikat = $predikatData[$pem->id] ?? 'Baik';
            NilaiPembiasaan::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'pembiasaan_id' => $pem->id,
                'periode' => $periode,
            ], [
                'predikat' => $predikat,
            ]);
        }

        // 3. Simpan Kehadiran
        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
        ], [
            'sakit' => (int) $request->input('sakit', 0),
            'izin' => (int) $request->input('izin', 0),
            'alpa' => (int) $request->input('alpa', 0),
        ]);

        $nextSiswaId = $request->input('next_siswa_id');
        if ($nextSiswaId) {
            return redirect()->route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $nextSiswaId, 'periode' => $periode])
                ->with('success', "Nilai {$kelasSiswa->siswa->nama} berhasil disimpan. Melanjutkan ke siswa berikutnya.");
        }

        return redirect()->back()->with('success', "Nilai {$kelasSiswa->siswa->nama} berhasil disimpan.");
    }

    /**
     * Tampilan input matriks cepat satu kelas
     */
    public function inputKelas(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');

        $mapels = MataPelajaran::aktif()->get();
        if (!$mapelId && $mapels->isNotEmpty()) {
            $mapelId = $mapels->first()->id;
        }

        $activeMapel = MataPelajaran::find($mapelId);
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();

        $nilaiList = Nilai::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('mapel_id', $mapelId)
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        return view('nilai.input_kelas', compact(
            'kelas',
            'periode',
            'mapels',
            'activeMapel',
            'kelasSiswaList',
            'nilaiList'
        ));
    }

    /**
     * Simpan input matriks satu kelas
     */
    public function simpanKelas(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');
        $formatifData = $request->input('formatif', []);
        $sumatifData = $request->input('sumatif', []);

        foreach ($kelas->kelasSiswa as $ks) {
            $formatif = isset($formatifData[$ks->id]) && $formatifData[$ks->id] !== '' ? (float) $formatifData[$ks->id] : null;
            $sumatif = isset($sumatifData[$ks->id]) && $sumatifData[$ks->id] !== '' ? (float) $sumatifData[$ks->id] : null;

            if ($formatif !== null) $formatif = max(0, min(100, $formatif));
            if ($sumatif !== null) $sumatif = max(0, min(100, $sumatif));

            Nilai::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'mapel_id' => $mapelId,
                'periode' => $periode,
            ], [
                'formatif' => $formatif,
                'sumatif' => $sumatif,
            ]);
        }

        return redirect()->back()->with('success', 'Nilai kelas untuk mata pelajaran ini berhasil disimpan.');
    }

    protected function authorizeAksesKelas(Kelas $kelas): void
    {
        $user = auth()->user();
        if ($user->isWali() && $kelas->wali_id !== $user->id) {
            abort(403, 'Anda hanya dapat mengakses kelas yang Anda ampu.');
        }
        if ($user->isWali() && (!$kelas->tahunAjaran || !$kelas->tahunAjaran->aktif)) {
            abort(403, 'Tahun ajaran kelas ini tidak aktif. Nilai hanya dapat diubah pada tahun ajaran yang aktif.');
        }
    }
}
