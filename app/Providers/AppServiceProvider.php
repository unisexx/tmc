<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        //
        Schema::defaultStringLength(191);

        // Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.lightable');

        View::composer('*', function ($view) {
            $pendingCount = User::where('reg_status', '!=', 'อนุมัติ')->count();
            $view->with('pendingApplicationCount', $pendingCount);
        });
    }
}
