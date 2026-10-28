@extends('layouts.app')

@section('title', 'Cetak Raport Sekelas - ' . $kelas->nama)

@section('content')
<style>
    @media print {
        @page {
            size: 215mm 330mm portrait;
            margin: 10mm 15mm;
        }
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            background: white !important;
        }
        .no-print {
            display: none !important;
        }
        .sheet-raport {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            page-break-after: always !important;
            break-after: page !important;
        }
        .sheet-raport:last-child {
            page-break-after: avoid !important;
            break-after: avoid !important;
        }
    }
</style>

@php
    $siswaBelumLengkap = [];
    foreach ($laporanSiswa as $item) {
        if ($item['hasilNilai']['ada_nilai_kosong']) {
            $siswaBelumLengkap[] = [
                'nama' => $item['siswa']->nama,
                'mapel' => $item['hasilNilai']['mapel_kosong'],
            ];
        }
    }
@endphp

<div class="space-y-6">

    <!-- Action Toolbar (No-Print) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-3 no-print">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('kelas.show', $kelas->id) }}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Kelas
            </a>
            <div class="bg-slate-100 p-1 rounded-xl flex items-center space-x-1 text-xs font-bold">
                <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sts']) }}"
                   class="px-3 py-1 rounded-lg transition {{ $periode === 'sts' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                   STS
                </a>
                <a href="{{ route('raport.cetak.kelas', ['kelas' => $kelas->id, 'periode' => 'sas']) }}"
                   class="px-3 py-1 rounded-lg transition {{ $periode === 'sas' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                   SAS
                </a>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-users mr-1.5 text-emerald-600"></i> {{ count($laporanSiswa) }} Siswa
            </span>
            <span class="hidden lg:inline-flex items-center px-2.5 py-1 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs font-semibold">
                <i class="fa-solid fa-ruler-combined mr-1 text-amber-600"></i> Format F4 (215 × 330 mm)
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Lompat ke Siswa Dropdown -->
            <select onchange="if(this.value) { document.getElementById(this.value).scrollIntoView({behavior: 'smooth'}); }"
                class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">-- Lompat ke Siswa --</option>
                @foreach($laporanSiswa as $idx => $item)
                    <option value="sheet-siswa-{{ $item['siswa']->id }}">{{ $idx + 1 }}. {{ $item['siswa']->nama }}</option>
                @endforeach
            </select>

            <!-- Ganti Tanggal Raport (No-Print) -->
            <div class="flex items-center space-x-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded-xl text-xs shadow-2xs">
                <i class="fa-regular fa-calendar-days text-slate-500"></i>
                <label for="inputTanggalRaport" class="font-bold text-slate-700 whitespace-nowrap">Tgl Rapor:</label>
                <input type="date" id="inputTanggalRaport" value="{{ $tanggalObj->format('Y-m-d') }}"
                       class="bg-white border border-slate-300 rounded-lg px-2 py-0.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-2xs">
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    <button type="button" onclick="simpanTanggalRaport()"
                            class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition flex items-center shadow-2xs"
                            title="Simpan tanggal ini ke sistem agar sewaktu print berikutnya tanggalnya tetap sama">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Simpan
                    </button>
                @endif
                <span id="badgeStatusTanggal" class="hidden text-[10px] font-bold text-emerald-600 ml-1"></span>
            </div>

            <!-- Tombol Cetak Semua Sekelas -->
            <button type="button" onclick="cetakSemuaSekelas({{ count($siswaBelumLengkap) }})"
                    class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5 text-amber-400"></i> Cetak Semua (Ctrl+P)
            </button>
            <a id="btnDownloadKelasPdf" href="{{ route('raport.download.kelas.pdf', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
               class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> Unduh PDF Sekelas
            </a>
            <a id="btnDownloadKelasDocx" href="{{ route('raport.download.kelas.docx', ['kelas' => $kelas->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-file-word mr-1.5"></i> Unduh Word Sekelas
            </a>
        </div>
    </div>

    <!-- Peringatan Nilai Kosong pada Sekelas (No-Print) -->
    @if(count($siswaBelumLengkap) > 0)
        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-2xl text-amber-900 shadow-sm no-print flex items-start space-x-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xl mt-0.5 shrink-0"></i>
            <div class="flex-1 text-xs">
                <p class="font-bold text-sm text-amber-950">Peringatan: Terdapat {{ count($siswaBelumLengkap) }} Siswa dengan Nilai Belum Lengkap!</p>
                <p class="mt-1">
                    Sebelum mencetak rapor sekelas, pastikan seluruh nilai siswa telah lengkap di menu <strong>Input Nilai</strong>:
                </p>
                <div class="mt-2 max-h-28 overflow-y-auto space-y-1 bg-amber-100/50 p-2.5 rounded-xl">
                    @foreach($siswaBelumLengkap as $s)
                        <div class="flex items-start justify-between">
                            <span class="font-bold text-slate-900">&bull; {{ $s['nama'] }}</span>
                            <span class="text-amber-800 font-mono text-[11px]">{{ implode(', ', $s['mapel']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Container Seluruh Lembar Raport Siswa -->
    <div id="semua-lembar-raport" class="space-y-12 print:space-y-0">
        @foreach($laporanSiswa as $item)
            @php
                $ks = $item['kelasSiswa'];
                $siswa = $item['siswa'];
                $hasilNilai = $item['hasilNilai'];
                $nilaiPemList = $item['nilaiPemList'];
                $kehadiran = $item['kehadiran'];
            @endphp

            <!-- Lembar Raport Tiap Siswa (1 Siswa = 1 Halaman F4) -->
            <div id="sheet-siswa-{{ $siswa->id }}"
                 class="sheet-raport page-sheet max-w-[850px] mx-auto bg-white p-8 sm:p-12 rounded-2xl border border-slate-200 shadow-xl print:shadow-none print:border-none print:p-0 print:m-0 text-slate-900">

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

                <!-- 4. TABEL NILAI RAPOR (RINGKAS & RESMI: MATA PELAJARAN, KKM, NILAI RAPOR, PREDIKAT, CAPAIAN KOMPETENSI) -->
                <div class="mb-5">
                    <table class="w-full text-xs sm:text-sm border-collapse border border-slate-900">
                        <thead>
                            <tr class="bg-slate-100 text-slate-900 font-bold text-center">
                                <th class="border border-slate-900 py-2 px-2 w-10">No</th>
                                <th class="border border-slate-900 py-2 px-3 text-left">Mata Pelajaran</th>
                                <th class="border border-slate-900 py-2 px-2 w-16">KKM</th>
                                <th class="border border-slate-900 py-2 px-2 w-24">Nilai Rapor</th>
                                <th class="border border-slate-900 py-2 px-2 w-16">Predikat</th>
                                <th class="border border-slate-900 py-2 px-3 text-left">Capaian Kompetensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hasilNilai['baris'] as $b)
                                <tr>
                                    <td class="border border-slate-900 py-2 px-2 text-center align-top">{{ $b['no'] }}</td>
                                    <td class="border border-slate-900 py-2 px-3 font-semibold align-top">{{ $b['mapel_nama'] }}</td>
                                    <td class="border border-slate-900 py-2 px-2 text-center font-bold align-top">{{ $b['kkm'] }}</td>
                                    <td class="border border-slate-900 py-2 px-2 text-center font-black text-sm align-top {{ $b['is_kosong'] ? 'text-rose-600 bg-rose-50/50' : '' }}">
                                        {{ $b['nilai_rapor_format'] }}
                                    </td>
                                    <td class="border border-slate-900 py-2 px-2 text-center font-bold align-top">
                                        {{ $b['predikat'] }}
                                    </td>
                                    <td class="border border-slate-900 py-2 px-3 text-xs leading-relaxed align-top">
                                        {{ $b['capaian_kompetensi'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="font-bold bg-slate-50">
                            <tr>
                                <td colspan="3" class="border border-slate-900 py-2 px-3 text-right uppercase">Jumlah Nilai</td>
                                <td class="border border-slate-900 py-2 px-2 text-center font-mono font-black">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_akhir']) }}</td>
                                <td class="border border-slate-900 py-2 px-2 text-center font-mono font-black">{{ $hasilNilai['predikat_rata'] ?? '-' }}</td>
                                <td class="border border-slate-900 py-2 px-3 text-xs text-slate-500 font-normal">
                                    Rata-rata: <strong>{{ $hasilNilai['rata_akhir_format'] }}</strong> &bull; Predikat Keseluruhan: <strong>{{ $hasilNilai['predikat_rata'] ?? '-' }}</strong>
                                </td>
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

                <!-- 6. BLOK TANDA TANGAN RESMI (3 PIHAK: ORANG TUA, KEPALA SEKOLAH, WALI KELAS) -->
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
                        <div class="h-20 flex items-center justify-center">
                            @if($sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                                <img src="{{ asset('storage/' . $sekolah->ttd) }}" alt="Tanda Tangan Kepala Sekolah" class="max-h-20 max-w-[150px] object-contain">
                            @endif
                        </div>
                        <p class="font-bold underline">{{ $sekolah->kepsek ?? '( ........................................ )' }}</p>
                        <p class="text-[11px] text-slate-600">NIP. {{ $sekolah->nip ?? '-' }}</p>
                    </div>

                    <div>
                        <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, <span class="label-tanggal-raport">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                        <p class="font-medium">Wali Kelas,</p>
                        <div class="h-20"></div>
                        <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                        <p class="text-[11px] text-slate-600">{{ $kelas->nip_wali !== '-' ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</p>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    <!-- Script Manajemen Ganti Tanggal Raport Real-time & Auto-Save Sekelas -->
    <script>
        const inputTanggal = document.getElementById('inputTanggalRaport');
        const badgeStatus = document.getElementById('badgeStatusTanggal');
        const btnDocx = document.getElementById('btnDownloadKelasDocx');
        const btnPdf = document.getElementById('btnDownloadKelasPdf');

        // Fungsi Cetak dengan Pengaman Nilai Kosong Sekelas
        function cetakSemuaSekelas(jumlahSiswaKosong) {
            if (jumlahSiswaKosong > 0) {
                if (!confirm(`PERINGATAN NILAI KOSONG:\nTerdapat ${jumlahSiswaKosong} siswa di kelas ini yang nilainya masih belum lengkap.\n\nApakah Anda yakin tetap ingin mencetak seluruh rapor sekelas sekarang?`)) {
                    return;
                }
            }
            window.print();
        }

        function formatTanggalIndo(dateStr) {
            if (!dateStr) return '';
            const parts = dateStr.split('-');
            if (parts.length !== 3) return dateStr;
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const day = parseInt(parts[2], 10);
            const bulanIndo = [
                '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return `${day} ${bulanIndo[month] || ''} ${year}`;
        }

        function updateDownloadUrls(val) {
            if (btnDocx) {
                const url = new URL(btnDocx.href, window.location.origin);
                url.searchParams.set('tanggal', val);
                btnDocx.href = url.pathname + url.search;
            }
            if (btnPdf) {
                const url = new URL(btnPdf.href, window.location.origin);
                url.searchParams.set('tanggal', val);
                btnPdf.href = url.pathname + url.search;
            }
        }

        function onTanggalChanged() {
            const val = inputTanggal.value;
            if (!val) return;
            const formatted = formatTanggalIndo(val);
            document.querySelectorAll('.label-tanggal-raport').forEach(el => {
                el.textContent = formatted;
            });
            updateDownloadUrls(val);
        }

        function simpanTanggalRaport() {
            const val = inputTanggal.value;
            if (!val) {
                alert('Silakan pilih tanggal terlebih dahulu.');
                return;
            }

            if (badgeStatus) {
                badgeStatus.textContent = 'Menyimpan...';
                badgeStatus.classList.remove('hidden', 'text-rose-600');
                badgeStatus.classList.add('text-slate-500');
            }

            fetch('{{ route("kelas.tanggal-raport.update", $kelas->id) }}', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    periode: '{{ $periode }}',
                    tanggal: val
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (badgeStatus) {
                        badgeStatus.textContent = '✓ Tersimpan';
                        badgeStatus.classList.remove('text-slate-500', 'text-rose-600');
                        badgeStatus.classList.add('text-emerald-600');
                        setTimeout(() => {
                            badgeStatus.classList.add('hidden');
                        }, 3500);
                    }
                } else {
                    throw new Error(data.message || 'Gagal menyimpan');
                }
            })
            .catch(err => {
                if (badgeStatus) {
                    badgeStatus.textContent = 'Gagal menyimpan';
                    badgeStatus.classList.remove('text-slate-500', 'text-emerald-600');
                    badgeStatus.classList.add('text-rose-600');
                }
            });
        }

        if (inputTanggal) {
            inputTanggal.addEventListener('change', onTanggalChanged);
        }
    </script>
</div>
@endsection
