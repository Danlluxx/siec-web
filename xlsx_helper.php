<?php

function siteXlsxSanitizeString(string $value): string
{
    return (string)preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);
}

function siteXlsxEscape(string $value): string
{
    return htmlspecialchars(siteXlsxSanitizeString($value), ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function siteXlsxSheetName(string $value): string
{
    $value = trim((string)preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]+/u', ' ', $value));

    if ($value === '') {
        $value = 'Sheet1';
    }

    return function_exists('mb_substr')
        ? mb_substr($value, 0, 31, 'UTF-8')
        : substr($value, 0, 31);
}

function siteXlsxStringLength(string $value): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : strlen($value);
}

function siteXlsxColumnName(int $columnNumber): string
{
    $name = '';

    while ($columnNumber > 0) {
        $columnNumber--;
        $name = chr(65 + ($columnNumber % 26)) . $name;
        $columnNumber = (int)floor($columnNumber / 26);
    }

    return $name;
}

function siteXlsxCellReference(int $rowNumber, int $columnNumber): string
{
    return siteXlsxColumnName($columnNumber) . $rowNumber;
}

function siteXlsxNormalizeCell($cell): array
{
    if (is_array($cell)) {
        return [
            'value' => html_entity_decode((string)($cell['value'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'style' => (int)($cell['style'] ?? 0),
        ];
    }

    return [
        'value' => html_entity_decode((string)$cell, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'style' => 0,
    ];
}

function siteBuildXlsxWorksheet(array $rows): string
{
    $sheetRows = [];
    $maxColumns = 1;
    $columnWidths = [];

    foreach ($rows as $rowIndex => $row) {
        $rowNumber = $rowIndex + 1;
        $rowCells = [];
        $cells = is_array($row) ? $row : [$row];
        $maxColumns = max($maxColumns, count($cells));

        foreach ($cells as $columnIndex => $cell) {
            $normalized = siteXlsxNormalizeCell($cell);
            $columnNumber = $columnIndex + 1;
            $ref = siteXlsxCellReference($rowNumber, $columnNumber);
            $value = $normalized['value'];
            $escapedValue = siteXlsxEscape($value);
            $style = max(0, (int)$normalized['style']);

            $rowCells[] = '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $escapedValue . '</t></is></c>';

            $columnWidths[$columnNumber] = max(
                $columnWidths[$columnNumber] ?? 10,
                min(60, max(10, siteXlsxStringLength($value) + 2))
            );
        }

        $sheetRows[] = '<row r="' . $rowNumber . '">' . implode('', $rowCells) . '</row>';
    }

    $cols = [];
    for ($columnNumber = 1; $columnNumber <= $maxColumns; $columnNumber++) {
        $width = $columnWidths[$columnNumber] ?? 14;
        $cols[] = '<col min="' . $columnNumber . '" max="' . $columnNumber . '" width="' . $width . '" customWidth="1"/>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="18"/>'
        . '<cols>' . implode('', $cols) . '</cols>'
        . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
        . '</worksheet>';
}

function siteBuildXlsxStyles(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2">'
        . '<font><sz val="11"/><name val="Arial"/><family val="2"/></font>'
        . '<font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Arial"/><family val="2"/></font>'
        . '</fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF14B17"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border>'
        . '<left style="thin"><color rgb="FFD9DEE6"/></left>'
        . '<right style="thin"><color rgb="FFD9DEE6"/></right>'
        . '<top style="thin"><color rgb="FFD9DEE6"/></top>'
        . '<bottom style="thin"><color rgb="FFD9DEE6"/></bottom>'
        . '<diagonal/>'
        . '</border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function siteCreateXlsxFile(string $attachmentName, array $rows, string $sheetName = 'Опросный лист'): array
{
    if (!class_exists('ZipArchive')) {
        return [
            'ok' => false,
            'error' => 'Класс ZipArchive недоступен на сервере.',
            'path' => '',
            'name' => '',
        ];
    }

    $tempBase = tempnam(sys_get_temp_dir(), 'xlsx_');
    if ($tempBase === false) {
        return [
            'ok' => false,
            'error' => 'Не удалось создать временный файл.',
            'path' => '',
            'name' => '',
        ];
    }

    @unlink($tempBase);
    $tempPath = $tempBase . '.xlsx';

    $zip = new ZipArchive();
    $openResult = $zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    if ($openResult !== true) {
        return [
            'ok' => false,
            'error' => 'Не удалось открыть XLSX-архив для записи.',
            'path' => '',
            'name' => '',
        ];
    }

    $sheetTitle = siteXlsxSheetName($sheetName);
    $createdAt = gmdate('Y-m-d\TH:i:s\Z');
    $safeAttachmentName = preg_match('/\.xlsx$/i', $attachmentName) ? $attachmentName : ($attachmentName . '.xlsx');

    $zip->addFromString(
        '[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
        . '</Types>'
    );

    $zip->addFromString(
        '_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>'
    );

    $zip->addFromString(
        'docProps/core.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . siteXlsxEscape($sheetTitle) . '</dc:title>'
        . '<dc:creator>Сибирская энергетическая компания</dc:creator>'
        . '<cp:lastModifiedBy>Сибирская энергетическая компания</cp:lastModifiedBy>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:modified>'
        . '</cp:coreProperties>'
    );

    $zip->addFromString(
        'docProps/app.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
        . '<Application>PHP</Application>'
        . '<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>1</vt:i4></vt:variant></vt:vector></HeadingPairs>'
        . '<TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>' . siteXlsxEscape($sheetTitle) . '</vt:lpstr></vt:vector></TitlesOfParts>'
        . '</Properties>'
    );

    $zip->addFromString(
        'xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="' . siteXlsxEscape($sheetTitle) . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>'
    );

    $zip->addFromString(
        'xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>'
    );

    $zip->addFromString('xl/styles.xml', siteBuildXlsxStyles());
    $zip->addFromString('xl/worksheets/sheet1.xml', siteBuildXlsxWorksheet($rows));
    $zip->close();

    return [
        'ok' => true,
        'error' => '',
        'path' => $tempPath,
        'name' => $safeAttachmentName,
    ];
}
