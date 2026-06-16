<?php

function siteDocxSanitizeString(string $value): string
{
    $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return (string)preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $decoded);
}

function siteDocxCreateDocument(string $templatePath, string $attachmentName, callable $mutator): array
{
    if (!class_exists('ZipArchive')) {
        return [
            'ok' => false,
            'error' => 'Класс ZipArchive недоступен на сервере.',
            'path' => '',
            'name' => '',
        ];
    }

    if (!class_exists('DOMDocument') || !class_exists('DOMXPath')) {
        return [
            'ok' => false,
            'error' => 'Расширение DOM недоступно на сервере.',
            'path' => '',
            'name' => '',
        ];
    }

    if (!is_file($templatePath)) {
        return [
            'ok' => false,
            'error' => 'Файл шаблона DOCX не найден.',
            'path' => '',
            'name' => '',
        ];
    }

    $tempBase = tempnam(sys_get_temp_dir(), 'docx_');
    if ($tempBase === false) {
        return [
            'ok' => false,
            'error' => 'Не удалось создать временный файл.',
            'path' => '',
            'name' => '',
        ];
    }

    @unlink($tempBase);
    $tempPath = $tempBase . '.docx';

    if (!copy($templatePath, $tempPath)) {
        return [
            'ok' => false,
            'error' => 'Не удалось скопировать шаблон DOCX.',
            'path' => '',
            'name' => '',
        ];
    }

    $zip = new ZipArchive();
    $openResult = $zip->open($tempPath);

    if ($openResult !== true) {
        @unlink($tempPath);

        return [
            'ok' => false,
            'error' => 'Не удалось открыть DOCX-архив для записи.',
            'path' => '',
            'name' => '',
        ];
    }

    $documentXml = $zip->getFromName('word/document.xml');
    if ($documentXml === false) {
        $zip->close();
        @unlink($tempPath);

        return [
            'ok' => false,
            'error' => 'В шаблоне отсутствует word/document.xml.',
            'path' => '',
            'name' => '',
        ];
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = true;
    $dom->formatOutput = false;

    if (!$dom->loadXML($documentXml)) {
        $zip->close();
        @unlink($tempPath);

        return [
            'ok' => false,
            'error' => 'Не удалось прочитать XML шаблона DOCX.',
            'path' => '',
            'name' => '',
        ];
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $mutator($dom, $xpath);

    $zip->addFromString('word/document.xml', $dom->saveXML());
    $zip->close();

    $safeAttachmentName = preg_match('/\.docx$/i', $attachmentName)
        ? $attachmentName
        : ($attachmentName . '.docx');

    return [
        'ok' => true,
        'error' => '',
        'path' => $tempPath,
        'name' => $safeAttachmentName,
    ];
}

function siteDocxNodeText(DOMXPath $xpath, DOMNode $node): string
{
    $parts = [];

    foreach ($xpath->query('.//w:t', $node) as $textNode) {
        $parts[] = $textNode->textContent;
    }

    return trim(preg_replace('/\s+/u', ' ', implode('', $parts)));
}

function siteDocxFirstRunProperties(DOMXPath $xpath, DOMNode $node): ?DOMElement
{
    foreach ($xpath->query('.//w:r[w:t][1]/w:rPr', $node) as $runProperties) {
        if ($runProperties instanceof DOMElement) {
            return $runProperties;
        }
    }

    foreach ($xpath->query('.//w:r[1]/w:rPr', $node) as $runProperties) {
        if ($runProperties instanceof DOMElement) {
            return $runProperties;
        }
    }

    return null;
}

function siteDocxCreateRunProperties(DOMDocument $dom, ?DOMElement $runProperties = null): DOMElement
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $result = $runProperties instanceof DOMElement
        ? $runProperties->cloneNode(true)
        : $dom->createElementNS($namespace, 'w:rPr');

    if (!($result instanceof DOMElement)) {
        $result = $dom->createElementNS($namespace, 'w:rPr');
    }

    $hasNoProof = false;
    foreach ($result->childNodes as $child) {
        if ($child instanceof DOMElement && $child->localName === 'noProof') {
            $hasNoProof = true;
            break;
        }
    }

    if (!$hasNoProof) {
        $result->appendChild($dom->createElementNS($namespace, 'w:noProof'));
    }

    return $result;
}

function siteDocxCreateValueParagraphProperties(DOMDocument $dom, ?DOMElement $paragraphProperties = null): ?DOMElement
{
    if (!($paragraphProperties instanceof DOMElement)) {
        return null;
    }

    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $result = $paragraphProperties->cloneNode(true);
    if (!($result instanceof DOMElement)) {
        return null;
    }

    $nodesToRemove = [];
    foreach ($result->childNodes as $child) {
        if (
            $child instanceof DOMElement
            && in_array($child->localName, ['numPr', 'tabs'], true)
        ) {
            $nodesToRemove[] = $child;
        }
    }

    foreach ($nodesToRemove as $child) {
        $result->removeChild($child);
    }

    foreach ($result->getElementsByTagNameNS($namespace, 'ind') as $indentation) {
        if ($indentation instanceof DOMElement) {
            foreach (['left', 'right', 'firstLine', 'hanging'] as $attribute) {
                $indentation->removeAttributeNS($namespace, $attribute);
            }
        }
    }

    return $result;
}

function siteDocxCreateParagraph(
    DOMDocument $dom,
    string $text,
    ?DOMElement $paragraphProperties = null,
    ?string $align = null,
    ?DOMElement $runProperties = null
): DOMElement
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $paragraph = $dom->createElementNS($namespace, 'w:p');

    if ($paragraphProperties instanceof DOMElement) {
        $paragraph->appendChild($paragraphProperties->cloneNode(true));
    }

    if ($align !== null) {
        $paragraphPr = null;
        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'pPr') {
                $paragraphPr = $child;
                break;
            }
        }

        if (!($paragraphPr instanceof DOMElement)) {
            $paragraphPr = $dom->createElementNS($namespace, 'w:pPr');
            $paragraph->appendChild($paragraphPr);
        }

        $jcNodes = [];
        foreach ($paragraphPr->getElementsByTagNameNS($namespace, 'jc') as $jcNode) {
            $jcNodes[] = $jcNode;
        }

        foreach ($jcNodes as $jcNode) {
            $paragraphPr->removeChild($jcNode);
        }

        $jc = $dom->createElementNS($namespace, 'w:jc');
        $jc->setAttributeNS($namespace, 'w:val', $align);
        $paragraphPr->appendChild($jc);
    }

    $lines = preg_split("/\r\n|\n|\r/", siteDocxSanitizeString($text));
    if ($lines === false || $lines === []) {
        $lines = [''];
    }

    foreach ($lines as $index => $line) {
        $segments = explode("\t", $line);

        foreach ($segments as $segmentIndex => $segment) {
            $run = $dom->createElementNS($namespace, 'w:r');

            $run->appendChild(siteDocxCreateRunProperties($dom, $runProperties));

            $textNode = $dom->createElementNS($namespace, 'w:t');
            if (preg_match('/^\s|\s$| {2,}/u', $segment)) {
                $textNode->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
            }
            $textNode->appendChild($dom->createTextNode($segment));
            $run->appendChild($textNode);
            $paragraph->appendChild($run);

            if ($segmentIndex < count($segments) - 1) {
                $tabRun = $dom->createElementNS($namespace, 'w:r');
                $tabRun->appendChild(siteDocxCreateRunProperties($dom, $runProperties));
                $tabRun->appendChild($dom->createElementNS($namespace, 'w:tab'));
                $paragraph->appendChild($tabRun);
            }
        }

        if ($index < count($lines) - 1) {
            $breakRun = $dom->createElementNS($namespace, 'w:r');
            $breakRun->appendChild(siteDocxCreateRunProperties($dom, $runProperties));
            $breakRun->appendChild($dom->createElementNS($namespace, 'w:br'));
            $paragraph->appendChild($breakRun);
        }
    }

    return $paragraph;
}

