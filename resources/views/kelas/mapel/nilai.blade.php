@extends('layouts.app')

@section('title', $tab === 'rapor' ? 'Nilai Rapor (Semua Mapel) - ' . $kelas->nama : 'Nilai ' . ($mapel->nama ?? 'Mapel') . ' - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- 1. HEADER KELAS & MAPEL -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col gap-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-start sm:items-center space-x-3.5">
                <a href="{{ route('kelas.show', $kelas->id) }}" class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition shrink-0" title="Kembali ke Kelas">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider {{ $tab === 'rapor' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                            {{ $tab === 'rapor' ? 'Nilai Rapor Terpadu (Semua Mapel)' : 'E-Rapor 4 Lembar' }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">
                            Kelas {{ $kelas->nama }} &bull; Semester {{ ucfirst($semester) }} &bull; TP {{ $tahunAjaran->nama_lengkap ?? '-' }}
                        </span>
                    </div>

                    @if($tab === 'rapor')
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex flex-wrap items-center gap-2.5">
                            <span>Nilai Rapor Siswa (Otomatis Semua Mapel)</span>
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">
                                {{ $allMapel->count() }} Mata Pelajaran
                            </span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                            &bull; Total: <strong class="text-slate-800">{{ $kelasSiswaList->count() }} Siswa</strong>
                            &bull; Rumus: <strong class="text-indigo-600 font-mono">(Formatif × {{ $bobotFormatif }}%) + (UTS × {{ $bobotUts }}%) + (UAS × {{ $bobotUas }}%)</strong>
                        </p>
                    @else
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex flex-wrap items-center gap-2.5">
                            <span>{{ $mapel->nama }}</span>
                            <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-lg bg-purple-100 text-purple-800 border border-purple-200 font-mono">
                                KKM: {{ $kkm }}
                            </span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Guru Pengampu: <strong class="text-slate-800">{{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? 'Belum ditentukan') }}</strong>
                            &bull; Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                            &bull; Total: <strong class="text-slate-800">{{ $kelasSiswaList->count() }} Siswa</strong>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Toolbar Kanan: Tombol Cetak & Status Kunci -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- Tombol Cetak Rekap Semester Otomatis -->
                <button type="button" onclick="document.getElementById('modalCetakRekapSemester').classList.remove('hidden')"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer"
                   title="Cetak Rekap Nilai Semester Otomatis (1 Siswa 1 Lembar F4 Sekelas / Leger)">
                    <i class="fa-solid fa-print"></i>
                    <span>Cetak Rekap Semester</span>
                </button>

                <!-- Tombol Cetak Bulanan -->
                <button type="button" onclick="document.getElementById('modalCetakBulanan').classList.remove('hidden')"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer"
                   title="Cetak Nilai Bulanan (Pilih Bulan Juli–Desember, Rapor Bulanan F4 / Leger)">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Cetak Bulanan</span>
                </button>

                @if($tab !== 'rapor')
                    @if($isAnyLocked)
                        <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs">
                            <i class="fa-solid fa-lock mr-1.5 text-emerald-600"></i> Nilai Terkunci
                        </span>
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('kelas.mapel.nilai.buka-kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" onsubmit="return confirm('Buka kunci nilai rapor mata pelajaran ini?')">
                                @csrf
                                <input type="hidden" name="tahun" value="{{ $tahun }}">
                                <input type="hidden" name="semester" value="{{ $semester }}">
                                <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition shadow-2xs flex items-center">
                                    <i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kunci
                                </button>
                            </form>
                        @endif
                    @else
                        <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-50 text-slate-600 border border-slate-200">
                            <i class="fa-solid fa-lock-open mr-1.5 text-slate-400"></i> Mode Pengisian Aktif
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <!-- Pilihan Cepat Mata Pelajaran (Hanya untuk Tab 1, 2, 3) -->
        @if($tab !== 'rapor' && $allMapel->isNotEmpty())
            <div class="pt-3 border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                <span class="text-slate-400 font-bold uppercase text-[10px] mr-1 shrink-0 flex items-center">
                    <i class="fa-solid fa-book-open mr-1"></i> Mapel:
                </span>
                @foreach($allMapel as $m)
                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $m->id, 'tab' => $tab, 'tahun' => $tahun]) }}"
                       class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 flex items-center gap-1.5 {{ $m->id === $mapel->id ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                        <span>{{ $m->nama }}</span>
                        <span class="text-[9px] px-1.5 py-0.2 rounded {{ $m->id === $mapel->id ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-200 text-slate-600' }}">
                            {{ $kelas->getKkmForMapel($m) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- 2. NAVIGASI 4 LEMBAR (TABS MODERN - Geser Samping di HP) -->
    <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-sm flex overflow-x-auto pb-2 md:pb-2 md:flex-wrap items-center gap-1.5 text-xs font-bold scrollbar-thin">
        <!-- Lembar 1: Formatif -->
        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'mapel_id' => $mapel->id ?? null, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
           class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 shrink-0 {{ $tab === 'formatif' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span class="w-5 h-5 rounded-full {{ $tab === 'formatif' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} flex items-center justify-center text-[10px]">1</span>
            <span>Formatif (Ulangan Bulanan)</span>
        </a>

        <!-- Lembar 2: UTS -->
        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'mapel_id' => $mapel->id ?? null, 'tab' => 'uts', 'tahun' => $tahun]) }}"
           class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 shrink-0 {{ $tab === 'uts' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span class="w-5 h-5 rounded-full {{ $tab === 'uts' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} flex items-center justify-center text-[10px]">2</span>
            <span>UTS (Tengah Semester)</span>
        </a>

        <!-- Lembar 3: Semester (UAS) -->
        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'mapel_id' => $mapel->id ?? null, 'tab' => 'uas', 'tahun' => $tahun]) }}"
           class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 shrink-0 {{ $tab === 'uas' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <span class="w-5 h-5 rounded-full {{ $tab === 'uas' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-200 text-slate-700 font-bold' }} flex items-center justify-center text-[10px]">3</span>
            <span>Semester (UAS / SAS)</span>
        </a>

        <!-- Lembar 4: Nilai Rapor (Otomatis Semua Mapel) -->
        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'tahun' => $tahun]) }}"
           class="px-4 py-2.5 rounded-xl transition flex items-center gap-2 shrink-0 {{ $tab === 'rapor' ? 'bg-indigo-600 text-white shadow-sm' : 'text-indigo-700 hover:text-indigo-900 hover:bg-indigo-50' }}">
            <span class="w-5 h-5 rounded-full {{ $tab === 'rapor' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-indigo-100 text-indigo-700 font-bold' }} flex items-center justify-center text-[10px]">4</span>
            <span>Nilai Rapor (Semua Mapel Otomatis)</span>
            <span class="text-[9px] px-1.5 py-0.5 rounded-md uppercase font-black {{ $tab === 'rapor' ? 'bg-indigo-700 text-white' : 'bg-indigo-100 text-indigo-800' }}">
                Terpadu
            </span>
        </a>
    </div>

    <!-- ========================================================================================= -->
    <!-- LEMBAR 1: FORMATIF (ULANGAN BULANAN)                                                     -->
    <!-- ========================================================================================= -->
    @if($tab === 'formatif')
        <div class="space-y-4">
            <!-- Petunjuk Sesuai Karakteristik Mata Pelajaran -->
            @if($isAgama)
                <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-emerald-950">
                    <div class="flex items-start space-x-3">
                        <i class="fa-solid fa-mosque text-emerald-600 text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-sm text-emerald-950">Lembar 1: Nilai Formatif & Praktikum — {{ $mapel->nama }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-200 text-emerald-900">Format Khusus Pendidikan Agama</span>
                            </div>
                            <p class="text-emerald-800 mt-1">
                                Formatif harian tetap diisi per bulan (<strong>JUL – DES</strong>), ditambah <strong>2 kali praktikum</strong>:
                                <span class="font-bold text-emerald-900 bg-emerald-100/90 border border-emerald-300 px-1.5 py-0.5 rounded">Praktik 1 (Pra-UTS)</span> dan
                                <span class="font-bold text-teal-900 bg-teal-100/90 border border-teal-300 px-1.5 py-0.5 rounded">Praktik 2 (Pra-UAS)</span>.
                                Rata-rata formatif dihitung otomatis dari seluruh ulangan harian dan praktikum yang terisi.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                       class="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto shrink-0"
                       title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rincian Nilai Harian</span>
                    </a>
                </div>
            @elseif($isPjok)
                <div class="bg-cyan-50 border border-cyan-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-cyan-950">
                    <div class="flex items-start space-x-3">
                        <i class="fa-solid fa-person-running text-cyan-600 text-lg mt-0.5 shrink-0"></i>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-sm text-cyan-950">Lembar 1: Nilai Formatif & Praktik Jasmani — {{ $mapel->nama }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-cyan-200 text-cyan-900">Format Khusus PJOK</span>
                            </div>
                            <p class="text-cyan-800 mt-1">
                                Setiap bulan (<strong>JUL – DES</strong>) memuat <strong>dua nilai</strong>:
                                <span class="font-bold text-blue-900 bg-blue-100/80 border border-blue-200 px-1.5 py-0.5 rounded">Fmt (Formatif Teori)</span> dan
                                <span class="font-bold text-emerald-900 bg-emerald-100/80 border border-emerald-200 px-1.5 py-0.5 rounded">Prak (Praktikum Jasmani)</span>.
                                Rata-rata akhir dihitung otomatis dari seluruh nilai teori dan uji gerak yang terisi.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                       class="px-3.5 py-2 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto shrink-0"
                       title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rincian Nilai Harian</span>
                    </a>
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-blue-900">
                    <div class="flex items-start space-x-3">
                        <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5 shrink-0"></i>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-sm text-blue-950">Lembar 1: Nilai Formatif Bulanan — {{ $mapel->nama }}</p>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">Mapel Reguler (1 Nilai/Bulan)</span>
                            </div>
                            <p class="text-blue-800 mt-1">
                                Isikan nilai ulangan sebulan sekali (<strong>JUL – DES</strong>). Rata-rata formatif dihitung otomatis dari bulan yang sudah terisi.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                       class="px-3.5 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5 self-start sm:self-auto shrink-0"
                       title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Rincian Nilai Harian</span>
                    </a>
                </div>
            @endif

            <!-- Formulir Nilai Formatif Bulanan -->
            <form action="{{ route('kelas.mapel.nilai.formatif', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                @if($isPjok)
                                    <!-- Header Bertingkat untuk PJOK: 2 Nilai Per Bulan -->
                                    <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                        <th rowspan="2" class="py-3 px-3 w-10 text-center border-r border-slate-200">No</th>
                                        <th rowspan="2" class="py-3 px-4 min-w-[190px] border-r border-slate-200 sticky left-0 bg-slate-50 z-10 shadow-[1px_0_0_0_#e2e8f0]">Nama Peserta Didik</th>
                                        @foreach($bulanSemester as $bNum => $bNama)
                                            @php $cntHarian = $harianCountPerBulan[$bNum] ?? 0; @endphp
                                            <th colspan="2" class="py-2 px-1 text-center border-r border-slate-200 bg-cyan-50/70 text-cyan-950 font-black">
                                                <div class="flex items-center justify-center gap-1">
                                                    <span>{{ substr($bNama, 0, 3) }}</span>
                                                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'bulan' => $bNum]) }}"
                                                       class="text-[9px] px-1 py-0.2 rounded font-bold transition flex items-center gap-0.5 {{ $cntHarian > 0 ? 'bg-cyan-600 text-white' : 'bg-cyan-200 text-cyan-900 hover:bg-cyan-300' }}"
                                                       title="Kelola & Rincian Nilai Harian Bulan {{ $bNama }}">
                                                        <i class="fa-solid fa-list-check text-[7.5px]"></i>
                                                        @if($cntHarian > 0)
                                                            <span>{{ $cntHarian }}</span>
                                                        @endif
                                                    </a>
                                                </div>
                                            </th>
                                        @endforeach
                                        <th rowspan="2" class="py-3.5 px-3 text-center border-r border-emerald-200 bg-emerald-50 text-emerald-950 font-black w-24">
                                            Rata-rata
                                        </th>
                                        <th rowspan="2" class="py-3.5 px-4 text-center w-52 bg-rose-50 text-rose-900 font-black">
                                            Cetak Dokumen F4
                                        </th>
                                    </tr>
                                    <tr class="bg-slate-100 text-[10px] font-extrabold uppercase text-slate-600 border-b border-slate-200">
                                        @foreach($bulanSemester as $bNum => $bNama)
                                            <th class="py-1 px-1 text-center border-r border-slate-200 bg-blue-50/50 text-blue-900" title="Formatif Teori {{ $bNama }}">Fmt</th>
                                            <th class="py-1 px-1 text-center border-r border-slate-200 bg-emerald-50/60 text-emerald-900" title="Praktikum Jasmani {{ $bNama }}">Prak</th>
                                        @endforeach
                                    </tr>
                                @elseif($isAgama)
                                    <!-- Header untuk Agama: 6 Bulan + 2 Praktikum (Pra-UTS & Pra-UAS) -->
                                    <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                        <th class="py-3.5 px-3 w-10 text-center border-r border-slate-200">No</th>
                                        <th class="py-3.5 px-4 min-w-[190px] border-r border-slate-200 sticky left-0 bg-slate-50 z-10 shadow-[1px_0_0_0_#e2e8f0]">Nama Peserta Didik</th>
                                        @php $bCount = 0; @endphp
                                        @foreach($bulanSemester as $bNum => $bNama)
                                            @php
                                                $bCount++;
                                                $cntHarian = $harianCountPerBulan[$bNum] ?? 0;
                                            @endphp
                                            <th class="py-2.5 px-1.5 text-center border-r border-slate-200 w-16">
                                                <div class="flex flex-col items-center justify-center">
                                                    <span>{{ substr($bNama, 0, 3) }}</span>
                                                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'bulan' => $bNum]) }}"
                                                       class="mt-0.5 text-[8.5px] px-1.5 py-0.2 rounded font-bold transition border flex items-center gap-0.5 {{ $cntHarian > 0 ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-emerald-100 hover:bg-emerald-200 text-emerald-800 border-emerald-300' }}"
                                                       title="Kelola & Rincian Nilai Harian Bulan {{ $bNama }}">
                                                        <i class="fa-solid fa-list-check text-[7.5px]"></i>
                                                        <span>{{ $cntHarian > 0 ? $cntHarian . ' Hrn' : 'Harian' }}</span>
                                                    </a>
                                                </div>
                                            </th>
                                            @if($bCount === 3)
                                                <th class="py-3.5 px-2 text-center border-r border-emerald-300 bg-emerald-100/70 text-emerald-950 font-black w-20"
                                                    title="Praktik 1 (Dilaksanakan sebelum UTS)">
                                                    Prak 1<br><span class="text-[9px] font-bold text-emerald-800">(Pra-UTS)</span>
                                                </th>
                                            @endif
                                            @if($bCount === count($bulanSemester))
                                                <th class="py-3.5 px-2 text-center border-r border-teal-300 bg-teal-100/70 text-teal-950 font-black w-20"
                                                    title="Praktik 2 (Dilaksanakan sebelum Ujian Akhir)">
                                                    Prak 2<br><span class="text-[9px] font-bold text-teal-800">(Pra-UAS)</span>
                                                </th>
                                            @endif
                                        @endforeach
                                        <th class="py-3.5 px-3 text-center border-r border-emerald-200 bg-emerald-50 text-emerald-950 font-black w-24">
                                            Rata-rata
                                        </th>
                                        <th class="py-3.5 px-4 text-center w-52 bg-rose-50 text-rose-900 font-black">
                                            Cetak Dokumen F4
                                        </th>
                                    </tr>
                                @else
                                    <!-- Header untuk Mapel Biasa / Reguler: 1 Nilai Per Bulan -->
                                    <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                        <th class="py-3.5 px-3 w-10 text-center border-r border-slate-200">No</th>
                                        <th class="py-3.5 px-4 min-w-[200px] border-r border-slate-200 sticky left-0 bg-slate-50 z-10 shadow-[1px_0_0_0_#e2e8f0]">Nama Peserta Didik</th>
                                        @foreach($bulanSemester as $bNum => $bNama)
                                            @php $cntHarian = $harianCountPerBulan[$bNum] ?? 0; @endphp
                                            <th class="py-2.5 px-1.5 text-center border-r border-slate-200 w-16">
                                                <div class="flex flex-col items-center justify-center">
                                                    <span>{{ substr($bNama, 0, 3) }}</span>
                                                    <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'bulan' => $bNum]) }}"
                                                       class="mt-0.5 text-[8.5px] px-1.5 py-0.2 rounded font-bold transition border flex items-center gap-0.5 {{ $cntHarian > 0 ? 'bg-blue-600 text-white border-blue-700' : 'bg-blue-100 hover:bg-blue-200 text-blue-800 border-blue-200' }}"
                                                       title="Kelola & Rincian Nilai Harian Bulan {{ $bNama }}">
                                                        <i class="fa-solid fa-list-check text-[7.5px]"></i>
                                                        <span>{{ $cntHarian > 0 ? $cntHarian . ' Hrn' : 'Harian' }}</span>
                                                    </a>
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="py-3.5 px-3 text-center border-r border-emerald-200 bg-emerald-50 text-emerald-950 font-black w-24">
                                            Rata-rata
                                        </th>
                                        <th class="py-3.5 px-4 text-center w-52 bg-rose-50 text-rose-900 font-black">
                                            Cetak Dokumen F4
                                        </th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($kelasSiswaList as $idx => $ks)
                                    @php
                                        $d = $siswaData[$ks->id] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                            {{ $idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 border-r border-slate-200 whitespace-nowrap sticky left-0 bg-white z-10 shadow-[1px_0_0_0_#e2e8f0]">
                                            <div class="font-bold text-slate-900">{{ $ks->siswa->nama }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }} &bull; {{ $ks->siswa->nisn ?? '-' }}</div>
                                        </td>

                                        @if($isPjok)
                                            <!-- PJOK: Formatif Teori & Praktikum per bulan -->
                                            @foreach($bulanSemester as $bNum => $bNama)
                                                @php
                                                    $valFmt = $d['bulanNilai'][$bNum] ?? '';
                                                    $valPrak = $d['bulanPraktik'][$bNum] ?? '';
                                                @endphp
                                                <td class="py-2.5 px-1 text-center border-r border-slate-200 bg-blue-50/20">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                           name="formatif[{{ $ks->id }}][{{ $bNum }}]"
                                                           value="{{ old('formatif.' . $ks->id . '.' . $bNum, $valFmt) }}"
                                                           placeholder="-"
                                                           title="Formatif Teori {{ $bNama }} - {{ $ks->siswa->nama }}"
                                                           data-siswa-id="{{ $ks->id }}"
                                                           {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                           class="input-formatif-bulan w-12 text-center font-mono font-bold text-xs py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                                                </td>
                                                <td class="py-2.5 px-1 text-center border-r border-slate-200 bg-emerald-50/30">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                           name="praktik[{{ $ks->id }}][{{ $bNum }}]"
                                                           value="{{ old('praktik.' . $ks->id . '.' . $bNum, $valPrak) }}"
                                                           placeholder="-"
                                                           title="Praktik Jasmani {{ $bNama }} - {{ $ks->siswa->nama }}"
                                                           data-siswa-id="{{ $ks->id }}"
                                                           {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                           class="input-formatif-bulan w-12 text-center font-mono font-bold text-xs py-1.5 border border-emerald-300 bg-emerald-50/40 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                                </td>
                                            @endforeach

                                        @elseif($isAgama)
                                            <!-- Agama: 6 Bulan + 2 Praktikum (Pra-UTS & Pra-UAS) -->
                                            @php $bCount = 0; @endphp
                                            @foreach($bulanSemester as $bNum => $bNama)
                                                @php
                                                    $bCount++;
                                                    $val = $d['bulanNilai'][$bNum] ?? '';
                                                @endphp
                                                <td class="py-2.5 px-1 text-center border-r border-slate-200">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                           name="formatif[{{ $ks->id }}][{{ $bNum }}]"
                                                           value="{{ old('formatif.' . $ks->id . '.' . $bNum, $val) }}"
                                                           placeholder="-"
                                                           title="Formatif {{ $bNama }} - {{ $ks->siswa->nama }}"
                                                           data-siswa-id="{{ $ks->id }}"
                                                           {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                           class="input-formatif-bulan w-14 text-center font-mono font-bold text-xs py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                                </td>
                                                @if($bCount === 3)
                                                    <td class="py-2.5 px-1.5 text-center border-r border-emerald-300 bg-emerald-50/60">
                                                        <input type="number" step="0.1" min="0" max="100"
                                                               name="praktik_1[{{ $ks->id }}]"
                                                               value="{{ old('praktik_1.' . $ks->id, $d['praktik_1'] ?? '') }}"
                                                               placeholder="-"
                                                               title="Praktik 1 (Pra-UTS) - {{ $ks->siswa->nama }}"
                                                               data-siswa-id="{{ $ks->id }}"
                                                               {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                               class="input-formatif-bulan w-14 text-center font-mono font-bold text-xs py-1.5 border border-emerald-400 bg-white rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                                    </td>
                                                @endif
                                                @if($bCount === count($bulanSemester))
                                                    <td class="py-2.5 px-1.5 text-center border-r border-teal-300 bg-teal-50/60">
                                                        <input type="number" step="0.1" min="0" max="100"
                                                               name="praktik_2[{{ $ks->id }}]"
                                                               value="{{ old('praktik_2.' . $ks->id, $d['praktik_2'] ?? '') }}"
                                                               placeholder="-"
                                                               title="Praktik 2 (Pra-UAS) - {{ $ks->siswa->nama }}"
                                                               data-siswa-id="{{ $ks->id }}"
                                                               {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                               class="input-formatif-bulan w-14 text-center font-mono font-bold text-xs py-1.5 border border-teal-400 bg-white rounded-lg focus:ring-2 focus:ring-teal-500 focus:outline-none transition">
                                                    </td>
                                                @endif
                                            @endforeach

                                        @else
                                            <!-- Mapel Reguler: 1 Nilai Per Bulan -->
                                            @foreach($bulanSemester as $bNum => $bNama)
                                                @php
                                                    $val = $d['bulanNilai'][$bNum] ?? '';
                                                @endphp
                                                <td class="py-2.5 px-1.5 text-center border-r border-slate-200">
                                                    <input type="number" step="0.1" min="0" max="100"
                                                           name="formatif[{{ $ks->id }}][{{ $bNum }}]"
                                                           value="{{ old('formatif.' . $ks->id . '.' . $bNum, $val) }}"
                                                           placeholder="-"
                                                           title="Formatif {{ $bNama }} - {{ $ks->siswa->nama }}"
                                                           data-siswa-id="{{ $ks->id }}"
                                                           {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                           class="input-formatif-bulan w-14 text-center font-mono font-bold text-xs py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                                </td>
                                            @endforeach
                                        @endif

                                        <td class="py-3 px-3 text-center font-mono font-black text-sm bg-emerald-50/50 text-emerald-800 border-r border-emerald-200" id="rata_formatif_{{ $ks->id }}">
                                            {{ ($d['rataFormatif'] ?? null) !== null ? $d['rataFormatif'] : '-' }}
                                        </td>
                                        <!-- CETAK REKAP SEMESTER & FORMATIF 1 SISWA (1 SISWA 1 LEMBAR F4) -->
                                        <td class="py-3 px-4 text-center whitespace-nowrap bg-rose-50/20 space-x-1">
                                            <a href="{{ route('kelas.nilai-formatif.rekap-semester-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-2xs transition"
                                               title="Cetak Rekap Nilai Semester 1 Siswa Ini (Pas 1 Lembar F4)">
                                                <i class="fa-solid fa-print"></i> Rekap F4
                                            </a>
                                            <a href="{{ route('kelas.mapel.nilai.cetak-formatif-siswa-pdf', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'kelasSiswa' => $ks->id, 'tahun' => $tahun]) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-2xs transition"
                                               title="Cetak Lembar Nilai Formatif 1 Siswa Ini (Standar F4 Ber-TTD Lengkap)">
                                                <i class="fa-solid fa-file-pdf"></i> Formatif F4
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    @php
                                        $emptyColspan = $isPjok ? ((count($bulanSemester) * 2) + 4) : ($isAgama ? (count($bulanSemester) + 6) : (count($bulanSemester) + 4));
                                    @endphp
                                    <tr>
                                        <td colspan="{{ $emptyColspan }}" class="py-12 text-center text-slate-400">
                                            Belum ada data siswa di kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Action Bar -->
                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="text-xs text-slate-500 flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            Rata-rata otomatis terhubung ke <strong>Lembar 4 (Nilai Rapor Semua Mapel)</strong>.
                        </div>
                        @if(! $isAnyLocked)
                            <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                                <i class="fa-solid fa-floppy-disk mr-2 text-amber-400"></i> Simpan Nilai Formatif
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- LEMBAR 2: UTS (TENGAH SEMESTER)                                                          -->
    <!-- ========================================================================================= -->
    @if($tab === 'uts')
        <div class="space-y-4">
            <div class="bg-amber-50 border border-amber-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-amber-900">
                <div class="flex items-start space-x-3">
                    <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold text-sm text-amber-950">Lembar 2: Nilai UTS (Sumatif Tengah Semester) — {{ $mapel->nama }}</p>
                        <p class="text-amber-800 mt-0.5">
                            Isikan satu nilai UTS per siswa (skala 0 – 100). Status ketuntasan dihitung otomatis berdasarkan KKM: <strong>{{ $kkm }}</strong>.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                    <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                       class="px-3.5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                       title="Cetak Lembar Laporan Hasil Belajar STS Siswa Sekelas (Format 1 Lembar Tanpa Deskripsi)">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak Lembar STS Siswa (Sekelas)</span>
                    </a>
                    <a href="{{ route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                       class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                       title="Unduh PDF Lembar STS Seluruh Siswa Sekelas (1 File PDF)">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Unduh PDF STS</span>
                    </a>
                    <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 font-bold text-xs shadow-2xs transition flex items-center gap-1.5"
                       title="Buku Leger Rekapitulasi Nilai UTS Seluruh Siswa (Landscape)">
                        <i class="fa-solid fa-book-open"></i>
                        <span>Buku Leger</span>
                    </a>
                </div>
            </div>

            <!-- Form Nilai UTS -->
            <form action="{{ route('kelas.mapel.nilai.uts', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                                    <th class="py-3.5 px-4 w-36 border-r border-slate-200">NIS / NISN</th>
                                    <th class="py-3.5 px-4 border-r border-slate-200">Nama Peserta Didik</th>
                                    <th class="py-3.5 px-4 w-44 text-center border-r border-slate-200">Nilai UTS (0-100)</th>
                                    <th class="py-3.5 px-4 w-36 text-center">Status Ketuntasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($kelasSiswaList as $idx => $ks)
                                    @php
                                        $d = $siswaData[$ks->id] ?? null;
                                        $valUts = $d['nilaiUts'] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                            {{ $idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono text-slate-600 border-r border-slate-200">
                                            <div>{{ $ks->siswa->nis }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $ks->siswa->nisn ?? '-' }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-slate-900 border-r border-slate-200">
                                            {{ $ks->siswa->nama }}
                                        </td>
                                        <td class="py-3 px-4 text-center border-r border-slate-200">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   name="uts[{{ $ks->id }}]"
                                                   id="input_uts_{{ $ks->id }}"
                                                   data-kkm="{{ $kkm }}"
                                                   value="{{ old('uts.' . $ks->id, $valUts) }}"
                                                   placeholder="0-100"
                                                   oninput="updateUtsStatus('{{ $ks->id }}', this.value, {{ $kkm }})"
                                                   {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                   class="w-28 text-center font-mono font-bold text-sm px-2.5 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span id="badge_uts_{{ $ks->id }}" class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $valUts !== null ? ($valUts >= $kkm ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'text-slate-400 bg-slate-100' }}">
                                                {{ $d['keteranganUts'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-slate-400">
                                            Belum ada data siswa di kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">
                            Nilai UTS otomatis terhubung ke perhitungan nilai akhir rapor.
                        </span>
                        @if(! $isAnyLocked)
                            <button type="submit" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                                <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Nilai UTS
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- LEMBAR 3: SEMESTER (UAS / SAS)                                                           -->
    <!-- ========================================================================================= -->
    @if($tab === 'uas')
        <div class="space-y-4">
            <div class="bg-indigo-50 border border-indigo-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-indigo-900">
                <div class="flex items-start space-x-3">
                    <i class="fa-solid fa-circle-info text-indigo-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold text-sm text-indigo-950">Lembar 3: Nilai Semester (UAS / SAS) — {{ $mapel->nama }}</p>
                        <p class="text-indigo-800 mt-0.5">
                            Isikan satu nilai UAS per peserta didik (skala 0 – 100). Status ketuntasan dihitung mengacu pada KKM: <strong>{{ $kkm }}</strong>.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                    <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                       class="px-3.5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                       title="Cetak Lembar Laporan Hasil Belajar SAS Siswa Sekelas (Format 1 Lembar Tanpa Deskripsi)">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak Lembar SAS Siswa (Sekelas)</span>
                    </a>
                    <a href="{{ route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                       class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                       title="Unduh PDF Lembar SAS Seluruh Siswa Sekelas (1 File PDF)">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Unduh PDF SAS</span>
                    </a>
                    <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                       class="px-3 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 font-bold text-xs shadow-2xs transition flex items-center gap-1.5"
                       title="Buku Leger Rekapitulasi Nilai Akhir Semester (Landscape)">
                        <i class="fa-solid fa-book-open"></i>
                        <span>Buku Leger</span>
                    </a>
                </div>
            </div>

            <!-- Form Nilai UAS -->
            <form action="{{ route('kelas.mapel.nilai.uas', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                                    <th class="py-3.5 px-4 w-36 border-r border-slate-200">NIS / NISN</th>
                                    <th class="py-3.5 px-4 border-r border-slate-200">Nama Peserta Didik</th>
                                    <th class="py-3.5 px-4 w-44 text-center border-r border-slate-200">Nilai UAS (0-100)</th>
                                    <th class="py-3.5 px-4 w-36 text-center">Status Ketuntasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($kelasSiswaList as $idx => $ks)
                                    @php
                                        $d = $siswaData[$ks->id] ?? null;
                                        $valUas = $d['nilaiUas'] ?? null;
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                            {{ $idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 font-mono text-slate-600 border-r border-slate-200">
                                            <div>{{ $ks->siswa->nis }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $ks->siswa->nisn ?? '-' }}</div>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-slate-900 border-r border-slate-200">
                                            {{ $ks->siswa->nama }}
                                        </td>
                                        <td class="py-3 px-4 text-center border-r border-slate-200">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   name="uas[{{ $ks->id }}]"
                                                   id="input_uas_{{ $ks->id }}"
                                                   data-kkm="{{ $kkm }}"
                                                   value="{{ old('uas.' . $ks->id, $valUas) }}"
                                                   placeholder="0-100"
                                                   oninput="updateUasStatus('{{ $ks->id }}', this.value, {{ $kkm }})"
                                                   {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                   class="w-28 text-center font-mono font-bold text-sm px-2.5 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span id="badge_uas_{{ $ks->id }}" class="text-[11px] font-bold px-2.5 py-1 rounded-full {{ $valUas !== null ? ($valUas >= $kkm ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'text-slate-400 bg-slate-100' }}">
                                                {{ $d['keteranganUas'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-12 text-center text-slate-400">
                                            Belum ada data siswa di kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <span class="text-xs text-slate-500">
                            Nilai UAS otomatis terhubung ke perhitungan nilai akhir rapor.
                        </span>
                        @if(! $isAnyLocked)
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                                <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Nilai Semester
                            </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- LEMBAR 4: NILAI RAPOR (OTOMATIS SEMUA MATA PELAJARAN)                                     -->
    <!-- ========================================================================================= -->
    @if($tab === 'rapor')
        <div class="space-y-5" x-data="{ mode: 'siswa' }">

            <!-- Mode Switcher: Tampilan per Siswa VS Rekap Leger Sekelas -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center">
                        <i class="fa-solid fa-chart-line mr-1.5"></i> Mode Tampilan:
                    </span>
                    <button type="button"
                            @click="mode = 'siswa'"
                            :class="mode === 'siswa' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold'"
                            class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center">
                        <i class="fa-solid fa-user-check mr-1.5"></i> Per Siswa (Semua Mapel)
                    </button>
                    <button type="button"
                            @click="mode = 'leger'"
                            :class="mode === 'leger' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 font-semibold'"
                            class="px-3.5 py-1.5 rounded-xl text-xs transition flex items-center">
                        <i class="fa-solid fa-table-cells mr-1.5"></i> Rekap Leger Sekelas
                    </button>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="hidden md:inline">Rumus Nilai Rapor:</span>
                    <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[11px] border border-slate-200">
                        (Formatif × {{ $bobotFormatif }}%) + (UTS × {{ $bobotUts }}%) + (UAS × {{ $bobotUas }}%)
                    </code>
                </div>
            </div>

            <!-- ================================================================================= -->
            <!-- MODE 1: PER SISWA (LENGKAP SEMUA MATA PELAJARAN OTOMATIS)                         -->
            <!-- ================================================================================= -->
            <div x-show="mode === 'siswa'" class="space-y-4">
                @if($activeKelasSiswa)
                    <!-- Kartu Navigasi Siswa & Aksi Cepat -->
                    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-5 rounded-2xl shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-amber-400 text-slate-950 font-black text-lg flex items-center justify-center shadow-sm shrink-0">
                                {{ strtoupper(substr($activeKelasSiswa->siswa->nama, 0, 1)) }}
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-amber-300 font-bold uppercase tracking-wider">
                                        Lembar 4 &bull; Rapor Peserta Didik
                                    </span>
                                </div>
                                <h2 class="text-lg sm:text-xl font-black text-white">
                                    {{ $activeKelasSiswa->siswa->nama }}
                                </h2>
                                <p class="text-xs text-indigo-200 mt-0.5 font-mono">
                                    NIS: {{ $activeKelasSiswa->siswa->nis }} &bull; NISN: {{ $activeKelasSiswa->siswa->nisn ?? '-' }} &bull; Kelas {{ $kelas->nama }}
                                </p>
                            </div>
                        </div>

                        <!-- Kontrol Navigasi Siswa (Dropdown + Prev/Next) -->
                        <div class="flex flex-wrap items-center gap-2">
                            @if($prevSiswaId)
                                <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'kelas_siswa_id' => $prevSiswaId, 'tahun' => $tahun]) }}"
                                   class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition flex items-center"
                                   title="Beralih ke siswa sebelumnya">
                                    <i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya
                                </a>
                            @endif

                            <select onchange="window.location.href=this.value"
                                    class="bg-slate-800 text-white font-bold text-xs px-3 py-2 rounded-xl border border-indigo-400/40 focus:ring-2 focus:ring-amber-400 focus:outline-none transition max-w-[220px]">
                                @foreach($kelasSiswaList as $ksIdx => $ksItem)
                                    <option value="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'kelas_siswa_id' => $ksItem->id, 'tahun' => $tahun]) }}"
                                            {{ $ksItem->id === $activeKelasSiswa->id ? 'selected' : '' }}>
                                        ({{ $ksIdx + 1 }}) {{ $ksItem->siswa->nama }}
                                    </option>
                                @endforeach
                            </select>

                            @if($nextSiswaId)
                                <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'kelas_siswa_id' => $nextSiswaId, 'tahun' => $tahun]) }}"
                                   class="px-3 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl transition flex items-center"
                                   title="Beralih ke siswa berikutnya">
                                    Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i>
                                </a>
                            @endif

                            <!-- Quick Button Pratinjau & Rekap Semester Siswa -->
                            <a href="{{ route('kelas.nilai-formatif.rekap-semester-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $activeKelasSiswa->id]) }}"
                               target="_blank"
                               class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition flex items-center shadow-xs"
                               title="Cetak Rekap Nilai Semester 1 Siswa Ini (Pas 1 Lembar F4)">
                                <i class="fa-solid fa-file-lines mr-1.5"></i> Rekap F4
                            </a>
                            <a href="{{ route('raport.preview', $activeKelasSiswa->id) }}"
                               target="_blank"
                               class="px-3 py-2 bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold text-xs rounded-xl transition flex items-center shadow-xs">
                                <i class="fa-solid fa-print mr-1.5"></i> Pratinjau Rapor
                            </a>
                        </div>
                    </div>

                    <!-- Formulir Rapor Siswa (Semua Mata Pelajaran) -->
                    <form action="{{ route('kelas.input-nilai.simpan-rapor-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $activeKelasSiswa->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <input type="hidden" name="semester" value="{{ $semester }}">

                        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <!-- Header Form & Tombol Isi Semua Saran -->
                            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center">
                                        <i class="fa-solid fa-graduation-cap text-indigo-600 mr-2"></i> Capaian Nilai & Catatan Capaian Pembelajaran (Semua Mapel)
                                    </h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Nilai formatif, UTS, UAS, dan predikat terkunci otomatis. Guru cukup mengisi atau menyesuaikan catatan capaian kompetensi.
                                    </p>
                                </div>
                                <button type="button"
                                        onclick="isiSemuaSaranMapel()"
                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition flex items-center shrink-0 shadow-2xs">
                                    <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i> Isi Semua Saran Otomatis
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50/80 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200 text-center">
                                            <th class="py-3 px-3 w-10 border-r border-slate-200">No</th>
                                            <th class="py-3 px-4 text-left border-r border-slate-200 min-w-[200px]">Mata Pelajaran</th>
                                            <th class="py-3 px-2 w-14 border-r border-slate-200">KKM</th>
                                            <th class="py-3 px-2 w-20 border-r border-slate-200 bg-slate-100/40">Formatif ({{ $bobotFormatif }}%)</th>
                                            <th class="py-3 px-2 w-16 border-r border-slate-200 bg-slate-100/40">UTS ({{ $bobotUts }}%)</th>
                                            <th class="py-3 px-2 w-16 border-r border-slate-200 bg-slate-100/40">UAS ({{ $bobotUas }}%)</th>
                                            <th class="py-3 px-3 w-20 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black">Nilai Rapor</th>
                                            <th class="py-3 px-2 w-16 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black">Predikat</th>
                                            <th class="py-3 px-4 text-left min-w-[340px]">Catatan Guru / Capaian Pembelajaran</th>
                                            <th class="py-3 px-2 w-16">Saran</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 font-medium">
                                        @forelse($allMapel as $idx => $m)
                                            @php
                                                $d = $raporSemuaMapelData[$m->id] ?? null;
                                                $valRap = $d['nilaiRapor'] ?? null;
                                                $pred = $d['predikat'] ?? null;
                                                $cat = $d['catatan'] ?? '';
                                                $saranDef = $d['saranDefault'] ?? '';
                                                $isLok = $d['isLocked'] ?? false;
                                            @endphp
                                            <tr class="hover:bg-slate-50/70 transition">
                                                <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                                    {{ $idx + 1 }}
                                                </td>
                                                <td class="py-3 px-4 border-r border-slate-200">
                                                    <div class="font-bold text-slate-900">{{ $m->nama }}</div>
                                                    <div class="text-[10px] text-slate-400">
                                                        {{ $kelas->guruPengampu->firstWhere('mapel_id', $m->id)?->nama ?? 'Guru Mapel' }}
                                                    </div>
                                                </td>
                                                <td class="py-3 px-2 text-center font-mono font-bold text-purple-700 border-r border-slate-200 bg-purple-50/20">
                                                    {{ $d['kkm'] ?? 75 }}
                                                </td>
                                                <td class="py-3 px-2 text-center font-mono font-bold text-slate-600 border-r border-slate-200 bg-slate-50/30">
                                                    {{ ($d['rataFormatif'] ?? null) !== null ? $d['rataFormatif'] : '-' }}
                                                </td>
                                                <td class="py-3 px-2 text-center font-mono font-bold text-slate-600 border-r border-slate-200 bg-slate-50/30">
                                                    {{ ($d['nilaiUts'] ?? null) !== null ? $d['nilaiUts'] : '-' }}
                                                </td>
                                                <td class="py-3 px-2 text-center font-mono font-bold text-slate-600 border-r border-slate-200 bg-slate-50/30">
                                                    {{ ($d['nilaiUas'] ?? null) !== null ? $d['nilaiUas'] : '-' }}
                                                </td>
                                                <td class="py-3 px-3 text-center font-mono font-black text-sm bg-emerald-50/60 text-emerald-800 border-r border-slate-200">
                                                    {{ $valRap !== null ? $valRap : '-' }}
                                                </td>
                                                <td class="py-3 px-2 text-center border-r border-slate-200 bg-emerald-50/60">
                                                    @if($pred)
                                                        @php
                                                            $badgeColors = [
                                                                'A' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                                'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                                'C' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                                'D' => 'bg-rose-100 text-rose-800 border-rose-300',
                                                            ];
                                                            $cls = $badgeColors[$pred] ?? 'bg-slate-100 text-slate-700';
                                                        @endphp
                                                        <span class="inline-block px-2 py-0.5 rounded-md font-mono font-black text-xs border {{ $cls }}">
                                                            {{ $pred }}
                                                        </span>
                                                    @else
                                                        <span class="text-slate-300">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-4">
                                                    <textarea name="catatan[{{ $m->id }}]"
                                                              id="catatan_{{ $m->id }}"
                                                              rows="2"
                                                              data-mapel-id="{{ $m->id }}"
                                                              data-saran-default="{{ $saranDef }}"
                                                              {{ $isLok ? 'disabled' : '' }}
                                                              placeholder="Ketik catatan capaian pembelajaran untuk {{ $m->nama }}..."
                                                              class="textarea-catatan-mapel w-full text-xs font-normal px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition leading-relaxed">{{ old('catatan.' . $m->id, $cat) }}</textarea>
                                                </td>
                                                <td class="py-2.5 px-2 text-center">
                                                    <button type="button"
                                                            onclick="isiSaranMapel('{{ $m->id }}')"
                                                            class="p-2 bg-slate-100 hover:bg-emerald-100 text-slate-600 hover:text-emerald-800 rounded-xl text-xs font-bold transition shadow-2xs"
                                                            title="Isi template kalimat saran sesuai predikat {{ $pred ?? 'B' }}">
                                                        <i class="fa-solid fa-wand-sparkles text-emerald-600"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="py-12 text-center text-slate-400">
                                                    Belum ada mata pelajaran terdaftar pada kelas ini.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <!-- Ringkasan Nilai Rapor Siswa -->
                                    <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold text-xs">
                                        <tr>
                                            <td colspan="6" class="py-3 px-4 text-right uppercase tracking-wider text-slate-700">
                                                Total Nilai Rapor & Rata-rata:
                                            </td>
                                            <td class="py-3 px-3 text-center font-mono font-black text-sm bg-emerald-100/60 text-emerald-900 border-r border-slate-200">
                                                {{ $totalNilaiRapor > 0 ? $totalNilaiRapor : '-' }}
                                            </td>
                                            <td class="py-3 px-2 text-center border-r border-slate-200 bg-emerald-100/60 font-mono font-black text-emerald-900">
                                                {{ $predikatRataRata ?? '-' }}
                                            </td>
                                            <td colspan="2" class="py-3 px-4 text-slate-600 font-semibold">
                                                Rata-rata: <strong class="text-slate-900 font-mono">{{ $rataRataNilaiRapor !== null ? $rataRataNilaiRapor : '-' }}</strong> &bull; Predikat Keseluruhan: <strong class="text-indigo-700">{{ $predikatRataRata ?? '-' }}</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Baris Kehadiran Siswa (SIA) -->
                            <div class="p-4 bg-slate-50/70 border-t border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center space-x-2 text-xs font-bold text-slate-700">
                                    <i class="fa-solid fa-clipboard-user text-indigo-600"></i>
                                    <span>Rekap Kehadiran Siswa (Semester Ini):</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs">
                                    <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                                        <span class="font-bold text-amber-700">Sakit:</span>
                                        <input type="number"
                                               name="kehadiran[sakit]"
                                               min="0"
                                               value="{{ old('kehadiran.sakit', $kehadiranActiveSiswa->sakit ?? 0) }}"
                                               class="w-14 text-center font-mono font-bold text-xs py-0.5 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                                        <span class="text-slate-400">hari</span>
                                    </div>
                                    <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                                        <span class="font-bold text-blue-700">Izin:</span>
                                        <input type="number"
                                               name="kehadiran[izin]"
                                               min="0"
                                               value="{{ old('kehadiran.izin', $kehadiranActiveSiswa->izin ?? 0) }}"
                                               class="w-14 text-center font-mono font-bold text-xs py-0.5 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                                        <span class="text-slate-400">hari</span>
                                    </div>
                                    <div class="flex items-center space-x-1.5 bg-white px-3 py-1.5 rounded-xl border border-slate-200">
                                        <span class="font-bold text-rose-700">Tanpa Keterangan:</span>
                                        <input type="number"
                                               name="kehadiran[alpa]"
                                               min="0"
                                               value="{{ old('kehadiran.alpa', $kehadiranActiveSiswa->alpa ?? 0) }}"
                                               class="w-14 text-center font-mono font-bold text-xs py-0.5 border border-slate-300 rounded-lg focus:ring-1 focus:ring-indigo-500">
                                        <span class="text-slate-400">hari</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi Simpan & Lanjut -->
                            <div class="p-4 bg-white border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <span class="text-xs text-slate-500 flex items-center">
                                    <i class="fa-solid fa-circle-check text-emerald-600 mr-1.5"></i>
                                    Perubahan catatan rapor disimpan sekaligus untuk seluruh mata pelajaran.
                                </span>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                                    <button type="submit"
                                            name="action"
                                            value="simpan"
                                            class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                                        <i class="fa-solid fa-floppy-disk mr-2 text-amber-400"></i> Simpan Catatan Rapor
                                    </button>
                                    @if($nextSiswaId)
                                        <button type="submit"
                                                name="action"
                                                value="simpan_lanjut"
                                                class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                                            <span>Simpan & Lanjut Siswa Berikutnya</span>
                                            <i class="fa-solid fa-arrow-right ml-2"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="bg-white p-12 text-center rounded-2xl border border-slate-200 text-slate-400">
                        Belum ada peserta didik di kelas ini.
                    </div>
                @endif
            </div>

            <!-- ================================================================================= -->
            <!-- MODE 2: REKAP LEGER SEKELAS (SEMUA SISWA X SEMUA MAPEL)                           -->
            <!-- ================================================================================= -->
            <div x-show="mode === 'leger'" style="display: none;" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden space-y-0">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center">
                            <i class="fa-solid fa-table text-indigo-600 mr-2"></i> Rekap Leger Nilai Rapor Sekelas (Semua Siswa)
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Ikhtisar nilai akhir rapor seluruh siswa untuk semua mata pelajaran. Klik tombol pada siswa untuk mengedit catatan.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('kelas.nilai-formatif.rekap-semester-sekelas', $kelas->id) }}"
                           target="_blank"
                           class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition flex items-center shadow-xs">
                            <i class="fa-solid fa-print mr-1.5 text-amber-300"></i> Rekap Semester Sekelas (F4)
                        </a>
                        <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                           class="px-3.5 py-1.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs transition flex items-center shadow-xs">
                            <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Rapor Sekelas (F4)
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200 text-center">
                                <th class="py-3 px-3 w-10 border-r border-slate-200">No</th>
                                <th class="py-3 px-3 w-28 border-r border-slate-200 text-left">NIS</th>
                                <th class="py-3 px-4 min-w-[180px] border-r border-slate-200 text-left">Nama Siswa</th>
                                @foreach($allMapel as $m)
                                    <th class="py-3 px-2 w-16 border-r border-slate-200" title="{{ $m->nama }}">
                                        {{ substr($m->nama, 0, 4) }}
                                    </th>
                                @endforeach
                                <th class="py-3 px-2 w-20 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black">Total</th>
                                <th class="py-3 px-2 w-16 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black">Rata2</th>
                                <th class="py-3 px-2 w-14 border-r border-slate-200 bg-emerald-50 text-emerald-950 font-black">Pred</th>
                                <th class="py-3 px-3 w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($kelasSiswaList as $idx => $ksItem)
                                @php
                                    $rek = $rekapSekelasRapor[$ksItem->id] ?? null;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition {{ ($activeKelasSiswa && $ksItem->id === $activeKelasSiswa->id) ? 'bg-indigo-50/40' : '' }}">
                                    <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="py-3 px-3 font-mono text-slate-600 border-r border-slate-200">
                                        {{ $ksItem->siswa->nis }}
                                    </td>
                                    <td class="py-3 px-4 border-r border-slate-200 font-bold text-slate-900 whitespace-nowrap">
                                        {{ $ksItem->siswa->nama }}
                                    </td>
                                    @foreach($allMapel as $m)
                                        @php
                                            $sc = $rek['scores'][$m->id] ?? null;
                                        @endphp
                                        <td class="py-3 px-2 text-center font-mono border-r border-slate-200 {{ $sc !== null ? 'font-bold text-slate-800' : 'text-slate-300' }}">
                                            {{ $sc !== null ? $sc : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="py-3 px-2 text-center font-mono font-black border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        {{ ($rek['total'] ?? 0) > 0 ? $rek['total'] : '-' }}
                                    </td>
                                    <td class="py-3 px-2 text-center font-mono font-black border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        {{ ($rek['rataRata'] ?? null) !== null ? $rek['rataRata'] : '-' }}
                                    </td>
                                    <td class="py-3 px-2 text-center font-mono font-black border-r border-slate-200 bg-emerald-50/60 text-emerald-900">
                                        {{ $rek['predikat'] ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'kelas_siswa_id' => $ksItem->id, 'tahun' => $tahun]) }}"
                                           class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-[11px] font-bold transition inline-flex items-center">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i> Buka
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $allMapel->count() + 7 }}" class="py-12 text-center text-slate-400">
                                        Belum ada data siswa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    @endif

    <!-- Modal Dialog Cetak Nilai Bulanan -->
    <div id="modalCetakBulanan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-calendar-days text-purple-600 mr-2 text-base"></i> Cetak Nilai Bulanan
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Kelas {{ $kelas->nama }} &bull; TP {{ $tahunAjaran->nama_lengkap ?? '-' }}</p>
                </div>
                <button type="button" onclick="document.getElementById('modalCetakBulanan').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="space-y-4 mt-4 text-xs">
                <!-- 1. Pilih Bulan -->
                <div>
                    <label class="block font-bold uppercase tracking-wider text-slate-700 mb-2">
                        1. Pilih Bulan Penilaian (Juli – Desember)
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        @php
                            $bulanGanjil = [
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];
                            $currBulan = now()->month;
                        @endphp
                        @foreach($bulanGanjil as $bNum => $bNama)
                            <label class="relative flex items-center justify-center p-2.5 rounded-xl border border-slate-200 cursor-pointer hover:bg-purple-50 hover:border-purple-300 transition text-center select-none font-bold text-slate-700 has-[:checked]:bg-purple-600 has-[:checked]:text-white has-[:checked]:border-purple-600 has-[:checked]:shadow-xs">
                                <input type="radio" name="pilih_bulan_cetak" value="{{ $bNum }}" {{ ($currBulan == $bNum || ($currBulan < 7 && $bNum == 10)) ? 'checked' : '' }} class="sr-only" onchange="updateLinkCetakBulanan()">
                                <span>{{ $bNama }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-500 bg-slate-50 p-2 rounded-xl border border-slate-200/60">
                        <span>Pilihan semester genap:</span>
                        <select id="selectBulanGenap" onchange="pilihBulanGenap(this.value)" class="text-xs border border-slate-300 rounded-lg px-2 py-1 font-semibold bg-white text-slate-700">
                            <option value="">-- Bulan Genap (Jan - Jun) --</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                        </select>
                    </div>
                </div>

                <!-- 2. Pilih Format Dokumen Cetak -->
                <div>
                    <label class="block font-bold uppercase tracking-wider text-slate-700 mb-2">
                        2. Pilih Format Dokumen Cetak
                    </label>
                    <div class="space-y-2.5">
                        <!-- Opsi A: Rapor Bulanan Sekelas (1 Siswa 1 Halaman F4) -->
                        <a id="btnCetakBulananSekelas" href="{{ route('kelas.nilai-formatif.cetak-sekelas', ['kelas' => $kelas->id, 'bulan' => ($currBulan >= 7 ? $currBulan : 10)]) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-purple-200 bg-purple-50/60 hover:bg-purple-100/70 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-purple-600 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-purple-950 text-sm flex items-center justify-between">
                                        <span>Cetak Rapor Bulanan Sekelas (F4)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-purple-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Format resmi <strong>1 Siswa 1 Halaman F4</strong> lengkap seluruh mapel, nilai, KKM, kehadiran, dan 3 kolom tanda tangan resmi untuk dibagikan ke orang tua siswa.
                                    </p>
                                </div>
                            </div>
                        </a>

                        <!-- Opsi B: Rekap Leger Bulanan (1 Lembar) -->
                        <a id="btnCetakLegerBulanan" href="{{ route('kelas.nilai-formatif.cetak-rekap', ['kelas' => $kelas->id, 'bulan' => ($currBulan >= 7 ? $currBulan : 10)]) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-table-cells"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-slate-900 text-sm flex items-center justify-between">
                                        <span>Cetak Rekap Leger Bulanan (1 Lembar)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Tabel rekapitulasi nilai seluruh siswa x semua mata pelajaran dalam 1 halaman untuk arsip bulanan sekolah.
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="document.getElementById('modalCetakBulanan').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Dialog Cetak Rekap Semester Otomatis -->
    <div id="modalCetakRekapSemester" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-print text-blue-600 mr-2 text-base"></i> Cetak Rekap Semester Otomatis
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Kelas {{ $kelas->nama }} &bull; Semester {{ ucfirst($semester) }} &bull; TP {{ $tahunAjaran->nama_lengkap ?? '-' }}</p>
                </div>
                <button type="button" onclick="document.getElementById('modalCetakRekapSemester').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <div class="space-y-4 mt-4 text-xs">
                <!-- Informasi Otomatisasi -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-blue-900 leading-relaxed flex items-start space-x-2.5">
                    <i class="fa-solid fa-circle-check text-blue-600 text-sm mt-0.5 shrink-0"></i>
                    <div>
                        <span class="font-bold">Otomatis dari Database:</span>
                        <p class="text-[11px] text-blue-800 mt-0.5">
                            Sistem otomatis menghitung rata-rata nilai setiap mata pelajaran berdasarkan nilai bulanan yang sudah diisi. <strong>Bulan yang kosong tidak ikut dihitung</strong>. Guru tidak perlu memasukkan ulang nilai atau menghitung rata-rata secara manual.
                        </p>
                    </div>
                </div>

                <!-- Pilihan Format Cetak -->
                <div>
                    <label class="block font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Pilih Format Dokumen Cetak Rekap Semester:
                    </label>
                    <div class="space-y-2.5">
                        <!-- Opsi A: Rekap Semester Sekelas (1 Siswa 1 Lembar F4) -->
                        <a href="{{ route('kelas.nilai-formatif.rekap-semester-sekelas', $kelas->id) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-blue-300 bg-blue-50/60 hover:bg-blue-100/70 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-blue-950 text-sm flex items-center justify-between">
                                        <span>1 Siswa 1 Lembar F4 Sekelas (Rekomendasi)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-blue-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Menampilkan semua mata pelajaran, nilai bulanan Juli–Desember, nilai akhir rata-rata, predikat, ketidakhadiran, catatan guru, dan tanda tangan resmi. Pas 1 lembar F4 portrait per siswa untuk seluruh siswa sekelas sekaligus.
                                    </p>
                                </div>
                            </div>
                        </a>

                        <!-- Opsi B: Lembar Nilai Rekapitulasi Akhir Semester (SAS) Sekelas -->
                        <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-book"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-slate-900 text-sm flex items-center justify-between">
                                        <span>Lembar Nilai SAS Sekelas (Semua Mata Pelajaran)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Daftar nilai kolektif resmi akhir semester memuat tabel seluruh siswa x semua mapel, total nilai, rata-rata, dan peringkat kelas.
                                    </p>
                                </div>
                            </div>
                        </a>

                        <!-- Opsi C: Lembar Nilai Tengah Semester (UTS / STS) Sekelas -->
                        <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-amber-200 bg-amber-50/50 hover:bg-amber-100/70 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-600 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-amber-950 text-sm flex items-center justify-between">
                                        <span>Lembar Nilai UTS / STS Sekelas (Semua Mata Pelajaran)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-amber-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Daftar nilai kolektif resmi tengah semester memuat tabel seluruh siswa x semua mapel, total nilai, rata-rata, dan absensi.
                                    </p>
                                </div>
                            </div>
                        </a>

                        <!-- Opsi D: Lembar Laporan Hasil Belajar STS & SAS Siswa Sekelas (Format 1 Lembar Tanpa Deskripsi) -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                               class="p-2.5 rounded-xl border border-amber-300 bg-amber-50 hover:bg-amber-100 transition text-center flex items-center justify-center gap-1.5 font-bold text-xs text-amber-900 shadow-2xs"
                               title="Cetak Lembar Laporan Hasil Belajar STS Siswa Sekelas (Format 1 Lembar Ringkas)">
                                <i class="fa-solid fa-file-invoice text-amber-600"></i>
                                <span>Lembar STS Siswa (Sekelas)</span>
                            </a>
                            <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                               class="p-2.5 rounded-xl border border-indigo-300 bg-indigo-50 hover:bg-indigo-100 transition text-center flex items-center justify-center gap-1.5 font-bold text-xs text-indigo-900 shadow-2xs"
                               title="Cetak Lembar Laporan Hasil Belajar SAS Siswa Sekelas (Format 1 Lembar Ringkas)">
                                <i class="fa-solid fa-file-invoice text-indigo-600"></i>
                                <span>Lembar SAS Siswa (Sekelas)</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="document.getElementById('modalCetakRekapSemester').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPT PERHITUNGAN REAL-TIME & GENERATOR SARAN -->
<script>
    // Tab 4: Isi saran otomatis untuk 1 mata pelajaran
    function isiSaranMapel(mapelId) {
        const textarea = document.getElementById(`catatan_${mapelId}`);
        if (!textarea) return;
        const saranDefault = textarea.dataset.saranDefault || '';
        if (saranDefault) {
            textarea.value = saranDefault;
        }
    }

    // Tab 4: Isi saran otomatis untuk SEMUA mata pelajaran sekaligus
    function isiSemuaSaranMapel() {
        document.querySelectorAll('.textarea-catatan-mapel').forEach(textarea => {
            const saranDefault = textarea.dataset.saranDefault || '';
            if (saranDefault && textarea.value.trim() === '') {
                textarea.value = saranDefault;
            }
        });
    }

    // Tab 1: Real-time hitung rata-rata formatif saat mengetik nilai
    document.querySelectorAll('.input-formatif-bulan').forEach(input => {
        input.addEventListener('input', function() {
            const ksId = this.dataset.siswaId;
            const rowInputs = document.querySelectorAll(`.input-formatif-bulan[data-siswa-id="${ksId}"]`);
            let total = 0;
            let count = 0;

            rowInputs.forEach(inp => {
                const val = inp.value.trim().replace(',', '.');
                if (val !== '' && !isNaN(val)) {
                    total += parseFloat(val);
                    count++;
                }
            });

            const labelRata = document.getElementById(`rata_formatif_${ksId}`);
            if (labelRata) {
                labelRata.textContent = count > 0 ? (total / count).toFixed(1) : '-';
            }
        });
    });

    // Tab 2: Real-time status UTS saat mengetik
    function updateUtsStatus(ksId, rawVal, kkm) {
        const badge = document.getElementById(`badge_uts_${ksId}`);
        if (!badge) return;
        if (rawVal.trim() !== '' && !isNaN(rawVal)) {
            const val = parseFloat(rawVal);
            if (val >= kkm) {
                badge.textContent = 'Tuntas';
                badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800';
            } else {
                badge.textContent = 'Belum Tuntas';
                badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800';
            }
        } else {
            badge.textContent = '-';
            badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full text-slate-400 bg-slate-100';
        }
    }

    // Tab 3: Real-time status UAS saat mengetik
    function updateUasStatus(ksId, rawVal, kkm) {
        const badge = document.getElementById(`badge_uas_${ksId}`);
        if (!badge) return;
        if (rawVal.trim() !== '' && !isNaN(rawVal)) {
            const val = parseFloat(rawVal);
            if (val >= kkm) {
                badge.textContent = 'Tuntas';
                badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800';
            } else {
                badge.textContent = 'Belum Tuntas';
                badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full bg-rose-100 text-rose-800';
            }
        } else {
            badge.textContent = '-';
            badge.className = 'text-[11px] font-bold px-2.5 py-1 rounded-full text-slate-400 bg-slate-100';
        }
    }

    // Modal Cetak Bulanan Dynamic Links
    const urlCetakBulananSekelasBase = "{{ route('kelas.nilai-formatif.cetak-sekelas', $kelas->id) }}";
    const urlCetakLegerBulananBase = "{{ route('kelas.nilai-formatif.cetak-rekap', $kelas->id) }}";

    function updateLinkCetakBulanan() {
        let checkedBulan = document.querySelector('input[name="pilih_bulan_cetak"]:checked');
        let bulan = checkedBulan ? checkedBulan.value : 10;
        
        let btnSekelas = document.getElementById('btnCetakBulananSekelas');
        let btnLeger = document.getElementById('btnCetakLegerBulanan');
        if (btnSekelas) {
            btnSekelas.href = urlCetakBulananSekelasBase + '?bulan=' + bulan;
        }
        if (btnLeger) {
            btnLeger.href = urlCetakLegerBulananBase + '?bulan=' + bulan;
        }
    }

    function pilihBulanGenap(val) {
        if (!val) return;
        document.querySelectorAll('input[name="pilih_bulan_cetak"]').forEach(r => r.checked = false);
        let btnSekelas = document.getElementById('btnCetakBulananSekelas');
        let btnLeger = document.getElementById('btnCetakLegerBulanan');
        if (btnSekelas) {
            btnSekelas.href = urlCetakBulananSekelasBase + '?bulan=' + val;
        }
        if (btnLeger) {
            btnLeger.href = urlCetakLegerBulananBase + '?bulan=' + val;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateLinkCetakBulanan();
    });
</script>
@endsection
