<?php

namespace App\Services;

use Illuminate\Http\Response;
use ZipArchive;

class ExcelWriter
{
    /**
     * Hasilkan file Excel (.xlsx) murni dalam format OpenXML (ECMA-376)
     * tanpa dependensi eksternal, kompatibel penuh dengan Microsoft Excel,
     * Google Sheets, LibreOffice, dan WPS Office.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function createXlsx(array $headers, array $rows, string $sheetTitle = 'Nilai'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_export_');
        $zip = new ZipArchive;

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file arsip Excel sementara.');
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $cleanTitle = htmlspecialchars(mb_substr($sheetTitle, 0, 31), ENT_XML1, 'UTF-8');
        $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="'.$cleanTitle.'" sheetId="1" r:id="rId1"/>'
            .'</sheets>'
            .'</workbook>';
        $zip->addFromString('xl/workbook.xml', $wbXml);

        // 5. xl/styles.xml (Gaya visual Excel rapi: Header tebal & latar hijau muda, border tipis, teks tengah/kiri)
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF0F5132"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE2EFDA"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFB8C2CC"/></left>'
            .'<right style="thin"><color rgb="FFB8C2CC"/></right>'
            .'<top style="thin"><color rgb="FFB8C2CC"/></top>'
            .'<bottom style="thin"><color rgb="FFB8C2CC"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'</cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" wrapText="1"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center"/>'
            .'</xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'</cellXfs>'
            .'</styleSheet>';
        $zip->addFromString('xl/styles.xml', $stylesXml);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = self::buildWorksheetXml($headers, $rows);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content !== false ? $content : '';
    }

    /**
     * Susun respon HTTP unduhan file .xlsx
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function downloadResponse(array $headers, array $rows, string $filename, string $sheetTitle = 'Nilai'): Response
    {
        $xlsxContent = self::createXlsx($headers, $rows, $sheetTitle);

        return response($xlsxContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Length' => strlen($xlsxContent),
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Konversi index kolom 0-based ke huruf Excel (0 -> A, 1 -> B, 26 -> AA, dst.)
     */
    public static function colIndexToLetter(int $colIndex): string
    {
        $letters = '';
        while ($colIndex >= 0) {
            $letters = chr(($colIndex % 26) + 65).$letters;
            $colIndex = intdiv($colIndex, 26) - 1;
        }

        return $letters;
    }

    /**
     * Bangun XML lembar kerja worksheet dengan lebar kolom teratur
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private static function buildWorksheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Lebar Kolom
        $totalCols = max(count($headers), ! empty($rows) ? count($rows[0]) : 0);
        if ($totalCols > 0) {
            $xml .= '<cols>';
            for ($c = 0; $c < $totalCols; $c++) {
                $colNum = $c + 1;
                $width = 20; // default lebar
                if ($c === 0) {
                    $width = 8; // No
                } elseif ($c === 1) {
                    $width = 16; // NIS
                } elseif ($c === 2) {
                    $width = 32; // Nama Siswa
                }
                $xml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$width.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        $currentRow = 1;

        // Baris Header
        if (! empty($headers)) {
            $xml .= '<row r="'.$currentRow.'" ht="24" customHeight="1">';
            foreach ($headers as $colIdx => $hText) {
                $colLetter = self::colIndexToLetter($colIdx);
                $safeText = htmlspecialchars((string) $hText, ENT_XML1, 'UTF-8');
                $xml .= '<c r="'.$colLetter.$currentRow.'" t="inlineStr" s="1">'
                    .'<is><t>'.$safeText.'</t></is>'
                    .'</c>';
            }
            $xml .= '</row>';
            $currentRow++;
        }

        // Baris Data Siswa
        foreach ($rows as $row) {
            $xml .= '<row r="'.$currentRow.'" ht="20" customHeight="1">';
            foreach ($row as $colIdx => $val) {
                $colLetter = self::colIndexToLetter($colIdx);
                $cellRef = $colLetter.$currentRow;
                $valStr = (string) $val;

                // Tentukan gaya alignment: Nama siswa (kolom index 2) kiri (s=2), lainnya tengah (s=3)
                $styleId = ($colIdx === 2) ? 2 : 3;

                if ($valStr === '') {
                    $xml .= '<c r="'.$cellRef.'" s="'.$styleId.'"/>';
                } elseif (is_numeric($valStr) && ! str_starts_with($valStr, '0') && $colIdx !== 1) {
                    // Angka murni (misal: Nilai, No urut)
                    $xml .= '<c r="'.$cellRef.'" s="'.$styleId.'"><v>'.$valStr.'</v></c>';
                } else {
                    // String teks (Nama, NIS dengan awalan 0, dsb)
                    $safeVal = htmlspecialchars($valStr, ENT_XML1, 'UTF-8');
                    $xml .= '<c r="'.$cellRef.'" t="inlineStr" s="'.$styleId.'">'
                        .'<is><t>'.$safeVal.'</t></is>'
                        .'</c>';
                }
            }
            $xml .= '</row>';
            $currentRow++;
        }

        $xml .= '</sheetData>';
        $xml .= '</worksheet>';

        return $xml;
    }
}
