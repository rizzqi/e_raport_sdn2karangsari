@extends('layouts.app')

@section('title', 'Kenaikan Kelas & Kelulusan Siswa')

@section('content')
<div class="space-y-6">

    <!-- Header Breadcrumb & Judul -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('kelas.index') }}" class="hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-chalkboard-user"></i> Kelas
                </a>
                <span>&bull;</span>
                <span class="text-slate-800 font-bold">Kenaikan Kelas & Kelulusan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 flex items-center gap-2">
                <span class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center shadow-md shadow-indigo-500/20 text-base shrink-0">
                    <i class="fa-solid fa-graduation-cap"></i>
                </span>
                <span>Kenaikan Kelas & Kelulusan Siswa</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola kenaikan kelas antar tahun ajaran, mutasi rombel siswa, dan penetapan status kelulusan peserta didik.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('kelas.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Daftar Kelas
            </a>
        </div>
    </div>

    <!-- Info Banner Prinsip Kurikulum Merdeka -->
    <div class="bg-gradient-to-r from-indigo-50 via-sky-50 to-emerald-50 border border-indigo-200/80 rounded-2xl p-4 text-xs text-indigo-950 flex items-start space-x-3 shadow-2xs">
        <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0 text-sm mt-0.5">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <div class="leading-relaxed">
            <p class="font-bold text-indigo-900">Prinsip Riwayat Nilai & Poros Relasi Kelas-Siswa</p>
            <p class="text-indigo-800/90 text-[11px] mt-0.5">
                Pada sistem E-Raport ini, seluruh data nilai formatif, sumatif, pembiasaan, kehadiran, dan cetak raport menempel pada relasi kelas siswa. Saat siswa <strong>Naik Kelas</strong> ke tahun ajaran baru, sistem akan mendaftarkan siswa ke kelas tujuan dengan tetap menjaga arsip nilai rapor di kelas lamanya secara utuh. Sedangkan untuk siswa tingkat akhir (Kelas 6) yang tamat belajar, gunakan tab <strong>Kelulusan Siswa</strong> untuk memperbarui status keaktifannya menjadi Lulus.
            </p>
        </div>
    </div>

    <!-- Navigasi Tab Segmented Control -->
    <div class="flex items-center border-b border-slate-200 bg-white px-3 pt-2 rounded-2xl shadow-xs">
        <a href="{{ route('kelas.mutasi.index', ['tab' => 'kenaikan', 'kelas_asal_id' => $kelasAsalId]) }}"
           class="flex items-center gap-2 px-5 py-3 text-xs font-bold border-b-2 transition {{ $activeTab === 'kenaikan' ? 'border-indigo-600 text-indigo-700 bg-indigo-50/40 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
            <i class="fa-solid fa-arrows-up-down text-sm"></i>
            <span>1. Kenaikan & Pindah Kelas</span>
        </a>
        <a href="{{ route('kelas.mutasi.index', ['tab' => 'kelulusan', 'kelas_lulus_id' => $kelasLulusId]) }}"
           class="flex items-center gap-2 px-5 py-3 text-xs font-bold border-b-2 transition {{ $activeTab === 'kelulusan' ? 'border-emerald-600 text-emerald-700 bg-emerald-50/40 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
            <i class="fa-solid fa-user-graduate text-sm"></i>
            <span>2. Proses Kelulusan Siswa</span>
        </a>
    </div>

    @if($activeTab === 'kenaikan')
        <!-- ========================================== -->
        <!-- TAB 1: KENAIKAN & PEMINDAHAN KELAS         -->
        <!-- ========================================== -->
        <div class="space-y-6">

            <!-- Pilih Kelas Asal Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <form action="{{ route('kelas.mutasi.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <input type="hidden" name="tab" value="kenaikan">
                    <label class="text-xs font-bold text-slate-700 shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-filter text-indigo-600"></i> Pilih Kelas Asal:
                    </label>
                    <select name="kelas_asal_id" onchange="this.form.submit()"
                            class="px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-slate-50 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition min-w-[220px]">
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasAsalId === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? 'TP -' }}) - {{ $k->kelas_siswa_count }} Siswa
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($kelasAsal)
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span class="px-2.5 py-1 bg-slate-100 rounded-lg font-semibold text-slate-700">
                            Wali: <strong>{{ $kelasAsal->nama_wali }}</strong>
                        </span>
                        <span class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-lg font-bold text-indigo-700">
                            Fase {{ $kelasAsal->fase ?? 'A' }}
                        </span>
                    </div>
                @endif
            </div>

            @if($kelasAsal)
                <form action="{{ route('kelas.mutasi.kenaikan') }}" method="POST" id="formKenaikanKelas">
                    @csrf
                    <input type="hidden" name="kelas_asal_id" value="{{ $kelasAsal->id }}">

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                        <!-- Kolom Kiri: Daftar Siswa Kelas Asal (7 Kolom) -->
                        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
                            <div>
                                <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                            <i class="fa-solid fa-users text-indigo-600"></i>
                                            <span>Siswa di {{ $kelasAsal->nama }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                                {{ $kelasAsal->kelasSiswa->count() }}
                                            </span>
                                        </h3>
                                        <p class="text-[11px] text-slate-400 mt-0.5">Centang siswa yang akan dinaikkan atau dipindahkan</p>
                                    </div>
                                    <!-- Input Pencarian Cepat Realtime -->
                                    <div class="relative w-full sm:w-56">
                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                        <input type="text" id="cariSiswaKenaikan" oninput="filterSiswaKenaikan(this.value)"
                                               placeholder="Cari nama / NIS..."
                                               class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white">
                                    </div>
                                </div>

                                <!-- Bar Aksi Seleksi Massal -->
                                <div class="px-4 py-2.5 bg-indigo-50/50 border-b border-indigo-100/70 flex items-center justify-between text-xs">
                                    <label class="flex items-center cursor-pointer font-bold text-slate-700 hover:text-indigo-900 select-none">
                                        <input type="checkbox" id="checkAllKenaikan" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 mr-2 w-4 h-4 cursor-pointer" onchange="toggleAllKenaikan(this)">
                                        <span>Pilih Semua Siswa</span>
                                    </label>
                                    <span id="labelTerpilihKenaikan" class="font-bold text-indigo-700 text-xs">
                                        0 siswa dipilih
                                    </span>
                                </div>

                                <!-- Daftar Siswa -->
                                <div class="divide-y divide-slate-100 max-h-[520px] overflow-y-auto" id="containerSiswaKenaikan">
                                    @forelse($kelasAsal->kelasSiswa as $index => $ks)
                                        <label class="item-siswa-kenaikan p-3.5 flex items-center justify-between hover:bg-indigo-50/30 cursor-pointer transition select-none block"
                                               data-nama="{{ strtolower($ks->siswa->nama) }}" data-nis="{{ $ks->siswa->nis }}">
                                            <div class="flex items-center space-x-3 min-w-0">
                                                <input type="checkbox" name="siswa_ids[]" value="{{ $ks->siswa->id }}"
                                                       class="cb-siswa-kenaikan rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                                                       onchange="updateCounterKenaikan()">
                                                <span class="text-xs font-bold text-slate-400 w-5 shrink-0">{{ $index + 1 }}</span>
                                                <div class="min-w-0">
                                                    <p class="font-bold text-xs text-slate-900 truncate">{{ $ks->siswa->nama }}</p>
                                                    <p class="text-[10px] text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }} &bull; NISN: {{ $ks->siswa->nisn ?: '-' }}</p>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold shrink-0 uppercase {{ $ks->siswa->status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                                {{ $ks->siswa->status }}
                                            </span>
                                        </label>
                                    @empty
                                        <div class="p-8 text-center text-slate-400 text-xs">
                                            <i class="fa-solid fa-users-slash text-2xl mb-1 text-slate-300 block"></i>
                                            Belum ada siswa yang terdaftar di kelas ini.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Pengaturan Kelas Tujuan & Mode (5 Kolom) -->
                        <div class="lg:col-span-5 space-y-5">
                            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-5">
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 pb-3 border-b border-slate-100">
                                    <i class="fa-solid fa-bullseye text-indigo-600"></i>
                                    <span>Tujuan Kenaikan / Pemindahan</span>
                                </h3>

                                <!-- Pilihan Kelas Tujuan -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                        Pilih Kelas Tujuan *
                                    </label>
                                    <select name="kelas_tujuan_id" required
                                            class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-indigo-500">
                                        <option value="">-- Pilih Kelas Tujuan --</option>
                                        @foreach($kelasList as $kt)
                                            @if($kt->id !== $kelasAsal->id)
                                                <option value="{{ $kt->id }}">
                                                    {{ $kt->nama }} ({{ $kt->tahunAjaran->nama_lengkap ?? '-' }}) &bull; Saat ini: {{ $kt->kelas_siswa_count }} Siswa
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400 mt-1">Pilih rombongan belajar tempat siswa akan didaftarkan.</p>
                                </div>

                                <!-- Pilihan Mode Kenaikan -->
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                                        Mode Operasi *
                                    </label>
                                    <div class="space-y-2.5">
                                        <label class="border-2 border-indigo-200 rounded-xl p-3 flex items-start space-x-3 cursor-pointer hover:bg-indigo-50/30 transition has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50">
                                            <input type="radio" name="mode" value="naik" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="block text-xs font-bold text-slate-900">
                                                    Naik Kelas (Tahun Ajaran Baru / Arsip Riwayat)
                                                    <span class="ml-1 text-[9px] uppercase px-1.5 py-0.5 rounded-sm bg-indigo-100 text-indigo-800 font-extrabold">Rekomendasi</span>
                                                </span>
                                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    Siswa didaftarkan ke kelas tujuan baru. Riwayat belajar & rapor di kelas lama <strong>tetap tersimpan utuh</strong> sebagai arsip nilai masa lalu siswa.
                                                </span>
                                            </div>
                                        </label>

                                        <label class="border border-slate-200 rounded-xl p-3 flex items-start space-x-3 cursor-pointer hover:bg-slate-50 transition has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50/40">
                                            <input type="radio" name="mode" value="pindah" class="mt-0.5 text-amber-600 focus:ring-amber-500">
                                            <div>
                                                <span class="block text-xs font-bold text-slate-900">
                                                    Pindah Rombel (Transfer Penuh)
                                                </span>
                                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                                    Siswa dipindahkan ke kelas tujuan dan <strong>dikeluarkan dari kelas asal</strong>. Digunakan untuk perbaikan rombel di semester yang sama.
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Tombol Submit Kenaikan -->
                                <div class="pt-3 border-t border-slate-100">
                                    <button type="submit" id="btnSubmitKenaikan"
                                            onclick="return konfirmasiKenaikan()"
                                            class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold rounded-xl text-xs shadow-md shadow-indigo-600/20 hover:shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-arrows-up-down"></i>
                                        <span id="btnTextKenaikan">Proses Kenaikan Kelas</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            @endif

        </div>
    @else
        <!-- ========================================== -->
        <!-- TAB 2: PROSES KELULUSAN SISWA              -->
        <!-- ========================================== -->
        <div class="space-y-6">

            <!-- Pilih Kelas Kelulusan Filter Bar -->
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <form action="{{ route('kelas.mutasi.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                    <input type="hidden" name="tab" value="kelulusan">
                    <label class="text-xs font-bold text-slate-700 shrink-0 flex items-center gap-1.5">
                        <i class="fa-solid fa-filter text-emerald-600"></i> Pilih Rombel Tingkat Akhir / Kelas:
                    </label>
                    <select name="kelas_lulus_id" onchange="this.form.submit()"
                            class="px-3.5 py-2 border border-slate-300 rounded-xl text-xs font-bold text-slate-800 bg-slate-50 focus:ring-2 focus:ring-emerald-500 focus:bg-white transition min-w-[220px]">
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasLulusId === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama }} ({{ $k->tahunAjaran->nama_lengkap ?? 'TP -' }}) - {{ $k->kelas_siswa_count }} Siswa
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($kelasLulus)
                    <div class="flex items-center gap-3 text-xs">
                        @php
                            $jmlAktif = $kelasLulus->kelasSiswa->where('siswa.status', 'aktif')->count();
                            $jmlLulus = $kelasLulus->kelasSiswa->where('siswa.status', 'lulus')->count();
                        @endphp
                        <span class="px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-lg font-bold text-emerald-700">
                            Aktif: {{ $jmlAktif }} Siswa
                        </span>
                        <span class="px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-lg font-bold text-blue-700">
                            Lulus: {{ $jmlLulus }} Siswa
                        </span>
                    </div>
                @endif
            </div>

            @if($kelasLulus)
                <form action="{{ route('kelas.mutasi.kelulusan') }}" method="POST" id="formKelulusan">
                    @csrf
                    <input type="hidden" name="kelas_id" value="{{ $kelasLulus->id }}">

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                        <!-- Header Card Kelulusan -->
                        <div class="p-4 border-b border-slate-100 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fa-solid fa-graduation-cap text-emerald-600"></i>
                                    <span>Peserta Didik di {{ $kelasLulus->nama }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        {{ $kelasLulus->kelasSiswa->count() }} Total
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Pilih siswa yang akan dinyatakan lulus sekolah atau dikembalikan status aktifnya</p>
                            </div>
                            <div class="relative w-full sm:w-64">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                <input type="text" id="cariSiswaLulus" oninput="filterSiswaLulus(this.value)"
                                       placeholder="Cari nama / NIS siswa..."
                                       class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 bg-white">
                            </div>
                        </div>

                        <!-- Bar Seleksi Massal & Tombol Aksi Cepat -->
                        <div class="px-4 py-3 bg-emerald-50/40 border-b border-emerald-100/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                            <label class="flex items-center cursor-pointer font-bold text-slate-700 hover:text-emerald-900 select-none">
                                <input type="checkbox" id="checkAllLulus" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2 w-4 h-4 cursor-pointer" onchange="toggleAllLulus(this)">
                                <span>Pilih Semua Siswa</span>
                                <span id="labelTerpilihLulus" class="ml-2 font-bold text-emerald-700">
                                    (0 siswa dipilih)
                                </span>
                            </label>

                            <div class="flex items-center gap-2">
                                <!-- Tombol Tetapkan LULUS -->
                                <button type="submit" name="status" value="lulus"
                                        onclick="return konfirmasiKelulusan('lulus')"
                                        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-certificate"></i>
                                    <span>Luluskan Siswa Terpilih</span>
                                </button>

                                <!-- Tombol Batalkan / Aktifkan Kembali -->
                                <button type="submit" name="status" value="aktif"
                                        onclick="return konfirmasiKelulusan('aktif')"
                                        class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-200 transition flex items-center gap-1.5"
                                        title="Kembalikan status siswa menjadi Aktif jika terjadi kesalahan">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Kembalikan Status Aktif</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tabel Daftar Siswa Kelulusan -->
                        <div class="divide-y divide-slate-100 max-h-[560px] overflow-y-auto" id="containerSiswaLulus">
                            @forelse($kelasLulus->kelasSiswa as $index => $ks)
                                <label class="item-siswa-lulus p-3.5 flex items-center justify-between hover:bg-emerald-50/30 cursor-pointer transition select-none block"
                                       data-nama="{{ strtolower($ks->siswa->nama) }}" data-nis="{{ $ks->siswa->nis }}">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <input type="checkbox" name="siswa_ids[]" value="{{ $ks->siswa->id }}"
                                               class="cb-siswa-lulus rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                                               onchange="updateCounterLulus()">
                                        <span class="text-xs font-bold text-slate-400 w-5 shrink-0">{{ $index + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs text-slate-900 truncate">{{ $ks->siswa->nama }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }} &bull; NISN: {{ $ks->siswa->nisn ?: '-' }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($ks->siswa->status === 'lulus')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1">
                                                <i class="fa-solid fa-graduation-cap text-[10px]"></i> Lulus
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                                <i class="fa-solid fa-user-check text-[10px]"></i> Aktif
                                            </span>
                                        @endif
                                    </div>
                                </label>
                            @empty
                                <div class="p-8 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-users-slash text-2xl mb-1 text-slate-300 block"></i>
                                    Belum ada siswa yang terdaftar di kelas ini.
                                </div>
                            @endforelse
                        </div>

                    </div>
                </form>
            @endif

        </div>
    @endif

</div>

<!-- Script Pembantu Seleksi & Filter Cepat Realtime -->
<script>
    // Tab 1: Kenaikan Kelas JS
    function toggleAllKenaikan(master) {
        const checkboxes = document.querySelectorAll('.cb-siswa-kenaikan');
        checkboxes.forEach(cb => {
            const parent = cb.closest('.item-siswa-kenaikan');
            if (parent && parent.style.display !== 'none') {
                cb.checked = master.checked;
            }
        });
        updateCounterKenaikan();
    }

    function updateCounterKenaikan() {
        const checkedCount = document.querySelectorAll('.cb-siswa-kenaikan:checked').length;
        const label = document.getElementById('labelTerpilihKenaikan');
        const btnText = document.getElementById('btnTextKenaikan');
        if (label) {
            label.textContent = checkedCount + ' siswa dipilih';
        }
        if (btnText) {
            btnText.textContent = checkedCount > 0 ? `Proses Kenaikan (${checkedCount} Siswa)` : 'Proses Kenaikan Kelas';
        }
    }

    function filterSiswaKenaikan(keyword) {
        keyword = keyword.toLowerCase().trim();
        const items = document.querySelectorAll('.item-siswa-kenaikan');
        items.forEach(el => {
            const nama = el.getAttribute('data-nama') || '';
            const nis = el.getAttribute('data-nis') || '';
            if (nama.includes(keyword) || nis.includes(keyword)) {
                el.style.display = 'flex';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function konfirmasiKenaikan() {
        const checkedCount = document.querySelectorAll('.cb-siswa-kenaikan:checked').length;
        if (checkedCount === 0) {
            alert('Silakan centang minimal satu siswa yang akan dinaikkan / dipindahkan.');
            return false;
        }

        const selectTujuan = document.querySelector('select[name="kelas_tujuan_id"]');
        if (!selectTujuan || !selectTujuan.value) {
            alert('Silakan pilih kelas tujuan terlebih dahulu.');
            return false;
        }

        const modeVal = document.querySelector('input[name="mode"]:checked')?.value;
        const tujuanText = selectTujuan.options[selectTujuan.selectedIndex].text;
        const actionMsg = modeVal === 'pindah' ? 'memindahkan dan mengeluarkan dari kelas asal' : 'menaikkan ke kelas tujuan (riwayat raport lama tersimpan aman)';

        return confirm(`Apakah Anda yakin ingin ${actionMsg} sebanyak ${checkedCount} siswa terpilih ke:\n${tujuanText}?`);
    }

    // Tab 2: Kelulusan Siswa JS
    function toggleAllLulus(master) {
        const checkboxes = document.querySelectorAll('.cb-siswa-lulus');
        checkboxes.forEach(cb => {
            const parent = cb.closest('.item-siswa-lulus');
            if (parent && parent.style.display !== 'none') {
                cb.checked = master.checked;
            }
        });
        updateCounterLulus();
    }

    function updateCounterLulus() {
        const checkedCount = document.querySelectorAll('.cb-siswa-lulus:checked').length;
        const label = document.getElementById('labelTerpilihLulus');
        if (label) {
            label.textContent = `(${checkedCount} siswa dipilih)`;
        }
    }

    function filterSiswaLulus(keyword) {
        keyword = keyword.toLowerCase().trim();
        const items = document.querySelectorAll('.item-siswa-lulus');
        items.forEach(el => {
            const nama = el.getAttribute('data-nama') || '';
            const nis = el.getAttribute('data-nis') || '';
            if (nama.includes(keyword) || nis.includes(keyword)) {
                el.style.display = 'flex';
            } else {
                el.style.display = 'none';
            }
        });
    }

    function konfirmasiKelulusan(status) {
        const checkedCount = document.querySelectorAll('.cb-siswa-lulus:checked').length;
        if (checkedCount === 0) {
            alert('Silakan centang minimal satu siswa untuk diproses.');
            return false;
        }

        const statusText = status === 'lulus' ? 'LULUS SEKOLAH' : 'AKTIF KEMBALI';
        return confirm(`Apakah Anda yakin ingin memperbarui status ${checkedCount} siswa terpilih menjadi: ${statusText}?`);
    }
</script>
@endsection

