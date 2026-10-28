@extends('layouts.app')

@section('title', 'Cetak Rapor Formatif Sekelas - ' . $kelas->nama . ' (' . $namaBulan . ' ' . $tahun . ')')

@section('content')
<style>
    @media print {
        @page {
            size: 215mm 330mm;
            margin: 10mm 15mm;
        }
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        main {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .no-print {
            display: none !important;
        }
        .sheet-raport {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            page-break-after: always !important;
            break-after: page !important;
        }
        .sheet-raport:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
    }
</style>

<div class="space-y-6">

    <!-- Action Toolbar (No-Print) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.nilai-formatif.index', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>

            <!-- Ganti Bulan Cepat -->
            <form action="{{ route('kelas.nilai-formatif.cetak-sekelas', $kelas->id) }}" method="GET" class="flex items-center space-x-1 bg-slate-50 border border-slate-200 px-2 py-1 rounded-xl">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <span class="text-xs font-bold text-slate-500">Bulan:</span>
                <select name="bulan" onchange="this.form.submit()" class="bg-white border border-slate-300 text-xs font-semibold rounded-lg px-2 py-0.5 focus:outline-none">
                    @foreach(App\Models\NilaiFormatifBulanan::NAMA_BULAN as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                    @endforeach
                </select>
            </form>

            <span class="inline-flex items-center px-2.5 py-1 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-users mr-1.5 text-indigo-600"></i> {{ count($laporanSiswa) }} Siswa
            </span>
            <span class="hidden lg:inline-flex items-center px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-ruler-combined mr-1 text-amber-600"></i> Format Standar F4 (215 × 330 mm)
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Lompat ke Siswa Dropdown -->
            <select onchange="if(this.value) { document.getElementById(this.value).scrollIntoView({behavior: 'smooth'}); }"
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">-- Lompat ke Siswa --</option>
                @foreach($laporanSiswa as $idx => $item)
                    <option value="sheet-siswa-{{ $item['siswa']->id }}">{{ $idx + 1 }}. {{ $item['siswa']->nama }}</option>
                @endforeach
            </select>

            <!-- Ganti Tanggal Raport (No-Print) -->
            <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-xs">
                <i class="fa-regular fa-calendar-days text-slate-500"></i>
                <label for="inputTanggalRaportSekelas" class="font-bold text-slate-700 whitespace-nowrap">Tgl Rapor:</label>
                <input type="date" id="inputTanggalRaportSekelas" value="{{ $tanggalObj->format('Y-m-d') }}"
                       onchange="updateTanggalSekelas(this.value)"
                       class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Tombol Cetak Semua Dokumen -->
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Semua Rapor (Ctrl+P)
            </button>
        </div>
    </div>

    <!-- Alert Petunjuk Cetak (No-Print) -->
    <div class="bg-indigo-50 border border-indigo-200/80 p-3.5 rounded-2xl flex items-start space-x-3 text-xs text-indigo-900 no-print">
        <i class="fa-solid fa-circle-info text-indigo-600 text-sm mt-0.5 flex-shrink-0"></i>
        <div class="space-y-0.5">
            <p class="font-bold">Petunjuk Mencetak Rapor Formatif Sekelas:</p>
            <p class="text-indigo-800">
                Klik tombol <strong>"Cetak Semua Rapor (Ctrl+P)"</strong> di atas. Di jendela print dialog browser, pilih ukuran kertas <strong>F4 / Folio (atau Legal)</strong>. Setiap rapor siswa otomatis terpisah 1 lembar utuh berkat sistem <em>page-break</em> standar.
            </p>
        </div>
    </div>

    @if(count($laporanSiswa) === 0)
        <div class="bg-white p-12 text-center rounded-2xl border border-slate-200">
            <i class="fa-solid fa-user-slash text-slate-300 text-4xl mb-3"></i>
            <h3 class="text-base font-bold text-slate-700">Belum Ada Siswa di Kelas Ini</h3>
            <p class="text-xs text-slate-500 mt-1">Tambahkan siswa terlebih dahulu melalui menu Penempatan Siswa.</p>
        </div>
    @endif

    <!-- Loop Lembar Rapor Siswa Sekelas -->
    <div>
        @foreach($laporanSiswa as $itemIndex => $item)
            @php
                $ks = $item['kelasSiswa'];
                $siswa = $item['siswa'];
                $baris = $item['baris'];
                $totalNilai = $item['totalNilai'];
                $rataRataFormat = $item['rataRataFormat'];
                $sakit = $item['sakit'];
                $izin = $item['izin'];
                $alpa = $item['alpa'];
            @endphp

            <!-- Lembar Raport Per Siswa (Standard F4 / Folio) -->
            <div id="sheet-siswa-{{ $siswa->id }}"
                 class="sheet-raport max-w-[850px] mx-auto bg-white p-8 sm:p-12 rounded-2xl border border-slate-200 shadow-xl mb-10 print:mb-0 print:shadow-none print:border-none print:p-0 print:m-0 text-slate-900 relative">

                <!-- Badge Nomor Siswa di Layar (No-Print) -->
                <div class="no-print mb-4 pb-2 border-b border-slate-200 flex items-center justify-between text-xs text-slate-500">
                    <span class="font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">
                        Siswa #{{ $itemIndex + 1 }} dari {{ count($laporanSiswa) }} : {{ $siswa->nama }}
                    </span>
                    <a href="{{ route('kelas.nilai-formatif.input-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $ks->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                       class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Nilai Siswa Ini
                    </a>
                </div>

                <!-- 1. KOP SURAT RESMI STANDAR F4 (Persis Raport UTS) -->
                <div class="pb-3 border-b-4 border-double border-slate-900">
                    <div class="flex items-center justify-between gap-4">
                        <!-- Logo Kiri -->
                        <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center">
                            @if($sekolah && $sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                                <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Pemda" class="max-h-20 max-w-20 object-contain">
                            @endif
                        </div>

                        <!-- Teks Kop Tengah -->
                        <div class="flex-1 text-center font-serif text-slate-900 space-y-0.5">
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight">
                                {{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}
                            </h3>
                            <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight">
                                {{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}
                            </h3>
                            <h2 class="text-base sm:text-xl font-black uppercase text-slate-900 tracking-wider leading-tight">
                                {{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
                            </h2>
                            <p class="text-[11px] sm:text-xs italic text-slate-600 leading-tight">
                                {{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}
                            </p>
                            @if($sekolah && $sekolah->kontak)
                                <p class="text-[10px] text-slate-500 leading-tight">
                                    {{ $sekolah->kontak }}
                                </p>
                            @endif
                        </div>

                        <!-- Logo Kanan -->
                        <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center">
                            @if($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                                <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Tut Wuri / Sekolah" class="max-h-20 max-w-20 object-contain">
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 2. JUDUL RAPOR -->
                <div class="text-center my-5">
                    <h1 class="text-base sm:text-lg font-black uppercase tracking-wide underline underline-offset-4">
                        LAPORAN HASIL BELAJAR PESERTA DIDIK
                    </h1>
                    <p class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-700 mt-1">
                        PENILAIAN FORMATIF BULANAN &bull; {{ strtoupper($namaBulan) }} {{ $tahun }}
                    </p>
                </div>

                <!-- 3. IDENTITAS PESERTA DIDIK -->
                <div class="grid grid-cols-2 gap-4 text-xs sm:text-sm mb-5 pb-2">
                    <div class="space-y-1">
                        <div class="flex">
                            <span class="w-36 text-slate-600">Nama Peserta Didik</span>
                            <span class="font-bold">: {{ $siswa->nama }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-36 text-slate-600">NIS / NISN</span>
                            <span class="font-mono">: {{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="flex">
                            <span class="w-32 text-slate-600">Kelas / Fase</span>
                            <span class="font-bold">: {{ $kelas->nama }} / {{ $kelas->fase ?? 'A' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-32 text-slate-600">Semester / Tahun</span>
                            <span class="font-bold">: {{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }} / {{ $kelas->tahunAjaran->tahun ?? $tahun }}</span>
                        </div>
                    </div>
                </div>

                <!-- 4. TABEL NILAI MATA PELAJARAN (Format Rapor UTS) -->
                <div class="mb-5">
                    <table class="w-full text-xs sm:text-sm border-collapse border border-slate-900">
                        <thead>
                            <tr class="bg-slate-100 text-slate-900 font-bold text-center">
                                <th class="border border-slate-900 py-2 px-2 w-10">No</th>
                                <th class="border border-slate-900 py-2 px-3 text-left">Mata Pelajaran</th>
                                <th class="border border-slate-900 py-2 px-3 w-20">KKM</th>
                                <th class="border border-slate-900 py-2 px-3 w-28">Nilai Formatif</th>
                                <th class="border border-slate-900 py-2 px-3 w-28">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($baris as $b)
                                <tr>
                                    <td class="border border-slate-900 py-1.5 px-2 text-center">{{ $b['no'] }}</td>
                                    <td class="border border-slate-900 py-1.5 px-3 font-medium">{{ $b['mapel_nama'] }}</td>
                                    <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['kkm'] }}</td>
                                    <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['nilai_format'] }}</td>
                                    <td class="border border-slate-900 py-1.5 px-3 text-center text-xs font-bold {{ $b['keterangan'] === 'Tuntas' ? 'text-emerald-700' : ($b['keterangan'] === 'Belum Tuntas' ? 'text-rose-600' : 'text-slate-400') }}">
                                        {{ $b['keterangan'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="border border-slate-900 py-4 text-center text-slate-400">
                                        Belum ada data mata pelajaran untuk kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-bold bg-slate-50">
                            <tr>
                                <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Jumlah Nilai</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center">{{ \App\Services\HitungNilaiService::formatAngka($totalNilai) }}</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                            </tr>
                            <tr>
                                <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Rata-rata Nilai</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center">{{ $rataRataFormat }}</td>
                                <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- 5. TABEL KETIDAKHADIRAN BULANAN -->
                <div class="mb-8">
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider mb-2 text-slate-800">
                        Ketidakhadiran (Bulan {{ $namaBulan }} {{ $tahun }})
                    </h4>
                    <table class="w-full sm:w-1/2 text-xs sm:text-sm border-collapse border border-slate-900">
                        <tbody>
                            <tr>
                                <td class="border border-slate-900 py-1.5 px-3 w-40 font-medium">1. Sakit</td>
                                <td class="border border-slate-900 py-1.5 px-3 font-bold text-center w-24">{{ $sakit }} hari</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 py-1.5 px-3 font-medium">2. Izin</td>
                                <td class="border border-slate-900 py-1.5 px-3 font-bold text-center">{{ $izin }} hari</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 py-1.5 px-3 font-medium">3. Tanpa Keterangan</td>
                                <td class="border border-slate-900 py-1.5 px-3 font-bold text-center">{{ $alpa }} hari</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 6. BLOK 3 TANDA TANGAN RESMI (Standar Rapor UTS) -->
                <div class="grid grid-cols-3 gap-2 text-center text-xs sm:text-sm pt-4">
                    <div>
                        <p>Mengetahui,</p>
                        <p class="font-medium">Orang Tua / Wali Murid</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline">( ........................................ )</p>
                    </div>

                    <div>
                        <p>&nbsp;</p>
                        <p class="font-medium">Kepala Sekolah,</p>
                        <div class="h-20 flex items-center justify-center">
                            @if($sekolah && $sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                                <img src="{{ asset('storage/' . $sekolah->ttd) }}" alt="Tanda Tangan Kepala Sekolah" class="max-h-20 max-w-[150px] object-contain">
                            @endif
                        </div>
                        <p class="font-bold underline">{{ $sekolah->kepsek ?? '( ........................................ )' }}</p>
                        <p class="text-[11px] text-slate-600">NIP. {{ $sekolah->nip ?? '-' }}</p>
                    </div>

                    <div>
                        <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, <span class="label-tanggal-sekelas">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                        <p class="font-medium">Wali Kelas,</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                        <p class="text-[11px] text-slate-600">{{ $kelas->nip_wali !== '-' ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</p>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</div>

<script>
    function updateTanggalSekelas(dateStr) {
        if (!dateStr) return;
        const d = new Date(dateStr);
        const bulanIndo = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const day = d.getDate();
        const month = bulanIndo[d.getMonth()];
        const year = d.getFullYear();
        const strFormatted = `${day} ${month} ${year}`;
        document.querySelectorAll('.label-tanggal-sekelas').forEach(el => {
            el.textContent = strFormatted;
        });
    }
</script>
@endsection
