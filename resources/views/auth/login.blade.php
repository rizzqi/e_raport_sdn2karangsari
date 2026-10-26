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

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-50 via-emerald-50/20 to-slate-100 min-h-screen flex items-center justify-center p-4 antialiased text-slate-800 relative overflow-x-hidden selection:bg-emerald-600 selection:text-white">

    <!-- Ambient Decorative Soft Glow (Terang & Segar) -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-emerald-200/35 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-teal-200/25 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-[440px] w-full my-6 relative z-10">
        
        <!-- Brand Header (Terang, Elegan, & Resmi) -->
        <div class="flex flex-col items-center text-center mb-6">
            <div class="w-20 h-20 rounded-2xl bg-white p-2 shadow-lg shadow-emerald-950/5 ring-1 ring-slate-200 mb-3.5 flex items-center justify-center">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="Logo SDN 2 Karangsari" class="w-full h-full object-contain">
                @else
                    <i class="fa-solid fa-school text-emerald-600 text-3xl"></i>
                @endif
            </div>

            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight uppercase leading-snug">
                SD NEGERI 2 KARANGSARI
            </h1>
            <p class="text-xs font-bold text-emerald-700 tracking-wider uppercase mt-1">
                E-Raport Kurikulum Merdeka
            </p>
            <p class="text-[11px] text-slate-500 mt-0.5">
                Kecamatan Sempu, Kabupaten Banyuwangi
            </p>
        </div>

        <!-- Login Card (Putih Bersih dengan Bayangan Halus) -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/80 p-6 sm:p-7 border border-slate-200/80">
            
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

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-regular fa-envelope text-xs"></i>
                        </div>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                            class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition placeholder:text-slate-400"
                            placeholder="nama@sdn2karangsari.sch.id">
                    </div>
                </div>

                <!-- Password Input with Toggle -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </div>
                        <input type="password" name="password" id="password" required
                            class="w-full pl-10 pr-10 py-2.5 bg-slate-50/70 border border-slate-200 focus:bg-white focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 transition placeholder:text-slate-400"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                            title="Tampilkan / Sembunyikan sandi">
                            <i id="eye-icon" class="fa-regular fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center text-xs pt-0.5">
                    <label class="flex items-center text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2 h-4 w-4">
                        <span class="font-medium text-slate-700">Ingat Saya</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 hover:shadow-emerald-600/30 transition-all flex items-center justify-center gap-2">
                    <span>Masuk ke Aplikasi</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>

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
        </div>
        
        <!-- Footer Sekolah -->
        <div class="text-center text-xs text-slate-500 mt-6 space-y-0.5">
            <p class="font-medium text-slate-600">
                &copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka
            </p>
            <p class="text-[11px] text-slate-400">
                Jl. Parijatah No. 45 Karangsari, Sempu, Banyuwangi
            </p>
        </div>
    </div>

    <script>
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
