<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiPembiasaan extends Model
{
    use HasFactory;

    protected $table = 'nilai_pembiasaan';

    protected $fillable = [
        'kelas_siswa_id',
        'pembiasaan_id',
        'periode',
        'predikat',
    ];

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class);
    }

    public function pembiasaan(): BelongsTo
    {
        return $this->belongsTo(Pembiasaan::class);
    }
}
