<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\MataPelajaran;
use App\Models\NilaiFormatifBulanan;
use App\Models\NilaiFormatifHarian;
use App\Models\PenilaianFormatifHarian;
use App\Models\Sekolah;
use App\Services\HitungNilaiService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'isAgama'
        ));
    }

    /**
     * Tambah Kolom Penilaian Harian Baru
     */
    public function tambahPenilaian(Kelas $kelas, MataPelajaran $mapel, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tanggal' => ['nullable', 'date'],
            'lingkup_materi' => ['nullable', 'string', 'max:255'],
            'jenis' => ['nullable', 'in:teori,praktik'],
            'bobot' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'min:2020'],
        ]);

        $tahun = (int) $request->input('tahun');
        $bulan = (int) $request->input('bulan');

        $count = PenilaianFormatifHarian::where('kelas_id', $kelas->id)
            ->where('mapel_id', $mapel->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->count();

        $penilaian = PenilaianFormatifHarian::create([
            'kelas_id' => $kelas->id,
            'mapel_id' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
            'nama' => trim($request->input('nama')),
            'lingkup_materi' => $request->input('lingkup_materi') ? trim($request->input('lingkup_materi')) : null,
            'tanggal' => $request->input('tanggal') ?: null,
            'bobot' => (float) $request->input('bobot', 1.0),
            'jenis' => $request->input('jenis', 'teori'),
            'urutan' => $count + 1,
        ]);

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', "Penilaian Harian '{$penilaian->nama}' berhasil ditambahkan ke Bulan " . (NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? $bulan) . '.');
    }

    /**
     * Update Data Penilaian Harian (Nama, Tanggal, Lingkup Materi)
     */
    public function updatePenilaian(Kelas $kelas, MataPelajaran $mapel, PenilaianFormatifHarian $penilaian, Request $request): RedirectResponse
    {
        $this->authorizeAkses($kelas);

        if ($penilaian->kelas_id !== $kelas->id || $penilaian->mapel_id !== $mapel->id) {
            abort(404);
        }

        $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'tanggal' => ['nullable', 'date'],
            'lingkup_materi' => ['nullable', 'string', 'max:255'],
            'jenis' => ['nullable', 'in:teori,praktik'],
            'bobot' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
        ]);

        $penilaian->update([
            'nama' => trim($request->input('nama')),
            'tanggal' => $request->input('tanggal') ?: null,
            'lingkup_materi' => $request->input('lingkup_materi') ? trim($request->input('lingkup_materi')) : null,
            'jenis' => $request->input('jenis', $penilaian->jenis),
            'bobot' => (float) $request->input('bobot', $penilaian->bobot),
        ]);

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $penilaian->tahun,
            'bulan' => $penilaian->bulan,
        ])->with('success', "Informasi penilaian '{$penilaian->nama}' berhasil diperbarui.");
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

        $namaBulan = NilaiFormatifBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";

        return redirect()->route('kelas.mapel.formatif-harian', [
            'kelas' => $kelas->id,
            'mapel' => $mapel->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ])->with('success', "Nilai Penilaian Harian berhasil disimpan! Rata-rata Formatif Bulan {$namaBulan} otomatis diperbarui.");
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
