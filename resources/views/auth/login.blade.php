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
        } elseif (file_exists(public_path('storage/sekolah/logo_kanan_6ace3397cbcc5.png'))) {
            $logoUrl = asset('storage/sekolah/logo_kanan_6ace3397cbcc5.png');
        }
    @endphp

    @if($logoUrl)
        <link rel="icon" type="image/jpeg" href="{{ $logoUrl }}">
    @endif

    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        @media (min-width: 1024px) {
            body { overflow-x: hidden; }
        }
    </style>
</head>
<body class="min-h-dvh bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white">

    <div class="relative flex min-h-dvh w-full flex-col lg:flex-row overflow-hidden">

        {{-- ============================================================ --}}
        {{-- PANEL KIRI: Background Gedung + Logo Sekolah + Info Branding --}}
        {{-- ============================================================ --}}
        <div class="relative flex min-h-[340px] w-full flex-col justify-between overflow-hidden bg-emerald-950 p-6 sm:p-10 lg:min-h-dvh lg:w-[48%] xl:w-[46%] lg:p-12">
            
            {{-- Background Foto Gedung Sekolah --}}
            <div class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-700 hover:scale-105"
                 style="background-image: url('{{ asset('images/gedung.jpg') }}');">
            </div>

            {{-- Overlay Gradien Hijau Zamrud Tebal & Lembut --}}
            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-950/95 via-emerald-900/85 to-teal-950/80 backdrop-blur-[1px]"></div>

            {{-- Elemen Pattern Dekoratif --}}
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle,rgba(255,255,255,0.08)_1px,transparent_1px)] [background-size:24px_24px]"></div>

            {{-- Header Atas: Logo Sekolah AI & Nama Sekolah --}}
            <div class="relative z-10 flex items-center gap-4 lg:gap-5">
                <div class="size-16 shrink-0 overflow-hidden rounded-2xl bg-white/10 p-1 shadow-2xl ring-2 ring-emerald-400/40 backdrop-blur-md sm:size-20 lg:size-24">
                    <img src="{{ asset('images/logo-sdn2.jpg') }}"
                         alt="Logo SDN 2 Karangsari"
                         class="h-full w-full rounded-xl object-cover shadow-inner">
                </div>
                <div class="text-white">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/25 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-emerald-300 ring-1 ring-emerald-400/30">
                        <i class="fa-solid fa-school text-[9px]"></i> SD Negeri
                    </span>
                    <h1 class="mt-1 text-xl font-black uppercase tracking-tight text-white drop-shadow sm:text-2xl lg:text-3xl">
                        SDN 2 Karangsari
                    </h1>
                    <p class="text-xs font-medium text-emerald-200/90 sm:text-sm">
                        Kecamatan Sempu, Kabupaten Banyuwangi
                    </p>
                </div>
            </div>

            {{-- Konten Tengah: Fitur Unggulan (Tampil di Tablet/Desktop) --}}
            <div class="relative z-10 my-auto hidden py-8 lg:block">
                <div class="mb-4 inline-flex items-center gap-2 rounded-lg bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300 ring-1 ring-emerald-400/20">
                    <i class="fa-solid fa-award"></i> Kurikulum Merdeka Terintegrasi
                </div>
                <h2 class="text-2xl font-extrabold text-white sm:text-3xl leading-snug drop-shadow-md">
                    Sistem E-Raport &amp;<br>
                    <span class="bg-gradient-to-r from-emerald-300 via-teal-200 to-amber-300 bg-clip-text text-transparent">
                        Administrasi Digital Sekolah
                    </span>
                </h2>
                <p class="mt-3 max-w-md text-xs leading-relaxed text-emerald-100/80 sm:text-sm">
                    Platform terpadu untuk pendataan nilai formatif, sumatif, absensi berkala, dan pencetakan buku raport secara akurat dan transparan.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="flex items-center gap-3 text-xs text-emerald-100/90">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <i class="fa-solid fa-check text-emerald-300 text-[11px]"></i>
                        </div>
                        <span>Penilaian Kurikulum Merdeka (Formatif &amp; Sumatif)</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-emerald-100/90">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <i class="fa-solid fa-check text-emerald-300 text-[11px]"></i>
                        </div>
                        <span>Rekapitulasi Absensi Otomatis Harian &amp; Bulanan</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-emerald-100/90">
                        <div class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                            <i class="fa-solid fa-check text-emerald-300 text-[11px]"></i>
                        </div>
                        <span>Cetak Dokumen Raport, STS, &amp; SAS Sekali Klik</span>
                    </div>
                </div>
            </div>

            {{-- Footer Bawah: Alamat --}}
            <div class="relative z-10 mt-4 text-[11px] text-emerald-200/70 sm:text-xs">
                <i class="fa-solid fa-location-dot mr-1.5 text-emerald-400"></i>
                Jl. Parijatah No. 45 Karangsari, Kec. Sempu, Kab. Banyuwangi, Jawa Timur
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- WAVE DIVIDER ORGANIK (ALAH MYUT)                            --}}
        {{-- Multi-layer gelombang yang memotong panel hijau ke putih     --}}
        {{-- ============================================================ --}}
        <div class="pointer-events-none absolute inset-y-0 left-[48%] xl:left-[46%] z-20 hidden -translate-x-[50%] lg:block" style="width: 140px; height: 100%;">
            <svg class="h-full w-full" viewBox="0 0 140 1000" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Drop shadow lembut di balik gelombang -->
                <path d="M 0 0 
                         C 70 120, 140 220, 50 360 
                         C -30 480, 130 620, 60 760 
                         C 0 880, 90 940, 70 1000 
                         L 140 1000 L 140 0 Z" 
                      fill="rgba(0, 0, 0, 0.08)" />

                <!-- Layer Gelombang Aksen 1: Emerald Cerah -->
                <path d="M 10 0 
                         C 80 120, 135 230, 55 365 
                         C -20 485, 125 625, 65 765 
                         C 5 885, 85 945, 65 1000 
                         L 140 1000 L 140 0 Z" 
                      fill="#059669" opacity="0.4" />

                <!-- Layer Gelombang Aksen 2: Teal / Emas Hangat -->
                <path d="M 25 0 
                         C 90 130, 130 240, 60 375 
                         C -10 495, 118 635, 72 775 
                         C 15 890, 80 950, 62 1000 
                         L 140 1000 L 140 0 Z" 
                      fill="#10b981" opacity="0.7" />

                <!-- Layer Utama: Putih Bersih menyatu ke Panel Kanan -->
                <path d="M 45 0 
                         C 105 140, 125 250, 68 385 
                         C 5 505, 110 645, 78 785 
                         C 25 895, 75 955, 60 1000 
                         L 140 1000 L 140 0 Z" 
                      fill="#f8fafc" />
            </svg>
        </div>

        {{-- ============================================================ --}}
        {{-- PANEL KANAN: Form Login Bersih & Modern                      --}}
        {{-- ============================================================ --}}
        <main class="relative z-10 flex flex-1 flex-col justify-center bg-slate-50 px-6 py-10 sm:px-12 lg:px-16 xl:px-24">
            <div class="mx-auto w-full max-w-md">

                {{-- Header Login --}}
                <div class="mb-8">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                        <i class="fa-solid fa-lock text-[10px]"></i> Portal Masuk Resmi
                    </span>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">
                        Selamat Datang
                    </h2>
                    <p class="mt-1.5 text-sm text-slate-500">
                        Silakan masukkan akun Anda untuk mengakses sistem E-Raport.
                    </p>
                </div>

                {{-- Alert Messages --}}
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

                {{-- Form Card --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-xl shadow-slate-200/60">
                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-xs font-bold text-slate-700">Alamat Email</label>
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
                            <label for="password" class="mb-1.5 block text-xs font-bold text-slate-700">Kata Sandi</label>
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

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex cursor-pointer select-none items-center">
                                <input type="checkbox" name="remember" class="mr-2 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-xs font-semibold text-slate-600">Ingat Saya</span>
                            </label>
                        </div>

                        <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition hover:-translate-y-0.5 hover:from-emerald-700 hover:to-teal-700 hover:shadow-emerald-600/40 active:translate-y-0">
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
                            <span class="bg-white px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                Akses Cepat Pengguna Demo
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-2.5 px-0.5 min-h-[18px]">
                        <span class="text-[10px] font-semibold text-slate-500">Klik untuk isi otomatis:</span>
                        <span id="quick-status" class="text-[10px] font-bold text-emerald-600 transition-all duration-200"></span>
                    </div>

                    {{-- Admin --}}
                    <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password', 'Administrator')"
                        class="w-full mb-2.5 p-2.5 rounded-xl border border-slate-200/90 bg-slate-50/90 hover:bg-emerald-50/70 hover:border-emerald-300 text-left transition flex items-center justify-between group">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center text-xs shrink-0 transition">
                                <i class="fa-solid fa-shield-halved text-xs"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-xs text-slate-800 group-hover:text-emerald-800 transition">Administrator</span>
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 uppercase">Admin</span>
                                </div>
                                <span class="text-[10px] text-slate-500 font-mono block truncate">admin@sdn2karangsari.sch.id</span>
                            </div>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400 group-hover:text-emerald-700 transition flex items-center gap-1 shrink-0 ml-2">
                            <span>Gunakan</span>
                            <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        </span>
                    </button>

                    {{-- Grid Guru Wali Kelas 1 s/d 6 --}}
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
                            class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/70 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
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

                {{-- Footer Info Hak Cipta --}}
                <footer class="mt-8 text-center text-xs text-slate-400">
                    <p class="font-medium text-slate-500">&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; E-Raport Kurikulum Merdeka</p>
                    <p class="mt-1 text-[11px] text-slate-400">Dikembangkan untuk kemudahan pendataan pendidikan sekolah dasar.</p>
                </footer>
            </div>
        </main>

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
                statusEl.innerHTML = '<i class="fa-solid fa-check mr-1"></i>' + (label || 'Akun') + ' siap digunakan';
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
