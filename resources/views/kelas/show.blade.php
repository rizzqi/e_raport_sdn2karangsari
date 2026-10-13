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
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">{{ $kelas->nama }}</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                <i class="fa-solid fa-user-tie text-slate-400 mr-1"></i> Wali Kelas: <strong class="text-slate-700">{{ $kelas->nama_wali }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('penempatan.index', $kelas->id) }}" class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center">
                    <i class="fa-solid fa-user-plus mr-1.5 text-emerald-600"></i> Kelola Siswa
                </a>
                <a href="{{ route('nilai.input.kelas', $kelas->id) }}" class="px-3 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm flex items-center">
                    <i class="fa-solid fa-pen-to-square mr-1.5"></i> Input Nilai Cepat
                </a>
            @endif
            <a href="{{ route('rekap.kelas', $kelas->id) }}" class="px-3 py-2 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition flex items-center">
                <i class="fa-solid fa-chart-pie mr-1.5"></i> Rekap Nilai
            </a>
            <a href="{{ route('raport.download.kelas.docx', ['kelas' => $kelas->id, 'periode' => 'sts']) }}" class="px-3 py-2 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition flex items-center">
                <i class="fa-solid fa-file-word mr-1.5"></i> Unduh Raport Kelas (.docx)
            </a>
        </div>
    </div>

    <!-- Tabel Daftar Siswa di Kelas -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-users text-emerald-600 mr-2"></i> Daftar Peserta Didik ({{ $kelas->kelasSiswa->count() }} Siswa)
            </h2>
            <div class="text-xs text-slate-400">
                Pilih siswa untuk menginput nilai atau mencetak rapor individu
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
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 transition"
                                        title="Input Nilai Siswa">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Input Nilai
                                    </a>
                                @endif
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
                                <div class="mt-2">
                                    <a href="{{ route('penempatan.index', $kelas->id) }}" class="inline-flex items-center text-xs font-bold text-emerald-600 hover:underline">
                                        <i class="fa-solid fa-user-plus mr-1"></i> Tempatkan Siswa ke Kelas Ini Sekarang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