function siteDocxSetParagraphText(DOMDocument $dom, DOMXPath $xpath, DOMElement $paragraph, string $text, ?string $align = null): void
{
    $paragraphProperties = null;
    $runProperties = siteDocxFirstRunProperties($xpath, $paragraph);

    foreach ($xpath->query('./w:pPr', $paragraph) as $pPr) {
        $paragraphProperties = $pPr;
        break;
    }

    while ($paragraph->firstChild) {
        $paragraph->removeChild($paragraph->firstChild);
    }

    $newParagraph = siteDocxCreateParagraph($dom, $text, $paragraphProperties, $align, $runProperties);
    while ($newParagraph->firstChild) {
        $paragraph->appendChild($newParagraph->firstChild);
    }
}

function siteDocxSetCellText(DOMDocument $dom, DOMXPath $xpath, DOMElement $cell, string $text, ?string $align = null): void
{
    $cellProperties = null;
    $firstParagraphProperties = null;
    $firstRunProperties = siteDocxFirstRunProperties($xpath, $cell);

    foreach ($xpath->query('./w:tcPr', $cell) as $tcPr) {
        $cellProperties = $tcPr;
        break;
    }

    foreach ($xpath->query('./w:p[1]/w:pPr', $cell) as $pPr) {
        $firstParagraphProperties = $pPr;
        break;
    }

    while ($cell->firstChild) {
        $cell->removeChild($cell->firstChild);
    }

    if ($cellProperties instanceof DOMElement) {
        $cell->appendChild($cellProperties->cloneNode(true));
    }

    $cell->appendChild(
        siteDocxCreateParagraph(
            $dom,
            $text,
            siteDocxCreateValueParagraphProperties($dom, $firstParagraphProperties),
            $align,
            $firstRunProperties
        )
    );
}

