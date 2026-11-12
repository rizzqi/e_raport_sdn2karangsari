<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiEkstrakurikuler extends Model
{
    /**
     * Daftar predikat resmi kegiatan ekstrakurikuler.
     *
     * @var array<int, string>
     */
    public const PREDIKAT_LIST = [
        'Sangat Baik',
        'Baik',
        'Cukup',
        'Perlu Bimbingan',
    ];

    /**
     * Nama tabel model di database.
     */
    protected $table = 'nilai_ekstrakurikuler';

    /**
     * Atribut yang boleh diisi massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kelas_siswa_id',
        'ekstrakurikuler_id',
        'periode',
        'predikat',
        'keterangan',
    ];

    /**
     * Relasi ke data keanggotaan siswa di kelas.
     */
    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class);
    }

    /**
     * Relasi ke master kegiatan ekstrakurikuler.
     */
    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class);
    }
}
