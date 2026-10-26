@extends('layouts.app')

@section('title', 'Input Nilai - ' . $kelasSiswa->siswa->nama)

@section('content')
<div class="space-y-6">

    <!-- Header & Navigasi Siswa -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Input Nilai Siswa</span>
            </div>
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white font-extrabold text-lg flex items-center justify-center shadow-md">
                    {{ substr($kelasSiswa->siswa->nama, 0, 1) }}
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 leading-tight">{{ $kelasSiswa->siswa->nama }}</h1>
                    <p class="text-xs text-slate-500 font-mono">
                        NIS: <strong class="text-slate-700">{{ $kelasSiswa->siswa->nis }}</strong> &bull; NISN: {{ $kelasSiswa->siswa->nisn ?? '-' }} &bull; {{ $kelas->nama }} ({{ $kelas->fase }})
                    </p>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Cepat & Navigasi -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Pilihan Periode STS / SAS -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => 'sts']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS (Tengah)
                </a>
                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => 'sas']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS (Akhir)
                </a>
            </div>

            @if($prevId)
                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $prevId, 'periode' => $periode]) }}"
                   class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition" title="Siswa Sebelumnya">
                   <i class="fa-solid fa-chevron-left"></i>
                </a>
            @endif

            @if($nextId)
                <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $nextId, 'periode' => $periode]) }}"
                   class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition" title="Siswa Berikutnya">
                   <i class="fa-solid fa-chevron-right"></i>
                </a>
            @endif

            <a href="{{ route('kelas.sia-pembiasaan.index', ['kelas' => $kelas->id, 'periode' => $periode]) }}"
               class="px-3 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition flex items-center shadow-sm"
               title="Buka Form SIA & Pembiasaan Seluruh Siswa Sekelas">
                <i class="fa-solid fa-clipboard-user mr-1.5"></i> SIA Sekelas
            </a>

            <a href="{{ route('raport.preview', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode]) }}" target="_blank"
               class="px-3 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white transition flex items-center shadow-sm">
                <i class="fa-solid fa-eye mr-1.5 text-emerald-400"></i> Pratinjau Raport
            </a>
        </div>
    </div>

    <!-- Form Input Nilai Utama -->
    <form action="{{ route('nilai.simpan.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode }}">
        <input type="hidden" name="next_siswa_id" id="nextSiswaIdField" value="">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- TABEL NILAI MATA PELAJARAN (2 Kolom) -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-pen-ruler text-emerald-600 mr-2"></i> Nilai Capaian Belajar (Berdasarkan KKM)
                        </h2>
                        <p class="text-xs text-slate-400">Rentang nilai 0 - 100. Nilai kosong tidak dihitung.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase bg-blue-100 text-blue-800">
                        Periode {{ strtoupper($periode) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                            <tr>
                                <th class="py-3 px-3 w-10 text-center">No</th>
                                <th class="py-3 px-4">Mata Pelajaran</th>
                                <th class="py-3 px-3 w-24 text-center">KKM</th>
                                <th class="py-3 px-3 w-40 text-center">Nilai Siswa (0-100)</th>
                                <th class="py-3 px-3 w-32 text-center bg-slate-100/60">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($mapels as $idx => $m)
                                @php
                                    $n = $nilaiList->get($m->id);
                                    $sVal = $n && $n->sumatif !== null ? (float)$n->sumatif : ($n && $n->formatif !== null ? (float)$n->formatif : '');
                                    $kkm = $kelas->getKkmForMapel($m);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-3 text-center text-slate-400 font-bold text-xs">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4 font-semibold text-slate-800 text-xs sm:text-sm">
                                        {{ $m->nama }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-xs">{{ $kkm }}</span>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="sumatif[{{ $m->id }}]"
                                            data-kkm="{{ $kkm }}"
                                            value="{{ $sVal }}"
                                            placeholder="-"
                                            class="input-sumatif w-full text-center py-1.5 px-2 text-sm font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                            oninput="hitungOtomatis()">
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-xs row-status">
                                        -
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-100/80 font-bold text-xs text-slate-800 border-t-2 border-slate-300">
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-right uppercase">Jumlah Nilai:</td>
                                <td class="py-3 px-3 text-center text-emerald-800 text-sm font-black" id="totalSumatif">-</td>
                                <td class="py-3 px-3 text-center bg-slate-200/50">-</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="py-3 px-4 text-right uppercase">Rata-rata Nilai:</td>
                                <td class="py-3 px-3 text-center text-emerald-900 text-sm font-black" id="rataSumatif">-</td>
                                <td class="py-3 px-3 text-center bg-slate-200/50">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- PANEL KANAN: PEMBIASAAN & KEHADIRAN (1 Kolom) -->
            <div class="space-y-6">

                <!-- ASPEK PEMBIASAAN -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-star text-amber-500 mr-2"></i> Karakter & Pembiasaan
                        </h2>
                    </div>

                    <div class="space-y-3">
                        @foreach($pembiasaans as $p)
                            @php
                                $pemVal = $pembiasaanList->get($p->id);
                                $predAktif = $pemVal ? $pemVal->predikat : 'Baik';
                            @endphp
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                                <label class="block text-xs font-semibold text-slate-800 mb-1.5 leading-snug">
                                    {{ $p->nama }}
                                </label>
                                <select name="predikat[{{ $p->id }}]" class="w-full text-xs font-bold py-1.5 px-2 bg-white border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                                    <option value="Sangat Baik" {{ $predAktif === 'Sangat Baik' ? 'selected' : '' }}>Sangat Baik (SB)</option>
                                    <option value="Baik" {{ $predAktif === 'Baik' ? 'selected' : '' }}>Baik (B)</option>
                                    <option value="Cukup" {{ $predAktif === 'Cukup' ? 'selected' : '' }}>Cukup (C)</option>
                                    <option value="Perlu Bimbingan" {{ $predAktif === 'Perlu Bimbingan' ? 'selected' : '' }}>Perlu Bimbingan (PB)</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- KEHADIRAN -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-clipboard-user text-blue-600 mr-2"></i> Ketidakhadiran (Hari)
                        </h2>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1 text-center">Sakit</label>
                            <input type="number" min="0" name="sakit" value="{{ $kehadiran->sakit }}"
                                class="w-full text-center py-2 border border-slate-300 rounded-xl font-bold text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1 text-center">Izin</label>
                            <input type="number" min="0" name="izin" value="{{ $kehadiran->izin }}"
                                class="w-full text-center py-2 border border-slate-300 rounded-xl font-bold text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1 text-center">Tanpa Ket.</label>
                            <input type="number" min="0" name="alpa" value="{{ $kehadiran->alpa }}"
                                class="w-full text-center py-2 border border-slate-300 rounded-xl font-bold text-sm focus:ring-2 focus:ring-emerald-500">
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-2 sticky bottom-4 z-20">
                    <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-lg shadow-emerald-600/30 text-sm transition flex items-center justify-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Nilai Siswa
                    </button>

                    @if($nextId)
                        <button type="button" onclick="simpanDanLanjut('{{ $nextId }}')"
                            class="w-full py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold rounded-xl text-xs transition flex items-center justify-center">
                            <i class="fa-solid fa-arrow-right mr-1.5"></i> Simpan & Lanjut Siswa Berikutnya
                        </button>
                    @endif
                </div>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
    function formatAngkaIndo(num) {
        if (isNaN(num) || num === 0) return '0';
        return Number(num).toFixed(2).replace('.', ',').replace(/,?0+$/, '');
    }

    function hitungOtomatis() {
        const sumatifInputs = document.querySelectorAll('.input-sumatif');
        const rowStatusCells = document.querySelectorAll('.row-status');

        let totalSumatif = 0;
        let countSumatif = 0;

        sumatifInputs.forEach((sInput, idx) => {
            const statusCell = rowStatusCells[idx];
            const kkm = parseFloat(sInput.dataset.kkm) || 70;
            const sVal = sInput.value !== '' ? parseFloat(sInput.value) : null;

            if (sVal !== null && !isNaN(sVal)) {
                totalSumatif += sVal;
                countSumatif++;
                if (sVal >= kkm) {
                    statusCell.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Tuntas</span>';
                } else {
                    statusCell.innerHTML = '<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Belum Tuntas</span>';
                }
            } else {
                statusCell.textContent = '-';
            }
        });

        document.getElementById('totalSumatif').textContent = formatAngkaIndo(totalSumatif);
        const rataSumatif = countSumatif > 0 ? (totalSumatif / countSumatif) : 0;
        document.getElementById('rataSumatif').textContent = formatAngkaIndo(rataSumatif);
    }

    function simpanDanLanjut(nextId) {
        document.getElementById('nextSiswaIdField').value = nextId;
        document.querySelector('form').submit();
    }

    // Jalankan hitung awal saat halaman dimuat
    document.addEventListener('DOMContentLoaded', hitungOtomatis);
</script>
@endpush
@endsection
