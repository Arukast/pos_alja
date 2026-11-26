<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\Sales\Actions\CreateInvoice;
use App\Modules\Sales\Http\Requests\StoreInvoiceRequest;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Modules\Sales\Http\Resources\ProductionInvoiceResource;
use App\Modules\Sales\Queries\InvoiceList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Manajemen pemesanan dan pembuatan invoice pelanggan (PB-02 / SCRUM-21).
 */
final class InvoiceController extends Controller
{
    /**
     * Menampilkan daftar invoice terpaginasi dengan filter cabang, status pembayaran, pickup, dan pencarian.
     */
    public function index(Request $request, InvoiceList $invoices): AnonymousResourceCollection
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('viewAny', Invoice::class);

        $branchId = $request->query('branch_id');

        $validated = $request->validate([
            'branch_id' => $actor->role === 'owner' && $branchId !== 'all'
                ? ['nullable', 'uuid']
                : ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1,2'],
            'pickup_status' => ['nullable', 'integer', 'in:0,1'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));

        $paginator = $invoices->paginate($actor, [
            'branch_id' => is_string($branchId) ? $branchId : null,
            'status' => isset($validated['status']) ? (int) $validated['status'] : null,
            'pickup_status' => isset($validated['pickup_status'])
                ? (int) $validated['pickup_status']
                : null,
            'search' => $search === '' ? null : $search,
        ]);

        if ($actor->role === 'production') {
            return ProductionInvoiceResource::collection($paginator);
        }

        return InvoiceResource::collection($paginator);
    }

    /**
     * Menyimpan invoice baru beserta item pesanan untuk cabang desainer yang sedang masuk.
     */
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

    /**
     * Menampilkan detail invoice sesuai cakupan cabang dan visibilitas nilai uang per peran.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('view', $invoice);

        $relations = $actor->role === 'production'
            ? ['customer', 'designer', 'branch', 'orders']
            : ['customer', 'designer', 'branch', 'orders', 'payments'];

        $invoice->load($relations);

        $resource = $actor->role === 'production'
            ? new ProductionInvoiceResource($invoice)
            : new InvoiceResource($invoice);

        return response()->json(['invoice' => $resource]);
    }

    /**
     * Menampilkan tiket kerja produksi bebas informasi finansial untuk invoice terkait.
     */
    public function ticket(Request $request, Invoice $invoice): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('ticket', $invoice);

        $invoice->load(['customer', 'designer', 'branch', 'orders']);

        return response()->json([
            'invoice' => new ProductionInvoiceResource($invoice),
        ]);
    }

    /**
     * Menampilkan data nota pembayaran invoice untuk peran yang berwenang (non-produksi).
     */
    public function receipt(Request $request, Invoice $invoice): JsonResponse
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        Gate::authorize('receipt', $invoice);

        $invoice->load(['customer', 'designer', 'branch', 'orders', 'payments']);

        return response()->json([
            'invoice' => new InvoiceResource($invoice),
        ]);
    }
}
