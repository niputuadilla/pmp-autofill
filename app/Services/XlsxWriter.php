<?php

namespace App\Services;

use App\Support\Pmp;
use RuntimeException;
use ZipArchive;

/**
 * Penulis .xlsx minimal tanpa dependensi (hanya ext-zip).
 * Semua sel ditulis sebagai teks supaya angka nol di depan (NIK, tanggal, no HP) tidak hilang.
 */
class XlsxWriter
{
    /**
     * Buat BAOWNITEMPLATE.xlsx: sheet "BAO WNI PMP" (header + baris data) dan "PETUNJUK PENGISIAN".
     *
     * @param array<int,array<string,string>> $rows nilai per kolom (key = Pmp::COLUMNS)
     */
    public static function baoWniTemplate(array $rows, string $path): void
    {
        $keys = array_keys(Pmp::COLUMNS);
        $headers = array_values(Pmp::COLUMNS);

        $data = [$headers];
        foreach ($rows as $r) {
            $line = [];
            foreach ($keys as $k) $line[] = (string)($r[$k] ?? '');
            $data[] = $line;
        }

        $guide = [$headers, array_map(fn($k) => Pmp::instruction($k), $keys)];

        self::write($path, [
            ['name' => 'BAO WNI PMP', 'rows' => $data, 'headerRows' => 1, 'bodyStyle' => 2, 'widths' => self::widths($keys, false)],
            ['name' => 'PETUNJUK PENGISIAN', 'rows' => $guide, 'headerRows' => 1, 'bodyStyle' => 3, 'widths' => self::widths($keys, true)],
        ]);
    }

    private static function widths(array $keys, bool $guide): array
    {
        $w = [];
        foreach ($keys as $k) {
            $base = match ($k) {
                'nama', 'alamat1', 'alamat2', 'email', 'ibu_kandung' => 30,
                'no_ktp', 'npwp', 'tgl_lhr', 'no_hp' => 20,
                default => 14,
            };
            $w[] = $guide ? max($base, 32) : $base;
        }
        return $w;
    }

    /** @param array<int,array{name:string,rows:array,headerRows:int,bodyStyle:int,widths:array}> $sheets */
    private static function write(string $path, array $sheets): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak bisa membuat file xlsx: ' . $path);
        }

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($sheets as $i => $_) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $ct);

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheets as $i => $s) {
            $n = $i + 1;
            $wb .= '<sheet name="' . self::esc($s['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        }
        $wb .= '</sheets></workbook>';
        $rels .= '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);

        // style 0 default, 1 header kuning, 2 body teks (@), 3 teks panduan (wrap, atas)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFFF00"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="49" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        foreach ($sheets as $i => $s) {
            $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
            $x .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
            $x .= '<cols>';
            foreach ($s['widths'] as $c => $w) {
                $x .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $x .= '</cols><sheetData>';
            foreach ($s['rows'] as $r => $line) {
                $rowNum = $r + 1;
                $style = $r < $s['headerRows'] ? 1 : $s['bodyStyle'];
                $x .= '<row r="' . $rowNum . '">';
                foreach ($line as $c => $val) {
                    $ref = self::col($c) . $rowNum;
                    $x .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . self::esc((string)$val) . '</t></is></c>';
                }
                $x .= '</row>';
            }
            $x .= '</sheetData></worksheet>';
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $x);
        }

        $zip->close();
    }

    private static function col(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    private static function esc(string $s): string
    {
        // buang karakter kontrol yang tidak valid di XML 1.0
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
