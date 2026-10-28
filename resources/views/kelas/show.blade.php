@extends('layouts.app')

@section('title', 'Detail Kelas ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header Kelas -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    {{ $kelas->fase ?? 'Fase A' }}
                </span>
                <span class="text-xs text-slate-400">&bull;</span>
                <span class="text-xs font-medium text-slate-600">Tahun Ajaran {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</span>
                @if(auth()->user()->isAdmin())
                    <button type="button" onclick="document.getElementById('modalEditKelas').classList.remove('hidden')"
                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition shadow-xs"
                        title="Edit Rombongan Kelas & Koreksi Fase">
                        <i class="fa-solid fa-pen-to-square mr-1 text-emerald-600"></i> Edit Kelas & Fase
                    </button>
                @endif
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $kelas->nama }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500">
                <span>
                    <i class="fa-solid fa-user-tie text-slate-400 mr-1"></i> Wali Kelas:
                    <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                    @if($kelas->nip_wali !== '-')
                        <span class="text-slate-400 ml-0.5 font-mono">(NIP: {{ $kelas->nip_wali }})</span>
                    @else
                        <span class="text-slate-400 italic ml-0.5">(NIP belum diisi)</span>
                    @endif
                </span>
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition shadow-sm"
                        title="Ubah Nama atau NIP Wali Kelas">
                        <i class="fa-solid fa-user-pen mr-1 text-amber-600"></i> Edit Wali & NIP
                    </button>
                @endif
            </div>

            <!-- Informasi Tanggal Cetak Raport STS & SAS -->
            <div class="flex flex-wrap items-center gap-2 mt-2 text-xs">
                <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                    <i class="fa-regular fa-calendar-days text-slate-500 mr-1.5"></i>
                    Tgl Rapor STS: <strong class="ml-1 text-slate-900">{{ $kelas->tanggal_raport_sts ? $kelas->tanggal_raport_sts->locale('id')->translatedFormat('d F Y') : 'Hari ini (Default)' }}</strong>
                </span>
                <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-xs font-semibold">
                    <i class="fa-regular fa-calendar-days text-slate-500 mr-1.5"></i>
                    Tgl Rapor SAS: <strong class="ml-1 text-slate-900">{{ $kelas->tanggal_raport_sas ? $kelas->tanggal_raport_sas->locale('id')->translatedFormat('d F Y') : 'Hari ini (Default)' }}</strong>
                </span>
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="document.getElementById('modalEditTanggalRaport').classList.remove('hidden')"
                        class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 transition shadow-sm"
                        title="Atur Tanggal Cetak Raport STS & SAS">
                        <i class="fa-regular fa-calendar-days mr-1 text-blue-600"></i> Atur Tanggal Rapor
                    </button>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- 1. Tombol Utama: Input Nilai Terpadu (Formatif, UTS, UAS, & Nilai Rapor Semua Mapel) -->
            <a href="{{ route('kelas.input-nilai', $kelas->id) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center gap-1.5">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Input Nilai</span>
            </a>

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

            <!-- 2. Dropdown Menu Raport & Dokumen Cetak -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition flex items-center shadow-2xs">
                    <i class="fa-solid fa-print mr-1.5 text-slate-500"></i> Cetak / Rapor
                    <i class="fa-solid fa-chevron-down ml-1.5 text-[10px] opacity-75"></i>
                </button>
                <div x-show="open" @click.outside="open = false"
                     class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 py-1.5 text-xs divide-y divide-slate-100" style="display: none;">
                    <div class="py-1">
                        <button type="button" onclick="open = false; document.getElementById('modalCetakRekapSemester').classList.remove('hidden')"
                           class="w-full text-left flex items-center px-4 py-2 text-blue-700 hover:bg-blue-50 font-bold cursor-pointer">
                            <i class="fa-solid fa-print w-5 text-blue-600 mr-2 text-center"></i> Cetak Rekap Semester (F4 / Leger)
                        </button>
                        <button type="button" onclick="open = false; document.getElementById('modalCetakBulanan').classList.remove('hidden')"
                           class="w-full text-left flex items-center px-4 py-2 text-purple-700 hover:bg-purple-50 font-bold cursor-pointer">
                            <i class="fa-solid fa-calendar-days w-5 text-purple-600 mr-2 text-center"></i> Cetak Bulanan (Pilih Bulan)
                        </button>
                        <a href="{{ route('kelas.nilai-formatif.rekap-semester-sekelas', $kelas->id) }}" target="_blank"
                           class="flex items-center px-4 py-2 text-blue-800 hover:bg-blue-50 font-medium">
                            <i class="fa-solid fa-file-lines w-5 text-blue-600 mr-2 text-center"></i> Rekap Semester Sekelas (F4)
                        </a>
                        <a href="{{ route('kelas.nilai-formatif.cetak-sekelas', $kelas->id) }}" target="_blank"
                           class="flex items-center px-4 py-2 text-indigo-700 hover:bg-indigo-50 font-medium">
                            <i class="fa-solid fa-file-invoice w-5 text-indigo-600 mr-2 text-center"></i> Cetak Rapor Bulanan Sekelas
                        </a>
                        <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                           class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-print w-5 text-slate-500 mr-2 text-center"></i> Cetak Rapor Sekelas (F4)
                        </a>
                        <a href="{{ route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                           class="flex items-center px-4 py-2 text-rose-700 hover:bg-rose-50 font-medium">
                            <i class="fa-solid fa-file-pdf w-5 text-rose-600 mr-2 text-center"></i> Unduh PDF Sekelas
                        </a>
                        <a href="{{ route('raport.download.kelas.docx', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                           class="flex items-center px-4 py-2 text-indigo-700 hover:bg-indigo-50 font-medium">
                            <i class="fa-solid fa-file-word w-5 text-indigo-600 mr-2 text-center"></i> Unduh Word (.docx)
                        </a>
                        <a href="{{ route('kelas.daftar-hadir', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                           class="flex items-center px-4 py-2 text-amber-700 hover:bg-amber-50 font-medium">
                            <i class="fa-solid fa-clipboard-user w-5 text-amber-600 mr-2 text-center"></i> Daftar Hadir (STS)
                        </a>
                        <a href="{{ route('rekap.kelas', $kelas->id) }}"
                           class="flex items-center px-4 py-2 text-blue-700 hover:bg-blue-50 font-medium">
                            <i class="fa-solid fa-chart-column w-5 text-blue-600 mr-2 text-center"></i> Rekapitulasi Nilai &amp; Grafik
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. Dropdown Menu Administrasi & Alat Kelas -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 transition flex items-center shadow-2xs">
                    <i class="fa-solid fa-sliders mr-1.5 text-slate-500"></i> Alat Kelas
                    <i class="fa-solid fa-chevron-down ml-1.5 text-[10px] opacity-75"></i>
                </button>
                <div x-show="open" @click.outside="open = false"
                     class="absolute right-0 mt-2 w-60 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 py-1.5 text-xs divide-y divide-slate-100" style="display: none;">
                    <div class="py-1">
                        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'formatif']) }}"
                           class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-calendar-check w-5 text-amber-600 mr-2 text-center"></i> Nilai Formatif Bulanan
                        </a>
                        <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor']) }}"
                           class="flex items-center px-4 py-2 text-indigo-700 hover:bg-indigo-50 font-medium">
                            <i class="fa-solid fa-table-list w-5 text-indigo-600 mr-2 text-center"></i> Rapor Semua Mapel
                        </a>
                        @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                            <a href="{{ route('nilai.input.kelas', $kelas->id) }}"
                               class="flex items-center px-4 py-2 text-emerald-700 hover:bg-emerald-50 font-medium">
                                <i class="fa-solid fa-pen-to-square w-5 text-emerald-600 mr-2 text-center"></i> Matriks Nilai Cepat
                            </a>
                        @endif
                        <a href="{{ route('kelas.absensi.index', $kelas->id) }}"
                           class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                            <i class="fa-regular fa-calendar-check w-5 text-slate-500 mr-2 text-center"></i> Absensi Bulanan
                        </a>
                        <a href="{{ route('kelas.sia-pembiasaan.index', $kelas->id) }}"
                           class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                            <i class="fa-solid fa-heart-pulse w-5 text-rose-500 mr-2 text-center"></i> SIA & Pembiasaan
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('penempatan.index', $kelas->id) }}"
                               class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                                <i class="fa-solid fa-user-plus w-5 text-indigo-500 mr-2 text-center"></i> Penempatan Siswa
                            </a>
                        @endif
                    </div>
                    @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                        <div class="py-1">
                            <button type="button" onclick="open = false; document.getElementById('modalImportNilai').classList.remove('hidden')"
                                    class="w-full text-left flex items-center px-4 py-2 text-emerald-800 hover:bg-emerald-50 font-medium">
                                <i class="fa-solid fa-file-arrow-up w-5 text-emerald-600 mr-2 text-center"></i> Impor Nilai Excel/PDF
                            </button>
                            <button type="button" onclick="open = false; document.getElementById('modalKosongkanNilai').classList.remove('hidden')"
                                    class="w-full text-left flex items-center px-4 py-2 text-rose-700 hover:bg-rose-50 font-medium">
                                <i class="fa-solid fa-trash-can w-5 text-rose-600 mr-2 text-center"></i> Kosongkan Nilai
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Baris Informasi Wali Kelas & 3 Guru Mapel Pengampu (Non-Wali) -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        <!-- Card Wali Kelas -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-start justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-base shadow-xs">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Wali Kelas</span>
                        <h4 class="text-sm font-bold text-slate-900 leading-tight">{{ $kelas->nama_wali }}</h4>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">NIP: {{ $kelas->nip_wali }}</p>
                    </div>
                </div>
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                        class="p-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition" title="Ubah Wali Kelas & NIP">
                        <i class="fa-solid fa-pen text-xs"></i>
                    </button>
                @endif
            </div>
            <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
                <span>Penandatangan Rapor</span>
                <span class="font-bold text-slate-700">Guru Kelas</span>
            </div>
        </div>

        <!-- 3 Guru Pengampu Mapel (Non-Wali) -->
        <div class="lg:col-span-3 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </span>
                    <div>
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">3 Guru Mata Pelajaran (Bukan Wali Kelas)</h3>
                        <p class="text-[11px] text-slate-400">Guru pengampu untuk absensi, pengawas ujian STS/SAS, dan administrasi</p>
                    </div>
                </div>
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="document.getElementById('modalEditGuruPengampu').classList.remove('hidden')"
                        class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition shadow-xs">
                        <i class="fa-solid fa-user-pen mr-1 text-emerald-600"></i> Atur 3 Guru Mapel
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @php $gurus = $kelas->guruPengampu; @endphp
                @for($i = 0; $i < 3; $i++)
                    @php $g = $gurus->get($i); @endphp
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-start space-x-2.5">
                        <span class="w-6 h-6 rounded-lg bg-emerald-600 text-white text-[11px] font-black flex items-center justify-center shrink-0 shadow-xs">
                            {{ $i + 1 }}
                        </span>
                        <div class="overflow-hidden min-w-0 flex-1">
                            <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider truncate max-w-full">
                                {{ $g && $g->mapel ? $g->mapel->nama : ($i === 0 ? 'Pendidikan Agama' : ($i === 1 ? 'PJOK' : 'Bahasa Inggris / Mulok')) }}
                            </span>
                            <p class="text-xs font-bold text-slate-900 truncate mt-0.5" title="{{ $g ? $g->nama : 'Belum diatur' }}">
                                {{ $g ? $g->nama : '(Belum Diatur)' }}
                            </p>
                            <p class="text-[10px] text-slate-500 font-mono truncate">
                                NIP: {{ $g && $g->nip ? $g->nip : '-' }}
                            </p>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- Baris Informasi KKM / KKTP Rombel -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div class="flex items-center space-x-2">
                <span class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center text-xs shadow-xs">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Kriteria Ketuntasan Minimal (KKM / KKTP) Rombel</h3>
                    <p class="text-[11px] text-slate-400">Standar ketuntasan nilai mata pelajaran per kelas (evaluasi formatif & sumatif)</p>
                </div>
            </div>
            @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.remove('hidden')"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 transition shadow-xs">
                    <i class="fa-solid fa-pen-to-square mr-1 text-purple-600"></i> Atur KKM Kelas Ini
                </button>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2.5">
            @foreach($mapelList as $m)
                @php
                    $kkmVal = $kelas->getKkmForMapel($m);
                    $hasCustom = $kelas->mapelKkm->firstWhere('mapel_id', $m->id) !== null;
                @endphp
                <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $m->id]) }}"
                   class="p-3 rounded-xl {{ $hasCustom ? 'bg-purple-50/70 border-purple-200' : 'bg-slate-50 border-slate-200/70' }} hover:border-indigo-400 hover:shadow-xs transition group border flex flex-col justify-between"
                   title="Buka 4 Lembar Nilai untuk {{ $m->nama }}">
                    <div>
                        <div class="truncate text-xs font-bold text-slate-800 group-hover:text-indigo-600 transition" title="{{ $m->nama }}">
                            {{ $m->nama }}
                        </div>
                        <div class="mt-1.5 flex items-baseline justify-between">
                            <span class="text-[10px] {{ $hasCustom ? 'text-purple-600 font-bold' : 'text-slate-400 font-medium' }}">
                                {{ $hasCustom ? 'Khusus Kelas' : 'Master' }}
                            </span>
                            <span class="text-sm font-black {{ $hasCustom ? 'text-purple-700' : 'text-slate-800' }}">
                                {{ $kkmVal }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-1.5 border-t border-slate-200/60 flex items-center justify-between text-[10px] font-bold text-indigo-600 group-hover:text-indigo-800">
                        <span>4 Lembar Nilai</span>
                        <i class="fa-solid fa-arrow-right text-[9px] group-hover:translate-x-0.5 transition-transform"></i>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Tabel Daftar Siswa di Kelas -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-users text-emerald-600 mr-2"></i> Daftar Peserta Didik ({{ $kelas->kelasSiswa->count() }} Siswa)
                </h2>
                <div class="text-xs text-slate-400 mt-0.5">
                    Pilih siswa untuk input/pratinjau individu, atau gunakan tombol di samping untuk cetak sekelas sekaligus
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white transition shadow-xs"
                    title="Cetak Raport Seluruh Siswa Sekelas">
                    <i class="fa-solid fa-print mr-1.5"></i> Cetak Raport Sekelas
                </a>
                <a href="{{ route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                    class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition"
                    title="Unduh PDF Sekelas">
                    <i class="fa-solid fa-file-pdf mr-1.5 text-rose-600"></i> PDF Sekelas
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">NISN</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi Raport</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($kelas->kelasSiswa as $index => $ks)
                        @php $s = $ks->siswa; @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-500 font-bold">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}" class="hover:text-emerald-600 flex items-center">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mr-2.5">
                                        {{ substr($s->nama, 0, 1) }}
                                    </div>
                                    {{ $s->nama }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 font-mono text-xs">{{ $s->nis }}</td>
                            <td class="py-3.5 px-4 text-slate-500 font-mono text-xs">{{ $s->nisn ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    Aktif
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('kelas.input-nilai', ['kelas' => $kelas->id, 'tab' => 'rapor', 'kelas_siswa_id' => $ks->id]) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition"
                                    title="Input Nilai & Catatan Rapor Siswa (Otomatis Semua Mapel)">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Nilai Rapor
                                </a>
                                <a href="{{ route('kelas.nilai-formatif.rekap-semester-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}"
                                    target="_blank"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition shadow-2xs"
                                    title="Cetak Rekap Nilai Semester 1 Siswa Ini (Pas 1 Lembar F4 Otomatis)">
                                    <i class="fa-solid fa-print mr-1 text-blue-600"></i> Rekap F4
                                </a>
                                <a href="{{ route('kelas.nilai-formatif.raport', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}"
                                    target="_blank"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 transition shadow-2xs"
                                    title="Cetak Lembar Nilai Formatif Bulanan Siswa Ini (F4)">
                                    <i class="fa-solid fa-calendar-day mr-1 text-purple-600"></i> Bulanan
                                </a>
                                <a href="{{ route('raport.preview', $ks->id) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                                    title="Pratinjau Raport">
                                    <i class="fa-solid fa-eye mr-1"></i> Pratinjau
                                </a>
                                <a href="{{ route('raport.download.docx', $ks->id) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition"
                                    title="Unduh Dokumen Word (.docx)">
                                    <i class="fa-solid fa-file-word mr-1"></i> Word
                                </a>
                                <a href="{{ route('raport.download.pdf', $ks->id) }}"
                                    class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition"
                                    title="Unduh PDF">
                                    <i class="fa-solid fa-file-pdf mr-1"></i> PDF
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-user-xmark text-3xl mb-2 block text-slate-300"></i>
                                Belum ada peserta didik di kelas ini.
                                @if(auth()->user()->isAdmin())
                                    <div class="mt-2">
                                        <a href="{{ route('penempatan.index', $kelas->id) }}" class="inline-flex items-center text-xs font-bold text-emerald-600 hover:underline">
                                            <i class="fa-solid fa-user-plus mr-1"></i> Tempatkan Siswa ke Kelas Ini Sekarang
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Edit Nama dan NIP Wali Kelas -->
    <div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-user-pen text-amber-600 mr-2 text-base"></i> Edit Wali Kelas & NIP
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
                </div>
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.update.wali', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap & Gelar Wali Kelas *
                    </label>
                    <input type="text" name="wali_nama" value="{{ old('wali_nama', $kelas->wali_nama ?: ($kelas->wali->name ?? '')) }}" required
                        placeholder="Contoh: Dewi Lestari, S.Pd."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Nama ini akan dicetak pada tanda tangan raport semester.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nomor Induk Pegawai (NIP)
                    </label>
                    <input type="text" name="wali_nip" value="{{ old('wali_nip', $kelas->wali_nip) }}"
                        placeholder="Contoh: 19880315 201101 2 008"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP.</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                        <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Atur Tanggal Raport (STS & SAS) -->
    <div id="modalEditTanggalRaport" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-regular fa-calendar-days text-blue-600 mr-2 text-base"></i> Atur Tanggal Cetak Raport
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; Tanggal ini dicetak pada tanda tangan raport</p>
                </div>
                <button type="button" onclick="document.getElementById('modalEditTanggalRaport').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.tanggal-raport.update', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tanggal Cetak Raport STS (Tengah Semester)
                    </label>
                    <input type="date" name="tanggal_raport_sts" value="{{ old('tanggal_raport_sts', $kelas->tanggal_raport_sts?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-blue-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan jika ingin otomatis mengikuti tanggal hari ini sewaktu dicetak.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Tanggal Cetak Raport SAS (Akhir Semester)
                    </label>
                    <input type="date" name="tanggal_raport_sas" value="{{ old('tanggal_raport_sas', $kelas->tanggal_raport_sas?->format('Y-m-d')) }}"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-blue-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan jika ingin otomatis mengikuti tanggal hari ini sewaktu dicetak.</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditTanggalRaport').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-sm flex items-center transition">
                        <i class="fa-solid fa-check mr-1.5"></i> Simpan Tanggal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Atur 3 Guru Mata Pelajaran (Non-Wali Kelas) -->
    <div id="modalEditGuruPengampu" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl p-6 border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-chalkboard-user text-emerald-600 mr-2 text-base"></i> Atur 3 Guru Mata Pelajaran (Non-Wali)
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; Untuk absensi kelas dan pengawas lembar ujian STS / SAS</p>
                </div>
                <button type="button" onclick="document.getElementById('modalEditGuruPengampu').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.guru-pengampu.update', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                @php $currentGurus = $kelas->guruPengampu; @endphp
                @for($idx = 0; $idx < 3; $idx++)
                    @php
                        $currG = $currentGurus->get($idx);
                        $defaultLabel = match($idx) {
                            0 => 'Guru Mapel 1 (Contoh: Pendidikan Agama Islam)',
                            1 => 'Guru Mapel 2 (Contoh: PJOK / Penjasorkes)',
                            default => 'Guru Mapel 3 (Contoh: Bahasa Inggris / Bahasa Jawa)',
                        };
                    @endphp
                    <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                            <span class="text-xs font-bold text-slate-800 flex items-center">
                                <span class="w-5 h-5 rounded-full bg-emerald-600 text-white text-[10px] font-black flex items-center justify-center mr-2">
                                    {{ $idx + 1 }}
                                </span>
                                {{ $defaultLabel }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Mata Pelajaran</label>
                                <select name="guru[{{ $idx }}][mapel_id]" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white">
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    @foreach($mapelList as $m)
                                        <option value="{{ $m->id }}" {{ ($currG && $currG->mapel_id == $m->id) ? 'selected' : '' }}>
                                            {{ $m->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Guru & Gelar</label>
                                <input type="text" name="guru[{{ $idx }}][nama]" value="{{ old("guru.{$idx}.nama", $currG ? $currG->nama : '') }}"
                                    placeholder="Contoh: Nama Guru, S.Pd."
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">NIP (Opsional)</label>
                                <input type="text" name="guru[{{ $idx }}][nip]" value="{{ old("guru.{$idx}.nip", $currG ? $currG->nip : '') }}"
                                    placeholder="Contoh: 19840510 201001 1 015"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>
                        </div>
                    </div>
                @endfor

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 italic">Nama guru akan otomatis menjadi pengawas saat mata pelajaran terkait dipilih.</span>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="document.getElementById('modalEditGuruPengampu').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                            <i class="fa-solid fa-check mr-1.5"></i> Simpan 3 Guru
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Rombongan Kelas (Nama, Fase, Tahun Ajaran, Wali) -->
    @if(auth()->user()->isAdmin())
        <div id="modalEditKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-pen-to-square text-emerald-600 mr-2 text-base"></i> Edit Rombongan Kelas
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Ubah nama rombel, koreksi fase kurikulum, atau wali kelas</p>
                    </div>
                    <button type="button" onclick="document.getElementById('modalEditKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
                </div>

                <form id="formEditKelas" action="{{ route('kelas.update', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nama Kelas *
                        </label>
                        <input type="text" name="nama" id="edit_kelas_nama" value="{{ old('nama', $kelas->nama) }}" required
                            placeholder="Contoh: Kelas 1, Kelas 3, Kelas 4A"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
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

                    @php
                        $currentFase = old('fase', $kelas->fase ?? 'Fase A');
                        if (str_contains(strtoupper($currentFase), 'C') || strtoupper($currentFase) === 'C') {
                            $selectedFase = 'Fase C';
                        } elseif (str_contains(strtoupper($currentFase), 'B') || strtoupper($currentFase) === 'B') {
                            $selectedFase = 'Fase B';
                        } else {
                            $selectedFase = 'Fase A';
                        }
                    @endphp
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Fase Kurikulum *
                        </label>
                        <select name="fase" id="edit_kelas_fase" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 bg-white">
                            <option value="Fase A" {{ $selectedFase === 'Fase A' ? 'selected' : '' }}>Fase A (Kelas 1 - 2)</option>
                            <option value="Fase B" {{ $selectedFase === 'Fase B' ? 'selected' : '' }}>Fase B (Kelas 3 - 4)</option>
                            <option value="Fase C" {{ $selectedFase === 'Fase C' ? 'selected' : '' }}>Fase C (Kelas 5 - 6)</option>
                        </select>
                        <div class="mt-1.5 bg-emerald-50 border border-emerald-200/60 rounded-xl p-2.5 text-[11px] text-emerald-900 leading-relaxed">
                            <i class="fa-solid fa-circle-info text-emerald-600 mr-1"></i>
                            <strong>Panduan Fase Kurikulum Merdeka SD:</strong>
                            <div class="grid grid-cols-3 gap-1 mt-1 text-[10px] text-emerald-800 font-medium">
                                <span class="bg-white/80 px-2 py-0.5 rounded border border-emerald-200 text-center">Fase A: Kls 1-2</span>
                                <span class="bg-white/80 px-2 py-0.5 rounded border border-emerald-200 text-center">Fase B: Kls 3-4</span>
                                <span class="bg-white/80 px-2 py-0.5 rounded border border-emerald-200 text-center">Fase C: Kls 5-6</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Tahun Ajaran *
                        </label>
                        <select name="tahun_ajaran_id" id="edit_kelas_tahun_ajaran_id" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                            @foreach($tahunAjaranList as $ta)
                                <option value="{{ $ta->id }}" {{ old('tahun_ajaran_id', $kelas->tahun_ajaran_id) == $ta->id ? 'selected' : '' }}>
                                    {{ $ta->nama_lengkap }} {{ $ta->aktif ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nama Guru / Wali Kelas *
                        </label>
                        <input type="text" name="wali_nama" id="edit_kelas_wali_nama" value="{{ old('wali_nama', $kelas->wali_nama ?: ($kelas->wali->name ?? '')) }}" required
                            placeholder="Contoh: Dewi Lestari, S.Pd."
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
                        <span class="text-[10px] text-slate-500">Nama guru penandatangan raport untuk kelas ini</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            NIP Wali Kelas (Opsional)
                        </label>
                        <input type="text" name="wali_nip" id="edit_kelas_wali_nip" value="{{ old('wali_nip', ($kelas->wali_nip && $kelas->wali_nip !== '-') ? $kelas->wali_nip : '') }}"
                            placeholder="Contoh: 19880315 201101 2 008"
                            class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                        <button type="button" onclick="konfirmasiHapusKelas()"
                            class="px-3 py-2 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition flex items-center"
                            title="Hapus Rombongan Belajar">
                            <i class="fa-solid fa-trash-can mr-1.5"></i> Hapus Kelas
                        </button>

                        <div class="flex items-center space-x-2">
                            <button type="button" onclick="document.getElementById('modalEditKelas').classList.add('hidden')"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                                <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>

                <form id="formHapusKelas" action="{{ route('kelas.destroy', $kelas->id) }}" method="POST" class="hidden">
                    @csrf
                    @method('DELETE')
                </form>
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
                    <p class="text-xs text-slate-500 mt-0.5">Kelas {{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
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
                    <p class="text-xs text-slate-500 mt-0.5">Kelas {{ $kelas->nama }} &bull; Semester {{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
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

                        <!-- Opsi B: Buku Leger Rekapitulasi Nilai Semester (Landscape) -->
                        <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas']) }}" target="_blank"
                           class="block p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 hover:bg-slate-100 transition group">
                            <div class="flex items-start space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm shrink-0 shadow-xs">
                                    <i class="fa-solid fa-book"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-slate-900 text-sm flex items-center justify-between">
                                        <span>Buku Leger Rekapitulasi Nilai (Landscape)</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-xs text-slate-600 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                    </div>
                                    <p class="text-slate-600 text-[11px] mt-0.5 leading-relaxed">
                                        Buku leger nilai kelas resmi memuat rekapitulasi nilai seluruh siswa, peringkat/ranking kelas, total nilai, dan rata-rata kelas.
                                    </p>
                                </div>
                            </div>
                        </a>
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

    <!-- Modal Atur KKM Per Rombel -->
    @include('kelas.modal_kkm')

    @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
        @include('nilai.modal_import_excel')
        @include('nilai.modal_kosongkan_nilai', ['mapels' => $mapelList])
    @endif

</div>

@push('scripts')
<script>
    function setEditKelasQuick(nama, fase) {
        document.getElementById('edit_kelas_nama').value = nama;
        document.getElementById('edit_kelas_fase').value = fase;
    }

    function konfirmasiHapusKelas() {
        const siswaCount = {{ $kelas->kelasSiswa->count() }};
        let msg = `Apakah Anda yakin ingin menghapus rombongan belajar "{{ $kelas->nama }}"?`;
        if (siswaCount > 0) {
            msg += `\n\nPERHATIAN: Ada ${siswaCount} siswa yang terdaftar di kelas ini. Seluruh data penempatan dan nilai di kelas ini akan ikut terhapus.`;
        }
        if (confirm(msg)) {
            document.getElementById('formHapusKelas').submit();
        }
    }

    // Auto-match Fase saat mengetik nama kelas
    const editNamaInput = document.getElementById('edit_kelas_nama');
    const editFaseSelect = document.getElementById('edit_kelas_fase');
    if (editNamaInput && editFaseSelect) {
        editNamaInput.addEventListener('input', function(e) {
            const val = e.target.value.toLowerCase();
            if (val.match(/(?:^|\D)(?:1|2|i|ii)(?:$|\D)/i)) {
                editFaseSelect.value = 'Fase A';
            } else if (val.match(/(?:^|\D)(?:3|4|iii|iv)(?:$|\D)/i)) {
                editFaseSelect.value = 'Fase B';
            } else if (val.match(/(?:^|\D)(?:5|6|v|vi)(?:$|\D)/i)) {
                editFaseSelect.value = 'Fase C';
            }
        });
    }
    // Update Link Cetak Bulanan berdasarkan bulan yang dipilih
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
@endpush
@endsection
