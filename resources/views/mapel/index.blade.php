@extends('layouts.app')

@section('title', 'Mata Pelajaran & Pembiasaan')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-book-open text-emerald-600 mr-2"></i> Mata Pelajaran & Aspek Pembiasaan
            </h1>
            <p class="text-xs text-slate-500">Atur mata pelajaran, aspek pembiasaan karakter, dan urutan kemunculan baris di raport</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- KELOLA MATA PELAJARAN -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-graduation-cap text-emerald-600 mr-2"></i> Mata Pelajaran
                        </h2>
                        <span class="text-xs text-slate-400">Tampil pada tabel nilai raport</span>
                    </div>

                    <button onclick="document.getElementById('modalTambahMapel').classList.remove('hidden')"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition">
                        <i class="fa-solid fa-plus mr-1"></i> Tambah Mapel
                    </button>
                </div>

                <!-- Banner Penjelasan KKM Master -->
                <div class="p-3.5 bg-amber-50/70 border-b border-amber-200/60 text-xs text-amber-900 flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                    <div>
                        <strong class="font-bold">Informasi KKM Standar (Master Default):</strong>
                        <p class="text-amber-800 text-[11px] leading-relaxed mt-0.5">
                            KKM di bawah ini adalah batas standar default sekolah. <strong>Setiap Wali Kelas berhak menentukan KKM / KKTP mandiri</strong> khusus rombelnya masing-masing di halaman <em>Rombongan Belajar</em> atau <em>Input Nilai</em> tanpa mempengaruhi kelas lainnya.
                        </p>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($mapelList as $m)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition {{ !$m->aktif ? 'bg-slate-50/80 opacity-75' : '' }}">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-lg {{ $m->aktif ? 'bg-slate-100 text-slate-600' : 'bg-slate-200 text-slate-400' }} font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $m->urutan }}
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="font-bold text-sm {{ $m->aktif ? 'text-slate-800' : 'text-slate-500 line-through' }}">{{ $m->nama }}</p>
                                        @if($m->aktif)
                                            <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-md text-[10px] font-bold inline-flex items-center gap-1">
                                                <i class="fa-solid fa-circle-check text-[9px] text-emerald-600"></i> Aktif
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 bg-slate-200 text-slate-600 border border-slate-300 rounded-md text-[10px] font-bold inline-flex items-center gap-1">
                                                <i class="fa-solid fa-ban text-[9px] text-slate-500"></i> Nonaktif
                                            </span>
                                        @endif
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-md text-[10px] font-bold">
                                            KKM: {{ $m->kkm ?? 70 }}
                                        </span>
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200/80 rounded-md text-[10px] font-bold">
                                            <i class="fa-solid fa-layer-group text-[9px] mr-1"></i>{{ $m->target_kelas_label }}
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-slate-400">
                                        Baris ke-{{ $m->urutan }} di Raport &bull; Target: <span class="text-slate-600 font-semibold">{{ $m->target_kelas_label }}</span>
                                        @if(!$m->aktif)
                                            <span class="text-amber-700 font-semibold ml-1">&bull; Disembunyikan dari Penilaian & Raport</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                                <form action="{{ route('mapel.toggle-status', $m->id) }}" method="POST" class="inline">
                                    @csrf
                                    @if($m->aktif)
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition" title="Klik untuk menonaktifkan mapel ini">
                                            <i class="fa-solid fa-power-off mr-1 text-amber-600"></i> Nonaktifkan
                                        </button>
                                    @else
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition" title="Klik untuk mengaktifkan kembali mapel ini">
                                            <i class="fa-solid fa-check mr-1 text-emerald-600"></i> Aktifkan
                                        </button>
                                    @endif
                                </form>
                                <button onclick="editMapel({{ json_encode($m) }})"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Ubah">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('mapel.destroy', $m->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus mapel ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">Belum ada mata pelajaran.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- KELOLA PEMBIASAAN -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                            <i class="fa-solid fa-heart text-rose-500 mr-2"></i> Aspek Pembiasaan / Karakter
                        </h2>
                        <span class="text-xs text-slate-400">Predikat: Sangat Baik, Baik, Cukup, Perlu Bimbingan</span>
                    </div>

                    <button onclick="document.getElementById('modalTambahPembiasaan').classList.remove('hidden')"
                        class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs transition">
                        <i class="fa-solid fa-plus mr-1"></i> Tambah Aspek
                    </button>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($pembiasaanList as $p)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition {{ !$p->aktif ? 'bg-slate-50/80 opacity-75' : '' }}">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-lg {{ $p->aktif ? 'bg-slate-100 text-slate-600' : 'bg-slate-200 text-slate-400' }} font-bold text-xs flex items-center justify-center shrink-0">
                                    {{ $p->urutan }}
                                </span>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-bold text-sm {{ $p->aktif ? 'text-slate-800' : 'text-slate-500 line-through' }}">{{ $p->nama }}</p>
                                        @if($p->aktif)
                                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[9px] font-bold">Aktif</span>
                                        @else
                                            <span class="px-1.5 py-0.5 bg-slate-200 text-slate-600 border border-slate-300 rounded text-[9px] font-bold">Nonaktif</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] text-slate-400">Urutan ke-{{ $p->urutan }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-1.5 shrink-0 ml-2">
                                <form action="{{ route('pembiasaan.toggle-status', $p->id) }}" method="POST" class="inline">
                                    @csrf
                                    @if($p->aktif)
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 transition" title="Nonaktifkan aspek ini">
                                            <i class="fa-solid fa-power-off mr-0.5 text-amber-600"></i> Nonaktifkan
                                        </button>
                                    @else
                                        <button type="submit" class="px-2 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition" title="Aktifkan aspek ini">
                                            <i class="fa-solid fa-check mr-0.5 text-emerald-600"></i> Aktifkan
                                        </button>
                                    @endif
                                </form>
                                <button onclick="editPembiasaan({{ json_encode($p) }})"
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Ubah">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form action="{{ route('pembiasaan.destroy', $p->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aspek ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">Belum ada aspek pembiasaan.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Tambah Mapel -->
    <div id="modalTambahMapel" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Tambah Mata Pelajaran</h3>
            <form action="{{ route('mapel.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Mata Pelajaran *</label>
                    <input type="text" name="nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">KKM (0 - 100) *</label>
                        <input type="number" name="kkm" value="70" min="0" max="100" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris *</label>
                        <input type="number" name="urutan" value="{{ $mapelList->count() + 1 }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Target Kelas / Fase Kurikulum</label>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex flex-wrap gap-1 text-[10px]">
                            <button type="button" onclick="pilihSemuaKelas('tambah')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-semibold text-slate-700">Semua Kelas</button>
                            <button type="button" onclick="pilihFase('tambah', [1, 2])" class="px-2 py-0.5 bg-emerald-100 hover:bg-emerald-200 rounded font-semibold text-emerald-800">Fase A (1-2)</button>
                            <button type="button" onclick="pilihFase('tambah', [3, 4])" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-semibold text-indigo-800">Fase B (3-4)</button>
                            <button type="button" onclick="pilihFase('tambah', [5, 6])" class="px-2 py-0.5 bg-purple-100 hover:bg-purple-200 rounded font-semibold text-purple-800">Fase C (5-6)</button>
                            <button type="button" onclick="pilihFase('tambah', [3, 4, 5, 6])" class="px-2 py-0.5 bg-amber-100 hover:bg-amber-200 rounded font-semibold text-amber-900">Fase B & C (3-6)</button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5 pt-1">
                            @for($k = 1; $k <= 6; $k++)
                                <label class="flex items-center space-x-1.5 text-xs text-slate-700 bg-white px-2 py-1.5 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-500 transition select-none">
                                    <input type="checkbox" name="target_kelas[]" value="{{ $k }}" class="tambah_target_kelas text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" checked>
                                    <span class="font-bold">Kelas {{ $k }}</span>
                                </label>
                            @endfor
                        </div>
                        <p class="text-[10px] text-slate-400 italic leading-snug">* Centang kelas yang mempelajari mata pelajaran ini. Jika semua dicentang, berlaku untuk seluruh rombel kelas.</p>
                    </div>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="aktif" value="1" class="text-emerald-600 rounded border-slate-300 focus:ring-emerald-500" checked>
                        <span>Mata Pelajaran Aktif (Tampil pada Penilaian & Raport)</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1 pl-6">Hilangkan centang jika mata pelajaran ini belum ada ujiannya atau dinonaktifkan sementara.</p>
                </div>

                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalTambahMapel').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Mapel -->
    <div id="modalEditMapel" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Ubah Mata Pelajaran</h3>
            <form id="formEditMapel" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Mata Pelajaran *</label>
                    <input type="text" name="nama" id="edit_mapel_nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">KKM (0 - 100) *</label>
                        <input type="number" name="kkm" id="edit_mapel_kkm" min="0" max="100" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris *</label>
                        <input type="number" name="urutan" id="edit_mapel_urutan" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Target Kelas / Fase Kurikulum</label>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 space-y-2">
                        <div class="flex flex-wrap gap-1 text-[10px]">
                            <button type="button" onclick="pilihSemuaKelas('edit')" class="px-2 py-0.5 bg-slate-200 hover:bg-slate-300 rounded font-semibold text-slate-700">Semua Kelas</button>
                            <button type="button" onclick="pilihFase('edit', [1, 2])" class="px-2 py-0.5 bg-emerald-100 hover:bg-emerald-200 rounded font-semibold text-emerald-800">Fase A (1-2)</button>
                            <button type="button" onclick="pilihFase('edit', [3, 4])" class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 rounded font-semibold text-indigo-800">Fase B (3-4)</button>
                            <button type="button" onclick="pilihFase('edit', [5, 6])" class="px-2 py-0.5 bg-purple-100 hover:bg-purple-200 rounded font-semibold text-purple-800">Fase C (5-6)</button>
                            <button type="button" onclick="pilihFase('edit', [3, 4, 5, 6])" class="px-2 py-0.5 bg-amber-100 hover:bg-amber-200 rounded font-semibold text-amber-900">Fase B & C (3-6)</button>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5 pt-1">
                            @for($k = 1; $k <= 6; $k++)
                                <label class="flex items-center space-x-1.5 text-xs text-slate-700 bg-white px-2 py-1.5 rounded-lg border border-slate-200 cursor-pointer hover:border-emerald-500 transition select-none">
                                    <input type="checkbox" name="target_kelas[]" value="{{ $k }}" class="edit_target_kelas text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                                    <span class="font-bold">Kelas {{ $k }}</span>
                                </label>
                            @endfor
                        </div>
                        <p class="text-[10px] text-slate-400 italic leading-snug">* Centang kelas yang mempelajari mata pelajaran ini. Jika semua dicentang, berlaku untuk seluruh rombel kelas.</p>
                    </div>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="aktif" id="edit_mapel_aktif" value="1" class="text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span>Mata Pelajaran Aktif (Tampil pada Penilaian & Raport)</span>
                    </label>
                    <p class="text-[10px] text-slate-400 mt-1 pl-6">Hilangkan centang jika mata pelajaran ini belum ada ujiannya (misal Koding) agar tidak muncul di form nilai dan lembar raport.</p>
                </div>

                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalEditMapel').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Pembiasaan -->
    <div id="modalTambahPembiasaan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Tambah Aspek Pembiasaan</h3>
            <form action="{{ route('pembiasaan.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Aspek Pembiasaan *</label>
                    <input type="text" name="nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris *</label>
                    <input type="number" name="urutan" value="{{ $pembiasaanList->count() + 1 }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500">
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="aktif" value="1" class="text-rose-600 rounded border-slate-300 focus:ring-rose-500" checked>
                        <span>Aspek Aktif (Tampil pada Raport)</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalTambahPembiasaan').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Pembiasaan -->
    <div id="modalEditPembiasaan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Ubah Aspek Pembiasaan</h3>
            <form id="formEditPembiasaan" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Aspek Pembiasaan *</label>
                    <input type="text" name="nama" id="edit_pem_nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris *</label>
                    <input type="number" name="urutan" id="edit_pem_urutan" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-rose-500">
                </div>
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <label class="flex items-center space-x-2 text-xs font-bold text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" name="aktif" id="edit_pem_aktif" value="1" class="text-rose-600 rounded border-slate-300 focus:ring-rose-500">
                        <span>Aspek Aktif (Tampil pada Raport)</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalEditPembiasaan').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function editMapel(mapel) {
        document.getElementById('formEditMapel').action = '/mapel/' + mapel.id;
        document.getElementById('edit_mapel_nama').value = mapel.nama;
        document.getElementById('edit_mapel_kkm').value = mapel.kkm || 70;
        document.getElementById('edit_mapel_urutan').value = mapel.urutan;
        document.getElementById('edit_mapel_aktif').checked = Boolean(mapel.aktif);

        let targets = mapel.target_kelas;
        if (!targets || targets.length === 0) {
            targets = [1, 2, 3, 4, 5, 6];
        } else {
            targets = targets.map(Number);
        }

        document.querySelectorAll('.edit_target_kelas').forEach(cb => {
            cb.checked = targets.includes(parseInt(cb.value));
        });

        document.getElementById('modalEditMapel').classList.remove('hidden');
    }

    function pilihSemuaKelas(prefix) {
        document.querySelectorAll('.' + prefix + '_target_kelas').forEach(cb => cb.checked = true);
    }

    function pilihFase(prefix, targetArray) {
        document.querySelectorAll('.' + prefix + '_target_kelas').forEach(cb => {
            cb.checked = targetArray.includes(parseInt(cb.value));
        });
    }

    function editPembiasaan(pem) {
        document.getElementById('formEditPembiasaan').action = '/pembiasaan/' + pem.id;
        document.getElementById('edit_pem_nama').value = pem.nama;
        document.getElementById('edit_pem_urutan').value = pem.urutan;
        document.getElementById('edit_pem_aktif').checked = Boolean(pem.aktif);
        document.getElementById('modalEditPembiasaan').classList.remove('hidden');
    }
</script>
@endpush
@endsection
