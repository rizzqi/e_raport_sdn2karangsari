<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Raport;
use App\Models\Sekolah;
use App\Services\CetakService;
use App\Services\HitungNilaiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CetakController extends Controller
{
    public function __construct(
        protected CetakService $cetakService,
        protected HitungNilaiService $hitungService
    ) {}

    /**
     * Pastikan pengguna memiliki hak akses ke data cetak kelas.
     */
    protected function authorizeAkses(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isKepsek()) {
            return;
        }

        if ($user->isWali() && (int) $kelas->wali_id === (int) $user->id) {
            return;
        }

        if ($user->isGuru() && $kelas->guruPengampu()->where('user_id', $user->id)->exists()) {
            return;
        }

        abort(403, 'Anda tidak berhak mencetak dokumen kelas ini.');
    }

    /**
     * Endpoint JSON: Cek kelengkapan nilai, predikat, dan catatan guru sebelum cetak.
     */
    public function cekKelengkapan(Kelas $kelas, Request $request): JsonResponse
    {
        $this->authorizeAkses($kelas);

        $kelas->loadMissing(['tahunAjaran', 'sekolah']);
        $jenis = (string) $request->input('jenis', 'rapor_kelas');
        if (! in_array($jenis, CetakService::JENIS_VALID, true)) {
            $jenis = 'rapor_kelas';
        }

        $cakupan = (string) $request->input('cakupan', $jenis === 'rapor_siswa' ? 'siswa' : 'kelas');
        if (! in_array($cakupan, ['siswa', 'kelas', 'sekolah'], true)) {
            $cakupan = 'kelas';
        }

        $options = [
            'cakupan' => $cakupan,
            'kelas_siswa_id' => $request->filled('kelas_siswa_id') ? (int) $request->input('kelas_siswa_id') : null,
            'mapel_id' => $request->filled('mapel_id') ? (int) $request->input('mapel_id') : null,
            'tahun' => (int) $request->input('tahun', now()->year),
            'bulan' => (int) $request->input('bulan', now()->month),
            'semester' => strtolower((string) $request->input('semester', $kelas->tahunAjaran->semester ?? 'ganjil')),
        ];

        $hasil = $this->cetakService->cekKelengkapanCetak($kelas, $jenis, $options, auth()->user());

        return response()->json($hasil);
    }

    /**
     * Endpoint Cetak PDF Terpadu (Semua Mapel Kolektif / Filter Mapel / Cakupan Siswa, Kelas, Sekolah).
     */
    public function cetakDokumen(Kelas $kelas, Request $request)
    {
        $this->authorizeAkses($kelas);

        $kelas->loadMissing(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first() ?? new Sekolah;

        $jenis = (string) $request->input('jenis', 'rapor_kelas');
        if (! in_array($jenis, CetakService::JENIS_VALID, true)) {
            $jenis = 'rapor_kelas';
        }

        $cakupan = (string) $request->input('cakupan', $jenis === 'rapor_siswa' ? 'siswa' : 'kelas');
        if (! in_array($cakupan, ['siswa', 'kelas', 'sekolah'], true)) {
            $cakupan = 'kelas';
        }

        $filterMapelId = $request->filled('mapel_id') && is_numeric($request->input('mapel_id'))
            ? (int) $request->input('mapel_id')
            : null;
        $filterMapel = $filterMapelId ? MataPelajaran::find($filterMapelId) : null;

        $kelasSiswaId = $request->filled('kelas_siswa_id') && is_numeric($request->input('kelas_siswa_id'))
            ? (int) $request->input('kelas_siswa_id')
            : null;

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = max(1, min(12, (int) $request->input('bulan', now()->month)));
        $semester = strtolower((string) $request->input('semester', $kelas->tahunAjaran->semester ?? 'ganjil'));
        $periode = $request->input('periode');
        if (! in_array($periode, ['sts', 'sas'], true)) {
            $periode = str_starts_with($jenis, 'sts_') ? 'sts' : 'sas';
        }

        $rawTanggal = $request->input('tanggal') ?? $kelas->getTanggalRaport($periode);
        try {
            $tanggalObj = $rawTanggal ? Carbon::parse($rawTanggal)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            $tanggalObj = Carbon::now()->locale('id');
        }
        $tanggal = $tanggalObj->format('Y-m-d');

        $printConfig = $sekolah->resolvePrintConfig(
            $request->input('paper'),
            $request->input('orientation'),
            $request->input('margin')
        );

        $kelasList = $this->cetakService->resolveTargetKelasList($kelas, $cakupan, auth()->user());
        $scopeLabel = $cakupan === 'sekolah'
            ? 'Semua Kelas (Sekolah)'
            : ($cakupan === 'siswa' ? 'Per Siswa' : "Kelas {$kelas->nama}");

        // 1. Cetak Formatif (Bulanan & 1 Semester — semua mapel kolektif)
        if ($jenis === 'formatif_bulanan' || $jenis === 'formatif_semester') {
            $mode = $jenis === 'formatif_bulanan' ? 'bulanan' : 'semester';
            $dataPerKelas = $this->cetakService->buildKolektifFormatifData(
                $kelasList,
                $tahun,
                $bulan,
                $mode,
                $filterMapelId,
                $cakupan === 'siswa' ? $kelasSiswaId : null
            );
            $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

            $pdf = Pdf::loadView('cetak.pdf_formatif_kolektif', compact(
                'kelas',
                'sekolah',
                'dataPerKelas',
                'mode',
                'tahun',
                'bulan',
                'namaBulan',
                'semester',
                'tanggalObj',
                'printConfig',
                'filterMapel',
                'scopeLabel',
                'cakupan'
            ))->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Daftar_Nilai_Formatif_'.strtoupper($mode).'_'.$tahun.'.pdf');
        }

        // 2. Cetak Daftar Nilai STS / SAS (semua mapel kolektif)
        if ($jenis === 'sts_daftar' || $jenis === 'sas_daftar') {
            $periodeSumatif = $jenis === 'sts_daftar' ? 'sts' : 'sas';
            $dataPerKelas = $this->cetakService->buildKolektifStsSasData(
                $kelasList,
                $periodeSumatif,
                $filterMapelId,
                $cakupan === 'siswa' ? $kelasSiswaId : null
            );

            $pdf = Pdf::loadView('cetak.pdf_sts_sas_kolektif', compact(
                'kelas',
                'sekolah',
                'dataPerKelas',
                'periodeSumatif',
                'tahun',
                'semester',
                'tanggalObj',
                'printConfig',
                'filterMapel',
                'scopeLabel',
                'cakupan'
            ))->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Daftar_Nilai_'.strtoupper($periodeSumatif).'_'.$tahun.'.pdf');
        }

        // 3. Cetak Lembar Rapor STS / SAS Ringkas
        if ($jenis === 'sts_lembar' || $jenis === 'sas_lembar') {
            $periodeLembar = $jenis === 'sts_lembar' ? 'sts' : 'sas';
            $targetKsId = $cakupan === 'siswa' ? $kelasSiswaId : null;
            if ($cakupan === 'siswa' && $targetKsId) {
                $ks = KelasSiswa::where('kelas_id', $kelas->id)->findOrFail($targetKsId);

                return app(RaportController::class)->lembarStsSasPdf($kelas, $ks, $request);
            }

            $laporanPerKelas = $this->buildLaporanStsSasPerKelas($kelasList, $periodeLembar, $filterMapelId, $targetKsId);
            $firstKelasData = $laporanPerKelas[0] ?? null;
            $laporanSiswa = $firstKelasData['laporanSiswa'] ?? [];
            $pembiasaanList = Pembiasaan::aktif()->orderBy('urutan')->get();

            $pdf = Pdf::loadView('raport.lembar_sts_sas_kelas_pdf', compact(
                'kelas',
                'sekolah',
                'laporanSiswa',
                'laporanPerKelas',
                'pembiasaanList',
                'periode',
                'tanggal',
                'tanggalObj',
                'printConfig',
                'filterMapelId'
            ))->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Lembar_'.strtoupper($periodeLembar).'_'.$tahun.'.pdf');
        }

        // 4. Grup Cetak Rapor: 4 pilihan
        // 4a. Rapor Lengkap Per Siswa
        if ($jenis === 'rapor_siswa') {
            $ks = $kelasSiswaId
                ? KelasSiswa::where('kelas_id', $kelas->id)->findOrFail($kelasSiswaId)
                : $kelas->kelasSiswa()->with('siswa')->get()->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)->first();

            if (! $ks) {
                abort(404, 'Tidak ada data siswa pada kelas ini.');
            }

            $siswa = $ks->siswa;
            $tahunAjaran = $kelas->tahunAjaran;
            $hasilNilai = $this->hitungService->hitungNilaiSiswa($ks, $periode, null, null, null, $filterMapelId);
            $pembiasaanList = Pembiasaan::aktif()->get();
            $nilaiPemList = NilaiPembiasaan::where('kelas_siswa_id', $ks->id)
                ->where('periode', $periode)
                ->get()
                ->keyBy('pembiasaan_id');
            $kehadiran = Kehadiran::where('kelas_siswa_id', $ks->id)
                ->where('periode', $periode)
                ->first();

            Raport::create([
                'kelas_siswa_id' => $ks->id,
                'periode' => $periode,
                'tanggal_cetak' => $tanggalObj->toDateString(),
                'file_path' => 'PDF_STREAM',
                'dibuat_oleh' => auth()->id(),
            ]);

            $pdf = Pdf::loadView('raport.pdf', [
                'kelasSiswa' => $ks,
                'kelas' => $kelas,
                'sekolah' => $sekolah,
                'siswa' => $siswa,
                'tahun' => $tahunAjaran,
                'periode' => $periode,
                'tanggal' => $tanggal,
                'tanggalObj' => $tanggalObj,
                'hasilNilai' => $hasilNilai,
                'pembiasaanList' => $pembiasaanList,
                'nilaiPemList' => $nilaiPemList,
                'kehadiran' => $kehadiran,
                'printConfig' => $printConfig,
                'filterMapelId' => $filterMapelId,
            ])->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Raport_Lengkap_'.str_replace(' ', '_', $siswa->nama).'.pdf');
        }

        // 4b. Rapor Lengkap Seluruh Kelas (atau Semua Kelas Sekolah)
        if ($jenis === 'rapor_kelas') {
            $laporanPerKelas = $this->cetakService->buildKolektifRaporLengkapData(
                $kelasList,
                $periode,
                $filterMapelId,
                $cakupan === 'siswa' ? $kelasSiswaId : null
            );

            foreach ($laporanPerKelas as $blok) {
                foreach ($blok['laporanSiswa'] as $item) {
                    Raport::create([
                        'kelas_siswa_id' => $item['kelasSiswa']->id,
                        'periode' => $periode,
                        'tanggal_cetak' => $tanggalObj->toDateString(),
                        'file_path' => 'PDF_STREAM_KELAS',
                        'dibuat_oleh' => auth()->id(),
                    ]);
                }
            }

            $first = $laporanPerKelas[0] ?? null;
            $pdf = Pdf::loadView('raport.pdf_kelas', [
                'kelas' => $first ? $first['kelas'] : $kelas,
                'sekolah' => $sekolah,
                'tahun' => $first ? $first['tahun'] : $kelas->tahunAjaran,
                'periode' => $periode,
                'tanggal' => $tanggal,
                'tanggalObj' => $tanggalObj,
                'pembiasaanList' => $first ? $first['pembiasaanList'] : Pembiasaan::aktif()->get(),
                'laporanSiswa' => $first ? $first['laporanSiswa'] : [],
                'laporanPerKelas' => $laporanPerKelas,
                'printConfig' => $printConfig,
                'filterMapelId' => $filterMapelId,
            ])->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Raport_Lengkap_Kolektif_'.$tahun.'.pdf');
        }

        // 4c. Leger Kelas
        if ($jenis === 'rapor_leger') {
            $dataPerKelas = $this->cetakService->buildKolektifLegerData(
                $kelasList,
                $periode,
                $filterMapelId,
                $cakupan === 'siswa' ? $kelasSiswaId : null
            );

            $pdf = Pdf::loadView('cetak.pdf_leger_kolektif', compact(
                'kelas',
                'sekolah',
                'dataPerKelas',
                'periode',
                'tahun',
                'semester',
                'tanggalObj',
                'printConfig',
                'filterMapel',
                'scopeLabel',
                'cakupan'
            ))->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

            return $pdf->stream('Leger_Nilai_Rapor_'.$tahun.'.pdf');
        }

        // 4d. Rekap Predikat & Catatan Guru Semua Mapel
        $dataPerKelas = $this->cetakService->buildKolektifPredikatCatatanData(
            $kelasList,
            $periode,
            $filterMapelId,
            $cakupan === 'siswa' ? $kelasSiswaId : null
        );

        $pdf = Pdf::loadView('cetak.pdf_rekap_predikat_catatan', compact(
            'kelas',
            'sekolah',
            'dataPerKelas',
            'periode',
            'tahun',
            'semester',
            'tanggalObj',
            'printConfig',
            'filterMapel',
            'scopeLabel',
            'cakupan'
        ))->setPaper($printConfig['dompdf_paper'], $printConfig['orientation']);

        return $pdf->stream('Rekap_Predikat_Catatan_Guru_'.$tahun.'.pdf');
    }

    /**
     * Susun laporan lembar ringkas STS/SAS per kelas untuk mendukung cakupan Sekolah.
     *
     * @param  Collection<int, Kelas>  $kelasList
     * @return array<int, array<string, mixed>>
     */
    protected function buildLaporanStsSasPerKelas($kelasList, string $periode, ?int $filterMapelId, ?int $kelasSiswaId): array
    {
        $result = [];
        $pembiasaanList = Pembiasaan::aktif()->orderBy('urutan')->get();

        foreach ($kelasList as $k) {
            $ksList = $k->kelasSiswa()->with('siswa')->get()
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
                $hasilNilai = $this->hitungService->hitungNilaiSiswa($ks, $periode, null, null, null, $filterMapelId);
                $totalNilai = 0;
                $countNilai = 0;
                foreach ($hasilNilai['baris'] as $b) {
                    $val = $periode === 'sts'
                        ? ($b['nilai_uts'] ?? $b['sumatif'] ?? $b['nilai'])
                        : ($b['nilai_rapor'] ?? $b['nilai_katrol'] ?? $b['sumatif'] ?? $b['nilai']);
                    if ($val !== null) {
                        $totalNilai += (float) $val;
                        $countNilai++;
                    }
                }
                $rataNilai = $countNilai > 0 ? round($totalNilai / $countNilai, 0) : 0;

                $laporanSiswa[] = [
                    'kelasSiswa' => $ks,
                    'siswa' => $ks->siswa,
                    'hasilNilai' => $hasilNilai,
                    'totalNilai' => $totalNilai,
                    'rataNilai' => $rataNilai,
                    'nilaiPemList' => isset($allNilaiPem[$ks->id]) ? $allNilaiPem[$ks->id]->keyBy('pembiasaan_id') : collect(),
                    'kehadiran' => $allKehadiran->get($ks->id) ?? (object) ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
                ];
            }

            $result[] = [
                'kelas' => $k,
                'tahun' => $k->tahunAjaran,
                'pembiasaanList' => $pembiasaanList,
                'laporanSiswa' => $laporanSiswa,
            ];
        }

        return $result;
    }
}
