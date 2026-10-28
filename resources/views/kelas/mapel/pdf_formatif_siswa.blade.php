<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Nilai Formatif - {{ $siswa->nama }} - {{ $mapel->nama }}</title>
    <style>
        @page {
            size: 215mm 330mm; /* Standar F4 / Folio */
            margin: 12mm 18mm 15mm 18mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #111;
            line-height: 1.35;
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
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-center h2 {
            margin: 2px 0;
            font-size: 14pt;
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
            max-height: 70px;
            max-width: 70px;
        }
        .title-block {
            text-align: center;
            margin: 12px 0 15px 0;
        }
        .title-block h1 {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .title-block p {
            margin: 3px 0 0 0;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
        }
        .meta-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9.5pt;
        }
        .meta-box td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 14px;
        }
        .data-table th, .data-table td {
            border: 1px solid #111;
            padding: 5px 8px;
        }
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .tuntas { color: #047857; font-weight: bold; }
        .belum-tuntas { color: #b91c1c; font-weight: bold; }
        .summary-row {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .catatan-box {
            border: 1px solid #333;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 9pt;
            background-color: #fff;
            min-height: 45px;
        }
        .catatan-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
            margin-bottom: 4px;
            color: #333;
        }
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9.5pt;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
        }
        .sign-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <!-- 1. KOP SURAT RESMI STANDAR F4 (SDN 2 KARANGSARI) -->
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

    <!-- 2. JUDUL LEMBAR PENILAIAN -->
    <div class="title-block">
        <h1>LEMBAR HASIL PENILAIAN FORMATIF PESERTA DIDIK</h1>
        <p>MATA PELAJARAN: {{ strtoupper($mapel->nama) }} &bull; SEMESTER {{ strtoupper($semester) }} TP {{ $tahunAjaran->tahun ?? $tahun }}</p>
    </div>

    <!-- 3. IDENTITAS PESERTA DIDIK (1 SISWA) -->
    <table class="meta-box">
        <tr>
            <td style="width: 22%;">Nama Peserta Didik</td>
            <td style="width: 2%;">:</td>
            <td style="width: 40%; font-weight: bold; font-size: 10pt;">{{ $siswa->nama }}</td>
            <td style="width: 16%;">Kelas / Fase</td>
            <td style="width: 2%;">:</td>
            <td style="width: 18%; font-weight: bold;">{{ $kelas->nama }} / {{ $kelas->fase ?? 'A' }}</td>
        </tr>
        <tr>
            <td>NIS / NISN</td>
            <td>:</td>
            <td style="font-family: monospace;">{{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</td>
            <td>Standar KKM</td>
            <td>:</td>
            <td style="font-weight: bold;">{{ $kkm }}</td>
        </tr>
        <tr>
            <td>Jenis Kelamin</td>
            <td>:</td>
            <td>{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            <td>Guru Pengampu</td>
            <td>:</td>
            <td>{{ $guruPengampu ? $guruPengampu->nama : ($kelas->nama_wali ?? '-') }}</td>
        </tr>
    </table>

    <!-- 4. TABEL NILAI FORMATIF SISWA (BULAN KE BULAN) -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 24%; text-align: left;">Bulan Penilaian</th>
                <th style="width: 38%; text-align: left;">Materi / Kegiatan Penilaian</th>
                <th style="width: 10%;">KKM</th>
                <th style="width: 10%;">Nilai</th>
                <th style="width: 12%;">Ketuntasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($baris as $idx => $b)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-bold">{{ $b['bulan_nama'] }}</td>
                    <td>{{ $b['lingkup_materi'] }}</td>
                    <td class="text-center font-bold">{{ $b['kkm'] }}</td>
                    <td class="text-center font-bold" style="font-size: 10pt;">{{ $b['nilai_format'] }}</td>
                    <td class="text-center">
                        @if($b['keterangan'] === 'Tuntas')
                            <span class="tuntas">Tuntas</span>
                        @elseif($b['keterangan'] === 'Belum Tuntas')
                            <span class="belum-tuntas">Belum Tuntas</span>
                        @else
                            <span style="color: #999;">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <td colspan="4" class="text-right" style="padding-right: 12px; text-transform: uppercase;">Rata-rata Nilai Formatif :</td>
                <td class="text-center font-bold" style="font-size: 11pt;">{{ $rataFormat }}</td>
                <td class="text-center font-bold">
                    @if($statusAkhir === 'Tuntas')
                        <span class="tuntas">Tuntas</span>
                    @elseif($statusAkhir === 'Perlu Pendampingan')
                        <span class="belum-tuntas">Perlu Bimbingan</span>
                    @else
                        <span>{{ $statusAkhir }}</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- 5. CATATAN / EVALUASI GURU MATA PELAJARAN -->
    <div class="catatan-box">
        <div class="catatan-title">Catatan Perkembangan Belajar & Evaluasi Guru:</div>
        <div>
            @if(!empty($catatanGuru))
                {{ $catatanGuru }}
            @else
                Peserta didik telah mengikuti seluruh rangkaian pembelajaran dan evaluasi formatif mata pelajaran {{ $mapel->nama }} dengan baik. Diharapkan terus mempertahankan dan meningkatkan semangat belajar.
            @endif
        </div>
    </div>

    <!-- 6. BLOK 3 TANDA TANGAN RESMI -->
    <table class="sign-table">
        <tr>
            <td>
                <p>Mengetahui,</p>
                <p class="font-bold">Orang Tua / Wali Murid</p>
                <div class="sign-space"></div>
                <p class="font-bold" style="text-decoration: underline;">( ........................................ )</p>
            </td>
            <td>
                <p>&nbsp;</p>
                <p class="font-bold">Kepala Sekolah</p>
                <div class="sign-space" style="display: flex; align-items: center; justify-content: center;">
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
