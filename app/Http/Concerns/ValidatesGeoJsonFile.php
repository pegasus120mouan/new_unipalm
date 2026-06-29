<?php

namespace App\Http\Concerns;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

trait ValidatesGeoJsonFile
{
    /**
     * @return array{file: UploadedFile, mode: string}
     */
    protected function validateGeoJsonImport(Request $request): array
    {
        $mode = $request->input('mode', 'upsert');
        if (! in_array($mode, ['create', 'upsert'], true)) {
            $mode = 'upsert';
        }

        if (! $request->hasFile('geojson_file')) {
            $postMax = ini_get('post_max_size') ?: '?';
            $uploadMax = ini_get('upload_max_filesize') ?: '?';

            throw ValidationException::withMessages([
                'geojson_file' => "Aucun fichier reçu par le serveur. Si le fichier GeoJSON est volumineux, les limites PHP en production sont peut-être trop basses (post_max_size={$postMax}, upload_max_filesize={$uploadMax}). Demandez à l'hébergeur d'augmenter ces valeurs ainsi que client_max_body_size (nginx).",
            ]);
        }

        $file = $request->file('geojson_file');

        if ($file === null || ! $file->isValid()) {
            throw ValidationException::withMessages([
                'geojson_file' => $this->geoJsonUploadErrorMessage($file),
            ]);
        }

        $maxKilobytes = 102400;
        if ($file->getSize() > $maxKilobytes * 1024) {
            throw ValidationException::withMessages([
                'geojson_file' => 'Le fichier dépasse la taille maximale autorisée (100 Mo).',
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['json', 'geojson'], true)) {
            throw ValidationException::withMessages([
                'geojson_file' => 'Le fichier doit être au format .json ou .geojson.',
            ]);
        }

        return ['file' => $file, 'mode' => $mode];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeGeoJsonFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw ValidationException::withMessages([
                'geojson_file' => 'Le fichier GeoJSON n\'est pas un JSON valide.',
            ]);
        }

        return $data;
    }

    private function geoJsonUploadErrorMessage(?UploadedFile $file): string
    {
        $uploadMax = ini_get('upload_max_filesize') ?: '?';

        if ($file === null) {
            return "Échec de l'envoi du fichier. Vérifiez les limites serveur (upload_max_filesize={$uploadMax}).";
        }

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la limite PHP upload_max_filesize (actuellement {$uploadMax}). Les fichiers GeoJSON nationaux dépassent souvent 2 Mo : augmentez upload_max_filesize et post_max_size (ex. 128M) sur le serveur de production.",
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite de taille autorisée par le formulaire.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement envoyé. Réessayez avec une connexion stable.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Erreur serveur lors de l\'enregistrement temporaire du fichier. Contactez l\'administrateur.',
            default => "Échec de l'envoi du fichier (code {$file->getError()}). Limite PHP actuelle : upload_max_filesize={$uploadMax}.",
        };
    }
}
