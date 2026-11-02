@extends('layouts.app')

@section('title', 'Profil & Ganti Kata Sandi')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-gear text-emerald-600 mr-2"></i> Pengaturan Profil & Kata Sandi
            </h1>
            <p class="text-xs text-slate-500">Kelola identitas profil akun Anda dan perbarui kata sandi secara berkala demi keamanan.</p>
        </div>
        <div class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 text-xs font-semibold">
            <i class="fa-solid fa-shield-halved text-emerald-600 mr-2"></i>
            Peran: <span class="capitalize ml-1 font-bold">{{ $user->role === 'admin' ? 'Administrator' : ($user->role === 'kepsek' ? 'Kepala Sekolah' : ($user->role === 'wali' ? 'Wali Kelas' : 'Guru')) }}</span>
        </div>
    </div>

    <!-- Alert Sukses / Galat -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Kartu Ringkasan Akun -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-black text-2xl flex items-center justify-center shadow-md">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500 font-mono mt-0.5 truncate">{{ $user->email }}</p>
                </div>
                <div class="pt-2 border-t border-slate-100 flex flex-col gap-1 text-[11px] text-slate-500">
                    <div class="flex justify-between py-1">
                        <span class="text-slate-400">Hak Akses</span>
                        <span class="font-bold text-emerald-700 uppercase tracking-wider text-[10px] bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">
                            {{ $user->role }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-400">Terdaftar</span>
                        <span class="font-medium text-slate-700">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulir Edit Profil & Sandi -->
        <div class="md:col-span-2 space-y-6">

            <!-- Formulir Data Profil -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
                    <i class="fa-solid fa-user text-emerald-600"></i> Informasi Pengguna
                </h3>

                <form action="{{ route('profil.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition">
                        @error('name')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email (Akun Login)</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-medium text-slate-500 cursor-not-allowed">
                        <p class="text-[10px] text-slate-400 mt-1">Alamat email diatur secara terpusat oleh Administrator Sekolah.</p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Nama</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Formulir Ganti Kata Sandi -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-1.5">
                    <i class="fa-solid fa-key text-emerald-600"></i> Ubah Kata Sandi
                </h3>

                <form action="{{ route('profil.password.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini</label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password" required
                                class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition"
                                placeholder="Masukkan kata sandi lama">
                            <button type="button" onclick="togglePass('current_password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                                <i class="fa-regular fa-eye text-xs"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru</label>
                            <div class="relative">
                                <input type="password" name="password" id="password" required minlength="6"
                                    class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition"
                                    placeholder="Minimal 6 karakter">
                                <button type="button" onclick="togglePass('password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                                    <i class="fa-regular fa-eye text-xs"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Ulangi Kata Sandi Baru</label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="6"
                                    class="w-full pl-3.5 pr-10 py-2.5 bg-slate-50 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition"
                                    placeholder="Konfirmasi kata sandi baru">
                                <button type="button" onclick="togglePass('password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                                    <i class="fa-regular fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>Perbarui Kata Sandi</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection
