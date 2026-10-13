<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kehadiran extends Model
{
    use HasFactory;

    protected $table = 'kehadiran';

    protected $fillable = [
        'kelas_siswa_id',
        'periode',
        'sakit',
        'izin',
        'alpa',
    ];

    protected function casts(): array
    {
        return [
            'sakit' => 'integer',
            'izin' => 'integer',
            'alpa' => 'integer',
        ];
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class);
    }
}
