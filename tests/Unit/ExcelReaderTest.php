<?php

namespace Tests\Unit;

use App\Services\ExcelReader;
use Tests\TestCase;

class ExcelReaderTest extends TestCase
{
    public function test_parse_clipboard_tab_separated(): void
    {
        $text = "Ahmad Fauzi\t1001\t0151234501\nAnisa Rahma\t1002\t0151234502";
        $rows = ExcelReader::parseClipboard($text);
        $this->assertCount(2, $rows);

        $students = ExcelReader::extractStudents($rows);
        $this->assertCount(2, $students);
        $this->assertEquals('Ahmad Fauzi', $students[0]['nama']);
        $this->assertEquals('1001', $students[0]['nis']);
        $this->assertEquals('0151234501', $students[0]['nisn']);
    }

    public function test_extract_students_with_header_and_no(): void
    {
        $rows = [
            ['No', 'Nama Siswa', 'NIS', 'NISN'],
            ['1', 'Budi Santoso', '1003', '0151234503'],
            ['2', 'Citra Lestari', '1004', '0151234504'],
        ];

        $students = ExcelReader::extractStudents($rows);
        $this->assertCount(2, $students);
        $this->assertEquals('Budi Santoso', $students[0]['nama']);
        $this->assertEquals('1003', $students[0]['nis']);
        $this->assertEquals('0151234503', $students[0]['nisn']);
    }

    public function test_parse_csv_file(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'csv_test');
        file_put_contents($tmp, "Nama,NIS,NISN\nDoni Pratama,1005,0151234505\nEka Putri,1006,0151234506");

        $rows = ExcelReader::parseCsv($tmp);
        unlink($tmp);

        $students = ExcelReader::extractStudents($rows);
        $this->assertCount(2, $students);
        $this->assertEquals('Doni Pratama', $students[0]['nama']);
        $this->assertEquals('1005', $students[0]['nis']);
    }

    public function test_extract_students_from_dapodik_format_with_multi_row_headers(): void
    {
        $rows = [
            ['Daftar Peserta Didik'],
            ['SD NEGERI 2 KARANGSARI'],
            ['Kecamatan Sempu'],
            ['Tanggal Unduh: 2026-08-25'],
            ['No', 'Nama', 'NIPD', 'JK', 'NISN', 'Tempat Lahir', 'Rombel Saat Ini'],
            ['', '', '', '', '', '', ''],
            ['1.0', 'ABIDZAR AZKA AL GIFRAN', '2237.0', 'L', '0142024201', 'Banyuwangi', 'Kelas 6'],
            ['2.0', 'ABIGAIL NOSTRADAMUS DIVARI', '2274.0', 'P', '3141603806', 'Banyuwangi', 'Kelas 6'],
            ['3.0', 'Adam Dwi Sakti', '2287.0', 'L', '3183328876', 'Banyuwangi', 'Kelas 3'],
        ];

        $students = ExcelReader::extractStudents($rows);
        $this->assertCount(3, $students);
        $this->assertEquals('ABIDZAR AZKA AL GIFRAN', $students[0]['nama']);
        $this->assertEquals('2237', $students[0]['nis']);
        $this->assertEquals('0142024201', $students[0]['nisn']);
        $this->assertEquals('Kelas 6', $students[0]['kelas']);

        $this->assertEquals('Adam Dwi Sakti', $students[2]['nama']);
        $this->assertEquals('2287', $students[2]['nis']);
        $this->assertEquals('Kelas 3', $students[2]['kelas']);
    }
}
