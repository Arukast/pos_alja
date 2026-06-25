<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>
            Receipt #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
        </title>
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
                class="flex items-center text-xs font-bold text-gray-500 hover:text-gray-700"
            >
                &larr; Back
            </a>
            <button
                onclick="window.print()"
                class="rounded bg-emerald-500 px-3 py-1.5 font-bold text-white shadow-sm transition hover:bg-emerald-600"
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
                    <span
                        >Inv: #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT)
                        }}</span
                    >
                    <span>Date: {{ $invoice->created_date }}</span>
                </div>
                <div class="flex justify-between">
                    <span>CS: {{ $invoice->customer_service }}</span>
                    <span
                        >Cashier: {{ $invoice->cashier_name ?:
                        ($invoice->cashier_dp_name ?: '-') }}</span
                    >
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
                @php // Pre-discount total = sum of all line-item subtotals
                (always stored clean, before invoice-level discount)
                $preDiscountTotal = $invoice->orders->sum('subtotal'); @endphp
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
                @endif @if ($invoice->dp_paid > 0)
                <div class="flex justify-between text-cyan-600">
                    <span>DP Paid ({{ $invoice->payment_dp_method }}):</span>
                    <span>Rp. {{ number_format($invoice->dp_paid) }}</span>
                </div>
                @endif @if ($invoice->settlement_amount > 0)
                <div class="flex justify-between text-emerald-600">
                    <span>Settlement ({{ $invoice->payment_method }}):</span>
                    <span
                        >Rp. {{ number_format($invoice->settlement_amount)
                        }}</span
                    >
                </div>
                @endif @if ($invoice->payment_amount > 0)
                <div class="flex justify-between text-gray-500">
                    <span>Cash Received:</span>
                    <span
                        >Rp. {{ number_format($invoice->payment_amount) }}</span
                    >
                </div>
                <div class="flex justify-between font-bold text-emerald-600">
                    <span>Change (Kembalian):</span>
                    <span
                        >Rp. {{ number_format($invoice->change_amount) }}</span
                    >
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
