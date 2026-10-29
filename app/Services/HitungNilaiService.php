<?php

namespace App\Services;

use App\Models\AbsensiHarian;
use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiPembiasaan;
use App\Models\NilaiRaporMapel;
use App\Models\Pembiasaan;
use App\Models\Sekolah;
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

        $fase = $kelas ? ($kelas->fase ?? 'Fase A') : 'Fase A';
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

                // Jika catatan capaian kompetensi masih kosong atau berisi format default/placeholder lama, generate format CP Kurikulum Merdeka resmi
                $isCatatanKosong = empty(trim((string) $catatan))
                    || str_starts_with(trim($catatan), 'Catatan tes otomatis')
                    || str_starts_with(trim($catatan), 'Menunjukkan penguasaan materi yang sangat istimewa dalam seluruh tujuan')
                    || str_starts_with(trim($catatan), 'Menunjukkan pemahaman yang baik dan mampu menguasai tujuan')
                    || str_starts_with(trim($catatan), 'Cukup memahami materi pokok')
                    || str_starts_with(trim($catatan), 'Perlu bimbingan dan pendampingan intensif dalam memahami materi pokok')
                    || str_starts_with(trim($catatan), 'Capaian pembelajaran');

                if ($isCatatanKosong) {
                    $catatan = self::generateDeskripsiCpKurikulumMerdeka($mapel, $nilaiMapelAkhir, $fase);
                }
                $isKosong = false;
            } else {
                $mapelKosong[] = $mapel->nama;
                $isKosong = true;
                $catatan = 'Belum ada penilaian capaian pembelajaran';
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

        // -------------------------------------------------------------------------
        // INTEGRASI KEHADIRAN (SIA) KE KARAKTER & SYARAT KELAYAKAN KENAIKAN KELAS
        // -------------------------------------------------------------------------
        $kehadiran = Kehadiran::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->first();
        $sakit = $kehadiran ? (int) $kehadiran->sakit : 0;
        $izin = $kehadiran ? (int) $kehadiran->izin : 0;
        $alpa = $kehadiran ? (int) $kehadiran->alpa : 0;
        $totalAbsen = $sakit + $izin + $alpa;

        $sekolah = $kelas ? ($kelas->sekolah ?? \App\Models\Sekolah::first()) : \App\Models\Sekolah::first();
        $hariEfektif = $sekolah ? $sekolah->getHariEfektif() : 108;
        $persenMinKenaikan = $sekolah ? $sekolah->getPersenKehadiranMinimal() : 75;
        $batasAlpa = $sekolah ? $sekolah->getBatasAlpaMaksimal() : 3;
        $batasIzin = $sekolah ? $sekolah->getBatasIzinMaksimal() : 7;

        $hariHadir = max(0, $hariEfektif - $totalAbsen);
        $persenKehadiran = $hariEfektif > 0 ? round(($hariHadir / $hariEfektif) * 100, 1) : 100.0;

        $isPeringatanKehadiran = false;
        $pesanKehadiran = [];
        $statusKelayakan = 'Memenuhi Syarat';

        if ($alpa > $batasAlpa) {
            $isPeringatanKehadiran = true;
            $pesanKehadiran[] = "Tanpa keterangan (Alpa) mencapai {$alpa} hari (melebihi batas toleransi {$batasAlpa} hari).";
            $statusKelayakan = 'Perlu Pertimbangan Khusus Dewan Guru';
        }

        if ($persenKehadiran < $persenMinKenaikan) {
            $isPeringatanKehadiran = true;
            $pesanKehadiran[] = "Kehadiran aktif ({$persenKehadiran}%) berada di bawah syarat minimal kenaikan kelas ({$persenMinKenaikan}%).";
            $statusKelayakan = 'Belum Memenuhi Syarat Minimal Kehadiran';
        }

        if ($izin > $batasIzin) {
            $pesanKehadiran[] = "Jumlah izin mencapai {$izin} hari (melebihi batas wajar {$batasIzin} hari).";
        }

        // Usulan predikat karakter kemandirian / kedisiplinan otomatis berdasarkan SIA
        $usulanKemandirian = 'Baik';
        if ($alpa === 0 && $persenKehadiran >= 90) {
            $usulanKemandirian = 'Sangat Baik';
        } elseif ($alpa <= 2 && $persenKehadiran >= $persenMinKenaikan) {
            $usulanKemandirian = 'Baik';
        } elseif ($alpa <= 5 || $persenKehadiran >= 60) {
            $usulanKemandirian = 'Cukup';
        } else {
            $usulanKemandirian = 'Perlu Bimbingan';
        }

        $namaSiswa = $kelasSiswa->siswa->nama ?? 'Peserta Didik';
        $catatanWaliKelasOtomatis = self::generateCatatanWaliKelas($rataAkhir, $persenKehadiran, $alpa, $sakit, $izin, $namaSiswa);

        $kehadiranInfo = [
            'hari_efektif' => $hariEfektif,
            'hari_hadir' => $hariHadir,
            'persen_kehadiran' => $persenKehadiran,
            'persen_kehadiran_format' => self::formatAngka($persenKehadiran) . '%',
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'total_absen' => $totalAbsen,
            'persen_minimal' => $persenMinKenaikan,
            'batas_alpa' => $batasAlpa,
            'batas_izin' => $batasIzin,
            'status_kelayakan' => $statusKelayakan,
            'is_peringatan' => $isPeringatanKehadiran,
            'pesan_peringatan' => $pesanKehadiran,
            'usulan_kemandirian' => $usulanKemandirian,
            'catatan_wali_otomatis' => $catatanWaliKelasOtomatis,
        ];

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
            'kehadiran_info' => $kehadiranInfo,
            'is_peringatan_kehadiran' => $isPeringatanKehadiran,
            'pesan_kehadiran' => $pesanKehadiran,
            'status_kelayakan' => $statusKelayakan,
            'catatan_wali_otomatis' => $catatanWaliKelasOtomatis,
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
        if (empty($kelasSiswaList) && ! empty($rekapSiswaByRank)) {
            $kelasSiswaList = collect($rekapSiswaByRank)->pluck('kelas_siswa')->filter();
        }
        $kelasSiswaIds = collect($kelasSiswaList)->pluck('id');
        $sampleKs = collect($kelasSiswaList)->first();
        $kelas = $sampleKs?->kelas;
        $sekolah = $kelas?->sekolah ?? Sekolah::first();
        $batasAlpa = $sekolah ? $sekolah->getBatasAlpaMaksimal() : 3;

        // Map KKM per mapel
        $chartMapelKkm = [];
        foreach ($mapels as $m) {
            $chartMapelKkm[$m->id] = (int) ($rekapMapel[$m->id]['kkm'] ?? ($m->kkm ?? 70));
        }

        // =========================================================================
        // 1. DATASET AKADEMIK: RAPOR, UTS, UAS, FORMATIF RATA & BULANAN (JUL-DES)
        // =========================================================================
        $raporMapelRecords = count($kelasSiswaIds) > 0 ? NilaiRaporMapel::whereIn('kelas_siswa_id', $kelasSiswaIds)->get() : collect();
        $formatifBulananRecords = count($kelasSiswaIds) > 0 ? NilaiFormatifBulanan::whereIn('kelas_siswa_id', $kelasSiswaIds)->get() : collect();
        $nilaiRegulerRecords = count($kelasSiswaIds) > 0 ? Nilai::whereIn('kelas_siswa_id', $kelasSiswaIds)->get() : collect();

        $matrixRapor = [];
        $matrixUts = [];
        $matrixUas = [];
        $matrixFormatif = [];
        $matrixFormatifBulan = [
            7 => [], 8 => [], 9 => [], 10 => [], 11 => [], 12 => [],
        ];

        // Isi matriks Rapor dari rekapSiswaByRank
        foreach ($rekapSiswaByRank as $item) {
            $ksId = $item['kelas_siswa']->id ?? ($item['siswa']->id ?? null);
            if (! $ksId) continue;
            $barisList = $item['hasil']['baris'] ?? [];
            foreach ($barisList as $b) {
                $mId = $b['mapel_id'] ?? null;
                $val = $b['sumatif'] ?? $b['nilai'] ?? null;
                if ($mId && $val !== null) {
                    $matrixRapor[$ksId][$mId] = (float) $val;
                }
            }
        }

        // Timpa / lengkapi dari tabel NilaiRaporMapel
        foreach ($raporMapelRecords as $rrm) {
            $ksId = $rrm->kelas_siswa_id;
            $mId = $rrm->mapel_id;
            if ($rrm->nilai_rapor !== null) {
                $matrixRapor[$ksId][$mId] = (float) $rrm->nilai_rapor;
            }
            if ($rrm->nilai_uts !== null) {
                $matrixUts[$ksId][$mId] = (float) $rrm->nilai_uts;
            }
            if ($rrm->nilai_uas !== null) {
                $matrixUas[$ksId][$mId] = (float) $rrm->nilai_uas;
            }
            if ($rrm->rata_formatif !== null) {
                $matrixFormatif[$ksId][$mId] = (float) $rrm->rata_formatif;
            }
        }

        // Fallback untuk UTS & UAS dari tabel Nilai
        foreach ($nilaiRegulerRecords as $nr) {
            $ksId = $nr->kelas_siswa_id;
            $mId = $nr->mapel_id;
            $val = $nr->sumatif !== null ? (float) $nr->sumatif : ($nr->nilai !== null ? (float) $nr->nilai : ($nr->formatif !== null ? (float) $nr->formatif : null));
            if ($val !== null) {
                if ($nr->periode === 'sts' && ! isset($matrixUts[$ksId][$mId])) {
                    $matrixUts[$ksId][$mId] = $val;
                } elseif (in_array($nr->periode, ['sas', 'semester']) && ! isset($matrixUas[$ksId][$mId])) {
                    $matrixUas[$ksId][$mId] = $val;
                }
            }
        }

        // Isi Formatif Bulanan per bulan (7 = Juli s.d. 12 = Desember)
        $formatifSums = [];
        $formatifCounts = [];
        foreach ($formatifBulananRecords as $nfb) {
            $ksId = $nfb->kelas_siswa_id;
            $mId = $nfb->mapel_id;
            $b = (int) $nfb->bulan;
            $v = $nfb->nilai_formatif !== null ? (float) $nfb->nilai_formatif : null;
            if ($v !== null && isset($matrixFormatifBulan[$b])) {
                $matrixFormatifBulan[$b][$ksId][$mId] = $v;
                $formatifSums[$ksId][$mId] = ($formatifSums[$ksId][$mId] ?? 0.0) + $v;
                $formatifCounts[$ksId][$mId] = ($formatifCounts[$ksId][$mId] ?? 0) + 1;
            }
        }

        // Fallback rata formatif jika rata_formatif di rapor mapel belum dihitung
        foreach ($formatifSums as $ksId => $mMap) {
            foreach ($mMap as $mId => $sumVal) {
                if (! isset($matrixFormatif[$ksId][$mId]) && ($formatifCounts[$ksId][$mId] ?? 0) > 0) {
                    $matrixFormatif[$ksId][$mId] = round($sumVal / $formatifCounts[$ksId][$mId], 2);
                }
            }
        }

        // Bangun sub-dataset akademik untuk masing-masing Jenis Nilai
        $akademik = [
            'rapor' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixRapor, $kelasSiswaList),
            'uts' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixUts, $kelasSiswaList),
            'uas' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixUas, $kelasSiswaList),
            'formatif' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatif, $kelasSiswaList),
            'formatif_7' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[7], $kelasSiswaList),
            'formatif_8' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[8], $kelasSiswaList),
            'formatif_9' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[9], $kelasSiswaList),
            'formatif_10' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[10], $kelasSiswaList),
            'formatif_11' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[11], $kelasSiswaList),
            'formatif_12' => $this->buildAkademikSubDataset($mapels, $chartMapelKkm, $matrixFormatifBulan[12], $kelasSiswaList),
        ];

        // =========================================================================
        // 2. DATASET PERBANDINGAN NILAI (FORMATIF, UTS, UAS, RAPOR BERDAMPINGAN)
        // =========================================================================
        $perbandinganLabels = [];
        $perbandinganFullNames = [];
        $perbandinganKkm = [];
        $perbandinganFormatif = [];
        $perbandinganUts = [];
        $perbandinganUas = [];
        $perbandinganRapor = [];

        foreach ($mapels as $idx => $m) {
            $perbandinganLabels[] = $m->nama_singkat ?? $m->nama;
            $perbandinganFullNames[] = $m->nama;
            $perbandinganKkm[] = $chartMapelKkm[$m->id] ?? 70;
            $perbandinganFormatif[] = $akademik['formatif']['mapel']['averages'][$idx] ?? 0.0;
            $perbandinganUts[] = $akademik['uts']['mapel']['averages'][$idx] ?? 0.0;
            $perbandinganUas[] = $akademik['uas']['mapel']['averages'][$idx] ?? 0.0;
            $perbandinganRapor[] = $akademik['rapor']['mapel']['averages'][$idx] ?? 0.0;
        }

        $calcOverall = function ($arr) {
            $nonZero = array_filter($arr, fn ($v) => $v > 0);
            return count($nonZero) > 0 ? round(array_sum($nonZero) / count($nonZero), 2) : 0.0;
        };

        $perbandingan = [
            'labels' => $perbandinganLabels,
            'full_names' => $perbandinganFullNames,
            'kkm' => $perbandinganKkm,
            'datasets' => [
                'formatif' => $perbandinganFormatif,
                'uts' => $perbandinganUts,
                'uas' => $perbandinganUas,
                'rapor' => $perbandinganRapor,
            ],
            'formatif' => $perbandinganFormatif,
            'uts' => $perbandinganUts,
            'uas' => $perbandinganUas,
            'rapor' => $perbandinganRapor,
            'overall' => [
                'formatif' => $calcOverall($perbandinganFormatif),
                'uts' => $calcOverall($perbandinganUts),
                'uas' => $calcOverall($perbandinganUas),
                'rapor' => $calcOverall($perbandinganRapor),
            ],
            'has_data' => $calcOverall($perbandinganFormatif) > 0 || $calcOverall($perbandinganUts) > 0 || $calcOverall($perbandinganUas) > 0 || $calcOverall($perbandinganRapor) > 0,
        ];

        // =========================================================================
        // 3. KEHADIRAN (SIA) MANDIRI: DONAT, BAR SISWA (SORT ALPA DESC), TREN BULAN
        // =========================================================================
        $kehadiranRecords = count($kelasSiswaIds) > 0 ? Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', in_array($periode, ['sas', 'semester']) ? 'sas' : 'sts')
            ->get() : collect();

        if ($kehadiranRecords->isEmpty() && in_array($periode, ['sas', 'semester'])) {
            $kehadiranRecords = count($kelasSiswaIds) > 0 ? Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds)
                ->where('periode', 'sts')
                ->get() : collect();
        }

        $totalSakit = (int) $kehadiranRecords->sum('sakit');
        $totalIzin = (int) $kehadiranRecords->sum('izin');
        $totalAlpa = (int) $kehadiranRecords->sum('alpa');
        $totalTidakHadir = $totalSakit + $totalIzin + $totalAlpa;

        $siswaHadirPenuh = 0;
        $siswaPernahAbsen = 0;
        $kehadiranByKs = $kehadiranRecords->keyBy('kelas_siswa_id');

        $siswaBar = [];
        $hariEfektif = $sekolah ? $sekolah->getHariEfektif() : 108;

        foreach ($kelasSiswaList as $ks) {
            $kh = $kehadiranByKs->get($ks->id);
            $s = $kh ? (int) $kh->sakit : 0;
            $i = $kh ? (int) $kh->izin : 0;
            $a = $kh ? (int) $kh->alpa : 0;
            $tot = $s + $i + $a;
            if ($tot === 0) {
                $siswaHadirPenuh++;
            } else {
                $siswaPernahAbsen++;
            }
            $persen = $hariEfektif > 0 ? round((max(0, $hariEfektif - $tot) / $hariEfektif) * 100, 1) : 100.0;
            $siswaBar[] = [
                'id' => $ks->id,
                'nama' => $ks->siswa->nama ?? 'Siswa',
                'nis' => $ks->siswa->nis ?? ($ks->siswa->nisn ?? '-'),
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
                'total_absen' => $tot,
                'persen_kehadiran' => $persen,
                'is_peringatan_alpa' => $a >= $batasAlpa,
            ];
        }

        // Urutkan siswa dari Alpa terbanyak (descending), lalu total absen terbanyak
        usort($siswaBar, function ($a, $b) {
            if ($a['alpa'] !== $b['alpa']) {
                return $b['alpa'] <=> $a['alpa'];
            }
            if ($a['total_absen'] !== $b['total_absen']) {
                return $b['total_absen'] <=> $a['total_absen'];
            }
            return strcasecmp($a['nama'], $b['nama']);
        });

        // Tren Bulanan (Juli s.d. Desember)
        $kehadiranBulananAll = count($kelasSiswaIds) > 0 ? KehadiranBulanan::whereIn('kelas_siswa_id', $kelasSiswaIds)->get() : collect();
        $absensiHarianAll = count($kelasSiswaIds) > 0 ? AbsensiHarian::whereIn('kelas_siswa_id', $kelasSiswaIds)->whereNull('mapel_id')->get() : collect();

        $bulanNamaMap = [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $trenBulanLabels = [];
        $trenSakit = [];
        $trenIzin = [];
        $trenAlpa = [];
        $trenTotal = [];

        foreach ($bulanNamaMap as $bNum => $bName) {
            $trenBulanLabels[] = $bName;
            $recsB = $kehadiranBulananAll->where('bulan', $bNum);
            $sB = (int) $recsB->sum('sakit');
            $iB = (int) $recsB->sum('izin');
            $aB = (int) $recsB->sum('alpa');

            if (($sB + $iB + $aB) === 0) {
                $harianB = $absensiHarianAll->filter(fn ($rec) => (int) $rec->tanggal->month === $bNum);
                $sB = $harianB->where('status', 'S')->count();
                $iB = $harianB->where('status', 'I')->count();
                $aB = $harianB->where('status', 'A')->count();
            }

            $trenSakit[] = $sB;
            $trenIzin[] = $iB;
            $trenAlpa[] = $aB;
            $trenTotal[] = $sB + $iB + $aB;
        }

        $persenHadirPenuh = $jumlahSiswa > 0 ? round(($siswaHadirPenuh / $jumlahSiswa) * 100, 1) : 0;
        $rataAbsen = $jumlahSiswa > 0 ? round($totalTidakHadir / $jumlahSiswa, 1) : 0;

        $pctSakit = $totalTidakHadir > 0 ? round(($totalSakit / $totalTidakHadir) * 100, 1) : 0;
        $pctIzin = $totalTidakHadir > 0 ? round(($totalIzin / $totalTidakHadir) * 100, 1) : 0;
        $pctAlpa = $totalTidakHadir > 0 ? round(($totalAlpa / $totalTidakHadir) * 100, 1) : 0;

        $chartKehadiran = [
            'has_data' => $kehadiranRecords->count() > 0 || $totalTidakHadir > 0 || array_sum($trenTotal) > 0,
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
            'batas_alpa' => $batasAlpa,
            'siswa_bar' => $siswaBar,
            'tren_bulanan' => [
                'labels' => $trenBulanLabels,
                'sakit' => $trenSakit,
                'izin' => $trenIzin,
                'alpa' => $trenAlpa,
                'total' => $trenTotal,
            ],
        ];

        // =========================================================================
        // 4. PEMBIASAAN MANDIRI: PREDIKAT, BAR PER ASPEK, SISWA "PERLU BIMBINGAN"
        // =========================================================================
        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();
        $nilaiPembiasaanRecords = count($kelasSiswaIds) > 0 ? NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get() : collect();

        if ($nilaiPembiasaanRecords->isEmpty() && in_array($periode, ['sas', 'semester'])) {
            $nilaiPembiasaanRecords = count($kelasSiswaIds) > 0 ? NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds)
                ->where('periode', 'sts')
                ->get() : collect();
        }

        $npByMap = [];
        $siswaPerluBimbinganMap = [];
        $ksMapById = collect($kelasSiswaList)->keyBy('id');

        foreach ($nilaiPembiasaanRecords as $np) {
            $pId = $np->pembiasaan_id;
            $pred = trim((string) $np->predikat);
            $cleanPred = 'Baik';
            if ($pred === 'SB' || stripos($pred, 'sangat') !== false) {
                $cleanPred = 'Sangat Baik';
            } elseif ($pred === 'B' || stripos($pred, 'baik') !== false) {
                $cleanPred = 'Baik';
            } elseif ($pred === 'C' || stripos($pred, 'cukup') !== false) {
                $cleanPred = 'Cukup';
            } else {
                $cleanPred = 'Perlu Bimbingan';
            }

            if (! isset($npByMap[$pId])) {
                $npByMap[$pId] = ['Sangat Baik' => 0, 'Baik' => 0, 'Cukup' => 0, 'Perlu Bimbingan' => 0];
            }
            $npByMap[$pId][$cleanPred]++;

            if ($cleanPred === 'Perlu Bimbingan') {
                $ks = $ksMapById->get($np->kelas_siswa_id);
                $pObj = $pembiasaans->firstWhere('id', $pId);
                $pNama = $pObj ? $pObj->nama : 'Aspek Pembiasaan';
                if ($ks) {
                    if (! isset($siswaPerluBimbinganMap[$ks->id])) {
                        $siswaPerluBimbinganMap[$ks->id] = [
                            'id' => $ks->id,
                            'nama' => $ks->siswa->nama ?? 'Siswa',
                            'nis' => $ks->siswa->nis ?? ($ks->siswa->nisn ?? '-'),
                            'aspek' => [],
                            'saran' => 'Perlu bimbingan dan pendampingan khusus wali kelas serta komunikasi intensif bersama orang tua.',
                        ];
                    }
                    $siswaPerluBimbinganMap[$ks->id]['aspek'][] = $pNama;
                }
            }
        }

        $siswaPerluBimbinganList = array_values($siswaPerluBimbinganMap);

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
            'siswa_perlu_bimbingan' => $siswaPerluBimbinganList,
        ];

        // Ranking list for table compatibility
        $rankingItems = [];
        foreach ($rekapSiswaByRank as $item) {
            $h = $item['hasil'] ?? [];
            if ((int) ($h['count_sumatif'] ?? 0) > 0) {
                $rata = (float) ($h['rata_sumatif'] ?? 0);
                $rankingItems[] = [
                    'peringkat' => $item['peringkat'] ?? (count($rankingItems) + 1),
                    'nama' => $item['siswa']->nama ?? 'Siswa',
                    'nis' => $item['siswa']->nis ?? '-',
                    'rata_rata' => $rata,
                    'rata_rata_format' => self::formatAngka($rata),
                    'total' => (float) ($h['total_sumatif'] ?? 0),
                    'total_format' => self::formatAngka($h['total_sumatif'] ?? 0),
                ];
            }
        }

        // Active default akademik dataset
        $defaultAkademik = $akademik['rapor']['has_data'] ? $akademik['rapor'] : ($akademik['formatif']['has_data'] ? $akademik['formatif'] : ($akademik['uts']['has_data'] ? $akademik['uts'] : $akademik['rapor']));

        return [
            'has_data' => $defaultAkademik['has_data'] || $perbandingan['has_data'] || $chartKehadiran['has_data'] || $chartPembiasaan['has_data'],
            'akademik' => $akademik,
            'perbandingan' => $perbandingan,
            'mapel' => $defaultAkademik['mapel'],
            'distribusi' => $defaultAkademik['distribusi'],
            'ranking' => $rankingItems,
            'rentang_nilai' => $defaultAkademik['rentang_nilai'],
            'kehadiran' => $chartKehadiran,
            'pembiasaan' => $chartPembiasaan,
            'summary' => $defaultAkademik['summary'],
        ];
    }

    /**
     * Helper untuk membangun paket data grafik akademik (Mapel, Distribusi, Rentang Nilai, Ringkasan)
     * berdasarkan matriks skor [$ksId][$mapelId] => float
     */
    public function buildAkademikSubDataset(
        Collection|array $mapels,
        array $chartMapelKkm,
        array $scoreMatrix,
        Collection|array $kelasSiswaList
    ): array {
        $chartMapelLabels = [];
        $chartMapelFullNames = [];
        $chartMapelAvg = [];
        $chartMapelKkmList = [];
        $chartMapelStatus = [];

        $mapelTertinggi = null;
        $mapelTerendah = null;
        $maxMapelAvg = -1.0;
        $minMapelAvg = 9999.0;
        $hasAnyScore = false;

        $perMapelSiswa = [];
        foreach ($mapels as $m) {
            $chartMapelLabels[] = $m->nama_singkat ?? $m->nama;
            $chartMapelFullNames[] = $m->nama;
            $kkm = $chartMapelKkm[$m->id] ?? 70;
            $chartMapelKkmList[] = $kkm;

            $sum = 0.0;
            $cnt = 0;
            foreach ($kelasSiswaList as $ks) {
                if (isset($scoreMatrix[$ks->id][$m->id]) && $scoreMatrix[$ks->id][$m->id] !== null) {
                    $sum += (float) $scoreMatrix[$ks->id][$m->id];
                    $cnt++;
                    $hasAnyScore = true;
                }
            }

            $avg = $cnt > 0 ? round($sum / $cnt, 2) : 0.0;
            $chartMapelAvg[] = $avg;
            $chartMapelStatus[] = $avg >= $kkm;

            if ($cnt > 0) {
                if ($avg > $maxMapelAvg) {
                    $maxMapelAvg = $avg;
                    $mapelTertinggi = [
                        'nama' => $m->nama,
                        'nama_singkat' => $m->nama_singkat ?? $m->nama,
                        'rata_rata' => $avg,
                        'rata_rata_format' => self::formatAngka($avg),
                    ];
                }
                if ($avg < $minMapelAvg) {
                    $minMapelAvg = $avg;
                    $mapelTerendah = [
                        'nama' => $m->nama,
                        'nama_singkat' => $m->nama_singkat ?? $m->nama,
                        'rata_rata' => $avg,
                        'rata_rata_format' => self::formatAngka($avg),
                    ];
                }
            }

            $perMapelSiswa[$m->id] = ['r1' => [], 'r2' => [], 'r3' => [], 'r4' => []];
        }

        $countSangatBaik = 0;
        $countBaik = 0;
        $countCukup = 0;
        $countPerluBimbingan = 0;
        $countKosong = 0;
        $countTuntasKkm = 0;

        $highestScore = null;
        $highestSiswa = null;
        $lowestScore = null;
        $lowestSiswa = null;

        $siswaRentang1 = [];
        $siswaRentang2 = [];
        $siswaRentang3 = [];
        $siswaRentang4 = [];

        $kkmList = array_values($chartMapelKkmList);
        $rataKkmKelas = count($kkmList) > 0 ? round(array_sum($kkmList) / count($kkmList), 1) : 70.0;
        $totalDinilai = 0;

        foreach ($kelasSiswaList as $ks) {
            $sNama = $ks->siswa->nama ?? 'Siswa';
            $sNis = $ks->siswa->nis ?? '-';

            $userScores = $scoreMatrix[$ks->id] ?? [];
            $validScores = array_filter($userScores, fn ($v) => $v !== null);

            if (count($validScores) > 0) {
                $totalDinilai++;
                $rata = round(array_sum($validScores) / count($validScores), 2);

                if ($rata >= 85) {
                    $countSangatBaik++;
                } elseif ($rata >= 75) {
                    $countBaik++;
                } elseif ($rata >= 65) {
                    $countCukup++;
                } else {
                    $countPerluBimbingan++;
                }

                $sInfo = [
                    'nama' => $sNama,
                    'nis' => $sNis,
                    'rata_rata' => $rata,
                    'rata_rata_format' => self::formatAngka($rata),
                ];

                if ($rata <= 25.0) {
                    $siswaRentang1[] = $sInfo;
                } elseif ($rata <= 50.0) {
                    $siswaRentang2[] = $sInfo;
                } elseif ($rata <= 75.0) {
                    $siswaRentang3[] = $sInfo;
                } else {
                    $siswaRentang4[] = $sInfo;
                }

                if ($rata >= $rataKkmKelas) {
                    $countTuntasKkm++;
                }

                if ($highestScore === null || $rata > $highestScore) {
                    $highestScore = $rata;
                    $highestSiswa = $sNama;
                }
                if ($lowestScore === null || $rata < $lowestScore) {
                    $lowestScore = $rata;
                    $lowestSiswa = $sNama;
                }

                foreach ($validScores as $mId => $sc) {
                    if (isset($perMapelSiswa[$mId])) {
                        $mInfo = [
                            'nama' => $sNama,
                            'nis' => $sNis,
                            'nilai' => $sc,
                            'nilai_format' => self::formatAngka($sc),
                        ];
                        if ($sc <= 25.0) {
                            $perMapelSiswa[$mId]['r1'][] = $mInfo;
                        } elseif ($sc <= 50.0) {
                            $perMapelSiswa[$mId]['r2'][] = $mInfo;
                        } elseif ($sc <= 75.0) {
                            $perMapelSiswa[$mId]['r3'][] = $mInfo;
                        } else {
                            $perMapelSiswa[$mId]['r4'][] = $mInfo;
                        }
                    }
                }
            } else {
                $countKosong++;
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
                'total_siswa' => count($kelasSiswaList),
                'siswa' => [$r1, $r2, $r3, $r4],
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

        $countR1 = count($siswaRentang1);
        $countR2 = count($siswaRentang2);
        $countR3 = count($siswaRentang3);
        $countR4 = count($siswaRentang4);
        $pctR1 = $totalDinilai > 0 ? round(($countR1 / $totalDinilai) * 100, 1) : 0;
        $pctR2 = $totalDinilai > 0 ? round(($countR2 / $totalDinilai) * 100, 1) : 0;
        $pctR3 = $totalDinilai > 0 ? round(($countR3 / $totalDinilai) * 100, 1) : 0;
        $pctR4 = $totalDinilai > 0 ? round(($countR4 / $totalDinilai) * 100, 1) : 0;

        $pctSangatBaik = $totalDinilai > 0 ? round(($countSangatBaik / $totalDinilai) * 100, 1) : 0;
        $pctBaik = $totalDinilai > 0 ? round(($countBaik / $totalDinilai) * 100, 1) : 0;
        $pctCukup = $totalDinilai > 0 ? round(($countCukup / $totalDinilai) * 100, 1) : 0;
        $pctPerluBimbingan = $totalDinilai > 0 ? round(($countPerluBimbingan / $totalDinilai) * 100, 1) : 0;
        $persenTuntasKkm = $totalDinilai > 0 ? round(($countTuntasKkm / $totalDinilai) * 100, 1) : 0;

        return [
            'has_data' => $hasAnyScore && $totalDinilai > 0,
            'mapel' => [
                'labels' => $chartMapelLabels,
                'full_names' => $chartMapelFullNames,
                'averages' => $chartMapelAvg,
                'kkm' => $chartMapelKkmList,
                'status' => $chartMapelStatus,
            ],
            'distribusi' => [
                'labels' => ['Sangat Baik (≥ 85)', 'Baik (75 - 84)', 'Cukup (65 - 74)', 'Perlu Bimbingan (< 65)'],
                'counts' => [$countSangatBaik, $countBaik, $countCukup, $countPerluBimbingan],
                'percentages' => [$pctSangatBaik, $pctBaik, $pctCukup, $pctPerluBimbingan],
                'colors' => ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                'count_kosong' => $countKosong,
                'total_dinilai' => $totalDinilai,
                'total_siswa' => count($kelasSiswaList),
            ],
            'rentang_nilai' => [
                'labels' => ['0 - 25', '26 - 50', '51 - 75', '76 - 100'],
                'full_labels' => ['Rentang 0 - 25', 'Rentang 26 - 50', 'Rentang 51 - 75', 'Rentang 76 - 100'],
                'keterangan' => ['Perlu Intervensi Khusus', 'Perlu Bimbingan', 'Cukup / Memadai', 'Baik & Sangat Baik'],
                'counts' => [$countR1, $countR2, $countR3, $countR4],
                'percentages' => [$pctR1, $pctR2, $pctR3, $pctR4],
                'colors' => ['#ef4444', '#f97316', '#3b82f6', '#10b981'],
                'border_colors' => ['#dc2626', '#ea580c', '#2563eb', '#059669'],
                'siswa' => [$siswaRentang1, $siswaRentang2, $siswaRentang3, $siswaRentang4],
                'total_dinilai' => $totalDinilai,
                'total_siswa' => count($kelasSiswaList),
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
            ],
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

    /**
     * Generator Deskripsi Capaian Pembelajaran (CP) Resmi Kurikulum Merdeka Jenjang SD
     * Berdasarkan Muatan Pelajaran, Fase (A, B, C), dan Nilai Akhir Peserta Didik.
     * Sesuai Panduan Pembelajaran dan Asesmen (PPA) Kurikulum Merdeka Kemendikbudristek.
     */
    public static function generateDeskripsiCpKurikulumMerdeka(MataPelajaran|string $mapel, ?float $nilai, string $fase = 'A'): string
    {
        $namaMapel = trim($mapel instanceof MataPelajaran ? $mapel->nama : (string) $mapel);

        // Standarisasi kode Fase (A: Kelas 1-2, B: Kelas 3-4, C: Kelas 5-6)
        $faseUpper = strtoupper($fase);
        if (str_contains($faseUpper, 'C')) {
            $faseKey = 'C';
        } elseif (str_contains($faseUpper, 'B')) {
            $faseKey = 'B';
        } else {
            $faseKey = 'A';
        }

        // Bank Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP) Kurikulum Merdeka SD Resmi
        $cpBank = [
            'agama' => [
                'A' => [
                    'tinggi' => 'mengenal rukun iman, melafalkan doa-doa harian, serta membiasakan perilaku jujur dan kasih sayang',
                    'bimbingan' => 'mempraktikkan tata cara bersuci dan gerakan salat fardhu dengan tertib',
                ],
                'B' => [
                    'tinggi' => 'memahami makna Asmaul Husna, meneladani kisah terpuji para nabi, serta berakhlak mulia kepada orang tua dan sesama',
                    'bimbingan' => 'menyempurnakan bacaan dan hafalan surah-surah pendek Al-Qur\'an dengan kaidah tajwid yang benar',
                ],
                'C' => [
                    'tinggi' => 'memahami ketentuan zakat dan puasa, meneladani sifat rasul, serta menerapkan sikap toleransi beragama',
                    'bimbingan' => 'menganalisis makna ayat Al-Qur\'an pilihan serta penerapannya dalam kehidupan sehari-hari',
                ],
            ],
            'pancasila' => [
                'A' => [
                    'tinggi' => 'mengenali simbol-simbol sila Pancasila, menaati aturan di rumah dan sekolah, serta menghargai keragaman teman',
                    'bimbingan' => 'membedakan antara hak dan kewajiban anak di lingkungan sekolah',
                ],
                'B' => [
                    'tinggi' => 'mengamalkan nilai-nilai Pancasila dalam keseharian, memahami norma kehidupan bermasyarakat, serta aktif bergotong royong',
                    'bimbingan' => 'mengidentifikasi keragaman budaya dan kearifan lokal di daerah setempat',
                ],
                'C' => [
                    'tinggi' => 'memahami sejarah perumusan Pancasila, menjaga keutuhan NKRI, serta menerapkan musyawarah untuk mufakat',
                    'bimbingan' => 'menelaah keterkaitan norma hukum dan tanggung jawab sebagai warga negara',
                ],
            ],
            'indonesia' => [
                'A' => [
                    'tinggi' => 'menyimak teks cerita lisan, membaca kata dan suku kata dengan lancar, serta menceritakan kembali isi bacaan dengan santun',
                    'bimbingan' => 'merangkai kata menjadi kalimat sederhana dengan ejaan dan huruf kapital yang tepat',
                ],
                'B' => [
                    'tinggi' => 'menemukan ide pokok teks narasi dan deskripsi, menyampaikan pendapat secara runtut, dan menyusun paragraf terstruktur',
                    'bimbingan' => 'memperkaya kosa kata baru serta menggunakan tanda baca yang sesuai kaidah penulisan',
                ],
                'C' => [
                    'tinggi' => 'menganalisis ide pokok dan informasi tersirat teks eksplanasi, mengapresiasi karya sastra, serta berpidato dengan percaya diri',
                    'bimbingan' => 'menyusun laporan hasil pengamatan dengan kaidah penulisan bahasa Indonesia baku',
                ],
            ],
            'matematika' => [
                'A' => [
                    'tinggi' => 'membilang dan mengurutkan bilangan cacah sampai 100 serta menyelesaikan operasi penjumlahan dan pengurangan dasar',
                    'bimbingan' => 'mengidentifikasi ciri-ciri bangun datar dan menyelesaikan soal cerita kontekstual sederhana',
                ],
                'B' => [
                    'tinggi' => 'operasi perkalian dan pembagian bilangan ratusan/ribuan, pecahan senilai, serta menghitung keliling dan luas bangun datar',
                    'bimbingan' => 'memecahkan soal cerita berbasis pecahan dan konversi satuan waktu/panjang',
                ],
                'C' => [
                    'tinggi' => 'operasi hitung campuran bilangan bulat, pecahan, dan desimal, menghitung volume bangun ruang, serta membaca diagram data',
                    'bimbingan' => 'menyelesaikan masalah perbandingan rasio dan penafsiran data statistik sederhana',
                ],
            ],
            'ipas' => [
                'A' => [
                    'tinggi' => 'mengenal bagian tubuh dan panca indra, membedakan makhluk hidup dan benda mati, serta mengamati lingkungan sekitar',
                    'bimbingan' => 'menjaga kebersihan diri dan lingkungan sekitar tempat tinggal',
                ],
                'B' => [
                    'tinggi' => 'memahami fungsi bagian tumbuhan, wujud zat serta perubahannya, dan mengamati siklus hidup makhluk hidup',
                    'bimbingan' => 'menjelaskan interaksi sosial ekonomi dan pemanfaatan bentang alam di daerah setempat',
                ],
                'C' => [
                    'tinggi' => 'menganalisis sistem organ pernapasan dan pencernaan manusia, jaring-jaring makanan ekosistem, serta ragam energi terbarukan',
                    'bimbingan' => 'menelaah peristiwa sejarah perjuangan bangsa dan upaya pelestarian lingkungan hidup',
                ],
            ],
            'pjok' => [
                'A' => [
                    'tinggi' => 'mempraktikkan variasi gerak dasar lokomotor, non-lokomotor, dan manipulatif terkoordinasi, serta membiasakan pola hidup bersih',
                    'bimbingan' => 'menjaga keseimbangan tubuh dan kelincahan gerak dasar secara konsisten',
                ],
                'B' => [
                    'tinggi' => 'menguasai kombinasi gerak dasar permainan bola besar/kecil, senam lantai dasar, serta memahami makanan bergizi seimbang',
                    'bimbingan' => 'memelihara daya tahan tubuh melalui aktivitas kebugaran jasmani secara rutin',
                ],
                'C' => [
                    'tinggi' => 'menerapkan strategi dan kerja sama dalam gerak berirama dan olahraga beregu, serta melakukan latihan kebugaran jasmani terukur',
                    'bimbingan' => 'memahami pentingnya pemeliharaan kebersihan alat reproduksi dan pola istirahat teratur',
                ],
            ],
            'seni' => [
                'A' => [
                    'tinggi' => 'mengeksplorasi garis, bentuk, dan warna primer pada karya gambar ekspresif serta percaya diri menampilkan kreasi seninya',
                    'bimbingan' => 'meningkatkan kerapian mewarnai serta keterampilan memotong dan merekatkan media karya',
                ],
                'B' => [
                    'tinggi' => 'menerapkan tekstur, pola ritmis, dan pencampuran warna dalam karya dekoratif yang mengangkat kearifan lokal',
                    'bimbingan' => 'menata proporsi dan komposisi gambar imajinatif dengan cermat',
                ],
                'C' => [
                    'tinggi' => 'menciptakan karya seni rupa dua atau tiga dimensi dari bahan ramah lingkungan serta mengapresiasi keragaman seni Nusantara',
                    'bimbingan' => 'menguasai teknik pencahayaan gelap-terang (arsir) dan perspektif gambar',
                ],
            ],
            'inggris' => [
                'A' => [
                    'tinggi' => 'merespons instruksi lisan sederhana, mengucapkan salam (greetings), serta mengenali kosakata warna dan angka',
                    'bimbingan' => 'melafalkan kosa kata benda di kelas dengan intonasi dan kepercayaan diri yang baik',
                ],
                'B' => [
                    'tinggi' => 'menggunakan kosa kata aktivitas harian, mengenali ungkapan kesukaan, serta mengajukan pertanyaan sederhana secara lisan',
                    'bimbingan' => 'mengeja dan menuliskan kosa kata bahasa Inggris secara tepat dalam kalimat pendek',
                ],
                'C' => [
                    'tinggi' => 'memahami teks bacaan deskriptif singkat, menceritakan rutinitas harian, dan menggunakan kata sifat dengan baik',
                    'bimbingan' => 'menerapkan tata bahasa sederhana (simple present tense) dalam penulisan kalimat bahasa Inggris',
                ],
            ],
            'using' => [
                'A' => [
                    'tinggi' => 'menyimak dongeng fabel Using, melantunkan gending Using sederhana, serta mengenal kosa kata bahasa Using sehari-hari',
                    'bimbingan' => 'membiasakan berbicara bahasa Using ragam alus dalam komunikasi santun di sekolah',
                ],
                'B' => [
                    'tinggi' => 'memahami legenda cerita rakyat Banyuwangi, nembang Using, serta mempraktikkan percakapan Using dengan percaya diri',
                    'bimbingan' => 'mengeja kata dan memahami perbedaan makna kosa kata dialek Using setempat',
                ],
                'C' => [
                    'tinggi' => 'mengapresiasi sastra daerah Using, menulis percakapan bertema tradisi lokal Banyuwangi, serta melestarikan kearifan budaya Blambangan',
                    'bimbingan' => 'menuliskan karya deskripsi tradisi adat Banyuwangi dengan kosa kata Using baku',
                ],
            ],
            'jawa' => [
                'A' => [
                    'tinggi' => 'menyimak dongeng fabel Jawa, melagukan tembang dolanan anak, serta menerapkan unggah-ungguh basa Jawa sederhana',
                    'bimbingan' => 'membiasakan melafalkan kosa kata krama lugu saat berinteraksi dengan sesama',
                ],
                'B' => [
                    'tinggi' => 'memahami cerita tokoh pewayangan/cerita rakyat Jawa, melagukan tembang macapat, serta menerapkan basa krama alus sehari-hari',
                    'bimbingan' => 'membaca dan menulis aksara Jawa dasar (legena) dengan teliti',
                ],
                'C' => [
                    'tinggi' => 'menelaah wacana narasi Jawa, menulis geguritan/pidato sederhana (sesorah), serta menerapkan tata krama berbahasa Jawa santun',
                    'bimbingan' => 'menulis kata beraksara Jawa lengkap dengan sandhangan swara dan panyigeg wanda',
                ],
            ],
            'koding' => [
                'A' => [
                    'tinggi' => 'mengenal konsep logika urutan instruksi dasar dan pemanfaatan perangkat digital untuk pembelajaran interaktif',
                    'bimbingan' => 'mengikuti instruksi pemrograman berbasis blok secara mandiri',
                ],
                'B' => [
                    'tinggi' => 'memahami dasar berpikir komputasional (computational thinking), algoritma langkah terstruktur, serta eksplorasi coding visual',
                    'bimbingan' => 'mendeteksi dan memperbaiki kesalahan urutan logika (debugging) pada proyek sederhana',
                ],
                'C' => [
                    'tinggi' => 'menerapkan computational thinking dalam pemecahan masalah nyata, pengenalan etika kecerdasan buatan, serta proyek digital kreatif',
                    'bimbingan' => 'merancang logika percabangan dan perulangan bertingkat dalam proyek pemrograman visual',
                ],
            ],
        ];

        // Tentukan kategori muatan pelajaran
        $key = 'umum';
        if (preg_match('/agama|pabp|islam|kristen|katolik|hindu|buddha/i', $namaMapel)) {
            $key = 'agama';
        } elseif (preg_match('/pancasila|pkn|kewarganegaraan/i', $namaMapel)) {
            $key = 'pancasila';
        } elseif (preg_match('/bahasa indonesia|b\. indonesia|b\.indo/i', $namaMapel)) {
            $key = 'indonesia';
        } elseif (preg_match('/matematika|mtk/i', $namaMapel)) {
            $key = 'matematika';
        } elseif (preg_match('/ipas|alam dan sosial/i', $namaMapel)) {
            $key = 'ipas';
        } elseif (preg_match('/pjok|jasmani|olahraga/i', $namaMapel)) {
            $key = 'pjok';
        } elseif (preg_match('/seni|budaya|rupa|musik|tari/i', $namaMapel)) {
            $key = 'seni';
        } elseif (preg_match('/using|osing/i', $namaMapel)) {
            $key = 'using';
        } elseif (preg_match('/jawa|daerah/i', $namaMapel)) {
            $key = 'jawa';
        } elseif (preg_match('/inggris|english/i', $namaMapel)) {
            $key = 'inggris';
        } elseif (preg_match('/koding|coding|kecerdasan|artifisial|ai/i', $namaMapel)) {
            $key = 'koding';
        }

        if (isset($cpBank[$key][$faseKey])) {
            $tpTinggi = $cpBank[$key][$faseKey]['tinggi'];
            $tpBimbingan = $cpBank[$key][$faseKey]['bimbingan'];
        } else {
            $tpTinggi = "memahami lingkup materi pokok dan target capaian pembelajaran {$namaMapel}";
            $tpBimbingan = "meningkatkan ketelitian latihan mandiri serta pemahaman materi lanjutan {$namaMapel}";
        }

        if ($nilai === null) {
            return "Belum ada penilaian capaian pembelajaran untuk mata pelajaran {$namaMapel}.";
        }

        // Susun format deskripsi resmi Kurikulum Merdeka (Tercapai Optimal & Perlu Peningkatan)
        if ($nilai >= 85) {
            return "Menunjukkan penguasaan yang sangat baik dalam {$tpTinggi}.";
        } elseif ($nilai >= 75) {
            return "Menunjukkan penguasaan yang baik dalam {$tpTinggi}. Perlu peningkatan dalam {$tpBimbingan}.";
        } elseif ($nilai >= 60) {
            return "Cukup menguasai materi dalam {$tpTinggi}. Perlu bimbingan dan latihan mandiri dalam {$tpBimbingan}.";
        } else {
            return "Perlu bimbingan dan pendampingan intensif dari guru serta orang tua dalam {$tpBimbingan}.";
        }
    }

    /**
     * Generate narasi catatan otomatis wali kelas yang memadukan capaian akademik dan kedisiplinan kehadiran (SIA).
     * Nilai akademik tidak dipotong karena absensi; absensi diekspresikan secara konstruktif pada catatan ini.
     */
    public static function generateCatatanWaliKelas(?float $rataNilai, float $persenHadir, int $alpa, int $sakit, int $izin, string $namaSiswa): string
    {
        // 1. Narasi Akademik
        if ($rataNilai === null || $rataNilai <= 0) {
            $kalimatAkademik = "Ananda {$namaSiswa} diharapkan terus bersemangat dan aktif dalam proses pembelajaran.";
        } elseif ($rataNilai >= 85) {
            $kalimatAkademik = "Ananda {$namaSiswa} menunjukkan prestasi belajar yang sangat membanggakan dan konsisten menguasai materi pembelajaran.";
        } elseif ($rataNilai >= 75) {
            $kalimatAkademik = "Ananda {$namaSiswa} menunjukkan pemahaman belajar yang baik serta memiliki keaktifan positif selama kegiatan belajar.";
        } elseif ($rataNilai >= 60) {
            $kalimatAkademik = "Ananda {$namaSiswa} cukup menguasai kompetensi materi pokok. Tingkatkan ketekunan belajar dan keaktifan di kelas.";
        } else {
            $kalimatAkademik = "Ananda {$namaSiswa} perlu meningkatkan fokus belajar dan memperbanyak latihan mandiri dengan pendampingan guru dan orang tua.";
        }

        // 2. Narasi Kehadiran & Kedisiplinan (SIA)
        $kalimatKehadiran = '';
        if ($alpa > 3) {
            $kalimatKehadiran = "Perlu perhatian khusus dari orang tua terkait {$alpa} hari ketidakhadiran tanpa keterangan (alpa) agar kedisiplinan dan syarat kehadiran tetap terpenuhi.";
        } elseif ($alpa > 0) {
            $kalimatKehadiran = "Kurangi ketidakhadiran tanpa keterangan ({$alpa} hari alpa) demi menjaga kesinambungan belajar dan kedisiplinan diri.";
        } elseif ($persenHadir < 75) {
            if ($sakit > 5) {
                $kalimatKehadiran = "Kehadiran berkurang karena sakit ({$sakit} hari). Jaga selalu kesehatan agar dapat mengikuti pembelajaran secara optimal.";
            } elseif ($izin > 7) {
                $kalimatKehadiran = "Jumlah izin tercatat {$izin} hari. Disarankan untuk memprioritaskan waktu masuk sekolah agar materi tidak tertinggal.";
            } else {
                $kalimatKehadiran = "Tingkatkan kehadiran aktif di kelas ({$persenHadir}%) untuk memaksimalkan capaian pembelajaran.";
            }
        } elseif ($persenHadir >= 95 && $alpa === 0) {
            $kalimatKehadiran = "Tingkat kehadiran dan kedisiplinan sangat baik, pertahankan sikap teladan ini.";
        } else {
            $kalimatKehadiran = "Pertahankan motivasi belajar dan kedisiplinan yang telah berjalan dengan baik.";
        }

        return "{$kalimatAkademik} {$kalimatKehadiran}";
    }

    /**
     * Generate narasi pembiasaan/karakter Kurikulum Merdeka yang relevan dengan predikat dan kedisiplinan kehadiran.
     */
    public static function generateDeskripsiPembiasaan(string $namaPembiasaan, string $predikat, int $alpa = 0): string
    {
        $namaLower = strtolower($namaPembiasaan);
        $isKemandirianAtauDisiplin = str_contains($namaLower, 'mandiri')
            || str_contains($namaLower, 'disiplin')
            || str_contains($namaLower, 'tertib')
            || str_contains($namaLower, 'tanggung jawab');

        switch ($predikat) {
            case 'Sangat Baik':
                if ($isKemandirianAtauDisiplin) {
                    return "Menunjukkan kemandirian dan kedisiplinan yang sangat tinggi serta konsisten hadir tepat waktu dan mematuhi tata tertib sekolah.";
                }
                return "Sangat konsisten dan mampu menjadi teladan bagi teman-temannya dalam pelaksanaan {$namaPembiasaan}.";

            case 'Baik':
                if ($isKemandirianAtauDisiplin && $alpa > 0) {
                    return "Menunjukkan sikap tertib dan mandiri yang baik, namun perlu lebih disiplin dalam kehadiran tanpa keterangan.";
                }
                return "Menunjukkan sikap dan kebiasaan yang baik serta terbiasa menjalankan {$namaPembiasaan} dengan kesadaran sendiri.";

            case 'Cukup':
                if ($isKemandirianAtauDisiplin && $alpa > 0) {
                    return "Cukup tertib dalam kegiatan harian, namun kedisiplinan kehadiran ({$alpa} hari alpa) masih perlu ditingkatkan.";
                }
                return "Cukup mampu melaksanakan {$namaPembiasaan}, namun masih memerlukan pengingat sesekali dari guru dan orang tua.";

            case 'Perlu Bimbingan':
                return "Memerlukan bimbingan intensif dan pembiasaan berkelanjutan dari guru dan orang tua dalam mengamalkan {$namaPembiasaan}.";

            default:
                return "Pelaksanaan {$namaPembiasaan} menunjukkan perkembangan sesuai tahap usianya.";
        }
    }
}
