<?php

namespace App\Services;

class PdfReader
{
    /**
     * Parse file PDF dan kembalikan baris-baris data tabular.
     *
     * @return array<int, array<int, string>>
     */
    public static function parseFile(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        if ($content === false || $content === '') {
            return [];
        }

        return self::parseContent($content);
    }

    /**
     * Parse binary PDF content ke array 2 dimensi.
     *
     * @return array<int, array<int, string>>
     */
    public static function parseContent(string $pdfBinary): array
    {
        // 1. Ambil semua stream di dalam dokumen PDF
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $pdfBinary, $matches);
        if (empty($matches[1])) {
            return [];
        }

        $items = [];
        $pageIndex = 0;

        foreach ($matches[1] as $rawStream) {
            $uncompressed = @gzuncompress($rawStream);
            if ($uncompressed === false) {
                $uncompressed = @gzinflate($rawStream);
            }
            if ($uncompressed === false) {
                $uncompressed = $rawStream;
            }

            // Periksa apakah stream mengandung teks
            if (strpos($uncompressed, 'BT') === false && strpos($uncompressed, 'Tj') === false && strpos($uncompressed, 'TJ') === false) {
                continue;
            }

            $pageIndex++;

            // Ambil semua blok BT ... ET
            if (preg_match_all('/BT([\s\S]*?)ET/s', $uncompressed, $btMatches)) {
                foreach ($btMatches[1] as $bt) {
                    $curX = 0.0;
                    $curY = 0.0;

                    // Cari matriks Tm (a b c d x y Tm)
                    if (preg_match('/([-\d\.]+)\s+([-\d\.]+)\s+([-\d\.]+)\s+([-\d\.]+)\s+([-\d\.]+)\s+([-\d\.]+)\s+Tm/', $bt, $mTm)) {
                        $curX = (float) $mTm[5];
                        $curY = (float) $mTm[6];
                    } elseif (preg_match('/([-\d\.]+)\s+([-\d\.]+)\s+Td/', $bt, $mTd)) {
                        $curX = (float) $mTd[1];
                        $curY = (float) $mTd[2];
                    }

                    $textParts = [];

                    // 1. Ekstrak TJ: [ (teks) 120 (teks2) ] TJ atau [ <hex> ... ] TJ
                    if (preg_match_all('/\[([\s\S]*?)\]\s*TJ/', $bt, $tjMatches)) {
                        foreach ($tjMatches[1] as $tjInner) {
                            if (preg_match_all('/\((.*?)(?<!\\\\)\)|<([0-9a-fA-F]+)>/', $tjInner, $strMatches, PREG_SET_ORDER)) {
                                foreach ($strMatches as $sm) {
                                    if (isset($sm[1]) && $sm[1] !== '') {
                                        $textParts[] = self::decodePdfString($sm[1]);
                                    } elseif (isset($sm[2]) && $sm[2] !== '') {
                                        $textParts[] = self::decodeHexString($sm[2]);
                                    }
                                }
                            }
                        }
                    }

                    // 2. Ekstrak Tj: (teks) Tj atau <hex> Tj
                    if (preg_match_all('/\((.*?)(?<!\\\\)\)\s*Tj|<([0-9a-fA-F]+)>\s*Tj/', $bt, $tjSingleMatches, PREG_SET_ORDER)) {
                        foreach ($tjSingleMatches as $sm) {
                            if (isset($sm[1]) && $sm[1] !== '') {
                                $textParts[] = self::decodePdfString($sm[1]);
                            } elseif (isset($sm[2]) && $sm[2] !== '') {
                                $textParts[] = self::decodeHexString($sm[2]);
                            }
                        }
                    }

                    $text = trim(implode('', $textParts));
                    if ($text !== '') {
                        $items[] = [
                            'page' => $pageIndex,
                            'x' => $curX,
                            'y' => $curY,
                            'text' => $text,
                        ];
                    }
                }
            } else {
                // Fallback: ekstrak string biasa jika BT..ET tidak terstruktur
                if (preg_match_all('/\((.*?)(?<!\\\\)\)\s*Tj/', $uncompressed, $rawMatches)) {
                    $yCounter = 1000.0;
                    foreach ($rawMatches[1] as $rawStr) {
                        $t = trim(self::decodePdfString($rawStr));
                        if ($t !== '') {
                            $items[] = [
                                'page' => $pageIndex,
                                'x' => 10.0,
                                'y' => $yCounter -= 20.0,
                                'text' => $t,
                            ];
                        }
                    }
                }
            }
        }

        if (empty($items)) {
            return [];
        }

        return self::clusterItemsToTable($items);
    }

    /**
     * Kelompokkan token teks berdasarkan halaman, koordinat Y (baris), dan X (kolom).
     *
     * @param  array<int, array{page: int, x: float, y: float, text: string}>  $items
     * @return array<int, array<int, string>>
     */
    protected static function clusterItemsToTable(array $items): array
    {
        $toleranceY = 4.0;

        // Pisahkan per halaman agar koordinat Y halaman berikutnya tidak bercampur
        $itemsByPage = [];
        foreach ($items as $item) {
            $itemsByPage[$item['page']][] = $item;
        }

        $allRows = [];

        foreach ($itemsByPage as $pageItems) {
            // Kelompokkan menjadi baris-baris horizontal berdasarkan kedekatan Y
            $groupedRows = [];
            foreach ($pageItems as $item) {
                $placed = false;
                foreach ($groupedRows as &$grow) {
                    if (abs($grow['y'] - $item['y']) <= $toleranceY) {
                        $grow['cells'][] = $item;
                        $placed = true;
                        break;
                    }
                }
                unset($grow);

                if (! $placed) {
                    $groupedRows[] = [
                        'y' => $item['y'],
                        'cells' => [$item],
                    ];
                }
            }

            // Urutkan baris dari atas ke bawah (Y turun / descending)
            usort($groupedRows, fn ($a, $b) => $b['y'] <=> $a['y']);

            // Untuk setiap baris, urutkan sel dari kiri ke kanan (X naik / ascending)
            foreach ($groupedRows as $grow) {
                usort($grow['cells'], fn ($a, $b) => $a['x'] <=> $b['x']);

                $rowCells = [];
                foreach ($grow['cells'] as $c) {
                    $txt = trim($c['text']);
                    if ($txt !== '') {
                        $rowCells[] = $txt;
                    }
                }

                if (! empty($rowCells)) {
                    $allRows[] = $rowCells;
                }
            }
        }

        return $allRows;
    }

    /**
     * Decode string representasi teks di PDF.
     */
    protected static function decodePdfString(string $str): string
    {
        $unescaped = stripcslashes($str);

        // Cek UTF-16BE BOM (\xFE\xFF)
        if (str_starts_with($unescaped, "\xFE\xFF")) {
            $converted = @mb_convert_encoding(substr($unescaped, 2), 'UTF-8', 'UTF-16BE');
            if ($converted !== false) {
                return $converted;
            }
        }

        // Cek apakah string valid UTF-8, jika bukan coba konversi dari Windows-1252 / ISO-8859-1
        if (! mb_check_encoding($unescaped, 'UTF-8')) {
            $converted = @mb_convert_encoding($unescaped, 'UTF-8', 'Windows-1252');
            if ($converted !== false) {
                return $converted;
            }
        }

        return $unescaped;
    }

    /**
     * Decode hexadecimal string PDF (<48656C6C6F>).
     */
    protected static function decodeHexString(string $hex): string
    {
        $bin = @hex2bin($hex);
        if ($bin === false) {
            return '';
        }

        return self::decodePdfString($bin);
    }
}
