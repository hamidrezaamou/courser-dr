<?php

namespace App\Support;

class SimpleXlsx
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function download(string $filename, array $headers, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $binary = self::build($headers, $rows);

        return response()->streamDownload(function () use ($binary) {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    public static function build(array $headers, array $rows): string
    {
        $sheet = self::sheetXml($headers, $rows);
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new \RuntimeException('امکان ساخت فایل اکسل نبود.');
        }
        @unlink($tmp);
        $zip = new \ZipArchive;
        if ($zip->open($tmp, \ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('ZipArchive در دسترس نیست.');
        }
        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);
        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="گزارش" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);
        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        $binary = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $binary;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private static function sheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        $xml .= self::rowXml(1, $headers);
        $r = 2;
        foreach ($rows as $row) {
            $xml .= self::rowXml($r, $row);
            $r++;
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  list<string>  $cells
     */
    private static function rowXml(int $rowNumber, array $cells): string
    {
        $xml = '<row r="'.$rowNumber.'">';
        foreach (array_values($cells) as $i => $value) {
            $col = self::columnLetter($i + 1);
            $xml .= '<c r="'.$col.$rowNumber.'" t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }

    private static function esc(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
