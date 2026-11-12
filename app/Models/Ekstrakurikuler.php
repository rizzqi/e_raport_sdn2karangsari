<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ekstrakurikuler extends Model
{
    /**
     * Nama tabel model di database.
     */
    protected $table = 'ekstrakurikuler';

    /**
     * Atribut yang boleh diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'urutan',
        'aktif',
    ];

    /**
     * Casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * Relasi ke catatan nilai ekstrakurikuler siswa.
     */
    public function nilaiEkstrakurikuler(): HasMany
    {
        return $this->hasMany(NilaiEkstrakurikuler::class);
    }

    /**
     * Scope hanya kegiatan ekstrakurikuler yang aktif.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }
}
