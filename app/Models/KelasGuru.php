<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasGuru extends Model
{
    use HasFactory;

    protected $table = 'kelas_guru';

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'nama',
        'nip',
        'urutan',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function getNipFormatAttribute(): string
    {
        return $this->nip ?: '-';
    }
}