function siteDocxFindParagraphByContains(DOMXPath $xpath, string $needle): ?DOMElement
{
    $needle = mb_strtolower($needle, 'UTF-8');

    foreach ($xpath->query('/w:document/w:body//w:p') as $paragraph) {
        $text = mb_strtolower(siteDocxNodeText($xpath, $paragraph), 'UTF-8');
        if ($text !== '' && mb_strpos($text, $needle, 0, 'UTF-8') !== false) {
            return $paragraph;
        }
    }

    return null;
}

function siteDocxInsertParagraphBeforeTable(DOMDocument $dom, DOMXPath $xpath, int $tableIndex, string $text, ?string $align = null): void
{
    $tables = $xpath->query('/w:document/w:body/w:tbl');
    $table = $tables->item($tableIndex - 1);

    if (!($table instanceof DOMElement) || !($table->parentNode instanceof DOMNode)) {
        return;
    }

    $table->parentNode->insertBefore(siteDocxCreateParagraph($dom, $text, null, $align), $table);
}

function siteDocxInsertParagraphAfter(DOMDocument $dom, DOMXPath $xpath, DOMElement $paragraph, string $text, ?string $align = null): ?DOMElement
{
    if (!($paragraph->parentNode instanceof DOMNode)) {
        return null;
    }

    $paragraphProperties = null;
    foreach ($xpath->query('./w:pPr', $paragraph) as $pPr) {
        if ($pPr instanceof DOMElement) {
            $paragraphProperties = $pPr;
            break;
        }
    }

    $newParagraph = siteDocxCreateParagraph($dom, $text, $paragraphProperties, $align, siteDocxFirstRunProperties($xpath, $paragraph));
    $nextSibling = $paragraph->nextSibling;

    if ($nextSibling instanceof DOMNode) {
        $paragraph->parentNode->insertBefore($newParagraph, $nextSibling);
    } else {
        $paragraph->parentNode->appendChild($newParagraph);
    }

    return $newParagraph;
}

function siteDocxFindNextEmptyParagraph(DOMXPath $xpath, DOMElement $paragraph, int $maxLookahead = 6): ?DOMElement
{
    $looked = 0;

    for ($node = $paragraph->nextSibling; $node !== null && $looked < $maxLookahead; $node = $node->nextSibling) {
        if (!($node instanceof DOMElement) || $node->localName !== 'p') {
            continue;
        }

        $looked++;
        $text = siteDocxNodeText($xpath, $node);
        $hasDrawing = $xpath->query('.//w:drawing|.//w:pict', $node)->length > 0;

        if ($text === '' && !$hasDrawing) {
            return $node;
        }
    }

    return null;
}

