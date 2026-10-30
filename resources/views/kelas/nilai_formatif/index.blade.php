@extends('layouts.app')

@section('title', 'Rapor Formatif Bulanan - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header & Navigasi -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('kelas.show', $kelas->id) }}" class="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition" title="Kembali ke Kelas">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                        Formatif Bulanan
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Semester {{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }}
                    </span>
                </div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mt-0.5">
                    Rapor Formatif Bulanan Kelas {{ $kelas->nama }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Wali Kelas: <strong class="text-slate-700">{{ $kelas->nama_wali }}</strong> &bull; Total: <strong class="text-slate-700">{{ $kelasSiswaList->count() }} Siswa</strong>
                </p>
            </div>
        </div>

        <!-- Filter Periode Bulan & Tahun -->
        <form action="{{ route('kelas.nilai-formatif.index', $kelas->id) }}" method="GET" class="flex flex-wrap items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-200">
            <div class="flex items-center space-x-1">
                <i class="fa-regular fa-calendar-days text-slate-400 text-xs ml-2"></i>
                <label for="bulan" class="text-xs font-bold text-slate-600 whitespace-nowrap">Bulan:</label>
                <select name="bulan" id="bulan" onchange="this.form.submit()" class="bg-white border border-slate-300 text-xs font-semibold rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    @foreach($daftarBulan as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-1">
                <label for="tahun" class="text-xs font-bold text-slate-600">Tahun:</label>
                <input type="number" name="tahun" id="tahun" value="{{ $tahun }}" min="2020" max="2035" class="w-20 bg-white border border-slate-300 text-xs font-semibold rounded-xl px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-filter mr-1"></i> Terapkan
            </button>
        </form>
    </div>

    <!-- Toolbar Aksi Utama -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Cetak Rekap Semester (1 Siswa 1 Lembar F4 Sekelas) -->
            <a href="{{ route('kelas.nilai-formatif.rekap-semester-sekelas', $kelas->id) }}"
               target="_blank"
               class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
               title="Cetak lembar rekap nilai semester otomatis seluruh siswa (1 Siswa 1 Lembar F4)">
                <i class="fa-solid fa-file-lines mr-2 text-amber-300"></i> Cetak Rekap Semester (F4)
            </a>

            <!-- Tombol Cetak Rapor Sekelas (UTS Style) -->
            <a href="{{ route('kelas.nilai-formatif.cetak-sekelas', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               target="_blank"
               class="px-4 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
               title="Cetak lembar rapor formatif bulanan untuk seluruh siswa sekaligus">
                <i class="fa-solid fa-print mr-2 text-amber-400"></i> Cetak Rapor Bulanan Sekelas
            </a>

            <!-- Tombol Cetak Rekap Bulanan (Leger 1 Halaman) -->
            <a href="{{ route('kelas.nilai-formatif.cetak-rekap', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               target="_blank"
               class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
               title="Cetak lembar rekapitulasi nilai bulanan seluruh siswa dalam 1 halaman">
                <i class="fa-solid fa-file-contract mr-2 text-white"></i> Cetak Rekap Bulanan (Leger)
            </a>

            <!-- Tombol Input Cepat Matriks -->
            <a href="{{ route('kelas.nilai-formatif.matriks', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="px-4 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-xs border border-slate-300 shadow-2xs transition flex items-center"
               title="Input nilai secara cepat dalam bentuk tabel matriks">
                <i class="fa-solid fa-table-cells mr-2 text-indigo-600"></i> Input Cepat Matriks
            </a>

            <!-- Tombol Kelola Rincian Nilai Harian -->
            @if($mapels->isNotEmpty())
                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapels->first()->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                   class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
                   title="Kelola rincian penilaian harian per mapel untuk bulan {{ $namaBulan }}">
                    <i class="fa-solid fa-list-check mr-2 text-amber-300"></i> Kelola Rincian Harian
                </a>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Unduh Format Excel -->
            <a href="{{ route('kelas.nilai-formatif.template', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-xl text-xs border border-emerald-300 transition flex items-center shadow-2xs">
                <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600"></i> Unduh Format Excel
            </a>

            <!-- Tombol Impor Excel -->
            <button type="button" onclick="document.getElementById('modalImporExcel').classList.remove('hidden')"
               class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 font-bold rounded-xl text-xs border border-blue-300 transition flex items-center shadow-2xs">
                <i class="fa-solid fa-file-import mr-1.5 text-blue-600"></i> Impor Nilai Excel
            </button>
        </div>
    </div>

    <!-- Ringkasan Kelengkapan Penilaian Bulan Terpilih -->
    <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 text-white p-5 rounded-2xl shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-slate-950 uppercase tracking-wider">
                        Periode Penilaian
                    </span>
                    <h3 class="text-base font-bold">{{ $namaBulan }} {{ $tahun }}</h3>
                </div>
                <p class="text-xs text-indigo-200">
                    Format rapor bulanan ini disusun menyerupai <strong>Rapor UTS / STS</strong> (mencakup nilai seluruh mapel, KKM, ketuntasan, kehadiran bulanan, serta 3 kolom tanda tangan resmi).
                </p>
            </div>

            <div class="flex items-center space-x-4 bg-white/10 px-4 py-3 rounded-xl backdrop-blur-xs border border-white/10">
                <div class="text-right">
                    <div class="text-xl font-black text-amber-400">{{ $totalSelesai }} / {{ $kelasSiswaList->count() }}</div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-indigo-200">Rapor Siap Cetak</div>
                </div>
                <div class="w-16 h-16 relative flex items-center justify-center">
                    <div class="text-xs font-black">{{ $persenSelesai }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Siswa & Rapor Formatif -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-users-viewfinder text-indigo-600"></i>
                <h3 class="font-bold text-sm text-slate-900">Daftar Rapor Peserta Didik ({{ $namaBulan }} {{ $tahun }})</h3>
            </div>
            <span class="text-xs text-slate-500">
                Klik <strong>"Input Nilai"</strong> untuk mengisi atau <strong>"Lihat Rapor"</strong> untuk mencetak per siswa.
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/80">
                        <th class="py-3 px-3 w-12 text-center">No</th>
                        <th class="py-3 px-3 w-28">NIS / NISN</th>
                        <th class="py-3 px-4">Nama Peserta Didik</th>
                        <th class="py-3 px-3 text-center w-36">Kelengkapan Mapel</th>
                        <th class="py-3 px-3 text-center w-28">Rata-rata</th>
                        <th class="py-3 px-3 text-center w-32">Status Rapor</th>
                        <th class="py-3 px-4 text-center w-64">Aksi Rapor Formatif</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($kelasSiswaList as $idx => $ks)
                        @php
                            $st = $statusSiswa[$ks->id] ?? ['terisi' => 0, 'total_mapel' => $mapels->count(), 'is_complete' => false, 'rata_rata' => null];
                        @endphp
                        <tr class="hover:bg-indigo-50/30 transition">
                            <td class="py-3 px-3 text-center font-bold text-slate-500">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3 font-mono text-slate-600">
                                <div>{{ $ks->siswa->nis }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ks->siswa->nisn ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $ks->siswa->nama }}</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $ks->siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold {{ $st['terisi'] === $st['total_mapel'] && $st['total_mapel'] > 0 ? 'bg-emerald-100 text-emerald-800' : ($st['terisi'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500') }}">
                                    {{ $st['terisi'] }} / {{ $st['total_mapel'] }} Mapel
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center font-mono font-bold text-sm {{ $st['rata_rata'] !== null ? 'text-indigo-700' : 'text-slate-300' }}">
                                {{ $st['rata_rata'] !== null ? $st['rata_rata'] : '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($st['is_complete'])
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Lengkap
                                    </span>
                                @elseif($st['terisi'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-clock mr-1 text-amber-600"></i> Parsial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500 border border-slate-200">
                                        <i class="fa-solid fa-circle-minus mr-1 text-slate-400"></i> Belum Diisi
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <!-- Tombol Input / Edit Nilai Siswa -->
                                    <a href="{{ route('kelas.nilai-formatif.input-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                                       class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold border border-indigo-200 transition flex items-center shadow-2xs"
                                       title="Input atau ubah nilai semua mata pelajaran untuk {{ $ks->siswa->nama }}">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Input Nilai
                                    </a>

                                    <!-- Tombol Cetak Rekap Semester 1 Siswa (1 Lembar F4) -->
                                    <a href="{{ route('kelas.nilai-formatif.rekap-semester-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id]) }}"
                                       target="_blank"
                                       class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold border border-blue-200 transition flex items-center shadow-2xs"
                                       title="Cetak Rekap Nilai Semester 1 Siswa Ini (Pas 1 Lembar F4)">
                                        <i class="fa-solid fa-file-lines mr-1 text-blue-600"></i> Rekap F4
                                    </a>

                                    <!-- Tombol Cetak / Lihat Rapor Formatif (Format UTS) -->
                                    <a href="{{ route('kelas.nilai-formatif.raport', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                                       target="_blank"
                                       class="px-2.5 py-1.5 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition flex items-center shadow-2xs"
                                       title="Lihat & Cetak Lembar Rapor Formatif Bulanan (Format Rapor UTS)">
                                        <i class="fa-solid fa-file-invoice mr-1 text-amber-400"></i> Rapor Formatif
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-user-slash text-3xl mb-2"></i>
                                <p class="text-sm font-semibold">Belum ada peserta didik di kelas ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Impor Nilai Formatif Excel -->
<div id="modalImporExcel" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100 text-left">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-file-excel text-emerald-600 mr-2 text-base"></i> Impor Nilai Formatif dari Excel
            </h3>
            <button type="button" onclick="document.getElementById('modalImporExcel').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>

        <form action="{{ route('kelas.nilai-formatif.import', $kelas->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4 mt-4">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="bulan" value="{{ $bulan }}">

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-900 space-y-1">
                <p class="font-bold flex items-center"><i class="fa-solid fa-circle-info mr-1 text-blue-600"></i> Petunjuk Impor:</p>
                <p>1. Silakan unduh format Excel terlebih dahulu dengan tombol <strong>"Unduh Format Excel"</strong> di atas.</p>
                <p>2. Isikan angka nilai (skala 0 - 100) pada kolom mata pelajaran yang tersedia.</p>
                <p>3. Periode impor saat ini: <strong>{{ $namaBulan }} {{ $tahun }}</strong>.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Pilih File Excel (.xlsx / .xls)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required class="w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-200 rounded-xl cursor-pointer">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modalImporExcel').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm flex items-center transition">
                    <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Proses Impor
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
