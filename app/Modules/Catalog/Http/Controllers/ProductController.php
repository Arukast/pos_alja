<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Modules\Catalog\Http\Resources\ProductResource;
use App\Modules\Catalog\Queries\CatalogQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ProductController extends Controller
{
    public function index(Request $request, CatalogQueries $queries): AnonymousResourceCollection
    {
        Gate::authorize('viewProducts', Product::class);

        $search = trim($request->string('search')->toString());
        $categoryId = $request->query('category_id');

        return ProductResource::collection($queries->products([
            'search' => $search === '' ? null : $search,
            'category_id' => is_string($categoryId) ? $categoryId : null,
        ]));
    }
}
