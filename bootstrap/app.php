<?php

// Apache réutilise parfois DB_DATABASE d'un autre vhost (ex. verif-ticket).
// Le fichier .env de cette application doit toujours gagner.
$databaseEnvFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env';
if (is_file($databaseEnvFile)) {
    $databaseEnvKeys = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

    foreach (file($databaseEnvFile, FILE_IGNORE_NEW_LINES) ?: [] as $databaseEnvLine) {
        $databaseEnvLine = trim($databaseEnvLine);

        if ($databaseEnvLine === '' || str_starts_with($databaseEnvLine, '#') || ! str_contains($databaseEnvLine, '=')) {
            continue;
        }

        [$databaseEnvKey, $databaseEnvValue] = explode('=', $databaseEnvLine, 2);
        $databaseEnvKey = trim($databaseEnvKey);

        if (! in_array($databaseEnvKey, $databaseEnvKeys, true)) {
            continue;
        }

        $databaseEnvValue = trim($databaseEnvValue, " \t\"'");
        putenv($databaseEnvKey.'='.$databaseEnvValue);
        $_ENV[$databaseEnvKey] = $databaseEnvValue;
        $_SERVER[$databaseEnvKey] = $databaseEnvValue;
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');

        $middleware->alias([
            'module' => \App\Http\Middleware\EnsureModuleAccess::class,
            'verif.api' => \App\Http\Middleware\VerifyVerifApiKey::class,
            'verif.env' => \App\Http\Middleware\EnsureVerifApiEnvironment::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Fichier trop volumineux pour le serveur (limite post_max_size PHP).',
                ], 413);
            }

            $message = 'Le fichier GeoJSON est trop volumineux pour le serveur web (limite PHP post_max_size / nginx client_max_body_size). '
                .'Augmentez ces limites à 128M sur le serveur, ou importez via SSH : '
                .'php artisan geojson:import sous-prefectures /chemin/vers/fichier.geojson';

            $redirectRoute = match (true) {
                $request->is('ponts/sous-prefectures/import') => 'ponts.sous-prefectures.index',
                $request->is('ponts/departements/import') => 'ponts.departements.index',
                $request->is('ponts/regions/import') => 'ponts.regions.index',
                default => null,
            };

            if ($redirectRoute !== null) {
                return redirect()->route($redirectRoute)->withErrors([
                    'geojson_file' => $message,
                ]);
            }

            return redirect()->back()->withErrors([
                'geojson_file' => $message,
            ]);
        });
    })->create();
