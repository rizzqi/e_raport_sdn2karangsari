<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasSiswa extends Model
{
    use HasFactory;

    protected $table = 'kelas_siswa';

    protected $fillable = [
        'kelas_id',
        'siswa_id',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(Nilai::class);
    }

    public function nilaiPembiasaan(): HasMany
    {
        return $this->hasMany(NilaiPembiasaan::class);
    }

    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class);
    }

    public function raport(): HasMany
    {
        return $this->hasMany(Raport::class);
    }

    public function kehadiranPeriode(string $periode)
    {
        return $this->kehadiran()->where('periode', $periode)->first();
    }
}
