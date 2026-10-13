@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-graduate text-emerald-600 mr-2"></i> Induk Data Peserta Didik
            </h1>
            <p class="text-xs text-slate-500">Kelola master siswa, NIS, NISN, dan impor data massal dari clipboard Excel</p>
        </div>

        @if(auth()->user()->isAdmin())
            <div class="flex items-center space-x-2">
                <button onclick="document.getElementById('modalImportClipboard').classList.remove('hidden')"
                    class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold rounded-xl text-xs transition">
                    <i class="fa-solid fa-paste mr-1.5"></i> Tempel dari Clipboard / Excel
                </button>
                <button onclick="document.getElementById('modalTambahSiswa').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition">
                    <i class="fa-solid fa-plus mr-1.5"></i> Tambah Siswa
                </button>
            </div>
        @endif
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm">
        <form action="{{ route('siswa.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa, NIS, atau NISN..."
                    class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="w-full sm:w-44">
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ $status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="lulus" {{ $status === 'lulus' ? 'selected' : '' }}>Lulus</option>
                </select>
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold rounded-xl text-xs transition">
                Cari Data
            </button>
            @if($search || $status)
                <a href="{{ route('siswa.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Tabel Siswa -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">NIS</th>
                        <th class="py-3 px-4">NISN</th>
                        <th class="py-3 px-4">Kelas Terakhir</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($siswaList as $index => $s)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-500 font-medium">
                                {{ $siswaList->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $s->nama }}
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-700">{{ $s->nis }}</td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-500">{{ $s->nisn ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                @if($s->kelasSiswa->isNotEmpty())
                                    <span class="font-medium text-slate-800">{{ $s->kelasSiswa->last()->kelas->nama ?? '-' }}</span>
                                    <span class="text-slate-400">({{ $s->kelasSiswa->last()->kelas->tahunAjaran->tahun ?? '-' }})</span>
                                @else
                                    <span class="text-slate-400 italic">Belum ditempatkan</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($s->status === 'aktif')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Lulus</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                @if(auth()->user()->isAdmin())
                                    <button onclick="editSiswa({{ json_encode($s) }})"
                                        class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Ubah">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('siswa.destroy', $s->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus siswa {{ $s->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic">Lihat Saja</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data siswa yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswaList->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $siswaList->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Tambah Siswa -->
    <div id="modalTambahSiswa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Tambah Siswa Baru</h3>
                <button onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('siswa.store') }}" method="POST" class="space-y-4 mt-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama" placeholder="Contoh: Muhammad Rizqi Pratama" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nomor Induk Siswa (NIS) * (Unik)</label>
                    <input type="text" name="nis" placeholder="Contoh: 1024" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">NISN (Nasional)</label>
                    <input type="text" name="nisn" placeholder="Contoh: 0158291823"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Status Keaktifan *</label>
                    <select name="status" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="aktif">Aktif</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                        Simpan Siswa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Siswa -->
    <div id="modalEditSiswa" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Ubah Data Siswa</h3>
                <button onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form id="formEditSiswa" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="nama" id="edit_nama" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Nomor Induk Siswa (NIS) *</label>
                    <input type="text" name="nis" id="edit_nis" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">NISN (Nasional)</label>
                    <input type="text" name="nisn" id="edit_nisn"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Status Keaktifan *</label>
                    <select name="status" id="edit_status" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="aktif">Aktif</option>
                        <option value="lulus">Lulus</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import Clipboard / Excel (F5) -->
    <div id="modalImportClipboard" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-file-excel text-emerald-600 mr-2"></i> Impor Cepat dari Clipboard Excel
                </h3>
                <button onclick="document.getElementById('modalImportClipboard').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
            </div>

            <form action="{{ route('siswa.import.clipboard') }}" method="POST" class="space-y-4 mt-4">
                @csrf

                <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-xs text-amber-800">
                    <p class="font-bold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Format Data:</p>
                    <p>Salin (Copy) tabel dari Excel atau Google Sheets dengan kolom urut:</p>
                    <p class="font-mono bg-white px-2 py-1 rounded border border-amber-300 mt-1">Nama Siswa [TAB] NIS [TAB] NISN</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Tempel (Paste) Teks Di Sini *</label>
                    <textarea name="data_teks" rows="6" required placeholder="Contoh:&#10;Ahmad Fauzi	1001	0151234501&#10;Anisa Rahma	1002	0151234502"
                        class="w-full p-3 font-mono border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('modalImportClipboard').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-sm flex items-center">
                        <i class="fa-solid fa-cloud-arrow-up mr-1.5"></i> Proses Impor Data
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function editSiswa(siswa) {
        document.getElementById('formEditSiswa').action = '/siswa/' + siswa.id;
        document.getElementById('edit_nama').value = siswa.nama;
        document.getElementById('edit_nis').value = siswa.nis;
        document.getElementById('edit_nisn').value = siswa.nisn || '';
        document.getElementById('edit_status').value = siswa.status;
        document.getElementById('modalEditSiswa').classList.remove('hidden');
    }
</script>
@endpush
@endsection
