@if ($selectedInvoiceId) @php $selInvoice =
\App\Models\Invoice::with(['customer', 'orders'])->find($selectedInvoiceId);
@endphp @if ($selInvoice)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 text-xs shadow-2xl dark:text-gray-200"
    >
        <!-- Brand Stripe Accent -->
        <div
            class="from-brand-primary via-brand-secondary to-brand-accent absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r"
        ></div>

        <!-- Header -->
        <div
            class="border-light-border dark:border-surface-300/30 mb-4 flex items-center justify-between border-b pt-2 pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    {{ $paymentType === 'dp' ? 'Register Down Payment' :
                    'Invoice Payment Checkout' }}
                </h3>
                <p
                    class="text-light-muted mt-0.5 text-xs font-semibold dark:text-gray-400"
                >
                    Invoice {{ $selInvoice->invoice_number }} &bull; Customer:
                    {{ $selInvoice->customer->name }}
                </p>
            </div>
            <button
                id="btn-close-checkout"
                wire:click="closeCheckout"
                class="text-light-muted hover:text-light-text cursor-pointer dark:text-gray-400 dark:hover:text-white"
            >
                <svg
                    class="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
            </button>
        </div>

        <!-- Items Summary -->
        <div class="mb-4 max-h-32 space-y-1.5 overflow-y-auto pr-1">
            <span
                class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Order Items</span
            >
            @foreach ($selInvoice->orders as $o)
            <div
                class="text-light-text flex justify-between text-[11px] font-medium dark:text-gray-300"
            >
                <span>{{ $o->quantity }}x {{ $o->file_name }}</span>
                <span class="text-light-text font-bold dark:text-white"
                    >Rp. {{ number_format($o->subtotal) }}</span
                >
            </div>
            @endforeach
        </div>

        <!-- Payment Details Form -->
        <div class="space-y-4">
            @if ($selInvoice->status == 0)
            <div>
                <label
                    class="text-light-muted mb-2 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Payment Type</label
                >
                <div class="flex gap-4">
                    <label
                        class="text-light-text flex cursor-pointer items-center gap-2 font-bold dark:text-gray-300"
                    >
                        <input
                            type="radio"
                            wire:model.live="paymentType"
                            value="dp"
                            class="dark:bg-surface-500 border-light-border dark:border-surface-300/50 text-brand-primary focus:ring-brand-primary/30 h-4 w-4"
                        />
                        <span>Down Payment (DP)</span>
                    </label>
                    <label
                        class="text-light-text flex cursor-pointer items-center gap-2 font-bold dark:text-gray-300"
                    >
                        <input
                            type="radio"
                            wire:model.live="paymentType"
                            value="settle"
                            class="dark:bg-surface-500 border-light-border dark:border-surface-300/50 text-brand-primary focus:ring-brand-primary/30 h-4 w-4"
                        />
                        <span>Full Settlement</span>
                    </label>
                </div>
            </div>
            @else
            <div
                class="dark:bg-amber-955/20 rounded-xl border border-amber-500/20 bg-amber-50 p-3 text-xs font-semibold text-amber-700 dark:text-amber-400"
            >
                Sisa Piutang Settlement (Current DP Paid: Rp. {{
                number_format($selInvoice->dp_paid) }})
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Payment Method</label
                    >
                    <select
                        id="checkout-method"
                        wire:model="inputMethod"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-semibold transition duration-200 focus:ring-2 focus:outline-none"
                    >
                        <option value="Cash" class="bg-white text-gray-900">
                            Cash
                        </option>
                        <option value="Transfer" class="bg-white text-gray-900">
                            Bank Transfer
                        </option>
                        <option value="QRIS" class="bg-white text-gray-900">
                            QRIS
                        </option>
                    </select>
                </div>

                @if ($paymentType === 'settle')
                <div>
                    <label
                        class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Additional Discount (Rp.)</label
                    >
                    <input
                        id="checkout-discount"
                        type="number"
                        wire:model.live.debounce.300ms="inputDiscount"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-semibold transition duration-200 focus:ring-2 focus:outline-none"
                    />
                    @error('inputDiscount')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>
                @endif
            </div>

            <div>
                <label
                    class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >
                    {{ $paymentType === 'dp' ? 'DP Amount Received (Rp.)' :
                    'Total Cash Paid by Customer (Rp.)' }}
                </label>
                <input
                    id="checkout-amount"
                    type="number"
                    wire:model.live.debounce.300ms="inputAmount"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm font-extrabold transition duration-200 focus:ring-2 focus:outline-none"
                />
                @error('inputAmount')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                    >{{ $message }}</span
                >
                @enderror
            </div>

            <!-- Calculations Preview -->
            @php $discount = (int) $inputDiscount; $receivable =
            $selInvoice->accounts_receivable; $netToPay = $paymentType === 'dp'
            ? (int)$inputAmount : ($receivable - $discount); $paid = (int)
            $inputAmount; $change = $paid - $netToPay; @endphp
            <div
                class="bg-light-surface-2 dark:bg-surface-700/30 border-light-border dark:border-surface-700/50 space-y-2 rounded-xl border p-3.5"
            >
                @if ($paymentType === 'dp')
                <div class="flex justify-between font-semibold">
                    <span class="text-light-muted dark:text-gray-400"
                        >Total Invoice:</span
                    >
                    <span class="text-light-text font-bold dark:text-white"
                        >Rp. {{ number_format($selInvoice->grand_total) }}</span
                    >
                </div>
                <div
                    class="text-brand-secondary flex justify-between font-bold"
                >
                    <span>Down Payment Paid:</span>
                    <span>Rp. {{ number_format($paid) }}</span>
                </div>
                <div
                    class="border-light-border dark:border-surface-300/30 text-red-550 dark:text-red-450 flex justify-between border-t pt-2 font-extrabold"
                >
                    <span>Remaining Balance (AR):</span>
                    <span
                        >Rp. {{ number_format(max(0, $selInvoice->grand_total -
                        $paid)) }}</span
                    >
                </div>
                @else
                <div class="flex justify-between font-semibold">
                    <span class="text-light-muted dark:text-gray-400"
                        >Remaining Balance:</span
                    >
                    <span class="text-light-text font-bold dark:text-white"
                        >Rp. {{ number_format($receivable) }}</span
                    >
                </div>
                @if ($discount > 0)
                <div
                    class="flex justify-between font-bold text-purple-600 dark:text-purple-400"
                >
                    <span>Discount Given:</span>
                    <span>-Rp. {{ number_format($discount) }}</span>
                </div>
                @endif
                <div class="flex justify-between font-bold">
                    <span class="text-light-muted dark:text-gray-400"
                        >Net Settle Amount:</span
                    >
                    <span class="text-light-text font-extrabold dark:text-white"
                        >Rp. {{ number_format(max(0, $receivable - $discount))
                        }}</span
                    >
                </div>
                <div
                    class="border-light-border dark:border-surface-300/30 dark:text-emerald-450 flex justify-between border-t pt-2 text-sm font-extrabold text-emerald-600"
                >
                    <span>Change (Kembalian):</span>
                    <span>Rp. {{ number_format(max(0, $change)) }}</span>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-2">
                <button
                    id="btn-checkout-cancel"
                    type="button"
                    wire:click="closeCheckout"
                    class="border-light-border dark:border-surface-300/50 text-light-muted hover:text-light-text hover:bg-light-surface-2 dark:hover:bg-surface-500 flex-1 cursor-pointer rounded-xl border py-2.5 font-bold transition duration-150 dark:text-gray-300 dark:hover:text-white"
                >
                    Cancel
                </button>
                <button
                    id="btn-checkout-confirm"
                    type="button"
                    wire:click="processCheckout"
                    class="bg-brand-primary hover:bg-brand-primary/95 flex-1 cursor-pointer rounded-xl py-2.5 font-bold text-white shadow-sm transition data-loading:cursor-not-allowed data-loading:opacity-50"
                    data-loading-disable
                >
                    Process Payment
                </button>
            </div>
        </div>
    </div>
</div>
@endif @endif
