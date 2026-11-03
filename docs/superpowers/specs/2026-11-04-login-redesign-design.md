# Design: Modernisasi Halaman Login (Split-Panel Premium)

**Tanggal:** 2026-11-04
**Status:** Disetujui user
**Ruang lingkup:** Fase 1 (halaman login). Fase 2 (dashboard) menyusul di spec terpisah.

## Keputusan Desain (disetujui via brainstorming)

1. **Arah visual:** Split-panel premium — panel kiri branding, panel kanan form.
2. **Isi panel kiri:** Emerard gelap (gradient emerald-950→900) + glow radial teal + pattern titik halus, logo dalam tile frosted glass, nama sekolah, tagline "E-Raport Kurikulum Merdeka", daftar 3 fitur aplikasi berikon Font Awesome, footer alamat sekolah. Tanpa foto asli, tanpa aset gambar baru (pattern murni CSS).
3. **Load CSS:** Migrasi dari Tailwind CDN ke **Vite build** (`resources/css/app.css`, Tailwind v4, font Instrument Sans via bunny). Font Awesome CDN dipertahankan (dipakai seluruh aplikasi).
4. **Mobile (< lig):** Panel kiri menjadi **banner ramping** di atas form (logo + nama sekolah, daftar fitur disembunyikan).

## Layout

- `body` grid `lg:grid-cols-[47%_53%]`, `min-h-dvh`.
- `<aside>`: panel branding. Mobile: banner ramping (brand row). Desktop (`lg:`): kolom penuh — brand, fitur list di tengah via `my-auto`, alamat di bawah via `mt-auto`.
- `<main>`: heading "Selamat Datang Kembali" + sub-teks, card putih `rounded-2xl` berisi alert session, form, demo accounts; footer copyright.
- Input: `py-3 text-sm`, focus ring emerald `ring-4`. Tombol: gradient `emerald-600 → teal-600`, hover lift (`-translate-y-0.5`).

## Yang Dipertahankan (markup/logika tidak berubah)

- Fallback logo sekolah (`@php` block) + favicon.
- Semua alert session: success/info/warning/error.
- Form: `route('login.post')`, `@csrf`, `old('email')`, toggle password, "Ingat Saya".
- Blok demo accounts (`config('app.demo_accounts')`) + script `setDemo()` / `togglePassword()`.

## Basis Teknis

- Hapus Tailwind CDN `cdn.tailwindcss.com` + Google Fonts Inter dari login.
- Gunakan `@vite(['resources/css/app.css'])` — Tailwind v4 + Instrument Sans.
- Tambah `@source '../../views/**';` di `app.css` agar class di blade ter-scan.

## Ruang Lingkup Perubahan

| File | Perubahan |
|---|---|
| `resources/views/auth/login.blade.php` | Desain ulang (fungsi tetap) |
| `resources/css/app.css` | +1 baris `@source` |
| `tests/Feature/LoginPageTest.php` | Baru (TDD) |

Tidak ada perubahan controller, route, atau database.

## Verifikasi

- Test: `php artisan test --compact --filter=Login` (termasuk `LoginRateLimiterTest`).
- Visual: cek desktop (dua panel) & mobile emulator (banner ramping).
- Build: `npm run build`.
- Formatter: `vendor/bin/pint --dirty --format agent`.
