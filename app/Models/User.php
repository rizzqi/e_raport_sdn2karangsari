<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isWali(): bool
    {
        return $this->role === 'wali';
    }

    public function isKepsek(): bool
    {
        return $this->role === 'kepsek';
    }

    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    public function kelasWali(): HasMany
    {
        return $this->hasMany(Kelas::class, 'wali_id');
    }

    public function penugasanGuru(): HasMany
    {
        return $this->hasMany(KelasGuru::class, 'user_id');
    }

    public function raportDibuat(): HasMany
    {
        return $this->hasMany(Raport::class, 'dibuat_oleh');
    }

    /**
     * Cek apakah user berhak menginput/menyimpan absensi pada kelas & tipe tertentu.
     * Aturan:
     * - Admin: lihat dan isi semua kelas.
     * - Wali kelas di kelas sendiri: boleh isi Wali, Agama, dan PJOK.
     * - Di kelas lain: hanya boleh isi mapel yang memang dia ampu (Agama atau PJOK).
     */
    public function canInputAbsensi(Kelas $kelas, string $tipe): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $tipe = strtolower(trim($tipe));

        // 1. Di kelas sendiri sebagai Wali Kelas: boleh isi Wali, Agama, dan PJOK
        if ((int) $kelas->wali_id === (int) $this->id) {
            return in_array($tipe, ['wali', 'agama', 'pjok'], true);
        }

        // 2. Di kelas lain, tipe 'wali' HANYA boleh diisi oleh wali kelas bersangkutan atau admin
        if ($tipe === 'wali') {
            return false;
        }

        // 3. Di kelas lain untuk Agama: harus terdaftar sebagai guru pengampu Agama
        if ($tipe === 'agama') {
            return $this->isPengampuAgama($kelas);
        }

        // 4. Di kelas lain untuk PJOK: harus terdaftar sebagai guru pengampu PJOK
        if ($tipe === 'pjok') {
            return $this->isPengampuPjok($kelas);
        }

        return false;
    }

    /**
     * Cek apakah user adalah pengampu Pendidikan Agama di kelas ini.
     */
    public function isPengampuAgama(Kelas $kelas): bool
    {
        $mapelAgama = $kelas->getMapelAgama();
        $mapelAgamaId = $mapelAgama?->id;

        return $kelas->guruPengampu->contains(function (KelasGuru $gp) use ($mapelAgamaId) {
            $isAgama = ($mapelAgamaId && (int) $gp->mapel_id === (int) $mapelAgamaId)
                || ($gp->mapel && $gp->mapel->isAgama());

            return $isAgama && $this->isPengampuRecord($gp);
        });
    }

    /**
     * Cek apakah user adalah pengampu PJOK di kelas ini.
     */
    public function isPengampuPjok(Kelas $kelas): bool
    {
        $mapelPjok = $kelas->getMapelPjok();
        $mapelPjokId = $mapelPjok?->id;

        return $kelas->guruPengampu->contains(function (KelasGuru $gp) use ($mapelPjokId) {
            $isPjok = ($mapelPjokId && (int) $gp->mapel_id === (int) $mapelPjokId)
                || ($gp->mapel && $gp->mapel->isPjok());

            return $isPjok && $this->isPengampuRecord($gp);
        });
    }

    /**
     * Cek apakah baris penugasan kelas_guru ($gp) mewakili user ini.
     *
     * Prioritas pencocokan:
     * 1. Kecocokan user_id (akurat dan aman).
     * 2. Kecocokan nama persis (case-insensitive).
     * 3. Kecocokan nama setelah membersihkan gelar akademik umum (S.Pd., M.Pd., dll).
     *    Hanya exact-match setelah normalisasi — tidak menggunakan substring/contains
     *    untuk menghindari false-positive (mis. "Budi" tidak cocok dengan "Budi Santoso").
     */
    public function isPengampuRecord(KelasGuru $gp): bool
    {
        if ($gp->user_id && (int) $gp->user_id === (int) $this->id) {
            return true;
        }

        if (! empty($gp->nama)) {
            $namaGp = strtolower(trim($gp->nama));
            $namaUser = strtolower(trim($this->name));

            if ($namaGp === $namaUser) {
                return true;
            }

            // Bersihkan gelar umum, lalu normalkan spasi, lalu exact-match
            $gelarPattern = '/\b(s\.pd\.?i?|m\.pd\.?|s\.ag|s\.tr\.kom|s\.kom)\b\.?/i';
            $cleanGp = trim(preg_replace('/\s+/', ' ', preg_replace($gelarPattern, '', $namaGp)));
            $cleanUser = trim(preg_replace('/\s+/', ' ', preg_replace($gelarPattern, '', $namaUser)));

            if ($cleanGp !== '' && $cleanUser !== '' && $cleanGp === $cleanUser) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah user memiliki penugasan apa pun (sebagai wali atau guru pengampu) pada kelas ini.
     */
    public function hasAnyPenugasanInKelas(Kelas $kelas): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ((int) $kelas->wali_id === (int) $this->id) {
            return true;
        }

        return $kelas->guruPengampu->contains(function (KelasGuru $gp) {
            return $this->isPengampuRecord($gp);
        });
    }

    /**
     * Cek apakah user adalah pengampu mapel tertentu pada kelas ini.
     */
    public function isPengampuMapel(Kelas $kelas, mixed $mapel): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ((int) $kelas->wali_id === (int) $this->id) {
            return true;
        }

        $mapelId = $mapel instanceof MataPelajaran ? $mapel->id : (int) $mapel;

        return $kelas->guruPengampu->contains(function (KelasGuru $gp) use ($mapelId) {
            return (int) $gp->mapel_id === $mapelId && $this->isPengampuRecord($gp);
        });
    }
}
