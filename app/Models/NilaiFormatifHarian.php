<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiFormatifHarian extends Model
{
    use HasFactory;

    protected $table = 'nilai_formatif_harian';

    protected $fillable = [
        'penilaian_formatif_harian_id',
        'kelas_siswa_id',
        'nilai',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai' => 'float',
        ];
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(PenilaianFormatifHarian::class, 'penilaian_formatif_harian_id');
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class, 'kelas_siswa_id');
    }

    public function getNilaiFormatAttribute(): string
    {
        return $this->nilai !== null ? (string) (round($this->nilai, 1) == (int) $this->nilai ? (int) $this->nilai : number_format($this->nilai, 1, ',', '.')) : '-';
    }
}
