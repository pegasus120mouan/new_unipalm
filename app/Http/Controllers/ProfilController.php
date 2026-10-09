<?php

namespace App\Http\Controllers;

use App\Models\Utilisateur;
use App\Services\MinioStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ProfilController extends Controller
{
    public function __construct(
        private readonly MinioStorageService $minio,
    ) {}

    public function show(Request $request): View
    {
        return view('profil.show', [
            'utilisateur' => $request->user(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4', 'confirmed', 'different:old_password'],
        ], [
            'new_password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'new_password.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
            'new_password.min' => 'Le nouveau mot de passe doit contenir au moins 4 caractères.',
        ]);

        if (! $utilisateur->checkPassword($validated['old_password'])) {
            return back()
                ->withErrors(['old_password' => 'L\'ancien mot de passe est incorrect.'])
                ->with('active_tab', 'password');
        }

        $utilisateur->setPasswordFromPlain($validated['new_password']);

        return redirect()
            ->route('profil.show')
            ->with('success', 'Votre mot de passe a été modifié avec succès.')
            ->with('active_tab', 'password');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        try {
            $objectKey = $this->minio->uploadUserPhoto($request->file('photo'), (int) $utilisateur->id);
            $utilisateur->update(['avatar' => $objectKey]);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['photo' => $exception->getMessage()]);
        }

        return redirect()
            ->route('profil.show')
            ->with('success', 'Votre photo a été mise à jour.');
    }

    public function avatar(Request $request): Response
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();
        $objectKey = $utilisateur->resolveMinioObjectKey();

        if ($objectKey === null || ! $this->minio->isConfigured()) {
            abort(404);
        }

        $content = $this->minio->getObjectContents($objectKey);

        if ($content === null) {
            abort(404);
        }

        $mime = match (strtolower(pathinfo($objectKey, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'ETag' => '"'.md5($objectKey).'"',
        ]);
    }
}
