<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - E-Raport SD Negeri 2 Karangsari</title>

    @php
        try {
            $sekolah = $sekolah ?? (\Illuminate\Support\Facades\Schema::hasTable('sekolah') ? \App\Models\Sekolah::first() : null);
        } catch (\Throwable $e) {
            $sekolah = null;
        }
        $logoUrl = null;
        if ($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan))) {
            $logoUrl = asset('storage/' . $sekolah->logo_kanan);
        } elseif ($sekolah && $sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo))) {
            $logoUrl = asset('storage/' . $sekolah->logo);
        } elseif (file_exists(public_path('images/logo-sdn2.jpg'))) {
            $logoUrl = asset('images/logo-sdn2.jpg');
        }
    @endphp

    @if($logoUrl)
        <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
    @endif

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="relative min-h-dvh w-full overflow-x-hidden bg-slate-50 text-slate-800 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="relative flex min-h-dvh w-full flex-col lg:flex-row">

        {{-- ========================================================================= --}}
        {{-- PANEL KIRI: FOTO GEDUNG SEKOLAH SD DENGAN OVERLAY & BRANDING ELEGAN      --}}
        {{-- ========================================================================= --}}
        <div class="relative flex min-h-[380px] w-full flex-col justify-between overflow-hidden bg-emerald-950 p-6 sm:p-10 lg:min-h-dvh lg:w-[58%] xl:w-[60%] lg:p-12 xl:p-16">
            
            {{-- Foto Gedung Asli --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-1000 hover:scale-105"
                 style="background-image: url('{{ asset('images/gedung.jpg') }}');">
            </div>

            {{-- Overlay Gradien Halus Agar Foto Tetap Cerah dan Terlihat Bagus --}}
            <div class="absolute inset-0 bg-gradient-to-t from-emerald-950/90 via-emerald-950/40 to-black/30"></div>

            {{-- Header Kiri Atas: Logo Sekolah + Identitas Instansi --}}
            <div class="relative z-10 flex items-center gap-4">
                <div class="size-16 shrink-0 overflow-hidden rounded-2xl bg-white/20 p-1 shadow-2xl backdrop-blur-md ring-2 ring-white/30 sm:size-20">
                    <img src="{{ asset('images/logo-sdn2.jpg') }}" 
                         alt="Logo SDN 2 Karangsari" 
                         class="h-full w-full rounded-xl object-cover shadow-inner">
                </div>
                <div class="text-white">
                    <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/30 px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-widest text-emerald-200 backdrop-blur-sm ring-1 ring-emerald-400/30">
                        <i class="fa-solid fa-school text-[9px]"></i> SD Negeri
                    </div>
                    <h1 class="mt-1 text-xl font-black uppercase tracking-tight text-white drop-shadow-md sm:text-2xl lg:text-3xl">
                        SDN 2 Karangsari
                    </h1>
                    <p class="text-xs font-medium text-emerald-100/90 sm:text-sm">
                        Kecamatan Sempu, Kabupaten Banyuwangi
                    </p>
                </div>
            </div>

            {{-- Konten Tengah Kiri: Headline & Deskripsi Sistem --}}
            <div class="relative z-10 my-auto hidden py-8 lg:block">
                <div class="mb-4 inline-flex items-center gap-2 rounded-xl bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-emerald-200 backdrop-blur-md ring-1 ring-white/20">
                    <i class="fa-solid fa-graduation-cap text-amber-300"></i> Kurikulum Merdeka
                </div>
                <h2 class="text-3xl font-black text-white sm:text-4xl lg:text-5xl leading-tight drop-shadow-lg">
                    Sistem E-Raport &amp;<br>
                    <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-300 bg-clip-text text-transparent">
                        Administrasi Digital
                    </span>
                </h2>
                <p class="mt-4 max-w-lg text-xs leading-relaxed text-emerald-50/80 sm:text-sm lg:text-base drop-shadow">
                    Aplikasi pelaporan hasil belajar siswa terpadu untuk pendataan nilai formatif, sumatif, rekapitulasi absensi, dan pencetakan raport digital.
                </p>

                <div class="mt-8 flex flex-wrap gap-2.5">
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3.5 py-2 text-xs font-semibold text-white backdrop-blur-md ring-1 ring-white/15">
                        <i class="fa-solid fa-file-pen text-emerald-300"></i> Nilai Formatif &amp; Sumatif
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3.5 py-2 text-xs font-semibold text-white backdrop-blur-md ring-1 ring-white/15">
                        <i class="fa-solid fa-calendar-check text-emerald-300"></i> Absensi Otomatis
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3.5 py-2 text-xs font-semibold text-white backdrop-blur-md ring-1 ring-white/15">
                        <i class="fa-solid fa-print text-emerald-300"></i> Cetak Siap Pakai
                    </span>
                </div>
            </div>

            {{-- Footer Kiri Bawah --}}
            <div class="relative z-10 text-xs text-emerald-200/80">
                <i class="fa-solid fa-location-dot mr-1.5 text-amber-400"></i>
                Jl. Parijatah No. 45 Karangsari, Kec. Sempu, Kab. Banyuwangi, Jawa Timur
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- WAVE DIVIDER PRESISI TINGGI & TEBAL (LEBIH BESAR, CURVY & ELEGAN)        --}}
        {{-- Mengalir indah dari atas ke bawah membelah panel foto dan panel form       --}}
        {{-- ========================================================================= --}}
        <div class="pointer-events-none absolute inset-y-0 left-[58%] xl:left-[60%] z-20 hidden -translate-x-[99%] lg:block" style="width: 220px; height: 100%;">
            <svg class="h-full w-full" viewBox="0 0 220 1000" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    {{-- Gradien Emas / Amber Cerah Mewah --}}
                    <linearGradient id="waveGold" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#f59e0b" />
                        <stop offset="35%" stop-color="#fbbf24" />
                        <stop offset="70%" stop-color="#f59e0b" />
                        <stop offset="100%" stop-color="#d97706" />
                    </linearGradient>

                    {{-- Gradien Emerald Zamrud Pekat Elegan --}}
                    <linearGradient id="waveEmerald" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#047857" />
                        <stop offset="45%" stop-color="#10b981" />
                        <stop offset="85%" stop-color="#059669" />
                        <stop offset="100%" stop-color="#064e3b" />
                    </linearGradient>

                    {{-- Gradien Teal Bahari Modern --}}
                    <linearGradient id="waveTeal" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#0d9488" />
                        <stop offset="50%" stop-color="#14b8a6" />
                        <stop offset="100%" stop-color="#0f766e" />
                    </linearGradient>

                    {{-- Shadow Lembut untuk Efek 3D Timbul --}}
                    <filter id="waveSoftShadow" x="-20%" y="-10%" width="150%" height="120%">
                        <feDropShadow dx="-6" dy="4" stdDeviation="8" flood-color="#022c22" flood-opacity="0.25" />
                    </filter>
                </defs>

                {{-- Bentuk Latar Putih Pembatas (Menutup area kanan persis dengan background form #ffffff) --}}
                <path d="M 85 0 
                         C 190 180, 205 280, 110 430 
                         C 15 580, 185 710, 120 850 
                         C 70 930, 155 975, 95 1000 
                         L 220 1000 L 220 0 Z" 
                      fill="#ffffff" />

                {{-- Pita 1: Emas Hangat (Paling Luar menyentuh foto gedung, lebih tebal & terlihat jelas) --}}
                <path d="M 85 0 
                         C 190 180, 205 280, 110 430 
                         C 15 580, 185 710, 120 850 
                         C 70 930, 155 975, 95 1000" 
                      stroke="url(#waveGold)" 
                      stroke-width="24" 
                      stroke-linecap="round"
                      filter="url(#waveSoftShadow)" />

                {{-- Pita 2: Teal Bahari (Tengah) --}}
                <path d="M 94 0 
                         C 199 180, 214 280, 119 430 
                         C 24 580, 194 710, 129 850 
                         C 79 930, 164 975, 104 1000" 
                      stroke="url(#waveTeal)" 
                      stroke-width="15" 
                      stroke-linecap="round" />

                {{-- Pita 3: Emerald Zamrud Utama (Tebal & Menyatu Halus ke Form Putih) --}}
                <path d="M 103 0 
                         C 208 180, 223 280, 128 430 
                         C 33 580, 203 710, 138 850 
                         C 88 930, 173 975, 113 1000" 
                      stroke="url(#waveEmerald)" 
                      stroke-width="8" 
                      stroke-linecap="round" />
            </svg>
        </div>

        {{-- ========================================================================= --}}
        {{-- PANEL KANAN: FORM LOGIN BERSIH & PRESISI (DI SEBELAH KANAN)               --}}
        {{-- ========================================================================= --}}
        <main class="relative z-10 flex min-h-dvh flex-1 flex-col justify-between bg-white px-6 py-10 sm:px-12 md:px-16 lg:px-12 xl:px-16 shadow-xl">
            
            {{-- Badge Atas: Portal Resmi --}}
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200/60">
                    <i class="fa-solid fa-lock text-[10px]"></i> Portal Masuk Resmi
                </span>
                <span class="text-[11px] font-semibold text-slate-400">Tahun Ajaran Aktif</span>
            </div>

            {{-- Form Login Area --}}
            <div class="mx-auto my-auto w-full max-w-sm sm:max-w-md">
                
                {{-- Sambutan --}}
                <div class="mb-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Selamat Datang di</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        E-Raport Digital
                    </h2>
                    <p class="mt-1.5 text-xs font-medium text-slate-500 sm:text-sm">
                        Silakan masukkan kredensial akun Anda untuk mengelola nilai dan raport siswa.
                    </p>
                    <div class="mt-3 h-1 w-12 rounded-full bg-emerald-600"></div>
                </div>

                {{-- Alerts Notifikasi --}}
                @if(session('success'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                        <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                        <div class="flex-1 font-medium">{{ session('success') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-600"></i>
                        <div class="flex-1 font-medium">{{ session('info') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                        <div class="flex-1 font-medium">{{ session('warning') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-900">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-600"></i>
                        <div class="flex-1 font-medium">{{ $errors->first() }}</div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                {{-- Form Input --}}
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700">Email Pengguna / NIP</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-regular fa-envelope text-sm"></i>
                            </div>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-3.5 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                                placeholder="nama@sdn2karangsari.sch.id">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-bold text-slate-700">Kata Sandi</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-10 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600"
                                title="Tampilkan / Sembunyikan sandi">
                                <i id="eye-icon" class="fa-regular fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex cursor-pointer select-none items-center">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                            <span class="text-xs font-semibold text-slate-600">Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition duration-200 hover:-translate-y-0.5 hover:from-emerald-700 hover:to-teal-700 hover:shadow-emerald-600/35 active:translate-y-0">
                        <span>Masuk ke Akun</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>

                @if(config('app.demo_accounts'))
                {{-- Akses Cepat Akun Demo --}}
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="bg-white px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                            Akses Cepat Pengguna Demo
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-2 px-1 min-h-[16px]">
                    <span class="text-[10px] font-semibold text-slate-400">Klik untuk isi otomatis:</span>
                    <span id="quick-status" class="text-[10px] font-bold text-emerald-600 transition-all"></span>
                </div>

                {{-- Akun Administrator --}}
                <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password', 'Administrator')"
                    class="w-full mb-2 flex items-center justify-between rounded-xl border border-slate-200/90 bg-slate-50/80 p-2 text-left transition hover:bg-emerald-50/70 hover:border-emerald-300 group">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="flex size-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition">
                            <i class="fa-solid fa-shield-halved text-[11px]"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-xs text-slate-800 group-hover:text-emerald-800">Administrator</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 uppercase">Admin</span>
                            </div>
                            <span class="text-[10px] text-slate-400 font-mono block truncate">admin@sdn2karangsari.sch.id</span>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-400 group-hover:text-emerald-700 transition flex items-center gap-1">
                        Gunakan <i class="fa-solid fa-chevron-right text-[8px]"></i>
                    </span>
                </button>

                {{-- Grid Akun Wali Kelas 1-6 --}}
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach([
                        ['wali1@sdn2karangsari.sch.id', 'Kelas 1', 'Dinda Asni F.', '1'],
                        ['wali2@sdn2karangsari.sch.id', 'Kelas 2', 'Umi Faiqotur R.', '2'],
                        ['wali3@sdn2karangsari.sch.id', 'Kelas 3', 'Erna Sugiartiningsih', '3'],
                        ['wali4@sdn2karangsari.sch.id', 'Kelas 4', 'Wahyu Siska N.', '4'],
                        ['wali5@sdn2karangsari.sch.id', 'Kelas 5', 'Kiki Susanto', '5'],
                        ['wali6@sdn2karangsari.sch.id', 'Kelas 6', 'Diah Puji L.', '6'],
                    ] as [$email, $label, $teacher, $num])
                    <button type="button" onclick="setDemo('{{ $email }}', 'password', '{{ $label }}')"
                        class="flex items-center gap-2 rounded-xl border border-slate-200/90 bg-slate-50/70 p-1.5 text-left transition hover:bg-emerald-50/70 hover:border-emerald-300 group">
                        <div class="flex size-6 shrink-0 items-center justify-center rounded-lg bg-slate-200 text-xs font-bold text-slate-700 group-hover:bg-emerald-600 group-hover:text-white transition">
                            {{ $num }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-[11px] text-slate-800 block truncate group-hover:text-emerald-800">{{ $label }}</span>
                            <span class="text-[9px] text-slate-400 block truncate">{{ $teacher }}</span>
                        </div>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Footer Kanan Bawah --}}
            <footer class="text-center text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka</p>
            </footer>
        </main>

    </div>

    {{-- Script Demo & Toggle Password --}}
    <script>
        @if(config('app.demo_accounts'))
        function setDemo(email, pass, label) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            emailInput.value = email;
            passInput.value = pass;

            emailInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
            passInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
            setTimeout(() => {
                emailInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
                passInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
            }, 600);

            const statusEl = document.getElementById('quick-status');
            if (statusEl) {
                statusEl.innerHTML = '<i class="fa-solid fa-check mr-1"></i>' + (label || 'Akun') + ' siap';
                setTimeout(() => { statusEl.textContent = ''; }, 2500);
            }
        }
        @endif

        function togglePassword() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
