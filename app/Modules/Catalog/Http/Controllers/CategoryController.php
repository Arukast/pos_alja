<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Modules\Catalog\Http\Resources\CategoryResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class CategoryController extends Controller
{
    public function index(CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewCategories', ProductCategory::class);

        return CategoryResource::collection($queries->categories());
    }
}
