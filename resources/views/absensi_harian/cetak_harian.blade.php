<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    @php
        $namaTipe = match ($tipe) {
            'agama' => 'Pendidikan Agama & Budi Pekerti',
            'pjok' => 'PJOK',
            default => 'Wali Kelas (Umum)',
        };
    @endphp
    <title>Presensi Harian {{ $tanggalObj->translatedFormat('d F Y') }} - Kelas {{ $kelas->nama }} ({{ $namaTipe }})</title>
    <style>
        @page {
            size: 215mm 330mm portrait; /* Format F4 / Folio Standar */
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #111;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .kop-table {
            width: 100%;
            border-bottom: 3px double #000;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-title {
            text-align: center;
            line-height: 1.2;
        }
        .kop-title h3 { margin: 0; font-size: 10pt; font-weight: bold; }
        .kop-title h2 { margin: 2px 0; font-size: 13pt; font-weight: bold; }
        .kop-title p { margin: 0; font-size: 8pt; font-style: italic; }

        .identitas-table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 9.5pt;
        }
        .identitas-table td {
            padding: 2px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 4px 6px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            font-weight: bold;
            text-align: center;
            width: 20px;
        }

        .summary-box {
            margin-top: 10px;
            padding: 6px 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 9pt;
        }

        .ttd-table {
            width: 100%;
            margin-top: 25px;
            font-size: 9.5pt;
        }
        .ttd-table td {
            vertical-align: top;
        }
        .space-ttd {
            height: 55px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; padding: 10px; background: #e0f2fe; border: 1px solid #7dd3fc; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 18px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Cetak Lembar Presensi Harian (Ctrl + P)
        </button>
    </div>

    <!-- KOP RESMI SEKOLAH -->
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ asset('storage/' . $sekolah->logo) }}" style="max-height: 60px; max-width: 60px;">
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
                    <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" style="max-height: 60px; max-width: 60px;">
                @endif
            </td>
        </tr>
    </table>

    <div class="text-center" style="margin-bottom: 12px;">
        <h3 class="font-bold uppercase" style="margin: 0; font-size: 11pt; text-decoration: underline;">
            DAFTAR PRESENSI HARIAN PESERTA DIDIK
        </h3>
        <p class="font-bold uppercase" style="margin: 3px 0 0 0; font-size: 9.5pt;">
            HARI / TANGGAL: {{ strtoupper($tanggalObj->translatedFormat('l, d F Y')) }}
        </p>
    </div>

    @php
        $guruNama = match ($tipe) {
            'agama' => $kelas->guruPengampu->firstWhere('mapel_id', $mapel?->id)?->guru?->name ?? 'Guru Pendidikan Agama',
            'pjok' => $kelas->guruPengampu->firstWhere('mapel_id', $mapel?->id)?->guru?->name ?? 'Guru PJOK',
            default => $kelas->nama_wali,
        };

        $guruNip = match ($tipe) {
            'agama' => $kelas->guruPengampu->firstWhere('mapel_id', $mapel?->id)?->guru?->nip ?? '-',
            'pjok' => $kelas->guruPengampu->firstWhere('mapel_id', $mapel?->id)?->guru?->nip ?? '-',
            default => $kelas->nip_wali,
        };
    @endphp

    <table class="identitas-table">
        <tr>
            <td style="width: 18%;">Kelas / Fase</td>
            <td style="width: 32%;">: <strong>Kelas {{ $kelas->nama }}</strong> / {{ $kelas->fase ?? 'Fase A' }}</td>
            <td style="width: 18%;">Tahun Pelajaran</td>
            <td style="width: 32%;">: {{ $kelas->tahunAjaran->tahun ?? '-' }} ({{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }})</td>
        </tr>
        <tr>
            <td>Jenis Presensi</td>
            <td>: <strong>{{ $namaTipe }}</strong></td>
            <td>Guru Pengampu</td>
            <td>: {{ $guruNama }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">NISN</th>
                <th style="width: 42%;">Nama Peserta Didik</th>
                <th style="width: 8%;">L/P</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $hCount = 0; $sCount = 0; $iCount = 0; $aCount = 0;
            @endphp
            @forelse($kelasSiswaList as $idx => $ks)
                @php
                    $rec = $absenHariIni->get($ks->id);
                    $st = $rec ? $rec->status : 'H';
                    $ket = $rec ? $rec->keterangan : '';

                    if ($st === 'H') $hCount++;
                    elseif ($st === 'S') $sCount++;
                    elseif ($st === 'I') $iCount++;
                    elseif ($st === 'A') $aCount++;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $ks->siswa->nisn ?? '-' }}</td>
                    <td class="font-bold">{{ $ks->siswa->nama }}</td>
                    <td class="text-center">{{ $ks->siswa->jenis_kelamin ?? '-' }}</td>
                    <td class="text-center font-bold">
                        <span class="status-badge">{{ $st }}</span>
                    </td>
                    <td>{{ $ket ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data siswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Ringkasan Statistik -->
    <div class="summary-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td><strong>Total Siswa:</strong> {{ $kelasSiswaList->count() }} orang</td>
                <td><strong>Hadir (H):</strong> {{ $hCount }} orang</td>
                <td><strong>Sakit (S):</strong> {{ $sCount }} orang</td>
                <td><strong>Izin (I):</strong> {{ $iCount }} orang</td>
                <td><strong>Alpa (A):</strong> {{ $aCount }} orang</td>
                <td><strong>Kehadiran:</strong> {{ $kelasSiswaList->count() > 0 ? round(($hCount / $kelasSiswaList->count()) * 100, 1) : 0 }}%</td>
            </tr>
        </table>
    </div>

    <!-- Tanda Tangan -->
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
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ $tanggalObj->translatedFormat('d F Y') }}<br>
                {{ $tipe === 'wali' ? "Wali Kelas {$kelas->nama}" : "Guru Pengampu {$namaTipe}" }}
                <div class="space-ttd"></div>
                <strong>{{ $guruNama }}</strong><br>
                NIP. {{ $guruNip }}
            </td>
        </tr>
    </table>

</body>
</html>
