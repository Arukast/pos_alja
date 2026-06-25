<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</title>
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
<body class="text-gray-900 leading-tight text-xs">
    
    <!-- Action buttons (Hidden when printing) -->
    <div class="max-w-[80mm] mx-auto mb-4 flex justify-between items-center no-print text-[11px] font-sans">
        <a href="/dashboard" class="text-xs font-bold text-gray-500 hover:text-gray-700 flex items-center">
            &larr; Back
        </a>
        <button onclick="window.print()" class="rounded bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 shadow-sm transition">
            Print (Ctrl + P)
        </button>
    </div>

    <!-- Receipt paper -->
    <div class="receipt-container text-[11px]">
        <!-- Store Header -->
        <div class="text-center space-y-0.5">
            <h1 class="text-base font-black uppercase tracking-tight">ALJA DIGIPRINT</h1>
            <p class="text-[10px]">Menes Main Branch</p>
            <p class="text-[9px] text-gray-500">Jalan Raya Menes, Pandeglang, Banten</p>
            <p class="text-[9px] text-gray-500">Telp: 0812-9876-5432</p>
        </div>

        <div class="divider"></div>

        <!-- Meta Info -->
        <div class="space-y-1">
            <div class="flex justify-between">
                <span>Inv: #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span>Date: {{ $invoice->created_date }}</span>
            </div>
            <div class="flex justify-between">
                <span>CS: {{ $invoice->customer_service }}</span>
                <span>Cashier: {{ $invoice->cashier_name ?: ($invoice->cashier_dp_name ?: '-') }}</span>
            </div>
            <div>
                <span>Cust: {{ $invoice->customer->name }} ({{ $invoice->customer->phone ?: '-' }})</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Column Headers -->
        <div class="grid grid-cols-12 font-bold mb-1">
            <span class="col-span-8">Description</span>
            <span class="col-span-4 text-right">Subtotal</span>
        </div>

        <!-- Order Items list -->
        <div class="space-y-2">
            @foreach ($invoice->orders as $oItem)
                <div class="grid grid-cols-12 items-start">
                    <div class="col-span-8 flex flex-col">
                        <span class="font-bold">{{ $oItem->file_name }}</span>
                        <span class="text-[10px] text-gray-500">{{ (float)$oItem->quantity }}x @ Rp. {{ number_format($oItem->unit_price) }}</span>
                        @if ($oItem->notes)
                            <span class="text-[9px] italic text-gray-400 font-sans">Notes: {{ $oItem->notes }}</span>
                        @endif
                    </div>
                    <span class="col-span-4 text-right font-bold self-end">Rp. {{ number_format($oItem->subtotal) }}</span>
                </div>
            @endforeach
        </div>

        <div class="divider"></div>

        <!-- Summary list -->
        <div class="space-y-1.5 font-sans font-medium text-[10px]">
            @php
                // Pre-discount total = sum of all line-item subtotals (always stored clean, before invoice-level discount)
                $preDiscountTotal = $invoice->orders->sum('subtotal');
            @endphp
            <div class="flex justify-between font-mono text-[11px] font-bold">
                <span>Grand Total:</span>
                <span>Rp. {{ number_format($preDiscountTotal) }}</span>
            </div>
            
            @if ($invoice->discount > 0)
                <div class="flex justify-between text-purple-600">
                    <span>Discount:</span>
                    <span>-Rp. {{ number_format($invoice->discount) }}</span>
                </div>
                <div class="flex justify-between font-mono text-[11px] font-bold">
                    <span>Net Total:</span>
                    <span>Rp. {{ number_format($invoice->grand_total) }}</span>
                </div>
            @endif

            @if ($invoice->dp_paid > 0)
                <div class="flex justify-between text-cyan-600">
                    <span>DP Paid ({{ $invoice->payment_dp_method }}):</span>
                    <span>Rp. {{ number_format($invoice->dp_paid) }}</span>
                </div>
            @endif

            @if ($invoice->settlement_amount > 0)
                <div class="flex justify-between text-emerald-600">
                    <span>Settlement ({{ $invoice->payment_method }}):</span>
                    <span>Rp. {{ number_format($invoice->settlement_amount) }}</span>
                </div>
            @endif

            @if ($invoice->payment_amount > 0)
                <div class="flex justify-between text-gray-500">
                    <span>Cash Received:</span>
                    <span>Rp. {{ number_format($invoice->payment_amount) }}</span>
                </div>
                <div class="flex justify-between text-emerald-600 font-bold">
                    <span>Change (Kembalian):</span>
                    <span>Rp. {{ number_format($invoice->change_amount) }}</span>
                </div>
            @endif

            @if ($invoice->accounts_receivable > 0)
                <div class="flex justify-between text-red-500 font-bold border-t border-dashed border-gray-300 pt-1">
                    <span>Remaining Balance (AR):</span>
                    <span>Rp. {{ number_format($invoice->accounts_receivable) }}</span>
                </div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Status Tag -->
        <div class="text-center py-1.5 font-bold uppercase tracking-widest border border-black rounded my-2 text-xs">
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
        <div class="text-center text-[9px] text-gray-500 space-y-1 mt-3 font-sans">
            <p class="font-bold text-black uppercase">Terima Kasih</p>
            <p>Barang yang sudah dicetak / dibeli tidak dapat ditukar atau dikembalikan.</p>
            <p>Silakan simpan struk ini sebagai bukti pembayaran dan pengambilan pesanan Anda.</p>
        </div>
    </div>

</body>
</html>
