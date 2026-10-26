<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\Siswa;
use Illuminate\Http\Request;

class PenempatanSiswaController extends Controller
{
    public function index(Kelas $kelas)
    {
        $kelas->load(['tahunAjaran', 'wali', 'kelasSiswa.siswa']);

        // Siswa aktif yang belum terdaftar di kelas ini
        $siswaTerdaftarIds = $kelas->kelasSiswa->pluck('siswa_id');
        $siswaTersedia = Siswa::where('status', 'aktif')
            ->whereNotIn('id', $siswaTerdaftarIds)
            ->orderBy('nama', 'asc')
            ->get();

        return view('penempatan.index', compact('kelas', 'siswaTersedia'));
    }

    public function assign(Request $request, Kelas $kelas)
    {
        $request->validate([
            'siswa_ids' => 'required|array',
            'siswa_ids.*' => 'exists:siswa,id',
        ]);

        foreach ($request->siswa_ids as $siswaId) {
            KelasSiswa::firstOrCreate([
                'kelas_id' => $kelas->id,
                'siswa_id' => $siswaId,
            ]);
        }

        return redirect()->route('penempatan.index', $kelas->id)->with('success', count($request->siswa_ids).' siswa berhasil ditempatkan ke kelas.');
    }

    public function remove(Kelas $kelas, KelasSiswa $kelasSiswa)
    {
        $kelasSiswa->delete();

        return redirect()->route('penempatan.index', $kelas->id)->with('success', 'Siswa berhasil dikeluarkan dari kelas.');
    }
}
