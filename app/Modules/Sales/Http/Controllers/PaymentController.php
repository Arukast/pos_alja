<?php

declare(strict_types=1);

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\Sales\Actions\MarkInvoicePickedUp;
use App\Modules\Sales\Actions\RegisterDownPayment;
use App\Modules\Sales\Actions\SettleInvoice;
use App\Modules\Sales\Http\Requests\SettleInvoiceRequest;
use App\Modules\Sales\Http\Requests\StoreDownPaymentRequest;
use App\Modules\Sales\Http\Resources\InvoiceResource;
use App\Modules\Sales\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PaymentController extends Controller
{
    public function downPayment(
        StoreDownPaymentRequest $request,
        Invoice $invoice,
        RegisterDownPayment $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $payment = $action->execute($invoice, $request->validated(), $actor);

        $invoice->refresh()->load(['customer', 'designer', 'branch', 'orders', 'payments']);

        return response()->json([
            'payment' => new PaymentResource($payment),
            'invoice' => new InvoiceResource($invoice),
        ], 201);
    }

    public function settle(
        SettleInvoiceRequest $request,
        Invoice $invoice,
        SettleInvoice $action
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $payment = $action->execute($invoice, $request->validated(), $actor);

        $invoice->refresh()->load(['customer', 'designer', 'branch', 'orders', 'payments']);

        return response()->json([
            'payment' => $payment ? new PaymentResource($payment) : null,
            'invoice' => new InvoiceResource($invoice),
        ], 201);
    }

    public function pickup(
        Request $request,
        Invoice $invoice,
        MarkInvoicePickedUp $action
    ): JsonResponse {
        Gate::authorize('pickup', $invoice);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        return response()->json([
            'invoice' => new InvoiceResource($action->execute($invoice, $actor)),
        ]);
    }
}
