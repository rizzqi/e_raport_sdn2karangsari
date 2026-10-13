@extends('layouts.app')

@section('title', 'Rekap Nilai Kelas ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header Rekap -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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
            <p class="text-xs text-slate-500">Tahun Ajaran: {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Wali: {{ $kelas->wali->name ?? '-' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Pilihan Periode STS / SAS -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS
                </a>
                <a href="{{ route('rekap.kelas', ['kelas' => $kelas->id, 'periode' => 'sas']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS
                </a>
            </div>

            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak Lembar Rekap
            </button>
        </div>
    </div>

    <!-- Statistik Ringkas Rekap -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Rata-rata Kelas</p>
                <p class="text-2xl font-black text-blue-700 mt-1">{{ $rekapData['rata_rata_kelas_format'] }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Nilai sumatif gabungan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Kelengkapan Input</p>
                <p class="text-2xl font-black text-emerald-600 mt-1">{{ $rekapData['persen_selesai'] }}%</p>
                <p class="text-xs text-slate-400 mt-0.5">{{ $rekapData['siswa_selesai'] }} dari {{ $rekapData['jumlah_siswa'] }} siswa lengkap</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Mata Pelajaran</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $rekapData['mapels']->count() }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Mapel aktif dinilai</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-book"></i>
            </div>
        </div>
    </div>

    <!-- Tabel Ledger Rekap Nilai -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-table-list text-slate-600 mr-2"></i> Rekapitulasi Nilai Sumatif Peserta Didik
            </h2>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full uppercase">
                Periode {{ strtoupper($periode) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-3 w-10 text-center">No</th>
                        <th class="py-3 px-3 min-w-[160px]">Nama Siswa</th>
                        <th class="py-3 px-2 w-20">NIS</th>
                        @foreach($rekapData['mapels'] as $m)
                            <th class="py-3 px-2 text-center" title="{{ $m->nama }}">
                                M{{ $m->urutan }}
                            </th>
                        @endforeach
                        <th class="py-3 px-3 text-center bg-slate-100 font-black">Jumlah</th>
                        <th class="py-3 px-3 text-center bg-blue-50 text-blue-900 font-black">Rata-rata</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekapData['siswa_list'] as $idx => $item)
                        @php
                            $s = $item['siswa'];
                            $h = $item['hasil'];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-bold">{{ $idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-900 whitespace-nowrap">
                                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $item['kelas_siswa']->id, 'periode' => $periode]) }}" class="hover:text-emerald-600">
                                    {{ $s->nama }}
                                </a>
                            </td>
                            <td class="py-2.5 px-2 font-mono text-slate-500">{{ $s->nis }}</td>
                            @foreach($h['baris'] as $b)
                                <td class="py-2.5 px-2 text-center font-semibold {{ $b['sumatif'] !== null ? 'text-slate-800' : 'text-slate-300' }}">
                                    {{ $b['sumatif_format'] }}
                                </td>
                            @endforeach
                            <td class="py-2.5 px-3 text-center font-black bg-slate-100/60 text-slate-900">
                                {{ \App\Services\HitungNilaiService::formatAngka($h['total_sumatif']) }}
                            </td>
                            <td class="py-2.5 px-3 text-center font-black bg-blue-50/60 text-blue-800">
                                {{ $h['rata_sumatif_format'] }}
                            </td>
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                @if($h['terisi_semua'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Lengkap</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Belum Lengkap</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $rekapData['mapels']->count() + 6 }}" class="py-8 text-center text-slate-400">
                                Belum ada siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-300">
                    <tr>
                        <td colspan="3" class="py-3 px-3 text-right uppercase">Rata-rata Mapel Kelas:</td>
                        @foreach($rekapData['mapels'] as $m)
                            <td class="py-3 px-2 text-center text-blue-900 font-black">
                                {{ $rekapData['rekap_mapel'][$m->id]['rata_rata_format'] }}
                            </td>
                        @endforeach
                        <td class="py-3 px-3 text-center bg-slate-200/50">-</td>
                        <td class="py-3 px-3 text-center bg-blue-100 text-blue-900 font-black text-sm">
                            {{ $rekapData['rata_rata_kelas_format'] }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Legenda Kode Mapel -->
        <div class="p-4 bg-slate-50 border-t border-slate-100 text-xs text-slate-600">
            <span class="font-bold text-slate-700 block mb-1">Keterangan Singkatan Mata Pelajaran:</span>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 text-[11px]">
                @foreach($rekapData['mapels'] as $m)
                    <div><strong>M{{ $m->urutan }}:</strong> {{ $m->nama }}</div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
