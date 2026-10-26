@extends('layouts.app')

@section('title', 'Identitas & Kop Raport Sekolah')

@push('styles')
<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<style>
    .cropper-view-box,
    .cropper-face {
        border-radius: 0;
    }
    .preview-box {
        overflow: hidden;
        background-color: #f8fafc;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-school text-emerald-600 mr-2"></i> Identitas & Kop Surat Raport
            </h1>
            <p class="text-xs text-slate-500">Sesuaikan data identitas, teks kop, dan logo (dengan fitur crop interaktif) untuk lembar raport F4 / Folio</p>
        </div>
        <div class="inline-flex items-center px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold">
            <i class="fa-solid fa-file-lines text-amber-600 mr-2"></i> Kertas Raport: F4 / Folio (215 × 330 mm)
        </div>
    </div>

    <!-- Preview Kop Surat -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center">
                <i class="fa-solid fa-eye mr-1.5 text-blue-500"></i> Pratinjau Kop Surat Raport (F4 / Folio)
            </h2>
            <span class="text-[11px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-mono">Format Kop Resmi</span>
        </div>

        <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo Kiri -->
                <div id="kop_container_logo_kiri" class="w-20 h-20 flex-shrink-0 flex items-center justify-center bg-white rounded-lg border border-slate-200 overflow-hidden">
                    @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                        <img id="kop_img_logo_kiri" src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Kiri" class="max-h-16 max-w-16 object-contain">
                    @else
                        <div id="kop_placeholder_logo_kiri" class="text-center text-slate-400 text-[10px] leading-tight p-1">
                            <i class="fa-solid fa-landmark text-lg block mb-0.5 text-slate-300"></i>
                            Logo Pemda
                        </div>
                    @endif
                </div>

                <!-- Teks Kop Tengah -->
                <div class="flex-1 text-center font-serif text-slate-900 space-y-0.5">
                    <p class="text-xs sm:text-sm font-bold uppercase tracking-wide leading-tight">
                        {{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}
                    </p>
                    <p class="text-xs sm:text-sm font-bold uppercase tracking-wide leading-tight">
                        {{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}
                    </p>
                    <p class="text-base sm:text-lg font-black uppercase text-slate-900 tracking-wider leading-tight">
                        {{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
                    </p>
                    <p class="text-[11px] sm:text-xs italic text-slate-600 leading-tight">
                        {{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}
                    </p>
                    @if($sekolah->kontak)
                        <p class="text-[10px] text-slate-500 leading-tight">
                            {{ $sekolah->kontak }}
                        </p>
                    @endif
                </div>

                <!-- Logo Kanan -->
                <div id="kop_container_logo_kanan" class="w-20 h-20 flex-shrink-0 flex items-center justify-center bg-white rounded-lg border border-slate-200 overflow-hidden">
                    @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                        <img id="kop_img_logo_kanan" src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Kanan" class="max-h-16 max-w-16 object-contain">
                    @else
                        <div id="kop_placeholder_logo_kanan" class="text-center text-slate-400 text-[10px] leading-tight p-1">
                            <i class="fa-solid fa-graduation-cap text-lg block mb-0.5 text-slate-300"></i>
                            Logo Sekolah
                        </div>
                    @endif
                </div>
            </div>
            <!-- Garis Double Tebal Kop Standar Surat Resmi -->
            <div class="mt-4 border-b-4 border-double border-slate-900"></div>
        </div>
    </div>

    <!-- Form Edit Identitas & Kop -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <form action="{{ route('sekolah.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="formSekolah">
            @csrf

            <!-- Hidden inputs untuk menyimpan base64 hasil crop -->
            <input type="hidden" name="cropped_logo" id="cropped_logo" value="">
            <input type="hidden" name="cropped_logo_kanan" id="cropped_logo_kanan" value="">
            <input type="hidden" name="cropped_ttd" id="cropped_ttd" value="">

            <!-- Bagian 1: Teks Kop Surat -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center">
                    <i class="fa-solid fa-heading mr-1.5"></i> 1. Teks Kop Surat Raport
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Pemerintah Daerah (Kop Baris 1) *
                        </label>
                        <input type="text" name="pemda" value="{{ old('pemda', $sekolah->pemda) }}" required
                            placeholder="Contoh: PEMERINTAH KABUPATEN BANYUWANGI"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Dinas Terkait (Kop Baris 2) *
                        </label>
                        <input type="text" name="dinas" value="{{ old('dinas', $sekolah->dinas) }}" required
                            placeholder="Contoh: DINAS PENDIDIKAN"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nama Satuan Pendidikan (Kop Baris 3) *
                        </label>
                        <input type="text" name="nama" value="{{ old('nama', $sekolah->nama) }}" required
                            placeholder="Contoh: SD NEGERI 2 KARANGSARI"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-bold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kota / Kabupaten Titimangsa *
                        </label>
                        <input type="text" name="kota" value="{{ old('kota', $sekolah->kota) }}" required
                            placeholder="Contoh: Banyuwangi"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <span class="text-[10px] text-slate-500">Digunakan pada tempat titimangsa tanda tangan (cth: Banyuwangi, 13 Oktober 2026)</span>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Alamat Lengkap Sekolah (Kop Baris 4)
                        </label>
                        <textarea name="alamat" rows="2" placeholder="Contoh: Jl. Karangsari No. 12, Desa Karangsari, Kec. Sempu"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">{{ old('alamat', $sekolah->alamat) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Kontak / Email / Website / Kode Pos (Kop Baris 5)
                        </label>
                        <input type="text" name="kontak" value="{{ old('kontak', $sekolah->kontak) }}"
                            placeholder="Contoh: Kode Pos 68468 | Telp: (0333) 123456 | Email: sdn2karangsari@gmail.com"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <span class="text-[10px] text-slate-500">Teks kontak tambahan di bawah alamat pada lembar kop raport</span>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Logo Kop Surat (Kiri & Kanan dengan Fitur Crop) -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 flex items-center">
                        <i class="fa-solid fa-crop-simple mr-1.5"></i> 2. Logo Kop Surat (Upload & Crop Otomatis)
                    </h3>
                    <span class="text-[11px] text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full font-medium">
                        <i class="fa-solid fa-scissors mr-1"></i> Mode Crop Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Logo Kiri -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Logo Kiri (Pemda / Daerah)
                                </label>
                                <span id="badge_status_logo" class="hidden text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-circle-check mr-0.5"></i> Tercrop
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mb-2">Pilih gambar lalu sesuaikan area crop agar pas di kop surat.</p>

                            <!-- Area Card Thumbnail Preview -->
                            <div id="card_preview_logo" class="flex items-center space-x-3 mb-3 bg-white p-2.5 rounded-xl border border-slate-200">
                                <div class="w-14 h-14 bg-slate-100 rounded-lg flex items-center justify-center overflow-hidden border border-slate-200 flex-shrink-0">
                                    @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                                        <img id="thumb_logo" src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Kiri" class="max-h-12 max-w-12 object-contain">
                                    @else
                                        <img id="thumb_logo" src="" alt="Logo Kiri" class="hidden max-h-12 max-w-12 object-contain">
                                        <i id="thumb_icon_logo" class="fa-solid fa-landmark text-slate-300 text-xl"></i>
                                    @endif
                                </div>
                                <div class="text-xs flex-1">
                                    <div class="flex items-center justify-between">
                                        <span id="label_logo" class="font-medium text-slate-700">
                                            {{ $sekolah->logo ? 'Logo saat ini' : 'Belum ada logo' }}
                                        </span>
                                        <button type="button" id="btn_recrop_logo" onclick="reCropExisting('logo')"
                                            class="{{ $sekolah->logo ? '' : 'hidden' }} px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-crop mr-1"></i> Sesuaikan
                                        </button>
                                    </div>
                                    @if($sekolah->logo)
                                        <label class="block text-[11px] text-rose-600 mt-1 cursor-pointer">
                                            <input type="checkbox" name="hapus_logo" value="1" class="rounded text-rose-600 mr-1">
                                            Hapus logo kiri
                                        </label>
                                    @endif
                                </div>
                            </div>

                            <!-- Input File -->
                            <input type="file" id="file_logo" name="logo" accept="image/*"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>

                    <!-- Logo Kanan -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex flex-col justify-between space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                    Logo Kanan (Tut Wuri / Sekolah)
                                </label>
                                <span id="badge_status_logo_kanan" class="hidden text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                    <i class="fa-solid fa-circle-check mr-0.5"></i> Tercrop
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 mb-2">Pilih gambar lalu sesuaikan area crop agar pas di kop surat.</p>

                            <!-- Area Card Thumbnail Preview -->
                            <div id="card_preview_logo_kanan" class="flex items-center space-x-3 mb-3 bg-white p-2.5 rounded-xl border border-slate-200">
                                <div class="w-14 h-14 bg-slate-100 rounded-lg flex items-center justify-center overflow-hidden border border-slate-200 flex-shrink-0">
                                    @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                                        <img id="thumb_logo_kanan" src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Kanan" class="max-h-12 max-w-12 object-contain">
                                    @else
                                        <img id="thumb_logo_kanan" src="" alt="Logo Kanan" class="hidden max-h-12 max-w-12 object-contain">
                                        <i id="thumb_icon_logo_kanan" class="fa-solid fa-graduation-cap text-slate-300 text-xl"></i>
                                    @endif
                                </div>
                                <div class="text-xs flex-1">
                                    <div class="flex items-center justify-between">
                                        <span id="label_logo_kanan" class="font-medium text-slate-700">
                                            {{ $sekolah->logo_kanan ? 'Logo saat ini' : 'Belum ada logo' }}
                                        </span>
                                        <button type="button" id="btn_recrop_logo_kanan" onclick="reCropExisting('logo_kanan')"
                                            class="{{ $sekolah->logo_kanan ? '' : 'hidden' }} px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-semibold transition">
                                            <i class="fa-solid fa-crop mr-1"></i> Sesuaikan
                                        </button>
                                    </div>
                                    @if($sekolah->logo_kanan)
                                        <label class="block text-[11px] text-rose-600 mt-1 cursor-pointer">
                                            <input type="checkbox" name="hapus_logo_kanan" value="1" class="rounded text-rose-600 mr-1">
                                            Hapus logo kanan
                                        </label>
                                    @endif
                                </div>
                            </div>

                            <!-- Input File -->
                            <input type="file" id="file_logo_kanan" name="logo_kanan" accept="image/*"
                                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Kepala Sekolah & Tanda Tangan -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center">
                    <i class="fa-solid fa-signature mr-1.5"></i> 3. Data Kepala Sekolah & Tanda Tangan Digital Raport
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Kepala Sekolah</label>
                        <input type="text" name="kepsek" value="{{ old('kepsek', $sekolah->kepsek) }}"
                            placeholder="Contoh: Drs. H. Ahmad Sudrajat, M.Pd"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-semibold">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">NIP Kepala Sekolah</label>
                        <input type="text" name="nip" value="{{ old('nip', $sekolah->nip) }}"
                            placeholder="Contoh: 19750812 200003 1 004"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
                    </div>

                    <!-- Scan / Upload Tanda Tangan & Stempel Digital Kepsek -->
                    <div class="md:col-span-2 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Scan / Upload Tanda Tangan Digital Kepala Sekolah
                            </label>
                            <span id="badge_status_ttd" class="hidden text-[10px] font-semibold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full">
                                <i class="fa-solid fa-circle-check mr-0.5"></i> TTD Siap Disimpan
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">
                            Unggah foto/scan tanda tangan & cap stempel kepala sekolah (format PNG transparan atau JPG). Gunakan fitur crop untuk menyesuaikan ukuran agar tercetak proporsional pada raport resmi.
                        </p>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <!-- Area Preview TTD -->
                            <div class="w-44 h-24 bg-white rounded-xl border-2 border-dashed border-slate-300 flex items-center justify-center p-2 overflow-hidden shadow-2xs shrink-0">
                                @if($sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                                    <img id="thumb_ttd" src="{{ asset('storage/' . $sekolah->ttd) }}" alt="Tanda Tangan Kepsek" class="max-h-20 max-w-full object-contain">
                                @else
                                    <img id="thumb_ttd" src="" alt="Tanda Tangan Kepsek" class="hidden max-h-20 max-w-full object-contain">
                                    <div id="thumb_placeholder_ttd" class="text-center text-slate-400">
                                        <i class="fa-solid fa-signature text-2xl block mb-1 text-slate-300"></i>
                                        <span class="text-[10px]">Belum ada TTD</span>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1 space-y-2 w-full">
                                <div class="flex items-center gap-2">
                                    <input type="file" id="file_ttd" name="ttd" accept="image/*"
                                        class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">

                                    <button type="button" id="btn_recrop_ttd" onclick="reCropExisting('ttd')"
                                        class="{{ $sekolah->ttd ? '' : 'hidden' }} shrink-0 px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-xs font-semibold transition">
                                        <i class="fa-solid fa-crop mr-1"></i> Sesuaikan
                                    </button>
                                </div>

                                @if($sekolah->ttd)
                                    <label class="inline-flex items-center text-xs text-rose-600 cursor-pointer pt-1">
                                        <input type="checkbox" name="hapus_ttd" value="1" class="rounded text-rose-600 mr-1.5 border-slate-300 focus:ring-rose-500">
                                        <span>Hapus tanda tangan digital (kembali ke tanda tangan manual/basah)</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-xs text-slate-500 flex items-center">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1.5"></i>
                    Perubahan langsung berlaku pada lembar Cetak & Unduh Raport F4.
                </div>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md text-sm transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan Identitas & Kop
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Modal Dialog Crop Image -->
<div id="cropModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col max-h-[92vh]">
        <!-- Header Modal -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 id="cropModalTitle" class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-crop-simple text-emerald-600 mr-2"></i> Crop Logo Kop Raport
                </h3>
                <p class="text-[11px] text-slate-500">Sesuaikan ukuran dan orientasi agar logo proporsional di kop surat resmi</p>
            </div>
            <button type="button" onclick="closeCropModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200/60 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-4 sm:p-5 flex flex-col md:flex-row gap-4 sm:gap-5 items-center justify-center overflow-y-auto">
            <!-- Box Canvas Cropper -->
            <div class="w-full md:w-3/5 h-64 sm:h-80 bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center relative">
                <img id="cropperImage" src="" alt="Gambar Crop" class="max-w-full max-h-full block">
            </div>

            <!-- Panel Kontrol & Mini Preview -->
            <div class="w-full md:w-2/5 flex flex-col space-y-3.5">
                <!-- Mini Preview Box -->
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-center">
                    <span class="text-[10px] font-bold uppercase text-slate-500 tracking-wider block mb-1.5">Pratinjau Hasil Crop</span>
                    <div class="w-24 h-24 mx-auto rounded-xl border-2 border-dashed border-emerald-500 bg-white overflow-hidden flex items-center justify-center shadow-sm preview-box">
                        <div id="cropperPreview" class="w-full h-full overflow-hidden"></div>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">Ukuran di Kop: proporsional</span>
                </div>

                <!-- Aspect Ratio Selector -->
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-600 tracking-wider block mb-1.5">Bentuk Area Crop</span>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="setCropRatio(1)" id="btnRatioSquare"
                            class="px-2.5 py-1.5 rounded-lg font-bold border border-emerald-600 bg-emerald-50 text-emerald-800 transition text-center">
                            1:1 Persegi
                        </button>
                        <button type="button" onclick="setCropRatio(NaN)" id="btnRatioFree"
                            class="px-2.5 py-1.5 rounded-lg font-medium border border-slate-200 hover:bg-slate-50 text-slate-700 transition text-center">
                            Bebas
                        </button>
                    </div>
                </div>

                <!-- Transform Controls -->
                <div>
                    <span class="text-[10px] font-bold uppercase text-slate-600 tracking-wider block mb-1.5">Alat Bantu</span>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button type="button" onclick="cropperZoom(0.1)" title="Perbesar" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </button>
                        <button type="button" onclick="cropperZoom(-0.1)" title="Perkecil" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                            <i class="fa-solid fa-magnifying-glass-minus"></i>
                        </button>
                        <button type="button" onclick="cropperRotate(-90)" title="Putar Kiri 90°" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                            <i class="fa-solid fa-rotate-left mr-1"></i> -90°
                        </button>
                        <button type="button" onclick="cropperRotate(90)" title="Putar Kanan 90°" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                            <i class="fa-solid fa-rotate-right mr-1"></i> +90°
                        </button>
                        <button type="button" onclick="cropperReset()" title="Reset" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                            <i class="fa-solid fa-arrows-rotate mr-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <button type="button" onclick="closeCropModal()" class="px-4 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 transition">
                Batal
            </button>
            <button type="button" onclick="applyCroppedImage()" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center">
                <i class="fa-solid fa-check mr-1.5"></i> Terapkan Hasil Crop
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Cropper.js JavaScript CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

<script>
    let cropper = null;
    let currentTarget = null; // 'logo', 'logo_kanan', atau 'ttd'
    let lastLoadedImageSource = {
        logo: document.getElementById('thumb_logo') ? document.getElementById('thumb_logo').src : '',
        logo_kanan: document.getElementById('thumb_logo_kanan') ? document.getElementById('thumb_logo_kanan').src : '',
        ttd: document.getElementById('thumb_ttd') ? document.getElementById('thumb_ttd').src : ''
    };

    const modal = document.getElementById('cropModal');
    const cropperImage = document.getElementById('cropperImage');
    const modalTitle = document.getElementById('cropModalTitle');
    const btnSquare = document.getElementById('btnRatioSquare');
    const btnFree = document.getElementById('btnRatioFree');

    // Hubungkan input file ke fungsi crop
    document.getElementById('file_logo').addEventListener('change', function(e) {
        handleFileSelect(e, 'logo');
    });

    document.getElementById('file_logo_kanan').addEventListener('change', function(e) {
        handleFileSelect(e, 'logo_kanan');
    });

    document.getElementById('file_ttd')?.addEventListener('change', function(e) {
        handleFileSelect(e, 'ttd');
    });

    function handleFileSelect(e, targetType) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            if (!file.type.startsWith('image/')) {
                alert('Silakan pilih file gambar yang valid (PNG, JPG, JPEG).');
                return;
            }
            const reader = new FileReader();
            reader.onload = function(evt) {
                lastLoadedImageSource[targetType] = evt.target.result;
                openCropModal(evt.target.result, targetType);
            };
            reader.readAsDataURL(file);
        }
    }

    function reCropExisting(targetType) {
        let src = lastLoadedImageSource[targetType];
        if (!src) {
            let thumbId = 'thumb_logo';
            if (targetType === 'logo_kanan') thumbId = 'thumb_logo_kanan';
            if (targetType === 'ttd') thumbId = 'thumb_ttd';

            const thumb = document.getElementById(thumbId);
            if (thumb && thumb.src) {
                src = thumb.src;
            }
        }
        if (src) {
            openCropModal(src, targetType);
        }
    }

    function openCropModal(imageSrc, targetType) {
        currentTarget = targetType;
        if (targetType === 'logo') {
            modalTitle.innerHTML = '<i class="fa-solid fa-landmark text-emerald-600 mr-2"></i> Crop Logo Kiri (Pemda / Daerah)';
        } else if (targetType === 'logo_kanan') {
            modalTitle.innerHTML = '<i class="fa-solid fa-graduation-cap text-emerald-600 mr-2"></i> Crop Logo Kanan (Sekolah / Tut Wuri)';
        } else if (targetType === 'ttd') {
            modalTitle.innerHTML = '<i class="fa-solid fa-signature text-emerald-600 mr-2"></i> Crop Tanda Tangan & Stempel Kepala Sekolah';
        }

        // Tampilkan modal
        modal.classList.remove('hidden');

        // Reset Cropper sebelumnya jika ada
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        cropperImage.src = imageSrc;

        const defaultRatio = targetType === 'ttd' ? NaN : 1;

        // Inisialisasi Cropper.js
        cropper = new Cropper(cropperImage, {
            aspectRatio: defaultRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.9,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            preview: '#cropperPreview',
            ready: function () {
                setCropRatio(defaultRatio);
            }
        });
    }

    function closeCropModal() {
        modal.classList.add('hidden');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function setCropRatio(ratio) {
        if (!cropper) return;
        cropper.setAspectRatio(ratio);
        if (isNaN(ratio)) {
            btnFree.className = 'px-2.5 py-1.5 rounded-lg font-bold border border-emerald-600 bg-emerald-50 text-emerald-800 transition text-center';
            btnSquare.className = 'px-2.5 py-1.5 rounded-lg font-medium border border-slate-200 hover:bg-slate-50 text-slate-700 transition text-center';
        } else {
            btnSquare.className = 'px-2.5 py-1.5 rounded-lg font-bold border border-emerald-600 bg-emerald-50 text-emerald-800 transition text-center';
            btnFree.className = 'px-2.5 py-1.5 rounded-lg font-medium border border-slate-200 hover:bg-slate-50 text-slate-700 transition text-center';
        }
    }

    function cropperZoom(val) {
        if (cropper) cropper.zoom(val);
    }

    function cropperRotate(deg) {
        if (cropper) cropper.rotate(deg);
    }

    function cropperReset() {
        if (cropper) cropper.reset();
    }

    function applyCroppedImage() {
        if (!cropper || !currentTarget) return;

        // Dapatkan gambar hasil crop dalam resolusi yang jernih
        const canvas = cropper.getCroppedCanvas({
            width: currentTarget === 'ttd' ? 480 : 320,
            height: currentTarget === 'ttd' ? 240 : 320,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        const croppedBase64 = canvas.toDataURL('image/png');

        if (currentTarget === 'logo') {
            // 1. Simpan ke hidden input
            document.getElementById('cropped_logo').value = croppedBase64;

            // 2. Update thumbnail pada form
            const thumb = document.getElementById('thumb_logo');
            thumb.src = croppedBase64;
            thumb.classList.remove('hidden');
            const icon = document.getElementById('thumb_icon_logo');
            if (icon) icon.classList.add('hidden');

            document.getElementById('badge_status_logo').classList.remove('hidden');
            document.getElementById('label_logo').textContent = 'Hasil crop siap disimpan';
            document.getElementById('btn_recrop_logo').classList.remove('hidden');

            // 3. Update pratinjau Kop Surat di atas secara live
            const kopContainer = document.getElementById('kop_container_logo_kiri');
            let kopImg = document.getElementById('kop_img_logo_kiri');
            if (!kopImg) {
                const placeholder = document.getElementById('kop_placeholder_logo_kiri');
                if (placeholder) placeholder.remove();
                kopImg = document.createElement('img');
                kopImg.id = 'kop_img_logo_kiri';
                kopImg.alt = 'Logo Kiri';
                kopImg.className = 'max-h-16 max-w-16 object-contain';
                kopContainer.appendChild(kopImg);
            }
            kopImg.src = croppedBase64;

        } else if (currentTarget === 'logo_kanan') {
            // 1. Simpan ke hidden input
            document.getElementById('cropped_logo_kanan').value = croppedBase64;

            // 2. Update thumbnail pada form
            const thumb = document.getElementById('thumb_logo_kanan');
            thumb.src = croppedBase64;
            thumb.classList.remove('hidden');
            const icon = document.getElementById('thumb_icon_logo_kanan');
            if (icon) icon.classList.add('hidden');

            document.getElementById('badge_status_logo_kanan').classList.remove('hidden');
            document.getElementById('label_logo_kanan').textContent = 'Hasil crop siap disimpan';
            document.getElementById('btn_recrop_logo_kanan').classList.remove('hidden');

            // 3. Update pratinjau Kop Surat di atas secara live
            const kopContainer = document.getElementById('kop_container_logo_kanan');
            let kopImg = document.getElementById('kop_img_logo_kanan');
            if (!kopImg) {
                const placeholder = document.getElementById('kop_placeholder_logo_kanan');
                if (placeholder) placeholder.remove();
                kopImg = document.createElement('img');
                kopImg.id = 'kop_img_logo_kanan';
                kopImg.alt = 'Logo Kanan';
                kopImg.className = 'max-h-16 max-w-16 object-contain';
                kopContainer.appendChild(kopImg);
            }
            kopImg.src = croppedBase64;

        } else if (currentTarget === 'ttd') {
            // 1. Simpan ke hidden input
            document.getElementById('cropped_ttd').value = croppedBase64;

            // 2. Update thumbnail pada form
            const thumb = document.getElementById('thumb_ttd');
            thumb.src = croppedBase64;
            thumb.classList.remove('hidden');
            const placeholder = document.getElementById('thumb_placeholder_ttd');
            if (placeholder) placeholder.classList.add('hidden');

            const badge = document.getElementById('badge_status_ttd');
            if (badge) badge.classList.remove('hidden');
            const btnRecrop = document.getElementById('btn_recrop_ttd');
            if (btnRecrop) btnRecrop.classList.remove('hidden');
        }

        closeCropModal();
    }
</script>
@endpush
