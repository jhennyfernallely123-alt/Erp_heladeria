<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\InventoryController;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected API Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Categories & Products
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('products', ProductController::class);
        Route::patch('/products/{product}/toggle-active', [ProductController::class, 'toggleActive']);
        Route::post('/products/{product}/image', [ProductController::class, 'uploadImage']);

        // Inventory (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);
            Route::post('/inventory/{stock}/adjust', [InventoryController::class, 'adjust']);
        });

        // Tables & Orders (POS)
        Route::apiResource('tables', TableController::class);
        Route::apiResource('orders', OrderController::class);
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

        // Invoicing & Checkout
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview']);
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
        Route::get('/invoices/{invoice}/ticket', [InvoiceController::class, 'downloadTicket']);

        // Cash Register (Turnos de caja)
        Route::get('/cash-register/current', [CashRegisterController::class, 'current']);
        Route::post('/cash-register/open', [CashRegisterController::class, 'open']);
        Route::post('/cash-register/movements', [CashRegisterController::class, 'addMovement']);
        Route::post('/cash-register/close', [CashRegisterController::class, 'close']);

        // Financial reports & Analytics
        Route::get('/finance/reports', [FinanceController::class, 'reports']);

        // Business Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
    });
});
