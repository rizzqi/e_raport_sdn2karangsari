@extends('layouts.app')

@section('title', 'Riwayat Cetak Raport')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-clock-rotate-left text-emerald-600 mr-2"></i> Riwayat Pencetakan & Unduhan Raport
            </h1>
            <p class="text-xs text-slate-500">Log pencatatan siapa yang mencetak raport siswa dan kapan dicetak</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Tampilan Kartu Mobile Khusus HP -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($riwayatList as $index => $r)
                <div class="p-3.5 space-y-2 bg-white">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h4 class="font-bold text-xs text-slate-900 truncate">{{ $r->kelasSiswa->siswa->nama ?? 'Siswa dihapus' }}</h4>
                            <p class="text-[10px] text-slate-500 mt-0.5">
                                {{ $r->kelasSiswa->kelas->nama ?? '-' }} &bull; {{ $r->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase shrink-0 {{ strtolower($r->periode) === 'sts' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ strtoupper($r->periode) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <span class="text-[10px] text-slate-400">
                            Oleh: <strong class="text-slate-600">{{ $r->pembuat->name ?? 'Sistem' }}</strong>
                        </span>
                        @if($r->kelas_siswa_id)
                            <a href="{{ route('raport.preview', ['kelasSiswa' => $r->kelas_siswa_id, 'periode' => $r->periode]) }}"
                               class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 transition">
                                <i class="fa-solid fa-file-lines mr-1 text-emerald-600"></i> Buka Raport
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400 text-xs">
                    Belum ada riwayat pencetakan raport.
                </div>
            @endforelse
        </div>

        <!-- Tabel untuk Layar Desktop & Tablet -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">Kelas</th>
                        <th class="py-3 px-4 text-center">Periode</th>
                        <th class="py-3 px-4">Tanggal Unduh</th>
                        <th class="py-3 px-4">Dicetak Oleh</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($riwayatList as $index => $r)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-500 font-medium">
                                {{ $riwayatList->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $r->kelasSiswa->siswa->nama ?? 'Siswa dihapus' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">
                                {{ $r->kelasSiswa->kelas->nama ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if(strtolower($r->periode) === 'sts')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        STS
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-100 text-blue-800 border border-blue-200">
                                        SAS
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600 font-mono">
                                {{ $r->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <span class="font-bold text-slate-800">{{ $r->pembuat->name ?? 'Sistem' }}</span>
                                <span class="text-slate-400 block text-[10px]">{{ $r->pembuat->email ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                @if($r->kelas_siswa_id)
                                    <a href="{{ route('raport.preview', ['kelasSiswa' => $r->kelas_siswa_id, 'periode' => $r->periode]) }}"
                                       class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-xl bg-white hover:bg-emerald-50 text-emerald-700 border border-emerald-200 transition shadow-2xs">
                                       <i class="fa-solid fa-file-lines mr-1.5 text-emerald-600"></i> Buka Raport
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clock-rotate-left text-3xl mb-2 block text-slate-300"></i>
                                <p class="font-bold text-slate-700">Belum ada riwayat pencetakan raport.</p>
                                <p class="text-xs text-slate-400 mt-1">Riwayat akan otomatis tercatat setiap kali ada raport yang diunduh atau dicetak.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($riwayatList->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $riwayatList->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
