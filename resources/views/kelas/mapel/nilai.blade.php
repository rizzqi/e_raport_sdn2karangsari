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
                        @php
                            $badgeMap = [
                                'pembiasaan' => ['bg-amber-100 text-amber-800 border-amber-200', 'Tab Pembiasaan & Kehadiran (SIA)'],
                                'formatif' => ['bg-blue-100 text-blue-800 border-blue-200', 'Form Input Formatif Bulanan'],
                                'sts' => ['bg-amber-100 text-amber-800 border-amber-200', 'Form Input Nilai STS'],
                                'sas' => ['bg-indigo-100 text-indigo-800 border-indigo-200', 'Form Input Nilai SAS'],
                                'rapor' => ['bg-purple-100 text-purple-800 border-purple-200', 'Form Catatan Capaian Guru'],
                                'mapel' => ['bg-emerald-100 text-emerald-800 border-emerald-200', 'Lembar Nilai Rapor (Read-Only)'],
                            ];
                            [$badgeClass, $badgeLabel] = $badgeMap[$tab] ?? $badgeMap['mapel'];
                        @endphp
                        <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                            {{ $badgeLabel }}
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
                        <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-1.5">
                            <span>Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong></span>
                            @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition shadow-2xs"
                                        title="Ubah Nama atau NIP Wali Kelas">
                                    <i class="fa-solid fa-user-pen text-amber-600 text-[10px]"></i>
                                    <span>Edit Wali Kelas</span>
                                </button>
                            @endif
                            <span>&bull; Total: <strong class="text-slate-800">{{ $totalSiswa }} Siswa</strong></span>
                            <span>&bull; Catatan kehadiran dan karakter terhubung ke rapor siswa</span>
                        </p>
                    @else
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-1 flex flex-wrap items-center gap-2.5">
                            <span>{{ $mapel->nama }}</span>
                            <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-lg bg-purple-100 text-purple-800 border border-purple-200 font-mono">
                                KKM: {{ $kkm }}
                            </span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-1.5">
                            <span>Guru Pengampu: <strong class="text-slate-800">{{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? 'Belum ditentukan') }}</strong></span>
                            <span>&bull; Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong></span>
                            @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition shadow-2xs"
                                        title="Ubah Nama atau NIP Wali Kelas">
                                    <i class="fa-solid fa-user-pen text-amber-600 text-[10px]"></i>
                                    <span>Edit Wali Kelas</span>
                                </button>
                            @endif
                            <span>&bull; Rumus: <strong class="text-indigo-600 font-mono">Formatif ({{ $bobotFormatif }}%) + STS ({{ $bobotUts }}%) + SAS ({{ $bobotUas }}%)</strong></span>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Toolbar Kanan: Tombol Cetak & Menu "⋯ Lainnya" -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <!-- DROPDOWN CETAK UTAMA (4 GRUP BERSIH, KOLEKTIF SEMUA MAPEL, CAKUPAN TERPADU) -->
                @include('partials.dropdown_cetak_menu', [
                    'label' => 'Cetak',
                    'buttonClass' => 'px-3.5 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm flex items-center gap-1.5 cursor-pointer',
                ])

                <!-- DROPDOWN "⋯ LAINNYA" (PUSAT ALAT BANTU PENILAIAN LENGKAP) -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                        <i class="fa-solid fa-ellipsis"></i>
                        <span>Menu &amp; Alat Bantu</span>
                        <i class="fa-solid fa-chevron-down text-[9px] opacity-70 ml-0.5" :class="{ 'rotate-180': open }"></i>
                    </button>
                    <div x-show="open" x-transition
                         class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 z-50 divide-y divide-slate-100" style="display: none;">
                        
                        <!-- Grup 1: Impor & Template Excel/PDF -->
                        <div class="py-1">
                            <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                                Impor &amp; Template Excel
                            </div>
                            <button type="button" @click="open = false; document.getElementById('modalImportNilai').classList.remove('hidden')"
                                    class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50 transition cursor-pointer">
                                <i class="fa-solid fa-file-arrow-up text-emerald-600 w-4 text-center"></i>
                                <span>Impor Nilai (Excel / PDF)</span>
                            </button>
                            <a href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'mapel', 'mapel_id' => $mapel ? $mapel->id : null, 'periode' => ($semester === 'genap' ? 'sas' : 'sts')]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-emerald-700 transition">
                                <i class="fa-solid fa-file-excel text-emerald-600 w-4 text-center"></i>
                                <span>Unduh Template Mapel Ini (.xlsx)</span>
                            </a>
                            <a href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'semua', 'periode' => ($semester === 'genap' ? 'sas' : 'sts')]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-emerald-700 transition">
                                <i class="fa-solid fa-table-list text-slate-500 w-4 text-center"></i>
                                <span>Unduh Template Semua Mapel</span>
                            </a>
                            <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'xlsx', 'periode' => $periodeSia]) }}"
                               class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-amber-700 transition">
                                <i class="fa-solid fa-star text-amber-500 w-4 text-center"></i>
                                <span>Unduh Template Pembiasaan (SIA)</span>
                            </a>
                        </div>

                        <!-- Grup 2: Rincian Nilai Formatif -->
                        <div class="py-1">
                            <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                                Formatif Bulanan &amp; TP
                            </div>
                            @if($mapel)
                                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                                   class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-calendar-days text-blue-500 w-4 text-center"></i>
                                    <span>Input Nilai Bulanan (JUL-DES)</span>
                                </a>
                                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                                   class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-list-check text-blue-500 w-4 text-center"></i>
                                    <span>Penilaian Formatif Harian (TP)</span>
                                </a>
                            @endif
                        </div>

                        <!-- Grup 3: Pengaturan KKM & Reset Nilai -->
                        @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                            <div class="py-1">
                                <div class="px-3.5 py-1 text-[10px] font-black uppercase text-slate-400 bg-slate-50 tracking-wider">
                                    Pengaturan &amp; Reset
                                </div>
                                <button type="button" @click="open = false; document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                                        class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50 transition cursor-pointer">
                                    <i class="fa-solid fa-user-pen text-amber-600 w-4 text-center"></i>
                                    <span>Edit Wali Kelas &amp; NIP</span>
                                </button>
                                <button type="button" @click="open = false; document.getElementById('modalEditKkmKelas').classList.remove('hidden')"
                                        class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-purple-800 hover:bg-purple-50 transition cursor-pointer">
                                    <i class="fa-solid fa-sliders text-purple-600 w-4 text-center"></i>
                                    <span>Atur KKM Mandiri Rombel</span>
                                </button>
                                <button type="button" @click="open = false; document.getElementById('modalKosongkanNilai').classList.remove('hidden')"
                                        class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 transition cursor-pointer">
                                    <i class="fa-solid fa-trash-can text-rose-600 w-4 text-center"></i>
                                    <span>Kosongkan / Reset Nilai</span>
                                </button>
                            </div>
                        @endif

                        <!-- Grup 4: Kunci Nilai Rapor -->
                        <div class="py-1">
                            @if($isAnyLocked)
                                @if(auth()->user()->isAdmin())
                                    <form action="{{ route('kelas.mapel.nilai.buka-kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" onsubmit="return confirm('Buka kunci nilai rapor mata pelajaran ini?')">
                                        @csrf
                                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                                        <input type="hidden" name="semester" value="{{ $semester }}">
                                        <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50 transition">
                                            <i class="fa-solid fa-lock-open text-amber-500 w-4 text-center"></i>
                                            <span>Buka Kunci Nilai Rapor</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <form action="{{ route('kelas.mapel.nilai.kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" onsubmit="return confirm('Kunci nilai rapor mata pelajaran ini?')">
                                    @csrf
                                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                                    <input type="hidden" name="semester" value="{{ $semester }}">
                                    <button type="submit" class="w-full text-left flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                        <i class="fa-solid fa-lock text-slate-500 w-4 text-center"></i>
                                        <span>Kunci Nilai Rapor</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- MENU INPUT NILAI (GANTI TOMBOL MODE INPUT) & STATUS KUNCI -->
                @include('kelas.mapel.partials.input_nilai_dropdown', ['activeMode' => $tab])

                @if($isAnyLocked)
                    <span class="inline-flex items-center px-3 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-300">
                        <i class="fa-solid fa-lock mr-1.5 text-emerald-600"></i> Terkunci
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
    <!-- TAB 1: LEMBAR NILAI RAPOR (READ-ONLY — TIDAK ADA KOLOM YANG BISA DIKETIK)                 -->
    <!-- ========================================================================================= -->
    @if($tab === 'mapel')
        <div class="space-y-4">
            <!-- Alert Petunjuk Read-Only & Alur Satu Arah -->
            <div class="bg-blue-50/80 border border-blue-200/80 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-blue-900">
                <div class="flex items-start space-x-3">
                    <i class="fa-solid fa-shield-halved text-blue-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-bold text-sm text-blue-950">Lembar Nilai Rapor (Read-Only) — {{ $mapel->nama }}</p>
                        <p class="text-blue-800 mt-0.5">
                            Tampilan ini khusus rekapitulasi akhir (hanya baca). Alur satu arah:
                            <strong>Harian &rarr; Bulanan &rarr; Formatif ({{ $bobotFormatif }}%)</strong> + <strong>STS ({{ $bobotUts }}%)</strong> + <strong>SAS ({{ $bobotUas }}%)</strong>.
                            Nilai Rapor dan Predikat otomatis muncul setelah komponen lengkap; gunakan tombol <strong>Input Nilai &#9662;</strong> atau klik badge status pada baris siswa untuk mengisi nilai.
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('sekolah.edit') }}#bobot_formatif"
                           class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-indigo-50 border border-blue-200 hover:border-indigo-300 text-indigo-700 font-bold text-[11px] transition flex items-center gap-1 shadow-2xs"
                           title="Atur proporsi bobot Formatif, STS, dan SAS di Pengaturan Sekolah">
                            <i class="fa-solid fa-sliders text-[10px]"></i>
                            <span>Atur Bobot ({{ $bobotFormatif }}/{{ $bobotUts }}/{{ $bobotUas }})</span>
                        </a>
                    @endif
                    @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                        <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.remove('hidden')"
                                class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-purple-50 border border-blue-200 hover:border-purple-300 text-blue-900 hover:text-purple-700 font-mono font-bold text-[11px] transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                title="Klik untuk atur / ubah KKM mata pelajaran khusus rombel ini">
                            <span>KKM: {{ $kkm }}</span>
                            <span class="text-[10px] text-purple-600 font-sans font-bold flex items-center gap-0.5">
                                <i class="fa-solid fa-pen-to-square text-[9px]"></i> Ubah KKM
                            </span>
                        </button>
                    @else
                        <span class="px-2.5 py-1.5 rounded-lg bg-white border border-blue-200 text-blue-900 font-mono font-bold text-[11px]">
                            KKM: {{ $kkm }}
                        </span>
                    @endif
                </div>
            </div>

            <!-- BAR STATUS KELENGKAPAN NILAI & FILTER CEPAT -->
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $countBelumLengkap > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }} flex items-center justify-center text-base shrink-0 shadow-2xs font-bold" id="iconKelengkapanBox">
                        <i class="fa-solid {{ $countBelumLengkap > 0 ? 'fa-clipboard-question' : 'fa-circle-check' }}" id="iconKelengkapan"></i>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-extrabold text-sm text-slate-900">Kelengkapan Nilai Siswa</h3>
                            <span id="badgeSummaryKelengkapan" class="px-2 py-0.5 rounded-full text-[11px] font-black {{ $countBelumLengkap > 0 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300' }}">
                                <span id="labelBelumLengkap">{{ $countBelumLengkap > 0 ? $countBelumLengkap . ' Belum Lengkap' : 'Semua Lengkap' }}</span>
                            </span>
                            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 font-mono text-[10.5px] font-bold transition">
                                Formatif: <strong>{{ $countFormatifFilled ?? 0 }}</strong>/{{ $totalSiswa }}
                            </a>
                            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sts', 'tahun' => $tahun]) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 font-mono text-[10.5px] font-bold transition">
                                STS: <strong id="statStsFilled">{{ $countStsFilled ?? 0 }}</strong>/{{ $totalSiswa }}
                            </a>
                            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sas', 'tahun' => $tahun]) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-900 font-mono text-[10.5px] font-bold transition">
                                SAS: <strong id="statSasFilled">{{ $countSasFilled ?? 0 }}</strong>/{{ $totalSiswa }}
                            </a>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Total <strong>{{ $totalSiswa }} siswa</strong>: <span id="textStatLengkap" class="font-semibold text-emerald-700">{{ $countLengkap }} lengkap</span>, <span id="textStatBelumLengkap" class="font-semibold text-amber-700">{{ $countBelumLengkap }} belum lengkap</span> pada mata pelajaran {{ $mapel->nama }}.
                        </p>
                    </div>
                </div>

                <!-- Tombol Filter Cepat Kelengkapan -->
                <div class="flex flex-wrap items-center gap-2 self-start lg:self-auto shrink-0">
                    <div class="flex items-center gap-1 bg-slate-100/90 p-1 rounded-xl border border-slate-200/80 text-xs font-bold">
                        <button type="button" onclick="setFilterKelengkapan('semua')" id="btnFilterSemua"
                                class="filter-pill-btn px-2.5 py-1 rounded-lg transition-all shadow-xs bg-white text-slate-900 border border-slate-200">
                            <span>Semua</span>
                            <span class="ml-1 text-[10px] font-mono px-1 py-0.2 rounded-md bg-slate-100 text-slate-700" id="badgeFilterSemua">{{ $totalSiswa }}</span>
                        </button>

                        <button type="button" onclick="setFilterKelengkapan('belum')" id="btnFilterBelum"
                                class="filter-pill-btn px-2.5 py-1 rounded-lg transition-all text-slate-600 hover:text-amber-800 hover:bg-white/60">
                            <i class="fa-solid fa-circle-exclamation text-amber-500 mr-1"></i>
                            <span>Belum</span>
                            <span class="ml-1 text-[10px] font-mono px-1 py-0.2 rounded-md bg-amber-100 text-amber-900" id="badgeFilterBelum">{{ $countBelumLengkap }}</span>
                        </button>

                        <button type="button" onclick="setFilterKelengkapan('lengkap')" id="btnFilterLengkap"
                                class="filter-pill-btn px-2.5 py-1 rounded-lg transition-all text-slate-600 hover:text-emerald-800 hover:bg-white/60">
                            <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>
                            <span>Lengkap</span>
                            <span class="ml-1 text-[10px] font-mono px-1 py-0.2 rounded-md bg-emerald-100 text-emerald-900" id="badgeFilterLengkap">{{ $countLengkap }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABEL READ-ONLY LEMBAR NILAI RAPOR -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-h-[70vh]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="sticky top-0 z-10 bg-slate-100/95 backdrop-blur border-b border-slate-200 shadow-xs text-xs font-bold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center border-r border-slate-200">No</th>
                                <th class="py-3 px-4 min-w-[220px] border-r border-slate-200">Nama Peserta Didik</th>
                                <th class="py-3 px-3 w-32 text-center border-r border-slate-200 bg-slate-50/80">
                                    <div>Formatif ({{ $bobotFormatif }}%)</div>
                                    <div class="text-[9px] text-slate-400 font-medium lowercase">dari harian &amp; bulanan</div>
                                </th>
                                <th class="py-3 px-3 w-28 text-center border-r border-slate-200 bg-amber-50/60 text-amber-950 font-black">
                                    <div>STS ({{ $bobotUts }}%)</div>
                                    <div class="text-[9px] text-amber-700/80 font-medium lowercase">tengah semester</div>
                                </th>
                                <th class="py-3 px-3 w-28 text-center border-r border-slate-200 bg-blue-50/60 text-blue-950 font-black">
                                    <div>SAS ({{ $bobotUas }}%)</div>
                                    <div class="text-[9px] text-blue-700/80 font-medium lowercase">akhir semester</div>
                                </th>
                                <th class="py-3 px-3 w-32 text-center border-r border-slate-200 bg-emerald-50/80 text-emerald-950 font-black">
                                    <div>Nilai Rapor</div>
                                    <div class="text-[9px] text-emerald-700 font-medium lowercase">otomatis lengkap</div>
                                </th>
                                <th class="py-3 px-3 w-24 text-center border-r border-slate-200 bg-emerald-50/80 text-emerald-950 font-black">
                                    <div>Predikat</div>
                                    <div class="text-[9px] text-emerald-700 font-medium lowercase">otomatis lengkap</div>
                                </th>
                                <th class="py-3 px-4 min-w-[200px] text-left">
                                    <div>Catatan Guru</div>
                                    <div class="text-[9px] text-slate-400 font-medium lowercase">ringkasan capaian</div>
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
                                    $catatan = trim((string) ($d['catatan'] ?? ''));
                                    $hasCatatan = $catatan !== '';
                                    $isComplete = (bool) ($d['isLengkap'] ?? false);
                                    $butuhFmt = $bobotFormatif > 0 && $valFmt === null;
                                    $butuhSts = $bobotUts > 0 && $valUts === null;
                                    $butuhSas = $bobotUas > 0 && $valUas === null;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition student-grade-row" id="row_student_{{ $ks->id }}" data-ks-id="{{ $ks->id }}" data-complete="{{ $isComplete ? 'true' : 'false' }}">
                                    <td class="py-3 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                        {{ $idx + 1 }}
                                    </td>

                                    <!-- Nama Siswa & Badge Kelengkapan yang Bisa Diklik -->
                                    <td class="py-3 px-4 border-r border-slate-200">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                            <div id="badge_status_{{ $ks->id }}" class="shrink-0">
                                                @if($isComplete)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Semua komponen nilai sudah lengkap">
                                                        <i class="fa-solid fa-circle-check text-[9px]"></i> Lengkap
                                                    </span>
                                                @elseif($butuhFmt && $butuhSts && $butuhSas)
                                                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun, 'focus_ks' => $ks->id]) }}#row_formatif_{{ $ks->id }}"
                                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition"
                                                       title="Klik untuk mengisi nilai mulai dari Formatif Bulanan">
                                                        <i class="fa-solid fa-clock text-[9px]"></i> Belum Lengkap
                                                    </a>
                                                @elseif($butuhFmt)
                                                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun, 'focus_ks' => $ks->id]) }}#row_formatif_{{ $ks->id }}"
                                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-300 transition"
                                                       title="Klik langsung untuk mengisi Formatif Bulanan siswa ini">
                                                        <i class="fa-solid fa-pen text-[9px]"></i> Perlu Formatif
                                                    </a>
                                                @elseif($butuhSts && $butuhSas)
                                                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sts', 'tahun' => $tahun, 'focus_ks' => $ks->id]) }}#row_sts_{{ $ks->id }}"
                                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition"
                                                       title="Klik untuk mengisi STS terlebih dahulu">
                                                        <i class="fa-solid fa-clock text-[9px]"></i> Belum STS &amp; SAS
                                                    </a>
                                                @elseif($butuhSts)
                                                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sts', 'tahun' => $tahun, 'focus_ks' => $ks->id]) }}#row_sts_{{ $ks->id }}"
                                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition"
                                                       title="Klik langsung ke Form STS untuk mengisi nilai siswa ini">
                                                        <i class="fa-solid fa-pen text-[9px]"></i> Perlu STS
                                                    </a>
                                                @else
                                                    <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'sas', 'tahun' => $tahun, 'focus_ks' => $ks->id]) }}#row_sas_{{ $ks->id }}"
                                                       class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-300 transition"
                                                       title="Klik langsung ke Form SAS untuk mengisi nilai siswa ini">
                                                        <i class="fa-solid fa-pen text-[9px]"></i> Perlu SAS
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                            NIS: {{ $ks->siswa->nis ?? '-' }} &bull; NISN: {{ $ks->siswa->nisn ?? '-' }}
                                        </div>
                                    </td>

                                    <!-- Formatif (Read-Only) -->
                                    <td class="py-3 px-3 text-center border-r border-slate-200 bg-slate-50/50">
                                        @if($valFmt !== null)
                                            <span class="inline-block px-2.5 py-1 rounded-lg font-mono font-bold text-xs {{ $valFmt < $kkm ? 'text-rose-700 bg-rose-50 border border-rose-200' : 'text-slate-800 bg-slate-100 border border-slate-200' }}">
                                                {{ \App\Services\HitungNilaiService::formatAngka($valFmt) }}
                                            </span>
                                        @else
                                            <span class="font-mono text-slate-400 font-bold">–</span>
                                        @endif
                                    </td>

                                    <!-- STS (Read-Only) -->
                                    <td class="py-3 px-3 text-center border-r border-slate-200 bg-amber-50/20">
                                        @if($valUts !== null)
                                            <span class="inline-block px-2.5 py-1 rounded-lg font-mono font-bold text-xs {{ $valUts < $kkm ? 'text-rose-700 bg-rose-50 border border-rose-200' : 'text-amber-950 bg-amber-50 border border-amber-200' }}">
                                                {{ \App\Services\HitungNilaiService::formatAngka($valUts) }}
                                            </span>
                                        @else
                                            <span class="font-mono text-slate-400 font-bold">–</span>
                                        @endif
                                    </td>

                                    <!-- SAS (Read-Only) -->
                                    <td class="py-3 px-3 text-center border-r border-slate-200 bg-blue-50/20">
                                        @if($valUas !== null)
                                            <span class="inline-block px-2.5 py-1 rounded-lg font-mono font-bold text-xs {{ $valUas < $kkm ? 'text-rose-700 bg-rose-50 border border-rose-200' : 'text-blue-950 bg-blue-50 border border-blue-200' }}">
                                                {{ \App\Services\HitungNilaiService::formatAngka($valUas) }}
                                            </span>
                                        @else
                                            <span class="font-mono text-slate-400 font-bold">–</span>
                                        @endif
                                    </td>

                                    <!-- Nilai Rapor (Hanya Tampil Jika Komponen Lengkap, Jika Belum = –) -->
                                    <td class="py-3 px-3 text-center border-r border-slate-200 bg-emerald-50/30">
                                        @if($valRap !== null)
                                            <span id="rapor_val_{{ $ks->id }}" class="font-mono font-black text-sm px-2.5 py-1 rounded-lg {{ $valRap < $kkm ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">{{ \App\Services\HitungNilaiService::formatAngka($valRap) }}</span>
                                        @else
                                            <span id="rapor_val_{{ $ks->id }}" class="font-mono font-black text-sm text-slate-400" title="Menunggu komponen lengkap">–</span>
                                        @endif
                                    </td>

                                    <!-- Predikat (Hanya Tampil Jika Komponen Lengkap, Jika Belum = –) -->
                                    <td class="py-3 px-3 text-center border-r border-slate-200 bg-emerald-50/30">
                                        @php
                                            $badgeColors = [
                                                'A' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                'C' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                'D' => 'bg-rose-100 text-rose-800 border-rose-300',
                                            ];
                                            $cls = ($valRap !== null && $pred) ? ($badgeColors[$pred] ?? 'bg-slate-100 text-slate-700') : '';
                                        @endphp
                                        @if($valRap !== null && $pred)
                                            <span id="predikat_badge_{{ $ks->id }}" class="inline-block px-2.5 py-0.5 rounded-md font-mono font-black text-xs border {{ $cls }}">{{ $pred }}</span>
                                        @else
                                            <span id="predikat_badge_{{ $ks->id }}" class="text-slate-400 font-bold">–</span>
                                        @endif
                                    </td>

                                    <!-- Catatan Guru (Read-Only Preview) -->
                                    <td class="py-3 px-4 text-left">
                                        @if($hasCatatan)
                                            <p class="text-[11px] text-slate-700 line-clamp-2 leading-snug" title="{{ $catatan }}">
                                                {{ \Illuminate\Support\Str::limit($catatan, 85) }}
                                            </p>
                                        @else
                                            <span class="text-slate-400 text-xs font-bold">–</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        Belum ada data siswa terdaftar pada kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                            <tr id="rowEmptyFilter" style="display: none;">
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fa-solid fa-filter-circle-xmark text-3xl text-slate-300 mb-2"></i>
                                        <p class="font-bold text-slate-700 text-sm">Tidak ada siswa pada kategori filter ini</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Semua data siswa pada filter yang dipilih tidak ditemukan.</p>
                                        <button type="button" onclick="setFilterKelengkapan('semua')" class="mt-3 px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                                            Tampilkan Semua Siswa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================================= -->
    <!-- FORM STS & FORM SAS TERPISAH                                                              -->
    <!-- ========================================================================================= -->
    @if($tab === 'sts')
        @include('kelas.mapel.partials.form_sts_sas', ['mode' => 'sts'])
    @endif

    @if($tab === 'sas')
        @include('kelas.mapel.partials.form_sts_sas', ['mode' => 'sas'])
    @endif

    @if($tab === 'rapor')
        @include('kelas.mapel.partials.form_catatan_rapor')
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
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                        <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                           title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Rincian Nilai Harian</span>
                        </a>
                        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali ke Lembar Nilai Rapor</span>
                        </a>
                    </div>
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
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                        <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-cyan-700 hover:bg-cyan-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                           title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Rincian Nilai Harian</span>
                        </a>
                        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali ke Lembar Nilai Rapor</span>
                        </a>
                    </div>
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
                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto shrink-0">
                        <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                           title="Buka rincian penilaian harian per bulan untuk {{ $mapel->nama }}">
                            <i class="fa-solid fa-list-check"></i>
                            <span>Rincian Nilai Harian</span>
                        </a>
                        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'mapel', 'tahun' => $tahun]) }}"
                           class="px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-black text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>Kembali ke Lembar Nilai Rapor</span>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Formulir Nilai Formatif Bulanan -->
            <form id="formFormatifBulanan" action="{{ route('kelas.mapel.nilai.formatif', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
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
                                        $isFocusedFmt = ($focusKsId ?? null) !== null && $focusKsId === $ks->id;
                                    @endphp
                                    <tr id="row_formatif_{{ $ks->id }}"
                                        class="transition {{ $isFocusedFmt ? 'bg-amber-50/90 ring-2 ring-inset ring-amber-400' : 'hover:bg-slate-50/70' }}">
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
                                                           oninput="setDirty(true)"
                                                           {{ ($isFocusedFmt && $loop->first) ? 'autofocus' : '' }}
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
                                                           oninput="setDirty(true)"
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
                                                           oninput="setDirty(true)"
                                                           {{ ($isFocusedFmt && $loop->first) ? 'autofocus' : '' }}
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
                                                               oninput="setDirty(true)"
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
                                                               oninput="setDirty(true)"
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
                                                           oninput="setDirty(true)"
                                                           {{ ($isFocusedFmt && $loop->first) ? 'autofocus' : '' }}
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

