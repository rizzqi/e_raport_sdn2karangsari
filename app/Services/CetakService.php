<?php

namespace App\Services;

use App\Http\Controllers\NilaiFormatifBulananController;
use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiPembiasaan;
use App\Models\NilaiRaporMapel;
use App\Models\Pembiasaan;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Support\Collection;

class CetakService
{
    public function __construct(
        protected HitungNilaiService $hitungService
    ) {}

    /**
     * Daftar tipe dokumen cetak yang didukung.
     */
    public const JENIS_VALID = [
        'formatif_bulanan',
        'formatif_semester',
        'sts_daftar',
        'sts_lembar',
        'sas_daftar',
        'sas_lembar',
        'rapor_siswa',
        'rapor_kelas',
        'rapor_leger',
        'rapor_predikat_catatan',
    ];

    /**
     * Selesaikan daftar kelas target berdasarkan cakupan (siswa / kelas / sekolah).
     *
     * @return Collection<int, Kelas>
     */
    public function resolveTargetKelasList(Kelas $kelas, string $cakupan, ?User $user = null): Collection
    {
        if ($cakupan !== 'sekolah') {
            $kelas->loadMissing(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm', 'guruPengampu']);

            return collect([$kelas]);
        }

        $query = Kelas::with(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm', 'guruPengampu'])
            ->where('tahun_ajaran_id', $kelas->tahun_ajaran_id);

        if ($user && $user->isWali()) {
            $query->where('wali_id', $user->id);
        }

        return $query->get()
            ->sortBy(fn (Kelas $k) => sprintf('%03d-%s', $k->tingkat ?? 999, $k->nama), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Periksa kelengkapan nilai, predikat, dan catatan guru sebelum cetak dokumen.
     *
     * @param  array{
     *   cakupan?: string,
     *   kelas_siswa_id?: int|null,
     *   mapel_id?: int|null,
     *   tahun?: int|null,
     *   bulan?: int|null,
     *   semester?: string|null
     * }  $options
     * @return array{
     *   is_complete: bool,
     *   total_missing_items: int,
     *   summary: array{nilai_kosong: int, predikat_kosong: int, catatan_kosong: int},
     *   items: array<int, array<string, mixed>>,
     *   total_checked_cells: int
     * }
     */
    public function cekKelengkapanCetak(Kelas $kelas, string $jenis, array $options = [], ?User $user = null): array
    {
        $cakupan = $options['cakupan'] ?? 'kelas';
        $kelasSiswaId = ! empty($options['kelas_siswa_id']) ? (int) $options['kelas_siswa_id'] : null;
        $filterMapelId = ! empty($options['mapel_id']) ? (int) $options['mapel_id'] : null;
        $tahun = (int) ($options['tahun'] ?? now()->year);
        $bulan = (int) ($options['bulan'] ?? now()->month);
        $semester = strtolower((string) ($options['semester'] ?? ($kelas->tahunAjaran->semester ?? 'ganjil')));

        $kelasList = $this->resolveTargetKelasList($kelas, $cakupan, $user);

        $items = [];
        $cntNilai = 0;
        $cntPredikat = 0;
        $cntCatatan = 0;
        $totalCells = 0;

        $bulanSemester = NilaiFormatifBulananController::getBulanSemester($semester);
        $bulanKeys = array_keys($bulanSemester);

        foreach ($kelasList as $k) {
            $mapels = $k->mataPelajaran();
            if ($filterMapelId !== null) {
                $mapels = $mapels->where('id', $filterMapelId)->values();
            }

            $ksQuery = $k->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            if ($cakupan === 'siswa' && $kelasSiswaId !== null) {
                $ksQuery = $ksQuery->where('id', $kelasSiswaId)->values();
            }

            if ($ksQuery->isEmpty() || $mapels->isEmpty()) {
                continue;
            }

            $ksIds = $ksQuery->pluck('id');
            $isRaporType = str_starts_with($jenis, 'rapor_') || $jenis === 'sas_lembar' || $jenis === 'sts_lembar';

            $allFormatif = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
                ->where('tahun', $tahun)
                ->get()
                ->groupBy('kelas_siswa_id');

            $allNilai = Nilai::whereIn('kelas_siswa_id', $ksIds)
                ->get()
                ->groupBy('kelas_siswa_id');

            $allRaporMapel = NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
                ->where('semester', $semester)
                ->get()
                ->groupBy('kelas_siswa_id');

            foreach ($ksQuery as $ks) {
                $fSiswa = $allFormatif->get($ks->id, collect());
                $nSiswa = $allNilai->get($ks->id, collect());
                $rSiswa = $allRaporMapel->get($ks->id, collect())->keyBy('mapel_id');

                $hasilSiswa = null;
                if ($isRaporType) {
                    $periodeRapor = $jenis === 'sts_lembar' ? 'sts' : 'sas';
                    $preloadedNilai = $nSiswa->where('periode', $periodeRapor)->keyBy('mapel_id');
                    $hasilSiswa = $this->hitungService->hitungNilaiSiswa($ks, $periodeRapor, $preloadedNilai, $rSiswa, null, $filterMapelId);
                }

                $barisByMapel = $hasilSiswa ? collect($hasilSiswa['baris'])->keyBy('mapel_id') : collect();

                foreach ($mapels as $m) {
                    $totalCells++;
                    $masalah = [];
                    $targetTab = 'rapor';

                    if ($jenis === 'formatif_bulanan') {
                        $recFmt = $fSiswa->where('mapel_id', $m->id)->where('bulan', $bulan)->first();
                        if (! $recFmt || $recFmt->nilai === null) {
                            $masalah[] = 'Nilai formatif bulan '.(NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? $bulan).' kosong';
                            $cntNilai++;
                            $targetTab = 'formatif';
                        }
                    } elseif ($jenis === 'formatif_semester') {
                        $recCount = $fSiswa->where('mapel_id', $m->id)
                            ->whereIn('bulan', $bulanKeys)
                            ->whereNotNull('nilai')
                            ->count();
                        if ($recCount === 0) {
                            $masalah[] = 'Nilai formatif semester kosong';
                            $cntNilai++;
                            $targetTab = 'formatif';
                        }
                    } elseif ($jenis === 'sts_daftar' || $jenis === 'sts_lembar') {
                        $recR = $rSiswa->get($m->id);
                        $recN = $nSiswa->where('mapel_id', $m->id)->where('periode', 'sts')->first();
                        $valSts = ($recN && $recN->sumatif !== null) ? $recN->sumatif : ($recR?->nilai_uts);
                        if ($valSts === null) {
                            $masalah[] = 'Nilai STS kosong';
                            $cntNilai++;
                            $targetTab = 'sts';
                        }
                    } elseif ($jenis === 'sas_daftar') {
                        $recR = $rSiswa->get($m->id);
                        $recN = $nSiswa->where('mapel_id', $m->id)->where('periode', 'sas')->first();
                        $valSas = ($recN && $recN->sumatif !== null) ? $recN->sumatif : ($recR?->nilai_uas);
                        if ($valSas === null) {
                            $masalah[] = 'Nilai SAS kosong';
                            $cntNilai++;
                            $targetTab = 'sas';
                        }
                    } else {
                        // Rapor Lengkap / Sekelas / Leger / Rekap Predikat & Catatan / Lembar SAS
                        $baris = $barisByMapel->get($m->id);
                        $nilaiRapor = $baris['nilai_rapor'] ?? null;
                        $predikat = $baris['predikat'] ?? '-';
                        $catatanKosong = (bool) ($baris['catatan_guru_kosong'] ?? true);

                        if ($nilaiRapor === null) {
                            $masalah[] = 'Nilai Rapor belum lengkap (Formatif/STS/SAS)';
                            $cntNilai++;
                            if (($baris['formatif_murni'] ?? null) === null) {
                                $targetTab = 'formatif';
                            } elseif (($baris['nilai_uts'] ?? null) === null) {
                                $targetTab = 'sts';
                            } else {
                                $targetTab = 'sas';
                            }
                        }

                        if ($predikat === '-' || $predikat === '') {
                            $masalah[] = 'Predikat kosong';
                            $cntPredikat++;
                        }

                        if ($catatanKosong) {
                            $masalah[] = 'Catatan Guru kosong';
                            $cntCatatan++;
                            if ($nilaiRapor !== null) {
                                $targetTab = 'rapor';
                            }
                        }
                    }

                    if (! empty($masalah)) {
                        $routeParams = [
                            'kelas' => $k->id,
                            'mapel' => $m->id,
                            'tab' => $targetTab,
                            'tahun' => $tahun,
                            'semester' => $semester,
                            'focus_ks' => $ks->id,
                        ];
                        if ($targetTab === 'formatif') {
                            $routeParams['bulan'] = $bulan;
                        }

                        $items[] = [
                            'kelas_id' => $k->id,
                            'kelas_nama' => $k->nama,
                            'kelas_siswa_id' => $ks->id,
                            'siswa_nama' => $ks->siswa->nama,
                            'mapel_id' => $m->id,
                            'mapel_nama' => $m->nama,
                            'masalah' => $masalah,
                            'target_tab' => $targetTab,
                            'url' => route('kelas.mapel.nilai', $routeParams),
                        ];
                    }
                }
            }
        }

        return [
            'is_complete' => count($items) === 0,
            'total_missing_items' => count($items),
            'summary' => [
                'nilai_kosong' => $cntNilai,
                'predikat_kosong' => $cntPredikat,
                'catatan_kosong' => $cntCatatan,
            ],
            'items' => array_slice($items, 0, 120),
            'total_checked_cells' => $totalCells,
        ];
    }

    /**
     * Susun data kolektif Nilai Formatif (Bulanan atau 1 Semester) semua mapel per kelas.
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    public function buildKolektifFormatifData(
        Collection $kelasList,
        int $tahun,
        int $bulan,
        string $mode = 'bulanan',
        ?int $filterMapelId = null,
        ?int $kelasSiswaId = null
    ): array {
        $result = [];

        foreach ($kelasList as $kelas) {
            $semester = strtolower((string) ($kelas->tahunAjaran->semester ?? 'ganjil'));
            $bulanSemester = NilaiFormatifBulananController::getBulanSemester($semester);
            $bulanKeys = array_keys($bulanSemester);

            $mapels = $kelas->mataPelajaran();
            if ($filterMapelId !== null) {
                $mapels = $mapels->where('id', $filterMapelId)->values();
            }

            $ksList = $kelas->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            if ($kelasSiswaId !== null) {
                $ksList = $ksList->where('id', $kelasSiswaId)->values();
            }

            $ksIds = $ksList->pluck('id');
            $allFmt = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
                ->where('tahun', $tahun)
                ->get()
                ->groupBy('kelas_siswa_id');

            $rows = [];
            foreach ($ksList as $idx => $ks) {
                $fSiswa = $allFmt->get($ks->id, collect());
                $nilaiPerMapel = [];
                $sum = 0.0;
                $cnt = 0;

                foreach ($mapels as $m) {
                    if ($mode === 'bulanan') {
                        $rec = $fSiswa->where('mapel_id', $m->id)->where('bulan', $bulan)->first();
                        $val = $rec && $rec->nilai !== null ? (float) $rec->nilai : null;
                    } else {
                        $vals = $fSiswa->where('mapel_id', $m->id)
                            ->whereIn('bulan', $bulanKeys)
                            ->whereNotNull('nilai')
                            ->pluck('nilai')
                            ->map(fn ($v) => (float) $v)
                            ->all();
                        $val = count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null;
                    }

                    $nilaiPerMapel[$m->id] = $val;
                    if ($val !== null) {
                        $sum += $val;
                        $cnt++;
                    }
                }

                $rows[] = [
                    'no' => $idx + 1,
                    'kelasSiswa' => $ks,
                    'siswa' => $ks->siswa,
                    'nilai_mapel' => $nilaiPerMapel,
                    'rata_rata' => $cnt > 0 ? round($sum / $cnt, 1) : null,
                ];
            }

            $result[] = [
                'kelas' => $kelas,
                'semester' => $semester,
                'mapels' => $mapels,
                'rows' => $rows,
            ];
        }

        return $result;
    }

    /**
     * Susun data kolektif Daftar Nilai STS atau SAS (semua mapel per kelas).
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    public function buildKolektifStsSasData(
        Collection $kelasList,
        string $periode,
        ?int $filterMapelId = null,
        ?int $kelasSiswaId = null
    ): array {
        $periodeKey = $periode === 'sas' ? 'sas' : 'sts';
        $result = [];

        foreach ($kelasList as $kelas) {
            $semester = strtolower((string) ($kelas->tahunAjaran->semester ?? 'ganjil'));
            $mapels = $kelas->mataPelajaran();
            if ($filterMapelId !== null) {
                $mapels = $mapels->where('id', $filterMapelId)->values();
            }

            $ksList = $kelas->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            if ($kelasSiswaId !== null) {
                $ksList = $ksList->where('id', $kelasSiswaId)->values();
            }

            $ksIds = $ksList->pluck('id');
            $allNilai = Nilai::whereIn('kelas_siswa_id', $ksIds)
                ->where('periode', $periodeKey)
                ->get()
                ->groupBy('kelas_siswa_id');

            $allRaporMapel = NilaiRaporMapel::whereIn('kelas_siswa_id', $ksIds)
                ->where('semester', $semester)
                ->get()
                ->groupBy('kelas_siswa_id');

            $rows = [];
            foreach ($ksList as $idx => $ks) {
                $nSiswa = $allNilai->get($ks->id, collect())->keyBy('mapel_id');
                $rSiswa = $allRaporMapel->get($ks->id, collect())->keyBy('mapel_id');

                $nilaiPerMapel = [];
                $sum = 0.0;
                $cnt = 0;

                foreach ($mapels as $m) {
                    $recN = $nSiswa->get($m->id);
                    $recR = $rSiswa->get($m->id);
                    $val = null;
                    if ($recN && $recN->sumatif !== null) {
                        $val = (float) $recN->sumatif;
                    } elseif ($recR) {
                        $val = $periodeKey === 'sts'
                            ? ($recR->nilai_uts !== null ? (float) $recR->nilai_uts : null)
                            : ($recR->nilai_uas !== null ? (float) $recR->nilai_uas : null);
                    }

                    $kkm = $kelas->getKkmForMapel($m);
                    $nilaiPerMapel[$m->id] = [
                        'nilai' => $val,
                        'format' => $val !== null ? HitungNilaiService::formatAngka($val) : '–',
                        'tuntas' => $val !== null ? $val >= $kkm : null,
                    ];
                    if ($val !== null) {
                        $sum += $val;
                        $cnt++;
                    }
                }

                $rows[] = [
                    'no' => $idx + 1,
                    'kelasSiswa' => $ks,
                    'siswa' => $ks->siswa,
                    'nilai_mapel' => $nilaiPerMapel,
                    'jumlah' => $cnt > 0 ? round($sum, 1) : null,
                    'rata_rata' => $cnt > 0 ? round($sum / $cnt, 1) : null,
                ];
            }

            $result[] = [
                'kelas' => $kelas,
                'semester' => $semester,
                'mapels' => $mapels,
                'rows' => $rows,
            ];
        }

        return $result;
    }

    /**
     * Susun data Rekap Predikat & Catatan Guru seluruh mapel per kelas.
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    public function buildKolektifPredikatCatatanData(
        Collection $kelasList,
        string $periode = 'sas',
        ?int $filterMapelId = null,
        ?int $kelasSiswaId = null
    ): array {
        $result = [];

        foreach ($kelasList as $kelas) {
            $mapels = $kelas->mataPelajaran();
            if ($filterMapelId !== null) {
                $mapels = $mapels->where('id', $filterMapelId)->values();
            }

            $ksList = $kelas->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            if ($kelasSiswaId !== null) {
                $ksList = $ksList->where('id', $kelasSiswaId)->values();
            }

            $siswaItems = [];
            foreach ($ksList as $ks) {
                $hasil = $this->hitungService->hitungNilaiSiswa($ks, $periode, null, null, null, $filterMapelId);
                $siswaItems[] = [
                    'kelasSiswa' => $ks,
                    'siswa' => $ks->siswa,
                    'hasilNilai' => $hasil,
                ];
            }

            $result[] = [
                'kelas' => $kelas,
                'mapels' => $mapels,
                'siswaItems' => $siswaItems,
            ];
        }

        return $result;
    }

    /**
     * Susun data Rapor Lengkap multi-kelas (untuk Cakupan Kelas maupun Sekolah).
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    public function buildKolektifRaporLengkapData(
        Collection $kelasList,
        string $periode = 'sas',
        ?int $filterMapelId = null,
        ?int $kelasSiswaId = null
    ): array {
        $result = [];
        $pembiasaanList = Pembiasaan::aktif()->get();

        foreach ($kelasList as $kelas) {
            $ksList = $kelas->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            if ($kelasSiswaId !== null) {
                $ksList = $ksList->where('id', $kelasSiswaId)->values();
            }

            $ksIds = $ksList->pluck('id');
            $allNilaiPem = NilaiPembiasaan::whereIn('kelas_siswa_id', $ksIds)
                ->where('periode', $periode)
                ->get()
                ->groupBy('kelas_siswa_id');

            $allKehadiran = Kehadiran::whereIn('kelas_siswa_id', $ksIds)
                ->where('periode', $periode)
                ->get()
                ->keyBy('kelas_siswa_id');

            $laporanSiswa = [];
            foreach ($ksList as $ks) {
                $laporanSiswa[] = [
                    'kelasSiswa' => $ks,
                    'siswa' => $ks->siswa,
                    'hasilNilai' => $this->hitungService->hitungNilaiSiswa($ks, $periode, null, null, null, $filterMapelId),
                    'nilaiPemList' => isset($allNilaiPem[$ks->id]) ? $allNilaiPem[$ks->id]->keyBy('pembiasaan_id') : collect(),
                    'kehadiran' => $allKehadiran->get($ks->id) ?? (object) ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                ];
            }

            $result[] = [
                'kelas' => $kelas,
                'tahun' => $kelas->tahunAjaran,
                'pembiasaanList' => $pembiasaanList,
                'laporanSiswa' => $laporanSiswa,
            ];
        }

        return $result;
    }

    /**
     * Susun data Leger Nilai Rapor multi-kelas (dengan optional filter mapel).
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    public function buildKolektifLegerData(
        Collection $kelasList,
        string $periode = 'sas',
        ?int $filterMapelId = null,
        ?int $kelasSiswaId = null
    ): array {
        $result = [];

        foreach ($kelasList as $kelas) {
            $ksList = $kelas->kelasSiswa()->with('siswa')->get()
                ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
            if ($kelasSiswaId !== null) {
                $ksList = $ksList->where('id', $kelasSiswaId)->values();
            }

            $rekap = $this->hitungService->hitungRekapKelas($ksList, $periode, 'ranking');
            if ($filterMapelId !== null && isset($rekap['mapels'])) {
                $rekap['mapels'] = $rekap['mapels']->where('id', $filterMapelId)->values();
            }

            $ksIds = $ksList->pluck('id');
            $kehadiranList = Kehadiran::whereIn('kelas_siswa_id', $ksIds)
                ->where('periode', $periode)
                ->get()
                ->keyBy('kelas_siswa_id');

            $result[] = [
                'kelas' => $kelas,
                'rekap' => $rekap,
                'kehadiranList' => $kehadiranList,
            ];
        }

        return $result;
    }
}
