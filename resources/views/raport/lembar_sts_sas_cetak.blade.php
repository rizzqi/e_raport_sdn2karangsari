<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Lembar {{ strtoupper($periode) }} Sekelas - {{ $kelas->nama }}</title>
    <!-- Tailwind CSS (CDN / local) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @page {
            size: 215mm 330mm portrait; /* Standar F4 */
            margin: 10mm 15mm 12mm 15mm;
        }
        @media print {
            body {
                background: white !important;
                color: #000 !important;
                font-family: Arial, Helvetica, sans-serif !important;
                font-size: 8.5pt !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet-card {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                page-break-after: always !important;
            }
            .sheet-card:last-child {
                page-break-after: avoid !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #000 !important;
            }
        }
        @media screen {
            body {
                background-color: #f1f5f9;
                font-family: Arial, Helvetica, sans-serif;
            }
            .sheet-card {
                background: white;
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 24px;
                margin-bottom: 24px;
                max-width: 800px;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>
<body class="text-slate-900 antialiased py-6">

    <!-- TOOLBAR AKSI (HANYA LAYAR / NO-PRINT) -->
    <div class="max-w-4xl mx-auto mb-6 px-4 no-print">
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('kelas.show', $kelas->id) }}"
                   class="px-3 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali ke Kelas</span>
                </a>

                @if(!empty($isSingleSiswa))
                    <!-- Switcher STS / SAS Siswa Tunggal -->
                    <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                        <a href="{{ route('kelas.lembar-sts-sas.cetak-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => 'sts']) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            STS (Tengah Semester)
                        </a>
                        <a href="{{ route('kelas.lembar-sts-sas.cetak-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => 'sas']) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            SAS (Akhir Semester)
                        </a>
                    </div>

                    <a href="{{ route('raport.preview', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode]) }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5"
                       title="Buka format Rapor Resmi Kurikulum Merdeka (dengan capaian kompetensi)">
                        <i class="fa-solid fa-file-contract text-emerald-600"></i>
                        <span>Format Rapor Lengkap</span>
                    </a>
                @else
                    <!-- Switcher STS / SAS Sekelas -->
                    <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                        <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sts', 'tanggal' => $tanggal]) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sts' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            STS (Tengah Semester)
                        </a>
                        <a href="{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => 'sas', 'tanggal' => $tanggal]) }}"
                           class="px-3 py-1.5 rounded-lg transition {{ $periode === 'sas' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900' }}">
                            SAS (Akhir Semester)
                        </a>
                    </div>

                    <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => $periode]) }}"
                       class="px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition flex items-center gap-1.5"
                       title="Buka format Rapor Resmi Kurikulum Merdeka Sekelas">
                        <i class="fa-solid fa-file-contract text-emerald-600"></i>
                        <span>Rapor Lengkap Sekelas</span>
                    </a>

                    <span class="inline-flex items-center px-2.5 py-1.5 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-bold">
                        <i class="fa-solid fa-users mr-1.5 text-blue-600"></i> {{ count($laporanSiswa) }} Siswa
                    </span>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Ubah Tanggal Cetak -->
                <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-3 py-1 rounded-xl text-xs">
                    <i class="fa-regular fa-calendar-days text-slate-500"></i>
                    <label for="inputTanggalCetak" class="font-bold text-slate-700 whitespace-nowrap">Tgl Cetak:</label>
                    <input type="date" id="inputTanggalCetak" value="{{ $tanggalObj->format('Y-m-d') }}"
                           @if(!empty($isSingleSiswa))
                               onchange="location.href='{{ route('kelas.lembar-sts-sas.cetak-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => $periode]) }}?tanggal=' + this.value"
                           @else
                               onchange="location.href='{{ route('kelas.lembar-sts-sas.cetak', ['kelas' => $kelas->id, 'periode' => $periode]) }}?tanggal=' + this.value"
                           @endif
                           class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                @if(!empty($isSingleSiswa))
                    <!-- Unduh PDF Siswa -->
                    <a href="{{ route('kelas.lembar-sts-sas.pdf-siswa', ['kelas' => $kelas->id, 'kelasSiswa' => $kelasSiswa->id, 'periode' => $periode, 'tanggal' => $tanggal]) }}"
                       class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Unduh PDF</span>
                    </a>
                @else
                    <!-- Unduh PDF Sekelas -->
                    <a href="{{ route('kelas.lembar-sts-sas.pdf-sekelas', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggal]) }}"
                       class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-file-pdf"></i>
                        <span>Unduh PDF Sekelas</span>
                    </a>
                @endif

                <!-- Cetak Langsung (Ctrl+P) -->
                <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-print text-amber-400"></i>
                    <span>{{ !empty($isSingleSiswa) ? 'Cetak Lembar Ini (F4)' : 'Cetak Semua (Ctrl+P)' }}</span>
                </button>
            </div>
        </div>
    </div>

    <!-- DOKUMEN CETAK SELURUH SISWA (1 LEMBAR PER SISWA) -->
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

        <div class="sheet-card text-[8.5pt] leading-tight">
            <!-- KOP SURAT RESMI STANDAR SEKOLAH -->
            <table class="w-full border-collapse border-b-[3px] border-double border-black pb-1 mb-2.5">
                <tr>
                    <td class="w-[15%] text-left border-none align-middle">
                        @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                            <img src="{{ asset('storage/' . $sekolah->logo) }}" class="max-h-[60px] max-w-[60px] object-contain">
                        @endif
                    </td>
                    <td class="w-[70%] text-center border-none align-middle">
                        <h3 class="m-0 text-[10pt] font-bold uppercase tracking-wide">{{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}</h3>
                        <h3 class="m-0 text-[10pt] font-bold uppercase tracking-wide">{{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}</h3>
                        <h2 class="my-0.5 text-[13pt] font-black uppercase tracking-wide">{{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}</h2>
                        <p class="m-0 text-[8pt]">{{ $sekolah->alamat ?? 'Jl. Parijatah Nomor. 45 Karangsari, Banyuwangi, Jawa Timur' }}</p>
                        <p class="m-0 text-[7.5pt] text-slate-700">
                            {{ $sekolah->kontak ?? 'Kode Pos 68468, NPSN : 20526449, NSS : 101052502014, NIS : 100380, sdn2krsr@gmail.com' }}
                        </p>
                    </td>
                    <td class="w-[15%] text-right border-none align-middle">
                        @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                            <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" class="max-h-[60px] max-w-[60px] object-contain ml-auto">
                        @endif
                    </td>
                </tr>
            </table>

            <!-- JUDUL LAPORAN HASIL BELAJAR -->
            <div class="text-center mb-2.5">
                <h1 class="text-[11pt] font-black uppercase tracking-wide m-0">LAPORAN HASIL BELAJAR PESERTA DIDIK</h1>
                <p class="text-[10pt] font-bold uppercase tracking-wider m-0 mt-0.5">
                    {{ $periode === 'sas' ? 'SUMATIF AKHIR SEMESTER (SAS)' : 'SUMATIF TENGAH SEMESTER (STS)' }}
                </p>
            </div>

            <!-- IDENTITAS PESERTA DIDIK -->
            <table class="w-full mb-2.5 text-[8.5pt] border-none">
                <tr>
                    <td class="w-[18%] py-0.5 border-none align-top">Nama Peserta Didik</td>
                    <td class="w-[35%] py-0.5 border-none align-top font-bold">: {{ $siswa->nama }}</td>
                    <td class="w-[18%] py-0.5 border-none align-top">Kelas / Fase</td>
                    <td class="w-[29%] py-0.5 border-none align-top">: {{ $kelas->nama }} / {{ $kelas->fase ?? 'Fase A' }}</td>
                </tr>
                <tr>
                    <td class="py-0.5 border-none align-top">NIS / NISN</td>
                    <td class="py-0.5 border-none align-top">: {{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</td>
                    <td class="py-0.5 border-none align-top">Semester / Tahun</td>
                    <td class="py-0.5 border-none align-top">: {{ ucfirst($tahun->semester ?? 'Ganjil') }} / {{ $tahun->tahun ?? '2026/2027' }}</td>
                </tr>
            </table>

            <!-- TABEL NILAI MATA PELAJARAN -->
            <table class="w-full border-collapse border border-black mb-3 text-[8.5pt]">
                <thead>
                    <tr class="bg-white text-black font-bold text-center">
                        <th class="border border-black py-1 px-1.5 w-[5%]">No</th>
                        <th class="border border-black py-1 px-2.5 text-left w-[48%]">Mata Pelajaran</th>
                        <th class="border border-black py-1 px-1.5 w-[14%]">KKM</th>
                        <th class="border border-black py-1 px-1.5 w-[15%]">Nilai</th>
                        <th class="border border-black py-1 px-1.5 w-[18%]">Keterangan</th>
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
                            <td class="border border-black py-1 px-1 text-center">{{ $b['no'] }}</td>
                            <td class="border border-black py-1 px-2.5 text-left">{{ $b['mapel_nama'] }}</td>
                            <td class="border border-black py-1 px-1.5 text-center font-bold">{{ $kkmVal }}</td>
                            <td class="border border-black py-1 px-1.5 text-center font-bold">{{ $displayNilai }}</td>
                            <td class="border border-black py-1 px-1.5 text-center">{{ $ket }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold">
                        <td colspan="2" class="border border-black py-1 px-3 text-right uppercase">JUMLAH NILAI</td>
                        <td class="border border-black py-1 px-1.5 text-center">-</td>
                        <td class="border border-black py-1 px-1.5 text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($totalNilai) }}</td>
                        <td class="border border-black py-1 px-1.5 text-center">-</td>
                    </tr>
                    <tr class="font-bold">
                        <td colspan="2" class="border border-black py-1 px-3 text-right uppercase">RATA-RATA NILAI</td>
                        <td class="border border-black py-1 px-1.5 text-center">-</td>
                        <td class="border border-black py-1 px-1.5 text-center font-bold">{{ \App\Services\HitungNilaiService::formatAngka($rataNilai) }}</td>
                        <td class="border border-black py-1 px-1.5 text-center">-</td>
                    </tr>
                </tfoot>
            </table>

            <!-- TABEL PERKEMBANGAN KARAKTER & KETIDAKHADIRAN -->
            <div class="text-[8.5pt] font-bold uppercase tracking-wide mb-1">
                PERKEMBANGAN KARAKTER &amp; KETIDAKHADIRAN
            </div>
            <table class="w-full border-collapse border border-black mb-2.5 text-[8.5pt]">
                <thead>
                    <tr class="bg-white text-black font-bold text-center">
                        <th class="border border-black py-1 px-2.5 text-left w-[58%]">Aspek Pembiasaan / Karakter</th>
                        <th class="border border-black py-1 px-1.5 w-[18%]">Predikat</th>
                        <th class="border border-black py-1 px-2 w-[24%]">Ketidakhadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pembiasaanList as $pIdx => $pem)
                        @php
                            $pemVal = $nilaiPemList->get($pem->id);
                            $predikat = $pemVal ? $pemVal->predikat : 'Baik';
                        @endphp
                        <tr>
                            <td class="border border-black py-0.5 px-2.5 text-left">{{ $pIdx + 1 }}. {{ $pem->nama }}</td>
                            <td class="border border-black py-0.5 px-1.5 text-center font-bold">{{ $predikat }}</td>
                            @if($pIdx === 0)
                                <td rowspan="{{ $pembiasaanList->count() }}" class="border border-black py-1.5 px-2.5 align-top text-[8.5pt] leading-relaxed">
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
            <table class="w-full text-center text-[8.5pt] mt-3 border-none">
                <tr>
                    <td class="w-[32%] border-none align-top">
                        Mengetahui,<br>
                        Orang Tua / Wali Murid
                        <div class="h-12"></div>
                        <b>( ........................................ )</b>
                    </td>
                    <td class="w-[36%] border-none align-top">
                        <br>
                        Kepala Sekolah,
                        <div class="h-12 flex items-center justify-center">
                            @if($sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                                <img src="{{ asset('storage/' . $sekolah->ttd) }}" class="max-h-11 max-w-[120px] object-contain mx-auto">
                            @endif
                        </div>
                        <b>{{ $sekolah->kepsek ?? 'Prastono Santoso, S.Pd' }}</b><br>
                        <span class="text-[8pt]">NIP. {{ $sekolah->nip ?? '196908222014071001' }}</span>
                    </td>
                    <td class="w-[32%] border-none align-top">
                        {{ $sekolah->kota ?? 'Banyuwangi' }}, {{ (isset($tanggalObj) ? $tanggalObj : \Carbon\Carbon::now()->locale('id'))->translatedFormat('d F Y') }}<br>
                        Wali Kelas,
                        <div class="h-12"></div>
                        <b>{{ $kelas->nama_wali }}</b><br>
                        <span class="text-[8pt]">{{ ($kelas->nip_wali && $kelas->nip_wali !== '-') ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

</body>
</html>
