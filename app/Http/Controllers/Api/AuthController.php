<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $login = trim($validated['login']);

        $utilisateur = Utilisateur::query()
            ->where('login', $login)
            ->first();

        if ($utilisateur === null || ! $utilisateur->checkPassword($validated['password'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Identifiant ou mot de passe incorrect.',
            ], 401);
        }

        if (! $utilisateur->isActive()) {
            return response()->json([
                'ok' => false,
                'message' => 'Ce compte est désactivé. Contactez un administrateur.',
            ], 403);
        }

        $deviceName = trim((string) ($validated['device_name'] ?? ''));
        if ($deviceName === '') {
            $deviceName = 'mobile-app';
        }

        $token = $utilisateur->createToken($deviceName)->plainTextToken;

        return response()->json([
            'ok' => true,
            'message' => 'Connexion réussie.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($utilisateur),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Utilisateur $utilisateur */
        $utilisateur = $request->user();

        return response()->json([
            'ok' => true,
            'user' => $this->formatUser($utilisateur),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Déconnexion réussie.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Toutes les sessions mobiles ont été fermées.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(Utilisateur $utilisateur): array
    {
        return [
            'id' => $utilisateur->id,
            'login' => $utilisateur->login,
            'nom' => $utilisateur->formatted_nom,
            'prenoms' => $utilisateur->formatted_prenoms,
            'full_name' => $utilisateur->full_name,
            'contact' => $utilisateur->contact,
            'role' => $utilisateur->role,
            'role_label' => $utilisateur->role_label,
            'avatar_url' => $utilisateur->avatar_url,
            'modules' => app(RolePermissionService::class)->modulesForRole((string) $utilisateur->role),
        ];
    }
}
