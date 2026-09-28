<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Auth
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Portal del empleado.
    //
    // Estas rutas NO cuelgan de auth:sanctum y es a proposito. El empleado no
    // tiene ni necesita login con email y contrasena: elige su nombre e
    // ingresa su PIN, como en el modulo de turnos. El PIN abre una sesion
    // corta y opaca (middleware 'portal') que solo habilita estas rutas.
    //
    // Si colgaran de Sanctum, un PIN de 4 digitos adivinado daria acceso a
    // todo lo que el rol permita. El listado de nombres cuelga aqui y no de
    // auth porque el empleado lo necesita antes de identificarse; solo
    // devuelve nombre y rol.
    Route::get('/portal/employees', [EmployeeController::class, 'index']);
    Route::post('/portal/login', [EmployeeController::class, 'login']);

    Route::middleware('portal')->group(function () {
        Route::post('/portal/logout', [EmployeeController::class, 'logout']);
        Route::get('/portal/profile', [EmployeeController::class, 'profile']);
        Route::get('/portal/requests', [EmployeeController::class, 'requests']);
        Route::post('/portal/requests', [EmployeeController::class, 'storeRequest']);
        Route::get('/portal/attachments/{attachment}', [EmployeeController::class, 'download']);
    });

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
        //
        // La gaveta es una sola y es del local, no del cajero. Abrir y cerrarla
        // es operacion del cajero; el admin consulta, registra gastos menores
        // sobre el turno abierto y puede hacer retiros de gaveta.
        //
        // Ojo: se usa role: y no permission: para abrir/cerrar porque el admin
        // tiene todos los permisos, asi que un permiso nunca lo excluiria.
        Route::middleware('permission:manage_cash_register')->group(function () {
            Route::get('/cash-register/current', [CashRegisterController::class, 'current']);
            Route::get('/cash-register/open-session', [CashRegisterController::class, 'openSession']);
            Route::get('/cash-register/balance', [CashRegisterController::class, 'balance']);
            Route::post('/cash-register/movements', [CashRegisterController::class, 'addMovement']);
        });

        Route::middleware('role:cashier')->group(function () {
            Route::post('/cash-register/open', [CashRegisterController::class, 'open']);
            Route::post('/cash-register/close', [CashRegisterController::class, 'close']);
        });

        // Vista de caja del administrador y retiros de gaveta.
        Route::middleware('role:admin')->group(function () {
            Route::get('/cash-register/overview', [CashRegisterController::class, 'overview']);
            Route::get('/cash-register/history', [CashRegisterController::class, 'history']);
            Route::post('/cash-register/withdrawals', [CashRegisterController::class, 'withdraw']);
        });

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

        // Gestion de meseros y equipo: solo el administrador.
        Route::middleware('permission:manage_settings')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}/reset-pin', [UserController::class, 'resetPin']);

            // Vista de equipo: fichas, saldos de vacaciones y solicitudes.
            Route::get('/team', [TeamController::class, 'index']);
            Route::get('/team/requests', [TeamController::class, 'requests']);
            Route::post('/team/requests/{timeOff}/review', [TeamController::class, 'review']);
            // Va antes de /team/{user} para que 'attachments' no se lea como id.
            Route::get('/team/attachments/{attachment}', [TeamController::class, 'download']);
            Route::get('/team/{user}', [TeamController::class, 'show']);
            Route::patch('/team/{user}', [TeamController::class, 'update']);
        });

        // Business Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);
    });
});
