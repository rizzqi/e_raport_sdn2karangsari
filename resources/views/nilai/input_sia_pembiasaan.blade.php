@extends('layouts.app')

@section('title', 'Input Kehadiran (SIA) & Pembiasaan - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header & Navigasi -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Input Kehadiran (SIA) & Karakter Pembiasaan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 flex items-center">
                <i class="fa-solid fa-clipboard-user text-amber-500 mr-2.5"></i> Input SIA & Pembiasaan Kelas: {{ $kelas->nama }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500">
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                    {{ $kelas->fase ?? 'Fase A' }}
                </span>
                <span>&bull;</span>
                <span>Tahun Ajaran {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</span>
                <span>&bull;</span>
                <span>Wali Kelas: <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong></span>
            </div>
        </div>

        <!-- Toolbar Aksi Cepat -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Pilihan Periode STS / SAS -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('kelas.sia-pembiasaan.index', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS (Tengah)
                </a>
                <a href="{{ route('kelas.sia-pembiasaan.index', ['kelas' => $kelas->id, 'periode' => 'sas']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS (Akhir)
                </a>
            </div>

            <!-- Tombol Impor File (Excel / PDF) -->
            <button type="button" onclick="document.getElementById('modalImportSiaPembiasaan').classList.remove('hidden')"
                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
                title="Impor Kehadiran & Pembiasaan dari File Excel (.xlsx, .csv) atau PDF (.pdf)">
                <i class="fa-solid fa-file-arrow-up mr-1.5"></i> Impor dari PDF / Excel
            </button>

            <!-- Menu Unduh Template -->
            <div class="flex items-center bg-white border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'xlsx', 'periode' => $periode]) }}"
                    class="px-3 py-2 text-slate-700 hover:bg-slate-50 font-bold text-xs transition flex items-center border-r border-slate-200"
                    title="Unduh format spreadsheet .xlsx resmi untuk SIA & Pembiasaan">
                    <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600"></i> Template (.xlsx)
                </a>
                <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'csv', 'periode' => $periode]) }}"
                    class="px-2.5 py-2 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition flex items-center"
                    title="Unduh format CSV">
                    .CSV
                </a>
            </div>

            <!-- Tombol Tarik dari Absensi Bulanan -->
            <form action="{{ route('kelas.sia-pembiasaan.tarik-absensi', $kelas->id) }}" method="POST" onsubmit="return confirm('Tarik akumulasi kehadiran dari absensi bulanan untuk periode {{ strtoupper($periode) }}? Nilai Sakit, Izin, dan Alpa saat ini akan diperbarui dengan hitungan dari modul absensi bulanan.')">
                @csrf
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="tahun" value="{{ now()->year }}">
                <button type="submit" class="px-3 py-2 bg-cyan-50 hover:bg-cyan-100 text-cyan-800 border border-cyan-300 font-bold rounded-xl text-xs transition flex items-center shadow-2xs"
                    title="Tarik otomatis akumulasi Sakit, Izin, dan Alpa dari modul Absensi Bulanan">
                    <i class="fa-solid fa-rotate mr-1.5 text-cyan-600"></i> Tarik dari Absensi
                </button>
            </form>

            <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'periode' => $periode]) }}"
               class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-bold rounded-xl text-xs transition flex items-center shadow-2xs"
               title="Kembali ke Pengisian Nilai Mata Pelajaran">
                <i class="fa-solid fa-table-cells mr-1.5 text-emerald-600"></i> Input Nilai Mapel
            </a>
        </div>
    </div>

    <!-- Standar Kebijakan Sekolah & SIA -->
    @php
        $stdHariEfektif = $sekolah ? $sekolah->getHariEfektif() : 108;
        $stdBatasAlpa = $sekolah ? $sekolah->getBatasAlpaMaksimal() : 3;
        $stdBatasIzin = $sekolah ? $sekolah->getBatasIzinMaksimal() : 7;
        $stdMinHadir = $sekolah ? $sekolah->getPersenKehadiranMinimal() : 75;
    @endphp
    <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-start space-x-3 text-xs text-blue-950">
            <i class="fa-solid fa-shield-halved text-blue-600 text-lg mt-0.5 shrink-0"></i>
            <div>
                <p class="font-bold flex items-center gap-2">
                    <span>Standar Kehadiran Sekolah</span>
                    <span class="text-[10px] bg-blue-200/70 text-blue-800 font-semibold px-2 py-0.5 rounded-full">Aturan Kenaikan Kelas</span>
                </p>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-blue-900 text-[11px] mt-1 font-medium">
                    <span><i class="fa-regular fa-calendar-check text-blue-600 mr-1"></i>Hari Efektif: <strong>{{ $stdHariEfektif }} Hari</strong></span>
                    <span><i class="fa-solid fa-user-check text-emerald-600 mr-1"></i>Syarat Minimal Hadir: <strong>{{ $stdMinHadir }}%</strong></span>
                    <span><i class="fa-solid fa-circle-exclamation text-rose-600 mr-1"></i>Batas Alpa: <strong>Maks. {{ $stdBatasAlpa }} Hari</strong></span>
                    <span><i class="fa-regular fa-clock text-amber-600 mr-1"></i>Batas Wajar Izin: <strong>Maks. {{ $stdBatasIzin }} Hari</strong></span>
                </div>
                <p class="text-[10px] text-blue-700 mt-1 italic">
                    <i class="fa-solid fa-circle-info mr-1"></i>Nilai mata pelajaran murni mengukur capaian kompetensi dan tidak dipotong absensi. SIA memengaruhi predikat karakter (Kemandirian/Disiplin), syarat kenaikan kelas, dan narasi wali kelas.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
            <button type="button" onclick="sinkronRekomendasiSiaKePembiasaan()" class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-xs flex items-center" title="Rekomendasikan predikat Kemandirian / Disiplin otomatis dari SIA (Wali kelas berhak menyesuaikan/override)">
                <i class="fa-solid fa-wand-magic-sparkles mr-1.5"></i> Rekomendasi dari SIA
            </button>
            <button type="button" onclick="setSemuaPredikat('Baik')" class="px-2.5 py-1.5 bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold transition shadow-2xs flex items-center">
                <i class="fa-solid fa-check mr-1 text-emerald-600"></i> Semua Baik (B)
            </button>
            <button type="button" onclick="setSemuaPredikat('Sangat Baik')" class="px-2.5 py-1.5 bg-white hover:bg-blue-50 text-blue-800 border border-blue-300 rounded-lg text-xs font-bold transition shadow-2xs flex items-center">
                <i class="fa-solid fa-star mr-1 text-blue-600"></i> Semua Sangat Baik (SB)
            </button>
            <button type="button" onclick="resetSemuaSia()" class="px-2.5 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition shadow-2xs flex items-center">
                <i class="fa-solid fa-eraser mr-1 text-slate-500"></i> Set SIA ke 0
            </button>
        </div>
    </div>

    <!-- Form Utama Matriks SIA & Pembiasaan -->
    <form id="formSiaPembiasaan" action="{{ route('kelas.sia-pembiasaan.simpan', $kelas->id) }}" method="POST">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode }}">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-table-list text-amber-500 mr-2"></i> Matriks Nilai Karakter & Kehadiran &bull; Periode {{ strtoupper($periode) }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Total {{ $kelasSiswaList->count() }} siswa. Isi jumlah hari ketidakhadiran (S, I, A) dan tentukan predikat pembiasaan.</p>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan SIA & Pembiasaan
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-xs font-black uppercase text-slate-700">
                            <th rowspan="2" class="py-3 px-3 w-12 text-center border-r border-slate-200">No</th>
                            <th rowspan="2" class="py-3 px-4 min-w-[200px] border-r border-slate-200 sticky left-0 bg-slate-100 z-10">Nama Siswa</th>
                            <th rowspan="2" class="py-3 px-3 w-24 border-r border-slate-200 text-center">NIS</th>
                            <th colspan="5" class="py-2 px-2 text-center bg-blue-50/80 border-r border-slate-200 text-blue-900">
                                <i class="fa-solid fa-calendar-xmark mr-1 text-blue-600"></i> Ketidakhadiran (Hari) & Kehadiran
                            </th>
                            <th colspan="{{ $pembiasaans->count() }}" class="py-2 px-3 text-center bg-amber-50/80 text-amber-900">
                                <i class="fa-solid fa-star mr-1 text-amber-600"></i> Aspek Karakter & Pembiasaan
                            </th>
                        </tr>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-600">
                            <!-- Kolom SIA -->
                            <th class="py-2 px-2 w-16 text-center bg-blue-50/40 border-r border-slate-200 text-blue-800" title="Sakit">Sakit (S)</th>
                            <th class="py-2 px-2 w-16 text-center bg-blue-50/40 border-r border-slate-200 text-blue-800" title="Izin">Izin (I)</th>
                            <th class="py-2 px-2 w-16 text-center bg-blue-50/40 border-r border-slate-200 text-blue-800" title="Alpa / Tanpa Keterangan">Alpa (A)</th>
                            <th class="py-2 px-2 w-14 text-center bg-blue-100/50 border-r border-slate-200 text-blue-950" title="Total Ketidakhadiran">Total</th>
                            <th class="py-2 px-2 w-16 text-center bg-blue-100/70 border-r border-slate-200 text-blue-950" title="Persentase Kehadiran terhadap Hari Efektif">% Hadir</th>

                            <!-- Kolom Pembiasaan -->
                            @foreach($pembiasaans as $p)
                                <th class="py-2 px-2 min-w-[150px] text-center bg-amber-50/40 border-r border-slate-100 last:border-r-0 text-amber-950 font-semibold" title="{{ $p->nama }}">
                                    <span class="block truncate max-w-[170px]" title="{{ $p->nama }}">{{ $p->nama }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($kelasSiswaList as $idx => $ks)
                            @php
                                $s = $ks->siswa;
                                $kh = $kehadiranList->get($ks->id);
                                $sakitVal = $kh ? (int)$kh->sakit : 0;
                                $izinVal = $kh ? (int)$kh->izin : 0;
                                $alpaVal = $kh ? (int)$kh->alpa : 0;
                                $totalSia = $sakitVal + $izinVal + $alpaVal;

                                $studentPemMap = $nilaiPemGroup->get($ks->id, collect())->keyBy('pembiasaan_id');
                            @endphp
                            <tr class="hover:bg-slate-50/80 transition group" data-siswa-id="{{ $ks->id }}">
                                <td class="py-3 px-3 text-center text-slate-400 font-bold text-xs border-r border-slate-100">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900 border-r border-slate-100 sticky left-0 bg-white group-hover:bg-slate-50/80 z-10">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[11px] flex items-center justify-center shrink-0">
                                            {{ substr($s->nama, 0, 1) }}
                                        </div>
                                        <span class="truncate max-w-[200px]" title="{{ $s->nama }}">{{ $s->nama }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-center text-slate-500 font-mono text-xs border-r border-slate-100">
                                    {{ $s->nis }}
                                </td>

                                <!-- Input SIA -->
                                <td class="py-2 px-1.5 text-center bg-blue-50/20 border-r border-slate-100">
                                    <input type="number" min="0" max="365"
                                        name="sakit[{{ $ks->id }}]"
                                        value="{{ $sakitVal }}"
                                        class="input-sia input-sakit w-14 text-center py-1.5 px-1 text-xs font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        oninput="updateSiaRowTotal(this)">
                                </td>
                                <td class="py-2 px-1.5 text-center bg-blue-50/20 border-r border-slate-100">
                                    <input type="number" min="0" max="365"
                                        name="izin[{{ $ks->id }}]"
                                        value="{{ $izinVal }}"
                                        class="input-sia input-izin w-14 text-center py-1.5 px-1 text-xs font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        oninput="updateSiaRowTotal(this)">
                                </td>
                                <td class="py-2 px-1.5 text-center bg-blue-50/20 border-r border-slate-100">
                                    <input type="number" min="0" max="365"
                                        name="alpa[{{ $ks->id }}]"
                                        value="{{ $alpaVal }}"
                                        class="input-sia input-alpa w-14 text-center py-1.5 px-1 text-xs font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        oninput="updateSiaRowTotal(this)">
                                </td>
                                <td class="py-2 px-1.5 text-center bg-blue-100/30 border-r border-slate-100 font-bold text-xs text-blue-900 total-sia-cell">
                                    {{ $totalSia }}
                                </td>
                                @php
                                    $rowHadir = max(0, $stdHariEfektif - $totalSia);
                                    $rowPersen = $stdHariEfektif > 0 ? round(($rowHadir / $stdHariEfektif) * 100, 1) : 100.0;
                                    $isWarnRow = ($alpaVal > $stdBatasAlpa) || ($rowPersen < $stdMinHadir);
                                @endphp
                                <td class="py-2 px-1 text-center border-r border-slate-100 text-xs font-black persen-hadir-cell {{ $isWarnRow ? 'bg-rose-50 text-rose-700' : 'bg-blue-50/30 text-blue-900' }}"
                                    title="Hadir {{ $rowHadir }} dari {{ $stdHariEfektif }} hari efektif">
                                    <span class="persen-text">{{ $rowPersen }}%</span>
                                    @if($isWarnRow)
                                        <i class="fa-solid fa-triangle-exclamation text-rose-500 ml-0.5" title="Melebihi batas alpa atau di bawah minimal hadir"></i>
                                    @endif
                                </td>

                                <!-- Select Pembiasaan -->
                                @foreach($pembiasaans as $p)
                                    @php
                                        $pemRecord = $studentPemMap->get($p->id);
                                        $predAktif = $pemRecord ? $pemRecord->predikat : 'Baik';
                                    @endphp
                                    <td class="py-2 px-1.5 text-center bg-amber-50/10 border-r border-slate-100 last:border-r-0">
                                        <select name="predikat[{{ $ks->id }}][{{ $p->id }}]"
                                            data-pembiasaan-nama="{{ $p->nama }}"
                                            class="select-pembiasaan w-full text-xs font-semibold py-1.5 px-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-amber-500 {{ $predAktif === 'Sangat Baik' ? 'bg-emerald-50 text-emerald-900 border-emerald-300' : ($predAktif === 'Baik' ? 'bg-white text-slate-800' : ($predAktif === 'Cukup' ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-rose-50 text-rose-900 border-rose-300')) }}"
                                            onchange="updateSelectColor(this)">
                                            <option value="Sangat Baik" {{ $predAktif === 'Sangat Baik' ? 'selected' : '' }}>Sangat Baik (SB)</option>
                                            <option value="Baik" {{ $predAktif === 'Baik' ? 'selected' : '' }}>Baik (B)</option>
                                            <option value="Cukup" {{ $predAktif === 'Cukup' ? 'selected' : '' }}>Cukup (C)</option>
                                            <option value="Perlu Bimbingan" {{ $predAktif === 'Perlu Bimbingan' ? 'selected' : '' }}>Perlu Bimbingan (PB)</option>
                                        </select>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 8 + $pembiasaans->count() }}" class="py-8 text-center text-slate-400">
                                    Belum ada siswa yang terdaftar di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Sticky Bottom Toolbar Simpan -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sticky bottom-0 z-20">
                <div class="text-xs text-slate-500 flex items-center space-x-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Pastikan Anda menekan tombol simpan setelah mengubah data.</span>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl text-xs shadow-md transition flex items-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Semua SIA & Pembiasaan
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<!-- Modal Impor SIA & Pembiasaan dari File Excel / PDF / Clipboard -->
<div id="modalImportSiaPembiasaan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-slate-100 overflow-hidden animate-in fade-in duration-200">

        <!-- Header Modal -->
        <div class="p-5 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-file-arrow-up text-amber-600 mr-2 text-lg"></i>
                    Impor SIA & Pembiasaan &bull; {{ $kelas->nama }}
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Unggah file spreadsheet (Excel / CSV), dokumen PDF, atau salin-tempel dari tabel</p>
            </div>
            <button type="button" onclick="document.getElementById('modalImportSiaPembiasaan').classList.add('hidden')"
                class="w-8 h-8 rounded-full bg-slate-200/60 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('kelas.sia-pembiasaan.import', $kelas->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Pilihan Periode Penilaian -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Periode Penilaian *</label>
                <select name="periode" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500">
                    <option value="sts" {{ $periode === 'sts' ? 'selected' : '' }}>STS (Sumatif Tengah Semester)</option>
                    <option value="sas" {{ $periode === 'sas' ? 'selected' : '' }}>SAS (Sumatif Akhir Semester)</option>
                </select>
            </div>

            <!-- Download Template Box -->
            <div class="p-3.5 bg-amber-50/70 border border-amber-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-start space-x-2">
                    <i class="fa-solid fa-file-excel text-amber-600 text-base mt-0.5 shrink-0"></i>
                    <div class="text-xs text-amber-950">
                        <p class="font-bold">Unduh Template Resmi (.xlsx)</p>
                        <p class="text-[11px] text-amber-800">Template memuat daftar siswa sekelas dan kolom Sakit, Izin, Alpa serta 8 aspek pembiasaan.</p>
                    </div>
                </div>
                <div class="shrink-0">
                    <a href="{{ route('kelas.sia-pembiasaan.template', ['kelas' => $kelas->id, 'format' => 'xlsx', 'periode' => $periode]) }}"
                       class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition flex items-center shadow-xs">
                        <i class="fa-solid fa-download mr-1.5"></i> Unduh .XLSX
                    </a>
                </div>
            </div>

            <!-- Tabs: Upload File vs Copy Paste -->
            <div>
                <div class="flex border-b border-slate-200 text-xs font-bold mb-3">
                    <button type="button" onclick="switchSiaImportTab('file')" id="siaTabBtnFile"
                        class="pb-2 px-3 border-b-2 border-amber-600 text-amber-700 flex items-center">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Unggah File (Excel / PDF)
                    </button>
                    <button type="button" onclick="switchSiaImportTab('paste')" id="siaTabBtnPaste"
                        class="pb-2 px-3 border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center">
                        <i class="fa-solid fa-paste mr-1.5"></i> Salin-Tempel (Clipboard)
                    </button>
                </div>

                <!-- Tab File -->
                <div id="siaTabContentFile" class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Pilih file spreadsheet (.xlsx, .csv) atau dokumen PDF (.pdf)</label>
                    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-4 text-center hover:border-amber-500 transition bg-slate-50/50">
                        <div class="flex justify-center items-center space-x-3 mb-2">
                            <i class="fa-solid fa-file-excel text-2xl text-emerald-600"></i>
                            <i class="fa-solid fa-file-pdf text-2xl text-rose-600"></i>
                        </div>
                        <input type="file" name="file_excel" accept=".xlsx,.csv,.txt,.pdf"
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-2">Mendukung Microsoft Excel (.xlsx), CSV, teks, dan dokumen PDF (.pdf). Header kolom akan dideteksi secara cerdas.</p>
                    </div>
                </div>

                <!-- Tab Paste -->
                <div id="siaTabContentPaste" class="space-y-2 hidden">
                    <label class="block text-xs font-semibold text-slate-700">Tempelkan baris tabel dari spreadsheet atau dokumen teks</label>
                    <textarea name="data_teks" rows="6"
                        placeholder="Contoh format:&#10;No	NIS	Nama Siswa	Sakit	Izin	Alpa	Keimanan	Kewargaan&#10;1	1001	Ahmad Dani	1	2	0	Baik	Sangat Baik&#10;2	1002	Budi Santoso	0	0	1	Sangat Baik	Baik"
                        class="w-full text-xs font-mono p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-amber-500"></textarea>
                    <p class="text-[10px] text-slate-400">Bisa langsung disalin dari Microsoft Excel atau Google Spreadsheet menggunakan Ctrl+V.</p>
                </div>
            </div>

            <!-- Petunjuk Format -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[11px] text-slate-600 space-y-1">
                <p class="font-bold text-slate-800">Petunjuk Pengisian:</p>
                <ul class="list-disc list-inside space-y-0.5 text-slate-500 text-[10px]">
                    <li>Kolom SIA menerima angka hari: <code>0</code>, <code>1</code>, <code>2</code>, dst.</li>
                    <li>Kolom Pembiasaan menerima: <code>Sangat Baik (SB)</code>, <code>Baik (B)</code>, <code>Cukup (C)</code>, <code>Perlu Bimbingan (PB)</code> atau angka 1 - 4.</li>
                    <li>Pencocokan nama siswa fleksibel (nama lengkap, NIS, maupun urutan absen).</li>
                </ul>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="document.getElementById('modalImportSiaPembiasaan').classList.add('hidden')"
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center">
                    <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Mulai Impor Data
                </button>
            </div>
        </form>

    </div>
</div>

@push('scripts')
<script>
    const HARI_EFEKTIF = {{ $stdHariEfektif }};
    const MIN_PERSEN_HADIR = {{ $stdMinHadir }};
    const BATAS_ALPA = {{ $stdBatasAlpa }};

    function updateSiaRowTotal(input) {
        const row = input.closest('tr');
        if (!row) return;

        const s = parseInt(row.querySelector('.input-sakit')?.value) || 0;
        const i = parseInt(row.querySelector('.input-izin')?.value) || 0;
        const a = parseInt(row.querySelector('.input-alpa')?.value) || 0;
        const total = s + i + a;

        const totalCell = row.querySelector('.total-sia-cell');
        if (totalCell) {
            totalCell.textContent = total;
        }

        const persenCell = row.querySelector('.persen-hadir-cell');
        if (persenCell) {
            const hadir = Math.max(0, HARI_EFEKTIF - total);
            const persen = HARI_EFEKTIF > 0 ? ((hadir / HARI_EFEKTIF) * 100).toFixed(1) : '100.0';
            const isWarn = (a > BATAS_ALPA) || (parseFloat(persen) < MIN_PERSEN_HADIR);

            let warnHtml = '';
            if (isWarn) {
                persenCell.className = 'py-2 px-1 text-center border-r border-slate-100 text-xs font-black persen-hadir-cell bg-rose-50 text-rose-700';
                warnHtml = ' <i class="fa-solid fa-triangle-exclamation text-rose-500 ml-0.5" title="Melebihi batas alpa atau di bawah minimal kehadiran"></i>';
            } else {
                persenCell.className = 'py-2 px-1 text-center border-r border-slate-100 text-xs font-black persen-hadir-cell bg-blue-50/30 text-blue-900';
            }
            persenCell.innerHTML = `<span class="persen-text">${persen}%</span>${warnHtml}`;
        }
    }

    function sinkronRekomendasiSiaKePembiasaan() {
        let updatedCount = 0;
        document.querySelectorAll('#formSiaPembiasaan tbody tr[data-siswa-id]').forEach(row => {
            const s = parseInt(row.querySelector('.input-sakit')?.value) || 0;
            const i = parseInt(row.querySelector('.input-izin')?.value) || 0;
            const a = parseInt(row.querySelector('.input-alpa')?.value) || 0;
            const total = s + i + a;
            const hadir = Math.max(0, HARI_EFEKTIF - total);
            const persen = HARI_EFEKTIF > 0 ? (hadir / HARI_EFEKTIF) * 100 : 100;

            // Logika usulan predikat karakter kemandirian / kedisiplinan berbasis SIA
            let usulan = 'Baik';
            if (a === 0 && persen >= 90) {
                usulan = 'Sangat Baik';
            } else if (a <= 2 && persen >= MIN_PERSEN_HADIR) {
                usulan = 'Baik';
            } else if (a <= BATAS_ALPA || persen >= 60) {
                usulan = 'Cukup';
            } else {
                usulan = 'Perlu Bimbingan';
            }

            row.querySelectorAll('.select-pembiasaan').forEach(sel => {
                const nama = (sel.getAttribute('data-pembiasaan-nama') || '').toLowerCase();
                if (nama.includes('mandiri') || nama.includes('disiplin') || nama.includes('tertib') || nama.includes('tanggung jawab')) {
                    sel.value = usulan;
                    updateSelectColor(sel);
                    updatedCount++;
                }
            });
        });

        alert('Rekomendasi karakter (Kemandirian & Kedisiplinan) berhasil disinkronkan dari data SIA pada ' + updatedCount + ' kolom siswa.\n\nSebagai wali kelas, Anda berhak mengubah (override) nilai tersebut jika ada pertimbangan khusus sebelum menyimpan.');
    }

    function updateSelectColor(select) {
        select.classList.remove('bg-emerald-50', 'text-emerald-900', 'border-emerald-300',
                               'bg-white', 'text-slate-800',
                               'bg-amber-50', 'text-amber-900', 'border-amber-300',
                               'bg-rose-50', 'text-rose-900', 'border-rose-300');

        if (select.value === 'Sangat Baik') {
            select.classList.add('bg-emerald-50', 'text-emerald-900', 'border-emerald-300');
        } else if (select.value === 'Baik') {
            select.classList.add('bg-white', 'text-slate-800');
        } else if (select.value === 'Cukup') {
            select.classList.add('bg-amber-50', 'text-amber-900', 'border-amber-300');
        } else if (select.value === 'Perlu Bimbingan') {
            select.classList.add('bg-rose-50', 'text-rose-900', 'border-rose-300');
        }
    }

    function setSemuaPredikat(predikat) {
        if (!confirm('Ubah semua predikat pembiasaan di layar ini menjadi "' + predikat + '"? (Perubahan belum disimpan ke server)')) {
            return;
        }
        document.querySelectorAll('.select-pembiasaan').forEach(sel => {
            sel.value = predikat;
            updateSelectColor(sel);
        });
    }

    function resetSemuaSia() {
        if (!confirm('Kosongkan semua isian Sakit, Izin, dan Alpa di layar ini menjadi 0? (Perubahan belum disimpan ke server)')) {
            return;
        }
        document.querySelectorAll('.input-sia').forEach(inp => {
            inp.value = 0;
        });
        document.querySelectorAll('.total-sia-cell').forEach(cell => {
            cell.textContent = 0;
        });
    }

    function switchSiaImportTab(tab) {
        const btnFile = document.getElementById('siaTabBtnFile');
        const btnPaste = document.getElementById('siaTabBtnPaste');
        const contentFile = document.getElementById('siaTabContentFile');
        const contentPaste = document.getElementById('siaTabContentPaste');

        if (tab === 'file') {
            btnFile.classList.add('border-amber-600', 'text-amber-700');
            btnFile.classList.remove('border-transparent', 'text-slate-400');
            btnPaste.classList.remove('border-amber-600', 'text-amber-700');
            btnPaste.classList.add('border-transparent', 'text-slate-400');
            contentFile.classList.remove('hidden');
            contentPaste.classList.add('hidden');
        } else {
            btnPaste.classList.add('border-amber-600', 'text-amber-700');
            btnPaste.classList.remove('border-transparent', 'text-slate-400');
            btnFile.classList.remove('border-amber-600', 'text-amber-700');
            btnFile.classList.add('border-transparent', 'text-slate-400');
            contentPaste.classList.remove('hidden');
            contentFile.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection
