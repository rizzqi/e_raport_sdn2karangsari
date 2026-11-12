<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\MataPelajaran;
use App\Models\Pembiasaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $mapelList = MataPelajaran::orderBy('urutan', 'asc')->get();
        $pembiasaanList = Pembiasaan::orderBy('urutan', 'asc')->get();
        $ekskulList = Ekstrakurikuler::orderBy('urutan', 'asc')->get();

        return view('mapel.index', compact('mapelList', 'pembiasaanList', 'ekskulList'));
    }

    public function storeMapel(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kkm' => 'nullable|integer|min:0|max:100',
            'urutan' => 'required|integer',
            'target_kelas' => 'nullable|array',
            'target_kelas.*' => 'integer|in:1,2,3,4,5,6',
            'aktif' => 'nullable|boolean',
        ]);

        $targetKelas = $request->input('target_kelas');
        if (is_array($targetKelas)) {
            $targetKelas = array_values(array_map('intval', $targetKelas));
            sort($targetKelas);
            if ($targetKelas === [1, 2, 3, 4, 5, 6] || empty($targetKelas)) {
                $targetKelas = null;
            }
        } else {
            $targetKelas = null;
        }

        MataPelajaran::create([
            'nama' => $request->nama,
            'kkm' => $request->input('kkm') !== null ? (int) $request->kkm : 70,
            'target_kelas' => $targetKelas,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif', true),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function updateMapel(Request $request, MataPelajaran $mapel)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'kkm' => 'nullable|integer|min:0|max:100',
            'urutan' => 'required|integer',
            'target_kelas' => 'nullable|array',
            'target_kelas.*' => 'integer|in:1,2,3,4,5,6',
            'aktif' => 'nullable|boolean',
        ]);

        $targetKelas = $request->input('target_kelas');
        if (is_array($targetKelas)) {
            $targetKelas = array_values(array_map('intval', $targetKelas));
            sort($targetKelas);
            if ($targetKelas === [1, 2, 3, 4, 5, 6] || empty($targetKelas)) {
                $targetKelas = null;
            }
        } else {
            $targetKelas = null;
        }

        $mapel->update([
            'nama' => $request->nama,
            'kkm' => $request->input('kkm') !== null ? (int) $request->kkm : 70,
            'target_kelas' => $targetKelas,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function toggleStatusMapel(MataPelajaran $mapel)
    {
        $mapel->update([
            'aktif' => ! $mapel->aktif,
        ]);

        $status = $mapel->aktif ? 'diaktifkan kembali' : 'dinonaktifkan (disembunyikan dari penilaian & raport)';

        return redirect()->route('mapel.index')->with('success', "Mata pelajaran \"{$mapel->nama}\" berhasil {$status}.");
    }

    public function destroyMapel(MataPelajaran $mapel)
    {
        $mapel->delete();

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    public function storePembiasaan(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'urutan' => 'required|integer',
            'aktif' => 'nullable|boolean',
        ]);

        Pembiasaan::create([
            'nama' => $request->nama,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif', true),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Aspek pembiasaan berhasil ditambahkan.');
    }

    public function updatePembiasaan(Request $request, Pembiasaan $pembiasaan)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'urutan' => 'required|integer',
            'aktif' => 'nullable|boolean',
        ]);

        $pembiasaan->update([
            'nama' => $request->nama,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Aspek pembiasaan berhasil diperbarui.');
    }

    public function toggleStatusPembiasaan(Pembiasaan $pembiasaan)
    {
        $pembiasaan->update([
            'aktif' => ! $pembiasaan->aktif,
        ]);

        $status = $pembiasaan->aktif ? 'diaktifkan kembali' : 'dinonaktifkan';

        return redirect()->route('mapel.index')->with('success', "Aspek pembiasaan \"{$pembiasaan->nama}\" berhasil {$status}.");
    }

    public function destroyPembiasaan(Pembiasaan $pembiasaan)
    {
        $pembiasaan->delete();

        return redirect()->route('mapel.index')->with('success', 'Aspek pembiasaan berhasil dihapus.');
    }

    /**
     * Simpan data master kegiatan ekstrakurikuler baru.
     */
    public function storeEkstrakurikuler(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150|unique:ekstrakurikuler,nama',
            'urutan' => 'required|integer|min:0',
        ]);

        Ekstrakurikuler::create([
            'nama' => trim($validated['nama']),
            'urutan' => (int) $validated['urutan'],
            'aktif' => true,
        ]);

        return redirect()->route('mapel.index')->with('success', 'Kegiatan ekstrakurikuler berhasil ditambahkan.');
    }

    /**
     * Perbarui data master kegiatan ekstrakurikuler.
     */
    public function updateEkstrakurikuler(Request $request, Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:150|unique:ekstrakurikuler,nama,'.$ekstrakurikuler->id,
            'urutan' => 'required|integer|min:0',
        ]);

        $ekstrakurikuler->update([
            'nama' => trim($validated['nama']),
            'urutan' => (int) $validated['urutan'],
        ]);

        return redirect()->route('mapel.index')->with('success', 'Kegiatan ekstrakurikuler berhasil diperbarui.');
    }

    /**
     * Ubah status aktif/nonaktif kegiatan ekstrakurikuler.
     */
    public function toggleStatusEkstrakurikuler(Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $ekstrakurikuler->update([
            'aktif' => ! $ekstrakurikuler->aktif,
        ]);

        return redirect()->route('mapel.index')->with('success', 'Status kegiatan ekstrakurikuler berhasil diubah.');
    }

    /**
     * Hapus kegiatan ekstrakurikuler beserta catatan nilainya.
     */
    public function destroyEkstrakurikuler(Ekstrakurikuler $ekstrakurikuler): RedirectResponse
    {
        $ekstrakurikuler->delete();

        return redirect()->route('mapel.index')->with('success', 'Kegiatan ekstrakurikuler berhasil dihapus.');
    }
}
