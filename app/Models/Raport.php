<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Raport extends Model
{
    use HasFactory;

    protected $table = 'raport';

    protected $fillable = [
        'kelas_siswa_id',
        'periode',
        'tanggal_cetak',
        'file_path',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_cetak' => 'date',
        ];
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
