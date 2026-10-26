<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

    protected $fillable = [
        'nama',
        'fase',
        'sekolah_id',
        'tahun_ajaran_id',
        'wali_id',
        'wali_nama',
        'wali_nip',
        'tanggal_raport_sts',
        'tanggal_raport_sas',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_raport_sts' => 'date',
            'tanggal_raport_sas' => 'date',
        ];
    }

    public function getTanggalRaport(string $periode = 'sts'): ?string
    {
        $date = $periode === 'sas' ? $this->tanggal_raport_sas : $this->tanggal_raport_sts;

        return $date ? $date->format('Y-m-d') : null;
    }

    public function getNamaWaliAttribute(): string
    {
        return $this->wali_nama ?: ($this->wali->name ?? '( ................................ )');
    }

    public function getNipWaliAttribute(): string
    {
        return $this->wali_nip ?: '-';
    }

    public function getTingkatAttribute(): ?int
    {
        if (preg_match('/(\d+)/', (string) $this->nama, $matches)) {
            return (int) $matches[1];
        }

        $cleanFase = strtoupper(trim((string) $this->fase));
        if (str_contains($cleanFase, 'A')) {
            return 1;
        }
        if (str_contains($cleanFase, 'B')) {
            return 3;
        }
        if (str_contains($cleanFase, 'C')) {
            return 5;
        }

        return null;
    }

    /**
     * Dapatkan daftar mata pelajaran aktif yang khusus berlaku untuk rombel kelas ini.
     *
     * @return Collection<int, MataPelajaran>
     */
    public function mataPelajaran(): Collection
    {
        return MataPelajaran::forKelas($this);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_id');
    }

    public function kelasSiswa(): HasMany
    {
        return $this->hasMany(KelasSiswa::class);
    }

    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(Siswa::class, 'kelas_siswa')
            ->withTimestamps();
    }

    public function guruPengampu(): HasMany
    {
        return $this->hasMany(KelasGuru::class, 'kelas_id')->orderBy('urutan');
    }

    public function mapelKkm(): HasMany
    {
        return $this->hasMany(KelasMapelKkm::class, 'kelas_id');
    }

    /**
     * Dapatkan nilai KKM untuk mata pelajaran tertentu pada kelas ini.
     * Menggunakan KKM khusus rombel jika ada, atau fallback ke KKM master mata pelajaran.
     */
    public function getKkmForMapel(int|MataPelajaran $mapel): int
    {
        $mapelId = $mapel instanceof MataPelajaran ? $mapel->id : $mapel;

        if ($this->relationLoaded('mapelKkm')) {
            $record = $this->mapelKkm->firstWhere('mapel_id', $mapelId);
            if ($record && $record->kkm !== null) {
                return (int) $record->kkm;
            }
        } else {
            $record = $this->mapelKkm()->where('mapel_id', $mapelId)->first();
            if ($record && $record->kkm !== null) {
                return (int) $record->kkm;
            }
        }

        if ($mapel instanceof MataPelajaran) {
            return (int) ($mapel->kkm ?? 70);
        }

        $m = MataPelajaran::find($mapelId);

        return (int) ($m?->kkm ?? 70);
    }

    /**
     * Dapatkan nama & NIP pengawas ujian berdasarkan mata pelajaran (otomatis).
     *
     * @return array{nama: string, nip: string, jabatan: string}
     */
    public function getPengawasOtomatis(?string $namaMapel = null): array
    {
        if ($namaMapel) {
            $cleanMapel = strtolower(trim($namaMapel));
            foreach ($this->guruPengampu as $gp) {
                $mNama = $gp->mapel ? strtolower(trim($gp->mapel->nama)) : '';
                if ($mNama !== '' && (str_contains($cleanMapel, $mNama) || str_contains($mNama, $cleanMapel))) {
                    return [
                        'nama' => $gp->nama,
                        'nip' => $gp->nip ?: '-',
                        'jabatan' => 'Guru Pengampu / Pengawas',
                    ];
                }
            }
        }

        return [
            'nama' => $this->nama_wali,
            'nip' => $this->nip_wali,
            'jabatan' => 'Wali Kelas / Pengawas',
        ];
    }
}
