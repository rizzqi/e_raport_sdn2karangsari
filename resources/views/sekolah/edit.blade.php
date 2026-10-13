@extends('layouts.app')

@section('title', 'Identitas Sekolah')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-school text-emerald-600 mr-2"></i> Identitas & Kop Sekolah
            </h1>
            <p class="text-xs text-slate-500">Konfigurasi nama sekolah, alamat, kop surat, dan data kepala sekolah untuk lembar raport resmi</p>
        </div>
    </div>

    <!-- Preview Kop Surat -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center">
            <i class="fa-solid fa-eye mr-1.5 text-blue-500"></i> Pratinjau Kop Surat Raport
        </h2>
        <div class="p-6 bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl text-center font-serif text-slate-900 space-y-1">
            <p class="text-sm font-bold uppercase tracking-wide">{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</p>
            <p class="text-sm font-bold uppercase tracking-wide">{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</p>
            <p class="text-xl font-black uppercase text-emerald-800">{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</p>
            <p class="text-xs italic text-slate-600">{{ $sekolah->alamat ?? 'Jl. Karangsari, Kec. Sempu, Kab. Banyuwangi' }}</p>
            <div class="border-b-2 border-slate-900 pt-2 w-full mx-auto"></div>
        </div>
    </div>

    <!-- Form Edit Identitas -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <form action="{{ route('sekolah.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Satuan Pendidikan *</label>
                    <input type="text" name="nama" value="{{ old('nama', $sekolah->nama) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kota / Kabupaten *</label>
                    <input type="text" name="kota" value="{{ old('kota', $sekolah->kota) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Pemerintah Daerah (Kop Baris 1) *</label>
                    <input type="text" name="pemda" value="{{ old('pemda', $sekolah->pemda) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Dinas Terkait (Kop Baris 2) *</label>
                    <input type="text" name="dinas" value="{{ old('dinas', $sekolah->dinas) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Alamat Lengkap Sekolah</label>
                    <textarea name="alamat" rows="2"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">{{ old('alamat', $sekolah->alamat) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Kepala Sekolah</label>
                    <input type="text" name="kepsek" value="{{ old('kepsek', $sekolah->kepsek) }}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">NIP Kepala Sekolah</label>
                    <input type="text" name="nip" value="{{ old('nip', $sekolah->nip) }}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md text-sm transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan Identitas
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
