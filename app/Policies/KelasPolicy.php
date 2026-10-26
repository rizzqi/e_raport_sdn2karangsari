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
     */
    public function view(User $user, Kelas $kelas): bool
    {
        if ($user->isAdmin() || $user->isKepsek()) {
            return true;
        }

        return $kelas->wali_id === $user->id;
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
