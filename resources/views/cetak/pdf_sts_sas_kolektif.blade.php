<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Nilai {{ strtoupper($periodeSumatif) }} Kolektif - {{ $scopeLabel }}</title>
    <style>
        @page {
            size: {{ $printConfig['css_page_size'] }} {{ $printConfig['orientation'] }};
            margin: {{ $printConfig['css_margin'] }};
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: {{ $printConfig['layout']['body_font'] }};
            line-height: 1.25;
            color: #000;
        }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: {{ $printConfig['layout']['table_font'] }};
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: {{ $printConfig['layout']['cell_padding'] }};
        }
        table.data th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .meta { width: 100%; margin-bottom: 6px; font-size: {{ $printConfig['layout']['table_font'] }}; }
        .ttd { width: 100%; margin-top: 14px; font-size: {{ $printConfig['layout']['body_font'] }}; text-align: center; }
        .ttd td { vertical-align: top; }
        .space-ttd { height: {{ $printConfig['layout']['ttd_height'] }}; }
    </style>
</head>
<body>
    @foreach($dataPerKelas as $blok)
        @php
            $k = $blok['kelas'];
            $mapels = $blok['mapels'];
            $rows = $blok['rows'];
            $labelSumatif = $periodeSumatif === 'sts' ? 'SUMATIF TENGAH SEMESTER (STS)' : 'SUMATIF AKHIR SEMESTER (SAS)';
            $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
            if (!$ttdPath || !file_exists($ttdPath)) {
                $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
            }
        @endphp

        @include('cetak.partials.kop_pdf', ['sekolah' => $sekolah])

        <div class="text-center" style="margin-bottom: 8px;">
            <div style="font-size: 11pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                DAFTAR NILAI {{ $labelSumatif }} &bull; {{ $filterMapel ? $filterMapel->nama : 'SEMUA MATA PELAJARAN' }}
            </div>
            <div style="font-size: 8.5pt; font-weight: bold; margin-top: 2px;">
                SEMESTER {{ strtoupper($blok['semester']) }} &bull; TAHUN AJARAN {{ $k->tahunAjaran->nama_lengkap ?? $tahun }}
            </div>
        </div>

        <table class="meta">
            <tr>
                <td style="width: 50%;"><b>Kelas / Fase:</b> {{ $k->nama }} ({{ $k->fase ?? 'A' }})</td>
                <td style="width: 50%; text-align: right;"><b>Wali Kelas:</b> {{ $k->nama_wali }}</td>
            </tr>
            <tr>
                <td><b>Cakupan:</b> {{ $scopeLabel }}</td>
                <td style="text-align: right;"><b>Ukuran:</b> {{ strtoupper($printConfig['paper']) }} {{ ucfirst($printConfig['orientation']) }}</td>
            </tr>
        </table>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 10%;">NIS</th>
                    <th style="text-align: left;">Nama Peserta Didik</th>
                    @foreach($mapels as $m)
                        <th>
                            {{ $m->kode ?: \Illuminate\Support\Str::limit($m->nama, 12, '') }}<br>
                            <span style="font-weight: normal; font-size: 6.5pt;">{{ $m->nama }} (KKM {{ $k->getKkmForMapel($m) }})</span>
                        </th>
                    @endforeach
                    <th style="width: 7%;">Jml</th>
                    <th style="width: 8%;">Rata²</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="text-center">{{ $r['no'] }}</td>
                        <td class="text-center">{{ $r['siswa']->nis }}</td>
                        <td class="font-bold">{{ $r['siswa']->nama }}</td>
                        @foreach($mapels as $m)
                            @php $cell = $r['nilai_mapel'][$m->id] ?? ['format' => '–', 'tuntas' => null]; @endphp
                            <td class="text-center" style="{{ $cell['tuntas'] === false ? 'color: #b91c1c; font-weight: bold;' : '' }}">
                                {{ $cell['format'] }}
                            </td>
                        @endforeach
                        <td class="text-center font-bold">{{ $r['jumlah'] !== null ? \App\Services\HitungNilaiService::formatAngka($r['jumlah']) : '–' }}</td>
                        <td class="text-center font-bold">{{ $r['rata_rata'] !== null ? \App\Services\HitungNilaiService::formatAngka($r['rata_rata']) : '–' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 5 + $mapels->count() }}" class="text-center">Belum ada data siswa.</td>
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
                            <img src="{{ $ttdPath }}" style="max-height: 45px; max-width: 130px;" alt="TTD">
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

