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
            'wali_nama' => 'nullable|string|max:255',
            'wali_nip' => 'nullable|string|max:100',
        ]);

        $sekolah = Sekolah::first();

        $data = $request->only(['nama', 'fase', 'tahun_ajaran_id', 'wali_id', 'wali_nama', 'wali_nip']);
        $data['sekolah_id'] = $sekolah ? $sekolah->id : 1;

        if (empty($data['wali_nama']) && !empty($data['wali_id'])) {
            $userWali = User::find($data['wali_id']);
            if ($userWali) {
                $data['wali_nama'] = $userWali->name;
            }
        }

        Kelas::create($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(Kelas $kelas)
    {
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
            'wali_nama' => 'nullable|string|max:255',
            'wali_nip' => 'nullable|string|max:100',
        ]);

        $data = $request->only(['nama', 'fase', 'tahun_ajaran_id', 'wali_id', 'wali_nama', 'wali_nip']);

        if (empty($data['wali_nama']) && !empty($data['wali_id'])) {
            $userWali = User::find($data['wali_id']);
            if ($userWali) {
                $data['wali_nama'] = $userWali->name;
            }
        }

        $kelas->update($data);

        return redirect()->route('kelas.index')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();
        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
