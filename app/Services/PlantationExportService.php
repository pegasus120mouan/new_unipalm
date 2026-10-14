<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class PlantationExportService
{
    /**
     * @var list<string>
     */
    private const HEADERS = [
        'N° champ',
        'Région',
        'Sous-préfecture',
        'Village',
        'Culture',
        'Superficie (ha)',
        'Superficie GPS (ha)',
        'Parcelle',
        'Planteur',
        'N° fiche',
        'Téléphone',
        'Collecteur',
    ];

    public function __construct(
        private readonly PlanteurApiService $planteurApi,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public function csv(array $query): StreamedResponse
    {
        $dataset = $this->planteurApi->plantationDataset($query);
        $filename = $this->filename('csv');

        return response()->streamDownload(function () use ($dataset): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::HEADERS, ';');
            foreach ($dataset['rows'] as $row) {
                fputcsv($out, $this->mapRow($row), ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function xls(array $query): StreamedResponse
    {
        $dataset = $this->planteurApi->plantationDataset($query);
        $rows = [self::HEADERS];
        foreach ($dataset['rows'] as $row) {
            $rows[] = $this->mapRow($row);
        }

        $binary = $this->buildXlsx($rows);
        $filename = $this->filename('xlsx');

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function pdf(array $query): Response
    {
        $dataset = $this->planteurApi->plantationDataset($query);
        $logoPath = public_path('assets/images/logo/logo.png');

        return Pdf::loadView('plantations.export-pdf', [
            'rows' => array_map(fn (array $row): array => $this->mapRow($row), $dataset['rows']),
            'headers' => self::HEADERS,
            'total' => count($dataset['rows']),
            'superficieTotale' => $dataset['superficie_totale'],
            'parcellesTracees' => $dataset['parcelles_tracees'],
            'nbPlanteurs' => $dataset['nb_planteurs'],
            'filters' => $dataset['filters'],
            'generatedAt' => now(),
            'logoPath' => is_file($logoPath) ? $logoPath : null,
        ])
            ->setPaper('a4', 'landscape')
            ->download($this->filename('pdf'));
    }

    private function filename(string $extension): string
    {
        return 'plantations_'.now()->format('d-m-Y').'.'.$extension;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function mapRow(array $row): array
    {
        $superficie = (float) ($row['superficie_ha'] ?? 0);
        $gps = (float) ($row['superficie_gps'] ?? 0);
        $affichee = $superficie > 0 ? $superficie : $gps;

        return [
            (string) ($row['numero_champ'] ?? ''),
            (string) ($row['region'] ?? ''),
            (string) ($row['sous_prefecture'] ?? ''),
            (string) ($row['village'] ?? ''),
            (string) ($row['type_culture'] ?? ''),
            $this->formatHa($affichee),
            $this->formatHa($gps),
            ! empty($row['parcelle_tracee']) ? 'Tracée' : 'Non tracée',
            (string) ($row['planteur_nom'] ?? ''),
            (string) ($row['numero_fiche'] ?? ''),
            (string) ($row['telephone'] ?? ''),
            (string) ($row['collecteur'] ?? ''),
        ];
    }

    private function formatHa(float $value): string
    {
        if ($value <= 0) {
            return '';
        }

        return number_format($value, 2, ',', ' ');
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function buildXlsx(array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'plantations_xlsx_');
        if ($tmp === false) {
            throw new \RuntimeException('Impossible de créer le fichier Excel.');
        }

        $zip = new ZipArchive;
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
    <sheet name="Plantations" sheetId="1" r:id="rId1"/>
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
