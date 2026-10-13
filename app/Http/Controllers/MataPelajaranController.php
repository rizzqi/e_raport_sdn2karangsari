<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MataPelajaran;
use App\Models\Pembiasaan;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $mapelList = MataPelajaran::orderBy('urutan', 'asc')->get();
        $pembiasaanList = Pembiasaan::orderBy('urutan', 'asc')->get();

        return view('mapel.index', compact('mapelList', 'pembiasaanList'));
    }

    public function storeMapel(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'urutan' => 'required|integer',
            'aktif' => 'nullable|boolean',
        ]);

        MataPelajaran::create([
            'nama' => $request->nama,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif', true),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function updateMapel(Request $request, MataPelajaran $mapel)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'urutan' => 'required|integer',
            'aktif' => 'nullable|boolean',
        ]);

        $mapel->update([
            'nama' => $request->nama,
            'urutan' => $request->urutan,
            'aktif' => $request->boolean('aktif', true),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata pelajaran berhasil diperbarui.');
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
            'aktif' => $request->boolean('aktif', true),
        ]);

        return redirect()->route('mapel.index')->with('success', 'Aspek pembiasaan berhasil diperbarui.');
    }

    public function destroyPembiasaan(Pembiasaan $pembiasaan)
    {
        $pembiasaan->delete();
        return redirect()->route('mapel.index')->with('success', 'Aspek pembiasaan berhasil dihapus.');
    }
}
