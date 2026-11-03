<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SekolahController extends Controller
{
    /** Ukuran maksimal gambar hasil crop base64 setelah didekode (3 MB). */
    private const MAX_IMAGE_DECODED_BYTES = 3 * 1024 * 1024;

    public function edit(): View
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

    public function update(Request $request): RedirectResponse
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
            'bobot_formatif' => 'nullable|integer|min:0|max:100',
            'bobot_uts' => 'nullable|integer|min:0|max:100',
            'bobot_uas' => 'nullable|integer|min:0|max:100',
            'hari_efektif_semester' => 'nullable|integer|min:1|max:200',
            'persen_kehadiran_minimal' => 'nullable|integer|min:1|max:100',
            'batas_alpa_maksimal' => 'nullable|integer|min:0|max:50',
            'batas_izin_maksimal' => 'nullable|integer|min:0|max:50',
        ]);

        $sekolah = Sekolah::first();
        if (! $sekolah) {
            $sekolah = new Sekolah;
        }

        $data = $request->only([
            'nama', 'pemda', 'dinas', 'kota', 'alamat', 'kontak', 'kepsek', 'nip',
            'bobot_formatif', 'bobot_uts', 'bobot_uas',
            'hari_efektif_semester', 'persen_kehadiran_minimal', 'batas_alpa_maksimal', 'batas_izin_maksimal',
        ]);

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
            $path = $this->saveCroppedImage($request->input('cropped_logo'), 'sekolah/logo_', 'cropped_logo');
            if ($path !== null) {
                if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                    Storage::disk('public')->delete($sekolah->logo);
                }
                $data['logo'] = $path;
            }
        } elseif ($request->hasFile('logo')) {
            if ($sekolah->logo && Storage::disk('public')->exists($sekolah->logo)) {
                Storage::disk('public')->delete($sekolah->logo);
            }
            $data['logo'] = $request->file('logo')->store('sekolah', 'public');
        }

        // Upload / Crop Logo Kanan (Sekolah / Tut Wuri)
        if ($request->filled('cropped_logo_kanan')) {
            $path = $this->saveCroppedImage($request->input('cropped_logo_kanan'), 'sekolah/logo_kanan_', 'cropped_logo_kanan');
            if ($path !== null) {
                if ($sekolah->logo_kanan && Storage::disk('public')->exists($sekolah->logo_kanan)) {
                    Storage::disk('public')->delete($sekolah->logo_kanan);
                }
                $data['logo_kanan'] = $path;
            }
        } elseif ($request->hasFile('logo_kanan')) {
            if ($sekolah->logo_kanan && Storage::disk('public')->exists($sekolah->logo_kanan)) {
                Storage::disk('public')->delete($sekolah->logo_kanan);
            }
            $data['logo_kanan'] = $request->file('logo_kanan')->store('sekolah', 'public');
        }

        // Upload / Crop TTD / Cap Kepala Sekolah
        if ($request->filled('cropped_ttd')) {
            $path = $this->saveCroppedImage($request->input('cropped_ttd'), 'sekolah/ttd_', 'cropped_ttd');
            if ($path !== null) {
                if ($sekolah->ttd && Storage::disk('public')->exists($sekolah->ttd)) {
                    Storage::disk('public')->delete($sekolah->ttd);
                }
                $data['ttd'] = $path;
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

    /**
     * Decode dan simpan gambar hasil crop (base64 data-URI) ke storage publik.
     *
     * Melakukan validasi biner setelah dekode untuk memastikan data adalah
     * gambar nyata (bukan file berbahaya yang disasar sebagai gambar):
     * - Memverifikasi ukuran maksimal data dekode (MAX_IMAGE_DECODED_BYTES).
     * - Memanggil getimagesizefromstring() untuk memvalidasi header biner gambar.
     *
     * @param  string  $dataUri  Nilai input berformat 'data:image/...;base64,...'
     * @param  string  $prefix  Awalan nama file (contoh: 'sekolah/logo_')
     * @param  string  $fieldName  Nama field untuk pesan error validasi
     * @return string|null Path storage jika berhasil, null jika data tidak valid
     *
     * @throws ValidationException Jika data bukan gambar valid atau melebihi batas ukuran
     */
    private function saveCroppedImage(string $dataUri, string $prefix, string $fieldName): ?string
    {
        if (! str_starts_with($dataUri, 'data:image')) {
            return null;
        }

        @[, $payload] = explode(';', $dataUri);
        @[, $base64Data] = explode(',', $payload ?? '');

        if (empty($base64Data)) {
            return null;
        }

        $decoded = base64_decode($base64Data, strict: true);
        if ($decoded === false) {
            throw ValidationException::withMessages([
                $fieldName => 'Data gambar tidak valid (gagal dekode base64).',
            ]);
        }

        if (strlen($decoded) > self::MAX_IMAGE_DECODED_BYTES) {
            throw ValidationException::withMessages([
                $fieldName => 'Ukuran gambar melebihi batas maksimal 3 MB.',
            ]);
        }

        // Validasi biner: pastikan data adalah gambar yang sebenarnya
        $imageInfo = @getimagesizefromstring($decoded);
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                $fieldName => 'File yang diunggah bukan gambar yang valid.',
            ]);
        }

        $filename = $prefix.uniqid().'.png';
        Storage::disk('public')->put($filename, $decoded);

        return $filename;
    }
}
