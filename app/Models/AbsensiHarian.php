<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiHarian extends Model
{
    use HasFactory;

    protected $table = 'absensi_harian';

    protected $fillable = [
        'kelas_siswa_id',
        'mapel_id',
        'tanggal',
        'status',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public const STATUS_HADIR = 'H';
    public const STATUS_SAKIT = 'S';
    public const STATUS_IZIN = 'I';
    public const STATUS_ALPA = 'A';

    public const STATUS_LABELS = [
        'H' => 'Hadir',
        'S' => 'Sakit',
        'I' => 'Izin',
        'A' => 'Alpa',
    ];

    public function getNamaStatusAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'H' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'S' => 'bg-blue-100 text-blue-800 border-blue-200',
            'I' => 'bg-amber-100 text-amber-800 border-amber-200',
            'A' => 'bg-rose-100 text-rose-800 border-rose-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class, 'kelas_siswa_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeUmum(Builder $query): Builder
    {
        return $query->whereNull('mapel_id');
    }

    public function scopeMapel(Builder $query, ?int $mapelId): Builder
    {
        if ($mapelId === null) {
            return $query->whereNull('mapel_id');
        }
        return $query->where('mapel_id', $mapelId);
    }

    public function scopeTanggal(Builder $query, string $tanggal): Builder
    {
        return $query->whereDate('tanggal', $tanggal);
    }

    public function scopeBulan(Builder $query, int $tahun, int $bulan): Builder
    {
        return $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
    }
}
