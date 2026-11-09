@extends('layouts.app')

@section('title', $tab === 'pembiasaan' ? 'Pembiasaan & Kehadiran - ' . $kelas->nama : 'Nilai ' . ($mapel->nama ?? 'Mapel') . ' - ' . $kelas->nama)

@section('content')
<div class="space-y-6 pb-20">

    <!-- 1. HEADER KELAS & MAPEL (CLEAN & COMPACT) -->
    <div class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col gap-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-start sm:items-center space-x-3.5">
                <a href="{{ route('kelas.show', $kelas->id) }}" class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition shrink-0" title="Kembali ke Kelas">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider {{ $tab === 'pembiasaan' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                            {{ $tab === 'pembiasaan' ? 'Tab Pembiasaan & Kehadiran (SIA)' : 'Input Nilai Rapor Terpadu' }}
                        </span>
                        <span class="text-xs font-semibold text-slate-500">
                            Kelas {{ $kelas->nama }} &bull; Semester {{ ucfirst($semester) }} &bull; TP {{ $tahunAjaran->nama_lengkap ?? '-' }}
                        </span>
                    </div>

                    @if($tab === 'pembiasaan')
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex flex-wrap items-center gap-2.5">
                            <span>Pembiasaan Karakter & Absensi (SIA)</span>
                            <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Periode: {{ strtoupper($periodeSia) }}
                            </span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                            &bull; Total: <strong class="text-slate-800">{{ $totalSiswa }} Siswa</strong>
                            &bull; Catatan kehadiran dan karakter terhubung ke rapor siswa
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
                            &bull; Rumus: <strong class="text-indigo-600 font-mono">Formatif ({{ $bobotFormatif }}%) + STS ({{ $bobotUts }}%) + SAS ({{ $bobotUas }}%)</strong>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Toolbar Kanan: Tombol Cetak & Menu "⋯ Lainnya" -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- DROPDOWN CETAK UTAMA -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak</span>
                        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5"></i>
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100">
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Lembar Rapor Siswa (F4)
                        </div>
                        <div class="py-1">
                            <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-print text-amber-500 w-4"></i>
                                <span>Cetak Rapor STS (Sekelas F4)</span>
                            </a>
                            <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-print text-blue-600 w-4"></i>
                                <span>Cetak Rapor SAS (Sekelas F4)</span>
                            </a>
                        </div>
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Rekapitulasi & Leger
                        </div>
                        <div class="py-1">
                            <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-book-open text-emerald-600 w-4"></i>
                                <span>Buku Leger Semester (SAS)</span>
                            </a>
                            <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-book text-amber-600 w-4"></i>
                                <span>Buku Leger Tengah Semester (STS)</span>
                            </a>
                            @if($mapel)
                                <a href="{{ route('kelas.mapel.nilai.cetak-formatif-pdf', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" target="_blank"
                                   class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-calendar-days text-purple-600 w-4"></i>
                                    <span>Cetak Formatif Bulanan (Mapel Ini)</span>
                                </a>
                            @endif
                        </div>
                        <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Unduh PDF Lengkap
                        </div>
                        <div class="py-1">
                            <a href="{{ route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-file-pdf text-rose-500 w-4"></i>
                                <span>Unduh PDF STS Sekelas</span>
                            </a>
                            <a href="{{ route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-file-pdf text-rose-600 w-4"></i>
                                <span>Unduh PDF SAS Sekelas</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- DROPDOWN "⋯ LAINNYA" (SECONDARY ACTIONS) -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-ellipsis"></i>
                        <span>Lainnya</span>
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 divide-y divide-slate-100">
                        <div class="py-1">
                            @if($mapel)
                                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                                   class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-calendar-check text-blue-500 w-4"></i>
                                    <span>Input Rincian Formatif Bulanan</span>
                                </a>
                                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                                   class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-list-check text-emerald-500 w-4"></i>
                                    <span>Penilaian Formatif Harian (TP)</span>
                                </a>
                            @endif
                            <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'xlsx', 'periode' => $periodeSia]) }}"
                               class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                <i class="fa-solid fa-file-excel text-emerald-600 w-4"></i>
                                <span>Unduh Template Excel Pembiasaan</span>
                            </a>
                        </div>
                        <div class="py-1">
                            @if($isAnyLocked)
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('kelas.mapel.nilai.buka-kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" onsubmit="return confirm('Buka kunci nilai rapor mata pelajaran ini?')">
                                        @csrf
                                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                        <button type="submit" class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 transition">
                                            <i class="fa-solid fa-lock-open text-amber-500 w-4"></i>
                                            <span>Buka Kunci Nilai Rapor</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form action="{{ route('kelas.mapel.nilai.kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" onsubmit="return confirm('Kunci nilai rapor mata pelajaran ini?')">
                                    @csrf
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                                    <input type="hidden" name="semester" value="{{ $semester }}">
                                    <button type="submit" class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                        <i class="fa-solid fa-lock text-slate-500 w-4"></i>
                                        <span>Kunci Nilai Rapor</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Status Kunci -->
                @if($isAnyLocked)
                    <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-lock mr-1.5 text-emerald-600"></i> Terkunci
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-semibold bg-slate-50 text-slate-600 border border-slate-200">
                        <i class="fa-solid fa-pen-to-square mr-1.5 text-slate-400"></i> Mode Input
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. TAB NAVIGASI UTAMA (MATA PELAJARAN + TAB PEMBIASAAN) -->
    <div class="bg-white p-2 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between gap-2 overflow-x-auto scrollbar-thin">
        <div class="flex items-center gap-1.5 min-w-max">
            <!-- TABS MATA PELAJARAN DENGAN PROGRES (Contoh: 12/30) -->
            @foreach($allMapel as $m)
                @php
                    $isCurrentMapel = ($tab !== 'pembiasaan' && $m->id === $mapel->id);
                    $doneCount = $progressMapel[$m->id] ?? 0;
                    $isCompleted = ($totalSiswa > 0 && $doneCount >= $totalSiswa);
                @endphp
                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $m->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
                   class="px-3.5 py-2 rounded-xl font-bold text-xs transition flex items-center gap-2 shrink-0 {{ $isCurrentMapel ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200/70' }}">
                    <span>{{ $m->nama }}</span>
                    <!-- Badge Progres -->
                    <span class="text-[10px] px-1.5 py-0.5 rounded-md font-mono font-black {{ $isCurrentMapel ? ($isCompleted ? 'bg-emerald-400 text-slate-950' : 'bg-amber-400 text-slate-950') : ($isCompleted ? 'bg-emerald-100 text-emerald-800' : ($doneCount > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600')) }}">
                        @if($isCompleted)
                            <i class="fa-solid fa-check mr-0.5"></i>
                        @endif
                        {{ $doneCount }}/{{ $totalSiswa }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- TAB PEMBIASAAN & KEHADIRAN (SIA) BERADA DI HALAMAN YANG SAMA -->
        <div class="pl-2 border-l border-slate-200 shrink-0">
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id ?? null, 'tab' => 'pembiasaan', 'tahun' => $tahun]) }}"
               class="px-4 py-2 rounded-xl font-bold text-xs transition flex items-center gap-2 shrink-0 {{ $tab === 'pembiasaan' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300' }}">
                <i class="fa-solid fa-star-half-stroke text-sm {{ $tab === 'pembiasaan' ? 'text-slate-950' : 'text-amber-600' }}"></i>
                <span>Pembiasaan & Kehadiran</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-md font-mono font-black {{ $tab === 'pembiasaan' ? 'bg-slate-950 text-amber-300' : 'bg-amber-200 text-amber-950' }}">
                    {{ $countPembiasaan }}/{{ $totalSiswa }}
                </span>
            </a>
        </div>
    </div>

    <!-- ========================================================================================= -->
    <!-- TAB 1: SATU HALAMAN INPUT PER MAPEL (TERPADU: FORMATIF + STS + SAS = NILAI RAPOR)          -->
    <!-- ========================================================================================= -->
    @if($tab === 'mapel')
        <div class="space-y-4">
            <!-- Alert Petunjuk & Bobot -->
            <div class="bg-blue-50/80 border border-blue-200/80 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-blue-900">
                <div class="flex items-start space-x-3">
                    <i class="fa-solid fa-wand-magic-sparkles text-blue-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold text-sm text-blue-950">Lembar Penilaian Terpadu — {{ $mapel->nama }}</p>
                        <p class="text-blue-800 mt-0.5">
                            Kolom <strong>Formatif ({{ $bobotFormatif }}%)</strong> terisi otomatis dari rata-rata bulanan. Guru cukup mengisi <strong>STS ({{ $bobotUts }}%)</strong> dan <strong>SAS ({{ $bobotUas }}%)</strong>.
                            Nilai Rapor dan Predikat terhitung otomatis secara langsung.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto shrink-0">
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-blue-200 text-blue-900 font-mono font-bold text-[11px]">
                        KKM: {{ $kkm }}
                    </span>
                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                       class="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-100 text-blue-800 border border-blue-200 font-bold text-xs transition flex items-center gap-1.5"
                       title="Buka rincian nilai ulangan bulanan untuk mapel ini">
                        <i class="fa-solid fa-calendar-days text-blue-600"></i>
                        <span>Detail Bulanan</span>
                    </a>
                </div>
            </div>

            <!-- FORM NILAI TERPADU -->
            <form id="formNilaiTerpadu" action="{{ route('kelas.mapel.nilai.simpan-terpadu', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="semester" value="{{ $semester }}">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto max-h-[70vh]">
                        <table class="w-full text-left text-xs border-collapse">
                            <!-- STICKY TABLE HEADER -->
                            <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 shadow-xs text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3 px-3 w-12 text-center border-r border-slate-200">No</th>
                                    <th class="py-3 px-4 min-w-[220px] border-r border-slate-200">Nama Peserta Didik</th>
                                    <th class="py-3 px-3 w-32 text-center border-r border-slate-200 bg-slate-50/80">
                                        <div>Formatif ({{ $bobotFormatif }}%)</div>
                                        <div class="text-[9px] text-slate-400 font-medium lowercase">otomatis bulanan</div>
                                    </th>
                                    <th class="py-3 px-3 w-36 text-center border-r border-slate-200 bg-amber-50/70 text-amber-950 font-black">
                                        <div>STS ({{ $bobotUts }}%)</div>
                                        <div class="text-[9px] text-amber-700/80 font-medium lowercase">diisi guru (0-100)</div>
                                    </th>
                                    <th class="py-3 px-3 w-36 text-center border-r border-slate-200 bg-blue-50/70 text-blue-950 font-black">
                                        <div>SAS ({{ $bobotUas }}%)</div>
                                        <div class="text-[9px] text-blue-700/80 font-medium lowercase">diisi guru (0-100)</div>
                                    </th>
                                    <th class="py-3 px-3 w-32 text-center border-r border-slate-200 bg-emerald-50/80 text-emerald-950 font-black">
                                        <div>Nilai Rapor</div>
                                        <div class="text-[9px] text-emerald-700 font-medium lowercase">otomatis</div>
                                    </th>
                                    <th class="py-3 px-3 w-24 text-center border-r border-slate-200 bg-emerald-50/80 text-emerald-950 font-black">
                                        <div>Predikat</div>
                                        <div class="text-[9px] text-emerald-700 font-medium lowercase">otomatis</div>
                                    </th>
                                    <th class="py-3 px-4 w-36 text-center">
                                        <div>Catatan Guru</div>
                                        <div class="text-[9px] text-slate-400 font-medium lowercase">ikon per baris</div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($kelasSiswaList as $idx => $ks)
                                    @php
                                        $d = $siswaData[$ks->id] ?? null;
                                        $valFmt = $d['rataFormatif'] ?? null;
                                        $valUts = $d['nilaiUts'] ?? null;
                                        $valUas = $d['nilaiUas'] ?? null;
                                        $valRap = $d['nilaiRapor'] ?? null;
                                        $pred = $d['predikat'] ?? null;
                                        $catatan = $d['catatan'] ?? '';
                                        $saranDef = $d['saranDefault'] ?? '';
                                        $hasCatatan = !empty(trim($catatan));
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition" id="row_student_{{ $ks->id }}">
                                        <!-- No -->
                                        <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                            {{ $idx + 1 }}
                                        </td>

                                        <!-- Nama Siswa -->
                                        <td class="py-3 px-4 border-r border-slate-200">
                                            <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                NIS: {{ $ks->siswa->nis ?? '-' }} &bull; NISN: {{ $ks->siswa->nisn ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- Formatif (40%) - HANYA TAMPIL / READ-ONLY -->
                                        <td class="py-3 px-3 text-center border-r border-slate-200 bg-slate-50/50">
                                            <input type="hidden" id="formatif_raw_{{ $ks->id }}" value="{{ $valFmt !== null ? $valFmt : '' }}">
                                            @if($valFmt !== null)
                                                @if($valFmt < $kkm)
                                                    <span class="inline-block px-2.5 py-1 rounded-lg font-mono font-bold text-xs text-rose-700 bg-rose-50 border border-rose-200" title="Di bawah KKM ({{ $kkm }})">
                                                        {{ \App\Services\HitungNilaiService::formatAngka($valFmt) }}
                                                    </span>
                                                @else
                                                    <span class="inline-block px-2.5 py-1 rounded-lg font-mono font-bold text-xs text-slate-800 bg-slate-100 border border-slate-200">
                                                        {{ \App\Services\HitungNilaiService::formatAngka($valFmt) }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="font-mono text-slate-300 font-bold">–</span>
                                            @endif
                                        </td>

                                        <!-- STS (30%) - INPUT GURU -->
                                        <td class="py-3 px-3 text-center border-r border-slate-200 bg-amber-50/20">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   name="sts[{{ $ks->id }}]"
                                                   id="input_sts_{{ $ks->id }}"
                                                   data-row="{{ $idx }}"
                                                   data-ks-id="{{ $ks->id }}"
                                                   value="{{ old('sts.' . $ks->id, $valUts !== null ? \App\Services\HitungNilaiService::formatAngka($valUts) : '') }}"
                                                   placeholder="–"
                                                   oninput="recalculateRow({{ $ks->id }}, {{ $kkm }}, {{ $bobotFormatif }}, {{ $bobotUts }}, {{ $bobotUas }})"
                                                   {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                   class="input-nilai-sts w-24 text-center font-mono font-bold text-sm px-2.5 py-1.5 border rounded-xl focus:ring-2 focus:ring-amber-400 focus:outline-none transition {{ $valUts !== null && $valUts < $kkm ? 'bg-rose-50 text-rose-700 border-rose-400' : 'border-slate-300 bg-white' }}">
                                        </td>

                                        <!-- SAS (30%) - INPUT GURU -->
                                        <td class="py-3 px-3 text-center border-r border-slate-200 bg-blue-50/20">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   name="sas[{{ $ks->id }}]"
                                                   id="input_sas_{{ $ks->id }}"
                                                   data-row="{{ $idx }}"
                                                   data-ks-id="{{ $ks->id }}"
                                                   value="{{ old('sas.' . $ks->id, $valUas !== null ? \App\Services\HitungNilaiService::formatAngka($valUas) : '') }}"
                                                   placeholder="–"
                                                   oninput="recalculateRow({{ $ks->id }}, {{ $kkm }}, {{ $bobotFormatif }}, {{ $bobotUts }}, {{ $bobotUas }})"
                                                   {{ ($d['isLocked'] ?? false) ? 'disabled' : '' }}
                                                   class="input-nilai-sas w-24 text-center font-mono font-bold text-sm px-2.5 py-1.5 border rounded-xl focus:ring-2 focus:ring-blue-400 focus:outline-none transition {{ $valUas !== null && $valUas < $kkm ? 'bg-rose-50 text-rose-700 border-rose-400' : 'border-slate-300 bg-white' }}">
                                        </td>

                                        <!-- Nilai Rapor (Otomatis) -->
                                        <td class="py-3 px-3 text-center border-r border-slate-200 bg-emerald-50/30">
                                            @if($valRap !== null)
                                                @if($valRap < $kkm)
                                                    <span id="rapor_val_{{ $ks->id }}" class="font-mono font-black text-sm px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200" title="Di bawah KKM ({{ $kkm }})">
                                                        {{ \App\Services\HitungNilaiService::formatAngka($valRap) }}
                                                    </span>
                                                @else
                                                    <span id="rapor_val_{{ $ks->id }}" class="font-mono font-black text-sm px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                        {{ \App\Services\HitungNilaiService::formatAngka($valRap) }}
                                                    </span>
                                                @endif
                                            @else
                                                <span id="rapor_val_{{ $ks->id }}" class="font-mono font-black text-sm text-slate-300">–</span>
                                            @endif
                                        </td>

                                        <!-- Predikat (Otomatis) -->
                                        <td class="py-3 px-3 text-center border-r border-slate-200 bg-emerald-50/30">
                                            @php
                                                $badgeColors = [
                                                    'A' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                    'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                    'C' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                    'D' => 'bg-rose-100 text-rose-800 border-rose-300',
                                                ];
                                                $cls = $pred ? ($badgeColors[$pred] ?? 'bg-slate-100 text-slate-700') : '';
                                            @endphp
                                            <span id="predikat_badge_{{ $ks->id }}" class="{{ $pred ? 'inline-block px-2.5 py-0.5 rounded-md font-mono font-black text-xs border ' . $cls : 'text-slate-300 font-bold' }}">
                                                {{ $pred ?? '–' }}
                                            </span>
                                        </td>

                                        <!-- Catatan Guru (Ikon Per Baris & Modal) -->
                                        <td class="py-3 px-4 text-center">
                                            <input type="hidden" name="catatan[{{ $ks->id }}]" id="hidden_catatan_{{ $ks->id }}" value="{{ old('catatan.' . $ks->id, $catatan) }}">
                                            <input type="hidden" id="saran_default_{{ $ks->id }}" value="{{ $saranDef }}">
                                            <button type="button"
                                                    onclick="openCatatanModal({{ $ks->id }}, '{{ addslashes($ks->siswa->nama) }}', '{{ addslashes($mapel->nama) }}')"
                                                    id="btn_catatan_{{ $ks->id }}"
                                                    class="px-2.5 py-1.5 rounded-xl text-xs font-semibold border transition flex items-center gap-1.5 mx-auto {{ $hasCatatan ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100' }}">
                                                <i id="icon_catatan_{{ $ks->id }}" class="{{ $hasCatatan ? 'fa-solid fa-comment-check text-indigo-600' : 'fa-regular fa-comment-dots text-slate-400' }}"></i>
                                                <span id="text_catatan_{{ $ks->id }}">{{ $hasCatatan ? 'Lihat/Edit' : '+ Catatan' }}</span>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-12 text-center text-slate-400">
                                            Belum ada data siswa terdaftar pada kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- TAB 2: PEMBIASAAN & KEHADIRAN (SIA) PADA HALAMAN YANG SAMA                                -->
    <!-- ========================================================================================= -->
    @if($tab === 'pembiasaan')
        <div class="space-y-4">
            <!-- Alert Petunjuk Pembiasaan -->
            <div class="bg-amber-50/90 border border-amber-200 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-950">
                <div class="flex items-start space-x-3">
                    <i class="fa-solid fa-star text-amber-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold text-sm text-amber-950">Penilaian Pembiasaan Karakter & Absensi Siswa (SIA)</p>
                        <p class="text-amber-800 mt-0.5">
                            Isikan kehadiran (Sakit, Izin, Alpa) dan predikat butir pembiasaan: <strong>SB</strong> (Sangat Baik), <strong>B</strong> (Baik), <strong>C</strong> (Cukup), <strong>K</strong> (Kurang).
                            Gunakan tombol <em>"Isi semua Baik"</em> untuk pengisian kilat sekelas.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                    <button type="button" onclick="isiSemuaPembiasaanSekelasBaik()"
                            class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-wand-sparkles"></i>
                        <span>Isi Semua Pembiasaan 'Baik'</span>
                    </button>
                    <!-- Tarik dari Absensi Bulanan -->
                    <form action="{{ route('kelas.sia-pembiasaan.tarik-absensi', $kelas->id) }}" method="POST" onsubmit="return confirm('Tarik data absensi bulanan semester ini ke kolom SIA kehadiran?')">
                        @csrf
                        <input type="hidden" name="periode" value="{{ $periodeSia }}">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 font-bold text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer">
                            <i class="fa-solid fa-cloud-arrow-down text-blue-600"></i>
                            <span>Tarik Absensi Bulanan</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- FORM PEMBIASAAN & SIA -->
            <form id="formSiaPembiasaan" action="{{ route('kelas.sia-pembiasaan.simpan', $kelas->id) }}" method="POST">
                @csrf
                <input type="hidden" name="periode" value="{{ $periodeSia }}">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto max-h-[70vh]">
                        <table class="w-full text-left text-xs border-collapse">
                            <!-- STICKY TABLE HEADER -->
                            <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 shadow-xs text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-3 w-12 text-center border-r border-slate-200">No</th>
                                    <th class="py-3.5 px-4 min-w-[200px] border-r border-slate-200">Nama Peserta Didik</th>
                                    <th class="py-2 px-2 w-18 text-center border-r border-slate-200 bg-amber-50/50">
                                        <div>Sakit (S)</div>
                                        <div class="text-[9px] text-slate-400 lowercase font-medium">hari</div>
                                    </th>
                                    <th class="py-2 px-2 w-18 text-center border-r border-slate-200 bg-blue-50/50">
                                        <div>Izin (I)</div>
                                        <div class="text-[9px] text-slate-400 lowercase font-medium">hari</div>
                                    </th>
                                    <th class="py-2 px-2 w-18 text-center border-r border-slate-200 bg-rose-50/50">
                                        <div>Alpa (A)</div>
                                        <div class="text-[9px] text-slate-400 lowercase font-medium">hari</div>
                                    </th>
                                    <!-- Kolom Butir Pembiasaan -->
                                    @foreach($pembiasaans as $p)
                                        <th class="py-2 px-3 min-w-[150px] text-center border-r border-slate-200 bg-slate-50/80">
                                            <div class="font-bold text-slate-800 text-[11px] leading-tight">{{ $p->nama }}</div>
                                            <button type="button" onclick="isiSemuaBaik({{ $p->id }})"
                                                    class="mt-1 px-2 py-0.5 rounded-md bg-blue-100 hover:bg-blue-200 text-blue-800 text-[10px] font-bold block mx-auto transition cursor-pointer">
                                                Isi semua Baik
                                            </button>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                @forelse($kelasSiswaList as $idx => $ks)
                                    @php
                                        $kh = $kehadiranList->get($ks->id);
                                        $sakit = $kh ? $kh->sakit : 0;
                                        $izin = $kh ? $kh->izin : 0;
                                        $alpa = $kh ? $kh->alpa : 0;
                                        $pemSiswa = $nilaiPemGroup->get($ks->id, collect())->keyBy('pembiasaan_id');
                                    @endphp
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                            {{ $idx + 1 }}
                                        </td>
                                        <td class="py-3 px-4 border-r border-slate-200">
                                            <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                NIS: {{ $ks->siswa->nis ?? '-' }}
                                            </div>
                                        </td>
                                        <!-- Sakit -->
                                        <td class="py-2.5 px-2 text-center border-r border-slate-200 bg-amber-50/20">
                                            <input type="number" min="0" name="sakit[{{ $ks->id }}]" value="{{ old('sakit.' . $ks->id, $sakit) }}"
                                                   oninput="setDirty(true)"
                                                   class="w-14 text-center font-mono font-bold text-sm px-1.5 py-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-400 focus:outline-none transition">
                                        </td>
                                        <!-- Izin -->
                                        <td class="py-2.5 px-2 text-center border-r border-slate-200 bg-blue-50/20">
                                            <input type="number" min="0" name="izin[{{ $ks->id }}]" value="{{ old('izin.' . $ks->id, $izin) }}"
                                                   oninput="setDirty(true)"
                                                   class="w-14 text-center font-mono font-bold text-sm px-1.5 py-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none transition">
                                        </td>
                                        <!-- Alpa -->
                                        <td class="py-2.5 px-2 text-center border-r border-slate-200 bg-rose-50/20">
                                            <input type="number" min="0" name="alpa[{{ $ks->id }}]" value="{{ old('alpa.' . $ks->id, $alpa) }}"
                                                   oninput="setDirty(true)"
                                                   class="w-14 text-center font-mono font-bold text-sm px-1.5 py-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-rose-400 focus:outline-none transition">
                                        </td>
                                        <!-- Pilihan Predikat Butir Pembiasaan -->
                                        @foreach($pembiasaans as $p)
                                            @php
                                                $recP = $pemSiswa->get($p->id);
                                                $curPred = $recP ? $recP->predikat : 'Baik';
                                            @endphp
                                            <td class="py-2 px-2 text-center border-r border-slate-200">
                                                <select name="predikat[{{ $ks->id }}][{{ $p->id }}]"
                                                        onchange="setDirty(true)"
                                                        class="select-pembiasaan-{{ $p->id }} font-bold text-xs py-1 px-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none transition">
                                                    <option value="Sangat Baik" {{ $curPred === 'Sangat Baik' ? 'selected' : '' }}>SB (Sangat Baik)</option>
                                                    <option value="Baik" {{ ($curPred === 'Baik' || empty($curPred)) ? 'selected' : '' }}>B (Baik)</option>
                                                    <option value="Cukup" {{ $curPred === 'Cukup' ? 'selected' : '' }}>C (Cukup)</option>
                                                    <option value="Perlu Bimbingan" {{ $curPred === 'Perlu Bimbingan' ? 'selected' : '' }}>K (Kurang)</option>
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 5 + $pembiasaans->count() }}" class="py-12 text-center text-slate-400">
                                            Belum ada data siswa.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- TAB 3: DETAIL FORMATIF BULANAN (FITUR BULANAN TETAP LENGKAP & DAPAT DIAKSES)                -->
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
                    <div class="overflow-x-auto max-h-[70vh]">
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
                            Rata-rata otomatis terhubung ke perhitungan <strong>Nilai Rapor Terpadu</strong>.
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

</div>

<!-- ========================================================================================= -->
<!-- 3. STICKY SAVE BAR (FLOATING DI BAGIAN BAWAH DENGAN INDIKATOR PERUBAHAN)                    -->
<!-- ========================================================================================= -->
<div id="stickySaveBar"
     class="fixed bottom-5 left-1/2 -translate-x-1/2 z-30 transition-all duration-300 transform translate-y-24 opacity-0 pointer-events-none">
    <div class="bg-slate-900/95 backdrop-blur-md text-white px-5 py-3 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center gap-4">
        <div id="dirtyIndicator" class="flex items-center text-xs font-semibold text-slate-200">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse mr-2"></span>
            <span>Ada perubahan belum disimpan</span>
        </div>
        <div class="h-4 w-px bg-slate-700"></div>
        <button type="button" onclick="submitActiveForm()"
                class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs shadow-md transition flex items-center gap-2 cursor-pointer transform hover:scale-[1.02] active:scale-[0.98]">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Simpan Nilai</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-black/30 text-white/80 font-mono font-bold">Ctrl+S</span>
        </button>
    </div>
</div>

<!-- ========================================================================================= -->
<!-- 4. MODAL CATATAN GURU PER SISWA                                                           -->
<!-- ========================================================================================= -->
<div id="modalCatatanGuru" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden transform transition-all">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <div>
                <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                    <i class="fa-solid fa-comment-dots text-indigo-600"></i>
                    <span>Catatan Capaian Pembelajaran</span>
                </h3>
                <p class="text-xs text-slate-500 mt-0.5" id="modalStudentInfo">Siswa: -</p>
            </div>
            <button type="button" onclick="closeCatatanModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-200/60 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <input type="hidden" id="modalKsId" value="">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="text-xs font-bold text-slate-700">Catatan Guru untuk Rapor:</label>
                    <button type="button" onclick="terapkanSaranModal()"
                            class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1">
                        <i class="fa-solid fa-wand-sparkles text-amber-500"></i>
                        <span>Gunakan Saran Otomatis</span>
                    </button>
                </div>
                <textarea id="modalCatatanTextarea"
                          rows="4"
                          placeholder="Tuliskan catatan deskripsi capaian pembelajaran peserta didik..."
                          class="w-full text-xs font-normal px-3 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition leading-relaxed"></textarea>
            </div>

            <!-- Saran Capaian Default Preview -->
            <div class="p-3 bg-indigo-50/60 rounded-xl border border-indigo-100 text-[11px] text-indigo-900">
                <span class="font-bold flex items-center gap-1 mb-1">
                    <i class="fa-solid fa-circle-info text-indigo-500"></i> Saran Deskripsi Standar:
                </span>
                <p id="modalSaranDefaultPreview" class="text-slate-600 italic leading-relaxed">-</p>
            </div>
        </div>

        <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
            <button type="button" onclick="closeCatatanModal()"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-200 hover:bg-slate-300 text-slate-700 transition">
                Batal
            </button>
            <div class="flex items-center gap-2">
                <button type="button" onclick="simpanCatatanKeForm()"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>Terapkan ke Form</span>
                </button>
                <button type="button" onclick="simpanCatatanAjaxDirect()"
                        id="btnSimpanCatatanAjax"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Simpan Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================================= -->
<!-- 5. JAVASCRIPT: LOGIKA REAL-TIME, KEYBOARD NAVIGATION, & MODAL                             -->
<!-- ========================================================================================= -->
<script>
    let isDirty = false;
    window.isSubmitting = false;

    // Aktifkan indikator perubahan
    function setDirty(dirty = true) {
        isDirty = dirty;
        const saveBar = document.getElementById('stickySaveBar');
        if (saveBar) {
            if (dirty) {
                saveBar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
                saveBar.classList.add('translate-y-0', 'opacity-100');
            }
        }
    }

    // Submit form yang sedang aktif
    function submitActiveForm() {
        window.isSubmitting = true;
        const formTerpadu = document.getElementById('formNilaiTerpadu');
        const formSia = document.getElementById('formSiaPembiasaan');
        if (formTerpadu) {
            formTerpadu.submit();
        } else if (formSia) {
            formSia.submit();
        }
    }

    // Peringatan jika meninggalkan halaman sebelum menyimpan
    window.addEventListener('beforeunload', function(e) {
        if (isDirty && !window.isSubmitting) {
            e.preventDefault();
            e.returnValue = 'Ada perubahan nilai yang belum disimpan!';
        }
    });

    // Perhitungan Nilai Rapor di sisi Client
    function hitungNilaiRaporClient(formatif, sts, sas, bobotF = 40, bobotUts = 30, bobotUas = 30) {
        let komponen = [];
        if (formatif !== null && !isNaN(formatif) && formatif !== '') {
            komponen.push({ nilai: parseFloat(formatif), bobot: bobotF });
        }
        if (sts !== null && !isNaN(sts) && sts !== '') {
            komponen.push({ nilai: parseFloat(sts), bobot: bobotUts });
        }
        if (sas !== null && !isNaN(sas) && sas !== '') {
            komponen.push({ nilai: parseFloat(sas), bobot: bobotUas });
        }
        if (komponen.length === 0) return null;

        let totalBobot = komponen.reduce((sum, item) => sum + item.bobot, 0);
        if (totalBobot <= 0) return null;

        let subtotal = komponen.reduce((sum, item) => sum + (item.nilai * item.bobot), 0);
        return Math.round((subtotal / totalBobot) * 10) / 10;
    }

    function hitungPredikatClient(nilai) {
        if (nilai === null || isNaN(nilai)) return null;
        if (nilai >= 90) return 'A';
        if (nilai >= 80) return 'B';
        if (nilai >= 70) return 'C';
        return 'D';
    }

    // Update real-time 1 baris siswa saat STS atau SAS diketik
    function recalculateRow(ksId, kkm, bobotF, bobotUts, bobotUas) {
        setDirty(true);

        const formatifEl = document.getElementById('formatif_raw_' + ksId);
        const stsEl = document.getElementById('input_sts_' + ksId);
        const sasEl = document.getElementById('input_sas_' + ksId);
        const raporValEl = document.getElementById('rapor_val_' + ksId);
        const predikatBadgeEl = document.getElementById('predikat_badge_' + ksId);

        const formatif = formatifEl && formatifEl.value !== '' ? parseFloat(formatifEl.value) : null;
        const rawSts = stsEl ? stsEl.value.trim().replace(',', '.') : '';
        const rawSas = sasEl ? sasEl.value.trim().replace(',', '.') : '';

        const sts = (rawSts !== '' && !isNaN(rawSts)) ? parseFloat(rawSts) : null;
        const sas = (rawSas !== '' && !isNaN(rawSas)) ? parseFloat(rawSas) : null;

        // Beri warna merah pada input jika nilai di bawah KKM
        if (stsEl) {
            if (sts !== null && sts < kkm) {
                stsEl.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-400');
                stsEl.classList.remove('border-slate-300', 'bg-white');
            } else {
                stsEl.classList.remove('bg-rose-50', 'text-rose-700', 'border-rose-400');
                stsEl.classList.add('border-slate-300', 'bg-white');
            }
        }

        if (sasEl) {
            if (sas !== null && sas < kkm) {
                sasEl.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-400');
                sasEl.classList.remove('border-slate-300', 'bg-white');
            } else {
                sasEl.classList.remove('bg-rose-50', 'text-rose-700', 'border-rose-400');
                sasEl.classList.add('border-slate-300', 'bg-white');
            }
        }

        const nilaiRapor = hitungNilaiRaporClient(formatif, sts, sas, bobotF, bobotUts, bobotUas);
        const predikat = hitungPredikatClient(nilaiRapor);

        if (raporValEl) {
            if (nilaiRapor !== null) {
                raporValEl.textContent = nilaiRapor.toFixed(1).replace('.0', '');
                if (nilaiRapor < kkm) {
                    raporValEl.className = 'font-mono font-black text-sm px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200';
                } else {
                    raporValEl.className = 'font-mono font-black text-sm px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200';
                }
            } else {
                raporValEl.textContent = '–';
                raporValEl.className = 'font-mono font-black text-sm text-slate-300';
            }
        }

        if (predikatBadgeEl) {
            if (predikat) {
                predikatBadgeEl.textContent = predikat;
                const badgeCls = {
                    'A': 'bg-emerald-100 text-emerald-800 border-emerald-300',
                    'B': 'bg-blue-100 text-blue-800 border-blue-300',
                    'C': 'bg-amber-100 text-amber-800 border-amber-300',
                    'D': 'bg-rose-100 text-rose-800 border-rose-300'
                };
                predikatBadgeEl.className = 'inline-block px-2.5 py-0.5 rounded-md font-mono font-black text-xs border ' + (badgeCls[predikat] || 'bg-slate-100 text-slate-700');
            } else {
                predikatBadgeEl.textContent = '–';
                predikatBadgeEl.className = 'text-slate-300 font-bold';
            }
        }
    }

    // Keyboard Navigation: Enter atau ↓ pindah ke baris berikutnya, ↑ ke baris sebelumnya
    document.addEventListener('keydown', function(e) {
        if (e.target && (e.target.classList.contains('input-nilai-sts') || e.target.classList.contains('input-nilai-sas'))) {
            const isSts = e.target.classList.contains('input-nilai-sts');
            const currentRow = parseInt(e.target.dataset.row);
            const totalRows = document.querySelectorAll('.input-nilai-sts').length;

            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault();
                const nextRow = currentRow + 1;
                if (nextRow < totalRows) {
                    const selector = isSts ? `.input-nilai-sts[data-row="${nextRow}"]` : `.input-nilai-sas[data-row="${nextRow}"]`;
                    const targetEl = document.querySelector(selector);
                    if (targetEl) {
                        targetEl.focus();
                        targetEl.select();
                    }
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevRow = currentRow - 1;
                if (prevRow >= 0) {
                    const selector = isSts ? `.input-nilai-sts[data-row="${prevRow}"]` : `.input-nilai-sas[data-row="${prevRow}"]`;
                    const targetEl = document.querySelector(selector);
                    if (targetEl) {
                        targetEl.focus();
                        targetEl.select();
                    }
                }
            }
        }

        // Shortcut Ctrl+S / Cmd+S untuk menyimpan perubahan
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            submitActiveForm();
        }
    });

    // Pilihan Cepat Pembiasaan: "Isi semua Baik" per kolom
    function isiSemuaBaik(pembiasaanId) {
        const selects = document.querySelectorAll('.select-pembiasaan-' + pembiasaanId);
        selects.forEach(sel => {
            sel.value = 'Baik';
        });
        setDirty(true);
    }

    // Pilihan Cepat Pembiasaan: Isi SEMUA Butir Sekelas dengan "Baik"
    function isiSemuaPembiasaanSekelasBaik() {
        document.querySelectorAll('select[class*="select-pembiasaan-"]').forEach(sel => {
            sel.value = 'Baik';
        });
        setDirty(true);
    }

    // Modal Catatan Guru
    function openCatatanModal(ksId, studentName, mapelName) {
        const modal = document.getElementById('modalCatatanGuru');
        const modalKsId = document.getElementById('modalKsId');
        const modalStudentInfo = document.getElementById('modalStudentInfo');
        const textarea = document.getElementById('modalCatatanTextarea');
        const previewSaran = document.getElementById('modalSaranDefaultPreview');

        const hiddenInput = document.getElementById('hidden_catatan_' + ksId);
        const saranDefaultInput = document.getElementById('saran_default_' + ksId);

        if (modalKsId) modalKsId.value = ksId;
        if (modalStudentInfo) modalStudentInfo.textContent = `Peserta Didik: ${studentName} • Mata Pelajaran: ${mapelName}`;
        if (textarea) textarea.value = hiddenInput ? hiddenInput.value : '';
        if (previewSaran) previewSaran.textContent = (saranDefaultInput && saranDefaultInput.value) ? saranDefaultInput.value : 'Belum ada saran otomatis yang dikonfigurasi.';

        if (modal) modal.classList.remove('hidden');
    }

    function closeCatatanModal() {
        const modal = document.getElementById('modalCatatanGuru');
        if (modal) modal.classList.add('hidden');
    }

    function terapkanSaranModal() {
        const ksId = document.getElementById('modalKsId').value;
        const saranDefaultInput = document.getElementById('saran_default_' + ksId);
        const textarea = document.getElementById('modalCatatanTextarea');
        if (saranDefaultInput && textarea) {
            textarea.value = saranDefaultInput.value;
        }
    }

    function simpanCatatanKeForm() {
        const ksId = document.getElementById('modalKsId').value;
        const textarea = document.getElementById('modalCatatanTextarea');
        const hiddenInput = document.getElementById('hidden_catatan_' + ksId);
        const btnCatatan = document.getElementById('btn_catatan_' + ksId);
        const textCatatan = document.getElementById('text_catatan_' + ksId);
        const iconCatatan = document.getElementById('icon_catatan_' + ksId);

        if (hiddenInput && textarea) {
            hiddenInput.value = textarea.value.trim();
            setDirty(true);

            // Update tampilan tombol catatan di baris siswa
            const hasText = textarea.value.trim().length > 0;
            if (textCatatan) textCatatan.textContent = hasText ? 'Lihat/Edit' : '+ Catatan';
            if (iconCatatan) {
                iconCatatan.className = hasText ? 'fa-solid fa-comment-check text-indigo-600' : 'fa-regular fa-comment-dots text-slate-400';
            }
            if (btnCatatan) {
                if (hasText) {
                    btnCatatan.classList.add('bg-indigo-50', 'border-indigo-200', 'text-indigo-700');
                    btnCatatan.classList.remove('bg-slate-50', 'border-slate-200', 'text-slate-500');
                } else {
                    btnCatatan.classList.remove('bg-indigo-50', 'border-indigo-200', 'text-indigo-700');
                    btnCatatan.classList.add('bg-slate-50', 'border-slate-200', 'text-slate-500');
                }
            }
        }
        closeCatatanModal();
    }

    function simpanCatatanAjaxDirect() {
        const ksId = document.getElementById('modalKsId').value;
        const textarea = document.getElementById('modalCatatanTextarea');
        const catatanText = textarea ? textarea.value.trim() : '';
        const btn = document.getElementById('btnSimpanCatatanAjax');
        const mapelId = "{{ $mapel->id ?? 0 }}";
        const kelasId = "{{ $kelas->id }}";
        const csrfToken = "{{ csrf_token() }}";

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
        }

        fetch(`{{ url('kelas') }}/${kelasId}/mapel/${mapelId}/nilai/simpan-catatan-ajax`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                kelas_siswa_id: ksId,
                catatan: catatanText,
                tahun: "{{ $tahun }}",
                semester: "{{ $semester }}"
            })
        })
        .then(response => response.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> <span>Simpan Sekarang</span>';
            }
            if (data.success) {
                simpanCatatanKeForm();
            } else {
                alert(data.message || 'Gagal menyimpan catatan');
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> <span>Simpan Sekarang</span>';
            }
            alert('Terjadi kesalahan jaringan saat menyimpan catatan.');
        });
    }
</script>
@endsection
