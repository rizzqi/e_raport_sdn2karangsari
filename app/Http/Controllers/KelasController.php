<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAjaran;
use App\Models\User;

class KelasController extends Controller
{
    public function index()
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

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'fase' => 'required|string|max:10', // A, B, C
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_id' => 'nullable|exists:users,id',
        ]);

        $sekolah = Sekolah::first();

        Kelas::create([
            'nama' => $request->nama,
            'fase' => $request->fase,
            'sekolah_id' => $sekolah ? $sekolah->id : 1,
            'tahun_ajaran_id' => $request->tahun_ajaran_id,
            'wali_id' => $request->wali_id,
        ]);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas)
    {
        $user = auth()->user();
        if ($user->isWali() && $kelas->wali_id !== $user->id) {
            abort(403, 'Anda tidak berhak mengakses kelas ini.');
        }

        $kelas->load(['wali', 'tahunAjaran', 'sekolah', 'kelasSiswa.siswa']);

        return view('kelas.show', compact('kelas'));
    }

    public function update(Request $request, Kelas $kelas)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'fase' => 'required|string|max:10',
            'tahun_ajaran_id' => 'required|exists:tahun_ajaran,id',
            'wali_id' => 'nullable|exists:users,id',
        ]);

        $kelas->update($request->only(['nama', 'fase', 'tahun_ajaran_id', 'wali_id']));

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
