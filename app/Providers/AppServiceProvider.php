<?php

namespace App\Providers;

use App\Models\CustomerAccount;
use App\Models\Reading;
use App\Models\User;
use App\Observers\CustomerAccountObserver;
use App\Observers\ReadingObserver;
use App\Observers\UserObserver;
use App\Services\FlagService;
use App\Models\Flag;
use App\Models\FlagRule;
use App\Observers\FlagObserver;
use App\Services\RuleEvaluator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\SystemNotification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
          $this->app->singleton(FlagService::class, function ($app) {
            return new FlagService(
                $app->make(RuleEvaluator::class)
            );
        });
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

        // Register flag observers — must run on every request, including API-only ones
        Reading::observe(ReadingObserver::class);
        CustomerAccount::observe(CustomerAccountObserver::class);
        User::observe(UserObserver::class);
        Flag::observe(FlagObserver::class);
        FlagRule::observe(FlagObserver::class);

        RateLimiter::for('balance-api', function () {
            return Limit::perMinute(50);
        });
    }
}
