@extends('layouts.app')

@php
    $namaTipe = match ($tipe) {
        'agama' => 'Pendidikan Agama & Budi Pekerti',
        'pjok' => 'PJOK (Pendidikan Jasmani, Olahraga, dan Kesehatan)',
        default => 'Wali Kelas (Presensi Harian Rombel)',
    };

    $badgeTipeColor = match ($tipe) {
        'agama' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'pjok' => 'bg-sky-100 text-sky-800 border-sky-300',
        default => 'bg-amber-100 text-amber-800 border-amber-300',
    };

    $namaGuru = match ($tipe) {
        'agama' => $guruPengampu?->nama ?: ($guruPengampu?->user?->name ?? 'Guru Pendidikan Agama'),
        'pjok' => $guruPengampu?->nama ?: ($guruPengampu?->user?->name ?? 'Guru PJOK'),
        default => $kelas->nama_wali,
    };
@endphp

@section('title', "Absensi Harian - Kelas {$kelas->nama} ({$namaTipe})")

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'harian' }">

    <!-- Header Breadcrumb & Control Bar -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Left Info -->
            <div class="flex items-center space-x-3.5">
                <a href="{{ route('absensi-harian.index', ['tanggal' => $tanggal]) }}"
                   class="w-10 h-10 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition shadow-2xs shrink-0"
                   title="Kembali ke Pusat Absensi Harian">
                    <i class="fa-solid fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-xl font-black text-slate-900 tracking-tight">
                            Absensi Harian Kelas {{ $kelas->nama }}
                        </h1>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeTipeColor }}">
                            {{ $namaTipe }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-2 flex-wrap">
                        <span><i class="fa-solid fa-user-tie text-slate-400 mr-1"></i> Pengampu: <strong>{{ $namaGuru }}</strong></span>
                        <span class="text-slate-300">•</span>
                        <span><i class="fa-solid fa-users text-slate-400 mr-1"></i> Total: <strong>{{ $kelasSiswaList->count() }} Siswa</strong></span>
                        <span class="text-slate-300">•</span>
                        <span><i class="fa-solid fa-graduation-cap text-slate-400 mr-1"></i> {{ $kelas->tahunAjaran->nama ?? 'Tahun Ajaran Aktif' }}</span>
                    </p>
                </div>
            </div>

            <!-- Date Picker & Quick Navigator -->
            <form method="GET" action="{{ route('kelas.absensi-harian.index', $kelas->id) }}" class="flex items-center gap-2 flex-wrap">
                <input type="hidden" name="tipe" value="{{ $tipe }}">
                <label for="tanggal" class="text-xs font-bold text-slate-600 shrink-0">
                    <i class="fa-regular fa-calendar mr-1"></i> Tanggal:
                </label>
                <input type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}"
                    onchange="this.form.submit()"
                    class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500 focus:outline-none shadow-xs">

                @php
                    $yesterday = \Carbon\Carbon::parse($tanggal)->subDay()->format('Y-m-d');
                    $tomorrow = \Carbon\Carbon::parse($tanggal)->addDay()->format('Y-m-d');
                    $today = now()->format('Y-m-d');
                @endphp
                <div class="inline-flex rounded-xl shadow-xs overflow-hidden border border-slate-200">
                    <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $yesterday, 'tipe' => $tipe]) }}"
                       title="Kemarin" class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-600 text-xs border-r border-slate-200">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $today, 'tipe' => $tipe]) }}"
                       title="Hari Ini" class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-700 text-xs font-bold border-r border-slate-200">
                        Hari Ini
                    </a>
                    <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $tomorrow, 'tipe' => $tipe]) }}"
                       title="Besok" class="px-2.5 py-2 bg-white hover:bg-slate-100 text-slate-600 text-xs">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Role / Type Switcher Pills -->
        @php
            $canWali = auth()->user()->canInputAbsensi($kelas, 'wali');
            $canAgama = auth()->user()->canInputAbsensi($kelas, 'agama');
            $canPjok = auth()->user()->canInputAbsensi($kelas, 'pjok');
            $canEditCurrent = auth()->user()->canInputAbsensi($kelas, $tipe);
        @endphp
        <div class="flex items-center gap-2 border-t border-slate-100 pt-3 flex-wrap">
            <span class="text-xs font-bold text-slate-400 mr-2">Pilih Peran:</span>
            
            @if($canWali)
                <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'tipe' => 'wali']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $tipe === 'wali' ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Wali Kelas (Umum)</span>
                </a>
            @else
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200/80 cursor-not-allowed flex items-center gap-1.5 opacity-60"
                      title="Khusus Wali Kelas {{ $kelas->nama }} atau Admin">
                    <i class="fa-solid fa-lock text-[10px]"></i>
                    <span>Wali Kelas (Terkunci)</span>
                </span>
            @endif

            @if($canAgama)
                <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'tipe' => 'agama']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $tipe === 'agama' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-book-quran"></i>
                    <span>Pendidikan Agama</span>
                </a>
            @else
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200/80 cursor-not-allowed flex items-center gap-1.5 opacity-60"
                      title="Hanya untuk Guru Pengampu Agama di Kelas {{ $kelas->nama }}">
                    <i class="fa-solid fa-lock text-[10px]"></i>
                    <span>Agama (Terkunci)</span>
                </span>
            @endif

            @if($canPjok)
                <a href="{{ route('kelas.absensi-harian.index', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'tipe' => 'pjok']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $tipe === 'pjok' ? 'bg-sky-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-person-running"></i>
                    <span>PJOK</span>
                </a>
            @else
                <span class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200/80 cursor-not-allowed flex items-center gap-1.5 opacity-60"
                      title="Hanya untuk Guru Pengampu PJOK di Kelas {{ $kelas->nama }}">
                    <i class="fa-solid fa-lock text-[10px]"></i>
                    <span>PJOK (Terkunci)</span>
                </span>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <!-- Cetak Harian -->
                <a href="{{ route('kelas.absensi-harian.cetak', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'mode' => 'harian', 'tipe' => $tipe]) }}"
                    target="_blank" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-print text-slate-500"></i>
                    <span>Cetak Lembar Harian</span>
                </a>

                <!-- Cetak Rekap Bulanan -->
                <a href="{{ route('kelas.absensi-harian.cetak', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'mode' => 'bulanan', 'tipe' => $tipe]) }}"
                    target="_blank" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white border border-slate-200 hover:bg-slate-50 text-blue-700 transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-calendar-days text-blue-600"></i>
                    <span>Cetak Rekap Bulan (F4)</span>
                </a>
            </div>
        </div>
    </div>

    @if(! $canEditCurrent)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-center gap-3 shadow-2xs">
            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <p class="font-bold">Mode Hanya Lihat (Bukan Pengampu)</p>
                <p class="text-amber-700 mt-0.5">Anda tidak terdaftar sebagai guru pengampu untuk sesi <strong>{{ strtoupper($tipe) }}</strong> pada Kelas {{ $kelas->nama }}. Anda dapat melihat rekap dan mencetak lembar, namun formulir perubahan dinonaktifkan.</p>
            </div>
        </div>
    @endif

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Siswa</span>
            <span class="text-xl font-black text-slate-800">{{ $summary['total'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Rombel aktif</span>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-emerald-100 shadow-2xs">
            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider block">Hadir (H)</span>
            <span class="text-xl font-black text-emerald-600">{{ $summary['hadir'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">
                {{ $summary['total'] > 0 ? round(($summary['hadir'] / $summary['total']) * 100, 1) : 0 }}% hadir
            </span>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-amber-100 shadow-2xs">
            <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block">Sakit (S)</span>
            <span class="text-xl font-black text-amber-600">{{ $summary['sakit'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Surat / izin sakit</span>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-blue-100 shadow-2xs">
            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block">Izin (I)</span>
            <span class="text-xl font-black text-blue-600">{{ $summary['izin'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Izin terencana</span>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-rose-100 shadow-2xs">
            <span class="text-[10px] font-bold text-rose-600 uppercase tracking-wider block">Alpa (A)</span>
            <span class="text-xl font-black text-rose-600">{{ $summary['alpa'] }}</span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Tanpa keterangan</span>
        </div>

        <div class="bg-white rounded-2xl p-3.5 border border-slate-200 shadow-2xs">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Belum Diisi</span>
            <span class="text-xl font-black {{ $summary['belum_diisi'] > 0 ? 'text-amber-500' : 'text-slate-400' }}">
                {{ $summary['belum_diisi'] }}
            </span>
            <span class="text-[10px] text-slate-400 block mt-0.5">Status pending</span>
        </div>
    </div>

    <!-- Mode Tab Navigation (Input Harian vs Rekap Matriks Kalender) -->
    <div class="flex items-center justify-between border-b border-slate-200">
        <div class="flex items-center space-x-2">
            <button type="button" @click="activeTab = 'harian'"
                class="px-4 py-2.5 border-b-2 text-xs font-bold transition flex items-center gap-2"
                :class="activeTab === 'harian' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                <i class="fa-solid fa-list-check"></i>
                <span>Input Presensi Tanggal {{ $tanggalObj->translatedFormat('d M Y') }}</span>
            </button>

            <button type="button" @click="activeTab = 'kalender'"
                class="px-4 py-2.5 border-b-2 text-xs font-bold transition flex items-center gap-2"
                :class="activeTab === 'kalender' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Rekap Matriks Kalender ({{ $tanggalObj->translatedFormat('F Y') }})</span>
            </button>
        </div>

        @if($tipe === 'wali')
            <!-- Tombol Sinkronisasi ke Modul Absensi Bulanan (KehadiranBulanan & Rapor) -->
            <form action="{{ route('kelas.absensi-harian.sync-bulanan', $kelas->id) }}" method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin menyinkronkan data akumulasi absensi harian umum bulan {{ $tanggalObj->translatedFormat('F Y') }} ini ke Modul Kehadiran Bulanan & SIA Rapor? Data di Kehadiran Bulanan bulan ini akan diperbarui sesuai rekap harian.')">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">
                <button type="submit"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition shadow-2xs flex items-center gap-1.5"
                    title="Akumulasikan S, I, A dari absensi harian bulan ini ke KehadiranBulanan & SIA Rapor">
                    <i class="fa-solid fa-rotate text-indigo-600"></i>
                    <span>Sinkronkan ke Rekap SIA Rapor</span>
                </button>
            </form>
        @endif
    </div>

    <!-- TAB 1: FORM INPUT PRESENSI HARIAN -->
    <div x-show="activeTab === 'harian'" class="space-y-4">
        <form action="{{ route('kelas.absensi-harian.simpan', $kelas->id) }}" method="POST" id="formAbsensiHarian">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <input type="hidden" name="tipe" value="{{ $tipe }}">

            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <!-- Action Header Toolbar -->
                <div class="p-4 bg-slate-50/70 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="setSemuaHadir()"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-xs flex items-center gap-1.5">
                            <i class="fa-solid fa-check-double"></i>
                            <span>Semua Hadir (H)</span>
                        </button>
                        <span class="text-xs text-slate-500">Klik untuk menandai semua siswa hadir secara instan</span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($canEditCurrent)
                            @if($absenHariIni->isNotEmpty())
                                <button type="button" onclick="document.getElementById('modalKonfirmasiHapusAbsensi').classList.remove('hidden')"
                                    class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition flex items-center justify-center gap-1.5 shadow-2xs"
                                    title="Hapus atau kosongkan presensi tanggal {{ $tanggalObj->translatedFormat('d F Y') }} untuk {{ $namaTipe }}">
                                    <i class="fa-solid fa-trash-can text-rose-600"></i>
                                    <span>Hapus Tanggal Ini</span>
                                </button>
                            @endif
                            <button type="submit"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition shadow-md shadow-amber-500/20 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Absensi Harian</span>
                            </button>
                        @else
                            <button type="button" disabled
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-200 text-slate-400 cursor-not-allowed flex items-center justify-center gap-2">
                                <i class="fa-solid fa-lock"></i>
                                <span>Simpan Dinonaktifkan (Bukan Pengampu)</span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Tampilan Mobile Card List (Khusus Layar HP / Smartphone) -->
                <div class="block md:hidden divide-y divide-slate-100">
                    @forelse($kelasSiswaList as $index => $ks)
                        @php
                            $absen = $absenHariIni->get($ks->id);
                            $currentStatus = $absen ? $absen->status : 'H';
                            $currentKet = $absen ? $absen->keterangan : '';
                        @endphp
                        <!-- Hidden Inputs Utama untuk Siswa Ini (Disimpan ke Form) -->
                        <input type="hidden" name="status[{{ $ks->id }}]" id="status_input_{{ $ks->id }}" value="{{ $currentStatus }}" class="real-status-input" data-ks-id="{{ $ks->id }}">
                        <input type="hidden" name="keterangan[{{ $ks->id }}]" id="keterangan_input_{{ $ks->id }}" value="{{ $currentKet }}">

                        <div class="p-3.5 space-y-2.5 bg-white">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-xs text-slate-900 truncate">{{ $ks->siswa->nama }}</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">NISN: {{ $ks->siswa->nisn ?? '-' }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $ks->siswa->jenis_kelamin === 'L' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-pink-50 text-pink-700 border border-pink-200' }}">
                                    {{ $ks->siswa->jenis_kelamin ?? '-' }}
                                </span>
                            </div>

                            <!-- Tombol Pilihan H / S / I / A (Ergonomis untuk Jempol di HP) -->
                            <div class="grid grid-cols-4 gap-1.5 p-1 bg-slate-100 rounded-xl border border-slate-200/60 shadow-2xs">
                                <!-- Hadir (H) -->
                                <button type="button" onclick="pilihStatus({{ $ks->id }}, 'H')" id="btn_m_{{ $ks->id }}_H"
                                    class="w-full py-2.5 rounded-lg text-xs font-black transition-all duration-150 flex flex-col items-center justify-center {{ $currentStatus === 'H' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                    <span class="text-xs">H</span>
                                    <span class="text-[8px] font-normal sub-label {{ $currentStatus === 'H' ? 'text-emerald-100' : 'text-slate-400' }}">Hadir</span>
                                </button>

                                <!-- Sakit (S) -->
                                <button type="button" onclick="pilihStatus({{ $ks->id }}, 'S')" id="btn_m_{{ $ks->id }}_S"
                                    class="w-full py-2.5 rounded-lg text-xs font-black transition-all duration-150 flex flex-col items-center justify-center {{ $currentStatus === 'S' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                    <span class="text-xs">S</span>
                                    <span class="text-[8px] font-normal sub-label {{ $currentStatus === 'S' ? 'text-amber-100' : 'text-slate-400' }}">Sakit</span>
                                </button>

                                <!-- Izin (I) -->
                                <button type="button" onclick="pilihStatus({{ $ks->id }}, 'I')" id="btn_m_{{ $ks->id }}_I"
                                    class="w-full py-2.5 rounded-lg text-xs font-black transition-all duration-150 flex flex-col items-center justify-center {{ $currentStatus === 'I' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                    <span class="text-xs">I</span>
                                    <span class="text-[8px] font-normal sub-label {{ $currentStatus === 'I' ? 'text-blue-100' : 'text-slate-400' }}">Izin</span>
                                </button>

                                <!-- Alpa (A) -->
                                <button type="button" onclick="pilihStatus({{ $ks->id }}, 'A')" id="btn_m_{{ $ks->id }}_A"
                                    class="w-full py-2.5 rounded-lg text-xs font-black transition-all duration-150 flex flex-col items-center justify-center {{ $currentStatus === 'A' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                    <span class="text-xs">A</span>
                                    <span class="text-[8px] font-normal sub-label {{ $currentStatus === 'A' ? 'text-rose-100' : 'text-slate-400' }}">Alpa</span>
                                </button>
                            </div>

                            <!-- Input Keterangan Mobile -->
                            <div>
                                <input type="text" id="keterangan_m_{{ $ks->id }}" value="{{ $currentKet }}"
                                    oninput="syncKeterangan({{ $ks->id }}, this.value)"
                                    placeholder="{{ $tipe === 'pjok' ? 'cth: cedera ringan / batuk...' : ($tipe === 'agama' ? 'cth: baca iqro / hafalan...' : 'cth: surat dokter / izin...') }}"
                                    class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400">
                            Belum ada siswa yang ditempatkan pada rombel kelas ini.
                        </div>
                    @endforelse
                </div>

                <!-- Table Daftar Siswa (Khusus Desktop & Tablet) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100 text-slate-700 uppercase tracking-wider font-bold text-[10px] border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-3 w-12 text-center">No</th>
                                <th class="py-3 px-3 w-28">NISN</th>
                                <th class="py-3 px-4">Nama Siswa</th>
                                <th class="py-3 px-3 w-20 text-center">L/P</th>
                                <th class="py-3 px-4 w-72 text-center">Status Kehadiran</th>
                                <th class="py-3 px-4">
                                    @if($tipe === 'pjok')
                                        Keterangan Fisik / Olahraga
                                    @elseif($tipe === 'agama')
                                        Catatan Pembiasaan / Ibadah
                                    @else
                                        Keterangan (Alasan / Catatan)
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($kelasSiswaList as $index => $ks)
                                @php
                                    $absen = $absenHariIni->get($ks->id);
                                    $currentStatus = $absen ? $absen->status : 'H';
                                    $currentKet = $absen ? $absen->keterangan : '';
                                @endphp
                                <tr class="hover:bg-amber-50/40 transition">
                                    <td class="py-2.5 px-3 text-center text-slate-500 font-mono">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-slate-600">
                                        {{ $ks->siswa->nisn ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-4 font-bold text-slate-900">
                                        {{ $ks->siswa->nama }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold {{ $ks->siswa->jenis_kelamin === 'L' ? 'text-blue-600' : 'text-pink-600' }}">
                                        {{ $ks->siswa->jenis_kelamin ?? '-' }}
                                    </td>
                                    <!-- Radio Buttons H / S / I / A -->
                                    <td class="py-2.5 px-4 text-center">
                                    <!-- Tombol Pilihan H / S / I / A Desktop -->
                                    <td class="py-2.5 px-4 text-center">
                                        <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200 shadow-2xs gap-1">
                                            <!-- Hadir (H) -->
                                            <button type="button" onclick="pilihStatus({{ $ks->id }}, 'H')" id="btn_d_{{ $ks->id }}_H"
                                                class="px-2.5 py-1 rounded-lg text-xs font-black transition-all duration-150 {{ $currentStatus === 'H' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                                H
                                            </button>

                                            <!-- Sakit (S) -->
                                            <button type="button" onclick="pilihStatus({{ $ks->id }}, 'S')" id="btn_d_{{ $ks->id }}_S"
                                                class="px-2.5 py-1 rounded-lg text-xs font-black transition-all duration-150 {{ $currentStatus === 'S' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                                S
                                            </button>

                                            <!-- Izin (I) -->
                                            <button type="button" onclick="pilihStatus({{ $ks->id }}, 'I')" id="btn_d_{{ $ks->id }}_I"
                                                class="px-2.5 py-1 rounded-lg text-xs font-black transition-all duration-150 {{ $currentStatus === 'I' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                                I
                                            </button>

                                            <!-- Alpa (A) -->
                                            <button type="button" onclick="pilihStatus({{ $ks->id }}, 'A')" id="btn_d_{{ $ks->id }}_A"
                                                class="px-2.5 py-1 rounded-lg text-xs font-black transition-all duration-150 {{ $currentStatus === 'A' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                                                A
                                            </button>
                                        </div>
                                    </td>
                                    <!-- Input Keterangan Desktop -->
                                    <td class="py-2.5 px-4">
                                        <input type="text" id="keterangan_d_{{ $ks->id }}" value="{{ $currentKet }}"
                                            oninput="syncKeterangan({{ $ks->id }}, this.value)"
                                            placeholder="{{ $tipe === 'pjok' ? 'cth: cedera lutut / batuk pusing' : ($tipe === 'agama' ? 'cth: membaca iqra / hafal doa' : 'cth: surat dokter / izin urusan keluarga') }}"
                                            class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-amber-500 focus:outline-none transition">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400">
                                        Belum ada siswa yang ditempatkan pada rombel kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Simpan & Hapus -->
                <div class="p-4 bg-slate-50/70 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <span class="text-xs text-slate-500">
                        <i class="fa-solid fa-circle-info text-amber-500 mr-1"></i> Perubahan akan langsung tersimpan setelah tombol ditekan.
                    </span>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        @if($canEditCurrent)
                            @if($absenHariIni->isNotEmpty())
                                <button type="button" onclick="document.getElementById('modalKonfirmasiHapusAbsensi').classList.remove('hidden')"
                                    class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition flex items-center justify-center gap-1.5 shadow-2xs"
                                    title="Hapus seluruh data presensi tanggal ini">
                                    <i class="fa-solid fa-trash-can text-rose-600"></i>
                                    <span>Hapus Tanggal Ini</span>
                                </button>
                            @endif
                            <button type="submit"
                                class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition shadow-md shadow-amber-500/20 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Simpan Absensi Harian</span>
                            </button>
                        @else
                            <button type="button" disabled
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold bg-slate-200 text-slate-400 cursor-not-allowed flex items-center justify-center gap-2">
                                <i class="fa-solid fa-lock"></i>
                                <span>Simpan Dinonaktifkan (Bukan Pengampu)</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </form>

        <!-- Floating Quick Action Bar Khusus HP (Memudahkan Guru Menyimpan saat Scroll di HP) -->
        <div class="md:hidden fixed bottom-15 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-2.5 shadow-[0_-2px_15px_rgba(0,0,0,0.1)] flex items-center gap-2">
            @if($canEditCurrent)
                @if($absenHariIni->isNotEmpty())
                    <button type="button" onclick="document.getElementById('modalKonfirmasiHapusAbsensi').classList.remove('hidden')"
                        class="px-3 py-2.5 rounded-xl text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 transition flex items-center justify-center"
                        title="Hapus Presensi Tanggal Ini">
                        <i class="fa-solid fa-trash-can text-rose-600"></i>
                    </button>
                @endif
                <button type="button" onclick="setSemuaHadir()"
                    class="flex-1 py-2.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-check-double text-emerald-600"></i>
                    <span>Semua Hadir</span>
                </button>
                <button type="submit" form="formAbsensiHarian"
                    class="flex-1 py-2.5 rounded-xl text-xs font-black bg-amber-500 hover:bg-amber-600 text-white transition flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/25">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Absen</span>
                </button>
            @else
                <div class="flex-1 py-2 text-center text-xs font-bold text-slate-400 bg-slate-100 rounded-xl">
                    <i class="fa-solid fa-lock mr-1"></i> Mode Hanya Lihat
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: MATRIKS REKAP KALENDER BULANAN (TANGGAL 1 - 31) -->
    <div x-show="activeTab === 'kalender'" class="space-y-4" style="display: none;">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
            <!-- Header Rekap Bulanan -->
            <div class="p-4 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-blue-600"></i>
                        <span>Matriks Kehadiran Bulan {{ $tanggalObj->translatedFormat('F Y') }}</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan rekap visual harian tanggal 1 s.d. {{ $jumlahHariBulan }} {{ $tanggalObj->translatedFormat('F Y') }}.
                    </p>
                </div>

                <!-- Legend Keterangan Warna -->
                <div class="flex items-center gap-3 text-xs flex-wrap font-semibold">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-sm bg-emerald-500 inline-block"></span> H (Hadir)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-sm bg-amber-500 inline-block"></span> S (Sakit)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-sm bg-blue-500 inline-block"></span> I (Izin)
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-sm bg-rose-500 inline-block"></span> A (Alpa)
                    </span>
                </div>
            </div>

            <!-- Tabel Matrix Kalender -->
            <div class="overflow-x-auto">
                <table class="w-full text-[11px] text-center border-collapse">
                    <thead class="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-2 w-8 text-center border-r border-slate-200 sticky left-0 bg-slate-100 z-10">No</th>
                            <th class="py-2.5 px-3 text-left w-48 border-r border-slate-200 sticky left-8 bg-slate-100 z-10">Nama Siswa</th>
                            
                            <!-- Kolom Tanggal 1 s.d. Akhir Bulan -->
                            @for($d = 1; $d <= $jumlahHariBulan; $d++)
                                @php
                                    $colDate = \Carbon\Carbon::createFromDate($tahun, $bulan, $d);
                                    $isSunday = $colDate->isSunday();
                                    $isToday = $colDate->isToday();
                                @endphp
                                <th class="py-2 w-7 border-r border-slate-200 {{ $isSunday ? 'bg-rose-50 text-rose-600' : ($isToday ? 'bg-amber-100 text-amber-800 font-black' : '') }}"
                                    title="{{ $colDate->translatedFormat('l, d F Y') }}">
                                    {{ $d }}
                                </th>
                            @endfor

                            <!-- Kolom Rekapitulasi -->
                            <th class="py-2 px-1 w-8 bg-emerald-50 text-emerald-800 border-r border-slate-200" title="Total Hadir">H</th>
                            <th class="py-2 px-1 w-8 bg-amber-50 text-amber-800 border-r border-slate-200" title="Total Sakit">S</th>
                            <th class="py-2 px-1 w-8 bg-blue-50 text-blue-800 border-r border-slate-200" title="Total Izin">I</th>
                            <th class="py-2 px-1 w-8 bg-rose-50 text-rose-800 border-r border-slate-200" title="Total Alpa">A</th>
                            <th class="py-2 px-1.5 w-12 bg-slate-200 text-slate-800" title="Total Tidak Hadir (S+I+A)">% H</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($kelasSiswaList as $index => $ks)
                            @php
                                $byDay = $matriksBulan[$ks->id] ?? [];
                                $rekap = $rekapTotalPerSiswa[$ks->id] ?? ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'total_tidak_hadir' => 0];
                                $totalHariTercatat = $rekap['H'] + $rekap['S'] + $rekap['I'] + $rekap['A'];
                                $persenHadir = $totalHariTercatat > 0 ? round(($rekap['H'] / $totalHariTercatat) * 100) : 100;
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-2 px-1 text-center font-mono text-slate-500 border-r border-slate-200 sticky left-0 bg-white">
                                    {{ $index + 1 }}
                                </td>
                                <td class="py-2 px-3 text-left font-bold text-slate-900 border-r border-slate-200 sticky left-8 bg-white truncate max-w-xs">
                                    {{ $ks->siswa->nama }}
                                </td>

                                <!-- Grid Status Tiap Tanggal -->
                                @for($d = 1; $d <= $jumlahHariBulan; $d++)
                                    @php
                                        $colDate = \Carbon\Carbon::createFromDate($tahun, $bulan, $d);
                                        $isSunday = $colDate->isSunday();
                                        $cell = $byDay[$d] ?? null;
                                        $st = $cell ? $cell['status'] : null;
                                        $ket = $cell ? $cell['keterangan'] : null;

                                        $cellClass = match ($st) {
                                            'H' => 'bg-emerald-500 text-white font-black',
                                            'S' => 'bg-amber-500 text-white font-black',
                                            'I' => 'bg-blue-500 text-white font-black',
                                            'A' => 'bg-rose-500 text-white font-black',
                                            default => $isSunday ? 'bg-rose-50/50 text-rose-300' : 'text-slate-300',
                                        };
                                    @endphp
                                    <td class="py-1 px-0 border-r border-slate-200 {{ $cellClass }}"
                                        title="{{ $d }} {{ $tanggalObj->translatedFormat('F') }}: {{ $st ? ($st . ($ket ? " ({$ket})" : '')) : ($isSunday ? 'Hari Libur' : 'Belum diisi') }}">
                                        {{ $st ?? ($isSunday ? '•' : '-') }}
                                    </td>
                                @endfor

                                <!-- Kolom Total S, I, A -->
                                <td class="py-2 px-1 font-bold text-emerald-700 bg-emerald-50/40 border-r border-slate-200 font-mono">
                                    {{ $rekap['H'] }}
                                </td>
                                <td class="py-2 px-1 font-bold text-amber-700 bg-amber-50/40 border-r border-slate-200 font-mono">
                                    {{ $rekap['S'] }}
                                </td>
                                <td class="py-2 px-1 font-bold text-blue-700 bg-blue-50/40 border-r border-slate-200 font-mono">
                                    {{ $rekap['I'] }}
                                </td>
                                <td class="py-2 px-1 font-bold text-rose-700 bg-rose-50/40 border-r border-slate-200 font-mono">
                                    {{ $rekap['A'] }}
                                </td>
                                <td class="py-2 px-1 font-black text-slate-800 bg-slate-100 font-mono">
                                    {{ $persenHadir }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $jumlahHariBulan + 7 }}" class="py-8 text-center text-slate-400">
                                    Tidak ada data siswa pada rombel ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    <i class="fa-solid fa-print mr-1 text-slate-400"></i>
                    Format dokumen cetak resmi dapat diunduh melalui tombol <strong>Cetak Rekap Bulan (F4)</strong> di bagian atas.
                </span>
                <a href="{{ route('kelas.absensi-harian.cetak', ['kelas' => $kelas->id, 'tanggal' => $tanggal, 'mode' => 'bulanan', 'tipe' => $tipe]) }}"
                   target="_blank" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm flex items-center gap-1.5">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span>Buka Lembar Rekap Bulanan (F4)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Data Presensi Tanggal Ini -->
    @if($canEditCurrent && $absenHariIni->isNotEmpty())
        <div id="modalKonfirmasiHapusAbsensi" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100 animate-in fade-in duration-150">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 text-xl shadow-xs">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="space-y-1.5">
                        <h3 class="text-base font-bold text-slate-900">Hapus Presensi Tanggal Ini?</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Anda akan menghapus seluruh catatan presensi <strong>{{ $namaTipe }}</strong> pada Kelas <strong>{{ $kelas->nama }}</strong> untuk tanggal <strong>{{ $tanggalObj->translatedFormat('d F Y') }}</strong> (sebanyak {{ $absenHariIni->count() }} siswa).
                        </p>
                        <div class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-[11px] text-rose-700 leading-relaxed font-medium">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>
                            Status kehadiran pada tanggal ini akan dikosongkan (kembali <em>Belum Diisi</em>) dan tidak lagi dihitung dalam akumulasi rekap bulanan.
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalKonfirmasiHapusAbsensi').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Batal
                    </button>
                    <form action="{{ route('kelas.absensi-harian.hapus-tanggal', $kelas->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <input type="hidden" name="tipe" value="{{ $tipe }}">
                        <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition shadow-sm shadow-rose-600/30 flex items-center gap-1.5">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>Ya, Hapus Data Ini</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
    const STATUS_MAP = {
        'H': { bg: 'bg-emerald-600', text: 'text-white', sub: 'text-emerald-100', shadow: 'shadow-sm', shadowD: 'shadow-xs' },
        'S': { bg: 'bg-amber-500', text: 'text-white', sub: 'text-amber-100', shadow: 'shadow-sm', shadowD: 'shadow-xs' },
        'I': { bg: 'bg-blue-600', text: 'text-white', sub: 'text-blue-100', shadow: 'shadow-sm', shadowD: 'shadow-xs' },
        'A': { bg: 'bg-rose-600', text: 'text-white', sub: 'text-rose-100', shadow: 'shadow-sm', shadowD: 'shadow-xs' },
    };

    function pilihStatus(ksId, status) {
        const hiddenInp = document.getElementById('status_input_' + ksId);
        if (hiddenInp) {
            hiddenInp.value = status;
        }

        ['H', 'S', 'I', 'A'].forEach(s => {
            // Update Tombol Mobile
            const btnM = document.getElementById(`btn_m_${ksId}_${s}`);
            if (btnM) {
                btnM.classList.remove('bg-emerald-600', 'bg-amber-500', 'bg-blue-600', 'bg-rose-600', 'text-white', 'shadow-sm');
                const subSpan = btnM.querySelector('.sub-label');
                if (subSpan) subSpan.classList.remove('text-emerald-100', 'text-amber-100', 'text-blue-100', 'text-rose-100', 'text-slate-400');

                if (s === status) {
                    btnM.classList.add(STATUS_MAP[s].bg, STATUS_MAP[s].text, STATUS_MAP[s].shadow);
                    btnM.classList.remove('text-slate-600', 'hover:bg-slate-200/70');
                    if (subSpan) subSpan.classList.add(STATUS_MAP[s].sub);
                } else {
                    btnM.classList.add('text-slate-600', 'hover:bg-slate-200/70');
                    if (subSpan) subSpan.classList.add('text-slate-400');
                }
            }

            // Update Tombol Desktop
            const btnD = document.getElementById(`btn_d_${ksId}_${s}`);
            if (btnD) {
                btnD.classList.remove('bg-emerald-600', 'bg-amber-500', 'bg-blue-600', 'bg-rose-600', 'text-white', 'shadow-xs');
                if (s === status) {
                    btnD.classList.add(STATUS_MAP[s].bg, STATUS_MAP[s].text, STATUS_MAP[s].shadowD);
                    btnD.classList.remove('text-slate-600', 'hover:bg-slate-200/70');
                } else {
                    btnD.classList.add('text-slate-600', 'hover:bg-slate-200/70');
                }
            }
        });
    }

    function syncKeterangan(ksId, val) {
        const hiddenInp = document.getElementById('keterangan_input_' + ksId);
        if (hiddenInp) {
            hiddenInp.value = val;
        }
        const inpM = document.getElementById('keterangan_m_' + ksId);
        if (inpM && inpM !== document.activeElement) {
            inpM.value = val;
        }
        const inpD = document.getElementById('keterangan_d_' + ksId);
        if (inpD && inpD !== document.activeElement) {
            inpD.value = val;
        }
    }

    function setSemuaHadir() {
        document.querySelectorAll('.real-status-input').forEach(inp => {
            const ksId = inp.getAttribute('data-ks-id');
            if (ksId) {
                pilihStatus(ksId, 'H');
            }
        });
    }
</script>
@endpush
@endsection
