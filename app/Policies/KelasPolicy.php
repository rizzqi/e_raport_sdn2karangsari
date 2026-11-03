<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    /**
     * Tentukan apakah user bisa melihat daftar kelas.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tentukan apakah user bisa melihat detail kelas.
     *
     * Admin dan Kepsek dapat melihat semua kelas.
     * Wali kelas dapat melihat kelas yang diampunya.
     * Guru pengampu (baik melalui user_id maupun kecocokan nama) dapat melihat kelasnya.
     */
    public function view(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin() || $user->isKepsek()) {
            return true;
        }

        if ((int) $kelas->wali_id === (int) $user->id) {
            return true;
        }

        // Guru pengampu: cek via user_id pada tabel kelas_guru
        if ($kelas->guruPengampu()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Cek pengampu Agama / PJOK via metode model (pencocokan nama)
        if ($user->isPengampuAgama($kelas) || $user->isPengampuPjok($kelas)) {
            return true;
        }

        return false;
    }

    /**
     * Tentukan apakah user bisa mengubah nilai / data kelas.
     */
    public function update(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Wali kelas hanya jika tahun ajaran aktif dan kelas miliknya
        if ($user->isWali() && $kelas->wali_id === $user->id) {
            return (bool) ($kelas->tahunAjaran && $kelas->tahunAjaran->aktif);
        }

        return false;
    }

    /**
     * Kelola struktur kelas (hanya Admin).
     */
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
