<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KenaikanKelasController extends Controller
{
    /**
     * Tampilkan halaman utama modul Kenaikan Kelas dan Kelulusan Siswa.
     */
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'kenaikan');
        if (! in_array($activeTab, ['kenaikan', 'kelulusan'])) {
            $activeTab = 'kenaikan';
        }

        $tahunAktif = TahunAjaran::where('status', 'aktif')->first();

        // Ambil semua kelas diurutkan berdasarkan tahun ajaran dan nama kelas
        $kelasList = Kelas::with(['tahunAjaran', 'wali'])
            ->withCount('kelasSiswa')
            ->orderBy('nama', 'asc')
            ->get();

        // 1. Logika untuk Tab Kenaikan Kelas
        $kelasAsalId = $request->query('kelas_asal_id');
        if (! $kelasAsalId && $kelasList->isNotEmpty()) {
            // Pilih kelas pertama yang memiliki siswa sebagai default
            $firstWithStudents = $kelasList->first(fn ($k) => $k->kelas_siswa_count > 0);
            $kelasAsalId = $firstWithStudents ? $firstWithStudents->id : $kelasList->first()->id;
        }

        $kelasAsal = null;
        if ($kelasAsalId) {
            $kelasAsal = Kelas::with([
                'tahunAjaran',
                'wali',
                'kelasSiswa' => function ($q) {
                    $q->join('siswa', 'kelas_siswa.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama', 'asc')
                        ->select('kelas_siswa.*');
                },
                'kelasSiswa.siswa',
            ])->find($kelasAsalId);
        }

        // 2. Logika untuk Tab Kelulusan Siswa
        $kelasLulusId = $request->query('kelas_lulus_id');
        if (! $kelasLulusId && $kelasList->isNotEmpty()) {
            // Cari kelas tingkat 6 secara otomatis sebagai default
            $kelas6 = $kelasList->first(function ($k) {
                return str_contains(strtolower($k->nama), '6') || str_contains(strtolower($k->fase ?? ''), 'c');
            });
            $kelasLulusId = $kelas6 ? $kelas6->id : $kelasList->last()->id;
        }

        $kelasLulus = null;
        if ($kelasLulusId) {
            $kelasLulus = Kelas::with([
                'tahunAjaran',
                'wali',
                'kelasSiswa' => function ($q) {
                    $q->join('siswa', 'kelas_siswa.siswa_id', '=', 'siswa.id')
                        ->orderBy('siswa.nama', 'asc')
                        ->select('kelas_siswa.*');
                },
                'kelasSiswa.siswa',
            ])->find($kelasLulusId);
        }

        return view('kelas.mutasi', compact(
            'activeTab',
            'kelasList',
            'kelasAsal',
            'kelasAsalId',
            'kelasLulus',
            'kelasLulusId',
            'tahunAktif'
        ));
    }

    /**
     * Proses kenaikan kelas atau pemindahan rombel siswa secara massal.
     */
    public function prosesKenaikan(Request $request): RedirectResponse
    {
        $request->validate([
            'kelas_asal_id' => 'required|exists:kelas,id',
            'kelas_tujuan_id' => 'required|exists:kelas,id|different:kelas_asal_id',
            'siswa_ids' => 'required|array|min:1',
            'siswa_ids.*' => 'exists:siswa,id',
            'mode' => 'required|in:naik,pindah',
        ], [
            'kelas_tujuan_id.different' => 'Kelas tujuan harus berbeda dari kelas asal.',
            'siswa_ids.required' => 'Pilih minimal satu siswa yang akan dinaikkan / dipindahkan.',
        ]);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            $targetKelasId = (int) $request->input('kelas_tujuan_id');
            $asalKelasId = (int) $request->input('kelas_asal_id');
            $mode = $request->input('mode', 'naik');

            foreach ($request->siswa_ids as $siswaId) {
                // Pastikan status siswa tetap aktif
                Siswa::where('id', $siswaId)->update(['status' => 'aktif']);

                // Tempatkan ke kelas tujuan
                KelasSiswa::firstOrCreate([
                    'kelas_id' => $targetKelasId,
                    'siswa_id' => $siswaId,
                ]);

                // Jika mode pindah rombel, keluarkan siswa dari kelas asal
                if ($mode === 'pindah') {
                    KelasSiswa::where('kelas_id', $asalKelasId)
                        ->where('siswa_id', $siswaId)
                        ->delete();
                }

                $count++;
            }
        });

        $kelasAsal = Kelas::findOrFail($request->input('kelas_asal_id'));
        $kelasTujuan = Kelas::findOrFail($request->input('kelas_tujuan_id'));
        $aksiText = $request->input('mode') === 'pindah' ? 'dipindahkan' : 'dinaikkan';

        return redirect()->route('kelas.mutasi.index', [
            'tab' => 'kenaikan',
            'kelas_asal_id' => $request->input('kelas_tujuan_id'),
        ])->with('success', "Sebanyak {$count} siswa berhasil {$aksiText} dari {$kelasAsal->nama} ke {$kelasTujuan->nama}.");
    }

    /**
     * Proses penetapan status kelulusan peserta didik secara massal.
     */
    public function prosesKelulusan(Request $request): RedirectResponse
    {
        $request->validate([
            'siswa_ids' => 'required|array|min:1',
            'siswa_ids.*' => 'exists:siswa,id',
            'status' => 'required|in:lulus,aktif',
            'kelas_id' => 'nullable|exists:kelas,id',
        ], [
            'siswa_ids.required' => 'Pilih minimal satu siswa untuk diproses status kelulusannya.',
        ]);

        $status = $request->input('status', 'lulus');
        $count = 0;

        DB::transaction(function () use ($request, $status, &$count) {
            $count = Siswa::whereIn('id', $request->siswa_ids)->update(['status' => $status]);
        });

        $statusLabel = $status === 'lulus' ? 'LULUS' : 'AKTIF';

        return redirect()->route('kelas.mutasi.index', [
            'tab' => 'kelulusan',
            'kelas_lulus_id' => $request->input('kelas_id'),
        ])->with('success', "Sebanyak {$count} siswa berhasil diperbarui status keaktifannya menjadi {$statusLabel}.");
    }
}
