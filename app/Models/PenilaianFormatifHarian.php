<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenilaianFormatifHarian extends Model
{
    use HasFactory;

    protected $table = 'penilaian_formatif_harian';

    protected $fillable = [
        'kelas_id',
        'mapel_id',
        'tahun',
        'bulan',
        'nama',
        'lingkup_materi',
        'tanggal',
        'bobot',
        'jenis',
        'urutan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'tanggal' => 'date',
            'bobot' => 'float',
            'urutan' => 'integer',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function nilaiFormatifHarian(): HasMany
    {
        return $this->hasMany(NilaiFormatifHarian::class, 'penilaian_formatif_harian_id');
    }

    public function scopeTeori($query)
    {
        return $query->where('jenis', 'teori');
    }

    public function scopePraktik($query)
    {
        return $query->where('jenis', 'praktik');
    }

    public function getTanggalFormatAttribute(): string
    {
        return $this->tanggal ? Carbon::parse($this->tanggal)->locale('id')->translatedFormat('d M Y') : '-';
    }
}
