@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-graduate text-emerald-600 mr-2"></i>
                @if(auth()->user()->isWali())
                    Data Peserta Didik {{ $kelasList->pluck('nama')->join(', ') }}
                @else
                    Induk Data Peserta Didik
                @endif
            </h1>
            <p class="text-xs text-slate-500">
                @if(auth()->user()->isWali())
                    Daftar siswa resmi rombongan belajar yang Anda ampu
                @else
                    Kelola master siswa, NIS, NISN, dan impor data massal dari file Excel atau clipboard per kelas
                @endif
            </p>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('siswa.template.import') }}" id="topDownloadTemplateBtn"
                    class="inline-flex items-center px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition border border-slate-200" title="Unduh format template Excel/CSV">
                    <i class="fa-solid fa-file-arrow-down mr-1.5 text-emerald-600"></i> Unduh Format Excel
                </a>
                @if(!empty($kelasId) && is_numeric($kelasId))
                    <a href="{{ route('kelas.daftar-hadir', ['kelas' => $kelasId, 'periode' => 'sts']) }}"
                        class="inline-flex items-center px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-bold rounded-xl text-xs transition shadow-sm"
                        title="Cetak atau Unduh Lembar Daftar Hadir Ujian STS kelas ini">
                        <i class="fa-solid fa-clipboard-user mr-1.5 text-amber-600"></i> Daftar Hadir STS
                    </a>
                @endif
                <button onclick="openImportModal()"
                    class="inline-flex items-center px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold rounded-xl text-xs transition shadow-sm">
                    <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600 text-sm"></i> Impor Siswa Per Kelas
                </button>
                <button onclick="document.getElementById('modalTambahSiswa').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition">
                    <i class="fa-solid fa-plus mr-1.5"></i> Tambah Siswa
                </button>
            </div>
        @endif
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form action="{{ route('siswa.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa, NIS, atau NISN..."
                    class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
            </div>

            @if(!auth()->user()->isWali())
                <div class="w-full sm:w-56">
                    <select name="kelas_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Kelas</option>
                        <option value="tanpa_kelas" {{ $kelasId === 'tanpa_kelas' ? 'selected' : '' }}>-- Belum Ditempatkan --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif($kelasList->count() > 1)
                <div class="w-full sm:w-56">
                    <select name="kelas_id" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                        <option value="">Semua Kelas Diampu</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="w-full sm:w-44">
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="lulus" {{ $status === 'lulus' ? 'selected' : '' }}>Lulus</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl text-xs transition">
                Cari Data
            </button>
            @if($search || $status || $kelasId)
                <a href="{{ route('siswa.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tab Navigasi Tingkat Kelas -->
    @if(auth()->user()->isWali())
        @if($kelasList->isNotEmpty())
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between bg-emerald-50 border border-emerald-200/90 rounded-2xl p-4 text-emerald-900 gap-3 shadow-2xs">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-base shadow-sm">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div>
                        <span class="font-extrabold text-sm block leading-tight text-slate-900">
                            {{ $kelasList->pluck('nama')->join(', ') }} ({{ $kelasList->first()->tahunAjaran->nama_lengkap ?? '-' }})
                        </span>
                        <span class="text-slate-600 text-xs">
                            Wali Kelas: <strong class="text-emerald-800">{{ auth()->user()->name }}</strong> &bull; Total: <strong class="text-emerald-800">{{ $totalSiswaCount }} Siswa Terdaftar</strong>
                        </span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('kelas.show', $kelasList->first()->id) }}"
                        class="inline-flex items-center px-3.5 py-2 bg-white text-emerald-700 hover:text-emerald-800 border border-emerald-300 hover:border-emerald-400 font-bold rounded-xl text-xs transition shadow-2xs">
                        <i class="fa-solid fa-users mr-1.5"></i> Buka Rombel
                    </a>
                    <a href="{{ route('rekap.kelas', $kelasList->first()->id) }}"
                        class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm">
                        <i class="fa-solid fa-chart-column mr-1.5"></i> Rekap Nilai
                    </a>
                </div>
            </div>
        @endif
    @else
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 scrollbar-none">
            <a href="{{ route('siswa.index', array_merge(request()->except(['kelas_id', 'page']), [])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center {{ empty($kelasId) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
               <i class="fa-solid fa-users mr-1.5"></i> Semua Siswa
               <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ empty($kelasId) ? 'bg-slate-800 text-slate-200' : 'bg-slate-100 text-slate-600 font-bold' }}">
                   {{ $totalSiswaCount }}
               </span>
            </a>
            @foreach($kelasList as $k)
                <a href="{{ route('siswa.index', array_merge(request()->except('page'), ['kelas_id' => $k->id])) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center {{ (string)$kelasId === (string)$k->id ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                   <i class="fa-solid fa-graduation-cap mr-1.5 {{ (string)$kelasId === (string)$k->id ? 'text-white' : 'text-emerald-600' }}"></i>
                   {{ $k->nama }}
                   <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ (string)$kelasId === (string)$k->id ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-600 font-bold' }}">
                       {{ $k->kelas_siswa_count }}
                   </span>
                </a>
            @endforeach
            <a href="{{ route('siswa.index', array_merge(request()->except('page'), ['kelas_id' => 'tanpa_kelas'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center {{ $kelasId === 'tanpa_kelas' ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
               <i class="fa-solid fa-user-clock mr-1.5 {{ $kelasId === 'tanpa_kelas' ? 'text-white' : 'text-amber-600' }}"></i>
               Belum Ada Kelas
               <span class="ml-1.5 px-2 py-0.5 rounded-full text-[10px] {{ $kelasId === 'tanpa_kelas' ? 'bg-amber-700 text-white' : 'bg-amber-50 text-amber-700 font-bold' }}">
                   {{ $tanpaKelasCount }}
               </span>
            </a>
        </div>
    @endif

    @if($kelasId === 'tanpa_kelas')
        <div class="bg-amber-50/90 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900 shadow-2xs flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 text-sm shadow-sm">
                    <i class="fa-solid fa-lightbulb"></i>
                </div>
                <div>
                    <h4 class="font-bold text-amber-950 text-sm">Cara Menempatkan Siswa ke Rombel Kelas:</h4>
                    <p class="text-amber-800 mt-0.5">
                        &bull; <strong>Per Siswa:</strong> Klik tombol <strong class="bg-white px-1.5 py-0.5 rounded border border-amber-300 text-amber-900 font-semibold">+ Masukkan Kelas</strong> pada baris siswa di bawah.<br>
                        &bull; <strong>Banyak Siswa Sekaligus:</strong> Buka halaman <strong>Penempatan Siswa</strong> pada rombel kelas terkait untuk mencentang banyak siswa sekaligus.
                    </p>
                </div>
            </div>
            @if(auth()->user()->isAdmin() && $kelasList->isNotEmpty())
                <div class="flex items-center flex-wrap gap-1.5 shrink-0">
                    <span class="text-[11px] font-bold text-amber-800 mr-1">Buka Penempatan:</span>
                    @foreach($kelasList as $k)
                        <a href="{{ route('penempatan.index', $k->id) }}"
                            class="px-2.5 py-1 rounded-lg bg-white hover:bg-amber-100 text-amber-900 font-bold border border-amber-300 transition text-[11px] shadow-2xs">
                            {{ $k->nama }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Tabel Siswa -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- Tampilan Kartu Siswa Khusus HP / Layar Kecil -->
        <div class="block md:hidden divide-y divide-slate-100">
            @forelse($siswaList as $index => $s)
                <div class="p-3.5 space-y-2 bg-white">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                {{ substr($s->nama, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs text-slate-900 truncate">{{ $s->nama }}</h4>
                                <p class="text-[10px] text-slate-500 font-mono mt-0.5">
                                    NIS: <strong class="text-slate-700">{{ $s->nis }}</strong> &bull; NISN: {{ $s->nisn ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            @if($s->status === 'aktif')
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600">Lulus</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                        <div>
                            @if($s->kelasSiswa->isNotEmpty())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg font-bold text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <i class="fa-solid fa-graduation-cap text-[9px] mr-1 text-emerald-600"></i>
                                    {{ $s->kelasSiswa->last()->kelas->nama ?? '-' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 italic">
                                    Belum Ada Kelas
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5">
                            @if(auth()->user()->isAdmin())
                                <button onclick="editSiswa({{ json_encode($s) }})"
                                    class="px-2.5 py-1 text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg transition" title="Ubah">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                </button>
                                <form action="{{ route('siswa.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa {{ $s->nama }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-rose-500 hover:text-rose-700 transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            @else
                                @php
                                    $targetKs = $s->kelasSiswa->first(function ($ks) use ($kelasList) {
                                        return $kelasList->contains('id', $ks->kelas_id);
                                    }) ?: $s->kelasSiswa->last();
                                @endphp
                                @if($targetKs)
                                    <a href="{{ route('raport.preview', $targetKs->id) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition" title="Lihat Raport Siswa">
                                        <i class="fa-solid fa-file-lines mr-1 text-emerald-600"></i> Raport
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-slate-400">
                    Tidak ada data siswa yang sesuai pencarian.
                </div>
            @endforelse
        </div>

        <!-- Tabel untuk Layar Desktop & Tablet -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">NISN</th>
                        <th class="py-3 px-4">Kelas Terakhir</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($siswaList as $index => $s)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-500 font-medium">
                                {{ $siswaList->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $s->nama }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-700">
                                <div class="flex items-center space-x-1.5">
                                    <span>{{ $s->nis }}</span>
                                    @if(str_starts_with($s->nis, 'TMP-') || str_starts_with($s->nis, 'TEMP-'))
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-sans font-semibold bg-amber-100 text-amber-800 border border-amber-200" title="NIS Sementara - Silakan perbarui jika NIS resmi sudah terbit">
                                            Sementara
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-500">{{ $s->nisn ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-xs">
                                @if($s->kelasSiswa->isNotEmpty())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg font-bold text-xs bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-graduation-cap text-[10px] mr-1 text-emerald-600"></i>
                                        {{ $s->kelasSiswa->last()->kelas->nama ?? '-' }}
                                    </span>
                                    <span class="text-slate-400 text-[10px] block mt-0.5">TP: {{ $s->kelasSiswa->last()->kelas->tahunAjaran->tahun ?? '-' }}</span>
                                @else
                                    @if(auth()->user()->isAdmin())
                                        <button type="button" onclick="editSiswa({{ json_encode($s) }})"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 transition shadow-2xs group"
                                            title="Klik untuk langsung menempatkan siswa ini ke kelas">
                                            <i class="fa-solid fa-circle-plus text-amber-600 mr-1.5 group-hover:scale-110 transition-transform"></i>
                                            + Masukkan Kelas
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 italic">
                                            <i class="fa-solid fa-clock text-[9px] mr-1"></i> Belum Ditempatkan
                                        </span>
                                    @endif
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($s->status === 'aktif')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Lulus</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                @if(auth()->user()->isAdmin())
                                    <button onclick="editSiswa({{ json_encode($s) }})"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Ubah">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('siswa.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa {{ $s->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @else
                                    @php
                                        $targetKs = $s->kelasSiswa->first(function ($ks) use ($kelasList) {
                                            return $kelasList->contains('id', $ks->kelas_id);
                                        }) ?: $s->kelasSiswa->last();
                                    @endphp
                                    @if($targetKs)
                                        <a href="{{ route('raport.preview', $targetKs->id) }}" class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition" title="Lihat Raport Siswa">
                                            <i class="fa-solid fa-file-lines mr-1.5 text-emerald-600"></i> Raport
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Lihat Saja</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data siswa yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswaList->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $siswaList->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah Siswa -->
    <div id="modalTambahSiswa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tambah Siswa Baru</h3>
                <button onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('siswa.store') }}" method="POST" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama" placeholder="Contoh: Muhammad Rizqi Pratama" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Nomor Induk Siswa (NIS)</label>
                        <span class="text-[11px] text-amber-700 font-semibold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Boleh Dikosongkan</span>
                    </div>
                    <div class="relative">
                        <input type="text" name="nis" id="tambah_nis" placeholder="Kosongkan jika belum ada (Auto-generate)"
                            class="w-full pr-28 px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
                        <button type="button" onclick="generateTempNisFrontend()"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded-lg transition"
                            title="Generate NIS Sementara otomatis">
                            <i class="fa-solid fa-wand-magic-sparkles mr-1 text-amber-600"></i> Auto NIS
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Jika dikosongkan, sistem akan otomatis membuatkan <strong>NIS Sementara</strong> (format: <code>TMP-{{ date('Y') }}-xxxx</code>) yang bisa diperbarui kapan saja.
                    </p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">NISN (Nasional)</label>
                        <span class="text-[11px] text-slate-400 font-normal">Opsional</span>
                    </div>
                    <input type="text" name="nisn" placeholder="Contoh: 0158291823"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Siswa baru Kelas 1 biasanya belum memiliki NISN dari Dapodik. Boleh dikosongkan terlebih dahulu.
                    </p>
                </div>

                <!-- Pilihan Penempatan Kelas -->
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-900">
                        <i class="fa-solid fa-users-rectangle mr-1 text-emerald-600"></i> Tempatkan Langsung ke Kelas
                    </label>
                    <select name="kelas_id" id="tambah_kelas_id"
                        class="w-full px-3 py-2 border border-emerald-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white text-slate-800">
                        <option value="">-- Tanpa Kelas (Hanya Simpan ke Induk) --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)($kelasId ?? '') === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-emerald-700">
                        Pilih rombel (misal <strong>Kelas 1</strong>) agar siswa langsung masuk ke kelas tersebut tanpa harus ke menu Penempatan Siswa.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Status Keaktifan *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="aktif">Aktif</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                        Simpan Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Siswa -->
    <div id="modalEditSiswa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Ubah Data Siswa</h3>
                <button onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form id="formEditSiswa" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama" id="edit_nama" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">Nomor Induk Siswa (NIS) *</label>
                        <span class="text-[11px] text-slate-400 font-normal">Wajib Unik</span>
                    </div>
                    <input type="text" name="nis" id="edit_nis" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
                    <p class="text-[11px] text-slate-500 mt-1">Ganti jika siswa sebelumnya memakai NIS Sementara dan nomor induk resmi sekolah sudah terbit.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">NISN (Nasional)</label>
                        <span class="text-[11px] text-slate-400 font-normal">Opsional</span>
                    </div>
                    <input type="text" name="nisn" id="edit_nisn" placeholder="Kosongkan jika belum ada di Dapodik"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 font-mono">
                </div>

                <!-- Dropdown Penempatan / Pindah Kelas -->
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-900">
                        <i class="fa-solid fa-users-rectangle mr-1 text-emerald-600"></i> Rombongan Belajar / Kelas
                    </label>
                    <select name="kelas_id" id="edit_kelas_id"
                        class="w-full px-3 py-2 border border-emerald-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white text-slate-800">
                        <option value="">-- Tetap / Tidak Diubah --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}">
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-emerald-700">
                        Pilih rombel (misal <strong>Kelas 1</strong>) untuk menempatkan atau memindahkan siswa ke rombel tersebut.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Status Keaktifan *</label>
                    <select name="status" id="edit_status" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="aktif">Aktif</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Siswa Per Kelas (File Excel & Clipboard) -->
    <div id="modalImportClipboard" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl p-6 border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-file-excel text-emerald-600 mr-2 text-base"></i> Impor Data Siswa Per Kelas
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Unggah file Excel atau tempelkan tabel data siswa sesuai kelas tujuan.</p>
                </div>
                <button onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
                @csrf

                <!-- Dropdown Pemilihan Kelas Tujuan -->
                <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-emerald-900">
                        <i class="fa-solid fa-users-rectangle mr-1 text-emerald-600"></i> Mode Penempatan Kelas *
                    </label>
                    <select name="kelas_id" id="modal_import_kelas_id" onchange="syncTemplateLink(this.value)"
                        class="w-full px-3 py-2 border border-emerald-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 bg-white text-slate-800">
                        <option value="auto" {{ empty($kelasId) || $kelasId === 'tanpa_kelas' ? 'selected' : '' }}>
                            ✨ Otomatis Sesuai Kolom Kelas di File Excel (Pisahkan ke Tingkat Masing-Masing)
                        </option>
                        <option value="">-- Tanpa Penempatan Kelas (Hanya Simpan ke Induk Siswa) --</option>
                        <optgroup label="Atau Paksa Seluruh Siswa Masuk ke Kelas Tertentu:">
                            @foreach($kelasList as $k)
                                <option value="{{ $k->id }}" {{ (string)($kelasId ?? '') === (string)$k->id ? 'selected' : '' }}>
                                    {{ $k->nama }} &bull; TP {{ $k->tahunAjaran->nama_lengkap ?? '-' }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <p class="text-[11px] text-emerald-700 flex items-center">
                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i>
                        Mode <strong>Otomatis</strong> membaca kolom <strong>Kelas</strong> di file Excel/teks (contoh: <em>Kelas 1, Kelas 2, 3, IV</em>) lalu menempatkan tiap siswa ke tingkat kelasnya.
                    </p>
                </div>

                <!-- Pilihan Mode Input (Tab: File Excel vs Clipboard) -->
                <div>
                    <div class="flex border-b border-slate-200 mb-3 text-xs font-bold">
                        <button type="button" id="tabBtnFile" onclick="switchImportTab('file')"
                            class="pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center transition">
                            <i class="fa-solid fa-file-arrow-up mr-1.5"></i> Unggah File Excel / CSV
                        </button>
                        <button type="button" id="tabBtnText" onclick="switchImportTab('text')"
                            class="pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center transition">
                            <i class="fa-solid fa-paste mr-1.5"></i> Tempel dari Clipboard (Salin/Paste)
                        </button>
                    </div>

                    <!-- Panel Unggah File Excel / CSV -->
                    <div id="panelImportFile" class="space-y-3">
                        <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-5 text-center transition bg-slate-50/50">
                            <i class="fa-solid fa-file-excel text-emerald-600 text-3xl mb-2"></i>
                            <p class="text-xs font-semibold text-slate-700">Pilih file Excel (.xlsx) atau CSV (.csv)</p>
                            <p class="text-[11px] text-slate-400 mt-0.5">Maksimal ukuran file 10 MB</p>
                            <input type="file" name="file_excel" id="file_excel_input" accept=".xlsx,.csv,.txt"
                                class="mt-2 text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                        </div>

                        <div class="flex items-center justify-between text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                            <div class="text-slate-600 text-[11px]">
                                <span class="font-bold">Format Kolom:</span> Nama Siswa, NIS, NISN, Kelas
                            </div>
                            <a id="modalDownloadTemplateBtn" href="{{ route('siswa.template.import') }}"
                                class="inline-flex items-center text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-white px-2.5 py-1 rounded-lg border border-emerald-300 transition shadow-sm">
                                <i class="fa-solid fa-download mr-1"></i> Unduh Template Excel (.csv)
                            </a>
                        </div>
                    </div>

                    <!-- Panel Tempel Teks Clipboard -->
                    <div id="panelImportText" class="space-y-3 hidden">
                        <div class="bg-amber-50 border border-amber-200 p-2.5 rounded-xl text-[11px] text-amber-800">
                            <p class="font-bold mb-0.5"><i class="fa-solid fa-circle-info mr-1"></i> Format Salinan:</p>
                            <p>Salin dari baris Excel/Sheets dengan kolom: <span class="font-mono font-bold bg-white px-1 py-0.5 rounded border border-amber-300">Nama Siswa [TAB] NIS [TAB] NISN [TAB] Kelas</span></p>
                        </div>

                        <div>
                            <textarea name="data_teks" id="data_teks_input" rows="5" placeholder="Contoh:&#10;Ahmad Fauzi	1001	0151234501	Kelas 1&#10;Siti Rahma	1002	0151234502	Kelas 1&#10;Budi Santoso	2001	0151234503	Kelas 2&#10;Citra Lestari	3001	0151234504	Kelas 3"
                                class="w-full p-2.5 font-mono border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <p class="text-[11px] text-slate-400">
                        NIS yang sudah ada akan diperbarui.
                    </p>
                    <div class="flex space-x-2">
                        <button type="button" onclick="closeImportModal()"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                            <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Proses Impor Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function generateTempNisFrontend() {
        const year = new Date().getFullYear();
        const rand = Math.floor(1000 + Math.random() * 9000);
        const inputNis = document.getElementById('tambah_nis');
        if (inputNis) {
            inputNis.value = 'TMP-' + year + '-' + rand;
            inputNis.focus();
        }
    }

    function editSiswa(siswa) {
        document.getElementById('formEditSiswa').action = '/siswa/' + siswa.id;
        document.getElementById('edit_nama').value = siswa.nama;
        document.getElementById('edit_nis').value = siswa.nis;
        document.getElementById('edit_nisn').value = siswa.nisn || '';
        document.getElementById('edit_status').value = siswa.status;
        const selectKelas = document.getElementById('edit_kelas_id');
        if (selectKelas) {
            const ks = (siswa.kelas_siswa && siswa.kelas_siswa.length > 0) ? siswa.kelas_siswa[siswa.kelas_siswa.length - 1] : null;
            selectKelas.value = ks ? ks.kelas_id : '';
        }
        document.getElementById('modalEditSiswa').classList.remove('hidden');
    }

    function openImportModal(kelasId = null) {
        const modal = document.getElementById('modalImportClipboard');
        const select = document.getElementById('modal_import_kelas_id');
        if (select) {
            if (kelasId && kelasId !== 'tanpa_kelas') {
                select.value = kelasId;
            } else if (!select.value) {
                select.value = 'auto';
            }
        }
        syncTemplateLink(select ? select.value : 'auto');
        modal.classList.remove('hidden');
    }

    function closeImportModal() {
        document.getElementById('modalImportClipboard').classList.add('hidden');
    }

    function switchImportTab(tab) {
        const btnFile = document.getElementById('tabBtnFile');
        const btnText = document.getElementById('tabBtnText');
        const panelFile = document.getElementById('panelImportFile');
        const panelText = document.getElementById('panelImportText');

        if (tab === 'file') {
            btnFile.className = "pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center transition font-bold";
            btnText.className = "pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center transition";
            panelFile.classList.remove('hidden');
            panelText.classList.add('hidden');
        } else {
            btnText.className = "pb-2 px-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center transition font-bold";
            btnFile.className = "pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center transition";
            panelText.classList.remove('hidden');
            panelFile.classList.add('hidden');
        }
    }

    function syncTemplateLink(kelasId) {
        const baseUrl = "{{ route('siswa.template.import') }}";
        const url = (kelasId && kelasId !== 'auto') ? `${baseUrl}?kelas_id=${kelasId}` : baseUrl;
        const btnModal = document.getElementById('modalDownloadTemplateBtn');
        const btnTop = document.getElementById('topDownloadTemplateBtn');
        if (btnModal) btnModal.href = url;
        if (btnTop && (!kelasId || kelasId === 'auto')) btnTop.href = baseUrl;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_import') === '1') {
            openImportModal(urlParams.get('kelas_id'));
        }
    });
</script>
@endpush
@endsection
