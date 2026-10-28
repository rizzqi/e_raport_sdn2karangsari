<?php

namespace App\Http\Controllers;

use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\Sekolah;
use App\Services\ExcelReader;
use App\Services\ExcelWriter;
use App\Services\HitungNilaiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NilaiFormatifBulananController extends Controller
{
    /**
     * Tampilan Utama: Daftar Rapor Formatif Bulanan Peserta Didik di Kelas.
     * Mirip dengan halaman utama kelas / modul raport UTS.
     */
    public function index(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $mapels = $kelas->mataPelajaran();

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        // Ambil nilai formatif bulan ini untuk semua siswa di kelas ini
        $allNilai = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        // Hitung kelengkapan nilai dan rata-rata per siswa
        $statusSiswa = [];
        $totalSelesai = 0;
        $totalMapel = $mapels->count();

        foreach ($kelasSiswaList as $ks) {
            $nilaiSiswa = $allNilai->where('kelas_siswa_id', $ks->id)->filter(fn ($n) => $n->nilai !== null);
            $terisiCount = $nilaiSiswa->count();
            $avg = $terisiCount > 0 ? round($nilaiSiswa->avg('nilai'), 1) : null;
            $sum = $terisiCount > 0 ? round($nilaiSiswa->sum('nilai'), 1) : null;
            $isComplete = $totalMapel > 0 && $terisiCount >= $totalMapel;

            if ($isComplete) {
                $totalSelesai++;
            }

            $statusSiswa[$ks->id] = [
                'terisi' => $terisiCount,
                'total_mapel' => $totalMapel,
                'is_complete' => $isComplete,
                'rata_rata' => $avg,
                'total' => $sum,
            ];
        }

        $persenSelesai = $kelasSiswaList->count() > 0 ? round(($totalSelesai / $kelasSiswaList->count()) * 100) : 0;

        return view('kelas.nilai_formatif.index', compact(
            'kelas',
            'kelasSiswaList',
            'mapels',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulan',
            'statusSiswa',
            'totalSelesai',
            'persenSelesai'
        ));
    }

    /**
     * Formulir Input Nilai Formatif Per Siswa (Persis alur input raport UTS).
     */
    public function inputSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $kelasSiswa->load('siswa');
        $mapels = $kelas->mataPelajaran();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        // Ambil nilai formatif yang sudah tersimpan untuk siswa ini
        $nilaiList = NilaiFormatifBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('mapel_id');

        // Ambil kehadiran bulanan jika ada
        $kehadiran = KehadiranBulanan::firstOrNew([
            'kelas_siswa_id' => $kelasSiswa->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], [
            'sakit' => 0,
            'izin' => 0,
            'alpa' => 0,
        ]);

        // Navigasi prev & next siswa
        $allSiswaIds = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
        $prevId = $currentIndex > 0 ? $allSiswaIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allSiswaIds) - 1 ? $allSiswaIds[$currentIndex + 1] : null;

        return view('kelas.nilai_formatif.input_siswa', compact(
            'kelas',
            'kelasSiswa',
            'mapels',
            'nilaiList',
            'kehadiran',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulan',
            'prevId',
            'nextId'
        ));
    }

    /**
     * Simpan Nilai Formatif & Kehadiran Per Siswa.
     */
    public function simpanSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request): RedirectResponse
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        $mapels = $kelas->mataPelajaran();

        $nilaiData = $request->input('nilai', []);

        foreach ($mapels as $m) {
            $rawVal = $nilaiData[$m->id] ?? null;
            $nilai = null;
            if ($rawVal !== null && trim((string) $rawVal) !== '') {
                $nilai = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
            }

            NilaiFormatifBulanan::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'mapel_id' => $m->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ], [
                'nilai' => $nilai,
            ]);
        }

        // Simpan Kehadiran Bulanan jika diinput
        if ($request->has('sakit') || $request->has('izin') || $request->has('alpa')) {
            KehadiranBulanan::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ], [
                'sakit' => max(0, (int) $request->input('sakit', 0)),
                'izin' => max(0, (int) $request->input('izin', 0)),
                'alpa' => max(0, (int) $request->input('alpa', 0)),
            ]);
        }

        $namaSiswa = $kelasSiswa->siswa->nama ?? 'Siswa';
        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        $nextSiswaId = $request->input('next_siswa_id');
        if ($nextSiswaId) {
            return redirect()->route('kelas.nilai-formatif.input-siswa', [
                'kelas' => $kelas->id,
                'kelasSiswa' => $nextSiswaId,
                'tahun' => $tahun,
                'bulan' => $bulan,
            ])->with('success', "Nilai formatif {$namaSiswa} ({$namaBulan}) berhasil disimpan. Melanjutkan ke siswa berikutnya.");
        }

        return redirect()->back()->with('success', "Nilai formatif {$namaSiswa} ({$namaBulan}) berhasil disimpan.");
    }

    /**
     * Lembar Rapor Formatif Bulanan Per Siswa (Persis Layout Rapor UTS / STS).
     */
    public function raportSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $kelasSiswa->load('siswa');
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $siswa = $kelasSiswa->siswa;
        $mapels = $kelas->mataPelajaran();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        // Nilai formatif siswa ini
        $nilaiRecords = NilaiFormatifBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('mapel_id');

        // Fallback otomatis dari tabel nilai jika formatif bulanan belum diinput khusus
        $existingNilaiList = \App\Models\Nilai::where('kelas_siswa_id', $kelasSiswa->id)->get()->keyBy('mapel_id');

        // Susun baris nilai
        $baris = [];
        $totalNilai = 0;
        $countNilai = 0;

        foreach ($mapels as $idx => $m) {
            $kkm = $kelas->getKkmForMapel($m);
            $rec = $nilaiRecords->get($m->id);
            $val = $rec && $rec->nilai !== null ? (float) $rec->nilai : null;

            if ($val === null && $existingNilaiList->has($m->id)) {
                $ex = $existingNilaiList->get($m->id);
                $val = $ex->formatif !== null ? (float) $ex->formatif : ($ex->sumatif !== null ? (float) $ex->sumatif : null);
            }

            if ($val !== null) {
                $totalNilai += $val;
                $countNilai++;
            }

            $keterangan = $val !== null ? ($val >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-';

            $baris[] = [
                'no' => $idx + 1,
                'mapel_nama' => $m->nama,
                'kkm' => $kkm,
                'nilai' => $val,
                'nilai_format' => $val !== null ? HitungNilaiService::formatAngka($val) : '-',
                'keterangan' => $keterangan,
            ];
        }

        $rataRata = $countNilai > 0 ? round($totalNilai / $countNilai, 2) : 0;
        $rataRataFormat = HitungNilaiService::formatAngka($rataRata);

        // Kehadiran bulanan (dengan fallback ke tabel kehadiran utama)
        $kehadiran = KehadiranBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        $fallbackKeh = \App\Models\Kehadiran::where('kelas_siswa_id', $kelasSiswa->id)->first();
        $sakit = $kehadiran ? $kehadiran->sakit : ($fallbackKeh?->sakit ?? 0);
        $izin = $kehadiran ? $kehadiran->izin : ($fallbackKeh?->izin ?? 0);
        $alpa = $kehadiran ? $kehadiran->alpa : ($fallbackKeh?->alpa ?? 0);

        // Tanggal Rapor (bisa diatur dari parameter atau tanggal kelas)
        $tanggalInput = $request->input('tanggal');
        $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput) : now();

        // Navigasi prev & next siswa
        $allSiswaIds = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
        $prevId = $currentIndex > 0 ? $allSiswaIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allSiswaIds) - 1 ? $allSiswaIds[$currentIndex + 1] : null;

        return view('kelas.nilai_formatif.raport_siswa', compact(
            'kelas',
            'kelasSiswa',
            'siswa',
            'sekolah',
            'tahun',
            'bulan',
            'namaBulan',
            'baris',
            'totalNilai',
            'rataRata',
            'rataRataFormat',
            'sakit',
            'izin',
            'alpa',
            'tanggalObj',
            'prevId',
            'nextId'
        ));
    }

    /**
     * Cetak Lembar Rapor Formatif Bulanan Seluruh Siswa Sekelas Sekaligus (Persis Cetak Raport Sekelas UTS).
     */
    public function cetakSekelas(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $mapels = $kelas->mataPelajaran();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');
        $allNilai = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        // Fallback otomatis dari tabel nilai dan kehadiran agar guru tidak perlu mengulang input data
        $allExistingNilai = \App\Models\Nilai::whereIn('kelas_siswa_id', $ksIds)->get();
        $fallbackKehadiran = \App\Models\Kehadiran::whereIn('kelas_siswa_id', $ksIds)->get()->keyBy('kelas_siswa_id');

        $allKehadiran = KehadiranBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');

        $tanggalInput = $request->input('tanggal');
        $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput) : now();

        $laporanSiswa = [];

        foreach ($kelasSiswaList as $ks) {
            $siswa = $ks->siswa;
            $nilaiRecords = $allNilai->where('kelas_siswa_id', $ks->id)->keyBy('mapel_id');
            $keh = $allKehadiran->get($ks->id);

            $baris = [];
            $totalNilai = 0;
            $countNilai = 0;

            foreach ($mapels as $idx => $m) {
                $kkm = $kelas->getKkmForMapel($m);
                $rec = $nilaiRecords->get($m->id);
                $val = $rec && $rec->nilai !== null ? (float) $rec->nilai : null;

                if ($val === null) {
                    $ex = $allExistingNilai->where('kelas_siswa_id', $ks->id)->where('mapel_id', $m->id)->first();
                    if ($ex) {
                        $val = $ex->formatif !== null ? (float) $ex->formatif : ($ex->sumatif !== null ? (float) $ex->sumatif : null);
                    }
                }

                if ($val !== null) {
                    $totalNilai += $val;
                    $countNilai++;
                }

                $keterangan = $val !== null ? ($val >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-';

                $baris[] = [
                    'no' => $idx + 1,
                    'mapel_nama' => $m->nama,
                    'kkm' => $kkm,
                    'nilai' => $val,
                    'nilai_format' => $val !== null ? HitungNilaiService::formatAngka($val) : '-',
                    'keterangan' => $keterangan,
                ];
            }

            $rataRata = $countNilai > 0 ? round($totalNilai / $countNilai, 2) : 0;
            $rataRataFormat = HitungNilaiService::formatAngka($rataRata);

            $laporanSiswa[] = [
                'kelasSiswa' => $ks,
                'siswa' => $siswa,
                'baris' => $baris,
                'totalNilai' => $totalNilai,
                'rataRata' => $rataRata,
                'rataRataFormat' => $rataRataFormat,
                'sakit' => $keh ? $keh->sakit : ($fallbackKehadiran->get($ks->id)?->sakit ?? 0),
                'izin' => $keh ? $keh->izin : ($fallbackKehadiran->get($ks->id)?->izin ?? 0),
                'alpa' => $keh ? $keh->alpa : ($fallbackKehadiran->get($ks->id)?->alpa ?? 0),
            ];
        }

        return view('kelas.nilai_formatif.cetak_sekelas', compact(
            'kelas',
            'sekolah',
            'laporanSiswa',
            'tahun',
            'bulan',
            'namaBulan',
            'tanggalObj'
        ));
    }

    /**
     * Cetak Lembar Rekapitulasi Nilai Formatif Bulanan Sekelas (Leger Bulanan 1 Halaman Landscape).
     */
    public function cetakRekapBulanan(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $mapels = $kelas->mataPelajaran();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');
        $allNilai = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        // Fallback otomatis dari tabel nilai dan kehadiran
        $allExistingNilai = \App\Models\Nilai::whereIn('kelas_siswa_id', $ksIds)->get();
        $allKehadiran = KehadiranBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');
        $fallbackKehadiran = \App\Models\Kehadiran::whereIn('kelas_siswa_id', $ksIds)->get()->keyBy('kelas_siswa_id');

        $tanggalInput = $request->input('tanggal');
        $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput) : now();

        $rekapSiswa = [];
        $mapelTotal = [];
        $mapelCount = [];
        foreach ($mapels as $m) {
            $mapelTotal[$m->id] = 0;
            $mapelCount[$m->id] = 0;
        }

        $totalKelasSemua = 0;

        foreach ($kelasSiswaList as $ks) {
            $siswa = $ks->siswa;
            $nilaiRecords = $allNilai->where('kelas_siswa_id', $ks->id)->keyBy('mapel_id');
            $keh = $allKehadiran->get($ks->id);

            $nilaiMapels = [];
            $sumNilai = 0;
            $countNilai = 0;

            foreach ($mapels as $m) {
                $rec = $nilaiRecords->get($m->id);
                $val = $rec && $rec->nilai !== null ? (float) $rec->nilai : null;
                if ($val === null) {
                    $ex = $allExistingNilai->where('kelas_siswa_id', $ks->id)->where('mapel_id', $m->id)->first();
                    if ($ex) {
                        $val = $ex->formatif !== null ? (float) $ex->formatif : ($ex->sumatif !== null ? (float) $ex->sumatif : null);
                    }
                }

                if ($val !== null) {
                    $sumNilai += $val;
                    $countNilai++;
                    $mapelTotal[$m->id] += $val;
                    $mapelCount[$m->id]++;
                }

                $nilaiMapels[$m->id] = $val;
            }

            $avgNilai = $countNilai > 0 ? round($sumNilai / $countNilai, 1) : 0;
            $totalKelasSemua += $avgNilai;

            $sakit = $keh ? $keh->sakit : ($fallbackKehadiran->get($ks->id)?->sakit ?? 0);
            $izin = $keh ? $keh->izin : ($fallbackKehadiran->get($ks->id)?->izin ?? 0);
            $alpa = $keh ? $keh->alpa : ($fallbackKehadiran->get($ks->id)?->alpa ?? 0);

            $rekapSiswa[] = [
                'kelasSiswa' => $ks,
                'siswa' => $siswa,
                'nilaiMapels' => $nilaiMapels,
                'totalNilai' => $sumNilai,
                'rataRata' => $avgNilai,
                'rataRataFormat' => HitungNilaiService::formatAngka($avgNilai),
                'countNilai' => $countNilai,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
            ];
        }

        // Urutkan peringkat berdasarkan totalNilai tertinggi
        usort($rekapSiswa, function ($a, $b) {
            if ($a['totalNilai'] == $b['totalNilai']) {
                return 0;
            }

            return ($a['totalNilai'] < $b['totalNilai']) ? 1 : -1;
        });

        $currentRank = 1;
        $prevScore = null;
        foreach ($rekapSiswa as &$item) {
            if ($prevScore !== null && abs($item['totalNilai'] - $prevScore) < 0.0001) {
                $item['peringkat'] = $currentRank;
            } else {
                $item['peringkat'] = $currentRank;
            }
            $prevScore = $item['totalNilai'];
            $currentRank++;
        }
        unset($item);

        // Hitung rata-rata per mapel untuk kelas
        $rataMapelKelas = [];
        foreach ($mapels as $m) {
            $c = $mapelCount[$m->id];
            $avg = $c > 0 ? round($mapelTotal[$m->id] / $c, 1) : 0;
            $rataMapelKelas[$m->id] = HitungNilaiService::formatAngka($avg);
        }

        $rataRataKelas = count($kelasSiswaList) > 0 ? round($totalKelasSemua / count($kelasSiswaList), 1) : 0;

        return view('kelas.nilai_formatif.cetak_rekap', compact(
            'kelas',
            'sekolah',
            'mapels',
            'rekapSiswa',
            'rataMapelKelas',
            'rataRataKelas',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulan',
            'tanggalObj'
        ));
    }

    /**
     * Input Cepat Sekelas (Matriks Seluruh Mapel).
     */
    public function matriks(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = now()->month;
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $mapels = $kelas->mataPelajaran();

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $daftarBulan = NilaiFormatifBulanan::NAMA_BULAN;
        $namaBulan = $daftarBulan[$bulan] ?? "Bulan {$bulan}";

        $allRecords = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        $matriksNilai = [];
        foreach ($allRecords as $r) {
            $matriksNilai[$r->kelas_siswa_id][$r->mapel_id] = $r->nilai;
        }

        $rekapSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $sum = 0;
            $cnt = 0;
            foreach ($mapels as $m) {
                $val = $matriksNilai[$ks->id][$m->id] ?? null;
                if ($val !== null && $val !== '') {
                    $sum += (float) $val;
                    $cnt++;
                }
            }
            $rekapSiswa[$ks->id] = [
                'total' => $cnt > 0 ? round($sum, 1) : null,
                'rata_rata' => $cnt > 0 ? round($sum / $cnt, 1) : null,
            ];
        }

        return view('kelas.nilai_formatif.matriks', compact(
            'kelas',
            'kelasSiswaList',
            'mapels',
            'matriksNilai',
            'rekapSiswa',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulan'
        ));
    }

    /**
     * Simpan Input Cepat Matriks.
     */
    public function simpanMatriks(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);

        $matriksData = $request->input('matriks', []);
        $mapels = $kelas->mataPelajaran();
        $kelasSiswaList = $kelas->kelasSiswa;

        foreach ($kelasSiswaList as $ks) {
            if (! isset($matriksData[$ks->id]) || ! is_array($matriksData[$ks->id])) {
                continue;
            }

            foreach ($mapels as $mapel) {
                $rawVal = $matriksData[$ks->id][$mapel->id] ?? null;
                $nilai = null;
                if ($rawVal !== null && trim((string) $rawVal) !== '') {
                    $nilai = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
                }

                NilaiFormatifBulanan::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'mapel_id' => $mapel->id,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                ], [
                    'nilai' => $nilai,
                ]);
            }
        }

        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return redirect()->route('kelas.nilai-formatif.index', [
            'kelas' => $kelas->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', "Seluruh nilai formatif kelas bulan {$namaBulan} {$tahun} berhasil disimpan.");
    }

    /**
     * Unduh template file Excel / CSV.
     */
    public function downloadTemplateExcel(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        $format = strtolower($request->input('format', 'xlsx'));

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan_{$bulan}";
        $slugKelas = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $kelas->nama));

        $mapels = $kelas->mataPelajaran();
        $headers = ['No', 'NIS', 'Nama Siswa'];
        foreach ($mapels as $m) {
            $headers[] = $m->nama;
        }

        $allRecords = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        $matrix = [];
        foreach ($allRecords as $r) {
            $matrix[$r->kelas_siswa_id][$r->mapel_id] = $r->nilai;
        }

        $dataRows = [];
        foreach ($kelasSiswaList as $idx => $ks) {
            $row = [
                $idx + 1,
                $ks->siswa->nis ?? '',
                $ks->siswa->nama,
            ];
            foreach ($mapels as $m) {
                $val = $matrix[$ks->id][$m->id] ?? null;
                $row[] = $val !== null ? (string) $val : '';
            }
            $dataRows[] = $row;
        }

        $filename = "template_formatif_{$slugKelas}_{$namaBulan}_{$tahun}.{$format}";

        if ($format === 'csv') {
            $responseHeaders = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($headers, $dataRows) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                fputcsv($output, $headers);
                foreach ($dataRows as $r) {
                    fputcsv($output, $r);
                }
                fclose($output);
            };

            return response()->stream($callback, 200, $responseHeaders);
        }

        return ExcelWriter::downloadResponse($headers, $dataRows, $filename, "Formatif {$namaBulan}");
    }

    /**
     * Impor nilai formatif bulanan dari file Excel, CSV, atau tempel clipboard.
     */
    public function importExcel(Kelas $kelas, Request $request): RedirectResponse
    {
        $this->authorizeAksesKelas($kelas);

        $request->validate([
            'tahun' => 'required|integer',
            'bulan' => 'required|integer|between:1,12',
            'file_excel' => 'nullable|file|mimes:xlsx,csv,txt|max:10240',
            'data_teks' => 'nullable|string',
        ]);

        $tahun = (int) $request->input('tahun');
        $bulan = (int) $request->input('bulan');

        $rows = [];
        if ($request->hasFile('file_excel')) {
            $file = $request->file('file_excel');
            $rows = ExcelReader::parseFile($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif (! empty($request->input('data_teks'))) {
            $rows = ExcelReader::parseClipboard($request->input('data_teks'));
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Tidak ada data yang terdeteksi dari file atau teks yang diunggah.');
        }

        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        $byNis = $kelasSiswaList->keyBy(fn ($ks) => trim((string) ($ks->siswa->nis ?? '')));
        $byName = $kelasSiswaList->keyBy(fn ($ks) => strtolower(trim((string) $ks->siswa->nama)));

        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[0]);
        $mapels = $kelas->mataPelajaran();

        $nisCol = array_search('nis', $headerRow);
        $namaCol = false;
        foreach ($headerRow as $idx => $hdr) {
            if (str_contains($hdr, 'nama') || str_contains($hdr, 'siswa') || str_contains($hdr, 'peserta')) {
                $namaCol = $idx;
                break;
            }
        }

        $mapelCols = [];
        foreach ($mapels as $m) {
            $mNama = strtolower(trim($m->nama));
            foreach ($headerRow as $idx => $hdr) {
                if ($idx === $nisCol || $idx === $namaCol) {
                    continue;
                }
                if ($hdr === $mNama || str_contains($hdr, $mNama) || str_contains($mNama, $hdr)) {
                    $mapelCols[$m->id] = $idx;
                    break;
                }
            }
        }

        $importedCount = 0;
        $dataRows = array_slice($rows, 1);
        foreach ($dataRows as $r) {
            $nisVal = $nisCol !== false ? trim((string) ($r[$nisCol] ?? '')) : '';
            $namaVal = $namaCol !== false ? strtolower(trim((string) ($r[$namaCol] ?? ''))) : '';

            $ks = ($nisVal !== '' && isset($byNis[$nisVal])) ? $byNis[$nisVal] : ($namaVal !== '' && isset($byName[$namaVal]) ? $byName[$namaVal] : null);
            if (! $ks) {
                continue;
            }

            foreach ($mapelCols as $mId => $cIdx) {
                $rawVal = $r[$cIdx] ?? null;
                if ($rawVal === null || trim((string) $rawVal) === '') {
                    continue;
                }

                $val = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));

                NilaiFormatifBulanan::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'mapel_id' => $mId,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                ], [
                    'nilai' => $val,
                ]);

                $importedCount++;
            }
        }

        return redirect()->back()->with('success', "Berhasil mengimpor {$importedCount} data nilai formatif bulanan.");
    }

    /**
     * Dapatkan daftar bulan untuk semester yang aktif (Ganjil: Juli-Desember [7-12], Genap: Januari-Juni [1-6]).
     */
    public static function getBulanSemester(string $semester = 'ganjil'): array
    {
        if (strtolower($semester) === 'genap') {
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
     * Hitung rekap nilai bulanan & rata-rata semester untuk satu siswa.
     * Bulan kosong diabaikan dari pembagian rata-rata.
     */
    public function hitungRekapSemesterSiswaData(Kelas $kelas, KelasSiswa $kelasSiswa, array $bulanSemester, int $tahun): array
    {
        $mapels = $kelas->mataPelajaran();
        $bulanKeys = array_keys($bulanSemester);

        // Ambil nilai formatif bulanan siswa untuk bulan-bulan di semester ini
        $nilaiBulananList = NilaiFormatifBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanKeys)
            ->get();

        // Fallback ke tabel nilai jika ada mapel yang belum ada di formatif bulanan
        $fallbackNilai = \App\Models\Nilai::where('kelas_siswa_id', $kelasSiswa->id)->get()->keyBy('mapel_id');

        $baris = [];
        $totalSemuaMapel = 0;
        $countMapelTerisi = 0;

        foreach ($mapels as $idx => $m) {
            $kkm = $kelas->getKkmForMapel($m);
            $nilaiPerBulan = [];
            $sumMapel = 0;
            $countBulanTerisi = 0;

            foreach ($bulanSemester as $bNum => $bNama) {
                $rec = $nilaiBulananList->first(function ($item) use ($m, $bNum) {
                    return $item->mapel_id === $m->id && $item->bulan === $bNum;
                });
                $val = ($rec && $rec->nilai !== null) ? (float) $rec->nilai : null;

                // Jika null dan ini bulan September/Oktober (periode STS) atau bulan akhir, coba fallback dari tabel nilai
                if ($val === null && $fallbackNilai->has($m->id)) {
                    $fn = $fallbackNilai->get($m->id);
                    if ($fn && ($fn->formatif !== null || $fn->sumatif !== null)) {
                        if ($bNum === 9 || $bNum === 10) {
                            $val = $fn->formatif !== null ? (float) $fn->formatif : (float) $fn->sumatif;
                        }
                    }
                }

                if ($val !== null) {
                    $sumMapel += $val;
                    $countBulanTerisi++;
                }

                $nilaiPerBulan[$bNum] = $val;
            }

            // Aturan Bisnis #2: Rata-rata dihitung berdasarkan bulan yang sudah diisi. Bulan kosong tidak ikut dihitung.
            $rataMapel = $countBulanTerisi > 0 ? round($sumMapel / $countBulanTerisi, 1) : null;

            if ($rataMapel !== null) {
                $totalSemuaMapel += $rataMapel;
                $countMapelTerisi++;
            }

            // Capaian Kompetensi / Keterangan Predikat
            $predikat = $rataMapel !== null ? \App\Models\NilaiRaporMapel::hitungPredikat($rataMapel) : '-';
            $keterangan = $rataMapel !== null ? ($rataMapel >= $kkm ? 'Tuntas' : 'Perlu Bimbingan') : '-';

            $baris[] = [
                'no' => $idx + 1,
                'mapel' => $m,
                'kkm' => $kkm,
                'nilai_bulanan' => $nilaiPerBulan,
                'count_bulan_terisi' => $countBulanTerisi,
                'rata_rata' => $rataMapel,
                'rata_rata_format' => $rataMapel !== null ? HitungNilaiService::formatAngka($rataMapel) : '-',
                'predikat' => $predikat,
                'keterangan' => $keterangan,
            ];
        }

        $rataRataKeseluruhan = $countMapelTerisi > 0 ? round($totalSemuaMapel / $countMapelTerisi, 1) : 0;

        // Kehadiran Semester
        $kehadiranBulanan = KehadiranBulanan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('tahun', $tahun)
            ->whereIn('bulan', $bulanKeys)
            ->get();

        $fallbackKeh = \App\Models\Kehadiran::where('kelas_siswa_id', $kelasSiswa->id)->first();
        if ($kehadiranBulanan->isNotEmpty()) {
            $sakit = $kehadiranBulanan->sum('sakit');
            $izin = $kehadiranBulanan->sum('izin');
            $alpa = $kehadiranBulanan->sum('alpa');
        } else {
            $sakit = $fallbackKeh ? $fallbackKeh->sakit : 0;
            $izin = $fallbackKeh ? $fallbackKeh->izin : 0;
            $alpa = $fallbackKeh ? $fallbackKeh->alpa : 0;
        }

        return [
            'kelasSiswa' => $kelasSiswa,
            'siswa' => $kelasSiswa->siswa,
            'baris' => $baris,
            'totalSemuaMapel' => $totalSemuaMapel,
            'totalSemuaMapelFormat' => HitungNilaiService::formatAngka($totalSemuaMapel),
            'rataRataKeseluruhan' => $rataRataKeseluruhan,
            'rataRataKeseluruhanFormat' => HitungNilaiService::formatAngka($rataRataKeseluruhan),
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
        ];
    }

    /**
     * F15: Cetak Rekap Nilai Semester Kumulatif Bulanan untuk Satu Siswa (Pas 1 Lembar F4 Portrait).
     */
    public function rekapSemesterSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        $bulanSemester = self::getBulanSemester($semester);
        $dataSiswa = $this->hitungRekapSemesterSiswaData($kelas, $kelasSiswa, $bulanSemester, $tahun);

        $tanggalInput = $request->input('tanggal') ?? $kelas->getTanggalRaport('sas');
        try {
            $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            $tanggalObj = Carbon::now()->locale('id');
        }

        // Navigasi prev & next siswa
        $allSiswaIds = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
        $prevId = $currentIndex > 0 ? $allSiswaIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allSiswaIds) - 1 ? $allSiswaIds[$currentIndex + 1] : null;

        return view('kelas.nilai_formatif.rekap_semester_siswa', compact(
            'kelas',
            'sekolah',
            'kelasSiswa',
            'dataSiswa',
            'bulanSemester',
            'semester',
            'tahun',
            'tanggalObj',
            'prevId',
            'nextId'
        ));
    }

    /**
     * F16: Cetak Rekap Nilai Semester Kumulatif Bulanan Seluruh Siswa Sekelas (Setiap Siswa Pas 1 Lembar F4).
     */
    public function cetakRekapSemesterSekelas(Kelas $kelas, Request $request): View
    {
        $this->authorizeAksesKelas($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahun = (int) $request->input('tahun', now()->year);
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        $bulanSemester = self::getBulanSemester($semester);

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $laporanSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $laporanSiswa[] = $this->hitungRekapSemesterSiswaData($kelas, $ks, $bulanSemester, $tahun);
        }

        $tanggalInput = $request->input('tanggal') ?? $kelas->getTanggalRaport('sas');
        try {
            $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            $tanggalObj = Carbon::now()->locale('id');
        }

        return view('kelas.nilai_formatif.rekap_semester_sekelas', compact(
            'kelas',
            'sekolah',
            'laporanSiswa',
            'bulanSemester',
            'semester',
            'tahun',
            'tanggalObj'
        ));
    }

    /**
     * Validasi hak akses wali kelas & administrator.
     */
    protected function authorizeAksesKelas(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Hanya Administrator atau Wali Kelas yang mengampu kelas ini yang berhak mengakses nilai formatif bulanan.');
        }

        if (! $kelas->tahunAjaran || ! $kelas->tahunAjaran->aktif) {
            abort(403, 'Tahun ajaran kelas ini tidak aktif. Nilai hanya dapat diubah pada tahun ajaran yang aktif.');
        }
    }
}
