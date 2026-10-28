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
    ];

    protected function casts(): array
    {
        return [
            'bobot_formatif' => 'integer',
            'bobot_uts' => 'integer',
            'bobot_uas' => 'integer',
        ];
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
