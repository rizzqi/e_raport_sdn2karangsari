# Login Page Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mendesain ulang halaman login menjadi split-panel premium (branding emerald gelap + form terang) dengan migrasi ke Tailwind v4 yang di-build Vite.

**Architecture:** Satu Blade view dirancang ulang (`resources/views/auth/login.blade.php`) — panel kiri berperan sebagai banner branding di mobile dan panel branding penuh di desktop (grid `lg:`). CSS via `resources/css/app.css` (Tailwind v4 + Instrument Sans, sudah ter-setup). Fungsionalitas (CSRF, route, alert, demo accounts, toggle password) tidak berubah.

**Tech Stack:** Laravel Blade, Tailwind CSS v4 (`@tailwindcss/vite`), Vite, Font Awesome 6.5.1 (CDN).

## Global Constraints

- Jangan ubah route, controller, atau database
- Pertahankan semua logika: `route('login.post')`, `@csrf`, `old()`, `config('app.demo_accounts')`, fallback logo sekolah, semua alert session, `setDemo()` & `togglePassword()`
- Ikon pakai Font Awesome (konsisten dengan `layouts/app.blade.php`), bukan CSS lain
- Font: Instrument Sans (sudah di-load vite via bunny fonts)
- Tanpa aset gambar baru — pattern & glow murni CSS
- TDD: test ditulis dulu, merah → hijau

---

### Task 1: Test render halaman login (merah dulu)

**Files:**
- Create: `tests/Feature/LoginPageTest.php`

- [ ] **Step 1: Tulis test yang gagal**

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_renders_branding_and_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Selamat Datang Kembali');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
    }
}
```

- [ ] **Step 2: Jalankan, pastikan GAGAL** — Run: `php artisan test --compact --filter=LoginPageTest` → Expected: FAIL (`Selamat Datang Kembali` tidak ada di view lama)

### Task 2: Migrasi head ke @vite + desain ulang blade

**Files:**
- Create/Modify: `resources/views/auth/login.blade.php` (full rewrite)
- Modify: `resources/css/app.css` (tambah 1 baris @source)
- Depends on: Task 1 (test sudah ada sebagai guard)

- [ ] **Step 1: Update `resources/css/app.css`** — tambahkan setelah `@import 'tailwindcss';`:

```css
@source '../../views/**';
```

- [ ] **Step 2: Rewrite `resources/views/auth/login.blade.php`.** Konten lengkap — blok yang ditandai `[DIPERTAHANKAN]` di-copy utuh dari file lama (ada di git sebelum rewrite):

```blade
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - E-Raport SD Negeri 2 Karangsari</title>

    @php (blok fallback logo sekolah — DIPERTAHANKAN utuh dari file lama) @endphp

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
                <!-- [Semua blok alert session success/info/warning/error DIPERTAHANKAN dari file lama] -->

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

                <!-- [Blok demo accounts @if(config('app.demo_accounts')) DIPERTAHANKAN utuh] -->
            </div>

            <footer class="mt-8 space-y-1 text-center text-xs text-slate-500">
                <p class="font-medium text-slate-600">&copy; {{ date('Y') }} SD Negeri 2 Karangsari &bull; Kurikulum Merdeka</p>
                <p class="text-[11px] text-slate-400">Jl. Parijatah No. 45 Karangsari, Sempu, Banyuwangi</p>
            </footer>
        </div>
    </main>

    <!-- [Script setDemo() dan togglePassword() DIPERTAHANKAN utuh dari file lama] -->
</body>
</html>
```

- [ ] **Step 3: Jalankan test, pastikan PASS** — Run: `php artisan test --compact --filter=LoginPageTest` → Expected: PASS
- [ ] **Step 4: Commit** — `git add tests/Feature/LoginPageTest.php resources/views/auth/login.blade.php resources/css/app.css && git commit -m "feat: modernisasi halaman login split-panel dengan Vite"`

### Task 3: Build, verifikasi visual, & test menyeluruh

- [ ] **Step 1: Build aset** — Run: `npm run build` → Expected: sukses (atau `npm run dev` untuk development)
- [ ] **Step 2: Verifikasi visual** — jalankan dev server, cek tampilan; dipandu controller
- [ ] **Step 3: Jalankan test login menyeluruh** — Run: `php artisan test --compact --filter=Login` → Expected: PASS (LoginPageTest + LoginRateLimiterTest)
- [ ] **Step 4: Formatter PHP** — Run: `vendor/bin/pint --dirty --format agent` → bersih
