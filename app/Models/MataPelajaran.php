<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class MataPelajaran extends Model
{
    use HasFactory;

    protected $table = 'mata_pelajaran';

    protected $fillable = [
        'nama',
        'kkm',
        'target_kelas',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'kkm' => 'integer',
            'aktif' => 'boolean',
            'urutan' => 'integer',
            'target_kelas' => 'array',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan', 'asc');
    }

    /**
     * Periksa apakah mata pelajaran ini berlaku untuk kelas tertentu.
     */
    public function appliesToKelas(int|Kelas $kelas): bool
    {
        if (empty($this->target_kelas)) {
            return true;
        }

        $tingkat = $kelas instanceof Kelas ? $kelas->tingkat : (int) $kelas;
        if (! $tingkat) {
            return true;
        }

        return in_array($tingkat, (array) $this->target_kelas, false);
    }

    /**
     * Dapatkan daftar mata pelajaran aktif yang berlaku untuk kelas tertentu.
     *
     * @return Collection<int, static>
     */
    public static function forKelas(Kelas|int $kelas): Collection
    {
        return static::aktif()->get()->filter(function (self $mapel) use ($kelas) {
            return $mapel->appliesToKelas($kelas);
        })->values();
    }

    /**
     * Label keterbacaan target kelas / fase untuk tampilan antarmuka.
     */
    public function getTargetKelasLabelAttribute(): string
    {
        if (empty($this->target_kelas)) {
            return 'Semua Kelas (1 - 6)';
        }

        $sorted = array_map('intval', (array) $this->target_kelas);
        sort($sorted);

        if ($sorted === [1, 2, 3, 4, 5, 6]) {
            return 'Semua Kelas (1 - 6)';
        }
        if ($sorted === [1, 2]) {
            return 'Fase A (Kelas 1 - 2)';
        }
        if ($sorted === [3, 4]) {
            return 'Fase B (Kelas 3 - 4)';
        }
        if ($sorted === [5, 6]) {
            return 'Fase C (Kelas 5 - 6)';
        }
        if ($sorted === [3, 4, 5, 6]) {
            return 'Fase B & C (Kelas 3 - 6)';
        }

        return 'Kelas '.implode(', ', $sorted);
    }

    /**
     * Nama singkat mata pelajaran untuk header tabel ledger / rekap agar muat dalam 1 layar & 1 lembar.
     */
    public function getNamaSingkatAttribute(): string
    {
        $nama = trim($this->nama);

        if (preg_match('/agama/i', $nama)) {
            return 'PABP / Agama';
        }
        if (preg_match('/pancasila/i', $nama)) {
            return 'P. Pancasila';
        }
        if (preg_match('/bahasa indonesia/i', $nama)) {
            return 'B. Indo';
        }
        if (preg_match('/matematika/i', $nama)) {
            return 'Matematika';
        }
        if (preg_match('/ipas|alam dan sosial/i', $nama)) {
            return 'IPAS';
        }
        if (preg_match('/pjok|jasmani/i', $nama)) {
            return 'PJOK';
        }
        if (preg_match('/seni/i', $nama)) {
            return 'Seni Budaya';
        }
        if (preg_match('/inggris/i', $nama)) {
            return 'B. Inggris';
        }
        if (preg_match('/daerah|jawa/i', $nama)) {
            return 'B. Daerah';
        }

        return strlen($nama) > 14 ? mb_substr($nama, 0, 12).'..' : $nama;
    }

    /**
     * Cek apakah mata pelajaran merupakan Pendidikan Agama (Islam / lainnya).
     */
    public function isAgama(): bool
    {
        return (bool) preg_match('/agama/i', $this->nama);
    }

    /**
     * Cek apakah mata pelajaran merupakan PJOK (Pendidikan Jasmani, Olahraga, dan Kesehatan).
     */
    public function isPjok(): bool
    {
        return (bool) preg_match('/pjok|jasmani|olahraga/i', $this->nama);
    }

    /**
     * Identifikasi jenis format penilaian harian/formatif:
     * - 'agama': formatif harian bulanan + 2 praktikum (pra-UTS & pra-UAS)
     * - 'pjok': formatif harian & praktikum setiap bulan
     * - 'reguler': formatif harian satu nilai per bulan
     */
    public function getJenisFormatif(): string
    {
        if ($this->isAgama()) {
            return 'agama';
        }
        if ($this->isPjok()) {
            return 'pjok';
        }
        return 'reguler';
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class, 'mapel_id');
    }
}
