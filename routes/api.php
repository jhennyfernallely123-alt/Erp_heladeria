<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkShiftController;
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
        //
        // Cobrar es exclusivo de quien tenga checkout_invoice (admin y cajero).
        // El mesero puede guardar y modificar comandas, pero no factura: sin
        // este middleware el router del front lo esconde, pero la API aceptaba
        // el POST igual y un mesero podia cerrar la venta desde curl.
        Route::middleware('permission:checkout_invoice')->group(function () {
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::post('/invoices', [InvoiceController::class, 'store']);
            Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview']);
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
            Route::get('/invoices/{invoice}/ticket', [InvoiceController::class, 'downloadTicket']);
        });

        // Cash Register (Turnos de caja)
        Route::get('/cash-register/current', [CashRegisterController::class, 'current']);
        Route::post('/cash-register/open', [CashRegisterController::class, 'open']);
        Route::post('/cash-register/movements', [CashRegisterController::class, 'addMovement']);
        Route::post('/cash-register/close', [CashRegisterController::class, 'close']);

        // Financial reports & Analytics
        Route::get('/finance/reports', [FinanceController::class, 'reports']);

        // Turnos de mesero
        //
        // El permiso clock_shift va en el grupo entero porque TODO este modulo
        // es para quien opera el dispositivo compartido: ver el listado de
        // meseros y abrir turnos con el PIN. A diferencia de la facturacion,
        // aca no hace falta un chequeo condicional porque no hay una accion
        // alternativa del mismo rol que quede habilitada.
        Route::middleware('permission:clock_shift')->group(function () {
            Route::get('/shifts/workers', [WorkShiftController::class, 'workers']);
            Route::get('/shifts', [WorkShiftController::class, 'index']);
            Route::get('/shifts/summary', [WorkShiftController::class, 'summary']);
            Route::post('/shifts/open', [WorkShiftController::class, 'store']);
            Route::get('/shifts/{shift}', [WorkShiftController::class, 'show']);
            Route::post('/shifts/{shift}/close', [WorkShiftController::class, 'close']);
        });

        // Gestion de meseros: solo el administrador.
        Route::middleware('permission:manage_settings')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::post('/users/{user}/reset-pin', [UserController::class, 'resetPin']);
        });

        // Business Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
    });
});
