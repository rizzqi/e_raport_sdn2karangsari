<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Hasil Belajar {{ strtoupper($periode) }} Sekelas - {{ $kelas->nama }}</title>
    <style>
        @page {
            size: 215mm 330mm portrait; /* Standar F4 */
            margin: 10mm 15mm 12mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            line-height: 1.25;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .kop-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px double #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-table h3 { margin: 1px 0; font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .kop-table h2 { margin: 2px 0; font-size: 13pt; font-weight: 900; text-transform: uppercase; }
        .kop-table p { margin: 1px 0; font-size: 8pt; }
        .kop-table .alamat { }
        .kop-table .kontak { font-size: 7.5pt; color: #333; }

        .judul {
            text-align: center;
            margin-bottom: 10px;
        }
        .judul h1 {
            margin: 0;
            font-size: 11pt;
            font-weight: 900;
            text-transform: uppercase;
        }
        .judul p {
            margin: 2px 0 0 0;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .identitas {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .identitas td {
            padding: 1.5px 0;
            vertical-align: top;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8.5pt;
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: 3.5px 6px;
        }
        table.data th {
            background-color: #ffffff;
            font-weight: bold;
            text-align: center;
        }

        .section-title {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .ttd {
            width: 100%;
            margin-top: 15px;
            font-size: 8.5pt;
            text-align: center;
        }
        .ttd td {
            vertical-align: top;
            padding: 0 4px;
        }
        .space-ttd {
            height: 48px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    @php
        $logoKiriPath = $sekolah->logo ? public_path('storage/' . $sekolah->logo) : null;
        if (!$logoKiriPath || !file_exists($logoKiriPath)) {
            $logoKiriPath = $sekolah->logo ? storage_path('app/public/' . $sekolah->logo) : null;
        }
        $logoKananPath = $sekolah->logo_kanan ? public_path('storage/' . $sekolah->logo_kanan) : null;
        if (!$logoKananPath || !file_exists($logoKananPath)) {
            $logoKananPath = $sekolah->logo_kanan ? storage_path('app/public/' . $sekolah->logo_kanan) : null;
        }
        $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
        if (!$ttdPath || !file_exists($ttdPath)) {
            $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
        }
    @endphp

    @foreach($laporanSiswa as $idxSiswa => $item)
        @php
            $ks = $item['kelasSiswa'];
            $siswa = $item['siswa'];
            $hasilNilai = $item['hasilNilai'];
            $totalNilai = $item['totalNilai'];
            $rataNilai = $item['rataNilai'];
            $nilaiPemList = $item['nilaiPemList'];
            $kehadiran = $item['kehadiran'];
        @endphp

        <div class="sheet">
            <!-- KOP SURAT RESMI STANDAR SEKOLAH -->
            <table class="kop-table">
                <tr>
                    <td style="width: 15%; text-align: left;">
                        @if($logoKiriPath && file_exists($logoKiriPath))
                            <img src="{{ $logoKiriPath }}" style="max-height: 60px; max-width: 60px;">
                        @endif
                    </td>
                    <td style="width: 70%; text-align: center;">
                        <h3>{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                        <h3>{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                        <h2>{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                        <p class="alamat">{{ $sekolah->alamat ?? 'Jl. Parijatah Nomor. 45 Karangsari, Banyuwangi, Jawa Timur' }}</p>
                        <p class="kontak">
                            {{ $sekolah->kontak ?? 'Kode Pos 68468, NPSN : 20526449, NSS : 101052502014, NIS : 100380, sdn2krsr@gmail.com' }}
                        </p>
                    </td>
                    <td style="width: 15%; text-align: right;">
                        @if($logoKananPath && file_exists($logoKananPath))
                            <img src="{{ $logoKananPath }}" style="max-height: 60px; max-width: 60px;">
                        @endif
                    </td>
                </tr>
            </table>

            <!-- JUDUL LAPORAN HASIL BELAJAR -->
            <div class="judul">
                <h1>LAPORAN HASIL BELAJAR PESERTA DIDIK</h1>
                <p>{{ $periode === 'sas' ? 'SUMATIF AKHIR SEMESTER (SAS)' : 'SUMATIF TENGAH SEMESTER (STS)' }}</p>
            </div>

            <!-- IDENTITAS PESERTA DIDIK -->
            <table class="identitas">
                <tr>
                    <td style="width: 18%;">Nama Peserta Didik</td>
                    <td style="width: 35%; font-weight: bold;">: {{ $siswa->nama }}</td>
                    <td style="width: 18%;">Kelas / Fase</td>
                    <td style="width: 29%;">: {{ $kelas->nama }} / {{ $kelas->fase ?? 'Fase A' }}</td>
                </tr>
                <tr>
                    <td>NIS / NISN</td>
                    <td>: {{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</td>
                    <td>Semester / Tahun</td>
                    <td>: {{ ucfirst($tahun->semester ?? 'Ganjil') }} / {{ $tahun->tahun ?? '2026/2027' }}</td>
                </tr>
            </table>

            <!-- TABEL NILAI MATA PELAJARAN -->
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 48%; text-align: left;">Mata Pelajaran</th>
                        <th style="width: 14%;">KKM</th>
                        <th style="width: 15%;">Nilai</th>
                        <th style="width: 18%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hasilNilai['baris'] as $b)
                        @php
                            $valNilai = $b['sumatif'] !== null ? $b['sumatif'] : $b['nilai'];
                            $kkmVal = $b['kkm'];
                            if ($valNilai !== null) {
                                $ket = $valNilai >= $kkmVal ? 'Tuntas' : 'Belum Tuntas';
                                $displayNilai = \App\Services\HitungNilaiService::formatAngka($valNilai);
                            } else {
                                $ket = '-';
                                $displayNilai = '-';
                            }
                        @endphp
                        <tr>
                            <td class="text-center">{{ $b['no'] }}</td>
                            <td class="text-left">{{ $b['mapel_nama'] }}</td>
                            <td class="text-center font-bold">{{ $kkmVal }}</td>
                            <td class="text-center font-bold">{{ $displayNilai }}</td>
                            <td class="text-center">{{ $ket }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="2" class="text-right uppercase" style="padding-right: 12px;">JUMLAH NILAI</td>
                        <td class="text-center">-</td>
                        <td class="text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($totalNilai) }}</td>
                        <td class="text-center">-</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="2" class="text-right uppercase" style="padding-right: 12px;">RATA-RATA NILAI</td>
                        <td class="text-center">-</td>
                        <td class="text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($rataNilai) }}</td>
                        <td class="text-center">-</td>
                    </tr>
                </tfoot>
            </table>

            <!-- TABEL PERKEMBANGAN KARAKTER & KETIDAKHADIRAN -->
            <div class="section-title">
                PERKEMBANGAN KARAKTER &amp; KETIDAKHADIRAN
            </div>
            <table class="data" style="margin-bottom: 10px;">
                <thead>
                    <tr>
                        <th style="width: 58%; text-align: left;">Aspek Pembiasaan / Karakter</th>
                        <th style="width: 18%;">Predikat</th>
                        <th style="width: 24%;">Ketidakhadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pembiasaanList as $pIdx => $pem)
                        @php
                            $pemVal = $nilaiPemList->get($pem->id);
                            $predikat = $pemVal ? $pemVal->predikat : 'Baik';
                        @endphp
                        <tr>
                            <td class="text-left">{{ $pIdx + 1 }}. {{ $pem->nama }}</td>
                            <td class="text-center font-bold">{{ $predikat }}</td>
                            @if($pIdx === 0)
                                <td rowspan="{{ $pembiasaanList->count() }}" style="vertical-align: top; padding: 6px 8px; line-height: 1.55;">
                                    Sakit : <strong>{{ $kehadiran ? $kehadiran->sakit : 0 }}</strong> hari<br>
                                    Izin : <strong>{{ $kehadiran ? $kehadiran->izin : 0 }}</strong> hari<br>
                                    Tanpa Ket. : <strong>{{ $kehadiran ? $kehadiran->alpa : 0 }}</strong> hari
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- TANDA TANGAN 3 PIHAK -->
            <table class="ttd">
                <tr>
                    <td style="width: 32%;">
                        Mengetahui,<br>
                        Orang Tua / Wali Murid
                        <div class="space-ttd"></div>
                        <b>( ........................................ )</b>
                    </td>
                    <td style="width: 36%;">
                        <br>
                        Kepala Sekolah,
                        <div class="space-ttd" style="text-align: center;">
                            @if($ttdPath && file_exists($ttdPath))
                                <img src="{{ $ttdPath }}" style="max-height: 44px; max-width: 120px;" alt="TTD">
                            @endif
                        </div>
                        <b>{{ $sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</b><br>
                        <span style="font-size: 8pt;">NIP. {{ $sekolah->nip ?? '196908222014071001' }}</span>
                    </td>
                    <td style="width: 32%;">
                        {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ (isset($tanggalObj) ? $tanggalObj : \Carbon\Carbon::now()->locale('id'))->translatedFormat('d F Y') }}<br>
                        Wali Kelas,
                        <div class="space-ttd"></div>
                        <b>{{ $kelas->nama_wali }}</b><br>
                        <span style="font-size: 8pt;">{{ ($kelas->nip_wali && $kelas->nip_wali !== '-') ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</span>
                    </td>
                </tr>
            </table>
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
