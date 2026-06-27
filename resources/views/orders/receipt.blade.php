<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Receipt {{ $invoice->invoice_number }}</title>
        @vite(['resources/css/app.css'])
        <style>
            body {
                font-family: 'Courier New', Courier, monospace;
                background-color: #f3f4f6;
                padding: 20px;
            }
            .receipt-container {
                width: 80mm;
                margin: 0 auto;
                background: white;
                padding: 15px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                border: 1px dashed #d1d5db;
            }
            .divider {
                border-top: 1px dashed black;
                margin: 8px 0;
            }
            @media print {
                body {
                    background-color: white;
                    padding: 0;
                    margin: 0;
                }
                .receipt-container {
                    width: 80mm;
                    box-shadow: none;
                    border: none;
                    padding: 0;
                    margin: 0;
                }
                .no-print {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body class="text-xs leading-tight text-gray-900">
        <!-- Action buttons (Hidden when printing) -->
        <div
            class="no-print mx-auto mb-4 flex max-w-[80mm] items-center justify-between font-sans text-[11px]"
        >
            <a
                href="/dashboard"
                class="flex cursor-pointer items-center text-xs font-bold text-gray-500 hover:text-gray-700"
            >
                &larr; Back
            </a>
            <button
                onclick="window.print()"
                class="cursor-pointer rounded bg-emerald-500 px-3 py-1.5 font-bold text-white shadow-sm transition hover:bg-emerald-600"
            >
                Print (Ctrl + P)
            </button>
        </div>

        <!-- Receipt paper -->
        <div class="receipt-container text-[11px]">
            <!-- Store Header -->
            <div class="space-y-0.5 text-center">
                <h1 class="text-base font-black tracking-tight uppercase">
                    {{ config('brand.fullname', 'ALJA DIGIPRINT') }}
                </h1>
                @if ($invoice->branch)
                <p class="text-[10px]">{{ $invoice->branch->name }}</p>
                @if ($invoice->branch->address)
                <p class="text-[9px] text-gray-500">
                    {{ $invoice->branch->address }}
                </p>
                @endif @if ($invoice->branch->phone)
                <p class="text-[9px] text-gray-500">
                    Telp: {{ $invoice->branch->phone }}
                </p>
                @endif @else
                <p class="text-[10px]">
                    {{ config('brand.slogan', 'High-Quality Print & Production
                    Shop') }}
                </p>
                @endif
            </div>

            <div class="divider"></div>

            <!-- Meta Info -->
            <div class="space-y-1">
                <div class="flex justify-between">
                    <span>Inv: {{ $invoice->invoice_number }}</span>
                    <span>Date: {{ $invoice->created_date }}</span>
                </div>
                @php $cashierName =
                $invoice->payments->sortByDesc('created_at')->first()?->cashier_name
                ?: '-'; @endphp
                <div class="flex justify-between">
                    <span>CS: {{ $invoice->designer?->name ?: '-' }}</span>
                    <span>Cashier: {{ $cashierName }}</span>
                </div>
                <div>
                    <span
                        >Cust: {{ $invoice->customer->name }} ({{
                        $invoice->customer->phone ?: '-' }})</span
                    >
                </div>
            </div>

            <div class="divider"></div>

            <!-- Column Headers -->
            <div class="mb-1 grid grid-cols-12 font-bold">
                <span class="col-span-8">Description</span>
                <span class="col-span-4 text-right">Subtotal</span>
            </div>

            <!-- Order Items list -->
            <div class="space-y-2">
                @foreach ($invoice->orders as $oItem)
                <div class="grid grid-cols-12 items-start">
                    <div class="col-span-8 flex flex-col">
                        <span class="font-bold">{{ $oItem->file_name }}</span>
                        <span class="text-[10px] text-gray-500"
                            >{{ (float)$oItem->quantity }}x @ Rp. {{
                            number_format($oItem->unit_price) }}</span
                        >
                        @if ($oItem->notes)
                        <span class="font-sans text-[9px] text-gray-400 italic"
                            >Notes: {{ $oItem->notes }}</span
                        >
                        @endif
                    </div>
                    <span class="col-span-4 self-end text-right font-bold"
                        >Rp. {{ number_format($oItem->subtotal) }}</span
                    >
                </div>
                @endforeach
            </div>

            <div class="divider"></div>

            <!-- Summary list -->
            <div class="space-y-1.5 font-sans text-[10px] font-medium">
                @php $preDiscountTotal = $invoice->orders->sum('subtotal');
                $dpPaid = (int) $invoice->payments->where('type',
                'down_payment')->sum('amount'); $paymentDpMethod =
                $invoice->payments->where('type',
                'down_payment')->first()?->payment_method; $settlementAmount =
                (int) $invoice->payments->where('type',
                'settlement')->sum('amount'); $paymentMethod =
                $invoice->payments->where('type',
                'settlement')->first()?->payment_method; $totalPaid = (int)
                $invoice->payments->sum('amount'); @endphp
                <div
                    class="flex justify-between font-mono text-[11px] font-bold"
                >
                    <span>Grand Total:</span>
                    <span>Rp. {{ number_format($preDiscountTotal) }}</span>
                </div>

                @if ($invoice->discount > 0)
                <div class="flex justify-between text-purple-600">
                    <span>Discount:</span>
                    <span>-Rp. {{ number_format($invoice->discount) }}</span>
                </div>
                <div
                    class="flex justify-between font-mono text-[11px] font-bold"
                >
                    <span>Net Total:</span>
                    <span>Rp. {{ number_format($invoice->grand_total) }}</span>
                </div>
                @endif @if ($dpPaid > 0)
                <div class="flex justify-between text-cyan-600">
                    <span>DP Paid ({{ $paymentDpMethod }}):</span>
                    <span>Rp. {{ number_format($dpPaid) }}</span>
                </div>
                @endif @if ($settlementAmount > 0)
                <div class="flex justify-between text-emerald-600">
                    <span>Settlement ({{ $paymentMethod }}):</span>
                    <span>Rp. {{ number_format($settlementAmount) }}</span>
                </div>
                @endif @if ($totalPaid > 0)
                <div
                    class="flex justify-between font-mono font-bold text-gray-500"
                >
                    <span>Total Paid:</span>
                    <span>Rp. {{ number_format($totalPaid) }}</span>
                </div>
                @endif @if ($invoice->accounts_receivable > 0)
                <div
                    class="flex justify-between border-t border-dashed border-gray-300 pt-1 font-bold text-red-500"
                >
                    <span>Remaining Balance (AR):</span>
                    <span
                        >Rp. {{ number_format($invoice->accounts_receivable)
                        }}</span
                    >
                </div>
                @endif
            </div>

            <div class="divider"></div>

            <!-- Status Tag -->
            <div
                class="my-2 rounded border border-black py-1.5 text-center text-xs font-bold tracking-widest uppercase"
            >
                @if ($invoice->status == 0)
                <span class="text-red-600">UNPAID (BELUM LUNAS)</span>
                @elseif ($invoice->status == 1)
                <span class="text-yellow-600">DOWN PAYMENT (DP)</span>
                @else
                <span class="text-emerald-600">PAID (LUNAS)</span>
                @endif
            </div>

            <div class="divider"></div>

            <!-- Footer -->
            <div
                class="mt-3 space-y-1 text-center font-sans text-[9px] text-gray-500"
            >
                <p class="font-bold text-black uppercase">Terima Kasih</p>
                <p>
                    Barang yang sudah dicetak / dibeli tidak dapat ditukar atau
                    dikembalikan.
                </p>
                <p>
                    Silakan simpan struk ini sebagai bukti pembayaran dan
                    pengambilan pesanan Anda.
                </p>
            </div>
        </div>
    </body>
</html>
