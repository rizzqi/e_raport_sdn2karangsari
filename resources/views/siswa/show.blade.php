@extends('layouts.app')

@section('title', 'Detail Siswa - ' . $siswa->nama)

@section('content')
<div class="space-y-6">

    <!-- Top Navigation / Breadcrumbs -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs sm:text-sm text-slate-500">
            <a href="{{ route('siswa.index') }}" class="hover:text-emerald-700 transition flex items-center gap-1.5 font-medium">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Kembali ke Daftar Siswa</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-800 font-semibold truncate max-w-[200px] sm:max-w-xs">{{ $siswa->nama }}</span>
        </div>

        @if(auth()->user()->isAdmin())
            <button type="button" onclick="document.getElementById('modalEditSiswa').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm shadow-emerald-600/20 transition">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit Siswa</span>
            </button>
        @endif
    </div>

    <!-- Header Profil Siswa -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white font-black text-2xl sm:text-3xl flex items-center justify-center shadow-lg shadow-emerald-600/20 shrink-0">
                    {{ strtoupper(substr($siswa->nama, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $siswa->status === 'aktif' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                            {{ ucfirst($siswa->status) }}
                        </span>
                        @if($kelasAktif)
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                                <i class="fa-solid fa-chalkboard-user mr-1 text-blue-500"></i>
                                {{ $kelasAktif->kelas->nama ?? 'Kelas' }}
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                Belum Masuk Rombel Aktif
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug">
                        {{ $siswa->nama }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 font-mono mt-0.5">
                        NIS: <strong class="text-slate-800 font-bold">{{ $siswa->nis }}</strong>
                        <span class="text-slate-300 mx-1">&bull;</span>
                        NISN: <span class="text-slate-700">{{ $siswa->nisn ?: '-' }}</span>
                    </p>
                </div>
            </div>

            <!-- Action Quick Link ke Raport / Nilai jika ada kelas aktif -->
            @if($kelasAktif)
                <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('raport.preview', $kelasAktif->id) }}"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition">
                        <i class="fa-solid fa-file-invoice text-emerald-400"></i>
                        <span>Lihat Raport</span>
                    </a>
                    <a href="{{ route('nilai.input.siswa', ['kelas' => $kelasAktif->kelas_id, 'kelasSiswa' => $kelasAktif->id]) }}"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs sm:text-sm font-bold transition">
                        <i class="fa-solid fa-chart-simple text-slate-500"></i>
                        <span>Input Nilai</span>
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Informasi Detail Siswa & Riwayat Kelas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Rincian Data Siswa -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4 h-fit">
            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fa-solid fa-id-card text-emerald-600"></i>
                <span>Data Pokok Siswa</span>
            </h3>

            <dl class="space-y-3 text-xs">
                <div>
                    <dt class="text-slate-400">Nama Lengkap</dt>
                    <dd class="font-bold text-slate-800 mt-0.5 text-sm">{{ $siswa->nama }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Nomor Induk Siswa (NIS)</dt>
                    <dd class="font-mono font-bold text-slate-800 mt-0.5 flex items-center gap-1.5">
                        <span>{{ $siswa->nis }}</span>
                        @if(str_starts_with($siswa->nis, 'TMP-') || str_starts_with($siswa->nis, 'TEMP-'))
                            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-semibold">Sementara</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400">NISN</dt>
                    <dd class="font-mono font-medium text-slate-800 mt-0.5">{{ $siswa->nisn ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400">Status Keaktifan</dt>
                    <dd class="font-bold mt-0.5">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] {{ $siswa->status === 'aktif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $siswa->status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                            {{ $siswa->status === 'aktif' ? 'Siswa Aktif' : 'Telah Lulus / Non-Aktif' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400">Terdaftar Pada Sistem</dt>
                    <dd class="text-slate-700 mt-0.5 font-medium">
                        {{ $siswa->created_at ? $siswa->created_at->locale('id')->translatedFormat('d F Y') : '-' }}
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Kolom Kanan: Riwayat Penempatan Kelas & Tahun Ajaran -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2 border-b border-slate-100 pb-3">
                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                <span>Riwayat Penempatan Kelas & Akademik</span>
            </h3>

            @if($siswa->kelasSiswa->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <th class="py-2.5 px-3 font-bold">Tahun Ajaran</th>
                                <th class="py-2.5 px-3 font-bold">Rombel / Kelas</th>
                                <th class="py-2.5 px-3 font-bold">Fase</th>
                                <th class="py-2.5 px-3 font-bold">Wali Kelas</th>
                                <th class="py-2.5 px-3 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($siswa->kelasSiswa as $ks)
                                @php
                                    $k = $ks->kelas;
                                    $isAktif = $k && $k->tahunAjaran && $k->tahunAjaran->aktif;
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition {{ $isAktif ? 'bg-emerald-50/20' : '' }}">
                                    <td class="py-3 px-3 font-medium text-slate-700 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span>{{ $k->tahunAjaran->nama_lengkap ?? ($k->tahunAjaran->tahun ?? '-') }}</span>
                                            @if($isAktif)
                                                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">Aktif</span>
                                            @endif
                                        </div>
                                        <span class="text-[10px] text-slate-400 capitalize">Semester {{ $k->tahunAjaran->semester ?? 'Ganjil' }}</span>
                                    </td>
                                    <td class="py-3 px-3 font-bold text-slate-900 whitespace-nowrap">
                                        <a href="{{ route('kelas.show', $k->id) }}" class="hover:text-emerald-700 transition flex items-center gap-1 group">
                                            <span>{{ $k->nama }}</span>
                                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px] text-slate-400 opacity-0 group-hover:opacity-100 transition"></i>
                                        </a>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                            {{ $k->fase ?? 'Fase A' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600">
                                        <div class="font-medium text-slate-800 truncate max-w-[150px]" title="{{ $k->nama_wali }}">
                                            {{ $k->nama_wali }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('raport.preview', $ks->id) }}"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition"
                                                title="Lihat Raport Siswa di Kelas Ini">
                                                <i class="fa-solid fa-eye text-[11px] mr-1"></i> Raport
                                            </a>
                                            <a href="{{ route('nilai.input.siswa', ['kelas' => $k->id, 'kelasSiswa' => $ks->id]) }}"
                                                class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-semibold transition"
                                                title="Input / Edit Nilai">
                                                <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-500">
                    <i class="fa-solid fa-user-xmark text-3xl text-slate-300 mb-2"></i>
                    <p class="text-xs font-semibold">Siswa ini belum ditempatkan pada kelas manapun.</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Silakan tambahkan siswa ke dalam rombel melalui menu Penempatan Kelas.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin())
    <!-- Modal Edit Siswa Sederhana -->
    <div id="modalEditSiswa" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-bold text-base text-slate-900">Edit Data Siswa</h3>
                <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('siswa.update', $siswa->id) }}" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                    <input type="text" name="nama" value="{{ old('nama', $siswa->nama) }}" required
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor Induk Siswa (NIS)</label>
                    <input type="text" name="nis" value="{{ old('nis', $siswa->nis) }}"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">NISN (10 Digit)</label>
                    <input type="text" name="nisn" value="{{ old('nisn', $siswa->nisn) }}" maxlength="10"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 font-mono text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Siswa</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-emerald-500 text-xs font-medium">
                        <option value="aktif" {{ $siswa->status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="lulus" {{ $siswa->status === 'lulus' ? 'selected' : '' }}>Lulus / Non-Aktif</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')"
                        class="px-3.5 py-2 rounded-xl text-slate-600 hover:bg-slate-100 font-semibold transition">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-sm shadow-emerald-600/20 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
