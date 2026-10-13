<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\KelasSiswa;
use App\Models\TahunAjaran;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Siswa::with(['kelasSiswa.kelas.tahunAjaran']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $siswaList = $query->orderBy('nama', 'asc')->paginate(20);

        return view('siswa.index', compact('siswaList', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:siswa,nis',
            'nisn' => 'nullable|string|max:50',
            'status' => 'required|in:aktif,lulus',
        ]);

        Siswa::create($request->only(['nama', 'nis', 'nisn', 'status']));

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'nis' => 'required|string|max:50|unique:siswa,nis,' . $siswa->id,
            'nisn' => 'nullable|string|max:50',
            'status' => 'required|in:aktif,lulus',
        ]);

        $siswa->update($request->only(['nama', 'nis', 'nisn', 'status']));

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Siswa $siswa)
    {
        $siswa->delete();
        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Impor cepat data siswa dari teks / clipboard (Format: Nama, NIS, NISN)
     */
    public function importClipboard(Request $request)
    {
        $request->validate([
            'data_teks' => 'required|string',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        $lines = explode("\n", str_replace("\r", "", $request->data_teks));
        $suksesCount = 0;
        $duplikatCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Pisahkan berdasarkan tab atau koma atau titik koma
            $delimiter = strpos($line, "\t") !== false ? "\t" : (strpos($line, ";") !== false ? ";" : ",");
            $parts = array_map('trim', explode($delimiter, $line));

            $nama = $parts[0] ?? null;
            $nis = $parts[1] ?? null;
            $nisn = $parts[2] ?? null;

            if ($nama && $nis) {
                $siswa = Siswa::where('nis', $nis)->first();
                if (!$siswa) {
                    $siswa = Siswa::create([
                        'nama' => $nama,
                        'nis' => $nis,
                        'nisn' => $nisn,
                        'status' => 'aktif',
                    ]);
                    $suksesCount++;
                } else {
                    $duplikatCount++;
                }

                if ($request->kelas_id) {
                    KelasSiswa::firstOrCreate([
                        'kelas_id' => $request->kelas_id,
                        'siswa_id' => $siswa->id,
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', "Impor selesai: {$suksesCount} siswa baru berhasil ditambahkan" . ($duplikatCount > 0 ? " ({$duplikatCount} NIS sudah ada)." : "."));
    }
}
