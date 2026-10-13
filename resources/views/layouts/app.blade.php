<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - E-Raport SDN 2 Karangsari</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; color: black !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="bg-school-darkblue text-white shadow-md sticky top-0 z-40 no-print" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand & Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-lg bg-emerald-600 flex items-center justify-center text-white shadow-md font-bold text-xl">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <span class="font-extrabold text-lg tracking-tight block leading-tight">E-RAPORT</span>
                            <span class="text-xs text-emerald-300 font-medium block leading-none">SDN 2 KARANGSARI</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-1 text-sm font-medium">
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                        <i class="fa-solid fa-house mr-1.5"></i> Dashboard
                    </a>

                    <a href="{{ route('kelas.index') }}" class="px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('kelas.*') || request()->routeIs('nilai.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                        <i class="fa-solid fa-chalkboard-user mr-1.5"></i> Kelas & Nilai
                    </a>

                    <a href="{{ route('siswa.index') }}" class="px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('siswa.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                        <i class="fa-solid fa-user-graduate mr-1.5"></i> Data Siswa
                    </a>

                    <a href="{{ route('raport.riwayat') }}" class="px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('raport.riwayat') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                        <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Riwayat Cetak
                    </a>

                    @if(auth()->user()->isAdmin())
                        <!-- Dropdown Pengaturan dengan Click Toggle (Tidak mudah tertutup) -->
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
                            <button @click="open = !open" type="button"
                                class="px-3 py-2 rounded-md hover:bg-slate-800 transition text-slate-200 flex items-center font-medium focus:outline-none select-none"
                                :class="{ 'bg-slate-800 text-emerald-400 font-semibold': open }">
                                <i class="fa-solid fa-gear mr-1.5"></i> Pengaturan
                                <i class="fa-solid fa-chevron-down ml-1.5 text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
                            </button>
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl py-1 text-slate-800 text-sm border border-slate-200 z-50 divide-y divide-slate-100"
                                 style="display: none;">
                                <div class="py-1">
                                    <a href="{{ route('sekolah.edit') }}" @click="open = false"
                                       class="flex items-center px-4 py-2 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <i class="fa-solid fa-school w-5 mr-2 text-emerald-600"></i> Identitas Sekolah
                                    </a>
                                    <a href="{{ route('tahun-ajaran.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <i class="fa-solid fa-calendar w-5 mr-2 text-emerald-600"></i> Tahun Ajaran
                                    </a>
                                    <a href="{{ route('mapel.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <i class="fa-solid fa-book w-5 mr-2 text-emerald-600"></i> Mapel & Pembiasaan
                                    </a>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('users.index') }}" @click="open = false"
                                       class="flex items-center px-4 py-2 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 transition font-medium">
                                        <i class="fa-solid fa-users w-5 mr-2 text-emerald-600"></i> Kelola Pengguna
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </nav>

                <!-- User Profile & Action Buttons -->
                <div class="flex items-center space-x-3">
                    <div class="text-right hidden sm:block">
                        <span class="block text-sm font-semibold leading-tight">{{ auth()->user()->name }}</span>
                        <span class="inline-block px-2 py-0.5 text-[10px] uppercase font-bold tracking-wider rounded bg-emerald-700/80 text-emerald-100">
                            {{ auth()->user()->role }}
                        </span>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar dari Aplikasi" class="p-2 rounded-md bg-red-600/20 hover:bg-red-600 text-red-300 hover:text-white transition">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>

                    <!-- Mobile Hamburger Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="md:hidden p-2 rounded-md hover:bg-slate-800 text-slate-200 transition focus:outline-none" title="Menu Navigasi">
                        <i class="fa-solid text-lg" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'"></i>
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
             class="md:hidden border-t border-slate-800 bg-school-darkblue px-4 pt-2 pb-4 space-y-1 text-sm font-medium"
             style="display: none;">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                <i class="fa-solid fa-house mr-2"></i> Dashboard
            </a>
            <a href="{{ route('kelas.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('kelas.*') || request()->routeIs('nilai.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                <i class="fa-solid fa-chalkboard-user mr-2"></i> Kelas & Nilai
            </a>
            <a href="{{ route('siswa.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('siswa.*') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                <i class="fa-solid fa-user-graduate mr-2"></i> Data Siswa
            </a>
            <a href="{{ route('raport.riwayat') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition {{ request()->routeIs('raport.riwayat') ? 'bg-slate-800 text-emerald-400 font-semibold' : 'text-slate-200' }}">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i> Riwayat Cetak
            </a>

            @if(auth()->user()->isAdmin())
                <div class="pt-2 border-t border-slate-800">
                    <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pengaturan Administrator</p>
                    <a href="{{ route('sekolah.edit') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition text-slate-200">
                        <i class="fa-solid fa-school mr-2 text-emerald-400"></i> Identitas Sekolah
                    </a>
                    <a href="{{ route('tahun-ajaran.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition text-slate-200">
                        <i class="fa-solid fa-calendar mr-2 text-emerald-400"></i> Tahun Ajaran
                    </a>
                    <a href="{{ route('mapel.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition text-slate-200">
                        <i class="fa-solid fa-book mr-2 text-emerald-400"></i> Mapel & Pembiasaan
                    </a>
                    <a href="{{ route('users.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 transition text-slate-200">
                        <i class="fa-solid fa-users mr-2 text-emerald-400"></i> Kelola Pengguna
                    </a>
                </div>
            @endif
        </div>
    </header>

    <!-- Flash Notifications -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full no-print">
        @if(session('success'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 rounded-r-lg shadow-sm mb-4 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg mr-3"></i>
                    <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm mb-4 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg mr-3"></i>
                    <p class="text-sm text-rose-800 font-medium">{{ session('error') }}</p>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg shadow-sm mb-4">
                <div class="flex items-start">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg mr-3 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold text-rose-800">Mohon perbaiki kesalahan berikut:</p>
                        <ul class="list-disc list-inside text-xs text-rose-700 mt-1">
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

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500 no-print">
        <div class="max-w-7xl mx-auto px-4">
            <p>&copy; {{ date('Y') }} SD Negeri 2 Karangsari Banyuwangi &bull; Sistem Informasi E-Raport Kurikulum Merdeka</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