function siteDocxBodyParagraph(DOMXPath $xpath, int $index): ?DOMElement
{
    $paragraph = $xpath->query('/w:document/w:body/w:p')->item($index - 1);

    return $paragraph instanceof DOMElement ? $paragraph : null;
}

function siteDocxTable(DOMXPath $xpath, int $index): ?DOMElement
{
    $table = $xpath->query('/w:document/w:body/w:tbl')->item($index - 1);

    return $table instanceof DOMElement ? $table : null;
}

function siteDocxTableCell(DOMXPath $xpath, int $tableIndex, int $rowIndex, int $cellIndex): ?DOMElement
{
    $table = siteDocxTable($xpath, $tableIndex);
    if (!($table instanceof DOMElement)) {
        return null;
    }

    $rows = $xpath->query('./w:tr', $table);
    $row = $rows->item($rowIndex - 1);
    if (!($row instanceof DOMElement)) {
        return null;
    }

    $cells = $xpath->query('./w:tc', $row);
    $cell = $cells->item($cellIndex - 1);

    return $cell instanceof DOMElement ? $cell : null;
}

function siteDocxSetTableCellText(DOMDocument $dom, DOMXPath $xpath, int $tableIndex, int $rowIndex, int $cellIndex, string $text, ?string $align = null): void
{
    $cell = siteDocxTableCell($xpath, $tableIndex, $rowIndex, $cellIndex);
    if ($cell instanceof DOMElement) {
        siteDocxSetCellText($dom, $xpath, $cell, $text, $align);
    }
}

function siteDocxSetCellWidth(DOMDocument $dom, DOMXPath $xpath, DOMElement $cell, int $widthDxa, int $gridSpan = 1): void
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    $cellProperties = null;
    foreach ($xpath->query('./w:tcPr', $cell) as $tcPr) {
        if ($tcPr instanceof DOMElement) {
            $cellProperties = $tcPr;
            break;
        }
    }

    if (!($cellProperties instanceof DOMElement)) {
        $cellProperties = $dom->createElementNS($namespace, 'w:tcPr');
        if ($cell->firstChild instanceof DOMNode) {
            $cell->insertBefore($cellProperties, $cell->firstChild);
        } else {
            $cell->appendChild($cellProperties);
        }
    }

    $widthNode = null;
    foreach ($xpath->query('./w:tcW', $cellProperties) as $tcW) {
        if ($tcW instanceof DOMElement) {
            $widthNode = $tcW;
            break;
        }
    }

    if (!($widthNode instanceof DOMElement)) {
        $widthNode = $dom->createElementNS($namespace, 'w:tcW');
        $cellProperties->appendChild($widthNode);
    }

    $widthNode->setAttributeNS($namespace, 'w:w', (string)$widthDxa);
    $widthNode->setAttributeNS($namespace, 'w:type', 'dxa');

    $gridSpanNode = null;
    foreach ($xpath->query('./w:gridSpan', $cellProperties) as $gridSpanElement) {
        if ($gridSpanElement instanceof DOMElement) {
            $gridSpanNode = $gridSpanElement;
            break;
        }
    }

    if ($gridSpan > 1) {
        if (!($gridSpanNode instanceof DOMElement)) {
            $gridSpanNode = $dom->createElementNS($namespace, 'w:gridSpan');
            $cellProperties->appendChild($gridSpanNode);
        }

        $gridSpanNode->setAttributeNS($namespace, 'w:val', (string)$gridSpan);
    } elseif ($gridSpanNode instanceof DOMElement) {
        $cellProperties->removeChild($gridSpanNode);
    }
}

