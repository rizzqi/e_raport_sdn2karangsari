<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absensi Bulanan {{ $namaBulan }} {{ $tahun }} - {{ $kelas->nama }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .kop-table {
            width: 100%;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-title {
            text-align: center;
            line-height: 1.25;
        }
        .kop-title h3 { margin: 0; font-size: 10pt; font-weight: bold; }
        .kop-title h2 { margin: 2px 0; font-size: 14pt; font-weight: bold; }
        .kop-title p { margin: 0; font-size: 8.5pt; font-style: italic; }

        .identitas-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .identitas-table td {
            padding: 2px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 5px 6px;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .ttd-table {
            width: 100%;
            margin-top: 30px;
            font-size: 10pt;
        }
        .ttd-table td {
            vertical-align: top;
        }
        .space-ttd {
            height: 60px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; padding: 10px; background: #e0f2fe; border: 1px solid #7dd3fc; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Cetak Dokumen Ini (Ctrl + P)
        </button>
    </div>

    <!-- KOP -->
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ asset('storage/' . $sekolah->logo) }}" style="max-height: 65px; max-width: 65px;">
                @endif
            </td>
            <td style="width: 70%;" class="kop-title">
                <h3>{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                <h3>{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                <h2>{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                <p>{{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}</p>
            </td>
            <td style="width: 15%; text-align: right;">
                @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" style="max-height: 65px; max-width: 65px;">
                @endif
            </td>
        </tr>
    </table>

    <div class="text-center" style="margin-bottom: 12px;">
        <h3 class="font-bold uppercase" style="margin: 0; font-size: 12pt; text-decoration: underline;">
            REKAPITULASI PRESENSI / ABSENSI PESERTA DIDIK
        </h3>
        <p class="font-bold uppercase" style="margin: 3px 0 0 0; font-size: 10pt;">
            BULAN {{ strtoupper($namaBulan) }} TAHUN {{ $tahun }}
        </p>
    </div>

    <table class="identitas-table">
        <tr>
            <td style="width: 18%;">Kelas / Rombel</td>
            <td style="width: 32%;">: <strong>{{ $kelas->nama }}</strong></td>
            <td style="width: 18%;">Tahun Ajaran</td>
            <td style="width: 32%;">: {{ $kelas->tahunAjaran->tahun ?? '-' }} ({{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }})</td>
        </tr>
        <tr>
            <td>Wali Kelas</td>
            <td>: {{ $kelas->wali_nama ?? ($kelas->wali->name ?? '-') }}</td>
            <td>NIP Wali Kelas</td>
            <td>: {{ $kelas->wali_nip ?? '-' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Nama Peserta Didik</th>
                <th style="width: 15%;">NIS</th>
                <th style="width: 10%;">Sakit (S)</th>
                <th style="width: 10%;">Izin (I)</th>
                <th style="width: 10%;">Alpa (A)</th>
                <th style="width: 10%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totS = 0; $totI = 0; $totA = 0;
            @endphp
            @foreach($kelasSiswaList as $idx => $ks)
                @php
                    $rec = $kehadiranBulanIni->get($ks->id);
                    $s = $rec ? $rec->sakit : 0;
                    $i = $rec ? $rec->izin : 0;
                    $a = $rec ? $rec->alpa : 0;
                    $tot = $s + $i + $a;
                    $totS += $s; $totI += $i; $totA += $a;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td>{{ $ks->siswa->nama }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $ks->siswa->nis }}</td>
                    <td class="text-center">{{ $s > 0 ? $s : '-' }}</td>
                    <td class="text-center">{{ $i > 0 ? $i : '-' }}</td>
                    <td class="text-center">{{ $a > 0 ? $a : '-' }}</td>
                    <td class="text-center font-bold">{{ $tot > 0 ? $tot : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #fafafa; font-weight: bold;">
                <td colspan="3" class="text-right uppercase">Jumlah Total Hari</td>
                <td class="text-center">{{ $totS }}</td>
                <td class="text-center">{{ $totI }}</td>
                <td class="text-center">{{ $totA }}</td>
                <td class="text-center">{{ $totS + $totI + $totA }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="ttd-table">
        <tr>
            <td style="width: 50%;">
                Mengetahui,<br>
                Kepala Sekolah
                <div class="space-ttd"></div>
                <strong>{{ $sekolah->kepala_sekolah ?? '....................................' }}</strong><br>
                NIP. {{ $sekolah->nip_kepala_sekolah ?? '........................' }}
            </td>
            <td style="width: 50%; text-align: right;">
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ (isset($tanggalObj) ? $tanggalObj : now()->locale('id'))->translatedFormat('d F Y') }}<br>
                Wali Kelas {{ $kelas->nama }}
                <div class="space-ttd"></div>
                <strong>{{ $kelas->wali_nama ?? ($kelas->wali->name ?? '....................................') }}</strong><br>
                NIP. {{ $kelas->wali_nip ?? '........................' }}
            </td>
        </tr>
    </table>

</body>
</html>
