@extends('layouts.app')

@section('title', 'Rekap Nilai Kelas ' . $kelas->nama)

@section('content')
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }

    @page {
        size: landscape;
        margin: 3.5mm 4mm;
    }

    *, ::before, ::after {
        box-sizing: border-box !important;
    }

    html, body {
        background: #ffffff !important;
        font-family: Arial, Helvetica, sans-serif !important;
        font-size: 6.8pt !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        overflow: visible !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    main, .rekap-print-box, .overflow-x-auto {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    /* Fixed table layout guarantees all columns fit page width without clipping */
    table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
        font-size: 6.8pt !important;
        line-height: 1.1 !important;
        page-break-inside: avoid !important;
    }

    th, td {
        border: 0.5pt solid #334155 !important;
        padding: 1.5px 2px !important;
        color: #000000 !important;
        word-break: break-word !important;
        overflow: hidden !important;
    }

    thead th {
        background-color: #f1f5f9 !important;
        font-weight: 800 !important;
        padding: 2px 1px !important;
    }

    tfoot td {
        background-color: #f8fafc !important;
        font-weight: 800 !important;
        padding: 1.5px 2px !important;
    }

    tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
    }
}

@media screen {
    .print-only { display: none !important; }
}
</style>

<div class="space-y-6 rekap-print-box">

    <!-- Kop / Header Khusus Cetak 1 Lembar -->
    <div class="print-only text-center mb-1 border-b border-black pb-1">
        <h2 class="text-xs font-black uppercase tracking-wider text-black">
            {{ $kelas->sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
        </h2>
        <h3 class="text-[9.5pt] font-bold uppercase tracking-wide text-black mt-0.5">
            LEMBAR REKAPITULASI NILAI SUMATIF (LEGER KELAS) &bull; SEMESTER {{ strtoupper($kelas->tahunAjaran->semester ?? 'GANJIL') }}
        </h3>
        <p class="text-[8pt] text-black mt-0.5">
            Kelas: <strong>{{ $kelas->nama }}</strong> &bull;
            Tahun Ajaran: <strong>{{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</strong> &bull;
            Periode: <strong>{{ strtoupper($periode) }}</strong> &bull;
            Wali Kelas: <strong>{{ $kelas->nama_wali }}</strong>
            @if($kelas->nip_wali !== '-') (NIP: {{ $kelas->nip_wali }}) @endif
        </p>
    </div>

    <!-- Header Web Rekap -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Rekapitulasi Nilai</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 flex items-center">
                <i class="fa-solid fa-chart-line text-blue-600 mr-2"></i> Rekapitulasi Nilai: {{ $kelas->nama }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500">
                <span>Tahun Ajaran: {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Semester: <strong class="capitalize">{{ $kelas->tahunAjaran->semester ?? 'Ganjil' }}</strong> &bull; Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                    @if($kelas->nip_wali !== '-')
                        <span class="font-mono text-slate-400">(NIP: {{ $kelas->nip_wali }})</span>
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
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(isset($kelasList) && $kelasList->count() > 1)
                <!-- Pilihan Kelas Cepat -->
                <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold shadow-2xs">
                    <span class="text-[10px] uppercase font-bold text-slate-500 px-2 flex items-center gap-1">
                        <i class="fa-solid fa-chalkboard text-slate-400"></i>
                        <span>Kelas:</span>
                    </span>
                    @foreach($kelasList as $kOpt)
                        <a href="{{ route('rekap.kelas', ['kelas' => $kOpt->id, 'periode' => $periode, 'sort' => $sortBy, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
                           class="px-2.5 py-1.5 rounded-lg transition {{ $kOpt->id === $kelas->id ? 'bg-blue-600 text-white shadow-2xs font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-200' }}"
                           title="Buka rekapitulasi & grafik {{ $kOpt->nama }}">
                            {{ $kOpt->nama }}
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- Pilihan Periode STS / SAS -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts', 'sort' => $sortBy]) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS
                </a>
                <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sas', 'sort' => $sortBy]) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS
                </a>
            </div>

            <!-- Ganti Tanggal Rekap / Raport (No-Print) -->
            <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-xs shadow-2xs">
                <i class="fa-regular fa-calendar-days text-slate-500"></i>
                <label for="inputTanggalRekap" class="font-bold text-slate-700 whitespace-nowrap">Tgl:</label>
                <input type="date" id="inputTanggalRekap" value="{{ $tanggalObj->format('Y-m-d') }}"
                       class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="simpanTanggalRekap()"
                            class="px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold transition flex items-center shadow-2xs"
                            title="Simpan tanggal ini ke sistem agar sewaktu print berikutnya tanggalnya tetap sama">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
                    </button>
                @endif
                <span id="badgeStatusTanggalRekap" class="hidden text-[10px] font-bold text-emerald-600 ml-1"></span>
            </div>

            <a href="#section-grafik" class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-xl text-xs border border-blue-200 shadow-2xs transition flex items-center" title="Lihat Visualisasi Grafik Nilai">
                <i class="fa-solid fa-chart-pie mr-1.5 text-blue-600"></i> Grafik Nilai
            </a>
            <a href="{{ route('rekap.kelas.cetak', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
               title="Buka Lembar Leger Rekap Nilai Resmi Siap Cetak (A4/F4)">
                <i class="fa-solid fa-print mr-1.5 text-amber-300"></i> Cetak Rekap Semester
            </a>
            <button onclick="window.print()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center" title="Cetak Rekap Pas 1 Lembar Landscape">
                <i class="fa-solid fa-file-invoice mr-1.5"></i> Cetak Layar Ini
            </button>
            <a id="btnCetakRaportSekelas" href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center" title="Cetak Raport Seluruh Siswa Sekelas">
                <i class="fa-solid fa-file-lines mr-1.5"></i> Cetak Raport Sekelas
            </a>
            <a id="btnPdfSekelas" href="{{ route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center" title="Unduh PDF Raport Sekelas">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> PDF Sekelas
            </a>
        </div>
    </div>

    @php
        $juara1 = collect($rekapData['siswa_list'])->firstWhere('peringkat', 1);
    @endphp

    <!-- Statistik Ringkas Rekap -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Rata-rata Kelas</p>
                <p class="text-2xl sm:text-3xl font-black text-blue-700 mt-1 tracking-tight">{{ $rekapData['rata_rata_kelas_format'] }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Nilai sumatif gabungan</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-amber-200/80 bg-linear-to-br from-white to-amber-50/40 shadow-2xs hover:shadow-md transition flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-700 flex items-center">
                    <i class="fa-solid fa-crown text-amber-500 mr-1.5"></i> Ranking 1 Kelas
                </p>
                <p class="text-base sm:text-lg font-black text-slate-900 mt-1 truncate" title="{{ $juara1['siswa']->nama ?? '-' }}">
                    {{ $juara1['siswa']->nama ?? '-' }}
                </p>
                <p class="text-[11px] text-amber-800 font-semibold mt-0.5">
                    @if($juara1 && ($juara1['hasil']['count_sumatif'] ?? 0) > 0)
                        Total: {{ \App\Services\HitungNilaiService::formatAngka($juara1['hasil']['total_sumatif']) }} (Rata-rata: {{ $juara1['hasil']['rata_sumatif_format'] }})
                    @else
                        Belum ada nilai
                    @endif
                </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-100 border border-amber-200 text-amber-700 flex items-center justify-center text-xl shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-trophy"></i>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Kelengkapan Input</p>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 mt-1 tracking-tight">{{ $rekapData['persen_selesai'] }}%</p>
                <p class="text-[11px] text-slate-500 mt-0.5">{{ $rekapData['siswa_selesai'] }} dari {{ $rekapData['jumlah_siswa'] }} siswa lengkap</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-2xs hover:shadow-md transition flex items-center justify-between h-full group">
            <div class="flex-1 pr-3">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Mata Pelajaran</p>
                <p class="text-2xl sm:text-3xl font-black text-slate-900 mt-1 tracking-tight">{{ $rekapData['mapels']->count() }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Mapel aktif dinilai</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition-transform duration-200">
                <i class="fa-solid fa-book-open"></i>
            </div>
        </div>
    </div>

    <!-- Visualisasi & Analisis Grafik Nilai (Khusus Layar / No-Print) -->
    <div id="section-grafik" class="no-print space-y-4" x-data="{ showGrafik: true, activeTab: 'semua', rankLimit: 10, jenisNilai: '{{ $jenisNilai ?? 'rapor' }}', bulanFormatif: '{{ $bulan ?? '' }}' }">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Header Kartu Visualisasi -->
            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3 bg-gradient-to-r from-white via-slate-50/50 to-blue-50/30">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-xl bg-blue-100 text-blue-700 text-xs font-bold shadow-2xs">
                            <i class="fa-solid fa-chart-column"></i>
                        </span>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 uppercase tracking-wider">
                            Visualisasi &amp; Analisis Grafik Nilai
                        </h2>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                            {{ strtoupper($periode) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        Grafik interaktif performa mata pelajaran terhadap KKM, distribusi capaian siswa, perbandingan nilai, kehadiran, dan pembiasaan.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Tab Filter Grafik -->
                    <div class="inline-flex rounded-xl p-1 bg-slate-100 text-xs font-semibold" x-show="showGrafik">
                        <button type="button" @click="activeTab = 'semua'; window.switchRekapTab && window.switchRekapTab('semua')"
                                :class="activeTab === 'semua' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-table-cells-large text-[11px]"></i>
                            <span>Semua</span>
                        </button>
                        <button type="button" @click="activeTab = 'mapel'; window.switchRekapTab && window.switchRekapTab('mapel')"
                                :class="activeTab === 'mapel' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-chart-column text-[11px]"></i>
                            <span>Mapel vs KKM</span>
                        </button>
                        <button type="button" @click="activeTab = 'distribusi'; window.switchRekapTab && window.switchRekapTab('distribusi')"
                                :class="activeTab === 'distribusi' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-chart-pie text-[11px]"></i>
                            <span>Distribusi</span>
                        </button>
                        <button type="button" @click="activeTab = 'ranking'; window.switchRekapTab && window.switchRekapTab('ranking')"
                                :class="activeTab === 'ranking' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-chart-simple text-[11px] text-blue-600"></i>
                            <span>Rentang Nilai</span>
                        </button>
                        <button type="button" @click="activeTab = 'perbandingan'; window.switchRekapTab && window.switchRekapTab('perbandingan')"
                                :class="activeTab === 'perbandingan' ? 'bg-white text-indigo-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-code-compare text-[11px] text-indigo-600"></i>
                            <span>Perbandingan Nilai</span>
                        </button>
                        <button type="button" @click="activeTab = 'kehadiran'; window.switchRekapTab && window.switchRekapTab('kehadiran')"
                                :class="activeTab === 'kehadiran' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-clipboard-user text-[11px] text-sky-600"></i>
                            <span>Kehadiran (SIA)</span>
                        </button>
                        <button type="button" @click="activeTab = 'pembiasaan'; window.switchRekapTab && window.switchRekapTab('pembiasaan')"
                                :class="activeTab === 'pembiasaan' ? 'bg-white text-blue-700 shadow-2xs font-bold' : 'text-slate-600 hover:text-slate-900'"
                                class="px-2.5 py-1 rounded-lg transition flex items-center gap-1">
                            <i class="fa-solid fa-hands-praying text-[11px] text-emerald-600"></i>
                            <span>Pembiasaan</span>
                        </button>
                    </div>

                    @if($rekapData['chart_data']['has_data'] ?? false)
                        <!-- Tombol Unduh Seluruh Grafik dalam Satu Infografik -->
                        <button type="button" onclick="downloadAllChartsInfographic('Infografik_Lengkap_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                x-show="showGrafik && activeTab === 'semua'"
                                class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-2xs transition flex items-center gap-1.5"
                                title="Unduh seluruh grafik & analisis lengkap dalam 1 gambar infografik">
                            <i class="fa-solid fa-file-image text-indigo-600"></i>
                            <span>Infografik Lengkap</span>
                        </button>
                    @endif

                    <!-- Tombol Ciutkan / Buka Grafik -->
                    <button type="button" @click="showGrafik = !showGrafik; if(showGrafik && window.switchRekapTab) window.switchRekapTab(activeTab);"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5 shadow-2xs"
                            :title="showGrafik ? 'Sembunyikan panel grafik' : 'Tampilkan panel grafik'">
                        <i class="fa-solid" :class="showGrafik ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        <span x-text="showGrafik ? 'Sembunyikan' : 'Tampilkan Grafik'"></span>
                    </button>
                </div>
            </div>

            @if(isset($kelasList) && $kelasList->count() > 1)
                <!-- Bar Pilihan Kelas Khusus Grafik -->
                <div class="px-4 sm:px-5 py-2.5 bg-gradient-to-r from-slate-50 via-blue-50/30 to-indigo-50/20 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-[11px] font-bold shrink-0">
                            <i class="fa-solid fa-users-viewfinder"></i>
                        </span>
                        <div>
                            <span class="font-extrabold text-slate-900">Pilih Kelas untuk Visualisasi Grafik:</span>
                            <span class="text-[11px] text-slate-500 ml-1">Grafik saat ini menampilkan data khusus <strong class="text-blue-700 font-bold">{{ $kelas->nama }}</strong></span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($kelasList as $kOpt)
                            <a href="{{ route('rekap.kelas', ['kelas' => $kOpt->id, 'periode' => $periode, 'sort' => $sortBy, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}#section-grafik"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold transition text-xs {{ $kOpt->id === $kelas->id ? 'bg-blue-600 text-white shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs' }}"
                               title="Lihat grafik nilai, SIA & pembiasaan untuk {{ $kOpt->nama }}">
                                <span>{{ $kOpt->nama }}</span>
                                @if($kOpt->id === $kelas->id)
                                    <i class="fa-solid fa-circle-check text-[11px] text-emerald-300"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Konten Grafik -->
            <div x-show="showGrafik" class="p-4 sm:p-5 space-y-5">
                @if($rekapData['chart_data']['has_data'] ?? false)
                    <!-- Filter Jenis Nilai Akademik (Formatif Bulanan Jul-Des, STS/UTS, Semesteran/UAS, Nilai Rapor) -->
                    <div x-show="activeTab === 'semua' || activeTab === 'mapel' || activeTab === 'distribusi' || activeTab === 'ranking'"
                         class="p-3 bg-gradient-to-r from-slate-50 via-blue-50/40 to-slate-50 rounded-2xl border border-blue-200/80 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-600 font-bold uppercase tracking-wider text-[11px] flex items-center gap-1.5 mr-1">
                                <i class="fa-solid fa-filter text-blue-600"></i> Jenis Nilai:
                            </span>

                            <button type="button" @click="jenisNilai = 'rapor'; bulanFormatif = ''; switchJenisNilai('rapor')"
                                    :class="jenisNilai === 'rapor' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                                    class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 text-xs font-bold shadow-2xs">
                                <i class="fa-solid fa-file-invoice"></i>
                                <span>Nilai Rapor (Akhir)</span>
                            </button>

                            <button type="button" @click="jenisNilai = 'uts'; bulanFormatif = ''; switchJenisNilai('uts')"
                                    :class="jenisNilai === 'uts' ? 'bg-amber-500 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                                    class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 text-xs font-bold shadow-2xs">
                                <i class="fa-solid fa-file-pen"></i>
                                <span>STS / UTS</span>
                            </button>

                            <button type="button" @click="jenisNilai = 'uas'; bulanFormatif = ''; switchJenisNilai('uas')"
                                    :class="jenisNilai === 'uas' ? 'bg-purple-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                                    class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 text-xs font-bold shadow-2xs">
                                <i class="fa-solid fa-award"></i>
                                <span>Semesteran / UAS</span>
                            </button>

                            <button type="button" @click="jenisNilai = 'formatif'; if(!bulanFormatif) switchJenisNilai('formatif')"
                                    :class="jenisNilai === 'formatif' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200'"
                                    class="px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 text-xs font-bold shadow-2xs">
                                <i class="fa-solid fa-calendar-check"></i>
                                <span>Formatif Bulanan</span>
                            </button>

                            <!-- Sub-Select Bulan Formatif (Jul-Des) -->
                            <div x-show="jenisNilai === 'formatif'" class="flex items-center gap-1.5 bg-white px-3 py-1.5 rounded-xl border border-emerald-300 shadow-2xs">
                                <label for="selectBulanFormatif" class="text-[11px] font-bold text-emerald-800 flex items-center gap-1">
                                    <i class="fa-regular fa-calendar-days text-emerald-600"></i> Bulan:
                                </label>
                                <select id="selectBulanFormatif" x-model="bulanFormatif"
                                        @change="switchJenisNilai('formatif', $event.target.value)"
                                        class="text-xs font-black text-emerald-900 bg-transparent border-none focus:ring-0 p-0 pr-4 cursor-pointer">
                                    <option value="">📊 Rata-rata 6 Bulan (JUL - DES)</option>
                                    <option value="7">📅 Juli (Bulan 7)</option>
                                    <option value="8">📅 Agustus (Bulan 8)</option>
                                    <option value="9">📅 September (Bulan 9)</option>
                                    <option value="10">📅 Oktober (Bulan 10)</option>
                                    <option value="11">📅 November (Bulan 11)</option>
                                    <option value="12">📅 Desember (Bulan 12)</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 font-medium">
                            <i class="fa-solid fa-circle-info text-blue-500"></i>
                            <span id="active-jenis-label">Menampilkan grafik: <strong>Nilai Rapor Akhir</strong></span>
                        </div>
                    </div>

                    <!-- Ringkasan Cepat Analisis Nilai -->
                    @php
                        $sum = $rekapData['chart_data']['summary'];
                        $khSummary = $rekapData['chart_data']['kehadiran'] ?? null;
                        $pemSummary = $rekapData['chart_data']['pembiasaan'] ?? null;
                    @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 text-xs">
                        <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-arrow-trend-up"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-emerald-700 block truncate">Mapel Tertinggi</span>
                                <span class="font-extrabold text-slate-900 truncate block">
                                    <span id="kpi-mapel-tertinggi-nama">{{ $sum['mapel_tertinggi']['nama_singkat'] ?? '-' }}</span>
                                    <span class="text-emerald-700 font-mono" id="kpi-mapel-tertinggi-val">({{ $sum['mapel_tertinggi']['rata_rata_format'] ?? '-' }})</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-rose-50/70 border border-rose-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-arrow-trend-down"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-rose-700 block truncate">Perlu Perhatian</span>
                                <span class="font-extrabold text-slate-900 truncate block">
                                    <span id="kpi-mapel-terendah-nama">{{ $sum['mapel_terendah']['nama_singkat'] ?? '-' }}</span>
                                    <span class="text-rose-700 font-mono" id="kpi-mapel-terendah-val">({{ $sum['mapel_terendah']['rata_rata_format'] ?? '-' }})</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-star"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-amber-800 block truncate">Rata² Tertinggi</span>
                                <span class="font-extrabold text-slate-900 truncate block" id="kpi-siswa-tertinggi-container" title="{{ $sum['siswa_tertinggi']['nama'] ?? '-' }}">
                                    <span id="kpi-siswa-tertinggi-nama">{{ $sum['siswa_tertinggi']['nama'] ?? '-' }}</span>
                                    <span class="text-amber-800 font-mono" id="kpi-siswa-tertinggi-val">({{ $sum['siswa_tertinggi']['nilai_format'] ?? '-' }})</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-blue-50/70 border border-blue-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-bullseye"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-blue-700 block truncate">Tuntas KKM</span>
                                <span class="font-extrabold text-slate-900 truncate block">
                                    <span id="kpi-tuntas-kkm-pct">{{ $sum['persen_tuntas_kkm'] }}</span>% Siswa
                                    <span class="text-slate-500 font-normal text-[11px]">(~<span id="kpi-kkm-kelas">{{ $sum['rata_kkm_kelas'] }}</span>)</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-sky-50/70 border border-sky-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-clipboard-user"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-sky-700 block truncate">Kehadiran (SIA)</span>
                                <span class="font-extrabold text-slate-900 truncate block">
                                    S:{{ $khSummary['total_sakit'] ?? 0 }} I:{{ $khSummary['total_izin'] ?? 0 }} A:{{ $khSummary['total_alpa'] ?? 0 }}
                                    <span class="text-sky-700 font-normal text-[10px]">({{ $khSummary['siswa_hadir_penuh'] ?? 0 }}/{{ $rekapData['jumlah_siswa'] }} hadir 100%)</span>
                                </span>
                            </div>
                        </div>

                        <div class="p-2.5 rounded-xl bg-teal-50/70 border border-teal-200/80 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-hands-praying"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] uppercase font-bold text-teal-700 block truncate">Pembiasaan</span>
                                <span class="font-extrabold text-slate-900 truncate block">
                                    {{ $pemSummary['percentages']['sangat_baik'] ?? 0 }}% Sangat Baik
                                    <span class="text-teal-700 font-normal text-[10px]">({{ $rekapData['jumlah_siswa'] }} siswa)</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Grid Grafik -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
                        <!-- Grafik 1: Nilai Mapel vs KKM -->
                        <div class="bg-slate-50/60 rounded-2xl p-4 border border-slate-200/80 flex flex-col justify-between"
                             :class="(activeTab === 'semua') ? 'lg:col-span-7' : 'col-span-12'"
                             x-show="activeTab === 'semua' || activeTab === 'mapel'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center">
                                            <i class="fa-solid fa-chart-column text-blue-600 mr-2"></i> Performa Mapel vs KKM
                                        </h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Batang hijau = tuntas/di atas KKM, merah = di bawah KKM, garis putus-putus = standar KKM.
                                        </p>
                                    </div>
                                    <button type="button" onclick="downloadChart('chartMapel', 'Grafik_Mapel_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                            title="Unduh grafik Mapel vs KKM lengkap dengan keterangan">
                                        <i class="fa-solid fa-download text-blue-600"></i>
                                        <span>Unduh Gambar</span>
                                    </button>
                                </div>
                                <div class="relative h-64 sm:h-72 w-full mt-2">
                                    <canvas id="chartMapel"></canvas>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3 text-[11px] pt-3 mt-2 border-t border-slate-200/60 text-slate-600">
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Rata-rata ≥ KKM
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-rose-500 inline-block"></span> Rata-rata &lt; KKM
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-4 border-t-2 border-dashed border-amber-500 inline-block"></span> Standar KKM
                                </span>
                            </div>
                        </div>

                        <!-- Grafik 2: Distribusi Capaian Nilai Siswa -->
                        <div class="bg-slate-50/60 rounded-2xl p-4 border border-slate-200/80 flex flex-col justify-between"
                             :class="(activeTab === 'semua') ? 'lg:col-span-5' : 'col-span-12'"
                             x-show="activeTab === 'semua' || activeTab === 'distribusi'">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center">
                                            <i class="fa-solid fa-chart-pie text-emerald-600 mr-2"></i> Distribusi Capaian Siswa
                                        </h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Komposisi tingkat capaian berdasarkan nilai rata-rata siswa.
                                        </p>
                                    </div>
                                    <button type="button" onclick="downloadChart('chartDistribusi', 'Grafik_Distribusi_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                            title="Unduh grafik distribusi capaian lengkap dengan keterangan">
                                        <i class="fa-solid fa-download text-emerald-600"></i>
                                        <span>Unduh Gambar</span>
                                    </button>
                                </div>
                                <div class="relative h-48 sm:h-56 w-full flex items-center justify-center mt-1">
                                    <canvas id="chartDistribusi"></canvas>
                                </div>
                            </div>

                            @php
                                $dist = $rekapData['chart_data']['distribusi'];
                            @endphp
                            <div class="grid grid-cols-2 gap-2 text-[11px] pt-3 mt-2 border-t border-slate-200/60">
                                <div class="flex items-center justify-between p-1.5 rounded-lg bg-white border border-slate-200/70">
                                    <span class="flex items-center gap-1.5 text-slate-700 font-semibold truncate">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span> Sangat Baik
                                    </span>
                                    <span class="font-bold text-slate-900 ml-1" id="dist-sb-count">{{ $dist['counts'][0] }} ({{ $dist['percentages'][0] }}%)</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 rounded-lg bg-white border border-slate-200/70">
                                    <span class="flex items-center gap-1.5 text-slate-700 font-semibold truncate">
                                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span> Baik
                                    </span>
                                    <span class="font-bold text-slate-900 ml-1" id="dist-b-count">{{ $dist['counts'][1] }} ({{ $dist['percentages'][1] }}%)</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 rounded-lg bg-white border border-slate-200/70">
                                    <span class="flex items-center gap-1.5 text-slate-700 font-semibold truncate">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span> Cukup
                                    </span>
                                    <span class="font-bold text-slate-900 ml-1" id="dist-c-count">{{ $dist['counts'][2] }} ({{ $dist['percentages'][2] }}%)</span>
                                </div>
                                <div class="flex items-center justify-between p-1.5 rounded-lg bg-white border border-slate-200/70">
                                    <span class="flex items-center gap-1.5 text-slate-700 font-semibold truncate">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shrink-0"></span> Perlu Bimbingan
                                    </span>
                                    <span class="font-bold text-slate-900 ml-1" id="dist-pb-count">{{ $dist['counts'][3] }} ({{ $dist['percentages'][3] }}%)</span>
                                </div>
                            </div>
                        </div>

                        <!-- Grafik 3: Sebaran Nilai Siswa (Rentang Nilai 0 - 100) -->
                        <div class="col-span-12 bg-slate-50/60 rounded-2xl p-4 border border-slate-200/80"
                             x-show="activeTab === 'semua' || activeTab === 'ranking'">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                <div>
                                    <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center flex-wrap gap-2">
                                        <span class="flex items-center"><i class="fa-solid fa-chart-simple text-blue-600 mr-2"></i> Sebaran Capaian Siswa (Rentang Nilai)</span>
                                        <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-blue-100 text-blue-800 border border-blue-200">{{ $kelas->nama }}</span>
                                    </h3>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Distribusi jumlah peserta didik per rentang nilai (0-25, 26-50, 51-75, 76-100) per mata pelajaran khusus {{ $kelas->nama }}.
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Filter Pilihan Mata Pelajaran -->
                                    <div class="flex items-center gap-1.5 bg-white px-2.5 py-1 rounded-lg border border-slate-200 shadow-2xs">
                                        <label for="selectMapelRentang" class="text-[11px] font-bold text-slate-600 flex items-center gap-1">
                                            <i class="fa-solid fa-book-open text-blue-600"></i> Mapel:
                                        </label>
                                        <select id="selectMapelRentang" onchange="switchRentangMapel(this.value)"
                                                class="text-xs font-bold text-slate-800 bg-transparent border-none focus:ring-0 p-0 pr-4 cursor-pointer">
                                            <option value="semua">📊 Semua Mapel (Grafik Bertumpuk)</option>
                                            <option value="rata_rata">🎯 Rata-rata Nilai Akhir Siswa</option>
                                            @foreach($rekapData['mapels'] as $m)
                                                <option value="{{ $m->id }}">{{ $m->nama_singkat ?? $m->nama }} - {{ $m->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button type="button" onclick="downloadChart('chartRanking', 'Grafik_Rentang_Nilai_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                            title="Unduh grafik sebaran rentang nilai siswa lengkap dengan keterangan">
                                        <i class="fa-solid fa-download text-blue-600"></i>
                                        <span>Unduh Gambar</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Baris Status & Petunjuk Scope -->
                            <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-500 mb-1.5 pt-1 border-t border-slate-200/50">
                                <span id="rentang-scope-label" class="font-semibold text-slate-700 flex items-center gap-1">
                                    <i class="fa-solid fa-layer-group text-blue-600"></i> Menampilkan: <strong>Semua Mata Pelajaran (Komparasi Bertumpuk)</strong>
                                </span>
                                <span id="rentang-sub-hint" class="text-[10px] text-slate-400">
                                    💡 Klik batang mapel pada grafik atau pilih menu dropdown untuk rincian per mapel
                                </span>
                            </div>

                            <div class="relative w-full max-w-4xl mx-auto overflow-hidden transition-all duration-200" id="rankingCanvasWrapper" style="height: 250px;">
                                <canvas id="chartRanking"></canvas>
                            </div>

                            @php
                                $rentang = $rekapData['chart_data']['rentang_nilai'] ?? null;
                                $km = $rentang['kumulatif_mapel'] ?? null;
                                $cR1 = $km['counts'][0] ?? ($rentang['counts'][0] ?? 0);
                                $pR1 = $km['percentages'][0] ?? ($rentang['percentages'][0] ?? 0);
                                $cR2 = $km['counts'][1] ?? ($rentang['counts'][1] ?? 0);
                                $pR2 = $km['percentages'][1] ?? ($rentang['percentages'][1] ?? 0);
                                $cR3 = $km['counts'][2] ?? ($rentang['counts'][2] ?? 0);
                                $pR3 = $km['percentages'][2] ?? ($rentang['percentages'][2] ?? 0);
                                $cR4 = $km['counts'][3] ?? ($rentang['counts'][3] ?? 0);
                                $pR4 = $km['percentages'][3] ?? ($rentang['percentages'][3] ?? 0);
                            @endphp
                            @if($rentang)
                                <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-xs pt-2 mt-2 border-t border-slate-200/60">
                                    <div class="flex flex-col p-2.5 rounded-xl bg-white border border-rose-200/80 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 font-bold text-rose-700">
                                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> 0 - 25
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 font-bold border border-rose-200">Perlu Intervensi</span>
                                        </div>
                                        <div class="mt-1.5 flex items-baseline justify-between">
                                            <span class="text-xl font-black text-slate-900" id="rentang-card-0-count">{{ $cR1 }} <span class="text-xs font-semibold text-slate-500">Nilai</span></span>
                                            <span class="text-xs font-bold text-rose-600 font-mono" id="rentang-card-0-pct">{{ $pR1 }}%</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col p-2.5 rounded-xl bg-white border border-amber-200/80 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 font-bold text-amber-700">
                                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span> 26 - 50
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 font-bold border border-amber-200">Perlu Bimbingan</span>
                                        </div>
                                        <div class="mt-1.5 flex items-baseline justify-between">
                                            <span class="text-xl font-black text-slate-900" id="rentang-card-1-count">{{ $cR2 }} <span class="text-xs font-semibold text-slate-500">Nilai</span></span>
                                            <span class="text-xs font-bold text-amber-600 font-mono" id="rentang-card-1-pct">{{ $pR2 }}%</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col p-2.5 rounded-xl bg-white border border-blue-200/80 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 font-bold text-blue-700">
                                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> 51 - 75
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-bold border border-blue-200">Cukup</span>
                                        </div>
                                        <div class="mt-1.5 flex items-baseline justify-between">
                                            <span class="text-xl font-black text-slate-900" id="rentang-card-2-count">{{ $cR3 }} <span class="text-xs font-semibold text-slate-500">Nilai</span></span>
                                            <span class="text-xs font-bold text-blue-600 font-mono" id="rentang-card-2-pct">{{ $pR3 }}%</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col p-2.5 rounded-xl bg-white border border-emerald-200/80 shadow-2xs">
                                        <div class="flex items-center justify-between">
                                            <span class="inline-flex items-center gap-1.5 font-bold text-emerald-700">
                                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> 76 - 100
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 font-bold border border-emerald-200">Baik - Sangat Baik</span>
                                        </div>
                                        <div class="mt-1.5 flex items-baseline justify-between">
                                            <span class="text-xl font-black text-slate-900" id="rentang-card-3-count">{{ $cR4 }} <span class="text-xs font-semibold text-slate-500">Nilai</span></span>
                                            <span class="text-xs font-bold text-emerald-600 font-mono" id="rentang-card-3-pct">{{ $pR4 }}%</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detail Nama Siswa Saat Mapel Tertentu Dipilih -->
                                <div id="rentang-siswa-detail" class="hidden mt-3 p-3 bg-white rounded-xl border border-slate-200/90 text-xs shadow-2xs"></div>
                            @endif
                        </div>

                        <!-- Grafik Baru: Perbandingan Nilai (Formatif, UTS, UAS, Nilai Rapor Berdampingan) -->
                        <div class="col-span-12 bg-slate-50/60 rounded-2xl p-4 sm:p-5 border border-indigo-200/80 flex flex-col justify-between"
                             x-show="activeTab === 'semua' || activeTab === 'perbandingan'">
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center flex-wrap gap-2">
                                            <span class="flex items-center"><i class="fa-solid fa-code-compare text-indigo-600 mr-2"></i> Perbandingan Nilai: Formatif vs UTS vs Semesteran vs Nilai Rapor</span>
                                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-indigo-100 text-indigo-800 border border-indigo-200">{{ $kelas->nama }}</span>
                                        </h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Komparasi capaian nilai peserta didik per mata pelajaran dari asesmen Formatif, UTS (STS), Semesteran (SAS), hingga Nilai Rapor Akhir secara berdampingan.
                                        </p>
                                    </div>
                                    <button type="button" onclick="downloadChart('chartPerbandingan', 'Grafik_Perbandingan_Nilai_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5 self-start sm:self-auto"
                                            title="Unduh grafik perbandingan nilai lengkap dengan keterangan">
                                        <i class="fa-solid fa-download text-indigo-600"></i>
                                        <span>Unduh Gambar</span>
                                    </button>
                                </div>

                                <!-- Ringkasan Metrik Overall Perbandingan -->
                                @php
                                    $pb = $rekapData['chart_data']['perbandingan'] ?? null;
                                @endphp
                                @if($pb)
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3 text-xs">
                                        <div class="p-2 rounded-xl bg-blue-50/80 border border-blue-200 text-center">
                                            <span class="text-[10px] font-bold uppercase text-blue-700 block">Rata² Formatif</span>
                                            <span class="font-black text-slate-900 text-sm font-mono">{{ $pb['overall']['formatif'] }}</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-amber-50/80 border border-amber-200 text-center">
                                            <span class="text-[10px] font-bold uppercase text-amber-700 block">Rata² UTS / STS</span>
                                            <span class="font-black text-slate-900 text-sm font-mono">{{ $pb['overall']['uts'] }}</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-purple-50/80 border border-purple-200 text-center">
                                            <span class="text-[10px] font-bold uppercase text-purple-700 block">Rata² SAS / UAS</span>
                                            <span class="font-black text-slate-900 text-sm font-mono">{{ $pb['overall']['uas'] }}</span>
                                        </div>
                                        <div class="p-2 rounded-xl bg-emerald-50/80 border border-emerald-200 text-center">
                                            <span class="text-[10px] font-bold uppercase text-emerald-700 block">Rata² Nilai Rapor</span>
                                            <span class="font-black text-slate-900 text-sm font-mono">{{ $pb['overall']['rapor'] }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="relative h-72 sm:h-80 w-full mt-2">
                                    <canvas id="chartPerbandingan"></canvas>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-4 text-[11px] pt-3 mt-2 border-t border-slate-200/60 text-slate-600">
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> Formatif (Rata-rata)
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-amber-500 inline-block"></span> STS / UTS
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-purple-500 inline-block"></span> SAS / UAS
                                </span>
                                <span class="inline-flex items-center gap-1.5 font-semibold">
                                    <span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span> Nilai Rapor (Akhir)
                                </span>
                            </div>
                        </div>

                        <!-- Grafik 4: Rekapitulasi Kehadiran Siswa (SIA - Non-Akademik) -->
                        <div class="col-span-12 bg-slate-50/60 rounded-2xl p-4 sm:p-5 border border-sky-200/80 flex flex-col justify-between"
                             x-show="activeTab === 'semua' || activeTab === 'kehadiran'">
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center flex-wrap gap-2">
                                            <span class="flex items-center"><i class="fa-solid fa-clipboard-user text-sky-600 mr-2"></i> Rekapitulasi Kehadiran Siswa (SIA)</span>
                                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-sky-100 text-sky-800 border border-sky-200">{{ $kelas->nama }}</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600 border border-slate-200">Non-Akademik</span>
                                        </h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Analisis mandiri absensi: donat komposisi S/I/A, tren ketidakhadiran per bulan (Juli–Desember), dan peringkat alpa siswa.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="downloadChart('chartKehadiran', 'Grafik_Kehadiran_Donat_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                                title="Unduh grafik donat kehadiran">
                                            <i class="fa-solid fa-download text-sky-600"></i>
                                            <span>Unduh Donat</span>
                                        </button>
                                        <button type="button" onclick="downloadChart('chartKehadiranSiswa', 'Grafik_Presensi_Siswa_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                                title="Unduh grafik bar kehadiran per siswa">
                                            <i class="fa-solid fa-download text-sky-600"></i>
                                            <span>Unduh Bar Siswa</span>
                                        </button>
                                    </div>
                                </div>

                                @php
                                    $kh = $rekapData['chart_data']['kehadiran'] ?? null;
                                    $siswaAlpaTinggi = $kh ? collect($kh['siswa_bar'] ?? [])->filter(fn ($s) => $s['is_peringatan_alpa'])->values() : collect();
                                @endphp

                                <!-- Peringatan Siswa Alpa Tinggi jika ada -->
                                @if($siswaAlpaTinggi->isNotEmpty())
                                    <div class="mb-4 p-3 bg-rose-50 border-2 border-rose-300 rounded-xl text-xs text-rose-900 shadow-2xs flex items-start gap-2.5">
                                        <div class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 text-sm mt-0.5">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-black text-rose-800 text-xs uppercase tracking-wide">
                                                🚨 Peringatan: Terdapat {{ $siswaAlpaTinggi->count() }} Siswa dengan Alpa Melebihi Batas Sekolah (Batas: {{ $kh['batas_alpa'] ?? 3 }} Hari)
                                            </div>
                                            <div class="mt-1 flex flex-wrap gap-2">
                                                @foreach($siswaAlpaTinggi as $sat)
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-white border border-rose-300 font-bold text-rose-700 text-[11px]">
                                                        <span>{{ $sat['nama'] }}</span>
                                                        <span class="px-1 py-0.2 rounded bg-rose-600 text-white text-[10px] font-black">{{ $sat['alpa'] }} Alpa</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Grid Visualisasi SIA: Donat & Tren Bulanan -->
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">
                                    <!-- Donut Komposisi S/I/A -->
                                    <div class="lg:col-span-5 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 flex items-center justify-between mb-2">
                                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-pie text-sky-600"></i> Komposisi S/I/A</span>
                                                <span class="text-[10px] text-slate-400">Total: {{ $kh['total_tidak_hadir'] ?? 0 }} Hari</span>
                                            </div>
                                            <div class="relative h-44 sm:h-48 w-full flex items-center justify-center">
                                                <canvas id="chartKehadiran"></canvas>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-3 gap-1.5 text-[11px] pt-3 mt-2 border-t border-slate-100">
                                            <div class="p-1.5 rounded-lg bg-blue-50 border border-blue-100 text-center">
                                                <span class="text-[10px] text-blue-700 font-bold block">Sakit (S)</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $kh['total_sakit'] ?? 0 }} Hari</span>
                                                <span class="text-[9px] text-slate-400">({{ $kh['percentages'][0] ?? 0 }}%)</span>
                                            </div>
                                            <div class="p-1.5 rounded-lg bg-amber-50 border border-amber-100 text-center">
                                                <span class="text-[10px] text-amber-700 font-bold block">Izin (I)</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $kh['total_izin'] ?? 0 }} Hari</span>
                                                <span class="text-[9px] text-slate-400">({{ $kh['percentages'][1] ?? 0 }}%)</span>
                                            </div>
                                            <div class="p-1.5 rounded-lg bg-rose-50 border border-rose-100 text-center">
                                                <span class="text-[10px] text-rose-700 font-bold block">Alpa (A)</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $kh['total_alpa'] ?? 0 }} Hari</span>
                                                <span class="text-[9px] text-slate-400">({{ $kh['percentages'][2] ?? 0 }}%)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tren Bulanan Juli - Desember -->
                                    <div class="lg:col-span-7 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 flex items-center justify-between mb-2">
                                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-line text-blue-600"></i> Tren Ketidakhadiran per Bulan (Juli - Desember)</span>
                                                <button type="button" onclick="downloadChart('chartKehadiranTren', 'Grafik_Tren_Kehadiran_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                                        class="text-[10px] text-blue-600 hover:underline font-bold flex items-center gap-1">
                                                    <i class="fa-solid fa-download"></i> Unduh Tren
                                                </button>
                                            </div>
                                            <div class="relative h-44 sm:h-48 w-full">
                                                <canvas id="chartKehadiranTren"></canvas>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center gap-4 text-[10px] font-semibold pt-2 border-t border-slate-100 text-slate-600">
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Sakit</span>
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Izin</span>
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Alpa</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bar Chart per Siswa Diurutkan dari Alpa Terbanyak -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                        <div>
                                            <div class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                                <i class="fa-solid fa-chart-bar text-sky-600"></i>
                                                <span>Presensi Seluruh Siswa (Diurutkan dari Alpa Terbanyak)</span>
                                            </div>
                                            <p class="text-[10px] text-slate-500 mt-0.5">
                                                Siswa di urutan atas memiliki frekuensi alpa tertinggi. Siswa dengan alpa ≥ {{ $kh['batas_alpa'] ?? 3 }} hari diberi penanda merah tegas.
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3 text-[11px] font-bold">
                                            <span class="flex items-center gap-1 text-rose-600">
                                                <span class="w-3 h-3 rounded-sm bg-rose-500"></span> Alpa (A)
                                            </span>
                                            <span class="flex items-center gap-1 text-amber-600">
                                                <span class="w-3 h-3 rounded-sm bg-amber-500"></span> Izin (I)
                                            </span>
                                            <span class="flex items-center gap-1 text-blue-600">
                                                <span class="w-3 h-3 rounded-sm bg-blue-500"></span> Sakit (S)
                                            </span>
                                        </div>
                                    </div>

                                    @php
                                        $barHeight = max(240, count($kh['siswa_bar'] ?? []) * 24);
                                    @endphp
                                    <div class="relative w-full overflow-hidden" style="height: {{ $barHeight }}px;">
                                        <canvas id="chartKehadiranSiswa"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grafik 5: Capaian Karakter & Pembiasaan Siswa (Non-Akademik) -->
                        <div class="col-span-12 bg-slate-50/60 rounded-2xl p-4 sm:p-5 border border-emerald-200/80 flex flex-col justify-between"
                             x-show="activeTab === 'semua' || activeTab === 'pembiasaan'">
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                                    <div>
                                        <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider flex items-center flex-wrap gap-2">
                                            <span class="flex items-center"><i class="fa-solid fa-hands-praying text-emerald-600 mr-2"></i> Capaian Karakter &amp; Pembiasaan</span>
                                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">{{ $kelas->nama }}</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600 border border-slate-200">Non-Angka / Predikat</span>
                                        </h3>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Visualisasi berbasis predikat kualitatif (Sangat Baik, Baik, Cukup, Perlu Bimbingan) per dimensi profil pelajar dan monitoring siswa yang memerlukan bimbingan.
                                        </p>
                                    </div>
                                    <button type="button" onclick="downloadChart('chartPembiasaan', 'Grafik_Pembiasaan_{{ Str::slug($kelas->nama) }}_{{ $periode }}')"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs transition flex items-center gap-1.5"
                                            title="Unduh grafik capaian pembiasaan lengkap">
                                        <i class="fa-solid fa-download text-emerald-600"></i>
                                        <span>Unduh Gambar</span>
                                    </button>
                                </div>

                                @php
                                    $pem = $rekapData['chart_data']['pembiasaan'] ?? null;
                                    $siswaPB = $pem['siswa_perlu_bimbingan'] ?? [];
                                @endphp

                                <!-- Grid: Donat Distribusi Predikat & Bar per Aspek Pembiasaan -->
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 mb-4">
                                    <!-- Donut Distribusi Predikat Total -->
                                    <div class="lg:col-span-5 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 flex items-center justify-between mb-2">
                                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-pie text-emerald-600"></i> Distribusi Predikat Seluruh Aspek</span>
                                                <span class="text-[10px] text-slate-400">Total: {{ $pem['totals']['total'] ?? 0 }} Nilai</span>
                                            </div>
                                            <div class="relative h-44 sm:h-48 w-full flex items-center justify-center">
                                                <canvas id="chartPembiasaanDonut"></canvas>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[11px] pt-3 mt-2 border-t border-slate-100">
                                            <div class="p-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-center">
                                                <span class="text-[9px] text-emerald-800 font-bold block">Sangat Baik</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $pem['totals']['sangat_baik'] ?? 0 }}</span>
                                                <span class="text-[9px] text-slate-400">({{ $pem['percentages']['sangat_baik'] ?? 0 }}%)</span>
                                            </div>
                                            <div class="p-1.5 rounded-lg bg-blue-50 border border-blue-100 text-center">
                                                <span class="text-[9px] text-blue-800 font-bold block">Baik</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $pem['totals']['baik'] ?? 0 }}</span>
                                                <span class="text-[9px] text-slate-400">({{ $pem['percentages']['baik'] ?? 0 }}%)</span>
                                            </div>
                                            <div class="p-1.5 rounded-lg bg-amber-50 border border-amber-100 text-center">
                                                <span class="text-[9px] text-amber-800 font-bold block">Cukup</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $pem['totals']['cukup'] ?? 0 }}</span>
                                                <span class="text-[9px] text-slate-400">({{ $pem['percentages']['cukup'] ?? 0 }}%)</span>
                                            </div>
                                            <div class="p-1.5 rounded-lg bg-rose-50 border border-rose-100 text-center">
                                                <span class="text-[9px] text-rose-800 font-bold block">Perlu Bimbingan</span>
                                                <span class="font-black text-slate-900 text-xs">{{ $pem['totals']['perlu_bimbingan'] ?? 0 }}</span>
                                                <span class="text-[9px] text-slate-400">({{ $pem['percentages']['perlu_bimbingan'] ?? 0 }}%)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bar Chart per Aspek Dimensi Pembiasaan -->
                                    <div class="lg:col-span-7 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs flex flex-col justify-between">
                                        <div>
                                            <div class="text-xs font-bold text-slate-800 flex items-center justify-between mb-2">
                                                <span class="flex items-center gap-1.5"><i class="fa-solid fa-chart-column text-emerald-600"></i> Sebaran Predikat per Dimensi Profil Pelajar</span>
                                            </div>
                                            <div class="relative h-44 sm:h-48 w-full">
                                                <canvas id="chartPembiasaan"></canvas>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center gap-3 text-[10px] font-semibold pt-2 border-t border-slate-100 text-slate-600">
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Sangat Baik</span>
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Baik</span>
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Cukup</span>
                                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Perlu Bimbingan</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Box Khusus: Daftar Siswa "Perlu Bimbingan" -->
                                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                                    <div class="flex items-center justify-between mb-2.5">
                                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <i class="fa-solid fa-user-pen text-rose-600"></i>
                                            <span>Daftar Siswa "Perlu Bimbingan" (Monitoring Intervensi Karakter)</span>
                                        </h4>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ count($siswaPB) > 0 ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                            {{ count($siswaPB) }} Siswa Memerlukan Bimbingan
                                        </span>
                                    </div>

                                    @if(count($siswaPB) > 0)
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-xs text-left">
                                                <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                                                    <tr>
                                                        <th class="py-2 px-2.5 w-10 text-center">No</th>
                                                        <th class="py-2 px-3 w-48">Nama Siswa</th>
                                                        <th class="py-2 px-3">Dimensi Pembiasaan yang Perlu Dibimbing</th>
                                                        <th class="py-2 px-3">Rekomendasi Tindak Lanjut Wali Kelas</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach($siswaPB as $idx => $spb)
                                                        <tr class="hover:bg-rose-50/40 transition">
                                                            <td class="py-2 px-2.5 text-center font-mono text-slate-500">{{ $idx + 1 }}</td>
                                                            <td class="py-2 px-3 font-bold text-slate-900">
                                                                {{ $spb['nama'] }}
                                                                <span class="block text-[10px] font-normal text-slate-400 font-mono">NIS: {{ $spb['nis'] }}</span>
                                                            </td>
                                                            <td class="py-2 px-3">
                                                                <div class="flex flex-wrap gap-1">
                                                                    @foreach($spb['aspek'] as $asp)
                                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                                            {{ $asp }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </td>
                                                            <td class="py-2 px-3 text-slate-600 text-[11px] leading-relaxed">
                                                                {{ $spb['saran'] }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2 font-medium">
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                                            <span>Seluruh siswa pada kelas ini telah mencapai predikat <strong>Baik</strong> atau <strong>Sangat Baik</strong> pada semua dimensi pembiasaan. Tidak ada peserta didik yang memerlukan bimbingan khusus.</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Tampilan Jika Belum Ada Data Nilai -->
                    <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-300">
                        <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl mb-3 shadow-2xs">
                            <i class="fa-solid fa-chart-simple"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">
                            Grafik Belum Tersedia
                        </h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4 leading-relaxed">
                            Grafik performa mata pelajaran, distribusi capaian, dan peringkat akan otomatis muncul begitu nilai siswa pada periode <strong>{{ strtoupper($periode) }}</strong> mulai diinputkan.
                        </p>
                        <a href="{{ route('kelas.show', $kelas->id) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Buka Menu Input Nilai</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden rekap-print-box">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3 no-print">
            <div>
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-table-list text-slate-600 mr-2"></i> Rekapitulasi Nilai Sumatif Peserta Didik
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Seluruh nilai mata pelajaran dan Ranking 1 s.d. terakhir tampil lengkap di layar dan pas 1 lembar saat dicetak.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Opsi Urutan Tabel -->
                <div class="inline-flex rounded-xl p-1 bg-slate-100 text-xs">
                    <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => $periode, 'sort' => 'ranking']) }}"
                       class="px-2.5 py-1 rounded-lg font-bold transition flex items-center gap-1.5 {{ $sortBy === 'ranking' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                       title="Urutkan dari ranking 1 sampai terakhir">
                        <i class="fa-solid fa-arrow-down-wide-short text-amber-500"></i>
                        <span>Ranking Tertinggi</span>
                    </a>
                    <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => $periode, 'sort' => 'nama']) }}"
                       class="px-2.5 py-1 rounded-lg font-bold transition flex items-center gap-1.5 {{ $sortBy === 'nama' ? 'bg-white text-blue-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}"
                       title="Urutkan berdasarkan alfabet nama siswa">
                        <i class="fa-solid fa-arrow-down-a-z text-slate-400"></i>
                        <span>Nama (A-Z)</span>
                    </a>
                </div>

                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-full uppercase">
                    Periode {{ strtoupper($periode) }}
                </span>
            </div>
        </div>

        <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 font-bold uppercase text-slate-700">
                    <tr>
                        <th class="py-2.5 px-1.5 w-8 text-center border-r border-slate-200/60 print:w-[3%]">No</th>
                        <th class="py-2.5 px-2.5 min-w-[140px] max-w-[200px] border-r border-slate-200/60 text-left sticky left-0 bg-slate-50 z-10 print:static print:w-[19%] print:text-left print:px-1">Nama Siswa</th>
                        <th class="py-2.5 px-1.5 w-16 border-r border-slate-200/60 text-center font-mono print:w-[8%]">NIS</th>
                        @foreach($rekapData['mapels'] as $m)
                            <th class="py-2 px-1 text-center min-w-[58px] max-w-[85px] align-top border-r border-slate-200/60 whitespace-normal print:w-[6%]" title="{{ $m->nama }} (KKM: {{ $kelas->getKkmForMapel($m) }})">
                                <div class="text-[10px] print:text-[6.2pt] font-black text-slate-800 leading-tight">
                                    {{ $m->nama_singkat }}
                                </div>
                                <div class="text-[9px] print:text-[5.5pt] text-slate-400 print:text-black font-semibold mt-0.5">
                                    {{ $kelas->getKkmForMapel($m) }}
                                </div>
                            </th>
                        @endforeach
                        <th class="py-2.5 px-1 text-center bg-slate-100 font-black min-w-[52px] border-r border-slate-200/60 print:w-[6%]">Jumlah</th>
                        <th class="py-2.5 px-1 text-center bg-blue-50 text-blue-900 font-black min-w-[50px] border-r border-slate-200/60 print:w-[5%]">Rata²</th>
                        <th class="py-2.5 px-1 text-center bg-amber-50 text-amber-900 font-black min-w-[48px] print:w-[5%]">
                            <div class="flex items-center justify-center space-x-0.5">
                                <i class="fa-solid fa-trophy text-amber-500 no-print text-[10px] mr-0.5"></i>
                                <span>Rank</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekapData['siswa_list'] as $idx => $item)
                        @php
                            $s = $item['siswa'];
                            $h = $item['hasil'];
                            $rank = $item['peringkat'] ?? ($idx + 1);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $rank === 1 ? 'bg-amber-50/40' : ($rank === 2 ? 'bg-slate-50/50' : ($rank === 3 ? 'bg-orange-50/20' : '')) }}">
                            <td class="py-2 px-1 text-center text-slate-500 font-bold border-r border-slate-100 print:text-black">{{ $idx + 1 }}</td>
                            <td class="py-2 px-2.5 font-bold text-slate-900 whitespace-nowrap border-r border-slate-100 text-left sticky left-0 bg-white z-10 print:static print:text-black print:px-1 {{ $rank === 1 ? '!bg-amber-50/90' : ($rank === 2 ? '!bg-slate-50/90' : ($rank === 3 ? '!bg-orange-50/70' : '')) }}">
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $item['kelas_siswa']->id, 'periode' => $periode]) }}" class="hover:text-emerald-600 transition truncate max-w-[170px]" title="{{ $s->nama }}">
                                        {{ $s->nama }}
                                    </a>
                                    @if($rank === 1)
                                        <span class="no-print" title="Ranking 1">🥇</span>
                                    @elseif($rank === 2)
                                        <span class="no-print" title="Ranking 2">🥈</span>
                                    @elseif($rank === 3)
                                        <span class="no-print" title="Ranking 3">🥉</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-2 px-1 font-mono text-center text-slate-600 border-r border-slate-100 print:text-black">{{ $s->nis }}</td>
                            @foreach($h['baris'] as $b)
                                <td class="py-2 px-1 text-center font-semibold border-r border-slate-100 {{ $b['sumatif'] !== null ? 'text-slate-800' : 'text-slate-300' }} print:text-black">
                                    {{ $b['sumatif_format'] }}
                                </td>
                            @endforeach
                            <td class="py-2 px-1 text-center font-black bg-slate-100/60 text-slate-900 border-r border-slate-100 print:bg-transparent print:text-black">
                                {{ \App\Services\HitungNilaiService::formatAngka($h['total_sumatif']) }}
                            </td>
                            <td class="py-2 px-1 text-center font-black bg-blue-50/60 text-blue-900 border-r border-slate-100 print:bg-transparent print:text-black">
                                {{ $h['rata_sumatif_format'] }}
                            </td>
                            <td class="py-2 px-1 text-center whitespace-nowrap bg-amber-50/40 print:bg-transparent">
                                @if($rank === 1)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-amber-950 font-black text-[11px] shadow-2xs print:border-none print:bg-transparent print:text-black print:text-[7pt]">
                                        1
                                    </span>
                                @elseif($rank === 2)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-200 text-slate-800 font-black text-[11px] shadow-2xs print:border-none print:bg-transparent print:text-black print:text-[7pt]">
                                        2
                                    </span>
                                @elseif($rank === 3)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-900 font-black text-[11px] shadow-2xs print:border-none print:bg-transparent print:text-black print:text-[7pt]">
                                        3
                                    </span>
                                @else
                                    <span class="font-bold text-slate-700 print:text-black print:text-[7pt]">
                                        {{ $rank }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $rekapData['mapels']->count() + 6 }}" class="py-6 text-center text-slate-400">
                                Belum ada siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300 print:bg-transparent">
                    <tr>
                        <td colspan="3" class="py-2 px-2 text-right uppercase border-r border-slate-200/80 font-black">Rata-rata Kelas:</td>
                        @foreach($rekapData['mapels'] as $m)
                            <td class="py-2 px-1 text-center text-blue-900 font-black border-r border-slate-200/80 print:text-black">
                                {{ $rekapData['rekap_mapel'][$m->id]['rata_rata_format'] }}
                            </td>
                        @endforeach
                        <td class="py-2 px-1 text-center bg-slate-200/50 border-r border-slate-200/80 print:bg-transparent">-</td>
                        <td class="py-2 px-1 text-center bg-blue-100 text-blue-900 font-black border-r border-slate-200/80 print:bg-transparent print:text-black">
                            {{ $rekapData['rata_rata_kelas_format'] }}
                        </td>
                        <td class="py-2 px-1 text-center bg-amber-100/40 text-amber-900 font-black print:bg-transparent">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Keterangan & KKM Mapel (Tampil di Web) -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 text-xs text-slate-600 no-print">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                <span class="font-bold text-slate-700">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i> Keterangan Mata Pelajaran & KKM:
                </span>
                <span class="text-[11px] text-slate-500 italic">
                    * Ranking 1 sampai terakhir ditentukan berdasarkan total akumulasi nilai sumatif seluruh mata pelajaran.
                </span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 text-[11px]">
                @foreach($rekapData['mapels'] as $m)
                    <div class="p-1.5 rounded-lg bg-white border border-slate-200/70">
                        <strong class="text-slate-800">{{ $m->nama_singkat }}:</strong>
                        <span class="text-slate-600">{{ $m->nama }}</span>
                        <span class="text-emerald-700 font-semibold font-mono ml-1">(KKM {{ $kelas->getKkmForMapel($m) }})</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Kolom Tanda Tangan Pas 1 Lembar Cetak -->
        <div class="pt-2 px-6 pb-0 bg-white print-only">
            <div class="flex justify-between items-start text-[7.5pt] text-black">
                <div class="text-center w-52">
                    <p>Mengetahui,</p>
                    <p class="font-bold">Kepala Sekolah</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline">{{ $kelas->sekolah->kepsek ?? '....................................' }}</p>
                    <p>NIP. {{ $kelas->sekolah->nip ?? '....................................' }}</p>
                </div>
                <div class="text-center w-52">
                    <p>{{ $kelas->sekolah->kota ?? 'Banyuwangi' }}, <span id="labelTanggalRekap" class="label-tanggal-rekap">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                    <p class="font-bold">Wali Kelas {{ $kelas->nama }}</p>
                    <div class="h-9"></div>
                    <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                    <p>NIP. {{ $kelas->nip_wali }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Nama dan NIP Wali Kelas -->
    <div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden no-print">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100 text-left">
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
                    <span class="text-[11px] text-slate-500 mt-1 block">Nama ini akan dicetak pada lembar rekap dan raport.</span>
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

    <!-- Script Manajemen Ganti Tanggal Rekap / Raport Real-time & Auto-Save -->
    <script>
        const inputTanggal = document.getElementById('inputTanggalRekap');
        const badgeStatus = document.getElementById('badgeStatusTanggalRekap');
        const btnCetakRaport = document.getElementById('btnCetakRaportSekelas');
        const btnPdf = document.getElementById('btnPdfSekelas');

        function formatTanggalIndo(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            const bulanIndo = [
                '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return `${day} ${bulanIndo[month] || ''} ${year}`;
        }

        function updateRekapUrls(val) {
            if (btnCetakRaport) {
                const url = new URL(btnCetakRaport.href, window.location.origin);
                url.searchParams.set('tanggal', val);
                btnCetakRaport.href = url.pathname + url.search;
            }
            if (btnPdf) {
                const url = new URL(btnPdf.href, window.location.origin);
                url.searchParams.set('tanggal', val);
                btnPdf.href = url.pathname + url.search;
            }
        }

        function onTanggalChanged() {
            const val = inputTanggal.value;
            if (!val) return;
            const formatted = formatTanggalIndo(val);
            document.querySelectorAll('.label-tanggal-rekap').forEach(el => {
                el.textContent = formatted;
            });
            updateRekapUrls(val);
        }

        function simpanTanggalRekap() {
            const val = inputTanggal.value;
            if (!val) {
                alert('Silakan pilih tanggal terlebih dahulu.');
                return;
            }

            if (badgeStatus) {
                badgeStatus.textContent = 'Menyimpan...';
                badgeStatus.classList.remove('hidden', 'text-rose-600');
                badgeStatus.classList.add('text-slate-500');
            }

            fetch('{{ route("kelas.tanggal-raport.update", $kelas->id) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    periode: '{{ $periode }}',
                    tanggal: val
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (badgeStatus) {
                        badgeStatus.textContent = '✓ Tersimpan';
                        badgeStatus.classList.remove('text-slate-500', 'text-rose-600');
                        badgeStatus.classList.add('text-emerald-600');
                        setTimeout(() => {
                            badgeStatus.classList.add('hidden');
                        }, 3500);
                    }
                } else {
                    throw new Error(data.message || 'Gagal menyimpan');
                }
            })
            .catch(err => {
                if (badgeStatus) {
                    badgeStatus.textContent = 'Gagal menyimpan';
                    badgeStatus.classList.remove('text-slate-500', 'text-emerald-600');
                    badgeStatus.classList.add('text-rose-600');
                }
            });
        }

        if (inputTanggal) {
            inputTanggal.addEventListener('change', () => {
                onTanggalChanged();
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    simpanTanggalRekap();
                @endif
            });
        }
    </script>
