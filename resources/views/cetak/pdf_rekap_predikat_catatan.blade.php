<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Predikat &amp; Catatan Guru {{ strtoupper($periode) }} - {{ $scopeLabel }}</title>
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
            font-size: {{ $printConfig['layout']['small_font'] }};
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }
        table.data th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .ttd { width: 100%; margin-top: 12px; font-size: {{ $printConfig['layout']['body_font'] }}; text-align: center; }
        .ttd td { vertical-align: top; }
        .space-ttd { height: {{ $printConfig['layout']['ttd_height'] }}; }
    </style>
</head>
<body>
    @foreach($dataPerKelas as $blok)
        @php
            $k = $blok['kelas'];
            $siswaItems = $blok['siswaItems'];
            $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
            if (!$ttdPath || !file_exists($ttdPath)) {
                $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
            }
        @endphp

        @include('cetak.partials.kop_pdf', ['sekolah' => $sekolah])

        <div class="text-center" style="margin-bottom: 8px;">
            <div style="font-size: 11pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                REKAP PREDIKAT &amp; CATATAN GURU ({{ $filterMapel ? $filterMapel->nama : 'SEMUA MATA PELAJARAN' }})
            </div>
            <div style="font-size: 8.5pt; font-weight: bold; margin-top: 2px;">
                KELAS {{ strtoupper($k->nama) }} &bull; SEMESTER {{ strtoupper($k->tahunAjaran->semester ?? $semester) }} &bull; {{ $k->tahunAjaran->nama_lengkap ?? $tahun }}
            </div>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 18%; text-align: left;">Nama Peserta Didik</th>
                    <th style="width: 20%; text-align: left;">Mata Pelajaran</th>
                    <th style="width: 8%;">Nilai</th>
                    <th style="width: 8%;">Predikat</th>
                    <th style="width: 42%; text-align: left;">Catatan Guru &amp; Capaian Kompetensi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($siswaItems as $sIdx => $sItem)
                    @php
                        $baris = $sItem['hasilNilai']['baris'] ?? [];
                        $rowCount = max(1, count($baris));
                    @endphp
                    @forelse($baris as $bIdx => $b)
                        <tr>
                            @if($bIdx === 0)
                                <td class="text-center" rowspan="{{ $rowCount }}">{{ $sIdx + 1 }}</td>
                                <td class="font-bold" rowspan="{{ $rowCount }}">
                                    {{ $sItem['siswa']->nama }}<br>
                                    <span style="font-weight: normal; font-size: 6.5pt; color: #555;">NIS: {{ $sItem['siswa']->nis }}</span>
                                </td>
                            @endif
                            <td class="font-bold">{{ $b['mapel_nama'] }}</td>
                            <td class="text-center font-bold">{{ $b['nilai_rapor_format'] }}</td>
                            <td class="text-center font-bold">{{ $b['predikat'] }}</td>
                            <td style="text-align: justify;">
                                @if(!empty($b['catatan_guru']))
                                    <div><b>Catatan Guru:</b> {{ $b['catatan_guru'] }}</div>
                                    @if(!empty($b['auto_cp']) && $b['auto_cp'] !== 'Belum ada penilaian capaian pembelajaran')
                                        <div style="color: #444; margin-top: 1px;">{{ $b['auto_cp'] }}</div>
                                    @endif
                                @else
                                    {{ $b['capaian_kompetensi'] }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center">{{ $sIdx + 1 }}</td>
                            <td class="font-bold">{{ $sItem['siswa']->nama }}</td>
                            <td colspan="4" class="text-center">Belum ada mata pelajaran.</td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada data siswa.</td>
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

