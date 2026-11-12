<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiFormatifHarian;
use App\Models\NilaiRaporMapel;
use App\Models\PenilaianFormatifHarian;
use App\Models\Sekolah;
use App\Services\HitungNilaiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NilaiFormatifHarianController extends Controller
{
    /**
     * Authorize akses kelas
     */
    protected function authorizeAkses(Kelas $kelas): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if ($user->isAdmin() || ($user->isWali() && $kelas->wali_id === $user->id) || $user->isGuru()) {
            return;
        }

        abort(403, 'Anda tidak memiliki hak akses untuk menginput nilai pada kelas ini.');
    }

    /**
     * Tampilan Utama: Rincian Penilaian Harian per Mapel & Bulan
     */
    public function index(Kelas $kelas, MataPelajaran $mapel, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahunAjaran = $kelas->tahunAjaran;

        $semester = strtolower($tahunAjaran->semester ?? 'ganjil');
        $tahun = (int) $request->input('tahun', now()->year);

        $daftarBulanSemester = $semester === 'genap'
            ? [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni']
            : [7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $defaultBulan = $semester === 'genap' ? 1 : 7;
        $currMonth = now()->month;
        if (isset($daftarBulanSemester[$currMonth])) {
            $defaultBulan = $currMonth;
        }

        $bulan = (int) $request->input('bulan', $defaultBulan);
        if (! isset($daftarBulanSemester[$bulan])) {
            $bulan = $defaultBulan;
        }

        $namaBulan = $daftarBulanSemester[$bulan] ?? "Bulan {$bulan}";

        // Daftar semua mapel di kelas untuk switcher
        $allMapel = $kelas->mataPelajaran();

        // Daftar siswa
        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        // Daftar penilaian harian untuk kelas, mapel, tahun, bulan ini
        $penilaianList = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        // Ambil seluruh nilai harian
        $penilaianIds = $penilaianList->pluck('id');
        $allNilaiHarian = $penilaianIds->isNotEmpty()
            ? NilaiFormatifHarian::whereIn('penilaian_formatif_harian_id', $penilaianIds)
                ->whereIn('kelas_siswa_id', $ksIds)
                ->get()
            : collect();

        // Matrix nilai [penilaian_id][kelas_siswa_id] = nilai
        $matrixHarian = [];
        foreach ($allNilaiHarian as $nh) {
            $matrixHarian[$nh->penilaian_formatif_harian_id][$nh->kelas_siswa_id] = $nh->nilai;
        }

        // Ambil nilai formatif bulanan yang tersimpan saat ini
        $existingBulanan = NilaiFormatifBulanan::whereIn('kelas_siswa_id', $ksIds)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get()
            ->keyBy('kelas_siswa_id');

        $isPjok = method_exists($mapel, 'isPjok') ? $mapel->isPjok() : false;
        $isAgama = method_exists($mapel, 'isAgama') ? $mapel->isAgama() : false;

        // Hitung rata-rata harian per siswa
        $rekapPerSiswa = [];
        foreach ($kelasSiswaList as $ks) {
            $scoresTeori = [];
            $scoresPraktik = [];

            foreach ($penilaianList as $p) {
                $val = $matrixHarian[$p->id][$ks->id] ?? null;
                if ($val !== null && $val !== '') {
                    if ($p->jenis === 'praktik') {
                        $scoresPraktik[] = (float) $val;
                    } else {
                        $scoresTeori[] = (float) $val;
                    }
                }
            }

            $avgTeori = count($scoresTeori) > 0 ? round(array_sum($scoresTeori) / count($scoresTeori), 1) : null;
            $avgPraktik = count($scoresPraktik) > 0 ? round(array_sum($scoresPraktik) / count($scoresPraktik), 1) : null;

            $recBulanan = $existingBulanan->get($ks->id);

            $rekapPerSiswa[$ks->id] = [
                'avg_teori' => $avgTeori,
                'avg_praktik' => $avgPraktik,
                'bulanan_tersimpan' => $recBulanan ? $recBulanan->nilai : null,
                'praktik_tersimpan' => $recBulanan ? $recBulanan->nilai_praktik : null,
            ];
        }

        $defaultNamaKolom = $this->suggestNextDefaultColumnName($penilaianList->pluck('nama')->all());

        return view('kelas.formatif_harian.index', compact(
            'kelas',
            'mapel',
            'allMapel',
            'kelasSiswaList',
            'penilaianList',
            'matrixHarian',
            'rekapPerSiswa',
            'tahun',
            'bulan',
            'namaBulan',
            'daftarBulanSemester',
            'semester',
            'isPjok',
            'isAgama',
            'defaultNamaKolom'
        ));
    }

    /**
     * Tambah Kolom Penilaian Harian Baru (mendukung tambah 1 atau beberapa kolom sekaligus).
     */
    public function tambahPenilaian(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $validated = $request->validate(
            array_merge($this->penilaianFieldRules(), [
                'bulan' => ['required', 'integer', 'between:1,12'],
                'tahun' => ['required', 'integer', 'min:2020'],
                'jumlah_kolom' => ['nullable', 'integer', 'min:1', 'max:10'],
                'lanjut_tambah' => ['nullable', 'boolean'],
            ]),
            $this->penilaianValidationMessages()
        );

        $tahun = (int) $validated['tahun'];
        $bulan = (int) $validated['bulan'];
        $jumlahKolom = (int) ($validated['jumlah_kolom'] ?? 1);
        $baseNama = trim((string) $validated['nama']);
        $lingkupMateri = filled($validated['lingkup_materi'] ?? null)
            ? trim((string) $validated['lingkup_materi'])
            : null;
        $tanggal = filled($validated['tanggal'] ?? null) ? $validated['tanggal'] : null;
        $jenis = $validated['jenis'] ?? 'teori';
        $bobot = isset($validated['bobot']) && $validated['bobot'] !== ''
            ? (float) $validated['bobot']
            : 1.0;
        $lanjutTambah = $request->boolean('lanjut_tambah');

        $created = DB::transaction(function () use (
            $kelas,
            $mapel,
            $tahun,
            $bulan,
            $jumlahKolom,
            $baseNama,
            $lingkupMateri,
            $tanggal,
            $jenis,
            $bobot
        ): array {
            $existing = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
                ->where('mapel_id', $mapel->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->orderBy('urutan')
                ->get(['nama', 'urutan']);

            $nextUrutan = ((int) $existing->max('urutan')) + 1;
            $usedNames = $existing->pluck('nama')
                ->map(static fn (string $name): string => mb_strtolower(trim($name)))
                ->all();

            $createdColumns = [];
            for ($i = 0; $i < $jumlahKolom; $i++) {
                $kolomNama = $jumlahKolom === 1
                    ? mb_substr($baseNama, 0, 150)
                    : $this->generateSequentialColumnName($baseNama, $usedNames);

                $usedNames[] = mb_strtolower($kolomNama);

                $createdColumns[] = PenilaianFormatifHarian::create([
                    'kelas_id' => $kelas->id,
                    'mapel_id' => $mapel->id,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'nama' => $kolomNama,
                    'lingkup_materi' => $lingkupMateri,
                    'tanggal' => $tanggal,
                    'bobot' => $bobot,
                    'jenis' => $jenis,
                    'urutan' => $nextUrutan + $i,
                ]);
            }

            return $createdColumns;
        });

        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? (string) $bulan;
        if (count($created) === 1) {
            $message = "Penilaian Harian '{$created[0]->nama}' berhasil ditambahkan ke Bulan {$namaBulan}.";
        } else {
            $namaPertama = $created[0]->nama;
            $namaTerakhir = $created[count($created) - 1]->nama;
            $message = count($created)." kolom penilaian ({$namaPertama} s.d. {$namaTerakhir}) berhasil ditambahkan ke Bulan {$namaBulan}.";
        }

        $redirect = redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', $message);

        if ($lanjutTambah) {
            $redirect->with('open_modal_tambah', true);
        }

        return $redirect;
    }

    /**
     * Update Data Penilaian Harian (Nama, Tanggal, Lingkup Materi).
     */
    public function updatePenilaian(Kelas $kelas, MataPelajaran $mapel, PenilaianFormatifHarian $penilaian, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        if ($penilaian->kelas_id !== $kelas->id || $penilaian->mapel_id !== $mapel->id) {
            abort(404);
        }

        $validated = $request->validate(
            $this->penilaianFieldRules(),
            $this->penilaianValidationMessages()
        );

        $penilaian->update([
            'nama' => trim((string) $validated['nama']),
            'tanggal' => filled($validated['tanggal'] ?? null) ? $validated['tanggal'] : null,
            'lingkup_materi' => filled($validated['lingkup_materi'] ?? null)
                ? trim((string) $validated['lingkup_materi'])
                : null,
            'jenis' => $validated['jenis'] ?? ($penilaian->jenis ?: 'teori'),
            'bobot' => isset($validated['bobot']) && $validated['bobot'] !== ''
                ? (float) $validated['bobot']
                : (float) ($penilaian->bobot ?? 1.0),
        ]);

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $penilaian->tahun,
            'bulan' => $penilaian->bulan,
        ])->with('success', "Informasi penilaian '{$penilaian->nama}' berhasil diperbarui.");
    }

    /**
     * Aturan validasi bersama untuk pembuatan dan pembaruan kolom penilaian harian.
     *
     * @return array<string, array<int, string>>
     */
    private function penilaianFieldRules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'tanggal' => ['nullable', 'date'],
            'lingkup_materi' => ['nullable', 'string', 'max:500'],
            'jenis' => ['nullable', 'in:teori,praktik'],
            'bobot' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
        ];
    }

    /**
     * Pesan validasi berbahasa Indonesia agar mudah dipahami guru saat ada kesalahan input.
     *
     * @return array<string, string>
     */
    private function penilaianValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama kolom penilaian wajib diisi.',
            'nama.string' => 'Nama kolom penilaian harus berupa teks.',
            'nama.max' => 'Nama kolom penilaian maksimal :max karakter.',
            'tanggal.date' => 'Format tanggal penilaian tidak valid.',
            'lingkup_materi.string' => 'Lingkup materi / TP harus berupa teks.',
            'lingkup_materi.max' => 'Lingkup materi / TP maksimal :max karakter.',
            'jenis.in' => 'Jenis penilaian harus berupa Teori atau Praktik.',
            'bobot.numeric' => 'Bobot penilaian harus berupa angka.',
            'bobot.min' => 'Bobot penilaian minimal :min.',
            'bobot.max' => 'Bobot penilaian maksimal :max.',
            'jumlah_kolom.integer' => 'Jumlah kolom harus berupa angka bulat.',
            'jumlah_kolom.min' => 'Jumlah kolom minimal :min.',
            'jumlah_kolom.max' => 'Jumlah kolom sekaligus maksimal :max kolom.',
            'bulan.required' => 'Bulan penilaian wajib dipilih.',
            'tahun.required' => 'Tahun penilaian wajib dipilih.',
        ];
    }

    /**
     * Hasilkan nama kolom default berikutnya (mis. Harian 4) yang tidak bentrok setelah penghapusan kolom.
     *
     * @param  array<int, string>  $existingNames
     */
    private function suggestNextDefaultColumnName(array $existingNames): string
    {
        $maxNumber = 0;
        $usedLower = [];

        foreach ($existingNames as $name) {
            $trimmed = trim($name);
            $usedLower[] = mb_strtolower($trimmed);
            if (preg_match('/^harian\s+(\d+)$/iu', $trimmed, $matches)) {
                $maxNumber = max($maxNumber, (int) $matches[1]);
            }
        }

        $next = max($maxNumber, count($existingNames)) + 1;
        while (in_array('harian '.$next, $usedLower, true)) {
            $next++;
        }

        return 'Harian '.$next;
    }

    /**
     * Buat nama kolom berurutan saat menambahkan beberapa kolom sekaligus tanpa bentrok dengan kolom yang ada.
     *
     * @param  array<int, string>  $usedNamesLower
     */
    private function generateSequentialColumnName(string $baseNama, array $usedNamesLower): string
    {
        if (preg_match('/^(.*?)(\d+)$/u', $baseNama, $matches)) {
            $prefix = $matches[1];
            $number = (int) $matches[2];
        } else {
            $prefix = rtrim($baseNama).' ';
            $number = 1;
        }

        while (true) {
            $suffix = (string) $number;
            $maxPrefixLength = max(1, 150 - mb_strlen($suffix));
            $trimmedPrefix = mb_strlen($prefix) > $maxPrefixLength
                ? mb_substr($prefix, 0, $maxPrefixLength)
                : $prefix;
            $candidate = $trimmedPrefix.$suffix;

            if (! in_array(mb_strtolower($candidate), $usedNamesLower, true)) {
                return $candidate;
            }

            $number++;
        }
    }

    /**
     * Hapus Kolom Penilaian Harian
     */
    public function hapusPenilaian(Kelas $kelas, MataPelajaran $mapel, PenilaianFormatifHarian $penilaian): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        if ($penilaian->kelas_id !== $kelas->id || $penilaian->mapel_id !== $mapel->id) {
            abort(404);
        }

        $tahun = $penilaian->tahun;
        $bulan = $penilaian->bulan;
        $namaPenilaian = $penilaian->nama;

        $penilaian->delete();

        // Recalculate monthly average for all students in this month
        $this->sinkronisasiRataRataBulanan($kelas, $mapel, $tahun, $bulan);

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', "Penilaian Harian '{$namaPenilaian}' berhasil dihapus dan rata-rata bulanan diperbarui.");
    }

    /**
     * Simpan Seluruh Nilai Harian & Hitung Rata-rata Bulanan Otomatis
     */
    public function simpan(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        $nilaiData = $request->input('nilai', []); // [penilaian_id => [kelas_siswa_id => score]]

        $penilaianList = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        $kelasSiswaList = $kelas->kelasSiswa;

        DB::transaction(function () use ($penilaianList, $nilaiData, $kelasSiswaList, $kelas, $mapel, $tahun, $bulan) {
            // 1. Simpan setiap nilai harian
            foreach ($penilaianList as $p) {
                $pId = $p->id;
                $siswaScores = $nilaiData[$pId] ?? [];

                foreach ($kelasSiswaList as $ks) {
                    $rawVal = $siswaScores[$ks->id] ?? null;
                    $val = null;
                    if ($rawVal !== null && trim((string) $rawVal) !== '') {
                        $val = max(0, min(100, (float) str_replace(',', '.', trim((string) $rawVal))));
                    }

                    if ($val !== null) {
                        NilaiFormatifHarian::updateOrCreate([
                            'penilaian_formatif_harian_id' => $pId,
                            'kelas_siswa_id' => $ks->id,
                        ], [
                            'nilai' => $val,
                        ]);
                    } else {
                        NilaiFormatifHarian::where('penilaian_formatif_harian_id', $pId)
                            ->where('kelas_siswa_id', $ks->id)
                            ->delete();
                    }
                }
            }

            // 2. Hitung rata-rata dan sinkronkan ke NilaiFormatifBulanan serta NilaiRaporMapel
            $this->sinkronisasiRataRataBulanan($kelas, $mapel, $tahun, $bulan);
        });

        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', "Nilai Penilaian Harian berhasil disimpan! Rata-rata Formatif Bulan {$namaBulan} otomatis diperbarui.");
    }

    /**
     * AJAX Autosave Endpoint: Simpan satu sel nilai harian siswa lalu sinkronkan bulanan & rapor otomatis.
     */
    public function autosave(Kelas $kelas, MataPelajaran $mapel, Request $request): JsonResponse
    {
        $this->authorizeAkses($kelas);

        if ($request->has('nilai')) {
            $raw = $request->input('nilai');
            $trimmed = $raw !== null ? trim((string) $raw) : '';
            $request->merge([
                'nilai' => $trimmed === '' ? null : str_replace(',', '.', $trimmed),
            ]);
        }

        $validated = $request->validate([
            'penilaian_id' => ['required', 'integer'],
            'kelas_siswa_id' => ['required', 'integer'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'nilai' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'nilai.numeric' => 'Nilai harus berupa angka.',
            'nilai.min' => 'Nilai minimal adalah 0.',
            'nilai.max' => 'Nilai maksimal adalah 100.',
        ]);

        $ksId = (int) $validated['kelas_siswa_id'];
        $penilaianId = (int) $validated['penilaian_id'];
        $tahun = (int) ($validated['tahun'] ?? now()->year);
        $bulan = (int) $validated['bulan'];
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        abort_unless($kelas->kelasSiswa()->where('id', $ksId)->exists(), 404);
        $penilaian = PenilaianFormatifHarian::where('id', $penilaianId)
            ->where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->firstOrFail();

        $raporCek = NilaiRaporMapel::where('kelas_siswa_id', $ksId)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('semester', $semester)
            ->first();

        if ($raporCek && $raporCek->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Nilai rapor terkunci dan tidak dapat diubah.',
            ], 403);
        }

        $val = isset($validated['nilai']) && $validated['nilai'] !== null ? round((float) $validated['nilai'], 1) : null;
        $avgTeori = null;
        $avgPraktik = null;
        $rapor = null;

        DB::transaction(function () use (
            $penilaian,
            $penilaianId,
            $ksId,
            $kelas,
            $mapel,
            $tahun,
            $semester,
            $val,
            &$avgTeori,
            &$avgPraktik,
            &$rapor
        ) {
            if ($val !== null) {
                NilaiFormatifHarian::updateOrCreate([
                    'penilaian_formatif_harian_id' => $penilaianId,
                    'kelas_siswa_id' => $ksId,
                ], [
                    'nilai' => $val,
                ]);
            } else {
                NilaiFormatifHarian::where('penilaian_formatif_harian_id', $penilaianId)
                    ->where('kelas_siswa_id', $ksId)
                    ->delete();
            }

            $penilaianBulan = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
                ->where('mapel_id', $mapel->id)
                ->where('tahun', $penilaian->tahun)
                ->where('bulan', $penilaian->bulan)
                ->get();

            $nilaiSiswa = NilaiFormatifHarian::whereIn('penilaian_formatif_harian_id', $penilaianBulan->pluck('id'))
                ->where('kelas_siswa_id', $ksId)
                ->get()
                ->keyBy('penilaian_formatif_harian_id');

            $scoresTeori = [];
            $scoresPraktik = [];
            foreach ($penilaianBulan as $p) {
                $nh = $nilaiSiswa->get($p->id);
                if ($nh && $nh->nilai !== null) {
                    if ($p->jenis === 'praktik') {
                        $scoresPraktik[] = (float) $nh->nilai;
                    } else {
                        $scoresTeori[] = (float) $nh->nilai;
                    }
                }
            }

            $avgTeori = count($scoresTeori) > 0 ? round(array_sum($scoresTeori) / count($scoresTeori), 1) : null;
            $avgPraktik = count($scoresPraktik) > 0 ? round(array_sum($scoresPraktik) / count($scoresPraktik), 1) : null;

            NilaiFormatifBulanan::updateOrCreate([
                'kelas_siswa_id' => $ksId,
                'mapel_id' => $mapel->id,
                'tahun' => $penilaian->tahun,
                'bulan' => $penilaian->bulan,
            ], [
                'nilai' => $avgTeori,
                'nilai_praktik' => $avgPraktik,
            ]);

            $rapor = HitungNilaiService::syncFormatifToRaporMapel($ksId, $mapel, $tahun, $semester);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tersimpan otomatis',
            'avg_teori' => $avgTeori !== null ? HitungNilaiService::formatAngka($avgTeori) : '-',
            'avg_praktik' => $avgPraktik !== null ? HitungNilaiService::formatAngka($avgPraktik) : '-',
            'rata_formatif' => $rapor && $rapor->rata_formatif !== null ? HitungNilaiService::formatAngka((float) $rapor->rata_formatif) : '-',
            'nilai_rapor' => $rapor && $rapor->nilai_rapor !== null ? HitungNilaiService::formatAngka((float) $rapor->nilai_rapor) : null,
            'predikat' => $rapor?->predikat,
            'saved_at' => now()->format('H:i:s'),
        ]);
    }

    /**
     * Hitung rata-rata penilaian harian dan sinkronkan ke NilaiFormatifBulanan serta NilaiRaporMapel
     */
    protected function sinkronisasiRataRataBulanan(Kelas $kelas, MataPelajaran $mapel, int $tahun, int $bulan): void
    {
        $penilaianList = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->get();

        $kelasSiswaList = $kelas->kelasSiswa;
        $semester = strtolower($kelas->tahunAjaran->semester ?? 'ganjil');

        $penilaianIds = $penilaianList->pluck('id');
        $allNilai = $penilaianIds->isNotEmpty()
            ? NilaiFormatifHarian::whereIn('penilaian_formatif_harian_id', $penilaianIds)->get()
            : collect();

        foreach ($kelasSiswaList as $ks) {
            $scoresTeori = [];
            $scoresPraktik = [];

            foreach ($penilaianList as $p) {
                $nh = $allNilai->where('penilaian_formatif_harian_id', $p->id)
                    ->where('kelas_siswa_id', $ks->id)
                    ->first();

                if ($nh && $nh->nilai !== null) {
                    if ($p->jenis === 'praktik') {
                        $scoresPraktik[] = (float) $nh->nilai;
                    } else {
                        $scoresTeori[] = (float) $nh->nilai;
                    }
                }
            }

            // Jika ada penilaian harian yang pernah dibuat untuk bulan ini:
            if ($penilaianList->isNotEmpty()) {
                $avgTeori = count($scoresTeori) > 0 ? round(array_sum($scoresTeori) / count($scoresTeori), 1) : null;
                $avgPraktik = count($scoresPraktik) > 0 ? round(array_sum($scoresPraktik) / count($scoresPraktik), 1) : null;

                NilaiFormatifBulanan::updateOrCreate([
                    'kelas_siswa_id' => $ks->id,
                    'mapel_id' => $mapel->id,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                ], [
                    'nilai' => $avgTeori,
                    'nilai_praktik' => $avgPraktik,
                ]);
            }

            // Sinkronkan ke rapor mapel semester
            HitungNilaiService::syncFormatifToRaporMapel($ks->id, $mapel->id, $tahun, $semester);
        }
    }

    /**
     * Cetak Lembar Rincian Penilaian Harian (F4/A4) Siap Cetak
     */
    public function cetak(Kelas $kelas, MataPelajaran $mapel, Request $request): View
    {
        $this->authorizeAkses($kelas);

        $kelas->load(['tahunAjaran', 'wali', 'sekolah', 'mapelKkm']);
        $sekolah = $kelas->sekolah ?? Sekolah::first();
        $tahunAjaran = $kelas->tahunAjaran;

        $semester = strtolower($tahunAjaran->semester ?? 'ganjil');
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = (int) $request->input('bulan', now()->month);
        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        $kelasSiswaList = $kelas->kelasSiswa()
            ->with(['siswa'])
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $ksIds = $kelasSiswaList->pluck('id');

        $penilaianList = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $penilaianIds = $penilaianList->pluck('id');
        $allNilaiHarian = $penilaianIds->isNotEmpty()
            ? NilaiFormatifHarian::whereIn('penilaian_formatif_harian_id', $penilaianIds)
                ->whereIn('kelas_siswa_id', $ksIds)
                ->get()
            : collect();

        $matrixHarian = [];
        foreach ($allNilaiHarian as $nh) {
            $matrixHarian[$nh->penilaian_formatif_harian_id][$nh->kelas_siswa_id] = $nh->nilai;
        }

        $tanggalInput = $request->input('tanggal');
        $tanggalObj = $tanggalInput ? Carbon::parse($tanggalInput)->locale('id') : now()->locale('id');

        return view('kelas.formatif_harian.cetak', compact(
            'kelas',
            'mapel',
            'sekolah',
            'tahunAjaran',
            'kelasSiswaList',
            'penilaianList',
            'matrixHarian',
            'tahun',
            'bulan',
            'namaBulan',
            'semester',
            'tanggalObj'
        ));
    }
}