function siteDocxReplaceTableGrid(DOMDocument $dom, DOMXPath $xpath, DOMElement $table, array $columnWidthsDxa): void
{
    $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    $gridNodes = [];
    foreach ($xpath->query('./w:tblGrid', $table) as $gridNode) {
        if ($gridNode instanceof DOMNode) {
            $gridNodes[] = $gridNode;
        }
    }

    foreach ($gridNodes as $gridNode) {
        if ($gridNode->parentNode instanceof DOMNode) {
            $gridNode->parentNode->removeChild($gridNode);
        }
    }

    $tableGrid = $dom->createElementNS($namespace, 'w:tblGrid');
    foreach ($columnWidthsDxa as $widthDxa) {
        $gridColumn = $dom->createElementNS($namespace, 'w:gridCol');
        $gridColumn->setAttributeNS($namespace, 'w:w', (string)$widthDxa);
        $tableGrid->appendChild($gridColumn);
    }

    $insertAfter = null;
    foreach ($xpath->query('./w:tblPr', $table) as $tableProperties) {
        if ($tableProperties instanceof DOMNode) {
            $insertAfter = $tableProperties;
            break;
        }
    }

    if ($insertAfter instanceof DOMNode && $insertAfter->nextSibling instanceof DOMNode) {
        $table->insertBefore($tableGrid, $insertAfter->nextSibling);
    } elseif ($insertAfter instanceof DOMNode) {
        $table->appendChild($tableGrid);
    } elseif ($table->firstChild instanceof DOMNode) {
        $table->insertBefore($tableGrid, $table->firstChild);
    } else {
        $table->appendChild($tableGrid);
    }
}

function siteDocxRemoveNode(DOMNode $node): void
{
    if ($node->parentNode instanceof DOMNode) {
        $node->parentNode->removeChild($node);
    }
}

function siteDocxConvertRouRowToTdmRow(DOMDocument $dom, DOMXPath $xpath, DOMElement $row, bool $isHeader = false): DOMElement
{
    $cells = [];
    foreach ($xpath->query('./w:tc', $row) as $cell) {
        if ($cell instanceof DOMElement) {
            $cells[] = $cell;
        }
    }

    if (count($cells) < 5) {
        return $row;
    }

    $parameterCell = $cells[1];
    $designationCell = $cells[2];
    $unitCell = $cells[3];
    $valueCell = $cells[4];

    siteDocxRemoveNode($designationCell);

    siteDocxSetCellWidth($dom, $xpath, $parameterCell, 5008);
    siteDocxSetCellWidth($dom, $xpath, $unitCell, 1559);
    siteDocxSetCellWidth($dom, $xpath, $valueCell, 2794);

    if ($isHeader) {
        siteDocxSetCellText($dom, $xpath, $cells[0], '№ п/п', 'center');
        siteDocxSetCellText($dom, $xpath, $parameterCell, 'Наименование параметра', 'center');
        siteDocxSetCellText($dom, $xpath, $unitCell, 'Единица измерения', 'center');
        siteDocxSetCellText($dom, $xpath, $valueCell, 'Значение', 'center');
    }

    return $row;
}

function siteDocxRemoveLastRenderedPageBreaks(DOMXPath $xpath): void
{
    $pageBreaks = [];

    foreach ($xpath->query('//w:lastRenderedPageBreak') as $pageBreak) {
        if ($pageBreak instanceof DOMNode) {
            $pageBreaks[] = $pageBreak;
        }
    }

    foreach ($pageBreaks as $pageBreak) {
        if ($pageBreak->parentNode instanceof DOMNode) {
            $pageBreak->parentNode->removeChild($pageBreak);
        }
    }
}

function siteDocxRemoveProofErrors(DOMXPath $xpath): void
{
    $proofErrors = [];

    foreach ($xpath->query('//w:proofErr') as $proofError) {
        if ($proofError instanceof DOMNode) {
            $proofErrors[] = $proofError;
        }
    }

    foreach ($proofErrors as $proofError) {
        if ($proofError->parentNode instanceof DOMNode) {
            $proofError->parentNode->removeChild($proofError);
        }
    }
}

