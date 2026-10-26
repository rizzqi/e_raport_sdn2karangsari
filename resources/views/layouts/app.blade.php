<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - E-Raport SDN 2 Karangsari</title>
    @php
        try {
            $navSekolah = \Illuminate\Support\Facades\Schema::hasTable('sekolah') ? \App\Models\Sekolah::first() : null;
        } catch (\Throwable $e) {
            $navSekolah = null;
        }
        $navLogo = ($navSekolah && $navSekolah->logo_kanan && file_exists(public_path('storage/' . $navSekolah->logo_kanan)))
            ? asset('storage/' . $navSekolah->logo_kanan)
            : (($navSekolah && $navSekolah->logo && file_exists(public_path('storage/' . $navSekolah->logo))) ? asset('storage/' . $navSekolah->logo) : null);
        if (!$navLogo && file_exists(public_path('storage/sekolah/logo_kanan_6ace3397cbcc5.png'))) {
            $navLogo = asset('storage/sekolah/logo_kanan_6ace3397cbcc5.png');
        }
    @endphp
    @if($navLogo)
        <link rel="icon" type="image/png" href="{{ $navLogo }}">
    @endif
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                        },
                        school: {
                            blue: '#1e3a8a',
                            darkblue: '#172554',
                            accent: '#f59e0b',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar (Elegan & Modern) -->
    <header class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white shadow-lg shadow-slate-950/25 sticky top-0 z-40 no-print border-b border-indigo-500/20 backdrop-blur-md" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-white p-1 flex items-center justify-center shadow-md shadow-emerald-500/20 ring-2 ring-emerald-400/30 group-hover:scale-105 transition-all duration-200">
                            @if($navLogo)
                                <img src="{{ $navLogo }}" alt="Logo SDN 2 Karangsari" class="w-full h-full object-contain">
                            @else
                                <i class="fa-solid fa-graduation-cap text-emerald-700 text-lg"></i>
                            @endif
                        </div>
                        <div>
                            <span class="font-black text-base tracking-tight block leading-tight text-white group-hover:text-emerald-300 transition-colors">E-RAPORT</span>
                            <span class="text-[10px] text-emerald-400 font-bold block leading-none tracking-widest mt-0.5 uppercase">SDN 2 KARANGSARI</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1.5 text-xs font-semibold">
                    <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl transition-all duration-150 flex items-center gap-2 {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white font-bold shadow-xs border border-white/20 ring-1 ring-white/10' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        <i class="fa-solid fa-house w-4 text-center {{ request()->routeIs('dashboard') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('kelas.index') }}" class="px-3.5 py-2 rounded-xl transition-all duration-150 flex items-center gap-2 {{ request()->routeIs('kelas.*') || request()->routeIs('nilai.*') ? 'bg-white/15 text-white font-bold shadow-xs border border-white/20 ring-1 ring-white/10' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        <i class="fa-solid fa-chalkboard-user w-4 text-center {{ request()->routeIs('kelas.*') || request()->routeIs('nilai.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Kelas & Nilai</span>
                    </a>

                    <a href="{{ route('siswa.index') }}" class="px-3.5 py-2 rounded-xl transition-all duration-150 flex items-center gap-2 {{ request()->routeIs('siswa.*') ? 'bg-white/15 text-white font-bold shadow-xs border border-white/20 ring-1 ring-white/10' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        <i class="fa-solid fa-user-graduate w-4 text-center {{ request()->routeIs('siswa.*') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Data Siswa</span>
                    </a>

                    <a href="{{ route('raport.riwayat') }}" class="px-3.5 py-2 rounded-xl transition-all duration-150 flex items-center gap-2 {{ request()->routeIs('raport.riwayat') ? 'bg-white/15 text-white font-bold shadow-xs border border-white/20 ring-1 ring-white/10' : 'text-slate-300 hover:text-white hover:bg-white/10' }}">
                        <i class="fa-solid fa-clock-rotate-left w-4 text-center {{ request()->routeIs('raport.riwayat') ? 'text-emerald-400' : 'text-slate-400' }}"></i>
                        <span>Riwayat Cetak</span>
                    </a>

                    @if(auth()->user()->isAdmin())
                        <!-- Dropdown Pengaturan -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                            <button @click="open = !open" type="button"
                                class="px-3.5 py-2 rounded-xl transition-all duration-150 text-slate-300 hover:text-white hover:bg-white/10 flex items-center gap-2 focus:outline-none select-none"
                                :class="{ 'bg-white/15 text-white font-bold shadow-xs border border-white/20': open }">
                                <i class="fa-solid fa-gear w-4 text-center text-slate-400"></i>
                                <span>Pengaturan</span>
                                <i class="fa-solid fa-chevron-down text-[10px] opacity-75 transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                 class="absolute right-0 mt-2 w-60 bg-white rounded-2xl shadow-2xl py-1.5 text-slate-800 text-xs border border-slate-100 z-50 divide-y divide-slate-100"
                                 style="display: none;">
                                <div class="py-1">
                                    <a href="{{ route('sekolah.edit') }}" @click="open = false"
                                       class="flex items-center px-4 py-2.5 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center mr-2.5 shrink-0">
                                            <i class="fa-solid fa-school text-xs"></i>
                                        </div>
                                        <div>
                                            <span class="block font-bold">Identitas Sekolah</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Profil & informasi resmi</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('tahun-ajaran.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2.5 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mr-2.5 shrink-0">
                                            <i class="fa-solid fa-calendar text-xs"></i>
                                        </div>
                                        <div>
                                            <span class="block font-bold">Tahun Ajaran</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Semester & kalender</span>
                                        </div>
                                    </a>
                                    <a href="{{ route('mapel.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2.5 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center mr-2.5 shrink-0">
                                            <i class="fa-solid fa-book text-xs"></i>
                                        </div>
                                        <div>
                                            <span class="block font-bold">Mapel & Pembiasaan</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Kurikulum & profil pelajar</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('users.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2.5 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <div class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center mr-2.5 shrink-0">
                                            <i class="fa-solid fa-users text-xs"></i>
                                        </div>
                                        <div>
                                            <span class="block font-bold">Kelola Pengguna</span>
                                            <span class="text-[10px] text-slate-400 font-normal">Akun guru & wali kelas</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </nav>

                <!-- User Profile & Action Buttons -->
                <div class="flex items-center space-x-2.5">
                    <!-- User Pill Badge -->
                    <div class="hidden sm:flex items-center gap-2 py-1 px-2.5 rounded-xl bg-white/10 border border-white/15">
                        <div class="w-7 h-7 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-400 text-white font-black text-xs flex items-center justify-center shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="text-left">
                            <span class="block text-xs font-bold leading-none text-white truncate max-w-[130px]">{{ auth()->user()->name }}</span>
                            <span class="inline-block text-[9px] uppercase font-extrabold tracking-wider text-emerald-300 mt-0.5">
                                {{ auth()->user()->role === 'admin' ? 'Administrator' : 'Wali Kelas' }}
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar dari Aplikasi" class="w-8 h-8 rounded-xl bg-rose-500/15 hover:bg-rose-600 text-rose-300 hover:text-white transition-all duration-150 flex items-center justify-center border border-rose-500/20 shadow-2xs hover:scale-105">
                            <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                        </button>
                    </form>

                    <!-- Mobile Hamburger Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="md:hidden w-8 h-8 rounded-xl hover:bg-white/10 text-slate-200 transition flex items-center justify-center focus:outline-none" title="Menu Navigasi">
                        <i class="fa-solid text-sm" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-white/10 bg-[#0f172a] px-4 pt-3 pb-4 space-y-1.5 text-xs font-semibold"
             style="display: none;">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white font-bold' : 'text-slate-200 hover:bg-white/10' }}">
                <i class="fa-solid fa-house w-5 mr-2 text-emerald-400"></i> Dashboard
            </a>
            <a href="{{ route('kelas.index') }}" class="block px-3 py-2 rounded-xl transition {{ request()->routeIs('kelas.*') || request()->routeIs('nilai.*') ? 'bg-white/15 text-white font-bold' : 'text-slate-200 hover:bg-white/10' }}">
                <i class="fa-solid fa-chalkboard-user w-5 mr-2 text-emerald-400"></i> Kelas & Nilai
            </a>
            <a href="{{ route('siswa.index') }}" class="block px-3 py-2 rounded-xl transition {{ request()->routeIs('siswa.*') ? 'bg-white/15 text-white font-bold' : 'text-slate-200 hover:bg-white/10' }}">
                <i class="fa-solid fa-user-graduate w-5 mr-2 text-emerald-400"></i> Data Siswa
            </a>
            <a href="{{ route('raport.riwayat') }}" class="block px-3 py-2 rounded-xl transition {{ request()->routeIs('raport.riwayat') ? 'bg-white/15 text-white font-bold' : 'text-slate-200 hover:bg-white/10' }}">
                <i class="fa-solid fa-clock-rotate-left w-5 mr-2 text-emerald-400"></i> Riwayat Cetak
            </a>

            @if(auth()->user()->isAdmin())
                <div class="pt-2 border-t border-white/10 mt-2">
                    <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pengaturan Administrator</p>
                    <a href="{{ route('sekolah.edit') }}" class="block px-3 py-2 rounded-xl hover:bg-white/10 transition text-slate-200">
                        <i class="fa-solid fa-school w-5 mr-2 text-emerald-400"></i> Identitas Sekolah
                    </a>
                    <a href="{{ route('tahun-ajaran.index') }}" class="block px-3 py-2 rounded-xl hover:bg-white/10 transition text-slate-200">
                        <i class="fa-solid fa-calendar w-5 mr-2 text-emerald-400"></i> Tahun Ajaran
                    </a>
                    <a href="{{ route('mapel.index') }}" class="block px-3 py-2 rounded-xl hover:bg-white/10 transition text-slate-200">
                        <i class="fa-solid fa-book w-5 mr-2 text-emerald-400"></i> Mapel & Pembiasaan
                    </a>
                    <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded-xl hover:bg-white/10 transition text-slate-200">
                        <i class="fa-solid fa-users w-5 mr-2 text-emerald-400"></i> Kelola Pengguna
                    </a>
                </div>
            @endif
        </div>
    </header>

    <!-- Global Floating Toast Notification System (Modern, Auto-dismiss, Context-aware) -->
    <div class="fixed top-20 right-4 sm:right-6 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none no-print">
        @if(session('success'))
            @php
                $msg = session('success');
                $isDelete = preg_match('/hapus|kosongkan/i', $msg);
                $isLogin = preg_match('/selamat datang|masuk/i', $msg);
            @endphp
            <div x-data="{ show: true, progress: 100 }"
                 x-init="
                    let timer = setInterval(() => { progress -= 2; if (progress <= 0) { show = false; clearInterval(timer); } }, 100);
                 "
                 x-show="show"
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-slate-200 overflow-hidden">
                <div class="p-4 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl {{ $isDelete ? 'bg-rose-100 text-rose-600' : ($isLogin ? 'bg-emerald-100 text-emerald-600' : 'bg-emerald-100 text-emerald-700') }} flex items-center justify-center shrink-0 text-sm shadow-xs">
                        @if($isDelete)
                            <i class="fa-solid fa-trash-can"></i>
                        @elseif($isLogin)
                            <i class="fa-solid fa-user-check"></i>
                        @else
                            <i class="fa-solid fa-circle-check"></i>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p class="text-xs font-bold text-slate-900">
                            @if($isDelete)
                                Data Berhasil Dihapus
                            @elseif($isLogin)
                                Autentikasi Berhasil
                            @else
                                Data Berhasil Disimpan
                            @endif
                        </p>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words">{{ $msg }}</p>
                    </div>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1 -mr-1 -mt-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <!-- Progress bar timer -->
                <div class="h-1 bg-slate-100 w-full">
                    <div class="h-full {{ $isDelete ? 'bg-rose-500' : 'bg-emerald-500' }} transition-all duration-100 ease-linear" :style="`width: ${progress}%`"></div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true, progress: 100 }"
                 x-init="
                    let timer = setInterval(() => { progress -= 2; if (progress <= 0) { show = false; clearInterval(timer); } }, 100);
                 "
                 x-show="show"
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-rose-200 overflow-hidden">
                <div class="p-4 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 text-sm shadow-xs">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p class="text-xs font-bold text-rose-900">Terjadi Kesalahan</p>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1 -mr-1 -mt-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div class="h-1 bg-slate-100 w-full">
                    <div class="h-full bg-rose-500 transition-all duration-100 ease-linear" :style="`width: ${progress}%`"></div>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div x-data="{ show: true, progress: 100 }"
                 x-init="
                    let timer = setInterval(() => { progress -= 2; if (progress <= 0) { show = false; clearInterval(timer); } }, 100);
                 "
                 x-show="show"
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-blue-200 overflow-hidden">
                <div class="p-4 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 text-sm shadow-xs">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p class="text-xs font-bold text-blue-900">Informasi</p>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words">{{ session('info') }}</p>
                    </div>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1 -mr-1 -mt-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div class="h-1 bg-slate-100 w-full">
                    <div class="h-full bg-blue-500 transition-all duration-100 ease-linear" :style="`width: ${progress}%`"></div>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div x-data="{ show: true, progress: 100 }"
                 x-init="
                    let timer = setInterval(() => { progress -= 2; if (progress <= 0) { show = false; clearInterval(timer); } }, 100);
                 "
                 x-show="show"
                 x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="pointer-events-auto bg-white rounded-2xl shadow-xl shadow-slate-900/15 border border-amber-200 overflow-hidden">
                <div class="p-4 flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 text-sm shadow-xs">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="flex-1 min-w-0 pt-0.5">
                        <p class="text-xs font-bold text-amber-900">Peringatan</p>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed break-words">{{ session('warning') }}</p>
                    </div>
                    <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1 -mr-1 -mt-1 rounded-lg">
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                </div>
                <div class="h-1 bg-slate-100 w-full">
                    <div class="h-full bg-amber-500 transition-all duration-100 ease-linear" :style="`width: ${progress}%`"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Flash Notifications (Validation Errors Container) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full no-print">
        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl shadow-xs mb-4">
                <div class="flex items-start">
                    <div class="w-8 h-8 rounded-xl bg-rose-500/15 border border-rose-500/20 text-rose-700 flex items-center justify-center mr-3 shrink-0 mt-0.5">
                        <i class="fa-solid fa-circle-exclamation text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs sm:text-sm font-bold text-rose-900">Mohon perbaiki kesalahan berikut:</p>
                        <ul class="list-disc list-inside text-xs text-rose-700 mt-1 space-y-0.5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer Elegan & Profesional -->
    <footer class="bg-white border-t border-slate-200/80 py-4 text-center text-xs text-slate-500 no-print mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-slate-500">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-md bg-emerald-600 text-white flex items-center justify-center text-[10px] font-black">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <span class="font-medium">&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; E-Raport Kurikulum Merdeka</span>
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-400">
                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold border border-slate-200/60">Versi 2.0</span>
                <span>Banyuwangi, Jawa Timur</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
