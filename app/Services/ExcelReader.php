<?php

namespace App\Services;

use ZipArchive;

class ExcelReader
{
    /**
     * Parse file (XLSX, CSV) or raw text into rows of columns.
     *
     * @return array<int, array<int, string>>
     */
    public static function parseFile(string $filePath, string $extension): array
    {
        $ext = strtolower($extension);
        if ($ext === 'csv' || $ext === 'txt') {
            return self::parseCsv($filePath);
        }

        if ($ext === 'xlsx') {
            return self::parseXlsx($filePath);
        }

        if ($ext === 'pdf') {
            return PdfReader::parseFile($filePath);
        }

        return [];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function parseCsv(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read first line to detect delimiter (, or ; or \t)
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = ',';
            if ($firstLine !== false) {
                if (substr_count($firstLine, "\t") > substr_count($firstLine, ',') && substr_count($firstLine, "\t") > substr_count($firstLine, ';')) {
                    $delimiter = "\t";
                } elseif (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                    $delimiter = ';';
                }
            }

            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                // filter empty rows
                $filtered = array_map(fn ($v) => trim((string) $v), $data);
                if (count(array_filter($filtered, fn ($v) => $v !== '')) > 0) {
                    $rows[] = $filtered;
                }
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function parseXlsx(string $filePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // 1. Read shared strings if any
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $str = '';
                        foreach ($si->r as $r) {
                            $str .= (string) $r->t;
                        }
                        $sharedStrings[] = $str;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read sheet1
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if (! $xml || ! isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowValues = [];
            $lastColIndex = 0;
            foreach ($row->c as $c) {
                $cellRef = (string) $c['r']; // e.g. "A1", "B1", "C2"
                $colIndex = self::colLetterToIndex(preg_replace('/[0-9]/', '', $cellRef));

                // Fill gaps if any
                while ($lastColIndex < $colIndex) {
                    $rowValues[] = '';
                    $lastColIndex++;
                }

                $val = '';
                $type = (string) $c['t'];
                if ($type === 's') { // shared string
                    $idx = (int) $c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }

                $rowValues[] = trim((string) $val);
                $lastColIndex++;
            }

            if (count(array_filter($rowValues, fn ($v) => $v !== '')) > 0) {
                $rows[] = $rowValues;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function parseClipboard(string $text): array
    {
        $lines = explode("\n", str_replace("\r", '', $text));
        $rows = [];
        foreach ($lines as $line) {
            $line = rtrim($line, "\r\n");
            if (trim($line) === '') {
                continue;
            }
            $delimiter = strpos($line, "\t") !== false ? "\t" : (strpos($line, ';') !== false ? ';' : ',');
            $parts = array_map(fn ($v) => trim((string) $v), explode($delimiter, $line));
            if (count(array_filter($parts, fn ($v) => $v !== '')) > 0) {
                $rows[] = $parts;
            }
        }

        return $rows;
    }

    /**
     * Extract structured student array from raw rows.
     *
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{nama: string, nis: string, nisn: ?string, kelas: ?string}>
     */
    public static function extractStudents(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $headerRowIdx = null;
        $colNama = null;
        $colNis = null;
        $colNisn = null;
        $colKelas = null;

        $namaSynonyms = ['nama', 'nama siswa', 'nama lengkap', 'nama peserta didik', 'nama murid', 'pd', 'peserta didik', 'nama_siswa'];
        $nisSynonyms = ['nis', 'nipd', 'no induk', 'nomor induk', 'nis siswa', 'no. induk', 'no_induk', 'nomor induk peserta didik', 'no_peserta_didik'];
        $nisnSynonyms = ['nisn', 'no nisn', 'nomor nisn', 'nisn siswa', 'no. nisn', 'no_nisn'];
        $kelasSynonyms = ['kelas', 'nama kelas', 'rombel', 'rombel saat ini', 'tingkat', 'tingkat kelas', 'kls', 'nama rombel', 'rombongan belajar', 'rombel_saat_ini'];

        // Scan candidate rows (up to 15 rows) for the header row
        $searchLimit = min(15, count($rows));
        for ($r = 0; $r < $searchLimit; $r++) {
            $row = $rows[$r];
            $detectedNama = null;
            $detectedNis = null;
            $detectedNisn = null;
            $detectedKelas = null;

            foreach ($row as $idx => $cell) {
                $val = strtolower(trim((string) $cell));
                $val = preg_replace('/\s+/', ' ', $val);

                if (in_array($val, $namaSynonyms)) {
                    $detectedNama = $idx;
                } elseif (in_array($val, $nisSynonyms)) {
                    $detectedNis = $idx;
                } elseif (in_array($val, $nisnSynonyms)) {
                    $detectedNisn = $idx;
                } elseif (in_array($val, $kelasSynonyms)) {
                    $detectedKelas = $idx;
                }
            }

            if ($detectedNama !== null && ($detectedNis !== null || $detectedNisn !== null || $detectedKelas !== null)) {
                $headerRowIdx = $r;
                $colNama = $detectedNama;
                $colNis = $detectedNis;
                $colNisn = $detectedNisn;
                $colKelas = $detectedKelas;
                break;
            }
        }

        $dataRows = [];
        if ($headerRowIdx !== null) {
            $dataRows = array_slice($rows, $headerRowIdx + 1);
        } else {
            // Fallback for headerless text/CSV
            $sampleRow = $rows[0] ?? [];
            $firstColVal = trim((string) ($sampleRow[0] ?? ''));
            if (is_numeric($firstColVal) && (int) $firstColVal < 500 && isset($sampleRow[1])) {
                $colNama = 1;
                $colNis = 2;
                $colNisn = isset($sampleRow[3]) ? 3 : null;
                $colKelas = isset($sampleRow[4]) ? 4 : null;
            } else {
                $colNama = 0;
                $colNis = 1;
                $colNisn = isset($sampleRow[2]) ? 2 : null;
                $colKelas = isset($sampleRow[3]) ? 3 : null;
            }
            $dataRows = $rows;
        }

        $students = [];
        $headerKeywords = array_merge($namaSynonyms, $nisSynonyms, $nisnSynonyms, $kelasSynonyms, [
            'no', 'jk', 'nik', 'agama', 'alamat', 'tempat lahir', 'tanggal lahir', 'data ayah', 'data ibu', 'data wali',
        ]);

        foreach ($dataRows as $row) {
            $nama = trim((string) ($row[$colNama] ?? ''));
            $nis = $colNis !== null ? trim((string) ($row[$colNis] ?? '')) : '';
            $nisn = $colNisn !== null ? trim((string) ($row[$colNisn] ?? '')) : null;
            $kelas = $colKelas !== null ? trim((string) ($row[$colKelas] ?? '')) : null;

            // Bersihkan format desimal .0 dari file Excel
            $nis = self::cleanNumberString($nis);
            if ($nisn !== null) {
                $nisn = self::cleanNumberString($nisn);
            }

            // Jika NIS kosong tetapi NISN ada, gunakan NISN
            if (empty($nis) && ! empty($nisn)) {
                $nis = $nisn;
            }

            // Abaikan baris kosong, baris subheader, atau angka nomor urut yang masuk ke nama
            if (empty($nama)) {
                continue;
            }
            $namaLower = strtolower($nama);
            if (in_array($namaLower, $headerKeywords) || is_numeric($nama)) {
                continue;
            }

            $students[] = [
                'nama' => $nama,
                'nis' => (string) $nis,
                'nisn' => $nisn !== '' ? (string) $nisn : null,
                'kelas' => $kelas !== '' ? (string) $kelas : null,
            ];
        }

        return $students;
    }

    /**
     * Bersihkan angka berformat desimal .0 dari Excel (contoh: "2237.0" => "2237").
     */
    public static function cleanNumberString(string $val): string
    {
        $val = trim($val);
        if (preg_match('/^(\d+)\.0+$/', $val, $m)) {
            return $m[1];
        }

        return $val;
    }

    private static function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index - 1; // 0-based
    }
}
