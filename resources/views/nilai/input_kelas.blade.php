@extends('layouts.app')

@section('title', 'Input Matriks Nilai Kelas ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Input Nilai Per Mapel</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 flex items-center">
                <i class="fa-solid fa-table-cells text-emerald-600 mr-2"></i> Input Cepat Nilai Kelas: {{ $kelas->nama }}
            </h1>
            <p class="text-xs text-slate-500">Pilih mata pelajaran untuk mengisi nilai seluruh siswa secara serentak</p>
        </div>

        <!-- Filter Mapel & Periode -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $activeMapel ? $activeMapel->id : null, 'periode' => 'sts']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS (Tengah)
                </a>
                <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $activeMapel ? $activeMapel->id : null, 'periode' => 'sas']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS (Akhir)
                </a>
            </div>
        </div>
    </div>

    <!-- Pilihan Tab Mapel -->
    <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-none">
        @foreach($mapels as $m)
            <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $m->id, 'periode' => $periode]) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center {{ $activeMapel && $activeMapel->id === $m->id ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                <span class="w-4 h-4 rounded-full bg-white/20 text-center mr-1.5 text-[10px]">{{ $m->urutan }}</span>
                {{ $m->nama }}
            </a>
        @endforeach
    </div>

    @if($activeMapel)
        <!-- Form Matriks Input -->
        <form action="{{ route('nilai.simpan.kelas', $kelas->id) }}" method="POST">
            @csrf
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="mapel_id" value="{{ $activeMapel->id }}">

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-book-bookmark text-emerald-600 mr-2"></i> {{ $activeMapel->nama }} &bull; Periode {{ strtoupper($periode) }}
                        </h2>
                        <p class="text-xs text-slate-400">Total {{ $kelasSiswaList->count() }} siswa di kelas ini</p>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Nilai Mapel Ini
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4 w-28">NIS</th>
                                <th class="py-3 px-4 w-36 text-center">Formatif (0-100)</th>
                                <th class="py-3 px-4 w-36 text-center">Sumatif (0-100)</th>
                                <th class="py-3 px-4 w-32 text-center">Rata-rata Mapel</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kelasSiswaList as $idx => $ks)
                                @php
                                    $n = $nilaiList->get($ks->id);
                                    $fVal = $n && $n->formatif !== null ? (float)$n->formatif : '';
                                    $sVal = $n && $n->sumatif !== null ? (float)$n->sumatif : '';
                                    $rata = '';
                                    if ($fVal !== '' && $sVal !== '') {
                                        $rata = round(($fVal + $sVal) / 2, 2);
                                    } elseif ($sVal !== '') {
                                        $rata = $sVal;
                                    } elseif ($fVal !== '') {
                                        $rata = $fVal;
                                    }
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-bold text-xs">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        {{ $ks->siswa->nama }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $ks->siswa->nis }}</td>
                                    <td class="py-2.5 px-4 text-center">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="formatif[{{ $ks->id }}]"
                                            value="{{ $fVal }}"
                                            placeholder="-"
                                            class="w-full text-center py-1.5 px-2 text-sm font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="sumatif[{{ $ks->id }}]"
                                            value="{{ $sVal }}"
                                            placeholder="-"
                                            class="w-full text-center py-1.5 px-2 text-sm font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-slate-700 bg-slate-50/50">
                                        {{ $rata !== '' ? str_replace('.', ',', (string)$rata) : '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id, 'periode' => $periode]) }}"
                                           class="text-xs text-blue-600 hover:underline font-semibold" title="Input lengkap semua mapel">
                                           Input Lengkap &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                        Belum ada siswa di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Seluruh Nilai
                    </button>
                </div>
            </div>
        </form>
    @endif

</div>
@endsection
