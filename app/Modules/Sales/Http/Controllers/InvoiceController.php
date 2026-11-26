<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Actions\UpdateInvoiceItems;
use App\Modules\Sales\Http\Requests\StoreInvoiceRequest;
use App\Modules\Sales\Http\Requests\UpdateInvoiceItemsRequest;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Modules\Sales\Http\Resources\ProductionInvoiceResource;
use App\Modules\Sales\Queries\InvoiceList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class InvoiceController extends Controller
{
    public function index(Request $request, InvoiceList $invoices): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Invoice::class);

        $branchId = $request->query('branch_id');
        $status = $request->query('status');
        $pickupStatus = $request->query('pickup_status');
        $search = trim($request->string('search')->toString());

        $paginator = $invoices->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'status' => is_string($status) && $status !== '' ? (int) $status : null,
            'pickup_status' => is_string($pickupStatus) && $pickupStatus !== ''
                ? (int) $pickupStatus
                : null,
            'search' => $search === '' ? null : $search,
        ]);

        if ($actor->role === 'production') {
            return ProductionInvoiceResource::collection($paginator);
        }

        return InvoiceResource::collection($paginator);
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('create', Invoice::class);

        $invoice = $action->execute($request->validated(), $actor);

        return response()->json([
            'invoice' => new InvoiceResource($invoice),
        ], 201);
    }

    /** Replaces and recalculates invoice items while the invoice is editable. */
    public function updateItems(
        UpdateInvoiceItemsRequest $request,
        Invoice $invoice,
        UpdateInvoiceItems $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('update', $invoice);
        $updatedInvoice = $action->execute($invoice, $request->validated(), $actor);

        return response()->json([
            'invoice' => new InvoiceResource($updatedInvoice),
        ]);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        Gate::authorize('view', $invoice);

        $invoice->load([
            'customer',
            'designer',
            'branch',
            'orders.pricing.product',
            'orders.pricing.material',
            'payments',
        ]);

        $resource = $request->user()?->role === 'production'
            ? new ProductionInvoiceResource($invoice)
            : new InvoiceResource($invoice);

        return response()->json(['invoice' => $resource]);
    }

    public function ticket(Invoice $invoice): JsonResponse
    {
        Gate::authorize('ticket', $invoice);

        $invoice->load(['customer', 'designer', 'branch', 'orders']);

        return response()->json([
            'invoice' => new ProductionInvoiceResource($invoice),
        ]);
    }

    public function receipt(Invoice $invoice): JsonResponse
    {
        Gate::authorize('receipt', $invoice);

        $invoice->load([
            'customer',
            'designer',
            'branch',
            'orders.pricing.product',
            'orders.pricing.material',
            'payments',
        ]);

        return response()->json([
            'invoice' => new InvoiceResource($invoice),
        ]);
    }
}
