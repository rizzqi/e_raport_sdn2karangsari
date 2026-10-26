<?php

namespace App\Services;

use App\Models\Kehadiran;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Models\Sekolah;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class RaportDocxService
{
    protected HitungNilaiService $hitungService;

    public function __construct(HitungNilaiService $hitungService)
    {
        $this->hitungService = $hitungService;
    }

    /**
     * Buat file Docx untuk satu siswa
     */
    public function generateDocxSatuSiswa(KelasSiswa $kelasSiswa, string $periode = 'sts', ?string $tanggal = null): string
    {
        $phpWord = new PhpWord;
        $this->setupStyles($phpWord);

        $section = $phpWord->addSection([
            'pageSizeW' => 12189, // F4 / Folio (215 mm)
            'pageSizeH' => 18709, // F4 / Folio (330 mm)
            'marginTop' => 720, // ~1.27 cm
            'marginBottom' => 720,
            'marginLeft' => 1000,
            'marginRight' => 1000,
        ]);

        $this->tulisHalamanRaport($section, $kelasSiswa, $periode, $tanggal);

        $filename = 'raport_'.str_replace(' ', '_', strtolower($kelasSiswa->siswa->nama)).'_'.$periode.'.docx';
        $savePath = storage_path('app/public/raport/'.$filename);

        if (! file_exists(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($savePath);

        return $savePath;
    }

    /**
     * Buat file Docx untuk seluruh siswa dalam satu kelas
     */
    public function generateDocxSatuKelas(Kelas $kelas, string $periode = 'sts', ?string $tanggal = null): string
    {
        $phpWord = new PhpWord;
        $this->setupStyles($phpWord);

        $kelasSiswaList = $kelas->kelasSiswa()->with(['siswa'])->get();

        foreach ($kelasSiswaList as $index => $ks) {
            $section = $phpWord->addSection([
                'pageSizeW' => 12189, // F4 / Folio (215 mm)
                'pageSizeH' => 18709, // F4 / Folio (330 mm)
                'marginTop' => 720,
                'marginBottom' => 720,
                'marginLeft' => 1000,
                'marginRight' => 1000,
            ]);

            $this->tulisHalamanRaport($section, $ks, $periode, $tanggal);

            // Jika bukan siswa terakhir, section baru akan bertindak sebagai page break
        }

        $namaKelasClean = str_replace(' ', '_', strtolower($kelas->nama));
        $filename = 'raport_kelas_'.$namaKelasClean.'_'.$periode.'.docx';
        $savePath = storage_path('app/public/raport/'.$filename);

        if (! file_exists(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($savePath);

        return $savePath;
    }

    protected function setupStyles(PhpWord $phpWord): void
    {
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $phpWord->addFontStyle('kopPemda', ['bold' => true, 'size' => 11]);
        $phpWord->addFontStyle('kopSekolah', ['bold' => true, 'size' => 13]);
        $phpWord->addFontStyle('kopAlamat', ['italic' => true, 'size' => 8.5]);
        $phpWord->addFontStyle('kopKontak', ['size' => 8, 'color' => '333333']);
        $phpWord->addFontStyle('judulRaport', ['bold' => true, 'size' => 12, 'underline' => 'single']);
        $phpWord->addFontStyle('boldText', ['bold' => true, 'size' => 10]);
        $phpWord->addFontStyle('tableHeader', ['bold' => true, 'size' => 9.5]);
        $phpWord->addFontStyle('tableBody', ['size' => 9.5]);
    }

    protected function tulisHalamanRaport($section, KelasSiswa $ks, string $periode, ?string $tanggal = null): void
    {
        $kelas = $ks->kelas;
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahun = $kelas->tahunAjaran;
        $siswa = $ks->siswa;
        $wali = $kelas->wali;

        // Cari logo jika ada
        $logoKiriPath = null;
        if ($sekolah && $sekolah->logo) {
            if (file_exists(storage_path('app/public/'.$sekolah->logo))) {
                $logoKiriPath = storage_path('app/public/'.$sekolah->logo);
            } elseif (file_exists(public_path('storage/'.$sekolah->logo))) {
                $logoKiriPath = public_path('storage/'.$sekolah->logo);
            }
        }

        $logoKananPath = null;
        if ($sekolah && $sekolah->logo_kanan) {
            if (file_exists(storage_path('app/public/'.$sekolah->logo_kanan))) {
                $logoKananPath = storage_path('app/public/'.$sekolah->logo_kanan);
            } elseif (file_exists(public_path('storage/'.$sekolah->logo_kanan))) {
                $logoKananPath = public_path('storage/'.$sekolah->logo_kanan);
            }
        }

        $ttdPath = null;
        if ($sekolah && $sekolah->ttd) {
            if (file_exists(storage_path('app/public/'.$sekolah->ttd))) {
                $ttdPath = storage_path('app/public/'.$sekolah->ttd);
            } elseif (file_exists(public_path('storage/'.$sekolah->ttd))) {
                $ttdPath = public_path('storage/'.$sekolah->ttd);
            }
        }

        // 1. KOP SURAT STANDAR F4
        $kopTable = $section->addTable(['alignment' => JcTable::CENTER, 'width' => 10000]);
        $kopRow = $kopTable->addRow();

        // Kolom Logo Kiri
        $cellKiri = $kopRow->addCell(1300, ['valign' => VerticalJc::CENTER]);
        if ($logoKiriPath) {
            $cellKiri->addImage($logoKiriPath, ['width' => 50, 'height' => 50, 'alignment' => Jc::CENTER]);
        }

        // Kolom Teks Kop Tengah
        $kopCell = $kopRow->addCell(7400, ['valign' => VerticalJc::CENTER]);
        $kopCell->addText(strtoupper($sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI'), 'kopPemda', ['alignment' => Jc::CENTER, 'spaceAfter' => 15]);
        $kopCell->addText(strtoupper($sekolah->dinas ?? 'DINAS PENDIDIKAN'), 'kopPemda', ['alignment' => Jc::CENTER, 'spaceAfter' => 15]);
        $kopCell->addText(strtoupper($sekolah->nama ?? 'SD NEGERI 2 KARANGSARI'), 'kopSekolah', ['alignment' => Jc::CENTER, 'spaceAfter' => 25]);
        $kopCell->addText($sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi', 'kopAlamat', ['alignment' => Jc::CENTER, 'spaceAfter' => 15]);
        if ($sekolah && $sekolah->kontak) {
            $kopCell->addText($sekolah->kontak, 'kopKontak', ['alignment' => Jc::CENTER, 'spaceAfter' => 25]);
        }

        // Kolom Logo Kanan
        $cellKanan = $kopRow->addCell(1300, ['valign' => VerticalJc::CENTER]);
        if ($logoKananPath) {
            $cellKanan->addImage($logoKananPath, ['width' => 50, 'height' => 50, 'alignment' => Jc::CENTER]);
        }

        // Garis Pembatas Kop
        $lineTable = $section->addTable(['alignment' => JcTable::CENTER, 'width' => 10000]);
        $lineRow = $lineTable->addRow();
        $lineRow->addCell(10000, ['borderBottomSize' => 18, 'borderBottomColor' => '000000']);

        $section->addTextBreak(1);

        // 2. JUDUL LAPORAN
        $namaPeriode = $periode === 'sts' ? 'SUMATIF TENGAH SEMESTER (STS)' : 'SUMATIF AKHIR SEMESTER (SAS)';
        $section->addText('LAPORAN HASIL BELAJAR PESERTA DIDIK', 'judulRaport', ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $section->addText($namaPeriode, 'boldText', ['alignment' => Jc::CENTER, 'spaceAfter' => 100]);

        // 3. IDENTITAS SISWA & KELAS (2 KOLOM)
        $idTable = $section->addTable(['alignment' => JcTable::CENTER]);
        $idRow1 = $idTable->addRow();
        $idRow1->addCell(4500)->addText("Nama Peserta Didik  : {$siswa->nama}", 'boldText');
        $idRow1->addCell(4500)->addText("Kelas / Fase           : {$kelas->nama} / ".($kelas->fase ?? 'A'), 'tableBody');

        $idRow2 = $idTable->addRow();
        $idRow2->addCell(4500)->addText("NIS / NISN              : {$siswa->nis} / ".($siswa->nisn ?? '-'), 'tableBody');
        $idRow2->addCell(4500)->addText('Semester / Tahun    : '.ucfirst($tahun->semester ?? 'Ganjil').' / '.($tahun->tahun ?? '2026/2027'), 'tableBody');

        $section->addTextBreak(1);

        // 4. TABEL NILAI MATA PELAJARAN
        $hasilNilai = $this->hitungService->hitungNilaiSiswa($ks, $periode);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMarginTop' => 60,
            'cellMarginBottom' => 60,
            'cellMarginLeft' => 80,
            'cellMarginRight' => 80,
            'alignment' => JcTable::CENTER,
        ];
        $nilaiTable = $section->addTable($tableStyle);

        // Header Tabel Nilai
        $rowH = $nilaiTable->addRow(350);
        $rowH->addCell(600, ['bgColor' => 'EAEAEA', 'valign' => VerticalJc::CENTER])->addText('No', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowH->addCell(4400, ['bgColor' => 'EAEAEA', 'valign' => VerticalJc::CENTER])->addText('Mata Pelajaran', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowH->addCell(1200, ['bgColor' => 'EAEAEA', 'valign' => VerticalJc::CENTER])->addText('KKM', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowH->addCell(1400, ['bgColor' => 'EAEAEA', 'valign' => VerticalJc::CENTER])->addText('Nilai', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowH->addCell(1400, ['bgColor' => 'EAEAEA', 'valign' => VerticalJc::CENTER])->addText('Keterangan', 'tableHeader', ['alignment' => Jc::CENTER]);

        // Isi Tabel Nilai
        foreach ($hasilNilai['baris'] as $b) {
            $rowB = $nilaiTable->addRow(300);
            $rowB->addCell(600)->addText($b['no'], 'tableBody', ['alignment' => Jc::CENTER]);
            $rowB->addCell(4400)->addText($b['mapel_nama'], 'tableBody');
            $rowB->addCell(1200)->addText((string) $b['kkm'], 'tableBody', ['alignment' => Jc::CENTER]);
            $rowB->addCell(1400)->addText($b['akhir_format'], 'tableBody', ['alignment' => Jc::CENTER]);
            $rowB->addCell(1400)->addText($b['keterangan'], 'tableBody', ['alignment' => Jc::CENTER]);
        }

        // Baris Jumlah & Rata-rata
        $rowJumlah = $nilaiTable->addRow(300);
        $rowJumlah->addCell(5000, ['gridSpan' => 2, 'bgColor' => 'F2F2F2'])->addText('Jumlah Nilai', 'boldText', ['alignment' => Jc::RIGHT]);
        $rowJumlah->addCell(1200, ['bgColor' => 'F2F2F2'])->addText('-', 'boldText', ['alignment' => Jc::CENTER]);
        $rowJumlah->addCell(1400, ['bgColor' => 'F2F2F2'])->addText(HitungNilaiService::formatAngka($hasilNilai['total_akhir']), 'boldText', ['alignment' => Jc::CENTER]);
        $rowJumlah->addCell(1400, ['bgColor' => 'F2F2F2'])->addText('-', 'boldText', ['alignment' => Jc::CENTER]);

        $rowRata = $nilaiTable->addRow(300);
        $rowRata->addCell(5000, ['gridSpan' => 2, 'bgColor' => 'F2F2F2'])->addText('Rata-rata Nilai', 'boldText', ['alignment' => Jc::RIGHT]);
        $rowRata->addCell(1200, ['bgColor' => 'F2F2F2'])->addText('-', 'boldText', ['alignment' => Jc::CENTER]);
        $rowRata->addCell(1400, ['bgColor' => 'F2F2F2'])->addText($hasilNilai['rata_akhir_format'], 'boldText', ['alignment' => Jc::CENTER]);
        $rowRata->addCell(1400, ['bgColor' => 'F2F2F2'])->addText('-', 'boldText', ['alignment' => Jc::CENTER]);

        $section->addTextBreak(1);

        // 5. TABEL PEMBIASAAN DAN KEHADIRAN (BERDAMPINGAN)
        $section->addText('PERKEMBANGAN KARAKTER DAN KEHADIRAN', 'boldText', ['spaceAfter' => 50]);

        $pembiasaanList = Pembiasaan::aktif()->get();
        $nilaiPemList = NilaiPembiasaan::where('kelas_siswa_id', $ks->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('pembiasaan_id');

        $bottomTable = $section->addTable($tableStyle);
        $rowBH = $bottomTable->addRow(300);
        $rowBH->addCell(5600, ['bgColor' => 'EAEAEA'])->addText('Aspek Pembiasaan / Karakter', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowBH->addCell(1600, ['bgColor' => 'EAEAEA'])->addText('Predikat', 'tableHeader', ['alignment' => Jc::CENTER]);
        $rowBH->addCell(1800, ['bgColor' => 'EAEAEA'])->addText('Kehadiran', 'tableHeader', ['alignment' => Jc::CENTER]);

        $kehadiran = Kehadiran::where('kelas_siswa_id', $ks->id)
            ->where('periode', $periode)
            ->first();
        $sakit = $kehadiran ? $kehadiran->sakit : 0;
        $izin = $kehadiran ? $kehadiran->izin : 0;
        $alpa = $kehadiran ? $kehadiran->alpa : 0;

        $kehadiranLines = [
            "Sakit  : {$sakit} hari",
            "Izin    : {$izin} hari",
            "Alpa   : {$alpa} hari",
        ];

        foreach ($pembiasaanList as $pIdx => $pem) {
            $rowPem = $bottomTable->addRow(280);
            $rowPem->addCell(5600)->addText(($pIdx + 1).'. '.$pem->nama, 'tableBody');
            $val = $nilaiPemList->get($pem->id);
            $pred = $val ? $val->predikat : 'Baik';
            $rowPem->addCell(1600)->addText($pred, 'tableBody', ['alignment' => Jc::CENTER]);

            if ($pIdx === 0) {
                // Cell kehadiran span semua baris pembiasaan
                $cellAtt = $rowPem->addCell(1800, ['vMerge' => 'restart']);
                foreach ($kehadiranLines as $line) {
                    $cellAtt->addText($line, 'tableBody', ['alignment' => Jc::CENTER]);
                }
            } else {
                $rowPem->addCell(1800, ['vMerge' => 'continue']);
            }
        }

        $section->addTextBreak(1);

        // 6. TANDA TANGAN (3 KOLOM: ORANG TUA, WALI KELAS, KEPALA SEKOLAH)
        $ttdTable = $section->addTable(['alignment' => JcTable::CENTER]);
        $ttdRow1 = $ttdTable->addRow();
        $ttdRow1->addCell(3000)->addText('Mengetahui,', 'tableBody', ['alignment' => Jc::CENTER]);
        $ttdRow1->addCell(3000)->addText('', 'tableBody');
        $kota = $sekolah->kota ?? 'Banyuwangi';
        $tanggalObj = $tanggal ? Carbon::parse($tanggal)->locale('id') : Carbon::now()->locale('id');
        $tanggalSekarang = $tanggalObj->translatedFormat('d F Y');
        $ttdRow1->addCell(3000)->addText("{$kota}, {$tanggalSekarang}", 'tableBody', ['alignment' => Jc::CENTER]);

        $ttdRow2 = $ttdTable->addRow();
        $ttdRow2->addCell(3000)->addText('Orang Tua / Wali Murid', 'tableBody', ['alignment' => Jc::CENTER]);
        $ttdRow2->addCell(3000)->addText('Kepala Sekolah', 'tableBody', ['alignment' => Jc::CENTER]);
        $ttdRow2->addCell(3000)->addText('Wali Kelas', 'tableBody', ['alignment' => Jc::CENTER]);

        // Spasi TTD
        $ttdRow3 = $ttdTable->addRow(900);
        $ttdRow3->addCell(3000)->addTextBreak(3);
        $cellKepsekTtd = $ttdRow3->addCell(3000);
        if ($ttdPath) {
            $cellKepsekTtd->addImage($ttdPath, [
                'width' => 90,
                'height' => 45,
                'alignment' => Jc::CENTER,
            ]);
        } else {
            $cellKepsekTtd->addTextBreak(3);
        }
        $ttdRow3->addCell(3000)->addTextBreak(3);

        // Nama & NIP
        $ttdRow4 = $ttdTable->addRow();
        $ttdRow4->addCell(3000)->addText('( ....................................... )', 'boldText', ['alignment' => Jc::CENTER]);
        $ttdRow4->addCell(3000)->addText($sekolah->kepsek ?? '( ................................ )', 'boldText', ['alignment' => Jc::CENTER]);
        $ttdRow4->addCell(3000)->addText($kelas->nama_wali, 'boldText', ['alignment' => Jc::CENTER]);

        $ttdRow5 = $ttdTable->addRow();
        $ttdRow5->addCell(3000)->addText('', 'tableBody');
        $nipKepsek = $sekolah->nip ? "NIP. {$sekolah->nip}" : '-';
        $ttdRow5->addCell(3000)->addText($nipKepsek, 'tableBody', ['alignment' => Jc::CENTER]);
        $nipWali = $kelas->nip_wali !== '-' ? "NIP. {$kelas->nip_wali}" : 'NIP. -';
        $ttdRow5->addCell(3000)->addText($nipWali, 'tableBody', ['alignment' => Jc::CENTER]);
    }
}
