<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\InventoryService;
use App\Services\CashRegisterService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderAndCashServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_deducts_stock_and_table_status_updates(): void
    {
        $user = User::create(['name' => 'Waiter 1', 'email' => 'waiter@test.com', 'password' => bcrypt('123')]);
        $table = RestaurantTable::create(['number' => '4', 'name' => 'Mesa 4', 'status' => 'available']);
        $cat = Category::create(['name' => 'Copas', 'slug' => 'copas']);
        $product = Product::create([
            'category_id' => $cat->id,
            'name' => 'Copa Frutos Rojos',
            'cost_price' => 4000,
            'sale_price' => 15000,
            'stock_quantity' => 20,
        ]);

        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'table_id' => $table->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'notes' => 'Sin chantilly',
                ]
            ]
        ], $user);

        $this->assertEquals('occupied', $table->fresh()->status);
        $this->assertEquals(18.00, $product->fresh()->stock_quantity);
        $this->assertEquals(30000.00, $order->total);

        // Cancel order restores stock and table
        $orderService->updateStatus($order, 'cancelled');
        $this->assertEquals('available', $table->fresh()->status);
        $this->assertEquals(20.00, $product->fresh()->stock_quantity);
    }

    public function test_cash_register_lifecycle_and_balance_difference(): void
    {
        $user = User::create(['name' => 'Cajero Test', 'email' => 'cajero1@test.com', 'password' => bcrypt('123')]);
        $cashService = app(CashRegisterService::class);

        // 1. Open register
        $session = $cashService->openRegister($user, 50000, 'Apertura de turno mañana');
        $this->assertEquals('open', $session->status);
        $this->assertEquals(50000, $session->opening_balance);

        // 2. Add cash out movement
        $cashService->addMovement($session, $user, 'cash_out', 10000, 'operating_expense', 'Compra de servilletas');

        // 3. Simulate a cash sale
        $table = RestaurantTable::create(['number' => '1', 'name' => 'Mesa 1']);
        $order = \App\Models\Order::create([
            'order_number' => 'ORD-SALE-1',
            'table_id' => $table->id,
            'user_id' => $user->id,
            'total' => 25000,
        ]);
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'FAC-CASH-1',
            'total' => 25000,
        ]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cash',
            'amount' => 25000,
        ]);

        // Expected cash: 50,000 (base) + 25,000 (sale) - 10,000 (expense) = 65,000
        // Cajero counts 64,500 (500 faltante)
        $closed = $cashService->closeRegister($session, 64500, 'Arqueo de cierre');

        $this->assertEquals('closed', $closed->status);
        $this->assertEquals(65000.00, $closed->expected_balance);
        $this->assertEquals(64500.00, $closed->actual_balance);
        $this->assertEquals(-500.00, $closed->difference);

        // Verify report service compiles metrics
        $reportService = app(ReportService::class);
        $summary = $reportService->getSummary();
        $this->assertEquals(25000.00, $summary['total_sales']);
        $this->assertEquals(10000.00, $summary['total_expenses']);
    }
}
