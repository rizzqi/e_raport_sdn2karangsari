@extends('layouts.app')

@section('title', 'Tahun Ajaran')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-calendar-days text-emerald-600 mr-2"></i> Tahun Pelajaran & Semester
            </h1>
            <p class="text-xs text-slate-500">Kelola tahun ajaran dan tetapkan satu periode yang sedang aktif</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Form Tambah Tahun Ajaran -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm h-fit">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center">
                <i class="fa-solid fa-plus-circle text-emerald-600 mr-2"></i> Tambah Tahun Ajaran
            </h2>

            <form action="{{ route('tahun-ajaran.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Tahun Pelajaran *</label>
                    <input type="text" name="tahun" placeholder="Contoh: 2026/2027" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Semester *</label>
                    <select name="semester" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="ganjil">Ganjil</option>
                        <option value="genap">Genap</option>
                    </select>
                </div>

                <div class="flex items-center text-xs text-slate-600">
                    <input type="checkbox" name="aktif" value="1" id="aktif" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                    <label for="aktif" class="cursor-pointer font-medium">Jadikan langsung sebagai tahun ajaran aktif</label>
                </div>

                <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center justify-center">
                    <i class="fa-solid fa-save mr-1.5"></i> Simpan Periode
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Tahun Ajaran -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Daftar Tahun Pelajaran</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                        <tr>
                            <th class="py-3 px-4">Tahun Pelajaran</th>
                            <th class="py-3 px-4">Semester</th>
                            <th class="py-3 px-4 text-center">Jumlah Kelas</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($tahunAjaranList as $ta)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $ta->tahun }}</td>
                                <td class="py-3 px-4 capitalize text-slate-700">{{ $ta->semester }}</td>
                                <td class="py-3 px-4 text-center text-slate-600 font-medium">{{ $ta->kelas_count }} Kelas</td>
                                <td class="py-3 px-4 text-center">
                                    @if($ta->aktif)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <i class="fa-solid fa-check-circle mr-1 text-emerald-600"></i> Aktif
                                        </span>
                                    @else
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                            Tidak Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                                    @if(!$ta->aktif)
                                        <form action="{{ route('tahun-ajaran.set-aktif', $ta->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
                                                Aktifkan
                                            </button>
                                        </form>

                                        <form action="{{ route('tahun-ajaran.destroy', $ta->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">Belum ada tahun ajaran.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
