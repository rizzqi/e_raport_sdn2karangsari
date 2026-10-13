<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Sekolah;
use App\Models\Raport;
use App\Models\Pembiasaan;
use App\Models\NilaiPembiasaan;
use App\Models\Kehadiran;
use App\Services\HitungNilaiService;
use App\Services\RaportDocxService;
use Barryvdh\DomPDF\Facade\Pdf;

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
     * F10: Pratinjau Raport resmi di web
     */
    public function preview(KelasSiswa $kelasSiswa, Request $request)
    {
        $periode = $request->input('periode', 'sts');
        $kelas = $kelasSiswa->kelas;
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $siswa = $kelasSiswa->siswa;
        $tahun = $kelas->tahunAjaran;

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

        $filePath = $this->docxService->generateDocxSatuSiswa($kelasSiswa, $periode);

        // Catat riwayat cetak F14
        Raport::create([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
            'tanggal_cetak' => now()->toDateString(),
            'file_path' => $filePath,
            'dibuat_oleh' => auth()->id(),
        ]);

        $fileName = 'Raport_' . str_replace(' ', '_', $kelasSiswa->siswa->nama) . '_' . strtoupper($periode) . '.docx';
        return response()->download($filePath, $fileName);
    }

    /**
     * F11: Unduh Raport .docx satu kelas penuh (satu dokumen Word)
     */
    public function downloadKelasDocx(Kelas $kelas, Request $request)
    {
        $periode = $request->input('periode', 'sts');

        $filePath = $this->docxService->generateDocxSatuKelas($kelas, $periode);

        // Catat riwayat cetak F14 untuk semua siswa di kelas
        foreach ($kelas->kelasSiswa as $ks) {
            Raport::create([
                'kelas_siswa_id' => $ks->id,
                'periode' => $periode,
                'tanggal_cetak' => now()->toDateString(),
                'file_path' => $filePath,
                'dibuat_oleh' => auth()->id(),
            ]);
        }

        $fileName = 'Raport_Kelas_' . str_replace(' ', '_', $kelas->nama) . '_' . strtoupper($periode) . '.docx';
        return response()->download($filePath, $fileName);
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
            'tanggal_cetak' => now()->toDateString(),
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
            'hasilNilai',
            'pembiasaanList',
            'nilaiPemList',
            'kehadiran'
        ))->setPaper([0, 0, 609.45, 935.43], 'portrait');

        $fileName = 'Raport_' . str_replace(' ', '_', $siswa->nama) . '_' . strtoupper($periode) . '.pdf';
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