<!-- Modal Edit Nama dan NIP Wali Kelas -->
@include('kelas.modal_edit_wali')

<!-- Modal Atur KKM Per Rombel -->
@include('kelas.modal_kkm', ['itemsMapel' => $allMapel])

<!-- Modal Import Nilai Excel / PDF -->
@include('nilai.modal_import_excel', ['activeMapel' => $mapel, 'periode' => ($semester === 'genap' ? 'sas' : 'sts')])

<!-- Modal Kosongkan Nilai Kelas -->
@include('nilai.modal_kosongkan_nilai', ['periode' => ($semester === 'genap' ? 'sas' : 'sts')])

<!-- Modal Dialog Cetak Universal (A4/F4, Cakupan Terpadu, Filter Mapel Opsional, Cek Kelengkapan) -->
@include('partials.modal_cetak_universal', [
    'kelas' => $kelas,
    'sekolah' => $sekolah ?? $kelas->sekolah,
    'mapelList' => $allMapel,
    'kelasSiswaList' => $kelasSiswaList,
    'tahun' => $tahun,
    'semester' => $semester,
])

<!-- ========================================================================================= -->
<!-- 4. JAVASCRIPT: NAVIGASI, FILTER KELENGKAPAN, & KEYBOARD SHORTCUTS                         -->
<!-- ========================================================================================= -->
<script>
    let isDirty = false;
    window.isSubmitting = false;

    function setDirty(dirty = true) {
        isDirty = dirty;
        const saveBar = document.getElementById('stickySaveBar');
        if (saveBar && dirty) {
            saveBar.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            saveBar.classList.add('translate-y-0', 'opacity-100');
        }
    }

    function submitActiveForm() {
        const activeForm =
            document.getElementById('formSingleComponent') ||
            document.getElementById('formFormatifBulanan') ||
            document.getElementById('formCatatanRapor') ||
            document.getElementById('formSiaPembiasaan');

        if (activeForm) {
            window.isSubmitting = true;
            activeForm.submit();
        }
    }

    ['formSingleComponent', 'formFormatifBulanan', 'formCatatanRapor', 'formSiaPembiasaan'].forEach(id => {
        const formEl = document.getElementById(id);
        if (formEl) {
            formEl.addEventListener('submit', () => {
                window.isSubmitting = true;
            });
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (isDirty && !window.isSubmitting) {
            e.preventDefault();
            e.returnValue = 'Ada perubahan nilai yang belum disimpan!';
        }
    });

    function updateSingleComponentStatus(inputEl) {
        setDirty(true);
        const kkm = parseFloat(inputEl.dataset.kkm || '70');
        const targetId = inputEl.dataset.statusTarget;
        const targetEl = targetId ? document.getElementById(targetId) : null;
        const raw = inputEl.value.trim().replace(',', '.');
        const val = (raw !== '' && !isNaN(raw)) ? parseFloat(raw) : null;

        if (val !== null && val < kkm) {
            inputEl.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-400');
            inputEl.classList.remove('border-slate-300', 'bg-white');
        } else {
            inputEl.classList.remove('bg-rose-50', 'text-rose-700', 'border-rose-400');
            inputEl.classList.add('border-slate-300', 'bg-white');
        }

        if (!targetEl) return;

        if (val === null) {
            targetEl.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Belum Diisi</span>';
        } else if (val >= kkm) {
            targetEl.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><i class="fa-solid fa-check text-[10px]"></i> Tuntas</span>';
        } else {
            targetEl.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200"><i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Belum Tuntas</span>';
        }
    }

    // Manajemen Filter Kelengkapan pada Lembar Nilai Rapor (Read-Only)
    let currentFilter = 'semua';

    function setFilterKelengkapan(mode) {
        currentFilter = mode;
        const btnSemua = document.getElementById('btnFilterSemua');
        const btnBelum = document.getElementById('btnFilterBelum');
        const btnLengkap = document.getElementById('btnFilterLengkap');

        [btnSemua, btnBelum, btnLengkap].forEach(b => {
            if (!b) return;
            b.className = 'filter-pill-btn px-3 py-1.5 rounded-lg transition-all text-slate-600 hover:text-slate-900 hover:bg-white/60';
        });

        if (mode === 'semua' && btnSemua) {
            btnSemua.className = 'filter-pill-btn px-3 py-1.5 rounded-lg transition-all shadow-xs bg-white text-slate-900 border border-slate-200 font-bold';
        } else if (mode === 'belum' && btnBelum) {
            btnBelum.className = 'filter-pill-btn px-3 py-1.5 rounded-lg transition-all shadow-xs bg-white text-amber-800 border border-amber-300 font-bold';
        } else if (mode === 'lengkap' && btnLengkap) {
            btnLengkap.className = 'filter-pill-btn px-3 py-1.5 rounded-lg transition-all shadow-xs bg-white text-emerald-800 border border-emerald-300 font-bold';
        }

        applyCurrentFilter();
    }

    function applyCurrentFilter() {
        const rows = document.querySelectorAll('.student-grade-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const isComplete = row.dataset.complete === 'true';
            let show = true;
            if (currentFilter === 'belum') {
                show = !isComplete;
            } else if (currentFilter === 'lengkap') {
                show = isComplete;
            }

            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });

        const emptyRow = document.getElementById('rowEmptyFilter');
        if (emptyRow) {
            emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
        }
    }

    // Keyboard Navigation: Enter / ArrowDown pindah ke baris berikutnya, ArrowUp ke sebelumnya, Ctrl+S simpan
    document.addEventListener('keydown', function(e) {
        if (e.target && (e.target.classList.contains('input-nilai-sts') || e.target.classList.contains('input-nilai-sas'))) {
            const cls = e.target.classList.contains('input-nilai-sts') ? 'input-nilai-sts' : 'input-nilai-sas';
            const currentRow = parseInt(e.target.dataset.row);
            const totalRows = document.querySelectorAll('.' + cls).length;

            if (e.key === 'Enter' || e.key === 'ArrowDown') {
                e.preventDefault();
                const nextRow = currentRow + 1;
                if (nextRow < totalRows) {
                    const targetEl = document.querySelector(`.${cls}[data-row="${nextRow}"]`);
                    if (targetEl) {
                        targetEl.focus();
                        targetEl.select();
                    }
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prevRow = currentRow - 1;
                if (prevRow >= 0) {
                    const targetEl = document.querySelector(`.${cls}[data-row="${prevRow}"]`);
                    if (targetEl) {
                        targetEl.focus();
                        targetEl.select();
                    }
                }
            }
        }

        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            const hasActiveForm =
                document.getElementById('formSingleComponent') ||
                document.getElementById('formFormatifBulanan') ||
                document.getElementById('formCatatanRapor') ||
                document.getElementById('formSiaPembiasaan');
            if (hasActiveForm) {
                e.preventDefault();
                submitActiveForm();
            }
        }
    });

    function isiSemuaBaik(pembiasaanId) {
        document.querySelectorAll('.select-pembiasaan-' + pembiasaanId).forEach(sel => {
            sel.value = 'Baik';
        });
        setDirty(true);
    }

    function isiSemuaPembiasaanSekelasBaik() {
        document.querySelectorAll('select[class*="select-pembiasaan-"]').forEach(sel => {
            sel.value = 'Baik';
        });
        setDirty(true);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const focusedInput = document.querySelector('[autofocus]');
        if (focusedInput) {
            focusedInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            focusedInput.select();
        }
    });
</script>
@endsection
