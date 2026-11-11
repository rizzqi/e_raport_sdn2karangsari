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
        'default_paper_size',
        'default_paper_orientation',
        'default_margin_preset',
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

    public function getKepalaSekolahAttribute(): ?string
    {
        return $this->kepsek;
    }

    public function getNipKepalaSekolahAttribute(): ?string
    {
        return $this->nip;
    }

    public function getDefaultPaperSize(): string
    {
        $val = strtolower((string) ($this->default_paper_size ?? 'a4'));

        return in_array($val, ['a4', 'f4'], true) ? $val : 'a4';
    }

    public function getDefaultPaperOrientation(): string
    {
        $val = strtolower((string) ($this->default_paper_orientation ?? 'portrait'));

        return in_array($val, ['portrait', 'landscape'], true) ? $val : 'portrait';
    }

    public function getDefaultMarginPreset(): string
    {
        $val = strtolower((string) ($this->default_margin_preset ?? 'normal'));

        return in_array($val, ['sempit', 'normal', 'lebar'], true) ? $val : 'normal';
    }

    /**
     * Selesaikan konfigurasi cetak (kertas A4/F4, orientasi, margin, dan token tata letak).
     *
     * @return array{
     *   paper: string,
     *   paper_label: string,
     *   orientation: string,
     *   margin: string,
     *   margin_label: string,
     *   margin_mm: array{top: int, right: int, bottom: int, left: int},
     *   css_margin: string,
     *   css_page_size: string,
     *   width_mm: int,
     *   height_mm: int,
     *   dompdf_paper: array{0: float, 1: float, 2: float, 3: float},
     *   layout: array{
     *     body_font: string,
     *     table_font: string,
     *     small_font: string,
     *     cell_padding: string,
     *     section_gap: string,
     *     ttd_height: string,
     *     max_w_class: string,
     *     is_compact: bool
     *   }
     * }
     */
    public function resolvePrintConfig(?string $paper = null, ?string $orientation = null, ?string $margin = null): array
    {
        $resolvedPaper = strtolower(trim((string) ($paper ?: $this->getDefaultPaperSize())));
        if (! in_array($resolvedPaper, ['a4', 'f4'], true)) {
            $resolvedPaper = 'a4';
        }

        $resolvedOrientation = strtolower(trim((string) ($orientation ?: $this->getDefaultPaperOrientation())));
        if (! in_array($resolvedOrientation, ['portrait', 'landscape'], true)) {
            $resolvedOrientation = 'portrait';
        }

        $resolvedMargin = strtolower(trim((string) ($margin ?: $this->getDefaultMarginPreset())));
        if (! in_array($resolvedMargin, ['sempit', 'normal', 'lebar'], true)) {
            $resolvedMargin = 'normal';
        }

        $marginMap = [
            'sempit' => ['label' => 'Sempit (6 × 8 mm)', 'top' => 6, 'right' => 8, 'bottom' => 6, 'left' => 8],
            'normal' => ['label' => 'Normal (10 × 12 mm)', 'top' => 10, 'right' => 12, 'bottom' => 10, 'left' => 12],
            'lebar' => ['label' => 'Lebar (15 × 18 mm)', 'top' => 15, 'right' => 18, 'bottom' => 15, 'left' => 18],
        ];
        $m = $marginMap[$resolvedMargin];

        $dims = $resolvedPaper === 'f4'
            ? ['w' => 215, 'h' => 330, 'pts' => [0.0, 0.0, 609.45, 935.43], 'label' => 'F4 / Folio (215 × 330 mm)']
            : ['w' => 210, 'h' => 297, 'pts' => [0.0, 0.0, 595.28, 841.89], 'label' => 'A4 (210 × 297 mm)'];

        $pageW = $resolvedOrientation === 'landscape' ? $dims['h'] : $dims['w'];
        $pageH = $resolvedOrientation === 'landscape' ? $dims['w'] : $dims['h'];

        // Sesuaikan kepadatan layout agar A4 maupun Landscape tidak terpotong ke halaman 2
        $isCompact = $resolvedPaper === 'a4' || $resolvedOrientation === 'landscape';
        $maxWClass = "max-w-[{$pageW}mm]";
        $layout = $isCompact
            ? [
                'body_font' => '8pt',
                'table_font' => '7.5pt',
                'small_font' => '7pt',
                'cell_padding' => '3px 4px',
                'section_gap' => '6px',
                'ttd_height' => '42px',
                'max_w_class' => $maxWClass,
                'css_max_width' => "{$pageW}mm",
                'is_compact' => true,
            ]
            : [
                'body_font' => '9pt',
                'table_font' => '8.5pt',
                'small_font' => '7.5pt',
                'cell_padding' => '4px 5px',
                'section_gap' => '10px',
                'ttd_height' => '52px',
                'max_w_class' => $maxWClass,
                'css_max_width' => "{$pageW}mm",
                'is_compact' => false,
            ];

        return [
            'paper' => $resolvedPaper,
            'paper_label' => $dims['label'],
            'orientation' => $resolvedOrientation,
            'margin' => $resolvedMargin,
            'margin_label' => $m['label'],
            'margin_mm' => [
                'top' => $m['top'],
                'right' => $m['right'],
                'bottom' => $m['bottom'],
                'left' => $m['left'],
            ],
            'css_margin' => "{$m['top']}mm {$m['right']}mm {$m['bottom']}mm {$m['left']}mm",
            'css_page_size' => "{$pageW}mm {$pageH}mm",
            'width_mm' => $pageW,
            'height_mm' => $pageH,
            'dompdf_paper' => $dims['pts'],
            'layout' => $layout,
        ];
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
