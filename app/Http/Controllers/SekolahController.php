<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sekolah;
use Illuminate\Support\Facades\Storage;

class SekolahController extends Controller
{
    public function edit()
    {
        $sekolah = Sekolah::firstOrCreate(
            ['nama' => 'SD NEGERI 2 KARANGSARI'],
            [
                'pemda' => 'PEMERINTAH KABUPATEN BANYUWANGI',
                'dinas' => 'DINAS PENDIDIKAN',
                'kota' => 'Banyuwangi',
                'alamat' => 'Jl. Karangsari No. 12, Sempu, Banyuwangi',
            ]
        );

        return view('sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'pemda' => 'required|string|max:255',
            'dinas' => 'required|string|max:255',
            'kota' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'kontak' => 'nullable|string|max:255',
            'kepsek' => 'nullable|string|max:255',
            'nip' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:2048',
            'logo_kanan' => 'nullable|image|max:2048',
            'ttd' => 'nullable|image|max:2048',
        ]);

        $sekolah = Sekolah::first();
        if (!$sekolah) {
            $sekolah = new Sekolah();
        }

        $data = $request->only(['nama', 'pemda', 'dinas', 'kota', 'alamat', 'kontak', 'kepsek', 'nip']);

        // Hapus logo jika diminta
        if ($request->boolean('hapus_logo') && $sekolah->logo) {
            if (Storage::disk('public')->exists($sekolah->logo)) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = null;
        }

        if ($request->boolean('hapus_logo_kanan') && $sekolah->logo_kanan) {
            if (Storage::disk('public')->exists($sekolah->logo_kanan)) {
                Storage::disk('public')->delete($sekolah->logo_kanan);
            }
            $data['logo_kanan'] = null;
        }

        // Upload Logo Kiri (Pemda / Tut Wuri Handayani)
        if ($request->hasFile('logo')) {
            if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = $request->file('logo')->store('sekolah', 'public');
        }

        // Upload Logo Kanan (Sekolah / Tut Wuri)
        if ($request->hasFile('logo_kanan')) {
            if ($sekolah->logo_kanan && Storage::disk('public')->exists($sekolah->logo_kanan)) {
                Storage::disk('public')->delete($sekolah->logo_kanan);
            }
            $data['logo_kanan'] = $request->file('logo_kanan')->store('sekolah', 'public');
        }

        // Upload TTD / Cap
        if ($request->hasFile('ttd')) {
            if ($sekolah->ttd && Storage::disk('public')->exists($sekolah->ttd)) {
                Storage::disk('public')->delete($sekolah->ttd);
            }
            $data['ttd'] = $request->file('ttd')->store('sekolah', 'public');
        }

        $sekolah->fill($data)->save();

        return redirect()->route('sekolah.edit')->with('success', 'Identitas dan Kop Sekolah berhasil diperbarui.');
    }
}
