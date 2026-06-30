<?php

namespace Database\Seeders;

use App\Models\Groupe;
use App\Models\Utilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChefEquipeCredentialsSeeder extends Seeder
{
    /**
     * Logins explicites par id_chef (prioritaires).
     *
     * @var array<int, array{login: string, password?: string}>
     */
    private array $overrides = [
        12 => ['login' => 'pgf'],
        5 => ['login' => 'koanda'],
        2 => ['login' => 'zalle'],
        4 => ['login' => 'sangare'],
    ];

    public function run(): void
    {
        $defaultPassword = Utilisateur::DEFAULT_PASSWORD;

        $chefs = Groupe::query()->orderBy('id_chef')->get();

        if ($chefs->isEmpty()) {
            $this->command?->warn('Aucun chef d\'équipe dans chef_equipe.');

            return;
        }

        $usedLogins = Groupe::query()
            ->whereNotNull('login')
            ->where('login', '!=', '')
            ->pluck('login')
            ->map(fn ($login) => Str::lower(trim((string) $login)))
            ->all();

        foreach ($chefs as $chef) {
            if ($chef->hasCredentials()) {
                $this->command?->line("Ignoré (déjà configuré) : {$chef->full_name} [{$chef->login}]");

                continue;
            }

            $override = $this->overrides[(int) $chef->id_chef] ?? null;
            $login = Str::lower(trim((string) ($override['login'] ?? $this->suggestLogin($chef))));
            $password = (string) ($override['password'] ?? $defaultPassword);

            $login = $this->ensureUniqueLogin($login, (int) $chef->id_chef, $usedLogins);

            $chef->login = $login;
            $chef->setPasswordFromPlain($password);
            $chef->save();

            $usedLogins[] = Str::lower($login);

            $this->command?->info("{$chef->full_name} → login: {$login}");
        }

        $this->command?->newLine();
        $this->command?->info("Mot de passe par défaut : {$defaultPassword}");
    }

    private function suggestLogin(Groupe $chef): string
    {
        $fromNom = Str::slug(Str::lower(trim((string) $chef->nom)), '');
        if ($fromNom !== '') {
            return Str::limit($fromNom, 50, '');
        }

        return 'chef' . $chef->id_chef;
    }

    /**
     * @param  list<string>  $usedLogins
     */
    private function ensureUniqueLogin(string $login, int $idChef, array $usedLogins): string
    {
        $base = Str::limit($login, 90, '');
        $candidate = $base;
        $suffix = 1;

        while (
            in_array(Str::lower($candidate), $usedLogins, true)
            || Groupe::query()
                ->whereRaw('LOWER(login) = ?', [Str::lower($candidate)])
                ->where('id_chef', '!=', $idChef)
                ->exists()
        ) {
            $candidate = $base . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
