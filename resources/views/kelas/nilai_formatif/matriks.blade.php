@extends('layouts.app')

@section('title', 'Input Cepat Matriks Formatif - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header & Navigasi -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('kelas.nilai-formatif.index', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition" title="Kembali ke Daftar Rapor Formatif">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Input Cepat Matriks
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        Kelas {{ $kelas->nama }} &bull; Periode: {{ $namaBulan }} {{ $tahun }}
                    </span>
                </div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight mt-0.5">
                    Input Cepat Nilai Formatif Semua Mapel
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Wali Kelas: <strong class="text-slate-700">{{ $kelas->nama_wali }}</strong> &bull; Total: <strong class="text-slate-700">{{ $kelasSiswaList->count() }} Siswa</strong>
                </p>
            </div>
        </div>

        <!-- Filter Bulan & Aksi -->
        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('kelas.nilai-formatif.matriks', $kelas->id) }}" method="GET" class="flex items-center space-x-1 bg-slate-50 border border-slate-200 p-1 rounded-xl">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <label for="selectBulan" class="text-xs font-bold text-slate-600 ml-1.5">Bulan:</label>
                <select name="bulan" id="selectBulan" onchange="this.form.submit()" class="bg-white border border-slate-300 text-xs font-semibold rounded-lg px-2 py-1 focus:outline-none">
                    @foreach($daftarBulan as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                    @endforeach
                </select>
            </form>

            <a href="{{ route('kelas.nilai-formatif.cetak-sekelas', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               target="_blank"
               class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs transition flex items-center shadow-sm">
                <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Rapor Sekelas
            </a>
        </div>
    </div>

    <!-- Alert Petunjuk -->
    <div class="bg-amber-50 border border-amber-200 p-3.5 rounded-2xl flex items-start space-x-3 text-xs text-amber-900">
        <i class="fa-solid fa-lightbulb text-amber-600 text-sm mt-0.5 flex-shrink-0"></i>
        <p>
            Isikan nilai formatif untuk seluruh peserta didik dalam tabel matriks di bawah ini. Anda dapat berpindah antar kolom menggunakan tombol <strong>Tab</strong> pada keyboard. Setelah selesai, klik tombol <strong>"Simpan Semua Nilai Matriks"</strong> di bagian bawah.
        </p>
    </div>

    <!-- Formulir Matriks -->
    <form action="{{ route('kelas.nilai-formatif.simpan-matriks', $kelas->id) }}" method="POST">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="bulan" value="{{ $bulan }}">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-3 px-3 w-10 text-center sticky left-0 bg-slate-50 z-10 border-r border-slate-200">No</th>
                            <th class="py-3 px-4 min-w-[200px] sticky left-10 bg-slate-50 z-10 border-r border-slate-200">Nama Peserta Didik</th>
                            @foreach($mapels as $m)
                                @php
                                    $kkm = $kelas->getKkmForMapel($m);
                                @endphp
                                <th class="py-3 px-3 text-center min-w-[90px] border-r border-slate-200">
                                    <div class="truncate max-w-[120px]" title="{{ $m->nama }}">{{ $m->nama }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">KKM: {{ $kkm }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($kelasSiswaList as $idx => $ks)
                            <tr class="hover:bg-indigo-50/20 transition">
                                <td class="py-2.5 px-3 text-center font-bold text-slate-400 sticky left-0 bg-white z-10 border-r border-slate-200">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-2.5 px-4 font-semibold text-slate-800 sticky left-10 bg-white z-10 border-r border-slate-200 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $ks->siswa->nama }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }}</div>
                                </td>
                                @foreach($mapels as $m)
                                    @php
                                        $val = $matrix[$ks->id][$m->id] ?? '';
                                    @endphp
                                    <td class="py-2 px-2 text-center border-r border-slate-200">
                                        <input type="number"
                                               step="0.1"
                                               min="0"
                                               max="100"
                                               name="matriks[{{ $ks->id }}][{{ $m->id }}]"
                                               value="{{ old('matriks.' . $ks->id . '.' . $m->id, $val) }}"
                                               placeholder="-"
                                               class="w-16 text-center font-mono font-bold text-xs px-1.5 py-1 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($mapels) + 2 }}" class="py-12 text-center text-slate-400">
                                    Belum ada data siswa di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Toolbar -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <span class="text-xs text-slate-500">
                    Perubahan nilai akan diterapkan langsung ke lembar <strong>Rapor Formatif Bulanan</strong> siswa.
                </span>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('kelas.nilai-formatif.index', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-100 rounded-xl text-xs font-semibold text-slate-700 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Semua Nilai Matriks
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>
@endsection
