<?php

namespace App\Services;

use App\Models\Kehadiran;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\NilaiRaporMapel;
use App\Models\Pembiasaan;
use Illuminate\Support\Collection;

class HitungNilaiService
{
    /**
     * Hitung nilai per siswa untuk suatu periode ('sts' atau 'sas')
     */
    public function hitungNilaiSiswa(KelasSiswa $kelasSiswa, string $periode = 'sts'): array
    {
        $kelas = $kelasSiswa->kelas;
        if ($kelas && ! $kelas->relationLoaded('mapelKkm')) {
            $kelas->load('mapelKkm');
        }

        $tahunAjaran = $kelas ? $kelas->tahunAjaran : null;
        $semesterAktif = strtolower($tahunAjaran ? ($tahunAjaran->semester ?? 'ganjil') : 'ganjil');

        $mapels = $kelas ? $kelas->mataPelajaran() : MataPelajaran::aktif()->get();
        $nilaiList = Nilai::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('mapel_id');

        // Ambil data nilai rapor resmi beserta capaian kompetensi
        $raporMapelList = NilaiRaporMapel::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('semester', $semesterAktif)
            ->get()
            ->keyBy('mapel_id');

        $baris = [];
        $totalFormatif = 0;
        $countFormatif = 0;
        $totalSumatif = 0;
        $countSumatif = 0;
        $totalAkhir = 0;
        $countAkhir = 0;
        $mapelKosong = [];

        foreach ($mapels as $index => $mapel) {
            $kkm = $kelas ? $kelas->getKkmForMapel($mapel) : (int) ($mapel->kkm ?? 70);
            $recRapor = $raporMapelList->get($mapel->id);
            $n = $nilaiList->get($mapel->id);
            if (! $n && in_array($periode, ['sas', 'semester'])) {
                $n = Nilai::where('kelas_siswa_id', $kelasSiswa->id)
                    ->where('mapel_id', $mapel->id)
                    ->where('periode', 'sts')
                    ->first();
            }

            // Prioritaskan nilai rapor akhir yang sudah dihitung atau tersimpan di nilai_rapor_mapel
            $nilaiMapelAkhir = null;
            $predikat = null;
            $catatan = '';

            if ($recRapor && $recRapor->nilai_rapor !== null) {
                $nilaiMapelAkhir = (float) $recRapor->nilai_rapor;
                $predikat = $recRapor->predikat ?? NilaiRaporMapel::hitungPredikat($nilaiMapelAkhir);
                $catatan = $recRapor->catatan ?? '';
            } elseif ($n && ($n->sumatif !== null || $n->formatif !== null)) {
                $nilaiMapelAkhir = $n->sumatif !== null ? (float) $n->sumatif : (float) $n->formatif;
                $predikat = NilaiRaporMapel::hitungPredikat($nilaiMapelAkhir);
                $catatan = '';
            }

            if ($nilaiMapelAkhir !== null) {
                $totalAkhir += $nilaiMapelAkhir;
                $countAkhir++;
                $totalSumatif += $nilaiMapelAkhir;
                $countSumatif++;

                // Jika catatan capaian kompetensi masih kosong, beri template deskripsi otomatis sesuai predikat
                if (empty(trim((string) $catatan))) {
                    switch ($predikat) {
                        case 'A':
                            $catatan = "Menunjukkan penguasaan materi yang sangat istimewa dalam seluruh tujuan pembelajaran {$mapel->nama}.";
                            break;
                        case 'B':
                            $catatan = "Menunjukkan pemahaman yang baik dan mampu menguasai tujuan pembelajaran {$mapel->nama} secara optimal.";
                            break;
                        case 'C':
                            $catatan = "Cukup memahami materi pokok {$mapel->nama}, perlu peningkatan latihan mandiri.";
                            break;
                        case 'D':
                            $catatan = "Perlu bimbingan dan pendampingan intensif dalam memahami materi pokok {$mapel->nama}.";
                            break;
                        default:
                            $catatan = "Capaian pembelajaran {$mapel->nama} telah terpenuhi dengan baik.";
                            break;
                    }
                }
                $isKosong = false;
            } else {
                $mapelKosong[] = $mapel->nama;
                $isKosong = true;
                $catatan = 'Belum ada nilai';
            }

            $totalFormatif += $kkm;
            $countFormatif++;

            $keterangan = $nilaiMapelAkhir !== null ? ($nilaiMapelAkhir >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-';

            $baris[] = [
                'no' => $index + 1,
                'mapel_id' => $mapel->id,
                'mapel_nama' => $mapel->nama,
                'kkm' => $kkm,
                'formatif' => $kkm,
                'sumatif' => $nilaiMapelAkhir,
                'nilai' => $nilaiMapelAkhir,
                'nilai_rapor' => $nilaiMapelAkhir,
                'nilai_rapor_format' => $nilaiMapelAkhir !== null ? self::formatAngka($nilaiMapelAkhir) : '-',
                'akhir' => $nilaiMapelAkhir,
                'predikat' => $predikat ?? '-',
                'capaian_kompetensi' => $catatan,
                'catatan' => $catatan,
                'keterangan' => $keterangan,
                'is_kosong' => $isKosong,
                'formatif_format' => (string) $kkm,
                'sumatif_format' => $nilaiMapelAkhir !== null ? self::formatAngka($nilaiMapelAkhir) : '-',
                'akhir_format' => $nilaiMapelAkhir !== null ? self::formatAngka($nilaiMapelAkhir) : '-',
            ];
        }

        $rataFormatif = $countFormatif > 0 ? round($totalFormatif / $countFormatif, 2) : 70;
        $rataSumatif = $countSumatif > 0 ? round($totalSumatif / $countSumatif, 2) : 0;
        $rataAkhir = $countAkhir > 0 ? round($totalAkhir / $countAkhir, 2) : 0;
        $predikatRata = $countAkhir > 0 ? NilaiRaporMapel::hitungPredikat($rataAkhir) : '-';

        return [
            'baris' => $baris,
            'total_formatif' => $totalFormatif,
            'count_formatif' => $countFormatif,
            'rata_formatif' => $rataFormatif,
            'rata_formatif_format' => self::formatAngka($rataFormatif),
            'total_sumatif' => $totalSumatif,
            'count_sumatif' => $countSumatif,
            'rata_sumatif' => $rataSumatif,
            'rata_sumatif_format' => self::formatAngka($rataSumatif),
            'total_akhir' => $totalAkhir,
            'rata_akhir' => $rataAkhir,
            'rata_akhir_format' => self::formatAngka($rataAkhir),
            'predikat_rata' => $predikatRata,
            'total_nilai' => $totalAkhir,
            'rata_nilai' => $rataAkhir,
            'rata_nilai_format' => self::formatAngka($rataAkhir),
            'total_mapel' => $mapels->count(),
            'mapel_kosong' => $mapelKosong,
            'jumlah_kosong' => count($mapelKosong),
            'ada_nilai_kosong' => count($mapelKosong) > 0,
            'terisi_semua' => count($mapelKosong) === 0,
        ];
    }

    /**
     * Hitung rekap nilai seluruh siswa dalam satu kelas
     */
    public function hitungRekapKelas(array|Collection $kelasSiswaList, string $periode = 'sts', string $sortBy = 'ranking'): array
    {
        $sampleKelas = $kelasSiswaList instanceof Collection ? $kelasSiswaList->first()?->kelas : ($kelasSiswaList[0]->kelas ?? null);
        $mapels = $sampleKelas ? $sampleKelas->mataPelajaran() : MataPelajaran::aktif()->get();
        $rekapSiswa = [];

        $mapelTotal = [];
        $mapelCount = [];
        foreach ($mapels as $m) {
            $mapelTotal[$m->id] = 0;
            $mapelCount[$m->id] = 0;
        }

        $totalKelasSemua = 0;
        $totalSiswaSelesai = 0;

        foreach ($kelasSiswaList as $ks) {
            $hasil = $this->hitungNilaiSiswa($ks, $periode);
            $rekapSiswa[] = [
                'kelas_siswa' => $ks,
                'siswa' => $ks->siswa,
                'hasil' => $hasil,
            ];

            foreach ($hasil['baris'] as $b) {
                if ($b['sumatif'] !== null) {
                    $mapelTotal[$b['mapel_id']] += $b['sumatif'];
                    $mapelCount[$b['mapel_id']]++;
                }
            }

            if ($hasil['terisi_semua']) {
                $totalSiswaSelesai++;
            }
            $totalKelasSemua += $hasil['rata_sumatif'];
        }

        $jumlahSiswa = count($kelasSiswaList);
        $rataRataKelas = $jumlahSiswa > 0 ? round($totalKelasSemua / $jumlahSiswa, 2) : 0;

        if ($sampleKelas && ! $sampleKelas->relationLoaded('mapelKkm')) {
            $sampleKelas->load('mapelKkm');
        }

        $rekapMapel = [];
        foreach ($mapels as $m) {
            $count = $mapelCount[$m->id];
            $avg = $count > 0 ? round($mapelTotal[$m->id] / $count, 2) : 0;
            $kkm = $sampleKelas ? $sampleKelas->getKkmForMapel($m) : (int) ($m->kkm ?? 70);
            $rekapMapel[$m->id] = [
                'mapel' => $m,
                'kkm' => $kkm,
                'rata_rata' => $avg,
                'rata_rata_format' => self::formatAngka($avg),
                'terisi' => $count,
                'total_siswa' => $jumlahSiswa,
            ];
        }

        // Urutkan seluruh siswa dari total_sumatif tertinggi ke terendah
        usort($rekapSiswa, function ($a, $b) {
            $hasScoreA = ($a['hasil']['count_sumatif'] ?? 0) > 0;
            $hasScoreB = ($b['hasil']['count_sumatif'] ?? 0) > 0;

            if ($hasScoreA && ! $hasScoreB) {
                return -1;
            }
            if (! $hasScoreA && $hasScoreB) {
                return 1;
            }

            $scoreA = (float) ($a['hasil']['total_sumatif'] ?? 0);
            $scoreB = (float) ($b['hasil']['total_sumatif'] ?? 0);
            if (abs($scoreA - $scoreB) < 0.0001) {
                return strcasecmp($a['siswa']->nama ?? '', $b['siswa']->nama ?? '');
            }

            return ($scoreA < $scoreB) ? 1 : -1;
        });

        // Tetapkan peringkat dari 1 sampai terakhir
        $prevScore = null;
        $currentRank = 1;
        foreach ($rekapSiswa as $idx => &$item) {
            $score = (float) ($item['hasil']['total_sumatif'] ?? 0);
            if ($prevScore !== null && abs($score - $prevScore) < 0.0001) {
                $item['peringkat'] = $currentRank;
            } else {
                $currentRank = $idx + 1;
                $prevScore = $score;
                $item['peringkat'] = $currentRank;
            }
        }
        unset($item);

        // Buat data visualisasi grafik nilai berdasarkan urutan ranking, kehadiran, dan pembiasaan
        $chartData = $this->generateChartData($mapels, $rekapMapel, $rekapSiswa, $jumlahSiswa, $kelasSiswaList, $periode);

        // Terapkan pengurutan data sesuai $sortBy
        if ($sortBy === 'nama') {
            usort($rekapSiswa, function ($a, $b) {
                return strcasecmp($a['siswa']->nama ?? '', $b['siswa']->nama ?? '');
            });
        } elseif ($sortBy === 'nis') {
            usort($rekapSiswa, function ($a, $b) {
                return strcasecmp($a['siswa']->nis ?? '', $b['siswa']->nis ?? '');
            });
        }

        return [
            'siswa_list' => $rekapSiswa,
            'mapels' => $mapels,
            'rekap_mapel' => $rekapMapel,
            'jumlah_siswa' => $jumlahSiswa,
            'siswa_selesai' => $totalSiswaSelesai,
            'persen_selesai' => $jumlahSiswa > 0 ? round(($totalSiswaSelesai / $jumlahSiswa) * 100, 1) : 0,
            'rata_rata_kelas' => $rataRataKelas,
            'rata_rata_kelas_format' => self::formatAngka($rataRataKelas),
            'sort_by' => $sortBy,
            'chart_data' => $chartData,
        ];
    }

    /**
     * Susun struktur data khusus visualisasi grafik rekap nilai
     */
    public function generateChartData(
        Collection|array $mapels,
        array $rekapMapel,
        array $rekapSiswaByRank,
        int $jumlahSiswa,
        Collection|array $kelasSiswaList = [],
        string $periode = 'sts'
    ): array {
        $chartMapelLabels = [];
        $chartMapelFullNames = [];
        $chartMapelAvg = [];
        $chartMapelKkm = [];
        $chartMapelStatus = [];

        $mapelTertinggi = null;
        $mapelTerendah = null;
        $maxMapelAvg = -1.0;
        $minMapelAvg = 9999.0;

        foreach ($mapels as $m) {
            $r = $rekapMapel[$m->id] ?? null;
            $avg = $r ? (float) $r['rata_rata'] : 0.0;
            $kkm = $r ? (int) $r['kkm'] : 70;
            $terisi = $r ? (int) $r['terisi'] : 0;

            $chartMapelLabels[] = $m->nama_singkat;
            $chartMapelFullNames[] = $m->nama;
            $chartMapelAvg[] = $avg;
            $chartMapelKkm[] = $kkm;
            $chartMapelStatus[] = $avg >= $kkm;

            if ($terisi > 0) {
                if ($avg > $maxMapelAvg) {
                    $maxMapelAvg = $avg;
                    $mapelTertinggi = [
                        'nama' => $m->nama,
                        'nama_singkat' => $m->nama_singkat,
                        'rata_rata' => $avg,
                        'rata_rata_format' => self::formatAngka($avg),
                    ];
                }
                if ($avg < $minMapelAvg) {
                    $minMapelAvg = $avg;
                    $mapelTerendah = [
                        'nama' => $m->nama,
                        'nama_singkat' => $m->nama_singkat,
                        'rata_rata' => $avg,
                        'rata_rata_format' => self::formatAngka($avg),
                    ];
                }
            }
        }

        $countSangatBaik = 0;      // >= 85
        $countBaik = 0;            // 75 - 84.99
        $countCukup = 0;           // 65 - 74.99
        $countPerluBimbingan = 0;   // < 65
        $countKosong = 0;

        $highestScore = null;
        $highestSiswa = null;
        $lowestScore = null;
        $lowestSiswa = null;

        $rankingItems = [];
        $countTuntasKkm = 0;

        $siswaRentang1 = []; // 0 - 25
        $siswaRentang2 = []; // 26 - 50
        $siswaRentang3 = []; // 51 - 75
        $siswaRentang4 = []; // 76 - 100

        $kkmList = array_values($chartMapelKkm);
        $rataKkmKelas = count($kkmList) > 0 ? round(array_sum($kkmList) / count($kkmList), 1) : 70.0;

        foreach ($rekapSiswaByRank as $item) {
            $h = $item['hasil'] ?? [];
            $countSumatif = (int) ($h['count_sumatif'] ?? 0);

            if ($countSumatif > 0) {
                $rata = (float) ($h['rata_sumatif'] ?? 0);

                if ($rata >= 85) {
                    $countSangatBaik++;
                } elseif ($rata >= 75) {
                    $countBaik++;
                } elseif ($rata >= 65) {
                    $countCukup++;
                } else {
                    $countPerluBimbingan++;
                }

                // Pengelompokan siswa per rentang nilai rata-rata (0-25, 26-50, 51-75, 76-100)
                $siswaInfo = [
                    'nama' => $item['siswa']->nama ?? 'Siswa',
                    'nis' => $item['siswa']->nis ?? '-',
                    'rata_rata' => $rata,
                    'rata_rata_format' => self::formatAngka($rata),
                ];
                if ($rata <= 25.0) {
                    $siswaRentang1[] = $siswaInfo;
                } elseif ($rata <= 50.0) {
                    $siswaRentang2[] = $siswaInfo;
                } elseif ($rata <= 75.0) {
                    $siswaRentang3[] = $siswaInfo;
                } else {
                    $siswaRentang4[] = $siswaInfo;
                }

                if ($rata >= $rataKkmKelas) {
                    $countTuntasKkm++;
                }

                if ($highestScore === null || $rata > $highestScore) {
                    $highestScore = $rata;
                    $highestSiswa = $item['siswa']->nama ?? 'Siswa';
                }
                if ($lowestScore === null || $rata < $lowestScore) {
                    $lowestScore = $rata;
                    $lowestSiswa = $item['siswa']->nama ?? 'Siswa';
                }

                $rankingItems[] = [
                    'peringkat' => $item['peringkat'] ?? (count($rankingItems) + 1),
                    'nama' => $item['siswa']->nama ?? 'Siswa',
                    'nis' => $item['siswa']->nis ?? '-',
                    'rata_rata' => $rata,
                    'rata_rata_format' => self::formatAngka($rata),
                    'total' => (float) ($h['total_sumatif'] ?? 0),
                    'total_format' => self::formatAngka($h['total_sumatif'] ?? 0),
                ];
            } else {
                $countKosong++;
            }
        }

        if (empty($kelasSiswaList) && ! empty($rekapSiswaByRank)) {
            $kelasSiswaList = collect($rekapSiswaByRank)->pluck('kelas_siswa')->filter();
        }
        $kelasSiswaIds = collect($kelasSiswaList)->pluck('id');

        // 1. Perhitungan Data Grafik Kehadiran Siswa (SIA: Sakit, Izin, Tanpa Keterangan)
        $kehadiranRecords = count($kelasSiswaIds) > 0 ? Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get() : collect();

        $totalSakit = (int) $kehadiranRecords->sum('sakit');
        $totalIzin = (int) $kehadiranRecords->sum('izin');
        $totalAlpa = (int) $kehadiranRecords->sum('alpa');
        $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;

        $siswaHadirPenuh = 0;
        $siswaPernahAbsen = 0;
        $kehadiranByKs = $kehadiranRecords->keyBy('kelas_siswa_id');

        foreach ($kelasSiswaList as $ks) {
            $kh = $kehadiranByKs->get($ks->id);
            $s = $kh ? (int) $kh->sakit : 0;
            $i = $kh ? (int) $kh->izin : 0;
            $a = $kh ? (int) $kh->alpa : 0;
            if (($s + $i + $a) === 0) {
                $siswaHadirPenuh++;
            } else {
                $siswaPernahAbsen++;
            }
        }

        $persenHadirPenuh = $jumlahSiswa > 0 ? round(($siswaHadirPenuh / $jumlahSiswa) * 100, 1) : 0;
        $rataAbsen = $jumlahSiswa > 0 ? round($totalTidakHadir / $jumlahSiswa, 1) : 0;

        $pctSakit = $totalTidakHadir > 0 ? round(($totalSakit / $totalTidakHadir) * 100, 1) : 0;
        $pctIzin = $totalTidakHadir > 0 ? round(($totalIzin / $totalTidakHadir) * 100, 1) : 0;
        $pctAlpa = $totalTidakHadir > 0 ? round(($totalAlpa / $totalTidakHadir) * 100, 1) : 0;

        $chartKehadiran = [
            'has_data' => $kehadiranRecords->count() > 0,
            'total_sakit' => $totalSakit,
            'total_izin' => $totalIzin,
            'total_alpa' => $totalAlpa,
            'total_tidak_hadir' => $totalTidakHadir,
            'siswa_hadir_penuh' => $siswaHadirPenuh,
            'siswa_pernah_absen' => $siswaPernahAbsen,
            'persen_hadir_penuh' => $persenHadirPenuh,
            'rata_absen' => $rataAbsen,
            'labels' => ['Sakit (S)', 'Izin (I)', 'Tanpa Keterangan (A)'],
            'counts' => [$totalSakit, $totalIzin, $totalAlpa],
            'percentages' => [$pctSakit, $pctIzin, $pctAlpa],
            'colors' => ['#3b82f6', '#f59e0b', '#ef4444'],
        ];

        // 2. Perhitungan Data Grafik Capaian Pembiasaan Siswa
        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();
        $nilaiPembiasaanRecords = count($kelasSiswaIds) > 0 ? NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get() : collect();

        $npByMap = [];
        foreach ($nilaiPembiasaanRecords as $np) {
            $pId = $np->pembiasaan_id;
            $pred = trim((string) $np->predikat);
            if (! isset($npByMap[$pId])) {
                $npByMap[$pId] = [
                    'Sangat Baik' => 0,
                    'Baik' => 0,
                    'Cukup' => 0,
                    'Perlu Bimbingan' => 0,
                ];
            }
            if (isset($npByMap[$pId][$pred])) {
                $npByMap[$pId][$pred]++;
            } elseif ($pred === 'SB' || stripos($pred, 'sangat') !== false) {
                $npByMap[$pId]['Sangat Baik']++;
            } elseif ($pred === 'B' || stripos($pred, 'baik') !== false) {
                $npByMap[$pId]['Baik']++;
            } elseif ($pred === 'C' || stripos($pred, 'cukup') !== false) {
                $npByMap[$pId]['Cukup']++;
            } else {
                $npByMap[$pId]['Perlu Bimbingan']++;
            }
        }

        $chartPemLabels = [];
        $chartPemFullNames = [];
        $dataSangatBaik = [];
        $dataBaik = [];
        $dataCukup = [];
        $dataPerluBimbingan = [];

        $totSangatBaik = 0;
        $totBaik = 0;
        $totCukup = 0;
        $totPerluBimbingan = 0;

        foreach ($pembiasaans as $p) {
            $chartPemLabels[] = $p->nama;
            $chartPemFullNames[] = $p->nama;

            $counts = $npByMap[$p->id] ?? [
                'Sangat Baik' => 0,
                'Baik' => 0,
                'Cukup' => 0,
                'Perlu Bimbingan' => 0,
            ];

            $sb = $counts['Sangat Baik'];
            $b = $counts['Baik'];
            $c = $counts['Cukup'];
            $pb = $counts['Perlu Bimbingan'];

            $dataSangatBaik[] = $sb;
            $dataBaik[] = $b;
            $dataCukup[] = $c;
            $dataPerluBimbingan[] = $pb;

            $totSangatBaik += $sb;
            $totBaik += $b;
            $totCukup += $c;
            $totPerluBimbingan += $pb;
        }

        $totalPredikat = $totSangatBaik + $totBaik + $totCukup + $totPerluBimbingan;
        $pctTotSb = $totalPredikat > 0 ? round(($totSangatBaik / $totalPredikat) * 100, 1) : 0;
        $pctTotB = $totalPredikat > 0 ? round(($totBaik / $totalPredikat) * 100, 1) : 0;
        $pctTotC = $totalPredikat > 0 ? round(($totCukup / $totalPredikat) * 100, 1) : 0;
        $pctTotPb = $totalPredikat > 0 ? round(($totPerluBimbingan / $totalPredikat) * 100, 1) : 0;

        $chartPembiasaan = [
            'has_data' => $nilaiPembiasaanRecords->count() > 0,
            'labels' => $chartPemLabels,
            'full_names' => $chartPemFullNames,
            'datasets' => [
                'sangat_baik' => $dataSangatBaik,
                'baik' => $dataBaik,
                'cukup' => $dataCukup,
                'perlu_bimbingan' => $dataPerluBimbingan,
            ],
            'totals' => [
                'sangat_baik' => $totSangatBaik,
                'baik' => $totBaik,
                'cukup' => $totCukup,
                'perlu_bimbingan' => $totPerluBimbingan,
                'total' => $totalPredikat,
            ],
            'percentages' => [
                'sangat_baik' => $pctTotSb,
                'baik' => $pctTotB,
                'cukup' => $pctTotC,
                'perlu_bimbingan' => $pctTotPb,
            ],
        ];

        $totalDinilai = count($rankingItems);
        $hasData = $totalDinilai > 0 || $chartKehadiran['has_data'] || $chartPembiasaan['has_data'];

        $countR1 = count($siswaRentang1);
        $countR2 = count($siswaRentang2);
        $countR3 = count($siswaRentang3);
        $countR4 = count($siswaRentang4);

        $pctR1 = $totalDinilai > 0 ? round(($countR1 / $totalDinilai) * 100, 1) : 0;
        $pctR2 = $totalDinilai > 0 ? round(($countR2 / $totalDinilai) * 100, 1) : 0;
        $pctR3 = $totalDinilai > 0 ? round(($countR3 / $totalDinilai) * 100, 1) : 0;
        $pctR4 = $totalDinilai > 0 ? round(($countR4 / $totalDinilai) * 100, 1) : 0;

        // Perhitungan sebaran rentang nilai (0-25, 26-50, 51-75, 76-100) per mata pelajaran
        $perMapelSiswa = [];
        foreach ($mapels as $m) {
            $perMapelSiswa[$m->id] = [
                'r1' => [],
                'r2' => [],
                'r3' => [],
                'r4' => [],
            ];
        }

        foreach ($rekapSiswaByRank as $item) {
            $sNama = $item['siswa']->nama ?? 'Siswa';
            $sNis = $item['siswa']->nis ?? '-';
            $barisList = $item['hasil']['baris'] ?? [];
            foreach ($barisList as $b) {
                $mId = $b['mapel_id'] ?? null;
                $scoreVal = $b['sumatif'] ?? $b['nilai'] ?? null;
                if ($mId && isset($perMapelSiswa[$mId]) && $scoreVal !== null) {
                    $val = (float) $scoreVal;
                    $sInfo = [
                        'nama' => $sNama,
                        'nis' => $sNis,
                        'nilai' => $val,
                        'nilai_format' => self::formatAngka($val),
                    ];
                    if ($val <= 25.0) {
                        $perMapelSiswa[$mId]['r1'][] = $sInfo;
                    } elseif ($val <= 50.0) {
                        $perMapelSiswa[$mId]['r2'][] = $sInfo;
                    } elseif ($val <= 75.0) {
                        $perMapelSiswa[$mId]['r3'][] = $sInfo;
                    } else {
                        $perMapelSiswa[$mId]['r4'][] = $sInfo;
                    }
                }
            }
        }

        $perMapelData = [];
        $dsMapelR1 = [];
        $dsMapelR2 = [];
        $dsMapelR3 = [];
        $dsMapelR4 = [];

        foreach ($mapels as $m) {
            $r1 = $perMapelSiswa[$m->id]['r1'];
            $r2 = $perMapelSiswa[$m->id]['r2'];
            $r3 = $perMapelSiswa[$m->id]['r3'];
            $r4 = $perMapelSiswa[$m->id]['r4'];

            $c1 = count($r1);
            $c2 = count($r2);
            $c3 = count($r3);
            $c4 = count($r4);
            $tot = $c1 + $c2 + $c3 + $c4;

            $p1 = $tot > 0 ? round(($c1 / $tot) * 100, 1) : 0;
            $p2 = $tot > 0 ? round(($c2 / $tot) * 100, 1) : 0;
            $p3 = $tot > 0 ? round(($c3 / $tot) * 100, 1) : 0;
            $p4 = $tot > 0 ? round(($c4 / $tot) * 100, 1) : 0;

            $dsMapelR1[] = $c1;
            $dsMapelR2[] = $c2;
            $dsMapelR3[] = $c3;
            $dsMapelR4[] = $c4;

            $perMapelData[$m->id] = [
                'id' => $m->id,
                'nama' => $m->nama,
                'nama_singkat' => $m->nama_singkat ?? $m->nama,
                'kkm' => $chartMapelKkm[$m->id] ?? 70,
                'counts' => [$c1, $c2, $c3, $c4],
                'percentages' => [$p1, $p2, $p3, $p4],
                'total_dinilai' => $tot,
                'total_siswa' => $jumlahSiswa,
                'siswa' => [
                    $r1,
                    $r2,
                    $r3,
                    $r4,
                ],
            ];
        }

        $totAllR1 = array_sum($dsMapelR1);
        $totAllR2 = array_sum($dsMapelR2);
        $totAllR3 = array_sum($dsMapelR3);
        $totAllR4 = array_sum($dsMapelR4);
        $totAllNilai = $totAllR1 + $totAllR2 + $totAllR3 + $totAllR4;
        $pctAllR1 = $totAllNilai > 0 ? round(($totAllR1 / $totAllNilai) * 100, 1) : 0;
        $pctAllR2 = $totAllNilai > 0 ? round(($totAllR2 / $totAllNilai) * 100, 1) : 0;
        $pctAllR3 = $totAllNilai > 0 ? round(($totAllR3 / $totAllNilai) * 100, 1) : 0;
        $pctAllR4 = $totAllNilai > 0 ? round(($totAllR4 / $totAllNilai) * 100, 1) : 0;

        $chartRentangNilai = [
            'labels' => ['0 - 25', '26 - 50', '51 - 75', '76 - 100'],
            'full_labels' => ['Rentang 0 - 25', 'Rentang 26 - 50', 'Rentang 51 - 75', 'Rentang 76 - 100'],
            'keterangan' => ['Perlu Intervensi Khusus', 'Perlu Bimbingan', 'Cukup / Memadai', 'Baik & Sangat Baik'],
            'counts' => [$countR1, $countR2, $countR3, $countR4],
            'percentages' => [$pctR1, $pctR2, $pctR3, $pctR4],
            'colors' => ['#ef4444', '#f97316', '#3b82f6', '#10b981'],
            'border_colors' => ['#dc2626', '#ea580c', '#2563eb', '#059669'],
            'siswa' => [
                $siswaRentang1,
                $siswaRentang2,
                $siswaRentang3,
                $siswaRentang4,
            ],
            'total_dinilai' => $totalDinilai,
            'total_siswa' => $jumlahSiswa,
            'per_mapel' => $perMapelData,
            'mapel_labels' => $chartMapelLabels,
            'mapel_full_names' => $chartMapelFullNames,
            'datasets_by_mapel' => [
                'r1_0_25' => $dsMapelR1,
                'r2_26_50' => $dsMapelR2,
                'r3_51_75' => $dsMapelR3,
                'r4_76_100' => $dsMapelR4,
            ],
            'kumulatif_mapel' => [
                'counts' => [$totAllR1, $totAllR2, $totAllR3, $totAllR4],
                'percentages' => [$pctAllR1, $pctAllR2, $pctAllR3, $pctAllR4],
                'total_nilai' => $totAllNilai,
            ],
        ];

        $pctSangatBaik = $totalDinilai > 0 ? round(($countSangatBaik / $totalDinilai) * 100, 1) : 0;
        $pctBaik = $totalDinilai > 0 ? round(($countBaik / $totalDinilai) * 100, 1) : 0;
        $pctCukup = $totalDinilai > 0 ? round(($countCukup / $totalDinilai) * 100, 1) : 0;
        $pctPerluBimbingan = $totalDinilai > 0 ? round(($countPerluBimbingan / $totalDinilai) * 100, 1) : 0;
        $persenTuntasKkm = $totalDinilai > 0 ? round(($countTuntasKkm / $totalDinilai) * 100, 1) : 0;

        return [
            'has_data' => $hasData,
            'mapel' => [
                'labels' => $chartMapelLabels,
                'full_names' => $chartMapelFullNames,
                'averages' => $chartMapelAvg,
                'kkm' => $chartMapelKkm,
                'status' => $chartMapelStatus,
            ],
            'distribusi' => [
                'labels' => ['Sangat Baik (≥ 85)', 'Baik (75 - 84)', 'Cukup (65 - 74)', 'Perlu Bimbingan (< 65)'],
                'counts' => [$countSangatBaik, $countBaik, $countCukup, $countPerluBimbingan],
                'percentages' => [$pctSangatBaik, $pctBaik, $pctCukup, $pctPerluBimbingan],
                'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                'count_kosong' => $countKosong,
                'total_dinilai' => $totalDinilai,
                'total_siswa' => $jumlahSiswa,
            ],
            'ranking' => $rankingItems,
            'rentang_nilai' => $chartRentangNilai,
            'kehadiran' => $chartKehadiran,
            'pembiasaan' => $chartPembiasaan,
            'summary' => [
                'mapel_tertinggi' => $mapelTertinggi,
                'mapel_terendah' => $mapelTerendah,
                'siswa_tertinggi' => $highestScore !== null ? [
                    'nama' => $highestSiswa,
                    'nilai' => $highestScore,
                    'nilai_format' => self::formatAngka($highestScore),
                ] : null,
                'siswa_terendah' => $lowestScore !== null ? [
                    'nama' => $lowestSiswa,
                    'nilai' => $lowestScore,
                    'nilai_format' => self::formatAngka($lowestScore),
                ] : null,
                'persen_tuntas_kkm' => $persenTuntasKkm,
                'rata_kkm_kelas' => $rataKkmKelas,
            ],
        ];
    }

    /**
     * Format angka dengan koma (contoh 86.5 -> "86,5")
     */
    public static function formatAngka(float|int|string $angka): string
    {
        $val = (float) $angka;
        // Jika bulat, tampilkan tanpa desimal atau dengan 1 desimal jika diperlukan
        if (floor($val) == $val) {
            return (string) ((int) $val);
        }
        $formatted = number_format($val, 2, ',', '');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
