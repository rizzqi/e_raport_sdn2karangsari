@extends('layouts.app')

@section('title', 'Pusat Absensi Harian')

@section('content')
<div class="space-y-6">

    <!-- Header & Date Filter -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-amber-500/25 ring-4 ring-amber-500/10 shrink-0">
                <i class="fa-solid fa-calendar-check text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    <span>Pusat Absensi Harian</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        SDN 2 Karangsari
                    </span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Presensi harian terpadu untuk <strong>Wali Kelas</strong>, <strong>Guru Pendidikan Agama</strong>, dan <strong>Guru PJOK</strong>.
                </p>
            </div>
        </div>

        <!-- Filter Tanggal Harian -->
        <form method="GET" action="{{ route('absensi-harian.index') }}" class="flex items-center gap-2 flex-wrap">
            <label for="tanggal" class="text-xs font-bold text-slate-600 shrink-0">
                <i class="fa-regular fa-calendar mr-1"></i> Tanggal:
            </label>
            <input type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}"
                onchange="this.form.submit()"
                class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500 focus:outline-none shadow-xs">
            
            @php
                $yesterday = \Carbon\Carbon::parse($tanggal)->subDay()->format('Y-m-d');
                $tomorrow = \Carbon\Carbon::parse($tanggal)->addDay()->format('Y-m-d');
                $today = now()->format('Y-m-d');
            @endphp
            <div class="inline-flex rounded-xl shadow-xs overflow-hidden border border-slate-200">
                <a href="{{ route('absensi-harian.index', ['tanggal' => $yesterday]) }}" title="Kemarin"
                   class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-600 text-xs border-r border-slate-200">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="{{ route('absensi-harian.index', ['tanggal' => $today]) }}" title="Hari Ini"
                   class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border-r border-slate-200">
                    Hari Ini
                </a>
                <a href="{{ route('absensi-harian.index', ['tanggal' => $tomorrow]) }}" title="Besok"
                   class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-600 text-xs">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Banner Tanggal Terpilih & Statistik Hari Ini -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3.5">
        <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-4 shadow-sm border border-slate-800 flex flex-col justify-between col-span-2 sm:col-span-4 lg:col-span-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tanggal Aktif</span>
            <div class="my-1">
                <span class="text-xl font-black block leading-tight text-amber-400">
                    {{ $tanggalObj->translatedFormat('l') }}
                </span>
                <span class="text-xs text-slate-200 font-medium">
                    {{ $tanggalObj->translatedFormat('d F Y') }}
                </span>
            </div>
            <span class="text-[10px] text-slate-400 flex items-center gap-1">
                <i class="fa-solid fa-clock-rotate-left"></i> Presensi Real-time
            </span>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hadir (H)</span>
                <span class="text-2xl font-black text-emerald-600">{{ $stats['hadir'] }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Siswa hadir</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg border border-emerald-100">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Sakit (S)</span>
                <span class="text-2xl font-black text-amber-600">{{ $stats['sakit'] }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Surat / izin sakit</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg border border-amber-100">
                <i class="fa-solid fa-notes-medical"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Izin (I)</span>
                <span class="text-2xl font-black text-blue-600">{{ $stats['izin'] }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Pemberitahuan resmi</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg border border-blue-100">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Alpa (A)</span>
                <span class="text-2xl font-black text-rose-600">{{ $stats['alpa'] }}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Tanpa keterangan</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg border border-rose-100">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Panduan Akses 3 Peran Guru -->
    <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 border border-amber-200/80 rounded-2xl p-4 shadow-xs">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="fa-solid fa-lightbulb text-sm"></i>
            </div>
            <div class="text-xs text-slate-700 leading-relaxed space-y-1">
                <p class="font-bold text-amber-900">Petunjuk Pengisian Absensi Harian Terpadu:</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 pt-1 text-[11px]">
                    <div class="bg-white/80 p-2.5 rounded-xl border border-amber-200/50">
                        <strong class="text-amber-800 flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-chalkboard-user"></i> 1. Wali Kelas
                        </strong>
                        Mengisi presensi rombel setiap hari. Absensi umum ini dapat langsung disinkronkan ke <em>Kehadiran Bulanan</em> &amp; lembar SIA rapor semester.
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-xl border border-amber-200/50">
                        <strong class="text-emerald-800 flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-book-quran"></i> 2. Guru Pendidikan Agama
                        </strong>
                        Mengisi presensi per jam pelajaran Agama di masing-masing kelas (Kelas 1–6) lengkap dengan status ketertiban dan catatan ibadah/keaktifan.
                    </div>
                    <div class="bg-white/80 p-2.5 rounded-xl border border-amber-200/50">
                        <strong class="text-sky-800 flex items-center gap-1.5 mb-1">
                            <i class="fa-solid fa-person-running"></i> 3. Guru PJOK
                        </strong>
                        Mengisi presensi per sesi praktik lapangan PJOK (Kelas 1–6) dengan keterangan kondisi fisik siswa (seperti cedera, tidak kuat lari, atau sakit).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid Kartu Kelas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($kelasList as $k)
            @php
                $statusK = $statusKelasHariIni[$k->id] ?? [
                    'wali_terisi' => false,
                    'wali_hadir' => 0,
                    'wali_absen' => 0,
                    'agama_terisi' => false,
                    'pjok_terisi' => false,
                ];
                $isWaliUser = auth()->user()->isWali() && auth()->user()->id === $k->wali_id;
            @endphp
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-lg transition-all duration-200 flex flex-col justify-between overflow-hidden {{ $isWaliUser ? 'ring-2 ring-amber-400' : '' }}">
                <div>
                    <!-- Header Kartu Kelas -->
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white font-black flex items-center justify-center text-sm shadow-xs">
                                {{ $k->tingkat ?? substr($k->nama, 0, 2) }}
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                                    <span>Kelas {{ $k->nama }}</span>
                                    @if($isWaliUser)
                                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            Kelas Anda
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">
                                    <i class="fa-solid fa-user-tie text-[10px] text-slate-400 mr-1"></i>
                                    {{ $k->nama_wali }}
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            {{ $k->kelas_siswa_count }} Siswa
                        </span>
                    </div>

                    <!-- Status Presensi Hari Ini -->
                    <div class="p-4 space-y-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Status Presensi {{ $tanggalObj->translatedFormat('d M Y') }}:
                        </div>

                        <!-- Wali Kelas -->
                        <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="font-medium text-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-chalkboard-user text-amber-500"></i> Wali Kelas
                            </span>
                            @if($statusK['wali_terisi'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <i class="fa-solid fa-check text-[9px]"></i> Terisi ({{ $statusK['wali_hadir'] }} Hadir, {{ $statusK['wali_absen'] }} SIA)
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Diisi
                                </span>
                            @endif
                        </div>

                        <!-- Guru Agama -->
                        <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="font-medium text-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-book-quran text-emerald-600"></i> Pendidikan Agama
                            </span>
                            @if($statusK['agama_terisi'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <i class="fa-solid fa-check text-[9px]"></i> Terisi
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Diisi
                                </span>
                            @endif
                        </div>

                        <!-- Guru PJOK -->
                        <div class="flex items-center justify-between text-xs py-1.5 px-2.5 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="font-medium text-slate-700 flex items-center gap-1.5">
                                <i class="fa-solid fa-person-running text-sky-600"></i> PJOK
                            </span>
                            @if($statusK['pjok_terisi'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <i class="fa-solid fa-check text-[9px]"></i> Terisi
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Diisi
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: 3 Pintu Masuk -->
                <div class="p-4 pt-0 space-y-2">
                    <div class="grid grid-cols-3 gap-1.5 pt-2 border-t border-slate-100">
                        <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $k->id, 'tanggal' => $tanggal, 'tipe' => 'wali']) }}"
                           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-xs text-center">
                            <i class="fa-solid fa-chalkboard-user text-xs mb-0.5"></i>
                            <span>Wali</span>
                        </a>

                        <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $k->id, 'tanggal' => $tanggal, 'tipe' => 'agama']) }}"
                           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs text-center">
                            <i class="fa-solid fa-book-quran text-xs mb-0.5"></i>
                            <span>Agama</span>
                        </a>

                        <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $k->id, 'tanggal' => $tanggal, 'tipe' => 'pjok']) }}"
                           class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-[11px] transition shadow-xs text-center">
                            <i class="fa-solid fa-person-running text-xs mb-0.5"></i>
                            <span>PJOK</span>
                        </a>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                        <a href="{{ route('kelas.absensi-harian.cetak', ['kelas' => $k->id, 'tanggal' => $tanggal, 'mode' => 'harian', 'tipe' => 'wali']) }}"
                           target="_blank" class="hover:text-amber-700 flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-print text-slate-400"></i> Cetak Hari Ini
                        </a>
                        <a href="{{ route('kelas.absensi-harian.cetak', ['kelas' => $k->id, 'tanggal' => $tanggal, 'mode' => 'bulanan', 'tipe' => 'wali']) }}"
                           target="_blank" class="hover:text-blue-700 flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-calendar-days text-slate-400"></i> Cetak Rekap Bulan (F4)
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
