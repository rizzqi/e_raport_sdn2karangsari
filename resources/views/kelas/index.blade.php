@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-chalkboard-user text-emerald-600 mr-2"></i> Rombongan Belajar (Kelas)
            </h1>
            <p class="text-xs text-slate-500">Kelola kelas, pembagian fase, penugasan wali kelas, dan akses raport per rombel</p>
        </div>

        @if(auth()->user()->isAdmin())
            <button onclick="document.getElementById('modalTambahKelas').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition">
                <i class="fa-solid fa-plus mr-1.5"></i> Tambah Kelas Baru
            </button>
        @endif
    </div>

    <!-- Grid Kartu Kelas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($kelasList as $kelas)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between">
                <div>
                    <div class="p-5 border-b border-slate-100 flex items-start justify-between">
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 mb-1.5">
                                {{ $kelas->fase ?? 'Fase A' }}
                            </span>
                            <h2 class="text-xl font-black text-slate-900">
                                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:text-emerald-600 transition">
                                    {{ $kelas->nama }}
                                </a>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tahun: <span class="font-medium text-slate-700">{{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</span>
                            </p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-600 font-bold text-lg">
                            <i class="fa-solid fa-school"></i>
                        </div>
                    </div>

                    <div class="p-5 space-y-3 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400"><i class="fa-solid fa-user-tie mr-1"></i> Wali Kelas:</span>
                            <span class="font-semibold text-slate-800">{{ $kelas->wali->name ?? 'Belum Ditentukan' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400"><i class="fa-solid fa-users mr-1"></i> Jumlah Siswa:</span>
                            <span class="font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded-md">{{ $kelas->kelas_siswa_count }} Siswa</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs font-semibold">
                    <a href="{{ route('kelas.show', $kelas->id) }}" class="py-2 px-3 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-center transition flex items-center justify-center">
                        <i class="fa-solid fa-list mr-1.5"></i> Siswa
                    </a>
                    <a href="{{ route('nilai.input.kelas', $kelas->id) }}" class="py-2 px-3 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-center transition flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-pen-to-square mr-1.5"></i> Input Nilai
                    </a>
                    <a href="{{ route('rekap.kelas', $kelas->id) }}" class="py-2 px-3 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 text-center transition flex items-center justify-center">
                        <i class="fa-solid fa-chart-column mr-1.5"></i> Rekap Nilai
                    </a>
                    <a href="{{ route('raport.download.kelas.docx', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" class="py-2 px-3 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 text-center transition flex items-center justify-center">
                        <i class="fa-solid fa-file-word mr-1.5"></i> Unduh Word
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-2xl border border-slate-200/80 text-center text-slate-400">
                <i class="fa-solid fa-chalkboard text-4xl mb-3 block text-slate-300"></i>
                <p class="font-semibold text-slate-700">Belum ada data rombongan belajar.</p>
                <p class="text-xs text-slate-400 mt-1">Silakan tambahkan kelas baru atau hubungi administrator sekolah.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Tambah Kelas -->
    @if(auth()->user()->isAdmin())
        <div id="modalTambahKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tambah Kelas Baru</h3>
                    <button onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <form action="{{ route('kelas.store') }}" method="POST" class="space-y-4 mt-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Kelas *</label>
                        <input type="text" name="nama" placeholder="Contoh: Kelas 1, Kelas 4A" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Fase Kurikulum *</label>
                        <select name="fase" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="Fase A">Fase A (Kelas 1-2)</option>
                            <option value="Fase B">Fase B (Kelas 3-4)</option>
                            <option value="Fase C">Fase C (Kelas 5-6)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Tahun Ajaran *</label>
                        <select name="tahun_ajaran_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta->id }}" {{ $ta->aktif ? 'selected' : '' }}>
                                    {{ $ta->nama_lengkap }} {{ $ta->aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Wali Kelas</label>
                        <select name="wali_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Wali Kelas --</option>
                            @foreach($waliList as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('modalTambahKelas').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                            Simpan Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
