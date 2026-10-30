@extends('layouts.app')

@section('title', 'Penilaian Harian Formatif - ' . $mapel->nama . ' - ' . $kelas->nama)

@section('content')
<div class="space-y-5" x-data="{
    modalTambah: false,
    modalEdit: false,
    editData: { id: '', nama: '', tanggal: '', lingkup_materi: '', jenis: 'teori', bobot: '1.0' },
    bukaModalEdit(p) {
        this.editData = {
            id: p.id,
            nama: p.nama,
            tanggal: p.tanggal ? p.tanggal.substr(0, 10) : '',
            lingkup_materi: p.lingkup_materi || '',
            jenis: p.jenis || 'teori',
            bobot: p.bobot || '1.0'
        };
        this.modalEdit = true;
    }
}">

    <!-- 1. Header & Navigasi Terpadu -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start space-x-3">
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
               class="p-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition shrink-0"
               title="Kembali ke Lembar 1 Formatif Bulanan">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Rincian Penilaian Harian
                    </span>
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $kelas->nama }}
                    </span>
                    <span class="text-xs font-semibold text-slate-500">
                        TP {{ $kelas->tahunAjaran->nama_lengkap ?? '-' }} &bull; Semester {{ ucfirst($semester) }}
                    </span>
                </div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mt-1 flex items-center gap-2">
                    <span>{{ $mapel->nama }}</span>
                    <span class="text-sm font-bold text-slate-500">({{ $namaBulan }} {{ $tahun }})</span>
                </h1>
                <p class="text-xs text-slate-500 mt-0.5">
                    Input beberapa penilaian harian / tugas / TP. Nilai otomatis dirata-rata menjadi <strong>Nilai Formatif Bulan {{ $namaBulan }}</strong> dan masuk ke rekap semester.
                </p>
            </div>
        </div>

        <!-- Filter Pemilih Bulan & Tahun -->
        <div class="flex flex-wrap items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-200 self-start md:self-auto text-xs">
            <form action="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="GET" class="flex items-center gap-2">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <span class="text-slate-500 font-bold ml-1 flex items-center gap-1">
                    <i class="fa-regular fa-calendar text-slate-400"></i> Bulan:
                </span>
                <select name="bulan" onchange="this.form.submit()" class="bg-white border border-slate-300 font-bold text-slate-800 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @foreach($daftarBulanSemester as $bNum => $bNama)
                        <option value="{{ $bNum }}" {{ $bulan == $bNum ? 'selected' : '' }}>
                            {{ $bNama }} (Bulan {{ $bNum }})
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Pilihan Cepat Mata Pelajaran -->
    @if($allMapel->isNotEmpty())
        <div class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-2xs flex items-center gap-1.5 overflow-x-auto text-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px] mr-1 shrink-0 flex items-center">
                <i class="fa-solid fa-book-open mr-1"></i> Ganti Mapel:
            </span>
            @foreach($allMapel as $m)
                <a href="{{ route('kelas.mapel.formatif-harian', ['kelas' => $kelas->id, 'mapel' => $m->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
                   class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 flex items-center gap-1.5 {{ $m->id === $mapel->id ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    <span>{{ $m->nama }}</span>
                    <span class="text-[9px] px-1.5 py-0.2 rounded {{ $m->id === $mapel->id ? 'bg-emerald-800 text-white' : 'bg-slate-200 text-slate-600' }}">
                        {{ $kelas->getKkmForMapel($m) }}
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    <!-- Toolbar Aksi & Ringkasan -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Tambah Penilaian Harian -->
            <button type="button" @click="modalTambah = true"
                    class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-amber-300"></i>
                <span>Tambah Penilaian Harian</span>
            </button>

            <!-- Tombol Cetak Dokumen Nilai Harian -->
            <a href="{{ route('kelas.mapel.formatif-harian.cetak', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tahun' => $tahun, 'bulan' => $bulan]) }}"
               target="_blank"
               class="px-4 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-2"
               title="Cetak lembar rincian penilaian harian bulan {{ $namaBulan }} (Format F4/A4)">
                <i class="fa-solid fa-print text-amber-400"></i>
                <span>Cetak Lembar Harian (F4)</span>
            </a>

            <!-- Tombol Kembali ke Lembar Formatif -->
            <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
               class="px-3.5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-xs border border-slate-300 shadow-2xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-table-cells text-slate-500"></i>
                <span>Tabel Bulanan</span>
            </a>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                Total Siswa: <strong>{{ $kelasSiswaList->count() }}</strong>
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-800 font-semibold border border-blue-200">
                Kolom Harian: <strong>{{ $penilaianList->count() }} Penilaian</strong>
            </span>
        </div>
    </div>

    <!-- Alert Edukasi & Alur Penilaian -->
    <div class="bg-blue-50/70 border border-blue-200/80 p-3.5 rounded-2xl flex items-start space-x-3 text-xs text-blue-900">
        <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5 shrink-0"></i>
        <div class="flex-1">
            <span class="font-bold">Alur Otomatisasi Nilai Formatif:</span>
            Setiap nilai yang Anda masukkan pada kolom harian di bawah akan langsung dihitung rata-ratanya pada kolom <strong>Rata-rata {{ substr($namaBulan, 0, 3) }}</strong>. Saat Anda mengklik <strong>Simpan Nilai Harian</strong>, nilai rata-rata tersebut otomatis mengisi nilai formatif bulan <strong>{{ $namaBulan }}</strong> di lembar bulanan utama serta mengalir langsung ke rekap nilai semester tanpa perlu input ulang.
        </div>
    </div>

    <!-- Formulir Matriks Nilai Harian -->
    <form action="{{ route('kelas.mapel.formatif-harian.simpan', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="tahun" value="{{ $tahun }}">
        <input type="hidden" name="bulan" value="{{ $bulan }}">

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            @if($penilaianList->isEmpty())
                <div class="p-10 text-center text-slate-500">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mb-3 shadow-2xs">
                        <i class="fa-solid fa-file-circle-plus"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wide">
                        Belum Ada Kolom Penilaian Harian
                    </h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-4 leading-relaxed">
                        Di bulan <strong>{{ $namaBulan }} {{ $tahun }}</strong> belum ada kolom penilaian harian untuk mata pelajaran <strong>{{ $mapel->nama }}</strong>. Klik tombol di bawah untuk menambahkan penilaian pertama (misal: Harian 1, Tugas 1, atau TP 1).
                    </p>
                    <button type="button" @click="modalTambah = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Tambah Penilaian Harian Pertama</span>
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3.5 px-3 w-10 text-center border-r border-slate-200">No</th>
                                <th class="py-3.5 px-4 min-w-[200px] border-r border-slate-200 sticky left-0 bg-slate-50 z-10 shadow-[1px_0_0_0_#e2e8f0]">
                                    Nama Peserta Didik
                                </th>

                                @foreach($penilaianList as $p)
                                    <th class="py-3 px-3 text-center border-r border-slate-200 min-w-[130px] max-w-[170px] align-top {{ $p->jenis === 'praktik' ? 'bg-cyan-50/70' : 'bg-blue-50/40' }}">
                                        <div class="flex items-center justify-between gap-1 mb-1">
                                            <span class="font-extrabold text-slate-900 truncate" title="{{ $p->nama }}">
                                                {{ $p->nama }}
                                            </span>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button type="button" @click="bukaModalEdit({{ json_encode($p) }})"
                                                        class="w-5 h-5 rounded hover:bg-slate-200 text-slate-500 hover:text-blue-600 flex items-center justify-center text-[10px]"
                                                        title="Edit penilaian ini">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                <button type="button" onclick="konfirmasiHapusPenilaian({{ $p->id }}, '{{ $p->nama }}')"
                                                        class="w-5 h-5 rounded hover:bg-rose-100 text-slate-400 hover:text-rose-600 flex items-center justify-center text-[10px]"
                                                        title="Hapus kolom penilaian ini">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="text-[10px] text-slate-500 font-normal space-y-0.5">
                                            @if($p->tanggal)
                                                <div class="flex items-center justify-center gap-1 font-mono text-[9px] text-slate-600">
                                                    <i class="fa-regular fa-calendar-days text-[9px]"></i> {{ $p->tanggal_format }}
                                                </div>
                                            @endif
                                            @if($p->lingkup_materi)
                                                <div class="truncate text-[9px] text-slate-600 bg-white/80 px-1.5 py-0.5 rounded border border-slate-200" title="{{ $p->lingkup_materi }}">
                                                    {{ $p->lingkup_materi }}
                                                </div>
                                            @endif
                                            @if($p->jenis === 'praktik')
                                                <span class="inline-block px-1.5 py-0.2 rounded text-[8px] font-black uppercase bg-cyan-100 text-cyan-800">
                                                    Praktik
                                                </span>
                                            @endif
                                        </div>
                                    </th>
                                @endforeach

                                <th class="py-3.5 px-3 text-center border-r border-emerald-200 bg-emerald-50 text-emerald-950 font-black w-28 align-middle">
                                    Rata-rata {{ substr($namaBulan, 0, 3) }}
                                    <span class="block text-[9px] font-normal text-emerald-800 font-sans">Otomatis Terhitung</span>
                                </th>

                                <th class="py-3.5 px-3 text-center bg-slate-100 text-slate-700 font-bold w-28 align-middle" title="Nilai yang saat ini tersimpan di lembar bulanan utama">
                                    Nilai Tersimpan
                                    <span class="block text-[9px] font-normal text-slate-500 font-sans">Di Lembar Bulanan</span>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($kelasSiswaList as $idx => $ks)
                                @php
                                    $rek = $rekapPerSiswa[$ks->id] ?? null;
                                    $currAvg = $rek['avg_teori'] ?? null;
                                    $savedBulanan = $rek['bulanan_tersimpan'] ?? null;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition" id="row-siswa-{{ $ks->id }}">
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-400 border-r border-slate-200">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td class="py-2.5 px-4 border-r border-slate-200 whitespace-nowrap sticky left-0 bg-white z-10 shadow-[1px_0_0_0_#e2e8f0]">
                                        <div class="font-bold text-slate-900">{{ $ks->siswa->nama }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">NIS: {{ $ks->siswa->nis }}</div>
                                    </td>

                                    @foreach($penilaianList as $p)
                                        @php
                                            $val = $matrixHarian[$p->id][$ks->id] ?? '';
                                        @endphp
                                        <td class="py-2 px-2 text-center border-r border-slate-200 {{ $p->jenis === 'praktik' ? 'bg-cyan-50/20' : '' }}">
                                            <input type="number" step="0.1" min="0" max="100"
                                                   name="nilai[{{ $p->id }}][{{ $ks->id }}]"
                                                   value="{{ old('nilai.' . $p->id . '.' . $ks->id, $val) }}"
                                                   placeholder="-"
                                                   data-siswa-id="{{ $ks->id }}"
                                                   data-jenis="{{ $p->jenis }}"
                                                   oninput="hitungLiveRataRata({{ $ks->id }})"
                                                   class="input-harian-cell w-16 text-center font-mono font-bold text-xs py-1.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                                        </td>
                                    @endforeach

                                    <!-- Kolom Rata-rata Otomatis Terhitung -->
                                    <td class="py-2.5 px-3 text-center border-r border-emerald-200 bg-emerald-50/50">
                                        <span class="font-black text-sm text-emerald-950 font-mono" id="live-avg-{{ $ks->id }}">
                                            {{ $currAvg !== null ? \App\Services\HitungNilaiService::formatAngka($currAvg) : '-' }}
                                        </span>
                                    </td>

                                    <!-- Nilai yang saat ini tersimpan di tabel bulanan -->
                                    <td class="py-2.5 px-3 text-center bg-slate-50 font-mono text-xs text-slate-600">
                                        @if($savedBulanan !== null)
                                            <span class="px-2 py-0.5 rounded-md font-bold bg-white border border-slate-200 text-slate-800">
                                                {{ \App\Services\HitungNilaiService::formatAngka($savedBulanan) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Bar Simpan -->
                <div class="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
                        <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i>
                        <span>Perubahan nilai akan otomatis mengupdate nilai formatif bulanan & nilai rapor semester.</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('kelas.mapel.nilai', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'tab' => 'formatif', 'tahun' => $tahun]) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-100 transition shadow-2xs">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-5 py-2 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Nilai Harian</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </form>

    <!-- ============================================================== -->
    <!-- MODAL 1: TAMBAH PENILAIAN HARIAN BARU                          -->
    <!-- ============================================================== -->
    <div x-show="modalTambah" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="modalTambah = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">Tambah Penilaian Harian</h3>
                        <p class="text-[11px] text-slate-500">{{ $mapel->nama }} &bull; Bulan {{ $namaBulan }}</p>
                    </div>
                </div>
                <button type="button" @click="modalTambah = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form action="{{ route('kelas.mapel.formatif-harian.tambah-penilaian', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                <input type="hidden" name="bulan" value="{{ $bulan }}">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">
                        Nama Penilaian <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama" required
                           placeholder="Contoh: Harian 1, Tugas 1, TP 1, dsb."
                           value="Harian {{ $penilaianList->count() + 1 }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold text-xs">
                    <p class="text-[10px] text-slate-400 mt-1">Nama ini akan tampil sebagai judul kolom penilaian.</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pelaksanaan</label>
                    <input type="date" name="tanggal"
                           value="{{ now()->format('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Lingkup Materi / Tujuan Pembelajaran (TP)</label>
                    <input type="text" name="lingkup_materi"
                           placeholder="Contoh: Menyimak Teks Narasi / Bilangan Cacah"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold text-xs">
                </div>

                @if($isPjok)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jenis Penilaian (Khusus PJOK)</label>
                        <select name="jenis" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:outline-none font-semibold text-xs">
                            <option value="teori">Formatif Teori (Fmt)</option>
                            <option value="praktik">Praktik Jasmani (Prak)</option>
                        </select>
                    </div>
                @else
                    <input type="hidden" name="jenis" value="teori">
                @endif

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalTambah = false"
                            class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl font-black text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambahkan Kolom</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: EDIT DATA PENILAIAN HARIAN                            -->
    <!-- ============================================================== -->
    <div x-show="modalEdit" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="modalEdit = false"
             class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-pen"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">Edit Penilaian Harian</h3>
                        <p class="text-[11px] text-slate-500">Ubah nama, tanggal, atau materi penilaian</p>
                    </div>
                </div>
                <button type="button" @click="modalEdit = false" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <form :action="'{{ url('kelas/' . $kelas->id . '/mapel/' . $mapel->id . '/formatif-harian/penilaian') }}/' + editData.id" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">
                        Nama Penilaian <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama" x-model="editData.nama" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal Pelaksanaan</label>
                    <input type="date" name="tanggal" x-model="editData.tanggal"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-xs">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Lingkup Materi / TP</label>
                    <input type="text" name="lingkup_materi" x-model="editData.lingkup_materi"
                           class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-xs">
                </div>

                @if($isPjok)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jenis Penilaian</label>
                        <select name="jenis" x-model="editData.jenis" class="w-full px-3 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-xs">
                            <option value="teori">Formatif Teori (Fmt)</option>
                            <option value="praktik">Praktik Jasmani (Prak)</option>
                        </select>
                    </div>
                @endif

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalEdit = false"
                            class="px-4 py-2 rounded-xl font-bold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl font-black text-white bg-blue-600 hover:bg-blue-700 transition shadow-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-check"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Tersembunyi untuk Hapus Penilaian -->
    <form id="formHapusPenilaian" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
    // Live calculation of row averages
    function hitungLiveRataRata(siswaId) {
        const row = document.getElementById('row-siswa-' + siswaId);
        if (!row) return;

        const inputs = row.querySelectorAll('.input-harian-cell');
        let total = 0;
        let count = 0;

        inputs.forEach(input => {
            const val = parseFloat(input.value);
            if (!isNaN(val) && val >= 0 && val <= 100) {
                total += val;
                count++;
            }
        });

        const spanAvg = document.getElementById('live-avg-' + siswaId);
        if (spanAvg) {
            if (count > 0) {
                const avg = (total / count).toFixed(1);
                spanAvg.textContent = avg.endsWith('.0') ? parseInt(avg) : avg.replace('.', ',');
            } else {
                spanAvg.textContent = '-';
            }
        }
    }

    function konfirmasiHapusPenilaian(id, nama) {
        if (confirm("Apakah Anda yakin ingin menghapus kolom penilaian '" + nama + "'? Semua nilai siswa pada kolom ini akan terhapus dan rata-rata bulanan akan disesuaikan.")) {
            const form = document.getElementById('formHapusPenilaian');
            form.action = "{{ url('kelas/' . $kelas->id . '/mapel/' . $mapel->id . '/formatif-harian/penilaian') }}/" + id;
            form.submit();
        }
    }
</script>
@endsection
