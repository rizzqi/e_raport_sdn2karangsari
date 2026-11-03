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
</head>
<body class="grid min-h-dvh bg-slate-50 text-slate-900 antialiased selection:bg-emerald-600 selection:text-white lg:grid-cols-[47%_53%]">

    <!-- PANEL KIRI: banner ramping di mobile, branding penuh di desktop -->
    <aside class="relative overflow-hidden bg-gradient-to-b from-emerald-950 via-emerald-900 to-emerald-950 text-white">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-40 -left-40 size-96 rounded-full bg-teal-500/20 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 size-80 rounded-full bg-emerald-400/10 blur-3xl"></div>
            <div class="absolute inset-0 bg-[radial-gradient(circle,rgba(255,255,255,0.07)_1px,transparent_1px)] [background-size:22px_22px]"></div>
        </div>

        <div class="relative z-10 flex h-full flex-col p-6 sm:p-10 lg:p-12">
            <div class="flex items-center gap-4 lg:flex-col lg:items-start lg:gap-5 lg:pt-12">
                <div class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-white/10 p-2 shadow-lg shadow-emerald-950/50 ring-1 ring-white/15 lg:size-20">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo SDN 2 Karangsari" class="h-full w-full object-contain">
                    @else
                        <i class="fa-solid fa-school text-2xl text-emerald-300"></i>
                    @endif
                </div>
                <div class="lg:mt-6">
                    <h1 class="text-lg font-extrabold uppercase leading-snug tracking-tight lg:text-2xl">SD Negeri 2 Karangsari</h1>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-emerald-300 lg:text-sm">E-Raport Kurikulum Merdeka</p>
                    <p class="mt-1 hidden text-xs text-emerald-100/60 lg:block">Kecamatan Sempu, Kabupaten Banyuwangi</p>
                </div>
            </div>

            <div class="my-auto hidden flex-col gap-4 sm:flex lg:my-8">
                <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-300/80">Aplikasi untuk guru &amp; admin</p>
                <ul class="space-y-3 lg:space-y-5">
                    <li class="flex items-center gap-3.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15"><i class="fa-solid fa-file-pen text-sm text-emerald-300"></i></span>
                        <span><p class="text-sm font-semibold">Kelola nilai &amp; raport digital</p><p class="text-xs text-emerald-100/60">Formatif, harian, hingga rekap semester</p></span>
                    </li>
                    <li class="flex items-center gap-3.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15"><i class="fa-solid fa-calendar-check text-sm text-emerald-300"></i></span>
                        <span><p class="text-sm font-semibold">Absensi harian &amp; rekap bulanan</p><p class="text-xs text-emerald-100/60">Kehadiran siswa tercatat rapi</p></span>
                    </li>
                    <li class="flex items-center gap-3.5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15"><i class="fa-solid fa-print text-sm text-emerald-300"></i></span>
                        <span><p class="text-sm font-semibold">Cetak raport, STS &amp; SAS</p><p class="text-xs text-emerald-100/60">Satu klik, siap cetak</p></span>
                    </li>
                </ul>
            </div>

            <div class="mt-auto hidden text-xs text-emerald-100/50 lg:block">
                Jl. Parijatah No. 45 Karangsari, Sempu, Banyuwangi
            </div>
        </div>
    </aside>

    <!-- PANEL KANAN: form login -->
    <main class="flex min-h-dvh flex-col px-4 py-8 sm:px-10 lg:px-16 xl:px-32">
        <div class="mx-auto my-auto w-full max-w-md">
            <div class="mb-6 sm:mb-8">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">Selamat Datang Kembali</h2>
                <p class="mt-1 text-sm text-slate-500">Masuk untuk mengelola nilai, raport, dan absensi.</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50 sm:p-8">
                @if(session('success'))
                    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs rounded-xl flex items-start gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-circle-check text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-emerald-950">Berhasil</p>
                            <p class="text-[11px] text-emerald-700 mt-0.5">{{ session('success') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 transition p-0.5">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-4 p-3.5 bg-blue-50 border border-blue-200 text-blue-900 text-xs rounded-xl flex items-start gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-circle-info text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-blue-950">Informasi</p>
                            <p class="text-[11px] text-blue-700 mt-0.5">{{ session('info') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700 transition p-0.5">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 p-3.5 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl flex items-start gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-amber-950">Peringatan</p>
                            <p class="text-[11px] text-amber-700 mt-0.5">{{ session('warning') }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700 transition p-0.5">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-xl flex items-start gap-2.5 shadow-2xs">
                        <div class="w-6 h-6 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0 mt-0.5">
                            <i class="fa-solid fa-circle-exclamation text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-rose-950">Gagal Masuk</p>
                            <p class="text-[11px] text-rose-700 mt-0.5">{{ $errors->first() }}</p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 transition p-0.5">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
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
                                title="Tampilkan / Sembunyikan sandi">
                                <i id="eye-icon" class="fa-regular fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center pt-0.5">
                        <label class="flex cursor-pointer select-none items-center">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
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
                <!-- Akses Cepat (Rapi & Terpadu) -->
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

                <!-- Status Helper Text -->
                <div class="flex items-center justify-between mb-2 px-0.5 min-h-[18px]">
                    <span class="text-[10px] font-semibold text-slate-500">Klik untuk isi otomatis:</span>
                    <span id="quick-status" class="text-[10px] font-semibold text-emerald-600 transition-all duration-200"></span>
                </div>

                <!-- Akun Administrator -->
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

                <!-- Akun Wali Kelas 1 s/d 6 -->
                <div class="grid grid-cols-2 gap-2">
                    <!-- Kelas 1 -->
                    <button type="button" onclick="setDemo('wali1@sdn2karangsari.sch.id', 'password', 'Kelas 1')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">1</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 1</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Dinda Asni F, S.Pd.">Dinda Asni F.</span>
                        </div>
                    </button>

                    <!-- Kelas 2 -->
                    <button type="button" onclick="setDemo('wali2@sdn2karangsari.sch.id', 'password', 'Kelas 2')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">2</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 2</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Umi Faiqotur Rizkiyani, S.Pd">Umi Faiqotur R.</span>
                        </div>
                    </button>

                    <!-- Kelas 3 -->
                    <button type="button" onclick="setDemo('wali3@sdn2karangsari.sch.id', 'password', 'Kelas 3')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">3</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 3</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Erna Sugiartiningsih, S.Pd">Erna Sugiartiningsih</span>
                        </div>
                    </button>

                    <!-- Kelas 4 -->
                    <button type="button" onclick="setDemo('wali4@sdn2karangsari.sch.id', 'password', 'Kelas 4')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">4</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 4</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Wahyu Siska Novita, S.Pd.">Wahyu Siska N.</span>
                        </div>
                    </button>

                    <!-- Kelas 5 -->
                    <button type="button" onclick="setDemo('wali5@sdn2karangsari.sch.id', 'password', 'Kelas 5')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">5</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 5</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Kiki Susanto, S.Pd">Kiki Susanto</span>
                        </div>
                    </button>

                    <!-- Kelas 6 -->
                    <button type="button" onclick="setDemo('wali6@sdn2karangsari.sch.id', 'password', 'Kelas 6')"
                        class="p-2 rounded-xl border border-slate-200/90 bg-slate-50/80 hover:bg-emerald-50/50 hover:border-emerald-300 text-left transition flex items-center gap-2 group">
                        <div class="w-7 h-7 rounded-lg bg-slate-200/80 group-hover:bg-emerald-600 group-hover:text-white text-slate-700 font-bold text-xs flex items-center justify-center shrink-0 transition">6</div>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold text-xs text-slate-800 block truncate group-hover:text-emerald-800 transition">Kelas 6</span>
                            <span class="text-[10px] text-slate-500 block truncate" title="Diah Puji Lestari, S.Pd">Diah Puji L.</span>
                        </div>
                    </button>
                </div>
                @endif
            </div>

            <footer class="mt-8 space-y-1 text-center text-xs text-slate-500">
                <p class="font-medium text-slate-600">&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka</p>
                <p class="text-[11px] text-slate-400">Jl. Parijatah No. 45 Karangsari, Sempu, Banyuwangi</p>
            </footer>
        </div>
    </main>

    <script>
        @if(config('app.demo_accounts'))
        function setDemo(email, pass, label) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            emailInput.value = email;
            passInput.value = pass;

            // Highlight visual feedback
            emailInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
            passInput.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500');
            setTimeout(() => {
                emailInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
                passInput.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500');
            }, 600);

            // Feedback status text
            const statusEl = document.getElementById('quick-status');
            if (statusEl) {
                statusEl.innerHTML = '<i class="fa-solid fa-check mr-1"></i>' + (label || 'Akun') + ' siap';
                setTimeout(() => {
                    statusEl.textContent = '';
                }, 2500);
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
