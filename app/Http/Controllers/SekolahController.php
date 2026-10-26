<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
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
            'logo' => 'nullable|image|max:3072',
            'logo_kanan' => 'nullable|image|max:3072',
            'cropped_logo' => 'nullable|string',
            'cropped_logo_kanan' => 'nullable|string',
            'cropped_ttd' => 'nullable|string',
            'ttd' => 'nullable|image|max:3072',
        ]);

        $sekolah = Sekolah::first();
        if (! $sekolah) {
            $sekolah = new Sekolah;
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

        if ($request->boolean('hapus_ttd') && $sekolah->ttd) {
            if (Storage::disk('public')->exists($sekolah->ttd)) {
                Storage::disk('public')->delete($sekolah->ttd);
            }
            $data['ttd'] = null;
        }

        // Upload / Crop Logo Kiri (Pemda / Daerah)
        if ($request->filled('cropped_logo')) {
            $cropData = $request->input('cropped_logo');
            if (str_starts_with($cropData, 'data:image')) {
                @[$type, $cropData] = explode(';', $cropData);
                @[, $cropData] = explode(',', $cropData);
                $decoded = base64_decode($cropData);
                if ($decoded !== false) {
                    if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                        Storage::disk('public')->delete($sekolah->logo);
                    }
                    $filename = 'sekolah/logo_'.uniqid().'.png';
                    Storage::disk('public')->put($filename, $decoded);
                    $data['logo'] = $filename;
                }
            }
        } elseif ($request->hasFile('logo')) {
            if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = $request->file('logo')->store('sekolah', 'public');
        }

        // Upload / Crop Logo Kanan (Sekolah / Tut Wuri)
        if ($request->filled('cropped_logo_kanan')) {
            $cropData = $request->input('cropped_logo_kanan');
            if (str_starts_with($cropData, 'data:image')) {
                @[$type, $cropData] = explode(';', $cropData);
                @[, $cropData] = explode(',', $cropData);
                $decoded = base64_decode($cropData);
                if ($decoded !== false) {
                    if ($sekolah->logo_kanan && Storage::disk('public')->exists($sekolah->logo_kanan)) {
                        Storage::disk('public')->delete($sekolah->logo_kanan);
                    }
                    $filename = 'sekolah/logo_kanan_'.uniqid().'.png';
                    Storage::disk('public')->put($filename, $decoded);
                    $data['logo_kanan'] = $filename;
                }
            }
        } elseif ($request->hasFile('logo_kanan')) {
            if ($sekolah->logo_kanan && Storage::disk('public')->exists($sekolah->logo_kanan)) {
                Storage::disk('public')->delete($sekolah->logo_kanan);
            }
            $data['logo_kanan'] = $request->file('logo_kanan')->store('sekolah', 'public');
        }

        // Upload / Crop TTD / Cap Kepala Sekolah
        if ($request->filled('cropped_ttd')) {
            $cropData = $request->input('cropped_ttd');
            if (str_starts_with($cropData, 'data:image')) {
                @[$type, $cropData] = explode(';', $cropData);
                @[, $cropData] = explode(',', $cropData);
                $decoded = base64_decode($cropData);
                if ($decoded !== false) {
                    if ($sekolah->ttd && Storage::disk('public')->exists($sekolah->ttd)) {
                        Storage::disk('public')->delete($sekolah->ttd);
                    }
                    $filename = 'sekolah/ttd_'.uniqid().'.png';
                    Storage::disk('public')->put($filename, $decoded);
                    $data['ttd'] = $filename;
                }
            }
        } elseif ($request->hasFile('ttd')) {
            if ($sekolah->ttd && Storage::disk('public')->exists($sekolah->ttd)) {
                Storage::disk('public')->delete($sekolah->ttd);
            }
            $data['ttd'] = $request->file('ttd')->store('sekolah', 'public');
        }

        $sekolah->fill($data)->save();

        return redirect()->route('sekolah.edit')->with('success', 'Identitas dan Kop Sekolah berhasil diperbarui.');
    }
}
