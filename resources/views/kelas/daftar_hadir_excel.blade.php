<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 12px; }
        .bordered th, .bordered td { border: 1px solid #000000; padding: 5px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-header { background-color: #E2EFDA; }
        .bg-alt { background-color: #F2F2F2; }
    </style>
</head>
<body>

    <!-- KOP & JUDUL -->
    <table>
        <tr>
            <td style="width: 80px; text-align: center; vertical-align: middle;">
                @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                    <img src="{{ asset('storage/' . $sekolah->logo) }}" width="65" height="65" alt="Logo Pemda">
                @endif
            </td>
            <td colspan="5" class="text-center font-bold">
                <div style="font-size: 11pt;">{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</div>
                <div style="font-size: 11pt;">{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</div>
                <div style="font-size: 14pt;">{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</div>
                <div style="font-size: 9pt; font-weight: normal;">{{ $sekolah->alamat ?? 'Jl. Parijatah No. 45 Karangsari, Kec. Sempu, Banyuwangi' }}</div>
                @if(!empty($sekolah->kontak))
                    <div style="font-size: 8pt; font-weight: normal;">{{ $sekolah->kontak }}</div>
                @endif
            </td>
            <td style="width: 80px; text-align: center; vertical-align: middle;">
                @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                    <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" width="65" height="65" alt="Logo Tut Wuri">
                @endif
            </td>
        </tr>
        <tr><td colspan="7" style="border-bottom: 2px solid #000;"></td></tr>
        <tr><td colspan="7"></td></tr>
        <tr>
            <td colspan="7" class="text-center font-bold" style="font-size: 12pt; text-decoration: underline;">
                DAFTAR HADIR {{ $periode === 'sts' ? 'ASESMEN / SUMATIF TENGAH SEMESTER (STS)' : 'ASESMEN / SUMATIF AKHIR SEMESTER (SAS)' }}
            </td>
        </tr>
        <tr>
            <td colspan="7" class="text-center font-bold">
                TAHUN AJARAN {{ $kelas->tahunAjaran->nama_lengkap ?? '2026/2027 Ganjil' }}
            </td>
        </tr>
    </table>

    <!-- INFORMASI UJIAN -->
    <table style="margin-bottom: 8px;">
        <tr>
            <td style="width: 15%;">Mata Pelajaran</td>
            <td style="width: 35%;">: <b>{{ !empty($mapel) ? $mapel : '....................................................' }}</b></td>
            <td style="width: 15%;">Hari / Tanggal</td>
            <td colspan="4">: <b>{{ !empty($tanggal) ? $tanggal : '....................................................' }}</b></td>
        </tr>
        <tr>
            <td>Kelas / Fase</td>
            <td>: <b>{{ $kelas->nama }} / {{ $kelas->fase ?? 'Fase A' }}</b></td>
            <td>Waktu</td>
            <td colspan="4">: <b>{{ !empty($waktu) ? $waktu : '....................................................' }}</b></td>
        </tr>
        <tr>
            <td>Semester</td>
            <td>: <b>{{ ucfirst($kelas->tahunAjaran->semester ?? 'Ganjil') }}</b></td>
            <td>Ruang Ujian</td>
            <td colspan="4">: <b>{{ !empty($ruang) ? $ruang : '....................................................' }}</b></td>
        </tr>
    </table>

    <!-- TABEL DATA SISWA -->
    <table class="bordered">
        <thead>
            <tr class="bg-header font-bold text-center">
                <th style="width: 5%;">No.</th>
                <th style="width: 12%;">NIS</th>
                <th style="width: 15%;">NISN</th>
                <th style="width: 36%;">Nama Lengkap Siswa</th>
                <th colspan="2" style="width: 24%;">Tanda Tangan / Kehadiran</th>
                <th style="width: 8%;">Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach($siswaList as $idx => $ks)
                @php
                    $nomor = $idx + 1;
                    $isGanjil = ($nomor % 2 !== 0);
                @endphp
                <tr>
                    <td class="text-center">{{ $nomor }}</td>
                    <td class="text-center" style="mso-number-format:'\@';">{{ $ks->siswa->nis }}</td>
                    <td class="text-center" style="mso-number-format:'\@';">{{ $ks->siswa->nisn ?? '-' }}</td>
                    <td style="text-transform: uppercase;">{{ $ks->siswa->nama }}</td>
                    
                    @if($isGanjil)
                        <td style="text-align: left;">{{ $nomor }}. ....................</td>
                        <td class="bg-alt"></td>
                    @else
                        <td class="bg-alt"></td>
                        <td style="text-align: left;">{{ $nomor }}. ....................</td>
                    @endif

                    <td class="text-center"></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- REKAPITULASI & TANDA TANGAN -->
    <br>
    <table>
        <tr>
            <!-- Box Rekapitulasi -->
            <td colspan="3" style="vertical-align: top; border: 1px solid #000; padding: 8px;">
                <div class="font-bold text-center" style="text-decoration: underline; margin-bottom: 4px;">
                    REKAPITULASI KEHADIRAN
                </div>
                <div>Jumlah Siswa Terdaftar : <b>{{ count($siswaList) }} Orang</b></div>
                <div>Jumlah Siswa Hadir &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ............. Orang</div>
                <div>Jumlah Tidak Hadir &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ............. Orang</div>
                <div style="padding-left: 12px; font-size: 9pt;">- Sakit (S) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ............. Orang</div>
                <div style="padding-left: 12px; font-size: 9pt;">- Izin (I) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ............. Orang</div>
                <div style="padding-left: 12px; font-size: 9pt;">- Tanpa Ket. (A) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ............. Orang</div>
            </td>

            <td style="width: 5%;"></td>

            <!-- Kolom Tanda Tangan -->
            <td colspan="3" style="vertical-align: top; text-align: center;">
                <div>{{ $sekolah->kota ?? 'Banyuwangi' }}, {{ !empty($tanggal) ? preg_replace('/^[A-Za-z]+,\s*/', '', $tanggal) : '........................................ 2026' }}</div>
                <table style="margin-top: 8px;">
                    <tr>
                        <td style="text-align: center; width: 50%;">
                            <div>Mengetahui,</div>
                            <div class="font-bold">Kepala Sekolah</div>
                            <br><br><br>
                            <div class="font-bold" style="text-decoration: underline;">{{ $sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</div>
                            <div style="font-size: 9pt;">NIP. {{ $sekolah->nip ?? '-' }}</div>
                        </td>
                        <td style="text-align: center; width: 50%;">
                            <div>&nbsp;</div>
                            <div class="font-bold">{{ $pengawasInfo['jabatan'] ?? 'Guru Pengawas / Pengampu' }}</div>
                            <br><br><br>
                            <div class="font-bold" style="text-decoration: underline;">{{ $pengawasInfo['nama'] ?? ($kelas->nama_wali ?? '.........................................') }}</div>
                            <div style="font-size: 9pt;">NIP. {{ ($pengawasInfo['nip'] ?? '-') !== '-' ? $pengawasInfo['nip'] : '.........................................' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
