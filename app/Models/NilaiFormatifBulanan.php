<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiFormatifBulanan extends Model
{
    use HasFactory;

    protected $table = 'nilai_formatif_bulanan';

    protected $fillable = [
        'kelas_siswa_id',
        'mapel_id',
        'tahun',
        'bulan',
        'nilai',
        'nilai_praktik',
        'lingkup_materi',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'nilai' => 'float',
            'nilai_praktik' => 'float',
        ];
    }

    public const NAMA_BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function getNamaBulanAttribute(): string
    {
        return self::NAMA_BULAN[$this->bulan] ?? "Bulan {$this->bulan}";
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class, 'kelas_siswa_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }
}
