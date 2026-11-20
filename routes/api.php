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
use App\Modules\Sales\Http\Controllers\CustomerController;
use App\Modules\Sales\Http\Controllers\ExpenseController;
use App\Modules\Sales\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::delete('/auth/profile', [AuthController::class, 'destroy']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);
        Route::post('/auth/confirm-password', [AuthController::class, 'confirmPassword']);

        Route::get('/branches', [BranchController::class, 'index']);
        Route::get('/branches/{branch}', [BranchController::class, 'show']);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/attribute-types', [AttributeTypeController::class, 'index']);
        Route::get('/attributes', [AttributeController::class, 'index']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/materials', [MaterialController::class, 'index']);
        Route::get('/machines', [MachineController::class, 'index']);
        Route::get('/pricing', [PricingController::class, 'index']);
        Route::get('/stock-ledgers', [StockLedgerController::class, 'index']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::get('/invoices/{invoice}/ticket', [InvoiceController::class, 'ticket']);
        Route::get('/invoices/{invoice}/receipt', [InvoiceController::class, 'receipt']);
        Route::get('/expenses', [ExpenseController::class, 'index']);
    });
});
