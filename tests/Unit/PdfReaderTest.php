<?php

namespace Tests\Unit;

use App\Services\ExcelReader;
use App\Services\PdfReader;
use Dompdf\Dompdf;
use Tests\TestCase;

class PdfReaderTest extends TestCase
{
    public function test_parse_pdf_table_content(): void
    {
        $dompdf = new Dompdf;
        $html = '
        <!DOCTYPE html>
        <html>
        <head><title>SIA Test</title></head>
        <body>
            <table border="1">
                <tr><th>No</th><th>NIS</th><th>Nama Siswa</th><th>Sakit</th><th>Izin</th><th>Alpa</th><th>Keimanan</th></tr>
                <tr><td>1</td><td>1001</td><td>Ahmad Dani</td><td>1</td><td>2</td><td>0</td><td>Baik</td></tr>
                <tr><td>2</td><td>1002</td><td>Budi Santoso</td><td>0</td><td>0</td><td>1</td><td>Sangat Baik</td></tr>
            </table>
        </body>
        </html>
        ';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $rows = PdfReader::parseContent($pdfOutput);

        $this->assertNotEmpty($rows);
        $this->assertGreaterThanOrEqual(3, count($rows));

        // Periksa apakah baris header terdeteksi
        $header = $rows[0];
        $this->assertContains('No', $header);
        $this->assertContains('Nama Siswa', $header);
        $this->assertContains('Sakit', $header);
        $this->assertContains('Izin', $header);
        $this->assertContains('Alpa', $header);

        // Periksa integrasi dengan ExcelReader::parseFile
        $tmpPdf = tempnam(sys_get_temp_dir(), 'test_pdf_').'.pdf';
        file_put_contents($tmpPdf, $pdfOutput);

        $excelRows = ExcelReader::parseFile($tmpPdf, 'pdf');
        unlink($tmpPdf);

        $this->assertNotEmpty($excelRows);
        $this->assertGreaterThanOrEqual(3, count($excelRows));
    }
}
