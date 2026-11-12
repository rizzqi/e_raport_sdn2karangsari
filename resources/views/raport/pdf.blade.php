<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Raport - {{ $siswa->nama }}</title>
    @php
        $cfg = $printConfig ?? ($sekolah ? $sekolah->resolvePrintConfig() : [
            'paper' => 'a4',
            'orientation' => 'portrait',
            'css_page_size' => '210mm 297mm',
            'css_margin' => '10mm 12mm',
            'layout' => [
                'body_font' => '8pt',
                'table_font' => '7.5pt',
                'small_font' => '7pt',
                'cell_padding' => '3px 4px',
                'section_gap' => '6px',
                'ttd_height' => '42px',
            ],
        ]);
        $lay = $cfg['layout'];
    @endphp
    <style>
        @page {
            size: {{ $cfg['css_page_size'] }} {{ $cfg['orientation'] }};
            margin: {{ $cfg['css_margin'] }};
        }
        body {
            font-family: Arial, sans-serif;
            font-size: {{ $lay['body_font'] }};
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
            padding-bottom: 4px;
            margin-bottom: {{ $lay['section_gap'] }};
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-table h3 { margin: 1px 0; font-size: 9.5pt; font-weight: bold; text-transform: uppercase; }
        .kop-table h2 { margin: 2px 0; font-size: 12pt; font-weight: bold; text-transform: uppercase; }
        .kop-table p { margin: 1px 0; font-size: 8pt; }
        .kop-table .alamat { font-style: italic; }
        .kop-table .kontak { font-size: 7.5pt; color: #333; }
        .judul {
            text-align: center;
            margin-bottom: {{ $lay['section_gap'] }};
        }
        .judul h1 {
            margin: 0;
            font-size: 11pt;
            text-decoration: underline;
        }
        .judul p {
            margin: 2px 0;
            font-size: 9pt;
            font-weight: bold;
        }
        .identitas {
            width: 100%;
            margin-bottom: {{ $lay['section_gap'] }};
            font-size: {{ $lay['body_font'] }};
        }
        .identitas td {
            padding: 1.5px 0;
            vertical-align: top;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: {{ $lay['section_gap'] }};
            font-size: {{ $lay['table_font'] }};
        }
        table.data th, table.data td {
            border: 1px solid #000;
            padding: {{ $lay['cell_padding'] }};
        }
        table.data th {
            background-color: #f2f2f2;
        }
        .ttd {
            width: 100%;
            margin-top: {{ $lay['section_gap'] }};
            font-size: {{ $lay['body_font'] }};
            text-align: center;
        }
        .ttd td {
            vertical-align: top;
            padding: 0 5px;
        }
        .space-ttd {
            height: {{ $lay['ttd_height'] }};
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
        <p class="uppercase">KURIKULUM MERDEKA &bull; {{ $periode === 'sts' ? 'SUMATIF TENGAH SEMESTER (STS)' : 'SUMATIF AKHIR SEMESTER (SAS)' }}</p>
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

    <!-- TABEL NILAI (KURIKULUM MERDEKA) -->
    <table class="data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 24%; text-align: left;">Muatan Pelajaran</th>
                <th style="width: 11%;">Nilai Akhir</th>
                <th style="width: 9%;">Predikat</th>
                <th style="width: 51%; text-align: left;">Catatan Guru &amp; Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hasilNilai['baris'] as $b)
                <tr>
                    <td class="text-center" style="vertical-align: top;">{{ $b['no'] }}</td>
                    <td class="font-bold" style="vertical-align: top;">{{ $b['mapel_nama'] }}</td>
                    <td class="text-center font-bold" style="vertical-align: top;">{{ $b['nilai_rapor_format'] }}</td>
                    <td class="text-center font-bold" style="vertical-align: top;">{{ $b['predikat'] }}</td>
                    <td style="font-size: {{ $lay['small_font'] }}; line-height: 1.25; text-align: justify; vertical-align: top;">
                        @if(!empty($b['catatan_guru']))
                            <div><b>Catatan Guru:</b> {{ $b['catatan_guru'] }}</div>
                            @if(!empty($b['auto_cp']) && $b['auto_cp'] !== 'Belum ada penilaian capaian pembelajaran')
                                <div style="color: #444; margin-top: 2px;">{{ $b['auto_cp'] }}</div>
                            @endif
                        @else
                            {{ $b['capaian_kompetensi'] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9;" class="font-bold">
                <td colspan="2" class="text-right uppercase">Jumlah Nilai</td>
                <td class="text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_akhir']) }}</td>
                <td class="text-center">{{ $hasilNilai['predikat_rata'] ?? '-' }}</td>
                <td style="font-size: {{ $lay['small_font'] }}; color: #444;">Total nilai dari {{ $hasilNilai['total_mapel'] }} muatan pelajaran</td>
            </tr>
            <tr style="background-color: #f9f9f9;" class="font-bold">
                <td colspan="2" class="text-right uppercase">Rata-rata Nilai</td>
                <td class="text-center">{{ $hasilNilai['rata_akhir_format'] }}</td>
                <td class="text-center">{{ $hasilNilai['predikat_rata'] ?? '-' }}</td>
                <td style="font-size: {{ $lay['small_font'] }}; color: #444;">Predikat Rata-rata: <b>{{ $hasilNilai['predikat_rata'] ?? '-' }}</b></td>
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
                <th style="width: 58%; text-align: left;">Aspek Pembiasaan / Karakter</th>
                <th style="width: 17%;">Predikat</th>
                <th style="width: 25%;">Ketidakhadiran & Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pembiasaanList as $pIdx => $pem)
                @php
                    $pemVal = $nilaiPemList->get($pem->id);
                    $predikat = $pemVal ? $pemVal->predikat : 'Baik';
                    $alpaCount = $kehadiran ? (int)$kehadiran->alpa : 0;
                @endphp
                <tr>
                    <td>
                        <b>{{ $pIdx + 1 }}. {{ $pem->nama }}</b><br>
                        <span style="font-size: 7.5pt; color: #555;">{{ \App\Services\HitungNilaiService::generateDeskripsiPembiasaan($pem->nama, $predikat, $alpaCount) }}</span>
                    </td>
                    <td class="text-center font-bold">{{ $predikat }}</td>
                    @if($pIdx === 0)
                        <td rowspan="{{ $pembiasaanList->count() }}" style="vertical-align: top; font-size: 7.5pt; line-height: 1.3; padding: 4px;">
                            <div style="border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 3px;">
                                Hari Efektif: <b>{{ $hasilNilai['kehadiran_info']['hari_efektif'] ?? 108 }} hari</b><br>
                                Kehadiran: <b>{{ $hasilNilai['kehadiran_info']['hari_hadir'] ?? 108 }} hari ({{ $hasilNilai['kehadiran_info']['persen_kehadiran_format'] ?? '100%' }})</b>
                            </div>
                            Sakit (S) : <b>{{ $kehadiran ? $kehadiran->sakit : 0 }}</b> hari<br>
                            Izin (I) : <b>{{ $kehadiran ? $kehadiran->izin : 0 }}</b> hari<br>
                            Alpa (A) : <b>{{ $kehadiran ? $kehadiran->alpa : 0 }}</b> hari
                            <div style="border-top: 1px solid #ccc; padding-top: 3px; margin-top: 3px; text-align: center; font-weight: bold;">
                                {{ $hasilNilai['kehadiran_info']['status_kelayakan'] ?? 'Memenuhi Syarat' }}
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('raport.partials.ekstrakurikuler_pdf', ['ekskulListSiswa' => $ekskulListSiswa ?? []])

    <!-- CATATAN WALI KELAS -->
    <div style="font-weight: bold; margin-top: 6px; margin-bottom: 3px; font-size: 8.5pt; text-transform: uppercase;">
        Catatan Wali Kelas
    </div>
    <div style="border: 1px solid #000; padding: 4px 6px; font-size: 7.5pt; line-height: 1.3; margin-bottom: 6px; min-height: 25px;">
        {{ $hasilNilai['catatan_wali_otomatis'] ?? 'Peserta didik menunjukkan perkembangan yang baik dan terus didorong untuk berprestasi.' }}
    </div>

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
                <div class="space-ttd" style="height: 55px; text-align: center;">
                    @php
                        $ttdPath = $sekolah->ttd ? public_path('storage/' . $sekolah->ttd) : null;
                        if (!$ttdPath || !file_exists($ttdPath)) {
                            $ttdPath = $sekolah->ttd ? storage_path('app/public/' . $sekolah->ttd) : null;
                        }
                    @endphp
                    @if($ttdPath && file_exists($ttdPath))
                        <img src="{{ $ttdPath }}" style="max-height: 50px; max-width: 140px;" alt="TTD">
                    @endif
                </div>
                <b>{{ $sekolah->kepsek ?? '( ................................ )' }}</b><br>
                <span style="font-size: 8pt;">NIP. {{ $sekolah->nip ?? '-' }}</span>
            </td>
            <td style="width: 34%;">
                {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ (isset($tanggalObj) ? $tanggalObj : \Carbon\Carbon::now()->locale('id'))->translatedFormat('d F Y') }}<br>
                Wali Kelas,
                <div class="space-ttd"></div>
                <b>{{ $kelas->nama_wali }}</b><br>
                <span style="font-size: 8pt;">{{ $kelas->nip_wali !== '-' ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
