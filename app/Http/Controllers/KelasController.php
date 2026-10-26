<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $tahunAktif = TahunAjaran::aktif()->first();

        $query = Kelas::with(['wali', 'tahunAjaran', 'sekolah'])->withCount('kelasSiswa');

        if ($user->isWali()) {
            $query->where('wali_id', $user->id);
        }

        $kelasList = $query->get();
        $tahunAjaranList = TahunAjaran::orderBy('tahun', 'desc')->get();
        $waliList = User::where('role', 'wali')->get();
        $sekolah = Sekolah::first();

        return view('kelas.index', compact('kelasList', 'tahunAjaranList', 'waliList', 'sekolah', 'tahunAktif'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'fase' => 'required|string|max:50',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_id' => 'nullable|exists:users,id',
            'wali_nama' => 'nullable|string|max:255',
            'wali_nip' => 'nullable|string|max:100',
        ]);

        $sekolah = Sekolah::first();

        $data = $request->only(['nama', 'fase', 'tahun_ajaran_id', 'wali_id', 'wali_nama', 'wali_nip']);
        $data['sekolah_id'] = $sekolah ? $sekolah->id : 1;

        if (empty($data['wali_nama']) && ! empty($data['wali_id'])) {
            $userWali = User::find($data['wali_id']);
            if ($userWali) {
                $data['wali_nama'] = $userWali->name;
            }
        }

        Kelas::create($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas): View
    {
        $kelas->load(['wali', 'tahunAjaran', 'sekolah', 'kelasSiswa.siswa', 'guruPengampu.mapel', 'mapelKkm']);
        $tahunAjaranList = TahunAjaran::orderBy('tahun', 'desc')->get();
        $mapelList = $kelas->mataPelajaran();

        return view('kelas.show', compact('kelas', 'tahunAjaranList', 'mapelList'));
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'fase' => 'required|string|max:50',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_id' => 'nullable|exists:users,id',
            'wali_nama' => 'nullable|string|max:255',
            'wali_nip' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['nama', 'fase', 'tahun_ajaran_id', 'wali_id', 'wali_nama', 'wali_nip']);

        if (empty($data['wali_nama']) && ! empty($data['wali_id'])) {
            $userWali = User::find($data['wali_id']);
            if ($userWali) {
                $data['wali_nama'] = $userWali->name;
            }
        }

        $kelas->update($data);

        return redirect()->back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    /**
     * Perbarui khusus Nama dan NIP Wali Kelas (dapat diakses Admin dan Wali terkait).
     */
    public function updateWali(Request $request, Kelas $kelas): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data wali kelas ini.');
        }

        $request->validate([
            'wali_nama' => 'required|string|max:255',
            'wali_nip' => 'nullable|string|max:100',
        ]);

        $kelas->update([
            'wali_nama' => $request->input('wali_nama'),
            'wali_nip' => $request->input('wali_nip'),
        ]);

        return redirect()->back()->with('success', "Identitas Wali Kelas {$kelas->nama} (Nama & NIP) berhasil diperbarui.");
    }

    /**
     * Perbarui Tanggal Raport (STS / SAS) untuk kelas ini (dapat diakses Admin dan Wali terkait).
     */
    public function updateTanggalRaport(Request $request, Kelas $kelas): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah tanggal raport kelas ini.');
        }

        if ($request->has('periode')) {
            $validated = $request->validate([
                'periode' => 'required|in:sts,sas',
                'tanggal' => 'required|date',
            ]);

            $periode = strtolower($validated['periode']);
            $field = $periode === 'sas' ? 'tanggal_raport_sas' : 'tanggal_raport_sts';

            $kelas->update([
                $field => $validated['tanggal'],
            ]);

            $tanggalFormat = Carbon::parse($validated['tanggal'])->locale('id')->translatedFormat('d F Y');
            $labelPeriode = strtoupper($periode);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Tanggal raport {$labelPeriode} berhasil disimpan: {$tanggalFormat}.",
                    'tanggal' => $validated['tanggal'],
                    'tanggal_formatted' => $tanggalFormat,
                    'periode' => $periode,
                ]);
            }

            return redirect()->back()->with('success', "Tanggal raport {$labelPeriode} kelas {$kelas->nama} berhasil diperbarui: {$tanggalFormat}.");
        }

        $validated = $request->validate([
            'tanggal_raport_sts' => 'nullable|date',
            'tanggal_raport_sas' => 'nullable|date',
        ]);

        $kelas->update($validated);

        return redirect()->back()->with('success', "Tanggal cetak raport kelas {$kelas->nama} berhasil diperbarui.");
    }

    /**
     * Perbarui data 3 Guru Pengampu Mata Pelajaran (Non-Wali Kelas).
     */
    public function updateGuruPengampu(Request $request, Kelas $kelas): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data guru pengampu kelas ini.');
        }

        $request->validate([
            'guru' => 'required|array|min:1|max:5',
            'guru.*.mapel_id' => 'nullable|exists:mata_pelajaran,id',
            'guru.*.nama' => 'nullable|string|max:255',
            'guru.*.nip' => 'nullable|string|max:100',
        ]);

        $kelas->guruPengampu()->delete();

        foreach ($request->input('guru') as $idx => $gData) {
            $nama = trim($gData['nama'] ?? '');
            if ($nama !== '') {
                $kelas->guruPengampu()->create([
                    'mapel_id' => ! empty($gData['mapel_id']) ? $gData['mapel_id'] : null,
                    'nama' => $nama,
                    'nip' => ! empty($gData['nip']) ? trim($gData['nip']) : null,
                    'urutan' => $idx + 1,
                ]);
            }
        }

        return redirect()->back()->with('success', "Data 3 Guru Mata Pelajaran untuk {$kelas->nama} berhasil diperbarui.");
    }

    /**
     * Perbarui KKM per mata pelajaran khusus untuk rombel kelas ini.
     */
    public function updateKkmMapel(Request $request, Kelas $kelas): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isAdmin() && ! ($user->isWali() && $kelas->wali_id === $user->id)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah KKM kelas ini.');
        }

        $request->validate([
            'kkm' => 'required|array',
            'kkm.*' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($request->input('kkm') as $mapelId => $kkmNilai) {
            if ($kkmNilai === null || $kkmNilai === '') {
                $kelas->mapelKkm()->where('mapel_id', $mapelId)->delete();
            } else {
                $kelas->mapelKkm()->updateOrCreate(
                    ['mapel_id' => $mapelId],
                    ['kkm' => (int) $kkmNilai]
                );
            }
        }

        return redirect()->back()->with('success', "KKM mata pelajaran untuk rombel {$kelas->nama} berhasil disimpan.");
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Tampilkan lembar daftar hadir ujian (STS / SAS) siap cetak A4.
     */
    public function daftarHadir(Request $request, Kelas $kelas): View
    {
        $periode = strtolower($request->query('periode', 'sts'));
        if (! in_array($periode, ['sts', 'sas'])) {
            $periode = 'sts';
        }

        $mapel = $request->query('mapel');
        $ruang = $request->query('ruang');
        $tanggal = $request->query('tanggal');
        $waktu = $request->query('waktu');
        $pengawas = $request->query('pengawas');

        $kelas->load(['wali', 'tahunAjaran', 'sekolah', 'guruPengampu.mapel']);
        $siswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $sekolah = Sekolah::first() ?? $kelas->sekolah;
        $mapelList = $kelas->mataPelajaran();
        $pengawasInfo = $this->resolvePengawas($kelas, $mapel, $pengawas);

        return view('kelas.daftar_hadir', compact(
            'kelas',
            'siswaList',
            'sekolah',
            'periode',
            'mapel',
            'ruang',
            'tanggal',
            'waktu',
            'pengawas',
            'pengawasInfo',
            'mapelList'
        ));
    }

    /**
     * Unduh lembar daftar hadir ujian dalam format Excel (.xls) siap pakai.
     */
    public function downloadDaftarHadirExcel(Request $request, Kelas $kelas): Response
    {
        $periode = strtolower($request->query('periode', 'sts'));
        if (! in_array($periode, ['sts', 'sas'])) {
            $periode = 'sts';
        }

        $mapel = $request->query('mapel');
        $ruang = $request->query('ruang');
        $tanggal = $request->query('tanggal');
        $waktu = $request->query('waktu');
        $pengawas = $request->query('pengawas');

        $kelas->load(['wali', 'tahunAjaran', 'sekolah', 'guruPengampu.mapel']);
        $siswaList = $kelas->kelasSiswa()
            ->with('siswa')
            ->get()
            ->sortBy('siswa.nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $sekolah = Sekolah::first() ?? $kelas->sekolah;
        $pengawasInfo = $this->resolvePengawas($kelas, $mapel, $pengawas);

        $namaFileLabel = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $kelas->nama));
        $suffixMapel = $mapel ? '_'.strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $mapel)) : '';
        $filename = 'Daftar_Hadir_'.strtoupper($periode)."_{$namaFileLabel}{$suffixMapel}.xls";

        $html = view('kelas.daftar_hadir_excel', compact(
            'kelas',
            'siswaList',
            'sekolah',
            'periode',
            'mapel',
            'ruang',
            'tanggal',
            'waktu',
            'pengawas',
            'pengawasInfo'
        ))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Tentukan data nama dan NIP pengawas ujian.
     *
     * @return array{nama: string, nip: string, jabatan: string}
     */
    protected function resolvePengawas(Kelas $kelas, ?string $mapel, ?string $pengawasQuery): array
    {
        if (! empty($pengawasQuery)) {
            // Cek apakah cocok dengan salah satu guruPengampu
            foreach ($kelas->guruPengampu as $gp) {
                if ($gp->nama === $pengawasQuery) {
                    return [
                        'nama' => $gp->nama,
                        'nip' => $gp->nip ?: '-',
                        'jabatan' => 'Guru Pengampu / Pengawas',
                    ];
                }
            }
            // Cek apakah wali
            if ($pengawasQuery === $kelas->nama_wali) {
                return [
                    'nama' => $kelas->nama_wali,
                    'nip' => $kelas->nip_wali,
                    'jabatan' => 'Wali Kelas / Pengawas',
                ];
            }

            return [
                'nama' => $pengawasQuery,
                'nip' => '-',
                'jabatan' => 'Guru Pengawas / Pengampu',
            ];
        }

        // Jika ada mapel yang dipilih, cek otomatis ke guru pengampu mapel
        if (! empty($mapel)) {
            return $kelas->getPengawasOtomatis($mapel);
        }

        return [
            'nama' => $kelas->nama_wali,
            'nip' => $kelas->nip_wali,
            'jabatan' => 'Wali Kelas / Pengawas',
        ];
    }
}
