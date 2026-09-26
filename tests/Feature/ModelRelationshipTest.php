<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestaurantTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\CashRegister;
use App\Models\CashMovement;
use App\Models\BusinessSetting;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_and_relationships_work_correctly(): void
    {
        // 1. Spatie Roles and User
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignRole('admin');
        $this->assertTrue($user->hasRole('admin'));

        // 2. Category and Product with Variants
        $category = Category::create([
            'name' => 'Helados Gourmet',
            'slug' => 'helados-gourmet',
            'icon' => 'ice-cream',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Copa Brownie',
            'cost_price' => 5000.00,
            'sale_price' => 12000.00,
            'stock_type' => 'unit',
            'stock_quantity' => 15.00,
            'min_stock_alert' => 5.00,
            'has_variants' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Tamaño Grande 3 Bolas',
            'cost_price' => 7000.00,
            'sale_price' => 16000.00,
            'stock_quantity' => 10.00,
        ]);

        $this->assertEquals('Helados Gourmet', $product->category->name);
        $this->assertCount(1, $product->variants);
        $this->assertFalse($product->isLowStock());

        // 3. Table and Order with Items
        $table = RestaurantTable::create([
            'number' => '1',
            'name' => 'Mesa Terraza 1',
            'capacity' => 4,
            'status' => 'occupied',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-0001',
            'table_id' => $table->id,
            'user_id' => $user->id,
            'type' => 'dine_in',
            'status' => 'open',
            'subtotal' => 16000.00,
            'tax_total' => 0.00,
            'discount_total' => 0.00,
            'tip_amount' => 1600.00,
            'total' => 17600.00,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 16000.00,
            'subtotal' => 16000.00,
            'notes' => 'Con salsa de chocolate extra',
        ]);

        $this->assertEquals('Mesa Terraza 1', $order->table->name);
        $this->assertCount(1, $order->items);
        $this->assertEquals($order->id, $table->activeOrder->id);

        // 4. Invoice and Payment
        $invoice = Invoice::create([
            'order_id' => $order->id,
            'invoice_number' => 'FAC-0001',
            'customer_name' => 'Juan Perez',
            'subtotal' => 16000.00,
            'total' => 17600.00,
            'billing_mode' => 'internal',
        ]);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'cash',
            'amount' => 17600.00,
        ]);

        $this->assertEquals('FAC-0001', $order->invoice->invoice_number);
        $this->assertCount(1, $invoice->payments);

        // 5. Cash Register and Movements
        $cash = CashRegister::create([
            'user_id' => $user->id,
            'opened_at' => now(),
            'opening_balance' => 50000.00,
            'status' => 'open',
        ]);

        $movement = CashMovement::create([
            'cash_register_id' => $cash->id,
            'user_id' => $user->id,
            'type' => 'cash_out',
            'category' => 'purchase',
            'amount' => 15000.00,
            'description' => 'Compra de hielo urgente',
        ]);

        $this->assertCount(1, $cash->movements);
        $this->assertEquals(15000.00, $cash->movements->first()->amount);

        // 6. Business Settings
        BusinessSetting::set('shop_name', 'Heladería Artesanal Dulce Nieve');
        $this->assertEquals('Heladería Artesanal Dulce Nieve', BusinessSetting::get('shop_name'));
    }
}