</div>
@endsection

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const rawChartData = @json($rekapData['chart_data'] ?? null);
    const metaSekolah = {
        namaSekolah: @json($kelas->sekolah->nama ?? 'SD NEGERI 2 KARANGSARI'),
        alamatSekolah: @json($kelas->sekolah->alamat ?? ''),
        namaKelas: @json($kelas->nama ?? 'Kelas'),
        tahunAjaran: @json($kelas->tahunAjaran->nama_lengkap ?? '-'),
        semester: @json(ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil')),
        periode: @json(strtoupper($periode)),
        periodeKet: @json($periode === 'sts' ? 'Sumatif Tengah Semester (STS)' : 'Sumatif Akhir Semester (SAS)'),
        waliKelas: @json($kelas->nama_wali ?? '-'),
        nipWali: @json($kelas->nip_wali ?? '-'),
        rataRataKelas: @json($rekapData['rata_rata_kelas_format'] ?? '-'),
        totalSiswa: @json($rekapData['jumlah_siswa'] ?? 0),
        siswaSelesai: @json($rekapData['siswa_selesai'] ?? 0),
        persenSelesai: @json($rekapData['persen_selesai'] ?? 0),
        tanggal: @json($tanggalObj ? $tanggalObj->translatedFormat('d F Y') : now()->locale('id')->translatedFormat('d F Y')),
    };
    let chartMapelInstance = null;
    let chartDistribusiInstance = null;
    let chartRankingInstance = null;
    let chartPerbandinganInstance = null;
    let chartKehadiranInstance = null;
    let chartKehadiranTrenInstance = null;
    let chartKehadiranSiswaInstance = null;
    let chartPembiasaanDonutInstance = null;
    let chartPembiasaanInstance = null;
    let currentActiveAkademik = (rawChartData && rawChartData.akademik && rawChartData.akademik.rapor) ? rawChartData.akademik.rapor : rawChartData;

    function initRekapCharts() {
        if (!rawChartData || !rawChartData.has_data || typeof Chart === 'undefined') {
            return;
        }

        Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#475569';

        // 1. Inisialisasi Grafik Mapel vs KKM (Bar Chart + Garis KKM)
        const ctxMapel = document.getElementById('chartMapel');
        if (ctxMapel) {
            const mapelData = rawChartData.mapel;
            const bgColors = mapelData.averages.map((avg, i) => {
                const kkm = mapelData.kkm[i] || 70;
                return avg >= kkm ? 'rgba(16, 185, 129, 0.85)' : 'rgba(239, 68, 68, 0.85)';
            });
            const borderColors = mapelData.averages.map((avg, i) => {
                const kkm = mapelData.kkm[i] || 70;
                return avg >= kkm ? '#059669' : '#dc2626';
            });

            if (chartMapelInstance) {
                chartMapelInstance.destroy();
            }

            chartMapelInstance = new Chart(ctxMapel, {
                type: 'bar',
                data: {
                    labels: mapelData.labels,
                    datasets: [
                        {
                            type: 'line',
                            label: 'Standar KKM',
                            data: mapelData.kkm,
                            borderColor: '#f59e0b',
                            backgroundColor: '#fef3c7',
                            borderWidth: 2.5,
                            borderDash: [5, 5],
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#f59e0b',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            fill: false,
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Rata-rata Kelas',
                            data: mapelData.averages,
                            backgroundColor: bgColors,
                            borderColor: borderColors,
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 45,
                            barPercentage: 0.72,
                            categoryPercentage: 0.75,
                            order: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                stepSize: 20
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    weight: 'bold',
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 },
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return (mapelData.full_names && mapelData.full_names[idx]) ? mapelData.full_names[idx] : mapelData.labels[idx];
                                },
                                label: function(item) {
                                    const val = item.parsed.y;
                                    return ` ${item.dataset.label}: ${val}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Inisialisasi Grafik Distribusi Capaian Siswa (Doughnut Chart)
        const ctxDist = document.getElementById('chartDistribusi');
        if (ctxDist) {
            const distData = rawChartData.distribusi;

            if (chartDistribusiInstance) {
                chartDistribusiInstance.destroy();
            }

            chartDistribusiInstance = new Chart(ctxDist, {
                type: 'doughnut',
                data: {
                    labels: distData.labels,
                    datasets: [{
                        data: distData.counts,
                        backgroundColor: distData.colors,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(item) {
                                    const total = distData.total_dinilai || 1;
                                    const val = item.raw || 0;
                                    const pct = ((val / total) * 100).toFixed(1);
                                    return ` ${val} Siswa (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Inisialisasi Grafik Rentang Nilai Siswa (0-25, 26-50, 51-75, 76-100)
        renderRankingChart();

        // 4. Inisialisasi Grafik Perbandingan Nilai (Formatif, UTS, UAS, Nilai Rapor Berdampingan)
        initPerbandinganChart();

        // 5. Inisialisasi Grafik Kehadiran Siswa (SIA) - Donut, Tren Bulanan, Presensi Siswa
        initKehadiranChart();
        initKehadiranTrenChart();
        initKehadiranSiswaChart();

        // 6. Inisialisasi Grafik Capaian Pembiasaan Siswa - Donut & Bar per Aspek
        initPembiasaanDonutChart();
        initPembiasaanChart();
    }

    let currentRentangMode = 'semua';

    window.switchRentangMapel = function(val) {
        currentRentangMode = val;
        const select = document.getElementById('selectMapelRentang');
        if (select && select.value !== val) {
            select.value = val;
        }
        renderRankingChart();
    };

    function updateRentangCards(counts, pcts, unit) {
        for (let i = 0; i < 4; i++) {
            const countEl = document.getElementById(`rentang-card-${i}-count`);
            const pctEl = document.getElementById(`rentang-card-${i}-pct`);
            if (countEl) countEl.innerHTML = `${counts[i] || 0} <span class="text-xs font-semibold text-slate-500">${unit}</span>`;
            if (pctEl) pctEl.textContent = `${pcts[i] || 0}%`;
        }
    }

    function renderStudentDetailList(siswaGroups, valueKey) {
        const detailContainer = document.getElementById('rentang-siswa-detail');
        if (!detailContainer) return;

        if (!siswaGroups || (!siswaGroups[0]?.length && !siswaGroups[1]?.length && !siswaGroups[2]?.length && !siswaGroups[3]?.length)) {
            detailContainer.classList.add('hidden');
            detailContainer.innerHTML = '';
            return;
        }

        const ranges = [
            { label: '0 - 25 (Perlu Intervensi)', color: 'border-rose-200 bg-rose-50/70 text-rose-800', dot: 'bg-rose-500', list: siswaGroups[0] || [] },
            { label: '26 - 50 (Perlu Bimbingan)', color: 'border-amber-200 bg-amber-50/70 text-amber-800', dot: 'bg-orange-500', list: siswaGroups[1] || [] },
            { label: '51 - 75 (Cukup)', color: 'border-blue-200 bg-blue-50/70 text-blue-800', dot: 'bg-blue-500', list: siswaGroups[2] || [] },
            { label: '76 - 100 (Baik - Sangat Baik)', color: 'border-emerald-200 bg-emerald-50/70 text-emerald-800', dot: 'bg-emerald-500', list: siswaGroups[3] || [] },
        ];

        let html = '<div class="font-extrabold text-slate-800 mb-2 flex items-center gap-1.5"><i class="fa-solid fa-users text-blue-600"></i> Rincian Peserta Didik per Rentang Nilai:</div><div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">';

        ranges.forEach(r => {
            html += `<div class="p-2.5 rounded-xl border ${r.color}">
                <div class="font-extrabold text-[11px] mb-1.5 flex items-center justify-between">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full ${r.dot}"></span> ${r.label}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-white shadow-2xs">${r.list.length} Siswa</span>
                </div>`;
            if (r.list.length > 0) {
                html += '<ul class="space-y-1 max-h-36 overflow-y-auto pr-1">';
                r.list.forEach(s => {
                    const score = valueKey === 'rata_rata' ? s.rata_rata_format : (s.nilai_format ?? s.rata_rata_format);
                    html += `<li class="flex items-center justify-between text-[11px] bg-white/80 px-2 py-1 rounded-md border border-slate-200/50">
                        <span class="truncate max-w-[130px] font-semibold text-slate-800" title="${s.nama}">${s.nama}</span>
                        <span class="font-mono font-black text-slate-900 ml-1.5">${score}</span>
                    </li>`;
                });
                html += '</ul>';
            } else {
                html += '<div class="text-[10px] italic text-slate-400 py-1 text-center">Tidak ada siswa</div>';
            }
            html += '</div>';
        });

        html += '</div>';
        detailContainer.innerHTML = html;
        detailContainer.classList.remove('hidden');
    }

    function renderRankingChart() {
        const ctxRanking = document.getElementById('chartRanking');
        if (!ctxRanking || !rawChartData) return;

        const rentang = rawChartData.rentang_nilai || {};
        const wrapper = document.getElementById('rankingCanvasWrapper');
        if (wrapper) {
            if (currentRentangMode === 'semua') {
                wrapper.style.height = '260px';
                wrapper.classList.remove('max-w-2xl', 'max-w-xl');
                wrapper.classList.add('max-w-4xl');
            } else {
                wrapper.style.height = '230px';
                wrapper.classList.remove('max-w-4xl');
                wrapper.classList.add('max-w-2xl');
            }
        }

        if (chartRankingInstance) {
            chartRankingInstance.destroy();
        }

        const scopeLabel = document.getElementById('rentang-scope-label');
        const scopeHint = document.getElementById('rentang-sub-hint');
        const detailContainer = document.getElementById('rentang-siswa-detail');

        if (currentRentangMode === 'semua') {
            // Mode 1: Komparasi Bertumpuk Semua Mata Pelajaran (Stacked Bar Chart)
            const mapelLabels = rentang.mapel_labels || [];
            const fullNames = rentang.mapel_full_names || [];
            const ds = rentang.datasets_by_mapel || {};

            chartRankingInstance = new Chart(ctxRanking, {
                type: 'bar',
                data: {
                    labels: mapelLabels,
                    datasets: [
                        {
                            label: '0 - 25 (Perlu Intervensi)',
                            data: ds.r1_0_25 || [],
                            backgroundColor: 'rgba(239, 68, 68, 0.9)',
                            borderColor: '#dc2626',
                            borderWidth: 1,
                            borderRadius: 4,
                            maxBarThickness: 42,
                            barPercentage: 0.72,
                            categoryPercentage: 0.75
                        },
                        {
                            label: '26 - 50 (Perlu Bimbingan)',
                            data: ds.r2_26_50 || [],
                            backgroundColor: 'rgba(249, 115, 22, 0.9)',
                            borderColor: '#ea580c',
                            borderWidth: 1,
                            borderRadius: 4,
                            maxBarThickness: 42,
                            barPercentage: 0.72,
                            categoryPercentage: 0.75
                        },
                        {
                            label: '51 - 75 (Cukup)',
                            data: ds.r3_51_75 || [],
                            backgroundColor: 'rgba(59, 130, 246, 0.9)',
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            borderRadius: 4,
                            maxBarThickness: 42,
                            barPercentage: 0.72,
                            categoryPercentage: 0.75
                        },
                        {
                            label: '76 - 100 (Baik - Sangat Baik)',
                            data: ds.r4_76_100 || [],
                            backgroundColor: 'rgba(16, 185, 129, 0.9)',
                            borderColor: '#059669',
                            borderWidth: 1,
                            borderRadius: 4,
                            maxBarThickness: 42,
                            barPercentage: 0.72,
                            categoryPercentage: 0.75
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 4, bottom: 2, left: 6, right: 6 }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: 'bold' } }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                callback: function(val) { return val + ' Siswa'; }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                usePointStyle: true,
                                font: { size: 11, weight: '600' },
                                padding: 14
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return fullNames[idx] ? fullNames[idx] : mapelLabels[idx];
                                },
                                label: function(item) {
                                    const rNames = ['0 - 25 (Perlu Intervensi)', '26 - 50 (Perlu Bimbingan)', '51 - 75 (Cukup)', '76 - 100 (Baik - Sangat Baik)'];
                                    const rTitle = rNames[item.datasetIndex] || item.dataset.label;
                                    return ` ${rTitle}: ${item.raw} Siswa`;
                                },
                                afterBody: function(items) {
                                    if (!items.length) return '';
                                    const mIdx = items[0].dataIndex;
                                    const mapelKeys = Object.keys(rentang.per_mapel || {});
                                    const mId = mapelKeys[mIdx];
                                    const mData = mId ? rentang.per_mapel[mId] : null;
                                    if (!mData) return '';
                                    return `(Total Dinilai: ${mData.total_dinilai} Siswa • KKM: ${mData.kkm})\n👉 Klik batang untuk melihat rincian per mapel`;
                                }
                            }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (elements.length > 0) {
                            const index = elements[0].index;
                            const mapelKeys = Object.keys(rentang.per_mapel || {});
                            if (mapelKeys[index]) {
                                window.switchRentangMapel(mapelKeys[index]);
                            }
                        }
                    }
                }
            });

            // Update 4 kartu dengan total kumulatif nilai mapel
            const km = rentang.kumulatif_mapel || { counts: [0,0,0,0], percentages: [0,0,0,0], total_nilai: 0 };
            updateRentangCards(km.counts, km.percentages, 'Nilai');
            if (scopeLabel) {
                scopeLabel.innerHTML = '<i class="fa-solid fa-layer-group text-blue-600"></i> Menampilkan: <strong>Semua Mata Pelajaran (Komparasi Bertumpuk)</strong>';
            }
            if (scopeHint) {
                scopeHint.textContent = '💡 Klik batang mapel pada grafik atau pilih menu dropdown untuk rincian per mapel';
            }
            if (detailContainer) {
                detailContainer.classList.add('hidden');
                detailContainer.innerHTML = '';
            }

        } else if (currentRentangMode === 'rata_rata') {
            // Mode 2: Rata-rata Nilai Akhir Siswa
            const counts = rentang.counts || [0,0,0,0];
            const pcts = rentang.percentages || [0,0,0,0];
            const fullLabels = rentang.full_labels || ['Rentang 0 - 25', 'Rentang 26 - 50', 'Rentang 51 - 75', 'Rentang 76 - 100'];

            chartRankingInstance = new Chart(ctxRanking, {
                type: 'bar',
                data: {
                    labels: fullLabels,
                    datasets: [{
                        label: 'Jumlah Siswa',
                        data: counts,
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.85)',
                            'rgba(249, 115, 22, 0.85)',
                            'rgba(59, 130, 246, 0.85)',
                            'rgba(16, 185, 129, 0.85)'
                        ],
                        borderColor: ['#dc2626', '#ea580c', '#2563eb', '#059669'],
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 54,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 6, bottom: 2, left: 8, right: 8 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                callback: function(val) { return val + ' Siswa'; }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return fullLabels[idx] + (rentang.keterangan ? ' (' + rentang.keterangan[idx] + ')' : '');
                                },
                                label: function(item) {
                                    return ` Jumlah: ${item.raw} Siswa (${pcts[item.dataIndex] || 0}%)`;
                                },
                                afterLabel: function(item) {
                                    const idx = item.dataIndex;
                                    const sList = (rentang.siswa && rentang.siswa[idx]) ? rentang.siswa[idx] : [];
                                    if (!sList || sList.length === 0) return ' Tidak ada siswa di rentang ini';
                                    const names = sList.slice(0, 5).map(s => s.nama + ' (' + s.rata_rata_format + ')');
                                    let res = ' Siswa: ' + names.join(', ');
                                    if (sList.length > 5) res += ' +' + (sList.length - 5) + ' lainnya';
                                    return res;
                                }
                            }
                        }
                    }
                }
            });

            updateRentangCards(counts, pcts, 'Anak');
            if (scopeLabel) {
                scopeLabel.innerHTML = '<i class="fa-solid fa-bullseye text-blue-600"></i> Menampilkan: <strong>Rata-rata Nilai Akhir Siswa Keseluruhan</strong>';
            }
            if (scopeHint) {
                scopeHint.textContent = '💡 Berdasarkan nilai rata-rata akhir siswa pada seluruh mata pelajaran';
            }
            renderStudentDetailList(rentang.siswa, 'rata_rata');

        } else {
            // Mode 3: Spesifik per Mata Pelajaran
            const mId = currentRentangMode;
            const mData = (rentang.per_mapel && rentang.per_mapel[mId]) ? rentang.per_mapel[mId] : null;
            if (!mData) return;

            const counts = mData.counts || [0,0,0,0];
            const pcts = mData.percentages || [0,0,0,0];
            const fullLabels = ['Rentang 0 - 25', 'Rentang 26 - 50', 'Rentang 51 - 75', 'Rentang 76 - 100'];

            chartRankingInstance = new Chart(ctxRanking, {
                type: 'bar',
                data: {
                    labels: fullLabels,
                    datasets: [{
                        label: 'Jumlah Siswa',
                        data: counts,
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.85)',
                            'rgba(249, 115, 22, 0.85)',
                            'rgba(59, 130, 246, 0.85)',
                            'rgba(16, 185, 129, 0.85)'
                        ],
                        borderColor: ['#dc2626', '#ea580c', '#2563eb', '#059669'],
                        borderWidth: 1.5,
                        borderRadius: 6,
                        maxBarThickness: 54,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: { top: 6, bottom: 2, left: 8, right: 8 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                callback: function(val) { return val + ' Siswa'; }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                title: function(items) {
                                    const idx = items[0].dataIndex;
                                    return mData.nama + ' • ' + fullLabels[idx];
                                },
                                label: function(item) {
                                    return ` Jumlah: ${item.raw} Siswa (${pcts[item.dataIndex] || 0}%)`;
                                },
                                afterLabel: function(item) {
                                    const idx = item.dataIndex;
                                    const sList = (mData.siswa && mData.siswa[idx]) ? mData.siswa[idx] : [];
                                    if (!sList || sList.length === 0) return ' Tidak ada siswa';
                                    const names = sList.slice(0, 6).map(s => s.nama + ' (' + s.nilai_format + ')');
                                    let res = ' Siswa: ' + names.join(', ');
                                    if (sList.length > 6) res += ' +' + (sList.length - 6) + ' lainnya';
                                    return res;
                                }
                            }
                        }
                    }
                }
            });

            updateRentangCards(counts, pcts, 'Anak');
            if (scopeLabel) {
                scopeLabel.innerHTML = `<i class="fa-solid fa-book-open text-blue-600"></i> Menampilkan Mapel: <strong>${mData.nama}</strong> <span class="text-[10px] bg-blue-100 text-blue-800 font-extrabold px-1.5 py-0.5 rounded ml-1 border border-blue-200">KKM: ${mData.kkm}</span> • Total ${mData.total_dinilai} Siswa Dinilai`;
            }
            if (scopeHint) {
                scopeHint.textContent = `💡 Distribusi nilai khusus mapel ${mData.nama}. Pilih 'Semua Mapel' di dropdown untuk kembali ke komparasi bertumpuk.`;
            }
            renderStudentDetailList(mData.siswa, 'nilai');
        }
    }

    window.setRankingFilter = function(val) {
        renderRankingChart();
    };

    function initKehadiranChart() {
        const ctxKehadiran = document.getElementById('chartKehadiran');
        if (!ctxKehadiran || !rawChartData || !rawChartData.kehadiran) return;

        const khData = rawChartData.kehadiran;
        if (chartKehadiranInstance) {
            chartKehadiranInstance.destroy();
        }

        const isZero = (khData.total_tidak_hadir === 0);

        chartKehadiranInstance = new Chart(ctxKehadiran, {
            type: 'doughnut',
            data: {
                labels: isZero ? ['Hadir 100% (Nol Absen)'] : khData.labels,
                datasets: [{
                    data: isZero ? [1] : khData.counts,
                    backgroundColor: isZero ? ['#10b981'] : khData.colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(item) {
                                if (isZero) return ' Seluruh siswa hadir penuh (0 hari absen)';
                                const val = item.raw || 0;
                                const pct = (khData.percentages && khData.percentages[item.dataIndex]) ? khData.percentages[item.dataIndex] : 0;
                                return ` ${val} Hari (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    function initPembiasaanChart() {
        const ctxPembiasaan = document.getElementById('chartPembiasaan');
        if (!ctxPembiasaan || !rawChartData || !rawChartData.pembiasaan) return;

        const pemData = rawChartData.pembiasaan;
        if (chartPembiasaanInstance) {
            chartPembiasaanInstance.destroy();
        }

        chartPembiasaanInstance = new Chart(ctxPembiasaan, {
            type: 'bar',
            data: {
                labels: pemData.labels,
                datasets: [
                    {
                        label: 'Sangat Baik',
                        data: pemData.datasets.sangat_baik,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#059669',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 36,
                        barPercentage: 0.72,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Baik',
                        data: pemData.datasets.baik,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 36,
                        barPercentage: 0.72,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Cukup',
                        data: pemData.datasets.cukup,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#d97706',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 36,
                        barPercentage: 0.72,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Perlu Bimbingan',
                        data: pemData.datasets.perlu_bimbingan,
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: '#dc2626',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 36,
                        barPercentage: 0.72,
                        categoryPercentage: 0.75
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: {
                            font: { size: 10, weight: 'bold' },
                            maxRotation: 25,
                            minRotation: 0,
                            callback: function(val, index) {
                                const label = this.getLabelForValue(val);
                                return label && label.length > 15 ? label.substr(0, 15) + '...' : label;
                            }
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 5 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                return (pemData.full_names && pemData.full_names[idx]) ? pemData.full_names[idx] : pemData.labels[idx];
                            },
                            label: function(item) {
                                return ` ${item.dataset.label}: ${item.raw} Siswa`;
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Inisialisasi Grafik Baru: Perbandingan Nilai (Formatif, UTS, UAS, Rapor)
    // -------------------------------------------------------------
    function initPerbandinganChart() {
        const ctx = document.getElementById('chartPerbandingan');
        if (!ctx || !rawChartData || !rawChartData.perbandingan) return;

        const pb = rawChartData.perbandingan;
        if (chartPerbandinganInstance) {
            chartPerbandinganInstance.destroy();
        }

        chartPerbandinganInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: pb.labels,
                datasets: [
                    {
                        label: 'Formatif (Rata-rata)',
                        data: pb.datasets.formatif,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'STS / UTS',
                        data: pb.datasets.uts,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#d97706',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'SAS / UAS',
                        data: pb.datasets.uas,
                        backgroundColor: 'rgba(147, 51, 234, 0.85)',
                        borderColor: '#7e22ce',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Nilai Rapor (Akhir)',
                        data: pb.datasets.rapor,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#059669',
                        borderWidth: 1.2,
                        borderRadius: 4,
                        maxBarThickness: 28,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: 'bold' } }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 20 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                return (pb.full_names && pb.full_names[idx]) ? pb.full_names[idx] : pb.labels[idx];
                            },
                            label: function(item) {
                                return ` ${item.dataset.label}: ${item.parsed.y}`;
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Inisialisasi Grafik Kehadiran Tren Bulanan (Juli - Desember)
    // -------------------------------------------------------------
    function initKehadiranTrenChart() {
        const ctx = document.getElementById('chartKehadiranTren');
        if (!ctx || !rawChartData || !rawChartData.kehadiran || !rawChartData.kehadiran.tren_bulanan) return;

        const tren = rawChartData.kehadiran.tren_bulanan;
        if (chartKehadiranTrenInstance) {
            chartKehadiranTrenInstance.destroy();
        }

        chartKehadiranTrenInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: tren.labels,
                datasets: [
                    {
                        label: 'Sakit (S)',
                        data: tren.sakit,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    },
                    {
                        label: 'Izin (I)',
                        data: tren.izin,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#d97706',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    },
                    {
                        label: 'Alpa (A)',
                        data: tren.alpa,
                        backgroundColor: 'rgba(239, 68, 68, 0.85)',
                        borderColor: '#dc2626',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10, weight: 'bold' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            callback: function(val) { return val + ' hr'; }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 8,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(item) {
                                return ` ${item.dataset.label}: ${item.parsed.y} Hari`;
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Inisialisasi Bar Presensi Seluruh Siswa (Diurutkan dari Alpa Terbanyak)
    // Siswa dengan Alpa >= batas alpa diberi penanda merah tegas
    // -------------------------------------------------------------
    function initKehadiranSiswaChart() {
        const ctx = document.getElementById('chartKehadiranSiswa');
        if (!ctx || !rawChartData || !rawChartData.kehadiran || !rawChartData.kehadiran.siswa_bar) return;

        const siswaBar = rawChartData.kehadiran.siswa_bar;
        if (chartKehadiranSiswaInstance) {
            chartKehadiranSiswaInstance.destroy();
        }

        const labels = siswaBar.map(s => s.nama + (s.is_peringatan_alpa ? ' ⚠️' : ''));
        const alpaData = siswaBar.map(s => s.alpa);
        const izinData = siswaBar.map(s => s.izin);
        const sakitData = siswaBar.map(s => s.sakit);

        const alpaBg = siswaBar.map(s => s.is_peringatan_alpa ? 'rgba(239, 68, 68, 0.95)' : 'rgba(239, 68, 68, 0.7)');
        const alpaBorder = siswaBar.map(s => s.is_peringatan_alpa ? '#991b1b' : '#dc2626');

        chartKehadiranSiswaInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Alpa (Tanpa Keterangan)',
                        data: alpaData,
                        backgroundColor: alpaBg,
                        borderColor: alpaBorder,
                        borderWidth: 1.5,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    },
                    {
                        label: 'Izin (I)',
                        data: izinData,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#d97706',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    },
                    {
                        label: 'Sakit (S)',
                        data: sakitData,
                        backgroundColor: 'rgba(59, 130, 246, 0.85)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 16,
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            stepSize: 1,
                            precision: 0,
                            callback: function(val) { return val + ' Hari'; }
                        }
                    },
                    y: {
                        stacked: true,
                        grid: { display: false },
                        ticks: {
                            font: { size: 10, weight: 'bold' },
                            color: function(c) {
                                const idx = c.index;
                                return (siswaBar[idx] && siswaBar[idx].is_peringatan_alpa) ? '#b91c1c' : '#334155';
                            }
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 8,
                        cornerRadius: 6,
                        callbacks: {
                            title: function(items) {
                                const idx = items[0].dataIndex;
                                const s = siswaBar[idx];
                                return `${s.nama} (NIS: ${s.nis})${s.is_peringatan_alpa ? ' - ⚠️ PERINGATAN ALPA TINGGI' : ''}`;
                            },
                            label: function(item) {
                                return ` ${item.dataset.label}: ${item.parsed.x} Hari`;
                            },
                            afterBody: function(items) {
                                const idx = items[0].dataIndex;
                                const s = siswaBar[idx];
                                return [`Total Tidak Hadir: ${s.total} Hari`];
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Inisialisasi Donut Capaian Pembiasaan (Sangat Baik, Baik, Cukup, PB)
    // -------------------------------------------------------------
    function initPembiasaanDonutChart() {
        const ctx = document.getElementById('chartPembiasaanDonut');
        if (!ctx || !rawChartData || !rawChartData.pembiasaan || !rawChartData.pembiasaan.totals) return;

        const pem = rawChartData.pembiasaan;
        const totals = pem.totals;
        if (chartPembiasaanDonutInstance) {
            chartPembiasaanDonutInstance.destroy();
        }

        const counts = [
            totals.sangat_baik || 0,
            totals.baik || 0,
            totals.cukup || 0,
            totals.perlu_bimbingan || 0
        ];

        const isZero = (totals.total === 0);

        chartPembiasaanDonutInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Sangat Baik', 'Baik', 'Cukup', 'Perlu Bimbingan'],
                datasets: [{
                    data: isZero ? [1, 0, 0, 0] : counts,
                    backgroundColor: isZero ? ['#cbd5e1'] : ['#10b981', '#3b82f6', '#f59e0b', '#ef4444'],
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(item) {
                                if (isZero) return ' Belum ada data penilaian pembiasaan';
                                const val = item.raw || 0;
                                const total = totals.total || 1;
                                const pct = ((val / total) * 100).toFixed(1);
                                return ` ${val} Nilai (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // -------------------------------------------------------------
    // Filter Dinamis Jenis Nilai (Rapor, UTS, UAS, Formatif Bulanan)
    // Bekerja secara instan tanpa reload halaman
    // -------------------------------------------------------------
    const namaBulanMap = {
        '7': 'Juli',
        '8': 'Agustus',
        '9': 'September',
        '10': 'Oktober',
        '11': 'November',
        '12': 'Desember'
    };

    window.switchJenisNilai = function(type, bulan = '') {
        if (!rawChartData || !rawChartData.akademik) return;

        let key = 'rapor';
        let labelText = 'Nilai Rapor Akhir';

        if (type === 'formatif') {
            if (bulan && rawChartData.akademik['formatif_' + bulan]) {
                key = 'formatif_' + bulan;
                labelText = 'Formatif Bulan: ' + (namaBulanMap[bulan] || ('Bulan ' + bulan));
            } else {
                key = 'formatif';
                labelText = 'Formatif Bulanan: Rata-rata 6 Bulan (JUL - DES)';
            }
        } else if (type === 'uts') {
            key = 'uts';
            labelText = 'Sumatif Tengah Semester (STS / UTS)';
        } else if (type === 'uas') {
            key = 'uas';
            labelText = 'Sumatif Akhir Semester (SAS / UAS)';
        } else {
            key = 'rapor';
            labelText = 'Nilai Rapor Akhir';
        }

        const labelEl = document.getElementById('active-jenis-label');
        if (labelEl) {
            labelEl.innerHTML = 'Menampilkan grafik: <strong>' + labelText + '</strong>';
        }

        const dataset = rawChartData.akademik[key] || rawChartData;
        currentActiveAkademik = dataset;

        // 1. Update Grafik Mapel vs KKM
        if (chartMapelInstance && dataset.mapel) {
            const mapelData = dataset.mapel;
            const bgColors = mapelData.averages.map((avg, i) => {
                const kkm = mapelData.kkm[i] || 70;
                return avg >= kkm ? 'rgba(16, 185, 129, 0.85)' : 'rgba(239, 68, 68, 0.85)';
            });
            const borderColors = mapelData.averages.map((avg, i) => {
                const kkm = mapelData.kkm[i] || 70;
                return avg >= kkm ? '#059669' : '#dc2626';
            });

            chartMapelInstance.data.labels = mapelData.labels;
            chartMapelInstance.data.datasets[0].data = mapelData.kkm;
            chartMapelInstance.data.datasets[1].data = mapelData.averages;
            chartMapelInstance.data.datasets[1].backgroundColor = bgColors;
            chartMapelInstance.data.datasets[1].borderColor = borderColors;
            chartMapelInstance.update();
        }

        // 2. Update Grafik Distribusi Capaian Siswa
        if (chartDistribusiInstance && dataset.distribusi) {
            const distData = dataset.distribusi;
            chartDistribusiInstance.data.datasets[0].data = distData.counts;
            chartDistribusiInstance.update();

            const elDistSb = document.getElementById('dist-sb-count');
            if (elDistSb) elDistSb.textContent = `${distData.counts[0] || 0} (${distData.percentages[0] || 0}%)`;
            const elDistB = document.getElementById('dist-b-count');
            if (elDistB) elDistB.textContent = `${distData.counts[1] || 0} (${distData.percentages[1] || 0}%)`;
            const elDistC = document.getElementById('dist-c-count');
            if (elDistC) elDistC.textContent = `${distData.counts[2] || 0} (${distData.percentages[2] || 0}%)`;
            const elDistPb = document.getElementById('dist-pb-count');
            if (elDistPb) elDistPb.textContent = `${distData.counts[3] || 0} (${distData.percentages[3] || 0}%)`;
        }

        // 3. Update Rentang Nilai (0-25, 26-50, 51-75, 76-100)
        if (dataset.rentang_nilai) {
            rawChartData.rentang_nilai = dataset.rentang_nilai;
            renderRankingChart();
        }

        // 4. Update Kartu KPI Ringkasan
        const sum = dataset.summary || {};
        const topM = sum.mapel_tertinggi;
        const botM = sum.mapel_terendah;
        const topS = sum.siswa_tertinggi;

        const elTopMName = document.getElementById('kpi-mapel-tertinggi-nama');
        const elTopMVal = document.getElementById('kpi-mapel-tertinggi-val');
        if (elTopMName) elTopMName.textContent = topM ? topM.nama_singkat : '-';
        if (elTopMVal) elTopMVal.textContent = topM ? `(${topM.rata_rata_format})` : '(-)';

        const elBotMName = document.getElementById('kpi-mapel-terendah-nama');
        const elBotMVal = document.getElementById('kpi-mapel-terendah-val');
        if (elBotMName) elBotMName.textContent = botM ? botM.nama_singkat : '-';
        if (elBotMVal) elBotMVal.textContent = botM ? `(${botM.rata_rata_format})` : '(-)';

        const elTopSName = document.getElementById('kpi-siswa-tertinggi-nama');
        const elTopSVal = document.getElementById('kpi-siswa-tertinggi-val');
        if (elTopSName) elTopSName.textContent = topS ? topS.nama : '-';
        if (elTopSVal) elTopSVal.textContent = topS ? `(${topS.nilai_format})` : '(-)';

        const elTuntasPct = document.getElementById('kpi-tuntas-kkm-pct');
        if (elTuntasPct) elTuntasPct.textContent = sum.persen_tuntas_kkm ?? 0;

        const elKkmKelas = document.getElementById('kpi-kkm-kelas');
        if (elKkmKelas) elKkmKelas.textContent = sum.rata_kkm_kelas ?? 70;
    };

    window.switchRekapTab = function(tab) {
        setTimeout(() => {
            if (chartMapelInstance) chartMapelInstance.resize();
            if (chartDistribusiInstance) chartDistribusiInstance.resize();
            if (chartRankingInstance) chartRankingInstance.resize();
            if (chartPerbandinganInstance) chartPerbandinganInstance.resize();
            if (chartKehadiranInstance) chartKehadiranInstance.resize();
            if (chartKehadiranTrenInstance) chartKehadiranTrenInstance.resize();
            if (chartKehadiranSiswaInstance) chartKehadiranSiswaInstance.resize();
            if (chartPembiasaanDonutInstance) chartPembiasaanDonutInstance.resize();
            if (chartPembiasaanInstance) chartPembiasaanInstance.resize();
        }, 50);
    };

    // ==========================================
    // Engine Export Gambar Grafik & Infografik
    // Dilengkapi Keterangan Lengkap & Kop Resmi
    // ==========================================

    function drawExportRoundedRect(ctx, x, y, width, height, radius, fillStyle = null, strokeStyle = null, lineWidth = 1) {
        let r = radius;
        if (typeof r === 'number') {
            r = { tl: r, tr: r, br: r, bl: r };
        }
        ctx.beginPath();
        ctx.moveTo(x + r.tl, y);
        ctx.lineTo(x + width - r.tr, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + r.tr);
        ctx.lineTo(x + width, y + height - r.br);
        ctx.quadraticCurveTo(x + width, y + height, x + width - r.br, y + height);
        ctx.lineTo(x + r.bl, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - r.bl);
        ctx.lineTo(x, y + r.tl);
        ctx.quadraticCurveTo(x, y, x + r.tl, y);
        ctx.closePath();

        if (fillStyle) {
            ctx.fillStyle = fillStyle;
            ctx.fill();
        }
        if (strokeStyle) {
            ctx.strokeStyle = strokeStyle;
            ctx.lineWidth = lineWidth;
            ctx.stroke();
        }
    }

    function getExportTruncatedText(ctx, text, maxWidth) {
        if (!text) return '';
        if (ctx.measureText(text).width <= maxWidth) return text;
        let truncated = text;
        while (truncated.length > 0 && ctx.measureText(truncated + '...').width > maxWidth) {
            truncated = truncated.slice(0, -1);
        }
        return truncated + '...';
    }

    function drawExportHeader(ctx, width, startY, meta) {
        let y = startY;

        // Aksen Garis Gradasi di Bagian Paling Atas Gambar
        const gradient = ctx.createLinearGradient(0, 0, width, 0);
        gradient.addColorStop(0, '#1d4ed8');
        gradient.addColorStop(0.5, '#2563eb');
        gradient.addColorStop(1, '#3b82f6');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, 8);

        // Logo / Emblem Lambang Sekolah
        drawExportRoundedRect(ctx, 40, y, 48, 48, 12, '#1e293b');
        ctx.fillStyle = '#fbbf24';
        ctx.font = 'bold 20px "Inter", "Segoe UI", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('SD', 40 + 24, y + 25);

        // Nama Sekolah & Sub-header
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 20px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(meta.namaSekolah, 102, y + 3);

        ctx.fillStyle = '#64748b';
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('E-RAPORT • SISTEM REKAPITULASI & EVALUASI NILAI KURIKULUM MERDEKA', 102, y + 29);

        // Lencana Kanan: Dokumen Resmi e-Raport
        const badgeW = 145;
        const badgeH = 30;
        const badgeX = width - 40 - badgeW;
        drawExportRoundedRect(ctx, badgeX, y + 8, badgeW, badgeH, 15, '#eff6ff', '#bfdbfe', 1.5);
        ctx.fillStyle = '#1d4ed8';
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('✓ E-RAPORT RESMI', badgeX + (badgeW / 2), y + 8 + (badgeH / 2));

        y += 58;

        // Garis Pembatas Header
        ctx.beginPath();
        ctx.moveTo(40, y);
        ctx.lineTo(width - 40, y);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        y += 14;

        // Barisan Kartu Badge Informasi Kelas & Periode
        const badges = [
            { label: 'Kelas', val: meta.namaKelas, bg: '#f1f5f9', border: '#e2e8f0', text: '#0f172a' },
            { label: 'Semester', val: meta.semester, bg: '#f1f5f9', border: '#e2e8f0', text: '#0f172a' },
            { label: 'Tahun Ajaran', val: meta.tahunAjaran, bg: '#f1f5f9', border: '#e2e8f0', text: '#0f172a' },
            { label: 'Periode', val: meta.periode + ' (' + meta.periodeKet + ')', bg: '#eff6ff', border: '#bfdbfe', text: '#1d4ed8' },
            { label: 'Wali Kelas', val: meta.waliKelas, bg: '#f1f5f9', border: '#e2e8f0', text: '#0f172a' }
        ];

        let badgeXPos = 40;
        ctx.font = '11px "Inter", "Segoe UI", sans-serif';
        ctx.textBaseline = 'middle';

        badges.forEach(b => {
            const fullText = b.label + ': ' + b.val;
            const textWidth = ctx.measureText(fullText).width;
            const pillW = Math.min(270, textWidth + 24);

            drawExportRoundedRect(ctx, badgeXPos, y, pillW, 28, 7, b.bg, b.border, 1);
            
            ctx.fillStyle = b.text;
            ctx.textAlign = 'left';
            ctx.fillText(getExportTruncatedText(ctx, fullText, pillW - 16), badgeXPos + 12, y + 14);

            badgeXPos += pillW + 8;
        });

        y += 40;
        return y;
    }

    function drawExportFooter(ctx, width, startY, meta) {
        let y = startY;

        // Garis Pembatas Atas Footer
        ctx.beginPath();
        ctx.moveTo(40, y);
        ctx.lineTo(width - 40, y);
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1.5;
        ctx.stroke();

        y += 16;

        // Waktu Cetak & Unduh Otomatis
        const now = new Date();
        const dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace(':', '.');
        const waktuCetak = `${dateStr}, ${timeStr} WIB`;

        // Teks Footer Sisi Kiri
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillStyle = '#64748b';
        ctx.font = '11px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(`Diunduh pada: ${waktuCetak}`, 40, y);

        const waliNip = meta.nipWali && meta.nipWali !== '-' ? `(NIP: ${meta.nipWali})` : '';
        ctx.fillStyle = '#334155';
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(`Wali Kelas: ${meta.waliKelas} ${waliNip}`, 40, y + 17);

        // Teks Footer Sisi Kanan
        ctx.textAlign = 'right';
        ctx.fillStyle = '#64748b';
        ctx.font = '11px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(`${meta.namaSekolah} • Dokumen e-Raport Digital`, width - 40, y);

        ctx.fillStyle = '#059669';
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('✓ Terverifikasi Sistem Resmi Sekolah', width - 40, y + 17);

        y += 40;

        // Garis Tebal Aksen Bawah
        ctx.fillStyle = '#0f172a';
        ctx.fillRect(0, y, width, 5);

        y += 5;
        return y;
    }

    function drawExportMetricCard(ctx, x, y, width, height, title, value, subtext, colorHex, bgHex, borderHex) {
        drawExportRoundedRect(ctx, x, y, width, height, 12, bgHex, borderHex, 1);

        // Garis aksen samping
        drawExportRoundedRect(ctx, x + 3, y + 6, 4, height - 12, 2, colorHex);

        // Judul Kartu
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillStyle = colorHex;
        ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(title.toUpperCase(), x + 16, y + 10);

        // Nilai Utama
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 14px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(getExportTruncatedText(ctx, value, width - 24), x + 16, y + 26);

        // Keterangan Tambahan
        if (subtext) {
            ctx.fillStyle = '#64748b';
            ctx.font = '10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText(getExportTruncatedText(ctx, subtext, width - 24), x + 16, y + 46);
        }
    }

    window.downloadChart = function(canvasId, filename) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const CANVAS_WIDTH = 1200;
        const PADDING = 40;
        const CONTENT_WIDTH = CANVAS_WIDTH - (PADDING * 2);

        const sum = (rawChartData && rawChartData.summary) ? rawChartData.summary : {};
        const dist = (rawChartData && rawChartData.distribusi) ? rawChartData.distribusi : { counts: [0,0,0,0], percentages: [0,0,0,0], total_dinilai: 0, total_siswa: metaSekolah.totalSiswa };

        let config = {
            title: 'GRAFIK ANALISIS NILAI PESERTA DIDIK',
            subtitle: 'Sistem evaluasi capaian hasil belajar peserta didik',
            badge: 'ANALISIS NILAI',
            badgeColor: '#2563eb',
            badgeBg: '#eff6ff',
            badgeBorder: '#bfdbfe',
            chartHeight: 380,
            extraHeight: 180
        };

        if (canvasId === 'chartMapel') {
            config = {
                title: 'GRAFIK PERFORMA MATA PELAJARAN VS KKM',
                subtitle: 'Perbandingan nilai rata-rata kelas per mata pelajaran terhadap batas standar Kriteria Ketercapaian Tujuan Pembelajaran (KKM)',
                badge: 'ANALISIS MATA PELAJARAN',
                badgeColor: '#2563eb',
                badgeBg: '#eff6ff',
                badgeBorder: '#bfdbfe',
                chartHeight: Math.min(460, Math.max(340, Math.round((CONTENT_WIDTH - 40) * (canvas.height / canvas.width)))),
                extraHeight: 180
            };
        } else if (canvasId === 'chartDistribusi') {
            config = {
                title: 'GRAFIK DISTRIBUSI CAPAIAN HASIL BELAJAR SISWA',
                subtitle: 'Komposisi pengelompokan tingkat capaian kompetensi peserta didik berdasarkan predikat akumulasi nilai rata-rata',
                badge: 'DISTRIBUSI PREDIKAT',
                badgeColor: '#059669',
                badgeBg: '#ecfdf5',
                badgeBorder: '#a7f3d0',
                chartHeight: 380,
                extraHeight: 60
            };
        } else if (canvasId === 'chartRanking') {
            const rentang = rawChartData?.rentang_nilai || {};
            let subTitleStr = 'Distribusi sebaran per rentang nilai (0-25, 26-50, 51-75, 76-100) khusus ' + metaSekolah.namaKelas;
            let titleStr = 'GRAFIK SEBARAN CAPAIAN SISWA PER MAPEL • ' + metaSekolah.namaKelas.toUpperCase();
            if (currentRentangMode === 'semua') {
                titleStr = 'GRAFIK SEBARAN RENTANG NILAI SEMUA MATA PELAJARAN • ' + metaSekolah.namaKelas.toUpperCase();
                subTitleStr = 'Komparasi bertumpuk sebaran nilai 0-25, 26-50, 51-75, 76-100 seluruh mata pelajaran di ' + metaSekolah.namaKelas;
            } else if (currentRentangMode === 'rata_rata') {
                titleStr = 'GRAFIK SEBARAN RENTANG NILAI RATA-RATA SISWA • ' + metaSekolah.namaKelas.toUpperCase();
                subTitleStr = 'Distribusi capaian berdasarkan akumulasi nilai rata-rata akhir siswa di ' + metaSekolah.namaKelas;
            } else {
                const mData = (rentang.per_mapel && rentang.per_mapel[currentRentangMode]) ? rentang.per_mapel[currentRentangMode] : null;
                if (mData) {
                    titleStr = 'GRAFIK SEBARAN RENTANG NILAI ' + mData.nama.toUpperCase() + ' • ' + metaSekolah.namaKelas.toUpperCase();
                    subTitleStr = 'Distribusi nilai siswa pada mata pelajaran ' + mData.nama + ' (KKM: ' + mData.kkm + ') di ' + metaSekolah.namaKelas;
                }
            }
            config = {
                title: titleStr,
                subtitle: subTitleStr,
                badge: 'RENTANG NILAI',
                badgeColor: '#2563eb',
                badgeBg: '#eff6ff',
                badgeBorder: '#bfdbfe',
                chartHeight: 340,
                extraHeight: 140
            };
        } else if (canvasId === 'chartKehadiran') {
            config = {
                title: 'GRAFIK REKAPITULASI KEHADIRAN SISWA (SIA) • ' + metaSekolah.namaKelas.toUpperCase(),
                subtitle: 'Perbandingan total hari Sakit (S), Izin (I), dan Tanpa Keterangan / Alpa (A) khusus peserta didik ' + metaSekolah.namaKelas,
                badge: 'KEHADIRAN & KEDISIPLINAN',
                badgeColor: '#0284c7',
                badgeBg: '#f0f9ff',
                badgeBorder: '#bae6fd',
                chartHeight: 380,
                extraHeight: 60
            };
        } else if (canvasId === 'chartPerbandingan') {
            config = {
                title: 'GRAFIK PERBANDINGAN NILAI ASESMEN • ' + metaSekolah.namaKelas.toUpperCase(),
                subtitle: 'Komparasi nilai Formatif, UTS (STS), Semesteran (SAS), dan Nilai Rapor Akhir per mata pelajaran',
                badge: 'PERBANDINGAN NILAI',
                badgeColor: '#4f46e5',
                badgeBg: '#eef2ff',
                badgeBorder: '#c7d2fe',
                chartHeight: 380,
                extraHeight: 80
            };
        } else if (canvasId === 'chartKehadiranTren') {
            config = {
                title: 'GRAFIK TREN KETIDAKHADIRAN PER BULAN (JULI - DESEMBER) • ' + metaSekolah.namaKelas.toUpperCase(),
                subtitle: 'Tren frekuensi Sakit, Izin, dan Alpa peserta didik per bulan selama satu semester',
                badge: 'TREN KEHADIRAN',
                badgeColor: '#0284c7',
                badgeBg: '#f0f9ff',
                badgeBorder: '#bae6fd',
                chartHeight: 380,
                extraHeight: 80
            };
        } else if (canvasId === 'chartKehadiranSiswa') {
            config = {
                title: 'GRAFIK PRESENSI SISWA (DIURUTKAN DARI ALPA TERBANYAK) • ' + metaSekolah.namaKelas.toUpperCase(),
                subtitle: 'Rekapitulasi ketidakhadiran per siswa dengan prioritas monitoring siswa alpa tinggi',
                badge: 'PRESENSI SISWA',
                badgeColor: '#e11d48',
                badgeBg: '#fff1f2',
                badgeBorder: '#fecdd3',
                chartHeight: Math.min(750, Math.max(340, canvas.height)),
                extraHeight: 80
            };
        } else if (canvasId === 'chartPembiasaan' || canvasId === 'chartPembiasaanDonut') {
            config = {
                title: 'GRAFIK CAPAIAN ASPEK KARAKTER & PEMBIASAAN • ' + metaSekolah.namaKelas.toUpperCase(),
                subtitle: 'Distribusi capaian predikat pembiasaan per aspek dimensi profil pelajar khusus peserta didik ' + metaSekolah.namaKelas,
                badge: 'KARAKTER & PEMBIASAAN',
                badgeColor: '#059669',
                badgeBg: '#ecfdf5',
                badgeBorder: '#a7f3d0',
                chartHeight: 380,
                extraHeight: 180
            };
        }

        const headerHeight = 150;
        const titleBlockHeight = 75;
        const chartContainerHeight = config.chartHeight + 40;
        const keteranganHeight = config.extraHeight;
        const footerHeight = 85;
        const TOTAL_HEIGHT = headerHeight + titleBlockHeight + chartContainerHeight + keteranganHeight + footerHeight;

        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = CANVAS_WIDTH;
        tempCanvas.height = TOTAL_HEIGHT;
        const ctx = tempCanvas.getContext('2d');

        // Latar Belakang Putih Bersih Solid
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, CANVAS_WIDTH, TOTAL_HEIGHT);

        // Frame Pinggir Gambar
        drawExportRoundedRect(ctx, 1, 1, CANVAS_WIDTH - 2, TOTAL_HEIGHT - 2, 0, null, '#cbd5e1', 2);

        // 1. Gambar Header Resmi
        let currY = drawExportHeader(ctx, CANVAS_WIDTH, 26, metaSekolah);

        // 2. Judul & Keterangan Grafik
        const badgeW = ctx.measureText(config.badge).width + 26;
        drawExportRoundedRect(ctx, PADDING, currY, Math.max(120, badgeW), 22, 6, config.badgeBg, config.badgeBorder, 1);
        ctx.fillStyle = config.badgeColor;
        ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.fillText(config.badge, PADDING + 10, currY + 11);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 18px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(config.title, PADDING, currY + 36);

        ctx.fillStyle = '#64748b';
        ctx.font = '12px "Inter", "Segoe UI", sans-serif';
        ctx.fillText(config.subtitle, PADDING, currY + 58);

        currY += 76;

        // 3. Render Area Grafik & Rincian Keterangan
        if (canvasId === 'chartMapel') {
            // Wadah Kartu Grafik
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, config.chartHeight + 40, 16, '#f8fafc', '#e2e8f0', 1);
            ctx.drawImage(canvas, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, config.chartHeight);
            currY += config.chartHeight + 52;

            // Keterangan & Legenda
            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText('KETERANGAN LEGENDA & RINGKASAN EVALUASI MAPEL', PADDING, currY);
            currY += 16;

            // Baris Legenda Warna
            drawExportRoundedRect(ctx, PADDING, currY, 14, 14, 3, '#10b981');
            ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
            ctx.fillStyle = '#1e293b';
            ctx.fillText('Rata-rata Mapel ≥ KKM (Tuntas)', PADDING + 22, currY + 7);

            drawExportRoundedRect(ctx, PADDING + 250, currY, 14, 14, 3, '#ef4444');
            ctx.fillText('Rata-rata Mapel < KKM (Perlu Perhatian)', PADDING + 272, currY + 7);

            ctx.beginPath();
            ctx.setLineDash([5, 4]);
            ctx.strokeStyle = '#f59e0b';
            ctx.lineWidth = 3;
            ctx.moveTo(PADDING + 560, currY + 7);
            ctx.lineTo(PADDING + 586, currY + 7);
            ctx.stroke();
            ctx.setLineDash([]);
            ctx.fillText('Standar KKM Mata Pelajaran', PADDING + 594, currY + 7);

            ctx.textAlign = 'right';
            ctx.font = '11px "Inter", "Segoe UI", sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText('Total ' + (rawChartData?.mapel?.labels?.length || 0) + ' Mata Pelajaran Terdata', CANVAS_WIDTH - PADDING, currY + 7);
            ctx.textAlign = 'left';

            currY += 26;

            // 4 Kartu Metrik
            const cardW = (CONTENT_WIDTH - (3 * 14)) / 4;
            const cardH = 68;
            const topM = sum.mapel_tertinggi;
            const botM = sum.mapel_terendah;

            drawExportMetricCard(ctx, PADDING, currY, cardW, cardH, 'Mapel Tertinggi', (topM ? topM.nama_singkat : '-') + ' (' + (topM ? topM.rata_rata_format : '-') + ')', topM ? topM.nama : 'Belum ada data', '#059669', '#ecfdf5', '#a7f3d0');
            drawExportMetricCard(ctx, PADDING + cardW + 14, currY, cardW, cardH, 'Perlu Perhatian', (botM ? botM.nama_singkat : '-') + ' (' + (botM ? botM.rata_rata_format : '-') + ')', botM ? botM.nama : 'Belum ada data', '#dc2626', '#fef2f2', '#fecaca');
            drawExportMetricCard(ctx, PADDING + (cardW + 14) * 2, currY, cardW, cardH, 'Tuntas KKM Kelas', (sum.persen_tuntas_kkm || 0) + '% Siswa', 'Batas KKM Kelas: ~' + (sum.rata_kkm_kelas || 70), '#2563eb', '#eff6ff', '#bfdbfe');
            drawExportMetricCard(ctx, PADDING + (cardW + 14) * 3, currY, cardW, cardH, 'Rata-rata Kelas', metaSekolah.rataRataKelas, (dist.total_dinilai || 0) + ' Siswa Terdata', '#475569', '#f8fafc', '#e2e8f0');

            currY += cardH + 24;

        } else if (canvasId === 'chartDistribusi') {
            const contH = config.chartHeight + 40;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, contH, 16, '#f8fafc', '#e2e8f0', 1);

            // Kolom Kiri: Doughnut Chart
            const donutBoxW = 440;
            const donutBoxH = config.chartHeight;
            const donutAspect = canvas.width / canvas.height;
            let dW = 340;
            let dH = 340 / donutAspect;
            if (dH > donutBoxH) { dH = donutBoxH; dW = dH * donutAspect; }
            const dX = PADDING + 20 + ((donutBoxW - dW) / 2);
            const dY = currY + 20 + ((donutBoxH - dH) / 2);
            ctx.drawImage(canvas, dX, dY, dW, dH);

            // Garis Pembatas Kolom
            ctx.beginPath();
            ctx.moveTo(PADDING + donutBoxW + 20, currY + 24);
            ctx.lineTo(PADDING + donutBoxW + 20, currY + contH - 24);
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.5;
            ctx.stroke();

            // Kolom Kanan: 4 Kartu Rincian Predikat Siswa
            const rightX = PADDING + donutBoxW + 40;
            const rightW = CONTENT_WIDTH - donutBoxW - 60;

            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.fillText('RINCIAN KOMPOSISI PREDIKAT CAPAIAN SISWA', rightX, currY + 22);

            const catRows = [
                { label: 'Sangat Baik (≥ 85)', count: dist.counts[0] || 0, pct: dist.percentages[0] || 0, color: '#10b981', border: '#a7f3d0' },
                { label: 'Baik (75 - 84)', count: dist.counts[1] || 0, pct: dist.percentages[1] || 0, color: '#3b82f6', border: '#bfdbfe' },
                { label: 'Cukup (65 - 74)', count: dist.counts[2] || 0, pct: dist.percentages[2] || 0, color: '#f59e0b', border: '#fde68a' },
                { label: 'Perlu Bimbingan (< 65)', count: dist.counts[3] || 0, pct: dist.percentages[3] || 0, color: '#ef4444', border: '#fecaca' }
            ];

            let rY = currY + 46;
            catRows.forEach(row => {
                drawExportRoundedRect(ctx, rightX, rY, rightW, 58, 10, '#ffffff', row.border, 1.2);

                // Titik warna indikator
                ctx.beginPath();
                ctx.arc(rightX + 22, rY + 29, 6, 0, Math.PI * 2);
                ctx.fillStyle = row.color;
                ctx.fill();

                // Label predikat
                ctx.fillStyle = '#1e293b';
                ctx.font = 'bold 13px "Inter", "Segoe UI", sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(row.label, rightX + 38, rY + 29);

                // Jumlah & Persentase
                ctx.textAlign = 'right';
                ctx.font = 'bold 14px "Inter", "Segoe UI", sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(row.count + ' Siswa  (' + row.pct + '%)', rightX + rightW - 18, rY + 29);
                ctx.textAlign = 'left';

                rY += 66;
            });

            // Kartu Ringkasan Bawah
            drawExportRoundedRect(ctx, rightX, rY + 4, rightW, 46, 10, '#f1f5f9', '#cbd5e1', 1);
            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillText('Total Siswa Dinilai: ' + (dist.total_dinilai || 0) + ' dari ' + (dist.total_siswa || metaSekolah.totalSiswa) + ' Siswa  •  Rata-rata: ' + metaSekolah.rataRataKelas, rightX + 16, rY + 27);

            currY += contH + 24;

        } else if (canvasId === 'chartRanking') {
            // Wadah Kartu Grafik Rentang Nilai
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, config.chartHeight + 40, 16, '#f8fafc', '#e2e8f0', 1);
            ctx.drawImage(canvas, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, config.chartHeight);
            currY += config.chartHeight + 52;

            // Keterangan Section
            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText('RINCIAN DISTRIBUSI JUMLAH SISWA PER RENTANG NILAI ' + metaSekolah.namaKelas.toUpperCase(), PADDING, currY);

            ctx.textAlign = 'right';
            ctx.font = '11px "Inter", "Segoe UI", sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText('Total ' + (dist.total_dinilai || 0) + ' Siswa Dinilai (' + (dist.total_siswa || metaSekolah.totalSiswa) + ' Siswa Kelas)', CANVAS_WIDTH - PADDING, currY);
            ctx.textAlign = 'left';

            currY += 20;

            const rentang = rawChartData.rentang_nilai || {};
            let rCounts = rentang.counts || [0, 0, 0, 0];
            let rPcts = rentang.percentages || [0, 0, 0, 0];
            let unitText = 'Siswa';

            if (currentRentangMode === 'semua') {
                const km = rentang.kumulatif_mapel;
                if (km) {
                    rCounts = km.counts;
                    rPcts = km.percentages;
                    unitText = 'Nilai';
                }
            } else if (currentRentangMode !== 'rata_rata') {
                const mData = (rentang.per_mapel && rentang.per_mapel[currentRentangMode]) ? rentang.per_mapel[currentRentangMode] : null;
                if (mData) {
                    rCounts = mData.counts;
                    rPcts = mData.percentages;
                }
            }

            const rCards = [
                { range: '0 - 25', label: 'Perlu Intervensi', count: rCounts[0], pct: rPcts[0], color: '#ef4444', bg: '#fef2f2', border: '#fecaca' },
                { range: '26 - 50', label: 'Perlu Bimbingan', count: rCounts[1], pct: rPcts[1], color: '#f97316', bg: '#fff7ed', border: '#fed7aa' },
                { range: '51 - 75', label: 'Cukup / Memadai', count: rCounts[2], pct: rPcts[2], color: '#3b82f6', bg: '#eff6ff', border: '#bfdbfe' },
                { range: '76 - 100', label: 'Baik & Sangat Baik', count: rCounts[3], pct: rPcts[3], color: '#10b981', bg: '#ecfdf5', border: '#a7f3d0' }
            ];

            const cardW = (CONTENT_WIDTH - (3 * 14)) / 4;
            const cardH = 68;
            rCards.forEach((c, idx) => {
                const cX = PADDING + (idx * (cardW + 14));
                drawExportMetricCard(ctx, cX, currY, cardW, cardH, 'Rentang ' + c.range, c.count + ' ' + unitText + ' (' + c.pct + '%)', c.label, c.color, c.bg, c.border);
            });

            currY += cardH + 24;

        } else if (canvasId === 'chartKehadiran') {
            const contH = config.chartHeight + 40;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, contH, 16, '#f8fafc', '#e2e8f0', 1);

            const kh = rawChartData.kehadiran || { counts: [0,0,0], percentages: [0,0,0], total_sakit: 0, total_izin: 0, total_alpa: 0, total_tidak_hadir: 0, siswa_hadir_penuh: 0, persen_hadir_penuh: 0, rata_absen: 0 };

            // Kolom Kiri: Doughnut Chart Kehadiran
            const donutBoxW = 440;
            const donutBoxH = config.chartHeight;
            const donutAspect = canvas.width / canvas.height;
            let dW = 340;
            let dH = 340 / donutAspect;
            if (dH > donutBoxH) { dH = donutBoxH; dW = dH * donutAspect; }
            const dX = PADDING + 20 + ((donutBoxW - dW) / 2);
            const dY = currY + 20 + ((donutBoxH - dH) / 2);
            ctx.drawImage(canvas, dX, dY, dW, dH);

            // Garis Pembatas Kolom
            ctx.beginPath();
            ctx.moveTo(PADDING + donutBoxW + 20, currY + 24);
            ctx.lineTo(PADDING + donutBoxW + 20, currY + contH - 24);
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1.5;
            ctx.stroke();

            // Kolom Kanan: 3 Kartu Rincian SIA + 2 Kartu Metrik
            const rightX = PADDING + donutBoxW + 40;
            const rightW = CONTENT_WIDTH - donutBoxW - 60;

            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'top';
            ctx.fillText('RINCIAN HARI KETIDAKHADIRAN PESERTA DIDIK ' + metaSekolah.namaKelas.toUpperCase(), rightX, currY + 22);

            const siaRows = [
                { label: 'Sakit (S)', count: kh.total_sakit, pct: kh.percentages[0] || 0, color: '#3b82f6', border: '#bfdbfe' },
                { label: 'Izin (I)', count: kh.total_izin, pct: kh.percentages[1] || 0, color: '#f59e0b', border: '#fde68a' },
                { label: 'Tanpa Keterangan / Alpa (A)', count: kh.total_alpa, pct: kh.percentages[2] || 0, color: '#ef4444', border: '#fecaca' }
            ];

            let rY = currY + 48;
            siaRows.forEach(row => {
                drawExportRoundedRect(ctx, rightX, rY, rightW, 56, 10, '#ffffff', row.border, 1.2);

                // Titik warna indikator
                ctx.beginPath();
                ctx.arc(rightX + 22, rY + 28, 6, 0, Math.PI * 2);
                ctx.fillStyle = row.color;
                ctx.fill();

                // Label
                ctx.fillStyle = '#1e293b';
                ctx.font = 'bold 13px "Inter", "Segoe UI", sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(row.label, rightX + 38, rY + 28);

                // Jumlah & Persentase
                ctx.textAlign = 'right';
                ctx.font = 'bold 14px "Inter", "Segoe UI", sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(row.count + ' Hari  (' + row.pct + '%)', rightX + rightW - 18, rY + 28);
                ctx.textAlign = 'left';

                rY += 64;
            });

            // 2 Kartu Metrik Tambahan
            const subW = (rightW - 12) / 2;
            drawExportRoundedRect(ctx, rightX, rY + 4, subW, 64, 10, '#ecfdf5', '#a7f3d0', 1);
            ctx.fillStyle = '#065f46';
            ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('SISWA HADIR 100%', rightX + 14, rY + 16);
            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 14px "Inter", "Segoe UI", sans-serif';
            ctx.fillText(kh.siswa_hadir_penuh + ' Siswa (' + kh.persen_hadir_penuh + '%)', rightX + 14, rY + 34);
            ctx.fillStyle = '#64748b';
            ctx.font = '10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('Nol hari tidak hadir', rightX + 14, rY + 52);

            drawExportRoundedRect(ctx, rightX + subW + 12, rY + 4, subW, 64, 10, '#f8fafc', '#e2e8f0', 1);
            ctx.fillStyle = '#475569';
            ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('TOTAL KETIDAKHADIRAN', rightX + subW + 26, rY + 16);
            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 14px "Inter", "Segoe UI", sans-serif';
            ctx.fillText(kh.total_tidak_hadir + ' Hari Kumulatif', rightX + subW + 26, rY + 34);
            ctx.fillStyle = '#64748b';
            ctx.font = '10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('Rata-rata: ' + kh.rata_absen + ' hari/siswa', rightX + subW + 26, rY + 52);

            currY += contH + 24;

        } else if (canvasId === 'chartPembiasaan') {
            const contH = config.chartHeight + 40;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, contH, 16, '#f8fafc', '#e2e8f0', 1);
            ctx.drawImage(canvas, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, config.chartHeight);
            currY += contH + 12;

            // Keterangan Section
            ctx.fillStyle = '#334155';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText('KETERANGAN LEGENDA PREDIKAT & OBSERVASI KARAKTER ' + metaSekolah.namaKelas.toUpperCase(), PADDING, currY);
            currY += 16;

            // Legenda 4 Warna
            const pem = rawChartData.pembiasaan || { totals: { sangat_baik: 0, baik: 0, cukup: 0, perlu_bimbingan: 0, total: 0 }, percentages: { sangat_baik: 0, baik: 0, cukup: 0, perlu_bimbingan: 0 } };
            const pemLegs = [
                { label: 'Sangat Baik (SB)', color: '#10b981' },
                { label: 'Baik (B)', color: '#3b82f6' },
                { label: 'Cukup (C)', color: '#f59e0b' },
                { label: 'Perlu Bimbingan (PB)', color: '#ef4444' }
            ];

            let lX = PADDING;
            pemLegs.forEach(lg => {
                drawExportRoundedRect(ctx, lX, currY, 14, 14, 3, lg.color);
                ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
                ctx.fillStyle = '#1e293b';
                ctx.fillText(lg.label, lX + 22, currY + 7);
                lX += ctx.measureText(lg.label).width + 36;
            });

            ctx.textAlign = 'right';
            ctx.font = '11px "Inter", "Segoe UI", sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText('Total ' + (rawChartData?.pembiasaan?.labels?.length || 0) + ' Aspek Dimensi Karakter', CANVAS_WIDTH - PADDING, currY + 7);
            ctx.textAlign = 'left';

            currY += 26;

            // 4 Kartu Metrik Predikat
            const cardW = (CONTENT_WIDTH - (3 * 14)) / 4;
            const cardH = 68;

            drawExportMetricCard(ctx, PADDING, currY, cardW, cardH, 'Sangat Baik', pem.totals.sangat_baik + ' (' + pem.percentages.sangat_baik + '%)', 'Predikat karakter tertinggi', '#059669', '#ecfdf5', '#a7f3d0');
            drawExportMetricCard(ctx, PADDING + cardW + 14, currY, cardW, cardH, 'Baik', pem.totals.baik + ' (' + pem.percentages.baik + '%)', 'Telah memenuhi pembiasaan', '#2563eb', '#eff6ff', '#bfdbfe');
            drawExportMetricCard(ctx, PADDING + (cardW + 14) * 2, currY, cardW, cardH, 'Cukup', pem.totals.cukup + ' (' + pem.percentages.cukup + '%)', 'Cukup konsisten berkarakter', '#d97706', '#fffbeb', '#fde68a');
            drawExportMetricCard(ctx, PADDING + (cardW + 14) * 3, currY, cardW, cardH, 'Perlu Bimbingan', pem.totals.perlu_bimbingan + ' (' + pem.percentages.perlu_bimbingan + '%)', 'Perlu pendampingan wali/guru', '#dc2626', '#fef2f2', '#fecaca');

            currY += cardH + 24;
        } else {
            // Wadah default untuk chart lainnya (chartPerbandingan, chartKehadiranTren, chartKehadiranSiswa, chartPembiasaanDonut)
            const contH = config.chartHeight + 40;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, contH, 16, '#f8fafc', '#e2e8f0', 1);
            ctx.drawImage(canvas, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, config.chartHeight);
            currY += contH + 24;
        }

        // 4. Gambar Footer Resmi
        drawExportFooter(ctx, CANVAS_WIDTH, currY, metaSekolah);

        // 5. Unduh Gambar
        const imageURI = tempCanvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = (filename || 'grafik') + '.png';
        link.href = imageURI;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    window.downloadAllChartsInfographic = function(filename) {
        if (!rawChartData || !rawChartData.has_data) {
            alert('Data grafik belum tersedia untuk diunduh.');
            return;
        }

        const cMapel = document.getElementById('chartMapel');
        const cDist = document.getElementById('chartDistribusi');
        const cRank = document.getElementById('chartRanking');
        const cKehadiran = document.getElementById('chartKehadiran');
        const cPembiasaan = document.getElementById('chartPembiasaan');

        if (!cMapel || !cDist || !cRank) {
            alert('Grafik belum siap diunduh.');
            return;
        }

        const CANVAS_WIDTH = 1200;
        const PADDING = 40;
        const CONTENT_WIDTH = CANVAS_WIDTH - (PADDING * 2);

        const sum = rawChartData.summary || {};
        const dist = rawChartData.distribusi || { counts: [0,0,0,0], percentages: [0,0,0,0], total_dinilai: 0, total_siswa: metaSekolah.totalSiswa };
        const kh = rawChartData.kehadiran || null;
        const pem = rawChartData.pembiasaan || null;

        // Kalkulasi Total Tinggi Infografik
        const headerHeight = 150;
        const topCardsHeight = 94;
        const mapelBlockHeight = 440 + 80;
        const distBlockHeight = 400 + 40;
        const rankBlockHeight = 420 + 80;
        const kehadiranBlockHeight = (cKehadiran && kh) ? 440 : 0;
        const pembiasaanBlockHeight = (cPembiasaan && pem) ? 500 : 0;
        const footerHeight = 85;

        const TOTAL_HEIGHT = headerHeight + topCardsHeight + mapelBlockHeight + distBlockHeight + rankBlockHeight + kehadiranBlockHeight + pembiasaanBlockHeight + footerHeight + 60;

        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = CANVAS_WIDTH;
        tempCanvas.height = TOTAL_HEIGHT;
        const ctx = tempCanvas.getContext('2d');

        // Latar Belakang Putih Bersih
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, CANVAS_WIDTH, TOTAL_HEIGHT);
        drawExportRoundedRect(ctx, 1, 1, CANVAS_WIDTH - 2, TOTAL_HEIGHT - 2, 0, null, '#cbd5e1', 2);

        // 1. Gambar Header Resmi
        let currY = drawExportHeader(ctx, CANVAS_WIDTH, 26, metaSekolah);

        // 2. Ringkasan Cepat 6 Kartu Metrik Utama di Atas
        const cardW = (CONTENT_WIDTH - (5 * 12)) / 6;
        const cardH = 68;
        const topM = sum.mapel_tertinggi;
        const botM = sum.mapel_terendah;
        const sTop = sum.siswa_tertinggi;

        drawExportMetricCard(ctx, PADDING, currY, cardW, cardH, 'Mapel Tertinggi', (topM ? topM.nama_singkat : '-') + ' (' + (topM ? topM.rata_rata_format : '-') + ')', topM ? topM.nama : '', '#059669', '#ecfdf5', '#a7f3d0');
        drawExportMetricCard(ctx, PADDING + (cardW + 12), currY, cardW, cardH, 'Perlu Perhatian', (botM ? botM.nama_singkat : '-') + ' (' + (botM ? botM.rata_rata_format : '-') + ')', botM ? botM.nama : '', '#dc2626', '#fef2f2', '#fecaca');
        drawExportMetricCard(ctx, PADDING + (cardW + 12) * 2, currY, cardW, cardH, 'Rata² Tertinggi', (sTop ? sTop.nama : '-') + ' (' + (sTop ? sTop.nilai_format : '-') + ')', 'Juara 1 Kelas', '#d97706', '#fffbeb', '#fde68a');
        drawExportMetricCard(ctx, PADDING + (cardW + 12) * 3, currY, cardW, cardH, 'Tuntas KKM', (sum.persen_tuntas_kkm || 0) + '% Siswa', 'Batas KKM: ~' + (sum.rata_kkm_kelas || 70), '#2563eb', '#eff6ff', '#bfdbfe');
        drawExportMetricCard(ctx, PADDING + (cardW + 12) * 4, currY, cardW, cardH, 'Kehadiran SIA', 'S:' + (kh ? kh.total_sakit : 0) + ' I:' + (kh ? kh.total_izin : 0) + ' A:' + (kh ? kh.total_alpa : 0), (kh ? kh.persen_hadir_penuh : 0) + '% hadir penuh', '#0284c7', '#f0f9ff', '#bae6fd');
        drawExportMetricCard(ctx, PADDING + (cardW + 12) * 5, currY, cardW, cardH, 'Pembiasaan', (pem ? pem.percentages.sangat_baik : 0) + '% Sangat Baik', (pem ? pem.totals.total : 0) + ' data karakter', '#0d9488', '#f0fdfa', '#99f6e4');

        currY += cardH + 26;

        // 3. Bagian 1: Performa Mapel vs KKM
        drawExportRoundedRect(ctx, PADDING, currY, 160, 22, 6, '#eff6ff', '#bfdbfe', 1);
        ctx.fillStyle = '#1d4ed8';
        ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'left';
        ctx.fillText('BAGIAN 1 • MATA PELAJARAN', PADDING + 10, currY + 11);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 16px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('PERFORMA NILAI MATA PELAJARAN VS STANDAR KKM', PADDING, currY + 34);
        currY += 48;

        const mapelH = 340;
        drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, mapelH + 40, 16, '#f8fafc', '#e2e8f0', 1);
        ctx.drawImage(cMapel, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, mapelH);
        currY += mapelH + 50;

        // Legenda Mapel
        drawExportRoundedRect(ctx, PADDING, currY, 14, 14, 3, '#10b981');
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.fillStyle = '#1e293b';
        ctx.fillText('Rata-rata Mapel ≥ KKM (Tuntas)', PADDING + 22, currY + 7);

        drawExportRoundedRect(ctx, PADDING + 240, currY, 14, 14, 3, '#ef4444');
        ctx.fillText('Rata-rata Mapel < KKM (Perlu Perhatian)', PADDING + 262, currY + 7);

        ctx.beginPath();
        ctx.setLineDash([5, 4]);
        ctx.strokeStyle = '#f59e0b';
        ctx.lineWidth = 3;
        ctx.moveTo(PADDING + 540, currY + 7);
        ctx.lineTo(PADDING + 566, currY + 7);
        ctx.stroke();
        ctx.setLineDash([]);
        ctx.fillText('Standar KKM Mata Pelajaran', PADDING + 574, currY + 7);

        currY += 34;

        // 4. Bagian 2: Distribusi Capaian Siswa
        drawExportRoundedRect(ctx, PADDING, currY, 150, 22, 6, '#ecfdf5', '#a7f3d0', 1);
        ctx.fillStyle = '#047857';
        ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('BAGIAN 2 • PREDIKAT NILAI', PADDING + 10, currY + 11);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 16px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('DISTRIBUSI TINGKAT CAPAIAN KOMPETENSI SISWA', PADDING, currY + 34);
        currY += 48;

        const distContH = 360;
        drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, distContH, 16, '#f8fafc', '#e2e8f0', 1);

        // Donut Chart di Kolom Kiri
        const donutBoxW = 420;
        const donutBoxH = 320;
        const donutAspect = cDist.width / cDist.height;
        let dW = 300;
        let dH = 300 / donutAspect;
        if (dH > donutBoxH) { dH = donutBoxH; dW = dH * donutAspect; }
        ctx.drawImage(cDist, PADDING + 20 + ((donutBoxW - dW) / 2), currY + 20 + ((donutBoxH - dH) / 2), dW, dH);

        // 4 Kartu Predikat di Kolom Kanan
        const rX = PADDING + donutBoxW + 30;
        const rW = CONTENT_WIDTH - donutBoxW - 50;
        const catRows = [
            { label: 'Sangat Baik (≥ 85)', count: dist.counts[0] || 0, pct: dist.percentages[0] || 0, color: '#10b981', border: '#a7f3d0' },
            { label: 'Baik (75 - 84)', count: dist.counts[1] || 0, pct: dist.percentages[1] || 0, color: '#3b82f6', border: '#bfdbfe' },
            { label: 'Cukup (65 - 74)', count: dist.counts[2] || 0, pct: dist.percentages[2] || 0, color: '#f59e0b', border: '#fde68a' },
            { label: 'Perlu Bimbingan (< 65)', count: dist.counts[3] || 0, pct: dist.percentages[3] || 0, color: '#ef4444', border: '#fecaca' }
        ];

        let rowY = currY + 25;
        catRows.forEach(row => {
            drawExportRoundedRect(ctx, rX, rowY, rW, 52, 9, '#ffffff', row.border, 1.2);
            ctx.beginPath();
            ctx.arc(rX + 20, rowY + 26, 6, 0, Math.PI * 2);
            ctx.fillStyle = row.color;
            ctx.fill();

            ctx.fillStyle = '#1e293b';
            ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText(row.label, rX + 36, rowY + 26);

            ctx.textAlign = 'right';
            ctx.font = 'bold 13px "Inter", "Segoe UI", sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.fillText(row.count + ' Siswa (' + row.pct + '%)', rX + rW - 16, rowY + 26);
            ctx.textAlign = 'left';

            rowY += 58;
        });

        drawExportRoundedRect(ctx, rX, rowY + 2, rW, 40, 8, '#f1f5f9', '#cbd5e1', 1);
        ctx.fillStyle = '#334155';
        ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
        ctx.textBaseline = 'middle';
        ctx.fillText('Total Siswa Dinilai: ' + (dist.total_dinilai || 0) + ' Siswa  •  Rata-rata: ' + metaSekolah.rataRataKelas, rX + 14, rowY + 22);

        currY += distContH + 34;

        // 5. Bagian 3: Rentang Nilai
        drawExportRoundedRect(ctx, PADDING, currY, 190, 22, 6, '#fffbeb', '#fde68a', 1);
        ctx.fillStyle = '#b45309';
        ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('BAGIAN 3 • RENTANG NILAI MAPEL', PADDING + 10, currY + 11);

        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 16px "Inter", "Segoe UI", sans-serif';
        ctx.fillText('DISTRIBUSI CAPAIAN PER RENTANG NILAI (0-25, 26-50, 51-75, 76-100) PER MAPEL', PADDING, currY + 34);
        currY += 48;

        const rankH = 280;
        drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, rankH + 40, 16, '#f8fafc', '#e2e8f0', 1);
        ctx.drawImage(cRank, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, rankH);
        currY += rankH + 50;

        // 4 Kartu Rentang Nilai (Kumulatif Mapel / Rata-rata)
        const rentang = rawChartData.rentang_nilai || {};
        const km = rentang.kumulatif_mapel || { counts: rentang.counts || [0, 0, 0, 0], percentages: rentang.percentages || [0, 0, 0, 0] };
        const rCards = [
            { range: '0 - 25', label: 'Perlu Intervensi', count: km.counts[0], pct: km.percentages[0], color: '#ef4444', bg: '#fef2f2', border: '#fecaca' },
            { range: '26 - 50', label: 'Perlu Bimbingan', count: km.counts[1], pct: km.percentages[1], color: '#f97316', bg: '#fff7ed', border: '#fed7aa' },
            { range: '51 - 75', label: 'Cukup / Memadai', count: km.counts[2], pct: km.percentages[2], color: '#3b82f6', bg: '#eff6ff', border: '#bfdbfe' },
            { range: '76 - 100', label: 'Baik & Sangat Baik', count: km.counts[3], pct: km.percentages[3], color: '#10b981', bg: '#ecfdf5', border: '#a7f3d0' }
        ];

        const rCardW = (CONTENT_WIDTH - (3 * 14)) / 4;
        const rCardH = 68;
        rCards.forEach((c, idx) => {
            const cX = PADDING + (idx * (rCardW + 14));
            drawExportMetricCard(ctx, cX, currY, rCardW, rCardH, 'Rentang ' + c.range, c.count + ' Nilai (' + c.pct + '%)', c.label, c.color, c.bg, c.border);
        });
        currY += rCardH + 24;

        // 6. Bagian 4: Kehadiran Siswa (SIA)
        if (cKehadiran && kh) {
            drawExportRoundedRect(ctx, PADDING, currY, 160, 22, 6, '#f0f9ff', '#bae6fd', 1);
            ctx.fillStyle = '#0284c7';
            ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('BAGIAN 4 • KEHADIRAN (SIA)', PADDING + 10, currY + 11);

            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 16px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('REKAPITULASI KEHADIRAN SISWA (SAKIT, IZIN, ALPA)', PADDING, currY + 34);
            currY += 48;

            const khBoxH = 340;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, khBoxH, 16, '#f8fafc', '#e2e8f0', 1);

            // Doughnut Kehadiran di Kolom Kiri
            const khDonutW = 400;
            const khAspect = cKehadiran.width / cKehadiran.height;
            let khW = 280;
            let khH = 280 / khAspect;
            if (khH > 300) { khH = 300; khW = khH * khAspect; }
            ctx.drawImage(cKehadiran, PADDING + 20 + ((khDonutW - khW) / 2), currY + 20 + ((300 - khH) / 2), khW, khH);

            // Rincian SIA di Kolom Kanan
            const khRX = PADDING + khDonutW + 30;
            const khRW = CONTENT_WIDTH - khDonutW - 50;

            const siaExportRows = [
                { label: 'Sakit (S)', count: kh.total_sakit, pct: kh.percentages[0] || 0, color: '#3b82f6', border: '#bfdbfe' },
                { label: 'Izin (I)', count: kh.total_izin, pct: kh.percentages[1] || 0, color: '#f59e0b', border: '#fde68a' },
                { label: 'Tanpa Keterangan / Alpa (A)', count: kh.total_alpa, pct: kh.percentages[2] || 0, color: '#ef4444', border: '#fecaca' }
            ];

            let sY = currY + 22;
            siaExportRows.forEach(row => {
                drawExportRoundedRect(ctx, khRX, sY, khRW, 52, 9, '#ffffff', row.border, 1.2);
                ctx.beginPath();
                ctx.arc(khRX + 20, sY + 26, 6, 0, Math.PI * 2);
                ctx.fillStyle = row.color;
                ctx.fill();

                ctx.fillStyle = '#1e293b';
                ctx.font = 'bold 12px "Inter", "Segoe UI", sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(row.label, khRX + 36, sY + 26);

                ctx.textAlign = 'right';
                ctx.font = 'bold 13px "Inter", "Segoe UI", sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.fillText(row.count + ' Hari (' + row.pct + '%)', khRX + khRW - 16, sY + 26);
                ctx.textAlign = 'left';

                sY += 60;
            });

            // Summary Hadir 100% & Total
            drawExportRoundedRect(ctx, khRX, sY + 4, khRW, 42, 8, '#f1f5f9', '#cbd5e1', 1);
            ctx.fillStyle = '#334155';
            ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
            ctx.textBaseline = 'middle';
            ctx.fillText('Siswa Hadir 100%: ' + kh.siswa_hadir_penuh + ' Siswa (' + kh.persen_hadir_penuh + '%)  •  Total: ' + kh.total_tidak_hadir + ' Hari (Rata-rata: ' + kh.rata_absen + ' hari/siswa)', khRX + 14, sY + 25);

            currY += khBoxH + 34;
        }

        // 7. Bagian 5: Capaian Karakter & Pembiasaan
        if (cPembiasaan && pem) {
            drawExportRoundedRect(ctx, PADDING, currY, 175, 22, 6, '#ecfdf5', '#a7f3d0', 1);
            ctx.fillStyle = '#047857';
            ctx.font = 'bold 10px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('BAGIAN 5 • KARAKTER & PEMBIASAAN', PADDING + 10, currY + 11);

            ctx.fillStyle = '#0f172a';
            ctx.font = 'bold 16px "Inter", "Segoe UI", sans-serif';
            ctx.fillText('CAPAIAN ASPEK KARAKTER & PEMBIASAAN PESERTA DIDIK', PADDING, currY + 34);
            currY += 48;

            const pemH = 340;
            drawExportRoundedRect(ctx, PADDING, currY, CONTENT_WIDTH, pemH + 40, 16, '#f8fafc', '#e2e8f0', 1);
            ctx.drawImage(cPembiasaan, PADDING + 20, currY + 20, CONTENT_WIDTH - 40, pemH);
            currY += pemH + 50;

            // Legenda 4 Warna
            const pemExportLegs = [
                { label: 'Sangat Baik (SB)', color: '#10b981' },
                { label: 'Baik (B)', color: '#3b82f6' },
                { label: 'Cukup (C)', color: '#f59e0b' },
                { label: 'Perlu Bimbingan (PB)', color: '#ef4444' }
            ];
            let pX = PADDING;
            pemExportLegs.forEach(lg => {
                drawExportRoundedRect(ctx, pX, currY, 14, 14, 3, lg.color);
                ctx.font = 'bold 11px "Inter", "Segoe UI", sans-serif';
                ctx.fillStyle = '#1e293b';
                ctx.fillText(lg.label, pX + 22, currY + 7);
                pX += ctx.measureText(lg.label).width + 36;
            });
            currY += 34;
        }

        // 8. Gambar Footer Resmi
        drawExportFooter(ctx, CANVAS_WIDTH, currY, metaSekolah);

        // 9. Unduh File Infografik
        const imageURI = tempCanvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = (filename || 'Infografik_Analisis_Nilai') + '.png';
        link.href = imageURI;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    document.addEventListener('DOMContentLoaded', initRekapCharts);
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initRekapCharts, 50);
    }
</script>
@endpush
