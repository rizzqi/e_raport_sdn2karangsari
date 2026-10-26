@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-users-gear text-emerald-600 mr-2"></i> Manajemen Akun Pengguna
            </h1>
            <p class="text-xs text-slate-500">Kelola akun administrator, wali kelas, dan kepala sekolah</p>
        </div>

        <button onclick="document.getElementById('modalTambahUser').classList.remove('hidden')"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center">
            <i class="fa-solid fa-user-plus mr-1.5"></i> Tambah Pengguna
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase text-slate-600">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Lengkap</th>
                        <th class="py-3 px-4">Alamat Email</th>
                        <th class="py-3 px-4 text-center">Peran (Hak Akses)</th>
                        <th class="py-3 px-4">Kelas Diampu</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $index => $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-bold text-xs">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mr-2.5">
                                    {{ substr($u->name, 0, 1) }}
                                </div>
                                {{ $u->name }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-mono text-xs">{{ $u->email }}</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($u->role === 'admin')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                        Admin Sekolah
                                    </span>
                                @elseif($u->role === 'wali')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Wali Kelas
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        Kepala Sekolah
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-600">
                                @if($u->role === 'wali')
                                    <span class="font-bold text-slate-800 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">{{ $u->kelas_wali_count }} Kelas</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <button onclick="editUser({{ json_encode($u) }})" class="p-1.5 text-slate-400 hover:text-emerald-600 transition" title="Ubah">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                @if($u->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna {{ $u->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 transition" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 text-xs">
                                <i class="fa-solid fa-users text-3xl mb-2 block text-slate-300"></i>
                                <p class="font-bold text-slate-700">Belum ada akun pengguna tambahan.</p>
                                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Tambah Pengguna" di atas untuk menambahkan akun baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div id="modalTambahUser" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Tambah Pengguna Baru</h3>
            <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Kata Sandi *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Peran (Hak Akses) *</label>
                    <select name="role" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="wali">Wali Kelas</option>
                        <option value="admin">Administrator Sekolah</option>
                        <option value="kepsek">Kepala Sekolah</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalTambahUser').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit User -->
    <div id="modalEditUser" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl p-6 border border-slate-100">
            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Ubah Data Pengguna</h3>
            <form id="formEditUser" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" id="edit_user_name" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" id="edit_user_email" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Kata Sandi Baru (Kosongkan jika tidak diganti)</label>
                    <input type="password" name="password" minlength="6" placeholder="Kosongkan jika tetap"
                        class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 mb-1">Peran (Hak Akses) *</label>
                    <select name="role" id="edit_user_role" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500">
                        <option value="wali">Wali Kelas</option>
                        <option value="admin">Administrator Sekolah</option>
                        <option value="kepsek">Kepala Sekolah</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-3">
                    <button type="button" onclick="document.getElementById('modalEditUser').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function editUser(user) {
        document.getElementById('formEditUser').action = '/users/' + user.id;
        document.getElementById('edit_user_name').value = user.name;
        document.getElementById('edit_user_email').value = user.email;
        document.getElementById('edit_user_role').value = user.role;
        document.getElementById('modalEditUser').classList.remove('hidden');
    }
</script>
@endpush
@endsection
