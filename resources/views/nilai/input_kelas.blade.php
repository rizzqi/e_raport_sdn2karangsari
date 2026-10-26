@extends('layouts.app')

@section('title', 'Input Matriks Nilai Kelas ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline">Kelas</a>
                <span>&bull;</span>
                <a href="{{ route('kelas.show', $kelas->id) }}" class="hover:underline">{{ $kelas->nama }}</a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Input Nilai Per Mapel</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 flex items-center">
                <i class="fa-solid fa-table-cells text-emerald-600 mr-2"></i> Input Cepat Nilai Kelas: {{ $kelas->nama }}
            </h1>
            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500">
                <span>
                    <i class="fa-solid fa-user-tie text-slate-400 mr-1"></i> Wali Kelas:
                    <strong class="text-slate-800">{{ $kelas->nama_wali }}</strong>
                    @if($kelas->nip_wali !== '-')
                        <span class="text-slate-400 ml-0.5 font-mono">(NIP: {{ $kelas->nip_wali }})</span>
                    @else
                        <span class="text-slate-400 italic ml-0.5">(NIP belum diisi)</span>
                    @endif
                </span>
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                    class="inline-flex items-center px-2 py-0.5 rounded-lg text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition shadow-sm"
                    title="Ubah Nama atau NIP Wali Kelas jika ada kesalahan ketik">
                    <i class="fa-solid fa-user-pen mr-1 text-amber-600"></i> Edit Wali Kelas & NIP
                </button>
            </div>
        </div>

        <!-- Filter Mapel & Periode & Excel Actions -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.sia-pembiasaan.index', ['kelas' => $kelas->id, 'periode' => $periode]) }}"
               class="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
               title="Input Kehadiran (Sakit, Izin, Alpa) dan Nilai Karakter Pembiasaan Sekelas">
                <i class="fa-solid fa-clipboard-user mr-1.5"></i> Input SIA & Pembiasaan
            </a>

            <button type="button" onclick="document.getElementById('modalImportNilai').classList.remove('hidden')"
                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center"
                title="Impor Nilai, SIA, dan Pembiasaan dari File Excel (.xlsx, .csv) atau PDF (.pdf)">
                <i class="fa-solid fa-file-arrow-up mr-1.5"></i> Impor Excel / PDF
            </button>

            <!-- Menu Unduh Template Excel -->
            <div class="flex items-center bg-white border border-slate-300 rounded-xl overflow-hidden shadow-2xs">
                <a href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'mapel', 'mapel_id' => $activeMapel ? $activeMapel->id : null, 'periode' => $periode]) }}"
                    class="px-3 py-2 text-slate-700 hover:bg-slate-50 font-bold text-xs transition flex items-center border-r border-slate-200"
                    title="Unduh format spreadsheet .xlsx khusus mapel yang sedang aktif">
                    <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600"></i> Template Mapel Ini (.xlsx)
                </a>
                <a href="{{ route('nilai.template.excel', ['kelas' => $kelas->id, 'mode' => 'semua', 'periode' => $periode]) }}"
                    class="px-2.5 py-2 text-slate-600 hover:bg-slate-50 font-semibold text-xs transition flex items-center"
                    title="Unduh format spreadsheet .xlsx lengkap seluruh mata pelajaran sekaligus">
                    <i class="fa-solid fa-table-list mr-1 text-slate-400"></i> Semua Mapel
                </a>
            </div>

            @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.remove('hidden')"
                    class="px-3 py-2 bg-white hover:bg-purple-50 text-purple-700 border border-purple-200 font-bold rounded-xl text-xs transition flex items-center shadow-2xs"
                    title="Atur KKM / KKTP mandiri mata pelajaran khusus {{ $kelas->nama }}">
                    <i class="fa-solid fa-sliders mr-1.5 text-purple-600"></i> KKM Rombel
                </button>

                <button type="button" onclick="document.getElementById('modalKosongkanNilai').classList.remove('hidden')"
                    class="px-3 py-2 bg-white hover:bg-rose-50 text-rose-700 border border-rose-300 font-bold rounded-xl text-xs transition flex items-center shadow-2xs"
                    title="Kosongkan / Reset nilai siswa per kelas jika terjadi kesalahan input atau impor">
                    <i class="fa-solid fa-trash-can mr-1.5 text-rose-600"></i> Kosongkan Nilai
                </button>
            @endif

            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $activeMapel ? $activeMapel->id : null, 'periode' => 'sts']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   STS (Tengah)
                </a>
                <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $activeMapel ? $activeMapel->id : null, 'periode' => 'sas']) }}"
                   class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                   SAS (Akhir)
                </a>
            </div>
        </div>
    </div>

    <!-- Pilihan Tab Mapel -->
    <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-none">
        @foreach($mapels as $m)
            <a href="{{ route('nilai.input.kelas', ['kelas' => $kelas->id, 'mapel_id' => $m->id, 'periode' => $periode]) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center {{ $activeMapel && $activeMapel->id === $m->id ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                <span class="w-4 h-4 rounded-full bg-white/20 text-center mr-1.5 text-[10px]">{{ $m->urutan }}</span>
                {{ $m->nama }}
                <span class="ml-1.5 text-[10px] opacity-80">(KKM: {{ $kelas->getKkmForMapel($m) }})</span>
            </a>
        @endforeach
        <a href="{{ route('kelas.sia-pembiasaan.index', ['kelas' => $kelas->id, 'periode' => $periode]) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition flex items-center bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 shadow-2xs"
           title="Input Kehadiran (Sakit, Izin, Alpa) dan Pembiasaan Sekelas">
            <i class="fa-solid fa-star text-amber-600 mr-1.5"></i>
            SIA & Pembiasaan
        </a>
    </div>

    @if($activeMapel)
        <!-- Form Matriks Input -->
        <form action="{{ route('nilai.simpan.kelas', $kelas->id) }}" method="POST">
            @csrf
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="mapel_id" value="{{ $activeMapel->id }}">

            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                                <i class="fa-solid fa-book-bookmark text-emerald-600 mr-2"></i> {{ $activeMapel->nama }} &bull; Periode {{ strtoupper($periode) }}
                            </h2>
                            <div class="flex items-center gap-1.5">
                                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-md text-xs font-black" title="Batas KKM mapel ini untuk {{ $kelas->nama }}">
                                    KKM: {{ $kelas->getKkmForMapel($activeMapel) }}
                                </span>
                                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                                    <button type="button" onclick="document.getElementById('modalEditKkmKelas').classList.remove('hidden')"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 transition shadow-2xs"
                                        title="Atur KKM khusus untuk {{ $kelas->nama }}">
                                        <i class="fa-solid fa-sliders text-purple-600 text-[11px]"></i>
                                        <span>Ubah KKM</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Total {{ $kelasSiswaList->count() }} siswa di kelas ini. Cukup masukkan nilai siswa (0 - 100).</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="resetFormInputsOnScreen()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center border border-slate-200" title="Kosongkan isian nilai di layar ini (belum tersimpan ke server)">
                            <i class="fa-solid fa-eraser mr-1.5 text-slate-500"></i> Bersihkan Isian Layar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center">
                            <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Nilai Mapel Ini
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">No</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-4 w-28">NIS</th>
                                <th class="py-3 px-4 w-24 text-center">KKM</th>
                                <th class="py-3 px-4 w-44 text-center">Nilai Siswa (0-100)</th>
                                <th class="py-3 px-4 w-32 text-center">Keterangan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kelasSiswaList as $idx => $ks)
                                @php
                                    $n = $nilaiList->get($ks->id);
                                    $fVal = $n && $n->formatif !== null ? (float)$n->formatif : '';
                                    $sVal = $n && $n->sumatif !== null ? (float)$n->sumatif : '';
                                    $val = $sVal !== '' ? $sVal : $fVal;
                                    $kkm = $kelas->getKkmForMapel($activeMapel);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 text-center text-slate-400 font-bold text-xs">{{ $idx + 1 }}</td>
                                    <td class="py-3 px-4 font-bold text-slate-900">
                                        {{ $ks->siswa->nama }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs text-slate-500">{{ $ks->siswa->nis }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold rounded-lg text-xs">
                                            {{ $kkm }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-4 text-center">
                                        <input type="number" step="0.01" min="0" max="100"
                                            name="sumatif[{{ $ks->id }}]"
                                            value="{{ $val }}"
                                            placeholder="-"
                                            oninput="checkKetuntasan(this, 'status_{{ $ks->id }}', {{ $kkm }})"
                                            class="w-full text-center py-1.5 px-2 text-sm font-bold border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500">
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span id="status_{{ $ks->id }}" class="px-2.5 py-1 rounded-full text-xs font-bold {{ $val !== '' ? ($val >= $kkm ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800') : 'text-slate-400' }}">
                                            {{ $val !== '' ? ($val >= $kkm ? 'Tuntas' : 'Belum Tuntas') : '-' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="{{ route('nilai.input.siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id, 'periode' => $periode]) }}"
                                           class="text-xs text-blue-600 hover:underline font-semibold" title="Input lengkap semua mapel">
                                           Input Lengkap &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                        Belum ada siswa di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Mapel: <strong>{{ $activeMapel->nama }}</strong> | Standar KKM: <strong>{{ $activeMapel->kkm ?? 70 }}</strong>
                    </span>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="resetFormInputsOnScreen()" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center border border-slate-200" title="Kosongkan kotak nilai di layar jika salah ketik">
                            <i class="fa-solid fa-eraser mr-1.5 text-slate-500"></i> Bersihkan Isian Layar
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm shadow-md transition flex items-center">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Seluruh Nilai Mapel Ini
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <!-- Modal Edit Nama dan NIP Wali Kelas -->
    <div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-user-pen text-amber-600 mr-2 text-base"></i> Edit Wali Kelas & NIP
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
                </div>
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.update.wali', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap & Gelar Wali Kelas *
                    </label>
                    <input type="text" name="wali_nama" value="{{ old('wali_nama', $kelas->wali_nama ?: ($kelas->wali->name ?? '')) }}" required
                        placeholder="Contoh: Dewi Lestari, S.Pd."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Nama ini akan dicetak pada tanda tangan raport semester.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nomor Induk Pegawai (NIP)
                    </label>
                    <input type="text" name="wali_nip" value="{{ old('wali_nip', $kelas->wali_nip) }}"
                        placeholder="Contoh: 19880315 201101 2 008"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP.</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                        <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Atur KKM Per Rombel -->
    @include('kelas.modal_kkm', ['itemsMapel' => $mapels])

    <!-- Modal Import Nilai Excel -->
    @include('nilai.modal_import_excel')

    <!-- Modal Kosongkan Nilai Kelas -->
    @include('nilai.modal_kosongkan_nilai')

</div>

@push('scripts')
<script>
    function checkKetuntasan(input, statusId, kkm) {
        const statusSpan = document.getElementById(statusId);
        if (!statusSpan) return;
        const val = parseFloat(input.value);
        if (isNaN(val) || input.value.trim() === '') {
            statusSpan.textContent = '-';
            statusSpan.className = 'px-2.5 py-1 rounded-full text-xs font-bold text-slate-400';
        } else if (val >= kkm) {
            statusSpan.textContent = 'Tuntas';
            statusSpan.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800';
        } else {
            statusSpan.textContent = 'Belum Tuntas';
            statusSpan.className = 'px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800';
        }
    }

    function resetFormInputsOnScreen() {
        if (confirm('Kosongkan semua isian nilai pada layar ini? (Perubahan ini hanya di tampilan layar dan belum tersimpan di server)')) {
            document.querySelectorAll('input[name^="sumatif"]').forEach(input => {
                input.value = '';
                input.dispatchEvent(new Event('input'));
            });
        }
    }
</script>
@endpush
@endsection
