@extends('layouts.app')

@section('title', 'Cetak Rekap Semester Sekelas (1 Siswa 1 Lembar F4) - ' . $kelas->nama)

@section('content')
<style>
    @media print {
        @page {
            size: 215mm 330mm portrait;
            margin: 8mm 12mm;
        }
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background: white !important;
            color: #000 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 8pt !important;
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
        .sheet-f4 {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 0 15mm 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            page-break-after: always !important;
            break-after: page !important;
        }
        .sheet-f4:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
            margin-bottom: 0 !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 7.5pt !important;
        }
        th, td {
            border: 0.5pt solid #000 !important;
            padding: 2.5px 3px !important;
            color: #000 !important;
            line-height: 1.2 !important;
        }
        thead th {
            background-color: #f1f5f9 !important;
            font-weight: bold !important;
            text-align: center !important;
        }
        tfoot td {
            background-color: #f8fafc !important;
            font-weight: bold !important;
        }
    }
</style>

<div class="space-y-6">

    <!-- Action Toolbar (No-Print) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.show', $kelas->id) }}"
               class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Kelas
            </a>

            <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-xs font-bold">
                <i class="fa-solid fa-users mr-1.5 text-blue-600"></i> {{ count($laporanSiswa) }} Siswa Sekelas
            </span>
            <span class="inline-flex items-center px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-bold">
                <i class="fa-solid fa-file mr-1.5 text-amber-600"></i> Format Pas 1 Siswa 1 Lembar F4
            </span>
            <span class="hidden lg:inline-flex items-center px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Bulan Kosong Otomatis Diabaikan dari Pembagi
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Lompat ke Siswa Dropdown -->
            <select onchange="if(this.value) { document.getElementById(this.value).scrollIntoView({behavior: 'smooth'}); }"
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">-- Lompat ke Siswa --</option>
                @foreach($laporanSiswa as $idx => $item)
                    <option value="sheet-siswa-{{ $item['siswa']->id }}">{{ $idx + 1 }}. {{ $item['siswa']->nama }}</option>
                @endforeach
            </select>

            <!-- Ganti Tanggal Cetak -->
            <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-xs">
                <i class="fa-regular fa-calendar-days text-slate-500"></i>
                <label for="inputTanggalCetak" class="font-bold text-slate-700 whitespace-nowrap">Tgl Rapor:</label>
                <input type="date" id="inputTanggalCetak" value="{{ $tanggalObj->format('Y-m-d') }}"
                       onchange="updateTanggalSekelas(this.value)"
                       class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Tombol Cetak Dokumen -->
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Seluruh Siswa (Ctrl+P)
            </button>
        </div>
    </div>

    <!-- Container Lembar Rapor Masing-masing Siswa (1 Siswa = 1 Lembar F4) -->
    <div class="space-y-8 print:space-y-0">
        @foreach($laporanSiswa as $idxSiswa => $item)
            @php
                $siswa = $item['siswa'];
                $ks = $item['kelasSiswa'];
            @endphp
            <div id="sheet-siswa-{{ $siswa->id }}" class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm sheet-f4 text-black">

                <!-- Kop Surat Resmi -->
                <div class="border-b-2 border-black pb-2 mb-3">
                    <div class="flex items-center justify-between text-center relative">
                        @if($kelas->sekolah && $kelas->sekolah->logo)
                            <img src="{{ asset('storage/' . $kelas->sekolah->logo) }}" alt="Logo" class="w-14 h-14 object-contain absolute left-0 top-0">
                        @endif

                        <div class="w-full px-16">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-black">
                                {{ $kelas->sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}
                            </h3>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-black">
                                {{ $kelas->sekolah->dinas ?? 'DINAS PENDIDIKAN' }}
                            </h3>
                            <h1 class="text-base font-black uppercase tracking-wide text-black my-0.5">
                                {{ $kelas->sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
                            </h1>
                            <p class="text-[8pt] text-black">
                                {{ $kelas->sekolah->alamat ?? 'Jl. Parijatah No. 45 Karangsari, Kec. Sempu, Kab. Banyuwangi' }}
                                @if($kelas->sekolah && $kelas->sekolah->kontak)
                                    &bull; {{ $kelas->sekolah->kontak }}
                                @endif
                            </p>
                        </div>

                        @if($kelas->sekolah && $kelas->sekolah->logo_kanan)
                            <img src="{{ asset('storage/' . $kelas->sekolah->logo_kanan) }}" alt="Logo Tut Wuri" class="w-14 h-14 object-contain absolute right-0 top-0">
                        @endif
                    </div>
                </div>

                <!-- Judul Dokumen -->
                <div class="text-center mb-3">
                    <h2 class="text-sm font-black uppercase tracking-wider text-black underline">
                        LAPORAN REKAPITULASI HASIL BELAJAR PESERTA DIDIK
                    </h2>
                    <p class="text-[8.5pt] font-bold text-black mt-0.5">
                        SEMESTER {{ strtoupper($kelas->tahunAjaran->semester ?? 'GANJIL') }} &bull; TAHUN AJARAN {{ $kelas->tahunAjaran->nama_lengkap ?? '2026/2027' }}
                    </p>
                </div>

                <!-- Biodata Peserta Didik -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-[8pt] border border-black p-2.5 rounded-sm mb-3 bg-slate-50/50 print:bg-transparent">
                    <div class="space-y-0.5">
                        <div class="flex">
                            <span class="w-32 font-semibold">Nama Peserta Didik</span>
                            <span class="mr-1">:</span>
                            <span class="font-black uppercase">{{ $siswa->nama }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-semibold">NIS / NISN</span>
                            <span class="mr-1">:</span>
                            <span class="font-mono">{{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-semibold">Kelas / Fase</span>
                            <span class="mr-1">:</span>
                            <span>{{ $kelas->nama }} / {{ $kelas->fase ?? 'Fase A' }}</span>
                        </div>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex">
                            <span class="w-32 font-semibold">Sekolah</span>
                            <span class="mr-1">:</span>
                            <span>{{ $kelas->sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-semibold">Semester</span>
                            <span class="mr-1">:</span>
                            <span class="font-bold uppercase">{{ $kelas->tahunAjaran->semester ?? 'Ganjil' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-32 font-semibold">Tahun Pelajaran</span>
                            <span class="mr-1">:</span>
                            <span>{{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Rekapitulasi Nilai Bulanan & Semester -->
                <table class="w-full text-left border-collapse border border-black text-[7.5pt] mb-3">
                    <thead>
                        <tr class="bg-slate-100 text-black">
                            <th class="border border-black p-1 text-center w-6" rowspan="2">No</th>
                            <th class="border border-black p-1 text-left min-w-[140px]" rowspan="2">Mata Pelajaran</th>
                            <th class="border border-black p-1 text-center w-9" rowspan="2">KKM</th>
                            <th class="border border-black p-1 text-center" colspan="{{ count($bulanSemester) }}">Nilai Bulanan</th>
                            <th class="border border-black p-1 text-center w-14 font-black bg-blue-100/70" rowspan="2">Nilai Akhir<br><span class="text-[6pt] font-normal">(Rata²)</span></th>
                            <th class="border border-black p-1 text-center w-10 font-bold" rowspan="2">Predikat</th>
                            <th class="border border-black p-1 text-center w-18 font-bold" rowspan="2">Keterangan</th>
                        </tr>
                        <tr class="bg-slate-50 text-[6.5pt] text-black">
                            @foreach($bulanSemester as $bNum => $bNama)
                                <th class="border border-black p-0.5 text-center w-9 font-semibold">
                                    {{ substr($bNama, 0, 3) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($item['baris'] as $b)
                            <tr>
                                <td class="border border-black p-1 text-center font-bold">{{ $b['no'] }}</td>
                                <td class="border border-black p-1 font-semibold">{{ $b['mapel']->nama }}</td>
                                <td class="border border-black p-1 text-center text-slate-700 font-mono">{{ $b['kkm'] }}</td>
                                @foreach($bulanSemester as $bNum => $bNama)
                                    @php
                                        $valBulan = $b['nilai_bulanan'][$bNum] ?? null;
                                    @endphp
                                    <td class="border border-black p-1 text-center font-mono {{ ($valBulan !== null && $valBulan < $b['kkm']) ? 'text-rose-600 font-bold' : '' }}">
                                        {{ $valBulan !== null ? \App\Services\HitungNilaiService::formatAngka($valBulan) : '-' }}
                                    </td>
                                @endforeach
                                <td class="border border-black p-1 text-center font-black bg-blue-50/70 text-black font-mono">
                                    {{ $b['rata_rata_format'] }}
                                </td>
                                <td class="border border-black p-1 text-center font-black">
                                    {{ $b['predikat'] }}
                                </td>
                                <td class="border border-black p-1 text-center font-semibold text-[7pt]">
                                    {{ $b['keterangan'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($bulanSemester) + 6 }}" class="p-4 text-center text-slate-500">
                                    Belum ada mata pelajaran di kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-black border-t-2 border-black">
                            <td colspan="{{ count($bulanSemester) + 3 }}" class="border border-black p-1.5 text-right font-black uppercase text-[7.5pt]">
                                Rata-rata Nilai Keseluruhan :
                            </td>
                            <td class="border border-black p-1.5 text-center font-black bg-blue-100 text-black font-mono text-[8pt]">
                                {{ $item['rataRataKeseluruhanFormat'] }}
                            </td>
                            <td colspan="2" class="border border-black p-1.5 text-center text-slate-500 text-[6.5pt]">
                                Dari {{ count($item['baris']) }} Mapel
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Baris Tambahan: Rekap Kehadiran (SIA) & Catatan -->
                <div class="grid grid-cols-12 gap-3 mb-4 text-[7.5pt]">
                    <!-- Kehadiran -->
                    <div class="col-span-5 border border-black p-2 rounded-sm">
                        <div class="font-bold border-b border-black pb-1 mb-1 text-[7.5pt] uppercase">
                            Ketidakhadiran (Semester {{ ucfirst($semester) }})
                        </div>
                        <div class="space-y-0.5 text-[7pt]">
                            <div class="flex justify-between">
                                <span>1. Sakit (S)</span>
                                <span class="font-bold">: {{ $item['sakit'] }} hari</span>
                            </div>
                            <div class="flex justify-between">
                                <span>2. Izin (I)</span>
                                <span class="font-bold">: {{ $item['izin'] }} hari</span>
                            </div>
                            <div class="flex justify-between">
                                <span>3. Tanpa Keterangan (A)</span>
                                <span class="font-bold">: {{ $item['alpa'] }} hari</span>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Wali Kelas -->
                    <div class="col-span-7 border border-black p-2 rounded-sm flex flex-col justify-between">
                        <div class="font-bold border-b border-black pb-1 mb-1 text-[7.5pt] uppercase">
                            Catatan Perkembangan Peserta Didik
                        </div>
                        <p class="text-[7pt] text-slate-700 italic">
                            @if($item['rataRataKeseluruhan'] >= 85)
                                Pertahankan prestasi belajar yang sangat membanggakan ini. Teruslah rajin dan disiplin.
                            @elseif($item['rataRataKeseluruhan'] >= 75)
                                Menunjukkan pemahaman belajar yang baik. Tingkatkan terus semangat dan keaktifan belajarmu.
                            @else
                                Tingkatkan kedisiplinan dan konsistensi belajar di rumah agar capaian kompetensi makin optimal.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Tanda Tangan Resmi (Pas di Bagian Bawah F4) -->
                <div class="pt-2">
                    <div class="grid grid-cols-3 text-center text-[7.5pt] text-black">
                        <div>
                            <p>Mengetahui,</p>
                            <p class="font-bold">Orang Tua / Wali Murid</p>
                            <div class="h-12"></div>
                            <p class="font-bold underline">( ............................................ )</p>
                        </div>
                        <div>
                            <p>Mengetahui,</p>
                            <p class="font-bold">Kepala Sekolah</p>
                            <div class="h-12"></div>
                            <p class="font-bold underline">{{ $kelas->sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</p>
                            <p>NIP. {{ $kelas->sekolah->nip ?? '-' }}</p>
                        </div>
                        <div>
                            <p>{{ $kelas->sekolah->kota ?? 'Banyuwangi' }}, <span class="label-tgl-sekelas">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                            <p class="font-bold">Wali Kelas {{ $kelas->nama }}</p>
                            <div class="h-12"></div>
                            <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                            <p>NIP. {{ $kelas->nip_wali !== '-' ? $kelas->nip_wali : '....................................' }}</p>
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</div>

<script>
    function updateTanggalSekelas(val) {
        if (!val) return;
        const d = new Date(val);
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const formatTgl = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        document.querySelectorAll('.label-tgl-sekelas').forEach(el => el.textContent = formatTgl);
    }
</script>
@endsection
