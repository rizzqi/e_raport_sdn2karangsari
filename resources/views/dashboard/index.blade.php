@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- 1. Banner Utama (Elegan, Mewah & Menginspirasi) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl py-5 px-6 sm:px-8 text-white shadow-xl shadow-slate-950/20 border border-white/10 relative overflow-hidden flex flex-col md:flex-row md:items-center md:justify-between gap-5">
        <!-- Ambient decorative blur orbs -->
        <div class="absolute -top-24 -right-24 w-80 h-80 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex-1">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 backdrop-blur-sm">
                    <i class="fa-solid fa-circle-dot text-[8px] text-emerald-400 animate-pulse"></i>
                    <span>Tahun Ajaran {{ $tahunAktif ? $tahunAktif->nama_lengkap : 'Belum Diatur' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-blue-500/20 text-blue-200 border border-blue-500/30 backdrop-blur-sm">
                    <i class="fa-solid fa-calendar-check text-[9px]"></i>
                    <span>Semester {{ $tahunAktif ? ucfirst($tahunAktif->semester) : '-' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-semibold bg-white/10 text-slate-300 border border-white/10 backdrop-blur-sm">
                    <i class="fa-solid fa-school text-[9px] text-amber-300"></i>
                    <span>Kurikulum Merdeka</span>
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-2.5">
                <span>Selamat Datang, {{ $user->name }}</span>
            </h1>
            <p class="text-slate-300 text-xs sm:text-sm mt-1 max-w-2xl font-normal leading-relaxed">
                Sistem Informasi E-Raport SD Negeri 2 Karangsari &mdash; Kelola nilai formatif & sumatif, analisis ketercapaian, dan cetak dokumen resmi raport secara instan.
            </p>
        </div>

        <div class="relative z-10 hidden sm:flex items-center gap-2.5 shrink-0">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('kelas.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-950/40 transition-all duration-200 hover:scale-105">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Kelola Kelas</span>
                </a>
                <a href="{{ route('siswa.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/15 text-white border border-white/20 font-bold text-xs rounded-xl transition-all duration-200 backdrop-blur-sm hover:scale-105">
                    <i class="fa-solid fa-user-graduate text-slate-300"></i>
                    <span>Data Siswa</span>
                </a>
            @else
                <a href="{{ route('kelas.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-950/40 transition-all duration-200 hover:scale-105">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Kelas Saya</span>
                </a>
            @endif
        </div>

        <!-- Subtle Watermark Icon Background -->
        <div class="absolute -right-6 -bottom-8 opacity-5 text-white text-9xl pointer-events-none">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
    </div>

    <!-- 2. Kartu Statistik Utama (Angka Menonjol, Ikon Gradien, Hover Elegan) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Kartu 1: Total Siswa -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Siswa Aktif</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ $totalSiswa }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px] text-emerald-500"></i>
                    <span>Terdaftar di rombel</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white shadow-md shadow-blue-500/25 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <!-- Kartu 2: Rombongan Belajar -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rombongan Belajar</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ $totalKelas }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-school text-[10px] text-emerald-500"></i>
                    <span>Kelas aktif semester ini</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-chalkboard"></i>
            </div>
        </div>

        <!-- Kartu 3: Semester Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Semester Aktif</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ $tahunAktif ? ucfirst($tahunAktif->semester) : '-' }}</p>
                <p class="text-[11px] text-emerald-600 font-semibold mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-check-double text-[10px]"></i>
                    <span>Periode STS & SAS</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white shadow-md shadow-amber-500/25 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>

        <!-- Kartu 4: Raport Dibuat -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Raport Dibuat</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mt-1">{{ $totalRaportDicetak }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                    <i class="fa-solid fa-file-word text-[10px] text-purple-500"></i>
                    <span>Riwayat unduh & cetak</span>
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-500 to-pink-600 text-white shadow-md shadow-purple-500/25 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-file-lines"></i>
            </div>
        </div>
    </div>

    <!-- 3. Hub Pintasan Fitur Cepat (Alur Kerja Cepat Guru & Wali) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <a href="{{ route('kelas.index') }}" class="group p-4 bg-white hover:bg-emerald-50/60 rounded-2xl border border-slate-200/80 hover:border-emerald-300 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-200 shadow-2xs">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-xs sm:text-sm text-slate-800 group-hover:text-emerald-800 truncate">Input Nilai</p>
                <p class="text-[11px] text-slate-400 truncate">Formatif & sumatif</p>
            </div>
        </a>

        <a href="{{ $kelasList->isNotEmpty() ? route('rekap.kelas', $kelasList->first()->id) : route('kelas.index') }}" class="group p-4 bg-white hover:bg-blue-50/60 rounded-2xl border border-slate-200/80 hover:border-blue-300 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 group-hover:bg-blue-600 group-hover:text-white transition-all duration-200 shadow-2xs">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-xs sm:text-sm text-slate-800 group-hover:text-blue-800 truncate">Grafik & Analisis</p>
                <p class="text-[11px] text-slate-400 truncate">Performa & rentang</p>
            </div>
        </a>

        <a href="{{ route('kelas.index') }}" class="group p-4 bg-white hover:bg-amber-50/60 rounded-2xl border border-slate-200/80 hover:border-amber-300 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 group-hover:bg-amber-600 group-hover:text-white transition-all duration-200 shadow-2xs">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-xs sm:text-sm text-slate-800 group-hover:text-amber-800 truncate">Absensi & Presensi</p>
                <p class="text-[11px] text-slate-400 truncate">Bulanan & daftar hadir</p>
            </div>
        </a>

        <a href="{{ route('raport.riwayat') }}" class="group p-4 bg-white hover:bg-purple-50/60 rounded-2xl border border-slate-200/80 hover:border-purple-300 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white transition-all duration-200 shadow-2xs">
                <i class="fa-solid fa-print"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-xs sm:text-sm text-slate-800 group-hover:text-purple-800 truncate">Cetak Raport</p>
                <p class="text-[11px] text-slate-400 truncate">PDF, Word & arsip</p>
            </div>
        </a>
    </div>

    <!-- 3. Tabel Kelas & Progres Pengisian Nilai (Jelas, Terstruktur & Tombol Berhierarki) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-base sm:text-lg font-black text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-emerald-600"></i>
                    <span>Status Pengisian Nilai & Cetak Raport</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar rombongan belajar, progres pengisian nilai STS, rata-rata kelas, dan aksi cepat</p>
            </div>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('kelas.index') }}" class="inline-flex items-center text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3.5 py-1.5 rounded-xl border border-emerald-200 transition">
                    <i class="fa-solid fa-gear mr-1.5 text-xs"></i> Kelola Rombel & KKM
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-48">Rombel & Fase</th>
                        <th class="py-3 px-4 min-w-[180px]">Wali Kelas</th>
                        <th class="py-3 px-4 text-center w-28">Jumlah Siswa</th>
                        <th class="py-3 px-4 min-w-[220px]">Progres Nilai (STS)</th>
                        <th class="py-3 px-4 text-center w-28">Rata-rata</th>
                        <th class="py-3 px-4 text-right min-w-[280px]">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekapKelasOverview as $item)
                        @php
                            $k = $item['kelas'];
                            $persen = (float) $item['persen_selesai'];
                            $isLengkap = ($persen >= 100);
                            $isKosong = ($persen <= 0);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition duration-150">
                            <!-- Rombel & Fase -->
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <a href="{{ route('kelas.show', $k->id) }}" class="text-slate-900 hover:text-emerald-700 flex items-center gap-2 group transition">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs group-hover:bg-emerald-600 group-hover:text-white transition">
                                        <i class="fa-solid fa-chalkboard"></i>
                                    </div>
                                    <span>{{ $k->nama }}</span>
                                </a>
                                <div class="mt-1 ml-9">
                                    <span class="inline-block text-[10px] font-bold text-slate-600 bg-slate-100 border border-slate-200/80 px-2 py-0.5 rounded-md uppercase tracking-wider">
                                        {{ $k->fase ?? 'Fase A' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Wali Kelas -->
                            <td class="py-3.5 px-4 text-slate-700">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ substr($k->nama_wali, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-xs text-slate-800 leading-tight">{{ $k->nama_wali }}</div>
                                        @if($k->nip_wali !== '-')
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">NIP: {{ $k->nip_wali }}</div>
                                        @else
                                            <div class="text-[10px] text-slate-400 italic mt-0.5">NIP: -</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Jumlah Siswa -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block font-extrabold text-xs text-slate-800 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg">
                                    {{ $item['total_siswa'] }} Siswa
                                </span>
                            </td>

                            <!-- Progres Pengisian Nilai -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center justify-between text-xs mb-1.5 gap-2">
                                    <div class="flex items-center gap-1.5">
                                        @if($isLengkap)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                <i class="fa-solid fa-circle-check text-[10px]"></i> Lengkap
                                            </span>
                                        @elseif($isKosong)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                                <i class="fa-solid fa-clock text-[10px]"></i> Belum Diisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                <i class="fa-solid fa-pen-clip text-[10px]"></i> Sedang Berjalan
                                            </span>
                                        @endif
                                        <span class="text-[11px] text-slate-500 font-medium">({{ $item['siswa_selesai'] }}/{{ $item['total_siswa'] }} selesai)</span>
                                    </div>
                                    <span class="font-black text-xs {{ $isLengkap ? 'text-emerald-700' : ($isKosong ? 'text-slate-400' : 'text-amber-700') }}">
                                        {{ $persen }}%
                                    </span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200/60">
                                    <div class="h-2 rounded-full transition-all duration-500 {{ $isLengkap ? 'bg-emerald-600' : ($isKosong ? 'bg-slate-200' : 'bg-emerald-500') }}"
                                         style="width: {{ $persen }}%"></div>
                                </div>
                            </td>

                            <!-- Rata-rata Kelas -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-black text-xs border border-blue-200/80">
                                    {{ $item['rata_rata'] }}
                                </span>
                            </td>

                            <!-- Tombol Aksi (Input Nilai = Tombol Utama Hijau, Lainnya = Sekunder) -->
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $k->wali_id))
                                    <!-- Tombol Utama: Input Nilai (Hijau #059669) -->
                                    <a href="{{ route('nilai.input.kelas', $k->id) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs hover:shadow transition"
                                        title="Input Nilai Format Matriks">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Input Nilai</span>
                                    </a>
                                @endif

                                <!-- Tombol Sekunder: Siswa -->
                                <a href="{{ route('kelas.show', $k->id) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition"
                                    title="Lihat Daftar Siswa Kelas">
                                    <i class="fa-solid fa-users text-slate-400"></i>
                                    <span>Siswa</span>
                                </a>

                                <!-- Tombol Sekunder: Rekap -->
                                <a href="{{ route('rekap.kelas', $k->id) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 transition"
                                    title="Rekapitulasi Nilai Kelas">
                                    <i class="fa-solid fa-chart-column text-blue-500"></i>
                                    <span>Rekap</span>
                                </a>

                                <!-- Tombol Sekunder: Cetak Raport Sekelas -->
                                <a href="{{ route('raport.cetak.kelas', ['kelas' => $k->id, 'periode' => 'sts']) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-900 text-white transition shadow-xs"
                                    title="Cetak Raport Seluruh Siswa Sekelas Sekaligus">
                                    <i class="fa-solid fa-print text-slate-300"></i>
                                    <span>Cetak</span>
                                </a>

                                <!-- Tombol Sekunder: PDF Sekelas -->
                                <a href="{{ route('raport.download.kelas.pdf', ['kelas' => $k->id, 'periode' => 'sts']) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-rose-50 text-rose-700 border border-rose-200 transition"
                                    title="Unduh PDF Raport Satu Kelas">
                                    <i class="fa-solid fa-file-pdf text-rose-500"></i>
                                    <span>PDF</span>
                                </a>

                                <!-- Tombol Sekunder: Unduh Word -->
                                <a href="{{ route('raport.download.kelas.docx', ['kelas' => $k->id, 'periode' => 'sts']) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 transition"
                                    title="Unduh Raport Satu Kelas format Word (.docx)">
                                    <i class="fa-solid fa-file-word text-indigo-500"></i>
                                    <span>Word</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-chalkboard text-4xl mb-3 block text-slate-300"></i>
                                <p class="font-bold text-slate-700">Belum ada rombongan belajar terdaftar.</p>
                                <p class="text-xs text-slate-400 mt-1">Silakan tambahkan data rombel di menu Kelas & Nilai.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
