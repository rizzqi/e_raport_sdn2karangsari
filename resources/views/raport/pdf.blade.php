<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Raport - {{ $siswa->nama }}</title>
    <style>
        @page {
            size: 215mm 330mm portrait;
            margin: 1cm 1.2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-table h3 { margin: 1px 0; font-size: 10.5pt; font-weight: bold; text-transform: uppercase; }
        .kop-table h2 { margin: 2px 0; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .kop-table p { margin: 1px 0; font-size: 8.5pt; }
        .kop-table .alamat { font-style: italic; }
        .kop-table .kontak { font-size: 8pt; color: #333; }
        .judul {
            text-align: center;
            margin-bottom: 12px;
        }
        .judul h1 {
            margin: 0;
            font-size: 12pt;
            text-decoration: underline;
        }
        .judul p {
            margin: 3px 0;
            font-size: 9.5pt;
            font-weight: bold;
        }
        .identitas {
            width: 100%;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        .identitas td {
            padding: 2px 0;
            vertical-align: top;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        table.data th {
            background-color: #f2f2f2;
        }
        .ttd {
            width: 100%;
            margin-top: 15px;
            font-size: 9pt;
            text-align: center;
        }
        .ttd td {
            vertical-align: top;
            padding: 0 5px;
        }
        .space-ttd {
            height: 55px;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI F4 -->
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: left;">
                @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ public_path('storage/' . $sekolah->logo) }}" style="max-height: 65px; max-width: 65px;">
                @endif
            </td>
            <td style="width: 70%; text-align: center;">
                <h3>{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                <h3>{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                <h2>{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                <p class="alamat">{{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}</p>
                @if($sekolah->kontak)
                    <p class="kontak">{{ $sekolah->kontak }}</p>
                @endif
            </td>
            <td style="width: 15%; text-align: right;">
                @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ public_path('storage/' . $sekolah->logo_kanan) }}" style="max-height: 65px; max-width: 65px;">
                @endif
            </td>
        </tr>
    </table>

    <!-- JUDUL -->
    <div class="judul">
        <h1 class="uppercase font-bold">LAPORAN HASIL BELAJAR PESERTA DIDIK</h1>
        <p class="uppercase">{{ $periode === 'sts' ? 'SUMATIF TENGAH SEMESTER (STS)' : 'SUMATIF AKHIR SEMESTER (SAS)' }}</p>
    </div>

    <!-- IDENTITAS -->
    <table class="identitas">
        <tr>
            <td style="width: 20%;">Nama Peserta Didik</td>
            <td style="width: 35%;" class="font-bold">: {{ $siswa->nama }}</td>
            <td style="width: 20%;">Kelas / Fase</td>
            <td style="width: 25%;">: {{ $kelas->nama }} / {{ $kelas->fase ?? 'A' }}</td>
        </tr>
        <tr>
            <td>NIS / NISN</td>
            <td>: {{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</td>
            <td>Semester / Tahun</td>
            <td>: {{ ucfirst($tahun->semester ?? 'Ganjil') }} / {{ $tahun->tahun ?? '2026/2027' }}</td>
        </tr>
    </table>

    <!-- TABEL NILAI -->
    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 55%; text-align: left;">Mata Pelajaran</th>
                <th style="width: 20%;">Nilai Formatif</th>
                <th style="width: 20%;">Nilai Sumatif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hasilNilai['baris'] as $b)
                <tr>
                    <td class="text-center">{{ $b['no'] }}</td>
                    <td>{{ $b['mapel_nama'] }}</td>
                    <td class="text-center font-bold">{{ $b['formatif_format'] }}</td>
                    <td class="text-center font-bold">{{ $b['sumatif_format'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9;" class="font-bold">
                <td colspan="2" class="text-right uppercase">Jumlah Nilai</td>
                <td class="text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_formatif']) }}</td>
                <td class="text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_sumatif']) }}</td>
            </tr>
            <tr style="background-color: #f9f9f9;" class="font-bold">
                <td colspan="2" class="text-right uppercase">Rata-rata Nilai</td>
                <td class="text-center">{{ $hasilNilai['rata_formatif_format'] }}</td>
                <td class="text-center">{{ $hasilNilai['rata_sumatif_format'] }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- TABEL PEMBIASAAN DAN KEHADIRAN -->
    <div style="font-weight: bold; margin-bottom: 4px; font-size: 9pt; text-transform: uppercase;">
        Perkembangan Karakter & Ketidakhadiran
    </div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 60%; text-align: left;">Aspek Pembiasaan / Karakter</th>
                <th style="width: 20%;">Predikat</th>
                <th style="width: 20%;">Ketidakhadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembiasaanList as $pIdx => $pem)
                @php
                    $pemVal = $nilaiPemList->get($pem->id);
                    $predikat = $pemVal ? $pemVal->predikat : 'Baik';
                @endphp
                <tr>
                    <td>{{ $pIdx + 1 }}. {{ $pem->nama }}</td>
                    <td class="text-center font-bold">{{ $predikat }}</td>
                    @if($pIdx === 0)
                        <td rowspan="{{ $pembiasaanList->count() }}" style="vertical-align: top; text-align: center; padding-top: 8px;">
                            Sakit : <b>{{ $kehadiran ? $kehadiran->sakit : 0 }}</b> hari<br>
                            Izin : <b>{{ $kehadiran ? $kehadiran->izin : 0 }}</b> hari<br>
                            Tanpa Ket. : <b>{{ $kehadiran ? $kehadiran->alpa : 0 }}</b> hari
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <table class="ttd">
        <tr>
            <td style="width: 33%;">
                Mengetahui,<br>
                Orang Tua / Wali Murid
                <div class="space-ttd"></div>
                <b>( ........................................ )</b>
            </td>
            <td style="width: 33%;">
                <br>
                Kepala Sekolah,
                <div class="space-ttd"></div>
                <b>{{ $sekolah->kepsek ?? '( ................................ )' }}</b><br>
                <span style="font-size: 8pt;">NIP. {{ $sekolah->nip ?? '-' }}</span>
            </td>
            <td style="width: 34%;">
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Wali Kelas,
                <div class="space-ttd"></div>
                <b>{{ $kelas->wali->name ?? '( ................................ )' }}</b><br>
                <span style="font-size: 8pt;">NIP. -</span>
            </td>
        </tr>
    </table>

</body>
</html>
