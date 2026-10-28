@extends('layouts.app')

@section('title', 'Input Formatif - ' . $kelasSiswa->siswa->nama)

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
                        Input Formatif Bulanan
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        Kelas {{ $kelas->nama }} &bull; Periode: {{ $namaBulan }} {{ $tahun }}
                    </span>
                </div>
                <h1 class="text-lg font-black text-slate-900 tracking-tight mt-0.5">
                    {{ $kelasSiswa->siswa->nama }}
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    NIS: <strong class="text-slate-700">{{ $kelasSiswa->siswa->nis }}</strong> &bull; NISN: <strong class="text-slate-700">{{ $kelasSiswa->siswa->nisn ?? '-' }}</strong>
                </p>
            </div>
        </div>

        <!-- Filter Bulan & Navigasi Antar Siswa -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Ubah Bulan Cepat -->
            <form action="{{ route('kelas.nilai-formatif.input-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id]) }}" method="GET" class="flex items-center space-x-1 bg-slate-50 border border-slate-200 p-1 rounded-xl">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <label for="selectBulan" class="text-xs font-bold text-slate-600 ml-1.5">Bulan:</label>
                <select name="bulan" id="selectBulan" onchange="this.form.submit()" class="bg-white border border-slate-300 text-xs font-semibold rounded-lg px-2 py-1 focus:outline-none">
                    @foreach($daftarBulan as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                    @endforeach
                </select>
            </form>

            <!-- Navigasi Siswa Prev & Next -->
            <div class="flex items-center space-x-1 bg-slate-50 border border-slate-200 p-1 rounded-xl">
                @if($prevId)
                    <a href="{{ route('kelas.nilai-formatif.input-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $prevId, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="px-2.5 py-1 bg-white hover:bg-slate-100 rounded-lg text-xs font-bold text-slate-700 border border-slate-200 transition flex items-center shadow-2xs"
                       title="Ke Siswa Sebelumnya">
                        <i class="fa-solid fa-chevron-left mr-1 text-[10px]"></i> Prev
                    </a>
                @else
                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-400 border border-slate-200 cursor-not-allowed">
                        <i class="fa-solid fa-chevron-left mr-1 text-[10px]"></i> Prev
                    </span>
                @endif

                @if($nextId)
                    <a href="{{ route('kelas.nilai-formatif.input-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $nextId, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="px-2.5 py-1 bg-white hover:bg-slate-100 rounded-lg text-xs font-bold text-slate-700 border border-slate-200 transition flex items-center shadow-2xs"
                       title="Ke Siswa Berikutnya">
                        Next <i class="fa-solid fa-chevron-right ml-1 text-[10px]"></i>
                    </a>
                @else
                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-400 border border-slate-200 cursor-not-allowed">
                        Next <i class="fa-solid fa-chevron-right ml-1 text-[10px]"></i>
                    </span>
                @endif
            </div>

            <!-- Tombol Langsung Cetak Rapor Formatif Siswa Ini -->
            <a href="{{ route('kelas.nilai-formatif.raport', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               target="_blank"
               class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs transition flex items-center shadow-sm">
                <i class="fa-solid fa-file-invoice mr-1.5 text-amber-400"></i> Lihat Rapor Formatif
            </a>
        </div>
    </div>

    <!-- Form Input Nilai Seluruh Mapel & Kehadiran -->
    <form id="formInputFormatif" action="{{ route('kelas.nilai-formatif.simpan-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="bulan" value="{{ $bulan }}">
        <input type="hidden" name="next_siswa_id" id="inputNextSiswaId" value="">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Kolom Kiri: Tabel Seluruh Mata Pelajaran (2 Kolom Lebar) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-book-open text-indigo-600"></i>
                            <h3 class="font-bold text-sm text-slate-900">Nilai Formatif Semua Mata Pelajaran</h3>
                        </div>
                        <span class="text-xs text-slate-500 font-medium">
                            Skala Nilai: <strong>0 – 100</strong>
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/80">
                                    <th class="py-3 px-3 w-10 text-center">No</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-3 w-24 text-center">KKM / KKTP</th>
                                    <th class="py-3 px-4 w-40 text-center">Nilai Formatif</th>
                                    <th class="py-3 px-4 w-32 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($mapels as $idx => $m)
                                    @php
                                        $kkm = $kelas->getKkmForMapel($m);
                                        $rec = $nilaiList->get($m->id);
                                        $currentVal = $rec && $rec->nilai !== null ? (float) $rec->nilai : '';
                                    @endphp
                                    <tr class="hover:bg-indigo-50/20 transition">
                                        <td class="py-2.5 px-3 text-center font-bold text-slate-400">{{ $idx + 1 }}</td>
                                        <td class="py-2.5 px-4 font-semibold text-slate-800 text-sm">
                                            {{ $m->nama }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-600">
                                            <span class="mapel-kkm" data-mapel-id="{{ $m->id }}">{{ $kkm }}</span>
                                        </td>
                                        <td class="py-2.5 px-4 text-center">
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   max="100"
                                                   name="nilai[{{ $m->id }}]"
                                                   id="inputMapel_{{ $m->id }}"
                                                   value="{{ old('nilai.' . $m->id, $currentVal) }}"
                                                   data-kkm="{{ $kkm }}"
                                                   placeholder="0-100"
                                                   class="input-nilai w-28 text-center font-mono font-bold text-sm px-2.5 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                        </td>
                                        <td class="py-2.5 px-4 text-center">
                                            <span id="badgeMapel_{{ $m->id }}" class="badge-status text-[11px] font-bold px-2 py-0.5 rounded-md">
                                                -
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-400">
                                            Belum ada mata pelajaran yang ditentukan untuk kelas ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                                <tr>
                                    <td colspan="2" class="py-2.5 px-4 text-right uppercase text-slate-600">Total Nilai:</td>
                                    <td colspan="2" class="py-2.5 px-4 text-center font-mono text-sm text-indigo-700" id="totalNilaiLabel">0</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="py-2.5 px-4 text-right uppercase text-slate-600">Rata-rata:</td>
                                    <td colspan="2" class="py-2.5 px-4 text-center font-mono text-sm text-indigo-700" id="rataRataLabel">0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Kehadiran Bulanan & Tombol Simpan -->
            <div class="space-y-4">

                <!-- Card Kehadiran Bulanan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                    <div class="flex items-center space-x-2 pb-2 border-b border-slate-100">
                        <i class="fa-solid fa-clipboard-user text-cyan-600"></i>
                        <h4 class="font-bold text-sm text-slate-900">Ketidakhadiran ({{ $namaBulan }})</h4>
                    </div>
                    <p class="text-xs text-slate-500">
                        Isikan jumlah hari ketidakhadiran siswa selama bulan <strong>{{ $namaBulan }} {{ $tahun }}</strong>. Data ini akan dicetak pada lembar rapor formatif.
                    </p>

                    <div class="space-y-3 pt-1">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 flex items-center">
                                <span class="w-3 h-3 rounded-full bg-amber-400 mr-2 inline-block"></span> Sakit (S)
                            </label>
                            <div class="flex items-center space-x-1">
                                <input type="number" min="0" max="31" name="sakit" value="{{ old('sakit', $kehadiran->sakit ?? 0) }}"
                                       class="w-20 text-center font-mono font-bold text-xs px-2 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                <span class="text-xs text-slate-400">hari</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 flex items-center">
                                <span class="w-3 h-3 rounded-full bg-blue-400 mr-2 inline-block"></span> Izin (I)
                            </label>
                            <div class="flex items-center space-x-1">
                                <input type="number" min="0" max="31" name="izin" value="{{ old('izin', $kehadiran->izin ?? 0) }}"
                                       class="w-20 text-center font-mono font-bold text-xs px-2 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                <span class="text-xs text-slate-400">hari</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 flex items-center">
                                <span class="w-3 h-3 rounded-full bg-rose-400 mr-2 inline-block"></span> Alpa / Tanpa Ket. (A)
                            </label>
                            <div class="flex items-center space-x-1">
                                <input type="number" min="0" max="31" name="alpa" value="{{ old('alpa', $kehadiran->alpa ?? 0) }}"
                                       class="w-20 text-center font-mono font-bold text-xs px-2 py-1.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                <span class="text-xs text-slate-400">hari</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Tombol Aksi Simpan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-3">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 mb-1">Aksi Penyimpanan</h4>

                    @if($nextId)
                        <!-- Tombol Simpan & Lanjut ke Siswa Berikutnya -->
                        <button type="button" onclick="simpanDanLanjut('{{ $nextId }}')"
                                class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                            <i class="fa-solid fa-arrow-right mr-2"></i> Simpan & Lanjut Siswa Berikutnya
                        </button>
                    @endif

                    <!-- Tombol Simpan Siswa Ini Saja -->
                    <button type="submit"
                            class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Nilai Siswa Ini
                    </button>

                    <!-- Tombol Batal / Kembali -->
                    <a href="{{ route('kelas.nilai-formatif.index', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center justify-center">
                        Kembali ke Daftar
                    </a>
                </div>

            </div>

        </div>
    </form>

</div>

<script>
    function updateLiveSummary() {
        const inputs = document.querySelectorAll('.input-nilai');
        let total = 0;
        let count = 0;

        inputs.forEach(input => {
            const rawVal = input.value.trim();
            const badge = document.getElementById(input.id.replace('inputMapel_', 'badgeMapel_'));
            const kkm = parseFloat(input.dataset.kkm) || 70;

            if (rawVal !== '' && !isNaN(rawVal)) {
                const val = parseFloat(rawVal);
                total += val;
                count++;

                if (val >= kkm) {
                    badge.textContent = 'Tuntas';
                    badge.className = 'badge-status text-[11px] font-bold px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800';
                } else {
                    badge.textContent = 'Belum Tuntas';
                    badge.className = 'badge-status text-[11px] font-bold px-2 py-0.5 rounded-md bg-rose-100 text-rose-800';
                }
            } else {
                badge.textContent = '-';
                badge.className = 'badge-status text-[11px] font-bold px-2 py-0.5 rounded-md text-slate-400';
            }
        });

        document.getElementById('totalNilaiLabel').textContent = count > 0 ? total.toFixed(1) : '0';
        document.getElementById('rataRataLabel').textContent = count > 0 ? (total / count).toFixed(1) : '0';
    }

    function simpanDanLanjut(nextId) {
        document.getElementById('inputNextSiswaId').value = nextId;
        document.getElementById('formInputFormatif').submit();
    }

    document.querySelectorAll('.input-nilai').forEach(input => {
        input.addEventListener('input', updateLiveSummary);
    });

    document.addEventListener('DOMContentLoaded', updateLiveSummary);
</script>
@endsection
