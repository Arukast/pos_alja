<?php

namespace App\Providers;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\WastedMaterial;
use App\Policies\CatalogPolicy;
use App\Policies\MachinePolicy;
use App\Policies\MaterialPolicy;
use App\Policies\WastePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider; // Explicitly imported here

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
        Gate::policy(ProductCategory::class, CatalogPolicy::class);
        Gate::policy(AttributeType::class, CatalogPolicy::class);
        Gate::policy(Attribute::class, CatalogPolicy::class);
        Gate::policy(Product::class, CatalogPolicy::class);
        Gate::policy(Pricing::class, CatalogPolicy::class);
        Gate::policy(StockLedger::class, CatalogPolicy::class);
        Gate::policy(Material::class, MaterialPolicy::class);
        Gate::policy(Machine::class, MachinePolicy::class);
        Gate::policy(WastedMaterial::class, WastePolicy::class);

        // Force HTTPS if the request comes from your Tailscale Funnel domain,
        // or if this deployment's APP_URL is https (reverse-proxied, e.g. VPS).
        if (str_contains(request()->getHost(), '.ts.net')
            || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
