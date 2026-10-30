<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Nilai Penilaian Harian - {{ $mapel->nama }} - {{ $namaBulan }} {{ $tahun }} - Kelas {{ $kelas->nama }}</title>
    <style>
        @page {
            size: 215mm 330mm portrait; /* Format F4 / Folio Standar */
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
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
            font-size: 9pt;
        }
        .identitas-table td {
            padding: 2px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
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

        .ttd-table {
            width: 100%;
            margin-top: 25px;
            font-size: 9pt;
        }
        .ttd-table td {
            vertical-align: top;
        }
        .space-ttd {
            height: 50px;
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak Browser -->
    <div class="no-print" style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 10px 16px; margin-bottom: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 9pt; color: #3730a3; font-weight: bold;">
            🖨️ Pratinjau Dokumen Cetak Penilaian Harian (Ukuran Standar Folio / F4)
        </span>
        <button type="button" onclick="window.print()" style="background: #2563eb; color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Cetak Sekarang
        </button>
    </div>

    <!-- Kop Surat Resmi Sekolah -->
    <table class="kop-table">
        <tr>
            <td style="width: 70px; text-align: center;">
                @if($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo" style="height: 60px;">
                @elseif(file_exists(public_path('storage/sekolah/logo_kanan_6ace3397cbcc5.png')))
                    <img src="{{ asset('storage/sekolah/logo_kanan_6ace3397cbcc5.png') }}" alt="Logo" style="height: 60px;">
                @else
                    <div style="font-size: 24pt; font-weight: bold;">SD</div>
                @endif
            </td>
            <td class="kop-title">
                <h3>{{ strtoupper($sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI') }}</h3>
                <h3>{{ strtoupper($sekolah->dinas ?? 'DINAS PENDIDIKAN') }}</h3>
                <h2>{{ strtoupper($sekolah->nama ?? 'SD NEGERI 2 KARANGSARI') }}</h2>
                <p>{{ $sekolah->alamat ?? 'Dusun Karanganyar, Desa Karangsari, Kec. Sempu, Kab. Banyuwangi' }}</p>
            </td>
            <td style="width: 70px;"></td>
        </tr>
    </table>

    <div class="text-center font-bold uppercase" style="font-size: 11pt; margin-bottom: 12px; letter-spacing: 0.5px;">
        DAFTAR NILAI PENILAIAN HARIAN FORMATIF PESERTA DIDIK
    </div>

    <!-- Identitas Mata Pelajaran & Kelas -->
    <table class="identitas-table">
        <tr>
            <td style="width: 18%;">Mata Pelajaran</td>
            <td style="width: 2%;">:</td>
            <td style="width: 40%; font-weight: bold;">{{ $mapel->nama }}</td>
            <td style="width: 18%;">Kelas / Rombel</td>
            <td style="width: 2%;">:</td>
            <td style="width: 20%; font-weight: bold;">{{ $kelas->nama }}</td>
        </tr>
        <tr>
            <td>Bulan / Tahun</td>
            <td>:</td>
            <td><strong>{{ $namaBulan }} {{ $tahun }}</strong></td>
            <td>Semester</td>
            <td>:</td>
            <td>{{ ucfirst($semester) }} (T.A. {{ $tahunAjaran->nama_lengkap ?? '-' }})</td>
        </tr>
        <tr>
            <td>Standar KKM/KKTP</td>
            <td>:</td>
            <td><strong>{{ $kelas->getKkmForMapel($mapel) }}</strong></td>
            <td>Wali Kelas</td>
            <td>:</td>
            <td>{{ $kelas->nama_wali }}</td>
        </tr>
    </table>

    <!-- Tabel Nilai Harian -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 14%;">NIS</th>
                <th style="text-align: left; width: 34%;">Nama Peserta Didik</th>
                @forelse($penilaianList as $p)
                    <th style="min-width: 45px;">
                        {{ $p->nama }}
                        @if($p->tanggal)
                            <br><span style="font-size: 7.5pt; font-weight: normal;">{{ $p->tanggal->format('d/m') }}</span>
                        @endif
                    </th>
                @empty
                    <th>Nilai Harian</th>
                @endforelse
                <th style="width: 12%;">Rata-rata {{ substr($namaBulan, 0, 3) }}</th>
                <th style="width: 12%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php
                $kkm = $kelas->getKkmForMapel($mapel);
                $totalKelas = 0;
                $countKelas = 0;
            @endphp

            @foreach($kelasSiswaList as $idx => $ks)
                @php
                    $scores = [];
                    foreach ($penilaianList as $p) {
                        $val = $matrixHarian[$p->id][$ks->id] ?? null;
                        if ($val !== null && $val !== '') {
                            $scores[] = (float) $val;
                        }
                    }
                    $avg = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
                    if ($avg !== null) {
                        $totalKelas += $avg;
                        $countKelas++;
                    }
                    $ket = $avg !== null ? ($avg >= $kkm ? 'Tuntas' : 'Perlu Bimbingan') : '-';
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $ks->siswa->nis }}</td>
                    <td style="font-weight: bold;">{{ $ks->siswa->nama }}</td>

                    @forelse($penilaianList as $p)
                        @php
                            $score = $matrixHarian[$p->id][$ks->id] ?? null;
                        @endphp
                        <td class="text-center" style="font-family: monospace; font-weight: bold;">
                            {{ $score !== null ? \App\Services\HitungNilaiService::formatAngka($score) : '-' }}
                        </td>
                    @empty
                        <td class="text-center">-</td>
                    @endforelse

                    <td class="text-center" style="font-family: monospace; font-weight: bold; background-color: #fafafa;">
                        {{ $avg !== null ? \App\Services\HitungNilaiService::formatAngka($avg) : '-' }}
                    </td>
                    <td class="text-center" style="font-size: 8pt; font-weight: {{ $ket === 'Tuntas' ? 'normal' : 'bold' }}; color: {{ $ket === 'Tuntas' ? '#000' : '#b91c1c' }};">
                        {{ $ket }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            @php
                $rataRataKelas = $countKelas > 0 ? round($totalKelas / $countKelas, 1) : 0;
            @endphp
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ 3 + max(1, $penilaianList->count()) }}" class="text-right" style="padding-right: 12px;">
                    Rata-rata Kelas Bulan {{ $namaBulan }}:
                </td>
                <td class="text-center" style="font-family: monospace; font-size: 9pt;">
                    {{ \App\Services\HitungNilaiService::formatAngka($rataRataKelas) }}
                </td>
                <td class="text-center" style="font-size: 8pt;">
                    {{ $rataRataKelas >= $kkm ? 'Tuntas' : 'Perlu Perhatian' }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Keterangan Lingkup Materi / TP jika ada -->
    @php
        $materiList = $penilaianList->filter(fn ($p) => !empty($p->lingkup_materi));
    @endphp
    @if($materiList->isNotEmpty())
        <div style="margin-top: 10px; font-size: 8pt; color: #444; border: 1px dashed #ccc; padding: 6px 10px; border-radius: 4px;">
            <strong>Keterangan Tujuan Pembelajaran / Lingkup Materi:</strong>
            <ul style="margin: 2px 0 0 16px; padding: 0;">
                @foreach($materiList as $p)
                    <li><strong>{{ $p->nama }}</strong>: {{ $p->lingkup_materi }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tanda Tangan Resmi -->
    <table class="ttd-table">
        <tr>
            <td style="width: 55%;">
                Mengetahui,<br>
                Kepala Sekolah {{ $sekolah->nama ?? 'SD Negeri 2 Karangsari' }}
                <div class="space-ttd"></div>
                <strong><u>{{ $sekolah->kepsek ?? 'SUSILOWATI, S.Pd.' }}</u></strong><br>
                NIP. {{ $sekolah->nip ?? '197108261996052001' }}
            </td>
            <td style="width: 45%; text-align: left;">
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ $tanggalObj->translatedFormat('d F Y') }}<br>
                Guru Pengampu / Wali Kelas
                <div class="space-ttd"></div>
                <strong><u>{{ $kelas->nama_wali }}</u></strong><br>
                NIP. {{ $kelas->nip_wali ?? '-' }}
            </td>
        </tr>
    </table>

</body>
</html>