function siteDocxMergeTables(DOMXPath $xpath, int $firstTableIndex, int $secondTableIndex): void
{
    $tables = $xpath->query('/w:document/w:body/w:tbl');
    $firstTable = $tables->item($firstTableIndex - 1);
    $secondTable = $tables->item($secondTableIndex - 1);

    if (!($firstTable instanceof DOMElement) || !($secondTable instanceof DOMElement)) {
        return;
    }

    if (!($firstTable->parentNode instanceof DOMNode) || $firstTable->parentNode !== $secondTable->parentNode) {
        return;
    }

    $rowsToMove = [];
    foreach ($xpath->query('./w:tr', $secondTable) as $row) {
        if ($row instanceof DOMNode) {
            $rowsToMove[] = $row;
        }
    }

    foreach ($rowsToMove as $row) {
        $firstTable->appendChild($row);
    }

    $betweenNodes = [];
    for ($node = $firstTable->nextSibling; $node !== null && $node !== $secondTable; $node = $node->nextSibling) {
        $betweenNodes[] = $node;
    }

    foreach ($betweenNodes as $node) {
        if ($node->parentNode instanceof DOMNode) {
            $node->parentNode->removeChild($node);
        }
    }

    if ($secondTable->parentNode instanceof DOMNode) {
        $secondTable->parentNode->removeChild($secondTable);
    }
}

function siteDocxMarkSelection(DOMDocument $dom, DOMXPath $xpath, int $tableIndex, int $rowIndex, int $yesCellIndex, ?int $noCellIndex, bool $selected): void
{
    siteDocxSetTableCellText($dom, $xpath, $tableIndex, $rowIndex, $yesCellIndex, $selected ? 'X' : '', 'center');

    if ($noCellIndex !== null) {
        siteDocxSetTableCellText($dom, $xpath, $tableIndex, $rowIndex, $noCellIndex, $selected ? '' : 'X', 'center');
    }
}

function siteDocxJoinLines(array $lines): string
{
    $normalized = [];
    foreach ($lines as $line) {
        $line = trim(siteDocxSanitizeString((string)$line));
        if ($line !== '') {
            $normalized[] = $line;
        }
    }

    return implode("\n", $normalized);
}

