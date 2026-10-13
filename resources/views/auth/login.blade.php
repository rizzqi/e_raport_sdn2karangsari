<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - E-Raport SDN 2 Karangsari</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-emerald-600 text-white shadow-xl shadow-emerald-900/40 mb-4 transform hover:scale-105 transition">
                <i class="fa-solid fa-graduation-cap text-3xl"></i>
            </div>
            <h1 class="text-2xl font-black text-white tracking-wide">E-RAPORT SD</h1>
            <p class="text-emerald-400 font-semibold text-sm">SD NEGERI 2 KARANGSARI</p>
            <p class="text-xs text-slate-400 mt-1">Sistem Pengolahan & Pencetakan Raport STS / SAS</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-100">
            @if(session('info'))
                <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-700 text-xs rounded-lg flex items-center">
                    <i class="fa-solid fa-circle-info mr-2"></i> {{ session('info') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-lg flex items-center">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" name="email" id="email" required value="{{ old('email') }}"
                            class="w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                            placeholder="nama@sdn2karangsari.sch.id">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input type="password" name="password" id="password" required
                            class="w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                        Ingat Saya
                    </label>
                    <span class="text-slate-400">Tahun Ajaran 2026/2027</span>
                </div>

                <button type="submit"
                    class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 text-sm flex items-center justify-center">
                    <i class="fa-solid fa-right-to-bracket mr-2"></i> Masuk ke Aplikasi
                </button>
            </form>

            <!-- Quick Demo Accounts -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 text-center mb-3">Akun Demo (Klik untuk Isi Cepat)</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="setDemo('admin@sdn2karangsari.sch.id', 'password')"
                        class="p-2 rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-left transition">
                        <span class="font-bold text-slate-800 block"><i class="fa-solid fa-shield text-emerald-600 mr-1"></i> Admin</span>
                        <span class="text-[10px] text-slate-500">Kelola Sekolah</span>
                    </button>
                    <button type="button" onclick="setDemo('wali1@sdn2karangsari.sch.id', 'password')"
                        class="p-2 rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-left transition">
                        <span class="font-bold text-slate-800 block"><i class="fa-solid fa-chalkboard-user text-blue-600 mr-1"></i> Wali Kelas 1</span>
                        <span class="text-[10px] text-slate-500">Input & Cetak</span>
                    </button>
                    <button type="button" onclick="setDemo('wali4@sdn2karangsari.sch.id', 'password')"
                        class="p-2 rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-left transition">
                        <span class="font-bold text-slate-800 block"><i class="fa-solid fa-chalkboard-user text-blue-600 mr-1"></i> Wali Kelas 4</span>
                        <span class="text-[10px] text-slate-500">Input & Cetak</span>
                    </button>
                    <button type="button" onclick="setDemo('kepsek@sdn2karangsari.sch.id', 'password')"
                        class="p-2 rounded-lg border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-left transition">
                        <span class="font-bold text-slate-800 block"><i class="fa-solid fa-user-tie text-purple-600 mr-1"></i> Kepsek</span>
                        <span class="text-[10px] text-slate-500">Lihat Rekap</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setDemo(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
