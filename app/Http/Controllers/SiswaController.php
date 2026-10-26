<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Services\ExcelReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    /**
     * Tampilkan daftar siswa dengan filter pencarian, status, dan tab per kelas.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isWali = $user && $user->isWali();

        $search = $request->input('search');
        $status = $request->input('status');
        $kelasId = $request->input('kelas_id');

        $query = Siswa::with(['kelasSiswa.kelas.tahunAjaran']);

        if ($isWali) {
            $waliKelasIds = $user->kelasWali()->pluck('id');

            // Wali kelas hanya dapat melihat murid di kelas yang diampunya
            $query->whereHas('kelasSiswa', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', $waliKelasIds);
            });

            if ($kelasId && $waliKelasIds->contains($kelasId)) {
                $query->whereHas('kelasSiswa', function ($q) use ($kelasId) {
                    $q->where('kelas_id', $kelasId);
                });
            }

            $kelasList = Kelas::whereIn('id', $waliKelasIds)
                ->with('tahunAjaran')
                ->withCount('kelasSiswa')
                ->orderBy('nama', 'asc')
                ->get();

            $totalSiswaCount = Siswa::whereHas('kelasSiswa', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', $waliKelasIds);
            })->count();

            $tanpaKelasCount = 0;
        } else {
            if ($kelasId) {
                if ($kelasId === 'tanpa_kelas') {
                    $query->whereDoesntHave('kelasSiswa');
                } else {
                    $query->whereHas('kelasSiswa', function ($q) use ($kelasId) {
                        $q->where('kelas_id', $kelasId);
                    });
                }
            }

            $totalSiswaCount = Siswa::count();
            $tanpaKelasCount = Siswa::whereDoesntHave('kelasSiswa')->count();
            $kelasList = Kelas::with('tahunAjaran')->withCount('kelasSiswa')->orderBy('nama', 'asc')->get();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $siswaList = $query->orderBy('nama', 'asc')->paginate(20);

        return view('siswa.index', compact(
            'siswaList',
            'search',
            'status',
            'kelasId',
            'kelasList',
            'totalSiswaCount',
            'tanpaKelasCount'
        ));
    }

    /**
     * Generate NIS sementara yang unik (format: TMP-YYYY-XXXX).
     */
    public function generateTemporaryNis(): string
    {
        $year = date('Y');
        $prefix = "TMP-{$year}-";

        // Ambil NIS terakhir yang memiliki prefix TMP-YYYY-
        $lastSiswa = Siswa::where('nis', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(nis) DESC')
            ->orderBy('nis', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastSiswa && preg_match('/^TMP-\d{4}-(\d+)$/', $lastSiswa->nis, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        // Pastikan nomor kandidat benar-benar belum terdaftar
        do {
            $candidate = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = Siswa::where('nis', $candidate)->exists();
            $nextNumber++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Simpan data siswa baru secara manual.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50|unique:siswa,nis',
            'nisn' => 'nullable|string|max:50',
            'status' => 'required|in:aktif,lulus',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        $nis = trim((string) $request->input('nis', ''));
        if ($nis === '') {
            $nis = $this->generateTemporaryNis();
        }

        $siswa = Siswa::create([
            'nama' => trim($request->input('nama')),
            'nis' => $nis,
            'nisn' => $request->filled('nisn') ? trim((string) $request->input('nisn')) : null,
            'status' => $request->input('status', 'aktif'),
        ]);

        $msg = 'Data siswa berhasil ditambahkan.';
        if ($request->filled('kelas_id')) {
            $kelas = Kelas::find($request->input('kelas_id'));
            if ($kelas) {
                KelasSiswa::firstOrCreate([
                    'kelas_id' => $kelas->id,
                    'siswa_id' => $siswa->id,
                ]);
                $msg = "Data siswa berhasil ditambahkan dan ditempatkan ke {$kelas->nama}.";
            }
        }

        $redirectParams = $request->filled('kelas_id') ? ['kelas_id' => $request->input('kelas_id')] : [];

        return redirect()->route('siswa.index', $redirectParams)->with('success', $msg);
    }

    /**
     * Perbarui data siswa.
     */
    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50|unique:siswa,nis,'.$siswa->id,
            'nisn' => 'nullable|string|max:50',
            'status' => 'required|in:aktif,lulus',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        $nis = trim((string) $request->input('nis', ''));
        if ($nis === '') {
            $nis = ! empty($siswa->nis) ? $siswa->nis : $this->generateTemporaryNis();
        }

        $siswa->update([
            'nama' => trim($request->input('nama')),
            'nis' => $nis,
            'nisn' => $request->filled('nisn') ? trim((string) $request->input('nisn')) : null,
            'status' => $request->input('status', 'aktif'),
        ]);

        $msg = 'Data siswa berhasil diperbarui.';

        if ($request->filled('kelas_id')) {
            $kelas = Kelas::find($request->input('kelas_id'));
            if ($kelas) {
                $ks = KelasSiswa::where('siswa_id', $siswa->id)
                    ->where('kelas_id', $kelas->id)
                    ->first();

                if (! $ks) {
                    KelasSiswa::create([
                        'kelas_id' => $kelas->id,
                        'siswa_id' => $siswa->id,
                    ]);
                    $msg = "Data siswa berhasil diperbarui dan ditempatkan ke {$kelas->nama}.";
                }
            }
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Hapus data siswa.
     */
    public function destroy(Siswa $siswa): RedirectResponse
    {
        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Impor data siswa dari file Excel (XLSX, CSV) atau clipboard otomatis per tingkat kelas.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'kelas_id' => 'nullable',
            'file_excel' => 'nullable|file|mimes:xlsx,csv,txt|max:10240',
            'data_teks' => 'nullable|string',
        ]);

        $rows = [];
        if ($request->hasFile('file_excel')) {
            $file = $request->file('file_excel');
            $rows = ExcelReader::parseFile($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif (! empty(trim((string) $request->input('data_teks', '')))) {
            $rows = ExcelReader::parseClipboard($request->input('data_teks'));
        } else {
            return redirect()->back()->withErrors([
                'import_error' => 'Silakan pilih file Excel/CSV atau tempelkan data teks dari clipboard.',
            ]);
        }

        $students = ExcelReader::extractStudents($rows);
        if (empty($students)) {
            return redirect()->back()->withErrors([
                'import_error' => 'Tidak ada data siswa yang valid ditemukan. Pastikan tabel memiliki kolom Nama Siswa.',
            ]);
        }

        // Tentukan mode kelas: jika ada ID kelas spesifik, gunakan kelas tersebut.
        // Jika 'auto' atau null, otomatis deteksi per baris dari kolom 'Kelas'.
        $targetKelasSpesifik = null;
        if (! empty($request->kelas_id) && $request->kelas_id !== 'auto' && is_numeric($request->kelas_id)) {
            $targetKelasSpesifik = Kelas::find($request->kelas_id);
        }

        $suksesCount = 0;
        $duplikatCount = 0;
        $penempatanSummary = []; // [nama_kelas => jumlah]
        $sudahDiKelas = 0;

        foreach ($students as $item) {
            $nis = ! empty($item['nis']) ? $item['nis'] : $this->generateTemporaryNis();
            $siswa = Siswa::where('nis', $nis)->first();
            if (! $siswa) {
                $siswa = Siswa::create([
                    'nama' => $item['nama'],
                    'nis' => $nis,
                    'nisn' => $item['nisn'],
                    'status' => 'aktif',
                ]);
                $suksesCount++;
            } else {
                $duplikatCount++;
                if (empty($siswa->nisn) && ! empty($item['nisn'])) {
                    $siswa->update(['nisn' => $item['nisn']]);
                }
            }

            // Tentukan kelas tujuan untuk siswa ini
            $targetKelas = null;
            if ($targetKelasSpesifik) {
                $targetKelas = $targetKelasSpesifik;
            } elseif (! empty($item['kelas'])) {
                $targetKelas = $this->matchOrCreateKelas($item['kelas']);
            }

            if ($targetKelas) {
                $ks = KelasSiswa::where('kelas_id', $targetKelas->id)
                    ->where('siswa_id', $siswa->id)
                    ->first();

                if (! $ks) {
                    KelasSiswa::create([
                        'kelas_id' => $targetKelas->id,
                        'siswa_id' => $siswa->id,
                    ]);
                    $penempatanSummary[$targetKelas->nama] = ($penempatanSummary[$targetKelas->nama] ?? 0) + 1;
                } else {
                    $sudahDiKelas++;
                }
            }
        }

        $msg = "Impor data berhasil: {$suksesCount} siswa baru ditambahkan";
        if ($duplikatCount > 0) {
            $msg .= ", {$duplikatCount} NIS sudah ada di database";
        }

        if (! empty($penempatanSummary)) {
            $details = [];
            foreach ($penempatanSummary as $namaKls => $cnt) {
                $details[] = "{$cnt} siswa ke {$namaKls}";
            }
            $msg .= '. Otomatis ditempatkan ke: '.implode(', ', $details);
        } elseif ($targetKelasSpesifik) {
            $msg .= ". Tidak ada siswa baru yang ditempatkan ke {$targetKelasSpesifik->nama}";
        }

        if ($sudahDiKelas > 0) {
            $msg .= " ({$sudahDiKelas} siswa sebelumnya sudah terdaftar di kelasnya)";
        }
        $msg .= '.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Kompatibilitas route lama impor clipboard.
     */
    public function importClipboard(Request $request): RedirectResponse
    {
        return $this->import($request);
    }

    /**
     * Unduh template file Excel/CSV untuk mempermudah impor siswa per kelas.
     */
    public function downloadTemplate(Request $request): StreamedResponse
    {
        $kelasId = $request->query('kelas_id');
        $namaFileLabel = 'umum';
        $headerKelasText = '';

        if ($kelasId && $kelasId !== 'auto' && is_numeric($kelasId)) {
            $kelas = Kelas::find($kelasId);
            if ($kelas) {
                $namaFileLabel = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $kelas->nama));
                $headerKelasText = $kelas->nama;
            }
        }

        $filename = "template_import_siswa_{$namaFileLabel}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($headerKelasText) {
            $output = fopen('php://output', 'w');
            // Byte Order Mark (BOM) UTF-8 agar langsung terbaca rapi oleh Microsoft Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, ['No', 'Nama Siswa', 'NIS', 'NISN', 'Kelas']);
            if ($headerKelasText) {
                fputcsv($output, ['1', 'Ahmad Fauzi', '1001', '0151234501', $headerKelasText]);
                fputcsv($output, ['2', 'Siti Rahmawati', '1002', '0151234502', $headerKelasText]);
                fputcsv($output, ['3', 'Budi Santoso', '1003', '0151234503', $headerKelasText]);
            } else {
                // Contoh gabungan multi-tingkat kelas agar pengguna paham sistem otomatis memisahkan per tingkat kelas
                fputcsv($output, ['1', 'Ahmad Fauzi', '1001', '0151234501', 'Kelas 1']);
                fputcsv($output, ['2', 'Siti Rahmawati', '1002', '0151234502', 'Kelas 1']);
                fputcsv($output, ['3', 'Budi Santoso', '2001', '0151234503', 'Kelas 2']);
                fputcsv($output, ['4', 'Citra Lestari', '3001', '0151234504', 'Kelas 3']);
                fputcsv($output, ['5', 'Doni Pratama', '4001', '0151234505', 'Kelas 4']);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Cari atau buat kelas yang cocok berdasarkan teks nama/tingkat kelas.
     */
    protected function matchOrCreateKelas(?string $kelasText): ?Kelas
    {
        if (empty($kelasText)) {
            return null;
        }

        $raw = trim($kelasText);
        $tahunAktif = TahunAjaran::aktif()->first() ?: TahunAjaran::first();
        $tahunId = $tahunAktif ? $tahunAktif->id : 1;

        // 1. Coba pencarian persis (exact match) di tahun ajaran aktif
        $exact = Kelas::where('tahun_ajaran_id', $tahunId)
            ->where('nama', $raw)
            ->first();
        if ($exact) {
            return $exact;
        }

        // Coba exact match tanpa filter tahun
        $exactGlobal = Kelas::where('nama', $raw)->first();
        if ($exactGlobal) {
            return $exactGlobal;
        }

        $lower = strtolower($raw);

        // Angka Romawi I s/d VI
        $romanMap = [
            'vi' => 6,
            'iv' => 4,
            'v' => 5,
            'iii' => 3,
            'ii' => 2,
            'i' => 1,
        ];

        $tingkat = null;

        // Cek jika mengandung angka 1-6
        if (preg_match('/(?:kelas|tingkat|kls|rombel)?\s*([1-6])\b/i', $raw, $m)) {
            $tingkat = (int) $m[1];
        } else {
            foreach ($romanMap as $rom => $num) {
                if (preg_match('/(?:kelas|tingkat|kls|rombel)\s*\b'.$rom.'\b/i', $lower) || preg_match('/^\s*'.$rom.'\s*$/i', $lower)) {
                    $tingkat = $num;
                    break;
                }
            }
        }

        if ($tingkat !== null) {
            // Prioritas pada tahun ajaran aktif
            $found = Kelas::where('tahun_ajaran_id', $tahunId)
                ->where(function ($q) use ($tingkat) {
                    $q->where('nama', "Kelas {$tingkat}")
                        ->orWhere('nama', (string) $tingkat)
                        ->orWhere('nama', 'like', "Kelas {$tingkat}%");
                })
                ->first();

            if (! $found) {
                $found = Kelas::where(function ($q) use ($tingkat) {
                    $q->where('nama', "Kelas {$tingkat}")
                        ->orWhere('nama', (string) $tingkat)
                        ->orWhere('nama', 'like', "Kelas {$tingkat}%");
                })->first();
            }

            if ($found) {
                return $found;
            }

            // Jika belum ada, buat kelas baru di tahun ajaran aktif
            $fase = match ($tingkat) {
                1, 2 => 'Fase A',
                3, 4 => 'Fase B',
                5, 6 => 'Fase C',
                default => 'Fase A',
            };

            return Kelas::create([
                'nama' => "Kelas {$tingkat}",
                'fase' => $fase,
                'tahun_ajaran_id' => $tahunId,
                'sekolah_id' => 1,
            ]);
        }

        if (strlen($raw) >= 3) {
            $fallback = Kelas::where('tahun_ajaran_id', $tahunId)
                ->where('nama', 'like', "%{$raw}%")
                ->first();
            if ($fallback) {
                return $fallback;
            }
        }

        return null;
    }
}
