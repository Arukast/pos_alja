<?php

declare(strict_types=1);

use App\Modules\Catalog\Http\Controllers\AttributeController;
use App\Modules\Catalog\Http\Controllers\AttributeTypeController;
use App\Modules\Catalog\Http\Controllers\CategoryController;
use App\Modules\Catalog\Http\Controllers\MachineController;
use App\Modules\Catalog\Http\Controllers\MaterialController;
use App\Modules\Catalog\Http\Controllers\PricingController;
use App\Modules\Catalog\Http\Controllers\ProductController;
use App\Modules\Catalog\Http\Controllers\StockLedgerController;
use App\Modules\Identity\Http\Controllers\AuthController;
use App\Modules\Identity\Http\Controllers\BranchController;
use App\Modules\Identity\Http\Controllers\UserController;
use App\Modules\Production\Http\Controllers\ProductionController;
use App\Modules\Production\Http\Controllers\WasteController;
use App\Modules\Reporting\Http\Controllers\AuditController;
use App\Modules\Reporting\Http\Controllers\DashboardController;
use App\Modules\Reporting\Http\Controllers\ReportController;
use App\Modules\Sales\Http\Controllers\CustomerController;
use App\Modules\Sales\Http\Controllers\ExpenseController;
use App\Modules\Sales\Http\Controllers\InvoiceController;
use App\Modules\Sales\Http\Controllers\PaymentController;
use App\Modules\Sales\Http\Controllers\QuoteController;
use App\Modules\Sales\Http\Controllers\QuoteOptionsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::delete('/auth/profile', [AuthController::class, 'destroy']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword'])
            ->middleware('throttle:sensitive');
        Route::post('/auth/confirm-password', [AuthController::class, 'confirmPassword'])
            ->middleware('throttle:sensitive');

        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::get('/branches/{branch}', [BranchController::class, 'show']);
        Route::put('/branches/{branch}', [BranchController::class, 'update']);
        Route::patch('/branches/{branch}/status', [BranchController::class, 'status']);
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::post('/users/{user}/restore', [UserController::class, 'restore']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/attribute-types', [AttributeTypeController::class, 'index']);
        Route::get('/attributes', [AttributeController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/materials', [MaterialController::class, 'index']);
        Route::get('/machines', [MachineController::class, 'index']);
        Route::get('/pricing', [PricingController::class, 'index']);
        Route::get('/stock-ledgers', [StockLedgerController::class, 'index']);

        Route::get('/materials/similar', [MaterialController::class, 'similar']);
        Route::post('/materials', [MaterialController::class, 'store']);
        Route::put('/materials/{material}', [MaterialController::class, 'update']);
        Route::delete('/materials/{material}', [MaterialController::class, 'destroy']);
        Route::post('/materials/{material}/restore', [MaterialController::class, 'restore']);
        Route::post('/materials/{material}/stock', [MaterialController::class, 'stock']);
        Route::post('/materials/{material}/activate', [MaterialController::class, 'activate']);

        Route::post('/machines', [MachineController::class, 'store']);
        Route::put('/machines/{machine}', [MachineController::class, 'update']);
        Route::delete('/machines/{machine}', [MachineController::class, 'destroy']);
        Route::post('/machines/{machine}/restore', [MachineController::class, 'restore']);

        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::post('/attribute-types', [AttributeTypeController::class, 'store']);
        Route::delete('/attribute-types/{attributeType}', [AttributeTypeController::class, 'destroy']);

        Route::post('/attributes', [AttributeController::class, 'store']);
        Route::put('/attributes/{attribute}', [AttributeController::class, 'update']);
        Route::delete('/attributes/{attribute}', [AttributeController::class, 'destroy']);

        Route::post('/pricing/clone-tiers', [PricingController::class, 'cloneTiers']);
        Route::post('/pricing', [PricingController::class, 'store']);
        Route::put('/pricing/{pricing}', [PricingController::class, 'update']);
        Route::delete('/pricing/{pricing}', [PricingController::class, 'destroy']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/quotes/options', [QuoteOptionsController::class, 'index']);
        Route::post('/quotes', [QuoteController::class, 'store']);
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/ticket', [InvoiceController::class, 'ticket']);
        Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'receipt']);
        Route::post('/invoices/{invoice}/down-payments', [PaymentController::class, 'downPayment']);
        Route::post('/invoices/{invoice}/settlements', [PaymentController::class, 'settle']);
        Route::post('/invoices/{invoice}/pickup', [PaymentController::class, 'pickup']);
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::post('/expenses', [ExpenseController::class, 'store']);

        Route::get('/production/queue', [ProductionController::class, 'queue']);
        Route::get('/production/summary', [ProductionController::class, 'summary']);
        Route::post('/orders/{order}/start', [ProductionController::class, 'start']);
        Route::post('/orders/{order}/complete', [ProductionController::class, 'complete']);
        Route::get('/waste/options', [WasteController::class, 'options']);
        Route::get('/waste', [WasteController::class, 'index']);
        Route::post('/waste', [WasteController::class, 'store']);

        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/reports/gross-revenue', [ReportController::class, 'grossRevenue']);
        Route::get('/reports/base-cost', [ReportController::class, 'baseCost']);
        Route::get('/reports/net-profit', [ReportController::class, 'netProfit']);
        Route::get('/reports/expenses', [ReportController::class, 'expenses']);
        Route::get('/reports/sparkline', [ReportController::class, 'sparkline']);
        Route::get('/reports/designer-leaderboard', [ReportController::class, 'leaderboard']);
        Route::get('/audit-logs', [AuditController::class, 'index']);
        Route::get('/audit-logs/{activityLog}', [AuditController::class, 'show']);
    });
});
