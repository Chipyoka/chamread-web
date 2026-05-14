<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

use App\Models\SystemNotification;

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
        View::composer('layouts.navigation', function ($view) {

        $hasUnreadNotifications = SystemNotification::where('is_read', false)->exists();

        $view->with('hasUnreadNotifications', $hasUnreadNotifications);
    });
    }
}
