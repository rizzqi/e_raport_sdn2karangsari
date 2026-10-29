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
        $namaBulan = \App\Models\KehadiranBulanan::NAMA_BULAN[$bulan] ?? "Bulan {$bulan}";
    @endphp
    <title>Leger Presensi Bulanan {{ $namaBulan }} {{ $tahun }} - Kelas {{ $kelas->nama }} ({{ $namaTipe }})</title>
    <style>
        @page {
            size: 330mm 215mm landscape; /* Format F4 / Folio Landscape */
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #111;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .header-box {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .header-box h2 {
            margin: 0;
            font-size: 11pt;
            font-weight: bold;
        }
        .header-box h3 {
            margin: 1px 0 0 0;
            font-size: 9.5pt;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .meta-table td {
            padding: 1px 0;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .matrix-table th, .matrix-table td {
            border: 1px solid #333;
            padding: 2px 2px;
        }
        .matrix-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .is-sunday {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
        }

        .ttd-table {
            width: 100%;
            margin-top: 15px;
            font-size: 8.5pt;
        }
        .ttd-table td {
            vertical-align: top;
        }
        .space-ttd {
            height: 45px;
        }

        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 10px; padding: 8px; background: #e0f2fe; border: 1px solid #7dd3fc; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 16px; background: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Cetak Leger Rekap Bulanan (F4 Landscape - Ctrl + P)
        </button>
    </div>

    <!-- Header Dokumen -->
    <div class="header-box">
        <h2>{{ strtoupper($sekolah->nama ?? 'SD NEGERI 2 KARANGSARI') }}</h2>
        <h3>LEGER REKAPITULASI PRESENSI HARIAN PESERTA DIDIK</h3>
        <div style="font-size: 8.5pt; font-weight: bold; margin-top: 2px;">
            BULAN {{ strtoupper($namaBulan) }} {{ $tahun }} — JENIS: {{ strtoupper($namaTipe) }}
        </div>
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

    <table class="meta-table">
        <tr>
            <td style="width: 10%;"><strong>Kelas / Fase</strong></td>
            <td style="width: 25%;">: Kelas {{ $kelas->nama }} ({{ $kelas->fase ?? 'Fase A' }})</td>
            <td style="width: 12%;"><strong>Tahun Pelajaran</strong></td>
            <td style="width: 25%;">: {{ $kelas->tahunAjaran->tahun ?? '-' }} ({{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }})</td>
            <td style="width: 10%;"><strong>Pengampu</strong></td>
            <td style="width: 18%;">: {{ $guruNama }}</td>
        </tr>
    </table>

    <!-- Matrix Grid Tanggal 1 s.d. $jumlahHariBulan -->
    <table class="matrix-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 20px;">No</th>
                <th rowspan="2" style="width: 65px;">NISN</th>
                <th rowspan="2" style="text-align: left; padding-left: 4px; min-width: 140px;">Nama Peserta Didik</th>
                <th rowspan="2" style="width: 20px;">L/P</th>
                <th colspan="{{ $jumlahHariBulan }}">Tanggal Presensi Bulan {{ $namaBulan }}</th>
                <th colspan="4">Rekapitulasi</th>
                <th rowspan="2" style="width: 32px;">% H</th>
            </tr>
            <tr>
                @for($d = 1; $d <= $jumlahHariBulan; $d++)
                    @php
                        $colDate = \Carbon\Carbon::createFromDate($tahun, $bulan, $d);
                        $isSun = $colDate->isSunday();
                    @endphp
                    <th style="width: 18px;" class="{{ $isSun ? 'is-sunday' : '' }}">{{ $d }}</th>
                @endfor
                <th style="width: 20px; background: #dcfce7;">H</th>
                <th style="width: 20px; background: #fef3c7;">S</th>
                <th style="width: 20px; background: #dbeafe;">I</th>
                <th style="width: 20px; background: #fee2e2;">A</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totHarian = array_fill(1, $jumlahHariBulan, ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0]);
                $grandH = 0; $grandS = 0; $grandI = 0; $grandA = 0;
            @endphp
            @forelse($kelasSiswaList as $idx => $ks)
                @php
                    $byDay = $matriksBulan[$ks->id] ?? [];
                    $rekap = $rekapTotalPerSiswa[$ks->id] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
                    $h = $rekap['H']; $s = $rekap['S']; $i = $rekap['I']; $a = $rekap['A'];
                    $grandH += $h; $grandS += $s; $grandI += $i; $grandA += $a;
                    $totalTercatat = $h + $s + $i + $a;
                    $persenH = $totalTercatat > 0 ? round(($h / $totalTercatat) * 100) : 100;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $ks->siswa->nisn ?? '-' }}</td>
                    <td style="text-align: left; padding-left: 4px; font-weight: bold; white-space: nowrap;">
                        {{ $ks->siswa->nama }}
                    </td>
                    <td class="text-center">{{ $ks->siswa->jenis_kelamin ?? '-' }}</td>

                    @for($d = 1; $d <= $jumlahHariBulan; $d++)
                        @php
                            $colDate = \Carbon\Carbon::createFromDate($tahun, $bulan, $d);
                            $isSun = $colDate->isSunday();
                            $status = $byDay[$d] ?? null;

                            if ($status && isset($totHarian[$d][$status])) {
                                $totHarian[$d][$status]++;
                            }
                        @endphp
                        <td class="text-center {{ $isSun ? 'is-sunday' : '' }}" style="{{ $status === 'A' ? 'color: red; font-weight: bold;' : ($status === 'S' ? 'color: #b45309; font-weight: bold;' : ($status === 'I' ? 'color: #1d4ed8; font-weight: bold;' : '')) }}">
                            {{ $status ?? ($isSun ? '•' : '-') }}
                        </td>
                    @endfor

                    <td class="text-center font-bold" style="background: #f0fdf4;">{{ $h }}</td>
                    <td class="text-center font-bold" style="background: #fffbeb;">{{ $s > 0 ? $s : '-' }}</td>
                    <td class="text-center font-bold" style="background: #eff6ff;">{{ $i > 0 ? $i : '-' }}</td>
                    <td class="text-center font-bold" style="background: #fef2f2; {{ $a > 0 ? 'color: red;' : '' }}">{{ $a > 0 ? $a : '-' }}</td>
                    <td class="text-center font-bold">{{ $persenH }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $jumlahHariBulan + 9 }}" class="text-center">Tidak ada data peserta didik.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background: #f9f9f9; font-weight: bold;">
                <td colspan="4" class="text-right" style="padding-right: 4px;">JUMLAH HADIR (H)</td>
                @for($d = 1; $d <= $jumlahHariBulan; $d++)
                    @php
                        $colDate = \Carbon\Carbon::createFromDate($tahun, $bulan, $d);
                        $isSun = $colDate->isSunday();
                        $cntH = $totHarian[$d]['H'];
                    @endphp
                    <td class="text-center {{ $isSun ? 'is-sunday' : '' }}">{{ $cntH > 0 ? $cntH : '-' }}</td>
                @endfor
                <td class="text-center" style="background: #dcfce7;">{{ $grandH }}</td>
                <td class="text-center" style="background: #fef3c7;">{{ $grandS }}</td>
                <td class="text-center" style="background: #dbeafe;">{{ $grandI }}</td>
                <td class="text-center" style="background: #fee2e2;">{{ $grandA }}</td>
                <td class="text-center">{{ ($grandH + $grandS + $grandI + $grandA) > 0 ? round(($grandH / ($grandH + $grandS + $grandI + $grandA)) * 100) : 100 }}%</td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan Resmi -->
    <table class="ttd-table">
        <tr>
            <td style="width: 50%; padding-left: 30px;">
                Mengetahui,<br>
                Kepala Sekolah
                <div class="space-ttd"></div>
                <strong>{{ $sekolah->kepala_sekolah ?? '....................................' }}</strong><br>
                NIP. {{ $sekolah->nip_kepala_sekolah ?? '........................' }}
            </td>
            <td style="width: 50%; text-align: right; padding-right: 30px;">
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ \Carbon\Carbon::createFromDate($tahun, $bulan, $jumlahHariBulan)->locale('id')->translatedFormat('d F Y') }}<br>
                {{ $tipe === 'wali' ? "Wali Kelas {$kelas->nama}" : "Guru Pengampu {$namaTipe}" }}
                <div class="space-ttd"></div>
                <strong>{{ $guruNama }}</strong><br>
                NIP. {{ $guruNip }}
            </td>
        </tr>
    </table>

</body>
</html>
