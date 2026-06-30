<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use App\Services\TicketService;
use Illuminate\Support\ServiceProvider;

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

            $view->with('ticketStats', app(TicketService::class)->getDashboardStats());
        });
    }
}
