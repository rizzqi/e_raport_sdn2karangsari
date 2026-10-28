<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Kehadiran;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiRaporMapel;
use App\Models\Sekolah;
use App\Services\HitungNilaiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NilaiMapelController extends Controller
{
    /**
     * Entry Point Terpadu: Input Nilai Kelas (Formatif, UTS, Semester, Nilai Rapor Semua Mapel).
     */
    public function inputNilai(Kelas $kelas, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $tab = $request->input('tab', 'formatif');
        $allMapel = $kelas->mataPelajaran();

        $mapelId = $request->input('mapel_id');
        $mapel = null;
        if ($mapelId) {
            $mapel = $allMapel->firstWhere('id', (int) $mapelId);
        }
        if (! $mapel) {
            $mapel = $allMapel->first();
        }

        return $this->index($kelas, $mapel, $request);
    }

    /**
     * Tampilan Utama: 4 Lembar per Mata Pelajaran (Formatif, UTS, Semester, Nilai Rapor).
     */
    public function index(Kelas $kelas, ?MataPelajaran $mapel, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahunAjaran = $kelas->tahunAjaran;

        $semester = strtolower($tahunAjaran->semester ?? 'ganjil');
        $tahun = (int) $request->input('tahun', now()->year);

        // Tentukan daftar bulan sesuai semester aktif
        $bulanSemester = $this->getBulanSemester($semester);

        $tab = $request->input('tab', 'formatif');
        if (! in_array($tab, ['formatif', 'uts', 'uas', 'rapor'])) {
            $tab = 'formatif';
        }

        // Daftar semua mata pelajaran di kelas ini
        $allMapel = $kelas->mataPelajaran();
        if (! $mapel && $allMapel->isNotEmpty()) {
            $mapel = $allMapel->first();
        }

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        // 1. Ambil Nilai Formatif Bulanan (Semua bulan di semester ini untuk mapel aktif)
        $allFormatif = $mapel ? NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', array_keys($bulanSemester))
            ->get() : collect();

        // 2. Ambil Nilai UTS (dari tabel nilai periode 'sts' atau dari nilai_rapor_mapel)
        $nilaiUtsList = $mapel ? Nilai::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('periode', 'sts')
            ->get()
            ->keyBy('kelas_siswa_id') : collect();

        // 3. Ambil Nilai UAS / Semester (dari tabel nilai periode 'sas' atau dari nilai_rapor_mapel)
        $nilaiUasList = $mapel ? Nilai::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('periode', 'sas')
            ->get()
            ->keyBy('kelas_siswa_id') : collect();

        // 4. Ambil Nilai Rapor & Catatan Mapel yang sudah tersimpan
        $nilaiRaporList = $mapel ? NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->get()
            ->keyBy('kelas_siswa_id') : collect();

        // Bobot Penilaian
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;

        $kkm = $mapel ? $kelas->getKkmForMapel($mapel) : 75;

        // Susun Data Lengkap Per Siswa untuk Tab 1, 2, 3
        $siswaData = [];
        $totalSiswa = $kelasSiswaList->count();
        $countUtsKosong = 0;
        $countUasKosong = 0;
        $isAnyLocked = false;

        foreach ($kelasSiswaList as $ks) {
            $formatifSiswa = $allFormatif->where('kelas_siswa_id', $ks->id)->keyBy('bulan');

            // Nilai bulanan
            $bulanNilai = [];
            $terisiFormatif = [];
            foreach ($bulanSemester as $bNum => $bNama) {
                $rec = $formatifSiswa->get($bNum);
                $val = ($rec && $rec->nilai !== null) ? (float) $rec->nilai : null;
                $bulanNilai[$bNum] = $val;
                if ($val !== null) {
                    $terisiFormatif[] = $val;
                }
            }

            // Rata-rata formatif (hanya dari bulan yang sudah terisi)
            $rataFormatif = count($terisiFormatif) > 0 ? round(array_sum($terisiFormatif) / count($terisiFormatif), 1) : null;

            // UTS
            $recUts = $nilaiUtsList->get($ks->id);
            $valUts = ($recUts && $recUts->sumatif !== null) ? (float) $recUts->sumatif : null;

            // UAS
            $recUas = $nilaiUasList->get($ks->id);
            $valUas = ($recUas && $recUas->sumatif !== null) ? (float) $recUas->sumatif : null;

            // Rapor & Catatan
            $recRapor = $nilaiRaporList->get($ks->id);

            // Jika nilai UTS/UAS ada di recRapor tapi belum di Nilai (atau sebaliknya)
            if ($valUts === null && $recRapor && $recRapor->nilai_uts !== null) {
                $valUts = (float) $recRapor->nilai_uts;
            }
            if ($valUas === null && $recRapor && $recRapor->nilai_uas !== null) {
                $valUas = (float) $recRapor->nilai_uas;
            }

            if ($valUts === null) {
                $countUtsKosong++;
            }
            if ($valUas === null) {
                $countUasKosong++;
            }

            // Hitung Nilai Rapor Akhir dengan rumus bobot & pengaman nilai kosong
            $nilaiRapor = $this->hitungNilaiRapor($rataFormatif, $valUts, $valUas, $bobotFormatif, $bobotUts, $bobotUas);
            $predikat = NilaiRaporMapel::hitungPredikat($nilaiRapor);

            $catatan = $recRapor ? $recRapor->catatan : '';
            $isLocked = $recRapor ? (bool) $recRapor->is_locked : false;
            if ($isLocked) {
                $isAnyLocked = true;
            }

            $siswaData[$ks->id] = [
                'kelasSiswa' => $ks,
                'siswa' => $ks->siswa,
                'bulanNilai' => $bulanNilai,
                'rataFormatif' => $rataFormatif,
                'nilaiUts' => $valUts,
                'keteranganUts' => $valUts !== null ? ($valUts >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-',
                'nilaiUas' => $valUas,
                'keteranganUas' => $valUas !== null ? ($valUas >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-',
                'nilaiRapor' => $nilaiRapor,
                'predikat' => $predikat,
                'keteranganPredikat' => NilaiRaporMapel::getKeteranganPredikat($predikat),
                'catatan' => $catatan,
                'isLocked' => $isLocked,
                'recRapor' => $recRapor,
            ];
        }

        // =========================================================================
        // DATA TAB 4: NILAI RAPOR (OTOMATIS SEMUA MATA PELAJARAN)
        // =========================================================================
        $activeKelasSiswa = null;
        $prevSiswaId = null;
        $nextSiswaId = null;
        $raporSemuaMapelData = [];
        $totalNilaiRapor = 0;
        $countMapelValid = 0;
        $rataRataNilaiRapor = null;
        $predikatRataRata = null;
        $kehadiranActiveSiswa = null;
        $rekapSekelasRapor = [];

        if ($tab === 'rapor' && $kelasSiswaList->isNotEmpty()) {
            $reqKsId = (int) $request->input('kelas_siswa_id', 0);
            $activeKelasSiswa = $kelasSiswaList->firstWhere('id', $reqKsId) ?? $kelasSiswaList->first();

            $allSiswaIds = $kelasSiswaList->pluck('id')->toArray();
            $currIdx = array_search($activeKelasSiswa->id, $allSiswaIds);
            if ($currIdx !== false) {
                $prevSiswaId = $currIdx > 0 ? $allSiswaIds[$currIdx - 1] : null;
                $nextSiswaId = $currIdx < count($allSiswaIds) - 1 ? $allSiswaIds[$currIdx + 1] : null;
            }

            // Ambil seluruh data formatif, uts, uas, dan rapor untuk siswa aktif
            $formatifStudent = NilaiFormatifBulanan::where('kelas_siswa_id', $activeKelasSiswa->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', array_keys($bulanSemester))
                ->get()
                ->groupBy('mapel_id');

            $utsStudent = Nilai::where('kelas_siswa_id', $activeKelasSiswa->id)
                ->where('periode', 'sts')
                ->get()
                ->keyBy('mapel_id');

            $uasStudent = Nilai::where('kelas_siswa_id', $activeKelasSiswa->id)
                ->where('periode', 'sas')
                ->get()
                ->keyBy('mapel_id');

            $raporStudent = NilaiRaporMapel::where('kelas_siswa_id', $activeKelasSiswa->id)
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->get()
                ->keyBy('mapel_id');

            foreach ($allMapel as $m) {
                $kkmM = $kelas->getKkmForMapel($m);

                // Rata-rata formatif dari bulan yang sudah terisi
                $fList = $formatifStudent->get($m->id, collect());
                $fFilled = $fList->whereNotNull('nilai')->pluck('nilai')->map(fn ($v) => (float) $v);
                $rataF = $fFilled->isNotEmpty() ? round($fFilled->avg(), 1) : null;

                // UTS & UAS
                $recU = $utsStudent->get($m->id);
                $valU = ($recU && $recU->sumatif !== null) ? (float) $recU->sumatif : null;

                $recA = $uasStudent->get($m->id);
                $valA = ($recA && $recA->sumatif !== null) ? (float) $recA->sumatif : null;

                $recRap = $raporStudent->get($m->id);
                if ($valU === null && $recRap && $recRap->nilai_uts !== null) {
                    $valU = (float) $recRap->nilai_uts;
                }
                if ($valA === null && $recRap && $recRap->nilai_uas !== null) {
                    $valA = (float) $recRap->nilai_uas;
                }

                $nRap = $this->hitungNilaiRapor($rataF, $valU, $valA, $bobotFormatif, $bobotUts, $bobotUas);
                $pred = NilaiRaporMapel::hitungPredikat($nRap);
                $cat = $recRap ? $recRap->catatan : '';
                $isLok = $recRap ? (bool) $recRap->is_locked : false;

                if ($nRap !== null) {
                    $totalNilaiRapor += $nRap;
                    $countMapelValid++;
                }

                $saran = $this->generateSaranCatatan($m->nama, $pred);

                $raporSemuaMapelData[$m->id] = [
                    'mapel' => $m,
                    'kkm' => $kkmM,
                    'rataFormatif' => $rataF,
                    'nilaiUts' => $valU,
                    'nilaiUas' => $valA,
                    'nilaiRapor' => $nRap,
                    'predikat' => $pred,
                    'keteranganPredikat' => NilaiRaporMapel::getKeteranganPredikat($pred),
                    'catatan' => $cat,
                    'saranDefault' => $saran,
                    'isLocked' => $isLok,
                ];
            }

            $rataRataNilaiRapor = $countMapelValid > 0 ? round($totalNilaiRapor / $countMapelValid, 1) : null;
            $predikatRataRata = NilaiRaporMapel::hitungPredikat($rataRataNilaiRapor);

            // Kehadiran Siswa
            $kehadiranActiveSiswa = Kehadiran::firstOrCreate([
                'kelas_siswa_id' => $activeKelasSiswa->id,
                'periode' => $semester === 'genap' ? 'sas' : 'sts',
            ], [
                'sakit' => 0,
                'izin' => 0,
                'alpa' => 0,
            ]);

            // Rekap Leger Sekelas (Semua Siswa x Semua Mapel)
            $allRaporKelas = NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
                ->where('tahun', $tahun)
                ->where('semester', $semester)
                ->get()
                ->groupBy('kelas_siswa_id');

            foreach ($kelasSiswaList as $ksItem) {
                $mapelScores = [];
                $totScore = 0;
                $totValid = 0;
                $ksRapors = $allRaporKelas->get($ksItem->id, collect())->keyBy('mapel_id');

                foreach ($allMapel as $m) {
                    $rap = $ksRapors->get($m->id);
                    $sc = ($rap && $rap->nilai_rapor !== null) ? (float) $rap->nilai_rapor : null;
                    $mapelScores[$m->id] = $sc;
                    if ($sc !== null) {
                        $totScore += $sc;
                        $totValid++;
                    }
                }

                $avgScore = $totValid > 0 ? round($totScore / $totValid, 1) : null;
                $rekapSekelasRapor[$ksItem->id] = [
                    'kelasSiswa' => $ksItem,
                    'scores' => $mapelScores,
                    'total' => $totScore,
                    'rataRata' => $avgScore,
                    'predikat' => NilaiRaporMapel::hitungPredikat($avgScore),
                ];
            }
        }

        // Cari data Guru Pengampu jika ada
        $guruPengampu = $mapel ? $kelas->guruPengampu->firstWhere('mapel_id', $mapel->id) : null;

        return view('kelas.mapel.nilai', compact(
            'kelas',
            'mapel',
            'allMapel',
            'sekolah',
            'tahunAjaran',
            'semester',
            'tahun',
            'bulanSemester',
            'tab',
            'kelasSiswaList',
            'siswaData',
            'kkm',
            'bobotFormatif',
            'bobotUts',
            'bobotUas',
            'countUtsKosong',
            'countUasKosong',
            'isAnyLocked',
            'guruPengampu',
            'activeKelasSiswa',
            'raporSemuaMapelData',
            'totalNilaiRapor',
            'rataRataNilaiRapor',
            'predikatRataRata',
            'kehadiranActiveSiswa',
            'prevSiswaId',
            'nextSiswaId',
            'rekapSekelasRapor'
        ));
    }

    /**
     * Lembar 1: Simpan Nilai Formatif Bulanan Sekelas.
     */
    public function simpanFormatif(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));
        $formatifData = $request->input('formatif', []);

        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;

        $bulanSemester = $this->getBulanSemester($semester);

        foreach ($formatifData as $ksId => $bulans) {
            $terisi = [];
            foreach ($bulanSemester as $bNum => $bNama) {
                $rawVal = $bulans[$bNum] ?? null;
                $val = null;
                if ($rawVal !== null && trim((string) $rawVal) !== '') {
                    $val = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
                    $terisi[] = $val;
                }

                NilaiFormatifBulanan::updateOrCreate([
                    'kelas_siswa_id' => $ksId,
                    'mapel_id' => $mapel->id,
                    'tahun' => $tahun,
                    'bulan' => $bNum,
                ], [
                    'nilai' => $val,
                ]);
            }

            // Hitung rata-rata formatif baru
            $rataFormatif = count($terisi) > 0 ? round(array_sum($terisi) / count($terisi), 1) : null;

            // Sinkronisasi ke NilaiRaporMapel
            $rapor = NilaiRaporMapel::firstOrNew([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ]);

            if (! $rapor->is_locked) {
                $rapor->rata_formatif = $rataFormatif;
                $rapor->nilai_rapor = $this->hitungNilaiRapor(
                    $rataFormatif,
                    $rapor->nilai_uts,
                    $rapor->nilai_uas,
                    $bobotFormatif,
                    $bobotUts,
                    $bobotUas
                );
                $rapor->predikat = NilaiRaporMapel::hitungPredikat($rapor->nilai_rapor);
                $rapor->save();
            }
        }

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'formatif',
            'tahun' => $tahun,
        ])->with('success', 'Nilai Formatif Bulanan untuk ' . $mapel->nama . ' berhasil disimpan dan rata-rata otomatis diperbarui.');
    }

    /**
     * Lembar 2: Simpan Nilai UTS (STS).
     */
    public function simpanUts(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));
        $utsData = $request->input('uts', []);

        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;

        foreach ($utsData as $ksId => $rawVal) {
            $val = null;
            if ($rawVal !== null && trim((string) $rawVal) !== '') {
                $val = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
            }

            // Simpan ke tabel nilai periode sts
            Nilai::updateOrCreate([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'periode' => 'sts',
            ], [
                'sumatif' => $val,
            ]);

            // Sinkronisasi ke NilaiRaporMapel
            $rapor = NilaiRaporMapel::firstOrNew([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ]);

            if (! $rapor->is_locked) {
                $rapor->nilai_uts = $val;
                $rapor->nilai_rapor = $this->hitungNilaiRapor(
                    $rapor->rata_formatif,
                    $val,
                    $rapor->nilai_uas,
                    $bobotFormatif,
                    $bobotUts,
                    $bobotUas
                );
                $rapor->predikat = NilaiRaporMapel::hitungPredikat($rapor->nilai_rapor);
                $rapor->save();
            }
        }

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'uts',
            'tahun' => $tahun,
        ])->with('success', 'Nilai UTS untuk ' . $mapel->nama . ' berhasil disimpan.');
    }

    /**
     * Lembar 3: Simpan Nilai Semester (SAS / UAS).
     */
    public function simpanUas(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));
        $uasData = $request->input('uas', []);

        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;

        foreach ($uasData as $ksId => $rawVal) {
            $val = null;
            if ($rawVal !== null && trim((string) $rawVal) !== '') {
                $val = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
            }

            // Simpan ke tabel nilai periode sas
            Nilai::updateOrCreate([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'periode' => 'sas',
            ], [
                'sumatif' => $val,
            ]);

            // Sinkronisasi ke NilaiRaporMapel
            $rapor = NilaiRaporMapel::firstOrNew([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ]);

            if (! $rapor->is_locked) {
                $rapor->nilai_uas = $val;
                $rapor->nilai_rapor = $this->hitungNilaiRapor(
                    $rapor->rata_formatif,
                    $rapor->nilai_uts,
                    $val,
                    $bobotFormatif,
                    $bobotUts,
                    $bobotUas
                );
                $rapor->predikat = NilaiRaporMapel::hitungPredikat($rapor->nilai_rapor);
                $rapor->save();
            }
        }

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'uas',
            'tahun' => $tahun,
        ])->with('success', 'Nilai Semester (UAS) untuk ' . $mapel->nama . ' berhasil disimpan.');
    }

    /**
     * Lembar 4: Simpan Catatan Guru pada Nilai Rapor.
     */
    public function simpanRapor(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));
        $catatanData = $request->input('catatan', []);

        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;

        foreach ($catatanData as $ksId => $catatanText) {
            $rapor = NilaiRaporMapel::firstOrNew([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ]);

            if (! $rapor->is_locked) {
                $rapor->catatan = trim((string) $catatanText);
                // Pastikan nilai rapor dan predikat selalu sinkron
                $rapor->nilai_rapor = $this->hitungNilaiRapor(
                    $rapor->rata_formatif,
                    $rapor->nilai_uts,
                    $rapor->nilai_uas,
                    $bobotFormatif,
                    $bobotUts,
                    $bobotUas
                );
                $rapor->predikat = NilaiRaporMapel::hitungPredikat($rapor->nilai_rapor);
                $rapor->save();
            }
        }

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'rapor',
            'tahun' => $tahun,
        ])->with('success', 'Catatan Guru untuk ' . $mapel->nama . ' berhasil disimpan.');
    }

    /**
     * Lembar 4: Simpan Catatan Rapor Siswa untuk SEMUA MATA PELAJARAN sekaligus.
     * Mendukung simpan biasa atau simpan & lanjut ke siswa berikutnya.
     */
    public function simpanRaporSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));
        $catatanMapel = $request->input('catatan', []);
        $action = $request->input('action', 'simpan');

        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $bobotFormatif = $sekolah ? $sekolah->getBobotFormatif() : 40;
        $bobotUts = $sekolah ? $sekolah->getBobotUts() : 30;
        $bobotUas = $sekolah ? $sekolah->getBobotUas() : 30;
        $bulanSemester = $this->getBulanSemester($semester);

        $mapels = $kelas->mataPelajaran();

        foreach ($mapels as $m) {
            $catatanText = $catatanMapel[$m->id] ?? null;

            // Rata formatif
            $allFormatif = NilaiFormatifBulanan::where('kelas_siswa_id', $kelasSiswa->id)
                ->where('mapel_id', $m->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', array_keys($bulanSemester))
                ->get();
            $filledFormatif = $allFormatif->whereNotNull('nilai')->pluck('nilai')->map(fn ($v) => (float) $v);
            $rataFormatif = $filledFormatif->isNotEmpty() ? round($filledFormatif->avg(), 1) : null;

            // UTS & UAS
            $utsRec = Nilai::where('kelas_siswa_id', $kelasSiswa->id)->where('mapel_id', $m->id)->where('periode', 'sts')->first();
            $uasRec = Nilai::where('kelas_siswa_id', $kelasSiswa->id)->where('mapel_id', $m->id)->where('periode', 'sas')->first();

            $valUts = $utsRec && $utsRec->sumatif !== null ? (float) $utsRec->sumatif : null;
            $valUas = $uasRec && $uasRec->sumatif !== null ? (float) $uasRec->sumatif : null;

            $rapor = NilaiRaporMapel::firstOrNew([
                'kelas_siswa_id' => $kelasSiswa->id,
                'mapel_id' => $m->id,
                'tahun' => $tahun,
                'semester' => $semester,
            ]);

            // Jika nilai UTS/UAS sudah ada di rapor tapi belum di tabel Nilai
            if ($valUts === null && $rapor->nilai_uts !== null) {
                $valUts = (float) $rapor->nilai_uts;
            }
            if ($valUas === null && $rapor->nilai_uas !== null) {
                $valUas = (float) $rapor->nilai_uas;
            }

            $nilaiRapor = $this->hitungNilaiRapor($rataFormatif, $valUts, $valUas, $bobotFormatif, $bobotUts, $bobotUas);
            $predikat = NilaiRaporMapel::hitungPredikat($nilaiRapor);

            if (! $rapor->is_locked) {
                $rapor->rata_formatif = $rataFormatif;
                $rapor->nilai_uts = $valUts;
                $rapor->nilai_uas = $valUas;
                $rapor->nilai_rapor = $nilaiRapor;
                $rapor->predikat = $predikat;
                if ($catatanText !== null) {
                    $rapor->catatan = trim((string) $catatanText);
                }
                $rapor->save();
            }
        }

        // Kehadiran Siswa
        if ($request->has('kehadiran')) {
            $kehadiranData = $request->input('kehadiran', []);
            Kehadiran::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'periode' => $semester === 'genap' ? 'sas' : 'sts',
            ], [
                'sakit' => max(0, (int) ($kehadiranData['sakit'] ?? 0)),
                'izin' => max(0, (int) ($kehadiranData['izin'] ?? 0)),
                'alpa' => max(0, (int) ($kehadiranData['alpa'] ?? 0)),
            ]);
        }

        $namaSiswa = $kelasSiswa->siswa->nama ?? 'Siswa';

        if ($action === 'simpan_lanjut') {
            $allSiswaIds = $kelas->kelasSiswa()
                ->with('siswa')
                ->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->pluck('id')
                ->values()
                ->toArray();

            $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
            if ($currentIndex !== false && $currentIndex < count($allSiswaIds) - 1) {
                $nextSiswaId = $allSiswaIds[$currentIndex + 1];

                return redirect()->route('kelas.input-nilai', [
                    'kelas' => $kelas->id,
                    'tab' => 'rapor',
                    'kelas_siswa_id' => $nextSiswaId,
                    'tahun' => $tahun,
                ])->with('success', "Catatan rapor {$namaSiswa} berhasil disimpan! Beralih ke siswa berikutnya.");
            }
        }

        return redirect()->route('kelas.input-nilai', [
            'kelas' => $kelas->id,
            'tab' => 'rapor',
            'kelas_siswa_id' => $kelasSiswa->id,
            'tahun' => $tahun,
        ])->with('success', "Catatan rapor {$namaSiswa} (Semua Mapel) berhasil disimpan.");
    }

    /**
     * Generator template saran catatan otomatis berdasarkan predikat dan nama mapel.
     */
    public function generateSaranCatatan(string $namaMapel, ?string $predikat): string
    {
        switch ($predikat) {
            case 'A':
                return "Menunjukkan penguasaan materi yang sangat istimewa dan luar biasa dalam seluruh capaian pembelajaran {$namaMapel}.";
            case 'B':
                return "Menunjukkan pemahaman yang baik dan mampu menguasai tujuan pembelajaran {$namaMapel} dengan optimal.";
            case 'C':
                return "Cukup memahami materi pokok {$namaMapel}, perlu sedikit peningkatan ketekunan dan bimbingan belajar.";
            case 'D':
                return "Memerlukan bimbingan intensif dan pendampingan lebih lanjut dalam memahami materi pokok {$namaMapel}.";
            default:
                return "Perlu melengkapi evaluasi pembelajaran {$namaMapel} untuk menentukan capaian kompetensi.";
        }
    }

    /**
     * Kunci Nilai Rapor Mata Pelajaran (Disetujui Kepala Sekolah).
     */
    public function kunciNilai(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));

        $ksIds = $kelas->kelasSiswa()->pluck('id');

        NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->update([
                'is_locked' => true,
                'locked_at' => now(),
                'locked_by' => auth()->id(),
            ]);

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'rapor',
            'tahun' => $tahun,
        ])->with('success', 'Nilai Rapor ' . $mapel->nama . ' berhasil dikunci dan disahkan.');
    }

    /**
     * Buka Kunci Nilai Rapor Mata Pelajaran (Admin Only).
     */
    public function bukaKunciNilai(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya Administrator yang berhak membuka kunci nilai.');
        }

        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($request->input('semester', 'ganjil'));

        $ksIds = $kelas->kelasSiswa()->pluck('id');

        NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->update([
                'is_locked' => false,
                'locked_at' => null,
                'locked_by' => null,
            ]);

        return redirect()->route('kelas.mapel.nilai', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tab' => 'rapor',
            'tahun' => $tahun,
        ])->with('success', 'Kunci Nilai Rapor ' . $mapel->nama . ' berhasil dibuka. Guru dapat melakukan pengeditan kembali.');
    }

    /**
     * Cetak Lembar Nilai Formatif 1 Siswa (1 Siswa 1 Halaman F4).
     */
    public function cetakFormatifSiswaPdf(Kelas $kelas, MataPelajaran $mapel, KelasSiswa $kelasSiswa, Request $request)
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);
        $kelasSiswa->load('siswa');
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $siswa = $kelasSiswa->siswa;
        $tahunAjaran = $kelas->tahunAjaran;
        $semester = strtolower($tahunAjaran->semester ?? 'ganjil');
        $tahun = (int) $request->input('tahun', now()->year);

        $bulanSemester = $this->getBulanSemester($semester);
        $guruPengampu = $kelas->guruPengampu->firstWhere('mapel_id', $mapel->id);
        $kkm = $kelas->getKkmForMapel($mapel);

        // Ambil nilai formatif bulanan siswa ini untuk mapel ini
        $nilaiBulananList = NilaiFormatifBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', array_keys($bulanSemester))
            ->get()
            ->keyBy('bulan');

        $baris = [];
        $terisi = [];
        $countTuntas = 0;

        foreach ($bulanSemester as $bNum => $bNama) {
            $rec = $nilaiBulananList->get($bNum);
            $val = ($rec && $rec->nilai !== null) ? (float) $rec->nilai : null;
            if ($val !== null) {
                $terisi[] = $val;
                if ($val >= $kkm) {
                    $countTuntas++;
                }
            }

            $baris[] = [
                'bulan_nomor' => $bNum,
                'bulan_nama' => $bNama,
                'lingkup_materi' => $rec->lingkup_materi ?? 'Ulangan Formatif ' . $bNama,
                'kkm' => $kkm,
                'nilai' => $val,
                'nilai_format' => $val !== null ? HitungNilaiService::formatAngka($val) : '-',
                'keterangan' => $val !== null ? ($val >= $kkm ? 'Tuntas' : 'Belum Tuntas') : 'Belum Diisi',
            ];
        }

        $rataFormatif = count($terisi) > 0 ? round(array_sum($terisi) / count($terisi), 1) : null;
        $rataFormat = $rataFormatif !== null ? HitungNilaiService::formatAngka($rataFormatif) : '-';
        $statusAkhir = $rataFormatif !== null ? ($rataFormatif >= $kkm ? 'Tuntas' : 'Perlu Pendampingan') : 'Belum Lengkap';

        // Ambil catatan jika ada di nilai_rapor_mapel
        $rapor = NilaiRaporMapel::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();
        $catatanGuru = $rapor ? $rapor->catatan : '';

        $tanggalCetak = Carbon::now();

        $pdf = Pdf::loadView('kelas.mapel.pdf_formatif_siswa', compact(
            'kelas',
            'mapel',
            'kelasSiswa',
            'siswa',
            'sekolah',
            'guruPengampu',
            'tahunAjaran',
            'semester',
            'tahun',
            'kkm',
            'baris',
            'rataFormatif',
            'rataFormat',
            'statusAkhir',
            'countTuntas',
            'terisi',
            'catatanGuru',
            'tanggalCetak'
        ))->setPaper([0, 0, 609.45, 935.43]); // Standar F4 / Folio (215 x 330 mm)

        $cleanSiswa = preg_replace('/[^a-zA-Z0-9]+/', '_', $siswa->nama);
        $cleanMapel = preg_replace('/[^a-zA-Z0-9]+/', '_', $mapel->nama);
        $filename = "Formatif_{$cleanMapel}_{$cleanSiswa}_{$tahun}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Cetak PDF Rekap Nilai Formatif Per Bulan (Tombol "Cetak Bulan Ini" di Lembar 1).
     */
    public function cetakFormatifPdf(Kelas $kelas, MataPelajaran $mapel, Request $request)
    {
        $this->authorizeAkses($kelas);

        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $guruPengampu = $kelas->guruPengampu->firstWhere('mapel_id', $mapel->id);
        $kkm = $kelas->getKkmForMapel($mapel);

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        $nilaiList = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');

        $baris = [];
        $totalNilai = 0;
        $countNilai = 0;
        $countTuntas = 0;

        foreach ($kelasSiswaList as $idx => $ks) {
            $rec = $nilaiList->get($ks->id);
            $val = ($rec && $rec->nilai !== null) ? (float) $rec->nilai : null;

            if ($val !== null) {
                $totalNilai += $val;
                $countNilai++;
                if ($val >= $kkm) {
                    $countTuntas++;
                }
            }

            $baris[] = [
                'no' => $idx + 1,
                'nis' => $ks->siswa->nis,
                'nisn' => $ks->siswa->nisn ?? '-',
                'nama' => $ks->siswa->nama,
                'jk' => $ks->siswa->jenis_kelamin,
                'nilai' => $val,
                'nilai_format' => $val !== null ? HitungNilaiService::formatAngka($val) : '-',
                'keterangan' => $val !== null ? ($val >= $kkm ? 'Tuntas' : 'Belum Tuntas') : 'Belum Diisi',
            ];
        }

        $rataRata = $countNilai > 0 ? round($totalNilai / $countNilai, 1) : 0;
        $persenTuntas = $countNilai > 0 ? round(($countTuntas / $countNilai) * 100) : 0;

        $tanggalCetak = Carbon::now();

        $pdf = Pdf::loadView('kelas.mapel.pdf_formatif_bulan', compact(
            'kelas',
            'mapel',
            'sekolah',
            'guruPengampu',
            'kkm',
            'bulan',
            'tahun',
            'namaBulan',
            'baris',
            'rataRata',
            'countNilai',
            'countTuntas',
            'persenTuntas',
            'tanggalCetak'
        ))->setPaper([0, 0, 609.45, 935.43]); // Ukuran Standar F4 / Folio (215 x 330 mm) dalam points

        $filename = 'Rekap_Formatif_' . str_replace(' ', '_', $mapel->nama) . '_' . $namaBulan . '_' . $tahun . '.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Hitung Nilai Rapor Akhir dengan Bobot & Pengaman Nilai Kosong.
     * Rumus: (Rata-rata Formatif × bobot) + (UTS × bobot) + (UAS × bobot).
     * Nilai kosong tidak dianggap 0 (dihitung proporsional dari komponen yang sudah ada).
     */
    protected function hitungNilaiRapor(?float $rataFormatif, ?float $uts, ?float $uas, int $bobotF, int $bobotUts, int $bobotUas): ?float
    {
        $komponen = [];

        if ($rataFormatif !== null) {
            $komponen[] = ['nilai' => $rataFormatif, 'bobot' => $bobotF];
        }
        if ($uts !== null) {
            $komponen[] = ['nilai' => $uts, 'bobot' => $bobotUts];
        }
        if ($uas !== null) {
            $komponen[] = ['nilai' => $uas, 'bobot' => $bobotUas];
        }

        if (empty($komponen)) {
            return null;
        }

        $totalBobotTerisi = array_sum(array_column($komponen, 'bobot'));
        if ($totalBobotTerisi <= 0) {
            return null;
        }

        $subtotal = 0;
        foreach ($komponen as $k) {
            $subtotal += ($k['nilai'] * $k['bobot']);
        }

        // Hitung nilai akhir yang dinormalisasi dengan bobot terisi
        return round($subtotal / $totalBobotTerisi, 1);
    }

    /**
     * Daftar bulan per semester.
     * Ganjil: Juli - Desember (Bulan 7 - 12)
     * Genap: Januari - Juni (Bulan 1 - 6)
     */
    protected function getBulanSemester(string $semester): array
    {
        if ($semester === 'genap') {
            return [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
            ];
        }

        return [
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    /**
     * Otorisasi Hak Akses.
     */
    protected function authorizeAkses(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        // Admin, Wali Kelas dari kelas ini, atau Guru di sekolah ini
        if ($user->isAdmin() || ($user->isWali() && $kelas->wali_id === $user->id) || $user->isGuru()) {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses untuk menginput nilai pada kelas ini.');
    }
}
