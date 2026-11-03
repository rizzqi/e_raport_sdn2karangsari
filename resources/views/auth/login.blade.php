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

    <style>
        /* S-Curve Clip-Path ala MyUT:
           Mulai dari x: 50% di atas (y: 0%),
           Melengkung cembung ke kanan hingga x: 62% di y: 28%,
           Melengkung cekung ke kiri hingga x: 42% di y: 65%,
           Melengkung cembung keluar ke kanan lagi hingga x: 58% di y: 100%
        */
        @media (min-width: 1024px) {
            .myut-left-panel {
                clip-path: polygon(
                    0% 0%,
                    50% 0%,
                    53% 8%,
                    57% 18%,
                    60% 26%,
                    61% 32%,
                    59% 40%,
                    54% 48%,
                    47% 56%,
                    43% 64%,
                    42% 72%,
                    44% 80%,
                    49% 88%,
                    54% 95%,
                    57% 100%,
                    0% 100%
                );
            }
        }
    </style>
</head>
<body class="relative min-h-dvh w-full overflow-x-hidden bg-slate-100 text-slate-800 antialiased selection:bg-emerald-600 selection:text-white">

    {{-- ========================================================================= --}}
    {{-- BACKGROUND LAYER KANAN: FOTO GEDUNG SEKOLAH FULL SCREEN                  --}}
    {{-- ========================================================================= --}}
    <div class="fixed inset-0 z-0 hidden lg:block">
        <div class="h-full w-full bg-cover bg-center bg-no-repeat"
             style="background-image: url('{{ asset('images/gedung.jpg') }}');">
        </div>
        {{-- Subtle light overlay --}}
        <div class="absolute inset-0 bg-slate-900/10 backdrop-brightness-[0.98]"></div>

        {{-- Badge Kanan Atas (ala Direktorat MyUT) --}}
        <div class="absolute right-8 top-8 z-10 flex items-center gap-3 rounded-2xl bg-white/90 px-4 py-2.5 shadow-lg backdrop-blur-md ring-1 ring-black/5">
            <div class="flex size-9 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-600/30">
                <i class="fa-solid fa-graduation-cap text-base"></i>
            </div>
            <div class="leading-tight">
                <p class="text-[10px] font-extrabold uppercase tracking-widest text-emerald-700">Kemendikbudristek</p>
                <p class="text-xs font-bold text-slate-800">Kurikulum Merdeka</p>
            </div>
        </div>

        {{-- Carousel Dots Indikator Kanan Bawah (ala MyUT) --}}
        <div class="absolute bottom-8 right-12 z-10 flex items-center gap-2">
            <span class="size-2.5 rounded-full bg-white/80 shadow"></span>
            <span class="h-2.5 w-6 rounded-full bg-emerald-500 shadow-md"></span>
            <span class="size-2.5 rounded-full bg-white/80 shadow"></span>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- WAVE SVG RIBBONS (Pita Gelombang Multi-layer Berkelok di Batas Kurva)     --}}
    {{-- ========================================================================= --}}
    <div class="pointer-events-none fixed inset-0 z-20 hidden lg:block">
        <svg class="h-full w-full" viewBox="0 0 1440 900" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                {{-- Gradien Emas / Amber Hangat --}}
                <linearGradient id="ribbonAmber" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#f59e0b" />
                    <stop offset="40%" stop-color="#fbbf24" />
                    <stop offset="80%" stop-color="#d97706" />
                    <stop offset="100%" stop-color="#b45309" />
                </linearGradient>

                {{-- Gradien Emerald / Hijau Sekolah Elegan --}}
                <linearGradient id="ribbonEmerald" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#059669" />
                    <stop offset="35%" stop-color="#10b981" />
                    <stop offset="70%" stop-color="#047857" />
                    <stop offset="100%" stop-color="#064e3b" />
                </linearGradient>

                {{-- Gradien Teal / Biru-Hijau Laut --}}
                <linearGradient id="ribbonTeal" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#0d9488" />
                    <stop offset="50%" stop-color="#14b8a6" />
                    <stop offset="100%" stop-color="#0f766e" />
                </linearGradient>

                {{-- Shadow untuk Memberikan Kedalaman --}}
                <filter id="waveDropShadow" x="-10%" y="-10%" width="130%" height="130%">
                    <feDropShadow dx="6" dy="4" stdDeviation="10" flood-color="#0f172a" flood-opacity="0.22" />
                </filter>
            </defs>

            {{-- Ribbon 1: Tebal Luar - Emas Hangat (Amber) --}}
            <path d="M 720 0 
                     C 920 220, 930 330, 830 460 
                     C 720 600, 560 670, 640 810 
                     C 680 870, 770 940, 810 1000" 
                  stroke="url(#ribbonAmber)" 
                  stroke-width="26" 
                  stroke-linecap="round" 
                  opacity="0.95" 
                  filter="url(#waveDropShadow)" />

            {{-- Ribbon 2: Tengah - Teal Modern --}}
            <path d="M 720 0 
                     C 905 220, 915 330, 820 460 
                     C 715 600, 570 670, 645 810 
                     C 680 870, 765 940, 800 1000" 
                  stroke="url(#ribbonTeal)" 
                  stroke-width="16" 
                  stroke-linecap="round" 
                  opacity="0.85" />

            {{-- Ribbon 3: Dalam - Emerald Zamrud Pekat --}}
            <path d="M 720 0 
                     C 890 220, 900 330, 810 460 
                     C 710 600, 580 670, 650 810 
                     C 680 870, 755 940, 790 1000" 
                  stroke="url(#ribbonEmerald)" 
                  stroke-width="8" 
                  stroke-linecap="round" />
        </svg>
    </div>

    {{-- ========================================================================= --}}
    {{-- PANEL KIRI: LATAR PUTIH BERSIH DENGAN POTONGAN S-CURVE (PERSIS MYUT)      --}}
    {{-- Di sinilah Brand Sekolah, Header, dan Form Login Berada                  --}}
    {{-- ========================================================================= --}}
    <div class="relative z-10 flex min-h-dvh w-full flex-col justify-between bg-white px-6 py-8 sm:px-12 md:px-16 lg:w-[62%] lg:px-14 lg:py-10 xl:w-[58%] xl:px-20 myut-left-panel shadow-2xl">

        {{-- Header Kiri Atas: Logo & Nama Instansi Resmi --}}
        <header class="flex items-center gap-3.5">
            <div class="size-13 shrink-0 overflow-hidden rounded-2xl p-0.5 shadow-md ring-1 ring-slate-200">
                <img src="{{ asset('images/logo-sdn2.jpg') }}" 
                     alt="Logo SD Negeri 2 Karangsari" 
                     class="h-full w-full rounded-[14px] object-cover">
            </div>
            <div class="leading-tight">
                <h1 class="text-sm font-black tracking-tight text-slate-800 uppercase sm:text-base">
                    SD Negeri 2 Karangsari
                </h1>
                <p class="text-[11px] font-semibold text-emerald-700 tracking-wide">
                    Kecamatan Sempu, Kabupaten Banyuwangi
                </p>
                <p class="text-[10px] text-slate-400">
                    Sistem Pelaporan Hasil Belajar Siswa Terpadu
                </p>
            </div>
        </header>

        {{-- Center Content: Branding MyUT Style + Form Login --}}
        <main class="my-auto py-6 lg:max-w-md xl:max-w-lg">
            
            {{-- Ucapan Selamat Datang ala MyUT --}}
            <div class="mb-6 text-center sm:text-left">
                <p class="text-sm font-semibold text-slate-500">Selamat datang di</p>
                <div class="mt-1 flex items-baseline justify-center sm:justify-start gap-2">
                    <span class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                        E-Raport
                    </span>
                    <span class="inline-block rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-3 py-1 text-xs font-black uppercase tracking-wider text-white shadow-md shadow-emerald-600/30">
                        SDN 2
                    </span>
                </div>
                <div class="mt-2.5 h-1 w-12 rounded-full bg-emerald-600 hidden sm:block"></div>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                    <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                    <div class="flex-1">{{ session('success') }}</div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('info'))
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                    <i class="fa-solid fa-circle-info mt-0.5 text-blue-600"></i>
                    <div class="flex-1">{{ session('info') }}</div>
                    <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                    <div class="flex-1">{{ session('warning') }}</div>
                    <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            @if(isset($errors) && $errors->any())
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-900">
                    <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-600"></i>
                    <div class="flex-1">{{ $errors->first() }}</div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif

            {{-- Form Login Sederhana & Bersih (ala MyUT input rounded-xl) --}}
            <form action="{{ route('login.post') }}" method="POST" class="space-y-3.5">
                @csrf

                <div>
                    <label for="email" class="mb-1 block text-xs font-bold text-slate-700">Email Pengguna / NIP</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-regular fa-envelope text-sm"></i>
                        </div>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 py-3 pl-10 pr-3 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
                            placeholder="nama@sdn2karangsari.sch.id">
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-1 block text-xs font-bold text-slate-700">Kata Sandi</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </div>
                        <input type="password" name="password" id="password" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50/80 py-3 pl-10 pr-10 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-600/10"
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

                {{-- Tombol Masuk Mirip MyUT (Border emerald kuat / solid rounded-xl) --}}
                <button type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-extrabold text-white shadow-md shadow-emerald-600/30 transition duration-200 hover:bg-emerald-700 hover:shadow-lg active:translate-y-0.5">
                    <span>Masuk</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

            @if(config('app.demo_accounts'))
            {{-- Menu Link Akses Bawah (ala MyUT Accordion List) --}}
            <div class="mt-6 rounded-2xl border border-slate-200/90 bg-white/80 p-3 shadow-xs backdrop-blur-sm">
                <div class="flex items-center justify-between mb-2 px-1">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Akses Cepat Pengguna</span>
                    <span id="quick-status" class="text-[10px] font-bold text-emerald-600"></span>
                </div>

                {{-- Admin Button --}}
                <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password', 'Admin')"
                    class="w-full mb-1.5 flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50/70 p-2 text-left transition hover:bg-emerald-50 hover:border-emerald-200 group">
                    <div class="flex items-center gap-2">
                        <div class="flex size-6 items-center justify-center rounded-lg bg-emerald-600 text-[10px] text-white">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800 group-hover:text-emerald-800">Administrator</p>
                            <p class="text-[10px] text-slate-400 font-mono">admin@sdn2karangsari.sch.id</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-emerald-600 group-hover:underline">Gunakan</span>
                </button>

                {{-- Grid Wali Kelas --}}
                <div class="grid grid-cols-2 gap-1.5">
                    @foreach([
                        ['wali1@sdn2karangsari.sch.id', 'Kelas 1', 'Dinda Asni F.'],
                        ['wali2@sdn2karangsari.sch.id', 'Kelas 2', 'Umi Faiqotur R.'],
                        ['wali3@sdn2karangsari.sch.id', 'Kelas 3', 'Erna Sugiartiningsih'],
                        ['wali4@sdn2karangsari.sch.id', 'Kelas 4', 'Wahyu Siska N.'],
                        ['wali5@sdn2karangsari.sch.id', 'Kelas 5', 'Kiki Susanto'],
                        ['wali6@sdn2karangsari.sch.id', 'Kelas 6', 'Diah Puji L.'],
                    ] as [$email, $label, $teacher])
                    <button type="button" onclick="setDemo('{{ $email }}', 'password', '{{ $label }}')"
                        class="flex items-center gap-1.5 rounded-lg border border-slate-100 bg-slate-50/70 p-1.5 text-left transition hover:bg-emerald-50 hover:border-emerald-200 group">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded bg-slate-200 text-[10px] font-bold text-slate-700 group-hover:bg-emerald-600 group-hover:text-white">
                            {{ substr($label, -1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[11px] font-bold text-slate-800 group-hover:text-emerald-800">{{ $label }}</p>
                            <p class="truncate text-[9px] text-slate-400">{{ $teacher }}</p>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

        </main>

        {{-- Footer Kiri Bawah --}}
        <footer class="pt-2 text-xs text-slate-400">
            <p>&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka</p>
        </footer>
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
