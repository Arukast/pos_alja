<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Exceptions\PricingNotFoundException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\User;
use App\Modules\Sales\Actions\CalculateQuote;
use App\Modules\Sales\Http\Requests\QuoteRequest;
use App\Services\MaterialAvailability;
use App\Support\Decimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class QuoteController extends Controller
{
    public function store(
        QuoteRequest $request,
        CalculateQuote $quote,
        MaterialAvailability $availability
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('createQuote', Invoice::class);

        if ($actor->branch_id === null) {
            abort(403, 'Desainer harus ditugaskan ke cabang.');
        }

        $data = $request->validated();
        $material = Material::query()->find($data['material_id']);

        if (! $availability->isAvailable($material, $actor->branch_id)) {
            throw ValidationException::withMessages([
                'material_id' => 'Bahan tidak aktif atau tidak tersedia di cabang ini.',
            ]);
        }

        try {
            $result = $quote->execute([
                'product_id' => $data['product_id'],
                'material_id' => $data['material_id'],
                'selected_attributes' => $data['selected_attributes'] ?? [],
                'quantity' => $data['quantity'],
                'width' => $data['width'] ?? 0,
                'length' => $data['length'] ?? 0,
                'customer_type' => $data['customer_type'] ?? 'Reguler',
                'branch_id' => $actor->branch_id,
            ]);
        } catch (PricingNotFoundException|InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'quote' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'quote' => [
                'pricing_id' => $result['pricing_id'],
                'unit_price' => Decimal::money($result['unit_price']),
                'area' => $result['area'],
                'quantity' => $result['quantity'],
                'subtotal' => Decimal::money($result['subtotal']),
            ],
        ]);
    }
}
