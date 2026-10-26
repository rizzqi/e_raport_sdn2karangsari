<?php

namespace Tests\Unit;

use App\Services\ExcelReader;
use App\Services\ExcelWriter;
use Tests\TestCase;

class ExcelWriterTest extends TestCase
{
    public function test_col_index_to_letter(): void
    {
        $this->assertEquals('A', ExcelWriter::colIndexToLetter(0));
        $this->assertEquals('B', ExcelWriter::colIndexToLetter(1));
        $this->assertEquals('Z', ExcelWriter::colIndexToLetter(25));
        $this->assertEquals('AA', ExcelWriter::colIndexToLetter(26));
        $this->assertEquals('AB', ExcelWriter::colIndexToLetter(27));
    }

    public function test_generate_and_parse_xlsx_roundtrip(): void
    {
        $headers = ['No', 'NIS', 'Nama Siswa', 'Matematika', 'Bahasa Indonesia'];
        $rows = [
            ['1', '1001', 'Ahmad Fauzi', '85', '90'],
            ['2', '0102', 'Budi Santoso', '78', '82'],
            ['3', '1003', 'Citra Lestari & Kawan', '92', ''],
        ];

        $xlsxBinary = ExcelWriter::createXlsx($headers, $rows, 'Template Nilai');
        $this->assertNotEmpty($xlsxBinary);

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_xlsx_');
        file_put_contents($tmpFile, $xlsxBinary);

        $parsedRows = ExcelReader::parseXlsx($tmpFile);
        @unlink($tmpFile);

        $this->assertCount(4, $parsedRows); // 1 header + 3 data rows
        $this->assertEquals($headers, $parsedRows[0]);
        $this->assertEquals('Ahmad Fauzi', $parsedRows[1][2]);
        $this->assertEquals('85', $parsedRows[1][3]);
        $this->assertEquals('0102', $parsedRows[2][1]);
        $this->assertEquals('Citra Lestari & Kawan', $parsedRows[3][2]);
    }
}
