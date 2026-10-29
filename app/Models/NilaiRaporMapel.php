<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NilaiRaporMapel extends Model
{
    use HasFactory;

    protected $table = 'nilai_rapor_mapel';

    protected $fillable = [
        'kelas_siswa_id',
        'mapel_id',
        'tahun',
        'semester',
        'rata_formatif',
        'praktik_1',
        'praktik_2',
        'rata_praktik',
        'nilai_uts',
        'nilai_uas',
        'nilai_rapor',
        'predikat',
        'catatan',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'rata_formatif' => 'float',
            'praktik_1' => 'float',
            'praktik_2' => 'float',
            'rata_praktik' => 'float',
            'nilai_uts' => 'float',
            'nilai_uas' => 'float',
            'nilai_rapor' => 'float',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
        ];
    }

    public function kelasSiswa(): BelongsTo
    {
        return $this->belongsTo(KelasSiswa::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Hitung Predikat berdasarkan Nilai Rapor (Standar SDN 2 Karangsari)
     * A: >= 90
     * B: >= 80
     * C: >= 70
     * D: < 70
     */
    public static function hitungPredikat(?float $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if ($nilai >= 90) {
            return 'A';
        }
        if ($nilai >= 80) {
            return 'B';
        }
        if ($nilai >= 70) {
            return 'C';
        }

        return 'D';
    }

    /**
     * Deskripsi Predikat
     */
    public static function getKeteranganPredikat(?string $predikat): string
    {
        return match ($predikat) {
            'A' => 'Sangat Baik',
            'B' => 'Baik',
            'C' => 'Cukup',
            'D' => 'Perlu Bimbingan',
            default => '-',
        };
    }

    /**
     * Generator Template Saran Catatan Guru berdasarkan Predikat & Mapel
     */
    public static function getTemplateCatatan(string $predikat, string $mapelNama, string $siswaNama = ''): string
    {
        $namaPrefix = $siswaNama ? "Ananda {$siswaNama}" : "Peserta didik";

        return match (strtoupper($predikat)) {
            'A' => "{$namaPrefix} menunjukkan penguasaan yang sangat baik dalam memahami materi {$mapelNama}, sangat aktif dan mandiri dalam proses pembelajaran.",
            'B' => "{$namaPrefix} menunjukkan penguasaan yang baik dalam memahami materi {$mapelNama}, mampu menyelesaikan seluruh tugas dengan tertib dan bertanggung jawab.",
            'C' => "{$namaPrefix} cukup menguasai materi pokok {$mapelNama}, diharapkan lebih giat belajar dan meningkatkan keaktifan saat pembelajaran di kelas.",
            'D' => "{$namaPrefix} memerlukan bimbingan dan pendampingan lebih lanjut dalam memahami konsep-konsep dasar pembelajaran {$mapelNama}.",
            default => "{$namaPrefix} telah mengikuti pembelajaran {$mapelNama} dengan baik.",
        };
    }
}