function siteCreateTdmDocxFile(string $questionnaireId, array $data): array
{
    $templatePath = __DIR__ . '/templates/rou-template.docx';

    return siteDocxCreateDocument($templatePath, $questionnaireId . '.docx', function (DOMDocument $dom, DOMXPath $xpath) use ($data): void {
        $organization = siteDocxSanitizeString((string)($data['organization'] ?? ''));
        $email = siteDocxSanitizeString((string)($data['email'] ?? ''));
        $phone = siteDocxSanitizeString((string)($data['phone'] ?? ''));

        siteDocxSetTableCellText($dom, $xpath, 1, 1, 2, 'Опросный лист: Тягодутьевая машина', 'center');

        $paragraph = siteDocxFindParagraphByContains($xpath, 'Заказчик:');
        if ($paragraph instanceof DOMElement) {
            siteDocxSetParagraphText($dom, $xpath, $paragraph, 'Название организации: ' . $organization);
        }

        $paragraph = siteDocxFindParagraphByContains($xpath, 'Контактное лицо:');
        if ($paragraph instanceof DOMElement) {
            siteDocxSetParagraphText(
                $dom,
                $xpath,
                $paragraph,
                'Наименование организации, где будет применяться ТДМ: ' . $organization
            );
        }

        $paragraph = siteDocxFindParagraphByContains($xpath, 'Телефон/E-mail:');
        if ($paragraph instanceof DOMElement) {
            siteDocxSetParagraphText($dom, $xpath, $paragraph, 'Телефон/E-mail: ' . trim($phone . ' / ' . $email, ' /'));
        }

        $table = siteDocxTable($xpath, 2);
        if (!($table instanceof DOMElement)) {
            siteDocxRemoveProofErrors($xpath);
            return;
        }

        $rowNodes = [];
        foreach ($xpath->query('./w:tr', $table) as $rowNode) {
            if ($rowNode instanceof DOMElement) {
                $rowNodes[] = $rowNode;
            }
        }

        if (count($rowNodes) < 2) {
            siteDocxRemoveProofErrors($xpath);
            return;
        }

        $headerRow = siteDocxConvertRouRowToTdmRow($dom, $xpath, $rowNodes[0]->cloneNode(true), true);
        $prototypeRow = siteDocxConvertRouRowToTdmRow($dom, $xpath, $rowNodes[1]->cloneNode(true), false);

        foreach ($rowNodes as $rowNode) {
            siteDocxRemoveNode($rowNode);
        }

        siteDocxReplaceTableGrid($dom, $xpath, $table, [629, 5008, 1559, 2794]);
        $table->appendChild($headerRow);

        $rows = [
            ['1', 'Тип тягодутьевой машины', '', (string)($data['one'] ?? '')],
            ['2', 'Вид климатического исполнения по ГОСТ 15150', '', (string)($data['two'] ?? '')],
            ['3', 'Угол разворота спирального корпуса', 'градус', (string)($data['three'] ?? '')],
            ['3.1', 'Угол разворота всасывающего кармана', 'градус', (string)($data['three_one'] ?? '')],
            ['4', 'Количество машин', 'шт.', (string)($data['four'] ?? '')],
            ['4.1', 'Направление вращения рабочего колеса', '', (string)($data['four_one'] ?? '')],
            ['5', 'Назначение машины', '', (string)($data['five'] ?? '')],
            ['6', 'Желательный максимальный КПД', '%', (string)($data['six'] ?? '')],
            ['7.1', 'Плотность', 'кг/м³', (string)($data['seven_one'] ?? '')],
            ['7.2', 'Концентрация твердых примесей абразивной золы, пыли и т.д.', 'г/м³', (string)($data['seven_two'] ?? '')],
            ['8.1', 'Температура перемещаемой среды', '°C', (string)($data['eight_one'] ?? '')],
            ['8.2', 'Избыточное статическое давление (+) или разряжение (-) на входе в машину', 'Па', (string)($data['eight_two'] ?? '')],
            ['8.3', 'Барометрическое давление окружающей среды в месте установки ТДМ', 'Па', (string)($data['eight_three'] ?? '')],
            ['8.4', 'Производительность с учетом п. 8.1, 8.2', 'м³/ч', (string)($data['eight_four'] ?? '')],
            ['8.5', 'Полное давление с учетом пп. 7.1, 8.1, 8.2', 'Па', (string)($data['eight_five'] ?? '')],
            ['8.6', 'Склонность к отложению на лопатках рабочего колеса примесей, содержащихся в перемещаемых газах', '', (string)($data['eight_six'] ?? '')],
            ['8.7', 'Содержание агрессивных компонентов в перемещаемых газах в процентах и рекомендуемая марка материала', '', (string)($data['eight_seven'] ?? '')],
            ['8.8', 'Предельная температура перемещаемой среды', '°C', (string)($data['eight_eight'] ?? '')],
            ['8.9', 'Частота вращения рабочего колеса (желательная)', 'об/мин', (string)($data['eight_nine'] ?? '')],
            ['8.10', 'Необходимость регулирования производительности', '', (string)($data['eight_ten'] ?? '')],
            ['9.1', 'Тип двигателя', '', (string)($data['nine_one'] ?? '')],
            ['9.2', 'Вид климатического исполнения по ГОСТ 15150', '', (string)($data['nine_two'] ?? '')],
            ['9.3', 'Степень защиты по ГОСТ 17494 или исполнение двигателя', '', (string)($data['nine_three'] ?? '')],
            ['9.4', 'Напряжение сети', 'В', (string)($data['nine_four'] ?? '')],
            ['9.5', 'Частота тока', 'Гц', (string)($data['nine_five'] ?? '')],
            ['9.6', 'Дополнительные требования', '', (string)($data['nine_six'] ?? '')],
            ['10', 'Ориентировочный срок поставки машины', 'год', (string)($data['ten'] ?? '')],
        ];

        foreach ($rows as $rowData) {
            $newRow = siteDocxConvertRouRowToTdmRow($dom, $xpath, $prototypeRow->cloneNode(true), false);
            $cells = [];
            foreach ($xpath->query('./w:tc', $newRow) as $cell) {
                if ($cell instanceof DOMElement) {
                    $cells[] = $cell;
                }
            }

            if (count($cells) === 4) {
                siteDocxSetCellText($dom, $xpath, $cells[0], $rowData[0], 'center');
                siteDocxSetCellText($dom, $xpath, $cells[1], $rowData[1]);
                siteDocxSetCellText($dom, $xpath, $cells[2], $rowData[2], 'center');
                siteDocxSetCellText($dom, $xpath, $cells[3], $rowData[3], 'center');
            }

            $table->appendChild($newRow);
        }

        $noteParagraph = siteDocxFindParagraphByContains($xpath, 'диаметры Заказчика');
        if ($noteParagraph instanceof DOMElement) {
            siteDocxRemoveNode($noteParagraph);
        }

        siteDocxRemoveProofErrors($xpath);
    });
}

