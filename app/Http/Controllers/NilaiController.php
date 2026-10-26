<?php

namespace App\Http\Controllers;

use App\Models\Kehadiran;
use App\Models\KehadiranBulanan;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\NilaiPembiasaan;
use App\Models\Pembiasaan;
use App\Services\ExcelReader;
use App\Services\ExcelWriter;
use App\Services\HitungNilaiService;
use Illuminate\Http\Request;

class NilaiController extends Controller
{
    protected HitungNilaiService $hitungService;

    public function __construct(HitungNilaiService $hitungService)
    {
        $this->hitungService = $hitungService;
    }

    /**
     * Tampilan input nilai per siswa dalam kelas
     */
    public function inputSiswa(Kelas $kelas, KelasSiswa $kelasSiswa, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapels = $kelas->mataPelajaran();
        $pembiasaans = Pembiasaan::aktif()->get();

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $kelasSiswa->load('siswa');

        $nilaiList = Nilai::where('kelas_siswa_id', $kelasSiswa->id)
            ->where('periode', $periode)
            ->get()
            ->keyBy('mapel_id');

        $pembiasaanList = NilaiPembiasaan::where('kelas_siswa_id', $kelasSiswa->id)
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

        $hasilPerhitungan = $this->hitungService->hitungNilaiSiswa($kelasSiswa, $periode);

        // Siswa berikutnya dan sebelumnya untuk navigasi cepat
        $allSiswaIds = $kelas->kelasSiswa()->orderBy('id')->pluck('id')->toArray();
        $currentIndex = array_search($kelasSiswa->id, $allSiswaIds);
        $prevId = $currentIndex > 0 ? $allSiswaIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allSiswaIds) - 1 ? $allSiswaIds[$currentIndex + 1] : null;

