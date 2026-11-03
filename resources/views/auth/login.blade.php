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
        } elseif (file_exists(public_path('storage/sekolah/logo_kanan_6ace3397cbcc5.png'))) {
            $logoUrl = asset('storage/sekolah/logo_kanan_6ace3397cbcc5.png');
        }
    @endphp

    @if($logoUrl)
        <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    @endif

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body { overflow: hidden; }
        @media (max-width: 1023px) { body { overflow: auto; } }
    </style>
</head>
<body class="min-h-dvh bg-white text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="relative flex min-h-dvh w-full overflow-hidden">

        {{-- ============================================================ --}}
        {{-- PANEL KIRI: Logo + Form Login (latar putih bersih)           --}}
        {{-- ============================================================ --}}
        <div class="relative z-10 flex w-full flex-col bg-white lg:w-[48%] xl:w-[44%]">

            {{-- Header logo --}}
            <div class="flex items-center gap-3 px-8 pt-8 sm:px-12 lg:px-10 xl:px-14">
                <img src="{{ asset('images/logo-sdn2.svg') }}"
                     alt="Logo SD Negeri 2 Karangsari"
                     class="h-14 w-14 shrink-0 drop-shadow-md">
                <div class="leading-tight">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-700">SD Negeri 2</p>
                    <p class="text-sm font-extrabold uppercase tracking-wide text-slate-800">Karangsari</p>
                    <p class="text-[10px] text-slate-400">Sempu, Banyuwangi</p>
                </div>
            </div>

            {{-- Form area --}}
            <div class="my-auto px-8 py-10 sm:px-12 lg:px-10 xl:px-14">
                <div class="mb-7">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Selamat Datang</h1>
                    <p class="mt-1.5 text-sm text-slate-500">Masuk untuk mengelola nilai, raport, dan absensi.</p>
                </div>

                {{-- Alert: success --}}
                @if(session('success'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs shadow-2xs">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-emerald-950">Berhasil</p>
                            <p class="mt-0.5 text-[11px] text-emerald-700">{{ session('success') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" aria-label="Tutup notifikasi" class="p-0.5 text-emerald-500 transition hover:text-emerald-700">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                {{-- Alert: info --}}
                @if(session('info'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 p-3.5 text-xs shadow-2xs">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-blue-950">Informasi</p>
                            <p class="mt-0.5 text-[11px] text-blue-700">{{ session('info') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" aria-label="Tutup notifikasi" class="p-0.5 text-blue-500 transition hover:text-blue-700">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                {{-- Alert: warning --}}
                @if(session('warning'))
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs shadow-2xs">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                            <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-amber-950">Peringatan</p>
                            <p class="mt-0.5 text-[11px] text-amber-700">{{ session('warning') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" aria-label="Tutup notifikasi" class="p-0.5 text-amber-500 transition hover:text-amber-700">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                {{-- Alert: errors --}}
                @if(isset($errors) && $errors->any())
                    <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs shadow-2xs">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                            <i class="fa-solid fa-circle-exclamation text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-rose-950">Gagal Masuk</p>
                            <p class="mt-0.5 text-[11px] text-rose-700">{{ $errors->first() }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" aria-label="Tutup notifikasi" class="p-0.5 text-rose-500 transition hover:text-rose-700">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                {{-- Login form --}}
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-700">Alamat Email</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-regular fa-envelope text-sm"></i>
                            </div>
                            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-3.5 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                placeholder="nama@sdn2karangsari.sch.id">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata Sandi</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3 pl-10 pr-10 text-sm font-medium text-slate-900 transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-500/10"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600"
                                title="Tampilkan / Sembunyikan sandi" aria-label="Tampilkan kata sandi">
                                <i id="eye-icon" class="fa-regular fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center pt-0.5">
                        <label class="flex cursor-pointer select-none items-center">
                            <input type="checkbox" name="remember" class="mr-2 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-slate-700">Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 transition hover:-translate-y-0.5 hover:from-emerald-700 hover:to-teal-700 hover:shadow-emerald-600/35 active:translate-y-0">
                        <span>Masuk ke Aplikasi</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>

                @if(config('app.demo_accounts'))
                {{-- Divider demo --}}
                <div class="relative my-5">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center text-xs">
                        <span class="bg-white px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Akses Cepat Pengguna
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between mb-2 px-0.5 min-h-[18px]">
                    <span class="text-[10px] font-semibold text-slate-500">Klik untuk isi otomatis:</span>
                    <span id="quick-status" class="text-[10px] font-semibold text-emerald-600 transition-all duration-200"></span>
                </div>

                {{-- Admin --}}
                <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password', 'Administrator')"
                    class="w-full mb-2 p-2.5 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center justify-between group">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 flex items-center justify-center text-xs shrink-0 transition">
                            <i class="fa-solid fa-shield-halved text-[11px]"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-xs text-slate-800 group-hover:text-emerald-800 transition">Administrator</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-200/70 text-slate-600 uppercase">Admin</span>
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono block truncate">admin@sdn2karangsari.sch.id</span>
                        </div>
                    </div>
                    <span class="text-[11px] font-semibold text-slate-400 group-hover:text-emerald-700 transition flex items-center gap-1 shrink-0 ml-2">
                        <span>Gunakan</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                    </span>
                </button>

                {{-- Wali kelas grid --}}
                <div class="grid grid-cols-2 gap-2">
                    @foreach([
                        ['wali1@sdn2karangsari.sch.id', 'Kelas 1', 'Dinda Asni F.', '1'],
                        ['wali2@sdn2karangsari.sch.id', 'Kelas 2', 'Umi Faiqotur R.', '2'],
                        ['wali3@sdn2karangsari.sch.id', 'Kelas 3', 'Erna Sugiartiningsih', '3'],
                        ['wali4@sdn2karangsari.sch.id', 'Kelas 4', 'Wahyu Siska N.', '4'],
                        ['wali5@sdn2karangsari.sch.id', 'Kelas 5', 'Kiki Susanto', '5'],
                        ['wali6@sdn2karangsari.sch.id', 'Kelas 6', 'Diah Puji L.', '6'],
                    ] as [$email, $label, $teacher, $num])
                    <button type="button" onclick="setDemo('{{ $email }}', 'password', '{{ $label }}')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">{{ $num }}</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">{{ $label }}</span>
                            <span class="text-[10px] text-slate-500 block truncate">{{ $teacher }}</span>
                        </div>
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <footer class="px-8 pb-6 text-center text-xs text-slate-400 sm:px-12 lg:px-10 xl:px-14">
                <p>&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka</p>
                <p class="text-[10px]">Jl. Parijatah No. 45 Karangsari, Sempu, Banyuwangi</p>
            </footer>
        </div>

        {{-- ============================================================ --}}
        {{-- WAVE DIVIDER SVG — absolut, menutupi batas kiri-kanan        --}}
        {{-- ============================================================ --}}
        <div class="pointer-events-none absolute inset-y-0 left-0 z-20 hidden lg:block" style="width: 56%; min-width: 400px;">
            <svg class="absolute inset-y-0 right-0 h-full" style="width: 120px; margin-right: -1px;"
                 viewBox="0 0 120 900" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Shadow/depth layer -->
                <path d="M 0 0 L 60 0
                         C 100 100, 20 200, 80 300
                         C 120 380, 10 470, 70 560
                         C 110 640, 20 730, 75 820
                         C 95 860, 60 880, 60 900
                         L 0 900 Z"
                      fill="rgba(0,0,0,0.15)"/>
                <!-- Emerald accent wave behind white -->
                <path d="M 0 0 L 55 0
                         C 95 100, 15 200, 75 300
                         C 115 380, 5 470, 65 560
                         C 105 640, 15 730, 70 820
                         C 90 860, 55 880, 55 900
                         L 0 900 Z"
                      fill="#059669"/>
                <!-- Main white wave -->
                <path d="M 0 0 L 48 0
                         C 90 100, 8 200, 68 300
                         C 108 380, 0 470, 58 560
                         C 100 640, 8 730, 64 820
                         C 86 860, 50 880, 50 900
                         L 0 900 Z"
                      fill="white"/>
            </svg>
        </div>

        {{-- ============================================================ --}}
        {{-- PANEL KANAN: Foto Gedung Sekolah                             --}}
        {{-- ============================================================ --}}
        <div class="relative hidden flex-1 overflow-hidden lg:flex">
            {{-- Background gedung --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                 style="background-image: url('{{ asset('images/gedung.jpg') }}');">
            </div>

            {{-- Overlay gradien hijau agar teks terbaca --}}
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-950/60 via-emerald-900/30 to-transparent"></div>

            {{-- Konten atas (badge) --}}
            <div class="relative z-10 flex w-full flex-col justify-between p-10">
                <div class="flex justify-end">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-xs font-semibold text-white backdrop-blur-sm ring-1 ring-white/20">
                        <i class="fa-solid fa-graduation-cap text-emerald-300"></i>
                        <span>Kurikulum Merdeka</span>
                    </div>
                </div>

                {{-- Quote/tagline bawah --}}
                <div class="max-w-xs">
                    <p class="text-2xl font-extrabold leading-tight text-white drop-shadow-lg sm:text-3xl">
                        E-Raport<br>
                        <span class="text-emerald-300">Digital</span>
                    </p>
                    <p class="mt-2 text-sm font-medium text-white/80">
                        Sistem penilaian dan pelaporan berbasis digital untuk SD Negeri 2 Karangsari.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600/80 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                            <i class="fa-solid fa-file-pen text-[9px]"></i> Nilai & Raport
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600/80 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                            <i class="fa-solid fa-calendar-check text-[9px]"></i> Absensi
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600/80 px-3 py-1 text-[11px] font-semibold text-white backdrop-blur-sm">
                            <i class="fa-solid fa-print text-[9px]"></i> Cetak
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

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
