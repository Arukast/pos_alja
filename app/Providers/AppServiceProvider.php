<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\StockLedger;
use App\Models\WastedMaterial;
use App\Policies\AuditPolicy;
use App\Policies\BackupPolicy;
use App\Policies\BackupSettingPolicy;
use App\Policies\CatalogPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\ReportPolicy;
use App\Policies\WastePolicy;
use App\Services\Backup\BackupDispatcher;
use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\PgDumpDatabaseDumper;
use App\Services\Backup\QueueBackupDispatcher;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider; // Explicitly imported here

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DatabaseDumper::class, PgDumpDatabaseDumper::class);
        $this->app->bind(BackupDispatcher::class, QueueBackupDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip())
        ));

        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(5)->by(
            (string) ($request->user()?->getAuthIdentifier() ?? $request->ip())
        ));

        Gate::policy(ProductCategory::class, CatalogPolicy::class);
        Gate::policy(Attribute::class, CatalogPolicy::class);
        Gate::policy(Product::class, CatalogPolicy::class);
        Gate::policy(Pricing::class, CatalogPolicy::class);
        Gate::policy(StockLedger::class, CatalogPolicy::class);
        Gate::policy(Material::class, MaterialPolicy::class);
        Gate::policy(WastedMaterial::class, WastePolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(ActivityLog::class, AuditPolicy::class);
        Gate::policy(Backup::class, BackupPolicy::class);
        Gate::policy(BackupSetting::class, BackupSettingPolicy::class);

        Gate::define('viewDashboard', [ReportPolicy::class, 'viewDashboard']);
        Gate::define('viewOwnerReports', [ReportPolicy::class, 'viewOwnerReports']);
        Gate::define('viewLeaderboard', [ReportPolicy::class, 'viewLeaderboard']);

        // Force HTTPS if the request comes from your Tailscale Funnel domain,
        // or if this deployment's APP_URL is https (reverse-proxied, e.g. VPS).
        if (str_contains(request()->getHost(), '.ts.net')
            || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
