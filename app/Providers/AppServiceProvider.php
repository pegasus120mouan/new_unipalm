<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\TicketService;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('eloquent', function ($app, array $config) {
            return (new EloquentUserProvider($app['hash'], $config['model']))
                ->withQuery(function ($query): void {
                    $query->withoutGlobalScope('visible');
                });
        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Paginator::useBootstrapFive();

        View::composer('*', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $user = auth()->user();

            $view->with('canModule', fn (string $module): bool => $user->canAccessModule($module));
            $view->with('canModuleAny', fn (array $modules): bool => $user->canAccessAnyModule($modules));
        });

        View::composer('layout.main', function ($view): void {
            if (! auth()->check()) {
                return;
            }

            $demandesFinancementEnAttenteCount = 0;
            $financementsEnAttenteValidationCount = 0;
            try {
                $demandesFinancementEnAttenteCount = (int) \App\Models\DemandeAvanceGestCamions::query()
                    ->where('statut', 'en_attente')
                    ->count();
            } catch (\Throwable $e) {
                report($e);
                $demandesFinancementEnAttenteCount = 0;
            }

            try {
                $financementsEnAttenteValidationCount = app(\App\Services\FinancementService::class)
                    ->countEnAttenteValidation();
            } catch (\Throwable $e) {
                report($e);
                $financementsEnAttenteValidationCount = 0;
            }

            $view->with([
                'ticketStats' => app(TicketService::class)->getDashboardStats(),
                'demandesFinancementEnAttenteCount' => $demandesFinancementEnAttenteCount,
                'financementsEnAttenteValidationCount' => $financementsEnAttenteValidationCount,
            ]);
        });
    }
}
