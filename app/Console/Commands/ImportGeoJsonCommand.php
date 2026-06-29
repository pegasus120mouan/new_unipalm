<?php

namespace App\Console\Commands;

use App\Services\DepartementGeoJsonImporter;
use App\Services\RegionGeoJsonImporter;
use App\Services\SousPrefectureGeoJsonImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportGeoJsonCommand extends Command
{
    protected $signature = 'geojson:import
                            {type : regions, departements ou sous-prefectures}
                            {file : Chemin absolu ou relatif vers le fichier .geojson}
                            {--mode=upsert : upsert ou create}';

    protected $description = 'Importer un fichier GeoJSON (contourne la limite de taille HTTP en production)';

    public function handle(
        RegionGeoJsonImporter $regionImporter,
        DepartementGeoJsonImporter $departementImporter,
        SousPrefectureGeoJsonImporter $sousPrefectureImporter,
    ): int {
        $type = strtolower(trim($this->argument('type')));
        $file = $this->argument('file');
        $mode = $this->option('mode') ?? 'upsert';

        if (! in_array($mode, ['upsert', 'create'], true)) {
            $this->error('Le mode doit être upsert ou create.');

            return self::FAILURE;
        }

        $path = $file;
        if (! is_file($path)) {
            $path = base_path($file);
        }

        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($extension, ['json', 'geojson'], true)) {
            $this->error('Le fichier doit avoir l\'extension .json ou .geojson.');

            return self::FAILURE;
        }

        $this->info('Lecture de '.$path.' ('.$this->formatBytes(filesize($path)).')…');

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (! is_array($data)) {
            $this->error('JSON invalide dans le fichier GeoJSON.');

            return self::FAILURE;
        }

        try {
            $result = match ($type) {
                'regions' => $regionImporter->import($data, $mode),
                'departements' => $departementImporter->import($data, $mode),
                'sous-prefectures', 'sous_prefectures' => $sousPrefectureImporter->import($data, $mode),
                default => throw new InvalidArgumentException("Type inconnu « {$type} ». Utilisez : regions, departements, sous-prefectures."),
            };
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf(
            'Import terminé : %d créé(s), %d mis à jour, %d ignoré(s).',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));

        if ($result['errors'] !== []) {
            $this->newLine();
            $this->warn('Avertissements (10 premiers) :');
            foreach (array_slice($result['errors'], 0, 10) as $error) {
                $this->line('  - '.$error);
            }

            if (count($result['errors']) > 10) {
                $this->line('  … et '.(count($result['errors']) - 10).' autre(s).');
            }
        }

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' Mo';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' Ko';
        }

        return $bytes.' o';
    }
}
