<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sekolah extends Model
{
    use HasFactory;

    protected $table = 'sekolah';

    protected $fillable = [
        'nama',
        'alamat',
        'pemda',
        'dinas',
        'kota',
        'kepsek',
        'nip',
        'logo',
        'logo_kanan',
        'ttd',
        'kontak',
        'bobot_formatif',
        'bobot_uts',
        'bobot_uas',
        'hari_efektif_semester',
        'persen_kehadiran_minimal',
        'batas_alpa_maksimal',
        'batas_izin_maksimal',
    ];

    protected function casts(): array
    {
        return [
            'bobot_formatif' => 'integer',
            'bobot_uts' => 'integer',
            'bobot_uas' => 'integer',
            'hari_efektif_semester' => 'integer',
            'persen_kehadiran_minimal' => 'integer',
            'batas_alpa_maksimal' => 'integer',
            'batas_izin_maksimal' => 'integer',
        ];
    }

    public function getHariEfektif(): int
    {
        return $this->hari_efektif_semester ? (int) $this->hari_efektif_semester : 108;
    }

    public function getPersenKehadiranMinimal(): int
    {
        return $this->persen_kehadiran_minimal ? (int) $this->persen_kehadiran_minimal : 75;
    }

    public function getBatasAlpaMaksimal(): int
    {
        return $this->batas_alpa_maksimal !== null ? (int) $this->batas_alpa_maksimal : 3;
    }

    public function getBatasIzinMaksimal(): int
    {
        return $this->batas_izin_maksimal !== null ? (int) $this->batas_izin_maksimal : 7;
    }

    public function getBobotFormatif(): int
    {
        return $this->bobot_formatif !== null ? (int) $this->bobot_formatif : 40;
    }

    public function getBobotUts(): int
    {
        return $this->bobot_uts !== null ? (int) $this->bobot_uts : 30;
    }

    public function getBobotUas(): int
    {
        return $this->bobot_uas !== null ? (int) $this->bobot_uas : 30;
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
