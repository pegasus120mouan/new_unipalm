<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Services\MinioStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class UtilisateurPhotoController extends Controller
{
    public function __construct(
        private readonly MinioStorageService $minio,
    ) {}

    public function store(Request $request, Utilisateur $utilisateur): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        try {
            $objectKey = $this->minio->uploadUserPhoto($request->file('photo'), (int) $utilisateur->id);

            $utilisateur->update([
                'avatar' => $objectKey,
            ]);

            $utilisateur->refresh();
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['photo' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('utilisateurs.show', $utilisateur)
            ->with('success', 'Photo enregistrée avec succès.');
    }

    public function show(Utilisateur $utilisateur): Response
    {
        $objectKey = $utilisateur->resolveMinioObjectKey();

        if ($objectKey === null || ! $this->minio->isConfigured()) {
            abort(404);
        }

        $content = $this->minio->getObjectContents($objectKey);

        if ($content === null) {
            abort(404);
        }

        $extension = strtolower(pathinfo($objectKey, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime = $mimeTypes[$extension] ?? 'image/jpeg';

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'ETag' => '"'.md5($objectKey).'"',
        ]);
    }
}
