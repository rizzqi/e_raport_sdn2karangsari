@extends('layouts.app')

@section('title', 'Identitas & Kop Raport Sekolah')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-school text-emerald-600 mr-2"></i> Identitas & Kop Surat Raport
            </h1>
            <p class="text-xs text-slate-500">Sesuaikan data identitas, teks kop, dan logo untuk lembar raport resmi standar kertas F4 / Folio</p>
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
                <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center bg-white rounded-lg border border-slate-200 overflow-hidden">
                    @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                        <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Kiri" class="max-h-16 max-w-16 object-contain">
                    @else
                        <div class="text-center text-slate-400 text-[10px] leading-tight p-1">
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
                <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center bg-white rounded-lg border border-slate-200 overflow-hidden">
                    @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                        <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Kanan" class="max-h-16 max-w-16 object-contain">
                    @else
                        <div class="text-center text-slate-400 text-[10px] leading-tight p-1">
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
        <form action="{{ route('sekolah.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

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

            <!-- Bagian 2: Logo Kop Surat (Kiri & Kanan) -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center">
                    <i class="fa-solid fa-image mr-1.5"></i> 2. Logo Kop Surat
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Logo Kiri -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Logo Kiri (Pemda / Daerah)
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">Format PNG/JPG transparan direkomendasikan, maks 2MB.</p>
                        @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                            <div class="flex items-center space-x-3 mb-3 bg-white p-2 rounded-lg border border-slate-200">
                                <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Kiri" class="h-12 w-12 object-contain">
                                <div class="text-xs flex-1">
                                    <span class="font-medium text-slate-700">Logo saat ini terpasang</span>
                                    <label class="block text-[11px] text-rose-600 mt-0.5 cursor-pointer">
                                        <input type="checkbox" name="hapus_logo" value="1" class="rounded text-rose-600 mr-1">
                                        Hapus logo kiri
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="logo" accept="image/*"
                            class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                    <!-- Logo Kanan -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Logo Kanan (Tut Wuri / Sekolah)
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">Format PNG/JPG transparan direkomendasikan, maks 2MB.</p>
                        @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                            <div class="flex items-center space-x-3 mb-3 bg-white p-2 rounded-lg border border-slate-200">
                                <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Kanan" class="h-12 w-12 object-contain">
                                <div class="text-xs flex-1">
                                    <span class="font-medium text-slate-700">Logo saat ini terpasang</span>
                                    <label class="block text-[11px] text-rose-600 mt-0.5 cursor-pointer">
                                        <input type="checkbox" name="hapus_logo_kanan" value="1" class="rounded text-rose-600 mr-1">
                                        Hapus logo kanan
                                    </label>
                                </div>
                            </div>
                        @endif
                        <input type="file" name="logo_kanan" accept="image/*"
                            class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Kepala Sekolah & Tanda Tangan -->
            <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center">
                    <i class="fa-solid fa-signature mr-1.5"></i> 3. Data Kepala Sekolah & Pengesahan Raport
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
@endsection