function siteCreateRouDocxFile(string $questionnaireId, array $data): array
{
    $templatePath = __DIR__ . '/templates/rou-template.docx';

    return siteDocxCreateDocument($templatePath, $questionnaireId . '.docx', function (DOMDocument $dom, DOMXPath $xpath) use ($questionnaireId, $data): void {
        $organization = siteDocxSanitizeString((string)($data['organization'] ?? ''));
        $email = siteDocxSanitizeString((string)($data['email'] ?? ''));
        $phone = siteDocxSanitizeString((string)($data['phone'] ?? ''));

        $paragraph = siteDocxFindParagraphByContains($xpath, 'Заказчик:');
        if ($paragraph instanceof DOMElement) {
            siteDocxSetParagraphText($dom, $xpath, $paragraph, 'Заказчик: ' . $organization);
        }

        $paragraph = siteDocxFindParagraphByContains($xpath, 'Телефон/E-mail:');
        if ($paragraph instanceof DOMElement) {
            siteDocxSetParagraphText($dom, $xpath, $paragraph, 'Телефон/E-mail: ' . trim($phone . ' / ' . $email, ' /'));
        }

        siteDocxSetTableCellText($dom, $xpath, 2, 2, 5, (string)($data['G'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 3, 5, (string)($data['P1'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 4, 5, (string)($data['P2'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 5, 5, (string)($data['T1'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 6, 5, (string)($data['T2'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 7, 5, (string)($data['Pv'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 8, 5, (string)($data['Tv'] ?? ''), 'center');
        siteDocxSetTableCellText($dom, $xpath, 2, 9, 5, (string)($data['execution'] ?? ''), 'center');

        $kitSelections = [
            11 => !empty($data['kit_inlet_gate']),
            12 => !empty($data['kit_outlet_gate']),
            13 => !empty($data['kit_electro_drive_gate']),
            14 => !empty($data['kit_manual_drive_gate']),
            15 => !empty($data['kit_electro_drive_ctrl']),
            16 => !empty($data['kit_pneumo_drive_ctrl']),
            17 => !empty($data['kit_check_valve']),
            18 => !empty($data['kit_drainage']),
            19 => !empty($data['kit_automation']),
        ];

        foreach ($kitSelections as $rowIndex => $selected) {
            siteDocxMarkSelection($dom, $xpath, 2, $rowIndex, 4, 5, $selected);
        }

        siteDocxSetTableCellText($dom, $xpath, 2, 20, 3, (string)($data['special_requirements'] ?? ''));
        siteDocxSetTableCellText(
            $dom,
            $xpath,
            2,
            21,
            5,
            siteDocxJoinLines([
                (string)($data['D_in'] ?? ''),
                (string)($data['D_out'] ?? ''),
                (string)($data['D_cw'] ?? ''),
            ]),
            'center'
        );

        $connectionType = siteDocxSanitizeString((string)($data['conn_type'] ?? ''));
        siteDocxMarkSelection($dom, $xpath, 2, 23, 6, 7, $connectionType === 'приварка');
        siteDocxMarkSelection($dom, $xpath, 2, 24, 6, 7, $connectionType === 'фланцы');
        siteDocxMarkSelection($dom, $xpath, 2, 25, 6, null, $connectionType === 'не важно');

        $supplyType = siteDocxSanitizeString((string)($data['supply_type'] ?? ''));
        siteDocxMarkSelection($dom, $xpath, 2, 26, 6, null, $supplyType === 'на ед.раме');
        siteDocxMarkSelection($dom, $xpath, 2, 27, 6, null, $supplyType === 'россыпью');

        siteDocxRemoveProofErrors($xpath);
    });
}
