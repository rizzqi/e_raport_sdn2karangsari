@extends('layouts.app')

@section('title', 'Daftar Kelas')

@section('content')
<div class="space-y-6">

    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25 ring-4 ring-emerald-500/10 shrink-0">
                <i class="fa-solid fa-chalkboard-user text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Rombongan Belajar (Kelas)</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                        {{ $kelasList->count() }} Kelas
                    </span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">Kelola kelas, pembagian fase, penugasan wali kelas, dan akses raport per rombel</p>
            </div>
        </div>

        @if(auth()->user()->isAdmin())
            <button onclick="document.getElementById('modalTambahKelas').classList.remove('hidden')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/30 hover:-translate-y-0.5 transition-all duration-200">
                <i class="fa-solid fa-plus mr-1.5"></i> Tambah Kelas Baru
            </button>
        @endif
    </div>

    <!-- Grid Kartu Kelas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($kelasList as $kelas)
            @php
                $faseRaw = strtoupper($kelas->fase ?? 'FASE A');
                if (str_contains($faseRaw, 'C')) {
                    $accentColor = 'border-t-indigo-500';
                    $badgeFase = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                    $iconColor = 'text-indigo-600 bg-indigo-50 border-indigo-100';
                } elseif (str_contains($faseRaw, 'B')) {
                    $accentColor = 'border-t-sky-500';
                    $badgeFase = 'bg-sky-50 text-sky-700 border-sky-200';
                    $iconColor = 'text-sky-600 bg-sky-50 border-sky-100';
                } else {
                    $accentColor = 'border-t-emerald-500';
                    $badgeFase = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    $iconColor = 'text-emerald-600 bg-emerald-50 border-emerald-100';
                }
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200/80 border-t-4 {{ $accentColor }} shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col justify-between group">
                <div>
                    <!-- Header Kartu -->
                    <div class="p-5 border-b border-slate-100 flex items-start justify-between">
                        <div>
                            <div class="flex items-center space-x-1.5 mb-1.5">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $badgeFase }}">
                                    {{ $kelas->fase ?? 'Fase A' }}
                                </span>
                                @if(auth()->user()->isAdmin())
                                    <button type="button" onclick="editKelasModal({{ json_encode($kelas) }})"
                                        class="inline-flex items-center text-[10px] font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-md transition shadow-2xs"
                                        title="Ubah data rombel atau koreksi fase kelas">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Kelas
                                    </button>
                                @endif
                            </div>
                            <h2 class="text-xl font-black text-slate-900 group-hover:text-emerald-700 transition">
                                <a href="{{ route('kelas.show', $kelas->id) }}">
                                    {{ $kelas->nama }}
                                </a>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                <i class="fa-regular fa-calendar text-[11px] text-slate-400"></i>
                                <span>Tahun:</span>
                                <span class="font-semibold text-slate-700">{{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</span>
                            </p>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            @if(auth()->user()->isAdmin())
                                <button type="button" onclick="editKelasModal({{ json_encode($kelas) }})"
                                    class="w-9 h-9 rounded-xl bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-slate-400 hover:text-emerald-700 flex items-center justify-center transition"
                                    title="Edit Rombongan Kelas (Nama & Fase)">
                                    <i class="fa-solid fa-gear text-sm"></i>
                                </button>
                            @endif
                            <div class="w-10 h-10 rounded-xl border flex items-center justify-center font-bold text-lg {{ $iconColor }}">
                                <i class="fa-solid fa-school"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Informasi Rombel -->
                    <div class="p-5 space-y-3 text-xs">
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-tie text-slate-400"></i> Wali Kelas:
                            </span>
                            <div class="text-right flex items-center space-x-1.5">
                                <div>
                                    <span class="font-bold text-slate-800">{{ $kelas->nama_wali }}</span>
                                    @if($kelas->nip_wali !== '-')
                                        <span class="block text-[10px] text-slate-400 font-mono">NIP: {{ $kelas->nip_wali }}</span>
                                    @endif
                                </div>
                                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                                    <button type="button" onclick="editWaliModal({{ json_encode($kelas) }})"
                                        class="p-1 rounded-md text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition"
                                        title="Edit Nama dan NIP Wali Kelas">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-slate-400"></i> Jumlah Siswa:
                            </span>
                            <span class="font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200/60">
                                {{ $kelas->kelas_siswa_count }} Siswa
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-slate-600">
                            <span class="text-slate-400 flex items-center gap-1.5">
                                <i class="fa-solid fa-sliders text-slate-400"></i> KKM / KKTP:
                            </span>
                            <a href="{{ route('kelas.show', $kelas->id) }}" class="inline-flex items-center gap-1 font-bold text-purple-700 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 px-2.5 py-0.5 rounded-full text-[11px] border border-purple-200 transition shadow-2xs" title="Lihat dan Atur KKM mandiri untuk kelas ini">
                                <span>KKM Rombel</span>
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Footer Aksi -->
                <div class="p-4 bg-slate-50/90 border-t border-slate-100 space-y-2 text-xs">
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('kelas.show', $kelas->id) }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 hover:border-slate-300 text-slate-700 hover:bg-slate-50 text-center transition flex items-center justify-center font-bold shadow-2xs">
                            <i class="fa-solid fa-users mr-1.5 text-slate-400"></i> Siswa
                        </a>
                        @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                            <a href="{{ route('nilai.input.kelas', $kelas->id) }}" class="py-2 px-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-center transition flex items-center justify-center font-bold shadow-sm hover:shadow">
                                <i class="fa-solid fa-pen-to-square mr-1.5"></i> Input Nilai
                            </a>
                        @else
                            <a href="{{ route('rekap.kelas', $kelas->id) }}" class="py-2 px-3 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-center transition flex items-center justify-center font-bold shadow-2xs">
                                <i class="fa-solid fa-eye mr-1.5 text-slate-400"></i> Lihat Rekap
                            </a>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-1.5 pt-1 border-t border-slate-200/60">
                        <a href="{{ route('rekap.kelas', $kelas->id) }}" class="py-1.5 px-2 rounded-xl bg-white text-blue-700 border border-blue-200 hover:bg-blue-50 text-center transition flex items-center justify-center font-bold text-[11px] shadow-2xs" title="Rekap Nilai Siswa">
                            <i class="fa-solid fa-chart-column mr-1 text-blue-500"></i> Rekap
                        </a>
                        <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" class="py-1.5 px-2 rounded-xl bg-slate-900 text-white hover:bg-black text-center transition flex items-center justify-center font-bold text-[11px] shadow-xs" title="Cetak Raport Sekelas Langsung">
                            <i class="fa-solid fa-print mr-1 text-slate-300"></i> Cetak
                        </a>
                        <a href="{{ route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" class="py-1.5 px-2 rounded-xl bg-white text-rose-700 border border-rose-200 hover:bg-rose-50 text-center transition flex items-center justify-center font-bold text-[11px] shadow-2xs" title="Unduh PDF Sekelas">
                            <i class="fa-solid fa-file-pdf mr-1 text-rose-500"></i> PDF
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-3xl border border-slate-200/80 text-center text-slate-400 shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                    <i class="fa-solid fa-chalkboard"></i>
                </div>
                <p class="font-bold text-slate-700 text-base">Belum ada data rombongan belajar.</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Silakan tambahkan kelas baru atau hubungi administrator sekolah untuk memuat data awal.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Tambah Kelas -->
    @if(auth()->user()->isAdmin())
        <div id="modalTambahKelas" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl p-6 sm:p-7 border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-plus-circle"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                                Tambah Kelas Baru
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Buat rombongan belajar baru beserta penentuan fase dan wali kelas</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition leading-none text-xl">&times;</button>
                </div>

                <form action="{{ route('kelas.store') }}" method="POST" class="space-y-4 mt-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Nama Kelas *</label>
                        <input type="text" name="nama" id="tambah_kelas_nama" placeholder="Contoh: Kelas 1, Kelas 3, Kelas 4A" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/15 font-semibold transition bg-slate-50/70 focus:bg-white">
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] font-semibold text-slate-400 mr-1">Pilihan Cepat:</span>
                            @foreach([1 => 'Fase A', 2 => 'Fase A', 3 => 'Fase B', 4 => 'Fase B', 5 => 'Fase C', 6 => 'Fase C'] as $tingkat => $faseDefault)
                                <button type="button" onclick="setTambahKelasQuick('Kelas {{ $tingkat }}', '{{ $faseDefault }}')"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 border border-slate-200 transition">
                                    Kelas {{ $tingkat }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Fase Kurikulum *</label>
                        <select name="fase" id="tambah_kelas_fase" required class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-emerald-500/15 bg-white transition">
                            <option value="Fase A">Fase A (Kelas 1 - 2)</option>
                            <option value="Fase B">Fase B (Kelas 3 - 4)</option>
                            <option value="Fase C">Fase C (Kelas 5 - 6)</option>
                        </select>
                        <div class="mt-2 bg-emerald-50/80 border border-emerald-200/60 rounded-xl p-3 text-[11px] text-emerald-900 leading-relaxed">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-emerald-800">
                                <i class="fa-solid fa-circle-info text-emerald-600"></i>
                                Panduan Fase Kurikulum Merdeka SD:
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 mt-1 text-[10px] text-emerald-800 font-medium">
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase A: Kls 1-2</span>
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase B: Kls 3-4</span>
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase C: Kls 5-6</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Tahun Ajaran *</label>
                        <select name="tahun_ajaran_id" required class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/15 bg-white transition">
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta->id }}" {{ $ta->aktif ? 'selected' : '' }}>
                                    {{ $ta->nama_lengkap }} {{ $ta->aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Nama Guru / Wali Kelas *</label>
                        <input type="text" name="wali_nama" placeholder="Contoh: Dewi Lestari, S.Pd." required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/15 font-semibold transition bg-slate-50/70 focus:bg-white">
                        <span class="text-[10px] text-slate-400 mt-1 block">Nama guru penandatangan raport untuk kelas ini</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">NIP Wali Kelas (Opsional)</label>
                        <input type="text" name="wali_nip" placeholder="Contoh: 19880315 201101 2 008"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/15 font-mono transition bg-slate-50/70 focus:bg-white">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('modalTambahKelas').classList.add('hidden')"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-md shadow-emerald-600/20 flex items-center transition">
                            <i class="fa-solid fa-plus mr-1.5"></i> Simpan Kelas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Edit Rombongan Kelas (Nama, Fase, Tahun Ajaran, Wali) -->
    @if(auth()->user()->isAdmin())
        <div id="modalEditKelas" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl p-6 sm:p-7 border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                                Edit Rombongan Kelas
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Ubah nama rombel, koreksi fase kurikulum, atau wali kelas</p>
                        </div>
                    </div>
                    <button type="button" onclick="document.getElementById('modalEditKelas').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition leading-none text-xl">&times;</button>
                </div>

                <form id="formEditKelas" method="POST" class="space-y-4 mt-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nama Kelas *
                        </label>
                        <input type="text" name="nama" id="edit_kelas_nama" required
                            placeholder="Contoh: Kelas 1, Kelas 3, Kelas 4A"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-emerald-500/15 bg-slate-50/70 focus:bg-white transition">
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] font-semibold text-slate-400 mr-1">Pilihan Cepat:</span>
                            @foreach([1 => 'Fase A', 2 => 'Fase A', 3 => 'Fase B', 4 => 'Fase B', 5 => 'Fase C', 6 => 'Fase C'] as $tingkat => $faseDefault)
                                <button type="button" onclick="setEditKelasQuick('Kelas {{ $tingkat }}', '{{ $faseDefault }}')"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 border border-slate-200 transition">
                                    Kelas {{ $tingkat }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Fase Kurikulum *
                        </label>
                        <select name="fase" id="edit_kelas_fase" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-semibold text-slate-800 focus:ring-4 focus:ring-emerald-500/15 bg-white transition">
                            <option value="Fase A">Fase A (Kelas 1 - 2)</option>
                            <option value="Fase B">Fase B (Kelas 3 - 4)</option>
                            <option value="Fase C">Fase C (Kelas 5 - 6)</option>
                        </select>
                        <div class="mt-2 bg-emerald-50/80 border border-emerald-200/60 rounded-xl p-3 text-[11px] text-emerald-900 leading-relaxed">
                            <div class="font-bold flex items-center gap-1.5 mb-1 text-emerald-800">
                                <i class="fa-solid fa-circle-info text-emerald-600"></i>
                                Panduan Fase Kurikulum Merdeka SD:
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 mt-1 text-[10px] text-emerald-800 font-medium">
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase A: Kls 1-2</span>
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase B: Kls 3-4</span>
                                <span class="bg-white/80 px-2 py-1 rounded-lg border border-emerald-200/80 text-center font-bold">Fase C: Kls 5-6</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Tahun Ajaran *
                        </label>
                        <select name="tahun_ajaran_id" id="edit_kelas_tahun_ajaran_id" required
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm focus:ring-4 focus:ring-emerald-500/15 bg-white transition">
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta->id }}">
                                    {{ $ta->nama_lengkap }} {{ $ta->aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Nama Guru / Wali Kelas *
                        </label>
                        <input type="text" name="wali_nama" id="edit_kelas_wali_nama" required
                            placeholder="Contoh: Dewi Lestari, S.Pd."
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-emerald-500/15 bg-slate-50/70 focus:bg-white transition">
                        <span class="text-[10px] text-slate-400 mt-1 block">Nama guru penandatangan raport untuk kelas ini</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            NIP Wali Kelas (Opsional)
                        </label>
                        <input type="text" name="wali_nip" id="edit_kelas_wali_nip"
                            placeholder="Contoh: 19880315 201101 2 008"
                            class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-mono focus:ring-4 focus:ring-emerald-500/15 bg-slate-50/70 focus:bg-white transition">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" onclick="konfirmasiHapusKelas()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition flex items-center shadow-2xs"
                            title="Hapus Rombongan Belajar">
                            <i class="fa-solid fa-trash-can mr-1.5"></i> Hapus Kelas
                        </button>

                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="document.getElementById('modalEditKelas').classList.add('hidden')"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-md shadow-emerald-600/20 flex items-center transition">
                                <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>

                <form id="formHapusKelas" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Edit Wali Kelas -->
    <div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-950/60 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl p-6 sm:p-7 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider">
                            Edit Wali Kelas & NIP
                        </h3>
                        <p id="modal_wali_kelas_title" class="text-xs font-bold text-emerald-700 mt-0.5"></p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition leading-none text-xl">&times;</button>
            </div>

            <form id="formEditWaliKelas" method="POST" class="space-y-4 mt-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Lengkap & Gelar Wali Kelas *
                    </label>
                    <input type="text" name="wali_nama" id="modal_wali_nama" required
                        placeholder="Contoh: Dewi Lestari, S.Pd."
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-semibold focus:ring-4 focus:ring-emerald-500/15 bg-slate-50/70 focus:bg-white transition">
                    <span class="text-[11px] text-slate-400 mt-1 block">Nama ini akan dicetak pada lembar tanda tangan raport semester.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nomor Induk Pegawai (NIP)
                    </label>
                    <input type="text" name="wali_nip" id="modal_wali_nip"
                        placeholder="Contoh: 19880315 201101 2 008"
                        class="w-full px-3.5 py-2.5 border border-slate-200 focus:border-emerald-500 rounded-xl text-sm font-mono focus:ring-4 focus:ring-emerald-500/15 bg-slate-50/70 focus:bg-white transition">
                    <span class="text-[11px] text-slate-400 mt-1 block">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP resmi.</span>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white shadow-md shadow-emerald-600/20 flex items-center transition">
                        <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    let currentKelasToEdit = null;

    function editKelasModal(kelas) {
        currentKelasToEdit = kelas;
        const form = document.getElementById('formEditKelas');
        form.action = '/kelas/' + kelas.id;

        document.getElementById('edit_kelas_nama').value = kelas.nama || '';

        // Deteksi Fase yang cocok
        let faseVal = 'Fase A';
        if (kelas.fase) {
            const f = kelas.fase.trim().toUpperCase();
            if (f.includes('C') || f === 'C') {
                faseVal = 'Fase C';
            } else if (f.includes('B') || f === 'B') {
                faseVal = 'Fase B';
            } else {
                faseVal = 'Fase A';
            }
        }
        document.getElementById('edit_kelas_fase').value = faseVal;

        document.getElementById('edit_kelas_tahun_ajaran_id').value = kelas.tahun_ajaran_id;
        document.getElementById('edit_kelas_wali_nama').value = kelas.wali_nama || (kelas.wali ? kelas.wali.name : '');
        document.getElementById('edit_kelas_wali_nip').value = (kelas.wali_nip && kelas.wali_nip !== '-') ? kelas.wali_nip : '';

        document.getElementById('modalEditKelas').classList.remove('hidden');
    }

    function setEditKelasQuick(nama, fase) {
        document.getElementById('edit_kelas_nama').value = nama;
        document.getElementById('edit_kelas_fase').value = fase;
    }

    function setTambahKelasQuick(nama, fase) {
        document.getElementById('tambah_kelas_nama').value = nama;
        document.getElementById('tambah_kelas_fase').value = fase;
    }

    function konfirmasiHapusKelas() {
        if (!currentKelasToEdit) return;
        const siswaCount = currentKelasToEdit.kelas_siswa_count !== undefined ? currentKelasToEdit.kelas_siswa_count : 0;
        let msg = `Apakah Anda yakin ingin menghapus rombongan belajar "${currentKelasToEdit.nama}"?`;
        if (siswaCount > 0) {
            msg += `\n\nPERHATIAN: Ada ${siswaCount} siswa yang terdaftar di kelas ini. Seluruh data penempatan dan nilai di kelas ini akan ikut terhapus.`;
        }
        if (confirm(msg)) {
            const formHapus = document.getElementById('formHapusKelas');
            formHapus.action = '/kelas/' + currentKelasToEdit.id;
            formHapus.submit();
        }
    }

    // Auto-match Fase saat mengetik nama kelas
    function setupFaseAutoMatcher(inputId, selectId) {
        const inputEl = document.getElementById(inputId);
        const selectEl = document.getElementById(selectId);
        if (!inputEl || !selectEl) return;

        inputEl.addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            if (val.match(/(?:^|\D)(?:1|2|i|ii)(?:$|\D)/i)) {
                selectEl.value = 'Fase A';
            } else if (val.match(/(?:^|\D)(?:3|4|iii|iv)(?:$|\D)/i)) {
                selectEl.value = 'Fase B';
            } else if (val.match(/(?:^|\D)(?:5|6|v|vi)(?:$|\D)/i)) {
                selectEl.value = 'Fase C';
            }
        });
    }

    function editWaliModal(kelas) {
        document.getElementById('formEditWaliKelas').action = '/kelas/' + kelas.id + '/wali';
        document.getElementById('modal_wali_kelas_title').textContent = kelas.nama;
        document.getElementById('modal_wali_nama').value = kelas.wali_nama || (kelas.wali ? kelas.wali.name : '');
        document.getElementById('modal_wali_nip').value = (kelas.wali_nip && kelas.wali_nip !== '-') ? kelas.wali_nip : '';
        document.getElementById('modalEditWaliKelas').classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        setupFaseAutoMatcher('tambah_kelas_nama', 'tambah_kelas_fase');
        setupFaseAutoMatcher('edit_kelas_nama', 'edit_kelas_fase');
    });
</script>
@endpush
@endsection
