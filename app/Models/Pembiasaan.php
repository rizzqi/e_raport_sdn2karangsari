<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembiasaan extends Model
{
    use HasFactory;

    protected $table = 'pembiasaan';

    protected $fillable = [
        'nama',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true)->orderBy('urutan', 'asc');
    }

    public function nilaiPembiasaan(): HasMany
    {
        return $this->hasMany(NilaiPembiasaan::class);
    }
}
