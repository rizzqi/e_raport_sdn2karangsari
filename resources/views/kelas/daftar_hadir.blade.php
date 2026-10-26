@extends('layouts.app')

@section('title', 'Daftar Hadir Ujian ' . strtoupper($periode) . ' - ' . $kelas->nama)

@section('content')
<div class="space-y-6">

    <!-- Toolbar Atas (Hanya tampil di layar, otomatis disembunyikan saat dicetak) -->
    <div class="no-print space-y-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center space-x-3">
                <a href="{{ route('kelas.show', $kelas->id) }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Kembali ke Detail Kelas">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h1 class="text-base font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-clipboard-user text-emerald-600 mr-2"></i> Lembar Daftar Hadir Ujian
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Total {{ count($siswaList) }} Siswa
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Pilihan Periode Ujian -->
                <div class="inline-flex rounded-xl bg-slate-100 p-1 text-xs font-bold border border-slate-200">
                    <a href="{{ route('kelas.daftar-hadir', array_filter(['kelas' => $kelas->id, 'periode' => 'sts', 'mapel' => $mapel, 'ruang' => $ruang, 'tanggal' => $tanggal, 'waktu' => $waktu, 'pengawas' => $pengawas])) }}"
                       class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        STS (Tengah Semester)
                    </a>
                    <a href="{{ route('kelas.daftar-hadir', array_filter(['kelas' => $kelas->id, 'periode' => 'sas', 'mapel' => $mapel, 'ruang' => $ruang, 'tanggal' => $tanggal, 'waktu' => $waktu, 'pengawas' => $pengawas])) }}"
                       class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                        SAS (Akhir Semester)
                    </a>
                </div>

                <!-- Tombol Unduh Excel -->
                <a href="{{ route('kelas.daftar-hadir.excel', array_filter(['kelas' => $kelas->id, 'periode' => $periode, 'mapel' => $mapel, 'ruang' => $ruang, 'tanggal' => $tanggal, 'waktu' => $waktu, 'pengawas' => $pengawas])) }}"
                   class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition shadow-sm">
                    <i class="fa-solid fa-file-excel mr-1.5 text-emerald-600"></i> Unduh Excel (.xls)
                </a>

                <!-- Tombol Cetak / PDF -->
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-sm">
                    <i class="fa-solid fa-print mr-1.5"></i> Cetak / Simpan PDF (A4)
                </button>
            </div>
        </div>

        <!-- Panel Form Atur Identitas Ujian (Mata Pelajaran, Ruang, Hari/Tanggal, Waktu, Pengawas) -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Atur Identitas & Jadwal Ujian</h2>
                </div>
                <span class="text-[11px] text-slate-400">Otomatis tercetak di lembar A4 & terisi di unduhan Excel</span>
            </div>

            <form method="GET" action="{{ route('kelas.daftar-hadir', $kelas->id) }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                <input type="hidden" name="periode" value="{{ $periode }}">

                <!-- Mata Pelajaran -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                    <select name="mapel" id="select_mapel" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">-- Kosongkan (Titik-titik) --</option>
                        @foreach($mapelList as $m)
                            <option value="{{ $m->nama }}" {{ $mapel === $m->nama ? 'selected' : '' }}>{{ $m->nama }}</option>
                        @endforeach
                        @if($mapel && !$mapelList->pluck('nama')->contains($mapel))
                            <option value="{{ $mapel }}" selected>{{ $mapel }} (Kustom)</option>
                        @endif
                        <option value="__custom__">+ Ketik Mapel Lainnya...</option>
                    </select>
                </div>

                <!-- Hari / Tanggal -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Hari / Tanggal</label>
                    <div class="flex">
                        <input type="text" id="input_tanggal" name="tanggal" value="{{ $tanggal }}" placeholder="Contoh: Senin, 20 Oktober 2026" class="w-full text-xs rounded-l-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <input type="date" id="date_picker" class="sr-only" onchange="formatDateToIndo(this.value)">
                        <button type="button" onclick="openDatePicker()" class="px-3 bg-slate-100 hover:bg-slate-200 border border-l-0 border-slate-300 rounded-r-xl text-slate-600 text-xs transition" title="Pilih dari Kalender">
                            <i class="fa-solid fa-calendar-day"></i>
                        </button>
                    </div>
                </div>

                <!-- Waktu Ujian -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Waktu Ujian</label>
                    <input type="text" name="waktu" list="waktu-presets" value="{{ $waktu }}" placeholder="Contoh: 07.30 - 09.00 WIB" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <datalist id="waktu-presets">
                        <option value="07.30 - 09.00 WIB">Jam Ke-1 (07.30 - 09.00 WIB)</option>
                        <option value="09.30 - 11.00 WIB">Jam Ke-2 (09.30 - 11.00 WIB)</option>
                        <option value="07.30 - 08.30 WIB">Jam Ke-1 Singkat (07.30 - 08.30 WIB)</option>
                    </datalist>
                </div>

                <!-- Ruang Ujian -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Ruang Ujian</label>
                    <input type="text" name="ruang" list="ruang-presets" value="{{ $ruang }}" placeholder="Contoh: Ruang 01 / {{ $kelas->nama }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                    <datalist id="ruang-presets">
                        <option value="Ruang 01">Ruang 01</option>
                        <option value="Ruang 02">Ruang 02</option>
                        <option value="Ruang 03">Ruang 03</option>
                        <option value="Ruang 04">Ruang 04</option>
                        <option value="Ruang 05">Ruang 05</option>
                        <option value="Ruang 06">Ruang 06</option>
                        <option value="Ruang {{ $kelas->nama }}">Ruang {{ $kelas->nama }}</option>
                    </datalist>
                </div>

                <!-- Guru Pengawas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Guru Pengawas</label>
                    <select name="pengawas" id="select_pengawas" class="w-full text-xs rounded-xl border-slate-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">Otomatis (Sesuai Mapel)</option>
                        <option value="{{ $kelas->nama_wali }}" {{ $pengawas === $kelas->nama_wali ? 'selected' : '' }}>
                            Wali Kelas: {{ $kelas->nama_wali }}
                        </option>
                        @foreach($kelas->guruPengampu as $gp)
                            <option value="{{ $gp->nama }}" {{ $pengawas === $gp->nama ? 'selected' : '' }}>
                                {{ $gp->mapel ? $gp->mapel->nama : 'Guru Mapel' }}: {{ $gp->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                        <i class="fa-solid fa-check mr-1.5"></i> Terapkan
                    </button>
                    @if($mapel || $tanggal || $waktu || $ruang || $pengawas)
                        <a href="{{ route('kelas.daftar-hadir', ['kelas' => $kelas->id, 'periode' => $periode]) }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition border border-slate-200" title="Reset Identitas ke Titik-titik">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Lembar Dokumen Siap Cetak (A4 Style) -->
    <div class="printable-sheet bg-white p-8 md:p-12 rounded-2xl border border-slate-200 shadow-sm max-w-4xl mx-auto text-black">
        
        <!-- KOP SURAT RESMI SEKOLAH DENGAN LOGO RAPOR -->
        <div class="pb-3 border-b-4 border-double border-black">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo Kiri (Pemda Banyuwangi) -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 flex items-center justify-center">
                    @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                        <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Pemda" class="max-h-16 sm:max-h-20 max-w-16 sm:max-w-20 object-contain">
                    @endif
                </div>

                <!-- Teks Kop Tengah -->
                <div class="flex-1 text-center font-serif text-black space-y-0.5">
                    <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight text-black m-0">
                        {{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}
                    </h3>
                    <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight text-black m-0">
                        {{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}
                    </h3>
                    <h2 class="text-base sm:text-xl font-black uppercase text-black tracking-wider leading-tight m-0 mt-0.5">
                        {{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
                    </h2>
                    <p class="text-[11px] sm:text-xs text-black leading-tight m-0 mt-1">
                        Alamat: {{ $sekolah->alamat ?? 'Jl. Parijatah No. 45 Karangsari, Kec. Sempu, Banyuwangi' }}
                    </p>
                    @if(!empty($sekolah->kontak))
                        <p class="text-[10px] text-black leading-tight m-0">
                            {{ $sekolah->kontak }}
                        </p>
                    @endif
                </div>

                <!-- Logo Kanan (Tut Wuri Handayani / Sekolah) -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 flex items-center justify-center">
                    @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                        <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Tut Wuri" class="max-h-16 sm:max-h-20 max-w-16 sm:max-w-20 object-contain">
                    @endif
                </div>
            </div>
        </div>

        <!-- JUDUL DAFTAR HADIR -->
        <div class="text-center my-4">
            <h2 class="text-base font-black uppercase underline tracking-wider m-0">
                DAFTAR HADIR {{ $periode === 'sts' ? 'ASESMEN / SUMATIF TENGAH SEMESTER (STS)' : 'ASESMEN / SUMATIF AKHIR SEMESTER (SAS)' }}
            </h2>
            <h3 class="text-xs font-bold uppercase tracking-wider mt-1 m-0">
                TAHUN AJARAN {{ $kelas->tahunAjaran->nama_lengkap ?? '2026/2027 Ganjil' }}
            </h3>
        </div>

        <!-- IDENTITAS UJIAN & RUANG -->
        <div class="text-xs font-semibold grid grid-cols-2 gap-x-6 gap-y-1 mb-4 border border-black p-3 rounded-lg">
            <div class="space-y-1">
                <div class="flex">
                    <span class="w-28 text-slate-800">Mata Pelajaran</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold border-b border-dotted border-black">
                        {{ !empty($mapel) ? $mapel : '...................................................' }}
                    </span>
                </div>
                <div class="flex">
                    <span class="w-28 text-slate-800">Kelas / Fase</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold">{{ $kelas->nama }} / {{ $kelas->fase ?? 'Fase A' }}</span>
                </div>
                <div class="flex">
                    <span class="w-28 text-slate-800">Semester</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold">{{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }}</span>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex">
                    <span class="w-28 text-slate-800">Hari / Tanggal</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold border-b border-dotted border-black">
                        {{ !empty($tanggal) ? $tanggal : '...................................................' }}
                    </span>
                </div>
                <div class="flex">
                    <span class="w-28 text-slate-800">Waktu</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold border-b border-dotted border-black">
                        {{ !empty($waktu) ? $waktu : '...................................................' }}
                    </span>
                </div>
                <div class="flex">
                    <span class="w-28 text-slate-800">Ruang Ujian</span>
                    <span class="w-3">:</span>
                    <span class="flex-1 font-bold border-b border-dotted border-black">
                        {{ !empty($ruang) ? $ruang : '...................................................' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- TABEL PRESENSI SISWA -->
        <table class="w-full border-collapse border border-black text-xs text-black mb-6">
            <thead>
                <tr class="bg-slate-100 text-center font-bold">
                    <th class="border border-black py-2 px-1 w-8">No.</th>
                    <th class="border border-black py-2 px-2 w-20">NIS</th>
                    <th class="border border-black py-2 px-2 w-24">NISN</th>
                    <th class="border border-black py-2 px-3 text-left">Nama Lengkap Siswa</th>
                    <th class="border border-black py-2 px-2 w-48 text-center" colspan="2">Tanda Tangan / Kehadiran</th>
                    <th class="border border-black py-2 px-2 w-16 text-center">Ket</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswaList as $idx => $ks)
                    @php
                        $nomor = $idx + 1;
                        $isGanjil = ($nomor % 2 !== 0);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="border border-black text-center py-2.5 px-1 font-semibold">{{ $nomor }}</td>
                        <td class="border border-black text-center py-2.5 px-1 font-mono text-[11px]">{{ $ks->siswa->nis }}</td>
                        <td class="border border-black text-center py-2.5 px-1 font-mono text-[11px]">{{ $ks->siswa->nisn ?? '-' }}</td>
                        <td class="border border-black py-2.5 px-3 font-semibold uppercase">{{ $ks->siswa->nama }}</td>
                        
                        <!-- Kolom Tanda Tangan Zig-Zag Kiri -->
                        <td class="border border-black py-2.5 px-2 w-24 align-middle {{ $isGanjil ? 'text-left' : 'bg-slate-50/50' }}">
                            @if($isGanjil)
                                <span class="font-bold text-[11px]">{{ $nomor }}.</span> <span class="text-slate-300">...........</span>
                            @endif
                        </td>

                        <!-- Kolom Tanda Tangan Zig-Zag Kanan -->
                        <td class="border border-black py-2.5 px-2 w-24 align-middle {{ !$isGanjil ? 'text-left' : 'bg-slate-50/50' }}">
                            @if(!$isGanjil)
                                <span class="font-bold text-[11px]">{{ $nomor }}.</span> <span class="text-slate-300">...........</span>
                            @endif
                        </td>

                        <td class="border border-black text-center py-2.5 px-1"></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="border border-black text-center py-6 text-slate-400">
                            Belum ada siswa yang terdaftar di kelas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- REKAPITULASI & TANDA TANGAN PENGAWAS -->
        <div class="grid grid-cols-2 gap-6 text-xs text-black pt-2 page-break-inside-avoid">
            <!-- Box Rekapitulasi Kehadiran -->
            <div class="border border-black p-3 rounded-lg space-y-1.5 self-start">
                <p class="font-bold underline text-center uppercase tracking-wider mb-2">REKAPITULASI KEHADIRAN</p>
                <div class="flex justify-between">
                    <span>Jumlah Siswa Terdaftar</span>
                    <span class="font-bold">: {{ count($siswaList) }} Orang</span>
                </div>
                <div class="flex justify-between">
                    <span>Jumlah Siswa Hadir</span>
                    <span>: ......... Orang</span>
                </div>
                <div class="flex justify-between">
                    <span>Jumlah Siswa Tidak Hadir</span>
                    <span>: ......... Orang</span>
                </div>
                <div class="pl-4 text-[11px] space-y-0.5 text-slate-700">
                    <div class="flex justify-between">
                        <span>- Sakit (S)</span>
                        <span>: ......... Orang</span>
                    </div>
                    <div class="flex justify-between">
                        <span>- Izin (I)</span>
                        <span>: ......... Orang</span>
                    </div>
                    <div class="flex justify-between">
                        <span>- Tanpa Keterangan (A)</span>
                        <span>: ......... Orang</span>
                    </div>
                </div>
            </div>

            <!-- Tanda Tangan Pengawas & Kepala Sekolah -->
            <div class="text-center space-y-10">
                <div>
                    <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, {{ !empty($tanggal) ? preg_replace('/^[A-Za-z]+,\s*/', '', $tanggal) : '........................................ 2026' }}</p>
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <div>
                            <p class="font-medium">Mengetahui,</p>
                            <p class="font-bold">Kepala Sekolah</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline uppercase">{{ $sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</p>
                            <p class="text-[11px]">NIP. {{ $sekolah->nip ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="font-medium">&nbsp;</p>
                            <p class="font-bold">{{ $pengawasInfo['jabatan'] ?? 'Guru Pengawas / Pengampu' }}</p>
                            <div class="h-16"></div>
                            <p class="font-bold underline uppercase">{{ $pengawasInfo['nama'] ?? ($kelas->nama_wali ?? '.........................................') }}</p>
                            <p class="text-[11px]">NIP. {{ ($pengawasInfo['nip'] ?? '-') !== '-' ? $pengawasInfo['nip'] : '.........................................' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@push('styles')
<style>
    @media print {
        /* Sembunyikan elemen navigasi utama */
        nav, header, aside, .no-print, footer, #sidebar {
            display: none !important;
        }

        /* Atur body & layout agar muat di kertas A4 */
        body {
            background: #ffffff !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 11pt !important;
        }

        .printable-sheet {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        /* Hilangkan margin default browser */
        @page {
            size: A4 portrait;
            margin: 1.5cm 1.2cm 1.5cm 1.2cm;
        }

        .page-break-inside-avoid {
            page-break-inside: avoid;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function openDatePicker() {
        const picker = document.getElementById('date_picker');
        if (!picker) return;
        if (picker.showPicker) {
            picker.showPicker();
        } else {
            picker.click();
        }
    }

    function formatDateToIndo(val) {
        if (!val) return;
        const parts = val.split('-');
        if (parts.length !== 3) return;
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const date = new Date(year, month, day);

        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        const hari = days[date.getDay()];
        const tgl = day;
        const bln = months[month];
        const thn = year;

        const inputTanggal = document.getElementById('input_tanggal');
        if (inputTanggal) {
            inputTanggal.value = `${hari}, ${tgl} ${bln} ${thn}`;
        }
    }

    const mapelPengawasMap = {
        @foreach($kelas->guruPengampu as $gp)
            @if($gp->mapel)
                "{{ strtolower(trim($gp->mapel->nama)) }}": "{{ $gp->nama }}",
            @endif
        @endforeach
    };

    document.getElementById('select_mapel')?.addEventListener('change', function() {
        if (this.value === '__custom__') {
            const custom = prompt('Masukkan nama Mata Pelajaran:');
            if (custom && custom.trim() !== '') {
                const trimmed = custom.trim();
                const opt = new Option(trimmed, trimmed, true, true);
                this.add(opt);
                this.value = trimmed;
            } else {
                this.value = '';
            }
        } else if (this.value && mapelPengawasMap[this.value.toLowerCase().trim()]) {
            const selectPengawas = document.getElementById('select_pengawas');
            if (selectPengawas) {
                selectPengawas.value = mapelPengawasMap[this.value.toLowerCase().trim()];
            }
        }
    });
</script>
@endpush
@endsection
