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
        'ttd',
    ];

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
