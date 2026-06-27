<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifApiEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->applyDatabaseConfigFromEnvFile();

        return $next($request);
    }

    private function applyDatabaseConfigFromEnvFile(): void
    {
        $envFile = base_path('.env');

        if (! is_file($envFile)) {
            return;
        }

        $vars = [];

        foreach (file($envFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $vars[trim($key)] = trim($value, " \t\"'");
        }

        $connection = (string) config('database.default', 'mysql');
        $map = [
            'DB_HOST' => 'host',
            'DB_PORT' => 'port',
            'DB_DATABASE' => 'database',
            'DB_USERNAME' => 'username',
            'DB_PASSWORD' => 'password',
        ];

        foreach ($map as $envKey => $configKey) {
            if (array_key_exists($envKey, $vars)) {
                config(["database.connections.{$connection}.{$configKey}" => $vars[$envKey]]);
            }
        }

        DB::purge($connection);
    }
}
