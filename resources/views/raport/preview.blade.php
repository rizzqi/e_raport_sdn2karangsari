@extends('layouts.app')

@section('title', 'Pratinjau Raport - ' . $siswa->nama)

@section('content')
<style>
    @media print {
        @page {
            size: 215mm 330mm;
            margin: 10mm 15mm;
        }
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .no-print {
            display: none !important;
        }
        .page-sheet {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="space-y-6">

    <!-- Action Toolbar (No-Print) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 no-print">
        <div class="flex items-center space-x-2">
            <a href="{{ route('kelas.show', $kelas->id) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Kelas
            </a>
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('raport.preview', ['kelasSiswa' => $kelasSiswa->id, 'periode' => 'sts']) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                   STS
                </a>
                <a href="{{ route('raport.preview', ['kelasSiswa' => $kelasSiswa->id, 'periode' => 'sas']) }}"
                   class="px-2.5 py-1 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                   SAS
                </a>
            </div>
            <span class="hidden md:inline-flex items-center px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-ruler-combined mr-1 text-amber-600"></i> F4 (215 × 330 mm)
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak Dokumen (F4)
            </button>
            <a href="{{ route('raport.download.docx', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode]) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-file-word mr-1.5"></i> Unduh Word F4 (.docx)
            </a>
            <a href="{{ route('raport.download.pdf', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode]) }}"
               class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> Unduh PDF F4
            </a>
        </div>
    </div>

    <!-- Official Report Card Sheet (Standard F4 / Folio: 215 x 330 mm) -->
    <div class="page-sheet max-w-[850px] mx-auto bg-white p-8 sm:p-12 rounded-2xl border border-slate-200 shadow-xl print:shadow-none print:border-none print:p-0 print:m-0 text-slate-900">

        <!-- 1. KOP SURAT RESMI STANDAR F4 -->
        <div class="pb-3 border-b-4 border-double border-slate-900">
            <div class="flex items-center justify-between gap-4">
                <!-- Logo Kiri -->
                <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center">
                    @if($sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo)))
                        <img src="{{ asset('storage/' . $sekolah->logo) }}" alt="Logo Pemda" class="max-h-20 max-w-20 object-contain">
                    @endif
                </div>

                <!-- Teks Kop Tengah -->
                <div class="flex-1 text-center font-serif text-slate-900 space-y-0.5">
                    <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight">
                        {{ $sekolah->pemda ?? 'PEMERINTAH KABUPATEN BANYUWANGI' }}
                    </h3>
                    <h3 class="text-xs sm:text-sm font-bold uppercase tracking-wider leading-tight">
                        {{ $sekolah->dinas ?? 'DINAS PENDIDIKAN' }}
                    </h3>
                    <h2 class="text-base sm:text-xl font-black uppercase text-slate-900 tracking-wider leading-tight">
                        {{ $sekolah->nama ?? 'SD NEGERI 2 KARANGSARI' }}
                    </h2>
                    <p class="text-[11px] sm:text-xs italic text-slate-600 leading-tight">
                        {{ $sekolah->alamat ?? 'Jl. Karangsari No. 12, Sempu, Banyuwangi' }}
                    </p>
                    @if($sekolah->kontak)
                        <p class="text-[10px] text-slate-500 leading-tight">
                            {{ $sekolah->kontak }}
                        </p>
                    @endif
                </div>

                <!-- Logo Kanan -->
                <div class="w-20 h-20 flex-shrink-0 flex items-center justify-center">
                    @if($sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan)))
                        <img src="{{ asset('storage/' . $sekolah->logo_kanan) }}" alt="Logo Tut Wuri / Sekolah" class="max-h-20 max-w-20 object-contain">
                    @endif
                </div>
            </div>
        </div>

        <!-- 2. JUDUL RAPOR -->
        <div class="text-center my-5">
            <h1 class="text-base sm:text-lg font-black uppercase tracking-wide underline underline-offset-4">
                LAPORAN HASIL BELAJAR PESERTA DIDIK
            </h1>
            <p class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-700 mt-1">
                {{ $periode === 'sts' ? 'SUMATIF TENGAH SEMESTER (STS)' : 'SUMATIF AKHIR SEMESTER (SAS)' }}
            </p>
        </div>

        <!-- 3. IDENTITAS PESERTA DIDIK -->
        <div class="grid grid-cols-2 gap-4 text-xs sm:text-sm mb-5 pb-3">
            <div class="space-y-1">
                <div class="flex">
                    <span class="w-36 text-slate-600">Nama Peserta Didik</span>
                    <span class="font-bold">: {{ $siswa->nama }}</span>
                </div>
                <div class="flex">
                    <span class="w-36 text-slate-600">NIS / NISN</span>
                    <span class="font-mono">: {{ $siswa->nis }} / {{ $siswa->nisn ?? '-' }}</span>
                </div>
            </div>
            <div class="space-y-1">
                <div class="flex">
                    <span class="w-32 text-slate-600">Kelas / Fase</span>
                    <span class="font-bold">: {{ $kelas->nama }} / {{ $kelas->fase ?? 'A' }}</span>
                </div>
                <div class="flex">
                    <span class="w-32 text-slate-600">Semester / Tahun</span>
                    <span class="font-bold">: {{ ucfirst($tahun->semester ?? 'Ganjil') }} / {{ $tahun->tahun ?? '2026/2027' }}</span>
                </div>
            </div>
        </div>

        <!-- 4. TABEL NILAI MATA PELAJARAN -->
        <div class="mb-5">
            <table class="w-full text-xs sm:text-sm border-collapse border border-slate-900">
                <thead>
                    <tr class="bg-slate-100 text-slate-900 font-bold text-center">
                        <th class="border border-slate-900 py-2 px-2 w-10">No</th>
                        <th class="border border-slate-900 py-2 px-3 text-left">Mata Pelajaran</th>
                        <th class="border border-slate-900 py-2 px-3 w-28">Nilai Formatif</th>
                        <th class="border border-slate-900 py-2 px-3 w-28">Nilai Sumatif</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hasilNilai['baris'] as $b)
                        <tr>
                            <td class="border border-slate-900 py-1.5 px-2 text-center">{{ $b['no'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 font-medium">{{ $b['mapel_nama'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['formatif_format'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['sumatif_format'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="font-bold bg-slate-50">
                    <tr>
                        <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Jumlah Nilai</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_formatif']) }}</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_sumatif']) }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Rata-rata Nilai</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ $hasilNilai['rata_formatif_format'] }}</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ $hasilNilai['rata_sumatif_format'] }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- 5. TABEL PEMBIASAAN DAN KEHADIRAN -->
        <div class="mb-8">
            <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider mb-2 text-slate-800">
                Perkembangan Karakter & Ketidakhadiran
            </h4>
            <table class="w-full text-xs sm:text-sm border-collapse border border-slate-900">
                <thead>
                    <tr class="bg-slate-100 font-bold text-center">
                        <th class="border border-slate-900 py-2 px-3 text-left w-3/5">Aspek Pembiasaan / Karakter</th>
                        <th class="border border-slate-900 py-2 px-3 w-1/5">Predikat</th>
                        <th class="border border-slate-900 py-2 px-3 w-1/5">Ketidakhadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pembiasaanList as $pIdx => $pem)
                        @php
                            $pemVal = $nilaiPemList->get($pem->id);
                            $predikat = $pemVal ? $pemVal->predikat : 'Baik';
                        @endphp
                        <tr>
                            <td class="border border-slate-900 py-1.5 px-3">
                                {{ $pIdx + 1 }}. {{ $pem->nama }}
                            </td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">
                                {{ $predikat }}
                            </td>
                            @if($pIdx === 0)
                                <td class="border border-slate-900 py-1.5 px-3 text-center align-top space-y-1" rowspan="{{ $pembiasaanList->count() }}">
                                    <div class="text-xs space-y-1 pt-1">
                                        <p>Sakit : <strong>{{ $kehadiran->sakit }}</strong> hari</p>
                                        <p>Izin : <strong>{{ $kehadiran->izin }}</strong> hari</p>
                                        <p>Tanpa Ket. : <strong>{{ $kehadiran->alpa }}</strong> hari</p>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- 6. BLOK TANDA TANGAN RESMI -->
        <div class="grid grid-cols-3 gap-2 text-center text-xs sm:text-sm pt-2">
            <div>
                <p>Mengetahui,</p>
                <p class="font-medium">Orang Tua / Wali Murid</p>
                <div class="h-20"></div>
                <p class="font-bold underline">( ........................................ )</p>
            </div>

            <div>
                <p>&nbsp;</p>
                <p class="font-medium">Kepala Sekolah,</p>
                <div class="h-20"></div>
                <p class="font-bold underline">{{ $sekolah->kepsek ?? '( ........................................ )' }}</p>
                <p class="text-[11px] text-slate-600">NIP. {{ $sekolah->nip ?? '-' }}</p>
            </div>

            <div>
                <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="font-medium">Wali Kelas,</p>
                <div class="h-20"></div>
                <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                <p class="text-[11px] text-slate-600">{{ $kelas->nip_wali !== '-' ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</p>
            </div>
        </div>

    </div>

</div>
@endsection