        return view('nilai.input_siswa', compact(
            'kelas',
            'kelasSiswa',
            'periode',
            'mapels',
            'pembiasaans',
            'nilaiList',
            'pembiasaanList',
            'kehadiran',
            'hasilPerhitungan',
            'prevId',
            'nextId'
        ));
    }

    /**
     * Simpan input nilai per siswa
     */
    public function simpanSiswa(Request $request, Kelas $kelas, KelasSiswa $kelasSiswa)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');

        // 1. Simpan Nilai Mapel
        $formatifData = $request->input('formatif', []);
        $sumatifData = $request->input('sumatif', []);

        $mapels = $kelas->mataPelajaran();

        foreach ($mapels as $mapel) {
            $sumatif = isset($sumatifData[$mapel->id]) && $sumatifData[$mapel->id] !== '' ? (float) $sumatifData[$mapel->id] : null;
            $formatif = isset($formatifData[$mapel->id]) && $formatifData[$mapel->id] !== '' ? (float) $formatifData[$mapel->id] : null;

            if ($sumatif === null && $formatif !== null) {
                $sumatif = $formatif;
            }

            if ($sumatif !== null) {
                $sumatif = max(0, min(100, $sumatif));
            }

            Nilai::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'mapel_id' => $mapel->id,
                'periode' => $periode,
            ], [
                'formatif' => null,
                'sumatif' => $sumatif,
            ]);
        }

        // 2. Simpan Pembiasaan
        $predikatData = $request->input('predikat', []);
        $pembiasaans = Pembiasaan::aktif()->get();

        foreach ($pembiasaans as $pem) {
            $predikat = $predikatData[$pem->id] ?? 'Baik';
            NilaiPembiasaan::updateOrCreate([
                'kelas_siswa_id' => $kelasSiswa->id,
                'pembiasaan_id' => $pem->id,
                'periode' => $periode,
            ], [
                'predikat' => $predikat,
            ]);
        }

        // 3. Simpan Kehadiran
        Kehadiran::updateOrCreate([
            'kelas_siswa_id' => $kelasSiswa->id,
            'periode' => $periode,
        ], [
            'sakit' => (int) $request->input('sakit', 0),
            'izin' => (int) $request->input('izin', 0),
            'alpa' => (int) $request->input('alpa', 0),
        ]);

        $nextSiswaId = $request->input('next_siswa_id');
        if ($nextSiswaId) {
            return redirect()->route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $nextSiswaId, 'periode' => $periode])
                ->with('success', "Nilai {$kelasSiswa->siswa->nama} berhasil disimpan. Melanjutkan ke siswa berikutnya.");
        }

        return redirect()->back()->with('success', "Nilai {$kelasSiswa->siswa->nama} berhasil disimpan.");
    }

    /**
     * Tampilan input matriks cepat satu kelas
     */
    public function inputKelas(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');

        $mapels = $kelas->mataPelajaran();
        if (! $mapelId && $mapels->isNotEmpty()) {
            $mapelId = $mapels->first()->id;
        }

        $activeMapel = MataPelajaran::find($mapelId);
        $kelas->load('mapelKkm');
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();

        $nilaiList = Nilai::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('mapel_id', $mapelId)
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        return view('nilai.input_kelas', compact(
            'kelas',
            'periode',
            'mapels',
            'activeMapel',
            'kelasSiswaList',
            'nilaiList'
        ));
    }

    /**
     * Simpan input matriks satu kelas
     */
    public function simpanKelas(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');
        $formatifData = $request->input('formatif', []);
        $sumatifData = $request->input('sumatif', []);

        foreach ($kelas->kelasSiswa as $ks) {
            $sumatif = isset($sumatifData[$ks->id]) && $sumatifData[$ks->id] !== '' ? (float) $sumatifData[$ks->id] : null;
            $formatif = isset($formatifData[$ks->id]) && $formatifData[$ks->id] !== '' ? (float) $formatifData[$ks->id] : null;

            if ($sumatif === null && $formatif !== null) {
                $sumatif = $formatif;
            }

            if ($sumatif !== null) {
                $sumatif = max(0, min(100, $sumatif));
            }

            Nilai::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'mapel_id' => $mapelId,
                'periode' => $periode,
            ], [
                'formatif' => null,
                'sumatif' => $sumatif,
            ]);
        }

        return redirect()->back()->with('success', 'Nilai kelas untuk mata pelajaran ini berhasil disimpan.');
    }

    /**
     * Unduh template file Excel (.xlsx atau CSV) untuk input nilai satu rombel kelas.
     * Mode: 'semua' (semua mata pelajaran dalam satu sheet) atau 'mapel' (satu mapel terpilih).
     */
    public function downloadTemplateExcel(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = strtolower($request->query('periode', 'sts'));
        if (! in_array($periode, ['sts', 'sas'])) {
            $periode = 'sts';
        }

        $mode = $request->query('mode', 'semua');
        $mapelId = $request->query('mapel_id');
        $format = strtolower($request->query('format', 'xlsx'));

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $mapels = $kelas->mataPelajaran();
        $namaFileLabel = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $kelas->nama));

        // Ambil nilai yang sudah ada di database untuk mengisi template
        $nilaiList = Nilai::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('periode', $periode)
            ->get();

        if ($mode === 'mapel' && $mapelId) {
            $targetMapel = MataPelajaran::find($mapelId);
            $namaMapelLabel = $targetMapel ? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $targetMapel->nama)) : 'mapel';
            $mapelHeaderName = $targetMapel ? $targetMapel->nama : 'Nilai';

            $headers = ['No', 'NIS', 'Nama Siswa', $mapelHeaderName];
            $nilaiMapel = $nilaiList->where('mapel_id', $mapelId)->keyBy('kelas_siswa_id');

            $dataRows = [];
            foreach ($kelasSiswaList as $idx => $ks) {
                $n = $nilaiMapel->get($ks->id);
                $val = $n && $n->sumatif !== null ? (string) $n->sumatif : ($n && $n->formatif !== null ? (string) $n->formatif : '');
                $dataRows[] = [
                    $idx + 1,
                    $ks->siswa->nis ?? '',
                    $ks->siswa->nama,
                    $val,
                ];
            }

            if ($format === 'csv') {
                $filename = "template_nilai_{$namaFileLabel}_{$namaMapelLabel}_".strtoupper($periode).'.csv';
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

            $filename = "template_nilai_{$namaFileLabel}_{$namaMapelLabel}_".strtoupper($periode).'.xlsx';

            return ExcelWriter::downloadResponse($headers, $dataRows, $filename, $targetMapel ? $targetMapel->nama : 'Template Nilai');
        }

        // Mode Semua Mapel (Sekaligus 1 Kelas Penuh)
        $headers = ['No', 'NIS', 'Nama Siswa'];
        foreach ($mapels as $m) {
            $headers[] = $m->nama;
        }

        $nilaiGroup = $nilaiList->groupBy('kelas_siswa_id');
        $dataRows = [];

        foreach ($kelasSiswaList as $idx => $ks) {
            $row = [
                $idx + 1,
                $ks->siswa->nis ?? '',
                $ks->siswa->nama,
            ];

            $studentScores = $nilaiGroup->get($ks->id, collect())->keyBy('mapel_id');
            foreach ($mapels as $m) {
                $n = $studentScores->get($m->id);
                $val = $n && $n->sumatif !== null ? (string) $n->sumatif : ($n && $n->formatif !== null ? (string) $n->formatif : '');
                $row[] = $val;
            }

            $dataRows[] = $row;
        }

        if ($format === 'csv') {
            $filename = "template_nilai_lengkap_{$namaFileLabel}_".strtoupper($periode).'.csv';
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

        $filename = "template_nilai_lengkap_{$namaFileLabel}_".strtoupper($periode).'.xlsx';

        return ExcelWriter::downloadResponse($headers, $dataRows, $filename, 'Nilai Kelas');
    }

    /**
     * Impor nilai dari file Excel (.xlsx, .csv, .txt), PDF (.pdf), atau clipboard.
     */
    public function importExcel(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $request->validate([
            'periode' => 'required|in:sts,sas',
            'file_excel' => 'nullable|file|mimes:xlsx,csv,txt,pdf|max:10240',
            'data_teks' => 'nullable|string',
            'mapel_id' => 'nullable|exists:mata_pelajaran,id',
        ]);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');

        $rows = [];
        if ($request->hasFile('file_excel')) {
            $file = $request->file('file_excel');
            $rows = ExcelReader::parseFile($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif (! empty($request->input('data_teks'))) {
            $rows = ExcelReader::parseClipboard($request->input('data_teks'));
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Tidak ada data nilai atau kehadiran yang terdeteksi dari file atau teks yang diunggah.');
        }

        $hasil = $this->prosesImporNilai($kelas, $rows, $periode, $mapelId);

        if ($hasil['sukses'] === 0) {
            return redirect()->back()->with('error', 'Gagal mengimpor nilai: '.$hasil['pesan']);
        }

        $msgParts = [];
        if (! empty($hasil['mapel_count'])) {
            $msgParts[] = "{$hasil['mapel_count']} mata pelajaran";
        }
        if (! empty($hasil['sia_count'])) {
            $msgParts[] = "kehadiran (SIA) {$hasil['sia_count']} siswa";
        }
        if (! empty($hasil['pembiasaan_count'])) {
            $msgParts[] = "{$hasil['pembiasaan_count']} aspek pembiasaan";
        }
        $kkmMsg = ! empty($hasil['kkm_count']) ? " serta menyinkronkan KKM untuk {$hasil['kkm_count']} mapel" : '';
        $msgScope = ! empty($msgParts) ? ' pada '.implode(', ', $msgParts) : '';

        return redirect()->back()->with('success', "Berhasil mengimpor data untuk {$hasil['sukses']} siswa{$msgScope}{$kkmMsg} (Periode ".strtoupper($periode).').');
    }

    /**
     * Proses penguraian baris data Excel ke record Nilai siswa.
     */
    protected function prosesImporNilai(Kelas $kelas, array $rows, string $periode, ?int $targetMapelId = null): array
    {
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        if ($kelasSiswaList->isEmpty()) {
            return ['sukses' => 0, 'pesan' => 'Rombel kelas ini belum memiliki siswa.'];
        }

        $siswaByNis = [];
        $siswaByNisn = [];
        $siswaByNama = [];
        $siswaByNormalizedNama = [];
        $siswaByNoUrut = [];

        foreach ($kelasSiswaList as $idx => $ks) {
            $cleanNis = ExcelReader::cleanNumberString((string) $ks->siswa->nis);
            if ($cleanNis !== '') {
                $siswaByNis[$cleanNis] = $ks;
            }

            $cleanNisn = ExcelReader::cleanNumberString((string) $ks->siswa->nisn);
            if ($cleanNisn !== '') {
                $siswaByNisn[$cleanNisn] = $ks;
            }

            $cleanNama = strtolower(trim(preg_replace('/\s+/', ' ', $ks->siswa->nama)));
            $siswaByNama[$cleanNama] = $ks;

            $alphaOnly = preg_replace('/[^a-z0-9]/', '', $cleanNama);
            if ($alphaOnly !== '') {
                $siswaByNormalizedNama[$alphaOnly] = $ks;
            }

            $siswaByNoUrut[$idx + 1] = $ks;
        }

        $mapels = $kelas->mataPelajaran();
        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();

        // 1. Deteksi Baris Header (Maksimal 15 baris pertama)
        $headerRowIdx = null;
        $colNo = null;
        $colNis = null;
        $colNama = null;
        $colSakit = null;
        $colIzin = null;
        $colAlpa = null;
        $colMapelMap = []; // [columnIndex => mapelId]
        $colPembiasaanMap = []; // [columnIndex => pembiasaanId]

        $sakitSynonyms = ['sakit', 's', 's (sakit)', 'jml sakit', 'jumlah sakit', 'skt'];
        $izinSynonyms = ['izin', 'ijin', 'i', 'i (izin)', 'i (ijin)', 'jml izin', 'jumlah izin', 'izn'];
        $alpaSynonyms = ['alpa', 'alpha', 'a', 'a (alpa)', 'a (alpha)', 'tanpa keterangan', 'tk', 'jml alpa', 'jumlah alpa', 'alp'];

        $searchLimit = min(15, count($rows));
        for ($r = 0; $r < $searchLimit; $r++) {
            $row = $rows[$r];
            $tempNo = null;
            $tempNis = null;
            $tempNama = null;
            $tempSakit = null;
            $tempIzin = null;
            $tempAlpa = null;
            $tempMapel = [];
            $tempPem = [];

            foreach ($row as $cIdx => $cell) {
                $cellVal = strtolower(trim((string) $cell));
                $cellVal = preg_replace('/\s+/', ' ', $cellVal);

                if (in_array($cellVal, ['no', 'no.', 'nomor', 'no urut', 'no. urut', 'absen', 'no absen', 'nomor absen'])) {
                    $tempNo = $cIdx;
                } elseif (in_array($cellVal, ['nis', 'nisn', 'nipd', 'no induk', 'nomor induk', 'nis siswa', 'nisn siswa', 'nis / nisn', 'nis/nisn', 'no. induk', 'no_induk'])) {
                    $tempNis = $cIdx;
                } elseif (in_array($cellVal, ['nama', 'nama siswa', 'nama lengkap', 'nama peserta didik', 'nama murid', 'pd', 'peserta didik', 'siswa'])) {
                    $tempNama = $cIdx;
                } elseif (in_array($cellVal, $sakitSynonyms)) {
                    $tempSakit = $cIdx;
                } elseif (in_array($cellVal, $izinSynonyms)) {
                    $tempIzin = $cIdx;
                } elseif (in_array($cellVal, $alpaSynonyms)) {
                    $tempAlpa = $cIdx;
                } else {
                    $mId = $this->resolveMapelFromHeader((string) $cell, $mapels, $targetMapelId);
                    if ($mId) {
                        $tempMapel[$cIdx] = $mId;
                    } else {
                        $pId = $this->resolvePembiasaanFromHeader((string) $cell, $pembiasaans);
                        if ($pId) {
                            $tempPem[$cIdx] = $pId;
                        }
                    }
                }
            }

            if (($tempNis !== null || $tempNama !== null) && (! empty($tempMapel) || $targetMapelId !== null || $tempSakit !== null || $tempIzin !== null || $tempAlpa !== null || ! empty($tempPem))) {
                $headerRowIdx = $r;
                $colNo = $tempNo;
                $colNis = $tempNis;
                $colNama = $tempNama;
                $colSakit = $tempSakit;
                $colIzin = $tempIzin;
                $colAlpa = $tempAlpa;
                $colMapelMap = $tempMapel;
                $colPembiasaanMap = $tempPem;
                break;
            }
        }

        // Jika single mapel tapi kolom mapel belum terpetakan, petakan kolom selain No/NIS/Nama
        if ($targetMapelId && empty($colMapelMap)) {
            $dataSample = $rows[$headerRowIdx !== null ? $headerRowIdx + 1 : 0] ?? [];
            foreach ($dataSample as $cIdx => $val) {
                if ($cIdx !== $colNo && $cIdx !== $colNis && $cIdx !== $colNama && $cIdx !== $colSakit && $cIdx !== $colIzin && $cIdx !== $colAlpa && ! isset($colPembiasaanMap[$cIdx])) {
                    $colMapelMap[$cIdx] = $targetMapelId;
                    break;
                }
            }
        }

        if (empty($colMapelMap) && $colSakit === null && $colIzin === null && $colAlpa === null && empty($colPembiasaanMap)) {
            return [
                'sukses' => 0,
                'pesan' => 'Kolom mata pelajaran, kehadiran (SIA), maupun aspek pembiasaan tidak terdeteksi. Pastikan baris header memuat label yang sesuai atau unduh template Excel resmi.',
            ];
        }

        $dataRows = $headerRowIdx !== null ? array_slice($rows, $headerRowIdx + 1) : $rows;

        $siswaTerupdate = [];
        $mapelTerupdate = [];
        $kkmTerupdate = [];
        $siaUpdatedCount = 0;
        $pembiasaanTerupdate = [];

        foreach ($dataRows as $row) {
            $noVal = $colNo !== null ? (int) ExcelReader::cleanNumberString(trim((string) ($row[$colNo] ?? ''))) : null;
            $nisVal = $colNis !== null ? ExcelReader::cleanNumberString(trim((string) ($row[$colNis] ?? ''))) : '';
            $namaVal = $colNama !== null ? strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row[$colNama] ?? '')))) : '';

            if ($namaVal === '' && $nisVal === '' && ! $noVal) {
                continue;
            }

            // Deteksi baris KKM pada spreadsheet (biasanya Nama atau No/NIS berisi 'KKM')
            $isKkmRow = ($namaVal === 'kkm' || ($colNo !== null && strtolower(trim((string) ($row[$colNo] ?? ''))) === 'kkm') || ($colNis !== null && strtolower(trim((string) ($row[$colNis] ?? ''))) === 'kkm'));
            if ($isKkmRow) {
                foreach ($colMapelMap as $cIdx => $mId) {
                    $kkmRaw = trim((string) ($row[$cIdx] ?? ''));
                    $kkmRaw = str_replace(',', '.', $kkmRaw);
                    if (is_numeric($kkmRaw)) {
                        $kkmInt = (int) $kkmRaw;
                        if ($kkmInt > 0 && $kkmInt <= 100) {
                            $kelas->mapelKkm()->updateOrCreate(
                                ['mapel_id' => $mId],
                                ['kkm' => $kkmInt]
                            );
                            $kkmTerupdate[$mId] = $kkmInt;
                        }
                    }
                }

                continue; // Jangan proses baris KKM sebagai siswa
            }

            // Cari siswa yang cocok secara berjenjang
            $matchedKs = null;

            // 1. Berdasarkan NIS
            if ($nisVal !== '' && isset($siswaByNis[$nisVal])) {
                $matchedKs = $siswaByNis[$nisVal];
            }
            // 2. Berdasarkan NISN
            elseif ($nisVal !== '' && isset($siswaByNisn[$nisVal])) {
                $matchedKs = $siswaByNisn[$nisVal];
            }
            // 3. Berdasarkan Nama Lengkap (persis)
            elseif ($namaVal !== '' && isset($siswaByNama[$namaVal])) {
                $matchedKs = $siswaByNama[$namaVal];
            }
            // 4. Berdasarkan Nama Alfanumerik (abaikan titik, koma, spasi ganda)
            elseif ($namaVal !== '') {
                $alphaVal = preg_replace('/[^a-z0-9]/', '', $namaVal);
                if ($alphaVal !== '' && isset($siswaByNormalizedNama[$alphaVal])) {
                    $matchedKs = $siswaByNormalizedNama[$alphaVal];
                }
            }

            // 5. Pencarian fallback kecocokan nama parsial (contains)
            if (! $matchedKs && $namaVal !== '') {
                foreach ($siswaByNama as $nKey => $ks) {
                    if (str_contains($nKey, $namaVal) || str_contains($namaVal, $nKey)) {
                        $matchedKs = $ks;
                        break;
                    }
                }
            }

            // 6. Pencarian kemiripan nama berbasis kemiripan fonetik / typo (similar_text >= 80%)
            if (! $matchedKs && $namaVal !== '') {
                $bestPct = 0;
                $bestKs = null;
                foreach ($kelasSiswaList as $ks) {
                    $dbNama = strtolower(trim(preg_replace('/\s+/', ' ', $ks->siswa->nama)));
                    similar_text($namaVal, $dbNama, $pct);
                    if ($pct > $bestPct) {
                        $bestPct = $pct;
                        $bestKs = $ks;
                    }
                }
                if ($bestPct >= 80 && $bestKs) {
                    $matchedKs = $bestKs;
                }
            }

            // 7. Pencarian fallback berdasarkan Nomor Urut Absen Kelas (jika NIS kosong & nama tidak cocok)
            if (! $matchedKs && $noVal && isset($siswaByNoUrut[$noVal])) {
                $matchedKs = $siswaByNoUrut[$noVal];
            }

            if (! $matchedKs) {
                continue;
            }

            // Simpan nilai untuk tiap mapel yang terdeteksi
            foreach ($colMapelMap as $cIdx => $mId) {
                $cellVal = trim((string) ($row[$cIdx] ?? ''));
                if ($cellVal === '' || $cellVal === '-') {
                    continue;
                }

                $cellVal = str_replace(',', '.', $cellVal);
                if (is_numeric($cellVal)) {
                    $score = (float) $cellVal;
                    $score = max(0, min(100, $score));

                    Nilai::updateOrCreate([
                        'kelas_siswa_id' => $matchedKs->id,
                        'mapel_id' => $mId,
                        'periode' => $periode,
                    ], [
                        'formatif' => null,
                        'sumatif' => $score,
                    ]);

                    $siswaTerupdate[$matchedKs->id] = true;
                    $mapelTerupdate[$mId] = true;
                }
            }

            // Simpan data Kehadiran (SIA) jika kolomnya terdeteksi di baris
            if ($colSakit !== null || $colIzin !== null || $colAlpa !== null) {
                $sakitVal = $colSakit !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colSakit] ?? '0')))) : 0;
                $izinVal = $colIzin !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colIzin] ?? '0')))) : 0;
                $alpaVal = $colAlpa !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colAlpa] ?? '0')))) : 0;

                Kehadiran::updateOrCreate([
                    'kelas_siswa_id' => $matchedKs->id,
                    'periode' => $periode,
                ], [
                    'sakit' => $sakitVal,
                    'izin' => $izinVal,
                    'alpa' => $alpaVal,
                ]);

                $siaUpdatedCount++;
                $siswaTerupdate[$matchedKs->id] = true;
            }

            // Simpan Nilai Pembiasaan jika kolomnya terdeteksi di baris
            foreach ($colPembiasaanMap as $cIdx => $pId) {
                $rawPred = trim((string) ($row[$cIdx] ?? ''));
                if ($rawPred === '' || $rawPred === '-') {
                    continue;
                }

                $pred = $this->normalizePredikat($rawPred);

                NilaiPembiasaan::updateOrCreate([
                    'kelas_siswa_id' => $matchedKs->id,
                    'pembiasaan_id' => $pId,
                    'periode' => $periode,
                ], [
                    'predikat' => $pred,
                ]);

                $pembiasaanTerupdate[$pId] = true;
                $siswaTerupdate[$matchedKs->id] = true;
            }
        }

        return [
            'sukses' => count($siswaTerupdate),
            'mapel_count' => count($mapelTerupdate),
            'kkm_count' => count($kkmTerupdate),
            'sia_count' => $siaUpdatedCount,
            'pembiasaan_count' => count($pembiasaanTerupdate),
            'pesan' => 'OK',
        ];
    }

    /**
     * Cocokkan teks header kolom spreadsheet dengan mata pelajaran terdaftar.
     */
    protected function resolveMapelFromHeader(string $rawHeader, $mapels, ?int $targetMapelId = null): ?int
    {
        $val = strtolower(trim($rawHeader));
        $val = preg_replace('/\s+/', ' ', $val);

        if (empty($val) || in_array($val, ['no', 'no.', 'nomor', 'nis', 'nisn', 'nipd', 'no induk', 'nomor induk', 'nama', 'nama siswa', 'nama lengkap', 'jumlah', 'total', 'rata', 'rata2', 'rata-rata', 'ranking', 'rank', 'keterangan', 'ket', 'status', 'kkm', 'sakit', 'izin', 'ijin', 'alpa', 'alpha', 's', 'i', 'a'])) {
            return null;
        }

        if ($targetMapelId && in_array($val, ['nilai', 'nilai siswa', 'sumatif', 'nilai akhir', 'angka', 'nilai (0-100)'])) {
            return $targetMapelId;
        }

        // Hapus keterangan dalam kurung seperti (0-100), (kkm: 70), (nilai)
        $cleanVal = trim(preg_replace('/\s*\([^)]*\)/', '', $val));
        $cleanVal = trim(preg_replace('/^nilai\s+/i', '', $cleanVal));

        if ($targetMapelId && in_array($cleanVal, ['nilai', 'nilai siswa', 'sumatif', 'nilai akhir', 'angka'])) {
            return $targetMapelId;
        }

        // 1. Direct match dengan nama mapel (persis atau setelah dibersihkan kurungnya)
        foreach ($mapels as $m) {
            $mNama = strtolower(trim($m->nama));
            if ($val === $mNama || $cleanVal === $mNama) {
                return $m->id;
            }
        }

        // 2. Normalisasi awalan 'b.' / 'b ' -> 'bahasa '
        $normalizedVal = preg_replace('/^b[\.\s]+/i', 'bahasa ', $val);
        $normalizedVal = preg_replace('/\s+/', ' ', $normalizedVal);

        foreach ($mapels as $m) {
            $mNama = strtolower(trim($m->nama));
            if ($normalizedVal === $mNama) {
                return $m->id;
            }
        }

        // 3. Singkatan standar SD di Indonesia
        $aliasMap = [
            'pai' => 'agama',
            'p.a.i' => 'agama',
            'paibp' => 'agama',
            'pabp' => 'agama',
            'agama' => 'agama',
            'pendidikan agama' => 'agama',
            'pend. agama' => 'agama',
            'p.agama' => 'agama',
            'pp' => 'pancasila',
            'p.p' => 'pancasila',
            'ppkn' => 'pancasila',
            'pkn' => 'pancasila',
            'pancasila' => 'pancasila',
            'pendidikan pancasila' => 'pancasila',
            'b. indonesia' => 'indonesia',
            'b.indonesia' => 'indonesia',
            'b indonesia' => 'indonesia',
            'bindonesia' => 'indonesia',
            'b. indo' => 'indonesia',
            'b indo' => 'indonesia',
            'bindo' => 'indonesia',
            'bind' => 'indonesia',
            'bin' => 'indonesia',
            'indonesia' => 'indonesia',
            'bahasa indonesia' => 'indonesia',
            'matematika' => 'matematika',
            'mtk' => 'matematika',
            'm.t.k' => 'matematika',
            'math' => 'matematika',
            'mat' => 'matematika',
            'ipas' => 'ipas',
            'i.p.a.s' => 'ipas',
            'ipa' => 'ipas',
            'ips' => 'ipas',
            'sbk' => 'seni',
            's.b.k' => 'seni',
            'sbdp' => 'seni',
            's.b.d.p' => 'seni',
            'seni' => 'seni',
            'seni budaya' => 'seni',
            'pjok' => 'pjok',
            'p.j.o.k' => 'pjok',
            'penjas' => 'jasmani',
            'penjasorkes' => 'jasmani',
            'olahraga' => 'jasmani',
            'b. jawa' => 'jawa',
            'b.jawa' => 'jawa',
            'b jawa' => 'jawa',
            'bjawa' => 'jawa',
            'b. jw' => 'jawa',
            'b jw' => 'jawa',
            'bjw' => 'jawa',
            'jawa' => 'jawa',
            'bahasa jawa' => 'jawa',
            'b. inggris' => 'inggris',
            'b.inggris' => 'inggris',
            'b inggris' => 'inggris',
            'binggris' => 'inggris',
            'b. ing' => 'inggris',
            'b ing' => 'inggris',
            'bing' => 'inggris',
            'inggris' => 'inggris',
            'english' => 'inggris',
            'bahasa inggris' => 'inggris',
            'b. using' => 'using',
            'b.using' => 'using',
            'b using' => 'using',
            'busing' => 'using',
            'b. us' => 'using',
            'b us' => 'using',
            'bus' => 'using',
            'using' => 'using',
            'osing' => 'using',
            'b. osing' => 'using',
            'b.osing' => 'using',
            'b osing' => 'using',
            'bosing' => 'using',
            'bahasa using' => 'using',
            'bahasa osing' => 'using',
        ];

        if (isset($aliasMap[$val])) {
            $kw = $aliasMap[$val];
            foreach ($mapels as $m) {
                if (str_contains(strtolower($m->nama), $kw)) {
                    return $m->id;
                }
            }
        }

        // 4. Substring dan akronim dalam kurung nama mapel
        foreach ($mapels as $m) {
            $mNama = strtolower(trim($m->nama));
            if (preg_match('/\((.*?)\)/', $mNama, $matches)) {
                $acronym = strtolower(trim($matches[1]));
                if ($val === $acronym || str_contains($val, $acronym)) {
                    return $m->id;
                }
            }
            if (str_contains($val, $mNama) || str_contains($mNama, $val)) {
                return $m->id;
            }
            if (str_contains($normalizedVal, $mNama) || str_contains($mNama, $normalizedVal)) {
                return $m->id;
            }
        }

        return null;
    }

    /**
     * Cocokkan teks header kolom dengan aspek pembiasaan.
     */
    protected function resolvePembiasaanFromHeader(string $rawHeader, $pembiasaans): ?int
    {
        $val = strtolower(trim($rawHeader));
        $val = preg_replace('/\s+/', ' ', $val);
        $cleanVal = trim(preg_replace('/\s*\([^)]*\)/', '', $val));
        $cleanVal = trim(preg_replace('/^pembiasaan\s+/i', '', $cleanVal));

        if (empty($val) || in_array($val, ['no', 'no.', 'nomor', 'nis', 'nisn', 'nipd', 'nama', 'nama siswa', 'sakit', 'izin', 'ijin', 'alpa', 'alpha', 's', 'i', 'a'])) {
            return null;
        }

        foreach ($pembiasaans as $p) {
            $pNama = strtolower(trim($p->nama));
            if ($val === $pNama || $cleanVal === $pNama) {
                return $p->id;
            }

            // Keyword matching
            $keywords = [
                'iman' => 'keimanan',
                'takwa' => 'ketakwaan',
                'warga' => 'kewargaan',
                'kritis' => 'penalaran kritis',
                'kreatif' => 'kreativitas',
                'gotong' => 'kolaborasi',
                'kolaborasi' => 'kolaborasi',
                'mandiri' => 'kemandirian',
                'sehat' => 'kesehatan',
                'komunikasi' => 'komunikasi',
            ];

            foreach ($keywords as $kw => $targetSubstr) {
                if (str_contains($cleanVal, $kw) && str_contains($pNama, $targetSubstr)) {
                    return $p->id;
                }
            }

            if (str_contains($pNama, $cleanVal) || str_contains($cleanVal, $pNama)) {
                return $p->id;
            }
        }

        return null;
    }

    /**
     * Normalisasi teks predikat nilai pembiasaan ke nilai enum yang valid.
     */
    protected function normalizePredikat(string $rawVal): string
    {
        $v = strtolower(trim($rawVal));

        if (in_array($v, ['sangat baik', 'sb', '4', 'a'])) {
            return 'Sangat Baik';
        }
        if (in_array($v, ['cukup', 'c', '2', 'sedang'])) {
            return 'Cukup';
        }
        if (in_array($v, ['perlu bimbingan', 'pb', 'kurang', 'k', '1', 'd', 'bimbingan'])) {
            return 'Perlu Bimbingan';
        }

        return 'Baik';
    }

    /**
     * Tampilan matriks input kehadiran (SIA) dan karakter pembiasaan untuk satu rombel kelas.
     */
    public function inputSiaPembiasaan(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        if (! in_array($periode, ['sts', 'sas'])) {
            $periode = 'sts';
        }

        $kelas->load(['tahunAjaran', 'wali', 'sekolah']);

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();

        $kehadiranList = Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        $nilaiPemGroup = NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('periode', $periode)
            ->get()
            ->groupBy('kelas_siswa_id');

        return view('nilai.input_sia_pembiasaan', compact(
            'kelas',
            'kelasSiswaList',
            'pembiasaans',
            'kehadiranList',
            'nilaiPemGroup',
            'periode'
        ));
    }

    /**
     * Simpan matriks input kehadiran (SIA) dan karakter pembiasaan untuk satu rombel kelas.
     */
    public function simpanSiaPembiasaan(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $request->validate([
            'periode' => 'required|in:sts,sas',
            'sakit' => 'nullable|array',
            'izin' => 'nullable|array',
            'alpa' => 'nullable|array',
            'predikat' => 'nullable|array',
        ]);

        $periode = $request->input('periode', 'sts');
        $sakitData = $request->input('sakit', []);
        $izinData = $request->input('izin', []);
        $alpaData = $request->input('alpa', []);
        $predikatData = $request->input('predikat', []);

        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();
        $validPredikats = ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Bimbingan'];

        $updatedCount = 0;

        foreach ($kelas->kelasSiswa as $ks) {
            $s = max(0, (int) ($sakitData[$ks->id] ?? 0));
            $i = max(0, (int) ($izinData[$ks->id] ?? 0));
            $a = max(0, (int) ($alpaData[$ks->id] ?? 0));

            Kehadiran::updateOrCreate([
                'kelas_siswa_id' => $ks->id,
                'periode' => $periode,
            ], [
                'sakit' => $s,
                'izin' => $i,
                'alpa' => $a,
            ]);

            foreach ($pembiasaans as $p) {
                $pred = $predikatData[$ks->id][$p->id] ?? 'Baik';
                if (! in_array($pred, $validPredikats)) {
                    $pred = $this->normalizePredikat($pred);
                }

                NilaiPembiasaan::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'pembiasaan_id' => $p->id,
                    'periode' => $periode,
                ], [
                    'predikat' => $pred,
                ]);
            }

            $updatedCount++;
        }

        return redirect()->back()->with('success', "Berhasil menyimpan data kehadiran (SIA) dan nilai pembiasaan untuk {$updatedCount} siswa (Periode ".strtoupper($periode).').');
    }

    /**
     * Tarik akumulasi data absensi bulanan ke nilai kehadiran (SIA) rapor semester ini.
     */
    public function tarikDariAbsensiBulanan(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = $request->input('periode', 'sts');
        $tahun = (int) ($request->input('tahun', now()->year));
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        if ($semester === 'genap') {
            $bulanList = ($periode === 'sts') ? [1, 2, 3] : [1, 2, 3, 4, 5, 6];
        } else {
            $bulanList = ($periode === 'sts') ? [7, 8, 9] : [7, 8, 9, 10, 11, 12];
        }

        $syncCount = 0;
        foreach ($kelas->kelasSiswa as $ks) {
            $records = KehadiranBulanan::where('kelas_siswa_id', $ks->id)
                ->where('tahun', $tahun)
                ->whereIn('bulan', $bulanList)
                ->get();

            if ($records->isNotEmpty()) {
                Kehadiran::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'periode' => $periode,
                ], [
                    'sakit' => $records->sum('sakit'),
                    'izin' => $records->sum('izin'),
                    'alpa' => $records->sum('alpa'),
                ]);
                $syncCount++;
            }
        }

        if ($syncCount === 0) {
            return redirect()->back()->with('warning', "Belum ada catatan absensi bulanan pada tahun {$tahun} untuk periode ".strtoupper($periode).'.');
        }

        return redirect()->back()->with('success', "Berhasil menarik data akumulasi kehadiran bulanan untuk {$syncCount} siswa (Periode ".strtoupper($periode).').');
    }

    /**
     * Unduh template file Excel (.xlsx atau .csv) untuk input SIA dan pembiasaan satu rombel kelas.
     */
    public function downloadTemplateSiaPembiasaan(Kelas $kelas, Request $request)
    {
        $this->authorizeAksesKelas($kelas);

        $periode = strtolower($request->query('periode', 'sts'));
        if (! in_array($periode, ['sts', 'sas'])) {
            $periode = 'sts';
        }

        $format = strtolower($request->query('format', 'xlsx'));

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();

        $kehadiranList = Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('periode', $periode)
            ->get()
            ->keyBy('kelas_siswa_id');

        $nilaiPemGroup = NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaList->pluck('id'))
            ->where('periode', $periode)
            ->get()
            ->groupBy('kelas_siswa_id');

        $headers = ['No', 'NIS', 'Nama Siswa', 'Sakit', 'Izin', 'Alpa'];
        foreach ($pembiasaans as $p) {
            $headers[] = $p->nama;
        }

        $dataRows = [];
        foreach ($kelasSiswaList as $idx => $ks) {
            $kh = $kehadiranList->get($ks->id);
            $sakit = $kh ? (string) $kh->sakit : '0';
            $izin = $kh ? (string) $kh->izin : '0';
            $alpa = $kh ? (string) $kh->alpa : '0';

            $row = [
                $idx + 1,
                $ks->siswa->nis ?? '',
                $ks->siswa->nama,
                $sakit,
                $izin,
                $alpa,
            ];

            $studentPem = $nilaiPemGroup->get($ks->id, collect())->keyBy('pembiasaan_id');
            foreach ($pembiasaans as $p) {
                $np = $studentPem->get($p->id);
                $row[] = $np ? $np->predikat : 'Baik';
            }

            $dataRows[] = $row;
        }

        $namaFileLabel = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $kelas->nama));

        if ($format === 'csv') {
            $filename = "template_sia_pembiasaan_{$namaFileLabel}_".strtoupper($periode).'.csv';
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

        $filename = "template_sia_pembiasaan_{$namaFileLabel}_".strtoupper($periode).'.xlsx';

        return ExcelWriter::downloadResponse($headers, $dataRows, $filename, 'SIA dan Pembiasaan');
    }

    /**
     * Impor data kehadiran (SIA) dan karakter pembiasaan dari file Excel (.xlsx, .csv, .txt), PDF (.pdf), atau clipboard.
     */
    public function importSiaPembiasaan(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $request->validate([
            'periode' => 'required|in:sts,sas',
            'file_excel' => 'nullable|file|mimes:xlsx,csv,txt,pdf|max:10240',
            'data_teks' => 'nullable|string',
        ]);

        $periode = $request->input('periode', 'sts');

        $rows = [];
        if ($request->hasFile('file_excel')) {
            $file = $request->file('file_excel');
            $rows = ExcelReader::parseFile($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif (! empty($request->input('data_teks'))) {
            $rows = ExcelReader::parseClipboard($request->input('data_teks'));
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Tidak ada data kehadiran atau pembiasaan yang terdeteksi dari file atau teks yang diunggah.');
        }

        $hasil = $this->prosesImporSiaPembiasaan($kelas, $rows, $periode);

        if ($hasil['sukses'] === 0) {
            return redirect()->back()->with('error', 'Gagal mengimpor data: '.$hasil['pesan']);
        }

        $details = [];
        if (! empty($hasil['sia_count'])) {
            $details[] = 'kehadiran (SIA) '.$hasil['sia_count'].' siswa';
        }
        if (! empty($hasil['pembiasaan_count'])) {
            $details[] = $hasil['pembiasaan_count'].' aspek pembiasaan';
        }
        $detailStr = ! empty($details) ? ' ('.implode(', ', $details).')' : '';

        return redirect()->back()->with('success', "Berhasil mengimpor SIA & Pembiasaan untuk {$hasil['sukses']} siswa{$detailStr} (Periode ".strtoupper($periode).').');
    }

    /**
     * Penguraian data baris tabel untuk kehadiran (SIA) dan nilai karakter pembiasaan.
     */
    protected function prosesImporSiaPembiasaan(Kelas $kelas, array $rows, string $periode): array
    {
        $kelasSiswaList = $kelas->kelasSiswa()->with('siswa')->get();
        if ($kelasSiswaList->isEmpty()) {
            return ['sukses' => 0, 'pesan' => 'Rombel kelas ini belum memiliki siswa.'];
        }

        $siswaByNis = [];
        $siswaByNisn = [];
        $siswaByNama = [];
        $siswaByNormalizedNama = [];
        $siswaByNoUrut = [];

        foreach ($kelasSiswaList as $idx => $ks) {
            $cleanNis = ExcelReader::cleanNumberString((string) $ks->siswa->nis);
            if ($cleanNis !== '') {
                $siswaByNis[$cleanNis] = $ks;
            }

            $cleanNisn = ExcelReader::cleanNumberString((string) $ks->siswa->nisn);
            if ($cleanNisn !== '') {
                $siswaByNisn[$cleanNisn] = $ks;
            }

            $cleanNama = strtolower(trim(preg_replace('/\s+/', ' ', $ks->siswa->nama)));
            $siswaByNama[$cleanNama] = $ks;

            $alphaOnly = preg_replace('/[^a-z0-9]/', '', $cleanNama);
            if ($alphaOnly !== '') {
                $siswaByNormalizedNama[$alphaOnly] = $ks;
            }

            $siswaByNoUrut[$idx + 1] = $ks;
        }

        $pembiasaans = Pembiasaan::aktif()->orderBy('urutan')->get();

        // Deteksi Baris Header
        $headerRowIdx = null;
        $colNo = null;
        $colNis = null;
        $colNama = null;
        $colSakit = null;
        $colIzin = null;
        $colAlpa = null;
        $colPembiasaanMap = [];

        $sakitSynonyms = ['sakit', 's', 's (sakit)', 'jml sakit', 'jumlah sakit', 'skt'];
        $izinSynonyms = ['izin', 'ijin', 'i', 'i (izin)', 'i (ijin)', 'jml izin', 'jumlah izin', 'izn'];
        $alpaSynonyms = ['alpa', 'alpha', 'a', 'a (alpa)', 'a (alpha)', 'tanpa keterangan', 'tk', 'jml alpa', 'jumlah alpa', 'alp'];

        $searchLimit = min(15, count($rows));
        for ($r = 0; $r < $searchLimit; $r++) {
            $row = $rows[$r];
            $tempNo = null;
            $tempNis = null;
            $tempNama = null;
            $tempSakit = null;
            $tempIzin = null;
            $tempAlpa = null;
            $tempPem = [];

            foreach ($row as $cIdx => $cell) {
                $cellVal = strtolower(trim((string) $cell));
                $cellVal = preg_replace('/\s+/', ' ', $cellVal);

                if (in_array($cellVal, ['no', 'no.', 'nomor', 'no urut', 'no. urut', 'absen', 'no absen', 'nomor absen'])) {
                    $tempNo = $cIdx;
                } elseif (in_array($cellVal, ['nis', 'nisn', 'nipd', 'no induk', 'nomor induk', 'nis siswa', 'nisn siswa', 'nis / nisn', 'nis/nisn', 'no. induk', 'no_induk'])) {
                    $tempNis = $cIdx;
                } elseif (in_array($cellVal, ['nama', 'nama siswa', 'nama lengkap', 'nama peserta didik', 'nama murid', 'pd', 'peserta didik', 'siswa'])) {
                    $tempNama = $cIdx;
                } elseif (in_array($cellVal, $sakitSynonyms)) {
                    $tempSakit = $cIdx;
                } elseif (in_array($cellVal, $izinSynonyms)) {
                    $tempIzin = $cIdx;
                } elseif (in_array($cellVal, $alpaSynonyms)) {
                    $tempAlpa = $cIdx;
                } else {
                    $pId = $this->resolvePembiasaanFromHeader((string) $cell, $pembiasaans);
                    if ($pId) {
                        $tempPem[$cIdx] = $pId;
                    }
                }
            }

            if (($tempNis !== null || $tempNama !== null) && ($tempSakit !== null || $tempIzin !== null || $tempAlpa !== null || ! empty($tempPem))) {
                $headerRowIdx = $r;
                $colNo = $tempNo;
                $colNis = $tempNis;
                $colNama = $tempNama;
                $colSakit = $tempSakit;
                $colIzin = $tempIzin;
                $colAlpa = $tempAlpa;
                $colPembiasaanMap = $tempPem;
                break;
            }
        }

        // Fallback jika baris 0 memiliki kolom SIA/Pembiasaan
        if ($headerRowIdx === null) {
            $sampleRow = $rows[0] ?? [];
            foreach ($sampleRow as $cIdx => $cell) {
                $cellVal = strtolower(trim((string) $cell));
                if (in_array($cellVal, $sakitSynonyms)) {
                    $colSakit = $cIdx;
                } elseif (in_array($cellVal, $izinSynonyms)) {
                    $colIzin = $cIdx;
                } elseif (in_array($cellVal, $alpaSynonyms)) {
                    $colAlpa = $cIdx;
                } else {
                    $pId = $this->resolvePembiasaanFromHeader((string) $cell, $pembiasaans);
                    if ($pId) {
                        $colPembiasaanMap[$cIdx] = $pId;
                    }
                }
            }
            if ($colSakit !== null || $colIzin !== null || $colAlpa !== null || ! empty($colPembiasaanMap)) {
                $headerRowIdx = 0;
                $colNama = 1;
                $colNis = 0;
            }
        }

        if ($colSakit === null && $colIzin === null && $colAlpa === null && empty($colPembiasaanMap)) {
            return [
                'sukses' => 0,
                'pesan' => 'Kolom Sakit/Izin/Alpa maupun Pembiasaan tidak terdeteksi. Pastikan baris header memuat label Sakit, Izin, Alpa atau Aspek Pembiasaan, atau gunakan template resmi.',
            ];
        }

        $dataRows = $headerRowIdx !== null ? array_slice($rows, $headerRowIdx + 1) : $rows;

        $siswaTerupdate = [];
        $siaUpdatedCount = 0;
        $pembiasaanTerupdate = [];

        foreach ($dataRows as $row) {
            $noVal = $colNo !== null ? (int) ExcelReader::cleanNumberString(trim((string) ($row[$colNo] ?? ''))) : null;
            $nisVal = $colNis !== null ? ExcelReader::cleanNumberString(trim((string) ($row[$colNis] ?? ''))) : '';
            $namaVal = $colNama !== null ? strtolower(trim(preg_replace('/\s+/', ' ', (string) ($row[$colNama] ?? '')))) : '';

            if ($namaVal === '' && $nisVal === '' && ! $noVal) {
                continue;
            }

            // Cari siswa yang cocok
            $matchedKs = null;
            if ($nisVal !== '' && isset($siswaByNis[$nisVal])) {
                $matchedKs = $siswaByNis[$nisVal];
            } elseif ($nisVal !== '' && isset($siswaByNisn[$nisVal])) {
                $matchedKs = $siswaByNisn[$nisVal];
            } elseif ($namaVal !== '' && isset($siswaByNama[$namaVal])) {
                $matchedKs = $siswaByNama[$namaVal];
            } elseif ($namaVal !== '') {
                $alphaVal = preg_replace('/[^a-z0-9]/', '', $namaVal);
                if ($alphaVal !== '' && isset($siswaByNormalizedNama[$alphaVal])) {
                    $matchedKs = $siswaByNormalizedNama[$alphaVal];
                }
            }

            if (! $matchedKs && $namaVal !== '') {
                foreach ($siswaByNama as $nKey => $ks) {
                    if (str_contains($nKey, $namaVal) || str_contains($namaVal, $nKey)) {
                        $matchedKs = $ks;
                        break;
                    }
                }
            }

            if (! $matchedKs && $namaVal !== '') {
                $bestPct = 0;
                $bestKs = null;
                foreach ($kelasSiswaList as $ks) {
                    $dbNama = strtolower(trim(preg_replace('/\s+/', ' ', $ks->siswa->nama)));
                    similar_text($namaVal, $dbNama, $pct);
                    if ($pct > $bestPct) {
                        $bestPct = $pct;
                        $bestKs = $ks;
                    }
                }
                if ($bestPct >= 80 && $bestKs) {
                    $matchedKs = $bestKs;
                }
            }

            if (! $matchedKs && $noVal && isset($siswaByNoUrut[$noVal])) {
                $matchedKs = $siswaByNoUrut[$noVal];
            }

            if (! $matchedKs) {
                continue;
            }

            // Simpan Kehadiran (SIA)
            if ($colSakit !== null || $colIzin !== null || $colAlpa !== null) {
                $sakitVal = $colSakit !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colSakit] ?? '0')))) : 0;
                $izinVal = $colIzin !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colIzin] ?? '0')))) : 0;
                $alpaVal = $colAlpa !== null ? max(0, (int) ExcelReader::cleanNumberString(trim((string) ($row[$colAlpa] ?? '0')))) : 0;

                Kehadiran::updateOrCreate([
                    'kelas_siswa_id' => $matchedKs->id,
                    'periode' => $periode,
                ], [
                    'sakit' => $sakitVal,
                    'izin' => $izinVal,
                    'alpa' => $alpaVal,
                ]);

                $siaUpdatedCount++;
                $siswaTerupdate[$matchedKs->id] = true;
            }

            // Simpan Nilai Pembiasaan
            foreach ($colPembiasaanMap as $cIdx => $pId) {
                $rawPred = trim((string) ($row[$cIdx] ?? ''));
                if ($rawPred === '' || $rawPred === '-') {
                    continue;
                }

                $pred = $this->normalizePredikat($rawPred);

                NilaiPembiasaan::updateOrCreate([
                    'kelas_siswa_id' => $matchedKs->id,
                    'pembiasaan_id' => $pId,
                    'periode' => $periode,
                ], [
                    'predikat' => $pred,
                ]);

                $pembiasaanTerupdate[$pId] = true;
                $siswaTerupdate[$matchedKs->id] = true;
            }
        }

        return [
            'sukses' => count($siswaTerupdate),
            'sia_count' => $siaUpdatedCount,
            'pembiasaan_count' => count($pembiasaanTerupdate),
            'pesan' => 'OK',
        ];
    }

    /**
     * Kosongkan / reset nilai kelas untuk periode dan mata pelajaran tertentu (atau seluruh mapel).
     * Digunakan ketika terjadi salah input atau salah impor spreadsheet.
     */
    public function kosongkanKelas(Request $request, Kelas $kelas)
    {
        $this->authorizeAksesKelas($kelas);

        $request->validate([
            'periode' => 'required|in:sts,sas,semua',
            'mapel_id' => 'nullable|string',
            'kosongkan_kehadiran' => 'nullable|boolean',
            'kosongkan_pembiasaan' => 'nullable|boolean',
            'konfirmasi' => 'required|accepted',
        ], [
            'konfirmasi.accepted' => 'Harap centang kotak persetujuan konfirmasi untuk mengosongkan nilai.',
        ]);

        $periode = $request->input('periode', 'sts');
        $mapelId = $request->input('mapel_id');
        $kelasSiswaIds = $kelas->kelasSiswa()->pluck('id');

        if ($kelasSiswaIds->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada siswa yang terdaftar di kelas ini.');
        }

        $queryNilai = Nilai::whereIn('kelas_siswa_id', $kelasSiswaIds);

        if ($periode !== 'semua') {
            $queryNilai->where('periode', $periode);
        }

        if ($mapelId && $mapelId !== 'semua' && is_numeric($mapelId)) {
            $queryNilai->where('mapel_id', (int) $mapelId);
            $namaMapel = MataPelajaran::find($mapelId)?->nama ?? 'Mapel Terpilih';
            $scopeMapel = "mata pelajaran {$namaMapel}";
        } else {
            $scopeMapel = 'seluruh mata pelajaran';
        }

        $deletedNilaiCount = $queryNilai->delete();

        // Opsi: Kosongkan Kehadiran jika dicentang
        $deletedKehadiranCount = 0;
        if ($request->boolean('kosongkan_kehadiran')) {
            $queryKehadiran = Kehadiran::whereIn('kelas_siswa_id', $kelasSiswaIds);
            if ($periode !== 'semua') {
                $queryKehadiran->where('periode', $periode);
            }
            $deletedKehadiranCount = $queryKehadiran->delete();
        }

        // Opsi: Kosongkan Pembiasaan jika dicentang
        $deletedPembiasaanCount = 0;
        if ($request->boolean('kosongkan_pembiasaan')) {
            $queryPembiasaan = NilaiPembiasaan::whereIn('kelas_siswa_id', $kelasSiswaIds);
            if ($periode !== 'semua') {
                $queryPembiasaan->where('periode', $periode);
            }
            $deletedPembiasaanCount = $queryPembiasaan->delete();
        }

        $periodeLabel = ($periode === 'semua') ? 'Semua Periode (STS & SAS)' : strtoupper($periode);
        $extraMsg = [];
        if ($deletedKehadiranCount > 0) {
            $extraMsg[] = 'kehadiran';
        }
        if ($deletedPembiasaanCount > 0) {
            $extraMsg[] = 'pembiasaan';
        }
        $extraText = ! empty($extraMsg) ? ' beserta data '.implode(' & ', $extraMsg) : '';

        return redirect()->back()->with('success', "Berhasil mengosongkan {$deletedNilaiCount} rekaman nilai untuk {$scopeMapel} ({$periodeLabel}) pada rombel {$kelas->nama}{$extraText}.");
    }

    protected function authorizeAksesKelas(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Hanya Administrator atau Wali Kelas bersangkutan yang berhak menginput atau mengubah nilai.');
        }
        if (! $kelas->tahunAjaran || ! $kelas->tahunAjaran->aktif) {
            abort(403, 'Tahun ajaran kelas ini tidak aktif. Nilai hanya dapat diubah pada tahun ajaran yang aktif.');
        }
    }
}
