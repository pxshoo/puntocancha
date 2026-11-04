<?php

namespace App\Services;

use RuntimeException;
use XMLWriter;
use ZipArchive;

class ExcelWorkbook
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** @param array<string, iterable<array>> $sheets */
    public function create(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'puntocancha-');
        $zip = new ZipArchive;
        $temporarySheets = [];
        $opened = false;
        try {
            if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo crear el archivo Excel.');
            }
            $opened = true;
            $content = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
            $workbook = '<workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
            $relations = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
            $index = 0;
            foreach ($sheets as $name => $rows) {
                $index++;
                $temporary = tempnam(sys_get_temp_dir(), 'pc-sheet-');
                if ($temporary === false) {
                    throw new RuntimeException('No se pudo crear la hoja.');
                }
                $temporarySheets[] = $temporary;
                $this->writeSheet($temporary, $rows, $name);
                $zip->addFile($temporary, "xl/worksheets/sheet{$index}.xml");
                $content .= '<Override PartName="/xl/worksheets/sheet'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $workbook .= '<sheet name="'.htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="'.$index.'" r:id="rId'.$index.'"/>';
                $relations .= '<Relationship Id="rId'.$index.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$index.'.xml"/>';
            }
            $content .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
            $relations .= '<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString('[Content_Types].xml', $content.'</Types>');
            $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', $workbook.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', $relations.'</Relationships>');
            if (! $zip->close()) {
                throw new RuntimeException('No se pudo finalizar el archivo Excel.');
            }

            $opened = false;

            return $path;
        } catch (\Throwable $error) {
            if ($opened) {
                $zip->close();
            }
            if ($path && is_file($path)) {
                unlink($path);
            }
            throw $error;
        } finally {
            foreach ($temporarySheets as $temporary) {
                unlink($temporary);
            }
        }
    }

    private function writeSheet(string $path, iterable $rows, string $name): void
    {
        $xml = new XMLWriter;
        $xml->openUri($path);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElementNS(null, 'worksheet', self::NS);
        $xml->startElement('sheetViews');
        $xml->startElement('sheetView');
        $xml->writeAttribute('workbookViewId', '0');
        $xml->writeAttribute('showGridLines', '0');
        $xml->startElement('pane');
        $xml->writeAttribute('ySplit', '1');
        $xml->writeAttribute('topLeftCell', 'A2');
        $xml->writeAttribute('state', 'frozen');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $summary = $name === 'Resumen ganancias';
        $widths = match ($name) {
            'Resumen ganancias' => [46, 96],
            'Inventario actual' => [38, 25, 22, 12, 12, 16, 23, 23, 29],
            'Ventas y costos' => [13, 24, 18, 18, 13, 22, 25, 22],
            'Arriendos' => [13, 30, 24, 24, 20, 22, 22, 22],
            'Gastos' => [18, 26, 48, 26, 23],
            default => [40, 40],
        };
        $xml->startElement('cols');
        foreach ($widths as $column => $width) {
            $xml->startElement('col');
            $xml->writeAttribute('min', (string) ($column + 1));
            $xml->writeAttribute('max', (string) ($column + 1));
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('customWidth', '1');
            $xml->endElement();
        }
        $xml->endElement();
        $headers = [];
        $xml->startElement('sheetData');
        $rowNumber = 0;
        foreach ($rows as $row) {
            if (++$rowNumber > 1048576) {
                throw new RuntimeException('Demasiadas filas. Reduce el rango de fechas.');
            }
            if ($rowNumber === 1) {
                $headers = $row;
            }
            $xml->startElement('row');
            $xml->writeAttribute('r', (string) $rowNumber);
            $xml->writeAttribute('ht', (string) ($summary && $rowNumber >= 14 && $rowNumber <= 17 ? 44 : ($rowNumber === 1 ? 38 : 30)));
            $xml->writeAttribute('customHeight', '1');
            foreach ($row as $column => $value) {
                $xml->startElement('c');
                $numeric = is_int($value) || is_float($value);
                $money = $numeric && ($summary || str_contains($headers[$column] ?? '', 'CLP'));
                $highlight = $summary && in_array($rowNumber, [7, 11], true);
                $style = $rowNumber === 1 ? 1 : ($highlight ? ($money ? 5 : 4) : ($money ? ($rowNumber % 2 === 0 ? 7 : 3) : ($rowNumber % 2 === 0 ? 6 : 2)));
                $xml->writeAttribute('s', (string) $style);
                if (is_int($value) || is_float($value)) {
                    $xml->writeElement('v', (string) $value);
                } else {
                    $xml->writeAttribute('t', 'inlineStr');
                    $xml->startElement('is');
                    $xml->writeElement('t', preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value) ?? '');
                    $xml->endElement();
                }
                $xml->endElement();
            }
            $xml->endElement();
        }
        $xml->endElement();
        if (! $summary && $rowNumber > 1) {
            $xml->startElement('autoFilter');
            $xml->writeAttribute('ref', 'A1:'.chr(64 + count($widths)).$rowNumber);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="'.self::NS.'">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;$&quot; #,##0.00;[Red]-&quot;$&quot; #,##0.00"/></numFmts>'
            .'<fonts count="3"><font><sz val="11"/><color rgb="FF334155"/><name val="Calibri"/></font><font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FF065F46"/><name val="Calibri"/></font></fonts>'
            .'<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD1FAE5"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
