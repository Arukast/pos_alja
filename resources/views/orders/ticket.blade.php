<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Ticket #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body {
                background-color: white;
                color: black;
                font-size: 12px;
            }
            .no-print {
                display: none !important;
            }
            .print-border {
                border: 1px solid black !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans p-6">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-gray-100 print:border-none print:shadow-none print:p-0">
        
        <!-- Header Actions -->
        <div class="flex justify-between items-center mb-6 no-print">
            <a href="/dashboard" class="text-xs font-bold text-gray-500 hover:text-gray-700 flex items-center">
                &larr; Back to Dashboard
            </a>
            <button onclick="window.print()" class="rounded-lg bg-teal-500 hover:bg-teal-600 text-white font-bold px-4 py-2 text-xs transition shadow-sm">
                Print Job Ticket (Ctrl + P)
            </button>
        </div>

        <!-- Job Ticket Content -->
        <div class="border-2 border-gray-900 p-6 rounded-lg space-y-6 print-border">
            
            <!-- Branding & Title -->
            <div class="flex justify-between items-start border-b border-gray-300 pb-4">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-gray-950 uppercase">ALJA digiprint</h1>
                    <p class="text-xs text-gray-500 mt-0.5">High-Quality Print & Production Shop</p>
                </div>
                <div class="text-right">
                    <h2 class="text-lg font-bold text-gray-900 uppercase">WORK ORDER TICKET</h2>
                    <p class="text-sm font-black text-teal-600 mt-0.5">#{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>

            <!-- Meta Data Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                <div>
                    <span class="block text-gray-400 font-semibold uppercase tracking-wider">Date Logged</span>
                    <span class="font-bold text-gray-900">{{ $invoice->created_date }}</span>
                </div>
                <div>
                    <span class="block text-gray-400 font-semibold uppercase tracking-wider">Target Deadline</span>
                    <span class="font-bold text-red-500">{{ $invoice->deadline }}</span>
                </div>
                <div>
                    <span class="block text-gray-400 font-semibold uppercase tracking-wider">Intake CS</span>
                    <span class="font-bold text-gray-900">{{ $invoice->customer_service }}</span>
                </div>
                <div>
                    <span class="block text-gray-400 font-semibold uppercase tracking-wider">Channel Source</span>
                    <span class="font-bold text-gray-900">{{ $invoice->source === 0 ? 'Offline / Walk-In' : 'Online Order' }}</span>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-xs space-y-2 print-border">
                <h3 class="font-bold text-gray-900 uppercase border-b border-gray-200 pb-1">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div><span class="text-gray-400">Name:</span> <strong class="text-gray-900">{{ $invoice->customer->name }}</strong></div>
                    <div><span class="text-gray-400">Phone:</span> <strong class="text-gray-900">{{ $invoice->customer->phone ?: '-' }}</strong></div>
                    <div><span class="text-gray-400">Type:</span> <strong class="text-teal-600 uppercase font-black">{{ $invoice->customer->type }}</strong></div>
                </div>
                <div>
                    <span class="text-gray-400">Address:</span> <span class="text-gray-700 font-medium">{{ $invoice->customer->address ?: 'N/A' }}</span>
                </div>
            </div>

            <!-- Order Items & Checklists -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-950 uppercase tracking-wider">Layout Files & Production Specs</h3>

                <div class="border border-gray-200 rounded-lg overflow-hidden divide-y divide-gray-200 print-border">
                    @foreach ($invoice->orders as $idx => $order)
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold text-teal-600">Item #{{ $idx + 1 }}</span>
                                <span class="text-xs font-black bg-gray-100 px-2.5 py-0.5 rounded text-gray-800">QTY: {{ $order->quantity }}</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="block text-gray-400">File Layout Name:</span>
                                    <strong class="text-sm font-bold text-gray-900 block truncate">{{ $order->file_name }}</strong>
                                    
                                    <span class="block text-gray-400 mt-2">Production Notes / Specs:</span>
                                    <p class="text-gray-700 font-medium whitespace-pre-line mt-0.5">{{ $order->notes }}</p>
                                </div>

                                <!-- Operator Checklist Grid -->
                                <div class="border border-gray-100 p-3 rounded-lg bg-gray-50/50 space-y-2 print-border">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 border-b pb-1">Operator Queue Checklist</div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="rounded border-gray-300 text-teal-500">
                                            <span>Prepress OK</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="rounded border-gray-300 text-teal-500">
                                            <span>Printed OK</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="rounded border-gray-300 text-teal-500">
                                            <span>Finishing OK</span>
                                        </label>
                                        <label class="flex items-center space-x-2">
                                            <input type="checkbox" class="rounded border-gray-300 text-teal-500">
                                            <span>QC & Pack OK</span>
                                        </label>
                                    </div>
                                    <div class="text-[9px] text-gray-400 pt-1">
                                        Operator Sign-off: _____________________
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-3 gap-6 text-center text-xs pt-8 border-t border-gray-200">
                <div class="space-y-12">
                    <span class="block text-gray-400">Customer Signature</span>
                    <div class="border-b border-gray-400 mx-auto w-36"></div>
                    <span class="block font-bold text-gray-900">{{ $invoice->customer->name }}</span>
                </div>
                <div class="space-y-12">
                    <span class="block text-gray-400">Intake CS / Prepress</span>
                    <div class="border-b border-gray-400 mx-auto w-36"></div>
                    <span class="block font-bold text-gray-900">{{ $invoice->customer_service }}</span>
                </div>
                <div class="space-y-12">
                    <span class="block text-gray-400">Print Operator Duty</span>
                    <div class="border-b border-gray-400 mx-auto w-36"></div>
                    <span class="block text-gray-300">_________________</span>
                </div>
            </div>
            
        </div>
    </div>
</body>
</html>
