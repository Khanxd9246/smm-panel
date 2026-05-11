<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Existing: disable Cashier auto-migrations
        Cashier::$runsMigrations = false;

        // Existing: ProviderSyncService singleton
        $this->app->singleton(\App\Services\ProviderSyncService::class);

        // NEW: AI services as singletons (one instance per request)
        $this->app->singleton(\App\Services\AIService::class);
        $this->app->singleton(\App\Services\PricingService::class);
        $this->app->singleton(\App\Services\WalletService::class);
        $this->app->singleton(\App\Services\SupplierHealthService::class);

        $this->app->singleton(\App\Services\ServiceQualityService::class, function ($app) {
            return new \App\Services\ServiceQualityService($app->make(\App\Services\AIService::class));
        });
    }

    public function boot(): void
    {
        // Existing: force HTTPS on Railway (SSL terminates at proxy)
        if ($this->app->environment('production', 'staging')) {
            URL::forceScheme('https');
        }

        // Existing: catch N+1 bugs in development
        if (! $this->app->environment('production')) {
            Model::preventLazyLoading();
            Model::preventSilentlyDiscardingAttributes();
        }

        // Existing: Tailwind pagination
        Paginator::useTailwind();

        // Existing: MySQL utf8mb4 string length
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\Schema::defaultStringLength(191);
        }

        // NEW: Blade helpers
        Blade::directive('money', function ($expression) {
            return "<?php echo '$' . number_format({$expression}, 2); ?>";
        });
    }
}
