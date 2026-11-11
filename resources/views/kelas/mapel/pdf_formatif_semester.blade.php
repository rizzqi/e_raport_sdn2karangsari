<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Formatif Semester - {{ $mapel->nama }} Kelas {{ $kelas->nama }}</title>
    <style>
        @page {
            size: {{ $printConfig['css_page_size'] ?? '210mm 297mm' }};
            margin: {{ $printConfig['css_margin'] ?? '10mm 12mm 10mm 12mm' }};
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            color: #111;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-table td { vertical-align: middle; }
        .header-center { text-align: center; }
        .header-center h3 { margin: 0; font-size: 9.5pt; font-weight: bold; text-transform: uppercase; }
        .header-center h2 { margin: 2px 0; font-size: 12.5pt; font-weight: 900; text-transform: uppercase; }
        .header-center p { margin: 0; font-size: 8pt; font-style: italic; color: #333; }
        .logo-img { max-height: 60px; max-width: 60px; }
        .title-block { text-align: center; margin: 10px 0 12px 0; }
        .title-block h1 { margin: 0; font-size: 11pt; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
        .title-block p { margin: 2px 0 0 0; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; color: #333; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8.5pt; }
        .meta-table td { padding: 2px 0; vertical-align: top; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 14px; }
        .data-table th, .data-table td { border: 1px solid #222; padding: 3.5px 4px; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; text-align: center; text-transform: uppercase; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .tuntas { color: #047857; font-weight: bold; }
        .belum-tuntas { color: #b91c1c; font-weight: bold; }
        .summary-box { background-color: #f9f9f9; font-weight: bold; }
        .sign-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 9pt; page-break-inside: avoid; }
        .sign-table td { width: 50%; vertical-align: top; text-align: center; }
        .sign-space { height: 50px; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 70px; text-align: left;">
                @if($sekolah && $sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ public_path('storage/' . $sekolah->logo) }}" class="logo-img" alt="Logo Pemda">
                @endif
            </td>
            <td class="header-center">
                <h3>{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                <h3>{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                <h2>{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                <p>{{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}</p>
            </td>
            <td style="width: 70px; text-align: right;">
                @if($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ public_path('storage/' . $sekolah->logo_kanan) }}" class="logo-img" alt="Logo Tut Wuri">
                @endif
            </td>
        </tr>
    </table>

    <div class="title-block">
        <h1>REKAPITULASI NILAI FORMATIF SATU SEMESTER</h1>
        <p>SEMESTER {{ strtoupper($semester) }} &bull; TAHUN PELAJARAN {{ $kelas->tahunAjaran->tahun ?? $tahun }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 18%;">Mata Pelajaran</td>
            <td style="width: 2%;">:</td>
            <td style="width: 35%; font-weight: bold;">{{ $mapel->nama }}</td>
            <td style="width: 18%;">Kelas / Fase</td>
            <td style="width: 2%;">:</td>
            <td style="width: 25%; font-weight: bold;">{{ $kelas->nama }} / {{ $kelas->fase ?? 'A' }}</td>
        </tr>
        <tr>
            <td>Guru Pengampu</td>
            <td>:</td>
            <td>{{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? '-') }}</td>
            <td>KKM / KKTP</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $kkm }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="{{ $isPjok ? 2 : 1 }}" style="width: 4%;">No</th>
                <th rowspan="{{ $isPjok ? 2 : 1 }}" style="width: 11%;">NIS</th>
                <th rowspan="{{ $isPjok ? 2 : 1 }}" style="text-align: left;">Nama Peserta Didik</th>
                @foreach($bulanSemester as $bNama)
                    <th colspan="{{ $isPjok ? 2 : 1 }}">{{ substr($bNama, 0, 3) }}</th>
                @endforeach
                @if($isAgama)
                    <th>Prak 1</th>
                    <th>Prak 2</th>
                @endif
                <th rowspan="{{ $isPjok ? 2 : 1 }}" style="width: 9%; background-color: #ecfdf5;">Rata Fmt</th>
                <th rowspan="{{ $isPjok ? 2 : 1 }}" style="width: 11%;">Ketuntasan</th>
            </tr>
            @if($isPjok)
                <tr>
                    @foreach($bulanSemester as $bNama)
                        <th style="font-size: 7pt;">F</th>
                        <th style="font-size: 7pt;">P</th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody>
            @forelse($baris as $b)
                <tr>
                    <td class="text-center">{{ $b['no'] }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $b['nis'] }}</td>
                    <td>{{ $b['nama'] }}</td>
                    @foreach($bulanSemester as $bNum => $bNama)
                        <td class="text-center">{{ $b['bulan_nilai'][$bNum] !== null ? \App\Services\HitungNilaiService::formatAngka($b['bulan_nilai'][$bNum]) : '–' }}</td>
                        @if($isPjok)
                            <td class="text-center">{{ $b['bulan_praktik'][$bNum] !== null ? \App\Services\HitungNilaiService::formatAngka($b['bulan_praktik'][$bNum]) : '–' }}</td>
                        @endif
                    @endforeach
                    @if($isAgama)
                        <td class="text-center">{{ $b['praktik_1'] !== null ? \App\Services\HitungNilaiService::formatAngka($b['praktik_1']) : '–' }}</td>
                        <td class="text-center">{{ $b['praktik_2'] !== null ? \App\Services\HitungNilaiService::formatAngka($b['praktik_2']) : '–' }}</td>
                    @endif
                    <td class="text-center font-bold" style="background-color: #f0fdf4;">{{ $b['rata_format'] }}</td>
                    <td class="text-center">
                        @if($b['keterangan'] === 'Tuntas')
                            <span class="tuntas">Tuntas</span>
                        @elseif($b['keterangan'] === 'Belum Tuntas')
                            <span class="belum-tuntas">Belum</span>
                        @else
                            <span style="color: #888;">Belum Diisi</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 12px; color: #888;">Belum ada peserta didik di kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            @php
                $colSpanRata = 3 + count($bulanSemester) * ($isPjok ? 2 : 1) + ($isAgama ? 2 : 0);
            @endphp
            <tr class="summary-box">
                <td colspan="{{ $colSpanRata }}" class="text-right" style="padding-right: 8px;">Rata-rata Formatif Kelas :</td>
                <td class="text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($rataKelas) }}</td>
                <td class="text-center font-bold">{{ $persenTuntas }}% Tuntas</td>
            </tr>
        </tfoot>
    </table>

    <table class="sign-table">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p class="font-bold">Kepala Sekolah</p>
                <div class="sign-space">
                    @if($sekolah && $sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                        <img src="{{ public_path('storage/' . $sekolah->ttd) }}" style="max-height: 45px; max-width: 110px;" alt="TTD Kepsek">
                    @endif
                </div>
                <p class="font-bold" style="text-decoration: underline;">{{ $sekolah->kepsek ?? '( ........................................ )' }}</p>
                <p style="font-size: 8pt; color: #333;">NIP. {{ $sekolah->nip ?? '-' }}</p>
            </td>
            <td>
                <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, {{ $tanggalCetak->translatedFormat('d F Y') }}</p>
                <p class="font-bold">Guru Mata Pelajaran</p>
                <div class="sign-space"></div>
                <p class="font-bold" style="text-decoration: underline;">
                    {{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? '( ........................................ )') }}
                </p>
                <p style="font-size: 8pt; color: #333;">
                    NIP. {{ ($guruPengampu && $guruPengampu->nip) ? $guruPengampu->nip : ($kelas->nip_wali ?? '-') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>

