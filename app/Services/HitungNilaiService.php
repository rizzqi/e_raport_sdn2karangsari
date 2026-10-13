<?php

namespace App\Services;

use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;

class HitungNilaiService
{
    /**
     * Hitung nilai per siswa untuk suatu periode ('sts' atau 'sas')
     */
    public function hitungNilaiSiswa(KelasSiswa $kelasSiswa, string $periode = 'sts'): array
    {
        $mapels = MataPelajaran::aktif()->get();
        $nilaiList = Nilai::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('mapel_id');

        $baris = [];
        $totalFormatif = 0;
        $countFormatif = 0;
        $totalSumatif = 0;
        $countSumatif = 0;
        $totalAkhir = 0;
        $countAkhir = 0;

        foreach ($mapels as $index => $mapel) {
            $n = $nilaiList->get($mapel->id);
            $formatif = $n && $n->formatif !== null ? (float) $n->formatif : null;
            $sumatif = $n && $n->sumatif !== null ? (float) $n->sumatif : null;

            // Rata-rata mapel jika keduanya ada, atau salah satu
            $nilaiMapelAkhir = null;
            if ($formatif !== null && $sumatif !== null) {
                $nilaiMapelAkhir = round(($formatif + $sumatif) / 2, 2);
            } elseif ($sumatif !== null) {
                $nilaiMapelAkhir = $sumatif;
            } elseif ($formatif !== null) {
                $nilaiMapelAkhir = $formatif;
            }

            if ($formatif !== null) {
                $totalFormatif += $formatif;
                $countFormatif++;
            }
            if ($sumatif !== null) {
                $totalSumatif += $sumatif;
                $countSumatif++;
            }
            if ($nilaiMapelAkhir !== null) {
                $totalAkhir += $nilaiMapelAkhir;
                $countAkhir++;
            }

            $baris[] = [
                'no' => $index + 1,
                'mapel_id' => $mapel->id,
                'mapel_nama' => $mapel->nama,
                'formatif' => $formatif,
                'sumatif' => $sumatif,
                'akhir' => $nilaiMapelAkhir,
                'formatif_format' => $formatif !== null ? self::formatAngka($formatif) : '-',
                'sumatif_format' => $sumatif !== null ? self::formatAngka($sumatif) : '-',
                'akhir_format' => $nilaiMapelAkhir !== null ? self::formatAngka($nilaiMapelAkhir) : '-',
            ];
        }

        $rataFormatif = $countFormatif > 0 ? round($totalFormatif / $countFormatif, 2) : 0;
        $rataSumatif = $countSumatif > 0 ? round($totalSumatif / $countSumatif, 2) : 0;
        $rataAkhir = $countAkhir > 0 ? round($totalAkhir / $countAkhir, 2) : 0;

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
            'total_mapel' => $mapels->count(),
            'terisi_semua' => $countSumatif === $mapels->count(),
        ];
    }

    /**
     * Hitung rekap nilai seluruh siswa dalam satu kelas
     */
    public function hitungRekapKelas(array|\Illuminate\Support\Collection $kelasSiswaList, string $periode = 'sts'): array
    {
        $mapels = MataPelajaran::aktif()->get();
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

        $rekapMapel = [];
        foreach ($mapels as $m) {
            $count = $mapelCount[$m->id];
            $avg = $count > 0 ? round($mapelTotal[$m->id] / $count, 2) : 0;
            $rekapMapel[$m->id] = [
                'mapel' => $m,
                'rata_rata' => $avg,
                'rata_rata_format' => self::formatAngka($avg),
                'terisi' => $count,
                'total_siswa' => $jumlahSiswa,
            ];
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
