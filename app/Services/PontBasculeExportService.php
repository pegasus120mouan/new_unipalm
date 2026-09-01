<?php

namespace App\Services;

use App\Models\PontBascule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PontBasculeExportService
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'Code',
        'Nom du pont',
        'Type',
        'Région',
        'Département',
        'Sous-préfecture',
        'Village',
        'Gérant',
        'Coopérative',
        'Commis',
        'Latitude',
        'Longitude',
        'Statut',
    ];

    public function streamAll(): StreamedResponse
    {
        $rows = [self::HEADERS];

        $ponts = PontBascule::query()
            ->with(['typePont', 'agent', 'commis', 'region', 'departement', 'sousPrefecture', 'village'])
            ->orderBy('code_pont')
            ->orderBy('id_pont')
            ->get();

        foreach ($ponts as $pont) {
            $rows[] = $this->mapPontRow($pont);
        }

        $binary = $this->buildXlsx($rows);
        $filename = 'liste_ponts_'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return list<string>
     */
    private function mapPontRow(PontBascule $pont): array
    {
        return [
            (string) ($pont->code_pont ?? ''),
            (string) ($pont->nom_pont ?? ''),
            (string) ($pont->typePont?->libelle ?? ''),
            (string) ($pont->region?->nom ?? ''),
            (string) ($pont->departement?->nom ?? ''),
            (string) ($pont->sousPrefecture?->nom ?? ''),
            (string) ($pont->village?->nom ?? ''),
            $pont->gerantLabel() === '—' ? '' : $pont->gerantLabel(),
            (string) ($pont->cooperatif ?? ''),
            (string) ($pont->commis?->full_name ?? ''),
            $pont->hasCoordinates() ? (string) $pont->latitude : '',
            $pont->hasCoordinates() ? (string) $pont->longitude : '',
            (string) ($pont->statut ?? ''),
        ];
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function buildXlsx(array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ponts_xlsx_');
        if ($tmp === false) {
            throw new \RuntimeException('Impossible de créer le fichier Excel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new \RuntimeException('Impossible de créer le fichier Excel.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Ponts" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildSheetXml($rows));
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        if ($binary === false) {
            throw new \RuntimeException('Impossible de lire le fichier Excel.');
        }

        return $binary;
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function buildSheetXml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        foreach ($rows as $rowIndex => $cells) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="'.$rowNumber.'">';

            foreach ($cells as $colIndex => $value) {
                $cellRef = $this->columnLetter($colIndex).$rowNumber;
                $xml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'
                    .$this->escapeXml((string) $value)
                    .'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function columnLetter(int $zeroBasedIndex): string
    {
        $letter = '';
        $index = $zeroBasedIndex + 1;

        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function escapeXml(string $value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? $value;

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
