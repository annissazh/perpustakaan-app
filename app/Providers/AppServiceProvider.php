<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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
        // Frontend memakai Bootstrap 5, jadi pagination memakai markup Bootstrap.
        Paginator::useBootstrapFive();

        // Policy untuk hierarki role pengguna.
        Gate::policy(User::class, UserPolicy::class);
    }
}
