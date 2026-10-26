@extends('layouts.app')

@section('title', 'Absensi Bulanan - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header & Navigasi -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:text-emerald-600">Daftar Kelas</a>
                <span>/</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:text-emerald-600">{{ $kelas->nama }}</a>
                <span>/</span>
                <span class="text-slate-700 font-semibold">Absensi Bulanan</span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-calendar-check text-blue-600 mr-2"></i> Presensi / Absensi Bulanan {{ $kelas->nama }}
            </h1>
            <p class="text-xs text-slate-500">
                Wali Kelas: <span class="font-bold text-slate-700">{{ $kelas->wali_nama ?? ($kelas->wali->name ?? 'Belum ditentukan') }}</span> |
                Tahun Ajaran: <span class="font-bold text-slate-700">{{ $kelas->tahunAjaran->tahun ?? '-' }} ({{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }})</span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.absensi.cetak', ['kelas' => $kelas->id, 'bulan' => $bulan, 'tahun' => $tahun]) }}" target="_blank"
                class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs transition flex items-center shadow-sm">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak Lembar {{ $namaBulan }}
            </a>
            <form action="{{ route('kelas.absensi.sync', $kelas->id) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition flex items-center shadow-sm"
                    title="Hitung ulang dan sinkronkan total kehadiran ke rapor STS & SAS">
                    <i class="fa-solid fa-rotate mr-1.5"></i> Sinkronkan ke Rapor
                </button>
            </form>
            <a href="{{ route('kelas.show', $kelas->id) }}"
                class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Kelas
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center shadow-sm">
            <i class="fa-solid fa-circle-check text-emerald-500 mr-2 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Pilih Bulan & Tahun Ajaran -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center">
                <i class="fa-solid fa-filter text-blue-500 mr-1.5"></i> Pilih Bulan Absensi (Tahun {{ $tahun }})
            </span>
            <div class="flex items-center space-x-2">
                <form method="GET" action="{{ route('kelas.absensi.index', $kelas->id) }}" class="flex items-center space-x-2">
                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                    <label class="text-xs text-slate-500">Tahun:</label>
                    <input type="number" name="tahun" value="{{ $tahun }}" min="2020" max="2050"
                        onchange="this.form.submit()"
                        class="w-24 px-2 py-1 text-xs border border-slate-300 rounded-lg text-center font-bold">
                </form>
            </div>
        </div>

        <!-- Tombol Tabs Bulan -->
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-12 gap-1.5">
            @foreach($daftarBulan as $bKey => $bNama)
                <a href="{{ route('kelas.absensi.index', ['kelas' => $kelas->id, 'bulan' => $bKey, 'tahun' => $tahun]) }}"
                   class="px-2 py-2 text-center rounded-xl text-xs font-bold transition {{ $bulan === $bKey ? 'bg-blue-600 text-white shadow-sm ring-2 ring-blue-300' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200/60' }}">
                    {{ substr($bNama, 0, 3) }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Form Input Absensi Bulan Terpilih -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <form action="{{ route('kelas.absensi.simpan', $kelas->id) }}" method="POST">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="bulan" value="{{ $bulan }}">

            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-user-check text-blue-600 mr-2"></i> Input Ketidakhadiran Bulan {{ $namaBulan }} {{ $tahun }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Isikan jumlah hari tidak hadir. Otomatis menjumlahkan ke rapor saat disimpan.</p>
                </div>

                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center justify-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Absensi Bulan {{ $namaBulan }}
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                        <tr>
                            <th class="py-3 px-3 w-12 text-center">No</th>
                            <th class="py-3 px-4 min-w-[200px]">Nama Peserta Didik</th>
                            <th class="py-3 px-3 w-28">NIS</th>
                            <th class="py-3 px-3 w-28 text-center bg-amber-50/60 text-amber-900">Sakit (Hari)</th>
                            <th class="py-3 px-3 w-28 text-center bg-blue-50/60 text-blue-900">Izin (Hari)</th>
                            <th class="py-3 px-3 w-28 text-center bg-rose-50/60 text-rose-900">Alpa (Hari)</th>
                            <th class="py-3 px-3 w-28 text-center bg-slate-100 text-slate-700">Total Bulan Ini</th>
                            <th class="py-3 px-3 w-36 text-center text-slate-500">Akumulasi {{ $tahun }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($kelasSiswaList as $idx => $ks)
                            @php
                                $item = $kehadiranBulanIni->get($ks->id);
                                $s = $item ? $item->sakit : 0;
                                $i = $item ? $item->izin : 0;
                                $a = $item ? $item->alpa : 0;
                                $rekap = $rekapPerSiswa[$ks->id] ?? ['sakit' => 0, 'izin' => 0, 'alpa' => 0, 'total' => 0];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-3 text-center text-slate-400 font-bold text-xs">{{ $idx + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $ks->siswa->nama }}
                                </td>
                                <td class="py-3 px-3 font-mono text-xs text-slate-500">{{ $ks->siswa->nis }}</td>
                                <td class="py-2 px-3 text-center bg-amber-50/30">
                                    <input type="number" min="0" max="31"
                                        name="sakit[{{ $ks->id }}]"
                                        value="{{ $s }}"
                                        class="w-20 text-center py-1.5 px-2 text-sm font-bold border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 bg-white">
                                </td>
                                <td class="py-2 px-3 text-center bg-blue-50/30">
                                    <input type="number" min="0" max="31"
                                        name="izin[{{ $ks->id }}]"
                                        value="{{ $i }}"
                                        class="w-20 text-center py-1.5 px-2 text-sm font-bold border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                                </td>
                                <td class="py-2 px-3 text-center bg-rose-50/30">
                                    <input type="number" min="0" max="31"
                                        name="alpa[{{ $ks->id }}]"
                                        value="{{ $a }}"
                                        class="w-20 text-center py-1.5 px-2 text-sm font-bold border border-rose-300 rounded-lg focus:ring-2 focus:ring-rose-500 bg-white">
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-slate-700 bg-slate-50/60">
                                    {{ $s + $i + $a }} hr
                                </td>
                                <td class="py-3 px-3 text-center text-xs text-slate-500">
                                    <span class="text-amber-700 font-semibold" title="Sakit">S:{{ $rekap['sakit'] }}</span>
                                    <span class="mx-1 text-slate-300">|</span>
                                    <span class="text-blue-700 font-semibold" title="Izin">I:{{ $rekap['izin'] }}</span>
                                    <span class="mx-1 text-slate-300">|</span>
                                    <span class="text-rose-700 font-semibold" title="Alpa">A:{{ $rekap['alpa'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                    Belum ada siswa di kelas ini. Silakan tambahkan siswa terlebih dahulu di menu Penempatan Siswa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    Total Siswa: <strong class="text-slate-800">{{ $kelasSiswaList->count() }}</strong> anak
                </span>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Seluruh Absensi Bulan {{ $namaBulan }}
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
