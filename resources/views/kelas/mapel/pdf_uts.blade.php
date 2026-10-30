<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Nilai UTS (STS) - {{ $mapel->nama }} Kelas {{ $kelas->nama }}</title>
    <style>
        @page {
            size: 215mm 330mm; /* Standard F4 / Folio */
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            line-height: 1.3;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
        }
        .header-center {
            text-align: center;
        }
        .header-center h3 {
            margin: 0;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-center h2 {
            margin: 2px 0;
            font-size: 13pt;
            font-weight: 900;
            text-transform: uppercase;
        }
        .header-center p {
            margin: 0;
            font-size: 8.5pt;
            font-style: italic;
            color: #333;
        }
        .logo-img {
            max-height: 65px;
            max-width: 65px;
        }
        .title-block {
            text-align: center;
            margin: 12px 0 14px 0;
        }
        .title-block h1 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .title-block p {
            margin: 2px 0 0 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 15px;
        }
        .data-table th, .data-table td {
            border: 1px solid #222;
            padding: 4px 6px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .tuntas { color: #047857; font-weight: bold; }
        .belum-tuntas { color: #b91c1c; font-weight: bold; }
        .summary-box {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .sign-space {
            height: 55px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI STANDAR F4 -->
    <table class="header-table">
        <tr>
            <td style="width: 75px; text-align: left;">
                @if($sekolah && $sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ public_path('storage/' . $sekolah->logo) }}" class="logo-img" alt="Logo Pemda">
                @endif
            </td>
            <td class="header-center">
                <h3>{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                <h3>{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                <h2>{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                <p>{{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}</p>
                @if($sekolah && $sekolah->kontak)
                    <p style="font-size: 7.5pt; color: #555;">{{ $sekolah->kontak }}</p>
                @endif
            </td>
            <td style="width: 75px; text-align: right;">
                @if($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ public_path('storage/' . $sekolah->logo_kanan) }}" class="logo-img" alt="Logo Tut Wuri">
                @endif
            </td>
        </tr>
    </table>

    <!-- JUDUL DOKUMEN -->
    <div class="title-block">
        <h1>DAFTAR NILAI SUMATIF TENGAH SEMESTER (STS / UTS)</h1>
        <p>SEMESTER {{ strtoupper($semester) }} &bull; TAHUN PELAJARAN {{ $kelas->tahunAjaran->tahun ?? $tahun }}</p>
    </div>

    <!-- IDENTITAS MATA PELAJARAN & KELAS -->
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
            <td>Semester / TP</td>
            <td>:</td>
            <td>{{ ucfirst($semester) }} / {{ $kelas->tahunAjaran->tahun ?? $tahun }}</td>
            <td>KKM / KKTP</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $kkm }}</td>
        </tr>
        <tr>
            <td>Guru Pengampu</td>
            <td>:</td>
            <td>{{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? '-') }}</td>
            <td>Wali Kelas</td>
            <td>:</td>
            <td>{{ $kelas->nama_wali }}</td>
        </tr>
    </table>

    <!-- TABEL NILAI UTS PESERTA DIDIK -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NIS / NISN</th>
                <th style="width: 48%; text-align: left;">Nama Peserta Didik</th>
                <th style="width: 7%;">L/P</th>
                <th style="width: 12%;">Nilai UTS</th>
                <th style="width: 13%;">Ketuntasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($baris as $b)
                <tr>
                    <td class="text-center">{{ $b['no'] }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $b['nis'] }}</td>
                    <td>{{ $b['nama'] }}</td>
                    <td class="text-center">{{ $b['jk'] }}</td>
                    <td class="text-center font-bold" style="font-size: 9.5pt;">{{ $b['nilai_format'] }}</td>
                    <td class="text-center">
                        @if($b['keterangan'] === 'Tuntas')
                            <span class="tuntas">Tuntas</span>
                        @elseif($b['keterangan'] === 'Belum Tuntas')
                            <span class="belum-tuntas">Belum Tuntas</span>
                        @else
                            <span style="color: #999;">Belum Diisi</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 15px; color: #888;">Belum ada peserta didik di kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="summary-box">
                <td colspan="4" class="text-right" style="padding-right: 10px;">Rata-rata Nilai UTS Kelas :</td>
                <td class="text-center font-bold" style="font-size: 10pt;">{{ \App\Services\HitungNilaiService::formatAngka($rataRata) }}</td>
                <td class="text-center font-bold">{{ $persenTuntas }}% Tuntas</td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN DUA PIHAK RESMI (Kepala Sekolah & Guru Mapel) -->
    <table class="sign-table">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p class="font-bold">Kepala Sekolah</p>
                <div class="sign-space">
                    @if($sekolah && $sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                        <img src="{{ public_path('storage/' . $sekolah->ttd) }}" style="max-height: 50px; max-width: 120px;" alt="TTD Kepsek">
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
