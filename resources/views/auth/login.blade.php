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
        if (file_exists(public_path('images/logo-sdn2.png'))) {
            $logoUrl = asset('images/logo-sdn2.png');
        } elseif (file_exists(public_path('images/logo-sdn2.jpg'))) {
            $logoUrl = asset('images/logo-sdn2.jpg');
        } elseif ($sekolah && $sekolah->logo_kanan && file_exists(public_path('storage/' . $sekolah->logo_kanan))) {
            $logoUrl = asset('storage/' . $sekolah->logo_kanan);
        } elseif ($sekolah && $sekolah->logo && file_exists(public_path('storage/' . $sekolah->logo))) {
            $logoUrl = asset('storage/' . $sekolah->logo);
        }
    @endphp

    @if($logoUrl)
        <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    @endif

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="relative min-h-screen w-full overflow-x-hidden bg-[#0a1e3f] text-slate-800 antialiased selection:bg-blue-600 selection:text-white flex flex-col justify-between">

    {{-- ========================================================================= --}}
    {{-- BACKGROUND DEKORATIF: OVERLAY FOTO + GRADASI BIRU RESMI + GELOMBANG EMAS  --}}
    {{-- ========================================================================= --}}
    <div class="pointer-events-none absolute inset-0 z-0 overflow-hidden">
        {{-- Foto Gedung / Lingkungan Sekolah sebagai Ambient Texture --}}
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-25 mix-blend-luminosity scale-105"
             style="background-image: url('{{ asset('images/gedung.jpg') }}');">
        </div>

        {{-- Gradasi Biru Navy Elegan Khas Pendidikan (Persis Mockup) --}}
        <div class="absolute inset-0 bg-gradient-to-br from-[#0c2b5e]/95 via-[#0d346c]/90 to-[#07172f]/95"></div>

        {{-- Ornamen Gelombang Pojok Kiri Atas (Kuning Emas, Cyan & Biru Royal) --}}
        <div class="absolute -top-12 -left-12 w-[340px] sm:w-[460px] h-[340px] sm:h-[460px]">
            <svg viewBox="0 0 400 400" class="h-full w-full" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0,0 L 260,0 C 260,150 150,260 0,260 Z" fill="#f59e0b" opacity="0.95" />
                <path d="M 0,0 L 220,0 C 220,130 130,220 0,220 Z" fill="#0284c7" opacity="0.85" />
                <path d="M 0,0 L 180,0 C 180,100 100,180 0,180 Z" fill="#1e40af" opacity="0.9" />
            </svg>
        </div>

        {{-- Pola Titik-Titik Dekoratif (Dot Matrix) Kiri Atas --}}
        <div class="absolute top-20 left-12 sm:left-24 w-36 h-36 opacity-30"
             style="background-image: radial-gradient(#ffffff 2px, transparent 2px); background-size: 16px 16px;">
        </div>

        {{-- Ornamen Gelombang Bawah (Emas & Biru Royal) --}}
        <div class="absolute bottom-12 left-0 w-80 h-32">
            <svg viewBox="0 0 320 120" class="h-full w-full" fill="none">
                <path d="M 0,40 C 100,30 180,90 280,120 L 0,120 Z" fill="#f59e0b" opacity="0.9" />
                <path d="M 0,70 C 90,60 160,100 240,120 L 0,120 Z" fill="#1d4ed8" opacity="0.8" />
            </svg>
        </div>

        <div class="absolute bottom-12 right-0 w-64 h-28">
            <svg viewBox="0 0 260 110" class="h-full w-full" fill="none">
                <path d="M 120,110 C 180,60 220,50 260,30 L 260,110 Z" fill="#f59e0b" opacity="0.9" />
                <path d="M 160,110 C 200,80 230,70 260,50 L 260,110 Z" fill="#1d4ed8" opacity="0.8" />
            </svg>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- BAGIAN ATAS: LOGO & IDENTITAS INSTANSI (TANPA TAHUN AJARAN)                --}}
    {{-- ========================================================================= --}}
    <header class="relative z-20 w-full px-6 pt-6 sm:px-12 sm:pt-8 flex items-center justify-between">
        {{-- Pill Brand Header (Putih dengan Logo & Nama Sekolah) --}}
        <div class="inline-flex items-center gap-3.5 rounded-full bg-white px-5 py-2.5 shadow-xl shadow-black/15 ring-1 ring-slate-100/90">
            <div class="size-11 sm:size-12 shrink-0 overflow-hidden rounded-full bg-slate-50 p-1 flex items-center justify-center">
                <img src="{{ $logoUrl }}" alt="Logo SDN 2 Karangsari" class="h-full w-full object-contain">
            </div>
            <div class="border-l border-slate-200 pl-3">
                <h1 class="text-sm sm:text-base font-black uppercase tracking-wider text-[#0a2558]">
                    SDN 2 KARANGSARI
                </h1>
                <p class="text-[11px] sm:text-xs font-medium text-slate-500">
                    Kecamatan Sempu, Kabupaten Banyuwangi
                </p>
            </div>
        </div>

        {{-- Tahun Ajaran Dikosongkan sesuai permintaan pengguna --}}
    </header>

    {{-- ========================================================================= --}}
    {{-- KONTEN UTAMA: HERO SAMBUTAN DI KIRI & FLOATING KARTU LOGIN DI KANAN        --}}
    {{-- ========================================================================= --}}
    <main class="relative z-10 w-full max-w-7xl mx-auto px-6 py-8 sm:px-12 lg:py-12 flex flex-col lg:flex-row items-center justify-between gap-10 lg:gap-14 flex-1 my-auto">
        
        {{-- SISI KIRI: Headline Selamat Datang & Informasi Sistem --}}
        <div class="w-full lg:max-w-xl text-white">
            <p class="text-lg sm:text-2xl font-medium tracking-tight text-white/90">
                Selamat Datang di
            </p>
            <h2 class="mt-1 text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-none drop-shadow-lg">
                E-Rapor <span class="text-amber-400">Digital</span>
            </h2>
            <h3 class="mt-2 text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white tracking-tight drop-shadow-md">
                SDN 2 Karangsari
            </h3>

            {{-- Garis Aksen Emas (Persis Mockup) --}}
            <div class="mt-4 mb-5 h-1.5 w-16 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></div>

            <p class="text-sm sm:text-base text-blue-100/90 leading-relaxed max-w-lg drop-shadow">
                Aplikasi pelaporan hasil belajar siswa secara digital, cepat, dan akurat.
            </p>

            {{-- Badges Fitur: Transparan & Akurat, Mudah & Aman --}}
            <div class="mt-8 flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-3 rounded-2xl bg-blue-600/35 px-4 py-2.5 backdrop-blur-md ring-1 ring-blue-400/30 shadow-lg">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-blue-500 text-white shadow-inner">
                        <i class="fa-solid fa-chart-line text-sm"></i>
                    </div>
                    <div class="leading-tight">
                        <span class="block text-xs font-bold text-white">Transparan</span>
                        <span class="block text-[11px] font-medium text-blue-200">&amp; Akurat</span>
                    </div>
                </div>

                <div class="hidden sm:block h-8 w-px bg-white/20"></div>

                <div class="inline-flex items-center gap-3 rounded-2xl bg-blue-600/35 px-4 py-2.5 backdrop-blur-md ring-1 ring-blue-400/30 shadow-lg">
                    <div class="flex size-9 items-center justify-center rounded-xl bg-blue-500 text-white shadow-inner">
                        <i class="fa-solid fa-shield-halved text-sm"></i>
                    </div>
                    <div class="leading-tight">
                        <span class="block text-xs font-bold text-white">Mudah</span>
                        <span class="block text-[11px] font-medium text-blue-200">&amp; Aman</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- SISI KANAN: FLOATING LOGIN CARD MODERN (PERSIS MOCKUP) --}}
        <div class="w-full lg:max-w-md">
            <div class="relative rounded-[2.5rem] bg-white p-7 sm:p-10 shadow-2xl shadow-black/40 border border-white/80 overflow-hidden">
                
                {{-- Aksen Melengkung Biru-Kuning di Pojok Kiri Atas Kartu (Persis Mockup) --}}
                <div class="pointer-events-none absolute -top-12 -left-12 size-36">
                    <svg viewBox="0 0 100 100" class="h-full w-full">
                        <path d="M 0,0 L 75,0 C 75,45 45,75 0,75 Z" fill="#2563eb" opacity="0.95" />
                        <path d="M 0,0 L 58,0 C 58,35 35,58 0,58 Z" fill="#f59e0b" />
                    </svg>
                </div>

                {{-- Header Kartu: Logo + Garis Aksen Emas --}}
                <div class="text-center pt-2">
                    <div class="flex items-center justify-center gap-3">
                        <span class="h-0.5 w-10 sm:w-12 rounded-full bg-amber-400"></span>
                        <div class="size-16 sm:size-20 shrink-0 p-1 flex items-center justify-center">
                            <img src="{{ $logoUrl }}" alt="Logo SDN 2 Karangsari" class="max-h-full max-w-full object-contain">
                        </div>
                        <span class="h-0.5 w-10 sm:w-12 rounded-full bg-amber-400"></span>
                    </div>

                    <h2 class="mt-3 text-xl sm:text-2xl font-black uppercase tracking-tight text-[#0a2558]">
                        SDN 2 KARANGSARI
                    </h2>
                    <p class="text-xs sm:text-sm font-bold text-slate-700 mt-0.5">
                        E-Rapor Digital
                    </p>
                    <p class="mt-1 text-xs text-slate-500 font-medium">
                        Silakan masuk dengan akun Anda untuk mengakses aplikasi.
                    </p>
                </div>

                {{-- Alerts Notifikasi --}}
                @if(session('success'))
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                        <i class="fa-solid fa-circle-check mt-0.5 text-emerald-600"></i>
                        <div class="flex-1 font-medium">{{ session('success') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-600"></i>
                        <div class="flex-1 font-medium">{{ session('info') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                        <div class="flex-1 font-medium">{{ session('warning') }}</div>
                        <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-900">
                        <i class="fa-solid fa-circle-exclamation mt-0.5 text-rose-600"></i>
                        <div class="flex-1 font-medium">{{ $errors->first() }}</div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                @endif

                {{-- Form Input --}}
                <form action="{{ route('login.post') }}" method="POST" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 flex items-center gap-1.5 text-xs font-bold text-[#0a2558]">
                            <i class="fa-solid fa-user text-[11px] text-[#0a2558]"></i> Email Pengguna / NIP
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-regular fa-envelope text-sm"></i>
                            </div>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-3.5 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-600/10"
                                placeholder="nama@sdn2karangsari.sch.id">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 flex items-center gap-1.5 text-xs font-bold text-[#0a2558]">
                            <i class="fa-solid fa-lock text-[11px] text-[#0a2558]"></i> Kata Sandi
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-10 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-600/10"
                                placeholder="Masukkan kata sandi">
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600"
                                title="Tampilkan / Sembunyikan sandi">
                                <i id="eye-icon" class="fa-regular fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-0.5 text-xs">
                        <label class="flex cursor-pointer select-none items-center">
                            <input type="checkbox" name="remember" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 mr-2">
                            <span class="font-medium text-slate-600">Ingat Saya</span>
                        </label>
                        <span class="font-semibold text-blue-600 hover:text-blue-800 cursor-pointer">Lupa Kata Sandi?</span>
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-600/30 transition duration-200 hover:-translate-y-0.5 active:translate-y-0">
                        <i class="fa-solid fa-arrow-right-to-bracket text-sm"></i>
                        <span>Masuk ke Akun</span>
                    </button>
                </form>

                {{-- Akses Cepat Demo (Jika Demo Diaktifkan) --}}
                @if(config('app.demo_accounts'))
                <div class="mt-6 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Akses Cepat Demo:</span>
                        <span id="quick-status" class="text-[10px] font-bold text-blue-600"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password', 'Admin')"
                            class="rounded-lg bg-blue-50 hover:bg-blue-100 p-1.5 text-center transition">
                            <span class="block text-[11px] font-bold text-blue-800">Admin</span>
                        </button>
                        <button type="button" onclick="setDemo('wali1@sdn2karangsari.sch.id', 'password', 'Wali 1')"
                            class="rounded-lg bg-slate-100 hover:bg-blue-50 p-1.5 text-center transition">
                            <span class="block text-[11px] font-bold text-slate-700">Wali 1</span>
                        </button>
                        <button type="button" onclick="setDemo('wali6@sdn2karangsari.sch.id', 'password', 'Wali 6')"
                            class="rounded-lg bg-slate-100 hover:bg-blue-50 p-1.5 text-center transition">
                            <span class="block text-[11px] font-bold text-slate-700">Wali 6</span>
                        </button>
                    </div>
                </div>
                @endif

            </div>
        </div>

    </main>

    {{-- ========================================================================= --}}
    {{-- FOOTER BAWAH: ALAMAT & HAK CIPTA (PERSIS MOCKUP)                          --}}
    {{-- ========================================================================= --}}
    <footer class="relative z-20 w-full bg-white/95 backdrop-blur-md border-t border-slate-200/80 px-6 py-3 sm:px-12 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-600 gap-2 shadow-inner">
        <div class="flex items-center gap-1.5 text-[11px] sm:text-xs">
            <i class="fa-solid fa-location-dot text-blue-600"></i>
            <span>Jl. Panjatan No. 45 Karangsari, Kec. Sempu, Kab. Banyuwangi, Jawa Timur</span>
        </div>
        <div class="text-[11px] sm:text-xs text-slate-500">
            &copy; {{ date('Y') }} SDN 2 Karangsari &bull; Kurikulum Merdeka
        </div>
    </footer>

    {{-- Script Demo & Toggle Password --}}
    <script>
        @if(config('app.demo_accounts'))
        function setDemo(email, pass, label) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            emailInput.value = email;
            passInput.value = pass;

            emailInput.classList.add('ring-2', 'ring-blue-500', 'border-blue-500');
            passInput.classList.add('ring-2', 'ring-blue-500', 'border-blue-500');
            setTimeout(() => {
                emailInput.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500');
                passInput.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500');
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
