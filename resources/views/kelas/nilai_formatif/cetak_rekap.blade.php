@extends('layouts.app')

@section('title', 'Cetak Rekap Nilai Bulanan - ' . $kelas->nama . ' (' . $namaBulan . ' ' . $tahun . ')')

@section('content')
<style>
    @media print {
        @page {
            size: 330mm 215mm landscape;
            margin: 6mm 8mm;
        }
        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background: white !important;
            color: #000 !important;
            font-family: Arial, Helvetica, sans-serif !important;
            font-size: 7.5pt !important;
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
        .print-box {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 7pt !important;
            table-layout: fixed !important;
        }
        th, td {
            border: 0.5pt solid #000 !important;
            padding: 2px 2px !important;
            color: #000 !important;
            word-break: break-word !important;
            line-height: 1.15 !important;
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
        tr {
            page-break-inside: avoid !important;
        }
    }
</style>

<div class="space-y-4">

    <!-- Action Toolbar (No-Print) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.show', $kelas->id) }}"
               class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Kelas
            </a>

            <!-- Ganti Bulan Cepat -->
            <form action="{{ route('kelas.nilai-formatif.cetak-rekap', $kelas->id) }}" method="GET" class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <i class="fa-regular fa-calendar-check text-slate-500 text-xs"></i>
                <label for="selectBulan" class="text-xs font-bold text-slate-600">Bulan:</label>
                <select name="bulan" id="selectBulan" onchange="this.form.submit()" class="bg-white border border-slate-300 text-xs font-semibold rounded-lg px-2 py-0.5 focus:outline-none">
                    @foreach($daftarBulan as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>{{ $bNama }}</option>
                    @endforeach
                </select>
            </form>

            <span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-bold">
                <i class="fa-solid fa-calculator mr-1.5 text-emerald-600"></i> Rata-rata Otomatis: {{ $rataRataKelas }}
            </span>
            <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-users mr-1 text-blue-600"></i> {{ count($rekapSiswa) }} Siswa
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Pilihan Cetak Rapor Bulanan Per Siswa -->
            <a href="{{ route('kelas.nilai-formatif.cetak-sekelas', ['kelas' => $kelas->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl text-xs border border-indigo-200 transition flex items-center">
                <i class="fa-solid fa-file-invoice mr-1 text-indigo-600"></i> Cetak Rapor Per Siswa (F4)
            </a>

            <!-- Ganti Tanggal Cetak -->
            <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-xs">
                <i class="fa-regular fa-calendar-days text-slate-500"></i>
                <label for="inputTanggalCetakBulanan" class="font-bold text-slate-700 whitespace-nowrap">Tgl Cetak:</label>
                <input type="date" id="inputTanggalCetakBulanan" value="{{ $tanggalObj->format('Y-m-d') }}"
                       onchange="updateTanggalCetak(this.value)"
                       class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Tombol Cetak Dokumen -->
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Rekap Bulanan (Ctrl+P)
            </button>
        </div>
    </div>

    <!-- Lembar Dokumen Rekap Resmi (A4 / F4 Landscape Siap Cetak) -->
    <div class="bg-white p-6 md:p-8 rounded-2xl border border-slate-200/80 shadow-sm print-box">

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

        <!-- Judul Dokumen Rekap Bulanan -->
        <div class="text-center mb-3">
            <h2 class="text-sm font-black uppercase tracking-wider text-black underline">
                REKAPITULASI NILAI BULANAN (FORMATIF)
            </h2>
            <div class="flex justify-between items-center text-[8pt] text-black font-semibold mt-1.5 px-1">
                <div>
                    <span>Kelas: <strong>{{ $kelas->nama }}</strong> ({{ $kelas->fase ?? 'Fase A' }})</span> &bull;
                    <span>Bulan: <strong>{{ strtoupper($namaBulan) }} {{ $tahun }}</strong></span>
                </div>
                <div>
                    <span>Tahun Ajaran: <strong>{{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</strong></span> &bull;
                    <span>Wali Kelas: <strong>{{ $kelas->nama_wali }}</strong></span>
                </div>
            </div>
        </div>

        <!-- Tabel Leger Bulanan Lengkap -->
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse border border-black text-[7pt]">
                <thead>
                    <tr class="bg-slate-100 text-black">
                        <th class="border border-black p-1 text-center w-6" rowspan="2">No</th>
                        <th class="border border-black p-1 text-center w-14" rowspan="2">NIS</th>
                        <th class="border border-black p-1 text-left min-w-[140px]" rowspan="2">Nama Peserta Didik</th>
                        @foreach($mapels as $m)
                            <th class="border border-black p-1 text-center min-w-[38px]" title="{{ $m->nama }}">
                                <span class="font-bold">{{ $m->nama_singkat }}</span>
                            </th>
                        @endforeach
                        <th class="border border-black p-1 text-center w-10 font-black bg-slate-200/70" rowspan="2">Total</th>
                        <th class="border border-black p-1 text-center w-10 font-black bg-blue-100/70 text-black" rowspan="2">Rata²</th>
                        <th class="border border-black p-1 text-center w-8 font-black bg-amber-100/70 text-black" rowspan="2">Rank</th>
                        <th class="border border-black p-1 text-center" colspan="3">Absensi</th>
                    </tr>
                    <tr class="bg-slate-50 text-[6.5pt] text-black">
                        @foreach($mapels as $m)
                            <th class="border border-black p-0.5 text-center text-slate-700">
                                {{ $kelas->getKkmForMapel($m) }}
                            </th>
                        @endforeach
                        <th class="border border-black p-0.5 text-center w-5">S</th>
                        <th class="border border-black p-0.5 text-center w-5">I</th>
                        <th class="border border-black p-0.5 text-center w-5">A</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapSiswa as $idx => $item)
                        @php
                            $s = $item['siswa'];
                            $rank = $item['peringkat'] ?? ($idx + 1);
                        @endphp
                        <tr class="text-black {{ $idx % 2 == 1 ? 'bg-slate-50/50' : '' }}">
                            <td class="border border-black p-1 text-center font-bold">{{ $idx + 1 }}</td>
                            <td class="border border-black p-1 text-center font-mono">{{ $s->nis }}</td>
                            <td class="border border-black p-1 font-semibold whitespace-nowrap text-left px-1.5">
                                {{ $s->nama }}
                            </td>
                            @foreach($mapels as $m)
                                @php
                                    $val = $item['nilaiMapels'][$m->id] ?? null;
                                    $kkm = $kelas->getKkmForMapel($m);
                                @endphp
                                <td class="border border-black p-1 text-center font-semibold {{ ($val !== null && $val < $kkm) ? 'text-rose-600 font-bold' : '' }}">
                                    {{ $val !== null ? \App\Services\HitungNilaiService::formatAngka($val) : '-' }}
                                </td>
                            @endforeach
                            <td class="border border-black p-1 text-center font-black bg-slate-100/70">
                                {{ \App\Services\HitungNilaiService::formatAngka($item['totalNilai']) }}
                            </td>
                            <td class="border border-black p-1 text-center font-black bg-blue-50/70">
                                {{ $item['rataRataFormat'] }}
                            </td>
                            <td class="border border-black p-1 text-center font-black bg-amber-50/70">
                                {{ $rank }}
                            </td>
                            <td class="border border-black p-1 text-center text-[6.5pt]">{{ $item['sakit'] }}</td>
                            <td class="border border-black p-1 text-center text-[6.5pt]">{{ $item['izin'] }}</td>
                            <td class="border border-black p-1 text-center text-[6.5pt]">{{ $item['alpa'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $mapels->count() + 7 }}" class="p-4 text-center text-slate-500">
                                Belum ada data siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold text-black border-t-2 border-black">
                        <td colspan="3" class="border border-black p-1 text-right font-black uppercase">
                            Rata-rata Kelas :
                        </td>
                        @foreach($mapels as $m)
                            <td class="border border-black p-1 text-center font-black text-black">
                                {{ $rataMapelKelas[$m->id] ?? '-' }}
                            </td>
                        @endforeach
                        <td class="border border-black p-1 text-center font-black bg-slate-200/50">-</td>
                        <td class="border border-black p-1 text-center font-black bg-blue-100/70 text-black">
                            {{ $rataRataKelas }}
                        </td>
                        <td class="border border-black p-1 text-center font-black bg-amber-100/50">-</td>
                        <td colspan="3" class="border border-black p-1 text-center font-black text-[6.5pt]">
                            SIA
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Kolom Tanda Tangan Pas 1 Lembar Cetak -->
        <div class="mt-4 pt-2">
            <div class="flex justify-between items-start text-[8pt] text-black">
                <div class="text-center w-56">
                    <p>Mengetahui,</p>
                    <p class="font-bold">Kepala Sekolah</p>
                    <div class="h-12"></div>
                    <p class="font-bold underline">{{ $kelas->sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</p>
                    <p>NIP. {{ $kelas->sekolah->nip ?? '-' }}</p>
                </div>
                <div class="text-center w-56">
                    <p>{{ $kelas->sekolah->kota ?? 'Banyuwangi' }}, <span id="labelTglCetakBulanan">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                    <p class="font-bold">Wali Kelas {{ $kelas->nama }}</p>
                    <div class="h-12"></div>
                    <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                    <p>NIP. {{ $kelas->nip_wali !== '-' ? $kelas->nip_wali : '....................................' }}</p>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    function updateTanggalCetak(val) {
        if (!val) return;
        const d = new Date(val);
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const formatTgl = d.getDate() + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
        const lbl = document.getElementById('labelTglCetakBulanan');
        if (lbl) lbl.textContent = formatTgl;
    }
</script>
@endsection
