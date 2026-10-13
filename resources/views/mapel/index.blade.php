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

                <div class="divide-y divide-slate-100">
                    @forelse($mapelList as $m)
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center">
                                    {{ $m->urutan }}
                                </span>
                                <div>
                                    <p class="font-bold text-sm text-slate-800">{{ $m->nama }}</p>
                                    <p class="text-[10px] text-slate-400">Baris ke-{{ $m->urutan }} di Raport</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-1">
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
                        <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 rounded-lg bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center">
                                    {{ $p->urutan }}
                                </span>
                                <div>
                                    <p class="font-bold text-sm text-slate-800">{{ $p->nama }}</p>
                                    <p class="text-[10px] text-slate-400">Urutan ke-{{ $p->urutan }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-1">
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
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Tambah Mata Pelajaran</h3>
            <form action="{{ route('mapel.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Mata Pelajaran *</label>
                    <input type="text" name="nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris di Raport *</label>
                    <input type="number" name="urutan" value="{{ $mapelList->count() + 1 }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
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
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Ubah Mata Pelajaran</h3>
            <form id="formEditMapel" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Mata Pelajaran *</label>
                    <input type="text" name="nama" id="edit_mapel_nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Urutan Baris di Raport *</label>
                    <input type="number" name="urutan" id="edit_mapel_urutan" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
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
        document.getElementById('edit_mapel_urutan').value = mapel.urutan;
        document.getElementById('modalEditMapel').classList.remove('hidden');
    }

    function editPembiasaan(pem) {
        document.getElementById('formEditPembiasaan').action = '/pembiasaan/' + pem.id;
        document.getElementById('edit_pem_nama').value = pem.nama;
        document.getElementById('edit_pem_urutan').value = pem.urutan;
        document.getElementById('modalEditPembiasaan').classList.remove('hidden');
    }
</script>
@endpush
@endsection
