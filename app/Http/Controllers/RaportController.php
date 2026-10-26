<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Raport;
use App\Models\Sekolah;
use App\Services\HitungNilaiService;
use App\Services\RaportDocxService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RaportController extends Controller
{
    protected HitungNilaiService $hitungService;

    protected RaportDocxService $docxService;

    public function __construct(HitungNilaiService $hitungService, RaportDocxService $docxService)
    {
        $this->hitungService = $hitungService;
        $this->docxService = $docxService;
    }

    /**
     * Dapatkan objek Carbon tanggal cetak raport berdasarkan input request atau pengaturan kelas.
     */
    protected function resolveTanggalRaport(Kelas $kelas, string $periode, Request $request): Carbon
    {
        $rawTanggal = $request->input('tanggal');
        if (! $rawTanggal) {
            $rawTanggal = $kelas->getTanggalRaport($periode);
        }

        try {
            return $rawTanggal ? Carbon::parse($rawTanggal)->locale('id') : Carbon::now()->locale('id');
        } catch (\Throwable $e) {
            return Carbon::now()->locale('id');
        }
    }

    /**
     * F10: Pratinjau Raport resmi di web
     */
    public function preview(KelasSiswa $kelasSiswa, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $kelas = $kelasSiswa->kelas;
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $siswa = $kelasSiswa->siswa;
        $tahun = $kelas->tahunAjaran;

        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $hasilNilai = $this->hitungService->hitungNilaiSiswa($kelasSiswa, $periode);
        $pembiasaanList = Pembiasaan::aktif()->get();
        $nilaiPemList = NilaiPembiasaan::where('kelas_siswa_id', $kelasSiswa->id)
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

        return view('raport.preview', compact(
            'kelasSiswa',
            'kelas',
            'sekolah',
            'siswa',
            'tahun',
            'periode',
            'tanggal',
            'tanggalObj',
            'hasilNilai',
            'pembiasaanList',
            'nilaiPemList',
            'kehadiran'
        ));
    }

    /**
     * F11: Unduh Raport .docx satu siswa
     */
    public function downloadDocx(KelasSiswa $kelasSiswa, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $kelas = $kelasSiswa->kelas;
        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $filePath = $this->docxService->generateDocxSatuSiswa($kelasSiswa, $periode, $tanggal);

        // Catat riwayat cetak F14
        Raport::create([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
            'tanggal_cetak' => $tanggalObj->toDateString(),
            'file_path' => $filePath,
            'dibuat_oleh' => auth()->id(),
        ]);

        $fileName = 'Raport_'.str_replace(' ', '_', $kelasSiswa->siswa->nama).'_'.strtoupper($periode).'.docx';

        return response()->download($filePath, $fileName);
    }

    /**
     * F11: Unduh Raport .docx satu kelas penuh (satu dokumen Word)
     */
    public function downloadKelasDocx(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $filePath = $this->docxService->generateDocxSatuKelas($kelas, $periode, $tanggal);

        // Catat riwayat cetak F14 untuk semua siswa di kelas
        foreach ($kelas->kelasSiswa as $ks) {
            Raport::create([
                'kelas_siswa_id' => $ks->id,
                'periode' => $periode,
                'tanggal_cetak' => $tanggalObj->toDateString(),
                'file_path' => $filePath,
                'dibuat_oleh' => auth()->id(),
            ]);
        }

        $fileName = 'Raport_Kelas_'.str_replace(' ', '_', $kelas->nama).'_'.strtoupper($periode).'.docx';

        return response()->download($filePath, $fileName);
    }

    /**
     * Cetak Raport langsung satu kelas penuh di browser (Print Preview Sekelas)
     */
    public function cetakKelas(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahun = $kelas->tahunAjaran;

        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $kelasSiswaIds = $kelasSiswaList->pluck('id');
        $allNilaiPem = NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get()
            ->groupBy('kelas_siswa_id');

        $allKehadiran = Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        $pembiasaanList = Pembiasaan::aktif()->get();

        $laporanSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $laporanSiswa[] = [
                'kelasSiswa' => $ks,
                'siswa' => $ks->siswa,
                'hasilNilai' => $this->hitungService->hitungNilaiSiswa($ks, $periode),
                'nilaiPemList' => isset($allNilaiPem[$ks->id]) ? $allNilaiPem[$ks->id]->keyBy('pembiasaan_id') : collect(),
                'kehadiran' => $allKehadiran->get($ks->id) ?? (object) ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ];
        }

        return view('raport.cetak_kelas', compact(
            'kelas',
            'sekolah',
            'tahun',
            'periode',
            'tanggal',
            'tanggalObj',
            'pembiasaanList',
            'laporanSiswa'
        ));
    }

    /**
     * Unduh PDF Raport satu kelas penuh (satu dokumen PDF berisi seluruh siswa sekelas)
     */
    public function downloadKelasPdf(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahun = $kelas->tahunAjaran;

        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $kelasSiswaIds = $kelasSiswaList->pluck('id');
        $allNilaiPem = NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get()
            ->groupBy('kelas_siswa_id');

        $allKehadiran = Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds)
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        $pembiasaanList = Pembiasaan::aktif()->get();

        $laporanSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $laporanSiswa[] = [
                'kelasSiswa' => $ks,
                'siswa' => $ks->siswa,
                'hasilNilai' => $this->hitungService->hitungNilaiSiswa($ks, $periode),
                'nilaiPemList' => isset($allNilaiPem[$ks->id]) ? $allNilaiPem[$ks->id]->keyBy('pembiasaan_id') : collect(),
                'kehadiran' => $allKehadiran->get($ks->id) ?? (object) ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ];

            // Catat riwayat cetak F14
            Raport::create([
                'kelas_siswa_id' => $ks->id,
                'periode' => $periode,
                'tanggal_cetak' => $tanggalObj->toDateString(),
                'file_path' => 'PDF_STREAM_KELAS',
                'dibuat_oleh' => auth()->id(),
            ]);
        }

        $pdf = Pdf::loadView('raport.pdf_kelas', compact(
            'kelas',
            'sekolah',
            'tahun',
            'periode',
            'tanggal',
            'tanggalObj',
            'pembiasaanList',
            'laporanSiswa'
        ))->setPaper([0, 0, 609.45, 935.43], 'portrait');

        $fileName = 'Raport_Kelas_'.str_replace(' ', '_', $kelas->nama).'_'.strtoupper($periode).'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * F12: Unduh PDF Raport satu siswa
     */
    public function downloadPdf(KelasSiswa $kelasSiswa, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $kelas = $kelasSiswa->kelas;
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $siswa = $kelasSiswa->siswa;
        $tahun = $kelas->tahunAjaran;

        $tanggalObj = $this->resolveTanggalRaport($kelas, $periode, $request);
        $tanggal = $tanggalObj->format('Y-m-d');

        $hasilNilai = $this->hitungService->hitungNilaiSiswa($kelasSiswa, $periode);
        $pembiasaanList = Pembiasaan::aktif()->get();
        $nilaiPemList = NilaiPembiasaan::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('pembiasaan_id');

        $kehadiran = Kehadiran::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->first();

        // Catat riwayat cetak F14
        Raport::create([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
            'tanggal_cetak' => $tanggalObj->toDateString(),
            'file_path' => 'PDF_STREAM',
            'dibuat_oleh' => auth()->id(),
        ]);

        $pdf = Pdf::loadView('raport.pdf', compact(
            'kelasSiswa',
            'kelas',
            'sekolah',
            'siswa',
            'tahun',
            'periode',
            'tanggal',
            'tanggalObj',
            'hasilNilai',
            'pembiasaanList',
            'nilaiPemList',
            'kehadiran'
        ))->setPaper([0, 0, 609.45, 935.43], 'portrait');

        $fileName = 'Raport_'.str_replace(' ', '_', $siswa->nama).'_'.strtoupper($periode).'.pdf';

        return $pdf->download($fileName);
    }

    /**
     * F14: Riwayat Cetak Raport
     */
    public function riwayatCetak(Request $request)
    {
        $riwayatList = Raport::with(['kelasSiswa.siswa', 'kelasSiswa.kelas', 'pembuat'])
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('raport.riwayat', compact('riwayatList'));
    }
}
