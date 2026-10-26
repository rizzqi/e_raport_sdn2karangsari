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

            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak Dokumen (F4)
            </button>
            <a id="btnDownloadDocx" href="{{ route('raport.download.docx', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
                <i class="fa-solid fa-file-word mr-1.5"></i> Unduh Word F4 (.docx)
            </a>
            <a id="btnDownloadPdf" href="{{ route('raport.download.pdf', ['kelasSiswa' => $kelasSiswa->id, 'periode' => $periode, 'tanggal' => $tanggalObj->format('Y-m-d')]) }}"
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
                        <th class="border border-slate-900 py-2 px-3 w-20">KKM</th>
                        <th class="border border-slate-900 py-2 px-3 w-24">Nilai</th>
                        <th class="border border-slate-900 py-2 px-3 w-28">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hasilNilai['baris'] as $b)
                        <tr>
                            <td class="border border-slate-900 py-1.5 px-2 text-center">{{ $b['no'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 font-medium">{{ $b['mapel_nama'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['kkm'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center font-bold">{{ $b['akhir_format'] }}</td>
                            <td class="border border-slate-900 py-1.5 px-3 text-center text-xs {{ $b['keterangan'] === 'Tuntas' ? 'text-emerald-700 font-bold' : ($b['keterangan'] === 'Belum Tuntas' ? 'text-rose-600 font-bold' : 'text-slate-400') }}">
                                {{ $b['keterangan'] }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="font-bold bg-slate-50">
                    <tr>
                        <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Jumlah Nilai</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ \App\Services\HitungNilaiService::formatAngka($hasilNilai['total_akhir']) }}</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                    </tr>
                    <tr>
                        <td colspan="2" class="border border-slate-900 py-1.5 px-3 text-right uppercase">Rata-rata Nilai</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center">{{ $hasilNilai['rata_akhir_format'] }}</td>
                        <td class="border border-slate-900 py-1.5 px-3 text-center text-slate-400">-</td>
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
                <div class="h-20 flex items-center justify-center">
                    @if($sekolah->ttd && file_exists(public_path('storage/' . $sekolah->ttd)))
                        <img src="{{ asset('storage/' . $sekolah->ttd) }}" alt="Tanda Tangan Kepala Sekolah" class="max-h-20 max-w-[150px] object-contain">
                    @endif
                </div>
                <p class="font-bold underline">{{ $sekolah->kepsek ?? '( ........................................ )' }}</p>
                <p class="text-[11px] text-slate-600">NIP. {{ $sekolah->nip ?? '-' }}</p>
            </div>

            <div>
                <p>{{ $sekolah->kota ?? 'Banyuwangi' }}, <span id="labelTanggalRaport" class="label-tanggal-raport">{{ $tanggalObj->translatedFormat('d F Y') }}</span></p>
                <div class="flex items-center justify-between">
                    <p class="font-medium">Wali Kelas,</p>
                    <div class="flex items-center space-x-1 no-print">
                        <button type="button" onclick="focusGantiTanggal()"
                            class="text-[10px] font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 px-2 py-0.5 rounded border border-blue-200 transition"
                            title="Ganti Tanggal Cetak Raport">
                            <i class="fa-regular fa-calendar-days mr-1 text-blue-600"></i> Ganti Tgl
                        </button>
                        @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                            <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.remove('hidden')"
                                class="text-[10px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded border border-amber-200 transition"
                                title="Edit Nama / NIP Wali Kelas jika ada salah ketik">
                                <i class="fa-solid fa-pen-to-square mr-1 text-amber-600"></i> Edit Wali
                            </button>
                        @endif
                    </div>
                </div>
                <div class="h-16"></div>
                <p class="font-bold underline">{{ $kelas->nama_wali }}</p>
                <p class="text-[11px] text-slate-600">{{ $kelas->nip_wali !== '-' ? 'NIP. ' . $kelas->nip_wali : 'NIP. -' }}</p>
            </div>
        </div>

    </div>

    <!-- Modal Edit Nama dan NIP Wali Kelas -->
    <div id="modalEditWaliKelas" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden no-print">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100 text-left">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                        <i class="fa-solid fa-user-pen text-amber-600 mr-2 text-base"></i> Edit Wali Kelas & NIP
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $kelas->nama }} &bull; TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }}</p>
                </div>
                <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
            </div>

            <form action="{{ route('kelas.update.wali', $kelas->id) }}" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nama Lengkap & Gelar Wali Kelas *
                    </label>
                    <input type="text" name="wali_nama" value="{{ old('wali_nama', $kelas->wali_nama ?: ($kelas->wali->name ?? '')) }}" required
                        placeholder="Contoh: Dewi Lestari, S.Pd."
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Nama ini akan dicetak pada tanda tangan raport.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                        Nomor Induk Pegawai (NIP)
                    </label>
                    <input type="text" name="wali_nip" value="{{ old('wali_nip', $kelas->wali_nip) }}"
                        placeholder="Contoh: 19880315 201101 2 008"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-500">
                    <span class="text-[11px] text-slate-500 mt-1 block">Kosongkan atau beri tanda strip (-) jika belum memiliki NIP.</span>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditWaliKelas').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm flex items-center transition">
                        <i class="fa-solid fa-check mr-1.5"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Manajemen Ganti Tanggal Raport Real-time & Auto-Save -->
    <script>
        const inputTanggal = document.getElementById('inputTanggalRaport');
        const badgeStatus = document.getElementById('badgeStatusTanggal');
        const btnDocx = document.getElementById('btnDownloadDocx');
        const btnPdf = document.getElementById('btnDownloadPdf');

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

        function focusGantiTanggal() {
            if (inputTanggal) {
                inputTanggal.focus();
                if (typeof inputTanggal.showPicker === 'function') {
                    inputTanggal.showPicker();
                }
            }
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
            inputTanggal.addEventListener('change', () => {
                onTanggalChanged();
                @if(auth()->user()->isAdmin() || (auth()->user()->isWali() && auth()->user()->id === $kelas->wali_id))
                    simpanTanggalRaport();
                @endif
            });
        }
    </script>
</div>
@endsection
