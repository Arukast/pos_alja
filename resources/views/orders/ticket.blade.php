<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>
            Job Ticket #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
        </title>
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
    <body class="bg-gray-50 p-6 font-sans text-gray-900">
        <div
            class="mx-auto max-w-4xl rounded-xl border border-gray-100 bg-white p-8 shadow-sm print:border-none print:p-0 print:shadow-none"
        >
            <!-- Header Actions -->
            <div class="no-print mb-6 flex items-center justify-between">
                <a
                    href="/dashboard"
                    class="flex items-center text-xs font-bold text-gray-500 hover:text-gray-700"
                >
                    &larr; Back to Dashboard
                </a>
                <button
                    onclick="window.print()"
                    class="rounded-lg bg-teal-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-teal-600"
                >
                    Print Job Ticket (Ctrl + P)
                </button>
            </div>

            <!-- Job Ticket Content -->
            <div
                class="print-border space-y-6 rounded-lg border-2 border-gray-900 p-6"
            >
                <!-- Branding & Title -->
                <div
                    class="flex items-start justify-between border-b border-gray-300 pb-4"
                >
                    <div>
                        <h1
                            class="text-2xl font-black tracking-tight text-gray-950 uppercase"
                        >
                            {{ config('brand.fullname', 'ALJA digiprint') }}
                        </h1>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ config('brand.slogan', 'High-Quality Print &
                            Production Shop') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <h2 class="text-lg font-bold text-gray-900 uppercase">
                            WORK ORDER TICKET
                        </h2>
                        <p class="mt-0.5 text-sm font-black text-teal-600">
                            #{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}
                        </p>
                    </div>
                </div>

                <!-- Meta Data Grid -->
                <div class="grid grid-cols-2 gap-4 text-xs md:grid-cols-4">
                    <div>
                        <span
                            class="block font-semibold tracking-wider text-gray-400 uppercase"
                            >Date Logged</span
                        >
                        <span class="font-bold text-gray-900"
                            >{{ $invoice->created_date }}</span
                        >
                    </div>
                    <div>
                        <span
                            class="block font-semibold tracking-wider text-gray-400 uppercase"
                            >Target Deadline</span
                        >
                        <span class="font-bold text-red-500"
                            >{{ $invoice->deadline }}</span
                        >
                    </div>
                    <div>
                        <span
                            class="block font-semibold tracking-wider text-gray-400 uppercase"
                            >Intake CS</span
                        >
                        <span class="font-bold text-gray-900"
                            >{{ $invoice->customer_service }}</span
                        >
                    </div>
                    <div>
                        <span
                            class="block font-semibold tracking-wider text-gray-400 uppercase"
                            >Channel Source</span
                        >
                        <span class="font-bold text-gray-900"
                            >{{ $invoice->source === 0 ? 'Offline / Walk-In' :
                            'Online Order' }}</span
                        >
                    </div>
                </div>

                <!-- Customer Details -->
                <div
                    class="print-border space-y-2 rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs"
                >
                    <h3
                        class="border-b border-gray-200 pb-1 font-bold text-gray-900 uppercase"
                    >
                        Customer Information
                    </h3>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                        <div>
                            <span class="text-gray-400">Name:</span>
                            <strong class="text-gray-900"
                                >{{ $invoice->customer->name }}</strong
                            >
                        </div>
                        <div>
                            <span class="text-gray-400">Phone:</span>
                            <strong class="text-gray-900"
                                >{{ $invoice->customer->phone ?: '-' }}</strong
                            >
                        </div>
                        <div>
                            <span class="text-gray-400">Type:</span>
                            <strong class="font-black text-teal-600 uppercase"
                                >{{ $invoice->customer->type }}</strong
                            >
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-400">Address:</span>
                        <span class="font-medium text-gray-700"
                            >{{ $invoice->customer->address ?: 'N/A' }}</span
                        >
                    </div>
                </div>

                <!-- Order Items & Checklists -->
                <div class="space-y-4">
                    <h3
                        class="text-xs font-bold tracking-wider text-gray-950 uppercase"
                    >
                        Layout Files & Production Specs
                    </h3>

                    <div
                        class="print-border divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200"
                    >
                        @foreach ($invoice->orders as $idx => $order)
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-teal-600"
                                    >Item #{{ $idx + 1 }}</span
                                >
                                <span
                                    class="rounded bg-gray-100 px-2.5 py-0.5 text-xs font-black text-gray-800"
                                    >QTY: {{ $order->quantity }}</span
                                >
                            </div>

                            <div
                                class="grid grid-cols-1 gap-4 text-xs md:grid-cols-2"
                            >
                                <div>
                                    <span class="block text-gray-400"
                                        >File Layout Name:</span
                                    >
                                    <strong
                                        class="block truncate text-sm font-bold text-gray-900"
                                        >{{ $order->file_name }}</strong
                                    >

                                    <span class="mt-2 block text-gray-400"
                                        >Production Notes / Specs:</span
                                    >
                                    <p
                                        class="mt-0.5 font-medium whitespace-pre-line text-gray-700"
                                    >
                                        {{ $order->notes }}
                                    </p>
                                </div>

                                <!-- Operator Checklist Grid -->
                                <div
                                    class="print-border space-y-2 rounded-lg border border-gray-100 bg-gray-50/50 p-3"
                                >
                                    <div
                                        class="border-b pb-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                                    >
                                        Operator Queue Checklist
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs">
                                        <label
                                            class="flex items-center space-x-2"
                                        >
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-teal-500"
                                            />
                                            <span>Prepress OK</span>
                                        </label>
                                        <label
                                            class="flex items-center space-x-2"
                                        >
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-teal-500"
                                            />
                                            <span>Printed OK</span>
                                        </label>
                                        <label
                                            class="flex items-center space-x-2"
                                        >
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-teal-500"
                                            />
                                            <span>Finishing OK</span>
                                        </label>
                                        <label
                                            class="flex items-center space-x-2"
                                        >
                                            <input
                                                type="checkbox"
                                                class="rounded border-gray-300 text-teal-500"
                                            />
                                            <span>QC & Pack OK</span>
                                        </label>
                                    </div>
                                    <div class="pt-1 text-[9px] text-gray-400">
                                        Operator Sign-off: _____________________
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Signatures Section -->
                <div
                    class="grid grid-cols-3 gap-6 border-t border-gray-200 pt-8 text-center text-xs"
                >
                    <div class="space-y-12">
                        <span class="block text-gray-400"
                            >Customer Signature</span
                        >
                        <div
                            class="mx-auto w-36 border-b border-gray-400"
                        ></div>
                        <span class="block font-bold text-gray-900"
                            >{{ $invoice->customer->name }}</span
                        >
                    </div>
                    <div class="space-y-12">
                        <span class="block text-gray-400"
                            >Intake CS / Prepress</span
                        >
                        <div
                            class="mx-auto w-36 border-b border-gray-400"
                        ></div>
                        <span class="block font-bold text-gray-900"
                            >{{ $invoice->customer_service }}</span
                        >
                    </div>
                    <div class="space-y-12">
                        <span class="block text-gray-400"
                            >Print Operator Duty</span
                        >
                        <div
                            class="mx-auto w-36 border-b border-gray-400"
                        ></div>
                        <span class="block text-gray-300"
                            >_________________</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
