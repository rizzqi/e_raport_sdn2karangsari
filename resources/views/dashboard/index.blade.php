@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">

    <!-- Welcome Hero Banner -->
    <div class="bg-gradient-to-r from-school-darkblue via-blue-900 to-indigo-950 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 mb-3">
                <i class="fa-solid fa-circle-dot mr-1.5 text-emerald-400 animate-pulse"></i> Tahun Ajaran {{ $tahunAktif ? $tahunAktif->nama_lengkap : 'Belum Diatur' }}
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Selamat Datang, {{ $user->name }}
            </h1>
            <p class="text-slate-300 text-sm sm:text-base mt-1 max-w-2xl">
                Aplikasi Raport SD Negeri 2 Karangsari &mdash; Input nilai sekali, hitung otomatis jumlah dan rata-rata, unduh dokumen raport resmi (.docx & PDF) dengan tata letak standar A4.
            </p>
        </div>
        <div class="absolute -right-8 -bottom-10 opacity-10 text-white text-9xl pointer-events-none">
            <i class="fa-solid fa-school"></i>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Siswa Aktif</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalSiswa }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Siswa terdaftar</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Rombongan Belajar</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalKelas }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Kelas aktif</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chalkboard"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Semester Aktif</p>
                <p class="text-xl font-bold text-slate-900 mt-1">{{ $tahunAktif ? ucfirst($tahunAktif->semester) : '-' }}</p>
                <p class="text-xs text-emerald-600 font-medium mt-0.5"><i class="fa-solid fa-check-circle"></i> Periode STS & SAS</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Raport Dibuat</p>
                <p class="text-2xl font-black text-slate-900 mt-1">{{ $totalRaportDicetak }}</p>
                <p class="text-xs text-slate-400 mt-0.5">Riwayat unduhan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-word"></i>
            </div>
        </div>
    </div>

    <!-- Kelas & Progres Raport Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-list-check text-emerald-600 mr-2"></i>
                    Status Pengisian Nilai & Cetak Raport
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar kelas dan progres pengisian nilai rapor semester ini</p>
            </div>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('kelas.index') }}" class="inline-flex items-center text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                    <i class="fa-solid fa-plus mr-1"></i> Kelola Semua Kelas
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4">Kelas & Fase</th>
                        <th class="py-3 px-4">Wali Kelas</th>
                        <th class="py-3 px-4 text-center">Jumlah Siswa</th>
                        <th class="py-3 px-4">Progres Input Nilai (STS)</th>
                        <th class="py-3 px-4 text-center">Rata-rata Kelas</th>
                        <th class="py-3 px-4 text-right">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rekapKelasOverview as $item)
                        @php
                            $k = $item['kelas'];
                            $persen = $item['persen_selesai'];
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                <a href="{{ route('kelas.show', $k->id) }}" class="text-blue-600 hover:underline flex items-center">
                                    <i class="fa-solid fa-door-open text-slate-400 mr-2"></i>
                                    {{ $k->nama }}
                                </a>
                                <span class="inline-block text-[10px] font-semibold text-slate-500 bg-slate-100 px-2 py-0.5 rounded mt-1">
                                    {{ $k->fase ?? 'Fase A' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700">
                                <div class="font-medium">{{ $k->wali->name ?? 'Belum Ditentukan' }}</div>
                                <div class="text-xs text-slate-400">{{ $k->wali->email ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                                {{ $item['total_siswa'] }} Siswa
                            </td>
                            <td class="py-3.5 px-4 min-w-[200px]">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-medium text-slate-700">{{ $item['siswa_selesai'] }} dari {{ $item['total_siswa'] }} siswa selesai</span>
                                    <span class="font-bold text-emerald-600">{{ $persen }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $persen }}%"></div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-800">
                                <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs border border-blue-200">
                                    {{ $item['rata_rata'] }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('kelas.show', $k->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition" title="Lihat Siswa Kelas">
                                    <i class="fa-solid fa-users mr-1"></i> Siswa
                                </a>
                                <a href="{{ route('nilai.input.kelas', $k->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition" title="Input Matriks Nilai">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Input Nilai
                                </a>
                                <a href="{{ route('rekap.kelas', $k->id) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition" title="Rekapitulasi Nilai">
                                    <i class="fa-solid fa-chart-simple mr-1"></i> Rekap
                                </a>
                                <a href="{{ route('raport.download.kelas.docx', ['kelas' => $k->id, 'periode' => 'sts']) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition" title="Unduh Rapor Satu Kelas (.docx)">
                                    <i class="fa-solid fa-file-word mr-1"></i> Word
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                Belum ada rombongan belajar / kelas yang terdaftar pada tahun ajaran ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
