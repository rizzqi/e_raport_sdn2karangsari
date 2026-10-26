<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaranList = TahunAjaran::withCount('kelas')->orderBy('tahun', 'desc')->get();

        return view('tahun_ajaran.index', compact('tahunAjaranList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun' => 'required|string|max:20', // e.g. 2026/2027
            'semester' => 'required|in:ganjil,genap',
            'aktif' => 'nullable|boolean',
        ]);

        $aktif = $request->boolean('aktif');

        if ($aktif) {
            TahunAjaran::query()->update(['aktif' => false]);
        }

        TahunAjaran::create([
            'tahun' => $request->tahun,
            'semester' => $request->semester,
            'aktif' => $aktif,
        ]);

        return redirect()->route('tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function setAktif(TahunAjaran $tahunAjaran)
    {
        TahunAjaran::query()->update(['aktif' => false]);
        $tahunAjaran->update(['aktif' => true]);

        return redirect()->route('tahun-ajaran.index')->with('success', "Tahun ajaran {$tahunAjaran->nama_lengkap} diaktifkan.");
    }

    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->aktif) {
            return back()->with('error', 'Tidak dapat menghapus tahun ajaran yang sedang aktif.');
        }

        $tahunAjaran->delete();

        return redirect()->route('tahun-ajaran.index')->with('success', 'Tahun ajaran berhasil dihapus.');
    }
}
