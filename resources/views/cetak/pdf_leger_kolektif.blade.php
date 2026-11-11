<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leger Nilai Rapor {{ strtoupper($periode) }} - {{ $scopeLabel }}</title>
    <style>
        @page {
            size: {{ $printConfig['css_page_size'] }} {{ $printConfig['orientation'] }};
            margin: {{ $printConfig['css_margin'] }};
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $printConfig['layout']['body_font'] }};
            line-height: 1.2;
            color: #000;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: {{ $printConfig['layout']['small_font'] }};
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 2.5px 3px;
        }
        table.data th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .meta { width: 100%; margin-bottom: 6px; font-size: {{ $printConfig['layout']['table_font'] }}; }
        .ttd { width: 100%; margin-top: 12px; font-size: {{ $printConfig['layout']['body_font'] }}; text-align: center; }
        .ttd td { vertical-align: top; }
        .space-ttd { height: {{ $printConfig['layout']['ttd_height'] }}; }
    </style>
</head>
<body>
    @foreach($dataPerKelas as $blok)
        @php
            $k = $blok['kelas'];
            $rekap = $blok['rekap'];
            $mapels = $rekap['mapels'] ?? collect();
            $siswaList = $rekap['siswa'] ?? [];
            $kehadiranList = $blok['kehadiranList'] ?? collect();
            $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
            if (!$ttdPath || !file_exists($ttdPath)) {
                $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
            }
        @endphp

        @include('cetak.partials.kop_pdf', ['sekolah' => $sekolah])

        <div class="text-center" style="margin-bottom: 8px;">
            <div style="font-size: 11pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                BUKU LEGER NILAI RAPOR {{ strtoupper($periode) }} &bull; {{ $filterMapel ? $filterMapel->nama : 'SEMUA MATA PELAJARAN' }}
            </div>
            <div style="font-size: 8.5pt; font-weight: bold; margin-top: 2px;">
                KELAS {{ strtoupper($k->nama) }} &bull; SEMESTER {{ strtoupper($k->tahunAjaran->semester ?? $semester) }} &bull; {{ $k->tahunAjaran->nama_lengkap ?? $tahun }}
            </div>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">No</th>
                    <th rowspan="2" style="width: 8%;">NIS</th>
                    <th rowspan="2" style="text-align: left;">Nama Peserta Didik</th>
                    @foreach($mapels as $m)
                        <th colspan="2">{{ $m->kode ?: \Illuminate\Support\Str::limit($m->nama, 10, '') }}</th>
                    @endforeach
                    <th rowspan="2" style="width: 5%;">Jml</th>
                    <th rowspan="2" style="width: 5%;">Rata²</th>
                    <th rowspan="2" style="width: 4%;">Rank</th>
                    <th colspan="3">SIA</th>
                </tr>
                <tr>
                    @foreach($mapels as $m)
                        <th style="font-size: 6pt;">N</th>
                        <th style="font-size: 6pt;">P</th>
                    @endforeach
                    <th style="font-size: 6pt;">S</th>
                    <th style="font-size: 6pt;">I</th>
                    <th style="font-size: 6pt;">A</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswaList as $idx => $sItem)
                    @php
                        $ks = $sItem['kelas_siswa'];
                        $barisMap = collect($sItem['hasil']['baris'] ?? [])->keyBy('mapel_id');
                        $keh = $kehadiranList->get($ks->id);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $sItem['siswa']->nis }}</td>
                        <td class="font-bold">{{ $sItem['siswa']->nama }}</td>
                        @foreach($mapels as $m)
                            @php $b = $barisMap->get($m->id); @endphp
                            <td class="text-center">{{ $b['nilai_rapor_format'] ?? '–' }}</td>
                            <td class="text-center font-bold">{{ $b['predikat'] ?? '–' }}</td>
                        @endforeach
                        <td class="text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($sItem['hasil']['total_akhir'] ?? 0) }}</td>
                        <td class="text-center font-bold">{{ $sItem['hasil']['rata_akhir_format'] ?? '–' }}</td>
                        <td class="text-center font-bold">{{ $sItem['ranking'] ?? '-' }}</td>
                        <td class="text-center">{{ $keh ? $keh->sakit : 0 }}</td>
                        <td class="text-center">{{ $keh ? $keh->izin : 0 }}</td>
                        <td class="text-center">{{ $keh ? $keh->alpa : 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 9 + ($mapels->count() * 2) }}" class="text-center">Belum ada data siswa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="ttd">
            <tr>
                <td style="width: 50%;">
                    Mengetahui,<br>Kepala Sekolah
                    <div class="space-ttd">
                        @if($ttdPath && file_exists($ttdPath))
                            <img src="{{ $ttdPath }}" style="max-height: 42px; max-width: 130px;" alt="TTD">
                        @endif
                    </div>
                    <b>{{ $sekolah->kepsek ?? '( ................................ )' }}</b><br>
                    <span>NIP. {{ $sekolah->nip ?? '-' }}</span>
                </td>
                <td style="width: 50%;">
                    {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ $tanggalObj->translatedFormat('d F Y') }}<br>
                    Wali Kelas {{ $k->nama }}
                    <div class="space-ttd"></div>
                    <b>{{ $k->nama_wali }}</b><br>
                    <span>{{ $k->nip_wali !== '-' ? 'NIP. ' . $k->nip_wali : 'NIP. -' }}</span>
                </td>
            </tr>
        </table>

        @if(!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>
</html>

