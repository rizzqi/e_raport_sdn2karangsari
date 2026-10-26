<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasMapelKkm extends Model
{
    use HasFactory;

    protected $table = 'kelas_mapel_kkm';

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'kkm',
    ];

    protected $casts = [
        'kkm' => 'integer',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }
}
